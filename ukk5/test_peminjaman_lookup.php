<?php
require_once 'src/config/database.php';
require_once 'src/models/Peminjaman.php';

try {
    $peminjamanModel = new Peminjaman();

    // Test for user 4 (has disetujui records)
    echo "Testing user 4 (should have disetujui records):\n";
    $stmt = $peminjamanModel->getPeminjamanByUserAndStatus(4, 'disetujui');
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($results) . " disetujui records\n";
    if (!empty($results)) {
        echo "First record: " . json_encode($results[0]) . "\n";
    }

    // Test for user 6 (has dikembalikan records)
    echo "\nTesting user 6 (should have dikembalikan records):\n";
    $stmt = $peminjamanModel->getPeminjamanByUserAndStatus(6, 'dikembalikan');
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($results) . " dikembalikan records\n";
    if (!empty($results)) {
        echo "First record: " . json_encode($results[0]) . "\n";
    }

    // Test general lookup for user 4
    echo "\nTesting general lookup for user 4:\n";
    $stmt = $peminjamanModel->getPeminjamanByUser(4);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($results) . " total records\n";
    if (!empty($results)) {
        echo "First record: " . json_encode($results[0]) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
