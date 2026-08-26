<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Profil Risiko</title>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <style>
        /* Copy semua style dari document index 2 yang Anda berikan */
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
        const BASE_URL = '<?= $baseUrl ?>';

        // Copy semua JavaScript dari document index 2 yang Anda berikan
        // (fungsi generateLaporanProfilRisiko dan helper functions)

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

        // Copy semua fungsi lainnya...

        window.onload = () => {
            generateLaporanProfilRisiko();
        };
    </script>
</body>

</html>