// ================================
//  CustomDesignPro â€“ Color Control
// ================================

// Ø¯Ú©Ù…Ù‡ Ùˆ Ù…Ù‚Ø§Ø¯ÛŒØ±
const colorBtn = document.getElementById("cdpColorBtn");
const colorModal = document.getElementById("cdpColorModal");
const colorBackdrop = document.getElementById("cdpColorModalBackdrop");
const colorCloseX = document.getElementById("cdpColorModalClose");
const colorCloseBottom = document.getElementById("cdpColorCloseBottom");
const colorName = document.getElementById("cdpColorName");
const colorOptions = document.querySelectorAll(".cdp-color-option");
const colorCircle = document.querySelector(".cdp-toggle-knob");

// Ø¹Ú©Ø³ ØªÛŒØ´Ø±Øª
const shirtImg = document.getElementById("cdpShirtImage");
const DEFAULT_PRODUCT_IMAGE_BASE = "images/Products/Men/Maglietta premium uomo";

// Ø¯Ú©Ù…Ù‡â€ŒÙ‡Ø§ÛŒ ÙˆÛŒÙˆ
const viewBtns = document.querySelectorAll(".cdp-view-btn");

// Ø°Ø®ÛŒØ±Ù‡ Ø±Ù†Ú¯ Ø§Ù†ØªØ®Ø§Ø¨â€ŒØ´Ø¯Ù‡
let selectedColorFolder = "bianco";
let selectedColorName = "White";

// -------------------------------------
// Ø¨Ø§Ø² Ú©Ø±Ø¯Ù† Ù…ÙˆØ¯Ø§Ù„ Ø±Ù†Ú¯
// -------------------------------------
colorBtn.addEventListener("click", () => {
  colorModal.hidden = false;
});

// -------------------------------------
// Ø¨Ø³ØªÙ† Ù…ÙˆØ¯Ø§Ù„
// -------------------------------------
function closeColorModal() {
  colorModal.hidden = true;
}

colorBackdrop.addEventListener("click", closeColorModal);
colorCloseX.addEventListener("click", closeColorModal);
colorCloseBottom.addEventListener("click", closeColorModal);

// -------------------------------------
// Ø§Ù†ØªØ®Ø§Ø¨ Ø±Ù†Ú¯
// -------------------------------------
colorOptions.forEach(option => {
  option.addEventListener("click", () => {
    const folder = option.dataset.folder;
    const name = option.dataset.color;

    selectedColorFolder = folder;
    selectedColorName = name;

    // Ù†Ù…Ø§ÛŒØ´ Ø§Ø³Ù… Ø±Ù†Ú¯
    colorName.textContent = selectedColorName;

    // ØªØºÛŒÛŒØ± Ø±Ù†Ú¯ Ø¯Ø§ÛŒØ±Ù‡ Ú©Ù†Ø§Ø± Ø§Ø³Ù…
    const dot = option.querySelector(".cdp-color-dot");
    const bg = window.getComputedStyle(dot).backgroundColor;
    colorCircle.style.background = bg;

    // ØªØºÛŒÛŒØ± Ø¹Ú©Ø³ ØªÛŒØ´Ø±Øª Ø¨Ø±Ø§ÛŒ ÙˆÛŒÙˆ ÙØ¹Ù„ÛŒ
    updateShirtImage();

    closeColorModal();
  });
});

// -------------------------------------
// ØªØºÛŒÛŒØ± ÙˆÛŒÙˆ (Front / Back / Right / Left)
// -------------------------------------
viewBtns.forEach(btn => {
  btn.addEventListener("click", () => {
    viewBtns.forEach(b => b.classList.remove("cdp-view-btn--active"));
    btn.classList.add("cdp-view-btn--active");

    const view = btn.dataset.view;
    shirtImg.dataset.view = view;

    updateShirtImage();
  });
});

// -------------------------------------
// ØªØ§Ø¨Ø¹ ØªØºÛŒÛŒØ± Ø¹Ú©Ø³ ØªÛŒØ´Ø±Øª
// -------------------------------------
function updateShirtImage() {
  const view = shirtImg.dataset.view;
  shirtImg.src =
    `${DEFAULT_PRODUCT_IMAGE_BASE}/${selectedColorFolder}/${getViewIndex(view)}.png`;
}

// ØªØ¨Ø¯ÛŒÙ„ front â†’ 1 , back â†’ 2 , right â†’ 3 , left â†’ 4
function getViewIndex(view) {
  switch (view) {
    case "front": return 1;
    case "back": return 2;
    case "right": return 3;
    case "left": return 4;
  }
}
