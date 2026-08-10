document.addEventListener('DOMContentLoaded', function () {
  const container = document.getElementById('gfShopSections');
  if (!container) {
    return;
  }

  const CART_KEY = 'girffon_cart';
  const WISHLIST_KEY = 'girffon_wishlist';
  const COLOR_MAP = {
    Bk: '#000000',
    Bl: '#2d3a56',
    Bu: '#4a6fdc',
    Gr: '#6f7d5c',
    Gy: '#bdbdbd',
    Ka: '#caa27f',
    Na: '#f1d4a5',
    Or: '#d68c8c',
    Pp: '#d9b3d9',
    Rd: '#c43b3b',
    Wh: '#ffffff',
    Ye: '#f2c94c'
  };
  const COLOR_CODES = Object.keys(COLOR_MAP);

  const SHOP_SECTIONS = [
    {
      key: 'men',
      title: 'MEN',
      href: 'men.html',
      tag: 'GirffoN Menswear',
      products: [
        { id: 'men-france-1', title: 'France T-Shirt', priceEur: 200, folderTemplate: 'Cart/products/tshirt-men/Men france/{color}/', href: 'FR-MEN-001.html' },
        { id: 'men-italy-2', title: 'Italy T-Shirt', priceEur: 220, folderTemplate: 'Cart/products/tshirt-men/men italy/{color}/', href: 'IT-MEN-002.html' },
        { id: 'men-usa-3', title: 'USA T-Shirt', priceEur: 230, folderTemplate: 'Cart/products/tshirt-men/men usa/{color}/', href: 'men.html' },
        { id: 'men-women-france-4', title: 'Women France T-Shirt', priceEur: 220, folderTemplate: 'Cart/products/tshirt-women/women france/{color}/', href: 'men.html' }
      ]
    },
    {
      key: 'women',
      title: 'WOMEN',
      href: 'woman.html',
      tag: 'GirffoN Womenswear',
      products: [
        { id: 'women-france-1', title: 'Women France T-Shirt', priceEur: 220, folderTemplate: 'Cart/products/tshirt-women/women france/{color}/', href: 'woman.html' },
        { id: 'women-italy-2', title: 'Women Italy T-Shirt', priceEur: 220, folderTemplate: 'Cart/products/tshirt-women/women italy/{color}/', href: 'woman.html' },
        { id: 'women-japan-3', title: 'Women Japan T-Shirt', priceEur: 220, folderTemplate: 'Cart/products/tshirt-women/Women japon/{color}/', href: 'woman.html' },
        { id: 'women-france-premium-4', title: 'Women France Premium', priceEur: 230, folderTemplate: 'Cart/products/tshirt-women/women france/{color}/', href: 'woman.html' }
      ]
    },
    {
      key: 'kids-babies',
      title: 'KIDS & BABIES',
      href: 'kids.html',
      tag: 'GirffoN Kidswear',
      products: [
        { id: 'kids-teddy-1', title: 'Teddy Bear Tee', priceEur: 180, folderTemplate: 'Cart/products/tshirt-men/Men france/{color}/', href: 'kids.html' },
        { id: 'kids-fun-2', title: 'Fun Park Tee', priceEur: 185, folderTemplate: 'Cart/products/tshirt-men/men italy/{color}/', href: 'kids.html' },
        { id: 'kids-unicorn-3', title: 'Unicorn Dream Tee', priceEur: 190, folderTemplate: 'Cart/products/tshirt-men/men usa/{color}/', href: 'kids.html' },
        { id: 'kids-dino-4', title: 'Dino Squad Tee', priceEur: 195, folderTemplate: 'Cart/products/tshirt-women/women france/{color}/', href: 'kids.html' }
      ]
    },
    {
      key: 'accessories',
      title: 'ACCESSORIES',
      href: 'accessories.html',
      tag: 'GirffoN Accessories',
      products: [
        { id: 'accessories-cap-1', title: 'Flexfit Cap', priceEur: 65, folderTemplate: 'Cart/products/tshirt-men/Men france/{color}/', href: 'accessories.html' },
        { id: 'accessories-tote-2', title: 'Classic Tote Bag', priceEur: 55, folderTemplate: 'Cart/products/tshirt-men/men italy/{color}/', href: 'accessories.html' },
        { id: 'accessories-bottle-3', title: 'Bottle Design', priceEur: 45, folderTemplate: 'Cart/products/tshirt-men/men usa/{color}/', href: 'accessories.html' },
        { id: 'accessories-phone-4', title: 'Phone Case', priceEur: 39, folderTemplate: 'Cart/products/tshirt-women/women france/{color}/', href: 'accessories.html' }
      ]
    },
    {
      key: 'home-living',
      title: 'HOME & LIVING',
      href: 'home-living.html',
      tag: 'GirffoN Home Living',
      products: [
        { id: 'home-cushion-1', title: 'Cushion Cover', priceEur: 49, folderTemplate: 'Cart/products/tshirt-men/Men france/{color}/', href: 'home-living.html' },
        { id: 'home-mug-2', title: 'Ceramic Mug', priceEur: 39, folderTemplate: 'Cart/products/tshirt-men/men italy/{color}/', href: 'home-living.html' },
        { id: 'home-coasters-3', title: 'Coasters Set', priceEur: 29, folderTemplate: 'Cart/products/tshirt-men/men usa/{color}/', href: 'home-living.html' },
        { id: 'home-poster-4', title: 'Wall Art Poster', priceEur: 59, folderTemplate: 'Cart/products/tshirt-women/women france/{color}/', href: 'home-living.html' }
      ]
    }
  ];

  function safeReadArray(key) {
    try {
      const raw = window.localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function safeWriteArray(key, value) {
    try {
      window.localStorage.setItem(key, JSON.stringify(Array.isArray(value) ? value : []));
    } catch (_error) {
      // Ignore storage failures.
    }
  }

  function getWishlistItems() {
    return safeReadArray(WISHLIST_KEY);
  }

  function getCartItems() {
    return safeReadArray(CART_KEY);
  }

  function setBadgeCount(triggerId, count) {
    const trigger = document.getElementById(triggerId);
    const badge = trigger ? trigger.querySelector('.count-badge') : null;
    if (badge) {
      badge.textContent = String(count);
    }
  }

  async function updateTopCounts() {
    const localCartCount = getCartItems().reduce(function (sum, item) {
      return sum + (Number(item.qty) || 1);
    }, 0);

    if (window.GirffonCartApi && typeof window.GirffonCartApi.getCart === 'function') {
      try {
        const cart = await window.GirffonCartApi.getCart();
        setBadgeCount('gfCartTrigger', cart && cart.summary ? (cart.summary.itemCount || 0) : 0);
      } catch (_error) {
        setBadgeCount('gfCartTrigger', localCartCount);
      }
    } else {
      setBadgeCount('gfCartTrigger', localCartCount);
    }

    setBadgeCount('gfWishlistTrigger', getWishlistItems().length);
  }

  function formatPrice(value) {
    return 'EUR ' + Number(value || 0).toFixed(2);
  }

  function buildImages(folderTemplate, colorCode) {
    const folder = String(folderTemplate || '').replace('{color}', colorCode);
    return [
      folder + '400.jpg',
      folder + '400-1.jpg',
      folder + '400-2.jpg',
      folder + '400-3.jpg'
    ];
  }

  function cardImageForProduct(product, colorCode) {
    return buildImages(product.folderTemplate, colorCode)[0];
  }

  function isWishlisted(productId) {
    return getWishlistItems().some(function (item) {
      return String(item && item.id || '') === String(productId || '');
    });
  }

  function buildWishlistItem(section, product, colorCode, image) {
    return {
      id: product.id,
      title: product.title,
      name: product.title,
      image: image,
      img: image,
      price: formatPrice(product.priceEur),
      priceNumber: Number(product.priceEur || 0),
      color: colorCode,
      category: section.title,
      href: product.href
    };
  }

  function buildCartPayload(section, product, colorCode, image) {
    return {
      id: product.id,
      sku: product.id,
      name: product.title,
      title: product.title,
      price: Number(product.priceEur || 0),
      priceNumber: Number(product.priceEur || 0),
      image: image,
      img: image,
      size: 'One Size',
      color: colorCode,
      quantity: 1,
      qty: 1,
      category: section.title,
      href: product.href
    };
  }

  async function addToCart(section, product, colorCode, image) {
    const payload = buildCartPayload(section, product, colorCode, image);

    if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === 'function') {
      await window.GirffonCartApi.addItem({
        id: payload.id,
        sku: payload.sku,
        name: payload.name,
        price: payload.price,
        image: payload.image,
        size: payload.size,
        color: payload.color,
        quantity: 1
      });
      await updateTopCounts();
      return;
    }

    const cartItems = getCartItems();
    const existingIndex = cartItems.findIndex(function (item) {
      return String(item && item.id || '') === payload.id && String(item && item.color || '') === payload.color;
    });

    if (existingIndex >= 0) {
      cartItems[existingIndex].qty = (Number(cartItems[existingIndex].qty) || 1) + 1;
    } else {
      cartItems.push(payload);
    }

    safeWriteArray(CART_KEY, cartItems);
    await updateTopCounts();
  }

  function toggleWishlist(section, product, colorCode, image) {
    const wishlistItems = getWishlistItems();
    const existingIndex = wishlistItems.findIndex(function (item) {
      return String(item && item.id || '') === product.id;
    });

    if (existingIndex >= 0) {
      wishlistItems.splice(existingIndex, 1);
      safeWriteArray(WISHLIST_KEY, wishlistItems);
      updateTopCounts();
      return false;
    }

    wishlistItems.push(buildWishlistItem(section, product, colorCode, image));
    safeWriteArray(WISHLIST_KEY, wishlistItems);
    updateTopCounts();
    return true;
  }

  function sectionByKey(sectionKey) {
    return SHOP_SECTIONS.find(function (section) {
      return section.key === sectionKey;
    }) || null;
  }

  function productById(section, productId) {
    if (!section) {
      return null;
    }
    return section.products.find(function (product) {
      return product.id === productId;
    }) || null;
  }

  function renderSections() {
    container.innerHTML = SHOP_SECTIONS.map(function (section) {
      const cards = section.products.map(function (product) {
        const defaultColor = COLOR_CODES[0];
        const defaultImage = cardImageForProduct(product, defaultColor);
        const heartClass = isWishlisted(product.id) ? ' is-active' : '';

        return '' +
          '<article class="gf-shop-card" data-section-key="' + section.key + '" data-product-id="' + product.id + '" data-active-color="' + defaultColor + '">' +
            '<div class="gf-shop-card-media">' +
              '<a class="gf-shop-card-link" href="' + product.href + '" aria-label="View ' + product.title + '">' +
                '<img class="gf-shop-card-image" src="' + defaultImage + '" alt="' + product.title + '" loading="lazy">' +
              '</a>' +
              '<div class="gf-shop-card-actions">' +
                '<button type="button" class="gf-shop-icon-btn gf-shop-heart' + heartClass + '" aria-label="Add to wishlist">' +
                  '<i class="fa-' + (isWishlisted(product.id) ? 'solid' : 'regular') + ' fa-heart"></i>' +
                '</button>' +
                '<a class="gf-shop-icon-btn gf-shop-view" href="' + product.href + '" aria-label="Open product page">' +
                  '<i class="fa-solid fa-pen-ruler"></i>' +
                '</a>' +
              '</div>' +
            '</div>' +
            '<div class="gf-shop-card-body">' +
              '<div class="gf-shop-card-meta">' + section.title + '</div>' +
              '<h3 class="gf-shop-card-title"><a href="' + product.href + '">' + product.title + '</a></h3>' +
              '<div class="gf-shop-card-price">' + formatPrice(product.priceEur) + '</div>' +
              '<div class="gf-shop-color-row">' +
                COLOR_CODES.slice(0, 6).map(function (colorCode, colorIndex) {
                  return '<button type="button" class="gf-shop-color-dot' + (colorIndex === 0 ? ' is-active' : '') + '" data-color="' + colorCode + '" aria-label="Select color ' + colorCode + '" style="background:' + COLOR_MAP[colorCode] + ';"></button>';
                }).join('') +
              '</div>' +
              '<div class="gf-shop-card-footer">' +
                '<span class="gf-shop-card-tag">' + section.tag + '</span>' +
                '<button type="button" class="gf-shop-add-btn"><i class="fa-solid fa-bag-shopping"></i><span>Add to Cart</span></button>' +
              '</div>' +
            '</div>' +
          '</article>';
      }).join('');

      return '' +
        '<section class="gf-shop-section" aria-labelledby="gfShopSection-' + section.key + '">' +
          '<div class="gf-shop-section-head">' +
            '<h2 id="gfShopSection-' + section.key + '">' + section.title + '</h2>' +
            '<a class="gf-shop-see-all" href="' + section.href + '"><span>See All</span><span aria-hidden="true">&rarr;</span></a>' +
          '</div>' +
          '<div class="gf-shop-grid">' + cards + '</div>' +
        '</section>';
    }).join('');
  }

  container.addEventListener('click', async function (event) {
    const card = event.target.closest('.gf-shop-card');
    if (!card) {
      return;
    }

    const section = sectionByKey(String(card.dataset.sectionKey || ''));
    const product = productById(section, String(card.dataset.productId || ''));
    if (!section || !product) {
      return;
    }

    const activeColor = String(card.dataset.activeColor || COLOR_CODES[0]);
    const image = card.querySelector('.gf-shop-card-image');
    const imagePath = image ? String(image.getAttribute('src') || '') : cardImageForProduct(product, activeColor);

    const colorDot = event.target.closest('.gf-shop-color-dot');
    if (colorDot) {
      const nextColor = String(colorDot.dataset.color || COLOR_CODES[0]);
      card.dataset.activeColor = nextColor;
      card.querySelectorAll('.gf-shop-color-dot').forEach(function (node) {
        node.classList.toggle('is-active', node === colorDot);
      });
      if (image) {
        image.setAttribute('src', cardImageForProduct(product, nextColor));
      }
      return;
    }

    const heartButton = event.target.closest('.gf-shop-heart');
    if (heartButton) {
      const active = toggleWishlist(section, product, activeColor, imagePath);
      heartButton.classList.toggle('is-active', active);
      const icon = heartButton.querySelector('i');
      if (icon) {
        icon.className = active ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
      }
      return;
    }

    const addButton = event.target.closest('.gf-shop-add-btn');
    if (addButton) {
      addButton.disabled = true;
      try {
        await addToCart(section, product, activeColor, imagePath);
      } finally {
        addButton.disabled = false;
      }
    }
  });

  window.addEventListener('girffon:cart-synced', function () {
    updateTopCounts();
  });

  window.addEventListener('storage', function (event) {
    if (!event || (event.key !== CART_KEY && event.key !== WISHLIST_KEY)) {
      return;
    }
    renderSections();
    updateTopCounts();
  });

  renderSections();
  updateTopCounts();
});
