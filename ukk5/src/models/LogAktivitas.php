<?php
require_once __DIR__ . '/../config/database.php';

class LogAktivitas {
    private $conn;
    private $table_name = "log_aktifitas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function logActivity($user_id, $activity) {
        $query = "INSERT INTO " . $this->table_name . " (id_user, aktifitas, created_at) VALUES (:id_user, :aktifitas, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->bindParam(':aktifitas', $activity);
        return $stmt->execute();
    }

    public function getRecentActivities($limit = 10) {
        $query = "SELECT la.*, u.nama as nama_user, u.role
                  FROM " . $this->table_name . " la
                  LEFT JOIN users u ON la.id_user = u.id_user
                  ORDER BY la.id_log DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getAllActivities($limit = null, $offset = 0) {
        $query = "SELECT la.*, u.nama as nama_user, u.role
                  FROM " . $this->table_name . " la
                  LEFT JOIN users u ON la.id_user = u.id_user
                  ORDER BY la.id_log DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);

        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt;
    }

    public function getActivitiesByUser($user_id, $limit = null) {
        $query = "SELECT la.*, u.nama as nama_user, u.role
                  FROM " . $this->table_name . " la
                  LEFT JOIN users u ON la.id_user = u.id_user
                  WHERE la.id_user = :user_id
                  ORDER BY la.id_log DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt;
    }

    public function getActivitiesByRole($role, $limit = null) {
        $query = "SELECT la.*, u.nama as nama_user, u.role
                  FROM " . $this->table_name . " la
                  LEFT JOIN users u ON la.id_user = u.id_user
                  WHERE u.role = :role
                  ORDER BY la.id_log DESC";

        if ($limit !== null) {
            $query .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role);

        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt;
    }

    public function countActivities() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function deleteOldActivities($days = 90) {
        $query = "DELETE FROM " . $this->table_name . " WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
