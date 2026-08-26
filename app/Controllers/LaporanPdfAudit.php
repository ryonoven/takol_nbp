<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_lingkupaudit;
use App\Models\M_temuanaudit;
use App\Models\M_kesimpulanaudit;
use App\Models\M_rekomendasiaudit;
use App\Models\M_pemantauanaudit;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Libraries\Pdf\LingkupauditPdfSection;
use App\Libraries\Pdf\TemuanauditPdfSection;
use App\Libraries\Pdf\KesimpulanauditPdfSection;
use App\Libraries\Pdf\RekomendasiauditPdfSection;
use App\Libraries\Pdf\PemantauanauditPdfSection;
use App\Libraries\Pdf\BprPdf;
use App\Libraries\Pdf\CoverPdfSection2;
use FPDF;

class LaporanPdfAudit extends Controller
{
    protected $auth;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $lingkupauditModel;
    protected $temuanauditModel;
    protected $kesimpulanauditModel;
    protected $rekomendasiauditModel;
    protected $pemantauanauditModel;

    protected $userKodebpr;

    public function __construct()
    {
        $this->auth = service('authentication');
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->lingkupauditModel = new M_lingkupaudit();
        $this->temuanauditModel = new M_temuanaudit();
        $this->kesimpulanauditModel = new M_kesimpulanaudit();
        $this->rekomendasiauditModel = new M_rekomendasiaudit();
        $this->pemantauanauditModel = new M_pemantauanaudit();

        if ($this->auth->isLoggedIn()) {
            $user = $this->userModel->find($this->auth->id());
            $this->userKodebpr = $user['kodebpr'] ?? null;
        } else {
            $this->userKodebpr = null;
        }
    }

    public function LaporanAuditInternal()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }

        if (!session('active_periode')) {
            return redirect()->to('/Periodesemesteran')
                ->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }

        $periodeId = (int) session('active_periode');
        $kodebpr = $this->userKodebpr;

        if (!$kodebpr) {
            return redirect()->back()->with('error', 'User tidak memiliki kode BPR yang valid.');
        }

        $periodeDetail = $this->periodeModel->find($periodeId);
        $bprData = $this->infobprModel->getBprByKode($kodebpr);

        $namabpr = $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan';
        $alamat = $bprData['alamat'] ?? '';
        $email = $bprData['email'] ?? '';
        $nomor = $bprData['nomor'] ?? '';
        $logo = $bprData['logo'] ?? '';
        $logoPath = $logo ? FCPATH . 'asset/img/' . $logo : null;
        $semDisplay = $periodeDetail['semester'] ?? 'N/A';
        $tahun = $periodeDetail['tahun'] ?? 'N/A';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Cover
       CoverPdfSection2::render($pdf, $namabpr, $logoPath, $semDisplay, (string) $tahun, $alamat);

        // Ruang Lingkup dan Waktu Pelaksanaan Audit
        $lingkupauditRows = $this->lingkupauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        LingkupauditPdfSection::render($pdf, $lingkupauditRows, $namabpr, $semDisplay, (string) $tahun, true, $alamat, $email, $nomor, $logoPath);

        // Temuan Audit
        $temuanauditRows = $this->temuanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        TemuanauditPdfSection::render($pdf, $temuanauditRows, $namabpr, $semDisplay, (string) $tahun, true, $alamat, $email, $nomor, $logoPath);

        // Kesimpulan Audit
        $kesimpulanauditRows = $this->kesimpulanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        KesimpulanauditPdfSection::render($pdf, $kesimpulanauditRows, $namabpr, $semDisplay, (string) $tahun, true, $alamat, $email, $nomor, $logoPath);

        // Rekomendasi Audit
        $rekomendasiauditRows = $this->rekomendasiauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        RekomendasiauditPdfSection::render($pdf, $rekomendasiauditRows, $namabpr, $semDisplay, (string) $tahun, true, $alamat, $email, $nomor, $logoPath);

        // Hasil Pemantauan Komitmen Auditee
        $pemantauanauditRows = $this->pemantauanauditModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        PemantauanauditPdfSection::render($pdf, $pemantauanauditRows, $namabpr, $semDisplay, (string) $tahun, true, $alamat, $email, $nomor, $logoPath);

        $filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $namabpr);
        $filename = "Laporan-Audit-Internal-{$filenameSafe}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdf->Output('S'));
    }
}