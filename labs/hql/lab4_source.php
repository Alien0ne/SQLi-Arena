<?php
// ============================================================
// Lab 4: Criteria API Bypass (Source Code)
// ============================================================
// Engine: HQL / Hibernate (Simulated) Criteria API
// Data: /home/kali/SQLi-Arena/data/hql/lab4.json
// ============================================================

// Entities: Order(id, customer_name, product, amount, status)
//           InternalConfig(id, config_key, config_value): contains flag

// --- Vulnerability 1: Entity Name Manipulation ---
// The entity name in the Criteria builder comes from user input:
$entityName = $_GET['entity'];  // Default "Order" but user-controlled

// session.createCriteria($entityName.class)
// Changing to "InternalConfig" accesses the restricted entity

// --- Vulnerability 2: Restrictions.sqlRestriction() ---
// The "Advanced Filter" uses sqlRestriction which passes raw SQL:
$sqlRestriction = $_GET['sql_restriction'];  // Raw SQL from user!

// Java code equivalent:
// Criteria criteria = session.createCriteria(Order.class);
// criteria.add(Restrictions.sqlRestriction($sqlRestriction));
// List results = criteria.list();

// This translates to:
// SELECT * FROM orders WHERE (<sqlRestriction>)
//
// If sqlRestriction = "1=1 UNION SELECT id, config_key, config_value, NULL, NULL FROM internal_config"
// The query becomes:
// SELECT * FROM orders WHERE (1=1 UNION SELECT ... FROM internal_config)

// --- Attack Vectors ---
// 1. Entity name: entity=InternalConfig -> queries InternalConfig directly
// 2. sqlRestriction: "1=1) UNION SELECT id, config_key, config_value, NULL, NULL FROM internal_config --"
// 3. Tautology: "1=1 OR 1=1" -> returns all records

// --- Why is Restrictions.sqlRestriction() dangerous? ---
// It is designed to allow raw SQL in the WHERE clause for edge cases
// where the Criteria API is not expressive enough. But it completely
// bypasses Hibernate's parameterization and type safety.

// --- Secure Version ---
// 1. Never use Restrictions.sqlRestriction() with user input
// 2. Validate entity names against an allowlist
// 3. Use typed Criteria restrictions only:
//    criteria.add(Restrictions.eq("status", userInput));
// 4. Use JPA Criteria API (type-safe) instead of legacy Hibernate Criteria
?>
