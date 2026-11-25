<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Laporan Transparansi 11 — Paket/Kebijakan Remunerasi dan Fasilitas Lain bagi Direksi dan Dewan Komisaris (E0500).
 * Satu baris per BPR + periode. Approval: Komisaris Utama (accdekom) lalu Direktur Utama (is_approved).
 * Total (616, 625, 630) selalu dihitung ulang di server dari nominal per pos.
 */
class M_paketkebijakandirdekom extends Model
{
    protected $table = 'paketkebijakandirdekom';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public const FORM_CODE = 'E0500';
    public const SUBKATEGORI = 'Paketkebijakandirdekom';
    public const MAX_TINDAK = 4000;
    public const MAX_PENERIMA = 9999;
    public const MAX_NOMINAL_DIGITS = 15;

    /** Pos remunerasi & fasilitas: kode OJK => [no, label, grup, penerima dir, nominal dir, penerima dekom, nominal dekom] */
    public const ITEMS = [
        '611' => ['1.1', 'Gaji', 'remun', 'penerimagajidir', 'nominalgajidir', 'penerimagajidekom', 'nominalgajidekom'],
        '612' => ['1.2', 'Tunjangan', 'remun', 'terimatunjangandir', 'nominaltunjangandir', 'terimatunjangandekom', 'nominaltunjangandekom'],
        '613' => ['1.3', 'Tantiem', 'remun', 'terimatantiemdir', 'nominaltantiemdir', 'terimatantiemdekom', 'nominaltantiemdekom'],
        '614' => ['1.4', 'Kompensasi berbasis saham', 'remun', 'terimashmdir', 'nominalshmdir', 'terimashmdekom', 'nominalshmdekom'],
        '615' => ['1.5', 'Remunerasi lainnya', 'remun', 'terimaremunlaindir', 'nominalremunlaindir', 'terimaremunlaindekom', 'nominalremunlaindekom'],
        '621' => ['2.1', 'Perumahan', 'fas', 'terimarumahdir', 'nominalrumahdir', 'terimarumahdekom', 'nominalrumahdekom'],
        '622' => ['2.2', 'Transportasi', 'fas', 'terimatransportdir', 'nominaltransportdir', 'terimatransportdekom', 'nominaltransportdekom'],
        '623' => ['2.3', 'Asuransi Kesehatan', 'fas', 'terimaasuransidir', 'nominalasuransidir', 'terimaasuransidekom', 'nominalasuransidekom'],
        '624' => ['2.4', 'Fasilitas Lainnya', 'fas', 'terimafasilitasdir', 'nominalfasilitasdir', 'terimafasilitasdekom', 'nominalfasilitasdekom'],
    ];

    /** Baris total: kode => [label, kolom direksi, kolom dekom] */
    public const TOTALS = [
        '616' => ['Total Remunerasi', 'totalremundir', 'totalremundekom'],
        '625' => ['Total Fasilitas Lain', 'totalfasdir', 'totalfasdekom'],
        '630' => ['Total Remunerasi dan Fasilitas Lain', 'totaldir', 'totaldekom'],
    ];

    /** Urutan baris D01 di TXT. */
    public const TXT_ORDER = ['611', '612', '613', '614', '615', '616', '621', '622', '623', '624', '625', '630'];

    /** Kolom NOT NULL tanpa default (aman untuk MySQL strict mode). */
    private const INSERT_DEFAULTS = [
        'keterangan' => '', 'komentar' => '', 'groups_id' => 0,
        'is_approved' => 0, 'approved_by' => 0, 'accdekom' => 0, 'accdekom_by' => 0,
        'accdekom2' => 0, 'accdekom2_by' => 0, 'accdekom3' => 0, 'accdekom3_by' => 0,
        'accdekom4' => 0, 'accdekom4_by' => 0, 'accdekom5' => 0, 'accdekom5_by' => 0,
    ];

    public function __construct(...$args)
    {
        $this->allowedFields = array_merge(
            self::valueColumns(),
            array_merge(...array_map(static fn($t) => [$t[1], $t[2]], array_values(self::TOTALS))),
            array_keys(self::INSERT_DEFAULTS),
            ['kodebpr', 'periode_id', 'user_id', 'fullname', 'approved_at', 'accdekom_at']
        );
        parent::__construct(...$args);
    }

    /** Semua kolom isian (penerima & nominal). */
    public static function valueColumns(): array
    {
        $cols = [];
        foreach (self::ITEMS as $it) {
            array_push($cols, $it[3], $it[4], $it[5], $it[6]);
        }
        return $cols;
    }

    public static function isNominal(string $col): bool
    {
        return strpos($col, 'nominal') === 0;
    }

    /** Hitung total remunerasi, fasilitas dan keseluruhan dari nominal per pos. */
    public static function computeTotals(array $v): array
    {
        $sum = ['remun' => [0, 0], 'fas' => [0, 0]];
        foreach (self::ITEMS as $it) {
            $sum[$it[2]][0] += (int) ($v[$it[4]] ?? 0);
            $sum[$it[2]][1] += (int) ($v[$it[6]] ?? 0);
        }
        return [
            'totalremundir' => $sum['remun'][0], 'totalremundekom' => $sum['remun'][1],
            'totalfasdir' => $sum['fas'][0], 'totalfasdekom' => $sum['fas'][1],
            'totaldir' => $sum['remun'][0] + $sum['fas'][0], 'totaldekom' => $sum['remun'][1] + $sum['fas'][1],
        ];
    }

    /** Dipakai Pdfgcg. */
    public function getAllData(): array
    {
        return $this->db->table($this->table)->get()->getResultArray();
    }

    public function getDataByKodebprAndPeriode($kodebpr, $periodeId): array
    {
        return $this->where('kodebpr', $kodebpr)->where('periode_id', $periodeId)->findAll();
    }

    public function getByPeriode(int $periodeId, string $kodebpr): ?array
    {
        return $this->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)->orderBy('id', 'DESC')->first();
    }

    /** Data tahun sebelumnya (periode terbaru di tahun - 1). */
    public function getPrevYear(string $kodebpr, int $tahun): ?array
    {
        return $this->db->table($this->table . ' p')
            ->select('p.*')
            ->join('periode_tahunan t', 't.id = p.periode_id')
            ->where('p.kodebpr', $kodebpr)->where('t.tahun', $tahun - 1)
            ->orderBy('p.periode_id', 'DESC')->orderBy('p.id', 'DESC')
            ->get(1)->getRowArray();
    }

    public function saveRow(?int $id, array $data): bool
    {
        return $id ? (bool) $this->update($id, $data) : (bool) $this->insert($data + self::INSERT_DEFAULTS);
    }

    public static function resetApprovalData(): array
    {
        return ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
    }

    /** Membatalkan Komisaris Utama juga membatalkan Direktur Utama. */
    public function setApproval(int $id, string $type, bool $approve, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($type === 'komut') {
            $data = $approve ? ['accdekom' => 1, 'accdekom_by' => $userId, 'accdekom_at' => $now]
                : ['accdekom' => 0, 'accdekom_by' => 0, 'is_approved' => 0, 'approved_by' => 0];
        } else {
            $data = $approve ? ['is_approved' => 1, 'approved_by' => $userId, 'approved_at' => $now]
                : ['is_approved' => 0, 'approved_by' => 0];
        }
        return (bool) $this->update($id, $data);
    }

    // ------------------------------------------------------------------ penjelasan lebih lanjut (penjelastindak)

    public function getTindak(int $periodeId, string $kodebpr): ?array
    {
        return $this->db->table('penjelastindak')->select('id, tindaklanjut')
            ->where('subkategori', self::SUBKATEGORI)->where('periode_id', $periodeId)->where('kodebpr', $kodebpr)
            ->orderBy('id', 'ASC')->get(1)->getRowArray();
    }

    public function saveTindak(?int $id, array $data): bool
    {
        $t = $this->db->table('penjelastindak');
        return $id ? (bool) $t->where('id', $id)->update($data)
            : (bool) $t->insert($data + ['subkategori' => self::SUBKATEGORI]);
    }
}
