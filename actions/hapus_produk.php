<?php
/**
 * actions/hapus_produk.php
 * Menghapus produk. Hanya menerima POST.
 * Produk yang sudah punya riwayat transaksi TIDAK boleh dihapus agar riwayat tetap utuh.
 */
require_once __DIR__ . '/../includes/helpers.php';
wajib_role(['admin']);

wajib_post('produk.php');

$id = ambil_id(post_str('id_produk'));
if ($id === null) {
    flash_set('error', 'ID produk tidak valid.');
    redirect('produk.php');
}

$produk = db_row('SELECT id_produk, nama_produk FROM produk WHERE id_produk = :id', ['id' => $id]);
if ($produk === null) {
    flash_set('error', 'Produk tidak ditemukan. Mungkin sudah dihapus.');
    redirect('produk.php');
}

$pesan_ditolak = 'Produk "' . $produk['nama_produk'] . '" tidak dapat dihapus karena sudah memiliki riwayat transaksi.';

$jumlah_riwayat = (int) db_scalar(
    'SELECT COUNT(*) FROM detail_transaksi WHERE id_produk = :id',
    ['id' => $id]
);
if ($jumlah_riwayat > 0) {
    flash_set('error', $pesan_ditolak);
    redirect('produk.php');
}

try {
    db_exec('DELETE FROM produk WHERE id_produk = :id', ['id' => $id]);
    flash_set('success', 'Produk "' . $produk['nama_produk'] . '" berhasil dihapus.');
} catch (PDOException $e) {
    // Kode 23000 = pelanggaran foreign key (lapisan pengaman terakhir dari database).
    if ($e->getCode() === '23000') {
        flash_set('error', $pesan_ditolak);
    } else {
        error_log('[KasirKu] hapus_produk: ' . $e->getMessage());
        flash_set('error', 'Produk gagal dihapus karena kesalahan sistem. Silakan coba lagi.');
    }
}
redirect('produk.php');
