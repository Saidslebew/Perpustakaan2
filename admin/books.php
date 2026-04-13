<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle CRUD
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_book'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $publisher = trim($_POST['publisher']);
        $year = $_POST['year'];
        $stock = (int)$_POST['stock'];
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        $upload_dir = '../assets/uploads/';
        $cover_image = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cover_image'];
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
                $cover_image = uniqid() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $upload_dir . $cover_image);
            }
        }
if (empty($title) || empty($author) || $stock < 0) {
    $message = 'alert-danger';
    $message_text = 'Data tidak valid!';
}
        $stmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year, stock, category_id, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $author, $publisher, $year, $stock, $category_id, $cover_image])) {
            $message = 'alert-success';
            $message_text = 'Buku berhasil ditambahkan!';
        }
    } elseif (isset($_POST['edit_book'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $publisher = trim($_POST['publisher']);
        $year = $_POST['year'];
        $stock = (int)$_POST['stock'];
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        $cover_image = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cover_image'];
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
                $cover_image = uniqid() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], '../assets/uploads/' . $cover_image);
            }
        }

        $update_fields = ["title = ?, author = ?, publisher = ?, year = ?, stock = ?, category_id = ?"];
        $update_params = [$title, $author, $publisher, $year, $stock, $category_id];
        
        if ($cover_image) {
            $update_fields[] = "cover_image = ?";
            $update_params[] = $cover_image;
        }

        $sql = "UPDATE books SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $update_params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($update_params)) {
            $message = 'alert-success';
            $message_text = 'Buku berhasil diupdate!';
        } else {
            $message = 'alert-danger';
            $message_text = 'Gagal update buku!';
        }
    } elseif (isset($_POST['delete_book'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'alert-success';
            $message_text = 'Buku berhasil dihapus!';
        }
    }
}

// Fetch books and categories
$books = $pdo->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku | Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Konsisten dengan dashboard & statistik */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --primary-dark: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            --success-blue: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --bg-primary: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-md: 0 10px 30px rgba(0,0,0,0.12);
            --shadow-lg: 0 20px 40px rgba(59,130,246,0.2);
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

        /* NAVBAR - IDENTIK dengan dashboard/statistik */
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

        /* 🔵 ACTION BUTTONS */
        .btn-add {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* TABLE STYLES */
        .table-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .table {
            margin: 0;
            background: transparent;
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

        .book-cover {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        /* 🔵 BUTTONS */
        .btn-action {
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-edit { 
            background: var(--primary-light); 
            color: white; 
        }
        .btn-edit:hover { 
            background: var(--primary-gradient); 
            transform: translateY(-1px);
        }

        .btn-delete { 
            background: #ef4444; 
            color: white; 
        }
        .btn-delete:hover { 
            background: #dc2626; 
            transform: translateY(-1px);
        }

        /* MODAL STYLES */
        .modal-content {
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 700;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid rgba(0,0,0,0.1);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.15);
        }

        /* ALERTS */
        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
            font-weight: 500;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-title { font-size: 2rem; }
            .book-cover { width: 50px; height: 65px; }
            .btn-action { padding: 0.4rem 0.8rem; font-size: 0.875rem; }
        }
    </style>
</head>

<body>
    <!-- NAVBAR - IDENTIK -->
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
                <i class="fas fa-book me-3"></i>
                Kelola Buku
            </h1>
            <p class="page-subtitle">
                Tambah, edit, dan hapus data buku perpustakaan
            </p>
        </div>

        <!-- Success/Error Message -->
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message ?> alert-dismissible fade show alert-custom mb-4" role="alert">
                <i class="fas fa-<?= $message === 'alert-success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= $message_text ?? '' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-muted"><i class="fas fa-list me-2"></i> Daftar Buku</h5>
            <button class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i>Tambah Buku
            </button>
        </div>

        <!-- Books Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-image me-1"></i>Cover</th>
                            <th><i class="fas fa-book me-1"></i>Judul</th>
                            <th><i class="fas fa-user me-1"></i>Penulis</th>
                            <th><i class="fas fa-building me-1"></i>Penerbit</th>
                            <th><i class="fas fa-calendar me-1"></i>Tahun</th>
                            <th><i class="fas fa-box me-1"></i>Stok</th>
                            <th><i class="fas fa-tag me-1"></i>Kategori</th>
                            <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-book fa-3x text-muted mb-3 opacity-50"></i>
                                    <h5 class="text-muted">Belum ada buku</h5>
                                    <p class="text-muted mb-0">Tambahkan buku pertama Anda</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <?php if ($book['cover_image']): ?>
                                        <img src="../assets/uploads/<?= htmlspecialchars($book['cover_image']) ?>" 
                                             alt="Cover" class="book-cover" 
                                             onerror="this.src='../assets/images/no-image.png'">
                                    <?php else: ?>
                                        <div class="book-cover bg-light d-flex align-items-center justify-content-center rounded text-muted">
                                            <i class="fas fa-book fa-lg"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($book['title']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= htmlspecialchars($book['publisher']) ?></td>
                                <td><?= $book['year'] ?: '-' ?></td>
                                <td>
                                    <span class="badge <?= $book['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $book['stock'] ?>
                                    </span>
                                </td>
                                <td><?= $book['category_name'] ?: '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-action btn-edit btn-sm"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editBookModal"
                                            data-id="<?= $book['id'] ?>"
                                            data-title="<?= htmlspecialchars($book['title']) ?>"
                                            data-author="<?= htmlspecialchars($book['author']) ?>"
                                            data-publisher="<?= htmlspecialchars($book['publisher']) ?>"
                                            data-year="<?= $book['year'] ?>"
                                            data-stock="<?= $book['stock'] ?>"
                                            data-category="<?= $book['category_id'] ?>">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus buku ini? Data tidak bisa dikembalikan!')">
                                            <input type="hidden" name="id" value="<?= $book['id'] ?>">
                                            <button type="submit" name="delete_book" class="btn btn-action btn-delete btn-sm">
                                                <i class="fas fa-trash me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Buku
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- ID (hidden) -->
                    <input type="hidden" name="id">

                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Penulis</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Penerbit</label>
                        <input type="text" name="publisher" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Tahun</label>
                        <input type="number" name="year" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Stok</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Cover (opsional)</label>
                        <input type="file" name="cover_image" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="edit_book" class="btn btn-primary">
                        Update Buku
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Penulis</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Penerbit</label>
                        <input type="text" name="publisher" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Tahun</label>
                        <input type="number" name="year" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Stok</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Cover</label>
                        <input type="file" name="cover_image" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_book" class="btn btn-success">
                        Tambah Buku
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- SCRIPT DI SINI -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editBookModal');

    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        const id = button.getAttribute('data-id');
        const title = button.getAttribute('data-title');
        const author = button.getAttribute('data-author');
        const publisher = button.getAttribute('data-publisher');
        const year = button.getAttribute('data-year');
        const stock = button.getAttribute('data-stock');
        const category = button.getAttribute('data-category');

        editModal.querySelector('input[name="id"]').value = id;
        editModal.querySelector('input[name="title"]').value = title;
        editModal.querySelector('input[name="author"]').value = author;
        editModal.querySelector('input[name="publisher"]').value = publisher;
        editModal.querySelector('input[name="year"]').value = year;
        editModal.querySelector('input[name="stock"]').value = stock;
        editModal.querySelector('select[name="category_id"]').value = category;
    });
});
</script>
</body>