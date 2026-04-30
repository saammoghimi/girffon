(function () {
    window.cdpProductConfig = {
        productName: "Women's Premium T-Shirt",
        defaultColorName: "Sun Yellow",
        defaultColorFolder: "giallo sole",
        imageBase: "images/Products/Women/Women's Premium T-Shirt"
    };

    document.addEventListener('DOMContentLoaded', function () {
        const nameEl = document.querySelector('.cdp-product-name');
        if (nameEl) {
            nameEl.textContent = window.cdpProductConfig.productName;
        }
    });
})();
