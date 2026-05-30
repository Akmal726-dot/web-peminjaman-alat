<?php
// Simulate web request to pengembalian.php
$_SERVER['REQUEST_METHOD'] = 'GET'; // Initial page load

// Simulate session for user 18 (ica) who has active loan
session_start();
$_SESSION['user_id'] = 18;
$_SESSION['role'] = 'peminjam';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

require_once 'src/models/Peminjaman.php';
require_once 'src/models/Alat.php';

$peminjamanModel = new Peminjaman();
$alatModel = new Alat();

// Get peminjaman aktif yang sedang dipinjam oleh user ini
$peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
$peminjamanAktifData = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);

// Get riwayat pengembalian user ini
$riwayatPengembalian = $peminjamanModel->getRiwayatPengembalianByUser($user_id);
$riwayatPengembalianData = $riwayatPengembalian->fetchAll(PDO::FETCH_ASSOC);

echo "=== PAGE LOAD SIMULATION ===\n";
echo "User ID: $user_id\n";
echo "Active loans: " . count($peminjamanAktifData) . "\n";

if (count($peminjamanAktifData) > 0) {
    echo "Active loan details:\n";
    foreach ($peminjamanAktifData as $loan) {
        echo "- ID: {$loan['id_peminjaman']}, Alat: {$loan['nama_alat']}, Status: {$loan['status']}\n";
    }

    // Now simulate form submission
    echo "\n=== FORM SUBMISSION SIMULATION ===\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'kembalikan' => '1',
        'id_peminjaman' => $peminjamanAktifData[0]['id_peminjaman'],
        'kondisi_alat' => 'baik',
        'catatan' => 'Test return from web simulation'
    ];

    // Handle pengembalian alat
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kembalikan'])) {
        $id_peminjaman = $_POST['id_peminjaman'] ?? null;
        $kondisi_alat = $_POST['kondisi_alat'] ?? '';
        $catatan = $_POST['catatan'] ?? '';

        echo "POST data: ID=$id_peminjaman, Kondisi=$kondisi_alat, User=$user_id\n";

        if ($id_peminjaman && !empty($kondisi_alat)) {
            try {
                $result = $peminjamanModel->kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, $catatan, $user_id);
                echo "Return result: " . json_encode($result) . "\n";

                if ($result['success']) {
                    echo "SUCCESS: Return processed\n";
                    $message = $result['message'];
                    $message_type = 'success';

                    // Refresh data
                    $peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
                    $peminjamanAktifData = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);
                    echo "Active loans after return: " . count($peminjamanAktifData) . "\n";
                } else {
                    echo "FAILED: " . $result['message'] . "\n";
                    $message = $result['message'];
                    $message_type = 'danger';
                }

            } catch (Exception $e) {
                echo "EXCEPTION: " . $e->getMessage() . "\n";
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            echo "Missing required data\n";
        }
    }
} else {
    echo "No active loans found for this user\n";
}

echo "\nFinal message: $message (type: $message_type)\n";
?>