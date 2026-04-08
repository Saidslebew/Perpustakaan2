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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Perpustakaan</a>
        <div class="navbar-nav">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link" href="books.php">Buku</a>
            <a class="nav-link active" href="#">Riwayat</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Riwayat Peminjaman Saya</h2>

    <?php if (empty($history)): ?>
        <div class="alert alert-info">Belum ada riwayat peminjaman.</div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Tgl Kembali</th>
                    <th>Status</th>
                    <th>Denda</th>
                    <th>Aksi</th> <!-- 🔥 KOLOM BARU -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $record): ?>
                <tr class="<?= $record['status'] === 'overdue' ? 'table-danger' : ''; ?>">
                    
                    <td><?= htmlspecialchars($record['title']); ?></td>

                    <td><?= date('d/m/Y', strtotime($record['borrow_date'])); ?></td>

                    <td><?= date('d/m/Y', strtotime($record['return_date'])); ?></td>

                    <td>
                        <span class="badge bg-<?=
                            $record['status'] === 'pending' ? 'secondary' :
                            ($record['status'] === 'borrowed' ? 'warning' :
                            ($record['status'] === 'returned' ? 'success' : 'danger'))
                        ?>">
                            <?= ucfirst($record['status']); ?>
                        </span>
                    </td>

                    <td>Rp <?= number_format($record['fine_amount']); ?></td>

                    <!-- 🔥 TOMBOL PRINT -->
                    <td>
                        <?php if ($record['status'] === 'borrowed'): ?>
                            <a href="print.php?id=<?= $record['id'] ?>" 
                               target="_blank"
                               class="btn btn-sm btn-dark"
                               title="Cetak bukti peminjaman">
                               🖨 Print
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>