(function (global) {
  'use strict';

  const store = global.GIRFFON_INDEX_STORE;

  if (!store) {
    return;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function renderNoticeBar() {
    const noticeBar = document.getElementById('gfManagedNoticeBar');
    const noticeList = document.getElementById('gfManagedNoticeList');
    if (!noticeBar || !noticeList) return;

    const notices = store.getVisibleNotices(new Date());
    if (!notices.length) {
      noticeBar.classList.remove('is-visible');
      noticeList.innerHTML = '';
      return;
    }

    noticeList.innerHTML = notices.map(function (item) {
      const iconClass = String(item.icon || 'fa-solid fa-circle-info');
      return '<div class="gf-notice-pill">'
        + '<i class="' + escapeHtml(iconClass) + '" aria-hidden="true"></i>'
        + '<span>' + escapeHtml(item.text || '') + '</span>'
        + (item.testMode ? '<span class="gf-notice-pill__test">Test</span>' : '')
        + '</div>';
    }).join('');

    noticeBar.classList.add('is-visible');
  }

  function renderHeroSection() {
    const slidesHost = document.getElementById('gfHeroSlides');
    const dotsHost = document.getElementById('gfHeroDots');
    if (!slidesHost || !dotsHost) return;

    const heroItems = store.getActiveHeroItems();
    slidesHost.innerHTML = heroItems.map(function (item, index) {
      const mediaType = item.mediaType === 'image' ? 'image' : 'video';
      const content = item.title || item.subtitle || item.buttonText;
      const contentMarkup = content
        ? '<div class="slide-content">'
          + (item.title ? '<h2>' + escapeHtml(item.title) + '</h2>' : '')
          + (item.subtitle ? '<p>' + escapeHtml(item.subtitle) + '</p>' : '')
          + (item.buttonText ? '<a href="' + escapeHtml(item.buttonLink || '#') + '" class="slide-btn">' + escapeHtml(item.buttonText) + '</a>' : '')
          + '</div>'
        : '';

      return '<div class="slide' + (index === 0 ? ' active' : '') + '">'
        + (mediaType === 'video'
          ? '<video autoplay muted loop playsinline><source src="' + escapeHtml(item.mediaUrl || '') + '" type="video/mp4"></video>'
          : '<img src="' + escapeHtml(item.mediaUrl || '') + '" alt="' + escapeHtml(item.title || 'Hero banner') + '">')
        + contentMarkup
        + '</div>';
    }).join('');

    dotsHost.innerHTML = heroItems.map(function (_item, index) {
      return '<span class="dot' + (index === 0 ? ' active' : '') + '" data-slide="' + index + '"></span>';
    }).join('');
  }

  function renderCategorySection() {
    const categoryGrid = document.getElementById('gfCategoryGrid');
    if (!categoryGrid) return;

    const cards = store.getActiveCategoryCards();
    categoryGrid.innerHTML = cards.map(function (item) {
      const videoMarkup = item.videoUrl
        ? '<video class="card-video" muted loop playsinline preload="auto"><source src="' + escapeHtml(item.videoUrl) + '" type="video/mp4"></video>'
        : '';

      return '<article class="category-card' + (item.size === 'large' ? ' large' : '') + '">'
        + '<img src="' + escapeHtml(item.imageUrl || '') + '" alt="' + escapeHtml(item.title || 'Category card') + '" class="card-img">'
        + videoMarkup
        + '<div class="card-overlay"></div>'
        + '<div class="card-content">'
        + '<h3>' + escapeHtml(item.title || '') + '</h3>'
        + '<p>' + escapeHtml(item.subtitle || '') + '</p>'
        + '<a href="' + escapeHtml(item.buttonLink || '#') + '" class="card-btn">' + escapeHtml(item.buttonText || 'Shop Now') + '</a>'
        + '</div>'
        + '</article>';
    }).join('');
  }

  renderNoticeBar();
  renderHeroSection();
  renderCategorySection();
})(window);