document.addEventListener("DOMContentLoaded", function () {
  const searchHost = document.getElementById("gfSiteSearch") || document.querySelector(".top-search");
  if (!searchHost) {
    return;
  }

  if (!searchHost.id) {
    searchHost.id = "gfSiteSearch";
  }
  searchHost.setAttribute("role", "search");
  searchHost.setAttribute("autocomplete", "off");

  const searchInput = searchHost.querySelector("input") || createSearchInput(searchHost);
  const searchButton = searchHost.querySelector("button") || createSearchButton(searchHost);
  const searchResults = ensureResultsPanel(searchHost);
  const searchResultsList = searchResults.querySelector("#gfSearchResultsList");
  const searchClose = searchResults.querySelector("#gfSearchClose");
  const searchResultsTitle = searchResults.querySelector("#gfSearchResultsTitle");

  const LOCALE_STORAGE_KEY = "gf-locale-country";
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
  const LOCALE_FALLBACK = {
    "en-US": "en-GB",
    "en-CA": "en-GB",
    "de-CH": "de-DE"
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
  const UI_TEXTS = {
    "en-GB": { title: "Search Results", emptyTitle: "No matching products found", emptyText: "Try a product name, theme, or product code.", categoryLabel: "Category" },
    "it-IT": { title: "Risultati di Ricerca", emptyTitle: "Nessun prodotto trovato", emptyText: "Prova con nome prodotto, tema o codice prodotto.", categoryLabel: "Categoria" },
    "de-DE": { title: "Suchergebnisse", emptyTitle: "Keine passenden Produkte gefunden", emptyText: "Versuche einen Produktnamen, ein Thema oder einen Produktcode.", categoryLabel: "Kategorie" },
    "fr-FR": { title: "Résultats de Recherche", emptyTitle: "Aucun produit correspondant", emptyText: "Essayez un nom de produit, un thème ou un code produit.", categoryLabel: "Catégorie" },
    "es-ES": { title: "Resultados de Búsqueda", emptyTitle: "No se encontraron productos", emptyText: "Prueba con un nombre, tema o código de producto.", categoryLabel: "Categoría" },
    "nl-NL": { title: "Zoekresultaten", emptyTitle: "Geen passende producten gevonden", emptyText: "Probeer een productnaam, thema of productcode.", categoryLabel: "Categorie" },
    "pl-PL": { title: "Wyniki Wyszukiwania", emptyTitle: "Nie znaleziono pasujących produktów", emptyText: "Spróbuj nazwy produktu, motywu lub kodu produktu.", categoryLabel: "Kategoria" },
    "sv-SE": { title: "Sökresultat", emptyTitle: "Inga matchande produkter hittades", emptyText: "Prova ett produktnamn, tema eller produktkod.", categoryLabel: "Kategori" }
  };
  const SEARCH_INDEX = [
    { code: "FR-MEN-001", name: "Men's France T-Shirt", category: "Men", description: "Premium men's France t-shirt from the Cart/products collection.", priceEur: 200, image: "Cart/products/tshirt-men/Men france/Bk/80.jpg", href: "FR-MEN-001.html", tags: ["mens france t-shirt", "men france", "france tee", "cart products men france"] },
    { code: "IT-MEN-002", name: "Men's Italy T-Shirt", category: "Men", description: "Clean men's Italy graphic tee from the Cart/products collection.", priceEur: 220, image: "Cart/products/tshirt-men/men italy/Bk/80.jpg", href: "IT-MEN-002.html", tags: ["mens italy t shirt", "men italy", "italy tee"] },
    { code: "US-MEN-003", name: "Men's USA T-Shirt", category: "Men", description: "Bold men's USA print t-shirt for everyday wear.", priceEur: 230, image: "Cart/products/tshirt-men/men usa/Bk/80.jpg", href: "men.html", tags: ["mens usa t shirt", "men usa", "usa tee"] },
    { code: "FR-WOM-101", name: "Women's France T-Shirt", category: "Women", description: "Women's France t-shirt from the Cart/products collection.", priceEur: 220, image: "Cart/products/tshirt-women/women france/Wh/80.jpg", href: "woman.html", tags: ["womens france t shirt", "women france", "france tee women"] },
    { code: "IT-WOM-102", name: "Women's Italy T-Shirt", category: "Women", description: "Premium women's Italy t-shirt with refined artwork.", priceEur: 220, image: "Cart/products/tshirt-women/Women italy/Wh/80.jpg", href: "woman.html", tags: ["womens italy t shirt", "women italy", "italy tee women"] },
    { code: "JP-WOM-103", name: "Women's Japan T-Shirt", category: "Women", description: "Women's Japan t-shirt with expressive collection visuals.", priceEur: 220, image: "Cart/products/tshirt-women/Women japon/Wh/80.jpg", href: "woman.html", tags: ["womens japan t shirt", "women japan", "japan tee women"] },
    { code: "TD-KID-201", name: "Kids Teddy Bear Tee", category: "Kids", description: "Soft and playful kids tee with teddy bear theme.", priceEur: 180, image: "Cart/products/tshirt-men/Men france/Bk/80.jpg", href: "kids.html", tags: ["kids teddy bear tee", "teddy bear tee", "kids tee"] },
    { code: "UC-KID-202", name: "Kids Unicorn Dream Tee", category: "Kids", description: "Colorful unicorn design for the kids collection.", priceEur: 190, image: "Cart/products/tshirt-men/men usa/Bk/80.jpg", href: "kids.html", tags: ["kids unicorn dream tee", "unicorn tee", "kids unicorn"] },
    { code: "SP-KID-203", name: "Kids Space Rocket Tee", category: "Kids", description: "Space-themed rocket shirt for adventurous kids.", priceEur: 195, image: "Cart/products/tshirt-women/Women italy/Wh/80.jpg", href: "kids.html", tags: ["kids space rocket tee", "space rocket tee", "kids space"] },
    { code: "HOM-CS-301", name: "Cushion Cover", category: "Home & Living", description: "Decorative home accent with premium print finish.", priceEur: 49, image: "Cart/products/tshirt-men/Men france/Wh/80.jpg", href: "home-living.html", tags: ["cushion cover", "home living cushion"] },
    { code: "HOM-MG-302", name: "Ceramic Mug", category: "Home & Living", description: "Ceramic mug for daily use with artistic styling.", priceEur: 39, image: "Cart/products/tshirt-men/men italy/Wh/80.jpg", href: "home-living.html", tags: ["ceramic mug", "home living mug"] },
    { code: "HOM-BL-303", name: "Soft Blanket", category: "Home & Living", description: "Comfort-first blanket with premium home aesthetic.", priceEur: 69, image: "Cart/products/tshirt-women/Women italy/Bk/80.jpg", href: "home-living.html", tags: ["soft blanket", "home living blanket"] },
    { code: "ACC-CP-401", name: "Flexfit Cap", category: "Accessories", description: "Structured cap with clean premium streetwear finish.", priceEur: 65, image: "Cart/products/tshirt-men/Men france/Bl/80.jpg", href: "accessories.html", tags: ["flexfit cap", "cap", "accessories cap"] },
    { code: "ACC-TB-402", name: "Classic Tote Bag", category: "Accessories", description: "Classic tote bag designed for daily carry.", priceEur: 55, image: "Cart/products/tshirt-men/men italy/Bl/80.jpg", href: "accessories.html", tags: ["classic tote bag", "tote bag"] },
    { code: "ACC-PC-403", name: "Phone Case", category: "Accessories", description: "Protective phone case with sharp custom artwork.", priceEur: 39, image: "Cart/products/tshirt-women/women france/Bk/80.jpg", href: "accessories.html", tags: ["phone case", "accessories phone case"] },
    { code: "PD-001", name: "Premium Product Details Tee", category: "Product Details", description: "Direct access to the current ProductDetails page.", priceEur: 39, image: "Cart/products/tshirt-men/Men france/Bk/80.jpg", href: "ProductDetails.html", tags: ["product details", "productdetails", "details tee"] },
    { code: "CDP-MN-501", name: "Organic Unisex T-Shirt", category: "Custom Design", description: "Start designing on the organic unisex t-shirt base.", priceEur: 29, image: "Image/Banner/img/2.png", href: "Image/Custom%20Design%20Pro/OrganicUnisexT-Shirt.html", tags: ["organic unisex t shirt", "custom design men"] },
    { code: "CDP-WM-502", name: "Women Premium T-Shirt", category: "Custom Design", description: "Premium women blank ready for custom artwork.", priceEur: 31, image: "Image/Banner/img/3.png", href: "Image/Custom%20Design%20Pro/WomenPremiumT-Shirt.html", tags: ["women premium t shirt", "custom design women"] },
    { code: "CDP-KD-503", name: "Kids T-Shirt", category: "Custom Design", description: "Customizable kids t-shirt with easy print area.", priceEur: 24, image: "Image/Banner/img/4.png", href: "Image/Custom%20Design%20Pro/KidsT-Shirt.html", tags: ["kids t shirt", "custom design kids"] }
  ];

  function createSearchInput(host) {
    const input = document.createElement("input");
    input.type = "text";
    input.placeholder = "Search";
    input.setAttribute("aria-label", "Search products by name or code");
    host.prepend(input);
    return input;
  }

  function createSearchButton(host) {
    const button = document.createElement("button");
    button.type = "button";
    button.setAttribute("aria-label", "Search products");
    button.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i>';
    host.append(button);
    return button;
  }

  function ensureResultsPanel(host) {
    let panel = document.getElementById("gfSearchResults");
    if (panel) {
      return panel;
    }

    panel = document.createElement("div");
    panel.className = "gf-search-results";
    panel.id = "gfSearchResults";
    panel.hidden = true;
    panel.innerHTML =
      '<div class="gf-search-results-head">' +
      '<span id="gfSearchResultsTitle">Search Results</span>' +
      '<button type="button" class="gf-search-close" id="gfSearchClose" aria-label="Close search results"><i class="fa-solid fa-xmark"></i></button>' +
      '</div>' +
      '<div class="gf-search-results-list" id="gfSearchResultsList"></div>';
    host.append(panel);
    return panel;
  }

  function getLocaleCode() {
    const countryCode = localStorage.getItem(LOCALE_STORAGE_KEY) || DEFAULT_COUNTRY;
    const localeCode = COUNTRY_TO_LOCALE[countryCode] || "en-GB";
    return UI_TEXTS[localeCode] ? localeCode : (LOCALE_FALLBACK[localeCode] || "en-GB");
  }

  function getTexts() {
    return UI_TEXTS[getLocaleCode()] || UI_TEXTS["en-GB"];
  }

  function formatPrice(value) {
    const localeCode = getLocaleCode();
    const config = LOCALE_CONFIG[localeCode] || LOCALE_CONFIG["en-GB"];
    return new Intl.NumberFormat(localeCode, {
      style: "currency",
      currency: config.currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value * config.rateFromEUR);
  }

  function normalize(value) {
    return String(value || "")
      .toLowerCase()
      .trim()
      .replace(/[’']/g, "")
      .replace(/[^a-z0-9]+/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function scoreItem(item, query) {
    const code = normalize(item.code);
    const name = normalize(item.name);
    const category = normalize(item.category);
    const description = normalize(item.description);
    const tags = Array.isArray(item.tags) ? item.tags.map(normalize) : [];

    if (code === query || name === query) return 200;
    if (tags.some(function (tag) { return tag === query; })) return 190;
    if (code.startsWith(query)) return 120;
    if (name.startsWith(query)) return 110;
    if (tags.some(function (tag) { return tag.startsWith(query); })) return 100;
    if (name.includes(query)) return 80;
    if (tags.some(function (tag) { return tag.includes(query); })) return 70;
    if (category.includes(query)) return 40;
    if (description.includes(query)) return 30;
    return 0;
  }

  function openResults() {
    searchResults.hidden = false;
  }

  function closeResults() {
    searchResults.hidden = true;
  }

  function emptyMarkup() {
    const texts = getTexts();
    return '<div class="gf-search-empty"><strong>' + texts.emptyTitle + '</strong><span>' + texts.emptyText + '</span></div>';
  }

  function resultMarkup(item) {
    const texts = getTexts();
    return (
      '<a class="gf-search-result-item" href="' + item.href + '">' +
      '<img class="gf-search-thumb" src="' + item.image + '" alt="' + item.name + '">' +
      '<div class="gf-search-meta">' +
      '<div class="gf-search-topline">' +
      '<span class="gf-search-code">' + item.code + '</span>' +
      '<span class="gf-search-category">' + texts.categoryLabel + ': ' + item.category + '</span>' +
      '</div>' +
      '<div class="gf-search-name">' + item.name + '</div>' +
      '<div class="gf-search-description">' + item.description + '</div>' +
      '</div>' +
      '<span class="gf-search-price">' + formatPrice(item.priceEur) + '</span>' +
      '</a>'
    );
  }

  function renderResults(query) {
    const texts = getTexts();
    searchResultsTitle.textContent = texts.title;

    if (!query || query.length < 2) {
      searchResultsList.innerHTML = "";
      closeResults();
      return [];
    }

    const results = SEARCH_INDEX
      .map(function (item) {
        return { item: item, score: scoreItem(item, query) };
      })
      .filter(function (entry) {
        return entry.score > 0;
      })
      .sort(function (a, b) {
        return b.score - a.score || a.item.name.localeCompare(b.item.name);
      })
      .slice(0, 8)
      .map(function (entry) {
        return entry.item;
      });

    searchResultsList.innerHTML = results.length ? results.map(resultMarkup).join("") : emptyMarkup();
    openResults();
    return results;
  }

  function runSearch() {
    return renderResults(normalize(searchInput.value));
  }

  function rerunSearchIfEligible() {
    const query = normalize(searchInput.value);
    if (query.length >= 2) {
      renderResults(query);
    } else {
      closeResults();
    }
  }

  function submitSearch(event) {
    event.preventDefault();
    const results = runSearch();
    if (results[0]) {
      window.location.href = results[0].href;
    }
  }

  searchInput.id = searchInput.id || "gfSiteSearchInput";
  searchButton.id = searchButton.id || "gfSiteSearchButton";
  searchButton.type = "submit";

  searchInput.addEventListener("input", runSearch);
  searchInput.addEventListener("focus", rerunSearchIfEligible);
  searchInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      submitSearch(event);
    }
  });

  searchButton.addEventListener("click", submitSearch);
  if (searchHost.tagName === "FORM") {
    searchHost.addEventListener("submit", submitSearch);
  }

  if (searchClose) {
    searchClose.addEventListener("click", function () {
      closeResults();
    });
  }

  document.addEventListener("click", function (event) {
    if (!searchHost.contains(event.target)) {
      closeResults();
    }
  });

  document.querySelectorAll(".gf-locale-card").forEach(function (card) {
    card.addEventListener("click", function () {
      window.setTimeout(rerunSearchIfEligible, 0);
    });
  });

  const langObserver = new MutationObserver(function () {
    rerunSearchIfEligible();
  });
  langObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
});