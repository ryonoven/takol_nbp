<?php

namespace App\Models;

/**
 * Laporan Direktur YMF Kepatuhan — 1. Formasi Sumber Daya Manusia (form K1300).
 * Tabel tanpa kolom is_null_record (tidak ada fitur "Tidak ada data"); accdir2_at NOT NULL.
 * Kolom `fullname` = nama user penginput; `nama` = nama pegawai.
 */
class M_formasisdm extends M_semesterlist
{
    protected $table = 'formasisdm';
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'tahun', 'semester',
        'nik', 'nama', 'jabatan', 'status', 'tingkatpendidikan', 'masajabatan',
        'fullname', 'accdir2', 'accdir2_by', 'accdir2_at',
    ];

    public const JABATAN = [
        '110' => 'Direktur Utama', '120' => 'Direktur yang membawahkan fungsi kepatuhan', '130' => 'Direktur',
        '210' => 'Komisaris Utama', '220' => 'Komisaris', '230' => 'Komisaris Independen',
        '310' => 'Komite Audit', '320' => 'Komite Pemantau Risiko', '330' => 'Komite Remunerasi dan Nominasi',
        '340' => 'Komite Manajemen Risiko', '350' => 'Komite Lainnya',
        '410' => 'Pejabat Eksekutif Kepatuhan', '420' => 'Pejabat Eksekutif Manajemen Risiko', '430' => 'Pejabat Eksekutif Audit Intern',
        '440' => 'Pejabat Eksekutif APU PPT', '450' => 'Pejabat Eksekutif Lainnya',
    ];
    public const STATUS = ['01' => 'Pengangkatan Baru', '02' => 'Pengangkatan Kembali', '03' => 'Perubahan Jabatan', '04' => 'Tidak Berubah'];
    public const PENDIDIKAN = ['01' => 'S3', '02' => 'S2', '03' => 'S1', '04' => 'D3', '05' => 'SLTA', '99' => 'Lainnya'];

    public const FIELDS = [
        'nama' => ['Nama lengkap', 'type' => 'text', 'max' => 255, 'col' => 'col-md-7', 'short' => 'Nama'],
        'nik' => ['NIK', 'type' => 'text', 'max' => 20, 'digits' => true, 'col' => 'col-md-5', 'short' => 'NIK'],
        'jabatan' => ['Jabatan', 'type' => 'select', 'options' => self::JABATAN, 'col' => 'col-md-7', 'short' => 'Jabatan',
            // ejaan lama pada template impor
            'aliases' => ['komite pemantau resiko' => '320', 'komite manajemen resiko' => '340', 'pejabat eksekutif manajemen resiko' => '420']],
        'masajabatan' => ['Tanggal pengangkatan', 'type' => 'date', 'required' => false, 'col' => 'col-md-5', 'short' => 'Diangkat'],
        'status' => ['Status kepegawaian', 'type' => 'select', 'options' => self::STATUS, 'required' => false, 'col' => 'col-md-7', 'short' => 'Status'],
        'tingkatpendidikan' => ['Tingkat pendidikan', 'type' => 'select', 'options' => self::PENDIDIKAN, 'required' => false, 'col' => 'col-md-5', 'short' => 'Pendidikan'],
    ];
    /** Template: A nama · B NIK · C jabatan · D status · E pendidikan · F tanggal pengangkatan. */
    public const IMPORT_COLS = ['nama', 'nik', 'jabatan', 'status', 'tingkatpendidikan', 'masajabatan'];

    /** Kartu orang seperti halaman Tugas & Tanggung Jawab Direksi. */
    public const CARD = [
        'title' => 'nama',
        'tag' => 'jabatan',
        'sub' => ['nik', 'masajabatan'],
        'chips' => ['status', 'tingkatpendidikan'],
        'utama' => ['110', '120', '210'],
    ];
    /** Persetujuan Direktur Utama untuk seluruh formasi sekaligus. */
    public const ROW_APPROVAL = false;

    public const HAS_NODATA = false;
    /** Sinkron periode sebelumnya: pegawai yang NIK-nya sudah ada dilewati. */
    public const DUP_KEY = ['nik'];

    /** Dipakai LaporanPdfGcg / laporan lain. */
    public function getDataByKodebprAndPeriode(string $kodebpr, int $periodeId): array
    {
        return $this->rows($kodebpr, $periodeId);
    }
}
