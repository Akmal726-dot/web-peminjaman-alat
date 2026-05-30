<?php
require_once 'src/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get full column info using PostgreSQL syntax
    $stmt = $conn->prepare("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'peminjaman' AND table_schema = 'public' ORDER BY ordinal_position");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in peminjaman table:\n";
    foreach($columns as $col) {
        echo $col['column_name'] . ' - ' . $col['data_type'] . ' - ' . ($col['is_nullable'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . ($col['column_default'] ?? 'NO DEFAULT') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
        if ($col['Field'] === 'status') {
            $statusExists = true;
            break;
        }
    }

    if ($statusExists) {
        echo "\nStatus column exists!\n";
    } else {
        echo "\nStatus column does NOT exist!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
