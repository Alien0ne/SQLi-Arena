package com.sqliarena.hql.controller;

import com.sqliarena.hql.entity.SecretVault;
import com.sqliarena.hql.entity.User;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import jakarta.persistence.metamodel.EntityType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.lang.annotation.Annotation;
import java.lang.reflect.Field;
import java.util.*;

/**
 * Lab 2: .class Metadata Access
 *
 * Vulnerability: User controls the SELECT fields, which allows dot-notation
 * access to class metadata. While real Hibernate .class access is limited,
 * this controller simulates metadata access by detecting dot-notation fields
 * and resolving them via Java reflection.
 *
 * Attack examples:
 *   GET /api/lab2/query?entity=User&fields=class.name
 *   GET /api/lab2/query?entity=User&fields=class.declaredFields
 *   GET /api/lab2/query?entity=User&fields=class.annotations
 *   GET /api/lab2/query?entity=SecretVault&fields=id,vaultKey,vaultValue
 */
@RestController
@RequestMapping("/api/lab2")
@CrossOrigin(origins = "*")
public class Lab2Controller {

    @PersistenceContext
    private EntityManager entityManager;

    private static final Map<String, Class<?>> ENTITY_MAP = Map.of(
        "User", User.class,
        "SecretVault", SecretVault.class
    );

    @GetMapping("/query")
    public ResponseEntity<Map<String, Object>> query(
            @RequestParam(defaultValue = "User") String entity,
            @RequestParam(defaultValue = "id,username") String fields,
            @RequestParam(name = "where_field", defaultValue = "") String whereField,
            @RequestParam(name = "where_value", defaultValue = "") String whereValue) {

        Map<String, Object> response = new LinkedHashMap<>();

        try {
            String[] fieldList = fields.split(",");

            // Check if any field uses dot-notation (metadata access)
            boolean hasMetadata = Arrays.stream(fieldList)
                    .anyMatch(f -> f.trim().startsWith("class."));

            if (hasMetadata) {
                // VULNERABLE: Simulate .class metadata access via reflection
                return handleMetadataQuery(entity, fieldList, response);
            }

            // VULNERABLE: fields and entity are concatenated into HQL
            String selectClause = String.join(", e.", fieldList);
            selectClause = "e." + selectClause.trim();

            String hql;
            if (!whereField.isEmpty() && !whereValue.isEmpty()) {
                // VULNERABLE: where clause values concatenated
                hql = "SELECT " + selectClause + " FROM " + entity + " e WHERE e."
                        + whereField + " = '" + whereValue + "'";
            } else {
                hql = "SELECT " + selectClause + " FROM " + entity + " e";
            }

            response.put("query", hql);

            List<?> results = entityManager.createQuery(hql).getResultList();

            List<Map<String, Object>> data = new ArrayList<>();
            String[] cleanFields = Arrays.stream(fieldList)
                    .map(String::trim)
                    .toArray(String[]::new);

            for (Object row : results) {
                Map<String, Object> rowMap = new LinkedHashMap<>();
                if (row instanceof Object[] arr) {
                    for (int i = 0; i < cleanFields.length && i < arr.length; i++) {
                        rowMap.put(cleanFields[i], arr[i]);
                    }
                } else {
                    // Single field selected
                    rowMap.put(cleanFields[0], row);
                }
                data.add(rowMap);
            }

            response.put("data", data);
            response.put("columns", List.of(cleanFields));
            response.put("rowCount", data.size());

        } catch (Exception e) {
            response.put("error", e.getMessage());
            response.put("errorType", e.getClass().getSimpleName());
            if (e.getMessage() != null && e.getMessage().contains("could not resolve")) {
                response.put("hint", "Available entities: " + ENTITY_MAP.keySet());
            }
        }

        return ResponseEntity.ok(response);
    }

    private ResponseEntity<Map<String, Object>> handleMetadataQuery(
            String entityName, String[] fieldList, Map<String, Object> response) {

        Class<?> entityClass = ENTITY_MAP.get(entityName);
        if (entityClass == null) {
            // VULNERABLE: Try to resolve unknown entity names - information disclosure
            try {
                // Attempt to find class in JPA metamodel
                Set<EntityType<?>> entities = entityManager.getMetamodel().getEntities();
                for (EntityType<?> et : entities) {
                    if (et.getName().equalsIgnoreCase(entityName)) {
                        entityClass = et.getJavaType();
                        break;
                    }
                }
            } catch (Exception ignored) {}

            if (entityClass == null) {
                response.put("error", "Unknown entity: " + entityName
                        + ". Available: " + ENTITY_MAP.keySet()
                        + ". All JPA entities: " + getEntityNames());
                return ResponseEntity.ok(response);
            }
        }

        List<Map<String, Object>> data = new ArrayList<>();
        Map<String, Object> metaRow = new LinkedHashMap<>();

        for (String field : fieldList) {
            field = field.trim();
            switch (field) {
                case "class.name":
                    metaRow.put("class.name", entityClass.getName());
                    break;
                case "class.simpleName":
                    metaRow.put("class.simpleName", entityClass.getSimpleName());
                    break;
                case "class.declaredFields":
                    Field[] declaredFields = entityClass.getDeclaredFields();
                    List<Map<String, String>> fieldInfo = new ArrayList<>();
                    for (Field f : declaredFields) {
                        Map<String, String> fi = new LinkedHashMap<>();
                        fi.put("name", f.getName());
                        fi.put("type", f.getType().getSimpleName());
                        fieldInfo.add(fi);
                    }
                    metaRow.put("class.declaredFields", fieldInfo);
                    break;
                case "class.annotations":
                    Annotation[] annotations = entityClass.getAnnotations();
                    List<String> annotationNames = new ArrayList<>();
                    for (Annotation a : annotations) {
                        annotationNames.add(a.annotationType().getSimpleName() + ": " + a.toString());
                    }
                    metaRow.put("class.annotations", annotationNames);
                    break;
                case "class.superclass":
                    metaRow.put("class.superclass", entityClass.getSuperclass().getName());
                    break;
                case "class.package":
                    metaRow.put("class.package", entityClass.getPackageName());
                    break;
                default:
                    // For non-metadata fields, try to get column info
                    metaRow.put(field, "(metadata mode - use without class. prefix for data)");
                    break;
            }
        }

        data.add(metaRow);
        response.put("data", data);
        response.put("columns", new ArrayList<>(metaRow.keySet()));
        response.put("mode", "metadata");
        response.put("entity", entityName);
        response.put("entityClass", entityClass.getName());

        return ResponseEntity.ok(response);
    }

    @GetMapping("/entities")
    public ResponseEntity<Map<String, Object>> listEntities() {
        Map<String, Object> response = new LinkedHashMap<>();
        List<Map<String, Object>> entities = new ArrayList<>();

        Set<EntityType<?>> entityTypes = entityManager.getMetamodel().getEntities();
        for (EntityType<?> et : entityTypes) {
            Map<String, Object> info = new LinkedHashMap<>();
            info.put("name", et.getName());
            info.put("javaType", et.getJavaType().getName());

            List<String> attributes = new ArrayList<>();
            et.getAttributes().forEach(a -> attributes.add(a.getName()));
            info.put("attributes", attributes);

            entities.add(info);
        }

        response.put("entities", entities);
        return ResponseEntity.ok(response);
    }

    @GetMapping("/info")
    public ResponseEntity<Map<String, Object>> info() {
        Map<String, Object> info = new LinkedHashMap<>();
        info.put("lab", "Lab 2: .class Metadata Access");
        info.put("endpoint", "GET /api/lab2/query?entity=User&fields=id,username&where_field=&where_value=");
        info.put("description", "Query user data with field selection. Supports dot-notation for metadata.");
        info.put("supported_metadata", List.of("class.name", "class.declaredFields", "class.annotations", "class.simpleName", "class.superclass", "class.package"));
        info.put("example_data", "/api/lab2/query?entity=User&fields=id,username,email");
        info.put("example_meta", "/api/lab2/query?entity=User&fields=class.name,class.declaredFields");
        return ResponseEntity.ok(info);
    }

    private List<String> getEntityNames() {
        List<String> names = new ArrayList<>();
        entityManager.getMetamodel().getEntities().forEach(e -> names.add(e.getName()));
        return names;
    }
}
