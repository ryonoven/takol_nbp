<?php

namespace App\Models;

use CodeIgniter\Model;

class M_usahasignifikan extends Model
{
    protected $table         = 'usahasignifikan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    // Semua kolom (termasuk kolom approval) harus ada di sini, kalau tidak save diam-diam hilang.
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'signifikan', 'lain', 'perkembanganpdf',
        'is_approved', 'approved_by', 'approved_at',
        'accdir2', 'accdir2_by', 'accdir2_at',
    ];

    /** Batas panjang teks sesuai struktur tabel (dipakai untuk counter + validasi). */
    public const MAX = [
        'signifikan' => 4000,
        'lain'       => 4000,
    ];

    /** Field teks yang wajib diisi bila TIDAK ada PDF Laporan Perkembangan yang diunggah. */
    public const REQUIRED = ['signifikan', 'lain'];

    /** Upload PDF Laporan Perkembangan. Nama file disimpan di kolom varchar(30). */
    public const PDF_MAX_KB = 5120;   // 5 MB
    public const PDF_DIR    = 'uploads/usahasignifikan/'; // relatif terhadap WRITEPATH

    private const COLUMNS = 'id, user_id, kodebpr, periode_id, signifikan, lain, perkembanganpdf, '
        . 'is_approved, approved_by, approved_at';

    public const INSERT_DEFAULTS = [
        'signifikan'      => '',
        'lain'            => '',
        'perkembanganpdf' => '',
        'is_approved'     => 0,
        'approved_by'     => 0,
        'accdir2'         => 0,
        'accdir2_by'      => 0,
    ];

    /** Satu baris usaha signifikan per periode + BPR. */
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
