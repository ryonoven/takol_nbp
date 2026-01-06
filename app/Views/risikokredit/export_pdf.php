<!DOCTYPE html>
<html lang="id">

<div id="fileListContainer" style="display: none;">
    <div class="file-list" id="fileList"></div>
</div>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export PDF - Risiko Kredit (Inheren + KPMR)</title>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .loading-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            color: white;
            margin-top: 20px;
            font-size: 18px;
        }

        .progress-bar-custom {
            width: 300px;
            height: 20px;
            background: #333;
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="loading-container" id="loadingContainer">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">Mempersiapkan data...</div>
        <div class="progress-bar-custom">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= base_url() ?>';

        function updateProgress(percent, text) {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('loadingText').textContent = text;
        }

        function sanitizeText(text) {
            if (!text) return text;
            return String(text)
                .replace(/≥/g, '>=')
                .replace(/≤/g, '<=')
                .replace(/×/g, 'x')
                .replace(/÷/g, '/')
                .replace(/–/g, '-')
                .replace(/—/g, '-')
                .replace(/[^\x00-\xFF]/g, '');
        }

        // Tambahkan fungsi baru khusus untuk child row Likuiditas Inheren (dengan rasio)
        function drawChildRowLikuiditasInheren(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterLikuiditasName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Rasio (TAMBAHKAN INI)
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 8);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - 15, size: 8, font
            });
            xPos += columnWidths[4];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[6], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        async function generatePDFGabungan() {
            try {
                updateProgress(5, 'Mengambil data dari server...');

                // Fetch data Risiko Kredit
                const responseKredit = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabungan`);
                const resultKredit = await responseKredit.json();

                if (resultKredit.status !== 'success') {
                    throw new Error('Gagal mengambil data kredit');
                }

                // Fetch data Risiko Operasional
                const responseOperasional = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganOperasional`);
                const resultOperasional = await responseOperasional.json();

                if (resultOperasional.status !== 'success') {
                    throw new Error('Gagal mengambil data operasional');
                }

                const responseKepatuhan = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganKepatuhan`);
                const resultKepatuhan = await responseKepatuhan.json();

                if (resultKepatuhan.status !== 'success') {
                    throw new Error('Gagal mengambil data kepatuhan');
                }

                const responseLikuiditas = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganLikuiditas`);
                const resultLikuiditas = await responseLikuiditas.json();

                if (resultLikuiditas.status !== 'success') {
                    throw new Error('Gagal mengambil data likuiditas');
                }

                const dataKredit = resultKredit.data;
                const dataOperasional = resultOperasional.data;
                const dataKepatuhan = resultKepatuhan.data;
                const dataLikuiditas = resultLikuiditas.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // ===== RISIKO KREDIT INHEREN =====
                updateProgress(25, 'Membuat halaman Risiko Kredit Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataKredit, width, height, 'INHEREN');

                updateProgress(35, 'Menambahkan data Kredit Inheren...');

                await drawTableInheren(pdfDoc, currentPage, fontBold, fontRegular, dataKredit.inheren, dataKredit.periode, dataKredit.bpr, width, height);

                // ===== RISIKO KREDIT KPMR =====
                updateProgress(45, 'Membuat halaman Risiko Kredit KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataKredit, width, height, 'KPMR');

                updateProgress(55, 'Menambahkan data Kredit KPMR...');

                await drawTableKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKredit.kpmr, dataKredit.periode, dataKredit.bpr, width, height);

                // ===== RISIKO OPERASIONAL INHEREN =====
                updateProgress(65, 'Membuat halaman Risiko Operasional Inheren...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataOperasional, width, height, 'OPERASIONAL_INHEREN');

                updateProgress(75, 'Menambahkan data Operasional Inheren...');

                await drawTableOperasionalInheren(pdfDoc, currentPage, fontBold, fontRegular, dataOperasional.inheren, dataOperasional.periode, dataOperasional.bpr, width, height);

                // ===== RISIKO OPERASIONAL KPMR =====
                updateProgress(80, 'Membuat halaman Risiko Operasional KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataOperasional, width, height, 'OPERASIONAL_KPMR');

                updateProgress(85, 'Menambahkan data Operasional KPMR...');

                await drawTableOperasionalKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataOperasional.kpmr, dataOperasional.periode, dataOperasional.bpr, width, height);

                // ===== RISIKO KEPATUHAN INHEREN ===== ✅
                updateProgress(87, 'Membuat halaman Risiko Kepatuhan Inheren...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataKepatuhan, width, height, 'KEPATUHAN_INHEREN');  // ✅ Perbaiki

                updateProgress(89, 'Menambahkan data Kepatuhan Inheren...');

                await drawTableKepatuhanInheren(pdfDoc, currentPage, fontBold, fontRegular, dataKepatuhan.inheren, dataKepatuhan.periode, dataKepatuhan.bpr, width, height);

                // ===== RISIKO KEPATUHAN KPMR ===== ✅
                updateProgress(91, 'Membuat halaman Risiko Kepatuhan KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataKepatuhan, width, height, 'KEPATUHAN_KPMR');  // ✅ Perbaiki

                updateProgress(93, 'Menambahkan data Kepatuhan KPMR...');

                await drawTableKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKepatuhan.kpmr, dataKepatuhan.periode, dataKepatuhan.bpr, width, height);

                // ===== RISIKO LIKUIDITAS INHEREN ===== ✅
                updateProgress(95, 'Membuat halaman Risiko Likuiditas Inheren...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataLikuiditas, width, height, 'LIKUIDITAS_INHEREN');  // ✅ Perbaiki dari 'KEPATUHAN_INHEREN'

                updateProgress(96, 'Menambahkan data Likuiditas Inheren...');

                await drawTableLikuiditas(pdfDoc, currentPage, fontBold, fontRegular, dataLikuiditas.inheren, dataLikuiditas.periode, dataLikuiditas.bpr, width, height);

                // ===== RISIKO LIKUIDITAS KPMR ===== ✅
                updateProgress(97, 'Membuat halaman Risiko Likuiditas KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataLikuiditas, width, height, 'LIKUIDITAS_KPMR');  // ✅ Perbaiki dari 'KEPATUHAN_KPMR'

                updateProgress(98, 'Menambahkan data Likuiditas KPMR...');

                await drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataLikuiditas.kpmr, dataLikuiditas.periode, dataLikuiditas.bpr, width, height);

                updateProgress(90, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                const printWindow = window.open(url, '_blank');
                if (autoGenerate) {
                    window.onload = () => {
                        setTimeout(() => {
                            generatePDF();
                        }, 500);
                    };
                }

                updateProgress(100, 'Selesai!');

                setTimeout(() => {
                    window.close();
                }, 1000);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingText').textContent = 'Error: ' + error.message;
            }
        }

        async function generatePDFKredit() {
            try {
                updateProgress(5, 'Mengambil data Risiko Kredit...');

                const responseKredit = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabungan`);
                const resultKredit = await responseKredit.json();

                if (resultKredit.status !== 'success') {
                    throw new Error('Gagal mengambil data kredit');
                }

                const dataKredit = resultKredit.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // ===== RISIKO KREDIT INHEREN =====
                updateProgress(40, 'Membuat halaman Risiko Kredit Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataKredit, width, height, 'INHEREN');

                updateProgress(60, 'Menambahkan data Kredit Inheren...');

                await drawTableInheren(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataKredit.inheren,
                    dataKredit.periode,
                    dataKredit.bpr,
                    width,
                    height
                );

                // ===== RISIKO KREDIT KPMR =====
                updateProgress(75, 'Membuat halaman Risiko Kredit KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataKredit, width, height, 'KPMR');

                updateProgress(90, 'Menambahkan data Kredit KPMR...');

                await drawTableKPMR(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataKredit.kpmr,
                    dataKredit.periode,
                    dataKredit.bpr,
                    width,
                    height
                );

                //UNTUK PRINT PREVIEW
                // updateProgress(95, 'Menyimpan dan mengunduh...');

                // const pdfBytes = await pdfDoc.save();
                // const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                // const url = URL.createObjectURL(blob);

                // const printWindow = window.open(url, '_blank');
                // if (autoGenerate) {
                //     window.onload = () => {
                //         setTimeout(() => {
                //             generatePDF();
                //         }, 500);
                //     };
                // }

                // updateProgress(100, 'Selesai!');

                // setTimeout(() => {
                //     window.close();
                // }, 1000);

                updateProgress(95, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                updateProgress(100, 'Mengunduh PDF...');

                // ✅ PERBAIKAN: Gunakan dataKredit, bukan data
                const namaBPR = dataKredit.bpr.namabpr || 'BPR';
                const semester = dataKredit.periode.semester || '1';
                const tahun = dataKredit.periode.tahun || '2024';

                const fileName = `01. Laporan Profil Risiko Kredit Semester ${semester} Tahun ${tahun}.pdf`;

                // Download PDF dengan nama file
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Close after delay
                setTimeout(() => {
                    URL.revokeObjectURL(url);
                    window.close();
                }, 2000);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingText').textContent = 'Error: ' + error.message;
            }
        }

        async function generatePDFOperasional() {
            try {
                updateProgress(5, 'Mengambil data Risiko Operasional...');

                // Fetch data Risiko Operasional Inheren
                const responseInheren = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganOperasional`);
                const resultInheren = await responseInheren.json();

                if (resultInheren.status !== 'success') {
                    throw new Error('Gagal mengambil data operasional');
                }

                const dataOperasional = resultInheren.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // ===== RISIKO OPERASIONAL INHEREN =====
                updateProgress(30, 'Membuat halaman Risiko Operasional Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataOperasional, width, height, 'OPERASIONAL_INHEREN');

                updateProgress(45, 'Menambahkan data Operasional Inheren...');

                await drawTableOperasionalInheren(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataOperasional.inheren,
                    dataOperasional.periode,
                    dataOperasional.bpr,
                    width,
                    height
                );

                // ===== RISIKO OPERASIONAL KPMR =====
                updateProgress(65, 'Membuat halaman Risiko Operasional KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataOperasional, width, height, 'OPERASIONAL_KPMR');

                updateProgress(80, 'Menambahkan data Operasional KPMR...');

                await drawTableOperasionalKPMR(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataOperasional.kpmr,
                    dataOperasional.periode,
                    dataOperasional.bpr,
                    width,
                    height
                );

                updateProgress(90, 'Menyimpan dan mengunduh...');

                updateProgress(90, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                // ✅ PERBAIKAN: Gunakan dataOperasional
                const namaBPR = dataOperasional.bpr.namabpr || 'BPR';
                const semester = dataOperasional.periode.semester || '1';
                const tahun = dataOperasional.periode.tahun || '2024';

                const fileName = `02. Laporan Profil Risiko Operasional Semester ${semester} Tahun ${tahun}.pdf`;

                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                updateProgress(100, 'Selesai!');

                setTimeout(() => {
                    URL.revokeObjectURL(url);
                    window.close();
                }, 1000);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingText').textContent = 'Error: ' + error.message;
            }
        }

        async function generatePDFKepatuhan() {
            try {
                updateProgress(5, 'Mengambil data Risiko Kepatuhan...');

                const responseKepatuhan = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganKepatuhan`);
                const resultKepatuhan = await responseKepatuhan.json();

                if (resultKepatuhan.status !== 'success') {
                    throw new Error('Gagal mengambil data kepatuhan');
                }

                const dataKepatuhan = resultKepatuhan.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // ===== RISIKO KEPATUHAN INHEREN =====
                updateProgress(35, 'Membuat halaman Risiko Kepatuhan Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataKepatuhan, width, height, 'KEPATUHAN_INHEREN');

                updateProgress(50, 'Menambahkan data Kepatuhan Inheren...');

                await drawTableKepatuhanInheren(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataKepatuhan.inheren,
                    dataKepatuhan.periode,
                    dataKepatuhan.bpr,
                    width,
                    height
                );

                // ===== RISIKO KEPATUHAN KPMR =====
                updateProgress(70, 'Membuat halaman Risiko Kepatuhan KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataKepatuhan, width, height, 'KEPATUHAN_KPMR');

                updateProgress(85, 'Menambahkan data Kepatuhan KPMR...');

                await drawTableKepatuhanKPMR(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataKepatuhan.kpmr,
                    dataKepatuhan.periode,
                    dataKepatuhan.bpr,
                    width,
                    height
                );

                updateProgress(90, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                // ✅ PERBAIKAN: Gunakan dataKepatuhan
                const namaBPR = dataKepatuhan.bpr.namabpr || 'BPR';
                const semester = dataKepatuhan.periode.semester || '1';
                const tahun = dataKepatuhan.periode.tahun || '2024';

                const fileName = `03. Laporan Profil Risiko Kepatuhan Semester ${semester} Tahun ${tahun}.pdf`;

                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                updateProgress(100, 'Selesai!');

                setTimeout(() => {
                    URL.revokeObjectURL(url);
                    window.close();
                }, 1000);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingText').textContent = 'Error: ' + error.message;
            }
        }

        async function generatePDFLikuiditas() {
            try {
                updateProgress(5, 'Mengambil data Risiko Likuiditas...');

                const responseLikuiditas = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganLikuiditas`);
                const resultLikuiditas = await responseLikuiditas.json();

                if (resultLikuiditas.status !== 'success') {
                    throw new Error('Gagal mengambil data likuiditas');
                }

                const dataLikuiditas = resultLikuiditas.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // ===== RISIKO LIKUIDITAS INHEREN =====
                updateProgress(40, 'Membuat halaman Risiko Likuiditas Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataLikuiditas, width, height, 'LIKUIDITAS_INHEREN');

                updateProgress(55, 'Menambahkan data Likuiditas Inheren...');

                await drawTableLikuiditas(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataLikuiditas.inheren,
                    dataLikuiditas.periode,
                    dataLikuiditas.bpr,
                    width,
                    height
                );

                // ===== RISIKO LIKUIDITAS KPMR =====
                updateProgress(75, 'Membuat halaman Risiko Likuiditas KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataLikuiditas, width, height, 'LIKUIDITAS_KPMR');

                updateProgress(88, 'Menambahkan data Likuiditas KPMR...');

                await drawTableLikuiditasKPMR(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataLikuiditas.kpmr,
                    dataLikuiditas.periode,
                    dataLikuiditas.bpr,
                    width,
                    height
                );

                updateProgress(95, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                // ✅ PERBAIKAN: Gunakan dataLikuiditas
                const namaBPR = dataLikuiditas.bpr.namabpr || 'BPR';
                const semester = dataLikuiditas.periode.semester || '1';
                const tahun = dataLikuiditas.periode.tahun || '2024';

                const fileName = `04. Laporan Profil Risiko Likuiditas Semester ${semester} Tahun ${tahun}.pdf`;

                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                updateProgress(100, 'Selesai!');

                setTimeout(() => {
                    URL.revokeObjectURL(url);
                    window.close();
                }, 1000);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingText').textContent = 'Error: ' + error.message;
            }
        }

        function drawHeader(page, fontBold, fontRegular, data, width, height, jenis) {
            const { rgb } = PDFLib;

            page.drawRectangle({
                x: 0,
                y: height - 80,
                width: width,
                height: 80,
                color: rgb(0.06, 0.35, 0.54)
            });

            let title = '';
            switch (jenis) {
                case 'INHEREN':
                    title = 'LAPORAN PROFIL RISIKO KREDIT INHEREN';
                    break;
                case 'KPMR':
                    title = 'LAPORAN PROFIL RISIKO KREDIT KPMR';
                    break;
                case 'OPERASIONAL_INHEREN':
                    title = 'LAPORAN PROFIL RISIKO OPERASIONAL INHEREN';
                    break;
                case 'OPERASIONAL_KPMR':
                    title = 'LAPORAN PROFIL RISIKO OPERASIONAL KPMR';
                    break;
                case 'KEPATUHAN_INHEREN':
                    title = 'LAPORAN PROFIL RISIKO KEPATUHAN INHEREN';
                    break;
                case 'KEPATUHAN_KPMR':
                    title = 'LAPORAN PROFIL RISIKO KEPATUHAN KPMR';
                    break;
                case 'LIKUIDITAS_INHEREN':
                    title = 'LAPORAN PROFIL RISIKO LIKUIDITAS INHEREN';
                    break;
                case 'LIKUIDITAS_KPMR':
                    title = 'LAPORAN PROFIL RISIKO LIKUIDITAS KPMR';
                    break;
                default:
                    title = 'LAPORAN PROFIL RISIKO';
            }

            const titleSize = 18;
            const textWidth = fontBold.widthOfTextAtSize(title, titleSize);
            const centerX = (width / 2) - (textWidth / 2);

            page.drawText(title, {
                x: centerX,
                y: height - 35,
                size: titleSize,
                font: fontBold,
                color: rgb(1, 1, 1)
            });

            const infoBPR = sanitizeText(data.bpr.namabpr) || 'N/A';
            const infoPeriode = `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`;

            const infoSize = 12;
            const bprWidth = fontRegular.widthOfTextAtSize(infoBPR, infoSize);
            const periodeWidth = fontBold.widthOfTextAtSize(infoPeriode, infoSize);

            page.drawText(infoBPR, {
                x: (width - bprWidth) / 2,
                y: height - 52,
                size: infoSize,
                font: fontRegular,
                color: rgb(1, 1, 1)
            });

            page.drawText(infoPeriode, {
                x: (width - periodeWidth) / 2,
                y: height - 68,
                size: infoSize,
                font: fontBold,
                color: rgb(1, 1, 1)
            });
        }

        // Fungsi untuk menggambar tabel KREDIT INHEREN
        async function drawTableInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Rasio', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 100, 120, 140, 60, 60, 232];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'INHEREN');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos,
                        y: yPosition - 18,
                        size: 9,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header tabel
            currentPage.drawRectangle({
                x: margin,
                y: yPosition - 25,
                width: tableWidth,
                height: 25,
                color: rgb(0.9, 0.9, 0.9),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition,
                    y: yPosition - 18,
                    size: 9,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Komposisi
            if (dataInheren.nilai.komposisi.kategori) {
                const result = drawKategori(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                    dataInheren.nilai.komposisi,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Data Kategori Kualitas
            if (dataInheren.nilai.kualitas.kategori) {
                const result = drawKategori(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kualitas Aset',
                    dataInheren.nilai.kualitas,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Single Factors
            if (dataInheren.nilai.strategi) {
                const result = drawSingleRow(currentPage, fontRegular, '3', 'Strategi Penyediaan Dana',
                    dataInheren.nilai.strategi, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
                if (result.needNewPage || yPosition < MIN_Y) yPosition = createNewPage();
            }

            if (dataInheren.nilai.eksternal) {
                const result = drawSingleRow(currentPage, fontRegular, '4', 'Faktor Eksternal',
                    dataInheren.nilai.eksternal, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
                if (result.needNewPage || yPosition < MIN_Y) yPosition = createNewPage();
            }

            if (dataInheren.nilai.lainnya) {
                const result = drawSingleRow(currentPage, fontRegular, '5', 'Faktor Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai13) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRow(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai13, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai14) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRow(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai14, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        // Fungsi untuk menggambar tabel KREDIT KPMR
        async function drawTableKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 200, 140, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'KPMR');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header
            currentPage.drawRectangle({
                x: margin, y: yPosition - 25, width: tableWidth, height: 25,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPos += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Pengawasan
            if (dataKPMR.nilai.pengawasan.kategori) {
                const result = drawKategoriKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataKPMR.nilai.pengawasan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR');
                        const newY = dims.height - 120;
                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Kebijakan
            if (dataKPMR.nilai.kebijakan.kategori) {
                const result = drawKategoriKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataKPMR.nilai.kebijakan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Proses
            if (dataKPMR.nilai.proses.kategori) {
                const result = drawKategoriKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Kecukupan Proses dan Sistem Manajemen Informasi',
                    dataKPMR.nilai.proses, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Pengendalian
            if (dataKPMR.nilai.pengendalian.kategori) {
                const result = drawKategoriKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Sistem Pengendalian Internal yang Menyeluruh',
                    dataKPMR.nilai.pengendalian, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Penilaian Risiko KPMR
            if (dataKPMR.nilai33) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai33, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataKPMR.nilai34) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai34, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        // Draw table OPERASIONAL INHEREN
        async function drawTableOperasionalInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 200, 140, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'OPERASIONAL_INHEREN');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos,
                        y: yPosition - 18,
                        size: 9,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header tabel
            currentPage.drawRectangle({
                x: margin,
                y: yPosition - 25,
                width: tableWidth,
                height: 25,
                color: rgb(0.9, 0.9, 0.9),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition,
                    y: yPosition - 18,
                    size: 9,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Kompleksitas
            if (dataInheren.nilai.kompleksitas.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Kompleksitas Bisnis dan Kelembagaan',
                    dataInheren.nilai.kompleksitas,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'OPERASIONAL_INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Data Kategori SDM
            if (dataInheren.nilai.sdm.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Sumber Daya Manusia (SDM)',
                    dataInheren.nilai.sdm,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'OPERASIONAL_INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Single Factors
            if (dataInheren.nilai.ti) {
                const result = drawSingleRowOperasional(currentPage, fontRegular, '3', 'Penyelenggaraan Teknologi Informasi (TI)',
                    dataInheren.nilai.ti, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
                if (result.needNewPage || yPosition < MIN_Y) yPosition = createNewPage();
            }

            if (dataInheren.nilai.fraud) {
                const result = drawSingleRowOperasional(currentPage, fontRegular, '4', 'Pilar Penyimpangan (Fraud)',
                    dataInheren.nilai.fraud, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
                if (result.needNewPage || yPosition < MIN_Y) yPosition = createNewPage();
            }

            if (dataInheren.nilai.eksternal) {
                const result = drawSingleRowOperasional(currentPage, fontRegular, '5', 'Faktor Eksternal',
                    dataInheren.nilai.eksternal, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
                if (result.needNewPage || yPosition < MIN_Y) yPosition = createNewPage();
            }

            if (dataInheren.nilai.lainnya) {
                const result = drawSingleRowOperasional(currentPage, fontRegular, '6', 'Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai13) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowOperasional(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai13, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai14) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowOperasional(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai14, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        // Draw table OPERASIONAL KPMR
        async function drawTableOperasionalKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 200, 140, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'OPERASIONAL_KPMR');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header
            currentPage.drawRectangle({
                x: margin, y: yPosition - 25, width: tableWidth, height: 25,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPos += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Pengawasan
            if (dataKPMR.nilai.pengawasan.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataKPMR.nilai.pengawasan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'OPERASIONAL_KPMR');
                        const newY = dims.height - 120;
                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Kebijakan
            if (dataKPMR.nilai.kebijakan.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataKPMR.nilai.kebijakan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'OPERASIONAL_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Proses
            if (dataKPMR.nilai.proses.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Kecukupan Proses dan Sistem Manajemen Informasi',
                    dataKPMR.nilai.proses, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'OPERASIONAL_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Pengendalian
            if (dataKPMR.nilai.pengendalian.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Sistem Pengendalian Internal yang Menyeluruh',
                    dataKPMR.nilai.pengendalian, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'OPERASIONAL_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Penilaian Risiko KPMR
            if (dataKPMR.nilai33) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowOperasional(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai33, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataKPMR.nilai34) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowOperasional(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai34, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        // Draw table KEPATUHAN INHEREN
        async function drawTableKepatuhanInheren(pdfDoc, currentPage, fontBold, fontRegular, dataKepatuhan, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 200, 140, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'KEPATUHAN_INHEREN');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos,
                        y: yPosition - 18,
                        size: 9,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header tabel
            currentPage.drawRectangle({
                x: margin,
                y: yPosition - 25,
                width: tableWidth,
                height: 25,
                color: rgb(0.9, 0.9, 0.9),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition,
                    y: yPosition - 18,
                    size: 9,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Pelanggaran
            if (dataKepatuhan.nilai.pelanggaran.kategori) {
                const result = drawKategoriKepatuhan(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                    dataKepatuhan.nilai.pelanggaran,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Data Kategori Hukum
            if (dataKepatuhan.nilai.hukum.kategori) {
                const result = drawKategoriKepatuhan(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Faktor kelemahan aspek hukum',
                    dataKepatuhan.nilai.hukum,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Single Factor - Lainnya
            if (dataKepatuhan.nilai.lainnya) {
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '3', 'Lainnya',
                    dataKepatuhan.nilai.lainnya, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            // Penilaian Risiko
            if (dataKepatuhan?.nilai81) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataKepatuhan.nilai81, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataKepatuhan?.nilai82) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataKepatuhan.nilai82, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        async function drawTableLikuiditas(pdfDoc, currentPage, fontBold, fontRegular, dataLikuiditas, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Rasio', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 100, 120, 140, 60, 60, 232];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'LIKUIDITAS_INHEREN');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos,
                        y: yPosition - 18,
                        size: 9,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header tabel
            currentPage.drawRectangle({
                x: margin,
                y: yPosition - 25,
                width: tableWidth,
                height: 25,
                color: rgb(0.9, 0.9, 0.9),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition,
                    y: yPosition - 18,
                    size: 9,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Komposisi
            if (dataLikuiditas.nilai.konsentrasi.kategori) {
                const result = drawKategoriLikuiditas(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Komposisi dan konsentrasi aset dan kewajiban',
                    dataLikuiditas.nilai.konsentrasi,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'LIKUIDITAS_INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Data Kategori Kualitas
            if (dataLikuiditas.nilai.kerentanan.kategori) {
                const result = drawKategoriLikuiditas(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan',
                    dataLikuiditas.nilai.kerentanan,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'LIKUIDITAS_INHEREN');
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 25, width: tableWidth, height: 25,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });

                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Single Factors
            if (dataLikuiditas.nilai.lainnya) {
                const result = drawSingleRowLikuiditas(currentPage, fontRegular, '3', 'Faktor Lainnya',
                    dataLikuiditas.nilai.lainnya, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataLikuiditas.nilai115) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditas(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataLikuiditas.nilai115, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataLikuiditas.nilai116) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditas(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataLikuiditas.nilai116, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        // ===== HELPER FUNCTIONS =====

        function wrapTextToLines(text, maxWidth, font, size) {
            if (!text) return ['-'];
            const words = String(text).split(/\s+/);
            const lines = [];
            let line = '';

            for (let i = 0; i < words.length; i++) {
                const word = words[i];
                const test = line ? (line + ' ' + word) : word;
                const testWidth = font.widthOfTextAtSize(test, size);

                if (testWidth <= maxWidth) {
                    line = test;
                } else {
                    if (line) lines.push(line);
                    if (font.widthOfTextAtSize(word, size) > maxWidth) {
                        let chunk = '';
                        for (const ch of word) {
                            const t = chunk + ch;
                            if (font.widthOfTextAtSize(t, size) <= maxWidth) {
                                chunk = t;
                            } else {
                                if (chunk) lines.push(chunk);
                                chunk = ch;
                            }
                        }
                        if (chunk) line = chunk;
                        else line = '';
                    } else {
                        line = word;
                    }
                }
            }
            if (line) lines.push(line);
            return lines;
        }

        function getRatingLabel(rating) {
            const labels = {
                '1': 'Sangat Rendah',
                '2': 'Rendah',
                '3': 'Sedang',
                '4': 'Tinggi',
                '5': 'Sangat Tinggi'
            };
            return labels[rating] || '-';
        }

        function getBackgroundColorByRating(rating) {
            const { rgb } = PDFLib;
            const colors = {
                '1': rgb(0.4, 0.7, 1),
                '2': rgb(0.4, 0.9, 0.4),
                '3': rgb(1, 0.8, 0.4),
                '4': rgb(1, 0.5, 0.5),
                '5': rgb(0.7, 0.3, 0.3)
            };
            return colors[rating] || rgb(1, 1, 1);
        }

        function getParameterName(faktorId) {
            const names = {
                2: 'Rasio aset produktif',
                3: 'Rasio kredit diberikan',
                4: 'Rasio 25 debitur terbesar',
                5: 'Rasio per sektor ekonomi',
                7: 'Rasio aset produktif bermasalah',
                8: 'Kredit bermasalah neto',
                9: 'Kredit kualitas rendah',
                16: 'Pengawasan Direksi dan Komisaris',
                17: 'Apakah Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit yang disusun oleh Direksi dan melakukan evaluasi secara berkala?'
            };
            return names[faktorId] || 'Parameter';
        }

        function getParameterKPMRName(faktorId) {
            const names = {
                16: 'Pengawasan Direksi dan Komisaris',
                17: 'Apakah Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko kredit yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                18: 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan Manajemen Risiko kredit secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?',
                19: 'Apakah Direksi telah menyusun kebijakan Manajemen Risiko kredit, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                20: 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kredit, dan melakukan komunikasi kebijakan Manajemen Risiko kredit terhadap seluruh jenjang organisasi BPR?',
                21: 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kredit dan fungsi Manajemen Risiko kredit?',
                22: 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kredit?',
                23: 'Kecukupan Kebijakan, Prosedur, dan Limit',
                24: 'Apakah BPR telah memiliki kebijakan Manajemen Risiko kredit yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                25: 'Apakah BPR:\n• Memiliki prosedur manajemen risiko kredit dan penetapan limit risiko kredit yang ditetapkan oleh Direksi;\n\n• Melaksanakan prosedur Manajemen Risiko kredit dan penetapan limit risiko kredit secara konsisten untuk seluruh aktivitas; dan\n\n• Melakukan evaluasi dan pengkinian terhadap prosedur Manajemen Risiko kredit dan penetapan limit risiko kredit secara berkala?',
                26: 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kredit sesuai dengan ketentuan?',
                27: 'Kecukupan Proses dan Sistem Manajemen Risiko',
                28: 'Apakah BPR telah melaksanakan proses Manajemen Risiko kredit yang melekat pada kegiatan usaha BPR yang terkait dengan Risiko kredit?',
                29: 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kredit serta telah dilaporkan kepada Direksi secara berkala?',
                30: 'Sistem Pengendalian Internal yang Menyeluruh',
                31: 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kredit, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                32: 'Apakah sistem pengendalian intern terhadap risiko kredit telah dilaksanakan oleh seluruh jenjang organisasi BPR?'
            };
            return names[faktorId] || 'Parameter';
        }

        function getParameterOperasionalName(faktorId) {
            const names = {
                37: 'Skala usaha dan struktur organisasi',
                38: 'Jaringan kantor, Rentang kendali dan lokasi kantor cabang',
                39: 'Keberagaman produk dan/atau jasa',
                40: 'Tindakan korporasi',
                42: 'Kecukupan kuantitas dan kualitas SDM',
                43: 'Permasalahan operasional karena faktor manusia (human error)',
                52: 'Apakah Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko operasional?',
                53: 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi?',
                54: 'Apakah Direksi telah menyusun kebijakan Manajemen Risiko operasional?',
                55: 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan mitigasi?',
                56: 'Apakah BPR telah memiliki kecukupan organisasi?',
                57: 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM?',
                59: 'Apakah BPR telah memiliki kebijakan Manajemen Risiko operasional yang memadai?',
                60: 'Apakah BPR memiliki prosedur Manajemen Risiko operasional?',
                61: 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk baru?',
                63: 'Apakah BPR telah melaksanakan proses Manajemen Risiko operasional?',
                64: 'Apakah BPR telah memiliki sistem informasi Manajemen Risiko?',
                65: 'Apakah BPR telah memiliki kebijakan dan prosedur penyelenggaraan TI?',
                66: 'Apakah BPR telah melakukan langkah mitigasi risiko terkait kejadian eksternal?',
                68: 'Apakah SKAI/PEAI telah melaksanakan audit secara berkala?',
                69: 'Apakah sistem pengendalian intern telah dilaksanakan?'
            };
            return names[faktorId] || 'Parameter';
        }

        function getParameterKepatuhanName(faktorId) {
            const names = {
                73: 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                74: 'Jenis, signifikansi, dan frekuensi pelanggaran yang dilakukan',
                75: 'Signifikansi tindak lanjut atas temuan pelanggaran',
                76: 'Faktor kelemahan aspek hukum',
                77: 'Kelemahan dalam perikatan',
                78: 'Litigasi terkait nominal gugatan atau estimasi kerugian yang dialami BPR akibat gugatan',
                79: 'Litigasi terkait kerugian yang dialami karena putusan pengadilan berkekuatan hukum tetap',
                80: 'Lainnya'
            };
            return names[faktorId] || 'Parameter';
        }

        function getParameterKepatuhanKPMRName(faktorId) {
            const names = {
                84: 'Pengawasan Direksi dan Dewan Komisaris',
                85: 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kepatuhan yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                86: 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kepatuhan secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?',
                87: 'Apakah Direksi telah menyusun kebijakan manajemen risiko kepatuhan, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                88: 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kepatuhan, dan melakukan komunikasi kebijakan manajemen risiko kepatuhan terhadap seluruh jenjang organisasi BPR?',
                89: 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kepatuhan dan fungsi manajemen risiko kepatuhan?',
                90: 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kepatuhan?',
                91: 'Apakah Direksi telah menyusun kebijakan internal yang mendukung terselenggaranya fungsi kepatuhan, memberikan perhatian terhadap ketentuan peraturan perundang-undangan, serta terdapat kebijakan reward and punishment bagi internal BPR?',
                92: 'Kecukupan Kebijakan, Prosedur, dan Limit',
                93: 'Apakah BPR telah memiliki kebijakan manajemen risiko kepatuhan yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                94: 'Apakah BPR memiliki prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan yang ditetapkan oleh Direksi; melaksanakan prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara konsisten untuk seluruh aktivitas; dan melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara berkala?',
                95: 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kepatuhan sesuai dengan ketentuan?',
                96: 'Kecukupan Proses dan Sistem Manajemen Informasi',
                97: 'Apakah BPR telah melaksanakan proses manajemen risiko kepatuhan yang melekat pada kegiatan usaha BPR?',
                98: 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kepatuhan serta telah dilaporkan kepada Direksi secara berkala?',
                99: 'Sistem Pengendalian Internal yang Menyeluruh',
                100: 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kepatuhan, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                101: 'Apakah sistem pengendalian intern terhadap risiko kepatuhan telah dilaksanakan oleh seluruh jenjang organisasi BPR?',
                102: 'Penilaian Risiko KPMR',
                103: 'Penilaian Risiko KPMR Periode Sebelumnya'
            };
            return names[faktorId] || 'Parameter';
        }

        function getParameterLikuiditasName(faktorId) {
            const names = {
                105: 'Komposisi dan konsentrasi aset dan kewajiban',
                106: 'Rasio aset likuid terhadap total aset',
                107: 'Rasio aset likuid terhadap kewajiban lancar',
                108: 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)',
                109: 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga',
                110: 'Rasio Pendanaan non inti terhadap total pendanaan',
                111: 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan',
                112: 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi  Kebutuhan pendanaan',
                113: 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan.',
                114: 'Lainnya',
                115: 'Penilaian Risiko KPMR',
                116: 'Penilaian Risiko KPMR Periode Sebelumnya'
            };
            return names[faktorId] || 'Parameter';
        }

        function getParameterLikuiditasKPMRName(faktorId) {
            const names = {
                118: 'Pengawasan Direksi dan Dewan Komisaris',
                119: 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko kepatuhan yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                120: 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko kepatuhan secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?',
                121: 'Apakah Direksi telah menyusun kebijakan manajemen risiko kepatuhan, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                122: 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko kepatuhan, dan melakukan komunikasi kebijakan manajemen risiko kepatuhan terhadap seluruh jenjang organisasi BPR?',
                123: 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi kepatuhan dan fungsi manajemen risiko kepatuhan?',
                124: 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko kepatuhan?',
                125: 'Apakah Direksi telah menyusun kebijakan internal yang mendukung terselenggaranya fungsi kepatuhan, memberikan perhatian terhadap ketentuan peraturan perundang-undangan, serta terdapat kebijakan reward and punishment bagi internal BPR?',
                126: 'Kecukupan Kebijakan, Prosedur, dan Limit',
                127: 'Apakah BPR telah memiliki kebijakan manajemen risiko kepatuhan yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                128: 'Apakah BPR memiliki prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan yang ditetapkan oleh Direksi; melaksanakan prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara konsisten untuk seluruh aktivitas; dan melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko kepatuhan dan penetapan limit risiko kepatuhan secara berkala?',
                129: 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko kepatuhan sesuai dengan ketentuan?',
                130: 'Kecukupan Proses dan Sistem Manajemen Informasi',
                131: 'Apakah BPR telah melaksanakan proses manajemen risiko kepatuhan yang melekat pada kegiatan usaha BPR?',
                132: 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko kepatuhan serta telah dilaporkan kepada Direksi secara berkala?',
                133: 'Sistem Pengendalian Internal yang Menyeluruh',
                134: 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko kepatuhan, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                135: 'Penilaian Risiko KPMR',
                136: 'Penilaian Risiko KPMR Periode Sebelumnya'
            };
            return names[faktorId] || 'Parameter';
        }

        // Fungsi untuk draw kategori INHEREN (dengan rasio)
        function drawKategori(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRow(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        // Fungsi untuk draw child row INHEREN
        function drawChildRow(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Rasio
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 8);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - 15, size: 8, font
            });
            xPos += columnWidths[4];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[6], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        // Fungsi untuk draw single row INHEREN
        function drawSingleRow(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[6] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[4];

            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        // Fungsi untuk draw kategori KPMR (tanpa kolom rasio)
        function drawKategoriKPMR(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterKPMRName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRowKPMR(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage };
        }

        // Fungsi untuk draw child row KPMR
        function drawChildRowKPMR(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter (lebih lebar karena tidak ada kolom rasio)
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterKPMRName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan tambahan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        // Fungsi untuk draw single row KPMR
        function drawSingleRowKPMR(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        function drawKategoriOperasional(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                if (!child) return;

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterOperasionalName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                if (!child) return;

                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRowOperasional(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        function drawChildRowOperasional(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterOperasionalName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawSingleRowOperasional(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        function drawKategoriKepatuhan(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                if (!child) return;

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterKepatuhanName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                if (!child) return;

                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRowKepatuhanKPMR(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        function drawKategoriKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                if (!child) return;

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterKepatuhanKPMRName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                if (!child) return;

                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRowKepatuhanKPMR(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        function drawKategoriLikuiditas(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                if (!child) return;

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterLikuiditasName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                if (!child) return;

                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRowLikuiditasInheren(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        function drawKategoriLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                if (!child) return;

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterLikuiditasKPMRName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
                const contentHeight = maxLines * lineHeight + 10;
                const childHeight = Math.max(25, contentHeight);

                childHeights.push(childHeight);
            });

            let categoryPages = [{ page: currentPage, startY: yPosition, endY: 0 }];
            let currentPageIndex = 0;
            let currentYPosition = yPosition;

            children.forEach((child, index) => {
                if (!child) return;

                const childHeight = childHeights[index];

                if (currentYPosition - childHeight < MIN_Y) {
                    categoryPages[currentPageIndex].endY = currentYPosition;
                    const newPageResult = createNewPageFunc();
                    currentPage = newPageResult.page;
                    currentYPosition = newPageResult.yPosition;
                    categoryPages.push({ page: currentPage, startY: currentYPosition, endY: 0 });
                    currentPageIndex++;
                }

                drawChildRowKepatuhanKPMR(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
                currentYPosition -= childHeight;
            });

            categoryPages[currentPageIndex].endY = currentYPosition;

            categoryPages.forEach((pageInfo, idx) => {
                const page = pageInfo.page;
                const startY = pageInfo.startY;
                const endY = pageInfo.endY;
                const height = startY - endY;

                page.drawRectangle({
                    x: margin, y: endY, width: columnWidths[0], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0], y: endY, width: columnWidths[1], height: height,
                    color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                if (idx === 0) {
                    const topY = startY - 20;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 7);
                    const titleStartY = topY + ((titleLines.length - 1) * 4.5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 9), size: 7, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        async function drawTableKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKepatuhan, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 200, 140, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'KEPATUHAN_KPMR');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos,
                        y: yPosition - 18,
                        size: 9,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header tabel
            currentPage.drawRectangle({
                x: margin,
                y: yPosition - 25,
                width: tableWidth,
                height: 25,
                color: rgb(0.9, 0.9, 0.9),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition,
                    y: yPosition - 18,
                    size: 9,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Pelanggaran
            if (dataKepatuhan.nilai.pengawasan.kategori) {
                const result = drawKategoriKepatuhanKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataKepatuhan.nilai.pengawasan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR');
                        const newY = dims.height - 120;
                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Kebijakan
            if (dataKepatuhan.nilai.kebijakan.kategori) {
                const result = drawKategoriKepatuhanKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataKepatuhan.nilai.kebijakan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Proses
            if (dataKepatuhan.nilai.proses.kategori) {
                const result = drawKategoriKepatuhanKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Kecukupan Proses dan Sistem Manajemen Informasi',
                    dataKepatuhan.nilai.proses, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Pengendalian
            if (dataKepatuhan.nilai.pengendalian.kategori) {
                const result = drawKategoriKepatuhanKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Sistem Pengendalian Internal yang Menyeluruh',
                    dataKepatuhan.nilai.pengendalian, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Penilaiank Risiko KPMR
            if (dataKepatuhan.nilai102) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhanKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKepatuhan.nilai102, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataKepatuhan.nilai103) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhanKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKepatuhan.nilai103, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        async function drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataLikuiditas, periode, bpr, width, height) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 200, 140, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'LIKUIDITAS_KPMR');
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin,
                    y: yPosition - 25,
                    width: tableWidth,
                    height: 25,
                    color: rgb(0.9, 0.9, 0.9),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });

                yPosition -= 25;
                return yPosition;
            };

            // Draw header
            currentPage.drawRectangle({
                x: margin, y: yPosition - 25, width: tableWidth, height: 25,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPos += columnWidths[i];
            });

            yPosition -= 25;

            // Data Kategori Pengawasan
            if (dataLikuiditas.nilai.pengawasan.kategori) {
                const result = drawKategoriLikuiditasKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataLikuiditas.nilai.pengawasan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'LIKUIDITAS_KPMR');
                        const newY = dims.height - 120;
                        return { page: newPage, yPosition: newY - 25 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Kebijakan
            if (dataLikuiditas.nilai.kebijakan.kategori) {
                const result = drawKategoriLikuiditasKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataLikuiditas.nilai.kebijakan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'LIKUIDITAS_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Proses
            if (dataLikuiditas.nilai.proses.kategori) {
                const result = drawKategoriLikuiditasKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Kecukupan Proses dan Sistem Manajemen Informasi',
                    dataLikuiditas.nilai.proses, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'LIKUIDITAS_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Kategori Pengendalian
            if (dataLikuiditas.nilai.pengendalian.kategori) {
                const result = drawKategoriLikuiditasKPMR(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Sistem Pengendalian Internal yang Menyeluruh',
                    dataLikuiditas.nilai.pengendalian, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'LIKUIDITAS_KPMR');
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Penilaian Risiko KPMR
            if (dataLikuiditas.nilai135) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditasKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataLikuiditas.nilai135, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataLikuiditas.nilai136) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditasKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataLikuiditas.nilai136, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        function drawSingleRowLikuiditas(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[6] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });
            page.drawText('-', { x: xPos + (columnWidths[4] / 2) - 5, y: cellY, size: 8, font });
            xPos += columnWidths[4];

            // Nilai Pilar (sekarang columnWidths[5])
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        function drawSingleRowLikuiditasKPMR(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            // No
            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            // Pilar Penilaian
            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            // Parameter Penilaian
            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            // Hasil Penilaian
            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        function drawChildRowLikuiditasInheren(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterLikuiditasName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Rasio (TAMBAHKAN INI)
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 8);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - 15, size: 8, font
            });
            xPos += columnWidths[4];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[6], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawChildRowKepatuhanKPMR(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterLikuiditasKPMRName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawChildRowLikuiditas(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterLikuiditasName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Rasio
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 8);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - 15, size: 8, font
            });
            xPos += columnWidths[4];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawChildRowLikuiditasKPMR(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterLikuiditasKPMRName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawSingleRowKepatuhan(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        function drawSingleRowKepatuhanKPMR(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight + 6;
            const rowHeight = Math.max(minRowHeight, contentHeight);
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 10;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 8, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 6);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (idx * 7), size: 6, font
                });
            });
            xPos += columnWidths[3];

            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 10) / 2),
                y: yPosition - 12, size: 10, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 5.5, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 10 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        async function generateAllPDFsToZip() {
            try {
                updateProgress(5, 'Mempersiapkan ZIP...');
                const zip = new JSZip();
                const folder = zip.folder("Laporan Profil Risiko");

                const files = [
                    {
                        name: '00. Lembar Pernyataan.pdf',
                        endpoint: 'Showprofilresiko/exportLembarPernyataanJSON',
                        generator: generateLembarPernyataanPDF,//batass
                        progress: 15
                    },
                    {
                        name: '01. Laporan Profil Risiko Kredit.pdf',
                        endpoint: 'Risikokredit/exportPDFGabungan',
                        generator: generatePDFKredit, // Will use server-generated if available
                        progress: 30
                    },
                    {
                        name: '02. Laporan Profil Risiko Operasional.pdf',
                        endpoint: 'Risikokredit/exportPDFGabunganOperasional',
                        generator: null,
                        progress: 45
                    },
                    {
                        name: '03. Laporan Profil Risiko Kepatuhan.pdf',
                        endpoint: 'Risikokredit/exportPDFGabunganKepatuhan',
                        generator: null,
                        progress: 60
                    },
                    {
                        name: '04. Laporan Profil Risiko Likuiditas.pdf',
                        endpoint: 'Risikokredit/exportPDFGabunganLikuiditas',
                        generator: null,
                        progress: 75
                    },
                    {
                        name: '05. Laporan Profil Risiko.pdf',
                        endpoint: 'Showprofilresiko/exportLaporanProfilRisikoJSON',
                        generator: null,
                        progress: 90
                    }
                ];

                let successCount = 0;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    try {
                        addFileToList(file.name, 'processing');
                        updateProgress(file.progress, `Membuat ${file.name}...`);

                        // Fetch data dari endpoint
                        const response = await fetch(`${BASE_URL}/${file.endpoint}`);
                        const result = await response.json();

                        if (result.status !== 'success') {
                            throw new Error(result.message || 'Gagal mengambil data');
                        }

                        let pdfBytes;

                        // Generate PDF menggunakan generator function jika ada
                        if (file.generator) {
                            pdfBytes = await file.generator(result.data);
                        } else {
                            // Placeholder - need to implement other PDF generators
                            // For now, create simple PDF with message
                            const pdfDoc = await PDFDocument.create();
                            const page = pdfDoc.addPage([595, 842]);
                            const font = await pdfDoc.embedFont(StandardFonts.Helvetica);

                            page.drawText('Data tersedia - implementasi PDF generator sedang dikembangkan', {
                                x: 50,
                                y: 750,
                                size: 12,
                                font: font
                            });

                            pdfBytes = await pdfDoc.save();
                        }

                        folder.file(file.name, pdfBytes);

                        // Update status sukses
                        const items = document.querySelectorAll('.file-list-item');
                        items[items.length - 1].className = 'file-list-item success';
                        items[items.length - 1].innerHTML = `<i class="fas fa-check-circle"></i> <span>${file.name}</span>`;

                        successCount++;

                    } catch (error) {
                        console.error(`Error generating ${file.name}:`, error);
                        const items = document.querySelectorAll('.file-list-item');
                        items[items.length - 1].className = 'file-list-item error';
                        items[items.length - 1].innerHTML = `<i class="fas fa-times-circle"></i> <span>${file.name} - ${error.message}</span>`;
                    }
                }

                updateProgress(95, 'Mengompres file ke ZIP...');

                const zipBlob = await zip.generateAsync({
                    type: 'blob',
                    compression: 'DEFLATE',
                    compressionOptions: { level: 6 }
                });

                updateProgress(100, `Selesai! ${successCount} dari ${files.length} file berhasil dibuat.`);

                // Download ZIP
                const link = document.createElement('a');
                link.href = URL.createObjectURL(zipBlob);

                const now = new Date();
                const timestamp = now.toISOString().slice(0, 10).replace(/-/g, '');
                link.download = `Laporan_Profil_Risiko_${timestamp}.zip`;

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Show success
                setTimeout(() => {
                    showSuccess(`Download selesai! ${successCount} dari ${files.length} file PDF berhasil dikumpulkan dalam ZIP.`);
                }, 1000);

            } catch (error) {
                console.error('Error:', error);
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        async function generatePDFKreditBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // INHEREN
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'INHEREN');
            await drawTableInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height);

            // KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'KPMR');
            await drawTableKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height);

            return await pdfDoc.save();
        }

        // ✅ Generator untuk Operasional - return bytes saja
        async function generatePDFOperasionalBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // INHEREN
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'OPERASIONAL_INHEREN');
            await drawTableOperasionalInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height);

            // KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'OPERASIONAL_KPMR');
            await drawTableOperasionalKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height);

            return await pdfDoc.save();
        }

        // ✅ Generator untuk Kepatuhan - return bytes saja
        async function generatePDFKepatuhanBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // INHEREN
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'KEPATUHAN_INHEREN');
            await drawTableKepatuhanInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height);

            // KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'KEPATUHAN_KPMR');
            await drawTableKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height);

            return await pdfDoc.save();
        }

        // ✅ Generator untuk Likuiditas - return bytes saja
        async function generatePDFLikuiditasBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // INHEREN
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'LIKUIDITAS_INHEREN');
            await drawTableLikuiditas(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height);

            // KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'LIKUIDITAS_KPMR');
            await drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height);

            return await pdfDoc.save();
        }

        // ✅ Placeholder untuk Lembar Pernyataan
        async function generateLembarPernyataanPDF(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const page = pdfDoc.addPage([595, 842]);
            const font = await pdfDoc.embedFont(StandardFonts.Helvetica);

            page.drawText('LEMBAR PERNYATAAN', {
                x: 50, y: 750, size: 16, font
            });

            return await pdfDoc.save();
        }

        // ✅ Placeholder untuk Laporan Profil Risiko
        async function generateLaporanProfilRisikoBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const page = pdfDoc.addPage([595, 842]);
            const font = await pdfDoc.embedFont(StandardFonts.Helvetica);

            page.drawText('LAPORAN PROFIL RISIKO', {
                x: 50, y: 750, size: 16, font
            });

            return await pdfDoc.save();
        }

        const files = [
            {
                name: '00. Lembar Pernyataan.pdf',
                endpoint: 'Showprofilresiko/exportLembarPernyataanJSON',
                generator: generateLembarPernyataanPDF,
                progress: 15
            },
            {
                name: '01. Laporan Profil Risiko Kredit.pdf',
                endpoint: 'Risikokredit/exportPDFGabungan',
                generator: generatePDFKreditBytes, // ✅ Buat fungsi baru ini
                progress: 30
            },
            {
                name: '02. Laporan Profil Risiko Operasional.pdf',
                endpoint: 'Risikokredit/exportPDFGabunganOperasional',
                generator: generatePDFOperasionalBytes, // ✅ Buat fungsi baru ini
                progress: 45
            },
            {
                name: '03. Laporan Profil Risiko Kepatuhan.pdf',
                endpoint: 'Risikokredit/exportPDFGabunganKepatuhan',
                generator: generatePDFKepatuhanBytes, // ✅ Buat fungsi baru ini
                progress: 60
            },
            {
                name: '04. Laporan Profil Risiko Likuiditas.pdf',
                endpoint: 'Risikokredit/exportPDFGabunganLikuiditas',
                generator: generatePDFLikuiditasBytes, // ✅ Buat fungsi baru ini
                progress: 75
            },
            {
                name: '05. Laporan Profil Risiko.pdf',
                endpoint: 'Showprofilresiko/exportLaporanProfilRisikoJSON',
                generator: generateLaporanProfilRisikoBytes, // ✅ Buat fungsi baru ini
                progress: 90
            }
        ];

        function addFileToList(fileName, status) {
            const fileList = document.getElementById('fileList');
            if (!fileList) return;

            document.getElementById('fileListContainer').style.display = 'block';

            const item = document.createElement('div');
            item.className = `file-list-item ${status}`;

            let icon = 'fa-spinner fa-spin';
            if (status === 'success') icon = 'fa-check-circle';
            if (status === 'error') icon = 'fa-times-circle';

            item.innerHTML = `<i class="fas ${icon}"></i> <span>${fileName}</span>`;
            fileList.appendChild(item);
        }

        function showSuccess(message) {
            alert(message); // Atau gunakan library notification yang lebih bagus
        }

        function showError(message) {
            alert('Error: ' + message);
        }

        // Auto start
        // window.onload = () => {
        //     // Ambil parameter dari URL
        //     const urlParams = new URLSearchParams(window.location.search);
        //     const type = urlParams.get('type');
        //     if (type === 'kredit') {
        //         generatePDFKredit();
        //     } else if (type === 'operasional') {
        //         generatePDFOperasional(); // Function khusus operasional
        //     } else if (type === 'kepatuhan') {
        //         generatePDFKepatuhan();
        //     } else if (type === 'likuiditas') {
        //         generatePDFLikuiditas();
        //     } else {
        //         generatePDFGabungan(); // Default: semua risiko
        //     }
        // };

        window.onload = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');

            if (type === 'zip') {
                generateAllPDFsToZip(); // ✅ Tambahkan kondisi ini
            } else if (type === 'kredit') {
                generatePDFKredit();
            } else if (type === 'operasional') {
                generatePDFOperasional();
            } else if (type === 'kepatuhan') {
                generatePDFKepatuhan();
            } else if (type === 'likuiditas') {
                generatePDFLikuiditas();
            } else {
                generatePDFGabungan();
            }
        };


    </script>
</body>

</html>