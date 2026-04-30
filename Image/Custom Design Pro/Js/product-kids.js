(function () {
    window.cdpProductConfig = {
        productName: "Kids T-Shirt",
        defaultColorName: "Orange",
        defaultColorFolder: "orange",
        imageBase: "images/Products/bambini/Kids T-Shirt"
    };

    document.addEventListener('DOMContentLoaded', function () {
        const nameEl = document.querySelector('.cdp-product-name');
        if (nameEl) {
            nameEl.textContent = window.cdpProductConfig.productName;
        }
    });
})();
