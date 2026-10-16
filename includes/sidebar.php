<?php
/**
 * includes/sidebar.php
 * Menu navigasi kiri. Memakai variabel $menu_aktif dari header.php.
 */
$role_pengguna = $_SESSION['role'] ?? '';
$menu = [
    'dashboard' => ['Dashboard',          'index.php',          'dashboard'],
    'produk'    => ['Produk',             'produk.php',         'box'],
    'transaksi' => ['Transaksi Kasir',    'transaksi.php',      'cart'],
    'riwayat'   => ['Riwayat Transaksi',  'riwayat.php',        'history'],
];
?>
<aside class="sidebar no-print" id="sidebar">
    <a class="brand" href="<?= e(BASE_URL) ?>index.php">
        <img src="<?= e(BASE_URL) ?>assets/images/POC.jpg" alt="" width="38" height="38">
        <span>Point Of Cashier</span>
    </a>
    <nav class="nav" aria-label="Menu utama">
        <?php foreach ($menu as $kunci => [$label, $url, $ikon]): ?>
            <?php if ($kunci === 'produk' && $role_pengguna !== 'admin') continue; ?>
            <a class="nav-link<?= $menu_aktif === $kunci ? ' active' : '' ?>"
               href="<?= e(BASE_URL . $url) ?>"<?= $menu_aktif === $kunci ? ' aria-current="page"' : '' ?>>
                <?= ikon($ikon) ?>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <strong><?= e($_SESSION['nama'] ?? 'Pengguna') ?></strong><br>
        <small><?= e(ucfirst($role_pengguna)) ?></small><br>
        <a href="<?= e(BASE_URL) ?>logout.php" style="color:inherit">Keluar</a>
    </div>
</aside>
