<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter gabungan. Argumen: role apa pun, dan/atau kata 'nav'.
 *   guard:nav            -> login + nav
 *   guard:admin          -> login + role:admin
 *   guard:admin,nav      -> login + role:admin + nav
 */
class GuardFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $arguments = $arguments ?? [];
        $aliases   = config('Filters')->aliases;

        // 1) login (memakai filter 'login' milik Anda)
        if (isset($aliases['login'])) {
            $res = (new $aliases['login']())->before($request);
            if ($res !== null) {
                return $res;
            }
        }

        // 2) role (memakai filter 'role' milik Anda)
        $roles = array_values(array_diff($arguments, ['nav']));
        if ($roles && isset($aliases['role'])) {
            $res = (new $aliases['role']())->before($request, $roles);
            if ($res !== null) {
                return $res;
            }
        }

        // 3) nav (tidak boleh dibuka langsung dari URL)
        if (in_array('nav', $arguments, true)) {
            $res = (new NavFilter())->before($request);
            if ($res !== null) {
                return $res;
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}