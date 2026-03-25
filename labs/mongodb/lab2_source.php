<?php
/*
 * SQLi-Arena. MongoDB Lab 2: Auth Bypass via $gt Operator
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab2.json';
$collection = json_decode(file_get_contents($dataFile), true);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// VULNERABLE: user input directly used in query
$query = ['username' => $username, 'password' => $password];

function matches($field, $input) {
    if (is_array($input)) {
        // MongoDB comparison operators: all processed!
        if (array_key_exists('$ne', $input))  return $field !== $input['$ne'];
        if (array_key_exists('$gt', $input))  return $field > $input['$gt'];
        if (array_key_exists('$gte', $input)) return $field >= $input['$gte'];
        if (array_key_exists('$lt', $input))  return $field < $input['$lt'];
        if (array_key_exists('$lte', $input)) return $field <= $input['$lte'];
        return false;
    }
    return $field === $input;
}

// Simulated db.users.findOne(query)
foreach ($collection['users'] as $user) {
    if (matches($user['username'], $username) &&
        matches($user['password'], $password)) {
        echo "Authenticated as: " . $user['username'];
        break;
    }
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * PHP converts: password[$gt]= --> $_POST['password'] = ['$gt' => '']
 *
 * The query becomes: {username: "admin", password: {$gt: ""}}
 * Since every non-empty string is "greater than" an empty string,
 * this matches the admin user regardless of their actual password.
 *
 * Other operators that work similarly:
 *   $gte (greater than or equal)
 *   $lt  (less than): with a very high value
 *   $lte (less than or equal): with a very high value
 *
 * FIX: Cast inputs to string: $password = (string)$_POST['password'];
 */
