<?php
require_once 'src/config/database.php';

try {
    $conn = new PDO("pgsql:host=localhost;dbname=ukk2", "postgres", "akmal12345");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check users
    echo "=== USERS ===\n";
    $stmt = $conn->query("SELECT id_user, username, role FROM users ORDER BY id_user");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id_user']}, Username: {$row['username']}, Role: {$row['role']}\n";
    }

    echo "\n=== PEMINJAMAN ===\n";
    $stmt = $conn->query("SELECT p.*, a.nama_alat FROM peminjaman p LEFT JOIN alat a ON p.id_alat = a.id_alat ORDER BY p.id_peminjaman DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id_peminjaman']}, User: {$row['id_user']}, Alat: {$row['nama_alat']}, Status: {$row['status']}, Tanggal: {$row['tanggal_peminjaman']}\n";
    }

    echo "\n=== ACTIVE LOANS (disetujui) ===\n";
    $stmt = $conn->query("SELECT p.*, a.nama_alat FROM peminjaman p LEFT JOIN alat a ON p.id_alat = a.id_alat WHERE p.status = 'disetujui' ORDER BY p.id_peminjaman DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id_peminjaman']}, User: {$row['id_user']}, Alat: {$row['nama_alat']}, Status: {$row['status']}\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>