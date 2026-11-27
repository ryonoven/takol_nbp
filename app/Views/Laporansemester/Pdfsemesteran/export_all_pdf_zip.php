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
        // Mode tertanam (iframe tersembunyi di halaman Laporan Profil Risiko): progres & hasil dikirim ke halaman induk
        const EMBED = window.parent !== window;
        function notifyParent(msg) {
            if (EMBED) window.parent.postMessage(Object.assign({ source: 'spr-pdf' }, msg), window.location.origin);
        }

        // ================= KOP & FOOTER SERAGAM (gaya PDF Laporan Tahunan) =================
        // Logo kiri · nama BPR (tebal, kapital) + alamat + kontak rata kanan · garis tebal · judul di tengah.
        const DOC_LOGO = new WeakMap();   // pdfDoc -> logo ter-embed (dipakai ulang di setiap halaman)
        const DOC_FOOT = new WeakMap();   // pdfDoc -> teks footer kiri
        const KOP_INK = rgb(0, 0, 0);
        const KOP_MUTED = rgb(0.25, 0.25, 0.25);

        /** Muat & simpan logo BPR untuk dokumen (aman dipanggil berulang). */
        async function attachDocLogo(pdfDoc, bpr) {
            if (DOC_LOGO.has(pdfDoc) || !bpr || !bpr.logo) return DOC_LOGO.get(pdfDoc) || null;
            try {
                const bytes = await fetch(bpr.logo).then(r => r.arrayBuffer());
                const img = (bpr.logo.includes('image/png') || bpr.logo.endsWith('.png'))
                    ? await pdfDoc.embedPng(bytes) : await pdfDoc.embedJpg(bytes);
                DOC_LOGO.set(pdfDoc, img);
                return img;
            } catch (e) {
                console.warn('Logo tidak dapat dimuat:', e);
                return null;
            }
        }

        /** Pecah teks menjadi baris sesuai lebar (pt). */
        function kopWrap(text, font, size, maxWidth) {
            const words = sanitizeText(String(text || '')).split(/\s+/).filter(Boolean);
            const lines = [];
            let line = '';
            words.forEach(w => {
                const t = line ? line + ' ' + w : w;
                if (font.widthOfTextAtSize(t, size) > maxWidth && line) { lines.push(line); line = w; } else { line = t; }
            });
            if (line) lines.push(line);
            return lines;
        }

        /**
         * Kop surat gaya Laporan Tahunan. Mengembalikan posisi Y tepat di bawah garis kop.
         * opts: { margin = 40, top = 24, logo }
         */
        function drawKop(page, fontBold, fontRegular, bpr, opts = {}) {
            const { width, height } = page.getSize();
            const m = opts.margin ?? 40;
            const top = height - (opts.top ?? 24);
            bpr = bpr || {};

            let logo = opts.logo || DOC_LOGO.get(page.doc) || null;
            if (opts.logo && page.doc) DOC_LOGO.set(page.doc, opts.logo);

            let logoBottom = top;
            let logoW = 0;
            if (logo) {
                try {
                    const s = Math.min(120 / logo.width, 42 / logo.height);
                    const w = logo.width * s, h = logo.height * s;
                    page.drawImage(logo, { x: m, y: top - h, width: w, height: h });
                    logoBottom = top - h;
                    logoW = w;
                } catch (e) { console.warn('Logo gagal digambar:', e); }
            }

            // Blok identitas rata kanan
            const right = width - m;
            const maxW = Math.max(180, width - m * 2 - logoW - 20);
            let y = top - 9;
            const drawRight = (txt, font, size, color) => {
                page.drawText(txt, { x: right - font.widthOfTextAtSize(txt, size), y, size, font, color });
                y -= size + 3;
            };
            kopWrap(String(bpr.namabpr || '').toUpperCase(), fontBold, 9.5, maxW).forEach(l => drawRight(l, fontBold, 9.5, KOP_INK));
            if (bpr.alamat) kopWrap(bpr.alamat, fontRegular, 8, maxW).forEach(l => drawRight(l, fontRegular, 8, KOP_MUTED));
            const kontak = [bpr.webbpr ? 'Web: ' + bpr.webbpr : '', bpr.email ? 'Email: ' + bpr.email : '', bpr.nomor || '']
                .filter(Boolean).join(' | ');
            if (kontak) kopWrap(kontak, fontRegular, 8, maxW).forEach(l => drawRight(l, fontRegular, 8, KOP_MUTED));

            const ruleY = Math.min(y + 4, logoBottom) - 6;
            page.drawLine({ start: { x: m, y: ruleY }, end: { x: width - m, y: ruleY }, thickness: 1.5, color: KOP_INK });
            return ruleY;
        }

        /** Judul bertingkat di tengah (baris pertama lebih besar). Mengembalikan Y di bawah judul. */
        function drawKopTitle(page, fontBold, lines, y, sizes = [12.5, 10]) {
            const { width } = page.getSize();
            lines.filter(Boolean).forEach((txt, i) => {
                const size = i === 0 ? sizes[0] : sizes[1];
                const t = sanitizeText(String(txt));
                y -= size + 4;
                page.drawText(t, { x: (width - fontBold.widthOfTextAtSize(t, size)) / 2, y, size, font: fontBold, color: KOP_INK });
            });
            return y - 6;
        }

        /** Kop + judul untuk halaman tabel risiko (lanskap); tabel tetap mulai di height - 120. */
        function drawRiskPageHeader(page, fontBold, fontRegular, data, title, logoImage) {
            const bpr = (data && data.bpr) || {};
            const p = (data && data.periode) || {};
            const ruleY = drawKop(page, fontBold, fontRegular, bpr, { margin: 50, top: 20, logo: logoImage });
            drawKopTitle(page, fontBold, [title, String(bpr.namabpr || '').toUpperCase(), `SEMESTER ${p.semester || '-'} TAHUN ${p.tahun || '-'}`],
                ruleY - 1, [11.5, 8.5]);
            if (page.doc && !DOC_FOOT.has(page.doc)) DOC_FOOT.set(page.doc, 'Laporan Profil Risiko · ' + (bpr.namabpr || ''));
        }

        /** Footer seragam: garis tipis, keterangan kiri, nomor halaman kanan. */

        function updateProgress(percent, text) {
            notifyParent({ ev: 'progress', pct: percent, text: text });
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

            fileList.scrollTop = fileList.scrollHeight;
        }

        function showError(message) {
            notifyParent({ ev: 'error', text: message });
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

            let sanitized = String(text);

            // --- Normalisasi SEMUA jenis enter/newline & tab ---
            sanitized = sanitized.replace(/\r\n/g, '\n');      // CRLF -> LF
            sanitized = sanitized.replace(/\r/g, '\n');        // CR -> LF
            sanitized = sanitized.replace(/<br\s*\/?>/gi, '\n'); // <br> -> LF dulu (biar konsisten)
            sanitized = sanitized.replace(/[\n]+/g, ' ');      // semua LF (satu atau berturut-turut) -> 1 spasi
            sanitized = sanitized.replace(/\t/g, ' ');

            // --- HTML entities ---
            sanitized = sanitized.replace(/&nbsp;/gi, ' ');
            sanitized = sanitized.replace(/&amp;/gi, '&');
            sanitized = sanitized.replace(/&lt;/gi, '<');
            sanitized = sanitized.replace(/&gt;/gi, '>');
            sanitized = sanitized.replace(/&quot;/gi, '"');
            sanitized = sanitized.replace(/&#39;/gi, "'");
            sanitized = sanitized.replace(/<[^>]*>/g, ''); // buang sisa tag HTML lain

            // --- Karakter matematika & spesial -> ASCII aman untuk font Helvetica ---
            sanitized = sanitized
                .replace(/≥/g, '>=')
                .replace(/≤/g, '<=')
                .replace(/≠/g, '!=')
                .replace(/≈/g, '~=')
                .replace(/×/g, 'x')
                .replace(/÷/g, '/')
                .replace(/–/g, '-')
                .replace(/—/g, '-')
                .replace(/[""]/g, '"')
                .replace(/['']/g, "'")
                .replace(/•/g, '-')
                .replace(/…/g, '...')
                .replace(/™/g, 'TM')
                .replace(/©/g, '(c)')
                .replace(/®/g, '(R)')
                .replace(/°/g, ' derajat')
                .replace(/±/g, '+/-')
                .replace(/¢/g, 'cent')
                .replace(/£/g, 'GBP')
                .replace(/€/g, 'EUR')
                .replace(/¥/g, 'YEN')
                .replace(/§/g, 'section')
                .replace(/¶/g, 'paragraph')
                .replace(/→/g, '->')
                .replace(/←/g, '<-')
                .replace(/↑/g, '^')
                .replace(/↓/g, 'v')
                .replace(/↔/g, '<->')
                .replace(/∞/g, 'infinity')
                .replace(/√/g, 'sqrt')
                .replace(/α/g, 'alpha')
                .replace(/β/g, 'beta')
                .replace(/γ/g, 'gamma')
                .replace(/δ/g, 'delta')
                .replace(/ε/g, 'epsilon')
                .replace(/θ/g, 'theta')
                .replace(/λ/g, 'lambda')
                .replace(/μ/g, 'mu')
                .replace(/π/g, 'pi')
                .replace(/σ/g, 'sigma');

            // --- Buang sisa karakter non-printable ASCII (di luar 0x20-0x7E) ---
            sanitized = sanitized.replace(/[^\x20-\x7E]/g, '');

            // --- Rapikan spasi ganda hasil dari semua replace di atas ---
            sanitized = sanitized.replace(/\s+/g, ' ');
            sanitized = sanitized.trim();

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

            const words = text.split(' ');
            const allLines = [];
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

        /** Footer seragam: garis tipis, keterangan kiri, nomor halaman kanan. */
        function addPageNumbers(pdfDoc, font) {
            const pages = pdfDoc.getPages();
            const total = pages.length;
            const left = sanitizeText(DOC_FOOT.get(pdfDoc) || 'Laporan Profil Risiko');
            pages.forEach((page, i) => {
                const { width } = page.getSize();
                const m = width > 700 ? 50 : 40;
                page.drawLine({ start: { x: m, y: 32 }, end: { x: width - m, y: 32 }, thickness: 0.5, color: rgb(0.6, 0.6, 0.6) });
                page.drawText(left, { x: m, y: 20, size: 7.5, font, color: KOP_MUTED });
                const t = `Halaman ${i + 1} dari ${total}`;
                page.drawText(t, { x: width - m - font.widthOfTextAtSize(t, 7.5), y: 20, size: 7.5, font, color: KOP_MUTED });
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

        // LEMBAR PERNYATAAN GENERATOR
        async function generateLembarPernyataanPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            const page = pdfDoc.addPage([595, 842]);
            const { width, height } = page.getSize();

            const margin = 20;
            const centerX = width / 2;
            let yPosition = height - margin;

            // Kop seragam (gaya PDF Laporan Tahunan)
            await attachDocLogo(pdfDoc, data.bpr);
            DOC_FOOT.set(pdfDoc, 'Lembar Pernyataan Profil Risiko · ' + (data.bpr.namabpr || ''));
            yPosition = drawKop(page, fontBold, fontRegular, data.bpr, { margin: 40, top: 30 });

            updateProgress(70, 'Menambahkan konten...', '📝');

            // JUDUL
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

            // ISI PERNYATAAN
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

            // TTD SECTION 
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
            page.drawText('Direktur', {
                x: rightX,
                y: yPosition,
                size: 9,
                font: fontRegular
            });

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function createTableConfig({ fontSize, hasRasio, getParamName, columnWidths, minRowHeight, minY }) {
            const idx = hasRasio
                ? { no: 0, pilar: 1, param: 2, hasil: 3, rasio: 4, nilai: 5, keterangan: 6 }
                : { no: 0, pilar: 1, param: 2, hasil: 3, nilai: 4, keterangan: 5 };

            return {
                fontSize,
                lineHeight: fontSize + 3,
                hasRasio: !!hasRasio,
                getParamName,
                columnWidths,
                idx,
                minRowHeight: minRowHeight || Math.max(30, fontSize * 3.5),
                rowPadding: fontSize + 8,
                minY: minY || 80
            };
        }

        // ----------------------------------------------------------------------------
        // B. HITUNG TINGGI BARIS (dipakai bareng oleh kategori & child row, SATU rumus)
        // ----------------------------------------------------------------------------
        function calcGenericRowHeight(data, cfg, fontRegular) {
            const { fontSize: fs, lineHeight: lh, idx, columnWidths: cw } = cfg;

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, cw[idx.hasil] - 10, fontRegular, fs);

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, cw[idx.keterangan] - 10, fontRegular, fs);

            const paramName = sanitizeText(cfg.getParamName(data.faktor1id));
            const paramLines = wrapTextToLines(paramName, cw[idx.param] - 10, fontRegular, fs);

            const maxLines = Math.max(
                penjelasanLines.length || 1,
                keteranganLines.length || 1,
                paramLines.length || 1
            );

            return Math.max(cfg.minRowHeight, (maxLines * lh) + cfg.rowPadding);
        }

        // ----------------------------------------------------------------------------
        // C. GAMBAR SATU BARIS "CHILD" (di dalam kategori, kolom No/Pilar sudah
        //    digambar terpisah oleh drawGenericCategoryBlock)
        // ----------------------------------------------------------------------------
        function drawGenericChildRow(page, font, data, yPosition, margin, cfg, rowHeight) {
            const { rgb } = PDFLib;
            const { fontSize: fs, lineHeight: lh, idx, columnWidths: cw } = cfg;

            let xPos = margin + cw[idx.no] + cw[idx.pilar];
            const topTextY = yPosition - 12; // jarak teks pertama dari atas sel

            // --- Parameter Penilaian ---
            page.drawRectangle({ x: xPos, y: yPosition - rowHeight, width: cw[idx.param], height: rowHeight, borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
            const paramName = sanitizeText(cfg.getParamName(data.faktor1id));
            wrapTextToLines(paramName, cw[idx.param] - 10, font, fs)
                .forEach((line, i) => page.drawText(line, { x: xPos + 5, y: topTextY - (i * lh), size: fs, font }));
            xPos += cw[idx.param];

            // --- Hasil Penilaian ---
            page.drawRectangle({ x: xPos, y: yPosition - rowHeight, width: cw[idx.hasil], height: rowHeight, borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            wrapTextToLines(penjelasanText, cw[idx.hasil] - 10, font, fs)
                .forEach((line, i) => page.drawText(line, { x: xPos + 5, y: topTextY - (i * lh), size: fs, font }));
            xPos += cw[idx.hasil];

            // --- Rasio (opsional, hanya Kredit & Likuiditas Inheren) ---
            if (cfg.hasRasio) {
                page.drawRectangle({ x: xPos, y: yPosition - rowHeight, width: cw[idx.rasio], height: rowHeight, borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                const rasioText = data.rasiokredit ? data.rasiokredit + '%' : '-';
                const rw = font.widthOfTextAtSize(rasioText, fs);
                page.drawText(rasioText, { x: xPos + (cw[idx.rasio] / 2) - (rw / 2), y: yPosition - (rowHeight / 2) - 3, size: fs, font });
                xPos += cw[idx.rasio];
            }

            // --- Nilai Pilar (kotak warna) ---
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';

            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: cw[idx.nilai], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6
            });
            page.drawText(nilaiText, {
                x: xPos + (cw[idx.nilai] / 2) - (font.widthOfTextAtSize(nilaiText, fs + 3) / 2),
                y: yPosition - (rowHeight / 2) + 4, size: fs + 3, font, color: rgb(0, 0, 0)
            });
            page.drawText(nilaiPilarLabel, {
                x: xPos + (cw[idx.nilai] / 2) - (font.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: yPosition - (rowHeight / 2) - 8, size: 7, font, color: rgb(0, 0, 0)
            });
            xPos += cw[idx.nilai];

            // --- Keterangan ---
            page.drawRectangle({ x: xPos, y: yPosition - rowHeight, width: cw[idx.keterangan], height: rowHeight, borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
            const keteranganText = sanitizeText(data.keterangan) || '-';
            wrapTextToLines(keteranganText, cw[idx.keterangan] - 10, font, fs)
                .forEach((line, i) => page.drawText(line, { x: xPos + 5, y: topTextY - (i * lh), size: fs, font }));
        }

        // ----------------------------------------------------------------------------
        // D. GAMBAR SATU BLOK KATEGORI (kolom No + Pilar Penilaian, bisa nyambung
        //    beberapa halaman) — pengganti drawKategoriXXX
        // ----------------------------------------------------------------------------
        function drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular, no, title, kategoriData, yPosition, margin, cfg, createNewPageFunc) {
            const { rgb } = PDFLib;
            const { fontSize: fs, lineHeight: lh, idx, columnWidths: cw, minY } = cfg;

            if (!kategoriData || !kategoriData.children) {
                return { yPosition, lastPage: currentPage };
            }
            const children = kategoriData.children.filter(c => c != null);
            if (children.length === 0) {
                return { yPosition, lastPage: currentPage };
            }

            const childHeights = children.map(child => calcGenericRowHeight(child, cfg, fontRegular));

            // Bagi child-row ke beberapa halaman kalau perlu, sambil catat startY/endY
            // PER HALAMAN supaya label kategori selalu dihitung relatif ke halaman itu
            // sendiri (ini yang mencegah label "nyasar" keluar tabel).
            let pages = [{ page: currentPage, startY: yPosition, endY: 0, isFirst: true }];
            let pageIdx = 0;
            let curY = yPosition;

            children.forEach((child, i) => {
                const h = childHeights[i];

                if (curY - h < minY) {
                    pages[pageIdx].endY = curY;
                    const np = createNewPageFunc();
                    currentPage = np.page;
                    curY = np.yPosition;
                    pages.push({ page: currentPage, startY: curY, endY: 0, isFirst: false });
                    pageIdx++;
                }

                drawGenericChildRow(currentPage, fontRegular, child, curY, margin, cfg, h);
                curY -= h;
            });
            pages[pageIdx].endY = curY;

            // Gambar background kuning + label — SETELAH semua baris digambar, supaya
            // startY/endY final (per halaman) sudah pasti benar.
            pages.forEach(pInfo => {
                const { page, startY, endY, isFirst } = pInfo;
                const boxHeight = startY - endY;
                if (boxHeight <= 0) return;

                page.drawRectangle({ x: margin, y: endY, width: cw[idx.no], height: boxHeight, color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                page.drawRectangle({ x: margin + cw[idx.no], y: endY, width: cw[idx.pilar], height: boxHeight, color: rgb(0.95, 0.95, 0.8), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });

                // Label No + Title HANYA di halaman pertama kategori ini, diposisikan
                // di TENGAH box (bukan offset tetap dari startY), jadi selalu masuk
                // di dalam kotak berapapun tinggi box-nya.
                if (isFirst) {
                    const centerY = startY - (boxHeight / 2);

                    const noText = String(no);
                    const noWidth = fontBold.widthOfTextAtSize(noText, fs + 1);
                    page.drawText(noText, { x: margin + (cw[idx.no] / 2) - (noWidth / 2), y: centerY - 3, size: fs + 1, font: fontBold });

                    const titleLines = wrapTextToLines(title, cw[idx.pilar] - 10, fontBold, fs);
                    const titleBlockHeight = titleLines.length * lh;
                    const titleStartY = centerY + (titleBlockHeight / 2) - fs;

                    titleLines.forEach((line, li) => {
                        page.drawText(line, { x: margin + cw[idx.no] + 5, y: titleStartY - (li * lh), size: fs, font: fontBold });
                    });
                }
            });

            return { yPosition: curY, lastPage: currentPage };
        }

        // ----------------------------------------------------------------------------
        // E. GAMBAR SATU BARIS TUNGGAL (misal "Faktor Lainnya", "Penilaian Risiko")
        //    — pengganti drawSingleRowXXX
        // ----------------------------------------------------------------------------
        function drawGenericSingleRow(page, fontBold, fontRegular, no, title, data, yPosition, margin, cfg, createNewPageFunc) {
            const { rgb } = PDFLib;
            const { fontSize: fs, lineHeight: lh, idx, columnWidths: cw } = cfg;

            const keteranganText = sanitizeText(data.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, cw[idx.keterangan] - 10, fontRegular, fs);

            const penjelasanText = sanitizeText(data.penjelasanpenilaian) || getRatingLabel(data.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, cw[idx.hasil] - 10, fontRegular, fs);

            const titleLines = wrapTextToLines(title, cw[idx.pilar] - 10, fontBold, fs);

            const maxLines = Math.max(keteranganLines.length, penjelasanLines.length, titleLines.length, 1);
            const rowHeight = Math.max(cfg.minRowHeight, (maxLines * lh) + cfg.rowPadding);

            let needNewPage = yPosition - rowHeight < cfg.minY;
            if (needNewPage) {
                const np = createNewPageFunc();
                page = np.page;
                yPosition = np.yPosition;
                needNewPage = false; // sudah pindah halaman, baris ini pasti muat
            }

            const totalWidth = cw.reduce((a, b) => a + b, 0);
            page.drawRectangle({ x: margin, y: yPosition - rowHeight, width: totalWidth, height: rowHeight, color: rgb(0.98, 0.98, 0.98), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });

            let xPos = margin;
            const centerY = yPosition - (rowHeight / 2);

            // No
            const noText = String(no);
            const noWidth = fontBold.widthOfTextAtSize(noText, fs + 1);
            page.drawText(noText, { x: xPos + (cw[idx.no] / 2) - (noWidth / 2), y: centerY - 3, size: fs + 1, font: fontBold });
            xPos += cw[idx.no];

            // Pilar / title (center vertikal, bisa multi baris)
            const titleBlockHeight = titleLines.length * lh;
            const titleStartY = centerY + (titleBlockHeight / 2) - fs;
            titleLines.forEach((line, i) => page.drawText(line, { x: xPos + 5, y: titleStartY - (i * lh), size: fs, font: fontBold }));
            xPos += cw[idx.pilar];

            // Parameter (selalu "-")
            page.drawText('-', { x: xPos + 5, y: centerY - 3, size: fs, font: fontRegular });
            xPos += cw[idx.param];

            // Hasil Penilaian
            const hasilBlockHeight = penjelasanLines.length * lh;
            const hasilStartY = centerY + (hasilBlockHeight / 2) - fs;
            penjelasanLines.forEach((line, i) => page.drawText(line, { x: xPos + 5, y: hasilStartY - (i * lh), size: fs, font: fontRegular }));
            xPos += cw[idx.hasil];

            // Rasio (opsional)
            if (cfg.hasRasio) {
                page.drawRectangle({ x: xPos, y: yPosition - rowHeight, width: cw[idx.rasio], height: rowHeight, borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                page.drawText('-', { x: xPos + (cw[idx.rasio] / 2) - 3, y: centerY - 3, size: fs, font: fontRegular });
                xPos += cw[idx.rasio];
            }

            // Nilai Pilar
            const penilaiankredit = data.penilaiankredit || '';
            const nilaiPilarLabel = getRatingLabel(penilaiankredit);
            const nilaiText = penilaiankredit || '-';
            page.drawRectangle({
                x: xPos, y: yPosition - rowHeight, width: cw[idx.nilai], height: rowHeight,
                color: getBackgroundColorByRating(penilaiankredit), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6
            });
            page.drawText(nilaiText, {
                x: xPos + (cw[idx.nilai] / 2) - (fontRegular.widthOfTextAtSize(nilaiText, fs + 3) / 2),
                y: centerY + 2, size: fs + 3, font: fontRegular, color: rgb(0, 0, 0)
            });
            page.drawText(nilaiPilarLabel, {
                x: xPos + (cw[idx.nilai] / 2) - (fontRegular.widthOfTextAtSize(nilaiPilarLabel, 7) / 2),
                y: centerY - 10, size: 7, font: fontRegular, color: rgb(0, 0, 0)
            });
            xPos += cw[idx.nilai];

            // Keterangan
            const ketBlockHeight = keteranganLines.length * lh;
            const ketStartY = centerY + (ketBlockHeight / 2) - fs;
            keteranganLines.forEach((line, i) => page.drawText(line, { x: xPos + 5, y: ketStartY - (i * lh), size: fs, font: fontRegular }));

            return { yPosition: yPosition - rowHeight, page, needNewPage };
        }


        // ============================================================================
        // F. KONFIGURASI PER MODUL — buat SEKALI di awal file (di luar fungsi), lalu
        //    tinggal dipakai berkali-kali di setiap drawTableXXX(...).
        // ============================================================================

        // --- KREDIT INHEREN (punya kolom Rasio) ---
        const CFG_KREDIT_INHEREN = createTableConfig({
            fontSize: 9,
            hasRasio: true,
            getParamName: getParameterName,
            columnWidths: [35, 110, 130, 150, 65, 70, 182] // No, Pilar, Param, Hasil, Rasio, Nilai, Keterangan
        });

        // --- KREDIT KPMR (tanpa kolom Rasio) ---
        const CFG_KREDIT_KPMR = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterKPMRName,
            columnWidths: [35, 130, 210, 150, 88, 132] // No, Pilar, Param, Hasil, Nilai, Keterangan
        });

        // --- OPERASIONAL INHEREN & KPMR (tanpa Rasio) ---
        const CFG_OPERASIONAL = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterOperasionalName,
            columnWidths: [30, 120, 180, 160, 80, 172]
        });

        // --- KEPATUHAN INHEREN (tanpa Rasio) ---
        const CFG_KEPATUHAN_INHEREN = createTableConfig({
            fontSize: 9, // dinaikkan dari 7 -> 9 biar lebih mudah dibaca & konsisten
            hasRasio: false,
            getParamName: getParameterKepatuhanName,
            columnWidths: [30, 120, 200, 140, 80, 172]
        });

        // --- KEPATUHAN KPMR (tanpa Rasio) ---
        const CFG_KEPATUHAN_KPMR = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterKepatuhanKPMRName,
            columnWidths: [30, 120, 200, 140, 80, 172]
        });

        // --- LIKUIDITAS INHEREN (punya kolom Rasio) ---
        const CFG_LIKUIDITAS_INHEREN = createTableConfig({
            fontSize: 9,
            hasRasio: true,
            getParamName: getParameterLikuiditasName,
            columnWidths: [35, 110, 130, 150, 65, 70, 182]
        });

        // --- LIKUIDITAS KPMR (tanpa Rasio) — ini yang di screenshot kamu ---
        const CFG_LIKUIDITAS_KPMR = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterLikuiditasKPMRName,
            columnWidths: [35, 130, 210, 150, 85, 132]
        });

        // --- REPUTASI INHEREN & KPMR ---
        const CFG_REPUTASI_INHEREN = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterReputasiName,
            columnWidths: [32, 130, 210, 150, 88, 132]
        });
        const CFG_REPUTASI_KPMR = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterReputasiKPMRName,
            columnWidths: [32, 130, 210, 150, 88, 132]
        });

        // --- STRATEJIK INHEREN & KPMR ---
        const CFG_STRATEJIK_INHEREN = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterStratejikName,
            columnWidths: [32, 130, 210, 150, 88, 132]
        });
        const CFG_STRATEJIK_KPMR = createTableConfig({
            fontSize: 9,
            hasRasio: false,
            getParamName: getParameterStratejikKPMRName,
            columnWidths: [32, 130, 210, 150, 88, 132]
        });


        // ============================================================================
        // G. CONTOH INTEGRASI — drawTableLikuiditasKPMR (modul di screenshot kamu)
        //    Pola ini SAMA untuk semua modul lain, tinggal ganti cfg & getParamName-nya.
        // ============================================================================
        async function drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_LIKUIDITAS_KPMR;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 30, width: tableWidth, height: 30, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 20, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 30;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderLikuiditas(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKPMR.nilai[cat.key]?.kategori) {
                    const result = drawGenericCategoryBlock(
                        pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKPMR.nilai[cat.key],
                        yPosition, margin, cfg, createNewPage
                    );
                    yPosition = result.yPosition;
                    currentPage = result.lastPage;
                }
            }

            if (dataKPMR.nilai135) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR', dataKPMR.nilai135, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition;
                currentPage = result.page;
            }

            if (dataKPMR.nilai136) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya', dataKPMR.nilai136, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition;
                currentPage = result.page;
            }

            return yPosition;
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
            const titles = {
                INHEREN: 'LAPORAN PROFIL RISIKO KREDIT INHEREN', KPMR: 'LAPORAN PROFIL RISIKO KREDIT KPMR',
                OPERASIONAL_INHEREN: 'LAPORAN PROFIL RISIKO OPERASIONAL INHEREN', OPERASIONAL_KPMR: 'LAPORAN PROFIL RISIKO OPERASIONAL KPMR',
                KEPATUHAN_INHEREN: 'LAPORAN PROFIL RISIKO KEPATUHAN INHEREN', KEPATUHAN_KPMR: 'LAPORAN PROFIL RISIKO KEPATUHAN KPMR',
                LIKUIDITAS_INHEREN: 'LAPORAN PROFIL RISIKO LIKUIDITAS INHEREN', LIKUIDITAS_KPMR: 'LAPORAN PROFIL RISIKO LIKUIDITAS KPMR',
                REPUTASI_INHEREN: 'LAPORAN PROFIL RISIKO REPUTASI INHEREN', REPUTASI_KPMR: 'LAPORAN PROFIL RISIKO REPUTASI KPMR',
                STRATEJIK_INHEREN: 'LAPORAN PROFIL RISIKO STRATEJIK INHEREN', STRATEJIK_KPMR: 'LAPORAN PROFIL RISIKO STRATEJIK KPMR',
            };
            drawRiskPageHeader(page, fontBold, fontRegular, data, titles[jenis] || 'LAPORAN PROFIL RISIKO', logoImage);
        }

        async function drawTableKreditInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_KREDIT_INHEREN;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Rasio', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 30, width: tableWidth, height: 30, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 20, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 30;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            if (dataInheren.nilai?.komposisi?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Komposisi Portofolio Aset dan Tingkat Konsentrasi Kredit',
                    dataInheren.nilai.komposisi, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.kualitas?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kualitas Aset',
                    dataInheren.nilai.kualitas, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.strategi) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '3', 'Strategi Penyediaan Dana',
                    dataInheren.nilai.strategi, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai?.eksternal) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '4', 'Faktor Eksternal',
                    dataInheren.nilai.eksternal, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai?.lainnya) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '5', 'Faktor Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai13) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai13, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai14) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai14, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        async function drawTableKreditKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_KREDIT_KPMR;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 30, width: tableWidth, height: 30, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 20, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 30;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKPMR.nilai?.[cat.key]?.kategori) {
                    const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKPMR.nilai[cat.key], yPosition, margin, cfg, createNewPage);
                    yPosition = result.yPosition; currentPage = result.lastPage;
                }
            }

            if (dataKPMR.nilai33) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai33, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataKPMR.nilai34) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai34, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
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

            addPageNumbers(pdfDoc, fontRegular);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeaderOperasional(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            drawRiskPageHeader(page, fontBold, fontRegular, data, 'LAPORAN PROFIL RISIKO OPERASIONAL ' + (jenis === 'INHEREN' ? 'INHEREN' : 'KPMR'), logoImage);
        }

        async function drawTableOperasionalInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_OPERASIONAL;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 25, width: tableWidth, height: 25, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 18, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 25;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            if (dataInheren.nilai?.kompleksitas?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Kompleksitas Bisnis dan Kelembagaan',
                    dataInheren.nilai.kompleksitas, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.sdm?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Sumber Daya Manusia (SDM)',
                    dataInheren.nilai.sdm, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.ti) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '3', 'Penyelenggaraan Teknologi Informasi (TI)',
                    dataInheren.nilai.ti, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai?.fraud) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '4', 'Pilar Penyimpangan (Fraud)',
                    dataInheren.nilai.fraud, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai?.eksternal) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '5', 'Faktor Eksternal',
                    dataInheren.nilai.eksternal, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai?.lainnya) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '6', 'Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai13) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai13, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai14) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai14, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        async function drawTableOperasionalKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_OPERASIONAL;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 25, width: tableWidth, height: 25, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 18, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 25;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderOperasional(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKPMR.nilai?.[cat.key]?.kategori) {
                    const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKPMR.nilai[cat.key], yPosition, margin, cfg, createNewPage);
                    yPosition = result.yPosition; currentPage = result.lastPage;
                }
            }

            if (dataKPMR.nilai33) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai33, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataKPMR.nilai34) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai34, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }


        // Helper function untuk membersihkan <br> dari teks
        function cleanBrTags(text) {
            if (!text) return text;
            return String(text).replace(/<br\s*\/?>/gi, ' ').trim();
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

            addPageNumbers(pdfDoc, fontRegular);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeaderKepatuhan(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            drawRiskPageHeader(page, fontBold, fontRegular, data, 'LAPORAN PROFIL RISIKO KEPATUHAN ' + (jenis === 'INHEREN' ? 'INHEREN' : 'KPMR'), logoImage);
        }

        async function drawTableKepatuhanInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_KEPATUHAN_INHEREN;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 25, width: tableWidth, height: 25, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 18, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 25;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            if (dataInheren.nilai?.pelanggaran?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pilar pelanggaran terhadap ketentuan peraturan perundang-undangan dan ketentuan lain',
                    dataInheren.nilai.pelanggaran, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.hukum?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Faktor kelemahan aspek hukum',
                    dataInheren.nilai.hukum, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.lainnya) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '3', 'Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai81) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai81, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai82) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai82, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        async function drawTableKepatuhanKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKepatuhan, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_KEPATUHAN_KPMR;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 25, width: tableWidth, height: 25, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 18, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 25;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderKepatuhan(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKepatuhan.nilai?.[cat.key]?.kategori) {
                    const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKepatuhan.nilai[cat.key], yPosition, margin, cfg, createNewPage);
                    yPosition = result.yPosition; currentPage = result.lastPage;
                }
            }

            if (dataKepatuhan.nilai102) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKepatuhan.nilai102, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataKepatuhan.nilai103) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKepatuhan.nilai103, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
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

            addPageNumbers(pdfDoc, fontRegular);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        function drawHeaderLikuiditas(page, fontBold, fontRegular, data, width, height, jenis, logoImage) {
            drawRiskPageHeader(page, fontBold, fontRegular, data, 'LAPORAN PROFIL RISIKO LIKUIDITAS ' + (jenis === 'INHEREN' ? 'INHEREN' : 'KPMR'), logoImage);
        }

        async function drawTableLikuiditasInheren(pdfDoc, currentPage, fontBold, fontRegular, dataInheren, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_LIKUIDITAS_INHEREN;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Rasio', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 30, width: tableWidth, height: 30, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 20, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 30;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderLikuiditas(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'INHEREN', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            if (dataInheren.nilai?.konsentrasi?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Komposisi dan konsentrasi aset dan kewajiban',
                    dataInheren.nilai.konsentrasi, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.kerentanan?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kerentanan pada kebutuhan pendanaan serta akses pada sumber pendanaan',
                    dataInheren.nilai.kerentanan, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataInheren.nilai?.lainnya) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '3', 'Faktor Lainnya',
                    dataInheren.nilai.lainnya, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai115) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko',
                    dataInheren.nilai115, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataInheren.nilai116) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataInheren.nilai116, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        // FUNGSI KPMR dengan font size 9
        async function drawTableLikuiditasKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataKPMR, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_LIKUIDITAS_KPMR;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);

            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - 30, width: tableWidth, height: 30, color: rgb(0.886, 0.902, 0.973), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((h, i) => {
                    page.drawText(h, { x: xPos, y: y - 20, size: 9, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - 30;
            };

            const createNewPage = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeaderLikuiditas(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'KPMR', logoImage);
                const newY = drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = drawHeaderRow(currentPage, yPosition);

            const categories = [
                { key: 'pengawasan', no: '1', title: 'Pengawasan Direksi dan Dewan Komisaris' },
                { key: 'kebijakan', no: '2', title: 'Kecukupan Kebijakan, Prosedur, dan Limit' },
                { key: 'proses', no: '3', title: 'Kecukupan Proses dan Sistem Manajemen Informasi' },
                { key: 'pengendalian', no: '4', title: 'Sistem Pengendalian Internal yang Menyeluruh' }
            ];

            for (const cat of categories) {
                if (dataKPMR.nilai?.[cat.key]?.kategori) {
                    const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                        cat.no, cat.title, dataKPMR.nilai[cat.key], yPosition, margin, cfg, createNewPage);
                    yPosition = result.yPosition; currentPage = result.lastPage;
                }
            }

            if (dataKPMR.nilai135) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataKPMR.nilai135, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataKPMR.nilai136) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataKPMR.nilai136, yPosition, margin, cfg, createNewPage);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        async function generatePDFReputasi() {
            try {
                updateProgress(5, 'Mengambil data Risiko Reputasi...');

                const responseReputasi = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganReputasi`);
                const resultReputasi = await responseReputasi.json();

                if (resultReputasi.status !== 'success') {
                    throw new Error('Gagal mengambil data reputasi');
                }

                const dataReputasi = resultReputasi.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // RISIKO REPUTASI INHEREN
                updateProgress(40, 'Membuat halaman Risiko Reputasi Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataReputasi, width, height, 'REPUTASI_INHEREN');

                updateProgress(55, 'Menambahkan data Reputasi Inheren...');

                await drawTableReputasiInheren(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataReputasi.inheren,
                    dataReputasi.periode,
                    dataReputasi.bpr,
                    width,
                    height
                );

                // RISIKO REPUTASI KPMR
                updateProgress(75, 'Membuat halaman Risiko Reputasi KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataReputasi, width, height, 'REPUTASI_KPMR');

                updateProgress(88, 'Menambahkan data Reputasi KPMR...');

                await drawTableReputasiKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataReputasi.kpmr, dataReputasi.periode, dataReputasi.bpr, width, height);

                updateProgress(95, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                const namaBPR = dataReputasi.bpr.namabpr || 'BPR';
                const semester = dataReputasi.periode.semester || '1';
                const tahun = dataReputasi.periode.tahun || '2024';

                const fileName = `05. Laporan Profil Risiko Reputasi Semester ${semester} Tahun ${tahun}.pdf`;

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

        // KONSTANTA FONT & LAYOUT TERPUSAT
        const TABLE_FONT_SIZE = 9;
        const TABLE_HEADER_SIZE = 10;
        const PILAR_FONT_SIZE = 9;
        const NILAI_FONT_SIZE = 12;
        const NILAI_LABEL_SIZE = 7;
        const PENJELASAN_FONT_SIZE = 8;
        const LINE_HEIGHT_FACTOR = 1.6;
        const MIN_ROW_HEIGHT = 32;
        const ROW_PADDING = 14;
        const HEADER_ROW_HEIGHT = 30;
        const MIN_Y = 80;
        const COL_WIDTHS = [32, 130, 210, 150, 88, 132];

        // TABLE REPUTASI INHEREN
        async function drawTableReputasiInheren(pdfDoc, currentPage, fontBold, fontRegular, dataReputasi, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_REPUTASI_INHEREN;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const _drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - HEADER_ROW_HEIGHT, width: tableWidth, height: HEADER_ROW_HEIGHT, color: rgb(0.85, 0.85, 0.85), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    page.drawText(header, { x: xPos, y: y - HEADER_ROW_HEIGHT + 10, size: TABLE_HEADER_SIZE, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - HEADER_ROW_HEIGHT;
            };

            const createNewPageForKategori = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'REPUTASI_INHEREN', logoImage);
                const newY = _drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = _drawHeaderRow(currentPage, yPosition);

            if (dataReputasi.nilai.pengelolaan?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Kompleksitas bisnis dan kelembagaan',
                    dataReputasi.nilai.pengelolaan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai.frekuensi) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '2', 'Frekuensi dan signifikansi pengaduan nasabah',
                    dataReputasi.nilai.frekuensi, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataReputasi.nilai.keluhan?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Administrasi dan tindak lanjut pengaduan nasabah',
                    dataReputasi.nilai.keluhan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai.etika?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Pelanggaran etika bisnis',
                    dataReputasi.nilai.etika, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai.lainnya) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '5', 'Lainnya',
                    dataReputasi.nilai.lainnya, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataReputasi.nilai148) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko',
                    dataReputasi.nilai148, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataReputasi.nilai149) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataReputasi.nilai149, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        function getParameterReputasiName(faktorId) {
            const names = {
                138: 'Kompleksitas bisnis dan kelembagaan',
                139: 'Skala usaha dan struktur organisasi',
                140: 'Jaringan kantor, Rentang kendali dan lokasi kantor cabang',
                141: 'Frekuensi dan signifikansi pengaduan nasabah',
                142: 'Administrasi dan tindak lanjut pengaduan nasabah',
                143: 'Signifikansi dan materialitas pengaduan nasabah',
                144: 'Pelanggaran etika bisnis',
                145: 'Transparansi informasi keuangan',
                146: 'Transparansi produk dan layanan BPR',
                147: 'Lainnya'
            };
            return names[faktorId] || 'Parameter';
        }

        function calcChildHeight(child, columnWidths, fontRegular, getParamNameFn) {
            const fs = TABLE_FONT_SIZE;
            const lh = Math.ceil(fs * LINE_HEIGHT_FACTOR);

            const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fs);

            const keteranganText = sanitizeText(child.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fs);

            const paramName = sanitizeText(getParamNameFn(child.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fs);

            const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
            return Math.max(MIN_ROW_HEIGHT, maxLines * lh + ROW_PADDING);
        }

        // Kategori Reputasi Inheren

        // generatePDFReputasiBytes (untuk ZIP)
        async function generatePDFReputasiBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);
            await attachDocLogo(pdfDoc, data.bpr);

            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'REPUTASI_INHEREN');
            await drawTableReputasiInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height);

            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'REPUTASI_KPMR');
            await drawTableReputasiKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height);

            addPageNumbers(pdfDoc, fontRegular);

            return await pdfDoc.save();
        }

        // drawTableReputasiKPMR
        async function drawTableReputasiKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataReputasi, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_REPUTASI_KPMR;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const _drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - HEADER_ROW_HEIGHT, width: tableWidth, height: HEADER_ROW_HEIGHT, color: rgb(0.85, 0.85, 0.85), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    page.drawText(header, { x: xPos, y: y - HEADER_ROW_HEIGHT + 10, size: TABLE_HEADER_SIZE, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - HEADER_ROW_HEIGHT;
            };

            const createNewPageForKategori = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'REPUTASI_KPMR', logoImage);
                const newY = _drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = _drawHeaderRow(currentPage, yPosition);

            if (dataReputasi.nilai.pengawasan.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataReputasi.nilai.pengawasan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai.kebijakan.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataReputasi.nilai.kebijakan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai.proses.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Kecukupan Proses dan Sistem Manajemen Informasi',
                    dataReputasi.nilai.proses, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai.pengendalian.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Sistem Pengendalian Internal yang Menyeluruh',
                    dataReputasi.nilai.pengendalian, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataReputasi.nilai165) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataReputasi.nilai165, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataReputasi.nilai166) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataReputasi.nilai166, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        // drawKategoriReputasiKPMR

        function getParameterReputasiKPMRName(faktorId) {
            const names = {
                151: 'Pengawasan Direksi dan Komisaris',
                152: 'Apakah Dewan Komisaris telah memberikan persetujuan terhadap kebijakan Manajemen Risiko reputasi?',
                153: 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi?',
                154: 'Apakah Direksi telah menyusun kebijakan Manajemen Risiko reputasi?',
                155: 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan mitigasi?',
                156: 'Apakah BPR telah memiliki kecukupan organisasi?',
                157: 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM?',
                158: 'Kecukupan Kebijakan, Prosedur, dan Limit',
                159: 'Apakah BPR telah memiliki kebijakan Manajemen Risiko reputasi yang memadai?',
                160: 'Apakah BPR memiliki prosedur Manajemen Risiko reputasi?',
                161: 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk baru?',
                162: 'Kecukupan Proses dan Sistem Manajemen Informasi',
                163: 'Apakah BPR telah melaksanakan proses Manajemen Risiko reputasi?',
                164: 'Apakah BPR telah memiliki sistem informasi Manajemen Risiko?',
                165: 'Sistem Pengendalian Internal yang Menyeluruh',
                166: 'Apakah SKAI/PEAI telah melaksanakan audit secara berkala?',
                167: 'Apakah sistem pengendalian intern telah dilaksanakan?',
                168: 'Penilaian Risiko KPMR',
                169: 'Penilaian Risiko KPMR Periode Sebelumnya'
            };
            return names[faktorId] || 'Parameter';
        }

        // 6. LAPORAN RISIKO STRATEJIK
        async function generatePDFStratejik() {
            try {
                updateProgress(5, 'Mengambil data Risiko Stratejik...');

                const responseStratejik = await fetch(`${BASE_URL}/Risikokredit/exportPDFGabunganStratejik`);
                const resultStratejik = await responseStratejik.json();

                if (resultStratejik.status !== 'success') {
                    throw new Error('Gagal mengambil data stratejik');
                }

                const dataStratejik = resultStratejik.data;

                updateProgress(15, 'Membuat dokumen PDF...');

                const { PDFDocument, rgb, StandardFonts } = PDFLib;
                const pdfDoc = await PDFDocument.create();

                updateProgress(20, 'Menambahkan font...');

                const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
                const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

                // RISIKO STRATEJIK INHEREN
                updateProgress(40, 'Membuat halaman Risiko Stratejik Inheren...');

                let currentPage = pdfDoc.addPage([842, 595]);
                let { width, height } = currentPage.getSize();

                drawHeader(currentPage, fontBold, fontRegular, dataStratejik, width, height, 'STRATEJIK_INHEREN');

                updateProgress(55, 'Menambahkan data Stratejik Inheren...');

                await drawTableStratejikInheren(
                    pdfDoc,
                    currentPage,
                    fontBold,
                    fontRegular,
                    dataStratejik.inheren,
                    dataStratejik.periode,
                    dataStratejik.bpr,
                    width,
                    height
                );

                // RISIKO STRATEJIK KPMR
                updateProgress(75, 'Membuat halaman Risiko Stratejik KPMR...');

                currentPage = pdfDoc.addPage([842, 595]);
                ({ width, height } = currentPage.getSize());

                drawHeader(currentPage, fontBold, fontRegular, dataStratejik, width, height, 'STRATEJIK_KPMR');

                updateProgress(88, 'Menambahkan data Stratejik KPMR...');

                await drawTableStratejikKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataStratejik.kpmr, dataStratejik.periode, dataStratejik.bpr, width, height);

                updateProgress(95, 'Menyimpan dan mengunduh...');

                const pdfBytes = await pdfDoc.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                const namaBPR = dataStratejik.bpr.namabpr || 'BPR';
                const semester = dataStratejik.periode.semester || '1';
                const tahun = dataStratejik.periode.tahun || '2024';

                const fileName = `06. Laporan Profil Risiko Stratejik Semester ${semester} Tahun ${tahun}.pdf`;

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

        // KONSTANTA FONT & LAYOUT TERPUSAT
        // const TABLE_FONT_SIZE = 9;
        // const TABLE_HEADER_SIZE = 10;
        // const PILAR_FONT_SIZE = 9;
        // const NILAI_FONT_SIZE = 12;
        // const NILAI_LABEL_SIZE = 7;
        // const PENJELASAN_FONT_SIZE = 8;
        // const LINE_HEIGHT_FACTOR = 1.6;
        // const MIN_ROW_HEIGHT = 32;
        // const ROW_PADDING = 14;
        // const HEADER_ROW_HEIGHT = 30;
        // const MIN_Y = 80;
        // const COL_WIDTHS = [32, 130, 210, 150, 88, 132];

        // TABLE STRATEJIK INHEREN
        async function drawTableStratejikInheren(pdfDoc, currentPage, fontBold, fontRegular, dataStratejik, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_STRATEJIK_INHEREN;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const _drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - HEADER_ROW_HEIGHT, width: tableWidth, height: HEADER_ROW_HEIGHT, color: rgb(0.85, 0.85, 0.85), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    page.drawText(header, { x: xPos, y: y - HEADER_ROW_HEIGHT + 10, size: TABLE_HEADER_SIZE, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - HEADER_ROW_HEIGHT;
            };

            const createNewPageForKategori = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'STRATEJIK_INHEREN', logoImage);
                const newY = _drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = _drawHeaderRow(currentPage, yPosition);

            if (dataStratejik.nilai.penetapan) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '1', 'Penetapan strategi bisnis',
                    dataStratejik.nilai.penetapan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataStratejik.nilai.penyusunan?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Penyusunan rencana bisnis',
                    dataStratejik.nilai.penyusunan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataStratejik.nilai.pencapaian?.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Pencapaian target bisnis',
                    dataStratejik.nilai.pencapaian, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataStratejik.nilai.lainnya) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '4', 'Lainnya',
                    dataStratejik.nilai.lainnya, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataStratejik.nilai179) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko',
                    dataStratejik.nilai179, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataStratejik.nilai180) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko Periode Sebelumnya',
                    dataStratejik.nilai180, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        function getParameterStratejikName(faktorId) {
            const names = {
                171: 'Penetapan strategi bisnis',
                172: 'Penyusunan rencana bisnis',
                173: 'Pertimbangan faktor eksternal dan internal dalam menyusun rencana dan model bisnis',
                174: 'Keunggulan kompetitif BPR dan ancaman dari kompetitor',
                175: 'Pencapaian target bisnis',
                176: 'Perbandingan realisasi dan target indikator keuangan utama sesuai ketentuan rencana bisnis BPR',
                177: 'Rekam jejak (track record) keberhasilan BPR dalam menerapkan keputusan strategis terkait dengan faktor pengembangan produk/jasa baru, perubahan sasaran bisnis, investasi strategis, rencana penggabungan, peleburan, dan pengambilalihan, serta pencapaian target bisnis',
                178: 'Lainnya',
                179: 'Penilaian Risiko Inheren',
                180: 'Penilaian Risiko Inheren Periode Sebelumnya'
            };
            return names[faktorId] || 'Parameter';
        }

        function calcChildHeight(child, columnWidths, fontRegular, getParamNameFn) {
            const fs = TABLE_FONT_SIZE;
            const lh = Math.ceil(fs * LINE_HEIGHT_FACTOR);

            const penjelasanText = sanitizeText(child.penjelasanpenilaian) || getRatingLabel(child.penilaiankredit);
            const penjelasanLines = wrapTextToLines(penjelasanText, columnWidths[3] - 10, fontRegular, fs);

            const keteranganText = sanitizeText(child.keterangan) || '-';
            const keteranganLines = wrapTextToLines(keteranganText, columnWidths[5] - 10, fontRegular, fs);

            const paramName = sanitizeText(getParamNameFn(child.faktor1id));
            const paramLines = wrapTextToLines(paramName, columnWidths[2] - 10, fontRegular, fs);

            const maxLines = Math.max(penjelasanLines.length, keteranganLines.length, paramLines.length);
            return Math.max(MIN_ROW_HEIGHT, maxLines * lh + ROW_PADDING);
        }


        // generatePDFStratejik
        async function generatePDFStratejikBytes(data) {
            const { PDFDocument, StandardFonts } = PDFLib;
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);
            await attachDocLogo(pdfDoc, data.bpr);

            let currentPage = pdfDoc.addPage([842, 595]);
            let { width, height } = currentPage.getSize();
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'STRATEJIK_INHEREN');
            await drawTableStratejikInheren(pdfDoc, currentPage, fontBold, fontRegular, data.inheren, data.periode, data.bpr, width, height);

            currentPage = pdfDoc.addPage([842, 595]);
            ({ width, height } = currentPage.getSize());
            drawHeader(currentPage, fontBold, fontRegular, data, width, height, 'STRATEJIK_KPMR');
            await drawTableStratejikKPMR(pdfDoc, currentPage, fontBold, fontRegular, data.kpmr, data.periode, data.bpr, width, height);

            addPageNumbers(pdfDoc, fontRegular);

            return await pdfDoc.save();
        }

        // drawTableStratejikKPMR
        async function drawTableStratejikKPMR(pdfDoc, currentPage, fontBold, fontRegular, dataStratejik, periode, bpr, width, height, logoImage) {
            const { rgb } = PDFLib;
            const cfg = CFG_STRATEJIK_KPMR;
            let yPosition = height - 120;
            const margin = 50;
            const tableWidth = width - (margin * 2);
            const headers = ['No', 'Pilar Penilaian', 'Parameter Penilaian', 'Hasil Penilaian', 'Nilai Pilar', 'Keterangan'];

            const _drawHeaderRow = (page, y) => {
                page.drawRectangle({ x: margin, y: y - HEADER_ROW_HEIGHT, width: tableWidth, height: HEADER_ROW_HEIGHT, color: rgb(0.85, 0.85, 0.85), borderColor: rgb(0.55, 0.58, 0.66), borderWidth: 0.6 });
                let xPos = margin + 5;
                headers.forEach((header, i) => {
                    page.drawText(header, { x: xPos, y: y - HEADER_ROW_HEIGHT + 10, size: TABLE_HEADER_SIZE, font: fontBold, color: rgb(0, 0, 0) });
                    xPos += cfg.columnWidths[i];
                });
                return y - HEADER_ROW_HEIGHT;
            };

            const createNewPageForKategori = () => {
                const newPage = pdfDoc.addPage([842, 595]);
                const dims = newPage.getSize();
                drawHeader(newPage, fontBold, fontRegular, { periode, bpr }, dims.width, dims.height, 'STRATEJIK_KPMR', logoImage);
                const newY = _drawHeaderRow(newPage, dims.height - 120);
                return { page: newPage, yPosition: newY };
            };

            yPosition = _drawHeaderRow(currentPage, yPosition);

            if (dataStratejik.nilai.pengawasan.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '1', 'Pengawasan Direksi dan Dewan Komisaris',
                    dataStratejik.nilai.pengawasan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataStratejik.nilai.kebijakan.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '2', 'Kecukupan Kebijakan, Prosedur, dan Limit',
                    dataStratejik.nilai.kebijakan, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataStratejik.nilai.proses.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '3', 'Kecukupan Proses dan Sistem Manajemen Informasi',
                    dataStratejik.nilai.proses, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataStratejik.nilai.pengendalian.kategori) {
                const result = drawGenericCategoryBlock(pdfDoc, currentPage, fontBold, fontRegular,
                    '4', 'Sistem Pengendalian Internal yang Menyeluruh',
                    dataStratejik.nilai.pengendalian, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.lastPage;
            }

            if (dataStratejik.nilai165) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR',
                    dataStratejik.nilai165, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            if (dataStratejik.nilai166) {
                const result = drawGenericSingleRow(currentPage, fontBold, fontRegular, '', 'Penilaian Risiko KPMR Periode Sebelumnya',
                    dataStratejik.nilai166, yPosition, margin, cfg, createNewPageForKategori);
                yPosition = result.yPosition; currentPage = result.page;
            }

            return yPosition;
        }

        // drawKategoriStratejikKPMR


        function getParameterStratejikKPMRName(faktorId) {
            const names = {
                182: 'Pilar Pengawasan Direksi dan Dewan Komisaris',
                183: 'Apakah Dewan Komisaris telah melakukan persetujuan terhadap kebijakan manajemen risiko stratejik yang disusun oleh Direksi dan melakukan evaluasi secara berkala?',
                184: 'Apakah Dewan Komisaris telah melakukan evaluasi terhadap pertanggungjawaban Direksi atas pelaksanaan kebijakan manajemen risiko stratejik secara berkala dan memastikan tindak lanjut hasil evaluasi dimaksud?',
                185: 'Apakah Direksi telah menyusun kebijakan manajemen risiko stratejik, melaksanakan secara konsisten, dan melakukan pengkinian secara berkala?',
                186: 'Apakah Direksi telah memiliki kemampuan untuk mengambil tindakan yang diperlukan dalam rangka mitigasi risiko stratejik, dan melakukan komunikasi kebijakan manajemen risiko stratejik terhadap seluruh jenjang organisasi BPR?',
                187: 'Apakah BPR telah memiliki kecukupan organisasi yang menangani fungsi stratejik dan fungsi manajemen risiko stratejik?',
                188: 'Apakah Direksi telah menerapkan kebijakan pengelolaan SDM dalam rangka penerapan Manajemen Risiko stratejik?',
                189: 'Pilar Kecukupan Kebijakan, Prosedur, dan Limit',
                190: 'Apakah BPR telah memiliki kebijakan manajemen risiko stratejik yang memadai dan disusun dengan mempertimbangkan visi, misi, skala usaha dan kompleksitas bisnis, serta kecukupan SDM?',
                191: 'Apakah BPR: \n● Memiliki prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik yang ditetapkan oleh Direksi\n ● Melaksanakan prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik secara konsisten untuk seluruh aktivitas; dan \n● Melakukan evaluasi dan pengkinian terhadap prosedur manajemen risiko stratejik dan penetapan limit risiko stratejik secara berkala?',
                192: 'Apakah BPR telah memiliki kebijakan dan prosedur penerbitan produk dan/atau pelaksanaan aktivitas baru yang mencakup identifikasi dan mitigasi risiko stratejik sesuai dengan ketentuan?',
                193: 'Kecukupan Proses dan Sistem Manajemen Informasi',
                194: 'Apakah BPR telah melaksanakan proses manajemen risiko stratejik yang melekat pada kegiatan usaha BPR?',
                195: 'Apakah BPR telah memiliki sistem informasi manajemen risiko yang mendukung Direksi dalam pengambilan keputusan terkait risiko stratejik serta telah dilaporkan kepada Direksi secara berkala?',
                196: 'Sistem Pengendalian Internal yang Menyeluruh',
                197: 'Apakah SKAI atau PEAI telah melaksanakan audit secara berkala terhadap penerapan manajemen risiko stratejik, menyampaikan laporan hasil audit intern, dan memastikan tindaklanjut atas temuan pemeriksaan?',
                198: 'Apakah sistem pengendalian intern terhadap risiko stratejik telah dilaksanakan oleh seluruh jenjang organisasi BPR?',
                199: 'Penilaian Risiko KPMR',
                200: 'Penilaian Risiko KPMR Periode Sebelumnya'
            };
            return names[faktorId] || 'Parameter';
        }

        // 7. LAPORAN PROFIL RISIKO
        async function generateLaporanProfilRisikoPDF(data) {
            const pdfDoc = await PDFDocument.create();
            const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
            const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

            // Safe draw text helper
            const safeDrawText = (page, text, options) => {
                page.drawText(sanitizeText(text || ''), options);
            };

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
                if (logoImage) DOC_LOGO.set(pdfDoc, logoImage);
                DOC_FOOT.set(pdfDoc, 'Laporan Profil Risiko · ' + (data.bpr.namabpr || ''));
                return drawKop(page, fontBold, fontRegular, data.bpr, { margin, top: height - yStart }) - 22;
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

            // Helper function untuk format rupiah
            function formatRupiah(angka) {
                if (!angka) return 'Rp 0';
                const number = String(angka).replace(/[^\d]/g, '');
                const formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return `Rp ${formatted}`;
            }

            function getAtmDebitLabel(value) {
                const v = String(value).trim();
                if (v === '1') return 'Ya';
                if (v === '2') return 'Tidak';
                return '-';
            }

            // HALAMAN PERTAMA - PROFIL RISIKO
            let page = pdfDoc.addPage([595, 842]);
            let yPos = drawPageHeader(page, height - margin);

            // Judul
            const judul = 'LAPORAN PROFIL RISIKO';
            const judulWidth = fontBold.widthOfTextAtSize(judul, 14);
            safeDrawText(page, judul, {
                x: centerX - (judulWidth / 2),
                y: yPos,
                size: 14,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            yPos -= 25;

            // A. PROFIL RISIKO
            safeDrawText(page, 'A. PROFIL RISIKO', {
                x: margin,
                y: yPos,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            yPos -= 20;

            // Data profil
            const profilData = [
                ['Periode', `Semester ${sanitizeText(data.periode.semester || '')} Tahun ${sanitizeText(data.periode.tahun || '')}`],
                ['Nama BPR', sanitizeText(data.bpr.namabpr || '')],
                ['Alamat', sanitizeText(data.bpr.alamat || '')],
                ['Nomor Telepon', sanitizeText(data.bpr.nomor || '')],
                ['Modal Inti', formatRupiah(data.periode.modalinti)],
                ['Total Aset', formatRupiah(data.periode.totalaset)],
                ['Jumlah Kantor Cabang', sanitizeText(data.periode.kantorcabang || '')],
                ['Kegiatan sebagai penerbit kartu ATM atau kartu debit', getAtmDebitLabel(data.periode.atmdebit)]
            ];

            profilData.forEach(([label, value]) => {
                safeDrawText(page, label, {
                    x: margin + 10,
                    y: yPos,
                    size: 8.5,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });

                safeDrawText(page, ':', {
                    x: margin + 220,
                    y: yPos,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });

                if (label === 'Alamat') {
                    const valueLines = wrapTextByCharLimit(sanitizeText(value), 60);
                    valueLines.forEach((line, idx) => {
                        safeDrawText(page, line, {
                            x: margin + 230,
                            y: yPos - (idx * 10),
                            size: 9,
                            font: fontRegular,
                            color: rgb(0, 0, 0)
                        });
                    });
                    yPos -= (valueLines.length * 10) + 5;
                } else {
                    safeDrawText(page, value, {
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
            safeDrawText(page, 'Penilaian Per Posisi', {
                x: tableStartX + colWidths[0] + 63,
                y: yPos - 17,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            safeDrawText(page, `Penilaian Posisi Sebelumnya`, {
                x: tableStartX + colWidths[0] + colWidths[1] + colWidths[2] + colWidths[3] + 40,
                y: yPos - 13,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            safeDrawText(page, `(Semester ${sanitizeText(data.periode.periodeSebelumSemester || '')} Tahun ${sanitizeText(data.periode.periodeSebelumTahun || '')})`, {
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
                    const textWidth = fontBold.widthOfTextAtSize(sanitizeText(line), 7);
                    safeDrawText(page, line, {
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

                const risikoText = sanitizeText(rType.name);
                const textX = currentX + 5;
                const textY = yPos - (rowHeight / 2) - 3;

                safeDrawText(page, risikoText, {
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
                        const text = sanitizeText(item.val.toString());
                        const valTextWidth = fontBold.widthOfTextAtSize(text, 10);
                        const valTextX = currentX + (colWidths[idx + 1] - valTextWidth) / 2;
                        const valTextY = yPos - (rowHeight / 2) - 3;

                        safeDrawText(page, text, {
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

            safeDrawText(page, 'Peringkat Risiko', {
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
                const text = sanitizeText(data.risiko.peringkat.current.toString());
                const textWidth = fontBold.widthOfTextAtSize(text, 11);
                safeDrawText(page, text, {
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
                const text = sanitizeText(data.risiko.peringkat.previous.toString());
                const textWidth = fontBold.widthOfTextAtSize(text, 11);
                safeDrawText(page, text, {
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
            const ketWidth = fontRegular.widthOfTextAtSize(sanitizeText(keterangan), 9);
            safeDrawText(page, keterangan, {
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

            safeDrawText(page, 'Analisis', {
                x: margin + 10,
                y: yPos - 15,
                size: 10,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            yPos -= 30;

            const defaultIntro = `Hasil dari analisis profil risiko Semester ${sanitizeText(data.periode.semester || '')} Tahun ${sanitizeText(data.periode.tahun || '')} sebagai berikut:`;
            const analisisIntro = sanitizeText(data.analisis.intro || defaultIntro);
            const introLines = wrapTextByCharLimit(analisisIntro, 75);
            introLines.forEach(line => {
                safeDrawText(page, line, {
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
                sanitizeText(data.analisis.kredit || ''),
                sanitizeText(data.analisis.operasional || ''),
                sanitizeText(data.analisis.kepatuhan || ''),
                sanitizeText(data.analisis.likuiditas || '')
            ];

            if (data.analisis.reputasi) analisisList.push(sanitizeText(data.analisis.reputasi));
            if (data.analisis.stratejik) analisisList.push(sanitizeText(data.analisis.stratejik));

            analisisList.forEach(item => {
                safeDrawText(page, '•', {
                    x: margin + 15,
                    y: yPos,
                    size: 8,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                const itemLines = wrapTextByCharLimit(item, 180);
                itemLines.forEach((line, idx) => {
                    safeDrawText(page, line, {
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

                const paragraphs = sanitizeText(data.kesimpulan)
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

                safeDrawText(page, 'Kesimpulan', {
                    x: margin + 5,
                    y: yPos - 15,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });

                yPos -= 30;

                wrappedParagraphs.forEach(lines => {
                    lines.forEach(line => {
                        safeDrawText(page, line, {
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
                    safeDrawText(page, 'B. ANALISIS PER JENIS RISIKO', {
                        x: margin,
                        y: yPos,
                        size: 11,
                        font: fontBold,
                        color: rgb(0, 0, 0)
                    });
                    yPos -= 30;
                }

                safeDrawText(page, risiko.title, {
                    x: margin + 10,
                    y: yPos,
                    size: 11,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 20;

                safeDrawText(page, `Nama BPR`, {
                    x: margin + 10,
                    y: yPos,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });
                safeDrawText(page, `: ${sanitizeText(data.bpr.namabpr || '')}`, {
                    x: margin + 80,
                    y: yPos,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });
                yPos -= 12;

                safeDrawText(page, `Periode`, {
                    x: margin + 10,
                    y: yPos,
                    size: 9,
                    font: fontRegular,
                    color: rgb(0, 0, 0)
                });
                safeDrawText(page, `: Semester ${sanitizeText(data.periode.semester || '')} Tahun ${sanitizeText(data.periode.tahun || '')}`, {
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

                safeDrawText(page, 'Analisis', {
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

                safeDrawText(page, `1. Tingkat Risiko:`, {
                    x: margin + 20,
                    y: yPos,
                    size: 10,
                    font: fontBold,
                    color: rgb(0, 0, 0)
                });
                yPos -= 12;

                safeDrawText(page, `   Peringkat ${sanitizeText(tingkatRisiko.toString())}, ${sanitizeText(tingkatRisikoText)}`, {
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

                safeDrawText(page, `2. Risiko Inheren:`, {
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
                        const keteranganText = `   ${idx + 1}. ${sanitizeText(item.keterangan || '')}`;
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

                            safeDrawText(page, line, {
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
                    const defaultText = `   Tingkat Risiko Inheren: ${sanitizeText((risikoData.inherenCurrent || '-').toString())} (${getRiskText(risikoData.inherenCurrent)})`;

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

                    safeDrawText(page, defaultText, {
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

                safeDrawText(page, '3. Kualitas Penerapan Manajemen Risiko:', {
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
                        const keteranganText = `   ${idx + 1}. ${sanitizeText(item.keterangan || '')}`;
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

                            safeDrawText(page, line, {
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
                    const defaultText = `   Tingkat KPMR: ${sanitizeText((risikoData.kpmrCurrent || '-').toString())} (${getRiskText(risikoData.kpmrCurrent)})`;

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

                    safeDrawText(page, defaultText, {
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

            // LEMBAR TANDA TANGAN
            page = pdfDoc.addPage([595, 842]);
            yPos = drawPageHeader(page, height - margin);
            yPos -= 60;

            const lokasiTanggal = `${sanitizeText(data.profil.lokasi || '')}, ${sanitizeText(data.profil.tanggal || '-')}`;
            const lokasiWidth = fontRegular.widthOfTextAtSize(lokasiTanggal, 11);
            safeDrawText(page, lokasiTanggal, {
                x: centerX - (lokasiWidth / 2),
                y: yPos,
                size: 11,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });
            yPos -= 18;

            const namaBprText = sanitizeText(data.bpr.namabpr || 'PT BPR NBP');
            const namaBprWidth = fontBold.widthOfTextAtSize(namaBprText, 11);
            safeDrawText(page, namaBprText, {
                x: centerX - (namaBprWidth / 2),
                y: yPos,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            yPos -= 30;

            const menyetujuiText = 'Menyetujui';
            const menyetujuiWidth = fontBold.widthOfTextAtSize(menyetujuiText, 11);
            safeDrawText(page, menyetujuiText, {
                x: centerX - (menyetujuiWidth / 2),
                y: yPos,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });
            yPos -= 70;

            // Titik tengah tiap kolom, simetris terhadap centerX
            const colOffset = 110;
            const leftColX = centerX - colOffset;
            const rightColX = centerX + colOffset;

            // --- Direktur Utama (kiri) ---
            const namaDirut = sanitizeText(data.profil.dirut || 'Nama Direktur Utama');
            const namaDirutWidth = fontBold.widthOfTextAtSize(namaDirut, 11);
            const namaDirutX = leftColX - (namaDirutWidth / 2);

            safeDrawText(page, namaDirut, {
                x: namaDirutX,
                y: yPos,
                size: 11,
                font: fontBold,
                color: rgb(0, 0, 0)
            });

            page.drawLine({
                start: { x: namaDirutX, y: yPos - 3 },
                end: { x: namaDirutX + namaDirutWidth, y: yPos - 3 },
                thickness: 1,
                color: rgb(0, 0, 0)
            });

            // --- Direktur (kanan) ---
            const namaDir = sanitizeText(data.profil.dirkep || 'Nama Direktur');
            const namaDirWidth = fontBold.widthOfTextAtSize(namaDir, 11);
            const namaDirX = rightColX - (namaDirWidth / 2);

            safeDrawText(page, namaDir, {
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

            // --- Jabatan, center-align terhadap titik tengah kolom masing-masing ---
            const jabatanDirut = 'Direktur Utama';
            const jabatanDirutWidth = fontRegular.widthOfTextAtSize(jabatanDirut, 10);
            safeDrawText(page, jabatanDirut, {
                x: leftColX - (jabatanDirutWidth / 2),
                y: yPos,
                size: 10,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            const jabatan = 'Direktur';
            const jabatanWidth = fontRegular.widthOfTextAtSize(jabatan, 10);
            safeDrawText(page, jabatan, {
                x: rightColX - (jabatanWidth / 2),
                y: yPos,
                size: 10,
                font: fontRegular,
                color: rgb(0, 0, 0)
            });

            addPageNumbers(pdfDoc, fontRegular);

            const pdfBytes = await pdfDoc.save();
            return pdfBytes;
        }

        // MAIN GENERATION FUNCTION
        async function generateAllPDFsToZip() {
            try {
                updateProgress(5, 'Mempersiapkan ZIP...');

                // Fetch data periode (sudah ada endpoint ini, sekarang include kategori) ──
                const periodeResponse = await fetch(`${BASE_URL}/Showprofilresiko/exportLembarPernyataanJSON`);
                const periodeResult = await periodeResponse.json();

                if (periodeResult.status !== 'success') {
                    throw new Error('Gagal mengambil data periode: ' + (periodeResult.message || ''));
                }

                const semester = periodeResult.data.periode.semester;
                const tahun = periodeResult.data.periode.tahun;

                // Baca kategori — setelah fix PHP, field ini sudah tersedia
                const kategori = String(periodeResult.data.periode.kategori ?? 'B').trim().toUpperCase();
                const isKategoriA = kategori === 'A';

                updateProgress(10, `Kategori: ${kategori} | Jumlah PDF: ${isKategoriA ? 7 : 5}`);

                const zip = new JSZip();
                const folder = zip.folder(`Laporan Profil Risiko Semester ${semester} Tahun ${tahun}`);

                // 5 file yang SELALU digenerate
                const baseFiles = [
                    {
                        name: '00. Lembar Pernyataan Profil Risiko.pdf',
                        endpoint: 'Showprofilresiko/exportLembarPernyataanJSON',
                        generator: generateLembarPernyataanPDF,
                        progress: 18
                    },
                    {
                        name: '01. Laporan Profil Risiko Kredit.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganKreditJSON',
                        generator: generateRisikoKreditPDF,
                        progress: 30
                    },
                    {
                        name: '02. Laporan Profil Risiko Operasional.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganOperasionalJSON',
                        generator: generateRisikoOperasionalPDF,
                        progress: 42
                    },
                    {
                        name: '03. Laporan Profil Risiko Kepatuhan.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganKepatuhanJSON',
                        generator: generateRisikoKepatuhanPDF,
                        progress: 54
                    },
                    {
                        name: '04. Laporan Profil Risiko Likuiditas.pdf',
                        endpoint: 'Showprofilresiko/exportPDFGabunganLikuiditasJSON',
                        generator: generateRisikoLikuiditasPDF,
                        progress: 66
                    }
                ];

                // 2 file HANYA untuk kategori A
                const kategoriAFiles = isKategoriA ? [
                    {
                        name: '05. Laporan Profil Risiko Reputasi.pdf',
                        endpoint: 'Risikokredit/exportPDFGabunganReputasi',
                        generator: generatePDFReputasiBytes,
                        progress: 76
                    },
                    {
                        name: '06. Laporan Profil Risiko Stratejik.pdf',
                        endpoint: 'Risikokredit/exportPDFGabunganStratejik',
                        generator: generatePDFStratejikBytes,
                        progress: 86
                    }
                ] : [];

                // File terakhir selalu ada, nomornya menyesuaikan
                const lastFile = {
                    name: isKategoriA ? '07. Laporan Profil Risiko.pdf'
                        : '05. Laporan Profil Risiko.pdf',
                    endpoint: 'Showprofilresiko/exportLaporanProfilRisikoJSON',
                    generator: generateLaporanProfilRisikoPDF,
                    progress: 95
                };

                const files = [...baseFiles, ...kategoriAFiles, lastFile];

                let successCount = 0;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    try {
                        addFileToList(file.name, 'processing');
                        notifyParent({ ev: 'file', name: file.name });
                        updateProgress(file.progress, `Membuat ${file.name}...`);

                        const response = await fetch(`${BASE_URL}/${file.endpoint}`, {
                            method: 'GET',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'same-origin'
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const result = await response.json();

                        if (result.status !== 'success') {
                            throw new Error(result.message || 'Gagal mengambil data');
                        }

                        const fullBpr = periodeResult.data.bpr || {};
                        if (result.data) result.data.bpr = Object.assign({}, fullBpr, result.data.bpr || {}, { logo: (result.data.bpr && result.data.bpr.logo) || fullBpr.logo });
                        const pdfBytes = await file.generator(result.data);
                        folder.file(file.name, pdfBytes);

                        const items = document.querySelectorAll('.file-list-item');
                        items[items.length - 1].className = 'file-list-item success';
                        items[items.length - 1].innerHTML =
                            `<i class="fas fa-check-circle"></i> <span>${file.name}</span>`;

                        successCount++;

                    } catch (error) {
                        console.error(`Error generating ${file.name}:`, error);
                        notifyParent({ ev: 'file-error', name: file.name, text: error.message });
                        const items = document.querySelectorAll('.file-list-item');
                        items[items.length - 1].className = 'file-list-item error';
                        items[items.length - 1].innerHTML =
                            `<i class="fas fa-times-circle"></i> <span>${file.name} — ${error.message}</span>`;
                    }
                }

                updateProgress(97, 'Mengompres file ke ZIP...');

                const zipBlob = await zip.generateAsync({
                    type: 'blob',
                    compression: 'DEFLATE',
                    compressionOptions: { level: 6 }
                });

                updateProgress(100, `Selesai! ${successCount} dari ${files.length} file berhasil dibuat.`);

                const zipName = `Laporan Profil Risiko Semester ${semester} Tahun ${tahun}.zip`;
                if (EMBED) {
                    notifyParent({ ev: 'done', blob: zipBlob, name: zipName, ok: successCount, total: files.length });
                    return;
                }
                const link = document.createElement('a');
                link.href = URL.createObjectURL(zipBlob);
                link.download = zipName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                setTimeout(() => {
                    showSuccess(
                        `Download selesai! ${successCount} dari ${files.length} file PDF ` +
                        `berhasil dikumpulkan dalam ZIP.`
                    );
                }, 1000);

            } catch (error) {
                console.error('Error:', error);
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        window.onload = () => {
            generateAllPDFsToZip();
        };
    </script>
</body>

</html>