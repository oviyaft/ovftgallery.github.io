<?php require_once 'auth.php'; ?>
<?php
$imageDir = 'images/';
$allowed = ['jpg','jpeg','png','gif','webp'];
$perPage = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Sort by modification time (NEWEST FIRST)
$allImages = array_filter(glob($imageDir . '*.*'), function($file) use ($allowed) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($ext, $allowed);
});
usort($allImages, function($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

$images = array_slice($allImages, $offset, $perPage);
$totalPages = ceil(count($allImages) / $perPage);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinterest Clone</title>
    <style>
        :root {
            --bg: #ffffff; --card: #f9f9f9; --text: #111111;
            --shadow: rgba(0,0,0,0.1); --accent: #4CAF50;
        }
        [data-theme="dark"] {
            --bg: #121212; --card: #1e1e1e; --text: #e0e0e0;
            --shadow: rgba(0,0,0,0.3); --accent: #66BB6A;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg); color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 20px; transition: background 0.3s ease;
            overflow-x: hidden;
        }
        .theme-toggle, .logout-btn {
            position: fixed; top: 20px; right: 20px;
            background: var(--card); border: none;
            padding: 12px 16px; border-radius: 50px;
            cursor: pointer; box-shadow: 0 2px 10px var(--shadow);
            z-index: 100; font-weight: 600; transition: all 0.3s;
            text-decoration: none; color: var(--text); display: inline-block;
        }
        .logout-btn { right: 140px; background: #f44336; color: white; }
        .theme-toggle:hover, .logout-btn:hover { transform: scale(1.05); }
        .upload-btn {
            position: fixed; bottom: 30px; left: 30px;
            background: var(--accent); color: white; border: none;
            padding: 14px 28px; border-radius: 50px; font-size: 1.1rem;
            font-weight: 600; cursor: pointer; box-shadow: 0 6px 20px rgba(76,175,80,0.4);
            display: flex; align-items: center; gap: 10px; z-index: 99;
            transition: all 0.3s ease;
        }
        .upload-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(76,175,80,0.5); }
        .upload-btn svg { width: 20px; height: 20px; }
        #uploadModal {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.85); z-index: 1000;
            align-items: center; justify-content: center;
            backdrop-filter: blur(8px); padding: 16px;
        }
        .modal-content {
            background: var(--card); padding: 24px;
            border-radius: 16px; width: 100%; max-width: 500px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 15px 40px var(--shadow);
        }
        .drop-zone {
            border: 3px dashed #888; border-radius: 12px;
            padding: 40px; text-align: center; margin: 16px 0;
            transition: all 0.3s; cursor: pointer; color: #333; background: #fdfdfd;
        }
        [data-theme="dark"] .drop-zone { border-color: #aaa; color: #ddd; background: #222; }
        .drop-zone.dragover { background: var(--accent)!important; color: white!important; border-color: transparent!important; }
        .preview-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px; margin: 16px 0; max-height: 200px; overflow-y: auto;
        }
        .preview-item {
            position: relative; border-radius: 8px; overflow: hidden;
            box-shadow: 0 2px 8px var(--shadow); border: 1px solid #ddd;
        }
        [data-theme="dark"] .preview-item { border-color: #444; }
        .preview-item img { width: 100%; height: 80px; object-fit: cover; }
        .remove-preview {
            position: absolute; top: 4px; right: 4px;
            background: rgba(0,0,0,0.7); color: white; border: none;
            width: 24px; height: 24px; border-radius: 50%; cursor: pointer;
            font-size: 0.8rem;
        }
        .btn {
            background: var(--accent); color: white; border: none;
            padding: 10px 20px; border-radius: 50px; cursor: pointer;
            font-weight: 600; margin: 8px 4px; transition: 0.3s;
        }
        .btn:hover { transform: scale(1.05); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.cancel { background: #999; }

        /* MODERN PROGRESS BAR */
        .progress-wrapper { margin: 20px 0 12px; display: none; }
        .progress-bar-container {
            background: rgba(255,255,255,0.15); border-radius: 12px;
            height: 10px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
            backdrop-filter: blur(4px);
        }
        .progress-bar-fill {
            height: 100%; width: 0%; background: linear-gradient(90deg, var(--accent), #81C784);
            border-radius: 12px; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
        }
        .progress-bar-fill::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 1.5s infinite; border-radius: 12px;
        }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        .progress-text {
            text-align: center; font-size: 0.9rem; font-weight: 600;
            margin-top: 8px; color: var(--text); opacity: 0.9;
        }

        #status { margin-top: 12px; font-size: 0.9em; color: #d32f2f; min-height: 20px; }
        .container {
            max-width: 1200px; margin: 80px auto 40px;
            column-count: 2; column-gap: 16px; padding: 0 8px;
        }
        @media (min-width: 600px) { .container { column-count: 3; } }
        @media (min-width: 900px) { .container { column-count: 4; } }
        @media (min-width: 1200px) { .container { column-count: 5; } }
        .pin {
            break-inside: avoid; margin-bottom: 16px;
            background: var(--card); border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 12px var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;
        }
        .pin:hover { transform: translateY(-4px); box-shadow: 0 8px 20px var(--shadow); }
        .pin img { width: 100%; height: auto; display: block; }
        #lightbox {
            display: none; position: fixed; inset: 0; background: #000;
            z-index: 1100; overflow: hidden; padding: 16px;
            align-items: center; justify-content: center; touch-action: pan-y;
        }
        #lightbox.active { display: flex; }
        #lightboxImg {
            max-width: 100%; max-height: 100%; width: auto; height: auto;
            object-fit: contain; border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }
        .lightbox-controls {
            position: absolute; bottom: 16px; left: 50%;
            transform: translateX(-50%); background: rgba(0,0,0,0.7);
            padding: 10px 16px; border-radius: 50px; display: flex; gap: 16px;
            backdrop-filter: blur(12px); z-index: 1101;
        }
        .lightbox-btn {
            background: transparent; border: none; color: white;
            font-size: 1.3rem; cursor: pointer; padding: 8px;
            transition: 0.2s; display: flex; align-items: center; justify-content: center;
        }
        .lightbox-btn:hover { transform: scale(1.2); }
        .nav-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(0,0,0,0.5); color: white; border: none;
            width: 48px; height: 48px; border-radius: 50%; font-size: 1.6rem;
            cursor: pointer; z-index: 1101; backdrop-filter: blur(10px);
            transition: 0.2s; display: flex; align-items: center; justify-content: center;
        }
        .nav-arrow:hover { background: rgba(0,0,0,0.8); transform: translateY(-50%) scale(1.1); }
        .prev-arrow { left: 16px; }
        .next-arrow { right: 16px; }
        .close-lightbox {
            position: absolute; top: 16px; right: 16px;
            background: rgba(0,0,0,0.5); color: white; border: none;
            width: 40px; height: 40px; border-radius: 50%; font-size: 1.4rem;
            cursor: pointer; z-index: 1101; backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center;
        }
        .close-lightbox:hover { background: rgba(0,0,0,0.8); }
        #loader {
            text-align: center; padding: 40px; color: var(--text); font-size: 1rem;
        }
        #loader svg { width: 40px; height: 40px; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 480px) {
            .lightbox-controls { bottom: 12px; padding: 8px 12px; gap: 12px; }
            .lightbox-btn { font-size: 1.1rem; }
            .nav-arrow { width: 40px; height: 40px; font-size: 1.4rem; }
            .upload-btn { bottom: 20px; left: 20px; padding: 12px 20px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <button id="themeBtn" class="theme-toggle">Dark Mode</button>
    <a href="logout.php" class="logout-btn">Logout</a>
    
    <button class="upload-btn" id="openUpload">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14m-7-7h14"/>
        </svg>
        Upload
    </button>

    <div id="uploadModal">
        <div class="modal-content">
            <h3>Upload Images</h3>
            <div class="drop-zone" id="dropZone">
                <p>Drop images here or click (multiple)</p>
                <input type="file" id="fileInput" accept="image/*" multiple hidden>
            </div>
            <div class="preview-grid" id="previewGrid"></div>
            <div>
                <button class="btn" id="uploadBtn" disabled>Upload All</button>
                <button class="btn cancel" id="cancelBtn">Cancel</button>
            </div>
            <!-- PROGRESS BAR -->
            <div class="progress-wrapper" id="progressWrapper">
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">0%</div>
            </div>
            <p id="status"></p>
        </div>
    </div>

    <div class="container" id="grid">
        <?php foreach ($images as $i => $src): ?>
            <div class="pin" data-index="<?= $i + $offset ?>">
                <img src="<?= htmlspecialchars($src) ?>" alt="Pin" loading="lazy">
            </div>
        <?php endforeach; ?>
    </div>

    <div id="loader" style="display:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" stroke-opacity="0.3"/>
            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
        </svg>
        Loading more...
    </div>

    <div id="lightbox">
        <button class="close-lightbox" id="closeBtn">×</button>
        <button class="nav-arrow prev-arrow" id="prevBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5m7-7l-7 7 7 7"/>
            </svg>
        </button>
        <button class="nav-arrow next-arrow" id="nextBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14m-7-7l7 7-7 7"/>
            </svg>
        </button>
        <img id="lightboxImg" src="" alt="">
        <div class="lightbox-controls">
            <button class="lightbox-btn" id="downloadBtn" title="Download">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </button>
        </div>
    </div>

<script>
    const images = <?= json_encode($allImages) ?>;
    let currentIndex = 0; let currentPage = <?= $page ?>; let isLoading = false;
    const perPage = <?= $perPage ?>; const totalPages = <?= $totalPages ?>;

    // ELEMENTS
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const closeBtn = document.getElementById('closeBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const downloadBtn = document.getElementById('downloadBtn');
    const loader = document.getElementById('loader');

    // THEME
    const html = document.documentElement;
    const themeBtn = document.getElementById('themeBtn');
    const saved = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', saved);
    themeBtn.textContent = saved === 'dark' ? 'Light Mode' : 'Dark Mode';
    themeBtn.onclick = () => {
        const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        themeBtn.textContent = next === 'dark' ? 'Light Mode' : 'Dark Mode';
    };

    // LIGHTBOX
    function openLightbox(i) {
        currentIndex = i; updateLightbox();
        lightbox.classList.add('active'); document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        lightbox.classList.remove('active'); document.body.style.overflow = '';
    }
    function changeImage(dir) {
        currentIndex = (currentIndex + dir + images.length) % images.length;
        updateLightbox();
    }
    function updateLightbox() {
        const path = images[currentIndex];
        lightboxImg.src = path + '?t=' + Date.now();
        downloadBtn.onclick = () => {
            const a = document.createElement('a');
            a.href = path; a.download = path.split('/').pop(); a.click();
        };
    }
    document.querySelectorAll('.pin').forEach(pin => {
        pin.addEventListener('click', () => openLightbox(parseInt(pin.dataset.index)));
    });
    closeBtn.onclick = closeLightbox;
    prevBtn.onclick = () => changeImage(-1);
    nextBtn.onclick = () => changeImage(1);
    document.addEventListener('keydown', e => {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') changeImage(-1);
        if (e.key === 'ArrowRight') changeImage(1);
    });
    let startX = 0;
    lightbox.addEventListener('touchstart', e => startX = e.touches[0].clientX, { passive: true });
    lightbox.addEventListener('touchend', e => {
        const delta = startX - e.changedTouches[0].clientX;
        if (Math.abs(delta) > 50) changeImage(delta > 0 ? 1 : -1);
    }, { passive: true });

    // INFINITE SCROLL
    function loadMore() {
        if (isLoading || currentPage >= totalPages) return;
        isLoading = true; loader.style.display = 'block'; currentPage++;
        fetch(`?page=${currentPage}`)
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newPins = doc.querySelectorAll('#grid .pin');
                newPins.forEach((pin, i) => {
                    const cloned = pin.cloneNode(true);
                    const globalIndex = (currentPage - 1) * perPage + i;
                    cloned.dataset.index = globalIndex;
                    cloned.addEventListener('click', () => openLightbox(globalIndex));
                    document.getElementById('grid').appendChild(cloned);
                });
                loader.style.display = 'none'; isLoading = false;
            })
            .catch(() => { loader.textContent = 'Error loading more'; isLoading = false; });
    }
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000) {
            loadMore();
        }
    });

    // UPLOAD WITH PROGRESS BAR (NO LIMIT)
    const modal = document.getElementById('uploadModal');
    const openBtn = document.getElementById('openUpload');
    const cancelBtn = document.getElementById('cancelBtn');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const previewGrid = document.getElementById('previewGrid');
    const uploadBtn = document.getElementById('uploadBtn');
    const status = document.getElementById('status');
    const progressWrapper = document.getElementById('progressWrapper');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    let selectedFiles = [];

    openBtn.onclick = () => modal.style.display = 'flex';
    cancelBtn.onclick = closeModal;
    dropZone.onclick = () => fileInput.click();
    fileInput.onchange = e => handleFiles(e.target.files);

    ['dragover', 'dragenter'].forEach(ev => dropZone.addEventListener(ev, e => {
        e.preventDefault(); dropZone.classList.add('dragover');
    }));
    ['dragleave', 'dragend', 'drop'].forEach(ev => dropZone.addEventListener(ev, e => {
        e.preventDefault(); if (ev !== 'drop') dropZone.classList.remove('dragover');
    }));
    dropZone.addEventListener('drop', e => {
        dropZone.classList.remove('dragover'); handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        [...files].forEach(file => {
            if (file.type.startsWith('image/') && file.size <= 10 * 1024 * 1024) {
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file); addPreview(file);
                }
            }
        });
        uploadBtn.disabled = selectedFiles.length === 0;
    }

    function addPreview(file) {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button class="remove-preview" onclick="removePreview(this, '${file.name}', ${file.size})">×</button>
            `;
            previewGrid.appendChild(div);
        };
        reader.readAsDataURL(file);
    }

    window.removePreview = (btn, name, size) => {
        selectedFiles = selectedFiles.filter(f => !(f.name === name && f.size === size));
        btn.parentElement.remove();
        uploadBtn.disabled = selectedFiles.length === 0;
    };

    // UPLOAD BUTTON – NO FILE LIMIT
    uploadBtn.onclick = () => {
        if (selectedFiles.length === 0) return;

        status.textContent = 'Preparing upload…';
        progressWrapper.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.textContent = '0%';
        uploadBtn.disabled = true;

        const xhr = new XMLHttpRequest();
        const form = new FormData();
        selectedFiles.forEach((file, i) => form.append(`image${i}`, file));

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = percent + '%';
            }
        });

        xhr.onload = () => {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success && data.paths) {
                        data.paths.forEach((path, i) => {
                            const cleanPath = path.replace(/\?t=\d+$/, '');
                            images.unshift(cleanPath);
                            addNewPin(cleanPath, i);
                        });

                        // FIX ALL PIN INDEXES
                        document.querySelectorAll('#grid .pin').forEach((pin, idx) => {
                            pin.dataset.index = idx;
                            pin.onclick = () => openLightbox(idx);
                        });

                        closeModal();
                        status.textContent = `Uploaded ${data.paths.length} image(s)!`;
                        setTimeout(() => status.textContent = '', 3000);
                    } else {
                        status.textContent = data.error || 'Upload failed';
                    }
                } catch (e) {
                    status.textContent = 'Invalid response';
                }
            } else {
                status.textContent = 'Server error: ' + xhr.status;
            }

            setTimeout(() => {
                progressWrapper.style.display = 'none';
                progressFill.style.width = '0%';
                progressText.textContent = '0%';
            }, 1500);
            uploadBtn.disabled = selectedFiles.length === 0;
        };

        xhr.onerror = () => {
            status.textContent = 'Network error';
            progressWrapper.style.display = 'none';
        };

        xhr.open('POST', 'upload.php');
        xhr.send(form);
    };

    function closeModal() {
        modal.style.display = 'none';
        previewGrid.innerHTML = '';
        selectedFiles = [];
        uploadBtn.disabled = true;
        status.textContent = '';
        fileInput.value = '';
        progressWrapper.style.display = 'none';
        progressFill.style.width = '0%';
        progressText.textContent = '0%';
    }

    function addNewPin(src, index) {
        const pin = document.createElement('div');
        pin.className = 'pin';
        pin.dataset.index = index;
        pin.innerHTML = `<img src="${src}" alt="Pin" loading="lazy">`;
        pin.addEventListener('click', () => openLightbox(index));
        document.getElementById('grid').insertBefore(pin, document.getElementById('grid').firstChild);
    }
</script>
</body>
</html>