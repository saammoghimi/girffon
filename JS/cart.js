(function () {
  const ENDPOINTS = {
    add: '/GirffoN/backend/cart/add-to-cart.php',
    data: '/GirffoN/backend/cart/cart-data.php',
    update: '/GirffoN/backend/cart/update-cart.php',
    remove: '/GirffoN/backend/cart/remove-cart.php',
    clear: '/GirffoN/backend/cart/clear-cart.php'
  };

  const pendingMutations = new Set();

  function isObject(value) {
    return value && typeof value === 'object' && !Array.isArray(value);
  }

  function normalizeCartResponse(payload) {
    const cart = isObject(payload) && isObject(payload.cart) ? payload.cart : {};
    const items = Array.isArray(cart.items) ? cart.items : [];
    const summary = isObject(cart.summary) ? cart.summary : {};

    const normalizedItems = items.map(function (item) {
      const price = Number(item.priceNumber != null ? item.priceNumber : item.price) || 0;
      const quantity = Number(item.quantity != null ? item.quantity : item.qty) || 0;
      const lineKey = String(item.line_key || item.line_id || '').trim();
      const sku = String(item.sku || item.id || item.code || '').trim();
      const name = String(item.name || item.title || 'GirffoN Product').trim() || 'GirffoN Product';
      const image = String(item.image || item.img || '').trim();
      const size = String(item.size || '').trim();
      const color = String(item.color || '').trim();

      return {
        line_key: lineKey,
        line_id: lineKey,
        id: sku,
        sku: sku,
        code: sku,
        name: name,
        title: name,
        price: price,
        priceNumber: price,
        image: image,
        img: image,
        size: size,
        color: color,
        quantity: quantity,
        qty: quantity,
        total_price: Number(item.total_price != null ? item.total_price : item.line_total) || (price * quantity),
        line_total: Number(item.line_total != null ? item.line_total : item.total_price) || (price * quantity)
      };
    });

    const itemCount = Number(summary.itemCount != null ? summary.itemCount : cart.item_count) || normalizedItems.reduce(function (sum, item) {
      return sum + (Number(item.quantity) || 0);
    }, 0);
    const lineCount = Number(summary.lineCount != null ? summary.lineCount : cart.line_count) || normalizedItems.length;
    const subtotal = Number(summary.subtotal != null ? summary.subtotal : cart.subtotal) || normalizedItems.reduce(function (sum, item) {
      return sum + (Number(item.total_price) || 0);
    }, 0);
    const total = Number(summary.total != null ? summary.total : cart.total) || subtotal;

    return {
      items: normalizedItems,
      summary: {
        lineCount: lineCount,
        itemCount: itemCount,
        subtotal: subtotal,
        total: total
      }
    };
  }

  function mirrorLegacyCart(cart) {
    return cart;
  }

  function updateBadge(cart) {
    const badge = document.querySelector('#gfCartTrigger .count-badge');
    if (!badge) {
      return;
    }

    const count = cart && cart.summary ? Number(cart.summary.itemCount) || 0 : 0;
    badge.textContent = String(count);
  }

  async function request(url, options) {
    const response = await fetch(url, Object.assign({
      credentials: 'same-origin',
      cache: 'no-store'
    }, options || {}));

    let payload = null;
    try {
      payload = await response.json();
    } catch (_error) {
      payload = null;
    }

    if (!response.ok || !(payload && (payload.success || payload.ok))) {
      const message = payload && payload.message ? payload.message : 'Cart request failed.';
      throw new Error(message);
    }

    const cart = normalizeCartResponse(payload);
    updateBadge(cart);
    return cart;
  }

  function queueBadgeSync() {
    if (!window.GirffonCartApi || typeof window.GirffonCartApi.getCart !== 'function') {
      return;
    }

    window.setTimeout(function () {
      awaitPendingMutations()
        .then(function () {
          return getCart();
        })
        .catch(function () {
          // Leave the current badge value in place if the sync fails.
        });
    }, 0);
  }

  function trackPendingMutation(promise) {
    pendingMutations.add(promise);

    return promise.finally(function () {
      pendingMutations.delete(promise);
    });
  }

  function awaitPendingMutations() {
    if (!pendingMutations.size) {
      return Promise.resolve();
    }

    return Promise.allSettled(Array.from(pendingMutations)).then(function () {
      return pendingMutations.size ? awaitPendingMutations() : undefined;
    });
  }

  async function getCart() {
    return request(ENDPOINTS.data, { method: 'GET' });
  }

  async function addItem(item) {
    return trackPendingMutation(request(ENDPOINTS.add, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(item || {})
    }));
  }

  async function updateItem(lineKey, quantity) {
    return trackPendingMutation(request(ENDPOINTS.update, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ line_key: lineKey, quantity: quantity })
    }));
  }

  async function removeItem(lineKey) {
    return trackPendingMutation(request(ENDPOINTS.remove, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ line_key: lineKey })
    }));
  }

  async function clearCart() {
    return trackPendingMutation(request(ENDPOINTS.clear, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: '{}'
    }));
  }

  window.GirffonCartApi = {
    getCart: getCart,
    addItem: addItem,
    updateItem: updateItem,
    removeItem: removeItem,
    clearCart: clearCart,
    awaitPendingMutations: awaitPendingMutations,
    updateBadge: updateBadge,
    mirrorLegacyCart: mirrorLegacyCart,
    normalizeCartResponse: normalizeCartResponse
  };

  document.addEventListener('DOMContentLoaded', function () {
    getCart().catch(function () {
      // Keep current page usable even if cart sync fails.
    });
  });

  document.addEventListener('click', function (event) {
    if (!event.target.closest('.gx25-enter, .pd-addcart-btn, #gfWishlistOpenCartBtn, .gf-wishlist-add-cart')) {
      return;
    }

    queueBadgeSync();
  });
})();