<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Bidang Usaha (laporan tahunan): banyak baris (produk) per BPR + periode.
 * Approval Direktur Utama berlaku untuk seluruh baris dalam satu periode
 * (is_approved / approved_by / approved_at disamakan di semua baris).
 */
class M_bidusahathn extends Model
{
    protected $table         = 'bidusahathn';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'kategoriusaha', 'jenisproduk', 'namaproduk', 'uraian', 'keterangan',
        'is_approved', 'approved_by', 'approved_at',
    ];

    /**
     * Form A05.02 Bidang Usaha (LTBPRK).
     * Detail D01|05001|kategori|jenis|namaproduk|uraian, Footer F01|keterangan.
     */
    public const FORM_CODE      = 'A0502';
    public const KODE_KOMPONEN  = '05001';

    /** Batas panjang teks per baris (detail). Keduanya Mandatory. */
    public const MAX = [
        'namaproduk' => 2000,
        'uraian'     => 2000,
    ];

    /** Field teks wajib per baris. */
    public const REQUIRED = ['namaproduk', 'uraian'];

    /** Keterangan = footer laporan (satu per periode, Optional), disimpan sama di semua baris. */
    public const MAX_KETERANGAN = 2000;

    /**
     * Kategori Kegiatan Usaha (char 2, Mandatory, referensi).
     * !!! Daftar kode belum tercantum di spesifikasi form — SESUAIKAN dengan referensi OJK.
     */
    public const KATEGORI = [
        '01' => 'Penghimpunan Dana',
        '02' => 'Penyaluran Dana',
        '03' => 'Kegiatan Usaha Lainnya',
    ];

    /** Jenis Produk (char 2, Mandatory) sesuai Detail Form A05.02. */
    public const JENIS = [
        '01' => 'Produk dasar',
        '02' => 'Produk dasar lainnya',
        '03' => 'Produk lanjutan berbasis teknologi informasi',
        '04' => 'Produk lanjutan berkaitan dengan penyelenggaraan kegiatan atau produk lembaga jasa keuangan nonbank atau mempengaruhi penilaian profil risiko',
        '05' => 'Produk lanjutan memerlukan izin dan/atau persetujuan dari otoritas lain',
        '06' => 'Kegiatan Laku Pandai',
    ];

    /** Kolom NOT NULL tanpa default yang wajib diisi saat insert. */
    private const INSERT_DEFAULTS = [
        'keterangan'  => '',
        'is_approved' => 0,
        'approved_by' => 0,
    ];

    private function scope(int $periodeId, string $kodebpr)
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr);
    }

    /** Semua baris milik BPR + periode. */
    public function getByPeriode(int $periodeId, string $kodebpr): array
    {
        return $this->scope($periodeId, $kodebpr)
            ->orderBy('kategoriusaha')->orderBy('jenisproduk')->orderBy('id')
            ->findAll();
    }

    /** Satu baris, hanya bila milik BPR + periode tersebut. */
    public function findOwned(int $id, int $periodeId, string $kodebpr): ?array
    {
        return $this->scope($periodeId, $kodebpr)->where('id', $id)->first();
    }

    /** Insert / update satu baris. */
    public function saveItem(?int $id, array $data): bool
    {
        return $id
            ? (bool) $this->update($id, $data)
            : (bool) $this->insert($data + self::INSERT_DEFAULTS);
    }

    /** Keterangan (footer) periode: diambil dari baris pertama yang terisi. */
    public static function keteranganOf(array $rows): string
    {
        foreach ($rows as $r) {
            if (trim((string) $r['keterangan']) !== '') {
                return (string) $r['keterangan'];
            }
        }
        return '';
    }

    /** Simpan keterangan (footer) ke semua baris periode. */
    public function setKeterangan(int $periodeId, string $kodebpr, string $keterangan): bool
    {
        return (bool) $this->builder()
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->update(['keterangan' => $keterangan]);
    }

    /** Status approval periode: disetujui bila ada data dan semua baris is_approved = 1. */
    public static function approvalOf(array $rows): array
    {
        $approved = $rows !== [] && !in_array(0, array_map(static fn($r) => (int) $r['is_approved'], $rows), true);
        return [
            'approved' => $approved,
            'by'       => $approved ? (int) $rows[0]['approved_by'] : 0,
            'at'       => $approved ? $rows[0]['approved_at'] : null,
        ];
    }

    /** Set / batalkan approval untuk seluruh baris periode (approved_by / approved_at NOT NULL). */
    public function setApproval(int $periodeId, string $kodebpr, bool $approve, int $userId): bool
    {
        $data = $approve
            ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => date('Y-m-d H:i:s')]
            : ['is_approved' => 0, 'approved_by' => 0];

        return (bool) $this->builder()
            ->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->update($data);
    }
}
