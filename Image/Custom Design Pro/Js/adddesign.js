// ==========================
// Add Design System
// ==========================

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    console.log("🎨 adddesign.js loaded");

    const BASE_PATH = 'Images/AddDesign/';
    let currentPath = [];
    let designPanel = null;
    let resizePanel = null;
    let selectedDesign = null;
    let currentResizingDesign = null;
    let currentResizingLayer = null;
    let tutorialToastTimeout = null;
    const FALLBACK_ADD_DESIGN_TUTORIAL_URL = 'https://www.youtube.com/watch?v=qdXK13gTSr0';
    
    const addDesignBtn = document.querySelector('[data-tool="add-design"]');
    if (!addDesignBtn) {
        console.error("❌ Add Design button not found");
        return;
    }

    // Global drag state
    let isDragging = false;
    let dragElement = null;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;

    if (!window.cdpState) {
        window.cdpState = { currentView: "front" };
    }

    if (!window.layersByView) {
        window.layersByView = {
            front: [],
            back: [],
            left: [],
            right: []
        };
    }

    if (typeof window.cdpAddDesignTutorialUrl !== 'string' || !window.cdpAddDesignTutorialUrl.trim()) {
        window.cdpAddDesignTutorialUrl = FALLBACK_ADD_DESIGN_TUTORIAL_URL;
    }

    function getDesignVisualElement(designEl) {
        if (!designEl) return null;
        return designEl.querySelector('img') || designEl.querySelector('canvas');
    }

    // ساختار پوشه‌ها
    const folderStructure = {
        'BackGroung': ['ColorGradients', 'GradientBackgrounds-1', 'GradientBackgrounds-2', 'GradientBackgrounds-3', 'LightLeakOverlays', 'Mesh Gradients'],
        'Frame': [],
        'Shapes': ['ColorShapes', 'CrayonTextures', 'Hand Drawn Arrow Elements', 'Hand Drawn Doodle Elements', 'Marker Elements'],
        'Tape': ['BoyTransparentTape', 'Electrical Duct Tape Textures', 'Masking Tape Textures', 'White Duct Tape Textures'],
        'Textures': ['ChalkboardTextures', 'Glitch Overlays', 'KraftPaperTextures', 'Resource Boy - Gold Textures', 'RustTextures'],
        'Lip': [],
        'king': []
    };

    const folderLabels = {
        'BackGroung': 'Backgrounds',
        'ColorGradients': 'Color Gradients',
        'GradientBackgrounds-1': 'Gradient Set 01',
        'GradientBackgrounds-2': 'Gradient Set 02',
        'GradientBackgrounds-3': 'Gradient Set 03',
        'LightLeakOverlays': 'Light Overlays',
        'Mesh Gradients': 'Mesh Gradients',
        'Frame': 'Frames',
        'Shapes': 'Shapes',
        'Tape': 'Tapes',
        'Textures': 'Textures',
        'Lip': 'Lips',
        'king': 'Kings',
        'ColorShapes': 'Color Shapes',
        'CrayonTextures': 'Crayon Textures',
        'Hand Drawn Arrow Elements': 'Hand Drawn Arrows',
        'Hand Drawn Doodle Elements': 'Hand Drawn Doodles',
        'Marker Elements': 'Marker Elements',
        'BoyTransparentTape': 'Transparent Tape',
        'Electrical Duct Tape Textures': 'Electrical Tape',
        'Masking Tape Textures': 'Masking Tape',
        'White Duct Tape Textures': 'White Duct Tape',
        'ChalkboardTextures': 'Chalkboard',
        'Glitch Overlays': 'Glitch Overlays',
        'KraftPaperTextures': 'Kraft Paper',
        'Resource Boy - Gold Textures': 'Gold Textures',
        'RustTextures': 'Rust Textures'
    };

    function formatFolderName(name) {
        if (!name) return '';
        if (folderLabels[name]) return folderLabels[name];
        return name
            .replace(/^\d+_/, '')
            .replace(/[-_]/g, ' ')
            .replace(/([a-z])([A-Z])/g, '$1 $2')
            .replace(/\s+/g, ' ')
            .trim();
    }

    const isFileProtocol = typeof window !== 'undefined' && window.location && window.location.protocol === 'file:';
    let designManifest = null;
    let manifestPromise = null;
    let manifestScriptPromise = null;

    function loadDesignManifest() {
        if (designManifest) return Promise.resolve(designManifest);

        if (typeof window !== 'undefined' && window.__ADD_DESIGN_MANIFEST) {
            designManifest = window.__ADD_DESIGN_MANIFEST;
            return Promise.resolve(designManifest);
        }

        if (isFileProtocol) {
            if (!manifestScriptPromise) {
                manifestScriptPromise = injectManifestScript()
                    .then(() => {
                        designManifest = window.__ADD_DESIGN_MANIFEST || {};
                        return designManifest;
                    })
                    .catch(error => {
                        manifestScriptPromise = null;
                        console.error('❌ Failed to load manifest script', error);
                        throw error;
                    });
            }
            return manifestScriptPromise;
        }

        if (!manifestPromise) {
            manifestPromise = fetch(`${BASE_PATH}adddesign-manifest.json`, { cache: 'no-cache' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Manifest request failed with status ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    designManifest = data || {};
                    return designManifest;
                })
                .catch(error => {
                    console.error('❌ Failed to load design manifest', error);
                    manifestPromise = null;
                    throw error;
                });
        }
        return manifestPromise;
    }

    function encodePathSegments(segments) {
        return segments.map(segment => encodeURIComponent(segment)).join('/');
    }

    function handleTutorialClick() {
        if (typeof window.cdpAddDesignTutorialHandler === 'function') {
            try {
                window.cdpAddDesignTutorialHandler();
                return;
            } catch (err) {
                console.error('Tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpAddDesignTutorialUrl || FALLBACK_ADD_DESIGN_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showTutorialToast('🎬 آموزش به زودی اضافه می‌شود');
    }

    function showTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpDesignTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpDesignTutorialToast';
            toast.className = 'cdp-design-tutorial-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (tutorialToastTimeout) {
            clearTimeout(tutorialToastTimeout);
        }

        tutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2800);
    }

    function injectManifestScript() {
        return new Promise((resolve, reject) => {
            if (typeof document === 'undefined') {
                reject(new Error('Document not available to load manifest'));
                return;
            }

            const globalManifest = window.__ADD_DESIGN_MANIFEST;
            if (globalManifest) {
                resolve(globalManifest);
                return;
            }

            let script = document.querySelector('script[data-adddesign-manifest]');

            const onLoad = () => {
                cleanup();
                if (window.__ADD_DESIGN_MANIFEST) {
                    script.dataset.ready = 'true';
                    resolve(window.__ADD_DESIGN_MANIFEST);
                } else {
                    reject(new Error('Manifest script loaded but data missing'));
                }
            };

            const onError = () => {
                cleanup();
                reject(new Error('Unable to load manifest script'));
            };

            const cleanup = () => {
                if (script) {
                    script.removeEventListener('load', onLoad);
                    script.removeEventListener('error', onError);
                }
            };

            if (script && script.dataset.ready === 'true') {
                cleanup();
                if (window.__ADD_DESIGN_MANIFEST) {
                    resolve(window.__ADD_DESIGN_MANIFEST);
                } else {
                    reject(new Error('Manifest script ready flag set but data missing'));
                }
                return;
            }

            if (!script) {
                script = document.createElement('script');
                script.src = `${BASE_PATH}adddesign-manifest.js?cb=${Date.now()}`;
                script.async = true;
                script.setAttribute('data-adddesign-manifest', 'true');
                document.head.appendChild(script);
            }

            script.addEventListener('load', onLoad);
            script.addEventListener('error', onError);
        });
    }

    // Global Mouse Events
    window.addEventListener('mousemove', function(e) {
        if (isDragging && dragElement) {
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            dragElement.style.left = (startLeft + deltaX) + 'px';
            dragElement.style.top = (startTop + deltaY) + 'px';
            dragElement.style.transform = 'none';
        }
    });

    window.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            if (dragElement) {
                dragElement.style.cursor = 'grab';
                dragElement = null;
            }
        }
    });

    function createPanel() {
        designPanel = document.getElementById('cdpAddDesignPanel');
        if (!designPanel) {
            console.error("❌ Add Design Panel not found in HTML!");
            return;
        }

        const closeBtn = designPanel.querySelector('.cdp-icon-panel-close');
        const cancelBtn = designPanel.querySelector('.cdp-icon-btn--cancel');
        const addBtn = designPanel.querySelector('.cdp-icon-btn--add');
        const backBtn = document.getElementById('addDesignBackBtn');
        const searchInput = document.getElementById('addDesignSearch');
        const clearBtn = document.getElementById('addDesignClear');
        const navRow = designPanel.querySelector('.cdp-icon-search-group');
        const tutorialBtn = document.getElementById('addDesignTutorial');
        const content = document.getElementById('addDesignContent');

        closeBtn.addEventListener('click', closePanel);
        cancelBtn.addEventListener('click', closePanel);
        
        addBtn.addEventListener('click', () => {
            if (selectedDesign) {
                insertDesignImage(selectedDesign);
                closePanel();
            }
        });

        if (backBtn) {
            backBtn.addEventListener('click', function() {
                if (currentPath.length > 0) {
                    currentPath.pop();
                    displayContent();
                }
            });
        }

        if (navRow) {
            navRow.classList.remove('cdp-icon-search-group');
            navRow.classList.add('cdp-design-nav-row');

            const lensIcon = navRow.querySelector('.fa-magnifying-glass');
            if (lensIcon) lensIcon.remove();
            if (searchInput) searchInput.remove();
            if (clearBtn) clearBtn.remove();

            if (!navRow.querySelector('.cdp-design-nav-hint')) {
                const hint = document.createElement('span');
                hint.className = 'cdp-design-nav-hint';
                hint.textContent = 'Browse folders to pick a design.';
                navRow.appendChild(hint);
            }
        }

        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleTutorialClick);
        }

        displayContent();
    }

    function showPanel() {
        console.log('📂 showPanel called');
        if (!designPanel) {
            console.log('📂 Creating panel...');
            createPanel();
        }
        if (!designPanel) {
            console.error('❌ Panel creation failed!');
            return;
        }

        console.log('✅ Setting panel visible');
        designPanel.setAttribute('data-visible', 'true');
        currentPath = [];
        displayContent();

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) sidebar.style.pointerEvents = 'none';
    }

    function closePanel() {
        if (!designPanel) return;
        
        designPanel.setAttribute('data-visible', 'false');
        currentPath = [];
        selectedDesign = null;

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) sidebar.style.pointerEvents = 'auto';
    }

    function displayContent() {
        const content = document.getElementById('addDesignContent');
        const backBtn = document.getElementById('addDesignBackBtn');
        const title = document.getElementById('addDesignTitle');
        if (!content) return;
        
        backBtn.style.display = currentPath.length === 0 ? 'none' : 'inline-flex';

        if (currentPath.length === 0) {
            title.textContent = 'Add Design';
            displayMainFolders(content);
        } else if (currentPath.length === 1) {
            const mainFolder = currentPath[0];
            const subFolders = folderStructure[mainFolder] || [];
            title.textContent = formatFolderName(mainFolder);

            if (subFolders.length === 0) {
                displayImages(content);
            } else {
                displaySubFolders(content);
            }
        } else {
            const lastSegment = currentPath[currentPath.length - 1];
            title.textContent = formatFolderName(lastSegment);
            displayImages(content);
        }
    }

    function displayMainFolders(content) {
        const folders = Object.keys(folderStructure).map(name => {
            const label = formatFolderName(name);
            return `
                <div class="cdp-design-folder" data-folder="${name}">
                    <i class="fa-solid fa-folder"></i>
                    <span>${label}</span>
                </div>
            `;
        }).join('');

        content.innerHTML = folders;

        content.querySelectorAll('.cdp-design-folder').forEach(folder => {
            folder.addEventListener('click', function() {
                currentPath.push(this.dataset.folder);
                displayContent();
            });
        });
    }

    function displaySubFolders(content) {
        const mainFolder = currentPath[0];
        const subFolders = folderStructure[mainFolder] || [];

        const html = subFolders.map(name => {
            const label = formatFolderName(name);
            return `
                <div class="cdp-design-folder" data-folder="${name}">
                    <i class="fa-solid fa-folder"></i>
                    <span>${label}</span>
                </div>
            `;
        }).join('');

        content.innerHTML = html;

        content.querySelectorAll('.cdp-design-folder').forEach(folder => {
            folder.addEventListener('click', function() {
                currentPath.push(this.dataset.folder);
                displayContent();
            });
        });
    }

    function displayImages(content) {
        const folderKey = currentPath.join('/');
        if (!folderKey) return;

        const encodedFolder = encodePathSegments(currentPath);
        const readablePath = `${BASE_PATH}${folderKey}/`;
        content.innerHTML = '<div class="cdp-design-loading">Loading images...</div>';

        loadDesignManifest()
            .then(manifest => {
                const files = manifest ? manifest[folderKey] : null;
                if (files && files.length) {
                    const items = files.map(name => ({
                        src: `${BASE_PATH}${encodedFolder}/${encodeURIComponent(name)}`,
                        label: name.replace(/\.[^/.]+$/, '')
                    }));
                    renderImageItems(content, items);
                } else {
                    loadSequentialImages(readablePath, encodedFolder, content);
                }
            })
            .catch(() => {
                loadSequentialImages(readablePath, encodedFolder, content);
            });
    }

    function renderImageItems(content, items) {
        if (!items || items.length === 0) {
            renderEmptyState(content);
            return;
        }

        const html = items.map(item => `
            <div class="cdp-design-image" data-src="${item.src}">
                <img src="${item.src}" alt="${item.label}" loading="lazy">
                <span class="cdp-design-image-name">${item.label}</span>
            </div>
        `).join('');

        content.innerHTML = html;

        const nodes = content.querySelectorAll('.cdp-design-image');
        nodes.forEach(imgDiv => {
            imgDiv.addEventListener('click', function() {
                nodes.forEach(d => d.classList.remove('selected'));
                this.classList.add('selected');
                selectedDesign = this.dataset.src;
            });
            
            imgDiv.addEventListener('dblclick', function() {
                selectedDesign = this.dataset.src;
                insertDesignImage(selectedDesign);
                closePanel();
            });
        });
    }

    function renderEmptyState(content, pathLabel) {
        content.innerHTML = `
            <div class="cdp-design-empty">
                <p>No images found</p>
                <p style="font-size: calc(var(--cdp-font-scale, 1) * 12px); margin-top: 8px;">Add PNG/JPG files (1.png, 2.jpg, etc.)</p>
                ${pathLabel ? `<p style="font-size: calc(var(--cdp-font-scale, 1) * 11px); margin-top: 8px; color: #9ca3af;">${pathLabel}</p>` : ''}
            </div>
        `;
    }

    function loadSequentialImages(pathLabel, encodedFolder, content) {
        const requestPath = encodedFolder ? `${BASE_PATH}${encodedFolder}/` : pathLabel;
        scanImagesInFolder(requestPath).then(images => {
            if (images.length === 0) {
                renderEmptyState(content, pathLabel);
                return;
            }

            const items = images.map(name => ({
                src: `${requestPath}${encodeURIComponent(name)}`,
                label: name.replace(/\.[^/.]+$/, '')
            }));
            renderImageItems(content, items);
        });
    }

    function scanImagesInFolder(path) {
        return new Promise((resolve) => {
            const images = [];
            const extensions = ['png', 'jpg', 'jpeg'];
            const maxImages = 500;
            const maxFails = 20;
            let count = 1;
            let consecutiveFails = 0;

            function checkImage(n) {
                if (n > maxImages || consecutiveFails >= maxFails) {
                    resolve(images);
                    return;
                }

                let extIndex = 0;

                function tryExtension() {
                    if (extIndex >= extensions.length) {
                        consecutiveFails++;
                        checkImage(n + 1);
                        return;
                    }

                    const filename = `${n}.${extensions[extIndex]}`;
                    const img = new Image();

                    img.onload = function() {
                        images.push(filename);
                        consecutiveFails = 0;
                        checkImage(n + 1);
                    };

                    img.onerror = function() {
                        extIndex++;
                        tryExtension();
                    };

                    img.src = path + encodeURIComponent(filename);
                }

                tryExtension();
            }

            checkImage(count);
        });
    }

    function insertDesignImage(src) {
        const view = window.cdpState.currentView || 'front';
        const boxMap = { front: 'boxFront', back: 'boxBack', right: 'boxRight', left: 'boxLeft' };
        const printBox = document.getElementById(boxMap[view]);

        if (!printBox) {
            console.error('Print box not found');
            return;
        }

        const designEl = document.createElement('div');
        designEl.className = 'cdp-design-element';
        designEl.id = 'design-' + Date.now();
        
        designEl.style.position = 'absolute';
        designEl.style.left = '50%';
        designEl.style.top = '50%';
        designEl.style.transform = 'translate(-50%, -50%)';
        designEl.style.transformOrigin = 'center';
        designEl.style.cursor = 'grab';
        designEl.style.zIndex = '9999';
        designEl.style.userSelect = 'none';
        designEl.style.pointerEvents = 'auto';

        const img = document.createElement('img');
        img.src = src;
        img.style.width = '200px';
        img.style.height = 'auto';
        img.style.display = 'block';
        img.style.pointerEvents = 'none';

        designEl.appendChild(img);
        printBox.appendChild(designEl);

        const layerData = window.cdpLayers ? window.cdpLayers.addLayer({
            element: designEl,
            name: 'Design',
            type: 'design',
            view: view
        }) : null;

        if (layerData) {
            attachDesignEvents(designEl, layerData);
        }

        console.log("✅ Design added:", designEl.id);
    }

    function attachDesignEvents(designEl, layerData) {
        console.log("📎 Attaching design events:", designEl.id, "locked =", layerData.locked);
        
        designEl.addEventListener('mousedown', function(e) {
            if (layerData.locked) {
                console.log("🔒 Design is locked!");
                return;
            }
            
            isDragging = true;
            dragElement = designEl;
            startX = e.clientX;
            startY = e.clientY;

            const rect = designEl.getBoundingClientRect();
            const parent = designEl.parentElement.getBoundingClientRect();
            startLeft = rect.left - parent.left;
            startTop = rect.top - parent.top;

            designEl.style.cursor = 'grabbing';
            e.preventDefault();
            e.stopPropagation();
        }, false);

        designEl.addEventListener('dblclick', function(e) {
            if (layerData.locked) return;
            
            currentResizingDesign = designEl;
            currentResizingLayer = layerData;
            showResizePanel();
            
            e.preventDefault();
            e.stopPropagation();
        }, false);

        console.log("✅ Design events attached!");
    }

    // ===========================
    // RESIZE PANEL
    // ===========================

    function createResizePanel() {
        resizePanel = document.createElement('div');
        resizePanel.className = 'cdp-design-resize-panel';
        resizePanel.setAttribute('data-visible', 'false');
        
        resizePanel.innerHTML = `
            <div class="cdp-design-resize-content">
                <div class="cdp-design-resize-header">
                    <h3>Edit Design</h3>
                    <button type="button" class="cdp-design-resize-close">&times;</button>
                </div>
                
                <div class="cdp-design-resize-transform">
                    <button type="button" class="cdp-transform-btn" data-transform="rotate-left" title="Rotate Left">↺</button>
                    <button type="button" class="cdp-transform-btn" data-transform="rotate-right" title="Rotate Right">↻</button>
                    <button type="button" class="cdp-transform-btn" data-transform="rotate-90" title="Rotate 90°">90°</button>
                    <button type="button" class="cdp-transform-btn" data-transform="flip-vertical" title="Flip Vertical">⇅</button>
                    <button type="button" class="cdp-transform-btn" data-transform="flip-horizontal" title="Flip Horizontal">⇄</button>
                </div>
                
                <div class="cdp-design-resize-body">
                    <div class="cdp-design-resize-group">
                        <label>Width: <span id="cdpDesignSizeValue">200</span>px</label>
                        <input type="range" id="cdpDesignSizeSlider" min="50" max="600" value="200" step="10">
                    </div>
                    
                    <div class="cdp-design-edit-tools">
                        <button type="button" class="cdp-tool-btn" id="cdpDesignEraseBtn" title="Erase">
                            <i class="fa-solid fa-eraser"></i> Erase
                        </button>
                        <button type="button" class="cdp-tool-btn" id="cdpDesignCropBtn" title="Crop">
                            <i class="fa-solid fa-crop"></i> Crop
                        </button>
                    </div>
                    
                    <div class="cdp-design-eraser-controls" id="cdpDesignEraserControls" style="display: none;">
                        <label>Eraser Size: <span id="cdpDesignEraserSizeValue">30</span>px</label>
                        <input type="range" id="cdpDesignEraserSizeSlider" min="10" max="150" value="30" step="5">
                    </div>
                    
                    <div class="cdp-design-resize-preview">
                        <canvas id="cdpDesignEditCanvas"></canvas>
                    </div>
                </div>
                
                <div class="cdp-design-resize-footer">
                    <button type="button" class="cdp-btn cdp-btn-secondary">Cancel</button>
                    <button type="button" class="cdp-btn cdp-btn-primary">Apply</button>
                </div>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            .cdp-design-resize-panel {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: transparent;
                display: none;
                align-items: center;
                justify-content: flex-start;
                padding-left: 100px;
                z-index: 100000;
                pointer-events: none;
            }
            .cdp-design-resize-panel[data-visible="true"] {
                display: flex;
            }
            .cdp-design-resize-content {
                background: #ffffff;
                border-radius: 12px;
                width: 400px;
                max-width: 90%;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                pointer-events: auto;
            }
            .cdp-design-resize-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                padding: 20px 20px 30px 20px;
                border-bottom: 1px solid #e5e7eb;
            }
            .cdp-design-resize-header h3 {
                margin: 0;
                color: #111827;
                font-size: calc(var(--cdp-font-scale, 1) * 18px);
                font-weight: 600;
            }
            .cdp-design-resize-close {
                background: none;
                border: none;
                color: #6b7280;
                font-size: calc(var(--cdp-font-scale, 1) * 28px);
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s;
            }
            .cdp-design-resize-close:hover {
                color: #111827;
            }
            .cdp-design-resize-transform {
                display: flex;
                gap: 10px;
                padding: 16px 20px;
                border-bottom: 1px solid #e5e7eb;
                justify-content: center;
            }
            .cdp-transform-btn {
                width: 44px;
                height: 44px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #ffffff;
                color: #374151;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: calc(var(--cdp-font-scale, 1) * 18px);
                transition: all 0.2s;
            }
            .cdp-transform-btn:hover {
                background: #f3f4f6;
                border-color: #d9a300;
                color: #d9a300;
                transform: scale(1.05);
            }
            .cdp-design-resize-body {
                padding: 20px;
            }
            .cdp-design-resize-group {
                margin-bottom: 16px;
            }
            .cdp-design-resize-group label {
                display: block;
                color: #374151;
                margin-bottom: 8px;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                font-weight: 500;
            }
            .cdp-design-edit-tools {
                display: flex;
                gap: 10px;
                margin-bottom: 16px;
            }
            .cdp-tool-btn {
                flex: 1;
                padding: 10px 16px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #ffffff;
                color: #374151;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.2s;
            }
            .cdp-tool-btn:hover {
                background: #f3f4f6;
                border-color: #d9a300;
                color: #d9a300;
            }
            .cdp-tool-btn.active {
                background: #d9a300;
                border-color: #d9a300;
                color: #ffffff;
            }
            .cdp-design-eraser-controls {
                margin-bottom: 16px;
                padding: 12px;
                background: #f9fafb;
                border-radius: 6px;
                border: 1px solid #e5e7eb;
            }
            .cdp-design-eraser-controls label {
                display: block;
                color: #374151;
                margin-bottom: 8px;
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
            }
            #cdpDesignEraserSizeSlider {
                width: 100%;
                height: 4px;
                border-radius: 2px;
                background: #e5e7eb;
                outline: none;
                -webkit-appearance: none;
            }
            #cdpDesignEraserSizeSlider::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: #d9a300;
                cursor: pointer;
            }
            #cdpDesignEraserSizeSlider::-moz-range-thumb {
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: #d9a300;
                cursor: pointer;
                border: none;
            }
            #cdpDesignSizeSlider {
                width: 100%;
                height: 6px;
                border-radius: 3px;
                background: #e5e7eb;
                outline: none;
                -webkit-appearance: none;
            }
            #cdpDesignSizeSlider::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: #d9a300;
                cursor: pointer;
            }
            #cdpDesignSizeSlider::-moz-range-thumb {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: #d9a300;
                cursor: pointer;
                border: none;
            }
            .cdp-design-resize-preview {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 250px;
                max-height: 300px;
                background: #f9fafb;
                border-radius: 8px;
                padding: 16px;
                border: 2px dashed #e5e7eb;
                overflow: hidden;
                position: relative;
            }
            #cdpDesignEditCanvas {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                background: white;
                display: block;
            }
            .cdp-btn {
                padding: 10px 20px;
                border-radius: 6px;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                border: none;
            }
            .cdp-btn-secondary {
                background: #f3f4f6;
                color: #374151;
            }
            .cdp-btn-secondary:hover {
                background: #e5e7eb;
            }
            .cdp-btn-primary {
                background: #d9a300;
                color: #ffffff;
            }
            .cdp-btn-primary:hover {
                background: #b38600;
            }
            .cdp-design-resize-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                padding: 16px 20px;
                border-top: 1px solid #e5e7eb;
            }
            body.dark-mode .cdp-design-resize-content {
                background: #0b1120;
                border: 1px solid #1f2937;
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.7);
            }
            body.dark-mode .cdp-design-resize-header {
                border-color: #1f2937;
            }
            body.dark-mode .cdp-design-resize-header h3 {
                color: #e2e8f0;
            }
            body.dark-mode .cdp-design-resize-close {
                color: #94a3b8;
            }
            body.dark-mode .cdp-design-resize-close:hover {
                color: #fbbf24;
            }
            body.dark-mode .cdp-design-resize-transform {
                border-color: #1f2937;
            }
            body.dark-mode .cdp-transform-btn {
                background: #111a2c;
                border-color: #243045;
                color: #e2e8f0;
            }
            body.dark-mode .cdp-transform-btn:hover {
                background: #1d2538;
                border-color: #fbbf24;
                color: #fbbf24;
            }
            body.dark-mode .cdp-design-resize-body {
                background: #0b1120;
            }
            body.dark-mode .cdp-design-resize-group label,
            body.dark-mode .cdp-design-eraser-controls label {
                color: #cbd5f5;
            }
            body.dark-mode .cdp-design-eraser-controls {
                background: #111a2c;
                border-color: #1f2937;
            }
            body.dark-mode #cdpDesignEraserSizeValue {
                color: #fbbf24;
            }
            body.dark-mode .cdp-design-edit-tools {
                background: transparent;
            }
            body.dark-mode .cdp-tool-btn {
                background: #111a2c;
                border-color: #1f2937;
                color: #e2e8f0;
            }
            body.dark-mode .cdp-tool-btn:hover {
                background: #1d2538;
                border-color: #fbbf24;
                color: #fbbf24;
            }
            body.dark-mode .cdp-tool-btn.active {
                background: #fbbf24;
                border-color: #fbbf24;
                color: #0b1120;
            }
            body.dark-mode #cdpDesignEraserSizeSlider,
            body.dark-mode #cdpDesignSizeSlider {
                background: #1f2937;
            }
            body.dark-mode #cdpDesignEraserSizeSlider::-webkit-slider-thumb,
            body.dark-mode #cdpDesignEraserSizeSlider::-moz-range-thumb,
            body.dark-mode #cdpDesignSizeSlider::-webkit-slider-thumb,
            body.dark-mode #cdpDesignSizeSlider::-moz-range-thumb {
                background: #fbbf24;
            }
            body.dark-mode .cdp-design-resize-preview {
                background: #111827;
                border-color: #1f2937;
            }
            body.dark-mode #cdpDesignEditCanvas {
                background: #0f172a;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.6);
            }
            body.dark-mode .cdp-btn-secondary {
                background: #111a2c;
                color: #e2e8f0;
            }
            body.dark-mode .cdp-btn-secondary:hover {
                background: #1d2538;
            }
            body.dark-mode .cdp-btn-primary {
                background: #fbbf24;
                color: #0b1120;
            }
            body.dark-mode .cdp-btn-primary:hover {
                background: #f59e0b;
            }
            body.dark-mode .cdp-design-resize-footer {
                border-color: #1f2937;
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(resizePanel);

        const closeBtn = resizePanel.querySelector('.cdp-design-resize-close');
        const cancelBtn = resizePanel.querySelector('.cdp-btn-secondary');
        const applyBtn = resizePanel.querySelector('.cdp-btn-primary');
        const sizeSlider = document.getElementById('cdpDesignSizeSlider');
        const sizeValue = document.getElementById('cdpDesignSizeValue');
        const eraseBtn = document.getElementById('cdpDesignEraseBtn');
        const cropBtn = document.getElementById('cdpDesignCropBtn');
        const eraserSizeSlider = document.getElementById('cdpDesignEraserSizeSlider');
        const eraserSizeValue = document.getElementById('cdpDesignEraserSizeValue');

        closeBtn.addEventListener('click', closeResizePanel);
        cancelBtn.addEventListener('click', closeResizePanel);

        // Erase button
        eraseBtn.addEventListener('click', openEraseTool);

        // Crop button
        cropBtn.addEventListener('click', openCropTool);

        // Eraser size
        eraserSizeSlider.addEventListener('input', function() {
            eraserSize = parseInt(this.value);
            eraserSizeValue.textContent = eraserSize;
        });

        sizeSlider.addEventListener('input', function() {
            const size = this.value;
            sizeValue.textContent = size;
            
            if (currentResizingDesign) {
                const visualEl = getDesignVisualElement(currentResizingDesign);
                if (visualEl) {
                    visualEl.style.width = size + 'px';
                }
                if (currentResizingLayer) {
                    currentResizingLayer.width = parseInt(size, 10);
                }
            }
        });

        const transformBtns = resizePanel.querySelectorAll('.cdp-transform-btn');
        transformBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!currentResizingDesign) return;
                
                const transform = this.dataset.transform;
                const visualEl = getDesignVisualElement(currentResizingDesign);
                if (!visualEl) return;
                
                const currentTransform = visualEl.style.transform || '';
                
                let rotation = 0;
                let scaleX = 1;
                let scaleY = 1;
                
                const rotateMatch = currentTransform.match(/rotate\((-?[\d.]+)deg\)/);
                const scaleXMatch = currentTransform.match(/scaleX\((-?[\d.]+)\)/);
                const scaleYMatch = currentTransform.match(/scaleY\((-?[\d.]+)\)/);
                
                if (rotateMatch) rotation = parseFloat(rotateMatch[1]);
                if (scaleXMatch) scaleX = parseFloat(scaleXMatch[1]);
                if (scaleYMatch) scaleY = parseFloat(scaleYMatch[1]);
                
                if (transform === 'rotate-right') {
                    rotation += 1;
                } else if (transform === 'rotate-left') {
                    rotation -= 1;
                } else if (transform === 'rotate-90') {
                    rotation += 90;
                } else if (transform === 'flip-horizontal') {
                    scaleX *= -1;
                } else if (transform === 'flip-vertical') {
                    scaleY *= -1;
                } else if (transform === 'crop') {
                    openCropTool();
                    return;
                } else if (transform === 'erase') {
                    openEraseTool();
                    return;
                }
                
                visualEl.style.transform = `rotate(${rotation}deg) scaleX(${scaleX}) scaleY(${scaleY})`;
                visualEl.style.transformOrigin = 'center';
                
                // Update canvas preview
                if (!isErasing && !isCropping) {
                    setupCanvas();
                }
            });
        });

        applyBtn.addEventListener('click', function() {
            console.log('🔘 Apply clicked, hasCanvasChanges:', hasCanvasChanges);
            if (currentResizingDesign) {
                const size = sizeSlider.value;
                const img = currentResizingDesign.querySelector('img');
                const canvasEl = currentResizingDesign.querySelector('canvas');
                
                // Apply canvas changes if any
                if (canvas && hasCanvasChanges) {
                    console.log('✅ Applying canvas changes...');
                    
                    // Create a new canvas to replace the image/canvas
                    const newCanvas = document.createElement('canvas');
                    newCanvas.width = canvas.width;
                    newCanvas.height = canvas.height;
                    const newCtx = newCanvas.getContext('2d');
                    newCtx.drawImage(canvas, 0, 0);
                    
                    // Style the new canvas
                    newCanvas.style.width = size + 'px';
                    newCanvas.style.height = 'auto';
                    newCanvas.style.display = 'block';
                    newCanvas.style.pointerEvents = 'none';
                    
                    // Replace img or canvas with new canvas
                    const oldElement = img || canvasEl;
                    if (oldElement) {
                        oldElement.parentNode.replaceChild(newCanvas, oldElement);
                        console.log('💾 Canvas replaced element');
                    }
                    
                    // Reset canvas state
                    hasCanvasChanges = false;
                    originalCanvasContent = null;
                    
                    if (currentResizingLayer) {
                        currentResizingLayer.width = size;
                    }
                    
                    closeResizePanel();
                } else {
                    console.log('ℹ️ No canvas changes to apply');
                    
                    // Apply size if no canvas changes
                    const element = img || canvasEl;
                    if (element) {
                        element.style.width = size + 'px';
                    }
                    
                    if (currentResizingLayer) {
                        currentResizingLayer.width = size;
                    }
                    
                    closeResizePanel();
                }
            }
        });
    }

    function showResizePanel() {
        if (!resizePanel) createResizePanel();
        if (!currentResizingDesign) return;

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) sidebar.style.pointerEvents = 'none';

        const sizeSlider = document.getElementById('cdpDesignSizeSlider');
        const sizeValue = document.getElementById('cdpDesignSizeValue');
        canvas = document.getElementById('cdpDesignEditCanvas');

        const img = currentResizingDesign.querySelector('img');
        const canvasEl = currentResizingDesign.querySelector('canvas');
        
        if ((img || canvasEl) && canvas) {
            const element = img || canvasEl;
            const currentSize = parseInt(element.style.width) || 200;
            sizeSlider.value = currentSize;
            sizeValue.textContent = currentSize;
            
            resetCanvasHistory();
            // Setup canvas
            setupCanvas();
        }

        // Reset tools
        isErasing = false;
        isCropping = false;
        document.getElementById('cdpDesignEraseBtn').classList.remove('active');
        document.getElementById('cdpDesignCropBtn').classList.remove('active');
        document.getElementById('cdpDesignEraserControls').style.display = 'none';

        resizePanel.setAttribute('data-visible', 'true');
    }

    function closeResizePanel() {
        if (!resizePanel) return;
        
        // Remove event handlers before closing
        removeCanvasHandlers();
        
        resizePanel.setAttribute('data-visible', 'false');
        resetCanvasHistory();
        currentResizingDesign = null;
        currentResizingLayer = null;
        isErasing = false;
        isCropping = false;
        canvas = null;
        ctx = null;

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) sidebar.style.pointerEvents = 'auto';
    }

    // CSS Styles
    const style = document.createElement('style');
    style.id = 'cdp-adddesign-styles';
    style.textContent = `
        .cdp-design-back-btn {
            background: #f3f4f6;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-right: 8px;
        }
        .cdp-design-back-btn:hover {
            background: #e5e7eb;
        }
        .cdp-design-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        .cdp-design-nav-row {
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cdp-design-nav-row .cdp-design-back-btn {
            margin-right: 0;
        }
        .cdp-design-nav-hint {
            font-size: calc(var(--cdp-font-scale, 1) * 13px);
            color: #6b7280;
        }
        .cdp-icon-round-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .cdp-icon-round-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.25);
        }
        .cdp-icon-round-btn i {
            font-size: calc(var(--cdp-font-scale, 1) * 16px);
            color: inherit;
        }
        .cdp-icon-round-btn--danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        .cdp-icon-round-btn--danger:hover {
            box-shadow: 0 8px 18px rgba(185, 28, 28, 0.35);
        }
        .cdp-design-search-help {
            width: 34px;
            height: 34px;
            margin-left: 8px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
        }
        .cdp-design-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .cdp-design-folder {
            padding: 14px 16px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            margin-bottom: 8px;
            background: #f9fafb;
        }
        .cdp-design-folder:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }
        .cdp-design-folder i {
            color: #f59e0b;
            font-size: calc(var(--cdp-font-scale, 1) * 20px);
        }
        .cdp-design-folder span {
            font-size: calc(var(--cdp-font-scale, 1) * 14px);
            font-weight: 500;
            color: #111827;
        }
        .cdp-design-image {
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0;
            transition: all 0.2s;
            background: #f9fafb;
            padding: 8px;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .cdp-design-image:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .cdp-design-image.selected {
            border-color: #d9a300;
            background: #eff6ff;
        }
        .cdp-design-image img {
            width: 70%;
            max-width: 260px;
            margin: 0 auto;
            height: auto;
            display: block;
            border-radius: 6px;
        }
        .cdp-design-image-name {
            display: block;
            padding: 8px;
            font-size: calc(var(--cdp-font-scale, 1) * 12px);
            color: #6b7280;
            text-align: center;
        }
        .cdp-design-loading,
        .cdp-design-empty {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
            font-size: calc(var(--cdp-font-scale, 1) * 14px);
        }
        .cdp-design-tutorial-toast {
            position: fixed;
            bottom: 32px;
            right: 32px;
            background: rgba(17, 24, 39, 0.95);
            color: #f3f4f6;
            padding: 14px 20px;
            border-radius: 999px;
            font-size: calc(var(--cdp-font-scale, 1) * 14px);
            font-weight: 600;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            z-index: 200000;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
        }
        .cdp-design-tutorial-toast[data-visible="true"] {
            opacity: 1;
            transform: translateY(0);
        }
        body.dark-mode .cdp-icon-round-btn {
            background: #1f2937;
            color: #f8fafc;
            box-shadow: 0 3px 10px rgba(0,0,0,0.5);
        }
        body.dark-mode .cdp-design-nav-row {
            border-bottom: 1px solid #343a46;
        }
        body.dark-mode .cdp-design-nav-hint {
            color: #bfc8db;
        }
        body.dark-mode .cdp-icon-round-btn--danger {
            background: #4c0519;
            color: #fcd34d;
        }
        body.dark-mode .cdp-design-tutorial-toast {
            background: rgba(15, 23, 42, 0.98);
            color: #fbbf24;
        }
    `;
    
    if (!document.getElementById('cdp-adddesign-styles')) {
        document.head.appendChild(style);
    }

    // Button Click
    addDesignBtn.addEventListener('click', function() {
        console.log('🔘 Add Design button clicked!');
        showPanel();
    });

    function isTextEntryElement(element) {
        if (!element) return false;
        if (element.isContentEditable) return true;
        const tag = element.tagName;
        if (tag === 'TEXTAREA') return true;
        if (tag === 'INPUT') {
            const type = (element.type || '').toLowerCase();
            const blockedTypes = ['text', 'email', 'password', 'search', 'url', 'tel', 'number'];
            return blockedTypes.includes(type);
        }
        return false;
    }

    // Escape Key
    document.addEventListener('keydown', (e) => {
        if (designPanel && designPanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closePanel();
        }
        if (resizePanel && resizePanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closeResizePanel();
        }
        const isUndoCombo = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z';
        if (isUndoCombo) {
            if (isTextEntryElement(document.activeElement)) return;
            if (resizePanel && resizePanel.getAttribute('data-visible') === 'true') {
                const undone = undoCanvasEdit();
                if (undone) {
                    e.preventDefault();
                }
            }
        }
    });

    // ===========================
    // CANVAS EDIT TOOLS
    // ===========================

    let canvas = null;
    let ctx = null;
    let isErasing = false;
    let isCropping = false;
    let hasCanvasChanges = false;
    let eraserSize = 30;
    let currentHandlers = null;
    let originalCanvasContent = null;
    let isCanvasHistoryPrimed = false;
    const CANVAS_MAX_WIDTH = 320;
    const CANVAS_MAX_HEIGHT = 280;
    const CANVAS_HISTORY_LIMIT = 20;
    let canvasHistory = [];
    let canvasHistoryIndex = -1;

    function setCanvasDisplaySize(sourceWidth, sourceHeight) {
        if (!canvas) return;
        let displayWidth = sourceWidth;
        let displayHeight = sourceHeight;

        if (displayWidth > CANVAS_MAX_WIDTH || displayHeight > CANVAS_MAX_HEIGHT) {
            const widthRatio = CANVAS_MAX_WIDTH / displayWidth;
            const heightRatio = CANVAS_MAX_HEIGHT / displayHeight;
            const ratio = Math.min(widthRatio, heightRatio);
            displayWidth = Math.floor(displayWidth * ratio);
            displayHeight = Math.floor(displayHeight * ratio);
        }

        canvas.style.width = displayWidth + 'px';
        canvas.style.height = displayHeight + 'px';
    }

    function resetCanvasHistory() {
        canvasHistory = [];
        canvasHistoryIndex = -1;
        hasCanvasChanges = false;
        originalCanvasContent = null;
        isCanvasHistoryPrimed = false;
    }

    function pushCanvasHistoryState() {
        if (!canvas) return;
        const snapshotCanvas = document.createElement('canvas');
        snapshotCanvas.width = canvas.width;
        snapshotCanvas.height = canvas.height;

        const snapshotCtx = snapshotCanvas.getContext('2d');

        try {
            snapshotCtx.clearRect(0, 0, snapshotCanvas.width, snapshotCanvas.height);
            snapshotCtx.drawImage(canvas, 0, 0);
        } catch (error) {
            console.error('❌ Unable to capture canvas history:', error);
            return;
        }

        if (canvasHistoryIndex < canvasHistory.length - 1) {
            canvasHistory = canvasHistory.slice(0, canvasHistoryIndex + 1);
        }

        canvasHistory.push({
            width: canvas.width,
            height: canvas.height,
            canvas: snapshotCanvas
        });

        canvasHistoryIndex = canvasHistory.length - 1;

        if (canvasHistory.length > CANVAS_HISTORY_LIMIT) {
            canvasHistory.splice(1, 1);
            if (canvasHistoryIndex > 1) {
                canvasHistoryIndex -= 1;
            }
            if (canvasHistoryIndex >= canvasHistory.length) {
                canvasHistoryIndex = canvasHistory.length - 1;
            }
        }

        hasCanvasChanges = canvasHistoryIndex > 0;
    }

    function applyCanvasSnapshot(snapshot) {
        if (!canvas || !ctx || !snapshot || !snapshot.canvas) return;
        canvas.width = snapshot.width;
        canvas.height = snapshot.height;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(snapshot.canvas, 0, 0);
        setCanvasDisplaySize(snapshot.width, snapshot.height);

        originalCanvasContent = document.createElement('canvas');
        const tempCtx = originalCanvasContent.getContext('2d');
        originalCanvasContent.width = snapshot.width;
        originalCanvasContent.height = snapshot.height;
        tempCtx.clearRect(0, 0, snapshot.width, snapshot.height);
        tempCtx.drawImage(snapshot.canvas, 0, 0);
    }

    function undoCanvasEdit() {
        if (!canvas || canvasHistoryIndex <= 0) return false;
        canvasHistoryIndex -= 1;
        applyCanvasSnapshot(canvasHistory[canvasHistoryIndex]);
        hasCanvasChanges = canvasHistoryIndex > 0;
        console.log('↩️ Undo applied. History index:', canvasHistoryIndex);
        return true;
    }

    function setupCanvas() {
        if (!currentResizingDesign) return;

        canvas = document.getElementById('cdpDesignEditCanvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');
        
        // Check if element is img or canvas
        let imgElement = currentResizingDesign.querySelector('img');
        let canvasElement = currentResizingDesign.querySelector('canvas');
        
        if (!imgElement && !canvasElement) return;

        const tempImg = new Image();
        tempImg.onload = function() {
            canvas.width = tempImg.width;
            canvas.height = tempImg.height;
            setCanvasDisplaySize(tempImg.width, tempImg.height);
            ctx.drawImage(tempImg, 0, 0);
            if (!isCanvasHistoryPrimed) {
                pushCanvasHistoryState();
                isCanvasHistoryPrimed = true;
            }

            if (isErasing) {
                setupEraser();
            } else if (isCropping) {
                setupCrop();
            }
        };
        
        // Load from img or canvas
        if (imgElement) {
            tempImg.src = imgElement.src;
        } else if (canvasElement) {
            // If already a canvas, draw it directly
            canvas.width = canvasElement.width;
            canvas.height = canvasElement.height;
            setCanvasDisplaySize(canvasElement.width, canvasElement.height);
            ctx.drawImage(canvasElement, 0, 0);
            if (!isCanvasHistoryPrimed) {
                pushCanvasHistoryState();
                isCanvasHistoryPrimed = true;
            }

            if (isErasing) {
                setupEraser();
            } else if (isCropping) {
                setupCrop();
            }
        }
    }

    function removeCanvasHandlers() {
        if (!canvas || !currentHandlers) return;

        if (currentHandlers.mousedown) canvas.removeEventListener('mousedown', currentHandlers.mousedown);
        if (currentHandlers.mouseup) canvas.removeEventListener('mouseup', currentHandlers.mouseup);
        if (currentHandlers.mousemove) canvas.removeEventListener('mousemove', currentHandlers.mousemove);
        if (currentHandlers.mouseleave) canvas.removeEventListener('mouseleave', currentHandlers.mouseleave);
        
        currentHandlers = null;
        console.log('🧹 Canvas handlers removed');
    }

    function setupEraser() {
        if (!canvas || !ctx) return;

        // Remove old handlers first
        removeCanvasHandlers();

        // Save original content if not already saved
        if (!originalCanvasContent) {
            originalCanvasContent = document.createElement('canvas');
            const tempCtx = originalCanvasContent.getContext('2d');
            originalCanvasContent.width = canvas.width;
            originalCanvasContent.height = canvas.height;
            tempCtx.drawImage(canvas, 0, 0);
            console.log('📋 Saved canvas copy for eraser');
        }

        let isDrawing = false;

        canvas.style.cursor = 'crosshair';

        const mousedownHandler = () => {
            isDrawing = true;
            console.log('🖌️ Eraser mousedown');
        };
        const mouseupHandler = () => {
            if (isDrawing) {
                isDrawing = false;
                pushCanvasHistoryState();
                console.log('✅ Erase completed. History index:', canvasHistoryIndex);
            }
        };
        const mouseleaveHandler = () => {
            if (isDrawing) {
                isDrawing = false;
                pushCanvasHistoryState();
            }
        };
        const mousemoveHandler = function(e) {
            if (!isDrawing) return;

            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const x = (e.clientX - rect.left) * scaleX;
            const y = (e.clientY - rect.top) * scaleY;

            ctx.globalCompositeOperation = 'destination-out';
            ctx.beginPath();
            ctx.arc(x, y, eraserSize, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalCompositeOperation = 'source-over';
        };

        canvas.addEventListener('mousedown', mousedownHandler);
        canvas.addEventListener('mouseup', mouseupHandler);
        canvas.addEventListener('mouseleave', mouseleaveHandler);
        canvas.addEventListener('mousemove', mousemoveHandler);

        currentHandlers = {
            mousedown: mousedownHandler,
            mouseup: mouseupHandler,
            mouseleave: mouseleaveHandler,
            mousemove: mousemoveHandler
        };
    }

    function setupCrop() {
        if (!canvas || !ctx) return;

        // Remove old handlers
        removeCanvasHandlers();

        let startX, startY, isSelecting = false;
        let cropRect = null;
        
        console.log('✂️ Setting up crop tool...');
        
        // Save current canvas content only if not already saved
        if (!originalCanvasContent) {
            originalCanvasContent = document.createElement('canvas');
            const tempCtx = originalCanvasContent.getContext('2d');
            originalCanvasContent.width = canvas.width;
            originalCanvasContent.height = canvas.height;
            tempCtx.drawImage(canvas, 0, 0);
            console.log('📋 Saved canvas copy:', originalCanvasContent.width, 'x', originalCanvasContent.height);
        }

        canvas.style.cursor = 'crosshair';

        const mousedownHandler = function(e) {
            const rect = canvas.getBoundingClientRect();
            startX = (e.clientX - rect.left) * (canvas.width / rect.width);
            startY = (e.clientY - rect.top) * (canvas.height / rect.height);
            isSelecting = true;
            console.log('✂️ Crop mousedown at', startX, startY);
        };

        const mousemoveHandler = function(e) {
            if (!isSelecting) return;

            const rect = canvas.getBoundingClientRect();
            const currentX = (e.clientX - rect.left) * (canvas.width / rect.width);
            const currentY = (e.clientY - rect.top) * (canvas.height / rect.height);

            // Restore original image
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(originalCanvasContent, 0, 0);

            // Draw selection rectangle
            ctx.strokeStyle = '#d9a300';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.strokeRect(startX, startY, currentX - startX, currentY - startY);
            ctx.setLineDash([]);

            cropRect = {
                x: Math.min(startX, currentX),
                y: Math.min(startY, currentY),
                width: Math.abs(currentX - startX),
                height: Math.abs(currentY - startY)
            };
        };

        const mouseupHandler = function() {
            if (isSelecting && cropRect && cropRect.width > 10 && cropRect.height > 10) {
                console.log('✂️ Applying crop:', cropRect);
                applyCrop(cropRect, originalCanvasContent);
                pushCanvasHistoryState();
                
                // Update originalCanvasContent with cropped result
                originalCanvasContent.width = canvas.width;
                originalCanvasContent.height = canvas.height;
                const tempCtx = originalCanvasContent.getContext('2d');
                tempCtx.clearRect(0, 0, canvas.width, canvas.height);
                tempCtx.drawImage(canvas, 0, 0);
                
                // Remove handlers after crop
                removeCanvasHandlers();
                
                isCropping = false;
                const cropBtn = document.getElementById('cdpDesignCropBtn');
                if (cropBtn) cropBtn.classList.remove('active');
                canvas.style.cursor = 'default';
                console.log('✅ Crop completed. History index:', canvasHistoryIndex);
            } else {
                console.log('⚠️ Crop too small or no selection');
                // Restore original if crop cancelled
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(originalCanvasContent, 0, 0);
            }
            isSelecting = false;
        };

        canvas.addEventListener('mousedown', mousedownHandler);
        canvas.addEventListener('mousemove', mousemoveHandler);
        canvas.addEventListener('mouseup', mouseupHandler);

        currentHandlers = {
            mousedown: mousedownHandler,
            mousemove: mousemoveHandler,
            mouseup: mouseupHandler
        };
    }

    function applyCrop(rect, sourceCanvas) {
        if (!canvas || !ctx || !rect) return;

        // Create temporary canvas for cropping
        const croppedCanvas = document.createElement('canvas');
        const croppedCtx = croppedCanvas.getContext('2d');
        
        croppedCanvas.width = rect.width;
        croppedCanvas.height = rect.height;
        
        // Draw cropped area from source canvas
        croppedCtx.drawImage(sourceCanvas, rect.x, rect.y, rect.width, rect.height, 0, 0, rect.width, rect.height);
        
        // Update main canvas
        canvas.width = rect.width;
        canvas.height = rect.height;
        setCanvasDisplaySize(rect.width, rect.height);
        ctx.drawImage(croppedCanvas, 0, 0);
        
        console.log('✂️ Crop applied! New canvas size:', canvas.width, 'x', canvas.height);
    }

    function openCropTool() {
        console.log('🖼️ Crop tool clicked!');
        isCropping = true;
        isErasing = false;
        
        const eraseBtn = document.getElementById('cdpDesignEraseBtn');
        const cropBtn = document.getElementById('cdpDesignCropBtn');
        const eraserControls = document.getElementById('cdpDesignEraserControls');
        
        if (eraseBtn) eraseBtn.classList.remove('active');
        if (cropBtn) cropBtn.classList.add('active');
        if (eraserControls) eraserControls.style.display = 'none';
        
        setupCanvas();
        console.log('✅ Crop tool setup complete');
    }

    function openEraseTool() {
        console.log('✏️ Erase tool clicked!');
        isErasing = true;
        isCropping = false;
        
        const eraseBtn = document.getElementById('cdpDesignEraseBtn');
        const cropBtn = document.getElementById('cdpDesignCropBtn');
        const eraserControls = document.getElementById('cdpDesignEraserControls');
        
        if (cropBtn) cropBtn.classList.remove('active');
        if (eraseBtn) eraseBtn.classList.add('active');
        if (eraserControls) eraserControls.style.display = 'block';
        
        setupCanvas();
        console.log('✅ Erase tool setup complete');
    }

    // Reattach for duplicate
    window.reattachAddDesignEvents = function(element) {
        const view = window.cdpState.currentView || 'front';
        const layerData = window.layersByView[view].find(l => l.element === element);
        
        if (layerData) {
            attachDesignEvents(element, layerData);
        }
    };

    console.log("✅ Add Design system ready!");
});
