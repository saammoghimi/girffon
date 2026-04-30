(function () {
    window.cdpProductConfig = {
        productName: "Flexfit Cap",
        defaultColorName: "Black",
        defaultColorFolder: "Black",
        imageBase: "images/Products/Accerico/Flexfit Cap"
    };

    document.addEventListener('DOMContentLoaded', function () {
        const nameEl = document.querySelector('.cdp-product-name');
        if (nameEl) {
            nameEl.textContent = window.cdpProductConfig.productName;
        }
    });
})();
