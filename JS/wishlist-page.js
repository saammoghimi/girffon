document.addEventListener("DOMContentLoaded", function () {
  const wishlistGrid = document.getElementById("gfWishlistGrid");
  const wishlistSummary = document.getElementById("gfWishlistSummary");
  const continueButton = document.getElementById("gfWishlistContinueBtn");
  const cartButton = document.getElementById("gfWishlistOpenCartBtn");
  const WISHLIST_KEY = "girffon_wishlist";
  const CART_STORAGE_KEY = "girffon_cart";
  const CART_URL = "CartTest.html";
  const WISHLIST_TRANSFER_KEY = "girffon_wishlist_cart_transfer";

  if (!wishlistGrid || !wishlistSummary) {
    return;
  }

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
      // Ignore local storage write failures.
    }
  }

  function getCartApi() {
    return window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function"
      ? window.GirffonCartApi
      : null;
  }

  function formatPrice(value) {
    const numeric = Number(value) || 0;
    return new Intl.NumberFormat("en-GB", {
      style: "currency",
      currency: "EUR",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(numeric);
  }

  function readWishlist() {
    return safeReadArray(WISHLIST_KEY);
  }

  function writePendingCartTransfer(items) {
    try {
      sessionStorage.setItem(WISHLIST_TRANSFER_KEY, JSON.stringify(Array.isArray(items) ? items : []));
    } catch (_error) {
      // Ignore storage failures.
    }
  }

  async function syncBadges() {
    const wishlistCount = readWishlist().length;
    const wishlistBadge = document.querySelector("#gfWishlistTrigger .count-badge");
    const cartBadge = document.querySelector("#gfCartTrigger .count-badge");
    if (wishlistBadge) wishlistBadge.textContent = String(wishlistCount);
    if (!cartBadge) {
      return;
    }

    const cartApi = getCartApi();
    if (!cartApi || typeof cartApi.getCart !== "function") {
      cartBadge.textContent = "0";
      return;
    }

    try {
      const cart = await cartApi.getCart();
      cartBadge.textContent = String((cart.summary && cart.summary.itemCount) || 0);
    } catch (_error) {
      cartBadge.textContent = "0";
    }
  }

  async function addToCart(item) {
    const cartApi = getCartApi();

    const nextItem = {
      id: item.id || item.title || item.name,
      name: item.name || item.title || "GirffoN Product",
      image: item.image || item.img || "Cart/products/tshirt-men/Men france/Bk/80.jpg",
      price: item.price || item.priceNumber || 0,
      color: item.color || "",
      size: item.size || "One Size",
      quantity: 1
    };

    if (cartApi) {
      await cartApi.addItem(nextItem);
      await syncBadges();
      return;
    }

    const existingCart = safeReadArray(CART_STORAGE_KEY);
    const existingIndex = existingCart.findIndex(function (entry) {
      return String(entry.code || entry.sku || entry.id || entry.title || entry.name || "") === String(nextItem.id)
        && String(entry.color || "") === String(nextItem.color || "")
        && String(entry.size || "") === String(nextItem.size || "");
    });

    if (existingIndex >= 0) {
      const currentQty = Number(existingCart[existingIndex].qty != null ? existingCart[existingIndex].qty : existingCart[existingIndex].quantity) || 1;
      existingCart[existingIndex].qty = currentQty + 1;
      existingCart[existingIndex].quantity = currentQty + 1;
    } else {
      existingCart.push({
        id: nextItem.id,
        sku: nextItem.id,
        code: nextItem.id,
        name: nextItem.name,
        title: nextItem.name,
        image: nextItem.image,
        img: nextItem.image,
        price: Number(nextItem.price) || 0,
        priceNumber: Number(nextItem.price) || 0,
        color: nextItem.color,
        size: nextItem.size,
        qty: 1,
        quantity: 1
      });
    }

    safeWriteArray(CART_STORAGE_KEY, existingCart);
    await syncBadges();
  }

  async function moveAllToCartAndOpen() {
    const wishlistItems = readWishlist();
    writePendingCartTransfer(wishlistItems);
    if (!wishlistItems.length) {
      window.location.href = CART_URL + "?source=wishlist";
      return;
    }

    try {
      for (const item of wishlistItems) {
        await addToCart(item);
      }

      safeWriteArray(WISHLIST_KEY, []);
      await syncBadges();
      window.location.href = CART_URL + "?source=wishlist";
    } catch (_error) {
      window.location.href = CART_URL + "?source=wishlist";
    }
  }

  async function moveToCartAndOpen(itemId) {
    const wishlistItems = readWishlist();
    const item = wishlistItems.find(function (entry) {
      return String(entry.id || entry.title || entry.name || "") === String(itemId);
    });

    if (!item) {
      return;
    }

    writePendingCartTransfer([item]);

    try {
      await addToCart(item);
      safeWriteArray(
        WISHLIST_KEY,
        wishlistItems.filter(function (entry) {
          return String(entry.id || entry.title || entry.name || "") !== String(itemId);
        })
      );
      await syncBadges();
      window.location.href = CART_URL + "?source=wishlist";
    } catch (_error) {
      window.location.href = CART_URL + "?source=wishlist";
    }
  }

  function removeFromWishlist(itemId) {
    const nextItems = readWishlist().filter(function (item) {
      return String(item.id || item.title || item.name || "") !== String(itemId);
    });
    safeWriteArray(WISHLIST_KEY, nextItems);
    renderWishlist();
  }

  function emptyStateMarkup() {
    return [
      '<div class="gf-wishlist-empty">',
      '<i class="fa-solid fa-heart-crack"></i>',
      '<h3>Your wishlist is empty</h3>',
      '<p>Save the products you love and come back here for a clean premium shortlist.</p>',
      '<button type="button" class="gf-wishlist-btn gf-wishlist-btn-primary" id="gfWishlistBrowseBtn">Browse Catalog</button>',
      '</div>'
    ].join("");
  }

  function cardMarkup(item) {
    const itemId = item.id || item.title || item.name || "girffon-item";
    const title = item.title || item.name || "GirffoN Product";
    const image = item.image || item.img || "Cart/products/tshirt-men/Men france/Bk/80.jpg";
    const category = item.section || item.category || "Saved Favorite";
    const color = item.colorName || item.color || "Ready to choose";
    const size = item.size || "One Size";
    const price = formatPrice(item.priceNumber || item.price || 0);

    return [
      '<article class="gf-wishlist-card" data-item-id="', itemId, '">',
      '<img class="gf-wishlist-card-image" src="', image, '" alt="', title, '">',
      '<div class="gf-wishlist-card-body">',
      '<div class="gf-wishlist-card-top">',
      '<h3>', title, '</h3>',
      '<span class="gf-wishlist-chip">', category, '</span>',
      '</div>',
      '<div class="gf-wishlist-meta"><span>Color: ', color, '</span><span>Size: ', size, '</span></div>',
      '<div class="gf-wishlist-price">', price, '</div>',
      '<div class="gf-wishlist-card-actions">',
      '<button type="button" class="gf-wishlist-add-cart">Move to Cart</button>',
      '<button type="button" class="gf-wishlist-remove">Remove</button>',
      '</div>',
      '</div>',
      '</article>'
    ].join("");
  }

  function renderWishlist() {
    const items = readWishlist();
    wishlistSummary.textContent = items.length + (items.length === 1 ? " saved product ready for your next order." : " saved products ready for your next order.");
    wishlistGrid.innerHTML = items.length ? items.map(cardMarkup).join("") : emptyStateMarkup();
    void syncBadges();

    const browseButton = document.getElementById("gfWishlistBrowseBtn");
    if (browseButton) {
      browseButton.addEventListener("click", function () {
        window.location.href = "catalog.html";
      });
    }
  }

  wishlistGrid.addEventListener("click", function (event) {
    const card = event.target.closest(".gf-wishlist-card");
    if (!card) {
      return;
    }

    const itemId = card.dataset.itemId;
    const items = readWishlist();
    const item = items.find(function (entry) {
      return String(entry.id || entry.title || entry.name || "") === String(itemId);
    });

    if (!item) {
      return;
    }

    if (event.target.closest(".gf-wishlist-add-cart")) {
      void moveToCartAndOpen(itemId);
      return;
    }

    if (event.target.closest(".gf-wishlist-remove")) {
      removeFromWishlist(itemId);
    }
  });

  if (continueButton) {
    continueButton.addEventListener("click", function () {
      window.location.href = "catalog.html";
    });
  }

  if (cartButton) {
    cartButton.addEventListener("click", function () {
      void moveAllToCartAndOpen();
    });
  }

  renderWishlist();
});