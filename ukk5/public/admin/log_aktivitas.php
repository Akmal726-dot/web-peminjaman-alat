<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/LogAktivitas.php';
$logModel = new LogAktivitas();

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Logs per page
$offset = ($page - 1) * $limit;

// Get activities with pagination
$activities = $logModel->getAllActivities($limit, $offset);
$total_activities = $logModel->countActivities();
$total_pages = ceil($total_activities / $limit);

// Get recent activities for quick view
$recent_activities = $logModel->getRecentActivities(5);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Admin</title>
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
            max-width: 1400px;
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

        .badge.bg-info {
            background: var(--info) !important;
            color: var(--putih) !important;
        }

        h2 {
            color: var(--hijau);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .activity-admin {
            background: var(--danger-transparan);
            color: var(--danger);
        }

        .activity-petugas {
            background: var(--warning-transparan);
            color: var(--warning);
        }

        .activity-peminjam {
            background: var(--hijau-transparan);
            color: var(--hijau);
        }

        .activity-content {
            display: flex;
            align-items: center;
        }

        .activity-text {
            font-weight: 500;
            color: var(--hitam);
        }

        .activity-time {
            font-size: 0.85rem;
            color: var(--abu-text);
        }

        .pagination .page-link {
            color: var(--hijau);
            border-color: var(--border);
        }

        .pagination .page-link:hover {
            background-color: var(--hijau-transparan);
            color: var(--hijau-gelap);
            border-color: var(--hijau);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--hijau);
            border-color: var(--hijau);
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

        .recent-activities {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-left">
                <h2 class="mb-0">
                    <i class="fas fa-history me-2"></i>Log Aktivitas
                </h2>
                <span class="stats-badge">
                    <i class="fas fa-list me-1"></i>
                    Total: <?php echo $total_activities; ?> Aktivitas
                </span>
            </div>
            <div class="action-buttons">
                <a href="../dashboard_admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities Sidebar -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Aktivitas Terbaru
                        </h5>
                    </div>
                    <div class="card-body recent-activities">
                        <?php if ($recent_activities->rowCount() > 0): ?>
                            <?php while ($activity = $recent_activities->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <div class="activity-icon activity-<?php echo $activity['role']; ?>">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="activity-text">
                                                <?php echo htmlspecialchars($activity['nama_user']); ?>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo htmlspecialchars($activity['aktifitas']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h6 class="mt-3 mb-2">Belum ada aktivitas</h6>
                                <p class="text-muted mb-0">Aktivitas akan muncul di sini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Activities Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Semua Aktivitas
                        </h5>
                        <small class="text-muted">
                            Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                        </small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">ID</th>
                                        <th>Pengguna</th>
                                        <th>Aktivitas</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($activities->rowCount() > 0):
                                        while ($activity = $activities->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td class="fw-bold" style="color: var(--hijau);">
                                            #<?php echo $activity['id_log']; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon activity-<?php echo $activity['role']; ?>">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($activity['nama_user']); ?></div>
                                                    <small class="badge bg-<?php
                                                        echo $activity['role'] == 'admin' ? 'danger' :
                                                             ($activity['role'] == 'petugas' ? 'warning' : 'primary');
                                                    ?>">
                                                        <?php echo ucfirst($activity['role']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="activity-text">
                                                <?php echo htmlspecialchars($activity['aktifitas']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="activity-time">
                                                <?php echo date('d/m/Y', strtotime($activity['created_at'])); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('H:i:s', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-history"></i>
                                                <h5 class="mt-3 mb-2">Belum ada log aktivitas</h5>
                                                <p class="text-muted mb-0">Aktivitas pengguna akan dicatat di sini</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efek hover untuk baris tabel
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Auto refresh recent activities every 30 seconds
        setInterval(function() {
            // Optional: implement AJAX to refresh recent activities
        }, 30000);
    </script>
</body>
</html>
