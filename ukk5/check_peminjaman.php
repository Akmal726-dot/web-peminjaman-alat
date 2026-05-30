<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check peminjaman table
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total peminjaman records: " . $count['total'] . "\n";

    if ($count['total'] > 0) {
        // Show sample data
        $stmt = $conn->prepare("SELECT id_peminjaman, id_user, status FROM peminjaman LIMIT 5");
        $stmt->execute();
        $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nSample peminjaman data:\n";
        foreach($sample as $row) {
            echo json_encode($row) . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
