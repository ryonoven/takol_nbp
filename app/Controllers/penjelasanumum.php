<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_penjelasanumum;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use Myth\Auth\Config\Services as AuthServices;

class Penjelasanumum extends Controller
{
    protected $auth;
    protected $session;
    protected $userKodebpr;
    protected $userId;

    protected $penjelasanModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $komentarModel;
    protected $commentReadsModel;

    private $userPermissions = null;
    private $userData = null;

    const KATEGORI = 'TRANSPARANSI';
    const SUBKATEGORI = 'Penjelasanumum';

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->session = service('session');
        $this->auth = service('authentication');

        if ($this->auth->check()) {
            $this->userId = $this->auth->id();
            $this->loadUserData();
        }
    }

    private function getPenjelasanModel(): M_penjelasanumum
    {
        return $this->penjelasanModel ??= new M_penjelasanumum();
    }
    private function getUserModel(): M_user
    {
        return $this->userModel ??= new M_user();
    }
    private function getPeriodeModel(): M_periodetahunan
    {
        return $this->periodeModel ??= new M_periodetahunan();
    }
    private function getKomentarModel(): M_transparansicomments
    {
        return $this->komentarModel ??= new M_transparansicomments();
    }
    private function getCommentReadsModel(): M_transparansicommentsread
    {
        return $this->commentReadsModel ??= new M_transparansicommentsread();
    }
    private function getInfobprModel(): M_infobpr
    {
        return $this->infobprModel ??= new M_infobpr();
    }

    private function loadUserData(): void
    {
        if ($this->userData === null && $this->userId) {
            $this->userData = $this->getUserModel()->find($this->userId);
            $this->userKodebpr = $this->userData['kodebpr'] ?? null;
        }
    }

    private function getUserPermissions(): array
    {
        if ($this->userPermissions === null && $this->userId) {
            $authorize = AuthServices::authorization();
            $uid = $this->userId;
            foreach (['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'] as $g) {
                $this->userPermissions[$g] = $authorize->inGroup($g, $uid);
            }
        }
        return $this->userPermissions ?? [];
    }

    private function getKodebprPeriode(): array
    {
        return [$this->userKodebpr, (int) session('active_periode')];
    }

    private function jsonOk(string $msg, array $extra = [])
    {
        return $this->response->setJSON(array_merge(
            ['status' => 'success', 'message' => $msg, 'csrf_hash' => csrf_hash()],
            $extra
        ));
    }

    private function jsonErr(string $msg, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status' => 'error',
            'message' => $msg,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    private function requireAjax()
    {
        if (!$this->request->isAJAX())
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        return null;
    }

    private function requireAuth()
    {
        if (!$this->auth->check())
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        return null;
    }

    private function requireEditor()
    {
        $p = $this->getUserPermissions();
        if (!($p['pe'] ?? false) && !($p['admin'] ?? false)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengubah data ini',
            ]);
        }
        return null;
    }

    private function requireDekom()
    {
        $p = $this->getUserPermissions();
        if (!($p['admin'] ?? false) && !($p['dekom'] ?? false)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Tidak memiliki akses']);
        }
        return null;
    }

    private function requireDireksi()
    {
        $p = $this->getUserPermissions();
        if (!($p['admin'] ?? false) && !($p['direksi'] ?? false)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Tidak memiliki akses']);
        }
        return null;
    }

    public function index()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');

        if (!session('active_periode'))
            return redirect()->to('/Periodetransparansi');

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        if (!$kodebpr)
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');

        $perms = $this->getUserPermissions();

        $penjelasanData = $this->getPenjelasanModel()
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->findAll();

        $allAccdekomApproved = !empty($penjelasanData)
            && array_reduce($penjelasanData, fn($c, $r) => $c && (int) $r['accdekom'] === 1, true);

        $allAccdirutApproved = !empty($penjelasanData)
            && array_reduce($penjelasanData, fn($c, $r) => $c && (int) $r['is_approved'] === 1, true);

        $accdekomData = array_map(fn($r) => [
            'accdekom' => $r['accdekom'],
            'accdekom_at' => $r['accdekom_at'] ?? null,
            'komut' => $r['komut'] ?? null,
        ], $penjelasanData);

        $accdirutData = array_map(fn($r) => [
            'is_approved' => $r['is_approved'],
            'approved_at' => $r['approved_at'] ?? null,
            'dirut' => $r['dirut'] ?? null,
        ], $penjelasanData);

        $data = [
            'judul' => '1. Penjelasan Umum',
            'penjelasanumum' => $penjelasanData,
            'userId' => $this->userId,
            'userInGroupPE' => $perms['pe'] ?? false,
            'userInGroupAdmin' => $perms['admin'] ?? false,
            'userInGroupDekom' => $perms['dekom'] ?? false,
            'userInGroupDekom2' => $perms['dekom2'] ?? false,
            'userInGroupDekom3' => $perms['dekom3'] ?? false,
            'userInGroupDekom4' => $perms['dekom4'] ?? false,
            'userInGroupDekom5' => $perms['dekom5'] ?? false,
            'userInGroupDireksi' => $perms['direksi'] ?? false,
            'userInGroupDireksi2' => $perms['direksi2'] ?? false,
            'fullname' => $this->userData['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $this->getPeriodeModel()->getPeriodeDetail($periodeId),
            'bprData' => $this->getInfobprModel()->getBprByKode($kodebpr),
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'canApprove' => $allAccdekomApproved,
            'canEdit' => (($perms['pe'] ?? false) || ($perms['admin'] ?? false)),
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/penjelasanumum', $data);
        echo view('templates/v_footer');
    }

    public function tambahpenjelasAjax()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireEditor())
            return $e;

        $val = $this->validate([
            'namabpr' => ['label' => 'Nama BPR', 'rules' => 'required'],
            'alamat' => ['label' => 'Alamat', 'rules' => 'required'],
            'nomor' => ['label' => 'Nomor', 'rules' => 'required'],
            'penjelasan' => ['label' => 'Penjelasan', 'rules' => 'required'],
            'peringkatkomposit' => ['label' => 'Peringkat Komposit', 'rules' => 'required'],
            'penjelasankomposit' => ['label' => 'Penjelasan Komposit', 'rules' => 'required'],
        ]);

        if (!$val)
            return $this->jsonErr(service('validation')->listErrors());

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $existing = $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->first();

            $rowData = [
                'namabpr' => trim($this->request->getPost('namabpr')),
                'alamat' => trim($this->request->getPost('alamat')),
                'nomor' => trim($this->request->getPost('nomor')),
                'penjelasan' => trim($this->request->getPost('penjelasan')),
                'peringkatkomposit' => trim($this->request->getPost('peringkatkomposit')),
                'penjelasankomposit' => trim($this->request->getPost('penjelasankomposit')),
                'periode_id' => $periodeId,
                'user_id' => $this->userId,
                'kodebpr' => $kodebpr,
                'fullname' => $this->userData['fullname'] ?? null,
                'accdekom' => 0,
                'is_approved' => 0,
            ];

            if ($existing) {
                $rowData['id'] = $existing['id'];
                $this->getPenjelasanModel()->save($rowData);
                return $this->jsonOk('Data Penjelasan Umum berhasil diubah');
            }

            $this->getPenjelasanModel()->save($rowData);
            return $this->jsonOk('Data Penjelasan Umum berhasil ditambahkan');

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::tambahpenjelasAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getKomentar()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $list = $this->getKomentarModel()->getKomentarByPeriode(
            $kodebpr,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON($list);
    }

    public function tambahKomentar()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        $teks = trim($this->request->getPost('komentar') ?? '');

        if (strlen($teks) < 3)
            return $this->jsonErr('Komentar minimal 3 karakter');
        if (strlen($teks) > 1000)
            return $this->jsonErr('Komentar maksimal 1000 karakter');

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            $this->getKomentarModel()->insertKomentar([
                'user_id' => $this->userId,
                'fullname' => $this->userData['fullname'] ?? '',
                'kategori' => self::KATEGORI,
                'subkategori' => self::SUBKATEGORI,
                'komentar' => $teks,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
            ]);

            return $this->jsonOk('Komentar berhasil ditambahkan');

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::tambahKomentar] ' . $e->getMessage());
            return $this->jsonErr('Gagal menambahkan komentar');
        }
    }

    public function getUnreadCounts()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $count = $this->getCommentReadsModel()->countUnreadComments(
            $kodebpr,
            $this->userId,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON([
            'status' => 'success',
            'count' => (int) $count,
        ]);
    }

    public function markKomentarAsRead()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $marked = $this->getCommentReadsModel()->markAllAsReadForPeriode(
            $kodebpr,
            $this->userId,
            $periodeId,
            self::KATEGORI,
            self::SUBKATEGORI
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Comments marked as read',
            'marked_count' => $marked,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function approveSemuaKom()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireDekom())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 1,
                    'accdekom_by' => $this->userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                    'komut' => $this->userData['fullname'] ?? '',
                ]);

            return $this->jsonOk('Penjelasan umum disetujui oleh Komisaris Utama');

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::approveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaKom()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireDekom())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 0,
                    'accdekom_by' => $this->userId,
                    'accdekom_at' => null,
                    'is_approved' => 0,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);

            return $this->jsonOk('Persetujuan Komisaris Utama dibatalkan');

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::unapproveSemuaKom] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveSemuaDirut()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireDireksi())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $rows = $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->findAll();
            $dekomOk = !empty($rows) && array_reduce($rows, fn($c, $r) => $c && (int) $r['accdekom'] === 1, true);
            if (!$dekomOk)
                return $this->jsonErr('Komisaris Utama harus menyetujui terlebih dahulu');

            $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, [
                    'is_approved' => 1,
                    'approved_by' => $this->userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'dirut' => $this->userData['fullname'] ?? '',
                ]);

            return $this->jsonOk('Penjelasan umum disetujui oleh Direktur Utama');

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::approveSemuaDirut] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function unapproveSemuaDirut()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireDireksi())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->update(null, [
                    'is_approved' => 0,
                    'approved_by' => $this->userId,
                    'approved_at' => null,
                ]);

            return $this->jsonOk('Persetujuan Direktur Utama dibatalkan');

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::unapproveSemuaDirut] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function checkPrevYear()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        $currentPeriode = $this->getPeriodeModel()->find($periodeId);
        if (!$currentPeriode)
            return $this->response->setJSON(['hasPrev' => false, 'debug' => 'current periode not found']);

        $prevTahun = (int) $currentPeriode['tahun'] - 1;

        $prevPeriode = $this->getPeriodeModel()
            ->where('tahun', $prevTahun)
            ->where('kodebpr', $kodebpr)
            ->first();

        if (!$prevPeriode)
            return $this->response->setJSON(['hasPrev' => false, 'prevTahun' => $prevTahun]);

        $exists = $this->getPenjelasanModel()
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $prevPeriode['id'])
            ->countAllResults() > 0;

        return $this->response->setJSON([
            'hasPrev' => $exists,
            'prevTahun' => $prevTahun,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function syncFromPrevYear()
    {
        if ($e = $this->requireAjax())
            return $e;
        if ($e = $this->requireAuth())
            return $e;
        if ($e = $this->requireEditor())
            return $e;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $currentPeriode = $this->getPeriodeModel()->find($periodeId);
            if (!$currentPeriode)
                return $this->jsonErr('Periode aktif tidak ditemukan');

            $prevTahun = (int) $currentPeriode['tahun'] - 1;

            $prevPeriode = $this->getPeriodeModel()
                ->where('tahun', $prevTahun)
                ->where('kodebpr', $kodebpr)
                ->first();

            if (!$prevPeriode)
                return $this->response->setJSON([
                    'status' => 'no_prev',
                    'hasPrev' => false,
                    'message' => "Tidak ada data periode tahun {$prevTahun}.",
                    'csrf_hash' => csrf_hash(),
                ]);

            $prevRow = $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $prevPeriode['id'])
                ->first();

            if (!$prevRow)
                return $this->response->setJSON([
                    'status' => 'no_prev',
                    'hasPrev' => false,
                    'message' => "Tidak ada data Penjelasan Umum untuk tahun {$prevTahun}.",
                    'csrf_hash' => csrf_hash(),
                ]);

            $syncFields = [
                'namabpr',
                'alamat',
                'nomor',
                'penjelasan',
                'peringkatkomposit',
                'penjelasankomposit'
            ];

            $syncData = array_intersect_key($prevRow, array_flip($syncFields));
            $syncData['periode_id'] = $periodeId;
            $syncData['kodebpr'] = $kodebpr;
            $syncData['user_id'] = $this->userId;
            $syncData['fullname'] = $this->userData['fullname'] ?? null;
            $syncData['accdekom'] = 0;
            $syncData['is_approved'] = 0;

            $existing = $this->getPenjelasanModel()
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($existing) {
                $syncData['id'] = $existing['id'];
            }

            $this->getPenjelasanModel()->save($syncData);

            return $this->response->setJSON([
                'status' => 'success',
                'hasPrev' => true,
                'synced' => true,
                'prevTahun' => $prevTahun,
                'data' => $syncData,
                'message' => "Data tahun {$prevTahun} berhasil disinkronkan ke tahun {$currentPeriode['tahun']}.",
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Penjelasanumum::syncFromPrevYear] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxtpenjelasanumum()
    {
        if (!$this->auth->check())
            return redirect()->to(session('redirect_url') ?? '/login');

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        $data_infobpr = $this->getInfobprModel()->getDataByKodebpr($kodebpr);
        $periodeDetail = $this->getPeriodeModel()->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $rows = $this->getPenjelasanModel()->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->findAll();

        $sanitizeOjk = function ($text): string {
            if ($text === null)
                return '';
            $text = (string) $text;
            $text = str_replace(["\r", "\n", "\t"], ' ', $text);
            $text = str_replace('|', ' ', $text);

            $text = preg_replace('/[^a-zA-Z0-9 @#$%^&*(){}[\]<=>~_\-+`\'".,;:\\/?!]/', '', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        };

        $output = "H01|010201|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0100|0|\r\n";

        foreach ($rows as $row) {
            $penjelasan = $sanitizeOjk($row['penjelasan'] ?? '');
            $penjelasankomposit = $sanitizeOjk($row['penjelasankomposit'] ?? '');
            $alamat = $sanitizeOjk($row['alamat'] ?? '');

            $output .= "D01|000100000000|{$alamat}|{$row['nomor']}|{$penjelasan}|{$row['peringkatkomposit']}|{$penjelasankomposit}\r\n";
        }

        $filename = "LTBPRK-E0100-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}