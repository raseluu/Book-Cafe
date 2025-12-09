<?php
require_once __DIR__ . '/config/Database.php';

$db = new Database();
// Hack: Connect without DB first to create it if not exists?
// PDO usually needs DB name.
// We'll try to connect to just host.
try {
    $dsn = "mysql:host=localhost";
    $conn = new PDO($dsn, 'rasel', 'rasel123');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("CREATE DATABASE IF NOT EXISTS aesthete_book_cafe");
    echo "Database created or exists.\n";
} catch (PDOException $e) {
    die("DB Creation Failed: " . $e->getMessage());
}

// Now connect properly
$conn = $db->getConnection();

// Run Schema
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
try {
    $conn->exec($schema);
    echo "Schema executed.\n";
} catch (PDOException $e) {
    echo "Schema Error: " . $e->getMessage() . "\n";
}

// Run Seed Content (Books, etc)
$seed = file_get_contents(__DIR__ . '/../database/seed.sql');
try {
    $conn->exec($seed);
    echo "Seed data executed.\n";
} catch (PDOException $e) {
    echo "Seed Error (might be duplicates): " . $e->getMessage() . "\n";
}

// Insert Admin
$pass = password_hash('rasel123', PASSWORD_DEFAULT);
$check = $conn->prepare("SELECT id FROM users WHERE email = 'admin@aesthete.com'");
$check->execute();
if ($check->rowCount() == 0) {
    $sql = "INSERT INTO users (name, email, password, role, is_verified) VALUES ('Admin Rasel', 'admin@aesthete.com', ?, 'admin', 1)";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$pass])) {
        echo "Admin user created.\n";
    } else {
        echo "Admin creation failed.\n";
    }
} else {
    echo "Admin already exists.\n";
}

echo "Setup Complete.";
