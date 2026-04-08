-- Database: perpustakaan2
-- Create database first in phpMyAdmin, then import this.

CREATE DATABASE IF NOT EXISTS perpustakaan2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan2;

-- Users table (admin/user roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher VARCHAR(100),
    year YEAR,
    stock INT DEFAULT 0,
    category_id INT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Borrowings table
CREATE TABLE borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL, -- expected return
    actual_return_date DATE NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Insert default admin (password: admin123 hashed)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@perpustakaan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample categories
INSERT INTO categories (name) VALUES 
('Fiksi'), ('Non-Fiksi'), ('Sains'), ('Sejarah');

-- Sample books
INSERT INTO books (title, author, publisher, year, stock, category_id) VALUES 
('PHP Native', 'John Doe', 'Gramedia', 2024, 5, 1),
('MySQL Guide', 'Jane Smith', 'Erlangga', 2023, 3, 3);
