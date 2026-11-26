<?php
/**
 * Admin — Detail / kelola user.
 * Variabel: $user, $userGroups (id[]), $groups, $bprs, $logins, $me
 */
$isMe = (int) $user['id'] === (int) $me;
$banned = $user['status'] === 'banned';
$initials = strtoupper(implode('', array_map(static fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($user['fullname'] ?: $user['username'])), 0, 2))));
$fmtDate = static fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '—';
?>
<link rel="stylesheet" href="<?= base_url('asset/css/transparansi.css') ?>?v=<?= @filemtime(FCPATH . 'asset/css/transparansi.css') ?>">
<div id="pu-toast" class="pu-toast" aria-live="polite"></div>

<div class="container-fluid pu">
    <a href="<?= base_url('admin') ?>" class="pu-back"><i class="fas fa-arrow-left"></i> Daftar user</a>

    <header class="pu-head">
        <div class="pu-head-main ud-head">
            <div class="ud-avatar" aria-hidden="true"><?= esc($initials ?: '?') ?></div>
            <div>
                <span class="pu-kicker">Admin · Kelola User<?= $isMe ? ' · Akun Anda' : '' ?></span>
                <h1 class="pu-title"><?= esc($user['fullname'] ?: $user['username']) ?></h1>
                <div class="pu-meta">
                    <span class="pu-bpr">@<?= esc($user['username']) ?></span>
                    <span class="pu-year"><?= esc($user['email']) ?> · Dibuat <?= $fmtDate($user['created_at']) ?></span>
                </div>
            </div>
        </div>
        <div class="pu-status">
            <div class="pu-chip <?= $banned ? '' : 'ok' ?>">
                <i class="fas <?= $banned ? 'fa-ban' : 'fa-check-circle' ?>"></i>
                <div><b><?= $banned ? 'Diblokir' : 'Aktif' ?></b><small><?= $banned ? esc($user['status_message'] ?: 'Tidak dapat login') : 'Dapat login' ?></small></div>
            </div>
        </div>
    </header>

    <div class="row">
        <div class="col-lg-7">
            <!-- Profil, BPR, peran -->
            <form class="pu-card" id="fProfile" autocomplete="off" novalidate>
                <div class="pu-card-head"><h2><i class="fas fa-id-card"></i> Profil, BPR &amp; Peran</h2></div>
                <div class="pu-card-body row">
                    <div class="col-md-12 pu-field" data-f="fullname">
                        <label for="u_fullname">Nama lengkap</label>
                        <input id="u_fullname" name="fullname" class="pu-input" maxlength="255" value="<?= esc($user['fullname']) ?>">
                        <div class="pu-foot"><span class="pu-err" data-msg="fullname"></span></div>
                    </div>
                    <div class="col-md-6 pu-field" data-f="username">
                        <label for="u_username">Username</label>
                        <input id="u_username" name="username" class="pu-input" maxlength="30" value="<?= esc($user['username']) ?>">
                        <div class="pu-foot"><span class="pu-err" data-msg="username"></span></div>
                    </div>
                    <div class="col-md-6 pu-field" data-f="email">
                        <label for="u_email">Email</label>
                        <input id="u_email" name="email" type="email" class="pu-input" maxlength="255" value="<?= esc($user['email']) ?>">
                        <div class="pu-foot"><span class="pu-err" data-msg="email"></span></div>
                    </div>
                    <div class="col-md-12 pu-field" data-f="kodebpr">
                        <label for="u_kodebpr">BPR</label>
                        <select id="u_kodebpr" name="kodebpr" class="pu-input">
                            <option value="">— Pilih BPR —</option>
                            <?php foreach ($bprs as $b): ?>
                                <option value="<?= esc($b['kodebpr']) ?>" <?= $b['kodebpr'] === $user['kodebpr'] ? 'selected' : '' ?>><?= esc($b['kodebpr'] . ($b['namabpr'] ? ' — ' . $b['namabpr'] : '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pu-foot"><span class="pu-err" data-msg="kodebpr"></span></div>
                    </div>
                    <div class="col-md-12 pu-field" data-f="groups">
                        <label>Peran <small class="text-muted">(boleh lebih dari satu)</small></label>
                        <div class="ud-roles">
                            <?php foreach ($groups as $g): $on = in_array((int) $g['id'], $userGroups, true); $lock = $isMe && $g['name'] === 'admin'; ?>
                                <label class="ud-role <?= $on ? 'on' : '' ?>">
                                    <input type="checkbox" name="groups[]" value="<?= (int) $g['id'] ?>" <?= $on ? 'checked' : '' ?> <?= $lock ? 'onclick="return false" title="Tidak dapat menghapus peran admin akun sendiri"' : '' ?>>
                                    <span><b><?= esc($g['description']) ?></b><small><?= esc($g['name']) ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="pu-foot"><span class="pu-err" data-msg="groups"></span></div>
                    </div>
                </div>
                <div class="pu-savebar" style="padding-right:20px">
                    <button type="submit" class="pu-btn pu-btn-primary"><i class="fas fa-save"></i> Simpan perubahan</button>
                    <span class="pu-dirty"><i class="fas fa-info-circle"></i> <span>Perubahan peran langsung berlaku pada halaman berikutnya yang dibuka user.</span></span>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <!-- Password -->
            <form class="pu-card" id="fPass" autocomplete="off" novalidate>
                <div class="pu-card-head"><h2><i class="fas fa-key"></i> Set Password Baru</h2></div>
                <div class="pu-card-body">
                    <div class="pu-field" data-f="password">
                        <label for="u_pass">Password baru</label>
                        <div class="ud-pass">
                            <input id="u_pass" name="password" type="password" class="pu-input" autocomplete="new-password" placeholder="Minimal 8 karakter, huruf & angka">
                            <button type="button" class="pu-btn pu-btn-light pu-btn-icon" id="uGen" title="Buat password acak"><i class="fas fa-dice"></i></button>
                            <button type="button" class="pu-btn pu-btn-light pu-btn-icon" id="uShow" title="Tampilkan"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="pu-foot"><span class="pu-err" data-msg="password"></span></div>
                    </div>
                    <div class="pu-field" data-f="password_confirm">
                        <label for="u_pass2">Ulangi password</label>
                        <input id="u_pass2" name="password_confirm" type="password" class="pu-input" autocomplete="new-password">
                        <div class="pu-foot"><span class="pu-err" data-msg="password_confirm"></span></div>
                    </div>
                    <button type="submit" class="pu-btn pu-btn-primary"><i class="fas fa-key"></i> Simpan password</button>
                </div>
            </form>

            <!-- Status -->
            <section class="pu-card">
                <div class="pu-card-head"><h2><i class="fas fa-user-lock"></i> Status Akun</h2></div>
                <div class="pu-card-body">
                    <?php if ($banned): ?>
                        <p class="small mb-2">Akun ini <b>diblokir</b> dan tidak dapat login.<?= $user['status_message'] ? ' Alasan: ' . esc($user['status_message']) : '' ?></p>
                        <button type="button" class="pu-btn pu-btn-primary" id="uStatus" data-act="unblock"><i class="fas fa-unlock"></i> Buka blokir</button>
                    <?php elseif ($isMe): ?>
                        <p class="small text-muted mb-0">Anda tidak dapat memblokir akun sendiri.</p>
                    <?php else: ?>
                        <p class="small mb-2">Blokir akun bila user sudah tidak bertugas. Data laporan yang pernah diisi tetap tersimpan.</p>
                        <input id="uReason" class="pu-input mb-2" maxlength="255" placeholder="Alasan (opsional), mis. pindah tugas">
                        <button type="button" class="pu-btn pu-btn-danger" id="uStatus" data-act="block"><i class="fas fa-ban"></i> Blokir akun</button>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Riwayat login -->
            <section class="pu-card">
                <div class="pu-card-head"><h2><i class="fas fa-history"></i> Riwayat Login</h2></div>
                <div class="pu-card-body">
                    <?php if (!$logins): ?>
                        <p class="small text-muted">Belum ada riwayat login.</p>
                    <?php else: ?>
                        <ul class="ud-logins">
                            <?php foreach ($logins as $l): ?>
                                <li><i class="fas <?= (int) $l['success'] ? 'fa-check-circle ok' : 'fa-times-circle bad' ?>"></i>
                                    <span><?= $fmtDate($l['date']) ?></span><small><?= esc($l['ip_address']) ?> · <?= (int) $l['success'] ? 'Berhasil' : 'Gagal' ?></small></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <input type="hidden" id="puCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
</div>

<style>
    .ud-head { display: flex; gap: 16px; align-items: center; }
    .ud-avatar { flex: 0 0 64px; height: 64px; border-radius: 18px; background: rgba(255,255,255,.18); color: #fff; font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; }
    .ud-roles { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; }
    .ud-role { display: flex; gap: 10px; align-items: flex-start; border: 1.5px solid var(--bd); border-radius: 10px; padding: 8px 10px; cursor: pointer; margin: 0; }
    .ud-role.on { border-color: #a5b4fc; background: #eef2ff; }
    .ud-role input { margin-top: 3px; }
    .ud-role b { display: block; font-size: .82rem; color: var(--txt); }
    .ud-role small { font-size: .7rem; color: var(--mut); }
    .ud-pass { display: flex; gap: 6px; }
    .ud-logins { list-style: none; margin: 0; padding: 0; font-size: .82rem; }
    .ud-logins li { display: flex; gap: 8px; align-items: baseline; padding: 5px 0; border-bottom: 1px dashed #e2e8f0; }
    .ud-logins small { margin-left: auto; color: var(--mut); }
    .ud-logins .ok { color: #059669; } .ud-logins .bad { color: #dc2626; }
</style>

<script>
    (function () {
        'use strict';
        var BASE = <?= json_encode(rtrim(site_url('admin'), '/')) ?>, ID = <?= (int) $user['id'] ?>;
        var csrf = document.getElementById('puCsrf');
        function toast(msg, err) {
            var d = document.createElement('div'); if (err) d.className = 'e'; d.textContent = msg;
            document.getElementById('pu-toast').appendChild(d); setTimeout(function () { d.remove(); }, 4000);
        }
        function post(path, fd) {
            fd.set(csrf.name, csrf.value);
            return fetch(BASE + '/' + path + '/' + ID, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Respons server tidak valid (' + r.status + ').' }; }); })
                .then(function (j) { if (j.csrf) { csrf.name = j.csrf.name; csrf.value = j.csrf.hash; } return j; });
        }
        function clearErr(f) { f.querySelectorAll('[data-msg]').forEach(function (n) { n.textContent = ''; }); f.querySelectorAll('.err').forEach(function (n) { n.classList.remove('err'); }); }
        function showErr(f, errors) {
            Object.keys(errors || {}).forEach(function (k) {
                var w = f.querySelector('[data-f="' + k + '"]'); if (!w) return;
                w.classList.add('err'); w.querySelector('[data-msg]').textContent = errors[k];
            });
        }
        function submit(form, path, after) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type=submit]'), ori = btn.innerHTML;
                btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
                clearErr(form);
                post(path, new FormData(form)).then(function (j) {
                    toast(j.message || 'Selesai.', !j.ok);
                    if (!j.ok) return showErr(form, j.errors);
                    if (after) after();
                }).catch(function () { toast('Koneksi bermasalah.', true); })
                    .then(function () { btn.disabled = false; btn.innerHTML = ori; });
            });
        }
        var fp = document.getElementById('fProfile');
        submit(fp, 'save', function () { setTimeout(function () { location.reload(); }, 800); });
        fp.addEventListener('change', function (e) { if (e.target.name === 'groups[]') e.target.closest('.ud-role').classList.toggle('on', e.target.checked); });
        submit(document.getElementById('fPass'), 'password', function () { document.getElementById('fPass').reset(); });

        document.getElementById('uShow').addEventListener('click', function () {
            ['u_pass', 'u_pass2'].forEach(function (id) { var el = document.getElementById(id); el.type = el.type === 'password' ? 'text' : 'password'; });
        });
        document.getElementById('uGen').addEventListener('click', function () {
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789', a = new Uint32Array(12), p = '';
            (window.crypto || window.msCrypto).getRandomValues(a);
            for (var i = 0; i < a.length; i++) p += chars[a[i] % chars.length];
            p = p.slice(0, 10) + (a[0] % 10) + chars[a[1] % 24];   // pastikan ada angka & huruf
            ['u_pass', 'u_pass2'].forEach(function (id) { var el = document.getElementById(id); el.value = p; el.type = 'text'; });
        });

        var st = document.getElementById('uStatus');
        if (st) st.addEventListener('click', function () {
            var block = st.dataset.act === 'block';
            if (!confirm(block ? 'Blokir akun ini? User tidak akan dapat login.' : 'Buka blokir akun ini?')) return;
            var fd = new FormData(); fd.set('act', st.dataset.act);
            var r = document.getElementById('uReason'); if (r) fd.set('reason', r.value);
            st.disabled = true;
            post('status', fd).then(function (j) { toast(j.message || 'Selesai.', !j.ok); if (j.ok) setTimeout(function () { location.reload(); }, 800); else st.disabled = false; })
                .catch(function () { toast('Koneksi bermasalah.', true); st.disabled = false; });
        });
    })();
</script>
