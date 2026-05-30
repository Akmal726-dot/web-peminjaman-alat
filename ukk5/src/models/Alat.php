<?php
require_once __DIR__ . '/../config/database.php';

class Alat {
    private $conn;
    private $table_name = "alat";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllAlat() {
        $query = "SELECT a.*, k.nama_kategori 
                  FROM " . $this->table_name . " a
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  ORDER BY a.nama_alat";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getAvailableAlat() {
        $query = "SELECT a.*, k.nama_kategori 
                  FROM " . $this->table_name . " a
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  WHERE a.jumlah_tersedia > 0
                  ORDER BY a.nama_alat";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function createAlat($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  (nama_alat, id_kategori, jumlah_total, jumlah_tersedia, deskripsi, kondisi)
                  VALUES (:nama_alat, :id_kategori, :jumlah_total, :jumlah_total, :deskripsi, :kondisi)";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':nama_alat' => $data['nama_alat'],
            ':id_kategori' => $data['id_kategori'],
            ':jumlah_total' => $data['jumlah_total'],
            ':deskripsi' => $data['deskripsi'],
            ':kondisi' => $data['kondisi'] ?? 'baik'
        ]);
    }

    // Method TAMBAH ALAT baru dengan return array
    public function tambahAlat($nama_alat, $id_kategori, $jumlah_total, $deskripsi = '', $kondisi = 'baik') {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                      (nama_alat, id_kategori, jumlah_total, jumlah_tersedia, deskripsi, kondisi)
                      VALUES (:nama_alat, :id_kategori, :jumlah_total, :jumlah_total, :deskripsi, :kondisi)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_alat', $nama_alat);
            $stmt->bindParam(':id_kategori', $id_kategori);
            $stmt->bindParam(':jumlah_total', $jumlah_total);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':kondisi', $kondisi);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Alat berhasil ditambahkan'];
            }
            return ['success' => false, 'message' => 'Gagal menambah alat'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Method UPDATE ALAT
    public function updateAlat($id_alat, $nama_alat, $id_kategori, $jumlah_total, $deskripsi = '') {
        try {
            // Get current data
            $current = $this->getAlatById($id_alat);
            
            if (!$current) {
                return ['success' => false, 'message' => 'Alat tidak ditemukan'];
            }
            
            // Calculate new jumlah_tersedia
            $dipinjam = $current['jumlah_total'] - $current['jumlah_tersedia'];
            $new_jumlah_tersedia = max(0, $jumlah_total - $dipinjam);
            
            $query = "UPDATE " . $this->table_name . " 
                      SET nama_alat = :nama_alat, 
                          id_kategori = :id_kategori, 
                          jumlah_total = :jumlah_total,
                          jumlah_tersedia = :jumlah_tersedia,
                          deskripsi = :deskripsi
                      WHERE id_alat = :id_alat";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_alat', $nama_alat);
            $stmt->bindParam(':id_kategori', $id_kategori);
            $stmt->bindParam(':jumlah_total', $jumlah_total);
            $stmt->bindParam(':jumlah_tersedia', $new_jumlah_tersedia);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':id_alat', $id_alat);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Alat berhasil diperbarui'];
            }
            return ['success' => false, 'message' => 'Gagal memperbarui alat'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Method HAPUS ALAT
    public function hapusAlat($id_alat) {
        try {
            // Cek apakah alat ada
            $alat = $this->getAlatById($id_alat);
            
            if (!$alat) {
                return ['success' => false, 'message' => 'Alat tidak ditemukan'];
            }
            
            // Cek apakah alat sedang dipinjam
            $dipinjam = $alat['jumlah_total'] - $alat['jumlah_tersedia'];
            
            if ($dipinjam > 0) {
                return ['success' => false, 'message' => 'Tidak dapat menghapus alat yang sedang dipinjam'];
            }
            
            $query = "DELETE FROM " . $this->table_name . " WHERE id_alat = :id_alat";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_alat', $id_alat);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Alat berhasil dihapus'];
            }
            return ['success' => false, 'message' => 'Gagal menghapus alat'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function countAlat() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getAlatById($id) {
        $query = "SELECT a.*, k.nama_kategori FROM " . $this->table_name . " a
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  WHERE a.id_alat = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStok($id_alat, $jumlah) {
        $query = "UPDATE " . $this->table_name . "
                  SET jumlah_tersedia = jumlah_tersedia - :jumlah
                  WHERE id_alat = :id_alat";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_alat', $id_alat);
        $stmt->bindParam(':jumlah', $jumlah);
        return $stmt->execute();
    }

    public function restoreStok($id_alat, $jumlah) {
        $query = "UPDATE " . $this->table_name . "
                  SET jumlah_tersedia = jumlah_tersedia + :jumlah
                  WHERE id_alat = :id_alat";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_alat', $id_alat);
        $stmt->bindParam(':jumlah', $jumlah);
        return $stmt->execute();
    }

    public function getKategori() {
        $query = "SELECT * FROM kategori ORDER BY nama_kategori";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>