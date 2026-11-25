<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ringkasan Laporan Transparansi Pelaksanaan Tata Kelola (laporan tahunan).
 * Daftar 18 bagian + status approval (Komisaris Utama & Direktur Utama) dari tabel masing-masing.
 */
class M_showtransparansi extends Model
{
    protected $table         = 'showtransparansi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'kodebpr', 'periode_id', 'tahun', 'dirut', 'komut', 'tanggal', 'lokasi',
        'kesimpulan', 'positifstruktur', 'positifproses', 'positifhasil',
        'negatifstruktur', 'negatifproses', 'negatifhasil',
        'pdf1_filename', 'pdf2_filename', 'cover',
    ];

    /**
     * Bagian laporan (urutan = nomor di halaman).
     *  - controller : halaman pengisian (juga dipakai untuk link).
     *  - table      : sumber status (kolom kodebpr, periode_id, accdekom, is_approved).
     *  - export     : method export TXT di controller tsb; null = belum ada (tidak masuk ZIP).
     *  - optional   : boleh kosong — tidak wajib diisi/di-approve bila memang tidak ada data.
     */
    public const SECTIONS = [
        ['name' => 'Penjelasan Umum Penerapan Tata Kelola', 'controller' => 'Penjelasanumum', 'table' => 'penjelasanumum', 'export' => 'exporttxtpenjelasanumum', 'optional' => false],
        ['name' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Direksi', 'controller' => 'Tgjwbdir', 'table' => 'tgjwbdir', 'export' => 'exporttxttgjwbdir', 'optional' => false],
        ['name' => 'Pelaksanaan Tugas dan Tanggung Jawab Anggota Dewan Komisaris', 'controller' => 'Tgjwbdekom', 'table' => 'tgjwbdekom', 'export' => 'exporttxttgjwbdekom', 'optional' => false],
        ['name' => 'Tugas, Tanggung Jawab, Program Kerja, dan Realisasi Program Kerja Komite', 'controller' => 'Tgjwbkomite', 'table' => 'tgjwbkomite', 'export' => 'exporttxttgjwbkomite', 'optional' => true],
        ['name' => 'Struktur, Keanggotaan, Keahlian, dan Independensi Anggota Komite', 'controller' => 'Strukturkomite', 'table' => 'strukturkomite', 'export' => 'exporttxtstrukturkomite', 'optional' => true],
        ['name' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada BPR', 'controller' => 'Sahamdirdekom', 'table' => 'sahamdirdekom', 'export' => null, 'optional' => true],
        ['name' => 'Kepemilikan Saham Anggota Direksi, Dewan Komisaris, dan Pemegang Saham pada Kelompok Usaha BPR', 'controller' => 'Shmusahadirdekom', 'table' => 'shmusahadirdekom', 'export' => 'exporttxtshmusahadirdekom', 'optional' => true],
        ['name' => 'Kepemilikan Saham Anggota Direksi dan Dewan Komisaris pada Perusahaan Lain', 'controller' => 'Shmdirdekomlain', 'table' => 'shmdirdekomlain', 'export' => 'exporttxtshmdirdekomlain', 'optional' => true],
        ['name' => 'Hubungan Keuangan Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR', 'controller' => 'Keuangandirdekompshm', 'table' => 'keuangandirdekompshm', 'export' => 'exporttxtkeuangandirdekompshm', 'optional' => true],
        ['name' => 'Hubungan Keluarga Anggota Direksi, Anggota Dewan Komisaris, dan Pemegang Saham pada BPR', 'controller' => 'Keluargadirdekompshm', 'table' => 'keluargadirdekompshm', 'export' => 'exporttxtkeluargadirdekompshm', 'optional' => true],
        ['name' => 'Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris', 'controller' => 'Paketkebijakandirdekom', 'table' => 'paketkebijakandirdekom', 'export' => 'exporttxtpaketkebijakandirdekom', 'optional' => false],
        ['name' => 'Rasio Gaji Tertinggi dan Gaji Terendah', 'controller' => 'Rasiogaji', 'table' => 'rasiogaji', 'export' => 'exporttxtrasiogaji', 'optional' => false],
        ['name' => 'Pelaksanaan Rapat dalam 1 (satu) tahun', 'controller' => 'Rapat', 'table' => 'rapat', 'export' => 'exporttxtrapat', 'optional' => false],
        ['name' => 'Kehadiran Anggota Dewan Komisaris', 'controller' => 'Kehadirandekom', 'table' => 'kehadirandekom', 'export' => 'exporttxtkehadirandekom', 'optional' => false],
        ['name' => 'Jumlah Penyimpangan Intern (Internal Fraud)', 'controller' => 'Fraudinternal', 'table' => 'fraudinternal', 'export' => 'exporttxtfraudinternal', 'optional' => true],
        ['name' => 'Permasalahan Hukum yang Dihadapi', 'controller' => 'Masalahhukum', 'table' => 'masalahhukum', 'export' => 'exporttxtmasalahhukum', 'optional' => true],
        ['name' => 'Transaksi yang Mengandung Benturan Kepentingan', 'controller' => 'Transaksikepentingan', 'table' => 'transaksikepentingan', 'export' => 'exporttxttransaksikepentingan', 'optional' => true],
        ['name' => 'Pemberian Dana untuk Kegiatan Sosial dan Kegiatan Politik', 'controller' => 'Danasosial', 'table' => 'danasosial', 'export' => 'exporttxtdanasosial', 'optional' => true],
    ];

    /**
     * Semua bagian dengan status:
     *  hasData, komut (accdekom = 1), dirut (is_approved = 1),
     *  skip  (opsional & tanpa data -> tidak perlu diisi),
     *  done  (skip atau sudah disetujui Direktur Utama -> syarat export).
     */
    public function getSections(string $kodebpr, int $periodeId): array
    {
        $out = [];
        foreach (self::SECTIONS as $i => $s) {
            $row = $this->db->table($s['table'])
                ->select('accdekom, is_approved')
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->get(1)->getRowArray();

            $s['no'] = $i + 1;
            $s['hasData'] = $row !== null;
            $s['komut'] = (int) ($row['accdekom'] ?? 0) === 1;
            $s['dirut'] = (int) ($row['is_approved'] ?? 0) === 1;
            $s['skip'] = $s['optional'] && !$s['hasData'];
            $s['done'] = $s['skip'] || $s['dirut'];
            $out[] = $s;
        }
        return $out;
    }
}
