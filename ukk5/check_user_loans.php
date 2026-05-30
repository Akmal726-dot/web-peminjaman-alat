<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $user_id = 6;

    // Check all loans for user 6
    $stmt = $conn->prepare("SELECT p.*, u.nama as nama_peminjam, a.nama_alat
                           FROM peminjaman p
                           LEFT JOIN users u ON p.id_user = u.id_user
                           LEFT JOIN alat a ON p.id_alat = a.id_alat
                           WHERE p.id_user = ?
                           ORDER BY p.id_peminjaman DESC");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "All loans for user $user_id:\n";
    foreach($results as $result) {
        echo "ID: {$result['id_peminjaman']}, Tool: {$result['nama_alat']}, Status: {$result['status']}, Tanggal Dikembalikan: " . ($result['tanggal_dikembalikan'] ?? 'NULL') . "\n";
    }

    if (empty($results)) {
        echo "No loans found for user $user_id.\n";
    }

    // Check active loans specifically
    echo "\nActive loans (status = 'disetujui'):\n";
    $activeStmt = $conn->prepare("SELECT p.*, u.nama as nama_peminjam, a.nama_alat
                                 FROM peminjaman p
                                 LEFT JOIN users u ON p.id_user = u.id_user
                                 LEFT JOIN alat a ON p.id_alat = a.id_alat
                                 WHERE p.id_user = ? AND p.status = 'disetujui'
                                 ORDER BY p.id_peminjaman DESC");
    $activeStmt->execute([$user_id]);
    $activeResults = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($activeResults as $result) {
        echo "ID: {$result['id_peminjaman']}, Tool: {$result['nama_alat']}, Status: {$result['status']}\n";
    }

    if (empty($activeResults)) {
        echo "No active loans found for user $user_id.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
