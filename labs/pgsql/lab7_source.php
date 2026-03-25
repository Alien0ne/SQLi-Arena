<?php
// PostgreSQL Lab 7. File Read: pg_read_file / COPY
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$input = $_GET['search'];

// VULNERABLE: User input directly concatenated into query
$query = "SELECT id, name, description FROM products WHERE name ILIKE '%$input%'";

$result = pg_query($conn, $query);

while ($row = pg_fetch_assoc($result)) {
    echo "ID: {$row['id']}<br>";
    echo "Name: {$row['name']}<br>";
    echo "Description: {$row['description']}<br><br>";
}
