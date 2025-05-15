<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_infobpr;
use App\Models\M_penjelasanumum;
use App\Models\M_tgjwbdir;
use App\Models\M_tgjwbdekom;
use App\Models\M_tgjwbkomite;
use App\Models\M_strukturkomite;
use App\Models\M_sahamdirdekom;
use App\Models\M_shmusahadirdekom;
use App\Models\M_shmdirdekomlain;
use App\Models\M_keuangandirdekompshm;
use App\Models\M_keluargadirdekompshm;
use App\Models\M_paketkebijakandirdekom;
use App\Models\M_rasiogaji;
use App\Models\M_rapat;
use App\Models\M_kehadirandekom;
use App\Models\M_fraudinternal;
use App\Models\M_masalahhukum;
use App\Models\M_transaksikepentingan;
use App\Models\M_danasosial;



use App\Libraries\PdfGenerator;

class Pdfgcg extends Controller
{
    protected $penjelasanumumModel;
    protected $tgjwbdirModel;
    protected $tgjwbdekomModel;
    protected $tgjwbkomiteModel;
    protected $strukturkomiteModel;
    protected $infobprModel;
    protected $sahamdirdekomModel;
    protected $shmusahadirdekomModel;
    protected $shmdirdekomlainModel;
    protected $keuangandirdekompshmModel;
    protected $keluargadirdekompshmModel;
    protected $paketkebijakandirdekomModel;
    protected $rasiogajiModel;
    protected $rapatModel;
    protected $kehadirandekomModel;
    protected $fraudinternalModel;
    protected $masalahhukumModel;
    protected $transaksikepentinganModel;
    protected $danasosialModel;

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->penjelasanumumModel = new M_penjelasanumum();
        $this->tgjwbdirModel = new M_tgjwbdir();
        $this->tgjwbdekomModel = new M_tgjwbdekom();
        $this->tgjwbkomiteModel = new M_tgjwbkomite();
        $this->strukturkomiteModel = new M_strukturkomite();
        $this->sahamdirdekomModel = new M_sahamdirdekom();
        $this->shmusahadirdekomModel = new M_shmusahadirdekom();
        $this->shmdirdekomlainModel = new M_shmdirdekomlain();
        $this->keuangandirdekompshmModel = new M_keuangandirdekompshm();
        $this->keluargadirdekompshmModel = new M_keluargadirdekompshm();
        $this->paketkebijakandirdekomModel = new M_paketkebijakandirdekom();
        $this->rasiogajiModel = new M_rasiogaji();
        $this->rapatModel = new M_rapat();
        $this->kehadirandekomModel = new M_kehadirandekom();
        $this->fraudinternalModel = new M_fraudinternal();
        $this->masalahhukumModel = new M_masalahhukum();
        $this->transaksikepentinganModel = new M_transaksikepentingan();
        $this->danasosialModel = new M_danasosial();
    }

    public function generateFullReport()
    {
        $infobprData = $this->infobprModel->getAllData();
        $penjelasanumum = $this->penjelasanumumModel->getAllData();
        $tgjwbdir = $this->tgjwbdirModel->getAllData();
        $tgjwbdekom = $this->tgjwbdekomModel->getAllData();
        $tgjwbkomite = $this->tgjwbkomiteModel->getAllData();        
        $strukturkomite = $this->strukturkomiteModel->getAllData();
        $sahamdirdekom = $this->sahamdirdekomModel->getAllData();
        $shmusahadirdekom = $this->shmusahadirdekomModel->getAllData();
        $shmdirdekomlain = $this->shmdirdekomlainModel->getAllData();
        $keuangandirdekompshm = $this->keuangandirdekompshmModel->getAllData();
        $keluargadirdekompshm = $this->keluargadirdekompshmModel->getAllData();
        $paketkebijakandirdekom = $this->paketkebijakandirdekomModel->getAllData();
        $rasiogaji = $this->rasiogajiModel->getAllData();
        $rapat = $this->rapatModel->getAllData();
        $kehadirandekom = $this->kehadirandekomModel->getAllData();
        $fraudinternal = $this->fraudinternalModel->getAllData();
        $masalahhukum = $this->masalahhukumModel->getAllData();
        $transaksikepentingan = $this->transaksikepentinganModel->getAllData();
        $danasosial = $this->danasosialModel->getAllData();


        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        $pdf->generateCoverPage($infobpr);

        $pdf->generateFullReport($penjelasanumum, $tgjwbdir, $infobpr, $tgjwbdekom, $tgjwbkomite, $strukturkomite, $sahamdirdekom, $shmusahadirdekom, $shmdirdekomlain, $keuangandirdekompshm, $keluargadirdekompshm, $paketkebijakandirdekom, $rasiogaji, $rapat, $kehadirandekom, $fraudinternal, $masalahhukum, $transaksikepentingan, $danasosial);

        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun ' . date('Y') . '' . ($infobpr['namabpr'] ?? ''), 'I');
        exit;
    }

    public function pdfPenjelasanUmum()
    {
        $penjelasanumum = $this->penjelasanumumModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generatePenjelasanUmum($penjelasanumum, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfTgjwbDir()
    {
        $tgjwbdir = $this->tgjwbdirModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateTanggungJawabDireksi($tgjwbdir, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfTgjwbDekom()
    {
        $tgjwbdekom = $this->tgjwbdekomModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateTanggungJawabDekom($tgjwbdekom, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfTgjwbKomite()
    {
        $tgjwbkomite = $this->tgjwbkomiteModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateTanggungJawabKomite($tgjwbkomite, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfStrukturKomite()
    {
        $strukturkomite = $this->strukturkomiteModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateStrukturKomite($strukturkomite, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfSahamdirdekom()
    {
        $sahamdirdekom = $this->sahamdirdekomModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateSahamdirdekom($sahamdirdekom, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfUsahadirdekom()
    {
        $shmusahadirdekom = $this->shmusahadirdekomModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateUsahadirdekom($shmusahadirdekom, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfDirdekomlain()
    {
        $shmdirdekomlain = $this->shmdirdekomlainModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateDirdekomlain($shmdirdekomlain, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfKeuangan()
    {
        $keuangandirdekompshm = $this->keuangandirdekompshmModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateKeuangan($keuangandirdekompshm, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfKeluarga()
    {
        $keluargadirdekompshm = $this->keluargadirdekompshmModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateKeluarga($keluargadirdekompshm, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfPaket()
    {
        $paketkebijakandirdekom = $this->paketkebijakandirdekomModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateKeluarga($paketkebijakandirdekom, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfRasio()
    {
        $rasiogaji = $this->rasiogajiModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateRasio($rasiogaji, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfRapat()
    {
        $rapat = $this->rapatModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateRapat($rapat, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfHadir()
    {
        $kehadirandekom = $this->kehadirandekomModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateHadir($kehadirandekom, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfFraud()
    {
        $fraudinternal = $this->fraudinternalModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateFraud($fraudinternal, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfMasalah()
    {
        $masalahhukum = $this->masalahhukumModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateMasalah($masalahhukum, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfBentur()
    {
        $transaksikepentingan = $this->transaksikepentinganModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateBentur($transaksikepentingan, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }

    public function pdfDanasosial()
    {
        $danasosial = $this->danasosialModel->getAllData();
        $infobprData = $this->infobprModel->getAllData();

        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }

        $infobpr = $infobprData[0];

        $pdf = new PdfGenerator('P', 'mm', 'A4');

        // Set header data with correct logo path
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => file_exists($logoPath) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Generate PDF
        $pdf->generateDanasosial($danasosial, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Transparansi Tahun 2025 BPR NBP 20.pdf', 'I');
        exit;
    }
}