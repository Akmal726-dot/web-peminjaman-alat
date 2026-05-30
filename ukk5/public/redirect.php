<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Redirect berdasarkan role
switch ($role) {
    case 'admin':
        header("Location: dashboard_admin.php");
        break;
    case 'petugas':
        header("Location: dashboard_petugas.php");
        break;
    case 'peminjam':
        header("Location: dashboard_peminjam.php");
        break;
    default:
        header("Location: login.php");
        break;
}
exit();
?>