<?php
/**
 * Topbar: tombol menu (mobile: buka laci, desktop: ciutkan sidebar), judul halaman, menu user.
 */
$u = user();
$fullname = trim((string) ($u->fullname ?? '')) ?: (string) ($u->username ?? 'Pengguna');
$initials = strtoupper(implode('', array_map(static fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', $fullname), 0, 2))));
// Label peran (grup Myth\Auth; pengecekan grup memakai cache Myth, tanpa query tambahan)
$roles = [
    'admin' => 'Admin', 'direksi' => 'Direktur Utama', 'direksi2' => 'Direksi', 'dekom' => 'Komisaris Utama',
    'dekom2' => 'Komisaris', 'dekom3' => 'Komisaris', 'dekom4' => 'Komisaris', 'dekom5' => 'Komisaris',
    'pe' => 'Pejabat Eksekutif', 'auditor' => 'Auditor', 'user' => 'Karyawan',
];
$role = '';
foreach ($roles as $g => $label) {
    if (in_groups($g)) {
        $role = $label;
        break;
    }
}
?>
<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <header class="app-tb" id="appTopbar">
            <button type="button" class="app-tb-btn" id="appSbToggle" aria-controls="appSidebar" aria-expanded="false" aria-label="Buka/tutup menu">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>

            <div class="app-tb-title" title="<?= esc($judul ?? '', 'attr') ?>"><?= esc($judul ?? '') ?></div>

            <div class="dropdown app-tb-user">
                <button type="button" class="app-tb-userbtn" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="app-tb-avatar" aria-hidden="true"><?= esc($initials) ?></span>
                    <span class="app-tb-who">
                        <span class="app-tb-name"><?= esc($fullname) ?></span>
                        <?php if ($role): ?><span class="app-tb-role"><?= esc($role) ?></span><?php endif; ?>
                    </span>
                    <i class="fas fa-chevron-down app-tb-caret" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right app-tb-menu" aria-labelledby="userDropdown">
                    <div class="app-tb-menuhead">
                        <strong><?= esc($fullname) ?></strong>
                        <?php if ($role): ?><small><?= esc($role) ?></small><?php endif; ?>
                    </div>
                    <a class="dropdown-item" href="<?= base_url('user') ?>"><i class="fas fa-user fa-fw"></i> Profil</a>
                    <a class="dropdown-item" href="<?= base_url('infobpr') ?>"><i class="fas fa-laptop-house fa-fw"></i> Informasi BPR</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item app-tb-logout" href="#" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a>
                </div>
            </div>
        </header>

        <style>
            .app-tb {
                position: sticky; top: 0; z-index: 1030; height: var(--tb-h, 64px); margin-bottom: 1rem;
                display: flex; align-items: center; gap: 12px; padding: 0 20px;
                background: rgba(255, 255, 255, .92); border-bottom: 1px solid #e5e7eb;
                -webkit-backdrop-filter: saturate(180%) blur(8px); backdrop-filter: saturate(180%) blur(8px);
            }
            .app-tb-btn {
                width: 40px; height: 40px; flex-shrink: 0; border: none; border-radius: 10px; background: transparent;
                color: #475569; font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center; transition: background-color .15s;
            }
            .app-tb-btn:hover { background: #f1f5f9; color: #1f2aa8; }
            .app-tb-btn:focus-visible, .app-tb-userbtn:focus-visible { outline: 2px solid #1f2aa8; outline-offset: 2px; }
            .app-tb-title { flex: 1; min-width: 0; font-weight: 700; font-size: 1.05rem; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            .app-tb-userbtn {
                display: flex; align-items: center; gap: 10px; padding: 4px 8px 4px 4px; border: 1px solid transparent; border-radius: 999px;
                background: transparent; transition: background-color .15s, border-color .15s; max-width: 280px;
            }
            .app-tb-userbtn:hover, .app-tb-user.show .app-tb-userbtn { background: #f8fafc; border-color: #e2e8f0; }
            .app-tb-avatar {
                width: 36px; height: 36px; flex-shrink: 0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
                background: linear-gradient(135deg, #1f2aa8, #141863); color: #fff; font-weight: 700; font-size: .85rem; letter-spacing: .02em;
            }
            .app-tb-who { display: flex; flex-direction: column; align-items: flex-start; min-width: 0; line-height: 1.2; }
            .app-tb-name { font-size: .9rem; font-weight: 600; color: #1e293b; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .app-tb-role { font-size: .72rem; color: #64748b; }
            .app-tb-caret { font-size: .7rem; color: #94a3b8; }

            .app-tb-menu { min-width: 230px; margin-top: 8px !important; padding: 6px; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 12px 30px rgba(15, 23, 42, .12); }
            .app-tb-menuhead { padding: 8px 12px 10px; border-bottom: 1px solid #f1f5f9; margin-bottom: 4px; display: flex; flex-direction: column; }
            .app-tb-menuhead strong { font-size: .9rem; color: #1e293b; }
            .app-tb-menuhead small { color: #64748b; }
            .app-tb-menu .dropdown-item { border-radius: 8px; padding: 9px 12px; font-size: .88rem; color: #334155; }
            .app-tb-menu .dropdown-item i { color: #94a3b8; margin-right: 6px; }
            .app-tb-menu .dropdown-item:hover { background: #eef2ff; color: #1f2aa8; }
            .app-tb-menu .dropdown-item:hover i { color: #1f2aa8; }
            .app-tb-menu .app-tb-logout:hover { background: #fef2f2; color: #b91c1c; }
            .app-tb-menu .app-tb-logout:hover i { color: #b91c1c; }

            @media (max-width: 575.98px) {
                .app-tb { padding: 0 12px; gap: 8px; }
                .app-tb-who, .app-tb-caret { display: none; }
                .app-tb-userbtn { padding: 2px; }
                .app-tb-title { font-size: .95rem; }
            }
            @media print { .app-tb { display: none !important; } }
        </style>
