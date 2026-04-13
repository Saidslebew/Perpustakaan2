<?php
session_start();
require_once '../config/database.php';

// Check user access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 📊 STATISTIK USER
$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
$stmt->execute([$user_id]);
$total_borrow = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_borrow = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_history = $stmt->fetchColumn();

// Recent borrowings
$recent = $pdo->prepare("
    SELECT b.*, books.title, books.cover_image 
    FROM borrowings b 
    JOIN books ON b.book_id = books.id 
    WHERE b.user_id = ? 
    ORDER BY b.borrow_date DESC 
    LIMIT 5
");
$recent->execute([$user_id]);
$recent_borrowings = $recent->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Perpustakaan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - IDENTIK dengan statistik.php & books.php */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --bg-primary: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-md: 0 10px 30px rgba(0,0,0,0.12);
            --shadow-lg: 0 20px 50px rgba(0,0,0,0.15);
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

        /* NAVBAR - IDENTIK SEMUA HALAMAN */
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
            text-decoration: none;
        }

        .nav-link:hover, .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .user-welcome {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 3rem;
            text-align: center;
        }

        /* STAT CARDS */
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 1.5rem;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--primary-gradient);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .stat-primary .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .stat-warning .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-success .stat-icon {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1rem;
            color: #4a5568;
            font-weight: 500;
        }

        /* RECENT BORROWINGS */
        .recent-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .recent-item {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item:hover {
            background: rgba(59, 130, 246, 0.05);
            padding-left: 2rem;
        }

        .recent-cover {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .recent-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .recent-date {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-borrowed {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        /* QUICK ACTIONS */
        .action-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            text-align: center;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0.5rem;
            min-width: 200px;
        }

        .btn-primary-custom {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.4);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
            background: transparent;
        }

        .btn-outline-custom:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- NAVBAR - IDENTIK SEMUA HALAMAN -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book me-2"></i>Perpustakaan
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 mt-1" style="color: rgba(255,255,255,0.9); font-weight: 500;">
                    <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a class="nav-link active" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- WELCOME -->
        <div class="text-center mb-5">
            <h1 class="main-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <div class="user-welcome">
                <i class="fas fa-user-circle text-primary"></i>
                Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- STATISTIK CARDS -->
            <div class="col-lg-4 col-md-6">
                <a href="history.php" class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-number"><?= $total_borrow ?></div>
                    <div class="stat-label">Peminjaman Aktif</div>
                </a>
            </div>

            <div class="col-lg-4 col-md-6">
                <a href="history.php" class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= $pending_borrow ?></div>
                    <div class="stat-label">Menunggu Konfirmasi</div>
                </a>
            </div>

            <div class="col-lg-4 col-md-6">
                <a href="history.php" class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-number"><?= $total_history ?></div>
                    <div class="stat-label">Total Riwayat</div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- QUICK ACTIONS -->
            <div class="col-lg-6">
                <div class="action-card">
                    <h5 class="mb-4">
                        <i class="fas fa-bolt text-primary me-2"></i>Aksi Cepat
                    </h5>
                    <div>
                        <a href="books.php" class="action-btn btn-primary-custom">
                            <i class="fas fa-search"></i>
                            Cari Buku Baru
                        </a>
                        <a href="history.php" class="action-btn btn-outline-custom">
                            <i class="fas fa-list"></i>
                            Lihat Riwayat
                        </a>
                    </div>
                </div>
            </div>

            <!-- RECENT BORROWINGS -->
            <div class="col-lg-6">
                <div class="recent-card">
                    <div class="card-header d-flex justify-content-between align-items-center p-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-recent text-primary me-2"></i>
                            Peminjaman Terbaru
                        </h5>
                        <a href="history.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    
                    <div class="p-0">
                        <?php if ($recent_borrowings): ?>
                            <?php foreach ($recent_borrowings as $borrow): ?>
                                <div class="recent-item">
                                    <img src="../assets/uploads/<?= $borrow['cover_image'] ?: 'default.jpg' ?>" 
                                         class="recent-cover" alt="<?= htmlspecialchars($borrow['title']) ?>">
                                    <div class="flex-grow-1">
                                        <div class="recent-title"><?= htmlspecialchars($borrow['title']) ?></div>
                                        <div class="recent-date">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d M Y', strtotime($borrow['borrow_date'])) ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?= $borrow['status'] ?>">
                                        <i class="fas fa-circle me-1"></i>
                                        <?= ucfirst($borrow['status']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                                <p>Belum ada riwayat peminjaman</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>