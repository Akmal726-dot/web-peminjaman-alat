<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="../dashboard_admin.php">
            <i class="fas fa-user-shield me-2"></i>
            <strong>Admin Panel</strong>
        </a>
        <div class="navbar-nav ms-auto">
            <span class="nav-link">
                <i class="fas fa-user me-1"></i>
                <?php echo htmlspecialchars($_SESSION['nama']); ?>
                <span class="badge bg-danger ms-1">Admin</span>
            </span>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>