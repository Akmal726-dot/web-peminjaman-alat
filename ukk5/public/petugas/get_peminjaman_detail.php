<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../src/models/Peminjaman.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id_peminjaman = $_GET['id'];

    $peminjamanModel = new Peminjaman();
    $detail = $peminjamanModel->getDetailPeminjaman($id_peminjaman);

    if ($detail) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id_peminjaman' => $detail['id_peminjaman'],
            'nama_peminjam' => $detail['nama_peminjam'],
            'email' => $detail['email'],
            'no_hp' => $detail['no_hp'],
            'nama_alat' => $detail['nama_alat'],
            'id_alat' => $detail['kode_alat'],
            'deskripsi' => $detail['spesifikasi'] ?: $detail['deskripsi'],
            'tanggal_peminjaman' => $detail['tanggal_peminjaman'],
            'tanggal_pengembalian' => $detail['tanggal_pengembalian'],
            'status' => $detail['status'],
            'keterangan' => $detail['keterangan'],
            'alasan_penolakan' => $detail['alasan_penolakan'],
            'created_at' => $detail['created_at']
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Data peminjaman tidak ditemukan']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
