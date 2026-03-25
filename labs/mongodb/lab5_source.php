<?php
/*
 * SQLi-Arena. MongoDB Lab 5: Aggregation Pipeline Injection
 * SOURCE CODE (shown in White-Box mode)
 */

$dataFile = __DIR__ . '/../../data/mongodb/lab5.json';
$data = json_decode(file_get_contents($dataFile), true);

// Data store has two collections:
// - products: public product catalog
// - secret_config: sensitive configuration (includes flag)

$category = $_GET['category'] ?? '';
$pipeline_json = $_GET['pipeline'] ?? '';

if (!empty($pipeline_json)) {
    // VULNERABLE: Raw pipeline JSON accepted from user input!
    $pipeline = json_decode($pipeline_json, true);

    // Run the user-supplied pipeline against products collection
    $results = run_pipeline($data['products'], $pipeline, $data);
    // Note: $data contains ALL collections, including secret_config!
} else {
    // Build pipeline from form fields
    $pipeline = [];
    if ($category) {
        $pipeline[] = ['$match' => ['category' => $category]];
    }
    $pipeline[] = ['$sort' => ['price' => 1]];

    $results = run_pipeline($data['products'], $pipeline, $data);
}

/*
 * WHY THIS IS VULNERABLE:
 * -----------------------
 * The application accepts a raw aggregation pipeline as JSON from the user.
 * This allows injecting ANY pipeline stage, including:
 *
 * 1. $lookup: join with other collections:
 *    {"$lookup": {"from": "secret_config", "localField": "", "foreignField": "", "as": "leaked"}}
 *    This performs a cross-collection join, exposing the secret_config data.
 *
 * 2. $group: aggregate data across documents
 * 3. $project: select/compute arbitrary fields
 *
 * The key issue: the $lookup stage's "from" field references any collection
 * in the database. Even if the user has no direct access to secret_config,
 * they can join it into their query results.
 *
 * FIX:
 * - Never accept raw pipeline JSON from users
 * - Whitelist allowed pipeline stages
 * - Validate the "from" field in $lookup against allowed collections
 * - Use role-based access control at the database level
 */
