<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Kegiatan Pengembangan (laporan tahunan).
 * Banyak baris per BPR + periode. Keterangan = footer (satu per periode, disimpan sama di semua baris).
 * Approval Direktur Utama berlaku untuk seluruh baris periode.
 */
class M_kegiatanpengembangan extends Model
{
    protected $table         = 'kegiatanpengembangan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'kegiatanpengembangan', 'tanggalpelaksana', 'kategoripeserta', 'jumlahpeserta',
        'uraiankegiatan', 'pihakpelaksana', 'keterangan',
        'is_approved', 'approved_by', 'approved_at',
    ];

    /**
     * !!! BELUM ADA SPESIFIKASI FORM — kode form, kode komponen, dan urutan kolom D01
     * (lihat Kegiatanpengembangan::exporttxt) adalah perkiraan. SESUAIKAN dengan Detail Form OJK.
     */
    public const FORM_CODE     = 'A05072';
    public const KODE_KOMPONEN = '05003';

    /** Kategori Peserta (char 2) — mengikuti kode Sosialisasi/Pelatihan; SESUAIKAN bila berbeda. */
    public const KATEGORI_PESERTA = [
        '01' => 'Seluruh Pegawai',
        '02' => 'Direksi dan/atau Dewan Komisaris',
        '03' => 'Satuan Kerja atau Pejabat Eksekutif',
        '04' => 'Komite',
    ];

    /** Pihak Pelaksana (char 2) — mengikuti kode Sosialisasi/Pelatihan; SESUAIKAN bila berbeda. */
    public const PIHAK_PELAKSANA = [
        '01' => 'Internal BPR',
        '02' => 'Eksternal BPR',
        '03' => 'Kolaborasi Dengan Lembaga Lain',
    ];

    /** Batas panjang teks per baris (detail), semua wajib. */
    public const MAX = [
        'kegiatanpengembangan' => 2000,
        'uraiankegiatan'       => 2000,
    ];

    public const MAX_PESERTA    = 99999999; // int(8)
    public const MAX_KETERANGAN = 2000;

    /** Kolom NOT NULL tanpa default yang wajib diisi saat insert. */
    private const INSERT_DEFAULTS = [
        'keterangan'  => '',
        'is_approved' => 0,
        'approved_by' => '0',
    ];

    private function scope(int $periodeId, string $kodebpr)
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr);
    }

    public function getByPeriode(int $periodeId, string $kodebpr): array
    {
        return $this->scope($periodeId, $kodebpr)->orderBy('tanggalpelaksana')->orderBy('id')->findAll();
    }

    public function findOwned(int $id, int $periodeId, string $kodebpr): ?array
    {
        return $this->scope($periodeId, $kodebpr)->where('id', $id)->first();
    }

    public function saveItem(?int $id, array $data): bool
    {
        return $id
            ? (bool) $this->update($id, $data)
            : (bool) $this->insert($data + self::INSERT_DEFAULTS);
    }

    /** Keterangan (footer) periode: dari baris pertama yang terisi. */
    public static function keteranganOf(array $rows): string
    {
        foreach ($rows as $r) {
            if (trim((string) $r['keterangan']) !== '') {
                return (string) $r['keterangan'];
            }
        }
        return '';
    }

    public function setKeterangan(int $periodeId, string $kodebpr, string $keterangan): bool
    {
        return (bool) $this->builder()
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->update(['keterangan' => $keterangan]);
    }

    /** Disetujui bila ada data dan semua baris is_approved = 1. */
    public static function approvalOf(array $rows): array
    {
        $approved = $rows !== [] && !in_array(0, array_map(static fn($r) => (int) $r['is_approved'], $rows), true);
        return [
            'approved' => $approved,
            'by'       => $approved ? (int) $rows[0]['approved_by'] : 0,
            'at'       => $approved ? $rows[0]['approved_at'] : null,
        ];
    }

    /** Set / batalkan approval seluruh baris periode (approved_by varchar NOT NULL). */
    public function setApproval(int $periodeId, string $kodebpr, bool $approve, int $userId): bool
    {
        $data = $approve
            ? ['is_approved' => 1, 'approved_by' => (string) $userId, 'approved_at' => date('Y-m-d H:i:s')]
            : ['is_approved' => 0, 'approved_by' => '0'];

        return (bool) $this->builder()
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->update($data);
    }
}
