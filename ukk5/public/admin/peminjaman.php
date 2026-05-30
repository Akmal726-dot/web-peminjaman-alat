<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
$peminjamanModel = new Peminjaman();
$peminjaman = $peminjamanModel->getAllPeminjaman();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #ffffff; /* Putih */
            color: #333333; /* Abu-abu gelap untuk teks */
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        /* Warna utama hijau */
        .text-primary, .text-success {
            color: #28a745 !important; /* Hijau Bootstrap */
        }
        
        .bg-primary, .bg-success {
            background-color: #28a745 !important; /* Hijau Bootstrap */
        }
        
        .border-primary, .border-success {
            border-color: #28a745 !important;
        }
        
        /* Tombol utama hijau */
        .btn-primary, .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }
        
        .btn-primary:hover, .btn-success:hover {
            background-color: #218838 !important;
            border-color: #1e7e34 !important;
        }
        
        /* Tombol outline hijau */
        .btn-outline-primary, .btn-outline-success {
            color: #28a745 !important;
            border-color: #28a745 !important;
            background: transparent;
        }
        
        .btn-outline-primary:hover, .btn-outline-success:hover {
            background-color: #28a745 !important;
            color: white !important;
            border-color: #28a745 !important;
        }
        
        /* Card styling */
        .card {
            background-color: #ffffff !important; /* Putih */
            border: 1px solid #dee2e6 !important; /* Abu-abu muda */
            color: #333333 !important;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        
        .card-header {
            background-color: #f8f9fa !important; /* Abu-abu sangat muda */
            border-bottom: 1px solid #dee2e6 !important;
            color: #333333 !important;
        }
        
        /* Table styling */
        .table {
            color: #333333 !important;
            border-color: #dee2e6 !important;
        }
        
        .table thead th {
            background-color: #f8f9fa !important; /* Abu-abu sangat muda */
            border-color: #dee2e6 !important;
            color: #28a745 !important; /* Hijau */
            font-weight: 600;
        }
        
        .table tbody tr {
            border-color: #dee2e6 !important;
        }
        
        .table tbody tr:hover {
            background-color: rgba(40, 167, 69, 0.05) !important; /* Hijau transparan sangat muda */
        }
        
        .table tbody td {
            border-color: #dee2e6 !important;
        }
        
        /* Navbar */
        .navbar {
            background-color: #28a745 !important; /* Hijau */
            border-bottom: 2px solid #218838 !important; /* Hijau lebih gelap */
        }
        
        .navbar-brand, .nav-link {
            color: #ffffff !important;
        }
        
        .navbar-brand:hover, .nav-link:hover {
            color: #f8f9fa !important; /* Putih sedikit abu-abu saat hover */
        }
        
        /* Text muted */
        .text-muted {
            color: #6c757d !important; /* Abu-abu Bootstrap */
        }
        
        /* Badges */
        .badge-success {
            background-color: #28a745 !important;
            color: white !important;
        }
        
        .badge-warning {
            background-color: #ffc107 !important; /* Kuning untuk pending */
            color: black !important;
        }
        
        .badge-danger {
            background-color: #dc3545 !important; /* Merah untuk ditolak */
            color: white !important;
        }
        
        .badge-info {
            background-color: #17a2b8 !important; /* Biru muda untuk dikembalikan */
            color: white !important;
        }
        
        .badge-secondary {
            background-color: #6c757d !important; /* Abu-abu */
            color: white !important;
        }
        
        /* Tombol secondary */
        .btn-secondary {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268 !important;
            border-color: #545b62 !important;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #28a745; /* Hijau */
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #218838; /* Hijau lebih gelap */
        }
        
        /* Form controls */
        .form-control {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #495057;
        }
        
        .form-control:focus {
            background-color: #ffffff;
            border-color: #28a745; /* Hijau */
            color: #495057;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        /* Modal (jika ada) */
        .modal-content {
            background-color: #ffffff;
            color: #333333;
            border: 1px solid #dee2e6;
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-footer {
            border-top: 1px solid #dee2e6;
        }
        
        /* Footer card */
        .card-footer {
            background-color: #f8f9fa !important;
            border-top: 1px solid #dee2e6 !important;
            color: #6c757d !important;
        }
        
        /* Efek hover untuk tombol */
        .btn {
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Navbar badge admin */
        .navbar .badge {
            background-color: #ffffff !important;
            color: #28a745 !important;
        }
    </style>
</head>
<body>
    <?php 
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: ../login.php");
        exit();
    }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background-color: #28a745; border-bottom: 2px solid #218838;">
        <div class="container">
            <a class="navbar-brand" href="../dashboard_admin.php">
                <i class="fas fa-user-shield me-2"></i>
                <strong>Admin Panel</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['nama']); ?>
                    <span class="badge bg-white text-success ms-1">Admin</span>
                </span>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-clipboard-list me-2 text-success"></i>Data Peminjaman
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Daftar seluruh peminjaman alat di sistem
                </p>
            </div>
            <div>
                <a href="../dashboard_admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2 text-success"></i>
                    Daftar Peminjaman
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Tanggal Pinjam</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalPeminjaman = 0;
                            $peminjaman = $peminjamanModel->getAllPeminjaman();
                            
                            while ($item = $peminjaman->fetch(PDO::FETCH_ASSOC)): 
                                $totalPeminjaman++;
                                $status_class = '';
                                $status_icon = '';
                                switch($item['status']) {
                                    case 'disetujui':
                                        $status_class = 'badge-success';
                                        $status_icon = 'fa-check-circle';
                                        break;
                                    case 'pending':
                                        $status_class = 'badge-warning';
                                        $status_icon = 'fa-clock';
                                        break;
                                    case 'ditolak':
                                        $status_class = 'badge-danger';
                                        $status_icon = 'fa-times-circle';
                                        break;
                                    case 'dikembalikan':
                                        $status_class = 'badge-info';
                                        $status_icon = 'fa-undo';
                                        break;
                                    default:
                                        $status_class = 'badge-secondary';
                                        $status_icon = 'fa-question-circle';
                                }
                            ?>
                            <tr>
                                <td class="fw-bold">#<?php echo $item['id_peminjaman']; ?></td>
                                <td><?php echo htmlspecialchars($item['nama_peminjam']); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_alat']); ?></td>
                                <td>
                                    <?php echo isset($item['tanggal_peminjaman']) && $item['tanggal_peminjaman'] ? date('d/m/Y', strtotime($item['tanggal_peminjaman'])) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($totalPeminjaman == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                        <h5 class="mb-2">Belum ada data peminjaman</h5>
                                        <p class="text-muted mb-0">Sistem belum memiliki data peminjaman alat</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small>
                            <i class="fas fa-sync-alt me-1"></i>
                            Diperbarui: <?php echo date('H:i:s'); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small>
                            Total: <?php echo $totalPeminjaman; ?> data peminjaman
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tambahkan fitur pencarian
        function searchTable() {
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'Cari data peminjaman...';
            input.className = 'form-control mb-3';
            
            const cardHeader = document.querySelector('.card-header');
            if (cardHeader) {
                cardHeader.appendChild(input);
                
                input.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.table tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }
        }

        // Inisialisasi pencarian
        searchTable();

        // Efek hover pada baris tabel
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.cursor = 'pointer';
            });
            
            // Tambahkan klik untuk detail (jika diperlukan)
            row.addEventListener('click', function() {
                const id = this.querySelector('td:first-child').textContent;
                console.log('Klik pada peminjaman ID:', id);
                // Di sini bisa ditambahkan fungsi untuk menampilkan detail
            });
        });
    </script>
</body>
</html>