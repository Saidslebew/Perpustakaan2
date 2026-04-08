<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// PAGINATION
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// FILTER
$search = $_GET['search'] ?? '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$params = [];

// QUERY DATA
$sql = "SELECT books.*, categories.name as category_name 
        FROM books 
        LEFT JOIN categories ON books.category_id = categories.id 
        WHERE 1=1";

if ($search) {
    $sql .= " AND (
        books.title LIKE ? 
        OR books.author LIKE ?
        OR books.publisher LIKE ?
    )";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category > 0) {
    $sql .= " AND books.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY books.title LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// COUNT
$count_sql = "SELECT COUNT(*) FROM books 
              LEFT JOIN categories ON books.category_id = categories.id 
              WHERE 1=1";

$count_params = [];

if ($search) {
    $count_sql .= " AND (
        books.title LIKE ? 
        OR books.author LIKE ? 
        OR books.publisher LIKE ?
    )";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
}

if ($category > 0) {
    $count_sql .= " AND books.category_id = ?";
    $count_params[] = $category;
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_books = $count_stmt->fetchColumn();
$total_pages = ceil($total_books / $limit);

// CATEGORIES
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// HANDLE PINJAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = (int)$_POST['book_id'];
    $borrow_days = (int)$_POST['borrow_days'] ?: 7;

    $stock_stmt = $pdo->prepare("SELECT stock FROM books WHERE id = ?");
    $stock_stmt->execute([$book_id]);
    $stock = $stock_stmt->fetchColumn();

   if ($stock > 0) {

    // CEK DUPLIKAT PINJAM
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM borrowings 
        WHERE user_id=? AND book_id=? AND status IN ('pending','borrowed')
    ");
    $check->execute([$user_id, $book_id]);

    if ($check->fetchColumn() > 0) {

        $message = '<div class="alert alert-warning">
            Kamu sudah meminjam atau masih menunggu konfirmasi buku ini!
        </div>';

    } else {

        $borrow_date = date('Y-m-d');
        $return_date = date('Y-m-d', strtotime("+$borrow_days days"));

        $stmt = $pdo->prepare("
            INSERT INTO borrowings (user_id, book_id, borrow_date, return_date, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");

        if ($stmt->execute([$user_id, $book_id, $borrow_date, $return_date])) {

            $message = '<div class="alert alert-info">
                Permintaan peminjaman dikirim! Menunggu konfirmasi admin.
            </div>';
        }

    }

} else {
    $message = '<div class="alert alert-danger">Stok habis!</div>';
}
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Buku</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary">
<div class="container">
    <a class="navbar-brand" href="dashboard.php">Perpustakaan</a>
    <div>
        <a href="dashboard.php" class="nav-link d-inline me-3">Dashboard</a>
        <a href="books.php" class="nav-link d-inline me-3">Buku</a>
        <a href="history.php" class="nav-link d-inline me-3">Riwayat</a>
        <a href="../auth/logout.php" class="nav-link d-inline">Logout</a>
    </div>
</div>
</nav>

<div class="container mt-4">

<h2>Daftar Buku</h2>
<?= $message ?>

<div class="row mb-4 align-items-center">

    <!-- KATEGORI -->
    <div class="col-md-8 mb-2">
        <div class="d-flex flex-wrap gap-2">
            <a href="?category=0&search=<?= urlencode($search) ?>" 
               class="btn btn-outline-primary <?= $category==0?'active':'' ?>">
                Semua
            </a>

            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>" 
                   class="btn btn-outline-primary <?= $category==$cat['id']?'active':'' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="col-md-4">
        <form method="GET">
            <input type="hidden" name="category" value="<?= $category ?>">

            <div class="search-modern">
                <input type="text" name="search"
                    placeholder="Cari judul atau penulis..."
                    value="<?= htmlspecialchars($search) ?>">
                <button type="submit">🔍</button>
            </div>
        </form>
    </div>

</div>

<!-- GRID -->
<div class="book-container">
<?php foreach ($books as $book): ?>
<div class="book-card">

    <div class="book-body">
        <img 
        src="../assets/uploads/<?= $book['cover_image'] ?: 'default.jpg' ?>" 
        class="book-cover preview-img"
        data-img="../assets/uploads/<?= $book['cover_image'] ?: 'default.jpg' ?>"
        data-title="<?= htmlspecialchars($book['title']) ?>"
        data-author="<?= htmlspecialchars($book['author']) ?>"
        data-publisher="<?= htmlspecialchars($book['publisher']) ?>"
        data-year="<?= $book['year'] ?>"
        data-category="<?= htmlspecialchars($book['category_name']) ?>">
        
        <div class="book-info">
            <h5><?= htmlspecialchars($book['title']) ?></h5>

            <?php if ($book['category_name']): ?>
                <span class="badge bg-info"><?= htmlspecialchars($book['category_name']) ?></span>
            <?php endif; ?>

            <p>
                Penulis: <?= htmlspecialchars($book['author']) ?><br>
                Penerbit: <?= htmlspecialchars($book['publisher']) ?><br>
                Tahun: <?= $book['year'] ?>
            </p>
        </div>
    </div>

    <div class="book-footer">
        <span class="badge bg-<?= $book['stock']>0?'success':'danger' ?>">
            Stok: <?= $book['stock'] ?>
        </span>

        <?php if ($book['stock'] > 0): ?>
        <button class="btn btn-primary mt-2 w-100 borrow-btn"
            data-id="<?= $book['id'] ?>"
            data-title="<?= htmlspecialchars($book['title']) ?>">
            Pinjam
        </button>
        <?php else: ?>
        <button class="btn btn-secondary mt-2 w-100" disabled>Habis</button>
        <?php endif; ?>
    </div>

</div>
<?php endforeach; ?>
</div>

<!-- PAGINATION -->
<div class="mt-4">
<ul class="pagination justify-content-center">

<!-- PREV -->
<?php if ($page > 1): ?>
<li class="page-item">
    <a class="page-link" href="?page=<?= $page-1 ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
        « Prev
    </a>
</li>
<?php endif; ?>

<?php
$start = max(1, $page - 1);
$end = min($total_pages, $page + 1);

for ($i = $start; $i <= $end; $i++):
?>

<li class="page-item <?= $i == $page ? 'active' : '' ?>">
    <a class="page-link" href="?page=<?= $i ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
        <?= $i ?>
    </a>
</li>

<?php endfor; ?>

<!-- NEXT -->
<?php if ($page < $total_pages): ?>
<li class="page-item">
    <a class="page-link" href="?page=<?= $page+1 ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">
        Next »
    </a>
</li>
<?php endif; ?>

</ul>
</div>

<!-- MODAL PINJAM -->
<div class="modal fade" id="borrowModal">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">

<div class="modal-header">
<h5>Konfirmasi Pinjam</h5>
</div>

<div class="modal-body">
<input type="hidden" name="book_id" id="book_id">

<p id="book_title"></p>

<select name="borrow_days" id="days" class="form-control">
<option value="3">3 Hari</option>
<option value="5">5 Hari</option>
<option value="7" selected>7 Hari</option>
</select>

<p class="mt-2">Kembali: <span id="return_date"></span></p>

</div>

<div class="modal-footer">
<button type="submit" name="borrow_book" class="btn btn-success">Konfirmasi</button>
</div>

</form>
</div>
</div>
</div>

<!-- MODAL PREVIEW DETAIL -->
<div class="modal fade" id="imageModal">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-4">

        <div class="row">
            <div class="col-md-4 text-center">
                <img id="previewImage" class="img-fluid rounded shadow">
            </div>

            <div class="col-md-8">
                <h4 id="previewTitle"></h4>
                <span id="previewCategory" class="badge bg-primary mb-2"></span>

                <p>
                    <strong>Penulis:</strong> <span id="previewAuthor"></span><br>
                    <strong>Penerbit:</strong> <span id="previewPublisher"></span><br>
                    <strong>Tahun:</strong> <span id="previewYear"></span>
                </p>
            </div>
        </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // PINJAM
    document.querySelectorAll('.borrow-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('book_id').value = this.dataset.id;
            document.getElementById('book_title').innerText = this.dataset.title;

            new bootstrap.Modal(document.getElementById('borrowModal')).show();
            updateDate();
        });
    });

    document.getElementById('days').addEventListener('change', updateDate);

    function updateDate() {
        let d = parseInt(document.getElementById('days').value);
        let date = new Date();
        date.setDate(date.getDate() + d);
        document.getElementById('return_date').innerText = date.toLocaleDateString('id-ID');
    }

    // PREVIEW DETAIL
    document.querySelectorAll('.preview-img').forEach(img => {
        img.addEventListener('click', function() {

            document.getElementById('previewImage').src = this.dataset.img;
            document.getElementById('previewTitle').innerText = this.dataset.title;
            document.getElementById('previewAuthor').innerText = this.dataset.author;
            document.getElementById('previewPublisher').innerText = this.dataset.publisher;
            document.getElementById('previewYear').innerText = this.dataset.year;
            document.getElementById('previewCategory').innerText = this.dataset.category;

            new bootstrap.Modal(document.getElementById('imageModal')).show();
        });
    });

});
</script>

</body>
</html>