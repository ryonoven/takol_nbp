<?php

namespace App\Controllers;

use App\Libraries\SemesterListController;
use App\Models\M_formasisdm;
use App\Models\M_semesterlist;

/** Laporan Direktur YMF Kepatuhan — 1. Formasi Sumber Daya Manusia (form K1300). */
class Formasisdm extends SemesterListController
{
    public const PAGE = 'Formasisdm';
    public const JUDUL = 'Formasi Sumber Daya Manusia';
    public const KICKER = 'Laporan Semesteran · Direktur YMF Kepatuhan · Bagian 1 dari 6';
    public const BACK = ['Showdirymf', 'Laporan Direktur YMF Kepatuhan'];
    public const NAV = Showdirymf::NAV;
    public const IMPORT_TEMPLATE = 'Template Impor Formasi Sumber Daya Manusia.xlsx';
    public const TXT_METHOD = 'exporttxtformasisdm';

    protected function newModel(): M_semesterlist
    {
        return new M_formasisdm();
    }

    /**
     * TXT TKSBPRKS-2000 (dipakai juga oleh ZIP Laporan Semesteran).
     * Perbaikan: tanggal pengangkatan kosong / 0000-00-00 ditulis kosong (sebelumnya "00000000").
     */
    public function exporttxtformasisdm()
    {
        return $this->txtBuild('2000', static function (array $r) {
            $nik = self::txtClean($r['nik'] ?? '');
            $nama = self::txtClean($r['nama'] ?? '');
            if ($nik === '' && $nama === '') {
                return null;
            }
            $tgl = (string) ($r['masajabatan'] ?? '');
            $tgl = ($tgl === '' || str_starts_with($tgl, '0000')) ? '' : str_replace('-', '', self::txtClean($tgl));
            return "K1300|{$nik}|{$nama}|" . self::txtClean((string) ($r['jabatan'] ?? '')) . '|' . self::txtClean($r['status'] ?? '')
                . '|' . self::txtClean($r['tingkatpendidikan'] ?? '') . "|{$tgl}";
        });
    }
}
