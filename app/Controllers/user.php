<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class user extends Controller
{
    public function __construct()
    {
        // Most services in this controller require
        // the session to be started - so fire it up!
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
            'judul' => 'My Profile',

        ];
        

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('user/index', $data);
        echo view('templates/v_footer');
    }
    
}