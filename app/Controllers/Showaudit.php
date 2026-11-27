<?php

namespace App\Controllers;

use App\Libraries\SemesterSummaryController;

/**
 * Laporan Pelaksanaan dan Pokok Hasil Audit Intern — halaman ringkasan.
 * Lokasi file & kolom (uploads/pdf_auditinternal, showaudit.pdfaudit_filename) tetap, dipakai ZIP Laporan Semesteran.
 */
class Showaudit extends SemesterSummaryController
{
    public const JUDUL = 'Laporan Pelaksanaan dan Pokok Hasil Audit Intern';
    public const KICKER = 'Laporan Semesteran · Audit Intern';

    /** Navigasi antar bagian (dipakai halaman bagian). */
    public const NAV = ['Lingkupaudit' => '1. Ruang Lingkup', 'Temuanaudit' => '2. Temuan', 'Kesimpulanaudit' => '3. Kesimpulan',
        'Rekomendasiaudit' => '4. Rekomendasi', 'Pemantauanaudit' => '5. Pemantauan', 'Showaudit' => 'Ringkasan'];

    public const SECTIONS = [
        'lingkup' => ['Ruang Lingkup dan Waktu Pelaksanaan Audit', 'Lingkupaudit', 'lingkupaudit'],
        'temuan' => ['Temuan Audit', 'Temuanaudit', 'temuanaudit'],
        'kesimpulan' => ['Kesimpulan Audit dan Pernyataan Auditor', 'Kesimpulanaudit', 'kesimpulanaudit'],
        'rekomendasi' => ['Rekomendasi Auditor, Tanggapan Auditee, dan Tindak Lanjut', 'Rekomendasiaudit', 'rekomendasiaudit'],
        'pemantauan' => ['Hasil Pemantauan Komitmen Auditee', 'Pemantauanaudit', 'pemantauanaudit'],
    ];

    public const SUMMARY_TABLE = 'showaudit';
    public const FILE_COL = 'pdfaudit_filename';
    public const FILE_DIR = 'pdf_auditinternal';
    public const FILE_TEXT = [
        'Dokumen Pendukung',
        'File PDF pendukung Laporan Pelaksanaan dan Pokok Hasil Audit Intern. Dokumen ini ikut digabung pada ZIP Laporan Semesteran.',
        'Dokumen-Audit-Intern.pdf',
        'Dokumen pendukung (PDF)',
    ];
    public const PRINT_URL = 'LaporanPdfAudit/LaporanAuditInternal';
}
