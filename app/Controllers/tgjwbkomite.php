<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_tgjwbkomite;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_periodetahunan;
use App\Models\M_transparansicomments;
use App\Models\M_transparansicommentsread;
use App\Models\M_penjelastindak;
use Myth\Auth\Config\Services as AuthServices;

class tgjwbkomite extends Controller
{
    protected $auth;
    protected $tgjwbkomiteModel;
    protected $userModel;
    protected $komentarModel;
    protected $infobprModel;
    protected $session;
    protected $userKodebpr;
    protected $periodeModel;
    protected $commentReadsModel;
    protected $penjelastindakModel;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;
    private const CACHE_TTL = 30;
    private const POLL_MIN_MS = 8000;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->tgjwbkomiteModel = new M_tgjwbkomite();
        $this->periodeModel = new M_periodetahunan();
        $this->userModel = new M_user();
        $this->komentarModel = new M_transparansicomments();
        $this->commentReadsModel = new M_transparansicommentsread();
        $this->penjelastindakModel = new M_penjelastindak();
        $this->infobprModel = new M_infobpr();
        $this->session = service('session');
        $this->auth = service('authentication');

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $this->userKodebpr = $user['kodebpr'] ?? null;

        $authorize = AuthServices::authorization();

        $this->userInGroupPE = $authorize->inGroup('pe', $this->auth->id());
        $this->userInGroupAdmin = $authorize->inGroup('admin', $this->auth->id());
        $this->userInGroupDekom = $authorize->inGroup('dekom', $this->auth->id());
        $this->userInGroupDekom2 = $authorize->inGroup('dekom2', $this->auth->id());
        $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->auth->id());
        $this->userInGroupDireksi2 = $authorize->inGroup('direksi2', $this->auth->id());
    }

    private function getPageBundle(string $kodebpr, int $periodeId): array
    {
        $cache = service('cache');
        $cacheKey = "tgjwb_bundle_{$kodebpr}_{$periodeId}";

        $rows = $this->getFreshRows($kodebpr, $periodeId);

        $allAccdekomApproved = !empty($rows) && array_reduce(
            $rows,
            fn($c, $i) => $c && (int) $i['accdekom'] === 1,
            true
        );
        $allAccdirutApproved = !empty($rows) && array_reduce(
            $rows,
            fn($c, $i) => $c && (int) $i['is_approved'] === 1,
            true
        );
        $canApprove = $allAccdekomApproved;

        $lastDekom = null;
        $lastDirut = null;
        foreach ($rows as $r) {
            if (!empty($r['accdekom_at']))
                $lastDekom = $r['accdekom_at'];
            if (!empty($r['approved_at']))
                $lastDirut = $r['approved_at'];
        }

        return compact(
            'rows',
            'allAccdekomApproved',
            'allAccdirutApproved',
            'canApprove',
            'lastDekom',
            'lastDirut'
        );
    }

    private function getFreshRows(string $kodebpr, int $periodeId): array
    {
        return $this->tgjwbkomiteModel
            ->select('id, komite, tugastgjwbkomite, jumlahrapat, prokerkomite,
                  hasilprokerkomite, accdekom, accdekom_at, is_approved, approved_at, is_null_record')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    private function jsonOk(string $message, array $extra = [])
    {
        return $this->response->setJSON(
            array_merge(['status' => 'success', 'message' => $message, 'ts' => time()], $extra)
        );
    }

    private function jsonErr(string $message, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status' => 'error',
            'message' => $message,
        ]);
    }

    private function requireAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }
        return null;
    }

    private function requireAuth()
    {
        if (!$this->auth->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }
        return null;
    }

    private function checkIdempotency(string $key): bool
    {
        $cache = service('cache');
        $cacheKey = 'idempotency_' . md5($key);
        if ($cache->get($cacheKey)) {
            return false;
        }
        $cache->save($cacheKey, 1, 10);
        return true;
    }

    public function index()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        if (!session('active_periode')) {
            return redirect()->to('/Periodetransparansi');
        }

        $periodeId = (int) session('active_periode');
        $kodebpr = $this->userKodebpr;
        $user = $this->userModel->find(user_id());
        $periodeDetail = $this->periodeModel->getPeriodeDetail($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $subkategori = 'Tgjwbkomite';

        $bundle = $this->getPageBundle($kodebpr, $periodeId);

        $penjelastindak = $this->penjelastindakModel
            ->getDataPenjelasByKodebprAndPeriode($subkategori, $kodebpr, $periodeId);

        $accdekomData = array_map(fn($r) => [
            'accdekom' => $r['accdekom'],
            'accdekom_at' => $r['accdekom_at'] ?? null,
        ], $bundle['rows']);

        $accdirutData = array_map(fn($r) => [
            'is_approved' => $r['is_approved'],
            'approved_at' => $r['approved_at'] ?? null,
        ], $bundle['rows']);

        $hasNullRecord = false;
        $hasAnyRecord = !empty($bundle['rows']);
        foreach ($bundle['rows'] as $r) {
            if (!empty($r['is_null_record'])) {
                $hasNullRecord = true;
                break;
            }
        }

        $data = [
            'judul' => '4. Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite',
            'tgjwbkomite' => $bundle['rows'],
            'userId' => user_id(),
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'accdekomData' => $accdekomData,
            'accdirutData' => $accdirutData,
            'penjelastindak' => $penjelastindak,
            'canApprove' => $bundle['canApprove'],
            'hasNullRecord' => $hasNullRecord,
            'hasAnyRecord' => $hasAnyRecord,
            'isComplete' => $hasAnyRecord,
            'pollIntervalMs' => self::POLL_MIN_MS,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporantahunan/Transparansi/tgjwbkomite', $data);
        echo view('templates/v_footer');
    }

    public function tambahAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $iKey = "tambah_{$kodebpr}_{$periodeId}_" . $this->auth->id();
            if (!$this->checkIdempotency($iKey)) {
                return $this->jsonErr('Permintaan duplikat — silakan tunggu sebentar');
            }

            $hasNull = $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->where('is_null_record', 1)
                ->countAllResults();

            if ($hasNull > 0)
                return $this->jsonErr('Hapus status "Tidak Ada Data" terlebih dahulu sebelum menambah data');


            $komite = trim($this->request->getPost('komite') ?? '');
            $tugas = trim($this->request->getPost('tugastgjwbkomite') ?? '');
            $jumlahrapat = trim($this->request->getPost('jumlahrapat') ?? '');
            $prokerkomite = trim($this->request->getPost('prokerkomite') ?? '');
            $hasilprokerkomite = trim($this->request->getPost('hasilprokerkomite') ?? '');

            if (empty($komite) || empty($tugas) || empty($jumlahrapat) || empty($prokerkomite) || empty($hasilprokerkomite)) {
                return $this->jsonErr('Semua field wajib diisi');
            }

            if (!is_numeric($jumlahrapat) || (int) $jumlahrapat < 0) {
                return $this->jsonErr('Jumlah rapat harus berupa angka positif');
            }

            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);

            $this->tgjwbkomiteModel->checkIncrement();
            $inserted = $this->tgjwbkomiteModel->insert([
                'komite' => $komite,
                'tugastgjwbkomite' => $tugas,
                'jumlahrapat' => (int) $jumlahrapat,
                'prokerkomite' => $prokerkomite,
                'hasilprokerkomite' => $hasilprokerkomite,
                'periode_id' => $periodeId,
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'fullname' => $user['fullname'] ?? null,
                'accdekom' => 0,
                'is_approved' => 0,
            ]);

            if (!$inserted)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Data berhasil ditambahkan', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[tgjwbkomite::tambahAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubahAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$id || !$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $existing = $this->tgjwbkomiteModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $komite = trim($this->request->getPost('komite') ?? '');
            $tugas = trim($this->request->getPost('tugastgjwbkomite') ?? '');
            $jumlahrapat = trim($this->request->getPost('jumlahrapat') ?? '');
            $prokerkomite = trim($this->request->getPost('prokerkomite') ?? '');
            $hasilprokerkomite = trim($this->request->getPost('hasilprokerkomite') ?? '');

            if (empty($komite) || empty($jumlahrapat) || empty($prokerkomite) || empty($hasilprokerkomite)) {
                return $this->jsonErr('Semua field wajib diisi');
            }

            if (!is_numeric($jumlahrapat) || (int) $jumlahrapat < 0) {
                return $this->jsonErr('Jumlah rapat harus berupa angka positif');
            }

            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);

            $updated = $this->tgjwbkomiteModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->set([
                    'komite' => $komite,
                    'tugastgjwbkomite' => $tugas,
                    'jumlahrapat' => (int) $jumlahrapat,
                    'prokerkomite' => $prokerkomite,
                    'hasilprokerkomite' => $hasilprokerkomite,
                    'user_id' => $userId,
                    'fullname' => $user['fullname'] ?? null,
                    'accdekom' => 0,
                    'is_approved' => 0,
                ])
                ->update();

            if (!$updated)
                return $this->jsonErr('Gagal mengubah data');

            return $this->jsonOk('Data berhasil diubah', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[tgjwbkomite::ubahAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapusAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$id || !$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $existing = $this->tgjwbkomiteModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');

            $deleted = $this->tgjwbkomiteModel
                ->where('id', $id)
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->delete();

            if (!$deleted)
                return $this->jsonErr('Gagal menghapus data');

            return $this->jsonOk('Data berhasil dihapus', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[tgjwbkomite::hapusAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getKomentarByFaktorId()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404)->setBody('Not Found');
        }

        $subkategori = 'Tgjwbkomite';
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $userId = user_id();

        $komentarList = $this->komentarModel->getKomentarByFaktorId($subkategori, $kodebpr, $periodeId);
        $unreadCount = count(array_filter($komentarList, fn($k) => (int) ($k['user_id']) !== $userId));

        return $this->response->setJSON([
            'comments' => $komentarList,
            'unread_count' => $unreadCount,
        ]);
    }

    public function getUnreadCommentCountForFactor()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['unread_count' => 0]);
        }

        $cache = service('cache');
        $cacheKey = "unread_count_Tgjwbkomite_{$kodebpr}_{$periodeId}_{$userId}";

        $count = $cache->remember($cacheKey, self::CACHE_TTL, function () use ($userId, $kodebpr, $periodeId) {
            return $this->commentReadsModel->countUnreadCommentsForUserByFactor(
                'Tgjwbkomite',
                $kodebpr,
                $userId,
                $periodeId
            );
        });

        return $this->response->setJSON([
            'unread_count' => (int) $count,
            'next_poll_ms' => self::POLL_MIN_MS,
        ]);
    }

    public function Tambahkomentar()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        if (!$this->request->isAJAX()) {
            session()->setFlashdata('err', 'Gunakan interface yang benar.');
            return redirect()->back();
        }

        $userId = $this->auth->id();
        $user = $this->userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return $this->jsonErr('User tidak memiliki kode BPR yang valid');
        }

        $komentar = trim($this->request->getPost('komentar') ?? '');
        if (strlen($komentar) < 3) {
            return $this->jsonErr('Komentar minimal 3 karakter');
        }
        if (strlen($komentar) > 1000) {
            return $this->jsonErr('Komentar maksimal 1000 karakter');
        }

        $cache = service('cache');
        $rlKey = "comment_rate_{$userId}";
        $rlCount = (int) ($cache->get($rlKey) ?? 0);
        if ($rlCount >= 10) {
            return $this->jsonErr('Terlalu banyak komentar — tunggu sebentar');
        }
        $cache->save($rlKey, $rlCount + 1, 60);

        $this->komentarModel->insertKomentar([
            'id' => 'Tgjwbkomite',
            'subkategori' => 'Tgjwbkomite',
            'komentar' => $komentar,
            'fullname' => $user['fullname'] ?? '',
            'user_id' => $userId,
            'kodebpr' => $kodebpr,
            'periode_id' => session('active_periode'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $cache->deleteMatching("unread_count_Tgjwbkomite_{$kodebpr}_*");

        return $this->jsonOk('Komentar berhasil dikirim');
    }

    public function markUserCommentsAsRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $kodebpr = $this->userKodebpr;
        $userId = user_id();
        $periodeId = session('active_periode');

        if (!$kodebpr || !$userId || !$periodeId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing data.']);
        }

        $ids = $this->komentarModel
            ->select('id')
            ->where('subkategori', 'Tgjwbkomite')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where('user_id !=', $userId)
            ->findColumn('id');

        if (!empty($ids)) {
            foreach ($ids as $commentId) {
                $this->commentReadsModel->markAsRead($commentId, $userId);
            }
        }

        service('cache')->delete("unread_count_Tgjwbkomite_{$kodebpr}_{$periodeId}_{$userId}");

        return $this->response->setJSON(['status' => 'success']);
    }

    public function tambahKeteranganAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
            if ($tindaklanjut === '')
                return $this->jsonErr('Tindak lanjut wajib diisi');

            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);

            if (
                !$this->penjelastindakModel->tambahpenjelastindak([
                    'subkategori' => 'Tgjwbkomite',
                    'tindaklanjut' => $tindaklanjut,
                    'penjelasanlanjut' => trim($this->request->getPost('penjelasanlanjut') ?? '') ?: null,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'fullname' => $user['fullname'] ?? null,
                    'user_id' => $userId,
                ])
            ) {
                return $this->jsonErr('Gagal menyimpan data');
            }

            return $this->jsonOk('Tindak lanjut berhasil disimpan');

        } catch (\Throwable $e) {
            log_message('error', '[tambahKeteranganAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function editKeteranganAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);
            $kodebpr = $user['kodebpr'] ?? null;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $tindaklanjut = trim($this->request->getPost('tindaklanjut') ?? '');
            if ($tindaklanjut === '')
                return $this->jsonErr('Tindak lanjut tidak boleh kosong');

            $updated = $this->penjelastindakModel->editberdasarkankodedanperiode(
                ['tindaklanjut' => $tindaklanjut, 'user_id' => $userId, 'kodebpr' => $kodebpr],
                'Tgjwbkomite',
                $kodebpr,
                $periodeId
            );

            if (!$updated)
                return $this->jsonErr('Gagal mengubah data');

            return $this->jsonOk('Data berhasil diubah');

        } catch (\Throwable $e) {
            log_message('error', '[editKeteranganAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function setNullKolomTindakAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            if (!$id)
                return $this->jsonErr('ID tidak valid');

            if (!$this->penjelastindakModel->setNullKolomTindak($id)) {
                return $this->jsonErr('Gagal menghapus data');
            }
            return $this->jsonOk('Tindak lanjut berhasil dihapus');

        } catch (\Exception $e) {
            log_message('error', '[tgjwbkomite::setNullKolomTindakAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    private function bulkApprove(array $fields, string $successMsg, string $errMsg)
    {
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, array_merge($fields, [
                    'approved_by' => $userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]));

            session()->setFlashdata(
                str_contains($successMsg, 'Batal') || str_contains($errMsg, 'dibatalkan') ? 'err' : 'message',
                $successMsg
            );
        } catch (\Exception $e) {
            log_message('error', '[bulkApprove] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaKom()
    {
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 1,
                    'accdekom_by' => $userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                ]);

            session()->setFlashdata('message', 'Data berhasil disetujui oleh Komisaris Utama.');
        } catch (\Exception $e) {
            log_message('error', '[approveSemuaKom] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function unapproveSemuaKom()
    {
        $userId = service('authentication')->id();
        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            session()->setFlashdata('err', 'Kode BPR atau Periode ID tidak valid');
            return redirect()->back();
        }

        try {
            $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->update(null, [
                    'accdekom' => 0,
                    'is_approved' => 0,
                    'accdekom_by' => $userId,
                    'accdekom_at' => date('Y-m-d H:i:s'),
                ]);

            session()->setFlashdata('err', 'Approval Komisaris Utama dibatalkan.');
        } catch (\Exception $e) {
            log_message('error', '[unapproveSemuaKom] ' . $e->getMessage());
            session()->setFlashdata('err', 'Terjadi kesalahan sistem');
        }

        return redirect()->back();
    }

    public function approveSemuaDirut()
    {
        return $this->bulkApprove(
            ['is_approved' => 1],
            'Data disetujui oleh Direktur Utama.',
            'Gagal menyetujui'
        );
    }

    public function unapproveSemuaDirut()
    {
        return $this->bulkApprove(
            ['is_approved' => 0, 'accdekom' => 0],
            'Approval Direktur Utama dibatalkan.',
            'Gagal membatalkan approval'
        );
    }

    public function setNodataAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Kode BPR atau periode tidak valid');

            // Prevent duplicate null records
            $existing = $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->countAllResults();

            if ($existing > 0)
                return $this->jsonErr('Data sudah ada, tidak bisa ditandai sebagai kosong');

            $userId = $this->auth->id();
            $user = $this->userModel->find($userId);

            $inserted = $this->tgjwbkomiteModel->insert([
                'komite' => null,
                'tugastgjwbkomite' => null,
                'jumlahrapat' => null,
                'prokerkomite' => null,
                'hasilprokerkomite' => null,
                'is_null_record' => 1,
                'periode_id' => $periodeId,
                'user_id' => $userId,
                'kodebpr' => $kodebpr,
                'fullname' => $user['fullname'] ?? null,
                'accdekom' => 0,
                'is_approved' => 0,
            ]);

            if (!$inserted)
                return $this->jsonErr('Gagal menyimpan data');

            return $this->jsonOk('Data berhasil ditandai sebagai tidak ada', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[tgjwbkomite::setNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function batalNodataAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            $kodebpr = $this->userKodebpr;
            $periodeId = (int) session('active_periode');

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            $deleted = $this->tgjwbkomiteModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->where('is_null_record', 1)
                ->delete();

            if (!$deleted)
                return $this->jsonErr('Gagal membatalkan status');

            return $this->jsonOk('Status berhasil dibatalkan', [
                'data' => $this->getFreshRows($kodebpr, $periodeId),
            ]);

        } catch (\Exception $e) {
            log_message('error', '[tgjwbkomite::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function exporttxttgjwbkomite()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = session('active_periode');
        $subkategori = 'Tgjwbkomite';
        $periodeDetail = $this->periodeModel->find($periodeId);
        $exportDate = $periodeDetail['tahun'] ?? date('Y');
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $data_tgjwbkomite = $this->tgjwbkomiteModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);
        $data_penjelastindak = $this->penjelastindakModel
            ->where('subkategori', $subkategori)
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->findAll();

        $sandibpr = $data_infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $data_infobpr[0]['kodejenis'] ?? '';

        $sanitize = function (string $text): string {
            $text = str_replace(["\r", "\n", "\t", '|', '%'], [' ', ' ', ' ', ' ', ' persen '], $text);
            $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D"], '"', $text);
            $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99"], "'", $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}-12-31|LTBPRK|E0203|0|\r\n";

        $hasRealData = false;

        foreach ($data_tgjwbkomite as $row) {
            if ((int)($row['is_null_record'] ?? 0) === 1) {
                continue;
            }
        
            $komite = trim((string) ($row['komite'] ?? ''));
            $tugas  = trim((string) ($row['tugastgjwbkomite'] ?? ''));
        
            if ($komite === '' && $tugas === '') {
                continue;
            }
        
            $hasRealData = true;
        
            $output .= sprintf(
                "D01|013301000000|%s|%s|%s|%s|%s\r\n",
                $sanitize($komite),
                $sanitize($tugas),
                $sanitize((string) ($row['prokerkomite'] ?? '')),
                $sanitize((string) ($row['hasilprokerkomite'] ?? '')),
                $sanitize((string) ($row['jumlahrapat'] ?? ''))
            );
        }
        
        if (!$hasRealData) {
            $output .= "D01|013301000000|05| | | |0\r\n";
        }

        foreach ($data_penjelastindak as $p) {
            if (!empty($p['tindaklanjut'])) {
                $output .= 'F01|' . $sanitize($p['tindaklanjut']) . "\r\n";
            }
            if (!empty($p['penjelasanlanjut'])) {
                $output .= 'F02|' . $sanitize($p['penjelasanlanjut']) . "\r\n";
            }
        }

        $filename = "LTBPRK-E0203-{$jenispelaporan}-A-{$exportDate}1231-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody($output);
    }
}