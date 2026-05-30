<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <div class="logo-circle mb-3">
                <i class="fas fa-tools fa-2x text-white"></i>
            </div>
            <h5 class="text-white">Admin Panel</h5>
            <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['nama'] ?? ''); ?></small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : ''; ?>"
                   href="../dashboard_admin.php">
                    <i class="fas fa-home me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Manajemen User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="kategori.php">
                    <i class="fas fa-tags me-2"></i>
                    Kategori Alat
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="alat.php">
                    <i class="fas fa-tools me-2"></i>
                    Data Alat
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="peminjaman.php">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Data Peminjaman
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="konfirmasi_pengembalian.php">
                    <i class="fas fa-undo me-2"></i>
                    Data Pengembalian
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="laporan.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logs.php">
                    <i class="fas fa-history me-2"></i>
                    Log Aktivitas
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../public/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.logo-circle {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
</style>