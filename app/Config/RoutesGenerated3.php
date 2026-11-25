<?php
// AUTO-GENERATED oleh `php spark routes:generate`. Jangan diedit manual.

$routes->group('', ['filter' => ['login', 'role:admin']], static function ($routes) {
    // Admin
    $routes->match(['get', 'post'], '(?i)admin', 'Admin::index');
    $routes->match(['get', 'post'], '(?i)admin/(detail|index)', 'Admin::$1');
    $routes->match(['get', 'post'], '(?i)admin/(detail|updateKodebpr)/(:segment)', 'Admin::$1/$2');
});

$routes->group('', ['filter' => 'login'], static function ($routes) {
    // Budayakepatuhan
    $routes->match(['get', 'post'], '(?i)budayakepatuhan', 'Budayakepatuhan::index');
    $routes->match(['get', 'post'], '(?i)budayakepatuhan/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtbudayakepatuhan|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Budayakepatuhan::$1');

    // Danasosial
    $routes->match(['get', 'post'], '(?i)danasosial', 'Danasosial::index');
    $routes->match(['get', 'post'], '(?i)danasosial/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtdanasosial|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Danasosial::$1');

    // Faktor
    $routes->match(['get', 'post'], '(?i)faktor', 'Faktor::index');
    $routes->match(['get', 'post'], '(?i)faktor/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markCommentsAsRead|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor::$1');
    $routes->match(['get', 'post'], '(?i)faktor/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor::$1/$2');

    // Faktor10
    $routes->match(['get', 'post'], '(?i)faktor10', 'Faktor10::index');
    $routes->match(['get', 'post'], '(?i)faktor10/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor10|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor10::$1');
    $routes->match(['get', 'post'], '(?i)faktor10/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor10::$1/$2');

    // Faktor11
    $routes->match(['get', 'post'], '(?i)faktor11', 'Faktor11::index');
    $routes->match(['get', 'post'], '(?i)faktor11/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor11|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor11::$1');
    $routes->match(['get', 'post'], '(?i)faktor11/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor11::$1/$2');

    // Faktor12
    $routes->match(['get', 'post'], '(?i)faktor12', 'Faktor12::index');
    $routes->match(['get', 'post'], '(?i)faktor12/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor12|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor12::$1');
    $routes->match(['get', 'post'], '(?i)faktor12/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor12::$1/$2');

    // Faktor2
    $routes->match(['get', 'post'], '(?i)faktor2', 'Faktor2::index');
    $routes->match(['get', 'post'], '(?i)faktor2/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor2|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovedekom|unapprovekom)', 'Faktor2::$1');
    $routes->match(['get', 'post'], '(?i)faktor2/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor2::$1/$2');

    // Faktor3
    $routes->match(['get', 'post'], '(?i)faktor3', 'Faktor3::index');
    $routes->match(['get', 'post'], '(?i)faktor3/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor3|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor3::$1');
    $routes->match(['get', 'post'], '(?i)faktor3/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor3::$1/$2');

    // Faktor4
    $routes->match(['get', 'post'], '(?i)faktor4', 'Faktor4::index');
    $routes->match(['get', 'post'], '(?i)faktor4/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor4|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor4::$1');
    $routes->match(['get', 'post'], '(?i)faktor4/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor4::$1/$2');

    // Faktor5
    $routes->match(['get', 'post'], '(?i)faktor5', 'Faktor5::index');
    $routes->match(['get', 'post'], '(?i)faktor5/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor5|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor5::$1');
    $routes->match(['get', 'post'], '(?i)faktor5/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor5::$1/$2');

    // Faktor6
    $routes->match(['get', 'post'], '(?i)faktor6', 'Faktor6::index');
    $routes->match(['get', 'post'], '(?i)faktor6/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|checkPrevPeriodeData|exporttxtfaktor6|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor6::$1');
    $routes->match(['get', 'post'], '(?i)faktor6/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor6::$1/$2');

    // Faktor7
    $routes->match(['get', 'post'], '(?i)faktor7', 'Faktor7::index');
    $routes->match(['get', 'post'], '(?i)faktor7/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor7|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor7::$1');
    $routes->match(['get', 'post'], '(?i)faktor7/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor7::$1/$2');

    // Faktor8
    $routes->match(['get', 'post'], '(?i)faktor8', 'Faktor8::index');
    $routes->match(['get', 'post'], '(?i)faktor8/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor8|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor8::$1');
    $routes->match(['get', 'post'], '(?i)faktor8/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor8::$1/$2');

    // Faktor9
    $routes->match(['get', 'post'], '(?i)faktor9', 'Faktor9::index');
    $routes->match(['get', 'post'], '(?i)faktor9/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor9|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|index|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor9::$1');
    $routes->match(['get', 'post'], '(?i)faktor9/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor9::$1/$2');

    // Formasisdm
    $routes->match(['get', 'post'], '(?i)formasisdm', 'Formasisdm::index');
    $routes->match(['get', 'post'], '(?i)formasisdm/(approveKomRow|approveSemuaDir|approveSemuaKom|checkPrevPeriode|confirmImportExcel|exporttxtformasisdm|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaDir|unapproveSemuaKom)', 'Formasisdm::$1');

    // Fraudinternal
    $routes->match(['get', 'post'], '(?i)fraudinternal', 'Fraudinternal::index');
    $routes->match(['get', 'post'], '(?i)fraudinternal/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtfraudinternal|getKomentarByFaktorId|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahfraudajax|tambahketerangan|unapproveSemuaDirut|unapproveSemuaKom|unapproveSemuaKomAjax)', 'Fraudinternal::$1');

    // Home
    $routes->match(['get', 'post'], '(?i)home', 'Home::index');
    $routes->match(['get', 'post'], '(?i)home/(index)', 'Home::$1');

    // infobpr
    $routes->match(['get', 'post'], '(?i)infobpr', 'infobpr::index');
    $routes->match(['get', 'post'], '(?i)infobpr/(index|simpaninfo)', 'infobpr::$1');

    // Kehadirandekom
    $routes->match(['get', 'post'], '(?i)kehadirandekom', 'Kehadirandekom::index');
    $routes->match(['get', 'post'], '(?i)kehadirandekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtkehadirandekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Kehadirandekom::$1');

    // Keluargadirdekompshm
    $routes->match(['get', 'post'], '(?i)keluargadirdekompshm', 'Keluargadirdekompshm::index');
    $routes->match(['get', 'post'], '(?i)keluargadirdekompshm/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtkeluargadirdekompshm|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Keluargadirdekompshm::$1');

    // Kepatuhankpmr
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr', 'Kepatuhankpmr::index');
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikokepatuhankpmr|exporttxtrisikokepatuhankpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Kepatuhankpmr::$1');
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Kepatuhankpmr::$1/$2');

    // Kesimpulanaudit
    $routes->match(['get', 'post'], '(?i)kesimpulanaudit', 'Kesimpulanaudit::index');
    $routes->match(['get', 'post'], '(?i)kesimpulanaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtkesimpulanaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Kesimpulanaudit::$1');

    // Ketentuanbpr
    $routes->match(['get', 'post'], '(?i)ketentuanbpr', 'Ketentuanbpr::index');
    $routes->match(['get', 'post'], '(?i)ketentuanbpr/(approveDirRow|approveKomRow|approveSemuaDir|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtketentuanbpr|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveDirRow|unapproveKomRow|unapproveSemuaDir|unapproveSemuaKom)', 'Ketentuanbpr::$1');

    // Keuangandirdekompshm
    $routes->match(['get', 'post'], '(?i)keuangandirdekompshm', 'Keuangandirdekompshm::index');
    $routes->match(['get', 'post'], '(?i)keuangandirdekompshm/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtkeuangandirdekompshm|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Keuangandirdekompshm::$1');

    // Komitmenotoritas
    $routes->match(['get', 'post'], '(?i)komitmenotoritas', 'Komitmenotoritas::index');
    $routes->match(['get', 'post'], '(?i)komitmenotoritas/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtkomitmenotoritas|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Komitmenotoritas::$1');

    // LaporanPdfAudit
    $routes->match(['get', 'post'], '(?i)laporanpdfaudit/(LaporanAuditInternal)', 'LaporanPdfAudit::$1');

    // LaporanPdfGcg
    $routes->match(['get', 'post'], '(?i)laporanpdfgcg/(LaporanDirekturYangMembawahiFungsiKepatuhan)', 'LaporanPdfGcg::$1');

    // Laporanpdfsaf
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf', 'Laporanpdfsaf::index');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(getApprovalStatus|hapusItem|index|simpan|simpanItem|status)', 'Laporanpdfsaf::$1');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(index|pdf|pdfFraud)/(:segment)', 'Laporanpdfsaf::$1/$2');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(statusRingkas)/(:segment)/(:segment)', 'Laporanpdfsaf::$1/$2/$3');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(:segment)', 'Laporanpdfsaf::index/$1');

    // Likuiditasinheren
    $routes->match(['get', 'post'], '(?i)likuiditasinheren', 'Likuiditasinheren::index');
    $routes->match(['get', 'post'], '(?i)likuiditasinheren/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikolikuiditas|exporttxtrisikolikuiditastks|getAllUnreadCounts|getApprovalStatus|getFactorsData|getUnreadCommentCountForFactor|index|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|simpanKesimpulan13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Likuiditasinheren::$1');
    $routes->match(['get', 'post'], '(?i)likuiditasinheren/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Likuiditasinheren::$1/$2');

    // Likuiditaskpmr
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr', 'Likuiditaskpmr::index');
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr/(accdir2|approveSemua|exporttxtrisikolikuiditaskpmr|exporttxtrisikolikuiditaskpmrtks|getAllUnreadCounts|getFactorsData|index|markUserCommentsAsRead|resetPeringkat13Auto|simpanKesimpulan13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|unapproveSemua|unapprovedir2)', 'Likuiditaskpmr::$1');
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr/(getKomentarByFaktorId|setNullKolom)/(:segment)', 'Likuiditaskpmr::$1/$2');

    // Lingkupaudit
    $routes->match(['get', 'post'], '(?i)lingkupaudit', 'Lingkupaudit::index');
    $routes->match(['get', 'post'], '(?i)lingkupaudit/(approveDirRow|approveKomRow|approveSemuaDir|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtlingkupaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveDirRow|unapproveKomRow|unapproveSemuaDir|unapproveSemuaKom)', 'Lingkupaudit::$1');

    // masalahhukum
    $routes->match(['get', 'post'], '(?i)masalahhukum', 'masalahhukum::index');
    $routes->match(['get', 'post'], '(?i)masalahhukum/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtmasalahhukum|getKomentarByFaktorId|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindak|tambahketerangan|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'masalahhukum::$1');
    $routes->match(['get', 'post'], '(?i)masalahhukum/(setNullKolomTindak)/(:segment)', 'masalahhukum::$1/$2');

    // Paketkebijakandirdekom
    $routes->match(['get', 'post'], '(?i)paketkebijakandirdekom', 'Paketkebijakandirdekom::index');
    $routes->match(['get', 'post'], '(?i)paketkebijakandirdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtpaketkebijakandirdekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|setNullKolomTindakAjax|tambahketerangan|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Paketkebijakandirdekom::$1');

    // Pdfgcg
    $routes->match(['get', 'post'], '(?i)pdfgcg/(generateFullReport|pdfBentur|pdfDanasosial|pdfDirdekomlain|pdfFraud|pdfHadir|pdfKeluarga|pdfKeuangan|pdfMasalah|pdfPaket|pdfPenjelasanUmum|pdfRapat|pdfRasio|pdfSahamdirdekom|pdfStrukturKomite|pdfTgjwbDekom|pdfTgjwbDir|pdfTgjwbKomite|pdfUsahadirdekom)', 'Pdfgcg::$1');

    // Pdfself
    $routes->match(['get', 'post'], '(?i)pdfself/(generateFullReport|generatePdfToBuffer|getAllData|getAllData10|getAllData11|getAllData12|getAllData2|getAllData3|getAllData4|getAllData5|getAllData6|getAllData7|getAllData8|getAllData9|getAllDatafaktor|pdfFaktor|uploadPdf)', 'Pdfself::$1');
    $routes->match(['get', 'post'], '(?i)pdfself/(downloadPdf)/(:segment)', 'Pdfself::$1/$2');

    // Pemantauanaudit
    $routes->match(['get', 'post'], '(?i)pemantauanaudit', 'Pemantauanaudit::index');
    $routes->match(['get', 'post'], '(?i)pemantauanaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtpemantauanaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Pemantauanaudit::$1');

    // Penerapansafst
    $routes->match(['get', 'post'], '(?i)penerapansafst', 'Penerapansafst::index');
    $routes->match(['get', 'post'], '(?i)penerapansafst/(approveKomRow|approveSemuaKom|batalNodataAjax|confirmImportExcel|exporttxtpenerapansafst|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|simpanSection|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Penerapansafst::$1');

    // Penjelasanumum
    $routes->match(['get', 'post'], '(?i)penjelasanumum', 'Penjelasanumum::index');
    $routes->match(['get', 'post'], '(?i)penjelasanumum/(approveSemuaDirut|approveSemuaKom|checkPrevYear|exporttxtpenjelasanumum|getKomentar|getUnreadCounts|index|markKomentarAsRead|syncFromPrevYear|tambahKomentar|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Penjelasanumum::$1');

    // Penyimpangankepatuhan
    $routes->match(['get', 'post'], '(?i)penyimpangankepatuhan', 'Penyimpangankepatuhan::index');
    $routes->match(['get', 'post'], '(?i)penyimpangankepatuhan/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtpenyimpangankepatuhan|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Penyimpangankepatuhan::$1');

    // Periode
    $routes->match(['get', 'post'], '(?i)periode', 'Periode::index');
    $routes->match(['get', 'post'], '(?i)periode/(handlePeriode|index|select)', 'Periode::$1');
    $routes->match(['get', 'post'], '(?i)periode/(switch)/(:segment)', 'Periode::$1/$2');

    // Periodeprofilresiko
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko', 'Periodeprofilresiko::index');
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko/(handlePeriode|index|select)', 'Periodeprofilresiko::$1');
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko/(switch)/(:segment)', 'Periodeprofilresiko::$1/$2');

    // Periodesaf
    $routes->match(['get', 'post'], '(?i)periodesaf', 'Periodesaf::index');
    $routes->match(['get', 'post'], '(?i)periodesaf/(handlePeriode|index|select)', 'Periodesaf::$1');
    $routes->match(['get', 'post'], '(?i)periodesaf/(switch)/(:segment)', 'Periodesaf::$1/$2');

    // Periodesemesteran
    $routes->match(['get', 'post'], '(?i)periodesemesteran', 'Periodesemesteran::index');
    $routes->match(['get', 'post'], '(?i)periodesemesteran/(handlePeriode|index|select)', 'Periodesemesteran::$1');
    $routes->match(['get', 'post'], '(?i)periodesemesteran/(switch)/(:segment)', 'Periodesemesteran::$1/$2');

    // Periodetahunan
    $routes->match(['get', 'post'], '(?i)periodetahunan', 'Periodetahunan::index');
    $routes->match(['get', 'post'], '(?i)periodetahunan/(handlePeriode|index|select)', 'Periodetahunan::$1');
    $routes->match(['get', 'post'], '(?i)periodetahunan/(switch)/(:segment)', 'Periodetahunan::$1/$2');

    // Periodetks
    $routes->match(['get', 'post'], '(?i)periodetks', 'Periodetks::index');
    $routes->match(['get', 'post'], '(?i)periodetks/(handlePeriode|index|select)', 'Periodetks::$1');
    $routes->match(['get', 'post'], '(?i)periodetks/(switch)/(:segment)', 'Periodetks::$1/$2');

    // Rapat
    $routes->match(['get', 'post'], '(?i)rapat', 'Rapat::index');
    $routes->match(['get', 'post'], '(?i)rapat/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtrapat|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Rapat::$1');

    // rasiogaji
    $routes->match(['get', 'post'], '(?i)rasiogaji', 'rasiogaji::index');
    $routes->match(['get', 'post'], '(?i)rasiogaji/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtrasiogaji|getKomentarByFaktorId|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|setNullKolomTindakAjax|tambahketerangan|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'rasiogaji::$1');

    // Rekomendasiaudit
    $routes->match(['get', 'post'], '(?i)rekomendasiaudit', 'Rekomendasiaudit::index');
    $routes->match(['get', 'post'], '(?i)rekomendasiaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtrekomendasiaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Rekomendasiaudit::$1');

    // Reputasiinheren
    $routes->match(['get', 'post'], '(?i)reputasiinheren', 'Reputasiinheren::index');
    $routes->match(['get', 'post'], '(?i)reputasiinheren/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikoreputasiinheren|exporttxtrisikoreputasiinherentks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Reputasiinheren::$1');
    $routes->match(['get', 'post'], '(?i)reputasiinheren/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Reputasiinheren::$1/$2');

    // Reputasikpmr
    $routes->match(['get', 'post'], '(?i)reputasikpmr', 'Reputasikpmr::index');
    $routes->match(['get', 'post'], '(?i)reputasikpmr/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikoreputasikpmr|exporttxtrisikoreputasikpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getPeringkat13|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Reputasikpmr::$1');
    $routes->match(['get', 'post'], '(?i)reputasikpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Reputasikpmr::$1/$2');

    // Risikokepatuhan
    $routes->match(['get', 'post'], '(?i)risikokepatuhan', 'Risikokepatuhan::index');
    $routes->match(['get', 'post'], '(?i)risikokepatuhan/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikokepatuhan|exporttxtrisikokepatuhantks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Risikokepatuhan::$1');
    $routes->match(['get', 'post'], '(?i)risikokepatuhan/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Risikokepatuhan::$1/$2');

    // Risikokredit
    $routes->match(['get', 'post'], '(?i)risikokredit', 'Risikokredit::index');
    $routes->match(['get', 'post'], '(?i)risikokredit/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exportAllPDFToZipView|exportPDF|exportPDFGabungan|exportPDFGabunganKepatuhan|exportPDFGabunganLikuiditas|exportPDFGabunganOperasional|exportPDFGabunganReputasi|exportPDFGabunganStratejik|exporttxtrisikokredit|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|index|insertRasioKualitasAsetToKertasKerja|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKalkulator|simpanKalkulatorKualitasAset|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|tambahNilairasio|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2|viewExportPDFOperasional|viewPDFPreview)', 'Risikokredit::$1');
    $routes->match(['get', 'post'], '(?i)risikokredit/(approve|getKomentarByFaktorId|unapprove)/(:segment)', 'Risikokredit::$1/$2');

    // Risikokredit
    $routes->match(['get', 'post'], '(?i)risikokredit', 'Risikokredit::index');
    $routes->match(['get', 'post'], '(?i)risikokredit/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exportAllPDFToZipView|exportPDF|exportPDFGabungan|exportPDFGabunganKepatuhan|exportPDFGabunganLikuiditas|exportPDFGabunganOperasional|exportPDFGabunganReputasi|exportPDFGabunganStratejik|exporttxtrisikokredit|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|index|insertRasioKualitasAsetToKertasKerja|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKalkulator|simpanKalkulatorKualitasAset|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|tambahNilairasio|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2|viewExportPDFOperasional|viewPDFPreview)', 'Risikokredit::$1');
    $routes->match(['get', 'post'], '(?i)risikokredit/(approve|getKomentarByFaktorId|unapprove)/(:segment)', 'Risikokredit::$1/$2');

    // Risikokredit
    $routes->match(['get', 'post'], '(?i)risikokredit', 'Risikokredit::index');
    $routes->match(['get', 'post'], '(?i)risikokredit/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exportAllPDFToZipView|exportPDF|exportPDFGabungan|exportPDFGabunganKepatuhan|exportPDFGabunganLikuiditas|exportPDFGabunganOperasional|exportPDFGabunganReputasi|exportPDFGabunganStratejik|exporttxtrisikokredit|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|index|insertRasioKualitasAsetToKertasKerja|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKalkulator|simpanKalkulatorKualitasAset|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|tambahNilairasio|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2|viewExportPDFOperasional|viewPDFPreview)', 'Risikokredit::$1');
    $routes->match(['get', 'post'], '(?i)risikokredit/(approve|getKomentarByFaktorId|unapprove)/(:segment)', 'Risikokredit::$1/$2');

    // Risikokreditkpmr
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr', 'Risikokreditkpmr::index');
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikokreditkpmr|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Risikokreditkpmr::$1');
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Risikokreditkpmr::$1/$2');

    // Risikooperasional
    $routes->match(['get', 'post'], '(?i)risikooperasional', 'Risikooperasional::index');
    $routes->match(['get', 'post'], '(?i)risikooperasional/(accdir2|approveSemua|checkPrevPeriode|exportPDFGabunganOperasional|exporttxtrisikooperasional|exporttxtrisikooperasionaltks|getAllUnreadCounts|getFactorsData|index|resetPeringkat13Auto|simpanKesimpulan13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|unapproveSemua|unapprovedir2)', 'Risikooperasional::$1');
    $routes->match(['get', 'post'], '(?i)risikooperasional/(getKomentarByFaktorId|setNullKolom)/(:segment)', 'Risikooperasional::$1/$2');

    // Risikooperasionalkpmr
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr', 'Risikooperasionalkpmr::index');
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikooperasionalkpmr|exporttxtrisikooperasionalkpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Risikooperasionalkpmr::$1');
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Risikooperasionalkpmr::$1/$2');

    // sahamdirdekom
    $routes->match(['get', 'post'], '(?i)sahamdirdekom', 'sahamdirdekom::index');
    $routes->match(['get', 'post'], '(?i)sahamdirdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'sahamdirdekom::$1');

    // Shmdirdekomlain
    $routes->match(['get', 'post'], '(?i)shmdirdekomlain', 'Shmdirdekomlain::index');
    $routes->match(['get', 'post'], '(?i)shmdirdekomlain/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtshmdirdekomlain|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Shmdirdekomlain::$1');

    // Shmusahadirdekom
    $routes->match(['get', 'post'], '(?i)shmusahadirdekom', 'Shmusahadirdekom::index');
    $routes->match(['get', 'post'], '(?i)shmusahadirdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketeranganAjax|exporttxtshmusahadirdekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketeranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Shmusahadirdekom::$1');

    // Showaudit
    $routes->match(['get', 'post'], '(?i)showaudit', 'Showaudit::index');
    $routes->match(['get', 'post'], '(?i)showaudit/(index|uploadPdf)', 'Showaudit::$1');
    $routes->match(['get', 'post'], '(?i)showaudit/(downloadPdf)/(:segment)', 'Showaudit::$1/$2');

    // Showdirymf
    $routes->match(['get', 'post'], '(?i)showdirymf', 'Showdirymf::index');
    $routes->match(['get', 'post'], '(?i)showdirymf/(LaporanDirekturYMFKepatuhan|cetakPengesahan|exporttxtfaktor10|index|updatettd|uploadPdf)', 'Showdirymf::$1');
    $routes->match(['get', 'post'], '(?i)showdirymf/(downloadPdf)/(:segment)', 'Showdirymf::$1/$2');

    // ShowFaktor
    $routes->match(['get', 'post'], '(?i)showfaktor', 'ShowFaktor::index');
    $routes->match(['get', 'post'], '(?i)showfaktor/(autosave|cetakPengesahan|exporttxtshowfaktor|index|update|updatecover|updatettd)', 'ShowFaktor::$1');

    // Showprofilresiko
    $routes->match(['get', 'post'], '(?i)showprofilresiko', 'Showprofilresiko::index');
    $routes->match(['get', 'post'], '(?i)showprofilresiko/(ajaxAutoSaveField|ajaxSaveKesimpulan|ajaxUpdateTtd|exportAllPDFToZipView|exportAllPDFToZipv2|exportAllToZip|exportLaporanProfilRisikoJSON|exportLembarPernyataanJSON|exportPDFGabunganKepatuhanJSON|exportPDFGabunganKreditJSON|exportPDFGabunganLikuiditasJSON|exportPDFGabunganOperasionalJSON|exporttxtkepatuhan|exporttxtlapprofrisk|exporttxtlikuiditas|exporttxtoperasional|exporttxtreputasi|exporttxtriskinheren|exporttxtstratejik|generateLaporanProfilRisikoPdfBytes|generateLembarPernyataanPdfBytes|index|resetNtotalrisk|update|updateNtotalrisk|updatecover|updatettd|viewLaporanProfilRisiko|viewLembarPernyataan)', 'Showprofilresiko::$1');
    $routes->match(['get', 'post'], '(?i)showprofilresiko/(getRiskKeterangan)/(:segment)/(:segment)', 'Showprofilresiko::$1/$2/$3');

    // Showsaf
    $routes->match(['get', 'post'], '(?i)showsaf', 'Showsaf::index');
    $routes->match(['get', 'post'], '(?i)showsaf/(exportZip|index)', 'Showsaf::$1');

    // Showsemesteran
    $routes->match(['get', 'post'], '(?i)showsemesteran', 'Showsemesteran::index');
    $routes->match(['get', 'post'], '(?i)showsemesteran/(downloadZip|exportAllToZip|index|startExport|update|updatettd|zipProgress)', 'Showsemesteran::$1');
    $routes->match(['get', 'post'], '(?i)showsemesteran/(downloadZip|zipProgress)/(:segment)', 'Showsemesteran::$1/$2');

    // Showtahunan
    $routes->match(['get', 'post'], '(?i)showtahunan', 'Showtahunan::index');
    $routes->match(['get', 'post'], '(?i)showtahunan/(exportAllToZip|index|update|updatettd)', 'Showtahunan::$1');

    // Showtks
    $routes->match(['get', 'post'], '(?i)showtks', 'Showtks::index');
    $routes->match(['get', 'post'], '(?i)showtks/(LaporanPDFTKS|ajaxAutoSaveField|ajaxSaveKesimpulan|ajaxUpdateTtd|exporttxtkepatuhan|exporttxtlapprofrisk|exporttxtlikuiditas|exporttxtoperasional|exporttxtreputasi|exporttxtriskinheren|exporttxtstratejik|exporttxttks|index|resetNtotalrisk|update|updateNtotalrisk|updatecover|updatettd)', 'Showtks::$1');
    $routes->match(['get', 'post'], '(?i)showtks/(sanitizeText)/(:segment)', 'Showtks::$1/$2');

    // ShowTransparansi
    $routes->match(['get', 'post'], '(?i)showtransparansi', 'ShowTransparansi::index');
    $routes->match(['get', 'post'], '(?i)showtransparansi/(exportAllToZip|index|update|updatecover|updatettd)', 'ShowTransparansi::$1');

    // Sosialisasipelatihan
    $routes->match(['get', 'post'], '(?i)sosialisasipelatihan', 'Sosialisasipelatihan::index');
    $routes->match(['get', 'post'], '(?i)sosialisasipelatihan/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtsosialisasipelatihan|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Sosialisasipelatihan::$1');

    // Stratejikinheren
    $routes->match(['get', 'post'], '(?i)stratejikinheren', 'Stratejikinheren::index');
    $routes->match(['get', 'post'], '(?i)stratejikinheren/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikostratejikinheren|exporttxtrisikostratejikinherentks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Stratejikinheren::$1');
    $routes->match(['get', 'post'], '(?i)stratejikinheren/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Stratejikinheren::$1/$2');

    // Stratejikkpmr
    $routes->match(['get', 'post'], '(?i)stratejikkpmr', 'Stratejikkpmr::index');
    $routes->match(['get', 'post'], '(?i)stratejikkpmr/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikostratejikkpmr|exporttxtrisikostratejikkpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|index|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Stratejikkpmr::$1');
    $routes->match(['get', 'post'], '(?i)stratejikkpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Stratejikkpmr::$1/$2');

    // strukturkomite
    $routes->match(['get', 'post'], '(?i)strukturkomite', 'strukturkomite::index');
    $routes->match(['get', 'post'], '(?i)strukturkomite/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editKeteranganAjax|exporttxtstrukturkomite|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'strukturkomite::$1');

    // Temuanaudit
    $routes->match(['get', 'post'], '(?i)temuanaudit', 'Temuanaudit::index');
    $routes->match(['get', 'post'], '(?i)temuanaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxttemuanaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|index|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Temuanaudit::$1');

    // Tgjwbdekom
    $routes->match(['get', 'post'], '(?i)tgjwbdekom', 'Tgjwbdekom::index');
    $routes->match(['get', 'post'], '(?i)tgjwbdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editKeteranganAjax|exporttxttgjwbdekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNullKolomPenjelaslanjutAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Tgjwbdekom::$1');

    // Tgjwbdir
    $routes->match(['get', 'post'], '(?i)tgjwbdir', 'Tgjwbdir::index');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|cekKomentarBaru|editketerangan|exporttxttgjwbdir|getDataAjax|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|tambahketerangan|tambahtgjwbdir|ubah|unapproveSemuaDirut|unapproveSemuaKom)', 'Tgjwbdir::$1');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(approve|hapus|setNullKolomPenjelaslanjut|setNullKolomTindak|unapprove)/(:segment)', 'Tgjwbdir::$1/$2');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(hapus)/(:segment)/(:segment)', 'Tgjwbdir::$1/$2/$3');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(hapus)/(:segment)/(:segment)/(:segment)', 'Tgjwbdir::$1/$2/$3/$4');

    // tgjwbkomite
    $routes->match(['get', 'post'], '(?i)tgjwbkomite', 'tgjwbkomite::index');
    $routes->match(['get', 'post'], '(?i)tgjwbkomite/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editKeteranganAjax|exporttxttgjwbkomite|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'tgjwbkomite::$1');

    // Tkspermodalan
    $routes->match(['get', 'post'], '(?i)tkspermodalan', 'Tkspermodalan::index');
    $routes->match(['get', 'post'], '(?i)tkspermodalan/(approve|autoSave|checkMisTksData|checkSyncStatus|getKomentar|getUnreadCount|index|markCommentsAsRead|syncFromPrevious|tambahKomentar|tarikDataMisTks|unapprove)', 'Tkspermodalan::$1');

    // Tksprofilrisiko
    $routes->match(['get', 'post'], '(?i)tksprofilrisiko', 'Tksprofilrisiko::index');
    $routes->match(['get', 'post'], '(?i)tksprofilrisiko/(approve|getKomentar|getUnreadCount|index|markCommentsAsRead|simpanAnalisa|simpanDeskripsi|simpanPeringkatBeforeManual|simpanPeringkatManual|syncWithProfilRisiko|tambahKomentar|unapprove|unsyncWithProfilRisiko)', 'Tksprofilrisiko::$1');

    // Tksrentabilitas
    $routes->match(['get', 'post'], '(?i)tksrentabilitas', 'Tksrentabilitas::index');
    $routes->match(['get', 'post'], '(?i)tksrentabilitas/(approve|autoSave|checkMisTksData|checkPrevPeriode|getKomentar|getUnreadCount|index|markCommentsAsRead|syncFromPrevPeriode|syncWithSipro|tambahKomentar|tarikDataMisTks|unapprove|unsyncWithSipro)', 'Tksrentabilitas::$1');

    // Tkstatakelola
    $routes->match(['get', 'post'], '(?i)tkstatakelola', 'Tkstatakelola::index');
    $routes->match(['get', 'post'], '(?i)tkstatakelola/(approve|getKomentar|getUnreadCount|index|markCommentsAsRead|simpanAnalisa|simpanDeskripsi|simpanManual|simpanPeringkatManual|simpanPeringkatSebelumnya|syncWithTatakelola|tambahKomentar|unapprove|unsyncWithTatakelola)', 'Tkstatakelola::$1');

    // Transaksikepentingan
    $routes->match(['get', 'post'], '(?i)transaksikepentingan', 'Transaksikepentingan::index');
    $routes->match(['get', 'post'], '(?i)transaksikepentingan/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editKeteranganAjax|exporttxttransaksikepentingan|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|index|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Transaksikepentingan::$1');

    // transparansitahunan
    $routes->match(['get', 'post'], '(?i)transparansitahunan', 'transparansitahunan::index');
    $routes->match(['get', 'post'], '(?i)transparansitahunan/(excel|index|tambah|ubah)', 'transparansitahunan::$1');
    $routes->match(['get', 'post'], '(?i)transparansitahunan/(hapus)/(:segment)', 'transparansitahunan::$1/$2');

    // User
    $routes->match(['get', 'post'], '(?i)user', 'User::index');
    $routes->match(['get', 'post'], '(?i)user/(index)', 'User::$1');
    $routes->match(['get', 'post'], '(?i)user/(updateEmail)/(:segment)', 'User::$1/$2');
});

