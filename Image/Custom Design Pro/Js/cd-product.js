// Js/cd-product.js
// نسخه حرفه‌ای و تمیز – فقط کنترل رنگ + ویو + سایز
// بدون dropdown اضافی – اتصال کامل به مودال جدید رنگ

document.addEventListener("DOMContentLoaded", () => {
    initCdpProduct();
});

function initCdpProduct() {

    const shirtImg     = document.querySelector(".cdp-shirt-image");
    const viewButtons  = document.querySelectorAll(".cdp-view-btn");
    const colorBtn     = document.getElementById("cdpColorBtn");
    const colorNameEl  = document.querySelector(".cdp-color-name");

    const modal        = document.getElementById("cdpColorModal");
    const modalClose   = document.getElementById("cdpColorModalClose");
    const modalClose2  = document.getElementById("cdpColorCloseBottom");
    const colorOptions = document.querySelectorAll(".cdp-color-option");

    if (!shirtImg) return;

    // =========================
    // تنظیمات رنگ‌ها
    // =========================
    const colorConfigs = {
        white:  { label: "White",  folder: "bianco",            circle: "#ffffff" },
        black:  { label: "Black",  folder: "nero",              circle: "#000000" },
        yellow: { label: "Yellow", folder: "giallo",            circle: "#facc15" },
        mint:   { label: "Mint",   folder: "menta",             circle: "#6ee7b7" },
        navy:   { label: "Navy",   folder: "Navy",              circle: "#111827" },
    };

    let currentColor = "white";
    let currentView  = "front";

    // =========================
    // تابع تغییر عکس
    // =========================
    function updateShirtImage() {
        const conf = colorConfigs[currentColor];

        let viewNum = 1;
        if (currentView === "back")  viewNum = 2;
        if (currentView === "right") viewNum = 3;
        if (currentView === "left")  viewNum = 4;

        shirtImg.src = `images/all photo tishirt/uomo/Maglietta uomo/${conf.folder}/${viewNum}.png`;
    }

    // =========================
    // UI رنگ (اسم + دایره)
    // =========================
    function updateColorUI() {
        const conf = colorConfigs[currentColor];
        colorNameEl.textContent = conf.label;
        colorBtn.style.setProperty("--cdp-color-circle", conf.circle);
    }

    // =========================
    // باز کردن مودال رنگ
    // =========================
    function openColorModal() {
        modal.hidden = false;
    }

    function closeColorModal() {
        modal.hidden = true;
    }

    colorBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        openColorModal();
    });

    modalClose.addEventListener("click", closeColorModal);
    modalClose2.addEventListener("click", closeColorModal);

    // کلیک بیرون → بستن مودال
    document.addEventListener("click", (e) => {
        if (!modal.hidden && !modal.querySelector(".cdp-color-modal-panel").contains(e.target)) {
            closeColorModal();
        }
    });

    // =========================
    // انتخاب رنگ از داخل مودال
    // =========================
    colorOptions.forEach((btn) => {
        btn.addEventListener("click", () => {
            const folder = btn.dataset.folder;

            // پیدا کردن رنگ بر اساس پوشه
            currentColor = Object.keys(colorConfigs).find(
                key => colorConfigs[key].folder === folder
            );

            updateColorUI();
            updateShirtImage();
            closeColorModal();
        });
    });

    // =========================
    // کنترل دکمه‌های View (Front, Back…)
    // =========================
    viewButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            currentView = btn.dataset.view;

            viewButtons.forEach(b => b.classList.remove("cdp-view-btn--active"));
            btn.classList.add("cdp-view-btn--active");

            updateShirtImage();
        });
    });

    // مقدار اولیه
    updateColorUI();
    updateShirtImage();
}
