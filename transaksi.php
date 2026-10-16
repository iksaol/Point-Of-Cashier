<?php
/**
 * transaksi.php - Halaman kasir
 * Keranjang berjalan di JavaScript (assets/js/script.js). Saat disimpan, yang dikirim ke
 * actions/proses_transaksi.php hanya id_produk, jumlah, dan bayar. Harga dan total dihitung ulang di PHP.
 */
require_once __DIR__ . '/includes/helpers.php';
wajib_login();

$produk = db_all('SELECT id_produk, nama_produk, harga, stok FROM produk ORDER BY nama_produk ASC');

// Data untuk JavaScript (di-escape agar aman di dalam tag script).
$data_js = [];
foreach ($produk as $p) {
    $data_js[] = [
        'id'    => (int) $p['id_produk'],
        'nama'  => $p['nama_produk'],
        'harga' => (int) round((float) $p['harga']),
        'stok'  => (int) $p['stok'],
    ];
}
$json_produk = json_encode($data_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

$judul_halaman = 'Transaksi Kasir';
$menu_aktif    = 'transaksi';
include __DIR__ . '/includes/header.php';
?>

<div class="kasir-grid">
    <section class="card">
        <div class="card-header">
            <h2>Daftar Produk</h2>
            <span class="muted"><?= count($produk) ?> produk</span>
        </div>
        <div class="search-box search-box-block">
            <?= ikon('search', 18) ?>
            <label class="sr-only" for="cariProduk">Cari produk</label>
            <input type="search" id="cariProduk" placeholder="Cari nama produk..." autocomplete="off">
        </div>

        <?php if (!$produk): ?>
            <p class="empty">Belum ada produk. Tambahkan produk terlebih dahulu di halaman Produk.</p>
        <?php else: ?>
            <div class="table-wrap table-scroll">
                <table class="table" id="tabelProduk">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Harga</th>
                            <th class="center">Stok</th>
                            <th class="center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($produk as $p): ?>
                        <?php $stok = (int) $p['stok']; ?>
                        <tr data-nama="<?= e($p['nama_produk']) ?>">
                            <td class="wrap"><?= e($p['nama_produk']) ?></td>
                            <td class="num"><?= e(rupiah($p['harga'])) ?></td>
                            <td class="center">
                                <?php if ($stok === 0): ?>
                                    <span class="badge badge-danger">Habis</span>
                                <?php elseif ($stok <= STOK_MENIPIS): ?>
                                    <span class="badge badge-warn"><?= $stok ?></span>
                                <?php else: ?>
                                    <span class="badge badge-ok"><?= $stok ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <button type="button" class="btn btn-light btn-sm"
                                        data-tambah="<?= (int) $p['id_produk'] ?>"
                                        <?= $stok === 0 ? 'disabled' : '' ?>><?= ikon('plus', 16) ?> Tambah</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                        <tr id="produkKosong" hidden>
                            <td colspan="4" class="empty">Produk tidak ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card cart-card">
        <div class="card-header">
            <h2>Keranjang</h2>
            <span class="badge badge-info" id="cartCount">0 item</span>
        </div>

        <form id="formTransaksi" method="post" action="<?= e(BASE_URL) ?>actions/proses_transaksi.php" novalidate data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="trx_token" value="<?= e(trx_token_baru()) ?>">

            <div class="table-wrap cart-wrap">
                <table class="table cart-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Harga</th>
                            <th class="center">Jumlah</th>
                            <th class="num">Subtotal</th>
                            <th><span class="sr-only">Hapus</span></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody"></tbody>
                </table>
            </div>

            <div class="summary">
                <div class="summary-row">
                    <span>Total belanja</span>
                    <strong id="totalText">Rp0</strong>
                </div>
                <label for="bayar">Uang pembayaran (Rp)</label>
                <div class="input-group">
                    <input type="text" id="bayar" name="bayar" inputmode="numeric" autocomplete="off" placeholder="Contoh: 50000">
                    <button type="button" class="btn btn-light" id="uangPas">Uang pas</button>
                </div>
                <div class="summary-row">
                    <span id="kembalianLabel">Kembalian</span>
                    <strong id="kembalianText">Rp0</strong>
                </div>
                <p class="bayar-info" id="bayarInfo" aria-live="polite"></p>
            </div>

            <div class="cart-actions">
                <button type="button" class="btn btn-outline" id="kosongkan">Kosongkan Keranjang</button>
                <button type="submit" class="btn btn-primary" id="btnBayar">Selesaikan Transaksi</button>
            </div>
        </form>
    </section>
</div>

<script type="application/json" id="dataProduk"><?= $json_produk ?></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
