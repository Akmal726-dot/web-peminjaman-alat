<?php
// Simulate server variables
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'kembalikan' => '1',
    'id_peminjaman' => '20', // User 15's active loan
    'kondisi_alat' => 'baik',
    'catatan' => 'Test from script'
];

// Simulate session
session_start();
$_SESSION['user_id'] = 15; // test_peminjam
$_SESSION['role'] = 'peminjam';

$user_id = $_SESSION['user_id'];

require_once 'src/models/Peminjaman.php';
$peminjamanModel = new Peminjaman();

echo "Simulating form submission for user $user_id\n";
echo "POST data: " . json_encode($_POST) . "\n";

// Handle pengembalian alat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kembalikan'])) {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $kondisi_alat = $_POST['kondisi_alat'] ?? '';
    $catatan = $_POST['catatan'] ?? '';

    echo "Processing return - ID: $id_peminjaman, Kondisi: $kondisi_alat, User: $user_id\n";

    if ($id_peminjaman && !empty($kondisi_alat)) {
        try {
            echo "Calling kembalikanAlatLangsung...\n";
            $result = $peminjamanModel->kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, $catatan, $user_id);
            echo "Result: " . json_encode($result) . "\n";

            if ($result['success']) {
                echo "SUCCESS - Return processed successfully\n";
            } else {
                echo "FAILED - " . $result['message'] . "\n";
            }

        } catch (Exception $e) {
            echo "EXCEPTION: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Missing required data\n";
    }
} else {
    echo "Not a POST request or kembalikan not set\n";
}
?>