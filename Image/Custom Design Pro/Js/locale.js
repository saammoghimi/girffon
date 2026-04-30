/* ===================================
   LOCALE + I18N (Same page translation)
   - Keeps the 11-country selector panel
   - DOES NOT navigate to other HTML files
   - On selection: saves locale, updates UI text on THIS page
   =================================== */

(function() {
  'use strict';

  // 11 locales (keep your flags)
  const locales = [
    { name: 'Italy',          code: 'IT', flag: 'country-flags-main/country-flags-main/svg/it.svg' },
    { name: 'Germany',        code: 'DE', flag: 'country-flags-main/country-flags-main/svg/de.svg' },
    { name: 'France',         code: 'FR', flag: 'country-flags-main/country-flags-main/svg/fr.svg' },
    { name: 'Spain',          code: 'ES', flag: 'country-flags-main/country-flags-main/svg/es.svg' },
    { name: 'Netherlands',    code: 'NL', flag: 'country-flags-main/country-flags-main/svg/nl.svg' },
    { name: 'Poland',         code: 'PL', flag: 'country-flags-main/country-flags-main/svg/pl.svg' },
    { name: 'Sweden',         code: 'SE', flag: 'country-flags-main/country-flags-main/svg/se.svg' },
    { name: 'United Kingdom', code: 'GB', flag: 'country-flags-main/country-flags-main/svg/gb.svg' },
    { name: 'United States',  code: 'US', flag: 'country-flags-main/country-flags-main/svg/us.svg' },
    { name: 'Switzerland',    code: 'CH', flag: 'country-flags-main/country-flags-main/svg/ch.svg' },
    { name: 'Canada',         code: 'CA', flag: 'country-flags-main/country-flags-main/svg/ca.svg' }
  ];

  // Country code -> language key (mirrors file.js)
  const COUNTRY_TO_LANG = {
    IT: 'it', it: 'it',
    DE: 'de', de: 'de',
    FR: 'fr', fr: 'fr',
    ES: 'es', es: 'es',
    NL: 'nl', nl: 'nl',
    PL: 'pl', pl: 'pl',
    SE: 'sv', se: 'sv',
    GB: 'gb', gb: 'gb',
    UK: 'gb', uk: 'gb',
    US: 'us', us: 'us',
    CH: 'ch', ch: 'ch',
    CA: 'ca', ca: 'ca'
  };


  function mapLocaleToLang(code) {
    const normalized = (code || 'US').toString();
    return COUNTRY_TO_LANG[normalized] || COUNTRY_TO_LANG[normalized.toUpperCase()] || COUNTRY_TO_LANG[normalized.toLowerCase()] || 'us';
  }

  function setFileLanguageForLocale(code) {
    const langKey = mapLocaleToLang(code);
    localStorage.setItem('cdpLang', langKey);
    if (window.cdpSetLang) {
      window.cdpSetLang(langKey);
    } else {
      window.dispatchEvent(new CustomEvent('cdp-locale-changed', { detail: { lang: langKey } }));
    }
    return langKey;
  }

  function getCurrentLocale() {
    const saved = localStorage.getItem('cdp-locale');
    return (saved && locales.some(l => l.code === saved)) ? saved : 'US';
  }

  let currentLocale = getCurrentLocale();

  // DOM Elements
  const localeBtn   = document.querySelector('[data-tool="locale"]');
  const localePanel = document.getElementById('cdpLocalePanel');
  const localeGrid  = document.getElementById('cdpLocaleGrid');
  const closeBtn    = localePanel?.querySelector('.cdp-icon-panel-close');
  const cancelBtn   = localePanel?.querySelector('.cdp-icon-btn--cancel');

  function currentLang() {
    return currentLangKey();
  }

  function tr(key) {
    if (typeof window.cdpTr === 'function') {
      try {
        return window.cdpTr(key);
      } catch (err) {
        console.warn('cdpTr failed', err);
      }
    }
    return key;
  }

  function applyTranslationsNow() {
    document.documentElement.setAttribute('lang', currentLangKey());
  }

  function currentLangKey() {
    return mapLocaleToLang(currentLocale);
  }

  // Render locale grid
  function renderLocaleGrid() {
    if (!localeGrid) return;
    localeGrid.innerHTML = '';

    locales.forEach(locale => {
      const item = document.createElement('div');
      item.className = 'cdp-locale-item';
      if (locale.code === currentLocale) item.classList.add('active');

      item.innerHTML = `
        <img src="${locale.flag}" alt="${locale.name}" class="cdp-locale-flag">
        <div class="cdp-locale-info">
          <div class="cdp-locale-name">${locale.name}</div>
          <div class="cdp-locale-code">${locale.code}</div>
        </div>
      `;

      item.addEventListener('click', () => selectLocale(locale.code));
      item.setAttribute('title', `Switch to ${locale.name}`);

      localeGrid.appendChild(item);
    });
  }

  function showPanel() {
    if (!localePanel) return;
    localePanel.setAttribute('data-visible', 'true');
    localePanel.classList.add('is-open');
  }

  function closePanel() {
    if (!localePanel) return;
    localePanel.setAttribute('data-visible', 'false');
    localePanel.classList.remove('is-open');
  }

  // Select locale - SAME PAGE translate
function selectLocale(code) {
  if (!locales.some(l => l.code === code)) return;

  currentLocale = code;

  // 1) save locale
  localStorage.setItem('cdp-locale', code);

  // 2) sync language key used by file.js
  setFileLanguageForLocale(code);

  // 3) apply translations now
  applyTranslationsNow();

  // 4) notify file.js to re-translate file menu/modals
  window.dispatchEvent(new CustomEvent('cdp-locale-changed', {
    detail: { locale: code, lang: localStorage.getItem('cdpLang') }
  }));

  renderLocaleGrid();
  closePanel();
}

  // Attach
  function attachEventListeners() {
    if (localeBtn) localeBtn.addEventListener('click', showPanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (cancelBtn) cancelBtn.addEventListener('click', closePanel);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && localePanel && localePanel.classList.contains('is-open')) {
        closePanel();
      }
    });

    // Re-apply when file menu/modals are created later
    document.addEventListener('click', () => setTimeout(applyTranslationsNow, 0), true);
  }

  function init() {
    if (!localeBtn || !localePanel || !localeGrid) {
      console.error('Locale elements not found');
      return;
    }
    setFileLanguageForLocale(currentLocale);
    renderLocaleGrid();
    attachEventListeners();
    applyTranslationsNow();
    console.log('✅ Locale + I18N initialized (same page)');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Export API
  window.cdpLocale = {
    getCurrentLocale: () => currentLocale,
    setLocale: (code) => selectLocale(code),
    getLocales: () => locales,
    // useful for other scripts
    getCurrentLang: () => currentLang(),
    translate: (key) => tr(key),
    applyNow: () => applyTranslationsNow()
  };

})();


/* ===================================
   THEME SYNC for Locale Panel (Dark/Light)
   - Dark: panel background dark gray, text white, cards dark, codes muted
   - Light: default styles (your existing CSS)
   =================================== */

(function () {
  'use strict';

  function cdpIsDarkMode() {
    const root = document.documentElement;
    const body = document.body;
    const attr = (root.getAttribute("data-theme") || body.getAttribute("data-theme") || "").toLowerCase();
    if (attr === "dark") return true;
    const cls = (root.className + " " + body.className).toLowerCase();
    return cls.includes("dark") || cls.includes("theme-dark") || cls.includes("dark-mode") || cls.includes("cdp-dark");
  }

  function applyLocalePanelTheme() {
    const panel = document.getElementById("cdpLocalePanel");
    if (!panel) return;

    const dark = cdpIsDarkMode();

    // Panel background & text
    panel.style.background = dark ? "#0f1115" : "";
    panel.style.color = dark ? "#ffffff" : "";

    // Some projects wrap content inside an inner panel
    const inner = panel.querySelector(".cdp-icon-panel") || panel;
    if (inner && inner !== panel) {
      inner.style.background = dark ? "#0f1115" : "";
      inner.style.color = dark ? "#ffffff" : "";
    }

    // Title + close button
    const title = panel.querySelector(".cdp-icon-panel-header h2, .cdp-icon-panel-header h3, h2, h3");
    if (title) title.style.color = dark ? "#ffffff" : "";

    const closeX = panel.querySelector(".cdp-icon-panel-close, .cdp-locale-close, .cdp-open-close, .cdp-saveas-close");
    if (closeX) {
      closeX.style.color = dark ? "#e5e7eb" : "";
      closeX.style.background = "transparent";
      closeX.style.border = "none";
    }

    // Grid cards
    const cards = panel.querySelectorAll(".cdp-locale-item");
    cards.forEach((card) => {
      card.style.background = dark ? "#151a22" : "";
      card.style.border = dark ? "1px solid #2b3340" : "";
      card.style.boxShadow = dark ? "0 10px 20px rgba(0,0,0,0.35)" : "";

      const name = card.querySelector(".cdp-locale-name");
      const code = card.querySelector(".cdp-locale-code");
      if (name) name.style.color = dark ? "#ffffff" : "";
      if (code) code.style.color = dark ? "#cbd5e1" : "";

      if (card.classList.contains("active")) {
        card.style.border = dark ? "2px solid #FFD600" : "";
      }
    });

    // Bottom Close button
    const bottomClose = panel.querySelector(".cdp-icon-btn--cancel, .cdp-locale-close-btn, button");
    if (bottomClose) {
      // only style if it looks like the panel close button area
      const txt = (bottomClose.textContent || "").trim().toLowerCase();
      if (txt === "close" || txt === "cancel" || txt === "بستن") {
        bottomClose.style.background = dark ? "#151a22" : "";
        bottomClose.style.color = dark ? "#ffffff" : "";
        bottomClose.style.border = dark ? "1px solid #2b3340" : "";
      }
    }
  }

  // Apply on load
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", applyLocalePanelTheme);
  } else {
    applyLocalePanelTheme();
  }

  // Observe theme toggles (class or data-theme)
  const obs = new MutationObserver(() => applyLocalePanelTheme());
  obs.observe(document.documentElement, { attributes: true, attributeFilter: ["class", "data-theme"] });
  obs.observe(document.body, { attributes: true, attributeFilter: ["class", "data-theme"] });

  // When panel opens / any click triggers re-render
  document.addEventListener("click", () => setTimeout(applyLocalePanelTheme, 0), true);

  // If your app dispatches an event on theme change
  window.addEventListener("cdp-theme-changed", applyLocalePanelTheme);

  // Also re-apply after changing locale (grid re-render)
  window.addEventListener("cdp-locale-changed", () => setTimeout(applyLocalePanelTheme, 0));

  // Expose for debugging
  window.cdpApplyLocaleTheme = applyLocalePanelTheme;
})();
