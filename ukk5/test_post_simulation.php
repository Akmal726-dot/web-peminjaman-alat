<?php
// Simulate POST request to pengembalian.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'kembalikan' => '1',
    'id_peminjaman' => '28',
    'kondisi_alat' => 'baik',
    'catatan' => 'Test return via direct POST'
];

// Simulate session
session_start();
$_SESSION['user_id'] = 6;
$_SESSION['role'] = 'peminjam';

echo "=== SIMULATING POST REQUEST ===\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST DATA: " . json_encode($_POST) . "\n";
echo "SESSION USER ID: " . $_SESSION['user_id'] . "\n\n";

// Include the pengembalian.php logic
require_once 'src/models/Peminjaman.php';
require_once 'src/models/Alat.php';

$user_id = $_SESSION['user_id'];
$peminjamanModel = new Peminjaman();
$alatModel = new Alat();

$message = '';
$message_type = '';

// Handle pengembalian alat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kembalikan'])) {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $kondisi_alat = $_POST['kondisi_alat'] ?? '';
    $catatan = $_POST['catatan'] ?? '';

    error_log("POST RECEIVED - ID: $id_peminjaman, Kondisi: $kondisi_alat, Catatan: $catatan, User: $user_id");
    error_log("SESSION DATA - User ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role']);
    error_log("POST DATA: " . json_encode($_POST));

    if ($id_peminjaman && !empty($kondisi_alat)) {
        try {
            error_log("CALLING kembalikanAlatLangsung...");
            $result = $peminjamanModel->kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, $catatan, $user_id);
            error_log("RESULT: " . json_encode($result));

            if ($result['success']) {
                error_log("SUCCESS - Setting message and refreshing data");

                $message = $result['message'];
                $message_type = 'success';

                // Refresh data
                $peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
                $peminjamanAktifData = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);
                $riwayatPengembalian = $peminjamanModel->getRiwayatPengembalianByUser($user_id);
                $riwayatPengembalianData = $riwayatPengembalian->fetchAll(PDO::FETCH_ASSOC);

                error_log("DATA REFRESHED - Active loans: " . count($peminjamanAktifData));

                // Redirect to avoid resubmission
                $redirect_url = "pengembalian.php?success=1&t=" . time();
                error_log("REDIRECTING TO: $redirect_url");
                echo "WOULD REDIRECT TO: $redirect_url\n";
                // header("Location: $redirect_url");
                // exit();
            } else {
                error_log("FAILED - Message: " . $result['message']);
                $message = $result['message'];
                $message_type = 'danger';
            }

        } catch (Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        echo "Missing required data: id_peminjaman or kondisi_alat\n";
    }
} else {
    echo "POST condition not met\n";
}

echo "\nFINAL RESULT:\n";
echo "Message: $message\n";
echo "Type: $message_type\n";
?>