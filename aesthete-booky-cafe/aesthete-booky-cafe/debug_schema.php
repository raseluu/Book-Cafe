<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->query("SHOW COLUMNS FROM events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = "Columns in 'events' table:\n";
    foreach ($columns as $col) {
        $output .= $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    file_put_contents('schema_debug.txt', $output);
    echo "Schema dumped to schema_debug.txt";

} catch (PDOException $e) {
    file_put_contents('schema_debug.txt', "Error: " . $e->getMessage());
}
