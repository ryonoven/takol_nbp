<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_ketentuanbpr;
use App\Models\M_semesterlist;

class Ketentuanbpr extends SemesterListController
{
    public const PAGE = 'Ketentuanbpr';
    public const JUDUL = 'Ketentuan Internal yang Dimiliki BPR';
    public const KICKER = 'Laporan Semesteran · Direktur YMF Kepatuhan · Bagian 2 dari 6';
    public const BACK = ['Showdirymf', 'Laporan Direktur YMF Kepatuhan'];
    public const NAV = Showdirymf::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Ketentuan Internal yang Dimiliki.xlsx';
    public const TXT_METHOD = 'exporttxtketentuanbpr';

    protected function newModel(): M_semesterlist
    {
        return new M_ketentuanbpr();
    }

    /** TXT TKSBPRKS-2001 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtketentuanbpr()
    {
        return $this->txtBuild('2001', static function (array $r) {
            $judul = self::txtClean($r['judulketentuan'] ?? '');
            $nomor = self::txtClean($r['nomorketentuan'] ?? '');
            if ($judul === '' && $nomor === '') {
                return null;
            }
            $tgl = (string) ($r['tanggalberlaku'] ?? '');
            $tgl = ($tgl === '' || str_starts_with($tgl, '0000')) ? '' : str_replace('-', '', self::txtClean($tgl));
            return "K1400|{$judul}|{$nomor}|{$tgl}|" . self::txtClean($r['keterangan'] ?? '');
        });
    }
}
