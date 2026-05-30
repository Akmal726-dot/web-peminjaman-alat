<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login($username, $password) {
        $user = $this->userModel->login($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama'];
            
            return true;
        }
        return false;
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function checkAccess($requiredRole) {
        if (!Auth::check()) {
            return false;
        }

        switch ($requiredRole) {
            case 'admin':
                return Auth::isAdmin();
            case 'petugas':
                return Auth::isPetugas();
            case 'peminjam':
                return Auth::isPeminjam();
            default:
                return false;
        }
    }
}
?>