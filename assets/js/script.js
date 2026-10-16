/* =====================================================================
   KasirKu - script.js
   Isi: sidebar mobile, notifikasi, konfirmasi hapus, anti kirim ganda,
        formulir produk (modal + validasi), dan keranjang kasir.
   Validasi di sini hanya untuk kenyamanan; PHP tetap memvalidasi ulang.
   ===================================================================== */
(function () {
    'use strict';

    function $(selector, root) { return (root || document).querySelector(selector); }
    function $$(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

    /** Format rupiah Indonesia, contoh: Rp10.000 */
    function rupiah(n) {
        n = Math.round(Number(n) || 0);
        var minus = n < 0 ? '-' : '';
        return minus + 'Rp' + String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /* ---------------------------------------------------------------
       Notifikasi (toast)
       --------------------------------------------------------------- */
    function pasangToast(toast, durasi) {
        var tutup = $('.toast-close', toast);
        function hapus() { if (toast.parentNode) { toast.parentNode.removeChild(toast); } }
        if (tutup) { tutup.addEventListener('click', hapus); }
        setTimeout(hapus, durasi);
    }

    function showToast(tipe, pesan) {
        var wadah = $('#toastContainer');
        if (!wadah) { return; }
        var daftar = Array.isArray(pesan) ? pesan : [pesan];

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + tipe;
        toast.setAttribute('role', tipe === 'error' ? 'alert' : 'status');

        var isi = document.createElement('div');
        isi.className = 'toast-body';
        if (daftar.length === 1) {
            isi.textContent = daftar[0];
        } else {
            var ul = document.createElement('ul');
            daftar.forEach(function (teks) {
                var li = document.createElement('li');
                li.textContent = teks;
                ul.appendChild(li);
            });
            isi.appendChild(ul);
        }
        var tutup = document.createElement('button');
        tutup.type = 'button';
        tutup.className = 'toast-close';
        tutup.setAttribute('aria-label', 'Tutup notifikasi');
        tutup.innerHTML = '&times;';

        toast.appendChild(isi);
        toast.appendChild(tutup);
        wadah.appendChild(toast);
        pasangToast(toast, tipe === 'error' ? 9000 : 5000);
    }

    $$('#toastContainer .toast').forEach(function (t) {
        pasangToast(t, t.classList.contains('toast-error') ? 9000 : 5000);
    });

    /* ---------------------------------------------------------------
       Sidebar (tampilan ponsel)
       --------------------------------------------------------------- */
    $$('[data-sidebar-toggle]').forEach(function (el) {
        el.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });
    });
    $$('.nav-link').forEach(function (el) {
        el.addEventListener('click', function () { document.body.classList.remove('sidebar-open'); });
    });

    /* ---------------------------------------------------------------
       Tombol cetak
       --------------------------------------------------------------- */
    $$('[data-print]').forEach(function (el) {
        el.addEventListener('click', function () { window.print(); });
    });

    /* ---------------------------------------------------------------
       Konfirmasi dan pencegahan kirim ganda untuk semua formulir POST
       --------------------------------------------------------------- */
    // Fase capture: blokir pengiriman kedua pada formulir yang sedang diproses.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.dataset && form.dataset.submitting === '1') {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

    // Konfirmasi (misalnya sebelum menghapus produk).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var pesan = form.getAttribute && form.getAttribute('data-confirm');
        if (pesan && !window.confirm(pesan)) {
            e.preventDefault();
        }
    });

    // Fase bubble (paling akhir): jika tidak dibatalkan, kunci formulir.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented || !form.hasAttribute || !form.hasAttribute('data-once')) { return; }
        form.dataset.submitting = '1';
        setTimeout(function () {
            $$('button[type="submit"]', form).forEach(function (tombol) {
                tombol.disabled = true;
                tombol.dataset.label = tombol.innerHTML;
                tombol.textContent = 'Memproses...';
            });
        }, 0);
    });

    // Saat kembali lewat tombol Back browser, aktifkan lagi formulir yang terkunci.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        $$('form[data-once]').forEach(function (form) {
            form.dataset.submitting = '0';
            $$('button[type="submit"]', form).forEach(function (tombol) {
                tombol.disabled = false;
                if (tombol.dataset.label) { tombol.innerHTML = tombol.dataset.label; }
            });
        });
    });

    /* ---------------------------------------------------------------
       Halaman produk: modal tambah / edit
       --------------------------------------------------------------- */
    var modal = $('#modalProduk');
    if (modal) {
        var formProduk = $('#formProduk');
        var fId = $('#produkId'), fNama = $('#produkNama'), fHarga = $('#produkHarga'), fStok = $('#produkStok');
        var daftarError = $('#produkError');
        var judulModal = $('#modalProdukJudul');
        var tombolSimpan = $('#produkSimpan');

        var MAKS_HARGA = 999999999, MAKS_STOK = 1000000;

        // Aturan sama dengan validasi_produk() di includes/helpers.php
        function validasiProduk(nama, harga, stok) {
            var err = [];
            nama = nama.replace(/\s+/g, ' ').trim();
            harga = harga.trim();
            stok = stok.trim();

            if (nama === '') { err.push('Nama produk tidak boleh kosong.'); }
            else if (nama.length > 100) { err.push('Nama produk maksimal 100 karakter.'); }

            if (harga === '') { err.push('Harga tidak boleh kosong.'); }
            else if (isNaN(Number(harga))) { err.push('Harga harus berupa angka.'); }
            else if (Number(harga) <= 0) { err.push('Harga harus lebih besar dari 0 (tidak boleh nol atau negatif).'); }
            else if (!/^\d+$/.test(harga)) { err.push('Harga harus berupa bilangan bulat rupiah, tanpa titik atau koma.'); }
            else if (Number(harga) > MAKS_HARGA) { err.push('Harga terlalu besar (maksimal ' + rupiah(MAKS_HARGA) + ').'); }

            if (stok === '') { err.push('Stok tidak boleh kosong.'); }
            else if (isNaN(Number(stok))) { err.push('Stok harus berupa angka.'); }
            else if (Number(stok) < 0) { err.push('Stok tidak boleh negatif.'); }
            else if (!/^\d+$/.test(stok)) { err.push('Stok harus berupa bilangan bulat, tanpa desimal.'); }
            else if (Number(stok) > MAKS_STOK) { err.push('Stok terlalu besar (maksimal 1.000.000).'); }
            return err;
        }

        function tampilkanError(daftar) {
            daftarError.innerHTML = '';
            daftar.forEach(function (teks) {
                var li = document.createElement('li');
                li.textContent = teks;
                daftarError.appendChild(li);
            });
            daftarError.hidden = daftar.length === 0;
        }

        function bukaModal(mode, data) {
            data = data || {};
            tampilkanError([]);
            if (mode === 'edit') {
                formProduk.action = formProduk.getAttribute('data-action-update');
                judulModal.textContent = 'Edit Produk';
                tombolSimpan.textContent = 'Simpan Perubahan';
            } else {
                formProduk.action = formProduk.getAttribute('data-action-tambah');
                judulModal.textContent = 'Tambah Produk';
                tombolSimpan.textContent = 'Simpan Produk';
            }
            fId.value = data.id || '';
            fNama.value = data.nama || '';
            fHarga.value = data.harga || '';
            fStok.value = (data.stok === undefined || data.stok === null) ? '' : data.stok;
            modal.hidden = false;
            fNama.focus();
        }

        function tutupModal() { modal.hidden = true; }

        $$('[data-produk-tambah]').forEach(function (btn) {
            btn.addEventListener('click', function () { bukaModal('tambah', {}); });
        });
        $$('[data-produk-edit]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                bukaModal('edit', {
                    id: btn.getAttribute('data-id'),
                    nama: btn.getAttribute('data-nama'),
                    harga: btn.getAttribute('data-harga'),
                    stok: btn.getAttribute('data-stok')
                });
            });
        });
        $$('[data-modal-close]', modal).forEach(function (btn) { btn.addEventListener('click', tutupModal); });
        modal.addEventListener('click', function (e) { if (e.target === modal) { tutupModal(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { tutupModal(); } });

        formProduk.addEventListener('submit', function (e) {
            var err = validasiProduk(fNama.value, fHarga.value, fStok.value);
            if (err.length) {
                e.preventDefault();
                tampilkanError(err);
            }
        });

        // Buka kembali formulir dengan input lama jika validasi server gagal.
        var lama = $('#oldProduk');
        if (lama) {
            try {
                var d = JSON.parse(lama.getAttribute('data-old'));
                bukaModal(d.mode === 'edit' ? 'edit' : 'tambah', d);
            } catch (err) { /* abaikan */ }
        }
    }

    /* ---------------------------------------------------------------
       Halaman kasir: keranjang
       --------------------------------------------------------------- */
    var dataEl = $('#dataProduk');
    var formTrx = $('#formTransaksi');
    if (dataEl && formTrx) {
        var STORAGE_KEY = 'KasirKu_cart';
        var produk = {};
        try {
            JSON.parse(dataEl.textContent).forEach(function (p) { produk[p.id] = p; });
        } catch (err) { produk = {}; }

        var cart = []; // [{ id, jumlah }]
        var cartBody = $('#cartBody');
        var totalText = $('#totalText');
        var cartCount = $('#cartCount');
        var inputBayar = $('#bayar');
        var kembalianText = $('#kembalianText');
        var kembalianLabel = $('#kembalianLabel');
        var bayarInfo = $('#bayarInfo');

        function simpanCart() {
            try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(cart)); } catch (err) { /* abaikan */ }
        }

        function muatCart() {
            try {
                var arr = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
                arr.forEach(function (it) {
                    var p = produk[it.id];
                    var q = parseInt(it.jumlah, 10);
                    // Sesuaikan dengan stok terbaru dari server.
                    if (p && p.stok > 0 && q > 0) { cart.push({ id: p.id, jumlah: Math.min(q, p.stok) }); }
                });
            } catch (err) { cart = []; }
        }

        function cariItem(id) {
            for (var i = 0; i < cart.length; i++) { if (cart[i].id === id) { return cart[i]; } }
            return null;
        }

        function hitungTotal() {
            return cart.reduce(function (sum, it) { return sum + produk[it.id].harga * it.jumlah; }, 0);
        }

        function sel(teks, kelas) {
            var td = document.createElement('td');
            if (kelas) { td.className = kelas; }
            td.textContent = teks;
            return td;
        }

        function render() {
            cartBody.innerHTML = '';
            if (cart.length === 0) {
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                td.colSpan = 5;
                td.className = 'empty';
                td.textContent = 'Keranjang kosong. Klik Tambah pada produk.';
                tr.appendChild(td);
                cartBody.appendChild(tr);
            }

            cart.forEach(function (it) {
                var p = produk[it.id];
                var tr = document.createElement('tr');

                tr.appendChild(sel(p.nama, 'wrap'));
                tr.appendChild(sel(rupiah(p.harga), 'num'));

                var tdQty = document.createElement('td');
                tdQty.className = 'center';
                var qty = document.createElement('div');
                qty.className = 'qty';

                var min = document.createElement('button');
                min.type = 'button'; min.textContent = '\u2212';
                min.setAttribute('data-act', 'kurang'); min.setAttribute('data-id', it.id);
                min.setAttribute('aria-label', 'Kurangi jumlah ' + p.nama);

                var inp = document.createElement('input');
                inp.type = 'text'; inp.inputMode = 'numeric'; inp.autocomplete = 'off';
                inp.name = 'jumlah[]'; inp.value = it.jumlah;
                inp.setAttribute('data-act', 'ubah'); inp.setAttribute('data-id', it.id);
                inp.setAttribute('aria-label', 'Jumlah ' + p.nama);

                var plus = document.createElement('button');
                plus.type = 'button'; plus.textContent = '+';
                plus.setAttribute('data-act', 'tambah'); plus.setAttribute('data-id', it.id);
                plus.setAttribute('aria-label', 'Tambah jumlah ' + p.nama);

                var hid = document.createElement('input');
                hid.type = 'hidden'; hid.name = 'id_produk[]'; hid.value = it.id;

                qty.appendChild(min); qty.appendChild(inp); qty.appendChild(plus); qty.appendChild(hid);
                tdQty.appendChild(qty);
                tr.appendChild(tdQty);

                tr.appendChild(sel(rupiah(p.harga * it.jumlah), 'num'));

                var tdDel = document.createElement('td');
                var del = document.createElement('button');
                del.type = 'button'; del.className = 'cart-remove'; del.textContent = 'Hapus';
                del.setAttribute('data-act', 'hapus'); del.setAttribute('data-id', it.id);
                del.setAttribute('aria-label', 'Hapus ' + p.nama + ' dari keranjang');
                tdDel.appendChild(del);
                tr.appendChild(tdDel);

                cartBody.appendChild(tr);
            });

            var jumlahBarang = cart.reduce(function (s, it) { return s + it.jumlah; }, 0);
            cartCount.textContent = cart.length + ' item (' + jumlahBarang + ' barang)';
            totalText.textContent = rupiah(hitungTotal());
            perbaruiKembalian();
            simpanCart();
        }

        function perbaruiKembalian() {
            var total = hitungTotal();
            var teks = inputBayar.value.trim();
            bayarInfo.textContent = '';
            bayarInfo.className = 'bayar-info';
            kembalianLabel.textContent = 'Kembalian';

            if (teks === '') { kembalianText.textContent = rupiah(0); return; }
            if (!/^\d+$/.test(teks)) {
                kembalianText.textContent = rupiah(0);
                bayarInfo.textContent = 'Nominal pembayaran harus berupa angka bulat, tanpa titik, koma, atau huruf.';
                bayarInfo.className = 'bayar-info error';
                return;
            }
            var bayar = parseInt(teks, 10);
            if (bayar < total) {
                kembalianLabel.textContent = 'Kekurangan';
                kembalianText.textContent = rupiah(total - bayar);
                bayarInfo.textContent = 'Pembayaran masih kurang dari total belanja.';
                bayarInfo.className = 'bayar-info error';
            } else {
                kembalianText.textContent = rupiah(bayar - total);
            }
        }

        function tambahProduk(id) {
            var p = produk[id];
            if (!p) { return; }
            if (p.stok <= 0) { showToast('error', 'Stok "' + p.nama + '" habis.'); return; }
            var it = cariItem(id);
            if (it) {
                if (it.jumlah >= p.stok) {
                    showToast('error', 'Jumlah "' + p.nama + '" tidak boleh melebihi stok (tersisa ' + p.stok + ').');
                    return;
                }
                it.jumlah += 1;
            } else {
                cart.push({ id: id, jumlah: 1 });
            }
            render();
        }

        function hapusItem(id) {
            cart = cart.filter(function (it) { return it.id !== id; });
            render();
        }

        function ubahJumlah(id, teks) {
            var it = cariItem(id), p = produk[id];
            if (!it || !p) { return; }
            teks = String(teks).trim();
            if (!/^\d+$/.test(teks) || parseInt(teks, 10) < 1) {
                showToast('error', 'Jumlah produk harus berupa bilangan bulat positif.');
            } else if (parseInt(teks, 10) > p.stok) {
                it.jumlah = p.stok;
                showToast('error', 'Jumlah "' + p.nama + '" melebihi stok. Diatur ke stok tersedia (' + p.stok + ').');
            } else {
                it.jumlah = parseInt(teks, 10);
            }
            render();
        }

        // Tombol Tambah pada daftar produk
        $$('[data-tambah]').forEach(function (btn) {
            btn.addEventListener('click', function () { tambahProduk(parseInt(btn.getAttribute('data-tambah'), 10)); });
        });

        // Aksi di dalam keranjang (event delegation)
        cartBody.addEventListener('click', function (e) {
            var el = e.target.closest('[data-act]');
            if (!el || el.tagName === 'INPUT') { return; }
            var id = parseInt(el.getAttribute('data-id'), 10);
            var act = el.getAttribute('data-act');
            var it = cariItem(id);
            if (act === 'hapus') { hapusItem(id); }
            else if (act === 'tambah') { tambahProduk(id); }
            else if (act === 'kurang' && it) {
                if (it.jumlah > 1) { it.jumlah -= 1; render(); } else { hapusItem(id); }
            }
        });
        cartBody.addEventListener('change', function (e) {
            var el = e.target;
            if (el.getAttribute('data-act') === 'ubah') {
                ubahJumlah(parseInt(el.getAttribute('data-id'), 10), el.value);
            }
        });

        // Pencarian produk (langsung saat mengetik)
        var cari = $('#cariProduk');
        var barisKosong = $('#produkKosong');
        if (cari) {
            cari.addEventListener('input', function () {
                var kata = cari.value.trim().toLowerCase();
                var tampil = 0;
                $$('#tabelProduk tbody tr[data-nama]').forEach(function (tr) {
                    var cocok = tr.getAttribute('data-nama').toLowerCase().indexOf(kata) !== -1;
                    tr.hidden = !cocok;
                    if (cocok) { tampil++; }
                });
                if (barisKosong) { barisKosong.hidden = tampil !== 0; }
            });
        }

        // Pembayaran
        inputBayar.addEventListener('input', perbaruiKembalian);
        $('#uangPas').addEventListener('click', function () {
            inputBayar.value = String(hitungTotal());
            perbaruiKembalian();
            inputBayar.focus();
        });

        // Kosongkan keranjang
        $('#kosongkan').addEventListener('click', function () {
            if (cart.length === 0) { showToast('error', 'Keranjang sudah kosong.'); return; }
            if (window.confirm('Kosongkan seluruh isi keranjang?')) {
                cart = [];
                inputBayar.value = '';
                render();
            }
        });

        // Validasi sebelum kirim (server tetap memvalidasi ulang)
        formTrx.addEventListener('submit', function (e) {
            var err = [];
            if (cart.length === 0) {
                err.push('Keranjang masih kosong. Tambahkan minimal satu produk.');
            } else {
                cart.forEach(function (it) {
                    var p = produk[it.id];
                    if (!(it.jumlah >= 1) || it.jumlah > p.stok) {
                        err.push('Jumlah "' + p.nama + '" tidak valid (stok tersedia ' + p.stok + ').');
                    }
                });
            }
            var teks = inputBayar.value.trim();
            if (teks === '') {
                err.push('Nominal pembayaran harus diisi.');
            } else if (!/^\d+$/.test(teks)) {
                err.push('Nominal pembayaran harus berupa angka bulat positif.');
            } else if (cart.length > 0 && parseInt(teks, 10) < hitungTotal()) {
                err.push('Pembayaran kurang dari total belanja (' + rupiah(hitungTotal()) + ').');
            }
            if (err.length) {
                e.preventDefault();
                showToast('error', err);
            }
        });

        muatCart();
        render();
    }
})();
