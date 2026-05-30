<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header("Location: redirect.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Peminjaman Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Warna Utama (Sama dengan dashboard.php dan login.php) */
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
        }
        
        /* Reset dan Body */
        body {
            background: linear-gradient(135deg, var(--hijau-transparan) 0%, var(--abu) 100%);
            color: var(--hitam);
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--hitam-gelap) 0%, var(--hitam) 100%);
            border-bottom: 3px solid var(--hijau);
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--putih) !important;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            color: var(--hijau);
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: var(--abu-gelap) !important;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--putih) !important;
            background-color: var(--hijau-transparan);
        }
        
        /* Hero Section */
        .hero-section {
            padding: 80px 0 60px;
            background: transparent;
        }
        
        /* Hero Card */
        .hero-card {
            background: var(--putih);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .hero-card:hover {
            box-shadow: 0 15px 50px rgba(40, 167, 69, 0.12);
            transform: translateY(-5px);
        }
        
        /* Typography */
        .display-4 {
            font-weight: 700;
            color: var(--hitam);
            margin-bottom: 1.5rem;
        }
        
        .display-4 i {
            color: var(--hijau);
        }
        
        .lead {
            color: var(--abu-text);
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            line-height: 1.8;
        }
        
        /* Feature Cards */
        .feature-card {
            background: var(--putih);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--hijau);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.1);
            border-color: var(--hijau);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 30px;
            background: linear-gradient(135deg, var(--hijau-transparan) 0%, rgba(40, 167, 69, 0.2) 100%);
            color: var(--hijau);
        }
        
        .feature-card h5 {
            color: var(--hitam);
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .feature-card p {
            color: var(--abu-text);
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-card ul {
            padding-left: 1.2rem;
        }
        
        .feature-card li {
            margin-bottom: 0.5rem;
            color: var(--hitam);
        }
        
        .feature-card li i {
            color: var(--hijau);
            margin-right: 8px;
        }
        
        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--hijau) 0%, var(--hijau-gelap) 100%);
            border: none;
            color: var(--putih);
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--hijau-gelap) 0%, var(--hijau) 100%);
            color: var(--putih);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--hijau);
            color: var(--hijau);
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-outline-custom:hover {
            background: var(--hijau);
            color: var(--putih);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.2);
        }
        
        /* Info Cards */
        .info-card {
            background: var(--abu);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            border-color: var(--hijau);
            background: var(--hijau-transparan);
        }
        
        .info-card h5 {
            color: var(--hitam);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h5 i {
            color: var(--hijau);
        }
        
        .info-card ol, .info-card ul {
            padding-left: 1.5rem;
        }
        
        .info-card li {
            margin-bottom: 0.75rem;
            color: var(--hitam);
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--hitam-gelap) 0%, var(--hitam) 100%);
            color: var(--abu-gelap);
            padding: 2rem 0;
            margin-top: 4rem;
            border-top: 3px solid var(--hijau);
        }
        
        .footer p {
            margin-bottom: 0.5rem;
        }
        
        .footer .small i {
            color: var(--hijau);
            margin-right: 8px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 0 30px;
            }
            
            .hero-card {
                padding: 2rem 1.5rem;
            }
            
            .display-4 {
                font-size: 2.5rem;
            }
            
            .lead {
                font-size: 1.1rem;
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Section Spacing */
        .section-spacing {
            margin-bottom: 4rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tools"></i>
                <span>Sistem Peminjaman Alat</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Daftar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="hero-card animate-fade-in-up">
                        <h1 class="display-4 text-center fw-bold">
                            <i class="fas fa-tools me-3"></i>Sistem Peminjaman Alat Digital
                        </h1>
                        
                        <p class="lead text-center">
                            Sistem manajemen peminjaman alat dengan 3 role berbeda untuk memenuhi kebutuhan organisasi Anda.
                            Daftar sekarang dan pilih peran Anda: <strong class="text-hijau">Admin</strong>, 
                            <strong class="text-hijau">Petugas</strong>, atau <strong class="text-hijau">Peminjam</strong>.
                            Setiap role memiliki hak akses dan fitur yang disesuaikan dengan kebutuhan.
                        </p>
                        
                        <!-- Role Features Section -->
                        <div class="section-spacing">
                            <h3 class="text-center mb-5" style="color: var(--hijau);">
                                <i class="fas fa-users me-2"></i>Pilih Peran Anda
                            </h3>
                            
                            <div class="row g-4 mb-5">
                                <!-- Admin Card -->
                                <div class="col-md-4">
                                    <div class="feature-card">
                                        <div class="feature-icon">
                                            <i class="fas fa-users-cog"></i>
                                        </div>
                                        <h5>Admin</h5>
                                        <p>Kontrol penuh sistem dengan akses manajemen lengkap</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check"></i> Kelola Pengguna</li>
                                            <li><i class="fas fa-check"></i> Manajemen Alat Lengkap</li>
                                            <li><i class="fas fa-check"></i> Pengaturan Kategori</li>
                                            <li><i class="fas fa-check"></i> Laporan dan Analitik</li>
                                            <li><i class="fas fa-check"></i> Sistem Backlog</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Petugas Card -->
                                <div class="col-md-4">
                                    <div class="feature-card">
                                        <div class="feature-icon">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <h5>Petugas</h5>
                                        <p>Kelola transaksi peminjaman dan pengembalian alat</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check"></i> Verifikasi Peminjaman</li>
                                            <li><i class="fas fa-check"></i> Pantau Pengembalian</li>
                                            <li><i class="fas fa-check"></i> Hitung dan Tagih Denda</li>
                                            <li><i class="fas fa-check"></i> Cetak Laporan Harian</li>
                                            <li><i class="fas fa-check"></i> Monitoring Stok Alat</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Peminjam Card -->
                                <div class="col-md-4">
                                    <div class="feature-card">
                                        <div class="feature-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <h5>Peminjam</h5>
                                        <p>Ajukan peminjaman dan kelola riwayat peminjaman</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check"></i> Lihat Katalog Alat</li>
                                            <li><i class="fas fa-check"></i> Ajukan Peminjaman</li>
                                            <li><i class="fas fa-check"></i> Lacak Status Peminjaman</li>
                                            <li><i class="fas fa-check"></i> Riwayat Peminjaman</li>
                                            <li><i class="fas fa-check"></i> Pengembalian Online</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="text-center mb-5">
                            <div class="d-grid gap-3 d-md-flex justify-content-center">
                                <a href="register.php" class="btn btn-primary-custom">
                                    <i class="fas fa-user-plus"></i>
                                    Daftar Akun Baru
                                </a>
                                <a href="login.php" class="btn btn-outline-custom">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Login ke Sistem
                                </a>
                            </div>
                        </div>
                        
                        <!-- Registration Info Section -->
                        <div class="section-spacing">
                            <h3 class="text-center mb-5" style="color: var(--hijau);">
                                <i class="fas fa-info-circle me-2"></i>Informasi Pendaftaran
                            </h3>
                            
                            <div class="row g-4">
                                <!-- Cara Mendaftar -->
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <h5>
                                            <i class="fas fa-question-circle"></i>
                                            Cara Mendaftar
                                        </h5>
                                        <ol>
                                            <li>Klik tombol "Daftar Akun Baru" di atas</li>
                                            <li>Isi formulir pendaftaran dengan data lengkap dan valid</li>
                                            <li>Pilih role yang sesuai dengan kebutuhan Anda</li>
                                            <li>Verifikasi semua data yang telah dimasukkan</li>
                                            <li>Submit pendaftaran dan tunggu konfirmasi</li>
                                            <li>Login menggunakan username dan password Anda</li>
                                        </ol>
                                    </div>
                                </div>
                                
                                <!-- Informasi Penting -->
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <h5>
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Informasi Penting
                                        </h5>
                                        <ul>
                                            <li>Pilih role sesuai dengan tanggung jawab Anda</li>
                                            <li>Role Admin memiliki akses penuh ke seluruh sistem</li>
                                            <li>Username harus unik dan mudah diingat</li>
                                            <li>Password minimal 6 karakter dengan kombinasi huruf dan angka</li>
                                            <li>Simpan informasi login Anda dengan aman</li>
                                            <li>Sistem ini dikembangkan untuk keperluan pendidikan</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats (Optional) -->
                        <div class="text-center mt-5 pt-4 border-top">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-users fa-2x me-3" style="color: var(--hijau);"></i>
                                        <div class="text-start">
                                            <h4 class="mb-0" style="color: var(--hijau);">3 Role</h4>
                                            <small class="text-abu">Tersedia</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-tools fa-2x me-3" style="color: var(--hijau);"></i>
                                        <div class="text-start">
                                            <h4 class="mb-0" style="color: var(--hijau);">100+</h4>
                                            <small class="text-abu">Alat Terkelola</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-shield-alt fa-2x me-3" style="color: var(--hijau);"></i>
                                        <div class="text-start">
                                            <h4 class="mb-0" style="color: var(--hijau);">Aman</h4>
                                            <small class="text-abu">Sistem Terproteksi</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto text-center">
                    <h5 class="mb-3" style="color: var(--putih);">
                        <i class="fas fa-tools me-2"></i>
                        Sistem Peminjaman Alat
                    </h5>
                    <p class="mb-2">
                        SMK Rekayasa Perangkat Lunak
                    </p>
                    <p class="small">
                        <i class="fas fa-exclamation-triangle"></i>
                        Sistem ini dikembangkan untuk tujuan pendidikan dan pembelajaran
                    </p>
                    <p class="mt-3 small">
                        &copy; <?php echo date('Y'); ?> Aplikasi Peminjaman Alat - All Rights Reserved
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in-up');
                    }
                });
            }, observerOptions);
            
            // Observe all feature cards and info cards
            document.querySelectorAll('.feature-card, .info-card').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>