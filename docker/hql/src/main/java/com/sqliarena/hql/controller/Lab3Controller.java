package com.sqliarena.hql.controller;

import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import jakarta.persistence.Query;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.*;

/**
 * Lab 3: Native Query Escape
 *
 * Vulnerability: When use_native=true, the controller constructs a native SQL
 * query with string concatenation instead of parameterized queries. The HQL
 * mode uses parameterized queries and is safe. The native SQL mode is injectable.
 *
 * Attack example:
 *   GET /api/lab3/query?department=x' UNION SELECT id,secret_key,secret_value,0 FROM internal_secrets--&use_native=true
 */
@RestController
@RequestMapping("/api/lab3")
@CrossOrigin(origins = "*")
public class Lab3Controller {

    @PersistenceContext
    private EntityManager entityManager;

    @GetMapping("/query")
    public ResponseEntity<Map<String, Object>> query(
            @RequestParam(defaultValue = "Engineering") String department,
            @RequestParam(name = "use_native", defaultValue = "false") String useNative) {

        Map<String, Object> response = new LinkedHashMap<>();
        boolean nativeMode = "true".equalsIgnoreCase(useNative) || "1".equals(useNative);

        try {
            if (nativeMode) {
                // VULNERABLE: Native SQL with string concatenation
                String sql = "SELECT id, name, department, salary FROM employees WHERE department = '" + department + "'";
                response.put("query", sql);
                response.put("mode", "native_sql");

                Query nativeQuery = entityManager.createNativeQuery(sql);
                List<?> results = nativeQuery.getResultList();

                List<Map<String, Object>> data = new ArrayList<>();
                // Native queries return Object[] arrays
                String[] columns = {"id", "name", "department", "salary"};

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

            } else {
                // SAFE: HQL with parameterized query
                String hql = "FROM Employee WHERE department = :dept";
                response.put("query", hql);
                response.put("mode", "hql_parameterized");

                List<?> results = entityManager.createQuery(hql)
                        .setParameter("dept", department)
                        .getResultList();

                List<Map<String, Object>> data = new ArrayList<>();
                for (Object row : results) {
                    Map<String, Object> rowMap = objectToMap(row);
                    data.add(rowMap);
                }

                response.put("data", data);
                response.put("columns", data.isEmpty() ? List.of() : new ArrayList<>(data.get(0).keySet()));
                response.put("rowCount", data.size());
            }

        } catch (Exception e) {
            response.put("error", e.getMessage());
            response.put("errorType", e.getClass().getSimpleName());
            // Verbose: include the cause chain for information disclosure
            if (e.getCause() != null) {
                response.put("cause", e.getCause().getMessage());
                if (e.getCause().getCause() != null) {
                    response.put("rootCause", e.getCause().getCause().getMessage());
                }
            }
        }

        return ResponseEntity.ok(response);
    }

    @GetMapping("/info")
    public ResponseEntity<Map<String, Object>> info() {
        Map<String, Object> info = new LinkedHashMap<>();
        info.put("lab", "Lab 3: Native Query Escape");
        info.put("endpoint", "GET /api/lab3/query?department=Engineering&use_native=false");
        info.put("description", "Query employees by department. Toggle between safe HQL and vulnerable native SQL mode.");
        info.put("modes", Map.of(
            "use_native=false", "Safe HQL with parameterized queries",
            "use_native=true", "Native SQL (potentially vulnerable)"
        ));
        info.put("tables", List.of("employees", "internal_secrets"));
        info.put("example_safe", "/api/lab3/query?department=Engineering&use_native=false");
        info.put("example_native", "/api/lab3/query?department=Engineering&use_native=true");
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
