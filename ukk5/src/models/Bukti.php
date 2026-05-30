<?php
require_once __DIR__ . '/../config/database.php';

class Bukti {
    private $conn;
    private $table_name = "bukti";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function countAllBukti() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getRecentBukti($limit = 10) {
        $query = "SELECT b.*, u.nama as nama_peminjam
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.id_user = u.id_user
                  ORDER BY b.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getAllBukti() {
        $query = "SELECT b.*, u.nama as nama_peminjam
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.id_user = u.id_user
                  ORDER BY b.id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getBuktiById($id) {
        $query = "SELECT b.*, u.nama as nama_peminjam
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.id_user = u.id_user
                  WHERE b.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createBukti($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  (id_user, id_peminjaman, nama_file, keterangan, tipe_bukti, created_at)
                  VALUES (:id_user, :id_peminjaman, :nama_file, :keterangan, :tipe_bukti, NOW())";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':id_user' => $data['id_user'],
            ':id_peminjaman' => $data['id_peminjaman'] ?? null,
            ':nama_file' => $data['nama_file'],
            ':keterangan' => $data['keterangan'] ?? '',
            ':tipe_bukti' => $data['tipe_bukti'] ?? 'pengembalian'
        ]);
    }

    public function updateBukti($id, $data) {
        $query = "UPDATE " . $this->table_name . "
                  SET id_peminjaman = :id_peminjaman,
                      nama_file = :nama_file,
                      keterangan = :keterangan,
                      tipe_bukti = :tipe_bukti
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':id' => $id,
            ':id_peminjaman' => $data['id_peminjaman'] ?? null,
            ':nama_file' => $data['nama_file'],
            ':keterangan' => $data['keterangan'] ?? '',
            ':tipe_bukti' => $data['tipe_bukti'] ?? 'pengembalian'
        ]);
    }

    public function deleteBukti($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getBuktiByUser($user_id) {
        $query = "SELECT b.*, u.nama as nama_peminjam
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.id_user = u.id_user
                  WHERE b.id_user = :id_user
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function getBuktiByTipe($tipe_bukti) {
        $query = "SELECT b.*, u.nama as nama_peminjam
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.id_user = u.id_user
                  WHERE b.tipe_bukti = :tipe_bukti
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipe_bukti', $tipe_bukti);
        $stmt->execute();
        return $stmt;
    }
}
?>
