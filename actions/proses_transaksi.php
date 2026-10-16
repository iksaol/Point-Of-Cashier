<?php
/**
 * actions/proses_transaksi.php
 * Menyimpan transaksi kasir. Hanya menerima POST.
 *
 * Prinsip keamanan:
 *  - Dari browser hanya diterima id_produk, jumlah, dan bayar.
 *  - Harga, subtotal, total, dan kembalian dihitung ulang di sini dari database.
 *  - Semua langkah berjalan dalam satu transaksi database (BEGIN / COMMIT / ROLLBACK).
 *  - Baris produk dikunci (SELECT ... FOR UPDATE) agar stok tidak bisa dipakai dua transaksi sekaligus.
 *  - Token sekali pakai mencegah transaksi ganda akibat klik berulang.
 */
require_once __DIR__ . '/../includes/helpers.php';
wajib_role(['admin', 'kasir']);

/** Kesalahan yang pesannya aman ditampilkan ke kasir. */
class TransaksiException extends RuntimeException
{
    public array $daftar;

    public function __construct(array $daftar)
    {
        parent::__construct(implode(' ', $daftar));
        $this->daftar = $daftar;
    }
}

wajib_post('transaksi.php');

// 1. Token sekali pakai
if (!trx_token_pakai()) {
    flash_set('error', 'Transaksi tidak diproses karena formulir sudah dikirim sebelumnya atau sudah kedaluwarsa. '
        . 'Periksa Riwayat Transaksi sebelum mengulang.');
    redirect('transaksi.php');
}

// 2. Validasi bentuk data yang dikirim
$ids       = $_POST['id_produk'] ?? [];
$jumlahan  = $_POST['jumlah'] ?? [];
$bayar_raw = post_str('bayar');
$errors    = [];
$items     = []; // id_produk => jumlah (duplikat digabung)

if (!is_array($ids) || !is_array($jumlahan) || count($ids) !== count($jumlahan)) {
    $errors[] = 'Data keranjang tidak valid.';
} elseif (count($ids) === 0) {
    $errors[] = 'Keranjang masih kosong. Tambahkan minimal satu produk.';
} elseif (count($ids) > 100) {
    $errors[] = 'Jumlah jenis produk dalam satu transaksi maksimal 100.';
} else {
    $ids      = array_values($ids);
    $jumlahan = array_values($jumlahan);
    foreach ($ids as $i => $id_raw) {
        $qty_raw = $jumlahan[$i];
        if (!is_string($id_raw) || !is_string($qty_raw)) {
            $errors[] = 'Data keranjang tidak valid.';
            break;
        }
        $id = ambil_id(trim($id_raw));
        if ($id === null) {
            $errors[] = 'ID produk pada keranjang tidak valid.';
            break;
        }
        if (!preg_match('/^[1-9]\d{0,6}$/', trim($qty_raw))) {
            $errors[] = 'Jumlah produk harus berupa bilangan bulat positif.';
            break;
        }
        $items[$id] = ($items[$id] ?? 0) + (int) trim($qty_raw);
    }
}

$bayar = 0;
if ($bayar_raw === '') {
    $errors[] = 'Nominal pembayaran harus diisi.';
} elseif (!preg_match('/^\d{1,12}$/', $bayar_raw)) {
    $errors[] = 'Nominal pembayaran harus berupa angka bulat positif.';
} else {
    $bayar = (int) $bayar_raw;
}

if ($errors) {
    flash_set('error', array_values(array_unique($errors)));
    redirect('transaksi.php');
}

// 3. Simpan dalam satu transaksi database
$pdo          = db();
$id_transaksi = 0;

try {
    $pdo->beginTransaction();

    $daftar_id = array_keys($items);
    sort($daftar_id); // urutan tetap -> mengurangi risiko deadlock
    $placeholder = implode(',', array_fill(0, count($daftar_id), '?'));

    $stmt = $pdo->prepare(
        "SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk IN ($placeholder) FOR UPDATE"
    );
    $stmt->execute($daftar_id);
    $produk = [];
    foreach ($stmt->fetchAll() as $r) {
        $produk[(int) $r['id_produk']] = $r;
    }

    $baris = [];
    $total = 0;
    $galat = [];
    foreach ($daftar_id as $id) {
        $jumlah = $items[$id];
        if (!isset($produk[$id])) {
            $galat[] = 'Produk dengan ID ' . $id . ' tidak ditemukan. Mungkin sudah dihapus.';
            continue;
        }
        $p     = $produk[$id];
        $harga = (int) round((float) $p['harga']); // harga selalu dari database
        $stok  = (int) $p['stok'];
        if ($jumlah > $stok) {
            $galat[] = 'Stok "' . $p['nama_produk'] . '" tidak mencukupi. Diminta ' . $jumlah . ', tersedia ' . $stok . '.';
            continue;
        }
        $subtotal = $harga * $jumlah;
        $total   += $subtotal;
        $baris[]  = [
            'id'       => $id,
            'nama'     => $p['nama_produk'],
            'harga'    => $harga,
            'jumlah'   => $jumlah,
            'subtotal' => $subtotal,
        ];
    }
    if ($galat) {
        throw new TransaksiException($galat);
    }
    if ($bayar < $total) {
        throw new TransaksiException([
            'Pembayaran kurang. Total belanja ' . rupiah($total) . ', pembayaran ' . rupiah($bayar) . '.',
        ]);
    }

    $kembalian = $bayar - $total;

    $pdo->prepare(
        'INSERT INTO transaksi (tanggal_transaksi, total, bayar, kembalian) VALUES (:tgl, :total, :bayar, :kembali)'
    )->execute([
        'tgl'     => date('Y-m-d H:i:s'),
        'total'   => $total,
        'bayar'   => $bayar,
        'kembali' => $kembalian,
    ]);
    $id_transaksi = (int) $pdo->lastInsertId();

    $simpan_detail = $pdo->prepare(
        'INSERT INTO detail_transaksi (id_transaksi, id_produk, nama_produk, harga_satuan, jumlah, subtotal)
         VALUES (:trx, :produk, :nama, :harga, :jumlah, :subtotal)'
    );
    $kurangi_stok = $pdo->prepare(
        'UPDATE produk SET stok = stok - :jumlah WHERE id_produk = :id AND stok >= :minimal'
    );

    foreach ($baris as $b) {
        $simpan_detail->execute([
            'trx'      => $id_transaksi,
            'produk'   => $b['id'],
            'nama'     => $b['nama'],
            'harga'    => $b['harga'],
            'jumlah'   => $b['jumlah'],
            'subtotal' => $b['subtotal'],
        ]);
        $kurangi_stok->execute(['jumlah' => $b['jumlah'], 'id' => $b['id'], 'minimal' => $b['jumlah']]);
        if ($kurangi_stok->rowCount() !== 1) {
            throw new TransaksiException(['Stok "' . $b['nama'] . '" berubah saat transaksi diproses. Silakan ulangi.']);
        }
    }

    $pdo->commit();
} catch (TransaksiException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', $e->daftar);
    redirect('transaksi.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[KasirKu] proses_transaksi: ' . $e->getMessage());
    flash_set('error', 'Transaksi gagal disimpan karena kesalahan sistem. Stok tidak berubah, silakan coba lagi.');
    redirect('transaksi.php');
}

flash_set('success', 'Transaksi ' . no_transaksi($id_transaksi) . ' berhasil disimpan.');
redirect('struk.php?id=' . $id_transaksi . '&baru=1');
