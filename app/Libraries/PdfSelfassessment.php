<?php
namespace App\Libraries;

use setasign\Fpdi\Fpdi;

class PdfSelfassessment extends Fpdi
{
    protected $headerData = [];
    protected $isCoverPage = true;
    protected $isCoverFooterPage = true;
    protected $kodebpr;
    protected $periodeId;

    public function setHeaderData(array $data)
    {
        $this->headerData = $data;
    }

    public function setFilterParams($kodebpr, $periodeId)
    {
        $this->kodebpr = $kodebpr;
        $this->periodeId = $periodeId;
    }

    private function checkPageBreak($heightNeeded)
    {
        $pageBottomMargin = 20; // Margin footer
        $maxPageY = $this->GetPageHeight() - $pageBottomMargin; // Tinggi halaman dikurangi margin bawah

        if ($this->GetY() + $heightNeeded > $maxPageY) {
            $this->AddPage();
            $this->SetY(40); // Sesuaikan jika tinggi header Anda berbeda
            return true;
        }
        return false;
    }


    public function generateCoverPage(array $infoData)
    {
        $this->isCoverPage = true;
        $this->AddPage();
        $this->SetAutoPageBreak(false);

        $bprName = $this->headerData['namabpr'] ?? 'Nama BPR Tidak Tersedia';
        $periodeYear = isset($this->headerData['tahun']) ? $this->headerData['tahun'] : date('Y');
        $periodeSemester = isset($this->headerData['semester']) && in_array($this->headerData['semester'], [1, 2]) ? $this->headerData['semester'] : 1;

        // 1. Handle cover image more safely
        $coverImagePath = FCPATH . 'assets/img/Cover.png';
        if (file_exists($coverImagePath)) {
            $this->Image($coverImagePath, 0, 0, 210, 297);
        }

        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(0, 0, 0);
        $this->SetY(25);
        $this->Cell(0, 15, 'Self Assessment Tata Kelola', 0, 1, 'C');
        $this->Cell(0, 2, 'Semester ' . $periodeSemester . ' Tahun ' . $periodeYear, 0, 1, 'C');
        $this->Cell(0, 15, $bprName, 0, 1, 'C');

        // 2. Handle logo more safely
        if (!empty($infoData['logo'])) {
            $logoPath = FCPATH . 'asset/img/' . $infoData['logo'];

            // Check if file exists and has valid image extension
            if (file_exists($logoPath)) {
                $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    $this->Image($logoPath, 155, 7, 45);
                }
            }
        }
        $this->isCoverPage = false;

    }

    public function generateNilaiFaktor1(array $nilaifaktorData, array $infoData, array $faktorData)
    {
        $this->isCoverPage = false;
        $this->isCoverFooterPage = false;
        // Sort data by faktor1id for consistent display
        usort($nilaifaktorData, function ($a, $b) {
            return $a['faktor1id'] <=> $b['faktor1id'];
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 1. Aspek Pemegang Saham', 0, 1, 'L');
        $this->Ln(1);

        // Table Header
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80]; // Widths for No, Kriteria, Nilai, Keterangan
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        // Draw header cells
        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        // Sub-category header (e.g., "A. Struktur dan Infrastruktur Tata Kelola (S)")
        $this->SetTextColor(0); // Black text for content
        $this->SetFont('Arial', 'B', 11); // Bold for sub-category    

        $this->SetFont('Arial', '', 11); // Regular font for table content
        $no = 1; // Row numbering

        $groupedFaktorData = [];
        foreach ($faktorData as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN'; // Default to UNKNOWN if 'sph' is missing
            $groupedFaktorData[$sphCategory][] = $row;
        }

        // Define mapping for 'sph' values to display labels
        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal', // Fallback for missing 'sph'
        ];

        // Define a desired order for categories to be displayed
        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0; // To track A, B, C... for sub-category headers

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktorData[$sphKey])) {
                $categoryCounter++;
                // Generate label like "A. Struktur dan Infrastruktur Tata Kelola (S)"
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                // Print the sub-category header
                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11); // Bold for sub-category header
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true); // Sub-category header with border
                $this->SetFont('Arial', '', 11); // Revert to regular font for content rows

                $no = 1; // Reset numbering for each category
                foreach ($groupedFaktorData[$sphKey] as $faktorRow) {
                    $nilaifaktorRow = null;
                    foreach ($nilaifaktorData as $nilaifaktor) {
                        if (
                            $nilaifaktor['faktor1id'] == $faktorRow['id'] &&
                            $nilaifaktor['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktorRow = $nilaifaktor;
                            break;
                        }
                    }

                    // Skip if no corresponding data is found for the current filter
                    if (!$nilaifaktorRow) {
                        continue;
                    }

                    $subCategory = $faktorRow['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktorRow['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktorRow['keterangan'] ?? 'N/A';
                    $nfaktor = $nilaifaktorRow['nfaktor'] ?? 'N/A';
                    // $penjelasan = $nilaifaktorRow['penjelasfaktor'] ?? 'N/A';

                    $lineHeight = 7; // Increased standard line height for better spacing

                    // Calculate the number of lines needed for multi-line cells
                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1); // Ensure at least 1 line height
                    $rowHeight = $lineHeight * $maxNbLines;

                    // Check for page break BEFORE drawing any part of the row
                    $this->checkPageBreak($rowHeight);

                    // Store current X and Y positions for drawing the row
                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    // --- Draw content for each cell without borders ---

                    // Cell 1: No (MultiCell for consistent Y handling, no border)
                    $this->MultiCell($headerWidths[0], $lineHeight, $faktorRow['id'], 0, 'C', false); // Gunakan $faktorRow['id'] atau $faktorRow['faktor1id']

                    $this->SetXY($startX + $headerWidths[0], $startY);

                    // Cell 2: Kriteria/Indikator (Sub Category) (MultiCell, no border)
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory, 0, 'L', false);
                    // Restore Y to start of row, move X to next column's start
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);

                    // Cell 3: Nilai (MultiCell for consistent Y handling, no border)
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    // Move X to next column's start (Y remains at startY for the next MultiCell)
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);

                    // Cell 4: Keterangan (MultiCell, no border)
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    // --- Draw borders for the entire row AFTER content is placed ---
                    // Draw a single rectangle around the entire row based on calculated rowHeight
                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);

                    // Draw vertical lines for columns inside the row
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    // Move the Y pointer to the start of the next row
                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor1(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor'];
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 1 (Aspek Pemegang Saham)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        // // Tambahkan peringkat komposit jika tersedia dan berbeda dari nilaiFaktor
        // if (!empty($infoData['peringkatkomposit']) && $infoData['peringkatkomposit'] !== $nilaiFaktor) {
        //     $displayNilai .= ' (' . $infoData['peringkatkomposit'] . ')'; // Misalnya: "2 (Memadai)"
        // } elseif (is_numeric($nilaiFaktor)) { // Jika hanya angka, tambahkan keterangan default
        //     $mapping = [1 => 'Sangat Baik', 2 => 'Memadai', 3 => 'Cukup Memadai', 4 => 'Kurang Memadai', 5 => 'Tidak Memadai'];
        //     $displayNilai .= ' (' . ($mapping[$nilaiFaktor] ?? 'N/A') . ')';
        // }


        $this->SetFillColor(199, 230, 230); // Latar belakang biru muda
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    /**
     * Menambahkan metode untuk membuat tabel "Analisa Faktor Positif dan Negatif"
     */
    public function generateAnalisaFaktor1(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spasi sebelum tabel baru
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 1 Analysis (Header for the entire section) ---
        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 1)', 1, 1, 'C', true); // Factor 1 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;  // Column for numbering (1), (2)
        $col2Width = $tableWidth - $col1Width;  // Remaining width for content

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5); // Adding buffer space
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');  // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');  // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 1.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent);

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);  // Draw content with borders

        // --- Section B: Faktor Negatif Struktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C'); // Left border for '2)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L'); // Right and Top border for 'Faktor Negatif'

        // Get the content for 'Faktor Negatif'
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 1.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content for 'Faktor Negatif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false); // Draw content with borders

        // --- Section C: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5); // Adding buffer space
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');  // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');  // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 1.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifProsesContent);

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);  // Draw content with borders

        // --- Section D: Faktor Negatif Proses ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C'); // Left border for '2)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L'); // Right border for 'Faktor Negatif'

        // Get the content for 'Faktor Negatif Proses'
        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 1.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifProsesContent);

        // Draw the MultiCell content for 'Faktor Negatif Proses'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false); // Draw content with borders

        // --- Section E: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5); // Adding buffer space
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');  // Left border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');  // Right border for 'Faktor Positif'

        // Get the content for 'Faktor Positif'
        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 1.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifHasilContent);

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);  // Draw content with borders

        // --- Section F: Faktor Negatif Hasil ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C'); // Left border for '2)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L'); // Right border for 'Faktor Negatif'

        // Get the content for 'Faktor Negatif Hasil'
        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 1.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifHasilContent);

        // Draw the MultiCell content for 'Faktor Negatif Hasil'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);  // Draw content with borders

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor2(array $nilaifaktor2Data, array $infoData, array $faktor2Data)
    {
        usort($nilaifaktor2Data, function ($a, $b) {
            return $a['faktor2id'] <=> $b['faktor2id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 2. Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi', 0, 1, 'L'); // Judul Faktor 2
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor2Data = [];
        foreach ($faktor2Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor2Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor2Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor2Data[$sphKey] as $faktor2Row) {
                    $nilaifaktor2Row = null;
                    foreach ($nilaifaktor2Data as $nilaifaktor2) {
                        if (
                            $nilaifaktor2['faktor2id'] == $faktor2Row['id'] && // Gunakan faktor2id
                            $nilaifaktor2['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor2['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor2Row = $nilaifaktor2;
                            break;
                        }
                    }

                    if (!$nilaifaktor2Row) {
                        continue;
                    }

                    $subCategory2 = $faktor2Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor2Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor2Row['keterangan'] ?? 'N/A';

                    $lineHeight = 7;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory2);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor2Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory2, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor2(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor2 = $infoData['nfaktor2'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 2 (Aspek Dewan Komisaris)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor2;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor2(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spacing before the new table
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // Estimating height for page break
        $estimatedHeight = 10; // For title
        $estimatedHeight += 10 * 3; // For 3 main categories (A, B, C)
        $estimatedHeight += $lineHeight * 6 * 2; // For positive/negative in each category (rough estimate)
        $this->checkPageBreak($estimatedHeight);

        // Table Title
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 2)', 1, 1, 'C', true); // Factor 2 Title
        $this->SetTextColor(0); // Reset text color to black

        // Column Width Definitions
        $col1Width = 8;  // Column for numbering (1), (2)
        $col2Width = $tableWidth - $col1Width;  // Remaining width for content

        // Section A. Struktur dan Infrastruktur
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L'); // Border Left/Right

        // Faktor Positif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col1Width, $lineHeight, '1)', 'RLT', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', 'RLT', 1, 'L'); // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 2.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent);

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);  // Draw content with borders

        // Section B. Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight); // Adding buffer space before the next section
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C'); // Left border for '2)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L'); // Right border for 'Faktor Negatif'

        // Get the content for 'Faktor Negatif'
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 2.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content for 'Faktor Negatif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false); // Draw content with borders

        // Section C. Proses Penerapan Tata Kelola
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5); // Adding buffer space for the section header
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight); // Ensure space for the next row
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');  // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');  // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 2.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifProsesContent);

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);  // Draw content with borders

        // Section D. Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight); // Adding buffer space before the next section
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C'); // Left border for '2)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L'); // Right border for 'Faktor Negatif'

        // Get the content for 'Faktor Negatif Proses'
        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 2.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifProsesContent);

        // Draw the MultiCell content for 'Faktor Negatif Proses'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false); // Draw content with borders

        // Section E. Hasil Penerapan Tata Kelola
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5); // Adding buffer space for the section header
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight); // Ensure space for the next row
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');  // Left border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');  // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 2.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifHasilContent);

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);  // Draw content with borders

        // Section F. Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight); // Adding buffer space before the next section
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C'); // Left border for '2)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L'); // Right border for 'Faktor Negatif'

        // Get the content for 'Faktor Negatif Hasil'
        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 2.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifHasilContent);

        // Draw the MultiCell content for 'Faktor Negatif Hasil'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);  // Draw content with borders

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor3(array $nilaifaktor3Data, array $infoData, array $faktor3Data)
    {
        usort($nilaifaktor3Data, function ($a, $b) {
            return $a['faktor3id'] <=> $b['faktor3id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 3. Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris', 0, 1, 'L'); // Judul Faktor 2
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor3Data = [];
        foreach ($faktor3Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor3Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor3Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor3Data[$sphKey] as $faktor3Row) {
                    $nilaifaktor3Row = null;
                    foreach ($nilaifaktor3Data as $nilaifaktor3) {
                        if (
                            $nilaifaktor3['faktor3id'] == $faktor3Row['id'] &&
                            $nilaifaktor3['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor3['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor3Row = $nilaifaktor3;
                            break;
                        }
                    }

                    if (!$nilaifaktor3Row) {
                        continue;
                    }

                    $subCategory3 = $faktor3Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor3Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor3Row['keterangan'] ?? 'N/A';

                    $lineHeight = 7;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory3);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor3Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory3, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor3(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor3 = $infoData['nfaktor3'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 3 (Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor3;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor3(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 3 Analysis (Header for the entire section) ---
        // No checkPageBreak here as AddPage() ensures it's on a new page.
        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 3)', 1, 1, 'C', true); // Factor 3 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width; // Remaining width for 'Faktor Positif/Negatif' label

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L'); // Right and Top border for 'Faktor Positif'
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 3.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent); // No need for extra buffer if MultiCell manages its own space

        // Draw the MultiCell content
        // MultiCell itself will draw borders if '1' or 'LRB' is used
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);
        // The previous Rect call is removed as MultiCell draws its own borders.

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        // Calculate space needed for the '2) Faktor Negatif' header row
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        // Get content and calculate its MultiCell height
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 3.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);
        // Rect call removed

        // --- Section B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category B title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 3.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);
        // Rect call removed

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 3.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);
        // Rect call removed

        // --- Section C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category C title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 3.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);
        // Rect call removed

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 3.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);
        // Rect call removed

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor4(array $nilaifaktor4Data, array $infoData, array $faktor4Data)
    {
        usort($nilaifaktor4Data, function ($a, $b) {
            return $a['faktor4id'] <=> $b['faktor4id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 4. Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris', 0, 1, 'L'); // Judul Faktor 2
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor4Data = [];
        foreach ($faktor4Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor4Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor4Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor4Data[$sphKey] as $faktor4Row) {
                    $nilaifaktor4Row = null;
                    foreach ($nilaifaktor4Data as $nilaifaktor4) {
                        if (
                            $nilaifaktor4['faktor4id'] == $faktor4Row['id'] &&
                            $nilaifaktor4['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor4['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor4Row = $nilaifaktor4;
                            break;
                        }
                    }

                    if (!$nilaifaktor4Row) {
                        continue;
                    }

                    $subCategory4 = $faktor4Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor4Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor4Row['keterangan'] ?? 'N/A';

                    $lineHeight = 7;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory4);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor4Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory4, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor4(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor4'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 4 (Kelengkapan dan Pelaksanaan Tugas Komite)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor4(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spacing before the new table
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 4 Analysis (Header for the entire section) ---
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 4)', 1, 1, 'C', true); // Factor 4 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width; // Remaining width for 'Faktor Positif/Negatif' label

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L'); // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 4.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent); // No need for extra buffer if MultiCell manages its own space

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        // Calculate space needed for the '2) Faktor Negatif' header row
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        // Get content and calculate its MultiCell height
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 4.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Section B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category B title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 4.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 4.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Section C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category C title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 4.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 4.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor5(array $nilaifaktor5Data, array $infoData, array $faktor5Data)
    {
        usort($nilaifaktor5Data, function ($a, $b) {
            return $a['faktor5id'] <=> $b['faktor5id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 5. Penanganan Benturan Kepentingan', 0, 1, 'L'); // Judul Faktor 2
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor5Data = [];
        foreach ($faktor5Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor5Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor5Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor5Data[$sphKey] as $faktor5Row) {
                    $nilaifaktor5Row = null;
                    foreach ($nilaifaktor5Data as $nilaifaktor5) {
                        if (
                            $nilaifaktor5['faktor5id'] == $faktor5Row['id'] &&
                            $nilaifaktor5['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor5['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor5Row = $nilaifaktor5;
                            break;
                        }
                    }

                    if (!$nilaifaktor5Row) {
                        continue;
                    }

                    $subCategory5 = $faktor5Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor5Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor5Row['keterangan'] ?? 'N/A';

                    $lineHeight = 7;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory5);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor5Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory5, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateAnalisaFaktor5(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spacing before the new table
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 5 Analysis (Header for the entire section) ---
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 5)', 1, 1, 'C', true); // Factor 5 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width; // Remaining width for 'Faktor Positif/Negatif' label

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L'); // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 5.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent); // No need for extra buffer if MultiCell manages its own space

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        // Calculate space needed for the '2) Faktor Negatif' header row
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        // Get content and calculate its MultiCell height
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 5.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content for 'Faktor Negatif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Section B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category B title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 5.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 5.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Section C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category C title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 5.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 5.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateKesimpulanFaktor5(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor5'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 5 (Kelengkapan dan Pelaksanaan Tugas Komite)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateNilaiFaktor6(array $nilaifaktor6Data, array $infoData, array $faktor6Data)
    {
        usort($nilaifaktor6Data, function ($a, $b) {
            return $a['faktor6id'] <=> $b['faktor6id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 6. Penerapan Fungsi Kepatuhan', 0, 1, 'L'); // Judul Faktor 6
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor6Data = [];
        foreach ($faktor6Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor6Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor6Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor6Data[$sphKey] as $faktor6Row) {
                    $nilaifaktor6Row = null;
                    foreach ($nilaifaktor6Data as $nilaifaktor6) {
                        if (
                            $nilaifaktor6['faktor6id'] == $faktor6Row['id'] &&
                            $nilaifaktor6['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor6['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor6Row = $nilaifaktor6;
                            break;
                        }
                    }

                    if (!$nilaifaktor6Row) {
                        continue;
                    }

                    $subCategory6 = $faktor6Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor6Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor6Row['keterangan'] ?? 'N/A';

                    $lineHeight = 7;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory6);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor6Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory6, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor6(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor6'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 6 (Penerapan Fungsi Kepatuhan)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor6(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spacing before the new table
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 6 Analysis (Header for the entire section) ---
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 6)', 1, 1, 'C', true); // Factor 6 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width; // Remaining width for 'Faktor Positif/Negatif' label

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L'); // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 6.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent); // No need for extra buffer if MultiCell manages its own space

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        // Calculate space needed for the '2) Faktor Negatif' header row
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        // Get content and calculate its MultiCell height
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 6.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content for 'Faktor Negatif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Section B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category B title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 6.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 6.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Section C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category C title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 6.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 6.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor7(array $nilaifaktor7Data, array $infoData, array $faktor7Data)
    {
        usort($nilaifaktor7Data, function ($a, $b) {
            return $a['faktor7id'] <=> $b['faktor7id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 7. Penerapan Fungsi Audit Intern', 0, 1, 'L'); // Judul Faktor 7
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor7Data = [];
        foreach ($faktor7Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor7Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor7Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor7Data[$sphKey] as $faktor7Row) {
                    $nilaifaktor7Row = null;
                    foreach ($nilaifaktor7Data as $nilaifaktor7) {
                        if (
                            $nilaifaktor7['faktor7id'] == $faktor7Row['id'] &&
                            $nilaifaktor7['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor7['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor7Row = $nilaifaktor7;
                            break;
                        }
                    }

                    if (!$nilaifaktor7Row) {
                        continue;
                    }

                    $subCategory7 = $faktor7Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor7Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor7Row['keterangan'] ?? 'N/A';

                    $lineHeight = 7;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory7);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor7Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory7, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor7(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor7'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 7 (Penerapan Fungsi Kepatuhan)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor7(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spacing before the new table
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 7 Analysis (Header for the entire section) ---
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 7)', 1, 1, 'C', true); // Factor 7 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width; // Remaining width for 'Faktor Positif/Negatif' label

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L'); // Right and Top border for 'Faktor Positif'

        // Get the content for 'Faktor Positif' section
        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 7.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent); // No need for extra buffer if MultiCell manages its own space

        // Draw the MultiCell content for 'Faktor Positif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        // Calculate space needed for the '2) Faktor Negatif' header row
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        // Get content and calculate its MultiCell height
        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 7.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content for 'Faktor Negatif'
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Section B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category B title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 7.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 7.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Section C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        // Check for space for Category C title
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 7.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 7.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor8(array $nilaifaktor8Data, array $infoData, array $faktor8Data)
    {
        usort($nilaifaktor8Data, function ($a, $b) {
            return $a['faktor8id'] <=> $b['faktor8id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 8. Penerapan Fungsi Audit Ekstern', 0, 1, 'L'); // Judul Faktor 8
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor8Data = [];
        foreach ($faktor8Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor8Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor8Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor8Data[$sphKey] as $faktor8Row) {
                    $nilaifaktor8Row = null;
                    foreach ($nilaifaktor8Data as $nilaifaktor8) {
                        if (
                            $nilaifaktor8['faktor8id'] == $faktor8Row['id'] &&
                            $nilaifaktor8['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor8['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor8Row = $nilaifaktor8;
                            break;
                        }
                    }

                    if (!$nilaifaktor8Row) {
                        continue;
                    }

                    $subCategory8 = $faktor8Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor8Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor8Row['keterangan'] ?? 'N/A';

                    $lineHeight = 8;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory8);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor8Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory8, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor8(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor8'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 8 (Penerapan Fungsi Audit Ekstern)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor8(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spacing before the new table
        $tableWidth = 190; // Total width for the content area (adjust as per your PDF margins)
        $lineHeight = 6;   // Base line height for MultiCell content

        // --- Main Title for Factor 8 Analysis (Header for the entire section) ---
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 8)', 1, 1, 'C', true); // Factor 8 Title
        $this->SetTextColor(0); // Reset text color to black

        // Define column widths for the '1)' / '2)' and 'Faktor Positif/Negatif' header row
        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width; // Remaining width for 'Faktor Positif/Negatif' label

        // --- Section A: Struktur dan Infrastruktur ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C'); // Left and Top border for '1)'
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L'); // Right and Top border for 'Faktor Positif'

        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 8.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent); // No need for extra buffer if MultiCell manages its own space

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);
        // The previous Rect call is removed as MultiCell draws its own borders.

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 8.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);
        // Rect call removed

        // --- Section B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 8.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);
        // Rect call removed

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 8.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);
        // Rect call removed

        // --- Section C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 8.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);
        // Rect call removed

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 8.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);
        // Rect call removed

        // Final bottom border (optional, if you want a single line at the very end of the section)
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor9(array $nilaifaktor9Data, array $infoData, array $faktor9Data)
    {
        usort($nilaifaktor9Data, function ($a, $b) {
            return $a['faktor9id'] <=> $b['faktor9id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 9. Penerapan Manajemen Risiko dan Strategi Anti Fraud', 0, 1, 'L'); // Judul Faktor 9
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor9Data = [];
        foreach ($faktor9Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor9Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor9Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor9Data[$sphKey] as $faktor9Row) {
                    $nilaifaktor9Row = null;
                    foreach ($nilaifaktor9Data as $nilaifaktor9) {
                        if (
                            $nilaifaktor9['faktor9id'] == $faktor9Row['id'] &&
                            $nilaifaktor9['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor9['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor9Row = $nilaifaktor9;
                            break;
                        }
                    }

                    if (!$nilaifaktor9Row) {
                        continue;
                    }

                    $subCategory9 = $faktor9Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor9Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor9Row['keterangan'] ?? 'N/A';

                    $lineHeight = 8;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory9);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor9Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory9, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor9(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor9'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 9 (Penerapan Manajemen Risiko dan Strategi Anti Fraud)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor9(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spasi sebelum tabel baru
        $tableWidth = 190;
        $lineHeight = 6;

        // Estimasi tinggi untuk page break
        $estimatedHeight = 10; // For title
        $estimatedHeight += 10 * 3; // For 3 main categories (A, B, C)
        $estimatedHeight += $lineHeight * 6 * 2; // For positive/negative in each category (rough estimate)
        $this->checkPageBreak($estimatedHeight);

        // Judul Tabel
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 9)', 1, 1, 'C', true); // Factor 9 Title
        $this->SetTextColor(0); // Kembali ke teks hitam

        // Definisikan lebar kolom
        $col1Width = 8; // Kolom "No"
        $col2Width = $tableWidth - $col1Width; // Kolom "Analisa Faktor Positif dan Negatif"

        // Bagian A. Struktur dan Infrastruktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L');

        // Faktor Positif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 9.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 9.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Bagian B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 9.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 9.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Bagian C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 9.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 9.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Garis bawah penutup tabel
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generateNilaiFaktor10(array $nilaifaktor10Data, array $infoData, array $faktor10Data)
    {
        usort($nilaifaktor10Data, function ($a, $b) {
            return $a['faktor10id'] <=> $b['faktor10id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 10. Batas Maksimum Pemberian Kredit', 0, 1, 'L'); // Judul Faktor 9
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor10Data = [];
        foreach ($faktor10Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor10Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor10Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor10Data[$sphKey] as $faktor10Row) {
                    $nilaifaktor10Row = null;
                    foreach ($nilaifaktor10Data as $nilaifaktor10) {
                        if (
                            $nilaifaktor10['faktor10id'] == $faktor10Row['id'] &&
                            $nilaifaktor10['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor10['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor10Row = $nilaifaktor10;
                            break;
                        }
                    }

                    if (!$nilaifaktor10Row) {
                        continue;
                    }

                    $subCategory10 = $faktor10Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor10Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor10Row['keterangan'] ?? 'N/A';

                    $lineHeight = 8;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory10);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor10Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory10, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor10(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor10'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 10 (Batas Maksimum Pemberian Kredit)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor10(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spasi sebelum tabel baru
        $tableWidth = 190;
        $lineHeight = 6;

        // Estimasi tinggi untuk page break
        $estimatedHeight = 10; // For title
        $estimatedHeight += 10 * 3; // For 3 main categories (A, B, C)
        $estimatedHeight += $lineHeight * 6 * 2; // For positive/negative in each category (rough estimate)
        $this->checkPageBreak($estimatedHeight);

        // Judul Tabel
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 10)', 1, 1, 'C', true); // Factor 10 Title
        $this->SetTextColor(0); // Kembali ke teks hitam

        // Definisikan lebar kolom
        $col1Width = 8; // Kolom "No"
        $col2Width = $tableWidth - $col1Width; // Kolom "Analisa Faktor Positif dan Negatif"

        // Bagian A. Struktur dan Infrastruktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L'); // Border Left/Right

        // Faktor Positif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 10.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 10.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Bagian B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 10.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 10.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Bagian C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 10.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 10.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Garis bawah penutup tabel
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C'); // Top border only
    }

    public function generateNilaiFaktor11(array $nilaifaktor11Data, array $infoData, array $faktor11Data)
    {
        usort($nilaifaktor11Data, function ($a, $b) {
            return $a['faktor11id'] <=> $b['faktor11id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 11. Integritas Pelaporan dan Sistem Teknologi Informasi', 0, 1, 'L'); // Judul Faktor 11
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor11Data = [];
        foreach ($faktor11Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor11Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor11Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor11Data[$sphKey] as $faktor11Row) {
                    $nilaifaktor11Row = null;
                    foreach ($nilaifaktor11Data as $nilaifaktor11) {
                        if (
                            $nilaifaktor11['faktor11id'] == $faktor11Row['id'] &&
                            $nilaifaktor11['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor11['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor11Row = $nilaifaktor11;
                            break;
                        }
                    }

                    if (!$nilaifaktor11Row) {
                        continue;
                    }

                    $subCategory11 = $faktor11Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor11Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor11Row['keterangan'] ?? 'N/A';

                    $lineHeight = 8;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory11);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor11Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory11, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor11(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor11'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 11 (Integritas Pelaporan dan Sistem Teknologi Informasi)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor11(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spasi sebelum tabel baru
        $tableWidth = 190;
        $lineHeight = 6;

        // Estimasi tinggi untuk page break
        $estimatedHeight = 10; // For title
        $estimatedHeight += 10 * 3; // For 3 main categories (A, B, C)
        $estimatedHeight += $lineHeight * 6 * 2; // For positive/negative in each category (rough estimate)
        $this->checkPageBreak($estimatedHeight);

        // Judul Tabel
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 11)', 1, 1, 'C', true); // Factor 11 Title
        $this->SetTextColor(0); // Kembali ke teks hitam

        // Definisikan lebar kolom
        $col1Width = 8; // Kolom "No"
        $col2Width = $tableWidth - $col1Width; // Kolom "Analisa Faktor Positif dan Negatif"

        // Bagian A. Struktur dan Infrastruktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L'); // Border Left/Right

        // Faktor Positif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 11.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 11.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Bagian B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 11.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 11.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Bagian C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 11.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 11.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Garis bawah penutup tabel
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C'); // Top border only
    }

    public function generateNilaiFaktor12(array $nilaifaktor12Data, array $infoData, array $faktor12Data)
    {
        usort($nilaifaktor12Data, function ($a, $b) {
            return $a['faktor12id'] <=> $b['faktor12id']; // Urutkan berdasarkan faktor2id
        });

        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 10, 'Faktor 12. Rencana Bisnis BPR', 0, 1, 'L'); // Judul Faktor 12
        $this->Ln(1);

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        $headerWidths = [10, 70, 30, 80];
        $headerLabels = ['No', 'Kriteria/Indikator', 'Nilai', 'Keterangan'];

        foreach ($headerLabels as $i => $label) {
            $this->Cell($headerWidths[$i], 8, $label, 1, ($i == count($headerLabels) - 1) ? 1 : 0, 'C', true);
        }

        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $groupedFaktor12Data = [];
        foreach ($faktor12Data as $row) {
            $sphCategory = $row['sph'] ?? 'UNKNOWN';
            $groupedFaktor12Data[$sphCategory][] = $row;
        }

        $categoryDisplayLabels = [
            'Struktur' => 'Struktur dan Infrastruktur Tata Kelola (S)',
            'Proses' => 'Proses Penerapan Tata Kelola (P)',
            'Hasil' => 'Hasil Penerapan Tata Kelola (H)',
            'UNKNOWN' => 'Kategori Tidak Dikenal',
        ];

        $orderedCategories = ['Struktur', 'Proses', 'Hasil', 'UNKNOWN'];

        $categoryCounter = 0;

        foreach ($orderedCategories as $sphKey) {
            if (isset($groupedFaktor12Data[$sphKey])) {
                $categoryCounter++;
                $categoryLabel = chr(64 + $categoryCounter) . '. ' . ($categoryDisplayLabels[$sphKey] ?? $sphKey);

                $this->checkPageBreak(8);

                $this->SetFillColor(180, 180, 180);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(array_sum($headerWidths), 8, $categoryLabel, 1, 1, 'L', true);
                $this->SetFont('Arial', '', 11);

                $no = 1;
                foreach ($groupedFaktor12Data[$sphKey] as $faktor12Row) {
                    $nilaifaktor12Row = null;
                    foreach ($nilaifaktor12Data as $nilaifaktor12) {
                        if (
                            $nilaifaktor12['faktor12id'] == $faktor12Row['id'] &&
                            $nilaifaktor12['kodebpr'] == $this->kodebpr &&
                            $nilaifaktor12['periode_id'] == $this->periodeId
                        ) {
                            $nilaifaktor12Row = $nilaifaktor12;
                            break;
                        }
                    }

                    if (!$nilaifaktor12Row) {
                        continue;
                    }

                    $subCategory12 = $faktor12Row['sub_category'] ?? 'N/A';
                    $nilai = $nilaifaktor12Row['nilai'] ?? 'N/A';
                    $keterangan = $nilaifaktor12Row['keterangan'] ?? 'N/A';

                    $lineHeight = 8;

                    $nbSubCat = $this->NbLines($headerWidths[1], $subCategory12);
                    $nbKeterangan = $this->NbLines($headerWidths[3], $keterangan);
                    $maxNbLines = max($nbSubCat, $nbKeterangan, 1);
                    $rowHeight = $lineHeight * $maxNbLines;

                    $this->checkPageBreak($rowHeight);

                    $startX = $this->GetX();
                    $startY = $this->GetY();

                    $this->MultiCell($headerWidths[0], $lineHeight, $faktor12Row['id'], 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0], $startY);
                    $this->MultiCell($headerWidths[1], $lineHeight, $subCategory12, 0, 'L', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1], $startY);
                    $this->MultiCell($headerWidths[2], $lineHeight, $nilai, 0, 'C', false);
                    $this->SetXY($startX + $headerWidths[0] + $headerWidths[1] + $headerWidths[2], $startY);
                    $this->MultiCell($headerWidths[3], $lineHeight, $keterangan, 0, 'L', false);

                    $this->Rect($startX, $startY, array_sum($headerWidths), $rowHeight);
                    $currentX = $startX;
                    for ($i = 0; $i < count($headerWidths) - 1; $i++) {
                        $currentX += $headerWidths[$i];
                        $this->Line($currentX, $startY, $currentX, $startY + $rowHeight);
                    }

                    $this->SetY($startY + $rowHeight);
                }
            }
        }
    }

    public function generateKesimpulanFaktor12(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1);
        $tableWidth = 190;
        $lineHeight = 6;

        $nilaiFaktor = $infoData['nfaktor12'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $penjelasanFaktor = $infoData['penjelasfaktor'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 12);
        $this->MultiCell($tableWidth, 10, 'Kesimpulan Penilaian Faktor 12 (Rencana Bisnis BPR)', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Nilai Faktor', 1, 1, 'C', true);

        $displayNilai = $nilaiFaktor;
        $this->SetFillColor(199, 230, 230);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, $displayNilai, 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($tableWidth, 8, 'Penjelasan Nilai Faktor', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $penjelasanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $penjelasanFaktor, 1, 'J', true);
    }

    public function generateAnalisaFaktor12(array $infoData)
    {
        $this->AddPage();
        $this->Ln(1); // Spasi sebelum tabel baru
        $tableWidth = 190;
        $lineHeight = 6;

        // Estimasi tinggi untuk page break
        $estimatedHeight = 10; // For title
        $estimatedHeight += 10 * 3; // For 3 main categories (A, B, C)
        $estimatedHeight += $lineHeight * 6 * 2; // For positive/negative in each category (rough estimate)
        $this->checkPageBreak($estimatedHeight);

        // Judul Tabel
        $this->SetFillColor(20, 24, 99); // Dark blue header background
        $this->SetTextColor(255); // White text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif (Faktor 12)', 1, 1, 'C', true); // Factor 12 Title
        $this->SetTextColor(0); // Kembali ke teks hitam

        // Definisikan lebar kolom
        $col1Width = 8; // Kolom "No"
        $col2Width = $tableWidth - $col1Width; // Kolom "Analisa Faktor Positif dan Negatif"

        // Bagian A. Struktur dan Infrastruktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'A. Struktur dan Infrastruktur', '1', 1, 'L'); // Border Left/Right

        // Faktor Positif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifStrukturText = $infoData['positifstruktur'] ?? 'Tidak ada data faktor positif struktur faktor 12.';
        $nbLinesPositifStruktur = $this->NbLines($tableWidth, $positifStrukturText); // Calculate lines for full tableWidth
        $heightPositifStrukturContent = max(1, $nbLinesPositifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightPositifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifStrukturText, '1', 'J', false);

        // Faktor Negatif Struktur
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifStrukturText = $infoData['negatifstruktur'] ?? 'Tidak ada data faktor negatif struktur faktor 12.';
        $nbLinesNegatifStruktur = $this->NbLines($tableWidth, $negatifStrukturText);
        $heightNegatifStrukturContent = max(1, $nbLinesNegatifStruktur) * $lineHeight;

        // Check for page break BEFORE drawing the MultiCell content
        $this->checkPageBreak($heightNegatifStrukturContent);

        // Draw the MultiCell content
        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifStrukturText, '1', 'J', false);

        // --- Bagian B: Proses Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'B. Proses Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifProsesText = $infoData['positifproses'] ?? 'Tidak ada data faktor positif proses faktor 12.';
        $nbLinesPositifProses = $this->NbLines($tableWidth, $positifProsesText);
        $heightPositifProsesContent = max(1, $nbLinesPositifProses) * $lineHeight;

        $this->checkPageBreak($heightPositifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifProsesText, '1', 'J', false);

        // Faktor Negatif Proses
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifProsesText = $infoData['negatifproses'] ?? 'Tidak ada data faktor negatif proses faktor 12.';
        $nbLinesNegatifProses = $this->NbLines($tableWidth, $negatifProsesText);
        $heightNegatifProsesContent = max(1, $nbLinesNegatifProses) * $lineHeight;

        $this->checkPageBreak($heightNegatifProsesContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifProsesText, '1', 'J', false);

        // --- Bagian C: Hasil Penerapan Tata Kelola ---
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak(7 + 5);
        $this->Cell($tableWidth, $lineHeight, 'C. Hasil Penerapan Tata Kelola', '1', 1, 'L');

        // Faktor Positif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '1)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Positif', '1', 1, 'L');

        $positifHasilText = $infoData['positifhasil'] ?? 'Tidak ada data faktor positif hasil faktor 12.';
        $nbLinesPositifHasil = $this->NbLines($tableWidth, $positifHasilText);
        $heightPositifHasilContent = max(1, $nbLinesPositifHasil) * $lineHeight;

        $this->checkPageBreak($heightPositifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $positifHasilText, '1', 'J', false);

        // Faktor Negatif Hasil
        $this->SetFont('Arial', 'B', 11);
        $this->checkPageBreak($lineHeight);
        $this->Cell($col1Width, $lineHeight, '2)', '1', 0, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', '1', 1, 'L');

        $negatifHasilText = $infoData['negatifhasil'] ?? 'Tidak ada data faktor negatif hasil faktor 12.';
        $nbLinesNegatifHasil = $this->NbLines($tableWidth, $negatifHasilText);
        $heightNegatifHasilContent = max(1, $nbLinesNegatifHasil) * $lineHeight;

        $this->checkPageBreak($heightNegatifHasilContent);

        $this->SetFont('Arial', '', 10);
        $this->MultiCell($tableWidth, $lineHeight, $negatifHasilText, '1', 'J', false);

        // Garis bawah penutup tabel
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C'); // Top border only
    }

    public function getNfaktorData($kodebpr, $periodeId)
    {
        // Inisialisasi array untuk menampung data nfaktor
        $nfaktorData = [];

        // Menarik data nfaktor dari berbagai tabel model yang relevan
        // IMPORTANT: The keys here will be used to store the values in $nfaktorData
        // and should match what you intend to access in generateKesimpulanSeluruhFaktor.
        $models = [
            'M_nilaifaktor' => 'nfaktor',    // Stores as 'nfaktor'
            'M_nilaifaktor2' => 'nfaktor2',  // Stores as 'nfaktor2'
            'M_nilaifaktor3' => 'nfaktor3',
            'M_nilaifaktor4' => 'nfaktor4',
            'M_nilaifaktor5' => 'nfaktor5',
            'M_nilaifaktor6' => 'nfaktor6',
            'M_nilaifaktor7' => 'nfaktor7',
            'M_nilaifaktor8' => 'nfaktor8',
            'M_nilaifaktor9' => 'nfaktor9',
            'M_nilaifaktor10' => 'nfaktor10',
            'M_nilaifaktor11' => 'nfaktor11',
            'M_nilaifaktor12' => 'nfaktor12',
        ];

        // Loop untuk mengambil data nfaktor dari setiap model
        foreach ($models as $model => $keyToStore) { // Use $keyToStore for clarity
            $modelInstance = new $model();

            // Ambil data berdasarkan kodebpr dan periode_id
            $data = $modelInstance
                ->where('kodebpr', $kodebpr)
                ->where('periode_id', $periodeId)
                ->first();

            // Simpan nilai nfaktor ke dalam array menggunakan keyToStore
            $nfaktorData[$keyToStore] = $data['nfaktor'] ?? 'Belum Dinilai'; // Default 'Belum Dinilai' jika tidak ada data
        }

        // Kembalikan array nfaktor
        return $nfaktorData;
    }
    public function generateKesimpulanSeluruhFaktor(array $infoData)
    {
        // --- Main Title (Kesimpulan Akhir) ---
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 20);
        $this->MultiCell(190, 20, 'Kesimpulan Akhir', 0, 'C', true);

        // --- Table Headers ---
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);

        $columnWidths = [15, 120, 55];
        $columnLabels = ['No', 'Kriteria / Indikator', 'Nilai Faktor'];

        foreach ($columnLabels as $index => $label) {
            $this->Cell($columnWidths[$index], 8, $label, 1, ($index == count($columnLabels) - 1) ? 1 : 0, 'C', true);
        }
        $this->SetFillColor(255, 255, 255);

        // --- Data Rows (Factors) ---
        $this->SetFont('Arial', '', 10);

        // Define descriptive labels for each factor
        $factorDescriptions = [
            1 => 'Aspek Pemegang Saham',
            2 => 'Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Direksi',
            3 => 'Pelaksanaan Tugas, Tanggung Jawab, dan Wewenang Dewan Komisaris',
            4 => 'Kelengkapan dan Pelaksanaan Tugas Komite',
            5 => 'Penanganan Benturan Kepentingan',
            6 => 'Penerapan Fungsi Kepatuhan',
            7 => 'Penerapan Fungsi Audit Intern',
            8 => 'Penerapan Fungsi Audit Extern',
            9 => 'Penerapan Manajemen Risiko dan Strategi Anti Fraud',
            10 => 'Batas Maksimum Pemberian Kredit',
            11 => 'Integritas Pelaporan dan Sistem Teknologi Informasi',
            12 => 'Rencana Bisnis BPR',
        ];

        $no = 1;
        $lineHeight = 7;

        for ($i = 1; $i <= 12; $i++) {
            // Construct the key based on the factor number, with 'nfaktor' for the first one
            $faktorKey = ($i == 1) ? 'nfaktor' : 'nfaktor' . $i;

            $label = $factorDescriptions[$i] ?? 'Faktor ' . $i; // Get descriptive label or default
            $nilaiFaktor = $infoData[$faktorKey] ?? 'Belum diisi'; // Retrieve value using the specific key

            // Initialize color and label
            $color = [255, 255, 255]; // Default to white (no color)
            $keterangan = 'Belum diisi'; // Default message

            // Determine the fill color and keterangan based on nilaiFaktor
            switch ($nilaiFaktor) {
                case 1:
                    $keterangan = ' (Sangat Baik)';
                    $color = [133, 193, 233]; // Blue
                    break;
                case 2:
                    $keterangan = ' (Baik)';
                    $color = [130, 224, 170]; // Green
                    break;
                case 3:
                    $keterangan = ' (Cukup)';
                    $color = [247, 220, 111]; // Yellow
                    break;
                case 4:
                    $keterangan = ' (Tidak Baik)';
                    $color = [241, 148, 138]; // Red
                    break;
                case 5:
                    $keterangan = ' (Buruk)';
                    $color = [44, 62, 80]; // Black
                    break;
                default:
                    $keterangan = ' (Belum diisi)';
                    $color = [255, 255, 255]; // White (no color)
                    break;
            }

            $nbKriteriaLines = $this->NbLines($columnWidths[1], $label);
            $rowHeight = $lineHeight * $nbKriteriaLines;

            $this->checkPageBreak($rowHeight);

            $startX = $this->GetX();
            $startY = $this->GetY();

            // Cell 1: No
            $this->SetTextColor(0);
            $this->MultiCell($columnWidths[0], $lineHeight, $no, 1, 'C', false);
            $this->SetXY($startX + $columnWidths[0], $startY);

            // Cell 2: Kriteria/Indikator
            $this->MultiCell($columnWidths[1], $lineHeight, $label, 1, 'L', false);
            $this->SetXY($startX + $columnWidths[0] + $columnWidths[1], $startY);

            // Cell 3: Nilai Faktor
            $this->SetFillColor($color[0], $color[1], $color[2]);
            $this->MultiCell($columnWidths[2], $lineHeight, 'Nilai ' . $nilaiFaktor . $keterangan, 1, 'C', true); // 'true' enables the fill color
            $this->SetFillColor(255, 255, 255); // Reset fill color to white for other cells

            $this->SetY($startY + $rowHeight);
            $no++;
        }

        // --- Nilai Komposit ---
        $showfaktorSummaryAndAnalysisData = $infoData['nilaikomposit'] ?? 'Belum diisi';
        $kompositColor = [255, 255, 255]; // Default to white (no color)
        $kompositKeterangan = 'Belum diisi'; // Default message

        // Determine the fill color and keterangan based on nilaiFaktor for Nilai Komposit
        switch ($showfaktorSummaryAndAnalysisData) {
            case 1:
                $kompositKeterangan = ' Sangat Baik';
                $kompositColor = [133, 193, 233]; // Blue
                break;
            case 2:
                $kompositKeterangan = ' Baik';
                $kompositColor = [130, 224, 170]; // Green
                break;
            case 3:
                $kompositKeterangan = ' Cukup';
                $kompositColor = [247, 220, 111]; // Yellow
                break;
            case 4:
                $kompositKeterangan = ' Tidak Baik';
                $kompositColor = [241, 148, 138]; // Red
                break;
            case 5:
                $kompositKeterangan = ' Buruk';
                $kompositColor = [44, 62, 80]; // Black
                break;
            default:
                $kompositKeterangan = ' Belum diisi';
                $kompositColor = [255, 255, 255]; // White (no color)
                break;
        }

        //NILAI KOMPOSIT
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(255);
        $this->SetFillColor(20, 24, 99);

        $this->Cell($columnWidths[0] + $columnWidths[1], 8, 'Nilai Komposit', 1, 0, 'C', true);

        // Apply the same coloring to the Nilai Komposit cell as we did for Nilai Faktor
        $this->SetTextColor(0);
        $this->SetFillColor($kompositColor[0], $kompositColor[1], $kompositColor[2]);
        $this->Cell($columnWidths[2], 8, $showfaktorSummaryAndAnalysisData, 1, 1, 'C', true); // 'true' enables the fill color

        $this->SetFillColor(255, 255, 255); // Reset fill color to white for other cells

        $this->SetFont('Arial', '', 10);

        //PERINGKAT KOMPOSIT
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(255);
        $this->SetFillColor(20, 24, 99);

        $this->Cell($columnWidths[0] + $columnWidths[1], 8, 'Peringkat Komposit', 1, 0, 'C', true);

        // Apply the same coloring to the Nilai Komposit cell as we did for Nilai Faktor
        $this->SetTextColor(0);
        $this->SetFillColor($kompositColor[0], $kompositColor[1], $kompositColor[2]);
        $this->Cell($columnWidths[2], 8, $kompositKeterangan, 1, 1, 'C', true); // 'true' enables the fill color

        $this->SetFillColor(255, 255, 255); // Reset fill color to white for other cells

        $this->SetFont('Arial', '', 10);

        //
        $this->Ln(15);
        $tableWidth = 190;
        $lineHeight = 6;

        // $nilaiFaktor = $infoData['nfaktor12'] ?? 'Belum Dinilai'; // Using nfaktor from infoData
        $kesimpulanFaktor = $infoData['kesimpulan'] ?? 'Tidak ada penjelasan.';

        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($tableWidth, 10, 'Kesimpulan Penilaian Self Assessment', 1, 1, 'C', true);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $kesimpulanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $kesimpulanFaktor, 1, 'J', true);

    }

    public function generateKesimpulanKomposit(array $infoData)
    {
        // $this->AddPage();

    }

    public function generateAnalisaSeluruhFaktor(array $infoData)
    {
        $this->AddPage(); // Always start this section on a new page as it's a major section.
        $this->Ln(1); // Small margin from the top.

        $tableWidth = 190;
        $lineHeight = 6;

        // Analisa Faktor Positif dan Negatif Kesimpulan
        $this->SetFillColor(20, 24, 99);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Analisa Faktor Positif dan Negatif Keseluruhan Faktor', 1, 1, 'C', true);
        $this->SetTextColor(0); // Reset text color to black after the header.

        $col1Width = 8;
        $col2Width = $tableWidth - $col1Width;

        // Define the data structure for easier iteration and better readability
        $analysisSections = [
            'A. Struktur dan Infrastruktur' => [
                'positif_key' => 'positifstruktur',
                'negatif_key' => 'negatifstruktur',
                'positif_default' => 'Tidak ada data faktor positif struktur.',
                'negatif_default' => 'Tidak ada data faktor negatif struktur.',
            ],
            'B. Proses Penerapan Tata Kelola' => [
                'positif_key' => 'positifproses',
                'negatif_key' => 'negatifproses',
                'positif_default' => 'Tidak ada data faktor positif proses.',
                'negatif_default' => 'Tidak ada data faktor negatif proses.',
            ],
            'C. Hasil Penerapan Tata Kelola' => [
                'positif_key' => 'positifhasil',
                'negatif_key' => 'negatifhasil',
                'positif_default' => 'Tidak ada data faktor positif hasil.',
                'negatif_default' => 'Tidak ada data faktor negatif hasil.',
            ],
        ];

        foreach ($analysisSections as $title => $keys) {
            // --- Category Header ---
            // Calculate estimated height for the category header itself (1 line)
            $this->checkPageBreak($lineHeight);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell($tableWidth, $lineHeight, $title, '1', 1, 'L');

            // --- Faktor Positif Section ---
            $positiveText = $infoData[$keys['positif_key']] ?? $keys['positif_default'];
            $nbLinesPositive = $this->NbLines($col2Width, $positiveText);
            $heightPositive = $nbLinesPositive * $lineHeight;

            // Check if there's enough space for the "Faktor Positif" label + content
            $this->checkPageBreak($lineHeight + $heightPositive);

            // Print "Faktor Positif" label
            $this->SetFont('Arial', 'B', 11);
            $this->Cell($col1Width, $lineHeight, '1)', 'RLT', 0, 'C');
            $this->Cell($col2Width, $lineHeight, 'Faktor Positif', 'RT', 1, 'L');

            // Store current position for drawing rectangle later
            $startX = $this->GetX();
            $startY = $this->GetY();

            // Print the positive factor content
            $this->SetFont('Arial', '', 10);
            $this->MultiCell($col2Width, $lineHeight, $positiveText, '', 'J', false);

            // Draw rectangle for the positive factor content.
            // We use $this->GetY() - $startY to get the actual height consumed by MultiCell.
            $this->Rect($startX, $startY, $tableWidth, $this->GetY() - $startY);

            // --- Faktor Negatif Section ---
            $negativeText = $infoData[$keys['negatif_key']] ?? $keys['negatif_default'];
            $nbLinesNegative = $this->NbLines($col2Width, $negativeText);
            $heightNegative = $nbLinesNegative * $lineHeight;

            // Check if there's enough space for the "Faktor Negatif" label + content
            $this->checkPageBreak($lineHeight + $heightNegative);

            // Print "Faktor Negatif" label
            $this->SetFont('Arial', 'B', 11);
            $this->Cell($col1Width, $lineHeight, '2)', 'RLT', 0, 'C');
            $this->Cell($col2Width, $lineHeight, 'Faktor Negatif', 'RLT', 1, 'L');

            // Store current position for drawing rectangle later
            $startX = $this->GetX();
            $startY = $this->GetY();

            // Print the negative factor content
            $this->SetFont('Arial', '', 10);
            $this->MultiCell($col2Width, $lineHeight, $negativeText, '', 'J', false);

            // Draw rectangle for the negative factor content.
            $this->Rect($startX, $startY, $tableWidth, $this->GetY() - $startY);
        }

        // Draw the final bottom border for the entire table.
        $this->Cell($tableWidth, 0, '', 'T', 1, 'C');
    }

    public function generatePersetujuanLembar(array $infoData)
    {
        $this->AddPage(); // Tambah halaman baru
        $this->Ln(10); // Jarak dari atas halaman

        // Mengambil data dari array infoData
        $bprName = $this->headerData['namabpr'] ?? 'Nama BPR Tidak Tersedia';
        $dirut = $infoData['dirut'] ?? 'Direktur Utama Tidak Diketahui';
        $komut = $infoData['komut'] ?? 'Komisaris Utama Tidak Diketahui';
        $lokasi = $infoData['lokasi'] ?? 'Lokasi Tidak Diketahui';
        setlocale(LC_TIME, 'id_ID.utf8', 'ind');
        $tanggal = isset($infoData['tanggal']) ? strftime('%d %B %Y', strtotime($infoData['tanggal'])) : 'Tanggal Tidak Diketahui';
        $kesimpulanFaktor = $infoData['kesimpulan'] ?? 'Tidak ada penjelasan.';

        $tableWidth = 190; // Lebar tabel
        $lineHeight = 6; // Tinggi baris

        // Judul Halaman
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 20);
        $this->MultiCell($tableWidth, 10, 'Lembar Persetujuan', 0, 'C', true);

        // Baris kosong
        $this->Ln(15);

        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 11);

        $effectiveTextWidth = $tableWidth;
        $penjelasanLines = $this->NbLines($effectiveTextWidth, $kesimpulanFaktor);
        $penjelasanHeight = $penjelasanLines * $lineHeight;

        $this->checkPageBreak($penjelasanHeight + $lineHeight);

        $this->MultiCell($tableWidth, $lineHeight, $kesimpulanFaktor, 0, 'C', true);

        // Menambahkan tanda tangan
        $this->Ln(20); // Jarak antara informasi dan tempat tanda tangan

        // Menambahkan tempat untuk tanda tangan
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($tableWidth, $lineHeight, $lokasi . ', ' . $tanggal, 0, 1, 'C');

        $this->Cell($tableWidth, $lineHeight, $bprName, 0, 1, 'C');

        $this->Ln(45); // Jarak antara tanda tangan

        // Menambahkan tempat untuk nama dan tanggal tanda tangan
        $this->SetFont('Arial', 'BU', 12); // 'B' untuk bold dan 'U' untuk underline
        $this->Cell($tableWidth / 2, $lineHeight, $dirut, 0, 0, 'C');

        $this->Cell($tableWidth / 2, $lineHeight, $komut, 0, 1, 'C');

        $this->SetFont('Arial', '', 12);
        $this->Cell($tableWidth / 2, $lineHeight, 'Direktur Utama', 0, 0, 'C');
        $this->Cell($tableWidth / 2, $lineHeight, 'Komisaris Utama', 0, 1, 'C');
    }

    public function mergeExistingPdf($filepath)
    {
        try {
            if (!file_exists($filepath)) {
                log_message('error', 'File not found: ' . $filepath);
                return false;
            }

            $pageCount = $this->setSourceFile($filepath);
            log_message('debug', 'Found ' . $pageCount . ' pages in: ' . $filepath);

            // Matikan header/footer untuk halaman yang diimpor
            $this->isCoverPage = true; // Skip header/footer
            $this->isCoverFooterPage = true;

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                try {
                    $templateId = $this->importPage($pageNo);
                    $size = $this->getTemplateSize($templateId);

                    // Tambahkan halaman baru tanpa header/footer
                    $this->AddPage(
                        $size['width'] > $size['height'] ? 'L' : 'P',
                        [$size['width'], $size['height']]
                    );

                    // Tempelkan konten PDF asli
                    $this->useTemplate($templateId);
                } catch (\Exception $e) {
                    log_message('error', 'Error merging page ' . $pageNo . ': ' . $e->getMessage());
                    continue;
                }
            }

            return $pageCount;
        } catch (\Exception $e) {
            log_message('error', 'PDF merge failed: ' . $e->getMessage());
            return false;
        }
    }

    // Improved NbLines calculation with word wrapping
    private function NbLines($w, $txt)
    {
        if (empty($txt))
            return 1;

        $words = explode(' ', $txt);
        $lineWidth = 0;
        $lines = 1;
        $maxWidth = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;

        foreach ($words as $word) {
            $wordWidth = 0;
            // Calculate word width
            for ($i = 0; $i < mb_strlen($word); $i++) {
                $char = mb_substr($word, $i, 1);
                $wordWidth += $this->CurrentFont['cw'][$char] ?? 500; // Default width if char not found
            }

            if ($lineWidth + $wordWidth > $maxWidth) {
                $lines++;
                $lineWidth = $wordWidth + ($wordWidth > 0 ? $this->CurrentFont['cw'][' '] : 0);
            } else {
                $lineWidth += $wordWidth + ($wordWidth > 0 ? $this->CurrentFont['cw'][' '] : 0);
            }
        }

        return max($lines, 1);
    }

    // Header function
    public function Header()
    {
        if ($this->isCoverPage) {
            return; // Skip header untuk halaman cover
        }

        // Fungsi untuk menampilkan header setelah halaman cover
        if (!empty($this->headerData)) {
            if (!empty($this->headerData['logo']) && file_exists($this->headerData['logo'])) {
                try {
                    $this->Image($this->headerData['logo'], 10, 8, 40); // Menampilkan logo
                } catch (Exception $e) {
                    log_message('error', 'Failed to load logo: ' . $e->getMessage());
                }
            }

            $this->SetFont('Arial', 'B', 17);
            $pageWidth = $this->GetPageWidth();
            $rightMargin = 10;

            // Tampilkan nama BPR di kanan atas
            $this->SetX($pageWidth - $this->GetStringWidth($this->headerData['namabpr'] ?? '') - $rightMargin);
            $this->Cell(0, 5, $this->headerData['namabpr'] ?? '', 0, 1);

            // Tampilkan alamat BPR
            $this->SetFont('Arial', '', 9);
            $this->SetX($pageWidth - $this->GetStringWidth($this->headerData['alamat'] ?? '') - $rightMargin);
            $this->Cell(0, 5, $this->headerData['alamat'] ?? '', 0, 1);

            // Tampilkan nomor telepon
            $this->SetX($pageWidth - $this->GetStringWidth('Telp: ' . $this->headerData['nomor'] ?? '') - $rightMargin);
            $this->Cell(0, 5, 'Telp: ' . $this->headerData['nomor'] ?? '', 0, 1);

            // Tampilkan website dan email
            $contactInfo = sprintf(
                'Website: %s | Email: %s',
                $this->headerData['webbpr'] ?? '',
                $this->headerData['email'] ?? ''
            );
            $this->SetX($pageWidth - $this->GetStringWidth($contactInfo) - $rightMargin);
            $this->Cell(0, 5, $contactInfo, 0, 1);

            $this->Ln(5); // Menambah jarak di bawah header
        }
    }

    // Footer function
    public function Footer()
    {
        if ($this->isCoverFooterPage) {
            return; // Skip footer untuk halaman cover
        }

        // Fungsi untuk menampilkan footer setelah halaman cover
        $this->SetY(-12); // Menentukan posisi footer 12mm dari bawah
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(0); // Set text color to black untuk footer

        // Dynamic footer content
        $bprName = $this->headerData['namabpr'] ?? 'Nama BPR Tidak Tersedia';
        $periodeYear = isset($this->headerData['tahun']) ? $this->headerData['tahun'] : date('Y');
        $periodeSemester = isset($this->headerData['semester']) && in_array($this->headerData['semester'], [1, 2]) ? $this->headerData['semester'] : 1;

        // Footer text
        $footerText = 'Laporan Self Assessment Tata Kelola ' . $bprName . ' Periode: Semester ' . $periodeSemester . ' Tahun ' . $periodeYear;


        // Calculate text width and center position
        $textWidth = $this->GetStringWidth($footerText);
        $pageWidth = $this->GetPageWidth();
        $centerPos = ($pageWidth - $textWidth) / 2;

        $this->SetX($centerPos); // Set posisi X untuk footer
        $this->Cell($textWidth, 10, $footerText, 0, 0, 'C');

        $this->SetY(-12); // Posisi footer di kanan bawah tetap 12mm dari bawah
        $this->SetX(-50); // Set posisi X agar berada di kanan
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'R');
    }

    // Memastikan halaman pertama tidak ada header atau footer
    public function skipHeaderFooter()
    {
        $this->isCoverPage = true;
        $this->AddPage(); // Halaman pertama (cover)
        // Simpan perubahan header/footer saat halaman pertama
        $this->isCoverPage = false;
    }


}