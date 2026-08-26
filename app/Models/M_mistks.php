<?php
namespace App\Models;

use CodeIgniter\Model;
use Config\Services;

class M_mistks extends Model
{
    protected $table = 'mis_tks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'oid',
        'bpr',
        'periode',
        'judul',
        'keterangan',
        'nominal_1',
        'nominal_2',
        'nominal_3',
        'nominal_4',
        'nominal_5',
        'inptgljam',
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    protected const CACHE_TTL = 600;

    private const FIELD_MAP = [
        // judul  => [ [substring_keterangan, field_current, field_before], ... ]
        'ROA' => [
            ['Laba Sebelum Pajak (yang disetahunkan)', 'labarugibelumpajak', 'lababelumpajakbefore'],
            ['Rata-rata Aset', 'ratarataaset', 'ratarataasetbefore'],
        ],
        'BOPO' => [
            ['Beban Operasional (yang disetahunkan)', 'bebanoperasional', 'bebanoperasionalbefore'],
            ['Pendapatan Operasional (yang disetahunkan)', 'pendapatanoperasional', 'pendapatanoperasionalbefore'],
        ],
        'NIM' => [
            ['Pendapatan Bunga yang disetahunkan', 'pendapatanbunga', 'pendapatanbungabefore'],
            ['Beban Bunga yang disetahunkan', 'bebanbunga', 'bebanbungabefore'],
            ['Rata-rata Aset Produktif', 'ratarataasetproduktif', 'ratarataasetprodbefore'],
        ]
    ];

    private const FIELD_MAP_PERMODALAN = [
        'Modal' => [
            ['Jumlah Modal Inti', 'modalinti', 'modalintibefore'],
            ['JUMLAH MODAL PELENGKAP (II.1 + II.2 + II.3)', 'modalpelengkap', 'modalpelengkapbefore'],
            ['ATMR', 'atmr', 'atmrbefore'],
        ],
        'MIAPB' => [
            ['Modal Inti', 'modalintiapb', 'modalintiapbbefore'],
            ['Aset Produktif Bermasalah (Netto)', 'totalasetbermasalah', 'totalasetbermasalahbefore'],
        ],
    ];

    public function getMisTksDataCached(string $bpr, string $periodeCurrent, string $periodePrev): array
    {
        $cache = Services::cache();
        $cacheKey = "mistks_full_{$bpr}_{$periodeCurrent}_{$periodePrev}";

        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rows = $this->select('periode, judul, keterangan, nominal_5')
            ->where('bpr', $bpr)
            ->whereIn('periode', [$periodeCurrent, $periodePrev])
            ->whereIn('judul', ['ROA', 'BOPO', 'NIM'])
            ->orderBy('id', 'ASC')
            ->findAll();

        // Inisialisasi semua field ke null
        $result = [];
        foreach (self::FIELD_MAP as $judul => $fields) {
            foreach ($fields as [$sub, $fCur, $fBef]) {
                $result[$fCur] = $result[$fCur] ?? null;
                $result[$fBef] = $result[$fBef] ?? null;
            }
        }

        foreach ($rows as $row) {
            $judul = $row['judul'];
            if (!isset(self::FIELD_MAP[$judul]))
                continue;

            foreach (self::FIELD_MAP[$judul] as [$sub, $fieldCurrent, $fieldBefore]) {
                if (stripos($row['keterangan'], $sub) === false)
                    continue;

                if ($row['periode'] === $periodeCurrent && $result[$fieldCurrent] === null) {
                    $result[$fieldCurrent] = $row['nominal_5'];
                } elseif ($row['periode'] === $periodePrev && $result[$fieldBefore] === null) {
                    $result[$fieldBefore] = $row['nominal_5'];
                }
            }
        }

        $cache->save($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    public function clearMisTksCache(string $bpr, string $periodeCurrent, string $periodePrev): void
    {
        Services::cache()->delete("mistks_full_{$bpr}_{$periodeCurrent}_{$periodePrev}");
    }

    public function getPermodalanDataCached(string $bpr, string $periodeCurrent, string $periodePrev): array
    {
        $cache = Services::cache();
        $cacheKey = "mistks_permodalan_{$bpr}_{$periodeCurrent}_{$periodePrev}";

        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rows = $this->select('periode, judul, keterangan, nominal_5')
            ->where('bpr', $bpr)
            ->whereIn('periode', [$periodeCurrent, $periodePrev])
            ->whereIn('judul', ['Modal', 'MIAPB'])
            ->orderBy('id', 'ASC')
            ->findAll();

        $result = [];
        foreach (self::FIELD_MAP_PERMODALAN as $judul => $fields) {
            foreach ($fields as [$sub, $fCur, $fBef]) {
                $result[$fCur] = $result[$fCur] ?? null;
                $result[$fBef] = $result[$fBef] ?? null;
            }
        }

        foreach ($rows as $row) {
            $judul = $row['judul'];
            if (!isset(self::FIELD_MAP_PERMODALAN[$judul]))
                continue;

            foreach (self::FIELD_MAP_PERMODALAN[$judul] as [$sub, $fieldCurrent, $fieldBefore]) {
                // trim() untuk keterangan sumber, karena beberapa contoh punya spasi/tab di depan (mis. " 2. Aset Produktif...")
                if (stripos(trim($row['keterangan']), $sub) === false)
                    continue;

                if ($row['periode'] === $periodeCurrent && $result[$fieldCurrent] === null) {
                    $result[$fieldCurrent] = $row['nominal_5'];
                } elseif ($row['periode'] === $periodePrev && $result[$fieldBefore] === null) {
                    $result[$fieldBefore] = $row['nominal_5'];
                }
            }
        }

        $cache->save($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    public function clearPermodalanCache(string $bpr, string $periodeCurrent, string $periodePrev): void
    {
        Services::cache()->delete("mistks_permodalan_{$bpr}_{$periodeCurrent}_{$periodePrev}");
    }
}