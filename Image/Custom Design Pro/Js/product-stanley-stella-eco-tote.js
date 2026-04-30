(function () {
    window.cdpProductConfig = {
        productName: "Stanley Stella Eco Tote",
        defaultColorName: "White",
        defaultColorFolder: "white",
        imageBase: "images/Products/Accerico/Stanley-Stella-Eco-Tote-Bag"
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
