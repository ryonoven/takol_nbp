<?php
namespace App\Controllers;

use App\Models\M_faktor12_data;
use App\Models\M_faktor12_approval;
use App\Models\M_faktor12_komentar;
use Myth\Auth\Config\Services as AuthServices;

class Faktor12Controller extends BaseController
{
    protected $model;
    protected $auth;
    protected $faktor12dataModel;
    protected $faktor12approvalModel;
    protected $faktor12komentarModel;
    protected $session;
    public function __construct()
    {
        $this->faktor12dataModel = new M_faktor12_data();
        $this->faktor12approvalModel = new M_faktor12_approval();
        $this->faktor12komentarModel = new M_faktor12_komentar();
        $this->session = service('session');
        $this->auth = service('authentication');
        $auth = AuthServices::authentication();
        $authorize = AuthServices::authorization();
    }

    // Menampilkan semua data faktor
    public function index()
    {
        $data['faktor12_data'] = $this->faktor12DataModel->findAll();
        return view('faktor/index', $data);
    }

    // Menambahkan data faktor baru
    public function tambahFaktor()
    {
        if ($this->request->getMethod() === 'post') {
            $data = [
                'masuktxt' => $this->request->getPost('masuktxt'),
                'flagdetail' => $this->request->getPost('flagdetail'),
                'penggunaan' => $this->request->getPost('penggunaan'),
                'plusmin' => $this->request->getPost('plusmin'),
                'number' => $this->request->getPost('number'),
                'sph' => $this->request->getPost('sph'),
                'category' => $this->request->getPost('category'),
                'sub_category' => $this->request->getPost('sub_category')
            ];

            $this->faktorDataModel->save($data);
            return redirect()->to('/faktor');
        }

        return view('faktor/tambah');
    }

    // Menambahkan data approval
    public function tambahApproval($faktorId)
    {
        if ($this->request->getMethod() === 'post') {
            $data = [
                'faktor_id' => $faktorId,
                'nilai' => $this->request->getPost('nilai'),
                'keterangan' => $this->request->getPost('keterangan'),
                'is_approved' => $this->request->getPost('is_approved'),
                'approved_by' => 1,  // ID user yang mengapprove (contoh: 1)
                'approved_at' => date('Y-m-d H:i:s')
            ];

            $this->faktorApprovalModel->save($data);
            return redirect()->to('/faktor');
        }

        return view('faktor/approval');
    }

    // Menambahkan komentar untuk faktor
    public function tambahKomentar($faktorId)
    {
        if ($this->request->getMethod() === 'post') {
            $data = [
                'faktor_id' => $faktorId,
                'komentar' => $this->request->getPost('komentar'),
                'created_by' => 1  // ID user yang memberikan komentar (contoh: 1)
            ];

            $this->faktorKomentarModel->save($data);
            return redirect()->to('/faktor');
        }

        return view('faktor/komentar');
    }
}
