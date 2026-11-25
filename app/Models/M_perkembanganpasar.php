<?php

namespace App\Models;

use CodeIgniter\Model;

class M_perkembanganpasar extends Model
{
    protected $table         = 'perkembanganpasar';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    // Semua kolom (termasuk kolom approval) harus ada di sini, kalau tidak save diam-diam hilang.
    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'perkembangan', 'perkembanganpdf',
        'is_approved', 'approved_by', 'approved_at',
    ];

    /** Batas panjang teks sesuai struktur tabel (dipakai untuk counter + validasi). */
    public const MAX = [
        'perkembangan' => 4000,
    ];

    /** Field teks yang wajib diisi bila TIDAK ada PDF Laporan Perkembangan yang diunggah. */
    public const REQUIRED = ['perkembangan'];

    /** Upload PDF Laporan Perkembangan. Nama file disimpan di kolom varchar(50). */
    public const PDF_MAX_KB = 5120;   // 5 MB
    public const PDF_DIR    = 'uploads/perkembanganpasar/'; // relatif terhadap WRITEPATH

    private const COLUMNS = 'id, user_id, kodebpr, periode_id, perkembangan, perkembanganpdf, '
        . 'is_approved, approved_by, approved_at';

    /**
     * Nilai default kolom NOT NULL tanpa default di tabel.
     * Approval Direktur Utama memakai is_approved / approved_by / approved_at.
     * approved_by NOT NULL -> pakai 0 (bukan null) saat belum / batal approve.
     */
    public const INSERT_DEFAULTS = [
        'perkembangan' => '',
        'perkembanganpdf' => '',
        'is_approved'     => 0,
        'approved_by'     => 0,
    ];

    /** Satu baris perkembangan & target pasar per periode + BPR. */
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
