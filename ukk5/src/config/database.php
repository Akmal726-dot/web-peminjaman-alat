<?php
class Database {
    private $host = "localhost";
    private $db_name = "ukk2";
    private $username = "postgres";
    private $password = "akmal12345";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Untuk PostgreSQL
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'UTF8'");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>