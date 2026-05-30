<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add missing columns
    $queries = [
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS tanggal_pengembalian TIMESTAMP",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW()",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS denda NUMERIC(10,2) DEFAULT 0",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS kondisi_kembali VARCHAR(50)",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS keterangan_kembali TEXT",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS tanggal_dikembalikan TIMESTAMP",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS tanggal_pengembalian_aktual TIMESTAMP",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS keterangan TEXT",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS id_petugas INT REFERENCES users(id_user)",
        "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS alasan_penolakan TEXT",
        "ALTER TABLE peminjaman ALTER COLUMN tanggal_kembali DROP NOT NULL"
    ];

    foreach ($queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo "Executed: $query\n";
    }

    echo "All missing columns added successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
