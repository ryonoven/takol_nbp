<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_penyimpangankepatuhan;
use App\Models\M_semesterlist;

/** Laporan Direktur YMF Kepatuhan — 5. Penyimpangan terhadap Kepatuhan Peraturan Perundang-undangan (form K1700). */
class Penyimpangankepatuhan extends SemesterListController
{
    public const PAGE = 'Penyimpangankepatuhan';
    public const JUDUL = 'Penyimpangan terhadap Kepatuhan Peraturan Perundang-undangan';
    public const KICKER = 'Laporan Semesteran · Direktur YMF Kepatuhan · Bagian 5 dari 6';
    public const BACK = ['Showdirymf', 'Laporan Direktur YMF Kepatuhan'];
    public const NAV = Showdirymf::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Penyimpangan Terhadap Kepatuhan Peraturan Perundang-undangan.xlsx';
    public const TXT_METHOD = 'exporttxtpenyimpangankepatuhan';

    protected function newModel(): M_semesterlist
    {
        return new M_penyimpangankepatuhan();
    }

    /**
     * TXT TKSBPRKS-2004 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran).
     * Tanpa data penyimpangan → satu baris "Tidak ada penyimpangan" bertanggal akhir periode (seperti sebelumnya).
     */
    public function exporttxtpenyimpangankepatuhan()
    {
        return $this->txtBuild('2004', static function (array $r) {
            $p = self::txtClean($r['pelanggaran'] ?? '');
            $tgl = (string) ($r['tanggaltemuan'] ?? '');
            $tgl = ($tgl === '' || str_starts_with($tgl, '0000')) ? '' : self::txtClean(str_replace('-', '', $tgl));
            $u = self::txtClean($r['uraian'] ?? '');
            if ($p === '' && $tgl === '' && $u === '') {
                return null;
            }
            return "K1700|{$p}|{$tgl}|{$u}|" . self::txtClean($r['upaya'] ?? '') . '|' . self::txtClean($r['statuspenyelesaian'] ?? '');
        }, static fn(string $tgl) => "K1700|Tidak ada penyimpangan|{$tgl}|Tidak ada|Tidak ada|01");
    }
}
