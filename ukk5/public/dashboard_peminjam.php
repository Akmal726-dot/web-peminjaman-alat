<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../src/config/auth.php';
require_once '../src/models/User.php';
require_once '../src/models/Alat.php';
require_once '../src/models/Peminjaman.php';

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

// Get statistics
$totalAlat = $alatModel->countAlat();

// Get recent activities (only for this user)
$recentPeminjaman = $peminjamanModel->getPeminjamanByUser($user['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peminjam - Aplikasi Peminjaman Alat</title>
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid var(--hitam-terang);
            }
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
                            <a class="nav-link active" href="dashboard_peminjam.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="peminjam/alat.php">
                                <i class="fas fa-list me-2"></i>Daftar Alat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="peminjam/peminjaman.php">
                                <i class="fas fa-handshake me-2"></i>Peminjaman Alat
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="peminjam/pengembalian.php">
                                <i class="fas fa-paper-plane me-2"></i>Pengembalian alat
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="user-info">
                    <p class="mb-1"><strong><?php echo htmlspecialchars($user['nama']); ?></strong></p>
                    <span class="badge bg-hijau">Peminjam</span>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <nav class="navbar navbar-light py-3">
                    <div class="container-fluid">
                        <h4 class="mb-0">
                            <i class="fas fa-home me-2 text-hijau"></i>
                            Dashboard Peminjam
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
                                        Anda login sebagai <strong>Peminjam</strong>.
                                        Anda dapat melihat daftar alat, mengajukan peminjaman, dan mengembalikan alat.
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
                                        <h6 class="text-uppercase mb-2 text-abu">Alat Tersedia</h6>
                                        <h2 class="mb-0"><?php echo $totalAlat; ?></h2>
                                        <small class="text-abu">Siap dipinjam</small>
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
                                        <h6 class="text-uppercase mb-2 text-abu">Peminjaman Aktif</h6>
                                        <h2 class="mb-0">
                                            <?php 
                                            $peminjamanUser = $peminjamanModel->getPeminjamanByUser($user['id']);
                                            $peminjamanUser->execute();
                                            $count = 0;
                                            while ($row = $peminjamanUser->fetch(PDO::FETCH_ASSOC)) {
                                                if ($row['status'] == 'disetujui') {
                                                    $count++;
                                                }
                                            }
                                            echo $count;
                                            ?>
                                        </h2>
                                        <small class="text-abu">Sedang Anda pinjam</small>
                                    </div>
                                    <div class="stat-icon stat-active">
                                        <i class="fas fa-clipboard-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-2 text-abu">Menunggu Persetujuan</h6>
                                        <h2 class="mb-0">
                                            <?php 
                                            $peminjamanUser = $peminjamanModel->getPeminjamanByUser($user['id']);
                                            $peminjamanUser->execute();
                                            $countPending = 0;
                                            while ($row = $peminjamanUser->fetch(PDO::FETCH_ASSOC)) {
                                                if ($row['status'] == 'pending') {
                                                    $countPending++;
                                                }
                                            }
                                            echo $countPending;
                                            ?>
                                        </h2>
                                        <small class="text-abu">Peminjaman pending</small>
                                    </div>
                                    <div class="stat-icon stat-pending">
                                        <i class="fas Ffa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-2 text-abu">Harus Dikembalikan</h6>
                                        <h2 class="mb-0">
                                            <?php 
                                            $peminjamanUser = $peminjamanModel->getPeminjamanByUser($user['id']);
                                            $peminjamanUser->execute();
                                            $countHarusKembali = 0;
                                            $today = date('Y-m-d');
                                            while ($row = $peminjamanUser->fetch(PDO::FETCH_ASSOC)) {
                                                if ($row['status'] == 'disetujui' && 
                                                    $row['tanggal_pengembalian'] && 
                                                    $row['tanggal_pengembalian'] <= $today) {
                                                    $countHarusKembali++;
                                                }
                                            }
                                            echo $countHarusKembali;
                                            ?>
                                        </h2>
                                        <small class="text-abu">Sudah jatuh tempo</small>
                                    </div>
                                    <div class="stat-icon stat-pending">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2 text-hijau"></i>
                                Aksi Cepat
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="peminjam/alat.php" class="quick-action">
                                        <i class="fas fa-list fa-2x mb-3 text-hijau"></i>
                                        <h6>Lihat Daftar Alat</h6>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="peminjam/peminjaman.php" class="quick-action">
                                        <i class="fas fa-handshake fa-2x mb-3 text-hijau"></i>
                                        <h6>Peminjaman Alat</h6>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="peminjam/data_peminjaman.php" class="quick-action">
                                        <i class="fas fa-clipboard-list fa-2x mb-3 text-hijau"></i>
                                        <h6>Data Peminjaman</h6>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="peminjam/pengembalian.php" class="quick-action">
                                        <i class="fas fa-paper-plane fa-2x mb-3 text-hijau"></i>
                                        <h6>Pengembalian alat</h6>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2 text-hijau"></i>
                                Aktivitas Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Alat</th>
                                            <th>Tanggal Pinjam</th>
                                            <th>Tanggal Kembali</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $counter = 0;
                                        $recentPeminjaman->execute();
                                        while (($row = $recentPeminjaman->fetch(PDO::FETCH_ASSOC)) && $counter < 5):
                                            $counter++;
                                            $badge_class = '';
                                            switch($row['status']) {
                                                case 'disetujui':
                                                    $badge_class = 'badge-success';
                                                    break;
                                                case 'pending':
                                                    $badge_class = 'badge-warning';
                                                    break;
                                                case 'ditolak':
                                                    $badge_class = 'badge-danger';
                                                    break;
                                                case 'dikembalikan':
                                                    $badge_class = 'badge-info';
                                                    break;
                                            }
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_peminjaman']; ?></strong></td>
                                            <td>
                                                <i class="fas fa-tools me-2 text-abu"></i>
                                                <?php echo htmlspecialchars($row['nama_alat']); ?>
                                            </td>
                                            <td><?php echo $row['tanggal_peminjaman'] ? date('d/m/Y', strtotime($row['tanggal_peminjaman'])) : '-'; ?></td>
                                            <td><?php echo $row['tanggal_pengembalian'] ? date('d/m/Y', strtotime($row['tanggal_pengembalian'])) : '-'; ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'disetujui'): ?>
                                                    <a href="peminjam/pengembalian.php?peminjaman_id=<?php echo $row['id_peminjaman']; ?>" 
                                                       class="btn btn-sm btn-hijau">
                                                        <i class="fas fa-paper-plane me-1"></i>Pengembalian alat
                                                    </a>
                                                <?php elseif ($row['status'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-hijau" disabled>
                                                        Menunggu
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php if ($counter == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Belum ada data peminjaman
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>