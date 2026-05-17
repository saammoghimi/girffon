// =========================
// Upload Image System with Edit Tools
// =========================

(function() {
    'use strict';
    console.log("📤 upload.js loaded");
    console.log("Upload system initializing...");

    let uploadModal = null;
    let editPanel = null;
    let currentUploadedImage = null;
    let currentEditingImage = null;
    let currentLayer = null;
    let isErasing = false;
    let isCropping = false;
    let hasCanvasChanges = false;
    let eraserSize = 20;
    let canvas = null;
    let ctx = null;
    let originalImageBackup = null; // Backup قبل از Edit
    let uploadTutorialToastTimeout = null;
    const FALLBACK_UPLOAD_TUTORIAL_URL = 'https://www.youtube.com/watch?v=5BJw2FUhJCM';
    const CANVAS_MAX_WIDTH = 320;
    const CANVAS_MAX_HEIGHT = 280;
    const CANVAS_HISTORY_LIMIT = 20;
    const FILTER_ADJUST_MIN = -100;
    const FILTER_ADJUST_MAX = 100;
    const EXPOSURE_GAMMA_MIN = 0.1;
    const EXPOSURE_GAMMA_MAX = 3;
    const EXPOSURE_GAMMA_STEP = 0.05;
    const LEVELS_CHANNELS = ['rgb', 'red', 'green', 'blue'];
    const LEVELS_INPUT_MIN = 0;
    const LEVELS_INPUT_MAX = 255;
    const LEVELS_MID_MIN = 0.1;
    const LEVELS_MID_MAX = 5;
    const LEVELS_OUTPUT_MIN = 0;
    const LEVELS_OUTPUT_MAX = 255;
    const CURVES_CHANNELS = ['rgb', 'red', 'green', 'blue'];
    const CURVES_MAX_POINTS = 12;
    const CURVES_POINT_RADIUS = 8;
    const CURVES_COLORS = {
        rgb: '#111827',
        red: '#ef4444',
        green: '#22c55e',
        blue: '#3b82f6'
    };
    const CURVES_DEFAULT_POINTS = Object.freeze([
        { x: 0, y: 0 },
        { x: 128, y: 128 },
        { x: 255, y: 255 }
    ]);
    const CURVES_CANVAS_WIDTH = 320;
    const CURVES_CANVAS_HEIGHT = 220;
    const CURVES_GRAPH_PADDING = 18;
    const LIGHT_COLOR_EXPOSURE_MIN = -5;
    const LIGHT_COLOR_EXPOSURE_MAX = 5;
    const LIGHT_COLOR_INCIDENT_MIN = -100;
    const LIGHT_COLOR_INCIDENT_MAX = 100;
    const LIGHT_COLOR_AUTO_ACTIONS = Object.freeze(['auto', 'bw']);
    const GAUSSIAN_BLUR_MIN = 0;
    const GAUSSIAN_BLUR_MAX = 20;
    const GAUSSIAN_BLUR_STEP = 0.1;
    const BLACK_WHITE_CHANNELS = ['reds', 'yellows', 'greens', 'cyans', 'blues', 'magentas'];
    const BLACK_WHITE_MIN = 0;
    const BLACK_WHITE_MAX = 200;
    const BLACK_WHITE_DEFAULT = 100;
    const BLACK_WHITE_DEFAULT_TINT = '#f4efe5';

    if (typeof window.cdpUploadTutorialUrl !== 'string' || !window.cdpUploadTutorialUrl.trim()) {
        window.cdpUploadTutorialUrl = FALLBACK_UPLOAD_TUTORIAL_URL;
    }

    function convertSvgToCursorData(svgString) {
        if (!svgString) return '';
        try {
            if (typeof btoa === 'function') {
                return 'data:image/svg+xml;base64,' + btoa(svgString);
            }
        } catch (err) {
            /* ignore and fall back */
        }
        return 'data:image/svg+xml,' + encodeURIComponent(svgString);
    }

    const ADVANCED_ERASE_CURSOR_WAND_SRC = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none"><g stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 25 L21 11"/><path d="M9 9 L12 6"/><path d="M23 17 L26 20"/></g><circle cx="21" cy="11" r="3" fill="#fcd34d"/></svg>';
    const ADVANCED_ERASE_CURSOR_MOVE_SRC = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4 L13 7"/><path d="M16 4 L19 7"/><path d="M16 28 L13 25"/><path d="M16 28 L19 25"/><path d="M4 16 L7 13"/><path d="M4 16 L7 19"/><path d="M28 16 L25 13"/><path d="M28 16 L25 19"/><line x1="16" y1="4" x2="16" y2="28"/><line x1="4" y1="16" x2="28" y2="16"/></svg>';
    const ADVANCED_ERASE_CURSOR_WAND = convertSvgToCursorData(ADVANCED_ERASE_CURSOR_WAND_SRC);
    const ADVANCED_ERASE_CURSOR_MOVE = convertSvgToCursorData(ADVANCED_ERASE_CURSOR_MOVE_SRC);

    const FILTER_PROPERTIES_COPY = {
        brightness: {
            title: 'Brightness / contrast',
            hint: 'Adjust brightness and contrast, then press OK to keep the changes.'
        },
        hue: {
            title: 'Hue / saturation',
            hint: 'Fine-tune hue, saturation, and lightness. Colorize applies a monochrome tint.'
        },
        exposure: {
            title: 'Exposure',
            hint: 'Dial in exposure, offset, and gamma to brighten or recover details.'
        },
        vibrance: {
            title: 'Vibrance',
            hint: 'Boost muted colors or calm intense tones without blowing out skin tones.'
        },
        blur: {
            title: 'Gaussian blur',
            hint: 'Soften the image with a precise blur radius measured in pixels.'
        },
        'color-balance': {
            title: 'Color balance',
            hint: 'Target shadows, midtones, or highlights and nudge each color pair while keeping luminosity intact.'
        },
        levels: {
            title: 'Levels',
            hint: 'Adjust input and output levels per channel for precise tonal control.'
        },
        curves: {
            title: 'Curves',
            hint: 'Fine-tune tonal response per channel. Add points, drag them, and craft your own S-curves.'
        },
        'light-color': {
            title: 'Light & color',
            hint: 'Balance exposure, contrast, and tonal regions for a Lightroom-style workflow.'
        },
        bw: {
            title: 'Black & white',
            hint: 'Adjust individual color luminance and optionally tint the monochrome mix.'
        }
    };
    let canvasHistory = [];
    let canvasHistoryIndex = -1;
    let isCanvasHistoryPrimed = false;
    let canvasHandlers = null;
    let cropSourceCanvas = null;
    let isCanvasReadyForOps = false;
    let advancedEraseOverlay = null;
    let advancedEraseCanvas = null;
    let advancedEraseCanvasInner = null;
    let advancedEraseCtx = null;
    let advancedEraseBrushSlider = null;
    let advancedEraseBrushValue = null;
    let advancedEraseZoomDisplay = null;
    let advancedEraseMoveBtn = null;
    let advancedEraseBrushBtn = null;
    let advancedEraseRemoveBgBtn = null;
    let advancedEraseAutoStrengthSlider = null;
    let advancedEraseAutoStrengthValue = null;
    let advancedEraseStatusEl = null;
    let advancedEraseStatusCopyEl = null;
    let advancedEraseZoom = 1;
    let advancedEraseBrushSize = 40;
    let advancedEraseAutoStrength = 55;
    let advancedEraseIsDrawing = false;
    let advancedEraseImageSnapshot = null;
    let advancedEraseHistory = [];
    let advancedEraseHistoryIndex = -1;
    let advancedErasePanMode = false;
    let advancedEraseBrushActive = true;
    let advancedErasePanStart = null;
    let advancedErasePanStartOffset = null;
    let advancedErasePanOffset = { x: 0, y: 0 };
    let advancedEraseKeyListenerBound = false;
    let advancedEraseAutoProcessing = false;
    let advancedEraseStatusTimeoutId = null;
    let advancedEraseWandMode = false;
    let advancedEraseBrushCursorCache = { diameter: null, url: null, hotspot: 0 };
    let filterPreviewZoom = 1;
    let cropWorkspaceOverlay = null;
    let cropWorkspaceCanvas = null;
    let cropWorkspaceCtx = null;
    let cropWorkspaceInner = null;
    let cropWorkspaceZoom = 1;
    let cropWorkspaceZoomLabel = null;
    let cropWorkspaceSelection = null;
    let cropWorkspaceSelectionEl = null;
    let cropWorkspaceIsDragging = false;
    let cropWorkspaceDragStart = null;
    let cropWorkspaceIsMovingSelection = false;
    let cropWorkspaceSelectionStart = null;
    let cropButtonRef = null;
    let cropWorkspaceHint = null;
    let cropWorkspaceHintDefault = '';
    let cropWorkspacePreviewCanvas = null;
    let cropWorkspacePreviewCtx = null;
    let cropWorkspacePreviewFrame = null;
    let cropWorkspacePreviewPlaceholder = null;
    let filterPropertiesCard = null;
    let filterPropertiesTitleEl = null;
    let filterPropertiesHintEl = null;
    let filterPropertiesPanelEls = null;
    let filterPropertiesResetBtn = null;
    let filterPropertiesMode = null;
    let brightnessSliderEl = null;
    let brightnessInputEl = null;
    let contrastSliderEl = null;
    let contrastInputEl = null;
    let brightnessContrastBaseCanvas = null;
    let brightnessContrastHistoryCaptured = false;
    let brightnessControlValue = 0;
    let contrastControlValue = 0;
    let hueTargetSelectEl = null;
    let hueSliderEl = null;
    let hueInputEl = null;
    let hueSaturationSliderEl = null;
    let hueSaturationInputEl = null;
    let hueLightnessSliderEl = null;
    let hueLightnessInputEl = null;
    let hueColorizeToggleEl = null;
    let hueAdjustmentBaseCanvas = null;
    let hueAdjustmentHistoryCaptured = false;
    let hueControlValue = 0;
    let hueSaturationControlValue = 0;
    let hueLightnessControlValue = 0;
    let hueColorizeEnabled = false;
    let exposureSliderEl = null;
    let exposureInputEl = null;
    let exposureOffsetSliderEl = null;
    let exposureOffsetInputEl = null;
    let exposureGammaSliderEl = null;
    let exposureGammaInputEl = null;
    let exposureAdjustmentBaseData = null;
    let exposureAdjustmentHistoryCaptured = false;
    let exposureControlValue = 0;
    let exposureOffsetValue = 0;
    let exposureGammaValue = 1;
    let vibranceSliderEl = null;
    let vibranceInputEl = null;
    let vibranceSaturationSliderEl = null;
    let vibranceSaturationInputEl = null;
    let vibranceAdjustmentBaseData = null;
    let vibranceAdjustmentHistoryCaptured = false;
    let vibranceControlValue = 0;
    let vibranceSaturationControlValue = 0;
    let blurSliderEl = null;
    let blurInputEl = null;
    let blurBaseCanvas = null;
    let blurHistoryCaptured = false;
    let blurValue = 0;
    let levelsChannelBtnEls = [];
    let levelsActiveChannel = 'rgb';
    let levelsChannelState = LEVELS_CHANNELS.reduce((acc, channel) => {
        acc[channel] = createDefaultLevelsState();
        return acc;
    }, {});
    let levelsInputBlackSliderEl = null;
    let levelsInputBlackInputEl = null;
    let levelsInputMidSliderEl = null;
    let levelsInputMidInputEl = null;
    let levelsInputWhiteSliderEl = null;
    let levelsInputWhiteInputEl = null;
    let levelsOutputBlackSliderEl = null;
    let levelsOutputBlackInputEl = null;
    let levelsOutputWhiteSliderEl = null;
    let levelsOutputWhiteInputEl = null;
    let levelsAdjustmentBaseData = null;
    let levelsAdjustmentHistoryCaptured = false;
    let curvesCanvasEl = null;
    let curvesCanvasCtx = null;
    let curvesModeButtons = [];
    let curvesChannelButtons = [];
    let curvesInputValueEl = null;
    let curvesOutputValueEl = null;
    let curvesDeletePointBtn = null;
    let curvesActiveMode = 'points';
    let curvesActiveChannel = 'rgb';
    let curvesSelectedPointIndex = null;
    let curvesIsDraggingPoint = false;
    let curvesDragPointIndex = null;
    let curvesActivePointerId = null;
    let curvesPointerEventsBound = false;
    let curvesChannelState = createCurvesStateSnapshot();
    let curvesAdjustmentBaseData = null;
    let curvesAdjustmentHistoryCaptured = false;
    let curvesLookupTables = CURVES_CHANNELS.reduce((acc, channel) => {
        acc[channel] = null;
        return acc;
    }, {});
    let curvesLookupDirty = CURVES_CHANNELS.reduce((acc, channel) => {
        acc[channel] = true;
        return acc;
    }, {});
    let lightColorExposureSliderEl = null;
    let lightColorExposureInputEl = null;
    let lightColorContrastSliderEl = null;
    let lightColorContrastInputEl = null;
    let lightColorHighlightsSliderEl = null;
    let lightColorHighlightsInputEl = null;
    let lightColorShadowsSliderEl = null;
    let lightColorShadowsInputEl = null;
    let lightColorWhitesSliderEl = null;
    let lightColorWhitesInputEl = null;
    let lightColorBlacksSliderEl = null;
    let lightColorBlacksInputEl = null;
    let lightColorAdjustmentBaseData = null;
    let lightColorAdjustmentHistoryCaptured = false;
    let lightColorExposureValue = 0;
    let lightColorContrastValue = 0;
    let lightColorHighlightsValue = 0;
    let lightColorShadowsValue = 0;
    let lightColorWhitesValue = 0;
    let lightColorBlacksValue = 0;
    let blackWhiteSliderMap = {};
    let blackWhiteInputMap = {};
    let blackWhiteControlValues = BLACK_WHITE_CHANNELS.reduce((acc, key) => {
        acc[key] = BLACK_WHITE_DEFAULT;
        return acc;
    }, {});
    let blackWhiteTintToggleEl = null;
    let blackWhiteTintColorEl = null;
    let blackWhiteTintEnabled = false;
    let blackWhiteTintColorValue = BLACK_WHITE_DEFAULT_TINT;
    let blackWhiteAdjustmentBaseData = null;
    let blackWhiteAdjustmentHistoryCaptured = false;
    let colorBalanceToneSelectEl = null;
    let colorBalanceCyanRedSliderEl = null;
    let colorBalanceCyanRedInputEl = null;
    let colorBalanceMagentaGreenSliderEl = null;
    let colorBalanceMagentaGreenInputEl = null;
    let colorBalanceYellowBlueSliderEl = null;
    let colorBalanceYellowBlueInputEl = null;
    let colorBalancePreserveLuminosityToggleEl = null;
    let colorBalanceAdjustmentBaseData = null;
    let colorBalanceAdjustmentHistoryCaptured = false;
    let colorBalanceToneValue = 'midtones';
    let colorBalanceCyanRedValue = 0;
    let colorBalanceMagentaGreenValue = 0;
    let colorBalanceYellowBlueValue = 0;
    let colorBalancePreserveLuminosity = true;
    let uploadFileInput = null;

    // =========================
    // Initialize Upload Button
    // =========================

    function init() {
        const uploadBtn = document.querySelector('[data-tool="upload"]');
        if (!uploadBtn) {
            console.error("❌ Upload button not found");
            return;
        }

        console.log('📤 Upload button element:', uploadBtn);
        console.log('📤 Upload button disabled?', uploadBtn.hasAttribute('disabled'));
        
        uploadBtn.addEventListener('click', function(e) {
            console.log('📤 UPLOAD BUTTON CLICKED!');
            console.log('📤 Button disabled?', this.hasAttribute('disabled'));
            console.log('📤 Button:', this);
            openUploadModal();
        });
    }

    // =========================
    // Upload Modal
    // =========================

    function openUploadModal() {
        console.log('Opening upload modal...');
        
        if (uploadModal) {
            uploadModal.style.display = 'block';
            console.log('Upload modal displayed');
            return;
        }

        console.log('Creating upload modal...');
        uploadModal = document.createElement('div');
        uploadModal.className = 'cdp-upload-modal';
        uploadModal.innerHTML = `
            <div class="cdp-upload-content">
                <div class="cdp-upload-header">
                    <h3>Upload Design</h3>
                    <div class="cdp-upload-header-actions">
                        <button type="button" class="cdp-icon-round-btn cdp-upload-help" title="Tutorial">
                            <i class="fa-regular fa-circle-question"></i>
                        </button>
                        <button type="button" class="cdp-icon-round-btn cdp-upload-close" title="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="cdp-upload-body">
                    <div class="cdp-upload-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <p>High-quality copy is kept for save. A smaller optimized copy will be inserted into the active T-shirt box after you press OK.</p>
                    </div>
                    <div class="cdp-upload-steps">
                        <div class="cdp-upload-step">
                            <div class="cdp-step-number">1</div>
                            <div class="cdp-step-text">Keep original file (HQ) for download</div>
                        </div>
                        <div class="cdp-upload-step">
                            <div class="cdp-step-number">2</div>
                            <div class="cdp-step-text">Prepare optimized copy (smaller size)</div>
                        </div>
                    </div>
                    <div class="cdp-upload-preview" id="cdpUploadPreview">
                        <p>Preview</p>
                    </div>
                </div>
                <div class="cdp-upload-footer">
                    <button type="button" class="cdp-btn cdp-btn-secondary" id="cdpChooseImage">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Choose image
                    </button>
                    <button type="button" class="cdp-btn cdp-btn-primary" id="cdpUploadOK" disabled>
                        <i class="fa-solid fa-check"></i> OK
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(uploadModal);
        console.log('Upload modal added to body');
        
        addUploadModalStyles();
        setupUploadEvents();
        
        uploadModal.style.display = 'block';
        console.log('Upload modal shown');
    }

    function setupUploadEvents() {
        const closeBtn = uploadModal.querySelector('.cdp-upload-close');
        const tutorialBtn = uploadModal.querySelector('.cdp-upload-help');
        const chooseBtn = document.getElementById('cdpChooseImage');
        const okBtn = document.getElementById('cdpUploadOK');

        closeBtn.addEventListener('click', closeUploadModal);
        chooseBtn.addEventListener('click', triggerFileInput);
        okBtn.addEventListener('click', insertImage);

        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleUploadTutorialClick);
        }
    }

    function handleUploadTutorialClick() {
        if (typeof window.cdpUploadTutorialHandler === 'function') {
            try {
                window.cdpUploadTutorialHandler();
                return;
            } catch (err) {
                console.error('Upload tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpUploadTutorialUrl || FALLBACK_UPLOAD_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showUploadTutorialToast('🎬 Upload tutorial coming soon');
    }

    function showUploadTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpUploadTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpUploadTutorialToast';
            toast.className = 'cdp-upload-tutorial-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (uploadTutorialToastTimeout) {
            clearTimeout(uploadTutorialToastTimeout);
        }

        uploadTutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2800);
    }

    function triggerFileInput() {
        const input = ensureUploadFileInput();
        input.value = '';

        if (typeof input.showPicker === 'function') {
            input.showPicker();
            return;
        }

        input.click();
    }

    function ensureUploadFileInput() {
        if (uploadFileInput && document.body.contains(uploadFileInput)) {
            return uploadFileInput;
        }

        uploadFileInput = document.createElement('input');
        uploadFileInput.type = 'file';
        uploadFileInput.accept = 'image/*';
        uploadFileInput.setAttribute('aria-hidden', 'true');
        uploadFileInput.tabIndex = -1;
        uploadFileInput.style.position = 'fixed';
        uploadFileInput.style.left = '-9999px';
        uploadFileInput.style.top = '0';
        uploadFileInput.style.width = '1px';
        uploadFileInput.style.height = '1px';
        uploadFileInput.style.opacity = '0';
        uploadFileInput.style.pointerEvents = 'none';
        uploadFileInput.addEventListener('change', handleFileSelect);
        document.body.appendChild(uploadFileInput);

        return uploadFileInput;
    }

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                // Check image quality
                const quality = checkImageQuality(img);
                
                // Show preview
                const preview = document.getElementById('cdpUploadPreview');
                preview.innerHTML = `
                    <img src="${event.target.result}" style="max-width: 100%; max-height: 200px;">
                    <p style="margin-top: 10px; font-size: calc(var(--cdp-font-scale, 1) * 12px); color: ${quality.color};">
                        <i class="fa-solid ${quality.icon}"></i> ${quality.message}
                    </p>
                `;

                // Store original and optimized
                currentUploadedImage = {
                    name: file.name || 'Uploaded Image',
                    type: file.type || 'image/jpeg',
                    original: event.target.result,
                    optimized: quality.needsOptimization ? optimizeImage(img) : event.target.result,
                    width: img.width,
                    height: img.height
                };

                // Enable OK button
                document.getElementById('cdpUploadOK').disabled = false;
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }

    function checkImageQuality(img) {
        const width = img.width;
        const height = img.height;
        const pixels = width * height;

        if (pixels < 100000) { // Less than 100k pixels
            return {
                message: 'Low quality image. Better quality recommended.',
                color: '#f59e0b',
                icon: 'fa-triangle-exclamation',
                needsOptimization: false
            };
        } else if (pixels > 2000000) { // More than 2M pixels
            return {
                message: 'Large image detected. Will create optimized copy.',
                color: '#d9a300',
                icon: 'fa-circle-info',
                needsOptimization: true
            };
        } else {
            return {
                message: 'Good quality image.',
                color: '#22c55e',
                icon: 'fa-circle-check',
                needsOptimization: false
            };
        }
    }

    function optimizeImage(img) {
        const maxWidth = 1200;
        const maxHeight = 1200;
        
        let width = img.width;
        let height = img.height;

        if (width > maxWidth || height > maxHeight) {
            const ratio = Math.min(maxWidth / width, maxHeight / height);
            width = width * ratio;
            height = height * ratio;
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        
        return canvas.toDataURL('image/jpeg', 0.85);
    }

    function insertImage() {
        if (!currentUploadedImage) {
            console.error('No image uploaded');
            return;
        }

        const view = window.cdpState?.currentView || 'front';
        console.log('Current view:', view);
        
        const printBox = document.querySelector(`.cdp-print-box[data-view="${view}"]`);
        console.log('Print box found:', printBox);
        
        if (!printBox) {
            console.error('Print box not found for view:', view);
            // Try to find any visible print box
            const visibleBox = document.querySelector('.cdp-print-box:not([style*="display: none"])');
            if (visibleBox) {
                console.log('Using visible print box instead');
                insertImageIntoBox(visibleBox, view);
                return;
            }
            return;
        }
        
        insertImageIntoBox(printBox, view);
    }
    
    function insertImageIntoBox(printBox, view) {

        console.log('Creating image element...');
        
        // Create image element
        const imgEl = document.createElement('div');
        imgEl.className = 'cdp-uploaded-image';
        imgEl.id = 'uploaded-' + Date.now();
        imgEl.style.position = 'absolute';
        imgEl.style.left = '50%';
        imgEl.style.top = '50%';
        imgEl.style.transform = 'translate(-50%, -50%)';
        imgEl.style.transformOrigin = 'center';
        imgEl.style.cursor = 'grab';
        imgEl.style.zIndex = 9999;
        imgEl.style.userSelect = 'none';
        imgEl.style.pointerEvents = 'auto';

        const img = document.createElement('img');
        img.src = currentUploadedImage.optimized;
        img.style.width = '200px';
        img.style.height = 'auto';
        img.style.display = 'block';
        img.style.pointerEvents = 'none';

        imgEl.appendChild(img);
        
        // ذخیره backup اولیه برای این عکس
        imgEl.dataset.originalBackup = currentUploadedImage.optimized;
        imgEl.dataset.originalSrc = currentUploadedImage.original;
        imgEl.dataset.optimizedSrc = currentUploadedImage.optimized;
        imgEl.dataset.uploadName = currentUploadedImage.name || 'Uploaded Image';
        imgEl.dataset.uploadType = currentUploadedImage.type || 'image/jpeg';
        
        printBox.appendChild(imgEl);
        
        console.log('Image element added to print box');

        // Add to layer system
        const layerData = window.cdpLayers ? window.cdpLayers.addLayer({
            element: imgEl,
            name: 'Uploaded Image',
            type: 'upload',
            view: view
        }) : null;
        
        console.log('Layer data:', layerData);

        if (layerData) {
            layerData.originalSrc = currentUploadedImage.original;
            layerData.width = 200;
        }

        // Setup drag
        setupImageDrag(imgEl, layerData);

        // Setup double click to edit
        imgEl.addEventListener('dblclick', () => openEditPanel(imgEl, layerData));

        console.log('Image inserted successfully');
        
        closeUploadModal();
        currentUploadedImage = null;
    }

    function closeUploadModal() {
        if (uploadModal) {
            uploadModal.style.display = 'none';
            document.getElementById('cdpUploadPreview').innerHTML = '<p>Preview</p>';
            document.getElementById('cdpUploadOK').disabled = true;
            currentUploadedImage = null;
        }
    }

    // =========================
    // Image Drag System
    // =========================

    function setupImageDrag(imgEl, layerData) {
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        function getEventPoint(event) {
            const source = event.touches?.[0] || event.changedTouches?.[0] || event;
            return {
                clientX: source.clientX,
                clientY: source.clientY
            };
        }

        function attachDoubleTapHandler(element, onActivate, isBlocked = () => false) {
            let lastTapTime = 0;
            let lastTapX = 0;
            let lastTapY = 0;

            element.addEventListener('touchend', function(e) {
                if (isBlocked()) {
                    lastTapTime = 0;
                    return;
                }

                const touch = e.changedTouches?.[0];
                if (!touch) return;

                const now = Date.now();
                const withinTime = now - lastTapTime <= 320;
                const withinDistance = Math.abs(touch.clientX - lastTapX) <= 24 && Math.abs(touch.clientY - lastTapY) <= 24;

                lastTapTime = now;
                lastTapX = touch.clientX;
                lastTapY = touch.clientY;

                if (!withinTime || !withinDistance) return;

                lastTapTime = 0;
                e.preventDefault();
                e.stopPropagation();
                onActivate(e);
            }, { passive: false });
        }

        const isLayerLocked = () => {
            if (layerData && typeof layerData.locked === 'boolean') {
                return Boolean(layerData.locked);
            }
            return imgEl.dataset.locked === 'true';
        };

        const refreshIdleCursor = () => {
            if (isLayerLocked()) {
                imgEl.style.cursor = 'not-allowed';
            } else if (!isDragging) {
                imgEl.style.cursor = 'grab';
            }
        };

        const nudgeLockHint = () => {
            imgEl.classList.remove('cdp-layer-locked-hint');
            // Force reflow so animation can retrigger
            void imgEl.offsetWidth;
            imgEl.classList.add('cdp-layer-locked-hint');
            setTimeout(() => imgEl.classList.remove('cdp-layer-locked-hint'), 350);
        };

        refreshIdleCursor();

        const handleImageDragStart = function(e) {
            if (e.target !== imgEl) return;

            if (isLayerLocked()) {
                e.preventDefault();
                nudgeLockHint();
                return;
            }
            if (e.type === 'mousedown' && e.button !== 0) return;
            
            isDragging = true;
            imgEl.style.cursor = 'grabbing';
            
            const point = getEventPoint(e);
            startX = point.clientX;
            startY = point.clientY;
            
            const rect = imgEl.getBoundingClientRect();
            const parent = imgEl.parentElement.getBoundingClientRect();
            initialLeft = rect.left - parent.left;
            initialTop = rect.top - parent.top;
            
            e.preventDefault();
        };

        imgEl.style.touchAction = 'none';
        imgEl.addEventListener('mousedown', handleImageDragStart);
        imgEl.addEventListener('pointerdown', handleImageDragStart);
        imgEl.addEventListener('touchstart', handleImageDragStart, { passive: false });

        const handleImageDragMove = function(e) {
            if (!isDragging) return;
            const point = getEventPoint(e);
            
            const dx = point.clientX - startX;
            const dy = point.clientY - startY;
            
            imgEl.style.left = (initialLeft + dx) + 'px';
            imgEl.style.top = (initialTop + dy) + 'px';
            imgEl.style.transform = imgEl.style.transform.replace(/translate\([^)]+\)/, '');
        };

        const handleImageDragEnd = function() {
            if (isDragging) {
                isDragging = false;
            }
            refreshIdleCursor();
        };

        document.addEventListener('mousemove', handleImageDragMove);
        document.addEventListener('pointermove', handleImageDragMove, { passive: false });
        document.addEventListener('touchmove', handleImageDragMove, { passive: false });
        document.addEventListener('mouseup', handleImageDragEnd);
        document.addEventListener('pointerup', handleImageDragEnd);
        document.addEventListener('pointercancel', handleImageDragEnd);
        document.addEventListener('touchend', handleImageDragEnd);
        document.addEventListener('touchcancel', handleImageDragEnd);

        attachDoubleTapHandler(imgEl, () => openEditPanel(imgEl, layerData), () => isDragging || isLayerLocked());

        const lockObserver = new MutationObserver(() => {
            refreshIdleCursor();
        });
        lockObserver.observe(imgEl, { attributes: true, attributeFilter: ['data-locked'] });
    }

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
        isCanvasHistoryPrimed = false;
        cropSourceCanvas = null;
        isCanvasReadyForOps = false;
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
    }

    function undoCanvasEdit() {
        if (!canvas || canvasHistoryIndex <= 0) return false;

        canvasHistoryIndex -= 1;
        applyCanvasSnapshot(canvasHistory[canvasHistoryIndex]);
        hasCanvasChanges = canvasHistoryIndex > 0;

        if (isErasing) {
            setupEraser();
        } else {
            removeCanvasHandlers();
        }
        return true;
    }

    function removeCanvasHandlers() {
        if (!canvas || !canvasHandlers) return;

        Object.entries(canvasHandlers).forEach(([event, handler]) => {
            canvas.removeEventListener(event, handler);
        });
        canvasHandlers = null;
    }

    // =========================
    // Edit Panel with Erase & Crop
    // =========================

    function openEditPanel(imgEl, layerData) {
        currentEditingImage = imgEl;
        currentLayer = layerData;
        resetCanvasHistory();
        removeCanvasHandlers();

        // Backup عکس فعلی قبل از Edit (ذخیره روی element)
        const img = imgEl.querySelector('img');
        if (img) {
            // ذخیره backup روی خود element
            if (!imgEl.dataset.originalBackup) {
                imgEl.dataset.originalBackup = img.src;
            }
            originalImageBackup = img.src;
            console.log('Backup saved:', originalImageBackup.substring(0, 50) + '...');
        }

        if (editPanel) {
            editPanel.setAttribute('data-visible', 'true');
            updateEditPreview();
            setupCanvas();
            return;
        }

        createEditPanel();
        updateEditPreview();
        setupCanvas();
    }

    function createEditPanel() {
        editPanel = document.createElement('div');
        editPanel.className = 'cdp-image-edit-panel';
        editPanel.setAttribute('data-visible', 'false');
        
        editPanel.innerHTML = `
            <div class="cdp-edit-content">
                <div class="cdp-edit-header">
                    <h3>Edit Image</h3>
                    <button type="button" class="cdp-edit-close">&times;</button>
                </div>
                
                <div class="cdp-edit-transform">
                    <button type="button" class="cdp-transform-btn" data-action="rotate-right" title="Rotate Right">↻</button>
                    <button type="button" class="cdp-transform-btn" data-action="rotate-left" title="Rotate Left">↺</button>
                    <button type="button" class="cdp-transform-btn" data-action="rotate-90" title="Rotate 90°">90°</button>
                    <button type="button" class="cdp-transform-btn" data-action="flip-vertical" title="Flip Vertical">⇅</button>
                    <button type="button" class="cdp-transform-btn" data-action="flip-horizontal" title="Flip Horizontal">⇄</button>
                </div>
                
                <div class="cdp-edit-body">
                    <div class="cdp-edit-group">
                        <label>Width: <span id="cdpImageWidthValue">200</span>px</label>
                        <input type="range" id="cdpImageWidthSlider" min="50" max="600" value="200" step="10">
                    </div>
                    
                    <div class="cdp-edit-tools">
                        <button type="button" class="cdp-tool-btn" id="cdpEraseBtn" title="Erase">
                            <i class="fa-solid fa-eraser"></i> Erase
                        </button>
                        <button type="button" class="cdp-tool-btn" id="cdpCropBtn" title="Crop">
                            <i class="fa-solid fa-crop"></i> Crop
                        </button>
                    </div>
                    
                    <div class="cdp-eraser-controls" id="cdpEraserControls" style="display: none;">
                        <label>Eraser Size: <span id="cdpEraserSizeValue">20</span>px</label>
                        <input type="range" id="cdpEraserSizeSlider" min="5" max="100" value="20" step="5">
                    </div>
                    
                    <div class="cdp-filter-shell">
                        <div class="cdp-filter-header">
                            <span>Filters</span>
                            <div class="cdp-filter-actions">
                                <button type="button" class="cdp-tool-btn cdp-filter-toggle" id="cdpFilterToggle">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    Filter Studio
                                </button>
                                <button type="button" class="cdp-filter-reset" data-filter-action="reset">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="cdp-filter-workspace" id="cdpFilterWorkspace" data-visible="false">
                        <div class="cdp-filter-workspace-panel">
                            <div class="cdp-filter-panel-top">
                                <div class="cdp-filter-panel-title">
                                    <p>Filter Studio</p>
                                    <h3>Creative presets</h3>
                                </div>
                                <div class="cdp-filter-panel-actions">
                                    <div class="cdp-filter-zoom-controls">
                                        <button type="button" class="cdp-filter-zoom-btn" data-filter-zoom="out" title="Zoom out">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <span id="cdpFilterZoomValue">100%</span>
                                        <button type="button" class="cdp-filter-zoom-btn" data-filter-zoom="in" title="Zoom in">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="cdp-filter-undo" data-filter-action="undo" title="Undo (Ctrl+Z)">
                                        <i class="fa-solid fa-arrow-rotate-left"></i>
                                        Undo
                                    </button>
                                    <button type="button" class="cdp-filter-apply" id="cdpFilterApply">
                                        <i class="fa-solid fa-check"></i>
                                        OK
                                    </button>
                                    <button type="button" class="cdp-filter-close" id="cdpFilterClose" aria-label="Close Filter Studio">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="cdp-filter-panel-body">
                                <aside class="cdp-filter-list">
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="brightness">Brightness / Contrast</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="hue">Hue / Saturation</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="exposure">Exposure</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="vibrance">Vibrance</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="color-balance">Color Balance</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="bw">Black &amp; White</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="blur">Gaussian Blur</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="levels">Levels</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="curves">Curves</button>
                                    <button type="button" class="cdp-filter-menu-btn" data-filter-preset="light-color">Light &amp; Color</button>
                                </aside>
                                <div class="cdp-filter-preview-area">
                                    <div class="cdp-filter-preview-toolbar">
                                        <p class="cdp-filter-hint">
                                            Pick any preset from the left. The live preview appears in the center. Reset takes you back to your original image.
                                        </p>
                                    </div>
                                    <div class="cdp-filter-preview-layout">
                                        <div class="cdp-filter-preview-stage">
                                            <div class="cdp-filter-preview-frame" id="cdpFilterPreviewFrame">
                                                <canvas id="cdpImageEditCanvas"></canvas>
                                            </div>
                                        </div>
                                        <aside class="cdp-filter-properties-card" id="cdpFilterPropertiesCard" data-visible="false" aria-hidden="true">
                                            <div class="cdp-filter-properties-header">
                                                <p>Properties</p>
                                                <h4 id="cdpFilterPropertiesTitle">Brightness / contrast</h4>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="brightness">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-slider" data-filter-control="brightness">
                                                        <label for="cdpBrightnessSlider">Brightness</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBrightnessSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpBrightnessInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="contrast">
                                                        <label for="cdpContrastSlider">Contrast</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpContrastSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpContrastInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="exposure">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-slider" data-filter-control="exposure">
                                                        <label for="cdpExposureSlider">Exposure</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpExposureSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpExposureInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="exposure-offset">
                                                        <label for="cdpExposureOffsetSlider">Offset</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpExposureOffsetSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpExposureOffsetInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="exposure-gamma">
                                                        <label for="cdpExposureGammaSlider">Gamma</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpExposureGammaSlider" min="0.1" max="3" value="1" step="0.05">
                                                            <input type="number" id="cdpExposureGammaInput" min="0.1" max="3" value="1" step="0.05">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="vibrance">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-slider" data-filter-control="vibrance">
                                                        <label for="cdpVibranceSlider">Vibrance</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpVibranceSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpVibranceInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="vibrance-saturation">
                                                        <label for="cdpVibranceSaturationSlider">Saturation</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpVibranceSaturationSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpVibranceSaturationInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="blur">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-slider" data-filter-control="gaussian-blur">
                                                        <label for="cdpBlurSlider">Blur</label>
                                                        <div class="cdp-filter-slider-inputs cdp-blur-slider-inputs">
                                                            <input type="range" id="cdpBlurSlider" min="0" max="20" value="0" step="0.1">
                                                            <span class="cdp-blur-value-pill">
                                                                <input type="number" id="cdpBlurInput" min="0" max="20" value="0" step="0.1" aria-label="Blur radius in pixels">
                                                                <span class="cdp-blur-value-unit">px</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="color-balance">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-select-row">
                                                        <label for="cdpColorBalanceToneSelect">Tone</label>
                                                        <select id="cdpColorBalanceToneSelect">
                                                            <option value="shadows">Shadows</option>
                                                            <option value="midtones" selected>Midtones</option>
                                                            <option value="highlights">Highlights</option>
                                                        </select>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="color-balance-cyan-red">
                                                        <label for="cdpColorBalanceCyanRedSlider">Cyan / red</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpColorBalanceCyanRedSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpColorBalanceCyanRedInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="color-balance-magenta-green">
                                                        <label for="cdpColorBalanceMagentaGreenSlider">Magenta / green</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpColorBalanceMagentaGreenSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpColorBalanceMagentaGreenInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="color-balance-yellow-blue">
                                                        <label for="cdpColorBalanceYellowBlueSlider">Yellow / blue</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpColorBalanceYellowBlueSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpColorBalanceYellowBlueInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <label class="cdp-filter-toggle">
                                                        <input type="checkbox" id="cdpColorBalancePreserveLuminosity" checked>
                                                        <span>Preserve luminosity</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="levels">
                                                <div class="cdp-filter-properties-section cdp-levels-panel">
                                                    <div class="cdp-levels-channel-switch">
                                                        <button type="button" class="cdp-levels-channel-btn" data-levels-channel="rgb" aria-pressed="true">
                                                            <span class="cdp-levels-channel-dot" style="--dot-color:#1f2937;"></span>
                                                            RGB
                                                        </button>
                                                        <button type="button" class="cdp-levels-channel-btn" data-levels-channel="red">
                                                            <span class="cdp-levels-channel-dot" style="--dot-color:#ef4444;"></span>
                                                            R
                                                        </button>
                                                        <button type="button" class="cdp-levels-channel-btn" data-levels-channel="green">
                                                            <span class="cdp-levels-channel-dot" style="--dot-color:#22c55e;"></span>
                                                            G
                                                        </button>
                                                        <button type="button" class="cdp-levels-channel-btn" data-levels-channel="blue">
                                                            <span class="cdp-levels-channel-dot" style="--dot-color:#3b82f6;"></span>
                                                            B
                                                        </button>
                                                    </div>
                                                    <div class="cdp-levels-histogram" aria-hidden="true">
                                                        <div class="cdp-levels-histogram-graph"></div>
                                                    </div>
                                                    <div class="cdp-levels-control-grid">
                                                        <div class="cdp-levels-control" data-levels-control="input-black">
                                                            <label for="cdpLevelsInputBlackSlider">Shadows</label>
                                                            <div class="cdp-filter-slider-inputs">
                                                                <input type="range" id="cdpLevelsInputBlackSlider" min="0" max="255" value="0" step="1">
                                                                <input type="number" id="cdpLevelsInputBlackInput" min="0" max="254" value="0" step="1">
                                                            </div>
                                                        </div>
                                                        <div class="cdp-levels-control" data-levels-control="input-mid">
                                                            <label for="cdpLevelsInputMidSlider">Midtones</label>
                                                            <div class="cdp-filter-slider-inputs">
                                                                <input type="range" id="cdpLevelsInputMidSlider" min="0.1" max="4" value="1" step="0.01">
                                                                <input type="number" id="cdpLevelsInputMidInput" min="0.1" max="4" value="1" step="0.01">
                                                            </div>
                                                        </div>
                                                        <div class="cdp-levels-control" data-levels-control="input-white">
                                                            <label for="cdpLevelsInputWhiteSlider">Highlights</label>
                                                            <div class="cdp-filter-slider-inputs">
                                                                <input type="range" id="cdpLevelsInputWhiteSlider" min="1" max="255" value="255" step="1">
                                                                <input type="number" id="cdpLevelsInputWhiteInput" min="1" max="255" value="255" step="1">
                                                            </div>
                                                        </div>
                                                        <div class="cdp-levels-control" data-levels-control="output-black">
                                                            <label for="cdpLevelsOutputBlackSlider">Output black</label>
                                                            <div class="cdp-filter-slider-inputs">
                                                                <input type="range" id="cdpLevelsOutputBlackSlider" min="0" max="254" value="0" step="1">
                                                                <input type="number" id="cdpLevelsOutputBlackInput" min="0" max="254" value="0" step="1">
                                                            </div>
                                                        </div>
                                                        <div class="cdp-levels-control" data-levels-control="output-white">
                                                            <label for="cdpLevelsOutputWhiteSlider">Output white</label>
                                                            <div class="cdp-filter-slider-inputs">
                                                                <input type="range" id="cdpLevelsOutputWhiteSlider" min="1" max="255" value="255" step="1">
                                                                <input type="number" id="cdpLevelsOutputWhiteInput" min="1" max="255" value="255" step="1">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="curves">
                                                <div class="cdp-filter-properties-section cdp-curves-panel">
                                                    <div class="cdp-curves-toolbar">
                                                        <div class="cdp-curves-mode-switch" role="group" aria-label="Curves mode">
                                                            <button type="button" class="cdp-curves-mode-btn active" data-curves-mode="points" aria-pressed="true">
                                                                <i class="fa-solid fa-wave-square"></i>
                                                                Points
                                                            </button>
                                                            <button type="button" class="cdp-curves-mode-btn" data-curves-mode="draw" aria-pressed="false" disabled>
                                                                <i class="fa-solid fa-pen"></i>
                                                                Draw
                                                            </button>
                                                            <button type="button" class="cdp-curves-mode-btn" data-curves-mode="auto" aria-pressed="false" disabled title="Coming soon">
                                                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                                                Auto
                                                            </button>
                                                        </div>
                                                        <div class="cdp-curves-channel-switch" role="group" aria-label="Curves channel">
                                                            <button type="button" class="cdp-curves-channel-btn active" data-curves-channel="rgb" aria-pressed="true">
                                                                <span class="cdp-curves-channel-dot" style="--dot-color:#111827;"></span>
                                                                RGB
                                                            </button>
                                                            <button type="button" class="cdp-curves-channel-btn" data-curves-channel="red" aria-pressed="false">
                                                                <span class="cdp-curves-channel-dot" style="--dot-color:#ef4444;"></span>
                                                                R
                                                            </button>
                                                            <button type="button" class="cdp-curves-channel-btn" data-curves-channel="green" aria-pressed="false">
                                                                <span class="cdp-curves-channel-dot" style="--dot-color:#22c55e;"></span>
                                                                G
                                                            </button>
                                                            <button type="button" class="cdp-curves-channel-btn" data-curves-channel="blue" aria-pressed="false">
                                                                <span class="cdp-curves-channel-dot" style="--dot-color:#3b82f6;"></span>
                                                                B
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="cdp-curves-graph">
                                                        <canvas id="cdpCurvesCanvas" width="320" height="220" aria-label="Curves graph"></canvas>
                                                    </div>
                                                    <div class="cdp-curves-meta-row">
                                                        <div class="cdp-curves-meta-block">
                                                            <p>Input</p>
                                                            <span id="cdpCurvesInputValue">—</span>
                                                        </div>
                                                        <div class="cdp-curves-meta-block">
                                                            <p>Output</p>
                                                            <span id="cdpCurvesOutputValue">—</span>
                                                        </div>
                                                        <button type="button" class="cdp-curves-delete-point" id="cdpCurvesDeletePoint" disabled>
                                                            <i class="fa-regular fa-trash-can"></i>
                                                            Delete point
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="light-color">
                                                <div class="cdp-filter-properties-section cdp-light-color-panel">
                                                    <div class="cdp-light-color-chip-group" role="group" aria-label="Light & color quick actions">
                                                        <button type="button" class="cdp-light-color-chip" data-light-color-action="auto" disabled>Auto</button>
                                                        <button type="button" class="cdp-light-color-chip" data-light-color-action="bw" disabled>B&amp;W</button>
                                                    </div>
                                                    <div class="cdp-light-color-block" data-light-color-block="light">
                                                        <div class="cdp-light-color-block-header">
                                                            <p>Light</p>
                                                        </div>
                                                        <div class="cdp-light-color-slider-grid">
                                                            <div class="cdp-filter-slider" data-light-color-control="exposure">
                                                                <label for="cdpLightColorExposureSlider">Exposure</label>
                                                                <div class="cdp-filter-slider-inputs">
                                                                    <input type="range" id="cdpLightColorExposureSlider" min="-5" max="5" value="0" step="0.05">
                                                                    <input type="number" id="cdpLightColorExposureInput" min="-5" max="5" value="0" step="0.05">
                                                                </div>
                                                            </div>
                                                            <div class="cdp-filter-slider" data-light-color-control="contrast">
                                                                <label for="cdpLightColorContrastSlider">Contrast</label>
                                                                <div class="cdp-filter-slider-inputs">
                                                                    <input type="range" id="cdpLightColorContrastSlider" min="-100" max="100" value="0" step="1">
                                                                    <input type="number" id="cdpLightColorContrastInput" min="-100" max="100" value="0" step="1">
                                                                </div>
                                                            </div>
                                                            <div class="cdp-filter-slider" data-light-color-control="highlights">
                                                                <label for="cdpLightColorHighlightsSlider">Highlights</label>
                                                                <div class="cdp-filter-slider-inputs">
                                                                    <input type="range" id="cdpLightColorHighlightsSlider" min="-100" max="100" value="0" step="1">
                                                                    <input type="number" id="cdpLightColorHighlightsInput" min="-100" max="100" value="0" step="1">
                                                                </div>
                                                            </div>
                                                            <div class="cdp-filter-slider" data-light-color-control="shadows">
                                                                <label for="cdpLightColorShadowsSlider">Shadows</label>
                                                                <div class="cdp-filter-slider-inputs">
                                                                    <input type="range" id="cdpLightColorShadowsSlider" min="-100" max="100" value="0" step="1">
                                                                    <input type="number" id="cdpLightColorShadowsInput" min="-100" max="100" value="0" step="1">
                                                                </div>
                                                            </div>
                                                            <div class="cdp-filter-slider" data-light-color-control="whites">
                                                                <label for="cdpLightColorWhitesSlider">Whites</label>
                                                                <div class="cdp-filter-slider-inputs">
                                                                    <input type="range" id="cdpLightColorWhitesSlider" min="-100" max="100" value="0" step="1">
                                                                    <input type="number" id="cdpLightColorWhitesInput" min="-100" max="100" value="0" step="1">
                                                                </div>
                                                            </div>
                                                            <div class="cdp-filter-slider" data-light-color-control="blacks">
                                                                <label for="cdpLightColorBlacksSlider">Blacks</label>
                                                                <div class="cdp-filter-slider-inputs">
                                                                    <input type="range" id="cdpLightColorBlacksSlider" min="-100" max="100" value="0" step="1">
                                                                    <input type="number" id="cdpLightColorBlacksInput" min="-100" max="100" value="0" step="1">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="cdp-light-color-accordion-group">
                                                        <div class="cdp-light-color-accordion" data-disabled="true">
                                                            <p>Color</p>
                                                            <span>Coming soon</span>
                                                        </div>
                                                        <div class="cdp-light-color-accordion" data-disabled="true">
                                                            <p>Effects</p>
                                                            <span>Coming soon</span>
                                                        </div>
                                                        <div class="cdp-light-color-accordion" data-disabled="true">
                                                            <p>Detail</p>
                                                            <span>Coming soon</span>
                                                        </div>
                                                        <div class="cdp-light-color-accordion" data-disabled="true">
                                                            <p>Optics</p>
                                                            <span>Coming soon</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="bw">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-slider" data-filter-control="bw-reds">
                                                        <label for="cdpBlackWhiteRedsSlider">Reds</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBlackWhiteRedsSlider" min="0" max="200" value="100" step="1">
                                                            <input type="number" id="cdpBlackWhiteRedsInput" min="0" max="200" value="100" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="bw-yellows">
                                                        <label for="cdpBlackWhiteYellowsSlider">Yellows</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBlackWhiteYellowsSlider" min="0" max="200" value="100" step="1">
                                                            <input type="number" id="cdpBlackWhiteYellowsInput" min="0" max="200" value="100" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="bw-greens">
                                                        <label for="cdpBlackWhiteGreensSlider">Greens</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBlackWhiteGreensSlider" min="0" max="200" value="100" step="1">
                                                            <input type="number" id="cdpBlackWhiteGreensInput" min="0" max="200" value="100" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="bw-cyans">
                                                        <label for="cdpBlackWhiteCyansSlider">Cyans</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBlackWhiteCyansSlider" min="0" max="200" value="100" step="1">
                                                            <input type="number" id="cdpBlackWhiteCyansInput" min="0" max="200" value="100" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="bw-blues">
                                                        <label for="cdpBlackWhiteBluesSlider">Blues</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBlackWhiteBluesSlider" min="0" max="200" value="100" step="1">
                                                            <input type="number" id="cdpBlackWhiteBluesInput" min="0" max="200" value="100" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="bw-magentas">
                                                        <label for="cdpBlackWhiteMagentasSlider">Magentas</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpBlackWhiteMagentasSlider" min="0" max="200" value="100" step="1">
                                                            <input type="number" id="cdpBlackWhiteMagentasInput" min="0" max="200" value="100" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-tint-row">
                                                        <label class="cdp-filter-toggle">
                                                            <input type="checkbox" id="cdpBlackWhiteTintToggle">
                                                            <span>Tint</span>
                                                        </label>
                                                        <input type="color" id="cdpBlackWhiteTintColor" value="#f4efe5" aria-label="Tint color" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cdp-filter-properties-content" data-filter-panel="hue">
                                                <div class="cdp-filter-properties-section">
                                                    <div class="cdp-filter-select-row">
                                                        <label for="cdpHueTargetSelect">Hue / saturation</label>
                                                        <select id="cdpHueTargetSelect">
                                                            <option value="master">Master</option>
                                                        </select>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="hue">
                                                        <label for="cdpHueSlider">Hue</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpHueSlider" min="-180" max="180" value="0" step="1">
                                                            <input type="number" id="cdpHueInput" min="-180" max="180" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="hue-sat">
                                                        <label for="cdpHueSaturationSlider">Saturation</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpHueSaturationSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpHueSaturationInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <div class="cdp-filter-slider" data-filter-control="hue-lightness">
                                                        <label for="cdpHueLightnessSlider">Lightness</label>
                                                        <div class="cdp-filter-slider-inputs">
                                                            <input type="range" id="cdpHueLightnessSlider" min="-100" max="100" value="0" step="1">
                                                            <input type="number" id="cdpHueLightnessInput" min="-100" max="100" value="0" step="1">
                                                        </div>
                                                    </div>
                                                    <label class="cdp-filter-toggle">
                                                        <input type="checkbox" id="cdpHueColorizeToggle">
                                                        <span>Colorize</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="button" class="cdp-filter-properties-reset" id="cdpFilterPropertiesReset">
                                                <i class="fa-solid fa-rotate-left"></i>
                                                Reset
                                            </button>
                                            <small class="cdp-filter-properties-hint" id="cdpFilterPropertiesHint">Adjust sliders, then press OK to keep the changes.</small>
                                        </aside>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="cdp-edit-footer">
                    <button type="button" class="cdp-btn cdp-btn-secondary cdp-btn-cancel">Cancel</button>
                    <button type="button" class="cdp-btn cdp-btn-primary cdp-btn-apply">Apply</button>
                </div>
            </div>
        `;

        document.body.appendChild(editPanel);
        addEditPanelStyles();
        setupEditEvents();
    }

    function setupEditEvents() {
        const closeBtn = editPanel.querySelector('.cdp-edit-close');
        const cancelBtn = editPanel.querySelector('.cdp-btn-cancel');
        const applyBtn = editPanel.querySelector('.cdp-btn-apply');
        const widthSlider = document.getElementById('cdpImageWidthSlider');
        const widthValue = document.getElementById('cdpImageWidthValue');
        const eraseBtn = document.getElementById('cdpEraseBtn');
        const cropBtn = document.getElementById('cdpCropBtn');
        const eraserControls = document.getElementById('cdpEraserControls');
        const eraserSizeSlider = document.getElementById('cdpEraserSizeSlider');
        const eraserSizeValue = document.getElementById('cdpEraserSizeValue');
        const filterWorkspace = document.getElementById('cdpFilterWorkspace');
        const filterToggleBtn = document.getElementById('cdpFilterToggle');
        const filterCloseBtn = document.getElementById('cdpFilterClose');
        const filterApplyBtn = document.getElementById('cdpFilterApply');
        const filterPresetButtons = Array.from(editPanel.querySelectorAll('[data-filter-preset]'));
        const filterResetBtn = editPanel.querySelector('[data-filter-action="reset"]');
        const filterUndoBtn = editPanel.querySelector('[data-filter-action="undo"]');
        filterPropertiesCard = editPanel.querySelector('#cdpFilterPropertiesCard');
        filterPropertiesTitleEl = editPanel.querySelector('#cdpFilterPropertiesTitle');
        filterPropertiesHintEl = editPanel.querySelector('#cdpFilterPropertiesHint');
        filterPropertiesPanelEls = Array.from(editPanel.querySelectorAll('.cdp-filter-properties-content'));
        filterPropertiesResetBtn = editPanel.querySelector('#cdpFilterPropertiesReset');
        brightnessSliderEl = editPanel.querySelector('#cdpBrightnessSlider');
        brightnessInputEl = editPanel.querySelector('#cdpBrightnessInput');
        contrastSliderEl = editPanel.querySelector('#cdpContrastSlider');
        contrastInputEl = editPanel.querySelector('#cdpContrastInput');
        hueTargetSelectEl = editPanel.querySelector('#cdpHueTargetSelect');
        hueSliderEl = editPanel.querySelector('#cdpHueSlider');
        hueInputEl = editPanel.querySelector('#cdpHueInput');
        hueSaturationSliderEl = editPanel.querySelector('#cdpHueSaturationSlider');
        hueSaturationInputEl = editPanel.querySelector('#cdpHueSaturationInput');
        hueLightnessSliderEl = editPanel.querySelector('#cdpHueLightnessSlider');
        hueLightnessInputEl = editPanel.querySelector('#cdpHueLightnessInput');
        hueColorizeToggleEl = editPanel.querySelector('#cdpHueColorizeToggle');
        exposureSliderEl = editPanel.querySelector('#cdpExposureSlider');
        exposureInputEl = editPanel.querySelector('#cdpExposureInput');
        exposureOffsetSliderEl = editPanel.querySelector('#cdpExposureOffsetSlider');
        exposureOffsetInputEl = editPanel.querySelector('#cdpExposureOffsetInput');
        exposureGammaSliderEl = editPanel.querySelector('#cdpExposureGammaSlider');
        exposureGammaInputEl = editPanel.querySelector('#cdpExposureGammaInput');
        vibranceSliderEl = editPanel.querySelector('#cdpVibranceSlider');
        vibranceInputEl = editPanel.querySelector('#cdpVibranceInput');
        vibranceSaturationSliderEl = editPanel.querySelector('#cdpVibranceSaturationSlider');
        vibranceSaturationInputEl = editPanel.querySelector('#cdpVibranceSaturationInput');
        blurSliderEl = editPanel.querySelector('#cdpBlurSlider');
        blurInputEl = editPanel.querySelector('#cdpBlurInput');
        levelsChannelBtnEls = Array.from(editPanel.querySelectorAll('[data-levels-channel]'));
        levelsInputBlackSliderEl = editPanel.querySelector('#cdpLevelsInputBlackSlider');
        levelsInputBlackInputEl = editPanel.querySelector('#cdpLevelsInputBlackInput');
        levelsInputMidSliderEl = editPanel.querySelector('#cdpLevelsInputMidSlider');
        levelsInputMidInputEl = editPanel.querySelector('#cdpLevelsInputMidInput');
        levelsInputWhiteSliderEl = editPanel.querySelector('#cdpLevelsInputWhiteSlider');
        levelsInputWhiteInputEl = editPanel.querySelector('#cdpLevelsInputWhiteInput');
        levelsOutputBlackSliderEl = editPanel.querySelector('#cdpLevelsOutputBlackSlider');
        levelsOutputBlackInputEl = editPanel.querySelector('#cdpLevelsOutputBlackInput');
        levelsOutputWhiteSliderEl = editPanel.querySelector('#cdpLevelsOutputWhiteSlider');
        levelsOutputWhiteInputEl = editPanel.querySelector('#cdpLevelsOutputWhiteInput');
        curvesModeButtons = Array.from(editPanel.querySelectorAll('[data-curves-mode]'));
        curvesChannelButtons = Array.from(editPanel.querySelectorAll('[data-curves-channel]'));
        curvesCanvasEl = editPanel.querySelector('#cdpCurvesCanvas');
        curvesInputValueEl = editPanel.querySelector('#cdpCurvesInputValue');
        curvesOutputValueEl = editPanel.querySelector('#cdpCurvesOutputValue');
        curvesDeletePointBtn = editPanel.querySelector('#cdpCurvesDeletePoint');
        if (curvesCanvasEl) {
            curvesCanvasCtx = curvesCanvasEl.getContext('2d');
            if (curvesCanvasCtx) {
                curvesCanvasEl.width = CURVES_CANVAS_WIDTH;
                curvesCanvasEl.height = CURVES_CANVAS_HEIGHT;
                curvesCanvasEl.addEventListener('pointerdown', handleCurvesPointerDown);
                bindCurvesPointerEvents();
            }
        }
        lightColorExposureSliderEl = editPanel.querySelector('#cdpLightColorExposureSlider');
        lightColorExposureInputEl = editPanel.querySelector('#cdpLightColorExposureInput');
        lightColorContrastSliderEl = editPanel.querySelector('#cdpLightColorContrastSlider');
        lightColorContrastInputEl = editPanel.querySelector('#cdpLightColorContrastInput');
        lightColorHighlightsSliderEl = editPanel.querySelector('#cdpLightColorHighlightsSlider');
        lightColorHighlightsInputEl = editPanel.querySelector('#cdpLightColorHighlightsInput');
        lightColorShadowsSliderEl = editPanel.querySelector('#cdpLightColorShadowsSlider');
        lightColorShadowsInputEl = editPanel.querySelector('#cdpLightColorShadowsInput');
        lightColorWhitesSliderEl = editPanel.querySelector('#cdpLightColorWhitesSlider');
        lightColorWhitesInputEl = editPanel.querySelector('#cdpLightColorWhitesInput');
        lightColorBlacksSliderEl = editPanel.querySelector('#cdpLightColorBlacksSlider');
        lightColorBlacksInputEl = editPanel.querySelector('#cdpLightColorBlacksInput');
        blackWhiteSliderMap = {};
        blackWhiteInputMap = {};
        BLACK_WHITE_CHANNELS.forEach((channel) => {
            const capitalized = channel.charAt(0).toUpperCase() + channel.slice(1);
            blackWhiteSliderMap[channel] = editPanel.querySelector(`#cdpBlackWhite${capitalized}Slider`);
            blackWhiteInputMap[channel] = editPanel.querySelector(`#cdpBlackWhite${capitalized}Input`);
        });
        blackWhiteTintToggleEl = editPanel.querySelector('#cdpBlackWhiteTintToggle');
        blackWhiteTintColorEl = editPanel.querySelector('#cdpBlackWhiteTintColor');
        colorBalanceToneSelectEl = editPanel.querySelector('#cdpColorBalanceToneSelect');
        colorBalanceCyanRedSliderEl = editPanel.querySelector('#cdpColorBalanceCyanRedSlider');
        colorBalanceCyanRedInputEl = editPanel.querySelector('#cdpColorBalanceCyanRedInput');
        colorBalanceMagentaGreenSliderEl = editPanel.querySelector('#cdpColorBalanceMagentaGreenSlider');
        colorBalanceMagentaGreenInputEl = editPanel.querySelector('#cdpColorBalanceMagentaGreenInput');
        colorBalanceYellowBlueSliderEl = editPanel.querySelector('#cdpColorBalanceYellowBlueSlider');
        colorBalanceYellowBlueInputEl = editPanel.querySelector('#cdpColorBalanceYellowBlueInput');
        colorBalancePreserveLuminosityToggleEl = editPanel.querySelector('#cdpColorBalancePreserveLuminosity');
        filterPreviewZoom = 1;
        cropButtonRef = cropBtn;

        closeBtn.addEventListener('click', cancelEdit);
        cancelBtn.addEventListener('click', cancelEdit);
        applyBtn.addEventListener('click', applyImageEdits);

        // Width slider
        widthSlider.addEventListener('input', function() {
            widthValue.textContent = this.value;
            if (currentEditingImage) {
                const img = currentEditingImage.querySelector('img');
                if (img) img.style.width = this.value + 'px';
            }
        });

        if (brightnessSliderEl) {
            brightnessSliderEl.addEventListener('input', (event) => {
                setBrightnessControlValue(event.target.value);
            });
        }
        if (brightnessInputEl) {
            brightnessInputEl.addEventListener('input', (event) => {
                setBrightnessControlValue(event.target.value);
            });
        }
        if (contrastSliderEl) {
            contrastSliderEl.addEventListener('input', (event) => {
                setContrastControlValue(event.target.value);
            });
        }
        if (contrastInputEl) {
            contrastInputEl.addEventListener('input', (event) => {
                setContrastControlValue(event.target.value);
            });
        }
        if (filterPropertiesResetBtn) {
            filterPropertiesResetBtn.addEventListener('click', () => {
                if (isFilterPropertiesMode('brightness')) {
                    handleBrightnessContrastReset();
                } else if (isFilterPropertiesMode('hue')) {
                    handleHueSaturationReset();
                } else if (isFilterPropertiesMode('exposure')) {
                    handleExposureReset();
                } else if (isFilterPropertiesMode('vibrance')) {
                    handleVibranceReset();
                } else if (isFilterPropertiesMode('blur')) {
                    handleBlurReset();
                } else if (isFilterPropertiesMode('color-balance')) {
                    handleColorBalanceReset();
                } else if (isFilterPropertiesMode('levels')) {
                    handleLevelsReset();
                } else if (isFilterPropertiesMode('curves')) {
                    handleCurvesReset();
                } else if (isFilterPropertiesMode('light-color')) {
                    handleLightColorReset();
                } else if (isFilterPropertiesMode('bw')) {
                    handleBlackWhiteReset();
                }
            });
        }
        if (hueSliderEl) {
            hueSliderEl.addEventListener('input', (event) => {
                setHueControlValue(event.target.value);
            });
        }
        if (hueInputEl) {
            hueInputEl.addEventListener('input', (event) => {
                setHueControlValue(event.target.value);
            });
        }
        if (hueSaturationSliderEl) {
            hueSaturationSliderEl.addEventListener('input', (event) => {
                setHueSaturationControlValue(event.target.value);
            });
        }
        if (hueSaturationInputEl) {
            hueSaturationInputEl.addEventListener('input', (event) => {
                setHueSaturationControlValue(event.target.value);
            });
        }
        if (hueLightnessSliderEl) {
            hueLightnessSliderEl.addEventListener('input', (event) => {
                setHueLightnessControlValue(event.target.value);
            });
        }
        if (hueLightnessInputEl) {
            hueLightnessInputEl.addEventListener('input', (event) => {
                setHueLightnessControlValue(event.target.value);
            });
        }
        if (hueColorizeToggleEl) {
            hueColorizeToggleEl.addEventListener('change', (event) => {
                setHueColorizeEnabled(event.target.checked);
            });
        }
        if (exposureSliderEl) {
            exposureSliderEl.addEventListener('input', (event) => {
                setExposureControlValue(event.target.value);
            });
        }
        if (exposureInputEl) {
            exposureInputEl.addEventListener('input', (event) => {
                setExposureControlValue(event.target.value);
            });
        }
        if (exposureOffsetSliderEl) {
            exposureOffsetSliderEl.addEventListener('input', (event) => {
                setExposureOffsetValue(event.target.value);
            });
        }
        if (exposureOffsetInputEl) {
            exposureOffsetInputEl.addEventListener('input', (event) => {
                setExposureOffsetValue(event.target.value);
            });
        }
        if (exposureGammaSliderEl) {
            exposureGammaSliderEl.addEventListener('input', (event) => {
                setExposureGammaValue(event.target.value);
            });
        }
        if (exposureGammaInputEl) {
            exposureGammaInputEl.addEventListener('input', (event) => {
                setExposureGammaValue(event.target.value);
            });
        }
        if (vibranceSliderEl) {
            vibranceSliderEl.addEventListener('input', (event) => {
                setVibranceControlValue(event.target.value);
            });
        }
        if (vibranceInputEl) {
            vibranceInputEl.addEventListener('input', (event) => {
                setVibranceControlValue(event.target.value);
            });
        }
        if (vibranceSaturationSliderEl) {
            vibranceSaturationSliderEl.addEventListener('input', (event) => {
                setVibranceSaturationControlValue(event.target.value);
            });
        }
        if (vibranceSaturationInputEl) {
            vibranceSaturationInputEl.addEventListener('input', (event) => {
                setVibranceSaturationControlValue(event.target.value);
            });
        }
        if (blurSliderEl) {
            blurSliderEl.addEventListener('input', (event) => {
                setBlurValue(event.target.value);
            });
        }
        if (blurInputEl) {
            blurInputEl.addEventListener('input', (event) => {
                setBlurValue(event.target.value);
            });
        }
        if (levelsChannelBtnEls?.length) {
            levelsChannelBtnEls.forEach((btn) => {
                btn.addEventListener('click', () => {
                    setLevelsActiveChannel(btn.getAttribute('data-levels-channel'));
                });
            });
        }
        if (levelsInputBlackSliderEl) {
            levelsInputBlackSliderEl.addEventListener('input', (event) => {
                setLevelsInputBlackValue(event.target.value);
            });
        }
        if (levelsInputBlackInputEl) {
            levelsInputBlackInputEl.addEventListener('input', (event) => {
                setLevelsInputBlackValue(event.target.value);
            });
        }
        if (levelsInputMidSliderEl) {
            levelsInputMidSliderEl.addEventListener('input', (event) => {
                setLevelsInputMidValue(event.target.value);
            });
        }
        if (levelsInputMidInputEl) {
            levelsInputMidInputEl.addEventListener('input', (event) => {
                setLevelsInputMidValue(event.target.value);
            });
        }
        if (levelsInputWhiteSliderEl) {
            levelsInputWhiteSliderEl.addEventListener('input', (event) => {
                setLevelsInputWhiteValue(event.target.value);
            });
        }
        if (levelsInputWhiteInputEl) {
            levelsInputWhiteInputEl.addEventListener('input', (event) => {
                setLevelsInputWhiteValue(event.target.value);
            });
        }
        if (levelsOutputBlackSliderEl) {
            levelsOutputBlackSliderEl.addEventListener('input', (event) => {
                setLevelsOutputBlackValue(event.target.value);
            });
        }
        if (levelsOutputBlackInputEl) {
            levelsOutputBlackInputEl.addEventListener('input', (event) => {
                setLevelsOutputBlackValue(event.target.value);
            });
        }
        if (levelsOutputWhiteSliderEl) {
            levelsOutputWhiteSliderEl.addEventListener('input', (event) => {
                setLevelsOutputWhiteValue(event.target.value);
            });
        }
        if (levelsOutputWhiteInputEl) {
            levelsOutputWhiteInputEl.addEventListener('input', (event) => {
                setLevelsOutputWhiteValue(event.target.value);
            });
        }
        if (Array.isArray(curvesModeButtons) && curvesModeButtons.length) {
            curvesModeButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    setCurvesMode(btn.getAttribute('data-curves-mode'));
                });
            });
        }
        if (Array.isArray(curvesChannelButtons) && curvesChannelButtons.length) {
            curvesChannelButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    setCurvesActiveChannel(btn.getAttribute('data-curves-channel'));
                });
            });
        }
        if (curvesDeletePointBtn) {
            curvesDeletePointBtn.addEventListener('click', handleCurvesDeletePoint);
        }
        if (lightColorExposureSliderEl) {
            lightColorExposureSliderEl.addEventListener('input', (event) => {
                setLightColorExposureValue(event.target.value);
            });
        }
        if (lightColorExposureInputEl) {
            lightColorExposureInputEl.addEventListener('input', (event) => {
                setLightColorExposureValue(event.target.value);
            });
        }
        if (lightColorContrastSliderEl) {
            lightColorContrastSliderEl.addEventListener('input', (event) => {
                setLightColorContrastValue(event.target.value);
            });
        }
        if (lightColorContrastInputEl) {
            lightColorContrastInputEl.addEventListener('input', (event) => {
                setLightColorContrastValue(event.target.value);
            });
        }
        if (lightColorHighlightsSliderEl) {
            lightColorHighlightsSliderEl.addEventListener('input', (event) => {
                setLightColorHighlightsValue(event.target.value);
            });
        }
        if (lightColorHighlightsInputEl) {
            lightColorHighlightsInputEl.addEventListener('input', (event) => {
                setLightColorHighlightsValue(event.target.value);
            });
        }
        if (lightColorShadowsSliderEl) {
            lightColorShadowsSliderEl.addEventListener('input', (event) => {
                setLightColorShadowsValue(event.target.value);
            });
        }
        if (lightColorShadowsInputEl) {
            lightColorShadowsInputEl.addEventListener('input', (event) => {
                setLightColorShadowsValue(event.target.value);
            });
        }
        if (lightColorWhitesSliderEl) {
            lightColorWhitesSliderEl.addEventListener('input', (event) => {
                setLightColorWhitesValue(event.target.value);
            });
        }
        if (lightColorWhitesInputEl) {
            lightColorWhitesInputEl.addEventListener('input', (event) => {
                setLightColorWhitesValue(event.target.value);
            });
        }
        if (lightColorBlacksSliderEl) {
            lightColorBlacksSliderEl.addEventListener('input', (event) => {
                setLightColorBlacksValue(event.target.value);
            });
        }
        if (lightColorBlacksInputEl) {
            lightColorBlacksInputEl.addEventListener('input', (event) => {
                setLightColorBlacksValue(event.target.value);
            });
        }
        updateCurvesUI();
        drawCurvesGraph();
        BLACK_WHITE_CHANNELS.forEach((channel) => {
            const slider = blackWhiteSliderMap[channel];
            const input = blackWhiteInputMap[channel];
            if (slider) {
                slider.addEventListener('input', (event) => {
                    setBlackWhiteChannelValue(channel, event.target.value);
                });
            }
            if (input) {
                input.addEventListener('input', (event) => {
                    setBlackWhiteChannelValue(channel, event.target.value);
                });
            }
        });
        if (blackWhiteTintToggleEl) {
            blackWhiteTintToggleEl.addEventListener('change', (event) => {
                setBlackWhiteTintEnabled(event.target.checked);
            });
        }
        if (blackWhiteTintColorEl) {
            blackWhiteTintColorEl.addEventListener('input', (event) => {
                setBlackWhiteTintColor(event.target.value);
            });
        }
        if (colorBalanceToneSelectEl) {
            colorBalanceToneSelectEl.addEventListener('change', (event) => {
                setColorBalanceToneValue(event.target.value);
            });
        }
        if (colorBalanceCyanRedSliderEl) {
            colorBalanceCyanRedSliderEl.addEventListener('input', (event) => {
                setColorBalanceCyanRedValue(event.target.value);
            });
        }
        if (colorBalanceCyanRedInputEl) {
            colorBalanceCyanRedInputEl.addEventListener('input', (event) => {
                setColorBalanceCyanRedValue(event.target.value);
            });
        }
        if (colorBalanceMagentaGreenSliderEl) {
            colorBalanceMagentaGreenSliderEl.addEventListener('input', (event) => {
                setColorBalanceMagentaGreenValue(event.target.value);
            });
        }
        if (colorBalanceMagentaGreenInputEl) {
            colorBalanceMagentaGreenInputEl.addEventListener('input', (event) => {
                setColorBalanceMagentaGreenValue(event.target.value);
            });
        }
        if (colorBalanceYellowBlueSliderEl) {
            colorBalanceYellowBlueSliderEl.addEventListener('input', (event) => {
                setColorBalanceYellowBlueValue(event.target.value);
            });
        }
        if (colorBalanceYellowBlueInputEl) {
            colorBalanceYellowBlueInputEl.addEventListener('input', (event) => {
                setColorBalanceYellowBlueValue(event.target.value);
            });
        }
        if (colorBalancePreserveLuminosityToggleEl) {
            colorBalancePreserveLuminosityToggleEl.addEventListener('change', (event) => {
                setColorBalancePreserveLuminosity(event.target.checked);
            });
        }

        // Transform buttons
        editPanel.querySelectorAll('.cdp-transform-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                handleTransform(this.getAttribute('data-action'));
            });
        });

        // Erase button now opens the advanced workspace overlay
        eraseBtn.addEventListener('click', function() {
            isErasing = false;
            isCropping = false;
            this.classList.remove('active');
            cropBtn.classList.remove('active');
            eraserControls.style.display = 'none';
            removeCanvasHandlers();
            openAdvancedEraseWorkspace();
        });

        // Crop button now opens a dedicated workspace overlay
        cropBtn.addEventListener('click', function() {
            exitDrawingModes();
            openCropWorkspace();
        });

        // Eraser size
        eraserSizeSlider.addEventListener('input', function() {
            eraserSize = parseInt(this.value);
            eraserSizeValue.textContent = eraserSize;
        });

        const filterZoomButtons = Array.from(editPanel.querySelectorAll('[data-filter-zoom]'));
        const filterZoomValue = document.getElementById('cdpFilterZoomValue');

        const exitDrawingModes = () => {
            if (isErasing) {
                isErasing = false;
                removeCanvasHandlers();
                if (eraseBtn) eraseBtn.classList.remove('active');
                if (eraserControls) eraserControls.style.display = 'none';
            }
            closeCropWorkspace(false);
        };

        const updateFilterZoomDisplay = () => {
            if (filterZoomValue) {
                filterZoomValue.textContent = Math.round(filterPreviewZoom * 100) + '%';
            }
            const canvasEl = document.getElementById('cdpImageEditCanvas');
            if (canvasEl && filterWorkspace?.getAttribute('data-visible') === 'true') {
                canvasEl.style.transform = `scale(${filterPreviewZoom})`;
            } else if (canvasEl) {
                canvasEl.style.transform = '';
            }
        };

        const setFilterWorkspaceVisible = (visible) => {
            if (!filterWorkspace) return;
            filterWorkspace.setAttribute('data-visible', String(visible));
            if (filterToggleBtn) {
                filterToggleBtn.classList.toggle('active', visible);
            }
            const canvasEl = document.getElementById('cdpImageEditCanvas');
            if (canvasEl) {
                canvasEl.classList.toggle('cdp-filter-preview-active', visible);
                if (!visible) {
                    canvasEl.style.transform = '';
                }
            }
            if (!visible) {
                setFilterPropertiesMode(null);
            }
            if (visible) {
                exitDrawingModes();
                filterPreviewZoom = 1;
                updateFilterZoomDisplay();
            }
        };

        if (filterToggleBtn && filterWorkspace) {
            filterToggleBtn.addEventListener('click', () => {
                const isVisible = filterWorkspace.getAttribute('data-visible') === 'true';
                setFilterWorkspaceVisible(!isVisible);
            });
        }

        if (filterCloseBtn) {
            filterCloseBtn.addEventListener('click', () => {
                setFilterWorkspaceVisible(false);
            });
        }

        if (filterApplyBtn) {
            filterApplyBtn.addEventListener('click', () => {
                setFilterWorkspaceVisible(false);
            });
        }

        if (filterWorkspace) {
            filterWorkspace.addEventListener('click', (event) => {
                if (event.target === filterWorkspace) {
                    setFilterWorkspaceVisible(false);
                }
            });
        }

        filterZoomButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                if (!filterWorkspace) return;
                if (filterWorkspace.getAttribute('data-visible') !== 'true') {
                    setFilterWorkspaceVisible(true);
                }
                const direction = btn.getAttribute('data-filter-zoom');
                const delta = direction === 'in' ? 0.2 : -0.2;
                filterPreviewZoom = Math.min(3, Math.max(0.4, filterPreviewZoom + delta));
                updateFilterZoomDisplay();
            });
        });

        filterPresetButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                exitDrawingModes();
                if (filterWorkspace && filterWorkspace.getAttribute('data-visible') !== 'true') {
                    setFilterWorkspaceVisible(true);
                }
                filterPresetButtons.forEach(item => item.classList.remove('active'));
                btn.classList.add('active');
                const preset = btn.dataset.filterPreset;
                if (preset === 'brightness') {
                    activateBrightnessContrastPanel();
                    return;
                }
                if (preset === 'hue') {
                    activateHueSaturationPanel();
                    return;
                }
                if (preset === 'exposure') {
                    activateExposurePanel();
                    return;
                }
                if (preset === 'vibrance') {
                    activateVibrancePanel();
                    return;
                }
                if (preset === 'blur') {
                    activateBlurPanel();
                    return;
                }
                if (preset === 'color-balance') {
                    activateColorBalancePanel();
                    return;
                }
                if (preset === 'bw') {
                    activateBlackWhitePanel();
                    return;
                }
                if (preset === 'levels') {
                    activateLevelsPanel();
                    return;
                }
                if (preset === 'curves') {
                    activateCurvesPanel();
                    return;
                }
                if (preset === 'light-color') {
                    activateLightColorPanel();
                    return;
                }
                setFilterPropertiesMode(null);
                applyFilterPreset(preset);
            });
        });

        if (filterResetBtn) {
            filterResetBtn.addEventListener('click', () => {
                exitDrawingModes();
                filterPresetButtons.forEach(item => item.classList.remove('active'));
                const brightnessBtn = editPanel.querySelector('[data-filter-preset="brightness"]');
                const hueBtn = editPanel.querySelector('[data-filter-preset="hue"]');
                const exposureBtn = editPanel.querySelector('[data-filter-preset="exposure"]');
                const vibranceBtn = editPanel.querySelector('[data-filter-preset="vibrance"]');
                const blurBtn = editPanel.querySelector('[data-filter-preset="blur"]');
                const colorBalanceBtn = editPanel.querySelector('[data-filter-preset="color-balance"]');
                const levelBtn = editPanel.querySelector('[data-filter-preset="levels"]');
                const curvesBtn = editPanel.querySelector('[data-filter-preset="curves"]');
                const lightColorBtn = editPanel.querySelector('[data-filter-preset="light-color"]');
                const blackWhiteBtn = editPanel.querySelector('[data-filter-preset="bw"]');
                const activeMode = filterPropertiesMode;
                resetCanvasToOriginal(() => {
                    if (activeMode === 'brightness') {
                        withCanvasReady(() => {
                            captureBrightnessContrastBaseSnapshot(true);
                            handleBrightnessContrastReset({ skipRender: true });
                            renderBrightnessContrastAdjustments();
                            if (brightnessBtn) {
                                brightnessBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'hue') {
                        withCanvasReady(() => {
                            captureHueSaturationBaseSnapshot(true);
                            handleHueSaturationReset({ skipRender: true });
                            renderHueSaturationAdjustments();
                            if (hueBtn) {
                                hueBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'exposure') {
                        withCanvasReady(() => {
                            captureExposureBaseSnapshot(true);
                            handleExposureReset({ skipRender: true });
                            renderExposureAdjustments();
                            if (exposureBtn) {
                                exposureBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'vibrance') {
                        withCanvasReady(() => {
                            captureVibranceBaseSnapshot(true);
                            handleVibranceReset({ skipRender: true });
                            renderVibranceAdjustments();
                            if (vibranceBtn) {
                                vibranceBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'blur') {
                        withCanvasReady(() => {
                            captureBlurBaseSnapshot(true);
                            handleBlurReset({ skipRender: true });
                            renderBlurAdjustments();
                            if (blurBtn) {
                                blurBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'color-balance') {
                        withCanvasReady(() => {
                            captureColorBalanceBaseSnapshot(true);
                            handleColorBalanceReset({ skipRender: true });
                            renderColorBalanceAdjustments();
                            if (colorBalanceBtn) {
                                colorBalanceBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'levels') {
                        withCanvasReady(() => {
                            captureLevelsBaseSnapshot(true);
                            handleLevelsReset({ skipRender: true });
                            renderLevelsAdjustments();
                            if (levelBtn) {
                                levelBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'curves') {
                        handleCurvesReset({ silent: true });
                        updateCurvesUI();
                        if (curvesBtn) {
                            curvesBtn.classList.add('active');
                        }
                    } else if (activeMode === 'light-color') {
                        withCanvasReady(() => {
                            captureLightColorBaseSnapshot(true);
                            handleLightColorReset({ skipRender: true });
                            renderLightColorAdjustments();
                            if (lightColorBtn) {
                                lightColorBtn.classList.add('active');
                            }
                        });
                    } else if (activeMode === 'bw') {
                        withCanvasReady(() => {
                            captureBlackWhiteBaseSnapshot(true);
                            handleBlackWhiteReset({ skipRender: true });
                            renderBlackWhiteAdjustments();
                            if (blackWhiteBtn) {
                                blackWhiteBtn.classList.add('active');
                            }
                        });
                    } else {
                        setFilterPropertiesMode(null);
                    }
                });
                if (filterWorkspace) {
                    setFilterWorkspaceVisible(true);
                }
            });
        }

        if (filterUndoBtn) {
            filterUndoBtn.addEventListener('click', () => {
                exitDrawingModes();
                const undone = undoCanvasEdit();
                if (!undone) {
                    return;
                }
                if (filterWorkspace) {
                    setFilterWorkspaceVisible(true);
                }
                updateEditPreview();
            });
        }
    }

    function handleTransform(action) {
        if (!currentEditingImage) return;

        const currentTransform = currentEditingImage.style.transform || '';
        let rotation = 0;
        let scaleX = 1;
        let scaleY = 1;

        const rotateMatch = currentTransform.match(/rotate\((-?[\d.]+)deg\)/);
        const scaleMatch = currentTransform.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
        const translateMatch = currentTransform.match(/translate\([^)]*\)/);

        if (rotateMatch) rotation = parseFloat(rotateMatch[1]);
        if (scaleMatch) {
            scaleX = parseFloat(scaleMatch[1]);
            scaleY = parseFloat(scaleMatch[2]);
        }

        if (action === 'rotate-right') {
            rotation += 1;
        } else if (action === 'rotate-left') {
            rotation -= 1;
        } else if (action === 'rotate-90') {
            rotation += 90;
        } else if (action === 'flip-horizontal') {
            scaleX *= -1;
        } else if (action === 'flip-vertical') {
            scaleY *= -1;
        }

        const translatePart = translateMatch ? translateMatch[0] + ' ' : '';
        currentEditingImage.style.transformOrigin = 'center';
        currentEditingImage.style.transform = `${translatePart}rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`.trim();
        updateEditPreview();
    }

    function setupCanvas(onReady) {
        if (!currentEditingImage) return;

        canvas = document.getElementById('cdpImageEditCanvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');
        const img = currentEditingImage.querySelector('img');
        if (!img) return;

        removeCanvasHandlers();
        isCanvasReadyForOps = false;

        const tempImg = new Image();
        tempImg.onload = function() {
            canvas.width = tempImg.width;
            canvas.height = tempImg.height;
            setCanvasDisplaySize(tempImg.width, tempImg.height);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(tempImg, 0, 0);
            cropSourceCanvas = null;

            if (!isCanvasHistoryPrimed) {
                pushCanvasHistoryState();
                isCanvasHistoryPrimed = true;
            }

            isCanvasReadyForOps = true;
            if (isErasing) {
                setupEraser();
            } else {
                canvas.style.cursor = 'default';
            }

            if (typeof onReady === 'function') {
                onReady();
            }
        };
        tempImg.src = img.src;
    }

    function setupEraser() {
        if (!canvas || !ctx) return;

        removeCanvasHandlers();

        let isDrawing = false;

        canvas.style.cursor = 'crosshair';

        const mousedownHandler = () => {
            isDrawing = true;
        };
        const mouseupHandler = () => {
            if (!isDrawing) return;
            isDrawing = false;
            pushCanvasHistoryState();
        };
        const mouseleaveHandler = () => {
            if (!isDrawing) return;
            isDrawing = false;
            pushCanvasHistoryState();
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

        canvasHandlers = {
            mousedown: mousedownHandler,
            mouseup: mouseupHandler,
            mouseleave: mouseleaveHandler,
            mousemove: mousemoveHandler
        };
    }

    function ensureCropWorkspaceOverlay() {
        if (cropWorkspaceOverlay) return;

        cropWorkspaceOverlay = document.createElement('div');
        cropWorkspaceOverlay.id = 'cdpCropOverlay';
        cropWorkspaceOverlay.className = 'cdp-crop-overlay';
        cropWorkspaceOverlay.setAttribute('data-visible', 'false');
        cropWorkspaceOverlay.innerHTML = `
            <div class="cdp-crop-shell">
                <header class="cdp-crop-header">
                    <div>
                        <p>Crop & Focus</p>
                        <h3>Precision workspace</h3>
                    </div>
                    <div class="cdp-crop-header-actions">
                        <div class="cdp-filter-zoom-controls">
                            <button type="button" class="cdp-filter-zoom-btn" data-crop-zoom="out" title="Zoom out">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <span id="cdpCropZoomLabel">100%</span>
                            <button type="button" class="cdp-filter-zoom-btn" data-crop-zoom="in" title="Zoom in">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <button type="button" class="cdp-filter-close" data-crop-action="close" aria-label="Close crop workspace">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </header>
                <div class="cdp-crop-body">
                    <div class="cdp-crop-body-top">
                        <p class="cdp-crop-hint" id="cdpCropHint">Click and drag to draw a crop box. Use zoom for precision, then press OK.</p>
                        <button type="button" class="cdp-crop-reset" data-crop-action="reset-selection">
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset selection
                        </button>
                    </div>
                    <div class="cdp-crop-stage">
                        <div class="cdp-crop-stage-original">
                            <div class="cdp-crop-canvas-wrap">
                                <div class="cdp-crop-canvas-inner" id="cdpCropCanvasInner">
                                    <canvas id="cdpCropCanvas"></canvas>
                                    <div class="cdp-crop-selection" id="cdpCropSelection"></div>
                                </div>
                            </div>
                        </div>
                        <div class="cdp-crop-preview-card">
                            <div class="cdp-crop-preview-title">Preview</div>
                            <div class="cdp-crop-preview-frame" id="cdpCropPreviewFrame">
                                <canvas id="cdpCropPreviewCanvas"></canvas>
                                <div class="cdp-crop-preview-placeholder" id="cdpCropPreviewPlaceholder">Drag to preview your crop.</div>
                            </div>
                            <p class="cdp-crop-preview-hint">Shows the exact result before you confirm.</p>
                        </div>
                    </div>
                </div>
                <footer class="cdp-crop-footer">
                    <button type="button" class="cdp-btn cdp-btn-secondary" data-crop-action="cancel">Cancel</button>
                    <button type="button" class="cdp-btn cdp-btn-primary" data-crop-action="apply">OK</button>
                </footer>
            </div>
        `;

        document.body.appendChild(cropWorkspaceOverlay);

        cropWorkspaceCanvas = cropWorkspaceOverlay.querySelector('#cdpCropCanvas');
        cropWorkspaceInner = cropWorkspaceOverlay.querySelector('#cdpCropCanvasInner');
        cropWorkspaceSelectionEl = cropWorkspaceOverlay.querySelector('#cdpCropSelection');
        cropWorkspaceZoomLabel = cropWorkspaceOverlay.querySelector('#cdpCropZoomLabel');
        cropWorkspaceHint = cropWorkspaceOverlay.querySelector('#cdpCropHint');
        cropWorkspaceHintDefault = cropWorkspaceHint ? cropWorkspaceHint.textContent.trim() : '';
        cropWorkspacePreviewCanvas = cropWorkspaceOverlay.querySelector('#cdpCropPreviewCanvas');
        cropWorkspacePreviewFrame = cropWorkspaceOverlay.querySelector('#cdpCropPreviewFrame');
        cropWorkspacePreviewPlaceholder = cropWorkspaceOverlay.querySelector('#cdpCropPreviewPlaceholder');
        cropWorkspaceCtx = cropWorkspaceCanvas ? cropWorkspaceCanvas.getContext('2d') : null;
        cropWorkspacePreviewCtx = cropWorkspacePreviewCanvas ? cropWorkspacePreviewCanvas.getContext('2d') : null;
        if (cropWorkspacePreviewFrame) {
            cropWorkspacePreviewFrame.setAttribute('data-empty', 'true');
        }

        cropWorkspaceOverlay.querySelectorAll('[data-crop-zoom]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const direction = btn.getAttribute('data-crop-zoom');
                const delta = direction === 'in' ? 0.2 : -0.2;
                cropWorkspaceZoom = Math.min(3, Math.max(0.4, cropWorkspaceZoom + delta));
                updateCropWorkspaceZoom();
            });
        });

        cropWorkspaceOverlay.querySelectorAll('[data-crop-action]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                handleCropWorkspaceAction(event.currentTarget.getAttribute('data-crop-action'));
            });
        });

        cropWorkspaceOverlay.addEventListener('click', (event) => {
            if (event.target === cropWorkspaceOverlay) {
                closeCropWorkspace(false);
            }
        });

        if (cropWorkspaceCanvas) {
            cropWorkspaceCanvas.addEventListener('pointerdown', handleCropPointerDown);
            cropWorkspaceCanvas.addEventListener('pointermove', handleCropPointerMove);
            cropWorkspaceCanvas.addEventListener('pointerup', handleCropPointerUp);
            cropWorkspaceCanvas.addEventListener('pointerleave', handleCropPointerUp);
        }

        window.addEventListener('resize', updateCropSelectionVisual);
        document.addEventListener('keydown', handleCropWorkspaceKeydown, true);
    }

    function openCropWorkspace() {
        if (!currentEditingImage) return;

        ensureCropWorkspaceOverlay();
        withCanvasReady(() => {
            if (!canvas || !ctx || !cropWorkspaceCanvas || !cropWorkspaceCtx) return;

            cropWorkspaceCanvas.width = canvas.width;
            cropWorkspaceCanvas.height = canvas.height;
            cropWorkspaceCtx.clearRect(0, 0, cropWorkspaceCanvas.width, cropWorkspaceCanvas.height);
            cropWorkspaceCtx.drawImage(canvas, 0, 0);

            cropSourceCanvas = document.createElement('canvas');
            cropSourceCanvas.width = canvas.width;
            cropSourceCanvas.height = canvas.height;
            const cropSourceCtx = cropSourceCanvas.getContext('2d');
            cropSourceCtx.clearRect(0, 0, cropSourceCanvas.width, cropSourceCanvas.height);
            cropSourceCtx.drawImage(canvas, 0, 0);

            cropWorkspaceSelection = null;
            cropWorkspaceIsDragging = false;
            cropWorkspaceDragStart = null;
            cropWorkspaceZoom = 1;
            updateCropWorkspaceZoom();
            resetCropWorkspaceHint();
            updateCropSelectionVisual();

            setCropWorkspaceVisible(true);
        });
    }

    function setCropWorkspaceVisible(visible) {
        if (!cropWorkspaceOverlay) return;
        cropWorkspaceOverlay.setAttribute('data-visible', String(visible));
        isCropping = visible;
        const body = document.body;
        if (body) {
            body.classList.toggle('cdp-lock-scroll', visible);
        }
        if (cropButtonRef) {
            cropButtonRef.classList.toggle('active', visible);
        }
        if (!visible) {
            cropWorkspaceZoom = 1;
            updateCropWorkspaceZoom();
            cropWorkspaceSelection = null;
            cropWorkspaceIsMovingSelection = false;
            cropWorkspaceSelectionStart = null;
            updateCropSelectionVisual();
        }
    }

    function closeCropWorkspace() {
        const body = document.body;
        if (body) {
            body.classList.remove('cdp-lock-scroll');
        }
        if (!cropWorkspaceOverlay) {
            isCropping = false;
            if (cropButtonRef) cropButtonRef.classList.remove('active');
            return;
        }
        if (cropWorkspaceOverlay.getAttribute('data-visible') === 'true') {
            setCropWorkspaceVisible(false);
        } else {
            isCropping = false;
            if (cropButtonRef) cropButtonRef.classList.remove('active');
        }
        cropWorkspaceIsDragging = false;
        cropWorkspaceDragStart = null;
        cropWorkspaceIsMovingSelection = false;
        cropWorkspaceSelectionStart = null;
        cropWorkspaceSelection = null;
        cropSourceCanvas = null;
        updateCropSelectionVisual();
        resetCropWorkspaceHint();
        clearCropPreview();
    }

    function updateCropWorkspaceZoom() {
        if (cropWorkspaceInner) {
            cropWorkspaceInner.style.transform = `scale(${cropWorkspaceZoom})`;
        }
        if (cropWorkspaceZoomLabel) {
            cropWorkspaceZoomLabel.textContent = Math.round(cropWorkspaceZoom * 100) + '%';
        }
    }

    function resetCropWorkspaceHint(message) {
        if (!cropWorkspaceHint) return;
        cropWorkspaceHint.textContent = message || cropWorkspaceHintDefault;
        if (!message) {
            cropWorkspaceHint.classList.remove('cdp-crop-hint--error');
        }
    }

    function handleCropWorkspaceAction(action) {
        if (!action) return;
        if (action === 'reset-selection') {
            cropWorkspaceSelection = null;
            cropWorkspaceIsDragging = false;
            cropWorkspaceDragStart = null;
            cropWorkspaceIsMovingSelection = false;
            cropWorkspaceSelectionStart = null;
            updateCropSelectionVisual();
            resetCropWorkspaceHint();
            clearCropPreview();
            return;
        }
        if (action === 'apply') {
            const applied = applyCropSelection();
            if (applied) {
                closeCropWorkspace(true);
            }
        } else {
            closeCropWorkspace(false);
        }
    }

    function handleCropPointerDown(event) {
        if (!cropWorkspaceCanvas || !cropSourceCanvas) return;
        const coords = getCropPointerCoords(event);
        const isInsideExistingSelection = isPointInsideCropSelection(coords);
        try {
            cropWorkspaceCanvas.setPointerCapture(event.pointerId);
        } catch (error) {
            // ignore pointer capture errors
        }
        event.preventDefault();
        event.stopPropagation();

        if (isInsideExistingSelection && cropWorkspaceSelection) {
            cropWorkspaceIsMovingSelection = true;
            cropWorkspaceIsDragging = false;
            cropWorkspaceDragStart = coords;
            cropWorkspaceSelectionStart = { ...cropWorkspaceSelection };
            resetCropWorkspaceHint();
            if (cropWorkspaceHint) {
                cropWorkspaceHint.textContent = 'Drag to reposition the crop box.';
            }
            return;
        }

        cropWorkspaceIsMovingSelection = false;
        cropWorkspaceSelectionStart = null;
        cropWorkspaceIsDragging = true;
        cropWorkspaceDragStart = coords;
        cropWorkspaceSelection = {
            x: coords.x,
            y: coords.y,
            width: 0,
            height: 0
        };
        resetCropWorkspaceHint();
        updateCropSelectionVisual();
    }

    function handleCropPointerMove(event) {
        if (!cropWorkspaceDragStart) return;
        event.preventDefault();
        event.stopPropagation();
        const coords = getCropPointerCoords(event);

        if (cropWorkspaceIsMovingSelection && cropWorkspaceSelection && cropWorkspaceSelectionStart) {
            const deltaX = coords.x - cropWorkspaceDragStart.x;
            const deltaY = coords.y - cropWorkspaceDragStart.y;
            cropWorkspaceSelection = {
                ...cropWorkspaceSelection,
                x: cropWorkspaceSelectionStart.x + deltaX,
                y: cropWorkspaceSelectionStart.y + deltaY
            };
            clampCropSelectionToBounds();
            updateCropSelectionVisual();
            return;
        }

        if (!cropWorkspaceIsDragging) return;

        cropWorkspaceSelection = {
            x: Math.min(cropWorkspaceDragStart.x, coords.x),
            y: Math.min(cropWorkspaceDragStart.y, coords.y),
            width: Math.abs(coords.x - cropWorkspaceDragStart.x),
            height: Math.abs(coords.y - cropWorkspaceDragStart.y)
        };
        clampCropSelectionToBounds();
        updateCropSelectionVisual();
    }

    function handleCropPointerUp(event) {
        if (!cropWorkspaceCanvas) return;
        try {
            if (cropWorkspaceCanvas.hasPointerCapture && cropWorkspaceCanvas.hasPointerCapture(event.pointerId)) {
                cropWorkspaceCanvas.releasePointerCapture(event.pointerId);
            }
        } catch (error) {
            // ignore pointer capture release errors
        }
        event.preventDefault();
        event.stopPropagation();

        if (cropWorkspaceIsMovingSelection) {
            cropWorkspaceIsMovingSelection = false;
            cropWorkspaceSelectionStart = null;
            cropWorkspaceDragStart = null;
            clampCropSelectionToBounds();
            updateCropSelectionVisual();
            return;
        }

        cropWorkspaceIsDragging = false;
        cropWorkspaceDragStart = null;
        if (cropWorkspaceSelection && (cropWorkspaceSelection.width < 4 || cropWorkspaceSelection.height < 4)) {
            cropWorkspaceSelection = null;
            updateCropSelectionVisual();
        }
    }

    function getCropPointerCoords(event) {
        if (!cropWorkspaceCanvas) return { x: 0, y: 0 };
        const rect = cropWorkspaceCanvas.getBoundingClientRect();
        const scaleX = cropWorkspaceCanvas.width / Math.max(rect.width, 1);
        const scaleY = cropWorkspaceCanvas.height / Math.max(rect.height, 1);
        const x = Math.min(Math.max(0, (event.clientX - rect.left) * scaleX), cropWorkspaceCanvas.width);
        const y = Math.min(Math.max(0, (event.clientY - rect.top) * scaleY), cropWorkspaceCanvas.height);
        return { x, y };
    }

    function isPointInsideCropSelection(point) {
        if (!cropWorkspaceSelection) return false;
        return (
            point.x >= cropWorkspaceSelection.x &&
            point.x <= cropWorkspaceSelection.x + cropWorkspaceSelection.width &&
            point.y >= cropWorkspaceSelection.y &&
            point.y <= cropWorkspaceSelection.y + cropWorkspaceSelection.height
        );
    }

    function clampCropSelectionToBounds() {
        if (!cropWorkspaceCanvas || !cropWorkspaceSelection) return;
        if (cropWorkspaceSelection.width > cropWorkspaceCanvas.width) {
            cropWorkspaceSelection.width = cropWorkspaceCanvas.width;
        }
        if (cropWorkspaceSelection.height > cropWorkspaceCanvas.height) {
            cropWorkspaceSelection.height = cropWorkspaceCanvas.height;
        }
        const maxX = Math.max(0, cropWorkspaceCanvas.width - cropWorkspaceSelection.width);
        const maxY = Math.max(0, cropWorkspaceCanvas.height - cropWorkspaceSelection.height);
        cropWorkspaceSelection.x = Math.min(Math.max(0, cropWorkspaceSelection.x), maxX);
        cropWorkspaceSelection.y = Math.min(Math.max(0, cropWorkspaceSelection.y), maxY);
    }

    function updateCropSelectionVisual() {
        if (!cropWorkspaceSelectionEl || !cropWorkspaceCanvas) return;
        if (!cropWorkspaceSelection || cropWorkspaceSelection.width < 1 || cropWorkspaceSelection.height < 1) {
            cropWorkspaceSelectionEl.style.display = 'none';
            updateCropPreviewCanvas();
            return;
        }

        const rect = cropWorkspaceCanvas.getBoundingClientRect();
        if (!rect.width || !rect.height) {
            cropWorkspaceSelectionEl.style.display = 'none';
            updateCropPreviewCanvas();
            return;
        }

        const scaleX = rect.width / cropWorkspaceCanvas.width;
        const scaleY = rect.height / cropWorkspaceCanvas.height;
        cropWorkspaceSelectionEl.style.display = 'block';
        cropWorkspaceSelectionEl.style.left = (cropWorkspaceSelection.x * scaleX) + 'px';
        cropWorkspaceSelectionEl.style.top = (cropWorkspaceSelection.y * scaleY) + 'px';
        cropWorkspaceSelectionEl.style.width = (cropWorkspaceSelection.width * scaleX) + 'px';
        cropWorkspaceSelectionEl.style.height = (cropWorkspaceSelection.height * scaleY) + 'px';
        updateCropPreviewCanvas();
    }

    function clearCropPreview() {
        if (cropWorkspacePreviewCanvas && cropWorkspacePreviewCtx) {
            cropWorkspacePreviewCtx.clearRect(0, 0, cropWorkspacePreviewCanvas.width || 0, cropWorkspacePreviewCanvas.height || 0);
            cropWorkspacePreviewCanvas.width = 0;
            cropWorkspacePreviewCanvas.height = 0;
        }
        if (cropWorkspacePreviewFrame) {
            cropWorkspacePreviewFrame.setAttribute('data-empty', 'true');
        }
        if (cropWorkspacePreviewPlaceholder) {
            cropWorkspacePreviewPlaceholder.style.opacity = '';
        }
    }

    function updateCropPreviewCanvas() {
        if (!cropWorkspacePreviewCanvas || !cropWorkspacePreviewCtx || !cropWorkspaceSelection || !cropSourceCanvas) {
            clearCropPreview();
            return;
        }
        if (cropWorkspaceSelection.width < 2 || cropWorkspaceSelection.height < 2) {
            clearCropPreview();
            return;
        }
        const selection = cropWorkspaceSelection;
        const maxDimension = 1600;
        const scale = Math.min(1, maxDimension / Math.max(selection.width, selection.height));
        const targetWidth = Math.max(1, Math.round(selection.width * scale));
        const targetHeight = Math.max(1, Math.round(selection.height * scale));
        cropWorkspacePreviewCanvas.width = targetWidth;
        cropWorkspacePreviewCanvas.height = targetHeight;
        cropWorkspacePreviewCtx.clearRect(0, 0, targetWidth, targetHeight);
        cropWorkspacePreviewCtx.drawImage(
            cropSourceCanvas,
            selection.x,
            selection.y,
            selection.width,
            selection.height,
            0,
            0,
            targetWidth,
            targetHeight
        );
        if (cropWorkspacePreviewFrame) {
            cropWorkspacePreviewFrame.setAttribute('data-empty', 'false');
        }
    }

    function flashCropSelection() {
        if (cropWorkspaceSelectionEl && cropWorkspaceSelectionEl.style.display === 'block') {
            cropWorkspaceSelectionEl.classList.add('cdp-crop-selection--pulse');
            setTimeout(() => {
                if (cropWorkspaceSelectionEl) {
                    cropWorkspaceSelectionEl.classList.remove('cdp-crop-selection--pulse');
                }
            }, 400);
            return;
        }
        if (cropWorkspaceInner) {
            cropWorkspaceInner.classList.add('cdp-crop-canvas-inner--pulse');
            setTimeout(() => {
                if (cropWorkspaceInner) {
                    cropWorkspaceInner.classList.remove('cdp-crop-canvas-inner--pulse');
                }
            }, 400);
        }
    }

    function applyCropSelection() {
        if (!cropWorkspaceSelection || cropWorkspaceSelection.width < 12 || cropWorkspaceSelection.height < 12 || !cropSourceCanvas) {
            flashCropSelection();
            resetCropWorkspaceHint('Select an area first, then press OK.');
            if (cropWorkspaceHint) {
                cropWorkspaceHint.classList.add('cdp-crop-hint--error');
            }
            return false;
        }

        if (!canvas || !ctx) {
            console.warn('Canvas not ready for cropping');
            return false;
        }

        applyCrop(cropWorkspaceSelection, cropSourceCanvas);
        pushCanvasHistoryState();
        hasCanvasChanges = true;
        updateEditPreview();

        return true;
    }

    function handleCropWorkspaceKeydown(event) {
        if (!cropWorkspaceOverlay || cropWorkspaceOverlay.getAttribute('data-visible') !== 'true') return;
        if ((event.ctrlKey || event.metaKey) && event.key && event.key.toLowerCase() === 'z') {
            event.preventDefault();
            cropWorkspaceSelection = null;
            cropWorkspaceIsDragging = false;
            cropWorkspaceDragStart = null;
            cropWorkspaceIsMovingSelection = false;
            cropWorkspaceSelectionStart = null;
            updateCropSelectionVisual();
            resetCropWorkspaceHint();
            clearCropPreview();
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closeCropWorkspace(false);
        } else if (event.key === 'Enter' && !event.shiftKey && !event.altKey) {
            const applied = applyCropSelection();
            if (applied) {
                event.preventDefault();
                closeCropWorkspace(true);
            }
        }
    }

    function ensureAdvancedEraseOverlay() {
        if (advancedEraseOverlay) return;

        advancedEraseOverlay = document.createElement('div');
        advancedEraseOverlay.id = 'cdpAdvancedEraseOverlay';
        advancedEraseOverlay.className = 'cdp-advanced-erase-overlay';
        advancedEraseOverlay.setAttribute('data-visible', 'false');
        advancedEraseOverlay.innerHTML = `
            <div class="cdp-advanced-erase-shell">
                <aside class="cdp-advanced-erase-toolbar">
                    <button type="button" class="cdp-advanced-erase-tool" title="Zoom in" data-mobile-label="Zoom +" data-erase-action="zoom-in"><i class="fa-solid fa-plus"></i></button>
                    <button type="button" class="cdp-advanced-erase-tool" title="Zoom out" data-mobile-label="Zoom -" data-erase-action="zoom-out"><i class="fa-solid fa-minus"></i></button>
                    <div class="cdp-advanced-erase-divider"></div>
                    <button type="button" class="cdp-advanced-erase-tool cdp-advanced-erase-tool--active" title="Erase" data-mobile-label="Erase" data-erase-action="brush" id="cdpAdvancedEraseBrushBtn"><i class="fa-solid fa-eraser"></i></button>
                    <button type="button" class="cdp-advanced-erase-tool" title="Smart auto cut" data-mobile-label="Auto cut" data-erase-action="remove-bg"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                    <button type="button" class="cdp-advanced-erase-tool" title="Undo" data-mobile-label="Undo" data-erase-action="undo"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    <button type="button" class="cdp-advanced-erase-tool" title="Move canvas" data-mobile-label="Move" data-erase-action="move-toggle" id="cdpAdvancedEraseMoveBtn"><i class="fa-solid fa-up-down-left-right"></i></button>
                    <button type="button" class="cdp-advanced-erase-tool" title="Reset" data-mobile-label="Reset" data-erase-action="reset"><i class="fa-solid fa-arrows-rotate"></i></button>
                </aside>
                <section class="cdp-advanced-erase-stage">
                    <header class="cdp-advanced-erase-header">
                        <div>
                            <p>Erase & Retouch</p>
                            <h3>Advanced workspace</h3>
                        </div>
                        <div class="cdp-advanced-erase-header-actions">
                            <span class="cdp-advanced-erase-zoom" id="cdpAdvancedEraseZoomLabel">100%</span>
                            <button type="button" class="cdp-advanced-erase-close" data-erase-action="close" aria-label="Close advanced erase">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </header>
                    <div class="cdp-advanced-erase-canvas-wrap">
                        <div class="cdp-advanced-erase-canvas-inner" id="cdpAdvancedEraseCanvasInner">
                            <canvas id="cdpAdvancedEraseCanvas"></canvas>
                        </div>
                    </div>
                    <div class="cdp-advanced-erase-status" id="cdpAdvancedEraseStatus" role="status" aria-live="polite">
                        <i class="fa-solid fa-sparkles"></i>
                        <span id="cdpAdvancedEraseStatusCopy">Ready for auto cleanup</span>
                    </div>
                    <footer class="cdp-advanced-erase-footer">
                        <div class="cdp-advanced-erase-brush">
                            <label>Brush size <span id="cdpAdvancedEraseBrushValue">40px</span></label>
                            <input type="range" id="cdpAdvancedEraseBrush" min="5" max="150" step="5" value="40">
                        </div>
                        <div class="cdp-advanced-erase-auto">
                            <label>Auto cut tolerance <span id="cdpAdvancedEraseAutoStrengthValue">Balanced</span></label>
                            <input type="range" id="cdpAdvancedEraseAutoStrength" min="5" max="95" step="5" value="55">
                        </div>
                        <div class="cdp-advanced-erase-footer-actions">
                            <button type="button" class="cdp-btn cdp-btn-secondary" data-erase-action="cancel">Cancel</button>
                            <button type="button" class="cdp-btn cdp-btn-primary" data-erase-action="apply">Apply</button>
                        </div>
                    </footer>
                </section>
            </div>
        `;

        document.body.appendChild(advancedEraseOverlay);

        advancedEraseCanvas = advancedEraseOverlay.querySelector('#cdpAdvancedEraseCanvas');
        advancedEraseCanvasInner = advancedEraseOverlay.querySelector('#cdpAdvancedEraseCanvasInner');
        advancedEraseBrushSlider = advancedEraseOverlay.querySelector('#cdpAdvancedEraseBrush');
        advancedEraseBrushValue = advancedEraseOverlay.querySelector('#cdpAdvancedEraseBrushValue');
        advancedEraseZoomDisplay = advancedEraseOverlay.querySelector('#cdpAdvancedEraseZoomLabel');
        advancedEraseMoveBtn = advancedEraseOverlay.querySelector('#cdpAdvancedEraseMoveBtn');
        advancedEraseBrushBtn = advancedEraseOverlay.querySelector('#cdpAdvancedEraseBrushBtn');
        advancedEraseRemoveBgBtn = advancedEraseOverlay.querySelector('[data-erase-action="remove-bg"]');
        advancedEraseAutoStrengthSlider = advancedEraseOverlay.querySelector('#cdpAdvancedEraseAutoStrength');
        advancedEraseAutoStrengthValue = advancedEraseOverlay.querySelector('#cdpAdvancedEraseAutoStrengthValue');
        advancedEraseStatusEl = advancedEraseOverlay.querySelector('#cdpAdvancedEraseStatus');
        advancedEraseStatusCopyEl = advancedEraseOverlay.querySelector('#cdpAdvancedEraseStatusCopy');

        if (advancedEraseBrushSlider) {
            advancedEraseBrushSlider.addEventListener('input', (e) => {
                const value = Number(e.target.value) || 40;
                advancedEraseBrushSize = value;
                if (advancedEraseBrushValue) {
                    advancedEraseBrushValue.textContent = value + 'px';
                }
                updateAdvancedEraseBrushCursor();
            });
        }

        if (advancedEraseAutoStrengthSlider) {
            advancedEraseAutoStrengthSlider.value = String(advancedEraseAutoStrength);
            advancedEraseAutoStrengthSlider.addEventListener('input', (e) => {
                advancedEraseAutoStrength = Number(e.target.value) || 55;
                updateAdvancedEraseAutoStrengthLabel();
            });
        }

        updateAdvancedEraseAutoStrengthLabel();
        updateAdvancedEraseStatus('Ready for auto cleanup', 'idle', 0);

        advancedEraseOverlay.querySelectorAll('[data-erase-action]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const action = event.currentTarget.getAttribute('data-erase-action');
                handleAdvancedEraseAction(action);
            });
        });

        attachAdvancedEraseCanvasEvents();
        setAdvancedEraseMoveMode(false);
        applyAdvancedEraseCursorStyle();

        if (!advancedEraseKeyListenerBound) {
            document.addEventListener('keydown', handleAdvancedEraseKeydown, true);
            window.addEventListener('keydown', handleAdvancedEraseKeydown, true);
            advancedEraseKeyListenerBound = true;
        }
    }

    function attachAdvancedEraseCanvasEvents() {
        if (!advancedEraseCanvas) return;

        advancedEraseCanvas.addEventListener('pointerdown', handleAdvancedErasePointerDown);
        advancedEraseCanvas.addEventListener('pointermove', handleAdvancedErasePointerMove);
        advancedEraseCanvas.addEventListener('pointerup', handleAdvancedErasePointerUp);
        advancedEraseCanvas.addEventListener('pointerleave', handleAdvancedErasePointerUp);
    }

    function describeAdvancedEraseStrength(value) {
        if (value <= 20) return 'Feathered';
        if (value <= 45) return 'Balanced';
        if (value <= 70) return 'Bold';
        return 'Intense';
    }

    function updateAdvancedEraseAutoStrengthLabel(value = advancedEraseAutoStrength) {
        if (!advancedEraseAutoStrengthValue) return;
        const descriptor = describeAdvancedEraseStrength(value);
        advancedEraseAutoStrengthValue.textContent = descriptor;
    }

    function updateAdvancedEraseStatus(copy, state = 'idle', persistMs = 2200) {
        if (!advancedEraseStatusEl || !advancedEraseStatusCopyEl) return;
        advancedEraseStatusEl.setAttribute('data-state', state);
        advancedEraseStatusCopyEl.textContent = copy;
        if (advancedEraseStatusTimeoutId) {
            clearTimeout(advancedEraseStatusTimeoutId);
            advancedEraseStatusTimeoutId = null;
        }
        if (state !== 'idle' && persistMs > 0) {
            const timerHost = typeof window !== 'undefined' ? window : globalThis;
            advancedEraseStatusTimeoutId = timerHost.setTimeout(() => {
                advancedEraseStatusTimeoutId = null;
                const idleMessage = advancedEraseWandMode
                    ? 'Click any background area to auto-cut. Press Esc to exit.'
                    : 'Ready for auto cleanup';
                updateAdvancedEraseStatus(idleMessage, 'idle', 0);
            }, persistMs);
        }
    }

    function setAdvancedEraseAutoProcessingState(isProcessing) {
        advancedEraseAutoProcessing = Boolean(isProcessing);
        if (advancedEraseOverlay) {
            advancedEraseOverlay.setAttribute('data-auto-processing', String(advancedEraseAutoProcessing));
        }
        if (advancedEraseRemoveBgBtn) {
            advancedEraseRemoveBgBtn.disabled = advancedEraseAutoProcessing;
            advancedEraseRemoveBgBtn.setAttribute('aria-busy', String(advancedEraseAutoProcessing));
        }
    }

    function setAdvancedEraseWandMode(active) {
        const shouldActivate = Boolean(active);
        if (advancedEraseWandMode === shouldActivate) {
            return;
        }
        advancedEraseWandMode = shouldActivate;
        advancedEraseBrushActive = !advancedEraseWandMode && !advancedErasePanMode;
        if (advancedEraseRemoveBgBtn) {
            if (advancedEraseWandMode) {
                advancedEraseRemoveBgBtn.classList.add('cdp-advanced-erase-tool--active');
                advancedEraseRemoveBgBtn.setAttribute('aria-pressed', 'true');
            } else {
                advancedEraseRemoveBgBtn.classList.remove('cdp-advanced-erase-tool--active');
                advancedEraseRemoveBgBtn.removeAttribute('aria-pressed');
            }
        }
        if (advancedEraseBrushBtn) {
            if (advancedEraseBrushActive) {
                advancedEraseBrushBtn.classList.add('cdp-advanced-erase-tool--active');
            } else {
                advancedEraseBrushBtn.classList.remove('cdp-advanced-erase-tool--active');
            }
        }
        if (advancedEraseWandMode) {
            updateAdvancedEraseStatus('Click any background area to auto-cut. Press Esc to exit.', 'info', 0);
        } else if (!advancedEraseAutoProcessing) {
            updateAdvancedEraseStatus('Ready for auto cleanup', 'idle', 0);
        }
        applyAdvancedEraseCursorStyle();
    }

    function getAdvancedEraseCanvasPoint(event) {
        if (!advancedEraseCanvas) return null;
        const rect = advancedEraseCanvas.getBoundingClientRect();
        const scaleX = rect.width ? advancedEraseCanvas.width / rect.width : 1;
        const scaleY = rect.height ? advancedEraseCanvas.height / rect.height : 1;
        return {
            x: (event.clientX - rect.left) * scaleX,
            y: (event.clientY - rect.top) * scaleY
        };
    }

    function isAdvancedEraseWithinCanvas(point) {
        if (!point || !advancedEraseCanvas) return false;
        return point.x >= 0 && point.y >= 0 && point.x < advancedEraseCanvas.width && point.y < advancedEraseCanvas.height;
    }

    function computeAdvancedEraseBrushCursorDiameter() {
        if (!advancedEraseCanvas) return advancedEraseBrushSize;
        const rect = advancedEraseCanvas.getBoundingClientRect();
        if (!rect.width || !advancedEraseCanvas.width) return advancedEraseBrushSize;
        const scale = rect.width / advancedEraseCanvas.width;
        return Math.max(6, advancedEraseBrushSize * scale);
    }

    function getAdvancedEraseBrushCursorAsset(diameter) {
        if (!diameter) return null;
        const size = Math.round(Math.min(180, Math.max(8, diameter)));
        if (advancedEraseBrushCursorCache && advancedEraseBrushCursorCache.diameter === size && advancedEraseBrushCursorCache.url) {
            return advancedEraseBrushCursorCache;
        }
        const padding = 4;
        const cursorCanvas = document.createElement('canvas');
        cursorCanvas.width = size + padding * 2;
        cursorCanvas.height = size + padding * 2;
        const cursorCtx = cursorCanvas.getContext('2d');
        if (!cursorCtx) {
            return null;
        }
        cursorCtx.clearRect(0, 0, cursorCanvas.width, cursorCanvas.height);
        cursorCtx.strokeStyle = 'rgba(255, 255, 255, 0.95)';
        cursorCtx.lineWidth = Math.max(1, size * 0.09);
        cursorCtx.shadowColor = 'rgba(15, 23, 42, 0.65)';
        cursorCtx.shadowBlur = Math.max(0.8, cursorCtx.lineWidth * 0.9);
        cursorCtx.beginPath();
        cursorCtx.arc(cursorCanvas.width / 2, cursorCanvas.height / 2, size / 2, 0, Math.PI * 2);
        cursorCtx.stroke();
        const url = cursorCanvas.toDataURL('image/png');
        advancedEraseBrushCursorCache = {
            diameter: size,
            url,
            hotspot: Math.round(cursorCanvas.width / 2)
        };
        return advancedEraseBrushCursorCache;
    }

    function updateAdvancedEraseBrushCursor() {
        if (!advancedEraseCanvas) return;
        const cssDiameter = computeAdvancedEraseBrushCursorDiameter();
        const asset = getAdvancedEraseBrushCursorAsset(cssDiameter);
        if (asset && asset.url) {
            advancedEraseCanvas.style.cursor = 'url(' + asset.url + ') ' + asset.hotspot + ' ' + asset.hotspot + ', crosshair';
        } else {
            advancedEraseCanvas.style.cursor = 'crosshair';
        }
    }

    function handleAdvancedEraseKeydown(e) {
        if (!advancedEraseOverlay || advancedEraseOverlay.getAttribute('data-visible') !== 'true') {
            return;
        }
        if ((e.ctrlKey || e.metaKey) && e.key && e.key.toLowerCase() === 'z') {
            const undone = advancedEraseUndo();
            if (undone) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }

    function setAdvancedEraseMoveMode(active) {
        advancedErasePanMode = Boolean(active);
        if (advancedErasePanMode) {
            setAdvancedEraseWandMode(false);
        }
        advancedEraseBrushActive = !advancedErasePanMode && !advancedEraseWandMode;
        advancedErasePanStart = null;
        advancedErasePanStartOffset = null;
        if (advancedEraseMoveBtn) {
            if (advancedErasePanMode) {
                advancedEraseMoveBtn.classList.add('cdp-advanced-erase-tool--active');
            } else {
                advancedEraseMoveBtn.classList.remove('cdp-advanced-erase-tool--active');
            }
        }
        if (advancedEraseBrushBtn) {
            if (advancedEraseBrushActive) {
                advancedEraseBrushBtn.classList.add('cdp-advanced-erase-tool--active');
            } else {
                advancedEraseBrushBtn.classList.remove('cdp-advanced-erase-tool--active');
            }
        }
        applyAdvancedEraseCursorStyle();
    }

    function handleAdvancedErasePointerDown(event) {
        if (!advancedEraseCanvas || !advancedEraseCtx) return;
        if (advancedErasePanMode) {
            advancedEraseCanvas.setPointerCapture(event.pointerId);
            advancedErasePanStart = { x: event.clientX, y: event.clientY };
            advancedErasePanStartOffset = { x: advancedErasePanOffset.x, y: advancedErasePanOffset.y };
            event.preventDefault();
            return;
        }
        if (advancedEraseWandMode) {
            event.preventDefault();
            advancedEraseMagicWandClick(event);
            return;
        }
        advancedEraseCanvas.setPointerCapture(event.pointerId);
        advancedEraseIsDrawing = true;
        eraseAtPointer(event);
    }

    function handleAdvancedErasePointerMove(event) {
        if (advancedErasePanMode) {
            if (!advancedErasePanStart) return;
            const dx = event.clientX - advancedErasePanStart.x;
            const dy = event.clientY - advancedErasePanStart.y;
            advancedErasePanOffset.x = advancedErasePanStartOffset.x + dx;
            advancedErasePanOffset.y = advancedErasePanStartOffset.y + dy;
            updateAdvancedEraseTransform();
            event.preventDefault();
            return;
        }
        if (advancedEraseWandMode) {
            return;
        }
        if (!advancedEraseIsDrawing) return;
        eraseAtPointer(event);
    }

    function handleAdvancedErasePointerUp(event) {
        if (advancedEraseCanvas && event.pointerId) {
            try {
                advancedEraseCanvas.releasePointerCapture(event.pointerId);
            } catch (err) {
                /* ignore */
            }
        }
        if (advancedErasePanMode) {
            advancedErasePanStart = null;
            advancedErasePanStartOffset = null;
            return;
        }
        if (advancedEraseWandMode) {
            return;
        }
        if (advancedEraseIsDrawing) {
            advancedEraseIsDrawing = false;
            pushAdvancedEraseHistory();
        }
    }


    function applyAdvancedEraseCursorStyle() {
        if (!advancedEraseCanvas) return;
        if (advancedErasePanMode) {
            advancedEraseCanvas.style.cursor = ADVANCED_ERASE_CURSOR_MOVE ? 'url(' + ADVANCED_ERASE_CURSOR_MOVE + ') 16 16, grab' : 'grab';
            return;
        }
        if (advancedEraseWandMode) {
            advancedEraseCanvas.style.cursor = ADVANCED_ERASE_CURSOR_WAND ? 'url(' + ADVANCED_ERASE_CURSOR_WAND + ') 6 6, crosshair' : 'crosshair';
            return;
        }
        updateAdvancedEraseBrushCursor();
    }

    function eraseAtPointer(event) {
        if (!advancedEraseCanvas || !advancedEraseCtx) return;
        const rect = advancedEraseCanvas.getBoundingClientRect();
        const scaleX = advancedEraseCanvas.width / rect.width;
        const scaleY = advancedEraseCanvas.height / rect.height;
        const x = (event.clientX - rect.left) * scaleX;
        const y = (event.clientY - rect.top) * scaleY;

        advancedEraseCtx.save();
        advancedEraseCtx.globalCompositeOperation = 'destination-out';
        advancedEraseCtx.beginPath();
        advancedEraseCtx.arc(x, y, advancedEraseBrushSize / 2, 0, Math.PI * 2);
        advancedEraseCtx.fill();
        advancedEraseCtx.restore();
    }

    function resetAdvancedEraseHistory() {
        advancedEraseHistory = [];
        advancedEraseHistoryIndex = -1;
    }

    function pushAdvancedEraseHistory() {
        if (!advancedEraseCanvas) return;
        const snapshot = advancedEraseCanvas.toDataURL('image/png');
        if (advancedEraseHistoryIndex < advancedEraseHistory.length - 1) {
            advancedEraseHistory = advancedEraseHistory.slice(0, advancedEraseHistoryIndex + 1);
        }
        advancedEraseHistory.push(snapshot);
        advancedEraseHistoryIndex = advancedEraseHistory.length - 1;
    }

    function restoreAdvancedEraseSnapshot(snapshot) {
        if (!snapshot || !advancedEraseCtx || !advancedEraseCanvas) return false;
        const img = new Image();
        img.onload = function() {
            advancedEraseCtx.clearRect(0, 0, advancedEraseCanvas.width, advancedEraseCanvas.height);
            advancedEraseCtx.drawImage(img, 0, 0);
        };
        img.src = snapshot;
        return true;
    }

    function advancedEraseUndo() {
        if (advancedEraseHistoryIndex <= 0) return false;
        advancedEraseHistoryIndex -= 1;
        const snapshot = advancedEraseHistory[advancedEraseHistoryIndex];
        return restoreAdvancedEraseSnapshot(snapshot);
    }

    function handleAdvancedEraseAction(action) {
        switch (action) {
            case 'zoom-in':
                adjustAdvancedEraseZoom(0.15);
                break;
            case 'zoom-out':
                adjustAdvancedEraseZoom(-0.15);
                break;
            case 'remove-bg':
                if (!advancedEraseAutoProcessing) {
                    setAdvancedEraseMoveMode(false);
                    setAdvancedEraseWandMode(!advancedEraseWandMode);
                }
                break;
            case 'undo':
                advancedEraseUndo();
                break;
            case 'brush':
                setAdvancedEraseWandMode(false);
                setAdvancedEraseMoveMode(false);
                break;
            case 'move-toggle':
                setAdvancedEraseWandMode(false);
                setAdvancedEraseMoveMode(!advancedErasePanMode);
                break;
            case 'reset':
                setAdvancedEraseWandMode(false);
                advancedEraseReset();
                break;
            case 'cancel':
                setAdvancedEraseWandMode(false);
                advancedEraseCancel();
                break;
            case 'close':
                setAdvancedEraseWandMode(false);
                advancedEraseCancel();
                break;
            case 'apply':
                setAdvancedEraseWandMode(false);
                advancedEraseApply();
                break;
            default:
                break;
        }
    }

    function openAdvancedEraseWorkspace() {
        ensureAdvancedEraseOverlay();

        if (!currentEditingImage) {
            alert('Please open an image to edit first.');
            return;
        }

        const img = currentEditingImage.querySelector('img');
        if (!img) {
            alert('No image found for this layer.');
            return;
        }

        const loader = new Image();
        loader.crossOrigin = 'anonymous';
        loader.onload = function() {
            if (!advancedEraseCanvas) return;
            advancedEraseCanvas.width = loader.width;
            advancedEraseCanvas.height = loader.height;
            advancedEraseCtx = advancedEraseCanvas.getContext('2d');
            advancedEraseCtx.clearRect(0, 0, loader.width, loader.height);
            advancedEraseCtx.drawImage(loader, 0, 0);
            advancedEraseImageSnapshot = advancedEraseCanvas.toDataURL('image/png');
            advancedEraseBrushSize = Number(advancedEraseBrushSlider?.value || 40);
            if (advancedEraseBrushValue) {
                advancedEraseBrushValue.textContent = advancedEraseBrushSize + 'px';
            }
            if (advancedEraseAutoStrengthSlider) {
                advancedEraseAutoStrengthSlider.value = String(advancedEraseAutoStrength);
            }
            updateAdvancedEraseAutoStrengthLabel();
            setAdvancedEraseAutoProcessingState(false);
            updateAdvancedEraseStatus('Ready for auto cleanup', 'idle', 0);
            setAdvancedEraseWandMode(false);
            advancedEraseZoom = 1;
            advancedErasePanOffset = { x: 0, y: 0 };
            updateAdvancedEraseTransform();
            resetAdvancedEraseHistory();
            pushAdvancedEraseHistory();
            adjustAdvancedEraseZoom(0);
            applyAdvancedEraseCursorStyle();
            advancedEraseOverlay.setAttribute('data-visible', 'true');
        };
        loader.onerror = function() {
            alert('Unable to load this image into the erase workspace.');
        };
        loader.src = img.src;
    }

    function adjustAdvancedEraseZoom(delta) {
        advancedEraseZoom = Math.min(3, Math.max(0.25, advancedEraseZoom + delta));
        updateAdvancedEraseTransform();
        if (advancedEraseZoomDisplay) {
            advancedEraseZoomDisplay.textContent = Math.round(advancedEraseZoom * 100) + '%';
        }
    }

    function updateAdvancedEraseTransform() {
        if (!advancedEraseCanvasInner) return;
        advancedEraseCanvasInner.style.transform = 'translate(' + advancedErasePanOffset.x + 'px, ' + advancedErasePanOffset.y + 'px) scale(' + advancedEraseZoom + ')';
        if (!advancedErasePanMode && !advancedEraseWandMode) {
            updateAdvancedEraseBrushCursor();
        }
    }

    function getAdvancedEraseColorDistance(r1, g1, b1, r2, g2, b2) {
        const dr = r1 - r2;
        const dg = g1 - g2;
        const db = b1 - b2;
        return Math.sqrt(dr * dr + dg * dg + db * db);
    }

    function mapAdvancedEraseValue(value, inMin, inMax, outMin, outMax) {
        if (inMax === inMin) return outMin;
        const clamped = Math.min(Math.max(value, inMin), inMax);
        const ratio = (clamped - inMin) / (inMax - inMin);
        return outMin + ratio * (outMax - outMin);
    }

    function runAdvancedEraseMagicSelection(pointX, pointY) {
        if (!advancedEraseCtx || !advancedEraseCanvas) {
            return { didChange: false, reason: 'Workspace not ready' };
        }
        const width = advancedEraseCanvas.width;
        const height = advancedEraseCanvas.height;
        if (!width || !height) {
            return { didChange: false, reason: 'Canvas unavailable' };
        }
        const targetX = Math.floor(pointX);
        const targetY = Math.floor(pointY);
        if (targetX < 0 || targetX >= width || targetY < 0 || targetY >= height) {
            return { didChange: false, reason: 'Click inside the artwork' };
        }
        const imageData = advancedEraseCtx.getImageData(0, 0, width, height);
        const data = imageData.data;
        const totalPixels = width * height;
        const anchorIndex = targetY * width + targetX;
        const anchorOffset = anchorIndex * 4;
        const anchorAlpha = data[anchorOffset + 3];
        if (anchorAlpha < 20) {
            return { didChange: false, reason: 'Area already transparent' };
        }

        const tolerance = mapAdvancedEraseValue(advancedEraseAutoStrength, 5, 95, 12, 120);
        const featherPx = mapAdvancedEraseValue(advancedEraseAutoStrength, 5, 95, 0.6, 5.5);
        const stack = new Uint32Array(totalPixels);
        const visited = new Uint8Array(totalPixels);
        const mask = new Uint8Array(totalPixels);
        let stackSize = 0;
        stack[stackSize++] = anchorIndex;
        visited[anchorIndex] = 1;
        const anchorColor = {
            r: data[anchorOffset],
            g: data[anchorOffset + 1],
            b: data[anchorOffset + 2]
        };
        let selectedPixels = 0;

        while (stackSize > 0) {
            const idx = stack[--stackSize];
            const offset = idx * 4;
            const alpha = data[offset + 3];
            if (alpha < 16) continue;
            const distance = getAdvancedEraseColorDistance(
                data[offset],
                data[offset + 1],
                data[offset + 2],
                anchorColor.r,
                anchorColor.g,
                anchorColor.b
            );
            if (distance > tolerance) {
                continue;
            }
            if (mask[idx]) continue;
            mask[idx] = 1;
            selectedPixels += 1;

            const x = idx % width;
            const y = (idx - x) / width;
            if (x > 0) {
                const left = idx - 1;
                if (!visited[left]) {
                    visited[left] = 1;
                    stack[stackSize++] = left;
                }
            }
            if (x < width - 1) {
                const right = idx + 1;
                if (!visited[right]) {
                    visited[right] = 1;
                    stack[stackSize++] = right;
                }
            }
            if (y > 0) {
                const up = idx - width;
                if (!visited[up]) {
                    visited[up] = 1;
                    stack[stackSize++] = up;
                }
            }
            if (y < height - 1) {
                const down = idx + width;
                if (!visited[down]) {
                    visited[down] = 1;
                    stack[stackSize++] = down;
                }
            }
        }

        if (!selectedPixels) {
            return { didChange: false, reason: 'No similar pixels found' };
        }
        if (selectedPixels < 18) {
            return { didChange: false, reason: 'Selection too small' };
        }

        const maskCanvas = document.createElement('canvas');
        maskCanvas.width = width;
        maskCanvas.height = height;
        const maskCtx = maskCanvas.getContext('2d');
        if (!maskCtx) {
            return { didChange: false, reason: 'Mask renderer unavailable' };
        }
        const maskImage = maskCtx.createImageData(width, height);
        const maskBuffer = maskImage.data;
        for (let i = 0; i < totalPixels; i++) {
            if (!mask[i]) continue;
            const bufferOffset = i * 4;
            maskBuffer[bufferOffset] = 255;
            maskBuffer[bufferOffset + 1] = 255;
            maskBuffer[bufferOffset + 2] = 255;
            maskBuffer[bufferOffset + 3] = 255;
        }
        maskCtx.putImageData(maskImage, 0, 0);

        advancedEraseCtx.save();
        advancedEraseCtx.globalCompositeOperation = 'destination-out';
        if (featherPx > 0.75) {
            advancedEraseCtx.filter = 'blur(' + featherPx.toFixed(2) + 'px)';
        }
        advancedEraseCtx.drawImage(maskCanvas, 0, 0);
        advancedEraseCtx.restore();
        advancedEraseCtx.filter = 'none';

        return {
            didChange: true,
            removedPixels: selectedPixels,
            totalPixels,
            featherPx
        };
    }

    function advancedEraseMagicWandClick(event) {
        if (advancedEraseAutoProcessing) return;
        const point = getAdvancedEraseCanvasPoint(event);
        if (!isAdvancedEraseWithinCanvas(point)) {
            updateAdvancedEraseStatus('Click within the image bounds', 'neutral');
            return;
        }
        setAdvancedEraseAutoProcessingState(true);
        updateAdvancedEraseStatus('Tracing edges…', 'busy', 0);

        const schedule = typeof requestAnimationFrame === 'function'
            ? requestAnimationFrame
            : (cb) => setTimeout(cb, 0);

        schedule(() => {
            let stats = { didChange: false, reason: 'No edges detected' };
            try {
                stats = runAdvancedEraseMagicSelection(point.x, point.y);
            } catch (err) {
                console.error('Magic wand selection failed', err);
                stats = { didChange: false, reason: 'Selection failed' };
            } finally {
                setAdvancedEraseAutoProcessingState(false);
            }

            if (stats.didChange && stats.totalPixels) {
                pushAdvancedEraseHistory();
                const cleanedPercent = Math.min(100, (stats.removedPixels / stats.totalPixels) * 100);
                const formatted = cleanedPercent >= 1 ? cleanedPercent.toFixed(1) : cleanedPercent.toFixed(2);
                updateAdvancedEraseStatus('Precision cut applied (' + formatted + '%)', 'success');
            } else {
                updateAdvancedEraseStatus(stats.reason || 'No edges detected', 'neutral');
            }
        });
    }

    function advancedEraseReset() {
        if (!advancedEraseCtx || !advancedEraseCanvas || !advancedEraseHistory.length) return;
        const snapshot = advancedEraseHistory[0];
        const img = new Image();
        img.onload = function() {
            advancedEraseCtx.clearRect(0, 0, advancedEraseCanvas.width, advancedEraseCanvas.height);
            advancedEraseCtx.drawImage(img, 0, 0);
            pushAdvancedEraseHistory();
        };
        img.src = snapshot;
    }

    function advancedEraseCancel() {
        if (advancedEraseOverlay) {
            advancedEraseOverlay.setAttribute('data-visible', 'false');
        }
        setAdvancedEraseWandMode(false);
        advancedEraseIsDrawing = false;
        setAdvancedEraseMoveMode(false);
        setAdvancedEraseAutoProcessingState(false);
        updateAdvancedEraseStatus('Ready for auto cleanup', 'idle', 0);
        if (advancedEraseCanvas) {
            advancedEraseCanvas.style.cursor = '';
        }
    }

    function advancedEraseApply() {
        if (!currentEditingImage || !advancedEraseCanvas) {
            advancedEraseCancel();
            return;
        }
        const img = currentEditingImage.querySelector('img');
        if (!img) {
            advancedEraseCancel();
            return;
        }
        const dataUrl = advancedEraseCanvas.toDataURL('image/png');
        img.src = dataUrl;
        currentEditingImage.dataset.originalBackup = dataUrl;
        hasCanvasChanges = true;

        if (canvas) {
            setupCanvas(() => {
                pushCanvasHistoryState();
            });
        }

        advancedEraseCancel();
    }

    function applyCrop(rect, sourceCanvas) {
        if (!canvas || !ctx || !rect) return;

        const source = sourceCanvas || canvas;
        const croppedCanvas = document.createElement('canvas');
        const croppedCtx = croppedCanvas.getContext('2d');

        croppedCanvas.width = rect.width;
        croppedCanvas.height = rect.height;
        croppedCtx.drawImage(
            source,
            rect.x,
            rect.y,
            rect.width,
            rect.height,
            0,
            0,
            rect.width,
            rect.height
        );

        canvas.width = rect.width;
        canvas.height = rect.height;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(croppedCanvas, 0, 0);
        setCanvasDisplaySize(rect.width, rect.height);
        isCanvasReadyForOps = true;
    }

    function withCanvasReady(callback) {
        if (typeof callback !== 'function') return;
        if (canvas && ctx && isCanvasReadyForOps) {
            callback();
            return;
        }
        setupCanvas(() => {
            if (canvas && ctx && isCanvasReadyForOps) {
                callback();
            }
        });
    }

    function applyFilterPreset(preset) {
        withCanvasReady(() => {
            if (!canvas || !ctx) return;
            switch (preset) {
                case 'brightness':
                    applyFilterPipeline({ filterString: 'brightness(1.18) contrast(1.12)' });
                    break;
                case 'hue':
                    applyFilterPipeline({ filterString: 'hue-rotate(20deg) saturate(1.3)' });
                    break;
                case 'exposure':
                    applyFilterPipeline({ filterString: 'brightness(1.3)' });
                    break;
                case 'vibrance':
                    applyFilterPipeline({ filterString: 'contrast(1.05) saturate(1.45)' });
                    break;
                case 'color-balance':
                    applyFilterPipeline({ filterString: 'sepia(0.15) saturate(1.1) hue-rotate(-10deg)' });
                    break;
                case 'bw':
                    applyFilterPipeline({ filterString: 'grayscale(1) contrast(1.1)' });
                    break;
                case 'blur':
                    applyFilterPipeline({ filterString: 'blur(1.6px)' });
                    break;
                case 'levels':
                    applyFilterPipeline({ filterString: 'brightness(0.98) contrast(1.35)' });
                    break;
                case 'curves':
                    applyFilterPipeline({ filterString: 'brightness(1.08) contrast(1.2)' });
                    break;
                case 'light-color':
                    applyFilterPipeline({
                        filterString: 'brightness(1.05) saturate(1.1) contrast(1.05)',
                        overlayColor: 'rgba(255, 255, 255, 0.12)',
                        overlayMode: 'screen'
                    });
                    break;
                default:
                    console.warn('Unknown filter preset', preset);
                    break;
            }
        });
    }

    function applyFilterPipeline(options = {}) {
        if (!canvas || !ctx) return;
        const { filterString = '', overlayColor = null, overlayMode = 'source-over' } = options;
        const buffer = document.createElement('canvas');
        buffer.width = canvas.width;
        buffer.height = canvas.height;
        const bufferCtx = buffer.getContext('2d');
        bufferCtx.drawImage(canvas, 0, 0);

        ctx.save();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (filterString) {
            ctx.filter = filterString;
        }
        ctx.drawImage(buffer, 0, 0);
        ctx.restore();
        ctx.filter = 'none';

        if (overlayColor) {
            ctx.save();
            ctx.globalCompositeOperation = overlayMode;
            ctx.fillStyle = overlayColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
        }

        pushCanvasHistoryState();
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function setFilterPropertiesMode(mode) {
        if (!filterPropertiesCard) return;
        if (filterPropertiesMode === mode) {
            if (mode) {
                updateFilterPropertiesPanelVisibility(mode);
                updateFilterPropertiesCardCopy(mode);
            }
            return;
        }

        const previousMode = filterPropertiesMode;
        filterPropertiesMode = mode || null;

        if (previousMode === 'brightness') {
            resetBrightnessContrastState();
        } else if (previousMode === 'hue') {
            resetHueSaturationState();
        } else if (previousMode === 'exposure') {
            resetExposureState();
        } else if (previousMode === 'vibrance') {
            resetVibranceState();
        } else if (previousMode === 'blur') {
            resetBlurState();
        } else if (previousMode === 'color-balance') {
            resetColorBalanceState();
        } else if (previousMode === 'levels') {
            resetLevelsState();
        } else if (previousMode === 'curves') {
            resetCurvesState();
        } else if (previousMode === 'light-color') {
            resetLightColorState();
        } else if (previousMode === 'bw') {
            resetBlackWhiteState();
        }

        if (!filterPropertiesMode) {
            filterPropertiesCard.setAttribute('data-visible', 'false');
            filterPropertiesCard.setAttribute('aria-hidden', 'true');
            updateFilterPropertiesPanelVisibility(null);
            return;
        }

        filterPropertiesCard.setAttribute('data-visible', 'true');
        filterPropertiesCard.setAttribute('aria-hidden', 'false');
        updateFilterPropertiesPanelVisibility(filterPropertiesMode);
        updateFilterPropertiesCardCopy(filterPropertiesMode);
    }

    function updateFilterPropertiesPanelVisibility(mode) {
        if (!Array.isArray(filterPropertiesPanelEls)) return;
        filterPropertiesPanelEls.forEach((panel) => {
            const isActive = panel.getAttribute('data-filter-panel') === mode;
            panel.setAttribute('data-active', String(Boolean(isActive)));
        });
    }

    function updateFilterPropertiesCardCopy(mode) {
        const copy = FILTER_PROPERTIES_COPY[mode] || {};
        if (filterPropertiesTitleEl) {
            filterPropertiesTitleEl.textContent = copy.title || '';
        }
        if (filterPropertiesHintEl) {
            filterPropertiesHintEl.textContent = copy.hint || '';
        }
    }

    function isFilterPropertiesMode(mode) {
        return filterPropertiesMode === mode;
    }

    function activateBrightnessContrastPanel() {
        setFilterPropertiesMode('brightness');
        setBrightnessControlValue(brightnessControlValue || 0, { silent: true });
        setContrastControlValue(contrastControlValue || 0, { silent: true });
        withCanvasReady(() => {
            captureBrightnessContrastBaseSnapshot(true);
            renderBrightnessContrastAdjustments();
        });
    }

    function captureBrightnessContrastBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && brightnessContrastBaseCanvas && brightnessContrastBaseCanvas.width === canvas.width && brightnessContrastBaseCanvas.height === canvas.height) {
            return true;
        }
        brightnessContrastBaseCanvas = document.createElement('canvas');
        brightnessContrastBaseCanvas.width = canvas.width;
        brightnessContrastBaseCanvas.height = canvas.height;
        const baseCtx = brightnessContrastBaseCanvas.getContext('2d');
        baseCtx.clearRect(0, 0, brightnessContrastBaseCanvas.width, brightnessContrastBaseCanvas.height);
        baseCtx.drawImage(canvas, 0, 0);
        brightnessContrastHistoryCaptured = false;
        return true;
    }

    function renderBrightnessContrastAdjustments() {
        if (!isFilterPropertiesMode('brightness')) return;
        if (!canvas || !ctx) return;
        if (brightnessContrastBaseCanvas && (brightnessContrastBaseCanvas.width !== canvas.width || brightnessContrastBaseCanvas.height !== canvas.height)) {
            captureBrightnessContrastBaseSnapshot(true);
        }
        if (!brightnessContrastBaseCanvas) {
            const captured = captureBrightnessContrastBaseSnapshot(true);
            if (!captured) return;
        }
        const hasAdjustment = brightnessControlValue !== 0 || contrastControlValue !== 0;
        if (hasAdjustment && !brightnessContrastHistoryCaptured) {
            pushCanvasHistoryState();
            brightnessContrastHistoryCaptured = true;
        }

        ctx.save();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (hasAdjustment) {
            const brightnessFactor = Math.max(0, 1 + (brightnessControlValue / 100));
            const contrastFactor = Math.max(0, 1 + (contrastControlValue / 100));
            ctx.filter = `brightness(${brightnessFactor}) contrast(${contrastFactor})`;
        } else {
            ctx.filter = 'none';
        }
        ctx.drawImage(brightnessContrastBaseCanvas, 0, 0);
        ctx.restore();
        ctx.filter = 'none';
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateBrightnessContrastUI() {
        if (brightnessSliderEl) {
            brightnessSliderEl.value = String(brightnessControlValue);
        }
        if (brightnessInputEl) {
            brightnessInputEl.value = String(brightnessControlValue);
        }
        if (contrastSliderEl) {
            contrastSliderEl.value = String(contrastControlValue);
        }
        if (contrastInputEl) {
            contrastInputEl.value = String(contrastControlValue);
        }
    }

    function setBrightnessControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        brightnessControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateBrightnessContrastUI();
        if (!options.silent && isFilterPropertiesMode('brightness')) {
            renderBrightnessContrastAdjustments();
        }
    }

    function setContrastControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        contrastControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateBrightnessContrastUI();
        if (!options.silent && isFilterPropertiesMode('brightness')) {
            renderBrightnessContrastAdjustments();
        }
    }

    function handleBrightnessContrastReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setBrightnessControlValue(0, { silent: true });
        setContrastControlValue(0, { silent: true });
        if (!skipRender && isFilterPropertiesMode('brightness')) {
            renderBrightnessContrastAdjustments();
        } else {
            updateBrightnessContrastUI();
        }
    }

    function resetBrightnessContrastState() {
        brightnessContrastBaseCanvas = null;
        brightnessContrastHistoryCaptured = false;
        setBrightnessControlValue(0, { silent: true });
        setContrastControlValue(0, { silent: true });
        updateBrightnessContrastUI();
    }

    function activateHueSaturationPanel() {
        setFilterPropertiesMode('hue');
        updateHueSaturationUI();
        withCanvasReady(() => {
            captureHueSaturationBaseSnapshot(true);
            renderHueSaturationAdjustments();
        });
    }

    function captureHueSaturationBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && hueAdjustmentBaseCanvas && hueAdjustmentBaseCanvas.width === canvas.width && hueAdjustmentBaseCanvas.height === canvas.height) {
            return true;
        }
        hueAdjustmentBaseCanvas = document.createElement('canvas');
        hueAdjustmentBaseCanvas.width = canvas.width;
        hueAdjustmentBaseCanvas.height = canvas.height;
        const baseCtx = hueAdjustmentBaseCanvas.getContext('2d');
        baseCtx.clearRect(0, 0, hueAdjustmentBaseCanvas.width, hueAdjustmentBaseCanvas.height);
        baseCtx.drawImage(canvas, 0, 0);
        hueAdjustmentHistoryCaptured = false;
        return true;
    }

    function renderHueSaturationAdjustments() {
        if (!isFilterPropertiesMode('hue')) return;
        if (!canvas || !ctx) return;
        if (hueAdjustmentBaseCanvas && (hueAdjustmentBaseCanvas.width !== canvas.width || hueAdjustmentBaseCanvas.height !== canvas.height)) {
            captureHueSaturationBaseSnapshot(true);
        }
        if (!hueAdjustmentBaseCanvas) {
            const captured = captureHueSaturationBaseSnapshot(true);
            if (!captured) return;
        }

        const hasAdjustment = hueControlValue !== 0 || hueSaturationControlValue !== 0 || hueLightnessControlValue !== 0 || hueColorizeEnabled;
        if (hasAdjustment && !hueAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            hueAdjustmentHistoryCaptured = true;
        }

        const filterParts = [];
        if (hueColorizeEnabled) {
            filterParts.push('grayscale(1)');
        }
        if (hueControlValue !== 0) {
            filterParts.push(`hue-rotate(${hueControlValue}deg)`);
        }
        const saturationFactor = Math.max(0, 1 + (hueSaturationControlValue / 100));
        if (hueSaturationControlValue !== 0) {
            filterParts.push(`saturate(${saturationFactor})`);
        }
        const lightnessFactor = Math.max(0, 1 + (hueLightnessControlValue / 100));
        if (hueLightnessControlValue !== 0) {
            filterParts.push(`brightness(${lightnessFactor})`);
        }

        ctx.save();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.filter = filterParts.length ? filterParts.join(' ') : 'none';
        ctx.drawImage(hueAdjustmentBaseCanvas, 0, 0);
        ctx.restore();
        ctx.filter = 'none';
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateHueSaturationUI() {
        if (hueSliderEl) hueSliderEl.value = String(hueControlValue);
        if (hueInputEl) hueInputEl.value = String(hueControlValue);
        if (hueSaturationSliderEl) hueSaturationSliderEl.value = String(hueSaturationControlValue);
        if (hueSaturationInputEl) hueSaturationInputEl.value = String(hueSaturationControlValue);
        if (hueLightnessSliderEl) hueLightnessSliderEl.value = String(hueLightnessControlValue);
        if (hueLightnessInputEl) hueLightnessInputEl.value = String(hueLightnessControlValue);
        if (hueColorizeToggleEl) hueColorizeToggleEl.checked = Boolean(hueColorizeEnabled);
    }

    function setHueControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        hueControlValue = clampValue(safeValue, -180, 180);
        updateHueSaturationUI();
        if (!options.silent && isFilterPropertiesMode('hue')) {
            renderHueSaturationAdjustments();
        }
    }

    function setHueSaturationControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        hueSaturationControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateHueSaturationUI();
        if (!options.silent && isFilterPropertiesMode('hue')) {
            renderHueSaturationAdjustments();
        }
    }

    function setHueLightnessControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        hueLightnessControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateHueSaturationUI();
        if (!options.silent && isFilterPropertiesMode('hue')) {
            renderHueSaturationAdjustments();
        }
    }

    function setHueColorizeEnabled(enabled, options = {}) {
        hueColorizeEnabled = Boolean(enabled);
        updateHueSaturationUI();
        if (!options.silent && isFilterPropertiesMode('hue')) {
            renderHueSaturationAdjustments();
        }
    }

    function handleHueSaturationReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setHueControlValue(0, { silent: true });
        setHueSaturationControlValue(0, { silent: true });
        setHueLightnessControlValue(0, { silent: true });
        setHueColorizeEnabled(false, { silent: true });
        if (!skipRender && isFilterPropertiesMode('hue')) {
            renderHueSaturationAdjustments();
        } else {
            updateHueSaturationUI();
        }
    }

    function resetHueSaturationState() {
        hueAdjustmentBaseCanvas = null;
        hueAdjustmentHistoryCaptured = false;
        handleHueSaturationReset({ skipRender: true });
    }

    function activateExposurePanel() {
        setFilterPropertiesMode('exposure');
        updateExposureUI();
        withCanvasReady(() => {
            captureExposureBaseSnapshot(true);
            renderExposureAdjustments();
        });
    }

    function captureExposureBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && exposureAdjustmentBaseData && exposureAdjustmentBaseData.width === canvas.width && exposureAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            exposureAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            exposureAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture exposure base snapshot', error);
            return false;
        }
    }

    function renderExposureAdjustments() {
        if (!isFilterPropertiesMode('exposure')) return;
        if (!canvas || !ctx) return;
        if (!exposureAdjustmentBaseData || exposureAdjustmentBaseData.width !== canvas.width || exposureAdjustmentBaseData.height !== canvas.height) {
            const captured = captureExposureBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }
        const hasAdjustment = exposureControlValue !== 0 || exposureOffsetValue !== 0 || Math.abs(exposureGammaValue - 1) > 0.001;
        if (hasAdjustment && !exposureAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            exposureAdjustmentHistoryCaptured = true;
        }
        if (!hasAdjustment) {
            ctx.putImageData(exposureAdjustmentBaseData, 0, 0);
            exposureAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = exposureAdjustmentBaseData.data;
        const baseWidth = exposureAdjustmentBaseData.width || canvas.width;
        const baseHeight = exposureAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(baseWidth, baseHeight);
        const dest = output.data;
        const exposureFactor = Math.pow(2, exposureControlValue / 50);
        const offset = (exposureOffsetValue / 100) * 255;
        const gamma = Math.min(EXPOSURE_GAMMA_MAX, Math.max(EXPOSURE_GAMMA_MIN, exposureGammaValue));
        const inverseGamma = 1 / gamma;
        const adjustChannel = (value) => {
            const exposed = Math.min(255, Math.max(0, value * exposureFactor + offset));
            const normalized = Math.max(0, Math.min(1, exposed / 255));
            const corrected = Math.pow(normalized, inverseGamma);
            return Math.min(255, Math.max(0, Math.round(corrected * 255)));
        };

        for (let i = 0; i < base.length; i += 4) {
            dest[i] = adjustChannel(base[i]);
            dest[i + 1] = adjustChannel(base[i + 1]);
            dest[i + 2] = adjustChannel(base[i + 2]);
            dest[i + 3] = base[i + 3];
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateExposureUI() {
        if (exposureSliderEl) exposureSliderEl.value = String(exposureControlValue);
        if (exposureInputEl) exposureInputEl.value = String(exposureControlValue);
        if (exposureOffsetSliderEl) exposureOffsetSliderEl.value = String(exposureOffsetValue);
        if (exposureOffsetInputEl) exposureOffsetInputEl.value = String(exposureOffsetValue);
        const gammaDisplayValue = Number(exposureGammaValue.toFixed(2));
        if (exposureGammaSliderEl) exposureGammaSliderEl.value = String(gammaDisplayValue);
        if (exposureGammaInputEl) exposureGammaInputEl.value = String(gammaDisplayValue);
    }

    function setExposureControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        exposureControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateExposureUI();
        if (!options.silent && isFilterPropertiesMode('exposure')) {
            renderExposureAdjustments();
        }
    }

    function setExposureOffsetValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        exposureOffsetValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateExposureUI();
        if (!options.silent && isFilterPropertiesMode('exposure')) {
            renderExposureAdjustments();
        }
    }

    function setExposureGammaValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 1;
        const clamped = Math.min(EXPOSURE_GAMMA_MAX, Math.max(EXPOSURE_GAMMA_MIN, safeValue));
        const quantized = Math.round(clamped / EXPOSURE_GAMMA_STEP) * EXPOSURE_GAMMA_STEP;
        exposureGammaValue = Number(quantized.toFixed(2));
        updateExposureUI();
        if (!options.silent && isFilterPropertiesMode('exposure')) {
            renderExposureAdjustments();
        }
    }

    function handleExposureReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setExposureControlValue(0, { silent: true });
        setExposureOffsetValue(0, { silent: true });
        setExposureGammaValue(1, { silent: true });
        if (!skipRender && isFilterPropertiesMode('exposure')) {
            renderExposureAdjustments();
        } else {
            updateExposureUI();
        }
    }

    function resetExposureState() {
        exposureAdjustmentBaseData = null;
        exposureAdjustmentHistoryCaptured = false;
        handleExposureReset({ skipRender: true });
    }

    function activateVibrancePanel() {
        setFilterPropertiesMode('vibrance');
        updateVibranceUI();
        withCanvasReady(() => {
            captureVibranceBaseSnapshot(true);
            renderVibranceAdjustments();
        });
    }

    function captureVibranceBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && vibranceAdjustmentBaseData && vibranceAdjustmentBaseData.width === canvas.width && vibranceAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            vibranceAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            vibranceAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture vibrance base snapshot', error);
            return false;
        }
    }

    function renderVibranceAdjustments() {
        if (!isFilterPropertiesMode('vibrance')) return;
        if (!canvas || !ctx) return;
        if (!vibranceAdjustmentBaseData || vibranceAdjustmentBaseData.width !== canvas.width || vibranceAdjustmentBaseData.height !== canvas.height) {
            const captured = captureVibranceBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }

        const hasAdjustment = vibranceControlValue !== 0 || vibranceSaturationControlValue !== 0;
        if (hasAdjustment && !vibranceAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            vibranceAdjustmentHistoryCaptured = true;
        }

        if (!hasAdjustment) {
            ctx.putImageData(vibranceAdjustmentBaseData, 0, 0);
            vibranceAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = vibranceAdjustmentBaseData.data;
        const baseWidth = vibranceAdjustmentBaseData.width || canvas.width;
        const baseHeight = vibranceAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(baseWidth, baseHeight);
        const dest = output.data;
        const vibranceAmount = clampValue(vibranceControlValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX) / 100;
        const saturationAmount = clampValue(vibranceSaturationControlValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX) / 100;

        for (let i = 0; i < base.length; i += 4) {
            const r = base[i];
            const g = base[i + 1];
            const b = base[i + 2];
            const a = base[i + 3];
            const hsl = rgbToHsl(r, g, b);
            let adjustedS = hsl.s;
            if (vibranceAmount !== 0) {
                if (vibranceAmount > 0) {
                    adjustedS += (1 - adjustedS) * vibranceAmount;
                } else {
                    adjustedS += adjustedS * vibranceAmount;
                }
            }
            if (saturationAmount !== 0) {
                adjustedS *= (1 + saturationAmount);
            }
            adjustedS = clamp01(adjustedS);
            const rgb = hslToRgb(hsl.h, adjustedS, hsl.l);
            dest[i] = rgb.r;
            dest[i + 1] = rgb.g;
            dest[i + 2] = rgb.b;
            dest[i + 3] = a;
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateVibranceUI() {
        if (vibranceSliderEl) vibranceSliderEl.value = String(vibranceControlValue);
        if (vibranceInputEl) vibranceInputEl.value = String(vibranceControlValue);
        if (vibranceSaturationSliderEl) vibranceSaturationSliderEl.value = String(vibranceSaturationControlValue);
        if (vibranceSaturationInputEl) vibranceSaturationInputEl.value = String(vibranceSaturationControlValue);
    }

    function setVibranceControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        vibranceControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateVibranceUI();
        if (!options.silent && isFilterPropertiesMode('vibrance')) {
            renderVibranceAdjustments();
        }
    }

    function setVibranceSaturationControlValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        vibranceSaturationControlValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateVibranceUI();
        if (!options.silent && isFilterPropertiesMode('vibrance')) {
            renderVibranceAdjustments();
        }
    }

    function handleVibranceReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setVibranceControlValue(0, { silent: true });
        setVibranceSaturationControlValue(0, { silent: true });
        if (!skipRender && isFilterPropertiesMode('vibrance')) {
            renderVibranceAdjustments();
        } else {
            updateVibranceUI();
        }
    }

    function resetVibranceState() {
        vibranceAdjustmentBaseData = null;
        vibranceAdjustmentHistoryCaptured = false;
        handleVibranceReset({ skipRender: true });
    }

    function activateBlurPanel() {
        setFilterPropertiesMode('blur');
        updateBlurUI();
        withCanvasReady(() => {
            captureBlurBaseSnapshot(true);
            renderBlurAdjustments();
        });
    }

    function captureBlurBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && blurBaseCanvas && blurBaseCanvas.width === canvas.width && blurBaseCanvas.height === canvas.height) {
            return true;
        }
        blurBaseCanvas = document.createElement('canvas');
        blurBaseCanvas.width = canvas.width;
        blurBaseCanvas.height = canvas.height;
        const baseCtx = blurBaseCanvas.getContext('2d');
        baseCtx.clearRect(0, 0, blurBaseCanvas.width, blurBaseCanvas.height);
        baseCtx.drawImage(canvas, 0, 0);
        blurHistoryCaptured = false;
        return true;
    }

    function renderBlurAdjustments() {
        if (!isFilterPropertiesMode('blur')) return;
        if (!canvas || !ctx) return;
        if (!blurBaseCanvas || blurBaseCanvas.width !== canvas.width || blurBaseCanvas.height !== canvas.height) {
            const captured = captureBlurBaseSnapshot(true);
            if (!captured) return;
        }
        const hasAdjustment = blurValue > 0.001;
        if (hasAdjustment && !blurHistoryCaptured) {
            pushCanvasHistoryState();
            blurHistoryCaptured = true;
        }
        if (!hasAdjustment) {
            blurHistoryCaptured = false;
        }

        ctx.save();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.filter = hasAdjustment ? `blur(${blurValue}px)` : 'none';
        ctx.drawImage(blurBaseCanvas, 0, 0);
        ctx.restore();
        ctx.filter = 'none';
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateBlurUI() {
        if (blurSliderEl) blurSliderEl.value = String(blurValue);
        if (blurInputEl) blurInputEl.value = Number(blurValue).toFixed(1);
    }

    function setBlurValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        const clamped = Math.min(GAUSSIAN_BLUR_MAX, Math.max(GAUSSIAN_BLUR_MIN, safeValue));
        blurValue = Number(clamped.toFixed(1));
        updateBlurUI();
        if (!options.silent && isFilterPropertiesMode('blur')) {
            renderBlurAdjustments();
        }
    }

    function handleBlurReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setBlurValue(0, { silent: true });
        if (!skipRender && isFilterPropertiesMode('blur')) {
            renderBlurAdjustments();
        } else {
            updateBlurUI();
        }
    }

    function resetBlurState() {
        blurBaseCanvas = null;
        blurHistoryCaptured = false;
        setBlurValue(0, { silent: true });
        updateBlurUI();
    }

    function activateColorBalancePanel() {
        setFilterPropertiesMode('color-balance');
        updateColorBalanceUI();
        withCanvasReady(() => {
            captureColorBalanceBaseSnapshot(true);
            renderColorBalanceAdjustments();
        });
    }

    function captureColorBalanceBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && colorBalanceAdjustmentBaseData && colorBalanceAdjustmentBaseData.width === canvas.width && colorBalanceAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            colorBalanceAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            colorBalanceAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture color balance base snapshot', error);
            return false;
        }
    }

    function renderColorBalanceAdjustments() {
        if (!isFilterPropertiesMode('color-balance')) return;
        if (!canvas || !ctx) return;
        if (!colorBalanceAdjustmentBaseData || colorBalanceAdjustmentBaseData.width !== canvas.width || colorBalanceAdjustmentBaseData.height !== canvas.height) {
            const captured = captureColorBalanceBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }

        const hasAdjustment = colorBalanceCyanRedValue !== 0 || colorBalanceMagentaGreenValue !== 0 || colorBalanceYellowBlueValue !== 0;
        if (hasAdjustment && !colorBalanceAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            colorBalanceAdjustmentHistoryCaptured = true;
        }

        if (!hasAdjustment) {
            ctx.putImageData(colorBalanceAdjustmentBaseData, 0, 0);
            colorBalanceAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = colorBalanceAdjustmentBaseData.data;
        const baseWidth = colorBalanceAdjustmentBaseData.width || canvas.width;
        const baseHeight = colorBalanceAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(baseWidth, baseHeight);
        const dest = output.data;
        const cyanRed = colorBalanceCyanRedValue / 100;
        const magentaGreen = colorBalanceMagentaGreenValue / 100;
        const yellowBlue = colorBalanceYellowBlueValue / 100;

        for (let i = 0; i < base.length; i += 4) {
            const r = base[i];
            const g = base[i + 1];
            const b = base[i + 2];
            const a = base[i + 3];
            const luminance = getLuminance(r, g, b);
            const toneWeight = getColorBalanceToneWeight(luminance, colorBalanceToneValue);
            if (toneWeight <= 0) {
                dest[i] = r;
                dest[i + 1] = g;
                dest[i + 2] = b;
                dest[i + 3] = a;
                continue;
            }
            let newR = r + 255 * cyanRed * toneWeight;
            let newG = g + 255 * magentaGreen * toneWeight;
            let newB = b + 255 * yellowBlue * toneWeight;
            if (colorBalancePreserveLuminosity) {
                const originalLum = luminance;
                const adjustedLum = getLuminance(newR, newG, newB);
                const diff = (adjustedLum - originalLum) * 255;
                newR -= diff;
                newG -= diff;
                newB -= diff;
            }
            dest[i] = clampChannel(newR);
            dest[i + 1] = clampChannel(newG);
            dest[i + 2] = clampChannel(newB);
            dest[i + 3] = a;
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateColorBalanceUI() {
        if (colorBalanceToneSelectEl) {
            colorBalanceToneSelectEl.value = colorBalanceToneValue;
        }
        if (colorBalanceCyanRedSliderEl) colorBalanceCyanRedSliderEl.value = String(colorBalanceCyanRedValue);
        if (colorBalanceCyanRedInputEl) colorBalanceCyanRedInputEl.value = String(colorBalanceCyanRedValue);
        if (colorBalanceMagentaGreenSliderEl) colorBalanceMagentaGreenSliderEl.value = String(colorBalanceMagentaGreenValue);
        if (colorBalanceMagentaGreenInputEl) colorBalanceMagentaGreenInputEl.value = String(colorBalanceMagentaGreenValue);
        if (colorBalanceYellowBlueSliderEl) colorBalanceYellowBlueSliderEl.value = String(colorBalanceYellowBlueValue);
        if (colorBalanceYellowBlueInputEl) colorBalanceYellowBlueInputEl.value = String(colorBalanceYellowBlueValue);
        if (colorBalancePreserveLuminosityToggleEl) colorBalancePreserveLuminosityToggleEl.checked = Boolean(colorBalancePreserveLuminosity);
    }

    function setColorBalanceToneValue(value, options = {}) {
        const nextValue = ['shadows', 'midtones', 'highlights'].includes(value) ? value : 'midtones';
        colorBalanceToneValue = nextValue;
        updateColorBalanceUI();
        if (!options.silent && isFilterPropertiesMode('color-balance')) {
            renderColorBalanceAdjustments();
        }
    }

    function setColorBalanceCyanRedValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        colorBalanceCyanRedValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateColorBalanceUI();
        if (!options.silent && isFilterPropertiesMode('color-balance')) {
            renderColorBalanceAdjustments();
        }
    }

    function setColorBalanceMagentaGreenValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        colorBalanceMagentaGreenValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateColorBalanceUI();
        if (!options.silent && isFilterPropertiesMode('color-balance')) {
            renderColorBalanceAdjustments();
        }
    }

    function setColorBalanceYellowBlueValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        colorBalanceYellowBlueValue = clampValue(safeValue, FILTER_ADJUST_MIN, FILTER_ADJUST_MAX);
        updateColorBalanceUI();
        if (!options.silent && isFilterPropertiesMode('color-balance')) {
            renderColorBalanceAdjustments();
        }
    }

    function setColorBalancePreserveLuminosity(enabled, options = {}) {
        colorBalancePreserveLuminosity = Boolean(enabled);
        updateColorBalanceUI();
        if (!options.silent && isFilterPropertiesMode('color-balance')) {
            renderColorBalanceAdjustments();
        }
    }

    function handleColorBalanceReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setColorBalanceToneValue('midtones', { silent: true });
        setColorBalanceCyanRedValue(0, { silent: true });
        setColorBalanceMagentaGreenValue(0, { silent: true });
        setColorBalanceYellowBlueValue(0, { silent: true });
        setColorBalancePreserveLuminosity(true, { silent: true });
        if (!skipRender && isFilterPropertiesMode('color-balance')) {
            renderColorBalanceAdjustments();
        } else {
            updateColorBalanceUI();
        }
    }

    function resetColorBalanceState() {
        colorBalanceAdjustmentBaseData = null;
        colorBalanceAdjustmentHistoryCaptured = false;
        handleColorBalanceReset({ skipRender: true });
    }

    function activateLevelsPanel() {
        setFilterPropertiesMode('levels');
        setLevelsActiveChannel(levelsActiveChannel || 'rgb', { silent: true, force: true });
        updateLevelsUI();
        withCanvasReady(() => {
            captureLevelsBaseSnapshot(true);
            renderLevelsAdjustments();
        });
    }

    function captureLevelsBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && levelsAdjustmentBaseData && levelsAdjustmentBaseData.width === canvas.width && levelsAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            levelsAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            levelsAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture levels base snapshot', error);
            return false;
        }
    }

    function renderLevelsAdjustments() {
        if (!isFilterPropertiesMode('levels')) return;
        if (!canvas || !ctx) return;
        if (!levelsAdjustmentBaseData || levelsAdjustmentBaseData.width !== canvas.width || levelsAdjustmentBaseData.height !== canvas.height) {
            const captured = captureLevelsBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }

        const hasAdjustment = LEVELS_CHANNELS.some((channel) => hasLevelsChanges(levelsChannelState[channel]));
        if (hasAdjustment && !levelsAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            levelsAdjustmentHistoryCaptured = true;
        }

        if (!hasAdjustment) {
            ctx.putImageData(levelsAdjustmentBaseData, 0, 0);
            levelsAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = levelsAdjustmentBaseData.data;
        const baseWidth = levelsAdjustmentBaseData.width || canvas.width;
        const baseHeight = levelsAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(baseWidth, baseHeight);
        const dest = output.data;
        const rgbState = levelsChannelState.rgb;
        const redState = levelsChannelState.red;
        const greenState = levelsChannelState.green;
        const blueState = levelsChannelState.blue;

        for (let i = 0; i < base.length; i += 4) {
            dest[i] = applyLevelsChain(base[i], rgbState, redState);
            dest[i + 1] = applyLevelsChain(base[i + 1], rgbState, greenState);
            dest[i + 2] = applyLevelsChain(base[i + 2], rgbState, blueState);
            dest[i + 3] = base[i + 3];
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateLevelsUI() {
        const state = getLevelsChannelState(levelsActiveChannel);
        if (!state) return;
        const values = {
            inBlack: state.inBlack,
            inMid: Number(state.inMid.toFixed(2)),
            inWhite: state.inWhite,
            outBlack: state.outBlack,
            outWhite: state.outWhite
        };
        if (levelsInputBlackSliderEl) levelsInputBlackSliderEl.value = String(values.inBlack);
        if (levelsInputBlackInputEl) levelsInputBlackInputEl.value = String(values.inBlack);
        if (levelsInputMidSliderEl) levelsInputMidSliderEl.value = String(values.inMid);
        if (levelsInputMidInputEl) levelsInputMidInputEl.value = String(values.inMid);
        if (levelsInputWhiteSliderEl) levelsInputWhiteSliderEl.value = String(values.inWhite);
        if (levelsInputWhiteInputEl) levelsInputWhiteInputEl.value = String(values.inWhite);
        if (levelsOutputBlackSliderEl) levelsOutputBlackSliderEl.value = String(values.outBlack);
        if (levelsOutputBlackInputEl) levelsOutputBlackInputEl.value = String(values.outBlack);
        if (levelsOutputWhiteSliderEl) levelsOutputWhiteSliderEl.value = String(values.outWhite);
        if (levelsOutputWhiteInputEl) levelsOutputWhiteInputEl.value = String(values.outWhite);
        if (Array.isArray(levelsChannelBtnEls)) {
            levelsChannelBtnEls.forEach((btn) => {
                const isActive = btn?.getAttribute('data-levels-channel') === levelsActiveChannel;
                btn?.classList.toggle('active', Boolean(isActive));
                if (btn) {
                    btn.setAttribute('aria-pressed', String(Boolean(isActive)));
                }
            });
        }
    }

    function setLevelsActiveChannel(channel, options = {}) {
        const safeChannel = LEVELS_CHANNELS.includes(channel) ? channel : 'rgb';
        const force = Boolean(options.force);
        if (!force && levelsActiveChannel === safeChannel) {
            updateLevelsUI();
            return;
        }
        levelsActiveChannel = safeChannel;
        updateLevelsUI();
        if (!options.silent && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function setLevelsInputBlackValue(value, options = {}) {
        const state = getLevelsChannelState(levelsActiveChannel);
        if (!state) return;
        const numericValue = Number(value);
        const safe = clampValue(numericValue, LEVELS_INPUT_MIN, LEVELS_INPUT_MAX - 1);
        state.inBlack = Math.min(safe, state.inWhite - 1);
        updateLevelsUI();
        if (!options.silent && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function setLevelsInputMidValue(value, options = {}) {
        const state = getLevelsChannelState(levelsActiveChannel);
        if (!state) return;
        const numericValue = Number(value);
        const safe = Number.isFinite(numericValue) ? numericValue : 1;
        const clamped = Math.min(LEVELS_MID_MAX, Math.max(LEVELS_MID_MIN, safe));
        state.inMid = clamped;
        updateLevelsUI();
        if (!options.silent && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function setLevelsInputWhiteValue(value, options = {}) {
        const state = getLevelsChannelState(levelsActiveChannel);
        if (!state) return;
        const numericValue = Number(value);
        const safe = clampValue(numericValue, LEVELS_INPUT_MIN + 1, LEVELS_INPUT_MAX);
        state.inWhite = Math.max(safe, state.inBlack + 1);
        updateLevelsUI();
        if (!options.silent && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function setLevelsOutputBlackValue(value, options = {}) {
        const state = getLevelsChannelState(levelsActiveChannel);
        if (!state) return;
        const numericValue = Number(value);
        const safe = clampValue(numericValue, LEVELS_OUTPUT_MIN, LEVELS_OUTPUT_MAX - 1);
        state.outBlack = Math.min(safe, state.outWhite - 1);
        updateLevelsUI();
        if (!options.silent && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function setLevelsOutputWhiteValue(value, options = {}) {
        const state = getLevelsChannelState(levelsActiveChannel);
        if (!state) return;
        const numericValue = Number(value);
        const safe = clampValue(numericValue, LEVELS_OUTPUT_MIN + 1, LEVELS_OUTPUT_MAX);
        state.outWhite = Math.max(safe, state.outBlack + 1);
        updateLevelsUI();
        if (!options.silent && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function handleLevelsReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        levelsChannelState = LEVELS_CHANNELS.reduce((acc, channel) => {
            acc[channel] = createDefaultLevelsState();
            return acc;
        }, {});
        setLevelsActiveChannel('rgb', { silent: true, force: true });
        updateLevelsUI();
        if (!skipRender && isFilterPropertiesMode('levels')) {
            renderLevelsAdjustments();
        }
    }

    function resetLevelsState() {
        levelsAdjustmentBaseData = null;
        levelsAdjustmentHistoryCaptured = false;
        handleLevelsReset({ skipRender: true });
    }

    function hasLevelsChanges(state) {
        if (!state) return false;
        return (
            state.inBlack !== LEVELS_INPUT_MIN ||
            state.inWhite !== LEVELS_INPUT_MAX ||
            Math.abs(state.inMid - 1) > 0.001 ||
            state.outBlack !== LEVELS_OUTPUT_MIN ||
            state.outWhite !== LEVELS_OUTPUT_MAX
        );
    }

    function getLevelsChannelState(channel) {
        const safeChannel = LEVELS_CHANNELS.includes(channel) ? channel : 'rgb';
        if (!levelsChannelState[safeChannel]) {
            levelsChannelState[safeChannel] = createDefaultLevelsState();
        }
        return levelsChannelState[safeChannel];
    }

    function applyLevelsChain(value, baseState, channelState) {
        let result = value;
        if (baseState && hasLevelsChanges(baseState)) {
            result = applyLevelsStage(result, baseState);
        }
        if (channelState && channelState !== baseState && hasLevelsChanges(channelState)) {
            result = applyLevelsStage(result, channelState);
        }
        return result;
    }

    function applyLevelsStage(value, state) {
        const inputBlack = state.inBlack / 255;
        const inputWhite = state.inWhite / 255;
        const normalizedInput = clamp01((value / 255 - inputBlack) / Math.max(0.0001, inputWhite - inputBlack));
        const gamma = Math.min(LEVELS_MID_MAX, Math.max(LEVELS_MID_MIN, state.inMid || 1));
        const gammaAdjusted = Math.pow(normalizedInput, 1 / gamma);
        const outBlack = state.outBlack / 255;
        const outWhite = state.outWhite / 255;
        const mapped = outBlack + gammaAdjusted * (outWhite - outBlack);
        return clampChannel(mapped * 255);
    }

    function activateCurvesPanel() {
        setFilterPropertiesMode('curves');
        setCurvesMode(curvesActiveMode || 'points', { silent: true, force: true });
        setCurvesActiveChannel(curvesActiveChannel || 'rgb', { silent: true, force: true });
        updateCurvesUI();
        withCanvasReady(() => {
            captureCurvesBaseSnapshot(true);
            renderCurvesAdjustments();
        });
    }

    function setCurvesMode(mode, options = {}) {
        const allowed = ['points', 'draw', 'auto'];
        const safeMode = allowed.includes(mode) ? mode : 'points';
        const force = Boolean(options.force);
        if (!force && curvesActiveMode === safeMode) {
            if (!options.silent) {
                updateCurvesUI();
            }
            return;
        }
        curvesActiveMode = safeMode;
        if (!options.silent) {
            updateCurvesUI();
        }
    }

    function setCurvesActiveChannel(channel, options = {}) {
        const safeChannel = CURVES_CHANNELS.includes(channel) ? channel : 'rgb';
        const force = Boolean(options.force);
        if (!force && curvesActiveChannel === safeChannel) {
            if (!options.silent) {
                updateCurvesUI();
            }
            return;
        }
        curvesActiveChannel = safeChannel;
        const points = getCurvesPoints(safeChannel);
        curvesSelectedPointIndex = points.length > 2 ? 1 : (points.length ? points.length - 1 : null);
        if (!options.silent) {
            updateCurvesUI();
        }
    }

    function updateCurvesUI() {
        const points = getCurvesPoints();
        if (curvesSelectedPointIndex == null || curvesSelectedPointIndex >= points.length) {
            curvesSelectedPointIndex = points.length > 2 ? 1 : (points.length ? points.length - 1 : null);
        }
        if (Array.isArray(curvesModeButtons) && curvesModeButtons.length) {
            curvesModeButtons.forEach((btn) => {
                if (!btn) return;
                const mode = btn.getAttribute('data-curves-mode');
                const isActive = mode === curvesActiveMode;
                btn.classList.toggle('active', Boolean(isActive));
                btn.setAttribute('aria-pressed', String(Boolean(isActive)));
            });
        }
        if (Array.isArray(curvesChannelButtons) && curvesChannelButtons.length) {
            curvesChannelButtons.forEach((btn) => {
                if (!btn) return;
                const channel = btn.getAttribute('data-curves-channel');
                const isActive = channel === curvesActiveChannel;
                btn.classList.toggle('active', Boolean(isActive));
                btn.setAttribute('aria-pressed', String(Boolean(isActive)));
            });
        }
        updateCurvesMetaDisplays();
        drawCurvesGraph();
        requestCurvesRender();
    }

    function updateCurvesMetaDisplays() {
        const selectedPoint = typeof curvesSelectedPointIndex === 'number'
            ? getCurvesPoints()[curvesSelectedPointIndex] || null
            : null;
        if (curvesInputValueEl) {
            curvesInputValueEl.textContent = selectedPoint ? Math.round(selectedPoint.x) : '—';
        }
        if (curvesOutputValueEl) {
            curvesOutputValueEl.textContent = selectedPoint ? Math.round(selectedPoint.y) : '—';
        }
        if (curvesDeletePointBtn) {
            const disableDelete = !selectedPoint || selectedPoint.locked || getCurvesPoints().length <= 2;
            curvesDeletePointBtn.disabled = disableDelete;
        }
    }

    function requestCurvesRender() {
        if (!isFilterPropertiesMode('curves')) return;
        withCanvasReady(() => {
            renderCurvesAdjustments();
        });
    }

    function drawCurvesGraph() {
        if (!curvesCanvasEl || !curvesCanvasCtx) return;
        const width = curvesCanvasEl.width || CURVES_CANVAS_WIDTH;
        const height = curvesCanvasEl.height || CURVES_CANVAS_HEIGHT;
        const padding = CURVES_GRAPH_PADDING;
        const graphWidth = Math.max(10, width - padding * 2);
        const graphHeight = Math.max(10, height - padding * 2);

        curvesCanvasCtx.clearRect(0, 0, width, height);
        const bgGradient = curvesCanvasCtx.createLinearGradient(0, 0, 0, height);
        bgGradient.addColorStop(0, '#f8fafc');
        bgGradient.addColorStop(1, '#f1f5f9');
        curvesCanvasCtx.fillStyle = bgGradient;
        curvesCanvasCtx.fillRect(0, 0, width, height);

        curvesCanvasCtx.strokeStyle = '#e5e7eb';
        curvesCanvasCtx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const horizontal = padding + (graphHeight / 4) * i;
            curvesCanvasCtx.beginPath();
            curvesCanvasCtx.moveTo(padding, horizontal);
            curvesCanvasCtx.lineTo(width - padding, horizontal);
            curvesCanvasCtx.stroke();

            const vertical = padding + (graphWidth / 4) * i;
            curvesCanvasCtx.beginPath();
            curvesCanvasCtx.moveTo(vertical, padding);
            curvesCanvasCtx.lineTo(vertical, height - padding);
            curvesCanvasCtx.stroke();
        }

        curvesCanvasCtx.save();
        curvesCanvasCtx.strokeStyle = 'rgba(15, 23, 42, 0.35)';
        curvesCanvasCtx.setLineDash([5, 5]);
        curvesCanvasCtx.beginPath();
        curvesCanvasCtx.moveTo(padding, height - padding);
        curvesCanvasCtx.lineTo(width - padding, padding);
        curvesCanvasCtx.stroke();
        curvesCanvasCtx.restore();

        const points = getCurvesPoints();
        if (!points.length) return;
        curvesCanvasCtx.strokeStyle = CURVES_COLORS[curvesActiveChannel] || '#111827';
        curvesCanvasCtx.lineWidth = 2;
        curvesCanvasCtx.beginPath();
        points.forEach((point, index) => {
            const coords = curveValueToCanvas(point);
            if (index === 0) {
                curvesCanvasCtx.moveTo(coords.x, coords.y);
            } else {
                curvesCanvasCtx.lineTo(coords.x, coords.y);
            }
        });
        curvesCanvasCtx.stroke();

        points.forEach((point, index) => {
            const coords = curveValueToCanvas(point);
            const isSelected = index === curvesSelectedPointIndex;
            curvesCanvasCtx.fillStyle = isSelected ? '#fef3c7' : '#ffffff';
            curvesCanvasCtx.strokeStyle = isSelected ? (CURVES_COLORS[curvesActiveChannel] || '#111827') : '#94a3b8';
            curvesCanvasCtx.lineWidth = 2;
            curvesCanvasCtx.beginPath();
            curvesCanvasCtx.arc(coords.x, coords.y, Math.max(3, CURVES_POINT_RADIUS - 2), 0, Math.PI * 2);
            curvesCanvasCtx.fill();
            curvesCanvasCtx.stroke();
        });
    }

    function markCurvesChannelDirty(channel) {
        const safeChannel = CURVES_CHANNELS.includes(channel) ? channel : 'rgb';
        curvesLookupDirty[safeChannel] = true;
    }

    function markAllCurvesDirty() {
        CURVES_CHANNELS.forEach((channel) => {
            curvesLookupDirty[channel] = true;
        });
    }

    function captureCurvesBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && curvesAdjustmentBaseData && curvesAdjustmentBaseData.width === canvas.width && curvesAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            curvesAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            curvesAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture curves base snapshot', error);
            return false;
        }
    }

    function hasCurvesAdjustments() {
        return CURVES_CHANNELS.some((channel) => hasCurvesChanges(channel));
    }

    function hasCurvesChanges(channel) {
        return !isCurvesDefault(getCurvesPoints(channel));
    }

    function isCurvesDefault(points) {
        if (!Array.isArray(points)) return true;
        if (points.length !== CURVES_DEFAULT_POINTS.length) return false;
        for (let i = 0; i < CURVES_DEFAULT_POINTS.length; i++) {
            const base = CURVES_DEFAULT_POINTS[i];
            const current = points[i];
            if (!current || current.x !== base.x || current.y !== base.y) {
                return false;
            }
        }
        return true;
    }

    function getCurveLookup(channel) {
        const safeChannel = CURVES_CHANNELS.includes(channel) ? channel : 'rgb';
        if (!curvesLookupTables[safeChannel] || curvesLookupDirty[safeChannel]) {
            curvesLookupTables[safeChannel] = buildCurveLookupFromPoints(getCurvesPoints(safeChannel));
            curvesLookupDirty[safeChannel] = false;
        }
        return curvesLookupTables[safeChannel];
    }

    function buildCurveLookupFromPoints(points) {
        const table = new Uint8ClampedArray(256);
        if (!Array.isArray(points) || !points.length) {
            for (let i = 0; i < 256; i++) {
                table[i] = i;
            }
            return table;
        }
        const sorted = [...points].sort((a, b) => a.x - b.x);
        for (let i = 0; i < 256; i++) {
            const x = i;
            let prev = sorted[0];
            let next = sorted[sorted.length - 1];
            for (let j = 1; j < sorted.length; j++) {
                if (x <= sorted[j].x) {
                    next = sorted[j];
                    prev = sorted[j - 1] || sorted[0];
                    break;
                }
                prev = sorted[j];
            }
            const span = Math.max(1, next.x - prev.x);
            const t = Math.min(1, Math.max(0, (x - prev.x) / span));
            const y = prev.y + (next.y - prev.y) * t;
            table[i] = clampChannel(y);
        }
        return table;
    }

    function renderCurvesAdjustments() {
        if (!isFilterPropertiesMode('curves')) return;
        if (!canvas || !ctx) return;
        if (!curvesAdjustmentBaseData || curvesAdjustmentBaseData.width !== canvas.width || curvesAdjustmentBaseData.height !== canvas.height) {
            const captured = captureCurvesBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }
        const hasAdjustment = hasCurvesAdjustments();
        if (hasAdjustment && !curvesAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            curvesAdjustmentHistoryCaptured = true;
        }
        if (!hasAdjustment) {
            ctx.putImageData(curvesAdjustmentBaseData, 0, 0);
            curvesAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = curvesAdjustmentBaseData.data;
        const width = curvesAdjustmentBaseData.width || canvas.width;
        const height = curvesAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(width, height);
        const dest = output.data;
        const masterLookup = getCurveLookup('rgb');
        const redLookup = getCurveLookup('red');
        const greenLookup = getCurveLookup('green');
        const blueLookup = getCurveLookup('blue');

        for (let i = 0; i < base.length; i += 4) {
            dest[i] = applyCurvesLookupChain(base[i], masterLookup, redLookup);
            dest[i + 1] = applyCurvesLookupChain(base[i + 1], masterLookup, greenLookup);
            dest[i + 2] = applyCurvesLookupChain(base[i + 2], masterLookup, blueLookup);
            dest[i + 3] = base[i + 3];
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function applyCurvesLookupChain(value, masterLookup, channelLookup) {
        let result = clampChannel(value);
        if (masterLookup) {
            result = masterLookup[result] ?? result;
        }
        if (channelLookup && channelLookup !== masterLookup) {
            result = channelLookup[result] ?? result;
        }
        return clampChannel(result);
    }

    function handleCurvesDeletePoint() {
        if (curvesSelectedPointIndex == null) return;
        const points = getCurvesPoints();
        if (!points.length) return;
        const target = points[curvesSelectedPointIndex];
        if (!target || target.locked || points.length <= 2) return;
        points.splice(curvesSelectedPointIndex, 1);
        const fallbackIndex = Math.min(curvesSelectedPointIndex, points.length - 1);
        curvesSelectedPointIndex = fallbackIndex >= 0 ? fallbackIndex : null;
        markCurvesChannelDirty(curvesActiveChannel);
        updateCurvesUI();
    }

    function handleCurvesReset(options = {}) {
        const silent = Boolean(options.silent);
        curvesChannelState = createCurvesStateSnapshot();
        setCurvesActiveChannel('rgb', { silent: true, force: true });
        curvesSelectedPointIndex = getCurvesPoints('rgb').length > 2 ? 1 : 0;
        markAllCurvesDirty();
        curvesAdjustmentBaseData = null;
        curvesAdjustmentHistoryCaptured = false;
        if (!silent) {
            updateCurvesUI();
        } else {
            updateCurvesMetaDisplays();
            drawCurvesGraph();
        }
    }

    function resetCurvesState() {
        curvesIsDraggingPoint = false;
        curvesDragPointIndex = null;
        curvesActivePointerId = null;
        curvesSelectedPointIndex = null;
        curvesAdjustmentBaseData = null;
        curvesAdjustmentHistoryCaptured = false;
    }

    function getCurvesPoints(channel = curvesActiveChannel) {
        const safeChannel = CURVES_CHANNELS.includes(channel) ? channel : 'rgb';
        if (!Array.isArray(curvesChannelState[safeChannel])) {
            curvesChannelState[safeChannel] = createDefaultCurvesPoints();
        }
        return curvesChannelState[safeChannel];
    }

    function insertCurvesPoint(point) {
        const points = getCurvesPoints();
        if (points.length >= CURVES_MAX_POINTS) {
            console.warn('Curve point limit reached');
            return null;
        }
        const newPoint = {
            x: clampValue(point.x, 0, 255),
            y: clampValue(point.y, 0, 255),
            locked: false
        };
        let insertIndex = points.findIndex((existing) => newPoint.x < existing.x);
        if (insertIndex === -1) {
            insertIndex = points.length;
        }
        const prev = points[insertIndex - 1];
        const next = points[insertIndex];
        if (prev && next && next.x - prev.x <= 2) {
            console.warn('Not enough space for a new control point between existing neighbors');
            return null;
        }
        if (prev) {
            newPoint.x = Math.max(prev.x + 1, newPoint.x);
        }
        if (next) {
            newPoint.x = Math.min(next.x - 1, newPoint.x);
        }
        newPoint.x = clampValue(newPoint.x, 0, 255);
        points.splice(insertIndex, 0, newPoint);
        markCurvesChannelDirty(curvesActiveChannel);
        return insertIndex;
    }

    function handleCurvesPointerDown(event) {
        if (!curvesCanvasEl || !curvesCanvasCtx) return;
        const coords = getCurveCoordinatesFromPointer(event);
        if (!coords) return;
        const hitIndex = findCurvesPointIndexAt(coords.canvasX, coords.canvasY);
        if (hitIndex != null) {
            curvesIsDraggingPoint = true;
            curvesDragPointIndex = hitIndex;
            curvesActivePointerId = event.pointerId;
            if (typeof curvesCanvasEl.setPointerCapture === 'function') {
                try {
                    curvesCanvasEl.setPointerCapture(event.pointerId);
                } catch (error) {
                    console.warn('Pointer capture failed', error);
                }
            }
            curvesSelectedPointIndex = hitIndex;
            updateCurvesMetaDisplays();
            drawCurvesGraph();
            event.preventDefault();
            return;
        }
        if (curvesActiveMode !== 'points') {
            return;
        }
        const inserted = insertCurvesPoint({ x: coords.valueX, y: coords.valueY });
        if (inserted == null) {
            return;
        }
        curvesSelectedPointIndex = inserted;
        curvesIsDraggingPoint = true;
        curvesDragPointIndex = inserted;
        curvesActivePointerId = event.pointerId;
        if (typeof curvesCanvasEl.setPointerCapture === 'function') {
            try {
                curvesCanvasEl.setPointerCapture(event.pointerId);
            } catch (error) {
                console.warn('Pointer capture failed', error);
            }
        }
        updateCurvesUI();
        event.preventDefault();
    }

    function handleCurvesPointerMove(event) {
        if (!curvesIsDraggingPoint || curvesDragPointIndex == null) return;
        if (!curvesCanvasEl || !curvesCanvasCtx) return;
        if (curvesActivePointerId != null && event.pointerId !== curvesActivePointerId) {
            return;
        }
        const coords = getCurveCoordinatesFromPointer(event);
        if (!coords) return;
        const points = getCurvesPoints();
        const target = points[curvesDragPointIndex];
        if (!target || target.locked) return;
        const prev = points[curvesDragPointIndex - 1];
        const next = points[curvesDragPointIndex + 1];
        const minX = prev ? prev.x + 1 : 0;
        const maxX = next ? next.x - 1 : 255;
        target.x = clampValue(coords.valueX, minX, maxX);
        target.y = clampValue(coords.valueY, 0, 255);
        markCurvesChannelDirty(curvesActiveChannel);
        updateCurvesMetaDisplays();
        drawCurvesGraph();
        event.preventDefault();
    }

    function handleCurvesPointerUp(event) {
        if (curvesActivePointerId != null && event.pointerId !== curvesActivePointerId && event.type === 'pointerup') {
            return;
        }
        if (curvesCanvasEl && typeof curvesCanvasEl.releasePointerCapture === 'function' && curvesActivePointerId != null) {
            try {
                curvesCanvasEl.releasePointerCapture(curvesActivePointerId);
            } catch (error) {
                console.warn('Release pointer capture failed', error);
            }
        }
        curvesIsDraggingPoint = false;
        curvesDragPointIndex = null;
        curvesActivePointerId = null;
    }

    function bindCurvesPointerEvents() {
        if (curvesPointerEventsBound || typeof window === 'undefined') return;
        curvesPointerEventsBound = true;
        window.addEventListener('pointermove', handleCurvesPointerMove);
        window.addEventListener('pointerup', handleCurvesPointerUp);
        window.addEventListener('pointercancel', handleCurvesPointerUp);
    }

    function getCurveCoordinatesFromPointer(event) {
        if (!curvesCanvasEl) return null;
        const rect = curvesCanvasEl.getBoundingClientRect();
        if (!rect.width || !rect.height) return null;
        const width = curvesCanvasEl.width || CURVES_CANVAS_WIDTH;
        const height = curvesCanvasEl.height || CURVES_CANVAS_HEIGHT;
        const scaleX = width / rect.width;
        const scaleY = height / rect.height;
        const canvasX = (event.clientX - rect.left) * scaleX;
        const canvasY = (event.clientY - rect.top) * scaleY;
        const padding = CURVES_GRAPH_PADDING;
        const graphWidth = Math.max(1, width - padding * 2);
        const graphHeight = Math.max(1, height - padding * 2);
        const clampedX = clampValue(canvasX, padding, padding + graphWidth);
        const clampedY = clampValue(canvasY, padding, padding + graphHeight);
        const normalizedX = (clampedX - padding) / graphWidth;
        const normalizedY = (clampedY - padding) / graphHeight;
        const valueX = clampValue(Math.round(normalizedX * 255), 0, 255);
        const valueY = clampValue(Math.round((1 - normalizedY) * 255), 0, 255);
        return { canvasX: clampedX, canvasY: clampedY, valueX, valueY };
    }

    function findCurvesPointIndexAt(canvasX, canvasY) {
        const points = getCurvesPoints();
        const tolerance = CURVES_POINT_RADIUS + 4;
        for (let i = 0; i < points.length; i++) {
            const coords = curveValueToCanvas(points[i]);
            const distance = Math.hypot(coords.x - canvasX, coords.y - canvasY);
            if (distance <= tolerance) {
                return i;
            }
        }
        return null;
    }

    function curveValueToCanvas(point) {
        const width = (curvesCanvasEl?.width) || CURVES_CANVAS_WIDTH;
        const height = (curvesCanvasEl?.height) || CURVES_CANVAS_HEIGHT;
        const padding = CURVES_GRAPH_PADDING;
        const graphWidth = Math.max(10, width - padding * 2);
        const graphHeight = Math.max(10, height - padding * 2);
        const normalizedX = clamp01(point.x / 255);
        const normalizedY = clamp01(point.y / 255);
        return {
            x: padding + normalizedX * graphWidth,
            y: padding + (1 - normalizedY) * graphHeight
        };
    }

    function activateLightColorPanel() {
        setFilterPropertiesMode('light-color');
        updateLightColorUI();
        withCanvasReady(() => {
            captureLightColorBaseSnapshot(true);
            renderLightColorAdjustments();
        });
    }

    function captureLightColorBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && lightColorAdjustmentBaseData && lightColorAdjustmentBaseData.width === canvas.width && lightColorAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            lightColorAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            lightColorAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture light & color base snapshot', error);
            return false;
        }
    }

    function renderLightColorAdjustments() {
        if (!isFilterPropertiesMode('light-color')) return;
        if (!canvas || !ctx) return;
        if (!lightColorAdjustmentBaseData || lightColorAdjustmentBaseData.width !== canvas.width || lightColorAdjustmentBaseData.height !== canvas.height) {
            const captured = captureLightColorBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }

        const hasAdjustment = Boolean(
            lightColorExposureValue !== 0 ||
            lightColorContrastValue !== 0 ||
            lightColorHighlightsValue !== 0 ||
            lightColorShadowsValue !== 0 ||
            lightColorWhitesValue !== 0 ||
            lightColorBlacksValue !== 0
        );
        if (hasAdjustment && !lightColorAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            lightColorAdjustmentHistoryCaptured = true;
        }

        if (!hasAdjustment) {
            ctx.putImageData(lightColorAdjustmentBaseData, 0, 0);
            lightColorAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = lightColorAdjustmentBaseData.data;
        const width = lightColorAdjustmentBaseData.width || canvas.width;
        const height = lightColorAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(width, height);
        const dest = output.data;
        const exposureAmt = clampValue(lightColorExposureValue, LIGHT_COLOR_EXPOSURE_MIN, LIGHT_COLOR_EXPOSURE_MAX) / 10;
        const contrastAmt = lightColorContrastValue / 120;
        const highlightsAmt = lightColorHighlightsValue / 200;
        const shadowsAmt = lightColorShadowsValue / 200;
        const whitesAmt = lightColorWhitesValue / 200;
        const blacksAmt = lightColorBlacksValue / 200;

        for (let i = 0; i < base.length; i += 4) {
            const r = base[i];
            const g = base[i + 1];
            const b = base[i + 2];
            const a = base[i + 3];
            const hsl = rgbToHsl(r, g, b);
            const baseLightness = hsl.l;
            let adjustedLightness = clamp01(baseLightness + exposureAmt);
            const contrastFactor = Math.max(0.1, 1 + contrastAmt);
            adjustedLightness = clamp01((adjustedLightness - 0.5) * contrastFactor + 0.5);
            const highlightWeight = Math.pow(clamp01((baseLightness - 0.55) / 0.45), 2);
            const shadowWeight = Math.pow(clamp01((0.45 - baseLightness) / 0.45), 2);
            const whitesWeight = Math.pow(clamp01((baseLightness - 0.75) / 0.25), 2);
            const blacksWeight = Math.pow(clamp01((0.25 - baseLightness) / 0.25), 2);
            adjustedLightness += highlightWeight * highlightsAmt;
            adjustedLightness += shadowWeight * shadowsAmt;
            adjustedLightness += whitesWeight * whitesAmt;
            adjustedLightness += blacksWeight * blacksAmt;
            adjustedLightness = clamp01(adjustedLightness);
            const rgb = hslToRgb(hsl.h, hsl.s, adjustedLightness);
            dest[i] = rgb.r;
            dest[i + 1] = rgb.g;
            dest[i + 2] = rgb.b;
            dest[i + 3] = a;
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateLightColorUI() {
        if (lightColorExposureSliderEl) lightColorExposureSliderEl.value = String(lightColorExposureValue);
        if (lightColorExposureInputEl) lightColorExposureInputEl.value = String(lightColorExposureValue);
        if (lightColorContrastSliderEl) lightColorContrastSliderEl.value = String(lightColorContrastValue);
        if (lightColorContrastInputEl) lightColorContrastInputEl.value = String(lightColorContrastValue);
        if (lightColorHighlightsSliderEl) lightColorHighlightsSliderEl.value = String(lightColorHighlightsValue);
        if (lightColorHighlightsInputEl) lightColorHighlightsInputEl.value = String(lightColorHighlightsValue);
        if (lightColorShadowsSliderEl) lightColorShadowsSliderEl.value = String(lightColorShadowsValue);
        if (lightColorShadowsInputEl) lightColorShadowsInputEl.value = String(lightColorShadowsValue);
        if (lightColorWhitesSliderEl) lightColorWhitesSliderEl.value = String(lightColorWhitesValue);
        if (lightColorWhitesInputEl) lightColorWhitesInputEl.value = String(lightColorWhitesValue);
        if (lightColorBlacksSliderEl) lightColorBlacksSliderEl.value = String(lightColorBlacksValue);
        if (lightColorBlacksInputEl) lightColorBlacksInputEl.value = String(lightColorBlacksValue);
    }

    function setLightColorExposureValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        lightColorExposureValue = clampValue(safeValue, LIGHT_COLOR_EXPOSURE_MIN, LIGHT_COLOR_EXPOSURE_MAX);
        updateLightColorUI();
        if (!options.silent && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        }
    }

    function setLightColorContrastValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        lightColorContrastValue = clampValue(safeValue, LIGHT_COLOR_INCIDENT_MIN, LIGHT_COLOR_INCIDENT_MAX);
        updateLightColorUI();
        if (!options.silent && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        }
    }

    function setLightColorHighlightsValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        lightColorHighlightsValue = clampValue(safeValue, LIGHT_COLOR_INCIDENT_MIN, LIGHT_COLOR_INCIDENT_MAX);
        updateLightColorUI();
        if (!options.silent && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        }
    }

    function setLightColorShadowsValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        lightColorShadowsValue = clampValue(safeValue, LIGHT_COLOR_INCIDENT_MIN, LIGHT_COLOR_INCIDENT_MAX);
        updateLightColorUI();
        if (!options.silent && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        }
    }

    function setLightColorWhitesValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        lightColorWhitesValue = clampValue(safeValue, LIGHT_COLOR_INCIDENT_MIN, LIGHT_COLOR_INCIDENT_MAX);
        updateLightColorUI();
        if (!options.silent && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        }
    }

    function setLightColorBlacksValue(value, options = {}) {
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : 0;
        lightColorBlacksValue = clampValue(safeValue, LIGHT_COLOR_INCIDENT_MIN, LIGHT_COLOR_INCIDENT_MAX);
        updateLightColorUI();
        if (!options.silent && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        }
    }

    function handleLightColorReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        setLightColorExposureValue(0, { silent: true });
        setLightColorContrastValue(0, { silent: true });
        setLightColorHighlightsValue(0, { silent: true });
        setLightColorShadowsValue(0, { silent: true });
        setLightColorWhitesValue(0, { silent: true });
        setLightColorBlacksValue(0, { silent: true });
        if (!skipRender && isFilterPropertiesMode('light-color')) {
            renderLightColorAdjustments();
        } else {
            updateLightColorUI();
        }
    }

    function resetLightColorState() {
        lightColorAdjustmentBaseData = null;
        lightColorAdjustmentHistoryCaptured = false;
        handleLightColorReset({ skipRender: true });
    }

    function activateBlackWhitePanel() {
        setFilterPropertiesMode('bw');
        updateBlackWhiteUI();
        withCanvasReady(() => {
            captureBlackWhiteBaseSnapshot(true);
            renderBlackWhiteAdjustments();
        });
    }

    function captureBlackWhiteBaseSnapshot(force = false) {
        if (!canvas || !ctx) return false;
        if (!force && blackWhiteAdjustmentBaseData && blackWhiteAdjustmentBaseData.width === canvas.width && blackWhiteAdjustmentBaseData.height === canvas.height) {
            return true;
        }
        try {
            blackWhiteAdjustmentBaseData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            blackWhiteAdjustmentHistoryCaptured = false;
            return true;
        } catch (error) {
            console.error('❌ Unable to capture black & white base snapshot', error);
            return false;
        }
    }

    function renderBlackWhiteAdjustments() {
        if (!isFilterPropertiesMode('bw')) return;
        if (!canvas || !ctx) return;
        if (!blackWhiteAdjustmentBaseData || blackWhiteAdjustmentBaseData.width !== canvas.width || blackWhiteAdjustmentBaseData.height !== canvas.height) {
            const captured = captureBlackWhiteBaseSnapshot(true);
            if (!captured) {
                return;
            }
        }

        const hasAdjustment = blackWhiteTintEnabled || BLACK_WHITE_CHANNELS.some((channel) => blackWhiteControlValues[channel] !== BLACK_WHITE_DEFAULT);
        if (hasAdjustment && !blackWhiteAdjustmentHistoryCaptured) {
            pushCanvasHistoryState();
            blackWhiteAdjustmentHistoryCaptured = true;
        }

        if (!hasAdjustment) {
            ctx.putImageData(blackWhiteAdjustmentBaseData, 0, 0);
            blackWhiteAdjustmentHistoryCaptured = false;
            hasCanvasChanges = true;
            updateEditPreview();
            return;
        }

        const base = blackWhiteAdjustmentBaseData.data;
        const baseWidth = blackWhiteAdjustmentBaseData.width || canvas.width;
        const baseHeight = blackWhiteAdjustmentBaseData.height || canvas.height;
        const output = ctx.createImageData(baseWidth, baseHeight);
        const dest = output.data;
        const tintRgb = blackWhiteTintEnabled ? (hexToRgb(blackWhiteTintColorValue) || null) : null;

        for (let i = 0; i < base.length; i += 4) {
            const r = base[i];
            const g = base[i + 1];
            const b = base[i + 2];
            const a = base[i + 3];
            const hsl = rgbToHsl(r, g, b);
            const hueDegrees = Number.isFinite(hsl.h) ? hsl.h * 360 : 0;
            const weight = getBlackWhiteWeightForHue(hueDegrees, hsl.s);
            let gray = (r * 0.299 + g * 0.587 + b * 0.114) * weight;
            gray = clampChannel(gray);
            let newR;
            let newG;
            let newB;
            if (tintRgb) {
                const normalized = gray / 255;
                newR = clampChannel(tintRgb.r * normalized);
                newG = clampChannel(tintRgb.g * normalized);
                newB = clampChannel(tintRgb.b * normalized);
            } else {
                newR = gray;
                newG = gray;
                newB = gray;
            }
            dest[i] = newR;
            dest[i + 1] = newG;
            dest[i + 2] = newB;
            dest[i + 3] = a;
        }

        ctx.putImageData(output, 0, 0);
        hasCanvasChanges = true;
        updateEditPreview();
    }

    function updateBlackWhiteUI() {
        BLACK_WHITE_CHANNELS.forEach((channel) => {
            const slider = blackWhiteSliderMap[channel];
            const input = blackWhiteInputMap[channel];
            const value = blackWhiteControlValues[channel];
            if (slider) slider.value = String(value);
            if (input) input.value = String(value);
        });
        if (blackWhiteTintToggleEl) {
            blackWhiteTintToggleEl.checked = Boolean(blackWhiteTintEnabled);
        }
        if (blackWhiteTintColorEl) {
            blackWhiteTintColorEl.value = blackWhiteTintColorValue;
            blackWhiteTintColorEl.disabled = !blackWhiteTintEnabled;
        }
    }

    function setBlackWhiteChannelValue(channel, value, options = {}) {
        if (!BLACK_WHITE_CHANNELS.includes(channel)) return;
        const numericValue = Number(value);
        const safeValue = Number.isFinite(numericValue) ? numericValue : BLACK_WHITE_DEFAULT;
        blackWhiteControlValues[channel] = clampValue(safeValue, BLACK_WHITE_MIN, BLACK_WHITE_MAX);
        updateBlackWhiteUI();
        if (!options.silent && isFilterPropertiesMode('bw')) {
            renderBlackWhiteAdjustments();
        }
    }

    function setBlackWhiteTintEnabled(enabled, options = {}) {
        blackWhiteTintEnabled = Boolean(enabled);
        updateBlackWhiteUI();
        if (!options.silent && isFilterPropertiesMode('bw')) {
            renderBlackWhiteAdjustments();
        }
    }

    function setBlackWhiteTintColor(value, options = {}) {
        const sanitized = typeof value === 'string' && value.trim().length ? value.trim() : BLACK_WHITE_DEFAULT_TINT;
        blackWhiteTintColorValue = sanitized;
        updateBlackWhiteUI();
        if (!options.silent && isFilterPropertiesMode('bw')) {
            if (blackWhiteTintEnabled) {
                renderBlackWhiteAdjustments();
            }
        }
    }

    function handleBlackWhiteReset(options = {}) {
        const skipRender = Boolean(options.skipRender);
        BLACK_WHITE_CHANNELS.forEach((channel) => {
            setBlackWhiteChannelValue(channel, BLACK_WHITE_DEFAULT, { silent: true });
        });
        setBlackWhiteTintEnabled(false, { silent: true });
        setBlackWhiteTintColor(BLACK_WHITE_DEFAULT_TINT, { silent: true });
        if (!skipRender && isFilterPropertiesMode('bw')) {
            renderBlackWhiteAdjustments();
        } else {
            updateBlackWhiteUI();
        }
    }

    function resetBlackWhiteState() {
        blackWhiteAdjustmentBaseData = null;
        blackWhiteAdjustmentHistoryCaptured = false;
        handleBlackWhiteReset({ skipRender: true });
    }

    function clampValue(value, min, max) {
        if (!Number.isFinite(value)) return 0;
        return Math.min(max, Math.max(min, value));
    }

    function clamp01(value) {
        if (!Number.isFinite(value)) return 0;
        return Math.min(1, Math.max(0, value));
    }

    function clampChannel(value) {
        if (!Number.isFinite(value)) return 0;
        return Math.min(255, Math.max(0, Math.round(value)));
    }

    function createDefaultCurvesPoints() {
        return CURVES_DEFAULT_POINTS.map((point, index, arr) => ({
            x: point.x,
            y: point.y,
            locked: index === 0 || index === arr.length - 1
        }));
    }

    function createCurvesStateSnapshot() {
        return CURVES_CHANNELS.reduce((acc, channel) => {
            acc[channel] = createDefaultCurvesPoints();
            return acc;
        }, {});
    }

    function createDefaultLevelsState() {
        return {
            inBlack: LEVELS_INPUT_MIN,
            inMid: 1,
            inWhite: LEVELS_INPUT_MAX,
            outBlack: LEVELS_OUTPUT_MIN,
            outWhite: LEVELS_OUTPUT_MAX
        };
    }

    function getLuminance(r, g, b) {
        return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    }

    function getColorBalanceToneWeight(luminance, tone) {
        const safeLum = clamp01(luminance);
        if (tone === 'shadows') {
            return clamp01((0.5 - safeLum) * 2);
        }
        if (tone === 'highlights') {
            return clamp01((safeLum - 0.5) * 2);
        }
        const mid = 1 - Math.abs(safeLum - 0.5) * 2;
        return clamp01(mid);
    }

    function getBlackWhiteWeightForHue(hueDegrees, saturation) {
        if (!Number.isFinite(hueDegrees)) {
            return 1;
        }
        if (!Number.isFinite(saturation) || saturation < 0.08) {
            const avg = BLACK_WHITE_CHANNELS.reduce((sum, channel) => sum + (blackWhiteControlValues[channel] || BLACK_WHITE_DEFAULT), 0) / BLACK_WHITE_CHANNELS.length;
            return avg / BLACK_WHITE_DEFAULT;
        }
        const normalizedHue = ((hueDegrees % 360) + 360) % 360;
        let channel = 'reds';
        if (normalizedHue >= 30 && normalizedHue < 90) {
            channel = 'yellows';
        } else if (normalizedHue >= 90 && normalizedHue < 150) {
            channel = 'greens';
        } else if (normalizedHue >= 150 && normalizedHue < 210) {
            channel = 'cyans';
        } else if (normalizedHue >= 210 && normalizedHue < 270) {
            channel = 'blues';
        } else if (normalizedHue >= 270 && normalizedHue < 330) {
            channel = 'magentas';
        }
        const value = blackWhiteControlValues[channel] ?? BLACK_WHITE_DEFAULT;
        return value / BLACK_WHITE_DEFAULT;
    }

    function rgbToHsl(r, g, b) {
        const rn = r / 255;
        const gn = g / 255;
        const bn = b / 255;
        const max = Math.max(rn, gn, bn);
        const min = Math.min(rn, gn, bn);
        let h = 0;
        let s = 0;
        const l = (max + min) / 2;

        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case rn:
                    h = (gn - bn) / d + (gn < bn ? 6 : 0);
                    break;
                case gn:
                    h = (bn - rn) / d + 2;
                    break;
                case bn:
                    h = (rn - gn) / d + 4;
                    break;
            }
            h /= 6;
        }

        return { h, s, l };
    }

    function hslToRgb(h, s, l) {
        if (s === 0) {
            const value = Math.round(l * 255);
            return { r: value, g: value, b: value };
        }
        const hue2rgb = (p, q, t) => {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1 / 6) return p + (q - p) * 6 * t;
            if (t < 1 / 2) return q;
            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        };
        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        const r = hue2rgb(p, q, h + 1 / 3);
        const g = hue2rgb(p, q, h);
        const b = hue2rgb(p, q, h - 1 / 3);
        return {
            r: Math.round(r * 255),
            g: Math.round(g * 255),
            b: Math.round(b * 255)
        };
    }

    function applyGrayscaleFilter() {
        withCanvasReady(() => {
            try {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const gray = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
                    data[i] = gray;
                    data[i + 1] = gray;
                    data[i + 2] = gray;
                }
                ctx.putImageData(imageData, 0, 0);
                pushCanvasHistoryState();
                hasCanvasChanges = true;
            } catch (error) {
                console.error('❌ Unable to apply grayscale filter', error);
            }
        });
    }

    function applyTintFilter(hex, strengthPercent) {
        withCanvasReady(() => {
            const rgb = hexToRgb(hex);
            if (!rgb) return;
            const strength = Math.min(Math.max(Number(strengthPercent) || 0, 0), 100) / 100;
            try {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    data[i] = data[i] * (1 - strength) + rgb.r * strength;
                    data[i + 1] = data[i + 1] * (1 - strength) + rgb.g * strength;
                    data[i + 2] = data[i + 2] * (1 - strength) + rgb.b * strength;
                }
                ctx.putImageData(imageData, 0, 0);
                pushCanvasHistoryState();
                hasCanvasChanges = true;
            } catch (error) {
                console.error('❌ Unable to apply tint filter', error);
            }
        });
    }

    function hexToRgb(hex) {
        if (!hex) return null;
        let value = hex.replace('#', '').trim();
        if (value.length === 3) {
            value = value.split('').map((char) => char + char).join('');
        }
        if (value.length !== 6) return null;
        const num = parseInt(value, 16);
        return {
            r: (num >> 16) & 255,
            g: (num >> 8) & 255,
            b: num & 255,
        };
    }

    function resetCanvasToOriginal(onComplete) {
        const activeImg = currentEditingImage ? currentEditingImage.querySelector('img') : null;
        const source = originalImageBackup || (activeImg ? activeImg.src : null);
        if (!source) return;
        withCanvasReady(() => {
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                setCanvasDisplaySize(img.width, img.height);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0);
                pushCanvasHistoryState();
                hasCanvasChanges = true;
                isCanvasReadyForOps = true;
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            };
            img.src = source;
        });
    }

    function updateEditPreview() {
        if (!currentEditingImage) return;

        const img = currentEditingImage.querySelector('img');
        if (!img) return;

        const widthSlider = document.getElementById('cdpImageWidthSlider');
        const widthValue = document.getElementById('cdpImageWidthValue');

        const currentWidth = parseInt(img.style.width) || 200;
        widthSlider.value = currentWidth;
        widthValue.textContent = currentWidth;
    }
    function applyImageEdits() {
        if (!currentEditingImage) return;

        // Apply canvas changes if any
        if (canvas && hasCanvasChanges) {
            const img = currentEditingImage.querySelector('img');
            img.src = canvas.toDataURL();
            console.log('Canvas changes applied to image');
            
            // بروزرسانی backup بعد از Apply
            currentEditingImage.dataset.originalBackup = img.src;
            console.log('Backup updated after apply:', img.src.substring(0, 50) + '...');
        }

        // Apply width
        const widthSlider = document.getElementById('cdpImageWidthSlider');
        const img = currentEditingImage.querySelector('img');
        if (img) {
            img.style.width = widthSlider.value + 'px';
            if (currentLayer) {
                currentLayer.width = parseInt(widthSlider.value);
            }
        }

        // بستن پنل
        if (editPanel) {
            editPanel.setAttribute('data-visible', 'false');
            removeCanvasHandlers();
            resetCanvasHistory();
            currentEditingImage = null;
            currentLayer = null;
            isErasing = false;
            isCropping = false;
            canvas = null;
            ctx = null;
            originalImageBackup = null;
            isCanvasReadyForOps = false;
            closeCropWorkspace(false);
        }
    }

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

    document.addEventListener('keydown', (e) => {
        if (!editPanel || editPanel.getAttribute('data-visible') !== 'true') return;
        if (!(e.ctrlKey || e.metaKey)) return;
        if (e.key.toLowerCase() !== 'z') return;

        if (isTextEntryElement(document.activeElement)) return;
        if (isCropping) return;

        if (undoCanvasEdit()) {
            e.preventDefault();
        }
    });

    function cancelEdit() {
        // برگرداندن عکس به حالت اصلی
        if (originalImageBackup && currentEditingImage) {
            const img = currentEditingImage.querySelector('img');
            if (img) {
                img.src = originalImageBackup;
            }
        }
        closeEditPanel();
    }

    function closeEditPanel() {
        if (editPanel) {
            editPanel.setAttribute('data-visible', 'false');
            removeCanvasHandlers();
            resetCanvasHistory();
            currentEditingImage = null;
            currentLayer = null;
            isErasing = false;
            isCropping = false;
            canvas = null;
            ctx = null;
            originalImageBackup = null;
            isCanvasReadyForOps = false;
            closeCropWorkspace(false);
            const filterWorkspace = document.getElementById('cdpFilterWorkspace');
            const filterToggleBtn = document.getElementById('cdpFilterToggle');
            const canvasEl = document.getElementById('cdpImageEditCanvas');
            if (filterWorkspace) {
                filterWorkspace.setAttribute('data-visible', 'false');
            }
            if (filterToggleBtn) {
                filterToggleBtn.classList.remove('active');
            }
            if (canvasEl) {
                canvasEl.classList.remove('cdp-filter-preview-active');
                canvasEl.style.transform = '';
            }
            setFilterPropertiesMode(null);
        }
    }

    // =========================
    // Styles
    // =========================

    function addUploadModalStyles() {
        if (document.getElementById('cdp-upload-modal-styles')) return;

        const style = document.createElement('style');
        style.id = 'cdp-upload-modal-styles';
        style.textContent = `
            .cdp-upload-modal {
                position: fixed;
                top: 0;
                left: 80px;
                width: 420px;
                height: 100vh;
                z-index: 10000;
                display: none;
                pointer-events: none;
            }
            .cdp-upload-content {
                position: relative;
                background: #ffffff;
                border-radius: 0;
                width: 100%;
                height: 100%;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
                pointer-events: auto;
            }
            .cdp-upload-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 20px 24px;
                border-bottom: 1px solid #e5e7eb;
            }
            .cdp-upload-header h3 {
                margin: 0;
                font-size: calc(var(--cdp-font-scale, 1) * 18px);
                font-weight: 600;
                color: #111827;
            }
            .cdp-upload-header-actions {
                margin-left: auto;
                display: inline-flex;
                align-items: center;
                gap: 10px;
            }
            .cdp-upload-close,
            .cdp-upload-help {
                padding: 0;
            }
            .cdp-upload-info {
                display: flex;
                gap: 12px;
                padding: 12px;
                margin-bottom: 20px;
            }
            .cdp-upload-body {
                padding: 24px;
                flex: 1;
                overflow-y: auto;
            }
            .cdp-upload-info i {
                color: #d9a300;
                font-size: calc(var(--cdp-font-scale, 1) * 20px);
                flex-shrink: 0;
            }
            .cdp-upload-info p {
                margin: 0;
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
                color: #1e40af;
                line-height: 1.5;
            }
            .cdp-upload-steps {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-bottom: 20px;
            }
            .cdp-upload-step {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .cdp-step-number {
                width: 28px;
                height: 28px;
                background: #d9a300;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                flex-shrink: 0;
            }
            .cdp-step-text {
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                color: #374151;
            }
            .cdp-upload-preview {
                border: 2px dashed #d1d5db;
                border-radius: 8px;
                padding: 20px;
                min-height: 150px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: #f9fafb;
            }
            .cdp-upload-preview p {
                margin: 0;
                color: #9ca3af;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
            }
            .cdp-upload-footer {
                display: flex;
                gap: 12px;
                padding: 20px 24px;
                border-top: 1px solid #e5e7eb;
                justify-content: flex-end;
            }
            .cdp-btn {
                padding: 10px 20px;
                border-radius: 8px;
                border: none;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                font-weight: 500;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
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
                color: white;
            }
            .cdp-btn-primary:hover {
                background: #b38600;
            }
            .cdp-btn-primary:disabled {
                background: #d1d5db;
                cursor: not-allowed;
            }
            .cdp-upload-tutorial-toast {
                position: fixed;
                bottom: 32px;
                right: 32px;
                background: rgba(17, 24, 39, 0.95);
                color: #f3f4f6;
                padding: 14px 20px;
                border-radius: 999px;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                font-weight: 600;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
                z-index: 200000;
                opacity: 0;
                transform: translateY(10px);
                transition: opacity 0.2s ease, transform 0.2s ease;
                pointer-events: none;
            }
            .cdp-upload-tutorial-toast[data-visible="true"] {
                opacity: 1;
                transform: translateY(0);
            }
            .cdp-uploaded-image[data-locked="true"] {
                cursor: not-allowed !important;
            }
            .cdp-uploaded-image.cdp-layer-locked-hint {
                animation: cdpLayerLockedPulse 0.35s ease;
            }
            @keyframes cdpLayerLockedPulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.7);
                }
                100% {
                    box-shadow: 0 0 0 18px rgba(248, 113, 113, 0);
                }
            }
            body.dark-mode .cdp-upload-modal {
                background: transparent;
            }
            body.dark-mode .cdp-upload-content {
                background: #1f2937;
                color: #f3f4f6;
                box-shadow: 2px 0 20px rgba(0, 0, 0, 0.65);
            }
            body.dark-mode .cdp-upload-header {
                border-bottom: 1px solid #374151;
                background: #1f2937;
            }
            body.dark-mode .cdp-upload-header h3 {
                color: #f9fafb;
            }
            body.dark-mode .cdp-upload-body {
                color: #e5e7eb;
            }
            body.dark-mode .cdp-upload-info {
                background: #111827;
                border-radius: 8px;
            }
            body.dark-mode .cdp-upload-info p {
                color: #cbd5f5;
            }
            body.dark-mode .cdp-step-text {
                color: #e5e7eb;
            }
            body.dark-mode .cdp-upload-preview {
                background: #0f172a;
                border-color: #334155;
            }
            body.dark-mode .cdp-upload-preview p {
                color: #64748b;
            }
            body.dark-mode .cdp-upload-footer {
                border-top: 1px solid #374151;
                background: #1f2937;
            }
            body.dark-mode .cdp-btn-secondary {
                background: #374151;
                color: #f3f4f6;
            }
            body.dark-mode .cdp-btn-secondary:hover {
                background: #4b5563;
            }
            body.dark-mode .cdp-btn-primary {
                background: #d9a300;
                color: #111827;
            }
            body.dark-mode .cdp-btn-primary:hover {
                background: #b38600;
            }
            body.dark-mode .cdp-upload-tutorial-toast {
                background: rgba(15, 23, 42, 0.98);
                color: #fbbf24;
            }
            @media (max-width: 768px) {
                .cdp-upload-modal {
                    left: 0;
                    width: 100vw;
                    height: 100svh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 12px;
                    box-sizing: border-box;
                    background: rgba(15, 23, 42, 0.24);
                }
                .cdp-upload-content {
                    width: min(92vw, 472px);
                    max-width: calc(100vw - 24px);
                    height: auto;
                    max-height: min(88svh, 760px);
                    border-radius: 18px;
                    box-shadow: 0 20px 44px rgba(15, 23, 42, 0.22);
                    overflow: hidden;
                }
                .cdp-upload-header {
                    padding: 13px 14px 11px;
                    align-items: center;
                }
                .cdp-upload-header h3 {
                    font-size: calc(var(--cdp-font-scale, 1) * 15px);
                }
                .cdp-upload-body {
                    padding: 14px 16px 16px;
                    max-height: none;
                }
                .cdp-upload-preview {
                    min-height: 148px;
                    padding: 16px;
                }
                .cdp-upload-footer {
                    position: static;
                    flex-direction: row;
                    flex-wrap: nowrap;
                    justify-content: stretch;
                    align-items: center;
                    padding: 13px 16px 16px;
                    background: #ffffff;
                }
                .cdp-btn {
                    width: auto;
                    min-width: 0;
                    min-height: 44px;
                    padding: 12px 16px;
                    font-size: calc(var(--cdp-font-scale, 1) * 13px);
                    justify-content: center;
                    flex: 1 1 0;
                }
                .cdp-upload-step {
                    gap: 8px;
                }
                .cdp-step-number {
                    width: 22px;
                    height: 22px;
                    font-size: calc(var(--cdp-font-scale, 1) * 11.5px);
                }
                .cdp-step-text,
                .cdp-upload-info p,
                .cdp-upload-preview p {
                    font-size: calc(var(--cdp-font-scale, 1) * 11.5px);
                }
            }
            @media (max-width: 480px) {
                .cdp-upload-modal {
                    padding: 10px;
                }
                .cdp-upload-content {
                    width: min(94vw, 430px);
                    max-width: calc(100vw - 20px);
                    max-height: min(86svh, 710px);
                }
                .cdp-upload-header {
                    gap: 8px;
                }
                .cdp-upload-info {
                    padding: 10px;
                    margin-bottom: 14px;
                }
                .cdp-upload-step {
                    align-items: flex-start;
                }
                .cdp-upload-footer {
                    gap: 6px;
                }
                .cdp-btn {
                    flex: 1 1 0;
                    font-size: calc(var(--cdp-font-scale, 1) * 12px);
                    padding: 11px 12px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function addEditPanelStyles() {
        if (document.getElementById('cdp-edit-panel-styles')) return;

        const style = document.createElement('style');
        style.id = 'cdp-edit-panel-styles';
        style.textContent = `
            .cdp-image-edit-panel {
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
            .cdp-image-edit-panel[data-visible="true"] {
                display: flex;
            }
            .cdp-edit-content {
                background: #ffffff;
                border-radius: 8px;
                width: 550px;
                max-width: 90%;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
                pointer-events: auto;
            }
            .cdp-edit-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 24px;
                border-bottom: 1px solid #e5e7eb;
            }
            .cdp-edit-header h3 {
                margin: 0;
                font-size: calc(var(--cdp-font-scale, 1) * 18px);
                font-weight: 600;
                color: #111827;
            }
            .cdp-edit-close {
                background: none;
                border: none;
                font-size: calc(var(--cdp-font-scale, 1) * 28px);
                color: #6b7280;
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cdp-edit-close:hover {
                color: #111827;
            }
            .cdp-edit-transform {
                display: flex;
                gap: 8px;
                padding: 16px 24px;
                border-bottom: 1px solid #e5e7eb;
                justify-content: center;
            }
            .cdp-transform-btn {
                width: 40px;
                height: 40px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #ffffff;
                color: #374151;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: calc(var(--cdp-font-scale, 1) * 20px);
                transition: all 0.15s ease;
            }
            .cdp-transform-btn:hover {
                background: #f3f4f6;
                border-color: #d9a300;
                color: #d9a300;
            }
            .cdp-edit-body {
                padding: 24px;
            }
            .cdp-edit-group {
                margin-bottom: 20px;
            }
            .cdp-edit-group label {
                display: block;
                color: #374151;
                margin-bottom: 8px;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
            }
            .cdp-edit-group input[type="range"] {
                width: 100%;
                height: 6px;
                border-radius: 3px;
                background: #e5e7eb;
                outline: none;
                -webkit-appearance: none;
            }
            .cdp-edit-group input[type="range"]::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #d9a300;
                cursor: pointer;
            }
            .cdp-edit-group input[type="range"]::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #d9a300;
                cursor: pointer;
                border: none;
            }
            .cdp-edit-tools {
                display: flex;
                gap: 12px;
                margin-bottom: 20px;
            }
            .cdp-tool-btn {
                flex: 1;
                padding: 12px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                background: #ffffff;
                color: #374151;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
                font-weight: 500;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.2s;
            }
            .cdp-tool-btn:hover {
                border-color: #d9a300;
                color: #d9a300;
            }
            .cdp-tool-btn.active {
                background: #d9a300;
                border-color: #d9a300;
                color: white;
            }
            .cdp-eraser-controls {
                margin-bottom: 20px;
                padding: 16px;
                background: #f9fafb;
                border-radius: 8px;
            }
            .cdp-eraser-controls label {
                display: block;
                color: #374151;
                margin-bottom: 8px;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
            }
            .cdp-eraser-controls input[type="range"] {
                width: 100%;
                height: 6px;
                border-radius: 3px;
                background: #e5e7eb;
                outline: none;
                -webkit-appearance: none;
            }
            .cdp-eraser-controls input[type="range"]::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #ef4444;
                cursor: pointer;
            }
            .cdp-eraser-controls input[type="range"]::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #ef4444;
                cursor: pointer;
                border: none;
            }
            .cdp-filter-shell {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 20px;
                background: #fffdf5;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .cdp-filter-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                color: #374151;
                font-weight: 600;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
            }
            .cdp-filter-actions {
                display: flex;
                gap: 12px;
                align-items: center;
            }
            .cdp-filter-toggle {
                flex: 1;
                justify-content: center;
            }
            .cdp-filter-undo {
                border: 1px solid #d1d5db;
                background: #ffffff;
                color: #1f2937;
                border-radius: 8px;
                padding: 10px 14px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s ease;
            }
            .cdp-filter-undo:hover {
                background: #f3f4f6;
                border-color: #9ca3af;
            }
            .cdp-filter-reset {
                border: none;
                background: none;
                color: #d97706;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
            }
            .cdp-filter-reset:hover {
                color: #b45309;
            }
            .cdp-filter-workspace {
                position: fixed;
                inset: 0;
                display: none;
                padding: 40px 60px;
                background: rgba(15, 23, 42, 0.78);
                z-index: 20000;
                align-items: center;
                justify-content: center;
            }
            .cdp-filter-workspace[data-visible="true"] {
                display: flex;
            }
            .cdp-filter-workspace-panel {
                width: min(1200px, 100%);
                max-height: 90vh;
                background: #ffffff;
                border-radius: 24px;
                padding: 32px;
                display: flex;
                flex-direction: column;
                gap: 24px;
                box-shadow: 0 35px 90px rgba(15, 23, 42, 0.45);
            }
            .cdp-filter-panel-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
            }
            .cdp-filter-panel-title p {
                margin: 0;
                text-transform: uppercase;
                font-weight: 600;
                letter-spacing: 0.08em;
                color: #9ca3af;
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
            }
            .cdp-filter-panel-title h3 {
                margin: 4px 0 0;
                font-size: calc(var(--cdp-font-scale, 1) * 22px);
                color: #111827;
            }
            .cdp-filter-panel-actions {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .cdp-filter-close {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                border: none;
                background: #f3f4f6;
                color: #111827;
                cursor: pointer;
                font-size: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
            }
            .cdp-filter-close:hover {
                background: #dc2626;
                color: #ffffff;
            }
            .cdp-filter-apply {
                border: none;
                background: #d9a300;
                color: #ffffff;
                border-radius: 999px;
                padding: 10px 20px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 10px 20px rgba(217, 163, 0, 0.3);
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .cdp-filter-apply:hover {
                transform: translateY(-1px);
                box-shadow: 0 14px 26px rgba(217, 163, 0, 0.35);
            }
            body.cdp-lock-scroll {
                overflow: hidden;
            }
            .cdp-filter-panel-body {
                display: flex;
                gap: 24px;
                flex: 1;
                min-height: 420px;
                max-height: calc(90vh - 140px);
                align-items: stretch;
                overflow: hidden;
            }
            .cdp-filter-list {
                width: 240px;
                display: flex;
                flex-direction: column;
                gap: 8px;
                overflow-y: auto;
                padding-right: 4px;
            }
            .cdp-filter-menu-btn {
                padding: 12px 14px;
                border-radius: 10px;
                border: 1px solid #e5e7eb;
                background: #ffffff;
                text-align: left;
                font-weight: 600;
                color: #1f2937;
                cursor: pointer;
                transition: all 0.2s ease;
                line-height: 1.3;
            }
            .cdp-filter-menu-btn small {
                display: block;
                font-weight: 500;
                color: #6b7280;
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
            }
            .cdp-filter-menu-btn:hover,
            .cdp-filter-menu-btn.active {
                border-color: #d9a300;
                background: #fef3c7;
                color: #92400e;
            }
            .cdp-filter-preview-area {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 16px;
                min-height: 0;
            }
            .cdp-filter-preview-toolbar {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 16px;
            }
            .cdp-filter-hint {
                margin: 0;
                color: #6b7280;
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
                flex: 1;
            }
            .cdp-filter-zoom-controls {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 999px;
                padding: 6px 14px;
                font-weight: 600;
                color: #374151;
            }
            .cdp-filter-zoom-btn {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                border: none;
                background: #f3f4f6;
                color: #1f2937;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background 0.15s ease;
            }
            .cdp-filter-zoom-btn:hover {
                background: #d9a300;
                color: #ffffff;
            }
            .cdp-filter-preview-stage {
                flex: 1;
                border: 1px dashed #d1d5db;
                border-radius: 16px;
                background: linear-gradient(145deg, #f9fafb, #f3f4f6);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                min-height: 0;
            }
            .cdp-filter-preview-frame {
                width: 100%;
                height: 100%;
                max-width: 640px;
                max-height: 640px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border-radius: 8px;
                background: #ffffff;
                box-shadow: inset 0 0 0 1px #e5e7eb;
            }
            .cdp-filter-preview-frame canvas {
                max-width: 100%;
                max-height: 100%;
                transition: transform 0.25s ease;
                transform-origin: center;
            }
            .cdp-filter-preview-frame canvas.cdp-filter-preview-active {
                pointer-events: none;
            }
            .cdp-filter-preview-layout {
                flex: 1;
                display: flex;
                gap: 28px;
                align-items: stretch;
                min-height: 420px;
            }
            .cdp-filter-preview-stage {
                min-width: 0;
                border-radius: 26px;
                padding: 32px;
            }
            .cdp-filter-properties-card {
                flex: 0 0 340px;
                max-width: 360px;
                background: #ffffff;
                border-radius: 28px;
                border: 1px solid #e8ecf5;
                box-shadow: 0 22px 65px rgba(15, 23, 42, 0.08);
                padding: 24px;
                padding-right: 32px;
                display: none;
                flex-direction: column;
                gap: 20px;
                max-height: calc(90vh - 220px);
                overflow-y: auto;
                overflow-x: hidden;
                scrollbar-width: thin;
                scrollbar-color: #d9a300 #f3f4f6;
            }
            .cdp-filter-properties-card::-webkit-scrollbar {
                width: 6px;
            }
            .cdp-filter-properties-card::-webkit-scrollbar-track {
                background: #f3f4f6;
                border-radius: 999px;
            }
            .cdp-filter-properties-card::-webkit-scrollbar-thumb {
                background: #d9a300;
                border-radius: 999px;
            }
            .cdp-filter-properties-card::-webkit-scrollbar-thumb:hover {
                background: #b38600;
            }
            .cdp-filter-properties-card[data-visible="true"] {
                display: flex;
            }
            .cdp-filter-properties-content {
                display: none;
            }
            .cdp-filter-properties-content[data-active="true"] {
                display: block;
            }
            .cdp-filter-properties-header p {
                margin: 0;
                text-transform: uppercase;
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
                letter-spacing: 0.08em;
                color: #9ca3af;
                font-weight: 600;
            }
            .cdp-filter-properties-header h4 {
                margin: 2px 0 0;
                font-size: calc(var(--cdp-font-scale, 1) * 18px);
                color: #111827;
            }
            .cdp-filter-properties-section {
                display: flex;
                flex-direction: column;
                gap: 16px;
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                padding: 16px;
                background: #f9fafb;
            }
            .cdp-filter-slider {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .cdp-filter-slider label {
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
                font-weight: 600;
                color: #374151;
            }
            .cdp-filter-slider-inputs {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .cdp-filter-slider-inputs input[type="range"] {
                flex: 1;
                accent-color: #d9a300;
            }
            .cdp-filter-slider-inputs input[type="number"] {
                width: 68px;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                padding: 6px 8px;
                font-weight: 600;
                text-align: center;
                background: #ffffff;
            }
            .cdp-blur-slider-inputs {
                gap: 16px;
            }
            .cdp-blur-value-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border: 1px solid #e5e7eb;
                border-radius: 999px;
                padding: 4px 12px 4px 10px;
                background: #ffffff;
                box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.05);
                font-weight: 600;
                color: #111827;
            }
            .cdp-blur-value-pill input {
                width: 54px;
                border: none;
                padding: 0;
                text-align: right;
                font-weight: 600;
                background: transparent;
                font-size: calc(var(--cdp-font-scale, 1) * 14px);
            }
            .cdp-blur-value-pill input:focus {
                outline: none;
            }
            .cdp-blur-value-unit {
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }
            .cdp-filter-select-row {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .cdp-filter-select-row select {
                border: 1px solid #d1d5db;
                border-radius: 10px;
                padding: 8px 10px;
                font-weight: 600;
                background: #ffffff;
                color: #111827;
            }
            .cdp-filter-toggle {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-weight: 600;
                color: #374151;
                cursor: pointer;
                user-select: none;
            }
            .cdp-filter-toggle input {
                width: 18px;
                height: 18px;
                border-radius: 4px;
            }
            .cdp-filter-tint-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .cdp-filter-tint-row input[type="color"] {
                width: 36px;
                height: 36px;
                border: none;
                border-radius: 50%;
                padding: 0;
                background: none;
                cursor: pointer;
                box-shadow: inset 0 0 0 2px #e5e7eb;
            }
            .cdp-filter-tint-row input[type="color"][disabled] {
                opacity: 0.4;
                cursor: not-allowed;
            }
            .cdp-filter-properties-reset {
                border: none;
                background: #f3f4f6;
                color: #374151;
                border-radius: 999px;
                padding: 10px 16px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: background 0.2s ease, color 0.2s ease;
            }
            .cdp-filter-properties-reset:hover {
                background: #e5e7eb;
                color: #111827;
            }
            .cdp-filter-properties-hint {
                color: #6b7280;
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
                margin: 0;
            }
            @media (max-width: 1180px) {
                .cdp-filter-preview-layout {
                    flex-direction: column;
                }
                .cdp-filter-properties-card {
                    flex: 1 1 auto;
                    width: 100%;
                }
            }
            @media (max-width: 1024px) {
                .cdp-filter-workspace {
                    padding: 20px;
                }
                .cdp-filter-workspace-panel {
                    width: min(100%, 960px);
                    height: calc(100vh - 40px);
                    max-height: none;
                    padding: 24px 22px;
                    gap: 18px;
                }
                .cdp-filter-panel-top {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 14px;
                }
                .cdp-filter-panel-actions {
                    width: 100%;
                    display: grid;
                    grid-template-columns: minmax(0, 1.4fr) repeat(3, minmax(0, 1fr));
                    gap: 10px;
                    align-items: stretch;
                }
                .cdp-filter-zoom-controls {
                    width: 100%;
                    min-height: 42px;
                }
                .cdp-filter-undo,
                .cdp-filter-apply,
                .cdp-filter-close {
                    width: 100%;
                    min-height: 42px;
                    justify-content: center;
                }
                .cdp-filter-close {
                    height: 42px;
                }
                .cdp-filter-panel-body {
                    flex-direction: column;
                    overflow-y: auto;
                    gap: 18px;
                }
                .cdp-filter-list {
                    width: 100%;
                    flex-direction: row;
                    flex-wrap: wrap;
                    max-height: none;
                    overflow-x: auto;
                    padding-bottom: 6px;
                    column-gap: 6px;
                    row-gap: 6px;
                }
                .cdp-filter-menu-btn {
                    flex: 1 1 calc(33.333% - 6px);
                    min-height: 40px;
                    padding: 10px 12px;
                    font-size: calc(var(--cdp-font-scale, 1) * 13px);
                }
            }
            @media (max-width: 720px) {
                .cdp-filter-workspace {
                    padding: 12px;
                }
                .cdp-filter-workspace-panel {
                    border-radius: 18px;
                    padding: 18px 16px;
                    gap: 14px;
                }
                .cdp-filter-panel-actions {
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                }
                .cdp-filter-zoom-controls {
                    grid-column: 1 / -1;
                }
                .cdp-filter-panel-body {
                    gap: 14px;
                }
                .cdp-filter-preview-stage {
                    padding: 14px;
                }
                .cdp-filter-preview-frame {
                    max-height: 300px;
                }
                .cdp-filter-properties-card {
                    padding: 16px;
                    max-height: none;
                }
                .cdp-filter-menu-btn {
                    flex: 1 1 calc(50% - 6px);
                    min-height: 38px;
                    font-size: calc(var(--cdp-font-scale, 1) * 12px);
                }
            }
            @media (max-width: 560px) {
                .cdp-filter-workspace {
                    padding: 0;
                }
                .cdp-filter-workspace-panel {
                    border-radius: 0;
                    height: 100dvh;
                    padding: 12px 10px calc(14px + env(safe-area-inset-bottom, 0px));
                    gap: 10px;
                }
                .cdp-filter-panel-top {
                    gap: 8px;
                }
                .cdp-filter-panel-actions {
                    grid-template-columns: 1fr 1fr;
                    gap: 6px;
                }
                .cdp-filter-zoom-controls {
                    width: 100%;
                    justify-content: space-between;
                    grid-column: 1 / -1;
                    min-height: 36px;
                    padding: 4px 10px;
                }
                .cdp-filter-undo,
                .cdp-filter-apply,
                .cdp-filter-close {
                    width: 100%;
                    justify-content: center;
                    min-height: 36px;
                    font-size: calc(var(--cdp-font-scale, 1) * 12px);
                    gap: 6px;
                }
                .cdp-filter-close {
                    height: 36px;
                    border-radius: 8px;
                }
                .cdp-filter-menu-btn {
                    flex: 1 1 100%;
                    min-height: 36px;
                    padding: 9px 10px;
                    font-size: calc(var(--cdp-font-scale, 1) * 11px);
                }
                .cdp-filter-preview-stage {
                    min-height: 196px;
                    padding: 10px;
                }
                .cdp-filter-properties-card {
                    padding: 14px;
                    border-radius: 15px;
                }
                .cdp-filter-properties-section {
                    padding: 12px;
                    gap: 12px;
                }
                .cdp-filter-slider-inputs {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 7px;
                }
                .cdp-filter-slider-inputs input[type="number"] {
                    width: 100%;
                }
                .cdp-blur-slider-inputs {
                    gap: 7px;
                }
                .cdp-blur-value-pill {
                    width: 100%;
                    justify-content: space-between;
                }
                .cdp-blur-value-pill input {
                    width: 100%;
                }
            }
            .cdp-crop-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.8);
                display: none;
                align-items: center;
                justify-content: center;
                padding: 40px 32px;
                z-index: 120000;
            }
            .cdp-crop-overlay[data-visible="true"] {
                display: flex;
            }
            .cdp-crop-shell {
                width: min(1180px, calc(100vw - 64px));
                min-width: 0;
                max-width: 100%;
                height: min(90vh, 820px);
                min-height: 640px;
                background: #ffffff;
                border-radius: 28px;
                padding: 32px;
                box-shadow: 0 40px 110px rgba(15, 23, 42, 0.4);
                display: flex;
                flex-direction: column;
                gap: 20px;
                overflow: hidden;
            }
            .cdp-crop-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
            }
            .cdp-crop-header-actions {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .cdp-crop-header p {
                margin: 0;
                text-transform: uppercase;
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
                letter-spacing: 0.08em;
                color: #9ca3af;
                font-weight: 600;
            }
            .cdp-crop-header h3 {
                margin: 4px 0 0;
                font-size: calc(var(--cdp-font-scale, 1) * 22px);
                color: #111827;
            }
            .cdp-crop-body {
                display: flex;
                flex-direction: column;
                gap: 16px;
                flex: 1;
                min-height: 0;
            }
            .cdp-crop-body-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap;
            }
            .cdp-crop-hint {
                margin: 0;
                color: #6b7280;
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
            }
            .cdp-crop-hint--error {
                color: #b91c1c;
            }
            .cdp-crop-reset {
                border: none;
                background: #f3f4f6;
                color: #374151;
                border-radius: 999px;
                padding: 8px 16px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: background 0.2s ease, color 0.2s ease;
            }
            .cdp-crop-reset:hover {
                background: #e5e7eb;
                color: #111827;
            }
            .cdp-crop-stage {
                flex: 1;
                min-height: 0;
                border: 1px dashed #e5e7eb;
                border-radius: 20px;
                background: linear-gradient(135deg, #f9fafb, #f3f4f6);
                padding: 24px;
                display: flex;
                gap: 24px;
                flex-wrap: wrap;
                align-items: stretch;
            }
            .cdp-crop-stage-original {
                flex: 0 0 720px;
                max-width: 720px;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cdp-crop-canvas-wrap {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .cdp-crop-canvas-inner {
                position: relative;
                transform-origin: center;
                transition: transform 0.25s ease;
                touch-action: none;
                cursor: crosshair;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cdp-crop-canvas-inner canvas {
                display: block;
                width: auto;
                max-width: 640px;
                height: auto;
                max-height: 100%;
                border-radius: 12px;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
                touch-action: none;
                user-select: none;
                cursor: crosshair;
                -webkit-user-drag: none;
            }
            .cdp-crop-selection {
                position: absolute;
                top: 0;
                left: 0;
                border: 2px dashed #fbbf24;
                box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.45);
                border-radius: 3px;
                pointer-events: none;
                display: none;
            }
            .cdp-crop-selection--pulse {
                animation: cdpCropSelectionPulse 0.45s ease;
            }
            .cdp-crop-canvas-inner--pulse {
                animation: cdpCropInnerPulse 0.45s ease;
            }
            @keyframes cdpCropSelectionPulse {
                0% { box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.45); }
                50% { box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.6); }
                100% { box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.45); }
            }
            @keyframes cdpCropInnerPulse {
                0% { filter: none; }
                50% { filter: brightness(0.96); }
                100% { filter: none; }
            }
            .cdp-crop-preview-card {
                flex: 0 0 320px;
                max-width: 320px;
                height: 100%;
                background: #ffffff;
                border-radius: 18px;
                box-shadow: inset 0 0 0 1px #e5e7eb;
                padding: 18px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .cdp-crop-preview-title {
                font-weight: 700;
                color: #111827;
                font-size: calc(var(--cdp-font-scale, 1) * 15px);
            }
            .cdp-crop-preview-frame {
                position: relative;
                border: 1px dashed #e5e7eb;
                border-radius: 14px;
                background: #f9fafb;
                flex: 1;
                min-height: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .cdp-crop-preview-frame canvas {
                width: 100%;
                height: auto;
                max-height: 100%;
                display: block;
                transition: opacity 0.2s ease;
                opacity: 1;
            }
            .cdp-crop-preview-frame[data-empty="true"] canvas {
                opacity: 0;
            }
            .cdp-crop-preview-placeholder {
                position: absolute;
                padding: 16px;
                text-align: center;
                color: #6b7280;
                font-size: calc(var(--cdp-font-scale, 1) * 13px);
                line-height: 1.4;
                pointer-events: none;
                transition: opacity 0.2s ease;
            }
            .cdp-crop-preview-frame[data-empty="false"] .cdp-crop-preview-placeholder {
                opacity: 0;
            }
            .cdp-crop-preview-hint {
                margin: 0;
                font-size: calc(var(--cdp-font-scale, 1) * 12px);
                color: #9ca3af;
            }
            .cdp-crop-footer {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
            }
            @media (max-width: 1024px) {
                .cdp-crop-overlay {
                    padding: 24px 20px;
                }
                .cdp-crop-shell {
                    width: min(100%, 960px);
                    height: calc(100vh - 40px);
                    min-height: 0;
                    padding: 24px 22px;
                    gap: 18px;
                }
                .cdp-crop-header {
                    gap: 16px;
                }
                .cdp-crop-header-actions {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 10px;
                    width: min(100%, 360px);
                }
                .cdp-crop-header-actions > * {
                    width: 100%;
                    min-height: 42px;
                }
                .cdp-crop-stage {
                    flex-direction: column;
                    flex-wrap: nowrap;
                    padding: 18px;
                    gap: 18px;
                }
                .cdp-crop-stage-original,
                .cdp-crop-preview-card {
                    flex: 1 1 auto;
                    max-width: 100%;
                    width: 100%;
                }
            }
            @media (max-width: 720px) {
                .cdp-crop-overlay {
                    padding: 12px;
                }
                .cdp-crop-shell {
                    border-radius: 18px;
                    padding: 18px 16px;
                    gap: 14px;
                    min-height: 0;
                }
                .cdp-crop-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }
                .cdp-crop-header-actions {
                    width: 100%;
                    grid-template-columns: 1fr 1fr;
                }
                .cdp-crop-body-top {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }
                .cdp-crop-stage {
                    padding: 14px;
                }
                .cdp-crop-preview-card {
                    padding: 14px;
                }
                .cdp-crop-footer {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                }
                .cdp-crop-footer > * {
                    width: 100%;
                    min-height: 40px;
                }
            }
            @media (max-width: 560px) {
                .cdp-crop-overlay {
                    padding: 0;
                }
                .cdp-crop-shell {
                    width: 100%;
                    height: 100dvh;
                    border-radius: 0;
                    padding: 10px 8px calc(12px + env(safe-area-inset-bottom, 0px));
                    min-height: 0;
                    gap: 8px;
                }
                .cdp-crop-header {
                    gap: 6px;
                }
                .cdp-crop-header h3 {
                    font-size: calc(var(--cdp-font-scale, 1) * 13px);
                }
                .cdp-crop-header-actions {
                    width: 100%;
                    gap: 6px;
                }
                .cdp-crop-header-actions > * {
                    min-height: 30px;
                    font-size: calc(var(--cdp-font-scale, 1) * 11px);
                }
                .cdp-crop-stage {
                    gap: 8px;
                    padding: 8px;
                }
                .cdp-crop-preview-card {
                    padding: 10px;
                }
                .cdp-crop-footer {
                    gap: 6px;
                }
                .cdp-crop-footer > * {
                    min-height: 30px;
                    font-size: calc(var(--cdp-font-scale, 1) * 11px);
                }
            }
            .cdp-edit-preview {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 20px;
                background: #f9fafb;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 300px;
            }
            .cdp-edit-preview canvas {
                max-width: 100%;
                max-height: 300px;
                border: 1px solid #d1d5db;
                background: white;
            }
            .cdp-edit-footer {
                display: flex;
                gap: 12px;
                padding: 20px 24px;
                border-top: 1px solid #e5e7eb;
                justify-content: flex-end;
            }
            @media (max-width: 768px) {
                .cdp-image-edit-panel {
                    justify-content: center;
                    padding-left: 0;
                    padding: 14px;
                    align-items: flex-start;
                    overflow-y: auto;
                    pointer-events: auto;
                    background: rgba(15, 23, 42, 0.36);
                }
                .cdp-edit-content {
                    width: min(100%, 442px);
                    max-width: 100%;
                    margin: 0 auto;
                    border-radius: 17px;
                    max-height: calc(100dvh - 28px);
                    overflow: hidden auto;
                }
                .cdp-edit-header,
                .cdp-edit-transform,
                .cdp-edit-body,
                .cdp-edit-footer {
                    padding-left: 15px;
                    padding-right: 15px;
                }
                .cdp-edit-transform {
                    flex-wrap: wrap;
                }
                .cdp-transform-btn {
                    width: 32px;
                    height: 32px;
                    font-size: calc(var(--cdp-font-scale, 1) * 15px);
                }
                .cdp-edit-tools {
                    gap: 8px;
                }
                .cdp-tool-btn {
                    min-height: 38px;
                    padding: 9px 8px;
                    font-size: calc(var(--cdp-font-scale, 1) * 11px);
                }
                .cdp-filter-shell {
                    padding: 12px;
                }
                .cdp-filter-header {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 8px;
                }
                .cdp-filter-actions {
                    width: 100%;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .cdp-filter-actions > * {
                    flex: 1 1 calc(50% - 5px);
                }
                .cdp-filter-reset {
                    justify-content: center;
                    min-height: 38px;
                    padding: 8px 10px;
                    border-radius: 8px;
                    background: #fff7df;
                }
                .cdp-edit-footer {
                    flex-wrap: wrap;
                }
                .cdp-edit-footer > * {
                    flex: 1 1 calc(50% - 6px);
                }
            }
            @media (max-width: 560px) {
                .cdp-image-edit-panel {
                    padding: 0;
                    align-items: stretch;
                }
                .cdp-edit-content {
                    width: 100%;
                    min-height: 100dvh;
                    max-height: 100dvh;
                    border-radius: 0;
                    box-shadow: none;
                }
                .cdp-edit-header {
                    padding: 14px 14px 12px;
                }
                .cdp-edit-transform {
                    padding: 10px 14px;
                    gap: 5px;
                }
                .cdp-edit-body {
                    padding: 14px;
                }
                .cdp-edit-group,
                .cdp-edit-tools,
                .cdp-eraser-controls,
                .cdp-filter-shell {
                    margin-bottom: 14px;
                }
                .cdp-edit-tools {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                }
                .cdp-tool-btn {
                    width: 100%;
                    min-height: 44px;
                    padding: 10px 12px;
                    font-size: calc(var(--cdp-font-scale, 1) * 12px);
                    font-weight: 700;
                    gap: 8px;
                }
                .cdp-filter-actions {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                }
                .cdp-filter-actions > * {
                    width: 100%;
                    flex: 1 1 100%;
                }
                .cdp-filter-toggle {
                    min-height: 44px;
                    padding: 10px 12px;
                    border-color: #d9a300;
                    background: linear-gradient(180deg, #fff8dc 0%, #ffefb3 100%);
                    color: #8a6500;
                    font-weight: 800;
                    box-shadow: 0 6px 14px rgba(217, 163, 0, 0.16);
                }
                .cdp-filter-toggle i {
                    font-size: calc(var(--cdp-font-scale, 1) * 14px);
                }
                .cdp-filter-reset {
                    min-height: 44px;
                }
                .cdp-eraser-controls {
                    padding: 12px;
                }
                .cdp-edit-footer {
                    padding: 12px 14px calc(14px + env(safe-area-inset-bottom, 0px));
                }
                .cdp-edit-footer > * {
                    flex: 1 1 100%;
                }
            }
            body.dark-mode .cdp-filter-shell {
                background: #1f2937;
                border-color: #374151;
            }
            body.dark-mode .cdp-filter-workspace {
                background: rgba(2, 6, 23, 0.85);
            }
            body.dark-mode .cdp-filter-workspace-panel {
                background: #0f172a;
                box-shadow: 0 35px 90px rgba(0, 0, 0, 0.65);
            }
            body.dark-mode .cdp-filter-panel-title p {
                color: #475569;
            }
            body.dark-mode .cdp-filter-panel-title h3 {
                color: #f9fafb;
            }
            body.dark-mode .cdp-filter-close {
                background: #1f2937;
                color: #f9fafb;
            }
            body.dark-mode .cdp-filter-close:hover {
                background: #dc2626;
                color: #ffffff;
            }
            body.dark-mode .cdp-filter-apply {
                background: #fbbf24;
                color: #111827;
                box-shadow: 0 10px 20px rgba(251, 191, 36, 0.25);
            }
            body.dark-mode .cdp-filter-apply:hover {
                box-shadow: 0 14px 26px rgba(251, 191, 36, 0.35);
            }
            body.dark-mode .cdp-filter-menu-btn {
                background: #111827;
                border-color: #1f2937;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-menu-btn:hover,
            body.dark-mode .cdp-filter-menu-btn.active {
                background: #f59e0b33;
                border-color: #fbbf24;
                color: #fbbf24;
            }
            body.dark-mode .cdp-filter-hint {
                color: #9ca3af;
            }
            body.dark-mode .cdp-filter-reset {
                color: #fbbf24;
            }
            body.dark-mode .cdp-filter-reset:hover {
                color: #f59e0b;
            }
            body.dark-mode .cdp-filter-undo {
                background: #111827;
                border-color: #1f2937;
                color: #f9fafb;
            }
            body.dark-mode .cdp-filter-undo:hover {
                background: #1f2937;
                border-color: #4b5563;
            }
            body.dark-mode .cdp-filter-zoom-controls {
                background: #111827;
                border-color: #374151;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-zoom-btn {
                background: #1f2937;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-zoom-btn:hover {
                background: #fbbf24;
                color: #111827;
            }
            body.dark-mode .cdp-filter-preview-stage {
                background: #0f172a;
                border-color: #1f2937;
            }
            body.dark-mode .cdp-filter-preview-frame {
                background: #111827;
                box-shadow: inset 0 0 0 1px #374151;
            }
            body.dark-mode .cdp-image-edit-panel {
                background: transparent;
            }
            body.dark-mode .cdp-edit-content {
                background: #111827;
                color: #e5e7eb;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.75);
            }
            body.dark-mode .cdp-edit-header {
                border-bottom: 1px solid #1f2937;
            }
            body.dark-mode .cdp-edit-header h3 {
                color: #f9fafb;
            }
            body.dark-mode .cdp-edit-close {
                color: #9ca3af;
            }
            body.dark-mode .cdp-edit-close:hover {
                color: #fff;
                background: #1f2937;
            }
            body.dark-mode .cdp-edit-transform {
                border-bottom: 1px solid #1f2937;
            }
            body.dark-mode .cdp-transform-btn {
                background: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-transform-btn:hover {
                background: #374151;
                border-color: #d9a300;
                color: #fff;
            }
            body.dark-mode .cdp-edit-body {
                background: #0f172a;
            }
            body.dark-mode .cdp-tool-btn {
                background: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-tool-btn:hover {
                border-color: #d9a300;
                color: #fff;
            }
            body.dark-mode .cdp-tool-btn.active {
                background: #d9a300;
                color: #111827;
            }
            body.dark-mode .cdp-eraser-controls {
                background: #111827;
                border: 1px solid #1f2937;
            }
            body.dark-mode .cdp-filter-controls {
                background: #111827;
                border-color: #1f2937;
            }
            body.dark-mode .cdp-filter-header {
                color: #f3f4f6;
            }
            body.dark-mode .cdp-filter-btn {
                background: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-btn:hover {
                border-color: #d9a300;
                color: #ffffff;
            }
            body.dark-mode .cdp-filter-field {
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-field input[type="color"] {
                border-color: #374151;
                box-shadow: inset 0 0 0 2px #1f2937;
                background: #0f172a;
            }
            body.dark-mode .cdp-filter-properties-card {
                background: #0b1120;
                border-color: #1f2937;
                box-shadow: 0 22px 65px rgba(0, 0, 0, 0.45);
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-properties-section {
                background: #111827;
                border-color: #1f2937;
            }
            body.dark-mode .cdp-filter-slider label {
                color: #e5e7eb;
            }
            body.dark-mode .cdp-filter-slider-inputs input[type="number"] {
                border-color: #374151;
                background: #0f172a;
                color: #f8fafc;
            }
            body.dark-mode .cdp-blur-value-pill {
                background: #111827;
                border-color: #1f2937;
                color: #f8fafc;
            }
            body.dark-mode .cdp-blur-value-pill input {
                color: #f8fafc;
            }
            body.dark-mode .cdp-blur-value-unit {
                color: #94a3b8;
            }
            body.dark-mode .cdp-filter-select-row select {
                background: #111827;
                border-color: #1f2937;
                color: #f8fafc;
            }
            body.dark-mode .cdp-filter-toggle {
                color: #f3f4f6;
            }
            body.dark-mode .cdp-filter-tint-row input[type="color"] {
                box-shadow: inset 0 0 0 2px #1f2937;
                background: #0f172a;
            }
            body.dark-mode .cdp-filter-properties-reset {
                background: #1f2937;
                color: #fbbf24;
            }
            body.dark-mode .cdp-filter-properties-reset:hover {
                background: #374151;
                color: #ffffff;
            }
            body.dark-mode .cdp-filter-properties-hint {
                color: #94a3b8;
            }
            body.dark-mode .cdp-crop-overlay {
                background: rgba(0, 0, 0, 0.85);
            }
            body.dark-mode .cdp-crop-shell {
                background: #0f172a;
                box-shadow: 0 40px 110px rgba(0, 0, 0, 0.65);
            }
            body.dark-mode .cdp-crop-header h3 {
                color: #f9fafb;
            }
            body.dark-mode .cdp-crop-hint {
                color: #94a3b8;
            }
            body.dark-mode .cdp-crop-hint--error {
                color: #f87171;
            }
            body.dark-mode .cdp-crop-stage {
                background: linear-gradient(135deg, #0f172a, #1e293b);
                border-color: #1f2937;
            }
            body.dark-mode .cdp-crop-selection {
                box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.75);
                border-color: #facc15;
            }
            body.dark-mode .cdp-crop-reset {
                background: #1f2937;
                color: #e5e7eb;
            }
            body.dark-mode .cdp-crop-reset:hover {
                background: #374151;
                color: #fff;
            }
            body.dark-mode .cdp-crop-preview-card {
                background: #111827;
                box-shadow: inset 0 0 0 1px #1f2937;
            }
            body.dark-mode .cdp-crop-preview-title {
                color: #f9fafb;
            }
            body.dark-mode .cdp-crop-preview-frame {
                background: #0f172a;
                border-color: #1f2937;
            }
            body.dark-mode .cdp-crop-preview-placeholder {
                color: #94a3b8;
            }
            body.dark-mode .cdp-crop-preview-hint {
                color: #64748b;
            }
            body.dark-mode .cdp-edit-preview {
                background: #0b1120;
                border-color: #1f2937;
            }
            body.dark-mode .cdp-edit-footer {
                border-top: 1px solid #1f2937;
            }
            .cdp-advanced-erase-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.72);
                backdrop-filter: blur(6px);
                z-index: 200000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 60px;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
            }
            .cdp-advanced-erase-overlay[data-visible="true"] {
                opacity: 1;
                pointer-events: auto;
            }
            .cdp-advanced-erase-shell {
                display: flex;
                width: min(1200px, 100%);
                height: min(720px, 100%);
                background: #ffffff;
                border-radius: 28px;
                box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
                overflow: hidden;
            }
            .cdp-advanced-erase-toolbar {
                width: 72px;
                background: #f8fafc;
                border-right: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                gap: 12px;
            }
            .cdp-advanced-erase-tool {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                border: 1px solid transparent;
                background: #ffffff;
                color: #0f172a;
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            }
            .cdp-advanced-erase-tool:hover {
                border-color: #cbd5f5;
                color: #2563eb;
            }
            .cdp-advanced-erase-tool--active {
                border-color: #2563eb;
                color: #2563eb;
                box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);
            }
            .cdp-advanced-erase-divider {
                width: 32px;
                height: 2px;
                background: #e2e8f0;
                margin: 6px 0 10px;
            }
            .cdp-advanced-erase-stage {
                flex: 1;
                display: flex;
                flex-direction: column;
                background: #ffffff;
            }
            .cdp-advanced-erase-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 24px 32px 12px;
                border-bottom: 1px solid #eef2ff;
            }
            .cdp-advanced-erase-header p {
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-size: 12px;
                color: #94a3b8;
                font-weight: 600;
            }
            .cdp-advanced-erase-header h3 {
                margin: 4px 0 0;
                font-size: 22px;
                color: #0f172a;
            }
            .cdp-advanced-erase-header-actions {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .cdp-advanced-erase-zoom {
                font-weight: 600;
                color: #2563eb;
                background: #eff6ff;
                padding: 8px 14px;
                border-radius: 999px;
            }
            .cdp-advanced-erase-close {
                border: none;
                background: #e2e8f0;
                width: 38px;
                height: 38px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                color: #0f172a;
            }
            .cdp-advanced-erase-close:hover {
                background: #cbd5f5;
            }
            .cdp-advanced-erase-canvas-wrap {
                flex: 1;
                position: relative;
                background: #f8fafc;
                overflow: hidden;
                padding: 32px;
            }
            .cdp-advanced-erase-canvas-inner {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                transform-origin: center;
                transition: transform 0.15s ease;
            }
            .cdp-advanced-erase-canvas-inner canvas {
                max-width: 100%;
                max-height: 100%;
                border-radius: 18px;
                background: transparent;
                box-shadow: inset 0 0 0 1px #e5e7eb;
                touch-action: none;
            }
            .cdp-advanced-erase-status {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 0 32px 12px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                letter-spacing: 0.01em;
            }
            .cdp-advanced-erase-status i {
                color: #2563eb;
                font-size: 16px;
            }
            .cdp-advanced-erase-status[data-state="busy"] i {
                animation: cdpWandPulse 1s infinite ease-in-out;
                color: #f97316;
            }
            .cdp-advanced-erase-status[data-state="success"] {
                color: #15803d;
            }
            .cdp-advanced-erase-status[data-state="success"] i {
                color: #22c55e;
            }
            .cdp-advanced-erase-status[data-state="neutral"] {
                color: #9333ea;
            }
            @keyframes cdpWandPulse {
                0% { transform: translateY(0); opacity: 0.7; }
                50% { transform: translateY(-2px); opacity: 1; }
                100% { transform: translateY(0); opacity: 0.7; }
            }
            .cdp-advanced-erase-footer {
                padding: 16px 32px 28px;
                display: flex;
                justify-content: space-between;
                gap: 24px;
                align-items: center;
                border-top: 1px solid #eef2ff;
            }
            .cdp-advanced-erase-brush {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .cdp-advanced-erase-brush label {
                font-size: 14px;
                color: #475569;
                font-weight: 600;
            }
            .cdp-advanced-erase-auto {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .cdp-advanced-erase-auto label {
                font-size: 14px;
                color: #475569;
                font-weight: 600;
            }
            .cdp-advanced-erase-brush input,
            .cdp-advanced-erase-auto input {
                width: 100%;
                accent-color: #2563eb;
            }
            .cdp-advanced-erase-footer-actions {
                display: flex;
                gap: 12px;
            }
            @media (max-width: 1024px) {
                .cdp-advanced-erase-overlay {
                    padding: 20px;
                }
                .cdp-advanced-erase-shell {
                    flex-direction: column;
                    width: min(100%, 960px);
                    height: calc(100vh - 40px);
                    border-radius: 24px;
                }
                .cdp-advanced-erase-toolbar {
                    width: 100%;
                    flex-direction: row;
                    height: auto;
                    border-right: none;
                    border-bottom: 1px solid #e2e8f0;
                    justify-content: center;
                    flex-wrap: wrap;
                    padding: 14px 16px;
                    gap: 8px;
                }
                .cdp-advanced-erase-tool {
                    width: 38px;
                    height: 38px;
                    border-radius: 12px;
                    font-size: 14px;
                }
                .cdp-advanced-erase-divider {
                    width: 2px;
                    height: 24px;
                    margin: 0 2px;
                }
                .cdp-advanced-erase-header {
                    padding: 18px 22px 10px;
                }
                .cdp-advanced-erase-canvas-wrap {
                    padding: 22px;
                }
                .cdp-advanced-erase-status {
                    padding: 0 22px 10px;
                }
                .cdp-advanced-erase-footer {
                    padding: 14px 22px 20px;
                    gap: 18px;
                }
                .cdp-advanced-erase-stage {
                    flex: 1;
                }
            }
            @media (max-width: 640px) {
                .cdp-advanced-erase-overlay {
                    padding: 0;
                }
                .cdp-advanced-erase-shell {
                    border-radius: 0;
                    width: 100%;
                    height: 100dvh;
                    overflow: hidden;
                }
                .cdp-advanced-erase-toolbar {
                    padding: calc(8px + env(safe-area-inset-top, 0px)) 8px 8px;
                    gap: 5px;
                    overflow: visible;
                    justify-content: stretch;
                    position: relative;
                    top: auto;
                    z-index: 3;
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    align-items: stretch;
                    background: #f8fafc;
                    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
                    flex: 0 0 auto;
                }
                .cdp-advanced-erase-tool {
                    width: 100%;
                    min-width: 0;
                    height: 33px;
                    border-radius: 10px;
                    font-size: 11px;
                    padding: 4px 6px;
                    gap: 3px;
                    flex: 0 0 auto;
                    flex-direction: column;
                }
                .cdp-advanced-erase-tool::after {
                    content: attr(data-mobile-label);
                    font-size: 8px;
                    font-weight: 700;
                    line-height: 1.1;
                    white-space: normal;
                    text-align: center;
                }
                .cdp-advanced-erase-divider {
                    display: none;
                }
                .cdp-advanced-erase-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                    padding: 14px 14px 10px;
                    flex: 0 0 auto;
                }
                .cdp-advanced-erase-header-actions {
                    width: 100%;
                    justify-content: space-between;
                }
                .cdp-advanced-erase-stage {
                    min-height: 0;
                    overflow-y: auto;
                    overflow-x: hidden;
                    -webkit-overflow-scrolling: touch;
                }
                .cdp-advanced-erase-canvas-wrap {
                    padding: 14px;
                    flex: 0 0 auto;
                    min-height: 280px;
                }
                .cdp-advanced-erase-status {
                    flex: 0 0 auto;
                }
                .cdp-advanced-erase-footer {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 12px;
                    padding: 12px 14px calc(14px + env(safe-area-inset-bottom, 0px));
                    flex: 0 0 auto;
                    position: sticky;
                    bottom: 0;
                    background: #ffffff;
                    box-shadow: 0 -10px 24px rgba(15, 23, 42, 0.08);
                }
                .cdp-advanced-erase-footer-actions {
                    width: 100%;
                    justify-content: stretch;
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                }
                .cdp-advanced-erase-footer-actions > * {
                    min-height: 40px;
                }
            }
            @media (max-width: 420px) {
                .cdp-advanced-erase-toolbar {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
                .cdp-advanced-erase-tool {
                    height: 32px;
                }
            }
            body.dark-mode .cdp-advanced-erase-shell {
                background: #0f172a;
                color: #f8fafc;
            }
            body.dark-mode .cdp-advanced-erase-toolbar {
                background: #0b1120;
                border-color: #1e293b;
            }
            body.dark-mode .cdp-advanced-erase-tool {
                background: #1e293b;
                color: #e2e8f0;
                box-shadow: none;
            }
            body.dark-mode .cdp-advanced-erase-tool:hover {
                border-color: #3b82f6;
            }
            body.dark-mode .cdp-advanced-erase-tool--active {
                border-color: #60a5fa;
                color: #93c5fd;
            }
            body.dark-mode .cdp-advanced-erase-divider {
                background: #1e293b;
            }
            body.dark-mode .cdp-advanced-erase-stage {
                background: #0f172a;
            }
            body.dark-mode .cdp-advanced-erase-header {
                border-bottom: 1px solid #1e293b;
            }
            body.dark-mode .cdp-advanced-erase-canvas-wrap {
                background: #020617;
            }
            body.dark-mode .cdp-advanced-erase-footer {
                border-top: 1px solid #1e293b;
            }
            body.dark-mode .cdp-advanced-erase-close {
                background: #1e293b;
                color: #e2e8f0;
            }
            body.dark-mode .cdp-advanced-erase-status {
                color: #94a3b8;
            }
            body.dark-mode .cdp-advanced-erase-status[data-state="success"] {
                color: #4ade80;
            }
            body.dark-mode .cdp-advanced-erase-status[data-state="neutral"] {
                color: #c084fc;
            }
            body.dark-mode .cdp-advanced-erase-status i {
                color: #93c5fd;
            }
            body.dark-mode .cdp-advanced-erase-auto label,
            body.dark-mode .cdp-advanced-erase-brush label {
                color: #e2e8f0;
            }
        `;
        document.head.appendChild(style);
    }

    // =========================
    // Reattach Events for Duplicated Images
    // =========================

    function reattachUploadEventsWithData(imgEl, layerData) {
        console.log('Reattaching events for uploaded image:', imgEl.id);
        
        // Setup drag
        setupImageDrag(imgEl, layerData);

        // Setup double click to edit
        imgEl.addEventListener('dblclick', () => openEditPanel(imgEl, layerData));
    }

    function getUploadedInvoiceAssets() {
        const layerAssets = window.cdpLayers && typeof window.cdpLayers.getLayers === 'function'
            ? window.cdpLayers.getLayers().filter(layer => layer && layer.type === 'upload')
            : [];

        if (layerAssets.length) {
            return layerAssets.map((layer, index) => {
                const element = layer.element instanceof Element ? layer.element : null;
                const imageEl = element ? element.querySelector('img') : null;
                return {
                    id: layer.id || `upload-${index + 1}`,
                    name: element?.dataset?.uploadName || layer.name || `Uploaded Image ${index + 1}`,
                    type: element?.dataset?.uploadType || 'image/jpeg',
                    originalSrc: layer.originalSrc || element?.dataset?.originalSrc || imageEl?.dataset?.originalSrc || '',
                    optimizedSrc: element?.dataset?.optimizedSrc || imageEl?.currentSrc || imageEl?.src || '',
                    view: layer.view || element?.closest('.cdp-print-box')?.dataset?.view || 'front'
                };
            }).filter(asset => asset.originalSrc || asset.optimizedSrc);
        }

        return Array.from(document.querySelectorAll('.cdp-uploaded-image')).map((element, index) => {
            const imageEl = element.querySelector('img');
            return {
                id: element.id || `upload-${index + 1}`,
                name: element.dataset.uploadName || `Uploaded Image ${index + 1}`,
                type: element.dataset.uploadType || 'image/jpeg',
                originalSrc: element.dataset.originalSrc || imageEl?.dataset?.originalSrc || '',
                optimizedSrc: element.dataset.optimizedSrc || imageEl?.currentSrc || imageEl?.src || '',
                view: element.closest('.cdp-print-box')?.dataset?.view || 'front'
            };
        }).filter(asset => asset.originalSrc || asset.optimizedSrc);
    }

    // Export for global use
    window.reattachUploadEventsWithData = reattachUploadEventsWithData;
    window.cdpUploadAssets = {
        getInvoiceAssets: getUploadedInvoiceAssets
    };

    // =========================
    // Initialize on DOM Ready
    // =========================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();




