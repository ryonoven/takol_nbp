<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_rekomendasiaudit;
use App\Models\M_semesterlist;

/** Laporan Audit Intern — IV. Rekomendasi Auditor, Tanggapan Auditee, dan Tindak Lanjut (form A2200). */
class Rekomendasiaudit extends SemesterListController
{
    public const PAGE = 'Rekomendasiaudit';
    public const JUDUL = 'Rekomendasi Auditor, Tanggapan Auditee, dan Tindak Lanjut';
    public const KICKER = 'Laporan Semesteran · Audit Intern · Bagian 4 dari 5';
    public const BACK = ['Showaudit', 'Laporan Audit Intern'];
    public const NAV = Showaudit::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Rekomendasi Audit.xlsx';
    public const TXT_METHOD = 'exporttxtrekomendasiaudit';

    protected function newModel(): M_semesterlist
    {
        return new M_rekomendasiaudit();
    }

    /** TXT TKSBPRKS-3003 (format tidak berubah; dipakai juga oleh ZIP Laporan Semesteran). */
    public function exporttxtrekomendasiaudit()
    {
        return $this->txtBuild('3003', static function (array $r) {
            $p = self::txtClean($r['penyimpangan'] ?? '');
            $u = self::txtClean($r['usulan'] ?? '');
            if ($p === '' && $u === '') {
                return null;
            }
            $bw = !empty($r['bataswaktu']) ? str_replace('-', '', $r['bataswaktu']) : '';
            return "A2200|{$p}|{$u}|" . self::txtClean($r['tindaklanjut'] ?? '') . '|' . self::txtClean($r['uraian'] ?? '') . "|{$bw}";
        });
    }
}
