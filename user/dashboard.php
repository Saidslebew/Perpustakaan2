<?php
session_start();
require_once '../config/database.php';

// Check user access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Hitung peminjaman aktif
$stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
$stmt->execute([$user_id]);
$total_borrow = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR (SUDAH KONSISTEN BIRU) -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Perpustakaan</a>

        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">
                Halo, <?= htmlspecialchars($_SESSION['username']) ?>
            </span>

            <a class="nav-link" href="books.php">Buku</a>
            <a class="nav-link" href="history.php">Riwayat</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <h2 class="mb-4">Dashboard</h2>

    <!-- CARD INFO -->
    <div class="row g-4">

        <!-- PEMINJAMAN -->
        <div class="col-md-6">
            <div class="card text-center p-4">
                <h5>Peminjaman Aktif</h5>
                <h1 class="text-primary"><?= $total_borrow ?></h1>
                <a href="history.php" class="btn btn-outline-primary mt-2">Lihat Riwayat</a>
            </div>
        </div>

        <!-- AKSI -->
        <div class="col-md-6">
            <div class="card text-center p-4">
                <h5>Cari Buku</h5>
                <p class="text-muted">Temukan dan pinjam buku yang kamu inginkan</p>
                <a href="books.php" class="btn btn-primary">Lihat Buku</a>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>