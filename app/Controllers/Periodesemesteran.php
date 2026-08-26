<?php
namespace App\Controllers;

use App\Models\M_periodesemesteran;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class Periodesemesteran extends BaseController
{
    protected $periodesemesteranModel;
    protected $userModel;
    protected $userGroups = [];

    public function __construct()
    {
        $this->periodesemesteranModel = new M_periodesemesteran();
        $this->userModel = new M_user();
        $this->initializeUserGroups();
    }

    private function initializeUserGroups()
    {
        $userId = user_id();
        if (!$userId) {
            return;
        }

        $groups = ['pe', 'admin'];
        $authorize = AuthServices::authorization();
        foreach ($groups as $group) {
            $this->userGroups[$group] = $authorize->inGroup($group, $userId);
        }
    }

    private function canManagePeriode(): bool
    {
        return ($this->userGroups['pe'] ?? false) || ($this->userGroups['admin'] ?? false);
    }

    public function index()
    {
        $user = $this->userModel->find(user_id());

        $data = [
            'judul'            => 'Periode Pelaporan Profil Risiko',
            'periodes'         => $this->periodesemesteranModel->getPeriodeByBpr($user['kodebpr']),
            'current_periode'  => session('active_periode'),
            'kodebpr'          => $user['kodebpr'],
            'validation'       => \Config\Services::validation(),
            'canManagePeriode' => $this->canManagePeriode(),
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Periode/periodesemesteran', $data);
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
        if (!$this->canManagePeriode()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk membuat periode baru.');
        }

        $user = $this->userModel->find(user_id());
        $jenispelaporan = $this->request->getPost('jenispelaporan');

        $rules = [
            'tahun' => 'required',
            'semester' => 'required|in_list[1,2]',
            'jenispelaporan' => 'required',
            'modalinti' => 'required',
            'totalaset' => 'required',
            'kantorcabang' => 'required',
            'atmdebit' => 'required',
        ];

        if ($jenispelaporan === 'K') {
            $rules['nomorsurat'] = 'required';
            $rules['jeniskoreksi'] = 'required|in_list[1,2]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $existing = $this->periodesemesteranModel->where([
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun'),
            'semester' => $this->request->getPost('semester'),
        ])->first();

        if ($existing) {
            return redirect()->back()->withInput()
                ->with('error', 'Periode Semester ' . $this->request->getPost('semester') . ' Tahun ' . $this->request->getPost('tahun') . ' sudah ada.');
        }

        $periodeId = $this->periodesemesteranModel->insert([
            'user_id' => user_id(),
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun'),
            'semester' => $this->request->getPost('semester'),
            'jenispelaporan' => $jenispelaporan,
            'modalinti' => $this->request->getPost('modalinti'),
            'totalaset' => $this->request->getPost('totalaset'),
            'kantorcabang' => $this->request->getPost('kantorcabang'),
            'atmdebit' => $this->request->getPost('atmdebit'),
            'kategori' => $this->request->getPost('kategori'),
            'nomorsurat' => $jenispelaporan === 'K' ? $this->request->getPost('nomorsurat') : '',
            'jeniskoreksi' => $jenispelaporan === 'K' ? (int) $this->request->getPost('jeniskoreksi') : 0,
        ]);

        $this->setActivePeriode($periodeId);

        return redirect()->to('/Showsemesteran')->with('message', 'Periode baru berhasil dibuat');
    }

    protected function edit()
    {
        if (!$this->canManagePeriode()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengedit periode.');
        }

        $user = $this->userModel->find(user_id());
        $periodeId = $this->request->getPost('periode_id');

        $periode = $this->periodesemesteranModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr'],
        ])->first();

        if (!$periode) {
            return redirect()->back()->with('error', 'Periode tidak valid.');
        }

        $rules = [
            'modalinti' => 'required|numeric|greater_than[0]',
            'totalaset' => 'required|numeric|greater_than[0]',
            'kantorcabang' => 'required|integer|greater_than_equal_to[0]',
            'atmdebit' => 'required|in_list[1,2]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $modalInti = (float) $this->request->getPost('modalinti');
        $totalAset = (float) $this->request->getPost('totalaset');
        $kantorcabang = (int)   $this->request->getPost('kantorcabang');
        $atmdebit = (int)   $this->request->getPost('atmdebit');
        $kategori = $this->request->getPost('kategori') ?: ($modalInti >= 50_000_000_000 ? 'A' : 'B');

        $this->periodesemesteranModel->update($periodeId, [
            'modalinti' => $modalInti,
            'totalaset' => $totalAset,
            'kantorcabang' => $kantorcabang,
            'atmdebit' => $atmdebit,
            'kategori' => $kategori,
        ]);

        if (session('active_periode') == $periodeId) {
            $this->setActivePeriode($periodeId);
        }

        return redirect()->back()
            ->with('message', 'Periode Semester ' . $periode['semester'] . ' Tahun ' . $periode['tahun'] . ' berhasil diperbarui.');
    }

    public function select()
    {
        $periodeId = $this->request->getPost('periode_id');
        $user = $this->userModel->find(user_id());

        $periodesemesteran = $this->periodesemesteranModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr'],
        ])->first();

        if (!$periodesemesteran) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $this->setActivePeriode($periodeId);

        return redirect()->to('/Showsemesteran')->with('message', 'Periode berhasil dipilih');
    }

    public function switch($id)
    {
        $user = $this->userModel->find(user_id());
        $periodesemesteran = $this->periodesemesteranModel->where([
            'id'      => $id,
            'kodebpr' => $user['kodebpr'],
        ])->first();

        if (!$periodesemesteran) {
            return redirect()->back()->with('error', 'Periode tidak valid');
        }

        $this->setActivePeriode($id);

        return redirect()->back()->with('message', 'Periode berhasil diubah');
    }

    protected function setActivePeriode($id)
    {
        session()->set('active_periode', $id);
        session()->set('periode_data', $this->periodesemesteranModel->find($id));
    }

    protected function hasActivePeriode()
    {
        return session()->has('active_periode');
    }
}