(function () {
  if (window.GirffonLivePricing) {
    return;
  }

  const ENDPOINT = "backend/utils/storefront-live-pricing.php";
  const CATEGORY_ALIASES = {
    men: ["men", "menswear", "girffon menswear"],
    women: ["women", "womenswear", "girffon womenswear"],
    kids: ["kids", "kids babies", "kids and babies", "kidswear", "girffon kidswear"],
    accessories: ["accessories", "girffon accessories"],
    "home-living": ["home living", "home and living", "home living decor", "girffon home living"]
  };

  let catalogPromise = null;
  let catalogIndex = null;

  function normalizeValue(value) {
    return String(value || "")
      .toLowerCase()
      .replace(/&/g, " and ")
      .replace(/[^a-z0-9]+/g, " ")
      .trim()
      .replace(/\s+/g, " ");
  }

  function toPriceNumber(value) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function defaultFormatPrice(value) {
    return "EUR " + Number(value || 0).toFixed(2);
  }

  function uniqueValues(values) {
    return Array.from(new Set(values.filter(Boolean)));
  }

  function categoryHints(categoryKey) {
    const normalizedKey = normalizeValue(categoryKey);
    const baseHints = CATEGORY_ALIASES[categoryKey] || CATEGORY_ALIASES[normalizedKey.replace(/ /g, "-")] || [];
    return uniqueValues([normalizedKey].concat(baseHints.map(normalizeValue)));
  }

  function looseName(value, categoryKey) {
    let normalized = normalizeValue(value);
    const normalizedCategory = normalizeValue(categoryKey);
    const prefixMap = {
      men: ["mens ", "men s ", "men "],
      women: ["womens ", "women s ", "women "],
      kids: ["kids ", "kid "]
    };
    const prefixes = prefixMap[normalizedCategory] || [];

    for (let index = 0; index < prefixes.length; index += 1) {
      if (normalized.startsWith(prefixes[index])) {
        normalized = normalized.slice(prefixes[index].length).trim();
        break;
      }
    }

    return normalized;
  }

  function buildIndex(products) {
    const bySku = Object.create(null);
    const byBarcode = Object.create(null);
    const byName = Object.create(null);
    const byCategoryName = Object.create(null);

    products.forEach(function (product) {
      const normalizedSku = normalizeValue(product.sku);
      const normalizedBarcode = normalizeValue(product.barcode);
      const normalizedName = normalizeValue(product.name);
      const normalizedCategory = normalizeValue(product.category);
      const normalizedLooseName = looseName(product.name, product.category);

      if (normalizedSku) {
        bySku[normalizedSku] = product;
      }

      if (normalizedBarcode) {
        byBarcode[normalizedBarcode] = product;
      }

      if (normalizedName) {
        if (!Array.isArray(byName[normalizedName])) {
          byName[normalizedName] = [];
        }
        byName[normalizedName].push(product);
      }

      if (normalizedName && normalizedCategory) {
        const key = normalizedCategory + "|" + normalizedName;
        if (!Array.isArray(byCategoryName[key])) {
          byCategoryName[key] = [];
        }
        byCategoryName[key].push(product);
      }

      if (normalizedLooseName && normalizedCategory) {
        const looseKey = normalizedCategory + "|" + normalizedLooseName;
        if (!Array.isArray(byCategoryName[looseKey])) {
          byCategoryName[looseKey] = [];
        }
        byCategoryName[looseKey].push(product);
      }
    });

    return {
      products: products,
      bySku: bySku,
      byBarcode: byBarcode,
      byName: byName,
      byCategoryName: byCategoryName
    };
  }

  function loadCatalog(forceRefresh) {
    if (catalogPromise && !forceRefresh) {
      return catalogPromise;
    }

    catalogPromise = window.fetch(ENDPOINT, {
      method: "GET",
      credentials: "same-origin",
      headers: {
        Accept: "application/json"
      }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error("live-pricing-http-" + response.status);
      }
      return response.json();
    }).then(function (payload) {
      const products = payload && Array.isArray(payload.products) ? payload.products : [];
      catalogIndex = buildIndex(products);
      return catalogIndex;
    }).catch(function (_error) {
      catalogIndex = buildIndex([]);
      return catalogIndex;
    });

    return catalogPromise;
  }

  function findProduct(details) {
    if (!catalogIndex) {
      return null;
    }

    const normalizedSku = normalizeValue(details && details.sku);
    if (normalizedSku && catalogIndex.bySku[normalizedSku]) {
      return catalogIndex.bySku[normalizedSku];
    }

    const normalizedBarcode = normalizeValue(details && details.barcode);
    if (normalizedBarcode && catalogIndex.byBarcode[normalizedBarcode]) {
      return catalogIndex.byBarcode[normalizedBarcode];
    }

    const normalizedName = normalizeValue(details && (details.name || details.baseTitle || details.title));
    if (!normalizedName) {
      return null;
    }
    const looseNormalizedName = looseName(details && (details.name || details.baseTitle || details.title), details && details.categoryKey);

    const hints = categoryHints(details && details.categoryKey);
    for (let index = 0; index < hints.length; index += 1) {
      const key = hints[index] + "|" + normalizedName;
      const matches = catalogIndex.byCategoryName[key];
      if (Array.isArray(matches) && matches.length) {
        return matches[0];
      }

      if (looseNormalizedName && looseNormalizedName !== normalizedName) {
        const looseKey = hints[index] + "|" + looseNormalizedName;
        const looseMatches = catalogIndex.byCategoryName[looseKey];
        if (Array.isArray(looseMatches) && looseMatches.length) {
          return looseMatches[0];
        }
      }
    }

    const byNameMatches = catalogIndex.byName[normalizedName];
    if (Array.isArray(byNameMatches) && byNameMatches.length) {
      return byNameMatches[0];
    }

    return null;
  }

  function getCardPricing(card, fallbackPrice, formatPrice) {
    const priceNumber = toPriceNumber(card && card.dataset ? card.dataset.effectivePriceEur : null);
    const basePriceNumber = toPriceNumber(card && card.dataset ? card.dataset.basePriceEur : null);
    const resolvedPrice = priceNumber !== null ? priceNumber : (toPriceNumber(fallbackPrice) || 0);
    const resolvedBasePrice = basePriceNumber !== null ? basePriceNumber : resolvedPrice;
    const formatter = typeof formatPrice === "function" ? formatPrice : defaultFormatPrice;

    return {
      priceNumber: resolvedPrice,
      basePriceNumber: resolvedBasePrice,
      priceText: formatter(resolvedPrice),
      basePriceText: formatter(resolvedBasePrice),
      isOnSale: resolvedBasePrice > resolvedPrice
    };
  }

  function renderPriceBlock(priceNode, pricing, formatPrice) {
    const formatter = typeof formatPrice === "function" ? formatPrice : defaultFormatPrice;
    const basePrice = toPriceNumber(pricing.price);
    const effectivePrice = toPriceNumber(pricing.effective_price);
    const isOnSale = Boolean(pricing.is_on_sale) && basePrice !== null && effectivePrice !== null && effectivePrice < basePrice;
    const caption = String(pricing.sale_caption || "").trim();

    priceNode.innerHTML = "";
    priceNode.classList.toggle("gf-live-price-block", isOnSale);
    if (basePrice !== null) {
      priceNode.dataset.baseEur = String(basePrice.toFixed(2));
    }
    if (effectivePrice !== null) {
      priceNode.dataset.effectiveEur = String(effectivePrice.toFixed(2));
    }
    priceNode.dataset.saleCaption = caption;

    if (!isOnSale || effectivePrice === null) {
      priceNode.textContent = formatter(effectivePrice !== null ? effectivePrice : basePrice || 0);
      return;
    }

    const row = document.createElement("span");
    row.className = "gf-live-price-row";

    const currentNode = document.createElement("span");
    currentNode.className = "gf-live-price-current";
    currentNode.textContent = formatter(effectivePrice);

    const originalNode = document.createElement("span");
    originalNode.className = "gf-live-price-original";
    originalNode.textContent = formatter(basePrice);

    row.appendChild(currentNode);
    row.appendChild(originalNode);
    priceNode.appendChild(row);

    if (caption) {
      const captionNode = document.createElement("span");
      captionNode.className = "gf-live-price-caption";
      captionNode.textContent = caption;
      priceNode.appendChild(captionNode);
    }
  }

  function applyBadge(card, pricing, badgeSelector) {
    const badge = card.querySelector(badgeSelector);
    if (!badge) {
      return;
    }

    if (!card.dataset.defaultBadgeText) {
      card.dataset.defaultBadgeText = badge.textContent || "";
    }

    if (pricing.is_on_sale) {
      badge.textContent = String(pricing.sale_badge || "SALE").trim() || "SALE";
      badge.hidden = false;
      card.classList.add("is-on-sale");
      return;
    }

    card.classList.remove("is-on-sale");
    if (badgeSelector === ".gf-shop-sale-badge") {
      badge.hidden = true;
      badge.textContent = "";
      return;
    }

    badge.textContent = card.dataset.defaultBadgeText;
  }

  function applyCard(card, details, options) {
    const priceNode = card.querySelector(options.priceSelector);
    if (!priceNode) {
      return null;
    }

    const titleNode = card.querySelector(options.titleSelector || ".gx25-title");

    const matchedProduct = findProduct(details);
    if (!matchedProduct) {
      return null;
    }

    const fallbackPrice = toPriceNumber(card.dataset.basePriceEur) || toPriceNumber(card.dataset.priceEur) || toPriceNumber(details.basePriceEur) || 0;
    const basePrice = toPriceNumber(matchedProduct.price);
    const effectivePrice = toPriceNumber(matchedProduct.effective_price);
    const resolvedBasePrice = basePrice !== null ? basePrice : fallbackPrice;
    const resolvedEffectivePrice = effectivePrice !== null ? effectivePrice : resolvedBasePrice;
    const pricing = Object.assign({}, matchedProduct, {
      price: resolvedBasePrice,
      effective_price: resolvedEffectivePrice,
      is_on_sale: Boolean(matchedProduct.is_on_sale) && resolvedEffectivePrice < resolvedBasePrice
    });
    const formatter = typeof options.formatPrice === "function" ? options.formatPrice : defaultFormatPrice;

    card.dataset.basePriceEur = resolvedBasePrice.toFixed(2);
    card.dataset.priceEur = resolvedEffectivePrice.toFixed(2);
    card.dataset.effectivePriceEur = resolvedEffectivePrice.toFixed(2);
    card.dataset.priceDisplay = formatter(resolvedEffectivePrice);

    if (titleNode && matchedProduct.name) {
      titleNode.textContent = matchedProduct.name;
    }

    renderPriceBlock(priceNode, pricing, formatter);
    applyBadge(card, pricing, options.badgeSelector);

    return pricing;
  }

  function applyCategoryCards(root, options) {
    const scope = root || document;
    const settings = options || {};

    return loadCatalog(false).then(function () {
      scope.querySelectorAll(".gx25-card").forEach(function (card) {
        applyCard(card, {
          categoryKey: settings.categoryKey,
          name: card.dataset.baseTitle || card.querySelector(".gx25-title")?.textContent || "",
          basePriceEur: card.dataset.basePriceEur || card.dataset.priceEur || 0
        }, {
          priceSelector: ".gx25-price",
          badgeSelector: ".gx25-badge",
          formatPrice: settings.formatPrice
        });
      });
    });
  }

  function applyShopCards(root) {
    const scope = root || document;

    return loadCatalog(false).then(function () {
      scope.querySelectorAll(".gf-shop-card").forEach(function (card) {
        applyCard(card, {
          categoryKey: card.dataset.categoryKey || "",
          name: card.dataset.productTitle || card.querySelector(".gf-shop-card-title")?.textContent || "",
          basePriceEur: card.dataset.basePriceEur || 0
        }, {
          priceSelector: ".gf-shop-card-price",
          badgeSelector: ".gf-shop-sale-badge",
          formatPrice: defaultFormatPrice
        });
      });
    });
  }

  window.GirffonLivePricing = {
    loadCatalog: loadCatalog,
    findProduct: findProduct,
    applyCategoryCards: applyCategoryCards,
    applyShopCards: applyShopCards,
    getCardPricing: getCardPricing,
    normalizeValue: normalizeValue
  };
})();

document.addEventListener("DOMContentLoaded", () => {
  const items = document.querySelectorAll(".menu-item");
  let activeBox = null;
  let timer;
  const COUNTER_MAX = 500;
  const COUNTER_SCHEMA_KEY = "gf-counter-schema";
  const COUNTER_SCHEMA_VERSION = "3";
  const CART_RESET_FIX_KEY = "gf-cart-reset-fix-v1";
  const REAL_CHECKOUT_URL = "CartTest.html";
  const TRACK_ORDER_URL = "TrackOrder.php";
  const STORAGE_KEYS = {
    cart: "girffon_cart",
    wishlist: "girffon_wishlist",
    orders: "girffon_orders"
  };
  const LEGACY_STORAGE_KEYS = {
    cart: [
      "gf-men-cart-items",
      "gf-women-cart-items",
      "gf-kids-cart-items",
      "gf-home-living-cart-items",
      "gf-accessories-cart-items",
      "gf-counter-cart"
    ],
    wishlist: [
      "gf-men-wishlist-items",
      "gf-women-wishlist-items",
      "gf-kids-wishlist-items",
      "gf-home-living-wishlist-items",
      "gf-accessories-wishlist-items",
      "gf-counter-wishlist"
    ]
  };

  function openMenu(box) {
    clearTimeout(timer);

    // اگر یکی باز است → فوری ببند
    if (activeBox && activeBox !== box) {
      activeBox.style.opacity = "0";
      activeBox.style.visibility = "hidden";
      activeBox.style.pointerEvents = "none";
    }

    box.style.opacity = "1";
    box.style.visibility = "visible";
    box.style.pointerEvents = "auto";

    activeBox = box;
  }

  function closeMenu(box) {
    timer = setTimeout(() => {
      box.style.opacity = "0";
      box.style.visibility = "hidden";
      box.style.pointerEvents = "none";
      activeBox = null;
    }, 1000); // 1 ثانیه
  }

  items.forEach((item) => {
    const box = item.querySelector(".menu-box");
    const trigger = item.querySelector(":scope > a");

    item.addEventListener("mouseenter", () => openMenu(box));

    item.addEventListener("mouseleave", () => closeMenu(box));

    box.addEventListener("mouseenter", () => clearTimeout(timer));

    box.addEventListener("mouseleave", () => closeMenu(box));

    trigger.addEventListener("click", (e) => {
      const href = String(trigger.getAttribute("href") || "").trim();
      if (href === "" || href === "#") {
        e.preventDefault();
        openMenu(box);
        return;
      }

      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return;
      }

      e.preventDefault();
      window.location.href = href;
    });
  });

  function safeReadArray(key) {
    try {
      const raw = localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_err) {
      return [];
    }
  }

  function safeWriteArray(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (_err) {
      // Ignore localStorage failures.
    }
  }

  function getStoredOrders() {
    return safeReadArray(STORAGE_KEYS.orders);
  }

  function toSafeCount(value) {
    const parsed = Number.parseInt(String(value || "0"), 10);
    if (!Number.isFinite(parsed) || parsed < 0) {
      return 0;
    }
    return Math.min(parsed, COUNTER_MAX);
  }

  function updateBadge(badge, value) {
    if (!badge) {
      return;
    }
    badge.textContent = String(toSafeCount(value));
  }

  function getTopLinks() {
    const container = document.querySelector(".top-actions");
    return {
      cartLink:
        document.getElementById("gfCartTrigger") ||
        container?.querySelector(".fa-cart-shopping")?.closest("a") ||
        null,
      wishlistLink:
        document.getElementById("gfWishlistTrigger") ||
        container?.querySelector(".fa-heart")?.closest("a") ||
        null
    };
  }

  function getCollectionItems(storageKey) {
    return safeReadArray(storageKey);
  }

  function getCollectionCount(storageKey) {
    const items = getCollectionItems(storageKey);
    if (storageKey === STORAGE_KEYS.cart) {
      return items.reduce((sum, item) => sum + (Number(item.qty) || 1), 0);
    }
    return items.length;
  }

  function collectionItemKey(storageKey, item) {
    if (!item || typeof item !== "object") {
      return "";
    }
    const baseId = String(item.id || item.title || item.name || item.image || "");
    if (storageKey === STORAGE_KEYS.cart) {
      return baseId + "::" + String(item.color || "");
    }
    return baseId;
  }

  function mergeLegacyCollection(targetKey, sourceKeys) {
    const existing = getCollectionItems(targetKey);
    const seen = new Set(existing.map((item) => collectionItemKey(targetKey, item)));
    let changed = false;

    sourceKeys.forEach((legacyKey) => {
      if (legacyKey === targetKey) {
        return;
      }

      const legacyItems = safeReadArray(legacyKey);
      if (!legacyItems.length) {
        localStorage.removeItem(legacyKey);
        return;
      }

      legacyItems.forEach((item) => {
        const key = collectionItemKey(targetKey, item);
        if (!key) {
          return;
        }

        const existingIndex = existing.findIndex((entry) => collectionItemKey(targetKey, entry) === key);
        if (existingIndex >= 0) {
          if (targetKey === STORAGE_KEYS.cart) {
            existing[existingIndex].qty = Math.max(Number(existing[existingIndex].qty) || 1, Number(item.qty) || 1);
            changed = true;
          }
          return;
        }

        existing.push(item);
        seen.add(key);
        changed = true;
      });

      localStorage.removeItem(legacyKey);
    });

    if (changed) {
      safeWriteArray(targetKey, existing);
    }
  }

  function runCounterMigration() {
    const savedSchema = localStorage.getItem(COUNTER_SCHEMA_KEY);
    if (savedSchema === COUNTER_SCHEMA_VERSION) {
      return;
    }

    mergeLegacyCollection(STORAGE_KEYS.cart, LEGACY_STORAGE_KEYS.cart);
    mergeLegacyCollection(STORAGE_KEYS.wishlist, LEGACY_STORAGE_KEYS.wishlist);
    localStorage.setItem(COUNTER_SCHEMA_KEY, COUNTER_SCHEMA_VERSION);
  }

  function runCartResetFixOnce() {
    if (localStorage.getItem(CART_RESET_FIX_KEY) === "1") {
      return;
    }

    safeWriteArray(STORAGE_KEYS.cart, []);
    LEGACY_STORAGE_KEYS.cart.forEach((legacyKey) => {
      localStorage.removeItem(legacyKey);
    });
    localStorage.setItem(CART_RESET_FIX_KEY, "1");
  }

  async function refreshCartCountFromSession() {
    const { cartLink } = getTopLinks();
    const badge = cartLink?.querySelector(".count-badge");
    if (!badge) {
      return;
    }

    if (!window.GirffonCartApi || typeof window.GirffonCartApi.getCart !== "function") {
      updateBadge(badge, getCollectionCount(STORAGE_KEYS.cart));
      return;
    }

    try {
      if (typeof window.GirffonCartApi.awaitPendingMutations === "function") {
        await window.GirffonCartApi.awaitPendingMutations();
      }

      const cart = await window.GirffonCartApi.getCart();
      updateBadge(badge, cart?.summary?.itemCount || 0);
    } catch (_error) {
      updateBadge(badge, 0);
    }
  }

  function refreshTopCounts() {
    const { wishlistLink } = getTopLinks();
    refreshCartCountFromSession();
    updateBadge(wishlistLink?.querySelector(".count-badge"), getCollectionCount(STORAGE_KEYS.wishlist));
  }

  function bindCounter(link, storageKey) {
    if (!link) {
      return;
    }

    const badge = link.querySelector(".count-badge");
    if (!badge) {
      return;
    }

    if (
      storageKey === STORAGE_KEYS.cart &&
      window.GirffonCartApi &&
      typeof window.GirffonCartApi.getCart === "function"
    ) {
      refreshCartCountFromSession();
      return;
    }

    updateBadge(badge, getCollectionCount(storageKey));
  }

  function bindTopDestinations() {
    const { cartLink, wishlistLink } = getTopLinks();
    if (cartLink) {
      cartLink.setAttribute("href", REAL_CHECKOUT_URL);
      cartLink.setAttribute("title", "Open cart page");
      cartLink.setAttribute("aria-label", "Open cart page");
      cartLink.addEventListener("click", async function (event) {
        if (document.body?.classList.contains("cart-test-page")) {
          return;
        }

        event.preventDefault();

        if (window.GirffonCartApi && typeof window.GirffonCartApi.awaitPendingMutations === "function") {
          try {
            await window.GirffonCartApi.awaitPendingMutations();
          } catch (_error) {
            // Continue to cart page even if a pending cart request failed.
          }
        }

        window.location.href = REAL_CHECKOUT_URL;
      });
    }

    if (wishlistLink) {
      wishlistLink.setAttribute("href", "WishlistPage.html");
      wishlistLink.setAttribute("title", "Open wishlist page");
      wishlistLink.setAttribute("aria-label", "Open wishlist page");
    }
  }

  function redirectToRealCheckout(event) {
    if (document.body?.classList.contains("cart-test-page")) {
      return;
    }

    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    window.location.href = REAL_CHECKOUT_URL;
  }

  function disableLegacyCheckoutFlows() {
    if (document.body?.classList.contains("cart-test-page")) {
      return;
    }

    const legacyTriggers = document.querySelectorAll("#openBankModal, .checkout-btn, [data-gf-checkout], [data-gf-action='checkout']");
    legacyTriggers.forEach((element) => {
      element.addEventListener("click", redirectToRealCheckout);
    });

    const legacyForm = document.getElementById("bankForm");
    if (legacyForm) {
      legacyForm.addEventListener("submit", redirectToRealCheckout);
    }
  }

  function openTrackOrderPage(orderNumber) {
    const nextUrl = new URL(TRACK_ORDER_URL, window.location.href);
    if (typeof orderNumber === "string" && orderNumber.trim()) {
      nextUrl.searchParams.set("order_number", orderNumber.trim());
    }
    window.location.href = nextUrl.toString();
  }

  function closeTrackModal() {
    // Track order now opens a dedicated page, but footer panel callers still
    // expect a close hook to exist.
  }

  function extractCardItem(card) {
    if (!card) {
      return null;
    }

    const image = card.querySelector(".gx25-main-image")?.getAttribute("src") || "";
    const title = card.querySelector(".gx25-title")?.textContent?.trim() || "Product";
    const priceText = card.dataset.priceDisplay || card.querySelector(".gx25-price")?.textContent || "0";
    const activeColor = card.querySelector(".gx25-color.active")?.dataset.color || "";
    const stableSku = card.dataset.productSku || card.dataset.sku || "";
    const itemId = stableSku || card.dataset.productId || title.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    const datasetPrice = Number.parseFloat(String(card.dataset.effectivePriceEur || card.dataset.priceEur || ""));
    const priceNumber = Number.isFinite(datasetPrice)
      ? datasetPrice
      : (Number.parseFloat(String(priceText).replace(/[^0-9.,]/g, "").replace(",", ".")) || 0);

    return {
      id: itemId,
      sku: itemId,
      code: itemId,
      title: title,
      image: image,
      price: priceText,
      priceNumber: priceNumber,
      color: activeColor,
      qty: 1
    };
  }

  function fallbackToggleWishlist(card, shouldBeActive) {
    const item = extractCardItem(card);
    if (!item) {
      return;
    }

    const wishlistItems = getCollectionItems(STORAGE_KEYS.wishlist);
    const index = wishlistItems.findIndex((entry) => entry.id === item.id);

    if (shouldBeActive && index === -1) {
      wishlistItems.push(item);
      safeWriteArray(STORAGE_KEYS.wishlist, wishlistItems);
    }

    if (!shouldBeActive && index >= 0) {
      wishlistItems.splice(index, 1);
      safeWriteArray(STORAGE_KEYS.wishlist, wishlistItems);
    }
  }

  function fallbackAddCart(card) {
    const item = extractCardItem(card);
    if (!item) {
      return;
    }

    const cartItems = getCollectionItems(STORAGE_KEYS.cart);
    const index = cartItems.findIndex((entry) => entry.id === item.id && String(entry.color || "") === String(item.color || ""));

    if (index >= 0) {
      cartItems[index].qty = (Number(cartItems[index].qty) || 1) + 1;
    } else {
      cartItems.push(item);
    }

    safeWriteArray(STORAGE_KEYS.cart, cartItems);
  }

  const { cartLink, wishlistLink } = getTopLinks();

  runCounterMigration();
  runCartResetFixOnce();

  bindCounter(cartLink, STORAGE_KEYS.cart);
  bindCounter(wishlistLink, STORAGE_KEYS.wishlist);
  bindTopDestinations();
  disableLegacyCheckoutFlows();
  refreshTopCounts();

  const trackTrigger = document.getElementById("gfTrackTrigger");
  const faqTrigger = document.getElementById("gfFaqTrigger");
  const faqModal = document.getElementById("gfFaqModal");
  const faqOverlay = document.getElementById("gfFaqOverlay");
  const faqClose = document.getElementById("gfFaqClose");
  const faqList = document.getElementById("gfFaqList");
  const returnTrigger = document.getElementById("gfReturnTrigger");
  const returnModal = document.getElementById("gfReturnModal");
  const returnOverlay = document.getElementById("gfReturnOverlay");
  const returnClose = document.getElementById("gfReturnClose");
  let lastFaqFocusedElement = null;
  let lastReturnFocusedElement = null;

  function isFaqOpen() {
    return Boolean(faqModal && faqModal.dataset.visible === "true");
  }

  function isReturnOpen() {
    return Boolean(returnModal && returnModal.dataset.visible === "true");
  }

  function refreshFooterPanelBodyState() {
    const isContactOpen = Boolean(window.gfContactPanel?.isOpen?.());

    if (isContactOpen || isFaqOpen() || isReturnOpen()) {
      document.body.classList.add("gf-footer-panel-open");
    } else {
      document.body.classList.remove("gf-footer-panel-open");
    }
  }

  function t(key, fallback) {
    const localeTexts = window.gfLocaleTexts || {};
    if (typeof localeTexts[key] === "string") {
      return localeTexts[key];
    }
    return fallback;
  }

  function closeFaqModal(restoreFocus = true) {
    if (!faqModal || !faqOverlay) {
      return;
    }

    faqOverlay.hidden = true;
    faqModal.dataset.visible = "false";
    faqModal.setAttribute("aria-hidden", "true");
    refreshFooterPanelBodyState();

    if (restoreFocus && lastFaqFocusedElement && typeof lastFaqFocusedElement.focus === "function") {
      lastFaqFocusedElement.focus();
    }
  }

  function closeReturnModal(restoreFocus = true) {
    if (!returnModal || !returnOverlay) {
      return;
    }

    returnOverlay.hidden = true;
    returnModal.dataset.visible = "false";
    returnModal.setAttribute("aria-hidden", "true");
    refreshFooterPanelBodyState();

    if (
      restoreFocus &&
      lastReturnFocusedElement &&
      typeof lastReturnFocusedElement.focus === "function"
    ) {
      lastReturnFocusedElement.focus();
    }
  }

  function closeAllFaqAnswers() {
    if (!faqList) {
      return;
    }

    faqList.querySelectorAll(".gf-faq-question").forEach((questionButton) => {
      questionButton.setAttribute("aria-expanded", "false");
    });

    faqList.querySelectorAll(".gf-faq-answer").forEach((answerPanel) => {
      answerPanel.hidden = true;
    });
  }

  function openFaqModal() {
    if (!faqModal || !faqOverlay) {
      return;
    }

    window.gfContactPanel?.close?.(false);
    closeReturnModal(false);
    closeTrackModal(false);

    lastFaqFocusedElement = document.activeElement;
    faqOverlay.hidden = false;
    faqModal.dataset.visible = "true";
    faqModal.setAttribute("aria-hidden", "false");
    refreshFooterPanelBodyState();

    window.setTimeout(() => {
      faqModal.querySelector(".gf-faq-question")?.focus();
    }, 0);
  }

  function openReturnModal() {
    if (!returnModal || !returnOverlay) {
      return;
    }

    window.gfContactPanel?.close?.(false);
    closeFaqModal(false);
    closeTrackModal(false);

    lastReturnFocusedElement = document.activeElement;
    returnOverlay.hidden = false;
    returnModal.dataset.visible = "true";
    returnModal.setAttribute("aria-hidden", "false");
    refreshFooterPanelBodyState();

    window.setTimeout(() => {
      returnClose?.focus();
    }, 0);
  }

  function validateTrackForm() {
    if (!trackOrderNumber || !trackEmail) {
      return false;
    }

    const orderNumberValue = trackOrderNumber.value.trim();
    const emailValue = trackEmail.value.trim();

    setTrackInputInvalid(trackOrderNumber, false);
    setTrackInputInvalid(trackEmail, false);

    if (!orderNumberValue || !emailValue) {
      if (!orderNumberValue) {
        setTrackInputInvalid(trackOrderNumber, true);
      }
      if (!emailValue) {
        setTrackInputInvalid(trackEmail, true);
      }
      setTrackStatus("error", t("formRequiredError", "Please fill in all required fields."));
      return false;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailPattern.test(emailValue)) {
      setTrackInputInvalid(trackEmail, true);
      setTrackStatus("error", t("formEmailError", "Please enter a valid email address."));
      return false;
    }

    return true;
  }

  function getMockTrackResponse(orderNumber, email) {
    const normalizedOrder = orderNumber.trim().toLowerCase();
    const normalizedEmail = email.trim().toLowerCase();

    const storedMatch = getStoredOrders().find(function (order) {
      const reference = String(order?.reference || order?.orderNumber || "").trim().toLowerCase();
      const orderEmail = String(order?.email || "").trim().toLowerCase();
      return reference === normalizedOrder && orderEmail === normalizedEmail;
    });

    if (storedMatch) {
      return {
        type: "success",
        status: storedMatch.status || "Processing",
        orderNumber: storedMatch.reference || storedMatch.orderNumber || orderNumber.trim().toUpperCase()
      };
    }

    if (normalizedOrder.includes("err") || normalizedEmail.includes("error")) {
      return { type: "error" };
    }

    if (
      normalizedOrder.includes("not") ||
      normalizedOrder.includes("none") ||
      normalizedOrder.includes("404")
    ) {
      return { type: "not-found" };
    }

    const statuses = ["Pending", "Processing", "Shipped", "Delivered"];
    const key = `${normalizedOrder}|${normalizedEmail}`;
    const score = Array.from(key).reduce((sum, char) => sum + char.charCodeAt(0), 0);
    const status = statuses[score % statuses.length];

    return {
      type: "success",
      status,
      orderNumber: orderNumber.trim().toUpperCase()
    };
  }

  window.gfFooterPanels = {
    closeTrackModal,
    closeFaqModal,
    closeReturnModal,
    isFaqOpen,
    isReturnOpen,
    refreshFooterPanelBodyState,
    t
  };

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
      return;
    }

    if (window.gfContactPanel?.isOpen?.()) {
      window.gfContactPanel.close();
    }
    if (isFaqOpen()) {
      closeFaqModal();
    }
    if (isReturnOpen()) {
      closeReturnModal();
    }
  });

  if (trackTrigger) {
    trackTrigger.setAttribute("href", TRACK_ORDER_URL);
    trackTrigger.setAttribute("title", "Open track order page");
    trackTrigger.setAttribute("aria-label", "Open track order page");

    trackTrigger.addEventListener("click", (event) => {
      event.preventDefault();
      openTrackOrderPage();
    });
  }

  if (faqTrigger && faqModal && faqOverlay && faqClose && faqList) {
    faqTrigger.addEventListener("click", (event) => {
      event.preventDefault();
      openFaqModal();
    });

    faqClose.addEventListener("click", () => closeFaqModal(true));
    faqOverlay.addEventListener("click", () => closeFaqModal(true));

    faqList.addEventListener("click", (event) => {
      const questionButton = event.target.closest(".gf-faq-question");
      if (!questionButton) {
        return;
      }

      const answerPanel = questionButton.nextElementSibling;
      const willExpand = questionButton.getAttribute("aria-expanded") !== "true";

      closeAllFaqAnswers();

      if (willExpand && answerPanel) {
        questionButton.setAttribute("aria-expanded", "true");
        answerPanel.hidden = false;
      }
    });
  }

  if (returnTrigger && returnModal && returnOverlay && returnClose) {
    returnTrigger.addEventListener("click", (event) => {
      event.preventDefault();
      openReturnModal();
    });

    returnClose.addEventListener("click", () => closeReturnModal(true));
    returnOverlay.addEventListener("click", () => closeReturnModal(true));
  }

  // Sync product interactions with top counters.
  document.addEventListener("click", (event) => {
    const favButton = event.target.closest(".gx25-fav");
    if (favButton) {
      const beforeCount = getCollectionCount(STORAGE_KEYS.wishlist);
      const card = favButton.closest(".gx25-card");
      window.setTimeout(() => {
        const afterCount = getCollectionCount(STORAGE_KEYS.wishlist);
        if (afterCount === beforeCount) {
          fallbackToggleWishlist(card, favButton.classList.contains("active"));
        }
        refreshTopCounts();
      }, 0);
      return;
    }

    const enterButton = event.target.closest(".gx25-enter");
    if (enterButton) {
      if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function") {
        window.setTimeout(async () => {
          if (typeof window.GirffonCartApi.awaitPendingMutations === "function") {
            try {
              await window.GirffonCartApi.awaitPendingMutations();
            } catch (_error) {
              // Ignore refresh timing errors and leave the API response handler to surface failures.
            }
          }

          refreshTopCounts();
        }, 0);
        return;
      }

      const beforeCount = getCollectionCount(STORAGE_KEYS.cart);
      const card = enterButton.closest(".gx25-card");
      window.setTimeout(() => {
        const afterCount = getCollectionCount(STORAGE_KEYS.cart);
        if (afterCount === beforeCount) {
          fallbackAddCart(card);
        }
        refreshTopCounts();
      }, 0);
    }
  });
});