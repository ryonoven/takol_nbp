<?php

namespace App\Models;

use CodeIgniter\Model;

class M_kerjasamabpr extends Model
{
    protected $table         = 'kerjasamabpr';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'reflembaga', 'namabank', 'sandibank', 'npwp',
        'jeniskerjasama', 'uraiankerjasama', 'tglkerjasama', 'keterangan',
        'is_approved', 'approved_by', 'approved_at',
    ];

    public const FORM_CODE     = 'A0506';
    public const KODE_KOMPONEN = '05002';
    public const REF_BANK    = '1';
    public const REF_LAINNYA = '2';
    public const REFLEMBAGA = [
        self::REF_BANK    => 'Bank',
        self::REF_LAINNYA => 'Lembaga Lain',
    ];

    public const MAX = [
        'namabank'        => 2000,
        'sandibank'       => 50,
        'npwp'            => 25,
        'jeniskerjasama'  => 2000,
        'uraiankerjasama' => 2000,
    ];

    /** Wajib di semua baris (sandibank / npwp kondisional terhadap reflembaga). */
    public const REQUIRED = ['namabank', 'jeniskerjasama', 'uraiankerjasama'];

    public const MAX_KETERANGAN = 2000;

    /** Kolom NOT NULL tanpa default yang wajib diisi saat insert. */
    private const INSERT_DEFAULTS = [
        'sandibank'   => '',
        'npwp'        => '',
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
        return $this->scope($periodeId, $kodebpr)->orderBy('tglkerjasama')->orderBy('id')->findAll();
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
            'by' => $approved ? (int) $rows[0]['approved_by'] : 0,
            'at' => $approved ? $rows[0]['approved_at'] : null,
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
