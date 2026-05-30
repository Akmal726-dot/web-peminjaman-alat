<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
$peminjamanModel = new Peminjaman();

// Handle return action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $id_peminjaman = $_POST['id_peminjaman'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $denda = $_POST['denda'] ?? 0;
    $keterangan = $_POST['keterangan'] ?? '';

    $result = $peminjamanModel->returnAlat([
        'id_peminjaman' => $id_peminjaman,
        'tanggal_kembali' => $tanggal_kembali,
        'denda' => $denda,
        'keterangan' => $keterangan,
        'id_user' => $_SESSION['user_id']
    ]);

    if ($result) {
        $success_msg = "Alat berhasil dikembalikan!";
    } else {
        $error_msg = "Gagal mengembalikan alat!";
    }
}

// Get all returns (dikembalikan status)
$pengembalian = $peminjamanModel->getAllPengembalian();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengembalian - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* WARNA: HIJAU, HITAM, PUTIH SAJA */
        /* HIJAU */
        .hijau { color: #28a745 !important; }
        .bg-hijau { background-color: #28a745 !important; }
        .border-hijau { border-color: #28a745 !important; }
        
        /* HITAM */
        .hitam { color: #000000 !important; }
        .bg-hitam { background-color: #000000 !important; }
        .border-hitam { border-color: #000000 !important; }
        
        /* PUTIH */
        .putih { color: #ffffff !important; }
        .bg-putih { background-color: #ffffff !important; }
        .border-putih { border-color: #ffffff !important; }

        body {
            background-color: #ffffff;
            color: #000000;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .card {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px;
        }

        .table {
            color: #000000 !important;
            border-color: #dee2e6 !important;
        }

        .table thead th {
            background-color: #ffffff !important;
            border-color: #dee2e6 !important;
            color: #000000 !important;
            font-weight: 600;
        }

        .table tbody tr {
            border-color: #dee2e6 !important;
        }

        .table tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }

        .btn-primary {
            background: #28a745 !important;
            border: none !important;
            color: #ffffff !important;
        }
        
        .btn-primary:hover {
            background: #218838 !important;
        }

        .btn-success {
            background: #28a745 !important;
            border: none !important;
            color: #ffffff !important;
        }
        
        .btn-success:hover {
            background: #218838 !important;
        }
        
        .btn-secondary {
            background: #ffffff !important;
            border: 1px solid #000000 !important;
            color: #000000 !important;
        }
        
        .btn-secondary:hover {
            background: #000000 !important;
            color: #ffffff !important;
        }

        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .badge-success {
            background-color: #28a745 !important;
            color: #ffffff !important;
        }
        
        .badge-danger {
            background-color: #000000 !important;
            color: #ffffff !important;
        }
        
        .badge-info {
            background-color: #28a745 !important;
            color: #ffffff !important;
        }
        
        .badge-warning {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }

        .modal-content {
            background: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }

        .modal-header {
            border-bottom: 1px solid #000000 !important;
            background: #ffffff !important;
        }

        .modal-footer {
            border-top: 1px solid #000000 !important;
            background: #ffffff !important;
        }

        .form-control, .form-select {
            background-color: #ffffff !important;
            border: 1px solid #000000 !important;
            color: #000000 !important;
        }

        .form-control:focus, .form-select:focus {
            background-color: #ffffff !important;
            border-color: #28a745 !important;
            color: #000000 !important;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        
        /* Alert Styles */
        .alert {
            border: 1px solid transparent;
            border-radius: 8px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1) !important;
            color: #28a745 !important;
            border-color: #28a745 !important;
        }
        
        .alert-danger {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }
        
        /* Card Header */
        .card-header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #28a745 !important;
        }
        
        /* Card Footer */
        .card-footer {
            background-color: #ffffff !important;
            border-top: 1px solid #dee2e6 !important;
        }
        
        /* Text Colors */
        .text-muted {
            color: #6c757d !important;
        }
        
        h2, h5 {
            color: #000000 !important;
        }
        
        .fa-undo, .fa-table, .fa-check-circle {
            color: #28a745 !important;
        }
        
        .fa-exclamation-circle {
            color: #000000 !important;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/src/views/includes/admin_header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 hitam">
                    <i class="fas fa-undo me-2 hijau"></i>Data Pengembalian
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Daftar pengembalian alat yang telah diproses
                </p>
            </div>
            <div>
    <a href="../dashboard_admin.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
    </a>
</div>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2 hijau"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2 hitam"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 hitam">
                    <i class="fas fa-table me-2 hijau"></i>Daftar Pengembalian
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="hitam">ID</th>
                                <th class="hitam">Peminjam</th>
                                <th class="hitam">Alat</th>
                                <th class="hitam">Tanggal Pinjam</th>
                                <th class="hitam">Tanggal Kembali</th>
                                <th class="hitam">Denda</th>
                                <th class="hitam">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalPengembalian = 0;
                            while ($item = $pengembalian->fetch(PDO::FETCH_ASSOC)):
                                $totalPengembalian++;
                            ?>
                            <tr>
                                <td class="fw-bold hijau">#<?php echo $item['id_peminjaman']; ?></td>
                                <td class="hitam"><?php echo htmlspecialchars($item['nama_peminjam']); ?></td>
                                <td class="hitam"><?php echo htmlspecialchars($item['nama_alat']); ?></td>
                                <td class="hitam"><?php echo $item['tanggal_peminjaman'] ? date('d/m/Y', strtotime($item['tanggal_peminjaman'])) : '-'; ?></td>
                                <td class="hitam"><?php echo $item['tanggal_kembali'] ? date('d/m/Y', strtotime($item['tanggal_kembali'])) : '-'; ?></td>
                                <td>
                                    <?php if ($item['denda'] > 0): ?>
                                        <span class="badge bg-hitam putih">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            Rp <?php echo number_format($item['denda'], 0, ',', '.'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-hijau putih">
                                            <i class="fas fa-check me-1"></i>Tidak ada denda
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-hijau putih">
                                        <i class="fas fa-undo me-1"></i>Dikembalikan
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php if ($totalPengembalian == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 hijau"></i>
                                        <h5 class="mb-2 hitam">Belum ada data pengembalian</h5>
                                        <p class="text-muted mb-0">Belum ada alat yang dikembalikan</p>
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
                        <small class="hitam">
                            <i class="fas fa-sync-alt me-1"></i>
                            Diperbarui: <?php echo date('H:i:s'); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="hitam">
                            Total: <?php echo $totalPengembalian; ?> data pengembalian
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>