<?php

namespace App\Controllers;

use App\Models\LaporanPdfSafModel;
use App\Models\M_penerapansafst;
use App\Models\M_user;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Myth\Auth\Config\Services as AuthServices;

class Laporanpdfsaf extends BaseController
{
    private LaporanPdfSafModel $m;
    private ?string $kb = null;
    private array $grup = [];
    private ?int $userRole = null;

    /** auth_groups.id */
    private const ROLE_IDS = [
        'admin' => 1,
        'dekom' => 2,
        'direksi' => 3,
        'pe' => 4,
        'dekom2' => 6,
        'dekom3' => 7,
        'dekom4' => 8,
        'dekom5' => 9,
        'direksi2' => 10,
    ];

    private const LEVEL_LABEL = [
        'dekom' => 'Dewan Komisaris',
        'direksi' => 'Direktur Utama',
    ];

    public function __construct()
    {
        $this->m = new LaporanPdfSafModel();
        $uid = user_id();
        if ($uid) {
            $auth = AuthServices::authorization();
            foreach (array_keys(self::ROLE_IDS) as $g) {
                $this->grup[$g] = $auth->inGroup($g, $uid);
            }
            $this->userRole = $this->resolveUserRole();
        }
    }

    private function resolveUserRole(): ?int
    {
        foreach (['pe', 'direksi', 'direksi2', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'admin'] as $g) {
            if ($this->grup[$g] ?? false) {
                return self::ROLE_IDS[$g];
            }
        }

        return null;
    }

    private function ringkasApproval(array $h): array
    {
        $req = M_penerapansafst::requiredLevels($h['user_role'] ?? null);
        $out = [];
        foreach (LaporanPdfSafModel::LEVEL_COLS as $lv => [$flag, $by, $at]) {
            $perlu = in_array($lv, $req, true);
            $ok = $perlu && (int) ($h[$flag] ?? 0) === 1;
            $out[$lv] = [
                'required' => $perlu,
                'approved' => $ok,
                'at' => $ok ? ($h[$at] ?? null) : null,
                'by' => $ok ? ($h[$by] ?? null) : null,
            ];
        }

        return $out;
    }

    public function statusRingkas(string $kodebpr, int $periodeId): array
    {
        $d = $this->m->getLaporan($kodebpr, $periodeId);
        $status = $d ? (int) $d['h']['status'] : 0;

        return [
            'status'      => $status,
            'is_approved' => $status === 2,
            'accdir_by'   => $d['h']['accdir_by'] ?? null,
            'accdir_at'   => $d['h']['accdir_at'] ?? null,
            'levels'      => $d ? $this->ringkasApproval($d['h']) : null,
        ];
    }

    private function bolehEdit(): bool
    {
        foreach (['pe', 'admin', 'dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5', 'direksi', 'direksi2'] as $g) {
            if ($this->grup[$g] ?? false) {
                return true;
            }
        }

        return false;
    }

    private function canApproveLevel(string $level): bool
    {
        if ($this->grup['admin'] ?? false) {
            return true;
        }
        $daftar = $level === 'dekom'
            ? ['dekom', 'dekom2', 'dekom3', 'dekom4', 'dekom5']
            : ($level === 'direksi' ? ['direksi', 'direksi2'] : []);
        foreach ($daftar as $g) {
            if ($this->grup[$g] ?? false) {
                return true;
            }
        }

        return false;
    }

    private function kodebpr(): string
    {
        if (!service('authentication')->check()) {
            throw new \CodeIgniter\Router\Exceptions\RedirectException('/login');
        }
        if ($this->kb === null) {
            $u = (new M_user())->select('kodebpr')->find(user_id());
            $this->kb = (string) ($u['kodebpr'] ?? '');
        }
        if ($this->kb === '') {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->kb;
    }

    private function aman(string $s): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $s);
    }

    private function json(array $d, int $code = 200)
    {
        $d['csrf'] = csrf_hash();

        return $this->response->setStatusCode($code)->setJSON($d);
    }

    private function teks($v, int $max): ?string
    {
        $v = mb_substr(trim((string) $v), 0, $max);

        return $v === '' ? null : $v;
    }

    private function bersih(array $items, bool $bolehAnak): array
    {
        $out = [];
        foreach (array_slice($items, 0, 30) as $it) {
            if (!is_array($it)) {
                continue;
            }
            $row = [
                'judul' => $this->teks($it['judul'] ?? '', 255),
                'uraian' => $this->teks($it['uraian'] ?? '', 5000),
                'gaya' => (($it['gaya'] ?? '') === 'poin') ? 'poin' : 'paragraf',
                'anak' => [],
            ];
            if ($bolehAnak) {
                foreach (array_slice((array) ($it['anak'] ?? []), 0, 30) as $a) {
                    if (!is_array($a)) {
                        continue;
                    }
                    $j = $this->teks($a['judul'] ?? '', 255);
                    $u = $this->teks($a['uraian'] ?? '', 5000);
                    if ($j !== null || $u !== null) {
                        $row['anak'][] = ['judul' => $j, 'uraian' => $u];
                    }
                }
            }
            if ($row['judul'] !== null || $row['uraian'] !== null || $row['anak']) {
                $out[] = $row;
            }
        }

        return $out;
    }

    public function index($periodeId = null)
    {
        $k = $this->kodebpr();
        $periodeId = (int) ($periodeId ?: session('active_periode'));
        if ($periodeId < 1) {
            return redirect()->to(base_url('Periodesaf'))->with('error', 'Pilih periode SAF terlebih dahulu.');
        }

        $d = $this->m->getLaporan($k, $periodeId);
        if (!$d) {
            if (!$this->m->siapkan($k, $periodeId, (int) user_id() ?: null)) {
                return redirect()->to(base_url('Periodesaf'))
                    ->with('error', 'Periode tidak valid atau bukan periode semesteran (SAFTS).');
            }
            $d = $this->m->getLaporan($k, $periodeId);
        }

        $data = [
            'judul' => 'Deskripsi Fraud / Modus Operandi',
            'h' => $d['h'],
            'tree' => $d['tree'],
            'fraud' => $this->m->kejadianFraud($k, $periodeId),
            'periodeId' => $periodeId,
            'pilar' => LaporanPdfSafModel::PILAR,
            'canEdit' => $this->bolehEdit(),
            'approval' => $this->ringkasApproval($d['h']),
            'canApprove' => [
                'dekom' => $this->canApproveLevel('dekom'),
                'direksi' => $this->canApproveLevel('direksi'),
            ],
        ];

        echo view('templates/v_header', $data);
        echo view('templates/v_sidebar');
        echo view('templates/v_topbar');
        echo view('Laporansaf/laporanpdfsaf', $data);
        echo view('templates/v_footer');
    }

    // simpan
    public function simpan()
    {
        if (!$this->bolehEdit()) {
            return $this->json(['ok' => false, 'msg' => 'Anda tidak memiliki akses untuk mengubah laporan.'], 403);
        }
        $k = $this->kodebpr();
        $p = (int) $this->request->getPost('periode_id');
        $h = $this->m->getHeader($k, $p);
        if (!$h) {
            return $this->json(['ok' => false, 'msg' => 'Laporan tidak ditemukan.'], 404);
        }
        if ((int) $h['status'] === 2) {
            return $this->json(['ok' => false, 'msg' => 'Laporan sudah disetujui dan tidak dapat diubah. Batalkan approval terlebih dahulu.'], 403);
        }
        $in = json_decode((string) $this->request->getPost('data'), true);
        if (!is_array($in)) {
            return $this->json(['ok' => false, 'msg' => 'Data tidak valid.'], 400);
        }

        $tgl = (string) ($in['tanggal_laporan'] ?? '');
        $dt = \DateTime::createFromFormat('Y-m-d', $tgl);
        $head = [
            'ringkasan_umum' => $this->teks($in['ringkasan_umum'] ?? '', 5000),
            'kota_ttd' => $this->teks($in['kota_ttd'] ?? '', 100),
            'tanggal_laporan' => ($dt && $dt->format('Y-m-d') === $tgl) ? $tgl : null,
            'nama_pembuat' => $this->teks($in['nama_pembuat'] ?? '', 150),
            'jabatan_pembuat' => $this->teks($in['jabatan_pembuat'] ?? '', 200),
            'status' => 0,
            'accdir' => 0,
            'accdir_by' => null,
            'accdir_at' => null,
            'accdir2' => 0,
            'accdir2_by' => null,
            'accdir2_at' => null,
        ];

        // user_role = role pengisi pertama; tidak berubah saat diedit orang lain
        if (empty($h['user_role']) && $this->userRole) {
            $head['user_role'] = $this->userRole;
        }

        // Tanda tangan
        $f = $this->request->getFile('ttd');
        if ($f && $f->isValid() && !$f->hasMoved()) {
            $mime = $f->getMimeType();
            if (!in_array($mime, ['image/png', 'image/jpeg'], true) || $f->getSize() > 153600) {
                return $this->json(['ok' => false, 'msg' => 'Tanda tangan harus PNG/JPG, maksimal 150 KB.'], 422);
            }
            $dir = WRITEPATH . 'uploads/ttd/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $ext = $mime === 'image/png' ? 'png' : 'jpg';
            $base = $this->aman($k) . '_' . $p . '.';
            @unlink($dir . $base . ($ext === 'png' ? 'jpg' : 'png'));
            $f->move($dir, $base . $ext, true);
            $head['ttd_path'] = $base . $ext;
        }

        $pilar = [];
        for ($i = 1; $i <= 5; $i++) {
            $pilar[$i] = $this->bersih((array) ($in['pilar'][$i] ?? []), $i < 5);
        }
        $ok = $this->m->simpanPenuh($k, $p, $head, $pilar);

        return $this->json(['ok' => $ok, 'msg' => $ok ? 'Laporan tersimpan. Approval direset.' : 'Gagal menyimpan.'], $ok ? 200 : 500);
    }

    public function simpanItem()
    {
        if (!$this->bolehEdit()) {
            return $this->json(['ok' => false, 'msg' => 'Anda tidak memiliki akses untuk mengubah laporan.'], 403);
        }
        $k = $this->kodebpr();
        $p = (int) $this->request->getPost('periode_id');
        $h = $this->m->getHeader($k, $p);
        if (!$h) {
            return $this->json(['ok' => false, 'msg' => 'Laporan tidak ditemukan.'], 404);
        }
        if ((int) $h['status'] === 2) {
            return $this->json(['ok' => false, 'msg' => 'Laporan sudah disetujui dan tidak dapat diubah. Batalkan approval terlebih dahulu.'], 403);
        }

        $pilar = (int) $this->request->getPost('pilar');
        $id = (int) $this->request->getPost('id') ?: null;
        $parent = (int) $this->request->getPost('parent_id') ?: null;
        if ($pilar < 1 || $pilar > 5 || ($parent !== null && $pilar === 5)) {
            return $this->json(['ok' => false, 'msg' => 'Data tidak valid.'], 400);
        }

        $row = [
            'judul' => $this->teks($this->request->getPost('judul'), 255),
            'uraian' => $this->teks($this->request->getPost('uraian'), 5000),
            'gaya' => ($parent === null && $this->request->getPost('gaya') === 'poin') ? 'poin' : 'paragraf',
            'urutan' => max(1, min(99, (int) $this->request->getPost('urutan'))),
        ];
        $implisit = (string) $this->request->getPost('implisit') === '1';
        if (!$implisit && $row['judul'] === null && $row['uraian'] === null) {
            return $this->json(['ok' => false, 'msg' => 'Isi judul atau uraian terlebih dahulu.'], 422);
        }

        $newId = $this->m->simpanItem($k, $p, $pilar, $id, $parent, $row);
        if ($newId === null) {
            return $this->json(['ok' => false, 'msg' => 'Data atau induk program tidak ditemukan. Muat ulang halaman.'], 404);
        }
        $reload = $this->m->resetApproval($k, $p, $this->userRole);

        return $this->json(['ok' => true, 'id' => $newId, 'reload' => $reload, 'msg' => 'Tersimpan. Approval direset.']);
    }

    public function hapusItem()
    {
        if (!$this->bolehEdit()) {
            return $this->json(['ok' => false, 'msg' => 'Anda tidak memiliki akses untuk mengubah laporan.'], 403);
        }
        $k = $this->kodebpr();
        $p = (int) $this->request->getPost('periode_id');
        $id = (int) $this->request->getPost('id');
        $h = $this->m->getHeader($k, $p);
        if (!$h || $id < 1) {
            return $this->json(['ok' => false, 'msg' => 'Data tidak valid.'], 400);
        }
        if ((int) $h['status'] === 2) {
            return $this->json(['ok' => false, 'msg' => 'Laporan sudah disetujui dan tidak dapat diubah. Batalkan approval terlebih dahulu.'], 403);
        }

        $this->m->hapusItem($k, $p, $id);
        $reload = $this->m->resetApproval($k, $p, $this->userRole);

        return $this->json(['ok' => true, 'reload' => $reload, 'msg' => 'Data dihapus. Approval direset.']);
    }

    private function namaFile(string $s): string
    {
        $s = preg_replace('/[\/\\\\:*?"<>|]/', '', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    public function status()
    {
        $k = $this->kodebpr();
        $p = (int) $this->request->getPost('periode_id');
        $aksi = (string) $this->request->getPost('aksi');
        $level = (string) $this->request->getPost('level');

        if (!isset(self::LEVEL_LABEL[$level])) {
            return $this->json(['ok' => false, 'msg' => 'Level approval tidak valid.'], 400);
        }
        if (!in_array($aksi, ['setujui', 'batal'], true)) {
            return $this->json(['ok' => false, 'msg' => 'Aksi tidak diizinkan.'], 403);
        }
        if (!$this->canApproveLevel($level)) {
            return $this->json(['ok' => false, 'msg' => 'Anda tidak memiliki akses approval ' . self::LEVEL_LABEL[$level] . '.'], 403);
        }

        $d = $this->m->getLaporan($k, $p);
        if (!$d) {
            return $this->json(['ok' => false, 'msg' => 'Laporan tidak ditemukan.'], 404);
        }
        if (!in_array($level, M_penerapansafst::requiredLevels($d['h']['user_role'] ?? null), true)) {
            return $this->json(['ok' => false, 'msg' => 'Laporan ini tidak memerlukan approval ' . self::LEVEL_LABEL[$level] . '.'], 422);
        }

        if ($aksi === 'setujui') {
            foreach ([1, 2, 3, 4] as $i) {
                if (empty($d['tree'][$i])) {
                    return $this->json(['ok' => false, 'msg' => 'Pilar ' . $i . ' (' . LaporanPdfSafModel::PILAR[$i] . ') belum memiliki program. Laporan belum dapat disetujui.'], 422);
                }
            }
        }

        $baru = $this->m->setApprovalLevel($k, $p, $level, $aksi === 'setujui', (int) user_id() ?: null);
        if ($baru === null) {
            return $this->json(['ok' => false, 'msg' => 'Gagal memperbarui approval.'], 500);
        }

        return $this->json([
            'ok' => true,
            'msg' => $aksi === 'setujui'
                ? 'Laporan disetujui (' . self::LEVEL_LABEL[$level] . ').'
                : 'Approval ' . self::LEVEL_LABEL[$level] . ' dibatalkan.',
            'status' => $baru,
            'is_approved' => $baru === 2,
        ]);
    }

    public function getApprovalStatus()
    {
        $k = $this->kodebpr();
        $p = (int) ($this->request->getGet('periode_id') ?: session('active_periode'));
        if ($p < 1) {
            return $this->json(['ok' => false, 'msg' => 'Periode tidak valid'], 400);
        }

        $d = $this->m->getLaporan($k, $p);
        $status = $d ? (int) $d['h']['status'] : 0;
        $levels = $d ? $this->ringkasApproval($d['h']) : null;

        if ($levels) {
            foreach ($levels as $lv => $info) {
                $levels[$lv]['by_nama'] = null;
                if (!empty($info['by'])) {
                    $u = (new M_user())->select('fullname')->find($info['by']);
                    $levels[$lv]['by_nama'] = $u['fullname'] ?? null;
                }
            }
        }

        return $this->json([
            'ok' => true,
            'status' => $status,
            'is_approved' => $status === 2,
            'levels' => $levels,
            'accdir_by' => $d['h']['accdir_by'] ?? null,
            'accdir_at' => $d['h']['accdir_at'] ?? null,
        ]);
    }

    public function pdf(int $periodeId)
    {
        $k = $this->kodebpr();
        $d = $this->m->getLaporan($k, $periodeId);
        if (!$d) {
            throw PageNotFoundException::forPageNotFound();
        }
        $h = $d['h'];

        $ttdFile = !empty($h['ttd_path']) ? WRITEPATH . 'uploads/ttd/' . basename($h['ttd_path']) : null;
        $ttdOk = $ttdFile && is_file($ttdFile);

        $logoFile = !empty($h['logo']) ? FCPATH . 'asset/img/' . basename($h['logo']) : null;
        $logoOk = $logoFile && is_file($logoFile);

        $ver = substr(md5($h['updated_at'] . '|' . ($ttdOk ? filemtime($ttdFile) : 0) . '|' . ($logoOk ? filemtime($logoFile) : 0)), 0, 12);

        $dir = WRITEPATH . 'cache/pdfsaf/';
        $pre = $this->aman($k) . '_' . $periodeId . '_';
        $path = $dir . $pre . $ver . '.pdf';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!is_file($path)) {
            $lock = fopen($path . '.lock', 'c');
            flock($lock, LOCK_EX);
            if (!is_file($path)) {
                $this->buatPdf($d, $ttdOk ? $ttdFile : null, $logoOk ? $logoFile : null, $path, $dir, $pre);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($path . '.lock');
        }

        $semNum = LaporanPdfSafModel::semNum($h['semester']);
        $judulFile = $this->namaFile(
            'Laporan Deskripsi Fraud / Modus Operandi - ' . $h['namabpr'] .
            ' - Semester ' . $semNum . ' Tahun ' . $h['tahun'] . '.pdf'
        );

        return service('response')
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $judulFile . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody(file_get_contents($path));
    }

    public function pdfFraud(int $periodeId)
    {
        $k = $this->kodebpr();
        $d = $this->m->getLaporan($k, $periodeId);
        if (!$d) {
            throw PageNotFoundException::forPageNotFound();
        }
        $h = $d['h'];

        $safstModel = new M_penerapansafst();
        $rows = $safstModel->getDataByKodebprAndPeriode($k, $periodeId);
        $kasus = array_values(array_filter(
            $rows,
            static fn($r) => empty($r['is_null_record']) && !empty($r['idkejadian'])
        ));

        $logoFile = !empty($h['logo']) ? FCPATH . 'asset/img/' . basename($h['logo']) : null;
        $logoOk = $logoFile && is_file($logoFile);
        $logo = null;
        if ($logoOk) {
            $ext = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFile));
        }

        $ttdFile = !empty($h['ttd_path']) ? WRITEPATH . 'uploads/ttd/' . basename($h['ttd_path']) : null;
        $ttdOk = $ttdFile && is_file($ttdFile);
        $ttd = null;
        if ($ttdOk) {
            $mime = substr($ttdFile, -4) === '.png' ? 'image/png' : 'image/jpeg';
            $ttd = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ttdFile));
        }

        $o = new Options();
        $o->set('isRemoteEnabled', false);
        $o->set('isPhpEnabled', false);
        $o->set('defaultFont', 'Helvetica');
        $o->set('tempDir', WRITEPATH . 'cache');
        $o->set('fontCache', WRITEPATH . 'cache');

        $pdf = new Dompdf($o);
        $pdf->loadHtml(view('Laporansaf/pdf_fraud', [
            'h' => $h,
            'kasus' => $kasus,
            'logo' => $logo,
            'ttd' => $ttd,
        ]));
        $pdf->setPaper('A4');
        $pdf->render();

        return service('response')
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="Laporan_Kejadian_Fraud_' . $this->aman($k) . '_' . $periodeId . '.pdf"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody($pdf->output());
    }

    private function buatPdf(array $d, ?string $ttdFile, ?string $logoFile, string $path, string $dir, string $pre): void
    {
        $ttd = null;
        if ($ttdFile) {
            $mime = substr($ttdFile, -4) === '.png' ? 'image/png' : 'image/jpeg';
            $ttd = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ttdFile));
        }

        $logo = null;
        if ($logoFile) {
            $ext = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFile));
        }

        $o = new Options();
        $o->set('isRemoteEnabled', false);
        $o->set('isPhpEnabled', false);
        $o->set('defaultFont', 'Helvetica');
        $o->set('tempDir', WRITEPATH . 'cache');
        $o->set('fontCache', WRITEPATH . 'cache');

        $pdf = new Dompdf($o);
        $pdf->loadHtml(view('Laporansaf/pdf', [
            'h' => $d['h'],
            'tree' => $d['tree'],
            'ttd' => $ttd,
            'logo' => $logo,
            'pilar' => LaporanPdfSafModel::PILAR,
        ]));
        $pdf->setPaper('A4');
        $pdf->render();

        $tmp = $path . '.tmp' . getmypid();
        file_put_contents($tmp, $pdf->output());
        rename($tmp, $path);

        foreach (glob($dir . $pre . '*.pdf') ?: [] as $f) {
            if ($f !== $path) {
                @unlink($f);
            }
        }
    }
}