<?php
// ============================================================
// Lab 3: Native Query Escape (Source Code)
// ============================================================
// Engine: HQL / Hibernate (Simulated) with Native SQL boundary
// Data: /home/kali/SQLi-Arena/data/hql/lab3.json
// ============================================================

// Hibernate entity: Employee(id, name, department, salary)
// Native SQL table: system_secrets(id, secret_name, secret_value). NOT an HQL entity

// User provides department name via GET parameter
$dept = $_GET['dept'];  // NOT sanitized!

// HQL query constructed with string concatenation
$hql = "FROM Employee WHERE department = '$dept'";
//                                        ^^^^^^
//                              User input in HQL string!

// Hibernate translates this HQL to native SQL:
// SELECT e.id, e.name, e.department, e.salary
// FROM employees e
// WHERE e.department = '<user_input>'

// --- Why is this vulnerable? ---
// HQL injection is similar to SQL injection but operates at the
// Hibernate layer. However, since HQL is ultimately translated to SQL,
// a UNION SELECT can escape the HQL boundary and access native SQL tables
// that are NOT mapped as Hibernate entities.
//
// Normal input: "Engineering"
// HQL: FROM Employee WHERE department = 'Engineering'
// SQL: SELECT ... FROM employees WHERE department = 'Engineering'
//
// Malicious input: "x' UNION ALL SELECT id, secret_name, secret_value, NULL FROM system_secrets -- "
// HQL: FROM Employee WHERE department = 'x' UNION ALL SELECT ... FROM system_secrets -- '
// SQL: SELECT e.id, e.name, e.department, e.salary FROM employees e
//      WHERE e.department = 'x'
//      UNION ALL SELECT id, secret_name, secret_value, NULL FROM system_secrets --'
//
// The UNION accesses system_secrets which exists in the database
// but is NOT mapped as a Hibernate entity, bypassing HQL restrictions.

// --- Secure Version ---
// Use positional or named parameters:
// $query = session.createQuery("FROM Employee WHERE department = :dept");
// $query.setParameter("dept", $dept);
?>
