<?php
require_once '../config/database.php';

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT b.*, bk.title, u.username 
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id=?
");
$stmt->execute([$id]);
$data = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Peminjaman</title>
    <style>
        body { font-family: Arial; text-align: center; }
        .card { border:1px solid #000; padding:20px; width:300px; margin:auto; }
    </style>
</head>
<body onload="window.print()">

<div class="card">
    <h3>Bukti Peminjaman</h3>
    <hr>
    <p>User: <?= $data['username'] ?></p>
    <p>Buku: <?= $data['title'] ?></p>
    <p>Tgl Pinjam: <?= $data['borrow_date'] ?></p>
    <p>Kembali: <?= $data['return_date'] ?></p>
    <hr>
    <p>Status: <?= $data['status'] ?></p>
</div>

</body>
</html>