(function () {
  if (window.GirffonAnalytics && window.GirffonAnalytics._shared) {
    return;
  }

  var VISITOR_KEY = 'girffon_analytics_visitor_id';
  var SESSION_KEY = 'girffon_analytics_session_id';
  var LAST_TOUCH_KEY = 'girffon_analytics_last_touch';
  var ONCE_PREFIX = 'girffon_analytics_once_';
  var EXIT_PREFIX = 'girffon_analytics_exit_';
  var TRACKER_VERSION = 'r20';
  var SESSION_TTL_MS = 30 * 60 * 1000;
  var HEARTBEAT_MS = 60 * 1000;
  var pageStartedAt = Date.now();
  var pageInstanceId = createId('page');
  var endpointUrl = resolveEndpointUrl();
  var userAgentHintsCache = {
    ready: false,
    values: {}
  };

  function createId(prefix) {
    return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
  }

  function safeStorageGet(storage, key) {
    try {
      return storage.getItem(key) || '';
    } catch (_error) {
      return '';
    }
  }

  function safeStorageSet(storage, key, value) {
    try {
      storage.setItem(key, value);
    } catch (_error) {
    }
  }

  function resolveBasePath() {
    var currentScript = document.currentScript;
    if (currentScript) {
      var rawSrc = String(currentScript.getAttribute('src') || '').trim();
      if (rawSrc) {
        try {
          var scriptUrl = new URL(rawSrc, window.location.href);
          var pathname = scriptUrl.pathname || '';
          var markerIndex = pathname.lastIndexOf('/JS/analytics.js');
          if (markerIndex !== -1) {
            return pathname.slice(0, markerIndex);
          }
        } catch (_error) {
        }
      }
    }

    var path = String(window.location.pathname || '');
    var girffonIndex = path.toLowerCase().indexOf('/girffon/');
    if (girffonIndex !== -1) {
      return path.slice(0, girffonIndex + '/GirffoN'.length);
    }

    return '';
  }

  function resolveEndpointUrl() {
    var basePath = resolveBasePath();
    return (basePath || '') + '/backend/analytics/track.php';
  }

  function isPublicPage() {
    var body = document.body;
    var path = String(window.location.pathname || '').toLowerCase();
    return !(body && body.dataset && body.dataset.adminPage) && path.indexOf('/admin') === -1 && path.indexOf('admin-') === -1;
  }

  function detectBrowser() {
    var userAgent = String(window.navigator.userAgent || '').toLowerCase();
    if (userAgent.indexOf('edg/') !== -1) {
      return 'Edge';
    }
    if (userAgent.indexOf('firefox/') !== -1) {
      return 'Firefox';
    }
    if (userAgent.indexOf('chrome/') !== -1 || userAgent.indexOf('crios/') !== -1) {
      return 'Chrome';
    }
    if ((userAgent.indexOf('safari/') !== -1 || userAgent.indexOf('applewebkit/') !== -1) && userAgent.indexOf('chrome/') === -1 && userAgent.indexOf('crios/') === -1 && userAgent.indexOf('edg/') === -1) {
      return 'Safari';
    }
    return 'Other';
  }

  function warmUserAgentHints() {
    if (!window.navigator.userAgentData || typeof window.navigator.userAgentData.getHighEntropyValues !== 'function') {
      return;
    }

    window.navigator.userAgentData.getHighEntropyValues([
      'architecture',
      'bitness',
      'formFactors',
      'model',
      'platform',
      'platformVersion',
      'uaFullVersion',
      'fullVersionList'
    ]).then(function (values) {
      userAgentHintsCache.ready = true;
      userAgentHintsCache.values = values || {};
    }).catch(function () {
      userAgentHintsCache.ready = false;
      userAgentHintsCache.values = {};
    });
  }

  function getPlatformValue() {
    if (window.navigator.userAgentData && window.navigator.userAgentData.platform) {
      return String(window.navigator.userAgentData.platform || '');
    }
    return String(window.navigator.platform || '');
  }

  function getOrientationValue() {
    if (window.screen && window.screen.orientation && window.screen.orientation.type) {
      return String(window.screen.orientation.type || '');
    }

    return Number(window.innerWidth || 0) >= Number(window.innerHeight || 0) ? 'landscape' : 'portrait';
  }

  function getPointerType() {
    try {
      if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
        return 'coarse';
      }
      if (window.matchMedia && window.matchMedia('(pointer: fine)').matches) {
        return 'fine';
      }
      if (window.matchMedia && window.matchMedia('(pointer: none)').matches) {
        return 'none';
      }
    } catch (_error) {
    }

    return 'unknown';
  }

  function getDeviceSignals() {
    var userAgent = String(window.navigator.userAgent || '');
    var platform = getPlatformValue();
    var screenWidth = Number(window.screen && window.screen.width || 0);
    var screenHeight = Number(window.screen && window.screen.height || 0);
    var viewportWidth = Number(window.innerWidth || 0);
    var viewportHeight = Number(window.innerHeight || 0);
    var touchPoints = Number(window.navigator.maxTouchPoints || 0);
    var devicePixelRatio = Number(window.devicePixelRatio || 1);
    var pointerType = getPointerType();
    var orientation = getOrientationValue();
    var clientHints = {
      mobile: !!(window.navigator.userAgentData && window.navigator.userAgentData.mobile),
      platform: String(window.navigator.userAgentData && window.navigator.userAgentData.platform || ''),
      brands: Array.isArray(window.navigator.userAgentData && window.navigator.userAgentData.brands)
        ? window.navigator.userAgentData.brands.map(function (brand) {
            return String(brand && brand.brand || '');
          }).filter(Boolean)
        : []
    };

    if (userAgentHintsCache.ready && userAgentHintsCache.values) {
      clientHints.model = String(userAgentHintsCache.values.model || '');
      clientHints.platformVersion = String(userAgentHintsCache.values.platformVersion || '');
      clientHints.architecture = String(userAgentHintsCache.values.architecture || '');
      clientHints.bitness = String(userAgentHintsCache.values.bitness || '');
      clientHints.uaFullVersion = String(userAgentHintsCache.values.uaFullVersion || '');
      clientHints.formFactors = Array.isArray(userAgentHintsCache.values.formFactors)
        ? userAgentHintsCache.values.formFactors.map(function (value) { return String(value || ''); }).filter(Boolean)
        : [];
    }

    return {
      userAgent: userAgent,
      userAgentLower: userAgent.toLowerCase(),
      platform: String(platform || ''),
      platformLower: String(platform || '').toLowerCase(),
      screenWidth: screenWidth,
      screenHeight: screenHeight,
      viewportWidth: viewportWidth,
      viewportHeight: viewportHeight,
      shortScreen: Math.min(screenWidth || viewportWidth || 0, screenHeight || viewportHeight || 0),
      longScreen: Math.max(screenWidth || viewportWidth || 0, screenHeight || viewportHeight || 0),
      shortViewport: Math.min(viewportWidth || screenWidth || 0, viewportHeight || screenHeight || 0),
      longViewport: Math.max(viewportWidth || screenWidth || 0, viewportHeight || screenHeight || 0),
      touchPoints: touchPoints,
      devicePixelRatio: devicePixelRatio,
      pointerType: pointerType,
      orientation: String(orientation || ''),
      clientHints: clientHints
    };
  }

  function isTabletModelMatch(text) {
    return /(lenovo[\s-]*tab|tb[-\s]?\w+|yt[-\s]?\w+|m10|m9|p11|p12|xiaomi[\s-]*pad|redmi[\s-]*pad|matepad|honor[\s-]*pad|oneplus[\s-]*pad|pixel[\s-]*tablet|sm-x\w+|galaxy[\s-]*tab|fire[\s-]*hd|kf[a-z]{2,4}\w*|nokia[\s-]*t\d+|tcl[\s-]*tab|alcatel[\s-]*(?:1t|3t|joy[\s-]*tab)|xperia[\s-]*tablet|zenpad|transformer|iconia|venue[\s-]*\d+|surface|mi[\s-]*pad)/i.test(text);
  }

  function detectDevice() {
    var signals = getDeviceSignals();
    var userAgent = signals.userAgentLower;
    var platform = signals.platformLower;
    var touchPoints = signals.touchPoints;
    var shortViewport = signals.shortViewport;
    var longViewport = signals.longViewport;
    var shortScreen = signals.shortScreen;
    var pointerType = signals.pointerType;
    var dpr = signals.devicePixelRatio;
    var clientHints = signals.clientHints || {};
    var clientHintsText = [
      clientHints.platform,
      clientHints.model,
      Array.isArray(clientHints.formFactors) ? clientHints.formFactors.join(' ') : '',
      Array.isArray(clientHints.brands) ? clientHints.brands.join(' ') : ''
    ].join(' ').toLowerCase();
    var isWindows = userAgent.indexOf('windows nt') !== -1 || platform.indexOf('win') !== -1 || clientHintsText.indexOf('windows') !== -1;
    var isAndroid = userAgent.indexOf('android') !== -1 || platform.indexOf('android') !== -1 || clientHintsText.indexOf('android') !== -1;
    var isIpadDesktopMode = (userAgent.indexOf('macintosh') !== -1 || platform.indexOf('mac') !== -1) && touchPoints > 1;
    var hasTabletKeyword = /ipad|tablet|playbook|silk|kindle|nexus 7|nexus 9|nexus 10|sm-t|xoom/.test(userAgent) || isTabletModelMatch(userAgent) || isTabletModelMatch(clientHintsText);
    var androidWithoutMobile = isAndroid && userAgent.indexOf('mobile') === -1;
    var explicitPhone = /mobi|iphone|ipod|phone/.test(userAgent) || (clientHints.mobile === true && !isIpadDesktopMode);
    var surfaceTabletMode = isWindows
      && touchPoints > 1
      && pointerType === 'coarse'
      && shortViewport >= 700
      && shortViewport <= 1100
      && longViewport <= 1500;
    var genericTouchTablet = touchPoints > 1
      && shortViewport >= 600
      && shortViewport <= 1280
      && longViewport >= 800
      && !explicitPhone
      && (pointerType === 'coarse' || dpr >= 1.25)
      && (!isWindows || surfaceTabletMode);
    var screenSizedTablet = touchPoints > 1
      && shortScreen >= 600
      && shortScreen <= 1600
      && !explicitPhone
      && (!isWindows || surfaceTabletMode);

    if (isIpadDesktopMode || hasTabletKeyword) {
      return 'Tablet';
    }
    if (androidWithoutMobile) {
      return 'Tablet';
    }
    if (surfaceTabletMode) {
      return 'Tablet';
    }
    if (explicitPhone) {
      return 'Mobile';
    }
    if (genericTouchTablet || screenSizedTablet) {
      return 'Tablet';
    }

    return 'Desktop';
  }

  function extractSearchKeyword() {
    var candidates = [];

    try {
      var currentUrl = new URL(window.location.href);
      ['utm_term', 'q', 'query', 'search', 'keyword', 'k', 's', 'text'].forEach(function (key) {
        var value = currentUrl.searchParams.get(key);
        if (value) {
          candidates.push(value);
        }
      });
    } catch (_error) {
    }

    try {
      if (document.referrer) {
        var referrerUrl = new URL(document.referrer);
        ['q', 'p', 'query', 'search', 'keyword', 'k', 's', 'text', 'utm_term'].forEach(function (key) {
          var value = referrerUrl.searchParams.get(key);
          if (value) {
            candidates.push(value);
          }
        });
      }
    } catch (_error) {
    }

    return String(candidates.find(Boolean) || '').trim();
  }

  function getCurrentUrl() {
    try {
      return new URL(window.location.href);
    } catch (_error) {
      return null;
    }
  }

  function getReferrerUrl() {
    try {
      return document.referrer ? new URL(document.referrer) : null;
    } catch (_error) {
      return null;
    }
  }

  function isInternalHost(hostname) {
    var host = String(hostname || '').toLowerCase();
    if (!host) {
      return false;
    }
    var currentHost = String(window.location.hostname || '').toLowerCase();
    if (host === currentHost) {
      return true;
    }
    return host === 'girffon.shop' || host === 'www.girffon.shop' || host === 'localhost';
  }

  function detectTrafficSourceDecision() {
    var currentUrl = getCurrentUrl();
    var referrerUrl = getReferrerUrl();
    var referrer = String(document.referrer || '');
    var referrerHost = String(referrerUrl && referrerUrl.hostname || '').toLowerCase();
    var userAgent = String(window.navigator.userAgent || '');
    var userAgentLower = userAgent.toLowerCase();
    var utmSource = String(currentUrl && currentUrl.searchParams.get('utm_source') || '').trim().toLowerCase();
    var fbclid = String(currentUrl && currentUrl.searchParams.get('fbclid') || '').trim();
    var internalReferrer = isInternalHost(referrerHost);
    var normalizedReferrerHost = internalReferrer ? '' : referrerHost;
    var instagramReferrer = /(^|\.)((l|lm)\.)?instagram\.com$/.test(normalizedReferrerHost);
    var facebookReferrer = /(^|\.)((m|l|lm|web)\.)?facebook\.com$/.test(normalizedReferrerHost) || /(^|\.)fb\.com$/.test(normalizedReferrerHost) || /(^|\.)m\.me$/.test(normalizedReferrerHost);
    var googleReferrer = normalizedReferrerHost.indexOf('google.') !== -1;
    var bingReferrer = normalizedReferrerHost.indexOf('bing.') !== -1;
    var instagramUa = /instagram/.test(userAgentLower);
    var facebookUa = /fban|fbav|fb_iab|fb4a|fbios|facebook|messenger/.test(userAgentLower);

    if (utmSource === 'instagram') {
      return { source: 'Instagram', rule: 'utm_source_instagram', utm_source: utmSource, fbclid: fbclid };
    }
    if (utmSource === 'facebook' || utmSource === 'messenger') {
      return { source: 'Facebook', rule: 'utm_source_facebook', utm_source: utmSource, fbclid: fbclid };
    }
    if (instagramReferrer) {
      return { source: 'Instagram', rule: 'instagram_referrer', utm_source: utmSource, fbclid: fbclid };
    }
    if (facebookReferrer) {
      return { source: 'Facebook', rule: 'facebook_referrer', utm_source: utmSource, fbclid: fbclid };
    }
    if (fbclid !== '') {
      return { source: 'Facebook', rule: 'fbclid_parameter', utm_source: utmSource, fbclid: fbclid };
    }
    if (!normalizedReferrerHost && instagramUa) {
      return { source: 'Instagram', rule: 'instagram_in_app_browser', utm_source: utmSource, fbclid: fbclid };
    }
    if (!normalizedReferrerHost && facebookUa) {
      return { source: 'Facebook', rule: 'facebook_in_app_browser', utm_source: utmSource, fbclid: fbclid };
    }
    if (googleReferrer) {
      return { source: 'Google', rule: 'google_referrer', utm_source: utmSource, fbclid: fbclid };
    }
    if (bingReferrer) {
      return { source: 'Bing', rule: 'bing_referrer', utm_source: utmSource, fbclid: fbclid };
    }
    if (!normalizedReferrerHost) {
      return { source: 'Direct', rule: 'direct_no_referrer', utm_source: utmSource, fbclid: fbclid };
    }

    return { source: 'Other', rule: 'other_referrer', utm_source: utmSource, fbclid: fbclid };
  }

  function getContext() {
    if (!isPublicPage()) {
      return null;
    }

    var now = Date.now();
    var visitorId = safeStorageGet(window.localStorage, VISITOR_KEY);
    if (!visitorId) {
      visitorId = createId('visitor');
      safeStorageSet(window.localStorage, VISITOR_KEY, visitorId);
    }

    var sessionId = safeStorageGet(window.sessionStorage, SESSION_KEY);
    var lastTouch = Number.parseInt(safeStorageGet(window.sessionStorage, LAST_TOUCH_KEY) || '0', 10) || 0;
    if (!sessionId || !lastTouch || (now - lastTouch) > SESSION_TTL_MS) {
      sessionId = createId('session');
      safeStorageSet(window.sessionStorage, SESSION_KEY, sessionId);
    }

    safeStorageSet(window.sessionStorage, LAST_TOUCH_KEY, String(now));

    return {
      visitor_id: visitorId,
      session_id: sessionId
    };
  }

  function debugLog(stage, details) {
    try {
      console.info('[GirffoN Analytics]', stage, details || {});
    } catch (_error) {
    }
  }

  try {
    console.info('GIRFFON ANALYTICS R20 LOADED');
  } catch (_error) {
  }

  function buildPayload(eventType, meta) {
    var context = getContext();
    if (!context) {
      return null;
    }

    var safeMeta = meta && typeof meta === 'object' ? Object.assign({}, meta) : {};
    if (!safeMeta.search_keyword) {
      var searchKeyword = extractSearchKeyword();
      if (searchKeyword) {
        safeMeta.search_keyword = searchKeyword;
      }
    }

    var signals = getDeviceSignals();
    var sourceDecision = detectTrafficSourceDecision();
    safeMeta.page_url = String(window.location.href || '');
    safeMeta.user_agent = String(window.navigator.userAgent || '');
    safeMeta.document_referrer = String(document.referrer || '');
    safeMeta.platform = signals.platform;
    safeMeta.touch_points = signals.touchPoints;
    safeMeta.viewport_width = signals.viewportWidth;
    safeMeta.viewport_height = signals.viewportHeight;
    safeMeta.screen_width = signals.screenWidth;
    safeMeta.screen_height = signals.screenHeight;
    safeMeta.device_pixel_ratio = signals.devicePixelRatio;
    safeMeta.pointer_type = signals.pointerType;
    safeMeta.orientation = signals.orientation;
    safeMeta.ua_client_hints = signals.clientHints;
    safeMeta.tracker_version = TRACKER_VERSION;
    safeMeta.utm_source = sourceDecision.utm_source;
    safeMeta.fbclid = sourceDecision.fbclid;
    safeMeta.matched_source_rule = sourceDecision.rule;

    var payload = {
      visitor_id: context.visitor_id,
      session_id: context.session_id,
      event_type: String(eventType || 'page_view'),
      page_path: String(window.location.pathname || '/'),
      page_title: String(document.title || ''),
      referrer: String(document.referrer || ''),
      traffic_source: sourceDecision.source,
      user_agent: safeMeta.user_agent,
      browser_name: detectBrowser(),
      device_type: detectDevice(),
      touch_points: safeMeta.touch_points,
      viewport_width: safeMeta.viewport_width,
      viewport_height: safeMeta.viewport_height,
      screen_width: safeMeta.screen_width,
      screen_height: safeMeta.screen_height,
      device_pixel_ratio: safeMeta.device_pixel_ratio,
      platform: safeMeta.platform,
      pointer_type: safeMeta.pointer_type,
      orientation: safeMeta.orientation,
      tracker_version: TRACKER_VERSION,
      meta: safeMeta
    };

    if (safeMeta.duration_seconds != null) {
      payload.duration_seconds = Number(safeMeta.duration_seconds) || 0;
    }

    return payload;
  }

  function sendPayload(payload, preferBeacon) {
    if (!payload || !endpointUrl) {
      return Promise.resolve(false);
    }

    debugLog('request', {
      tracker_version: payload.tracker_version,
      traffic_source: payload.traffic_source,
      matched_source_rule: payload.meta && payload.meta.matched_source_rule,
      document_referrer: payload.referrer,
      utm_source: payload.meta && payload.meta.utm_source,
      fbclid: payload.meta && payload.meta.fbclid,
      user_agent: payload.user_agent || payload.meta && payload.meta.user_agent || window.navigator.userAgent,
      platform: payload.platform,
      viewport_width: payload.viewport_width,
      viewport_height: payload.viewport_height,
      screen_width: payload.screen_width,
      screen_height: payload.screen_height,
      touch_points: payload.touch_points,
      device_pixel_ratio: payload.device_pixel_ratio,
      pointer_type: payload.pointer_type,
      orientation: payload.orientation,
      event_type: payload.event_type,
      detected_device: payload.device_type,
      page_url: payload.meta && payload.meta.page_url || payload.page_path,
      endpoint: endpointUrl
    });

    if (preferBeacon && navigator.sendBeacon) {
      try {
        var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        var queued = navigator.sendBeacon(endpointUrl, blob);
        debugLog('response', {
          tracker_version: payload.tracker_version,
          traffic_source: payload.traffic_source,
          matched_source_rule: payload.meta && payload.meta.matched_source_rule,
          document_referrer: payload.referrer,
          utm_source: payload.meta && payload.meta.utm_source,
          fbclid: payload.meta && payload.meta.fbclid,
          user_agent: payload.user_agent || payload.meta && payload.meta.user_agent || window.navigator.userAgent,
          platform: payload.platform,
          viewport_width: payload.viewport_width,
          viewport_height: payload.viewport_height,
          screen_width: payload.screen_width,
          screen_height: payload.screen_height,
          touch_points: payload.touch_points,
          device_pixel_ratio: payload.device_pixel_ratio,
          pointer_type: payload.pointer_type,
          orientation: payload.orientation,
          event_type: payload.event_type,
          detected_device: payload.device_type,
          page_url: payload.meta && payload.meta.page_url || payload.page_path,
          endpoint_response: { transport: 'beacon', queued: queued }
        });
        return Promise.resolve(queued);
      } catch (error) {
        debugLog('error', { message: error && error.message || 'Beacon send failed.' });
      }
    }

    return fetch(endpointUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      keepalive: true,
      cache: 'no-store'
    }).then(function (response) {
      return response.json().catch(function () {
        return null;
      }).then(function (body) {
        debugLog('response', {
          tracker_version: payload.tracker_version,
          traffic_source: payload.traffic_source,
          matched_source_rule: payload.meta && payload.meta.matched_source_rule,
          document_referrer: payload.referrer,
          utm_source: payload.meta && payload.meta.utm_source,
          fbclid: payload.meta && payload.meta.fbclid,
          user_agent: payload.meta && payload.meta.user_agent || window.navigator.userAgent,
          platform: payload.platform,
          viewport_width: payload.viewport_width,
          screen_width: payload.screen_width,
          touch_points: payload.touch_points,
          device_pixel_ratio: payload.device_pixel_ratio,
          event_type: payload.event_type,
          detected_device: payload.device_type,
          page_url: payload.meta && payload.meta.page_url || payload.page_path,
          endpoint_response: {
            status: response.status,
            ok: response.ok,
            body: body
          }
        });
        return response.ok;
      });
    }).catch(function (error) {
      debugLog('error', {
        tracker_version: payload.tracker_version,
        traffic_source: payload.traffic_source,
        matched_source_rule: payload.meta && payload.meta.matched_source_rule,
        document_referrer: payload.referrer,
        utm_source: payload.meta && payload.meta.utm_source,
        fbclid: payload.meta && payload.meta.fbclid,
        user_agent: payload.meta && payload.meta.user_agent || window.navigator.userAgent,
        platform: payload.platform,
        viewport_width: payload.viewport_width,
        screen_width: payload.screen_width,
        touch_points: payload.touch_points,
        device_pixel_ratio: payload.device_pixel_ratio,
        event_type: payload.event_type,
        detected_device: payload.device_type,
        page_url: payload.meta && payload.meta.page_url || payload.page_path,
        message: error && error.message || 'Network error'
      });
      return false;
    });
  }

  function track(eventType, meta) {
    return sendPayload(buildPayload(eventType, meta), false);
  }

  function trackOnce(eventType, onceKey, meta) {
    var storageKey = ONCE_PREFIX + String(onceKey || eventType || 'event');
    if (safeStorageGet(window.sessionStorage, storageKey) === '1') {
      return Promise.resolve(false);
    }
    safeStorageSet(window.sessionStorage, storageKey, '1');
    return track(eventType, meta);
  }

  function trackExit() {
    if (!isPublicPage()) {
      return false;
    }
    var exitKey = EXIT_PREFIX + pageInstanceId;
    if (safeStorageGet(window.sessionStorage, exitKey) === '1') {
      return false;
    }
    safeStorageSet(window.sessionStorage, exitKey, '1');
    sendPayload(buildPayload('page_exit', {
      duration_seconds: Math.max(1, Math.round((Date.now() - pageStartedAt) / 1000)),
      search_keyword: extractSearchKeyword()
    }), true);
    return true;
  }

  function trackPageSpecificOpenEvents() {
    var pathname = String(window.location.pathname || '').toLowerCase();
    if (pathname.indexOf('/image/custom%20design%20pro/') !== -1 || pathname.indexOf('/image/custom design pro/') !== -1) {
      trackOnce('custom_design_open', 'custom-design-open:' + pathname, {
        page: 'custom_design',
        product: String(document.title || 'Custom Design')
      });
    }
  }

  window.GirffonAnalytics = {
    _shared: true,
    endpoint: endpointUrl,
    detectDevice: detectDevice,
    detectBrowser: detectBrowser,
    track: track,
    trackOnce: trackOnce,
    trackExit: trackExit,
    getContext: getContext
  };

  document.addEventListener('DOMContentLoaded', function () {
    warmUserAgentHints();

    if (!isPublicPage()) {
      return;
    }

    track('page_view', {
      section: 'public_website'
    });

    trackPageSpecificOpenEvents();

    window.setInterval(function () {
      track('heartbeat', {
        active_seconds: Math.max(1, Math.round((Date.now() - pageStartedAt) / 1000))
      });
    }, HEARTBEAT_MS);
  });

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      trackExit();
    }
  });

  window.addEventListener('pagehide', function () {
    trackExit();
  });
})();