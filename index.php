<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Perpustakaan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    height: 100vh;
    margin: 0;
    background: linear-gradient(135deg, #0d6efd, #3a8bfd);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}

.card-custom {
    background: white;
    border-radius: 15px;
    padding: 40px;
    width: 380px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.card-custom h1 {
    font-size: 26px;
    font-weight: bold;
    color: #0d6efd;
}

.card-custom p {
    color: #555;
    margin-bottom: 25px;
}

.btn-login {
    background: #0d6efd;
    border: none;
    color: white;
    border-radius: 8px;
    padding: 10px;
    width: 100%;
    margin-bottom: 10px;
    transition: 0.3s;
}

.btn-login:hover {
    background: #0b5ed7;
    transform: scale(1.03);
}

.btn-register {
    background: transparent;
    border: 2px solid #0d6efd;
    color: #0d6efd;
    border-radius: 8px;
    padding: 10px;
    width: 100%;
    transition: 0.3s;
}

.btn-register:hover {
    background: #0d6efd;
    color: white;
}

.icon {
    font-size: 45px;
    margin-bottom: 10px;
}
</style>
</head>

<body>

<div class="card-custom">
    <div class="icon">📖</div>

    <h1>Perpustakaan</h1>
    <p>Sistem peminjaman buku digital</p>

    <a href="auth/login.php" class="btn btn-login">Login</a>
    <a href="auth/register.php" class="btn btn-register">Daftar</a>
</div>

</body>
</html>