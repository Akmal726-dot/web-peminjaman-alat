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
        $query = "SELECT p.*, p.created_at, u.nama as nama_peminjam, u.email, u.no_hp, a.nama_alat, a.id_alat, a.deskripsi, a.kondisi
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  ORDER BY p.id_peminjaman ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getPeminjamanByUser($user_id) {
        $query = "SELECT p.id_peminjaman, p.id_user, p.id_alat, p.tanggal_peminjaman, p.tanggal_pengembalian, p.jumlah, p.keterangan, p.status, p.created_at, p.updated_at, a.nama_alat, a.id_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.id_user = :id_user
                  ORDER BY p.tanggal_peminjaman DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        return $stmt;
    }

    public function getPeminjamanByUserAndStatus($user_id, $status) {
        $query = "SELECT p.*, a.nama_alat, a.id_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.id_user = :id_user AND p.status = :status
                  ORDER BY p.tanggal_peminjaman DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->bindParam(':status', $status);
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

            // Update peminjaman status
            $query = "UPDATE " . $this->table_name . "
                      SET status = 'disetujui',
                          id_petugas = :id_petugas,
                          updated_at = NOW()
                      WHERE id_peminjaman = :id_peminjaman";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id_peminjaman' => $id_peminjaman,
                ':id_petugas' => $id_petugas
            ]);

            if ($result && $stmt->rowCount() > 0) {
                // Log activity
                $this->logActivity($id_petugas, "Menyetujui peminjaman #{$id_peminjaman} ({$peminjaman['nama_alat']}) oleh {$peminjaman['nama_peminjam']}");

                // Create bukti record for peminjaman
                require_once __DIR__ . '/Bukti.php';
                $buktiModel = new Bukti();
                $buktiData = [
                    'id_user' => $peminjaman['id_user'],
                    'id_peminjaman' => $id_peminjaman,
                    'nama_file' => 'struk_peminjaman_' . $id_peminjaman . '.pdf',
                    'keterangan' => 'Bukti peminjaman alat ' . $peminjaman['nama_alat'],
                    'tipe_bukti' => 'peminjaman'
                ];
                $buktiModel->createBukti($buktiData);

                // Now reduce stock since approval is granted
                $alatModel = new Alat();
                $alatModel->updateStok($peminjaman['id_alat'], $peminjaman['jumlah']);

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

        if ($result && $stmt->rowCount() > 0) {
            // Log activity
            $this->logActivity($id_petugas, "Menolak peminjaman #{$id_peminjaman} ({$peminjaman['nama_alat']}) oleh {$peminjaman['nama_peminjam']}");

            // Note: Stock is not restored for rejected pending loans since it was never decreased
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
                      VALUES (:id_user, :id_alat, :tanggal_peminjaman, :tanggal_pengembalian, :jumlah, :keterangan, 'pending', NOW())";

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
                // Do NOT reduce stock here - wait for approval
                $this->conn->commit();
                return ['success' => true, 'message' => 'Peminjaman berhasil diajukan dan menunggu persetujuan petugas'];
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

    public function createPeminjamanInstant($id_user, $id_alat, $tanggal_peminjaman, $tanggal_pengembalian, $jumlah, $keterangan = '') {
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
                      VALUES (:id_user, :id_alat, :tanggal_peminjaman, :tanggal_pengembalian, :jumlah, :keterangan, 'disetujui', NOW())";

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
                // Decrease stock immediately for instant approval
                $alatModel->updateStok($id_alat, $jumlah);
                $this->conn->commit();
                return ['success' => true, 'message' => 'Peminjaman berhasil diajukan dan langsung disetujui'];
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

    public function returnAlat($param) {
        // Handle both old single parameter and new array parameter
        if (is_array($param)) {
            $id_peminjaman = $param['id_peminjaman'];
            $kondisi_alat = $param['kondisi_alat'] ?? 'baik';
            $keterangan = $param['keterangan'] ?? '';
            $petugas_id = $param['id_user'] ?? null;

            // Use processPengembalian for proper stock update
            return $this->processPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $petugas_id);
        } else {
            // Legacy single parameter - only update status, no stock change
            $query = "UPDATE " . $this->table_name . "
                      SET status = 'dikembalikan',
                          tanggal_dikembalikan = NOW(),
                          updated_at = NOW()
                      WHERE id_peminjaman = :id_peminjaman";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_peminjaman', $param);
            return $stmt->execute();
        }
    }

    public function getFilteredPeminjaman($whereClause, $params) {
        $query = "SELECT p.*, u.nama as nama_peminjam, a.nama_alat, a.kondisi, k.nama_kategori
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
        $query = "SELECT p.status, COUNT(*) as jumlah
                  FROM " . $this->table_name . " p
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  $whereClause
                  GROUP BY p.status";
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
                         a.nama_alat, a.id_alat, petugas.nama as nama_petugas
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  LEFT JOIN users petugas ON p.id_petugas = petugas.id_user
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
            $query = "SELECT COALESCE(SUM(denda), 0) as total_denda FROM " . $this->table_name . " WHERE tanggal_dikembalikan::date = CURRENT_DATE AND status = 'dikembalikan'";
        } else {
            // Jika kolom denda tidak ada, hitung denda berdasarkan hari telat
            $query = "SELECT COALESCE(SUM(CASE WHEN (tanggal_dikembalikan::date - tanggal_pengembalian::date) > 0 THEN (tanggal_dikembalikan::date - tanggal_pengembalian::date) * 5000 ELSE 0 END), 0) as total_denda FROM " . $this->table_name . " WHERE tanggal_dikembalikan::date = CURRENT_DATE AND status = 'dikembalikan'";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_denda'];
    }

    public function processPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $petugas_id = null) {
        try {
            $this->conn->beginTransaction();

            // Get peminjaman details to get jumlah and id_alat
            $peminjaman = $this->getPeminjamanById($id_peminjaman);
            if (!$peminjaman) {
                error_log("Peminjaman not found: " . $id_peminjaman);
                $this->conn->rollBack();
                return false;
            }

            error_log("Processing return for peminjaman ID: " . $id_peminjaman . ", current status: " . $peminjaman['status']);

            // Check if peminjaman is in waiting confirmation status
            if ($peminjaman['status'] !== 'menunggu_konfirmasi') {
                error_log("Peminjaman status is not 'menunggu_konfirmasi': " . $peminjaman['status']);
                $this->conn->rollBack();
                return false;
            }

            // Update peminjaman status - simplified query
            $query = "UPDATE " . $this->table_name . " SET
                      status = 'dikembalikan',
                      tanggal_dikembalikan = NOW(),
                      kondisi_kembali = :kondisi_alat,
                      keterangan_kembali = :keterangan,
                      id_petugas = :id_petugas,
                      updated_at = NOW()
                      WHERE id_peminjaman = :id_peminjaman AND status = 'menunggu_konfirmasi'";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id_peminjaman' => $id_peminjaman,
                ':kondisi_alat' => $kondisi_alat,
                ':keterangan' => $keterangan,
                ':id_petugas' => $petugas_id
            ]);

            if (!$result) {
                error_log("Failed to execute peminjaman update query");
                $this->conn->rollBack();
                return false;
            }

            $rowsAffected = $stmt->rowCount();
            error_log("Rows affected by peminjaman update: " . $rowsAffected);

            if ($rowsAffected == 0) {
                error_log("No rows updated - peminjaman may not be in 'disetujui' status or already processed");
                $this->conn->rollBack();
                return false;
            }

            // Log activity using the petugas_id parameter
            $this->logActivity($petugas_id ?? 1, "Memproses pengembalian #{$id_peminjaman} ({$peminjaman['nama_alat']}) oleh {$peminjaman['nama_peminjam']} - Kondisi: {$kondisi_alat}");

            // Update status alat berdasarkan kondisi - only update jumlah_tersedia since status column doesn't exist
            $updateAlatQuery = "UPDATE alat
                                SET jumlah_tersedia = jumlah_tersedia + :jumlah, updated_at = NOW()
                                WHERE id_alat = :id_alat";
            $updateAlatStmt = $this->conn->prepare($updateAlatQuery);
            $alatResult = $updateAlatStmt->execute([
                ':jumlah' => $peminjaman['jumlah'],
                ':id_alat' => $peminjaman['id_alat']
            ]);

            if (!$alatResult) {
                error_log("Failed to update alat stock for id_alat: " . $peminjaman['id_alat']);
                $this->conn->rollBack();
                return false;
            }

            $alatRowsAffected = $updateAlatStmt->rowCount();
            error_log("Rows affected by alat update: " . $alatRowsAffected);

            $this->conn->commit();
            error_log("Successfully processed return for peminjaman ID: " . $id_peminjaman);
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Exception in processPengembalian: " . $e->getMessage());
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

    // Method untuk mendapatkan peminjaman aktif berdasarkan user
    public function getPeminjamanAktifByUser($user_id) {
        $query = "SELECT p.*, a.nama_alat, a.id_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.id_user = :id_user AND p.status = 'disetujui'
                  ORDER BY p.tanggal_peminjaman DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Method untuk mendapatkan riwayat pengembalian berdasarkan user
    public function getRiwayatPengembalianByUser($user_id) {
        $query = "SELECT p.*, a.nama_alat, a.id_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.id_user = :id_user AND (p.status = 'dikembalikan' OR p.status = 'menunggu_konfirmasi')
                  ORDER BY p.updated_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Method untuk mendapatkan semua pengembalian
    public function getAllPengembalian() {
        $query = "SELECT p.*, u.nama as nama_peminjam, a.nama_alat
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  WHERE p.status = 'dikembalikan'
                  ORDER BY p.tanggal_dikembalikan DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Method untuk mendapatkan pengembalian yang menunggu konfirmasi
    public function getPengembalianMenungguKonfirmasi() {
        $query = "SELECT p.*, u.nama as nama_peminjam, u.username, u.email,
                         a.nama_alat, a.id_alat,
                         petugas.nama as nama_petugas
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  LEFT JOIN users petugas ON p.id_petugas = petugas.id_user
                  WHERE p.status = 'menunggu_konfirmasi'
                  ORDER BY p.tanggal_pengembalian_aktual DESC";
        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    // Method untuk mendapatkan peminjaman yang sudah selesai (menunggu konfirmasi pengembalian)
    public function getPeminjamanSelesai() {
        $query = "SELECT p.*, u.nama as nama_peminjam, u.username, u.email,
                         a.nama_alat, a.id_alat,
                         petugas.nama as nama_petugas
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  LEFT JOIN users petugas ON p.id_petugas = petugas.id_user
                  WHERE p.status = 'menunggu_konfirmasi'
                  ORDER BY p.tanggal_pengembalian_aktual DESC";
        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    // Method untuk mendapatkan pengembalian yang sudah dikonfirmasi
    public function getPengembalianDikonfirmasi() {
        $query = "SELECT p.*, u.nama as nama_peminjam, u.username, u.email,
                         a.nama_alat, a.id_alat,
                         petugas.nama as nama_petugas
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.id_user = u.id_user
                  LEFT JOIN alat a ON p.id_alat = a.id_alat
                  LEFT JOIN users petugas ON p.id_petugas = petugas.id_user
                  WHERE p.status = 'dikembalikan' AND p.kondisi_kembali IS NOT NULL AND p.kondisi_kembali != ''
                  ORDER BY p.updated_at DESC";
        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    // Method untuk menghitung pengembalian berdasarkan status
    public function countPengembalianByStatus($status) {
        if ($status === 'menunggu_konfirmasi') {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'menunggu_konfirmasi'";
        } elseif ($status === 'dikembalikan') {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'dikembalikan' AND kondisi_kembali IS NOT NULL AND kondisi_kembali != ''";
        } else {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = :status";
        }
        $stmt = $this->conn->prepare($query);
        if ($status !== 'menunggu_konfirmasi' && $status !== 'dikembalikan') {
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Method untuk konfirmasi pengembalian (alias untuk processPengembalian)
    public function konfirmasiPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $petugas_id) {
        return $this->processPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $petugas_id);
    }

    // Method untuk peminjam mengajukan pengembalian
    public function ajukanPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $user_id) {
        try {
            $this->conn->beginTransaction();

            // Get peminjaman details
            $peminjaman = $this->getPeminjamanById($id_peminjaman);
            if (!$peminjaman) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Peminjaman tidak ditemukan'];
            }

            // Check if peminjaman is approved
            if ($peminjaman['status'] !== 'disetujui') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Peminjaman tidak dalam status yang dapat dikembalikan'];
            }

            // Update peminjaman status to waiting confirmation
            $query = "UPDATE " . $this->table_name . " SET
                      status = 'menunggu_konfirmasi',
                      tanggal_pengembalian_aktual = NOW(),
                      kondisi_kembali = :kondisi_alat,
                      keterangan_kembali = :keterangan,
                      updated_at = NOW()
                      WHERE id_peminjaman = :id_peminjaman AND status = 'disetujui'";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id_peminjaman' => $id_peminjaman,
                ':kondisi_alat' => $kondisi_alat,
                ':keterangan' => $keterangan
            ]);

            if (!$result || $stmt->rowCount() == 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Gagal memperbarui status peminjaman'];
            }

            // Log activity
            $this->logActivity($user_id, "Mengajukan pengembalian #{$id_peminjaman} ({$peminjaman['nama_alat']}) - Kondisi: {$kondisi_alat}");

            $this->conn->commit();
            return ['success' => true, 'message' => 'Pengembalian alat berhasil diajukan! Menunggu konfirmasi dari petugas.'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Exception in ajukanPengembalian: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Method untuk peminjam mengajukan pengembalian (tidak langsung update stok)
    public function kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, $keterangan, $user_id) {
        return $this->ajukanPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $user_id);
    }

    private function logActivity($user_id, $activity) {
        $query = "INSERT INTO log_aktifitas (id_user, aktifitas) VALUES (:id_user, :aktifitas)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_user', $user_id);
        $stmt->bindParam(':aktifitas', $activity);
        $stmt->execute();
    }
}
