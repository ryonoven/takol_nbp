<?php
namespace App\Controllers;

use App\Models\M_periode;
use App\Models\M_user;

class Periode extends BaseController
{
    protected $periodeModel;
    protected $userModel;

    public function __construct()
    {
        $this->periodeModel = new M_periode();
        $this->userModel = new M_user();

        if (!session()->has('active_periode')) {
            
        }

    }

    public function index()
    {
        // Redirect jika sudah ada periode aktif        
        $user = $this->userModel->find(user_id());

        $data = [
            'judul' => 'Periode Pelaporan',
            'periodes' => $this->periodeModel->getPeriodeByBpr($user['kodebpr']),
            'current_periode' => session('active_periode'),
            'kodebpr' => $user['kodebpr'],
            'validation' => \Config\Services::validation()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('periode/index', $data);
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
            'semester' => 'required|in_list[1,2]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Cek duplikasi periode
        $existing = $this->periodeModel->where([
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun'),
            'semester' => $this->request->getPost('semester')
        ])->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Periode ini sudah ada');
        }

        $periodeId = $this->periodeModel->insert([
            'user_id' => user_id(),
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun'),
            'semester' => $this->request->getPost('semester')
        ]);

        $this->setActivePeriode($periodeId);

        return redirect()->to('/faktor')
            ->with('message', 'Periode baru berhasil dibuat');
    }

    public function select()
    {
        $periodeId = $this->request->getPost('periode_id');
        $user = $this->userModel->find(user_id());

        // Validasi kepemilikan periode
        $periode = $this->periodeModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr']
        ])->first();

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode tidak valid');
        }

        $this->setActivePeriode($periodeId);

        // Redirect ke halaman faktor dengan filter periode
        return redirect()->to('/faktor')
            ->with('message', 'Periode berhasil dipilih');
    }

    public function switch($id)
    {
        $user = $this->userModel->find(user_id());

        // Validasi kepemilikan periode
        $periode = $this->periodeModel->where([
            'id' => $id,
            'kodebpr' => $user['kodebpr']
        ])->first();

        if (!$periode) {
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
        session()->set('periode_data', $this->periodeModel->find($id));
    }

    protected function hasActivePeriode()
    {
        return session()->has('active_periode');
    }
}