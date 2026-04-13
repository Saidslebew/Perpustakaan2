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
                $message = 'alert-success';
                $message_text = 'Kategori berhasil ditambahkan!';
            } else {
                $message = 'alert-danger';
                $message_text = 'Gagal menambahkan kategori!';
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'alert-success';
            $message_text = 'Kategori berhasil dihapus!';
        } else {
            $message = 'alert-danger';
            $message_text = 'Gagal menghapus kategori!';
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
    <title>Kelola Kategori | Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Konsisten dengan semua halaman */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --primary-dark: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            --success-blue: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

        /* 🔵 ADD FORM */
        .add-form-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .add-form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .input-group-text {
            background: var(--primary-gradient);
            border: none;
            color: white;
            border-radius: 12px 0 0 12px;
            font-weight: 500;
        }

        .btn-add-cat {
            background: var(--success-blue);
            border: none;
            border-radius: 0 12px 12px 0;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-add-cat:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* TABLE CONTAINER */
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

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(59,130,246,0.05);
            transform: scale(1.01);
        }

        .category-badge {
            background: var(--primary-light);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        /* 🔵 DELETE BUTTON */
        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* ALERTS */
        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-title { font-size: 2rem; }
            .add-form-card { padding: 1.5rem; }
            .input-group { flex-direction: column; }
            .btn-add-cat { border-radius: 12px; margin-top: 0.5rem; }
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
                <i class="fas fa-tags me-3"></i>
                Kelola Kategori
            </h1>
            <p class="page-subtitle">
                Tambah dan kelola kategori buku perpustakaan
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

        <!-- Add Category Form -->
        <div class="add-form-card position-relative">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form method="POST" class="d-flex">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text">
                                <i class="fas fa-tag"></i>
                            </span>
                            <input type="text" class="form-control" 
                                   name="name" 
                                   placeholder="Masukkan nama kategori baru (contoh: Fiksi, Non-Fiksi, Sains...)"
                                   maxlength="50" 
                                   required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-add-cat ms-2">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                    </form>
                </div>
                <div class="col-md-4 text-end text-md-start mt-3 mt-md-0">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Maksimal 50 karakter
                    </small>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-tag me-1"></i>Nama Kategori</th>
                            <th><i class="fas fa-book me-1"></i>Jumlah Buku</th>
                            <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-tags-slash"></i>
                                    <h5>Belum ada kategori</h5>
                                    <p class="mb-0">Tambahkan kategori pertama untuk mengelompokkan buku</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark fs-6 px-2 py-1">#<?= str_pad($category['id'], 2, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td>
                                    <div>
                                        <span class="category-badge"><?= htmlspecialchars($category['name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $count = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
                                    $count->execute([$category['id']]);
                                    $book_count = $count->fetchColumn();
                                    ?>
                                    <span class="badge bg-<?= $book_count > 0 ? 'success' : 'secondary' ?>">
                                        <?= $book_count ?> <?= $book_count == 1 ? 'buku' : 'buku' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Hapus kategori \"<?= htmlspecialchars($category['name']) ?>\"? Buku terkait akan kehilangan kategori (tidak terhapus).')">
                                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                        <button type="submit" name="delete_category" class="btn btn-delete btn-sm">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stats Card -->
        <?php if (!empty($categories)): ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card text-center p-4 bg-primary text-white rounded-4 shadow-lg" 
                     style="background: var(--primary-gradient);">
                    <i class="fas fa-tags fa-3x mb-3 opacity-75"></i>
                    <h3 class="mb-1"><?= count($categories) ?></h3>
                    <p class="mb-0 opacity-90">Total Kategori</p>
                </div>
            </div>
            <div class="col-md-6">
                <?php 
                $total_books = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
                ?>
                <div class="card text-center p-4 bg-success text-white rounded-4 shadow-lg" 
                     style="background: var(--success-blue);">
                    <i class="fas fa-book fa-3x mb-3 opacity-75"></i>
                    <h3 class="mb-1"><?= $total_books ?></h3>
                    <p class="mb-0 opacity-90">Total Buku</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Input focus effect
        document.querySelector('input[name="name"]').addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        document.querySelector('input[name="name"]').addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    </script>
</body>
</html>