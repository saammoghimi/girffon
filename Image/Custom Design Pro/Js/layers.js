// ========================
// LAYER MANAGEMENT SYSTEM
// ========================

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

let currentSettingsLayerId = null;

// ========================
// LAYER SETTINGS MODAL
// ========================

function openLayerSettings(layerId) {
    console.log("Opening settings for layer:", layerId);
    const modal = document.getElementById('cdpLayerSettings');
    if (!modal) {
        console.error("Settings modal not found!");
        return;
    }

    currentSettingsLayerId = layerId;
    
    const currentView = window.cdpState.currentView || 'front';
    const layer = window.layersByView[currentView]?.find(l => l.id === layerId);
    
    if (!layer) {
        console.error("Layer not found:", layerId);
        return;
    }

    const opacitySlider = document.getElementById('cdpLayerOpacity');
    const opacityValue = document.getElementById('cdpLayerOpacityValue');
    
    if (opacitySlider && opacityValue && layer.element) {
        const currentOpacity = parseFloat(layer.element.style.opacity || '1') * 100;
        opacitySlider.value = currentOpacity;
        opacityValue.textContent = Math.round(currentOpacity) + '%';
    }

    modal.removeAttribute('hidden');
    console.log("Modal opened!");
}

function closeLayerSettings() {
    const modal = document.getElementById('cdpLayerSettings');
    if (modal) {
        modal.setAttribute('hidden', '');
    }
    currentSettingsLayerId = null;
}

function duplicateLayer() {
    if (!currentSettingsLayerId) {
        console.error("❌ No layer selected!");
        return;
    }

    const currentView = window.cdpState.currentView || 'front';
    const layers = window.layersByView[currentView];

    // --- RESTORED: addLayer and updateLayersList from cd-layers.js ---
    let layers = [];
    let activeLayerId = null;
    let settingsLayerId = null;
    let nextId = 1;

    function findLayer(id) {
        return layers.find((l) => l.id === id);
    }

    function setActiveLayer(id) {
        activeLayerId = id;
        const layersList = document.getElementById('cdpLayersList');
        if (!layersList) return;
        const items = layersList.querySelectorAll('.cdp-layer-item');
        items.forEach((li) => {
            const layerId = Number(li.dataset.layerId);

            // This file is intentionally left empty.
            // All layer management is handled by cd-layers.js only.
                l.element.style.display = l.visible ? '' : 'none';
            }
            eyeBtn.innerHTML = l.visible
                ? '<i class="fa-regular fa-eye"></i>'
                : '<i class="fa-regular fa-eye-slash"></i>';
        });
        lockBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const l = findLayer(layer.id);
            if (!l) return;
            l.locked = !l.locked;
            lockBtn.innerHTML = l.locked
                ? '<i class="fa-solid fa-lock"></i>'
                : '<i class="fa-solid fa-lock-open"></i>';
            if (l.element) {
                l.element.dataset.locked = l.locked ? 'true' : 'false';
            }
        });
        settingsBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            setActiveLayer(layer.id);
            openLayerSettings(layer.id);
        });
        return li;
    }

    function addLayer(options) {
        const layersList = document.getElementById('cdpLayersList');
        if (!layersList) {
            console.warn('[addLayer] No layersList found');
            return null;
        }
        const id = nextId++;
        const layer = {
            id,
            element: options.element || null,
            type: options.type || 'L',
            name: options.name || `Layer #${id}`,
            view: options.view || (document.getElementById('cdpShirtImage')?.dataset.view || 'front'),
            visible: true,
            locked: false,
            opacity: 1,
        };
        if (layer.element) {
            layer.element.style.opacity = '1';
        }
        layers.push(layer);
        if (!window.layersByView) {
            window.layersByView = { front: [], back: [], left: [], right: [] };
        }
        const view = layer.view || 'front';
        if (!window.layersByView[view]) {
            window.layersByView[view] = [];
        }
        window.layersByView[view].push(layer);
        console.log('[addLayer] Added layer:', layer, 'All layers:', layers, 'layersByView:', window.layersByView);
        const currentView = window.cdpState?.currentView || 'front';
        if (layer.view === currentView) {
            const li = createLayerListItem(layer);
            layersList.prepend(li);
            setActiveLayer(id);
        }
        return layer;
    }

    function updateLayersList() {
        const layersList = document.getElementById('cdpLayersList');
        if (!layersList) {
            console.warn('[updateLayersList] No layersList found');
            return;
        }
        layersList.innerHTML = '';
        const currentView = window.cdpState?.currentView || 'front';
        const viewLayers = layers.filter(l => l.view === currentView);
        viewLayers.reverse().forEach(layer => {
            const li = createLayerListItem(layer);
            layersList.appendChild(li);
        });
        const viewLabel = document.getElementById('cdpLayersViewLabel');
        if (viewLabel) {
            viewLabel.textContent = currentView.charAt(0).toUpperCase() + currentView.slice(1);
        }
        console.log(`[updateLayersList] For view: ${currentView}, count: ${viewLayers.length}, layers:`, viewLayers);
    }

    function reorderLayerZIndex() {
        const currentView = window.cdpState?.currentView || 'front';
        const viewLayers = layers.filter(l => l.view === currentView);
        viewLayers.forEach((layer, index) => {
            if (layer.element) {
                layer.element.style.zIndex = 1000 + index;
            }
        });
    }

    function reorderLayersFromDOM() {
        const currentView = window.cdpState?.currentView || 'front';
        const layersList = document.getElementById('cdpLayersList');
        const items = Array.from(layersList.querySelectorAll('.cdp-layer-item'));
        const orderedIds = items.reverse().map(item => Number(item.dataset.layerId));
        const viewLayers = layers.filter(l => l.view === currentView);
        const otherLayers = layers.filter(l => l.view !== currentView);
        const reorderedViewLayers = orderedIds
            .map(id => viewLayers.find(l => l.id === id))
            .filter(Boolean);
        layers = [...otherLayers, ...reorderedViewLayers];
        reorderLayerZIndex();
        console.log('✅ Layers reordered from DOM');
    }

    function openLayerSettings(id) {
        settingsLayerId = id;
        const modal = document.getElementById('cdpLayerSettings');
        if (!modal) return;
        const layer = findLayer(id);
        if (!layer) return;
        const opacitySlider = document.getElementById('cdpLayerOpacity');
        const opacityValue = document.getElementById('cdpLayerOpacityValue');
        if (opacitySlider && opacityValue && layer.element) {
            const currentOpacity = parseFloat(layer.element.style.opacity || '1') * 100;
            opacitySlider.value = currentOpacity;
            opacityValue.textContent = Math.round(currentOpacity) + '%';
        }
        modal.removeAttribute('hidden');
    }

    function closeLayerSettings() {
        const modal = document.getElementById('cdpLayerSettings');
        if (modal) {
            modal.setAttribute('hidden', '');
        }
        settingsLayerId = null;
    }

    function duplicateLayer() {
        if (!settingsLayerId) return;
        const layer = findLayer(settingsLayerId);
        if (!layer) return;
        let newElement = null;
        if (layer.element && layer.element.cloneNode) {
            newElement = layer.element.cloneNode(true);
            const newId = layer.type + '-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            newElement.id = newId;
            if (newElement.dataset) {
                delete newElement.dataset.eventsAttached;
                delete newElement.dataset.locked;
            }
            if (newElement.style) {
                const top = parseInt(layer.element.style.top || '0', 10) || 0;
                const left = parseInt(layer.element.style.left || '0', 10) || 0;
                if (layer.type === 'fill') {
                    newElement.style.top = top + 'px';
                    newElement.style.left = left + 'px';
                } else {
                    newElement.style.top = top + 10 + 'px';
                    newElement.style.left = left + 10 + 'px';
                }
            }
            if (layer.element.parentElement) {
                layer.element.parentElement.appendChild(newElement);
            }
        }
        const newLayer = addLayer({
            element: newElement,
            type: layer.type,
            name: layer.name + ' copy',
            view: layer.view,
        });
        if (newLayer && layer) {
            if (layer.size) newLayer.size = layer.size;
            if (layer.width) newLayer.width = layer.width;
            if (layer.height) newLayer.height = layer.height;
            if (layer.iconType) newLayer.iconType = layer.iconType;
            if (layer.code) newLayer.code = layer.code;
            if (layer.shapeId) newLayer.shapeId = layer.shapeId;
        }
        if (newElement && newLayer) {
            if (layer.type === 'icon' && typeof window.reattachIconEventsWithData === 'function') {
                window.reattachIconEventsWithData(newElement, newLayer);
            } else if (layer.type === 'flag' && typeof window.reattachFlagEventsWithData === 'function') {
                window.reattachFlagEventsWithData(newElement, newLayer);
            } else if (layer.type === 'shape' && typeof window.reattachShapeEventsWithData === 'function') {
                window.reattachShapeEventsWithData(newElement, newLayer);
            } else if (layer.type === 'text' && typeof window.reattachTextEventsWithData === 'function') {
                window.reattachTextEventsWithData(newElement, newLayer);
            } else if (layer.type === 'upload' && typeof window.reattachUploadEventsWithData === 'function') {
                window.reattachUploadEventsWithData(newElement, newLayer);
            } else if (layer.type === 'design' && typeof window.reattachAddDesignEvents === 'function') {
                window.reattachAddDesignEvents(newElement);
            }
        }
        closeLayerSettings();
    }

    function deleteLayer() {
        if (!settingsLayerId) return;
        const layer = findLayer(settingsLayerId);
        if (!layer) return;
        if (layer.element && layer.element.parentElement) {
            layer.element.parentElement.removeChild(layer.element);
        }
        layers = layers.filter((l) => l.id !== layer.id);
        if (window.layersByView && window.layersByView[layer.view]) {
            window.layersByView[layer.view] = window.layersByView[layer.view].filter(
                (l) => l.id !== layer.id
            );
        }
        const item = document.getElementById('cdpLayersList').querySelector(
            `.cdp-layer-item[data-layer-id="${layer.id}"]`
        );
        if (item) item.remove();
        settingsLayerId = null;
        activeLayerId = null;
        closeLayerSettings();
    }

    function handleOpacityChange(value) {
        if (!settingsLayerId) return;
        const layer = findLayer(settingsLayerId);
        if (!layer) return;
        const alpha = value / 100;
        layer.opacity = alpha;
        const opacityValue = document.getElementById('cdpLayerOpacityValue');
        if (opacityValue) {
            opacityValue.textContent = Math.round(value) + '%';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const closeButtons = document.querySelectorAll('[data-layer-close]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', closeLayerSettings);
        });
        const duplicateBtn = document.querySelector('[data-layer-duplicate]');
        if (duplicateBtn) {
            duplicateBtn.addEventListener('click', duplicateLayer);
        }
        const deleteBtn = document.querySelector('[data-layer-delete]');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', deleteLayer);
        }
        const opacitySlider = document.getElementById('cdpLayerOpacity');
        if (opacitySlider) {
            opacitySlider.addEventListener('input', function() {
                handleOpacityChange(this.value);
            });
        }
        console.log('✅ Layers system initialized!');
    });

    window.openLayerSettings = openLayerSettings;
    window.closeLayerSettings = closeLayerSettings;
    window.updateLayersList = updateLayersList;
    window.reorderLayerZIndex = reorderLayerZIndex;