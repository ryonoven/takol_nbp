<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_risikooperasionalkpmr;

/**
 * Penilaian Risiko Operasional KPMR (tabel risikooperasional_kpmr, faktor 51–71).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Risikooperasionalkpmr extends ProfilRisikoController
{
    protected const TABLE = 'risikooperasional_kpmr';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'OPERASIONALKPMR';
    protected const ROUTE = 'Risikooperasionalkpmr';
    protected const TITLE = 'Risiko Operasional KPMR';
    protected const RISK = 'Risiko Operasional';
    protected const ICON = 'fa-tasks';
    protected const TOTAL_ID = 70;
    protected const PREV_ID = 71;
    protected const NAV = ['Risikooperasional' => 'Inheren', 'Risikooperasionalkpmr' => 'KPMR'];

    private const KODE_MAP = [51 => '2310', 52 => '2311', 53 => '2312', 54 => '2313', 55 => '2314', 56 => '2315', 57 => '2316',
        58 => '2320', 59 => '2321', 60 => '2322', 61 => '2323', 62 => '2330', 63 => '2331', 64 => '2332', 65 => '2333',
        66 => '2334', 67 => '2340', 68 => '2341', 69 => '2342', 70 => '2350'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_risikooperasionalkpmr();
    }

    protected function structure(): array
    {
        return [
            ['id' => 51, 'title' => 'Pengawasan Direksi dan Dewan Komisaris', 'children' => [
                ['id' => 52, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko operasional yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
                ['id' => 53, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko operasional secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
                ['id' => 54, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko operasional, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
                ['id' => 55, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko operasional, dan melakukan komunikasi kebijakan manajemen risiko operasional terhadap seluruh jenjang organisasi BPR?'],
                ['id' => 56, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi operasional dan fungsi manajemen risiko operasional?'],
                ['id' => 57, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko operasional?'],
            ]],
            ['id' => 58, 'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit', 'children' => [
                ['id' => 59, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko operasional yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
                ['id' => 60, 'title' => 'Apakah BPR:
● Memiliki prosedur manajemen risiko operasional dan penetapan limit risiko operasional yang ditetapkan oleh Direksi;
● Melaksanakan prosedur manajemen risiko operasional dan penetapan limit risiko operasional secara konsisten untuk seluruh aktivitas; dan
● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko operasional dan penetapan limit risiko operasional secara berkala?'],
                ['id' => 61, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko operasional sesuai dengan ketentuan?'],
            ]],
            ['id' => 62, 'title' => 'Kecukupan Proses dan Sistem Manajemen Risiko', 'children' => [
                ['id' => 63, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko operasional yang melekat pada kegiatan usaha BPR?'],
                ['id' => 64, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko operasional serta telah dilaporkan kepada Direksi secara berkala?'],
                ['id' => 65, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penyelenggaraan TI?'],
                ['id' => 66, 'title' => 'Apakah BPR telah melakukan langkah mitigasi risiko terkait kejadian eksternal?'],
            ]],
            ['id' => 67, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh', 'children' => [
                ['id' => 68, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko operasional, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
                ['id' => 69, 'title' => 'Apakah sistem pengendalian intern terhadap risiko operasional telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
            ]],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik → tidak ditampilkan
        $catatan = 'Peniliaian Risiko Operasional Kualitas Penerapan Manajemen Risiko (KPMR)';
        return array_map(static fn($x) => array_diff_key($x, ['catatan' => 1, 'threshold' => 1]), [
            51 => ['descriptions' => [1 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Rendah', 3 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sedang', 4 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Pengawasan Direksi dan Komisaris berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            52 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional <br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan <br>• Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko operasional.',
                    2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional; dan <br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; <br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional; dan <br>• Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan',
                    4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; dan <br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional.',
                    5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional; dan <br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            53 => [
                'descriptions' => [
                    1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih; <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan <br>• Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko operasional.',
                    2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi dilakukan secara berkala setiap semester; <br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi dilakukan secara berkala setiap semester; dan <br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                    4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi; <br>• Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan <br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi.',
                    5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko operasional oleh Direksi.',
                ],
                'catatan' => $catatan
            ],
            54 => [
                'descriptions' => [
                    1 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; <br>• Direksi melakukan pengkinian secara berkala; dan <br>• Kebijakan yang dijalankan terbukti memitigasi terjadinya Risiko operasional.',
                    2 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; dan <br>• Direksi melakukan pengkinian secara berkala.',
                    3 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; dan <br>• Direksi tidak melakukan pengkinian secara berkala.',
                    4 => '• Direksi telah menyusun kebijakan Manajemen Risiko operasional; <br>• Tidak menjalankan kegiatan usaha berdasarkan kebijakan yang telah ditetapkan; dan <br>• Direksi tidak melakukan pengkinian.',
                    5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            55 => [
                'descriptions' => [
                    1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko operasional; dan <br>• Seluruh jenjang organisasi BPR mampu memahami kebijakan yang diterapkan.',
                    2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko operasional; dan <br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi mengomunikasikan kebijakan Manajemen Risiko operasional; dan <br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan dan menimbulkan dampak yang signifikan.',
                    4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko; <br>• Direksi tidak mengomunikasikan kebijakan; dan <br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan yang diterapkan.',
                    5 => '• Direksi tidak mengambil tindakan yang diperlukan; <br>• Direksi tidak mengomunikasikan kebijakan; dan <br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan yang diterapkan.',
                ],
                'catatan' => $catatan
            ],
            56 => [
                'descriptions' => [
                    1 => '• Memiliki unit kerja yang menangani fungsi operasional secara lengkap dan tidak terdapat rangkap jabatan. <br>• Unit kerja telah melaksanakan tugas dan wewenangnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    2 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan tidak terdapat rangkap jabatan. <br>• Unit kerja telah melaksanakan tugas dan wewenangnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko operasional.',
                    3 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan namun tidak menyebabkan tidak terlaksananya tata kelola yang baik. <br>• Unit kerja telah melaksanakan tugasnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya.',
                    4 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>• Unit kerja telah melaksanakan tugasnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya.',
                    5 => '• Memiliki unit kerja yang menangani fungsi operasional namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik. <br>• Unit kerja tidak melaksanakan tugasnya sesuai pedoman; dan <br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya.',
                ],
                'catatan' => $catatan
            ],
            57 => [
                'descriptions' => [
                    1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan; <br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM sesuai dengan tugas dan tanggung jawab.',
                    2 => '• Terdapat ketidaksesuaian kualifikasi SDM namun tidak memberikan dampak yang signifikan; <br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM sesuai dengan tugas dan tanggung jawab.',
                    3 => '• Terdapat ketidaksesuaian kualifikasi SDM namun tidak memberikan dampak yang signifikan; <br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM sesuai dengan tugas dan tanggung jawab.',
                    4 => '• Terdapat ketidaksesuaian kualifikasi SDM dan memberikan dampak yang signifikan; <br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan <br>• Tingkat pemenuhan standar kinerja SDM tidak sesuai namun tidak memberikan dampak yang signifikan.',
                    5 => '• Terdapat ketidaksesuaian kualifikasi SDM dan memberikan dampak yang signifikan; <br>• Tidak terdapat upaya peningkatan kompetensi SDM; dan <br>• Tingkat pemenuhan standar kinerja SDM tidak sesuai dan memberikan dampak yang signifikan.',
                ],
                'catatan' => $catatan
            ],
            58 => ['descriptions' => [1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            59 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat kesesuaian antara substansi kebijakan dengan ketentuan Manajemen Risiko BPR; dan <br>• Terdapat keselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis.',
                    2 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan dengan ketentuan; dan <br>• Terdapat keselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis.',
                    3 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan dengan ketentuan; dan <br>• Terdapat ketidakselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah memiliki kebijakan Manajemen Risiko operasional; <br>• Terdapat ketidaksesuaian yang signifikan antara substansi kebijakan dengan ketentuan; dan <br>• Terdapat ketidakselarasan antara kebijakan dengan visi, misi, skala usaha, dan kompleksitas bisnis dan menimbulkan dampak yang signifikan.',
                    5 => 'Tidak memiliki kebijakan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            60 => [
                'descriptions' => [
                    1 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Melaksanakan prosedur secara konsisten; dan <br>• Melakukan evaluasi dan pengkinian dalam hal terdapat perubahan bisnis yang signifikan.',
                    2 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Melaksanakan prosedur secara konsisten; dan <br>• Tidak melakukan evaluasi dan pengkinian, namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Melaksanakan prosedur secara konsisten; dan <br>• Tidak melakukan evaluasi dan pengkinian, dan menimbulkan dampak yang signifikan.',
                    4 => '• Memiliki prosedur dan limit yang ditetapkan Direksi; <br>• Tidak melaksanakan prosedur secara konsisten; dan <br>• Tidak melakukan evaluasi dan pengkinian.',
                    5 => 'Tidak memiliki prosedur Manajemen Risiko operasional dan penetapan limit.',
                ],
                'catatan' => $catatan
            ],
            61 => [
                'descriptions' => [
                    1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan <br>• Terdapat kesesuaian antara kebijakan dan prosedur dengan ketentuan.',
                    2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Menerapkan kebijakan dan prosedur; dan <br>• Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur dengan ketentuan.',
                    3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Menerapkan kebijakan dan prosedur; dan <br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur dengan ketentuan.',
                    4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru; <br>• Tidak menerapkan kebijakan dan prosedur; dan <br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur dengan ketentuan.',
                    5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru.',
                ],
                'catatan' => $catatan
            ],
            62 => ['descriptions' => [1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah', 3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang', 4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            63 => [
                'descriptions' => [
                    1 => '• Telah melaksanakan proses Manajemen Risiko operasional meliputi identifikasi, pengukuran, pemantauan, dan pengendalian; <br>• Penerapan Manajemen Risiko operasional dilakukan dengan sangat memadai; dan <br>• Penerapan dilakukan secara konsisten.',
                    2 => '• Telah melaksanakan proses Manajemen Risiko operasional; <br>• Penerapan dilakukan dengan memadai; dan <br>• Penerapan dilakukan cukup konsisten.',
                    3 => '• Telah melaksanakan proses Manajemen Risiko operasional; <br>• Penerapan dilakukan dengan memadai; dan <br>• Penerapan tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                    4 => '• Telah melaksanakan proses Manajemen Risiko operasional namun tidak secara keseluruhan; <br>• Penerapan tidak memadai; dan <br>• Penerapan tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                    5 => 'Tidak melaksanakan proses Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            64 => [
                'descriptions' => [
                    1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional; <br>• Data pada sistem informasi lengkap, akurat, kini, dan utuh; <br>• Mendukung Direksi dalam pengambilan keputusan; dan <br>• Sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                    2 => '• Telah memiliki sistem informasi Manajemen Risiko; <br>• Data cukup lengkap, akurat, kini, dan utuh; <br>• Mendukung Direksi dalam pengambilan keputusan; dan <br>• Cukup mendukung SKMR atau PEMR dalam pembuatan laporan.',
                    3 => '• Telah memiliki sistem informasi Manajemen Risiko; <br>• Data kurang lengkap, akurat, kini, dan utuh; <br>• Tidak sepenuhnya mendukung Direksi; dan <br>• Kurang mendukung SKMR atau PEMR dalam pembuatan laporan.',
                    4 => '• Telah memiliki sistem informasi Manajemen Risiko; <br>• Data tidak lengkap, akurat, kini, dan utuh; <br>• Tidak sepenuhnya mendukung Direksi; dan <br>• Tidak mendukung SKMR atau PEMR dalam pembuatan laporan.',
                    5 => 'Tidak memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            65 => [
                'descriptions' => [
                    1 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi; <br>• Telah menjalankan kegiatan operasional sesuai kebijakan; dan <br>• Telah memiliki aspek pengamanan TI sesuai ketentuan OJK.',
                    2 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI; <br>• Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai kebijakan dan tidak menimbulkan dampak signifikan; dan <br>• Telah memiliki aspek pengamanan TI sesuai ketentuan OJK.',
                    3 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI; <br>• Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai kebijakan dan menimbulkan dampak signifikan; dan <br>• Telah memiliki aspek pengamanan TI sesuai ketentuan OJK.',
                    4 => '• Telah memiliki kebijakan dan prosedur penyelenggaraan TI; <br>• Telah menjalankan kegiatan operasional namun tidak sepenuhnya sesuai kebijakan dan menimbulkan dampak signifikan; dan <br>• Aspek pengamanan TI tidak sesuai ketentuan OJK.',
                    5 => 'Tidak memiliki kebijakan dan prosedur penyelenggaraan TI yang telah ditetapkan oleh Direksi.',
                ],
                'catatan' => $catatan
            ],
            66 => [
                'descriptions' => [
                    1 => '• Telah memiliki rekam cadang, sumber listrik cadangan, jaringan komunikasi alternatif, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana; dan <br>• Telah melakukan uji coba terhadap rencana pemulihan bencana sesuai ketentuan OJK.',
                    2 => '• Telah memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana; dan <br>• Telah melakukan uji coba namun tidak sepenuhnya sesuai ketentuan OJK.',
                    3 => '• Telah memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai ketentuan dan tidak menimbulkan dampak signifikan; dan <br>• Telah melakukan uji coba namun tidak sepenuhnya sesuai ketentuan OJK.',
                    4 => '• Telah memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Telah memiliki rencana pemulihan bencana namun tidak sepenuhnya sesuai ketentuan dan menimbulkan dampak signifikan; dan <br>• Telah melakukan uji coba namun tidak sepenuhnya sesuai ketentuan OJK.',
                    5 => '• Tidak memiliki rekam cadang, sumber listrik cadangan, dan pusat pemulihan bencana sesuai ketentuan OJK; <br>• Rencana pemulihan bencana tidak sepenuhnya sesuai ketentuan dan menimbulkan dampak signifikan; dan <br>• Uji coba tidak sepenuhnya sesuai ketentuan OJK.',
                ],
                'catatan' => $catatan
            ],
            67 => ['descriptions' => [1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah', 2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah', 3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang', 4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi', 5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi'], 'catatan' => $catatan],
            68 => [
                'descriptions' => [
                    1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama; <br>• Audit intern mencakup pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                    2 => '• SKAI atau PEAI telah melaksanakan audit intern; <br>• Audit intern mencakup pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                    3 => '• SKAI atau PEAI telah melaksanakan audit intern; <br>• Audit intern mencakup pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                    4 => '• SKAI atau PEAI telah melaksanakan audit intern; <br>• Audit intern tidak sesuai cakupan pelaksanaan kebijakan dan prosedur; dan <br>• Hasil temuan tidak ditindaklanjuti.',
                    5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko operasional.',
                ],
                'catatan' => $catatan
            ],
            69 => [
                'descriptions' => [
                    1 => '• Seluruh jenjang organisasi BPR telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan, prosedur, serta penetapan limit; <br>• Terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah dari unit operasional; dan <br>• SKAI atau PEAI terpisah dari unit operasional.',
                    2 => '• Seluruh jenjang organisasi BPR telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan dan tidak berdampak signifikan; <br>• Terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah; dan <br>• SKAI atau PEAI terpisah.',
                    3 => '• Seluruh jenjang organisasi BPR telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan dan berdampak signifikan; <br>• Terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah; dan <br>• SKAI atau PEAI terpisah.',
                    4 => '• Tidak seluruh jenjang organisasi BPR melaksanakan fungsi pengendalian intern dan berdampak sangat signifikan; <br>• Tidak terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR terpisah; dan <br>• SKAI atau PEAI terpisah.',
                    5 => '• Seluruh jenjang organisasi BPR tidak melaksanakan fungsi pengendalian intern; <br>• Tidak terdapat kejelasan wewenang dan tanggung jawab; <br>• SKMR atau PEMR tidak terpisah dari unit operasional; dan <br>• SKAI atau PEAI tidak terpisah dari unit operasional.',
                ],
                'catatan' => $catatan
            ],
        ]);
    }

    private static function sanitizeTxt(string $text = ''): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(['"', '"', '%', '|'], ['"', '"', "'", "'", ' persen ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikooperasionalkpmr()
    {
        if (!$this->auth->check()) {
            return redirect()->to(session('redirect_url') ?? '/login');
        }
        // Periode aktif bukan milik BPR user → tidak ada berkas
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }
        $periodeDetail = $this->periode;
        $tahun = $periodeDetail['tahun'] ?? date('Y');
        $semester = $periodeDetail['semester'] ?? '';
        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1') ? "{$tahun}-06-30" : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $data = $this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr)[0] ?? [];

        $sandibpr = $infobpr['sandibpr'] ?? '';
        $kodejenis = $infobpr['kodejenis'] ?? '';
        $modalinti = $periodeDetail['modalinti'];
        $nomorsurat = $periodeDetail['nomorsurat'] ?? '';
        $jeniskoreksi = $periodeDetail['jeniskoreksi'];
        $jenispelaporan = $periodeDetail['jenispelaporan'];
        $kategori = $periodeDetail['kategori'];

        usort($data, fn($a, $b) => ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0));

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0202|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";
        foreach ($data as $row) {
            $fid = $row['faktor1id'] ?? null;
            if (!isset(self::KODE_MAP[$fid])) {
                continue;
            }
            $output .= 'D01|' . self::KODE_MAP[$fid] . '|' . self::sanitizeTxt($row['penilaiankredit']) . '|' . self::sanitizeTxt($row['keterangan']) . "\r\n";
        }

        $filename = "TKSBPRKS-0202-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";
        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
