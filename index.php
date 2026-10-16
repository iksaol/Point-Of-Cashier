<?php
/**
 * index.php - Dashboard
 * Semua angka diambil langsung dari database.
 */
require_once __DIR__ . '/includes/helpers.php';
wajib_login();

$total_produk     = (int) db_scalar('SELECT COUNT(*) FROM produk');
$total_transaksi  = (int) db_scalar('SELECT COUNT(*) FROM transaksi');
$total_pendapatan = (float) db_scalar('SELECT COALESCE(SUM(total), 0) FROM transaksi');
$jumlah_menipis   = (int) db_scalar('SELECT COUNT(*) FROM produk WHERE stok <= :batas', ['batas' => STOK_MENIPIS]);

$stok_menipis = db_all(
    'SELECT id_produk, nama_produk, stok FROM produk WHERE stok <= :batas
     ORDER BY stok ASC, nama_produk ASC LIMIT 10',
    ['batas' => STOK_MENIPIS]
);
$transaksi_terbaru = db_all(
    'SELECT id_transaksi, tanggal_transaksi, total FROM transaksi
     ORDER BY tanggal_transaksi DESC, id_transaksi DESC LIMIT 5'
);

$judul_halaman = 'Dashboard';
$menu_aktif    = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<section class="stats" aria-label="Ringkasan">
    <article class="stat">
        <div class="stat-icon"><?= ikon('box', 24) ?></div>
        <div>
            <div class="stat-label">Total Produk</div>
            <div class="stat-value"><?= e(number_format($total_produk, 0, ',', '.')) ?></div>
        </div>
    </article>
    <article class="stat">
        <div class="stat-icon"><?= ikon('receipt', 24) ?></div>
        <div>
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value"><?= e(number_format($total_transaksi, 0, ',', '.')) ?></div>
        </div>
    </article>
    <article class="stat">
        <div class="stat-icon"><?= ikon('money', 24) ?></div>
        <div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value"><?= e(rupiah($total_pendapatan)) ?></div>
        </div>
    </article>
    <article class="stat stat-warn">
        <div class="stat-icon"><?= ikon('alert', 24) ?></div>
        <div>
            <div class="stat-label">Stok Menipis</div>
            <div class="stat-value"><?= e(number_format($jumlah_menipis, 0, ',', '.')) ?></div>
        </div>
    </article>
</section>

<div class="quick-links no-print">
    <a class="btn btn-primary" href="<?= e(BASE_URL) ?>transaksi.php"><?= ikon('cart', 18) ?> Mulai Transaksi</a>
    <a class="btn btn-light" href="<?= e(BASE_URL) ?>produk.php"><?= ikon('box', 18) ?> Kelola Produk</a>
    <a class="btn btn-light" href="<?= e(BASE_URL) ?>riwayat.php"><?= ikon('history', 18) ?> Riwayat Transaksi</a>
</div>

<div class="grid-2">
    <section class="card">
        <div class="card-header">
            <h2>Transaksi Terbaru</h2>
            <a class="link" href="<?= e(BASE_URL) ?>riwayat.php">Lihat semua</a>
        </div>
        <?php if (!$transaksi_terbaru): ?>
            <p class="empty">Belum ada transaksi. Buka halaman Transaksi Kasir untuk membuat transaksi pertama.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>No. Transaksi</th><th>Tanggal</th><th class="num">Total</th><th class="center">Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transaksi_terbaru as $t): ?>
                        <tr>
                            <td><?= e(no_transaksi($t['id_transaksi'])) ?></td>
                            <td><?= e(fmt_tanggal($t['tanggal_transaksi'])) ?></td>
                            <td class="num"><?= e(rupiah($t['total'])) ?></td>
                            <td class="center">
                                <a class="btn btn-light btn-sm"
                                   href="<?= e(BASE_URL) ?>detail_transaksi.php?id=<?= (int) $t['id_transaksi'] ?>">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Produk dengan Stok Menipis</h2>
            <span class="muted">Stok &le; <?= (int) STOK_MENIPIS ?></span>
        </div>
        <?php if (!$stok_menipis): ?>
            <p class="empty">Semua produk memiliki stok yang cukup.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Produk</th><th class="center">Stok</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stok_menipis as $p): ?>
                        <tr>
                            <td class="wrap"><?= e($p['nama_produk']) ?></td>
                            <td class="center">
                                <?php if ((int) $p['stok'] === 0): ?>
                                    <span class="badge badge-danger">Habis</span>
                                <?php else: ?>
                                    <span class="badge badge-warn"><?= (int) $p['stok'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
