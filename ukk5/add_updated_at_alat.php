<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add updated_at column to alat table if it doesn't exist
    $query = "ALTER TABLE alat ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW()";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    echo "updated_at column added successfully to alat table!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
