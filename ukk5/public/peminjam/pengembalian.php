<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peminjam') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
require_once '../../src/models/Alat.php';

$user_id = $_SESSION['user_id'];
$peminjamanModel = new Peminjaman();
$alatModel = new Alat();

// Log all requests
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST DATA: " . json_encode($_POST));
error_log("GET DATA: " . json_encode($_GET));
error_log("SESSION USER ID: $user_id");

$message = '';
$message_type = '';

// Handle success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Pengembalian alat berhasil! Stok alat telah dikembalikan.';
    $message_type = 'success';
} elseif (isset($_GET['success_pinjam']) && $_GET['success_pinjam'] == '1') {
    $message = 'Peminjaman berhasil! Alat siap untuk dikembalikan.';
    $message_type = 'success';
}

// Get peminjaman aktif yang sedang dipinjam oleh user ini
$peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
$peminjamanAktifData = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);

error_log("ACTIVE LOANS LOADED - User: $user_id, Count: " . count($peminjamanAktifData));
if (count($peminjamanAktifData) > 0) {
    foreach ($peminjamanAktifData as $loan) {
        error_log("Active loan - ID: " . $loan['id_peminjaman'] . ", Alat: " . $loan['nama_alat'] . ", Status: " . $loan['status']);
    }
}

// Get riwayat pengembalian user ini
$riwayatPengembalian = $peminjamanModel->getRiwayatPengembalianByUser($user_id);
$riwayatPengembalianData = $riwayatPengembalian->fetchAll(PDO::FETCH_ASSOC);

// Handle pengembalian alat
// Accept either a submit with name="kembalikan" or a direct POST containing `id_peminjaman`
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['kembalikan']) || isset($_POST['id_peminjaman']))) {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $kondisi_alat = $_POST['kondisi_alat'] ?? '';
    $catatan = $_POST['catatan'] ?? '';

    error_log("POST RECEIVED - ID: $id_peminjaman, Kondisi: $kondisi_alat, Catatan: $catatan, User: $user_id");
    error_log("SESSION DATA - User ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role']);
    error_log("POST DATA: " . json_encode($_POST));

    if ($id_peminjaman && !empty($kondisi_alat)) {
        try {
            error_log("CALLING kembalikanAlatLangsung...");
            $result = $peminjamanModel->kembalikanAlatLangsung($id_peminjaman, $kondisi_alat, $catatan, $user_id);
            error_log("RESULT: " . json_encode($result));

            if ($result['success']) {
                error_log("SUCCESS - Setting message and refreshing data");

                $message = $result['message'];
                $message_type = 'success';

                // Refresh data
                $peminjamanAktif = $peminjamanModel->getPeminjamanAktifByUser($user_id);
                $peminjamanAktifData = $peminjamanAktif->fetchAll(PDO::FETCH_ASSOC);
                $riwayatPengembalian = $peminjamanModel->getRiwayatPengembalianByUser($user_id);
                $riwayatPengembalianData = $riwayatPengembalian->fetchAll(PDO::FETCH_ASSOC);

                error_log("DATA REFRESHED - Active loans: " . count($peminjamanAktifData));

                // Redirect to avoid resubmission
                $redirect_url = $_SERVER['PHP_SELF'] . "?success=1&t=" . time();
                error_log("REDIRECTING TO: $redirect_url");
                header("Location: $redirect_url");
                exit();
            } else {
                error_log("FAILED - Message: " . $result['message']);
                $message = $result['message'];
                $message_type = 'danger';
            }

        } catch (Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        error_log("VALIDATION FAILED - ID: $id_peminjaman, Kondisi: $kondisi_alat");
        $message = 'Semua field harus diisi.';
        $message_type = 'danger';
    }
}

// Get total stats
$totalAktif = count($peminjamanAktifData);
$totalRiwayat = count($riwayatPengembalianData);

// Get yang sudah jatuh tempo
$today = date('Y-m-d');
$jatuhTempo = 0;
foreach ($peminjamanAktifData as $peminjaman) {
    if ($peminjaman['tanggal_pengembalian'] && $peminjaman['tanggal_pengembalian'] <= $today) {
        $jatuhTempo++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Alat - Peminjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama */
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
        }

        /* Reset dan Body */
        body {
            background-color: var(--abu);
            color: var(--hitam);
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
        }

        /* Utility Classes */
        .text-hijau { color: var(--hijau) !important; }
        .bg-hijau { background-color: var(--hijau) !important; }
        .border-hijau { border-color: var(--hijau) !important; }

        .text-hitam { color: var(--hitam) !important; }
        .bg-hitam { background-color: var(--hitam) !important; }

        .text-abu { color: var(--abu-text) !important; }
        .bg-abu { background-color: var(--abu) !important; }

        /* Header */
        .navbar {
            background-color: var(--putih);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Cards */
        .card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background-color: var(--putih);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--putih);
            border-bottom: 1px solid var(--border);
            padding: 15px 20px;
            font-weight: 600;
        }

        /* Tables */
        .table {
            background-color: var(--putih);
            border: 1px solid var(--border);
        }

        .table thead th {
            background-color: var(--abu);
            border-bottom: 2px solid var(--border);
            color: var(--hitam);
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: var(--abu-gelap);
        }

        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .badge-hijau { background-color: var(--hijau-muda); color: var(--hijau-gelap); }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }

        /* Buttons */
        .btn-hijau {
            background-color: var(--hijau);
            border-color: var(--hijau);
            color: var(--putih);
        }

        .btn-hijau:hover {
            background-color: var(--hijau-gelap);
            border-color: var(--hijau-gelap);
            color: var(--putih);
        }

        .btn-outline-hijau {
            color: var(--hijau);
            border-color: var(--hijau);
            background-color: transparent;
        }

        .btn-outline-hijau:hover {
            background-color: var(--hijau);
            color: var(--putih);
        }

        /* Stat Cards */
        .stat-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background-color: var(--putih);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Modal */
        .modal-content {
            border: 1px solid var(--border);
            background-color: var(--putih);
        }

        .modal-header {
            background-color: var(--abu);
            border-bottom: 1px solid var(--border);
        }

        .modal-footer {
            border-top: 1px solid var(--border);
        }

        /* Form Controls */
        .form-control, .form-select {
            border: 1px solid var(--border);
            border-radius: 4px;
            background-color: var(--putih);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--hijau);
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }

        /* Alerts */
        .alert {
            border: 1px solid;
            border-radius: 4px;
        }

        .alert-success {
            background-color: var(--hijau-muda);
            border-color: #c3e6cb;
            color: var(--hijau-gelap);
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid var(--border);
        }

        .nav-tabs .nav-link {
            color: var(--abu-text);
            border: 1px solid transparent;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }

        .nav-tabs .nav-link:hover {
            border-color: var(--abu-gelap);
            color: var(--hijau);
        }

        .nav-tabs .nav-link.active {
            color: var(--hijau);
            background-color: var(--putih);
            border-color: var(--border) var(--border) transparent;
            border-bottom: 3px solid var(--hijau);
        }

        /* Custom Styles */
        .btn-back {
            background-color: var(--putih);
            border: 1px solid var(--hijau);
            color: var(--hijau);
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background-color: var(--hijau);
            color: var(--putih);
        }

        .btn-kembalikan {
            background-color: var(--hijau);
            color: var(--putih);
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .btn-kembalikan:hover {
            background-color: var(--hijau-gelap);
            color: var(--putih);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--abu);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--abu-text);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--hijau);
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
                    <span class="badge bg-success ms-2">Peminjam</span>
                </span>
                <a class="btn btn-outline-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-2" style="font-weight: 600; color: var(--hijau);">
                    <i class="fas fa-paper-plane me-2"></i>Pengembalian Alat
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    pengembalian alat yang sedang Anda pinjam
                </p>
            </div>
            <a href="../dashboard_peminjam.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>

        <?php if (isset($_POST['kembalikan'])): ?>
        <div class="alert alert-info mb-4">
            <strong>Debug Info:</strong> Form submitted<br>
            ID Peminjaman: <?php echo $_POST['id_peminjaman'] ?? 'null'; ?><br>
            Kondisi Alat: <?php echo $_POST['kondisi_alat'] ?? 'empty'; ?><br>
            Catatan: <?php echo $_POST['catatan'] ?? 'empty'; ?>
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-clipboard-check me-1"></i>Sedang Dipinjam
                                </h6>
                                <h3 class="mb-0" style="color: var(--hijau); font-weight: 600;">
                                    <?php echo $totalAktif; ?>
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
                                <i class="fas fa-tools fa-lg text-hijau"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Jatuh Tempo
                                </h6>
                                <h3 class="mb-0" style="color: #ffc107; font-weight: 600;">
                                    <?php echo $jatuhTempo; ?>
                                </h3>
                            </div>
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(255, 193, 7, 0.1);
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-clock fa-lg" style="color: #ffc107;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-history me-1"></i>Sudah Dikembalikan
                                </h6>
                                <h3 class="mb-0" style="color: var(--hijau); font-weight: 600;">
                                    <?php echo $totalRiwayat; ?>
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
                                <i class="fas fa-check fa-lg text-hijau"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" style="font-weight: 600; color: var(--hijau);">
                    <i class="fas fa-tasks me-2"></i>Kelola Pengajuan Pengembalian
                </h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="aktif-tab" data-bs-toggle="tab" data-bs-target="#aktif" type="button" role="tab">
                            <i class="fas fa-clock me-2"></i>Sedang Dipinjam (<?php echo $totalAktif; ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat" type="button" role="tab">
                            <i class="fas fa-history me-2"></i>Riwayat Pengajuan (<?php echo $totalRiwayat; ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <!-- Tab Sedang Dipinjam -->
                    <div class="tab-pane fade show active" id="aktif" role="tabpanel">
                        <?php if ($totalAktif > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Alat</th>
                                            <th>Tanggal Pinjam</th>
                                            <th>Tanggal Kembali</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($peminjamanAktifData as $peminjaman): 
                                            $isLate = false;
                                            if ($peminjaman['tanggal_pengembalian'] && $peminjaman['tanggal_pengembalian'] < date('Y-m-d')) {
                                                $isLate = true;
                                            }
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $counter++; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($peminjaman['nama_alat']); ?></div>
                                                <?php if (!empty($peminjaman['kode_alat'])): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-barcode me-1"></i>
                                                    <?php echo htmlspecialchars($peminjaman['kode_alat']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_peminjaman'])); ?>
                                            </td>
                                            <td>
                                                <?php if ($peminjaman['tanggal_pengembalian']): ?>
                                                    <span class="<?php echo $isLate ? 'text-danger' : 'text-hijau'; ?>">
                                                        <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pengembalian'])); ?>
                                                    </span>
                                                    <?php if ($isLate): ?>
                                                    <br><small class="text-danger">
                                                        <i class="fas fa-exclamation-circle me-1"></i>Terlambat
                                                    </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-hijau">
                                                    <?php echo $peminjaman['jumlah']; ?> unit
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge bg-hijau text-white">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Disetujui
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-kembalikan" data-bs-toggle="modal" data-bs-target="#kembalikanModal" 
                                                        data-id="<?php echo $peminjaman['id_peminjaman']; ?>"
                                                        data-nama="<?php echo htmlspecialchars($peminjaman['nama_alat']); ?>"
                                                        data-jumlah="<?php echo $peminjaman['jumlah']; ?>">
                                                    <i class="fas fa-paper-plane me-1"></i>Kembalikan
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
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
                                    <i class="fas fa-inbox fa-2x text-hijau"></i>
                                </div>
                                <h5 style="color: var(--hitam); margin-bottom: 10px;">
                                    Tidak ada alat yang sedang dipinjam
                                </h5>
                                <p class="text-muted" style="max-width: 400px; margin: 0 auto;">
                                    Anda tidak memiliki alat yang sedang dipinjam saat ini.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab Riwayat -->
                    <div class="tab-pane fade" id="riwayat" role="tabpanel">
                        <?php if ($totalRiwayat > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Alat</th>
                                            <th>Tanggal Pinjam</th>
                                            <th>Tanggal Kembali</th>
                                            <th>Jumlah</th>
                                            <th>Kondisi</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $counterRiwayat = 1; ?>
                                        <?php foreach ($riwayatPengembalianData as $riwayat): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $counterRiwayat++; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($riwayat['nama_alat']); ?></div>
                                                <?php if (!empty($riwayat['kode_alat'])): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-barcode me-1"></i>
                                                    <?php echo htmlspecialchars($riwayat['kode_alat']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($riwayat['tanggal_peminjaman'])); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $tanggalKembali = $riwayat['tanggal_pengembalian_aktual'] ?? $riwayat['tanggal_dikembalikan'];
                                                if ($tanggalKembali): ?>
                                                    <span class="text-hijau">
                                                        <?php echo date('d/m/Y', strtotime($tanggalKembali)); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-hijau">
                                                    <?php echo $riwayat['jumlah']; ?> unit
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $riwayat['status'];
                                                if ($status == 'menunggu_konfirmasi') {
                                                    echo '<span class="text-muted">-</span>';
                                                } else {
                                                    $kondisi = $riwayat['kondisi_kembali'] ?? 'baik';
                                                    if ($kondisi == 'rusak_ringan') {
                                                        $badgeClass = 'badge-warning';
                                                        $icon = 'exclamation-triangle';
                                                    } elseif ($kondisi == 'rusak_berat') {
                                                        $badgeClass = 'badge-danger';
                                                        $icon = 'times-circle';
                                                    } else {
                                                        $badgeClass = 'badge-hijau';
                                                        $icon = 'check-circle';
                                                    }
                                                    echo '<span class="badge ' . $badgeClass . '">';
                                                    echo '<i class="fas fa-' . $icon . ' me-1"></i>';
                                                    echo $kondisi == 'baik' ? 'Baik' : ($kondisi == 'rusak_ringan' ? 'Rusak Ringan' : 'Rusak Berat');
                                                    echo '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $riwayat['status'];
                                                if ($status == 'menunggu_konfirmasi') {
                                                    $statusClass = 'bg-warning text-dark';
                                                    $statusIcon = 'clock';
                                                    $statusText = 'Menunggu Konfirmasi';
                                                } elseif ($status == 'dikembalikan') {
                                                    $statusClass = 'bg-hijau text-white';
                                                    $statusIcon = 'check';
                                                    $statusText = 'Dikembalikan';
                                                } else {
                                                    $statusClass = 'bg-secondary text-white';
                                                    $statusIcon = 'question';
                                                    $statusText = ucfirst($status);
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
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
                                    <i class="fas fa-history fa-2x text-hijau"></i>
                                </div>
                                <h5 style="color: var(--hitam); margin-bottom: 10px;">
                                    Belum ada riwayat pengajuan
                                </h5>
                                <p class="text-muted" style="max-width: 400px; margin: 0 auto;">
                                    Anda belum pernah mengajukan pengembalian alat. Riwayat pengajuan akan muncul di sini.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <span>Terakhir diperbarui: <?php echo date('d/m/Y H:i'); ?></span>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-list-check me-1"></i>
                            <span>Total: <?php echo $totalAktif + $totalRiwayat; ?> data</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kembalikan Alat -->
    <div class="modal fade" id="kembalikanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-hijau text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2"></i>Form Pengembalian Alat
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formKembalikan">
                    <div class="modal-body">
                        <input type="hidden" name="id_peminjaman" id="modalIdPeminjaman">
                        
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-3"></i>
                                    <div>
                                        <h6 class="mb-2 fw-bold">Informasi Alat</h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="d-block">Nama Alat:</small>
                                                <strong id="modalNamaAlat">-</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="d-block">Jumlah:</small>
                                                <strong id="modalJumlahAlat">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="kondisi_alat" class="form-label fw-bold">
                                <i class="fas fa-clipboard-check me-2"></i>Kondisi Alat Saat Dikembalikan
                            </label>
                            <select class="form-select" id="kondisi_alat" name="kondisi_alat" required>
                                <option value="">Pilih kondisi alat</option>
                                <option value="baik">Baik (Seperti saat dipinjam)</option>
                                <option value="rusak_ringan">Rusak Ringan (Masih bisa digunakan)</option>
                                <option value="rusak_berat">Rusak Berat (Tidak bisa digunakan)</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Pilih kondisi alat sesuai keadaan saat dikembalikan
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="catatan" class="form-label fw-bold">
                                <i class="fas fa-sticky-note me-2"></i>Catatan Pengembalian
                            </label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3" 
                                      placeholder="Opsional: Jelaskan kondisi alat lebih detail atau berikan catatan lainnya..."></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Catatan akan dicatat dalam sistem untuk dokumentasi
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3"></i>
                                <div>
                                    <h6 class="mb-2 fw-bold">Perhatian!</h6>
                                    <p class="mb-0 small">
                                        Dengan mengajukan pengembalian alat, Anda menyatakan bahwa:
                                    </p>
                                    <ul class="mb-0 small">
                                        <li>Alat dikembalikan sesuai kondisi yang dipilih</li>
                                        <li>Anda bertanggung jawab atas kondisi alat</li>
                                        <li>Data pengembalian akan diajukan ke petugas untuk konfirmasi</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-hijau" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="submit" name="kembalikan" class="btn btn-hijau">
                            <i class="fas fa-paper-plane me-2"></i>Konfirmasi Pengembalian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle modal show event
        const kembalikanModal = document.getElementById('kembalikanModal');
        if (kembalikanModal) {
            kembalikanModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const idPeminjaman = button.getAttribute('data-id');
                const namaAlat = button.getAttribute('data-nama');
                const jumlahAlat = button.getAttribute('data-jumlah');
                
                console.log('Modal show:', {idPeminjaman, namaAlat, jumlahAlat});
                
                // Update modal content
                document.getElementById('modalIdPeminjaman').value = idPeminjaman;
                document.getElementById('modalNamaAlat').textContent = namaAlat;
                document.getElementById('modalJumlahAlat').textContent = jumlahAlat + ' unit';
            });
        }

        // Form validation
        document.getElementById('formKembalikan').addEventListener('submit', function(e) {
            const kondisi = document.getElementById('kondisi_alat').value;
            const id = document.getElementById('modalIdPeminjaman').value;
            
            console.log('Form submit:', {id, kondisi});
            
            if (!kondisi) {
                e.preventDefault();
                alert('Silakan pilih kondisi alat terlebih dahulu.');
                document.getElementById('kondisi_alat').focus();
                return false;
            }
            
            if (!id) {
                e.preventDefault();
                alert('ID peminjaman tidak ditemukan.');
                return false;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                submitBtn.disabled = true;
            }
            
            return true;
        });

        // Tab functionality
        const tabEl = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEl.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                // You can add additional functionality here if needed
            });
        });

        // Auto focus on kondisi alat when modal opens
        kembalikanModal.addEventListener('shown.bs.modal', function () {
            document.getElementById('kondisi_alat').focus();
        });
    </script>
</body>
</html>