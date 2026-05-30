<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/User.php';
$userModel = new User();
$users = $userModel->getAllUsers();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama (Sama dengan dashboard_admin.php) */
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
            --danger: #dc3545;
            --danger-transparan: rgba(220, 53, 69, 0.1);
            --warning: #ffc107;
            --warning-transparan: rgba(255, 193, 7, 0.1);
            --info: #17a2b8;
            --info-transparan: rgba(23, 162, 184, 0.1);
        }
        
        body {
            background-color: var(--abu);
            color: var(--hitam);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .card {
            background: var(--putih) !important;
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .table {
            color: var(--hitam) !important;
            border-color: var(--border) !important;
        }
        
        .table thead th {
            background-color: var(--abu) !important;
            border-color: var(--border) !important;
            color: var(--hitam) !important;
            font-weight: 600;
        }
        
        .table tbody tr {
            border-color: var(--border) !important;
        }
        
        .table tbody tr:hover {
            background-color: var(--hijau-transparan) !important;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background: var(--hijau) !important;
            border: none !important;
            color: var(--putih) !important;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--hijau-gelap) !important;
        }
        
        .btn-secondary {
            background: var(--abu) !important;
            border: 1px solid var(--border) !important;
            color: var(--hitam) !important;
            border-radius: 12px;
        }
        
        .btn-secondary:hover {
            background: var(--abu-gelap) !important;
        }
        
        .btn-warning {
            background: var(--warning) !important;
            border: none !important;
            color: var(--hitam) !important;
            border-radius: 8px;
        }
        
        .btn-danger {
            background: var(--danger) !important;
            border: none !important;
            color: var(--putih) !important;
            border-radius: 8px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .badge.bg-danger {
            background: var(--danger) !important;
            color: var(--putih) !important;
        }
        
        .badge.bg-warning {
            background: var(--warning) !important;
            color: var(--hitam) !important;
        }
        
        .badge.bg-primary {
            background: var(--hijau) !important;
            color: var(--putih) !important;
        }
        
        .badge.bg-success {
            background: var(--hijau) !important;
            color: var(--putih) !important;
        }
        
        h2 {
            color: var(--hijau);
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--abu);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--hijau);
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats-badge {
            background: var(--hijau-transparan);
            border: 1px solid var(--hijau);
            color: var(--hijau);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-buttons .btn {
            transition: all 0.3s;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .user-info-cell {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--hitam);
        }
        
        .user-detail {
            font-size: 0.85rem;
            color: var(--abu-text);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--abu-text);
            margin-bottom: 15px;
        }
        
        /* Info Section */
        .info-section .role-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .admin-icon {
            background: var(--danger-transparan);
            color: var(--danger);
        }
        
        .petugas-icon {
            background: var(--warning-transparan);
            color: var(--warning);
        }
        
        .peminjam-icon {
            background: var(--hijau-transparan);
            color: var(--hijau);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-left">
                <h2 class="mb-0">
                    <i class="fas fa-users me-2"></i>Kelola User
                </h2>
                <span class="stats-badge">
                    <i class="fas fa-user-friends me-1"></i>
                    Total: <?php echo $users->rowCount(); ?> User
                </span>
            </div>
            <div class="action-buttons">
                <a href="../dashboard_admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Informasi User</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->rowCount() > 0): 
                                while ($user = $users->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                            <tr>
                                <td class="fw-bold" style="color: var(--hijau);">
                                    #<?php echo $user['id_user']; ?>
                                </td>
                                <td>
                                    <div class="user-info-cell">
                                        <span class="user-name"><?php echo htmlspecialchars($user['nama']); ?></span>
                                        <span class="user-detail">ID: <?php echo $user['id_user']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: var(--hitam);">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] == 'admin' ? 'danger' : 
                                             ($user['role'] == 'petugas' ? 'warning' : 'primary'); 
                                    ?>">
                                        <i class="fas fa-user-tag me-1"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Aktif
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-warning" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['role'] != 'admin'): ?>
                                        <button class="btn btn-danger" title="Hapus User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary disabled" title="Admin tidak dapat dihapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <h5 class="mt-3 mb-2" style="color: var(--abu-text);">
                                            Belum ada data user
                                        </h5>
                                        <p class="text-muted mb-0">
                                            Semua user akan ditampilkan di sini
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="card mt-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="role-icon admin-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" style="color: var(--danger);">Admin</h6>
                                <small class="text-muted">Hak akses penuh sistem</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="role-icon petugas-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" style="color: var(--warning);">Petugas</h6>
                                <small class="text-muted">Mengelola peminjaman</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="role-icon peminjam-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" style="color: var(--hijau);">Peminjam</h6>
                                <small class="text-muted">Hanya dapat meminjam</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efek hover untuk tombol aksi
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                if (!this.classList.contains('disabled')) {
                    this.style.transform = 'scale(1.1)';
                    this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
                }
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Efek hover untuk baris tabel
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // Konfirmasi sebelum hapus user
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Update statistik badge
        document.addEventListener('DOMContentLoaded', function() {
            const totalUsers = <?php echo $users->rowCount(); ?>;
            const statsBadge = document.querySelector('.stats-badge');
            if (statsBadge) {
                const icon = statsBadge.querySelector('i');
                statsBadge.innerHTML = `<i class="${icon.className}"></i> Total: ${totalUsers} User`;
            }
        });
    </script>
</body>
</html>