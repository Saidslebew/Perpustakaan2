<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// =====================
// KONFIRMASI PINJAMAN
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_borrow'])) {
    $id = (int)$_POST['id'];

    $stmt = $pdo->prepare("SELECT book_id FROM borrowings WHERE id=?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if ($data) {
        // ubah status jadi borrowed
        $pdo->prepare("UPDATE borrowings SET status='borrowed' WHERE id=?")->execute([$id]);

        // kurangi stok
        $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE id=?")->execute([$data['book_id']]);

        $_SESSION['success'] = "Peminjaman disetujui!";
    }
}

// =====================
// KEMBALIKAN BUKU
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $id = (int)$_POST['id'];
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT b.return_date, b.book_id 
        FROM borrowings b 
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    $borrowing = $stmt->fetch();

    if ($borrowing) {
        $late_days = (strtotime($today) - strtotime($borrowing['return_date'])) / (60*60*24);
        $fine = $late_days > 0 ? max(0, $late_days * 1000) : 0;

        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET actual_return_date = ?, status = 'returned', fine_amount = ? 
            WHERE id = ?
        ");
        $stmt->execute([$today, $fine, $id]);

        // kembalikan stok
        $pdo->prepare("UPDATE books SET stock = stock + 1 WHERE id = ?")
            ->execute([$borrowing['book_id']]);

        $_SESSION['success'] = "Buku berhasil dikembalikan. Denda: Rp " . number_format($fine);
    }
}

// =====================
// AMBIL DATA
// =====================
$borrowings = $pdo->query("
    SELECT b.*, u.username, bk.title 
    FROM borrowings b 
    JOIN users u ON b.user_id = u.id 
    JOIN books bk ON b.book_id = bk.id 
    ORDER BY b.borrow_date DESC
")->fetchAll();

// Hitung stats
$total_pending = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'pending'")->fetchColumn();
$total_borrowed = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'borrowed'")->fetchColumn();
$total_returned = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'returned'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman | Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Konsisten 100% */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --primary-dark: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            --success-blue: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

        /* NAVBAR - IDENTIK 100% */
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
        }

        /* 🔵 STATS CARDS */
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-lg);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
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

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
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

        /* STATUS BADGES */
        .status-badge {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .status-pending { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); color: #6b7280; }
        .status-borrowed { background: var(--warning-blue); color: white; }
        .status-returned { background: var(--success-blue); color: white; }

        /* ACTION BUTTONS */
        .btn-confirm {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-return {
            background: var(--success-blue);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* ALERT */
        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-title { font-size: 2rem; }
            .stat-card { margin-bottom: 1rem; }
        }
    </style>
</head>

<body>
    <!-- NAVBAR - IDENTIK 100% -->
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
        <!-- Header -->
        <div class="page-header">
            <h1 class="main-title">
                <i class="fas fa-handshake me-3"></i>
                Kelola Peminjaman
            </h1>
            <p class="page-subtitle">
                Konfirmasi peminjaman dan pengembalian buku
            </p>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-custom mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-clock stat-icon" style="background: var(--primary-gradient); color: white; padding: 1rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="stat-number"><?= $total_pending ?></div>
                    <div class="text-muted fw-semibold">Menunggu</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-book-open stat-icon" style="background: var(--warning-blue); color: white; padding: 1rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="stat-number"><?= $total_borrowed ?></div>
                    <div class="text-muted fw-semibold">Dipinjam</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-check-double stat-icon" style="background: var(--success-blue); color: white; padding: 1rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="stat-number"><?= $total_returned ?></div>
                    <div class="text-muted fw-semibold">Dikembalikan</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-list stat-icon" style="background: var(--primary-light); color: white; padding: 1rem; border-radius: 50%; display: inline-block; box-shadow: var(--shadow-sm);"></i>
                    <div class="stat-number"><?= count($borrowings) ?></div>
                    <div class="text-muted fw-semibold">Total</div>
                </div>
            </div>
        </div>

        <!-- Borrowings Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-user me-1"></i>User</th>
                            <th><i class="fas fa-book me-1"></i>Buku</th>
                            <th><i class="fas fa-calendar-day me-1"></i>Tgl Pinjam</th>
                            <th><i class="fas fa-calendar-check me-1"></i>Tgl Kembali</th>
                            <th><i class="fas fa-info-circle me-1"></i>Status</th>
                            <th><i class="fas fa-coins me-1"></i>Denda</th>
                            <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($borrowings)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-handshake-slash fa-3x mb-3 opacity-50"></i>
                                    <h5>Belum ada peminjaman</h5>
                                    <p class="mb-0">Menunggu peminjaman pertama</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($borrowings as $b): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark fs-6 px-2 py-1">
                                        #<?= str_pad($b['id'], 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($b['username']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($b['title']) ?></div>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-day text-muted me-1"></i>
                                    <?= date('d/m/Y', strtotime($b['borrow_date'])) ?>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-check text-muted me-1"></i>
                                    <?= date('d/m/Y', strtotime($b['return_date'])) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $b['status'] ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>Rp <?= number_format($b['fine_amount'] ?? 0) ?></strong>
                                </td>
                                   <td>
    <?php if ($b['status'] === 'pending'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button type="submit" name="confirm_borrow" 
                class="btn btn-confirm btn-sm"
                onclick="return confirm('Setujui peminjaman ini?')">
                <i class="fas fa-check me-1"></i>Konfirmasi
            </button>
        </form>

    <?php elseif ($b['status'] === 'borrowed'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button type="submit" name="return_book" 
                class="btn btn-return btn-sm"
                onclick="return confirm('Kembalikan buku ini?')">
                <i class="fas fa-undo me-1"></i>Kembalikan
            </button>
        </form>
    <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div> <!-- container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>