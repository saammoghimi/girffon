(function () {
  window.GirffonProductCartSessionActive = true;

  function parsePrice(value) {
    return Number.parseFloat(String(value || "0").replace(/[^0-9.,-]/g, "").replace(",", ".")) || 0;
  }

  function getActiveColor() {
    return document.querySelector('.pd-color-dot.active')?.getAttribute('data-code') || 'Bk';
  }

  function getActiveSize() {
    return document.querySelector('.pd-size-btn.active')?.textContent?.trim() || 'One Size';
  }

  function getMainImage() {
    const image = document.getElementById('main-product-image');
    return image?.dataset?.thumb || image?.getAttribute('src') || '';
  }

  function getProductPrice() {
    const priceNode = document.getElementById('product-price');
    const effectivePrice = Number(priceNode?.dataset?.effectivePriceEur || 0);
    if (effectivePrice > 0) {
      return effectivePrice;
    }
    const basePrice = Number(priceNode?.dataset?.baseEur || priceNode?.dataset?.priceEur || 0);
    if (basePrice > 0) {
      return basePrice;
    }
    return parsePrice(priceNode?.textContent || '0');
  }

  function getMainProductPayload() {
    const code = document.getElementById('product-code')?.textContent?.trim() || 'girffon-product';
    const title = document.getElementById('product-title')?.textContent?.trim() || 'GirffoN Product';

    return {
      id: code,
      sku: code,
      name: title,
      price: getProductPrice(),
      size: getActiveSize(),
      color: getActiveColor(),
      image: getMainImage(),
      quantity: 1
    };
  }

  function getRelatedProductPayload(card) {
    const title = card.querySelector('.gx25-title')?.textContent?.trim() || 'GirffoN Product';
    const sku = card.getAttribute('data-product-sku') || card.getAttribute('data-product-id') || title.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const color = card.querySelector('.gx25-color.active')?.getAttribute('data-color') || getActiveColor();
    const image = card.querySelector('.gx25-main-image')?.getAttribute('src') || '';
    const effectivePrice = parsePrice(card.querySelector('.gx25-price')?.getAttribute('data-effective-eur') || card.querySelector('.gx25-price')?.dataset?.effectiveEur || '0');
    const basePrice = parsePrice(card.querySelector('.gx25-price')?.getAttribute('data-base-eur') || card.querySelector('.gx25-price')?.textContent || '0');

    return {
      id: sku,
      sku: sku,
      name: title,
      price: effectivePrice > 0 ? effectivePrice : basePrice,
      size: getActiveSize(),
      color: color,
      image: image,
      quantity: 1
    };
  }

  async function syncCartBadge() {
    if (!window.GirffonCartApi || typeof window.GirffonCartApi.getCart !== 'function') {
      return;
    }

    try {
      const cart = await window.GirffonCartApi.getCart();
      if (typeof window.GirffonCartApi.updateBadge === 'function') {
        window.GirffonCartApi.updateBadge(cart);
      }
    } catch (_error) {
      // Leave the current page usable if cart sync fails.
    }
  }

  async function addPayloadToSessionCart(payload) {
    if (!window.GirffonCartApi || typeof window.GirffonCartApi.addItem !== 'function') {
      return;
    }

    try {
      const cart = await window.GirffonCartApi.addItem(payload);
      if (typeof window.GirffonCartApi.updateBadge === 'function') {
        window.GirffonCartApi.updateBadge(cart);
      }
    } catch (_error) {
      return;
    }

    syncCartBadge();
  }

  document.addEventListener('click', function (event) {
    const mainAddButton = event.target.closest('.pd-addcart-btn');
    if (mainAddButton) {
      event.preventDefault();
      event.stopPropagation();
      addPayloadToSessionCart(getMainProductPayload());
      return;
    }

    const relatedAddButton = event.target.closest('.gx25-category-section .gx25-enter');
    if (relatedAddButton) {
      const card = relatedAddButton.closest('.gx25-card');
      if (!card) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      addPayloadToSessionCart(getRelatedProductPayload(card));
    }
  }, true);

  document.addEventListener('DOMContentLoaded', function () {
    syncCartBadge();
  });
})();