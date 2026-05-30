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
require_once '../../src/models/User.php';

$peminjamanModel = new Peminjaman();
$alatModel = new Alat();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_peminjaman'])) {
    $alat_id = $_POST['alat_id'] ?? '';
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'] ?? '';
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Validate dates
    $today = date('Y-m-d');
    if ($tanggal_peminjaman < $today) {
        $error_message = 'Tanggal peminjaman tidak boleh kurang dari hari ini.';
    } elseif ($tanggal_pengembalian <= $tanggal_peminjaman) {
        $error_message = 'Tanggal pengembalian harus lebih besar dari tanggal peminjaman.';
    } else {
        // Check if alat is available
        $alat = $alatModel->getAlatById($alat_id);
        if ($alat && $alat['jumlah_tersedia'] > 0) {
            // Create peminjaman
            $data = [
                'user_id' => $_SESSION['user_id'],
                'alat_id' => $alat_id,
                'tanggal_peminjaman' => $tanggal_peminjaman,
                'tanggal_pengembalian' => $tanggal_pengembalian,
                'keterangan' => $keterangan,
                'status' => 'pending'
            ];
            
            $result = $peminjamanModel->createPeminjaman($data['user_id'], $data['alat_id'], $data['tanggal_peminjaman'], $data['tanggal_pengembalian'], 1, $data['keterangan']);
            if ($result['success']) {
                $success_message = $result['message'] . ' Alat siap untuk dikembalikan.';
                // Redirect to pengembalian page
                header("Location: pengembalian.php?success_pinjam=1");
                exit();
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = 'Alat tidak tersedia untuk dipinjam.';
        }
    }
}

// Get all available alat
$alat_stmt = $alatModel->getAllAlat();
$alat_data = $alat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending peminjaman for this user
$pendingPeminjaman = $peminjamanModel->getPeminjamanByUserAndStatus($_SESSION['user_id'], 'pending');
if ($pendingPeminjaman) {
    $pendingPeminjaman->execute();
}

// Get all peminjaman for this user for statistics
$allPeminjaman = $peminjamanModel->getPeminjamanByUser($_SESSION['user_id']);
if ($allPeminjaman) {
    $allPeminjaman->execute();
    $allPeminjamanData = $allPeminjaman->fetchAll(PDO::FETCH_ASSOC);
} else {
    $allPeminjamanData = [];
}

// Debug: Tampilkan struktur data untuk peminjaman
if (!empty($allPeminjamanData)) {
    error_log("Struktur data peminjaman: " . print_r($allPeminjamanData[0], true));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Alat - Peminjam</title>
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

        /* Utility Classes */
        .text-hijau { color: var(--hijau) !important; }
        .bg-hijau { background-color: var(--hijau) !important; }
        .border-hijau { border-color: var(--hijau) !important; }

        .text-hitam { color: var(--hitam) !important; }
        .bg-hitam { background-color: var(--hitam) !important; }

        .text-abu { color: var(--abu-text) !important; }

        /* Alat Card */
        .alat-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .alat-card:hover {
            border-color: var(--hijau);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.1);
        }
        
        .alat-card.selected {
            border-color: var(--hijau);
            background-color: var(--hijau-transparan);
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
        <!-- Messages -->
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-2" style="font-weight: 600; color: var(--hijau);">
                    <i class="fas fa-handshake me-2"></i>Peminjaman Alat
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Ajukan peminjaman alat yang tersedia
                </p>
            </div>
            <a href="../dashboard_peminjam.php" class="btn btn-outline-hijau">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>

        <!-- Statistik Ringkas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-check-circle me-1"></i>Alat Tersedia
                                </h6>
                                <h3 class="mb-0" style="color: var(--hijau); font-weight: 600;">
                                    <?php
                                    $tersedia = 0;
                                    foreach ($alat_data as $item) {
                                        if (isset($item['jumlah_tersedia']) && $item['jumlah_tersedia'] > 0) {
                                            $tersedia++;
                                        }
                                    }
                                    echo $tersedia;
                                    ?>
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
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-clock me-1"></i>Menunggu
                                </h6>
                                <h3 class="mb-0" style="color: #ffc107; font-weight: 600;">
                                    <?php
                                    $countPending = 0;
                                    if ($pendingPeminjaman) {
                                        while ($row = $pendingPeminjaman->fetch(PDO::FETCH_ASSOC)) {
                                            $countPending++;
                                        }
                                        $pendingPeminjaman->execute(); // Reset cursor
                                    }
                                    echo $countPending;
                                    ?>
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
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-clipboard-check me-1"></i>Aktif
                                </h6>
                                <h3 class="mb-0" style="color: #17a2b8; font-weight: 600;">
                                    <?php
                                    $countActive = 0;
                                    foreach ($allPeminjamanData as $row) {
                                        // Cek semua kemungkinan nama kolom status
                                        if (isset($row['status']) && $row['status'] == 'disetujui') {
                                            $countActive++;
                                        }
                                        elseif (isset($row['status']) && $row['status'] == 'menunggu_konfirmasi') {
                                            $countActive++;
                                        }
                                        elseif (isset($row['status_peminjaman']) && $row['status_peminjaman'] == 'disetujui') {
                                            $countActive++;
                                        }
                                        elseif (isset($row['Status']) && $row['Status'] == 'disetujui') {
                                            $countActive++;
                                        }
                                    }
                                    echo $countActive;
                                    ?>
                                </h3>
                            </div>
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(23, 162, 184, 0.1);
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-clipboard-check fa-lg" style="color: #17a2b8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-tools me-1"></i>Total Alat
                                </h6>
                                <h3 class="mb-0" style="color: var(--hitam); font-weight: 600;">
                                    <?php echo count($alat_data); ?>
                                </h3>
                            </div>
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(33, 37, 41, 0.1);
                                border-radius: 10px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">
                                <i class="fas fa-tools fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Alat List -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" style="font-weight: 600; color: var(--hijau);">
                            <i class="fas fa-tools me-2"></i>Daftar Alat Tersedia
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="alat-list" style="max-height: 500px; overflow-y: auto;">
                            <?php
                            $hasAvailable = false;
                            
                            foreach ($alat_data as $item):
                                if (isset($item['jumlah_tersedia']) && $item['jumlah_tersedia'] > 0):
                                    $hasAvailable = true;
                            ?>
                            <div class="alat-card position-relative"
                                 data-id="<?php echo htmlspecialchars($item['id_alat'] ?? ''); ?>"
                                 data-name="<?php echo htmlspecialchars($item['nama_alat'] ?? '', ENT_QUOTES); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-hijau p-3 text-white">
                                            <i class="fas fa-tools fa-lg"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['nama_alat']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-barcode me-1"></i>
                                            Kode: <?php echo htmlspecialchars($item['kode_alat'] ?? 'N/A'); ?>
                                        </p>
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-box me-1"></i>
                                            Stok: <?php echo $item['jumlah_tersedia']; ?> unit
                                        </p>
                                        <?php if (!empty($item['nama_kategori'])): ?>
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-tag me-1"></i>
                                            Kategori: <?php echo htmlspecialchars($item['nama_kategori']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if (!empty($item['kondisi'])): ?>
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Kondisi:
                                            <?php
                                            $kondisiClass = '';
                                            $kondisiText = '';
                                            switch ($item['kondisi']) {
                                                case 'baik':
                                                    $kondisiClass = 'badge-success';
                                                    $kondisiText = 'Baik';
                                                    break;
                                                case 'kurang_baik':
                                                    $kondisiClass = 'badge-warning';
                                                    $kondisiText = 'Kurang Baik';
                                                    break;
                                                case 'rusak':
                                                    $kondisiClass = 'badge-danger';
                                                    $kondisiText = 'Rusak';
                                                    break;
                                                case 'masih_bisa_digunakan':
                                                    $kondisiClass = 'badge-info';
                                                    $kondisiText = 'Masih Bisa Digunakan';
                                                    break;
                                                default:
                                                    $kondisiClass = 'badge-secondary';
                                                    $kondisiText = ucfirst($item['kondisi']);
                                            }
                                            ?>
                                            <span class="badge <?php echo $kondisiClass; ?> ms-1"><?php echo $kondisiText; ?></span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge badge-success status-badge">Tersedia</span>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$hasAvailable): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada alat yang tersedia untuk dipinjam</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form Peminjaman -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" style="font-weight: 600; color: var(--hijau);">
                            <i class="fas fa-file-signature me-2"></i>Form Peminjaman
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="peminjamanForm" method="POST" action="">
                            <input type="hidden" id="selected_alat_id" name="alat_id" value="">
                            
                            <div class="mb-3">
                                <label for="alat_nama" class="form-label">Alat yang Dipilih</label>
                                <input type="text" class="form-control" id="alat_nama" readonly placeholder="Pilih alat dari daftar di samping">
                                <div class="form-text text-muted">Klik pada alat yang ingin dipinjam</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tanggal_peminjaman" class="form-label">Tanggal Peminjaman</label>
                                <input type="date" class="form-control" id="tanggal_peminjaman" name="tanggal_peminjaman" required 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text text-muted">Minimal tanggal hari ini</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tanggal_pengembalian" class="form-label">Tanggal Pengembalian</label>
                                <input type="date" class="form-control" id="tanggal_pengembalian" name="tanggal_pengembalian" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                <div class="form-text text-muted">Harus lebih dari tanggal peminjaman</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                          placeholder="Tujuan peminjaman alat..."></textarea>
                                <div class="form-text text-muted">Opsional</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="submit_peminjaman" class="btn btn-hijau btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Ajukan Peminjaman
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="text-hijau mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Informasi Penting
                            </h6>
                            <ul class="small mb-0">
                                <li>Peminjaman harus diajukan minimal 1 hari sebelum pemakaian</li>
                                <li>Status peminjaman akan ditinjau oleh admin</li>
                                <li>Anda akan menerima notifikasi ketika peminjaman disetujui/ditolak</li>
                                <li>Pastikan untuk mengembalikan alat tepat waktu</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Peminjaman Menunggu Persetujuan -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0" style="font-weight: 600; color: var(--hijau);">
                    <i class="fas fa-clock me-2"></i>Peminjaman Menunggu Persetujuan
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
                                <th>Keterangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasPending = false;
                            if ($pendingPeminjaman) {
                                $pendingPeminjaman->execute(); // Reset cursor for display
                                
                                while ($row = $pendingPeminjaman->fetch(PDO::FETCH_ASSOC)):
                                    $hasPending = true;
                            ?>
                            <tr>
                                <td><strong>#<?php echo $row['id_peminjaman'] ?? $row['id'] ?? 'N/A'; ?></strong></td>
                                <td>
                                    <i class="fas fa-tools me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($row['nama_alat'] ?? 'Unknown'); ?>
                                </td>
                                <td><?php echo isset($row['tanggal_peminjaman']) ? date('d/m/Y', strtotime($row['tanggal_peminjaman'])) : 'N/A'; ?></td>
                                <td><?php echo isset($row['tanggal_pengembalian']) ? date('d/m/Y', strtotime($row['tanggal_pengembalian'])) : 'N/A'; ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo isset($row['keterangan']) && $row['keterangan'] ? htmlspecialchars($row['keterangan']) : '-'; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php 
                                        // Cek semua kemungkinan nama kolom status
                                        if (isset($row['status'])) {
                                            echo ucfirst($row['status']);
                                        } elseif (isset($row['status_peminjaman'])) {
                                            echo ucfirst($row['status_peminjaman']);
                                        } elseif (isset($row['Status'])) {
                                            echo ucfirst($row['Status']);
                                        } else {
                                            echo 'Pending';
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            }
                            
                            if (!$hasPending): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-check-circle me-2 text-success"></i>
                                    Tidak ada peminjaman yang menunggu persetujuan
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle alat selection
        document.querySelectorAll('.alat-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.alat-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Set form values
                const alatId = this.getAttribute('data-id');
                const alatName = this.getAttribute('data-name');
                
                document.getElementById('selected_alat_id').value = alatId;
                document.getElementById('alat_nama').value = alatName;
            });
        });

        // Set min date for pengembalian based on peminjaman date
        const tanggalPeminjaman = document.getElementById('tanggal_peminjaman');
        const tanggalPengembalian = document.getElementById('tanggal_pengembalian');
        
        tanggalPeminjaman.addEventListener('change', function() {
            const minDate = new Date(this.value);
            minDate.setDate(minDate.getDate() + 1);
            
            const minDateString = minDate.toISOString().split('T')[0];
            tanggalPengembalian.min = minDateString;
            
            // Reset pengembalian date if it's now invalid
            if (tanggalPengembalian.value && tanggalPengembalian.value <= this.value) {
                tanggalPengembalian.value = minDateString;
            }
        });

        // Form validation
        document.getElementById('peminjamanForm').addEventListener('submit', function(e) {
            const selectedAlat = document.getElementById('selected_alat_id').value;
            const tanggalPinjam = document.getElementById('tanggal_peminjaman').value;
            const tanggalKembali = document.getElementById('tanggal_pengembalian').value;
            
            if (!selectedAlat) {
                e.preventDefault();
                alert('Silakan pilih alat yang ingin dipinjam.');
                return;
            }
            
            if (!tanggalPinjam || !tanggalKembali) {
                e.preventDefault();
                alert('Silakan isi tanggal peminjaman dan pengembalian.');
                return;
            }
            
            if (tanggalKembali <= tanggalPinjam) {
                e.preventDefault();
                alert('Tanggal pengembalian harus lebih besar dari tanggal peminjaman.');
                return;
            }
        });

        // Auto select first available alat on page load
        document.addEventListener('DOMContentLoaded', function() {
            const firstAlatCard = document.querySelector('.alat-card');
            if (firstAlatCard) {
                firstAlatCard.click();
            }
        });
    </script>
</body>
</html>