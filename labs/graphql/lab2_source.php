<?php
// ============================================================
// Lab 2: Field Suggestion Exploitation (Source Code)
// ============================================================
// Engine: GraphQL (Simulated)
// Data: /home/kali/SQLi-Arena/data/graphql/lab2.json
// ============================================================

// Schema types:
//   User -> id, username, email, secretFlag (hidden), internalNotes (hidden)
//   Product -> id, name, price

// Introspection is DISABLED:
if (strpos($query, '__schema') !== false || strpos($query, '__type') !== false) {
    return error('Introspection is disabled on this endpoint.');
}

// When an invalid field is requested, the server suggests valid fields
// using Levenshtein distance matching:
//
// Query: { user(id: 1) { id, secret } }
// Error: "Cannot query field 'secret' on type 'User'.
//         Did you mean: 'secretFlag'?"
//
// The suggestion algorithm checks ALL fields including hidden ones!

// --- Why is this vulnerable? ---
// 1. Introspection is disabled (good), but error messages leak field names
// 2. The suggestion algorithm uses Levenshtein distance to find "close" fields
// 3. Hidden fields (secretFlag, internalNotes) are included in suggestions
// 4. An attacker can enumerate fields by trying various invalid names:
//    - "secret" -> suggests "secretFlag"
//    - "internal" -> suggests "internalNotes"
//    - "flag" -> suggests "secretFlag"
//    - "notes" -> suggests "internalNotes"

// Once discovered, the hidden field can be queried normally:
// { user(id: 1) { id, username, secretFlag } }

// --- Secure Version ---
// 1. Disable field suggestions in error messages:
//    - Configure GraphQL to not include "Did you mean" suggestions
//    - Return generic "field not found" errors
// 2. Remove hidden fields from the schema entirely if they should not be queryable
// 3. Use field-level authorization instead of hiding fields
// 4. Tools: graphql-shield, graphql-armor
?>
