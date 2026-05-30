<?php
session_start();
require_once 'src/config/database.php';
require_once 'src/models/Peminjaman.php';

// Simulate a user session
$_SESSION['user_id'] = 6; // Assuming user ID 6 exists
$_SESSION['role'] = 'peminjam';

$user_id = $_SESSION['user_id'];
$peminjamanModel = new Peminjaman();

// Get active loans for this user
$peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
$activeLoans = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);

echo "Active loans for user $user_id:\n";
foreach ($activeLoans as $loan) {
    echo "ID: {$loan['id_peminjaman']}, Tool: {$loan['nama_alat']}, Status: {$loan['status']}\n";
}

if (count($activeLoans) > 0) {
    // Test return for the first active loan
    $testLoan = $activeLoans[0];
    echo "\nTesting return for loan ID: {$testLoan['id_peminjaman']}\n";

    // Simulate POST data
    $_POST['kembalikan'] = '1';
    $_POST['id_peminjaman'] = $testLoan['id_peminjaman'];
    $_POST['kondisi_alat'] = 'baik';
    $_POST['catatan'] = 'Test return from web simulation';
    $_SERVER['REQUEST_METHOD'] = 'POST';

    // Simulate the form processing logic from pengembalian.php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kembalikan'])) {
        $id_peminjaman = $_POST['id_peminjaman'] ?? null;
        $kondisi_alat = $_POST['kondisi_alat'] ?? '';
        $catatan = $_POST['catatan'] ?? '';

        echo "Processing return - ID: $id_peminjaman, Kondisi: $kondisi_alat, Catatan: $catatan\n";

        if ($id_peminjaman && !empty($kondisi_alat)) {
            $result = $peminjamanModel->kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, $catatan, $user_id);

            echo "Return result: " . json_encode($result) . "\n";

            if ($result['success']) {
                echo "SUCCESS: Tool returned successfully!\n";
            } else {
                echo "FAILED: " . $result['message'] . "\n";
            }
        } else {
            echo "FAILED: Missing required fields\n";
        }
    }
} else {
    echo "No active loans found to test with.\n";
}
?>
