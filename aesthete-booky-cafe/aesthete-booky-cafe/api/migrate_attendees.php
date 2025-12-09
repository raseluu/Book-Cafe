<?php
require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if cancelled_by exists
    $check = $conn->query("SHOW COLUMNS FROM registrations LIKE 'cancelled_by'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE registrations ADD COLUMN cancelled_by ENUM('user','admin') DEFAULT NULL";
        $conn->exec($sql);
        echo "Added cancelled_by column.\n";
    } else {
        echo "Column cancelled_by already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
