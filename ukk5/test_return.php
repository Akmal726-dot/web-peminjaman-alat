<?php
require_once 'src/config/database.php';
require_once 'src/models/Peminjaman.php';

try {
    $peminjamanModel = new Peminjaman();
    $user_id = 6; // User dedi who has active loans

    // Get active loans
    $activeLoans = $peminjamanModel->getPeminjamanAktifByUser($user_id);
    $data = $activeLoans->fetchAll(PDO::FETCH_ASSOC);

    echo 'Active loans for user ' . $user_id . ': ' . count($data) . PHP_EOL;
    if (count($data) > 0) {
        foreach ($data as $loan) {
            echo 'ID: ' . $loan['id_peminjaman'] . ', Alat: ' . $loan['nama_alat'] . ', Status: ' . $loan['status'] . PHP_EOL;
        }

        // Test return functionality
        if (count($data) > 0) {
            $testLoan = $data[0];
            echo PHP_EOL . 'Testing return for loan ID: ' . $testLoan['id_peminjaman'] . PHP_EOL;
            $result = $peminjamanModel->kembalikanAlatLangsung($testLoan['id_peminjaman'], 'baik', 'Test return', $user_id);
            echo 'Result: ' . json_encode($result) . PHP_EOL;
        }
    } else {
        echo 'No active loans found' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>