<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peminjam') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Alat.php';
require_once '../../src/models/Peminjaman.php';

$alatModel = new Alat();
$peminjamanModel = new Peminjaman();

$id_alat = $_GET['id'] ?? null;
if (!$id_alat) {
    header("Location: alat.php");
    exit();
}

$alat = $alatModel->getAlatById($id_alat);
if (!$alat) {
    header("Location: alat.php");
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'] ?? '';
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? '';
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $keterangan = $_POST['keterangan'] ?? '';

    // Validasi tanggal
    $today = date('Y-m-d');

    if (empty($tanggal_peminjaman) || empty($tanggal_pengembalian) || $jumlah <= 0) {
        $message = 'Semua field harus diisi dengan benar.';
        $message_type = 'danger';
    } elseif (!strtotime($tanggal_peminjaman) || !strtotime($tanggal_pengembalian)) {
        $message = 'Format tanggal tidak valid.';
        $message_type = 'danger';
    } elseif ($tanggal_peminjaman < $today) {
        $message = 'Tanggal peminjaman tidak boleh kurang dari hari ini.';
        $message_type = 'danger';
    } elseif ($jumlah > $alat['jumlah_tersedia']) {
        $message = 'Jumlah yang diminta melebihi stok tersedia.';
        $message_type = 'danger';
    } elseif ($tanggal_peminjaman > $tanggal_pengembalian) {
        $message = 'Tanggal pengembalian harus sama atau setelah tanggal peminjaman.';
        $message_type = 'danger';
    } else {
        // Use the Peminjaman model to create pending request
        $result = $peminjamanModel->createPeminjaman(
            $_SESSION['user_id'],
            $id_alat,
            $tanggal_peminjaman,
            $tanggal_pengembalian,
            $jumlah,
            $keterangan
        );

        if ($result['success']) {
            $message = 'Peminjaman berhasil diajukan dan menunggu persetujuan petugas!';
            $message_type = 'success';

            // Refresh data alat
            $alat = $alatModel->getAlatById($id_alat);
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Alat - Peminjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .sidebar {
            background: var(--dark-secondary) !important;
            border-right: 1px solid var(--dark-border);
        }

        .stat-card {
            background: var(--dark-card) !important;
            border: 1px solid var(--dark-border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3) !important;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--dark-card), var(--dark-secondary)) !important;
            border: 1px solid var(--dark-border);
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
            box-shadow: 0 2px 15px rgba(0,0,0,0.2) !important;
        }

        .text-muted {
            color: var(--dark-text-secondary) !important;
        }

        .border {
            border-color: var(--dark-border) !important;
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
            background: var(--dark-border);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        .badge {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Tombol Kembali */
        .btn-back {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: white !important;
            border: none !important;
            padding: 10px 20px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
        }

        .btn-back:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4) !important;
            color: white !important;
        }

        .btn-back:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
        }

        /* Styling untuk tombol pinjam/submit */
        .btn-submit {
            background: linear-gradient(135deg, var(--success), var(--success-dark)) !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important;
        }

        .btn-submit:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4) !important;
            color: white !important;
        }

        .btn-secondary {
            background: rgba(59, 130, 246, 0.1) !important;
            color: var(--primary) !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
        }

        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            color: var(--primary) !important;
        }

        /* Styling untuk form input */
        .form-control, .form-select {
            background-color: var(--dark-card) !important;
            color: var(--dark-text) !important;
            border: 1px solid var(--dark-border) !important;
            border-radius: 8px !important;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--dark-card) !important;
            color: var(--dark-text) !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25) !important;
        }

        .form-label {
            color: var(--dark-text) !important;
            font-weight: 600 !important;
            margin-bottom: 8px !important;
        }

        .input-group-text {
            background-color: var(--dark-secondary) !important;
            color: var(--dark-text) !important;
            border: 1px solid var(--dark-border) !important;
        }

        .form-text {
            color: var(--dark-text-secondary) !important;
            font-size: 0.85rem !important;
            margin-top: 6px !important;
        }

        .form-range::-webkit-slider-thumb {
            background: var(--primary) !important;
        }

        .form-range::-moz-range-thumb {
            background: var(--primary) !important;
        }

        .form-range::-ms-thumb {
            background: var(--primary) !important;
        }

        /* Styling untuk navbar */
        .navbar-brand {
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            color: var(--primary) !important;
            padding: 8px 0 !important;
            background: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        .navbar-brand:hover {
            color: var(--primary-dark) !important;
        }

        .badge-role {
            background: rgba(59, 130, 246, 0.2) !important;
            color: var(--primary) !important;
            padding: 4px 10px !important;
            border-radius: 20px !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
        }

        /* Styling untuk card utama */
        .main-card {
            background: linear-gradient(135deg, var(--dark-card), var(--dark-secondary)) !important;
            border: 1px solid var(--dark-border) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            overflow: hidden !important;
        }

        .main-card .card-header {
            background: linear-gradient(135deg, var(--dark-secondary), var(--dark-card)) !important;
            border-bottom: 1px solid var(--dark-border) !important;
            padding: 20px !important;
            border-radius: 20px 20px 0 0 !important;
        }

        .main-card .card-footer {
            background: linear-gradient(135deg, var(--dark-secondary), var(--dark-card)) !important;
            border-top: 1px solid var(--dark-border) !important;
            padding: 15px 20px !important;
            border-radius: 0 0 20px 20px !important;
        }

        /* Styling untuk alert */
        .alert {
            border: none !important;
            border-radius: 12px !important;
            padding: 15px 20px !important;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15) !important;
            color: var(--success) !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15) !important;
            color: var(--danger) !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.15) !important;
            color: var(--warning) !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
        }

        .alert-info {
            background: rgba(6, 182, 212, 0.15) !important;
            color: var(--info) !important;
            border: 1px solid rgba(6, 182, 212, 0.3) !important;
        }

        /* Styling untuk list group */
        .list-group-item {
            background-color: transparent !important;
            color: var(--dark-text) !important;
            border: none !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            padding: 12px 0 !important;
        }

        .list-group-item:last-child {
            border-bottom: none !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4" style="padding: 15px 0;">
        <div class="container">
            <a class="navbar-brand" href="../dashboard_peminjam.php">
                <i class="fas fa-user me-2"></i>
                <span>Peminjam Panel</span>
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <span class="nav-link me-3">
                    <i class="fas fa-user-circle me-1" style="color: var(--primary);"></i>
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                    <span class="badge-role ms-2">Peminjam</span>
                </span>
                <a class="nav-link" href="../logout.php" style="
                    color: var(--danger) !important;
                    background: rgba(239, 68, 68, 0.1);
                    padding: 8px 15px;
                    border-radius: 12px;
                    border: 1px solid rgba(239, 68, 68, 0.3);
                    font-weight: 500;
                    transition: all 0.3s;
                ">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-2" style="font-weight: 600;">
                    <i class="fas fa-hand-holding me-2" style="color: var(--primary);"></i>Form Peminjaman Alat
                </h2>
                <p class="text-muted mb-0" style="font-size: 1rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Form peminjaman dengan persetujuan petugas
                </p>
            </div>
            <a href="alat.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Alat
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> mb-4" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-2 fw-bold">
                        <?php echo $message_type === 'success' ? 'Berhasil!' : 'Perhatian!'; ?>
                    </h5>
                    <p class="mb-0"><?php echo $message; ?></p>
                    <?php if ($message_type === 'success'): ?>
                    <div class="mt-3 p-3 rounded" style="background: rgba(16, 185, 129, 0.1);">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-clock me-2" style="color: var(--warning);"></i>
                            <span class="fw-bold" style="color: var(--warning);">Peminjaman Menunggu Persetujuan!</span>
                        </div>
                        <small class="text-muted">
                            Peminjaman Anda telah diajukan dan sedang menunggu persetujuan dari petugas.
                            Anda akan menerima notifikasi setelah permintaan disetujui atau ditolak.
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="main-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0" style="font-weight: 600;">
                            <i class="fas fa-calendar-alt me-2" style="color: var(--primary);"></i>Form Peminjaman
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="peminjamanForm">
                            <!-- Tanggal Peminjaman -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="tanggal_peminjaman" class="form-label">
                                        <i class="fas fa-calendar-plus me-2"></i>Tanggal Peminjaman
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        <input type="date" class="form-control" id="tanggal_peminjaman" name="tanggal_peminjaman"
                                               value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-text">Pilih tanggal mulai peminjaman</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="tanggal_pengembalian" class="form-label">
                                        <i class="fas fa-calendar-minus me-2"></i>Tanggal Pengembalian
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar-check"></i>
                                        </span>
                                        <input type="date" class="form-control" id="tanggal_pengembalian" name="tanggal_pengembalian"
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                    <div class="form-text">Pilih tanggal pengembalian</div>
                                </div>
                            </div>

                            <!-- Jumlah & Durasi -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="jumlah" class="form-label">
                                        <i class="fas fa-boxes me-2"></i>Jumlah yang Dipinjam
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-cube"></i>
                                        </span>
                                        <input type="number" class="form-control" id="jumlah" name="jumlah"
                                               min="1" max="<?php echo $alat['jumlah_tersedia']; ?>" value="1" required>
                                        <span class="input-group-text">unit</span>
                                    </div>
                                    <div class="form-text">
                                        Stok tersedia: <strong style="color: var(--success);"><?php echo $alat['jumlah_tersedia']; ?></strong> unit
                                    </div>
                                    <div class="mt-3">
                                        <input type="range" class="form-range" id="jumlahRange" min="1" max="<?php echo $alat['jumlah_tersedia']; ?>" value="1">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">1 unit</small>
                                            <small class="text-muted"><?php echo $alat['jumlah_tersedia']; ?> unit</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-clock me-2"></i>Durasi Peminjaman
                                    </label>
                                    <div class="card" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3);">
                                        <div class="card-body text-center py-4">
                                            <h1 class="mb-2" id="durasiHari" style="color: var(--primary); font-weight: 700;">0</h1>
                                            <p class="mb-0 text-muted">Hari</p>
                                            <small id="durasiDetail" class="text-muted">-</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Keterangan -->
                            <div class="mb-4">
                                <label for="keterangan" class="form-label">
                                    <i class="fas fa-sticky-note me-2"></i>Keterangan Peminjaman
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-edit"></i>
                                    </span>
                                    <textarea class="form-control" id="keterangan" name="keterangan" rows="4"
                                              placeholder="Jelaskan keperluan peminjaman Anda (contoh: Untuk acara seminar, praktikum lab, dll)..."><?php echo htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-text">Opsional, namun membantu memahami kebutuhan Anda</div>
                            </div>

                            <!-- Alert Peringatan -->
                            <div class="alert alert-warning mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3"></i>
                                    <div>
                                        <h6 class="mb-2 fw-bold">Ketentuan Peminjaman</h6>
                                        <ul class="mb-0 ps-3" style="font-size: 0.9rem;">
                                            <li>Alat harus dikembalikan sesuai jadwal yang ditentukan</li>
                                            <li>Bertanggung jawab atas kerusakan atau kehilangan alat</li>
                                            <li>Denda keterlambatan: Rp 5.000 per hari</li>
                                            <li>Segera laporkan jika ada kerusakan saat peminjaman</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Submit -->
                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" class="btn btn-submit flex-grow-1">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Ajukan Peminjaman
                                </button>
                                <a href="alat.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Batal
                                </a>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-lock me-1"></i>
                                    Data Anda aman dan terenkripsi
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Detail Alat -->
                <div class="main-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0" style="font-weight: 600;">
                            <i class="fas fa-tools me-2" style="color: var(--primary);"></i>Detail Alat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="mb-3" style="
                                width: 80px;
                                height: 80px;
                                background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                                border-radius: 16px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto;
                                box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
                            ">
                                <i class="fas fa-tools fa-2x" style="color: white;"></i>
                            </div>
                            <h5 class="mb-2" style="color: var(--dark-text);"><?php echo htmlspecialchars($alat['nama_alat']); ?></h5>
                            <?php if (!empty($alat['kode_alat'])): ?>
                            <span class="badge" style="background: rgba(6, 182, 212, 0.1); color: var(--info); padding: 6px 12px; border-radius: 20px;">
                                <i class="fas fa-barcode me-1"></i><?php echo htmlspecialchars($alat['kode_alat']); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Kategori:</span>
                                <span class="badge" style="background: rgba(139, 92, 246, 0.1); color: var(--purple); padding: 6px 12px; border-radius: 20px;">
                                    <?php echo htmlspecialchars($alat['nama_kategori'] ?? '-'); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Stok Tersedia:</span>
                                <span class="badge" style="
                                    background: <?php echo $alat['jumlah_tersedia'] > 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>;
                                    color: <?php echo $alat['jumlah_tersedia'] > 0 ? 'var(--success)' : 'var(--danger)'; ?>;
                                    padding: 6px 12px;
                                    border-radius: 20px;
                                    font-weight: 600;
                                ">
                                    <?php echo $alat['jumlah_tersedia']; ?> unit
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Status:</span>
                                <?php if ($alat['jumlah_tersedia'] > 5): ?>
                                <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 6px 12px; border-radius: 20px;">
                                    <i class="fas fa-check-circle me-1"></i>Tersedia
                                </span>
                                <?php elseif ($alat['jumlah_tersedia'] > 0): ?>
                                <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: var(--warning); padding: 6px 12px; border-radius: 20px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Terbatas
                                </span>
                                <?php else: ?>
                                <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 6px 12px; border-radius: 20px;">
                                    <i class="fas fa-times-circle me-1"></i>Habis
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Kondisi:</span>
                                <?php
                                $kondisi_class = 'secondary';
                                $kondisi_text = 'Tidak Diketahui';
                                switch ($alat['kondisi']) {
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
                                <span class="badge" style="background: rgba(<?php
                                    switch ($kondisi_class) {
                                        case 'success': echo '16, 185, 129'; break;
                                        case 'warning': echo '245, 158, 11'; break;
                                        case 'danger': echo '239, 68, 68'; break;
                                        case 'info': echo '6, 182, 212'; break;
                                        default: echo '108, 117, 125'; break;
                                    }
                                ?>, 0.1); color: var(--<?php echo $kondisi_class; ?>); padding: 6px 12px; border-radius: 20px;">
                                    <i class="fas fa-info-circle me-1"></i><?php echo $kondisi_text; ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($alat['deskripsi'])): ?>
                        <div class="mb-3">
                            <h6 class="mb-2" style="color: var(--dark-text);">Deskripsi:</h6>
                            <p class="text-muted mb-0" style="font-size: 0.9rem; line-height: 1.5;">
                                <?php echo htmlspecialchars($alat['deskripsi']); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($alat['spesifikasi'])): ?>
                        <div class="mb-3">
                            <h6 class="mb-2" style="color: var(--dark-text);">Spesifikasi:</h6>
                            <p class="text-muted mb-0" style="font-size: 0.9rem; line-height: 1.5;">
                                <?php echo htmlspecialchars($alat['spesifikasi']); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Info Sistem -->
                        <div class="mt-4 pt-3 border-top border-dark">
                            <h6 class="mb-3" style="color: var(--dark-text);">
                                <i class="fas fa-info-circle me-2" style="color: var(--info);"></i>Sistem Peminjaman
                            </h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-user-check me-3" style="color: var(--primary);"></i>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">Persetujuan Petugas</div>
                                        <small class="text-muted">Perlu persetujuan dari petugas</small>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-clock me-3" style="color: var(--warning);"></i>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">Tepat Waktu</div>
                                        <small class="text-muted">Harus dikembalikan tepat waktu</small>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-history me-3" style="color: var(--info);"></i>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">Terpantau</div>
                                        <small class="text-muted">Semua aktivitas terekam</small>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum return date based on loan date
        const loanInput = document.getElementById('tanggal_peminjaman');
        const returnInput = document.getElementById('tanggal_pengembalian');
        const jumlahInput = document.getElementById('jumlah');
        const jumlahRange = document.getElementById('jumlahRange');
        const durasiHari = document.getElementById('durasiHari');
        const durasiDetail = document.getElementById('durasiDetail');

        function updateReturnDate() {
            const loanDate = new Date(loanInput.value);
            const returnDate = new Date(loanDate);
            returnDate.setDate(returnDate.getDate() + 1);

            returnInput.min = returnDate.toISOString().split('T')[0];

            if (!returnInput.value || new Date(returnInput.value) <= loanDate) {
                // Set default to 3 days from loan date
                returnDate.setDate(loanDate.getDate() + 3);
                returnInput.value = returnDate.toISOString().split('T')[0];
            }
            
            calculateDuration();
        }

        function calculateDuration() {
            if (loanInput.value && returnInput.value) {
                const start = new Date(loanInput.value);
                const end = new Date(returnInput.value);
                
                // Calculate difference in days
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                durasiHari.textContent = diffDays;
                
                // Format dates for display
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const startFormatted = start.toLocaleDateString('id-ID', options);
                const endFormatted = end.toLocaleDateString('id-ID', options);
                
                durasiDetail.textContent = `${startFormatted} - ${endFormatted}`;
            }
        }

        // Sync range slider with number input
        function syncJumlahInputs() {
            jumlahInput.value = jumlahRange.value;
            jumlahRange.value = jumlahInput.value;
        }

        // Event listeners
        loanInput.addEventListener('change', updateReturnDate);
        returnInput.addEventListener('change', calculateDuration);
        jumlahInput.addEventListener('input', syncJumlahInputs);
        jumlahRange.addEventListener('input', syncJumlahInputs);

        // Validate jumlah input
        jumlahInput.addEventListener('change', function() {
            const max = parseInt(this.max);
            const value = parseInt(this.value);
            
            if (value > max) {
                this.value = max;
                alert('Jumlah tidak boleh melebihi stok tersedia (' + max + ' unit)');
            }
            
            if (value < 1) {
                this.value = 1;
            }
            
            syncJumlahInputs();
        });

        // Form validation
        document.getElementById('peminjamanForm').addEventListener('submit', function(e) {
            const jumlah = parseInt(jumlahInput.value);
            const max = parseInt(jumlahInput.max);
            
            if (jumlah > max) {
                e.preventDefault();
                alert('Jumlah yang diminta melebihi stok tersedia. Stok tersedia: ' + max + ' unit');
                jumlahInput.focus();
                return false;
            }
            
            if (jumlah < 1) {
                e.preventDefault();
                alert('Jumlah minimal adalah 1 unit');
                jumlahInput.focus();
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize range slider max value
            const maxStok = <?php echo $alat['jumlah_tersedia']; ?>;
            jumlahRange.max = maxStok;
            
            // Set initial values
            updateReturnDate();
            syncJumlahInputs();
            calculateDuration();
        });
    </script>
</body>
</html>