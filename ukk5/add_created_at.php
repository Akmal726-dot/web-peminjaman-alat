<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add created_at column if it doesn't exist
    $query = "ALTER TABLE peminjaman ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT NOW()";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    echo "created_at column added successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
