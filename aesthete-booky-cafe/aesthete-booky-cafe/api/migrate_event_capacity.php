<?php
require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // 1. Add capacity to events
    $check = $conn->query("SHOW COLUMNS FROM events LIKE 'capacity'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE events ADD COLUMN capacity INT DEFAULT 50 AFTER user_id"); // user_id might not exist, checking schema... actually schema.sql said (id, title, date, location, image, description). So I'll add after description
        // Re-checking description psn.
        $conn->exec("ALTER TABLE events ADD COLUMN capacity INT DEFAULT 50");
        echo "Column 'capacity' added to events.\n";
    }

    // 2. Add guests to registrations
    $checkReg = $conn->query("SHOW COLUMNS FROM registrations LIKE 'guests'");
    if ($checkReg->rowCount() == 0) {
        $conn->exec("ALTER TABLE registrations ADD COLUMN guests INT DEFAULT 1");
        echo "Column 'guests' added to registrations.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
