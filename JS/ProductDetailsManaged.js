document.addEventListener('DOMContentLoaded', function () {
  const store = window.GIRFFON_PRODUCT_DETAILS_STORE;
  if (!store) return;

  const settings = store.getProductDetailsSettings();
  const titleEl = document.getElementById('product-title');
  const codeEl = document.getElementById('product-code');
  const descEl = document.getElementById('product-desc');
  const priceEl = document.getElementById('product-price');
  const infoCol = document.querySelector('.pd-info-col');
  const pdMain = document.querySelector('.pd-main');
  const ratingCountEl = document.querySelector('.pd-rating-count');
  const colorsLabelEl = document.querySelector('.pd-colors-label');
  const sizesLabelEl = document.querySelector('.pd-sizes-label');
  const codeLabelEl = document.querySelector('.pd-code');
  const returnContent = document.querySelector('.gf-return-content');
  const faqList = document.getElementById('gfFaqList');
  const colorBoxOriginal = document.getElementById('product-colors');
  const sizeBoxOriginal = document.getElementById('product-sizes');
  const thumbsOriginal = document.getElementById('product-thumbnails');
  const addToCartOriginal = document.querySelector('.pd-addcart-btn');
  const galleryMainOriginal = document.querySelector('.pd-gallery-main');

  if (!titleEl || !codeEl || !descEl || !priceEl || !infoCol || !pdMain || !colorBoxOriginal || !sizeBoxOriginal || !thumbsOriginal || !addToCartOriginal || !galleryMainOriginal) {
    return;
  }

  const managedStyle = document.createElement('style');
  managedStyle.textContent = [
    '.pd-managed-price{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}',
    '.pd-managed-price .pd-current{font-size:1.85rem;font-weight:800;}',
    '.pd-managed-price .pd-old{color:#9da6b4;text-decoration:line-through;}',
    '.pd-managed-price .pd-discount{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:rgba(212,168,67,0.15);color:#c18f24;font-weight:800;font-size:.8rem;}',
    '.pd-managed-desc{display:grid;gap:12px;}',
    '.pd-managed-desc p{margin:0;line-height:1.7;}',
    '.pd-managed-extra{padding:12px 14px;border-radius:16px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);}',
    '.pd-managed-actions{display:grid;gap:10px;margin-top:14px;}',
    '.pd-managed-action-row{display:flex;gap:10px;flex-wrap:wrap;}',
    '.pd-managed-action{border:1px solid rgba(255,255,255,0.12);border-radius:999px;padding:12px 18px;font:inherit;font-weight:700;cursor:pointer;background:#1a2230;color:#eef2f7;}',
    '.pd-managed-action.is-primary{background:linear-gradient(135deg,#d4a843,#b38830);color:#171107;border:0;}',
    '.pd-managed-section{max-width:1320px;margin:0 auto 28px;padding:0 18px;}',
    '.pd-managed-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}',
    '.pd-managed-card{border-radius:24px;border:1px solid rgba(255,255,255,0.08);background:linear-gradient(180deg,rgba(255,255,255,0.04),rgba(255,255,255,0.02));padding:20px;}',
    '.pd-managed-card h3{margin:0 0 12px;font-size:1.15rem;}',
    '.pd-managed-card p{margin:0;line-height:1.7;color:#b5bcc7;}',
    '.pd-size-chart-table{width:100%;border-collapse:collapse;margin-top:10px;}',
    '.pd-size-chart-table th,.pd-size-chart-table td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.08);text-align:left;}',
    '.pd-size-chart-table th{color:#d4a843;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;}',
    '@media (max-width:900px){.pd-managed-grid{grid-template-columns:1fr;}}'
  ].join('');
  document.head.appendChild(managedStyle);

  const LOCALE_CONFIG = {
    'it-IT': { currency: 'EUR', rateFromEUR: 1 },
    'de-DE': { currency: 'EUR', rateFromEUR: 1 },
    'fr-FR': { currency: 'EUR', rateFromEUR: 1 },
    'es-ES': { currency: 'EUR', rateFromEUR: 1 },
    'nl-NL': { currency: 'EUR', rateFromEUR: 1 },
    'pl-PL': { currency: 'PLN', rateFromEUR: 4.32 },
    'sv-SE': { currency: 'SEK', rateFromEUR: 11.4 },
    'en-GB': { currency: 'GBP', rateFromEUR: 0.86 },
    'en-US': { currency: 'USD', rateFromEUR: 1.09 },
    'de-CH': { currency: 'CHF', rateFromEUR: 0.97 },
    'en-CA': { currency: 'CAD', rateFromEUR: 1.48 }
  };

  const LOCALE_FALLBACK = {
    it: 'it-IT',
    de: 'de-DE',
    fr: 'fr-FR',
    es: 'es-ES',
    nl: 'nl-NL',
    pl: 'pl-PL',
    sv: 'sv-SE',
    en: 'en-GB'
  };

  function getResolvedLocale() {
    const lang = (document.documentElement.lang || 'en-GB').trim();
    return LOCALE_CONFIG[lang] ? lang : (LOCALE_FALLBACK[lang] || 'en-GB');
  }

  function formatLocalizedAmount(valueEUR) {
    const localeCode = getResolvedLocale();
    const config = LOCALE_CONFIG[localeCode] || LOCALE_CONFIG['en-GB'];
    const converted = Number(valueEUR || 0) * config.rateFromEUR;
    return new Intl.NumberFormat(localeCode, {
      style: 'currency',
      currency: config.currency,
      maximumFractionDigits: 2,
      minimumFractionDigits: 2
    }).format(converted);
  }

  function formatPrice(value, currency) {
    try {
      return new Intl.NumberFormat(document.documentElement.lang || 'en-GB', {
        style: 'currency',
        currency: currency || 'EUR',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
      }).format(Number(value || 0));
    } catch (_error) {
      return String(currency || 'EUR') + ' ' + Number(value || 0).toFixed(2);
    }
  }
  
  function getReviewCount() {
    return Number((((settings.seo || {}).schema || {}).reviewCount) != null
      ? ((settings.seo || {}).schema || {}).reviewCount
      : ((settings.rating || {}).count || 0)) || 0;
  }
  
  function getSeoImageUrl() {
    const seo = settings.seo || {};
    const source = String(((seo.openGraph || {}).image) || ((seo.twitter || {}).image) || '').trim();
    if (source) {
      try {
        return new URL(source, window.location.href).href;
      } catch (_error) {
        return source;
      }
    }
    try {
      return new URL(store.getImageUrl(((settings.images || {}).main) || null, 1200), window.location.href).href;
    } catch (_error) {
      return store.getImageUrl(((settings.images || {}).main) || null, 1200);
    }
  }
  
  function getCanonicalUrl() {
    const seo = settings.seo || {};
    const canonical = String(seo.canonicalUrl || '').trim();
    if (canonical) {
      try {
        return new URL(canonical, window.location.href).href;
      } catch (_error) {
        return canonical;
      }
    }
    const url = new URL(window.location.href);
    url.searchParams.set('sku', settings.sku || '');
    if (seo.slug) {
      url.searchParams.set('slug', seo.slug);
    }
    return url.href;
  }
  
  function getResolvedSeo() {
    const seo = settings.seo || {};
    const title = String(seo.title || settings.productName || 'GirffoN Product').trim() || settings.productName || 'GirffoN Product';
    const description = String(seo.metaDescription || ((settings.descriptions || {}).shortDescription) || '').trim();
    const robots = (seo.robots || {});
    return {
      title: title,
      description: description,
      keywords: String(seo.metaKeywords || '').trim(),
      canonicalUrl: getCanonicalUrl(),
      imageUrl: getSeoImageUrl(),
      robots: {
        index: String(robots.index || 'index').trim() || 'index',
        follow: String(robots.follow || 'follow').trim() || 'follow'
      },
      openGraph: {
        title: String(((seo.openGraph || {}).title) || title).trim() || title,
        description: String(((seo.openGraph || {}).description) || description).trim() || description,
        image: String(((seo.openGraph || {}).image) || getSeoImageUrl()).trim() || getSeoImageUrl()
      },
      twitter: {
        title: String(((seo.twitter || {}).title) || title).trim() || title,
        description: String(((seo.twitter || {}).description) || description).trim() || description,
        image: String(((seo.twitter || {}).image) || getSeoImageUrl()).trim() || getSeoImageUrl()
      }
    };
  }
  
  function upsertHeadTag(selector, tagName, attributes) {
    let node = document.head.querySelector(selector);
    if (!node) {
      node = document.createElement(tagName);
      document.head.appendChild(node);
    }
    Object.keys(attributes).forEach(function (key) {
      if (attributes[key] == null || attributes[key] === '') {
        if (node.hasAttribute(key)) {
          node.removeAttribute(key);
        }
        return;
      }
      node.setAttribute(key, String(attributes[key]));
    });
    return node;
  }
  
  function buildProductSchema() {
    const canonicalUrl = getCanonicalUrl();
    const reviewCount = getReviewCount();
    const ratingValue = Number((settings.rating || {}).value || 0);
    const imageUrls = [store.getImageUrl(((settings.images || {}).main) || null, 1200)]
      .concat((((settings.images || {}).gallery) || []).map(function (asset) {
        return store.getImageUrl(asset, 1200);
      }))
      .filter(Boolean)
      .map(function (value) {
        try {
          return new URL(value, window.location.href).href;
        } catch (_error) {
          return value;
        }
      });
    const seo = settings.seo || {};
    const schema = {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: settings.productName || '',
      sku: settings.sku || '',
      description: String(seo.metaDescription || ((settings.descriptions || {}).shortDescription) || '').trim(),
      image: imageUrls,
      category: String((((seo.schema || {}).category) || settings.category || '')).trim(),
      brand: {
        '@type': 'Brand',
        name: String((((seo.schema || {}).brand) || 'GirffoN')).trim() || 'GirffoN'
      },
      offers: {
        '@type': 'Offer',
        url: canonicalUrl,
        priceCurrency: (settings.pricing || {}).currency || 'EUR',
        price: Number((settings.pricing || {}).price || 0),
        availability: String((((seo.schema || {}).availability) || 'https://schema.org/InStock')).trim() || 'https://schema.org/InStock',
        itemCondition: String((((seo.schema || {}).condition) || 'https://schema.org/NewCondition')).trim() || 'https://schema.org/NewCondition'
      }
    };
    if (ratingValue > 0 && reviewCount > 0) {
      schema.aggregateRating = {
        '@type': 'AggregateRating',
        ratingValue: ratingValue,
        reviewCount: reviewCount
      };
    }
    return schema;
  }
  
  function applyProductHeadSeo() {
    const seo = getResolvedSeo();
    document.title = seo.title;
    upsertHeadTag('meta[name="description"]', 'meta', { name: 'description', content: seo.description });
    upsertHeadTag('meta[name="keywords"]', 'meta', { name: 'keywords', content: seo.keywords });
    upsertHeadTag('meta[name="robots"]', 'meta', { name: 'robots', content: seo.robots.index + ', ' + seo.robots.follow });
    upsertHeadTag('link[rel="canonical"]', 'link', { rel: 'canonical', href: seo.canonicalUrl });
    upsertHeadTag('meta[property="og:type"]', 'meta', { property: 'og:type', content: 'product' });
    upsertHeadTag('meta[property="og:title"]', 'meta', { property: 'og:title', content: seo.openGraph.title });
    upsertHeadTag('meta[property="og:description"]', 'meta', { property: 'og:description', content: seo.openGraph.description });
    upsertHeadTag('meta[property="og:image"]', 'meta', { property: 'og:image', content: seo.openGraph.image });
    upsertHeadTag('meta[property="og:url"]', 'meta', { property: 'og:url', content: seo.canonicalUrl });
    upsertHeadTag('meta[name="twitter:card"]', 'meta', { name: 'twitter:card', content: 'summary_large_image' });
    upsertHeadTag('meta[name="twitter:title"]', 'meta', { name: 'twitter:title', content: seo.twitter.title });
    upsertHeadTag('meta[name="twitter:description"]', 'meta', { name: 'twitter:description', content: seo.twitter.description });
    upsertHeadTag('meta[name="twitter:image"]', 'meta', { name: 'twitter:image', content: seo.twitter.image });
    const schemaNode = upsertHeadTag('script[data-product-schema="true"]', 'script', { type: 'application/ld+json', 'data-product-schema': 'true' });
    schemaNode.textContent = JSON.stringify(buildProductSchema());
  }

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

  function updateHeaderBadge(triggerId, count) {
    const trigger = document.getElementById(triggerId);
    const badge = trigger ? trigger.querySelector('.count-badge') : null;
    if (badge) badge.textContent = String(count);
  }

  function refreshHeaderCounts() {
    const wishlistItems = safeReadArray('girffon_wishlist');
    updateHeaderBadge('gfWishlistTrigger', wishlistItems.length);

    if (!window.GirffonCartApi || typeof window.GirffonCartApi.getCart !== 'function') {
      updateHeaderBadge('gfCartTrigger', 0);
      return;
    }

    window.GirffonCartApi.getCart()
      .then(function (cart) {
        updateHeaderBadge('gfCartTrigger', cart && cart.summary ? (cart.summary.itemCount || 0) : 0);
      })
      .catch(function () {
        updateHeaderBadge('gfCartTrigger', 0);
      });
  }

  function setPrice() {
    const livePriceEl = document.getElementById('product-price');
    if (!livePriceEl) return;
    const current = formatLocalizedAmount(settings.pricing.price);
    const oldPrice = Number(settings.pricing.oldPrice || 0);
    const discount = Number(settings.pricing.discount || 0);
    livePriceEl.innerHTML = '<div class="pd-managed-price">'
      + '<span class="pd-current">' + current + '</span>'
      + (oldPrice > 0 ? '<span class="pd-old">' + formatLocalizedAmount(oldPrice) + '</span>' : '')
      + (discount > 0 ? '<span class="pd-discount">-' + discount + '%</span>' : '')
      + '</div>';
    livePriceEl.dataset.baseEur = String(Number(settings.pricing.price || 0));
    livePriceEl.dataset.managedPrice = 'true';
  }

  function setDescriptions() {
    descEl.innerHTML = '<div class="pd-managed-desc">'
      + '<p>' + settings.descriptions.shortDescription + '</p>'
      + '<p>' + settings.descriptions.fullDescription + '</p>'
      + '<div class="pd-managed-extra">' + settings.descriptions.extraInfo + '</div>'
      + '</div>';
  }

  function ensureManagedContentSection() {
    let section = document.getElementById('pdManagedContent');
    if (section) return section;
    section = document.createElement('section');
    section.id = 'pdManagedContent';
    section.className = 'pd-managed-section';
    pdMain.insertAdjacentElement('afterend', section);
    return section;
  }

  function renderManagedContent() {
    const section = ensureManagedContentSection();
    const sizeRows = (settings.sizeChart.rows || []).map(function (row) {
      return '<tr><td>' + row.size + '</td><td>' + row.chest + '</td><td>' + row.length + '</td><td>' + row.shoulder + '</td></tr>';
    }).join('');
    section.innerHTML = '<div class="pd-managed-grid">'
      + '<article class="pd-managed-card"><h3>Product Detail</h3><p>' + settings.descriptions.detailText + '</p></article>'
      + '<article class="pd-managed-card"><h3>' + settings.sizeChart.title + '</h3>'
      + '<table class="pd-size-chart-table"><thead><tr><th>Size</th><th>Chest</th><th>Length</th><th>Shoulder</th></tr></thead><tbody>' + sizeRows + '</tbody></table></article>'
      + '</div>';
  }

  function renderSupportContent() {
    if (returnContent) {
      returnContent.innerHTML = '<p>' + settings.shipping.returnText + '</p>'
        + '<ul class="gf-return-points">'
        + '<li>' + settings.shipping.shippingText + '</li>'
        + '<li>' + settings.shipping.deliveryInfo + '</li>'
        + '<li>' + settings.shipping.supportNote + '</li>'
        + '</ul>'
        + '<h4>Delivery and shipping</h4><p>' + settings.shipping.deliveryInfo + '</p>'
        + '<h4>Support</h4><p>' + settings.shipping.supportNote + '</p>';
    }

    if (faqList) {
      faqList.innerHTML = [
        { q: 'How long does shipping take?', a: settings.shipping.deliveryInfo },
        { q: 'What is the return policy?', a: settings.shipping.returnText },
        { q: 'When do orders ship?', a: settings.shipping.shippingText },
        { q: 'How can I get support?', a: settings.shipping.supportNote }
      ].map(function (item) {
        return '<article class="gf-faq-item"><button type="button" class="gf-faq-question" aria-expanded="false">' + item.q + '</button><div class="gf-faq-answer" hidden><p>' + item.a + '</p></div></article>';
      }).join('');
    }
  }

  function applyManagedTextualContent() {
    const liveTitleEl = document.getElementById('product-title');
    const liveCodeEl = document.getElementById('product-code');
    const liveDescEl = document.getElementById('product-desc');
    const liveRatingCountEl = document.querySelector('.pd-rating-count');
    const liveColorsLabelEl = document.querySelector('.pd-colors-label');
    const liveSizesLabelEl = document.querySelector('.pd-sizes-label');
    const liveCodeLabelEl = document.querySelector('.pd-code');

    if (liveTitleEl) liveTitleEl.textContent = settings.productName;
    if (liveCodeEl) liveCodeEl.textContent = settings.sku;
    if (liveDescEl) {
      liveDescEl.innerHTML = '<div class="pd-managed-desc">'
        + '<p>' + settings.descriptions.shortDescription + '</p>'
        + '<p>' + settings.descriptions.fullDescription + '</p>'
        + '<div class="pd-managed-extra">' + settings.descriptions.extraInfo + '</div>'
        + '</div>';
    }
    if (liveRatingCountEl) liveRatingCountEl.textContent = '(' + settings.rating.value + ' | ' + getReviewCount() + ' reviews)';
    if (liveColorsLabelEl) liveColorsLabelEl.textContent = 'Colors:';
    if (liveSizesLabelEl) liveSizesLabelEl.textContent = 'Sizes:';
    if (liveCodeLabelEl && liveCodeLabelEl.firstChild) liveCodeLabelEl.firstChild.textContent = 'Code: ';
    applyProductHeadSeo();
    setPrice();
    renderManagedContent();
    renderSupportContent();
  }

  applyManagedTextualContent();

  const galleryMain = galleryMainOriginal.cloneNode(true);
  galleryMainOriginal.parentNode.replaceChild(galleryMain, galleryMainOriginal);
  let mainImg = galleryMain.querySelector('#main-product-image');
  if (!mainImg) {
    mainImg = document.createElement('img');
    mainImg.id = 'main-product-image';
    galleryMain.appendChild(mainImg);
  }

  const thumbs = thumbsOriginal.cloneNode(true);
  thumbsOriginal.parentNode.replaceChild(thumbs, thumbsOriginal);
  const colorBox = colorBoxOriginal.cloneNode(false);
  colorBox.id = 'product-colors';
  colorBoxOriginal.parentNode.replaceChild(colorBox, colorBoxOriginal);
  const sizeBox = sizeBoxOriginal.cloneNode(false);
  sizeBox.id = 'product-sizes';
  sizeBoxOriginal.parentNode.replaceChild(sizeBox, sizeBoxOriginal);
  const addToCartBtn = addToCartOriginal.cloneNode(true);
  addToCartOriginal.parentNode.replaceChild(addToCartBtn, addToCartOriginal);

  let actionRow = document.getElementById('pdManagedActionRow');
  if (!actionRow) {
    const wrapper = document.createElement('div');
    wrapper.className = 'pd-managed-actions';
    actionRow = document.createElement('div');
    actionRow.id = 'pdManagedActionRow';
    actionRow.className = 'pd-managed-action-row';
    wrapper.appendChild(actionRow);
    addToCartBtn.insertAdjacentElement('afterend', wrapper);
  }

  let selectedColor = settings.defaultColorCode;
  let selectedSize = settings.defaultSize;
  let currentIndex = 0;
  let currentMedia = [];

  function renderColorOptions() {
    const colors = store.getActiveColors(settings);
    colorBox.innerHTML = colors.map(function (item) {
      const border = item.code === 'Wh' ? 'border:1px solid #d8d8d8;' : '';
      const active = item.code === selectedColor ? ' active' : '';
      return '<span class="pd-color-dot' + active + '" data-code="' + item.code + '" title="' + item.name + '" style="background:' + item.hex + ';' + border + '"></span>';
    }).join('');
  }

  function renderSizeOptions() {
    const sizes = store.getActiveSizes(settings);
    sizeBox.innerHTML = sizes.map(function (item) {
      const active = item.label === selectedSize ? ' active' : '';
      return '<button class="pd-size-btn' + active + '" type="button">' + item.label + '</button>';
    }).join('');
  }

  function renderActions() {
    addToCartBtn.textContent = 'Add to Cart';
    addToCartBtn.style.display = settings.actions.addToCartActive ? '' : 'none';
    actionRow.innerHTML = '';

    if (settings.actions.buyNowActive) {
      const buyNowBtn = document.createElement('button');
      buyNowBtn.type = 'button';
      buyNowBtn.className = 'pd-managed-action is-primary';
      buyNowBtn.textContent = 'Buy Now';
      buyNowBtn.addEventListener('click', function () {
        async function addCurrentToCart() {
          const list = safeReadArray('girffon_cart');
          const entry = {
            id: settings.sku,
            sku: settings.sku,
            name: settings.productName,
      actionRow.appendChild(buyNowBtn);
    }
            price: Number(settings.pricing.price || 0),
    if (settings.actions.wishlistActive) {
      const wishlistBtn = document.createElement('button');
      wishlistBtn.type = 'button';
            quantity: 1
      wishlistBtn.textContent = 'Add to Wishlist';

          if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === 'function') {
            try {
              await window.GirffonCartApi.addItem(entry);
            } catch (_error) {
              return;
            }
          } else {
            const index = list.findIndex(function (item) {
              return item.code === entry.code && item.size === entry.size && item.color === entry.color;
            });
            if (index >= 0) {
              list[index].qty = (Number(list[index].qty) || 1) + 1;
            } else {
              list.push({
                title: entry.title,
                code: entry.code,
                price: formatPrice(settings.pricing.price, settings.pricing.currency),
                size: entry.size,
                color: entry.color,
                image: entry.image,
                qty: 1
              });
            }
            safeWriteArray('girffon_cart', list);
      });

    }
  }
            buyNowBtn.addEventListener('click', async function () {
              await addCurrentToCart();
    const media = store.getMediaForColor(colorCode, settings);
    const allAssets = [media.main].concat(media.gallery || []).filter(Boolean);
        addToCartBtn.addEventListener('click', function () {
          addCurrentToCart();
        });
            enterBtn.addEventListener('click', async function () {
              if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === 'function') {
                try {
                  await window.GirffonCartApi.addItem({
                    id: productItem.sku,
                    sku: productItem.sku,
                    name: productItem.title,
                    price: Number(productItem.price || 0),
                    color: selectedColor,
                    size: selectedSize,
                    image: productItem.imageUrl,
                    quantity: 1
                  });
                } catch (_error) {
                  return;
                }
              } else {
                const list = safeReadArray('girffon_cart');
                list.push({ title: productItem.title, code: productItem.sku, price: formatPrice(productItem.price, settings.pricing.currency), color: selectedColor, size: selectedSize, image: productItem.imageUrl, qty: 1 });
                safeWriteArray('girffon_cart', list);
              }

      };
    });

    currentIndex = 0;
    const first = currentMedia[0];
    mainImg.src = first ? first.thumb : '';
    mainImg.alt = (first && first.asset && first.asset.alt) ? first.asset.alt : (settings.productName || 'Product image');
    mainImg.dataset.thumb = first ? first.thumb : '';
    mainImg.dataset.zoom = first ? first.zoom : '';
    mainImg.style.transform = 'scale(1)';
    mainImg.style.transformOrigin = 'center center';
    thumbs.innerHTML = currentMedia.map(function (item, index) {
      const altText = (item.asset && item.asset.alt) ? item.asset.alt : ((settings.productName || 'Product image') + ' ' + (index + 1));
      return '<img src="' + item.thumb + '" data-idx="' + index + '" class="' + (index === 0 ? 'active' : '') + '" alt="' + altText.replace(/"/g, '&quot;') + '" draggable="false" />';
    }).join('');
  }

  async function addCurrentToCart() {
    if (!window.GirffonCartApi || typeof window.GirffonCartApi.addItem !== 'function') {
      return;
    }

    try {
      const cart = await window.GirffonCartApi.addItem({
        id: settings.sku,
        sku: settings.sku,
        name: settings.productName,
        price: Number(settings.pricing.price || 0),
        size: selectedSize,
        color: selectedColor,
        image: currentMedia[currentIndex] ? currentMedia[currentIndex].thumb : '',
        quantity: 1
      });
      updateHeaderBadge('gfCartTrigger', cart && cart.summary ? (cart.summary.itemCount || 0) : 0);
    } catch (_error) {
      return;
    }

    refreshHeaderCounts();
  }

  colorBox.addEventListener('click', function (event) {
    const dot = event.target.closest('.pd-color-dot');
    if (!dot) return;
    selectedColor = dot.getAttribute('data-code') || settings.defaultColorCode;
    renderColorOptions();
    renderGallery(selectedColor);
  });

  sizeBox.addEventListener('click', function (event) {
    const button = event.target.closest('.pd-size-btn');
    if (!button) return;
    selectedSize = button.textContent.trim();
    renderSizeOptions();
  });

  thumbs.addEventListener('click', function (event) {
    const image = event.target.closest('img[data-idx]');
    if (!image) return;
    currentIndex = Number(image.getAttribute('data-idx') || 0);
    const current = currentMedia[currentIndex];
    if (!current) return;
    mainImg.src = current.thumb;
    mainImg.alt = (current.asset && current.asset.alt) ? current.asset.alt : (settings.productName || 'Product image');
    mainImg.dataset.thumb = current.thumb;
    mainImg.dataset.zoom = current.zoom;
    thumbs.querySelectorAll('img').forEach(function (item) { item.classList.remove('active'); });
    image.classList.add('active');
  });

  galleryMain.addEventListener('mouseenter', function () {
    if (!mainImg.dataset.zoom) return;
    mainImg.src = mainImg.dataset.zoom;
    mainImg.style.transform = 'scale(1.9)';
  });

  galleryMain.addEventListener('mousemove', function (event) {
    const rect = galleryMain.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    mainImg.style.transformOrigin = x + '% ' + y + '%';
  });

  galleryMain.addEventListener('mouseleave', function () {
    mainImg.src = mainImg.dataset.thumb || '';
    mainImg.style.transform = 'scale(1)';
    mainImg.style.transformOrigin = 'center center';
  });

  addToCartBtn.addEventListener('click', addCurrentToCart);

  function renderRelatedSections() {
    const groups = store.getRelatedGroups(settings);
    const existingSections = Array.from(document.querySelectorAll('.gx25-category-section'));

    existingSections.forEach(function (section, index) {
      const group = groups[index] || groups[groups.length - 1];
      const heading = section.querySelector('.gx25-title-main');
      const wrap = section.querySelector('.gx25-wrap');
      if (heading) heading.textContent = group.title;
      if (wrap) {
        wrap.innerHTML = '<button class="gx25-outer-nav gx25-outer-prev" aria-label="Previous products"><span>&#10094;</span></button>'
          + '<div class="gx25-viewport"><div class="gx25-track"></div></div>'
          + '<button class="gx25-outer-nav gx25-outer-next" aria-label="Next products"><span>&#10095;</span></button>';
        bindManagedSection(section, group);
      }
      section.dataset.category = 'managed';
    });
  }

  function bindManagedSection(section, group) {
    const track = section.querySelector('.gx25-track');
    const prev = section.querySelector('.gx25-outer-prev');
    const next = section.querySelector('.gx25-outer-next');
    const items = group.items || [];

    track.innerHTML = items.map(function (item) {
      return '<article class="gx25-card" data-product-id="' + item.sku + '" data-image-index="0">'
        + '<span class="gx25-badge">' + item.badge + '</span>'
        + '<button class="gx25-fav" type="button" aria-label="Add to wishlist"><i class="fa-regular fa-heart"></i></button>'
        + '<div class="gx25-image-box">'
        + '<button class="gx25-inner-nav gx25-inner-prev" type="button" aria-label="Previous image"><span>&#10094;</span></button>'
        + '<img class="gx25-main-image" src="' + item.imageUrl + '" alt="' + item.title + '" draggable="false">'
        + '<button class="gx25-inner-nav gx25-inner-next" type="button" aria-label="Next image"><span>&#10095;</span></button>'
        + '</div>'
        + '<h3 class="gx25-title">' + item.title + '</h3>'
        + '<p class="gx25-price" data-base-eur="' + Number(item.price || 0) + '">' + formatLocalizedAmount(item.price) + '</p>'
        + '<div class="gx25-colors"></div>'
        + '<button class="gx25-enter" type="button">Add To Cart</button>'
        + '</article>';
    }).join('');

    let index = 0;
    function visibleCards() {
      if (window.innerWidth <= 640) return 1;
      if (window.innerWidth <= 920) return 2;
      if (window.innerWidth <= 1240) return 3;
      return 4;
    }
    function cardWidth() {
      const card = track.querySelector('.gx25-card');
      return card ? card.offsetWidth + 24 : 0;
    }
    function maxIndex() {
      return Math.max(0, track.querySelectorAll('.gx25-card').length - visibleCards());
    }
    function updateSlider() {
      index = Math.max(0, Math.min(index, maxIndex()));
      track.style.transform = 'translateX(-' + (index * cardWidth()) + 'px)';
    }

    next.addEventListener('click', function () { index += 1; updateSlider(); });
    prev.addEventListener('click', function () { index -= 1; updateSlider(); });
    window.addEventListener('resize', updateSlider);

    track.querySelectorAll('.gx25-card').forEach(function (card) {
      const enterBtn = card.querySelector('.gx25-enter');
      const favBtn = card.querySelector('.gx25-fav');
      const productId = card.getAttribute('data-product-id');
      const productItem = items.find(function (item) { return item.sku === productId; }) || items[0];

      enterBtn.addEventListener('click', async function () {
        if (!window.GirffonCartApi || typeof window.GirffonCartApi.addItem !== 'function') {
          return;
        }

        try {
          const cart = await window.GirffonCartApi.addItem({
            id: productItem.sku,
            sku: productItem.sku,
            name: productItem.title,
            price: Number(productItem.price || 0),
            color: selectedColor,
            size: selectedSize,
            image: productItem.imageUrl,
            quantity: 1
          });
          updateHeaderBadge('gfCartTrigger', cart && cart.summary ? (cart.summary.itemCount || 0) : 0);
        } catch (_error) {
          return;
        }

        refreshHeaderCounts();
      });

      favBtn.addEventListener('click', function () {
        const list = safeReadArray('girffon_wishlist');
        if (!list.some(function (entry) { return entry.code === productItem.sku; })) {
          list.push({ id: productItem.sku, code: productItem.sku, title: productItem.title, price: formatPrice(productItem.price, settings.pricing.currency), image: productItem.imageUrl });
          safeWriteArray('girffon_wishlist', list);
          refreshHeaderCounts();
        }
      });
    });

    updateSlider();
  }

  renderColorOptions();
  renderSizeOptions();
  renderActions();
  renderGallery(selectedColor);
  renderRelatedSections();
  refreshHeaderCounts();

  function reapplyManagedOverrides() {
    applyManagedTextualContent();
    renderRelatedSections();
    applyProductHeadSeo();
  }

  function scheduleManagedReapply() {
    window.requestAnimationFrame(reapplyManagedOverrides);
  }

  function startManagedStabilizer() {
    const startedAt = Date.now();
    const intervalId = window.setInterval(function () {
      reapplyManagedOverrides();
      if (Date.now() - startedAt > 3000) {
        window.clearInterval(intervalId);
      }
    }, 120);
  }

  const localeObserver = new MutationObserver(function () {
    scheduleManagedReapply();
  });
  localeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });

  const productPriceObserver = new MutationObserver(function () {
    if (!document.querySelector('#product-price .pd-managed-price')) {
      scheduleManagedReapply();
    }
  });
  productPriceObserver.observe(document.body, { childList: true, subtree: true, characterData: true });

  if (!document.getElementById('product-price')?.dataset.managedPrice) {
    reapplyManagedOverrides();
  }

  window.requestAnimationFrame(reapplyManagedOverrides);
  window.setTimeout(reapplyManagedOverrides, 0);
  window.setTimeout(reapplyManagedOverrides, 150);
  window.setTimeout(reapplyManagedOverrides, 500);
  window.setTimeout(reapplyManagedOverrides, 1000);
  window.addEventListener('load', reapplyManagedOverrides);
  startManagedStabilizer();
});