<?php
session_start();
require_once '../../src/config/database.php';
require_once '../../src/models/Alat.php';
require_once '../../src/models/Kategori.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$alatModel = new Alat();
$kategoriModel = new Kategori();

$success_msg = '';
$error_msg = '';

// Handle actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// GET semua kategori untuk dropdown
$kategori_options = $kategoriModel->getAllKategori();

// TAMBAH ALAT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $id_kategori = $_POST['id_kategori'] ?? '';
    $jumlah_total = $_POST['jumlah_total'] ?? 0;
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kondisi = $_POST['kondisi'] ?? 'baik';

    if (empty($nama_alat) || empty($id_kategori) || $jumlah_total <= 0) {
        $error_msg = "Nama alat, kategori, dan jumlah total harus diisi!";
    } else {
        $result = $alatModel->tambahAlat($nama_alat, $id_kategori, $jumlah_total, $deskripsi, $kondisi);

        if ($result['success']) {
            $success_msg = "Alat berhasil ditambahkan!";
            header("refresh:3;url=alat.php");
        } else {
            $error_msg = $result['message'];
        }
    }
}

// EDIT ALAT - Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $id_kategori = $_POST['id_kategori'] ?? '';
    $jumlah_total = $_POST['jumlah_total'] ?? 0;
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kondisi = $_POST['kondisi'] ?? 'baik';

    if (empty($nama_alat) || empty($id_kategori) || $jumlah_total <= 0) {
        $error_msg = "Nama alat, kategori, dan jumlah total harus diisi!";
    } else {
        $result = $alatModel->updateAlat($id, $nama_alat, $id_kategori, $jumlah_total, $deskripsi, $kondisi);

        if ($result['success']) {
            $success_msg = "Alat berhasil diperbarui!";
            header("refresh:3;url=alat.php");
        } else {
            $error_msg = $result['message'];
        }
    }
}

// HAPUS ALAT
if ($action === 'delete' && $id > 0) {
    $result = $alatModel->hapusAlat($id);
    
    if ($result['success']) {
        $success_msg = "Alat berhasil dihapus!";
        header("refresh:3;url=alat.php");
    } else {
        $error_msg = $result['message'];
    }
}

// GET alat untuk edit
$edit_alat = null;
if ($action === 'edit' && $id > 0) {
    $edit_alat = $alatModel->getAlatById($id);
    if (!$edit_alat) {
        $error_msg = "Alat tidak ditemukan!";
        header("refresh:2;url=alat.php");
        exit();
    }
}

// GET semua alat untuk tabel
$alat = $alatModel->getAllAlat();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($action === 'add' ? 'Tambah' : ($action === 'edit' ? 'Edit' : 'Data')); ?> Alat - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama (Sama dengan dashboard.php) */
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
        }
        
        .container {
            padding: 20px;
        }
        
        .form-container {
            max-width: 800px;
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
        }
        
        .table {
            color: var(--hitam) !important;
            border-color: var(--border) !important;
        }
        
        .table thead th {
            background-color: var(--abu) !important;
            border-color: var(--border) !important;
            color: var(--hitam) !important;
        }
        
        .table tbody tr {
            border-color: var(--border) !important;
        }
        
        .table tbody tr:hover {
            background-color: var(--hijau-transparan) !important;
        }
        
        .form-control, .form-select {
            background-color: var(--putih) !important;
            border: 1px solid var(--border) !important;
            color: var(--hitam) !important;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--putih) !important;
            border-color: var(--hijau) !important;
            color: var(--hitam) !important;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        
        .form-label {
            color: var(--hitam);
        }
        
        .btn-primary {
            background: var(--hijau) !important;
            border: none !important;
            color: var(--putih) !important;
        }
        
        .btn-primary:hover {
            background: var(--hijau-gelap) !important;
        }
        
        .btn-secondary {
            background: var(--abu) !important;
            border: 1px solid var(--border) !important;
            color: var(--hitam) !important;
        }
        
        .btn-secondary:hover {
            background: var(--abu-gelap) !important;
        }
        
        .btn-warning {
            background: var(--warning) !important;
            border: none !important;
            color: var(--hitam) !important;
        }
        
        .btn-danger {
            background: var(--danger) !important;
            border: none !important;
            color: var(--putih) !important;
        }
        
        .btn-info {
            background: var(--info) !important;
            border: none !important;
            color: var(--putih) !important;
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
        
        .badge {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .badge.bg-success {
            background: var(--hijau) !important;
            color: var(--putih) !important;
        }
        
        .badge.bg-danger {
            background: var(--danger) !important;
            color: var(--putih) !important;
        }
        
        .badge.bg-warning {
            background: var(--warning) !important;
            color: var(--hitam) !important;
        }
        
        .badge.bg-secondary {
            background: var(--abu-text) !important;
            color: var(--putih) !important;
        }
        
        .badge.bg-info {
            background: var(--info) !important;
            color: var(--putih) !important;
        }
        
        .btn-group .btn {
            border-radius: 8px !important;
            margin: 0 2px;
        }
        
        h2 {
            color: var(--hijau);
            font-weight: 600;
        }
        
        .btn-custom {
            min-width: 120px;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        /* Animasi sederhana untuk notifikasi */
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
        
        .text-muted {
            color: var(--abu-text) !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <!-- TAMPILAN TAMBAH/EDIT FORM -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
        
        <div class="form-container">
            <h2 class="mb-4">
                <i class="fas <?php echo $action === 'add' ? 'fa-plus' : 'fa-edit'; ?> me-2"></i>
                <?php echo $action === 'add' ? 'Tambah Alat Baru' : 'Edit Alat'; ?>
            </h2>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show alert-slide" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-slide" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $id : ''; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama_alat" class="form-label fw-bold">
                            <i class="fas fa-toolbox me-1"></i>Nama Alat
                        </label>
                        <input type="text" class="form-control" id="nama_alat" name="nama_alat" 
                               value="<?php echo $edit_alat['nama_alat'] ?? ''; ?>" 
                               placeholder="Masukkan nama alat" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="id_kategori" class="form-label fw-bold">
                            <i class="fas fa-tags me-1"></i>Kategori
                        </label>
                        <select class="form-select" id="id_kategori" name="id_kategori" required>
                            <option value="">Pilih Kategori</option>
                            <?php
                            while ($kategori = $kategori_options->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <option value="<?php echo $kategori['id_kategori']; ?>" 
                                <?php echo ($edit_alat['id_kategori'] ?? '') == $kategori['id_kategori'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jumlah_total" class="form-label fw-bold">
                            <i class="fas fa-boxes me-1"></i>Jumlah Total
                        </label>
                        <input type="number" class="form-control" id="jumlah_total" name="jumlah_total"
                               value="<?php echo $edit_alat['jumlah_total'] ?? ''; ?>"
                               min="1" placeholder="Masukkan jumlah total" required>
                        <small class="text-muted">Jumlah alat yang tersedia di inventaris</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="kondisi" class="form-label fw-bold">
                            <i class="fas fa-info-circle me-1"></i>Kondisi Alat
                        </label>
                        <select class="form-select" id="kondisi" name="kondisi" required>
                            <option value="baik" <?php echo ($edit_alat['kondisi'] ?? 'baik') == 'baik' ? 'selected' : ''; ?>>Baik</option>
                            <option value="kurang_baik" <?php echo ($edit_alat['kondisi'] ?? '') == 'kurang_baik' ? 'selected' : ''; ?>>Kurang Baik</option>
                            <option value="rusak" <?php echo ($edit_alat['kondisi'] ?? '') == 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                            <option value="masih_bisa_digunakan" <?php echo ($edit_alat['kondisi'] ?? '') == 'masih_bisa_digunakan' ? 'selected' : ''; ?>>Masih Bisa Digunakan</option>
                        </select>
                        <small class="text-muted">Pilih kondisi alat saat ini</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-check-circle me-1"></i>Status Tersedia
                        </label>
                        <div class="form-control bg-light">
                            <?php if ($action === 'edit'): ?>
                                <span class="badge bg-<?php echo ($edit_alat['jumlah_tersedia'] ?? 0) > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo $edit_alat['jumlah_tersedia'] ?? 0; ?> unit tersedia
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Akan sama dengan jumlah total saat pertama ditambahkan</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="deskripsi" class="form-label fw-bold">
                        <i class="fas fa-file-alt me-1"></i>Deskripsi
                    </label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" 
                              rows="3" placeholder="Masukkan deskripsi alat (opsional)"><?php echo $edit_alat['deskripsi'] ?? ''; ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="alat.php" class="btn btn-secondary btn-custom">
                        <i class="fas fa-times me-1"></i>Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-1"></i>
                        <?php echo $action === 'add' ? 'Simpan' : 'Update'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <?php else: ?>
        <!-- TAMPILAN TABEL DATA ALAT -->
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-tools me-2"></i>Data Alat
            </h2>
            <div class="d-flex align-items-center">
                <span class="badge bg-info me-3">
                    Total Alat: <?php echo $alat->rowCount(); ?>
                </span>
                <a href="../dashboard_admin.php" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
                <a href="alat.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Tambah Alat
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
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">No</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Tersedia</th>
                                <th class="text-center">Dipinjam</th>
                                <th>Deskripsi</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($alat->rowCount() > 0): 
                                $no = 1;
                                while ($item = $alat->fetch(PDO::FETCH_ASSOC)): 
                                    $dipinjam = $item['jumlah_total'] - $item['jumlah_tersedia'];
                            ?>
                                <tr>
                                    <td class="text-center fw-bold" style="color: var(--hijau);">
                                        <?php echo $no; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['nama_alat']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($item['nama_kategori'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold"><?php echo $item['jumlah_total']; ?></span>
                                        <small class="d-block text-muted">unit</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $item['jumlah_tersedia'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $item['jumlah_tersedia']; ?> unit
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning">
                                            <?php echo $dipinjam; ?> unit
                                        </span>
                                    </td>
                                    <td class="text-center">
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
                                        <?php if ($item['deskripsi']): ?>
                                            <small><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 50)); ?>...</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="alat.php?action=edit&id=<?php echo $item['id_alat']; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="alat.php?action=delete&id=<?php echo $item['id_alat']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus alat ini?')"
                                               title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $no++;
                                endwhile; 
                            ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fa-3x mb-3"></i>
                                            <h5 class="mb-2">Belum ada data alat</h5>
                                            <p class="mb-0">Klik tombol <strong>Tambah Alat</strong> untuk menambahkan alat baru</p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus pada input pertama di form
        <?php if ($action === 'add' || $action === 'edit'): ?>
        document.getElementById('nama_alat').focus();
        
        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const namaAlat = document.getElementById('nama_alat').value.trim();
            const jumlahTotal = document.getElementById('jumlah_total').value;
            
            if (namaAlat.length < 2) {
                e.preventDefault();
                alert('Nama alat minimal 2 karakter!');
                return false;
            }
            
            if (jumlahTotal <= 0) {
                e.preventDefault();
                alert('Jumlah total harus lebih dari 0!');
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
    </script>
</body>
</html>