<?php
namespace App\Controllers;

use App\Models\M_periodetks;
use App\Models\M_user;
use Myth\Auth\Config\Services as AuthServices;

class Periodetks extends BaseController
{
    protected $periodetksModel;
    protected $userModel;

    public function __construct()
    {
        $this->periodetksModel = new M_periodetks();
        $this->userModel = new M_user();

        if (!session()->has('active_periode')) {

        }
    }

    public function index()
    {
        $user = $this->userModel->find(user_id());

        $data = [
            'judul' => 'Periode Pelaporan Tingkat Kesehatan Bank (TKS)',
            'periodes' => $this->periodetksModel->getPeriodeByBpr($user['kodebpr']),
            'current_periode' => session('active_periode'),
            'kodebpr' => $user['kodebpr'],
            'validation' => \Config\Services::validation()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('periodetks/index', $data);
        echo view('templates/v_footer');
    }

    public function handlePeriode()
    {
        $action = $this->request->getPost('action');

        if (!in_array($action, ['create', 'select'])) {
            return redirect()->back()->with('error', 'Aksi tidak valid');
        }

        return $this->$action();
    }

    protected function create()
    {
        $user = $this->userModel->find(user_id());

        $rules = [
            'tahun' => 'required',
            'semester' => 'required|in_list[1,2]',
            'jenispelaporan' => 'required',
            'modalinti' => 'required',
            'totalaset' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Cek duplikasi periodetks
        $existing = $this->periodetksModel->where([
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun'),
            'semester' => $this->request->getPost('semester'),
            'jenispelaporan' => $this->request->getPost('jenispelaporan'),
            'modalinti' => $this->request->getPost('modalinti'),
            'totalaset' => $this->request->getPost('totalaset')

        ])->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Periode ini sudah ada');
        }

        $periodeId = $this->periodetksModel->insert([
            'user_id' => user_id(),
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun'),
            'semester' => $this->request->getPost('semester'),
            'jenispelaporan' => $this->request->getPost('jenispelaporan'),
            'modalinti' => $this->request->getPost('modalinti'),
            'totalaset' => $this->request->getPost('totalaset')         
        ]);

        $this->setActivePeriode($periodeId);

        return redirect()->to('/Showtks')
            ->with('message', 'Periode baru berhasil dibuat');
    }

    public function select()
    {
        $periodeId = $this->request->getPost('periode_id');
        $user = $this->userModel->find(user_id());

        // Validasi kepemilikan tks
        $periodetks = $this->periodetksModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr']
        ])->first();

        if (!$periodetks) {
            return redirect()->back()
                ->with('error', 'Periode tidak valid');
        }

        $this->setActivePeriode($periodeId);

        // Redirect ke halaman faktor dengan filter periodetks
        return redirect()->to('/Showtks')
            ->with('message', 'Periode berhasil dipilih');
    }

    public function switch($id)
    {
        $user = $this->userModel->find(user_id());

        // Validasi kepemilikan periodetks
        $periodetks = $this->periodetksModel->where([
            'id' => $id,
            'kodebpr' => $user['kodebpr']
        ])->first();

        if (!$periodetks) {
            return redirect()->back()
                ->with('error', 'Periode tidak valid');
        }

        $this->setActivePeriode($id);

        return redirect()->back()
            ->with('message', 'Periode berhasil diubah');
    }

    protected function setActivePeriode($id)
    {
        session()->set('active_periode', $id);
        session()->set('periode_data', $this->periodetksModel->find($id));
    }

    protected function hasActivePeriode()
    {
        return session()->has('active_periode');
    }
}