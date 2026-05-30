<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'peminjam') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';

$peminjamanModel = new Peminjaman();
$peminjamanUser = $peminjamanModel->getPeminjamanByUser($_SESSION['user_id']);
$peminjamanUser->execute();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman Saya - Peminjam</title>
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
    <nav class="navbar navbar-light py-3">
        <div class="container-fluid">
            <h4 class="mb-0">
                <i class="fas fa-clipboard-list me-2 text-hijau"></i>
                Data Peminjaman
            </h4>
            <div class="d-flex align-items-center">
                <span class="me-3 text-abu">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?php echo date('l, d F Y'); ?>
                </span>
                <a href="../dashboard_peminjam.php" class="btn btn-outline-hijau btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list me-2 text-hijau"></i>Data Peminjaman Saya
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Peminjaman</th>
                                        <th>Nama Alat</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $hasData = false;
                                    while ($row = $peminjamanUser->fetch(PDO::FETCH_ASSOC)):
                                        $hasData = true;
                                        $statusClass = '';
                                        $statusText = '';

                                        switch($row['status']) {
                                            case 'pending':
                                                $statusClass = 'badge-warning';
                                                $statusText = 'Menunggu Persetujuan';
                                                break;
                                            case 'disetujui':
                                                $statusClass = 'badge-success';
                                                $statusText = 'Disetujui';
                                                break;
                                            case 'menunggu_konfirmasi':
                                                $statusClass = 'badge-warning';
                                                $statusText = 'Menunggu Konfirmasi Petugas';
                                                break;
                                            case 'ditolak':
                                                $statusClass = 'badge-danger';
                                                $statusText = 'Ditolak';
                                                break;
                                            case 'dikembalikan':
                                                $statusClass = 'badge-info';
                                                $statusText = 'Dikembalikan';
                                                break;
                                            default:
                                                $statusClass = 'badge-secondary';
                                                $statusText = ucfirst($row['status']);
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $row['id_peminjaman']; ?></strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-tools me-2 text-abu"></i>
                                            <?php echo htmlspecialchars($row['nama_alat']); ?>
                                        </td>
                                        <td>
                                            <?php echo $row['tanggal_peminjaman'] ? date('d/m/Y', strtotime($row['tanggal_peminjaman'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo $row['tanggal_pengembalian'] ? date('d/m/Y', strtotime($row['tanggal_pengembalian'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo $row['jumlah']; ?> unit
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['keterangan'])): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($row['keterangan'], 0, 50)); ?>
                                                    <?php if (strlen($row['keterangan']) > 50): ?>...<?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'disetujui'): ?>
                                                <a href="pengembalian.php?peminjaman_id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-paper-plane me-1"></i>Ajukan Pengembalian
                                                </a>
                                            <?php elseif ($row['status'] == 'pending'): ?>
                                                <button class="btn btn-warning btn-sm" disabled>
                                                    <i class="fas fa-clock me-1"></i>Menunggu
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>

                                    <?php if (!$hasData): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Belum ada data peminjaman</h5>
                                                <p class="text-muted mb-3">Anda belum pernah mengajukan peminjaman alat.</p>
                                                <a href="alat.php" class="btn btn-primary">
                                                    <i class="fas fa-plus me-1"></i>Mulai Pinjam Alat
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($hasData): ?>
                        <div class="mt-3">
                            <div class="alert alert-info border-info" style="background-color: rgba(6, 182, 212, 0.1);">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x text-info me-3"></i>
                                    <div>
                                        <h6 class="mb-1 text-info">Informasi Status Peminjaman</h6>
                                        <ul class="mb-0 small">
                                            <li><strong>Menunggu Persetujuan:</strong> Peminjaman sedang ditinjau petugas</li>
                                            <li><strong>Disetujui:</strong> Peminjaman telah disetujui dan alat dapat diambil</li>
                                            <li><strong>Ditolak:</strong> Peminjaman tidak disetujui</li>
                                            <li><strong>Dikembalikan:</strong> Alat telah dikembalikan</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
