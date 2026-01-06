<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Pernyataan - <?= esc($bpr['namabpr']) ?></title>
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

        async function generateLembarPernyataan() {
            try {
                updateProgress(10, 'Mengambil data dari server...', '🔄');

                const response = await fetch(`${BASE_URL}/Showprofilresiko/exportLembarPernyataanJSON`);
                const result = await response.json();

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Gagal mengambil data');
                }

                const data = result.data;

                updateProgress(30, 'Membuat dokumen PDF...', '📝');

                const {
                    PDFDocument,
                    rgb,
                    StandardFonts
                } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(40, 'Menambahkan font...', '✍️');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                updateProgress(50, 'Membuat halaman...', '📄');

                // Buat halaman A4 (595 x 842 points)
                const page = pdfDoc.addPage([595, 842]);
                const {
                    width,
                    height
                } = page.getSize();

                const margin = 20;
                const centerX = width / 2;
                let yPosition = height - margin;

                // === HEADER: Logo dan Info BPR ===
                updateProgress(60, 'Menambahkan logo dan header...', '🖼️');

                // Function untuk wrap text berdasarkan jumlah karakter (MAX 90)
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

                // Logo di pojok kiri atas
                if (data.bpr.logo) {
                    try {
                        const logoBytes = await fetch(data.bpr.logo).then(res => res.arrayBuffer());
                        let logoImage;

                        if (data.bpr.logo.includes('image/png')) {
                            logoImage = await pdfDoc.embedPng(logoBytes);
                        } else {
                            logoImage = await pdfDoc.embedJpg(logoBytes);
                        }

                        const logoSize = 40;
                        page.drawImage(logoImage, {
                            x: margin,
                            y: yPosition - logoSize,
                            width: logoSize + 70,
                            height: logoSize
                        });
                    } catch (e) {
                        console.warn('Logo tidak dapat dimuat:', e);
                    }
                }

                // Nama BPR (MAX 90 karakter per baris)
                const namaBPRText = data.bpr.namabpr || 'PT BPR';
                const namaBPRLines = wrapTextByCharLimit(namaBPRText, 40);

                namaBPRLines.forEach((line, index) => {
                    const lineWidth = fontBold.widthOfTextAtSize(line, 12);
                    page.drawText(line, {
                        x: centerX - (lineWidth / 2),
                        y: yPosition - 10 - (index * 14),
                        size: 12,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                });

                yPosition -= 10 + (namaBPRLines.length * 14);

                // Alamat (MAX 90 karakter per baris)
                const alamatText = data.bpr.alamat || '';
                const alamatLines = wrapTextByCharLimit(alamatText, 90);

                alamatLines.forEach((line, index) => {
                    const lineWidth = fontRegular.widthOfTextAtSize(line, 8);
                    page.drawText(line, {
                        x: centerX - (lineWidth / 2),
                        y: yPosition - (index * 11),
                        size: 8,
                        font: fontRegular,
                        color: rgb(0.2, 0.2, 0.2)
                    });
                });

                yPosition -= (alamatLines.length * 11) + 2;

                // Telepon
                const teleponText = `Telepon: ${data.bpr.nomor || '-'}`;
                const teleponWidth = fontRegular.widthOfTextAtSize(teleponText, 8);
                page.drawText(teleponText, {
                    x: centerX - (teleponWidth / 2),
                    y: yPosition,
                    size: 8,
                    font: fontRegular,
                    color: rgb(0.2, 0.2, 0.2)
                });

                yPosition -= 12;

                // Website dan Email (MAX 90 karakter per baris)
                const websiteEmailText = `Website: ${data.bpr.webbpr || '-'}. Email: ${data.bpr.email || '-'}`;
                const websiteEmailLines = wrapTextByCharLimit(websiteEmailText, 90);

                websiteEmailLines.forEach((line, index) => {
                    const lineWidth = fontRegular.widthOfTextAtSize(line, 8);
                    page.drawText(line, {
                        x: centerX - (lineWidth / 2),
                        y: yPosition - (index * 11),
                        size: 8,
                        font: fontRegular,
                        color: rgb(0.2, 0.2, 0.2)
                    });
                });

                yPosition -= (websiteEmailLines.length * 11) + 5;

                // Garis pemisah
                page.drawLine({
                    start: {
                        x: margin,
                        y: yPosition
                    },
                    end: {
                        x: width - margin,
                        y: yPosition
                    },
                    thickness: 2,
                    color: rgb(0, 0, 0)
                });

                updateProgress(70, 'Menambahkan konten...', '📝');

                // === JUDUL ===
                yPosition -= 40;
                const judulText = 'LEMBAR PERNYATAAN';
                const judulWidth = fontBold.widthOfTextAtSize(judulText, 14);
                page.drawText(judulText, {
                    x: centerX - (judulWidth / 2),
                    y: yPosition,
                    size: 14,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                // === ISI PERNYATAAN ===
                yPosition -= 50;

                // Paragraf pembuka
                const textLines = [
                    'Dengan ini kami menyatakan bahwa',
                    `Laporan Profil Risiko ${data.bpr.namabpr || 'PT BPR'}`,
                    `Semester ${data.periode.semester || ''} Tahun ${data.periode.tahun || ''}`
                ];

                textLines.forEach(line => {
                    const lineWidth = fontBold.widthOfTextAtSize(line, 10);
                    page.drawText(line, {
                        x: centerX - (lineWidth / 2),
                        y: yPosition,
                        size: 10,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    yPosition -= 20;
                });

                // Paragraf peraturan
                yPosition -= 10;
                const paragraf = [
                    'Telah disusun sesuai dengan hasil penilaian atas Penerapan Manajemen Risiko BPR yang mengacu pada',
                    'ketentuan OJK sebagai berikut:'
                ];

                paragraf.forEach(line => {
                    const lineWidth = fontRegular.widthOfTextAtSize(line, 8);
                    page.drawText(line, {
                        x: margin + 50,
                        y: yPosition,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    yPosition -= 18;
                });

                // Daftar peraturan
                yPosition -= 10;
                const peraturan = [
                    '1. POJK No. 13/ POJK.03/2015 tentang Penerapan Manajemen Risiko bagi BPR tanggal 12 November ',
                    '    2015.',
                    '2. Surat Edaran OJK (SEOJK) No. 1/SEOJK.03/2019 tentang Penerapan Manajemen Risiko bagi BPR',
                    '    tanggal 21 Januari 2019.'
                ];

                peraturan.forEach(line => {
                    const lineWidth = fontRegular.widthOfTextAtSize(line, 10);
                    page.drawText(line, {
                        x: margin + 50,
                        y: yPosition,
                        size: 10,
                        font: fontRegular,
                        color: rgb(0, 0, 0)
                    });
                    yPosition -= 18;
                });

                updateProgress(80, 'Menambahkan tanda tangan...', '✍️');

                // === TTD SECTION ===
                yPosition -= 25;

                // Lokasi dan Tanggal
                const lokasiTanggal = `${data.profil.lokasi || ''}, ${data.profil.tanggal || '-'}`;
                const lokasiWidth = fontRegular.widthOfTextAtSize(lokasiTanggal, 10);
                page.drawText(lokasiTanggal, {
                    x: centerX - (lokasiWidth / 2),
                    y: yPosition,
                    size: 10,
                    font: fontRegular
                });

                yPosition -= 20;
                const namaBPR = data.bpr.namabpr || 'PT BPR';
                const namaBPRWidth2 = fontBold.widthOfTextAtSize(namaBPR, 10);
                page.drawText(namaBPR, {
                    x: centerX - (namaBPRWidth2 / 2),
                    y: yPosition,
                    size: 10,
                    font: fontBold
                });

                // Penyusun (PE)
                yPosition -= 20;
                const penyusunText = 'Penyusun';
                const penyusunWidth = fontBold.widthOfTextAtSize(penyusunText, 10);
                page.drawText(penyusunText, {
                    x: centerX - (penyusunWidth / 2),
                    y: yPosition,
                    size: 10,
                    font: fontBold
                });

                yPosition -= 100;
                const peNama = data.profil.pe || '';
                const peWidth = fontBold.widthOfTextAtSize(peNama, 10);
                page.drawText(peNama, {
                    x: centerX - (peWidth / 2),
                    y: yPosition + 10,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                yPosition -= 5;
                const peJabatan = 'Pejabat Eksekutif Manajemen Risiko';
                const peJabatanWidth = fontRegular.widthOfTextAtSize(peJabatan, 10);
                page.drawText(peJabatan, {
                    x: centerX - (peJabatanWidth / 2),
                    y: yPosition,
                    size: 10,
                    font: fontRegular
                });

                // Menyetujui (2 Direktur)
                yPosition -= 40;
                const menyetujuiText = 'Menyetujui';
                const menyetujuiWidth = fontBold.widthOfTextAtSize(menyetujuiText, 10);
                page.drawText(menyetujuiText, {
                    x: centerX - (menyetujuiWidth / 2),
                    y: yPosition,
                    size: 10,
                    font: fontBold
                });

                // Direktur Utama (kiri)
                const leftX = margin + 60;
                yPosition -= 100;

                const dirutNama = data.profil.dirut || '';
                page.drawText(dirutNama, {
                    x: leftX,
                    y: yPosition,
                    size: 10,
                    font: fontBold
                });

                yPosition -= 15;
                page.drawText('Direktur Utama', {
                    x: leftX,
                    y: yPosition,
                    size: 10,
                    font: fontRegular
                });

                // Direktur Kepatuhan (kanan)
                const rightX = width - margin - 200;
                yPosition += 15;

                const dirkepNama = data.profil.dirkep || '';
                page.drawText(dirkepNama, {
                    x: rightX,
                    y: yPosition,
                    size: 10,
                    font: fontBold
                });

                yPosition -= 15;
                page.drawText('Direktur Operasional yang juga membawahkan', {
                    x: rightX,
                    y: yPosition,
                    size: 9,
                    font: fontRegular
                });

                yPosition -= 12;
                page.drawText('fungsi kepatuhan', {
                    x: rightX,
                    y: yPosition,
                    size: 9,
                    font: fontRegular
                });

                updateProgress(90, 'Menyimpan PDF...', '💾');

                // Save PDF
                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], {
                    type: 'application/pdf'
                });
                const url = URL.createObjectURL(blob);

                updateProgress(100, 'Membuka PDF...', '✅');

                // Open in new tab and trigger print
                const printWindow = window.open(url, '_blank');
                if (autoGenerate) {
                    window.onload = () => {
                        setTimeout(() => {
                            generatePDF();
                        }, 500);
                    };
                }

                // Close after delay
                setTimeout(() => {
                    window.close();
                }, 2000);

            } catch (error) {
                console.error('Error:', error);
                showError(error.message || 'Terjadi kesalahan saat membuat PDF');
            }
        }

        // Auto start
        window.onload = () => {
            generateLembarPernyataan();
        };
    </script>
</body>

</html>