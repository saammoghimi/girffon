(function () {
  'use strict';

  const endpoint = 'backend/homepage/homepage-config.php';
  const validSurfaces = ['top_bar', 'above_hero', 'below_hero'];
  const itemCollections = ['announcementBars', 'campaigns', 'technicalAlerts', 'appAnnouncements'];

  function text(value) {
    return String(value == null ? '' : value).trim();
  }

  function createElement(tagName, className, content) {
    const node = document.createElement(tagName);
    if (className) node.className = className;
    if (content) node.textContent = content;
    return node;
  }

  function safeLink(value) {
    const raw = text(value);
    if (!raw) return '';

    try {
      const resolved = new URL(raw, document.baseURI);
      return resolved.protocol === 'http:' || resolved.protocol === 'https:' ? resolved.href : '';
    } catch (_error) {
      return '';
    }
  }

  function itemKind(item) {
    const type = text(item.item_type);
    if (type === 'homepage_campaign') return 'Campaign';
    if (type === 'technical_alert') return 'Technical alert';
    if (type === 'app_announcement') return 'App announcement';
    return 'Announcement';
  }

  function createMeta(item) {
    const values = [];
    const eventKey = text(item.event_key);
    const percent = Number(item.display_percent);
    const coupon = text(item.coupon_code);

    if (eventKey && eventKey !== 'none') values.push(eventKey.replace(/_/g, ' '));
    if (Number.isFinite(percent) && percent > 0) values.push(percent + '% OFF');
    if (coupon) values.push('Code: ' + coupon);
    if (!values.length) return null;

    const meta = createElement('div', 'gf-managed-item__meta');
    values.forEach(function (value) {
      meta.appendChild(createElement('span', 'gf-managed-item__tag', value));
    });
    return meta;
  }

  function createManagedItem(item) {
    const requestedSeverity = text(item.severity);
    const severity = ['info', 'warning', 'critical'].includes(requestedSeverity) ? requestedSeverity : 'info';
    const type = text(item.item_type);
    const article = createElement('article', 'gf-managed-item gf-managed-item--' + type + ' is-' + severity);
    article.dataset.managedHomepageItem = String(item.id || '');
    article.dataset.itemType = type;
    article.dataset.targetSurface = text(item.target_surface);

    if (type === 'technical_alert' && severity === 'critical') {
      article.setAttribute('role', 'alert');
      article.setAttribute('aria-live', 'assertive');
    } else {
      article.setAttribute('role', 'status');
      article.setAttribute('aria-live', 'polite');
    }

    const copy = createElement('div', 'gf-managed-item__copy');
    copy.appendChild(createElement('span', 'gf-managed-item__kind', itemKind(item)));

    const title = text(item.title);
    if (title) copy.appendChild(createElement('h2', 'gf-managed-item__title', title));
    copy.appendChild(createElement('p', 'gf-managed-item__message', text(item.message)));

    const meta = createMeta(item);
    if (meta) copy.appendChild(meta);
    article.appendChild(copy);

    const href = safeLink(item.cta_url);
    const label = text(item.cta_label);
    if (href && label) {
      const link = createElement('a', 'gf-managed-item__cta', label);
      link.href = href;
      if (new URL(href).origin !== window.location.origin) link.rel = 'noopener noreferrer';
      article.appendChild(link);
    }

    return article;
  }

  function getMount(surface) {
    const existing = document.querySelector('[data-managed-homepage-surface="' + surface + '"]');
    if (existing) return existing;

    const mount = createElement('section', 'gf-managed-surface gf-managed-surface--' + surface);
    mount.dataset.managedHomepageSurface = surface;
    mount.setAttribute('aria-label', 'GirffoN homepage updates');

    const topBar = document.querySelector('.top-bar');
    const hero = document.querySelector('.hero-slider');
    if (surface === 'top_bar' && topBar) {
      topBar.before(mount);
    } else if (surface === 'above_hero' && hero) {
      hero.before(mount);
    } else if (surface === 'below_hero' && hero) {
      hero.after(mount);
    } else {
      document.body.prepend(mount);
    }
    return mount;
  }

  function clearManagedContent() {
    document.querySelectorAll('[data-managed-homepage-surface]').forEach(function (mount) {
      mount.remove();
    });
  }

  function renderItems(payload) {
    clearManagedContent();
    const items = [];

    itemCollections.forEach(function (collection) {
      const collectionItems = Array.isArray(payload[collection]) ? payload[collection] : [];
      collectionItems.forEach(function (item) {
        if (item && validSurfaces.includes(text(item.target_surface))) items.push(item);
      });
    });

    items.sort(function (left, right) {
      return Number(right.priority || 0) - Number(left.priority || 0);
    });

    validSurfaces.forEach(function (surface) {
      const surfaceItems = items.filter(function (item) {
        return text(item.target_surface) === surface;
      });
      if (!surfaceItems.length) return;

      const mount = getMount(surface);
      const inner = createElement('div', 'gf-managed-surface__inner');
      surfaceItems.forEach(function (item) {
        inner.appendChild(createManagedItem(item));
      });
      mount.appendChild(inner);
    });
  }

  function removeMaintenance() {
    document.body.classList.remove('gf-public-maintenance-active');
    const existing = document.getElementById('gfPublicMaintenance');
    if (existing) existing.remove();
  }

  function renderMaintenance(maintenance) {
    clearManagedContent();
    removeMaintenance();

    const main = createElement('main', 'gf-public-maintenance');
    main.id = 'gfPublicMaintenance';
    main.setAttribute('role', 'main');

    const panel = createElement('section', 'gf-public-maintenance__panel');
    panel.setAttribute('aria-labelledby', 'gfPublicMaintenanceTitle');
    panel.appendChild(createElement('span', 'gf-public-maintenance__brand', 'GIRFFON'));

    const title = createElement('h1', 'gf-public-maintenance__title', text(maintenance.title) || "We'll be back soon.");
    title.id = 'gfPublicMaintenanceTitle';
    panel.appendChild(title);
    panel.appendChild(createElement('p', 'gf-public-maintenance__message', text(maintenance.message) || 'The GIRFFON storefront is temporarily unavailable.'));

    const eta = text(maintenance.eta);
    if (eta) panel.appendChild(createElement('p', 'gf-public-maintenance__eta', 'Estimated return: ' + eta));

    main.appendChild(panel);
    document.body.appendChild(main);
    document.body.classList.add('gf-public-maintenance-active');
  }

  function applyConfig(payload) {
    if (!payload || payload.ok !== true || !payload.site) return;

    const maintenance = payload.site.maintenance || {};
    if (text(payload.site.status) === 'maintenance' && text(maintenance.state) === 'active') {
      renderMaintenance(maintenance);
      return;
    }

    removeMaintenance();
    renderItems(payload);
  }

  function loadConfig() {
    fetch(endpoint, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    }).then(function (response) {
      if (!response.ok) throw new Error('homepage-config-http-' + response.status);
      return response.json();
    }).then(applyConfig).catch(function () {
      console.warn('GIRFFON homepage updates are temporarily unavailable.');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadConfig, { once: true });
  } else {
    loadConfig();
  }
})();