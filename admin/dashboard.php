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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Dashboard Admin</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Total Buku</h5>
                        <h2><?php echo $total_books; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5>Total User</h5>
                        <h2><?php echo $total_users; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5>Buku Dipinjam</h5>
                        <h2><?php echo $borrowed_books; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5>Peminjaman</h5>
                        <h2><?php echo $pdo->query("SELECT COUNT(*) FROM borrowings")->fetchColumn(); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="books.php" class="btn btn-primary w-100 h-100 p-4 text-start">
                    <h4>Buku</h4>
                    <p>Kelola data buku dan stok</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="categories.php" class="btn btn-success w-100 h-100 p-4 text-start">
                    <h4>Kategori</h4>
                    <p>Kelola kategori buku</p>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="borrowings.php" class="btn btn-warning w-100 h-100 p-4 text-start">
                    <h4>Peminjaman</h4>
                    <p>Konfirmasi dan riwayat</p>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

