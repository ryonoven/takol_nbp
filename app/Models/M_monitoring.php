<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Monitoring pelaporan seluruh BPR (menu admin).
 * Status dihitung massal per tabel (satu query per bagian untuk semua BPR), bukan per BPR.
 *
 * Status sel:
 *   none  = belum diisi
 *   draft = sudah diisi, belum disetujui
 *   komut = disetujui Komisaris Utama, menunggu Direktur Utama (khusus Transparansi)
 *   ok    = disetujui / lengkap
 *   skip  = bagian opsional tanpa data (dianggap selesai)
 */
class M_monitoring extends Model
{
    protected $table = 'periode_tahunan';

    /** Tahun yang memiliki periode tahunan (terbaru dulu). */
    public function tahunList(): array
    {
        return array_map('intval', array_column(
            $this->db->table('periode_tahunan')->select('tahun')->distinct()->where('tahun IS NOT NULL')->orderBy('tahun', 'DESC')->get()->getResultArray(),
            'tahun'
        ));
    }

    /**
     * Rekap Laporan Tahunan seluruh BPR untuk satu tahun.
     * @return array{sections: array, bprs: array, summary: array}
     */
    public function tahunan(int $tahun): array
    {
        $bprs = $this->db->table('infobpr')->select('kodebpr, namabpr, sandibpr')->orderBy('kodebpr')->get()->getResultArray();

        // Periode terbaru (Koreksi menggantikan Rutin) per BPR untuk tahun tsb
        $periodes = [];
        foreach ($this->db->table('periode_tahunan')->select('id, kodebpr, kategori, jenispelaporan, updated_at')
            ->where('tahun', $tahun)->orderBy('id', 'ASC')->get()->getResultArray() as $p) {
            $periodes[$p['kodebpr']] = $p;
        }
        $pids = array_map('intval', array_column($periodes, 'id'));

        $sections = array_values(array_filter(M_showtahunan::SECTIONS, static fn($s) => !empty($s['link'])));
        $transSections = M_showtransparansi::SECTIONS;

        // Status bagian laporan tahunan (kecuali via khusus) — satu query per tabel
        $annual = [];
        foreach ($sections as $s) {
            if (!empty($s['via'])) {
                continue;
            }
            $annual[$s['link']] = $this->aggregate($s['table'], $pids);
        }
        $trans = [];
        foreach ($transSections as $t) {
            $trans[$t['controller']] = $this->aggregate($t['table'], $pids);
        }
        $dokumen = $this->dokumenStatus($pids);

        $out = [];
        $sum = ['total' => count($bprs), 'withPeriode' => 0, 'ready' => 0, 'progress' => 0, 'started' => 0];
        foreach ($bprs as $b) {
            $p = $periodes[$b['kodebpr']] ?? null;
            $row = [
                'kodebpr' => $b['kodebpr'],
                'nama' => trim((string) $b['namabpr']) !== '' ? $b['namabpr'] : '(nama belum diisi)',
                'sandi' => (string) $b['sandibpr'],
                'periode' => $p ? ['id' => (int) $p['id'], 'kategori' => $p['kategori'], 'updated' => $p['updated_at']] : null,
                'cells' => [],
                'trans' => [],
                'done' => 0,
                'total' => count($sections),
            ];
            if ($p) {
                $pid = (int) $p['id'];
                // Transparansi: 18 bagian
                $tDone = 0;
                $tAny = false;
                $tKomut = 0;
                foreach ($transSections as $t) {
                    $a = $trans[$t['controller']][$pid] ?? null;
                    if ($a === null) {
                        $st = $t['optional'] ? 'skip' : 'none';
                    } else {
                        $tAny = true;
                        $st = $a['dirut'] ? 'ok' : ($a['komut'] ? 'komut' : 'draft');
                    }
                    $tKomut += $st === 'komut' ? 1 : 0;
                    $tDone += in_array($st, ['ok', 'skip'], true) ? 1 : 0;
                    $row['trans'][] = $st;
                }
                foreach ($sections as $s) {
                    if (($s['via'] ?? '') === 'transparansi') {
                        $st = $tDone === count($transSections) ? 'ok' : (!$tAny ? 'none' : ($tKomut ? 'komut' : 'draft'));
                        // Hitungan hanya ditampilkan bila sudah ada isian (bagian opsional kosong dihitung selesai)
                        $row['cells'][] = ['st' => $st, 'note' => $tAny ? $tDone . '/' . count($transSections) : ''];
                    } elseif (($s['via'] ?? '') === 'dokumenpendukung') {
                        $d = $dokumen[$pid] ?? null;
                        $st = $d === null ? 'none' : ($d['uploaded'] === $d['slots'] ? 'ok' : 'draft');
                        $row['cells'][] = ['st' => $st, 'note' => $d ? $d['uploaded'] . '/' . $d['slots'] : ''];
                    } else {
                        $a = $annual[$s['link']][$pid] ?? null;
                        $row['cells'][] = ['st' => $a === null ? 'none' : ($a['dirut'] ? 'ok' : 'draft'), 'note' => ''];
                    }
                }
                $row['done'] = count(array_filter($row['cells'], static fn($c) => $c['st'] === 'ok'));
                $sum['withPeriode']++;
                $sum['started'] += count(array_filter($row['cells'], static fn($c) => $c['st'] !== 'none')) > 0 ? 1 : 0;
            }
            $row['percent'] = $row['total'] ? (int) round($row['done'] / $row['total'] * 100) : 0;
            $row['status'] = !$p ? 'noperiode' : ($row['done'] === $row['total'] ? 'ready'
                : (count(array_filter($row['cells'], static fn($c) => $c['st'] !== 'none')) ? 'progress' : 'notstarted'));
            $sum['ready'] += $row['status'] === 'ready' ? 1 : 0;
            $sum['progress'] += $row['percent'];
            $out[] = $row;
        }
        $sum['avg'] = $sum['total'] ? (int) round($sum['progress'] / $sum['total']) : 0;

        return [
            'sections' => array_map(static fn($s) => ['name' => $s['name'], 'link' => $s['link']], $sections),
            'transSections' => array_map(static fn($t) => ['name' => $t['name'], 'optional' => $t['optional']], $transSections),
            'bprs' => $out,
            'summary' => $sum,
            'generated' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Ringkasan approval per periode untuk satu tabel: [periode_id => ['komut' => bool, 'dirut' => bool]].
     * Disetujui = SEMUA baris periode disetujui (MIN), sesuai approval massal di halaman daftar.
     */
    private function aggregate(string $table, array $pids): array
    {
        if (!$pids || !$this->db->tableExists($table)) {
            return [];
        }
        $hasKomut = $this->db->fieldExists('accdekom', $table);
        $rows = $this->db->table($table)
            ->select('periode_id, MIN(is_approved) AS d' . ($hasKomut ? ', MIN(accdekom) AS k' : ''))
            ->whereIn('periode_id', $pids)
            ->groupBy('periode_id')
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['periode_id']] = ['dirut' => (int) $r['d'] === 1, 'komut' => $hasKomut && (int) ($r['k'] ?? 0) === 1];
        }
        return $out;
    }

    /** Dokumen Pendukung: jumlah slot PDF terunggah per periode. */
    private function dokumenStatus(array $pids): array
    {
        if (!$pids || !$this->db->tableExists('dokumenpendukung')) {
            return [];
        }
        $cols = array_column(M_dokumenpendukung::SLOTS, 0);
        $out = [];
        foreach ($this->db->table('dokumenpendukung')->select('periode_id, ' . implode(', ', $cols))
            ->whereIn('periode_id', $pids)->get()->getResultArray() as $r) {
            $out[(int) $r['periode_id']] = [
                'uploaded' => count(array_filter($cols, static fn($c) => !empty($r[$c]))),
                'slots' => count($cols),
            ];
        }
        return $out;
    }
}
