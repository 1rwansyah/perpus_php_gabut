CREATE DATABASE perpustakaan;
USE perpustakaan;

-- =====================
-- USERS (admin, operator, user)
-- =====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin','operator','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- KATEGORI
-- =====================
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100)
);

-- =====================
-- BUKU
-- =====================
CREATE TABLE buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255),
    author VARCHAR(100),
    tahun_terbit YEAR,
    kategori_id INT,
    cover VARCHAR(255),
    cover_position ENUM('top','left','right') DEFAULT 'top',
    cover_width INT DEFAULT 200,
    cover_height INT DEFAULT 0,
    file_buku VARCHAR(255) NULL,
    file_buku_nama VARCHAR(255) NULL,
    isi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
);

-- =====================
-- PEMINJAMAN
-- =====================
CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tanggal_pinjam DATE,
    tanggal_kembali DATE,
    returned_at DATE NULL,
    operator_confirmed_at DATE NULL,
    status ENUM('dipinjam','dikembalikan') DEFAULT 'dipinjam',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =====================
-- DETAIL PEMINJAMAN
-- =====================
CREATE TABLE detail_peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT,
    buku_id INT,
    jumlah INT DEFAULT 1,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id),
    FOREIGN KEY (buku_id) REFERENCES buku(id)
);

-- =====================
-- CHAPTERS / BAB BUKU
-- =====================
CREATE TABLE chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buku_id INT NOT NULL,
    nomor INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    isi LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_buku_nomor (buku_id, nomor),
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
);

-- =====================
-- NOTIFIKASI / MAINTENANCE
-- =====================
CREATE TABLE pengumuman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255),
    isi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_saved_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_buku (user_id, buku_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
);

CREATE TABLE user_read_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    chapter_id INT NOT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_buku (user_id, buku_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
);