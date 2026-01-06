<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_user;

class User extends Controller
{
    protected $userModel;
    protected $auth;

    public function __construct()
    {
        $this->session = service('session');
        $this->auth = service('authentication');
        $this->authorize = service('authorization');
        $this->userModel = new M_user();
    }

    public function index()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL);
        }

        $data = [
            'judul' => 'My Profile',

        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('user/index', $data);
        echo view('templates/v_footer');
    }

    public function updateEmail($id)
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        // Debug - lihat data yang diterima
        // dd($this->request->getPost());

        $rules = [
            'email' => 'required|valid_email|is_unique[users.email,id,' . $id . ']'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'email' => $this->request->getPost('email')
        ];

        $this->userModel->update($id, $data);

        return redirect()->back()->with('message', 'Email berhasil diperbarui');
    }



}