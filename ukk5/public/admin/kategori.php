<?php
session_start();
require_once '../../src/config/database.php';
require_once '../../src/models/Kategori.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$kategoriModel = new Kategori();

$success_msg = '';
$error_msg = '';

// Handle actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// TAMBAH KATEGORI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($nama_kategori)) {
        $error_msg = "Nama kategori harus diisi!";
    } else {
        $result = $kategoriModel->tambahKategori($nama_kategori, $keterangan);

        if ($result['success']) {
            $success_msg = "Kategori berhasil ditambahkan!";
            header("refresh:3;url=kategori.php");
        } else {
            $error_msg = $result['message'];
        }
    }
}

// EDIT KATEGORI - Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($nama_kategori)) {
        $error_msg = "Nama kategori harus diisi!";
    } else {
        $result = $kategoriModel->updateKategori($id, $nama_kategori, $keterangan);

        if ($result['success']) {
            $success_msg = "Kategori berhasil diperbarui!";
            header("refresh:3;url=kategori.php");
        } else {
            $error_msg = $result['message'];
        }
    }
}

// HAPUS KATEGORI
if ($action === 'delete' && $id > 0) {
    $result = $kategoriModel->hapusKategori($id);

    if ($result['success']) {
        $success_msg = "Kategori berhasil dihapus!";
        header("refresh:3;url=kategori.php");
    } else {
        $error_msg = $result['message'];
    }
}

// GET kategori untuk edit
$edit_kategori = null;
if ($action === 'edit' && $id > 0) {
    $edit_kategori = $kategoriModel->getKategoriById($id);
    if (!$edit_kategori) {
        $error_msg = "Kategori tidak ditemukan!";
        header("refresh:2;url=kategori.php");
        exit();
    }
}

// GET semua kategori untuk tabel dengan jumlah alat
$kategori_with_count = $kategoriModel->getKategoriWithCount();
$total_alat_keseluruhan = $kategoriModel->getTotalAlat();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($action === 'add' ? 'Tambah' : ($action === 'edit' ? 'Edit' : 'Data')); ?> Kategori - Admin</title>
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
            min-height: 100vh;
        }
        
        .main-content {
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--putih);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .card {
            background: var(--putih) !important;
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
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
            font-size: 0.9rem;
            padding: 15px;
        }
        
        .table tbody tr {
            border-color: var(--border) !important;
            transition: background-color 0.3s;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: var(--hijau-transparan) !important;
        }
        
        .form-control, .form-select {
            background-color: var(--putih) !important;
            border: 1px solid var(--border) !important;
            color: var(--hitam) !important;
            border-radius: 12px;
            padding: 10px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--putih) !important;
            border-color: var(--hijau) !important;
            color: var(--hitam) !important;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        
        .btn-primary {
            background: var(--hijau) !important;
            border: none !important;
            color: var(--putih) !important;
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
        }
        
        .btn-primary:hover {
            background: var(--hijau-gelap) !important;
        }
        
        .btn-secondary {
            background: var(--abu) !important;
            border: 1px solid var(--border) !important;
            color: var(--hitam) !important;
            border-radius: 12px;
            padding: 10px 20px;
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
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .badge.bg-info {
            background: var(--info) !important;
            color: var(--putih) !important;
        }
        
        .badge.bg-secondary {
            background: var(--abu-text) !important;
            color: var(--putih) !important;
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
            font-weight: 700;
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
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 3.5rem;
            color: var(--abu-text);
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Animasi untuk notifikasi */
        .alert-slide {
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .alert {
            border: 1px solid transparent !important;
            border-radius: 12px !important;
        }
        
        .alert-success {
            background: var(--hijau-muda) !important;
            color: var(--hijau-gelap) !important;
            border-color: var(--hijau) !important;
        }
        
        .alert-danger {
            background: #f8d7da !important;
            color: #721c24 !important;
            border-color: #f5c6cb !important;
        }
        
        /* Stat Cards */
        .stat-cards {
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 16px;
            height: 100%;
            color: var(--putih);
        }
        
        .stat-card.total {
            background: var(--hijau);
        }
        
        .stat-card.alat {
            background: var(--info);
        }
        
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }
        
        .stat-card .stat-label {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            color: white;
        }
        
        /* Card Header */
        .card-header-custom {
            background: var(--abu) !important;
            border-bottom: 1px solid var(--border) !important;
            padding: 20px !important;
            border-radius: 16px 16px 0 0 !important;
        }
        
        .card-header-custom h5 {
            color: var(--hijau);
            font-weight: 600;
            margin: 0;
        }
        
        .text-muted {
            color: var(--abu-text) !important;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <!-- TAMPILAN TAMBAH/EDIT FORM -->
            <?php if ($action === 'add' || $action === 'edit'): ?>
            
            <div class="form-container">
                <h2 class="mb-4">
                    <i class="fas <?php echo $action === 'add' ? 'fa-plus' : 'fa-edit'; ?> me-2"></i>
                    <?php echo $action === 'add' ? 'Tambah Kategori Baru' : 'Edit Kategori'; ?>
                </h2>
                
                <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show alert-slide mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show alert-slide mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $id : ''; ?>">
                    <div class="mb-4">
                        <label for="nama_kategori" class="form-label fw-bold">
                            <i class="fas fa-tag me-1"></i>Nama Kategori
                        </label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                               value="<?php echo $edit_kategori['nama_kategori'] ?? ''; ?>"
                               placeholder="Masukkan nama kategori" required>
                    </div>

                    <div class="mb-4">
                        <label for="keterangan" class="form-label fw-bold">
                            <i class="fas fa-info-circle me-1"></i>Keterangan
                        </label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                                  placeholder="Masukkan keterangan kategori"><?php echo $edit_kategori['keterangan'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="kategori.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            <?php echo $action === 'add' ? 'Simpan' : 'Update'; ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <?php else: ?>
            <!-- TAMPILAN TABEL DATA KATEGORI -->
            
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-left">
                    <h2 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Data Kategori
                    </h2>
                    <span class="stats-badge">
                        <i class="fas fa-layer-group me-1"></i>
                        Total: <?php echo count($kategori_with_count); ?> Kategori
                    </span>
                </div>
                <div class="action-buttons">
                    <a href="../dashboard_admin.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                    <a href="kategori.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tambah Kategori
                    </a>
                </div>
            </div>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show alert-slide mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-slide mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row stat-cards">
                <div class="col-md-6 mb-3">
                    <div class="stat-card total">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo count($kategori_with_count); ?></div>
                                <div class="stat-label">Total Kategori</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-card alat">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $total_alat_keseluruhan; ?></div>
                                <div class="stat-label">Total Alat Semua Kategori</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kategori Table -->
            <div class="card">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Daftar Semua Kategori
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">No</th>
                                    <th>Nama Kategori</th>
                                    <th class="text-center">Jumlah Alat</th>
                                    <th class="text-center" style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($kategori_with_count)): 
                                    $no = 1;
                                    foreach ($kategori_with_count as $item): 
                                ?>
                                    <tr>
                                        <td class="fw-bold" style="color: var(--hijau);">
                                            <?php echo $no; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama_kategori']); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">
                                                <?php echo $item['jumlah_alat']; ?> alat
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="kategori.php?action=edit&id=<?php echo $item['id_kategori']; ?>"
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="kategori.php?action=delete&id=<?php echo $item['id_kategori']; ?>"
                                                   class="btn btn-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                    $no++;
                                    endforeach; 
                                ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-tags"></i>
                                                <h5 class="mt-3 mb-2">
                                                    Belum ada data kategori
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    Klik tombol <strong>Tambah Kategori</strong> untuk menambahkan kategori baru
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
            
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus pada input pertama di form
        <?php if ($action === 'add' || $action === 'edit'): ?>
        document.getElementById('nama_kategori').focus();
        
        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const namaKategori = document.getElementById('nama_kategori').value.trim();
            
            if (namaKategori.length < 2) {
                e.preventDefault();
                alert('Nama kategori minimal 2 karakter!');
                return false;
            }
            
            return true;
        });
        <?php endif; ?>
        
        // Efek hover untuk tombol aksi
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
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
        
        // Efek hover untuk stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.1)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Efek hover untuk cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Konfirmasi sebelum hapus kategori
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>