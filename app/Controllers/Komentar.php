<?php

namespace App\Controllers;

use App\Models\M_komentar;
use App\Models\M_user;
use CodeIgniter\Controller;

/**
 * Endpoint komentar bersama untuk halaman pelaporan (laporan tahunan & SAF).
 *   GET  komentar/daftar?page=X      -> daftar komentar + tandai sudah dibaca
 *   POST komentar/kirim (page, komentar)
 *   GET  komentar/belumdibaca?page=X -> jumlah komentar baru (untuk badge)
 * Semua data dibatasi ke kodebpr user + periode aktif (session active_periode).
 */
class Komentar extends Controller
{
    /** Interval polling badge (ms); browser menunda polling saat tab tidak aktif. */
    public const POLL_MS = 30000;

    private const RATE_LIMIT = 10;   // komentar per menit per user

    protected $komentarModel;
    protected $auth;
    protected ?array $user = null;

    public function __construct()
    {
        $this->komentarModel = new M_komentar();
        $this->auth = service('authentication');
        if ($this->auth->check()) {
            $this->user = (new M_user())->find($this->auth->id());
        }
    }

    // ------------------------------------------------------------------ helpers

    private function json(int $status, array $payload)
    {
        $payload['csrf'] = ['name' => csrf_token(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    /** [page, kodebpr, periodeId, userId] atau response error. */
    private function context(?string $page)
    {
        if (!$this->user) {
            return $this->json(401, ['ok' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
        }
        $kodebpr = (string) ($this->user['kodebpr'] ?? '');
        $periodeId = (int) session('active_periode');
        if ($kodebpr === '' || $periodeId <= 0) {
            return $this->json(400, ['ok' => false, 'message' => 'Periode aktif tidak ditemukan.']);
        }
        if (!M_komentar::isPage($page)) {
            return $this->json(422, ['ok' => false, 'message' => 'Halaman tidak valid.']);
        }
        return [$page, $kodebpr, $periodeId, (int) $this->user['id']];
    }

    /** Daftar komentar + tandai dibaca sampai komentar terakhir. */
    private function listAndMarkRead(string $page, string $kodebpr, int $periodeId, int $userId): array
    {
        $rows = $this->komentarModel->listFor($page, $kodebpr, $periodeId);
        if ($rows) {
            $this->komentarModel->markRead($page, $kodebpr, $periodeId, $userId, (int) end($rows)['id']);
        }

        return array_map(static fn($r) => [
            'id' => (int) $r['id'],
            'fullname' => (string) ($r['fullname'] ?? ''),
            'komentar' => (string) $r['komentar'],
            'created_at' => (string) $r['created_at'],
            'own' => (int) $r['user_id'] === $userId,
        ], $rows);
    }

    // ------------------------------------------------------------------ endpoints

    public function daftar()
    {
        $ctx = $this->context($this->request->getGet('page'));
        if (!is_array($ctx)) {
            return $ctx;
        }
        return $this->json(200, ['ok' => true, 'comments' => $this->listAndMarkRead(...$ctx), 'unread' => 0]);
    }

    public function kirim()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
        }
        $ctx = $this->context($this->request->getPost('page'));
        if (!is_array($ctx)) {
            return $ctx;
        }
        [$page, $kodebpr, $periodeId, $userId] = $ctx;

        $komentar = trim((string) $this->request->getPost('komentar'));
        $len = mb_strlen($komentar);
        if ($len < M_komentar::MIN_LEN) {
            return $this->json(422, ['ok' => false, 'message' => 'Komentar minimal ' . M_komentar::MIN_LEN . ' karakter.']);
        }
        if ($len > M_komentar::MAX_LEN) {
            return $this->json(422, ['ok' => false, 'message' => 'Komentar maksimal ' . M_komentar::MAX_LEN . ' karakter.']);
        }

        $cache = service('cache');
        $rlKey = 'cmtthn_' . $userId;
        $count = (int) ($cache->get($rlKey) ?? 0);
        if ($count >= self::RATE_LIMIT) {
            return $this->json(429, ['ok' => false, 'message' => 'Terlalu banyak komentar, tunggu sebentar.']);
        }
        $cache->save($rlKey, $count + 1, 60);

        try {
            $this->komentarModel->add($page, $kodebpr, $periodeId, $userId, (string) ($this->user['fullname'] ?? $this->user['username'] ?? ''), $komentar);
        } catch (\Throwable $e) {
            log_message('error', 'Komentar::kirim ' . $e->getMessage());
            return $this->json(500, ['ok' => false, 'message' => 'Komentar gagal dikirim.']);
        }

        return $this->json(200, ['ok' => true, 'message' => 'Komentar terkirim.', 'comments' => $this->listAndMarkRead(...$ctx), 'unread' => 0]);
    }

    public function belumdibaca()
    {
        $ctx = $this->context($this->request->getGet('page'));
        if (!is_array($ctx)) {
            return $ctx;
        }
        [$page, $kodebpr, $periodeId, $userId] = $ctx;
        $counts = $this->komentarModel->unreadCounts($kodebpr, $periodeId, $userId, [$page]);

        return $this->json(200, ['ok' => true, 'unread' => $counts[$page] ?? 0, 'poll_ms' => self::POLL_MS]);
    }
}
