<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if status column exists in alat table
    $checkQuery = "SELECT column_name FROM information_schema.columns WHERE table_name = 'alat' AND column_name = 'status' AND table_schema = 'public'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute();

    if ($checkStmt->rowCount() == 0) {
        // Add status column if it doesn't exist
        $query = "ALTER TABLE alat ADD COLUMN status VARCHAR(20) DEFAULT 'tersedia'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo "Status column added to alat table successfully!\n";
    } else {
        echo "Status column already exists in alat table!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
