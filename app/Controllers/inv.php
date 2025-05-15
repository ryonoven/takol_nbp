<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_inv;

class Inv extends Controller
{
    protected $model;
    public function __construct()
    {
        $this->model = new M_inv();
        $this->session = service('session');
        $this->auth   = service('authentication');
    }

    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $data = [
            'judul' => 'Data Inventaris',
            'inv' => $this->model->getAllData()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('inv/index', $data);
        echo view('templates/v_footer');

        
    }
    
    public function tambahI()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()   
                ->to($redirectURL);
        }

        if (isset($_POST['tambahI'])){
            $val = $this->validate([
                'namadat' => [
                    'label' => 'Nama Data',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'media' => [
                    'label' => 'Media Penyimpanan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'lokasi' => [
                    'label' => 'Lokasi Penyimpanan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'utgjawab' => [
                    'label' => 'Unit Kerja Penanggung Jawab',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'keterangan' => [
                    'label' => 'Keterangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Data Inventaris',
                    'inv' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('inv/index', $data);
                echo view('templates/v_footer');
            } else {   
                $data = [
                    'namadat' => $this->request->getPost('namadat'),
                    'media' => $this->request->getPost('media'),
                    'lokasi' => $this->request->getPost('lokasi'),
                    'utgjawab' => $this->request->getPost('utgjawab'),
                    'keterangan' => $this->request->getPost('keterangan'),
                ];
        
                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambahI($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data Inventaris berhasil ditambahkan ');
                    return redirect()->to(base_url('inv'));
                }
            }
        } else{
            return redirect()->to(base_url('inv'));
        }
    }

    public function hapus($id)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');

        return redirect()->to(base_url('inv'));

    }

    public function ubah()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()   
                ->to($redirectURL);
        }

        if (isset($_POST['ubah'])){
            $val = $this->validate([
                'namadat' => [
                    'label' => 'Nama Data',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'media' => [
                    'label' => 'Media Penyimpanan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'lokasi' => [
                    'label' => 'Lokasi Penyimpanan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'utgjawab' => [
                    'label' => 'Unit Kerja Penanggung Jawab',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'keterangan' => [
                    'label' => 'Keterangan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val){
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Data Inventaris',
                    'inv' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('inv/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'namadat' => $this->request->getPost('namadat'),
                    'media' => $this->request->getPost('media'),
                    'lokasi' => $this->request->getPost('lokasi'),
                    'utgjawab' => $this->request->getPost('utgjawab'),
                    'keterangan' => $this->request->getPost('keterangan')
                ];
        
                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data Inventaris berhasil diubah ');
                    return redirect()->to(base_url('inv'));
                }
            }
        } else{
            return redirect()->to(base_url('inv'));
        }
    }

    public function excel()
    {
        $data = [
            'inv' => $this->model->getAllData()
        ];

        echo view('inv/excel', $data);

    }

}


