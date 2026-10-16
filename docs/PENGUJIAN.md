# Dokumen Pengujian KasirKu

Mata kuliah: Pengujian dan Validasi

> Kolom **Hasil Aktual** dan **Status** sengaja **dikosongkan**. Hasil aktual harus diisi dari pengujian nyata
> pada komputer Anda, bukan dikarang. Cara memperoleh hasil aktual ada di bagian 3.

## 1. Persiapan pengujian
1. Start Apache dan MySQL, lalu impor `database/KasirKu.sql` (ini mereset data ke kondisi awal).
2. Buka `http://localhost/KasirKu/`.
3. Siapkan phpMyAdmin (`http://localhost/phpmyadmin`, database `KasirKu`) untuk memeriksa isi tabel.
4. Siapkan DevTools browser (tekan F12) untuk uji yang melewati JavaScript.

**Data awal produk** (hasil impor):

| Produk | Harga | Stok |
|---|---|---|
| Beras Premium 5 kg | Rp68.000 | 25 |
| Minyak Goreng 1 L | Rp18.500 | 40 |
| Gula Pasir 1 kg | Rp16.000 | 30 |
| Telur Ayam 1 kg | Rp29.000 | 20 |
| Mie Instan Goreng | Rp3.500 | 100 |
| Kopi Sachet | Rp2.000 | 150 |
| Teh Celup 25 pcs | Rp7.500 | 4 |
| Susu UHT 250 ml | Rp6.500 | 3 |
| Sabun Mandi Batang | Rp4.500 | 60 |
| Air Mineral 600 ml | Rp3.000 | 0 |

**Belanja contoh (dipakai berulang):** Beras x2 + Gula x1 + Mie x3
= 136.000 + 16.000 + 10.500 = **Rp162.500**. Bayar Rp200.000, kembalian **Rp37.500**.
Stok setelah transaksi sukses: Beras 23, Gula 29, Mie 97.

## 2. Tabel test case

### A. Manajemen produk

| ID | Fitur | Tujuan | Langkah pengujian | Data input | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| TC-01 | Tambah produk | Produk valid tersimpan (positive) | Menu Produk > Tambah Produk > isi > Simpan | Nama: Kopi Bubuk 200 g; Harga: 12000; Stok: 15 | Notifikasi sukses; produk muncul di daftar dengan Rp12.000 dan stok 15; 1 baris baru di tabel `produk` | | |
| TC-02 | Tambah produk | Nama kosong ditolak (negative) | Buka form tambah, kosongkan nama, Simpan | Nama: (kosong); Harga: 5000; Stok: 5 | Muncul pesan "Nama produk tidak boleh kosong."; data tidak tersimpan | | |
| TC-03 | Tambah produk | Nama hanya spasi ditolak (negative) | Isi nama dengan beberapa spasi, Simpan | Nama: "   "; Harga: 5000; Stok: 5 | Pesan "Nama produk tidak boleh kosong."; data tidak tersimpan | | |
| TC-04 | Tambah produk | Harga nol ditolak (negative) | Isi harga 0, Simpan | Nama: Uji Harga; Harga: 0; Stok: 5 | Pesan harga harus lebih besar dari 0; data tidak tersimpan | | |
| TC-05 | Tambah produk | Harga negatif ditolak (negative) | Isi harga negatif, Simpan | Harga: -5000 | Pesan harga tidak boleh nol atau negatif; data tidak tersimpan | | |
| TC-06 | Tambah produk | Harga bukan angka / desimal ditolak (negative) | Coba harga huruf lalu desimal | Harga: abc, kemudian 1500.5 | "Harga harus berupa angka." untuk abc; "Harga harus berupa bilangan bulat rupiah..." untuk 1500.5; tidak tersimpan | | |
| TC-07 | Tambah produk | Stok negatif ditolak (negative) | Isi stok -1, Simpan | Nama: Uji Stok; Harga: 5000; Stok: -1 | Pesan "Stok tidak boleh negatif."; tidak tersimpan | | |
| TC-08 | Tambah produk | Stok desimal / huruf ditolak (negative) | Coba stok 2.5 lalu huruf | Stok: 2.5, kemudian dua | "Stok harus berupa bilangan bulat..." untuk 2.5; "Stok harus berupa angka." untuk huruf; tidak tersimpan | | |
| TC-09 | Edit produk | Perubahan tersimpan (positive) | Klik Edit pada Gula Pasir 1 kg, ubah harga dan stok, Simpan Perubahan | Harga: 17000; Stok: 35 | Notifikasi sukses; daftar menampilkan Rp17.000 dan stok 35; tabel `produk` ikut berubah | | |
| TC-10 | Hapus produk | Produk tanpa riwayat terhapus (positive) | Tambah produk baru "Uji Hapus" lalu klik Hapus > OK pada konfirmasi | Produk: Uji Hapus | Muncul dialog konfirmasi; setelah OK produk hilang dari daftar dan dari tabel | | |
| TC-11 | Hapus produk | Produk berriwayat tidak boleh dihapus (negative, integration) | Lakukan transaksi dengan Beras (TC-13), lalu hapus Beras Premium 5 kg | Produk: Beras Premium 5 kg | Pesan "tidak dapat dihapus karena sudah memiliki riwayat transaksi"; produk dan riwayat tetap ada | | |
| TC-12 | Cari produk | Pencarian berdasarkan nama (black-box) | Ketik kata kunci di kotak cari, klik Cari | "gula", lalu "zzz" | "gula" hanya menampilkan Gula Pasir 1 kg; "zzz" menampilkan pesan produk tidak ditemukan | | |

### B. Transaksi kasir

| ID | Fitur | Tujuan | Langkah pengujian | Data input | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| TC-13 | Transaksi | Transaksi valid berhasil (positive, integration) | Menu Transaksi Kasir > tambahkan produk belanja contoh > isi bayar > Selesaikan Transaksi | Belanja contoh; Bayar: 200000 | Notifikasi sukses; diarahkan ke halaman struk dengan nomor transaksi | | |
| TC-14 | Perhitungan | Total belanja benar (black-box) | Tambahkan Beras x2, Gula x1, Mie x3 | Belanja contoh | Subtotal 136.000 / 16.000 / 10.500; total tampil **Rp162.500** | | |
| TC-15 | Perhitungan | Kembalian benar (black-box) | Dengan keranjang TC-14, isi bayar 200000; coba juga tombol Uang pas | Bayar: 200000 | Kembalian **Rp37.500**; Uang pas menghasilkan kembalian Rp0 | | |
| TC-16 | Transaksi | Keranjang kosong ditolak (negative) | Biarkan keranjang kosong, isi bayar, klik Selesaikan Transaksi | Keranjang: kosong; Bayar: 10000 | Muncul pesan keranjang masih kosong; tidak ada transaksi baru | | |
| TC-17 | Validasi jumlah | Jumlah melebihi stok ditolak di sisi klien (negative) | Tambahkan Susu UHT (stok 3), ubah jumlah menjadi 4 | Jumlah: 4 | Pesan jumlah melebihi stok; jumlah otomatis menjadi 3 | | |
| TC-18 | Validasi jumlah | Jumlah melebihi stok ditolak di backend (negative, integration) | Tambahkan Susu UHT x1, lewat DevTools ubah nilai input `jumlah[]` menjadi 99, lalu Selesaikan Transaksi | Jumlah: 99 | Pesan "Stok ... tidak mencukupi. Diminta 99, tersedia 3."; tidak ada transaksi; stok tetap 3 | | |
| TC-19 | Pembayaran | Bayar kurang dari total ditolak (negative) | Keranjang belanja contoh, bayar kurang | Bayar: 100000 | Tampil "Kekurangan Rp62.500"; klik Selesaikan menghasilkan pesan pembayaran kurang; tidak ada transaksi | | |
| TC-20 | Pembayaran | Pembayaran bukan angka ditolak (negative) | Isi bayar dengan huruf / desimal | Bayar: abc, kemudian 1000.5 | Pesan nominal harus berupa angka bulat; tidak ada transaksi | | |
| TC-21 | Keamanan | Total dari browser tidak dipercaya (negative, integration) | Di DevTools tambahkan `<input name="total" value="1">` ke form, lalu kirim transaksi valid | Total palsu: 1 | Transaksi tersimpan dengan total **hasil hitung server** (Rp162.500), bukan 1 | | |
| TC-22 | Penyimpanan | Transaksi tersimpan lengkap (integration) | Setelah TC-13, buka phpMyAdmin dan jalankan query di bagian 3 | Query 1 dan 2 | 1 baris di `transaksi` (total 162500, bayar 200000, kembalian 37500) dan 3 baris di `detail_transaksi` dengan nama dan harga satuan benar | | |
| TC-23 | Stok | Stok berkurang setelah transaksi sukses (integration) | Setelah TC-13, buka halaman Produk | Belanja contoh | Beras 23, Gula 29, Mie 97 | | |
| TC-24 | Stok | Stok tidak berkurang saat transaksi gagal (integration) | Catat stok, lakukan TC-18 atau TC-19, cek stok lagi | TC-18 / TC-19 | Stok semua produk sama persis dengan sebelumnya; tidak ada baris baru di `transaksi` maupun `detail_transaksi` | | |
| TC-25 | Transaksi ganda | Klik berkali-kali hanya menyimpan satu transaksi (negative) | Isi transaksi valid, klik Selesaikan Transaksi dua atau tiga kali dengan cepat | Belanja contoh | Hanya 1 transaksi baru; tombol berubah menjadi "Memproses..." | | |

### C. Struk dan riwayat

| ID | Fitur | Tujuan | Langkah pengujian | Data input | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| TC-26 | Struk | Struk sesuai data database (integration) | Setelah TC-13, bandingkan struk dengan hasil query di bagian 3 | Query 1 dan 2 | Nomor, tanggal, barang, jumlah, harga satuan, subtotal, total, bayar, kembalian sama persis dengan database; tombol Cetak Struk membuka dialog print | | |
| TC-27 | Riwayat | Riwayat sesuai transaksi yang dilakukan (integration) | Buka menu Riwayat Transaksi | Transaksi TC-13 | Ada baris TRX-... dengan total Rp162.500, bayar Rp200.000, kembalian Rp37.500 dan tanggal hari ini | | |
| TC-28 | Detail transaksi | Detail menampilkan barang yang dibeli (black-box) | Klik Detail pada transaksi TC-13 | Transaksi TC-13 | Tabel berisi Beras x2, Gula x1, Mie x3 dengan subtotal benar dan total Rp162.500 | | |
| TC-29 | Riwayat | Pencarian dan filter tanggal (black-box) | Cari "beras"; cari nomor transaksi; filter dengan tanggal hari ini; filter tanggal terbalik | "beras"; "1"; hari ini; Dari > Sampai | Hasil sesuai kriteria; tanggal terbalik menampilkan pesan kesalahan | | |
| TC-30 | Riwayat | Riwayat tidak berubah saat harga produk diubah (integration) | Setelah TC-13, ubah harga Beras menjadi 70000 di Produk, lalu buka detail transaksi TC-13 | Harga baru: 70000 | Detail dan struk lama tetap memakai Rp68.000 | | |
| TC-31 | Dashboard | Statistik sesuai database (integration) | Bandingkan kartu dashboard dengan query di bagian 3 | Query 3 | Total produk, total transaksi, total pendapatan, dan jumlah stok menipis sama dengan hasil query | | |

### D. Keamanan dan penanganan error

| ID | Fitur | Tujuan | Langkah pengujian | Data input | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| TC-32 | Keamanan | Akses langsung ke action lewat GET ditolak (negative) | Buka `http://localhost/KasirKu/actions/hapus_produk.php?id_produk=1` di address bar | URL di samping | Diarahkan ke halaman Produk dengan pesan akses tidak valid; produk tidak terhapus | | |
| TC-33 | Keamanan | Output di-escape (XSS) (negative) | Tambah produk dengan nama berisi tag script | Nama: `<script>alert(1)</script>`; Harga: 1000; Stok: 1 | Nama tampil sebagai teks apa adanya; tidak ada pop-up alert | | |
| TC-34 | Keamanan | File konfigurasi tidak bisa dibuka (negative) | Buka `http://localhost/KasirKu/config/database.php` | URL di samping | Muncul 403 Forbidden | | |
| TC-35 | Error | Database mati ditangani dengan ramah (negative) | Stop MySQL di XAMPP, buka halaman mana pun | MySQL: stopped | Muncul pesan ramah "Tidak dapat terhubung ke database..." tanpa detail teknis (password, host, stack trace) | | |

## 3. Cara menjalankan pengujian dan memperoleh hasil aktual

Untuk setiap test case: (1) reset data bila perlu dengan impor ulang SQL, (2) jalankan langkah, (3) amati layar,
(4) cocokkan dengan database, (5) tulis yang **benar-benar terjadi** pada kolom Hasil Aktual, (6) isi Status:
**Pass** jika sama dengan hasil yang diharapkan, **Fail** jika berbeda. Tambahkan tangkapan layar sebagai bukti.

**Query pemeriksaan (jalankan di tab SQL phpMyAdmin, database KasirKu):**

```sql
-- Query 1: transaksi terakhir
SELECT * FROM transaksi ORDER BY id_transaksi DESC LIMIT 5;

-- Query 2: detail transaksi terakhir
SELECT * FROM detail_transaksi
WHERE id_transaksi = (SELECT MAX(id_transaksi) FROM transaksi);

-- Query 3: angka yang harus sama dengan dashboard
SELECT (SELECT COUNT(*) FROM produk)                AS total_produk,
       (SELECT COUNT(*) FROM transaksi)             AS total_transaksi,
       (SELECT COALESCE(SUM(total),0) FROM transaksi) AS total_pendapatan,
       (SELECT COUNT(*) FROM produk WHERE stok <= 5) AS stok_menipis;

-- Query 4: cek stok
SELECT id_produk, nama_produk, stok FROM produk ORDER BY id_produk;

-- Query 5: konsistensi (harus kosong): subtotal tidak sama dengan harga x jumlah
SELECT * FROM detail_transaksi WHERE subtotal <> harga_satuan * jumlah;

-- Query 6: konsistensi (harus kosong): total transaksi tidak sama dengan jumlah subtotal
SELECT t.id_transaksi, t.total, SUM(d.subtotal) AS jumlah_detail
FROM transaksi t JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
GROUP BY t.id_transaksi, t.total HAVING t.total <> SUM(d.subtotal);
```

**Cara melewati JavaScript (untuk negative testing backend):** tekan F12 > tab Elements > klik kanan elemen
`<input name="jumlah[]">` > *Edit attribute* / ubah `value`, atau *Edit as HTML* untuk menambah input.
Setelah itu klik tombol kirim seperti biasa.

## 4. Pemetaan jenis pengujian

| Jenis pengujian | Test case |
|---|---|
| Black-box (input dan output) | TC-12, TC-14, TC-15, TC-28, TC-29 |
| Positive testing (input valid) | TC-01, TC-09, TC-10, TC-13 |
| Negative testing (input tidak valid) | TC-02 s.d. TC-08, TC-11, TC-16 s.d. TC-21, TC-24, TC-25, TC-32 s.d. TC-35 |
| Integration testing (halaman PHP dan database) | TC-11, TC-13, TC-18, TC-21 s.d. TC-24, TC-26, TC-27, TC-30, TC-31 |
| Retesting | Bagian 6 |

## 5. Daftar kemungkinan bug dan cara memeriksanya

| No | Kemungkinan bug | Cara memeriksa |
|---|---|---|
| 1 | Stok berkurang padahal transaksi gagal | TC-18, TC-19, lalu Query 4 |
| 2 | Transaksi tersimpan dobel karena klik berulang | TC-25, lalu Query 1 |
| 3 | Total dari JavaScript dipercaya server | TC-21 |
| 4 | Stok bisa menjadi negatif jika dua kasir membeli bersamaan | Buka dua tab, isi Susu UHT x3 di kedua tab, kirim bergantian; tab kedua harus ditolak. Cek Query 4 (stok tidak boleh < 0) |
| 5 | Riwayat berubah setelah harga produk diedit | TC-30 |
| 6 | Produk berriwayat terhapus sehingga riwayat rusak | TC-11 |
| 7 | Nama produk berisi HTML tereksekusi (XSS) | TC-33 |
| 8 | Pencarian dengan karakter `%` atau `_` menampilkan semua data | Cari `%` di Produk; harus tampil "tidak ditemukan" kecuali ada nama berisi `%` |
| 9 | Pembulatan uang tidak tepat | Query 5 dan Query 6 harus kosong |
| 10 | Tombol tetap terkunci setelah kembali dengan tombol Back browser | Setelah transaksi gagal, tekan Back; tombol harus aktif lagi |
| 11 | Keranjang hilang saat halaman dimuat ulang | Isi keranjang, tekan F5; isi keranjang harus tetap ada. Setelah transaksi sukses, keranjang harus kosong |
| 12 | Tampilan terpotong atau bertumpuk di ponsel | Buka DevTools > mode perangkat (Ctrl+Shift+M), cek lebar 375 px pada semua halaman |
| 13 | Tautan sidebar salah arah | Klik semua menu dan tombol navigasi |
| 14 | Format rupiah tidak konsisten | Periksa semua halaman: harus berbentuk Rp10.000 |
| 15 | Filter tanggal melewatkan transaksi di hari terakhir | Filter dengan Dari = Sampai = hari ini; transaksi hari ini harus muncul |

## 6. Retesting (setelah bug diperbaiki)

Isi tabel ini hanya jika ada test case berstatus **Fail**. Catat bug, perbaikan, lalu jalankan ulang test case yang sama
dan test case terkait (regression).

| ID Retest | Test case asal | Deskripsi bug | Perbaikan yang dilakukan | Tanggal retest | Hasil aktual setelah perbaikan | Status |
|---|---|---|---|---|---|---|
| RT-01 | | | | | | |
| RT-02 | | | | | | |
