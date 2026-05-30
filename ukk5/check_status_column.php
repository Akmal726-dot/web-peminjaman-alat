<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Connected to database successfully.\n";

    // Check if status column exists
    $stmt = $conn->prepare('SELECT column_name FROM information_schema.columns WHERE table_name = \'peminjaman\' AND column_name = \'status\'');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "Status column exists in peminjaman table.\n";
    } else {
        echo "Status column does NOT exist in peminjaman table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
