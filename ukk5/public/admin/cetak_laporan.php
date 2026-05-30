<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
require_once '../../src/models/Alat.php';
require_once '../../src/models/User.php';
require_once '../../src/models/Kategori.php';

$peminjamanModel = new Peminjaman();
$alatModel = new Alat();
$userModel = new User();
$kategoriModel = new Kategori();

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_tanggal_dari = $_GET['tanggal_dari'] ?? '';
$filter_tanggal_sampai = $_GET['tanggal_sampai'] ?? '';
$filter_kategori = $_GET['kategori'] ?? 'all';

// Build query conditions
$conditions = [];
$params = [];

if ($filter_status != 'all') {
    $conditions[] = "status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_tanggal_dari) {
    $conditions[] = "tanggal_peminjaman >= :tanggal_dari";
    $params[':tanggal_dari'] = $filter_tanggal_dari;
}

if ($filter_tanggal_sampai) {
    $conditions[] = "tanggal_peminjaman <= :tanggal_sampai";
    $params[':tanggal_sampai'] = $filter_tanggal_sampai;
}

if ($filter_kategori != 'all') {
    $conditions[] = "a.id_kategori = :kategori";
    $params[':kategori'] = $filter_kategori;
}

$whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Get report data
$laporanData = $peminjamanModel->getFilteredPeminjaman($whereClause, $params);

// Get statistics
$totalPeminjaman = $laporanData->rowCount();

// Count by status
$statusStats = [
    'pending' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'dikembalikan' => 0
];

$statusStatsStmt = $peminjamanModel->getStatusStats($whereClause, $params);
while ($row = $statusStatsStmt->fetch(PDO::FETCH_ASSOC)) {
    $statusStats[$row['status_peminjaman']] = $row['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset untuk print */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: white;
            color: black;
            padding: 20px;
            line-height: 1.4;
        }

        /* Header */
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .print-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .print-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .print-date {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }

        /* Table */
        .table-container {
            margin-top: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table thead th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        .table tbody td {
            border: 1px solid #dee2e6;
            padding: 10px 8px;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-dikembalikan {
            background-color: #17a2b8;
            color: white;
        }

        /* Print buttons */
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .btn-print {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Footer */
        .print-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }

        /* Hide elements when printing */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding: 10px;
                font-size: 12px;
            }
            
            .print-header {
                border-bottom: 1px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
            }
            
            .print-title {
                font-size: 18px;
            }
            
            .print-subtitle {
                font-size: 12px;
            }
            
            .table thead th {
                padding: 8px 6px;
                font-size: 12px;
            }
            
            .table tbody td {
                padding: 6px 6px;
                font-size: 11px;
            }
            
            .status-badge {
                padding: 2px 8px;
                font-size: 10px;
            }
            
            .print-footer {
                font-size: 10px;
                margin-top: 20px;
                padding-top: 10px;
            }
        }

        /* Additional styling for web view */
        @media screen {
            body {
                background: linear-gradient(135deg, #0f172a, #1e293b);
                min-height: 100vh;
                padding: 30px;
            }
            
            .content-wrapper {
                background: white;
                border-radius: 15px;
                padding: 40px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                max-width: 1000px;
                margin: 0 auto;
            }
            
            .print-header {
                background: linear-gradient(135deg, #3b82f6, #06b6d4);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                border-bottom: 2px solid #3b82f6;
            }
            
            .btn-print:hover, .btn-back:hover {
                opacity: 0.9;
                transform: translateY(-2px);
                transition: all 0.3s ease;
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- Header -->
        <div class="print-header">
            <h1 class="print-title">Laporan Peminjaman</h1>
            <p class="print-subtitle">Sistem Peminjaman Alat - Admin Panel</p>
            <p class="print-subtitle">Laporan lengkap peminjaman alat dengan filter dan statistik</p>
            <p class="print-date"><?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Peminjam</th>
                        <th>Alat</th>
                        <th>Kategori</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $laporanData->execute($params);
                    $hasData = false;
                    
                    // Fetch all data first
                    $dataRows = [];
                    while ($row = $laporanData->fetch(PDO::FETCH_ASSOC)) {
                        $hasData = true;
                        $dataRows[] = $row;
                    }
                    
                    // Display data if exists
                    if ($hasData) {
                        $counter = 1;
                        foreach ($dataRows as $row) {
                            $status_class = '';
                            $status_icon = '';
                            switch($row['status']) {
                                case 'disetujui':
                                    $status_class = 'badge bg-success';
                                    $status_icon = 'fa-check-circle';
                                    break;
                                case 'pending':
                                    $status_class = 'badge bg-warning';
                                    $status_icon = 'fa-clock';
                                    break;
                                case 'ditolak':
                                    $status_class = 'badge bg-danger';
                                    $status_icon = 'fa-times-circle';
                                    break;
                                case 'dikembalikan':
                                    $status_class = 'badge bg-info';
                                    $status_icon = 'fa-undo';
                                    break;
                                default:
                                    $status_class = 'badge bg-secondary';
                                    $status_icon = 'fa-question-circle';
                            }
                    ?>
                    <tr>
                        <td class="fw-bold"><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_peminjam']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_alat']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_kategori'] ?? '-'); ?></td>
                        <td><?php echo $row['tanggal_peminjaman'] ? date('d/m/Y', strtotime($row['tanggal_peminjaman'])) : '-'; ?></td>
                        <td><?php echo isset($row['tanggal_kembali']) && $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-'; ?></td>
                        <td><?php echo $row['jumlah']; ?> unit</td>
                        <td>
                            <span class="<?php echo $status_class; ?>">
                                <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="print-footer">
            <p>Dicetak pada: <?php echo date('d F Y H:i:s'); ?></p>
            <p>Total Data: <?php echo $totalPeminjaman; ?> peminjaman</p>
        </div>

        <!-- Action Buttons -->
        <div class="print-actions no-print">
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
            <a href="laporan.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Laporan
            </a>
        </div>
    </div>

    <script>
        // Auto print jika parameter print=true
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.print();
        }

        // Shortcut Ctrl+P
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Set judul halaman saat print
        window.addEventListener('beforeprint', function() {
            document.title = "Laporan Peminjaman - " + new Date().toLocaleDateString();
        });

        window.addEventListener('afterprint', function() {
            document.title = "Cetak Laporan Peminjaman";
        });
    </script>
</body>
</html>