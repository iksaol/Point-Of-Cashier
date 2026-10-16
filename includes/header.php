<?php
/**
 * includes/header.php
 * Pembuka halaman: <head>, sidebar, topbar, dan notifikasi.
 * Variabel yang diatur oleh halaman pemanggil:
 *   $judul_halaman : judul pada topbar dan tab browser
 *   $menu_aktif    : dashboard | produk | transaksi | riwayat
 */
$judul_halaman = $judul_halaman ?? 'Point Of Cashier';
$menu_aktif    = $menu_aktif ?? '';
$flash         = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul_halaman) ?> - Point Of Cashier</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(BASE_URL) ?>assets/images/POC.jpg">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
</head>
<body>
<div id="toastContainer" class="toast-container no-print" aria-live="polite">
    <?php if ($flash): ?>
        <div class="toast toast-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"
             role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
            <div class="toast-body">
                <?php if (count($flash['messages']) === 1): ?>
                    <?= e($flash['messages'][0]) ?>
                <?php else: ?>
                    <ul>
                        <?php foreach ($flash['messages'] as $pesan): ?>
                            <li><?= e($pesan) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <button type="button" class="toast-close" aria-label="Tutup notifikasi">&times;</button>
        </div>
    <?php endif; ?>
</div>

<div class="app">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="sidebar-backdrop" data-sidebar-toggle></div>

    <div class="main">
        <header class="topbar no-print">
            <button type="button" class="icon-btn menu-btn" data-sidebar-toggle aria-label="Buka atau tutup menu">
                <?= ikon('menu', 22) ?>
            </button>
            <h1 class="topbar-title"><?= e($judul_halaman) ?></h1>
            <div class="topbar-date"><?= e(tanggal_indonesia()) ?></div>
        </header>
        <main class="content">
