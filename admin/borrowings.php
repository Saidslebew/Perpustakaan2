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

        $_SESSION['success'] = "Buku dikembalikan. Denda: Rp " . number_format($fine);
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Peminjaman - Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary">
<div class="container">
    <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
    <div class="navbar-nav d-flex flex-row">
        <a class="nav-link me-3" href="dashboard.php">Dashboard</a>
        <a class="nav-link me-3" href="books.php">Buku</a>
        <a class="nav-link me-3" href="categories.php">Kategori</a>
        <a class="nav-link active me-3" href="#">Peminjaman</a>
        <a class="nav-link" href="../auth/logout.php">Logout</a>
    </div>
</div>
</nav>

<div class="container mt-4">

<h2>Data Peminjaman</h2>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Buku</th>
    <th>Tgl Pinjam</th>
    <th>Tgl Kembali</th>
    <th>Status</th>
    <th>Denda</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>
<?php foreach ($borrowings as $b): ?>
<tr>

<td><?= $b['id'] ?></td>
<td><?= htmlspecialchars($b['username']) ?></td>
<td><?= htmlspecialchars($b['title']) ?></td>
<td><?= date('d/m/Y', strtotime($b['borrow_date'])) ?></td>
<td><?= date('d/m/Y', strtotime($b['return_date'])) ?></td>

<!-- STATUS -->
<td>
<span class="badge bg-<?php 
    echo $b['status'] === 'pending' ? 'secondary' : 
         ($b['status'] === 'borrowed' ? 'warning' : 
         ($b['status'] === 'returned' ? 'success' : 'danger')); 
?>">
    <?= ucfirst($b['status']) ?>
</span>
</td>

<td>Rp <?= number_format($b['fine_amount']) ?></td>

<!-- AKSI -->
<td>

<?php if ($b['status'] === 'pending'): ?>

<form method="POST" style="display:inline;">
    <input type="hidden" name="id" value="<?= $b['id'] ?>">
    <button type="submit" name="confirm_borrow" 
        class="btn btn-sm btn-primary me-1"
        onclick="return confirm('Setujui peminjaman ini?')">
        Setujui
    </button>
</form>

<?php elseif ($b['status'] === 'borrowed'): ?>

<form method="POST" style="display:inline;">
    <input type="hidden" name="id" value="<?= $b['id'] ?>">
    <button type="submit" name="return_book" 
        class="btn btn-sm btn-success"
        onclick="return confirm('Konfirmasi pengembalian?')">
        Kembalikan
    </button>
</form>

<?php endif; ?>

</td>

</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>