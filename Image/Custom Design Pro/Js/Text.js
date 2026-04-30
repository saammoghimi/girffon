// ===========================
// TEXT SYSTEM - Final Working Version
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    console.log("📝 text.js loaded");

    const textBtn = document.querySelector('[data-tool="add-text"]');
    const FALLBACK_TEXT_TUTORIAL_URL = 'https://www.youtube.com/watch?v=rX0U0Osghs0&t=72s';
    let textPanel = null;
    let selectedFont = 'Montserrat';
    let currentTextElement = null;
    let currentLayerData = null;

    // Global drag state
    let isDragging = false;
    let dragElement = null;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    let dragTransformExtras = '';
    let textTutorialToastTimeout = null;

    if (!textBtn) {
        console.error("❌ Add Text button not found!");
        return;
    }

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

    if (typeof window.cdpTextTutorialUrl !== 'string' || !window.cdpTextTutorialUrl.trim()) {
        window.cdpTextTutorialUrl = FALLBACK_TEXT_TUTORIAL_URL;
    }

    const fonts = [
        'Montserrat', 'Playfair Display', 'Cinzel', 'Abril Fatface', 'Oswald',
        'Raleway', 'Roboto', 'Open Sans', 'Lato', 'Poppins',
        'Nunito', 'Source Serif Pro', 'PT Serif', 'Karla', 'Fira Sans',
        'Inter', 'Crimson Text', 'Cormorant Garamond',
        'Great Vibes', 'Allura', 'Alex Brush', 'Tangerine', 'Pacifico',
        'Lobster', 'Dancing Script', 'Courgette', 'Satisfy', 'Marck Script',
        'Sacramento', 'Parisienne', 'Yellowtail', 'Rouge Script', 'Arizonia',
        'Kaushan Script', 'Gloria Hallelujah', 'Handlee', 'Indie Flower',
        'Shadows Into Light', 'Cookie', 'Mr Dafoe', 'Rochester',
        'VT323', 'Share Tech Mono', 'Space Mono',
        'Anton', 'Black Ops One', 'Titan One', 'Rubik Mono One',
        'Bungee', 'Bungee Shade', 'Monoton', 'Faster One',
        'Orbitron', 'Audiowide', 'Righteous'
    ];

    function handleTextTutorialClick() {
        if (typeof window.cdpTextTutorialHandler === 'function') {
            try {
                window.cdpTextTutorialHandler();
                return;
            } catch (err) {
                console.error('Text tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpTextTutorialUrl || FALLBACK_TEXT_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showTextTutorialToast('🎬 Text tutorial coming soon');
    }

    function showTextTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpTextTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpTextTutorialToast';
            toast.className = 'cdp-text-tutorial-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (textTutorialToastTimeout) {
            clearTimeout(textTutorialToastTimeout);
        }

        textTutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2800);
    }

    // ===========================
    // Global Mouse Events
    // ===========================

    function stripTranslate(transformStr) {
        if (!transformStr || transformStr === 'none') return '';
        return transformStr.replace(/translate\([^)]+\)\s*/g, '').trim();
    }

    window.addEventListener('mousemove', function(e) {
        if (isDragging && dragElement) {
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            dragElement.style.left = (startLeft + deltaX) + 'px';
            dragElement.style.top = (startTop + deltaY) + 'px';
            const extras = dragTransformExtras || '';
            dragElement.style.transform = extras || 'none';
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
        dragTransformExtras = '';
    });

    // ===========================
    // Attach Events to Text Element
    // ===========================

    function attachTextEvents(textEl, layerData) {
        textEl.addEventListener('mousedown', function(e) {
            if (layerData.locked) return;
            
            console.log('🔴 MOUSEDOWN!');
            isDragging = true;
            dragElement = textEl;
            startX = e.clientX;
            startY = e.clientY;

            const inlineTransform = textEl.style.transform || '';
            dragTransformExtras = stripTranslate(inlineTransform);

            const rect = textEl.getBoundingClientRect();
            const parent = textEl.parentElement.getBoundingClientRect();
            startLeft = rect.left - parent.left;
            startTop = rect.top - parent.top;

            textEl.style.cursor = 'grabbing';
            e.preventDefault();
            e.stopPropagation();
        }, false);

        textEl.addEventListener('dblclick', function(e) {
            if (layerData.locked) return;
            
            console.log('🟣 DOUBLE CLICK!');
            e.preventDefault();
            e.stopPropagation();
            showPanel(true, textEl, layerData);
        }, false);
    }

    // ===========================
    // Create Panel
    // ===========================

    function createPanel() {
        textPanel = document.createElement('div');
        textPanel.className = 'cdp-text-panel';
        textPanel.setAttribute('data-visible', 'false');

        let fontsHTML = '';
        fonts.forEach(font => {
            fontsHTML += `
                <button class="cdp-text-font-item" data-font="${font}">
                    <div class="cdp-text-font-label">${font}</div>
                    <div class="cdp-text-font-preview" style="font-family: '${font}', sans-serif;">GirffoN</div>
                </button>
            `;
        });

        textPanel.innerHTML = `
            <div class="cdp-text-panel-container">
                <header class="cdp-text-panel-header">
                    <h3><i class="fa-solid fa-font"></i> Text Editor</h3>
                    <div class="cdp-text-header-actions">
                        <button type="button" id="cdpTextTutorial" class="cdp-text-round-btn cdp-text-help" title="Tutorial">
                            <i class="fa-regular fa-circle-question"></i>
                        </button>
                        <button type="button" class="cdp-text-panel-close cdp-text-round-btn" title="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </header>
                <div class="cdp-text-panel-body">
                    <div class="cdp-text-group">
                        <label>Text Content</label>
                        <input type="text" id="cdpTextInput" class="cdp-text-input" value="Your Text">
                    </div>
                    <div class="cdp-text-group">
                        <label>Font Family</label>
                        <div class="cdp-text-font-scroll">${fontsHTML}</div>
                    </div>
                    <div class="cdp-text-group">
                        <label>Font Size</label>
                        <div class="cdp-text-size-row">
                            <input type="range" id="cdpTextSizeSlider" min="10" max="200" value="26">
                            <span id="cdpTextSizeValue">26px</span>
                        </div>
                    </div>
                    <div class="cdp-text-group">
                        <label>Text Color</label>
                        <div class="cdp-text-color-row">
                            <input type="color" id="cdpTextColorPicker" value="#000000">
                            <span id="cdpTextColorHex">#000000</span>
                        </div>
                    </div>
                    <div class="cdp-text-group">
                        <label>Text Style</label>
                        <div class="cdp-text-style-buttons">
                            <button type="button" class="cdp-text-style-btn" id="cdpTextBoldBtn">
                                <i class="fa-solid fa-bold"></i>
                            </button>
                            <button type="button" class="cdp-text-style-btn" id="cdpTextItalicBtn">
                                <i class="fa-solid fa-italic"></i>
                            </button>
                            <button type="button" class="cdp-text-style-btn" id="cdpTextUnderlineBtn">
                                <i class="fa-solid fa-underline"></i>
                            </button>
                        </div>
                    </div>
                    <div class="cdp-text-group">
                        <label>Transform</label>
                        <div class="cdp-text-transform-buttons">
                            <button type="button" class="cdp-text-transform-btn" data-action="rotate-right" title="Rotate Right">↻</button>
                            <button type="button" class="cdp-text-transform-btn" data-action="rotate-left" title="Rotate Left">↺</button>
                            <button type="button" class="cdp-text-transform-btn" data-action="rotate-90" title="Rotate 90°">90°</button>
                            <button type="button" class="cdp-text-transform-btn" data-action="flip-vertical" title="Flip Vertical">⇅</button>
                            <button type="button" class="cdp-text-transform-btn" data-action="flip-horizontal" title="Flip Horizontal">⇄</button>
                        </div>
                    </div>
                </div>
                <footer class="cdp-text-panel-footer">
                    <button type="button" class="cdp-text-btn cdp-text-btn--cancel">Cancel</button>
                    <button type="button" class="cdp-text-btn cdp-text-btn--apply">Add Text</button>
                </footer>
            </div>
        `;

        document.body.appendChild(textPanel);
        setupPanelEvents();
    }

    function setupPanelEvents() {
        const tutorialBtn = textPanel.querySelector('#cdpTextTutorial');
        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleTextTutorialClick);
        }

        textPanel.querySelector('.cdp-text-panel-close').addEventListener('click', closePanel);
        textPanel.querySelector('.cdp-text-btn--cancel').addEventListener('click', closePanel);
        textPanel.querySelector('.cdp-text-btn--apply').addEventListener('click', applyText);

        textPanel.querySelectorAll('.cdp-text-font-item').forEach(btn => {
            btn.addEventListener('click', function() {
                textPanel.querySelectorAll('.cdp-text-font-item').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                selectedFont = this.dataset.font;
                if (currentTextElement) {
                    currentTextElement.style.fontFamily = `'${selectedFont}', sans-serif`;
                }
            });
        });

        document.getElementById('cdpTextInput').addEventListener('input', function() {
            if (currentTextElement) {
                currentTextElement.textContent = this.value;
            }
        });

        document.getElementById('cdpTextSizeSlider').addEventListener('input', function() {
            document.getElementById('cdpTextSizeValue').textContent = this.value + 'px';
            if (currentTextElement) {
                currentTextElement.style.fontSize = this.value + 'px';
            }
        });

        document.getElementById('cdpTextColorPicker').addEventListener('input', function() {
            document.getElementById('cdpTextColorHex').textContent = this.value;
            if (currentTextElement) {
                currentTextElement.style.color = this.value;
            }
        });

        document.getElementById('cdpTextBoldBtn').addEventListener('click', function() {
            this.classList.toggle('active');
            if (currentTextElement) {
                currentTextElement.style.fontWeight = this.classList.contains('active') ? 'bold' : 'normal';
            }
        });

        document.getElementById('cdpTextItalicBtn').addEventListener('click', function() {
            this.classList.toggle('active');
            if (currentTextElement) {
                currentTextElement.style.fontStyle = this.classList.contains('active') ? 'italic' : 'normal';
            }
        });

        document.getElementById('cdpTextUnderlineBtn').addEventListener('click', function() {
            this.classList.toggle('active');
            if (currentTextElement) {
                currentTextElement.style.textDecoration = this.classList.contains('active') ? 'underline' : 'none';
            }
        });

        // Transform buttons
        textPanel.querySelectorAll('.cdp-text-transform-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!currentTextElement) return;
                
                const action = this.getAttribute('data-action');
                const currentTransform = currentTextElement.style.transform || '';
                
                // Parse current rotation and flips
                let rotation = 0;
                let scaleX = 1;
                let scaleY = 1;
                let translateX = null;
                let translateY = null;
                
                const rotateMatch = currentTransform.match(/rotate\((-?[\d.]+)deg\)/);
                if (rotateMatch) rotation = parseFloat(rotateMatch[1]);
                
                const scaleMatch = currentTransform.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
                if (scaleMatch) {
                    scaleX = parseFloat(scaleMatch[1]);
                    scaleY = parseFloat(scaleMatch[2]);
                }
                
                const translateMatch = currentTransform.match(/translate\(([^,]+),\s*([^)]+)\)/);
                if (translateMatch) {
                    translateX = translateMatch[1].trim();
                    translateY = translateMatch[2].trim();
                }
                
                // Apply transformation
                if (action === 'rotate-right') {
                    rotation += 0.5;
                } else if (action === 'rotate-left') {
                    rotation -= 0.5;
                } else if (action === 'rotate-90') {
                    rotation += 90;
                } else if (action === 'flip-horizontal') {
                    scaleX *= -1;
                } else if (action === 'flip-vertical') {
                    scaleY *= -1;
                }
                
                // Apply to text
                const translatePart = (translateX !== null && translateY !== null)
                    ? `translate(${translateX}, ${translateY}) `
                    : '';
                const newTransform = `${translatePart}rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`.trim();
                currentTextElement.style.transformOrigin = 'center';
                currentTextElement.style.transform = newTransform;
            });
        });
    }

    function showPanel(editMode, textEl, layerData) {
        if (!textPanel) createPanel();

        currentTextElement = textEl;
        currentLayerData = layerData;

        textPanel.setAttribute('data-visible', 'true');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'none';
            sidebar.style.opacity = '0.5';
        }

        textPanel.querySelector('.cdp-text-btn--apply').textContent = editMode ? 'Update' : 'Add Text';

        if (editMode && textEl && layerData) {
            document.getElementById('cdpTextInput').value = textEl.textContent;
            document.getElementById('cdpTextSizeSlider').value = layerData.fontSize;
            document.getElementById('cdpTextSizeValue').textContent = layerData.fontSize + 'px';
            document.getElementById('cdpTextColorPicker').value = layerData.color;
            document.getElementById('cdpTextColorHex').textContent = layerData.color;

            selectedFont = layerData.font;
            textPanel.querySelectorAll('.cdp-text-font-item').forEach(b => b.classList.remove('active'));
            const fontBtn = textPanel.querySelector(`[data-font="${layerData.font}"]`);
            if (fontBtn) fontBtn.classList.add('active');

            const isBold = textEl.style.fontWeight === 'bold' || textEl.style.fontWeight === '700';
            const isItalic = textEl.style.fontStyle === 'italic';
            const isUnderline = textEl.style.textDecoration.includes('underline');

            document.getElementById('cdpTextBoldBtn').classList.toggle('active', isBold);
            document.getElementById('cdpTextItalicBtn').classList.toggle('active', isItalic);
            document.getElementById('cdpTextUnderlineBtn').classList.toggle('active', isUnderline);
        } else {
            document.getElementById('cdpTextInput').value = 'Your Text';
            document.getElementById('cdpTextSizeSlider').value = 26;
            document.getElementById('cdpTextSizeValue').textContent = '26px';
            document.getElementById('cdpTextColorPicker').value = '#000000';
            document.getElementById('cdpTextColorHex').textContent = '#000000';

            selectedFont = 'Montserrat';
            textPanel.querySelectorAll('.cdp-text-font-item').forEach(b => b.classList.remove('active'));
            const first = textPanel.querySelector('[data-font="Montserrat"]');
            if (first) first.classList.add('active');

            textPanel.querySelectorAll('.cdp-text-style-btn').forEach(btn => btn.classList.remove('active'));
        }
    }

    function closePanel() {
        if (textPanel) textPanel.setAttribute('data-visible', 'false');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'auto';
            sidebar.style.opacity = '1';
        }

        currentTextElement = null;
        currentLayerData = null;

        if (typeof window.updateLayersList === 'function') {
            window.updateLayersList();
        }
    }

    function applyText() {
        if (currentTextElement && currentLayerData) {
            const textValue = document.getElementById('cdpTextInput').value;
            currentLayerData.name = `Text: ${textValue.substring(0, 12)}${textValue.length > 12 ? '...' : ''}`;
            currentLayerData.font = selectedFont;
            currentLayerData.fontSize = parseInt(document.getElementById('cdpTextSizeSlider').value);
            currentLayerData.color = document.getElementById('cdpTextColorPicker').value;
        } else {
            addText();
        }
        closePanel();
    }

    function addText() {
        const view = window.cdpState.currentView || 'front';
        const boxMap = { front: 'boxFront', back: 'boxBack', right: 'boxRight', left: 'boxLeft' };
        const printBox = document.getElementById(boxMap[view]);

        if (!printBox) return;

        const textValue = document.getElementById('cdpTextInput').value;
        const fontSize = document.getElementById('cdpTextSizeSlider').value;
        const color = document.getElementById('cdpTextColorPicker').value;
        const isBold = document.getElementById('cdpTextBoldBtn').classList.contains('active');
        const isItalic = document.getElementById('cdpTextItalicBtn').classList.contains('active');
        const isUnderline = document.getElementById('cdpTextUnderlineBtn').classList.contains('active');

        const textEl = document.createElement('div');
        textEl.className = 'cdp-text-element';
        textEl.textContent = textValue;
        textEl.id = 'text-' + Date.now();

        textEl.style.position = 'absolute';
        textEl.style.left = '50%';
        textEl.style.top = '50%';
        textEl.style.transform = 'translate(-50%, -50%)';
        textEl.style.transformOrigin = 'center';
        textEl.style.fontFamily = `'${selectedFont}', sans-serif`;
        textEl.style.fontSize = fontSize + 'px';
        textEl.style.color = color;
        textEl.style.fontStyle = isItalic ? 'italic' : 'normal';
        textEl.style.textDecoration = isUnderline ? 'underline' : 'none';
        textEl.style.cursor = 'grab';
        textEl.style.padding = '8px';
        textEl.style.whiteSpace = 'nowrap';
        textEl.style.userSelect = 'none';
        textEl.style.pointerEvents = 'auto';

        const layerName = `Text: ${textValue.substring(0, 20)}${textValue.length > 20 ? '...' : ''}`;
        const baseLayerData = {
            id: textEl.id,
            name: layerName,
            type: 'text',
            font: selectedFont,
            fontSize: parseInt(fontSize, 10),
            color: color,
            view: view,
            visible: true,
            locked: false,
            element: textEl,
            domId: textEl.id,
            textValue
        };

        printBox.appendChild(textEl);

        let runtimeLayer = baseLayerData;

        if (window.cdpLayers && window.cdpLayers.addLayer) {
            const layer = window.cdpLayers.addLayer({
                element: textEl,
                name: layerName,
                type: 'text',
                view: view
            });
            if (layer) {
                runtimeLayer = layer;
                runtimeLayer.name = layerName;
                runtimeLayer.font = selectedFont;
                runtimeLayer.fontSize = parseInt(fontSize, 10);
                runtimeLayer.color = color;
                runtimeLayer.textValue = textValue;
                runtimeLayer.domId = textEl.id;
                runtimeLayer.element = textEl;
                runtimeLayer.visible = true;
                runtimeLayer.locked = false;
            }
        } else {
            window.layersByView[view].push(baseLayerData);
            if (typeof window.updateLayersList === 'function') {
                window.updateLayersList();
            }
        }

        attachTextEvents(textEl, runtimeLayer);
        if (typeof window.reorderLayerZIndex === 'function') {
            window.reorderLayerZIndex();
        }
    }

    // ===========================
    // Expose function for duplicate
    // ===========================
    window.reattachTextEvents = function(textElement) {
        console.log('🔄 Reattaching events to duplicated text');
        
        // Find layer data
        const view = window.cdpState.currentView || 'front';
        const layerData = window.layersByView[view].find(l => l.element === textElement);
        
        if (layerData) {
            attachTextEvents(textElement, layerData);
            console.log('✅ Events reattached successfully');
        } else {
            console.error('❌ Layer data not found for text element');
        }
    };

    window.reattachTextEventsWithData = function(textElement, layerData) {
        console.log('🔄 Reattaching text events with data:', {
            locked: layerData.locked
        });
        attachTextEvents(textElement, layerData);
        console.log('✅ Text events attached!');
    };

    textBtn.addEventListener('click', () => showPanel(false, null, null));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && textPanel && textPanel.getAttribute('data-visible') === 'true') {
            closePanel();
        }
    });

    console.log("✅ Text system ready!");
});