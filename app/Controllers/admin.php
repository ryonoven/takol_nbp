<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Admin extends Controller
{
    protected $db, $builder;

    public function __construct()
    {
        $this->session = service('session');
        $this->auth = service('authentication');
        $this->authorize = service('authorization');
        $this->db = \Config\Database::connect();
        $this->builder = $this->db->table('users');

        if ($this->auth->check()) {
            $userId = $this->auth->id(); // atau $this->auth->user()->id
            if (!$this->authorize->inGroup('admin', $userId)) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException("Page not found");
            }
        } else {
            // Jika belum login
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Page not found");
        }
    }


    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $data['judul'] = 'Users List';
        // $users = new \Myth\Auth\Models\UserModel();
        // $data['users'] = $users->findAll();
        $this->builder->select('users.id as userid, username, email, kodebpr');
        $this->builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $this->builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $query = $this->builder->get();

        $data['users'] = $query->getResult();

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('admin/index', $data);
        echo view('templates/v_footer');
    }
    public function detail($id = 0)
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $data['judul'] = 'Users Detail';

        $this->builder->select('users.id as userid, email, username, fullname, user_image, kodebpr');
        $this->builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $this->builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $this->builder->where('users.id', $id);
        $query = $this->builder->get();

        $data['user'] = $query->getRow();

        // Add the kodebpr data
        $kodebpr = $this->db->table('infobpr')->select('kodebpr')->get()->getResult();
        $data['kodebpr'] = $kodebpr;

        if (empty($data['user'])) {
            return redirect()->to('/admin');
        }

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('admin/detail', $data);  // Ensure $data is passed to the view
        echo view('templates/v_footer');
    }


    public function updateKodebpr($id)
    {
        // Periksa jika user login dan authorized (admin)
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        $userId = $this->auth->id();
        if (!$this->authorize->inGroup('admin', $userId)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Page not found");
        }

        // Ambil data user berdasarkan ID
        $this->builder->select('users.id as userid, email, username, fullname, user_image, kodebpr');
        $this->builder->where('users.id', $id);
        $query = $this->builder->get();
        $data['user'] = $query->getRow();

        // Jika user tidak ditemukan, arahkan kembali ke halaman admin
        if (empty($data['user'])) {
            return redirect()->to('/admin');
        }

        // Ambil daftar kodebpr dari tabel infobpr
        $kodebpr = $this->db->table('infobpr')->select('kodebpr')->get()->getResult();
        $data['kodebpr'] = $kodebpr; // Mengirimkan data kodebpr ke view

        // Jika form disubmit
        if ($this->request->getMethod() === 'post') {
            $kodebpr = $this->request->getPost('kodebpr');
            // Update kodebpr untuk user yang dipilih
            $this->db->table('users')->where('id', $id)->update(['kodebpr' => $kodebpr]);

            // Redirect ke halaman admin setelah update
            return redirect()->to('/admin');
        }

        // Kirim data ke view
        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('admin/update_kodebpr', $data); // Mengirimkan data ke view
        echo view('templates/v_footer');
    }

}

