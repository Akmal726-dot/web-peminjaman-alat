<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username AND password = :password LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        
        if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Log aktivitas login
                $this->logActivity($user['id_user'], 'Login ke sistem');
                return $user;
            }
        }
        return false;
    }

    public function register($nama, $username, $email, $no_hp, $password, $role) {
        // Validasi role
        $valid_roles = ['admin', 'petugas', 'peminjam'];
        if (!in_array($role, $valid_roles)) {
            return ['success' => false, 'message' => 'Role tidak valid'];
        }

        // Cek apakah username sudah ada
        if ($this->isUsernameExists($username)) {
            return ['success' => false, 'message' => 'Username sudah terdaftar'];
        }

        // HAPUS BATASAN HANYA SATU ADMIN
        // if ($role === 'admin' && $this->countAdmins() > 0) {
        //     return ['success' => false, 'message' => 'Hanya boleh ada satu admin'];
        // }

        $query = "INSERT INTO " . $this->table_name . " (nama, username, email, no_hp, password, role)
                  VALUES (:nama, :username, :email, :no_hp, :password, :role)
                  RETURNING id_user, nama, username, email, no_hp, role";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':no_hp', $no_hp);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            $newUser = $stmt->fetch(PDO::FETCH_ASSOC);

            // Log aktivitas registrasi
            $this->logActivity($newUser['id_user'], 'Registrasi akun baru sebagai ' . $role);

            return [
                'success' => true,
                'message' => 'Registrasi berhasil sebagai ' . $role,
                'user' => $newUser
            ];
        }

        return ['success' => false, 'message' => 'Registrasi gagal'];
    }

    // Method baru: Cek apakah sudah ada admin
    public function hasAdmin() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    private function isUsernameExists($username) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    private function countAdmins() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    private function logActivity($user_id, $activity) {
        $query = "INSERT INTO log_aktifitas (id_user, aktifitas, created_at) VALUES (:id_user, :aktifitas, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->bindParam(':aktifitas', $activity);
        $stmt->execute();
    }

    public function getAllUsers() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY role, nama";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getUsersByRole($role) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE role = :role ORDER BY nama";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt;
    }

    public function countUsers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_user = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRole($user_id, $new_role) {
        $valid_roles = ['admin', 'petugas', 'peminjam'];
        if (!in_array($new_role, $valid_roles)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " SET role = :role WHERE id_user = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $new_role);
        $stmt->bindParam(':id', $user_id);

        return $stmt->execute();
    }

    public function getRecentActivities($limit = 10) {
        $query = "SELECT la.*, u.nama as nama_user, u.role
                  FROM log_aktifitas la
                  LEFT JOIN users u ON la.id_user = u.id_user
                  ORDER BY la.id_log DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
}
?>
