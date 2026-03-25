<?php
/*
 * SQLi-Arena. MongoDB Lab 8: BSON $type / $exists Enumeration
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab8.json';
$collection = json_decode(file_get_contents($dataFile), true);

$field = $_GET['field'] ?? 'username';
$value = $_GET['value'] ?? '';

// VULNERABLE: Value can be JSON with operators!
$parsed = json_decode($value, true);
if ($parsed !== null && is_array($parsed)) {
    $query_value = $parsed;  // Operators like $exists, $type pass through!
} else {
    $query_value = $value;
}

// Build query: {field: value}
$query = [$field => $query_value];

function matchField($doc, $field, $input) {
    $value = $doc[$field] ?? null;

    if (is_array($input)) {
        // $exists: does the field exist?
        if (array_key_exists('$exists', $input)) {
            $exists = (bool)$input['$exists'];
            return $exists ? array_key_exists($field, $doc) : !array_key_exists($field, $doc);
        }
        // $type: what BSON type is the field?
        if (array_key_exists('$type', $input)) {
            $actual = gettype($value);
            if ($input['$type'] === 'string')  return is_string($value);
            if ($input['$type'] === 'number')  return is_numeric($value);
            if ($input['$type'] === 'int')     return is_int($value);
            return false;
        }
        // $ne
        if (array_key_exists('$ne', $input)) return $value !== $input['$ne'];
        // $regex
        if (array_key_exists('$regex', $input)) return preg_match('/'.$input['$regex'].'/', $value);
        return false;
    }

    return $value === $input;
}

foreach ($collection['users'] as $user) {
    if (matchField($user, $field, $query_value)) {
        // Display limited user info (no password shown)
        echo $user['username'] . " | " . $user['email'] . " | " . $user['role'];
    }
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * The value parameter accepts JSON, which can contain MongoDB operators:
 *
 * 1. Schema discovery with $exists:
 *    field=secret_note&value={"$exists": true}
 *    --> Finds users that have a "secret_note" field (admin only)
 *
 * 2. Type checking with $type:
 *    field=password&value={"$type": "string"}
 *    --> Confirms password is stored as a string (not hashed)
 *
 *    field=login_count&value={"$type": "number"}
 *    --> Discovers numeric fields for potential injection
 *
 * 3. Data extraction with $regex:
 *    field=password&value={"$regex": "^FLAG{mg_bs0n"}
 *    --> If match returns results, the prefix is correct
 *
 * Attack chain:
 *   $exists -> discover hidden fields
 *   $type   -> understand field types
 *   $regex  -> extract values character by character
 *
 * FIX:
 * - Never parse user input as JSON for query operators
 * - Whitelist allowed fields for searching
 * - Cast all values to expected types
 * - Use parameterized queries even for NoSQL
 */
