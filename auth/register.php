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
        // Note: Pastikan fungsi hashPassword() ada di config
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
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
    <title>Daftar Akun | Perpustakaan Digital</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            /* 🔵 BLUE THEME - Konsisten 100% */
            --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --primary-light: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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

        /* Animated background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
            width: 420px;
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
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-icon {
            background: var(--primary-gradient);
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            margin-bottom: 2rem;
            font-weight: 500;
        }

        /* Form Styling */
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

        .form-floating > label {
            color: #6b7280;
            font-weight: 500;
            padding-left: 1.25rem;
        }

        /* Buttons */
        .btn-register {
            background: var(--primary-gradient);
            border: none;
            border-radius: 16px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            height: 56px;
            transition: all 0.4s ease;
            box-shadow: var(--shadow-md);
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .btn-login-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        /* Alerts */
        .alert-custom {
            border-radius: 16px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            font-weight: 500;
        }

        .alert-success-custom {
            background: rgba(16,185,129,0.15);
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .alert-danger-custom {
            background: rgba(239,68,68,0.15);
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        /* Password strength */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .auth-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <!-- Icon -->
        <div class="auth-icon">
            <i class="fas fa-user-plus"></i>
        </div>

        <!-- Title -->
        <h1 class="auth-title">Buat Akun Baru</h1>
        <p class="auth-subtitle">
            Daftar untuk mulai meminjam buku digital
        </p>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Success -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Register Form -->
        <form method="POST" id="registerForm" novalidate>
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Username" required maxlength="50" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <label for="username">
                    <i class="fas fa-user me-1"></i>Username
                </label>
            </div>

            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <label for="email">
                    <i class="fas fa-envelope me-1"></i>Email
                </label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Password" required minlength="6">
                <label for="password">
                    <i class="fas fa-lock me-1"></i>Password
                </label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="Konfirmasi Password" required minlength="6">
                <label for="confirm_password">
                    <i class="fas fa-lock me-1"></i>Konfirmasi Password
                </label>
            </div>

            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus me-2"></i>
                Daftar Akun
            </button>
        </form>

        <!-- Login Link -->
        <div class="text-center mt-3">
            <p class="mb-0 text-muted">
                Sudah punya akun? 
                <a href="login.php" class="btn-login-link">
                    <i class="fas fa-sign-in-alt me-1"></i>Masuk sekarang
                </a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        const confirmPassword = document.getElementById('confirm_password');

        passwordInput.addEventListener('input', function() {
            const strength = calculateStrength(this.value);
            strengthBar.className = `password-strength strength-${strength}`;
        });

        function calculateStrength(password) {
            let score = 0;
            if (password.length >= 6) score++;
            if (password.match(/[a-z]/)) score++;
            if (password.match(/[A-Z]/)) score++;
            if (password.match(/[0-9]/)) score++;
            if (password.match(/[^a-zA-Z0-9]/)) score++;
            
            if (score <= 2) return 'weak';
            if (score <= 4) return 'medium';
            return 'strong';
        }

        // Real-time password match validation
        confirmPassword.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#10b981';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmPassword.value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Password dan konfirmasi tidak cocok!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>