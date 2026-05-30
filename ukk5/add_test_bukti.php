<?php
require_once 'src/config/database.php';
require_once 'src/models/Bukti.php';

try {
    $buktiModel = new Bukti();

    // Add test bukti data
    $testBukti = [
        [
            'id_user' => 3, // Assuming user ID exists
            'id_peminjaman' => 1, // Add loan ID
            'nama_file' => 'bukti_pengembalian_001.jpg',
            'keterangan' => 'Bukti pengembalian alat proyektor',
            'tipe_bukti' => 'pengembalian'
        ],
        [
            'id_user' => 4,
            'id_peminjaman' => 2, // Add loan ID
            'nama_file' => 'bukti_peminjaman_001.pdf',
            'keterangan' => 'Surat permohonan peminjaman laptop',
            'tipe_bukti' => 'peminjaman'
        ],
        [
            'id_user' => 6,
            'id_peminjaman' => 3, // Add loan ID
            'nama_file' => 'bukti_pengembalian_002.png',
            'keterangan' => 'Foto kondisi alat setelah dikembalikan',
            'tipe_bukti' => 'pengembalian'
        ],
        [
            'id_user' => 8,
            'id_peminjaman' => 4, // Add loan ID
            'nama_file' => 'bukti_peminjaman_002.jpg',
            'keterangan' => 'Bukti identitas untuk peminjaman',
            'tipe_bukti' => 'peminjaman'
        ]
    ];

    foreach ($testBukti as $bukti) {
        $result = $buktiModel->createBukti($bukti);
        if ($result) {
            echo "Created bukti for user {$bukti['id_user']}: {$bukti['nama_file']}\n";
        } else {
            echo "Failed to create bukti for user {$bukti['id_user']}\n";
        }
    }

    echo "Test bukti data added successfully!\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
