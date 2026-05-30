<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        if (self::check()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'nama' => $_SESSION['nama']
            ];
        }
        return null;
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function isPetugas() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'petugas';
    }

    public static function isPeminjam() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'peminjam';
    }

    public static function redirectIfNotLoggedIn($redirectTo = '../public/login.php') {
        if (!self::check()) {
            header("Location: " . $redirectTo);
            exit();
        }
    }
}
?>