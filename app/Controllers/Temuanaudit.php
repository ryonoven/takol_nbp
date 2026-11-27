<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_semesterlist;
use App\Models\M_temuanaudit;

/** Laporan Audit Intern — II. Temuan Audit (form A2000). */
class Temuanaudit extends SemesterListController
{
    public const PAGE = 'Temuanaudit';
    public const JUDUL = 'Temuan Audit';
    public const KICKER = 'Laporan Semesteran · Audit Intern · Bagian 2 dari 5';
    public const BACK = ['Showaudit', 'Laporan Audit Intern'];
    public const NAV = Showaudit::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Temuan Audit.xlsx';
    public const TXT_METHOD = 'exporttxttemuanaudit';

    protected function newModel(): M_semesterlist
    {
        return new M_temuanaudit();
    }

    /** TXT TKSBPRKS-3001 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxttemuanaudit()
    {
        return $this->txtBuild('3001', static function (array $r) {
            $fakta = self::txtClean($r['fakta'] ?? '');
            $tgl = !empty($r['tanggal']) ? str_replace('-', '', $r['tanggal']) : '';
            if ($fakta === '' && $tgl === '') {
                return null;
            }
            return "A2000|{$fakta}|{$tgl}|" . self::txtClean($r['penyebab'] ?? '') . '|' . self::txtClean($r['dampak'] ?? '');
        });
    }
}
