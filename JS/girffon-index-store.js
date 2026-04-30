(function (global) {
  'use strict';

  function buildPlaceholder(title, accent) {
    var safeTitle = String(title || 'GirffoN');
    var safeAccent = String(accent || '#d4a100');
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 960">'
      + '<defs>'
      + '<linearGradient id="g" x1="0" x2="1" y1="0" y2="1">'
      + '<stop offset="0%" stop-color="#111111"/>'
      + '<stop offset="100%" stop-color="' + safeAccent + '"/>'
      + '</linearGradient>'
      + '</defs>'
      + '<rect width="800" height="960" fill="url(#g)"/>'
      + '<circle cx="640" cy="180" r="120" fill="rgba(255,255,255,0.08)"/>'
      + '<circle cx="190" cy="760" r="180" fill="rgba(255,255,255,0.06)"/>'
      + '<text x="72" y="760" fill="#ffffff" font-family="Georgia, serif" font-size="72" font-weight="700">' + safeTitle + '</text>'
      + '<text x="72" y="830" fill="#f5f5f5" font-family="Arial, sans-serif" font-size="26">Premium GirffoN Collection</text>'
      + '</svg>'
    );
  }

  var notices = [
    {
      text: 'Free EU delivery on selected premium orders.',
      icon: 'fa-solid fa-truck-fast',
      visibleFrom: '2026-01-01T00:00:00Z',
      visibleTo: '2027-01-01T00:00:00Z'
    },
    {
      text: 'New custom design videos are live on the homepage hero.',
      icon: 'fa-solid fa-clapperboard',
      visibleFrom: '2026-01-01T00:00:00Z',
      visibleTo: '2027-01-01T00:00:00Z'
    }
  ];

  var heroItems = [
    {
      title: 'Tarot Collection',
      subtitle: 'Discover mystical tarot-inspired designs for your unique style.',
      mediaType: 'video',
      mediaUrl: 'Image/Banner/Mp4/banner1.mp4',
      active: true
    },
    {
      title: 'Men & Women',
      subtitle: 'Modern premium t-shirts designed for both men and women.',
      buttonText: 'Shop Now',
      buttonLink: 'catalog.html',
      mediaType: 'video',
      mediaUrl: 'Image/Banner/Mp4/banner2.mp4',
      active: true
    },
    {
      title: 'Boys & Girls',
      subtitle: 'Creative and fun designs for kids, boys and girls alike.',
      buttonText: 'Explore Kids',
      buttonLink: 'kids.html',
      mediaType: 'video',
      mediaUrl: 'Image/Banner/Mp4/banner3.mp4',
      active: true
    },
    {
      title: 'Accessories & Home Living',
      subtitle: 'Explore stylish accessories and artistic home living products.',
      buttonText: 'Discover More',
      buttonLink: 'home-living.html',
      mediaType: 'video',
      mediaUrl: 'Image/Banner/Mp4/banner4.mp4',
      active: true
    },
    {
      title: 'Custom Design Studio',
      subtitle: 'Upload artwork, add text, and build premium products with GirffoN design tools.',
      buttonText: 'Create Now',
      buttonLink: 'Image/Custom%20Design%20Pro/CustomDesignPro.html',
      mediaType: 'video',
      mediaUrl: 'Image/Banner/Mp4/banner5.mp4',
      active: true
    }
  ];

  var categoryCards = [
    {
      title: 'Men',
      subtitle: 'Graphic tees, premium basics, and refined custom silhouettes.',
      buttonText: 'Shop Men',
      buttonLink: 'men.html',
      imageUrl: buildPlaceholder('Men', '#8d6e63'),
      size: 'large',
      active: true
    },
    {
      title: 'Women',
      subtitle: 'Luxury casualwear and custom-ready premium fits.',
      buttonText: 'Shop Women',
      buttonLink: 'woman.html',
      imageUrl: buildPlaceholder('Women', '#c97b63'),
      active: true
    },
    {
      title: 'Kids',
      subtitle: 'Comfort-first personalized pieces for younger collections.',
      buttonText: 'Shop Kids',
      buttonLink: 'kids.html',
      imageUrl: buildPlaceholder('Kids', '#4d7c8a'),
      active: true
    },
    {
      title: 'Accessories',
      subtitle: 'Caps, mugs, bags, and add-ons that complete the look.',
      buttonText: 'Shop Accessories',
      buttonLink: 'accessories.html',
      imageUrl: buildPlaceholder('Accessories', '#6b8e23'),
      active: true
    }
  ];

  global.GIRFFON_INDEX_STORE = {
    getVisibleNotices: function (date) {
      var currentTime = date instanceof Date ? date.getTime() : Date.now();
      return notices.filter(function (item) {
        var start = new Date(item.visibleFrom).getTime();
        var end = new Date(item.visibleTo).getTime();
        return currentTime >= start && currentTime <= end;
      });
    },
    getActiveHeroItems: function () {
      return heroItems.filter(function (item) {
        return item.active !== false && item.mediaUrl;
      });
    },
    getActiveCategoryCards: function () {
      return categoryCards.filter(function (item) {
        return item.active !== false;
      });
    }
  };
})(window);