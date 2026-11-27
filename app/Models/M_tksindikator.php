<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Basis model faktor TKS berbasis indikator rasio (Rentabilitas, Permodalan). Satu baris per BPR + periode.
 *
 * Setiap indikator dihitung dari nominal input untuk periode saat ini & periode sebelumnya. Semua rasio,
 * peringkat indikator, peringkat faktor (pembulatan rata-rata peringkat indikator) dan nilai faktor
 * dihitung di compute() — satu sumber rumus (tampilan memakai rumus yang sama lewat konfigurasi JSON).
 */
abstract class M_tksindikator extends Model
{
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public const BOBOT = 0.15;
    /** Label peringkat 1–5 (disimpan di kolom penilaian indikator). */
    public const LABELS = [];

    /**
     * Definisi indikator.
     *  inputs : [label, kolom saat ini, kolom sebelumnya]
     *  calc   : 'ratio' = a ÷ b · 'sum' = (a + b) ÷ c · 'net' = (a − b) ÷ c  (× 100%)
     *  den    : 'nonzero' | 'positive' — syarat penyebut agar rasio dihitung
     *  dir    : 'high' = makin besar makin baik (rasio >= batas), 'low' = makin kecil makin baik (rasio <= batas)
     *  limits : batas peringkat 1..4 (selain itu peringkat 5)
     *  ratio/rank/label/net : [kolom saat ini, kolom sebelumnya]
     */
    public const INDIKATOR = [];

    /** Kolom faktor: [rata-rata, penilaian, peringkat, nilai faktor] untuk saat ini & sebelumnya. */
    public const FAKTOR_COLS = [];

    /** Kolom teks: deskripsi & analisa. */
    public const TEXT_COLS = [];

    /** Default tambahan untuk kolom NOT NULL tanpa default. */
    protected const EXTRA_DEFAULTS = [];

    public static function inputCols(): array
    {
        $out = [];
        foreach (static::INDIKATOR as $ind) {
            foreach ($ind['inputs'] as [, $cur, $bef]) {
                $out[] = $cur;
                $out[] = $bef;
            }
        }
        return $out;
    }

    public static function rankOf(string $key, float $ratio): int
    {
        $ind = static::INDIKATOR[$key];
        foreach ($ind['limits'] as $i => $lim) {
            if ($ind['dir'] === 'high' ? $ratio >= $lim : $ratio <= $lim) {
                return $i + 1;
            }
        }
        return 5;
    }

    public function getByKodebprAndPeriode($kodebpr, $periodeId): ?array
    {
        return $this->where(['kodebpr' => $kodebpr, 'periode_id' => $periodeId])->first();
    }

    /**
     * Hitung seluruh kolom turunan dari nominal input.
     * @param array<string,int> $in kolom input => nominal
     */
    public static function compute(array $in): array
    {
        $out = [];
        foreach ([0, 1] as $p) {
            $ranks = [];
            foreach (static::INDIKATOR as $key => $ind) {
                $v = array_map(static fn($f) => (float) ($in[$f[$p + 1]] ?? 0), $ind['inputs']);
                switch ($ind['calc']) {
                    case 'sum':
                        [$num, $den] = [$v[0] + $v[1], $v[2]];
                        break;
                    case 'net':
                        [$num, $den] = [$v[0] - $v[1], $v[2]];
                        break;
                    default:
                        [$num, $den] = [$v[0], $v[1]];
                }
                if (isset($ind['net'])) {
                    $out[$ind['net'][$p]] = (int) round($num);
                }
                $ok = ($ind['den'] ?? 'nonzero') === 'positive' ? $den > 0 : $den != 0.0;
                if ($ok) {
                    $ratio = round($num / $den * 100, 2);
                    $rank = static::rankOf($key, $ratio);
                    $out[$ind['ratio'][$p]] = $ratio;
                    $out[$ind['rank'][$p]] = (string) $rank;
                    $out[$ind['label'][$p]] = static::LABELS[$rank];
                    $ranks[] = $rank;
                } else {
                    $out[$ind['ratio'][$p]] = 0;
                    $out[$ind['rank'][$p]] = '';
                    $out[$ind['label'][$p]] = '';
                }
            }
            [$cAvg, $cPen, $cPk, $cNf] = static::FAKTOR_COLS[$p];
            if ($ranks && count($ranks) === count(static::INDIKATOR)) {
                $avg = array_sum($ranks) / count($ranks);
                $pk = (int) round($avg);
                $out += [$cAvg => round($avg, 2), $cPen => $pk, $cPk => (string) $pk, $cNf => round($pk * static::BOBOT, 2)];
            } else {
                $out += [$cAvg => 0, $cPen => 0, $cPk => '', $cNf => 0];
            }
        }
        return $out;
    }

    /** Nilai awal semua kolom NOT NULL tanpa default (MySQL strict). */
    public static function insertDefaults(): array
    {
        $now = date('Y-m-d H:i:s');
        return array_fill_keys(static::inputCols(), 0) + static::compute([]) + array_fill_keys(static::TEXT_COLS, '')
            + static::EXTRA_DEFAULTS + ['user_id' => 0, 'approved_by' => 0, 'created_at' => $now, 'updated_at' => $now];
    }
}
