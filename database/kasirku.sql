-- =====================================================================
-- KasirKu - Skrip Database
-- File     : database/KasirKu.sql
-- Cara pakai: impor lewat phpMyAdmin (tab "Impor"), lihat README.md
-- Catatan  : Mengimpor ulang file ini akan MENGHAPUS data lama dan
--            mengembalikan data awal (berguna untuk mengulang pengujian).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS KasirKu
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE KasirKu;

-- ---------------------------------------------------------------------
-- Pengguna dan role
-- Akun demo: admin/admin123 dan kasir/kasir123. Ganti password setelah login.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (nama, username, password, role) VALUES
('Administrator', 'admin', '$2y$12$oZfgU25zzZJIJpweWyPdEefPWVC9sxvfRJ/1NDn9BenSCe/d9nmse', 'admin'),
('Kasir', 'kasir', '$2y$12$3CM02SwrKYt.g99.T.gb7uOLrRXXoIhGLuSMQK0HKkFeDdnROlwC.', 'kasir');


-- Hapus tabel lama (urutan: anak dulu, baru induk)
DROP TABLE IF EXISTS detail_transaksi;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS produk;

-- ---------------------------------------------------------------------
-- Tabel produk
-- ---------------------------------------------------------------------
CREATE TABLE produk (
    id_produk   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nama_produk VARCHAR(100)  NOT NULL,
    harga       DECIMAL(12,2) NOT NULL,
    stok        INT           NOT NULL DEFAULT 0,
    PRIMARY KEY (id_produk),
    KEY idx_produk_nama (nama_produk),
    CONSTRAINT chk_produk_nama  CHECK (CHAR_LENGTH(TRIM(nama_produk)) > 0),
    CONSTRAINT chk_produk_harga CHECK (harga > 0),
    CONSTRAINT chk_produk_stok  CHECK (stok >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel transaksi
-- ---------------------------------------------------------------------
CREATE TABLE transaksi (
    id_transaksi      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    tanggal_transaksi DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total             DECIMAL(12,2) NOT NULL,
    bayar             DECIMAL(12,2) NOT NULL,
    kembalian         DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id_transaksi),
    KEY idx_transaksi_tanggal (tanggal_transaksi),
    CONSTRAINT chk_transaksi_total     CHECK (total > 0),
    CONSTRAINT chk_transaksi_bayar     CHECK (bayar >= total),
    CONSTRAINT chk_transaksi_kembalian CHECK (kembalian >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel detail_transaksi
-- nama_produk dan harga_satuan disalin saat transaksi, sehingga
-- perubahan produk di kemudian hari tidak mengubah riwayat lama.
-- ---------------------------------------------------------------------
CREATE TABLE detail_transaksi (
    id_detail    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    id_transaksi INT UNSIGNED  NOT NULL,
    id_produk    INT UNSIGNED  NOT NULL,
    nama_produk  VARCHAR(100)  NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    jumlah       INT           NOT NULL,
    subtotal     DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id_detail),
    KEY idx_detail_transaksi (id_transaksi),
    KEY idx_detail_produk (id_produk),
    CONSTRAINT fk_detail_transaksi
        FOREIGN KEY (id_transaksi) REFERENCES transaksi (id_transaksi)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_produk
        FOREIGN KEY (id_produk) REFERENCES produk (id_produk)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_detail_jumlah   CHECK (jumlah > 0),
    CONSTRAINT chk_detail_harga    CHECK (harga_satuan > 0),
    CONSTRAINT chk_detail_subtotal CHECK (subtotal > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Data awal produk (untuk pengujian)
-- Ada produk dengan stok menipis (<= 5) dan stok 0 untuk uji skenario.
-- Tidak ada data transaksi awal: statistik dashboard dimulai dari nol
-- dan bertambah sesuai transaksi yang benar-benar dilakukan.
-- ---------------------------------------------------------------------
INSERT INTO produk (nama_produk, harga, stok) VALUES
    ('Beras Premium 5 kg',      68000.00, 25),
    ('Minyak Goreng 1 L',       18500.00, 40),
    ('Gula Pasir 1 kg',         16000.00, 30),
    ('Telur Ayam 1 kg',         29000.00, 20),
    ('Mie Instan Goreng',        3500.00, 100),
    ('Kopi Sachet',              2000.00, 150),
    ('Teh Celup 25 pcs',         7500.00, 4),
    ('Susu UHT 250 ml',          6500.00, 3),
    ('Sabun Mandi Batang',       4500.00, 60),
    ('Air Mineral 600 ml',       3000.00, 0);
