<?php
// PostgreSQL Lab 2. UNION. Dollar-Quoting Bypass
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

// addslashes() escapes single quotes: but does it stop everything?
$input = addslashes($_GET['search']);

// VULNERABLE: addslashes does not protect against dollar-quoting
$query = "SELECT id, name, price FROM products WHERE name ILIKE '%$input%'";

$result = pg_query($conn, $query);

while ($row = pg_fetch_assoc($result)) {
    echo "ID: {$row['id']}<br>";
    echo "Name: {$row['name']}<br>";
    echo "Price: \${$row['price']}<br>";
}
