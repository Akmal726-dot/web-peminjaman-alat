<?php
require_once __DIR__ . '/../config/database.php';

class Peminjaman {
    private $conn;
    private $table_name = "peminjaman";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllPeminjaman() {
        $query = "SELECT p.*, p.created_at, u.nama as nama_peminjam, u.email, u.no_hp, a.nama_alat, a.id_alat, a.deskripsi
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  ORDER BY p.tanggal_peminjaman DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getPeminjamanByUser($user_id) {
        $query = "SELECT p.*, a.nama_alat, a.id_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.id_user = :id_user
                  ORDER BY p.tanggal_peminjaman DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function countPeminjamanAktif() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'disetujui'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function countPendingPeminjaman() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function countPeminjamanByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = :status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function approvePeminjaman($id_peminjaman, $id_petugas) {
        try {
            $this->conn->beginTransaction();

            // Get peminjaman details
            $peminjaman = $this->getPeminjamanById($id_peminjaman);
            if (!$peminjaman) {
                return false;
            }

            // Update peminjaman status (handle both 'pending' and 'menunggu')
            $query = "UPDATE " . $this->table_name . "
                      SET status = 'disetujui',
                          id_petugas = :id_petugas,
                          updated_at = NOW()
                      WHERE id_peminjaman = :id_peminjaman
                      AND (status = 'menunggu' OR status = 'pending')";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id_peminjaman' => $id_peminjaman,
                ':id_petugas' => $id_petugas
            ]);

            if ($result) {
                // Stock was already reserved when request was created, no need to decrease again
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error approving peminjaman: " . $e->getMessage());
            return false;
        }
    }

    public function rejectPeminjaman($id_peminjaman, $id_petugas, $alasan_penolakan = '') {
        try {
            $this->conn->beginTransaction();

            // Get peminjaman details to restore stock
            $peminjaman = $this->getPeminjamanById($id_peminjaman);
            if (!$peminjaman) {
                return false;
            }

            $query = "UPDATE " . $this->table_name . "
                      SET status = 'ditolak',
                          id_petugas = :id_petugas,
                          alasan_penolakan = :alasan_penolakan,
                          updated_at = NOW()
                      WHERE id_peminjaman = :id_peminjaman
                      AND (status = 'menunggu' OR status = 'pending')";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id_peminjaman' => $id_peminjaman,
                ':id_petugas' => $id_petugas,
                ':alasan_penolakan' => $alasan_penolakan
            ]);

            if ($result) {
                // Restore stock since peminjaman was rejected
                $alatModel = new Alat();
                $alatModel->restoreStok($peminjaman['id_alat'], $peminjaman['jumlah']);
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error rejecting peminjaman: " . $e->getMessage());
            return false;
        }
    }

    public function createPeminjaman($id_user, $id_alat, $tanggal_peminjaman, $tanggal_pengembalian, $jumlah, $keterangan = '') {
        try {
            $this->conn->beginTransaction();

            // Check if enough stock is available
            $alatModel = new Alat();
            $alat = $alatModel->getAlatById($id_alat);
            if (!$alat || $alat['jumlah_tersedia'] < $jumlah) {
                return ['success' => false, 'message' => 'Stok alat tidak mencukupi'];
            }

            $query = "INSERT INTO " . $this->table_name . "
                      (id_user, id_alat, tanggal_peminjaman, tanggal_pengembalian, jumlah, keterangan, status, created_at)
                      VALUES (:id_user, :id_alat, :tanggal_peminjaman, :tanggal_pengembalian, :jumlah, :keterangan, 'menunggu', NOW())";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id_user' => $id_user,
                ':id_alat' => $id_alat,
                ':tanggal_peminjaman' => $tanggal_peminjaman,
                ':tanggal_pengembalian' => $tanggal_pengembalian,
                ':jumlah' => $jumlah,
                ':keterangan' => $keterangan
            ]);

            if ($result) {
                // Reserve the items by decreasing stock immediately
                $alatModel->updateStok($id_alat, $jumlah);
                $this->conn->commit();
                return ['success' => true, 'message' => 'Peminjaman berhasil diajukan'];
            } else {
                $this->conn->rollBack();
                $errorInfo = $stmt->errorInfo();
                return ['success' => false, 'message' => 'Gagal mengajukan peminjaman: ' . $errorInfo[2]];
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getPeminjamanById($id) {
        $query = "SELECT p.*, u.nama as nama_peminjam, a.nama_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.id_peminjaman = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id_peminjaman, $status, $id_petugas = null) {
        $query = "UPDATE " . $this->table_name . " SET status = :status, updated_at = NOW()";
        $params = [':status' => $status, ':id_peminjaman' => $id_peminjaman];

        if ($id_petugas) {
            $query .= ", id_petugas = :id_petugas";
            $params[':id_petugas'] = $id_petugas;
        }

        $query .= " WHERE id_peminjaman = :id_peminjaman";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function returnAlat($id_peminjaman) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'dikembalikan', 
                      tanggal_dikembalikan = NOW(), 
                      updated_at = NOW() 
                  WHERE id_peminjaman = :id_peminjaman";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_peminjaman', $id_peminjaman);
        return $stmt->execute();
    }

    public function getFilteredPeminjaman($whereClause, $params) {
        $query = "SELECT p.*, u.nama as nama_peminjam, a.nama_alat, k.nama_kategori
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  $whereClause
                  ORDER BY p.tanggal_peminjaman DESC";
        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    public function getStatusStats($whereClause, $params) {
        $query = "SELECT status, COUNT(*) as jumlah
                  FROM " . $this->table_name . "
                  $whereClause
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    public function getPeminjamanAktif() {
        $query = "SELECT p.*, u.nama as nama_peminjam, u.username,
                         a.nama_alat, a.id_alat, a.deskripsi, a.id_kategori
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.status = 'disetujui'
                  ORDER BY p.tanggal_pengembalian ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getPengembalianTerbaru($limit = 10) {
        $query = "SELECT p.*, u.nama as nama_peminjam, u.username,
                         a.nama_alat, a.id_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.status = 'dikembalikan'
                  ORDER BY p.tanggal_dikembalikan DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // PERBAIKAN: Method ini diubah untuk tidak memanggil kolom denda jika tidak ada
    public function getTotalDendaHariIni() {
        // Cek apakah kolom denda ada di tabel
        $checkQuery = "SELECT column_name FROM information_schema.columns WHERE table_name = 'peminjaman' AND column_name = 'denda'";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->execute();

        $columnExists = $checkStmt->rowCount() > 0;

        if ($columnExists) {
            $query = "SELECT COALESCE(SUM(denda), 0) as total_denda FROM " . $this->table_name . " WHERE DATE(tanggal_dikembalikan) = CURDATE() AND status = 'dikembalikan'";
        } else {
            // Jika kolom denda tidak ada, hitung denda berdasarkan hari telat
            $query = "SELECT COALESCE(SUM(CASE WHEN DATEDIFF(tanggal_dikembalikan, tanggal_pengembalian) > 0 THEN DATEDIFF(tanggal_dikembalikan, tanggal_pengembalian) * 5000 ELSE 0 END), 0) as total_denda FROM " . $this->table_name . " WHERE DATE(tanggal_dikembalikan) = CURDATE() AND status = 'dikembalikan'";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_denda'];
    }

    public function processPengembalian($id_peminjaman, $kondisi_alat, $keterangan) {
        try {
            $this->conn->beginTransaction();

            // Get peminjaman details to get jumlah and id_alat
            $peminjaman = $this->getPeminjamanById($id_peminjaman);
            if (!$peminjaman) {
                return false;
            }

            // Hitung denda
            $denda = $this->hitungEstimasiDenda($id_peminjaman);

            // Cek apakah kolom denda ada
            $checkQuery = "SELECT column_name
                           FROM information_schema.columns
                           WHERE table_name = 'peminjaman'
                           AND column_name = 'denda'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $columnExists = $checkStmt->rowCount() > 0;

            if ($columnExists) {
                $query = "UPDATE " . $this->table_name . " SET status = 'dikembalikan', tanggal_dikembalikan = NOW(), kondisi_kembali = :kondisi_alat, keterangan_kembali = :keterangan, denda = :denda, updated_at = NOW() WHERE id_peminjaman = :id_peminjaman AND status = 'disetujui'";

                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([
                    ':id_peminjaman' => $id_peminjaman,
                    ':kondisi_alat' => $kondisi_alat,
                    ':keterangan' => $keterangan,
                    ':denda' => $denda
                ]);
            } else {
                // Jika kolom denda tidak ada, gunakan query tanpa kolom denda
                $query = "UPDATE " . $this->table_name . " SET status = 'dikembalikan', tanggal_dikembalikan = NOW(), kondisi_kembali = :kondisi_alat, keterangan_kembali = :keterangan, updated_at = NOW() WHERE id_peminjaman = :id_peminjaman AND status = 'disetujui'";

                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([
                    ':id_peminjaman' => $id_peminjaman,
                    ':kondisi_alat' => $kondisi_alat,
                    ':keterangan' => $keterangan
                ]);
            }

            if ($result) {
                // Update status alat berdasarkan kondisi
                $status_alat = 'tersedia';
                if ($kondisi_alat == 'rusak_ringan' || $kondisi_alat == 'rusak_berat') {
                    $status_alat = 'rusak';
                } elseif ($kondisi_alat == 'hilang') {
                    $status_alat = 'hilang';
                }

                $updateAlatQuery = "UPDATE alat
                                    SET status = :status, jumlah_tersedia = jumlah_tersedia + :jumlah, updated_at = NOW()
                                    WHERE id_alat = :id_alat";
                $updateAlatStmt = $this->conn->prepare($updateAlatQuery);
                $updateAlatStmt->execute([
                    ':status' => $status_alat,
                    ':jumlah' => $peminjaman['jumlah'],
                    ':id_alat' => $peminjaman['id_alat']
                ]);

                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error processing pengembalian: " . $e->getMessage());
            return false;
        }
    }

    // Method untuk menghitung estimasi denda
    public function hitungEstimasiDenda($id_peminjaman) {
        $query = "SELECT tanggal_pengembalian
                  FROM " . $this->table_name . " 
                  WHERE id_peminjaman = :id_peminjaman 
                  AND status = 'disetujui'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_peminjaman', $id_peminjaman);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['tanggal_pengembalian']) {
            $tenggat = new DateTime($result['tanggal_pengembalian']);
            $hari_ini = new DateTime();
            
            if ($hari_ini > $tenggat) {
                $telat_hari = $hari_ini->diff($tenggat)->days;
                // Denda Rp 5.000 per hari telat
                return $telat_hari * 5000;
            }
        }
        
        return 0;
    }

    // Method untuk mendapatkan detail lengkap peminjaman
    public function getDetailPeminjaman($id_peminjaman) {
        $query = "SELECT p.*, u.nama as nama_peminjam, u.username, u.email, u.no_hp,
                         a.nama_alat, a.kode_alat, a.spesifikasi, a.deskripsi,
                         k.nama_kategori
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  WHERE p.id_peminjaman = :id_peminjaman
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_peminjaman', $id_peminjaman);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

