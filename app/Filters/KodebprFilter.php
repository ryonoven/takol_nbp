<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class KodebprFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = service('authentication');
        
        if ($auth->check()) {
            $userId = $auth->id();
            $userModel = new \App\Models\M_user();
            $user = $userModel->find($userId);
            
            if (empty($user['kodebpr'])) {
                return redirect()->to('/profile')->with('error', 'Anda harus memiliki kode BPR yang valid');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu melakukan apa-apa setelah request
    }
}