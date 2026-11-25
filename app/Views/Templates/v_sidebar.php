<?php
/**
 * Sidebar aplikasi.
 *  - Desktop (>= 992px): tetap di kiri; bisa diciutkan jadi ikon saja (tombol di topbar, tersimpan di browser).
 *  - Mobile/tablet: laci (off-canvas) + overlay; tutup dengan overlay, tombol ×, Esc, atau klik menu.
 * Menu aktif ditentukan di server (segmen URL pertama), tanpa loop JS.
 * Class sengaja "app-sb" (bukan ".sidebar") agar tidak diubah oleh sb-admin-2.min.js.
 */
use App\Models\M_showtahunan;
use App\Models\M_showtransparansi;

$seg = strtolower((string) (service('request')->getUri()->getSegment(1) ?? ''));

// Halaman turunan -> menu induk yang disorot
$groups = [
    'periodetahunan' => array_merge(
        ['periodetahunan', 'showtahunan', 'showtransparansi', 'komentar'],
        array_map('strtolower', array_filter(array_column(M_showtahunan::SECTIONS, 'link'))),
        array_map('strtolower', array_column(M_showtransparansi::SECTIONS, 'controller'))
    ),
    'periodesemesteran' => ['periodesemesteran', 'showsemesteran'],
    'periodesaf' => ['periodesaf', 'showsaf', 'laporanpdfsaf'],
];
$isActive = static function (string $key) use ($seg, $groups): bool {
    $key = strtolower($key);
    return $seg === $key || in_array($seg, $groups[$key] ?? [], true) || ($key === 'home' && $seg === '');
};

$menu = [
    ['heading' => null, 'items' => [
        ['Home', 'fa-house-user', 'Dashboard'],
    ]],
    ['heading' => 'Manajemen User', 'admin' => true, 'items' => [
        ['admin', 'fa-users', 'Daftar User'],
    ]],
    ['heading' => 'Pelaporan', 'items' => [
        ['infobpr', 'fa-laptop-house', 'Informasi BPR'],
        ['Periodesemesteran', 'fa-clipboard-check', 'Laporan Semesteran'],
        ['Periodetahunan', 'fa-chart-line', 'Laporan Tahunan'],
        ['Periodesaf', 'fa-user-secret', 'Laporan SAF'],
    ]],
];
$isAdmin = in_groups('admin');
?>
<div class="app-sb-overlay" id="appSbOverlay" hidden></div>

<aside class="app-sb" id="appSidebar" aria-label="Navigasi utama">
    <div class="app-sb-brand">
        <a href="<?= base_url('Home') ?>" class="app-sb-logo" title="NBP Simpel">
            <img src="/asset/img/logo/logo.png" alt="NBP Simpel" width="140" height="48" decoding="async">
        </a>
        <button type="button" class="app-sb-close" id="appSbClose" aria-label="Tutup menu"><i class="fas fa-times"></i></button>
    </div>

    <nav class="app-sb-nav">
        <?php foreach ($menu as $group):
            if (!empty($group['admin']) && !$isAdmin) {
                continue;
            } ?>
            <?php if ($group['heading']): ?>
                <div class="app-sb-heading"><span><?= esc($group['heading']) ?></span></div>
            <?php endif; ?>
            <ul>
                <?php foreach ($group['items'] as [$link, $icon, $label]):
                    $active = $isActive($link); ?>
                    <li>
                        <a href="<?= base_url($link) ?>" class="app-sb-link<?= $active ? ' is-active' : '' ?>"
                            title="<?= esc($label, 'attr') ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                            <i class="fas <?= $icon ?>" aria-hidden="true"></i>
                            <span><?= esc($label) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <div class="app-sb-foot">
        <a href="#" class="app-sb-link app-sb-logout" data-toggle="modal" data-target="#logoutModal" title="Logout">
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i><span>Logout</span>
        </a>
    </div>
</aside>

<style>
    :root { --sb-w: 250px; --sb-w-mini: 76px; --tb-h: 64px; --brand: #1f2aa8; --brand-d: #141863; }

    /* Ruang konten mengikuti lebar sidebar (desktop) */
    #wrapper { padding-left: var(--sb-w); transition: padding-left .2s ease; min-width: 0; }
    #wrapper #content-wrapper { min-width: 0; }
    body.sb-mini #wrapper { padding-left: var(--sb-w-mini); }

    .app-sb {
        position: fixed; inset: 0 auto 0 0; z-index: 1040; width: var(--sb-w);
        display: flex; flex-direction: column; background: #fff; border-right: 1px solid #e5e7eb;
        transition: width .2s ease, transform .25s ease; will-change: transform;
    }
    .app-sb-brand { height: var(--tb-h); display: flex; align-items: center; justify-content: center; padding: 0 16px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; position: relative; }
    .app-sb-logo img { max-width: 140px; max-height: 48px; width: auto; height: auto; display: block; }
    .app-sb-close { display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border: none; border-radius: 8px; background: transparent; color: #64748b; }
    .app-sb-close:hover { background: #f1f5f9; }

    .app-sb-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 12px; overscroll-behavior: contain; }
    .app-sb-nav ul { list-style: none; margin: 0 0 6px; padding: 0; }
    .app-sb-heading { margin: 14px 10px 6px; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; white-space: nowrap; }

    .app-sb-link {
        display: flex; align-items: center; gap: 12px; min-height: 44px; padding: 10px 12px; margin-bottom: 2px;
        border-radius: 10px; color: #334155; font-size: 14.5px; font-weight: 500; text-decoration: none; white-space: nowrap;
        transition: background-color .15s, color .15s;
    }
    .app-sb-link i { width: 20px; text-align: center; font-size: 16px; color: #64748b; flex-shrink: 0; transition: color .15s; }
    .app-sb-link:hover { background: #eef2ff; color: var(--brand); text-decoration: none; }
    .app-sb-link:hover i { color: var(--brand); }
    .app-sb-link:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
    .app-sb-link.is-active { background: linear-gradient(135deg, var(--brand), var(--brand-d)); color: #fff; box-shadow: 0 4px 12px rgba(31, 42, 168, .25); }
    .app-sb-link.is-active i { color: #fff; }

    .app-sb-foot { padding: 12px; border-top: 1px solid #f1f5f9; flex-shrink: 0; }
    .app-sb-logout:hover { background: #fef2f2; color: #b91c1c; }
    .app-sb-logout:hover i { color: #b91c1c; }

    .app-sb-nav::-webkit-scrollbar { width: 5px; }
    .app-sb-nav::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    /* Desktop: mode ringkas (ikon saja) */
    @media (min-width: 992px) {
        body.sb-mini .app-sb { width: var(--sb-w-mini); }
        body.sb-mini .app-sb-link { justify-content: center; padding: 10px; }
        body.sb-mini .app-sb-link span, body.sb-mini .app-sb-heading span { display: none; }
        body.sb-mini .app-sb-heading { height: 1px; margin: 12px 8px; background: #e5e7eb; }
        body.sb-mini .app-sb-logo img { max-width: 44px; object-fit: cover; object-position: left; }
    }

    /* Mobile & tablet: laci off-canvas */
    @media (max-width: 991.98px) {
        #wrapper, body.sb-mini #wrapper { padding-left: 0; }
        .app-sb { width: min(280px, 85vw); transform: translateX(-100%); box-shadow: none; }
        body.sb-open .app-sb { transform: none; box-shadow: 8px 0 30px rgba(15, 23, 42, .2); }
        body.sb-open { overflow: hidden; }
        .app-sb-close { display: inline-flex; align-items: center; justify-content: center; }
        .app-sb-overlay { position: fixed; inset: 0; z-index: 1035; background: rgba(15, 23, 42, .45); }
        .app-sb-overlay[hidden] { display: none; }
    }

    @media (prefers-reduced-motion: reduce) { .app-sb, #wrapper { transition: none; } }
    @media print { .app-sb, .app-sb-overlay { display: none !important; } #wrapper { padding-left: 0 !important; } }
</style>
