<?php
// app/Libraries/PdfNormalizer.php

namespace App\Libraries;

class PdfNormalizer
{
    protected ?string $gsBinary = null;

    public function __construct()
    {
        $this->gsBinary = $this->detectGhostscript();
    }

    /**
     * Cek apakah Ghostscript bisa dipakai di environment ini.
     */
    public function isAvailable(): bool
    {
        return !empty($this->gsBinary);
    }

    /**
     * Deteksi binary gs, hanya dijalankan sekali per instance.
     */
    protected function detectGhostscript(): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $disabled = explode(',', (string) ini_get('disable_functions'));
        if (in_array('shell_exec', array_map('trim', $disabled))) {
            return null;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: gswin64c di PATH, atau instalasi standar C:\Program Files\gs\gs*\bin
            $path = trim((string) strtok((string) @shell_exec('where gswin64c 2>NUL'), "\r\n"));
            if ($path === '') {
                $found = glob('C:/Program Files/gs/gs*/bin/gswin64c.exe') ?: [];
                rsort($found);
                $path = $found[0] ?? '';
            }
            return $path !== '' ? $path : null;
        }

        $path = trim((string) @shell_exec('which gs 2>/dev/null'));

        return !empty($path) ? $path : null;
    }

    /**
     * Normalize PDF ke compatibility level 1.4.
     * Return path file hasil normalisasi, atau null kalau gagal/tidak tersedia.
     */
    public function normalizeToV14(string $inputPath): ?string
    {
        if (!$this->isAvailable()) {
            log_message('warning', 'PdfNormalizer: Ghostscript tidak tersedia, skip normalisasi.');
            return null;
        }

        if (!file_exists($inputPath) || !is_readable($inputPath)) {
            log_message('error', 'PdfNormalizer: File input tidak ditemukan/tidak bisa dibaca: ' . $inputPath);
            return null;
        }

        $outputPath = sys_get_temp_dir() . '/gs_norm_' . uniqid() . '.pdf';

        $cmd = sprintf(
            '%s -dCompatibilityLevel=1.4 -dPDFSETTINGS=/prepress -dNOPAUSE -dBATCH -dQUIET -sDEVICE=pdfwrite -sOutputFile=%s %s 2>&1',
            PHP_OS_FAMILY === 'Windows' ? '"' . $this->gsBinary . '"' : escapeshellcmd($this->gsBinary),
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        $output = @shell_exec($cmd);

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            log_message('error', 'PdfNormalizer: Ghostscript gagal memproses file. Output: ' . $output);
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            return null;
        }

        return $outputPath;
    }
}