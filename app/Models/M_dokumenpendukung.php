<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Dokumen Pendukung laporan tahunan.
 *  1. Daftar Direksi & Dewan Komisaris (+ tempat/tanggal) -> dasar Lembar Pernyataan.
 *  2. Surat Pernyataan Direksi (bertanda tangan)            -> upload PDF
 *  3. Lembar Pernyataan Direksi & Dewan Komisaris (TTD)     -> upload PDF (template di-generate dari bagian 1)
 *  4. Laporan Pengendalian Internal Pelaporan Keuangan      -> upload PDF
 * Bila ketiga PDF ada, digabung menjadi satu PDF (form LTBPRK F0000) untuk ZIP laporan tahunan.
 */
class M_dokumenpendukung extends Model
{
    protected $table = 'dokumenpendukung';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id', 'kota', 'tanggalsurat',
        'suratdireksi_pdf', 'lembar_pdf', 'pengendalian_pdf',
    ];

    public const TABLE_ANGGOTA = 'dokumenpendukung_anggota';

    /** Jenis anggota => [judul daftar, contoh jabatan]. */
    public const JENIS = [
        'direksi' => ['Direksi', 'Direktur Utama'],
        'dekom' => ['Dewan Komisaris', 'Komisaris Utama'],
    ];
    public const MIN_ANGGOTA = 1;
    public const MAX_ANGGOTA = 10;
    public const MAX_NAMA = 150;
    public const MAX_KOTA = 100;

    /** Slot upload (urutan = urutan penggabungan) => [kolom, prefix nama file, judul singkat]. */
    public const SLOTS = [
        'suratdireksi' => ['suratdireksi_pdf', 'sd', 'Surat Pernyataan Direksi'],
        'lembar' => ['lembar_pdf', 'lp', 'Lembar Pernyataan Direksi & Dewan Komisaris'],
        'pengendalian' => ['pengendalian_pdf', 'pi', 'Laporan Pengendalian Internal'],
    ];
    public const PDF_MAX_KB = 10240;
    public const PDF_DIR = 'uploads/dokumenpendukung/';
    public const FORM_CODE = 'F0000';

    public function getByPeriode(int $periodeId, string $kodebpr): ?array
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->first();
    }

    /** Header milik BPR + periode; dibuat (kosong) bila belum ada. */
    public function getOrCreate(int $periodeId, string $kodebpr, int $userId): array
    {
        $row = $this->getByPeriode($periodeId, $kodebpr);
        if ($row) {
            return $row;
        }
        $this->db->table($this->table)->ignore(true)->insert([
            'user_id' => $userId, 'kodebpr' => $kodebpr, 'periode_id' => $periodeId,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->getByPeriode($periodeId, $kodebpr);
    }

    /** @return array{direksi: list<array>, dekom: list<array>} */
    public function getAnggota(?int $dokId): array
    {
        $out = ['direksi' => [], 'dekom' => []];
        if (!$dokId) {
            return $out;
        }
        $rows = $this->db->table(self::TABLE_ANGGOTA)->select('jenis, nama, jabatan')
            ->where('dok_id', $dokId)->orderBy('jenis')->orderBy('urutan')->orderBy('id')
            ->get()->getResultArray();
        foreach ($rows as $r) {
            $out[$r['jenis']][] = ['nama' => $r['nama'], 'jabatan' => $r['jabatan']];
        }
        return $out;
    }

    /** Simpan header (tempat/tanggal) + ganti seluruh daftar anggota dalam satu transaksi. */
    public function saveAnggota(int $dokId, array $header, array $anggota): bool
    {
        $this->db->transStart();
        $this->update($dokId, $header);
        $t = $this->db->table(self::TABLE_ANGGOTA);
        $t->where('dok_id', $dokId)->delete();
        $rows = [];
        foreach (array_keys(self::JENIS) as $jenis) {
            foreach (array_values($anggota[$jenis] ?? []) as $i => $a) {
                $rows[] = ['dok_id' => $dokId, 'jenis' => $jenis, 'urutan' => $i + 1, 'nama' => $a['nama'], 'jabatan' => $a['jabatan']];
            }
        }
        if ($rows) {
            $t->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /** Lengkap = ketiga PDF sudah diunggah. */
    public static function isComplete(?array $row): bool
    {
        if (!$row) {
            return false;
        }
        foreach (self::SLOTS as [$col]) {
            if (empty($row[$col])) {
                return false;
            }
        }
        return true;
    }

    /** Status kelengkapan untuk kartu Showtahunan. */
    public function isCompleteFor(string $kodebpr, int $periodeId): bool
    {
        return self::isComplete($this->getByPeriode($periodeId, $kodebpr));
    }
}
