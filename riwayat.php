<?php
/**
 * riwayat.php - Riwayat transaksi dengan pencarian dan filter tanggal.
 * Pencarian cocok dengan nomor transaksi (TRX-000001 atau 1) atau nama barang yang dibeli.
 */
require_once __DIR__ . '/includes/helpers.php';
wajib_login();

$q      = mb_substr(get_str('q'), 0, 100, 'UTF-8');
$dari   = get_str('dari');
$sampai = get_str('sampai');
$errors = [];

if ($dari !== '' && !tanggal_valid($dari)) {
    $errors[] = 'Format tanggal "Dari" tidak valid.';
    $dari = '';
}
if ($sampai !== '' && !tanggal_valid($sampai)) {
    $errors[] = 'Format tanggal "Sampai" tidak valid.';
    $sampai = '';
}
if ($dari !== '' && $sampai !== '' && $dari > $sampai) {
    $errors[] = 'Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai".';
    $dari = $sampai = '';
}

$where  = [];
$params = [];
if ($dari !== '') {
    $where[]         = 't.tanggal_transaksi >= :dari';
    $params['dari']  = $dari . ' 00:00:00';
}
if ($sampai !== '') {
    $where[]           = 't.tanggal_transaksi <= :sampai';
    $params['sampai']  = $sampai . ' 23:59:59';
}
if ($q !== '') {
    $bagian = ['EXISTS (SELECT 1 FROM detail_transaksi d WHERE d.id_transaksi = t.id_transaksi AND d.nama_produk LIKE :suka)'];
    $params['suka'] = '%' . addcslashes($q, '\\%_') . '%';
    if (preg_match('/^(?:TRX-?)?0*(\d{1,10})$/i', $q, $m)) {
        $bagian[]         = 't.id_transaksi = :idtrx';
        $params['idtrx']  = (int) $m[1];
    }
    $where[] = '(' . implode(' OR ', $bagian) . ')';
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$ringkas = db_row(
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(t.total), 0) AS pendapatan FROM transaksi t $sql_where",
    $params
);
$daftar = db_all(
    "SELECT t.id_transaksi, t.tanggal_transaksi, t.total, t.bayar, t.kembalian
     FROM transaksi t $sql_where
     ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC LIMIT 200",
    $params
);
$ada_filter = ($q !== '' || $dari !== '' || $sampai !== '');

$judul_halaman = 'Riwayat Transaksi';
$menu_aktif    = 'riwayat';
include __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert">
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<section class="card filter-card no-print">
    <form class="filter-form" method="get" action="<?= e(BASE_URL) ?>riwayat.php">
        <div class="field">
            <label for="q">Cari transaksi</label>
            <div class="search-box search-box-block">
                <?= ikon('search', 18) ?>
                <input type="search" id="q" name="q" value="<?= e($q) ?>" maxlength="100"
                       placeholder="No. transaksi atau nama barang" autocomplete="off">
            </div>
        </div>
        <div class="field">
            <label for="dari">Dari tanggal</label>
            <input type="date" id="dari" name="dari" value="<?= e($dari) ?>">
        </div>
        <div class="field">
            <label for="sampai">Sampai tanggal</label>
            <input type="date" id="sampai" name="sampai" value="<?= e($sampai) ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <?php if ($ada_filter): ?>
                <a class="btn btn-outline" href="<?= e(BASE_URL) ?>riwayat.php">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <div class="card-header">
        <h2>Daftar Transaksi</h2>
        <span class="muted">
            <?= (int) $ringkas['jumlah'] ?> transaksi &bull; Total <?= e(rupiah($ringkas['pendapatan'])) ?>
        </span>
    </div>
    <?php if (!$daftar): ?>
        <p class="empty">
            <?= $ada_filter
                ? 'Tidak ada transaksi yang cocok dengan pencarian atau filter.'
                : 'Belum ada transaksi.' ?>
        </p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tanggal &amp; Waktu</th>
                        <th class="num">Total</th>
                        <th class="num">Bayar</th>
                        <th class="num">Kembalian</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $t): ?>
                    <tr>
                        <td><?= e(no_transaksi($t['id_transaksi'])) ?></td>
                        <td><?= e(fmt_tanggal($t['tanggal_transaksi'])) ?></td>
                        <td class="num"><?= e(rupiah($t['total'])) ?></td>
                        <td class="num"><?= e(rupiah($t['bayar'])) ?></td>
                        <td class="num"><?= e(rupiah($t['kembalian'])) ?></td>
                        <td class="center">
                            <div class="row-actions">
                                <a class="btn btn-light btn-sm"
                                   href="<?= e(BASE_URL) ?>detail_transaksi.php?id=<?= (int) $t['id_transaksi'] ?>"><?= ikon('eye', 16) ?> Detail</a>
                                <a class="btn btn-outline btn-sm"
                                   href="<?= e(BASE_URL) ?>struk.php?id=<?= (int) $t['id_transaksi'] ?>"><?= ikon('receipt', 16) ?> Struk</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int) $ringkas['jumlah'] > 200): ?>
            <p class="muted note">Menampilkan 200 transaksi terbaru. Persempit dengan pencarian atau filter tanggal.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
