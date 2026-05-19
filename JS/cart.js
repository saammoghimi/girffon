(function () {
  const ENDPOINTS = {
    add: '/GirffoN/backend/cart/add-to-cart.php',
    data: '/GirffoN/backend/cart/cart-data.php',
    update: '/GirffoN/backend/cart/update-cart.php',
    remove: '/GirffoN/backend/cart/remove-cart.php',
    clear: '/GirffoN/backend/cart/clear-cart.php'
  };
  const LOCAL_CART_KEY = 'girffon_cart';
  const LEGACY_CART_KEYS = [
    'gf-men-cart-items',
    'gf-women-cart-items',
    'gf-kids-cart-items',
    'gf-home-living-cart-items',
    'gf-accessories-cart-items',
    'gf-counter-cart'
  ];
  const DUPLICATE_ADD_WINDOW_MS = 450;

  const pendingMutations = new Set();
  const recentAddKeys = new Map();

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

  function safeReadLocalCart() {
    try {
      const raw = window.localStorage.getItem(LOCAL_CART_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function safeWriteLocalCart(items) {
    try {
      window.localStorage.setItem(LOCAL_CART_KEY, JSON.stringify(Array.isArray(items) ? items : []));
    } catch (_error) {
      // Ignore local storage failures.
    }
  }

  function clearLegacyCartStorage() {
    try {
      LEGACY_CART_KEYS.forEach(function (key) {
        window.localStorage.removeItem(key);
      });
    } catch (_error) {
      // Ignore local storage failures.
    }
  }

  function toNumber(value) {
    if (typeof value === 'number' && Number.isFinite(value)) {
      return value;
    }

    const normalized = String(value == null ? '' : value)
      .replace(/,/g, '.')
      .replace(/[^0-9.-]+/g, '');
    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function normalizeLocalCartItem(item) {
    const quantity = Math.max(1, Number(item && (item.quantity != null ? item.quantity : item.qty)) || 1);
    const sku = String(item && (item.sku || item.id || item.code || item.title || item.name) || '').trim();
    const name = String(item && (item.name || item.title || item.sku || item.id) || 'GirffoN Product').trim() || 'GirffoN Product';

    return {
      id: sku,
      sku: sku,
      code: String(item && (item.code || sku) || '').trim(),
      name: name,
      title: name,
      image: String(item && (item.image || item.img) || '').trim(),
      img: String(item && (item.img || item.image) || '').trim(),
      price: toNumber(item && (item.priceNumber != null ? item.priceNumber : item.price)),
      priceNumber: toNumber(item && (item.priceNumber != null ? item.priceNumber : item.price)),
      size: String(item && item.size || '').trim(),
      color: String(item && item.color || '').trim(),
      quantity: quantity,
      qty: quantity
    };
  }

  function buildAddDedupKey(item) {
    return [
      String(item && (item.sku || item.id || item.code || '') || '').trim().toLowerCase(),
      String(item && item.size || '').trim().toLowerCase(),
      String(item && item.color || '').trim().toLowerCase(),
      String(Math.max(1, Number(item && (item.quantity != null ? item.quantity : item.qty)) || 1))
    ].join('|');
  }

  function shouldSkipDuplicateAdd(item) {
    const dedupKey = buildAddDedupKey(item);
    if (!dedupKey.replace(/\|/g, '')) {
      return false;
    }

    const now = Date.now();
    const lastAt = recentAddKeys.get(dedupKey) || 0;
    recentAddKeys.set(dedupKey, now);

    window.setTimeout(function () {
      if ((recentAddKeys.get(dedupKey) || 0) === now) {
        recentAddKeys.delete(dedupKey);
      }
    }, DUPLICATE_ADD_WINDOW_MS + 50);

    return now - lastAt < DUPLICATE_ADD_WINDOW_MS;
  }

  function mirrorLegacyCart(cart) {
    const items = Array.isArray(cart && cart.items) ? cart.items : [];
    safeWriteLocalCart(items.map(function (item) {
      const quantity = Math.max(1, Number(item && (item.quantity != null ? item.quantity : item.qty)) || 1);
      const price = toNumber(item && (item.priceNumber != null ? item.priceNumber : item.price));
      const sku = String(item && (item.sku || item.id || item.code || item.line_key) || '').trim();
      const name = String(item && (item.name || item.title || sku) || 'GirffoN Product').trim() || 'GirffoN Product';

      return {
        id: sku,
        sku: sku,
        code: String(item && (item.code || sku) || '').trim(),
        name: name,
        title: name,
        image: String(item && (item.image || item.img) || '').trim(),
        img: String(item && (item.img || item.image) || '').trim(),
        price: price,
        priceNumber: price,
        size: String(item && item.size || '').trim(),
        color: String(item && item.color || '').trim(),
        quantity: quantity,
        qty: quantity
      };
    }));
    clearLegacyCartStorage();

    try {
      window.dispatchEvent(new CustomEvent('girffon:cart-synced', { detail: cart }));
    } catch (_error) {
      // Ignore event dispatch failures.
    }

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
    mirrorLegacyCart(cart);
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
    if (shouldSkipDuplicateAdd(item)) {
      return getCart();
    }

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
    })).then(function (cart) {
      safeWriteLocalCart([]);
      clearLegacyCartStorage();
      return cart;
    });
  }

  async function syncLocalCartToBackend() {
    const localItems = safeReadLocalCart().map(normalizeLocalCartItem).filter(function (item) {
      return item.sku !== '' && item.quantity > 0;
    });

    const backendCart = await getCart();
    if ((backendCart.summary && Number(backendCart.summary.itemCount)) || !localItems.length) {
      return backendCart;
    }

    let cart = backendCart;
    for (const item of localItems) {
      cart = await request(ENDPOINTS.add, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item)
      });
    }

    return cart;
  }

  window.GirffonCartApi = {
    getCart: getCart,
    addItem: addItem,
    updateItem: updateItem,
    removeItem: removeItem,
    clearCart: clearCart,
    syncLocalCartToBackend: syncLocalCartToBackend,
    awaitPendingMutations: awaitPendingMutations,
    updateBadge: updateBadge,
    mirrorLegacyCart: mirrorLegacyCart,
    normalizeCartResponse: normalizeCartResponse
  };

  document.addEventListener('DOMContentLoaded', function () {
    syncLocalCartToBackend().catch(function () {
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