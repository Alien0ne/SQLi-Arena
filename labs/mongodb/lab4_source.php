<?php
/*
 * SQLi-Arena. MongoDB Lab 4: Server-Side JS Injection via $where
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab4.json';
$collection = json_decode(file_get_contents($dataFile), true);

$search = $_GET['search'] ?? '';

// VULNERABLE: User input concatenated into $where JavaScript string
$where_expr = "this.username == '$search'";

// In real MongoDB, this would be:
// db.users.find({$where: "this.username == 'USER_INPUT'"})

// Simulated evaluation
foreach ($collection['users'] as $user) {
    if (evaluate_where($user, $where_expr)) {
        echo "Found: " . $user['username'] . " (" . $user['email'] . ")";
    }
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * The $where clause accepts JavaScript expressions evaluated server-side.
 * User input is concatenated into the JS string without sanitization:
 *
 *   this.username == 'INPUT'
 *
 * Injecting: ' || 'a'=='a
 * Becomes:   this.username == '' || 'a'=='a'
 * Result:    Always true: returns ALL documents
 *
 * For blind extraction:
 *   Injecting: ' || this.password.startsWith('FLAG{') || 'a'=='b
 *   Becomes:   this.username == '' || this.password.startsWith('FLAG{') || 'a'=='b'
 *   Result:    Returns users whose password starts with 'FLAG{'
 *
 * This is far more dangerous than operator injection because the attacker
 * can execute arbitrary JavaScript expressions, including:
 *   - String manipulation (startsWith, charAt, substring)
 *   - Comparison chains
 *   - sleep() for timing attacks
 *   - In older MongoDB versions: system command execution
 *
 * FIX: Never use $where with user input. Use standard query operators instead:
 *   db.users.find({username: sanitizedInput})
 */
