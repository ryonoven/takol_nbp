<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Komentar halaman pelaporan (laporan tahunan & SAF) — satu mekanisme untuk semua halaman.
 *
 * Setiap halaman (PAGES) menentukan tabel penyimpanan komentarnya:
 *   - 'transparansi_comments' : laporan tahunan (subkategori = nama halaman)
 *   - 'semesteran_comments'   : SAF (kategori + subkategori; komentar lama Penerapansafst tetap terbaca)
 *
 * Status baca: tabel laporantahunan_comment_reads, SATU baris per user + halaman + BPR + periode berisi
 * id komentar terakhir yang dibaca. Belum dibaca = komentar orang lain dengan id > last_read_id.
 * (Kolom subkategori di tabel baca berisi KEY halaman, sehingga tidak bentrok antar tabel komentar.)
 */
class M_komentar extends Model
{
    protected $table = 'transparansi_comments';
    protected $returnType = 'array';

    private const READS = 'laporantahunan_comment_reads';

    /** key halaman => [judul, tabel, kategori|null, subkategori] */
    public const PAGES = [
        // Laporan tahunan
        'Riwayatbpr' => ['Riwayat Pendirian BPR', 'transparansi_comments', null, 'Riwayatbpr'],
        'Penjelasannpl' => ['Penjelasan NPL', 'transparansi_comments', null, 'Penjelasannpl'],
        'Usahasignifikan' => ['Perkembangan Usaha Signifikan', 'transparansi_comments', null, 'Usahasignifikan'],
        'Kebijaksanaanmanathn' => ['Strategi dan Kebijakan Manajemen', 'transparansi_comments', null, 'Kebijaksanaanmanathn'],
        'Bidusahathn' => ['Bidang Usaha', 'transparansi_comments', null, 'Bidusahathn'],
        'Titahunan' => ['Teknologi Informasi', 'transparansi_comments', null, 'Titahunan'],
        'Perkembanganpasar' => ['Perkembangan dan Target Pasar', 'transparansi_comments', null, 'Perkembanganpasar'],
        'Kerjasamabpr' => ['Kerja Sama BPR', 'transparansi_comments', null, 'Kerjasamabpr'],
        'Kegiatanpengembangan' => ['Kegiatan Pengembangan', 'transparansi_comments', null, 'Kegiatanpengembangan'],
        'Laporanakuntan' => ['Laporan Akuntan Publik', 'transparansi_comments', null, 'Laporanakuntan'],
        'Suratpernyataandireksi' => ['Surat Pernyataan Direksi', 'transparansi_comments', null, 'Suratpernyataandireksi'],
        'Dokumenpendukung' => ['Dokumen Pendukung', 'transparansi_comments', null, 'Dokumenpendukung'],
        // Laporan Transparansi (subkategori sama dengan versi lama -> komentar lama tetap terbaca)
        'Penjelasanumum' => ['Penjelasan Umum', 'transparansi_comments', null, 'Penjelasanumum'],
        'Tgjwbdir' => ['Tugas & Tanggung Jawab Direksi', 'transparansi_comments', null, 'Tgjwbdir'],
        'Tgjwbdekom' => ['Tugas & Tanggung Jawab Dewan Komisaris', 'transparansi_comments', null, 'Tgjwbdekom'],
        'Tgjwbkomite' => ['Tugas & Program Kerja Komite', 'transparansi_comments', null, 'Tgjwbkomite'],
        'Strukturkomite' => ['Struktur & Keanggotaan Komite', 'transparansi_comments', null, 'Strukturkomite'],
        'Sahamdirdekom' => ['Kepemilikan Saham Direksi & Dekom', 'transparansi_comments', null, 'Sahamdirdekom'],
        'Shmusahadirdekom' => ['Kepemilikan Saham pada Kelompok Usaha', 'transparansi_comments', null, 'Shmusahadirdekom'],
        'Shmdirdekomlain' => ['Kepemilikan Saham pada Perusahaan Lain', 'transparansi_comments', null, 'Shmdirdekomlain'],
        'Keuangandirdekompshm' => ['Hubungan Keuangan Direksi, Dekom & PSP', 'transparansi_comments', null, 'Keuangandirdekompshm'],
        'Keluargadirdekompshm' => ['Hubungan Keluarga Direksi, Dekom & PSP', 'transparansi_comments', null, 'Keluargadirdekompshm'],
        'Paketkebijakandirdekom' => ['Paket/Kebijakan Remunerasi', 'transparansi_comments', null, 'Paketkebijakandirdekom'],
        'Rasiogaji' => ['Rasio Gaji', 'transparansi_comments', null, 'Rasiogaji'],
        'Rapat' => ['Pelaksanaan Rapat', 'transparansi_comments', null, 'Rapat'],
        'Kehadirandekom' => ['Kehadiran Dewan Komisaris', 'transparansi_comments', null, 'Kehadirandekom'],
        'Fraudinternal' => ['Internal Fraud', 'transparansi_comments', null, 'Fraudinternal'],
        'Masalahhukum' => ['Permasalahan Hukum', 'transparansi_comments', null, 'Masalahhukum'],
        'Transaksikepentingan' => ['Transaksi Benturan Kepentingan', 'transparansi_comments', null, 'Transaksikepentingan'],
        'Danasosial' => ['Dana Kegiatan Sosial & Politik', 'transparansi_comments', null, 'Danasosial'],
        // Strategi Anti Fraud
        'Penerapansafst' => ['Penerapan Strategi Anti Fraud', 'semesteran_comments', 'DIRYMF', 'PENERAPANSAFST'],
        'Laporanpdfsaf' => ['Deskripsi Fraud / Modus Operandi', 'semesteran_comments', 'DIRYMF', 'LAPORANPDFSAF'],
    ];

    /** Kelompok halaman (untuk badge ringkasan, mis. kartu Showtahunan / Showsaf). */
    public const GROUP_TAHUNAN = ['Riwayatbpr', 'Penjelasannpl', 'Usahasignifikan', 'Kebijaksanaanmanathn', 'Bidusahathn', 'Titahunan',
        'Perkembanganpasar', 'Kerjasamabpr', 'Kegiatanpengembangan', 'Laporanakuntan', 'Suratpernyataandireksi', 'Dokumenpendukung'];
    public const GROUP_SAF = ['Penerapansafst', 'Laporanpdfsaf'];

    public const MIN_LEN = 3;
    public const MAX_LEN = 1000;

    public static function isPage(?string $page): bool
    {
        return $page !== null && isset(self::PAGES[$page]);
    }

    public static function title(string $page): string
    {
        return self::PAGES[$page][0] ?? $page;
    }

    /** Builder komentar satu halaman (sudah difilter BPR + periode + kategori/subkategori). */
    private function scoped(string $page, string $kodebpr, int $periodeId, string $alias = 'c')
    {
        [, $table, $kategori, $sub] = self::PAGES[$page];
        $b = $this->db->table($table . ' ' . $alias)
            ->where("{$alias}.kodebpr", $kodebpr)
            ->where("{$alias}.periode_id", $periodeId)
            ->where("{$alias}.subkategori", $sub);
        if ($kategori !== null) {
            $b->where("{$alias}.kategori", $kategori);
        }
        return $b;
    }

    /** Semua komentar satu halaman (lama -> baru). */
    public function listFor(string $page, string $kodebpr, int $periodeId): array
    {
        return $this->scoped($page, $kodebpr, $periodeId)
            ->select('c.id, c.user_id, COALESCE(NULLIF(u.fullname, \'\'), c.fullname) AS fullname, c.komentar, c.created_at')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->orderBy('c.id', 'ASC')
            ->get()->getResultArray();
    }

    public function add(string $page, string $kodebpr, int $periodeId, int $userId, string $fullname, string $komentar): int
    {
        [, $table, $kategori, $sub] = self::PAGES[$page];
        $now = date('Y-m-d H:i:s');
        $row = [
            'subkategori' => $sub,
            'user_id' => $userId,
            'fullname' => $fullname,
            'komentar' => $komentar,
            'kodebpr' => $kodebpr,
            'periode_id' => $periodeId,
            'created_at' => $now,
        ];
        if ($kategori !== null) {
            $row['kategori'] = $kategori;
        }
        if ($table === 'semesteran_comments') {
            $row['updated_at'] = $now;
        }
        $this->db->table($table)->insert($row);
        return (int) $this->db->insertID();
    }

    /**
     * Jumlah komentar belum dibaca per halaman — satu query per tabel komentar.
     * @param string[] $pages
     * @return array<string,int> key halaman => jumlah (halaman tanpa komentar baru tidak muncul)
     */
    public function unreadCounts(string $kodebpr, int $periodeId, int $userId, array $pages): array
    {
        $byTable = [];
        foreach ($pages as $page) {
            if (self::isPage($page)) {
                $byTable[self::PAGES[$page][1]][] = $page;
            }
        }

        $out = [];
        foreach ($byTable as $table => $list) {
            // CASE: subkategori(+kategori) tabel -> key halaman
            $case = 'CASE';
            $conds = [];
            foreach ($list as $page) {
                [, , $kategori, $sub] = self::PAGES[$page];
                $cond = 'c.subkategori = ' . $this->db->escape($sub) . ($kategori !== null ? ' AND c.kategori = ' . $this->db->escape($kategori) : '');
                $case .= " WHEN {$cond} THEN " . $this->db->escape($page);
                $conds[] = "({$cond})";
            }
            $case .= ' END';

            $sql = "SELECT pg, COUNT(*) AS n FROM (
                        SELECT {$case} AS pg, c.id FROM {$table} c
                        WHERE c.kodebpr = ? AND c.periode_id = ? AND c.user_id <> ? AND (" . implode(' OR ', $conds) . ")
                    ) x
                    LEFT JOIN " . self::READS . " r
                        ON r.user_id = ? AND r.kodebpr = ? AND r.periode_id = ? AND r.subkategori = x.pg
                    WHERE x.id > COALESCE(r.last_read_id, 0)
                    GROUP BY pg";
            foreach ($this->db->query($sql, [$kodebpr, $periodeId, $userId, $userId, $kodebpr, $periodeId])->getResultArray() as $r) {
                $out[$r['pg']] = (int) $r['n'];
            }
        }
        return $out;
    }

    /** Tandai semua komentar halaman sampai $lastId sudah dibaca (upsert satu baris). */
    public function markRead(string $page, string $kodebpr, int $periodeId, int $userId, int $lastId): void
    {
        if ($lastId <= 0) {
            return;
        }
        $this->db->query(
            'INSERT INTO ' . self::READS . ' (user_id, kodebpr, periode_id, subkategori, last_read_id, read_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE last_read_id = GREATEST(last_read_id, VALUES(last_read_id)), read_at = NOW()',
            [$userId, $kodebpr, $periodeId, $page, $lastId]
        );
    }
}
