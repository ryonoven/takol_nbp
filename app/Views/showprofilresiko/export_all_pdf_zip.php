<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export All PDF to ZIP</title>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loading-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
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
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
            color: #333;
            font-weight: 500;
        }

        .progress-bar-custom {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            margin-top: 20px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .file-list {
            margin-top: 30px;
            max-height: 300px;
            overflow-y: auto;
        }

        .file-list-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: background 0.2s;
        }

        .file-list-item:hover {
            background: #f8f9fa;
        }

        .file-list-item.success {
            color: #28a745;
        }

        .file-list-item.error {
            color: #dc3545;
        }

        .file-list-item.processing {
            color: #ffc107;
        }

        .file-list-item i {
            font-size: 18px;
        }

        .status-icon {
            font-size: 60px;
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-close-window {
            margin-top: 20px;
            width: 100%;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #f5c6cb;
        }

        /* Scrollbar styling */
        .file-list::-webkit-scrollbar {
            width: 8px;
        }

        .file-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .file-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .file-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="loading-container">
        <div class="status-icon" id="statusIcon"></div>
        <div class="spinner" id="spinner"></div>
        <div class="loading-text" id="loadingText">Mempersiapkan data...</div>
        <div class="progress-bar-custom">
            <div class="progress-bar-fill" id="progressBar">0%</div>
        </div>
        <div class="file-list" id="fileList"></div>
        <div class="error-message" id="errorMessage" style="display: none;"></div>
        <button class="btn btn-primary btn-close-window" id="btnClose" style="display: none;" onclick="window.close()">
            Tutup Window
        </button>
    </div>

    <script>
        const BASE_URL = '<?= $baseUrl ?>';
        const { PDFDocument, rgb, StandardFonts } = PDFLib;

        function updateProgress(percent, text) {
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
            document.getElementById('loadingText').textContent = text;
        }

        function addFileToList(filename, status, message = '') {
            const fileList = document.getElementById('fileList');
            const item = document.createElement('div');
            item.className = `file-list-item ${status}`;

            let icon = '<i class="fas fa-spinner fa-spin"></i>';
            if (status === 'success') icon = '<i class="fas fa-check-circle"></i>';
            if (status === 'error') icon = '<i class="fas fa-times-circle"></i>';

            item.innerHTML = `${icon} <span>${filename} ${message}</span>`;
            fileList.appendChild(item);

            // Auto scroll ke bawah
            fileList.scrollTop = fileList.scrollHeight;
        }

        function showError(message) {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('statusIcon').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>';
            document.getElementById('loadingText').textContent = 'Terjadi Kesalahan';
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('btnClose').style.display = 'block';
        }

        function showSuccess(message) {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('statusIcon').innerHTML = '<i class="fas fa-check-circle" style="color: #28a745;"></i>';
            document.getElementById('loadingText').textContent = message;
            document.getElementById('btnClose').style.display = 'block';
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

        function sanitizeText(text) {
            if (!text) return '';

            // Konversi ke string dulu
            let sanitized = String(text);

            // ✅ TAMBAHAN: Replace <br> tags dengan newline SEBELUM sanitasi lainnya
            sanitized = sanitized.replace(/<br\s*\/?>/gi, '\n');
            sanitized = sanitized.replace(/<BR\s*\/?>/g, '\n');

            // Replace HTML entities
            sanitized = sanitized.replace(/&nbsp;/gi, ' ');
            sanitized = sanitized.replace(/&amp;/gi, '&');
            sanitized = sanitized.replace(/&lt;/gi, '<');
            sanitized = sanitized.replace(/&gt;/gi, '>');
            sanitized = sanitized.replace(/&quot;/gi, '"');
            sanitized = sanitized.replace(/&#39;/gi, "'");

            // Remove other HTML tags (after converting br to newline)
            sanitized = sanitized.replace(/<[^>]*>/g, '');

            // Replace karakter matematika
            sanitized = sanitized
                .replace(/≥/g, '>=')
                .replace(/≤/g, '<=')
                .replace(/×/g, 'x')
                .replace(/÷/g, '/')
                .replace(/–/g, '-')
                .replace(/—/g, '-')
                .replace(/"/g, '"')
                .replace(/"/g, '"')
                .replace(/'/g, "'")
                .replace(/'/g, "'")
                .replace(/•/g, '-')
                .replace(/…/g, '...')
                .replace(/™/g, 'TM')
                .replace(/©/g, '(c)')
                .replace(/®/g, '(R)');

            // ✅ PENTING: Hapus SEMUA karakter non-ASCII yang tersisa (tapi SIMPAN \n)
            // Gunakan replace dengan callback untuk preserve newlines
            sanitized = sanitized.replace(/[^\x00-\x7F\n]/g, '');

            return sanitized;
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

        function wrapTextToLines(text, maxWidth, font, fontSize) {
            if (!text) return [''];

            // Text sudah di-sanitize, jadi \n sudah ada dari <br>
            // Split by newlines first
            const paragraphs = text.split('\n');
            const allLines = [];

            paragraphs.forEach(paragraph => {
                paragraph = paragraph.trim();

                if (!paragraph) {
                    // Empty line for spacing between paragraphs
                    allLines.push('');
                    return;
                }

                // Wrap each paragraph
                const words = paragraph.split(' ');
                let currentLine = '';

                words.forEach(word => {
                    const testLine = currentLine ? currentLine + ' ' + word : word;
                    const testWidth = font.widthOfTextAtSize(testLine, fontSize);

                    if (testWidth > maxWidth && currentLine) {
                        allLines.push(currentLine);
                        currentLine = word;
                    } else {
                        currentLine = testLine;
                    }
                });

                if (currentLine) {
                    allLines.push(currentLine);
                }
            });

            return allLines.length > 0 ? allLines : [''];
        }

        function getBackgroundColorByRating(rating) {
            const { rgb } = PDFLib;
            const colors = {
                '1': rgb(0.4, 0.7, 1),      // Biru - Sangat Rendah
                '2': rgb(0.4, 0.9, 0.4),    // Hijau - Rendah
                '3': rgb(1, 0.8, 0.4),      // Kuning - Sedang
                '4': rgb(1, 0.5, 0.5),      // Orange - Tinggi
                '5': rgb(0.7, 0.3, 0.3)     // Merah - Sangat Tinggi
            };
            return colors[String(rating)] || rgb(1, 1, 1);
        }

        function addPageNumbers(pdfDoc, font) {
            const { rgb } = PDFLib;
            const pages = pdfDoc.getPages();
            const totalPages = pages.length;

            pages.forEach((page, index) => {
                const { width, height } = page.getSize();
                const pageNumber = index + 1;

                // Format: "Halaman 1 dari 10" atau cukup "1"
                // const pageText = `${pageNumber}`;
                // Atau jika ingin format lengkap:
                const pageText = `Halaman ${pageNumber} dari ${totalPages}`;

                const textWidth = font.widthOfTextAtSize(pageText, 10);
                const x = (width / 2) - (textWidth / 2); // Center horizontal
                const y = 30; // 30 point dari bawah

                page.drawText(pageText, {
                    x: x,
                    y: y,
                    size: 10,
                    font: font,
                    color: rgb(0, 0, 0)
                });
            });
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
                106: 'Rasio aset likuid terhadap total aset',
                107: 'Rasio aset likuid terhadap kewajiban lancar',
                108: 'Rasio kredit yang diberikan terhadap total dana pihak ketiga bukan bank (Loan to Deposit Ratio/LDR)',
                109: 'Rasio 25 deposan dan penabung terbesar terhadap total dana pihak ketiga',
                110: 'Rasio Pendanaan non inti terhadap total pendanaan',
                112: 'Penilaian kebutuhan pendanaan BPR pada situasi normal maupun krisis, dan kemampuan BPR untuk memenuhi  Kebutuhan pendanaan',
                113: 'Penilaian terhadap seberapa luas atau seberapa besar BPR memiliki komitmen pendanaan yang dapat digunakan jika dibutuhkan.'
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

        // ===== LEMBAR PERNYATAAN GENERATOR =====
        async function generateLembarPernyataanPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            const page = pdfDoc.addPage([595, 842]);
            const { width, height } = page.getSize();

            const margin = 20;
            const centerX = width / 2;
            let yPosition = height - margin;

            // Logo
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

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        // 2. RISIKO KREDIT INHEREN + KPMR
        async function generateRisikoKreditPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // Risiko Kredit Inheren
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            let logoImage = null;

            const margin = 30;
            const centerX = width / 2;

            // Logo
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

            // ✅ Sekarang logoImage sudah terisi (jika berhasil load)
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'INHEREN', logoImage);
            await drawTableKreditInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height, logoImage);

            // Risiko Kredit KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());

            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'KPMR', logoImage);
            await drawTableKreditKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height, logoImage);

            addPageNumbers(pdfDoc, fontRegular);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeader(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            const { rgb } = PDFLib;

            // 1. BACKGROUND PUTIH UNTUK HEADER
            page.drawRectangle({
                x: 0,
                y: height - 80,
                width: width,
                height: 80,
                color: rgb(1, 1, 1),
                borderColor: rgb(0, 0, 0),
                borderWidth: 0
            });

            // 2. GARIS PEMBATAS DI BAWAH HEADER
            page.drawLine({
                start: { x: 80, y: height - 90 },
                end: { x: width - 80, y: height - 90 },
                thickness: 1,
                color: rgb(0, 0, 0)
            });

            // 3. LOGO DI KIRI ATAS (jika ada)
            if (logoImage) {
                try {
                    // Ukuran maksimal yang kamu inginkan
                    const MAX_WIDTH = 130;
                    const MAX_HEIGHT = 50;

                    // Ambil ukuran asli logo
                    const originalWidth = logoImage.width;
                    const originalHeight = logoImage.height;

                    // Hitung rasio scaling (agar TIDAK gepeng)
                    const scale = Math.min(
                        MAX_WIDTH / originalWidth,
                        MAX_HEIGHT / originalHeight
                    );

                    const drawWidth = originalWidth * scale;
                    const drawHeight = originalHeight * scale;

                    page.drawImage(logoImage, {
                        x: 20,
                        y: height - drawHeight - 20, // posisi aman dari atas
                        width: drawWidth,
                        height: drawHeight
                    });
                } catch (e) {
                    console.warn('Logo tidak dapat dimuat di header:', e);
                }
            }

            // 4. JUDUL (warna hitam, di tengah)
            const title = jenis === 'INHEREN'
                ? 'LAPORAN PROFIL RISIKO KREDIT INHEREN'
                : 'LAPORAN PROFIL RISIKO KREDIT KPMR';

            const titleSize = 18;
            const textWidth = fontBold.widthOfTextAtSize(title, titleSize);

            page.drawText(title, {
                x: (width / 2) - (textWidth / 2),
                y: height - 35,
                size: titleSize,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            // 5. INFO BPR (warna hitam)
            const infoBPR = data.bpr.namabpr || 'N/A';
            const alamatBPR = data.bpr.alamat || 'N/A';
            const infoPeriode = `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`;

            const bprWidth = fontRegular.widthOfTextAtSize(infoBPR, 12);
            const periodeWidth = fontBold.widthOfTextAtSize(infoPeriode, 12);

            page.drawText(infoBPR, {
                x: (width - bprWidth) / 2,
                y: height - 52,
                size: 12,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            page.drawText(infoPeriode, {
                x: (width - periodeWidth) / 2,
                y: height - 68,
                size: 12,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
        }

        async function drawTableKreditInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80; // Batas bawah yang lebih ketat

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Rasio', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [35, 110, 130, 150, 65, 70, 182];

            // PENTING: createNewPage harus mengembalikan object lengkap
            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const newDims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'INHEREN', logoImage);
                let newY = newDims.height - 120;

                // Draw header table
                newPage.drawRectangle({
                    x: margin, y: newY - 30, width: tableWidth, height: 30,
                    color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    newPage.drawText(header, {
                        x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });
                newY -= 30;

                currentPage = newPage;
                return { page: newPage, yPosition: newY };
            };

            // Draw header tabel pertama kali
            currentPage.drawRectangle({
                x: margin, y: yPosition - 30, width: tableWidth, height: 30,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });
            yPosition -= 30;

            // Data Kategori Komposisi
            if (dataInheren.nilai && dataInheren.nilai.komposisi && dataInheren.nilai.komposisi.kategori) {
                const result = drawKategoriKredit(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                    dataInheren.nilai.komposisi,
                    yPosition, margin, columnWidths,
                    createNewPage, MIN_Y
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Data Kategori Kualitas - LANGSUNG LANJUT TANPA GAP
            if (dataInheren.nilai && dataInheren.nilai.kualitas && dataInheren.nilai.kualitas.kategori) {
                const result = drawKategoriKredit(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kualitas Aset',
                    dataInheren.nilai.kualitas,
                    yPosition, margin, columnWidths,
                    createNewPage, MIN_Y
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Single Factors - LANGSUNG LANJUT TANPA GAP
            if (dataInheren.nilai && dataInheren.nilai.strategi) {
                const result = drawSingleRowKredit(currentPage, fontRegular, '3', 'Strategi Penyediaan Dana',
                    dataInheren.nilai.strategi, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }

            if (dataInheren.nilai && dataInheren.nilai.eksternal) {
                const result = drawSingleRowKredit(currentPage, fontRegular, '4', 'Faktor Eksternal',
                    dataInheren.nilai.eksternal, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }

            if (dataInheren.nilai && dataInheren.nilai.lainnya) {
                const result = drawSingleRowKredit(currentPage, fontRegular, '5', 'Faktor Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }

            if (dataInheren.nilai13) {
                const result = drawSingleRowKredit(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai13, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }

            if (dataInheren.nilai14) {
                const result = drawSingleRowKredit(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai14, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }
        }

        // ============================================
        // FUNGSI DRAW KATEGORI KREDIT (ROW-BY-ROW SPLIT)
        // ============================================

        function drawKategoriKredit(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc, MIN_Y) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = 11;

            if (!kategoriData || !kategoriData.children) {
                return { yPosition, lastPage: currentPage };
            }

            const children = kategoriData.children.filter(child => child != null);

            if (children.length === 0) {
                return { yPosition, lastPage: currentPage };
            }

            // Hitung tinggi setiap child row
            const childHeights = children.map(child => {
                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length || 1, keteranganLines.length || 1, paramLines.length || 1);
                const contentHeight = maxLines * lineHeight + 10;
                return Math.max(35, contentHeight);
            });

            // Track pages untuk kategori ini - ROW BY ROW
            let categoryPages = [];
            let currentPageObj = {
                page: currentPage,
                rows: [],
                startY: yPosition,
                isFirstPageOfCategory: true
            };
            let currentYPosition = yPosition;
            let lastPage = currentPage;

            // Distribusikan child rows ke pages - SATU PER SATU
            children.forEach((child, index) => {
                const childHeight = childHeights[index];

                // Cek apakah ROW INI masih muat di page saat ini
                if (currentYPosition - childHeight < MIN_Y) {
                    // Simpan page saat ini HANYA jika ada rows
                    if (currentPageObj.rows.length > 0) {
                        categoryPages.push(currentPageObj);
                    }

                    // Buat page baru
                    const newPageData = createNewPageFunc();
                    currentYPosition = newPageData.yPosition;
                    lastPage = newPageData.page;

                    // Page baru untuk kategori yang sama, tapi bukan first page
                    currentPageObj = {
                        page: lastPage,
                        rows: [],
                        startY: currentYPosition,
                        isFirstPageOfCategory: false
                    };
                }

                // Tambahkan row INI ke page saat ini
                currentPageObj.rows.push({
                    child: child,
                    height: childHeight,
                    yStart: currentYPosition,
                    yEnd: currentYPosition - childHeight
                });

                currentYPosition -= childHeight;
            });

            // Simpan page terakhir
            if (currentPageObj.rows.length > 0) {
                categoryPages.push(currentPageObj);
            }

            // Render semua pages dengan kategori label yang benar
            categoryPages.forEach((pageObj, pageIndex) => {
                const page = pageObj.page;
                const rows = pageObj.rows;

                if (!page || rows.length === 0) return;

                const firstRowY = rows[0].yStart;
                const lastRowY = rows[rows.length - 1].yEnd;
                const totalHeight = firstRowY - lastRowY;

                if (isNaN(totalHeight) || totalHeight <= 0) return;

                // Gambar background untuk kolom No dan Pilar Penilaian
                page.drawRectangle({
                    x: margin,
                    y: lastRowY,
                    width: columnWidths[0],
                    height: totalHeight,
                    color: rgb(0.95, 0.95, 0.8),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0],
                    y: lastRowY,
                    width: columnWidths[1],
                    height: totalHeight,
                    color: rgb(0.95, 0.95, 0.8),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                // Tampilkan No dan Title HANYA di page pertama dari kategori ini
                if (pageObj.isFirstPageOfCategory) {
                    const centerY = firstRowY - (totalHeight / 2);

                    // No
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: centerY,
                        size: 9,
                        font: fontBold
                    });

                    // Title dengan word wrap
                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 9);
                    const titleHeight = titleLines.length * 11;
                    let titleY = centerY + (titleHeight / 2) - 5;

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleY - (lineIdx * 11),
                            size: 9,
                            font: fontBold
                        });
                    });
                }

                // Gambar child rows
                rows.forEach(rowInfo => {
                    if (rowInfo && rowInfo.yStart && !isNaN(rowInfo.yStart)) {
                        drawChildRowKredit(
                            page,
                            fontRegular,
                            rowInfo.child,
                            rowInfo.yStart,
                            margin,
                            columnWidths,
                            rowInfo.height
                        );
                    }
                });
            });

            return { yPosition: currentYPosition, lastPage: lastPage };
        }

        // ============================================
        // FUNGSI DRAW CHILD ROW
        // ============================================

        function drawChildRowKredit(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = 11;

            if (!page || !yPosition || isNaN(yPosition) || !rowHeight || isNaN(rowHeight)) {
                console.error('Invalid parameters in drawChildRowKredit:', { yPosition, rowHeight });
                return;
            }

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            let paramY = yPosition - 12;
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: paramY - (idx * lineHeight), size: fontSize, font
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
            let penjelasanY = yPosition - 12;
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: penjelasanY - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Rasio
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 9);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - (rowHeight / 2) - 3, size: 9, font
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
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 11) / 2),
                y: yPosition - (rowHeight / 2) + 5, size: 11, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 7, size: 7, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[6], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, font, fontSize);
            let keteranganY = yPosition - 12;
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: keteranganY - (index * lineHeight), size: fontSize, font
                });
            });
        }

        // ============================================
        // FUNGSI DRAW SINGLE ROW (dengan auto page break)
        // ============================================

        function drawSingleRowKredit(page, font, no, title, data, yPosition, margin, columnWidths, MIN_Y, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = 11;
            const minRowHeight = 40;

            if (!page || !yPosition || isNaN(yPosition)) {
                console.error('Invalid parameters in drawSingleRowKredit');
                return { yPosition: yPosition || 0 };
            }

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const lines = wrapTextToLines(keteranganText, columnWidths[6] - 10, font, fontSize);

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 9);

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 9);

            const maxLines = Math.max(lines.length, penjelasanLines.length, titleLines.length);
            const contentHeight = maxLines * lineHeight + 16;
            const rowHeight = Math.max(minRowHeight, contentHeight);

            // Cek apakah row ini muat di page saat ini
            let newPage = null;
            if (yPosition - rowHeight < MIN_Y) {
                const newPageData = createNewPageFunc();
                yPosition = newPageData.yPosition;
                page = newPageData.page;
                newPage = page;
            }

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 12;

            // No
            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            // Title
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 11), size: 9, font
                });
            });
            xPos += columnWidths[1];

            // Parameter (-)
            page.drawText('-', { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[2];

            // Hasil Penilaian
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * lineHeight), size: 9, font
                });
            });
            xPos += columnWidths[3];

            // Rasio
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 9);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - (rowHeight / 2) - 3, size: 9, font
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
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 11) / 2),
                y: yPosition - (rowHeight / 2) + 5, size: 11, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 7, size: 7, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            // Keterangan
            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, newPage: newPage };
        }

        async function drawTableKreditKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80; // Batas bawah yang lebih ketat

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [35, 130, 210, 150, 85, 132];

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const newDims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'KPMR', logoImage);
                let newY = newDims.height - 120;

                newPage.drawRectangle({
                    x: margin, y: newY - 30, width: tableWidth, height: 30,
                    color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    newPage.drawText(header, {
                        x: xPos, y: newY - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });
                newY -= 30;

                currentPage = newPage;
                return { page: newPage, yPosition: newY };
            };

            // Draw header tabel pertama kali
            currentPage.drawRectangle({
                x: margin, y: yPosition - 30, width: tableWidth, height: 30,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPos, y: yPosition - 18, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPos += columnWidths[i];
            });
            yPosition -= 30;

            // Draw categories
            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKPMR.nilai && dataKPMR.nilai[cat.key] && dataKPMR.nilai[cat.key].kategori) {
                    const result = drawKategoriKreditKPMR(
                        pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKPMR.nilai[cat.key],
                        yPosition, margin, columnWidths,
                        createNewPage, MIN_Y
                    );
                    yPosition = result.yPosition;
                    currentPage = result.lastPage;
                }
            }

            // Penilaian Risiko KPMR
            if (dataKPMR.nilai33) {
                const result = drawSingleRowKreditKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai33, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }

            if (dataKPMR.nilai34) {
                const result = drawSingleRowKreditKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai34, yPosition, margin, columnWidths, MIN_Y, createNewPage);
                yPosition = result.yPosition;
                if (result.newPage) currentPage = result.newPage;
            }

            return yPosition;
        }

        // ============================================
        // FUNGSI DRAW KATEGORI KPMR (ROW-BY-ROW SPLIT)
        // ============================================

        function drawKategoriKreditKPMR(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc, MIN_Y) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = 11;

            if (!kategoriData || !kategoriData.children) {
                return { yPosition, lastPage: currentPage };
            }

            const children = kategoriData.children.filter(child => child != null);

            if (children.length === 0) {
                return { yPosition, lastPage: currentPage };
            }

            // Hitung tinggi setiap child row
            const childHeights = children.map(child => {
                if (!child) return 35;

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterKPMRName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length || 1, keteranganLines.length || 1, paramLines.length || 1);
                const contentHeight = maxLines * lineHeight + 10;
                return Math.max(35, contentHeight);
            });

            // Track pages untuk kategori ini - ROW BY ROW
            let categoryPages = [];
            let currentPageObj = {
                page: currentPage,
                rows: [],
                startY: yPosition,
                isFirstPageOfCategory: true
            };
            let currentYPosition = yPosition;
            let lastPage = currentPage;

            // Distribusikan child rows ke pages - SATU PER SATU
            children.forEach((child, index) => {
                const childHeight = childHeights[index];

                // Cek apakah ROW INI masih muat di page saat ini
                if (currentYPosition - childHeight < MIN_Y) {
                    // Simpan page saat ini HANYA jika ada rows
                    if (currentPageObj.rows.length > 0) {
                        categoryPages.push(currentPageObj);
                    }

                    // Buat page baru
                    const newPageData = createNewPageFunc();
                    currentYPosition = newPageData.yPosition;
                    lastPage = newPageData.page;

                    // Page baru untuk kategori yang sama, tapi bukan first page
                    currentPageObj = {
                        page: lastPage,
                        rows: [],
                        startY: currentYPosition,
                        isFirstPageOfCategory: false
                    };
                }

                // Tambahkan row INI ke page saat ini
                currentPageObj.rows.push({
                    child: child,
                    height: childHeight,
                    yStart: currentYPosition,
                    yEnd: currentYPosition - childHeight
                });

                currentYPosition -= childHeight;
            });

            // Simpan page terakhir
            if (currentPageObj.rows.length > 0) {
                categoryPages.push(currentPageObj);
            }

            // Render semua pages dengan kategori label yang benar
            categoryPages.forEach((pageObj, pageIndex) => {
                const page = pageObj.page;
                const rows = pageObj.rows;

                if (!page || rows.length === 0) return;

                const firstRowY = rows[0].yStart;
                const lastRowY = rows[rows.length - 1].yEnd;
                const totalHeight = firstRowY - lastRowY;

                if (isNaN(totalHeight) || totalHeight <= 0) return;

                // Gambar background untuk kolom No dan Pilar Penilaian
                page.drawRectangle({
                    x: margin,
                    y: lastRowY,
                    width: columnWidths[0],
                    height: totalHeight,
                    color: rgb(0.95, 0.95, 0.8),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                page.drawRectangle({
                    x: margin + columnWidths[0],
                    y: lastRowY,
                    width: columnWidths[1],
                    height: totalHeight,
                    color: rgb(0.95, 0.95, 0.8),
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                // Tampilkan No dan Title HANYA di page pertama dari kategori ini
                if (pageObj.isFirstPageOfCategory) {
                    const centerY = firstRowY - (totalHeight / 2);

                    // No
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: centerY,
                        size: 9,
                        font: fontBold
                    });

                    // Title dengan word wrap
                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 9);
                    const titleHeight = titleLines.length * 11;
                    let titleY = centerY + (titleHeight / 2) - 5;

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleY - (lineIdx * 11),
                            size: 9,
                            font: fontBold
                        });
                    });
                }

                // Gambar child rows
                rows.forEach(rowInfo => {
                    if (rowInfo && rowInfo.yStart && !isNaN(rowInfo.yStart)) {
                        drawChildRowKreditKPMR(
                            page,
                            fontRegular,
                            rowInfo.child,
                            rowInfo.yStart,
                            margin,
                            columnWidths,
                            rowInfo.height
                        );
                    }
                });
            });

            return { yPosition: currentYPosition, lastPage: lastPage };
        }

        // ============================================
        // FUNGSI DRAW CHILD ROW KPMR
        // ============================================

        function drawChildRowKreditKPMR(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = 11;

            if (!page || !yPosition || isNaN(yPosition) || !rowHeight || isNaN(rowHeight)) {
                console.error('Invalid parameters in drawChildRowKreditKPMR:', { yPosition, rowHeight });
                return;
            }

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos,
                y: yPosition - rowHeight,
                width: columnWidths[2],
                height: rowHeight,
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            const paramName = sanitizeText(getParameterKPMRName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            let paramY = yPosition - 12;
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5,
                    y: paramY - (idx * lineHeight),
                    size: fontSize,
                    font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos,
                y: yPosition - rowHeight,
                width: columnWidths[3],
                height: rowHeight,
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            let penjelasanY = yPosition - 12;
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5,
                    y: penjelasanY - (idx * lineHeight),
                    size: fontSize,
                    font
                });
            });
            xPos += columnWidths[3];

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos,
                y: yPosition - rowHeight,
                width: columnWidths[4],
                height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit),
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            page.drawText(nilaiText, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 11) / 2),
                y: yPosition - (rowHeight / 2) + 5,
                size: 11,
                font,
                color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 7,
                size: 7,
                font,
                color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            page.drawRectangle({
                x: xPos,
                y: yPosition - rowHeight,
                width: columnWidths[5],
                height: rowHeight,
                borderColor: rgb(0, 0, 0),
                borderWidth: 1
            });

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            let keteranganY = yPosition - 12;
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5,
                    y: keteranganY - (index * lineHeight),
                    size: fontSize,
                    font
                });
            });
        }

        // ============================================
        // FUNGSI DRAW SINGLE ROW KPMR (dengan auto page break)
        // ============================================

        function drawSingleRowKreditKPMR(page, font, no, title, data, yPosition, margin, columnWidths, MIN_Y, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = 11;
            const minRowHeight = 40;

            if (!page || !yPosition || isNaN(yPosition)) {
                console.error('Invalid parameters in drawSingleRowKreditKPMR');
                return { yPosition: yPosition || 0 };
            }

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const lines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 9);

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 9);

            const maxLines = Math.max(lines.length, penjelasanLines.length, titleLines.length);
            const contentHeight = maxLines * lineHeight + 16;
            const rowHeight = Math.max(minRowHeight, contentHeight);

            // Cek apakah row ini muat di page saat ini
            let newPage = null;
            if (yPosition - rowHeight < MIN_Y) {
                const newPageData = createNewPageFunc();
                yPosition = newPageData.yPosition;
                page = newPageData.page;
                newPage = page;
            }

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 12;

            // No
            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            // Title
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 11), size: 9, font
                });
            });
            xPos += columnWidths[1];

            // Parameter (-)
            page.drawText('-', { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[2];

            // Hasil Penilaian
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * lineHeight), size: 9, font
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
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 11) / 2),
                y: yPosition - (rowHeight / 2) + 5, size: 11, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 7, size: 7, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, newPage: newPage };
        }

        // 3. RISIKO OPERASIONAL
        async function generateRisikoOperasionalPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // Operasional Inheren
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            let logoImage = null;

            const margin = 30;
            const centerX = width / 2;

            // Logo
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

            drawHeaderOperasional(currentPage, fontBold, fontRegular, data, width, height, 'INHEREN', logoImage);
            await drawTableOperasionalInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height, logoImage);

            // Operasional KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());

            drawHeaderOperasional(currentPage, fontBold, fontRegular, data, width, height, 'KPMR', logoImage);
            await drawTableOperasionalKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height, logoImage);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeaderOperasional(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            const { rgb } = PDFLib;

            page.drawRectangle({
                x: 0,
                y: height - 80,
                width: width,
                height: 80,
                color: rgb(1, 1, 1),
                borderColor: rgb(0, 0, 0),
                borderWidth: 0
            });

            // 2. GARIS PEMBATAS DI BAWAH HEADER
            page.drawLine({
                start: { x: 40, y: height - 90 },
                end: { x: width - 80, y: height - 90 },
                thickness: 1,
                color: rgb(0, 0, 0)
            });


            // 3. LOGO DI KIRI ATAS (jika ada)
            if (logoImage) {
                try {
                    // Ukuran maksimal yang kamu inginkan
                    const MAX_WIDTH = 130;
                    const MAX_HEIGHT = 50;

                    // Ambil ukuran asli logo
                    const originalWidth = logoImage.width;
                    const originalHeight = logoImage.height;

                    // Hitung rasio scaling (agar TIDAK gepeng)
                    const scale = Math.min(
                        MAX_WIDTH / originalWidth,
                        MAX_HEIGHT / originalHeight
                    );

                    const drawWidth = originalWidth * scale;
                    const drawHeight = originalHeight * scale;

                    page.drawImage(logoImage, {
                        x: 20,
                        y: height - drawHeight - 20, // posisi aman dari atas
                        width: drawWidth,
                        height: drawHeight
                    });
                } catch (e) {
                    console.warn('Logo tidak dapat dimuat di header:', e);
                }
            }

            const title = jenis === 'INHEREN'
                ? 'LAPORAN PROFIL RISIKO OPERASIONAL INHEREN'
                : 'LAPORAN PROFIL RISIKO OPERASIONAL KPMR';

            const titleSize = 18;
            const textWidth = fontBold.widthOfTextAtSize(title, titleSize);

            page.drawText(title, {
                x: (width / 2) - (textWidth / 2),
                y: height - 35,
                size: titleSize,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            const infoBPR = data.bpr.namabpr || 'N/A';
            const infoPeriode = `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`;

            const bprWidth = fontRegular.widthOfTextAtSize(infoBPR, 12);
            const periodeWidth = fontBold.widthOfTextAtSize(infoPeriode, 12);

            page.drawText(infoBPR, {
                x: (width - bprWidth) / 2,
                y: height - 52,
                size: 12,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            page.drawText(infoPeriode, {
                x: (width - periodeWidth) / 2,
                y: height - 68,
                size: 12,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
        }

        async function drawTableOperasionalInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 180, 160, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeaderOperasional(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'INHEREN', logoImage);
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
                        drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
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
                        drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
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

        async function drawTableOperasionalKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 180, 160, 80, 172];

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
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataKPMR.nilai.pengawasan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
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

            // Kategori Kebijakan
            if (dataKPMR.nilai.kebijakan.kategori) {
                const result = drawKategoriOperasional(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataKPMR.nilai.kebijakan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
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
                        drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
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
                        drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
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
                    dataKPMR.nilai33, yPosition, margin, columnWidths, logoImage);
                yPosition = result.yPosition;
            }

            if (dataKPMR.nilai34) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowOperasional(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai34, yPosition, margin, columnWidths, logoImage);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        // Helper function untuk membersihkan <br> dari teks
        function cleanBrTags(text) {
            if (!text) return text;
            return text.replace(/<br\s*\/?>/gi, '\n').trim();
        }

        function drawKategoriOperasional(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = fontSize + 3;
            const MIN_Y = 80;

            const children = kategoriData.children || [];
            const childHeights = [];

            children.forEach(child => {
                if (!child) return;

                // Bersihkan <br> dari semua teks
                const penjelasanText = cleanBrTags(sanitizeText(child.penjelasanpenilaian)) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = cleanBrTags(sanitizeText(child.keterangan)) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = cleanBrTags(sanitizeText(getParameterOperasionalName(child.faktor1id)));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const verticalPadding = 20; // top + bottom padding
                const maxLines = Math.max(
                    penjelasanLines.length || 1,
                    keteranganLines.length || 1,
                    paramLines.length || 1
                );
                const contentHeight = (maxLines * lineHeight) + verticalPadding;
                const childHeight = Math.max(35, contentHeight);

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
                    // Posisi dari atas box kategori
                    const topY = startY - 15;

                    // Gambar nomor 
                    const noWidth = fontBold.widthOfTextAtSize(no, 10);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY,
                        size: 10,
                        font: fontBold
                    });

                    // Gambar title dengan word wrap
                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 10);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: topY - (lineIdx * 10),
                            size: 10,
                            font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage, needNewPage: false, childHeights };
        }

        function drawChildRowOperasional(page, font, data, yPosition, margin, columnWidths, rowHeight, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 10;
            const lineHeight = fontSize + 3;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = cleanBrTags(sanitizeText(getParameterOperasionalName(data.faktor1id)));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, font, fontSize);
            paramLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 12 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[2];

            // Hasil Penilaian
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[3], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const penjelasanText = cleanBrTags(sanitizeText(data.penjelasanpenilaian)) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, fontSize);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 12 - (idx * lineHeight), size: fontSize, font
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
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 14) / 2) + 1,
                y: yPosition - 18, size: 14, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 10) / 2) + 3,
                y: yPosition - 30, size: 9, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[5], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const keteranganText = cleanBrTags(sanitizeText(data.keterangan)) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, font, fontSize);
            keteranganLines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 12 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawSingleRowOperasional(page, font, no, title, data, yPosition, margin, columnWidths, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 10;
            const lineHeight = fontSize + 2;
            const minRowHeight = 72;
            const MIN_Y = 75;

            const keteranganText = cleanBrTags(sanitizeText(data.keterangan)) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight * 1.2;
            const rowHeight = Math.max(minRowHeight, contentHeight) * 1.2;
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 12;

            // No
            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            // Title
            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 9);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 10), size: 9, font
                });
            });
            xPos += columnWidths[1];

            // Parameter 
            page.drawText('-', { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[2];

            // Hasil Penilaian
            const penjelasanText = cleanBrTags(sanitizeText(data.penjelasanpenilaian)) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 12, font, 10);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 12 - (idx * 10), size: 10, font
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
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiText, 14) / 2),
                y: yPosition - 18, size: 14, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 10) / 2),
                y: yPosition - 30, size: 9, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[4];

            // Keterangan
            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 12 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight, needNewPage };
        }

        // 4. RISIKO KEPATUHAN
        async function generateRisikoKepatuhanPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // Kepatuhan Inheren
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            let logoImage = null;

            const margin = 30;
            const centerX = width / 2;

            // Logo
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

            drawHeaderKepatuhan(currentPage, fontBold, fontRegular, data, width, height, 'INHEREN', logoImage);
            await drawTableKepatuhanInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height, logoImage);

            // Kepatuhan KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());

            drawHeaderKepatuhan(currentPage, fontBold, fontRegular, data, width, height, 'KPMR', logoImage);
            await drawTableKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height, logoImage);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeaderKepatuhan(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            const { rgb } = PDFLib;

            page.drawRectangle({
                x: 0,
                y: height - 80,
                width: width,
                height: 80,
                color: rgb(1, 1, 1),  // ✅ UBAH: Putih (sebelumnya biru)
                borderColor: rgb(0, 0, 0),
                borderWidth: 0
            });

            // 2. GARIS PEMBATAS DI BAWAH HEADER
            page.drawLine({
                start: { x: 40, y: height - 90 },
                end: { x: width - 80, y: height - 90 },
                thickness: 1,
                color: rgb(0, 0, 0)
            });


            // 3. LOGO DI KIRI ATAS (jika ada)
            if (logoImage) {
                try {
                    // Ukuran maksimal yang kamu inginkan
                    const MAX_WIDTH = 130;
                    const MAX_HEIGHT = 50;

                    // Ambil ukuran asli logo
                    const originalWidth = logoImage.width;
                    const originalHeight = logoImage.height;

                    // Hitung rasio scaling (agar TIDAK gepeng)
                    const scale = Math.min(
                        MAX_WIDTH / originalWidth,
                        MAX_HEIGHT / originalHeight
                    );

                    const drawWidth = originalWidth * scale;
                    const drawHeight = originalHeight * scale;

                    page.drawImage(logoImage, {
                        x: 20,
                        y: height - drawHeight - 20, // posisi aman dari atas
                        width: drawWidth,
                        height: drawHeight
                    });
                } catch (e) {
                    console.warn('Logo tidak dapat dimuat di header:', e);
                }
            }

            const title = jenis === 'INHEREN'
                ? 'LAPORAN PROFIL RISIKO KEPATUHAN INHEREN'
                : 'LAPORAN PROFIL RISIKO KEPATUHAN KPMR';

            const titleSize = 18;
            const textWidth = fontBold.widthOfTextAtSize(title, titleSize);

            page.drawText(title, {
                x: (width / 2) - (textWidth / 2),
                y: height - 35,
                size: titleSize,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            const infoBPR = data.bpr.namabpr || 'N/A';
            const infoPeriode = `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`;

            const bprWidth = fontRegular.widthOfTextAtSize(infoBPR, 12);
            const periodeWidth = fontBold.widthOfTextAtSize(infoPeriode, 12);

            page.drawText(infoBPR, {
                x: (width - bprWidth) / 2,
                y: height - 52,
                size: 12,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            page.drawText(infoPeriode, {
                x: (width - periodeWidth) / 2,
                y: height - 68,
                size: 12,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
        }

        async function drawTableKepatuhanInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 80;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [30, 120, 180, 160, 80, 172];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeaderKepatuhan(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'INHEREN', logoImage);
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
            if (dataInheren.nilai?.pelanggaran?.kategori) {
                const result = drawKategoriKepatuhan(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                    dataInheren.nilai.pelanggaran,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
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
            if (dataInheren.nilai?.hukum?.kategori) {
                const result = drawKategoriKepatuhan(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Faktor kelemahan aspek hukum',
                    dataInheren.nilai.hukum,
                    yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
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
            if (dataInheren.nilai?.lainnya) {
                // if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '3', 'Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            // Penilaian Risiko
            if (dataInheren.nilai81) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai81, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai82) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai82, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        function drawKategoriKepatuhan(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc, logoImage) {
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

                drawChildRowKepatuhan(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight, logoImage);
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

        function drawChildRowKepatuhan(page, font, data, yPosition, margin, columnWidths, rowHeight, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterKepatuhanName(data.faktor1id));
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
                y: yPosition - 12, size: 11, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[4] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 5.5) / 2),
                y: yPosition - 21, size: 7, font, color: rgb(0, 0, 0)
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

        async function drawTableKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKepatuhan, periode, bpr, width, height, logoImage) {
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
                drawHeader(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'KEPATUHAN_KPMR', logoImage);
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
                        drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR', logoImage);
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
                        drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR', logoImage);
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
                        drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR', logoImage);
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
                        drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KEPATUHAN_KPMR', logoImage);
                        return { page: newPage, yPosition: dims.height - 145 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Penilaian Risiko KPMR
            if (dataKepatuhan.nilai102) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKepatuhan.nilai102, yPosition, margin, columnWidths, logoImage);
                yPosition = result.yPosition;
            }

            if (dataKepatuhan.nilai103) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowKepatuhan(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKepatuhan.nilai103, yPosition, margin, columnWidths, logoImage);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        function drawKategoriKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;
            const MIN_Y = 80;

            if (!kategoriData || !kategoriData.children) {
                console.warn('kategoriData atau children tidak valid');
                return { yPosition, lastPage: currentPage, needNewPage: false, childHeights: [] };
            }

            const children = kategoriData.children.filter(child => child != null);
            const childHeights = [];

            children.forEach(child => {
                if (!child) {
                    childHeights.push(25);
                    return;
                }

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterKepatuhanKPMRName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(
                    penjelasanLines.length || 1,
                    keteranganLines.length || 1,
                    paramLines.length || 1
                );
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

                drawChildrowKepatuhanKPMR(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight, logoImage);
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

        function drawChildrowKepatuhanKPMR(page, font, data, yPosition, margin, columnWidths, rowHeight, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const paramName = sanitizeText(getParameterKepatuhanKPMRName(data.faktor1id));
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

        function drawSingleRowKepatuhan(page, font, no, title, data, yPosition, margin, columnWidths, logoImage) {
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

        // 5. RISIKO LIKUIDITAS
        async function generateRisikoLikuiditasPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // Likuiditas Inheren
            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            let logoImage = null;

            const margin = 30;
            const centerX = width / 2;

            // Logo
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

            drawHeaderLikuiditas(currentPage, fontBold, fontRegular, data, width, height, 'INHEREN', logoImage);
            await drawTableLikuiditasInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height, logoImage);

            // Likuiditas KPMR
            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());

            drawHeaderLikuiditas(currentPage, fontBold, fontRegular, data, width, height, 'KPMR', logoImage);
            await drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height, logoImage);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeaderLikuiditas(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            const { rgb } = PDFLib;

            page.drawRectangle({
                x: 0,
                y: height - 80,
                width: width,
                height: 80,
                color: rgb(1, 1, 1),
                borderColor: rgb(0, 0, 0),
                borderWidth: 0
            });

            // 2. GARIS PEMBATAS DI BAWAH HEADER
            page.drawLine({
                start: { x: 80, y: height - 90 },
                end: { x: width - 80, y: height - 90 },
                thickness: 1,
                color: rgb(0, 0, 0)
            });


            // 3. LOGO DI KIRI ATAS (jika ada)
            if (logoImage) {
                try {
                    // Ukuran maksimal yang kamu inginkan
                    const MAX_WIDTH = 130;
                    const MAX_HEIGHT = 50;

                    // Ambil ukuran asli logo
                    const originalWidth = logoImage.width;
                    const originalHeight = logoImage.height;

                    // Hitung rasio scaling (agar TIDAK gepeng)
                    const scale = Math.min(
                        MAX_WIDTH / originalWidth,
                        MAX_HEIGHT / originalHeight
                    );

                    const drawWidth = originalWidth * scale;
                    const drawHeight = originalHeight * scale;

                    page.drawImage(logoImage, {
                        x: 20,
                        y: height - drawHeight - 20, // posisi aman dari atas
                        width: drawWidth,
                        height: drawHeight
                    });
                } catch (e) {
                    console.warn('Logo tidak dapat dimuat di header:', e);
                }
            }

            const title = jenis === 'INHEREN'
                ? 'LAPORAN PROFIL RISIKO LIKUIDITAS INHEREN'
                : 'LAPORAN PROFIL RISIKO LIKUIDITAS KPMR';

            const titleSize = 18;
            const textWidth = fontBold.widthOfTextAtSize(title, titleSize);

            page.drawText(title, {
                x: (width / 2) - (textWidth / 2),
                y: height - 35,
                size: titleSize,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            const infoBPR = data.bpr.namabpr || 'N/A';
            const infoPeriode = `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`;

            const bprWidth = fontRegular.widthOfTextAtSize(infoBPR, 12);
            const periodeWidth = fontBold.widthOfTextAtSize(infoPeriode, 12);

            page.drawText(infoBPR, {
                x: (width - bprWidth) / 2,
                y: height - 52,
                size: 12,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            page.drawText(infoPeriode, {
                x: (width - periodeWidth) / 2,
                y: height - 68,
                size: 12,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
        }

        async function drawTableLikuiditasInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;

            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 100;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Rasio', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [35, 110, 130, 150, 65, 70, 182];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeaderLikuiditas(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'INHEREN', logoImage);
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin, y: yPosition - 30, width: tableWidth, height: 30,
                    color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos, y: yPosition - 20, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });
                yPosition -= 30;
                return yPosition;
            };

            // Draw header
            currentPage.drawRectangle({
                x: margin, y: yPosition - 30, width: tableWidth, height: 30,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPosition = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPosition, y: yPosition - 20, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPosition += columnWidths[i];
            });
            yPosition -= 30;

            // Data Kategori Komposisi
            if (dataInheren.nilai.konsentrasi.kategori) {
                const result = drawKategoriLikuiditas(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Komposisi dan konsentrasi aset dan kewajiban',
                    dataInheren.nilai.konsentrasi, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeaderLikuiditas(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 30, width: tableWidth, height: 30,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 20, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });
                        return { page: newPage, yPosition: newY - 30 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            // Data Kategori Kerentanan
            if (dataInheren.nilai.kerentanan.kategori) {
                const result = drawKategoriLikuiditas(
                    pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan',
                    dataInheren.nilai.kerentanan, yPosition, margin, columnWidths,
                    () => {
                        const newPage = pdfDoc.addPage([842, 595]);
                        const dims = newPage.getSize();
                        drawHeaderLikuiditas(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
                        const newY = dims.height - 120;

                        newPage.drawRectangle({
                            x: margin, y: newY - 30, width: tableWidth, height: 30,
                            color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                        });

                        let xPos = margin + 5;
                        headers.forEach((header, i) => {
                            newPage.drawText(header, {
                                x: xPos, y: newY - 20, size: 9, font: fontBold, color: rgb(0, 0, 0)
                            });
                            xPos += columnWidths[i];
                        });
                        return { page: newPage, yPosition: newY - 30 };
                    }
                );
                yPosition = result.yPosition;
                currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.lainnya) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditas(currentPage, fontRegular, '3', 'Faktor Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai115) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditas(currentPage, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai115, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataInheren.nilai116) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditas(currentPage, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai116, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        function drawKategoriLikuiditas(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = fontSize + 3;
            const MIN_Y = 100;

            if (!kategoriData || !kategoriData.children) {
                return { yPosition, lastPage: currentPage };
            }

            const children = kategoriData.children.filter(child => child != null);
            const childHeights = [];

            children.forEach(child => {
                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[6] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterLikuiditasName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length || 1, keteranganLines.length || 1, paramLines.length || 1);
                const contentHeight = maxLines * lineHeight + 12;
                const childHeight = Math.max(35, contentHeight);

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

                drawChildRowLikuiditas(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
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
                    const topY = startY - 22;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 9);
                    const titleStartY = topY + ((titleLines.length - 1) * 5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 10), size: 9, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage };
        }

        function drawChildRowLikuiditas(page, font, data, yPosition, margin, columnWidths, rowHeight) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = fontSize + 3;

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
                    x: xPos + 5, y: yPosition - 15 - (idx * lineHeight), size: fontSize, font
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
                    x: xPos + 5, y: yPosition - 15 - (idx * lineHeight), size: fontSize, font
                });
            });
            xPos += columnWidths[3];

            // Rasio
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 9);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - (rowHeight / 2) - 3, size: 9, font
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
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 11) / 2),
                y: yPosition - (rowHeight / 2) + 3, size: 11, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 8, size: 7, font, color: rgb(0, 0, 0)
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
                    x: xPos + 5, y: yPosition - 15 - (index * lineHeight), size: fontSize, font
                });
            });
        }

        function drawSingleRowLikuiditas(page, font, no, title, data, yPosition, margin, columnWidths) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = fontSize + 3;
            const minRowHeight = 50;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const lines = wrapTextToLines(keteranganText, columnWidths[6] - 10, font, fontSize);

            const contentHeight = lines.length * lineHeight + 20;
            const rowHeight = Math.max(minRowHeight, contentHeight);

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 15;

            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 9);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 10), size: 9, font
                });
            });
            xPos += columnWidths[1];

            page.drawText('-', { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[2];

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, font, 9);
            penjelasanLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 15 - (idx * lineHeight), size: 9, font
                });
            });
            xPos += columnWidths[3];

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[4], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            const rasioText = '-';
            const rasioWidth = font.widthOfTextAtSize(rasioText, 9);
            page.drawText(rasioText, {
                x: xPos + (columnWidths[4] / 2) - (rasioWidth / 2),
                y: yPosition - (rowHeight / 2) - 3, size: 9, font
            });
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
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiText, 11) / 2),
                y: yPosition - (rowHeight / 2) + 3, size: 11, font, color: rgb(0, 0, 0)
            });

            page.drawText(nilaiPilarLabel, {
                x: xPos + (columnWidths[5] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 8, size: 7, font, color: rgb(0, 0, 0)
            });
            xPos += columnWidths[5];

            lines.forEach((line, index) => {
                page.drawText(line, {
                    x: xPos + 5, y: yPosition - 15 - (index * lineHeight), size: fontSize, font
                });
            });

            return { yPosition: yPosition - rowHeight };
        }

        // FUNGSI KPMR dengan font size 9
        async function drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const MIN_Y = 100;

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];
            const columnWidths = [35, 130, 210, 150, 85, 132];

            const createNewPage = () => {
                currentPage = pdfDoc.addPage([842, 595]);
                const newDims = currentPage.getSize();
                drawHeaderLikuiditas(currentPage, fontBold, fontRegular, { periode, bpr }, newDims.width, newDims.height, 'KPMR', logoImage);
                yPosition = newDims.height - 120;

                currentPage.drawRectangle({
                    x: margin, y: yPosition - 30, width: tableWidth, height: 30,
                    color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
                });

                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    currentPage.drawText(header, {
                        x: xPos, y: yPosition - 20, size: 9, font: fontBold, color: rgb(0, 0, 0)
                    });
                    xPos += columnWidths[i];
                });
                yPosition -= 30;
                return yPosition;
            };

            // Draw header
            currentPage.drawRectangle({
                x: margin, y: yPosition - 30, width: tableWidth, height: 30,
                color: rgb(0.9, 0.9, 0.9), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin + 5;
            headers.forEach((header, i) => {
                currentPage.drawText(header, {
                    x: xPos, y: yPosition - 20, size: 9, font: fontBold, color: rgb(0, 0, 0)
                });
                xPos += columnWidths[i];
            });
            yPosition -= 30;

            // Draw categories
            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKPMR.nilai[cat.key]?.kategori) {
                    const result = drawKategoriLikuiditasKPMR(
                        pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKPMR.nilai[cat.key],
                        yPosition, margin, columnWidths,
                        () => {
                            const newPage = pdfDoc.addPage([842, 595]);
                            const dims = newPage.getSize();
                            drawHeaderLikuiditas(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
                            return { page: newPage, yPosition: dims.height - 150 };
                        }
                    );
                    yPosition = result.yPosition;
                    currentPage = result.lastPage;
                }
            }

            if (dataKPMR.nilai135) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditasKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai135, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            if (dataKPMR.nilai136) {
                if (yPosition < MIN_Y) yPosition = createNewPage();
                const result = drawSingleRowLikuiditasKPMR(currentPage, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai136, yPosition, margin, columnWidths);
                yPosition = result.yPosition;
            }

            return yPosition;
        }

        function drawKategoriLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, columnWidths, createNewPageFunc) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = fontSize + 3;
            const MIN_Y = 100;

            if (!kategoriData || !kategoriData.children) {
                return { yPosition, lastPage: currentPage };
            }

            const children = kategoriData.children.filter(child => child != null);
            const childHeights = [];

            children.forEach(child => {
                if (!child) {
                    childHeights.push(35);
                    return;
                }

                const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
                const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fontSize);

                const keteranganText = sanitizeText(child.keterangan) || '-';
                const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fontSize);

                const paramName = sanitizeText(getParameterLikuiditasKPMRName(child.faktor1id));
                const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fontSize);

                const maxLines = Math.max(penjelasanLines.length || 1, keteranganLines.length || 1, paramLines.length || 1);
                const contentHeight = maxLines * lineHeight + 12;
                const childHeight = Math.max(35, contentHeight);

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

                drawChildRowLikuiditasKPMR(currentPage, fontRegular, child, currentYPosition, margin, columnWidths, childHeight);
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
                    const topY = startY - 22;
                    const noWidth = fontBold.widthOfTextAtSize(no, 9);
                    page.drawText(no, {
                        x: margin + (columnWidths[0] / 2) - (noWidth / 2),
                        y: topY, size: 9, font: fontBold
                    });

                    const titleLines = wrapTextToLines(title, columnWidths[1] - 10, fontBold, 9);
                    const titleStartY = topY + ((titleLines.length - 1) * 5);

                    titleLines.forEach((line, lineIdx) => {
                        page.drawText(line, {
                            x: margin + columnWidths[0] + 5,
                            y: titleStartY - (lineIdx * 10), size: 9, font: fontBold
                        });
                    });
                }
            });

            return { yPosition: currentYPosition, lastPage: currentPage };
        }

        function drawSingleRowLikuiditasKPMR(page, font, no, title, data, yPosition, margin, columnWidths, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 9;
            const lineHeight = fontSize + 3;
            const minRowHeight = 25;
            const MIN_Y = 80;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const maxWidth = columnWidths[5] - 10;
            const lines = wrapTextToLines(keteranganText, maxWidth, font, fontSize);

            const contentHeight = lines.length * lineHeight * 1.2;
            const rowHeight = Math.max(minRowHeight, contentHeight) * 1.2;
            const needNewPage = yPosition - rowHeight < MIN_Y;

            page.drawRectangle({
                x: margin, y: yPosition - rowHeight,
                width: columnWidths.reduce((a, b) => a + b, 0), height: rowHeight,
                color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            let xPos = margin;
            const cellY = yPosition - 12;

            // No
            page.drawText(no, { x: xPos + 5, y: cellY, size: 9, font });
            xPos += columnWidths[0];

            // Title
            const titleLines = wrapTextToLines(title, columnWidths[1] - 10, font, 7);
            titleLines.forEach((line, idx) => {
                page.drawText(line, {
                    x: xPos + 5, y: cellY - (idx * 9), size: 7, font
                });
            });
            xPos += columnWidths[1];

            // Parameter (dash)
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

            // Nilai Pilar (dengan background color)
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

        function drawChildRowLikuiditasKPMR(page, font, data, yPosition, margin, columnWidths, rowHeight, logoImage) {
            const { rgb } = PDFLib;
            const fontSize = 7;
            const lineHeight = fontSize + 2;

            let xPos = margin + columnWidths[0] + columnWidths[1];

            // Parameter
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: columnWidths[2], height: rowHeight,
                borderColor: rgb(0, 0, 0), borderWidth: 1
            });

            // ✅ PERBAIKAN: Gunakan getParameterLikuiditasKPMRName
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

        // 6. LAPORAN PROFIL RISIKO (Summary) - Updated Version
        async function generateLaporanProfilRisikoPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            const width = 595;
            const height = 842;
            const margin = 30;
            const centerX = width / 2;

            let logoImage = null;

            // Load logo jika ada
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

            // Helper function untuk menggambar header
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

            // === HALAMAN PERTAMA - PROFIL RISIKO ===
            let page = pdfDoc.addPage([595, 842]);
            let yPos = drawPageHeader(page, height - margin);

            // Judul
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

            // A. PROFIL RISIKO
            page.drawText('A. PROFIL RISIKO', {
                x: margin,
                y: yPos,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            yPos -= 20;

            // Helper function untuk format rupiah
            function formatRupiah(angka) {
                if (!angka) return 'Rp 0';

                // Hilangkan karakter non-digit
                const number = String(angka).replace(/[^\d]/g, '');

                // Format dengan separator ribuan
                const formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                return `Rp ${formatted}`;
            }

            // Data profil
            const profilData = [
                ['Periode', `Semester ${data.periode.semester} Tahun ${data.periode.tahun}`],
                ['Nama BPR', data.bpr.namabpr],
                ['Alamat', data.bpr.alamat],
                ['Nomor Telepon', data.bpr.nomor],
                ['Modal Inti', formatRupiah(data.periode.modalinti)],
                ['Total Aset', formatRupiah(data.periode.totalaset)],
                ['Jumlah Kantor Cabang', data.periode.kantorcabang],
                ['Kegiatan sebagai penerbit kartu ATM atau kartu debit', data.periode.atmdebit]
            ];

            profilData.forEach(([label, value]) => {
                page.drawText(label, {
                    x: margin + 10,
                    y: yPos,
                    size: 8.5,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });

                page.drawText(':', {
                    x: margin + 220,
                    y: yPos,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });

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

            // TABEL PENILAIAN
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

            if (data.risiko.reputasi && data.risiko.reputasi.current) {
                risikoTypes.push({ name: 'Risiko Reputasi', key: 'reputasi' });
            }
            if (data.risiko.stratejik && data.risiko.stratejik.current) {
                risikoTypes.push({ name: 'Risiko Stratejik', key: 'stratejik' });
            }

            risikoTypes.forEach((rType) => {
                const rData = data.risiko[rType.key];

                page.drawRectangle({
                    x: tableStartX,
                    y: yPos - rowHeight,
                    width: tableWidth,
                    height: rowHeight,
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                currentX = tableStartX;

                const risikoText = rType.name;
                const textX = currentX + 5;
                const textY = yPos - (rowHeight / 2) - 3;

                page.drawText(risikoText, {
                    x: textX,
                    y: textY,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });

                currentX += colWidths[0];

                const values = [
                    { val: rData.inherenCurrent, color: getBackgroundColorByRating(rData.inherenCurrent) },
                    { val: rData.kpmrCurrent, color: getBackgroundColorByRating(rData.kpmrCurrent) },
                    { val: rData.current, color: getBackgroundColorByRating(rData.current) },
                    { val: rData.inherenPrevious, color: getBackgroundColorByRating(rData.inherenPrevious) },
                    { val: rData.kpmrPrevious, color: getBackgroundColorByRating(rData.kpmrPrevious) },
                    { val: rData.previous, color: getBackgroundColorByRating(rData.previous) }
                ];

                values.forEach((item, idx) => {
                    page.drawRectangle({
                        x: currentX,
                        y: yPos - rowHeight,
                        width: colWidths[idx + 1],
                        height: rowHeight,
                        color: item.color,
                        borderColor: rgb(0, 0, 0),
                        borderWidth: 1
                    });

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

            currentX = tableStartX + colWidths[0] + colWidths[1] + colWidths[2];
            const peringkatCurrentColor = getBackgroundColorByRating(data.risiko.peringkat.current);
            page.drawRectangle({
                x: currentX,
                y: yPos - rowHeight,
                width: colWidths[3],
                height: rowHeight,
                color: peringkatCurrentColor,
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

            currentX += colWidths[3] + colWidths[4] + colWidths[5];
            const peringkatPrevColor = getBackgroundColorByRating(data.risiko.peringkat.previous);
            page.drawRectangle({
                x: currentX,
                y: yPos - rowHeight,
                width: colWidths[6],
                height: rowHeight,
                color: peringkatPrevColor,
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

            // ANALISIS
            if (yPos < 200) {
                page = pdfDoc.addPage([595, 842]);
                yPos = drawPageHeader(page, height - margin);
            }

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

            // Kesimpulan
            if (data.kesimpulan && data.kesimpulan.trim() !== '') {
                yPos -= 15;

                const paragraphs = data.kesimpulan
                    .replace(/\r\n/g, '\n')
                    .replace(/<br\s*\/?>/gi, '\n')
                    .split('\n');

                let totalLines = 0;
                const wrappedParagraphs = [];

                paragraphs.forEach(para => {
                    if (para.trim() === '') {
                        wrappedParagraphs.push(['']);
                        totalLines += 1;
                    } else {
                        const wrapped = wrapTextByCharLimit(para, 119);
                        wrappedParagraphs.push(wrapped);
                        totalLines += wrapped.length;
                    }
                });

                const kesimpulanBoxHeight = (totalLines * 11) + 100;

                if (yPos < kesimpulanBoxHeight + 50) {
                    page = pdfDoc.addPage([595, 842]);
                    yPos = drawPageHeader(page, height - margin);
                }

                page.drawRectangle({
                    x: margin,
                    y: yPos - kesimpulanBoxHeight,
                    width: width - (2 * margin),
                    height: kesimpulanBoxHeight,
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                page.drawText('Kesimpulan', {
                    x: margin + 5,
                    y: yPos - 15,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                yPos -= 30;

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

            // === B. ANALISIS PER JENIS RISIKO ===
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

            for (let i = 0; i < risikoArray.length; i++) {
                const risiko = risikoArray[i];

                page = pdfDoc.addPage([595, 842]);
                yPos = drawPageHeader(page, height - margin);

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

                page.drawText(risiko.title, {
                    x: margin + 10,
                    y: yPos,
                    size: 11,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 20;

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

                const inherenKeterangan = await fetchRiskDetailKeterangan(risiko.key, risiko.inherenFaktorId);
                const kpmrKeterangan = await fetchRiskDetailKeterangan(risiko.key, risiko.kpmrFaktorId);

                const risikoData = data.risiko[risiko.key];
                const tingkatRisiko = risikoData.current || '-';
                const tingkatRisikoText = getRiskText(tingkatRisiko);

                if (yPos < 100) {
                    page = pdfDoc.addPage([595, 842]);
                    yPos = drawPageHeader(page, height - margin);
                }

                let boxStartY = yPos;
                let boxBottomMargin = 50;

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

                page.drawText(`   Peringkat ${tingkatRisiko}, ${tingkatRisikoText}`, {
                    x: margin + 24,
                    y: yPos,
                    size: 10,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });
                yPos -= 18;

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

                if (inherenKeterangan.length > 0) {
                    for (let idx = 0; idx < inherenKeterangan.length; idx++) {
                        const item = inherenKeterangan[idx];
                        const keteranganText = `   ${idx + 1}. ${item.keterangan || ''}`;
                        const keteranganLines = wrapTextByCharLimit(keteranganText, 100);

                        for (let lineIdx = 0; lineIdx < keteranganLines.length; lineIdx++) {
                            const line = keteranganLines[lineIdx];

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

                // KPMR
                if (yPos < 100) {
                    page = pdfDoc.addPage([595, 842]);
                    yPos = drawPageHeader(page, height - margin);
                }

                boxStartY = yPos;

                page.drawRectangle({
                    x: margin + 10,
                    y: boxBottomMargin,
                    width: width - (2 * margin) - 20,
                    height: boxStartY - boxBottomMargin,
                    borderColor: rgb(0, 0, 0),
                    borderWidth: 1
                });

                page.drawText('3. Kualitas Penerapan Manajemen Risiko:', {
                    x: margin + 20,
                    y: yPos - 15,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 35;

                if (kpmrKeterangan.length > 0) {
                    for (let idx = 0; idx < kpmrKeterangan.length; idx++) {
                        const item = kpmrKeterangan[idx];
                        const keteranganText = `   ${idx + 1}. ${item.keterangan || ''}`;
                        const keteranganLines = wrapTextByCharLimit(keteranganText, 100);

                        for (let lineIdx = 0; lineIdx < keteranganLines.length; lineIdx++) {
                            const line = keteranganLines[lineIdx];

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

            // === LEMBAR TANDA TANGAN ===
            page = pdfDoc.addPage([595, 842]);
            yPos = drawPageHeader(page, height - margin);
            yPos -= 100;

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

            const namaBprText = data.bpr.namabpr || 'PT BPR NBP';
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
                size: 11,
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

            const jabatan = 'Direktur Yang Membawahi Fungsi Kepatuhan';
            const jabatanWidth = fontRegular.widthOfTextAtSize(jabatan, 10);
            page.drawText(jabatan, {
                x: centerX - (jabatanWidth / 2),
                y: yPos,
                size: 10,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        // ===== MAIN GENERATION FUNCTION =====

        async function generateAllPDFsToZip() {
            try {
                updateProgress(5, 'Mempersiapkan ZIP...');
                const periodeResponse = await fetch(`${BASE_URL}/Showprofilresiko/exportLembarPernyataanJSON`);
                const periodeResult = await periodeResponse.json();
                const semester = periodeResult.data.periode.semester;
                const tahun = periodeResult.data.periode.tahun;
                const zip = new JSZip();
                const folder = zip.folder(`Laporan Profil Risiko Semester ${semester} Tahun ${tahun}`);

                const files = [
                    {
                        name: '00. Lembar Pernyataan Profil Risiko.pdf',
                        endpoint: 'Showprofilresiko/exportLembarPernyataanJSON',
                        generator: generateLembarPernyataanPDF,
                        progress: 10
                    },
                    {
                        name: '01. Laporan Profil Risiko Kredit.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganKreditJSON',
                        generator: generateRisikoKreditPDF,
                        progress: 25
                    },
                    {
                        name: '02. Laporan Profil Risiko Operasional.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganOperasionalJSON',
                        generator: generateRisikoOperasionalPDF,
                        progress: 40
                    },
                    {
                        name: '03. Laporan Profil Risiko Kepatuhan.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganKepatuhanJSON',
                        generator: generateRisikoKepatuhanPDF,
                        progress: 55
                    },
                    {
                        name: '04. Laporan Profil Risiko Likuiditas.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganLikuiditasJSON',
                        generator: generateRisikoLikuiditasPDF,
                        progress: 70
                    },
                    {
                        name: '05. Laporan Profil Risiko.pdf',
                        endpoint: 'Showprofilresiko/exportLaporanProfilRisikoJSON',
                        generator: generateLaporanProfilRisikoPDF,
                        progress: 85
                    }
                ];

                let successCount = 0;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    try {
                        addFileToList(file.name, 'processing');
                        updateProgress(file.progress, `Membuat ${file.name}...`);

                        // Fetch data dari endpoint
                        const response = await fetch(`${BASE_URL}/${file.endpoint}`, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin'
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const result = await response.json();

                        if (result.status !== 'success') {
                            throw new Error(result.message || 'Gagal mengambil data');
                        }

                        // Generate PDF
                        const pdfBytes = await file.generator(result.data);

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
                link.download = `Laporan Profil Risiko Semester ${semester} Tahun ${tahun}.zip`;


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

        // Auto start ketika halaman dimuat
        window.onload = () => {
            generateAllPDFsToZip();
        };
    </script>
</body>

</html>