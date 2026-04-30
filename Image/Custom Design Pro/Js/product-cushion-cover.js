(function () {
    window.cdpProductConfig = {
        productName: "Cushion Cover Natural",
        defaultColorName: "Natural White",
        defaultColorFolder: "natural-white",
        imageBase: "images/Products/Home/Cushion Cover"
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
        setText(document.getElementById('cdpSizeValue'), '45 × 45 cm');
        setText(document.getElementById('cdpCartSize'), '45 × 45 cm');

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
