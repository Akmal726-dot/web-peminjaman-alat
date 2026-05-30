<?php
require_once 'src/config/database.php';
require_once 'src/models/Bukti.php';
require_once 'src/models/Peminjaman.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $buktiModel = new Bukti();
    $peminjamanModel = new Peminjaman();

    // Get all bukti records
    $buktiRecords = $buktiModel->getAllBukti();

    if ($buktiRecords && $buktiRecords->rowCount() > 0) {
        while ($bukti = $buktiRecords->fetch(PDO::FETCH_ASSOC)) {
            if (empty($bukti['id_peminjaman'])) {
                // Find a matching peminjaman for this user
                $peminjamanStmt = $peminjamanModel->getPeminjamanByUser($bukti['id_user']);
                $peminjamanData = $peminjamanStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($peminjamanData)) {
                    // Use the first peminjaman for this user
                    $loanId = $peminjamanData[0]['id_peminjaman'];

                    // Update the bukti record
                    $updateData = [
                        'id_peminjaman' => $loanId,
                        'nama_file' => $bukti['nama_file'],
                        'keterangan' => $bukti['keterangan'],
                        'tipe_bukti' => $bukti['tipe_bukti']
                    ];

                    $result = $buktiModel->updateBukti($bukti['id'], $updateData);

                    if ($result) {
                        echo "Updated bukti ID {$bukti['id']} with loan ID {$loanId}\n";
                    } else {
                        echo "Failed to update bukti ID {$bukti['id']}\n";
                    }
                } else {
                    echo "No peminjaman found for user ID {$bukti['id_user']}, bukti ID {$bukti['id']}\n";
                }
            } else {
                echo "Bukti ID {$bukti['id']} already has loan ID {$bukti['id_peminjaman']}\n";
            }
        }
    } else {
        echo "No bukti records found\n";
    }

    echo "Update process completed!\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
