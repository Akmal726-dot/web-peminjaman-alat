<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
require_once '../../src/models/Alat.php';
require_once '../../src/models/User.php';
require_once '../../src/models/Bukti.php';

$peminjamanModel = new Peminjaman();
$alatModel = new Alat();
$userModel = new User();
$buktiModel = new Bukti();

// Handle konfirmasi pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $petugas_id = $_SESSION['user_id'];
    
    if (isset($_POST['konfirmasi'])) {
        $kondisi_alat = $_POST['kondisi_alat'] ?? 'baik';
        $keterangan = $_POST['keterangan_pengembalian'] ?? '';
        
        // Konfirmasi pengembalian
        $result = $peminjamanModel->konfirmasiPengembalian($id_peminjaman, $kondisi_alat, $keterangan, $petugas_id);
        if ($result) {
            // Buat bukti pengembalian otomatis
            $peminjaman = $peminjamanModel->getPeminjamanById($id_peminjaman);
            if ($peminjaman) {
                $user = $userModel->getUserById($peminjaman['id_user']);
                if ($user) {
                    // Generate nama file bukti
                    $nama_file = 'pengembalian_' . $id_peminjaman . '_' . date('Ymd_His') . '.pdf';

                    // Buat data bukti
                    $buktiData = [
                        'id_user' => $peminjaman['id_user'],
                        'id_peminjaman' => $id_peminjaman,
                        'nama_file' => $nama_file,
                        'keterangan' => 'Bukti pengembalian alat - Kondisi: ' . $kondisi_alat . ($keterangan ? ' - ' . $keterangan : ''),
                        'tipe_bukti' => 'pengembalian'
                    ];

                    $buktiResult = $buktiModel->createBukti($buktiData);
                    if ($buktiResult) {
                        $_SESSION['flash_message'] = [
                            'type' => 'success',
                            'message' => 'Pengembalian alat berhasil dikonfirmasi dan bukti telah dibuat.'
                        ];
                    } else {
                        $_SESSION['flash_message'] = [
                            'type' => 'warning',
                            'message' => 'Pengembalian alat berhasil dikonfirmasi, namun gagal membuat bukti otomatis.'
                        ];
                    }
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'warning',
                        'message' => 'Pengembalian alat berhasil dikonfirmasi, namun data user tidak ditemukan untuk membuat bukti.'
                    ];
                }
            } else {
                $_SESSION['flash_message'] = [
                    'type' => 'warning',
                    'message' => 'Pengembalian alat berhasil dikonfirmasi, namun data peminjaman tidak ditemukan untuk membuat bukti.'
                ];
            }
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Gagal mengkonfirmasi pengembalian alat.'
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

// Ambil data peminjaman yang sudah selesai (belum dikonfirmasi pengembalian)
$peminjaman_selesai_stmt = $peminjamanModel->getPeminjamanSelesai();
$peminjaman_selesai_stmt->execute();
$peminjaman_selesai = $peminjaman_selesai_stmt;
$totalSelesai = $peminjamanModel->countPeminjamanByStatus('selesai');

// Ambil data pengembalian yang sudah dikonfirmasi
$pengembalian_dikonfirmasi_stmt = $peminjamanModel->getPengembalianDikonfirmasi();
$pengembalian_dikonfirmasi_stmt->execute();
$pengembalian_dikonfirmasi = $pengembalian_dikonfirmasi_stmt;
$totalDikonfirmasi = $peminjamanModel->countPengembalianByStatus('dikembalikan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pengembalian Alat - Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama */
        :root {
            --hijau: #28a745;
            --hijau-gelap: #218838;
            --hijau-muda: #d4edda;
            --hijau-transparan: rgba(40, 167, 69, 0.1);
            
            --biru: #17a2b8;
            --biru-gelap: #138496;
            --biru-transparan: rgba(23, 162, 184, 0.1);
            
            --hitam: #212529;
            --hitam-gelap: #121416;
            --hitam-terang: #343a40;
            
            --putih: #ffffff;
            --abu: #f8f9fa;
            --abu-gelap: #e9ecef;
            --abu-text: #6c757d;
            
            --border: #dee2e6;
            
            --warning: #ffc107;
            --warning-gelap: #e0a800;
            --warning-transparan: rgba(255, 193, 7, 0.1);
            
            --danger: #dc3545;
            --danger-gelap: #c82333;
            --danger-transparan: rgba(220, 53, 69, 0.1);
            
            --info: #17a2b8;
            --info-gelap: #138496;
            --info-transparan: rgba(23, 162, 184, 0.1);
        }
        
        /* Reset dan Body */
        body {
            background-color: var(--abu);
            color: var(--hitam);
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
        }
        
        .container-fluid {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            background-color: var(--putih);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid var(--hijau);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--hijau);
            display: inline-block;
        }

        /* Statistik Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--putih);
            border-radius: 8px;
            padding: 25px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-card.selesai {
            border-left: 5px solid var(--warning);
        }

        .stat-card.dikonfirmasi {
            border-left: 5px solid var(--hijau);
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 5px;
            line-height: 1;
        }

        .stat-card.selesai .stat-number {
            color: var(--warning-gelap);
        }

        .stat-card.dikonfirmasi .stat-number {
            color: var(--hijau);
        }

        .stat-label {
            font-size: 1rem;
            color: var(--abu-text);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .stat-card.selesai .stat-icon {
            background: var(--warning-transparan);
            color: var(--warning);
        }

        .stat-card.dikonfirmasi .stat-icon {
            background: var(--hijau-transparan);
            color: var(--hijau);
        }

        /* Main Card */
        .main-card {
            background: var(--putih);
            border-radius: 8px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .card-header-custom {
            background-color: var(--putih);
            border-bottom: 1px solid var(--border);
            padding: 15px 20px;
        }

        .card-header-custom h3 {
            margin: 0;
            font-weight: 600;
            color: var(--hitam);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-body-custom {
            padding: 20px;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }

        .table-custom {
            width: 100%;
            min-width: 1000px;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--putih);
        }

        .table-custom thead th {
            background-color: var(--abu);
            color: var(--hitam);
            font-weight: 600;
            padding: 18px 20px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        .table-custom tbody tr {
            background: var(--putih);
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border);
        }

        .table-custom tbody tr:hover {
            background-color: var(--abu-gelap);
        }

        .table-custom tbody td {
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border);
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-selesai {
            background: var(--info-transparan);
            color: var(--info-gelap);
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        .status-dikonfirmasi {
            background: var(--hijau-muda);
            color: var(--hijau-gelap);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        /* Action Buttons */
        .btn-konfirmasi {
            background-color: var(--hijau);
            border-color: var(--hijau);
            color: var(--putih);
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-konfirmasi:hover {
            background-color: var(--hijau-gelap);
            border-color: var(--hijau-gelap);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
        }

        .btn-detail {
            background-color: var(--biru);
            border-color: var(--biru);
            color: var(--putih);
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-detail:hover {
            background-color: var(--biru-gelap);
            border-color: var(--biru-gelap);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--abu-text);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h4 {
            margin-bottom: 10px;
            color: var(--hitam);
        }

        /* Modal Styles */
        .modal-custom .modal-content {
            background: var(--putih);
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .modal-custom .modal-header {
            background-color: var(--putih);
            border-bottom: 1px solid var(--border);
            border-radius: 8px 8px 0 0;
            padding: 20px;
        }

        .modal-custom .modal-header h5 {
            color: var(--hitam);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-custom .modal-body {
            padding: 20px;
            color: var(--hitam);
        }

        .modal-custom .modal-footer {
            border-top: 1px solid var(--border);
            padding: 15px 20px;
            background-color: var(--abu);
        }

        /* Form Styles */
        .form-control-custom {
            background: var(--putih);
            border: 1px solid var(--border);
            color: var(--hitam);
            border-radius: 4px;
            padding: 10px 12px;
        }

        .form-control-custom:focus {
            border-color: var(--hijau);
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* Alert */
        .alert-custom {
            background: var(--hijau-muda);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: var(--hijau-gelap);
            border-radius: 4px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-custom.danger {
            background: var(--danger-transparan);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: var(--danger-gelap);
        }

        /* Button Kembali */
        .btn-kembali {
            background-color: var(--putih);
            border: 1px solid var(--border);
            color: var(--hitam);
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-kembali:hover {
            border-color: var(--hijau);
            color: var(--hijau);
            text-decoration: none;
        }

        /* Badges for kondisi alat */
        .badge-kondisi {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .badge-baik { 
            background-color: var(--hijau-muda); 
            color: var(--hijau-gelap); 
        }
        
        .badge-rusak-ringan { 
            background-color: var(--warning-transparan); 
            color: var(--warning-gelap); 
        }
        
        .badge-rusak-berat { 
            background-color: var(--danger-transparan); 
            color: var(--danger-gelap); 
        }

        /* Text Colors */
        .text-hijau { color: var(--hijau) !important; }
        .text-biru { color: var(--biru) !important; }
        .text-danger { color: var(--danger) !important; }
        .text-warning { color: var(--warning) !important; }
        .text-info { color: var(--info) !important; }

        .text-muted { color: var(--abu-text) !important; }
        .text-hitam { color: var(--hitam) !important; }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-custom {
                min-width: 800px;
            }
            
            .page-header {
                padding: 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h1><i class="fas fa-check-circle me-3"></i>Konfirmasi Pengembalian Alat</h1>
                    <p class="text-muted">Konfirmasi pengembalian alat yang telah selesai dipinjam</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="../dashboard_petugas.php" class="btn-kembali">
                        <i class="fas fa-arrow-left"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if ($flash_message): ?>
        <div class="alert-custom <?php echo $flash_message['type'] === 'danger' ? 'danger' : ''; ?>">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?php echo $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-3" style="font-size: 1.2rem;"></i>
                <div><?php echo $flash_message['message']; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card selesai">
                <div class="stat-number"><?php echo $totalSelesai; ?></div>
                <div class="stat-label">
                    <i class="fas fa-clock"></i>
                    <span>Siap Dikonfirmasi</span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-inbox"></i>
                </div>
            </div>

            <div class="stat-card dikonfirmasi">
                <div class="stat-number"><?php echo $totalDikonfirmasi; ?></div>
                <div class="stat-label">
                    <i class="fas fa-check-circle"></i>
                    <span>Sudah Dikonfirmasi</span>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Peminjaman Selesai (Siap Dikonfirmasi) -->
        <div class="main-card">
            <div class="card-header-custom">
                <h3>
                    <i class="fas fa-inbox me-2"></i>
                    Peminjaman Selesai - Siap Dikonfirmasi
                </h3>
            </div>
            <div class="card-body-custom">
                <div class="table-container">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($peminjaman_selesai && $peminjaman_selesai->rowCount() > 0): ?>
                                <?php $counterSelesai = 1; ?>
                                <?php while ($item = $peminjaman_selesai->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <strong class="text-hitam"><?php echo $counterSelesai++; ?></strong>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_peminjam']); ?></div>
                                        <?php if (!empty($item['username'])): ?>
                                        <div class="small text-muted">Username: <?php echo htmlspecialchars($item['username']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_alat']); ?></div>
                                        <?php if (!empty($item['id_alat'])): ?>
                                        <div class="small text-muted">Kode: <?php echo htmlspecialchars($item['id_alat']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($item['tanggal_peminjaman'])); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($item['tanggal_pengembalian'])); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-selesai">
                                            <i class="fas fa-clock"></i>Selesai
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn-konfirmasi" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#konfirmasiModal<?php echo $item['id_peminjaman']; ?>">
                                                <i class="fas fa-check"></i>Konfirmasi Kembali
                                            </button>
                                            <button type="button" class="btn-detail" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?php echo $item['id_peminjaman']; ?>">
                                                <i class="fas fa-eye"></i>Detail
                                            </button>
                                        </div>

                                        <!-- Modal Konfirmasi -->
                                        <div class="modal fade modal-custom" id="konfirmasiModal<?php echo $item['id_peminjaman']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-check-circle me-2"></i>Konfirmasi Pengembalian Alat
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_peminjaman" value="<?php echo $item['id_peminjaman']; ?>">
                                                            
                                                            <div class="alert alert-info mb-4" style="
                                                                background-color: var(--biru-transparan);
                                                                border: 1px solid rgba(23, 162, 184, 0.3);
                                                                color: var(--biru);
                                                                border-radius: 4px;
                                                                padding: 15px;
                                                            ">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                Konfirmasi pengembalian alat yang telah selesai dipinjam
                                                            </div>
                                                            
                                                            <!-- Informasi Peminjaman -->
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <div class="detail-item">
                                                                        <h6>Peminjam</h6>
                                                                        <p class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_peminjam']); ?></p>
                                                                        <?php if (!empty($item['username'])): ?>
                                                                        <p class="mb-0 text-muted">Username: <?php echo htmlspecialchars($item['username']); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="detail-item">
                                                                        <h6>Alat</h6>
                                                                        <p class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_alat']); ?></p>
                                                                        <?php if (!empty($item['id_alat'])): ?>
                                                                        <p class="mb-0 text-muted">Kode: <?php echo htmlspecialchars($item['id_alat']); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Form Konfirmasi -->
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-bold text-hitam">Tanggal Dikembalikan:</label>
                                                                    <input type="datetime-local" 
                                                                           class="form-control form-control-custom" 
                                                                           name="tanggal_dikembalikan" 
                                                                           value="<?php echo date('Y-m-d\TH:i'); ?>"
                                                                           required>
                                                                </div>
                                                                
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-bold text-hitam">Kondisi Alat Saat Dikembalikan:</label>
                                                                    <select class="form-control form-control-custom" name="kondisi_alat" required>
                                                                        <option value="baik">Baik - Tidak ada kerusakan</option>
                                                                        <option value="rusak_ringan">Rusak Ringan - Kerusakan kecil</option>
                                                                        <option value="rusak_berat">Rusak Berat - Kerusakan signifikan</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="col-12 mb-3">
                                                                    <label class="form-label fw-bold text-hitam">Keterangan Pengembalian:</label>
                                                                    <textarea class="form-control form-control-custom" 
                                                                              name="keterangan_pengembalian" 
                                                                              rows="3" 
                                                                              placeholder="Tambahkan keterangan mengenai kondisi alat saat dikembalikan..."></textarea>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="alert alert-warning mt-3" style="
                                                                background-color: var(--warning-transparan);
                                                                border: 1px solid rgba(255, 193, 7, 0.3);
                                                                color: var(--warning-gelap);
                                                                border-radius: 4px;
                                                            ">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                <strong>Perhatian!</strong> Pastikan alat sudah diperiksa dengan teliti sebelum dikonfirmasi.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                                                                background-color: var(--abu-gelap);
                                                                border: 1px solid var(--border);
                                                                color: var(--hitam);
                                                                padding: 8px 16px;
                                                                border-radius: 4px;
                                                            ">Batal</button>
                                                            <button type="submit" name="konfirmasi" class="btn-konfirmasi">
                                                                <i class="fas fa-check me-1"></i>Konfirmasi Pengembalian
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Detail -->
                                        <div class="modal fade modal-custom" id="detailModal<?php echo $item['id_peminjaman']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
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
                                                                <div class="detail-item">
                                                                    <h6>Informasi Peminjam</h6>
                                                                    <p class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_peminjam']); ?></p>
                                                                    <?php if (!empty($item['username'])): ?>
                                                                    <p class="mb-1 text-muted">Username: <?php echo htmlspecialchars($item['username']); ?></p>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['email'])): ?>
                                                                    <p class="mb-1 text-muted">Email: <?php echo htmlspecialchars($item['email']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Informasi Alat</h6>
                                                                    <p class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_alat']); ?></p>
                                                                    <?php if (!empty($item['id_alat'])): ?>
                                                                    <p class="mb-0 text-muted">Kode: <?php echo htmlspecialchars($item['id_alat']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Jadwal Peminjaman</h6>
                                                                    <p class="mb-1 text-hitam">Tanggal Pinjam: <?php echo date('d/m/Y', strtotime($item['tanggal_peminjaman'])); ?></p>
                                                                    <p class="mb-1 text-hitam">Tanggal Kembali: <?php echo date('d/m/Y', strtotime($item['tanggal_pengembalian'])); ?></p>
                                                                    <?php if ($item['tanggal_dikembalikan']): ?>
                                                                    <p class="mb-0 text-info">Dikembalikan: <?php echo date('d/m/Y H:i', strtotime($item['tanggal_dikembalikan'])); ?></p>
                                                                    <?php else: ?>
                                                                    <p class="mb-0 text-warning">Belum dikonfirmasi pengembalian</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Status</h6>
                                                                    <p class="mb-2">
                                                                        <span class="status-badge status-selesai">
                                                                            <i class="fas fa-clock"></i>Selesai - Siap Dikonfirmasi
                                                                        </span>
                                                                    </p>
                                                                    <?php if (!empty($item['keterangan'])): ?>
                                                                    <p class="mb-0 text-muted">Keperluan: <?php echo htmlspecialchars($item['keterangan']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                                                            background-color: var(--abu-gelap);
                                                            border: 1px solid var(--border);
                                                            color: var(--hitam);
                                                            padding: 8px 16px;
                                                            border-radius: 4px;
                                                        ">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-check" style="color: var(--hijau);"></i>
                                            <h4>Tidak ada peminjaman yang siap dikonfirmasi</h4>
                                            <p>Semua peminjaman sudah dikonfirmasi atau belum ada yang selesai</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Pengembalian Dikonfirmasi -->
        <div class="main-card">
            <div class="card-header-custom">
                <h3>
                    <i class="fas fa-history me-2"></i>
                    Riwayat Pengembalian Dikonfirmasi
                </h3>
            </div>
            <div class="card-body-custom">
                <div class="table-container">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Dikembalikan</th>
                                <th>Kondisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pengembalian_dikonfirmasi && $pengembalian_dikonfirmasi->rowCount() > 0): ?>
                                <?php $counterDikonfirmasi = 1; ?>
                                <?php while ($item = $pengembalian_dikonfirmasi->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <strong class="text-hitam"><?php echo $counterDikonfirmasi++; ?></strong>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_peminjam']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_alat']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($item['id_alat']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($item['tanggal_peminjaman'])); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($item['tanggal_dikembalikan'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $kondisi_icon = 'fa-check';
                                        $kondisi_class = 'badge-baik';
                                        $kondisi_text = 'Baik';

                                        switch($item['kondisi_kembali']) {
                                            case 'rusak_ringan':
                                                $kondisi_icon = 'fa-exclamation-triangle';
                                                $kondisi_class = 'badge-rusak-ringan';
                                                $kondisi_text = 'Rusak Ringan';
                                                break;
                                            case 'rusak_berat':
                                                $kondisi_icon = 'fa-times-circle';
                                                $kondisi_class = 'badge-rusak-berat';
                                                $kondisi_text = 'Rusak Berat';
                                                break;
                                        }
                                        ?>
                                        <span class="badge-kondisi <?php echo $kondisi_class; ?>">
                                            <i class="fas <?php echo $kondisi_icon; ?> me-1"></i><?php echo $kondisi_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-detail" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailRiwayatModal<?php echo $item['id_peminjaman']; ?>">
                                            <i class="fas fa-eye"></i>Detail
                                        </button>

                                        <!-- Modal Detail Riwayat -->
                                        <div class="modal fade modal-custom" id="detailRiwayatModal<?php echo $item['id_peminjaman']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-file-alt me-2"></i>Detail Riwayat Pengembalian
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Informasi Peminjaman</h6>
                                                                    <p class="mb-1 text-hitam">ID: #<?php echo $item['id_peminjaman']; ?></p>
                                                                    <p class="mb-1 text-hitam">Tanggal Pinjam: <?php echo date('d/m/Y', strtotime($item['tanggal_peminjaman'])); ?></p>
                                                                    <p class="mb-1 text-hitam">Tanggal Kembali: <?php echo date('d/m/Y', strtotime($item['tanggal_pengembalian'])); ?></p>
                                                                    <p class="mb-0 text-hitam">Dikembalikan: <?php echo date('d/m/Y H:i', strtotime($item['tanggal_dikembalikan'])); ?></p>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Peminjam</h6>
                                                                    <p class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_peminjam']); ?></p>
                                                                    <?php if (!empty($item['username'])): ?>
                                                                    <p class="mb-0 text-muted">Username: <?php echo htmlspecialchars($item['username']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Alat</h6>
                                                                    <p class="fw-bold text-hitam"><?php echo htmlspecialchars($item['nama_alat']); ?></p>
                                                                    <p class="mb-0 text-muted">Kode: <?php echo htmlspecialchars($item['id_alat']); ?></p>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <h6>Status Pengembalian</h6>
                                                                    <p class="mb-2 text-hitam">
                                                                        <span class="status-badge status-dikonfirmasi">
                                                                            <i class="fas fa-check-circle"></i>Sudah Dikonfirmasi
                                                                        </span>
                                                                    </p>
                                                                    <p class="mb-1 text-hitam">
                                                                        Kondisi: 
                                                                        <span class="badge-kondisi <?php echo $kondisi_class; ?>">
                                                                            <i class="fas <?php echo $kondisi_icon; ?> me-1"></i><?php echo $kondisi_text; ?>
                                                                        </span>
                                                                    </p>
                                                                    <p class="mb-0 text-muted">Dikonfirmasi oleh: <?php echo htmlspecialchars($item['nama_petugas']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($item['keterangan_kembali'])): ?>
                                                        <div class="mt-4">
                                                            <h6 class="text-hitam mb-2">Keterangan Pengembalian:</h6>
                                                            <div style="background: var(--abu); padding: 15px; border-radius: 4px; border: 1px solid var(--border);">
                                                                <?php echo nl2br(htmlspecialchars($item['keterangan_kembali'])); ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                                                            background-color: var(--abu-gelap);
                                                            border: 1px solid var(--border);
                                                            color: var(--hitam);
                                                            padding: 8px 16px;
                                                            border-radius: 4px;
                                                        ">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-history"></i>
                                            <h4>Belum ada riwayat pengembalian</h4>
                                            <p>Riwayat pengembalian akan muncul setelah ada konfirmasi</p>
                                        </div>
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
        // Validasi form konfirmasi
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('[name="konfirmasi"]')) {
                form.addEventListener('submit', function(e) {
                    const tanggalKembali = this.querySelector('input[name="tanggal_dikembalikan"]');
                    const kondisiAlat = this.querySelector('select[name="kondisi_alat"]');
                    
                    if (!tanggalKembali.value) {
                        e.preventDefault();
                        alert('Harap isi tanggal dikembalikan.');
                        tanggalKembali.focus();
                        return;
                    }
                    
                    if (!kondisiAlat.value) {
                        e.preventDefault();
                        alert('Harap pilih kondisi alat saat dikembalikan.');
                        kondisiAlat.focus();
                        return;
                    }
                    
                    // Konfirmasi sebelum submit
                    if (!confirm('Apakah Anda yakin ingin mengkonfirmasi pengembalian ini?')) {
                        e.preventDefault();
                    }
                });
            }
        });

        // Auto-refresh setiap 30 detik untuk mengecek peminjaman baru yang selesai
        setInterval(function() {
            location.reload();
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + K untuk konfirmasi di modal yang aktif
            if (e.ctrlKey && e.key === 'k') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const konfirmasiBtn = activeModal.querySelector('button[name="konfirmasi"]');
                    if (konfirmasiBtn) {
                        konfirmasiBtn.click();
                    }
                }
            }
            
            // Escape untuk close modal
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const closeBtn = activeModal.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }
            }
        });
    </script>
</body>
</html>