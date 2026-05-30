<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Connected to database successfully.\n";

    // Update any NULL status values to 'pending'
    $query = "UPDATE peminjaman SET status = 'pending' WHERE status IS NULL";
    $stmt = $conn->prepare($query);

    if ($stmt->execute()) {
        $rowsAffected = $stmt->rowCount();
        echo "Updated $rowsAffected rows with NULL status to 'pending'.\n";
    } else {
        echo "Failed to update NULL status values.\n";
        print_r($stmt->errorInfo());
    }

    // Check how many rows have each status
    $countQuery = "SELECT status, COUNT(*) as count FROM peminjaman GROUP BY status";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $results = $countStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nStatus distribution:\n";
    foreach ($results as $result) {
        echo "{$result['status']}: {$result['count']} rows\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
