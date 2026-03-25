<?php
// ============================================================
// Lab 3: Alias-Based Auth Bypass (Source Code)
// ============================================================
// Engine: GraphQL (Simulated)
// Data: /home/kali/SQLi-Arena/data/graphql/lab3.json
// ============================================================

// User type fields: id, name, email, password, apiKey, flag
// Restricted fields: password, apiKey, flag (admin only)

// The access control works by parsing the query string and
// checking if restricted field names appear as standalone tokens.

$query = $_POST['query'];
$restricted = ['password', 'apiKey', 'flag'];

// VULNERABLE access control implementation:
// Split query into tokens and check each one
$tokens = preg_split('/[\s,]+/', $fieldsSection);
foreach ($tokens as $token) {
    if (in_array($token, $restricted)) {
        return error("Access denied: field '$token' requires admin privileges.");
    }
}

// --- Why is this vulnerable? ---
// GraphQL aliases use the format: aliasName: actualField
// Example: { user(id: 1) { myFlag: flag } }
//
// The token parser splits on whitespace and commas:
// Tokens: ["myFlag:", "flag"]
//
// Wait: "flag" appears as a token! But...
// When using: { user(id: 1) { f: flag } }
// After the alias "f:", "flag" appears but the access control
// actually parses the braces content and checks only standalone tokens.
//
// The real bypass: aliases change how the parser sees the fields.
// With comma separation:
// { user(id: 1) { name, f:flag } }
// Tokens from splitting: ["name", "f:flag"]
// "f:flag" is not in the restricted list -> BYPASS!

// --- Correct Implementation ---
// 1. Parse the GraphQL AST properly (don't regex the query string)
// 2. Check field names AFTER alias resolution in the parsed AST
// 3. Use a proper GraphQL authorization library (graphql-shield)
// 4. Implement field-level resolvers with auth checks:
//    @auth(requires: ADMIN) directive on restricted fields
?>
