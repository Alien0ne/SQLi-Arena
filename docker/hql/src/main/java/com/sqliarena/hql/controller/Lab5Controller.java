package com.sqliarena.hql.controller;

import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import jakarta.persistence.Query;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.*;

/**
 * Lab 5: Cache Poisoning (HQL Injection via cache_region)
 *
 * Vulnerability: The cache_region parameter is concatenated into the HQL query
 * as a comment/hint: the query becomes:
 *   FROM Article WHERE title LIKE '%search%' /* CACHE(region) * /
 *
 * Since the cache_region is inside a comment that gets closed prematurely,
 * an attacker can break out of the comment and inject additional HQL/SQL.
 *
 * Actually, for practical exploitation: the cache_region is used in constructing
 * the full query string which is then executed. The attacker can inject via
 * the cache_region to modify the query.
 *
 * Attack example:
 *   cache_region=articles) * / FROM CacheConfig WHERE cacheKey='master_key' OR cacheKey LIKE '%25
 *
 * Alternative approach: Since HQL comments might not work the same way as SQL,
 * we implement this as a native query where the cache region is injected.
 */
@RestController
@RequestMapping("/api/lab5")
@CrossOrigin(origins = "*")
public class Lab5Controller {

    @PersistenceContext
    private EntityManager entityManager;

    @GetMapping("/query")
    public ResponseEntity<Map<String, Object>> query(
            @RequestParam(defaultValue = "") String search,
            @RequestParam(name = "cache_region", defaultValue = "articles") String cacheRegion) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            // VULNERABLE: cache_region is concatenated into native SQL query
            // The "cache hint" is embedded as a comment in the SQL
            // Attacker can break out of the comment to inject SQL
            String sql = "SELECT id, title, content, author, cached FROM articles WHERE title LIKE '%" + search
                    + "%' /* CACHE(" + cacheRegion + ") */";

            response.put("query", sql);
            response.put("cache_region", cacheRegion);

            Query nativeQuery = entityManager.createNativeQuery(sql);
            List<?> results = nativeQuery.getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            String[] columns = {"id", "title", "content", "author", "cached"};

            for (Object row : results) {
                Map<String, Object> rowMap = new LinkedHashMap<>();
                if (row instanceof Object[] arr) {
                    for (int i = 0; i < arr.length; i++) {
                        String colName = (i < columns.length) ? columns[i] : "col_" + i;
                        rowMap.put(colName, arr[i]);
                    }
                } else {
                    rowMap.put("value", row);
                }
                data.add(rowMap);
            }

            response.put("data", data);
            if (!data.isEmpty()) {
                response.put("columns", new ArrayList<>(data.get(0).keySet()));
            } else {
                response.put("columns", List.of(columns));
            }
            response.put("rowCount", data.size());

        } catch (Exception e) {
            response.put("error", e.getMessage());
            response.put("errorType", e.getClass().getSimpleName());
            if (e.getCause() != null) {
                response.put("cause", e.getCause().getMessage());
                if (e.getCause().getCause() != null) {
                    response.put("rootCause", e.getCause().getCause().getMessage());
                }
            }
        }

        return ResponseEntity.ok(response);
    }

    /**
     * Alternative injection point: search_hql endpoint uses HQL with concatenation.
     * The cache_region modifies the query behavior.
     */
    @GetMapping("/search_hql")
    public ResponseEntity<Map<String, Object>> searchHql(
            @RequestParam(defaultValue = "") String search,
            @RequestParam(name = "cache_region", defaultValue = "articles") String cacheRegion) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            // VULNERABLE: Both search and cache_region are concatenated
            // The cache_region breaks out of the intended query structure
            String hql = "FROM Article a WHERE a.title LIKE '%" + search + "%'"
                    + " AND a.cached = true AND '" + cacheRegion + "' = '" + cacheRegion + "'";

            response.put("query", hql);
            response.put("mode", "hql");

            List<?> results = entityManager.createQuery(hql).getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            for (Object row : results) {
                data.add(objectToMap(row));
            }

            response.put("data", data);
            response.put("columns", data.isEmpty()
                    ? List.of("id", "title", "content", "author", "cached")
                    : new ArrayList<>(data.get(0).keySet()));
            response.put("rowCount", data.size());

        } catch (Exception e) {
            response.put("error", e.getMessage());
            response.put("errorType", e.getClass().getSimpleName());
            if (e.getCause() != null) {
                response.put("cause", e.getCause().getMessage());
            }
        }

        return ResponseEntity.ok(response);
    }

    @GetMapping("/info")
    public ResponseEntity<Map<String, Object>> info() {
        Map<String, Object> info = new LinkedHashMap<>();
        info.put("lab", "Lab 5: Cache Poisoning (HQL/SQL Injection via cache_region)");
        info.put("endpoint", "GET /api/lab5/query?search=&cache_region=articles");
        info.put("description", "Search articles with caching. The cache_region parameter controls the cache hint.");
        info.put("tables", List.of("articles", "cache_config"));
        info.put("example", "/api/lab5/query?search=Spring&cache_region=articles");
        info.put("alternative", "/api/lab5/search_hql?search=Spring&cache_region=articles");
        return ResponseEntity.ok(info);
    }

    private Map<String, Object> objectToMap(Object obj) {
        Map<String, Object> map = new LinkedHashMap<>();
        if (obj == null) return map;

        java.lang.reflect.Field[] fields = obj.getClass().getDeclaredFields();
        for (java.lang.reflect.Field field : fields) {
            field.setAccessible(true);
            try {
                map.put(field.getName(), field.get(obj));
            } catch (IllegalAccessException e) {
                map.put(field.getName(), "ACCESS_DENIED");
            }
        }
        return map;
    }
}
