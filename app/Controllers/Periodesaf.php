<?php

namespace App\Controllers;

use App\Models\M_periodesaf;
use App\Models\M_user;

/**
 * Periode Laporan Strategi Anti Fraud: buat, edit, dan pilih periode aktif (session 'active_periode').
 * Buat & edit hanya untuk grup PE / admin.
 */
class Periodesaf extends BaseController
{
    protected $periodeModel;
    protected $userModel;

    private const TAHUN_MIN = 2024;
    private const TAHUN_MAX = 2030;
    private const KATEGORI_A_MIN = 50_000_000_000;   // modal inti >= 50 M -> kategori A
    private const NOMORSURAT_MAX = 100;              // panjang kolom periode_saf.nomorsurat

    /** Periode pelaporan => [label, kodeform]. */
    public const JENIS_LAPORAN = [
        'SAFTS' => ['Semesteran', 'S'],
        'SAFTI3' => ['Insidental 3HK', 'I'],
    ];
    public const JENIS_KOREKSI = ['1' => 'Koreksi Laporan Semesteran', '2' => 'Pengkinian Data'];

    public function __construct()
    {
        $this->periodeModel = new M_periodesaf();
        $this->userModel = new M_user();
    }

    // ------------------------------------------------------------------ helpers

    private function kodebpr(): ?string
    {
        return $this->userModel->find(user_id())['kodebpr'] ?? null;
    }

    private function canManage(): bool
    {
        return in_groups(['pe', 'admin']);
    }

    private function findOwn($id): ?array
    {
        $kodebpr = $this->kodebpr();
        if (!$kodebpr || !$id) {
            return null;
        }
        return $this->periodeModel->where(['id' => (int) $id, 'kodebpr' => $kodebpr])->first();
    }

    /** Aturan bersama; $create = true menambah tahun/semester/periode pelaporan. */
    private function rules(bool $create): array
    {
        $rules = [
            'date' => ['rules' => 'required|valid_date[Y-m-d]', 'errors' => ['required' => 'Tanggal pelaporan harus diisi.', 'valid_date' => 'Tanggal pelaporan tidak valid.']],
            'kodejenis' => ['rules' => 'required|in_list[R,K]', 'errors' => ['required' => 'Jenis pelaporan harus dipilih.', 'in_list' => 'Jenis pelaporan tidak valid.']],
            'modalinti' => ['rules' => 'required|numeric|greater_than[0]', 'errors' => ['required' => 'Modal inti harus diisi.', 'numeric' => 'Modal inti harus berupa angka.', 'greater_than' => 'Modal inti harus lebih dari 0.']],
            'totalaset' => ['rules' => 'required|numeric|greater_than[0]', 'errors' => ['required' => 'Total aset harus diisi.', 'numeric' => 'Total aset harus berupa angka.', 'greater_than' => 'Total aset harus lebih dari 0.']],
            'kantorcabang' => ['rules' => 'required|integer|greater_than_equal_to[0]', 'errors' => ['required' => 'Jumlah kantor cabang harus diisi.', 'integer' => 'Jumlah kantor cabang harus bilangan bulat.', 'greater_than_equal_to' => 'Jumlah kantor cabang tidak boleh negatif.']],
        ];

        if ($create) {
            $rules += [
                'tahun' => ['rules' => 'required|integer|greater_than_equal_to[' . self::TAHUN_MIN . ']|less_than_equal_to[' . self::TAHUN_MAX . ']', 'errors' => ['required' => 'Tahun harus dipilih.', 'integer' => 'Tahun tidak valid.', 'greater_than_equal_to' => 'Tahun tidak valid.', 'less_than_equal_to' => 'Tahun tidak valid.']],
                'semester' => ['rules' => 'required|in_list[1,2]', 'errors' => ['required' => 'Semester harus dipilih.', 'in_list' => 'Semester tidak valid.']],
                'jenispelaporan' => ['rules' => 'required|in_list[' . implode(',', array_keys(self::JENIS_LAPORAN)) . ']', 'errors' => ['required' => 'Periode pelaporan harus dipilih.', 'in_list' => 'Periode pelaporan tidak valid.']],
            ];
        }

        if ($this->request->getPost('kodejenis') === 'K') {
            $rules['nomorsurat'] = ['rules' => 'required|max_length[' . self::NOMORSURAT_MAX . ']', 'errors' => ['required' => 'Nomor surat wajib diisi untuk pelaporan Koreksi.', 'max_length' => 'Nomor surat terlalu panjang.']];
            $rules['jeniskoreksi'] = ['rules' => 'required|in_list[1,2]', 'errors' => ['required' => 'Jenis koreksi harus dipilih.', 'in_list' => 'Jenis koreksi tidak valid.']];
        }

        return $rules;
    }

    /** Data bersama create & edit. Kategori dihitung di server dari modal inti. */
    private function formData(): array
    {
        $modalInti = (float) $this->request->getPost('modalinti');
        $isK = $this->request->getPost('kodejenis') === 'K';

        return [
            'date' => (string) $this->request->getPost('date'),
            'kodejenis' => $isK ? 'K' : 'R',
            'modalinti' => $modalInti,
            'totalaset' => (float) $this->request->getPost('totalaset'),
            'kantorcabang' => (int) $this->request->getPost('kantorcabang'),
            'kategori' => $modalInti >= self::KATEGORI_A_MIN ? 'A' : 'B',
            'nomorsurat' => $isK ? trim((string) $this->request->getPost('nomorsurat')) : '',
            'jeniskoreksi' => $isK ? (int) $this->request->getPost('jeniskoreksi') : 0,
        ];
    }

    private function invalid(array $extra = []): \CodeIgniter\HTTP\RedirectResponse
    {
        $r = redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        foreach ($extra as $k => $v) {
            $r->with($k, $v);
        }
        return $r;
    }

    private function setActivePeriode(int $id): void
    {
        session()->set('active_periode', $id);
        session()->set('periode_data', $this->periodeModel->find($id));
    }

    public function index()
    {
        $kodebpr = $this->kodebpr();

        $data = [
            'judul' => 'Periode Laporan Strategi Anti Fraud',
            'periodes' => $kodebpr ? $this->periodeModel->where('kodebpr', $kodebpr)
                ->orderBy('tahun', 'DESC')->orderBy('semester', 'DESC')->orderBy('id', 'DESC')->findAll() : [],
            'current_periode' => (int) session('active_periode'),
            'canManagePeriode' => $this->canManage(),
            'tahunMin' => self::TAHUN_MIN,
            'tahunMax' => self::TAHUN_MAX,
            'jenisLaporan' => self::JENIS_LAPORAN,
            'jenisKoreksi' => self::JENIS_KOREKSI,
            'nomorsuratMax' => self::NOMORSURAT_MAX,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Periode/periodesaf', $data)
            . view('templates/v_footer');
    }

    public function handlePeriode()
    {
        return match ($this->request->getPost('action')) {
            'create' => $this->create(),
            'edit' => $this->edit(),
            'select' => $this->select(),
            default => redirect()->back()->with('error', 'Aksi tidak valid.'),
        };
    }

    protected function create()
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk membuat periode baru.');
        }
        $kodebpr = $this->kodebpr();
        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        if (!$this->validate($this->rules(true))) {
            return $this->invalid();
        }

        $tahun = (int) $this->request->getPost('tahun');
        $semester = (string) $this->request->getPost('semester');
        if ($this->periodeModel->where(['kodebpr' => $kodebpr, 'tahun' => $tahun, 'semester' => $semester])->first()) {
            return redirect()->back()->withInput()
                ->with('error', "Periode Strategi Anti Fraud Semester {$semester} Tahun {$tahun} sudah ada.");
        }

        $jenis = (string) $this->request->getPost('jenispelaporan');
        $id = $this->periodeModel->insert($this->formData() + [
            'user_id' => user_id(),
            'kodebpr' => $kodebpr,
            'tahun' => $tahun,
            'semester' => $semester,
            'jenispelaporan' => $jenis,
            'kodeform' => self::JENIS_LAPORAN[$jenis][1],
        ]);
        if (!$id) {
            return redirect()->back()->withInput()->with('error', 'Periode gagal dibuat.');
        }

        $this->setActivePeriode((int) $id);
        return redirect()->to('/Showsaf')->with('message', "Periode Semester {$semester} Tahun {$tahun} berhasil dibuat.");
    }

    protected function edit()
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengedit periode.');
        }
        $periode = $this->findOwn($this->request->getPost('periode_id'));
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }
        if (!$this->validate($this->rules(false))) {
            return $this->invalid(['edit_id' => (int) $periode['id']]);
        }

        $this->periodeModel->update((int) $periode['id'], $this->formData());

        if ((int) session('active_periode') === (int) $periode['id']) {
            $this->setActivePeriode((int) $periode['id']);
        }
        return redirect()->back()->with('message', "Periode Semester {$periode['semester']} Tahun {$periode['tahun']} berhasil diperbarui.");
    }

    public function select()
    {
        $periode = $this->findOwn($this->request->getPost('periode_id'));
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $this->setActivePeriode((int) $periode['id']);
        return redirect()->to('/Showsaf')->with('message', "Periode Semester {$periode['semester']} Tahun {$periode['tahun']} berhasil dipilih.");
    }

    public function switch($id)
    {
        $periode = $this->findOwn($id);
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $this->setActivePeriode((int) $periode['id']);
        return redirect()->back()->with('message', 'Periode berhasil diubah.');
    }
}
