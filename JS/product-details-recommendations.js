document.addEventListener("DOMContentLoaded", function () {
  const sections = Array.from(document.querySelectorAll(".gx25-category-section"));
  if (!sections.length) return;

  const CART_KEY = "girffon_cart";
  const WISHLIST_KEY = "girffon_wishlist";
  const colorMap = {
    Bk: "#000000",
    Wh: "#ffffff",
    Rd: "#c43b3b",
    Bl: "#2d3a56",
    Bu: "#4a6fdc",
    Gr: "#6f7d5c",
    Gy: "#bdbdbd",
    Ka: "#caa27f",
    Na: "#f1d4a5",
    Or: "#d68c8c",
    Pp: "#d9b3d9",
    Ye: "#f2c94c"
  };

  const sectionConfigs = [
    {
      title: "Kids Favorites",
      products: [
        { id: "kids-fav-1", title: "Oni Black Tee", price: "€29.00", badge: "Hot", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/" },
        { id: "kids-fav-2", title: "Street France", price: "€31.00", badge: "New", folderTemplate: "Cart/products/tshirt-men/men italy/{color}/" },
        { id: "kids-fav-3", title: "Soft Cotton", price: "€27.00", badge: "Best", folderTemplate: "Cart/products/tshirt-men/men usa/{color}/" },
        { id: "kids-fav-4", title: "Daily Graphic", price: "€30.00", badge: "Top", folderTemplate: "Cart/products/tshirt-women/women france/{color}/" },
        { id: "kids-fav-5", title: "Tokyo Pop", price: "€32.00", badge: "Edit", folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/" }
      ]
    },
    {
      title: "Mini Streetwear",
      products: [
        { id: "mini-street-1", title: "Urban Icon", price: "€33.00", badge: "Fresh", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/" },
        { id: "mini-street-2", title: "Tokyo Wave", price: "€35.00", badge: "Drop", folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/" },
        { id: "mini-street-3", title: "Natural Fit", price: "€28.00", badge: "New", folderTemplate: "Cart/products/tshirt-women/Women italy/{color}/" },
        { id: "mini-street-4", title: "Club Print", price: "€34.00", badge: "Edit", folderTemplate: "Cart/products/tshirt-men/men italy/{color}/" },
        { id: "mini-street-5", title: "Skater Mood", price: "€36.00", badge: "Pro", folderTemplate: "Cart/products/tshirt-men/men usa/{color}/" }
      ]
    },
    {
      title: "Kids Best Sellers",
      products: [
        { id: "kids-best-1", title: "Monster Print", price: "€36.00", badge: "Best", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/" },
        { id: "kids-best-2", title: "Soft Blue", price: "€34.00", badge: "Hot", folderTemplate: "Cart/products/tshirt-men/men usa/{color}/" },
        { id: "kids-best-3", title: "Grey Fit", price: "€32.00", badge: "Top", folderTemplate: "Cart/products/tshirt-women/women france/{color}/" },
        { id: "kids-best-4", title: "Japan Art", price: "€38.00", badge: "Pro", folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/" },
        { id: "kids-best-5", title: "Color Club", price: "€35.00", badge: "Now", folderTemplate: "Cart/products/tshirt-women/Women italy/{color}/" }
      ]
    },
    {
      title: "Gift Picks",
      products: [
        { id: "gift-pick-1", title: "Gold Mood", price: "€25.00", badge: "Gift", folderTemplate: "Cart/products/tshirt-men/men italy/{color}/" },
        { id: "gift-pick-2", title: "Cute Line", price: "€26.00", badge: "Cute", folderTemplate: "Cart/products/tshirt-women/Women italy/{color}/" },
        { id: "gift-pick-3", title: "Urban Art", price: "€29.00", badge: "Fav", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/" },
        { id: "gift-pick-4", title: "Color Pop", price: "€28.00", badge: "Mix", folderTemplate: "Cart/products/tshirt-women/women france/{color}/" },
        { id: "gift-pick-5", title: "Weekend Set", price: "€31.00", badge: "New", folderTemplate: "Cart/products/tshirt-men/men usa/{color}/" }
      ]
    }
  ];

  function safeReadArray(key) {
    try {
      const raw = localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function safeWriteArray(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (_error) {
      return;
    }
  }

  function getGap(track) {
    const styles = window.getComputedStyle(track);
    const gapValue = parseFloat(styles.columnGap || styles.gap || "24");
    return Number.isFinite(gapValue) ? gapValue : 24;
  }

  function setBadgeCount(triggerId, count) {
    const trigger = document.getElementById(triggerId);
    const badge = trigger ? trigger.querySelector(".count-badge") : null;
    if (badge) badge.textContent = String(count);
  }

  function updateHeaderCounts() {
    const cartItems = safeReadArray(CART_KEY);
    const wishlistItems = safeReadArray(WISHLIST_KEY);
    const cartCount = cartItems.reduce(function (sum, item) {
      return sum + (Number(item.qty) || 1);
    }, 0);

    setBadgeCount("gfCartTrigger", cartCount);
    setBadgeCount("gfWishlistTrigger", wishlistItems.length);
  }

  function buildImages(folderTemplate, colorCode) {
    const folder = folderTemplate.replace("{color}", colorCode);
    return [
      folder + "400.jpg",
      folder + "400-1.jpg",
      folder + "400-2.jpg",
      folder + "400-3.jpg"
    ];
  }

  function createColorDots(defaultColor) {
    return Object.keys(colorMap).map(function (code) {
      const active = code === defaultColor ? " active" : "";
      const borderColor = code === "Wh" ? "border:1px solid #d8d8d8;" : "";
      return '<button class="gx25-color' + active + '" type="button" aria-label="Choose color ' + code + '" data-color="' + code + '" style="background:' + colorMap[code] + ';' + borderColor + '"></button>';
    }).join("");
  }

  function createCard(product) {
    const defaultColor = "Bk";
    const images = buildImages(product.folderTemplate, defaultColor);

    return [
      '<article class="gx25-card" data-product-id="' + product.id + '" data-folder-template="' + product.folderTemplate + '" data-color="' + defaultColor + '" data-image-index="0">',
      '<span class="gx25-badge">' + product.badge + '</span>',
      '<button class="gx25-fav" type="button" aria-label="Add to wishlist"><i class="fa-regular fa-heart"></i></button>',
      '<div class="gx25-image-box">',
      '<button class="gx25-inner-nav gx25-inner-prev" type="button" aria-label="Previous image"><span>&#10094;</span></button>',
      '<img class="gx25-main-image" src="' + images[0] + '" alt="' + product.title + '" draggable="false">',
      '<button class="gx25-inner-nav gx25-inner-next" type="button" aria-label="Next image"><span>&#10095;</span></button>',
      '</div>',
      '<h3 class="gx25-title">' + product.title + '</h3>',
      '<p class="gx25-price">' + product.price + '</p>',
      '<div class="gx25-colors">' + createColorDots(defaultColor) + '</div>',
      '<button class="gx25-enter" type="button">Add To Cart</button>',
      '</article>'
    ].join("");
  }

  function getVisibleCards() {
    if (window.innerWidth <= 640) return 1;
    if (window.innerWidth <= 920) return 2;
    if (window.innerWidth <= 1240) return 3;
    return 4;
  }

  function createZoomModal() {
    let modal = document.querySelector(".pd-rec-zoom-modal");
    if (modal) return modal;

    modal = document.createElement("div");
    modal.className = "pd-rec-zoom-modal";
    modal.setAttribute("hidden", "hidden");
    modal.innerHTML = [
      '<button class="pd-rec-zoom-close" type="button" aria-label="Close image preview">&times;</button>',
      '<div class="pd-rec-zoom-stage">',
      '<img class="pd-rec-zoom-image" src="" alt="">',
      '</div>'
    ].join("");

    document.body.appendChild(modal);
    return modal;
  }

  const zoomModal = createZoomModal();
  const zoomImage = zoomModal.querySelector(".pd-rec-zoom-image");

  function closeZoomModal() {
    zoomModal.hidden = true;
    zoomModal.classList.remove("is-open");
    document.body.classList.remove("pd-rec-modal-open");
  }

  function openZoomModal(src, alt) {
    zoomImage.src = src;
    zoomImage.alt = alt || "Product preview";
    zoomModal.hidden = false;
    zoomModal.classList.add("is-open");
    document.body.classList.add("pd-rec-modal-open");
  }

  zoomModal.addEventListener("click", function (event) {
    if (event.target === zoomModal || event.target.closest(".pd-rec-zoom-close")) {
      closeZoomModal();
    }
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && zoomModal.classList.contains("is-open")) {
      closeZoomModal();
    }
  });

  function isWishlisted(productId) {
    return safeReadArray(WISHLIST_KEY).some(function (item) {
      return item.id === productId;
    });
  }

  function toggleWishlist(item) {
    const list = safeReadArray(WISHLIST_KEY);
    const index = list.findIndex(function (entry) {
      return entry.id === item.id;
    });

    if (index >= 0) {
      list.splice(index, 1);
      safeWriteArray(WISHLIST_KEY, list);
      updateHeaderCounts();
      return false;
    }

    list.push(item);
    safeWriteArray(WISHLIST_KEY, list);
    updateHeaderCounts();
    return true;
  }

  function addToCart(item) {
    const priceNumber = Number.parseFloat(String(item.price || 0).replace(/[^0-9.,]/g, "").replace(",", ".")) || 0;
    const quantity = Number(item.qty != null ? item.qty : item.quantity) || 1;
    const payload = {
      id: item.code || item.id,
      sku: item.code || item.id,
      name: item.title || item.name || "GirffoN Product",
      price: priceNumber,
      image: item.image || item.img || "",
      size: item.size || "One Size",
      color: item.color || "",
      quantity: quantity
    };

    if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function") {
      return window.GirffonCartApi.addItem(payload).then(function () {
        updateHeaderCounts();
      });
    }

    const list = safeReadArray(CART_KEY);
    const index = list.findIndex(function (entry) {
      return entry.code === item.code && entry.color === item.color;
    });

    if (index >= 0) {
      list[index].qty = (Number(list[index].qty) || 1) + quantity;
    } else {
      list.push(Object.assign({}, item, {
        price: priceNumber,
        priceNumber: priceNumber,
        qty: quantity
      }));
    }

    safeWriteArray(CART_KEY, list);
    updateHeaderCounts();
    return Promise.resolve();
  }

  function bindSection(section, config, sectionIndex) {
    const track = section.querySelector(".gx25-track");
    const prevBtn = section.querySelector(".gx25-outer-prev");
    const nextBtn = section.querySelector(".gx25-outer-next");
    const title = section.querySelector(".gx25-title-main");
    const products = (config && config.products) || [];

    if (!track || !prevBtn || !nextBtn || !title || !products.length) return;

    section.dataset.gx25Ready = "true";
    section.dataset.gx25Index = String(sectionIndex);
    title.textContent = config.title;
    track.innerHTML = products.map(createCard).join("");

    const cardNodes = Array.from(track.querySelectorAll(".gx25-card"));
    let outerIndex = 0;

    function maxIndex() {
      return Math.max(0, cardNodes.length - getVisibleCards());
    }

    function updateOuterNavState() {
      prevBtn.disabled = outerIndex <= 0;
      nextBtn.disabled = outerIndex >= maxIndex();
    }

    function updateTrack() {
      const firstCard = track.querySelector(".gx25-card");
      const gap = getGap(track);

      if (!firstCard) return;

      outerIndex = Math.max(0, Math.min(outerIndex, maxIndex()));
      track.style.transform = "translateX(-" + (outerIndex * (firstCard.getBoundingClientRect().width + gap)) + "px)";
      updateOuterNavState();
    }

    prevBtn.addEventListener("click", function () {
      outerIndex -= 1;
      updateTrack();
    });

    nextBtn.addEventListener("click", function () {
      outerIndex += 1;
      updateTrack();
    });

    cardNodes.forEach(function (card, productIndex) {
      const product = products[productIndex];
      const image = card.querySelector(".gx25-main-image");
      const titleNode = card.querySelector(".gx25-title");
      const colorDots = Array.from(card.querySelectorAll(".gx25-color"));
      const favBtn = card.querySelector(".gx25-fav");
      const favIcon = favBtn.querySelector("i");
      const addBtn = card.querySelector(".gx25-enter");
      const inPrev = card.querySelector(".gx25-inner-prev");
      const inNext = card.querySelector(".gx25-inner-next");
      let activeColor = card.dataset.color || "Bk";
      let imageIndex = Number(card.dataset.imageIndex || 0);

      function setWishlistState(active) {
        favBtn.classList.toggle("active", active);
        favIcon.classList.toggle("fa-regular", !active);
        favIcon.classList.toggle("fa-solid", active);
      }

      function currentImages() {
        return buildImages(product.folderTemplate, activeColor);
      }

      function renderImage(nextIndex) {
        const images = currentImages();
        imageIndex = nextIndex;

        if (imageIndex < 0) imageIndex = images.length - 1;
        if (imageIndex >= images.length) imageIndex = 0;

        card.dataset.imageIndex = String(imageIndex);
        image.src = images[imageIndex];
      }

      setWishlistState(isWishlisted(product.id));

      image.addEventListener("click", function () {
        openZoomModal(image.src, titleNode ? titleNode.textContent : product.title);
      });

      inPrev.addEventListener("click", function (event) {
        event.preventDefault();
        renderImage(imageIndex - 1);
      });

      inNext.addEventListener("click", function (event) {
        event.preventDefault();
        renderImage(imageIndex + 1);
      });

      colorDots.forEach(function (dot) {
        dot.addEventListener("click", function () {
          activeColor = dot.dataset.color || "Bk";
          card.dataset.color = activeColor;
          colorDots.forEach(function (entry) {
            entry.classList.remove("active");
          });
          dot.classList.add("active");
          renderImage(0);
        });
      });

      favBtn.addEventListener("click", function () {
        const active = toggleWishlist({
          id: product.id,
          title: product.title,
          price: product.price,
          image: image.src
        });

        setWishlistState(active);
      });

      addBtn.addEventListener("click", function () {
        addToCart({
          title: product.title,
          code: product.id,
          price: product.price,
          size: config.title,
          color: activeColor,
          image: image.src,
          qty: 1
        });
      });
    });

    window.addEventListener("resize", updateTrack);
    updateTrack();
  }

  sections.forEach(function (section, index) {
    const config = sectionConfigs[index] || sectionConfigs[sectionConfigs.length - 1];
    bindSection(section, config, index);
  });

  updateHeaderCounts();
});