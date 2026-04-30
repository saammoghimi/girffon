(function () {
    window.cdpProductConfig = {
        productName: "Coasters Set of 4",
        defaultColorName: "Natural Cork",
        defaultColorFolder: "Sottobicchieri",
        imageBase: "images/Products/Home"
    };

    function setText(el, value) {
        if (el) {
            el.textContent = value;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const config = window.cdpProductConfig;
        const defaultSize = "10 × 10 cm";

        setText(document.querySelector('.cdp-product-name'), config.productName);
        setText(document.getElementById('cdpCartProductName'), config.productName);
        setText(document.getElementById('cdpCartScanProduct'), config.productName);
        setText(document.getElementById('cdpColorName'), config.defaultColorName);
        setText(document.getElementById('cdpCartColor'), config.defaultColorName);
        setText(document.getElementById('cdpSizeValue'), defaultSize);
        setText(document.getElementById('cdpCartSize'), defaultSize);
        setText(document.getElementById('cdpCartViewMeta'), 'View: Front');

        document.addEventListener('keydown', function (event) {
            const targetTag = (event.target.tagName || '').toLowerCase();
            if (targetTag === 'input' || targetTag === 'textarea' || event.target.isContentEditable) {
                return;
            }

            if (event.key === '2' || event.key === '3' || event.key === '4') {
                event.stopImmediatePropagation();
                event.preventDefault();
            }
        }, true);
    });
})();
