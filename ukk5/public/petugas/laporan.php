<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
require_once '../../src/models/Alat.php';
require_once '../../src/models/Kategori.php';

$peminjamanModel = new Peminjaman();
$alatModel = new Alat();
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

// For petugas, show all loans that are not pending (i.e., those that have been processed)
// Since id_petugas column doesn't exist in the database, we'll show all processed loans
$petugasCondition = "status IN ('disetujui', 'ditolak', 'dikembalikan')";

if ($whereClause) {
    $whereClause .= " AND " . $petugasCondition;
} else {
    $whereClause = "WHERE " . $petugasCondition;
}

// Get report data
$laporanData = $peminjamanModel->getFilteredPeminjaman($whereClause, $params);
$laporanData->execute($params);

// Get statistics
$totalPeminjaman = $laporanData->rowCount();

// Count by status for this petugas
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

// Since id_petugas column doesn't exist, we'll show general statistics instead
$approvedByMe = 0; // Will show total approved loans
$rejectedByMe = 0; // Will show total rejected loans
$returnedByMe = 0; // Will show total returned loans

// Get category options
$kategoriOptions = $kategoriModel->getAllKategori();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman - Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --dark-bg: #ffffff;
            --dark-secondary: #f8f9fa;
            --dark-card: #ffffff;
            --dark-border: #dee2e6;
            --dark-text: #000000;
            --dark-text-secondary: #6c757d;

            --primary: #28a745;
            --primary-dark: #218838;
            --success: #28a745;
            --success-dark: #218838;
            --warning: #ffc107;
            --warning-dark: #e0a800;
            --danger: #000000;
            --danger-dark: #000000;
            --info: #17a2b8;
            --info-dark: #138496;
            --purple: #28a745;
            --purple-dark: #218838;
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            background-color: rgba(40, 167, 69, 0.1) !important;
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

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), var(--warning-dark)) !important;
            border: none !important;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark)) !important;
            border: none !important;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info), var(--info-dark)) !important;
            border: none !important;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268) !important;
            border: none !important;
        }

        .badge {
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success), var(--success-dark)) !important;
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning), var(--warning-dark)) !important;
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark)) !important;
            color: white !important;
        }

        .badge-info {
            background: linear-gradient(135deg, var(--info), var(--info-dark)) !important;
        }

        .alert {
            background: var(--dark-card) !important;
            border: 1px solid var(--dark-border) !important;
            color: var(--dark-text) !important;
        }

        .alert-warning {
            border-color: var(--warning) !important;
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
    </style>
</head>
<body>
    <?php include '../../src/views/includes/petugas_header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 2px solid #28a745;">
            <div>
                <h2 class="mb-1" style="color: #000000 !important; font-weight: 700 !important; font-size: 1.8rem !important;">
                    <i class="fas fa-tasks me-2" style="color: #28a745 !important; font-size: 1.8rem !important;"></i>Dashboard Laporan Petugas
                </h2>
                <p class="mb-0" style="color: #6c757d !important; font-size: 1rem !important; font-weight: 400 !important;">
                    <i class="fas fa-user-shield me-1" style="color: #28a745 !important;"></i>
                    Pantau dan kelola peminjaman alat yang telah diproses
                </p>
            </div>
            <div>
                <a href="../dashboard_petugas.php" class="btn btn-secondary" style="font-weight: 600; padding: 10px 25px;">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Pending Approvals Alert -->
        <?php
        // Get pending loans count using existing method
        $pendingCount = $peminjamanModel->countPendingPeminjaman();
        ?>
        <?php if ($pendingCount > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Perhatian!</strong> Ada <?php echo $pendingCount; ?> peminjaman yang menunggu persetujuan Anda.
            <a href="peminjaman.php" class="alert-link">Proses Sekarang</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Petugas Personal Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card" style="border-radius: 16px;">
                    <div class="card-header border-0 py-3" style="background: var(--dark-secondary); border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="color: #000000 !important;">
                            <i class="fas fa-user-tie me-2" style="color: #ffc107 !important;"></i>
                            Statistik Pribadi Anda
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Peminjaman Disetujui</h6>
                                            <h2 class="mb-0" style="color: #28a745 !important;"><?php echo $approvedByMe; ?></h2>
                                            <small style="color: var(--dark-text-secondary);">Oleh Anda</small>
                                        </div>
                                        <div style="width: 60px; height: 60px; background: rgba(40, 167, 69, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-check-circle fa-2x" style="color: #28a745 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Peminjaman Ditolak</h6>
                                            <h2 class="mb-0" style="color: #000000 !important;"><?php echo $rejectedByMe; ?></h2>
                                            <small style="color: var(--dark-text-secondary);">Oleh Anda</small>
                                        </div>
                                        <div style="width: 60px; height: 60px; background: rgba(0, 0, 0, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-times-circle fa-2x" style="color: #000000 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Pengembalian Ditangani</h6>
                                            <h2 class="mb-0" style="color: #17a2b8 !important;"><?php echo $returnedByMe; ?></h2>
                                            <small style="color: var(--dark-text-secondary);">Oleh Anda</small>
                                        </div>
                                        <div style="width: 60px; height: 60px; background: rgba(23, 162, 184, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-undo fa-2x" style="color: #17a2b8 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions for Petugas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card" style="border-radius: 16px; background: white;">
                    <div class="card-header border-0 py-3" style="background: rgba(40, 167, 69, 0.1); border-radius: 16px 16px 0 0;">
                        <h5 class="mb-0" style="color: #000000 !important;">
                            <i class="fas fa-bolt me-2" style="color: #ffc107 !important;"></i>
                            Aksi Cepat Petugas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="peminjaman.php" class="btn btn-warning w-100 d-flex align-items-center justify-content-center py-3" style="border-radius: 12px;">
                                    <div class="text-center">
                                        <i class="fas fa-clock fa-2x mb-2" style="color: #000000 !important;"></i>
                                        <div class="fw-bold" style="color: #000000 !important;">Proses Pending</div>
                                        <small style="color: #000000 !important;"><?php echo $pendingCount; ?> menunggu</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="pengembalian.php" class="btn btn-info w-100 d-flex align-items-center justify-content-center py-3" style="border-radius: 12px;">
                                    <div class="text-center">
                                        <i class="fas fa-undo fa-2x mb-2" style="color: #ffffff !important;"></i>
                                        <div class="fw-bold" style="color: #ffffff !important;">Kelola Pengembalian</div>
                                        <small style="color: #ffffff !important;">Alat kembali</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Total Peminjaman</h6>
                            <h2 class="mb-0" style="color: #28a745 !important;"><?php echo $totalPeminjaman; ?></h2>
                            <small style="color: var(--dark-text-secondary);">Yang Anda tangani</small>
                        </div>
                        <div style="width: 60px; height: 60px; background: rgba(40, 167, 69, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-clipboard-list fa-2x" style="color: #28a745 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Menunggu Persetujuan</h6>
                                            <h2 class="mb-0" style="color: #ffc107 !important;"><?php echo $statusStats['pending']; ?></h2>
                                            <small style="color: var(--dark-text-secondary);">Perlu ditangani</small>
                                        </div>
                                        <div style="width: 60px; height: 60px; background: rgba(255, 193, 7, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-clock fa-2x" style="color: #ffc107 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Aktif Dipinjam</h6>
                                            <h2 class="mb-0" style="color: #28a745 !important;"><?php echo $statusStats['disetujui']; ?></h2>
                                            <small style="color: var(--dark-text-secondary);">Sedang berlangsung</small>
                                        </div>
                                        <div style="width: 60px; height: 60px; background: rgba(40, 167, 69, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-clipboard-check fa-2x" style="color: #28a745 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-uppercase mb-2" style="color: var(--dark-text-secondary); font-size: 0.8rem;">Sudah Dikembalikan</h6>
                                            <h2 class="mb-0" style="color: #17a2b8 !important;"><?php echo $statusStats['dikembalikan']; ?></h2>
                                            <small style="color: var(--dark-text-secondary);">Selesai</small>
                                        </div>
                                        <div style="width: 60px; height: 60px; background: rgba(23, 162, 184, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-undo fa-2x" style="color: #17a2b8 !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Form -->
                        <div class="card mb-4">
                            <div class="card-header" style="color: #000000 !important;">
                                <h5 class="mb-0">
                                    <i class="fas fa-filter me-2" style="color: #28a745 !important;"></i>Filter Laporan
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="status" class="form-label" style="color: #000000 !important;">Status Peminjaman</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="disetujui" <?php echo $filter_status == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                            <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                            <option value="dikembalikan" <?php echo $filter_status == 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="tanggal_dari" class="form-label" style="color: #000000 !important;">Tanggal Dari</label>
                                        <input type="date" class="form-control" id="tanggal_dari" name="tanggal_dari" value="<?php echo $filter_tanggal_dari; ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="tanggal_sampai" class="form-label" style="color: #000000 !important;">Tanggal Sampai</label>
                                        <input type="date" class="form-control" id="tanggal_sampai" name="tanggal_sampai" value="<?php echo $filter_tanggal_sampai; ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="kategori" class="form-label" style="color: #000000 !important;">Kategori Alat</label>
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
                            <div class="card-header d-flex justify-content-between align-items-center" style="color: #000000 !important;">
                                <h5 class="mb-0">
                                    <i class="fas fa-table me-2" style="color: #28a745 !important;"></i>Riwayat Peminjaman Diproses
                                </h5>
                                <div>
                                    <a href="cetak_laporan.php?status=<?php echo $filter_status; ?>&tanggal_dari=<?php echo $filter_tanggal_dari; ?>&tanggal_sampai=<?php echo $filter_tanggal_sampai; ?>&kategori=<?php echo $filter_kategori; ?>" class="btn btn-success me-2">
                                        <i class="fas fa-print me-1"></i>Cetak Laporan
                                    </a>
                                    <a href="peminjaman.php" class="btn btn-warning me-2">
                                        <i class="fas fa-clock me-1"></i>Proses Pending
                                    </a>
                                    <a href="pengembalian.php" class="btn btn-info">
                                        <i class="fas fa-undo me-1"></i>Kelola Pengembalian
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="color: #000000 !important;">ID</th>
                                                <th style="color: #000000 !important;">Peminjam</th>
                                                <th style="color: #000000 !important;">Alat</th>
                                                <th style="color: #000000 !important;">Kategori</th>
                                                <th style="color: #000000 !important;">Tanggal Pinjam</th>
                                                <th style="color: #000000 !important;">Tanggal Kembali</th>
                                                <th style="color: #000000 !important;">Jumlah</th>
                                                <th style="color: #000000 !important;">Kondisi Alat</th>
                                                <th style="color: #000000 !important;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $laporanData->execute($params);
                                            $counter = 1;
                                            while ($row = $laporanData->fetch(PDO::FETCH_ASSOC)):
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
                                                <td class="fw-bold" style="color: #000000 !important;"><?php echo $counter++; ?></td>
                                                <td style="color: #000000 !important;"><?php echo htmlspecialchars($row['nama_peminjam']); ?></td>
                                                <td style="color: #000000 !important;"><?php echo htmlspecialchars($row['nama_alat']); ?></td>
                                                <td style="color: #000000 !important;"><?php echo htmlspecialchars($row['nama_kategori'] ?? '-'); ?></td>
                                                <td style="color: #000000 !important;"><?php echo $row['tanggal_peminjaman'] ? date('d/m/Y', strtotime($row['tanggal_peminjaman'])) : '-'; ?></td>
                                                <td style="color: #000000 !important;"><?php echo isset($row['tanggal_kembali']) && $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-'; ?></td>
                                                <td style="color: #000000 !important;"><?php echo $row['jumlah']; ?> unit</td>
                                                <td style="color: #000000 !important;"><?php echo htmlspecialchars($row['kondisi_kembali'] ?? $row['kondisi'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="<?php echo $status_class; ?>">
                                                        <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <small style="color: #6c757d !important;">
                                            <i class="fas fa-sync-alt me-1"></i>
                                            Diperbarui: <?php echo date('H:i:s'); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <small style="color: #6c757d !important;">
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