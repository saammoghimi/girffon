// Js/layers.js
// --- Robust layers list querying ---
function getLayersList() {
  return document.getElementById("cdpLayersList");
}

const VIEW_SEQUENCE = ["front", "back", "right", "left"];

let layers = []; // {id, element, type, name, view, visible, locked, opacity}
let activeLayerId = null;
let settingsLayerId = null;
let nextId = 1;

document.addEventListener("DOMContentLoaded", () => {

  const layerSettingsPanel = document.getElementById("cdpLayerSettings");
  const layerSettingsCloseBtn = layerSettingsPanel.querySelector("[data-layer-close]");
  const opacityRange = document.getElementById("cdpLayerOpacity");
  const opacityValueLabel = document.getElementById("cdpLayerOpacityValue");
  const duplicateBtn = layerSettingsPanel.querySelector("[data-layer-duplicate]");
  const deleteBtn = layerSettingsPanel.querySelector("[data-layer-delete]");

  // حذف تعریف تکراری متغیرها؛ فقط از نسخه سراسری استفاده شود

  /* ---------- کمک‌ها ---------- */

  function findLayer(id) {
    return layers.find((l) => l.id === id);
  }

  function setActiveLayer(id) {
    activeLayerId = id;
    const list = getLayersList();
    if (!list) return;
    const items = list.querySelectorAll(".cdp-layer-item");
    items.forEach((li) => {
      const layerId = Number(li.dataset.layerId);
      if (layerId === id) {
        li.classList.add("cdp-layer-item--active");
      } else {
        li.classList.remove("cdp-layer-item--active");
      }
    });
  }

  function updateOpacityUI(layer) {
    if (!opacityRange || !opacityValueLabel || !layer) return;
    opacityRange.value = layer.opacity * 100;
    opacityValueLabel.textContent = Math.round(layer.opacity * 100) + "%";
  }

  function openLayerSettings(id) {
    const layer = findLayer(id);
    if (!layer) return;
    settingsLayerId = id;
    updateOpacityUI(layer);
    layerSettingsPanel.removeAttribute("hidden");
    layerSettingsPanel.setAttribute("data-visible", "true");
    layerSettingsPanel.style.zIndex = 9999;
    layerSettingsPanel.style.display = "";
  }

  function closeLayerSettings() {
    layerSettingsPanel.setAttribute("data-visible", "false");
    layerSettingsPanel.setAttribute("hidden", "");
    layerSettingsPanel.style.display = "none";
    settingsLayerId = null;
  }

  layerSettingsCloseBtn.addEventListener("click", closeLayerSettings);

  /* ---------- ساخت آیتم در لیست ---------- */

  function createLayerListItem(layer) {
    const li = document.createElement("li");
    li.className = "cdp-layer-item";
    li.dataset.layerId = layer.id;
    li.draggable = true;
    li.innerHTML = `
      <div class="cdp-layer-main">
        <div class="cdp-layer-type">${layer.type}</div>
        <div class="cdp-layer-name">${layer.name}</div>
      </div>
      <div class="cdp-layer-actions">
        <button class="cdp-layer-btn cdp-layer-btn--eye" type="button">
          <i class="fa-regular fa-eye"></i>
        </button>
        <button class="cdp-layer-btn cdp-layer-btn--lock" type="button">
          <i class="fa-solid fa-lock-open"></i>
        </button>
        <button class="cdp-layer-btn cdp-layer-btn--settings" type="button">
          <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
      </div>
    `;

    // Drag & Drop برای تغییر ترتیب لایه‌ها
    li.addEventListener("dragstart", (e) => {
      li.classList.add("cdp-layer-item--dragging");
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/html", li.innerHTML);
    });

    li.addEventListener("dragend", (e) => {
      li.classList.remove("cdp-layer-item--dragging");
    });

    li.addEventListener("dragover", (e) => {
      e.preventDefault();
      const list = getLayersList();
      if (!list) return;
      const draggingItem = list.querySelector(".cdp-layer-item--dragging");
      if (!draggingItem || draggingItem === li) return;
      const rect = li.getBoundingClientRect();
      const midpoint = rect.top + rect.height / 2;
      if (e.clientY < midpoint) {
        li.parentNode.insertBefore(draggingItem, li);
      } else {
        li.parentNode.insertBefore(draggingItem, li.nextSibling);
      }
    });

    li.addEventListener("drop", (e) => {
      e.preventDefault();
      reorderLayersFromDOM();
    });

    // انتخاب لایه با کلیک روی ردیف
    li.addEventListener("click", (e) => {
      // اگر روی دکمه کلیک شده، اجازه بده همون رو هندل کنیم
      if ((e.target.closest && e.target.closest("button"))) return;
      setActiveLayer(layer.id);
    });

    const eyeBtn = li.querySelector(".cdp-layer-btn--eye");
    const lockBtn = li.querySelector(".cdp-layer-btn--lock");
    const settingsBtn = li.querySelector(".cdp-layer-btn--settings");

    // چشم
    eyeBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const l = findLayer(layer.id);
      if (!l) return;
      l.visible = !l.visible;

      if (l.element) {
        l.element.style.display = l.visible ? "" : "none";
      }

      eyeBtn.innerHTML = l.visible
        ? '<i class="fa-regular fa-eye"></i>'
        : '<i class="fa-regular fa-eye-slash"></i>';
    });

    // قفل
    lockBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const l = findLayer(layer.id);
      if (!l) return;
      l.locked = !l.locked;

      lockBtn.innerHTML = l.locked
        ? '<i class="fa-solid fa-lock"></i>'
        : '<i class="fa-solid fa-lock-open"></i>';

      if (l.element) {
        l.element.dataset.locked = l.locked ? "true" : "false";
      }
    });

    // تنظیمات
    settingsBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      setActiveLayer(layer.id);
      openLayerSettings(layer.id);
    });

    return li;
  }

  /* ---------- API عمومی برای اضافه‌کردن لایه ---------- */

  function addLayer(options) {
    const list = getLayersList();
    if (!list) {
      console.warn('[cd-layers.js] No layersList found');
      return null;
    }
    const id = nextId++;
    const layer = {
      id,
      element: options.element || null,
      type: options.type || "L",
      name: options.name || `Layer #${id}`,
      view: options.view || (document.getElementById("cdpShirtImage")?.dataset.view || "front"),
      visible: true,
      locked: false,
      opacity: 1,
    };
    if (layer.element) {
      layer.element.style.opacity = "1";
      layer.element.dataset.layerId = String(id);
      layer.element.dataset.layerType = layer.type || "layer";
      layer.element.dataset.layerName = layer.name;
      layer.element.dataset.layerView = layer.view;
      if (!layer.element.classList.contains("cdp-design-element")) {
        layer.element.classList.add("cdp-design-element");
      }
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
    console.log('[cd-layers.js][addLayer] Added:', layer, 'All layers:', layers, 'layersByView:', window.layersByView);
    // Always update the list after adding
    updateLayersList();
    return layer;
  }

  /* ---------- تنظیمات: Opacity / Duplicate / Delete ---------- */

  if (duplicateBtn) {
    duplicateBtn.disabled = true;
    duplicateBtn.addEventListener("click", () => {
      if (duplicateBtn.disabled) return;
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
          const top = parseInt(layer.element.style.top || "0", 10) || 0;
          const left = parseInt(layer.element.style.left || "0", 10) || 0;
          if (layer.type === 'fill') {
            newElement.style.top = top + "px";
            newElement.style.left = left + "px";
          } else {
            newElement.style.top = top + 10 + "px";
            newElement.style.left = left + 10 + "px";
          }
        }
        if (layer.element.parentElement) {
          layer.element.parentElement.appendChild(newElement);
        }
      }
      const newLayer = addLayer({
        element: newElement,
        type: layer.type,
        name: layer.name + " copy",
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
    });
  }

  if (deleteBtn) {
    deleteBtn.disabled = true;
    deleteBtn.addEventListener("click", () => {
      if (deleteBtn.disabled) return;
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
      const list = getLayersList();
      if (list) {
        const item = list.querySelector(
          `.cdp-layer-item[data-layer-id="${layer.id}"]`
        );
        if (item) item.remove();
      }
      settingsLayerId = null;
      activeLayerId = null;
      closeLayerSettings();
    });
  }
  // Enable/disable Duplicate/Delete based on layer selection
  function updateLayerSettingsButtons() {
    if (duplicateBtn) duplicateBtn.disabled = !settingsLayerId;
    if (deleteBtn) deleteBtn.disabled = !settingsLayerId;
  }

  // Patch open/close to update button state
  const _openLayerSettings = openLayerSettings;
  openLayerSettings = function(id) {
    _openLayerSettings(id);
    updateLayerSettingsButtons();
  };
  const _closeLayerSettings = closeLayerSettings;
  closeLayerSettings = function() {
    _closeLayerSettings();
    updateLayerSettingsButtons();
  };
  // Initial state
  updateLayerSettingsButtons();

  if (opacityRange) {
    opacityRange.addEventListener("input", () => {
      if (!settingsLayerId) return;
      const layer = findLayer(settingsLayerId);
      if (!layer) return;

      const value = Number(opacityRange.value) || 100;
      const alpha = value / 100;

      layer.opacity = alpha;
      if (layer.element) {
        layer.element.style.opacity = String(alpha);
      }
      opacityValueLabel.textContent = Math.round(value) + "%";
    });
  }

  // توابع برای سازگاری با کدهای قدیمی
  function updateLayersList() {
    const list = getLayersList();
    if (!list) {
      console.warn('[cd-layers.js][updateLayersList] No layersList found');
      return;
    }
    list.innerHTML = '';
    const currentView = window.cdpState?.currentView || 'front';
    const viewLayers = layers.filter(l => l.view === currentView);
    viewLayers.reverse().forEach(layer => {
      const li = createLayerListItem(layer);
      list.appendChild(li);
    });
    console.log(`[cd-layers.js][updateLayersList] For view: ${currentView}, count: ${viewLayers.length}, layers:`, viewLayers, 'All layers:', layers);
  }

  function reorderLayerZIndex() {
    // مرتب‌سازی z-index بر اساس ترتیب لایه‌ها
    const currentView = window.cdpState?.currentView || 'front';
    const viewLayers = layers.filter(l => l.view === currentView);
    
    viewLayers.forEach((layer, index) => {
      if (layer.element) {
        const zIndex = 1000 + index;
        layer.zIndex = zIndex;
        layer.element.style.zIndex = zIndex;
      }
    });
  }

  function reorderLayersFromDOM() {
    const list = getLayersList();
    if (!list) return;
    const currentView = window.cdpState?.currentView || 'front';
    const items = Array.from(list.querySelectorAll('.cdp-layer-item'));
    const orderedIds = items.reverse().map(item => Number(item.dataset.layerId));
    const viewLayers = layers.filter(l => l.view === currentView);
    const otherLayers = layers.filter(l => l.view !== currentView);
    const reorderedViewLayers = orderedIds
      .map(id => viewLayers.find(l => l.id === id))
      .filter(Boolean);
    layers = [...otherLayers, ...reorderedViewLayers];
    if (window.layersByView && window.layersByView[currentView]) {
      window.layersByView[currentView] = reorderedViewLayers.slice();
    }
    reorderLayerZIndex();
    console.log('✅ Layers reordered from DOM');
  }

  // API جهانی برای استفاده بعدی (Upload / Text / Shape / Flag ...)
  function inferLayerTypeFromElement(element) {
    if (!element || !element.classList) return "layer";
    const cls = Array.from(element.classList);
    if (cls.includes("cdp-text-element")) return "text";
    if (cls.includes("cdp-icon-element")) return "icon";
    if (cls.includes("cdp-flag-element")) return "flag";
    if (cls.includes("cdp-shape-element")) return "shape";
    if (cls.includes("cdp-layer-fill")) return "fill";
    if (cls.includes("cdp-uploaded-image")) return "upload";
    if (cls.includes("cdp-design-element")) return element.dataset?.layerType || "design";
    return element.dataset?.layerType || "layer";
  }

  function defaultLayerName(type, id) {
    const label = (type || "Layer").charAt(0).toUpperCase() + (type || "layer").slice(1);
    return `${label} #${id}`;
  }

  function reattachLayerHandlers(layer) {
    if (!layer || !layer.element) return;
    switch (layer.type) {
      case "icon":
        if (typeof window.reattachIconEventsWithData === "function") {
          window.reattachIconEventsWithData(layer.element, layer);
        }
        break;
      case "flag":
        if (typeof window.reattachFlagEventsWithData === "function") {
          window.reattachFlagEventsWithData(layer.element, layer);
        }
        break;
      case "shape":
        if (typeof window.reattachShapeEventsWithData === "function") {
          window.reattachShapeEventsWithData(layer.element, layer);
        }
        break;
      case "text":
        if (typeof window.reattachTextEventsWithData === "function") {
          window.reattachTextEventsWithData(layer.element, layer);
        }
        break;
      case "upload":
        if (typeof window.reattachUploadEventsWithData === "function") {
          window.reattachUploadEventsWithData(layer.element, layer);
        }
        break;
      case "design":
        if (typeof window.reattachAddDesignEvents === "function") {
          window.reattachAddDesignEvents(layer.element);
        }
        break;
      default:
        break;
    }
  }

  function normalizeViewName(view, fallbackIndex) {
    if (view) return view;
    return VIEW_SEQUENCE[fallbackIndex] || "front";
  }

  function refreshLayersFromDOM(savedMeta = []) {
    const metaMap = new Map();
    const fallbackMeta = [];
    (savedMeta || []).forEach((meta) => {
      if (!meta) return;
      const key = meta.layerId ?? meta.id;
      if (key !== undefined && key !== null) {
        metaMap.set(String(key), meta);
      } else {
        fallbackMeta.push(meta);
      }
    });

    layers = [];
    window.layersByView = { front: [], back: [], left: [], right: [] };
    activeLayerId = null;
    settingsLayerId = null;

    const boxes = Array.from(document.querySelectorAll(".cdp-print-box"));
    let maxId = 0;

    boxes.forEach((box, boxIndex) => {
      const view = normalizeViewName(box?.dataset?.view, boxIndex);
      Array.from(box.children || []).forEach((child) => {
        if (!(child instanceof Element)) return;
        if (child.dataset?.layerIgnore === "true") return;

        const datasetId = child.dataset?.layerId;
        const meta = datasetId ? metaMap.get(String(datasetId)) : null;

        let layerId = meta?.id;
        if (layerId === undefined || layerId === null) {
          layerId = datasetId ? Number(datasetId) : null;
        }
        if (layerId === null || Number.isNaN(layerId)) {
          const fallback = fallbackMeta.shift();
          if (fallback && fallback.id !== undefined) {
            layerId = fallback.id;
          } else {
            layerId = nextId++;
          }
        }

        maxId = Math.max(maxId, layerId);
        child.dataset.layerId = String(layerId);

        const type = meta?.type || child.dataset?.layerType || inferLayerTypeFromElement(child);
        const name = meta?.name || child.dataset?.layerName || defaultLayerName(type, layerId);
        const opacity = typeof meta?.opacity === "number"
          ? meta.opacity
          : (child.style.opacity ? Number(child.style.opacity) : 1);
        const locked = typeof meta?.locked === "boolean" ? meta.locked : child.dataset?.locked === "true";
        const visible = child.style.display !== "none";

        child.dataset.layerType = type;
        child.dataset.layerName = name;
        child.dataset.layerView = view;
        child.dataset.locked = locked ? "true" : "false";
        child.style.opacity = String(opacity);
        if (!child.classList.contains("cdp-design-element")) {
          child.classList.add("cdp-design-element");
        }

        const layer = {
          id: layerId,
          element: child,
          type,
          name,
          view,
          visible,
          locked,
          opacity,
          originalSrc: meta?.originalSrc || child.dataset?.originalSrc || "",
          optimizedSrc: meta?.optimizedSrc || child.dataset?.optimizedSrc || child.querySelector?.("img")?.currentSrc || child.querySelector?.("img")?.src || "",
          uploadName: meta?.uploadName || child.dataset?.uploadName || "",
          uploadType: meta?.uploadType || child.dataset?.uploadType || "",
          width: meta?.width ?? null,
          height: meta?.height ?? null,
          transform: meta?.transform || null,
        };

        layers.push(layer);
        if (!window.layersByView[view]) {
          window.layersByView[view] = [];
        }
        window.layersByView[view].push(layer);
        reattachLayerHandlers(layer);
      });
    });

    nextId = Math.max(maxId + 1, nextId);
    updateLayersList();
    reorderLayerZIndex();
    updateLayerSettingsButtons();
  }

  function clearAllLayers() {
    layers = [];
    window.layersByView = { front: [], back: [], left: [], right: [] };
    activeLayerId = null;
    settingsLayerId = null;
    nextId = 1;
    updateLayersList();
    updateLayerSettingsButtons();
  }

  window.cdpLayers = {
    addLayer,
    getLayers: () => layers.slice(),
    getActiveLayerId: () => activeLayerId,
    refreshFromDOM: refreshLayersFromDOM,
    clearAll: clearAllLayers,
  };

  // Export توابع برای سازگاری با سیستم قدیمی
  window.openLayerSettings = openLayerSettings;
  window.closeLayerSettings = closeLayerSettings;
  window.updateLayersList = updateLayersList;
  window.reorderLayerZIndex = reorderLayerZIndex;
  window.refreshLayers = refreshLayersFromDOM;

  // Force update after DOMContentLoaded
  setTimeout(() => {
    updateLayersList();
  }, 200);
});

