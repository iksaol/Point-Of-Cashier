<?php
/**
 * actions/simpan_produk.php
 * Menambah produk baru. Hanya menerima POST.
 */
require_once __DIR__ . '/../includes/helpers.php';
wajib_role(['admin']);

wajib_post('produk.php');

$hasil = validasi_produk(post_str('nama_produk'), post_str('harga'), post_str('stok'));

// Simpan input lama supaya formulir terisi kembali saat ada kesalahan.
$input_lama = [
    'mode'  => 'tambah',
    'id'    => '',
    'nama'  => post_str('nama_produk'),
    'harga' => post_str('harga'),
    'stok'  => post_str('stok'),
];

if ($hasil['errors']) {
    $_SESSION['old_produk'] = $input_lama;
    flash_set('error', $hasil['errors']);
    redirect('produk.php');
}

$d = $hasil['data'];
try {
    db_exec(
        'INSERT INTO produk (nama_produk, harga, stok) VALUES (:nama, :harga, :stok)',
        ['nama' => $d['nama'], 'harga' => $d['harga'], 'stok' => $d['stok']]
    );
    flash_set('success', 'Produk "' . $d['nama'] . '" berhasil ditambahkan.');
} catch (PDOException $e) {
    error_log('[KasirKu] simpan_produk: ' . $e->getMessage());
    $_SESSION['old_produk'] = $input_lama;
    flash_set('error', 'Produk gagal disimpan karena kesalahan sistem. Silakan coba lagi.');
}
redirect('produk.php');
