<?php

namespace App\Models;

use CodeIgniter\Model;

class M_riwayatbpr extends Model
{
    protected $table         = 'riwayatbpr';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'nomorakta', 'tglakta',
        'noanggaran', 'tglanggaran',
        'nopengesahan', 'tglpengesahan',
        'tgloperasi',
        'bidangusaha', 'tempatkedudukan',
        'opinipublik', 'namaakuntan', 'keterangan',
        'is_approved', 'approved_by', 'approved_at',
        'accdir2', 'accdir2_by', 'accdir2_at',
    ];

    /** Batas panjang sesuai struktur tabel (dipakai untuk counter + validasi). */
    public const MAX = [
        'nomorakta'       => 50,
        'noanggaran'      => 50,
        'nopengesahan'    => 50,
        'bidangusaha'     => 2000,
        'tempatkedudukan' => 2000,
        'namaakuntan'     => 2000,
        'keterangan'      => 2000,
    ];

    public const DATE_FIELDS = ['tglakta', 'tglanggaran', 'tglpengesahan', 'tgloperasi'];

    /**
     * Export TXT LTBPRK.
     * !!! BELUM ADA SPESIFIKASI FORM — kode form & urutan kolom D01 perlu dikonfirmasi.
     */
    public const FORM_CODE = 'A0301';

    /** Urutan kolom D01 (keterangan masuk ke baris F01). */
    public const TXT_FIELDS = [
        'nomorakta', 'tglakta',
        'noanggaran', 'tglanggaran',
        'nopengesahan', 'tglpengesahan',
        'tgloperasi',
        'bidangusaha', 'tempatkedudukan',
        'opinipublik', 'namaakuntan',
    ];

    /** Opini akuntan publik: nilai => label */
    public const OPINI = [
        '1' => 'Wajar tanpa pengecualian',
        '2' => 'Wajar dengan pengecualian',
        '3' => 'Wajar tanpa pengecualian dengan paragraf penjelasan',
        '4' => 'Tidak wajar',
        '5' => 'Tidak menyatakan pendapat',
    ];

    private const COLUMNS = 'id, user_id, kodebpr, periode_id, nomorakta, noanggaran, tglanggaran, '
        . 'nopengesahan, tglpengesahan, tgloperasi, bidangusaha, tempatkedudukan, opinipublik, '
        . 'namaakuntan, keterangan, tglakta, is_approved, approved_by, approved_at, '
        . 'accdir2, accdir2_by, accdir2_at';

    /** Satu baris riwayat per periode + BPR. */
    public function getByPeriode(int $periodeId, string $kodebpr): ?array
    {
        return $this->select(self::COLUMNS)
            ->where('periode_id', $periodeId)
            ->where('kodebpr', $kodebpr)
            ->first();
    }

    /** Insert jika belum ada, update jika sudah ada. Mengembalikan true bila berhasil. */
    public function saveRow(?int $id, array $data): bool
    {
        if ($id) {
            return (bool) $this->update($id, $data);
        }

        return (bool) $this->insert($data);
    }

    public function setApproval(int $id, string $type, bool $approve, int $userId): bool
    {
        $map = $type === 'dirut'
            ? ['is_approved', 'approved_by', 'approved_at']
            : ['accdir2', 'accdir2_by', 'accdir2_at'];

        $data = $approve
            ? [$map[0] => 1, $map[1] => $userId, $map[2] => date('Y-m-d H:i:s')]
            : [$map[0] => 0, $map[1] => null,    $map[2] => null];

        return (bool) $this->update($id, $data);
    }
}