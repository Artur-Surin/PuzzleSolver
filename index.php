<?php
require_once __DIR__ . '/PuzzleSolver.php';

// API Handler for AJAX requests
if (isset($_GET['api']) && $_GET['api'] === 'solve') {
    header('Content-Type: application/json; charset=utf-8');
    set_time_limit(0); // дозволяємо необмежений час виконання — алгоритм детермінований
    
    $rawInput = '';
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $rawInput = file_get_contents($_FILES['file']['tmp_name']);
    } elseif (isset($_POST['fragments_text'])) {
        $rawInput = $_POST['fragments_text'];
    }

    if (empty(trim($rawInput))) {
        echo json_encode([
            'success' => false,
            'error' => 'Будь ласка, завантажте файл fragments.txt або введіть фрагменти у поле.'
        ]);
        exit;
    }

    $rawFragments = preg_split('/\s+/', trim($rawInput));
    $solver = new PuzzleSolver($rawFragments);
    $result = $solver->solve();

    echo json_encode($result);
    exit;
}

// Handler for loading default sample file
if (isset($_GET['api']) && $_GET['api'] === 'get_sample') {
    header('Content-Type: application/json; charset=utf-8');
    $samplePath = __DIR__ . '/fragments.txt';
    if (file_exists($samplePath)) {
        echo json_encode([
            'success' => true,
            'text' => file_get_contents($samplePath)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Файл fragments.txt не знайдено на сервері.'
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Цифровий Пазл - Пошук найдовшої послідовності (PHP)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(23, 32, 54, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-cyan: #38bdf8;
            --accent-purple: #c084fc;
            --accent-green: #4ade80;
            --accent-pink: #f472b6;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --font-family: 'Outfit', sans-serif;
            --font-mono: 'Fira Code', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(56, 189, 248, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(192, 132, 252, 0.12) 0%, transparent 40%);
            color: var(--text-main);
            font-family: var(--font-family);
            min-height: 100vh;
            padding: 2rem 1rem;
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(192, 132, 252, 0.2));
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: var(--accent-cyan);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, var(--accent-cyan) 50%, var(--accent-purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 650px;
            margin: 0 auto;
        }

        .glass-panel {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 1.25rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .input-grid {
                grid-template-columns: 1fr;
            }
            h1 { font-size: 2rem; }
        }

        .drop-zone {
            border: 2px dashed rgba(56, 189, 248, 0.3);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(15, 23, 42, 0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .drop-zone:hover, .drop-zone.dragover {
            border-color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.05);
            transform: translateY(-2px);
        }

        .drop-zone-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        textarea {
            width: 100%;
            height: 180px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            color: var(--text-main);
            font-family: var(--font-mono);
            font-size: 0.9rem;
            padding: 1rem;
            resize: vertical;
            outline: none;
            transition: border-color 0.3s;
        }

        textarea:focus {
            border-color: var(--accent-cyan);
        }

        .action-bar {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.85rem 1.75rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-cyan), #0284c7);
            color: #0f172a;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--card-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* Loading Spinner */
        .loading-overlay {
            display: none;
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(56, 189, 248, 0.1);
            border-left-color: var(--accent-cyan);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-cyan);
        }

        .stat-value.purple { color: var(--accent-purple); }
        .stat-value.green { color: var(--accent-green); }
        .stat-value.pink { color: var(--accent-pink); }

        /* Results Display */
        .results-section {
            display: none;
        }

        .result-box {
            background: #070a12;
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            padding: 1.5rem;
            font-family: var(--font-mono);
            font-size: 1.05rem;
            word-break: break-all;
            color: var(--accent-green);
            line-height: 1.8;
            max-height: 250px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.5);
        }

        /* Chain Visualization */
        .chain-visualizer {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            max-height: 350px;
            overflow-y: auto;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 1rem;
            border: 1px solid var(--card-border);
        }

        .chain-item {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 0.4rem 0.75rem;
            font-family: var(--font-mono);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .chain-item .overlap {
            color: var(--accent-pink);
            font-weight: bold;
            text-decoration: underline;
        }

        .chain-arrow {
            color: var(--text-muted);
            margin: 0 0.25rem;
        }

        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--accent-green);
            color: #0f172a;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(74, 222, 128, 0.4);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        footer {
            text-align: center;
            margin-top: 3rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        footer a {
            color: var(--accent-cyan);
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="badge">⚡ PHP 8.3 Digital Puzzle Engine</div>
            <h1>Цифровий Пазл - Solver</h1>
            <p class="subtitle">Пошук найдовшої послідовності 6-значних цифрових фрагментів із перекриттям по перших та останніх 2 цифрах.</p>
        </header>

        <div class="glass-panel">
            <form id="puzzleForm">
                <div class="input-grid">
                    <div class="drop-zone" id="dropZone">
                        <div class="drop-zone-icon">📁</div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Завантажити файл fragments.txt</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Перетягніть файл сюди або натисніть для вибору</div>
                        <input type="file" id="fileInput" accept=".txt" style="display: none;">
                    </div>
                    <div>
                        <textarea id="fragmentsText" placeholder="Або вставте фрагменти через пробіл/перенесення (наприклад: 608017 248460 177092)..."></textarea>
                    </div>
                </div>

                <div class="action-bar">
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" id="btnSample">
                            📥 Завантажити фрагменти (142 шт)
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnClear">
                            🗑️ Очистити
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnSolve">
                        🧩 Обчислити найдовший пазл
                    </button>
                </div>
            </form>
        </div>

        <!-- Loading Overlay -->
        <div class="glass-panel loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <h3 style="margin-bottom: 0.5rem;">Виконується обчислення...</h3>
            <p style="color: var(--text-muted);">Аналіз графа зв'язності та ейлерових степенів...</p>
        </div>

        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Макс. фрагментів</span>
                    <span class="stat-value" id="statFragments">0</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Загальна к-сть цифр</span>
                    <span class="stat-value purple" id="statDigits">0</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Час виконання</span>
                    <span class="stat-value green" id="statTime">0 ms</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Охоплення вхідних даних</span>
                    <span class="stat-value pink" id="statCoverage">0%</span>
                </div>
            </div>

            <div class="glass-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.25rem;">Результат (Цифровий рядок):</h3>
                    <div class="btn-group">
                        <button class="btn btn-secondary" id="btnCopy">📋 Копіювати</button>
                        <button class="btn btn-secondary" id="btnDownload">💾 Завантажити .txt</button>
                    </div>
                </div>
                <div class="result-box" id="resultStringBox"></div>

                <h3 style="font-size: 1.1rem; margin-top: 1.5rem; margin-bottom: 1rem;">Ланцюжок з'єднання фрагментів:</h3>
                <div class="chain-visualizer" id="chainVisualizer"></div>
            </div>
        </div>

        <footer>
            Створено на PHP | Посилання для поширення: скопіюйте URL даної сторінки
        </footer>
    </div>

    <div class="toast" id="toast">Результат успішно скопійовано!</div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fragmentsText = document.getElementById('fragmentsText');
        const puzzleForm = document.getElementById('puzzleForm');
        const btnSample = document.getElementById('btnSample');
        const btnClear = document.getElementById('btnClear');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const resultsSection = document.getElementById('resultsSection');
        const toast = document.getElementById('toast');

        // Drag & Drop
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateDropZoneText(e.dataTransfer.files[0].name);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                updateDropZoneText(fileInput.files[0].name);
            }
        });

        function updateDropZoneText(filename) {
            dropZone.querySelector('.drop-zone-icon').textContent = '📄';
            dropZone.querySelector('div:nth-child(2)').textContent = filename;
        }

        // Sample Load
        btnSample.addEventListener('click', async () => {
            try {
                const res = await fetch('index.php?api=get_sample');
                const data = await res.json();
                if (data.success) {
                    fragmentsText.value = data.text;
                    showToast('Стандартний файл на 142 фрагменти завантажено!');
                } else {
                    alert(data.error);
                }
            } catch (err) {
                alert('Помилка при завантаженні шаблону: ' + err.message);
            }
        });

        // Clear
        btnClear.addEventListener('click', () => {
            fragmentsText.value = '';
            fileInput.value = '';
            dropZone.querySelector('.drop-zone-icon').textContent = '📁';
            dropZone.querySelector('div:nth-child(2)').textContent = 'Завантажити файл fragments.txt';
            resultsSection.style.display = 'none';
        });

        // Form Submit
        puzzleForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();

            if (fileInput.files.length) {
                formData.append('file', fileInput.files[0]);
            } else {
                formData.append('fragments_text', fragmentsText.value);
            }

            loadingOverlay.style.display = 'block';
            resultsSection.style.display = 'none';

            try {
                const response = await fetch('index.php?api=solve', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                loadingOverlay.style.display = 'none';

                if (data.success) {
                    renderResults(data);
                } else {
                    alert(data.error);
                }
            } catch (err) {
                loadingOverlay.style.display = 'none';
                alert('Помилка виконання: ' + err.message);
            }
        });

        let currentResultString = '';

        function renderResults(data) {
            currentResultString = data.puzzle_string;

            document.getElementById('statFragments').textContent = data.max_fragments;
            document.getElementById('statDigits').textContent = data.total_digits;
            document.getElementById('statTime').textContent = data.execution_time_ms + ' ms';
            
            const coverage = ((data.max_fragments / data.total_input_fragments) * 100).toFixed(1);
            document.getElementById('statCoverage').textContent = coverage + '%';

            document.getElementById('resultStringBox').textContent = data.puzzle_string;

            // Render Chain
            const visualizer = document.getElementById('chainVisualizer');
            visualizer.innerHTML = '';

            const frags = data.ordered_fragments;
            frags.forEach((f, idx) => {
                const item = document.createElement('div');
                item.className = 'chain-item';

                const pref = f.slice(0, 2);
                const mid = f.slice(2, 4);
                const suff = f.slice(4, 6);

                item.innerHTML = `<span class="overlap">${pref}</span>${mid}<span class="overlap">${suff}</span>`;
                visualizer.appendChild(item);

                if (idx < frags.length - 1) {
                    const arrow = document.createElement('span');
                    arrow.className = 'chain-arrow';
                    arrow.textContent = '➔';
                    visualizer.appendChild(arrow);
                }
            });

            resultsSection.style.display = 'block';
            resultsSection.scrollIntoView({ behavior: 'smooth' });
        }

        // Copy & Download
        document.getElementById('btnCopy').addEventListener('click', () => {
            navigator.clipboard.writeText(currentResultString);
            showToast('Результат успішно скопійовано!');
        });

        document.getElementById('btnDownload').addEventListener('click', () => {
            const blob = new Blob([currentResultString], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'puzzle_result.txt';
            a.click();
            URL.revokeObjectURL(url);
        });

        function showToast(msg) {
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
