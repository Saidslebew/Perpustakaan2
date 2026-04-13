<?php
session_start();
require_once '../config/database.php';

// Cek jika sudah login, arahkan ke index
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verifikasi password menggunakan password_verify
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../user/dashboard.php');
            }
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Perpustakaan Digital</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Konsisten dengan Register */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --bg-primary: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-lg: 0 25px 50px rgba(59,130,246,0.2);
            --shadow-md: 0 15px 35px rgba(0,0,0,0.1);
            --border-radius: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background identik */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(59,130,246,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(96,165,250,0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 3rem 2.5rem;
            width: 420px; /* Lebar disamakan dengan Register */
            max-width: 90vw;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--primary-gradient);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-icon {
            background: var(--primary-gradient);
            width: 80px; height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            box-shadow: var(--shadow-md);
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .auth-subtitle {
            color: #64748b;
            text-align: center;
            margin-bottom: 2.5rem;
            font-weight: 500;
        }

        /* Form Styling disamakan */
        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 16px;
            border: 2px solid rgba(0,0,0,0.1);
            padding: 1rem 1.25rem;
            font-size: 1rem;
            background: rgba(255,255,255,0.9);
            transition: all 0.3s ease;
            height: 60px;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.25rem rgba(59,130,246,0.15);
            background: white;
        }

        /* Button Styling disamakan */
        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: 16px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            height: 56px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s ease;
            box-shadow: var(--shadow-md);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .alert-custom {
            border-radius: 16px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            background: rgba(239,68,68,0.15);
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .btn-register-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-register-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="auth-icon">
            <i class="fas fa-sign-in-alt"></i>
        </div>

        <h1 class="auth-title">Selamat Datang</h1>
        <p class="auth-subtitle">Masuk untuk mengakses perpustakaan</p>

        <?php if ($error): ?>
            <div class="alert alert-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <label for="username">
                    <i class="fas fa-user me-1"></i>Username
                </label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Password" required>
                <label for="password">
                    <i class="fas fa-lock me-1"></i>Password
                </label>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Masuk Sekarang
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="mb-0 text-muted">
                Belum punya akun? 
                <a href="register.php" class="btn-register-link">
                    Buat akun baru
                </a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alert
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>
</html>