(function () {
    window.cdpProductConfig = {
        productName: "Baby T-Shirt",
        defaultColorName: "Crystal Pink",
        defaultColorFolder: "crystal-pink",
        imageBase: "images/Products/neonati/Baby T-Shirt"
    };

    document.addEventListener('DOMContentLoaded', function () {
        const nameEl = document.querySelector('.cdp-product-name');
        if (nameEl) {
            nameEl.textContent = window.cdpProductConfig.productName;
        }
    });
})();
