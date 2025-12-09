<?php
require_once 'config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if columns exist
    $stmt = $db->prepare("SHOW COLUMNS FROM registrations LIKE 'contact_name'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE registrations 
                ADD COLUMN contact_name VARCHAR(255) NULL AFTER guests,
                ADD COLUMN contact_email VARCHAR(255) NULL AFTER contact_name,
                ADD COLUMN contact_phone VARCHAR(50) NULL AFTER contact_email";
        $db->exec($sql);
        echo "Columns contact_name, contact_email, contact_phone added successfully.\n";
    } else {
        echo "Columns already exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
