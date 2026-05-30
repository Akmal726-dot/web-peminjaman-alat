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
$laporanData->execute($params);

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
$statusStatsStmt->execute($params);
while ($row = $statusStatsStmt->fetch(PDO::FETCH_ASSOC)) {
    $statusStats[$row['status']] = $row['jumlah'];
}

// Get category options
$kategoriOptions = $kategoriModel->getAllKategori();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --dark-bg: #0f172a;
            --dark-secondary: #1e293b;
            --dark-card: #334155;
            --dark-border: #475569;
            --dark-text: #e2e8f0;
            --dark-text-secondary: #94a3b8;

            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --success-dark: #059669;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --info: #06b6d4;
            --info-dark: #0891b2;
            --purple: #8b5cf6;
            --purple-dark: #7c3aed;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .stat-card {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .card {
            background: var(--dark-card) !important;
            border: 1px solid var(--dark-border) !important;
        }

        .table {
            color: var(--dark-text) !important;
            border-color: var(--dark-border) !important;
        }

        .table thead th {
            background-color: var(--dark-secondary) !important;
            border-color: var(--dark-border) !important;
            color: var(--primary) !important;
        }

        .table tbody tr {
            border-color: var(--dark-border) !important;
        }

        .table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }

        .form-control, .form-select {
            background-color: var(--dark-secondary) !important;
            border: 1px solid var(--dark-border) !important;
            color: var(--dark-text) !important;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--dark-secondary) !important;
            border-color: var(--primary) !important;
            color: var(--dark-text) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            border: none !important;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), var(--success-dark)) !important;
            border: none !important;
        }

        .badge {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/src/views/includes/admin_header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-chart-bar me-2" style="color: var(--primary);"></i>Laporan Peminjaman
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Laporan lengkap peminjaman alat dengan filter dan statistik
                </p>
            </div>
            <div>
                <a href="../dashboard_admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Total Peminjaman</h6>
                            <h2 class="mb-0" style="color: var(--primary);"><?php echo $totalPeminjaman; ?></h2>
                        </div>
                        <div style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clipboard-list fa-2x" style="color: var(--primary);"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Disetujui</h6>
                            <h2 class="mb-0" style="color: var(--success);"><?php echo $statusStats['disetujui']; ?></h2>
                        </div>
                        <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check-circle fa-2x" style="color: var(--success);"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Menunggu</h6>
                            <h2 class="mb-0" style="color: var(--warning);"><?php echo $statusStats['pending']; ?></h2>
                        </div>
                        <div style="width: 60px; height: 60px; background: rgba(245, 158, 11, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clock fa-2x" style="color: var(--warning);"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Dikembalikan</h6>
                            <h2 class="mb-0" style="color: var(--info);"><?php echo $statusStats['dikembalikan']; ?></h2>
                        </div>
                        <div style="width: 60px; height: 60px; background: rgba(6, 182, 212, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-undo fa-2x" style="color: var(--info);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2" style="color: var(--primary);"></i>Filter Laporan
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status Peminjaman</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="disetujui" <?php echo $filter_status == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            <option value="dikembalikan" <?php echo $filter_status == 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="tanggal_dari" class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" id="tanggal_dari" name="tanggal_dari" value="<?php echo $filter_tanggal_dari; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="tanggal_sampai" class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" id="tanggal_sampai" name="tanggal_sampai" value="<?php echo $filter_tanggal_sampai; ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="kategori" class="form-label">Kategori Alat</label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="all">Semua Kategori</option>
                            <?php
                            $kategoriOptions->execute();
                            while ($kategori = $kategoriOptions->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <option value="<?php echo $kategori['id_kategori']; ?>" <?php echo $filter_kategori == $kategori['id_kategori'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="laporan.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2" style="color: var(--primary);"></i>Data Laporan
                </h5>
                <a href="cetak_laporan.php?status=<?php echo $filter_status; ?>&tanggal_dari=<?php echo $filter_tanggal_dari; ?>&tanggal_sampai=<?php echo $filter_tanggal_sampai; ?>&kategori=<?php echo $filter_kategori; ?>" class="btn btn-success">
                    <i class="fas fa-print me-1"></i>Cetak Laporan
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
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
                            while ($row = $laporanData->fetch(PDO::FETCH_ASSOC)):
                                $status_class = '';
                                $status_icon = '';
                                switch($row['status_peminjaman']) {
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
                                <td class="fw-bold">#<?php echo $row['id_peminjaman']; ?></td>
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
                            <?php endwhile; ?>

                            <?php if ($totalPeminjaman == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-inbox fa-3x mb-3" style="color: var(--dark-text-secondary);"></i>
                                        <h5 class="mb-2">Tidak ada data laporan</h5>
                                        <p class="text-muted mb-0">Belum ada data peminjaman yang sesuai dengan filter</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small>
                            <i class="fas fa-sync-alt me-1"></i>
                            Diperbarui: <?php echo date('H:i:s'); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small>
                            Total data: <?php echo $totalPeminjaman; ?> peminjaman
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print functionality
        function printReport() {
            window.print();
        }

        // Auto refresh data every 60 seconds
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
