// =========================
// Fill Color System
// =========================

document.addEventListener('DOMContentLoaded', function() {
    console.log("🎨 fill.js loaded");

    const fillBtn = document.querySelector('[data-tool="Fill"]');
    const FALLBACK_FILL_TUTORIAL_URL = 'https://www.youtube.com/watch?v=H9Yfk6t_hHc';
    let fillModal = null;
    let selectedColor = null;
    let currentView = "front";
    let fillTutorialToastTimeout = null;

    if (!fillBtn) {
        console.error("❌ Fill button not found");
        return;
    }

    // =========================
    // T-Shirt Colors (24 colors)
    // =========================

    const tshirtColors = [
        { name: "White", hex: "#ffffff" },
        { name: "Black", hex: "#000000" },
        { name: "Navy", hex: "#001f3f" },
        { name: "Royal Blue", hex: "#1d4ed8" },
        { name: "Carolina Blue", hex: "#60a5fa" },
        { name: "Red", hex: "#ef4444" },
        { name: "Pink", hex: "#fb7185" },
        { name: "Yellow", hex: "#facc15" },
        { name: "Kelly Green", hex: "#22c55e" },
        { name: "Military Green", hex: "#4d7c0f" },
        { name: "Mint", hex: "#6ee7b7" },
        { name: "Anthracite", hex: "#374151" },
        { name: "Graphite Melange", hex: "#4b5563" },
        { name: "Grey Melange", hex: "#9ca3af" },
        { name: "Blue Grey", hex: "#94a3b8" },
        { name: "Lilac Grey", hex: "#a855f7" },
        { name: "Sand Beige", hex: "#e5d0aa" },
        { name: "Brick", hex: "#b45309" },
        { name: "Orange", hex: "#f97316" },
        { name: "Purple", hex: "#9333ea" },
        { name: "Brown", hex: "#78350f" },
        { name: "Teal", hex: "#14b8a6" },
        { name: "Sky Blue", hex: "#38bdf8" },
        { name: "Coral", hex: "#fb923c" }
    ];

    function handleFillTutorialClick() {
        if (typeof window.cdpFillTutorialHandler === 'function') {
            try {
                window.cdpFillTutorialHandler();
                return;
            } catch (err) {
                console.error('Fill tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpFillTutorialUrl || FALLBACK_FILL_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showFillTutorialToast('🎬 Fill tutorial coming soon');
    }

    function showFillTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpFillTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpFillTutorialToast';
            toast.className = 'cdp-fill-tutorial-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (fillTutorialToastTimeout) {
            clearTimeout(fillTutorialToastTimeout);
        }

        fillTutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2800);
    }

    // Initialize global state
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

    if (typeof window.cdpFillTutorialUrl !== 'string' || !window.cdpFillTutorialUrl.trim()) {
        window.cdpFillTutorialUrl = FALLBACK_FILL_TUTORIAL_URL;
    }

    // =========================
    // Track Current View
    // =========================

    function trackViewChanges() {
        const viewBtns = document.querySelectorAll('[data-view]');
        viewBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                currentView = btn.dataset.view;
                window.cdpState.currentView = currentView;
                if (typeof window.updateLayersList === 'function') {
                    window.updateLayersList();
                }
            });
        });
    }

    // =========================
    // Create Modal
    // =========================

    function createFillModal() {
        if (fillModal) return;

        fillModal = document.createElement("div");
        fillModal.id = "cdpFillModal";
        fillModal.className = "cdp-fill-modal";

        let colorsHTML = '';
        tshirtColors.forEach(color => {
            const borderStyle = color.hex === "#ffffff" ? 'border-color:#e5e7eb;' : '';
            colorsHTML += `
                <button class="cdp-fill-color-btn" data-color="${color.hex}" data-name="${color.name}">
                    <div class="cdp-fill-color-dot" style="background:${color.hex};${borderStyle}"></div>
                    <span class="cdp-fill-color-name">${color.name}</span>
                </button>
            `;
        });

        fillModal.innerHTML = `
            <div class="cdp-fill-container">
                <header class="cdp-fill-header">
                    <h3><i class="fa-solid fa-fill-drip"></i> Fill Color</h3>
                    <div class="cdp-fill-header-actions">
                        <button type="button" id="cdpFillTutorial" class="cdp-fill-round-btn cdp-fill-help" title="Tutorial">
                            <i class="fa-regular fa-circle-question"></i>
                        </button>
                        <button type="button" class="cdp-fill-close-btn cdp-fill-round-btn" title="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </header>
                
                <div class="cdp-fill-body">
                    <div class="cdp-fill-colors">
                        ${colorsHTML}
                    </div>
                    
                    <div class="cdp-fill-custom">
                        <label class="cdp-fill-custom-label">Custom Color</label>
                        <input type="color" class="cdp-fill-color-input" value="#3b82f6">
                    </div>
                </div>
                
                <footer class="cdp-fill-footer">
                    <button type="button" class="cdp-fill-btn cdp-fill-btn--secondary" data-fill-cancel>
                        Cancel
                    </button>
                    <button type="button" class="cdp-fill-btn cdp-fill-btn--primary" data-fill-apply disabled>
                        Apply
                    </button>
                </footer>
            </div>
        `;

        document.body.appendChild(fillModal);

        // Event listeners
        const closeBtn = fillModal.querySelector(".cdp-fill-close-btn");
        const cancelBtn = fillModal.querySelector("[data-fill-cancel]");
        const applyBtn = fillModal.querySelector("[data-fill-apply]");
        const colorBtns = fillModal.querySelectorAll(".cdp-fill-color-btn");
        const customInput = fillModal.querySelector(".cdp-fill-color-input");
        const tutorialBtn = fillModal.querySelector('#cdpFillTutorial');

        closeBtn.addEventListener("click", closeFillModal);
        cancelBtn.addEventListener("click", closeFillModal);
        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleFillTutorialClick);
        }

        colorBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                colorBtns.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                selectedColor = btn.dataset.color;
                applyBtn.disabled = false;
            });
        });

        customInput.addEventListener("change", (e) => {
            colorBtns.forEach(b => b.classList.remove("active"));
            selectedColor = e.target.value;
            applyBtn.disabled = false;
        });

        applyBtn.addEventListener("click", applyFillColor);
    }

    function applyFillColor() {
        if (!selectedColor) return;

        const activeBox = document.querySelector('.cdp-print-box:not(.cdp-print-box--hidden)');
        if (!activeBox) {
            alert("Please select a print area first!");
            return;
        }

        const fillLayer = document.createElement('div');
        fillLayer.className = 'cdp-layer-fill';
        fillLayer.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: ${selectedColor};
            pointer-events: none;
            z-index: 0;
        `;

        const layerId = 'fill-' + Date.now();
        fillLayer.id = layerId;
        fillLayer.dataset.layerType = 'fill';
        fillLayer.dataset.layerColor = selectedColor;

        activeBox.appendChild(fillLayer);

        const layerData = {
            id: layerId,
            name: 'Fill Color',
            type: 'fill',
            color: selectedColor,
            view: currentView,
            visible: true,
            locked: false,
            element: fillLayer
        };

        // استفاده از API مرکزی
        if (window.cdpLayers && window.cdpLayers.addLayer) {
            const layer = window.cdpLayers.addLayer({
                element: fillLayer,
                name: `Fill: ${selectedColor}`,
                type: 'fill',
                view: currentView
            });
            if (layer) {
                layer.fillColor = selectedColor;
            }
            console.log("✅ Fill layer added via cdpLayers.addLayer");
        } else {
            // fallback
            window.layersByView[currentView].push(layerData);
            if (typeof window.updateLayersList === 'function') {
                window.updateLayersList();
            }
            console.log("✅ Fill layer added via fallback");
        }
        
        // بستن پنل بلافاصله بعد از Apply
        closeFillModal();
        selectedColor = null;
    }

    // =========================
    // Show/Close Modal
    // =========================

    function showFillModal() {
        createFillModal();
        fillModal.setAttribute('data-visible', 'true');
    }

    function closeFillModal() {
        if (fillModal) {
            fillModal.removeAttribute('data-visible');
            selectedColor = null;
            
            const applyBtn = fillModal.querySelector("[data-fill-apply]");
            if (applyBtn) applyBtn.disabled = true;
            
            fillModal.querySelectorAll(".cdp-fill-color-btn").forEach(b => 
                b.classList.remove("active")
            );
        }
    }

    fillBtn.addEventListener("click", showFillModal);

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && fillModal?.getAttribute('data-visible')) {
            closeFillModal();
        }
    });

    trackViewChanges();

    console.log("✅ Fill system ready!");
});