<?php
// AUTO-GENERATED oleh `php spark routes:generate`. Jangan diedit manual.

$routes->group('', ['filter' => 'guard:admin,nav'], static function ($routes) {
    // Admin (halaman)
    $routes->match(['get', 'post'], '(?i)admin', 'Admin::index');
    $routes->match(['get', 'post'], '(?i)admin/(detail|index)', 'Admin::$1');
    $routes->match(['get', 'post'], '(?i)admin/(detail)/(:segment)', 'Admin::$1/$2');
});

$routes->group('', ['filter' => 'guard:admin'], static function ($routes) {
    // Admin (function)
    $routes->match(['get', 'post'], '(?i)admin/(updateKodebpr)/(:segment)', 'Admin::$1/$2');
});

$routes->group('', ['filter' => 'guard:nav'], static function ($routes) {
    // Budayakepatuhan (halaman)
    $routes->match(['get', 'post'], '(?i)budayakepatuhan', 'Budayakepatuhan::index');
    $routes->match(['get', 'post'], '(?i)budayakepatuhan/(index)', 'Budayakepatuhan::$1');

    // Danasosial (halaman)
    $routes->match(['get', 'post'], '(?i)danasosial', 'Danasosial::index');
    $routes->match(['get', 'post'], '(?i)danasosial/(index)', 'Danasosial::$1');

    // Faktor (halaman)
    $routes->match(['get', 'post'], '(?i)faktor', 'Faktor::index');
    $routes->match(['get', 'post'], '(?i)faktor/(index)', 'Faktor::$1');

    // Faktor10 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor10', 'Faktor10::index');
    $routes->match(['get', 'post'], '(?i)faktor10/(index)', 'Faktor10::$1');

    // Faktor11 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor11', 'Faktor11::index');
    $routes->match(['get', 'post'], '(?i)faktor11/(index)', 'Faktor11::$1');

    // Faktor12 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor12', 'Faktor12::index');
    $routes->match(['get', 'post'], '(?i)faktor12/(index)', 'Faktor12::$1');

    // Faktor2 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor2', 'Faktor2::index');
    $routes->match(['get', 'post'], '(?i)faktor2/(index)', 'Faktor2::$1');

    // Faktor3 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor3', 'Faktor3::index');
    $routes->match(['get', 'post'], '(?i)faktor3/(index)', 'Faktor3::$1');

    // Faktor4 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor4', 'Faktor4::index');
    $routes->match(['get', 'post'], '(?i)faktor4/(index)', 'Faktor4::$1');

    // Faktor5 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor5', 'Faktor5::index');
    $routes->match(['get', 'post'], '(?i)faktor5/(index)', 'Faktor5::$1');

    // Faktor6 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor6', 'Faktor6::index');
    $routes->match(['get', 'post'], '(?i)faktor6/(index)', 'Faktor6::$1');

    // Faktor7 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor7', 'Faktor7::index');
    $routes->match(['get', 'post'], '(?i)faktor7/(index)', 'Faktor7::$1');

    // Faktor8 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor8', 'Faktor8::index');
    $routes->match(['get', 'post'], '(?i)faktor8/(index)', 'Faktor8::$1');

    // Faktor9 (halaman)
    $routes->match(['get', 'post'], '(?i)faktor9', 'Faktor9::index');
    $routes->match(['get', 'post'], '(?i)faktor9/(index)', 'Faktor9::$1');

    // Formasisdm (halaman)
    $routes->match(['get', 'post'], '(?i)formasisdm', 'Formasisdm::index');
    $routes->match(['get', 'post'], '(?i)formasisdm/(index)', 'Formasisdm::$1');

    // Fraudinternal (halaman)
    $routes->match(['get', 'post'], '(?i)fraudinternal', 'Fraudinternal::index');
    $routes->match(['get', 'post'], '(?i)fraudinternal/(index)', 'Fraudinternal::$1');

    // infobpr (halaman)
    $routes->match(['get', 'post'], '(?i)infobpr', 'infobpr::index');
    $routes->match(['get', 'post'], '(?i)infobpr/(index)', 'infobpr::$1');

    // Kehadirandekom (halaman)
    $routes->match(['get', 'post'], '(?i)kehadirandekom', 'Kehadirandekom::index');
    $routes->match(['get', 'post'], '(?i)kehadirandekom/(index)', 'Kehadirandekom::$1');

    // Keluargadirdekompshm (halaman)
    $routes->match(['get', 'post'], '(?i)keluargadirdekompshm', 'Keluargadirdekompshm::index');
    $routes->match(['get', 'post'], '(?i)keluargadirdekompshm/(index)', 'Keluargadirdekompshm::$1');

    // Kepatuhankpmr (halaman)
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr', 'Kepatuhankpmr::index');
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr/(index)', 'Kepatuhankpmr::$1');

    // Kesimpulanaudit (halaman)
    $routes->match(['get', 'post'], '(?i)kesimpulanaudit', 'Kesimpulanaudit::index');
    $routes->match(['get', 'post'], '(?i)kesimpulanaudit/(index)', 'Kesimpulanaudit::$1');

    // Ketentuanbpr (halaman)
    $routes->match(['get', 'post'], '(?i)ketentuanbpr', 'Ketentuanbpr::index');
    $routes->match(['get', 'post'], '(?i)ketentuanbpr/(index)', 'Ketentuanbpr::$1');

    // Keuangandirdekompshm (halaman)
    $routes->match(['get', 'post'], '(?i)keuangandirdekompshm', 'Keuangandirdekompshm::index');
    $routes->match(['get', 'post'], '(?i)keuangandirdekompshm/(index)', 'Keuangandirdekompshm::$1');

    // Komitmenotoritas (halaman)
    $routes->match(['get', 'post'], '(?i)komitmenotoritas', 'Komitmenotoritas::index');
    $routes->match(['get', 'post'], '(?i)komitmenotoritas/(index)', 'Komitmenotoritas::$1');

    // Laporanpdfsaf (halaman)
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf', 'Laporanpdfsaf::index');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(index)', 'Laporanpdfsaf::$1');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(index)/(:segment)', 'Laporanpdfsaf::$1/$2');

    // Likuiditasinheren (halaman)
    $routes->match(['get', 'post'], '(?i)likuiditasinheren', 'Likuiditasinheren::index');
    $routes->match(['get', 'post'], '(?i)likuiditasinheren/(index)', 'Likuiditasinheren::$1');

    // Likuiditaskpmr (halaman)
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr', 'Likuiditaskpmr::index');
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr/(index)', 'Likuiditaskpmr::$1');

    // Lingkupaudit (halaman)
    $routes->match(['get', 'post'], '(?i)lingkupaudit', 'Lingkupaudit::index');
    $routes->match(['get', 'post'], '(?i)lingkupaudit/(index)', 'Lingkupaudit::$1');

    // masalahhukum (halaman)
    $routes->match(['get', 'post'], '(?i)masalahhukum', 'masalahhukum::index');
    $routes->match(['get', 'post'], '(?i)masalahhukum/(index)', 'masalahhukum::$1');

    // Paketkebijakandirdekom (halaman)
    $routes->match(['get', 'post'], '(?i)paketkebijakandirdekom', 'Paketkebijakandirdekom::index');
    $routes->match(['get', 'post'], '(?i)paketkebijakandirdekom/(index)', 'Paketkebijakandirdekom::$1');

    // Pemantauanaudit (halaman)
    $routes->match(['get', 'post'], '(?i)pemantauanaudit', 'Pemantauanaudit::index');
    $routes->match(['get', 'post'], '(?i)pemantauanaudit/(index)', 'Pemantauanaudit::$1');

    // Penerapansafst (halaman)
    $routes->match(['get', 'post'], '(?i)penerapansafst', 'Penerapansafst::index');
    $routes->match(['get', 'post'], '(?i)penerapansafst/(index)', 'Penerapansafst::$1');

    // Penjelasanumum (halaman)
    $routes->match(['get', 'post'], '(?i)penjelasanumum', 'Penjelasanumum::index');
    $routes->match(['get', 'post'], '(?i)penjelasanumum/(index)', 'Penjelasanumum::$1');

    // Penyimpangankepatuhan (halaman)
    $routes->match(['get', 'post'], '(?i)penyimpangankepatuhan', 'Penyimpangankepatuhan::index');
    $routes->match(['get', 'post'], '(?i)penyimpangankepatuhan/(index)', 'Penyimpangankepatuhan::$1');

    // Periode (halaman)
    $routes->match(['get', 'post'], '(?i)periode', 'Periode::index');
    $routes->match(['get', 'post'], '(?i)periode/(index)', 'Periode::$1');

    // Periodeprofilresiko (halaman)
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko', 'Periodeprofilresiko::index');
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko/(index)', 'Periodeprofilresiko::$1');

    // Periodesaf (halaman)
    $routes->match(['get', 'post'], '(?i)periodesaf', 'Periodesaf::index');
    $routes->match(['get', 'post'], '(?i)periodesaf/(index)', 'Periodesaf::$1');

    // Periodesemesteran (halaman)
    $routes->match(['get', 'post'], '(?i)periodesemesteran', 'Periodesemesteran::index');
    $routes->match(['get', 'post'], '(?i)periodesemesteran/(index)', 'Periodesemesteran::$1');

    // Periodetahunan (halaman)
    $routes->match(['get', 'post'], '(?i)periodetahunan', 'Periodetahunan::index');
    $routes->match(['get', 'post'], '(?i)periodetahunan/(index)', 'Periodetahunan::$1');

    // Periodetks (halaman)
    $routes->match(['get', 'post'], '(?i)periodetks', 'Periodetks::index');
    $routes->match(['get', 'post'], '(?i)periodetks/(index)', 'Periodetks::$1');

    // Rapat (halaman)
    $routes->match(['get', 'post'], '(?i)rapat', 'Rapat::index');
    $routes->match(['get', 'post'], '(?i)rapat/(index)', 'Rapat::$1');

    // rasiogaji (halaman)
    $routes->match(['get', 'post'], '(?i)rasiogaji', 'rasiogaji::index');
    $routes->match(['get', 'post'], '(?i)rasiogaji/(index)', 'rasiogaji::$1');

    // Rekomendasiaudit (halaman)
    $routes->match(['get', 'post'], '(?i)rekomendasiaudit', 'Rekomendasiaudit::index');
    $routes->match(['get', 'post'], '(?i)rekomendasiaudit/(index)', 'Rekomendasiaudit::$1');

    // Reputasiinheren (halaman)
    $routes->match(['get', 'post'], '(?i)reputasiinheren', 'Reputasiinheren::index');
    $routes->match(['get', 'post'], '(?i)reputasiinheren/(index)', 'Reputasiinheren::$1');

    // Reputasikpmr (halaman)
    $routes->match(['get', 'post'], '(?i)reputasikpmr', 'Reputasikpmr::index');
    $routes->match(['get', 'post'], '(?i)reputasikpmr/(index)', 'Reputasikpmr::$1');

    // Risikokepatuhan (halaman)
    $routes->match(['get', 'post'], '(?i)risikokepatuhan', 'Risikokepatuhan::index');
    $routes->match(['get', 'post'], '(?i)risikokepatuhan/(index)', 'Risikokepatuhan::$1');

    // Risikokredit (halaman)
    $routes->match(['get', 'post'], '(?i)risikokredit', 'Risikokredit::index');
    $routes->match(['get', 'post'], '(?i)risikokredit/(index)', 'Risikokredit::$1');

    // Risikokredit (halaman)
    $routes->match(['get', 'post'], '(?i)risikokredit', 'Risikokredit::index');
    $routes->match(['get', 'post'], '(?i)risikokredit/(index)', 'Risikokredit::$1');

    // Risikokredit (halaman)
    $routes->match(['get', 'post'], '(?i)risikokredit', 'Risikokredit::index');
    $routes->match(['get', 'post'], '(?i)risikokredit/(index)', 'Risikokredit::$1');

    // Risikokreditkpmr (halaman)
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr', 'Risikokreditkpmr::index');
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr/(index)', 'Risikokreditkpmr::$1');

    // Risikooperasional (halaman)
    $routes->match(['get', 'post'], '(?i)risikooperasional', 'Risikooperasional::index');
    $routes->match(['get', 'post'], '(?i)risikooperasional/(index)', 'Risikooperasional::$1');

    // Risikooperasionalkpmr (halaman)
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr', 'Risikooperasionalkpmr::index');
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr/(index)', 'Risikooperasionalkpmr::$1');

    // sahamdirdekom (halaman)
    $routes->match(['get', 'post'], '(?i)sahamdirdekom', 'sahamdirdekom::index');
    $routes->match(['get', 'post'], '(?i)sahamdirdekom/(index)', 'sahamdirdekom::$1');

    // Shmdirdekomlain (halaman)
    $routes->match(['get', 'post'], '(?i)shmdirdekomlain', 'Shmdirdekomlain::index');
    $routes->match(['get', 'post'], '(?i)shmdirdekomlain/(index)', 'Shmdirdekomlain::$1');

    // Shmusahadirdekom (halaman)
    $routes->match(['get', 'post'], '(?i)shmusahadirdekom', 'Shmusahadirdekom::index');
    $routes->match(['get', 'post'], '(?i)shmusahadirdekom/(index)', 'Shmusahadirdekom::$1');

    // Showaudit (halaman)
    $routes->match(['get', 'post'], '(?i)showaudit', 'Showaudit::index');
    $routes->match(['get', 'post'], '(?i)showaudit/(index)', 'Showaudit::$1');

    // Showdirymf (halaman)
    $routes->match(['get', 'post'], '(?i)showdirymf', 'Showdirymf::index');
    $routes->match(['get', 'post'], '(?i)showdirymf/(index)', 'Showdirymf::$1');

    // ShowFaktor (halaman)
    $routes->match(['get', 'post'], '(?i)showfaktor', 'ShowFaktor::index');
    $routes->match(['get', 'post'], '(?i)showfaktor/(index)', 'ShowFaktor::$1');

    // Showprofilresiko (halaman)
    $routes->match(['get', 'post'], '(?i)showprofilresiko', 'Showprofilresiko::index');
    $routes->match(['get', 'post'], '(?i)showprofilresiko/(index)', 'Showprofilresiko::$1');

    // Showsaf (halaman)
    $routes->match(['get', 'post'], '(?i)showsaf', 'Showsaf::index');
    $routes->match(['get', 'post'], '(?i)showsaf/(index)', 'Showsaf::$1');

    // Showsemesteran (halaman)
    $routes->match(['get', 'post'], '(?i)showsemesteran', 'Showsemesteran::index');
    $routes->match(['get', 'post'], '(?i)showsemesteran/(index)', 'Showsemesteran::$1');

    // Showtahunan (halaman)
    $routes->match(['get', 'post'], '(?i)showtahunan', 'Showtahunan::index');
    $routes->match(['get', 'post'], '(?i)showtahunan/(index)', 'Showtahunan::$1');

    // Showtks (halaman)
    $routes->match(['get', 'post'], '(?i)showtks', 'Showtks::index');
    $routes->match(['get', 'post'], '(?i)showtks/(index)', 'Showtks::$1');

    // ShowTransparansi (halaman)
    $routes->match(['get', 'post'], '(?i)showtransparansi', 'ShowTransparansi::index');
    $routes->match(['get', 'post'], '(?i)showtransparansi/(index)', 'ShowTransparansi::$1');

    // Sosialisasipelatihan (halaman)
    $routes->match(['get', 'post'], '(?i)sosialisasipelatihan', 'Sosialisasipelatihan::index');
    $routes->match(['get', 'post'], '(?i)sosialisasipelatihan/(index)', 'Sosialisasipelatihan::$1');

    // Stratejikinheren (halaman)
    $routes->match(['get', 'post'], '(?i)stratejikinheren', 'Stratejikinheren::index');
    $routes->match(['get', 'post'], '(?i)stratejikinheren/(index)', 'Stratejikinheren::$1');

    // Stratejikkpmr (halaman)
    $routes->match(['get', 'post'], '(?i)stratejikkpmr', 'Stratejikkpmr::index');
    $routes->match(['get', 'post'], '(?i)stratejikkpmr/(index)', 'Stratejikkpmr::$1');

    // strukturkomite (halaman)
    $routes->match(['get', 'post'], '(?i)strukturkomite', 'strukturkomite::index');
    $routes->match(['get', 'post'], '(?i)strukturkomite/(index)', 'strukturkomite::$1');

    // Temuanaudit (halaman)
    $routes->match(['get', 'post'], '(?i)temuanaudit', 'Temuanaudit::index');
    $routes->match(['get', 'post'], '(?i)temuanaudit/(index)', 'Temuanaudit::$1');

    // Tgjwbdekom (halaman)
    $routes->match(['get', 'post'], '(?i)tgjwbdekom', 'Tgjwbdekom::index');
    $routes->match(['get', 'post'], '(?i)tgjwbdekom/(index)', 'Tgjwbdekom::$1');

    // Tgjwbdir (halaman)
    $routes->match(['get', 'post'], '(?i)tgjwbdir', 'Tgjwbdir::index');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(index)', 'Tgjwbdir::$1');

    // tgjwbkomite (halaman)
    $routes->match(['get', 'post'], '(?i)tgjwbkomite', 'tgjwbkomite::index');
    $routes->match(['get', 'post'], '(?i)tgjwbkomite/(index)', 'tgjwbkomite::$1');

    // Tkspermodalan (halaman)
    $routes->match(['get', 'post'], '(?i)tkspermodalan', 'Tkspermodalan::index');
    $routes->match(['get', 'post'], '(?i)tkspermodalan/(index)', 'Tkspermodalan::$1');

    // Tksprofilrisiko (halaman)
    $routes->match(['get', 'post'], '(?i)tksprofilrisiko', 'Tksprofilrisiko::index');
    $routes->match(['get', 'post'], '(?i)tksprofilrisiko/(index)', 'Tksprofilrisiko::$1');

    // Tksrentabilitas (halaman)
    $routes->match(['get', 'post'], '(?i)tksrentabilitas', 'Tksrentabilitas::index');
    $routes->match(['get', 'post'], '(?i)tksrentabilitas/(index)', 'Tksrentabilitas::$1');

    // Tkstatakelola (halaman)
    $routes->match(['get', 'post'], '(?i)tkstatakelola', 'Tkstatakelola::index');
    $routes->match(['get', 'post'], '(?i)tkstatakelola/(index)', 'Tkstatakelola::$1');

    // Transaksikepentingan (halaman)
    $routes->match(['get', 'post'], '(?i)transaksikepentingan', 'Transaksikepentingan::index');
    $routes->match(['get', 'post'], '(?i)transaksikepentingan/(index)', 'Transaksikepentingan::$1');

    // transparansitahunan (halaman)
    $routes->match(['get', 'post'], '(?i)transparansitahunan', 'transparansitahunan::index');
    $routes->match(['get', 'post'], '(?i)transparansitahunan/(index)', 'transparansitahunan::$1');

    // User (halaman)
    $routes->match(['get', 'post'], '(?i)user', 'User::index');
    $routes->match(['get', 'post'], '(?i)user/(index)', 'User::$1');
});

$routes->group('', ['filter' => 'login'], static function ($routes) {
    // Budayakepatuhan (function)
    $routes->match(['get', 'post'], '(?i)budayakepatuhan/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtbudayakepatuhan|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Budayakepatuhan::$1');

    // Danasosial (function)
    $routes->match(['get', 'post'], '(?i)danasosial/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtdanasosial|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Danasosial::$1');

    // Faktor (function)
    $routes->match(['get', 'post'], '(?i)faktor/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markCommentsAsRead|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor::$1');
    $routes->match(['get', 'post'], '(?i)faktor/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor::$1/$2');

    // Faktor10 (function)
    $routes->match(['get', 'post'], '(?i)faktor10/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor10|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor10::$1');
    $routes->match(['get', 'post'], '(?i)faktor10/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor10::$1/$2');

    // Faktor11 (function)
    $routes->match(['get', 'post'], '(?i)faktor11/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor11|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor11::$1');
    $routes->match(['get', 'post'], '(?i)faktor11/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor11::$1/$2');

    // Faktor12 (function)
    $routes->match(['get', 'post'], '(?i)faktor12/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor12|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor12::$1');
    $routes->match(['get', 'post'], '(?i)faktor12/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor12::$1/$2');

    // Faktor2 (function)
    $routes->match(['get', 'post'], '(?i)faktor2/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor2|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovedekom|unapprovekom)', 'Faktor2::$1');
    $routes->match(['get', 'post'], '(?i)faktor2/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor2::$1/$2');

    // Faktor3 (function)
    $routes->match(['get', 'post'], '(?i)faktor3/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor3|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor3::$1');
    $routes->match(['get', 'post'], '(?i)faktor3/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor3::$1/$2');

    // Faktor4 (function)
    $routes->match(['get', 'post'], '(?i)faktor4/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor4|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor4::$1');
    $routes->match(['get', 'post'], '(?i)faktor4/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor4::$1/$2');

    // Faktor5 (function)
    $routes->match(['get', 'post'], '(?i)faktor5/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor5|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor5::$1');
    $routes->match(['get', 'post'], '(?i)faktor5/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor5::$1/$2');

    // Faktor6 (function)
    $routes->match(['get', 'post'], '(?i)faktor6/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|checkPrevPeriodeData|exporttxtfaktor6|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor6::$1');
    $routes->match(['get', 'post'], '(?i)faktor6/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor6::$1/$2');

    // Faktor7 (function)
    $routes->match(['get', 'post'], '(?i)faktor7/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor7|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor7::$1');
    $routes->match(['get', 'post'], '(?i)faktor7/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor7::$1/$2');

    // Faktor8 (function)
    $routes->match(['get', 'post'], '(?i)faktor8/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor8|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor8::$1');
    $routes->match(['get', 'post'], '(?i)faktor8/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor8::$1/$2');

    // Faktor9 (function)
    $routes->match(['get', 'post'], '(?i)faktor9/(accdekom|accdekom2|accdir2|approve|approveSemua|approveSemuaKom|cekKomentarBaru|checkAccDekomApproval|exporttxtfaktor9|getAllUnreadCounts|getApprovalStatus|getFactorsData|getKomentar|getUnreadCommentCountForFactor|getUnreadCount|markUserCommentsAsRead|saveKomentar|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahkesimpulan|unapproveSemua|unapproveddekom|unapproveddekom2|unapproveddir2|unapprovekom)', 'Faktor9::$1');
    $routes->match(['get', 'post'], '(?i)faktor9/(approve|getKomentarByFaktorId|getNilaiByFaktorId|unapprove)/(:segment)', 'Faktor9::$1/$2');

    // Formasisdm (function)
    $routes->match(['get', 'post'], '(?i)formasisdm/(approveKomRow|approveSemuaDir|approveSemuaKom|checkPrevPeriode|confirmImportExcel|exporttxtformasisdm|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaDir|unapproveSemuaKom)', 'Formasisdm::$1');

    // Fraudinternal (function)
    $routes->match(['get', 'post'], '(?i)fraudinternal/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtfraudinternal|getKomentarByFaktorId|getUnreadCommentCountForFactor|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahfraudajax|tambahketerangan|unapproveSemuaDirut|unapproveSemuaKom|unapproveSemuaKomAjax)', 'Fraudinternal::$1');

    // Home (halaman)
    $routes->match(['get', 'post'], '(?i)home', 'Home::index');
    $routes->match(['get', 'post'], '(?i)home/(index)', 'Home::$1');

    // infobpr (function)
    $routes->match(['get', 'post'], '(?i)infobpr/(simpaninfo)', 'infobpr::$1');

    // Kehadirandekom (function)
    $routes->match(['get', 'post'], '(?i)kehadirandekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtkehadirandekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Kehadirandekom::$1');

    // Keluargadirdekompshm (function)
    $routes->match(['get', 'post'], '(?i)keluargadirdekompshm/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtkeluargadirdekompshm|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Keluargadirdekompshm::$1');

    // Kepatuhankpmr (function)
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikokepatuhankpmr|exporttxtrisikokepatuhankpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Kepatuhankpmr::$1');
    $routes->match(['get', 'post'], '(?i)kepatuhankpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Kepatuhankpmr::$1/$2');

    // Kesimpulanaudit (function)
    $routes->match(['get', 'post'], '(?i)kesimpulanaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtkesimpulanaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Kesimpulanaudit::$1');

    // Ketentuanbpr (function)
    $routes->match(['get', 'post'], '(?i)ketentuanbpr/(approveDirRow|approveKomRow|approveSemuaDir|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtketentuanbpr|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveDirRow|unapproveKomRow|unapproveSemuaDir|unapproveSemuaKom)', 'Ketentuanbpr::$1');

    // Keuangandirdekompshm (function)
    $routes->match(['get', 'post'], '(?i)keuangandirdekompshm/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtkeuangandirdekompshm|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Keuangandirdekompshm::$1');

    // Komitmenotoritas (function)
    $routes->match(['get', 'post'], '(?i)komitmenotoritas/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtkomitmenotoritas|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Komitmenotoritas::$1');

    // LaporanPdfAudit (function)
    $routes->match(['get', 'post'], '(?i)laporanpdfaudit/(LaporanAuditInternal)', 'LaporanPdfAudit::$1');

    // LaporanPdfGcg (function)
    $routes->match(['get', 'post'], '(?i)laporanpdfgcg/(LaporanDirekturYangMembawahiFungsiKepatuhan)', 'LaporanPdfGcg::$1');

    // Laporanpdfsaf (function)
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(getApprovalStatus|hapusItem|simpan|simpanItem|status)', 'Laporanpdfsaf::$1');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(pdf|pdfFraud)/(:segment)', 'Laporanpdfsaf::$1/$2');
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(statusRingkas)/(:segment)/(:segment)', 'Laporanpdfsaf::$1/$2/$3');

    // Likuiditasinheren (function)
    $routes->match(['get', 'post'], '(?i)likuiditasinheren/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikolikuiditas|exporttxtrisikolikuiditastks|getAllUnreadCounts|getApprovalStatus|getFactorsData|getUnreadCommentCountForFactor|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|simpanKesimpulan13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Likuiditasinheren::$1');
    $routes->match(['get', 'post'], '(?i)likuiditasinheren/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Likuiditasinheren::$1/$2');

    // Likuiditaskpmr (function)
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr/(accdir2|approveSemua|exporttxtrisikolikuiditaskpmr|exporttxtrisikolikuiditaskpmrtks|getAllUnreadCounts|getFactorsData|markUserCommentsAsRead|resetPeringkat13Auto|simpanKesimpulan13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|unapproveSemua|unapprovedir2)', 'Likuiditaskpmr::$1');
    $routes->match(['get', 'post'], '(?i)likuiditaskpmr/(getKomentarByFaktorId|setNullKolom)/(:segment)', 'Likuiditaskpmr::$1/$2');

    // Lingkupaudit (function)
    $routes->match(['get', 'post'], '(?i)lingkupaudit/(approveDirRow|approveKomRow|approveSemuaDir|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtlingkupaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveDirRow|unapproveKomRow|unapproveSemuaDir|unapproveSemuaKom)', 'Lingkupaudit::$1');

    // masalahhukum (function)
    $routes->match(['get', 'post'], '(?i)masalahhukum/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtmasalahhukum|getKomentarByFaktorId|getUnreadCommentCountForFactor|markUserCommentsAsRead|setNodataAjax|setNullKolomTindak|tambahketerangan|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'masalahhukum::$1');
    $routes->match(['get', 'post'], '(?i)masalahhukum/(setNullKolomTindak)/(:segment)', 'masalahhukum::$1/$2');

    // Paketkebijakandirdekom (function)
    $routes->match(['get', 'post'], '(?i)paketkebijakandirdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtpaketkebijakandirdekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|markUserCommentsAsRead|setNullKolomTindakAjax|tambahketerangan|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Paketkebijakandirdekom::$1');

    // Pdfgcg (function)
    $routes->match(['get', 'post'], '(?i)pdfgcg/(generateFullReport|pdfBentur|pdfDanasosial|pdfDirdekomlain|pdfFraud|pdfHadir|pdfKeluarga|pdfKeuangan|pdfMasalah|pdfPaket|pdfPenjelasanUmum|pdfRapat|pdfRasio|pdfSahamdirdekom|pdfStrukturKomite|pdfTgjwbDekom|pdfTgjwbDir|pdfTgjwbKomite|pdfUsahadirdekom)', 'Pdfgcg::$1');

    // Pdfself (function)
    $routes->match(['get', 'post'], '(?i)pdfself/(generateFullReport|generatePdfToBuffer|getAllData|getAllData10|getAllData11|getAllData12|getAllData2|getAllData3|getAllData4|getAllData5|getAllData6|getAllData7|getAllData8|getAllData9|getAllDatafaktor|pdfFaktor|uploadPdf)', 'Pdfself::$1');
    $routes->match(['get', 'post'], '(?i)pdfself/(downloadPdf)/(:segment)', 'Pdfself::$1/$2');

    // Pemantauanaudit (function)
    $routes->match(['get', 'post'], '(?i)pemantauanaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtpemantauanaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Pemantauanaudit::$1');

    // Penerapansafst (function)
    $routes->match(['get', 'post'], '(?i)penerapansafst/(approveKomRow|approveSemuaKom|batalNodataAjax|confirmImportExcel|exporttxtpenerapansafst|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|simpanSection|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Penerapansafst::$1');

    // Penjelasanumum (function)
    $routes->match(['get', 'post'], '(?i)penjelasanumum/(approveSemuaDirut|approveSemuaKom|checkPrevYear|exporttxtpenjelasanumum|getKomentar|getUnreadCounts|markKomentarAsRead|syncFromPrevYear|tambahKomentar|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Penjelasanumum::$1');

    // Penyimpangankepatuhan (function)
    $routes->match(['get', 'post'], '(?i)penyimpangankepatuhan/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtpenyimpangankepatuhan|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Penyimpangankepatuhan::$1');

    // Periode (function)
    $routes->match(['get', 'post'], '(?i)periode/(handlePeriode|select)', 'Periode::$1');
    $routes->match(['get', 'post'], '(?i)periode/(switch)/(:segment)', 'Periode::$1/$2');

    // Periodeprofilresiko (function)
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko/(handlePeriode|select)', 'Periodeprofilresiko::$1');
    $routes->match(['get', 'post'], '(?i)periodeprofilresiko/(switch)/(:segment)', 'Periodeprofilresiko::$1/$2');

    // Periodesaf (function)
    $routes->match(['get', 'post'], '(?i)periodesaf/(handlePeriode|select)', 'Periodesaf::$1');
    $routes->match(['get', 'post'], '(?i)periodesaf/(switch)/(:segment)', 'Periodesaf::$1/$2');

    // Periodesemesteran (function)
    $routes->match(['get', 'post'], '(?i)periodesemesteran/(handlePeriode|select)', 'Periodesemesteran::$1');
    $routes->match(['get', 'post'], '(?i)periodesemesteran/(switch)/(:segment)', 'Periodesemesteran::$1/$2');

    // Periodetahunan (function)
    $routes->match(['get', 'post'], '(?i)periodetahunan/(handlePeriode|select)', 'Periodetahunan::$1');
    $routes->match(['get', 'post'], '(?i)periodetahunan/(switch)/(:segment)', 'Periodetahunan::$1/$2');

    // Periodetks (function)
    $routes->match(['get', 'post'], '(?i)periodetks/(handlePeriode|select)', 'Periodetks::$1');
    $routes->match(['get', 'post'], '(?i)periodetks/(switch)/(:segment)', 'Periodetks::$1/$2');

    // Rapat (function)
    $routes->match(['get', 'post'], '(?i)rapat/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtrapat|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Rapat::$1');

    // rasiogaji (function)
    $routes->match(['get', 'post'], '(?i)rasiogaji/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editketerangan|exporttxtrasiogaji|getKomentarByFaktorId|getUnreadCommentCountForFactor|markUserCommentsAsRead|setNullKolomTindakAjax|tambahketerangan|tambahpenjelasAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'rasiogaji::$1');

    // Rekomendasiaudit (function)
    $routes->match(['get', 'post'], '(?i)rekomendasiaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtrekomendasiaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Rekomendasiaudit::$1');

    // Reputasiinheren (function)
    $routes->match(['get', 'post'], '(?i)reputasiinheren/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikoreputasiinheren|exporttxtrisikoreputasiinherentks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Reputasiinheren::$1');
    $routes->match(['get', 'post'], '(?i)reputasiinheren/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Reputasiinheren::$1/$2');

    // Reputasikpmr (function)
    $routes->match(['get', 'post'], '(?i)reputasikpmr/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikoreputasikpmr|exporttxtrisikoreputasikpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getPeringkat13|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Reputasikpmr::$1');
    $routes->match(['get', 'post'], '(?i)reputasikpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Reputasikpmr::$1/$2');

    // Risikokepatuhan (function)
    $routes->match(['get', 'post'], '(?i)risikokepatuhan/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikokepatuhan|exporttxtrisikokepatuhantks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Risikokepatuhan::$1');
    $routes->match(['get', 'post'], '(?i)risikokepatuhan/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Risikokepatuhan::$1/$2');

    // Risikokredit (function)
    $routes->match(['get', 'post'], '(?i)risikokredit/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exportAllPDFToZipView|exportPDF|exportPDFGabungan|exportPDFGabunganKepatuhan|exportPDFGabunganLikuiditas|exportPDFGabunganOperasional|exportPDFGabunganReputasi|exportPDFGabunganStratejik|exporttxtrisikokredit|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|insertRasioKualitasAsetToKertasKerja|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKalkulator|simpanKalkulatorKualitasAset|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|tambahNilairasio|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2|viewExportPDFOperasional|viewPDFPreview)', 'Risikokredit::$1');
    $routes->match(['get', 'post'], '(?i)risikokredit/(approve|getKomentarByFaktorId|unapprove)/(:segment)', 'Risikokredit::$1/$2');

    // Risikokredit (function)
    $routes->match(['get', 'post'], '(?i)risikokredit/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exportAllPDFToZipView|exportPDF|exportPDFGabungan|exportPDFGabunganKepatuhan|exportPDFGabunganLikuiditas|exportPDFGabunganOperasional|exportPDFGabunganReputasi|exportPDFGabunganStratejik|exporttxtrisikokredit|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|insertRasioKualitasAsetToKertasKerja|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKalkulator|simpanKalkulatorKualitasAset|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|tambahNilairasio|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2|viewExportPDFOperasional|viewPDFPreview)', 'Risikokredit::$1');
    $routes->match(['get', 'post'], '(?i)risikokredit/(approve|getKomentarByFaktorId|unapprove)/(:segment)', 'Risikokredit::$1/$2');

    // Risikokredit (function)
    $routes->match(['get', 'post'], '(?i)risikokredit/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exportAllPDFToZipView|exportPDF|exportPDFGabungan|exportPDFGabunganKepatuhan|exportPDFGabunganLikuiditas|exportPDFGabunganOperasional|exportPDFGabunganReputasi|exportPDFGabunganStratejik|exporttxtrisikokredit|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|insertRasioKualitasAsetToKertasKerja|insertRasioToKertasKerja|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKalkulator|simpanKalkulatorKualitasAset|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|tambahNilairasio|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2|viewExportPDFOperasional|viewPDFPreview)', 'Risikokredit::$1');
    $routes->match(['get', 'post'], '(?i)risikokredit/(approve|getKomentarByFaktorId|unapprove)/(:segment)', 'Risikokredit::$1/$2');

    // Risikokreditkpmr (function)
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikokreditkpmr|getAllUnreadCounts|getFactorsData|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Risikokreditkpmr::$1');
    $routes->match(['get', 'post'], '(?i)risikokreditkpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Risikokreditkpmr::$1/$2');

    // Risikooperasional (function)
    $routes->match(['get', 'post'], '(?i)risikooperasional/(accdir2|approveSemua|checkPrevPeriode|exportPDFGabunganOperasional|exporttxtrisikooperasional|exporttxtrisikooperasionaltks|getAllUnreadCounts|getFactorsData|resetPeringkat13Auto|simpanKesimpulan13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|unapproveSemua|unapprovedir2)', 'Risikooperasional::$1');
    $routes->match(['get', 'post'], '(?i)risikooperasional/(getKomentarByFaktorId|setNullKolom)/(:segment)', 'Risikooperasional::$1/$2');

    // Risikooperasionalkpmr (function)
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr/(accdir2|approve|approveSemua|cekKomentarBaru|checkPrevPeriode|exporttxtrisikooperasionalkpmr|exporttxtrisikooperasionalkpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|syncFromPrevPeriode|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Risikooperasionalkpmr::$1');
    $routes->match(['get', 'post'], '(?i)risikooperasionalkpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Risikooperasionalkpmr::$1/$2');

    // sahamdirdekom (function)
    $routes->match(['get', 'post'], '(?i)sahamdirdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'sahamdirdekom::$1');

    // Shmdirdekomlain (function)
    $routes->match(['get', 'post'], '(?i)shmdirdekomlain/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketerangan|exporttxtshmdirdekomlain|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketerangan|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Shmdirdekomlain::$1');

    // Shmusahadirdekom (function)
    $routes->match(['get', 'post'], '(?i)shmusahadirdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editketeranganAjax|exporttxtshmusahadirdekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahketeranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Shmusahadirdekom::$1');

    // Showaudit (function)
    $routes->match(['get', 'post'], '(?i)showaudit/(uploadPdf)', 'Showaudit::$1');
    $routes->match(['get', 'post'], '(?i)showaudit/(downloadPdf)/(:segment)', 'Showaudit::$1/$2');

    // Showdirymf (function)
    $routes->match(['get', 'post'], '(?i)showdirymf/(LaporanDirekturYMFKepatuhan|cetakPengesahan|exporttxtfaktor10|updatettd|uploadPdf)', 'Showdirymf::$1');
    $routes->match(['get', 'post'], '(?i)showdirymf/(downloadPdf)/(:segment)', 'Showdirymf::$1/$2');

    // ShowFaktor (function)
    $routes->match(['get', 'post'], '(?i)showfaktor/(autosave|cetakPengesahan|exporttxtshowfaktor|update|updatecover|updatettd)', 'ShowFaktor::$1');

    // Showprofilresiko (function)
    $routes->match(['get', 'post'], '(?i)showprofilresiko/(ajaxAutoSaveField|ajaxSaveKesimpulan|ajaxUpdateTtd|exportAllPDFToZipView|exportAllPDFToZipv2|exportAllToZip|exportLaporanProfilRisikoJSON|exportLembarPernyataanJSON|exportPDFGabunganKepatuhanJSON|exportPDFGabunganKreditJSON|exportPDFGabunganLikuiditasJSON|exportPDFGabunganOperasionalJSON|exporttxtkepatuhan|exporttxtlapprofrisk|exporttxtlikuiditas|exporttxtoperasional|exporttxtreputasi|exporttxtriskinheren|exporttxtstratejik|generateLaporanProfilRisikoPdfBytes|generateLembarPernyataanPdfBytes|resetNtotalrisk|update|updateNtotalrisk|updatecover|updatettd|viewLaporanProfilRisiko|viewLembarPernyataan)', 'Showprofilresiko::$1');
    $routes->match(['get', 'post'], '(?i)showprofilresiko/(getRiskKeterangan)/(:segment)/(:segment)', 'Showprofilresiko::$1/$2/$3');

    // Showsaf (function)
    $routes->match(['get', 'post'], '(?i)showsaf/(exportZip)', 'Showsaf::$1');

    // Showsemesteran (function)
    $routes->match(['get', 'post'], '(?i)showsemesteran/(downloadZip|exportAllToZip|startExport|update|updatettd|zipProgress)', 'Showsemesteran::$1');
    $routes->match(['get', 'post'], '(?i)showsemesteran/(downloadZip|zipProgress)/(:segment)', 'Showsemesteran::$1/$2');

    // Showtahunan (function)
    $routes->match(['get', 'post'], '(?i)showtahunan/(exportAllToZip|update|updatettd)', 'Showtahunan::$1');

    // Showtks (function)
    $routes->match(['get', 'post'], '(?i)showtks/(LaporanPDFTKS|ajaxAutoSaveField|ajaxSaveKesimpulan|ajaxUpdateTtd|exporttxtkepatuhan|exporttxtlapprofrisk|exporttxtlikuiditas|exporttxtoperasional|exporttxtreputasi|exporttxtriskinheren|exporttxtstratejik|exporttxttks|resetNtotalrisk|update|updateNtotalrisk|updatecover|updatettd)', 'Showtks::$1');
    $routes->match(['get', 'post'], '(?i)showtks/(sanitizeText)/(:segment)', 'Showtks::$1/$2');

    // ShowTransparansi (function)
    $routes->match(['get', 'post'], '(?i)showtransparansi/(exportAllToZip|update|updatecover|updatettd)', 'ShowTransparansi::$1');

    // Sosialisasipelatihan (function)
    $routes->match(['get', 'post'], '(?i)sosialisasipelatihan/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxtsosialisasipelatihan|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Sosialisasipelatihan::$1');

    // Stratejikinheren (function)
    $routes->match(['get', 'post'], '(?i)stratejikinheren/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikostratejikinheren|exporttxtrisikostratejikinherentks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Stratejikinheren::$1');
    $routes->match(['get', 'post'], '(?i)stratejikinheren/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Stratejikinheren::$1/$2');

    // Stratejikkpmr (function)
    $routes->match(['get', 'post'], '(?i)stratejikkpmr/(accdir2|approve|approveSemua|cekKomentarBaru|exporttxtrisikostratejikkpmr|exporttxtrisikostratejikkpmrtks|getAllUnreadCounts|getFactorsData|getNilai14|getUnreadCommentCountForFactor|markUserCommentsAsRead|recalculateCategoryAverage|resetPeringkat13Auto|saveKomentar|simpanKesimpulan13|simpanNilai13|simpanNilai14|tambahKomentar|tambahNilai|ubah|ubahPeringkat13Manual|ubahkesimpulan|ubahketerangan|unapproveSemua|unapprovedir2)', 'Stratejikkpmr::$1');
    $routes->match(['get', 'post'], '(?i)stratejikkpmr/(approve|getKomentarByFaktorId|getNilaiByFaktorId|setNullKolom|unapprove)/(:segment)', 'Stratejikkpmr::$1/$2');

    // strukturkomite (function)
    $routes->match(['get', 'post'], '(?i)strukturkomite/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editKeteranganAjax|exporttxtstrukturkomite|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'strukturkomite::$1');

    // Temuanaudit (function)
    $routes->match(['get', 'post'], '(?i)temuanaudit/(approveKomRow|approveSemuaKom|batalNodataAjax|checkPrevPeriode|confirmImportExcel|exporttxttemuanaudit|getApprovalStatus|getData|getKomentar|getUnreadCounts|hapus|markKomentarAsRead|previewImportExcel|setNodataAjax|syncFromPrevPeriode|tambah|tambahKomentar|ubah|unapproveKomRow|unapproveSemuaKom)', 'Temuanaudit::$1');

    // Tgjwbdekom (function)
    $routes->match(['get', 'post'], '(?i)tgjwbdekom/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|editKeteranganAjax|exporttxttgjwbdekom|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNullKolomPenjelaslanjutAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Tgjwbdekom::$1');

    // Tgjwbdir (function)
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|cekKomentarBaru|editketerangan|exporttxttgjwbdir|getDataAjax|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|tambahketerangan|tambahtgjwbdir|ubah|unapproveSemuaDirut|unapproveSemuaKom)', 'Tgjwbdir::$1');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(approve|hapus|setNullKolomPenjelaslanjut|setNullKolomTindak|unapprove)/(:segment)', 'Tgjwbdir::$1/$2');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(hapus)/(:segment)/(:segment)', 'Tgjwbdir::$1/$2/$3');
    $routes->match(['get', 'post'], '(?i)tgjwbdir/(hapus)/(:segment)/(:segment)/(:segment)', 'Tgjwbdir::$1/$2/$3/$4');

    // tgjwbkomite (function)
    $routes->match(['get', 'post'], '(?i)tgjwbkomite/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editKeteranganAjax|exporttxttgjwbkomite|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'tgjwbkomite::$1');

    // Tkspermodalan (function)
    $routes->match(['get', 'post'], '(?i)tkspermodalan/(approve|autoSave|checkMisTksData|checkSyncStatus|getKomentar|getUnreadCount|markCommentsAsRead|syncFromPrevious|tambahKomentar|tarikDataMisTks|unapprove)', 'Tkspermodalan::$1');

    // Tksprofilrisiko (function)
    $routes->match(['get', 'post'], '(?i)tksprofilrisiko/(approve|getKomentar|getUnreadCount|markCommentsAsRead|simpanAnalisa|simpanDeskripsi|simpanPeringkatBeforeManual|simpanPeringkatManual|syncWithProfilRisiko|tambahKomentar|unapprove|unsyncWithProfilRisiko)', 'Tksprofilrisiko::$1');

    // Tksrentabilitas (function)
    $routes->match(['get', 'post'], '(?i)tksrentabilitas/(approve|autoSave|checkMisTksData|checkPrevPeriode|getKomentar|getUnreadCount|markCommentsAsRead|syncFromPrevPeriode|syncWithSipro|tambahKomentar|tarikDataMisTks|unapprove|unsyncWithSipro)', 'Tksrentabilitas::$1');

    // Tkstatakelola (function)
    $routes->match(['get', 'post'], '(?i)tkstatakelola/(approve|getKomentar|getUnreadCount|markCommentsAsRead|simpanAnalisa|simpanDeskripsi|simpanManual|simpanPeringkatManual|simpanPeringkatSebelumnya|syncWithTatakelola|tambahKomentar|unapprove|unsyncWithTatakelola)', 'Tkstatakelola::$1');

    // Transaksikepentingan (function)
    $routes->match(['get', 'post'], '(?i)transaksikepentingan/(Tambahkomentar|approveSemuaDirut|approveSemuaKom|batalNodataAjax|editKeteranganAjax|exporttxttransaksikepentingan|getKomentarByFaktorId|getUnreadCommentCountForFactor|hapusAjax|markUserCommentsAsRead|setNodataAjax|setNullKolomTindakAjax|tambahAjax|tambahKeteranganAjax|ubahAjax|unapproveSemuaDirut|unapproveSemuaKom)', 'Transaksikepentingan::$1');

    // transparansitahunan (function)
    $routes->match(['get', 'post'], '(?i)transparansitahunan/(excel|tambah|ubah)', 'transparansitahunan::$1');
    $routes->match(['get', 'post'], '(?i)transparansitahunan/(hapus)/(:segment)', 'transparansitahunan::$1/$2');

    // User (function)
    $routes->match(['get', 'post'], '(?i)user/(updateEmail)/(:segment)', 'User::$1/$2');
});

$routes->group('', ['filter' => 'guard:nav'], static function ($routes) {
    // Laporanpdfsaf (halaman dengan parameter)
    $routes->match(['get', 'post'], '(?i)laporanpdfsaf/(:segment)', 'Laporanpdfsaf::index/$1');
});

