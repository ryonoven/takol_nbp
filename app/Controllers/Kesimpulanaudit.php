<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_kesimpulanaudit;
use App\Models\M_semesterlist;

/** Laporan Audit Intern — III. Kesimpulan Audit dan Pernyataan Auditor (form A2100). */
class Kesimpulanaudit extends SemesterListController
{
    public const PAGE = 'Kesimpulanaudit';
    public const JUDUL = 'Kesimpulan Audit dan Pernyataan Auditor';
    public const KICKER = 'Laporan Semesteran · Audit Intern · Bagian 3 dari 5';
    public const BACK = ['Showaudit', 'Laporan Audit Intern'];
    public const NAV = Showaudit::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Kesimpulan Audit.xlsx';
    public const TXT_METHOD = 'exporttxtkesimpulanaudit';

    protected function newModel(): M_semesterlist
    {
        return new M_kesimpulanaudit();
    }

    /** TXT TKSBPRKS-3002 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtkesimpulanaudit()
    {
        return $this->txtBuild('3002', static function (array $r) {
            $k = self::txtClean($r['kesimpulan'] ?? '');
            $p = self::txtClean($r['pernyataan'] ?? '');
            return ($k === '' && $p === '') ? null : "A2100|{$k}|{$p}";
        });
    }
}
