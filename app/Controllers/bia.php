<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_bia;
use App\Models\M_Bisnis;

class Bia extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = new M_bia();
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
            'judul' => 'Data Bia',
            'bia' => $this->model->getAllData(),
            'bisnis' => $this->bisnis->getAllData()
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_topbar');
        echo view('bia/index', $data);
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
                'sub_ordinat' => [
                    'label' => 'Sub Ordinat',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'proses_bisnis' => [
                    'label' => 'Proses Bisnis',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'kredit' => [
                    'label' => 'Kredit',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'pasar' => [
                    'label' => 'Pasar',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'likuiditas' => [
                    'label' => 'Likuiditas',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'operasional' => [
                    'label' => 'Operasional',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'kepatuhan' => [
                    'label' => 'Kepatuhan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukum' => [
                    'label' => 'Hukum',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'reputasi' => [
                    'label' => 'Reputasi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'strategi' => [
                    'label' => 'Strategi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val) {
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Data Bia',
                    'bia' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('bia/index', $data);
                echo view('templates/v_footer');
            } else {
                $data = [
                    'sub_ordinat' => $this->request->getPost('sub_ordinat'),
                    'proses_bisnis' => $this->request->getPost('proses_bisnis'),
                    'kredit' => $this->request->getPost('kredit'),
                    'pasar' => $this->request->getPost('pasar'),
                    'liquiditas' => $this->request->getPost('likuiditas'),
                    'operasional' => $this->request->getPost('operasional'),
                    'kepatuhan' => $this->request->getPost('kepatuhan'),
                    'hukum' => $this->request->getPost('hukum'),
                    'reputasi' => $this->request->getPost('reputasi'),
                    'strategi' => $this->request->getPost('strategi'),
                ];
                $data['total'] = (int)$data['kredit'] + (int)$data['pasar'] + (int)$data['liquiditas'] + (int)$data['operasional'] + (int)$data['kepatuhan']
                                 + (int)$data['hukum'] + (int)$data['reputasi'] + (int)$data['strategi'];
        
                // Insert data
                $this->model->checkIncrement();
                $success = $this->model->tambah($data);
                if ($success) {
                    session()->setFlashdata('message', 'Data Bia berhasil ditambahkan ');
                    return redirect()->to(base_url('bia'));
                }
            }
        } else{
            return redirect()->to(base_url('bia'));
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
        return redirect()->to(base_url('bia'));

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
                'sub_ordinat' => [
                    'label' => 'Sub Ordinat',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'proses_bisnis' => [
                    'label' => 'Proses Bisnis',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'kredit' => [
                    'label' => 'Kredit',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'pasar' => [
                    'label' => 'Pasar',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'liquiditas' => [
                    'label' => 'Likuiditas',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'operasional' => [
                    'label' => 'Operasional',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'kepatuhan' => [
                    'label' => 'Kepatuhan',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'hukum' => [
                    'label' => 'Hukum',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'reputasi' => [
                    'label' => 'Reputasi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],
                'strategi' => [
                    'label' => 'Strategi',
                    'rules' => 'required',
                    'errors' => [
                        'required' => '{field} tidak boleh kosong.'
                    ]
                ],

            ]);

            if (!$val){
                session()->setFlashdata('err', \Config\Services::validation()->listErrors());
                $data = [
                    'judul' => 'Data Bia',
                    'bia' => $this->model->getAllData()
                ];
        
                echo view('templates/v_header', $data);
                echo view('templates/v_sidebar');
                echo view('templates/v_topbar');
                echo view('bia/index', $data);
                echo view('templates/v_footer');
            } else {
                $id = $this->request->getPost('id');

                $data = [
                    'sub_ordinat' => $this->request->getPost('sub_ordinat'),
                    'proses_bisnis' => $this->request->getPost('proses_bisnis'),
                    'kredit' => $this->request->getPost('kredit'),
                    'pasar' => $this->request->getPost('pasar'),
                    'liquiditas' => $this->request->getPost('liquiditas'),
                    'operasional' => $this->request->getPost('operasional'),
                    'kepatuhan' => $this->request->getPost('kepatuhan'),
                    'hukum' => $this->request->getPost('hukum'),
                    'reputasi' => $this->request->getPost('reputasi'),
                    'strategi' => $this->request->getPost('strategi'),
                ];

                $data['total'] = (int)$data['kredit'] + (int)$data['pasar'] + (int)$data['liquiditas'] + (int)$data['operasional'] + (int)$data['kepatuhan']
                + (int)$data['hukum'] + (int)$data['reputasi'] + (int)$data['strategi'];
                
                // Update data
                $success = $this->model->ubah($data, $id);
                if ($success) {
                    session()->setFlashdata('message', 'Data Bia berhasil diubah ');
                    return redirect()->to(base_url('bia'));
                }
            }
        } else{
            return redirect()->to(base_url('bia'));
        }
    }

    public function excel()
    {
        $data = [
            'bia' => $this->model->getAllData()
        ];

        echo view('bia/excel', $data);

    }

}


