// Js/print.js
document.addEventListener("DOMContentLoaded", () => {
  const shirtImage = document.getElementById("cdpShirtImage");
  const viewButtons = document.querySelectorAll(".cdp-view-btn");
  const layersViewLabel = document.getElementById("cdpLayersViewLabel");

  const boxes = {
    front: document.getElementById("boxFront"),
    back: document.getElementById("boxBack"),
    right: document.getElementById("boxRight"),
    left: document.getElementById("boxLeft"),
  };

  Object.entries(boxes).forEach(([view, el]) => {
    if (el) {
      el.dataset.view = view;
    }
  });

  // map ویو به شماره عکس داخل فولدر
  const viewIndex = {
    front: 1,
    back: 2,
    right: 3,
    left: 4,
  };

  const viewKeyMap = {
    "1": "front",
    "2": "back",
    "3": "right",
    "4": "left",
  };

  let currentView = shirtImage.dataset.view || "front";

  function updateView(newView) {
    if (!boxes[newView]) return;

    currentView = newView;
    shirtImage.dataset.view = newView;

    // دکمه فعال
    viewButtons.forEach((btn) => {
      const v = btn.dataset.view;
      if (v === newView) {
        btn.classList.add("cdp-view-btn--active");
      } else {
        btn.classList.remove("cdp-view-btn--active");
      }
    });

    // عنوان لایه‌ها
    layersViewLabel.textContent =
      newView.charAt(0).toUpperCase() + newView.slice(1);

    // فقط کادر مربوط به ویو فعال بماند
    Object.entries(boxes).forEach(([name, el]) => {
      if (name === newView) {
        el.classList.remove("cdp-print-box--hidden");
      } else {
        el.classList.add("cdp-print-box--hidden");
      }
    });

    // تصویر تیشرت بر اساس رنگ + ویو
    const colorFolder = shirtImage.dataset.colorFolder || "bianco";
    const idx = viewIndex[newView] || 1;

    shirtImage.src =
      `images/all photo tishirt/uomo/Maglietta uomo/${colorFolder}/${idx}.png`;
    
    // به‌روزرسانی window.cdpState برای سیستم لایه‌ها
    if (!window.cdpState) {
      window.cdpState = {};
    }
    window.cdpState.currentView = newView;
    
    // به‌روزرسانی لیست لایه‌ها
    if (typeof window.updateLayersList === 'function') {
      console.log('[print.js] Switching to view:', newView);
      window.updateLayersList();
    }
  }

  // کلیک روی دکمه‌های Front / Back / Right / Left
  viewButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const v = btn.dataset.view;
      updateView(v);
    });
  });

  // کلیدهای میانبر برای تعویض سریع نما
  document.addEventListener("keydown", (event) => {
    const targetTag = (event.target.tagName || "").toLowerCase();
    if (targetTag === "input" || targetTag === "textarea" || event.target.isContentEditable) {
      return;
    }

    const mappedView = viewKeyMap[event.key];
    if (!mappedView || mappedView === currentView) {
      return;
    }

    event.preventDefault();
    updateView(mappedView);
  });

  // تنظیم اولیه
  updateView(currentView);
});
