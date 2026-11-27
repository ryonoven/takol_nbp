<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_semesterlist;
use App\Models\M_sosialisasipelatihan;

/** Laporan Direktur YMF Kepatuhan — 3. Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan (form K1500). */
class Sosialisasipelatihan extends SemesterListController
{
    public const PAGE = 'Sosialisasipelatihan';
    public const JUDUL = 'Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan';
    public const KICKER = 'Laporan Semesteran · Direktur YMF Kepatuhan · Bagian 3 dari 6';
    public const BACK = ['Showdirymf', 'Laporan Direktur YMF Kepatuhan'];
    public const NAV = Showdirymf::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Pelaksanaan Sosialisasi dan Pelatihan Berkelanjutan.xlsx';
    public const TXT_METHOD = 'exporttxtsosialisasipelatihan';

    protected function newModel(): M_semesterlist
    {
        return new M_sosialisasipelatihan();
    }

    /** TXT TKSBPRKS-2002 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtsosialisasipelatihan()
    {
        return $this->txtBuild('2002', static function (array $r) {
            $topik = self::txtClean($r['topik'] ?? '');
            if ($topik === '') {
                return null;
            }
            $tgl = (string) ($r['tanggal'] ?? '');
            $tgl = ($tgl === '' || str_starts_with($tgl, '0000')) ? '' : self::txtClean(str_replace('-', '', $tgl));
            $uraian = self::txtClean($r['uraian'] ?? '');
            return "K1500|{$topik}|{$tgl}|" . self::txtClean($r['pihakpelaksana'] ?? '') . '|' . self::txtClean($r['namapihak'] ?? '')
                . '|' . self::txtClean($r['kategoripeserta'] ?? '') . '|' . self::txtClean($r['keteranganpeserta'] ?? '')
                . '|' . self::txtClean((string) ($r['jumlahpeserta'] ?? '')) . '|' . ($uraian === '' ? '-' : $uraian);
        });
    }
}
