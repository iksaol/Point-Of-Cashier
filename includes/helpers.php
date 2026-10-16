<?php
/**
 * includes/helpers.php
 * Fungsi bantu yang dipakai bersama oleh semua halaman dan action.
 * File ini wajib di-include paling awal (ia juga memuat config/database.php).
 */

// Detail error tidak ditampilkan ke pengguna; error tetap dicatat di log PHP.
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ---------------------------------------------------------------------
// Konstanta aplikasi
// ---------------------------------------------------------------------
const STOK_MENIPIS = 5;          // stok <= 5 dianggap menipis
const MAKS_HARGA   = 999999999;  // batas harga (Rp999.999.999)
const MAKS_STOK    = 1000000;    // batas stok per produk

/**
 * Alamat dasar aplikasi, misalnya "/KasirKu/".
 * Dihitung otomatis dari lokasi folder terhadap document root Apache.
 */
function hitung_base_url(): string
{
    $root = realpath(__DIR__ . '/..');
    $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($root !== false && $doc !== false) {
        $root = str_replace('\\', '/', $root);
        $doc  = rtrim(str_replace('\\', '/', $doc), '/');
        if ($doc !== '' && stripos($root, $doc) === 0) {
            return rtrim(substr($root, strlen($doc)), '/') . '/';
        }
    }
    return '/KasirKu/';
}
define('BASE_URL', hitung_base_url());

// ---------------------------------------------------------------------
// Penanganan error
// ---------------------------------------------------------------------
function halaman_error(string $pesan, int $kode = 500): void
{
    if (!headers_sent()) {
        http_response_code($kode);
    }
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Terjadi Kesalahan - KasirKu</title>'
        . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
        . 'background:#f0f5fb;font-family:"Segoe UI",system-ui,Arial,sans-serif;color:#16263d}'
        . '.box{background:#fff;border:1px solid #dbe5f1;border-radius:10px;padding:32px;max-width:460px;margin:16px}'
        . 'h1{margin:0 0 12px;font-size:1.3rem;color:#0b2e59}p{line-height:1.6;margin:0 0 20px}'
        . 'a{display:inline-block;background:#0d3b78;color:#fff;text-decoration:none;padding:10px 18px;border-radius:8px}'
        . '</style></head><body><div class="box"><h1>Terjadi Kesalahan</h1><p>' . e($pesan) . '</p>'
        . '<a href="' . e(BASE_URL) . 'index.php">Kembali ke Dashboard</a></div></body></html>';
    exit;
}

set_exception_handler(function (Throwable $ex): void {
    error_log('[KasirKu] ' . get_class($ex) . ': ' . $ex->getMessage()
        . ' di ' . $ex->getFile() . ':' . $ex->getLine());
    halaman_error('Terjadi kesalahan pada sistem. Silakan coba lagi atau hubungi administrator.');
});

// ---------------------------------------------------------------------
// Output, format, dan tampilan
// ---------------------------------------------------------------------

/** Escape output HTML untuk mencegah XSS. */
function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format rupiah Indonesia, contoh: Rp10.000 */
function rupiah($angka): string
{
    $n = (int) round((float) $angka);
    return ($n < 0 ? '-' : '') . 'Rp' . number_format(abs($n), 0, ',', '.');
}

/** Nomor transaksi tampilan, contoh: TRX-000123 */
function no_transaksi($id): string
{
    return 'TRX-' . str_pad((string) (int) $id, 6, '0', STR_PAD_LEFT);
}

/** Format tanggal dan waktu, contoh: 16/10/2026 14:30 */
function fmt_tanggal(string $datetime, bool $detik = false): string
{
    $ts = strtotime($datetime);
    return $ts === false ? '-' : date($detik ? 'd/m/Y H:i:s' : 'd/m/Y H:i', $ts);
}

/** Tanggal hari ini dalam bahasa Indonesia. */
function tanggal_indonesia(): string
{
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
              'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $hari[(int) date('w')] . ', ' . date('j') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
}

/** Ikon SVG sederhana (tanpa library eksternal). */
function ikon(string $nama, int $ukuran = 20): string
{
    static $path = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
        'box'       => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'cart'      => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a1 1 0 0 0 1 .8h8.8a1 1 0 0 0 1-.8L20 7H6"/>',
        'history'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'edit'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash'     => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'print'     => '<path d="M6 9V3h12v6"/><rect x="6" y="14" width="12" height="7"/><path d="M6 17H4a1 1 0 0 1-1-1v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a1 1 0 0 1-1 1h-2"/>',
        'eye'       => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        'menu'      => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'money'     => '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'alert'     => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18h.01"/>',
        'receipt'   => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
        'check'     => '<path d="M5 12l5 5 9-10"/>',
        'x'         => '<path d="M6 6l12 12M18 6L6 18"/>',
        'back'      => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
    ];
    $isi = $path[$nama] ?? '';
    return '<svg class="icon" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $isi . '</svg>';
}

// ---------------------------------------------------------------------
// Notifikasi (flash message), pengalihan, dan keamanan formulir
// ---------------------------------------------------------------------

/** Simpan notifikasi untuk ditampilkan pada halaman berikutnya. $type: success | error */
function flash_set(string $type, $pesan): void
{
    $_SESSION['flash'] = ['type' => $type, 'messages' => array_values((array) $pesan)];
}

/** Ambil dan hapus notifikasi. */
function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $halaman): void
{
    header('Location: ' . BASE_URL . $halaman);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && $t !== '' && hash_equals(csrf_token(), $t);
}

/**
 * Wajib dipanggil di awal setiap action: hanya menerima POST dengan token CSRF yang valid.
 */
function wajib_post(string $halaman_kembali): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        flash_set('error', 'Akses tidak valid. Gunakan tombol yang tersedia pada halaman aplikasi.');
        redirect($halaman_kembali);
    }
    if (!csrf_valid()) {
        flash_set('error', 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        redirect($halaman_kembali);
    }
}

/** Token sekali pakai untuk mencegah transaksi ganda. Dibuat saat halaman kasir dibuka. */
function trx_token_baru(): string
{
    if (empty($_SESSION['trx_token'])) {
        $_SESSION['trx_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['trx_token'];
}

/** Memeriksa token transaksi lalu langsung menghapusnya (tidak bisa dipakai dua kali). */
function trx_token_pakai(): bool
{
    $t  = $_POST['trx_token'] ?? '';
    $ok = isset($_SESSION['trx_token']) && is_string($t) && $t !== ''
        && hash_equals($_SESSION['trx_token'], $t);
    unset($_SESSION['trx_token']);
    return $ok;
}

// ---------------------------------------------------------------------
// Ambil dan validasi input
// ---------------------------------------------------------------------

function post_str(string $kunci): string
{
    return (isset($_POST[$kunci]) && is_string($_POST[$kunci])) ? trim($_POST[$kunci]) : '';
}

function get_str(string $kunci): string
{
    return (isset($_GET[$kunci]) && is_string($_GET[$kunci])) ? trim($_GET[$kunci]) : '';
}

/** Mengubah teks menjadi ID positif, atau null jika tidak valid. */
function ambil_id(string $nilai): ?int
{
    if (!preg_match('/^\d{1,10}$/', $nilai)) {
        return null;
    }
    $id = (int) $nilai;
    return $id > 0 ? $id : null;
}

function tanggal_valid(string $tgl): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $tgl);
    return $d !== false && $d->format('Y-m-d') === $tgl;
}

/**
 * Validasi data produk (dipakai oleh simpan dan update).
 * Mengembalikan ['errors' => [...], 'data' => ['nama' => string, 'harga' => int, 'stok' => int]]
 */
function validasi_produk(string $nama, string $harga, string $stok): array
{
    $err  = [];
    $nama = trim(preg_replace('/\s+/u', ' ', $nama) ?? '');

    if ($nama === '') {
        $err[] = 'Nama produk tidak boleh kosong.';
    } elseif (mb_strlen($nama, 'UTF-8') > 100) {
        $err[] = 'Nama produk maksimal 100 karakter.';
    }

    $harga = trim($harga);
    if ($harga === '') {
        $err[] = 'Harga tidak boleh kosong.';
    } elseif (!is_numeric($harga)) {
        $err[] = 'Harga harus berupa angka.';
    } elseif ((float) $harga <= 0) {
        $err[] = 'Harga harus lebih besar dari 0 (tidak boleh nol atau negatif).';
    } elseif (!preg_match('/^\d+$/', $harga)) {
        $err[] = 'Harga harus berupa bilangan bulat rupiah, tanpa titik atau koma.';
    } elseif ((float) $harga > MAKS_HARGA) {
        $err[] = 'Harga terlalu besar (maksimal ' . rupiah(MAKS_HARGA) . ').';
    }

    $stok = trim($stok);
    if ($stok === '') {
        $err[] = 'Stok tidak boleh kosong.';
    } elseif (!is_numeric($stok)) {
        $err[] = 'Stok harus berupa angka.';
    } elseif ((float) $stok < 0) {
        $err[] = 'Stok tidak boleh negatif.';
    } elseif (!preg_match('/^\d+$/', $stok)) {
        $err[] = 'Stok harus berupa bilangan bulat, tanpa desimal.';
    } elseif ((float) $stok > MAKS_STOK) {
        $err[] = 'Stok terlalu besar (maksimal ' . number_format(MAKS_STOK, 0, ',', '.') . ').';
    }

    return [
        'errors' => $err,
        'data'   => [
            'nama'  => $nama,
            'harga' => $err ? 0 : (int) $harga,
            'stok'  => $err ? 0 : (int) $stok,
        ],
    ];
}

// ---------------------------------------------------------------------
// Akses database (selalu memakai prepared statement)
// ---------------------------------------------------------------------

function db_all(string $sql, array $param = []): array
{
    $s = db()->prepare($sql);
    $s->execute($param);
    return $s->fetchAll();
}

function db_row(string $sql, array $param = []): ?array
{
    $s = db()->prepare($sql);
    $s->execute($param);
    $r = $s->fetch();
    return $r === false ? null : $r;
}

function db_scalar(string $sql, array $param = [])
{
    $s = db()->prepare($sql);
    $s->execute($param);
    return $s->fetchColumn();
}

function db_exec(string $sql, array $param = []): int
{
    $s = db()->prepare($sql);
    $s->execute($param);
    return $s->rowCount();
}


/** Memastikan pengguna sudah login. */
function wajib_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/** Membatasi akses berdasarkan role. */
function wajib_role(array $roles): void
{
    wajib_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        halaman_error('Anda tidak memiliki izin untuk mengakses halaman ini.', 403);
    }
}
