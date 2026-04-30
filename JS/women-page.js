document.addEventListener("DOMContentLoaded", function () {
  const sections = Array.from(document.querySelectorAll(".gx25-section"));
  if (!sections.length) return;
  const localeHelper = window.GFCategoryLocaleHelper ? window.GFCategoryLocaleHelper.create("women") : null;

  const CART_KEY = "girffon_cart";
  const WISHLIST_KEY = "girffon_wishlist";

  const colorMap = {
    Bk: "#000000",
    Bl: "#2d3a56",
    Bu: "#4a6fdc",
    Gr: "#6f7d5c",
    Gy: "#bdbdbd",
    Ka: "#caa27f",
    Na: "#f1d4a5",
    Or: "#d68c8c",
    Pp: "#d9b3d9",
    Rd: "#c43b3b",
    Wh: "#ffffff",
    Ye: "#f2c94c"
  };

  const colorCodes = Object.keys(colorMap);

  const baseProducts = [
    {
      title: "Women France T-Shirt",
      priceEur: 220,
      folderTemplate: "Cart/products/tshirt-women/women france/{color}/"
    },
    {
      title: "Women Italy T-Shirt",
      priceEur: 220,
      folderTemplate: "Cart/products/tshirt-women/women italy/{color}/"
    },
    {
      title: "Women Japan T-Shirt",
      priceEur: 220,
      folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/"
    },
    {
      title: "Women France Premium",
      priceEur: 230,
      folderTemplate: "Cart/products/tshirt-women/women france/{color}/"
    },
    {
      title: "Women Italy Premium",
      priceEur: 230,
      folderTemplate: "Cart/products/tshirt-women/women italy/{color}/"
    },
    {
      title: "Women Japan Premium",
      priceEur: 230,
      folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/"
    }
  ];

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

  function getCartItems() {
    return safeReadArray(CART_KEY);
  }

  function getWishlistItems() {
    return safeReadArray(WISHLIST_KEY);
  }

  function setBadgeCount(triggerId, count) {
    const trigger = document.getElementById(triggerId);
    const badge = trigger ? trigger.querySelector(".count-badge") : null;
    if (badge) badge.textContent = String(count);
  }

  function updateTopCounts() {
    const cartCount = getCartItems().reduce(function (sum, item) {
      return sum + (Number(item.qty) || 1);
    }, 0);

    setBadgeCount("gfCartTrigger", cartCount);
    setBadgeCount("gfWishlistTrigger", getWishlistItems().length);
  }

  function sectionName(section) {
    const fixedName = (section.dataset.sectionName || "").trim();
    if (fixedName) {
      const titleNode = section.querySelector(".gx25-men-title, .gx25-women-title");
      if (titleNode) {
        titleNode.textContent = localeHelper ? localeHelper.translateSection(fixedName) : fixedName;
      }
      return fixedName;
    }

    const titleNode = section.querySelector(".gx25-men-title, .gx25-women-title");
    return (titleNode ? titleNode.textContent : "Women").trim();
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

  function buildProductsForSection(name) {
    return baseProducts.map(function (base, index) {
      return {
        id: "women-" + name.toLowerCase().replace(/\s+/g, "-") + "-" + (index + 1),
        badge: localeHelper ? localeHelper.texts().badge : "New",
        sectionName: name,
        baseTitle: base.title,
        title: localeHelper ? localeHelper.composeTitle(name, base.title) : name + " - " + base.title,
        priceEur: base.priceEur,
        price: localeHelper ? localeHelper.formatPrice(base.priceEur) : "220,00 €",
        folderTemplate: base.folderTemplate,
        colors: colorCodes.slice(),
        defaultColor: "Bk"
      };
    });
  }

  function createColorDots(product) {
    return product.colors
      .map(function (code, idx) {
        const active = code === product.defaultColor || (idx === 0 && !product.defaultColor);
        return '<span class="gx25-color ' + (active ? "active" : "") + '" data-color="' + code + '" style="background:' + (colorMap[code] || "#cccccc") + ';"></span>';
      })
      .join("");
  }

  function createCard(product) {
    const images = buildImages(product.folderTemplate, product.defaultColor);

    return (
      '<article class="gx25-card" data-product-id="' + product.id + '" data-section-name="' + product.sectionName + '" data-base-title="' + product.baseTitle + '" data-price-eur="' + product.priceEur + '">' +
        '<span class="gx25-badge">' + product.badge + "</span>" +
        '<button class="gx25-fav" type="button" aria-label="Add to wishlist"><i class="fa-regular fa-heart"></i></button>' +
        '<div class="gx25-image-box">' +
          '<button class="gx25-inner-nav gx25-inner-prev" type="button" aria-label="Previous image"><span>&#10094;</span></button>' +
          '<img class="gx25-main-image" src="' + images[0] + '" alt="' + product.title + '">' +
          '<button class="gx25-inner-nav gx25-inner-next" type="button" aria-label="Next image"><span>&#10095;</span></button>' +
        "</div>" +
        '<h3 class="gx25-title">' + product.title + "</h3>" +
        '<p class="gx25-price">' + product.price + "</p>" +
        '<div class="gx25-colors">' + createColorDots(product) + "</div>" +
        '<button class="gx25-enter" type="button">' + (localeHelper ? localeHelper.texts().addToCart : 'Add To Cart') + '</button>' +
      "</article>"
    );
  }

  function wishlistHas(id) {
    return getWishlistItems().some(function (item) {
      return item.id === id;
    });
  }

  function toggleWishlist(item) {
    const list = getWishlistItems();
    const idx = list.findIndex(function (x) {
      return x.id === item.id;
    });

    if (idx >= 0) {
      list.splice(idx, 1);
      safeWriteArray(WISHLIST_KEY, list);
      updateTopCounts();
      return false;
    }

    list.push(item);
    safeWriteArray(WISHLIST_KEY, list);
    updateTopCounts();
    return true;
  }

  function parseCartPrice(value) {
    return Number.parseFloat(String(value || "0").replace(/[^0-9.,]/g, "").replace(",", ".")) || 0;
  }

  async function addCart(item) {
    const quantity = Number(item.qty != null ? item.qty : item.quantity) || 1;
    const priceNumber = Number(item.priceNumber != null ? item.priceNumber : parseCartPrice(item.price));
    const payload = {
      id: item.id,
      sku: item.id,
      name: item.title || item.name || "GirffoN Product",
      price: priceNumber,
      image: item.image || item.img || "",
      size: item.size || "One Size",
      color: item.color || "",
      quantity: quantity
    };

    if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function") {
      await window.GirffonCartApi.addItem(payload);
      updateTopCounts();
      return;
    }

    const list = getCartItems();
    const idx = list.findIndex(function (x) {
      return x.id === item.id && x.color === item.color;
    });

    if (idx >= 0) {
      list[idx].qty = (Number(list[idx].qty) || 1) + quantity;
    } else {
      list.push(Object.assign({}, item, {
        price: priceNumber,
        priceNumber: priceNumber,
        qty: quantity,
        size: item.size || "One Size"
      }));
    }

    safeWriteArray(CART_KEY, list);
    updateTopCounts();
  }

  function initSection(section) {
    const name = sectionName(section);
    const products = buildProductsForSection(name);
    const track = section.querySelector(".gx25-track");
    const prevBtn = section.querySelector(".gx25-outer-prev");
    const nextBtn = section.querySelector(".gx25-outer-next");

    if (!track || !prevBtn || !nextBtn) return;

    track.innerHTML = products.map(createCard).join("");

    let outerIndex = 0;
    const gap = 24;

    function getVisibleCards() {
      if (window.innerWidth <= 600) return 1;
      if (window.innerWidth <= 900) return 2;
      if (window.innerWidth <= 1280) return 3;
      return 5;
    }

    function cards() {
      return track.querySelectorAll(".gx25-card");
    }

    function cardWidth() {
      const firstCard = track.querySelector(".gx25-card");
      if (!firstCard) return 0;
      return firstCard.offsetWidth + gap;
    }

    function maxIndex() {
      return Math.max(0, cards().length - getVisibleCards());
    }

    function updateOuter() {
      const max = maxIndex();
      if (outerIndex < 0) outerIndex = 0;
      if (outerIndex > max) outerIndex = max;
      track.style.transform = "translateX(-" + outerIndex * cardWidth() + "px)";
    }

    nextBtn.addEventListener("click", function () {
      if (outerIndex < maxIndex()) {
        outerIndex += 1;
        updateOuter();
      }
    });

    prevBtn.addEventListener("click", function () {
      if (outerIndex > 0) {
        outerIndex -= 1;
        updateOuter();
      }
    });

    const cardNodes = track.querySelectorAll(".gx25-card");
    cardNodes.forEach(function (card, idx) {
      const product = products[idx];
      const favBtn = card.querySelector(".gx25-fav");
      const favIcon = favBtn.querySelector("i");
      const img = card.querySelector(".gx25-main-image");
      const inPrev = card.querySelector(".gx25-inner-prev");
      const inNext = card.querySelector(".gx25-inner-next");
      const colorDots = card.querySelectorAll(".gx25-color");
      const addBtn = card.querySelector(".gx25-enter");

      let selectedColor = product.defaultColor;
      let imageIndex = 0;
      let imageSet = buildImages(product.folderTemplate, selectedColor);

      if (wishlistHas(product.id)) {
        favBtn.classList.add("active");
        favIcon.classList.remove("fa-regular");
        favIcon.classList.add("fa-solid");
      }

      favBtn.addEventListener("click", function () {
        const active = toggleWishlist({
          id: product.id,
          title: product.title,
          price: product.price,
          image: imageSet[0]
        });

        favBtn.classList.toggle("active", active);
        favIcon.classList.toggle("fa-regular", !active);
        favIcon.classList.toggle("fa-solid", active);
      });

      inPrev.addEventListener("click", function (event) {
        event.preventDefault();
        imageIndex = (imageIndex - 1 + imageSet.length) % imageSet.length;
        img.style.opacity = "0.35";
        setTimeout(function () {
          img.src = imageSet[imageIndex];
          img.style.opacity = "1";
        }, 120);
      });

      inNext.addEventListener("click", function (event) {
        event.preventDefault();
        imageIndex = (imageIndex + 1) % imageSet.length;
        img.style.opacity = "0.35";
        setTimeout(function () {
          img.src = imageSet[imageIndex];
          img.style.opacity = "1";
        }, 120);
      });

      colorDots.forEach(function (dot) {
        dot.addEventListener("click", function () {
          colorDots.forEach(function (d) {
            d.classList.remove("active");
          });
          dot.classList.add("active");
          selectedColor = dot.dataset.color || product.defaultColor;
          imageSet = buildImages(product.folderTemplate, selectedColor);
          imageIndex = 0;
          img.style.opacity = "0.35";
          setTimeout(function () {
            img.src = imageSet[0];
            img.style.opacity = "1";
          }, 120);
        });
      });

      addBtn.addEventListener("click", async function () {
        await addCart({
          id: product.id,
          title: product.title,
          price: product.price,
          color: selectedColor,
          image: imageSet[0],
          section: name,
          qty: 1
        });
      });
    });

    window.addEventListener("resize", updateOuter);
    updateOuter();
  }

  sections.forEach(initSection);
  updateTopCounts();
  if (localeHelper) {
    localeHelper.applyPage(document);
    localeHelper.bindBankModal(document);
    localeHelper.watch(function () {
      localeHelper.applyPage(document);
    });
  }
});
