<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT p.id_peminjaman, p.status, u.nama as nama_peminjam, a.nama_alat
                           FROM peminjaman p
                           LEFT JOIN users u ON p.id_user = u.id_user
                           LEFT JOIN alat a ON p.id_alat = a.id_alat
                           WHERE p.status = 'disetujui'
                           ORDER BY p.id_peminjaman DESC
                           LIMIT 5");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Active loans:\n";
    foreach($results as $result) {
        echo "ID: {$result['id_peminjaman']}, User: {$result['nama_peminjam']}, Tool: {$result['nama_alat']}, Status: {$result['status']}\n";
    }

    if (empty($results)) {
        echo "No active loans found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
