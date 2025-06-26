<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_infobpr;
use App\Models\M_nilaifaktor;
use App\Models\M_faktor;
use App\Models\M_nilaifaktor2;
use App\Models\M_faktor2;
use App\Models\M_nilaifaktor3;
use App\Models\M_faktor3;
use App\Models\M_nilaifaktor4;
use App\Models\M_faktor4;
use App\Models\M_nilaifaktor5;
use App\Models\M_faktor5;
use App\Models\M_nilaifaktor6;
use App\Models\M_faktor6;
use App\Models\M_nilaifaktor7;
use App\Models\M_faktor7;
use App\Models\M_nilaifaktor8;
use App\Models\M_faktor8;
use App\Models\M_nilaifaktor9;
use App\Models\M_faktor9;
use App\Models\M_nilaifaktor10;
use App\Models\M_faktor10;
use App\Models\M_nilaifaktor11;
use App\Models\M_faktor11;
use App\Models\M_nilaifaktor12;
use App\Models\M_faktor12;
use App\Models\M_user;
use App\Models\M_periode;
use App\Models\M_showfaktor;

use App\Libraries\PdfSelfassessment;

class Pdfself extends Controller
{
    protected $infobprModel;
    protected $nilaifaktorModel;
    protected $faktorModel;
    protected $nilaifaktor2Model;
    protected $faktor2Model;
    protected $nilaifaktor3Model;
    protected $faktor3Model;
    protected $nilaifaktor4Model;
    protected $faktor4Model;
    protected $nilaifaktor5Model;
    protected $faktor5Model;
    protected $nilaifaktor6Model;
    protected $faktor6Model;
    protected $nilaifaktor7Model;
    protected $faktor7Model;
    protected $nilaifaktor8Model;
    protected $faktor8Model;
    protected $nilaifaktor9Model;
    protected $faktor9Model;
    protected $nilaifaktor10Model;
    protected $faktor10Model;
    protected $nilaifaktor11Model;
    protected $faktor11Model;
    protected $nilaifaktor12Model;
    protected $faktor12Model;
    protected $periodeModel;
    protected $userModel;
    protected $showfaktorModel;

    public function __construct()
    {
        $this->infobprModel = new M_infobpr();
        $this->nilaifaktorModel = new M_nilaifaktor();
        $this->faktorModel = new M_faktor();
        $this->nilaifaktor2Model = new M_nilaifaktor2();
        $this->faktor2Model = new M_faktor2();
        $this->nilaifaktor3Model = new M_nilaifaktor3();
        $this->faktor3Model = new M_faktor3();
        $this->nilaifaktor4Model = new M_nilaifaktor4();
        $this->faktor4Model = new M_faktor4();
        $this->nilaifaktor5Model = new M_nilaifaktor5();
        $this->faktor5Model = new M_faktor5();
        $this->nilaifaktor6Model = new M_nilaifaktor6();
        $this->faktor6Model = new M_faktor6();
        $this->nilaifaktor7Model = new M_nilaifaktor7();
        $this->faktor7Model = new M_faktor7();
        $this->nilaifaktor8Model = new M_nilaifaktor8();
        $this->faktor8Model = new M_faktor8();
        $this->nilaifaktor9Model = new M_nilaifaktor9();
        $this->faktor9Model = new M_faktor9();
        $this->nilaifaktor10Model = new M_nilaifaktor10();
        $this->faktor10Model = new M_faktor10();
        $this->nilaifaktor11Model = new M_nilaifaktor11();
        $this->faktor11Model = new M_faktor11();
        $this->nilaifaktor12Model = new M_nilaifaktor12();
        $this->faktor12Model = new M_faktor12();
        $this->userModel = new M_user();
        $this->periodeModel = new M_periode();
        $this->showfaktorModel = new M_showfaktor();

    }

    public function uploadPdf()
    {
        $validationRules = [
            'pdf1' => [
                'rules' => 'if_exist|uploaded[pdf1]|ext_in[pdf1,pdf]|max_size[pdf1,2048]', // Max 2MB
                'errors' => [
                    'uploaded' => 'File PDF 1 tidak diunggah.',
                    'ext_in' => 'File PDF 1 harus berekstensi .pdf.',
                    'max_size' => 'Ukuran file PDF 1 terlalu besar (maksimal 2MB).'
                ]
            ],
            'pdf2' => [
                'rules' => 'if_exist|uploaded[pdf2]|ext_in[pdf2,pdf]|max_size[pdf2,2048]', // Max 2MB
                'errors' => [
                    'uploaded' => 'File PDF 2 tidak diunggah.',
                    'ext_in' => 'File PDF 2 harus berekstensi .pdf.',
                    'max_size' => 'Ukuran file PDF 2 terlalu besar (maksimal 2MB).'
                ]
            ]
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = service('authentication')->id();
        $user = $this->userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return redirect()->back()->with('error', 'Data BPR atau Periode tidak ditemukan.');
        }

        $showfaktorRecord = $this->showfaktorModel
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->first();

        if (empty($showfaktorRecord)) {
            // If no record exists, you might need to create one first.
            // For simplicity, we'll redirect with an error.
            return redirect()->back()->with('error', 'Data Ringkasan Penilaian belum ada. Harap buat terlebih dahulu.');
        }

        $pdfUploadPath = WRITEPATH . 'uploads/pdf_attachments/';
        // Ensure the directory exists
        if (!is_dir($pdfUploadPath)) {
            mkdir($pdfUploadPath, 0777, true);
        }

        $updateData = [];

        // Handle PDF 1 upload
        $pdf1 = $this->request->getFile('pdf1');
        if ($pdf1 && $pdf1->isValid() && !$pdf1->hasMoved()) {
            $oldPdf1 = $showfaktorRecord['pdf1_filename'];
            if ($oldPdf1 && file_exists($pdfUploadPath . $oldPdf1)) {
                unlink($pdfUploadPath . $oldPdf1); // Delete old file
            }
            $newName1 = $pdf1->getRandomName();
            $pdf1->move($pdfUploadPath, $newName1);
            $updateData['pdf1_filename'] = $newName1;
            session()->setFlashdata('message', 'File PDF 1 berhasil diunggah.');
        }

        // Handle PDF 2 upload
        $pdf2 = $this->request->getFile('pdf2');
        if ($pdf2 && $pdf2->isValid() && !$pdf2->hasMoved()) {
            $oldPdf2 = $showfaktorRecord['pdf2_filename'];
            if ($oldPdf2 && file_exists($pdfUploadPath . $oldPdf2)) {
                unlink($pdfUploadPath . $oldPdf2); // Delete old file
            }
            $newName2 = $pdf2->getRandomName();
            $pdf2->move($pdfUploadPath, $newName2);
            $updateData['pdf2_filename'] = $newName2;
            session()->setFlashdata('message', 'File PDF 2 berhasil diunggah.');
        }

        // Only update if there's actual data to update
        if (!empty($updateData)) {
            $this->showfaktorModel->update($showfaktorRecord['id'], $updateData);
            return redirect()->back()->with('message', 'File PDF berhasil diunggah dan disimpan.');
        } else {
            return redirect()->back()->with('error', 'Tidak ada file PDF yang diunggah atau ada masalah saat mengunggah.');
        }
    }

    public function downloadPdf($filename)
    {
        $pdfPath = WRITEPATH . 'uploads/pdf_attachments/' . $filename;

        // Periksa apakah file ada
        if (!file_exists($pdfPath)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        // Dapatkan MIME type
        $mime = mime_content_type($pdfPath);

        // Kirim file ke browser
        return $this->response->download($pdfPath, null)->setContentType($mime);
    }

    public function getAllData()
    {
        return $this->db->table('nilaifaktor')
            ->select('nilaifaktor.*, faktor.sph, faktor.sub_category')  // Mengambil data sub_category dan sph
            ->join('faktor', 'faktor.id = nilaifaktor.faktor1id', 'left')  // Join dengan tabel faktor
            ->get()
            ->getResultArray();
    }

    public function getAllData2()
    {
        return $this->db->table('nilaifaktor2')
            ->select('nilaifaktor2.*, faktor2.sph, faktor2.sub_category')  // Mengambil data sub_category dan sph
            ->join('faktor2', 'faktor2.id = nilaifaktor2.faktor2id', 'left')  // Join dengan tabel faktor2
            ->get()
            ->getResultArray();
    }
    public function getAllData3()
    {
        return $this->db->table('nilaifaktor3')
            ->select('nilaifaktor3.*, faktor3.sph, faktor3.sub_category')
            ->join('faktor3', 'faktor3.id = nilaifaktor3.faktor3id', 'left')
            ->get()
            ->getResultArray();
    }
    public function getAllData4()
    {
        return $this->db->table('nilaifaktor4')
            ->select('nilaifaktor4.*, faktor4.sph, faktor4.sub_category')
            ->join('faktor4', 'faktor4.id = nilaifaktor4.faktor4id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData5()
    {
        return $this->db->table('nilaifaktor5')
            ->select('nilaifaktor5.*, faktor5.sph, faktor5.sub_category')
            ->join('faktor5', 'faktor5.id = nilaifaktor5.faktor5id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData6()
    {
        return $this->db->table('nilaifaktor6')
            ->select('nilaifaktor6.*, faktor6.sph, faktor6.sub_category')
            ->join('faktor6', 'faktor6.id = nilaifaktor6.faktor6id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData7()
    {
        return $this->db->table('nilaifaktor7')
            ->select('nilaifaktor7.*, faktor7.sph, faktor7.sub_category')
            ->join('faktor7', 'faktor7.id = nilaifaktor7.faktor7id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData8()
    {
        return $this->db->table('nilaifaktor8')
            ->select('nilaifaktor8.*, faktor8.sph, faktor8.sub_category')
            ->join('faktor8', 'faktor8.id = nilaifaktor8.faktor8id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData9()
    {
        return $this->db->table('nilaifaktor9')
            ->select('nilaifaktor9.*, faktor9.sph, faktor9.sub_category')
            ->join('faktor9', 'faktor9.id = nilaifaktor9.faktor9id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData10()
    {
        return $this->db->table('nilaifaktor10')
            ->select('nilaifaktor10.*, faktor10.sph, faktor10.sub_category')
            ->join('faktor10', 'faktor10.id = nilaifaktor10.faktor10id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData11()
    {
        return $this->db->table('nilaifaktor11')
            ->select('nilaifaktor11.*, faktor11.sph, faktor11.sub_category')
            ->join('faktor11', 'faktor11.id = nilaifaktor11.faktor11id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllData12()
    {
        return $this->db->table('nilaifaktor12')
            ->select('nilaifaktor12.*, faktor12.sph, faktor12.sub_category')
            ->join('faktor12', 'faktor12.id = nilaifaktor12.faktor12id', 'left')
            ->get()
            ->getResultArray();
    }

    public function getAllDatafaktor()
    {
        return $this->db->table('showfaktor')
            ->select('showfaktor.*')
            ->get()
            ->getResultArray();
    }




    public function generateFullReport()
    {

        $userId = service('authentication')->id();
        $userModel = new M_user();
        $user = $userModel->find($userId);
        $kodebpr = $user['kodebpr'] ?? null;
        $periodeId = session('active_periode');

        if (!$kodebpr || !$periodeId) {
            return redirect()->back()->with('error', 'Data BPR atau Periode tidak ditemukan.');
        }

        try {
            $userId = service('authentication')->id();
            if (!$userId) {
                return redirect()->back()->with('error', 'User not authenticated.');
            }

            $userModel = new M_user();
            $user = $userModel->find($userId);
            if (!$user) {
                return redirect()->back()->with('error', 'User data not found.');
            }

            $kodebpr = $user['kodebpr'] ?? null;
            $periodeId = session('active_periode');

            if (!$kodebpr) {
                return redirect()->back()->with('error', 'Kode BPR tidak ditemukan untuk user ini. Harap lengkapi profil.');
            }

            if (!$periodeId) {
                return redirect()->back()->with('error', 'Periode aktif tidak ditemukan dalam session. Harap pilih periode.');
            }

            $infobprData = $this->infobprModel->getBprByKode($kodebpr);
            if (empty($infobprData)) {
                return redirect()->back()->with('error', 'Data BPR tidak ditemukan untuk kode: ' . $kodebpr);
            }

            $periodeData = $this->periodeModel->find($periodeId); // Asumsi find() bisa mencari berdasarkan primary key 'id'
            if (empty($periodeData)) {
                return redirect()->back()->with('error', 'Data Periode tidak ditemukan untuk ID: ' . $periodeId);
            }

            $nilaifaktor = $this->nilaifaktorModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktorData = $this->faktorModel->getAllData();

            // Ambil data summary dan analisa positif/negatif dari salah satu baris nilaifaktor.            
            $nilaifaktorSummaryAndAnalysisData = $this->nilaifaktorModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first(); // Mengambil baris pertama yang cocok            

            if ($nilaifaktorSummaryAndAnalysisData) {
                $infobprData['nfaktor'] = $nilaifaktorSummaryAndAnalysisData['nfaktor'] ?? 'Belum Dinilai';
                $infobprData['penjelasfaktor'] = $nilaifaktorSummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infobprData['nkpomposit'] = $nilaifaktorSummaryAndAnalysisData['nkpomposit'] ?? 'N/A';
                $infobprData['peringkatkomposit'] = $nilaifaktorSummaryAndAnalysisData['peringkatkomposit'] ?? 'N/A';
                $infobprData['positifstruktur'] = $nilaifaktorSummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infobprData['negatifstruktur'] = $nilaifaktorSummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infobprData['positifproses'] = $nilaifaktorSummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infobprData['negatifproses'] = $nilaifaktorSummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infobprData['positifhasil'] = $nilaifaktorSummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infobprData['negatifhasil'] = $nilaifaktorSummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
                $infobprData['kesimpulan'] = $nilaifaktorSummaryAndAnalysisData['kesimpulan'] ?? 'Tidak ada kesimpulan.';
            } else {
                $infobprData['nfaktor'] = 'Belum Dinilai';
                $infobprData['penjelasfaktor'] = 'Data penilaian faktor belum tersedia untuk periode ini.';
                $infobprData['nkpomposit'] = 'N/A';
                $infobprData['peringkatkomposit'] = 'N/A';
                $infobprData['positifstruktur'] = 'Tidak ada data.';
                $infobprData['negatifstruktur'] = 'Tidak ada data.';
                $infobprData['positifproses'] = 'Tidak ada data.';
                $infobprData['negatifproses'] = 'Tidak ada data.';
                $infobprData['positifhasil'] = 'Tidak ada data.';
                $infobprData['negatifhasil'] = 'Tidak ada data.';
                $infobprData['kesimpulan'] = 'Tidak ada kesimpulan.';
            }

            $nilaifaktor2 = $this->nilaifaktor2Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor2Data = $this->faktor2Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 2
            $nilaifaktor2SummaryAndAnalysisData = $this->nilaifaktor2Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor2SummaryAndAnalysisData) {
                $infoDataForPdf['nfaktor2'] = $nilaifaktor2SummaryAndAnalysisData['nfaktor2'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor2
                $infoDataForPdf['penjelasfaktor'] = $nilaifaktor2SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf['positifstruktur'] = $nilaifaktor2SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf['negatifstruktur'] = $nilaifaktor2SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf['positifproses'] = $nilaifaktor2SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf['negatifproses'] = $nilaifaktor2SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf['positifhasil'] = $nilaifaktor2SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf['negatifhasil'] = $nilaifaktor2SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf['nfaktor2'] = 'Belum Dinilai';
                $infoDataForPdf['penjelasfaktor'] = 'Data penilaian faktor 2 belum tersedia untuk periode ini.';
                $infoDataForPdf['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor3 = $this->nilaifaktor3Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor3Data = $this->faktor3Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 3
            $nilaifaktor3SummaryAndAnalysisData = $this->nilaifaktor3Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor3SummaryAndAnalysisData) {
                $infoDataForPdf3['nfaktor3'] = $nilaifaktor3SummaryAndAnalysisData['nfaktor3'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor3
                $infoDataForPdf3['penjelasfaktor'] = $nilaifaktor3SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf3['positifstruktur'] = $nilaifaktor3SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf3['negatifstruktur'] = $nilaifaktor3SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf3['positifproses'] = $nilaifaktor3SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf3['negatifproses'] = $nilaifaktor3SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf3['positifhasil'] = $nilaifaktor3SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf3['negatifhasil'] = $nilaifaktor3SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf3['nfaktor3'] = 'Belum Dinilai';
                $infoDataForPdf3['penjelasfaktor'] = 'Data penilaian faktor 3 belum tersedia untuk periode ini.';
                $infoDataForPdf3['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf3['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf3['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf3['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf3['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf3['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor4 = $this->nilaifaktor4Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor4Data = $this->faktor4Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 4
            $nilaifaktor4SummaryAndAnalysisData = $this->nilaifaktor4Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor4SummaryAndAnalysisData) {
                $infoDataForPdf4['nfaktor4'] = $nilaifaktor4SummaryAndAnalysisData['nfaktor4'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor4
                $infoDataForPdf4['penjelasfaktor'] = $nilaifaktor4SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf4['positifstruktur'] = $nilaifaktor4SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf4['negatifstruktur'] = $nilaifaktor4SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf4['positifproses'] = $nilaifaktor4SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf4['negatifproses'] = $nilaifaktor4SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf4['positifhasil'] = $nilaifaktor4SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf4['negatifhasil'] = $nilaifaktor4SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf4['nfaktor4'] = 'Belum Dinilai';
                $infoDataForPdf4['penjelasfaktor'] = 'Data penilaian faktor 4 belum tersedia untuk periode ini.';
                $infoDataForPdf4['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf4['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf4['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf4['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf4['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf4['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor5 = $this->nilaifaktor5Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor5Data = $this->faktor5Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 5
            $nilaifaktor5SummaryAndAnalysisData = $this->nilaifaktor5Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor5SummaryAndAnalysisData) {
                $infoDataForPdf5['nfaktor5'] = $nilaifaktor5SummaryAndAnalysisData['nfaktor5'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor5
                $infoDataForPdf5['penjelasfaktor'] = $nilaifaktor5SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf5['positifstruktur'] = $nilaifaktor5SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf5['negatifstruktur'] = $nilaifaktor5SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf5['positifproses'] = $nilaifaktor5SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf5['negatifproses'] = $nilaifaktor5SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf5['positifhasil'] = $nilaifaktor5SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf5['negatifhasil'] = $nilaifaktor5SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf5['nfaktor5'] = 'Belum Dinilai';
                $infoDataForPdf5['penjelasfaktor'] = 'Data penilaian faktor 5 belum tersedia untuk periode ini.';
                $infoDataForPdf5['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf5['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf5['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf5['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf5['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf5['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor6 = $this->nilaifaktor6Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor6Data = $this->faktor6Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 6
            $nilaifaktor6SummaryAndAnalysisData = $this->nilaifaktor6Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor6SummaryAndAnalysisData) {
                $infoDataForPdf6['nfaktor6'] = $nilaifaktor6SummaryAndAnalysisData['nfaktor6'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor6
                $infoDataForPdf6['penjelasfaktor'] = $nilaifaktor6SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf6['positifstruktur'] = $nilaifaktor6SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf6['negatifstruktur'] = $nilaifaktor6SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf6['positifproses'] = $nilaifaktor6SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf6['negatifproses'] = $nilaifaktor6SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf6['positifhasil'] = $nilaifaktor6SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf6['negatifhasil'] = $nilaifaktor6SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf6['nfaktor6'] = 'Belum Dinilai';
                $infoDataForPdf6['penjelasfaktor'] = 'Data penilaian faktor 6 belum tersedia untuk periode ini.';
                $infoDataForPdf6['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf6['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf6['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf6['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf6['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf6['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor7 = $this->nilaifaktor7Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor7Data = $this->faktor7Model->getAllData();

            $nilaifaktor7SummaryAndAnalysisData = $this->nilaifaktor7Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();
            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 7
            if ($nilaifaktor7SummaryAndAnalysisData) {
                $infoDataForPdf7['nfaktor7'] = $nilaifaktor7SummaryAndAnalysisData['nfaktor7'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor7
                $infoDataForPdf7['penjelasfaktor'] = $nilaifaktor7SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf7['positifstruktur'] = $nilaifaktor7SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf7['negatifstruktur'] = $nilaifaktor7SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf7['positifproses'] = $nilaifaktor7SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf7['negatifproses'] = $nilaifaktor7SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf7['positifhasil'] = $nilaifaktor7SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf7['negatifhasil'] = $nilaifaktor7SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf7['nfaktor7'] = 'Belum Dinilai';
                $infoDataForPdf7['penjelasfaktor'] = 'Data penilaian faktor 7 belum tersedia untuk periode ini.';
                $infoDataForPdf7['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf7['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf7['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf7['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf7['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf7['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor8 = $this->nilaifaktor8Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor8Data = $this->faktor8Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 8
            $nilaifaktor8SummaryAndAnalysisData = $this->nilaifaktor8Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();
            if ($nilaifaktor8SummaryAndAnalysisData) {
                $infoDataForPdf8['nfaktor8'] = $nilaifaktor8SummaryAndAnalysisData['nfaktor8'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor8
                $infoDataForPdf8['penjelasfaktor'] = $nilaifaktor8SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf8['positifstruktur'] = $nilaifaktor8SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf8['negatifstruktur'] = $nilaifaktor8SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf8['positifproses'] = $nilaifaktor8SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf8['negatifproses'] = $nilaifaktor8SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf8['positifhasil'] = $nilaifaktor8SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf8['negatifhasil'] = $nilaifaktor8SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf8['nfaktor8'] = 'Belum Dinilai';
                $infoDataForPdf8['penjelasfaktor'] = 'Data penilaian faktor 8 belum tersedia untuk periode ini.';
                $infoDataForPdf8['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf8['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf8['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf8['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf8['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf8['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor9 = $this->nilaifaktor9Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor9Data = $this->faktor9Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 9
            $nilaifaktor9SummaryAndAnalysisData = $this->nilaifaktor9Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor9SummaryAndAnalysisData) {
                $infoDataForPdf9['nfaktor9'] = $nilaifaktor9SummaryAndAnalysisData['nfaktor9'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor9
                $infoDataForPdf9['penjelasfaktor'] = $nilaifaktor9SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf9['positifstruktur'] = $nilaifaktor9SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf9['negatifstruktur'] = $nilaifaktor9SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf9['positifproses'] = $nilaifaktor9SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf9['negatifproses'] = $nilaifaktor9SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf9['positifhasil'] = $nilaifaktor9SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf9['negatifhasil'] = $nilaifaktor9SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf9['nfaktor9'] = 'Belum Dinilai';
                $infoDataForPdf9['penjelasfaktor'] = 'Data penilaian faktor 9 belum tersedia untuk periode ini.';
                $infoDataForPdf9['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf9['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf9['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf9['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf9['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf9['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor10 = $this->nilaifaktor10Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor10Data = $this->faktor10Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 10
            $nilaifaktor10SummaryAndAnalysisData = $this->nilaifaktor10Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor10SummaryAndAnalysisData) {
                $infoDataForPdf10['nfaktor10'] = $nilaifaktor10SummaryAndAnalysisData['nfaktor10'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor10
                $infoDataForPdf10['penjelasfaktor'] = $nilaifaktor10SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf10['positifstruktur'] = $nilaifaktor10SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf10['negatifstruktur'] = $nilaifaktor10SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf10['positifproses'] = $nilaifaktor10SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf10['negatifproses'] = $nilaifaktor10SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf10['positifhasil'] = $nilaifaktor10SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf10['negatifhasil'] = $nilaifaktor10SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf10['nfaktor10'] = 'Belum Dinilai';
                $infoDataForPdf10['penjelasfaktor'] = 'Data penilaian faktor 10 belum tersedia untuk periode ini.';
                $infoDataForPdf10['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf10['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf10['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf10['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf10['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf10['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor11 = $this->nilaifaktor11Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor11Data = $this->faktor11Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 11
            $nilaifaktor11SummaryAndAnalysisData = $this->nilaifaktor11Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();
            if ($nilaifaktor11SummaryAndAnalysisData) {
                $infoDataForPdf11['nfaktor11'] = $nilaifaktor11SummaryAndAnalysisData['nfaktor11'] ?? 'Belum Dinilai'; // Asumsi kolom sama di M_nilaifaktor11
                $infoDataForPdf11['penjelasfaktor'] = $nilaifaktor11SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf11['positifstruktur'] = $nilaifaktor11SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf11['negatifstruktur'] = $nilaifaktor11SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf11['positifproses'] = $nilaifaktor11SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf11['negatifproses'] = $nilaifaktor11SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf11['positifhasil'] = $nilaifaktor11SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf11['negatifhasil'] = $nilaifaktor11SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf11['nfaktor11'] = 'Belum Dinilai';
                $infoDataForPdf11['penjelasfaktor'] = 'Data penilaian faktor 11 belum tersedia untuk periode ini.';
                $infoDataForPdf11['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf11['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf11['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf11['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf11['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf11['negatifhasil'] = 'Tidak ada data.';
            }

            $nilaifaktor12 = $this->nilaifaktor12Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->findAll();

            $faktor12Data = $this->faktor12Model->getAllData();

            // Ambil data summary dan analisa positif/negatif untuk FAKTOR 12
            $nilaifaktor12SummaryAndAnalysisData = $this->nilaifaktor12Model
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($nilaifaktor12SummaryAndAnalysisData) {
                $infoDataForPdf12['nfaktor12'] = $nilaifaktor12SummaryAndAnalysisData['nfaktor12'] ?? 'Belum Dinilai';
                $infoDataForPdf12['penjelasfaktor'] = $nilaifaktor12SummaryAndAnalysisData['penjelasfaktor'] ?? 'Data penjelasan belum tersedia.';
                $infoDataForPdf12['positifstruktur'] = $nilaifaktor12SummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf12['negatifstruktur'] = $nilaifaktor12SummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.';
                $infoDataForPdf12['positifproses'] = $nilaifaktor12SummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf12['negatifproses'] = $nilaifaktor12SummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.';
                $infoDataForPdf12['positifhasil'] = $nilaifaktor12SummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.';
                $infoDataForPdf12['negatifhasil'] = $nilaifaktor12SummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.';
            } else {
                $infoDataForPdf12['nfaktor12'] = 'Belum Dinilai';
                $infoDataForPdf12['penjelasfaktor'] = 'Data penilaian faktor 12 belum tersedia untuk periode ini.';
                $infoDataForPdf12['positifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf12['negatifstruktur'] = 'Tidak ada data.';
                $infoDataForPdf12['positifproses'] = 'Tidak ada data.';
                $infoDataForPdf12['negatifproses'] = 'Tidak ada data.';
                $infoDataForPdf12['positifhasil'] = 'Tidak ada data.';
                $infoDataForPdf12['negatifhasil'] = 'Tidak ada data.';
            }

            $showfaktorSummaryAndAnalysisData = $this->showfaktorModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($showfaktorSummaryAndAnalysisData) {
                $infoDataForPdfshow = [
                    'nilaikomposit' => $showfaktorSummaryAndAnalysisData['nilaikomposit'] ?? 'Belum Dinilai',
                    'kesimpulan' => $showfaktorSummaryAndAnalysisData['kesimpulan'] ?? 'Belum Dinilai',
                    'positifstruktur' => $showfaktorSummaryAndAnalysisData['positifstruktur'] ?? 'Tidak ada data.',
                    'negatifstruktur' => $showfaktorSummaryAndAnalysisData['negatifstruktur'] ?? 'Tidak ada data.',
                    'positifproses' => $showfaktorSummaryAndAnalysisData['positifproses'] ?? 'Tidak ada data.',
                    'negatifproses' => $showfaktorSummaryAndAnalysisData['negatifproses'] ?? 'Tidak ada data.',
                    'positifhasil' => $showfaktorSummaryAndAnalysisData['positifhasil'] ?? 'Tidak ada data.',
                    'negatifhasil' => $showfaktorSummaryAndAnalysisData['negatifhasil'] ?? 'Tidak ada data.'
                ];
            } else {
                $infoDataForPdfshow = [
                    'nilaikomposit' => 'Belum Dinilai',
                    'kesimpulan' => 'Belum Dinilai',
                    'positifstruktur' => 'Tidak ada data.',
                    'negatifstruktur' => 'Tidak ada data.',
                    'positifproses' => 'Tidak ada data.',
                    'negatifproses' => 'Tidak ada data.',
                    'positifhasil' => 'Tidak ada data.',
                    'negatifhasil' => 'Tidak ada data.'
                ];
            }

            $showfaktortandatanganData = $this->showfaktorModel
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            if ($showfaktortandatanganData) {
                $infottdForPdfshow = [
                    'dirut' => $showfaktortandatanganData['dirut'] ?? 'Belum Dinilai',
                    'komut' => $showfaktortandatanganData['komut'] ?? 'Belum Dinilai',
                    'tanggal' => $showfaktortandatanganData['tanggal'] ?? 'Tidak ada data.',
                    'lokasi' => $showfaktortandatanganData['lokasi'] ?? 'Tidak ada data.',
                    'pdf1_filename' => $showfaktortandatanganData['pdf1_filename'] ?? null, // <<< PASTIKAN INI ADA
                    'pdf2_filename' => $showfaktortandatanganData['pdf2_filename'] ?? null,
                ];
            } else {
                $infottdForPdfshow = [
                    'dirut' => 'Belum Dinilai',
                    'komut' => 'Belum Dinilai',
                    'tanggal' => 'Tidak ada data.',
                    'lokasi' => 'Tidak ada data.'
                ];
            }


            $pdf = new PdfSelfassessment('P', 'mm', 'A4');
            $pdf->setFilterParams($kodebpr, $periodeId);

            $logoPath = FCPATH . 'asset/img/' . ($infobprData['logo'] ?? '');

            $namaBPR = $infobprData['namabpr'] ?? 'BPR Default';
            $tahun = $periodeData['tahun'] ?? date('Y'); // mengambil tahun dari periodeData
            $semester = $periodeData['semester'] ?? null;

            $semesterDisplay = 'Periode Tidak Diketahui';
            if ($semester === 'Ganjil') {
                $semesterDisplay = 'Semester 1';
            } elseif ($semester === 'Genap') {
                $semesterDisplay = 'Semester 2';
            }

            $filename = 'Laporan Self Assessment Tata Kelola ' . $namaBPR . ' - ' . $semesterDisplay . ' - ' . $tahun . '.pdf';

            $pdf->setHeaderData([
                'logo' => file_exists($logoPath) ? $logoPath : '',
                'namabpr' => $infobprData['namabpr'] ?? 'N/A',
                'alamat' => $infobprData['alamat'] ?? 'N/A',
                'nomor' => $infobprData['nomor'] ?? 'N/A',
                'webbpr' => $infobprData['webbpr'] ?? 'N/A',
                'email' => $infobprData['email'] ?? '',
                'title' => $filename
            ]);

            $pdf->generateCoverPage($infobprData);

            //Faktor 1     
            $pdf->AddPage(); // Tambahkan halaman pertama untuk konten
            $pdf->generateNilaiFaktor1($nilaifaktor, $infobprData, $faktorData);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor1($infobprData); // Panggil metode Kesimpulan Faktor di sini
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor1($infobprData); // Panggil metode Analisa Faktor di sini
            $pdf->AliasNbPages();

            //Faktor 2
            $pdf->AddPage();
            $pdf->generateNilaiFaktor2($nilaifaktor2, $infoDataForPdf, $faktor2Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor2($infoDataForPdf);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor2($infoDataForPdf);
            $pdf->AliasNbPages();

            //Faktor 3
            $pdf->AddPage();
            $pdf->generateNilaiFaktor3($nilaifaktor3, $infoDataForPdf3, $faktor3Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor3($infoDataForPdf3);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor3($infoDataForPdf3);
            $pdf->AliasNbPages();

            //Faktor 4
            $pdf->AddPage();
            $pdf->generateNilaiFaktor4($nilaifaktor4, $infoDataForPdf4, $faktor4Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor4($infoDataForPdf4);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor4($infoDataForPdf4);
            $pdf->AliasNbPages();

            //Faktor 5
            $pdf->AddPage();
            $pdf->generateNilaiFaktor5($nilaifaktor5, $infoDataForPdf5, $faktor5Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor5($infoDataForPdf5);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor5($infoDataForPdf5);
            $pdf->AliasNbPages();

            //Faktor 6
            $pdf->AddPage();
            $pdf->generateNilaiFaktor6($nilaifaktor6, $infoDataForPdf6, $faktor6Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor6($infoDataForPdf6);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor6($infoDataForPdf6);
            $pdf->AliasNbPages();

            //Faktor 7
            $pdf->AddPage();
            $pdf->generateNilaiFaktor7($nilaifaktor7, $infoDataForPdf7, $faktor7Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor7($infoDataForPdf7);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor7($infoDataForPdf7);
            $pdf->AliasNbPages();

            //Faktor 8
            $pdf->AddPage();
            $pdf->generateNilaiFaktor8($nilaifaktor8, $infoDataForPdf8, $faktor8Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor8($infoDataForPdf8);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor8($infoDataForPdf8);
            $pdf->AliasNbPages();

            //Faktor 9
            $pdf->AddPage();
            $pdf->generateNilaiFaktor9($nilaifaktor9, $infoDataForPdf9, $faktor9Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor9($infoDataForPdf9);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor9($infoDataForPdf9);
            $pdf->AliasNbPages();

            //Faktor 10
            $pdf->AddPage();
            $pdf->generateNilaiFaktor10($nilaifaktor10, $infoDataForPdf10, $faktor10Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor10($infoDataForPdf10);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor10($infoDataForPdf10);
            $pdf->AliasNbPages();

            //Faktor 11
            $pdf->AddPage();
            $pdf->generateNilaiFaktor11($nilaifaktor11, $infoDataForPdf11, $faktor11Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor11($infoDataForPdf11);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor11($infoDataForPdf11);
            $pdf->AliasNbPages();

            //Faktor 12
            $pdf->AddPage();
            $pdf->generateNilaiFaktor12($nilaifaktor12, $infoDataForPdf12, $faktor12Data);
            $pdf->Ln(10);
            $pdf->generateKesimpulanFaktor12($infoDataForPdf12);
            $pdf->Ln(10);
            $pdf->generateAnalisaFaktor12($infoDataForPdf12);
            $pdf->AliasNbPages();

            //Show Faktor
            $pdf->AddPage();

            // Combine all the infoData for the factors
            $combinedData = array_merge($infobprData, $infoDataForPdf, $infoDataForPdf3, $infoDataForPdf4, $infoDataForPdf5, $infoDataForPdf6, $infoDataForPdf7, $infoDataForPdf8, $infoDataForPdf9, $infoDataForPdf10, $infoDataForPdf11, $infoDataForPdf12, $infoDataForPdfshow, $infottdForPdfshow);

            // Call the method to generate the summary with the combined data
            $pdf->generateKesimpulanSeluruhFaktor($combinedData);
            $pdf->generateAnalisaSeluruhFaktor($combinedData);

            $pdf->AliasNbPages();

            //Tanda tangan
            $pdf->generatePersetujuanLembar($combinedData);
            $pdf->AliasNbPages();

            log_message('debug', 'Data dari database: ' . print_r($infottdForPdfshow, true));

            // Validasi data
            if (empty($infottdForPdfshow) || !isset($infottdForPdfshow['pdf1_filename']) || !isset($infottdForPdfshow['pdf2_filename'])) {
                throw new \RuntimeException('Data filename PDF tidak lengkap dari database');
            }

            $pdf1Filename = trim($infottdForPdfshow['pdf1_filename']);
            $pdf2Filename = trim($infottdForPdfshow['pdf2_filename']);

            if (empty($pdf1Filename) || empty($pdf2Filename)) {
                throw new \RuntimeException('Nama file PDF tidak boleh kosong');
            }

            // Validasi ekstensi file
            $allowedExtensions = ['pdf'];
            $pdf1Ext = strtolower(pathinfo($pdf1Filename, PATHINFO_EXTENSION));
            $pdf2Ext = strtolower(pathinfo($pdf2Filename, PATHINFO_EXTENSION));

            if (!in_array($pdf1Ext, $allowedExtensions) || !in_array($pdf2Ext, $allowedExtensions)) {
                throw new \RuntimeException('File harus berformat PDF');
            }

            $pdfUploadPath = WRITEPATH . 'uploads/pdf_attachments/';

            // Pastikan direktori ada
            if (!is_dir($pdfUploadPath)) {
                throw new \RuntimeException('Direktori penyimpanan PDF tidak ditemukan');
            }

            $pdf1Path = $pdfUploadPath . $pdf1Filename;
            $pdf2Path = $pdfUploadPath . $pdf2Filename;

            // Debug path file
            log_message('debug', 'Mencari PDF 1 di: ' . $pdf1Path);
            log_message('debug', 'Mencari PDF 2 di: ' . $pdf2Path);
            
            // Proses merge PDF pertama
            if (file_exists($pdf1Path) && is_readable($pdf1Path)) {
                $pageCount = $pdf->mergeExistingPdf($pdf1Path);
                log_message('info', "Berhasil merge {$pdf1Filename} ({$pageCount} halaman)");
            } else {
                throw new \RuntimeException("File PDF pertama tidak ditemukan: {$pdf1Filename}");
            }

            // Proses merge PDF kedua
            if (file_exists($pdf2Path) && is_readable($pdf2Path)) {
                $pageCount = $pdf->mergeExistingPdf($pdf2Path);
                log_message('info', "Berhasil merge {$pdf2Filename} ({$pageCount} halaman)");
            } else {
                throw new \RuntimeException("File PDF kedua tidak ditemukan: {$pdf2Filename}");
            }

            $this->response->setContentType('application/pdf');
            $pdf->Output($filename, 'I');

        } catch (\RuntimeException $e) {
            log_message('error', 'PDF Generation Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        } catch (\Exception $e) {
            log_message('error', 'Unexpected PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat membuat PDF: ' . $e->getMessage());
        }
    }


    public function pdfFaktor()
    {
        // Ambil data dari model dengan join untuk sub_category dan sph
        $faktorData = $this->faktorModel
            ->select('faktor.id as faktor_id, faktor.sub_category, faktor.sph, nilaifaktor.nilai, nilaifaktor.keterangan')  // Ambil data dari kedua tabel
            ->join('nilaifaktor', 'nilaifaktor.faktor1id = faktor.id', 'left')  // Join dengan nilaifaktor berdasarkan faktor1id
            ->orderBy('faktor.id', 'ASC')  // Urutkan berdasarkan id faktor
            ->findAll();  // Ambil semua data

        $infobprData = $this->infobprModel->getAllData();
        if (empty($infobprData)) {
            throw new \RuntimeException('Data BPR tidak ditemukan');
        }
        $infobpr = $infobprData[0];

        $factorsWithDetails = [];
        foreach ($faktorData as $row) {
            $factorsWithDetails[] = [
                'sub_category' => $row['sub_category'], // sub_category dari faktor
                'sph' => $row['sph'],  // sph dari faktor
                'nilai' => $row['nilai'],  // nilai dari nilaifaktor
                'keterangan' => $row['keterangan'],
                'nfaktor' => $row['nfaktor']  // keterangan dari nilaifaktor
            ];
        }

        // Membuat PDF
        $pdf = new PdfSelfassessment('P', 'mm', 'A4');

        // Set header data
        $logoPath = FCPATH . 'asset/img/' . ($infobpr['logo'] ?? '');
        $pdf->setHeaderData([
            'logo' => (!empty($infobpr['logo']) && file_exists($logoPath)) ? $logoPath : '',
            'namabpr' => $infobpr['namabpr'] ?? '',
            'alamat' => $infobpr['alamat'] ?? '',
            'nomor' => $infobpr['nomor'] ?? '',
            'webbpr' => $infobpr['webbpr'] ?? '',
            'email' => $infobpr['email'] ?? ''
        ]);

        // Menambahkan halaman cover jika perlu
        // $pdf->generateCoverPage($infobpr);

        // Panggil generateNilaiFaktor dengan data yang sudah digabungkan
        $pdf->generateNilaiFaktor($factorsWithDetails, $infobpr);

        // Output PDF
        $this->response->setContentType('application/pdf');
        $pdf->Output('Laporan Self Assessment Penilaian Faktor.pdf', 'I');

        exit;
    }


}