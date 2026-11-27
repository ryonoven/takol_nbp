<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_pemantauanaudit;
use App\Models\M_semesterlist;

/** Laporan Audit Intern — V. Hasil Pemantauan Komitmen Auditee (form A2300). */
class Pemantauanaudit extends SemesterListController
{
    public const PAGE = 'Pemantauanaudit';
    public const JUDUL = 'Hasil Pemantauan Komitmen Auditee';
    public const KICKER = 'Laporan Semesteran · Audit Intern · Bagian 5 dari 5';
    public const BACK = ['Showaudit', 'Laporan Audit Intern'];
    public const NAV = Showaudit::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Hasil Pemantauan Komitmen Auditee.xlsx';
    public const TXT_METHOD = 'exporttxtpemantauanaudit';

    protected function newModel(): M_semesterlist
    {
        return new M_pemantauanaudit();
    }

    /** TXT TKSBPRKS-3004 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtpemantauanaudit()
    {
        return $this->txtBuild('3004', static function (array $r) {
            $re = self::txtClean($r['realisasi'] ?? '');
            $ha = self::txtClean($r['hasil'] ?? '');
            $st = self::txtClean($r['status'] ?? '');
            return ($re === '' && $ha === '' && $st === '') ? null : "A2300|{$re}|{$ha}|{$st}";
        });
    }
}
