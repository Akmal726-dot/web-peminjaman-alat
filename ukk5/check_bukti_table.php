<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get full column info using PostgreSQL syntax
    $stmt = $conn->prepare("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'bukti' AND table_schema = 'public' ORDER BY ordinal_position");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in bukti table:\n";
    foreach($columns as $col) {
        echo $col['column_name'] . ' - ' . $col['data_type'] . ' - ' . ($col['is_nullable'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . ($col['column_default'] ?? 'NO DEFAULT') . "\n";
    }

    // Also check if there's any data
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bukti");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal records in bukti table: " . $count['total'] . "\n";

    if ($count['total'] > 0) {
        // Show sample data
        $stmt = $conn->prepare("SELECT * FROM bukti LIMIT 5");
        $stmt->execute();
        $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nSample data:\n";
        foreach($sample as $row) {
            echo json_encode($row) . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
