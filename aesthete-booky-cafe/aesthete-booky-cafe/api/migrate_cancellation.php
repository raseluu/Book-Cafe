<?php
require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if status exists
    $check = $conn->query("SHOW COLUMNS FROM registrations LIKE 'status'");
    if ($check->rowCount() == 0) {
        // Enforce default confirmed
        $sql = "ALTER TABLE registrations 
                ADD COLUMN status ENUM('confirmed','pending_cancellation','cancelled') DEFAULT 'confirmed',
                ADD COLUMN cancellation_reason TEXT DEFAULT NULL";
        $conn->exec($sql);
        echo "Added status and cancellation_reason columns.\n";
    } else {
        echo "Columns already exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
