<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$history = $pdo->prepare("
    SELECT b.*, bk.title 
    FROM borrowings b 
    JOIN books bk ON b.book_id = bk.id 
    WHERE b.user_id = ? 
    ORDER BY b.borrow_date DESC
");
$history->execute([$user_id]);
$history = $history->fetchAll();

// Hitung stats user
$pending_count = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'pending'")->execute([$user_id]);
$pending_count = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE user_id = $user_id AND status = 'pending'")->fetchColumn();
$borrowed_count = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE user_id = $user_id AND status = 'borrowed'")->fetchColumn();
$returned_count = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE user_id = $user_id AND status = 'returned'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman | User Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - IDENTIK 100% DENGAN ADMIN */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --primary-dark: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            --success-blue: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --warning-blue: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --bg-primary: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-md: 0 10px 30px rgba(0,0,0,0.12);
            --shadow-lg: 0 25px 50px rgba(59,130,246,0.2);
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
        }

        /* NAVBAR - IDENTIK 100% DENGAN ADMIN */
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

        .page-header {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #64748b;
            font-weight: 500;
            font-size: 1.1rem;
        }

        /* USER STATS CARDS */
        .user-stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .user-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .user-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .user-stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .user-stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* TABLE */
        .table-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
            padding: 1.25rem 1rem;
        }

        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.05);
        }

        .table tbody tr:hover {
            background: rgba(59,130,246,0.05);
        }

        /* STATUS BADGES - IDENTIK */
        .status-badge {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .status-pending { 
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); 
            color: #6b7280; 
        }
        .status-borrowed { 
            background: var(--warning-blue); 
            color: white; 
        }
        .status-returned { 
            background: var(--success-blue); 
            color: white; 
        }

        /* PRINT BUTTON */
        .btn-print {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white !important;
            text-decoration: none;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 5rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-title { font-size: 2rem; }
            .user-stat-card { margin-bottom: 1rem; }
        }

        /* ALERT */
        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body>
    <!-- NAVBAR - IDENTIK 100% DENGAN ADMIN -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book me-2"></i>User Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                <a class="nav-link" href="books.php"><i class="fas fa-book me-1"></i>Buku</a>
                <a class="nav-link active" href="history.php"><i class="fas fa-history me-1"></i>Riwayat</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header -->
        <div class="page-header">
            <h1 class="main-title">
                <i class="fas fa-history me-3"></i>
                Riwayat Peminjaman
            </h1>
            <p class="page-subtitle">
                Lihat semua buku yang pernah kamu pinjam
            </p>
        </div>

        <!-- User Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="user-stat-card">
                    <i class="fas fa-clock user-stat-icon" style="background: var(--primary-gradient); color: white; padding: 1.5rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="user-stat-number"><?= $pending_count ?></div>
                    <div class="text-muted fw-semibold fs-5">Menunggu</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="user-stat-card">
                    <i class="fas fa-book-open user-stat-icon" style="background: var(--warning-blue); color: white; padding: 1.5rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="user-stat-number"><?= $borrowed_count ?></div>
                    <div class="text-muted fw-semibold fs-5">Dipinjam</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="user-stat-card">
                    <i class="fas fa-check-double user-stat-icon" style="background: var(--success-blue); color: white; padding: 1.5rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="user-stat-number"><?= $returned_count ?></div>
                    <div class="text-muted fw-semibold fs-5">Dikembalikan</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="user-stat-card">
                    <i class="fas fa-list user-stat-icon" style="background: var(--primary-light); color: white; padding: 1.5rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="user-stat-number"><?= count($history) ?></div>
                    <div class="text-muted fw-semibold fs-5">Total</div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-book me-1"></i>Buku</th>
                            <th><i class="fas fa-calendar-day me-1"></i>Tgl Pinjam</th>
                            <th><i class="fas fa-calendar-check me-1"></i>Tgl Kembali</th>
                            <th><i class="fas fa-info-circle me-1"></i>Status</th>
                            <th><i class="fas fa-coins me-1"></i>Denda</th>
                            <th><i class="fas fa-print me-1"></i>Cetak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <h5>Belum ada riwayat</h5>
                                        <p class="mb-0">Pinjam buku pertama kamu sekarang!</p>
                                        <a href="books.php" class="btn btn-primary mt-3">Cari Buku</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $record): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($record['title']) ?></div>
                                    <small class="text-muted">#<?= str_pad($record['id'], 3, '0', STR_PAD_LEFT) ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-day text-muted me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($record['borrow_date'])) ?>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-check text-muted me-1"></i>
                                    <?= date('d/m/Y', strtotime($record['return_date'])) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $record['status'] ?>">
                                        <?= ucfirst($record['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-danger">Rp <?= number_format($record['fine_amount'] ?? 0) ?></strong>
                                </td>
                                <td>
                                    <?php if ($record['status'] === 'borrowed'): ?>
                                        <a href="print.php?id=<?= $record['id'] ?>" 
                                           target="_blank"
                                           class="btn-print btn-sm">
                                           <i class="fas fa-print"></i> Print
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>