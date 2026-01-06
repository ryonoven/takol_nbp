<?php
namespace App\Controllers;

use App\Models\M_periodetransparansi;
use App\Models\M_user;
// use Myth\Auth\Config\Services as AuthServices;

class Periodetransparansi extends BaseController
{
    protected $periodetransparansiModel;
    protected $userModel;

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->periodetransparansiModel = new M_periodetransparansi();
        $this->userModel = new M_user();

        if (!session()->has('active_periode')) {
            
        }

    }

    public function index()
    {
        // Redirect jika sudah ada periode aktif        
        $user = $this->userModel->find(user_id());


        $data = [
            'judul' => 'Periode Pelaporan Transparansi Tahunan',
            'periodes' => $this->periodetransparansiModel->getPeriodeByBpr($user['kodebpr']),
            'current_periode' => session('active_periode'),
            'kodebpr' => $user['kodebpr'],
            'validation' => \Config\Services::validation()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('periodetransparansi/index', $data);
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
            'tahun' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Cek duplikasi periode
        $existing = $this->periodetransparansiModel->where([
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun')
        ])->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Periode ini sudah ada');
        }

        $periodeId = $this->periodetransparansiModel->insert([
            'user_id' => user_id(),
            'kodebpr' => $user['kodebpr'],
            'tahun' => $this->request->getPost('tahun')
        ]);

        $this->setActivePeriode($periodeId);

        return redirect()->to('/penjelasanumum')
            ->with('message', 'Periode baru berhasil dibuat');
    }

    public function select()
    {
        $periodeId = $this->request->getPost('periode_id');
        $user = $this->userModel->find(user_id());

        // Validasi kepemilikan periode
        $periode = $this->periodetransparansiModel->where([
            'id' => $periodeId,
            'kodebpr' => $user['kodebpr']
        ])->first();

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode tidak valid');
        }

        $this->setActivePeriode($periodeId);

        // Redirect ke halaman penjelasan umum dengan filter periode
        return redirect()->to('/penjelasanumum')
            ->with('message', 'Periode berhasil dipilih');
    }

    public function switch($id)
    {
        $user = $this->userModel->find(user_id());

        // Validasi kepemilikan periode
        $periode = $this->periodetransparansiModel->where([
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
        session()->set('periode_data', $this->periodetransparansiModel->find($id));
    }

    protected function hasActivePeriode()
    {
        return session()->has('active_periode');
    }
}