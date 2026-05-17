// ===========================
// SHAPE SYSTEM
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    console.log("🔷 shape.js loaded");

    const shapeBtn = document.querySelector('[data-tool="shape"]');
    const FALLBACK_SHAPE_TUTORIAL_URL = 'https://www.youtube.com/watch?v=q1_kB3BZ6Yo';
    if (!shapeBtn) {
        console.error("❌ Shape button not found!");
        return;
    }

    if (!window.cdpState) {
        window.cdpState = { currentView: 'front' };
    }

    if (!window.layersByView) {
        window.layersByView = {
            front: [],
            back: [],
            left: [],
            right: []
        };
    }

    if (typeof window.cdpShapeTutorialUrl !== 'string' || !window.cdpShapeTutorialUrl.trim()) {
        window.cdpShapeTutorialUrl = FALLBACK_SHAPE_TUTORIAL_URL;
    }

    const settings = {
        color: '#111111',
        stroke: 2,
        fill: false
    };

    const SHAPES = [
        { id: 'circle', name: 'Circle', type: 'circle', cx: 50, cy: 50, r: 32 },
        { id: 'square', name: 'Square', type: 'path', d: 'M20 20 H80 V80 H20 Z' },
        { id: 'rounded-square', name: 'Rounded Square', type: 'path', d: 'M32 18 H68 Q82 18 82 32 V68 Q82 82 68 82 H32 Q18 82 18 68 V32 Q18 18 32 18 Z' },
        { id: 'triangle', name: 'Triangle', type: 'path', d: 'M50 14 L86 86 H14 Z' },
        { id: 'diamond', name: 'Diamond', type: 'path', d: 'M50 10 L88 50 L50 90 L12 50 Z' },
        { id: 'pentagon', name: 'Pentagon', type: 'path', d: 'M50 10 L85 38 L70 90 H30 L15 38 Z' },
        { id: 'hexagon', name: 'Hexagon', type: 'path', d: 'M32 12 H68 L90 50 L68 88 H32 L10 50 Z' },
        { id: 'star', name: 'Star', type: 'path', d: 'M50 10 L62 38 L92 38 L68 56 L78 88 L50 70 L22 88 L32 56 L8 38 L38 38 Z' },
        { id: 'heart', name: 'Heart', type: 'path', d: 'M50 78 L24 48 C14 34 20 18 36 18 C46 18 50 26 50 26 C50 26 54 18 64 18 C80 18 86 34 76 48 Z' },
        { id: 'cloud', name: 'Cloud', type: 'path', d: 'M30 70 H74 C86 70 92 60 86 52 C90 32 62 22 54 34 C46 24 30 30 32 44 C20 46 20 62 30 70 Z' },
        { id: 'lightning', name: 'Lightning', type: 'path', d: 'M40 12 L70 12 L60 40 H84 L40 92 L48 58 H20 Z' },
        { id: 'arrow-right', name: 'Arrow Right', type: 'path', d: 'M20 50 H70 M70 50 L52 32 M70 50 L52 68' },
        { id: 'arrow-left', name: 'Arrow Left', type: 'path', d: 'M80 50 H30 M30 50 L48 32 M30 50 L48 68' },
        { id: 'arrow-up', name: 'Arrow Up', type: 'path', d: 'M50 20 V80 M50 20 L32 38 M50 20 L68 38' },
        { id: 'arrow-down', name: 'Arrow Down', type: 'path', d: 'M50 20 V80 M50 80 L32 62 M50 80 L68 62' },
        { id: 'speech', name: 'Speech Bubble', type: 'path', d: 'M20 30 H80 V68 H58 L50 82 L46 68 H20 Z' },
        { id: 'badge', name: 'Badge', type: 'path', d: 'M30 10 H70 L90 30 V70 L70 90 H30 L10 70 V30 Z' },
        { id: 'plus', name: 'Plus', type: 'path', d: 'M50 18 V82 M18 50 H82' },
        { id: 'minus', name: 'Minus', type: 'path', d: 'M20 50 H80' }
    ];

    let shapePanel = null;
    let resizePanel = null;
    let selectedShape = SHAPES.length ? SHAPES[0] : null;
    let currentResizingShape = null;
    let currentResizingLayer = null;
    let originalResizingSize = null;
    const PREVIEW_MAX_SIZE = 220;

    // Global drag state
    let isDragging = false;
    let dragElement = null;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    let dragTransformExtras = '';
    let shapeTutorialToastTimeout = null;

    function handleShapeTutorialClick() {
        if (typeof window.cdpShapeTutorialHandler === 'function') {
            try {
                window.cdpShapeTutorialHandler();
                return;
            } catch (err) {
                console.error('Shape tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpShapeTutorialUrl || FALLBACK_SHAPE_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showShapeTutorialToast('🎥 Shape tutorial coming soon');
    }

    function showShapeTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpShapeTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpShapeTutorialToast';
            toast.className = 'cdp-shape-tutorial-toast';
            document.body.appendChild(toast);

            if (!document.getElementById('cdpShapeTutorialToastStyles')) {
                const toastStyle = document.createElement('style');
                toastStyle.id = 'cdpShapeTutorialToastStyles';
                toastStyle.textContent = `
                    .cdp-shape-tutorial-toast {
                        position: fixed;
                        top: 32px;
                        right: 32px;
                        background: #0f172a;
                        color: #f8fafc;
                        padding: 12px 20px;
                        border-radius: 999px;
                        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.2);
                        opacity: 0;
                        transform: translateY(-10px);
                        transition: opacity 0.25s ease, transform 0.25s ease;
                        font-weight: 600;
                        letter-spacing: 0.04em;
                        pointer-events: none;
                        z-index: 100001;
                    }
                    .cdp-shape-tutorial-toast[data-visible="true"] {
                        opacity: 1;
                        transform: translateY(0);
                    }
                `;
                document.head.appendChild(toastStyle);
            }
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (shapeTutorialToastTimeout) {
            clearTimeout(shapeTutorialToastTimeout);
        }

        shapeTutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2600);
    }

    function stripTranslate(transformStr) {
        if (!transformStr || transformStr === 'none') return '';
        return transformStr.replace(/translate\([^)]+\)\s*/g, '').trim();
    }

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

    function moveDraggedShape(event) {
        if (isDragging && dragElement) {
            if ((event.type === 'touchmove' || event.type === 'pointermove') && event.cancelable) {
                event.preventDefault();
            }

            const point = getEventPoint(event);
            const deltaX = point.clientX - startX;
            const deltaY = point.clientY - startY;

            dragElement.style.left = (startLeft + deltaX) + 'px';
            dragElement.style.top = (startTop + deltaY) + 'px';
            const extras = dragTransformExtras || '';
            dragElement.style.transform = extras || 'none';
        }
    }

    function stopDraggedShape() {
        if (isDragging) {
            isDragging = false;
            if (dragElement) {
                dragElement.style.cursor = 'grab';
                dragElement = null;
            }
            dragTransformExtras = '';
        }
    }

    document.addEventListener('mousemove', moveDraggedShape);
    document.addEventListener('pointermove', moveDraggedShape, { passive: false });
    document.addEventListener('touchmove', moveDraggedShape, { passive: false });

    document.addEventListener('mouseup', stopDraggedShape);
    document.addEventListener('pointerup', stopDraggedShape);
    document.addEventListener('pointercancel', stopDraggedShape);
    document.addEventListener('touchend', stopDraggedShape);
    document.addEventListener('touchcancel', stopDraggedShape);

    function attachShapeEvents(shapeEl, layerData) {
        console.log("📎 Attaching events to", shapeEl.id, "locked =", layerData.locked);
        
        if (!shapeEl.parentNode) {
            console.warn("⚠️ Element not in DOM yet, attaching directly");
            
            const handleDetachedShapeDragStart = function(e) {
                console.log("👆 Mousedown on", shapeEl.id, "locked =", layerData.locked);
                if (layerData.locked) {
                    console.log("🔒 Layer is locked, ignoring");
                    return;
                }
                if (e.type === 'mousedown' && e.button !== 0) return;
                
                isDragging = true;
                dragElement = shapeEl;
                const point = getEventPoint(e);
                startX = point.clientX;
                startY = point.clientY;

                const inlineTransform = shapeEl.style.transform || '';
                dragTransformExtras = stripTranslate(inlineTransform);

                const rect = shapeEl.getBoundingClientRect();
                const parent = shapeEl.parentElement.getBoundingClientRect();
                startLeft = rect.left - parent.left;
                startTop = rect.top - parent.top;

                shapeEl.style.cursor = 'grabbing';
                e.preventDefault();
                e.stopPropagation();
            };

            shapeEl.style.touchAction = 'none';
            shapeEl.addEventListener('mousedown', handleDetachedShapeDragStart, false);
            shapeEl.addEventListener('pointerdown', handleDetachedShapeDragStart, false);
            shapeEl.addEventListener('touchstart', handleDetachedShapeDragStart, { passive: false });

            shapeEl.addEventListener('dblclick', function(e) {
                if (layerData.locked) return;
                
                currentResizingShape = shapeEl;
                currentResizingLayer = layerData;
                showResizePanel();
                
                e.preventDefault();
                e.stopPropagation();
            }, false);

            attachDoubleTapHandler(shapeEl, function(e) {
                if (layerData.locked) return;
                currentResizingShape = shapeEl;
                currentResizingLayer = layerData;
                showResizePanel();
            }, () => isDragging || layerData.locked);
            
            console.log("✅ Events attached successfully");
            return;
        }
        
        const newShapeEl = shapeEl.cloneNode(true);
        shapeEl.parentNode.replaceChild(newShapeEl, shapeEl);
        layerData.element = newShapeEl;
        
        const handleShapeDragStart = function(e) {
            console.log("👆 Mousedown on", newShapeEl.id, "locked =", layerData.locked);
            if (layerData.locked) {
                console.log("🔒 Layer is locked, ignoring");
                return;
            }
            if (e.type === 'mousedown' && e.button !== 0) return;
            
            isDragging = true;
            dragElement = newShapeEl;
            const point = getEventPoint(e);
            startX = point.clientX;
            startY = point.clientY;

            const inlineTransform = newShapeEl.style.transform || '';
            dragTransformExtras = stripTranslate(inlineTransform);

            const rect = newShapeEl.getBoundingClientRect();
            const parent = newShapeEl.parentElement.getBoundingClientRect();
            startLeft = rect.left - parent.left;
            startTop = rect.top - parent.top;

            newShapeEl.style.cursor = 'grabbing';
            e.preventDefault();
            e.stopPropagation();
        };

        newShapeEl.style.touchAction = 'none';
        newShapeEl.addEventListener('mousedown', handleShapeDragStart, false);
        newShapeEl.addEventListener('pointerdown', handleShapeDragStart, false);
        newShapeEl.addEventListener('touchstart', handleShapeDragStart, { passive: false });

        newShapeEl.addEventListener('dblclick', function(e) {
            if (layerData.locked) return;
            
            currentResizingShape = newShapeEl;
            currentResizingLayer = layerData;
            showResizePanel();
            
            e.preventDefault();
            e.stopPropagation();
        }, false);

        attachDoubleTapHandler(newShapeEl, function(e) {
            if (layerData.locked) return;
            currentResizingShape = newShapeEl;
            currentResizingLayer = layerData;
            showResizePanel();
        }, () => isDragging || layerData.locked);
        
        console.log("✅ Events attached successfully");
    }
    // ===========================
    // Resize Panel
    // ===========================

    function applyPreviewStroke(previewEl) {
        if (!previewEl) return;
        const svgGroups = previewEl.querySelectorAll('svg g');
        svgGroups.forEach(group => {
            group.setAttribute('stroke', '#111827');
            group.setAttribute('stroke-width', '2');
            group.setAttribute('fill', 'none');
        });
    }

    function renderShapePreview(previewEl, size) {
        if (!previewEl || !currentResizingShape) return;
        const svg = currentResizingShape.querySelector('svg');
        if (!svg) return;

        const clone = svg.cloneNode(true);
        previewEl.innerHTML = '';
        previewEl.appendChild(clone);

        const renderSize = Math.min(size, PREVIEW_MAX_SIZE);
        clone.setAttribute('width', renderSize);
        clone.setAttribute('height', renderSize);
        clone.style.width = renderSize + 'px';
        clone.style.height = renderSize + 'px';
        clone.style.display = 'block';

        applyPreviewStroke(previewEl);
    }

    function createResizePanel() {
        if (resizePanel) return;

        resizePanel = document.createElement('div');
        resizePanel.className = 'cdp-icon-resize-panel';
        resizePanel.innerHTML = `
            <div class="cdp-icon-resize-content">
                <div class="cdp-icon-resize-header">
                    <h3>Resize Shape</h3>
                    <button type="button" class="cdp-icon-resize-close">&times;</button>
                </div>
                <div class="cdp-icon-resize-body">
                    <div class="cdp-icon-resize-group">
                        <label>Size: <span id="cdpShapeSizeValue">180</span>px</label>
                        <input type="range" id="cdpShapeSizeSlider" min="50" max="400" value="180" step="10">
                    </div>
                    <div class="cdp-icon-resize-transform">
                        <label>Transform:</label>
                        <div class="cdp-transform-buttons">
                            <button type="button" class="cdp-transform-btn" data-action="rotate-right" title="Rotate Right">↻</button>
                            <button type="button" class="cdp-transform-btn" data-action="rotate-left" title="Rotate Left">↺</button>
                            <button type="button" class="cdp-transform-btn" data-action="rotate-90" title="Rotate 90°">90°</button>
                            <button type="button" class="cdp-transform-btn" data-action="flip-vertical" title="Flip Vertical">⇅</button>
                            <button type="button" class="cdp-transform-btn" data-action="flip-horizontal" title="Flip Horizontal">⇄</button>
                        </div>
                    </div>
                    <div class="cdp-icon-resize-preview">
                        <div id="cdpShapeResizePreview"></div>
                    </div>
                </div>
                <div class="cdp-icon-resize-footer">
                    <button type="button" class="cdp-icon-btn cdp-icon-btn--cancel">Cancel</button>
                    <button type="button" class="cdp-icon-btn cdp-icon-btn--apply">Apply</button>
                </div>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            .cdp-icon-resize-panel {
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
            .cdp-icon-resize-panel[data-visible="true"] {
                display: flex;
            }
            .cdp-icon-resize-content {
                background: #ffffff;
                border-radius: 16px;
                width: 500px;
                max-width: 90%;
                box-shadow: 0 25px 50px rgba(15, 23, 42, 0.25);
                border: 1px solid #d7dbe7;
                pointer-events: auto;
                color: #0f172a;
            }
            .cdp-icon-resize-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 18px 24px;
                border-bottom: 1px solid #e2e8f0;
            }
            .cdp-icon-resize-header h3 {
                margin: 0;
                color: #0f172a;
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.04em;
            }
            .cdp-icon-resize-close {
                background: none;
                border: none;
                color: #94a3b8;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s ease;
            }
            .cdp-icon-resize-close:hover {
                color: #0f172a;
            }
            .cdp-icon-resize-body {
                padding: 22px 24px 18px;
                background: #f8fafc;
            }
            .cdp-icon-resize-group {
                margin-bottom: 20px;
            }
            .cdp-icon-resize-group label {
                display: block;
                color: #1f2937;
                margin-bottom: 10px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            #cdpShapeSizeSlider {
                width: 100%;
                height: 6px;
                border-radius: 999px;
                background: #e2e8f0;
                outline: none;
                -webkit-appearance: none;
            }
            #cdpShapeSizeSlider::-webkit-slider-runnable-track {
                height: 6px;
                border-radius: 999px;
                background: #e2e8f0;
            }
            #cdpShapeSizeSlider::-moz-range-track {
                height: 6px;
                border-radius: 999px;
                background: #e2e8f0;
            }
            #cdpShapeSizeSlider::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #fbbf24;
                cursor: pointer;
                box-shadow: 0 6px 18px rgba(251, 191, 36, 0.35);
                margin-top: -6px;
            }
            #cdpShapeSizeSlider::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #fbbf24;
                cursor: pointer;
                border: none;
                box-shadow: 0 6px 18px rgba(251, 191, 36, 0.35);
            }
            .cdp-icon-resize-transform {
                margin-bottom: 20px;
            }
            .cdp-icon-resize-transform label {
                display: block;
                color: #1f2937;
                margin-bottom: 10px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            .cdp-transform-buttons {
                display: flex;
                gap: 10px;
            }
            .cdp-transform-btn {
                flex: 1;
                padding: 10px 16px;
                background: #ffffff;
                border: 1px solid #dfe7f1;
                border-radius: 10px;
                cursor: pointer;
                font-size: 18px;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #0f172a;
                box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
            }
            .cdp-transform-btn:hover {
                background: #fef3c7;
                border-color: #fbbf24;
                color: #92400e;
            }
            .cdp-transform-btn:active {
                background: #fbbf24;
                color: #0f172a;
                border-color: #fbbf24;
            }
            .cdp-icon-resize-preview {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 150px;
                height: 280px;
                width: 100%;
                max-width: 340px;
                background: #ffffff;
                border-radius: 16px;
                padding: 20px;
                border: 2px dashed #e2e8f0;
                box-shadow: inset 0 0 0 1px #f8fafc;
                overflow: hidden;
            }
            .cdp-icon-resize-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                padding: 18px 24px;
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
            }
            .cdp-icon-resize-footer .cdp-icon-btn {
                min-width: 120px;
                padding: 10px 18px;
                border-radius: 999px;
                border: 1px solid #d7dbe7;
                background: #ffffff;
                color: #0f172a;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                transition: all 0.2s ease;
            }
            .cdp-icon-resize-footer .cdp-icon-btn--cancel:hover {
                border-color: #0f172a;
                color: #0f172a;
            }
            .cdp-icon-resize-footer .cdp-icon-btn--apply {
                background: #fbbf24;
                color: #0f172a;
                border-color: #fbbf24;
                box-shadow: 0 12px 24px rgba(250, 204, 21, 0.35);
            }
            .cdp-icon-resize-footer .cdp-icon-btn--apply:hover {
                filter: brightness(1.05);
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(resizePanel);

        const closeBtn = resizePanel.querySelector('.cdp-icon-resize-close');
        const cancelBtn = resizePanel.querySelector('.cdp-icon-btn--cancel');
        const applyBtn = resizePanel.querySelector('.cdp-icon-btn--apply');
        const sizeSlider = document.getElementById('cdpShapeSizeSlider');
        const sizeValue = document.getElementById('cdpShapeSizeValue');
        const preview = document.getElementById('cdpShapeResizePreview');

        closeBtn.addEventListener('click', () => closeResizePanel());
        cancelBtn.addEventListener('click', () => closeResizePanel());

        sizeSlider.addEventListener('input', function() {
            const size = parseInt(this.value, 10);
            sizeValue.textContent = size;
            
            if (currentResizingShape) {
                currentResizingShape.style.width = size + 'px';
                currentResizingShape.style.height = size + 'px';

                if (currentResizingLayer) {
                    currentResizingLayer.width = size;
                    currentResizingLayer.height = size;
                }

                renderShapePreview(preview, size);
                
                // Extract only rotation and scale for preview (not translate)
                const transform = currentResizingShape.style.transform || '';
                const rotateMatch = transform.match(/rotate\((-?[\d.]+)deg\)/);
                const scaleMatch = transform.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
                
                let previewTransform = '';
                if (rotateMatch) previewTransform += `rotate(${rotateMatch[1]}deg) `;
                if (scaleMatch) previewTransform += `scale(${scaleMatch[1]}, ${scaleMatch[2]})`;
                
                const previewShape = preview.firstElementChild;
                if (previewShape) {
                    previewShape.style.transformOrigin = 'center';
                    previewShape.style.transform = previewTransform.trim();
                }
            }
        });

        // Transform buttons
        const transformButtons = resizePanel.querySelectorAll('.cdp-transform-btn');
        transformButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!currentResizingShape) return;
                
                const action = this.getAttribute('data-action');
                const rawTransform = (currentResizingShape.style.transform || '').trim();
                const currentTransform = rawTransform === 'none' ? '' : rawTransform;
                
                // Preserve any existing translate so rotations/flips do not move the shape
                const translateMatches = currentTransform.match(/translate\([^)]+\)/g);
                const translatePart = translateMatches ? translateMatches.join(' ') + ' ' : '';
                
                // Parse current rotation and flips
                let rotation = 0;
                let scaleX = 1;
                let scaleY = 1;
                
                const rotateMatch = currentTransform.match(/rotate\((-?[\d.]+)deg\)/);
                if (rotateMatch) rotation = parseFloat(rotateMatch[1]);
                
                const scaleMatch = currentTransform.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
                if (scaleMatch) {
                    scaleX = parseFloat(scaleMatch[1]);
                    scaleY = parseFloat(scaleMatch[2]);
                }
                
                // Apply transformation
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
                
                // Apply to shape while keeping its current position intact
                const newTransform = `${translatePart}rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`.trim();
                currentResizingShape.style.transformOrigin = 'center';
                currentResizingShape.style.transform = newTransform || 'none';
                
                const previewShape = preview.firstElementChild;
                if (previewShape) {
                    previewShape.style.transformOrigin = 'center';
                    previewShape.style.transform = `rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`;
                }
            });
        });

        applyBtn.addEventListener('click', function() {
            if (currentResizingShape) {
                const size = parseInt(sizeSlider.value, 10);
                
                currentResizingShape.style.width = size + 'px';
                currentResizingShape.style.height = size + 'px';

                if (currentResizingLayer) {
                    currentResizingLayer.width = size;
                    currentResizingLayer.height = size;
                }
            }
            closeResizePanel({ commit: true });
        });
    }

    function showResizePanel() {
        if (!resizePanel) createResizePanel();
        
        if (!currentResizingShape) return;

        const sizeSlider = document.getElementById('cdpShapeSizeSlider');
        const sizeValue = document.getElementById('cdpShapeSizeValue');
        const preview = document.getElementById('cdpShapeResizePreview');

        let currentSize = 180;
        if (currentResizingLayer && currentResizingLayer.width) {
            currentSize = currentResizingLayer.width;
        } else {
            const computedWidth = window.getComputedStyle(currentResizingShape).width;
            currentSize = parseInt(computedWidth);
        }

        sizeSlider.value = currentSize;
        sizeValue.textContent = currentSize;
        originalResizingSize = currentSize;

        renderShapePreview(preview, currentSize);
        
        // Extract only rotation and scale for preview (not translate)
        const transform = currentResizingShape.style.transform || '';
        const rotateMatch = transform.match(/rotate\((-?[\d.]+)deg\)/);
        const scaleMatch = transform.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
        
        let previewTransform = '';
        if (rotateMatch) previewTransform += `rotate(${rotateMatch[1]}deg) `;
        if (scaleMatch) previewTransform += `scale(${scaleMatch[1]}, ${scaleMatch[2]})`;
        
        const previewShape = preview.firstElementChild;
        if (previewShape) {
            previewShape.style.transformOrigin = 'center';
            previewShape.style.transform = previewTransform.trim();
        }

        resizePanel.setAttribute('data-visible', 'true');
    }

    function closeResizePanel(options = {}) {
        if (!resizePanel) return;

        const commit = options.commit === true;

        if (!commit && currentResizingShape && originalResizingSize !== null) {
            currentResizingShape.style.width = originalResizingSize + 'px';
            currentResizingShape.style.height = originalResizingSize + 'px';

            if (currentResizingLayer) {
                currentResizingLayer.width = originalResizingSize;
                currentResizingLayer.height = originalResizingSize;
            }
        }

        resizePanel.setAttribute('data-visible', 'false');
        currentResizingShape = null;
        currentResizingLayer = null;
        originalResizingSize = null;
    }

    // ===========================
    // Create Panel
    // ===========================

    function createPanel() {
        shapePanel = document.getElementById('cdpShapePanel');
        if (!shapePanel) return;

        const closeBtn = shapePanel.querySelector('.cdp-icon-panel-close');
        const cancelBtn = shapePanel.querySelector('.cdp-icon-btn--cancel');
        const addBtn = shapePanel.querySelector('.cdp-icon-btn--add');
        const grid = document.getElementById('cdpShapeGrid');
        const tutorialBtn = document.getElementById('cdpShapeTutorial');
        
        const colorChip = document.getElementById('cdpShapeColorChip');
        const colorInput = document.getElementById('cdpShapeColorInput');
        const strokeSlider = document.getElementById('cdpShapeStroke');
        const strokeValue = document.getElementById('cdpShapeStrokeValue');
        const fillCheckbox = document.getElementById('cdpShapeFill');

        colorChip.style.background = settings.color;
        colorInput.value = settings.color;
        strokeSlider.value = settings.stroke;
        strokeValue.textContent = settings.stroke;
        fillCheckbox.checked = settings.fill;

        closeBtn.addEventListener('click', closePanel);
        cancelBtn.addEventListener('click', closePanel);
        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleShapeTutorialClick);
        }

        // Color controls
        colorChip.addEventListener('click', () => colorInput.click());
        
        colorInput.addEventListener('input', () => {
            settings.color = colorInput.value;
            colorChip.style.background = settings.color;
            refreshPreviews();
        });

        strokeSlider.addEventListener('input', () => {
            settings.stroke = parseInt(strokeSlider.value);
            strokeValue.textContent = settings.stroke;
            refreshPreviews();
        });

        fillCheckbox.addEventListener('change', () => {
            settings.fill = fillCheckbox.checked;
            refreshPreviews();
        });

        grid.addEventListener('click', (e) => {
            const btn = e.target.closest('.cdp-shape-item');
            if (!btn) return;
            
            grid.querySelectorAll('.cdp-shape-item').forEach(b => b.classList.remove('cdp-shape-item--selected'));
            
            btn.classList.add('cdp-shape-item--selected');
            selectedShape = SHAPES.find(s => s.id === btn.dataset.id);
        });

        addBtn.addEventListener('click', () => {
            addShape();
            closePanel();
        });

        renderShapes();
    }

    function renderShapes() {
        if (!shapePanel) return;

        const grid = document.getElementById('cdpShapeGrid');
        grid.innerHTML = '';

        if (!Array.isArray(SHAPES) || SHAPES.length === 0) {
            const emptyMsg = document.createElement('p');
            emptyMsg.className = 'cdp-shape-empty';
            emptyMsg.textContent = 'Shapes unavailable';
            grid.appendChild(emptyMsg);
            selectedShape = null;
            return;
        }
        
        const frag = document.createDocumentFragment();
        
        SHAPES.forEach(shape => {
            const btn = document.createElement('button');
            btn.className = 'cdp-shape-item';
            btn.type = 'button';
            btn.dataset.id = shape.id;
            btn.title = shape.name;

            const svg = createShapeSVG(shape, true);
            btn.appendChild(svg);
            if (selectedShape && selectedShape.id === shape.id) {
                btn.classList.add('cdp-shape-item--selected');
            }
            frag.appendChild(btn);
        });
        
        grid.appendChild(frag);

        if (!selectedShape) {
            selectedShape = SHAPES[0];
            const firstBtn = grid.querySelector('.cdp-shape-item');
            if (firstBtn) {
                firstBtn.classList.add('cdp-shape-item--selected');
            }
        }
    }

    function refreshPreviews() {
        const grid = document.getElementById('cdpShapeGrid');
        if (!grid) return;
        if (!Array.isArray(SHAPES) || SHAPES.length === 0) return;

        grid.querySelectorAll('.cdp-shape-item').forEach(btn => {
            const shapeId = btn.dataset.id;
            const shape = SHAPES.find(s => s.id === shapeId);
            if (shape) {
                btn.innerHTML = '';
                const svg = createShapeSVG(shape, true);
                btn.appendChild(svg);
            }
        });
    }

    function createShapeSVG(shape, preview = false) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 100 100');
        
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('stroke', preview ? '#111111' : settings.color);
        g.setAttribute('stroke-width', preview ? '2' : String(settings.stroke));
        g.setAttribute('stroke-linecap', 'round');
        g.setAttribute('stroke-linejoin', 'round');
        g.setAttribute('fill', preview ? 'none' : (settings.fill ? settings.color : 'none'));

        let element;
        if (shape.type === 'circle') {
            element = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            element.setAttribute('cx', shape.cx);
            element.setAttribute('cy', shape.cy);
            element.setAttribute('r', shape.r);
        } else {
            element = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            element.setAttribute('d', shape.d);
        }

        g.appendChild(element);
        svg.appendChild(g);
        
        return svg;
    }

    function showPanel() {
        if (!shapePanel) createPanel();
        if (!shapePanel) return;

        shapePanel.style.transition = 'none';
        shapePanel.setAttribute('data-visible', 'true');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'none';
        }
    }

    function closePanel() {
        if (!shapePanel) return;
        
        shapePanel.style.transition = 'none';
        shapePanel.setAttribute('data-visible', 'false');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'auto';
        }
    }

    function addShape() {
        if (!selectedShape) {
            const firstShape = document.querySelector('#cdpShapeGrid .cdp-shape-item');
            if (firstShape) {
                selectedShape = SHAPES.find(s => s.id === firstShape.dataset.id);
            }
        }

        if (!selectedShape) return;

        const view = window.cdpState.currentView || 'front';
        const boxMap = { front: 'boxFront', back: 'boxBack', right: 'boxRight', left: 'boxLeft' };
        const printBox = document.getElementById(boxMap[view]);

        if (!printBox) return;

        const shapeEl = document.createElement('div');
        shapeEl.className = 'cdp-shape-element';
        shapeEl.id = 'shape-' + Date.now();

        shapeEl.style.position = 'absolute';
        shapeEl.style.left = '50%';
        shapeEl.style.top = '50%';
        shapeEl.style.transform = 'translate(-50%, -50%)';
        shapeEl.style.transformOrigin = 'center';
        shapeEl.style.cursor = 'grab';
        shapeEl.style.zIndex = 9999;
        shapeEl.style.userSelect = 'none';
        shapeEl.style.pointerEvents = 'auto';
        shapeEl.style.width = '180px';
        shapeEl.style.height = '180px';

        const svg = createShapeSVG(selectedShape, false);
        shapeEl.appendChild(svg);

        printBox.appendChild(shapeEl);

        // استفاده از API مرکزی برای اضافه کردن لایه
        const layerData = window.cdpLayers ? window.cdpLayers.addLayer({
            element: shapeEl,
            name: `Shape: ${selectedShape.name}`,
            type: 'shape',
            view: view
        }) : null;

        if (layerData) {
            layerData.shapeId = selectedShape.id;
            layerData.width = 180;
            layerData.height = 180;
            attachShapeEvents(shapeEl, layerData);
        } else {
            // fallback - روش قدیمی
            const fallbackLayerData = {
                id: shapeEl.id,
                name: `Shape: ${selectedShape.name}`,
                type: 'shape',
                shapeId: selectedShape.id,
                view: view,
                visible: true,
                locked: false,
                width: 180,
                height: 180,
                element: shapeEl
            };
            attachShapeEvents(shapeEl, fallbackLayerData);
            if (!window.layersByView[view]) {
                window.layersByView[view] = [];
            }
            window.layersByView[view].push(fallbackLayerData);
        }
    }

    // Fix for duplicate
    window.reattachShapeEvents = function(shapeElement) {
        const view = window.cdpState.currentView || 'front';
        const layerData = window.layersByView[view].find(l => l.element === shapeElement);
        
        if (layerData) {
            attachShapeEvents(shapeElement, layerData);
        }
    };

    window.reattachShapeEventsWithData = function(shapeElement, layerData) {
        console.log("🔄 Reattaching shape events with data:", {
            element: shapeElement.id,
            locked: layerData.locked
        });
        attachShapeEvents(shapeElement, layerData);
        console.log("✅ Shape events attached!");
    };

    shapeBtn.addEventListener('click', showPanel);

    document.addEventListener('keydown', (e) => {
        if (shapePanel && shapePanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closePanel();
        }
        if (resizePanel && resizePanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closeResizePanel();
        }
    });

    console.log("✅ Shape system ready!");
});