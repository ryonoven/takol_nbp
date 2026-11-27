<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_reputasikpmr;

/**
 * Penilaian Risiko Reputasi KPMR (tabel reputasikpmr, faktor 151–169).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Reputasikpmr extends ProfilRisikoController
{
    protected const TABLE = 'reputasikpmr';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'REPUTASIKPMR';
    protected const ROUTE = 'Reputasikpmr';
    protected const TITLE = 'Risiko Reputasi KPMR';
    protected const RISK = 'Risiko Reputasi';
    protected const ICON = 'fa-comments';
    protected const TOTAL_ID = 168;
    protected const PREV_ID = 169;
    protected const NAV = ['Reputasiinheren' => 'Inheren', 'Reputasikpmr' => 'KPMR'];

    private const KODE_MAP = [
        151 => '5310', 152 => '5311', 153 => '5312', 154 => '5313', 155 => '5314', 156 => '5315', 157 => '5316',
        158 => '5320', 159 => '5321', 160 => '5322', 161 => '5323', 162 => '5330', 163 => '5331', 164 => '5332',
        165 => '5340', 166 => '5341', 167 => '5342', 168 => '5350',
    ];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_reputasikpmr();
    }

    protected function structure(): array
    {
        return [
            ['id' => 151, 'title' => 'Pengawasan Direksi dan Dewan Komisaris', 'children' => [
                ['id' => 152, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko reputasi yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
                ['id' => 153, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko reputasi secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
                ['id' => 154, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko reputasi, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
                ['id' => 155, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko reputasi, dan melakukan komunikasi kebijakan manajemen risiko reputasi terhadap seluruh jenjang organisasi BPR?'],
                ['id' => 156, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi reputasi dan fungsi manajemen risiko reputasi?'],
                ['id' => 157, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko reputasi?'],
            ]],
            ['id' => 158, 'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit', 'children' => [
                ['id' => 159, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko reputasi yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
                ['id' => 160, 'title' => 'Apakah BPR:
● Memiliki prosedur manajemen risiko reputasi dan penetapan limit risiko reputasi yang ditetapkan oleh Direksi;
● Melaksanakan prosedur manajemen risiko reputasi dan penetapan limit risiko reputasi secara konsisten untuk seluruh aktivitas; dan
● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko reputasi dan penetapan limit risiko reputasi secara berkala?'],
                ['id' => 161, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko reputasi sesuai dengan ketentuan?'],
            ]],
            ['id' => 162, 'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi', 'children' => [
                ['id' => 163, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko reputasi yang melekat pada kegiatan usaha BPR?'],
                ['id' => 164, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko reputasi serta telah dilaporkan kepada Direksi secara berkala?'],
            ]],
            ['id' => 165, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh', 'children' => [
                ['id' => 166, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko reputasi, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
                ['id' => 167, 'title' => 'Apakah sistem pengendalian intern terhadap risiko reputasi telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
            ]],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Peniliaian …") → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            151 => ['descriptions' => [
                1 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Pengawasan Direksi dan Dewan Komisaris berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            152 => ['descriptions' => [
                1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko reputasi;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko reputasi;<br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Evaluasi yang dilakukan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko reputasi.',
                2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko reputasi;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko reputasi; dan<br>• Evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko reputasi;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko reputasi; dan<br>• Evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko reputasi; dan<br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko reputasi.',
                5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko reputasi; dan<br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko reputasi.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            153 => ['descriptions' => [
                1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko reputasi oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan;<br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan<br>• Evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko reputasi dalam rangka mendukung perbaikan kinerja BPR.',
                2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko reputasi oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko reputasi oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko reputasi oleh Direksi;<br>• Evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala; dan<br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko reputasi oleh Direksi.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            154 => ['descriptions' => [
                1 => '• Direksi telah menyusun kebijakan Manajemen Risiko reputasi;<br>• Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko reputasi yang telah ditetapkan;<br>• Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko reputasi dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan<br>• Kebijakan Manajemen Risiko reputasi yang dijalankan terbukti memitigasi terjadinya Risiko reputasi.',
                2 => '• Direksi telah menyusun kebijakan Manajemen Risiko reputasi;<br>• Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko reputasi yang telah ditetapkan; dan<br>• Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko reputasi dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                3 => '• Direksi telah menyusun kebijakan Manajemen Risiko reputasi;<br>• Menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko reputasi yang telah ditetapkan; dan<br>• Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko reputasi dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                4 => '• Direksi telah menyusun kebijakan Manajemen Risiko reputasi;<br>• Tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko reputasi yang telah ditetapkan; dan<br>• Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko reputasi dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko. reputasi.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            155 => ['descriptions' => [
                1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko reputasi;<br>• Direksi mengomunikasi kan kebijakan Manajemen Risiko reputasi; dan<br>• Seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko reputasi yang diterapkan.',
                2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko reputasi;<br>• Direksi mengomunikasi kan kebijakan Manajemen Risiko reputasi; dan<br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko reputasi yang diterapkan namun tidak menimbulkan dampak yang signifikan.',
                3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko reputasi;<br>• Direksi mengomunikasi kan kebijakan Manajemen Risiko reputasi; dan<br>• Tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko reputasi yang diterapkan dan menimbulkan dampak yang signifikan.',
                4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko reputasi;<br>• Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko reputasi; dan<br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko reputasi yang diterapkan.',
                5 => '• Direksi tidak mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko reputasi;<br>• Direksi tidak mengomunikasi kan kebijakan Manajemen Risiko reputasi; dan<br>• Seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko reputasi yang diterapkan.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            156 => ['descriptions' => [
                1 => '• Memiliki unit kerja yang menangani fungsi reputasi;<br>• Unit kerja yang menangani fungsi reputasi telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR yang mampu melaksanakan fungsinya untuk memitigasi Risiko reputasi.',
                2 => '• Memiliki unit kerja yang menangani fungsi reputasi namun tidak lengkap;<br>• Unit kerja yang menangani fungsi reputasi telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR yang mampu melaksanakan fungsinya untuk memitigasi Risiko reputasi.',
                3 => '• Memiliki unit kerja yang menangani fungsi reputasi;<br>• Unit kerja yang menangani fungsi reputasi telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko reputasi.',
                4 => '• Memiliki unit kerja yang menangani fungsi reputasi namun tidak lengkap;<br>• Unit kerja yang menangani fungsi reputasi telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi risiko reputasi.',
                5 => '• Memiliki unit kerja yang menangani fungsi reputasi namun tidak lengkap;<br>• Unit kerja yang menangani fungsi reputasi tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• Memiliki SKMR atau PEMR namun tidak mampu melaksanakan fungsinya untuk memitigasi Risiko reputasi.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            157 => ['descriptions' => [
                1 => '• Terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan;<br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi reputasi sesuai dengan tugas dan tanggung jawab.',
                2 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi reputasi sesuai dengan tugas dan tanggung jawab.',
                3 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi reputasi sesuai dengan tugas dan tanggung jawab',
                4 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• Terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi reputasi tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                5 => '• Terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• Tidak terdapat upaya peningkatan kompetensi SDM; dan<br>• Tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi reputasi tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan.',
            ], 'catatan' => 'Peniliaian Risiko Reputasi Kualitas Penerapan Manajemen Risiko (KPMR)'],
            158 => ['descriptions' => [
                1 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Kecukupan Kebijakan, Prosedur, dan Limit berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            159 => ['descriptions' => [
                1 => '• telah memiliki kebijakan Manajemen Risiko reputasi;<br>• terdapat kesesuaian antara substansi kebijakan Manajemen Risiko reputasi dengan ketentuan Manajemen Risiko BPR antara lain kebijakan untuk mencegah terjadinya Risiko reputasi, dan peningkatan kualitas pelayanan nasabah; dan<br>• terdapat keselarasan antara kebijakan Manajemen Risiko reputasi dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko reputasi.',
                2 => '• telah memiliki kebijakan Manajemen Risiko reputasi;<br>• terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko reputasi dengan ketentuan Manajemen Risiko BPR antara lain kebijakan untuk mencegah terjadinya Risiko reputasi, dan peningkatan kualitas pelayanan nasabah; dan<br>• terdapat keselarasan antara kebijakan Manajemen Risiko reputasi dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM bisnis dalam menetapkan kebijakan Manajemen Risiko reputasi.',
                3 => '• telah memiliki kebijakan Manajemen Risiko reputasi;<br>• terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko reputasi dengan ketentuan Manajemen Risiko BPR antara lain kebijakan untuk mencegah terjadinya Risiko reputasi, dan peningkatan kualitas pelayanan nasabah; dan<br>• terdapat ketidakselarasan antara kebijakan Manajemen Risiko reputasi dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM bisnis dalam menetapkan kebijakan Manajemen Risiko reputasi, namun tidak menimbulkan dampak yang signifikan.',
                4 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM',
                5 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            160 => ['descriptions' => [
                1 => '• memiliki prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• melaksanakan prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam setiap aktivitas fungsional secara konsisten; dan<br>• melakukan evaluasi dan pengkinian prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan.',
                2 => '• memiliki prosedur Manajemen Risiko reputasi dan penetapan limit Risiko<br>• reputasi yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• melaksanakan prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam setiap aktivitas fungsional secara konsisten; dan<br>• tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan, namun tidak menimbulkan dampak yang signifikan',
                3 => '• memiliki prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• melaksanakan prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam setiap aktivitas fungsional secara konsisten; dan<br>• tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan, dan menimbulkan dampak yang',
                4 => '• memiliki prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggung jawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• tidak melaksanakan prosedur Manajemen Risiko reputasdan penetapan limit Risiko reputasi dalam setiap aktivitas fungsional secara konsisten; dan<br>• tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan dan menimbulkan dampak yang signifikan',
                5 => 'tidak memiliki prosedur Manajemen Risiko reputasi dan penetapan limit Risiko reputasi yang ditetapkan oleh Direksi.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            161 => ['descriptions' => [
                1 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko reputasi;<br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktiv; dan<br>• Terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan',
                2 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko reputasi;<br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• Terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                3 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko reputasi;<br>• Menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• penerapan Manajemen Risiko reputasi tidak dilakukan secara konsisten, namun tidak menimbulkan dampak yang signifikan.',
                4 => '• Memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko reputasi;<br>• Tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• Terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                5 => 'Tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko reputasi.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            162 => ['descriptions' => [
                1 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Rendah',
                2 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Rendah',
                3 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sedang',
                4 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Tinggi',
                5 => 'Parameter Kecukupan Proses dan Sistem Manajemen Risiko berada pada tingkat risiko yang Sangat Tinggi',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            163 => ['descriptions' => [
                1 => '• telah melaksanakan proses Manajemen Risiko reputasi meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko reputasi terhadap seluruh kegiatan usaha BPR termasuk terhadap jumlah keluhan dari nasabah yang diajukan serta terhadap pemberitaan negatif BPR;<br>• penerapan Manajemen Risiko reputasi dilakukan dengan sangat memadai; dan<br>• penerapan Manajemen Risiko reputasi dilakukan secara konsisten.',
                2 => '• telah melaksanakan proses Manajemen Risiko reputasi meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko reputasi terhadap seluruh kegiatan usaha BPR termasuk terhadap jumlah keluhan dari nasabah yang diajukan serta terhadap pemberitaan negatif BPR;<br>• penerapan Manajemen Risiko reputasi dilakukan dengan memadai; dan<br>• penerapan Manajemen Risiko reputasi dilakukan cukup konsisten.',
                3 => '• telah melaksanakan proses Manajemen Risiko reputasi meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko reputasi terhadap seluruh kegiatan usaha BPR termasuk terhadap jumlah keluhan dari nasabah yang diajukan serta terhadap pemberitaan negatif BPR;<br>• penerapan Manajemen Risiko reputasi dilakukan dengan memadai; dan<br>• penerapan Manajemen Risiko reputasi tidak dilakukan secara konsisten, namun tidak menimbulkan dampak yang signifikan.',
                4 => '• telah melaksanakan proses Manajemen Risiko reputasi namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko reputasi terhadap seluruh kegiatan usaha BPR termasuk terhadap jumlah keluhan dari nasabah yang diajukan serta terhadap pemberitaan negatif BPR;<br>• penerapan Manajemen Risiko reputasi tidak memadai; dan<br>• penerapan Manajemen Risiko reputasi tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                5 => 'tidak melaksanakan proses Manajemen Risiko reputasi meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko reputasi terhadap seluruh kegiatan usaha BPR.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            164 => ['descriptions' => [
                1 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko reputasi;<br>• Data pada sistem informasi Manajemen Risiko telah lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan<br>• Sistem informasi Manajemen Risiko sangat mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                2 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko reputasi;<br>• Data pada sistem informasi Manajemen Risiko cukup lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko mendukung Direksi dalam pengambilan keputusan; dan<br>• Sistem informasi Manajemen Risiko cukup mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                3 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko reputasi;<br>• Data pada sistem informasi Manajemen Risiko kurang lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan
 • Sistem informasi Manajemen Risiko kurang mendukung SKMR atau PE Manajemen Risiko dalam pembuatan laporan kepada Direksi setiap semester.',
                4 => '• Telah memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko reputasi;<br>• Data pada sistem informasi Manajemen Risiko tidak lengkap, akurat, kini, dan utuh;<br>• Sistem informasi Manajemen Risiko tidak sepenuhnya mendukung Direksi dalam pengambilan keputusan; dan<br>• Sistem informasi Manajemen Risiko tidak mendukung SKMR atau PEMR dalam pembuatan laporan kepada Direksi setiap semester.',
                5 => 'Tidak memiliki sistem informasi Manajemen Risiko yang mencerminkan Risiko reputasi.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            165 => ['descriptions' => [
                1 => 'Sangat Rendah',
                2 => 'Rendah',
                3 => 'Sedang',
                4 => 'Tinggi',
                5 => 'Sangat Tinggi',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            166 => ['descriptions' => [
                1 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko reputasi, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAmeliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko reputasi dengan mempertimbang kan ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti.',
                2 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko reputasi, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko reputasi dengan mempertimbang kan ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                3 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko reputasi, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko reputasi dengan mempertimbang kan ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                4 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko reputasi, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko reputasi; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi tidak ditindaklanjuti.',
                5 => 'SKAI atau PEAI tidak melaksanakan audit intern terhadap penerapan Manajemen Risiko reputasi.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
            167 => ['descriptions' => [
                1 => '• seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko reputasi;<br>• terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi.',
                2 => '• seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko reputasi dan tidak berdampak signifikan;<br>• terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi.',
                3 => '• seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi telah melaksanakan fungsi pengendalian intern namun tidak sepenuhnya memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko reputasi dan berdampak signifikan;<br>• terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi; dan<br>• SKAI atau PEAI terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi.',
                4 => '• tidak seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi telah melaksanakan fungsi pengendalian intern dengan memerhatikan kebijakan Manajemen Risiko, prosedur Manajemen Risiko, serta penetapan limit Risiko reputasi;<br>• tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi;<br>• SKMR atau PEMR terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi; dan<br>• SKAI atau PEAterpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi.',
                5 => '• seluruh jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi tidak melaksanakan fungsi pengendalian intern;<br>• tidak terdapat kejelasan wewenang dan tanggung jawab dari masing- masing jenjang organisasi BPR yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi;<br>• SKMR atau PEMRtidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi; dan<br>• SKAI atau PEAI tidak terpisah dari unit yang berkaitan dengan aktivitas yang memiliki eksposur Risiko reputasi.',
            ], 'catatan' => 'Penilaian parameter risiko reputasi inheren'],
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

    public function exporttxtrisikoreputasikpmr()
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0502|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

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

        $filename = "TKSBPRKS-0502-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
