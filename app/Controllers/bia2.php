<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_bia2;
use App\Models\M_Bisnis;

class Bia2 extends Controller
{
    protected $model; 
    public function __construct()
    {
        $this->model = new M_bia2();
        $this->bisnis = new M_Bisnis();
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
            'judul' => 'Data Bia Lanjutan',
            'bia2' => $this->model->getAllData(),
            'bisnis' => $this->bisnis->getAllData()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('bia2/index', $data);
        echo view('templates/v_footer');

        
    }
    
    public function tambah()
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()   
                ->to($redirectURL);
        }

        if (isset($_POST['tambah'])){
            $val = $this->validate([
                'appsti' => [
                    'label' => 'Aplikasi TI yang digunakan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'rto' => [
                    'label' => 'RTO',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'datahasil' => [
                    'label' => 'Data yang dihasilkan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'rpo' => [
                    'label' => 'RPO',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'mtd' => [
                    'label' => 'MTD',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'puncak' => [
                    'label' => 'Peaktime',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Data Bia Lanjutan',
                    'bia2' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('bia2/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'appsti' => $this->request->getPost('appsti'),
                    'rto' => $this->request->getPost('rto'),
                    'datahasil' => $this->request->getPost('datahasil'),
                    'rpo' => $this->request->getPost('rpo'),
                    'mtd' => $this->request->getPost('mtd'),
                    'puncak' => $this->request->getPost('puncak'),
                ];
                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambah($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data Bia Lanjutan berhasil ditambahkan ');
                    return redirect()->to(base_url('bia2'));
                }
            }
        } else{
            return redirect()->to(base_url('bia2'));
        }
    }

    public function hapus($id)
    {

        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()   
                ->to($redirectURL);
        }

        // Memanggil fungsi hapus pada model dan menyimpan hasilnya dalam variabel $success
        $this->model->hapus($id);
        session()->setFlashdata('message', 'Data berhasil dihapus');
        
            // Redirect pengguna ke halaman "/bisnis"
        return redirect()->to(base_url('bia2'));

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
                'appsti' => [
                    'label' => 'Aplikasi TI yang digunakan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'rto' => [
                    'label' => 'RTO',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'datahasil' => [
                    'label' => 'Data yang dihasilkan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'rpo' => [
                    'label' => 'RPO',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'mtd' => [
                    'label' => 'MTD',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'puncak' => [
                    'label' => 'Peaktime',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val){
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Data Bia Lanjutan',
                    'bia2' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('bia2/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'appsti' => $this->request->getPost('appsti'),
                    'rto' => $this->request->getPost('rto'),
                    'datahasil' => $this->request->getPost('datahasil'),
                    'rpo' => $this->request->getPost('rpo'),
                    'mtd' => $this->request->getPost('mtd'),
                    'puncak' => $this->request->getPost('puncak'),
                ];
        
                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data Bia Lanjutan berhasil diubah ');
                    return redirect()->to(base_url('bia2'));
                }
            }
        } else{
            return redirect()->to(base_url('bia2'));
        }
    }

    public function excel()
    {
        $data = [
            'bia2' => $this->model->getAllData()
        ];

        echo view('bia2/excel', $data);

    }

}

