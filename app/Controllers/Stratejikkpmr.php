<?php

namespace App\Controllers;

use App\Libraries\ProfilRisikoController;
use App\Models\M_stratejikkpmr;

/**
 * Penilaian Risiko Stratejik KPMR (tabel stratejikkpmr, faktor 182–200).
 * Logika halaman di App\Libraries\ProfilRisikoController; ekspor TXT dipakai Showprofilresiko & Showsemesteran.
 */
class Stratejikkpmr extends ProfilRisikoController
{
    protected const TABLE = 'stratejikkpmr';
    protected const COL_NILAI = 'penilaiankredit';
    protected const SUBKATEGORI = 'STRATEJIKKPMR';
    protected const ROUTE = 'Stratejikkpmr';
    protected const TITLE = 'Risiko Stratejik KPMR';
    protected const RISK = 'Risiko Stratejik';
    protected const ICON = 'fa-chess-board';
    protected const TOTAL_ID = 199;
    protected const PREV_ID = 200;
    protected const NAV = ['Stratejikinheren' => 'Inheren', 'Stratejikkpmr' => 'KPMR'];

    private const KODE_MAP = [
        182 => '6310', 183 => '6311', 184 => '6312', 185 => '6313', 186 => '6314', 187 => '6315', 188 => '6316',
        189 => '6320', 190 => '6321', 191 => '6322', 192 => '6323', 193 => '6330', 194 => '6331', 195 => '6332',
        196 => '6340', 197 => '6341', 198 => '6342', 199 => '6350',
    ];

    protected $nilaiModel;

    public function __construct()
    {
        parent::__construct();
        $this->nilaiModel = new M_stratejikkpmr();
    }

    protected function structure(): array
    {
        return [
            ['id' => 182, 'title' => 'Pengawasan Direksi dan Dewan Komisaris', 'children' => [
                ['id' => 183, 'title' => 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko stratejik yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'],
                ['id' => 184, 'title' => 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko stratejik secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?'],
                ['id' => 185, 'title' => 'Apakah Direksi telah menyusun kebijakan manajemen risiko stratejik, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?'],
                ['id' => 186, 'title' => 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko stratejik, dan melakukan komunikasi kebijakan manajemen risiko stratejik terhadap seluruh jenjang organisasi BPR?'],
                ['id' => 187, 'title' => 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi stratejik dan fungsi manajemen risiko stratejik?'],
                ['id' => 188, 'title' => 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko stratejik?'],
            ]],
            ['id' => 189, 'title' => 'Kecukupan Kebijakan, Prosedur, dan Limit', 'children' => [
                ['id' => 190, 'title' => 'Apakah BPR telah memiliki kebijakan manajemen risiko stratejik yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?'],
                ['id' => 191, 'title' => 'Apakah BPR:
● Memiliki prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik yang ditetapkan oleh Direksi;
● Melaksanakan prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik secara konsisten untuk seluruh aktivitas; dan
● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik secara berkala?'],
                ['id' => 192, 'title' => 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko stratejik sesuai dengan ketentuan?'],
            ]],
            ['id' => 193, 'title' => 'Kecukupan Proses dan Sistem Manajemen Informasi', 'children' => [
                ['id' => 194, 'title' => 'Apakah BPR telah melaksanakan proses manajemen risiko stratejik yang melekat pada kegiatan usaha BPR?'],
                ['id' => 195, 'title' => 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko stratejik serta telah dilaporkan kepada Direksi secara berkala?'],
            ]],
            ['id' => 196, 'title' => 'Sistem Pengendalian Internal yang Menyeluruh', 'children' => [
                ['id' => 197, 'title' => 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko stratejik, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?'],
                ['id' => 198, 'title' => 'Apakah sistem pengendalian intern terhadap risiko stratejik telah dilaksanakan oleh seluruh jenjang organisasi BPR?'],
            ]],
        ];
    }

    protected function penilaianConfig(): array
    {
        // catatan lama generik ("Peniliaian …") → tidak ditampilkan
        return array_map(static fn($c) => array_diff_key($c, ['catatan' => 1]), [
            182 => ['descriptions' => [
                1 => 'Sangat Baik',
                2 => 'Baik',
                3 => 'Cukup',
                4 => 'Kurang Baik',
                5 => 'Buruk',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            183 => ['descriptions' => [
                1 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik;<br>• evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• evaluasi yang diberikan relevan dengan kebutuhan penyesuaian kebijakan Manajemen Risiko stratejik.',
                2 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik; dan<br>• evaluasi dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                3 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik;<br>• Dewan Komisaris telah melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik; dan<br>• evaluasi tidak dilakukan oleh Dewan Komisaris secara berkala paling sedikit satu kali dalam satu tahun atau sewaktu-waktu dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan.',
                4 => '• Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; dan<br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik.',
                5 => '• Dewan Komisaris tidak memberikan persetujuan terhadap kebijakan Manajemen Risiko stratejik; dan<br>• Dewan Komisaris tidak melakukan evaluasi terhadap kebijakan Manajemen Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            184 => ['descriptions' => [
                1 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan manajemen stratejik oleh Direksi;<br>• evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan;<br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan; dan<br>• evaluasi yang diberikan relevan dengan pelaksanaan kebijakan Manajemen Risiko stratejik dalam rangka mendukung perbaikan kinerja BPR.',
                2 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi;<br>• evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris telah memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                3 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi;<br>• evaluasi oleh Dewan Komisaris dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                4 => '• Dewan Komisaris telah melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi;<br>• evaluasi oleh Dewan Komisaris tidak dilakukan secara berkala setiap semester atau lebih berdasarkan laporan yang disampaikan Direksi dalam hal terdapat perubahan yang memengaruhi kegiatan usaha BPR secara signifikan; dan<br>• Dewan Komisaris tidak memastikan tindak lanjut hasil evaluasi dalam setiap periode laporan.',
                5 => 'Dewan Komisaris tidak melakukan evaluasi terhadap pelaksanaan kebijakan Manajemen Risiko stratejik oleh Direksi.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            185 => ['descriptions' => [
                1 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik;<br>• menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan;<br>• Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris; dan<br>• kebijakan Manajemen Risiko stratejik yang dijalankan terbukti memitigasi terjadinya Risiko stratejik.',
                2 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik;<br>• menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; dan<br>• Direksi melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                3 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik;<br>• menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; dan<br>• Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                4 => '• Direksi telah menyusun kebijakan Manajemen Risiko stratejik;<br>• tidak menjalankan kegiatan usaha berdasarkan kebijakan Manajemen Risiko stratejik yang telah ditetapkan; dan<br>• Direksi tidak melakukan pengkinian terhadap kebijakan Manajemen Risiko stratejik dalam hal terdapat perubahan ketentuan peraturan perundang-undangan, perubahan bisnis, dan hasil evaluasi kebijakan Manajemen Risiko oleh Dewan Komisaris.',
                5 => 'Direksi tidak menyusun kebijakan Manajemen Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            186 => ['descriptions' => [
                1 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik;<br>• Direksi mengomunikasikan kebijakan Manajemen Risiko stratejik; dan<br>• seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan.',
                2 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik;<br>• Direksi mengomunikasikan kebijakan Manajemen Risiko stratejik; dan<br>• tidak seluruh jenjang organisasi BPR mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan namun tidak menimbulkan dampak yang signifikan.',
                3 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik;<br>• Direksi mengomunikasikan kebijakan Manajemen Risiko stratejik; dan<br>• tidak seluruh jenjang organisasiBPR mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan dan menimbulkan dampak yang signifikan.',
                4 => '• Direksi mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik;<br>• Direksi tidak mengomunikasikan kebijakan Manajemen Risiko stratejik; dan<br>• seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan.',
                5 => '• Direksi tidak mengambil tindakan yang diperlukan untuk memitigasi Risiko saat menjalankan kebijakan Manajemen Risiko stratejik;<br>• Direksi tidak mengomunikasikan kebijakan Manajemen Risiko stratejik; dan<br>• seluruh jenjang organisasi BPR tidak mampu memahami kebijakan Manajemen Risiko stratejik yang diterapkan.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            187 => ['descriptions' => [
                1 => '• memiliki unit kerja yang menangani fungsi stratejik;<br>• unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• memiliki SKMR atau PEMR yang mampu melaksanakan fungsinya untuk memitigasi risiko Stratejik',
                2 => '• memiliki unit kerja yang menangani fungsi stratejik namun tidak lengkap;<br>• unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• memiliki SKMR atau PEMR yang mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.',
                3 => '• memiliki unit kerja yang menangani fungsi stratejik;<br>• unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• memiliki SKMR atau PEMR yang tidak mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.',
                4 => '• memiliki unit kerja yang menangani fungsi stratejik namun tidak lengkap;<br>• unit kerja yang menangani fungsi stratejik telah melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• memiliki SKMR atau PEMR yang tidak mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.',
                5 => '• memiliki unit kerja yang menangani fungsi stratejik namun tidak lengkap;<br>• unit kerja yang menangani fungsi stratejik tidak melaksanakan tugas dan wewenangnya sesuai dengan pedoman yang ditetapkan; dan<br>• memiliki SKMR atau PEMR yang tidak mampu melaksanakan fungsinya untuk memitigasi Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            188 => ['descriptions' => [
                1 => '• terdapat kesesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan;<br>• terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik sesuai dengan tugas dan tanggung jawab.',
                2 => '• terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• terdapat upaya peningkatan kompetensi SDM secara konsisten; dan<br>• tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik sesuai dengan tugas dan tanggung jawab.',
                3 => '• terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan namun tidak memberikan dampak yang signifikan;<br>• terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik sesuai dengan tugas dan tanggung jawab',
                4 => '• terdapat ketidaksesuaian kualifikasi SDM dengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• terdapat upaya peningkatan kompetensi SDM namun tidak secara konsisten; dan<br>• tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik tidak sesuai dengan tugas dan tanggung jawab namun tidak memberikan dampak yang signifikan.',
                5 => '• terdapat ketidaksesuaian kualifikasi SDMdengan jabatan dan bidang pekerjaan dan memberikan dampak yang signifikan;<br>• tidak terdapat upaya peningkatan kompetensi SDM; dan<br>• tingkat pemenuhan standar kinerja SDM pada unit kerja yang menjalankan fungsi stratejik tidak sesuai dengan tugas dan tanggung jawab dan memberikan dampak yang signifikan',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            189 => ['descriptions' => [
                1 => 'Sangat Baik',
                2 => 'Baik',
                3 => 'Cukup',
                4 => 'Kurang Baik',
                5 => 'Buruk',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            190 => ['descriptions' => [
                1 => '• telah memiliki kebijakan Manajemen Risiko stratejik;<br>• terdapat kesesuaian antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan<br>• terdapat keselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik.',
                2 => '• telah memiliki kebijakan Manajemen Risiko stratejik;<br>• terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan<br>• terdapat keselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik.',
                3 => '• telah memiliki kebijakan Manajemen Risiko stratejik;<br>• terdapat ketidaksesuaian yang tidak signifikan antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan<br>• terdapat ketidakselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik namun tidak menimbulkan dampak yang signifikan.',
                4 => '• telah memiliki kebijakan Manajemen Risiko stratejik;<br>• terdapat ketidaksesuaian yang signifikan antara substansi kebijakan Manajemen Risiko stratejik dengan ketentuan Manajemen Risiko BPR termasuk target pencapaian tahunan BPR yang tertuang dalam rencana bisnis BPR; dan<br>• terdapat ketidakselarasan antara kebijakan Manajemen Risiko stratejik dengan visi, misi, skala usaha, dan kompleksitas bisnis, serta kecukupan SDM dalam menetapkan kebijakan Manajemen Risiko stratejik dan menimbulkan dampak yang signifikan.',
                5 => 'tidak memiliki kebijakan Manajemen Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            191 => ['descriptions' => [
                1 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan<br>• melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan.',
                2 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan<br>• tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan, namun tidak menimbulkan dampak yang signifikan.',
                3 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan<br>• tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan dan menimbulkan dampak yang signifikan.',
                4 => '• memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi paling sedikit meliputi jenjang delegasi wewenang dan pertanggungjawaban yang jelas serta terdokumentasi dengan baik sehingga memudahkan keperluan jejak audit untuk keperluan pengendalian intern;<br>• tidak melaksanakan prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam setiap aktivitas fungsional secara konsisten; dan<br>• tidak melakukan evaluasi dan pengkinian prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik dalam hal terdapat perubahan bisnis yang signifikan dan/atau ketentuan peraturan perundang-undangan.',
                5 => 'tidak memiliki prosedur Manajemen Risiko stratejik dan penetapan limit Risiko stratejik yang ditetapkan oleh Direksi.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            192 => ['descriptions' => [
                1 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambil alihan;<br>• BPR tidak dalam proses pemindahan kantor pusat BPR; dan<br>• BPR tidak dalam proses penerbitan produk dan/atau pelaksanaan aktivitas baru.',
                2 => '• BPR tidak dalam proses penggabungan, peleburan, dan pengambil alihan;<br>• Terdapat prosespemindahan kantor pusat BPR; dan/atau<br>• BPR dalam proses pengembangan produk dan/atau aktivitas baru (yang hanya memerlukan pelaporan ke OJK).',
                3 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) bekerja sama dengan pihak ketiga (tidak ada biaya investasi - capital expenditure BPR); dan/atau<br>• BPR melaksanakan penggabungan, peleburan, dan pengambilalihan pada jangka waktu sangat lama sebelum periode penilaian.<br>• Proses pengambilalihan tidak berpengaruh terhadap strategi bisnis dan budaya perusahaan',
                4 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) yang memerlukan biaya investasi - capital expenditure BPR; dan/atau<br>• BPR melaksanakan penggabungan, peleburan, dan pengambilalihan pada jangka waktu lama sebelum periode penilaian.<br>• Proses pengambilalihan berpengaruh terhadap strategi bisnis dan budaya perusahaan',
                5 => '• Terdapat proses pemindahan kantor pusat BPR;<br>• BPR menerbitkan produk dan/atau melaksanakan aktivitas baru (memerlukan persetujuan OJK) yang memerlukan biaya investasi - capital expenditure BPR; dan/atau<br>• BPR melaksanakan penggabungan, peleburan, dan pengambilalihan pada jangka waktu tidak lama sebelum periode penilaian.<br>• Proses pengambilalihan berpengaruh terhadap strategi bisnis dan budaya perusahaan',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            193 => ['descriptions' => [
                1 => 'Sangat Baik',
                2 => 'Baik',
                3 => 'Cukup',
                4 => 'Kurang Baik',
                5 => 'Buruk',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            194 => ['descriptions' => [
                1 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik;<br>• menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• terdapat kesesuaian antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                2 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik;<br>• menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• terdapat ketidaksesuaian yang tidak signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                3 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik;<br>• menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                4 => '• memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik;<br>• tidak menerapkan kebijakan dan prosedur dalam hal terdapat penerbitan produk atau pelaksanaan aktivitas baru; dan<br>• terdapat ketidaksesuaian yang signifikan antara kebijakan dan prosedur produk dan/atau aktivitas baru dengan ketentuan.',
                5 => 'tidak memiliki kebijakan dan prosedur mengenai penerbitan produk dan/atau aktivitas baru yang memiliki eksposur Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            195 => ['descriptions' => [
                1 => '• telah melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR;<br>• penerapan Manajemen Risiko stratejik dilakukan dengan sangat memadai; dan<br>• penerapan Manajemen Risiko stratejik dilakukan secara konsisten.',
                2 => '• telah melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR;<br>• penerapan Manajemen Risiko stratejik dilakukan dengan memadai; dan<br>• penerapan Manajemen Risiko stratejik dilakukan cukup konsisten.',
                3 => '• telah melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR;<br>• penerapan Manajemen Risiko stratejik dilakukan dengan memadai; dan<br>• penerapan Manajemen Risiko stratejik tidak dilakukan secara konsisten namun tidak menimbulkan dampak yang signifikan.',
                4 => '• telah melaksanakan proses Manajemen Risiko stratejik namun tidak secara keseluruhan meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik terhadap seluruh kegiatan usaha BPR termasuk realisasi dari target pencapaian BPR;<br>• penerapan Manajemen Risiko stratejik tidak memadai; dan<br>• penerapan Manajemen Risiko stratejik tidak dilakukan secara konsisten sehingga menimbulkan dampak yang signifikan.',
                5 => 'tidak melaksanakan proses Manajemen Risiko stratejik meliputi identifikasi, pengukuran, pemantauan, dan pengendalian Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            196 => ['descriptions' => [
                1 => 'Sangat Baik',
                2 => 'Baik',
                3 => 'Cukup',
                4 => 'Kurang Baik',
                5 => 'Buruk',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            197 => ['descriptions' => [
                1 => '• SKAI atau PEAItelah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangk an ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi telah ditindaklanjuti',
                2 => '• SKAI atau PEAI telah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangk an ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti namun tidak menimbulkan dampak yang signifikan.',
                3 => '• SKAI atau PEAItelah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI meliputi pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangkan ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi tidak sepenuhnya ditindaklanjuti dan menimbulkan dampak yang signifikan.',
                4 => '• SKAI atau PEAtelah melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik, memberikan rekomendasi, dan melaporkan hasil audit intern kepada Direktur Utama;<br>• audit intern telah dilaksanakan oleh SKAI atau PEAI namun tidak sesuai dengan cakupan pelaksanaan kebijakan dan prosedur Manajemen Risiko stratejik dengan mempertimbangk an ketentuan serta kondisi BPR; dan<br>• hasil temuan audit intern yang dijadikan rekomendasi tidak ditindaklanjuti.',
                5 => 'SKAI atau PEAItidak melaksanakan audit intern terhadap penerapan Manajemen Risiko stratejik.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
            198 => ['descriptions' => [
                1 => 'Tidak terjadihuman error pada BPR.',
                2 => '• Terjadi human error pada BPR; namun<br>• Tidak berdampak finansial bagi BPR.',
                3 => '• Terjadi human error pada BPR; dan<br>• mengurangi keuntungan namun tidak menyebabkan BPR membukukan laba negatif.',
                4 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun namun masih sesuai ketentuan KPMM',
                5 => '• Terjadi human error pada BPR; dan<br>• BPR membukukan laba negatif yang menyebabkan rasio permodalan menurun di bawah ketentuan KPMM.',
            ], 'catatan' => 'Penilaian parameter risiko stratejik KPMR'],
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

    public function exporttxtrisikostratejikkpmr()
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

        $output = "H01|{$kodejenis}|{$sandibpr}|{$exportDate}|TKSBPRKS|0602|{$modalinti}|{$kategori}|{$nomorsurat}|{$jeniskoreksi}\r\n";

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

        $filename = "TKSBPRKS-0602-{$jenispelaporan}-S-{$titleDate}-{$sandibpr}-01.txt";

        return service('response')
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($output);
    }
}
