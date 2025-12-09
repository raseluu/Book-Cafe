<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Attempting to add 'capacity' column...\n";
    $sql = "ALTER TABLE events ADD COLUMN capacity INT DEFAULT 50";
    $conn->exec($sql);
    echo "SUCCESS: Column 'capacity' added.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SKIPPED: Column already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
