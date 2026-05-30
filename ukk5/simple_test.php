<?php
// Simple test page for return functionality
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force set session
$_SESSION['user_id'] = 6;
$_SESSION['role'] = 'peminjam';

require_once 'src/models/Peminjaman.php';

$user_id = $_SESSION['user_id'];
$peminjamanModel = new Peminjaman();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_item'])) {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $kondisi_alat = $_POST['kondisi_alat'] ?? '';

    if ($id_peminjaman && !empty($kondisi_alat)) {
        $result = $peminjamanModel->kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, 'Simple test', $user_id);

        if ($result['success']) {
            $message = '<div style="color:green; padding:10px; border:1px solid green; margin:10px 0;">SUCCESS: ' . $result['message'] . '</div>';
        } else {
            $message = '<div style="color:red; padding:10px; border:1px solid red; margin:10px 0;">FAILED: ' . $result['message'] . '</div>';
        }
    } else {
        $message = '<div style="color:orange; padding:10px; border:1px solid orange; margin:10px 0;">ERROR: Missing required fields</div>';
    }
}

// Get active loans
$aktifStmt = $peminjamanModel->getPeminjamanAktifByUser($user_id);
$aktifStmt->execute();
$aktifData = $aktifStmt->fetchAll(PDO::FETCH_ASSOC);

echo '<!DOCTYPE html><html><head><title>Test Return</title></head><body>';
echo '<h1>Test Return Functionality</h1>';
echo '<p>User ID: ' . $user_id . '</p>';
echo '<p>Active Loans: ' . count($aktifData) . '</p>';

echo $message;

if (count($aktifData) > 0) {
    echo '<h2>Return Form:</h2>';
    echo '<form method="POST">';
    echo '<select name="id_peminjaman" required>';
    echo '<option value="">Select item to return</option>';

    foreach ($aktifData as $loan) {
        echo '<option value="' . $loan['id_peminjaman'] . '">' . $loan['nama_alat'] . ' (ID: ' . $loan['id_peminjaman'] . ')</option>';
    }

    echo '</select><br><br>';

    echo '<select name="kondisi_alat" required>';
    echo '<option value="">Select condition</option>';
    echo '<option value="baik">Baik</option>';
    echo '<option value="rusak_ringan">Rusak Ringan</option>';
    echo '<option value="rusak_berat">Rusak Berat</option>';
    echo '</select><br><br>';

    echo '<button type="submit" name="return_item">Return Item</button>';
    echo '</form>';
} else {
    echo '<p>No items available for return.</p>';
}

echo '</body></html>';
?>