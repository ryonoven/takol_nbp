<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_formasisdm;
use App\Models\M_ketentuanbpr;
use App\Models\M_sosialisasipelatihan;
use App\Models\M_budayakepatuhan;
use App\Models\M_penyimpangankepatuhan;
use App\Models\M_komitmenotoritas;
use App\Models\M_showdirymf;
use App\Models\M_user;
use App\Models\M_infobpr;
use App\Models\M_periodesemesteran;
use App\Libraries\Pdf\CoverPdfSection;
use App\Libraries\Pdf\LembarPengesahanPdfSection;
use App\Libraries\Pdf\FormasisdmPdfSection;
use App\Libraries\Pdf\KetentuanbprPdfSection;
use App\Libraries\Pdf\SosialisasipelatihanPdfSection;
use App\Libraries\Pdf\BudayakepatuhanPdfSection;
use App\Libraries\Pdf\PenyimpangankepatuhanPdfSection;
use App\Libraries\Pdf\KomitmenotoritasPdfSection;
use setasign\Fpdi\Fpdi;

/**
 * Controller untuk mencetak PDF laporan gabungan GCG.
 *
 * Struktur halaman:
 * 1. Cover
 * 2. Lembar Pengesahan — DITEMPEL dari file PDF yang sudah diunggah dan
 *    ditandatangani user (kolom showdirymf.pdfdir_filename), bukan hasil
 *    generate ulang. Jika belum ada file yang diunggah, halaman ini
 *    dilewati (tidak ada halaman kosong disisipkan).
 * 3. Formasi Sumber Daya Manusia
 * 4. Ketentuan Internal Yang Dimiliki BPR
 * 5. Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan
 * 6. Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan
 * 7. Penyimpangan Terhadap Kepatuhan Peraturan Perundang-undangan
 * 8. Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas
 *
 * Setiap halaman KECUALI Cover dan Lembar Pengesahan menampilkan header
 * standar (logo BPR kiri atas, nama+alamat+kontak BPR kanan atas) via
 * PdfPageHeader, dipanggil otomatis di dalam masing-masing *PdfSection.
 *
 * PENTING: $pdf di sini adalah instance Fpdi (bukan FPDF polos), karena
 * Lembar Pengesahan butuh kemampuan import halaman dari file PDF lain.
 * Fpdi extends FPDF sehingga seluruh *PdfSection yang type-hint FPDF
 * tetap kompatibel tanpa perubahan.
 */
class LaporanPdfGcg extends Controller
{
    protected $auth;
    protected $userModel;
    protected $infobprModel;
    protected $periodeModel;
    protected $formasisdmModel;
    protected $ketentuanbprModel;
    protected $sosialisasipelatihanModel;
    protected $budayakepatuhanModel;
    protected $penyimpangankepatuhanModel;
    protected $komitmenotoritasModel;
    protected $showdirymfModel;

    protected $userKodebpr;

    public function __construct()
    {
        $this->auth = service('authentication');
        $this->userModel = new M_user();
        $this->infobprModel = new M_infobpr();
        $this->periodeModel = new M_periodesemesteran();
        $this->formasisdmModel = new M_formasisdm();
        $this->ketentuanbprModel = new M_ketentuanbpr();
        $this->sosialisasipelatihanModel = new M_sosialisasipelatihan();
        $this->budayakepatuhanModel = new M_budayakepatuhan();
        $this->penyimpangankepatuhanModel = new M_penyimpangankepatuhan();
        $this->komitmenotoritasModel = new M_komitmenotoritas();
        $this->showdirymfModel = new M_showdirymf();

        if ($this->auth->isLoggedIn()) {
            $user = $this->userModel->find($this->auth->id());
            $this->userKodebpr = $user['kodebpr'] ?? null;
        } else {
            $this->userKodebpr = null;
        }
    }

    public function LaporanDirekturYangMembawahiFungsiKepatuhan()
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

        $showfaktor = $this->showdirymfModel
            ->select('pdfdir_filename')
            ->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])
            ->first();

        $namabpr = $bprData['namabpr'] ?? 'Nama BPR Tidak Ditemukan';
        $alamat = $bprData['alamat'] ?? '';
        $email = $bprData['email'] ?? '';
        $nomor = $bprData['nomor'] ?? '';
        $logo = $bprData['logo'] ?? '';
        $logoPath = $logo ? FCPATH . 'asset/img/' . $logo : null;

        $semDisplay = $periodeDetail['semester'] ?? 'N/A';
        $tahun = $periodeDetail['tahun'] ?? 'N/A';

        // Dipakai berulang sebagai header standar tiap halaman konten
        $bprHeader = [
            'namabpr' => $namabpr,
            'alamat' => $alamat,
            'email' => $email,
            'nomor' => $nomor,
            'logoPath' => $logoPath,
        ];

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // ── Halaman 1: Cover ──
        CoverPdfSection::render($pdf, $namabpr, $logoPath, $semDisplay, (string) $tahun);

        // ── Halaman 2: Lembar Pengesahan — tempel file PDF yang sudah diunggah user ──
        $pdfdirFilename = $showfaktor['pdfdir_filename'] ?? null;
        if ($pdfdirFilename) {
            $pengesahanPath = WRITEPATH . 'uploads/pdf_dirymf/' . $pdfdirFilename;
            $attached = LembarPengesahanPdfSection::render($pdf, $pengesahanPath);

            if (!$attached) {
                log_message('error', "[LaporanPdfGcg] File Lembar Pengesahan tidak ditemukan/gagal dibaca: {$pengesahanPath}");
            }
        } else {
            log_message('info', "[LaporanPdfGcg] Belum ada file Lembar Pengesahan yang diunggah untuk kodebpr={$kodebpr}, periode_id={$periodeId}");
        }

        // ── Section: Formasi Sumber Daya Manusia ──
        // ── Section: Formasi Sumber Daya Manusia ──
        $formasisdmRows = $this->sanitizeRows(
            $this->formasisdmModel->getDataByKodebprAndPeriode($kodebpr, $periodeId)
        );
        FormasisdmPdfSection::render($pdf, $formasisdmRows, $bprHeader, $semDisplay, (string) $tahun, true);

        // ── Section: Ketentuan Internal Yang Dimiliki BPR ──
        $ketentuanbprRows = $this->sanitizeRows(
            $this->ketentuanbprModel->getDataByKodebprAndPeriode($kodebpr, $periodeId)
        );
        KetentuanbprPdfSection::render($pdf, $ketentuanbprRows, $bprHeader, $semDisplay, (string) $tahun, true);

        // ── Section: Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan ──
        $sosialisasipelatihanRows = $this->sanitizeRows(
            $this->sosialisasipelatihanModel->getDataByKodebprAndPeriode($kodebpr, $periodeId)
        );
        SosialisasipelatihanPdfSection::render($pdf, $sosialisasipelatihanRows, $bprHeader, $semDisplay, (string) $tahun, true);

        // ── Section: Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan ──
        $budayakepatuhanRows = $this->sanitizeRows(
            $this->budayakepatuhanModel->getDataByKodebprAndPeriode($kodebpr, $periodeId)
        );
        BudayakepatuhanPdfSection::render($pdf, $budayakepatuhanRows, $bprHeader, $semDisplay, (string) $tahun, true);

        // ── Section: Penyimpangan Terhadap Kepatuhan Peraturan Perundang-undangan ──
        $penyimpangankepatuhanRows = $this->sanitizeRows(
            $this->penyimpangankepatuhanModel->getDataByKodebprAndPeriode($kodebpr, $periodeId)
        );
        PenyimpangankepatuhanPdfSection::render($pdf, $penyimpangankepatuhanRows, $bprHeader, $semDisplay, (string) $tahun, true);

        // ── Section: Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas ──
        $komitmenotoritasRows = $this->sanitizeRows(
            $this->komitmenotoritasModel->getDataByKodebprAndPeriode($kodebpr, $periodeId)
        );
        KomitmenotoritasPdfSection::render($pdf, $komitmenotoritasRows, $bprHeader, $semDisplay, (string) $tahun, true);

        $filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $namabpr);
        $filename = "Laporan-GCG-{$filenameSafe}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdf->Output('S'));
    }

    private function sanitizeText($text): string
    {
        if ($text === null) {
            return '';
        }

        $text = (string) $text;

        // Normalisasi semua jenis line break (\r\n, \r, \n) jadi spasi tunggal
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);

        // Kolaps tab dan spasi berulang jadi satu spasi
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }

    /**
     * Menerapkan sanitizeText() ke setiap nilai string dalam array baris data
     * (hasil getDataByKodebprAndPeriode dari masing-masing model), tanpa
     * mengubah nilai non-string (id, angka, null, dll).
     */
    private function sanitizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = $this->sanitizeText($value);
                }
            }
        }
        unset($row);

        return $rows;
    }
}