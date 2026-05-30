<?php
require_once __DIR__ . '/../config/database.php';

class Kategori {
    private $conn;
    private $table_name = "kategori";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllKategori() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nama_kategori";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getKategoriById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_kategori = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function tambahKategori($nama_kategori, $keterangan = '') {
        try {
            $query = "INSERT INTO " . $this->table_name . " (nama_kategori, keterangan) VALUES (:nama_kategori, :keterangan)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_kategori', $nama_kategori);
            $stmt->bindParam(':keterangan', $keterangan);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Kategori berhasil ditambahkan'];
            }
            return ['success' => false, 'message' => 'Gagal menambah kategori'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateKategori($id_kategori, $nama_kategori, $keterangan = '') {
        try {
            $query = "UPDATE " . $this->table_name . " SET nama_kategori = :nama_kategori, keterangan = :keterangan WHERE id_kategori = :id_kategori";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_kategori', $nama_kategori);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':id_kategori', $id_kategori);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Kategori berhasil diperbarui'];
            }
            return ['success' => false, 'message' => 'Gagal memperbarui kategori'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function hapusKategori($id_kategori) {
        try {
            // Cek apakah kategori digunakan oleh alat
            $query_check = "SELECT COUNT(*) as total FROM alat WHERE id_kategori = :id_kategori";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->bindParam(':id_kategori', $id_kategori);
            $stmt_check->execute();
            $row = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($row['total'] > 0) {
                return ['success' => false, 'message' => 'Tidak dapat menghapus kategori yang masih digunakan oleh alat'];
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id_kategori = :id_kategori";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_kategori', $id_kategori);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Kategori berhasil dihapus'];
            }
            return ['success' => false, 'message' => 'Gagal menghapus kategori'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getKategoriWithCount() {
        $query = "SELECT k.id_kategori, k.nama_kategori, COUNT(a.id_alat) as jumlah_alat
                  FROM " . $this->table_name . " k
                  LEFT JOIN alat a ON k.id_kategori = a.id_kategori
                  GROUP BY k.id_kategori, k.nama_kategori
                  ORDER BY k.nama_kategori";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalAlat() {
        $query = "SELECT COUNT(*) as total FROM alat";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
