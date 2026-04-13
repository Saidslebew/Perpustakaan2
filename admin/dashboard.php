<?php
session_start();
require_once '../config/database.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Stats
$total_books = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$borrowed_books = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status != 'returned'")->fetchColumn();
$total_borrowings = $pdo->query("SELECT COUNT(*) FROM borrowings")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Konsisten dengan statistik.php */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --primary-dark: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            --success-blue: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --bg-primary: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-md: 0 10px 30px rgba(0,0,0,0.12);
            --shadow-lg: 0 20px 40px rgba(59,130,246,0.2);
            --border-radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            backdrop-filter: blur(10px);
        }

        /* NAVBAR - IDENTIK dengan statistik.php */
        .navbar {
            background: var(--primary-gradient) !important;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(45deg, #fff, rgba(255,255,255,0.9));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .nav-link:hover, .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .subtitle {
            color: #64748b;
            font-weight: 500;
            font-size: 1.1rem;
        }

        /* 🔵 STAT CARDS - FULL BLUE GRADIENT */
        .stat-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            height: 140px;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(59,130,246,0.3);
        }

        .stat-card .card-body {
            position: relative;
            z-index: 2;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:nth-child(1) { background: var(--primary-gradient); }
        .stat-card:nth-child(2) { background: var(--primary-light); }
        .stat-card:nth-child(3) { background: var(--primary-dark); }
        .stat-card:nth-child(4) { background: var(--success-blue); }

        .stat-number {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            color: white !important;
        }

        .stat-label {
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            opacity: 0.9;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        /* 🔵 ACTION CARDS - FULL BLUE GRADIENT */
        .action-card {
            border: none;
            border-radius: var(--border-radius);
            height: 200px;
            transition: all 0.4s ease;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            color: white;
        }

        .action-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 50px rgba(59,130,246,0.4);
            text-decoration: none;
            color: white;
        }

        .action-card:nth-child(1) { background: var(--primary-gradient); }
        .action-card:nth-child(2) { background: var(--primary-light); }
        .action-card:nth-child(3) { background: var(--primary-dark); }

        .action-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.95;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .action-title {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .action-desc {
            color: rgba(255,255,255,0.9);
            text-align: center;
            font-size: 0.95rem;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .page-header {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 3rem 2.5rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }

        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }
            .stat-card {
                height: 120px;
            }
            .action-card {
                height: 160px;
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- NAVBAR - IDENTIK dengan statistik.php -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book me-2"></i>Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                <a class="nav-link" href="statistik.php"><i class="fas fa-chart-bar me-1"></i>Statistik</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header -->
        <div class="page-header fade-in-up">
            <h1 class="main-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard Admin
            </h1>
            <p class="subtitle">
                Kelola perpustakaan digital Anda dengan mudah dan efisien
            </p>
        </div>

        <!-- Success Alert -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-custom mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 🔵 STAT CARDS - FULL BLUE GRADIENT -->
        <div class="row g-4 mb-5 fade-in-up" style="animation-delay: 0.2s;">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card">
                    <i class="fas fa-book stat-icon"></i>
                    <div class="card-body">
                        <p class="stat-label">Total Buku</p>
                        <h2 class="stat-number"><?= number_format($total_books) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card">
                    <i class="fas fa-users stat-icon"></i>
                    <div class="card-body">
                        <p class="stat-label">Total User</p>
                        <h2 class="stat-number"><?= number_format($total_users) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card">
                    <i class="fas fa-book-open stat-icon"></i>
                    <div class="card-body">
                        <p class="stat-label">Buku Dipinjam</p>
                        <h2 class="stat-number"><?= number_format($borrowed_books) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card">
                    <i class="fas fa-exchange-alt stat-icon"></i>
                    <div class="card-body">
                        <p class="stat-label">Total Peminjaman</p>
                        <h2 class="stat-number"><?= number_format($total_borrowings) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔵 ACTION CARDS - FULL BLUE GRADIENT -->
        <div class="row g-4 fade-in-up" style="animation-delay: 0.4s;">
            <div class="col-lg-4 col-md-6">
                <a href="books.php" class="action-card">
                    <i class="fas fa-book action-icon"></i>
                    <h4 class="action-title">Buku</h4>
                    <p class="action-desc">Kelola data buku dan stok</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="categories.php" class="action-card">
                    <i class="fas fa-tags action-icon"></i>
                    <h4 class="action-title">Kategori</h4>
                    <p class="action-desc">Kelola kategori buku</p>
                </a>
            </div>
            <div class="col-lg-4 col-md-12">
                <a href="borrowings.php" class="action-card">
                    <i class="fas fa-handshake action-icon"></i>
                    <h4 class="action-title">Peminjaman</h4>
                    <p class="action-desc">Konfirmasi dan riwayat</p>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>