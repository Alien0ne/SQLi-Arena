package com.sqliarena.hql.controller;

import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.*;

/**
 * Lab 1: Entity Name Injection
 *
 * Vulnerability: Entity name is taken directly from user input and concatenated
 * into the HQL query string. An attacker can change the entity name to query
 * AdminCredential instead of Product, leaking the flag from secretNote.
 *
 * Attack example:
 *   GET /api/lab1/query?entity=AdminCredential&filter_field=username&filter_value=admin
 */
@RestController
@RequestMapping("/api/lab1")
@CrossOrigin(origins = "*")
public class Lab1Controller {

    @PersistenceContext
    private EntityManager entityManager;

    private static final Set<String> KNOWN_ENTITIES = Set.of(
        "Product", "AdminCredential", "AuditLog"
    );

    @GetMapping("/query")
    public ResponseEntity<Map<String, Object>> query(
            @RequestParam(defaultValue = "Product") String entity,
            @RequestParam(name = "filter_field", defaultValue = "") String filterField,
            @RequestParam(name = "filter_value", defaultValue = "") String filterValue) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            // VULNERABLE: Entity name from user input concatenated into HQL
            String hql;
            if (!filterField.isEmpty() && !filterValue.isEmpty()) {
                // VULNERABLE: Both entity name AND filter values are concatenated
                hql = "FROM " + entity + " WHERE " + filterField + " = '" + filterValue + "'";
            } else if (!filterField.isEmpty()) {
                hql = "SELECT " + filterField + " FROM " + entity;
            } else {
                hql = "FROM " + entity;
            }

            response.put("query", hql);

            List<?> results = entityManager.createQuery(hql).getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            for (Object row : results) {
                Map<String, Object> rowMap = objectToMap(row);
                data.add(rowMap);
            }

            response.put("data", data);
            response.put("columns", data.isEmpty() ? List.of() : new ArrayList<>(data.get(0).keySet()));
            response.put("rowCount", data.size());

        } catch (Exception e) {
            response.put("error", e.getMessage());
            response.put("errorType", e.getClass().getSimpleName());
            // Hint: on unknown entity, list available entities
            if (e.getMessage() != null && (e.getMessage().contains("not mapped") || e.getMessage().toLowerCase().contains("could not resolve"))) {
                response.put("hint", "Available entities: " + KNOWN_ENTITIES);
            }
        }

        return ResponseEntity.ok(response);
    }

    @GetMapping("/info")
    public ResponseEntity<Map<String, Object>> info() {
        Map<String, Object> info = new LinkedHashMap<>();
        info.put("lab", "Lab 1: Entity Name Injection");
        info.put("endpoint", "GET /api/lab1/query?entity=Product&filter_field=&filter_value=");
        info.put("description", "Query products from the database. Change entity/filter parameters to explore.");
        info.put("entities", KNOWN_ENTITIES);
        info.put("example", "/api/lab1/query?entity=Product&filter_field=category&filter_value=Electronics");
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
