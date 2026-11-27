<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_lingkupaudit;
use App\Models\M_semesterlist;

/** Laporan Audit Intern — I. Ruang Lingkup dan Waktu Pelaksanaan Audit (form A1900). */
class Lingkupaudit extends SemesterListController
{
    public const PAGE = 'Lingkupaudit';
    public const JUDUL = 'Ruang Lingkup dan Waktu Pelaksanaan Audit';
    public const KICKER = 'Laporan Semesteran · Audit Intern · Bagian 1 dari 5';
    public const BACK = ['Showaudit', 'Laporan Audit Intern'];
    public const NAV = Showaudit::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Ruang Lingkup dan Waktu Pelaksanaan Audit.xlsx';
    public const TXT_METHOD = 'exporttxtlingkupaudit';

    protected function newModel(): M_semesterlist
    {
        return new M_lingkupaudit();
    }

    /** TXT TKSBPRKS-3000 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtlingkupaudit()
    {
        return $this->txtBuild('3000', static function (array $r) {
            $rl = self::txtClean($r['ruanglingkup'] ?? '');
            $mulai = self::txtDate($r['tanggalmulai'] ?? null);
            if ($rl === '' && $mulai === '') {
                return null;
            }
            return "A1900|{$rl}|{$mulai}|" . self::txtDate($r['tanggalberakhir'] ?? null);
        });
    }
}
