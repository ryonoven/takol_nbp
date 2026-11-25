<?php

namespace App\Controllers;

use App\Models\M_periodetahunan;
use App\Models\M_user;

/**
 * Periode Laporan Tahunan: buat, edit, dan pilih periode aktif (session 'active_periode').
 * Jenis pelaporan Koreksi (K) wajib menyertakan nomor surat.
 */
class Periodetahunan extends BaseController
{
    protected $periodeModel;
    protected $userModel;

    private const TAHUN_MIN = 2024;
    private const TAHUN_MAX = 2030;
    private const NOMORSURAT_MAX = 30;           // panjang kolom periode_tahunan.nomorsurat

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->periodeModel = new M_periodetahunan();
        $this->userModel = new M_user();
    }

    // ------------------------------------------------------------------ helpers

    private function kodebpr(): ?string
    {
        return $this->userModel->find(user_id())['kodebpr'] ?? null;
    }

    /** Periode milik BPR user, atau null. */
    private function findOwn($id): ?array
    {
        $kodebpr = $this->kodebpr();
        if (!$kodebpr || !$id) {
            return null;
        }
        return $this->periodeModel->where(['id' => (int) $id, 'kodebpr' => $kodebpr])->first();
    }

    /** Aturan validasi bersama create & edit. */
    private function rules(bool $withTahun): array
    {
        $rules = [
            'modalinti' => [
                'rules' => 'required|numeric|greater_than[0]',
                'errors' => ['required' => 'Modal inti harus diisi.', 'numeric' => 'Modal inti harus berupa angka.', 'greater_than' => 'Modal inti harus lebih dari 0.'],
            ],
            'totalaset' => [
                'rules' => 'required|numeric|greater_than[0]',
                'errors' => ['required' => 'Total aset harus diisi.', 'numeric' => 'Total aset harus berupa angka.', 'greater_than' => 'Total aset harus lebih dari 0.'],
            ],
            'jenispelaporan' => [
                'rules' => 'required|in_list[R,K]',
                'errors' => ['required' => 'Jenis pelaporan harus dipilih.', 'in_list' => 'Jenis pelaporan tidak valid.'],
            ],
        ];

        if ($withTahun) {
            $rules['tahun'] = [
                'rules' => 'required|integer|greater_than_equal_to[' . self::TAHUN_MIN . ']|less_than_equal_to[' . self::TAHUN_MAX . ']',
                'errors' => ['required' => 'Tahun harus dipilih.', 'integer' => 'Tahun tidak valid.',
                    'greater_than_equal_to' => 'Tahun minimal ' . self::TAHUN_MIN . '.', 'less_than_equal_to' => 'Tahun maksimal ' . self::TAHUN_MAX . '.'],
            ];
        }

        if ($this->request->getPost('jenispelaporan') === 'K') {
            $rules['nomorsurat'] = [
                'rules' => 'required|max_length[' . self::NOMORSURAT_MAX . ']',
                'errors' => ['required' => 'Nomor surat wajib diisi untuk pelaporan Koreksi.', 'max_length' => 'Nomor surat maksimal ' . self::NOMORSURAT_MAX . ' karakter.'],
            ];
        }

        return $rules;
    }

    /**
     * Data yang disimpan dari form (create & edit).
     * kategori = jenis pelaporan (R = Rutin, K = Koreksi); nomor surat dikosongkan bila Rutin.
     */
    private function formData(): array
    {
        $jenis = (string) $this->request->getPost('jenispelaporan');

        return [
            'modalinti' => (float) $this->request->getPost('modalinti'),
            'totalaset' => (float) $this->request->getPost('totalaset'),
            'kategori' => $jenis,
            'jenispelaporan' => $jenis,
            'nomorsurat' => $jenis === 'K' ? trim((string) $this->request->getPost('nomorsurat')) : '',
        ];
    }

    private function invalid(): \CodeIgniter\HTTP\RedirectResponse
    {
        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }

    private function setActivePeriode(int $id): void
    {
        session()->set('active_periode', $id);
        session()->set('periode_data', $this->periodeModel->find($id));
    }

    // ------------------------------------------------------------------ halaman

    public function index()
    {
        $kodebpr = $this->kodebpr();

        $data = [
            'judul' => 'Periode Pelaporan Transparansi Tahunan',
            'periodes' => $kodebpr ? $this->periodeModel->where('kodebpr', $kodebpr)->orderBy('tahun', 'DESC')->findAll() : [],
            'current_periode' => (int) session('active_periode'),
            'tahunMin' => self::TAHUN_MIN,
            'tahunMax' => self::TAHUN_MAX,
            'nomorsuratMax' => self::NOMORSURAT_MAX,
        ];

        return view('templates/v_header', $data)
            . view('templates/v_sidebar')
            . view('templates/v_topbar')
            . view('Periode/periodetahunan', $data)
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
        $kodebpr = $this->kodebpr();
        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }
        if (!$this->validate($this->rules(true))) {
            return $this->invalid();
        }

        $tahun = (int) $this->request->getPost('tahun');
        if ($this->periodeModel->where(['kodebpr' => $kodebpr, 'tahun' => $tahun])->first()) {
            return redirect()->back()->withInput()->with('error', 'Periode tahun ' . $tahun . ' sudah ada.');
        }

        $id = $this->periodeModel->insert($this->formData() + [
            'user_id' => user_id(),
            'kodebpr' => $kodebpr,
            'tahun' => $tahun,
        ]);
        if (!$id) {
            return redirect()->back()->withInput()->with('error', 'Periode gagal dibuat.');
        }

        $this->setActivePeriode((int) $id);
        return redirect()->to('/Showtahunan')->with('message', 'Periode tahun ' . $tahun . ' berhasil dibuat.');
    }

    protected function edit()
    {
        $periode = $this->findOwn($this->request->getPost('periode_id'));
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }
        if (!$this->validate($this->rules(false))) {
            return $this->invalid()->with('edit_id', (int) $periode['id']);
        }

        $this->periodeModel->update((int) $periode['id'], $this->formData());

        if ((int) session('active_periode') === (int) $periode['id']) {
            $this->setActivePeriode((int) $periode['id']);
        }
        return redirect()->back()->with('message', 'Periode tahun ' . $periode['tahun'] . ' berhasil diperbarui.');
    }

    public function select()
    {
        $periode = $this->findOwn($this->request->getPost('periode_id'));
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $this->setActivePeriode((int) $periode['id']);
        return redirect()->to('/Showtahunan')->with('message', 'Periode tahun ' . $periode['tahun'] . ' berhasil dipilih.');
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
