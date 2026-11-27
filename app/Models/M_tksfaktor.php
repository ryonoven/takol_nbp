<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Basis model faktor TKS dengan pola kolom yang sama (Profil Risiko, Tata Kelola):
 * nilaiperingkat/peringkat/nilaifaktor (+ *before), deskripsi, analisis, persetujuan & flag sinkron.
 * Satu baris per BPR + periode semesteran.
 *
 * Kolom yang tidak ada di tabel turunan otomatis dibuang oleh $allowedFields CodeIgniter.
 * is_approved dikirim sebagai string '0'/'1' agar aman untuk kolom TINYINT maupun ENUM('1','0').
 */
abstract class M_tksfaktor extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    /** Bobot faktor terhadap nilai komposit TKS. */
    public const BOBOT = 0.25;
    /** Label peringkat 1–5. */
    public const LABELS = [];
    /** Nama kolom flag sinkron (1 = data diambil dari laporan sumber). */
    public const SYNC_FLAG = 'synced_from_pr';

    public function calculateNilaiFaktor($nilaiperingkat): float
    {
        return round((float) $nilaiperingkat * static::BOBOT, 2);
    }

    public function getPeringkatLabel($nilai): string
    {
        return static::LABELS[(int) $nilai] ?? '';
    }

    public function getByKodebprAndPeriode($kodebpr, $periodeId): ?array
    {
        return $this->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();
    }

    public function isSynced($kodebpr, $periodeId): bool
    {
        $row = $this->getByKodebprAndPeriode($kodebpr, $periodeId);
        return $row && (int) ($row[static::SYNC_FLAG] ?? 0) === 1;
    }

    /** Kolom turunan dari peringkat (saat ini & sebelumnya). 0 = belum diisi. */
    public function nilaiFields(int $pk, int $pkBefore): array
    {
        return [
            'nilaiperingkat' => $pk,
            'ratarata' => $pk,
            'peringkat' => $this->getPeringkatLabel($pk),
            'nilaifaktor' => $this->calculateNilaiFaktor($pk),
            'nilaiperingkatbefore' => $pkBefore,
            'rataratabefore' => $pkBefore,
            'peringkatbefore' => $this->getPeringkatLabel($pkBefore),
            'nilaifaktorbefore' => $this->calculateNilaiFaktor($pkBefore),
        ];
    }

    /** Nilai awal semua kolom NOT NULL tanpa default (MySQL strict). */
    public function insertDefaults(): array
    {
        $now = date('Y-m-d H:i:s');
        return $this->nilaiFields(0, 0) + [
            'user_id' => 0, 'fullname' => '', 'deskripsi' => '', 'analisis' => '',
            'is_approved' => '0', 'approved_by' => 0, 'accdir2' => '0', 'accdir2_by' => 0,
            static::SYNC_FLAG => 0, 'created_at' => $now, 'updated_at' => $now,
        ];
    }
}
