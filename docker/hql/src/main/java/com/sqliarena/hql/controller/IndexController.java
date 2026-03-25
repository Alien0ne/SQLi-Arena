package com.sqliarena.hql.controller;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.*;

/**
 * Root index controller providing API overview and lab listing.
 */
@RestController
@CrossOrigin(origins = "*")
public class IndexController {

    @GetMapping("/")
    public ResponseEntity<Map<String, Object>> index() {
        Map<String, Object> response = new LinkedHashMap<>();
        response.put("application", "SQLi-Arena HQL Labs");
        response.put("version", "1.0.0");
        response.put("engine", "Hibernate / HQL (H2 Database)");

        List<Map<String, String>> labs = new ArrayList<>();

        labs.add(Map.of(
            "id", "lab1",
            "name", "Entity Name Injection",
            "endpoint", "/api/lab1/query",
            "info", "/api/lab1/info"
        ));

        labs.add(Map.of(
            "id", "lab2",
            "name", ".class Metadata Access",
            "endpoint", "/api/lab2/query",
            "info", "/api/lab2/info"
        ));

        labs.add(Map.of(
            "id", "lab3",
            "name", "Native Query Escape",
            "endpoint", "/api/lab3/query",
            "info", "/api/lab3/info"
        ));

        labs.add(Map.of(
            "id", "lab4",
            "name", "Criteria API Bypass",
            "endpoint", "/api/lab4/query",
            "info", "/api/lab4/info"
        ));

        labs.add(Map.of(
            "id", "lab5",
            "name", "Cache Poisoning",
            "endpoint", "/api/lab5/query",
            "info", "/api/lab5/info"
        ));

        response.put("labs", labs);
        response.put("health", "/actuator/health");
        response.put("h2_console", "/h2-console");

        return ResponseEntity.ok(response);
    }
}
