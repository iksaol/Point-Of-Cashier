<?php
/**
 * detail_transaksi.php - Rincian barang pada satu transaksi.
 * Data diambil dari tabel transaksi dan detail_transaksi.
 */
require_once __DIR__ . '/includes/helpers.php';
wajib_login();

$id = ambil_id(get_str('id'));
$transaksi = $id === null ? null : db_row(
    'SELECT id_transaksi, tanggal_transaksi, total, bayar, kembalian FROM transaksi WHERE id_transaksi = :id',
    ['id' => $id]
);
if ($transaksi === null) {
    flash_set('error', 'Transaksi tidak ditemukan.');
    redirect('riwayat.php');
}

$detail = db_all(
    'SELECT nama_produk, harga_satuan, jumlah, subtotal FROM detail_transaksi
     WHERE id_transaksi = :id ORDER BY id_detail ASC',
    ['id' => $id]
);
$jumlah_item = 0;
foreach ($detail as $d) {
    $jumlah_item += (int) $d['jumlah'];
}

$judul_halaman = 'Detail ' . no_transaksi($id);
$menu_aktif    = 'riwayat';
include __DIR__ . '/includes/header.php';
?>

<div class="toolbar no-print">
    <a class="btn btn-outline" href="<?= e(BASE_URL) ?>riwayat.php"><?= ikon('back', 18) ?> Kembali ke Riwayat</a>
    <a class="btn btn-primary" href="<?= e(BASE_URL) ?>struk.php?id=<?= (int) $id ?>"><?= ikon('receipt', 18) ?> Lihat dan Cetak Struk</a>
</div>

<section class="info-grid">
    <div class="info-item">
        <span class="info-label">No. Transaksi</span>
        <strong><?= e(no_transaksi($id)) ?></strong>
    </div>
    <div class="info-item">
        <span class="info-label">Tanggal &amp; Waktu</span>
        <strong><?= e(fmt_tanggal($transaksi['tanggal_transaksi'], true)) ?></strong>
    </div>
    <div class="info-item">
        <span class="info-label">Total Belanja</span>
        <strong><?= e(rupiah($transaksi['total'])) ?></strong>
    </div>
    <div class="info-item">
        <span class="info-label">Pembayaran</span>
        <strong><?= e(rupiah($transaksi['bayar'])) ?></strong>
    </div>
    <div class="info-item">
        <span class="info-label">Kembalian</span>
        <strong><?= e(rupiah($transaksi['kembalian'])) ?></strong>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2>Barang yang Dibeli</h2>
        <span class="muted"><?= count($detail) ?> jenis &bull; <?= $jumlah_item ?> barang</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th class="num">Harga Satuan</th>
                    <th class="center">Jumlah</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detail as $d): ?>
                <tr>
                    <td class="wrap"><?= e($d['nama_produk']) ?></td>
                    <td class="num"><?= e(rupiah($d['harga_satuan'])) ?></td>
                    <td class="center"><?= (int) $d['jumlah'] ?></td>
                    <td class="num"><?= e(rupiah($d['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="num"><strong>Total</strong></td>
                    <td class="num"><strong><?= e(rupiah($transaksi['total'])) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
