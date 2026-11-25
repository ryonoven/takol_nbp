<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ringkasan halaman Pelaporan Tahunan.
 * Daftar bagian laporan + status approval Direktur Utama dari tabel masing-masing.
 */
class M_showtahunan extends Model
{
    /**
     * Bagian laporan tahunan (urutan = nomor romawi di halaman).
     *  - link  : controller tujuan; null = halaman belum tersedia.
     *  - table : tabel sumber status approval (kolom is_approved, kodebpr, periode_id).
     *  - where : filter tambahan (opsional).
     *  - via   : 'transparansi' = status diambil dari ringkasan Laporan Transparansi
     *            (disetujui bila seluruh 18 bagian wajib sudah disetujui Direktur Utama);
     *            'dokumenpendukung' = selesai bila ketiga PDF bertanda tangan sudah diunggah (tanpa approval).
     *  - statusLabel : teks chip status (default "Disetujui oleh: Direktur Utama").
     * Bagian tanpa 'table' tidak dihitung dalam syarat export ZIP.
     */
    public const SECTIONS = [
        ['name' => 'Riwayat Pendirian BPR', 'link' => 'Riwayatbpr', 'table' => 'riwayatbpr'],
        ['name' => 'Penjelasan NPL', 'link' => 'Penjelasannpl', 'table' => 'penjelasannpl'],
        ['name' => 'Perkembangan Usaha yang Berpengaruh secara Signifikan dan Perubahan Penting Lain', 'link' => 'Usahasignifikan', 'table' => 'usahasignifikan'],
        ['name' => 'Strategi dan Kebijakan Manajemen', 'link' => 'Kebijaksanaanmanathn', 'table' => 'kebijaksanaanmanathn'],
        ['name' => 'Bidang Usaha', 'link' => 'Bidusahathn', 'table' => 'bidusahathn'],
        ['name' => 'Teknologi Informasi', 'link' => 'Titahunan', 'table' => 'titahunan'],
        ['name' => 'Perkembangan dan Target Pasar', 'link' => 'Perkembanganpasar', 'table' => 'perkembanganpasar'],
        ['name' => 'Kerja Sama BPR dengan Bank atau Lembaga Lain', 'link' => 'Kerjasamabpr', 'table' => 'kerjasamabpr'],
        ['name' => 'Kegiatan Pengembangan', 'link' => 'Kegiatanpengembangan', 'table' => 'kegiatanpengembangan'],
        ['name' => 'Laporan Akuntan Publik', 'link' => 'Laporanakuntan', 'table' => 'laporanakuntan'],
        ['name' => 'Surat Pernyataan Direksi', 'link' => 'Suratpernyataandireksi', 'table' => 'suratpernyataandireksi'],
        ['name' => 'Dokumen Pendukung', 'link' => 'Dokumenpendukung', 'table' => 'dokumenpendukung', 'via' => 'dokumenpendukung',
            'statusLabel' => 'Lengkap: 3 dokumen bertanda tangan diunggah'],
        ['name' => 'Transparansi Pelaksanaan Tata Kelola', 'link' => 'Showtransparansi', 'table' => 'showtransparansi', 'via' => 'transparansi'],
    ];

    /** Daftar bagian lengkap dengan status: available (bool) & approved (bool). */
    public function getSections(string $kodebpr, int $periodeId): array
    {
        $out = [];
        foreach (self::SECTIONS as $s) {
            $s['available'] = $s['link'] !== null;
            if (($s['via'] ?? null) === 'transparansi') {
                $s['approved'] = $this->isTransparansiApproved($kodebpr, $periodeId);
            } elseif (($s['via'] ?? null) === 'dokumenpendukung') {
                // Tanpa approval: selesai bila ketiga PDF bertanda tangan sudah diunggah
                $s['approved'] = (new M_dokumenpendukung())->isCompleteFor($kodebpr, $periodeId);
            } else {
                $s['approved'] = $s['table'] !== null && $this->isApproved($s['table'], $kodebpr, $periodeId, $s['where'] ?? []);
            }
            $out[] = $s;
        }
        return $out;
    }

    /** Sama dengan syarat export ZIP di halaman Laporan Transparansi: semua bagian 'done'. */
    private function isTransparansiApproved(string $kodebpr, int $periodeId): bool
    {
        $sections = (new M_showtransparansi())->getSections($kodebpr, $periodeId);
        return !array_filter($sections, static fn($s) => !$s['done']);
    }

    /** is_approved = 1 pada baris terbaru milik BPR + periode. */
    private function isApproved(string $table, string $kodebpr, int $periodeId, array $where): bool
    {
        $row = $this->db->table($table)
            ->select('is_approved')
            ->where('kodebpr', $kodebpr)
            ->where('periode_id', $periodeId)
            ->where($where)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return (int) ($row['is_approved'] ?? 0) === 1;
    }
}
