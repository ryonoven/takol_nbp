<?php
namespace App\Controllers;

use App\Models\M_periodetahunan;
use App\Models\M_user;

class Periodetahunan extends BaseController
{
    protected $periodetahunanModel;
    protected $userModel;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->periodetahunanModel = new M_periodetahunan();
        $this->userModel = new M_user();
    }

    public function index()
    {
        $user = $this->userModel->find(user_id());

        $data = [
            'judul' => 'Periode Pelaporan Transparansi Tahunan',
            'periodes' => $this->periodetahunanModel->getPeriodeByBpr($user['kodebpr']),
            'current_periode' => session('active_periode'),
            'kodebpr' => $user['kodebpr'],
            'validation' => \Config\Services::validation(),
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Periode/periodetahunan', $data);
        echo view('templates/v_footer');
    }

    public function handlePeriode()
    {
        $action = $this->request->getPost('action');

        if (!in_array($action, ['create', 'select', 'edit'])) {
            return redirect()->back()->with('error', 'Aksi tidak valid');
        }

        return $this->$action();
    }

    protected function create()
    {
        $user = $this->userModel->find(user_id());

        $rules = [
            'tahun' => 'required|integer|greater_than[2023]|less_than[2031]',
            'modalinti' => 'required|numeric|greater_than[0]',
            'totalaset' => 'required|numeric|greater_than[0]',
            'kategori' => 'permit_empty|in_list[A,B]',
            'jenispelaporan' => 'required|in_list[R,K]',
            // 'jeniskoreksi' => 'permit_empty',
        ];

        $messages = [
            'tahun' => [
                'required' => 'Tahun harus dipilih.',
                'integer' => 'Tahun tidak valid.',
                'greater_than' => 'Tahun minimal 2024.',
                'less_than' => 'Tahun maksimal 2030.',
            ],
            'modalinti' => [
                'required' => 'Modal inti harus diisi.',
                'numeric' => 'Modal inti harus berupa angka.',
                'greater_than' => 'Modal inti harus lebih dari 0.',
            ],
            'totalaset' => [
                'required' => 'Total aset harus diisi.',
                'numeric' => 'Total aset harus berupa angka.',
                'greater_than' => 'Total aset harus lebih dari 0.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $tahun = (int) $this->request->getPost('tahun');
        $modalInti = (float) $this->request->getPost('modalinti');
        $totalAset = (float) $this->request->getPost('totalaset');
        $kategori = $this->request->getPost('kategori') ?: $this->hitungKategori($modalInti);
        $jenispelaporan = $this->request->getPost('jenispelaporan') ?: 'R';
        $jeniskoreksi = $jenispelaporan === 'K' ? ($this->request->getPost('jeniskoreksi') ?: null) : null;

        $existing = $this->periodetahunanModel->where([
            'kodebpr' => $user['kodebpr'],
            'tahun' => $tahun,
        ])->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Periode tahun ' . $tahun . ' sudah ada.');
        }

        $periodeId = $this->periodetahunanModel->insert([
            'user_id' => user_id(),
            'kodebpr' => $user['kodebpr'],
            'tahun' => $tahun,
            'modalinti' => $modalInti,
            'totalaset' => $totalAset,
            'kategori' => $kategori,
            'jenispelaporan' => $jenispelaporan,
            // 'jeniskoreksi' => $jeniskoreksi,
        ]);

        $this->setActivePeriode($periodeId);

        return redirect()->to('/Showtahunan')
            ->with('message', 'Periode tahun ' . $tahun . ' berhasil dibuat.');
    }

    protected function edit()
    {
        $user = $this->userModel->find(user_id());
        $periodeId = $this->request->getPost('periode_id');

        $periode = $this->periodetahunanModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr'],
        ])->first();

        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $rules = [
            'modalinti' => 'required|numeric|greater_than[0]',
            'totalaset' => 'required|numeric|greater_than[0]',
            'jenispelaporan' => 'required|in_list[R,K]',
            // 'jeniskoreksi' => 'permit_empty',
        ];

        $messages = [
            'modalinti' => ['required' => 'Modal inti harus diisi.', 'numeric' => 'Modal inti harus berupa angka.', 'greater_than' => 'Modal inti harus lebih dari 0.'],
            'totalaset' => ['required' => 'Total aset harus diisi.', 'numeric' => 'Total aset harus berupa angka.', 'greater_than' => 'Total aset harus lebih dari 0.'],
            'jenispelaporan' => ['required' => 'Jenis pelaporan harus dipilih.', 'in_list' => 'Jenis pelaporan tidak valid.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $modalInti = (float) $this->request->getPost('modalinti');
        $totalAset = (float) $this->request->getPost('totalaset');
        $jenispelaporan = $this->request->getPost('jenispelaporan');
        // $jeniskoreksi = $jenispelaporan === 'K' ? ($this->request->getPost('jeniskoreksi') ?: null) : null;
        $kategori = $this->request->getPost('kategori') ?: $this->hitungKategori($modalInti);

        $this->periodetahunanModel->update($periodeId, [
            'modalinti' => $modalInti,
            'totalaset' => $totalAset,
            'kategori' => $kategori,
            'jenispelaporan' => $jenispelaporan,
            // 'jeniskoreksi' => $jeniskoreksi,
        ]);

        if (session('active_periode') == $periodeId) {
            $this->setActivePeriode($periodeId);
        }

        return redirect()->back()
            ->with('message', 'Periode tahun ' . $periode['tahun'] . ' berhasil diperbarui.');
    }

    public function select()
    {
        $periodeId = $this->request->getPost('periode_id');
        $user = $this->userModel->find(user_id());

        $periode = $this->periodetahunanModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr'],
        ])->first();

        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $this->setActivePeriode($periodeId);

        return redirect()->to('/Showtahunan')
            ->with('message', 'Periode berhasil dipilih.');
    }

    public function switch($id)
    {
        $user = $this->userModel->find(user_id());

        $periode = $this->periodetahunanModel->where([
            'id' => $id,
            'kodebpr' => $user['kodebpr'],
        ])->first();

        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $this->setActivePeriode($id);

        return redirect()->back()->with('message', 'Periode berhasil diubah.');
    }

    protected function setActivePeriode($id)
    {
        session()->set('active_periode', $id);
        session()->set('periode_data', $this->periodetahunanModel->find($id));
    }

    protected function hasActivePeriode()
    {
        return session()->has('active_periode');
    }

    protected function hitungKategori(float $modalInti): string
    {
        return $modalInti >= 50_000_000_000 ? 'A' : 'B';
    }
}