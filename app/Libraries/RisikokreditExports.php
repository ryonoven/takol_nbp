<?php

namespace App\Libraries;

/**
 * Endpoint ekspor lama Risikokredit (JSON untuk generator PDF Profil Risiko & TXT untuk ZIP semesteran).
 * Dipindahkan apa adanya dari controller; periode aktif kini wajib milik BPR user ($this->periodeId).
 * Dipakai oleh: Pdfsemesteran/export_pdf.php, export_all_pdf_zip.php, Showprofilresiko, Showsemesteran.
 */
trait RisikokreditExports
{
    private function checkAuth()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        return null;
    }

    private function checkPeriode()
    {
        if (!$this->periodeId) {
            return redirect()->to('/Periodeprofilresiko')->with('error', 'Silakan pilih periode aktif terlebih dahulu.');
        }
        return null;
    }

    public function exporttxtrisikokredit()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }

        // Periode aktif bukan milik BPR user → tidak ada berkas (cegah ekspor data BPR lain)
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;
        $periodeDetail = $this->periodeModel->find($this->periodeId);

        $modalinti = $periodeDetail['modalinti'];
        $kategori = $periodeDetail['kategori'];
        $nomorsurat = $periodeDetail['nomorsurat'];
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];

        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";

        $titleDate = str_replace('-', '', $exportDate);

        $sanitizeTxt = function ($text) {
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            return trim(preg_replace('/\s+/', ' ', $text));
        };

        $data_risikokredit = $this->nilaiModel->getDataByKodebprAndPeriode($kodebpr, $periodeId);
        $data_infobpr = $this->infobprModel->getDataByKodebpr($kodebpr);

        $sandibpr = '';
        $kodejenis = '';
        if (!empty($data_infobpr)) {
            $infobpr = $data_infobpr[0];
            $sandibpr = $infobpr['sandibpr'] ?? '';
            $kodejenis = $infobpr['kodejenis'] ?? '';
        }

        $kodeMap = [
            1 => '1210',
            2 => '1211',
            3 => '1212',
            4 => '1213',
            5 => '1214',
            6 => '1220',
            7 => '1221',
            8 => '1222',
            9 => '1223',
            10 => '1230',
            11 => '1240',
            12 => '1299',
            13 => '1292',
        ];

        $indexedData = [];
        foreach ($data_risikokredit as $row) {
            if (isset($row['faktor1id']))
                $indexedData[$row['faktor1id']] = $row;
        }

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0101|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        foreach ($kodeMap as $faktorId => $kode) {
            if (isset($indexedData[$faktorId])) {
                $row = $indexedData[$faktorId];
                $rasio = $sanitizeTxt($row['rasiokredit'] ?? '');
                $pk = $sanitizeTxt($row['penilaiankredit'] ?? '');
                $ket = $sanitizeTxt($row['keterangan'] ?? '-');
            } else {
                $rasio = '';
                $pk = '';
                $ket = '';
            }
            $output .= "D01|{$kode}|{$rasio}|{$pk}|{$ket}\r\n";
        }

        $filename = "TKSBPRKS-0101-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }

    public function exportPDF()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data tidak valid');

        $periodeDetail = $this->periodeModel->find($this->periodeId);
        $bprData = $this->infobprModel->where('kodebpr', $this->userKodebpr)->first();

        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')
            ->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 13))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        $kalkulatorData = $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first();
        $nilai13 = $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();
        $nilai14 = $this->nilaiModel->where(['faktor1id' => 14, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'periode' => $periodeDetail,
                'bpr' => $bprData,
                'nilai' => $this->strukturDataNilai($nilaiData),
                'kalkulator' => $kalkulatorData,
                'nilai13' => $nilai13,
                'nilai14' => $nilai14,
                'kesimpulan' => $nilai13,
            ],
        ]);
    }

    public function viewPDFPreview()
    {
        return view('Risikokredit/export_pdf');
    }

    public function exportPDFGabungan()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if (!$this->userKodebpr || !$this->periodeId)
            return redirect()->back()->with('error', 'Data tidak valid');

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'inheren' => $this->getDataInheren(),
                'kpmr' => $this->getDataKPMR(),
                'kepatuhan' => $this->getDataKepatuhanInheren(),
                'kepatuhankpmr' => $this->getDataKepatuhanKPMR(),
                'likuiditas' => $this->getDataLikuiditas(),
                'likuidiaskpmr' => $this->getDataLikuiditasKPMR(),
                'periode' => $this->periodeModel->find($this->periodeId),
                'bpr' => $this->infobprModel->where('kodebpr', $this->userKodebpr)->first(),
            ],
        ]);
    }

    public function exportPDFGabunganOperasional()
    {
        if (!$this->auth->check())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;
        if (!$kodebpr || !$periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('risikooperasional')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(36, 49))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('risikooperasional_kpmr')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(51, 71))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $kodebpr],
                    'inheren' => $this->buildOperasionalInherenStructure($dataInheren),
                    'kpmr' => $this->buildOperasionalKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganOperasional: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
    }

    public function exportPDFGabunganKepatuhan()
    {
        if (!$this->auth->check())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;
        if (!$kodebpr || !$periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('risikokepatuhan')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(73, 82))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('risikokepatuhan_kpmr')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(84, 103))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $kodebpr],
                    'inheren' => $this->buildKepatuhanInherenStructure($dataInheren),
                    'kpmr' => $this->buildKepatuhanKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganKepatuhan: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
    }

    public function exportPDFGabunganLikuiditas()
    {
        if (!$this->auth->check())
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;
        if (!$kodebpr || !$periodeId)
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap']);

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('likuiditasinheren')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(105, 116))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            $dataKPMR = $this->db->table('likuiditaskpmr')
                ->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(118, 136))->orderBy('faktor1id', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'periode' => ['semester' => $periodeDetail['semester'] ?? '', 'tahun' => $periodeDetail['tahun'] ?? ''],
                    'bpr' => ['namabpr' => $bprData['namabpr'] ?? '', 'kodebpr' => $kodebpr],
                    'inheren' => $this->buildLikuiditasInherenStructure($dataInheren),
                    'kpmr' => $this->buildLikuiditasKPMRStructure($dataKPMR),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganLikuiditas: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
    }

    public function exportPDFGabunganReputasi()
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        if (!$kodebpr || !$periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
        }

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('reputasiinheren')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(138, 149))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $dataKPMR = $this->db->table('reputasikpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(151, 169))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $inheren = $this->buildReputasiInherenStructure($dataInheren);
            $kpmr = $this->buildReputasiKPMRStructure($dataKPMR);

            $responseData = [
                'status' => 'success',
                'data' => [
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'bpr' => [
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'kodebpr' => $kodebpr
                    ],
                    'inheren' => $inheren,
                    'kpmr' => $kpmr
                ]
            ];

            return $this->response->setJSON($responseData);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganReputasi: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ]);
        }
    }

    public function exportPDFGabunganStratejik()
    {
        if (!$this->auth->check()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $kodebpr = $this->userKodebpr;
        $periodeId = $this->periodeId;

        if (!$kodebpr || !$periodeId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
        }

        try {
            $periodeDetail = $this->periodeModel->find($periodeId);
            $bprData = $this->infobprModel->where('kodebpr', $kodebpr)->first();

            $dataInheren = $this->db->table('stratejikinheren')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(171, 180))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $dataKPMR = $this->db->table('stratejikkpmr')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->whereIn('faktor1id', range(182, 200))
                ->orderBy('faktor1id', 'ASC')
                ->get()
                ->getResultArray();

            $inheren = $this->buildStratejikInherenStructure($dataInheren);
            $kpmr = $this->buildStratejikKPMRStructure($dataKPMR);

            $responseData = [
                'status' => 'success',
                'data' => [
                    'periode' => [
                        'semester' => $periodeDetail['semester'] ?? '',
                        'tahun' => $periodeDetail['tahun'] ?? ''
                    ],
                    'bpr' => [
                        'namabpr' => $bprData['namabpr'] ?? '',
                        'kodebpr' => $kodebpr
                    ],
                    'inheren' => $inheren,
                    'kpmr' => $kpmr
                ]
            ];

            return $this->response->setJSON($responseData);

        } catch (\Exception $e) {
            log_message('error', 'Error exportPDFGabunganStratejik: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ]);
        }
    }


    public function viewExportPDFOperasional()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        return view('Risikokredit/export_pdf_operasional_view', ['judul' => 'Export PDF Risiko Operasional']);
    }

    public function exportAllPDFToZipView()
    {
        if ($redirect = $this->checkAuth())
            return $redirect;
        if ($redirect = $this->checkPeriode())
            return $redirect;
        return view('risikokredit/export_pdf', ['baseUrl' => base_url()]);
    }


    private function strukturDataNilai($nilaiData): array
    {
        $struktur = [
            'komposisi' => ['kategori' => null, 'children' => []],
            'kualitas' => ['kategori' => null, 'children' => []],
            'strategi' => null,
            'eksternal' => null,
            'lainnya' => null,
            'rata_rata' => null,
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 1)
                $struktur['komposisi']['kategori'] = $nilai;
            elseif (in_array($fid, [2, 3, 4, 5]))
                $struktur['komposisi']['children'][] = $nilai;
            elseif ($fid == 6)
                $struktur['kualitas']['kategori'] = $nilai;
            elseif (in_array($fid, [7, 8, 9]))
                $struktur['kualitas']['children'][] = $nilai;
            elseif ($fid == 10)
                $struktur['strategi'] = $nilai;
            elseif ($fid == 11)
                $struktur['eksternal'] = $nilai;
                    elseif ($fid == 12)
                $struktur['lainnya'] = $nilai;
            elseif ($fid == 13)
                $struktur['rata_rata'] = $nilai;
        }
        return $struktur;
    }

    private function getDataInheren(): array
    {
        $nilaiData = $this->db->table('risikokredit as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(1, 14))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilai($nilaiData),
            'kalkulator' => $this->kalkulatorModel->where('kodebpr', $this->userKodebpr)->where('periode_id', $this->periodeId)->first(),
            'nilai13' => $this->nilaiModel->where(['faktor1id' => 13, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai14' => $this->nilaiModel->where(['faktor1id' => 14, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function getDataKPMR(): array
    {
        $kpmrModel = new \App\Models\M_risikokreditkpmr();
        $nilaiData = $this->db->table('risikokredit_kpmr as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(16, 34))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKPMR($nilaiData),
            'nilai33' => $kpmrModel->where(['faktor1id' => 33, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai34' => $kpmrModel->where(['faktor1id' => 34, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiKPMR($nilaiData): array
    {
        $struktur = [
            'pengawasan' => ['kategori' => null, 'children' => []],
            'kebijakan' => ['kategori' => null, 'children' => []],
            'proses' => ['kategori' => null, 'children' => []],
            'pengendalian' => ['kategori' => null, 'children' => []],
            'rata_rata' => null,
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 16)
                $struktur['pengawasan']['kategori'] = $nilai;
            elseif (in_array($fid, [17, 18, 19, 20, 21, 22]))
                $struktur['pengawasan']['children'][] = $nilai;
            elseif ($fid == 23)
                $struktur['kebijakan']['kategori'] = $nilai;
            elseif (in_array($fid, [24, 25, 26]))
                $struktur['kebijakan']['children'][] = $nilai;
            elseif ($fid == 27)
                $struktur['proses']['kategori'] = $nilai;
            elseif (in_array($fid, [28, 29]))
                $struktur['proses']['children'][] = $nilai;
            elseif ($fid == 30)
                $struktur['pengendalian']['kategori'] = $nilai;
            elseif (in_array($fid, [31, 32]))
                $struktur['pengendalian']['children'][] = $nilai;
            elseif ($fid == 33)
                $struktur['rata_rata'] = $nilai;
        }
        return $struktur;
    }

    private function getDataKepatuhanInheren(): array
    {
        $model = new \App\Models\M_risikokepatuhan();
        $nilaiData = $this->db->table('risikokepatuhan as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(73, 82))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKepatuhan($nilaiData),
            'nilai81' => $model->where(['faktor1id' => 81, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai82' => $model->where(['faktor1id' => 82, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiKepatuhan($nilaiData): array
    {
        $struktur = ['pelanggaran' => ['kategori' => null, 'children' => []], 'hukum' => ['kategori' => null, 'children' => []], 'lainnya' => null];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 73)
                $struktur['pelanggaran']['kategori'] = $nilai;
            elseif (in_array($fid, [74, 75]))
                $struktur['pelanggaran']['children'][] = $nilai;
            elseif ($fid == 76)
                $struktur['hukum']['kategori'] = $nilai;
            elseif (in_array($fid, [77, 78, 79]))
                $struktur['hukum']['children'][] = $nilai;
            elseif ($fid == 80)
                $struktur['lainnya'] = $nilai;
        }
        return $struktur;
    }

    private function getDataKepatuhanKPMR(): array
    {
        $model = new \App\Models\M_kepatuhankpmr();
        $nilaiData = $this->db->table('risikokepatuhan_kpmr as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(84, 103))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiKepatuhanKPMR($nilaiData),
            'nilai102' => $model->where(['faktor1id' => 102, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai103' => $model->where(['faktor1id' => 103, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiKepatuhanKPMR($nilaiData): array
    {
        $struktur = [
            'pengawasan' => ['kategori' => null, 'children' => []],
            'kebijakan' => ['kategori' => null, 'children' => []],
            'proses' => ['kategori' => null, 'children' => []],
            'pengendalian' => ['kategori' => null, 'children' => []],
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 84)
                $struktur['pengawasan']['kategori'] = $nilai;
            elseif (in_array($fid, [85, 86, 87, 88, 89, 90, 91]))
                $struktur['pengawasan']['children'][] = $nilai;
            elseif ($fid == 92)
                $struktur['kebijakan']['kategori'] = $nilai;
            elseif (in_array($fid, [93, 94, 95]))
                $struktur['kebijakan']['children'][] = $nilai;
            elseif ($fid == 96)
                $struktur['proses']['kategori'] = $nilai;
            elseif (in_array($fid, [97, 98]))
                $struktur['proses']['children'][] = $nilai;
            elseif ($fid == 99)
                $struktur['pengendalian']['kategori'] = $nilai;
            elseif (in_array($fid, [100, 101]))
                $struktur['pengendalian']['children'][] = $nilai;
        }
        return $struktur;
    }

    private function getDataLikuiditas(): array
    {
        $model = new \App\Models\M_likuiditasinheren();
        $nilaiData = $this->db->table('likuiditasinheren as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(105, 116))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiLikuiditas($nilaiData),
            'nilai115' => $model->where(['faktor1id' => 115, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai116' => $model->where(['faktor1id' => 116, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiLikuiditas($nilaiData): array
    {
        $struktur = ['konsentrasi' => ['kategori' => null, 'children' => []], 'kerentanan' => ['kategori' => null, 'children' => []], 'lainnya' => null];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 105)
                $struktur['konsentrasi']['kategori'] = $nilai;
            elseif (in_array($fid, [106, 107, 108, 109, 110]))
                $struktur['konsentrasi']['children'][] = $nilai;
            elseif ($fid == 111)
                $struktur['kerentanan']['kategori'] = $nilai;
            elseif (in_array($fid, [112, 113]))
                $struktur['kerentanan']['children'][] = $nilai;
            elseif ($fid == 114)
                $struktur['lainnya'] = $nilai;
        }
        return $struktur;
    }

    private function getDataLikuiditasKPMR(): array
    {
        $model = new \App\Models\M_likuiditaskpmr();
        $nilaiData = $this->db->table('likuiditaskpmr as r')
            ->select('r.*, u.fullname')->join('users as u', 'u.id = r.user_id', 'left')
            ->where('r.kodebpr', $this->userKodebpr)->where('r.periode_id', $this->periodeId)
            ->whereIn('r.faktor1id', range(118, 136))->orderBy('r.faktor1id', 'ASC')
            ->get()->getResultArray();

        return [
            'nilai' => $this->strukturDataNilaiLikuiditasKPMR($nilaiData),
            'nilai135' => $model->where(['faktor1id' => 135, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
            'nilai136' => $model->where(['faktor1id' => 136, 'kodebpr' => $this->userKodebpr, 'periode_id' => $this->periodeId])->first(),
        ];
    }

    private function strukturDataNilaiLikuiditasKPMR($nilaiData): array
    {
        $struktur = [
            'pengawasan' => ['kategori' => null, 'children' => []],
            'kebijakan' => ['kategori' => null, 'children' => []],
            'proses' => ['kategori' => null, 'children' => []],
            'pengendalian' => ['kategori' => null, 'children' => []],
        ];
        foreach ($nilaiData as $nilai) {
            $fid = $nilai['faktor1id'];
            if ($fid == 118)
                $struktur['pengawasan']['kategori'] = $nilai;
            elseif (in_array($fid, [119, 120, 121, 122, 123, 124]))
                $struktur['pengawasan']['children'][] = $nilai;
            elseif ($fid == 125)
                $struktur['kebijakan']['kategori'] = $nilai;
            elseif (in_array($fid, [126, 127, 128]))
                $struktur['kebijakan']['children'][] = $nilai;
            elseif ($fid == 129)
                $struktur['proses']['kategori'] = $nilai;
            elseif (in_array($fid, [130, 131]))
                $struktur['proses']['children'][] = $nilai;
            elseif ($fid == 132)
                $struktur['pengendalian']['kategori'] = $nilai;
            elseif (in_array($fid, [133, 134]))
                $struktur['pengendalian']['children'][] = $nilai;
        }
        return $struktur;
    }

    private function buildOperasionalInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'kompleksitas' => ['kategori' => $lookup[36] ?? null, 'children' => array_values(array_filter([$lookup[37] ?? null, $lookup[38] ?? null, $lookup[39] ?? null, $lookup[40] ?? null]))],
                'sdm' => ['kategori' => $lookup[41] ?? null, 'children' => array_values(array_filter([$lookup[42] ?? null, $lookup[43] ?? null]))],
                'ti' => $lookup[44] ?? null,
                'fraud' => $lookup[45] ?? null,
                'eksternal' => $lookup[46] ?? null,
                'lainnya' => $lookup[47] ?? null,
            ],
            'nilai13' => $lookup[48] ?? null,
            'nilai14' => $lookup[49] ?? null,
        ];
    }

    private function buildOperasionalKPMRStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pengawasan' => ['kategori' => $lookup[51] ?? null, 'children' => array_values(array_filter([$lookup[52] ?? null, $lookup[53] ?? null, $lookup[54] ?? null, $lookup[55] ?? null, $lookup[56] ?? null, $lookup[57] ?? null]))],
                'kebijakan' => ['kategori' => $lookup[58] ?? null, 'children' => array_values(array_filter([$lookup[59] ?? null, $lookup[60] ?? null, $lookup[61] ?? null]))],
                'proses' => ['kategori' => $lookup[62] ?? null, 'children' => array_values(array_filter([$lookup[63] ?? null, $lookup[64] ?? null, $lookup[65] ?? null, $lookup[66] ?? null]))],
                'pengendalian' => ['kategori' => $lookup[67] ?? null, 'children' => array_values(array_filter([$lookup[68] ?? null, $lookup[69] ?? null]))],
            ],
            'nilai33' => $lookup[70] ?? null,
            'nilai34' => $lookup[71] ?? null,
        ];
    }

    private function buildKepatuhanInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pelanggaran' => ['kategori' => $lookup[73] ?? null, 'children' => array_values(array_filter([$lookup[74] ?? null, $lookup[75] ?? null]))],
                'hukum' => ['kategori' => $lookup[76] ?? null, 'children' => array_values(array_filter([$lookup[77] ?? null, $lookup[78] ?? null, $lookup[79] ?? null]))],
                'lainnya' => $lookup[80] ?? null,
            ],
            'nilai81' => $lookup[81] ?? null,
            'nilai82' => $lookup[82] ?? null,
        ];
    }

    private function buildKepatuhanKPMRStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pengawasan' => ['kategori' => $lookup[84] ?? null, 'children' => array_values(array_filter([$lookup[85] ?? null, $lookup[86] ?? null, $lookup[87] ?? null, $lookup[88] ?? null, $lookup[89] ?? null, $lookup[90] ?? null, $lookup[91] ?? null]))],
                'kebijakan' => ['kategori' => $lookup[92] ?? null, 'children' => array_values(array_filter([$lookup[93] ?? null, $lookup[94] ?? null, $lookup[95] ?? null]))],
                'proses' => ['kategori' => $lookup[96] ?? null, 'children' => array_values(array_filter([$lookup[97] ?? null, $lookup[98] ?? null]))],
                'pengendalian' => ['kategori' => $lookup[99] ?? null, 'children' => array_values(array_filter([$lookup[100] ?? null, $lookup[101] ?? null]))],
            ],
            'nilai102' => $lookup[102] ?? null,
            'nilai103' => $lookup[103] ?? null,
        ];
    }

    private function buildLikuiditasInherenStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'konsentrasi' => ['kategori' => $lookup[105] ?? null, 'children' => array_values(array_filter([$lookup[106] ?? null, $lookup[107] ?? null, $lookup[108] ?? null, $lookup[109] ?? null, $lookup[110] ?? null]))],
                'kerentanan' => ['kategori' => $lookup[111] ?? null, 'children' => array_values(array_filter([$lookup[112] ?? null, $lookup[113] ?? null]))],
                'lainnya' => $lookup[114] ?? null,
            ],
            'nilai115' => $lookup[115] ?? null,
            'nilai116' => $lookup[116] ?? null,
        ];
    }

    private function buildLikuiditasKPMRStructure(array $dataArray): array
    {
        $lookup = array_column($dataArray, null, 'faktor1id');
        return [
            'nilai' => [
                'pengawasan' => ['kategori' => $lookup[118] ?? null, 'children' => array_values(array_filter([$lookup[119] ?? null, $lookup[120] ?? null, $lookup[121] ?? null, $lookup[122] ?? null, $lookup[123] ?? null, $lookup[124] ?? null]))],
                'kebijakan' => ['kategori' => $lookup[125] ?? null, 'children' => array_values(array_filter([$lookup[126] ?? null, $lookup[127] ?? null, $lookup[128] ?? null]))],
                'proses' => ['kategori' => $lookup[129] ?? null, 'children' => array_values(array_filter([$lookup[130] ?? null, $lookup[131] ?? null]))],
                'pengendalian' => ['kategori' => $lookup[132] ?? null, 'children' => array_values(array_filter([$lookup[133] ?? null, $lookup[134] ?? null]))],
            ],
            'nilai102' => $lookup[135] ?? null,
            'nilai103' => $lookup[136] ?? null,
        ];
    }

    private function buildReputasiInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengelolaan' => [
                    'kategori' => $lookup[138] ?? null,
                    'children' => array_filter([
                        $lookup[139] ?? null,
                        $lookup[140] ?? null
                    ])
                ],
                'frekuensi' => $lookup[141] ?? null,
                'keluhan' => [
                    'kategori' => $lookup[142] ?? null,
                    'children' => array_filter([
                        $lookup[143] ?? null
                    ])
                ],
                'etika' => [
                    'kategori' => $lookup[144] ?? null,
                    'children' => array_filter([
                        $lookup[145] ?? null,
                        $lookup[146] ?? null
                    ])
                ],
                'lainnya' => $lookup[147] ?? null
            ],
            'nilai148' => $lookup[148] ?? null,
            'nilai149' => $lookup[149] ?? null
        ];

        return $structure;
    }

    private function buildReputasiKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[151] ?? null,
                    'children' => array_filter([
                        $lookup[152] ?? null,
                        $lookup[153] ?? null,
                        $lookup[154] ?? null,
                        $lookup[155] ?? null,
                        $lookup[156] ?? null,
                        $lookup[157] ?? null
                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[158] ?? null,
                    'children' => array_filter([
                        $lookup[159] ?? null,
                        $lookup[160] ?? null,
                        $lookup[161] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[162] ?? null,
                    'children' => array_filter([
                        $lookup[163] ?? null,
                        $lookup[164] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[165] ?? null,
                    'children' => array_filter([
                        $lookup[166] ?? null,
                        $lookup[167] ?? null
                    ])
                ]
            ],
            'nilai168' => $lookup[168] ?? null,
            'nilai169' => $lookup[169] ?? null
        ];

        return $structure;
    }

    private function buildStratejikInherenStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'penetapan' => $lookup[171] ?? null,
                'penyusunan' => [
                    'kategori' => $lookup[172] ?? null,
                    'children' => array_filter([
                        $lookup[173] ?? null,
                        $lookup[174] ?? null
                    ])
                ],
                'pencapaian' => [
                    'kategori' => $lookup[175] ?? null,
                    'children' => array_filter([
                        $lookup[176] ?? null,
                        $lookup[177] ?? null
                    ])
                ],
                'lainnya' => $lookup[178] ?? null
            ],
            'nilai179' => $lookup[179] ?? null,
            'nilai180' => $lookup[180] ?? null
        ];

        return $structure;
    }

    private function buildStratejikKPMRStructure($dataArray)
    {
        $lookup = [];
        foreach ($dataArray as $item) {
            $lookup[$item['faktor1id']] = $item;
        }

        $structure = [
            'nilai' => [
                'pengawasan' => [
                    'kategori' => $lookup[182] ?? null,
                    'children' => array_filter([
                        $lookup[183] ?? null,
                        $lookup[184] ?? null,
                        $lookup[185] ?? null,
                        $lookup[186] ?? null,
                        $lookup[187] ?? null,
                        $lookup[188] ?? null
                    ])
                ],
                'kebijakan' => [
                    'kategori' => $lookup[189] ?? null,
                    'children' => array_filter([
                        $lookup[190] ?? null,
                        $lookup[191] ?? null,
                        $lookup[192] ?? null
                    ])
                ],
                'proses' => [
                    'kategori' => $lookup[193] ?? null,
                    'children' => array_filter([
                        $lookup[194] ?? null,
                        $lookup[195] ?? null
                    ])
                ],
                'pengendalian' => [
                    'kategori' => $lookup[196] ?? null,
                    'children' => array_filter([
                        $lookup[197] ?? null,
                        $lookup[198] ?? null
                    ])
                ]
            ],
            'nilai199' => $lookup[199] ?? null,
            'nilai200' => $lookup[200] ?? null
        ];

        return $structure;
    }
}
