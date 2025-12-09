<?php
require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'is_banned'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE users ADD COLUMN is_banned ENUM('0','1') DEFAULT '0' AFTER is_verified";
        $conn->exec($sql);
        echo "Column 'is_banned' added successfully.\n";
    } else {
        echo "Column 'is_banned' already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
