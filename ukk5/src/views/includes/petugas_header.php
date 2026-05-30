<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4" style="background-color: #20c997 !important;">
    <div class="container">
        <a class="navbar-brand" href="../dashboard_petugas.php">
            <i class="fas fa-user-tie me-2"></i>
            <strong>Petugas Panel</strong>
        </a>
        <div class="navbar-nav ms-auto">
            <span class="nav-link">
                <i class="fas fa-user me-1"></i>
                <?php echo htmlspecialchars($_SESSION['nama']); ?>
                <span class="badge bg-light text-success ms-1">Petugas</span>
            </span>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>