<?php
// ============================================================
// Lab 1: Introspection. Schema Discovery (Source Code)
// ============================================================
// Engine: GraphQL (Simulated)
// Data: /home/kali/SQLi-Arena/data/graphql/lab1.json
// ============================================================

// Schema types defined:
//   Query (root) -> user(id), users, product(id), products(category)
//   User -> id, username, email, role
//   Product -> id, name, price, category
//   SecretFlag -> id, flag, description     <-- HIDDEN TYPE!
//   Mutation -> updateProfile(id, email)

// The GraphQL endpoint processes queries like:
// POST /graphql
// { "query": "{ users { id username email } }" }

// --- Why is introspection dangerous? ---
// GraphQL introspection is a built-in feature that allows querying
// the schema itself. It reveals ALL types, fields, arguments, and
// relationships: including types meant to be internal/hidden.

// Introspection query:
// { __schema { types { name fields { name type { name } } } } }
//
// This returns ALL types including SecretFlag, which was not intended
// to be discoverable by regular API users.

// Once the attacker discovers SecretFlag, they can query it:
// { secretflags { id flag description } }

// --- Secure Version ---
// 1. Disable introspection in production:
//    GraphQL.newGraphQL(schema)
//      .instrumentation(new NoIntrospectionGraphqlInstrumentation())
//      .build();
// 2. If introspection must be enabled, use authorization:
//    Only allow introspection for authenticated admin users
// 3. Use a schema allowlist/persisted queries
// 4. Remove unused types from the schema entirely
?>
