<?php
/**
 * config/database.php
 * Satu-satunya tempat pengaturan dan pembuatan koneksi database (PDO).
 * Jika password MySQL Anda bukan kosong, ubah DB_PASS di bawah.
 */

const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'KasirKu';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

/**
 * Mengembalikan koneksi PDO (dibuat sekali, dipakai ulang).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Detail error hanya masuk log server, tidak ditampilkan ke pengguna.
        error_log('[KasirKu] Koneksi database gagal: ' . $e->getMessage());
        halaman_error(
            "Tidak dapat terhubung ke database. Pastikan MySQL pada XAMPP sudah dijalankan dan database 'KasirKu' sudah diimpor.",
            503
        );
    }
    return $pdo;
}
