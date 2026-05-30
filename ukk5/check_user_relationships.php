<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get users from bukti table
    $stmt = $conn->prepare("SELECT DISTINCT id_user FROM bukti");
    $stmt->execute();
    $buktiUsers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "Users in bukti table: " . implode(', ', $buktiUsers) . "\n";

    // Get users from peminjaman table
    $stmt = $conn->prepare("SELECT DISTINCT id_user FROM peminjaman");
    $stmt->execute();
    $peminjamanUsers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "Users in peminjaman table: " . implode(', ', $peminjamanUsers) . "\n";

    // Check intersection
    $commonUsers = array_intersect($buktiUsers, $peminjamanUsers);
    echo "Common users: " . implode(', ', $commonUsers) . "\n";

    // Check peminjaman records for each bukti user
    foreach ($buktiUsers as $userId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE id_user = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "User $userId has {$count['total']} peminjaman records\n";

        if ($count['total'] > 0) {
            $stmt = $conn->prepare("SELECT id_peminjaman, status FROM peminjaman WHERE id_user = ? LIMIT 3");
            $stmt->execute([$userId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($records as $record) {
                echo "  - ID: {$record['id_peminjaman']}, Status: {$record['status']}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
