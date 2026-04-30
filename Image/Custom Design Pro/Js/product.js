// =========================
// Ø³ÛŒØ³ØªÙ… Ø¬Ø¯ÛŒØ¯ Ùˆ Ø­Ø±ÙÙ‡â€ŒØ§ÛŒ Ø§Ù†ØªØ®Ø§Ø¨ Ø³Ø§ÛŒØ²
// =========================

const sizes = ["XS", "S", "M", "L", "XL", "XXL"];
let sizeMenuEl = null;

const cdpProductConfig = window.cdpProductConfig || {};
const PRODUCT_IMAGE_BASE = cdpProductConfig.imageBase || "images/Products/Men/Basic Men's T-Shirt";
const DEFAULT_COLOR_FOLDER = cdpProductConfig.defaultColorFolder || "arancione";
const DEFAULT_COLOR_NAME = cdpProductConfig.defaultColorName || "Sun Yellow";

// Ù…Ù†ØªØ¸Ø± Ø¨Ø§Ø±Ú¯Ø°Ø§Ø±ÛŒ Ú©Ø§Ù…Ù„ ØµÙØ­Ù‡ Ø¨Ø§Ø´ÛŒÙ…
document.addEventListener('DOMContentLoaded', function() {
    const sizeBtn = document.getElementById("cdpSizeBtn");
    const sizeValueEl = document.getElementById("cdpSizeValue");

    // Ø¨Ø±Ø±Ø³ÛŒ ÙˆØ¬ÙˆØ¯ Ø¹Ù†Ø§ØµØ±
    if (!sizeBtn || !sizeValueEl) {
        console.error("Ø¹Ù†Ø§ØµØ± cdpSizeBtn ÛŒØ§ cdpSizeValue Ù¾ÛŒØ¯Ø§ Ù†Ø´Ø¯Ù†Ø¯!");
        return;
    }

    function createSizeMenu() {
        if (sizeMenuEl) return;

        const products = {
            men: [
                { id: "m1", name: "Basic Men's T-Shirt Sun Yellow", image: "images/Products/Men/Basic Men's T-Shirt/arancione/1.png", price: "$19.99", html: "products/men/1.html" },
                { id: "m2", name: "Organic Unisex T-Shirt Sun Glow", image: "images/Products/Men/Organic Unisex T-Shirt/crema/1.png", price: "$21.99", html: "products/men/2.html" }
            ],
            women: [
                { id: "w1", name: "Women's Premium Tee Sun Yellow", image: "images/Products/Women/Women's Premium T-Shirt/giallo sole/1.png", price: "$19.99", html: "products/women/1.html" }
            ],
            kids: [
                { id: "k1", name: "Kids T-Shirt Sun Yellow", image: "images/Products/bambini/Kids T-Shirt/gold/1.png", price: "$14.99", html: "products/kids/1.html" }
            ],
            neonati: [
                { id: "n1", name: "Baby T-Shirt White", image: "images/Products/neonati/Baby T-Shirt/white/1.png", price: "$12.99", html: "products/kids/5.html" }
            ],
            accerico: [
                { id: "a1", name: "Flexfit Cap Black", image: "images/Products/Accerico/Flexfit Cap/black/1.png", price: "$19.99", html: "products/accessories/1.html" },
                { id: "a2", name: "Trucker Cap White/Red", image: "images/Products/Accerico/Trucker-Cap/white-red/1.png", price: "$21.99", html: "products/accessories/2.html" },
                { id: "a3", name: "Classic Tote Bag Natural", image: "images/Products/Accerico/Tote-Bag/Natural/1.png", price: "$15.99", html: "products/accessories/3.html" },
                { id: "a4", name: "Stanley Stella Eco Tote White", image: "images/Products/Accerico/Stanley-Stella-Eco-Tote-Bag/white/1.png", price: "$18.99", html: "products/accessories/4.html" },
                { id: "a5", name: "Stella Recycled Shopping Bag", image: "images/Products/Accerico/Stella Recycled Fabric Shopping Bag/natural-white/1.png", price: "$24.99", html: "products/accessories/5.html" },
                { id: "a6", name: "Vintage Washed Shopper Denim", image: "images/Products/Accerico/Vintage-Washed-Shopper/vintage-denim/1.png", price: "$27.99", html: "products/accessories/6.html" }
            ],
            home: [
                { id: "h1", name: "Ceramic Mug 11oz", image: "images/Products/Home/Tazza/1.png", price: "$11.99", html: "products/home/1.html" },
                { id: "h2", name: "Cushion Cover Natural", image: "images/Products/Home/Cushion Cover/natural-white/1.png", price: "$24.99", html: "products/home/2.html" },
                { id: "h3", name: "Coasters Set of 4", image: "images/Products/Home/Sottobicchieri/1.png", price: "$16.99", html: "products/home/3.html" }
            ]
        };

        // TODO: hook this data into the size modal once ready.
    }

    createSizeMenu();
});



// =========================
// Ø³ÛŒØ³ØªÙ… Ú©Ø§Ù…Ù„ Product - Size & Color
// =========================

document.addEventListener('DOMContentLoaded', function () {
    console.log("ðŸš€ product.js Ø¨Ø§Ø±Ú¯Ø°Ø§Ø±ÛŒ Ø´Ø¯");

    // =============================
    // 1ï¸âƒ£ Ø³ÛŒØ³ØªÙ… Ø§Ù†ØªØ®Ø§Ø¨ SIZE (Panel style)
    // =============================
    
    const sizeBtn = document.getElementById("cdpSizeBtn");
    const sizeValueEl = document.getElementById("cdpSizeValue");
    const sizePanel = document.getElementById("cdpSizePanel");
    const sizeOptions = document.querySelectorAll(".cdp-size-option");
    const sizeCloseBtn = document.querySelector(".cdp-size-close");

    if (sizeBtn && sizePanel && sizeValueEl) {
        console.log("âœ… Size system elements found");

        // Ø¨Ø§Ø² Ú©Ø±Ø¯Ù† Ù¾Ù†Ù„ Ø³Ø§ÛŒØ²
        sizeBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            sizePanel.setAttribute("data-visible", "true");
            console.log("ðŸ“ Size panel opened");
        });

        // Ø¨Ø³ØªÙ† Ù¾Ù†Ù„ Ø³Ø§ÛŒØ²
        function closeSizePanel() {
            sizePanel.setAttribute("data-visible", "false");
            console.log("âŒ Size panel closed");
        }

        if (sizeCloseBtn) {
            sizeCloseBtn.addEventListener("click", closeSizePanel);
        }

        // Ø§Ù†ØªØ®Ø§Ø¨ Ø³Ø§ÛŒØ²
        sizeOptions.forEach(option => {
            option.addEventListener("click", () => {
                const size = option.dataset.size;
                if (size) {
                    sizeValueEl.textContent = size;
                    console.log(`âœ… Size selected: ${size}`);
                    
                    // Ø­Ø°Ù active Ø§Ø² Ù‡Ù…Ù‡
                    sizeOptions.forEach(opt => opt.classList.remove("active"));
                    // Ø§Ø¶Ø§ÙÙ‡ Ú©Ø±Ø¯Ù† Ø¨Ù‡ Ø§Ù†ØªØ®Ø§Ø¨ Ø´Ø¯Ù‡
                    option.classList.add("active");
                    
                    closeSizePanel();
                }
            });
        });
    } else {
        console.warn("âš ï¸ Size system elements not found");
    }

    // =============================
    // 2ï¸âƒ£ Ø³ÛŒØ³ØªÙ… Ø§Ù†ØªØ®Ø§Ø¨ COLOR
    // =============================

    const colorBtn = document.getElementById("cdpColorBtn");
    const colorNameEl = document.getElementById("cdpColorName");
    const colorPanel = document.getElementById("cdpColorPanel");
    const colorCloseBtn = document.querySelector(".cdp-color-close");
    const colorOptions = document.querySelectorAll(".cdp-color-option");
    const shirtImg = document.getElementById("cdpShirtImage");
    const viewButtons = document.querySelectorAll(".cdp-view-btn");

    if (!colorBtn || !colorPanel || !shirtImg) {
        console.error("âŒ Color system elements not found:", {
            colorBtn: !!colorBtn,
            colorPanel: !!colorPanel,
            shirtImg: !!shirtImg
        });
        return;
    }

    console.log("âœ… Color system elements found");

    if (colorNameEl && !colorNameEl.textContent.trim()) {
        colorNameEl.textContent = DEFAULT_COLOR_NAME;
    }

    // Ø¯Ø§ÛŒØ±Ù‡ Ú©ÙˆÚ†Ú© Ø¯Ø§Ø®Ù„ Ø¯Ú©Ù…Ù‡ Ø±Ù†Ú¯
    const colorKnob = colorBtn.querySelector(".cdp-toggle-knob");

    // ÙˆØ¶Ø¹ÛŒØª Ù¾ÛŒØ´â€ŒÙØ±Ø¶
    let currentFolder = DEFAULT_COLOR_FOLDER; // Ø±Ù†Ú¯ Ù¾ÛŒØ´â€ŒÙØ±Ø¶
    let currentView = "front";    // ÙˆÛŒÙˆ Ù¾ÛŒØ´â€ŒÙØ±Ø¶

    // ØªØ¨Ø¯ÛŒÙ„ ÙˆÛŒÙˆ Ø¨Ù‡ Ø´Ù…Ø§Ø±Ù‡ Ø¹Ú©Ø³
    function getViewIndex(view) {
        const viewMap = {
            "front": 1,
            "back": 2,
            "right": 3,
            "left": 4
        };
        return viewMap[view] || 1;
    }

    // Ø¨Ù‡â€ŒØ±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ Ø¹Ú©Ø³ ØªÛŒØ´Ø±Øª
    function updateShirtImage() {
        const idx = getViewIndex(currentView);
        const newSrc = `${PRODUCT_IMAGE_BASE}/${currentFolder}/${idx}.png`;
        
        console.log("ðŸ”„ Updating shirt image:", {
            folder: currentFolder,
            view: currentView,
            index: idx,
            path: newSrc
        });

        shirtImg.src = newSrc;
        shirtImg.dataset.colorFolder = currentFolder;
        shirtImg.dataset.view = currentView;

        // Ø¨Ù‡â€ŒØ±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ ØªØµØ§ÙˆÛŒØ± thumbnail Ø¯Ø± Ø¯Ú©Ù…Ù‡â€ŒÙ‡Ø§ÛŒ ÙˆÛŒÙˆ
        viewButtons.forEach(btn => {
            const btnView = btn.dataset.view;
            const btnIdx = getViewIndex(btnView);
            const img = btn.querySelector("img");
            if (img) {
                img.src = `${PRODUCT_IMAGE_BASE}/${currentFolder}/${btnIdx}.png`;
            }
        });
    }

    // Ø¨Ø§Ø² Ú©Ø±Ø¯Ù† Ù¾Ù†Ù„ Ø±Ù†Ú¯
    function openColorPanel() {
        colorPanel.setAttribute("data-visible", "true");
        console.log("ðŸŽ¨ Color panel opened");
    }

    // Ø¨Ø³ØªÙ† Ù¾Ù†Ù„ Ø±Ù†Ú¯
    function closeColorPanel() {
        colorPanel.setAttribute("data-visible", "false");
        console.log("âŒ Color panel closed");
    }

    // Ú©Ù„ÛŒÚ© Ø±ÙˆÛŒ Ø¯Ú©Ù…Ù‡ Ø±Ù†Ú¯
    colorBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        openColorPanel();
    });

    // Ø¨Ø³ØªÙ† Ù¾Ù†Ù„ Ø¨Ø§ Ø¯Ú©Ù…Ù‡ X
    if (colorCloseBtn) {
        colorCloseBtn.addEventListener("click", closeColorPanel);
    }

    // Ø§Ù†ØªØ®Ø§Ø¨ Ø±Ù†Ú¯ Ø§Ø² Ù…ÙˆØ¯Ø§Ù„
    colorOptions.forEach((opt) => {
        opt.addEventListener("click", function() {
            const folder = opt.dataset.folder;
            const name = opt.dataset.color;

            console.log(`ðŸŽ¨ Color selected:`, { folder, name });

            // ØªØºÛŒÛŒØ± Ù†Ø§Ù… Ø±Ù†Ú¯
            if (name && colorNameEl) {
                colorNameEl.textContent = name;
            }

            // ØªØºÛŒÛŒØ± ÙÙˆÙ„Ø¯Ø± Ø±Ù†Ú¯
            if (folder) {
                currentFolder = folder;
            } else {
                console.warn("âš ï¸ Color folder not defined");
            }

            // ØªØºÛŒÛŒØ± Ø±Ù†Ú¯ Ø¯Ø§ÛŒØ±Ù‡ Ø¯Ú©Ù…Ù‡
            const dot = opt.querySelector(".cdp-color-dot");
            if (dot && colorKnob) {
                const bg = window.getComputedStyle(dot).backgroundColor;
                colorKnob.style.backgroundColor = bg;
                console.log("ðŸ”´ Knob color:", bg);
            }

            // Ø­Ø°Ù active Ø§Ø² Ù‡Ù…Ù‡
            colorOptions.forEach(o => o.classList.remove("active"));
            // Ø§Ø¶Ø§ÙÙ‡ Ú©Ø±Ø¯Ù† Ø¨Ù‡ Ø§Ù†ØªØ®Ø§Ø¨ Ø´Ø¯Ù‡
            opt.classList.add("active");

            // Ø¨Ù‡â€ŒØ±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ Ø¹Ú©Ø³ ØªÛŒØ´Ø±Øª
            updateShirtImage();

            // Ø¨Ø³ØªÙ† Ù¾Ù†Ù„
            closeColorPanel();
        });
    });

    // =============================
    // 3ï¸âƒ£ Ø³ÛŒØ³ØªÙ… ØªØºÛŒÛŒØ± VIEW (Front/Back/Right/Left)
    // =============================

    function applyViewChange(view) {
        if (!view) {
            console.warn("âš ï¸ View not defined");
            return;
        }

        console.log(`ðŸ‘ï¸ View changed to: ${view}`);

        currentView = view;

        viewButtons.forEach(btn => {
            btn.classList.toggle("cdp-view-btn--active", btn.dataset.view === view);
        });

        updateShirtImage();

        const layersLabel = document.getElementById("cdpLayersViewLabel");
        if (layersLabel) {
            layersLabel.textContent = view.charAt(0).toUpperCase() + view.slice(1);
        }
    }

    viewButtons.forEach((btn) => {
        btn.addEventListener("click", function() {
            applyViewChange(btn.dataset.view);
        });
    });

    const viewHotkeys = {
        "1": "front",
        "2": "back",
        "3": "right",
        "4": "left"
    };

    document.addEventListener("keydown", (event) => {
        const targetView = viewHotkeys[event.key];
        if (!targetView) {
            return;
        }

        const activeElement = document.activeElement;
        if (activeElement && (activeElement.tagName === "INPUT" || activeElement.tagName === "TEXTAREA" || activeElement.isContentEditable)) {
            return;
        }

        event.preventDefault();
        applyViewChange(targetView);
    });

    // =============================
    // 4ï¸âƒ£ Ø§ÙˆÙ„ÛŒÙ† Ø¨Ø§Ø± ØµÙØ­Ù‡ Ø¨Ø§Ø±Ú¯Ø°Ø§Ø±ÛŒ Ø´Ø¯
    // =============================

    // ØªÙ†Ø¸ÛŒÙ… Ø±Ù†Ú¯ Ø§ÙˆÙ„ÛŒÙ‡ Ø¯Ø§ÛŒØ±Ù‡
    const defaultColor = document.querySelector(`.cdp-color-option[data-folder="${DEFAULT_COLOR_FOLDER}"]`);
    if (defaultColor && colorKnob) {
        const defaultDot = defaultColor.querySelector(".cdp-color-dot");
        if (defaultDot) {
            const bg = window.getComputedStyle(defaultDot).backgroundColor;
            colorKnob.style.backgroundColor = bg;
        }
    }

    // Ù†Ù…Ø§ÛŒØ´ Ø¹Ú©Ø³ Ø§ÙˆÙ„ÛŒÙ‡
    applyViewChange(currentView);

    console.log("âœ… Product system ready!");
});









// =========================
// Products System - Clean with CSS
// =========================

document.addEventListener('DOMContentLoaded', function() {
    console.log("ðŸ›ï¸ products.js loaded");

    const productsBtn = document.querySelector('[data-tool="products"]');
    let productsModal = null;

    if (!productsBtn) {
        console.error("âŒ Products button not found");
        return;
    }

    // =========================
    // Product Categories Data
    // =========================

    const categories = [
        { id: "all", name: "All", icon: "fa-border-all", count: 14 },
        { id: "men", name: "Men", icon: "fa-person", count: 2 },
        { id: "women", name: "Women", icon: "fa-person-dress", count: 1 },
        { id: "kids", name: "Kids", icon: "fa-child", count: 1 },
        { id: "neonati", name: "Neonati", icon: "fa-baby", count: 1 },
        { id: "accerico", name: "Accerico", icon: "fa-bag-shopping", count: 6 },
        { id: "home", name: "Home & Living", icon: "fa-house", count: 3 }
    ];

    // =========================
    // Product Items Data
    // =========================

    const products = {
        men: [
            { id: "m1", name: "Basic Men's T-Shirt Sun Yellow", image: "images/Products/Men/Basic Men's T-Shirt/arancione/1.png", price: "$19.99", html: "CustomDesignPro.html" },
            { id: "m2", name: "Organic Unisex T-Shirt Sun Glow", image: "images/Products/Men/Organic Unisex T-Shirt/crema/1.png", price: "$21.99", html: "OrganicUnisexT-Shirt.html" }
        ],
        women: [
            { id: "w1", name: "Women's Premium Tee Sun Yellow", image: "images/Products/Women/Women's Premium T-Shirt/giallo sole/1.png", price: "$19.99", html: "WomenPremiumT-Shirt.html" }
        ],
        kids: [
            { id: "k1", name: "Kids T-Shirt Sun Yellow", image: "images/Products/bambini/Kids T-Shirt/gold/1.png", price: "$14.99", html: "KidsT-Shirt.html" }
        ],
        neonati: [
            { id: "n1", name: "Baby T-Shirt White", image: "images/Products/neonati/Baby T-Shirt/white/1.png", price: "$12.99", html: "BabyT-Shirt.html" }
        ],
        accerico: [
            { id: "a1", name: "Flexfit Cap Black", image: "images/Products/Accerico/Flexfit Cap/black/1.png", price: "$19.99", html: "FlexfitCap.html" },
            { id: "a2", name: "Trucker Cap White/Red", image: "images/Products/Accerico/Trucker-Cap/white-red/1.png", price: "$21.99", html: "TruckerCap.html" },
            { id: "a3", name: "Classic Tote Bag Natural", image: "images/Products/Accerico/Tote-Bag/Natural/1.png", price: "$15.99", html: "ClassicToteBag.html" },
            { id: "a4", name: "Stanley Stella Eco Tote White", image: "images/Products/Accerico/Stanley-Stella-Eco-Tote-Bag/white/1.png", price: "$18.99", html: "StanleyStellaEcoTote.html" },
            { id: "a5", name: "Stella Recycled Shopping Bag", image: "images/Products/Accerico/Stella Recycled Fabric Shopping Bag/natural-white/1.png", price: "$24.99", html: "StellaRecycledShoppingBag.html" },
            { id: "a6", name: "Vintage Washed Shopper Denim", image: "images/Products/Accerico/Vintage-Washed-Shopper/vintage-denim/1.png", price: "$27.99", html: "VintageWashedShopperDenim.html" }
        ],
        home: [
            { id: "h1", name: "Ceramic Mug 11oz", image: "images/Products/Home/Tazza/1.png", price: "$11.99", html: "CeramicMugoz.html" },
            { id: "h2", name: "Cushion Cover Natural", image: "images/Products/Home/Cushion Cover/natural-white/1.png", price: "$24.99", html: "CushionCoverNatural.html" },
            { id: "h3", name: "Coasters Set of 4", image: "images/Products/Home/Sottobicchieri/1.png", price: "$16.99", html: "CoastersSetOf4.html" }
        ]
    };

    // =========================
    // Create Modal
    // =========================

    function createProductsModal() {
        if (productsModal) return;

        productsModal = document.createElement("div");
        productsModal.className = "cdp-products-modal";

        productsModal.innerHTML = `
            <div class="cdp-products-backdrop"></div>
            <div class="cdp-products-container">
                <aside class="cdp-products-sidebar">
                    <header class="cdp-products-sidebar-header">
                        <h3><i class="fa-solid fa-shirt"></i> Products</h3>
                        <button type="button" class="cdp-products-close-btn">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </header>
                    <nav id="cdpProductCategories" class="cdp-products-categories"></nav>
                </aside>
                <main class="cdp-products-main">
                    <header class="cdp-products-main-header">
                        <h2 id="cdpProductsTitle">All Products</h2>
                        <span id="cdpProductsCount" class="cdp-products-count">14 items</span>
                    </header>
                    <div id="cdpProductsGrid" class="cdp-products-grid"></div>
                </main>
            </div>
        `;

        document.body.appendChild(productsModal);

        const closeBtn = productsModal.querySelector(".cdp-products-close-btn");
        const backdrop = productsModal.querySelector(".cdp-products-backdrop");

        closeBtn.addEventListener("click", closeProductsModal);
        backdrop.addEventListener("click", closeProductsModal);

        buildCategories();
    }

    function buildCategories() {
        const categoriesNav = document.getElementById("cdpProductCategories");
        categoriesNav.innerHTML = "";

        categories.forEach((cat, index) => {
            const btn = document.createElement("button");
            btn.className = "cdp-category-btn";
            if (index === 0) btn.classList.add("cdp-category-btn--active");

            btn.innerHTML = `
                <i class="fa-solid ${cat.icon}"></i>
                <span class="cdp-category-name">${cat.name}</span>
                <span class="cdp-category-count">${cat.count}</span>
            `;

            btn.addEventListener("click", () => {
                categoriesNav.querySelectorAll(".cdp-category-btn").forEach(b => 
                    b.classList.remove("cdp-category-btn--active")
                );
                btn.classList.add("cdp-category-btn--active");
                displayProducts(cat.id);
            });

            categoriesNav.appendChild(btn);
        });

        displayProducts("all");
    }

    function displayProducts(categoryId) {
        const grid = document.getElementById("cdpProductsGrid");
        const title = document.getElementById("cdpProductsTitle");
        const countEl = document.getElementById("cdpProductsCount");

        grid.innerHTML = "";

        let allProducts = [];
        
        if (categoryId === "all") {
            Object.values(products).forEach(catProducts => {
                allProducts = allProducts.concat(catProducts);
            });
            title.textContent = "All Products";
        } else {
            allProducts = products[categoryId] || [];
            const cat = categories.find(c => c.id === categoryId);
            title.textContent = cat ? cat.name : "Products";
        }

        countEl.textContent = `${allProducts.length} items`;

        allProducts.forEach(product => {
            const card = document.createElement("div");
            card.className = "cdp-product-card";
            card.innerHTML = `
                <div class="cdp-product-image">
                    <img src="${product.image}" alt="${product.name}">
                </div>
                <div class="cdp-product-info">
                    <h4 class="cdp-product-name">${product.name}</h4>
                    <p class="cdp-product-price">${product.price}</p>
                </div>
            `;
            card.addEventListener("click", () => window.open(product.html, '_blank'));
            grid.appendChild(card);
        });
    }

    function showProductsModal() {
        createProductsModal();
        productsModal.setAttribute('data-visible', 'true');
        document.body.style.overflow = "hidden";
    }

    function closeProductsModal() {
        if (productsModal) {
            productsModal.removeAttribute('data-visible');
            document.body.style.overflow = "";
        }
    }

    productsBtn.addEventListener("click", showProductsModal);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeProductsModal();
    });

    console.log("âœ… Products system ready!");
});
