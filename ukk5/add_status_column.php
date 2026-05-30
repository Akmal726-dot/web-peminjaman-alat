<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if status column exists first
    $checkQuery = "SELECT column_name FROM information_schema.columns WHERE table_name = 'peminjaman' AND column_name = 'status' AND table_schema = 'public'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute();

    if ($checkStmt->rowCount() == 0) {
        // Add status column if it doesn't exist
        $query = "ALTER TABLE peminjaman ADD COLUMN status VARCHAR(50) DEFAULT 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo "Status column added to peminjaman table successfully!\n";
    } else {
        echo "Status column already exists in peminjaman table!\n";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute();

    echo "Status column added to peminjaman table successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
