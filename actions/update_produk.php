<?php
/**
 * actions/update_produk.php
 * Mengubah data produk. Hanya menerima POST.
 * Riwayat transaksi lama tidak berubah karena detail_transaksi menyimpan salinan nama dan harga.
 */
require_once __DIR__ . '/../includes/helpers.php';
wajib_role(['admin']);

wajib_post('produk.php');

$id = ambil_id(post_str('id_produk'));
if ($id === null) {
    flash_set('error', 'ID produk tidak valid.');
    redirect('produk.php');
}

if (db_row('SELECT id_produk FROM produk WHERE id_produk = :id', ['id' => $id]) === null) {
    flash_set('error', 'Produk tidak ditemukan. Mungkin sudah dihapus.');
    redirect('produk.php');
}

$hasil = validasi_produk(post_str('nama_produk'), post_str('harga'), post_str('stok'));

$input_lama = [
    'mode'  => 'edit',
    'id'    => (string) $id,
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
        'UPDATE produk SET nama_produk = :nama, harga = :harga, stok = :stok WHERE id_produk = :id',
        ['nama' => $d['nama'], 'harga' => $d['harga'], 'stok' => $d['stok'], 'id' => $id]
    );
    flash_set('success', 'Produk "' . $d['nama'] . '" berhasil diperbarui.');
} catch (PDOException $e) {
    error_log('[KasirKu] update_produk: ' . $e->getMessage());
    $_SESSION['old_produk'] = $input_lama;
    flash_set('error', 'Produk gagal diperbarui karena kesalahan sistem. Silakan coba lagi.');
}
redirect('produk.php');
