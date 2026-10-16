# Point Of Cashier - Sistem Kasir Sederhana

Aplikasi kasir berbasis **PHP native, MySQL, HTML, CSS, dan JavaScript** (tanpa framework) yang berjalan lokal di **XAMPP**.
Dibuat untuk tugas mata kuliah **Pengujian dan Validasi**.

## Fitur
- **Login dan role pengguna**: admin dan kasir dengan pembatasan akses.
- **Dashboard**: total produk, total transaksi, total pendapatan, stok menipis, transaksi terbaru (semua dari database).
- **Produk**: tambah, ubah, hapus dengan konfirmasi, cari berdasarkan nama, validasi input.
- **Transaksi kasir**: keranjang, ubah jumlah, total dan kembalian otomatis, validasi stok dan pembayaran.
- **Struk**: dari data tersimpan di database, bisa dicetak lewat print browser.
- **Riwayat**: pencarian (no. transaksi atau nama barang), filter tanggal, detail transaksi.

## Persyaratan
- Windows dengan **XAMPP** (PHP 8.0 atau lebih baru; aktifkan Apache dan MySQL)
- **Visual Studio Code** (opsional, untuk membaca kode)
- Browser modern (Chrome, Edge, Firefox)

## Instalasi (Windows + XAMPP + VS Code)
1. Ekstrak ZIP, sehingga ada folder `C:\xampp\htdocs\Point Of Cashier\` yang langsung berisi `index.php`, `produk.php`, dan seterusnya.
   (Pastikan tidak menjadi `Point Of Cashier\Point Of Cashier\`.)
2. Buka **XAMPP Control Panel**, klik **Start** pada **Apache** dan **MySQL** sampai keduanya hijau.
3. Buka `http://localhost/phpmyadmin`, klik tab **Impor**, pilih file `database\Point Of Cashier.sql`, lalu klik **Import**.
4. Buka **`http://localhost/Point Of Cashier/`** di browser; Anda akan diarahkan ke halaman login.

## Akun demo
- **Admin** — username `admin`, password `admin123` (akses dashboard, produk, transaksi, dan riwayat).
- **Kasir** — username `kasir`, password `kasir123` (akses dashboard, transaksi, dan riwayat; tidak dapat mengelola produk).

Ganti password demo sebelum aplikasi digunakan di lingkungan nyata.
5. (Opsional) Buka folder di VS Code: *File > Open Folder > C:\xampp\htdocs\Point Of Cashier*.

> Mengimpor ulang `Point Of Cashier.sql` akan **mereset** seluruh data (berguna untuk mengulang pengujian).

## Pengaturan database
Ada di satu file: `config/database.php` (host `127.0.0.1`, user `root`, password kosong, database `Point Of Cashier`).
Ubah `DB_PASS` jika MySQL Anda memakai password.

## Struktur folder dan fungsi file
| File | Fungsi |
|---|---|
| `index.php` | Dashboard |
| `produk.php` | Daftar, pencarian, dan formulir tambah/edit produk |
| `transaksi.php` | Halaman kasir (daftar produk, keranjang, pembayaran) |
| `struk.php` | Struk dari data database, tombol cetak |
| `riwayat.php` | Riwayat transaksi, pencarian, filter tanggal |
| `detail_transaksi.php` | Rincian barang pada satu transaksi |
| `actions/simpan_produk.php` | Proses tambah produk (POST) |
| `actions/update_produk.php` | Proses ubah produk (POST) |
| `actions/hapus_produk.php` | Proses hapus produk, ditolak jika punya riwayat (POST) |
| `actions/proses_transaksi.php` | Verifikasi ulang keranjang di server, simpan transaksi, kurangi stok (BEGIN/COMMIT/ROLLBACK) |
| `config/database.php` | Koneksi PDO (satu-satunya tempat) |
| `config/.htaccess`, `includes/.htaccess` | Menolak akses langsung dari browser |
| `includes/helpers.php` | Fungsi bersama: escaping, format rupiah, notifikasi, token, validasi produk, akses database |
| `includes/header.php`, `sidebar.php`, `footer.php` | Kerangka tampilan bersama |
| `assets/css/style.css` | Seluruh tampilan, responsif, gaya cetak struk |
| `assets/js/script.js` | Keranjang, validasi sisi klien, modal produk, anti klik ganda |
| `assets/images/POC.jpg` | Logo |
| `database/Point Of Cashier.sql` | Skema database dan data awal |
| `docs/PENGUJIAN.md` | Dokumen pengujian (test case, cara uji, daftar bug) |

## Aturan validasi
| Data | Aturan |
|---|---|
| Nama produk | Wajib diisi, maksimal 100 karakter |
| Harga | Bilangan bulat rupiah > 0 (maksimal Rp999.999.999), tanpa titik/koma |
| Stok | Bilangan bulat >= 0 (maksimal 1.000.000) |
| Jumlah beli | Bilangan bulat > 0 dan tidak melebihi stok |
| Pembayaran | Bilangan bulat dan tidak kurang dari total belanja |

Validasi dilakukan **dua kali**: di JavaScript (cepat) dan di PHP (penentu akhir). Database juga memiliki constraint `CHECK`.

## Keamanan dasar
Prepared statement PDO, escaping output (`htmlspecialchars`), operasi ubah data hanya lewat POST, token CSRF pada formulir,
token sekali pakai untuk mencegah transaksi ganda, harga dan total dihitung ulang di server, error database hanya masuk log
(tidak ditampilkan ke pengguna). Aplikasi menggunakan session login, password hash `password_hash`, verifikasi `password_verify`, regenerasi session saat login, dan pembatasan role di sisi server. Akun demo wajib diganti sebelum digunakan secara nyata.

## Mengatasi masalah umum
| Masalah | Penyebab dan solusi |
|---|---|
| Pesan "Tidak dapat terhubung ke database" | MySQL belum Start di XAMPP, database belum diimpor, atau password salah di `config/database.php`. |
| `404 Not Found` / halaman PHP tidak ditemukan | Folder harus tepat `C:\xampp\htdocs\Point Of Cashier\` (huruf besar-kecil sesuai), dan Apache harus Start. Pastikan bukan `Point Of Cashier\Point Of Cashier\`. |
| Halaman tampil tanpa gaya (polos) | Folder `assets` tidak lengkap atau salah letak. Tekan Ctrl+F5. |
| Apache tidak mau Start (port 80) | Port dipakai program lain (Skype, IIS). Tutup program itu, atau ubah port di XAMPP: *Config > httpd.conf* (`Listen 8080`), lalu buka `http://localhost:8080/Point Of Cashier/`. |
| MySQL tidak mau Start (port 3306) | Ada MySQL lain yang berjalan. Hentikan layanan itu, atau ubah port di `my.ini`. |
| Muncul "Terjadi kesalahan pada sistem" | Lihat detailnya di log: `C:\xampp\apache\logs\error.log` atau `C:\xampp\php\logs\php_error_log`. |
| Impor SQL gagal pada `CHECK` | Versi MariaDB/MySQL terlalu lama. Perbarui XAMPP. |
| Pesan "Sesi formulir tidak valid" | Halaman terlalu lama terbuka atau cookie diblokir. Muat ulang halaman. |
