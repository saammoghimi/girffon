(function () {
    window.cdpProductConfig = {
        productName: "Classic Tote Bag",
        defaultColorName: "Natural",
        defaultColorFolder: "Natural",
        imageBase: "images/Products/Accerico/Tote-Bag"
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

        // Prevent unsupported side-view shortcuts (only front/back assets exist).
        document.addEventListener('keydown', function (event) {
            const targetTag = (event.target.tagName || '').toLowerCase();
            if (targetTag === 'input' || targetTag === 'textarea' || event.target.isContentEditable) {
                return;
            }

            if (event.key === '3' || event.key === '4') {
                event.stopImmediatePropagation();
                event.preventDefault();
            }
        }, true);
    });
})();
