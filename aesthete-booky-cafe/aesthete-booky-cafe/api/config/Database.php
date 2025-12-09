<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'aesthete_book_cafe';
    private $username = 'rasel';
    private $password = 'rasel123';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Echoing error for debugging, but in prod should be logged
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
