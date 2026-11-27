<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_komitmenotoritas;
use App\Models\M_semesterlist;

/** Laporan Direktur YMF Kepatuhan — 6. Pelaksanaan Pemenuhan Komitmen terhadap Otoritas (form K1800). */
class Komitmenotoritas extends SemesterListController
{
    public const PAGE = 'Komitmenotoritas';
    public const JUDUL = 'Pelaksanaan Pemenuhan Komitmen terhadap Otoritas';
    public const KICKER = 'Laporan Semesteran · Direktur YMF Kepatuhan · Bagian 6 dari 6';
    public const BACK = ['Showdirymf', 'Laporan Direktur YMF Kepatuhan'];
    public const NAV = Showdirymf::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Pelaksanaan Pemenuhan Komitmen Terhadap Otoritas.xlsx';
    public const TXT_METHOD = 'exporttxtkomitmenotoritas';

    protected function newModel(): M_semesterlist
    {
        return new M_komitmenotoritas();
    }

    /**
     * TXT TKSBPRKS-2005 (dipakai juga oleh ZIP Laporan Semesteran).
     * Perbaikan: baris yang hanya berisi uraian (field wajib) kini ikut diekspor — sebelumnya terlewat.
     */
    public function exporttxtkomitmenotoritas()
    {
        return $this->txtBuild('2005', static function (array $r) {
            $u = self::txtClean($r['uraian'] ?? '');
            $pemberi = self::txtClean($r['pemberikomitmen'] ?? '');
            $pelaksanaan = self::txtClean($r['pelaksanaan'] ?? '');
            $pelaksana = self::txtClean($r['pelaksanakomitmen'] ?? '');
            if ($u === '' && $pemberi === '' && $pelaksanaan === '' && $pelaksana === '') {
                return null;
            }
            return "K1800|{$u}|{$pemberi}|{$pelaksanaan}|{$pelaksana}|" . self::txtClean($r['statuskomitmen'] ?? '');
        });
    }
}
