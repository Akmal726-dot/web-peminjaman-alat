<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if kondisi column exists
    $checkQuery = "SELECT column_name FROM information_schema.columns WHERE table_name = 'alat' AND column_name = 'kondisi'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute();

    if ($checkStmt->rowCount() == 0) {
        // Add kondisi column
        $query = "ALTER TABLE alat ADD COLUMN kondisi VARCHAR(20) DEFAULT 'baik' CHECK (kondisi IN ('baik', 'kurang_baik', 'rusak', 'masih_bisa_digunakan'))";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo "Kondisi column added to alat table successfully!\n";
    } else {
        echo "Kondisi column already exists in alat table!\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
