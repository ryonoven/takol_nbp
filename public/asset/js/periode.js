/**
 * Halaman periode (SAF & Semesteran) — dipakai bersama, di-cache browser.
 *
 * Form (.js-form):
 *   .js-rupiah  angka rupiah (format ribuan, dikirim angka murni)   .js-modal  modal inti (info kategori)
 *   .js-req     wajib diisi        .js-int  bilangan bulat >= 0     .js-radio-req  grup radio wajib (pada .ps-group)
 *   .js-jenis   radio Rutin/Koreksi; .js-koreksi kotak field koreksi; .js-kreq field wajib saat Koreksi
 * Tombol edit (.js-edit): data-id, data-label, data-f-<nama field>="nilai" -> mengisi #editForm.
 * #modalEdit[data-reopen="<id>"] -> buka kembali modal setelah edit gagal validasi di server.
 */
(function () {
    'use strict';
    var MODAL_MIN = 6e9, KAT_A = 5e10;
    var digits = function (s) { return String(s == null ? '' : s).replace(/\D/g, ''); };
    var toRupiah = function (s) { var d = digits(s); return d ? parseInt(d, 10).toLocaleString('id-ID') : ''; };
    var num = function (el) { return parseInt(digits(el.value), 10) || 0; };
    var setError = function (el, on) {
        if (el.classList) el.classList.toggle('is-invalid', on);
        var g = el.closest ? el.closest('.ps-group') : null;
        if (g) g.classList.toggle('has-error', on);
    };

    /** Info kategori (A >= 50 M = 6 risiko, B = 4 risiko). Kategori final dihitung di server. */
    function updateKat(form) {
        var m = form.querySelector('.js-modal'), box = form.querySelector('.js-kat');
        if (!m || !box) return;
        var n = num(m);
        if (n <= 0) { box.hidden = true; return; }
        var v = n < MODAL_MIN ? ['warn', 'Kategori B – 4 Risiko · modal inti di bawah Rp 6 Miliar, cek kembali.']
            : n < KAT_A ? ['info', 'Kategori B – 4 Risiko (modal inti di bawah Rp 50 Miliar)']
                : ['ok', 'Kategori A – 6 Risiko (modal inti minimal Rp 50 Miliar)'];
        box.className = 'ps-inline mt-2 js-kat ' + v[0];
        box.textContent = v[1];
        box.hidden = false;
    }

    /** Field koreksi hanya untuk Koreksi (disabled -> tidak ikut terkirim). */
    function updateJenis(form, focus) {
        var box = form.querySelector('.js-koreksi');
        if (!box) return;
        var isK = (form.querySelector('.js-jenis:checked') || {}).value === 'K';
        box.hidden = !isK;
        form.querySelectorAll('.js-kreq').forEach(function (el) { el.disabled = !isK; if (!isK) setError(el, false); });
        if (isK && focus) { var f = form.querySelector('.js-kreq'); if (f) f.focus(); }
    }

    function validate(form) {
        var first = null;
        var check = function (el, bad) { setError(el, bad); if (bad && !first) first = el; };
        form.querySelectorAll('.js-req').forEach(function (el) { check(el, !el.value); });
        form.querySelectorAll('.js-rupiah').forEach(function (el) { check(el, num(el) <= 0); });
        form.querySelectorAll('.js-int').forEach(function (el) { check(el, el.value === '' || parseInt(el.value, 10) < 0); });
        form.querySelectorAll('.js-kreq').forEach(function (el) { if (!el.disabled) check(el, !el.value.trim()); });
        form.querySelectorAll('.js-radio-req').forEach(function (g) {
            var bad = !g.querySelector('input[type=radio]:checked');
            g.classList.toggle('has-error', bad);
            if (bad && !first) first = g.querySelector('input[type=radio]');
        });
        if (first) first.focus();
        return !first;
    }

    document.querySelectorAll('.js-form').forEach(function (form) {
        form.addEventListener('input', function (e) {
            var el = e.target;
            if (el.classList.contains('js-rupiah')) {
                el.value = toRupiah(el.value);
                if (el.classList.contains('js-modal')) updateKat(form);
            }
            if (el.classList.contains('is-invalid') && el.value) setError(el, false);
        });
        form.addEventListener('change', function (e) {
            var el = e.target;
            if (el.classList.contains('js-jenis')) updateJenis(form, true);
            if (el.type === 'radio') { var g = el.closest('.js-radio-req'); if (g) g.classList.remove('has-error'); }
            if (el.classList.contains('is-invalid') && el.value) setError(el, false);
        });

        var locked = false;
        form.addEventListener('submit', function (e) {
            if (locked || !validate(form)) { e.preventDefault(); return; }
            locked = true;
            form.querySelectorAll('.js-rupiah').forEach(function (el) { el.value = digits(el.value); });
            var btn = form.querySelector('.js-submit');
            if (btn) {
                btn.disabled = true;
                var t = btn.querySelector('.js-submit-txt');
                if (t) t.textContent = 'Memproses…';
            }
        });

        updateJenis(form, false);
        updateKat(form);
    });

    // ---------------------------------------------------------------- modal edit
    var overlay = document.getElementById('modalEdit');
    if (overlay) {
        var form = document.getElementById('editForm'), lastTrigger = null;

        var open = function () {
            overlay.hidden = false;
            document.body.style.overflow = 'hidden';
            var first = form.querySelector('.ps-modal-body input:not([type=hidden]):not([disabled]), .ps-modal-body select');
            if (first) first.focus();
        };
        var close = function () {
            overlay.hidden = true;
            document.body.style.overflow = '';
            if (lastTrigger) lastTrigger.focus();
        };

        /** Isi field #editForm dari data-f-<nama> pada tombol edit. */
        var fill = function (btn) {
            Object.keys(btn.dataset).forEach(function (k) {
                if (k.indexOf('f') !== 0 || k.length < 2) return;
                var name = k.charAt(1).toLowerCase() + k.slice(2), val = btn.dataset[k];
                var els = form.querySelectorAll('[name="' + name + '"]');
                els.forEach(function (el) {
                    if (el.type === 'radio') el.checked = el.value === val;
                    else el.value = el.classList.contains('js-rupiah') ? toRupiah(val) : val;
                });
            });
        };

        document.querySelectorAll('.js-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                lastTrigger = btn;
                document.getElementById('editId').value = btn.dataset.id;
                document.getElementById('mEditLabel').textContent = btn.dataset.label || '';
                fill(btn);
                form.querySelectorAll('.is-invalid, .has-error').forEach(function (el) { el.classList.remove('is-invalid', 'has-error'); });
                updateJenis(form, false);
                updateKat(form);
                open();
            });
        });
        overlay.querySelectorAll('.js-close').forEach(function (b) { b.addEventListener('click', close); });
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !overlay.hidden) close(); });

        // Edit gagal validasi di server -> buka kembali dengan isian sebelumnya
        var reopen = overlay.dataset.reopen;
        if (reopen) {
            var btn = document.querySelector('.js-edit[data-id="' + reopen + '"]');
            if (btn) {
                lastTrigger = btn;
                document.getElementById('mEditLabel').textContent = btn.dataset.label || '';
                open();
            }
        }
    }

    // Pilih periode: tanda sedang diproses
    document.querySelectorAll('.ps-item-form').forEach(function (fm) {
        fm.addEventListener('submit', function () { fm.querySelector('button').style.opacity = '.6'; });
    });

    // Notifikasi sukses hilang otomatis
    setTimeout(function () {
        document.querySelectorAll('.js-autohide').forEach(function (el) {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        });
    }, 5000);
})();
