<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_Bisnis;

class Bisnis extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = new M_Bisnis();
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
            'judul' => 'Proses Bisnis',
            'bisnis' => $this->model->getAllData()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('bisnis/index', $data);
        //echo view('templates/v_footer');
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
                'ordinat' => [
                    'label' => 'Sub Ordinat',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'bis' => [
                    'label' => 'Form Bisnis',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'lain' => [
                    'label' => 'Keterkaitan Dengan Unit Kerja Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'ketiga' => [
                    'label' => 'Keterkaitan Dengan Pihak Ketiga',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hasil' => [
                    'label' => 'Data Yang Dihasilkan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'apps' => [
                    'label' => 'Aplikasi Yang Digunakan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Form Bisnis',
                    'bisnis' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('bisnis/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'ordinat' => $this->request->getPost('ordinat'),
                    'bis' => $this->request->getPost('bis'),
                    'lain' => $this->request->getPost('lain'),
                    'ketiga' => $this->request->getPost('ketiga'),
                    'hasil' => $this->request->getPost('hasil'),
                    'apps' => $this->request->getPost('apps')
                ];
        
                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambah($data);
                if ($success) {
                    session()->setFlashdata('message', 'Form bisnis berhasil ditambahkan ');
                    return redirect()->to(base_url('bisnis'));
                }
            }
        } else{
            return redirect()->to(base_url('bisnis'));
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
        return redirect()->to(base_url('bisnis'));

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
                'ordinat' => [
                    'label' => 'Sub Ordinat',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'bis' => [
                    'label' => 'Form Bisnis',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'lain' => [
                    'label' => 'Keterkaitan Dengan Unit Kerja Lain',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'ketiga' => [
                    'label' => 'Keterkaitan Dengan Pihak Ketiga',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hasil' => [
                    'label' => 'Data Yang Dihasilkan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'apps' => [
                    'label' => 'Aplikasi Yang Digunakan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val){
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Form Bisnis',
                    'bisnis' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('bisnis/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'ordinat' => $this->request->getPost('ordinat'),
                    'bis' => $this->request->getPost('bis'),
                    'lain' => $this->request->getPost('lain'),
                    'ketiga' => $this->request->getPost('ketiga'),
                    'hasil' => $this->request->getPost('hasil'),
                    'apps' => $this->request->getPost('apps')
                ];
        
                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Form bisnis berhasil diubah ');
                    return redirect()->to(base_url('bisnis'));
                }
            }
        } else{
            return redirect()->to(base_url('bisnis'));
        }
    }

    public function excel()
    {
        $data = [
            'bisnis' => $this->model->getAllData()
        ];

        echo view('bisnis/excel', $data);

    }
}
