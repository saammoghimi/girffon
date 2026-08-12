document.addEventListener("DOMContentLoaded", function () {
  const livePricing = window.GirffonLivePricing || null;
  const colorList = [
    { code: "Bk", name: "Black", hex: "#000000" },
    { code: "Wh", name: "White", hex: "#ffffff" },
    { code: "Rd", name: "Red", hex: "#c43b3b" },
    { code: "Bl", name: "Blue", hex: "#2d3a56" },
    { code: "Bu", name: "Light Blue", hex: "#4a6fdc" },
    { code: "Gr", name: "Green", hex: "#6f7d5c" },
    { code: "Gy", name: "Gray", hex: "#bdbdbd" },
    { code: "Ka", name: "Khaki", hex: "#caa27f" },
    { code: "Na", name: "Natural", hex: "#f1d4a5" },
    { code: "Or", name: "Orange", hex: "#d68c8c" },
    { code: "Pp", name: "Purple", hex: "#d9b3d9" },
    { code: "Ye", name: "Yellow", hex: "#f2c94c" }
  ];

  const product = {
    title: "Men's France T-Shirt",
    code: "FR-MEN-001",
    description: "High-quality cotton t-shirt with custom print. Made in France. Soft, durable, and stylish for everyday wear.",
    basePriceEur: 39,
    price: "€39.00",
    sizes: ["S", "M", "L", "XL", "XXL"],
    colorList,
    folder: "Cart/products/tshirt-men/Men france/"
  };

  let currentPricing = {
    basePriceEur: Number(product.basePriceEur || 0),
    effectivePriceEur: Number(product.basePriceEur || 0),
    isOnSale: false,
    saleBadge: "",
    saleCaption: ""
  };

  const STORAGE_KEY = "gf-locale-country";
  const DEFAULT_COUNTRY = "GB";
  const COUNTRY_TO_LOCALE = {
    IT: "it-IT",
    DE: "de-DE",
    FR: "fr-FR",
    ES: "es-ES",
    NL: "nl-NL",
    PL: "pl-PL",
    SE: "sv-SE",
    GB: "en-GB",
    US: "en-US",
    CH: "de-CH",
    CA: "en-CA"
  };
  const LOCALE_CONFIG = {
    "it-IT": { currency: "EUR", rateFromEUR: 1.0 },
    "de-DE": { currency: "EUR", rateFromEUR: 1.0 },
    "fr-FR": { currency: "EUR", rateFromEUR: 1.0 },
    "es-ES": { currency: "EUR", rateFromEUR: 1.0 },
    "nl-NL": { currency: "EUR", rateFromEUR: 1.0 },
    "pl-PL": { currency: "PLN", rateFromEUR: 4.32 },
    "sv-SE": { currency: "SEK", rateFromEUR: 11.4 },
    "en-GB": { currency: "GBP", rateFromEUR: 0.86 },
    "en-US": { currency: "USD", rateFromEUR: 1.09 },
    "de-CH": { currency: "CHF", rateFromEUR: 0.97 },
    "en-CA": { currency: "CAD", rateFromEUR: 1.48 }
  };
  const PRODUCT_DETAIL_FALLBACK = {
    "en-US": "en-GB",
    "en-CA": "en-GB",
    "de-CH": "de-DE"
  };
  const PRODUCT_DETAIL_I18N = {
    "en-GB": {
      pageTitle: "GirffoN - Product Details",
      title: "Men's France T-Shirt",
      description: "High-quality cotton t-shirt with custom print. Made in France. Soft, durable, and stylish for everyday wear.",
      ratingCount: "(4.5 | 2518 reviews)",
      colorsLabel: "Colors:",
      sizesLabel: "Sizes:",
      addToCart: "Add to Cart",
      relatedAddToCart: "Add To Cart",
      codeLabel: "Code:",
      sectionTitles: ["Kids Favorites", "Mini Streetwear", "Kids Best Sellers", "Gift Picks"],
      prevProductsAria: "Previous products",
      nextProductsAria: "Next products",
      prevImageAria: "Previous image",
      nextImageAria: "Next image",
      addWishlistAria: "Add to wishlist",
      colorNames: {
        Bk: "Black", Wh: "White", Rd: "Red", Bl: "Blue", Bu: "Light Blue", Gr: "Green",
        Gy: "Gray", Ka: "Khaki", Na: "Natural", Or: "Orange", Pp: "Purple", Ye: "Yellow"
      },
      badges: { Hot: "Hot", New: "New", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Gift", Cute: "Cute", Fav: "Fav", Mix: "Mix" }
    },
    "it-IT": {
      pageTitle: "GirffoN - Dettagli Prodotto",
      title: "T-Shirt Francia Uomo",
      description: "T-shirt in cotone di alta qualita con stampa personalizzata. Realizzata in Francia. Morbida, resistente ed elegante per l'uso quotidiano.",
      ratingCount: "(4.5 | 2518 recensioni)",
      colorsLabel: "Colori:",
      sizesLabel: "Taglie:",
      addToCart: "Aggiungi al Carrello",
      relatedAddToCart: "Aggiungi al Carrello",
      codeLabel: "Codice:",
      sectionTitles: ["Preferiti Kids", "Mini Streetwear", "Best Seller Kids", "Idee Regalo"],
      prevProductsAria: "Prodotti precedenti",
      nextProductsAria: "Prodotti successivi",
      prevImageAria: "Immagine precedente",
      nextImageAria: "Immagine successiva",
      addWishlistAria: "Aggiungi alla wishlist",
      colorNames: { Bk: "Nero", Wh: "Bianco", Rd: "Rosso", Bl: "Blu", Bu: "Azzurro", Gr: "Verde", Gy: "Grigio", Ka: "Khaki", Na: "Naturale", Or: "Arancione", Pp: "Viola", Ye: "Giallo" },
      badges: { Hot: "Hot", New: "Nuovo", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Regalo", Cute: "Cute", Fav: "Preferito", Mix: "Mix" }
    },
    "de-DE": {
      pageTitle: "GirffoN - Produktdetails",
      title: "Frankreich T-Shirt Herren",
      description: "Hochwertiges Baumwoll-T-Shirt mit individuellem Druck. Hergestellt in Frankreich. Weich, langlebig und stilvoll fur jeden Tag.",
      ratingCount: "(4.5 | 2518 Bewertungen)",
      colorsLabel: "Farben:",
      sizesLabel: "Grossen:",
      addToCart: "In den Warenkorb",
      relatedAddToCart: "In den Warenkorb",
      codeLabel: "Code:",
      sectionTitles: ["Kids Favoriten", "Mini Streetwear", "Kids Bestseller", "Geschenkideen"],
      prevProductsAria: "Vorherige Produkte",
      nextProductsAria: "Nachste Produkte",
      prevImageAria: "Vorheriges Bild",
      nextImageAria: "Nachstes Bild",
      addWishlistAria: "Zur Wunschliste hinzufugen",
      colorNames: { Bk: "Schwarz", Wh: "Weiss", Rd: "Rot", Bl: "Blau", Bu: "Hellblau", Gr: "Grun", Gy: "Grau", Ka: "Khaki", Na: "Natur", Or: "Orange", Pp: "Lila", Ye: "Gelb" },
      badges: { Hot: "Hot", New: "Neu", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Geschenk", Cute: "Cute", Fav: "Favorit", Mix: "Mix" }
    },
    "fr-FR": {
      pageTitle: "GirffoN - Details Produit",
      title: "T-Shirt France Homme",
      description: "T-shirt en coton de haute qualite avec impression personnalisee. Fabrique en France. Doux, durable et elegant pour tous les jours.",
      ratingCount: "(4.5 | 2518 avis)",
      colorsLabel: "Couleurs:",
      sizesLabel: "Tailles:",
      addToCart: "Ajouter au Panier",
      relatedAddToCart: "Ajouter au Panier",
      codeLabel: "Code:",
      sectionTitles: ["Favoris Kids", "Mini Streetwear", "Meilleures Ventes Kids", "Idees Cadeaux"],
      prevProductsAria: "Produits precedents",
      nextProductsAria: "Produits suivants",
      prevImageAria: "Image precedente",
      nextImageAria: "Image suivante",
      addWishlistAria: "Ajouter a la liste d'envies",
      colorNames: { Bk: "Noir", Wh: "Blanc", Rd: "Rouge", Bl: "Bleu", Bu: "Bleu clair", Gr: "Vert", Gy: "Gris", Ka: "Kaki", Na: "Naturel", Or: "Orange", Pp: "Violet", Ye: "Jaune" },
      badges: { Hot: "Hot", New: "Nouveau", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Cadeau", Cute: "Cute", Fav: "Favori", Mix: "Mix" }
    },
    "es-ES": {
      pageTitle: "GirffoN - Detalles del Producto",
      title: "Camiseta Francia Hombre",
      description: "Camiseta de algodon de alta calidad con estampado personalizado. Hecha en Francia. Suave, resistente y con estilo para uso diario.",
      ratingCount: "(4.5 | 2518 resenas)",
      colorsLabel: "Colores:",
      sizesLabel: "Tallas:",
      addToCart: "Agregar al Carrito",
      relatedAddToCart: "Agregar al Carrito",
      codeLabel: "Codigo:",
      sectionTitles: ["Favoritos Kids", "Mini Streetwear", "Mas Vendidos Kids", "Ideas de Regalo"],
      prevProductsAria: "Productos anteriores",
      nextProductsAria: "Productos siguientes",
      prevImageAria: "Imagen anterior",
      nextImageAria: "Imagen siguiente",
      addWishlistAria: "Agregar a favoritos",
      colorNames: { Bk: "Negro", Wh: "Blanco", Rd: "Rojo", Bl: "Azul", Bu: "Azul claro", Gr: "Verde", Gy: "Gris", Ka: "Caqui", Na: "Natural", Or: "Naranja", Pp: "Morado", Ye: "Amarillo" },
      badges: { Hot: "Hot", New: "Nuevo", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Regalo", Cute: "Cute", Fav: "Favorito", Mix: "Mix" }
    },
    "nl-NL": {
      pageTitle: "GirffoN - Productdetails",
      title: "Frankrijk T-Shirt Heren",
      description: "Hoogwaardig katoenen T-shirt met custom print. Gemaakt in Frankrijk. Zacht, duurzaam en stijlvol voor elke dag.",
      ratingCount: "(4.5 | 2518 reviews)",
      colorsLabel: "Kleuren:",
      sizesLabel: "Maten:",
      addToCart: "Toevoegen aan Winkelwagen",
      relatedAddToCart: "Toevoegen aan Winkelwagen",
      codeLabel: "Code:",
      sectionTitles: ["Kids Favorieten", "Mini Streetwear", "Kids Bestsellers", "Cadeaukeuzes"],
      prevProductsAria: "Vorige producten",
      nextProductsAria: "Volgende producten",
      prevImageAria: "Vorige afbeelding",
      nextImageAria: "Volgende afbeelding",
      addWishlistAria: "Toevoegen aan verlanglijst",
      colorNames: { Bk: "Zwart", Wh: "Wit", Rd: "Rood", Bl: "Blauw", Bu: "Lichtblauw", Gr: "Groen", Gy: "Grijs", Ka: "Khaki", Na: "Naturel", Or: "Oranje", Pp: "Paars", Ye: "Geel" },
      badges: { Hot: "Hot", New: "Nieuw", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Cadeau", Cute: "Cute", Fav: "Favoriet", Mix: "Mix" }
    },
    "pl-PL": {
      pageTitle: "GirffoN - Szczegoly Produktu",
      title: "Meski T-Shirt Francja",
      description: "Wysokiej jakosci bawelniany T-shirt z nadrukiem custom. Wyprodukowany we Francji. Miekki, trwaly i stylowy na co dzien.",
      ratingCount: "(4.5 | 2518 opinii)",
      colorsLabel: "Kolory:",
      sizesLabel: "Rozmiary:",
      addToCart: "Dodaj do Koszyka",
      relatedAddToCart: "Dodaj do Koszyka",
      codeLabel: "Kod:",
      sectionTitles: ["Ulubione Kids", "Mini Streetwear", "Bestsellery Kids", "Pomysly na Prezenty"],
      prevProductsAria: "Poprzednie produkty",
      nextProductsAria: "Nastepne produkty",
      prevImageAria: "Poprzedni obraz",
      nextImageAria: "Nastepny obraz",
      addWishlistAria: "Dodaj do listy zyczen",
      colorNames: { Bk: "Czarny", Wh: "Bialy", Rd: "Czerwony", Bl: "Niebieski", Bu: "Jasny niebieski", Gr: "Zielony", Gy: "Szary", Ka: "Khaki", Na: "Naturalny", Or: "Pomaranczowy", Pp: "Fioletowy", Ye: "Zolty" },
      badges: { Hot: "Hot", New: "Nowy", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Prezent", Cute: "Cute", Fav: "Ulubione", Mix: "Mix" }
    },
    "sv-SE": {
      pageTitle: "GirffoN - Produktdetaljer",
      title: "Frankrike T-Shirt Herr",
      description: "T-shirt i hogkvalitativ bomull med customtryck. Tillverkad i Frankrike. Mjuk, hallbar och stilren for vardagsbruk.",
      ratingCount: "(4.5 | 2518 recensioner)",
      colorsLabel: "Farger:",
      sizesLabel: "Storlekar:",
      addToCart: "Lagg i Varukorg",
      relatedAddToCart: "Lagg i Varukorg",
      codeLabel: "Kod:",
      sectionTitles: ["Kids Favoriter", "Mini Streetwear", "Kids Bastsaljare", "Presentval"],
      prevProductsAria: "Forgaende produkter",
      nextProductsAria: "Nasta produkter",
      prevImageAria: "Forgaende bild",
      nextImageAria: "Nasta bild",
      addWishlistAria: "Lagg till i onskelista",
      colorNames: { Bk: "Svart", Wh: "Vit", Rd: "Rod", Bl: "Bla", Bu: "Ljusbla", Gr: "Gron", Gy: "Gra", Ka: "Khaki", Na: "Natur", Or: "Orange", Pp: "Lila", Ye: "Gul" },
      badges: { Hot: "Hot", New: "Ny", Best: "Best", Top: "Top", Fresh: "Fresh", Drop: "Drop", Edit: "Edit", Pro: "Pro", Gift: "Present", Cute: "Cute", Fav: "Favorit", Mix: "Mix" }
    }
  };

  function getLocaleCode() {
    const countryCode = localStorage.getItem(STORAGE_KEY) || DEFAULT_COUNTRY;
    return COUNTRY_TO_LOCALE[countryCode] || "en-GB";
  }

  function getProductTexts() {
    const localeCode = getLocaleCode();
    const resolvedLocale = PRODUCT_DETAIL_I18N[localeCode]
      ? localeCode
      : (PRODUCT_DETAIL_FALLBACK[localeCode] || "en-GB");

    return PRODUCT_DETAIL_I18N[resolvedLocale] || PRODUCT_DETAIL_I18N["en-GB"];
  }

  function formatLocalizedPrice(valueEUR) {
    const localeCode = getLocaleCode();
    const config = LOCALE_CONFIG[localeCode] || LOCALE_CONFIG["en-GB"];
    const converted = valueEUR * config.rateFromEUR;
    return new Intl.NumberFormat(localeCode, {
      style: "currency",
      currency: config.currency,
      maximumFractionDigits: 2,
      minimumFractionDigits: 2
    }).format(converted);
  }

  function setCurrentPricingFromProduct(liveProduct) {
    const basePriceEur = Number(liveProduct && liveProduct.price != null ? liveProduct.price : product.basePriceEur || 0);
    const effectivePriceEur = Number(liveProduct && liveProduct.effective_price != null ? liveProduct.effective_price : basePriceEur);
    const isOnSale = Boolean(liveProduct && liveProduct.is_on_sale) && effectivePriceEur < basePriceEur;

    currentPricing = {
      basePriceEur: basePriceEur,
      effectivePriceEur: effectivePriceEur,
      isOnSale: isOnSale,
      saleBadge: isOnSale ? String((liveProduct && liveProduct.sale_badge) || "SALE").trim() || "SALE" : "",
      saleCaption: isOnSale ? String((liveProduct && liveProduct.sale_caption) || "").trim() : ""
    };
  }

  function renderProductPrice() {
    const basePrice = Number(currentPricing.basePriceEur || 0);
    const effectivePrice = Number(currentPricing.effectivePriceEur || basePrice || 0);
    const isOnSale = Boolean(currentPricing.isOnSale) && effectivePrice < basePrice;

    priceEl.dataset.baseEur = basePrice.toFixed(2);
    priceEl.dataset.priceEur = basePrice.toFixed(2);
    priceEl.dataset.effectivePriceEur = effectivePrice.toFixed(2);
    priceEl.dataset.saleBadge = currentPricing.saleBadge || "";
    priceEl.dataset.saleCaption = currentPricing.saleCaption || "";
    priceEl.classList.toggle("pd-price-sale", isOnSale);

    if (!isOnSale) {
      priceEl.textContent = formatLocalizedPrice(basePrice);
      return;
    }

    const priceRow = [
      '<span class="pd-price-badge">' + currentPricing.saleBadge + '</span>',
      '<span class="pd-price-current">' + formatLocalizedPrice(effectivePrice) + '</span>',
      '<span class="pd-price-original">' + formatLocalizedPrice(basePrice) + '</span>'
    ].join("");

    const caption = currentPricing.saleCaption
      ? '<span class="pd-price-caption">' + currentPricing.saleCaption + '</span>'
      : "";

    priceEl.innerHTML = '<span class="pd-price-row">' + priceRow + '</span>' + caption;
  }

  function syncLiveProductPricing() {
    if (!livePricing || typeof livePricing.loadCatalog !== "function" || typeof livePricing.findProduct !== "function") {
      renderProductPrice();
      return Promise.resolve();
    }

    return livePricing.loadCatalog(true).then(function () {
      const matchedProduct = livePricing.findProduct({
        sku: product.code,
        name: product.title,
        categoryKey: "men"
      });

      if (matchedProduct) {
        setCurrentPricingFromProduct(matchedProduct);
      } else {
        setCurrentPricingFromProduct(null);
      }

      renderProductPrice();
    }).catch(function () {
      setCurrentPricingFromProduct(null);
      renderProductPrice();
    });
  }

  function getImagesForColor(colorCode) {
    const folder = `${product.folder}${colorCode}/`;
    return {
      thumbs: [
        folder + "1000.jpg",
        folder + "1000-1.jpg",
        folder + "1000-2.jpg",
        folder + "1000-3.jpg"
      ],
      zooms: [
        folder + "1200.jpg",
        folder + "1200-1.jpg",
        folder + "1200-2.jpg",
        folder + "1200-3.jpg"
      ]
    };
  }

  let selectedColor = colorList[0].code;
  let selectedSize = product.sizes[0];
  let currentImgIdx = 0;

  const colorBox = document.getElementById("product-colors");
  const sizeBox = document.getElementById("product-sizes");
  const mainImg = document.getElementById("main-product-image");
  const thumbs = document.getElementById("product-thumbnails");
  const titleEl = document.getElementById("product-title");
  const codeEl = document.getElementById("product-code");
  const descEl = document.getElementById("product-desc");
  const priceEl = document.getElementById("product-price");
  const galleryMain = document.querySelector(".pd-gallery-main");
  const ratingCountEl = document.querySelector(".pd-rating-count");
  const colorsLabelEl = document.querySelector(".pd-colors-label");
  const sizesLabelEl = document.querySelector(".pd-sizes-label");
  const addToCartBtn = document.querySelector(".pd-addcart-btn");
  const codeLabelEl = document.querySelector(".pd-code");

  if (!colorBox || !sizeBox || !mainImg || !thumbs || !galleryMain) return;

  titleEl.textContent = product.title;
  codeEl.textContent = product.code;
  descEl.textContent = product.description;
  renderProductPrice();

  colorBox.innerHTML = colorList.map(c => {
    const borderColor = c.code === "Wh" ? "#d8d8d8" : "rgba(0,0,0,0.15)";
    return `
      <span
        class="pd-color-dot"
        title="${c.name}"
        data-code="${c.code}"
        data-name="${c.name}"
        style="background:${c.hex}; border:1px solid ${borderColor};">
      </span>
    `;
  }).join("");

  sizeBox.innerHTML = product.sizes.map(size => {
    return `<button class="pd-size-btn" type="button">${size}</button>`;
  }).join("");

  const firstColorDot = colorBox.querySelector(".pd-color-dot");
  if (firstColorDot) firstColorDot.classList.add("active");

  const firstSizeBtn = sizeBox.querySelector(".pd-size-btn");
  if (firstSizeBtn) firstSizeBtn.classList.add("active");

  function preventImageActions(img) {
    img.setAttribute("draggable", "false");
    img.style.userSelect = "none";
    img.style.webkitUserSelect = "none";
    img.style.webkitTouchCallout = "none";
    img.style.webkitUserDrag = "none";

    img.addEventListener("contextmenu", e => e.preventDefault());
    img.addEventListener("dragstart", e => e.preventDefault());
    img.addEventListener("selectstart", e => e.preventDefault());
  }

  preventImageActions(mainImg);

  function applyThumbImage() {
    const thumbSrc = mainImg.dataset.thumb;
    if (thumbSrc) mainImg.src = thumbSrc;
  }

  function applyZoomImage() {
    const zoomSrc = mainImg.dataset.zoom;
    if (zoomSrc) mainImg.src = zoomSrc;
  }

  function resetZoomView() {
    applyThumbImage();
    mainImg.style.transform = "scale(1)";
    mainImg.style.transformOrigin = "center center";
  }

  function renderGallery(colorCode) {
    const imgs = getImagesForColor(colorCode);

    mainImg.src = imgs.thumbs[0];
    mainImg.dataset.thumb = imgs.thumbs[0];
    mainImg.dataset.zoom = imgs.zooms[0];

    thumbs.innerHTML = imgs.thumbs.map((img, i) => {
      return `
        <img
          src="${img}"
          class="${i === 0 ? "active" : ""}"
          data-idx="${i}"
          alt="Product image ${i + 1}"
          draggable="false">
      `;
    }).join("");

    thumbs.querySelectorAll("img").forEach(preventImageActions);
    currentImgIdx = 0;
    resetZoomView();
  }

  function applyProductLocale() {
    const texts = getProductTexts();

    document.title = texts.pageTitle;
    titleEl.textContent = texts.title;
    descEl.textContent = texts.description;
    renderProductPrice();
    if (ratingCountEl) ratingCountEl.textContent = texts.ratingCount;
    if (colorsLabelEl) colorsLabelEl.textContent = texts.colorsLabel;
    if (sizesLabelEl) sizesLabelEl.textContent = texts.sizesLabel;
    if (addToCartBtn) addToCartBtn.textContent = texts.addToCart;
    if (codeLabelEl && codeLabelEl.firstChild) {
      codeLabelEl.firstChild.textContent = texts.codeLabel + " ";
    }

    colorBox.querySelectorAll(".pd-color-dot").forEach(function (dot) {
      const colorCode = dot.dataset.code || "Bk";
      const colorName = texts.colorNames[colorCode] || PRODUCT_DETAIL_I18N["en-GB"].colorNames[colorCode] || colorCode;
      dot.dataset.name = colorName;
      dot.setAttribute("title", colorName);
      dot.setAttribute("aria-label", colorName);
    });

    const sections = Array.from(document.querySelectorAll(".gx25-category-section"));
    sections.forEach(function (section, index) {
      const heading = section.querySelector(".gx25-title-main");
      const prevProducts = section.querySelector(".gx25-outer-prev");
      const nextProducts = section.querySelector(".gx25-outer-next");
      if (heading) heading.textContent = texts.sectionTitles[index] || PRODUCT_DETAIL_I18N["en-GB"].sectionTitles[index] || heading.textContent;
      if (prevProducts) prevProducts.setAttribute("aria-label", texts.prevProductsAria);
      if (nextProducts) nextProducts.setAttribute("aria-label", texts.nextProductsAria);
    });

    document.querySelectorAll(".gx25-enter").forEach(function (button) {
      button.textContent = texts.relatedAddToCart;
    });
    document.querySelectorAll(".gx25-fav").forEach(function (button) {
      button.setAttribute("aria-label", texts.addWishlistAria);
    });
    document.querySelectorAll(".gx25-inner-prev").forEach(function (button) {
      button.setAttribute("aria-label", texts.prevImageAria);
    });
    document.querySelectorAll(".gx25-inner-next").forEach(function (button) {
      button.setAttribute("aria-label", texts.nextImageAria);
    });
    document.querySelectorAll(".gx25-badge").forEach(function (badge) {
      const value = (badge.textContent || "").trim();
      badge.textContent = texts.badges[value] || PRODUCT_DETAIL_I18N["en-GB"].badges[value] || value;
    });
  }

  renderGallery(selectedColor);
  applyProductLocale();
  syncLiveProductPricing();

  colorBox.addEventListener("click", function (e) {
    const target = e.target.closest(".pd-color-dot");
    if (!target) return;

    colorBox.querySelectorAll(".pd-color-dot").forEach(dot => {
      dot.classList.remove("active");
    });

    target.classList.add("active");
    selectedColor = target.dataset.code;
    renderGallery(selectedColor);
  });

  sizeBox.addEventListener("click", function (e) {
    const target = e.target.closest(".pd-size-btn");
    if (!target) return;

    sizeBox.querySelectorAll(".pd-size-btn").forEach(btn => {
      btn.classList.remove("active");
    });

    target.classList.add("active");
    selectedSize = target.textContent.trim();
  });

  thumbs.addEventListener("click", function (e) {
    const target = e.target.closest("img");
    if (!target) return;

    const idx = Number(target.dataset.idx);
    const imgs = getImagesForColor(selectedColor);

    currentImgIdx = idx;
    mainImg.src = imgs.thumbs[idx];
    mainImg.dataset.thumb = imgs.thumbs[idx];
    mainImg.dataset.zoom = imgs.zooms[idx];

    thumbs.querySelectorAll("img").forEach(img => img.classList.remove("active"));
    target.classList.add("active");

    resetZoomView();
  });

  galleryMain.addEventListener("mouseenter", function () {
    applyZoomImage();
    mainImg.style.transform = "scale(1.9)";
  });

  galleryMain.addEventListener("mousemove", function (e) {
    const rect = galleryMain.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    mainImg.style.transformOrigin = `${x}% ${y}%`;
  });

  galleryMain.addEventListener("mouseleave", function () {
    resetZoomView();
  });

  if (addToCartBtn) {
    addToCartBtn.addEventListener("click", async function () {
      if (window.GirffonProductCartSessionActive) {
        return;
      }

      const selectedThumb = mainImg.dataset.thumb || mainImg.src;

      const cartItem = {
        id: product.code,
        sku: product.code,
        name: titleEl.textContent,
        title: titleEl.textContent,
        code: product.code,
        price: Number(currentPricing.effectivePriceEur || currentPricing.basePriceEur || product.basePriceEur || 0),
        size: selectedSize,
        color: selectedColor,
        image: selectedThumb,
        quantity: 1
      };

      if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function") {
        try {
          const cart = await window.GirffonCartApi.addItem(cartItem);
          const cartBadge = document.querySelector("#gfCartTrigger .count-badge");
          if (cartBadge && cart && cart.summary) {
            cartBadge.textContent = String(cart.summary.itemCount || 0);
          }
          return;
        } catch (_error) {
          return;
        }
      }

      const existingCart = JSON.parse(localStorage.getItem("girffon_cart")) || [];

      const existingIndex = existingCart.findIndex(item =>
        item.code === cartItem.code &&
        item.size === cartItem.size &&
        item.color === cartItem.color
      );

      if (existingIndex > -1) {
        existingCart[existingIndex].qty += 1;
      } else {
        existingCart.push(cartItem);
      }

      localStorage.setItem("girffon_cart", JSON.stringify(existingCart));

      const cartBadge = document.querySelector("#gfCartTrigger .count-badge");
      if (cartBadge) {
        const totalQty = existingCart.reduce((sum, item) => sum + item.qty, 0);
        cartBadge.textContent = totalQty;
      }
    });
  }

  const gx25Sections = Array.from(document.querySelectorAll(".gx25-category-section"));
  const gx25SectionKeys = ["kids-favorites", "mini-streetwear", "kids-best-sellers", "gift-picks"];

  gx25Sections.forEach(function (section, index) {
    if (!section.dataset.gx25Key) {
      section.dataset.gx25Key = gx25SectionKeys[index] || gx25SectionKeys[0];
    }
  });

  const gx25ColorMap = {
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

  const gx25SectionProducts = {
    "kids-favorites": [
      { id: "kf-1", sku: "TD-KID-201", category: "kids", title: "Kids Teddy Bear Tee", price: "€180.00", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/", badge: "Hot" },
      { id: "kf-2", sku: "CAT-SHOP-KIDS-FUN-2", category: "kids", title: "Fun Park Tee", price: "€185.00", folderTemplate: "Cart/products/tshirt-men/men italy/{color}/", badge: "New" },
      { id: "kf-3", sku: "UC-KID-202", category: "kids", title: "Kids Unicorn Dream Tee", price: "€190.00", folderTemplate: "Cart/products/tshirt-men/men usa/{color}/", badge: "Best" },
      { id: "kf-4", sku: "SP-KID-203", category: "kids", title: "Kids Space Rocket Tee", price: "€195.00", folderTemplate: "Cart/products/tshirt-women/women france/{color}/", badge: "Top" }
    ],
    "mini-streetwear": [
      { id: "ms-1", sku: "FR-MEN-001", category: "men", title: "Men's France T-Shirt", price: "€200.00", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/", badge: "Fresh" },
      { id: "ms-2", sku: "IT-MEN-002", category: "men", title: "Men's Italy T-Shirt", price: "€220.00", folderTemplate: "Cart/products/tshirt-men/men italy/{color}/", badge: "Drop" },
      { id: "ms-3", sku: "FR-WOM-101", category: "women", title: "Women's France T-Shirt", price: "€220.00", folderTemplate: "Cart/products/tshirt-women/women france/{color}/", badge: "New" },
      { id: "ms-4", sku: "JP-WOM-103", category: "women", title: "Women's Japan T-Shirt", price: "€220.00", folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/", badge: "Edit" }
    ],
    "kids-best-sellers": [
      { id: "bs-1", sku: "US-MEN-003", category: "men", title: "Men's USA T-Shirt", price: "€230.00", folderTemplate: "Cart/products/tshirt-men/men usa/{color}/", badge: "Best" },
      { id: "bs-2", sku: "IT-WOM-102", category: "women", title: "Women's Italy T-Shirt", price: "€220.00", folderTemplate: "Cart/products/tshirt-women/Women italy/{color}/", badge: "Hot" },
      { id: "bs-3", sku: "CAT-SHOP-KIDS-DINO-4", category: "kids", title: "Dino Squad Tee", price: "€195.00", folderTemplate: "Cart/products/tshirt-women/women france/{color}/", badge: "Top" },
      { id: "bs-4", sku: "CAT-PAGE-KIDS-PAGE-CANDY-POP-TEE", category: "kids", title: "Candy Pop Tee", price: "€185.00", folderTemplate: "Cart/products/tshirt-women/Women japon/{color}/", badge: "Pro" }
    ],
    "gift-picks": [
      { id: "gp-1", sku: "GRF-ACC-TB-402", category: "accessories", title: "Classic Tote Bag", price: "€55.00", folderTemplate: "Cart/products/tshirt-men/men italy/{color}/", badge: "Gift" },
      { id: "gp-2", sku: "GRF-ACC-PC-403", category: "accessories", title: "Phone Case", price: "€39.00", folderTemplate: "Cart/products/tshirt-women/Women italy/{color}/", badge: "Cute" },
      { id: "gp-3", sku: "GRF-HOM-CS-301", category: "home-living", title: "Cushion Cover", price: "€49.00", folderTemplate: "Cart/products/tshirt-men/Men france/{color}/", badge: "Fav" },
      { id: "gp-4", sku: "GRF-HOM-MG-302", category: "home-living", title: "Ceramic Mug", price: "€39.00", folderTemplate: "Cart/products/tshirt-women/women france/{color}/", badge: "Mix" }
    ]
  };

  function gx25ParseFallbackPrice(value) {
    return Number.parseFloat(String(value || "0").replace(/[^0-9.,]/g, "").replace(",", ".")) || 0;
  }

  function gx25BuildImages(folderTemplate, colorCode) {
    const folder = folderTemplate.replace("{color}", colorCode);
    return [
      folder + "400.jpg",
      folder + "400-1.jpg",
      folder + "400-2.jpg",
      folder + "400-3.jpg"
    ];
  }

  function gx25CreateColorDots(defaultColor) {
    return Object.keys(gx25ColorMap).map(function (code) {
      const ring = code === "Wh" ? "border:1px solid #d7d7d7;" : "";
      const active = code === defaultColor ? " active" : "";
      return `<span class="gx25-color${active}" data-color="${code}" style="background:${gx25ColorMap[code]};${ring}"></span>`;
    }).join("");
  }

  function gx25CreateCard(productItem) {
    const defaultColor = "Bk";
    const images = gx25BuildImages(productItem.folderTemplate, defaultColor);
    const texts = getProductTexts();
    const badgeText = texts.badges[productItem.badge] || PRODUCT_DETAIL_I18N["en-GB"].badges[productItem.badge] || productItem.badge;
    const fallbackPrice = gx25ParseFallbackPrice(productItem.price);

    return `
      <article class="gx25-card" data-product-id="${productItem.id}" data-product-sku="${productItem.sku || ''}" data-base-title="${productItem.title}" data-base-price-eur="${fallbackPrice}" data-price-eur="${fallbackPrice}" data-folder-template="${productItem.folderTemplate}" data-color="${defaultColor}" data-image-index="0">
        <span class="gx25-badge">${badgeText}</span>
        <button class="gx25-fav" type="button" aria-label="${texts.addWishlistAria}">
          <i class="fa-regular fa-heart"></i>
        </button>

        <div class="gx25-image-box">
          <button class="gx25-inner-nav gx25-inner-prev" type="button" aria-label="${texts.prevImageAria}"><span>&#10094;</span></button>
          <img class="gx25-main-image" src="${images[0]}" alt="${productItem.title}" draggable="false">
          <button class="gx25-inner-nav gx25-inner-next" type="button" aria-label="${texts.nextImageAria}"><span>&#10095;</span></button>
        </div>

        <h3 class="gx25-title">${productItem.title}</h3>
        <p class="gx25-price">${productItem.price}</p>
        <div class="gx25-colors">${gx25CreateColorDots(defaultColor)}</div>
        <button class="gx25-enter" type="button">${texts.relatedAddToCart}</button>
      </article>
    `;
  }

  function gx25VisibleCards() {
    if (window.innerWidth <= 640) return 1;
    if (window.innerWidth <= 900) return 2;
    if (window.innerWidth <= 1180) return 3;
    return 4;
  }

  function gx25CardWidth(track) {
    const card = track.querySelector(".gx25-card");
    if (!card) return 0;
    return card.offsetWidth + 24;
  }

  function gx25BindSection(section) {
    const key = section.dataset.gx25Key;
    const products = gx25SectionProducts[key] || [];
    const track = section.querySelector(".gx25-track");
    const prev = section.querySelector(".gx25-outer-prev");
    const next = section.querySelector(".gx25-outer-next");

    if (!track || !prev || !next || !products.length) return;

    track.innerHTML = products.map(gx25CreateCard).join("");
    track.querySelectorAll("img").forEach(preventImageActions);

    function renderRecommendationPrice(priceNode, pricing) {
      const basePrice = Number(pricing && pricing.price != null ? pricing.price : 0);
      const effectivePrice = Number(pricing && pricing.effective_price != null ? pricing.effective_price : basePrice);
      const isOnSale = Boolean(pricing && pricing.is_on_sale) && effectivePrice < basePrice;
      const caption = String(pricing && pricing.sale_caption ? pricing.sale_caption : "").trim();

      priceNode.dataset.baseEur = String(basePrice.toFixed(2));
      priceNode.dataset.effectiveEur = String(effectivePrice.toFixed(2));
      priceNode.dataset.saleCaption = caption;
      priceNode.classList.toggle("gf-live-price-block", isOnSale);

      if (!isOnSale) {
        priceNode.textContent = formatLocalizedPrice(basePrice);
        return;
      }

      priceNode.innerHTML = '<span class="gf-live-price-row">'
        + '<span class="gf-live-price-current">' + formatLocalizedPrice(effectivePrice) + '</span>'
        + '<span class="gf-live-price-original">' + formatLocalizedPrice(basePrice) + '</span>'
        + '</span>'
        + (caption ? '<span class="gf-live-price-caption">' + caption + '</span>' : '');
    }

    function syncRecommendationPricing() {
      if (!livePricing || typeof livePricing.loadCatalog !== "function" || typeof livePricing.findProduct !== "function") {
        return Promise.resolve();
      }

      return livePricing.loadCatalog(false).then(function () {
        Array.from(track.querySelectorAll(".gx25-card")).forEach(function (card, cardIndex) {
          const productItem = products[cardIndex] || {};
          const matchedProduct = livePricing.findProduct({
            sku: productItem.sku || card.dataset.productSku || "",
            name: productItem.title || card.dataset.baseTitle || card.querySelector(".gx25-title")?.textContent || "",
            categoryKey: productItem.category || ""
          });
          const priceNode = card.querySelector(".gx25-price");
          const titleNode = card.querySelector(".gx25-title");
          const badgeNode = card.querySelector(".gx25-badge");
          const fallbackPrice = Number(card.dataset.basePriceEur || 0);

          if (!priceNode) {
            return;
          }

          const pricing = matchedProduct
            ? {
                price: Number(matchedProduct.price || fallbackPrice),
                effective_price: Number(matchedProduct.effective_price || matchedProduct.price || fallbackPrice),
                is_on_sale: Boolean(matchedProduct.is_on_sale),
                sale_caption: matchedProduct.sale_caption || ""
              }
            : {
                price: fallbackPrice,
                effective_price: fallbackPrice,
                is_on_sale: false,
                sale_caption: ""
              };

          if (matchedProduct && titleNode) {
            titleNode.textContent = matchedProduct.name || titleNode.textContent;
          }

          card.dataset.productSku = matchedProduct && matchedProduct.sku ? matchedProduct.sku : (productItem.sku || card.dataset.productSku || "");
          card.dataset.basePriceEur = Number(pricing.price || 0).toFixed(2);
          card.dataset.priceEur = Number(pricing.effective_price || pricing.price || 0).toFixed(2);
          card.dataset.effectivePriceEur = Number(pricing.effective_price || pricing.price || 0).toFixed(2);
          renderRecommendationPrice(priceNode, pricing);

          if (badgeNode) {
            badgeNode.textContent = pricing.is_on_sale ? "SALE" : (badgeNode.textContent || "");
          }
        });
      }).catch(function () {
        return Promise.resolve();
      });
    }

    syncRecommendationPricing();

    let outerIndex = 0;

    function gx25MaxIndex() {
      const cards = track.querySelectorAll(".gx25-card").length;
      return Math.max(0, cards - gx25VisibleCards());
    }

    function gx25UpdateOuter() {
      outerIndex = Math.max(0, Math.min(outerIndex, gx25MaxIndex()));
      track.style.transform = `translateX(-${outerIndex * gx25CardWidth(track)}px)`;
    }

    prev.addEventListener("click", function () {
      outerIndex -= 1;
      gx25UpdateOuter();
    });

    next.addEventListener("click", function () {
      outerIndex += 1;
      gx25UpdateOuter();
    });

    section.addEventListener("click", function (e) {
      const card = e.target.closest(".gx25-card");
      if (!card) return;

      const productId = card.dataset.productSku || card.dataset.productId;
      const folderTemplate = card.dataset.folderTemplate;
      const activeColor = card.dataset.color || "Bk";
      let imageIndex = Number(card.dataset.imageIndex || 0);
      const image = card.querySelector(".gx25-main-image");

      if (e.target.closest(".gx25-inner-prev") || e.target.closest(".gx25-inner-next")) {
        const images = gx25BuildImages(folderTemplate, activeColor);
        imageIndex += e.target.closest(".gx25-inner-next") ? 1 : -1;

        if (imageIndex < 0) imageIndex = images.length - 1;
        if (imageIndex >= images.length) imageIndex = 0;

        card.dataset.imageIndex = String(imageIndex);
        image.src = images[imageIndex];
        return;
      }

      const colorDot = e.target.closest(".gx25-color");
      if (colorDot) {
        const color = colorDot.dataset.color;
        const images = gx25BuildImages(folderTemplate, color);

        card.dataset.color = color;
        card.dataset.imageIndex = "0";
        image.src = images[0];

        card.querySelectorAll(".gx25-color").forEach(dot => dot.classList.remove("active"));
        colorDot.classList.add("active");
        return;
      }

      const favBtn = e.target.closest(".gx25-fav");
      if (favBtn) {
        favBtn.classList.toggle("active");
        return;
      }

      const addBtn = e.target.closest(".gx25-enter");
      if (addBtn) {
        if (window.GirffonProductCartSessionActive) {
          return;
        }

        if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function") {
          window.GirffonCartApi.addItem({
            id: productId,
            sku: productId,
            name: card.querySelector(".gx25-title").textContent,
            price: Number(card.dataset.effectivePriceEur || card.dataset.priceEur || 0),
            size: "Kids",
            color: activeColor,
            image: image.src,
            quantity: 1
          }).then(function (cart) {
            const cartBadge = document.querySelector("#gfCartTrigger .count-badge");
            if (cartBadge && cart && cart.summary) {
              cartBadge.textContent = String(cart.summary.itemCount || 0);
            }
          }).catch(function () {
            // Keep page stable if cart request fails.
          });
          return;
        }

        const existingCart = JSON.parse(localStorage.getItem("girffon_cart")) || [];

        const existingIndex = existingCart.findIndex(function (item) {
          return item.code === productId && item.color === activeColor;
        });

        if (existingIndex > -1) {
          existingCart[existingIndex].qty += 1;
        } else {
          existingCart.push({
            title: card.querySelector(".gx25-title").textContent,
            code: productId,
            price: card.querySelector(".gx25-price").textContent,
            size: "Kids",
            color: activeColor,
            image: image.src,
            qty: 1
          });
        }

        localStorage.setItem("girffon_cart", JSON.stringify(existingCart));

        const cartBadge = document.querySelector("#gfCartTrigger .count-badge");
        if (cartBadge) {
          const totalQty = existingCart.reduce((sum, item) => {
            return sum + (Number(item.qty) || 1);
          }, 0);

          cartBadge.textContent = String(totalQty);
        }
      }
    });

    window.addEventListener("resize", gx25UpdateOuter);
    gx25UpdateOuter();
  }

  gx25Sections.forEach(gx25BindSection);
  applyProductLocale();

  const localeCards = Array.from(document.querySelectorAll(".gf-locale-card"));
  localeCards.forEach(function (card) {
    card.addEventListener("click", function () {
      window.setTimeout(applyProductLocale, 0);
    });
  });

  const langObserver = new MutationObserver(function () {
    applyProductLocale();
  });
  langObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
});