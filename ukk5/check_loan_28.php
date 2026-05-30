<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check loan 28 specifically
    $stmt = $conn->prepare("SELECT p.*, u.nama as nama_peminjam, a.nama_alat
                           FROM peminjaman p
                           LEFT JOIN users u ON p.id_user = u.id_user
                           LEFT JOIN alat a ON p.id_alat = a.id_alat
                           WHERE p.id_peminjaman = 28");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "Loan 28 details:\n";
        echo "ID: {$result['id_peminjaman']}\n";
        echo "User: {$result['nama_peminjam']} (ID: {$result['id_user']})\n";
        echo "Tool: {$result['nama_alat']} (ID: {$result['id_alat']})\n";
        echo "Status: {$result['status']}\n";
        echo "Tanggal Pinjam: {$result['tanggal_peminjaman']}\n";
        echo "Tanggal Kembali: {$result['tanggal_pengembalian']}\n";
        echo "Tanggal Dikembalikan: " . ($result['tanggal_dikembalikan'] ?? 'NULL') . "\n";
        echo "Kondisi Kembali: " . ($result['kondisi_kembali'] ?? 'NULL') . "\n";
        echo "Jumlah: {$result['jumlah']}\n";
    } else {
        echo "Loan 28 not found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
