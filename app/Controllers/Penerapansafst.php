<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_penerapansafst;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesaf;
use Myth\Auth\Config\Services as AuthServices;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Penerapansafst extends Controller
{
    protected $db;
    protected $auth;
    protected $session;

    protected $penerapansafstModel;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;

    protected $userKodebpr;
    protected $userId;
    protected $userRole;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDekom2;
    protected $userInGroupDekom3;
    protected $userInGroupDekom4;
    protected $userInGroupDekom5;
    protected $userInGroupDireksi;
    protected $userInGroupDireksi2;


    const KATEGORI = 'DIRYMF';
    const SUBKATEGORI = 'PENERAPANSAFST';
    private const IMPORT_MAX_ROWS = 400;
    private const IMPORT_MAX_SIZE = 3 * 1024 * 1024;

    private const KODE_KOMPONEN = '0101000000';

    private const ROLE_IDS = [
        'admin' => 1,
        'dekom' => 2,
        'direksi' => 3,
        'pe' => 4,
        'dekom2' => 6,
        'dekom3' => 7,
        'dekom4' => 8,
        'dekom5' => 9,
        'direksi2' => 10,
    ];

    private const LEVEL_LABEL = [
        'dekom' => 'Dewan Komisaris',
        'direksi' => 'Direksi',
    ];

    private const DATE_FIELDS = ['wterjadiawal', 'wterjadiakhir', 'wfrauddiketahui', 'tanggallahir', 'targetpelaksanaan', 'realpelaksanaan',];

    private const MONEY_FIELDS_01A = [
        'rugiljkriil',
        'rugiljkpot',
        'rugiljkrec',
        'rugikonsumenriil',
        'rugikonsumenpot',
        'rugikonsumenrec',
        'rugilainriil',
        'rugilainpot',
        'rugilainrec',
    ];

    private const MONEY_FIELDS_01B = [
        'rugipotensitotal',
    ];

    private const REQUIRED_FIELDS_01A = [
        'kejadianfraud',
        'idkejadian',
        'jenisfraud',
        'aktivitasfraud',
        'lokfraud',
        'ketlokfraud',
        'pihakrugi',
        'kelemahanfraud',
        'tindakpenanganan',
        'tindakperbaikan',
        'targetpelaksanaan',
        'realpelaksanaan',
        'pelakufraud',
        'namapelaku',
        'jenisidentitas',
        'statuspelaku',
        'jabatanterjadi',
        'jabatandiketahui',
        'statuspenanganan',
        'rugiljkriil',
        'rugiljkpot',
        'rugiljkrec'
    ];

    private const REQUIRED_FIELDS_01B = [
        'kejadianfraud',
        'idkejadian',
        'jenisfraud',
        'aktivitasfraud',
        'lokfraud',
        'ketlokfraud',
        'pihakrugi',
        'tindaklanjutljk',
        'targetpelaksanaan',
        'realpelaksanaan',
        'pelakufraud',
        'namapelaku',
        'jenisidentitas',
        'statuspelaku',
        'jabatanterjadi',
        'jabatandiketahui',
        'statuspenanganan',
        'rugipotensitotal',
    ];

    private const COND_RULES = [
        'ketjenisfraud' => ['jenisfraud', '701'],
        'ketkelemahanfraud' => ['kelemahanfraud', '901'],
        'kettindakpenanganan' => ['tindakpenanganan', '19'],
        'kettindakperbaikan' => ['tindakperbaikan', '900'],
        'keteranganpelaku' => ['pelakufraud', '002'],
    ];

    private const FIELD_LABELS = [
        'kejadianfraud' => 'Kejadian Fraud Menurut Pelaku',
        'idkejadian' => 'ID Kejadian Fraud',
        'jenisfraud' => 'Jenis Fraud',
        'ketjenisfraud' => 'Keterangan Jenis Fraud',
        'aktivitasfraud' => 'Aktivitas Terkait Fraud',
        'lokfraud' => 'Lokasi Fraud',
        'ketlokfraud' => 'Keterangan Lokasi',
        'pihakrugi' => 'Pihak Yang Dirugikan',
        'kelemahanfraud' => 'Kelemahan Penyebab Fraud',
        'ketkelemahanfraud' => 'Keterangan Kelemahan',
        'tindakpenanganan' => 'Tindakan Untuk Penanganan Fraud',
        'kettindakpenanganan' => 'Keterangan Tindakan Penanganan',
        'tindakperbaikan' => 'Tindakan Perbaikan untuk Pencegahan Fraud',
        'kettindakperbaikan' => 'Keterangan Tindakan Perbaikan',
        'tindaklanjutljk' => 'Tindak Lanjut LJK',
        'targetpelaksanaan' => 'Target Waktu Pelaksanaan',
        'realpelaksanaan' => 'Realisasi Pelaksanaan',
        'pelakufraud' => 'Pelaku Fraud (Internal/Eksternal)',
        'namapelaku' => 'Nama Pelaku',
        'jenisidentitas' => 'Jenis Identitas',
        'statuspelaku' => 'Status Pelaku',
        'jabatanterjadi' => 'Jabatan Saat Fraud Terjadi',
        'jabatandiketahui' => 'Jabatan Saat Fraud Diketahui',
        'keteranganpelaku' => 'Keterangan Pelaku',
        'statuspenanganan' => 'Status Penanganan',
        'rugiljkriil' => 'Kerugian LJK - Riil',
        'rugiljkpot' => 'Kerugian LJK - Potensial',
        'rugiljkrec' => 'Kerugian LJK - Recovery',
        'rugikonsumenriil' => 'Kerugian Konsumen - Riil',
        'rugikonsumenpot' => 'Kerugian Konsumen - Potensial',
        'rugikonsumenrec' => 'Kerugian Konsumen - Recovery',
        'rugilainriil' => 'Kerugian Pihak Lain - Riil',
        'rugilainpot' => 'Kerugian Pihak Lain - Potensial',
        'rugilainrec' => 'Kerugian Pihak Lain - Recovery',
        'rugipotensitotal' => 'Total Kerugian Potensial',
    ];

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->db = \Config\Database::connect();
        $this->session = service('session');
        $this->auth = service('authentication');

        $this->penerapansafstModel = new M_penerapansafst();
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesaf();

        helper('url');

        if ($this->auth->check()) {
            $this->userId = $this->auth->id();
            $user = $this->userModel->find($this->userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();

            $this->userInGroupPE = $authorize->inGroup('pe', $this->userId);
            $this->userInGroupAdmin = $authorize->inGroup('admin', $this->userId);
            $this->userInGroupDekom = $authorize->inGroup('dekom', $this->userId);
            $this->userInGroupDekom2 = $authorize->inGroup('dekom2', $this->userId);
            $this->userInGroupDekom3 = $authorize->inGroup('dekom3', $this->userId);
            $this->userInGroupDekom4 = $authorize->inGroup('dekom4', $this->userId);
            $this->userInGroupDekom5 = $authorize->inGroup('dekom5', $this->userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $this->userId);
            $this->userInGroupDireksi2 = $authorize->inGroup('direksi2', $this->userId);

            $this->userRole = $this->resolveUserRole();
        }
    }

    // ROLE & HAK AKSES
    private function resolveUserRole(): ?int
    {
        $priority = [
            'pe' => $this->userInGroupPE,
            'direksi' => $this->userInGroupDireksi,
            'direksi2' => $this->userInGroupDireksi2,
            'dekom' => $this->userInGroupDekom,
            'dekom2' => $this->userInGroupDekom2,
            'dekom3' => $this->userInGroupDekom3,
            'dekom4' => $this->userInGroupDekom4,
            'dekom5' => $this->userInGroupDekom5,
            'admin' => $this->userInGroupAdmin,
        ];

        foreach ($priority as $name => $inGroup) {
            if ($inGroup) {
                return self::ROLE_IDS[$name];
            }
        }
        return null;
    }

    private function canInput(): bool
    {
        return (bool) ($this->userInGroupPE || $this->userInGroupAdmin
            || $this->userInGroupDekom || $this->userInGroupDekom2 || $this->userInGroupDekom3
            || $this->userInGroupDekom4 || $this->userInGroupDekom5
            || $this->userInGroupDireksi || $this->userInGroupDireksi2);
    }

    private function canApproveLevel(string $level): bool
    {
        if ($this->userInGroupAdmin) {
            return true;
        }
        if ($level === 'dekom') {
            return (bool) ($this->userInGroupDekom || $this->userInGroupDekom2 || $this->userInGroupDekom3
                || $this->userInGroupDekom4 || $this->userInGroupDekom5);
        }
        if ($level === 'direksi') {
            return (bool) ($this->userInGroupDireksi || $this->userInGroupDireksi2);
        }
        return false;
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

    private function requireInput()
    {
        if (!$this->canInput()) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }
        return null;
    }

    private function getKodebprPeriode(): array
    {
        return [$this->userKodebpr, (int) session('active_periode')];
    }

    private function getFormType(?array $periodeDetail = null): string
    {
        if ($periodeDetail === null) {
            $periodeId = (int) session('active_periode');
            $periodeDetail = $periodeId ? $this->periodeModel->find($periodeId) : null;
        }
        $kodeform = $periodeDetail['kodeform'] ?? 'S';

        return $kodeform === 'I' ? 'B' : 'A';
    }

    private function getTanggalLaporan(?array $periodeDetail): string
    {
        $tahun = (int) ($periodeDetail['tahun'] ?? date('Y'));
        $sem = strtolower(trim((string) ($periodeDetail['semester'] ?? '')));
        $jenis = strtoupper(trim((string) ($periodeDetail['jenispelaporan'] ?? '')));

        if ($jenis === 'SAFTA') {
            return "{$tahun}-12-31";
        }

        $isSemester1 = in_array($sem, ['1', 'ganjil', 'semester 1', 'semester ganjil', 'i'], true);

        return $isSemester1 ? "{$tahun}-06-30" : "{$tahun}-12-31";
    }

    private function sections(string $formType): array
    {
        $isB = $formType === 'B';

        return [
            'kejadian' => ['kejadianfraud', 'idkejadian'],
            'jenis' => ['jenisfraud', 'ketjenisfraud', 'aktivitasfraud', 'descfraud'],
            'lokasi' => ['lokfraud', 'ketlokfraud', 'divisifraud'],
            'kerugian' => array_merge(
                ['pihakrugi', 'wterjadiawal', 'wterjadiakhir', 'wfrauddiketahui'],
                $this->moneyFields($formType)
            ),
            'tindakan' => $isB
                ? ['tindaklanjutljk', 'kettindaklanjutljk', 'targetpelaksanaan', 'realpelaksanaan']
                : [
                    'kelemahanfraud',
                    'ketkelemahanfraud',
                    'tindakpenanganan',
                    'kettindakpenanganan',
                    'tindakperbaikan',
                    'kettindakperbaikan',
                    'targetpelaksanaan',
                    'realpelaksanaan',
                ],
            'pelaku' => [
                'pelakufraud',
                'namapelaku',
                'jenisidentitas',
                'noidentitas',
                'jeniskelamin',
                'alamatidentitas',
                'alamatdomisili',
                'tempatlahir',
                'tanggallahir',
                'statuspelaku',
                'jabatanterjadi',
                'ketjabatanterjadi',
                'jabatandiketahui',
                'ketjabatanketahui',
                'keteranganpelaku',
            ],
            'status' => ['pengenaansanksi', 'statuspenanganan'],
        ];
    }

    private function dataFields(string $formType): array
    {
        return array_merge(...array_values($this->sections($formType)));
    }

    private function moneyFields(string $formType): array
    {
        return $formType === 'B' ? self::MONEY_FIELDS_01B : self::MONEY_FIELDS_01A;
    }

    private function requiredFields(string $formType): array
    {
        return $formType === 'B' ? self::REQUIRED_FIELDS_01B : self::REQUIRED_FIELDS_01A;
    }

    private function jsonOk(string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge(['status' => 'success', 'message' => $message], $extra));
    }

    private function jsonErr(string $message, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => 'error', 'message' => $message]);
    }

    private function normalizeConditional(array $data): array
    {
        if (array_key_exists('pelakufraud', $data) && (string) $data['pelakufraud'] !== '002') {
            $data['keteranganpelaku'] = null;
        }

        if (array_key_exists('tindakpenanganan', $data) && (string) $data['tindakpenanganan'] !== '19') {
            $data['kettindakpenanganan'] = null;
        }

        return $data;
    }

    private function extractDataFields(string $formType, ?array $only = null): array
    {
        $data = [];
        $money = $this->moneyFields($formType);
        $post = $this->request->getPost();

        foreach ($only ?? $this->dataFields($formType) as $f) {
            if ($only !== null && !array_key_exists($f, $post)) {
                continue;
            }
            $raw = $post[$f] ?? null;

            if (in_array($f, $money, true)) {
                $digits = preg_replace('/\D/', '', (string) $raw);
                $data[$f] = $digits === '' ? null : (int) $digits;
            } else {
                $val = trim((string) ($raw ?? ''));
                $data[$f] = $val === '' ? null : $val;
            }
        }

        return $this->normalizeConditional($data);
    }

    private function isBlank($v): bool
    {
        return $v === null || trim((string) $v) === '';
    }

    private function isNullRow(array $r): bool
    {
        if (!empty($r['is_null_record'])) {
            return true;
        }
        foreach (['idkejadian', 'kejadianfraud', 'jenisfraud', 'aktivitasfraud', 'namapelaku', 'descfraud'] as $f) {
            if (!$this->isBlank($r[$f] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function missingLabels(array $data, array $fields, string $formType): array
    {
        $missing = [];

        foreach ($this->requiredFields($formType) as $f) {
            if (in_array($f, $fields, true) && $this->isBlank($data[$f] ?? null)) {
                $missing[] = self::FIELD_LABELS[$f] ?? $f;
            }
        }

        foreach (self::COND_RULES as $ket => [$trigger, $val]) {
            if (!in_array($ket, $fields, true) || !in_array($trigger, $fields, true)) {
                continue;
            }
            if ((string) ($data[$trigger] ?? '') === $val && $this->isBlank($data[$ket] ?? null)) {
                $missing[] = self::FIELD_LABELS[$ket] ?? $ket;
            }
        }

        return $missing;
    }

    private function validateFields(array $data, array $fields, string $formType): ?string
    {
        $missing = $this->missingLabels($data, $fields, $formType);

        return $missing ? 'Field wajib berikut belum diisi: ' . implode(', ', $missing) : null;
    }

    private function validateRequired(array $data, string $formType): ?string
    {
        return $this->validateFields($data, $this->dataFields($formType), $formType);
    }

    private function approvalReset(): array
    {
        return [
            'accdir' => 0,
            'accdir_by' => null,
            'accdir_at' => null,
            'accdir2' => 0,
            'accdir2_by' => null,
            'accdir2_at' => null,
        ];
    }

    public function index()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/Periodesaf');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $user = $this->userModel->find($this->userId);
        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);
        $formType = $this->getFormType($periodeDetail);

        $data = [
            'judul' => 'Penerapan SAF yang mencakup informasi kejadian fraud dan informasi pelaku fraud',
            'userId' => $this->userId,
            'fullname' => $user['fullname'] ?? 'Unknown',
            'kodebpr' => $kodebpr,
            'periodeId' => $periodeId,
            'periodeDetail' => $periodeDetail,
            'bprData' => $bprData,
            'formType' => $formType,
            'canInput' => $this->canInput(),
            'canApproveDekom' => $this->canApproveLevel('dekom'),
            'canApproveDireksi' => $this->canApproveLevel('direksi'),
            'userInGroupPE' => $this->userInGroupPE,
            'userInGroupAdmin' => $this->userInGroupAdmin,
            'userInGroupDekom' => $this->userInGroupDekom,
            'userInGroupDekom2' => $this->userInGroupDekom2,
            'userInGroupDekom3' => $this->userInGroupDekom3,
            'userInGroupDekom4' => $this->userInGroupDekom4,
            'userInGroupDekom5' => $this->userInGroupDekom5,
            'userInGroupDireksi' => $this->userInGroupDireksi,
            'userInGroupDireksi2' => $this->userInGroupDireksi2,
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporansaf/penerapansafst', $data);
        echo view('templates/v_footer');
    }

    public function getData()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            $formType = $this->getFormType();
            $fields = $this->dataFields($formType);

            $allRows = $this->penerapansafstModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);

            $nullRow = null;
            $dataRows = [];
            foreach ($allRows as $r) {
                if ($this->isNullRow($r)) {
                    $nullRow = $r;
                    continue;
                }
                $r['req_levels'] = M_penerapansafst::requiredLevels($r['user_role'] ?? null);
                $r['missing'] = $this->missingLabels($r, $fields, $formType);
                $dataRows[] = $r;
            }

            return $this->jsonOk('OK', [
                'data' => $dataRows,
                'is_null_record' => $nullRow !== null,
                'form_type' => $formType,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::getData] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function tambah()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau Periode tidak valid');
            }

            if ($this->penerapansafstModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $formType = $this->getFormType();
            $fields = $this->extractDataFields($formType);

            if ($err = $this->validateRequired($fields, $formType)) {
                return $this->jsonErr($err);
            }

            $data = array_merge($fields, $this->approvalReset(), [
                'user_id' => $this->userId,
                'user_role' => $this->userRole,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'is_null_record' => 0,
            ]);

            if ($this->penerapansafstModel->tambah($data)) {
                return $this->jsonOk('Data berhasil ditambahkan');
            }

            return $this->jsonErr('Gagal menambahkan data');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::tambah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function ubah()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->penerapansafstModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            $formType = $this->getFormType();
            $fields = $this->extractDataFields($formType);

            if ($err = $this->validateRequired($fields, $formType)) {
                return $this->jsonErr($err);
            }

            $data = array_merge($fields, $this->approvalReset());

            if ($this->penerapansafstModel->ubah($data, $id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil diubah');
            }

            return $this->jsonErr('Gagal mengubah data');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::ubah] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function simpanSection()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau Periode tidak valid');
            }

            if ($this->penerapansafstModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum menambah data');
            }

            $formType = $this->getFormType();
            $sections = $this->sections($formType);
            $section = (string) $this->request->getPost('section');

            if (!isset($sections[$section])) {
                return $this->jsonErr('Bagian form tidak dikenal');
            }

            $fields = $sections[$section];
            $data = $this->extractDataFields($formType, $fields);

            if ($err = $this->validateFields($data, $fields, $formType)) {
                return $this->jsonErr($err);
            }

            $id = (int) $this->request->getPost('id');

            if ($id) {
                $existing = $this->penerapansafstModel
                    ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                    ->first();

                if (!$existing) {
                    return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
                }

                if ($this->penerapansafstModel->ubah(array_merge($data, $this->approvalReset()), $id, $kodebpr, $periodeId)) {
                    return $this->jsonOk('Bagian form berhasil disimpan', ['id' => $id]);
                }

                return $this->jsonErr('Gagal menyimpan data');
            }

            if ($section !== 'kejadian') {
                return $this->jsonErr('Simpan bagian "Kejadian Fraud" terlebih dahulu');
            }

            $newId = $this->penerapansafstModel->tambah(array_merge($data, $this->approvalReset(), [
                'user_id' => $this->userId,
                'user_role' => $this->userRole,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
                'is_null_record' => 0,
            ]));

            if ($newId) {
                return $this->jsonOk('Data kejadian berhasil disimpan', ['id' => (int) $newId]);
            }

            return $this->jsonErr('Gagal menyimpan data');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::simpanSection] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function hapus()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->penerapansafstModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing) {
                return $this->jsonErr('Data tidak ditemukan atau tidak memiliki akses');
            }

            if ($this->penerapansafstModel->hapusSpesifik($id, $kodebpr, $periodeId)) {
                return $this->jsonOk('Data berhasil dihapus');
            }

            return $this->jsonErr('Gagal menghapus data');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::hapus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function getApprovalStatus()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak lengkap');
            }

            $status = $this->penerapansafstModel->getApprovalStatus($kodebpr, $periodeId);

            return $this->jsonOk('OK', $status);

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::getApprovalStatus] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function approveKomRow()
    {
        return $this->handleApprovalRow(true);
    }

    public function unapproveKomRow()
    {
        return $this->handleApprovalRow(false);
    }

    public function approveSemuaKom()
    {
        return $this->handleApprovalAll(true);
    }

    public function unapproveSemuaKom()
    {
        return $this->handleApprovalAll(false);
    }

    private function postedLevel(): ?string
    {
        $level = (string) $this->request->getPost('level');
        return isset(self::LEVEL_LABEL[$level]) ? $level : null;
    }

    private function handleApprovalRow(bool $approve)
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $level = $this->postedLevel();
        if (!$level) {
            return $this->jsonErr('Level approval tidak valid');
        }
        if (!$this->canApproveLevel($level)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }
        $label = self::LEVEL_LABEL[$level];

        try {
            $id = (int) $this->request->getPost('id');
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$id || !$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            $existing = $this->penerapansafstModel
                ->where('id', $id)->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->first();

            if (!$existing)
                return $this->jsonErr('Data tidak ditemukan');

            if (!in_array($level, M_penerapansafst::requiredLevels($existing['user_role'] ?? null), true)) {
                return $this->jsonErr("Data ini tidak memerlukan approval {$label}");
            }

            if ($approve && !$this->isNullRow($existing)) {
                $formType = $this->getFormType();
                $missing = $this->missingLabels($existing, $this->dataFields($formType), $formType);
                if ($missing) {
                    return $this->jsonErr('Data belum lengkap: ' . implode(', ', array_slice($missing, 0, 5)));
                }
            }

            $ok = $approve
                ? $this->penerapansafstModel->approveRow($id, $kodebpr, $periodeId, $this->userId, $level)
                : $this->penerapansafstModel->unapproveRow($id, $kodebpr, $periodeId, $this->userId, $level);

            if ($ok) {
                return $this->jsonOk($approve ? "Data berhasil disetujui ({$label})" : "Approval {$label} dibatalkan");
            }

            return $this->jsonErr('Gagal menyimpan approval');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::handleApprovalRow] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    private function handleApprovalAll(bool $approve)
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;

        $level = $this->postedLevel();
        if (!$level) {
            return $this->jsonErr('Level approval tidak valid');
        }
        if (!$this->canApproveLevel($level)) {
            return $this->jsonErr('Tidak memiliki akses', 403);
        }
        $label = self::LEVEL_LABEL[$level];

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();

            if (!$kodebpr || !$periodeId)
                return $this->jsonErr('Data tidak valid');

            if ($approve) {
                $formType = $this->getFormType();
                $fields = $this->dataFields($formType);
                $incomplete = 0;

                foreach ($this->penerapansafstModel->getDataByKodebprAndPeriode($kodebpr, $periodeId) as $r) {
                    if ($this->isNullRow($r)) {
                        continue;
                    }
                    if (!in_array($level, M_penerapansafst::requiredLevels($r['user_role'] ?? null), true)) {
                        continue;
                    }
                    if ($this->missingLabels($r, $fields, $formType)) {
                        $incomplete++;
                    }
                }

                if ($incomplete > 0) {
                    return $this->jsonErr("{$incomplete} data belum lengkap. Lengkapi terlebih dahulu sebelum disetujui");
                }
            }

            $n = $this->penerapansafstModel->setApprovalAll($kodebpr, $periodeId, $this->userId, $level, $approve);

            if ($n > 0) {
                return $this->jsonOk($approve
                    ? "Semua data berhasil disetujui oleh {$label}"
                    : "Semua approval {$label} berhasil dibatalkan");
            }

            return $this->jsonErr("Tidak ada data yang memerlukan approval {$label}");

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::handleApprovalAll] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    // TIDAK ADA DATA FRAUD
    public function setNodataAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->penerapansafstModel->hasRealData($kodebpr, $periodeId)) {
                return $this->jsonErr('Tidak bisa ditandai kosong — masih ada data yang tersimpan. Hapus data terlebih dahulu.');
            }

            if ($this->penerapansafstModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Periode ini sudah ditandai tidak ada data');
            }

            $formType = $this->getFormType();

            $nullData = ['is_null_record' => 1];
            foreach ($this->dataFields($formType) as $f) {
                $nullData[$f] = null;
            }

            $ok = $this->penerapansafstModel->insertNullRecord(array_merge($nullData, $this->approvalReset(), [
                'user_id' => $this->userId,
                'user_role' => $this->userRole,
                'kodebpr' => $kodebpr,
                'periode_id' => $periodeId,
            ]));

            if (!$ok)
                return $this->jsonErr('Gagal menyimpan status');

            return $this->jsonOk('Periode berhasil ditandai tidak ada data fraud');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::setNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    public function batalNodataAjax()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->penerapansafstModel->removeNullRecord($kodebpr, $periodeId)) {
                return $this->jsonOk('Status "Tidak Ada Data" berhasil dibatalkan');
            }
            return $this->jsonErr('Gagal membatalkan status');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::batalNodataAjax] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }

    // EXPORT TXT
    public function exporttxtpenerapansafst()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        [$kodebpr, $periodeId] = $this->getKodebprPeriode();
        $periodeDetail = $this->periodeModel->find($periodeId);
        $dataRows = $this->penerapansafstModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $dataInfobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodesektorljk = '';
        if (!empty($dataInfobpr)) {
            $sandibpr = $dataInfobpr[0]['sandibpr'] ?? '';
            $kodesektorljk = $dataInfobpr[0]['kodejenis'] ?? '';
        }

        $kodejenis = $periodeDetail['kodejenis'] ?? '';
        $kodeform = $periodeDetail['kodeform'] ?? '';
        $jenispelaporan = $periodeDetail['jenispelaporan'] ?? 'SAFTS';
        $formType = $this->getFormType($periodeDetail);
        $kodeFormOut = $formType === 'B' ? '01B' : '01A';
        $fields = $this->dataFields($formType);

        // Jumlah kolom tetap: D01 + kode komponen + seluruh field form
        $expectedCols = 2 + count($fields);

        // Periode Data Laporan (YYYY-MM-DD): 30 Juni / 31 Desember
        $periodeDataLaporan = $this->getTanggalLaporan($periodeDetail);

        // Rutin = 0
        $kodeStatusLaporan = '0';

        $sanitize = function ($text) {
            $text = (string) $text;
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['|', '%'], [' ', ' persen '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $fmtDate = function ($val) {
            if (empty($val)) {
                return '';
            }
            return str_replace('-', '', substr((string) $val, 0, 10));
        };

        // Bangun 1 baris D01; $row null/kosong => semua kolom data kosong
        $buildLine = function (?array $row) use ($fields, $sanitize, $fmtDate, $expectedCols) {
            if ($row !== null) {
                $row = $this->normalizeConditional($row);
            }
            $cols = ['D01', self::KODE_KOMPONEN];
            foreach ($fields as $f) {
                $val = $row[$f] ?? null;
                if ($val === null) {
                    $cols[] = '';
                } elseif (in_array($f, self::DATE_FIELDS, true)) {
                    $cols[] = $fmtDate($val);
                } else {
                    $cols[] = $sanitize($val);
                }
            }
            // Pengaman: jumlah kolom selalu sama
            $cols = array_slice(array_pad($cols, $expectedCols, ''), 0, $expectedCols);
            return implode('|', $cols) . "\r\n";
        };

        // Ambil semua baris data nyata (tanpa filter kelengkapan / ID kosong)
        $realRows = [];
        foreach ($dataRows as $row) {
            if (!empty($row['is_null_record'])) {
                continue;
            }
            $realRows[] = $row;
        }

        $output = "H01|{$kodesektorljk}|{$sandibpr}|{$periodeDataLaporan}|{$jenispelaporan}|{$kodeFormOut}|{$kodeStatusLaporan}\r\n";

        if (empty($realRows)) {
            // "Tidak ada data" / belum ada data: 1 baris D01 dengan kolom kosong
            $output .= $buildLine(null);
        } else {
            foreach ($realRows as $row) {
                $output .= $buildLine($row);
            }
        }

        $tanggalFile = str_replace('-', '', $periodeDataLaporan);
        $filename = "{$jenispelaporan}-{$kodeFormOut}-{$kodejenis}-{$kodeform}-{$tanggalFile}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    // IMPOR EXCEL
    public function previewImportExcel()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Kode BPR atau periode tidak valid');
            }

            if ($this->penerapansafstModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $formType = $this->getFormType();
            $fieldsForm = $this->dataFields($formType);
            $moneyForm = $this->moneyFields($formType);

            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                return $this->jsonErr('File tidak valid atau gagal diupload');
            }

            $ext = strtolower($file->getClientExtension());
            if (!in_array($ext, ['xlsx', 'xls'], true)) {
                return $this->jsonErr('Format file harus .xlsx atau .xls');
            }

            if ($file->getSize() > self::IMPORT_MAX_SIZE) {
                return $this->jsonErr('Ukuran file maksimal 3MB');
            }

            $reader = $ext === 'xlsx' ? new XlsxReader() : new XlsReader();
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $startRow = 6;

            if (($highestRow - $startRow + 1) > self::IMPORT_MAX_ROWS) {
                unset($spreadsheet, $sheet);
                return $this->jsonErr('Maksimal ' . self::IMPORT_MAX_ROWS . ' baris per impor.');
            }

            $startCol = Coordinate::columnIndexFromString('C');

            $existingIdKejadian = $this->penerapansafstModel->getIdKejadianByPeriode($kodebpr, $periodeId);
            $existingSet = array_flip($existingIdKejadian);
            $seenInFile = [];
            $preview = [];

            for ($rowIdx = $startRow; $rowIdx <= $highestRow; $rowIdx++) {
                $rowData = [];
                $isEmptyRow = true;

                foreach ($fieldsForm as $i => $fieldName) {
                    $colIdx = $startCol + $i;
                    $cellVal = $sheet->getCell(Coordinate::stringFromColumnIndex($colIdx) . $rowIdx)->getValue();

                    if (in_array($fieldName, self::DATE_FIELDS, true)) {
                        $rowData[$fieldName] = $this->parseExcelDate($cellVal);
                    } elseif (in_array($fieldName, $moneyForm, true)) {
                        $digits = preg_replace('/\D/', '', (string) $cellVal);
                        $rowData[$fieldName] = $digits === '' ? null : (int) $digits;
                    } else {
                        $val = trim((string) ($cellVal ?? ''));
                        $rowData[$fieldName] = $val === '' ? null : $val;
                    }

                    if (!empty($rowData[$fieldName])) {
                        $isEmptyRow = false;
                    }
                }

                if ($isEmptyRow) {
                    continue;
                }

                $rowData = $this->normalizeConditional($rowData);
                $rowErr = $this->missingLabels($rowData, $fieldsForm, $formType);

                $idKejadian = $rowData['idkejadian'] ?? '';
                $isDupDb = $idKejadian !== '' && isset($existingSet[$idKejadian]);
                $isDupFile = $idKejadian !== '' && isset($seenInFile[$idKejadian]);
                if ($idKejadian !== '') {
                    $seenInFile[$idKejadian] = true;
                }

                $status = empty($rowErr) ? 'ok' : 'error';

                $preview[] = array_merge($rowData, [
                    'line' => $rowIdx,
                    'status' => $status,
                    'errors' => $rowErr,
                    'dup_reason' => $isDupDb ? 'ID Kejadian sudah ada di periode ini (baris lanjutan diperbolehkan)' : ($isDupFile ? 'ID Kejadian sama dengan baris lain di file (baris lanjutan diperbolehkan)' : null),
                ]);
            }

            unset($sheet, $spreadsheet, $reader);

            if (empty($preview)) {
                return $this->jsonErr('Tidak ada baris data yang terbaca dari file');
            }

            $okCount = count(array_filter($preview, static fn($p) => $p['status'] === 'ok'));

            return $this->jsonOk('OK', [
                'preview' => $preview,
                'ok_count' => $okCount,
                'total' => count($preview),
                'form_type' => $formType,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::previewImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Gagal membaca file Excel. Pastikan format sesuai template.', 500);
        }
    }

    private function parseExcelDate($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject($raw)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        $ts = strtotime(str_replace('/', '-', trim((string) $raw)));
        return $ts ? date('Y-m-d', $ts) : null;
    }

    public function confirmImportExcel()
    {
        if ($err = $this->requireAjax())
            return $err;
        if ($err = $this->requireAuth())
            return $err;
        if ($err = $this->requireInput())
            return $err;

        try {
            [$kodebpr, $periodeId] = $this->getKodebprPeriode();
            if (!$kodebpr || !$periodeId) {
                return $this->jsonErr('Data tidak valid');
            }

            if ($this->penerapansafstModel->hasNullRecord($kodebpr, $periodeId)) {
                return $this->jsonErr('Batalkan status "Tidak Ada Data" terlebih dahulu sebelum impor');
            }

            $formType = $this->getFormType();
            $fieldsForm = $this->dataFields($formType);

            $rows = json_decode($this->request->getPost('rows') ?? '[]', true);
            if (!is_array($rows) || empty($rows)) {
                return $this->jsonErr('Tidak ada data valid untuk disimpan');
            }
            if (count($rows) > self::IMPORT_MAX_ROWS) {
                return $this->jsonErr('Jumlah baris melebihi batas maksimal');
            }

            $insertBatch = [];

            foreach ($rows as $r) {
                $rowData = [];
                foreach ($fieldsForm as $f) {
                    $val = $r[$f] ?? null;
                    $rowData[$f] = ($val === '' || $val === null) ? null : $val;
                }

                $rowData = $this->normalizeConditional($rowData);

                if ($this->validateRequired($rowData, $formType)) {
                    continue;
                }

                $insertBatch[] = array_merge($rowData, $this->approvalReset(), [
                    'user_id' => $this->userId,
                    'user_role' => $this->userRole,
                    'kodebpr' => $kodebpr,
                    'periode_id' => $periodeId,
                    'is_null_record' => 0,
                ]);
            }

            if (empty($insertBatch)) {
                return $this->jsonErr('Tidak ada data valid yang dapat disimpan');
            }

            $this->db->transStart();
            $this->penerapansafstModel->insertBatchSync($insertBatch);
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonErr('Transaksi gagal, silakan coba kembali');
            }

            return $this->jsonOk(count($insertBatch) . ' data berhasil diimpor dari Excel');

        } catch (\Exception $e) {
            log_message('error', '[Penerapansafst::confirmImportExcel] ' . $e->getMessage());
            return $this->jsonErr('Terjadi kesalahan sistem', 500);
        }
    }
}