<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add missing columns to users table
    $queries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255)",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS no_hp VARCHAR(20)"
    ];

    foreach ($queries as $query) {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo "Executed: $query\n";
    }

    echo "All missing columns added to users table successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
