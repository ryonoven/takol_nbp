/**
 * Export ZIP dengan loading bar — dipakai bersama (Showtahunan, Showsaf), di-cache browser.
 *
 * Pemakaian:
 *   <a href="/Url/export" data-export-zip data-stages='[[0,"Memeriksa…"],[40,"Membuat PDF…"]]'>EXPORT</a>
 *   <script src="/asset/js/export-zip.js" defer></script>
 *
 * Tahap server tidak bisa diukur -> progres estimasi hingga 90%; tahap unduh memakai Content-Length (90% -> 100%).
 * Server menolak (redirect) -> halaman dimuat ulang agar pesan flash tampil.
 */
(function () {
    'use strict';
    if (!window.fetch || !window.Blob || !window.URL) return; // fallback: link biasa

    var CSS = ''
        + '.ez-box{max-width:520px;margin:20px auto 0;text-align:left;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:14px 18px;box-shadow:0 2px 12px rgba(0,0,0,.08)}'
        + '.ez-box[hidden]{display:none}'
        + '.ez-head{display:flex;justify-content:space-between;gap:12px;font-size:.88rem;font-weight:700;color:#1e293b;margin-bottom:8px}'
        + '.ez-pct{font-variant-numeric:tabular-nums;color:#1f2aa8}'
        + '.ez-track{height:12px;background:#e2e8f0;border-radius:999px;overflow:hidden}'
        + '.ez-fill{height:100%;width:0;border-radius:999px;background:linear-gradient(135deg,#1f2aa8,#141863);transition:width .25s ease;position:relative;overflow:hidden}'
        + '.ez-fill:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);animation:ezShine 1.2s linear infinite}'
        + '@keyframes ezShine{from{transform:translateX(-100%)}to{transform:translateX(100%)}}'
        + '.ez-note{font-size:.78rem;color:#475569;margin-top:8px;word-break:break-all}'
        + '.ez-box.done .ez-fill{background:linear-gradient(135deg,#059669,#10b981)}.ez-box.done .ez-stage{color:#059669}'
        + '.ez-box.error .ez-fill{background:#ef4444}.ez-box.error .ez-stage{color:#dc2626}'
        + '.ez-box.done .ez-fill:after,.ez-box.error .ez-fill:after{display:none}'
        + '[data-export-zip].busy{opacity:.75;pointer-events:none}'
        + '@media (prefers-reduced-motion:reduce){.ez-fill,.ez-fill:after{transition:none;animation:none}}';

    function injectCss() {
        if (document.getElementById('ez-css')) return;
        var st = document.createElement('style');
        st.id = 'ez-css';
        st.textContent = CSS;
        document.head.appendChild(st);
    }

    function setup(btn) {
        var stages = [[0, 'Menyiapkan file…'], [85, 'Mengemas file ZIP…']];
        try { if (btn.dataset.stages) stages = JSON.parse(btn.dataset.stages); } catch (e) { }

        var box = document.createElement('div');
        box.className = 'ez-box';
        box.hidden = true;
        box.setAttribute('aria-live', 'polite');
        box.innerHTML = '<div class="ez-head"><span class="ez-stage"></span><span class="ez-pct">0%</span></div>'
            + '<div class="ez-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="ez-fill"></div></div>'
            + '<div class="ez-note"></div>';
        btn.insertAdjacentElement('afterend', box);

        var fill = box.querySelector('.ez-fill'), track = box.querySelector('.ez-track'), pctEl = box.querySelector('.ez-pct'),
            stage = box.querySelector('.ez-stage'), note = box.querySelector('.ez-note'), label = btn.innerHTML, timer = null;

        function setPct(p) {
            p = Math.max(0, Math.min(100, p));
            fill.style.width = p + '%';
            pctEl.textContent = Math.floor(p) + '%';
            track.setAttribute('aria-valuenow', Math.floor(p));
        }
        function setStage(p) {
            for (var i = stages.length - 1; i >= 0; i--) if (p >= stages[i][0]) { stage.textContent = stages[i][1]; return; }
        }
        function reset() { clearInterval(timer); btn.innerHTML = label; btn.classList.remove('busy'); }
        function fail(msg) { reset(); box.classList.add('error'); stage.textContent = 'Export gagal'; note.textContent = msg; }
        function fileNameFrom(res) {
            var m = /filename\*?=(?:UTF-8'')?["']?([^"';]+)["']?/i.exec(res.headers.get('Content-Disposition') || '');
            return m ? decodeURIComponent(m[1]) : 'laporan.zip';
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (btn.classList.contains('busy')) return;
            btn.classList.add('busy');
            btn.textContent = 'Sedang menyiapkan file…';
            box.hidden = false;
            box.classList.remove('error', 'done');
            note.textContent = 'Mohon tunggu, jangan tutup atau muat ulang halaman ini.';

            var p = 0;
            setPct(0); setStage(0);
            timer = setInterval(function () { p += (90 - p) * 0.035; setPct(p); setStage(p); }, 250);

            fetch(btn.href, { credentials: 'same-origin', redirect: 'manual' })
                .then(function (res) {
                    if (res.type === 'opaqueredirect') {           // ditolak server -> tampilkan pesan flash
                        clearInterval(timer);
                        stage.textContent = 'Memuat ulang halaman…';
                        window.location.reload();
                        return null;
                    }
                    if (!res.ok) throw new Error('Server error (HTTP ' + res.status + ').');
                    if ((res.headers.get('Content-Type') || '').indexOf('application/zip') === -1) throw new Error('Respons server bukan file ZIP.');

                    clearInterval(timer);
                    var name = fileNameFrom(res), total = parseInt(res.headers.get('Content-Length') || '0', 10);
                    stage.textContent = 'Mengunduh file ZIP…';
                    setPct(90);
                    if (!res.body || !res.body.getReader || !total) return res.blob().then(function (b) { return [b, name]; });

                    var reader = res.body.getReader(), chunks = [], got = 0;
                    return (function pump() {
                        return reader.read().then(function (r) {
                            if (r.done) return [new Blob(chunks, { type: 'application/zip' }), name];
                            chunks.push(r.value);
                            got += r.value.length;
                            setPct(90 + (got / total) * 10);
                            return pump();
                        });
                    })();
                })
                .then(function (out) {
                    if (!out) return;
                    var url = URL.createObjectURL(out[0]), a = document.createElement('a');
                    a.href = url;
                    a.download = out[1];
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
                    setPct(100);
                    box.classList.add('done');
                    stage.textContent = 'Selesai — file ZIP berhasil diunduh';
                    note.textContent = out[1];
                    reset();
                })
                .catch(function (err) { fail((err && err.message ? err.message + ' ' : '') + 'Silakan coba lagi.'); });
        });
    }

    var links = document.querySelectorAll('a[data-export-zip]');
    if (links.length) {
        injectCss();
        links.forEach(setup);
    }
})();
