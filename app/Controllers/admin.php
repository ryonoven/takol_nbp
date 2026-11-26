<?php

namespace App\Controllers;

use App\Models\M_adminuser;
use CodeIgniter\Controller;
use Myth\Auth\Password;

/**
 * Manajemen user (khusus admin).
 *   GET  admin                      daftar user
 *   GET  admin/(:num)               detail user (route: Admin::detail)
 *   POST admin/save/{id}            ubah profil, BPR, peran
 *   POST admin/password/{id}        set password baru
 *   POST admin/status/{id}          act=block|unblock
 *   POST admin/cleanroles           hapus baris peran ganda
 */
class Admin extends Controller
{
    protected $auth;
    protected $model;
    protected int $me = 0;

    public function __construct()
    {
        $this->auth = service('authentication');
        $this->model = new M_adminuser();

        if (!$this->auth->check() || !service('authorization')->inGroup('admin', (int) $this->auth->id())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $this->me = (int) $this->auth->id();
    }

    // ------------------------------------------------------------------ helpers

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function isPost(): bool
    {
        return strtolower((string) $this->request->getMethod()) === 'post';
    }

    /** Hapus cache user & peran (Myth Auth + controller lama) agar perubahan langsung berlaku. */
    private function clearUserCache(int $id, array $groupIds = []): void
    {
        $c = service('cache');
        foreach (["{$id}_groups", "{$id}_permissions", "user_data_{$id}", "user_groups_{$id}"] as $k) {
            $c->delete($k);
        }
        foreach ($groupIds as $g) {
            $c->delete("{$g}_users");
        }
    }

    private function page(string $view, array $data)
    {
        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view($view, $data)
            . view('templates/v_footer');
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        $users = $this->model->listUsers();
        $groups = $this->model->groups();

        return $this->page('admin/index', [
            'judul' => 'Daftar User',
            'users' => $users,
            'groups' => array_column($groups, 'description', 'name'),
            'bprs' => array_column($this->model->bprs(), 'namabpr', 'kodebpr'),
            'warnings' => $this->model->roleWarnings($users),
            'dupRoles' => $this->model->duplicateRoleRows(),
            'me' => $this->me,
        ]);
    }

    public function detail($id = 0)
    {
        $user = $this->model->findUser((int) $id);
        if (!$user) {
            return redirect()->to('/admin')->with('error', 'User tidak ditemukan.');
        }

        return $this->page('admin/detail', [
            'judul' => 'Detail User',
            'user' => $user,
            'userGroups' => $this->model->userGroupIds((int) $id),
            'groups' => $this->model->groups(),
            'bprs' => $this->model->bprs(),
            'logins' => $this->model->loginHistory((int) $id),
            'me' => $this->me,
        ]);
    }

    // ------------------------------------------------------------------ aksi

    public function save($id = 0)
    {
        if (!$this->isPost()) {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        $id = (int) $id;
        $user = $this->model->findUser($id);
        if (!$user) {
            return $this->json(404, ['ok' => false, 'message' => 'User tidak ditemukan.']);
        }

        $in = [
            'fullname' => trim((string) $this->request->getPost('fullname')),
            'username' => trim((string) $this->request->getPost('username')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'kodebpr' => trim((string) $this->request->getPost('kodebpr')),
        ];
        $groupIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('groups'))));
        $validGroups = array_column($this->model->groups(), 'name', 'id');
        $bprs = array_column($this->model->bprs(), 'kodebpr');

        $errors = [];
        if ($in['fullname'] === '' || mb_strlen($in['fullname']) > 255) {
            $errors['fullname'] = 'Nama lengkap wajib diisi (maks. 255 karakter).';
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $in['username'])) {
            $errors['username'] = 'Username 3–30 karakter: huruf, angka, titik, minus, garis bawah.';
        } elseif ($this->model->isTaken('username', $in['username'], $id)) {
            $errors['username'] = 'Username sudah dipakai user lain.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif ($this->model->isTaken('email', $in['email'], $id)) {
            $errors['email'] = 'Email sudah dipakai user lain.';
        }
        if (!in_array($in['kodebpr'], $bprs, true)) {
            $errors['kodebpr'] = 'Pilih BPR.';
        }
        if (!$groupIds || array_diff($groupIds, array_keys($validGroups))) {
            $errors['groups'] = 'Pilih minimal satu peran.';
        } elseif ($id === $this->me && !in_array('admin', array_map(static fn($g) => $validGroups[$g], $groupIds), true)) {
            $errors['groups'] = 'Anda tidak dapat menghapus peran admin dari akun Anda sendiri.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $oldGroups = $this->model->userGroupIds($id);
        $db = db_connect();
        $db->transStart();
        $this->model->updateProfile($id, $in);
        $this->model->setGroups($id, $groupIds);
        $db->transComplete();
        if (!$db->transStatus()) {
            log_message('error', 'Admin::save transaksi gagal untuk user ' . $id);
            return $this->json(500, ['ok' => false, 'message' => 'Data gagal disimpan.']);
        }
        $this->clearUserCache($id, array_merge($oldGroups, $groupIds));
        log_message('notice', "Admin {$this->me} mengubah user {$id}: kodebpr={$in['kodebpr']} peran=" . implode(',', $groupIds));

        return $this->json(200, ['ok' => true, 'message' => 'Data user disimpan.']);
    }

    public function password($id = 0)
    {
        if (!$this->isPost()) {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        $id = (int) $id;
        if (!$this->model->findUser($id)) {
            return $this->json(404, ['ok' => false, 'message' => 'User tidak ditemukan.']);
        }
        $pass = (string) $this->request->getPost('password');
        $conf = (string) $this->request->getPost('password_confirm');
        $min = max(8, (int) (config(\Myth\Auth\Config\Auth::class)->minimumPasswordLength ?? 8));

        $errors = [];
        if (mb_strlen($pass) < $min) {
            $errors['password'] = "Password minimal {$min} karakter.";
        } elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
            $errors['password'] = 'Password harus mengandung huruf dan angka.';
        }
        if ($pass !== $conf) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }
        if ($errors) {
            return $this->json(422, ['ok' => false, 'message' => 'Periksa kembali isian yang ditandai.', 'errors' => $errors]);
        }

        $ok = $this->model->setPasswordHash($id, Password::hash($pass));
        log_message('notice', "Admin {$this->me} mengganti password user {$id}");
        return $ok
            ? $this->json(200, ['ok' => true, 'message' => 'Password baru disimpan. Sampaikan ke user secara langsung.'])
            : $this->json(500, ['ok' => false, 'message' => 'Password gagal disimpan.']);
    }

    public function status($id = 0)
    {
        if (!$this->isPost()) {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        $id = (int) $id;
        if (!$this->model->findUser($id)) {
            return $this->json(404, ['ok' => false, 'message' => 'User tidak ditemukan.']);
        }
        $act = (string) $this->request->getPost('act');
        if ($act === 'block' && $id === $this->me) {
            return $this->json(422, ['ok' => false, 'message' => 'Anda tidak dapat memblokir akun sendiri.']);
        }
        if (!in_array($act, ['block', 'unblock'], true)) {
            return $this->json(422, ['ok' => false, 'message' => 'Permintaan tidak valid.']);
        }
        $reason = mb_substr(trim((string) $this->request->getPost('reason')), 0, 255);
        $ok = $this->model->setBanned($id, $act === 'block', $reason !== '' ? $reason : 'Diblokir oleh admin');
        $this->clearUserCache($id);
        log_message('notice', "Admin {$this->me} {$act} user {$id}");

        return $ok
            ? $this->json(200, ['ok' => true, 'message' => $act === 'block' ? 'User diblokir — tidak dapat login.' : 'Blokir dibuka — user dapat login kembali.'])
            : $this->json(500, ['ok' => false, 'message' => 'Gagal memproses permintaan.']);
    }

    public function cleanroles()
    {
        if (!$this->isPost()) {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        $n = $this->model->cleanDuplicateRoles();
        log_message('notice', "Admin {$this->me} merapikan {$n} baris peran ganda");
        return $this->json(200, ['ok' => true, 'message' => $n ? "{$n} baris peran ganda dihapus." : 'Tidak ada baris peran ganda.']);
    }
}
