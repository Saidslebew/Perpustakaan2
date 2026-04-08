<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            if ($stmt->execute([$name])) {
                $message = '<div class="alert alert-success">Kategori berhasil ditambahkan!</div>';
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Kategori berhasil dihapus!</div>';
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <div class="navbar-nav">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="books.php">Buku</a>
                <a class="nav-link active" href="#">Kategori</a>
                <a class="nav-link" href="borrowings.php">Peminjaman</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Kelola Kategori</h2>
        <?php echo $message; ?>

        <!-- Add Category Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST">
                    <div class="input-group">
                        <input type="text" class="form-control" name="name" placeholder="Nama kategori baru..." required>
                        <button type="submit" name="add_category" class="btn btn-success">Tambah</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Categories Table -->
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Jumlah Buku</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo $category['id']; ?></td>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td>
                        <?php 
                        $count = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
                        $count->execute([$category['id']]);
                        echo $count->fetchColumn();
                        ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus kategori ini? Buku terkait akan kehilangan kategori.')">
                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                            <button type="submit" name="delete_category" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

