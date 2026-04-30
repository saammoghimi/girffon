(function () {
  const cartProductConfig = window.cdpProductConfig || {};
  const DEFAULT_CART_COLOR = cartProductConfig.defaultColorName || "Sun Yellow";
  const DEFAULT_CART_PRODUCT = cartProductConfig.productName || "Custom Product";
  const FALLBACK_CART_TUTORIAL_URL = "https://www.youtube.com/watch?v=d6fYzNGZpFo&t=1s";
  const SHARED_CART_KEY = "girffon_cart";
  const LAST_CUSTOM_INVOICE_KEY = "girffon_last_custom_invoice";
  const CURRENT_PROJECT_KEY = "cdpCurrentProject";
  const CURRENT_FOLDER_KEY = "cdpCurrentFolder";
  const PROJECT_STORAGE_PREFIX = "cdpProject_";
  const CART_TEST_URL = "../../CartTest.html";
  const CART_STRINGS = {
    us: {
      orderSummary: "Order summary",
      cartInvoice: "Cart & Invoice",
      closeCartPanel: "Close cart panel",
      cartPreview: "Cart preview",
      color: "Color",
      size: "Size",
      view: "View",
      sizeColorLines: "Size & color lines",
      noLinesYet: "No lines yet",
      fit: "Fit",
      fitUnisex: "Unisex / Classic",
      fitWomen: "Women's Fit",
      matchDesignColor: "Match design color",
      useProductColor: "Use product color",
      recommendedColors: "Recommended colors",
      qty: "Qty",
      addLine: "Add line",
      addSizeLines: "Add size lines for XS to XXL with any color.",
      customLayers: "Custom layers",
      noCustomLayersYet: "No custom layers yet.",
      viewBreakdown: "View breakdown",
      noViewsYet: "No views yet.",
      baseProduct: "Base product",
      viewSurcharge: "View surcharge",
      customization: "Customization",
      total: "Total",
      viewLabel: "View: {value}",
      scanVerify: "Scan & verify",
      invoiceCapsule: "Invoice capsule",
      scanDetailsPlaceholder: "Color · Size · View",
      scanLayersPlaceholder: "Layers: --",
      continueEditing: "Continue editing",
      sendInvoice: "Send invoice",
      layer: "layer",
      layers: "layers",
      viewWord: "view",
      viewsWord: "views",
      layersLabel: "Layers",
      base: "Base",
      surcharge: "Surcharge",
      custom: "Custom",
      textLayer: "Text",
      layerSuffix: "layer",
      font: "Font",
      layerNumber: "Layer",
      noLayers: "No layers",
      none: "None",
      fitGeneric: "Fit",
      more: "more",
      sizes: "Sizes",
      pcs: "pcs",
      sizeWord: "Size",
      colorLabel: "Color",
      qtyLabel: "Qty",
      removeSizeLine: "Remove size line",
      invoiceSent: "Invoice packaged and sent",
      openTutorials: "Open tutorials"
    },
    it: {
      orderSummary: "Riepilogo ordine",
      cartInvoice: "Carrello e Fattura",
      closeCartPanel: "Chiudi pannello carrello",
      cartPreview: "Anteprima carrello",
      color: "Colore",
      size: "Taglia",
      view: "Vista",
      sizeColorLines: "Righe taglia e colore",
      noLinesYet: "Nessuna riga ancora",
      fit: "Vestibilità",
      fitUnisex: "Unisex / Classica",
      fitWomen: "Vestibilità donna",
      matchDesignColor: "Abbina il colore del design",
      useProductColor: "Usa il colore prodotto",
      recommendedColors: "Colori consigliati",
      qty: "Qtà",
      addLine: "Aggiungi riga",
      addSizeLines: "Aggiungi righe taglia da XS a XXL con qualsiasi colore.",
      customLayers: "Livelli personalizzati",
      noCustomLayersYet: "Nessun livello personalizzato.",
      viewBreakdown: "Dettaglio viste",
      noViewsYet: "Nessuna vista ancora.",
      baseProduct: "Prodotto base",
      viewSurcharge: "Supplemento vista",
      customization: "Personalizzazione",
      total: "Totale",
      viewLabel: "Vista: {value}",
      scanVerify: "Scansiona e verifica",
      invoiceCapsule: "Capsula fattura",
      scanDetailsPlaceholder: "Colore · Taglia · Vista",
      scanLayersPlaceholder: "Livelli: --",
      continueEditing: "Continua a modificare",
      sendInvoice: "Invia fattura",
      layer: "livello",
      layers: "livelli",
      viewWord: "vista",
      viewsWord: "viste",
      layersLabel: "Livelli",
      base: "Base",
      surcharge: "Supplemento",
      custom: "Personalizzazione",
      textLayer: "Testo",
      layerSuffix: "livello",
      font: "Font",
      layerNumber: "Livello",
      noLayers: "Nessun livello",
      none: "Nessuno",
      fitGeneric: "Vestibilità",
      more: "in più",
      sizes: "Taglie",
      pcs: "pz",
      sizeWord: "Taglia",
      colorLabel: "Colore",
      qtyLabel: "Qtà",
      removeSizeLine: "Rimuovi riga taglia",
      invoiceSent: "Fattura preparata e inviata",
      openTutorials: "Apri tutorial"
    },
    de: {
      orderSummary: "Bestellübersicht",
      cartInvoice: "Warenkorb & Rechnung",
      closeCartPanel: "Warenkorb schließen",
      cartPreview: "Warenkorb-Vorschau",
      color: "Farbe",
      size: "Größe",
      view: "Ansicht",
      sizeColorLines: "Größen- und Farbzeilen",
      noLinesYet: "Noch keine Zeilen",
      fit: "Passform",
      fitUnisex: "Unisex / Klassisch",
      fitWomen: "Damenpassform",
      matchDesignColor: "Designfarbe übernehmen",
      useProductColor: "Produktfarbe verwenden",
      recommendedColors: "Empfohlene Farben",
      qty: "Menge",
      addLine: "Zeile hinzufügen",
      addSizeLines: "Fügen Sie Größenzeilen von XS bis XXL in jeder Farbe hinzu.",
      customLayers: "Individuelle Ebenen",
      noCustomLayersYet: "Noch keine individuellen Ebenen.",
      viewBreakdown: "Ansichtsübersicht",
      noViewsYet: "Noch keine Ansichten.",
      baseProduct: "Basisprodukt",
      viewSurcharge: "Ansichtsaufschlag",
      customization: "Anpassung",
      total: "Gesamt",
      viewLabel: "Ansicht: {value}",
      scanVerify: "Scannen & prüfen",
      invoiceCapsule: "Rechnungskapsel",
      scanDetailsPlaceholder: "Farbe · Größe · Ansicht",
      scanLayersPlaceholder: "Ebenen: --",
      continueEditing: "Weiter bearbeiten",
      sendInvoice: "Rechnung senden",
      layer: "Ebene",
      layers: "Ebenen",
      viewWord: "Ansicht",
      viewsWord: "Ansichten",
      layersLabel: "Ebenen",
      base: "Basis",
      surcharge: "Aufschlag",
      custom: "Individuell",
      textLayer: "Text",
      layerSuffix: "Ebene",
      font: "Schrift",
      layerNumber: "Ebene",
      noLayers: "Keine Ebenen",
      none: "Keine",
      fitGeneric: "Passform",
      more: "mehr",
      sizes: "Größen",
      pcs: "Stk",
      sizeWord: "Größe",
      colorLabel: "Farbe",
      qtyLabel: "Menge",
      removeSizeLine: "Größenzeile entfernen",
      invoiceSent: "Rechnung erstellt und gesendet",
      openTutorials: "Tutorials öffnen"
    },
    fr: {
      orderSummary: "Résumé de commande",
      cartInvoice: "Panier et facture",
      closeCartPanel: "Fermer le panier",
      cartPreview: "Aperçu du panier",
      color: "Couleur",
      size: "Taille",
      view: "Vue",
      sizeColorLines: "Lignes taille et couleur",
      noLinesYet: "Aucune ligne pour le moment",
      fit: "Coupe",
      fitUnisex: "Unisexe / Classique",
      fitWomen: "Coupe femme",
      matchDesignColor: "Associer la couleur du design",
      useProductColor: "Utiliser la couleur du produit",
      recommendedColors: "Couleurs recommandées",
      qty: "Qté",
      addLine: "Ajouter une ligne",
      addSizeLines: "Ajoutez des lignes de taille de XS à XXL avec n'importe quelle couleur.",
      customLayers: "Calques personnalisés",
      noCustomLayersYet: "Aucun calque personnalisé.",
      viewBreakdown: "Répartition des vues",
      noViewsYet: "Aucune vue pour le moment.",
      baseProduct: "Produit de base",
      viewSurcharge: "Supplément de vue",
      customization: "Personnalisation",
      total: "Total",
      viewLabel: "Vue : {value}",
      scanVerify: "Scanner et vérifier",
      invoiceCapsule: "Capsule de facture",
      scanDetailsPlaceholder: "Couleur · Taille · Vue",
      scanLayersPlaceholder: "Calques : --",
      continueEditing: "Continuer la modification",
      sendInvoice: "Envoyer la facture",
      layer: "calque",
      layers: "calques",
      viewWord: "vue",
      viewsWord: "vues",
      layersLabel: "Calques",
      base: "Base",
      surcharge: "Supplément",
      custom: "Personnalisation",
      textLayer: "Texte",
      layerSuffix: "calque",
      font: "Police",
      layerNumber: "Calque",
      noLayers: "Aucun calque",
      none: "Aucun",
      fitGeneric: "Coupe",
      more: "de plus",
      sizes: "Tailles",
      pcs: "pcs",
      sizeWord: "Taille",
      colorLabel: "Couleur",
      qtyLabel: "Qté",
      removeSizeLine: "Supprimer la ligne de taille",
      invoiceSent: "Facture préparée et envoyée",
      openTutorials: "Ouvrir les tutoriels"
    },
    es: {
      orderSummary: "Resumen del pedido",
      cartInvoice: "Carrito y factura",
      closeCartPanel: "Cerrar carrito",
      cartPreview: "Vista previa del carrito",
      color: "Color",
      size: "Talla",
      view: "Vista",
      sizeColorLines: "Líneas de talla y color",
      noLinesYet: "Sin líneas todavía",
      fit: "Ajuste",
      fitUnisex: "Unisex / Clásico",
      fitWomen: "Ajuste mujer",
      matchDesignColor: "Igualar color del diseño",
      useProductColor: "Usar color del producto",
      recommendedColors: "Colores recomendados",
      qty: "Cant.",
      addLine: "Agregar línea",
      addSizeLines: "Añade líneas de talla de XS a XXL con cualquier color.",
      customLayers: "Capas personalizadas",
      noCustomLayersYet: "Aún no hay capas personalizadas.",
      viewBreakdown: "Desglose por vista",
      noViewsYet: "Aún no hay vistas.",
      baseProduct: "Producto base",
      viewSurcharge: "Recargo por vista",
      customization: "Personalización",
      total: "Total",
      viewLabel: "Vista: {value}",
      scanVerify: "Escanear y verificar",
      invoiceCapsule: "Cápsula de factura",
      scanDetailsPlaceholder: "Color · Talla · Vista",
      scanLayersPlaceholder: "Capas: --",
      continueEditing: "Seguir editando",
      sendInvoice: "Enviar factura",
      layer: "capa",
      layers: "capas",
      viewWord: "vista",
      viewsWord: "vistas",
      layersLabel: "Capas",
      base: "Base",
      surcharge: "Recargo",
      custom: "Personalización",
      textLayer: "Texto",
      layerSuffix: "capa",
      font: "Fuente",
      layerNumber: "Capa",
      noLayers: "Sin capas",
      none: "Ninguno",
      fitGeneric: "Ajuste",
      more: "más",
      sizes: "Tallas",
      pcs: "uds",
      sizeWord: "Talla",
      colorLabel: "Color",
      qtyLabel: "Cant.",
      removeSizeLine: "Eliminar línea de talla",
      invoiceSent: "Factura preparada y enviada",
      openTutorials: "Abrir tutoriales"
    },
    nl: {
      orderSummary: "Besteloverzicht",
      cartInvoice: "Winkelwagen en factuur",
      closeCartPanel: "Winkelwagen sluiten",
      cartPreview: "Winkelwagenvoorbeeld",
      color: "Kleur",
      size: "Maat",
      view: "Weergave",
      sizeColorLines: "Maat- en kleurregels",
      noLinesYet: "Nog geen regels",
      fit: "Pasvorm",
      fitUnisex: "Unisex / Klassiek",
      fitWomen: "Damespasvorm",
      matchDesignColor: "Ontwerpkleur overnemen",
      useProductColor: "Productkleur gebruiken",
      recommendedColors: "Aanbevolen kleuren",
      qty: "Aantal",
      addLine: "Regel toevoegen",
      addSizeLines: "Voeg maatregels van XS tot XXL toe in elke kleur.",
      customLayers: "Aangepaste lagen",
      noCustomLayersYet: "Nog geen aangepaste lagen.",
      viewBreakdown: "Weergave-overzicht",
      noViewsYet: "Nog geen weergaven.",
      baseProduct: "Basisproduct",
      viewSurcharge: "Weergavetoeslag",
      customization: "Personalisatie",
      total: "Totaal",
      viewLabel: "Weergave: {value}",
      scanVerify: "Scannen en controleren",
      invoiceCapsule: "Factuurcapsule",
      scanDetailsPlaceholder: "Kleur · Maat · Weergave",
      scanLayersPlaceholder: "Lagen: --",
      continueEditing: "Verder bewerken",
      sendInvoice: "Factuur verzenden",
      layer: "laag",
      layers: "lagen",
      viewWord: "weergave",
      viewsWord: "weergaven",
      layersLabel: "Lagen",
      base: "Basis",
      surcharge: "Toeslag",
      custom: "Aangepast",
      textLayer: "Tekst",
      layerSuffix: "laag",
      font: "Lettertype",
      layerNumber: "Laag",
      noLayers: "Geen lagen",
      none: "Geen",
      fitGeneric: "Pasvorm",
      more: "meer",
      sizes: "Maten",
      pcs: "st",
      sizeWord: "Maat",
      colorLabel: "Kleur",
      qtyLabel: "Aantal",
      removeSizeLine: "Maatregel verwijderen",
      invoiceSent: "Factuur voorbereid en verzonden",
      openTutorials: "Tutorials openen"
    },
    pl: {
      orderSummary: "Podsumowanie zamówienia",
      cartInvoice: "Koszyk i faktura",
      closeCartPanel: "Zamknij koszyk",
      cartPreview: "Podgląd koszyka",
      color: "Kolor",
      size: "Rozmiar",
      view: "Widok",
      sizeColorLines: "Linie rozmiaru i koloru",
      noLinesYet: "Brak linii",
      fit: "Krój",
      fitUnisex: "Unisex / Klasyczny",
      fitWomen: "Krój damski",
      matchDesignColor: "Dopasuj kolor projektu",
      useProductColor: "Użyj koloru produktu",
      recommendedColors: "Polecane kolory",
      qty: "Ilość",
      addLine: "Dodaj linię",
      addSizeLines: "Dodaj linie rozmiarów od XS do XXL w dowolnym kolorze.",
      customLayers: "Warstwy własne",
      noCustomLayersYet: "Brak własnych warstw.",
      viewBreakdown: "Podział widoków",
      noViewsYet: "Brak widoków.",
      baseProduct: "Produkt bazowy",
      viewSurcharge: "Dopłata za widok",
      customization: "Personalizacja",
      total: "Razem",
      viewLabel: "Widok: {value}",
      scanVerify: "Skanuj i sprawdź",
      invoiceCapsule: "Kapsuła faktury",
      scanDetailsPlaceholder: "Kolor · Rozmiar · Widok",
      scanLayersPlaceholder: "Warstwy: --",
      continueEditing: "Kontynuuj edycję",
      sendInvoice: "Wyślij fakturę",
      layer: "warstwa",
      layers: "warstwy",
      viewWord: "widok",
      viewsWord: "widoki",
      layersLabel: "Warstwy",
      base: "Baza",
      surcharge: "Dopłata",
      custom: "Personalizacja",
      textLayer: "Tekst",
      layerSuffix: "warstwa",
      font: "Czcionka",
      layerNumber: "Warstwa",
      noLayers: "Brak warstw",
      none: "Brak",
      fitGeneric: "Krój",
      more: "więcej",
      sizes: "Rozmiary",
      pcs: "szt.",
      sizeWord: "Rozmiar",
      colorLabel: "Kolor",
      qtyLabel: "Ilość",
      removeSizeLine: "Usuń linię rozmiaru",
      invoiceSent: "Faktura przygotowana i wysłana",
      openTutorials: "Otwórz samouczki"
    },
    sv: {
      orderSummary: "Ordersammanfattning",
      cartInvoice: "Varukorg och faktura",
      closeCartPanel: "Stäng varukorgen",
      cartPreview: "Förhandsvisning av varukorg",
      color: "Färg",
      size: "Storlek",
      view: "Vy",
      sizeColorLines: "Storleks- och färgrader",
      noLinesYet: "Inga rader ännu",
      fit: "Passform",
      fitUnisex: "Unisex / Klassisk",
      fitWomen: "Dammodell",
      matchDesignColor: "Matcha designfärg",
      useProductColor: "Använd produktfärg",
      recommendedColors: "Rekommenderade färger",
      qty: "Antal",
      addLine: "Lägg till rad",
      addSizeLines: "Lägg till storleksrader från XS till XXL i valfri färg.",
      customLayers: "Anpassade lager",
      noCustomLayersYet: "Inga anpassade lager ännu.",
      viewBreakdown: "Vyöversikt",
      noViewsYet: "Inga vyer ännu.",
      baseProduct: "Basprodukt",
      viewSurcharge: "Vytillägg",
      customization: "Anpassning",
      total: "Totalt",
      viewLabel: "Vy: {value}",
      scanVerify: "Skanna och verifiera",
      invoiceCapsule: "Fakturakapsel",
      scanDetailsPlaceholder: "Färg · Storlek · Vy",
      scanLayersPlaceholder: "Lager: --",
      continueEditing: "Fortsätt redigera",
      sendInvoice: "Skicka faktura",
      layer: "lager",
      layers: "lager",
      viewWord: "vy",
      viewsWord: "vyer",
      layersLabel: "Lager",
      base: "Bas",
      surcharge: "Tillägg",
      custom: "Anpassat",
      textLayer: "Text",
      layerSuffix: "lager",
      font: "Typsnitt",
      layerNumber: "Lager",
      noLayers: "Inga lager",
      none: "Ingen",
      fitGeneric: "Passform",
      more: "mer",
      sizes: "Storlekar",
      pcs: "st",
      sizeWord: "Storlek",
      colorLabel: "Färg",
      qtyLabel: "Antal",
      removeSizeLine: "Ta bort storleksrad",
      invoiceSent: "Fakturan har förberetts och skickats",
      openTutorials: "Öppna guider"
    }
  };
  const CART_FALLBACKS = { gb: "us", ca: "us", ch: "de" };
  const CART_VIEW_LABELS = {
    us: { front: "Front", back: "Back", right: "Right", left: "Left" },
    it: { front: "Fronte", back: "Retro", right: "Destra", left: "Sinistra" },
    de: { front: "Vorne", back: "Hinten", right: "Rechts", left: "Links" },
    fr: { front: "Avant", back: "Arrière", right: "Droite", left: "Gauche" },
    es: { front: "Frente", back: "Espalda", right: "Derecha", left: "Izquierda" },
    nl: { front: "Voor", back: "Achter", right: "Rechts", left: "Links" },
    pl: { front: "Przód", back: "Tył", right: "Prawa", left: "Lewa" },
    sv: { front: "Fram", back: "Bak", right: "Höger", left: "Vänster" }
  };
  const CART_LINE_WORDS = {
    us: { one: "line", many: "lines" },
    it: { one: "riga", many: "righe" },
    de: { one: "Zeile", many: "Zeilen" },
    fr: { one: "ligne", many: "lignes" },
    es: { one: "línea", many: "líneas" },
    nl: { one: "regel", many: "regels" },
    pl: { one: "linia", many: "linie" },
    sv: { one: "rad", many: "rader" }
  };

  const BASE_PRICE_PRESETS = new Map([
    ["maglietta uomo – basic", 19.99],
    ["maglietta uomo - basic", 19.99],
    ["maglietta premium uomo bianco", 19.99],
    ["maglietta unisex ecologica nero", 21.99],
    ["basic men's t-shirt", 19.99],
    ["basic men's t-shirt white", 19.99],
    ["organic unisex t-shirt", 21.99],
    ["organic unisex t-shirt black", 21.99],
    ["*", 19.99]
  ]);

  const LAYER_FEES = {
    fill: 1.0,
    text: 0,
    design: 0,
    icon: 0,
    flag: 0,
    shape: 0,
    upload: 0,
    default: 0
  };

  const LAYER_ICONS = {
    text: "fa-solid fa-font",
    design: "fa-solid fa-layer-group",
    icon: "fa-regular fa-face-smile",
    flag: "fa-solid fa-flag",
    shape: "fa-regular fa-square",
    upload: "fa-solid fa-cloud-arrow-up",
    fill: "fa-solid fa-fill-drip",
    default: "fa-solid fa-layer-group"
  };

  const VIEW_FEES = {
    front: 0,
    back: 7,
    right: 1,
    left: 1
  };

  const VIEW_ORDER = ["front", "back", "right", "left"];
  const VIEW_LABELS = {
    front: "Front",
    back: "Back",
    right: "Right",
    left: "Left"
  };

  const FIT_LABELS = {
    unisex: "Unisex / Classic",
    women: "Women's Fit"
  };

  let sizeEntryCounter = 1;

  function resolveCartLang() {
    const raw = (localStorage.getItem("cdpLang") || "us").toLowerCase();
    if (CART_STRINGS[raw]) return raw;
    return CART_FALLBACKS[raw] || "us";
  }

  function t(key) {
    const lang = resolveCartLang();
    const dict = CART_STRINGS[lang] || CART_STRINGS.us;
    return dict[key] || CART_STRINGS.us[key] || "";
  }

  function getIntlLocale() {
    const lang = resolveCartLang();
    const localeMap = {
      us: "en-US",
      it: "it-IT",
      de: "de-DE",
      fr: "fr-FR",
      es: "es-ES",
      nl: "nl-NL",
      pl: "pl-PL",
      sv: "sv-SE"
    };
    return localeMap[lang] || "en-US";
  }

  function labelWithCount(count, singularKey, pluralKey) {
    return `${count} ${count === 1 ? t(singularKey) : t(pluralKey)}`;
  }

  function getViewLabel(view) {
    const lang = resolveCartLang();
    const labels = CART_VIEW_LABELS[lang] || CART_VIEW_LABELS.us;
    return labels[view] || capitalize(view);
  }

  function getLineLabel(count) {
    const lang = resolveCartLang();
    const words = CART_LINE_WORDS[lang] || CART_LINE_WORDS.us;
    return `${count} ${count === 1 ? words.one : words.many}`;
  }

  function safeReadSharedCart() {
    try {
      const raw = localStorage.getItem(SHARED_CART_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_err) {
      return [];
    }
  }

  function safeWriteSharedCart(value) {
    try {
      localStorage.setItem(SHARED_CART_KEY, JSON.stringify(value));
    } catch (_err) {
      // Ignore localStorage write failures.
    }
  }

  function slugify(value) {
    return String(value || "custom-product")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "") || "custom-product";
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (typeof window.cdpCartTutorialUrl !== "string" || !window.cdpCartTutorialUrl.trim()) {
      window.cdpCartTutorialUrl = FALLBACK_CART_TUTORIAL_URL;
    }

    const cartBtn = document.querySelector('[data-tool="cart"]');
    const cartPanel = document.getElementById("cdpCartPanel");
    if (!cartBtn || !cartPanel) {
      console.warn("cart.js: panel or trigger missing");
      return;
    }

    const panelInner = cartPanel.querySelector(".cdp-cart-panel-inner");
    ensureCartHelpStructure();
    const closeTriggers = cartPanel.querySelectorAll('[data-cart-close]');
    const sendBtn = cartPanel.querySelector('[data-cart-send]');
    const cartState = {
      lastSnapshot: null,
      basePrices: new Map(BASE_PRICE_PRESETS),
      layerFees: { ...LAYER_FEES },
      sizeRequests: [],
      invoiceAttachments: []
    };

    const elements = {
      preview: document.getElementById("cdpCartPreview"),
      productName: document.getElementById("cdpCartProductName"),
      color: document.getElementById("cdpCartColor"),
      size: document.getElementById("cdpCartSize"),
      viewLabel: document.getElementById("cdpCartViewLabel"),
      viewMeta: document.getElementById("cdpCartViewMeta"),
      price: document.getElementById("cdpCartPrice"),
      viewFee: document.getElementById("cdpCartViewFee"),
      customFee: document.getElementById("cdpCartCustomFee"),
      total: document.getElementById("cdpCartTotal"),
      timestamp: document.getElementById("cdpCartTimestamp"),
      customViews: document.getElementById("cdpCartCustomViews"),
      customEmpty: document.getElementById("cdpCartCustomEmpty"),
      count: document.getElementById("cdpCartCustomizationCount"),
      viewList: document.getElementById("cdpCartViewList"),
      viewCount: document.getElementById("cdpCartViewCount"),
      scanPrice: document.getElementById("cdpCartScanPrice"),
      scanProduct: document.getElementById("cdpCartScanProduct"),
      scanDetails: document.getElementById("cdpCartScanDetails"),
      scanLayers: document.getElementById("cdpCartScanLayers"),
      scanCode: document.getElementById("cdpCartScanCode"),
      payload: document.getElementById("cdpCartInvoicePayload"),
      sizeFit: document.getElementById("cdpCartSizeFit"),
      sizeSelect: document.getElementById("cdpCartSizeSelect"),
      sizeColor: document.getElementById("cdpCartSizeColor"),
      colorDatalist: document.getElementById("cdpCartColorOptions"),
      sizeQty: document.getElementById("cdpCartSizeQty"),
      sizeAdd: cartPanel.querySelector('[data-size-add]'),
      sizeList: document.getElementById("cdpCartSizeList"),
      sizeEmpty: document.getElementById("cdpCartSizeEmpty"),
      sizeCount: document.getElementById("cdpCartSizeCount"),
      sizeColorChoices: document.getElementById("cdpCartColorChoices"),
      sizeColorSync: cartPanel.querySelector('[data-size-color-sync]'),
      helpToggle: cartPanel.querySelector('[data-cart-help-toggle]'),
      sendBtn
    };

    let toastEl = null;
    let toastTimer = null;
    let invoiceModal = null;
    let invoiceModalResolver = null;
    let invoiceModalState = {
      snapshot: null,
      attachments: []
    };

    initSizeRequestControls();
    renderSizeRequests();
    populateColorOptions();
    initCartHelpTrigger();
    applyCartTranslations();

    function openCartPanel() {
      renderCart();
      syncSizeColorInput(cartState.lastSnapshot);
      cartPanel.setAttribute("data-visible", "true");
      cartPanel.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    }

    function closeCartPanel() {
      cartPanel.setAttribute("data-visible", "false");
      cartPanel.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }

    function applyCartTranslations() {
      const cartTrigger = document.querySelector('[data-tool="cart"]');
      if (cartTrigger) {
        cartTrigger.setAttribute("title", t("cartInvoice"));
        cartTrigger.setAttribute("aria-label", t("cartInvoice"));
      }

      const eyebrowNodes = cartPanel.querySelectorAll(".cdp-cart-eyebrow");
      if (eyebrowNodes[0]) eyebrowNodes[0].textContent = t("orderSummary");
      if (eyebrowNodes[1]) eyebrowNodes[1].textContent = t("scanVerify");

      if (panelInner) {
        panelInner.setAttribute("aria-label", t("cartInvoice"));
      }

      const titleEl = document.getElementById("cdpCartTitle");
      if (titleEl) titleEl.textContent = t("cartInvoice");

      const closeBtn = cartPanel.querySelector(".cdp-cart-close-btn");
      if (closeBtn) closeBtn.setAttribute("aria-label", t("closeCartPanel"));

      if (elements.preview) elements.preview.alt = t("cartPreview");

      const summaryTerms = cartPanel.querySelectorAll(".cdp-cart-summary dt");
      if (summaryTerms[0]) summaryTerms[0].textContent = t("color");
      if (summaryTerms[1]) summaryTerms[1].textContent = t("size");
      if (summaryTerms[2]) summaryTerms[2].textContent = t("view");

      const sectionTitles = cartPanel.querySelectorAll(".cdp-cart-section-header h4");
      if (sectionTitles[0]) sectionTitles[0].textContent = t("sizeColorLines");
      if (sectionTitles[1]) sectionTitles[1].textContent = t("customLayers");
      if (sectionTitles[2]) sectionTitles[2].textContent = t("viewBreakdown");

      const sizeFieldLabels = cartPanel.querySelectorAll(".cdp-cart-size-field > span");
      if (sizeFieldLabels[0]) sizeFieldLabels[0].textContent = t("fit");
      if (sizeFieldLabels[1]) sizeFieldLabels[1].textContent = t("size");
      if (sizeFieldLabels[2]) sizeFieldLabels[2].textContent = t("color");
      if (sizeFieldLabels[3]) sizeFieldLabels[3].textContent = t("qty");

      if (elements.sizeFit?.options[0]) elements.sizeFit.options[0].textContent = t("fitUnisex");
      if (elements.sizeFit?.options[1]) elements.sizeFit.options[1].textContent = t("fitWomen");

      if (elements.sizeColor) elements.sizeColor.placeholder = t("matchDesignColor");

      if (elements.sizeColorChoices) elements.sizeColorChoices.setAttribute("aria-label", t("recommendedColors"));

      const syncBtnLabel = cartPanel.querySelector("[data-size-color-sync]");
      if (syncBtnLabel) {
        syncBtnLabel.innerHTML = '<i class="fa-solid fa-droplet"></i> ' + t("useProductColor");
      }

      if (elements.sizeAdd) {
        elements.sizeAdd.innerHTML = '<i class="fa-solid fa-plus"></i> ' + t("addLine");
      }

      if (elements.sizeEmpty) elements.sizeEmpty.textContent = t("addSizeLines");
      if (elements.customEmpty) elements.customEmpty.textContent = t("noCustomLayersYet");

      const totalRows = cartPanel.querySelectorAll(".cdp-cart-total-row > span:first-child");
      if (totalRows[0]) totalRows[0].textContent = t("baseProduct");
      if (totalRows[1]) totalRows[1].textContent = t("viewSurcharge");
      if (totalRows[2]) totalRows[2].textContent = t("customization");
      if (totalRows[3]) totalRows[3].textContent = t("total");

      const invoiceTitle = cartPanel.querySelector(".cdp-cart-scan-header h4");
      if (invoiceTitle) invoiceTitle.textContent = t("invoiceCapsule");

      if (elements.scanDetails && (!elements.scanDetails.textContent || elements.scanDetails.textContent === "Color · Size · View")) {
        elements.scanDetails.textContent = t("scanDetailsPlaceholder");
      }
      if (elements.scanLayers && (!elements.scanLayers.textContent || elements.scanLayers.textContent === "Layers: --")) {
        elements.scanLayers.textContent = t("scanLayersPlaceholder");
      }

      const footerButtons = cartPanel.querySelectorAll(".cdp-cart-footer button");
      if (footerButtons[0]) footerButtons[0].textContent = t("continueEditing");
      if (footerButtons[1]) footerButtons[1].textContent = t("sendInvoice");

      renderSizeRequests();
      if (cartState.lastSnapshot) {
        updateSummary(cartState.lastSnapshot);
        updateCustomizations(cartState.lastSnapshot);
        updateViewBreakdown(cartState.lastSnapshot);
        updateTotals(cartState.lastSnapshot);
      }
    }

    async function renderCart() {
      const snapshot = await buildSnapshot();
      cartState.lastSnapshot = snapshot;
      updateSummary(snapshot);
      updateCustomizations(snapshot);
      updateViewBreakdown(snapshot);
      updateTotals(snapshot);
    }

    function getRenderableChildren(box) {
      return Array.from(box?.children || []).filter((child) => child instanceof Element && child.dataset?.layerIgnore !== "true");
    }

    function getPreviewBox(view = "") {
      const normalizedView = String(view || "").trim().toLowerCase();
      if (normalizedView) {
        const matchedBox = document.querySelector(`.cdp-print-box[data-view="${normalizedView}"]`);
        if (matchedBox) {
          return matchedBox;
        }
      }
      return document.querySelector('.cdp-print-box:not(.cdp-print-box--hidden)');
    }

    async function renderPrintBoxToDataUrl(box, options = {}) {
      const fallbackPreview = "";
      if (!(box instanceof Element)) {
        return fallbackPreview;
      }

      const boxRect = box.getBoundingClientRect();
      if (!boxRect.width || !boxRect.height || !hasRenderableLayerContent(box)) {
        return fallbackPreview;
      }

      const boxWidth = Math.max(1, Math.round(boxRect.width));
      const boxHeight = Math.max(1, Math.round(boxRect.height));
      const canvas = document.createElement('canvas');
      canvas.width = boxWidth;
      canvas.height = boxHeight;
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        return fallbackPreview;
      }

      if (options.backgroundColor) {
        ctx.save();
        ctx.fillStyle = options.backgroundColor;
        ctx.fillRect(0, 0, boxWidth, boxHeight);
        ctx.restore();
      }

      let drewAnyLayer = false;
      const childElements = getRenderableChildren(box);

      for (const child of childElements) {
        const rendered = await drawElementPreviewToCanvas(ctx, child, boxRect);
        drewAnyLayer = drewAnyLayer || rendered;
      }

      if (options.includeFrame) {
        ctx.save();
        ctx.strokeStyle = options.frameColor || '#111827';
        ctx.lineWidth = Number(options.frameWidth) || 2;
        ctx.setLineDash(Array.isArray(options.frameDash) ? options.frameDash : [8, 6]);
        const inset = ctx.lineWidth / 2;
        ctx.strokeRect(inset, inset, boxWidth - ctx.lineWidth, boxHeight - ctx.lineWidth);
        ctx.restore();
      }

      return drewAnyLayer || options.includeFrame ? canvas.toDataURL('image/png') : fallbackPreview;
    }

    async function captureComposedPreview() {
      return renderPrintBoxToDataUrl(getPreviewBox());
    }

    function hasRenderableLayerContent(box) {
      if (!(box instanceof Element)) {
        return false;
      }
      return getRenderableChildren(box).length > 0;
    }

    function loadPreviewImage(src) {
      if (!src) {
        return Promise.resolve(null);
      }
      return new Promise((resolve) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => resolve(null);
        image.src = src;
      });
    }

    function buildSvgDataUrl(svgElement) {
      if (!(svgElement instanceof SVGElement)) {
        return "";
      }
      const serialized = new XMLSerializer().serializeToString(svgElement);
      return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(serialized)}`;
    }

    function getPreviewBounds(element, boxRect) {
      const rect = element.getBoundingClientRect();
      return {
        x: rect.left - boxRect.left,
        y: rect.top - boxRect.top,
        width: rect.width,
        height: rect.height,
      };
    }

    function drawPreviewBackground(ctx, style, bounds) {
      const backgroundColor = style.backgroundColor || "";
      const hasSolidBackground = backgroundColor && backgroundColor !== "transparent" && backgroundColor !== "rgba(0, 0, 0, 0)";
      if (!hasSolidBackground) {
        return false;
      }
      ctx.save();
      ctx.fillStyle = backgroundColor;
      ctx.fillRect(bounds.x, bounds.y, bounds.width, bounds.height);
      ctx.restore();
      return true;
    }

    function drawPreviewBorder(ctx, style, bounds) {
      const borderWidth = Number.parseFloat(style.borderTopWidth || "0");
      const borderStyle = style.borderTopStyle || "none";
      const borderColor = style.borderTopColor || "";
      if (!borderWidth || borderStyle === "none" || !borderColor || borderColor === "transparent") {
        return false;
      }
      ctx.save();
      ctx.strokeStyle = borderColor;
      ctx.lineWidth = borderWidth;
      const inset = borderWidth / 2;
      ctx.strokeRect(bounds.x + inset, bounds.y + inset, Math.max(0, bounds.width - borderWidth), Math.max(0, bounds.height - borderWidth));
      ctx.restore();
      return true;
    }

    function drawPreviewText(ctx, element, style, bounds) {
      const textValue = (element.textContent || "").trim();
      if (!textValue) {
        return false;
      }

      const fontSize = style.fontSize || "16px";
      const lineHeightValue = Number.parseFloat(style.lineHeight || "");
      const lineHeight = Number.isFinite(lineHeightValue) ? lineHeightValue : Number.parseFloat(fontSize) * 1.2;
      const textAlign = style.textAlign || "left";
      const paddingLeft = Number.parseFloat(style.paddingLeft || "0") || 0;
      const paddingRight = Number.parseFloat(style.paddingRight || "0") || 0;
      let x = bounds.x + paddingLeft;

      if (textAlign === "center") {
        x = bounds.x + bounds.width / 2;
      } else if (textAlign === "right" || textAlign === "end") {
        x = bounds.x + bounds.width - paddingRight;
      }

      ctx.save();
      ctx.fillStyle = style.color || "#111111";
      ctx.font = `${style.fontWeight || "400"} ${fontSize} ${style.fontFamily || "sans-serif"}`;
      ctx.textAlign = textAlign === "center" ? "center" : (textAlign === "right" || textAlign === "end" ? "right" : "left");
      ctx.textBaseline = "top";

      String(textValue)
        .split(/\r?\n/)
        .forEach((line, index) => {
          ctx.fillText(line, x, bounds.y + index * lineHeight, bounds.width);
        });

      ctx.restore();
      return true;
    }

    async function drawElementPreviewToCanvas(ctx, element, boxRect) {
      if (!(element instanceof Element)) {
        return false;
      }

      const bounds = getPreviewBounds(element, boxRect);
      if (!bounds.width || !bounds.height) {
        return false;
      }

      const style = window.getComputedStyle(element);
      const opacity = Number.parseFloat(style.opacity || element.style.opacity || "1");
      const previousAlpha = ctx.globalAlpha;
      ctx.globalAlpha = previousAlpha * (Number.isFinite(opacity) ? opacity : 1);

      try {
        let drewLayer = drawPreviewBackground(ctx, style, bounds);
        drewLayer = drawPreviewBorder(ctx, style, bounds) || drewLayer;

        const imageEl = element.tagName === "IMG" ? element : element.querySelector("img");
        if (imageEl) {
          const image = await loadPreviewImage(imageEl.currentSrc || imageEl.src || element.dataset?.optimizedSrc || element.dataset?.originalSrc || "");
          if (image) {
            ctx.drawImage(image, bounds.x, bounds.y, bounds.width, bounds.height);
            return true;
          }
        }

        const svgElement = element.tagName === "svg" ? element : element.querySelector("svg");
        if (svgElement) {
          const svgImage = await loadPreviewImage(buildSvgDataUrl(svgElement));
          if (svgImage) {
            ctx.drawImage(svgImage, bounds.x, bounds.y, bounds.width, bounds.height);
            return true;
          }
        }

        drewLayer = drawPreviewText(ctx, element, style, bounds) || drewLayer;
        return drewLayer;
      } finally {
        ctx.globalAlpha = previousAlpha;
      }
    }

    function isGeneratedPreview(value) {
      return typeof value === "string" && /^data:image\//i.test(value.trim());
    }

    function syncLayersFromDOM() {
      if (window.cdpLayers && typeof window.cdpLayers.refreshFromDOM === "function") {
        window.cdpLayers.refreshFromDOM(window.cdpLayers.getLayers());
      }
    }

    async function buildSnapshot() {
      syncLayersFromDOM();
      const productName = getTextContent(".cdp-product-name", DEFAULT_CART_PRODUCT);
      const normalized = normalizeName(productName);
      const basePrice = determinePrice(normalized);
      const size = getTextContent("#cdpSizeValue", "M");
      const color = getTextContent("#cdpColorName", DEFAULT_CART_COLOR);
      const activeView = (document.getElementById("cdpShirtImage")?.dataset.view || "front").toLowerCase();
      const previewSrc = await captureComposedPreview();
      const preservedPreview = isGeneratedPreview(cartState.lastSnapshot?.image) ? cartState.lastSnapshot.image : "";

      const viewBreakdown = buildViewBreakdown(basePrice);
      const viewMap = new Map(viewBreakdown.map((entry) => [entry.view, entry]));
      const activeEntry = viewMap.get(activeView) || createEmptyViewEntry(activeView);

      const invoiceViewFee = viewBreakdown.reduce((sum, entry) => sum + (entry.viewFee || 0), 0);
      const invoiceCustomTotal = viewBreakdown.reduce((sum, entry) => sum + (entry.customTotal || 0), 0);
      const total = viewBreakdown.reduce((sum, entry) => sum + (entry.subtotal || 0), 0);
      const sizeRequests = cartState.sizeRequests.map((entry) => ({ ...entry }));
      const quantity = computeOrderQuantity(sizeRequests);
      const unitTotal = total;
      const orderTotal = unitTotal * quantity;
      const invoiceAttachments = await resolveInvoiceAttachments({
        productName,
        size,
        color,
        view: activeView
      });

      return {
        productName,
        size,
        color,
        view: activeView,
        image: isGeneratedPreview(previewSrc) ? previewSrc : preservedPreview,
        price: basePrice,
        layers: activeEntry.layers || [],
        customTotal: activeEntry.customTotal || 0,
        invoiceCustomTotal,
        invoiceViewFee,
        viewBreakdown,
        sizeRequests,
        invoiceAttachments,
        quantity,
        unitTotal,
        orderTotal,
        total,
        generatedAt: new Date()
      };
    }

    function getTextContent(selector, fallback) {
      const node = document.querySelector(selector);
      return node ? node.textContent.trim() || fallback : fallback;
    }

    function determinePrice(normalizedName) {
      if (cartState.basePrices.has(normalizedName)) {
        return cartState.basePrices.get(normalizedName);
      }
      return cartState.basePrices.get("*") || 19.99;
    }

    function calculateViewFee(view) {
      return VIEW_FEES[view] ?? 0;
    }

    function buildViewBreakdown(basePrice) {
      const entries = [];
      VIEW_ORDER.forEach((view) => {
        const layers = priceLayers(summaryLayers(view));
        const hasLayers = layers.length > 0;
        const baseShare = view === "front" ? basePrice : 0;
        const viewFee = hasLayers ? calculateViewFee(view) : 0;
        const customTotal = layers.reduce((sum, layer) => sum + (layer.fee || 0), 0);
        const subtotal = baseShare + viewFee + customTotal;
        const include = view === "front" || hasLayers;
        if (include) {
          entries.push({
            view,
            label: VIEW_LABELS[view] || capitalize(view),
            baseShare,
            viewFee,
            customTotal,
            subtotal,
            layers
          });
        }
      });
      return entries;
    }

    function createEmptyViewEntry(view) {
      return {
        view,
        label: VIEW_LABELS[view] || capitalize(view),
        baseShare: view === "front" ? 0 : 0,
        viewFee: 0,
        customTotal: 0,
        subtotal: 0,
        layers: []
      };
    }

    function summaryLayers(view) {
      if (!window.cdpLayers || typeof window.cdpLayers.getLayers !== "function") {
        return [];
      }
      const allLayers = window.cdpLayers.getLayers();
      return allLayers
        .filter((layer) => !view || (layer.view || view) === view)
        .map((layer) => {
          const type = (layer.type || "custom").toLowerCase();
          const element = layer.element || null;
          const summary = {
            id: layer.id,
            name: layer.name || `Layer #${layer.id}`,
            type,
            view: layer.view || view,
            text: "",
            font: "",
            preview: ""
          };
          if (element) {
            if (type === "text") {
              summary.text = (element.textContent || "").trim();
              try {
                const fontValue = element.style.fontFamily || window.getComputedStyle(element).fontFamily || "";
                summary.font = fontValue.split(",")[0].replace(/\"/g, "").trim();
              } catch (err) {
                summary.font = "";
              }
            }
            if (element.tagName === "IMG") {
              summary.preview = element.src;
            }
          }
          return summary;
        });
    }

    function priceLayers(layers) {
      return layers.map((layer) => ({
        ...layer,
        fee: cartState.layerFees[layer.type] ?? cartState.layerFees.default
      }));
    }

    function updateSummary(snapshot) {
      if (elements.preview) {
        elements.preview.src = snapshot.image;
      }
      if (elements.productName) {
        elements.productName.textContent = snapshot.productName;
      }
      if (elements.color) {
        elements.color.textContent = snapshot.color;
      }
      if (elements.size) {
        elements.size.textContent = snapshot.size;
      }
      const viewLabel = getViewLabel(snapshot.view);
      if (elements.viewLabel) {
        elements.viewLabel.textContent = viewLabel;
      }
      if (elements.viewMeta) {
        elements.viewMeta.textContent = t("viewLabel").replace("{value}", viewLabel);
      }
    }

    function updateCustomizations(snapshot) {
      if (!elements.customViews) return;
      elements.customViews.innerHTML = "";
      const entries = snapshot.viewBreakdown || [];
      const totalLayers = entries.reduce((sum, entry) => sum + entry.layers.length, 0);
      if (elements.count) {
        elements.count.textContent = labelWithCount(totalLayers, "layer", "layers");
      }
      if (!totalLayers) {
        if (elements.customEmpty) {
          elements.customEmpty.removeAttribute("hidden");
        }
        return;
      }
      if (elements.customEmpty) {
        elements.customEmpty.setAttribute("hidden", "true");
      }
      entries.forEach((entry) => {
        if (!entry.layers.length) return;
        const card = document.createElement("article");
        card.className = "cdp-cart-custom-view";
        card.innerHTML = `
          <header>
            <span class="cdp-cart-custom-view-title">${getViewLabel(entry.view)}</span>
            <span class="cdp-cart-custom-view-meta">${labelWithCount(entry.layers.length, "layer", "layers")}</span>
          </header>
        `;
        const list = document.createElement("ul");
        list.className = "cdp-cart-custom-list";
        entry.layers.forEach((layer) => {
          list.appendChild(createLayerListItem(layer));
        });
        card.appendChild(list);
        elements.customViews.appendChild(card);
      });
    }

    function updateViewBreakdown(snapshot) {
      if (!elements.viewList) return;
      const entries = snapshot.viewBreakdown || [];
      if (elements.viewCount) {
        elements.viewCount.textContent = entries.length
          ? labelWithCount(entries.length, "viewWord", "viewsWord")
          : `0 ${t("viewsWord")}`;
      }
      elements.viewList.innerHTML = "";
      if (!entries.length) {
        const empty = document.createElement("p");
        empty.className = "cdp-cart-empty";
        empty.textContent = t("noViewsYet");
        elements.viewList.appendChild(empty);
        return;
      }
      entries.forEach((entry) => {
        const card = document.createElement("article");
        card.className = "cdp-cart-view-card";
        const layerText = formatLayersSummary(entry.layers);
        card.innerHTML = `
          <header>
            <div>
              <span class="cdp-cart-view-name">${getViewLabel(entry.view)}</span>
              <span class="cdp-cart-view-meta">${labelWithCount(entry.layers.length, "layer", "layers")}</span>
            </div>
            <div class="cdp-cart-view-total">${formatCurrency(entry.subtotal)}</div>
          </header>
          <div class="cdp-cart-view-details">
            <div><span>${t("base")}</span><span>${formatCurrency(entry.baseShare)}</span></div>
            <div><span>${t("surcharge")}</span><span>${formatCurrency(entry.viewFee)}</span></div>
            <div><span>${t("custom")}</span><span>${formatCurrency(entry.customTotal)}</span></div>
          </div>
          <p class="cdp-cart-view-layers"><strong>${t("layersLabel")}:</strong> ${layerText}</p>
        `;
        elements.viewList.appendChild(card);
      });
    }

    function formatLayerTitle(layer) {
      if (layer.type === "text" && layer.text) {
        return `${t("textLayer")} — \"${escapeHTML(layer.text)}\"`;
      }
      return `${capitalize(layer.type)} ${t("layerSuffix")}`;
    }

    function formatLayerMeta(layer) {
      const parts = [];
      if (layer.font) {
        parts.push(`${t("font")}: ${escapeHTML(layer.font)}`);
      }
      if (layer.view) {
        parts.push(t("viewLabel").replace("{value}", getViewLabel(layer.view)));
      }
      parts.push(`${t("layerNumber")} #${layer.id}`);
      return parts.join(" · ");
    }

    function createLayerListItem(layer) {
      const li = document.createElement("li");
      li.className = "cdp-cart-custom-item";
      const icon = LAYER_ICONS[layer.type] || LAYER_ICONS.default;
      li.innerHTML = `
        <div class="cdp-cart-custom-icon"><i class="${icon}"></i></div>
        <div class="cdp-cart-custom-body">
          <div class="cdp-cart-custom-title">${formatLayerTitle(layer)}</div>
          <div class="cdp-cart-custom-meta">${formatLayerMeta(layer)}</div>
        </div>
        <div class="cdp-cart-custom-price">${formatCurrency(layer.fee)}</div>
      `;
      return li;
    }

    function formatLayersSummary(layers) {
      if (!layers || !layers.length) {
        return t("noLayers");
      }
      if (layers.length <= 2) {
        return layers.map((layer) => formatLayerTitle(layer)).join(" · ");
      }
      const preview = layers
        .slice(0, 2)
        .map((layer) => formatLayerTitle(layer))
        .join(" · ");
      const remaining = layers.length - 2;
      return `${preview} · +${remaining} ${t("more")}`;
    }

    function formatSizeRequestsSummary(entries) {
      if (!entries || !entries.length) {
        return t("none");
      }
      return entries
        .map((entry) => {
          const fitLabel = entry.fitLabel || entry.fit || t("fitGeneric");
          const parts = [`${entry.size}×${entry.quantity}`];
          parts.push(fitLabel);
          if (entry.color) {
            parts.push(entry.color);
          }
          return parts.join(" · ");
        })
        .join(" · ");
    }

    function updateTotals(snapshot) {
      const quantity = snapshot.quantity ?? computeOrderQuantity(snapshot.sizeRequests);
      const unitBase = Number(snapshot.price) || 0;
      const unitViewFee = snapshot.invoiceViewFee || 0;
      const unitCustom = snapshot.invoiceCustomTotal || 0;
      const baseTotal = unitBase * quantity;
      const viewFee = unitViewFee * quantity;
      const customizationTotal = unitCustom * quantity;
      const unitTotal = snapshot.unitTotal ?? snapshot.total ?? unitBase + unitViewFee + unitCustom;
      const totalValue = snapshot.orderTotal ?? unitTotal * quantity;
      if (elements.price) {
        elements.price.textContent = formatCurrency(baseTotal);
      }
      if (elements.viewFee) {
        elements.viewFee.textContent = formatCurrency(viewFee);
      }
      if (elements.customFee) {
        elements.customFee.textContent = formatCurrency(customizationTotal);
      }
      if (elements.total) {
        elements.total.textContent = formatCurrency(totalValue);
      }
      if (elements.timestamp) {
        elements.timestamp.textContent = new Intl.DateTimeFormat(getIntlLocale(), {
          dateStyle: "medium",
          timeStyle: "short"
        }).format(snapshot.generatedAt);
      }
      updateScanArea(snapshot);
      cachePayload(snapshot);
    }

    function updateScanArea(snapshot) {
      const viewLabel = getViewLabel(snapshot.view);
      const quantity = snapshot.quantity ?? computeOrderQuantity(snapshot.sizeRequests);
      const perUnitTotal = snapshot.unitTotal ?? snapshot.total ?? snapshot.price + (snapshot.invoiceViewFee || 0) + (snapshot.invoiceCustomTotal || 0);
      const orderTotal = snapshot.orderTotal ?? perUnitTotal * quantity;
      if (elements.scanPrice) {
        elements.scanPrice.textContent = formatCurrency(orderTotal);
      }
      if (elements.scanProduct) {
        elements.scanProduct.textContent = snapshot.productName;
      }
      if (elements.scanDetails) {
        const viewNames = (snapshot.viewBreakdown || [])
          .map((entry) => getViewLabel(entry.view))
          .join(", ") || viewLabel;
        const surchargeTotal = (snapshot.invoiceViewFee || 0) * quantity;
        const feeText = surchargeTotal ? ` · ${t("surcharge")}: +${formatCurrency(surchargeTotal)}` : "";
        const qtyText = quantity ? ` · ${t("qtyLabel")}: ${quantity}` : "";
        elements.scanDetails.textContent = `${t("colorLabel")}: ${snapshot.color} · ${t("sizeWord")}: ${snapshot.size} · ${t("viewsWord")}: ${viewNames}${feeText}${qtyText}`;
      }
      if (elements.scanLayers) {
        const layerSummary = (snapshot.viewBreakdown || [])
          .map((entry) => `${getViewLabel(entry.view)}: ${entry.layers.length}`)
          .join(" · ");
        const sizeSummary = formatSizeRequestsSummary(snapshot.sizeRequests || []);
        const qtyCopy = quantity ? ` · ${t("qtyLabel")}: ${quantity}` : "";
        elements.scanLayers.textContent = `${t("layersLabel")}: ${layerSummary || t("none")} · ${t("sizes")}: ${sizeSummary}${qtyCopy}`;
      }
      renderInvoiceAttachmentPreview(snapshot);
    }

    function cachePayload(snapshot) {
      if (!elements.payload) return;
      const quantity = snapshot.quantity ?? computeOrderQuantity(snapshot.sizeRequests);
      const perUnitTotal = snapshot.unitTotal ?? snapshot.total ?? snapshot.price + (snapshot.invoiceViewFee || 0) + (snapshot.invoiceCustomTotal || 0);
      const totalValue = snapshot.orderTotal ?? perUnitTotal * quantity;
      const payload = {
        ...snapshot,
        quantity,
        unitTotal: perUnitTotal,
        total: totalValue,
        generatedAt: snapshot.generatedAt.toISOString()
      };
      elements.payload.value = JSON.stringify(payload, null, 2);
    }

    function buildCartTestItem(snapshot) {
      const quantity = snapshot.quantity ?? computeOrderQuantity(snapshot.sizeRequests);
      const sizeSummary = (snapshot.sizeRequests && snapshot.sizeRequests.length)
        ? snapshot.sizeRequests
            .map((entry) => `${entry.size}x${entry.quantity}`)
            .join(", ")
        : (snapshot.size || "One Size");
      const colorSummary = (snapshot.sizeRequests && snapshot.sizeRequests.length)
        ? snapshot.sizeRequests[0].color
        : (snapshot.color || DEFAULT_CART_COLOR);
      const numericPrice = Number(snapshot.orderTotal ?? snapshot.total ?? 0) || 0;
      const generatedCode = generateScanCode(snapshot);

      return {
        id: `${slugify(snapshot.productName)}-${generatedCode.toLowerCase().replace(/[^a-z0-9]+/g, "-")}`,
        title: snapshot.productName,
        name: snapshot.productName,
        image: snapshot.image,
        img: snapshot.image,
        invoiceAttachments: cloneInvoiceAttachments(snapshot.invoiceAttachments),
        price: numericPrice,
        priceNumber: numericPrice,
        color: colorSummary,
        colorName: colorSummary,
        size: sizeSummary,
        qty: quantity,
        code: generatedCode,
        view: snapshot.view,
        invoicePayload: elements.payload?.value || ""
      };
    }

    function pushSnapshotToSharedCart(snapshot) {
      const nextItem = buildCartTestItem(snapshot);
      const cartItems = safeReadSharedCart();
      cartItems.push(nextItem);
      safeWriteSharedCart(cartItems);
    }

    function generateScanCode(snapshot) {
      const stamp = snapshot.generatedAt.getTime().toString(16).toUpperCase();
      const quantity = snapshot.quantity ?? computeOrderQuantity(snapshot.sizeRequests);
      const perUnitTotal = snapshot.unitTotal ?? snapshot.total ?? snapshot.price + (snapshot.invoiceViewFee || 0) + (snapshot.invoiceCustomTotal || 0);
      const totalValue = snapshot.orderTotal ?? perUnitTotal * quantity;
      const total = Math.round(totalValue * 100)
        .toString(16)
        .toUpperCase();
      return `#${stamp.slice(-6)}-${total}`;
    }

    function initSizeRequestControls() {
      if (elements.sizeAdd) {
        elements.sizeAdd.addEventListener("click", handleSizeAdd);
      }
      if (elements.sizeList) {
        elements.sizeList.addEventListener("click", (event) => {
          const target = event.target.closest("[data-size-remove]");
          if (target) {
            removeSizeRequest(target.getAttribute("data-size-remove"));
          }
        });
      }
      if (elements.sizeColorChoices) {
        elements.sizeColorChoices.addEventListener("click", handleColorChoiceClick);
      }
      if (elements.sizeColorSync) {
        elements.sizeColorSync.addEventListener("click", () => {
          const productColor = getTextContent("#cdpColorName", DEFAULT_CART_COLOR);
          setSizeColorValue(productColor);
        });
      }
      if (elements.sizeColor) {
        elements.sizeColor.addEventListener("input", (event) => {
          highlightSelectedCartColor(event.target.value);
        });
      }
    }

    function populateColorOptions() {
      const colors = collectColorOptions();
      if (elements.colorDatalist) {
        elements.colorDatalist.innerHTML = colors
          .map((entry) => `<option value="${escapeHTML(entry.label)}"></option>`)
          .join("");
      }
      if (elements.sizeColorChoices) {
        elements.sizeColorChoices.innerHTML = "";
        colors.forEach((entry) => {
          const button = document.createElement("button");
          button.type = "button";
          button.className = "cdp-cart-color-choice";
          button.dataset.colorValue = entry.label;
          button.setAttribute("aria-pressed", "false");

          const swatch = document.createElement("span");
          swatch.className = "cdp-cart-color-choice-dot";
          if (entry.swatch) {
            swatch.style.background = entry.swatch;
          }

          const label = document.createElement("span");
          label.className = "cdp-cart-color-choice-label";
          label.textContent = entry.label;

          button.appendChild(swatch);
          button.appendChild(label);
          elements.sizeColorChoices.appendChild(button);
        });
        highlightSelectedCartColor(elements.sizeColor?.value || getTextContent("#cdpColorName", DEFAULT_CART_COLOR));
      }
    }

    function ensureCartHelpStructure() {
      const header = cartPanel.querySelector(".cdp-cart-header");
      if (!header) {
        return;
      }
      if (!header.querySelector(".cdp-cart-header-text")) {
        const firstBlock = header.querySelector("div");
        if (firstBlock && !firstBlock.classList.contains("cdp-cart-header-actions")) {
          firstBlock.classList.add("cdp-cart-header-text");
        }
      }
      let actions = header.querySelector(".cdp-cart-header-actions");
      if (!actions) {
        actions = document.createElement("div");
        actions.className = "cdp-cart-header-actions";
        header.appendChild(actions);
      }
      const closeBtn = header.querySelector(".cdp-cart-close-btn");
      if (closeBtn && closeBtn.parentElement !== actions) {
        actions.appendChild(closeBtn);
      }
      if (!header.querySelector('[data-cart-help-toggle]')) {
        const helpBtn = document.createElement("button");
        helpBtn.type = "button";
        helpBtn.className = "cdp-cart-help-btn";
        helpBtn.setAttribute("data-cart-help-toggle", "");
        helpBtn.setAttribute("aria-label", "Open tutorials");
        helpBtn.innerHTML = '<i class="fa-solid fa-circle-question" aria-hidden="true"></i>';
        if (actions.firstChild) {
          actions.insertBefore(helpBtn, actions.firstChild);
        } else {
          actions.appendChild(helpBtn);
        }
      }
    }

    function initCartHelpTrigger() {
      if (!elements.helpToggle) {
        return;
      }
      elements.helpToggle.addEventListener("click", handleCartHelpClick);
    }

    function handleCartHelpClick(event) {
      event.preventDefault();
      closeCartPanel();
      const tutorialUrl = window.cdpCartTutorialUrl || FALLBACK_CART_TUTORIAL_URL;
      if (typeof tutorialUrl === "string" && tutorialUrl.trim().length > 0) {
        window.open(tutorialUrl, "_blank", "noopener");
        return;
      }
      const detail = buildCartHelpDetail();
      document.dispatchEvent(new CustomEvent("cdp:help:open", { detail }));
    }

    function buildCartHelpDetail() {
      const detail = { source: "cart" };
      const track = panelInner?.dataset.helpTrack || "basic";
      if (track) {
        detail.track = track;
      }
      const chapterAttr = panelInner?.dataset.helpChapter;
      if (chapterAttr !== undefined) {
        const index = Number(chapterAttr);
        if (!Number.isNaN(index)) {
          detail.chapter = index > 0 ? index - 1 : index;
        }
      }
      if (panelInner?.dataset.helpTitle) {
        detail.title = panelInner.dataset.helpTitle;
      }
      return detail;
    }

    function collectColorOptions() {
      const buttons = document.querySelectorAll(".cdp-color-option[data-color]");
      const seen = new Set();
      const list = [];
      buttons.forEach((btn) => {
        const label = (btn.dataset.color || "").trim();
        if (!label) return;
        const key = label.toLowerCase();
        if (seen.has(key)) return;
        seen.add(key);
        const dot = btn.querySelector(".cdp-color-dot");
        let swatch = "";
        if (dot) {
          swatch = dot.style.background || dot.style.backgroundColor || "";
          if (!swatch && window.getComputedStyle) {
            swatch = window.getComputedStyle(dot).backgroundColor;
          }
        }
        list.push({ label, swatch });
      });
      if (!list.length && DEFAULT_CART_COLOR) {
        list.push({ label: DEFAULT_CART_COLOR, swatch: "" });
      }
      return list;
    }

    function handleSizeAdd() {
      const fitValue = elements.sizeFit?.value || "unisex";
      const sizeValue = elements.sizeSelect?.value || "M";
      const qtyValue = Math.max(1, Number(elements.sizeQty?.value || 0));
      const colorValue = (elements.sizeColor?.value || elements.color?.textContent || DEFAULT_CART_COLOR).trim();
      const entry = {
        id: `sr-${sizeEntryCounter++}`,
        fit: fitValue,
        fitLabel: FIT_LABELS[fitValue] || fitValue,
        size: sizeValue,
        color: colorValue || DEFAULT_CART_COLOR,
        quantity: qtyValue
      };
      cartState.sizeRequests.push(entry);
      if (elements.sizeQty) {
        elements.sizeQty.value = "1";
      }
      renderSizeRequests();
      renderCart();
    }

    function renderSizeRequests() {
      if (!elements.sizeList) return;
      const entries = cartState.sizeRequests;
      elements.sizeList.innerHTML = "";
      if (!entries.length) {
        if (elements.sizeEmpty) {
          elements.sizeEmpty.removeAttribute("hidden");
        }
        if (elements.sizeCount) {
          elements.sizeCount.textContent = "No lines yet";
          elements.sizeCount.textContent = t("noLinesYet");
        }
        return;
      }
      if (elements.sizeEmpty) {
        elements.sizeEmpty.setAttribute("hidden", "true");
      }
      const totalQty = entries.reduce((sum, entry) => sum + Number(entry.quantity || 0), 0);
      entries.forEach((entry) => {
        elements.sizeList.appendChild(createSizeListItem(entry));
      });
      if (elements.sizeCount) {
        elements.sizeCount.textContent = `${getLineLabel(entries.length)} · ${totalQty} ${t("pcs")}`;
      }
    }

    function computeOrderQuantity(entries) {
      if (!entries || !entries.length) {
        return 1;
      }
      const total = entries.reduce((sum, entry) => sum + Math.max(0, Number(entry.quantity || 0)), 0);
      return Math.max(1, total);
    }

    function createSizeListItem(entry) {
      const li = document.createElement("li");
      li.className = "cdp-cart-size-item";
      li.dataset.sizeId = entry.id;
      li.innerHTML = `
        <div class="cdp-cart-size-text">
          <span class="cdp-cart-size-line">${escapeHTML(entry.fitLabel || entry.fit)} · ${t("sizeWord")} ${escapeHTML(entry.size)}</span>
          <span class="cdp-cart-size-meta">${t("colorLabel")}: ${escapeHTML(entry.color)} · ${t("qtyLabel")} ${escapeHTML(String(entry.quantity))}</span>
        </div>
        <button type="button" class="cdp-cart-size-remove" data-size-remove="${entry.id}" aria-label="${t("removeSizeLine")}">
          <i class="fa-solid fa-xmark"></i>
        </button>
      `;
      return li;
    }

    function removeSizeRequest(id) {
      const index = cartState.sizeRequests.findIndex((entry) => entry.id === id);
      if (index === -1) return;
      cartState.sizeRequests.splice(index, 1);
      renderSizeRequests();
      renderCart();
    }

    function handleColorChoiceClick(event) {
      const target = event.target.closest("[data-color-value]");
      if (!target) return;
      const value = target.dataset.colorValue || DEFAULT_CART_COLOR;
      setSizeColorValue(value);
    }

    function syncSizeColorInput(snapshot) {
      if (!snapshot) {
        setSizeColorValue(DEFAULT_CART_COLOR, { silent: true });
        return;
      }
      const colorValue = snapshot.color || DEFAULT_CART_COLOR;
      setSizeColorValue(colorValue, { silent: true });
    }

    function setSizeColorValue(value, options = {}) {
      if (!elements.sizeColor) return;
      const finalValue = value || DEFAULT_CART_COLOR;
      elements.sizeColor.value = finalValue;
      highlightSelectedCartColor(finalValue);
      if (!options.silent) {
        const evt = new Event("change", { bubbles: true });
        elements.sizeColor.dispatchEvent(evt);
      }
    }

    function highlightSelectedCartColor(value) {
      if (!elements.sizeColorChoices) return;
      const normalized = (value || "").trim().toLowerCase();
      const buttons = elements.sizeColorChoices.querySelectorAll("[data-color-value]");
      buttons.forEach((btn) => {
        const match = (btn.dataset.colorValue || "").trim().toLowerCase() === normalized;
        btn.classList.toggle("is-selected", match);
        btn.setAttribute("aria-pressed", match ? "true" : "false");
      });
    }

    async function handleSend() {
      await renderCart();
      const snapshot = await buildSnapshot();
      cartState.lastSnapshot = snapshot;
      persistInvoiceAttachments(snapshot);
      updateScanArea(snapshot);
      cachePayload(snapshot);
      pushSnapshotToSharedCart(snapshot);
      console.info("cdpCart invoice payload", elements.payload?.value || "");
      window.location.href = `${CART_TEST_URL}?source=custom-invoice`;
    }

    function cloneInvoiceAttachments(list) {
      return Array.isArray(list)
        ? list
            .filter((item) => item && typeof item.dataUrl === "string" && item.dataUrl)
            .map((item, index) => ({
              id: item.id || `invoice-${index + 1}`,
              slot: item.slot || `file-${index + 1}`,
              name: item.name || `Invoice file ${index + 1}`,
              type: item.type || "image/png",
              dataUrl: item.dataUrl,
              size: Number(item.size) || 0
            }))
        : [];
    }

    function getCurrentProjectPath() {
      const projectName = (localStorage.getItem(CURRENT_PROJECT_KEY) || "").trim();
      const currentFolder = (localStorage.getItem(CURRENT_FOLDER_KEY) || "").trim();
      if (!projectName || projectName.toLowerCase() === "untitled") {
        return "";
      }
      return currentFolder ? `${currentFolder}/${projectName}` : projectName;
    }

    function readStoredInvoiceRecord() {
      try {
        const rawValue = localStorage.getItem(LAST_CUSTOM_INVOICE_KEY);
        if (!rawValue) return null;
        const parsed = JSON.parse(rawValue);
        if (!parsed || typeof parsed !== "object") return null;
        return parsed;
      } catch (_error) {
        return null;
      }
    }

    async function resolveInvoiceAttachments(snapshotMeta) {
      const runtimeAttachments = await buildRuntimeInvoiceAttachments(snapshotMeta);
      if (runtimeAttachments.length) {
        return runtimeAttachments;
      }

      if (cartState.invoiceAttachments.length) {
        return cloneInvoiceAttachments(cartState.invoiceAttachments);
      }

      const storedRecord = readStoredInvoiceRecord();
      if (!storedRecord) return [];

      const storedAttachments = cloneInvoiceAttachments(storedRecord.attachments);
      if (!storedAttachments.length) return [];

      const currentProjectPath = getCurrentProjectPath();
      if (currentProjectPath && storedRecord.projectPath === currentProjectPath) {
        return storedAttachments;
      }

      if (!currentProjectPath && storedRecord.productName === snapshotMeta.productName) {
        return storedAttachments;
      }

      return [];
    }

    function getRuntimeUploadAssets() {
      if (window.cdpUploadAssets && typeof window.cdpUploadAssets.getInvoiceAssets === "function") {
        return window.cdpUploadAssets.getInvoiceAssets();
      }
      return [];
    }

    function getDataUrlMimeType(dataUrl) {
      const match = /^data:([^;]+);/i.exec(String(dataUrl || ""));
      return match ? match[1] : "image/png";
    }

    function captureScanBoxPreview(view = "") {
      return renderPrintBoxToDataUrl(getPreviewBox(view), {
        backgroundColor: "#ffffff",
        includeFrame: true,
        frameColor: "#111827",
        frameWidth: 2,
        frameDash: [8, 6]
      });
    }

    async function buildRuntimeInvoiceAttachments(snapshotMeta) {
      const uploadAssets = getRuntimeUploadAssets();
      const attachments = [];

      const scanPreview = await captureScanBoxPreview(snapshotMeta.view || "front");
      if (scanPreview) {
        attachments.push({
          id: `scan-${snapshotMeta.view || "front"}`,
          slot: `scan-box-${snapshotMeta.view || "front"}`,
          name: `${snapshotMeta.productName} ${snapshotMeta.view || "front"} scan box`,
          type: "image/png",
          dataUrl: scanPreview,
          size: 0
        });
      }

      if (!uploadAssets.length) {
        return attachments;
      }

      const primaryUpload = uploadAssets[0];
      const originalSrc = primaryUpload.originalSrc || primaryUpload.optimizedSrc || "";

      if (originalSrc) {
        attachments.push({
          id: `${primaryUpload.id || "upload"}-source`,
          slot: "upload-source",
          name: primaryUpload.name || `${snapshotMeta.productName} upload`,
          type: primaryUpload.type || getDataUrlMimeType(originalSrc),
          dataUrl: originalSrc,
          size: 0
        });
      }

      return attachments;
    }

    function persistInvoiceAttachments(snapshot) {
      const attachments = cloneInvoiceAttachments(snapshot.invoiceAttachments);
      cartState.invoiceAttachments = attachments;
      const quantity = snapshot.quantity ?? computeOrderQuantity(snapshot.sizeRequests);
      const unitTotal = snapshot.unitTotal ?? snapshot.total ?? snapshot.price + (snapshot.invoiceViewFee || 0) + (snapshot.invoiceCustomTotal || 0);
      const orderTotal = snapshot.orderTotal ?? unitTotal * quantity;

      const record = {
        projectPath: getCurrentProjectPath(),
        projectName: (localStorage.getItem(CURRENT_PROJECT_KEY) || snapshot.productName || "Untitled").trim(),
        productName: snapshot.productName,
        updatedAt: new Date().toISOString(),
        previewImage: snapshot.image || "",
        quantity,
        unitTotal,
        orderTotal,
        total: orderTotal,
        product: {
          color: snapshot.color || DEFAULT_CART_COLOR,
          size: snapshot.size || "M",
          view: snapshot.view || "front"
        },
        attachments
      };

      try {
        localStorage.setItem(LAST_CUSTOM_INVOICE_KEY, JSON.stringify(record));
      } catch (_error) {
        // Ignore localStorage write failures.
      }

      if (!record.projectPath) {
        return;
      }

      try {
        const projectKey = PROJECT_STORAGE_PREFIX + record.projectPath;
        const rawProject = localStorage.getItem(projectKey);
        if (!rawProject) return;
        const parsedProject = JSON.parse(rawProject);
        parsedProject.invoiceAttachments = attachments;
        parsedProject.invoiceScanImage = (attachments.find((item) => item.slot === "scan-box") || attachments[0] || {}).dataUrl || "";
        parsedProject.previewImage = parsedProject.previewImage || snapshot.image || "";
        parsedProject.thumbnail = parsedProject.thumbnail || snapshot.image || "";
        localStorage.setItem(projectKey, JSON.stringify(parsedProject));
      } catch (_error) {
        // Ignore localStorage write failures.
      }
    }

    function ensureInvoiceAttachmentHost() {
      if (!elements.scanLayers || !elements.scanLayers.parentElement) {
        return null;
      }
      let host = elements.scanLayers.parentElement.querySelector(".cdp-cart-attachment-summary");
      if (!host) {
        host = document.createElement("div");
        host.className = "cdp-cart-attachment-summary";
        elements.scanLayers.parentElement.appendChild(host);
      }
      return host;
    }

    function renderInvoiceAttachmentPreview(snapshot) {
      const attachments = cloneInvoiceAttachments(snapshot.invoiceAttachments);
      const attachmentHost = ensureInvoiceAttachmentHost();

      const scanAttachment = attachments.find((item) => item.slot === "scan-box") || attachments[0] || null;

      if (elements.scanCode) {
        if (scanAttachment && scanAttachment.dataUrl) {
          elements.scanCode.classList.add("has-preview");
          elements.scanCode.innerHTML = ''
            + `<img class="cdp-cart-scan-preview" src="${escapeHTML(scanAttachment.dataUrl)}" alt="Invoice scan preview">`
            + `<span class="cdp-cart-scan-badge">${attachments.length} file${attachments.length === 1 ? "" : "s"}</span>`;
        } else {
          elements.scanCode.classList.remove("has-preview");
          elements.scanCode.textContent = generateScanCode(snapshot);
        }
      }

      if (!attachmentHost) {
        return;
      }

      if (!attachments.length) {
        attachmentHost.innerHTML = "";
        attachmentHost.hidden = true;
        return;
      }

      attachmentHost.hidden = false;
      attachmentHost.innerHTML = attachments.map((attachment, index) => {
        const label = attachment.name || `Invoice file ${index + 1}`;
        return ''
          + `<div class="cdp-cart-attachment-chip">`
          + `<img src="${escapeHTML(attachment.dataUrl)}" alt="${escapeHTML(label)}">`
          + `<span>${escapeHTML(label)}</span>`
          + `</div>`;
      }).join("");
    }

    function readFileAsDataUrl(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ""));
        reader.onerror = () => reject(new Error("Unable to read file"));
        reader.readAsDataURL(file);
      });
    }

    function updateInvoiceUploadSlot(slotName, attachment) {
      const nextAttachments = cloneInvoiceAttachments(invoiceModalState.attachments)
        .filter((item) => item.slot !== slotName);
      nextAttachments.push(attachment);
      invoiceModalState.attachments = nextAttachments.sort((left, right) => String(left.slot).localeCompare(String(right.slot)));
      syncInvoiceModalPreviews();
    }

    function ensureInvoiceUploadModal() {
      if (invoiceModal) {
        return invoiceModal;
      }

      invoiceModal = document.createElement("div");
      invoiceModal.hidden = true;
      invoiceModal.innerHTML = `
        <div class="cdp-invoice-modal-backdrop"></div>
        <div class="cdp-invoice-modal-panel">
          <div class="cdp-invoice-modal-head">
            <div>
              <p class="cdp-invoice-modal-eyebrow">Invoice upload</p>
              <h3>Upload 2 invoice images or scans</h3>
            </div>
            <button type="button" class="cdp-invoice-modal-close" aria-label="Close invoice upload">&times;</button>
          </div>
          <p class="cdp-invoice-modal-copy">Attach both files before continuing. The first upload is mirrored inside the scan box and both files are saved to your invoice data.</p>
          <div class="cdp-invoice-modal-grid">
            <label class="cdp-invoice-upload-slot" data-slot="file-1">
              <input type="file" accept="image/*" data-invoice-input="file-1" hidden>
              <span class="cdp-invoice-upload-title">Invoice file 1</span>
              <span class="cdp-invoice-upload-preview">Click to upload image or scan</span>
            </label>
            <label class="cdp-invoice-upload-slot" data-slot="file-2">
              <input type="file" accept="image/*" data-invoice-input="file-2" hidden>
              <span class="cdp-invoice-upload-title">Invoice file 2</span>
              <span class="cdp-invoice-upload-preview">Click to upload image or scan</span>
            </label>
          </div>
          <div class="cdp-invoice-modal-actions">
            <button type="button" data-invoice-cancel>Cancel</button>
            <button type="button" data-invoice-confirm>Save and continue</button>
          </div>
        </div>
      `;

      Object.assign(invoiceModal.style, {
        position: "fixed",
        inset: "0",
        zIndex: "12000",
        display: "none",
        alignItems: "center",
        justifyContent: "center"
      });

      const backdrop = invoiceModal.querySelector(".cdp-invoice-modal-backdrop");
      Object.assign(backdrop.style, {
        position: "absolute",
        inset: "0",
        background: "rgba(15, 23, 42, 0.52)",
        backdropFilter: "blur(4px)"
      });

      const panel = invoiceModal.querySelector(".cdp-invoice-modal-panel");
      Object.assign(panel.style, {
        position: "relative",
        width: "min(640px, 92vw)",
        background: "#ffffff",
        borderRadius: "20px",
        padding: "24px",
        boxShadow: "0 28px 60px rgba(15, 23, 42, 0.28)",
        display: "flex",
        flexDirection: "column",
        gap: "16px"
      });

      const head = invoiceModal.querySelector(".cdp-invoice-modal-head");
      Object.assign(head.style, {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "space-between",
        gap: "12px"
      });

      const eyebrow = invoiceModal.querySelector(".cdp-invoice-modal-eyebrow");
      Object.assign(eyebrow.style, {
        margin: "0 0 6px",
        fontSize: "12px",
        textTransform: "uppercase",
        letterSpacing: "0.08em",
        color: "#d97706",
        fontWeight: "700"
      });

      const title = invoiceModal.querySelector("h3");
      Object.assign(title.style, {
        margin: "0",
        fontSize: "22px",
        color: "#0f172a"
      });

      const copy = invoiceModal.querySelector(".cdp-invoice-modal-copy");
      Object.assign(copy.style, {
        margin: "0",
        color: "#475569",
        lineHeight: "1.6"
      });

      const closeBtn = invoiceModal.querySelector(".cdp-invoice-modal-close");
      Object.assign(closeBtn.style, {
        border: "none",
        background: "transparent",
        color: "#64748b",
        fontSize: "28px",
        cursor: "pointer",
        lineHeight: "1"
      });

      const grid = invoiceModal.querySelector(".cdp-invoice-modal-grid");
      Object.assign(grid.style, {
        display: "grid",
        gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))",
        gap: "14px"
      });

      invoiceModal.querySelectorAll(".cdp-invoice-upload-slot").forEach((slot) => {
        Object.assign(slot.style, {
          minHeight: "220px",
          border: "2px dashed #111827",
          borderRadius: "18px",
          background: "#f8fafc",
          cursor: "pointer",
          display: "flex",
          flexDirection: "column",
          justifyContent: "space-between",
          padding: "16px",
          gap: "12px"
        });
      });

      invoiceModal.querySelectorAll(".cdp-invoice-upload-title").forEach((label) => {
        Object.assign(label.style, {
          fontSize: "14px",
          fontWeight: "700",
          color: "#0f172a"
        });
      });

      invoiceModal.querySelectorAll(".cdp-invoice-upload-preview").forEach((preview) => {
        Object.assign(preview.style, {
          flex: "1",
          borderRadius: "14px",
          background: "#ffffff",
          color: "#475569",
          display: "grid",
          placeItems: "center",
          textAlign: "center",
          padding: "12px",
          overflow: "hidden"
        });
      });

      const actions = invoiceModal.querySelector(".cdp-invoice-modal-actions");
      Object.assign(actions.style, {
        display: "flex",
        justifyContent: "flex-end",
        gap: "12px"
      });

      const cancelBtn = invoiceModal.querySelector("[data-invoice-cancel]");
      Object.assign(cancelBtn.style, {
        border: "1px solid #cbd5e1",
        background: "#ffffff",
        color: "#0f172a",
        padding: "10px 18px",
        borderRadius: "999px",
        cursor: "pointer",
        fontWeight: "600"
      });

      const confirmBtn = invoiceModal.querySelector("[data-invoice-confirm]");
      Object.assign(confirmBtn.style, {
        border: "none",
        background: "#d9a300",
        color: "#ffffff",
        padding: "10px 18px",
        borderRadius: "999px",
        cursor: "pointer",
        fontWeight: "700"
      });

      invoiceModal.querySelectorAll("[data-invoice-input]").forEach((input) => {
        input.addEventListener("change", async (event) => {
          const target = event.currentTarget;
          const file = target.files && target.files[0];
          if (!file) return;
          try {
            const dataUrl = await readFileAsDataUrl(file);
            updateInvoiceUploadSlot(target.getAttribute("data-invoice-input"), {
              id: `${Date.now()}-${target.getAttribute("data-invoice-input")}`,
              slot: target.getAttribute("data-invoice-input"),
              name: file.name,
              type: file.type || "image/png",
              size: file.size || 0,
              dataUrl
            });
          } catch (_error) {
            showToast("Unable to read the selected image.");
          }
        });
      });

      function closeInvoiceModal(result) {
        invoiceModal.hidden = true;
        invoiceModal.style.display = "none";
        document.body.style.overflow = "";
        const resolver = invoiceModalResolver;
        invoiceModalResolver = null;
        if (resolver) {
          resolver(result);
        }
      }

      closeBtn.addEventListener("click", () => closeInvoiceModal(null));
      cancelBtn.addEventListener("click", () => closeInvoiceModal(null));
      backdrop.addEventListener("click", () => closeInvoiceModal(null));
      confirmBtn.addEventListener("click", () => {
        if (cloneInvoiceAttachments(invoiceModalState.attachments).length < 2) {
          showToast("Upload both invoice files before continuing.");
          return;
        }
        closeInvoiceModal(cloneInvoiceAttachments(invoiceModalState.attachments));
      });

      document.body.appendChild(invoiceModal);
      return invoiceModal;
    }

    function syncInvoiceModalPreviews() {
      if (!invoiceModal) return;
      const attachments = cloneInvoiceAttachments(invoiceModalState.attachments);
      invoiceModal.querySelectorAll(".cdp-invoice-upload-slot").forEach((slot) => {
        const slotName = slot.getAttribute("data-slot");
        const preview = slot.querySelector(".cdp-invoice-upload-preview");
        const attachment = attachments.find((item) => item.slot === slotName);
        if (!preview) return;
        if (!attachment) {
          preview.innerHTML = "Click to upload image or scan";
          return;
        }
        preview.innerHTML = ''
          + `<img src="${escapeHTML(attachment.dataUrl)}" alt="${escapeHTML(attachment.name)}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">`
          + `<span style="display:block;margin-top:8px;font-size:12px;font-weight:600;color:#0f172a;">${escapeHTML(attachment.name)}</span>`;
      });
    }

    async function openInvoiceUploadModal(snapshot) {
      ensureInvoiceUploadModal();
      invoiceModalState = {
        snapshot,
        attachments: cloneInvoiceAttachments(snapshot.invoiceAttachments)
      };
      syncInvoiceModalPreviews();
      invoiceModal.hidden = false;
      invoiceModal.style.display = "flex";
      document.body.style.overflow = "hidden";
      return new Promise((resolve) => {
        invoiceModalResolver = resolve;
      });
    }

    function showToast(message) {
      if (!message) return;
      if (!toastEl) {
        toastEl = document.createElement("div");
        toastEl.className = "cdp-cart-toast";
        document.body.appendChild(toastEl);
      }
      toastEl.textContent = message;
      toastEl.setAttribute("data-visible", "true");
      if (toastTimer) {
        clearTimeout(toastTimer);
      }
      toastTimer = setTimeout(() => {
        toastEl.setAttribute("data-visible", "false");
      }, 2200);
    }

    function formatCurrency(value) {
      return new Intl.NumberFormat(getIntlLocale(), {
        style: "currency",
        currency: "EUR"
      }).format(Number(value) || 0);
    }

    function capitalize(value) {
      if (!value) return "";
      return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function normalizeName(value) {
      return (value || "").trim().toLowerCase();
    }

    function escapeHTML(value) {
      if (!value) return "";
      return value
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
    }

    cartBtn.addEventListener("click", openCartPanel);
    closeTriggers.forEach((btn) => btn.addEventListener("click", closeCartPanel));
    cartPanel.addEventListener("click", (event) => {
      if (event.target === cartPanel) {
        closeCartPanel();
      }
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && cartPanel.getAttribute("data-visible") === "true") {
        closeCartPanel();
      }
    });
    if (sendBtn) {
      sendBtn.addEventListener("click", handleSend);
    }

    window.addEventListener("cdp-locale-changed", () => {
      FIT_LABELS.unisex = t("fitUnisex");
      FIT_LABELS.women = t("fitWomen");
      applyCartTranslations();
      renderCart();
    });

    window.cdpCart = {
      refresh: () => renderCart(),
      setBasePrice: (name, price) => {
        if (!name) return;
        cartState.basePrices.set(normalizeName(name), Number(price));
      },
      setLayerFee: (type, price) => {
        if (!type) return;
        cartState.layerFees[type.toLowerCase()] = Number(price);
      },
      capturePreview: () => captureComposedPreview(),
      captureScan: (view) => captureScanBoxPreview(view),
      captureAllScans: async () => {
        const scans = {};
        for (const view of VIEW_ORDER) {
          const dataUrl = await captureScanBoxPreview(view);
          if (dataUrl) {
            scans[view] = dataUrl;
          }
        }
        return scans;
      },
      getSnapshot: () => cartState.lastSnapshot,
      getInvoiceAttachments: () => cloneInvoiceAttachments(cartState.lastSnapshot?.invoiceAttachments || resolveStoredInvoiceAttachments()),
      send: () => handleSend()
    };

    function resolveStoredInvoiceAttachments() {
      const storedRecord = readStoredInvoiceRecord();
      return storedRecord ? storedRecord.attachments : [];
    }

    document.addEventListener("cdp:cart:refresh", renderCart);
  });
})();
