<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../src/config/database.php';
require_once '../src/models/User.php';

$error = '';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: redirect.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $userModel = new User();
        $user = $userModel->login($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama'];
            
            header("Location: redirect.php");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Peminjaman Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama (Sama dengan dashboard.php) */
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
        }
        
        /* Reset dan Body */
        body {
            background: linear-gradient(135deg, var(--hijau-transparan) 0%, var(--abu) 100%);
            color: var(--hitam);
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        /* Login Container */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Login Card */
        .login-card {
            background-color: var(--putih);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .login-card:hover {
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.15);
        }
        
        /* Login Header */
        .login-header {
            background: linear-gradient(135deg, var(--hijau) 0%, var(--hijau-gelap) 100%);
            color: var(--putih);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }
        
        .login-header h3 {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            opacity: 0.9;
            margin-bottom: 0;
            font-size: 14px;
        }
        
        /* Login Body */
        .login-body {
            padding: 30px;
        }
        
        /* Alert Styles */
        .alert-danger {
            background-color: var(--danger-transparan);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: var(--danger);
            border-radius: 8px;
            padding: 12px 15px;
        }
        
        .alert-danger i {
            color: var(--danger);
        }
        
        /* Form Labels */
        .form-label {
            color: var(--hitam);
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            color: var(--hijau);
            width: 20px;
        }
        
        /* Form Controls */
        .form-control {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--hijau);
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        
        /* Input Group */
        .input-group .form-control {
            border-right: none;
        }
        
        .input-group .btn {
            border: 1px solid var(--border);
            border-left: none;
            background-color: var(--abu);
            color: var(--abu-text);
            border-radius: 0 8px 8px 0;
        }
        
        .input-group .btn:hover {
            background-color: var(--abu-gelap);
            color: var(--hijau);
        }
        
        /* Login Button */
        .btn-login {
            background: linear-gradient(135deg, var(--hijau) 0%, var(--hijau-gelap) 100%);
            border: none;
            color: var(--putih);
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, var(--hijau-gelap) 0%, var(--hijau) 100%);
            color: var(--putih);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Links */
        a {
            color: var(--hijau);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        a:hover {
            color: var(--hijau-gelap);
            text-decoration: underline;
        }
        
        .text-decoration-none {
            text-decoration: none;
        }
        
        .text-decoration-none:hover {
            text-decoration: underline;
        }
        
        /* Login Footer */
        .login-footer {
            background-color: var(--abu);
            border-top: 1px solid var(--border);
            padding: 20px;
            text-align: center;
            color: var(--abu-text);
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                margin: 10px;
            }
            
            .login-body {
                padding: 20px;
            }
            
            .login-header {
                padding: 20px 15px;
            }
        }
        
        /* Loading Animation */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Logo/Brand */
        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .brand-logo i {
            font-size: 32px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container-fluid login-container">
        <div class="row justify-content-center w-100">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="login-card">
                    <div class="login-header">
                        <div class="brand-logo">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3 class="mb-2">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login Sistem
                        </h3>
                        <p class="mb-0">Aplikasi Peminjaman Alat</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="loginForm">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username"
                                       placeholder="Masukkan username Anda"
                                       required
                                       autofocus>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Masukkan password Anda" 
                                           required>
                                    <button class="btn" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-login" id="submitBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-3 text-abu">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Masukkan kredensial Anda untuk mengakses sistem
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                    <a href="index.php" class="text-decoration-none">
                                        <i class="fas fa-home me-1"></i>Kembali ke Beranda
                                    </a>
                                    
                                    <span class="text-abu">|</span>
                                    
                                    <a href="register.php" class="text-decoration-none fw-bold">
                                        <i class="fas fa-user-plus me-1"></i>Daftar Akun Baru
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="login-footer">
                        <small class="text-muted">
                            <i class="fas fa-copyright me-1"></i>
                            <?php echo date('Y'); ?> Aplikasi Peminjaman Alat
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username.length === 0 || password.length === 0) {
                e.preventDefault();
                alert('Username dan password harus diisi!');
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            
            return true;
        });
        
        // Auto focus on username field if not already focused
        if (!document.activeElement || document.activeElement.tagName === 'BODY') {
            document.getElementById('username').focus();
        }
        
        // Add animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const loginCard = document.querySelector('.login-card');
            loginCard.style.transform = 'translateY(20px)';
            loginCard.style.opacity = '0';
            
            setTimeout(() => {
                loginCard.style.transition = 'all 0.5s ease';
                loginCard.style.transform = 'translateY(0)';
                loginCard.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>