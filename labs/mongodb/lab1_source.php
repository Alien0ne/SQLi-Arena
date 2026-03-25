<?php
/*
 * SQLi-Arena. MongoDB Lab 1: Auth Bypass via $ne Operator
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Data store (simulates MongoDB collection)
$dataFile = __DIR__ . '/../../data/mongodb/lab1.json';
$collection = json_decode(file_get_contents($dataFile), true);

// Get user input from POST
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Build MongoDB query. VULNERABLE: user input used directly
$query = ['username' => $username, 'password' => $password];

// Simulated MongoDB find() with operator support
function matches($field, $input) {
    if (is_array($input)) {
        // NoSQL operator injection happens here!
        // PHP converts password[$ne]= into ["$ne" => ""]
        if (array_key_exists('$ne', $input))  return $field !== $input['$ne'];
        if (array_key_exists('$gt', $input))  return $field > $input['$gt'];
        if (array_key_exists('$regex', $input)) return preg_match('/'.$input['$regex'].'/', $field);
        return false;
    }
    return $field === $input;
}

// Simulated db.users.findOne(query)
foreach ($collection['users'] as $user) {
    if (matches($user['username'], $username) &&
        matches($user['password'], $password)) {
        echo "Welcome, " . $user['username'];
        // If admin: show sensitive data
        break;
    }
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * PHP automatically converts bracket notation in form parameters into arrays:
 *   password[$ne]=  -->  $_POST['password'] = ['$ne' => '']
 *
 * This array is passed directly to the query matcher, which interprets
 * $ne as the MongoDB "not equal" operator.
 *
 * The query becomes: {username: "admin", password: {$ne: ""}}
 * This matches any user named "admin" whose password is NOT empty.
 *
 * FIX: Always cast/validate input types before using in queries:
 *   $password = (string)$_POST['password'];
 */
