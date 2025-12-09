<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Attempting to add 'guests' column to registrations...\n";
    $sql = "ALTER TABLE registrations ADD COLUMN guests INT DEFAULT 1";
    $conn->exec($sql);
    echo "SUCCESS: Column 'guests' added.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
