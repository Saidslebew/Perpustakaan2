<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "Semua field wajib diisi!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok!";
    } else {
        // Check existing user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username atau email sudah terdaftar!";
        }
    }

    if (empty($errors)) {
        $hashed_password = hashPassword($password);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password])) {
            $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
            header('Location: login.php');
            exit;
        } else {
            $errors[] = "Gagal mendaftar. Coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Daftar Akun Baru</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required maxlength="50">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php">Sudah punya akun? Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>validateForm('registerForm');</script>
</body>
</html>

