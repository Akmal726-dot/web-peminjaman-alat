<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Bukti.php';
require_once '../../src/models/User.php';

$buktiModel = new Bukti();
$userModel = new User();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $result = $buktiModel->deleteBukti($id);
        if ($result) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Bukti berhasil dihapus.'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Gagal menghapus bukti.'
            ];
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Tampilkan flash message
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}

$allBukti = $buktiModel->getAllBukti();
$totalBukti = $buktiModel->countAllBukti();

// Get bukti by type statistics
$pengembalianBukti = $buktiModel->getBuktiByTipe('pengembalian');
$peminjamanBukti = $buktiModel->getBuktiByTipe('peminjaman');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Bukti - Petugas</title>
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
            --danger: #dc3545;
            --danger-dark: #c82333;
            --info: #17a2b8;
            --info-dark: #138496;
            --purple: #6f42c1;
            --purple-dark: #5a359a;
            --ungu: #6f42c1;
            --ungu-transparan: rgba(111, 66, 193, 0.1);
        }

        body {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .bukti-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .bukti-preview:hover {
            transform: scale(1.05);
        }

        .card {
            background: var(--dark-card) !important;
            border: 1px solid var(--dark-border) !important;
            border-radius: 12px !important;
        }

        .table {
            color: var(--dark-text) !important;
            border-color: var(--dark-border) !important;
        }

        .table thead th {
            background-color: var(--dark-secondary) !important;
            border-color: var(--dark-border) !important;
            color: var(--primary) !important;
            font-weight: 600;
            padding: 1rem !important;
        }

        .table tbody tr {
            border-color: var(--dark-border) !important;
        }

        .table tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }

        .table tbody td {
            border-color: var(--dark-border) !important;
            padding: 1rem !important;
            vertical-align: middle;
        }

        .modal-content {
            background: var(--dark-card) !important;
            color: var(--dark-text) !important;
            border: 1px solid var(--dark-border) !important;
        }

        .modal-header {
            border-bottom: 1px solid var(--dark-border) !important;
            background: var(--dark-secondary) !important;
        }

        .modal-footer {
            border-top: 1px solid var(--dark-border) !important;
        }

        .badge {
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.5em 1em !important;
        }

        .stat-card {
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-ungu {
            background-color: var(--ungu-transparan);
            color: var(--ungu);
        }

        .btn-ungu {
            background: linear-gradient(135deg, var(--ungu), var(--ungu-dark)) !important;
            border: none !important;
            color: white !important;
        }

        .btn-ungu:hover {
            background: linear-gradient(135deg, var(--ungu-dark), var(--purple)) !important;
        }

        .text-ungu {
            color: var(--ungu) !important;
        }

        .action-btn {
            transition: all 0.3s ease;
            margin: 0 2px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .filter-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .alert {
            background: var(--dark-card) !important;
            border: 1px solid var(--dark-border) !important;
            color: var(--dark-text) !important;
        }

        .alert-success {
            border-color: var(--success) !important;
        }

        .alert-danger {
            border-color: var(--danger) !important;
        }

        .text-muted {
            color: var(--dark-text-secondary) !important;
        }

        .border {
            border-color: var(--dark-border) !important;
        }

        h2 {
            color: var(--dark-text) !important;
            font-weight: 600;
        }

        .card-header {
            background-color: var(--dark-secondary) !important;
            border-bottom: 1px solid var(--dark-border) !important;
            padding: 1rem 1.5rem !important;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .container {
            padding-top: 1rem;
        }

        .badge-pengembalian {
            background: linear-gradient(135deg, var(--info), var(--info-dark)) !important;
        }

        .badge-peminjaman {
            background: linear-gradient(135deg, var(--warning), var(--warning-dark)) !important;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
        }

        .fa-images, .fa-file-image, .fa-eye, .fa-trash, .fa-check-circle,
        .fa-exclamation-triangle, .fa-question-circle, .fa-comment-alt,
        .fa-spinner, .fa-chart-bar, .fa-table, .fa-info-circle, .fa-filter,
        .fa-list, .fa-search, .fa-arrow-left, .fa-sync-alt, .fa-inbox,
        .fa-money-bill-wave, .fa-tools, .fa-handshake, .fa-toolbox,
        .fa-users, .fa-home, .fa-sign-out-alt, .fa-print, .fa-file-invoice {
            color: var(--primary) !important;
        }

        .text-warning {
            color: var(--warning) !important;
        }

        .text-success {
            color: var(--success) !important;
        }

        .text-danger {
            color: var(--danger) !important;
        }

        .text-info {
            color: var(--info) !important;
        }

        .text-ungu {
            color: var(--ungu) !important;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--info), var(--info-dark)) !important;
            border: none !important;
            color: white !important;
        }

        .btn-print:hover {
            background: linear-gradient(135deg, var(--info-dark), #0d6efd) !important;
        }

        .btn-invoice {
            background: linear-gradient(135deg, var(--success), var(--success-dark)) !important;
            border: none !important;
            color: white !important;
        }

        .btn-invoice:hover {
            background: linear-gradient(135deg, var(--success-dark), #198754) !important;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/src/views/includes/petugas_header.php'; ?>

    <div class="container mt-4">
        <?php if ($flash_message): ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $flash_message['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-file-image me-2"></i>Management Bukti
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Kelola bukti upload dari peminjam dan pengembalian alat
                </p>
            </div>
            <div>
                <a href="../dashboard_petugas.php" class="btn btn-secondary action-btn">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-ungu">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total Bukti</h6>
                                <h3 class="mb-0"><?php echo $totalBukti; ?></h3>
                            </div>
                            <div class="stat-icon stat-ungu">
                                <i class="fas fa-file-image"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Bukti Pengembalian</h6>
                                <h3 class="mb-0"><?php
                                    $countPengembalian = 0;
                                    $pengembalianBukti->execute();
                                    while ($pengembalianBukti->fetch()) {
                                        $countPengembalian++;
                                    }
                                    echo $countPengembalian;
                                ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                                <i class="fas fa-undo"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Bukti Peminjaman</h6>
                                <h3 class="mb-0"><?php
                                    $countPeminjaman = 0;
                                    $peminjamanBukti->execute();
                                    while ($peminjamanBukti->fetch()) {
                                        $countPeminjaman++;
                                    }
                                    echo $countPeminjaman;
                                ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-handshake"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Cetak Struk</h6>
                                <h3 class="mb-0"><?php echo $totalBukti; ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                                <i class="fas fa-print"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Tipe Bukti</h6>
            </div>
            <div class="card-body">
                <div class="filter-buttons">
                    <button class="btn btn-outline-ungu filter-btn active" data-tipe="semua">
                        <i class="fas fa-list me-1"></i>Semua Bukti (<?php echo $totalBukti; ?>)
                    </button>
                    <button class="btn btn-outline-info filter-btn" data-tipe="pengembalian">
                        <i class="fas fa-undo me-1"></i>Pengembalian (<?php echo $countPengembalian; ?>)
                    </button>
                    <button class="btn btn-outline-warning filter-btn" data-tipe="peminjaman">
                        <i class="fas fa-handshake me-1"></i>Peminjaman (<?php echo $countPeminjaman; ?>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Daftar Bukti
                </h5>
                <div class="d-flex align-items-center">
                    <div class="input-group" style="width: 300px;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari bukti...">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="buktiTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Tipe Bukti</th>
                                <th>Keterangan</th>
                                <th>Tanggal Upload</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalBuktiListed = 0;
                            $allBukti->execute();

                            while ($item = $allBukti->fetch(PDO::FETCH_ASSOC)):
                                $totalBuktiListed++;

                                $tipe_class = '';
                                $tipe_icon = '';
                                switch($item['tipe_bukti']) {
                                    case 'pengembalian':
                                        $tipe_class = 'badge-pengembalian';
                                        $tipe_icon = 'fa-undo';
                                        break;
                                    case 'peminjaman':
                                        $tipe_class = 'badge-peminjaman';
                                        $tipe_icon = 'fa-handshake';
                                        break;
                                    default:
                                        $tipe_class = 'badge-secondary';
                                        $tipe_icon = 'fa-question-circle';
                                }

                                // Check if file is image
                                $isImage = in_array(strtolower(pathinfo($item['nama_file'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                            ?>
                            <tr class="bukti-row" data-tipe="<?php echo $item['tipe_bukti']; ?>">
                                <td>
                                    <strong>#<?php echo $item['id']; ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['nama_peminjam']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $tipe_class; ?>">
                                        <i class="fas <?php echo $tipe_icon; ?> me-1"></i>
                                        <?php echo ucfirst($item['tipe_bukti']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo !empty($item['keterangan']) ? htmlspecialchars($item['keterangan']) : '<em class="text-muted">Tidak ada keterangan</em>'; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <!-- TOMBOL DETAIL BUKTI -->
                                        <button type="button" class="btn btn-outline-primary btn-sm action-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal<?php echo $item['id']; ?>"
                                                title="Lihat Detail Bukti">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- TOMBOL PREVIEW STRUK PEMINJAMAN -->
                                        <button type="button" class="btn btn-info btn-sm action-btn preview-peminjaman"
                                                data-id="<?php echo $item['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($item['nama_peminjam']); ?>"
                                                data-tipe="<?php echo $item['tipe_bukti']; ?>"
                                                title="Preview Struk Peminjaman"
                                                <?php echo $item['tipe_bukti'] != 'peminjaman' ? 'disabled' : '' ?>>
                                            <i class="fas fa-file-pdf"></i>
                                        </button>

                                        <!-- TOMBOL PREVIEW STRUK PENGEMBALIAN -->
                                        <button type="button" class="btn btn-info btn-sm action-btn preview-pengembalian"
                                                data-id="<?php echo $item['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($item['nama_peminjam']); ?>"
                                                data-tipe="<?php echo $item['tipe_bukti']; ?>"
                                                title="Preview Struk Pengembalian"
                                                <?php echo $item['tipe_bukti'] != 'pengembalian' ? 'disabled' : '' ?>>
                                            <i class="fas fa-file-pdf"></i>
                                        </button>

                                        <button type="button" class="btn btn-outline-danger btn-sm action-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $item['id']; ?>"
                                                title="Hapus bukti">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php if ($totalBuktiListed == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-inbox fa-3x mb-3" style="color: var(--dark-text-secondary);"></i>
                                        <h5 class="mb-2">Belum ada bukti</h5>
                                        <p class="text-muted mb-0">Belum ada bukti yang diupload oleh peminjam</p>
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
                    <div class="col-md-12 text-end">
                        <small>
                            Total: <?php echo $totalBuktiListed; ?> bukti
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modals -->
    <?php
    $allBukti->execute();
    while ($item = $allBukti->fetch(PDO::FETCH_ASSOC)):
        if (in_array(strtolower(pathinfo($item['nama_file'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])):
    ?>
    <div class="modal fade" id="imageModal<?php echo $item['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-image me-2"></i>Preview Bukti
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../uploads/bukti/<?php echo htmlspecialchars($item['nama_file']); ?>"
                         alt="Bukti"
                         class="img-fluid rounded">
                    <hr>
                    <div class="text-start">
                        <h6 class="text-center mb-3">Sistem Peminjaman Alat</h6>
                        <p><strong>ID Peminjam:</strong> <?php
                            // Get user ID from bukti
                            $userModel = new User();
                            $user = $userModel->getUserById($item['id_user']);
                            echo htmlspecialchars($user['username'] ?? '-');
                        ?></p>
                        <p><strong>Tipe Bukti:</strong> <?php echo ucfirst($item['tipe_bukti']); ?></p>
                        <p><strong>Tanggal Upload:</strong> <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></p>
                        <?php if (!empty($item['keterangan'])): ?>
                        <p><strong>Keterangan:</strong> <?php echo htmlspecialchars($item['keterangan']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; endwhile; ?>

    <!-- Detail Modals -->
    <?php
    $allBukti->execute();
    while ($item = $allBukti->fetch(PDO::FETCH_ASSOC)):
    ?>
    <div class="modal fade" id="detailModal<?php echo $item['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detail Bukti
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Informasi Bukti</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID Bukti:</strong></td>
                                    <td>#<?php echo $item['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Nama Peminjam:</strong></td>
                                    <td><?php echo htmlspecialchars($item['nama_peminjam']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tipe Bukti:</strong></td>
                                    <td>
                                        <span class="badge <?php echo $item['tipe_bukti'] == 'pengembalian' ? 'badge-pengembalian' : 'badge-peminjaman'; ?>">
                                            <i class="fas <?php echo $item['tipe_bukti'] == 'pengembalian' ? 'fa-undo' : 'fa-handshake'; ?> me-1"></i>
                                            <?php echo ucfirst($item['tipe_bukti']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Upload:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Informasi File</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Aktif
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($item['keterangan'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary mb-2">Keterangan</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-comment me-2"></i>
                                <?php echo htmlspecialchars($item['keterangan']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary mb-2">Informasi User</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td><?php
                                        $user = $userModel->getUserById($item['id_user']);
                                        echo htmlspecialchars($user['username'] ?? '-');
                                    ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                </tr>
                                <?php if (!empty($user['no_hp'])): ?>
                                <tr>
                                    <td><strong>No. HP:</strong></td>
                                    <td><?php echo htmlspecialchars($user['no_hp']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Tutup
                    </button>
                    <?php if ($item['tipe_bukti'] == 'peminjaman'): ?>
                    <button type="button" class="btn btn-info preview-peminjaman"
                            data-id="<?php echo $item['id']; ?>"
                            data-nama="<?php echo htmlspecialchars($item['nama_peminjam']); ?>"
                            data-tipe="<?php echo $item['tipe_bukti']; ?>">
                        <i class="fas fa-file-pdf me-1"></i>Preview Struk
                    </button>
                    <button type="button" class="btn btn-print cetak-peminjaman"
                            data-id="<?php echo $item['id']; ?>"
                            data-nama="<?php echo htmlspecialchars($item['nama_peminjam']); ?>"
                            data-tipe="<?php echo $item['tipe_bukti']; ?>">
                        <i class="fas fa-print me-1"></i>Cetak Struk
                    </button>
                    <?php elseif ($item['tipe_bukti'] == 'pengembalian'): ?>
                    <button type="button" class="btn btn-info preview-pengembalian"
                            data-id="<?php echo $item['id']; ?>"
                            data-nama="<?php echo htmlspecialchars($item['nama_peminjam']); ?>"
                            data-tipe="<?php echo $item['tipe_bukti']; ?>">
                        <i class="fas fa-file-pdf me-1"></i>Preview Struk
                    </button>
                    <button type="button" class="btn btn-invoice cetak-pengembalian"
                            data-id="<?php echo $item['id']; ?>"
                            data-nama="<?php echo htmlspecialchars($item['nama_peminjam']); ?>"
                            data-tipe="<?php echo $item['tipe_bukti']; ?>">
                        <i class="fas fa-print me-1"></i>Cetak Struk
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- Delete Confirmation Modals -->
    <?php
    $allBukti->execute();
    while ($item = $allBukti->fetch(PDO::FETCH_ASSOC)):
    ?>
    <div class="modal fade" id="deleteModal<?php echo $item['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Hapus Bukti
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                        <p>Apakah Anda yakin ingin menghapus bukti ini?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Peminjam:</strong> <?php echo htmlspecialchars($item['nama_peminjam']); ?></p>
                                <p><strong>Tipe:</strong> <?php echo ucfirst($item['tipe_bukti']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>File:</strong> <?php echo htmlspecialchars($item['nama_file']); ?></p>
                                <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="delete" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const tipe = this.getAttribute('data-tipe');
                filterTable(tipe);

                // Update active filter button
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        function filterTable(tipe) {
            const rows = document.querySelectorAll('.bukti-row');
            rows.forEach(row => {
                if (tipe === 'semua' || row.getAttribute('data-tipe') === tipe) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.bukti-row');

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Handler untuk tombol cetak peminjaman
        document.querySelectorAll('.cetak-peminjaman').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');

                    // Tampilkan konfirmasi
                    if (confirm(`Cetak struk peminjaman untuk ${nama}?`)) {
                        // Redirect atau buka window baru untuk cetak dengan bukti_id
                        window.open(`cetak_struk_peminjaman.php?bukti_id=${id}`, '_blank');
                    }
                } else {
                    alert('Tombol ini hanya tersedia untuk bukti dengan tipe peminjaman.');
                }
            });
        });

        // Handler untuk tombol preview peminjaman
        document.querySelectorAll('.preview-peminjaman').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const id = this.getAttribute('data-id');
                    // Buka preview di tab baru dengan bukti_id
                    window.open(`cetak_struk_peminjaman.php?bukti_id=${id}&preview=1`, '_blank');
                } else {
                    alert('Tombol ini hanya tersedia untuk bukti dengan tipe peminjaman.');
                }
            });
        });

        // Handler untuk tombol preview pengembalian
        document.querySelectorAll('.preview-pengembalian').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const id = this.getAttribute('data-id');
                    // Buka preview di tab baru
                    window.open(`cetak_struk_pengembalian.php?id=${id}&preview=1`, '_blank');
                } else {
                    alert('Tombol ini hanya tersedia untuk bukti dengan tipe pengembalian.');
                }
            });
        });

        // Handler untuk tombol cetak peminjaman
        document.querySelectorAll('.cetak-peminjaman').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');

                    // Tampilkan konfirmasi
                    if (confirm(`Cetak struk peminjaman untuk ${nama}?`)) {
                        // Redirect atau buka window baru untuk cetak
                        window.open(`cetak_struk_peminjaman.php?id=${id}`, '_blank');
                    }
                } else {
                    alert('Tombol ini hanya tersedia untuk bukti dengan tipe peminjaman.');
                }
            });
        });

        // Handler untuk tombol cetak pengembalian (sekarang menampilkan preview dulu)
        document.querySelectorAll('.cetak-pengembalian').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');

                    // Tampilkan konfirmasi
                    if (confirm(`Lihat preview struk pengembalian untuk ${nama}?`)) {
                        // Buka preview di tab baru
                        window.open(`cetak_struk_pengembalian.php?id=${id}&preview=1`, '_blank');
                    }
                } else {
                    alert('Tombol ini hanya tersedia untuk bukti dengan tipe pengembalian.');
                }
            });
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>