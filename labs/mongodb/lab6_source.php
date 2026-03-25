<?php
/*
 * SQLi-Arena. MongoDB Lab 6: $lookup Cross-Collection Access
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab6.json';
$data = json_decode(file_get_contents($dataFile), true);

// Collections: employees, salaries, secrets

$department = $_GET['department'] ?? '';
$join_from = $_GET['join_from'] ?? 'salaries';     // VULNERABLE: user-controlled!
$join_local = $_GET['join_local'] ?? '_id';          // VULNERABLE: user-controlled!
$join_foreign = $_GET['join_foreign'] ?? 'employee_id'; // VULNERABLE: user-controlled!

// Build aggregation pipeline
$pipeline = [];

if ($department) {
    $pipeline[] = ['$match' => ['department' => $department]];
}

// VULNERABLE: The $lookup "from" field is user-controlled
// The attacker can point it to any collection, including "secrets"
$pipeline[] = [
    '$lookup' => [
        'from' => $join_from,         // Should be hardcoded to 'salaries'!
        'localField' => $join_local,   // Should be hardcoded to '_id'!
        'foreignField' => $join_foreign, // Should be hardcoded to 'employee_id'!
        'as' => 'joined_data'
    ]
];

$results = run_pipeline($data['employees'], $pipeline, $data);

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * The $lookup stage's parameters (from, localField, foreignField) are all
 * controlled by user input via GET parameters.
 *
 * Normal use: join employees with salaries
 *   join_from=salaries&join_local=_id&join_foreign=employee_id
 *
 * Attack: join employees with secrets
 *   join_from=secrets&join_local=_id&join_foreign=_id
 *
 * This exposes the entire secrets collection, which contains the flag.
 * The $lookup is equivalent to a SQL JOIN: and the attacker controls
 * which table to join with.
 *
 * FIX:
 * - Hardcode the $lookup target collection (never accept from user input)
 * - Whitelist allowed collections for joining
 * - Implement collection-level access control
 * - Never expose aggregation pipeline parameters to users
 */
