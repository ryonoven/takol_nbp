<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_budayakepatuhan;
use App\Models\M_semesterlist;

/** Laporan Direktur YMF Kepatuhan — 4. Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan (form K1600). */
class Budayakepatuhan extends SemesterListController
{
    public const PAGE = 'Budayakepatuhan';
    public const JUDUL = 'Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan';
    public const KICKER = 'Laporan Semesteran · Direktur YMF Kepatuhan · Bagian 4 dari 6';
    public const BACK = ['Showdirymf', 'Laporan Direktur YMF Kepatuhan'];
    public const NAV = Showdirymf::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Penerapan Budaya Kepatuhan dan Fungsi Kepatuhan.xlsx';
    public const TXT_METHOD = 'exporttxtbudayakepatuhan';

    protected function newModel(): M_semesterlist
    {
        return new M_budayakepatuhan();
    }

    /** TXT TKSBPRKS-2003 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtbudayakepatuhan()
    {
        return $this->txtBuild('2003', static function (array $r) {
            $p = self::txtClean($r['pengawasandekom'] ?? '');
            if ($p === '') {
                return null;
            }
            // Seperti sebelumnya: keterangan NULL ditulis "Tidak ada Keterangan"
            $ket = ($r['keterangan'] ?? null) === null ? 'Tidak ada Keterangan' : self::txtClean($r['keterangan']);
            return "K1600|{$p}|" . self::txtClean($r['tindaklanjut'] ?? '') . "|{$ket}";
        });
    }
}
