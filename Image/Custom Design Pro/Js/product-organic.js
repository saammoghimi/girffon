(function () {
    window.cdpProductConfig = {
        productName: "Organic Unisex T-Shirt",
        defaultColorName: "Sun Glow",
        defaultColorFolder: "crema",
        imageBase: "images/Products/Men/Organic Unisex T-Shirt"
    };

    document.addEventListener('DOMContentLoaded', function () {
        const nameEl = document.querySelector('.cdp-product-name');
        if (nameEl) {
            nameEl.textContent = window.cdpProductConfig.productName;
        }
    });
})();
