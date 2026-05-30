<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peminjam') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Alat.php';
require_once '../../src/models/Peminjaman.php';

$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'nama' => $_SESSION['nama']
];

$alatModel = new Alat();
$peminjamanModel = new Peminjaman();

$alat_stmt = $alatModel->getAllAlat();
$alat_data = $alat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Alat - Peminjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --hijau: #28a745;
            --hijau-gelap: #218838;
            --hijau-muda: #d4edda;
            --hijau-transparan: rgba(40, 167, 69, 0.1);
            
            --hitam: #212529;
            --hitam-gelap: #121416;
            --hitam-terang: #343a40;
            
            --putih: #ffffff;
            --abu: #f8f9fa;
            --abu-gelap: #e9ecef;
            --abu-text: #6c757d;
            
            --border: #dee2e6;
            
            --warning: #ffc107;
            --warning-gelap: #e0a800;
            --warning-transparan: rgba(255, 193, 7, 0.1);
            
            --danger: #dc3545;
            --danger-gelap: #c82333;
            --danger-transparan: rgba(220, 53, 69, 0.1);
            
            --info: #17a2b8;
            --info-gelap: #138496;
        }

        body {
            background-color: var(--abu);
            color: var(--hitam);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .text-hijau {
            color: var(--hijau) !important;
        }

        .bg-hijau {
            background-color: var(--hijau) !important;
        }

        .border-hijau {
            border-color: var(--hijau) !important;
        }

        .btn-hijau {
            background-color: var(--hijau) !important;
            border-color: var(--hijau) !important;
            color: var(--putih) !important;
        }

        .btn-hijau:hover {
            background-color: var(--hijau-gelap) !important;
            border-color: var(--hijau-gelap) !important;
        }

        .stat-card {
            background: var(--putih);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .card {
            background: var(--putih) !important;
            border: 1px solid var(--border) !important;
        }

        .table {
            color: var(--hitam) !important;
            border-color: var(--border) !important;
        }

        .table thead th {
            background-color: var(--abu) !important;
            border-color: var(--border) !important;
            color: var(--hijau) !important;
            font-weight: 600;
        }

        .table tbody tr {
            border-color: var(--border) !important;
        }

        .table tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.05) !important;
        }

        .navbar {
            background: var(--putih) !important;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .text-muted {
            color: var(--abu-text) !important;
        }

        .border {
            border-color: var(--border) !important;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--abu-gelap);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--hijau);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--hijau-gelap);
        }

        .badge {
            font-weight: 500;
        }

        /* Tombol Kembali */
        .btn-kembali {
            background-color: var(--hijau);
            color: var(--putih);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-kembali:hover {
            background-color: var(--hijau-gelap);
            color: var(--putih);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }

        /* Styling untuk card statistik */
        .card-stat {
            background: var(--putih);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        /* Styling untuk navbar */
        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--hijau);
        }

        .navbar-brand:hover {
            color: var(--hijau-gelap);
        }

        .badge-role {
            background: var(--hijau-transparan);
            color: var(--hijau);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid var(--hijau);
        }

        /* Styling untuk card utama */
        .main-card {
            background: var(--putih);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .main-card .card-header {
            background-color: var(--abu);
            border-bottom: 1px solid var(--border);
            padding: 20px;
        }

        .main-card .card-footer {
            background-color: var(--abu);
            border-top: 1px solid var(--border);
            padding: 15px 20px;
        }

        /* Status badges */
        .badge-tersedia {
            background: var(--hijau-muda);
            color: var(--hijau-gelap);
            border: 1px solid var(--hijau);
        }

        .badge-terbatas {
            background: var(--warning-transparan);
            color: var(--warning-gelap);
            border: 1px solid var(--warning);
        }

        .badge-habis {
            background: var(--danger-transparan);
            color: var(--danger-gelap);
            border: 1px solid var(--danger);
        }

        /* Category badge */
        .badge-kategori {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info);
            border: 1px solid rgba(23, 162, 184, 0.2);
        }

        /* Quantity badge */
        .badge-jumlah {
            background: rgba(40, 167, 69, 0.1);
            color: var(--hijau);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        /* Table row hover */
        .table-hover tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.05);
            cursor: default;
        }

        /* Icon colors */
        .fa-tools, .fa-list, .fa-info-circle, .fa-user, .fa-arrow-left,
        .fa-check-circle, .fa-tag, .fa-barcode, .fa-times-circle,
        .fa-exclamation-triangle, .fa-user-circle, .fa-sign-out-alt {
            color: var(--hijau) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="../dashboard_peminjam.php">
                <i class="fas fa-user me-2"></i>
                <span>Panel Peminjam</span>
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <span class="nav-link me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                    <span class="badge-role ms-2">Peminjam</span>
                </span>
                <a class="nav-link" href="../logout.php" style="
                    color: var(--danger);
                    background: var(--danger-transparan);
                    padding: 8px 15px;
                    border-radius: 8px;
                    border: 1px solid var(--danger);
                    font-weight: 500;
                    transition: all 0.3s;
                ">
                    <i class="fas fa-sign-out-alt me-1"></i>Keluar
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-2" style="font-weight: 600; color: var(--hijau);">
                    <i class="fas fa-tools me-2"></i>Daftar Alat
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Daftar lengkap alat yang tersedia
                </p>
            </div>
            <a href="../dashboard_peminjam.php" class="btn-kembali">
                <i class="fas fa-arrow-left me-2"></i>Kembali Ke Dashboard
            </a>
        </div>

        <!-- Statistik Ringkas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-check-circle me-1"></i>Alat Tersedia
                                </h6>
                                <h3 class="mb-0" style="color: var(--hijau); font-weight: 600;">
                                    <?php
                                    $tersedia = 0;
                                    foreach ($alat_data as $item) {
                                        if ($item['jumlah_tersedia'] > 0) {
                                            $tersedia++;
                                        }
                                    }
                                    echo $tersedia;
                                    ?>
                                </h3>
                            </div>
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: var(--hijau-transparan);
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-check fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-tools me-1"></i>Total Alat
                                </h6>
                                <h3 class="mb-0" style="color: var(--hitam); font-weight: 600;">
                                    <?php echo count($alat_data); ?>
                                </h3>
                            </div>
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(33, 37, 41, 0.1);
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-tools fa-lg" style="color: var(--hitam);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-tags me-1"></i>Kategori
                                </h6>
                                <h3 class="mb-0" style="color: var(--info); font-weight: 600;">
                                    <?php
                                    $kategori = [];
                                    foreach ($alat_data as $item) {
                                        if (!empty($item['nama_kategori'])) {
                                            $kategori[$item['nama_kategori']] = true;
                                        }
                                    }
                                    echo count($kategori);
                                    ?>
                                </h3>
                            </div>
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(23, 162, 184, 0.1);
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-tags fa-lg" style="color: var(--info);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Alat -->
        <div class="main-card">
            <div class="card-header">
                <h5 class="mb-0" style="font-weight: 600; color: var(--hijau);">
                    <i class="fas fa-list me-2"></i>Daftar Lengkap Alat
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Kondisi</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            foreach ($alat_data as $item):
                                $jumlah = $item['jumlah_tersedia'];
                                
                                if ($jumlah > 5) {
                                    $status_class = 'badge-tersedia';
                                    $status_text = 'Tersedia';
                                    $status_icon = 'fa-check-circle';
                                } elseif ($jumlah > 0) {
                                    $status_class = 'badge-terbatas';
                                    $status_text = 'Terbatas';
                                    $status_icon = 'fa-exclamation-triangle';
                                } else {
                                    $status_class = 'badge-habis';
                                    $status_text = 'Habis';
                                    $status_icon = 'fa-times-circle';
                                }
                            ?>
                            <tr>
                                <td class="fw-bold text-muted"><?php echo $counter++; ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($item['nama_alat']); ?>
                                    </div>
                                    <?php if (!empty($item['kode_alat'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-barcode me-1"></i>
                                        <?php echo htmlspecialchars($item['kode_alat']); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-kategori">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($item['nama_kategori'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-jumlah">
                                        <?php echo $jumlah; ?> unit
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $kondisi_class = 'secondary';
                                    $kondisi_text = 'Tidak Diketahui';
                                    switch ($item['kondisi']) {
                                        case 'baik':
                                            $kondisi_class = 'success';
                                            $kondisi_text = 'Baik';
                                            break;
                                        case 'kurang_baik':
                                            $kondisi_class = 'warning';
                                            $kondisi_text = 'Kurang Baik';
                                            break;
                                        case 'rusak':
                                            $kondisi_class = 'danger';
                                            $kondisi_text = 'Rusak';
                                            break;
                                        case 'masih_bisa_digunakan':
                                            $kondisi_class = 'info';
                                            $kondisi_text = 'Masih Bisa Digunakan';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $kondisi_class; ?>">
                                        <?php echo $kondisi_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted" style="line-height: 1.4;">
                                        <?php
                                        $deskripsi = htmlspecialchars($item['deskripsi'] ?? 'Tidak ada deskripsi');
                                        if (strlen($deskripsi) > 60) {
                                            echo substr($deskripsi, 0, 60) . '...';
                                        } else {
                                            echo $deskripsi;
                                        }
                                        ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if ($counter == 1): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div style="
                                        width: 80px;
                                        height: 80px;
                                        background: var(--hijau-transparan);
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin: 0 auto 20px;
                                    ">
                                        <i class="fas fa-inbox fa-2x"></i>
                                    </div>
                                    <h5 style="color: var(--hitam); margin-bottom: 10px;">
                                        Belum ada alat tersedia
                                    </h5>
                                    <p class="text-muted" style="max-width: 400px; margin: 0 auto;">
                                        Tidak ada alat yang dapat ditampilkan saat ini.
                                    </p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Terakhir diperbarui: <?php echo date('d/m/Y H:i'); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-list-check me-1"></i>
                            Total: <?php echo $counter - 1; ?> alat
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efek hover untuk card statistik
        document.querySelectorAll('.card-stat').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Efek hover untuk baris tabel
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(40, 167, 69, 0.05)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>i