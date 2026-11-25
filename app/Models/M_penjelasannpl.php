<?php

namespace App\Models;

use CodeIgniter\Model;

class M_penjelasannpl extends Model
{
    protected $table         = 'penjelasannpl';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    // Semua kolom (termasuk kolom approval) harus ada di sini, kalau tidak save diam-diam hilang.
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'nplgross', 'nplnetto',
        'uraian', 'keterangan',
        'is_approved', 'approved_by', 'approved_at',
        'accdir2', 'accdir2_by', 'accdir2_at',
    ];

    /** Batas panjang teks sesuai struktur tabel (dipakai untuk counter + validasi). */
    public const MAX = [
        'uraian'     => 2000,
        'keterangan' => 2000,
    ];

    /** Field angka (persen): nama => label. */
    public const NUMERIC_FIELDS = [
        'nplgross' => 'NPL Gross',
        'nplnetto' => 'NPL Netto',
    ];

    /** Kode form LTBPRK untuk export TXT. */
    public const FORM_CODE = 'A0304';

    /** Rentang nilai NPL (%). Ubah di sini bila aturan berbeda. */
    public const NPL_MIN = 0;
    public const NPL_MAX = 100;

    private const COLUMNS = 'id, user_id, kodebpr, periode_id, nplgross, nplnetto, uraian, keterangan, '
        . 'is_approved, approved_by, approved_at, accdir2, accdir2_by, accdir2_at';

    /** Satu baris penjelasan NPL per periode + BPR. */
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

    /**
     * Set / batalkan approval.
     * $type: 'dirut' (is_approved/approved_by/approved_at) atau 'dir2' (accdir2/...).
     */
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