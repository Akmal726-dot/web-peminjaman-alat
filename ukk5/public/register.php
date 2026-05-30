<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../src/config/database.php';
require_once '../src/models/User.php';

$error = '';
$success = '';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: redirect.php");
    exit();
}

// Cek apakah sudah ada admin (hanya untuk info, TIDAK membatasi)
$userModel = new User();
$hasAdmin = $userModel->hasAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'peminjam';
    
    // Validasi
    if (empty($nama) || empty($username) || empty($email) || empty($no_hp) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (!preg_match('/^[0-9]{10,13}$/', $no_hp)) {
        $error = "Nomor HP harus berupa angka (10-13 digit)!";
    } elseif (!in_array($role, ['admin', 'petugas', 'peminjam'])) {
        $error = "Role tidak valid!";
    } else {
        $result = $userModel->register($nama, $username, $email, $no_hp, $password, $role);
        
        if ($result['success']) {
            $success = "Registrasi berhasil sebagai " . $role . "! Silakan login.";
            // Auto-redirect ke login setelah 3 detik
            header("refresh:3;url=login.php");
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Aplikasi Peminjaman Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama (Sama dengan login.php) */
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
            max-width: 700px; /* Lebih lebar untuk form register */
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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

        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            border: 1px solid rgba(25, 135, 84, 0.3);
            color: #198754;
            border-radius: 8px;
            padding: 12px 15px;
        }

        .alert-success i {
            color: #198754;
        }

        .alert-info {
            background-color: rgba(13, 110, 253, 0.1);
            border: 1px solid rgba(13, 110, 253, 0.3);
            color: #0d6efd;
            border-radius: 8px;
            padding: 12px 15px;
        }

        .alert-info i {
            color: #0d6efd;
        }

        /* Form Labels */
        .form-label {
            color: var(--hitam-text);
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

        /* Role Options */
        .role-option {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid var(--border);
        }

        .role-option:hover {
            border-color: var(--hijau);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }

        .role-option.selected {
            border-color: var(--hijau);
            background-color: rgba(40, 167, 69, 0.1);
        }

        .role-icon {
            font-size: 2rem;
            margin-bottom: 10px;
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

        /* Password Strength */
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
        }

        .strength-weak {
            background-color: var(--danger);
        }

        .strength-medium {
            background-color: #ffc107;
        }

        .strength-strong {
            background-color: #198754;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-card {
                margin: 10px;
            }

            .login-body {
                padding: 20px;
            }

            .login-header {
                padding: 20px 15px;
            }
            
            .role-option {
                margin-bottom: 15px;
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
        
        /* Email and Phone Validation */
        .valid-feedback {
            display: none;
            color: var(--hijau);
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        
        .invalid-feedback {
            display: none;
            color: var(--danger);
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        
        .was-validated .form-control:valid ~ .valid-feedback {
            display: block;
        }
        
        .was-validated .form-control:invalid ~ .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container-fluid login-container">
        <div class="row justify-content-center w-100">
            <div class="col-md-10 col-lg-8 col-xl-7">
                <div class="login-card">
                    <div class="login-header">
                        <div class="brand-logo">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3 class="mb-2">
                            <i class="fas fa-user-plus me-2"></i>
                            Daftar Akun Baru
                        </h3>
                        <p class="mb-0">Aplikasi Peminjaman Alat</p>
                    </div>

                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="registerForm" class="needs-validation" novalidate>
                            <!-- Pilihan Role -->
                            <div class="mb-5">
                                <h5 class="mb-3 fw-bold">
                                    <i class="fas fa-user-tag me-2"></i>Pilih Role Anda:
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="role-option card text-center p-4 <?php echo ($_POST['role'] ?? 'peminjam') == 'peminjam' ? 'selected' : ''; ?>" 
                                             onclick="selectRole('peminjam')">
                                            <div class="role-icon text-muted">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <h6>Peminjam</h6>
                                            <p class="small text-muted">Mengajukan peminjaman alat</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="role" 
                                                       id="rolePeminjam" value="peminjam" 
                                                       <?php echo ($_POST['role'] ?? 'peminjam') == 'peminjam' ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="rolePeminjam">
                                                    Pilih Role Ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="role-option card text-center p-4 <?php echo ($_POST['role'] ?? '') == 'petugas' ? 'selected' : ''; ?>" 
                                             onclick="selectRole('petugas')">
                                            <div class="role-icon text-muted">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <h6>Petugas</h6>
                                            <p class="small text-muted">Menyetujui dan mengelola peminjaman</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="role" 
                                                       id="rolePetugas" value="petugas"
                                                       <?php echo ($_POST['role'] ?? '') == 'petugas' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="rolePetugas">
                                                    Pilih Role Ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="role-option card text-center p-4 <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>" 
                                             onclick="selectRole('admin')" 
                                             id="adminRoleOption">
                                            <div class="role-icon text-danger">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <h6>Admin</h6>
                                            <p class="small text-muted">Mengelola semua data sistem</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="role" 
                                                       id="roleAdmin" value="admin"
                                                       <?php echo ($_POST['role'] ?? '') == 'admin' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="roleAdmin">
                                                    Pilih Role Ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- INFO: Admin bisa dipilih kapan saja -->
                                <?php if ($hasAdmin): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Informasi:</strong> Anda bisa mendaftar sebagai Admin, Petugas, atau Peminjam.
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Form Data Diri -->
                            <h5 class="mb-3 fw-bold">
                                <i class="fas fa-user-circle me-2"></i>Data Diri
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="nama" class="form-label fw-bold">
                                        <i class="fas fa-user me-2"></i>Nama Lengkap
                                    </label>
                                    <input type="text" class="form-control" id="nama" name="nama" 
                                           placeholder="Masukkan nama lengkap" 
                                           value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>"
                                           required>
                                    <div class="valid-feedback">
                                        <i class="fas fa-check-circle"></i> Nama valid
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> Harap isi nama lengkap
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="username" class="form-label fw-bold">
                                        <i class="fas fa-at me-2"></i>Username
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Masukkan username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                           minlength="3"
                                           required>
                                    <small class="text-muted">Minimal 3 karakter, unik</small>
                                    <div class="valid-feedback">
                                        <i class="fas fa-check-circle"></i> Username valid
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> Username minimal 3 karakter
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="email" class="form-label fw-bold">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="contoh@email.com" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           required>
                                    <div class="valid-feedback">
                                        <i class="fas fa-check-circle"></i> Format email valid
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> Harap isi email yang valid
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="no_hp" class="form-label fw-bold">
                                        <i class="fas fa-phone me-2"></i>Nomor HP
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">+62</span>
                                        <input type="tel" class="form-control" id="no_hp" name="no_hp" 
                                               placeholder="81234567890" 
                                               value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>"
                                               pattern="[0-9]{10,13}"
                                               required>
                                    </div>
                                    <small class="text-muted">Contoh: 81234567890 (10-13 digit)</small>
                                    <div class="valid-feedback">
                                        <i class="fas fa-check-circle"></i> Nomor HP valid
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> Nomor HP harus 10-13 digit angka
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mb-3 fw-bold">
                                <i class="fas fa-lock me-2"></i>Keamanan Akun
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="password" class="form-label fw-bold">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Masukkan password" 
                                               minlength="6"
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordStrength" class="password-strength d-none"></div>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                    <div class="valid-feedback">
                                        <i class="fas fa-check-circle"></i> Password valid
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> Password minimal 6 karakter
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="confirm_password" class="form-label fw-bold">
                                        <i class="fas fa-lock me-2"></i>Konfirmasi Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="Ulangi password" 
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatch" class="mt-1"></div>
                                    <div class="valid-feedback">
                                        <i class="fas fa-check-circle"></i> Password cocok
                                    </div>
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> Password tidak cocok
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Saya menyetujui <a href="#" class="text-decoration-none">Syarat dan Ketentuan</a>
                                    </label>
                                    <div class="invalid-feedback">
                                        Anda harus menyetujui syarat dan ketentuan
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-login" id="submitBtn">
                                    <i class="fas fa-user-plus me-2"></i>Daftar Akun
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p>Sudah punya akun? 
                                    <a href="login.php" class="text-decoration-none fw-bold">
                                        <i class="fas fa-sign-in-alt me-1"></i>Login di sini
                                    </a>
                                </p>
                                <a href="index.php" class="text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Kembali ke Beranda
                                </a>
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
        // Bootstrap form validation
        (function() {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            togglePasswordVisibility(passwordInput, icon);
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            togglePasswordVisibility(confirmInput, icon);
        });
        
        function togglePasswordVisibility(input, icon) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Pilih role
        function selectRole(role) {
            // Hapus selected class dari semua role option
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Tambah selected class ke role yang dipilih
            document.querySelector(`.role-option[onclick="selectRole('${role}')"]`).classList.add('selected');
            
            // Set radio button
            document.getElementById(`role${role.charAt(0).toUpperCase() + role.slice(1)}`).checked = true;
        }
        
        // Check password strength
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength d-none';
                return;
            }
            
            strengthBar.classList.remove('d-none');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthBar.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                strengthBar.className = 'password-strength strength-medium';
            } else {
                strengthBar.className = 'password-strength strength-strong';
            }
        });
        
        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Password cocok</span>';
            } else {
                matchDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Password tidak cocok</span>';
            }
        });
        
        // Email validation
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.length > 0 && !emailPattern.test(email)) {
                this.setCustomValidity('Format email tidak valid');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Phone number formatting
        document.getElementById('no_hp').addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/\D/g, '');
            
            // Validate length
            if (this.value.length < 10 || this.value.length > 13) {
                this.setCustomValidity('Nomor HP harus 10-13 digit angka');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            const role = document.querySelector('input[name="role"]:checked');
            const email = document.getElementById('email').value;
            const no_hp = document.getElementById('no_hp').value;
            
            // Validate email format
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                alert('Format email tidak valid!');
                return false;
            }
            
            // Validate phone number
            const phonePattern = /^[0-9]{10,13}$/;
            if (!phonePattern.test(no_hp)) {
                e.preventDefault();
                alert('Nomor HP harus 10-13 digit angka!');
                return false;
            }
            
            if (!role) {
                e.preventDefault();
                alert('Silakan pilih role Anda!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak sama!');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Anda harus menyetujui syarat dan ketentuan!');
                return false;
            }
            
            // Disable submit button to prevent double submission
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            return true;
        });
        
        // Auto focus on first field
        document.getElementById('nama').focus();

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