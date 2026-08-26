<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan TKS</title>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #353535;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

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

        .status-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .error-message {
            color: #e74c3c;
            margin-top: 15px;
            padding: 10px;
            background: #fadbd8;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="loading-container" id="loadingContainer">
        <div class="status-icon" id="statusIcon"></div>
        <div class="spinner" id="spinner"></div>
        <div class="loading-text" id="loadingText">Mempersiapkan dokumen...</div>
        <div class="progress-bar-custom">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>
        <div class="error-message" id="errorMessage" style="display: none;"></div>
    </div>

    <script>
        const BASE_URL = '<?= base_url() ?>';
        let fontBold, fontRegular, logoImage = null;
        let width, height, margin, centerX;
        let data;
        const { PDFDocument, rgb, StandardFonts } = PDFLib;

        function updateProgress(percent, text, icon = '') {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('loadingText').textContent = text;
            document.getElementById('statusIcon').textContent = icon;
        }

        function showError(message) {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('statusIcon').textContent = '❌';
            document.getElementById('loadingText').textContent = 'Terjadi Kesalahan';
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function wrapTextByCharLimit(text, maxChars) {
            if (!text) return [''];
            const lines = [];
            let currentLine = '';
            const words = text.split(' ');

            for (const word of words) {
                const testLine = currentLine ? currentLine + ' ' + word : word;
                if (testLine.length > maxChars && currentLine) {
                    lines.push(currentLine);
                    currentLine = word;
                } else {
                    currentLine = testLine;
                }
            }
            if (currentLine) lines.push(currentLine);
            return lines;
        }

        function getPeringkatColor(value) {
            if (!value || value === 0) return [0.8, 0.8, 0.8];
            switch (parseInt(value)) {
                case 1: return [0, 1, 0];
                case 2: return [0.56, 0.93, 0.56];
                case 3: return [1, 1, 0];
                case 4: return [1, 0.65, 0];
                case 5: return [1, 0, 0];
                default: return [0.8, 0.8, 0.8];
            }
        }

        function getPeringkatText(value) {
            const texts = {
                1: 'Sangat Sehat',
                2: 'Sehat',
                3: 'Cukup Sehat',
                4: 'Kurang Sehat',
                5: 'Tidak Sehat'
            };
            return texts[parseInt(value)] || '-';
        }

        async function generateLaporanTKS() {
            try {
                updateProgress(10, 'Mengambil data dari server...', '🔄');

                const response = await fetch(`${BASE_URL}/Showtks/exportLaporanTKSJSON`);
                const result = await response.json();

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Gagal mengambil data');
                }

                data = result.data;

                updateProgress(30, 'Membuat dokumen PDF...', '📝');

                const pdfDoc = await PDFDocument.create();

                updateProgress(40, 'Menambahkan font...', '✍️');

                fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                updateProgress(50, 'Membuat halaman...', '📄');

                let page = pdfDoc.addPage([595, 842]); // A4
                ({ width, height } = page.getSize());

                margin = 30;
                centerX = width / 2;
                let yPos = height - margin;

                updateProgress(55, 'Menambahkan header...', '🖼️');

                // Load logo if exists
                if (data.bpr.logo) {
                    try {
                        const logoBytes = await fetch(data.bpr.logo).then(res => res.arrayBuffer());
                        if (data.bpr.logo.includes('image/png') || data.bpr.logo.endsWith('.png')) {
                            logoImage = await pdfDoc.embedPng(logoBytes);
                        } else {
                            logoImage = await pdfDoc.embedJpg(logoBytes);
                        }
                    } catch (e) {
                        console.warn('Logo tidak dapat dimuat:', e);
                    }
                }

                // Draw header
                yPos = drawHeader(page, yPos);

                updateProgress(60, 'Menambahkan konten...', '📝');

                // Title
                const judul = 'LAPORAN PENILAIAN SENDIRI';
                const judul2 = 'TINGKAT KESEHATAN';
                let judulWidth = fontBold.widthOfTextAtSize(judul, 14);
                page.drawText(judul, {
                    x: centerX - (judulWidth / 2),
                    y: yPos,
                    size: 14,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 18;

                judulWidth = fontBold.widthOfTextAtSize(judul2, 14);
                page.drawText(judul2, {
                    x: centerX - (judulWidth / 2),
                    y: yPos,
                    size: 14,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 25;

                // Info
                const infoData = [
                    ['Nama BPR', data.bpr.namabpr],
                    ['Posisi', `Bulan ${data.periode.bulan} Tahun ${data.periode.tahun}`]
                ];

                infoData.forEach(([label, value]) => {
                    page.drawText(label, {
                        x: margin,
                        y: yPos,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    page.drawText(':', {
                        x: margin + 80,
                        y: yPos,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    page.drawText(value, {
                        x: margin + 90,
                        y: yPos,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    yPos -= 15;
                });

                yPos -= 10;

                updateProgress(70, 'Membuat tabel penilaian...', '📊');

                // Draw table
                yPos = await drawTKSTable(page, yPos);

                updateProgress(85, 'Menambahkan analisis...', '📋');

                // Check if need new page
                if (yPos < 300) {
                    page = pdfDoc.addPage([595, 842]);
                    yPos = drawHeader(page, height - margin);
                }

                // Draw analysis
                yPos = await drawAnalysis(page, yPos);

                updateProgress(95, 'Menambahkan tanda tangan...', '✍️');

                // Check if need new page for signature
                if (yPos < 150) {
                    page = pdfDoc.addPage([595, 842]);
                    yPos = drawHeader(page, height - margin);
                }

                // Draw signature
                await drawSignature(page, yPos);

                updateProgress(98, 'Menyimpan PDF...', '💾');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                updateProgress(100, 'Membuka PDF...', '✅');

                const fileName = `00. Laporan TKS Bulan ${data.periode.bulan} Tahun ${data.periode.tahun}.pdf`;

                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                setTimeout(() => {
                    URL.revokeObjectURL(url);
                    window.close();
                }, 2000);

            } catch (error) {
                console.error('Error:', error);
                showError(error.message || 'Terjadi kesalahan saat membuat PDF');
            }
        }

        function drawHeader(page, yStart) {
            let yPos = yStart;

            if (logoImage) {
                const logoSize = 35;
                page.drawImage(logoImage, {
                    x: margin,
                    y: yPos - logoSize,
                    width: logoSize + 60,
                    height: logoSize
                });
            }

            const namaBPRLines = wrapTextByCharLimit(data.bpr.namabpr, 50);
            namaBPRLines.forEach((line, index) => {
                const lineWidth = fontBold.widthOfTextAtSize(line, 12);
                page.drawText(line, {
                    x: centerX - (lineWidth / 2),
                    y: yPos - (index * 14),
                    size: 12,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
            });
            yPos -= (namaBPRLines.length * 14) + 2;

            const alamatLines = wrapTextByCharLimit(data.bpr.alamat, 75);
            alamatLines.forEach((line, index) => {
                const lineWidth = fontRegular.widthOfTextAtSize(line, 8);
                page.drawText(line, {
                    x: centerX - (lineWidth / 2),
                    y: yPos - (index * 10),
                    size: 9,
                    font: fontRegular,
                    color: rgb(0.2, 0.2, 0.2)
                });
            });
            yPos -= (alamatLines.length * 10) + 2;

            const telepon = `Telepon: ${data.bpr.nomor}`;
            const teleponWidth = fontRegular.widthOfTextAtSize(telepon, 8);
            page.drawText(telepon, {
                x: centerX - (teleponWidth / 2),
                y: yPos,
                size: 8,
                font: fontRegular,
                color: rgb(0.2, 0.2, 0.2)
            });
            yPos -= 10;

            const contact = `Website: ${data.bpr.webbpr || '-'}, Email: ${data.bpr.email || '-'}`;
            const contactWidth = fontRegular.widthOfTextAtSize(contact, 8);
            page.drawText(contact, {
                x: centerX - (contactWidth / 2),
                y: yPos,
                size: 8,
                font: fontRegular,
                color: rgb(0.2, 0.2, 0.2)
            });
            yPos -= 15;

            page.drawLine({
                start: { x: margin, y: yPos },
                end: { x: width - margin, y: yPos },
                thickness: 2,
                color: rgb(0, 0, 0)
            });
            yPos -= 20;

            return yPos;
        }

        async function drawTKSTable(page, yStart) {
            let yPos = yStart;
            const rowHeight = 25;
            const colWidths = [100, 70, 70, 70, 70, 70, 70, 70, 70];
            const tableWidth = colWidths.reduce((a, b) => a + b, 0);
            const tableStartX = margin;

            // Header row 1 - Merged cells
            page.drawRectangle({
                x: tableStartX,
                y: yPos - rowHeight,
                width: tableWidth,
                height: rowHeight,
                color: rgb(0.85, 0.85, 0.85),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            let currentX = tableStartX;

            // "Faktor / Komponen"
            page.drawText('Faktor / Komponen', {
                x: currentX + 10,
                y: yPos - 15,
                size: 9,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            currentX += colWidths[0];

            // "Penilaian Posisi Laporan"
            page.drawText('Penilaian Posisi Laporan', {
                x: currentX + 100,
                y: yPos - 15,
                size: 9,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            // "Penilaian Posisi Sebelumnya"
            page.drawText('Penilaian Posisi Sebelumnya', {
                x: currentX + 320,
                y: yPos - 15,
                size: 9,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            yPos -= rowHeight;

            // Header row 2
            page.drawRectangle({
                x: tableStartX,
                y: yPos - rowHeight,
                width: tableWidth,
                height: rowHeight,
                color: rgb(0.85, 0.85, 0.85),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            // Sub-header for previous period
            const prevPeriodText = `Semester ${data.periode.periodeSebelumBulan === 'Desember' ? '2' : '1'} Tahun ${data.periode.periodeSebelumTahun}`;
            page.drawText(prevPeriodText, {
                x: tableStartX + colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3] + colWidths[4] + 60,
                y: yPos - 15,
                size: 8,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            yPos -= rowHeight;

            // Header row 3
            const headers = [
                '', 'Rasio\n(%)', 'Peringkat\n(a)', 'Bobot\n(b)', 'Nilai Faktor\n(c) = (a) x (b)',
                'Rasio\n(%)', 'Peringkat\n(a)', 'Bobot\n(b)', 'Nilai Faktor\n(c) = (a) x (b)'
            ];

            page.drawRectangle({
                x: tableStartX,
                y: yPos - rowHeight,
                width: tableWidth,
                height: rowHeight,
                color: rgb(0.85, 0.85, 0.85),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            currentX = tableStartX;
            headers.forEach((header, idx) => {
                const lines = header.split('\n');
                const startY = yPos - 8 - ((lines.length - 1) * 4);

                lines.forEach((line, lineIdx) => {
                    const textWidth = fontBold.widthOfTextAtSize(line, 7);
                    page.drawText(line, {
                        x: currentX + (colWidths[idx] - textWidth) / 2,
                        y: startY + (lineIdx * 9),
                        size: 7,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                });

                if (idx < headers.length - 1) {
                    page.drawLine({
                        start: { x: currentX + colWidths[idx], y: yPos },
                        end: { x: currentX + colWidths[idx], y: yPos - rowHeight },
                        thickness: 1,
                        color: rgb(0, 0, 0)
                    });
                }

                currentX += colWidths[idx];
            });

            yPos -= rowHeight;

            // Data rows
            const rows = [
                {
                    name: 'Profil Risiko',
                    current: {
                        rasio: '',
                        peringkat: data.tks.profilrisiko.peringkatCurrent,
                        bobot: data.tks.profilrisiko.bobotCurrent,
                        nilai: data.tks.profilrisiko.nilaifaktorCurrent
                    },
                    previous: {
                        rasio: '',
                        peringkat: data.tks.profilrisiko.peringkatPrevious,
                        bobot: data.tks.profilrisiko.bobotPrevious,
                        nilai: data.tks.profilrisiko.nilaifaktorPrevious
                    },
                    isSubRow: false
                },
                {
                    name: 'Tata Kelola',
                    current: {
                        rasio: '',
                        peringkat: data.tks.tatakelola.peringkatCurrent,
                        bobot: data.tks.tatakelola.bobotCurrent,
                        nilai: data.tks.tatakelola.nilaifaktorCurrent
                    },
                    previous: {
                        rasio: '',
                        peringkat: data.tks.tatakelola.peringkatPrevious,
                        bobot: data.tks.tatakelola.bobotPrevious,
                        nilai: data.tks.tatakelola.nilaifaktorPrevious
                    },
                    isSubRow: false
                },
                {
                    name: 'Rentabilitas',
                    current: {
                        rasio: '',
                        peringkat: data.tks.rentabilitas.peringkatCurrent,
                        bobot: data.tks.rentabilitas.bobotCurrent,
                        nilai: data.tks.rentabilitas.nilaifaktorCurrent
                    },
                    previous: {
                        rasio: '',
                        peringkat: data.tks.rentabilitas.peringkatPrevious,
                        bobot: data.tks.rentabilitas.bobotPrevious,
                        nilai: data.tks.rentabilitas.nilaifaktorPrevious
                    },
                    isSubRow: false
                },
                {
                    name: '1. Return on Asset (ROA)',
                    current: {
                        rasio: data.tks.rentabilitas.roa.rasioCurrent,
                        peringkat: data.tks.rentabilitas.roa.peringkatCurrent,
                        bobot: '',
                        nilai: ''
                    },
                    previous: {
                        rasio: data.tks.rentabilitas.roa.rasioPrevious,
                        peringkat: data.tks.rentabilitas.roa.peringkatPrevious,
                        bobot: '',
                        nilai: ''
                    },
                    isSubRow: true
                },
                {
                    name: '2. Rasio Beban Operasional\nterhadap Pendapatan\nOperasional (BOPO)',
                    current: {
                        rasio: data.tks.rentabilitas.bopo.rasioCurrent,
                        peringkat: data.tks.rentabilitas.bopo.peringkatCurrent,
                        bobot: '',
                        nilai: ''
                    },
                    previous: {
                        rasio: data.tks.rentabilitas.bopo.rasioPrevious,
                        peringkat: data.tks.rentabilitas.bopo.peringkatPrevious,
                        bobot: '',
                        nilai: ''
                    },
                    isSubRow: true
                },
                {
                    name: '3. Net Interest Margin (NIM)',
                    current: {
                        rasio: data.tks.rentabilitas.nim.rasioCurrent,
                        peringkat: data.tks.rentabilitas.nim.peringkatCurrent,
                        bobot: '',
                        nilai: ''
                    },
                    previous: {
                        rasio: data.tks.rentabilitas.nim.rasioPrevious,
                        peringkat: data.tks.rentabilitas.nim.peringkatPrevious,
                        bobot: '',
                        nilai: ''
                    },
                    isSubRow: true
                },
                {
                    name: 'Permodalan',
                    current: {
                        rasio: '',
                        peringkat: data.tks.permodalan.peringkatCurrent,
                        bobot: data.tks.permodalan.bobotCurrent,
                        nilai: data.tks.permodalan.nilaifaktorCurrent
                    },
                    previous: {
                        rasio: '',
                        peringkat: data.tks.permodalan.peringkatPrevious,
                        bobot: data.tks.permodalan.bobotPrevious,
                        nilai: data.tks.permodalan.nilaifaktorPrevious
                    },
                    isSubRow: false
                },
                {
                    name: '1. Rasio KPMM',
                    current: {
                        rasio: data.tks.permodalan.kpmm.rasioCurrent,
                        peringkat: data.tks.permodalan.kpmm.peringkatCurrent,
                        bobot: '',
                        nilai: ''
                    },
                    previous: {
                        rasio: data.tks.permodalan.kpmm.rasioPrevious,
                        peringkat: data.tks.permodalan.kpmm.peringkatPrevious,
                        bobot: '',
                        nilai: ''
                    },
                    isSubRow: true
                },
                {
                    name: '2. Rasio MIAPB',
                    current: {
                        rasio: data.tks.permodalan.miapb.rasioCurrent,
                        peringkat: data.tks.permodalan.miapb.peringkatCurrent,
                        bobot: '',
                        nilai: ''
                    },
                    previous: {
                        rasio: data.tks.permodalan.miapb.rasioPrevious,
                        peringkat: data.tks.permodalan.miapb.peringkatPrevious,
                        bobot: '',
                        nilai: ''
                    },
                    isSubRow: true
                }
            ];

            rows.forEach(row => {
                page.drawRectangle({
                    x: tableStartX,
                    y: yPos - rowHeight,
                    width: tableWidth,
                    height: rowHeight,
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                currentX = tableStartX;

                // Name
                const nameLines = row.name.split('\n');
                const nameStartY = yPos - 10 - ((nameLines.length - 1) * 4);
                nameLines.forEach((line, idx) => {
                    page.drawText(line, {
                        x: currentX + (row.isSubRow ? 10 : 5),
                        y: nameStartY + (idx * 8),
                        size: row.isSubRow ? 8 : 9,
                        font: row.isSubRow ? fontRegular : fontBold,
                        color: rgb(0, 0, 0)
                    });
                });
                currentX += colWidths[0];

                // Current values
                const currentVals = [
                    row.current.rasio,
                    row.current.peringkat,
                    row.current.bobot,
                    row.current.nilai
                ];

                currentVals.forEach((val, idx) => {
                    if (idx === 1 && val) { // Peringkat - add color
                        const peringkatColor = getPeringkatColor(val);
                        page.drawRectangle({
                            x: currentX,
                            y: yPos - rowHeight,
                            width: colWidths[idx + 1],
                            height: rowHeight,
                            color: rgb(peringkatColor[0], peringkatColor[1], peringkatColor[2]),
                            borderColor: rgb(0, 0, 0),
                            borderWidth: 1
                        });
                    }

                    if (val) {
                        const text = val.toString();
                        const textWidth = fontRegular.widthOfTextAtSize(text, 9);
                        page.drawText(text, {
                            x: currentX + (colWidths[idx + 1] - textWidth) / 2,
                            y: yPos - 15,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                    }
                    currentX += colWidths[idx + 1];
                });

                // Previous values
                const previousVals = [
                    row.previous.rasio,
                    row.previous.peringkat,
                    row.previous.bobot,
                    row.previous.nilai
                ];

                previousVals.forEach((val, idx) => {
                    if (idx === 1 && val) { // Peringkat - add color
                        const peringkatColor = getPeringkatColor(val);
                        page.drawRectangle({
                            x: currentX,
                            y: yPos - rowHeight,
                            width: colWidths[idx + 5],
                            height: rowHeight,
                            color: rgb(peringkatColor[0], peringkatColor[1], peringkatColor[2]),
                            borderColor: rgb(0, 0, 0),
                            borderWidth: 1
                        });
                    }

                    if (val) {
                        const text = val.toString();
                        const textWidth = fontRegular.widthOfTextAtSize(text, 9);
                        page.drawText(text, {
                            x: currentX + (colWidths[idx + 5] - textWidth) / 2,
                            y: yPos - 15,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                    }
                    currentX += colWidths[idx + 5];
                });

                yPos -= rowHeight;
            });

            // Komposit rows
            const kompositRows = [
                {
                    label: 'Nilai Komposit',
                    currentVal: data.tks.komposit.nilaiCurrent,
                    previousVal: data.tks.komposit.nilaiPrevious,
                    isColored: false
                },
                {
                    label: 'Peringkat Komposit',
                    currentVal: data.tks.komposit.peringkatCurrent,
                    currentText: `${data.tks.komposit.peringkatCurrent}\n(${getPeringkatText(data.tks.komposit.peringkatCurrent)})`,
                    previousVal: data.tks.komposit.peringkatPrevious,
                    previousText: `${data.tks.komposit.peringkatPrevious}\n(${getPeringkatText(data.tks.komposit.peringkatPrevious)})`,
                    isColored: true
                }
            ];

            kompositRows.forEach(row => {
                const bgColor = row.isColored ? rgb(0.5, 0.8, 0.9) : rgb(1, 1, 1);
                page.drawRectangle({
                    x: tableStartX,
                    y: yPos - rowHeight,
                    width: tableWidth,
                    height: rowHeight,
                    color: bgColor,
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                // Label
                page.drawText(row.label, {
                    x: tableStartX + 5,
                    y: yPos - 15,
                    size: 9,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                // Current value
                let currentX = tableStartX + colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3];
                if (row.isColored) {
                    const color = getPeringkatColor(row.currentVal);
                    page.drawRectangle({
                        x: currentX,
                        y: yPos - rowHeight,
                        width: colWidths[4],
                        height: rowHeight,
                        color: rgb(color[0], color[1], color[2]),
                        borderColor: rgb(0, 0, 0),
                        borderWidth: 1
                    });

                    const lines = row.currentText.split('\n');
                    lines.forEach((line, idx) => {
                        const textWidth = fontBold.widthOfTextAtSize(line, 8);
                        page.drawText(line, {
                            x: currentX + (colWidths[4] - textWidth) / 2,
                            y: yPos - 10 - (idx * 10),
                            size: 8,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                    });
                } else {
                    const text = row.currentVal ? row.currentVal.toString() : '';
                    const textWidth = fontBold.widthOfTextAtSize(text, 10);
                    page.drawText(text, {
                        x: currentX + (colWidths[4] - textWidth) / 2,
                        y: yPos - 15,
                        size: 10,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                }

                // Previous value
                currentX = tableStartX + colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3] + colWidths[4] + colWidths[5] + colWidths[6] + colWidths[7];
                if (row.isColored) {
                    const color = getPeringkatColor(row.previousVal);
                    page.drawRectangle({
                        x: currentX,
                        y: yPos - rowHeight,
                        width: colWidths[8],
                        height: rowHeight,
                        color: rgb(color[0], color[1], color[2]),
                        borderColor: rgb(0, 0, 0),
                        borderWidth: 1
                    });

                    const lines = row.previousText.split('\n');
                    lines.forEach((line, idx) => {
                        const textWidth = fontBold.widthOfTextAtSize(line, 8);
                        page.drawText(line, {
                            x: currentX + (colWidths[8] - textWidth) / 2,
                            y: yPos - 10 - (idx * 10),
                            size: 8,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                    });
                } else {
                    const text = row.previousVal ? row.previousVal.toString() : '';
                    const textWidth = fontBold.widthOfTextAtSize(text, 10);
                    page.drawText(text, {
                        x: currentX + (colWidths[8] - textWidth) / 2,
                        y: yPos - 15,
                        size: 10,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                }

                yPos -= rowHeight;
            });

            yPos -= 10;

            return yPos;
        }

        async function drawAnalysis(page, yStart) {
            let yPos = yStart;

            // Analysis box
            page.drawRectangle({
                x: margin,
                y: 50,
                width: width - (2 * margin),
                height: yPos - 50,
                color: rgb(0.5, 0.8, 0.9),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            page.drawText('Analisis Posisi Laporan', {
                x: margin + 10,
                y: yPos - 15,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            yPos -= 30;

            // Sections
            const sections = [
                { title: '1. Kesimpulan Tingkat Kesehatan', content: data.analisis.kesimpulan },
                { title: '2. Profil Risiko', content: data.analisis.profilrisiko },
                { title: '3. Tata Kelola', content: data.analisis.tatakelola },
                { title: '4. Rentabilitas', content: data.analisis.rentabilitas },
                { title: '5. Permodalan', content: data.analisis.permodalan }
            ];

            for (const section of sections) {
                if (yPos < 100) {
                    page = pdfDoc.addPage([595, 842]);
                    yPos = drawHeader(page, height - margin);

                    page.drawRectangle({
                        x: margin,
                        y: 50,
                        width: width - (2 * margin),
                        height: yPos - 50,
                        borderColor: rgb(0, 0, 0),
                        borderWidth: 1
                    });
                }

                page.drawText(section.title, {
                    x: margin + 10,
                    y: yPos,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 15;

                if (section.content) {
                    const paragraphs = section.content.split('\n');
                    for (const para of paragraphs) {
                        if (para.trim() === '') {
                            yPos -= 10;
                            continue;
                        }

                        const lines = wrapTextByCharLimit(para, 119);
                        for (const line of lines) {
                            if (yPos < 70) {
                                page = pdfDoc.addPage([595, 842]);
                                yPos = drawHeader(page, height - margin);

                                page.drawRectangle({
                                    x: margin,
                                    y: 50,
                                    width: width - (2 * margin),
                                    height: yPos - 50,
                                    borderColor: rgb(0, 0, 0),
                                    borderWidth: 1
                                });
                                yPos -= 20;
                            }

                            page.drawText(line, {
                                x: margin + 15,
                                y: yPos,
                                size: 9,
                                font: fontRegular,
                                color: rgb(0, 0, 0)
                            });
                            yPos -= 12;
                        }
                    }
                }

                yPos -= 10;
            }

            return yPos;
        }

        async function drawSignature(page, yStart) {
            let yPos = yStart - 100;

            const lokasiTanggal = `${data.profil.lokasi}, ${formatDate(data.profil.tanggal)}`;
            const lokasiWidth = fontRegular.widthOfTextAtSize(lokasiTanggal, 11);
            page.drawText(lokasiTanggal, {
                x: centerX - (lokasiWidth / 2),
                y: yPos,
                size: 11,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });
            yPos -= 18;

            const namaBprText = data.bpr.namabpr;
            const namaBprWidth = fontBold.widthOfTextAtSize(namaBprText, 11);
            page.drawText(namaBprText, {
                x: centerX - (namaBprWidth / 2),
                y: yPos,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            yPos -= 80;

            const namaDir = data.profil.dirkep || 'Nama Direktur';
            const namaDirWidth = fontBold.widthOfTextAtSize(namaDir, 11);
            const namaDirX = centerX - (namaDirWidth / 2);

            page.drawText(namaDir, {
                x: namaDirX,
                y: yPos,
                size: 13,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            page.drawLine({
                start: { x: namaDirX, y: yPos - 3 },
                end: { x: namaDirX + namaDirWidth, y: yPos - 3 },
                thickness: 1,
                color: rgb(0, 0, 0)
            });
            yPos -= 18;

            const jabatan = 'Direktur';
            const jabatanWidth = fontRegular.widthOfTextAtSize(jabatan, 10);
            page.drawText(jabatan, {
                x: centerX - (jabatanWidth / 2),
                y: yPos,
                size: 10,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        }

        window.onload = () => {
            generateLaporanTKS();
        };
    </script>
</body>

</html>