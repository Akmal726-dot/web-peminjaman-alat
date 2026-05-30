<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
require_once '../../src/models/Alat.php';
require_once '../../src/models/User.php';

$peminjamanModel = new Peminjaman();
$alatModel = new Alat();
$userModel = new User();

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $petugas_id = $_SESSION['user_id'];
    
    if (isset($_POST['approve'])) {
        error_log("Approve request received for ID: " . $id_peminjaman);
        $result = $peminjamanModel->approvePeminjaman($id_peminjaman, $petugas_id);
        error_log("Approve result: " . ($result ? 'true' : 'false'));
        if ($result) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Peminjaman berhasil disetujui.'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Gagal menyetujui peminjaman.'
            ];
        }
    }
    elseif (isset($_POST['reject'])) {
        $alasan_penolakan = $_POST['alasan_penolakan'] ?? '';
        $result = $peminjamanModel->rejectPeminjaman($id_peminjaman, $petugas_id, $alasan_penolakan);
        if ($result) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Peminjaman berhasil ditolak.'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Gagal menolak peminjaman.'
            ];
        }
    }
    elseif (isset($_POST['return'])) {
        $kondisi_alat = $_POST['kondisi_alat'] ?? 'baik';
        $keterangan = $_POST['keterangan_pengembalian'] ?? '';
        
        $result = $peminjamanModel->processPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $petugas_id);
        if ($result) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Pengembalian alat berhasil diproses.'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Gagal memproses pengembalian alat.'
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

$peminjaman = $peminjamanModel->getAllPeminjaman();
$totalMenunggu = $peminjamanModel->countPeminjamanByStatus('pending');
$totalDisetujui = $peminjamanModel->countPeminjamanByStatus('disetujui');
$totalDitolak = $peminjamanModel->countPeminjamanByStatus('ditolak');
$totalDikembalikan = $peminjamanModel->countPeminjamanByStatus('dikembalikan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peminjaman - Petugas</title>
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

        .text-primary {
            color: var(--primary) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .border-primary {
            border-color: var(--primary) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            border: none !important;
            color: white !important;
        }

        .btn-outline-primary {
            color: var(--primary) !important;
            border-color: var(--primary) !important;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: white !important;
            border-color: transparent !important;
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

        .dropdown-menu {
            background: var(--dark-card) !important;
            border: 1px solid var(--dark-border) !important;
        }

        .dropdown-item {
            color: var(--dark-text) !important;
        }

        .dropdown-item:hover {
            background-color: var(--dark-secondary) !important;
            color: var(--primary) !important;
        }

        .navbar {
            background: var(--dark-secondary) !important;
            border-bottom: 1px solid var(--dark-border);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1) !important;
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

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268) !important;
            border: none !important;
            color: white !important;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #495057) !important;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--dark-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .badge {
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.5em 1em !important;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .container {
            padding-top: 1rem;
        }

        /* Status badges styling */
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

        .badge-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268) !important;
        }

        /* Card header styling */
        .card-header {
            background-color: var(--dark-secondary) !important;
            border-bottom: 1px solid var(--dark-border) !important;
            padding: 1rem 1.5rem !important;
        }

        /* Action buttons */
        .action-btn {
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Table hover effects */
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }

        .table-hover tbody tr:hover {
            transform: translateX(5px);
        }

        /* Status column styling */
        .status-column {
            min-width: 140px;
        }

        /* Action column styling */
        .action-column {
            min-width: 220px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
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

        .alert-info {
            border-color: var(--info) !important;
        }

        .alert-warning {
            border-color: var(--warning) !important;
        }

        .stats-card {
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .filter-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .peminjaman-detail {
            background: var(--dark-secondary);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }

        .peminjaman-detail p {
            margin-bottom: 5px;
        }

        .urgent {
            border-left: 4px solid var(--danger) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .tooltip-custom {
            position: relative;
        }

        .tooltip-custom .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: var(--dark-secondary);
            color: var(--dark-text);
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            border: 1px solid var(--dark-border);
        }

        .tooltip-custom:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Quick Action Buttons */
        .action-quick-btn {
            width: 32px;
            height: 32px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px !important;
            transition: none !important;
        }

        /* Hanya berikan efek hover untuk tombol yang aktif */
        .action-quick-btn:not(:disabled):not(.disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease !important;
        }

        /* Tombol yang disabled */
        .action-quick-btn:disabled,
        .action-quick-btn.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Status indicator dots */
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-dot.menunggu {
            background-color: var(--warning);
            animation: pulse-dot 2s infinite;
        }

        .status-dot.disetujui {
            background-color: var(--success);
        }

        .status-dot.ditolak {
            background-color: var(--danger);
        }

        .status-dot.dikembalikan {
            background-color: var(--info);
        }

        @keyframes pulse-dot {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }

        /* Quick action tooltips */
        .quick-action-tooltip {
            position: relative;
        }

        .quick-action-tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--dark-secondary);
            color: var(--dark-text);
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            border: 1px solid var(--dark-border);
            font-size: 12px;
        }

        .quick-action-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Action button group styling */
        .action-button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            align-items: center;
        }
        
        /* Approval buttons container */
        .approval-buttons {
            background: var(--dark-secondary);
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .fa-check, .fa-check-circle, .fa-undo, .fa-clipboard-check,
        .fa-table, .fa-info-circle, .fa-arrow-left, .fa-sync-alt,
        .fa-inbox, .fa-money-bill-wave, .fa-eye, .fa-filter,
        .fa-list, .fa-search, .fa-redo, .fa-clock, .fa-times,
        .fa-tools, .fa-handshake, .fa-toolbox, .fa-users,
        .fa-home, .fa-sign-out-alt, .fa-exclamation-triangle,
        .fa-exclamation-circle, .fa-question-circle, .fa-comment-alt,
        .fa-spinner, .fa-chart-bar {
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

        .btn-outline-success {
            color: var(--success) !important;
            border-color: var(--success) !important;
        }

        .btn-outline-success:hover {
            background: var(--success) !important;
            color: white !important;
        }

        .btn-outline-danger {
            color: var(--danger) !important;
            border-color: var(--danger) !important;
        }

        .btn-outline-danger:hover {
            background: var(--danger) !important;
            color: white !important;
        }

        .btn-outline-warning {
            color: var(--warning) !important;
            border-color: var(--warning) !important;
        }

        .btn-outline-warning:hover {
            background: var(--warning) !important;
            color: var(--dark-text) !important;
        }

        .btn-outline-info {
            color: var(--info) !important;
            border-color: var(--info) !important;
        }

        .btn-outline-info:hover {
            background: var(--info) !important;
            color: white !important;
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
                    <i class="fas fa-clipboard-check me-2"></i>Persetujuan Peminjaman
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Kelola persetujuan dan penolakan peminjaman alat
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
                <div class="card stats-card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Menunggu</h6>
                                <h3 class="mb-0"><?php echo $totalMenunggu; ?></h3>
                            </div>
                            <div class="icon-circle bg-warning">
                                <i class="fas fa-clock text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Disetujui</h6>
                                <h3 class="mb-0"><?php echo $totalDisetujui; ?></h3>
                            </div>
                            <div class="icon-circle bg-success">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Ditolak</h6>
                                <h3 class="mb-0"><?php echo $totalDitolak; ?></h3>
                            </div>
                            <div class="icon-circle bg-danger">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Dikembalikan</h6>
                                <h3 class="mb-0"><?php echo $totalDikembalikan; ?></h3>
                            </div>
                            <div class="icon-circle bg-info">
                                <i class="fas fa-undo text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Status</h6>
            </div>
            <div class="card-body">
                <div class="filter-buttons">
                    <button class="btn btn-outline-warning filter-btn" data-status="pending">
                        <i class="fas fa-clock me-1"></i>Menunggu (<?php echo $totalMenunggu; ?>)
                    </button>
                    <button class="btn btn-outline-success filter-btn" data-status="disetujui">
                        <i class="fas fa-check me-1"></i>Disetujui (<?php echo $totalDisetujui; ?>)
                    </button>
                    <button class="btn btn-outline-danger filter-btn" data-status="ditolak">
                        <i class="fas fa-times me-1"></i>Ditolak (<?php echo $totalDitolak; ?>)
                    </button>
                    <button class="btn btn-outline-info filter-btn" data-status="dikembalikan">
                        <i class="fas fa-undo me-1"></i>Dikembalikan (<?php echo $totalDikembalikan; ?>)
                    </button>
                    <button class="btn btn-outline-primary filter-btn" data-status="semua">
                        <i class="fas fa-list me-1"></i>Semua Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Daftar Peminjaman
                </h5>
                <div class="d-flex align-items-center">
                    <div class="input-group" style="width: 300px;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari peminjaman...">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="peminjamanTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Kondisi</th>
                                <th>Tanggal</th>
                                <th>Durasi</th>
                                <th class="status-column">Status</th>
                                <th class="action-column">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalPeminjaman = 0;
                            $peminjaman->execute();
                            
                            while ($item = $peminjaman->fetch(PDO::FETCH_ASSOC)):
                                $totalPeminjaman++;
                                
                                // Check if urgent (less than 1 day before start)
                                $isUrgent = false;
                                if ($item['tanggal_peminjaman'] && $item['status'] == 'pending') {
                                    $startDate = new DateTime($item['tanggal_peminjaman']);
                                    $today = new DateTime();
                                    $interval = $today->diff($startDate);
                                    if ($interval->days <= 1 && !$interval->invert) {
                                        $isUrgent = true;
                                    }
                                }
                                
                                $status_class = '';
                                $status_icon = '';
                                switch($item['status']) {
                                    case 'disetujui':
                                        $status_class = 'badge-success';
                                        $status_icon = 'fa-check-circle';
                                        break;
                                    case 'pending':
                                        $status_class = 'badge-warning';
                                        $status_icon = 'fa-clock';
                                        break;
                                    case 'ditolak':
                                        $status_class = 'badge-danger';
                                        $status_icon = 'fa-times-circle';
                                        break;
                                    case 'dikembalikan':
                                        $status_class = 'badge-info';
                                        $status_icon = 'fa-undo';
                                        break;
                                    default:
                                        $status_class = 'badge-secondary';
                                        $status_icon = 'fa-question-circle';
                                }
                                
                                // Calculate duration
                                $durasi = '-';
                                if (isset($item['tanggal_peminjaman']) && isset($item['tanggal_pengembalian']) && $item['tanggal_peminjaman'] && $item['tanggal_pengembalian']) {
                                    $start = new DateTime($item['tanggal_peminjaman']);
                                    $end = new DateTime($item['tanggal_pengembalian']);
                                    $interval = $start->diff($end);
                                    $durasi = $interval->days . ' hari';
                                }
                            ?>
                            <tr class="peminjaman-row" data-status="<?php echo $item['status']; ?>" 
                                data-id="<?php echo $item['id_peminjaman']; ?>"
                                <?php echo $isUrgent ? 'data-urgent="true"' : ''; ?>>
                                <td>
                                    <strong>#<?php echo $item['id_peminjaman']; ?></strong>
                                    <?php if ($isUrgent): ?>
                                    <span class="badge bg-danger ms-1 tooltip-custom">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span class="tooltip-text">Harus segera diproses! Peminjaman dimulai dalam waktu dekat.</span>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['nama_peminjam']); ?></strong>
                                        <?php if (!empty($item['email'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['email']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($item['no_hp'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['no_hp']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['nama_alat']); ?></strong>
                                        <?php if (!empty($item['id_alat'])): ?>
                                        <br><small class="text-muted">Kode: <?php echo htmlspecialchars($item['id_alat']); ?></small>
                                        <?php endif; ?>
                                    </div>
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
                                    <div>
                                        <small class="d-block">Pinjam: <?php echo $item['tanggal_peminjaman'] ? date('d/m/Y', strtotime($item['tanggal_peminjaman'])) : '-'; ?></small>
                                        <small class="d-block">Kembali: <?php echo isset($item['tanggal_pengembalian']) && $item['tanggal_pengembalian'] ? date('d/m/Y', strtotime($item['tanggal_pengembalian'])) : '-'; ?></small>
                                    </div>
                                </td>
                                <td><?php echo $durasi; ?></td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                    <?php if (!empty($item['alasan_penolakan']) && $item['status'] == 'ditolak'): ?>
                                    <br>
                                    <small class="text-danger mt-1 d-block">
                                        <i class="fas fa-comment-alt me-1"></i>
                                        <?php echo htmlspecialchars($item['alasan_penolakan']); ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Quick Action Buttons (always visible) -->
                                    <div class="approval-buttons">
                                        <div class="action-button-group">
                                            <!-- Quick Approve Button -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id_peminjaman" value="<?php echo $item['id_peminjaman']; ?>">
                                                <button type="submit"
                                                        name="approve"
                                                        class="btn btn-outline-success action-quick-btn <?php echo $item['status'] !== 'pending' ? 'disabled' : ''; ?>"
                                                        <?php echo $item['status'] !== 'pending' ? 'disabled' : ''; ?>
                                                        data-bs-toggle="tooltip"
                                                        title="Setujui peminjaman ini">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Quick Reject Button -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id_peminjaman" value="<?php echo $item['id_peminjaman']; ?>">
                                                <input type="hidden" name="alasan_penolakan" value="Ditolak oleh petugas">
                                                <button type="submit"
                                                        name="reject"
                                                        class="btn btn-outline-danger action-quick-btn <?php echo $item['status'] !== 'pending' ? 'disabled' : ''; ?>"
                                                        <?php echo $item['status'] !== 'pending' ? 'disabled' : ''; ?>
                                                        data-bs-toggle="tooltip"
                                                        title="Tolak peminjaman ini">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>


                                        </div>
                                        
                                        <!-- Status-based action text -->
                                        <div class="small mt-2">
                                            <?php if ($item['status'] === 'pending' || $item['status'] === 'menunggu'): ?>
                                            <span class="text-warning">
                                                <i class="fas fa-clock me-1"></i>Menunggu persetujuan
                                            </span>
                                            <?php elseif ($item['status'] === 'disetujui'): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check me-1"></i>Disetujui - Menunggu pengembalian
                                            </span>
                                            <?php elseif ($item['status'] === 'ditolak'): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-times me-1"></i>Ditolak
                                                <?php if (!empty($item['alasan_penolakan'])): ?>
                                                <br><small>Alasan: <?php echo substr(htmlspecialchars($item['alasan_penolakan']), 0, 50); ?>...</small>
                                                <?php endif; ?>
                                            </span>
                                            <?php elseif ($item['status'] === 'dikembalikan'): ?>
                                            <span class="text-info">
                                                <i class="fas fa-undo me-1"></i>Telah dikembalikan
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    

                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php if ($totalPeminjaman == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-inbox fa-3x mb-3" style="color: var(--dark-text-secondary);"></i>
                                        <h5 class="mb-2">Belum ada data peminjaman</h5>
                                        <p class="text-muted mb-0">Sistem belum memiliki data peminjaman alat</p>
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
                            Total: <?php echo $totalPeminjaman; ?> data peminjaman
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Modals for Quick Actions -->
    <div class="modal fade" id="globalApproveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Setujui Peminjaman
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="globalApproveForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_peminjaman" id="globalApproveId">
                        <p>Anda akan menyetujui peminjaman ini. Apakah Anda yakin?</p>
                        <div class="alert alert-success">
                            <i class="fas fa-info-circle me-2"></i>
                            Setelah disetujui, alat akan berstatus "dipinjam" dan tidak dapat dipinjam oleh orang lain.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="approve" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Ya, Setujui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="globalRejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Tolak Peminjaman
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="globalRejectForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_peminjaman" id="globalRejectId">
                        <p>Anda akan menolak peminjaman ini. Berikan alasan penolakan:</p>
                        <div class="mb-3">
                            <label for="globalAlasanPenolakan" class="form-label">
                                <strong>Alasan Penolakan:</strong>
                            </label>
                            <textarea class="form-control" 
                                      id="globalAlasanPenolakan" 
                                      name="alasan_penolakan" 
                                      rows="3" 
                                      placeholder="Berikan alasan penolakan yang jelas..." 
                                      required></textarea>
                            <div class="form-text">Alasan ini akan dikirimkan ke peminjam.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="reject" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Ya, Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modals for each peminjaman -->
    <?php
    // Reset the cursor to loop again for modals
    $peminjaman->execute();
    while ($item = $peminjaman->fetch(PDO::FETCH_ASSOC)):
        // Check if urgent
        $isUrgent = false;
        if ($item['tanggal_peminjaman'] && $item['status'] == 'pending') {
            $startDate = new DateTime($item['tanggal_peminjaman']);
            $today = new DateTime();
            $interval = $today->diff($startDate);
            if ($interval->days <= 1 && !$interval->invert) {
                $isUrgent = true;
            }
        }
        
        // Calculate duration
        $durasi = '-';
        if (isset($item['tanggal_peminjaman']) && isset($item['tanggal_pengembalian']) && $item['tanggal_peminjaman'] && $item['tanggal_pengembalian']) {
            $start = new DateTime($item['tanggal_peminjaman']);
            $end = new DateTime($item['tanggal_pengembalian']);
            $interval = $start->diff($end);
            $durasi = $interval->days . ' hari';
        }
    ?>
    <div class="modal fade" id="detailModal<?php echo $item['id_peminjaman']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Detail Peminjaman
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Peminjam</h6>
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($item['nama_peminjam']); ?></p>
                            <?php if (!empty($item['email'])): ?>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($item['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['no_hp'])): ?>
                            <p><strong>No. HP:</strong> <?php echo htmlspecialchars($item['no_hp']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Alat</h6>
                            <p><strong>Nama Alat:</strong> <?php echo htmlspecialchars($item['nama_alat']); ?></p>
                            <?php if (!empty($item['id_alat'])): ?>
                            <p><strong>Kode:</strong> <?php echo htmlspecialchars($item['id_alat']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['deskripsi'])): ?>
                            <p><strong>Spesifikasi:</strong> <?php echo htmlspecialchars($item['deskripsi']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Jadwal</h6>
                            <p><strong>Tanggal Pinjam:</strong> <?php echo date('d/m/Y', strtotime($item['tanggal_peminjaman'])); ?></p>
                            <p><strong>Tanggal Kembali:</strong> <?php echo isset($item['tanggal_pengembalian']) && $item['tanggal_pengembalian'] ? date('d/m/Y', strtotime($item['tanggal_pengembalian'])) : '-'; ?></p>
                            <p><strong>Durasi:</strong> <?php echo $durasi; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Status & Keterangan</h6>
                            <p><strong>Status:</strong> 
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </p>
                            <?php if (!empty($item['keterangan'])): ?>
                            <p><strong>Keperluan:</strong> <?php echo htmlspecialchars($item['keterangan']); ?></p>
                            <?php endif; ?>
                            <p><strong>Diajukan pada:</strong> <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php if ($isUrgent): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Peminjaman ini akan dimulai dalam waktu dekat. Segera proses permintaan ini.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- Modal for reconsider request -->
    <div class="modal fade" id="reconsiderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-redo me-2"></i>Pertimbangkan Ulang Peminjaman
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="reconsiderForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_peminjaman" id="reconsiderId">
                        <input type="hidden" name="action" value="reconsider">
                        <p>Anda akan mengubah status peminjaman ini menjadi "Menunggu" untuk diproses ulang.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Peminjaman yang dipertimbangkan ulang akan muncul kembali di daftar permintaan menunggu.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-redo me-1"></i>Ya, Pertimbangkan Ulang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quick Action Functions
        function quickApprove(peminjamanId) {
            // Close any open detail modal first
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });
            
            document.getElementById('globalApproveId').value = peminjamanId;
            const modal = new bootstrap.Modal(document.getElementById('globalApproveModal'));
            modal.show();
        }

        function quickReject(peminjamanId) {
            // Close any open detail modal first
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });
            
            document.getElementById('globalRejectId').value = peminjamanId;
            const modal = new bootstrap.Modal(document.getElementById('globalRejectModal'));
            modal.show();
        }

        function quickReturn(peminjamanId) {
            // Find and trigger the return modal
            const returnModal = document.getElementById('returnModal' + peminjamanId);
            if (returnModal) {
                const modal = new bootstrap.Modal(returnModal);
                modal.show();
            }
        }

        // Disable buttons on form submit to prevent multiple submissions
        function disableOnSubmit(formId, buttonText) {
            const form = document.getElementById(formId);
            const submitBtn = form.querySelector('button[type="submit"]');
            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + buttonText;
            });
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const status = this.getAttribute('data-status');
                filterTable(status);
            });
        });

        function filterTable(status) {
            const rows = document.querySelectorAll('.peminjaman-row');
            rows.forEach(row => {
                if (status === 'semua' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-status') === status) {
                    btn.classList.add('active');
                }
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.peminjaman-row');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Reconsider request function
        function reconsiderRequest(peminjamanId) {
            document.getElementById('reconsiderId').value = peminjamanId;
            const modal = new bootstrap.Modal(document.getElementById('reconsiderModal'));
            modal.show();
        }

        // Highlight urgent rows
        document.querySelectorAll('[data-urgent="true"]').forEach(row => {
            row.style.borderLeft = '4px solid var(--danger)';
            row.style.animation = 'pulse 2s infinite';
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto refresh disabled to prevent flickering
        // setInterval(function() {
        //     location.reload();
        // }, 30000);

        // Sort table by urgency and date
        function sortTable() {
            const table = document.getElementById('peminjamanTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('.peminjaman-row'));
            
            rows.sort((a, b) => {
                const aUrgent = a.getAttribute('data-urgent') === 'true';
                const bUrgent = b.getAttribute('data-urgent') === 'true';
                
                if (aUrgent && !bUrgent) return -1;
                if (!aUrgent && bUrgent) return 1;
                
                const aStatus = a.getAttribute('data-status');
                const bStatus = b.getAttribute('data-status');
                
                if (aStatus === 'pending' && bStatus !== 'pending') return -1;
                if (aStatus !== 'pending' && bStatus === 'pending') return 1;
                
                return 0;
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }

        // Initial sort
        sortTable();

        // Update quick actions based on status
        function updateQuickActions() {
            document.querySelectorAll('.action-quick-btn').forEach(btn => {
                const row = btn.closest('tr');
                if (!row) return;
                
                const status = row.getAttribute('data-status');
                const isApproveBtn = btn.querySelector('.fa-check');
                const isRejectBtn = btn.querySelector('.fa-times');
                
                // Enable/disable based on status
                if ((isApproveBtn || isRejectBtn) && status !== 'pending') {
                    btn.disabled = true;
                    btn.classList.add('disabled');
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateQuickActions();
            
            // Initialize disable on submit for forms
            disableOnSubmit('globalApproveForm', 'Menyetujui...');
            disableOnSubmit('globalRejectForm', 'Menolak...');
        });
    </script>
</body>
</html>