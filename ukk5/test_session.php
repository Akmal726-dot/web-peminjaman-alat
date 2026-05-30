<?php
session_start();
$_SESSION['user_id'] = 15;
$_SESSION['role'] = 'peminjam';

require_once 'src/models/Peminjaman.php';
$peminjamanModel = new Peminjaman();
$user_id = 15;

echo 'Testing getPeminjamanAktifByUser for user ' . $user_id . PHP_EOL;
$peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
$data = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);

echo 'Found ' . count($data) . ' active loans' . PHP_EOL;
foreach ($data as $loan) {
    echo 'ID: ' . $loan['id_peminjaman'] . ', Alat: ' . $loan['nama_alat'] . ', Status: ' . $loan['status'] . PHP_EOL;
}
?>