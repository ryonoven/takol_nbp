<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_infobpr;

class infobpr extends Controller
{
    protected $model;
    public function __construct()
    {
        $this->model = new M_infobpr();
        $this->session = service('session');
        $this->auth = service('authentication');
    }

    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $userId = $this->auth->id();
        $userModel = new \App\Models\M_user();
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid');
        }

        $data = [
            'judul' => 'Informasi BPR',
            'infobpr' => $this->model->getBprByKode($kodebpr),
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('infobpr/index', $data);
        echo view('templates/v_footer');
    }

    public function simpaninfo()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        $id = $this->request->getPost('id');
        $file = $this->request->getFile('logo');

        // Validasi logo hanya jika ada upload
        $logoRules = '';
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $logoRules = 'max_size[logo,2048]|mime_in[logo,image/png,image/jpg,image/jpeg]';
        }

        // Aturan validasi lainnya
        $validationRules = [
            'kodebpr' => [
                'label' => 'Kode BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'namabpr' => [
                'label' => 'Nama BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'alamat' => [
                'label' => 'Alamat BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'nomor' => [
                'label' => 'Nomor Telepon BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'sandibpr' => [
                'label' => 'Sandi BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'jenis' => [
                'label' => 'Jenis Lembaga',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'kategori' => [
                'label' => 'Kategori BPR',
                'rules' => 'required',
                'errors' => ['required' => '{field} tidak boleh kosong.']
            ],
            'email' => [
                'label' => 'Email BPR',
                'rules' => 'permit_empty|valid_email',
                'errors' => ['valid_email' => '{field} tidak valid.']
            ],
            'webbpr' => [
                'label' => 'Website BPR',
                'rules' => 'permit_empty|valid_url',
                'errors' => ['valid_url' => '{field} tidak valid.']
            ],
        ];

        // Tambah validasi logo jika ada upload
        if ($logoRules !== '') {
            $validationRules['logo'] = $logoRules;
        }

        if ($this->validate($validationRules)) {
            $data = [
                'kodebpr' => $this->request->getPost('kodebpr'),
                'namabpr' => $this->request->getPost('namabpr'),
                'alamat' => $this->request->getPost('alamat'),
                'nomor' => $this->request->getPost('nomor'),
                'sandibpr' => $this->request->getPost('sandibpr'),
                'jenis' => $this->request->getPost('jenis'),
                'kodejenis' => $this->request->getPost('kodejenis'),
                'kategori' => $this->request->getPost('kategori'),
                'email' => $this->request->getPost('email'),
                'webbpr' => $this->request->getPost('webbpr'),
            ];

            // Proses upload logo jika ada file valid
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $namaBaru = $file->getRandomName();
                $file->move('./asset/img', $namaBaru);
                $data['logo'] = $namaBaru;

                // Hapus logo lama jika sedang update
                if (!empty($id)) {
                    $oldData = $this->model->find($id);
                    if ($oldData && !empty($oldData['logo']) && file_exists('./asset/img/' . $oldData['logo'])) {
                        unlink('./asset/img/' . $oldData['logo']);
                    }
                }
            }

            if (empty($id)) {
                $success = $this->model->tambahinfo($data);
                $message = 'Data Informasi BPR berhasil ditambahkan.';
            } else {
                $success = $this->model->ubah($data, $id);
                $message = 'Data Informasi BPR berhasil diubah.';
            }

            if ($success) {
                session()->setFlashdata('message', $message);
                return redirect()->to(base_url('infobpr'));
            } else {
                session()->setFlashdata('err', 'Terjadi kesalahan saat menyimpan data.');
                return redirect()->to(base_url('infobpr'))->withInput();
            }
        } else {
            session()->setFlashdata('err', $this->validator->listErrors());
            return redirect()->to(base_url('infobpr'))->withInput();
        }
    }


}