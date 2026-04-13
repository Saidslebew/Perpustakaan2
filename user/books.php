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
    $sql .= " AND (books.title LIKE ? OR books.author LIKE ? OR books.publisher LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

if ($category > 0) {
    $sql .= " AND books.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY books.title LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// COUNT TOTAL UNTUK PAGINATION
$count_sql = "SELECT COUNT(*) FROM books WHERE 1=1";
$count_params = [];
if ($search) {
    $count_sql .= " AND (title LIKE ? OR author LIKE ? OR publisher LIKE ?)";
    $count_params[] = "%$search%"; $count_params[] = "%$search%"; $count_params[] = "%$search%";
}
if ($category > 0) {
    $count_sql .= " AND category_id = ?";
    $count_params[] = $category;
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_books = $count_stmt->fetchColumn();
$total_pages = ceil($total_books / $limit);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// HANDLE PINJAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = (int)$_POST['book_id'];
    $borrow_days = (int)$_POST['borrow_days'] ?: 7;

    $stock_stmt = $pdo->prepare("SELECT stock FROM books WHERE id = ?");
    $stock_stmt->execute([$book_id]);
    $stock = $stock_stmt->fetchColumn();

    if ($stock > 0) {
        $checkActive = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id=? AND status IN ('pending','borrowed')");
        $checkActive->execute([$user_id]);

        if ($checkActive->fetchColumn() > 0) {
            $message = '<div class="alert alert-warning alert-custom">Kamu masih memiliki pinjaman aktif atau menunggu konfirmasi!</div>';
        } else {
            $borrow_date = date('Y-m-d');
            $return_date = date('Y-m-d', strtotime("+$borrow_days days"));
            $stmt = $pdo->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, return_date, status) VALUES (?, ?, ?, ?, 'pending')");
            if ($stmt->execute([$user_id, $book_id, $borrow_date, $return_date])) {
                $message = '<div class="alert alert-success alert-custom">Permintaan peminjaman dikirim! Mohon tunggu konfirmasi admin.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger alert-custom">Maaf, stok buku ini sedang habis!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku | User Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --bg-primary: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-lg: 0 25px 50px rgba(59,130,246,0.15);
            --border-radius: 20px;
        }

        body {
            background: var(--bg-primary);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        /* NAVBAR IDENTIK */
        .navbar {
            background: var(--primary-gradient) !important;
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; color: white !important; }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s;
            margin: 0 0.25rem;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }

        /* HEADER */
        .page-header {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .main-title {
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* SEARCH & FILTER */
        .search-modern {
            display: flex;
            background: white;
            padding: 5px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .search-modern input {
            border: none;
            padding: 10px 20px;
            width: 100%;
            outline: none;
            border-radius: 15px;
        }
        .search-modern button {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
        }

        /* BOOK CARD */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .book-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        .book-cover-wrapper {
            height: 250px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }
        .book-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .book-card:hover .book-cover { transform: scale(1.1); }
        .book-content { padding: 1.5rem; flex-grow: 1; }
        .book-title { font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; }
        .book-meta { font-size: 0.9rem; color: #64748b; margin-bottom: 1rem; }
        .book-footer {
            padding: 1.25rem;
            background: rgba(0,0,0,0.02);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* CUSTOM ALERT */
        .alert-custom {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .category-btn {
            border-radius: 12px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-book me-2"></i>User Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                <a class="nav-link active" href="books.php"><i class="fas fa-book me-1"></i>Buku</a>
                <a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i>Riwayat</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="page-header d-md-flex justify-content-between align-items-center text-center text-md-start">
            <div>
                <h1 class="main-title mb-1">E-Library Explorer</h1>
                <p class="page-subtitle mb-0">
    Temukan dan pinjam buku favoritmu 
</p>
            </div>
            <div class="mt-3 mt-md-0">
                <form method="GET" class="search-modern">
                    <input type="hidden" name="category" value="<?= $category ?>">
                    <input type="text" name="search" placeholder="Cari judul/penulis..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <?= $message ?>

        <div class="mb-4 d-flex flex-wrap gap-2 justify-content-center">
            <a href="?category=0&search=<?= urlencode($search) ?>" 
               class="btn category-btn <?= $category==0 ? 'btn-primary' : 'btn-outline-primary bg-white' ?>">
                Semua Koleksi
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>" 
                   class="btn category-btn <?= $category==$cat['id'] ? 'btn-primary' : 'btn-outline-primary bg-white' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="book-grid">
            <?php foreach ($books as $book): ?>
            <div class="book-card">
                <div class="book-cover-wrapper preview-img" 
                     data-img="../assets/uploads/<?= $book['cover_image'] ?: 'default.jpg' ?>"
                     data-title="<?= htmlspecialchars($book['title']) ?>"
                     data-author="<?= htmlspecialchars($book['author']) ?>"
                     data-publisher="<?= htmlspecialchars($book['publisher']) ?>"
                     data-year="<?= $book['year'] ?>"
                     data-category="<?= htmlspecialchars($book['category_name']) ?>">
                    <img src="../assets/uploads/<?= $book['cover_image'] ?: 'default.jpg' ?>" class="book-cover" alt="Cover">
                </div>
                
                <div class="book-content">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-soft-primary text-primary px-3 py-2" style="background: rgba(59,130,246,0.1)"><?= htmlspecialchars($book['category_name']) ?></span>
                        <span class="text-<?= $book['stock']>0?'success':'danger' ?> small fw-bold">
                            <i class="fas fa-circle me-1" style="font-size: 8px"></i> 
                            Stok: <?= $book['stock'] ?>
                        </span>
                    </div>
                    <h5 class="book-title"><?= htmlspecialchars($book['title']) ?></h5>
                    <div class="book-meta">
                        <i class="fas fa-pen-nib me-1"></i> <?= htmlspecialchars($book['author']) ?><br>
                        <i class="fas fa-building me-1"></i> <?= htmlspecialchars($book['publisher']) ?>
                    </div>
                </div>

                <div class="book-footer">
                    <?php if ($book['stock'] > 0): ?>
                        <button class="btn btn-primary w-100 borrow-btn py-2 shadow-sm"
                                data-id="<?= $book['id'] ?>"
                                data-title="<?= htmlspecialchars($book['title']) ?>"
                                style="border-radius: 12px; font-weight: 600;">
                            <i class="fas fa-bookmark me-2"></i>Pinjam Buku
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 py-2" disabled style="border-radius: 12px;">Habis</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link border-0 shadow-sm mx-1 rounded-3" href="?page=<?= $page-1 ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">Prev</a>
                </li>
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link border-0 shadow-sm mx-1 rounded-3 <?= $i==$page?'bg-primary text-white':'' ?>" href="?page=<?= $i ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link border-0 shadow-sm mx-1 rounded-3" href="?page=<?= $page+1 ?>&category=<?= $category ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="borrowModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <form method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i>Konfirmasi Pinjam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="book_id" id="book_id">
                        <h6 id="book_title" class="mb-4 text-primary fw-bold"></h6>
                        <label class="form-label fw-semibold">Durasi Peminjaman</label>
                        <select name="borrow_days" id="days" class="form-select mb-3" style="border-radius: 10px;">
                            <option value="3">3 Hari</option>
                            <option value="5">5 Hari</option>
                            <option value="7" selected>7 Hari</option>
                        </select>
                        <div class="alert alert-info py-2" style="border-radius: 10px; font-size: 0.9rem;">
                            Estimasi Pengembalian: <span id="return_date" class="fw-bold"></span>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="borrow_book" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 12px;">Konfirmasi Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 p-4" style="border-radius: 25px;">
            <div class="row g-4">
                <div class="col-md-5">
                    <img id="previewImage" class="img-fluid rounded shadow-lg w-100">
                </div>
                <div class="col-md-7">
                    <button type="button" class="btn-close float-end" data-bs-dismiss="modal"></button>
                    <h3 id="previewTitle" class="fw-bold mb-1"></h3>
                    <span id="previewCategory" class="badge bg-primary mb-3 px-3 py-2"></span>
                    <hr>
                    <p class="mb-2"><strong><i class="fas fa-user me-2"></i>Penulis:</strong> <span id="previewAuthor"></span></p>
                    <p class="mb-2"><strong><i class="fas fa-building me-2"></i>Penerbit:</strong> <span id="previewPublisher"></span></p>
                    <p class="mb-0"><strong><i class="fas fa-calendar me-2"></i>Tahun:</strong> <span id="previewYear"></span></p>
                </div>
            </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // PINJAM MODAL
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
            document.getElementById('return_date').innerText = date.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        // PREVIEW MODAL
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