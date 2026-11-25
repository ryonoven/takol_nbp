<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Surat Pernyataan Direksi tentang Tanggung Jawab atas Laporan Keuangan (laporan tahunan).
 *  - header  (tabel ini)                     : satu baris per BPR + periode.
 *  - detail  (suratpernyataandireksi_detail) : penandatangan / direksi, jumlah bebas, urut `urutan`.
 */
class M_suratpernyataandireksi extends Model
{
    protected $table         = 'suratpernyataandireksi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'kodebpr', 'periode_id',
        'kota', 'tanggalsurat', 'suratpdf',
        'is_approved', 'approved_by', 'approved_at',
    ];

    public const TABLE_DETAIL = 'suratpernyataandireksi_detail';

    public const DIREKSI_FIELDS = [
        'nama'           => ['Nama', 150],
        'alamatkantor'   => ['Alamat Kantor', 500],
        'alamatdomisili' => ['Alamat Domisili', 500],
        'telepon'        => ['No. Telepon', 30],
        'jabatan'        => ['Jabatan', 150],
    ];

    public const MIN_DIREKSI = 1;
    public const MAX_DIREKSI = 10;
    public const MAX_KOTA    = 100;

    /** Upload scan surat bertanda tangan + meterai (opsional). */
    public const PDF_MAX_KB = 5120;
    public const PDF_DIR    = 'uploads/suratpernyataandireksi/';

    /**
     * Isi pernyataan (tetap). {bpr} = nama BPR, {tahun} = tahun buku.
     * Ubah di sini bila redaksi surat berubah.
     */
    public const PERNYATAAN = [
        'Laporan Keuangan {bpr} telah disusun untuk tahun buku {tahun} dan disajikan sesuai dengan Standar Akuntansi Keuangan yang berlaku,',
        'Semua informasi dalam laporan keuangan {bpr} Tahun Buku {tahun} telah dimuat secara lengkap dan benar,',
        'Bertanggung jawab atas penerapan pengendalian internal dalam proses pelaporan keuangan {bpr} tahun buku {tahun} sesuai POJK mengenai integritas pelaporan keuangan Bank,',
        'Hasil Penilaian terhadap efektivitas pengendalian internal dalam proses pelaporan keuangan BPR sesuai dengan dokumen Penilaian Sendiri Pengendalian Internal dalam Pelaporan Keuangan Bank (terlampir).',
    ];

    /** Kalimat pernyataan dengan nama BPR & tahun terisi. */
    public static function pernyataan(string $namabpr, $tahun): array
    {
        return array_map(
            static fn($s) => strtr($s, ['{bpr}' => $namabpr, '{tahun}' => (string) $tahun]),
            self::PERNYATAAN
        );
    }

    public function getByPeriode(int $periodeId, string $kodebpr): ?array
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->first();
    }

    /** Daftar direksi milik surat, urut tampil. Hanya kolom DIREKSI_FIELDS. */
    public function getDireksi(?int $suratId): array
    {
        if (!$suratId) {
            return [];
        }
        return $this->db->table(self::TABLE_DETAIL)
            ->select(implode(', ', array_keys(self::DIREKSI_FIELDS)))
            ->where('surat_id', $suratId)
            ->orderBy('urutan')->orderBy('id')
            ->get()->getResultArray();
    }

    /**
     * Simpan header + ganti seluruh daftar direksi dalam satu transaksi.
     * $direksi: list of [nama, alamatkantor, alamatdomisili, telepon, jabatan].
     */
    public function saveSurat(?int $id, array $header, array $direksi): bool
    {
        $this->db->transStart();

        if ($id) {
            $this->update($id, $header);
        } else {
            $this->insert($header);
            $id = (int) $this->getInsertID();
        }

        $detail = $this->db->table(self::TABLE_DETAIL);
        $detail->where('surat_id', $id)->delete();
        $rows = [];
        foreach (array_values($direksi) as $i => $d) {
            $rows[] = ['surat_id' => $id, 'urutan' => $i + 1] + $d;
        }
        if ($rows) {
            $detail->insertBatch($rows);
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public static function resetApprovalData(): array
    {
        return ['is_approved' => 0, 'approved_by' => null, 'approved_at' => null];
    }

    /** Set / batalkan approval Direktur Utama. */
    public function setApproval(int $id, bool $approve, int $userId): bool
    {
        $data = $approve
            ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => date('Y-m-d H:i:s')]
            : self::resetApprovalData();

        return (bool) $this->update($id, $data);
    }
}
