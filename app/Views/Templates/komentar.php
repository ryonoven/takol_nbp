<?php
/**
 * Widget komentar halaman pelaporan (laporan tahunan & SAF) (tombol mengambang + badge + dialog).
 * Pemakaian di view:  <?= view('templates/komentar', ['komentarPage' => 'Riwayatbpr']) ?>
 *
 * - Badge = jumlah komentar orang lain yang belum dibaca; hilang setelah dialog dibuka.
 * - Polling ringan tiap POLL_MS, berhenti saat tab tidak aktif dan langsung cek saat kembali aktif.
 */
use App\Controllers\Komentar;
use App\Models\M_komentar;

$page = (string) ($komentarPage ?? '');
if (!M_komentar::isPage($page)) {
    return;
}
$title = M_komentar::title($page);
?>
<div id="kmt" data-page="<?= esc($page, 'attr') ?>" data-base="<?= esc(rtrim(site_url('komentar'), '/'), 'attr') ?>"
    data-poll="<?= Komentar::POLL_MS ?>" data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>">

    <button type="button" class="kmt-fab" id="kmtOpen" aria-haspopup="dialog" aria-controls="kmtDialog"
        title="Komentar">
        <i class="fas fa-comment-dots" aria-hidden="true"></i>
        <span class="kmt-fab-label">Komentar</span>
        <span class="kmt-badge" id="kmtBadge" hidden aria-live="polite"></span>
    </button>

    <div class="kmt-overlay" id="kmtDialog" role="dialog" aria-modal="true" aria-labelledby="kmtTitle" hidden>
        <div class="kmt-panel">
            <div class="kmt-head">
                <div>
                    <div class="kmt-title" id="kmtTitle"><i class="fas fa-comments"></i> Komentar</div>
                    <div class="kmt-sub"><?= esc($title) ?></div>
                </div>
                <button type="button" class="kmt-close" id="kmtClose" aria-label="Tutup"><i class="fas fa-times"></i></button>
            </div>

            <div class="kmt-list" id="kmtList" aria-live="polite"></div>

            <form class="kmt-form" id="kmtForm" novalidate>
                <textarea id="kmtText" rows="3" maxlength="<?= M_komentar::MAX_LEN ?>"
                    placeholder="Tulis komentar, catatan, atau arahan…" aria-label="Tulis komentar"></textarea>
                <div class="kmt-form-foot">
                    <span class="kmt-hint">Ctrl+Enter untuk kirim · <span id="kmtCount">0</span>/<?= M_komentar::MAX_LEN ?></span>
                    <button type="submit" class="kmt-send" id="kmtSend"><i class="fas fa-paper-plane"></i> Kirim</button>
                </div>
                <div class="kmt-error" id="kmtError" hidden></div>
            </form>
        </div>
    </div>
</div>

<style>
    .kmt-fab {
        position: fixed; right: 22px; bottom: 22px; z-index: 1040;
        display: inline-flex; align-items: center; gap: 8px; padding: 12px 18px;
        border: none; border-radius: 999px; cursor: pointer;
        background: linear-gradient(135deg, #1f2aa8, #141863); color: #fff; font-weight: 600; font-size: .92rem;
        box-shadow: 0 6px 20px rgba(20, 24, 99, .35); transition: transform .15s, box-shadow .15s;
    }
    .kmt-fab:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(20, 24, 99, .45); }
    .kmt-fab:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
    .kmt-fab .fa-comment-dots { font-size: 1.15rem; }
    .kmt-fab.has-unread { animation: kmtPulse 2s ease-in-out infinite; }
    @keyframes kmtPulse { 0%, 100% { box-shadow: 0 6px 20px rgba(20, 24, 99, .35); } 50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, .18), 0 6px 20px rgba(20, 24, 99, .35); } }
    .kmt-badge {
        position: absolute; top: -6px; right: -4px; min-width: 22px; height: 22px; padding: 0 6px;
        border-radius: 999px; background: #ef4444; color: #fff; border: 2px solid #fff;
        font-size: .72rem; font-weight: 700; line-height: 18px; text-align: center;
    }

    .kmt-overlay { position: fixed; inset: 0; z-index: 1060; background: rgba(15, 23, 42, .45); display: flex; justify-content: flex-end; }
    .kmt-overlay[hidden] { display: none; }
    .kmt-panel {
        width: 420px; max-width: 100%; height: 100%; background: #fff; display: flex; flex-direction: column;
        box-shadow: -10px 0 30px rgba(0, 0, 0, .15); animation: kmtSlide .2s ease;
    }
    @keyframes kmtSlide { from { transform: translateX(30px); opacity: 0; } to { transform: none; opacity: 1; } }
    .kmt-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 16px 18px; background: linear-gradient(135deg, #1f2aa8, #141863); color: #fff; }
    .kmt-title { font-weight: 700; font-size: 1.05rem; }
    .kmt-sub { font-size: .8rem; opacity: .85; margin-top: 2px; }
    .kmt-close { background: rgba(255, 255, 255, .15); border: none; color: #fff; width: 34px; height: 34px; border-radius: 8px; cursor: pointer; }
    .kmt-close:hover { background: rgba(255, 255, 255, .28); }

    .kmt-list { flex: 1; overflow-y: auto; padding: 16px; background: #f8fafc; display: flex; flex-direction: column; gap: 10px; }
    .kmt-empty, .kmt-loading { margin: auto; text-align: center; color: #94a3b8; font-size: .88rem; }
    .kmt-item { max-width: 88%; padding: 10px 12px; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; align-self: flex-start; }
    .kmt-item.own { align-self: flex-end; background: #eef2ff; border-color: #c7d2fe; }
    .kmt-item.new { border-color: #fca5a5; box-shadow: 0 0 0 2px rgba(239, 68, 68, .12); }
    .kmt-meta { display: flex; justify-content: space-between; gap: 10px; font-size: .74rem; color: #64748b; margin-bottom: 4px; }
    .kmt-author { font-weight: 700; color: #1e293b; }
    .kmt-you { font-weight: 600; color: #4338ca; }
    .kmt-text { margin: 0; font-size: .88rem; color: #1e293b; white-space: pre-wrap; word-break: break-word; }

    .kmt-form { border-top: 1px solid #e2e8f0; padding: 12px 16px 16px; background: #fff; }
    .kmt-form textarea { width: 100%; resize: vertical; min-height: 70px; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: .9rem; }
    .kmt-form textarea:focus { outline: none; border-color: #1f2aa8; box-shadow: 0 0 0 3px rgba(31, 42, 168, .1); }
    .kmt-form-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 8px; }
    .kmt-hint { font-size: .74rem; color: #94a3b8; }
    .kmt-send { border: none; border-radius: 8px; padding: 8px 16px; background: linear-gradient(135deg, #1fa85a, #146337); color: #fff; font-weight: 600; cursor: pointer; }
    .kmt-send:disabled { opacity: .6; cursor: not-allowed; }
    .kmt-error { margin-top: 8px; font-size: .8rem; color: #b91c1c; }

    @media (max-width: 576px) {
        .kmt-fab { right: 14px; bottom: 14px; padding: 12px 14px; }
        .kmt-fab-label { display: none; }
    }
    @media (prefers-reduced-motion: reduce) { .kmt-fab, .kmt-panel { animation: none !important; transition: none !important; } }
</style>

<script>
    (function () {
        'use strict';
        var root = document.getElementById('kmt');
        if (!root || !window.fetch) return;

        var PAGE = root.dataset.page, BASE = root.dataset.base, POLL = parseInt(root.dataset.poll, 10) || 30000;
        var csrf = { name: root.dataset.csrfName, hash: root.dataset.csrfHash };
        var el = function (id) { return document.getElementById(id); };
        var fab = el('kmtOpen'), badge = el('kmtBadge'), dlg = el('kmtDialog'), list = el('kmtList'),
            form = el('kmtForm'), text = el('kmtText'), send = el('kmtSend'), err = el('kmtError'), count = el('kmtCount');
        var unread = 0, timer = null, busy = false;

        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
        function when(s) {
            var t = new Date(String(s).replace(' ', 'T'));
            if (isNaN(t)) return esc(s);
            var diff = (Date.now() - t.getTime()) / 1000;
            if (diff < 60) return 'baru saja';
            if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
            if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
            return t.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
        function setBadge(n) {
            unread = n;
            badge.hidden = n <= 0;
            badge.textContent = n > 99 ? '99+' : String(n);
            fab.classList.toggle('has-unread', n > 0);
            fab.setAttribute('aria-label', n > 0 ? 'Komentar, ' + n + ' belum dibaca' : 'Komentar');
        }
        function api(path, opts) {
            return fetch(BASE + '/' + path, Object.assign({ credentials: 'same-origin', headers: { 'Accept': 'application/json' } }, opts || {}))
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid.' }; }); })
                .then(function (j) { if (j && j.csrf) csrf = j.csrf; return j; });
        }

        function render(items, newCount) {
            if (!items.length) {
                list.innerHTML = '<div class="kmt-empty"><i class="fas fa-comment-slash fa-2x d-block mb-2"></i>Belum ada komentar.</div>';
                return;
            }
            var others = items.filter(function (k) { return !k.own; });
            var firstNew = others.length - newCount; // komentar orang lain yang baru ditandai
            var idx = 0;
            list.innerHTML = items.map(function (k) {
                var isNew = !k.own && (idx++ >= firstNew) && newCount > 0;
                return '<div class="kmt-item' + (k.own ? ' own' : '') + (isNew ? ' new' : '') + '">'
                    + '<div class="kmt-meta"><span class="kmt-author">' + esc(k.fullname || 'Pengguna')
                    + (k.own ? ' <span class="kmt-you">(Anda)</span>' : '') + '</span><span>' + when(k.created_at) + '</span></div>'
                    + '<p class="kmt-text">' + esc(k.komentar) + '</p></div>';
            }).join('');
            list.scrollTop = list.scrollHeight;
        }

        function load() {
            var newCount = unread;
            list.innerHTML = '<div class="kmt-loading"><i class="fas fa-spinner fa-spin"></i> Memuat…</div>';
            return api('daftar?page=' + encodeURIComponent(PAGE)).then(function (j) {
                if (!j.ok) { list.innerHTML = '<div class="kmt-empty text-danger">' + esc(j.message || 'Gagal memuat komentar.') + '</div>'; return; }
                render(j.comments || [], newCount);
                setBadge(0); // sudah dibaca
            }).catch(function () { list.innerHTML = '<div class="kmt-empty text-danger">Gagal memuat komentar.</div>'; });
        }

        // ---- polling badge (berhenti saat tab tersembunyi)
        function poll() {
            clearTimeout(timer);
            if (document.hidden) return;
            api('belumdibaca?page=' + encodeURIComponent(PAGE)).then(function (j) {
                if (!j.ok) return;
                if (!dlg.hidden && j.unread > 0) { unread = j.unread; load(); } // dialog terbuka -> tampilkan langsung
                else setBadge(j.unread || 0);
            }).catch(function () { }).then(function () { timer = setTimeout(poll, POLL); });
        }
        document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); else clearTimeout(timer); });

        // ---- dialog
        var lastFocus = null;
        function open() {
            lastFocus = document.activeElement;
            dlg.hidden = false;
            document.body.style.overflow = 'hidden';
            load();
            setTimeout(function () { text.focus(); }, 50);
        }
        function close() {
            dlg.hidden = true;
            document.body.style.overflow = '';
            (lastFocus || fab).focus();
        }
        fab.addEventListener('click', open);
        el('kmtClose').addEventListener('click', close);
        dlg.addEventListener('click', function (e) { if (e.target === dlg) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !dlg.hidden) close(); });

        // ---- kirim
        text.addEventListener('input', function () { count.textContent = text.value.length; err.hidden = true; });
        text.addEventListener('keydown', function (e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit')); } });
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var val = text.value.trim();
            if (val.length < 3) { err.textContent = 'Komentar minimal 3 karakter.'; err.hidden = false; return; }
            if (busy) return;
            busy = true; send.disabled = true;
            var fd = new FormData();
            fd.append('page', PAGE); fd.append('komentar', val); fd.append(csrf.name, csrf.hash);
            api('kirim', { method: 'POST', body: fd }).then(function (j) {
                if (!j.ok) { err.textContent = j.message || 'Komentar gagal dikirim.'; err.hidden = false; return; }
                text.value = ''; count.textContent = '0';
                render(j.comments || [], 0);
                setBadge(0);
            }).catch(function () {
                err.textContent = 'Koneksi bermasalah, komentar belum terkirim.'; err.hidden = false;
            }).then(function () { busy = false; send.disabled = false; });
        });

        poll();
    })();
</script>
