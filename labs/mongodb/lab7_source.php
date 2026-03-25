<?php
/*
 * SQLi-Arena. MongoDB Lab 7: JSON Parameter Pollution
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab7.json';
$collection = json_decode(file_get_contents($dataFile), true);

// The API accepts both form data and JSON body
$input = null;

// Check for JSON body
$raw_body = file_get_contents('php://input');
if (!empty($raw_body)) {
    $json_input = json_decode($raw_body, true);
    if ($json_input !== null) {
        $input = $json_input; // VULNERABLE: raw JSON used directly!
    }
}

// Fall back to form data
if ($input === null) {
    $input = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? ''
    ];
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// VULNERABLE: $username and $password may be arrays (objects) from JSON!
// JSON: {"username":"admin","password":{"$ne":""}}
// $password becomes: ['$ne' => '']
$query = ['username' => $username, 'password' => $password];

function matches($field, $input) {
    if (is_array($input)) {
        if (array_key_exists('$ne', $input)) return $field !== $input['$ne'];
        if (array_key_exists('$gt', $input)) return $field > $input['$gt'];
        if (array_key_exists('$regex', $input)) return preg_match('/'.$input['$regex'].'/', $field);
        return false;
    }
    return $field === $input;
}

foreach ($collection['users'] as $user) {
    if (matches($user['username'], $username) &&
        matches($user['password'], $password)) {
        echo "Authenticated: " . $user['username'];
        break;
    }
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * Two attack vectors exist:
 *
 * 1. URL-encoded form (PHP array syntax):
 *    POST: username=admin&password[$ne]=
 *    PHP converts password[$ne]= into ['$ne' => '']
 *
 * 2. JSON body (direct object injection):
 *    POST: {"username": "admin", "password": {"$ne": ""}}
 *    json_decode creates ['$ne' => ''] natively
 *
 * The JSON approach is more dangerous because:
 *   - It bypasses form-level input validation
 *   - It works even if PHP array syntax (bracket notation) is blocked
 *   - Complex nested operators can be injected naturally
 *   - APIs often accept JSON without additional sanitization
 *
 * FIX:
 * - Validate input types: if (is_array($password)) reject it
 * - Use JSON Schema validation for API inputs
 * - Cast all query values to expected types: (string)$password
 */
