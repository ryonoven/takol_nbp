<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Profil Risiko</title>
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
        // GANTI dengan base URL sesuai project Anda
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

            if (currentLine) {
                lines.push(currentLine);
            }

            return lines;
        }

        // Fungsi untuk menggambar header
        function drawHeader(currentPage, isFirstPage = false) {
            let headerYPos = height - margin;

            // Logo
            if (logoImage) {
                const logoSize = 35;
                currentPage.drawImage(logoImage, {
                    x: margin,
                    y: headerYPos - logoSize,
                    width: logoSize + 60,
                    height: logoSize
                });
            }

            // Nama BPR
            const namaBPRLines = wrapTextByCharLimit(data.bpr.namabpr, 50);
            namaBPRLines.forEach((line, index) => {
                const lineWidth = fontBold.widthOfTextAtSize(line, 12);
                currentPage.drawText(line, {
                    x: centerX - (lineWidth / 2),
                    y: headerYPos - (index * 14),
                    size: 12,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
            });
            headerYPos -= (namaBPRLines.length * 14) + 2;

            // Alamat
            const alamatLines = wrapTextByCharLimit(data.bpr.alamat, 75);
            alamatLines.forEach((line, index) => {
                const lineWidth = fontRegular.widthOfTextAtSize(line, 8);
                currentPage.drawText(line, {
                    x: centerX - (lineWidth / 2),
                    y: headerYPos - (index * 10),
                    size: 9,
                    font: fontRegular,
                    color: rgb(0.2, 0.2, 0.2)
                });
            });
            headerYPos -= (alamatLines.length * 10) + 2;

            // Telepon
            const telepon = `Telepon: ${data.bpr.nomor}`;
            const teleponWidth = fontRegular.widthOfTextAtSize(telepon, 8);
            currentPage.drawText(telepon, {
                x: centerX - (teleponWidth / 2),
                y: headerYPos,
                size: 8,
                font: fontRegular,
                color: rgb(0.2, 0.2, 0.2)
            });
            headerYPos -= 10;

            // Website & Email
            const contact = `Website: ${data.bpr.webbpr || '-'}. Email: ${data.bpr.email || '-'}`;
            const contactWidth = fontRegular.widthOfTextAtSize(contact, 8);
            currentPage.drawText(contact, {
                x: centerX - (contactWidth / 2),
                y: headerYPos,
                size: 8,
                font: fontRegular,
                color: rgb(0.2, 0.2, 0.2)
            });
            headerYPos -= 15;

            // Garis pemisah
            currentPage.drawLine({
                start: { x: margin, y: headerYPos },
                end: { x: width - margin, y: headerYPos },
                thickness: 2,
                color: rgb(0, 0, 0)
            });
            headerYPos -= 20;

            return headerYPos; // Mengembalikan posisi Y untuk konten selanjutnya
        }

        function getRiskColor(value) {
            if (!value || value === 0) return [0.8, 0.8, 0.8]; // Gray
            switch (parseInt(value)) {
                case 1: return [0, 1, 0]; // Green - Sangat Rendah
                case 2: return [0.56, 0.93, 0.56]; // Light Green - Rendah
                case 3: return [1, 1, 0]; // Yellow - Sedang
                case 4: return [1, 0.65, 0]; // Orange - Tinggi
                case 5: return [1, 0, 0]; // Red - Sangat Tinggi
                default: return [0.8, 0.8, 0.8];
            }
        }

        async function generateLaporanProfilRisiko() {
            try {
                function drawPageHeader(page, yStart) {
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

                    const contact = `Website: ${data.bpr.webbpr || '-'} Email: ${data.bpr.email || '-'}`;
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
                updateProgress(10, 'Mengambil data dari server...', '🔄');

                // Ambil data dari endpoint yang akan dibuat
                const response = await fetch(`${BASE_URL}/Showprofilresiko/exportLaporanProfilRisikoJSON`);
                const result = await response.json();

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Gagal mengambil data');
                }

                const data = result.data;

                updateProgress(30, 'Membuat dokumen PDF...', '📝');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(40, 'Menambahkan font...', '✍️');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                updateProgress(50, 'Membuat halaman...', '📄');

                // const page = pdfDoc.addPage([595, 842]); // A4
                let page = pdfDoc.addPage([595, 842]); // A4
                const { width, height } = page.getSize();

                const margin = 30;
                const centerX = width / 2;
                let yPos = height - margin;

                // === HEADER ===
                updateProgress(55, 'Menambahkan header...', '🖼️');

                // Logo (jika ada)
                // if (data.bpr.logo) {
                //     try {
                //         const logoBytes = await fetch(data.bpr.logo).then(res => res.arrayBuffer());
                //         let logoImage;

                //         if (data.bpr.logo.includes('image/png') || data.bpr.logo.endsWith('.png')) {
                //             logoImage = await pdfDoc.embedPng(logoBytes);
                //         } else {
                //             logoImage = await pdfDoc.embedJpg(logoBytes);
                //         }

                //         const logoSize = 35;
                //         page.drawImage(logoImage, {
                //             x: margin,
                //             y: yPos - logoSize,
                //             width: logoSize + 60,
                //             height: logoSize
                //         });
                //     } catch (e) {
                //         console.warn('Logo tidak dapat dimuat:', e);
                //     }
                // }


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

                if (logoImage) {
                    const logoSize = 35;
                    page.drawImage(logoImage, {
                        x: margin,
                        y: yPos - logoSize,
                        width: logoSize + 60,
                        height: logoSize
                    });
                }

                // Nama BPR
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

                // Alamat
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

                // Telepon
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

                // Website & Email
                const contact = `Website: ${data.bpr.webbpr || '-'}. Email: ${data.bpr.email || '-'}`;
                const contactWidth = fontRegular.widthOfTextAtSize(contact, 8);
                page.drawText(contact, {
                    x: centerX - (contactWidth / 2),
                    y: yPos,
                    size: 8,
                    font: fontRegular,
                    color: rgb(0.2, 0.2, 0.2)
                });
                yPos -= 15;

                // Garis pemisah
                page.drawLine({
                    start: { x: margin, y: yPos },
                    end: { x: width - margin, y: yPos },
                    thickness: 2,
                    color: rgb(0, 0, 0)
                });
                yPos -= 20;

                // === JUDUL ===
                updateProgress(60, 'Menambahkan konten...', '📝');

                const judul = 'LAPORAN PROFIL RISIKO';
                const judulWidth = fontBold.widthOfTextAtSize(judul, 14);
                page.drawText(judul, {
                    x: centerX - (judulWidth / 2),
                    y: yPos,
                    size: 14,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 25;

                // === A. PROFIL RISIKO ===
                page.drawText('A. PROFIL RISIKO', {
                    x: margin,
                    y: yPos,
                    size: 11,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 20;

                // Data profil
                const profilData = [
                    ['Periode', `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`],
                    ['Nama BPR', data.bpr.namabpr],
                    ['Alamat', data.bpr.alamat],
                    ['Nomor Telepon', data.bpr.nomor],
                    ['Modal Inti', data.periode.modalinti],
                    ['Total Aset', data.periode.totalaset],
                    ['Jumlah Kantor Cabang', data.periode.kantorcabang],
                    ['Kegiatan sebagai penerbit kartu ATM atau kartu debit', data.periode.atmdebit]
                ];

                profilData.forEach(([label, value]) => {
                    // Label
                    page.drawText(label, {
                        x: margin + 10,
                        y: yPos,
                        size: 8.5,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });

                    // Titik dua
                    page.drawText(':', {
                        x: margin + 220,
                        y: yPos,
                        size: 9,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });

                    // Value (bisa multiline untuk alamat)
                    if (label === 'Alamat') {
                        const valueLines = wrapTextByCharLimit(value, 60);
                        valueLines.forEach((line, idx) => {
                            page.drawText(line, {
                                x: margin + 230,
                                y: yPos - (idx * 10),
                                size: 9,
                                font: fontRegular,
                                color: rgb(0, 0, 0)
                            });
                        });
                        yPos -= (valueLines.length * 10) + 5;
                    } else {
                        page.drawText(value, {
                            x: margin + 230,
                            y: yPos,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 15;
                    }
                });

                yPos -= 10;

                // === TABEL PENILAIAN ===
                updateProgress(70, 'Membuat tabel penilaian...', '📊');

                // Header tabel
                const tableStartY = yPos;
                const rowHeight = 25;
                const colWidths = [85, 75, 75, 75, 75, 75, 75];
                const tableWidth = colWidths.reduce((a, b) => a + b, 0);
                const tableStartX = margin;

                // Background header
                page.drawRectangle({
                    x: tableStartX,
                    y: yPos - rowHeight * 2,
                    width: tableWidth,
                    height: rowHeight * 2,
                    color: rgb(0.85, 0.85, 0.85),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                // Header row 1
                page.drawText('Penilaian Per Posisi', {
                    x: tableStartX + colWidths[0] + 63,
                    y: yPos - 17,
                    size: 11,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                page.drawText(`Penilaian Posisi Sebelumnya`, {
                    x: tableStartX + colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3] + 40,
                    y: yPos - 13,
                    size: 11,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                page.drawText(`(Semester ${data.periode.periodeSebelumSemester} Tahun ${data.periode.periodeSebelumTahun})`, {
                    x: tableStartX + colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3] + 72,
                    y: yPos - 23,
                    size: 8,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });

                yPos -= rowHeight * 2;

                // Header row 2
                const headers = [
                    'Jenis Risiko',
                    'Inheren\nTingkat Risiko',
                    'Risiko\nManajemen\nPenerapan\nTingkat Kualitas',
                    'Tingkat Risiko',
                    'Inheren\nTingkat Risiko',
                    'Risiko\nManajemen\nPenerapan\nTingkat Kualitas',
                    'Tingkat Risiko'
                ];

                page.drawRectangle({
                    x: tableStartX,
                    y: yPos - rowHeight,
                    width: tableWidth,
                    height: rowHeight + 22,
                    color: rgb(0.85, 0.85, 0.85),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                let currentX = tableStartX;
                headers.forEach((header, idx) => {
                    const lines = header.split('\n');
                    const startY = yPos - 8 - ((lines.length - 1) * 3);

                    lines.forEach((line, lineIdx) => {
                        const textWidth = fontBold.widthOfTextAtSize(line, 7);
                        page.drawText(line, {
                            x: currentX + (colWidths[idx] - textWidth) / 2 - 4,
                            y: startY + (lineIdx * 8),
                            size: 8,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                    });

                    // Border vertikal
                    if (idx < headers.length - 1) {
                        page.drawLine({
                            start: { x: currentX + colWidths[idx], y: yPos + 22 },
                            end: { x: currentX + colWidths[idx], y: yPos - rowHeight },
                            thickness: 1,
                            color: rgb(0, 0, 0)
                        });
                    }

                    currentX += colWidths[idx];
                });

                yPos -= rowHeight;

                // Data rows
                const risikoTypes = [
                    { name: 'Risiko Kredit', key: 'kredit' },
                    { name: 'Risiko Operasional', key: 'operasional' },
                    { name: 'Risiko Kepatuhan', key: 'kepatuhan' },
                    { name: 'Risiko Likuiditas', key: 'likuiditas' }
                ];

                // Tambahkan risiko reputasi & stratejik jika ada
                if (data.risiko.reputasi && data.risiko.reputasi.current) {
                    risikoTypes.push({ name: 'Risiko Reputasi', key: 'reputasi' });
                }
                if (data.risiko.stratejik && data.risiko.stratejik.current) {
                    risikoTypes.push({ name: 'Risiko Stratejik', key: 'stratejik' });
                }

                risikoTypes.forEach((rType) => {
                    const rData = data.risiko[rType.key];

                    // Background row
                    page.drawRectangle({
                        x: tableStartX,
                        y: yPos - rowHeight,
                        width: tableWidth,
                        height: rowHeight,
                        borderColor: rgb(0, 0, 0),
                        borderWidth: 1
                    });

                    currentX = tableStartX;

                    // Jenis Risiko - Posisikan di tengah vertikal
                    const risikoText = rType.name;
                    const textWidth = fontRegular.widthOfTextAtSize(risikoText, 9);
                    const textX = currentX + 5;
                    const textY = yPos - (rowHeight / 2) - 3; // Posisi tengah vertikal

                    page.drawText(risikoText, {
                        x: textX,
                        y: textY,
                        size: 9,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });

                    currentX += colWidths[0];

                    // Kolom dengan warna background
                    const values = [
                        { val: rData.inherenCurrent, color: getRiskColor(rData.inherenCurrent) },
                        { val: rData.kpmrCurrent, color: getRiskColor(rData.kpmrCurrent) },
                        { val: rData.current, color: getRiskColor(rData.current) },
                        { val: rData.inherenPrevious, color: getRiskColor(rData.inherenPrevious) },
                        { val: rData.kpmrPrevious, color: getRiskColor(rData.kpmrPrevious) },
                        { val: rData.previous, color: getRiskColor(rData.previous) }
                    ];

                    values.forEach((item, idx) => {
                        // Background color
                        page.drawRectangle({
                            x: currentX,
                            y: yPos - rowHeight,
                            width: colWidths[idx + 1],
                            height: rowHeight,
                            color: rgb(item.color[0], item.color[1], item.color[2]),
                            borderColor: rgb(0, 0, 0),
                            borderWidth: 1
                        });

                        // Text value - Posisikan di tengah horizontal dan vertikal
                        if (item.val) {
                            const text = item.val.toString();
                            const valTextWidth = fontBold.widthOfTextAtSize(text, 10);
                            const valTextX = currentX + (colWidths[idx + 1] - valTextWidth) / 2;
                            const valTextY = yPos - (rowHeight / 2) - 3;

                            page.drawText(text, {
                                x: valTextX,
                                y: valTextY,
                                size: 10,
                                font: fontBold,
                                color: rgb(0, 0, 0)
                            });
                        }

                        currentX += colWidths[idx + 1];
                    });

                    yPos -= rowHeight;
                });

                // Peringkat Risiko Row
                page.drawRectangle({
                    x: tableStartX,
                    y: yPos - rowHeight,
                    width: tableWidth,
                    height: rowHeight,
                    color: rgb(0.6, 0.6, 0.6),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                page.drawText('Peringkat Risiko', {
                    x: tableStartX + 5,
                    y: yPos - 15,
                    size: 9,
                    font: fontBold,
                    color: rgb(1, 1, 1)
                });

                // Nilai Peringkat Current
                currentX = tableStartX + colWidths[0] + colWidths[1] + colWidths[2];
                const peringkatCurrentColor = getRiskColor(data.risiko.peringkat.current);
                page.drawRectangle({
                    x: currentX,
                    y: yPos - rowHeight,
                    width: colWidths[3],
                    height: rowHeight,
                    color: rgb(peringkatCurrentColor[0], peringkatCurrentColor[1], peringkatCurrentColor[2]),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                if (data.risiko.peringkat.current) {
                    const text = data.risiko.peringkat.current.toString();
                    const textWidth = fontBold.widthOfTextAtSize(text, 11);
                    page.drawText(text, {
                        x: currentX + (colWidths[3] - textWidth) / 2,
                        y: yPos - 15,
                        size: 11,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                }

                // Nilai Peringkat Previous
                currentX += colWidths[3] + colWidths[4] + colWidths[5];
                const peringkatPrevColor = getRiskColor(data.risiko.peringkat.previous);
                page.drawRectangle({
                    x: currentX,
                    y: yPos - rowHeight,
                    width: colWidths[6],
                    height: rowHeight,
                    color: rgb(peringkatPrevColor[0], peringkatPrevColor[1], peringkatPrevColor[2]),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                if (data.risiko.peringkat.previous) {
                    const text = data.risiko.peringkat.previous.toString();
                    const textWidth = fontBold.widthOfTextAtSize(text, 11);
                    page.drawText(text, {
                        x: currentX + (colWidths[6] - textWidth) / 2,
                        y: yPos - 15,
                        size: 11,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                }

                yPos -= rowHeight + 10;

                // Keterangan Peringkat
                const keterangan = 'Keterangan Peringkat: 1 (Sangat Rendah), 2 (Rendah), 3 (Sedang), 4 (Tinggi), 5 (Sangat Tinggi)';
                const ketWidth = fontRegular.widthOfTextAtSize(keterangan, 9);
                page.drawText(keterangan, {
                    x: centerX - (ketWidth / 2),
                    y: yPos,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0.5, 0.5, 0.5)
                });

                yPos -= 20;

                // === ANALISIS ===
                updateProgress(85, 'Menambahkan analisis...', '📋');

                // Cek apakah perlu halaman baru
                if (yPos < 200) {
                    const newPage = pdfDoc.addPage([595, 842]);
                    yPos = newPage.getSize().height - margin;
                }

                // Box Analisis - tinggi diperbesar untuk menampung kesimpulan
                const analisisBoxHeight = 120;
                page.drawRectangle({
                    x: margin,
                    y: yPos - analisisBoxHeight,
                    width: width - (2 * margin),
                    height: analisisBoxHeight,
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                page.drawText('Analisis', {
                    x: margin + 10,
                    y: yPos - 15,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                yPos -= 30;

                // Hasil analisis intro - gunakan semester dan tahun dari database
                const defaultIntro = `Hasil dari analisis profil risiko Semester ${data.periode.semester} Tahun ${data.periode.tahun} sebagai berikut:`;
                const analisisIntro = data.analisis.intro || defaultIntro;
                const introLines = wrapTextByCharLimit(analisisIntro, 75);
                introLines.forEach(line => {
                    page.drawText(line, {
                        x: margin + 10,
                        y: yPos,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    yPos -= 10;
                });

                yPos -= 5;

                // Bullet points analisis
                const analisisList = [
                    data.analisis.kredit,
                    data.analisis.operasional,
                    data.analisis.kepatuhan,
                    data.analisis.likuiditas
                ];

                if (data.analisis.reputasi) analisisList.push(data.analisis.reputasi);
                if (data.analisis.stratejik) analisisList.push(data.analisis.stratejik);

                analisisList.forEach(item => {
                    page.drawText('•', {
                        x: margin + 15,
                        y: yPos,
                        size: 8,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });

                    const itemLines = wrapTextByCharLimit(item, 180);
                    itemLines.forEach((line, idx) => {
                        page.drawText(line, {
                            x: margin + 25,
                            y: yPos - (idx * 10),
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                    });
                    yPos -= (itemLines.length * 10) + 3;
                });

                yPos -= 10;

                // Tambahkan data kesimpulan dari database (kolom 'kesimpulan')
                if (data.kesimpulan && data.kesimpulan.trim() !== '') {
                    yPos -= 15;

                    // Proses kesimpulan dengan mempertahankan line breaks
                    // Split berdasarkan \n, \r\n, atau <br> yang mungkin ada di database
                    const paragraphs = data.kesimpulan
                        .replace(/\r\n/g, '\n')
                        .replace(/<br\s*\/?>/gi, '\n')
                        .split('\n');

                    // Hitung total lines untuk estimasi tinggi box
                    let totalLines = 0;
                    const wrappedParagraphs = [];

                    paragraphs.forEach(para => {
                        if (para.trim() === '') {
                            wrappedParagraphs.push(['']); // Empty line
                            totalLines += 1;
                        } else {
                            const wrapped = wrapTextByCharLimit(para, 119);
                            wrappedParagraphs.push(wrapped);
                            totalLines += wrapped.length;
                        }
                    });

                    const kesimpulanBoxHeight = (totalLines * 11) + 100;

                    // Cek apakah perlu halaman baru
                    if (yPos < kesimpulanBoxHeight + 50) {
                        page = pdfDoc.addPage([595, 842]);
                        yPos = height - margin;

                        // Gambar header di halaman baru
                        yPos = drawPageHeader(page, yPos);
                    }

                    // Draw box untuk kesimpulan
                    page.drawRectangle({
                        x: margin,
                        y: yPos - kesimpulanBoxHeight,
                        width: width - (2 * margin),
                        height: kesimpulanBoxHeight,
                        borderColor: rgb(0, 0, 0),
                        borderWidth: 1
                    });

                    // Header "Kesimpulan"
                    page.drawText('Kesimpulan', {
                        x: margin + 5,
                        y: yPos - 15,
                        size: 10,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });

                    yPos -= 30;

                    // Tampilkan kesimpulan dengan mempertahankan line breaks
                    wrappedParagraphs.forEach(lines => {
                        lines.forEach(line => {
                            page.drawText(line, {
                                x: margin + 15,
                                y: yPos,
                                size: 9,
                                font: fontRegular,
                                color: rgb(0, 0, 0)
                            });
                            yPos -= 15;
                        });
                    });

                    yPos -= 10;
                }

                updateProgress(88, 'Membuat halaman analisis per jenis risiko...', '📋');

                // Fungsi untuk fetch detail keterangan dari backend
                async function fetchRiskDetailKeterangan(riskType, faktorId) {
                    try {
                        const response = await fetch(`${BASE_URL}/Showprofilresiko/getRiskKeterangan/${riskType}/${faktorId}`);
                        const result = await response.json();

                        if (result.status === 'success') {
                            return result.data || [];
                        }
                        return [];
                    } catch (error) {
                        console.warn(`Error fetching ${riskType} keterangan:`, error);
                        return [];
                    }
                }

                // Fungsi untuk menggambar halaman B. ANALISIS PER JENIS RISIKO
                async function drawAnalisisPerJenisRisiko(pdfDoc) {
                    // Fungsi helper untuk menggambar header
                    function drawPageHeader(page, yStart) {
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

                        const contact = `Website: ${data.bpr.webbpr || '-'} Email: ${data.bpr.email || '-'}`;
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

                    // Array risiko yang akan ditampilkan
                    const risikoArray = [
                        {
                            key: 'kredit',
                            title: 'ANALISIS RISIKO KREDIT',
                            inherenFaktorId: 13,
                            kpmrFaktorId: 33
                        },
                        {
                            key: 'operasional',
                            title: 'ANALISIS RISIKO OPERASIONAL',
                            inherenFaktorId: 48,
                            kpmrFaktorId: 70
                        },
                        {
                            key: 'kepatuhan',
                            title: 'ANALISIS RISIKO KEPATUHAN',
                            inherenFaktorId: 81,
                            kpmrFaktorId: 102
                        },
                        {
                            key: 'likuiditas',
                            title: 'ANALISIS RISIKO LIKUIDITAS',
                            inherenFaktorId: 115,
                            kpmrFaktorId: 135
                        }
                    ];

                    // Tambahkan risiko reputasi dan stratejik jika ada
                    if (data.risiko.reputasi && data.risiko.reputasi.current) {
                        risikoArray.push({
                            key: 'reputasi',
                            title: 'ANALISIS RISIKO REPUTASI',
                            inherenFaktorId: 148,
                            kpmrFaktorId: 168
                        });
                    }

                    if (data.risiko.stratejik && data.risiko.stratejik.current) {
                        risikoArray.push({
                            key: 'stratejik',
                            title: 'ANALISIS RISIKO STRATEJIK',
                            inherenFaktorId: 179,
                            kpmrFaktorId: 199
                        });
                    }

                    // Loop untuk setiap risiko
                    for (let i = 0; i < risikoArray.length; i++) {
                        const risiko = risikoArray[i];

                        // SELALU buat halaman baru untuk setiap analisis risiko
                        let page = pdfDoc.addPage([595, 842]);
                        let yPos = drawPageHeader(page, height - margin);

                        // Judul B. ANALISIS PER JENIS RISIKO (hanya di halaman pertama)
                        if (i === 0) {
                            page.drawText('B. ANALISIS PER JENIS RISIKO', {
                                x: margin,
                                y: yPos,
                                size: 11,
                                font: fontBold,
                                color: rgb(0, 0, 0)
                            });
                            yPos -= 30;
                        }

                        // Judul risiko
                        page.drawText(risiko.title, {
                            x: margin + 10,
                            y: yPos,
                            size: 11,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 20;

                        // Info BPR dan Periode
                        page.drawText(`Nama BPR`, {
                            x: margin + 10,
                            y: yPos,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                        page.drawText(`: ${data.bpr.namabpr}`, {
                            x: margin + 80,
                            y: yPos,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 12;

                        page.drawText(`Periode`, {
                            x: margin + 10,
                            y: yPos,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                        page.drawText(`: Semester ${data.periode.semester} Tahun ${data.periode.tahun}`, {
                            x: margin + 80,
                            y: yPos,
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 20;

                        // Fetch data detail dari backend
                        const inherenKeterangan = await fetchRiskDetailKeterangan(risiko.key, risiko.inherenFaktorId);
                        const kpmrKeterangan = await fetchRiskDetailKeterangan(risiko.key, risiko.kpmrFaktorId);

                        const risikoData = data.risiko[risiko.key];
                        const tingkatRisiko = risikoData.current || '-';
                        const tingkatRisikoText = getRiskText(tingkatRisiko);

                        // === BOX ANALISIS (BAGIAN 1 & 2) ===
                        // Cek apakah cukup ruang untuk box analisis
                        if (yPos < 100) {
                            page = pdfDoc.addPage([595, 842]);
                            yPos = drawPageHeader(page, height - margin);
                        }

                        // Mulai box analisis
                        let boxStartY = yPos;
                        let boxBottomMargin = 50; // Margin bawah halaman

                        // Gambar border box
                        page.drawRectangle({
                            x: margin + 10,
                            y: boxBottomMargin,
                            width: width - (2 * margin) - 20,
                            height: boxStartY - boxBottomMargin,
                            borderColor: rgb(0, 0, 0),
                            borderWidth: 1
                        });

                        page.drawText('Analisis', {
                            x: margin + 20,
                            y: yPos - 15,
                            size: 10,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 30;

                        // 1. Tingkat Risiko
                        if (yPos < boxBottomMargin + 20) {
                            page = pdfDoc.addPage([595, 842]);
                            yPos = drawPageHeader(page, height - margin);
                            boxStartY = yPos;

                            page.drawRectangle({
                                x: margin + 10,
                                y: boxBottomMargin,
                                width: width - (2 * margin) - 20,
                                height: boxStartY - boxBottomMargin,
                                borderColor: rgb(0, 0, 0),
                                borderWidth: 1
                            });
                            yPos -= 20;
                        }

                        page.drawText(`1. Tingkat Risiko:`, {
                            x: margin + 20,
                            y: yPos,
                            size: 10,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 12;

                        if (yPos < boxBottomMargin + 20) {
                            page = pdfDoc.addPage([595, 842]);
                            yPos = drawPageHeader(page, height - margin);
                            boxStartY = yPos;

                            page.drawRectangle({
                                x: margin + 10,
                                y: boxBottomMargin,
                                width: width - (2 * margin) - 20,
                                height: boxStartY - boxBottomMargin,
                                borderColor: rgb(0, 0, 0),
                                borderWidth: 1
                            });
                            yPos -= 20;
                        }

                        page.drawText(`   Peringkat ${tingkatRisiko}, ${tingkatRisikoText}`, {
                            x: margin + 24,
                            y: yPos,
                            size: 10,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 18;

                        // 2. Risiko Inheren
                        if (yPos < boxBottomMargin + 20) {
                            page = pdfDoc.addPage([595, 842]);
                            yPos = drawPageHeader(page, height - margin);
                            boxStartY = yPos;

                            page.drawRectangle({
                                x: margin + 10,
                                y: boxBottomMargin,
                                width: width - (2 * margin) - 20,
                                height: boxStartY - boxBottomMargin,
                                borderColor: rgb(0, 0, 0),
                                borderWidth: 1
                            });
                            yPos -= 50;
                        }

                        page.drawText(`2. Risiko Inheren:`, {
                            x: margin + 20,
                            y: yPos,
                            size: 10,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 25;

                        // Tampilkan semua keterangan inheren dari database
                        if (inherenKeterangan.length > 0) {
                            for (let idx = 0; idx < inherenKeterangan.length; idx++) {
                                const item = inherenKeterangan[idx];
                                // mulai dari no 1
                                const keteranganText = `   ${idx + 1}. ${item.keterangan || ''}`;
                                const keteranganLines = wrapTextByCharLimit(keteranganText, 100);

                                for (let lineIdx = 0; lineIdx < keteranganLines.length; lineIdx++) {
                                    const line = keteranganLines[lineIdx];

                                    // Cek apakah perlu halaman baru
                                    if (yPos < boxBottomMargin + 20) {
                                        page = pdfDoc.addPage([595, 842]);
                                        yPos = drawPageHeader(page, height - margin);
                                        boxStartY = yPos;

                                        // Gambar box baru
                                        page.drawRectangle({
                                            x: margin + 10,
                                            y: boxBottomMargin,
                                            width: width - (2 * margin) - 20,
                                            height: boxStartY - boxBottomMargin,
                                            borderColor: rgb(0, 0, 0),
                                            borderWidth: 1
                                        });
                                        yPos -= 20;
                                    }

                                    page.drawText(line, {
                                        x: margin + 30,
                                        y: yPos,
                                        size: 10,
                                        font: fontRegular,
                                        color: rgb(0, 0, 0)
                                    });
                                    yPos -= 14;
                                }
                            }
                        } else {
                            const defaultText = `   Tingkat Risiko Inheren: ${risikoData.inherenCurrent || '-'} (${getRiskText(risikoData.inherenCurrent)})`;

                            if (yPos < boxBottomMargin + 20) {
                                page = pdfDoc.addPage([595, 842]);
                                yPos = drawPageHeader(page, height - margin);
                                boxStartY = yPos;

                                page.drawRectangle({
                                    x: margin + 10,
                                    y: boxBottomMargin,
                                    width: width - (2 * margin) - 20,
                                    height: boxStartY - boxBottomMargin,
                                    borderColor: rgb(0, 0, 0),
                                    borderWidth: 1
                                });
                                yPos -= 20;
                            }

                            page.drawText(defaultText, {
                                x: margin + 20,
                                y: yPos,
                                size: 8.5,
                                font: fontRegular,
                                color: rgb(0, 0, 0)
                            });
                            yPos -= 10;
                        }

                        yPos -= 20;

                        // === BOX KPMR (BAGIAN 3) ===
                        // Cek apakah perlu halaman baru untuk box KPMR
                        if (yPos < 100) {
                            page = pdfDoc.addPage([595, 842]);
                            yPos = drawPageHeader(page, height - margin);
                        }

                        boxStartY = yPos;

                        // Gambar border box KPMR
                        page.drawRectangle({
                            x: margin + 10,
                            y: boxBottomMargin,
                            width: width - (2 * margin) - 20,
                            height: boxStartY - boxBottomMargin,
                            borderColor: rgb(0, 0, 0),
                            borderWidth: 1
                        });

                        // 3. Kualitas Penerapan Manajemen Risiko
                        page.drawText('3. Kualitas Penerapan Manajemen Risiko:', {
                            x: margin + 20,
                            y: yPos - 15,
                            size: 10,
                            font: fontBold,
                            color: rgb(0, 0, 0)
                        });
                        yPos -= 35;

                        // Tampilkan semua keterangan KPMR dari database
                        if (kpmrKeterangan.length > 0) {
                            for (let idx = 0; idx < kpmrKeterangan.length; idx++) {
                                const item = kpmrKeterangan[idx];
                                const keteranganText = `   ${idx + 1}. ${item.keterangan || ''}`;
                                const keteranganLines = wrapTextByCharLimit(keteranganText, 100);

                                for (let lineIdx = 0; lineIdx < keteranganLines.length; lineIdx++) {
                                    const line = keteranganLines[lineIdx];

                                    // Cek apakah perlu halaman baru
                                    if (yPos < boxBottomMargin + 20) {
                                        page = pdfDoc.addPage([595, 842]);
                                        yPos = drawPageHeader(page, height - margin);
                                        boxStartY = yPos;

                                        // Gambar box baru
                                        page.drawRectangle({
                                            x: margin + 10,
                                            y: boxBottomMargin,
                                            width: width - (2 * margin) - 20,
                                            height: boxStartY - boxBottomMargin,
                                            borderColor: rgb(0, 0, 0),
                                            borderWidth: 1
                                        });
                                        yPos -= 20;
                                    }

                                    page.drawText(line, {
                                        x: margin + 31,
                                        y: yPos,
                                        size: 10,
                                        font: fontRegular,
                                        color: rgb(0, 0, 0)
                                    });
                                    yPos -= 14;
                                }
                            }
                        } else {
                            const defaultText = `   Tingkat KPMR: ${risikoData.kpmrCurrent || '-'} (${getRiskText(risikoData.kpmrCurrent)})`;

                            if (yPos < boxBottomMargin + 20) {
                                page = pdfDoc.addPage([595, 842]);
                                yPos = drawPageHeader(page, height - margin);
                                boxStartY = yPos;

                                page.drawRectangle({
                                    x: margin + 10,
                                    y: boxBottomMargin,
                                    width: width - (2 * margin) - 20,
                                    height: boxStartY - boxBottomMargin,
                                    borderColor: rgb(0, 0, 0),
                                    borderWidth: 1
                                });
                                yPos -= 20;
                            }

                            page.drawText(defaultText, {
                                x: margin + 20,
                                y: yPos,
                                size: 8.5,
                                font: fontRegular,
                                color: rgb(0, 0, 0)
                            });
                            yPos -= 10;
                        }

                        yPos -= 20;
                    }

                    updateProgress(98, 'Menambahkan lembar tanda tangan...', '✍️');

                    // === LEMBAR TANDA TANGAN ===
                    // Buat halaman baru untuk tanda tangan
                    page = pdfDoc.addPage([595, 842]);
                    yPos = height - margin;

                    // Gambar header
                    yPos = drawPageHeader(page, yPos);

                    yPos -= 100; // Jarak dari header

                    // Lokasi dan Tanggal (tengah)
                    const lokasiTanggal = `${data.profil.lokasi || ''}, ${data.profil.tanggal || '-'}`;
                    const lokasiWidth = fontRegular.widthOfTextAtSize(lokasiTanggal, 11);
                    page.drawText(lokasiTanggal, {
                        x: centerX - (lokasiWidth / 2),
                        y: yPos,
                        size: 11,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    yPos -= 18;

                    // Nama BPR (tengah)
                    const namaBprText = data.bpr.namabpr || 'PT BPR NBP';
                    const namaBprWidth = fontBold.widthOfTextAtSize(namaBprText, 11);
                    page.drawText(namaBprText, {
                        x: centerX - (namaBprWidth / 2),
                        y: yPos,
                        size: 11,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    yPos -= 80; // Space untuk tanda tangan

                    // Nama Direktur Kepatuhan (tengah, dengan underline)
                    const namaDir = data.profil.dirkep || 'Nama Direktur';
                    const namaDirWidth = fontBold.widthOfTextAtSize(namaDir, 11);
                    const namaDirX = centerX - (namaDirWidth / 2);

                    page.drawText(namaDir, {
                        x: namaDirX,
                        y: yPos,
                        size: 11,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });

                    // Garis bawah nama
                    page.drawLine({
                        start: { x: namaDirX, y: yPos - 3 },
                        end: { x: namaDirX + namaDirWidth, y: yPos - 3 },
                        thickness: 1,
                        color: rgb(0, 0, 0)
                    });
                    yPos -= 18;

                    // Jabatan (tengah)
                    const jabatan = 'Direktur Yang Membawahi Fungsi Kepatuhan';
                    const jabatanWidth = fontRegular.widthOfTextAtSize(jabatan, 10);
                    page.drawText(jabatan, {
                        x: centerX - (jabatanWidth / 2),
                        y: yPos,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });

                    updateProgress(100, 'Menyimpan PDF...', '💾');
                }

                function getRiskText(value) {
                    switch (parseInt(value)) {
                        case 1: return 'Sangat Rendah';
                        case 2: return 'Rendah';
                        case 3: return 'Sedang';
                        case 4: return 'Tinggi';
                        case 5: return 'Sangat Tinggi';
                        default: return '-';
                    }
                }

                // Panggil fungsi untuk menggambar analisis per jenis risiko
                await drawAnalisisPerJenisRisiko(pdfDoc);



                updateProgress(95, 'Menyimpan PDF...', '💾');

                // Save PDF
                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                updateProgress(100, 'Membuka PDF...', '✅');

                // Open in new tab
                const fileName = `00. Laporan Profil Risiko Semester ${data.periode.semester} Tahun ${data.periode.tahun}.pdf`;

                // Download PDF dengan nama file
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Buka di tab baru juga (opsional)
                // window.open(url, '_blank');

                // Close after delay
                setTimeout(() => {
                    URL.revokeObjectURL(url);
                    window.close();
                }, 2000);

            } catch (error) {
                console.error('Error:', error);
                showError(error.message || 'Terjadi kesalahan saat membuat PDF');
            }
        }

        // Auto start
        window.onload = () => {
            generateLaporanProfilRisiko();
        };
    </script>
</body>

</html>