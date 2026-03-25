package com.sqliarena.hql.controller;

import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.*;

/**
 * Lab 4: Criteria API Bypass (ORDER BY Injection)
 *
 * Vulnerability: The sort_by parameter is directly concatenated into the ORDER BY
 * clause of the HQL query. An attacker can inject HQL to break out of the ORDER BY
 * and query SecretOrder to get the flag.
 *
 * Attack examples:
 *   - Inject into sort_by to cause errors and enumerate:
 *     GET /api/lab4/query?customer_id=1&sort_by=amount,(SELECT+secretFlag+FROM+SecretOrder+WHERE+id=1)
 *   - UNION-style (if HQL supports it):
 *     GET /api/lab4/query?customer_id=1&sort_by=amount)--
 *   - Boolean-based via ORDER BY subquery:
 *     GET /api/lab4/query?customer_id=1&sort_by=(SELECT+CASE+WHEN+(SELECT+COUNT(*)+FROM+SecretOrder)>0+THEN+amount+ELSE+id+END)
 */
@RestController
@RequestMapping("/api/lab4")
@CrossOrigin(origins = "*")
public class Lab4Controller {

    @PersistenceContext
    private EntityManager entityManager;

    @GetMapping("/query")
    public ResponseEntity<Map<String, Object>> query(
            @RequestParam(name = "customer_id", defaultValue = "") String customerId,
            @RequestParam(name = "sort_by", defaultValue = "amount") String sortBy,
            @RequestParam(name = "order", defaultValue = "ASC") String order,
            @RequestParam(name = "min_amount", defaultValue = "") String minAmount,
            @RequestParam(name = "max_amount", defaultValue = "") String maxAmount) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            // Build HQL query with vulnerable ORDER BY
            StringBuilder hql = new StringBuilder("FROM Order o WHERE 1=1");

            if (!customerId.isEmpty()) {
                // VULNERABLE: customerId concatenated
                hql.append(" AND o.customerId = ").append(customerId);
            }

            if (!minAmount.isEmpty()) {
                // VULNERABLE: minAmount concatenated
                hql.append(" AND o.amount >= ").append(minAmount);
            }

            if (!maxAmount.isEmpty()) {
                // VULNERABLE: maxAmount concatenated
                hql.append(" AND o.amount <= ").append(maxAmount);
            }

            // VULNERABLE: sort_by is directly concatenated into ORDER BY
            hql.append(" ORDER BY o.").append(sortBy);

            // VULNERABLE: order direction also concatenated
            if ("DESC".equalsIgnoreCase(order)) {
                hql.append(" DESC");
            } else {
                hql.append(" ASC");
            }

            String hqlStr = hql.toString();
            response.put("query", hqlStr);

            List<?> results = entityManager.createQuery(hqlStr).getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            for (Object row : results) {
                Map<String, Object> rowMap = objectToMap(row);
                data.add(rowMap);
            }

            response.put("data", data);
            response.put("columns", data.isEmpty()
                    ? List.of("id", "customerId", "product", "amount", "status")
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

    /**
     * Direct HQL query endpoint for more advanced injection.
     * Accepts raw WHERE clause and ORDER BY.
     */
    @GetMapping("/advanced")
    public ResponseEntity<Map<String, Object>> advancedQuery(
            @RequestParam(name = "where", defaultValue = "1=1") String whereClause,
            @RequestParam(name = "order_by", defaultValue = "id ASC") String orderBy) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            // VULNERABLE: Entire WHERE and ORDER BY from user input
            String hql = "FROM Order o WHERE " + whereClause + " ORDER BY o." + orderBy;
            response.put("query", hql);

            List<?> results = entityManager.createQuery(hql).getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            for (Object row : results) {
                data.add(objectToMap(row));
            }

            response.put("data", data);
            response.put("columns", data.isEmpty()
                    ? List.of("id", "customerId", "product", "amount", "status")
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

    /**
     * Secret endpoint: directly query SecretOrder (for verification / hints).
     * Normally hidden but reachable if attacker discovers it.
     */
    @GetMapping("/secret")
    public ResponseEntity<Map<String, Object>> secretQuery(
            @RequestParam(name = "hql", defaultValue = "FROM SecretOrder") String hql) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            response.put("query", hql);

            List<?> results = entityManager.createQuery(hql).getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            for (Object row : results) {
                data.add(objectToMap(row));
            }

            response.put("data", data);
            response.put("rowCount", data.size());

        } catch (Exception e) {
            response.put("error", e.getMessage());
        }

        return ResponseEntity.ok(response);
    }

    @GetMapping("/info")
    public ResponseEntity<Map<String, Object>> info() {
        Map<String, Object> info = new LinkedHashMap<>();
        info.put("lab", "Lab 4: Criteria API Bypass (ORDER BY Injection)");
        info.put("endpoint", "GET /api/lab4/query?customer_id=1&sort_by=amount&order=ASC&min_amount=&max_amount=");
        info.put("description", "Query orders with sorting and filtering. The sort_by parameter controls the ORDER BY clause.");
        info.put("sortable_fields", List.of("id", "customerId", "product", "amount", "status"));
        info.put("order_directions", List.of("ASC", "DESC"));
        info.put("example", "/api/lab4/query?customer_id=1&sort_by=amount&order=DESC");
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
