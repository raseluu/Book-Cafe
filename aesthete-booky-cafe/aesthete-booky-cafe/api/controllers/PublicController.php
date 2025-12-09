<?php
require_once __DIR__ . '/../config/Database.php';

class PublicController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getBooks() {
        $query = "SELECT * FROM books ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMenu() {
        $query = "SELECT * FROM menu_items ORDER BY category, name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEvents($id = null) {
        try {
            if ($id) {
                // Single event with booking count
                $stmt = $this->conn->prepare("
                    SELECT events.*, 
                    (SELECT COALESCE(SUM(guests), 0) FROM registrations WHERE event_id = events.id AND status IN ('confirmed', 'pending_cancellation')) as booked
                    FROM events WHERE id = ?
                ");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // All events with booking count
                $stmt = $this->conn->prepare("
                    SELECT events.*, 
                    (SELECT COALESCE(SUM(guests), 0) FROM registrations WHERE event_id = events.id AND status IN ('confirmed', 'pending_cancellation')) as booked
                    FROM events ORDER BY created_at DESC
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/../../db_debug.txt', "GetEvents Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return []; // Return empty array on error to prevent 500
        }
    }
}
