(function () {
    window.cdpProductConfig = {
        productName: "Trucker Cap",
        defaultColorName: "White/Red",
        defaultColorFolder: "white-red",
        imageBase: "images/Products/Accerico/Trucker-Cap"
    };

    function setText(el, value) {
        if (el) {
            el.textContent = value;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const config = window.cdpProductConfig;

        setText(document.querySelector('.cdp-product-name'), config.productName);
        setText(document.getElementById('cdpCartProductName'), config.productName);
        setText(document.getElementById('cdpCartScanProduct'), config.productName);
        setText(document.getElementById('cdpColorName'), config.defaultColorName);
        setText(document.getElementById('cdpCartColor'), config.defaultColorName);
        setText(document.getElementById('cdpSizeValue'), 'One Size');
        setText(document.getElementById('cdpCartSize'), 'One Size');
    });
})();
