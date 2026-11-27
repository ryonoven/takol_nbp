<?php

namespace App\Libraries;

use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Models\M_user;
use CodeIgniter\Controller;
use Myth\Auth\Config\Services as AuthServices;

/**
 * Basis halaman laporan semesteran (TKS, Audit Intern): user, grup, periode aktif (wajib milik BPR user), helper JSON & guard aksi POST.
 * Ditempatkan di Libraries (bukan Controllers) agar tidak dapat diakses lewat auto-routing.
 */
abstract class TksBaseController extends Controller
{
    public const MAX_TEXT = 10000;
    /** Halaman pemilihan periode bila periode aktif tidak valid. */
    protected const PERIODE_URL = '/Periodetks';

    /** Faktor TKS (untuk navigasi antar halaman). */
    public const FAKTOR_NAV = ['Tksprofilrisiko' => 'Profil Risiko', 'Tkstatakelola' => 'Tata Kelola',
        'Tksrentabilitas' => 'Rentabilitas', 'Tkspermodalan' => 'Permodalan'];

    protected $auth;
    protected $userId;
    protected $user = [];
    protected $userKodebpr;
    protected $periodeId;
    protected ?array $periode = null;
    protected array $userGroups = ['pe' => false, 'admin' => false, 'dekom' => false, 'direksi' => false, 'direksi2' => false, 'auditor' => false];

    protected M_periodesemesteran $periodeModel;
    protected M_infobpr $infobprModel;

    public function __construct()
    {
        $this->periodeModel = new M_periodesemesteran();
        $this->infobprModel = new M_infobpr();
        $this->auth = service('authentication');

        if ($this->auth->isLoggedIn()) {
            $this->userId = (int) $this->auth->id();
            $this->user = (new M_user())->find($this->userId) ?? [];
            $this->userKodebpr = $this->user['kodebpr'] ?? null;
            $authorize = AuthServices::authorization();
            foreach (array_keys($this->userGroups) as $g) {
                $this->userGroups[$g] = (bool) $authorize->inGroup($g, $this->userId);
            }

            // Periode wajib milik BPR user (cegah akses data BPR lain lewat session active_periode)
            $pid = (int) session('active_periode');
            if ($pid > 0 && $this->userKodebpr) {
                $p = $this->periodeModel->find($pid);
                if ($p && (string) $p['kodebpr'] === (string) $this->userKodebpr) {
                    $this->periodeId = $pid;
                    $this->periode = $p;
                }
            }
        }
    }

    protected function canEdit(): bool
    {
        return $this->userGroups['pe'] || $this->userGroups['admin'];
    }

    protected function canApprove(): bool
    {
        return $this->userGroups['direksi'] || $this->userGroups['admin'];
    }

    protected function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    /** Redirect untuk halaman (GET). Null = lolos. */
    protected function guardPage()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }
        if (!$this->periodeId) {
            return redirect()->to(static::PERIODE_URL)->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    /** Guard untuk aksi JSON. Null = lolos. */
    protected function guardPost(bool $needEdit = true, bool $needPost = true)
    {
        if (!$this->auth->check()) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir, silakan login kembali.']);
        }
        if ($needPost && strtolower((string) $this->request->getMethod()) !== 'post') {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        if (!$this->periodeId) {
            return $this->json(400, ['ok' => false, 'message' => 'Periode aktif tidak valid. Silakan pilih periode kembali.']);
        }
        if ($needEdit && !$this->canEdit()) {
            return $this->json(403, ['ok' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data ini.']);
        }
        return null;
    }

    /** [semester, tahun, semester sebelumnya, tahun sebelumnya] periode aktif. */
    protected function semesters(): array
    {
        $sem = (int) ($this->periode['semester'] ?? 1) === 2 ? 2 : 1;
        $tahun = (int) ($this->periode['tahun'] ?? date('Y'));
        return $sem === 2 ? [2, $tahun, 1, $tahun] : [1, $tahun, 2, $tahun - 1];
    }

    /** ID periode semesteran sebelumnya milik BPR user (0 bila belum ada). */
    protected function prevPeriodeId(): int
    {
        [, , $semB, $tahunB] = $this->semesters();
        $prev = $this->periodeModel->select('id')
            ->where(['kodebpr' => $this->userKodebpr, 'semester' => $semB, 'tahun' => $tahunB])->first();
        return (int) ($prev['id'] ?? 0);
    }

    protected function render(string $view, array $data)
    {
        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view($view, $data)
            . view('templates/v_footer');
    }
}
