<?php
require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if column exists first to avoid error
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email";
        $conn->exec($sql);
        echo "Column 'phone' added successfully.\n";
    } else {
        echo "Column 'phone' already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
