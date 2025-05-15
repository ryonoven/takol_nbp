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
        $this->builder->select('users.id as userid, username, email, name');
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

        $this->builder->select('users.id as userid, email, username,  fullname, user_image, name');
        $this->builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $this->builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $this->builder->where('users.id', $id);
        $query = $this->builder->get();

        $data['user'] = $query->getRow();

        if (empty($data['user'])) {
            return redirect()->to('/admin');
        }

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('admin/detail', $data);
        echo view('templates/v_footer');
    }
}
