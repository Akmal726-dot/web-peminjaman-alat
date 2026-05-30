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
    <title>Riwayat Peminjaman - Peminjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
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
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            padding: 1.2rem 1.5rem;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
        }

        .nav-link:hover {
            opacity: 0.9;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem 0.75rem;
        }

        .table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .badge {
            font-weight: 600;
            padding: 0.5em 1em;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #64748b, #475569);
            border: none;
        }

        .alert {
            border: none;
            border-radius: 8px;
        }

        h4, h5 {
            font-weight: 700;
            color: #333;
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .text-success {
            color: var(--success) !important;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* Status badges */
        .badge-pending {
            background: linear-gradient(135deg, var(--warning), var(--warning-dark));
            color: white;
        }

        .badge-approved {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            color: white;
        }

        .badge-rejected {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark));
            color: white;
        }

        .badge-returned {
            background: linear-gradient(135deg, var(--info), var(--info-dark));
            color: white;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }

            .table-responsive {
                font-size: 0.9rem;
            }

            .badge {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="../dashboard_peminjam.php">
                <i class="fas fa-user me-2"></i>
                <strong>Peminjam Panel</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <span class="nav-link">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['nama']); ?>
                        <span class="badge bg-light text-primary ms-1">Peminjam</span>
                    </span>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-history me-2"></i>Riwayat Peminjaman
                            </h4>
                            <a href="../dashboard_peminjam.php" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID Peminjaman</th>
                                        <th>Nama Alat</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
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
                                                $statusClass = 'badge-pending';
                                                $statusText = 'Menunggu Persetujuan';
                                                break;
                                            case 'disetujui':
                                                $statusClass = 'badge-approved';
                                                $statusText = 'Disetujui';
                                                break;
                                            case 'ditolak':
                                                $statusClass = 'badge-rejected';
                                                $statusText = 'Ditolak';
                                                break;
                                            case 'dikembalikan':
                                                $statusClass = 'badge-returned';
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
                                            <i class="fas fa-tools text-primary me-2"></i>
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
                                    </tr>
                                    <?php endwhile; ?>

                                    <?php if (!$hasData): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Belum ada riwayat peminjaman</h5>
                                                <p class="text-muted mb-3">Anda belum pernah melakukan peminjaman alat.</p>
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
