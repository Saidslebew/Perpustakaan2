# Sistem Perpustakaan v2 - PHP Native + MySQL

## Fitur Lengkap
- ✅ Autentikasi login/register (admin/user)
- ✅ Admin: CRUD buku/kategori, kelola peminjaman, denda otomatis
- ✅ User: Lihat/pinjam buku, riwayat peminjaman
- ✅ Pencarian, pagination, upload cover buku
- ✅ Validasi form, session security, PDO prepared statements
- ✅ Bootstrap UI modern, responsive

## Cara Menjalankan (XAMPP)

### 1. Persiapan
```
- Start Apache & MySQL di XAMPP Control Panel
- Buka phpMyAdmin: http://localhost/phpmyadmin
```

### 2. Setup Database
```
- Buat database baru: `perpustakaan2`
- Import file `database.sql` (sudah ada default admin: admin/admin123)
```

### 3. Akses Aplikasi
```
http://localhost/perpustakaan2/
```

### 4. Login
```
Admin: username=admin, password=admin123
User: Daftar baru di halaman register
```

## Struktur File
```
perpustakaan2/
├── config/database.php     # PDO koneksi
├── auth/                   # Login/register/logout
├── admin/                  # CRUD admin
├── user/                   # User features
├── assets/                 # CSS/JS/uploads
├── database.sql            # Schema + sample data
└── index.php               # Landing
```

## Catatan
- Upload gambar buku: max 2MB, jpg/png
- Denda: Rp1.000/hari keterlambatan
- Stok otomatis berkurang/bertambah
- Semua error handling dan validasi sudah ada

**Aplikasi siap production dengan sedikit config DB! 🚀**

