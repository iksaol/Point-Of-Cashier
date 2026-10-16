<?php
/**
 * struk.php - Struk transaksi (dapat dicetak lewat fitur print browser).
 * Seluruh isi struk diambil dari data yang tersimpan di database.
 */
require_once __DIR__ . '/includes/helpers.php';
wajib_login();

$id = ambil_id(get_str('id'));
$transaksi = $id === null ? null : db_row(
    'SELECT id_transaksi, tanggal_transaksi, total, bayar, kembalian FROM transaksi WHERE id_transaksi = :id',
    ['id' => $id]
);
if ($transaksi === null) {
    flash_set('error', 'Struk tidak ditemukan karena transaksi tidak ada.');
    redirect('riwayat.php');
}

$detail = db_all(
    'SELECT nama_produk, harga_satuan, jumlah, subtotal FROM detail_transaksi
     WHERE id_transaksi = :id ORDER BY id_detail ASC',
    ['id' => $id]
);
$baru = isset($_GET['baru']);

$judul_halaman = 'Struk ' . no_transaksi($id);
$menu_aktif    = 'riwayat';
include __DIR__ . '/includes/header.php';
?>

<?php if ($baru): ?>
    <!-- Transaksi baru berhasil: kosongkan keranjang yang tersimpan di browser. -->
    <script>try { sessionStorage.removeItem('KasirKu_cart'); } catch (e) {}</script>
<?php endif; ?>

<div class="struk-wrap">
    <article class="struk" id="struk">
        <div class="struk-head">
            <h2>Point Of Cashier</h2>
            <p>Struk Pembayaran</p>
        </div>

        <dl class="struk-meta">
            <div><dt>No. Transaksi</dt><dd><?= e(no_transaksi($id)) ?></dd></div>
            <div><dt>Tanggal</dt><dd><?= e(fmt_tanggal($transaksi['tanggal_transaksi'], true)) ?></dd></div>
        </dl>

        <table class="struk-table">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th class="center">Qty</th>
                    <th class="num">Harga</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detail as $d): ?>
                <tr>
                    <td class="wrap"><?= e($d['nama_produk']) ?></td>
                    <td class="center"><?= (int) $d['jumlah'] ?></td>
                    <td class="num"><?= e(rupiah($d['harga_satuan'])) ?></td>
                    <td class="num"><?= e(rupiah($d['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="struk-total">
            <div><span>Total belanja</span><strong><?= e(rupiah($transaksi['total'])) ?></strong></div>
            <div><span>Pembayaran</span><span><?= e(rupiah($transaksi['bayar'])) ?></span></div>
            <div><span>Kembalian</span><strong><?= e(rupiah($transaksi['kembalian'])) ?></strong></div>
        </div>

        <p class="struk-foot">Terima kasih atas kunjungan Anda</p>
    </article>

    <div class="struk-actions no-print">
        <button type="button" class="btn btn-primary" data-print><?= ikon('print', 18) ?> Cetak Struk</button>
        <a class="btn btn-light" href="<?= e(BASE_URL) ?>transaksi.php"><?= ikon('cart', 18) ?> Transaksi Baru</a>
        <a class="btn btn-outline" href="<?= e(BASE_URL) ?>detail_transaksi.php?id=<?= (int) $id ?>">Lihat Detail</a>
        <a class="btn btn-outline" href="<?= e(BASE_URL) ?>riwayat.php">Riwayat</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
