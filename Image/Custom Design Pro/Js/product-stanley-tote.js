(function () {
    window.cdpProductConfig = {
        productName: "Stanley/Stella Eco Tote",
        defaultColorName: "White",
        defaultColorFolder: "white",
        imageBase: "images/Products/Accerico/Stanley-Stella-Eco-Tote-Bag"
    };

    function setTextContent(el, value) {
        if (el) {
            el.textContent = value;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const config = window.cdpProductConfig;

        setTextContent(document.querySelector('.cdp-product-name'), config.productName);
        setTextContent(document.getElementById('cdpCartProductName'), config.productName);
        setTextContent(document.getElementById('cdpCartScanProduct'), config.productName);
        setTextContent(document.getElementById('cdpColorName'), config.defaultColorName);
        setTextContent(document.getElementById('cdpCartColor'), config.defaultColorName);
        setTextContent(document.getElementById('cdpSizeValue'), 'One Size');
        setTextContent(document.getElementById('cdpCartSize'), 'One Size');

        // Block unsupported side-view shortcuts so missing tote angles are never requested.
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
