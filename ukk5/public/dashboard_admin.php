<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../src/config/auth.php';
require_once '../src/models/User.php';
require_once '../src/models/Alat.php';
require_once '../src/models/Peminjaman.php';
require_once '../src/models/LogAktivitas.php'; // Tambahkan model LogAktivitas

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'nama' => $_SESSION['nama']
];

// Initialize models
$userModel = new User();
$alatModel = new Alat();
$peminjamanModel = new Peminjaman();
$logModel = new LogAktivitas(); // Inisialisasi model LogAktivitas

// Handle pengembalian alat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kembalikan_alat'])) {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $kondisi_alat = $_POST['kondisi_alat'] ?? 'baik';
    $catatan = $_POST['catatan'] ?? '';
    
    if ($id_peminjaman) {
        $success = $peminjamanModel->konfirmasiPengembalian($id_peminjaman, $kondisi_alat, $catatan, $user['id']);

        if ($success) {
            $_SESSION['success_message'] = "Pengembalian alat berhasil dikonfirmasi!";
            // Log aktivitas
            $logModel->logActivity($user['id'], "Mengkonfirmasi pengembalian alat #{$id_peminjaman}");
        } else {
            $_SESSION['error_message'] = "Gagal mengkonfirmasi pengembalian alat.";
        }

        // Refresh halaman
        header("Location: dashboard_admin.php");
        exit();
    }
}

// Get statistics
$totalUsers = $userModel->countUsers();
$totalAlat = $alatModel->countAlat();
$totalPeminjamanAktif = $peminjamanModel->countPeminjamanAktif();
$totalPending = $peminjamanModel->countPendingPeminjaman();

// Get peminjaman yang sedang dipinjam (untuk konfirmasi pengembalian)
$peminjamanAktif = $peminjamanModel->getPeminjamanAktif();

// Get recent activities from log - TAMBAHKAN INI
$recentLogs = $logModel->getRecentActivities(10);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Aplikasi Peminjaman Alat</title>
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
        
        /* Sidebar */
        .sidebar {
            background-color: var(--hitam-gelap);
            min-height: 100vh;
            color: var(--putih);
            border-right: 1px solid var(--hitam-terang);
        }
        
        .sidebar .logo {
            padding: 20px 15px;
            border-bottom: 1px solid var(--hitam-terang);
            text-align: center;
        }
        
        .sidebar .nav-link {
            color: var(--abu-gelap);
            padding: 12px 15px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover {
            color: var(--putih);
            background-color: var(--hitam-terang);
            border-left: 3px solid var(--hijau);
        }
        
        .sidebar .nav-link.active {
            color: var(--putih);
            background-color: var(--hitam-terang);
            border-left: 3px solid var(--hijau);
        }
        
        .sidebar .user-info {
            padding: 15px;
            border-top: 1px solid var(--hitam-terang);
            text-align: center;
        }
        
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
        
        .stat-card {
            background-color: var(--putih);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Statistics Icons */
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-users { background-color: rgba(40, 167, 69, 0.1); color: var(--hijau); }
        .stat-alat { background-color: rgba(0, 123, 255, 0.1); color: #007bff; }
        .stat-pending { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-active { background-color: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        
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
        
        .badge-success { background-color: var(--hijau-muda); color: var(--hijau-gelap); }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-primary { background-color: #cfe2ff; color: #084298; }
        
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
        
        /* Quick Actions */
        .quick-action {
            background-color: var(--putih);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--hitam);
            display: block;
        }
        
        .quick-action:hover {
            background-color: var(--hijau-transparan);
            border-color: var(--hijau);
            transform: translateY(-2px);
            text-decoration: none;
            color: var(--hitam);
        }
        
        /* Modal */
        .modal-content {
            border: 1px solid var(--border);
            border-radius: 8px;
        }
        
        /* Utility Classes */
        .text-hijau { color: var(--hijau) !important; }
        .bg-hijau { background-color: var(--hijau) !important; }
        .border-hijau { border-color: var(--hijau) !important; }
        
        .text-hitam { color: var(--hitam) !important; }
        .bg-hitam { background-color: var(--hitam) !important; }
        
        .text-abu { color: var(--abu-text) !important; }
        
        /* Alerts */
        .alert {
            border: 1px solid transparent;
            border-radius: 8px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid var(--hitam-terang);
            }
        }
        
        /* Kembalikan button */
        .btn-kembalikan {
            background-color: var(--hijau);
            border-color: var(--hijau);
            color: white;
            padding: 4px 12px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-kembalikan:hover {
            background-color: var(--hijau-gelap);
            border-color: var(--hijau-gelap);
            color: white;
        }
        
        /* Return Modal */
        .modal-return {
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-return .modal-dialog {
            max-width: 500px;
        }
        
        .kondisi-radio {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .kondisi-radio:hover {
            border-color: var(--hijau);
            background-color: var(--hijau-transparan);
        }
        
        .kondisi-radio.selected {
            border-color: var(--hijau);
            background-color: var(--hijau-transparan);
        }
        
        .kondisi-radio input[type="radio"] {
            margin-right: 10px;
        }
        
        /* Dashboard grid adjustments */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .grid-full {
            grid-column: 1 / -1;
        }
        
        /* Mini table */
        .mini-table {
            font-size: 0.875rem;
        }
        
        .mini-table th,
        .mini-table td {
            padding: 8px 12px;
        }
        
        /* Scrollbar styling */
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
        
        /* Log Aktivitas Styling */
        .log-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .log-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        
        .log-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .log-item:last-child {
            padding-bottom: 0;
        }
        
        .log-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--hijau);
            border: 2px solid var(--putih);
            box-shadow: 0 0 0 2px var(--hijau);
        }
        
        .log-time {
            font-size: 0.75rem;
            color: var(--abu-text);
            margin-bottom: 5px;
        }
        
        .log-content {
            background: var(--putih);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 5px;
        }
        
        .log-user {
            font-weight: 600;
            color: var(--hijau);
        }
        
        .log-activity {
            color: var(--hitam);
        }
        
        .log-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        .log-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--abu-text);
        }
        
        .log-empty i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--abu-gelap);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="logo">
                    <h5 class="mb-0">Peminjaman Alat</h5>
                    <small class="text-abu">Sistem Manajemen</small>
                </div>
                
                <div class="pt-3 px-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard_admin.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="admin/users.php">
                                <i class="fas fa-users me-2"></i>Kelola User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/alat.php">
                                <i class="fas fa-tools me-2"></i>Kelola Alat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/kategori.php">
                                <i class="fas fa-tags me-2"></i>Kategori Alat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/peminjaman.php">
                                <i class="fas fa-clipboard-list me-2"></i>Data Peminjaman
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/konfirmasi_pengembalian.php">
                                <i class="fas fa-undo-alt me-2"></i>Pengembalian Alat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/log_aktivitas.php">
                                <i class="fas fa-history me-2"></i>Log Aktivitas
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="user-info">
                    <p class="mb-1"><strong><?php echo htmlspecialchars($user['nama']); ?></strong></p>
                    <span class="badge bg-hijau">Admin</span>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <nav class="navbar navbar-light py-3">
                    <div class="container-fluid">
                        <h4 class="mb-0">
                            <i class="fas fa-home me-2 text-hijau"></i>
                            Dashboard Admin
                        </h4>
                        <div class="d-flex align-items-center">
                            <span class="me-3 text-abu">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('l, d F Y'); ?>
                            </span>
                            <a href="logout.php" class="btn btn-outline-hijau btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <main class="py-4">
                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Welcome Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="text-hijau mb-2">
                                        <i class="fas fa-user-circle me-2"></i>
                                        Selamat Datang, <?php echo htmlspecialchars($user['nama']); ?>!
                                    </h5>
                                    <p class="mb-0 text-abu">
                                        Anda login sebagai <strong>Admin</strong>.
                                        Anda memiliki akses penuh untuk mengelola seluruh sistem.
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <i class="fas fa-tools fa-4x text-hijau opacity-25"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-2 text-abu">Total Pengguna</h6>
                                        <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                                        <small class="text-abu">Pengguna aktif</small>
                                    </div>
                                    <div class="stat-icon stat-users">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-2 text-abu">Total Alat</h6>
                                        <h2 class="mb-0"><?php echo $totalAlat; ?></h2>
                                        <small class="text-abu">Alat tersedia</small>
                                    </div>
                                    <div class="stat-icon stat-alat">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-2 text-abu">Menunggu Persetujuan</h6>
                                        <h2 class="mb-0"><?php echo $totalPending; ?></h2>
                                        <small class="text-abu">Butuh konfirmasi</small>
                                    </div>
                                    <div class="stat-icon stat-pending">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-2 text-abu">Sedang Dipinjam</h6>
                                        <h2 class="mb-0"><?php echo $totalPeminjamanAktif; ?></h2>
                                        <small class="text-abu">Dalam peminjaman</small>
                                    </div>
                                    <div class="stat-icon stat-active">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Grid -->
                    <div class="dashboard-grid">
                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2 text-hijau"></i>
                                    Aksi Cepat
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <a href="admin/users.php" class="quick-action">
                                            <i class="fas fa-users fa-lg mb-2 text-hijau"></i>
                                            <h6 class="mb-0">Kelola User</h6>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/alat.php" class="quick-action">
                                            <i class="fas fa-tools fa-lg mb-2 text-hijau"></i>
                                            <h6 class="mb-0">Kelola Alat</h6>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/peminjaman.php" class="quick-action">
                                            <i class="fas fa-clipboard-list fa-lg mb-2 text-hijau"></i>
                                            <h6 class="mb-0">Data Peminjaman</h6>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/konfirmasi_pengembalian.php" class="quick-action">
                                            <i class="fas fa-undo-alt fa-lg mb-2 text-hijau"></i>
                                            <h6 class="mb-0">Pengembalian Alat</h6>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/log_aktivitas.php" class="quick-action">
                                            <i class="fas fa-history fa-lg mb-2 text-hijau"></i>
                                            <h6 class="mb-0">Log Aktivitas</h6>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/kategori.php" class="quick-action">
                                            <i class="fas fa-tags fa-lg mb-2 text-hijau"></i>
                                            <h6 class="mb-0">Kategori Alat</h6>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Peminjaman Aktif untuk Pengembalian -->
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-undo-alt me-2 text-hijau"></i>
                                        Pengembalian Alat
                                    </h5>
                                    <span class="badge bg-hijau"><?php echo $totalPeminjamanAktif; ?> alat</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (is_object($peminjamanAktif) && $peminjamanAktif->rowCount() > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 mini-table">
                                        <thead>
                                            <tr>
                                                <th>Peminjam</th>
                                                <th>Alat</th>
                                                <th>Jadwal Kembali</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $counter = 0;
                                            while (($row = $peminjamanAktif->fetch(PDO::FETCH_ASSOC)) && $counter < 4):
                                                $counter++;
                                                // Hitung keterlambatan
                                                $jadwal_kembali = $row['tanggal_pengembalian'] ? new DateTime($row['tanggal_pengembalian']) : null;
                                                $today = new DateTime();
                                                $terlambat = false;
                                                $selisih_hari = 0;

                                                if ($jadwal_kembali) {
                                                    $terlambat = $today > $jadwal_kembali;
                                                    $selisih_hari = $today->diff($jadwal_kembali)->days;
                                                    if ($today > $jadwal_kembali) {
                                                        $selisih_hari = $selisih_hari * -1;
                                                    }
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <small class="d-block text-abu">#<?php echo $row['id_peminjaman']; ?></small>
                                                    <span class="text-truncate d-block" style="max-width: 80px;">
                                                        <?php echo htmlspecialchars($row['nama_peminjam']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-tools text-abu me-2"></i>
                                                        <span class="text-truncate d-block" style="max-width: 80px;">
                                                            <?php echo htmlspecialchars($row['nama_alat']); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($row['tanggal_pengembalian']): ?>
                                                    <small><?php echo date('d/m/y', strtotime($row['tanggal_pengembalian'])); ?></small>
                                                    <?php if ($terlambat): ?>
                                                    <br><small class="badge badge-danger">+<?php echo abs($selisih_hari); ?> hari</small>
                                                    <?php elseif ($selisih_hari <= 2): ?>
                                                    <br><small class="badge badge-warning">-<?php echo $selisih_hari; ?> hari</small>
                                                    <?php else: ?>
                                                    <br><small class="badge badge-success"><?php echo $selisih_hari; ?> hari</small>
                                                    <?php endif; ?>
                                                    <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-kembalikan btn-sm" 
                                                            onclick="openReturnModal(
                                                                '<?php echo $row['id_peminjaman']; ?>',
                                                                '<?php echo htmlspecialchars($row['nama_peminjam']); ?>',
                                                                '<?php echo htmlspecialchars($row['nama_alat']); ?>'
                                                            )">
                                                        <i class="fas fa-check me-1"></i>Kembalikan
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer text-center bg-transparent border-top py-2">
                                    <a href="admin/konfirmasi_pengembalian.php" class="text-hijau text-decoration-none small">
                                        <i class="fas fa-arrow-right me-1"></i>Lihat semua pengembalian
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                                    <h6 class="text-success mb-1">Tidak ada alat yang sedang dipinjam</h6>
                                    <p class="text-muted small">Semua alat telah dikembalikan</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Log Aktivitas Terbaru -->
                    <div class="card grid-full">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2 text-hijau"></i>
                                    Log Aktivitas Terbaru
                                </h5>
                                <a href="admin/log_aktivitas.php" class="btn btn-outline-hijau btn-sm">
                                    <i class="fas fa-list me-1"></i>Lihat Semua
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (is_object($recentLogs) && $recentLogs->rowCount() > 0): ?>
                            <div class="log-timeline">
                                <?php
                                $counter = 0;
                                while (($row = $recentLogs->fetch(PDO::FETCH_ASSOC)) && $counter < 8):
                                    $counter++;
                                    $badge_class = '';
                                    switch($row['role']) {
                                        case 'admin':
                                            $badge_class = 'badge-danger';
                                            $icon = 'fa-user-shield';
                                            break;
                                        case 'petugas':
                                            $badge_class = 'badge-primary';
                                            $icon = 'fa-user-tie';
                                            break;
                                        case 'peminjam':
                                            $badge_class = 'badge-success';
                                            $icon = 'fa-user';
                                            break;
                                        default:
                                            $badge_class = 'badge-secondary';
                                            $icon = 'fa-user';
                                    }
                                ?>
                                <div class="log-item">
                                    <div class="log-time">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                                    </div>
                                    <div class="log-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span class="log-user">
                                                    <i class="fas <?php echo $icon; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($row['nama_user']); ?>
                                                </span>
                                                <span class="log-activity">
                                                    <?php echo htmlspecialchars($row['aktifitas']); ?>
                                                </span>
                                            </div>
                                            <span class="badge <?php echo $badge_class; ?> log-badge">
                                                <?php echo ucfirst($row['role']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="log-empty">
                                <i class="fas fa-history"></i>
                                <h6 class="mt-3 mb-2">Belum ada aktivitas</h6>
                                <p class="text-muted small">Log aktivitas akan muncul di sini</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Menampilkan <?php echo $counter; ?> aktivitas terbaru
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        Auto-refresh setiap 60 detik
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pengembalian -->
    <div class="modal fade" id="modalKembalikan" tabindex="-1" aria-labelledby="modalKembalikanLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-hijau" id="modalKembalikanLabel">
                        <i class="fas fa-undo-alt me-2"></i>Konfirmasi Pengembalian Alat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id_peminjaman" id="id_peminjaman">
                        <input type="hidden" name="kembalikan_alat" value="1">
                        
                        <div class="mb-3">
                            <p><strong>Peminjam:</strong> <span id="modal_peminjam"></span></p>
                            <p><strong>Alat:</strong> <span id="modal_alat"></span></p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label"><strong>Kondisi Alat Saat Dikembalikan:</strong></label>
                            <div class="mb-3">
                                <div class="kondisi-radio">
                                    <input type="radio" id="kondisi_baik" name="kondisi_alat" value="baik" checked>
                                    <label for="kondisi_baik" class="mb-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>Baik
                                    </label>
                                    <p class="text-muted mb-0 mt-1 ms-4">Alat dalam kondisi baik dan berfungsi normal</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="kondisi-radio">
                                    <input type="radio" id="kondisi_rusak_ringan" name="kondisi_alat" value="rusak_ringan">
                                    <label for="kondisi_rusak_ringan" class="mb-0">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Rusak Ringan
                                    </label>
                                    <p class="text-muted mb-0 mt-1 ms-4">Alat mengalami kerusakan kecil yang dapat diperbaiki</p>
                                </div>
                            </div>
                            <div>
                                <div class="kondisi-radio">
                                    <input type="radio" id="kondisi_rusak_berat" name="kondisi_alat" value="rusak_berat">
                                    <label for="kondisi_rusak_berat" class="mb-0">
                                        <i class="fas fa-times-circle text-danger me-2"></i>Rusak Berat
                                    </label>
                                    <p class="text-muted mb-0 mt-1 ms-4">Alat mengalami kerusakan parah yang membutuhkan perbaikan besar</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="catatan" class="form-label"><strong>Catatan (Opsional):</strong></label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3" 
                                      placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Setelah dikonfirmasi, status akan berubah menjadi "dikembalikan" dan alat akan tersedia untuk dipinjam kembali.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-hijau">
                            <i class="fas fa-check me-1"></i>Konfirmasi Pengembalian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to open return modal
        function openReturnModal(id_peminjaman, peminjam, alat) {
            // Set values in the modal
            document.getElementById('id_peminjaman').value = id_peminjaman;
            document.getElementById('modal_peminjam').textContent = peminjam;
            document.getElementById('modal_alat').textContent = alat;
            
            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('modalKembalikan'));
            modal.show();
        }
        
        // Initialize kondisi radio styling
        document.addEventListener('DOMContentLoaded', function() {
            // Handle radio selection styling
            var kondisiRadios = document.querySelectorAll('input[name="kondisi_alat"]');
            kondisiRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    // Remove selected class from all
                    document.querySelectorAll('.kondisi-radio').forEach(function(div) {
                        div.classList.remove('selected');
                    });
                    
                    // Add selected class to parent
                    this.closest('.kondisi-radio').classList.add('selected');
                });
                
                // Initialize selected state
                if (radio.checked) {
                    radio.closest('.kondisi-radio').classList.add('selected');
                }
            });
            
            // Auto refresh every 60 seconds
            setTimeout(function() {
                location.reload();
            }, 60000);
        });
    </script>
</body>
</html>