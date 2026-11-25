<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class NavFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Halaman yang dimuat lewat AJAX tidak diganggu
        if ($request->isAJAX()) {
            return;
        }

        $hosts = array_filter([
            strtolower((string) $request->getUri()->getHost()),
            strtolower((string) parse_url(base_url(), PHP_URL_HOST)),
        ]);

        // 1) Referer berasal dari aplikasi sendiri (klik menu/link)
        $referer = $request->getHeaderLine('Referer');
        if ($referer !== '' && in_array(strtolower((string) parse_url($referer, PHP_URL_HOST)), $hosts, true)) {
            return;
        }

        // 2) Cadangan: browser menandai navigasi berasal dari situs sendiri
        if (in_array($request->getHeaderLine('Sec-Fetch-Site'), ['same-origin', 'same-site'], true)) {
            return;
        }

        return redirect()->to('/')->with('error', 'Halaman tidak dapat dibuka langsung dari URL.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}