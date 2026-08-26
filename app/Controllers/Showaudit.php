<?php

namespace App\Controllers;

use App\Models\M_periodesemesteran;
use CodeIgniter\Controller;
use App\Models\M_infobpr;
use App\Models\M_user;
use App\Models\M_showaudit;
use App\Models\M_showprofilresiko;
use App\Models\M_lingkupaudit;
use App\Models\M_temuanaudit;
use App\Models\M_kesimpulanaudit;
use App\Models\M_rekomendasiaudit;
use App\Models\M_pemantauanaudit;
use Myth\Auth\Config\Services as AuthServices;

class Showaudit extends Controller
{
    protected $showauditModel;
    protected $showprofilrisikoModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $userModel;
    protected $session;
    protected $auth;
    protected $userKodebpr;
    protected $lingkupauditModel;
    protected $temuanauditModel;
    protected $kesimpulanauditModel;
    protected $rekomendasiauditModel;
    protected $pemantauanauditModel;

    protected $userInGroupPE;
    protected $userInGroupAdmin;
    protected $userInGroupDekom;
    protected $userInGroupDireksi;

    public function __construct()
    {
        $this->infobprModel         = new M_infobpr();
        $this->periodeModel         = new M_periodesemesteran();
        $this->userModel            = new M_user();
        $this->showauditModel       = new M_showaudit();
        $this->showprofilrisikoModel = new M_showprofilresiko();
        $this->lingkupauditModel    = new M_lingkupaudit();
        $this->temuanauditModel     = new M_temuanaudit();
        $this->kesimpulanauditModel = new M_kesimpulanaudit();
        $this->rekomendasiauditModel = new M_rekomendasiaudit();
        $this->pemantauanauditModel = new M_pemantauanaudit();
        $this->session              = service('session');
        $this->auth                 = service('authentication');

        helper('text');

        if ($this->auth->isLoggedIn()) {
            $userId = $this->auth->id();
            $user   = $this->userModel->find($userId);
            $this->userKodebpr = $user['kodebpr'] ?? null;

            $authorize = AuthServices::authorization();
            $this->userInGroupPE      = $authorize->inGroup('pe',      $userId);
            $this->userInGroupAdmin   = $authorize->inGroup('admin',   $userId);
            $this->userInGroupDekom   = $authorize->inGroup('dekom',   $userId);
            $this->userInGroupDireksi = $authorize->inGroup('direksi', $userId);
        } else {
            $this->userKodebpr        = null;
            $this->userInGroupPE      = false;
            $this->userInGroupAdmin   = false;
            $this->userInGroupDekom   = false;
            $this->userInGroupDireksi = false;
        }
    }

    public function index()
    {
        if (!$this->auth->check()) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = session('active_periode');
        $kodebpr   = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $db = \Config\Database::connect();

        $subRows = $db->query(
            "SELECT 'lingkup'      AS src, kodebpr, periode_id, accdir2 AS is_approved FROM lingkupaudit      WHERE kodebpr = ? AND periode_id = ?
             UNION ALL
             SELECT 'temuan'       AS src, kodebpr, periode_id, accdir2 AS is_approved FROM temuanaudit       WHERE kodebpr = ? AND periode_id = ?
             UNION ALL
             SELECT 'kesimpulan'   AS src, kodebpr, periode_id, accdir2 AS is_approved FROM kesimpulanaudit   WHERE kodebpr = ? AND periode_id = ?
             UNION ALL
             SELECT 'rekomendasi'  AS src, kodebpr, periode_id, accdir2 AS is_approved FROM rekomendasiaudit  WHERE kodebpr = ? AND periode_id = ?
             UNION ALL
             SELECT 'pemantauan'   AS src, kodebpr, periode_id, accdir2 AS is_approved FROM pemantauanaudit   WHERE kodebpr = ? AND periode_id = ?",
            array_fill(0, 10, null)
        );

        $subData = [];
        $models = [
            'lingkup'    => $this->lingkupauditModel,
            'temuan'     => $this->temuanauditModel,
            'kesimpulan' => $this->kesimpulanauditModel,
            'rekomendasi'=> $this->rekomendasiauditModel,
            'pemantauan' => $this->pemantauanauditModel,
        ];
        foreach ($models as $key => $model) {
            $subData[$key] = $model
                ->select('accdir2')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();
        }

        // showaudit row
        $showfaktor = $this->showauditModel
            ->select('id, pdfaudit_filename')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        // Period & BPR
        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData       = $this->infobprModel->getBprByKode($kodebpr);

        // Build factors array
        $factors = [
            [
                'name'        => 'Ruang Lingkup dan Waktu Pelaksanaan Audit',
                'link'        => base_url('Lingkupaudit'),
                'is_approved' => $subData['lingkup']['accdir2']     ?? 0,
            ],
            [
                'name'        => 'Temuan Audit',
                'link'        => base_url('Temuanaudit'),
                'is_approved' => $subData['temuan']['accdir2']      ?? 0,
            ],
            [
                'name'        => 'Kesimpulan Audit dan Pernyataan Auditor',
                'link'        => base_url('Kesimpulanaudit'),
                'is_approved' => $subData['kesimpulan']['accdir2']  ?? 0,
            ],
            [
                'name'        => 'Rekomendasi Auditor, Tanggapan Auditee, dan Tindak Lanjut/Rekomendasi Auditee',
                'link'        => base_url('Rekomendasiaudit'),
                'is_approved' => $subData['rekomendasi']['accdir2'] ?? 0,
            ],
            [
                'name'        => 'Hasil Pemantauan Komitmen Auditee',
                'link'        => base_url('Pemantauanaudit'),
                'is_approved' => $subData['pemantauan']['accdir2']  ?? 0,
            ],
        ];

        // Upsert showaudit row
        if (empty($showfaktor)) {
            $this->showauditModel->simpanShowFaktor([
                'kodebpr'   => $kodebpr,
                'periode_id'=> $periodeId,
                'semester'  => $periodeDetail['semester'] ?? 'N/A',
                'tahun'     => $periodeDetail['tahun']    ?? 'N/A',
            ]);

            $showfaktor = $this->showauditModel
                ->select('id, pdfaudit_filename')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();
        }

        $data = [
            'judul'            => 'Laporan Pelaksanaan dan Pokok Hasil Audit Intern',
            'namabpr'          => $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan',
            'periode_semester' => $periodeDetail['semester'] ?? 'N/A',
            'periode_tahun'    => $periodeDetail['tahun']    ?? 'N/A',
            'factors'          => $factors,
            'showfaktor'       => $showfaktor,
        ];

        return view('templates/v_header',  $data)
             . view('templates/v_sidebar')
             . view('templates/v_topbar')
             . view('Laporansemester/Laporanaudit/showaudit', $data)
             . view('templates/v_footer');
    }

    public function uploadPdf()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/login');
        }

        $validationRules = [
            'pdf1' => [
                'rules'  => 'if_exist|uploaded[pdf1]|ext_in[pdf1,pdf]|max_size[pdf1,10240]',
                'errors' => [
                    'uploaded' => 'File PDF tidak diunggah.',
                    'ext_in'   => 'File harus berekstensi .pdf.',
                    'max_size' => 'Ukuran file terlalu besar (maksimal 10MB).',
                ],
            ],
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $kodebpr   = $this->userKodebpr;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return redirect()->back()->with('error', 'Data BPR atau Periode tidak ditemukan.');
        }

        $showauditRecord = $this->showauditModel
            ->select('id, pdfaudit_filename')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (empty($showauditRecord)) {
            return redirect()->back()->with('error', 'Data belum ada. Harap buat terlebih dahulu.');
        }

        $pdfUploadPath = WRITEPATH . 'uploads/pdf_auditinternal/';
        if (!is_dir($pdfUploadPath)) {
            mkdir($pdfUploadPath, 0775, true);
        }

        $pdf1 = $this->request->getFile('pdf1');

        if ($pdf1 && $pdf1->isValid() && !$pdf1->hasMoved()) {
            $oldPdf = $showauditRecord['pdfaudit_filename'] ?? null;
            if ($oldPdf && file_exists($pdfUploadPath . $oldPdf)) {
                unlink($pdfUploadPath . $oldPdf);
            }

            $newName = $pdf1->getRandomName();
            $pdf1->move($pdfUploadPath, $newName);

            \Config\Database::connect()
                ->table('showaudit')
                ->where('id', $showauditRecord['id'])
                ->update(['pdfaudit_filename' => $newName]);

            return redirect()->back()->with('message', 'File PDF berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Tidak ada file yang diunggah atau file tidak valid.');
    }

    public function downloadPdf(string $filename)
    {
        $filename = basename($filename);
        $pdfPath  = WRITEPATH . 'uploads/pdf_auditinternal/' . $filename;

        if (!file_exists($pdfPath)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $filesize = filesize($pdfPath);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) $filesize)
            ->setHeader('Cache-Control', 'public, max-age=3600')  // allow CDN/browser caching
            ->setBody(file_get_contents($pdfPath));
    }
}