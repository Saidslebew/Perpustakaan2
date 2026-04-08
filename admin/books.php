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
        $category_id = (int)$_POST['category_id'] ?: null;

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

        $stmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year, stock, category_id, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $author, $publisher, $year, $stock, $category_id, $cover_image])) {
            $message = '<div class="alert alert-success">Buku berhasil ditambahkan!</div>';
        }
    } elseif (isset($_POST['edit_book'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $publisher = trim($_POST['publisher']);
        $year = $_POST['year'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'] ?: null;

        // Handle image update (optional)
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
            $message = '<div class="alert alert-success">Buku berhasil diupdate!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal update buku!</div>';
        }
    } elseif (isset($_POST['delete_book'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Buku berhasil dihapus!</div>';
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
    <title>Kelola Buku - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <div class="navbar-nav">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="#">Buku</a>
                <a class="nav-link" href="categories.php">Kategori</a>
                <a class="nav-link" href="borrowings.php">Peminjaman</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Kelola Buku</h2>
        <?php echo $message; ?>

        <!-- Add Book Form (Modal trigger) -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addBookModal">+ Tambah Buku</button>

        <!-- Books Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Penerbit</th>
                        <th>Tahun</th>
                        <th>Stok</th>
                        <th>Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td>
                            <?php if ($book['cover_image']): ?>
                                <img src="../assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Cover" class="book-cover">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                        <td><?php echo $book['year']; ?></td>
                        <td><span class="badge bg-<?php echo $book['stock'] > 0 ? 'success' : 'danger'; ?>"><?php echo $book['stock']; ?></span></td>
                        <td><?php echo $book['category_name'] ?: '-'; ?></td>
                       <td>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-warning edit-btn"
            data-bs-toggle="modal" 
            data-bs-target="#editBookModal"
            data-id="<?= $book['id']; ?>"
            data-title="<?= htmlspecialchars($book['title']); ?>"
            data-author="<?= htmlspecialchars($book['author']); ?>"
            data-publisher="<?= htmlspecialchars($book['publisher']); ?>"
            data-year="<?= $book['year']; ?>"
            data-stock="<?= $book['stock']; ?>"
            data-category="<?= $book['category_id']; ?>">
            Edit
        </button>

        <form method="POST" onsubmit="return confirm('Hapus buku ini?')">
            <input type="hidden" name="id" value="<?= $book['id']; ?>">
            <button type="submit" name="delete_book" class="btn btn-sm btn-danger">
                Hapus
            </button>
        </form>
    </div>
</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="editBookForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Buku</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label>Judul *</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Penulis *</label>
                                <input type="text" class="form-control" name="author" id="edit_author" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Penerbit</label>
                                <input type="text" class="form-control" name="publisher" id="edit_publisher">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Tahun</label>
                                <input type="number" class="form-control" name="year" id="edit_year" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Stok *</label>
                                <input type="number" class="form-control" name="stock" id="edit_stock" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Kategori</label>
                                <select class="form-control" name="category_id" id="edit_category">
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Gambar Baru (opsional)</label>
                            <input type="file" class="form-control" name="cover_image" accept="image/*">
                            <div class="form-text">Biarkan kosong untuk mempertahankan gambar lama</div>
                            <img id="edit_preview" style="max-width:200px; margin-top:10px; display:none;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_book" class="btn btn-warning">Update Buku</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="addBookForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Buku Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Judul</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Penulis</label>
                                <input type="text" class="form-control" name="author" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Penerbit</label>
                                <input type="text" class="form-control" name="publisher">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Tahun</label>
                                <input type="number" class="form-control" name="year" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Stok</label>
                                <input type="number" class="form-control" name="stock" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Kategori</label>
                                <select class="form-control" name="category_id">
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Cover Image</label>
                            <input type="file" class="form-control" name="cover_image" accept="image/*">
                            <img id="preview" style="max-width:200px; margin-top:10px; display:none;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_book" class="btn btn-success">Tambah Buku</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // Preview image
        previewImage('cover_image', 'preview');
        validateForm('addBookForm');
    </script>
</body>
</html>
