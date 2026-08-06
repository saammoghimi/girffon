(function () {
  const ENDPOINTS = {
    add: '/GirffoN/backend/cart/add-to-cart.php',
    data: '/GirffoN/backend/cart/cart-data.php',
    update: '/GirffoN/backend/cart/update-cart.php',
    remove: '/GirffoN/backend/cart/remove-cart.php',
    clear: '/GirffoN/backend/cart/clear-cart.php'
  };
  const LOCAL_CART_KEY = 'girffon_cart';
  const LOCAL_WISHLIST_KEY = 'girffon_wishlist';
  const LEGACY_CART_KEYS = [
    'gf-men-cart-items',
    'gf-women-cart-items',
    'gf-kids-cart-items',
    'gf-home-living-cart-items',
    'gf-accessories-cart-items',
    'gf-counter-cart'
  ];
  const DUPLICATE_ADD_WINDOW_MS = 450;
  const ANALYTICS_ENDPOINT = '/GirffoN/backend/analytics/track.php';
  const ANALYTICS_VISITOR_KEY = 'girffon_analytics_visitor_id';
  const ANALYTICS_SESSION_KEY = 'girffon_analytics_session_id';
  const ANALYTICS_LAST_TOUCH_KEY = 'girffon_analytics_last_touch';
  const ANALYTICS_ONCE_PREFIX = 'girffon_analytics_once_';
  const ANALYTICS_SESSION_TTL_MS = 30 * 60 * 1000;
  const ANALYTICS_HEARTBEAT_MS = 60 * 1000;
  const ANALYTICS_EXIT_ONCE_PREFIX = 'girffon_analytics_exit_';
  const sharedAnalytics = window.GirffonAnalytics && window.GirffonAnalytics._shared ? window.GirffonAnalytics : null;

  const pendingMutations = new Set();
  const recentAddKeys = new Map();
  const pageStartedAt = Date.now();
  const pageInstanceId = createAnalyticsId('page');

  function createAnalyticsId(prefix) {
    return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
  }

  function safeStorageGet(storage, key) {
    try {
      return storage.getItem(key) || '';
    } catch (_error) {
      return '';
    }
  }

  function safeStorageSet(storage, key, value) {
    try {
      storage.setItem(key, value);
    } catch (_error) {
      // Ignore storage failures.
    }
  }

  function isPublicAnalyticsPage() {
    const body = document.body;
    const path = String(window.location.pathname || '').toLowerCase();
    return !(body && body.dataset && body.dataset.adminPage) && path.indexOf('/admin') === -1 && path.indexOf('admin-') === -1;
  }

  function ensureAnalyticsContext() {
    if (sharedAnalytics && typeof sharedAnalytics.getContext === 'function') {
      return sharedAnalytics.getContext();
    }

    if (!isPublicAnalyticsPage()) {
      return null;
    }

    const now = Date.now();
    let visitorId = safeStorageGet(window.localStorage, ANALYTICS_VISITOR_KEY);
    if (!visitorId) {
      visitorId = createAnalyticsId('visitor');
      safeStorageSet(window.localStorage, ANALYTICS_VISITOR_KEY, visitorId);
    }

    let sessionId = safeStorageGet(window.sessionStorage, ANALYTICS_SESSION_KEY);
    const lastTouch = Number.parseInt(safeStorageGet(window.sessionStorage, ANALYTICS_LAST_TOUCH_KEY) || '0', 10) || 0;
    if (!sessionId || !lastTouch || (now - lastTouch) > ANALYTICS_SESSION_TTL_MS) {
      sessionId = createAnalyticsId('session');
      safeStorageSet(window.sessionStorage, ANALYTICS_SESSION_KEY, sessionId);
    }

    safeStorageSet(window.sessionStorage, ANALYTICS_LAST_TOUCH_KEY, String(now));

    return {
      visitor_id: visitorId,
      session_id: sessionId
    };
  }

  function extractSearchKeyword() {
    const candidates = [];

    try {
      const currentUrl = new URL(window.location.href);
      ['utm_term', 'q', 'query', 'search', 'keyword', 'k', 's', 'text'].forEach(function (key) {
        const value = currentUrl.searchParams.get(key);
        if (value) {
          candidates.push(value);
        }
      });
    } catch (_error) {
    }

    try {
      if (document.referrer) {
        const referrerUrl = new URL(document.referrer);
        ['q', 'p', 'query', 'search', 'keyword', 'k', 's', 'text', 'utm_term'].forEach(function (key) {
          const value = referrerUrl.searchParams.get(key);
          if (value) {
            candidates.push(value);
          }
        });
      }
    } catch (_error) {
    }

    const keyword = String(candidates.find(Boolean) || '').trim();
    return keyword || '';
  }

  function buildAnalyticsPayload(eventType, meta) {
    const context = ensureAnalyticsContext();
    if (!context) {
      return null;
    }

    const safeMeta = meta && typeof meta === 'object' ? Object.assign({}, meta) : {};
    if (!safeMeta.search_keyword) {
      const searchKeyword = extractSearchKeyword();
      if (searchKeyword) {
        safeMeta.search_keyword = searchKeyword;
      }
    }

    const payload = {
      visitor_id: context.visitor_id,
      session_id: context.session_id,
      event_type: String(eventType || 'page_view'),
      page_path: String(window.location.pathname || '/'),
      page_title: String(document.title || ''),
      referrer: String(document.referrer || ''),
      meta: safeMeta
    };

    if (safeMeta.duration_seconds != null) {
      payload.duration_seconds = Number(safeMeta.duration_seconds) || 0;
    }

    return payload;
  }

  function sendExitAnalytics() {
    if (sharedAnalytics && typeof sharedAnalytics.trackExit === 'function') {
      return sharedAnalytics.trackExit();
    }

    if (!isPublicAnalyticsPage()) {
      return false;
    }

    const exitKey = ANALYTICS_EXIT_ONCE_PREFIX + pageInstanceId;
    if (safeStorageGet(window.sessionStorage, exitKey) === '1') {
      return false;
    }

    safeStorageSet(window.sessionStorage, exitKey, '1');
    sendAnalyticsPayload(buildAnalyticsPayload('page_exit', {
      duration_seconds: Math.max(1, Math.round((Date.now() - pageStartedAt) / 1000)),
      search_keyword: extractSearchKeyword()
    }), true);
    return true;
  }

  function sendAnalyticsPayload(payload, preferBeacon) {
    if (!payload) {
      return Promise.resolve(false);
    }

    if (preferBeacon && navigator.sendBeacon) {
      try {
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        return Promise.resolve(navigator.sendBeacon(ANALYTICS_ENDPOINT, blob));
      } catch (_error) {
      }
    }

    return fetch(ANALYTICS_ENDPOINT, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      keepalive: true
    }).then(function () {
      return true;
    }).catch(function () {
      return false;
    });
  }

  function trackAnalytics(eventType, meta) {
    if (sharedAnalytics && typeof sharedAnalytics.track === 'function') {
      return sharedAnalytics.track(eventType, meta);
    }

    return sendAnalyticsPayload(buildAnalyticsPayload(eventType, meta), false);
  }

  function trackAnalyticsOnce(eventType, onceKey, meta) {
    if (sharedAnalytics && typeof sharedAnalytics.trackOnce === 'function') {
      return sharedAnalytics.trackOnce(eventType, onceKey, meta);
    }

    const storageKey = ANALYTICS_ONCE_PREFIX + String(onceKey || eventType || 'event');
    if (safeStorageGet(window.sessionStorage, storageKey) === '1') {
      return Promise.resolve(false);
    }

    safeStorageSet(window.sessionStorage, storageKey, '1');
    return trackAnalytics(eventType, meta);
  }

  function readWishlistCount() {
    try {
      const raw = window.localStorage.getItem(LOCAL_WISHLIST_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed.length : 0;
    } catch (_error) {
      return 0;
    }
  }

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
        line_total: Number(item.line_total != null ? item.line_total : item.total_price) || (price * quantity),
        item_type: String(item.item_type || 'product').trim(),
        delivery_type: String(item.delivery_type || '').trim(),
        gift_card_amount: Number(item.gift_card_amount != null ? item.gift_card_amount : price) || 0,
        buyer_name: String(item.buyer_name || '').trim(),
        buyer_email: String(item.buyer_email || '').trim(),
        recipient_name: String(item.recipient_name || '').trim(),
        recipient_email: String(item.recipient_email || '').trim(),
        gift_message: String(item.gift_message || '').trim(),
        expires_at: String(item.expires_at || '').trim()
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
      ,item_type: String(item && item.item_type || 'product').trim()
      ,delivery_type: String(item && item.delivery_type || '').trim()
      ,gift_card_amount: toNumber(item && (item.gift_card_amount != null ? item.gift_card_amount : item.priceNumber != null ? item.priceNumber : item.price))
      ,buyer_name: String(item && item.buyer_name || '').trim()
      ,buyer_email: String(item && item.buyer_email || '').trim()
      ,recipient_name: String(item && item.recipient_name || '').trim()
      ,recipient_email: String(item && item.recipient_email || '').trim()
      ,gift_message: String(item && item.gift_message || '').trim()
      ,expires_at: String(item && item.expires_at || '').trim()
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
        qty: quantity,
        item_type: String(item && item.item_type || 'product').trim(),
        delivery_type: String(item && item.delivery_type || '').trim(),
        gift_card_amount: toNumber(item && (item.gift_card_amount != null ? item.gift_card_amount : item.priceNumber != null ? item.priceNumber : item.price)),
        buyer_name: String(item && item.buyer_name || '').trim(),
        buyer_email: String(item && item.buyer_email || '').trim(),
        recipient_name: String(item && item.recipient_name || '').trim(),
        recipient_email: String(item && item.recipient_email || '').trim(),
        gift_message: String(item && item.gift_message || '').trim(),
        expires_at: String(item && item.expires_at || '').trim()
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
    })).then(function (cart) {
      trackAnalytics('add_to_cart', {
        sku: String(item && (item.sku || item.id || item.code || '') || ''),
        item_type: String(item && item.item_type || 'product'),
        quantity: Math.max(1, Number(item && (item.quantity != null ? item.quantity : item.qty)) || 1)
      });
      return cart;
    });
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

  if (!sharedAnalytics) {
    window.GirffonAnalytics = {
      track: trackAnalytics,
      trackOnce: trackAnalyticsOnce,
      getContext: ensureAnalyticsContext
    };
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!sharedAnalytics) {
      trackAnalytics('page_view', {
        section: 'public_website'
      });

      if (isPublicAnalyticsPage()) {
        window.setInterval(function () {
          trackAnalytics('heartbeat', {
            active_seconds: Math.max(1, Math.round((Date.now() - pageStartedAt) / 1000))
          });
        }, ANALYTICS_HEARTBEAT_MS);
      }
    }

    syncLocalCartToBackend().catch(function () {
      // Keep current page usable even if cart sync fails.
    });
  });

  if (!sharedAnalytics) {
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState !== 'hidden') {
        return;
      }

      sendExitAnalytics();
    });

    window.addEventListener('pagehide', function () {
      sendExitAnalytics();
    });
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('.gx25-enter, .pd-addcart-btn, #gfWishlistOpenCartBtn, .gf-wishlist-add-cart')) {
      const wishlistTrigger = event.target.closest('.gx25-fav');
      if (!wishlistTrigger) {
        return;
      }

      const previousCount = readWishlistCount();
      window.setTimeout(function () {
        const nextCount = readWishlistCount();
        if (nextCount > previousCount) {
          trackAnalytics('wishlist_add', {
            count_delta: nextCount - previousCount
          });
        }
      }, 0);
      return;
    }

    queueBadgeSync();
  }, true);
})();