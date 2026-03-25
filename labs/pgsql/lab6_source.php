<?php
// PostgreSQL Lab 6. Stacked Queries. Multi-Statement
// Vulnerable Source Code

require_once __DIR__ . '/../../includes/db.php';

$input = $_GET['search'];

// VULNERABLE: User input directly concatenated, pg_query allows stacked queries
$query = "SELECT id, title, content FROM notes WHERE title ILIKE '%$input%'";

$result = pg_query($conn, $query);

while ($row = pg_fetch_assoc($result)) {
    echo "ID: {$row['id']}<br>";
    echo "Title: {$row['title']}<br>";
    echo "Content: {$row['content']}<br><br>";
}
