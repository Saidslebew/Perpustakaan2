<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// ==========================
// 📊 DATA STATISTIK
// ==========================
$monthly = $pdo->query("
    SELECT 
        DATE_FORMAT(borrow_date, '%M %Y') as bulan,
        COUNT(*) as total
    FROM borrowings
    WHERE status = 'borrowed'
    GROUP BY YEAR(borrow_date), MONTH(borrow_date)
    ORDER BY YEAR(borrow_date), MONTH(borrow_date)
")->fetchAll();

$bulan_labels = [];
$bulan_data = [];

foreach ($monthly as $m) {
    $bulan_labels[] = $m['bulan'];
    $bulan_data[] = $m['total'];
}

$books = $pdo->query("
    SELECT bk.title, COUNT(*) as total
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.status = 'borrowed'
    GROUP BY b.book_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

$users = $pdo->query("
    SELECT u.username, COUNT(*) as total
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'borrowed'
    GROUP BY b.user_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik | Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Semua ungu diganti biru */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --bg-primary: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-md: 0 10px 30px rgba(0,0,0,0.12);
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
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .card-header {
            background: none;
            border: none;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stat-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-item:hover {
            /* 🔵 Blue hover effect */
            background: rgba(59, 130, 246, 0.08);
            padding-left: 2rem;
        }

        .stat-name {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .stat-value {
            font-weight: 700;
            font-size: 1.1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .chart-container {
            position: relative;
            height: 400px;
            padding: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }
            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book me-2"></i>Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
               <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                <a class="nav-link" href="books.php"><i class="fas fa-book me-1"></i>Buku</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-tags me-1"></i>Kategori</a>
                <a class="nav-link active" href="borrowings.php"><i class="fas fa-handshake me-1"></i>Peminjaman</a>
                <a class="nav-link" href="statistik.php"><i class="fas fa-chart-bar me-1"></i>Statistik</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="main-title">
                <i class="fas fa-chart-line"></i>
                Statistik Perpustakaan
            </h1>
        </div>

        <div class="row g-4">
            <!-- CHART CARD -->
            <div class="col-lg-8">
                <div class="stat-card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar text-primary"></i>
                            Peminjaman per Bulan
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- USER AKTIF CARD -->
            <div class="col-lg-4">
                <div class="stat-card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users text-success"></i>
                            User Paling Aktif
                        </h5>
                    </div>
                    <div class="p-0">
                        <?php if ($users): ?>
                            <?php foreach ($users as $index => $u): ?>
                                <div class="stat-item">
                                    <div class="stat-name"><?= htmlspecialchars($u['username']) ?></div>
                                    <div class="stat-value"><?= $u['total'] ?>x</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h6>Belum ada data</h6>
                                <p>Belum ada user yang meminjam buku</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-4">
            <!-- BUKU TERPOPULER -->
            <div class="col-12">
                <div class="stat-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-fire text-warning"></i>
                            Buku Terpopuler
                        </h5>
                    </div>
                    <div class="p-0">
                        <?php if ($books): ?>
                            <?php foreach ($books as $index => $b): ?>
                                <div class="stat-item">
                                    <div class="stat-name"><?= htmlspecialchars($b['title']) ?></div>
                                    <div class="stat-value"><?= $b['total'] ?>x</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <h6>Belum ada data</h6>
                                <p>Belum ada buku yang dipinjam</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        
        // 🔵 Blue gradient untuk chart
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
        gradient.addColorStop(0.5, 'rgba(29, 78, 216, 0.6)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.2)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($bulan_labels) ?: '[]' ?>,
                datasets: [{
                    label: 'Peminjaman',
                    data: <?= json_encode($bulan_data) ?: '[]' ?>,
                    backgroundColor: gradient,
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 12,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12, weight: '500' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            font: { size: 12, weight: '500' }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
    </script>
</body>
</html>