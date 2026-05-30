<?php
require_once 'src/config/database.php';
require_once 'src/models/Peminjaman.php';
require_once 'src/models/Alat.php';

try {
    $peminjamanModel = new Peminjaman();

    // Add active loans for different users
    $loans = [
        ['user_id' => 3, 'alat_id' => 1, 'tanggal_peminjaman' => '2024-01-20', 'tanggal_pengembalian' => '2024-01-25', 'jumlah' => 1, 'keterangan' => 'Test loan for peminjam1'],
        ['user_id' => 4, 'alat_id' => 2, 'tanggal_peminjaman' => '2024-01-20', 'tanggal_pengembalian' => '2024-01-25', 'jumlah' => 1, 'keterangan' => 'Test loan for peminjam2'],
        ['user_id' => 6, 'alat_id' => 3, 'tanggal_peminjaman' => '2024-01-20', 'tanggal_pengembalian' => '2024-01-25', 'jumlah' => 1, 'keterangan' => 'Test loan for dedi'],
        ['user_id' => 8, 'alat_id' => 4, 'tanggal_peminjaman' => '2024-01-20', 'tanggal_pengembalian' => '2024-01-25', 'jumlah' => 1, 'keterangan' => 'Test loan for viva'],
    ];

    foreach ($loans as $loan) {
        $result = $peminjamanModel->createPeminjaman(
            $loan['user_id'],
            $loan['alat_id'],
            $loan['tanggal_peminjaman'],
            $loan['tanggal_pengembalian'],
            $loan['jumlah'],
            $loan['keterangan']
        );

        if ($result['success']) {
            echo "Created loan for user {$loan['user_id']}: {$result['message']}\n";
        } else {
            echo "Failed to create loan for user {$loan['user_id']}: {$result['message']}\n";
        }
    }

    // Now approve some loans to make them active
    $loansToApprove = [26, 27, 28, 29]; // Assuming these are the new loan IDs

    foreach ($loansToApprove as $loanId) {
        $result = $peminjamanModel->updateStatus($loanId, 'disetujui');
        if ($result) {
            echo "Approved loan ID $loanId\n";
        } else {
            echo "Failed to approve loan ID $loanId\n";
        }
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>