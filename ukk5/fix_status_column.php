<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Connected to database successfully.\n";

    // Try to add the status column
    $query = "ALTER TABLE peminjaman ADD COLUMN status VARCHAR(50) DEFAULT 'pending'";
    $stmt = $conn->prepare($query);

    if ($stmt->execute()) {
        echo "Status column added successfully!\n";
    } else {
        echo "Failed to add status column.\n";
        print_r($stmt->errorInfo());
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "Status column already exists.\n";
    }
}
?>
