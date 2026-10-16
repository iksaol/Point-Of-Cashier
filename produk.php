<?php
/**
 * produk.php - Manajemen produk (daftar, cari, tambah, ubah, hapus)
 * Penyimpanan dilakukan oleh actions/simpan_produk.php, update_produk.php, hapus_produk.php.
 */
require_once __DIR__ . '/includes/helpers.php';
wajib_role(['admin']);

$q = mb_substr(get_str('q'), 0, 100, 'UTF-8');
if ($q !== '') {
    $daftar = db_all(
        'SELECT id_produk, nama_produk, harga, stok FROM produk
         WHERE nama_produk LIKE :q ORDER BY nama_produk ASC',
        ['q' => '%' . addcslashes($q, '\\%_') . '%']
    );
} else {
    $daftar = db_all('SELECT id_produk, nama_produk, harga, stok FROM produk ORDER BY nama_produk ASC');
}

// Input lama (jika penyimpanan sebelumnya gagal validasi) untuk membuka kembali formulir.
$input_lama = $_SESSION['old_produk'] ?? null;
unset($_SESSION['old_produk']);

$judul_halaman = 'Manajemen Produk';
$menu_aktif    = 'produk';
include __DIR__ . '/includes/header.php';
?>

<div class="toolbar no-print">
    <form class="search-form" method="get" action="<?= e(BASE_URL) ?>produk.php" role="search">
        <label class="sr-only" for="cari">Cari produk</label>
        <div class="search-box">
            <?= ikon('search', 18) ?>
            <input type="search" id="cari" name="q" value="<?= e($q) ?>" maxlength="100"
                   placeholder="Cari nama produk..." autocomplete="off">
        </div>
        <button type="submit" class="btn btn-light">Cari</button>
        <?php if ($q !== ''): ?>
            <a class="btn btn-outline" href="<?= e(BASE_URL) ?>produk.php">Reset</a>
        <?php endif; ?>
    </form>
    <button type="button" class="btn btn-primary" data-produk-tambah><?= ikon('plus', 18) ?> Tambah Produk</button>
</div>

<section class="card">
    <div class="card-header">
        <h2>Daftar Produk</h2>
        <span class="muted"><?= count($daftar) ?> produk<?= $q !== '' ? ' ditemukan' : '' ?></span>
    </div>
    <?php if (!$daftar): ?>
        <p class="empty">
            <?= $q !== ''
                ? 'Tidak ada produk dengan nama "' . e($q) . '". Coba kata kunci lain.'
                : 'Belum ada produk. Klik Tambah Produk untuk memulai.' ?>
        </p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th class="num">Harga</th>
                        <th class="center">Stok</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($daftar as $p): ?>
                    <?php $stok = (int) $p['stok']; $harga = (int) round((float) $p['harga']); ?>
                    <tr>
                        <td><?= (int) $p['id_produk'] ?></td>
                        <td class="wrap"><?= e($p['nama_produk']) ?></td>
                        <td class="num"><?= e(rupiah($harga)) ?></td>
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
                            <div class="row-actions">
                                <button type="button" class="btn btn-light btn-sm" data-produk-edit
                                        data-id="<?= (int) $p['id_produk'] ?>"
                                        data-nama="<?= e($p['nama_produk']) ?>"
                                        data-harga="<?= $harga ?>"
                                        data-stok="<?= $stok ?>"><?= ikon('edit', 16) ?> Edit</button>
                                <form method="post" action="<?= e(BASE_URL) ?>actions/hapus_produk.php"
                                      data-confirm="Hapus produk &quot;<?= e($p['nama_produk']) ?>&quot;? Tindakan ini tidak dapat dibatalkan."
                                      data-once>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_produk" value="<?= (int) $p['id_produk'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><?= ikon('trash', 16) ?> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Modal tambah / edit produk -->
<div class="modal" id="modalProduk" role="dialog" aria-modal="true" aria-labelledby="modalProdukJudul" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="modalProdukJudul">Tambah Produk</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><?= ikon('x', 20) ?></button>
        </div>
        <form id="formProduk" method="post" novalidate data-once
              data-action-tambah="<?= e(BASE_URL) ?>actions/simpan_produk.php"
              data-action-update="<?= e(BASE_URL) ?>actions/update_produk.php"
              action="<?= e(BASE_URL) ?>actions/simpan_produk.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id_produk" id="produkId" value="">

            <ul class="form-errors" id="produkError" hidden></ul>

            <div class="field">
                <label for="produkNama">Nama produk</label>
                <input type="text" id="produkNama" name="nama_produk" maxlength="100" autocomplete="off"
                       placeholder="Contoh: Gula Pasir 1 kg">
            </div>
            <div class="field">
                <label for="produkHarga">Harga (Rp)</label>
                <input type="text" id="produkHarga" name="harga" inputmode="numeric" autocomplete="off"
                       placeholder="Contoh: 16000">
                <small class="form-hint">Bilangan bulat rupiah lebih dari 0, tanpa titik atau koma.</small>
            </div>
            <div class="field">
                <label for="produkStok">Stok</label>
                <input type="text" id="produkStok" name="stok" inputmode="numeric" autocomplete="off"
                       placeholder="Contoh: 30">
                <small class="form-hint">Bilangan bulat 0 atau lebih.</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary" id="produkSimpan">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<?php if (is_array($input_lama)): ?>
    <div id="oldProduk" hidden data-old="<?= e(json_encode($input_lama, JSON_UNESCAPED_UNICODE)) ?>"></div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
