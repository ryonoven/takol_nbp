<?php

$L_KEJADIAN = ['A' => 'Pelaku internal', 'B' => 'Pelaku eksternal', 'C' => 'Pelaku internal & eksternal'];

$L_JENISFRAUD = [
    '201' => 'Pemerasan (Korupsi)',
    '202' => 'Benturan kepentingan yang merugikan LJK dan/atau Konsumen (Korupsi)',
    '203' => 'Penyuapan (Korupsi)',
    '204' => 'Penerimaan tidak sah (Korupsi)',
    '301' => 'Penyalahgunaan uang tunai (Penyalahgunaan aset)',
    '302' => 'Penyalahgunaan persediaan (Penyalahgunaan aset)',
    '303' => 'Penyalahgunaan aset lainnya (Penyalahgunaan aset)',
    '401' => 'Kecurangan laporan keuangan',
    '501' => 'Penipuan',
    '601' => 'Pembocoran informasi rahasia',
    '701' => 'Tindakan lain yang dapat dipersamakan dengan Fraud',
];

$L_AKTIVITAS = [
    '301' => 'Pendanaan',
    '302' => 'Perkreditan/pembiayaan',
    '303' => 'Penggunaan identitas dan data orang, pihak lain, atau konsumen',
    '304' => 'Pengelolaan aset/investasi',
    '305' => 'Penggunaan siber',
    '306' => 'Pembukuan dan penyajian laporan keuangan',
    '307' => 'APU, PPT dan PPPSPM',
    '308' => 'Transaksi efek',
    '309' => 'Pemasaran',
    '310' => 'Kustodian',
    '311' => 'Penjatahan efek',
    '312' => 'Due diligence penjaminan emisi efek',
    '313' => 'Riset investasi',
    '314' => 'Proses underwriting',
    '315' => 'Pengelolaan iuran/premi/kontribusi/imbalan jasa penjaminan/kafalah',
    '316' => 'Pengurusan klaim/manfaat pensiun',
    '317' => 'Penilaian kerugian asuransi',
    '318' => 'Proses pemilihan asuransi/reasuransi',
    '319' => 'Pengelolaan surplus underwriting',
    '320' => 'Pengelolaan data kepesertaan',
    '321' => 'Proses subrogasi',
    '322' => 'Pemberian jasa manajemen',
    '323' => 'Layanan pendanaan bersama berbasis teknologi informasi',
    '324' => 'Bullion',
    '325' => 'Sekuritisasi',
    '326' => 'Pendukung Pasar',
    '327' => 'Aktivitas terkait Aset Keuangan Digital, termasuk Aset Kripto',
    '399' => 'Aktivitas lain',
];

$L_LOKASI = [
    '01' => 'Kantor Pusat Operasional',
    '02' => 'Kantor Pusat Non Operasional',
    '03' => 'Kantor Cabang LJK (Luar Negeri)',
    '04' => 'Kantor Wilayah',
    '05' => 'Kantor Cabang (Dalam Negeri)',
    '06' => 'Kantor cabang dari bank (Luar Negeri)',
    '07' => 'Kantor Cabang Pembantu LJK (Luar Negeri)',
    '08' => 'Kantor Cabang Pembantu (Dalam Negeri)',
    '09' => 'Kantor Cabang Pembantu (Luar Negeri)',
    '10' => 'Kantor Kas',
    '11' => 'Kantor Fungsional/Outlet',
    '12' => 'Payment Point',
    '13' => 'Kas Keliling/Kas Mobil/Kas Terapung',
    '14' => 'Kantor Perwakilan LJK (Luar Negeri)',
    '15' => 'ATM/Cash Deposit Machine/Cash Recycling Machine',
];

$L_PIHAKRUGI = ['001' => 'Lembaga Jasa Keuangan (LJK)', '002' => 'Konsumen', '003' => 'Pihak Lain'];

$L_KELEMAHAN = [
    '101' => 'Sumber Daya Manusia - Integritas',
    '102' => 'Sumber Daya Manusia - Kompetensi',
    '201' => 'Sistem Pengendalian Internal - Pengendalian internal Pimpinan',
    '202' => 'Sistem Pengendalian Internal - Pada Kebijakan internal LJK',
    '203' => 'Sistem Pengendalian Internal - Ketidaksesuaian Tingkat/Toleransi Risiko',
    '204' => 'Sistem Pengendalian Internal - Pelanggaran Standar dan Prosedur LJK',
    '205' => 'Sistem Pengendalian Internal - Tidak Berjalannya Pemisahan Fungsi (Four Eyes Principle)',
    '206' => 'Sistem Pengendalian Internal - Pelaporan Keuangan/Operasional Tidak Akurat/Tepat Waktu',
    '207' => 'Sistem Pengendalian Internal - Struktur Organisasi Belum Berjalan Efektif',
    '301' => 'Teknologi Informasi',
    '401' => 'Penerapan Strategi Anti Fraud Belum Berjalan Efektif',
    '501' => 'Eksternal - Kelalaian Konsumen',
    '502' => 'Eksternal - Pemahaman Konsumen menjaga Kerahasiaan Data Pribadi',
    '503' => 'Eksternal - Kecurangan Konsumen',
    '504' => 'Eksternal - Kecurangan Pihak Lain',
    '901' => 'Kelemahan Lain',
];
$L_PERBAIKAN = $L_KELEMAHAN;

$L_TINDAKPENANGANAN = [
    '01' => 'Pemberian surat peringatan',
    '02' => 'Rotasi atau mutasi',
    '03' => 'Penurunan jabatan',
    '04' => 'Pengunduran diri sukarela',
    '05' => 'Pemutusan hubungan kerja',
    '06' => 'Pemblokiran kartu debit/kartu kredit',
    '07' => 'Pemblokiran rekening',
    '08' => 'Penggantian kartu debit/kartu kredit',
    '09' => 'Pelaporan kepolisian atau tindakan hukum',
    '10' => 'Ganti rugi',
    '11' => 'Pembatalan polis/kontrak',
    '12' => 'Pencatatan dalam track record',
    '13' => 'Pelaporan kepada asosiasi/regulator/instansi',
    '19' => 'Tindakan Lain',
];

$L_PELAKUFRAUD = ['001' => 'Internal', '002' => 'Eksternal'];
$L_JENISID = ['001' => 'KTP (NIK)', '002' => 'Paspor', '003' => 'NPWP', '009' => 'Tidak diketahui'];
$L_KELAMIN = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$L_STATUSPELAKU = ['001' => 'Pelaku Utama', '002' => 'Pelaku Terlibat'];

$L_JABATAN = [
    '001' => 'Direktur Utama/Ketua Pengurus',
    '002' => 'Direktur/Pengurus',
    '003' => 'Direktur Kepatuhan/Pengurus bidang Kepatuhan',
    '004' => 'Komisaris Utama/Ketua Dewan Pengawas',
    '005' => 'Komisaris/Dewan Pengawas',
    '006' => 'Dewan Pengawas Syariah',
    '007' => 'Pejabat Eksekutif',
    '008' => 'Pemegang Saham Pengendali',
    '009' => 'Pemegang Saham',
    '010' => 'Tenaga Ahli dan Konsultan',
    '011' => 'Komisaris Independen/Dewan Pengawas Independen',
    '012' => 'Pejabat non Pejabat Eksekutif',
    '019' => 'Pegawai non Pejabat',
    '041' => 'Pensiun Karir',
    '042' => 'Pensiun Dini/Disabilitas',
    '043' => 'Diberhentikan atas keinginan sendiri',
    '044' => 'Berakhir masa kontrak/penugasan',
    '045' => 'Meninggal Dunia',
];

$L_KETPELAKU = [
    '001' => 'Konsumen',
    '002' => 'Pihak yang bekerjasama dengan LJK (vendor, investor, supplier, pejabat negara, rekanan)',
    '003' => 'Pihak yang tidak berhubungan langsung dengan LJK',
];

$L_STATUSPENANGANAN = [
    '001' => 'Proses internal LJK',
    '002' => 'Selesai diproses internal LJK',
    '003' => 'Dalam proses penanganan aparat penegak hukum',
    '004' => 'Berkekuatan hukum tetap (Inkracht)',
];

$lbl = static function (array $map, $code) {
    if ($code === null || $code === '') {
        return '-';
    }

    return $map[$code] ?? (string) $code;
};

$fmtRp = static function ($v) {
    $v = (float) $v;
    if (!$v) {
        return null;
    }

    return 'Rp' . number_format($v, 0, ',', '.');
};

$fmtTgl = static function (?string $v) {
    if (empty($v)) {
        return null;
    }
    $ts = strtotime($v);

    return $ts ? date('d-m-Y', $ts) : $v;
};

$semNum = \App\Models\LaporanPdfSafModel::semNum($h['semester'] ?? '');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>
        <?= esc('Laporan Kejadian Fraud - ' . $h['namabpr'] . ' - Semester ' . \App\Models\LaporanPdfSafModel::semNum($h['semester']) . ' Tahun ' . $h['tahun']) ?>
    </title>
    <style>
        @page {
            margin: 2.2cm 2cm;
        }

        body {
            margin: 0;
            font-family: Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #000;
        }

        .header-bpr {
            margin-top: -1.2cm;
            margin-bottom: 10pt;
        }

        p {
            margin: 0 0 5pt;
            text-align: justify;
        }

        .jdl {
            text-align: center;
            font-weight: bold;
            margin: 0;
        }

        .kasus {
            margin-top: 4pt;
        }

        .sec {
            font-weight: bold;
            font-size: 11pt;
            margin: 12pt 0 4pt;
            padding-bottom: 2pt;
            border-bottom: 1pt solid #333;
            page-break-after: avoid;
        }

        .fld {
            margin: 0 0 4pt;
        }

        .fld b {
            font-weight: bold;
        }

        .box {
            border: 0.75pt solid #888;
            border-radius: 3pt;
            padding: 6pt 9pt;
            margin: 0 0 8pt;
            background: #fafafa;
        }

        .ttd {
            width: 55%;
            margin: 22pt auto 0;
            text-align: center;
            page-break-inside: avoid;
        }

        .ttd img {
            height: 22mm;
            margin: 4pt 0;
        }

        .nihil {
            text-align: center;
            font-style: italic;
            margin-top: 40pt;
        }
    </style>
</head>

<body>
    <table class="header-bpr" style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:22%; vertical-align:middle;">
                <?php if ($logo): ?>
                    <img src="<?= $logo ?>" style="max-height:20mm; max-width:90%;">
                <?php endif; ?>
            </td>
            <td style="width:78%; text-align:right; vertical-align:middle;">
                <div style="font-size:9.5pt; font-weight:bold;">
                    <?= esc(mb_strtoupper((string) ($h['namabpr'] ?? ''))) ?></div>
                <?php if (!empty($h['alamat'])): ?>
                    <div style="font-size:8pt;"><?= esc($h['alamat']) ?></div>
                <?php endif; ?>
                <?php
                $kontak = array_filter([
                    !empty($h['webbpr']) ? 'Web: ' . $h['webbpr'] : null,
                    !empty($h['email']) ? 'Email: ' . $h['email'] : null,
                    !empty($h['nomor']) ? $h['nomor'] : null,
                ]);
                ?>
                <?php if ($kontak): ?>
                    <div style="font-size:8pt;"><?= esc(implode(' | ', $kontak)) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <hr style="border:none; border-top:1.5pt solid #000; margin:0 0 12pt;">

    <p class="jdl" style="font-size:12.5pt;">DESKRIPSI FRAUD / MODUS OPERANDI</p>
    <p class="jdl"><?= esc(mb_strtoupper((string) ($h['namabpr'] ?? ''))) ?></p>
    <p class="jdl">SEMESTER <?= esc($semNum) ?> TAHUN <?= esc((string) ($h['tahun'] ?? '')) ?></p>

    <?php if (empty($kasus)): ?>
        <p class="nihil">Tidak terdapat kejadian fraud yang dilaporkan pada periode ini (NIHIL).</p>
    <?php else: ?>
        <?php foreach ($kasus as $i => $r): ?>
            <div class="kasus" <?= $i > 0 ? 'style="page-break-before: always;"' : '' ?>>

                <?php if ($i === 0): ?>
                    <div style="height:6pt;"></div><?php endif; ?>

                <p class="sec">1. IDENTITAS KASUS</p>
                <p class="fld"><b>Judul Kasus:</b> <?= esc($lbl($L_JENISFRAUD, $r['jenisfraud'] ?? null)) ?></p>
                <p class="fld"><b>Nomor Referensi Kasus:</b> <?= esc($r['idkejadian'] ?: '-') ?></p>
                <p class="fld"><b>Kejadian Menurut Pelaku:</b> <?= esc($lbl($L_KEJADIAN, $r['kejadianfraud'] ?? null)) ?></p>

                <p class="sec">2. WAKTU DAN LOKASI</p>
                <?php
                $awal = $fmtTgl($r['wterjadiawal'] ?? null);
                $akhir = $fmtTgl($r['wterjadiakhir'] ?? null);
                $waktu = $awal ? ($awal . ($akhir ? ' s/d ' . $akhir : '')) : '-';
                ?>
                <p class="fld"><b>Tanggal Kejadian:</b> <?= esc($waktu) ?></p>
                <p class="fld"><b>Waktu Fraud Diketahui:</b> <?= esc($fmtTgl($r['wfrauddiketahui'] ?? null) ?: '-') ?></p>
                <p class="fld">
                    <b>Lokasi:</b> <?= esc($lbl($L_LOKASI, $r['lokfraud'] ?? null)) ?>
                    <?php if (!empty($r['divisifraud'])): ?>
                        &ndash; Divisi/Unit: <?= esc($r['divisifraud']) ?>
                    <?php endif; ?>
                </p>

                <p class="sec">3. PELAKU</p>
                <p class="fld"><b>Identitas Pelaku:</b> <?= esc($r['namapelaku'] ?: '-') ?></p>
                <p class="fld">
                    <b>Posisi/Jabatan (saat terjadi):</b> <?= esc($lbl($L_JABATAN, $r['jabatanterjadi'] ?? null)) ?>
                    <?php if (!empty($r['ketjabatanterjadi'])): ?> &ndash; <?= esc($r['ketjabatanterjadi']) ?><?php endif; ?>
                </p>
                <p class="fld">
                    <b>Pelaku Berasal Dari:</b> <?= esc($lbl($L_PELAKUFRAUD, $r['pelakufraud'] ?? null)) ?>
                    (<?= esc($lbl($L_STATUSPELAKU, $r['statuspelaku'] ?? null)) ?>)
                    &ndash; <?= esc($lbl($L_JENISID, $r['jenisidentitas'] ?? null)) ?>
                    <?php if (!empty($r['jeniskelamin'])): ?>, <?= esc($lbl($L_KELAMIN, $r['jeniskelamin'])) ?><?php endif; ?>
                </p>
                <p class="fld"><b>Jumlah Pelaku:</b> 1 orang</p>

                <p class="sec">4. DESKRIPSI MODUS OPERANDI</p>
                <p class="fld">
                    <b>Jenis Fraud:</b> <?= esc($lbl($L_JENISFRAUD, $r['jenisfraud'] ?? null)) ?>
                    <?php if (!empty($r['ketjenisfraud'])): ?> &ndash; <?= esc($r['ketjenisfraud']) ?><?php endif; ?>
                </p>
                <p class="fld"><b>Aktivitas Terkait:</b> <?= esc($lbl($L_AKTIVITAS, $r['aktivitasfraud'] ?? null)) ?></p>
                <p class="fld"><b>Rincian Modus Operandi:</b> <?= esc($r['descfraud'] ?: '-') ?></p>

                <p class="sec">5. DAMPAK FRAUD</p>
                <?php
                $totRiil = (float) ($r['rugiljkriil'] ?? 0) + (float) ($r['rugikonsumenriil'] ?? 0) + (float) ($r['rugilainriil'] ?? 0);
                $totPot = (float) ($r['rugiljkpot'] ?? 0) + (float) ($r['rugikonsumenpot'] ?? 0) + (float) ($r['rugilainpot'] ?? 0);
                $totRec = (float) ($r['rugiljkrec'] ?? 0) + (float) ($r['rugikonsumenrec'] ?? 0) + (float) ($r['rugilainrec'] ?? 0);
                ?>
                <p class="fld"><b>Pihak Yang Dirugikan:</b> <?= esc($lbl($L_PIHAKRUGI, $r['pihakrugi'] ?? null)) ?></p>
                <p class="fld">
                    <b>Kerugian Finansial (Riil / Potensial / Recovery):</b>
                    <?= esc(($fmtRp($totRiil) ?: 'Rp0') . ' / ' . ($fmtRp($totPot) ?: 'Rp0') . ' / ' . ($fmtRp($totRec) ?: 'Rp0')) ?>
                </p>
                <p class="fld">
                    <b>Dampak Non-Finansial:</b> <?= esc($lbl($L_KETPELAKU, $r['keteranganpelaku'] ?? null)) ?>
                    <?php if (!empty($r['pengenaansanksi'])): ?>; Sanksi: <?= esc($r['pengenaansanksi']) ?><?php endif; ?>
                </p>

                <p class="sec">6. PENANGANAN</p>
                <p class="fld">
                    <b>Kelemahan Penyebab:</b> <?= esc($lbl($L_KELEMAHAN, $r['kelemahanfraud'] ?? null)) ?>
                    <?php if (!empty($r['ketkelemahanfraud'])): ?> &ndash; <?= esc($r['ketkelemahanfraud']) ?><?php endif; ?>
                </p>
                <p class="fld">
                    <b>Langkah yang Sudah Diambil:</b> <?= esc($lbl($L_TINDAKPENANGANAN, $r['tindakpenanganan'] ?? null)) ?>
                    <?php if (!empty($r['kettindakpenanganan'])): ?> &ndash;
                        <?= esc($r['kettindakpenanganan']) ?>        <?php endif; ?>
                </p>
                <p class="fld">
                    <b>Tindak Lanjut yang Direncanakan:</b> <?= esc($lbl($L_PERBAIKAN, $r['tindakperbaikan'] ?? null)) ?>
                    <?php if (!empty($r['kettindakperbaikan'])): ?> &ndash; <?= esc($r['kettindakperbaikan']) ?><?php endif; ?>
                    <?php if (!empty($r['targetpelaksanaan'])): ?>; Target: <?= esc($r['targetpelaksanaan']) ?><?php endif; ?>
                    <?php if (!empty($r['realpelaksanaan'])): ?>; Realisasi: <?= esc($r['realpelaksanaan']) ?><?php endif; ?>
                </p>
                <p class="fld"><b>Status Penanganan:</b> <?= esc($lbl($L_STATUSPENANGANAN, $r['statuspenanganan'] ?? null)) ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="ttd">
        <?= esc((string) ($h['kota_ttd'] ?? '')) ?><?= (!empty($h['kota_ttd']) && !empty($h['tanggal_laporan'])) ? ', ' : '' ?><?= esc($fmtTgl($h['tanggal_laporan'] ?? null) ?: '') ?><br>
        Dibuat oleh<br>
        <?php if ($ttd): ?><img src="<?= $ttd ?>"><br><?php else: ?><br><br><br><br><?php endif; ?>
        <b><?= esc((string) ($h['nama_pembuat'] ?? '')) ?></b><br>
        <b><?= esc((string) ($h['jabatan_pembuat'] ?? '')) ?></b>
    </div>
</body>

</html>