<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_kepatuhankpmr;

/**
 * Penilaian Risiko Kepatuhan KPMR (tabel risikokepatuhan_kpmr, faktor 84–103).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Kepatuhankpmr extends ProfilRisikoController
{
    protected const TABLE = 'risikokepatuhan_kpmr';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'KEPATUHANKPMR';
    protected const ROUTE = 'Kepatuhankpmr';
    protected const TITLE = 'Risiko Kepatuhan KPMR';
    protected const RISK = 'Risiko Kepatuhan';
    protected const ICON = 'fa-gavel';
    protected const TOTAL_ID = 102;
    protected const PREV_ID = 103;
    protected const NAV = ['Risikokepatuhan' => 'Inheren', 'Kepatuhankpmr' => 'KPMR'];

    private const KODE_MAP = [84 => '3310', 85 => '3311', 86 => '3312', 87 => '3313', 88 => '3314', 89 => '3315', 90 => '3316',
        91 => '3317', 92 => '3320', 93 => '3321', 94 => '3322', 95 => '3323', 96 => '3330', 97 => '3331', 98 => '3332',
        99 => '3340', 100 => '3341', 101 => '3342', 102 => '3350'];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_kepatuhankpmr();
    }

    protected function structure(): array
    {
        return [
            ['id' => 84, 'title' => 'Pengawasan Direksi dan Dewan Komisaris', 'children' => [
                ['id' => 85, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kepatuhan yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
                ['id' => 86, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kepatuhan secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
                ['id' => 87, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko kepatuhan, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
                ['id' => 88, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kepatuhan, dan melakukan komunikasi kebijakan manajemen risiko kepatuhan terhadap seluruh jenjang organisasi BPR?'],
                ['id' => 89, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kepatuhan dan fungsi manajemen risiko kepatuhan?'],
                ['id' => 90, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kepatuhan?'],
                ['id' => 91, 'title' => 'Apakah Direksi telah menyusun kebijakan internal yang mendukung terselenggaranya fungsi kepatuhan, memberikan perhatian terhadap ketentuan peraturan perundang-undangan, serta terdapat kebijakan reward and punishment bagi internal BPR?'],
            ]],
            ['id' => 92, 'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit', 'children' => [
                ['id' => 93, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko kepatuhan yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
                ['id' => 94, 'title' => 'Apakah BPR:
● Memiliki prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan yang ditetapkan oleh Direksi;
● Melaksanakan prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara konsisten untuk seluruh aktivitas; dan
● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara berkala?'],
                ['id' => 95, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kepatuhan sesuai dengan ketentuan?'],
            ]],
            ['id' => 96, 'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi', 'children' => [
                ['id' => 97, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko kepatuhan yang melekat pada kegiatan usaha BPR?'],
                ['id' => 98, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kepatuhan serta telah dilaporkan kepada Direksi secara berkala?'],
            ]],
            ['id' => 99, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh', 'children' => [
                ['id' => 100, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kepatuhan, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
                ['id' => 101, 'title' => 'Apakah sistem pengendalian intern terhadap risiko kepatuhan telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
            ]],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Peniliaian …") → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            84 => ['descriptions' => [
                1 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            85 => ['descriptions' => [
                1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kepatuhan;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kepatuhan;<br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Evaluasi yang diberikan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko kepatuhan.',
                2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kepatuhan;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kepatuhan; dan<br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kepatuhan;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko kepatuhan; dan<br>• Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kepatuhan; dan<br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko kepatuhan.',
                5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko kepatuhan; dan<br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            86 => ['descriptions' => [
                1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kepatuhan oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan;<br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan<br>• Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko kepatuhan dalam rangka mendukung perbaikan kinerja BPR.',
                2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kepatuhan oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kepatuhan oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kepatuhan oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan<br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko kepatuhan oleh Direksi.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            87 => ['descriptions' => [
                1 => '• Direksi telah menyusun kebijakan Manajemen Risiko kepatuhan;<br>• Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kepatuhan yang telah ditetapkan;<br>• Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko kepatuhan apabila ada kebutuhan termasuk perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan<br>• Kebijakan Manajemen Risiko kepatuhan yang dijalankan terbukti memitigasi terjadinya Risiko kepatuhan.',
                2 => '• Direksi telah menyusun kebijakan Manajemen Risiko kepatuhan;<br>• Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kepatuhan yang telah ditetapkan; dan<br>• Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko kepatuhan apabila ada kebutuhan termasuk perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                3 => '• Direksi telah menyusun kebijakan Manajemen Risiko kepatuhan;<br>• Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kepatuhan yang telah ditetapkan; dan<br>• Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko kepatuhan apabila ada kebutuhan termasuk perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                4 => '• Direksi telah menyusun kebijakan Manajemen Risiko kepatuhan;<br>• Tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko kepatuhan yang telah ditetapkan; dan<br>• Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko kepatuhan apabila ada kebutuhan termasuk perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            88 => ['descriptions' => [
                1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kepatuhan;<br>• Direksi mengomunikasi kan kebijakan Manajemen Risiko kepatuhan; dan<br>• Seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko kepatuhan yang diterapkan.',
                2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kepatuhan;<br>• Direksi mengomunikasi kan kebijakan Manajemen Risiko kepatuhan; dan<br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko kepatuhan yang diterapkan namun tidak menimbulkan dampak yang signifikan',
                3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kepatuhan;<br>• Direksi mengomunikasi kan kebijakan Manajemen Risiko kepatuhan; dan<br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko kepatuhan yang diterapkan dan menimbulkan dampak yang signifikan',
                4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko kepatuhan;<br>• Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko kepatuhan; dan<br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko kepatuhan yang diterapkan.',
                5 => '• Direksi tidak mengambil tindakan yangdiperlukan untuk memitigasi Risikosaat menjalankan kebijakan Manajemen Risiko kepatuhan;<br>• Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko kepatuhan; dan<br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko kepatuhan yang diterapkan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            89 => ['descriptions' => [
                1 => '• Memiliki unit kerja yang menangani fungsi kepatuhan secara lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yangbaik. 
 • Unit kerja yangmenangani fungsi kepatuhan telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan 
 • Memiliki SKMR atau PEMR danmampu melaksanakan fungsinya untuk memitigasi Risiko kepatuhan.',
                2 => '• Memiliki unit kerja yang menangani fungsi kepatuhan namun tidak lengkap dan tidak terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik.<br>• Unit kerja yang menangani fungsi kepatuhan telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR dan mampu melaksanakan fungsinya untuk memitigasi Risiko kepatuhan',
                3 => '• Memiliki unit kerja yang menangani fungsi kepatuhan namun tidak lengkap dan terdapat rangkap jabatan namun tidak menyebabkan tidak terlaksananya tata kelola yangbaik.<br>• Unit kerja yangmenangani fungsi kepatuhan telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko kepatuhan.',
                4 => '• Memiliki unit kerja yang menangani fungsi kepatuhan namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik.<br>• Unit kerja yang menangani fungsi kepatuhan telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko kepatuhan.',
                5 => '• Memiliki unit kerja yang menangani fungsi kepatuhan namun tidak lengkap dan terdapat rangkap jabatan yang dapat menyebabkan tidak terlaksananya tata kelola yang baik.<br>• Unit kerja yang menangani fungsi kepatuhan tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMRatau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risikokredit.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            90 => ['descriptions' => [
                1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan;<br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan sesuai dengan tugas dan tanggung jawab.',
                2 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan sesuai dengan tugas dan tanggung jawab.',
                3 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan sesuai dengan tugas dan tanggung jawab.',
                4 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                5 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• Tidak terdapat upaya peningkatan kompetensi SDM; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            91 => ['descriptions' => [
                1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan;<br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan sesuai dengan tugas dan tanggung jawab.',
                2 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan sesuai dengan tugas dan tanggung jawab.',
                3 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan sesuai dengan tugas dan tanggung jawab.',
                4 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                5 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• Tidak terdapat upaya peningkatan kompetensi SDM; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi kepatuhan tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            92 => ['descriptions' => [
                1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            93 => ['descriptions' => [
                1 => '• Memiliki prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• Melaksanakan prosedur Manajemen Risiko kepatuhan danpenetapan limit Risiko kepatuhan dalam setiap aktivitas fungsional secara konsisten; dan<br>• Melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kepatuhan danpenetapan limit Risiko kepatuhan dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan.',
                2 => '• Memiliki prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• Melaksanakan prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan dalam setiap aktivitas fungsional secara konsisten; dan<br>• Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan, namun tidak menimbulkan dampak yang signifikan.',
                3 => '• Memiliki prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• Melaksanakan prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan dalam setiap aktivitas fungsional secara konsisten; dan<br>• Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan, dan menimbulkan dampak yang signifikan.',
                4 => '• Memiliki prosedur Manajemen Risiko kepatuhan dan penetapan limit Risiko kepatuhan yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• Tidak melaksanakan prosedur Manajemen Risiko kepatuhan danpenetapan limit Risiko kepatuhan dalam setiap aktivitas fungsional secara konsisten; dan<br>• Tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko kepatuhan danpenetapan limit Risiko kepatuhan dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan.',
                5 => '• Tidak memiliki prosedur Manajemen Risikokredit dan penetapan limit Risiko kepatuhan yangditetapkan oleh Direksi.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            94 => ['descriptions' => [
                1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kepatuhan;<br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• Terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kepatuhan;<br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kepatuhan;<br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atauaktivitas baru yang memiliki eksposur Risiko kepatuhan;<br>• Tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            95 => ['descriptions' => [
                1 => '• Telah melaksanakan proses Manajemen Risiko kepatuhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kepatuhan terhadap kegiatan usaha BPR yang terkait dengan Risiko kepatuhan paling sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur;<br>• Penerapan Manajemen Risiko kepatuhan dilakukan dengan sangat memadai; dan<br>• Penerapan Manajemen Risiko kepatuhan dilakukan secara konsisten.',
                2 => '• Telah melaksanakan proses Manajemen Risiko kepatuhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kepatuhan terhadap kegiatan usaha BPR yang terkait dengan Risiko kepatuhan paling sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur;<br>• Penerapan Manajemen Risiko kepatuhan dilakukan dengan memadai; dan<br>• Penerapan Manajemen Risiko kepatuhan dilakukan cukup konsisten.',
                3 => '• Telah melaksanakan proses Manajemen Risiko kepatuhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kepatuhan terhadap kegiatan usaha BPR yang terkaitdengan Risiko kepatuhan paling sedikit mencakup kondisi keuanganatau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur;<br>• Penerapan Manajemen Risiko kepatuhan dilakukan dengan memadai; dan<br>• Penerapan Manajemen Risiko kepatuhan tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                4 => '• Telah melaksanakan proses Manajemen Risiko kepatuhan namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kepatuhan terhadap kegiatan usaha BPR yang terkait dengan Risiko kepatuhan paling sedikit mencakup kondisi keuangan atau laporan keuangan terakhir, hasil proyeksi arus kas, dan dokumen lain yang dapat digunakan untuk menganalisis kondisi dan kredibilitas debitur;<br>• Penerapan Manajemen Risiko kepatuhan tidak memadai; dan<br>• Penerapan Manajemen Risiko kepatuhan tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                5 => '• Tidak melaksanakan proses Manajemen Risiko kepatuhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko kepatuhan terhadap kegiatan usaha BPR yang terkait dengan Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            96 => ['descriptions' => [
                1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Informasi berada pada tingkat yang Sangat Rendah',
                2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Informasi berada pada tingkat yang Rendah',
                3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Informasi berada pada tingkat yang Sedang',
                4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Informasi berada pada tingkat yang Tinggi',
                5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Informasi berada pada tingkat yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            97 => ['descriptions' => [
                1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kepatuhan;<br>• Data pada sistem informasi Manajemen Risiko telah lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan<br>• Sistem informasi Manajemen Risiko sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                2 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kepatuhan;<br>• Data pada sistem informasi Manajemen Risiko cukup lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan<br>• Sistem informasi Manajemen Risiko cukup mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                3 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kepatuhan;<br>• Data pada sistem informasi Manajemen Risiko kurang lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan  sistem informasi Manajemen Risiko kurang mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                4 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko kepatuhan;<br>• Data pada sistem informasi Manajemen Risiko tidak lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan<br>• Sistem informasi Manajemen Risiko tidak mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                5 => 'Tidak memiliki sistem informasi Manajemen Risikoyang mencerminkan Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan KPMR'],
            98 => ['descriptions' => [
                1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kepatuhan, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko kepatuhan dengan mempertimbang kan ketentuan serta kondisi BPR; dan<br>• Hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                2 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kepatuhan, memberikan rekomendasi, dan melaporkan hasil audit internkepada Direktur Utama;<br>• Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko kepatuhan dengan mempertimbang kan ketentuan serta kondisi BPR; dan<br>• Hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                3 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kepatuhan, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• Audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko kepatuhan dengan mempertimbang kan ketentuan serta kondisi BPR; dan<br>• Hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                4 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko kepatuhan, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• Audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko kepatuhan; dan<br>• Hasil temuan audit intern yangdijadikan rekomendasi tidak ditindaklanjuti.',
                5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan KPMR'],
            99 => ['descriptions' => [
                1 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Sistem Pengendalian Internal yang Menyeluruh berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            100 => ['descriptions' => [
                1 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan;<br>• Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                2 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan dan tidak berdampak signifikan;<br>• Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                3 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan dan berdampak signifikan;<br>• Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                4 => '• Tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan dan berdampak sangat signifikan;<br>• Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                5 => '• seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan tidak melaksanakan fungsi pengendalian intern;<br>• Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
            101 => ['descriptions' => [
                1 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan;<br>• Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                2 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan dan tidak berdampak signifikan;<br>• Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unityang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                3 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan dan berdampak signifikan;<br>• Terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                4 => '• Tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko kepatuhan dan berdampak sangat signifikan;<br>• Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
                5 => '• Seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan tidak melaksanakan fungsi pengendalian intern;<br>• Tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan;<br>• SKMR atau PEMR tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan; dan<br>• SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko kepatuhan.',
            ], 'catatan' => 'Peniliaian Risiko Kepatuhan Kualitas Penerapan Manajemen Risiko (KPMR)'],
        ]);
    }

    private function sanitizeTxt(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        $text = str_replace(
            ['“', '”', '‘', '’', '%', '|'],
            ['"', '"', "'", "'", ' persen ', ' '],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function exporttxtrisikokepatuhankpmr()
    {
        if (!$this->auth->check()) {
            $redirectURL = session('redirect_url') ?? '/login';
            unset($_SESSION['redirect_url']);
            return redirect()->to($redirectURL);
        }
        // Periode aktif bukan milik BPR user → tidak ada berkas
        if (!$this->periodeId || !$this->userKodebpr) {
            return service('response')->setContentType('text/plain')->setBody('');
        }

        [
            'modalinti' => $modalinti,
            'nomorsurat' => $nomorsurat,
            'jeniskoreksi' => $jeniskoreksi,
            'jenispelaporan' => $jenispelaporan,
            'kategori' => $kategori,
        ] = $this->periode;
        $tahun = $this->periode['tahun'] ?? date('Y');
        $semester = $this->periode['semester'] ?? '';

        $exportDate = (strtolower($semester) === 'ganjil' || $semester === '1')
            ? "{$tahun}-06-30"
            : "{$tahun}-12-31";
        $titleDate = str_replace('-', '', $exportDate);

        $rows = $this->nilaiModel->getDataByKodebprAndPeriode($this->userKodebpr, $this->periodeId);
        usort($rows, static fn($a, $b) => ($a['faktor1id'] ?? 0) <=> ($b['faktor1id'] ?? 0));

        $infobpr = $this->infobprModel->getDataByKodebpr($this->userKodebpr);
        $sandibpr = $infobpr[0]['sandibpr'] ?? '';
        $kodejenis = $infobpr[0]['kodejenis'] ?? '';

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0302|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

        // Hanya baris yang tersimpan & punya kode (perilaku lama dipertahankan)
        foreach ($rows as $row) {
            $kode = self::KODE_MAP[$row['faktor1id'] ?? 0] ?? null;
            if ($kode === null) {
                continue;
            }
            $penilaiankredit = $this->sanitizeTxt((string) ($row['penilaiankredit'] ?? ''));
            $keterangan = $this->sanitizeTxt((string) ($row['keterangan'] ?? ''));
            $output .= "D01|{$kode}|{$penilaiankredit}|{$keterangan}\r\n";
        }

        $filename = "TKSBPRKS-0302-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
