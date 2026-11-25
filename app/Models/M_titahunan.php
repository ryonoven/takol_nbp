<?php

namespace App\Models;

use CodeIgniter\Model;

class M_titahunan extends Model
{
    protected $table         = 'titahunan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    // Semua kolom (termasuk kolom approval) harus ada di sini, kalau tidak save diam-diam hilang.
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'sisop', 'siskeamanan', 'penjelasantipdf',
        'is_approved', 'approved_by', 'approved_at',
    ];

    /** Batas panjang teks sesuai struktur tabel (dipakai untuk counter + validasi). */
    public const MAX = [
        'sisop' => 4000,
        'siskeamanan' => 4000,
    ];

    /** Field teks yang wajib diisi bila TIDAK ada PDF Penjelasan yang diunggah. */
    public const REQUIRED = ['sisop', 'siskeamanan'];

    /** Upload PDF Penjelasan. Nama file disimpan di kolom varchar(50). */
    public const PDF_MAX_KB = 5120;   // 5 MB
    public const PDF_DIR    = 'uploads/titahunan/'; // relatif terhadap WRITEPATH

    private const COLUMNS = 'id, user_id, kodebpr, periode_id, sisop, siskeamanan, penjelasantipdf, '
        . 'is_approved, approved_by, approved_at';

    /**
     * Nilai default kolom NOT NULL tanpa default di tabel.
     * Approval Direktur Utama memakai is_approved / approved_by / approved_at.
     * approved_by NOT NULL -> pakai 0 (bukan null) saat belum / batal approve.
     */
    public const INSERT_DEFAULTS = [
        'sisop' => '',
        'siskeamanan' => '',
        'penjelasantipdf'   => '',
        'is_approved'     => 0,
        'approved_by'     => 0,
    ];

    /** Satu baris teknologi informasi per periode + BPR. */
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

        return (bool) $this->insert($data + self::INSERT_DEFAULTS);
    }

    /** Data untuk me-reset approval Direktur Utama (approved_by / approved_at NOT NULL, jadi tidak di-null-kan). */
    public static function resetApprovalData(): array
    {
        return ['is_approved' => 0, 'approved_by' => 0];
    }

    /** Set / batalkan approval Direktur Utama (is_approved / approved_by = user_id / approved_at). */
    public function setApproval(int $id, bool $approve, int $userId): bool
    {
        $data = $approve
            ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => date('Y-m-d H:i:s')]
            : self::resetApprovalData();

        return (bool) $this->update($id, $data);
    }
}
