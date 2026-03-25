<?php
/*
 * SQLi-Arena. MongoDB Lab 3: Blind Extraction via $regex
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab3.json';
$collection = json_decode(file_get_contents($dataFile), true);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// VULNERABLE: user input used directly in query
$query = ['username' => $username, 'password' => $password];

function matches($field, $input) {
    if (is_array($input)) {
        if (array_key_exists('$ne', $input))    return $field !== $input['$ne'];
        if (array_key_exists('$gt', $input))    return $field > $input['$gt'];
        if (array_key_exists('$regex', $input)) {
            // $regex operator: allows pattern matching!
            return (bool)preg_match('/' . $input['$regex'] . '/', $field);
        }
        return false;
    }
    return $field === $input;
}

foreach ($collection['users'] as $user) {
    if (matches($user['username'], $username) &&
        matches($user['password'], $password)) {
        // NOTE: Does NOT reveal the password: blind only
        echo "Login successful! Welcome, " . $user['username'];
        break;
    }
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * password[$regex]=^FLAG  -->  $_POST['password'] = ['$regex' => '^FLAG']
 *
 * The regex ^FLAG matches any password starting with "FLAG".
 * By iterating: ^F, ^FL, ^FLA, ^FLAG, ^FLAG{, ^FLAG{m, ...
 * the attacker can reconstruct the entire password character by character.
 *
 * The "blind" aspect: the app only says success/fail, never shows the password.
 * But each regex test leaks one bit of information (match or no match).
 *
 * FIX: Cast to string AND sanitize regex special characters:
 *   $password = (string)$_POST['password'];
 */
