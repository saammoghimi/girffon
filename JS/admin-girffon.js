(function () {
  const STORAGE_KEYS = {
    products: "girffon_admin_products",
    messages: "girffon_admin_messages",
    settings: "girffon_admin_settings"
  };

  const PAGE_TITLES = {
    dashboard: "Dashboard",
    homepage: "Homepage",
    products: "Products",
    orders: "Orders",
    invoices: "Invoices",
    messages: "Messages",
    settings: "Settings"
  };

  const DEFAULT_SETTINGS = {
    storeName: "GirffoN",
    storeEmail: "store@girffon.com",
    country: "Italy",
    currency: "EUR",
    taxRate: 12,
    shippingCost: 9.9,
    returnPolicyText: "Returns are accepted within 14 days for unused products in original condition.",
    supportEmail: "support@girffon.com"
  };

  const SEED_DATA = {
    products: [
      {
        id: uid(),
        name: "Organic T-Shirt Black",
        sku: "GF-BLK-101",
        price: 39.9,
        category: "Men",
        size: "M, L, XL",
        color: "Black",
        status: "Active",
        imageUrl: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80",
        stock: 28
      },
      {
        id: uid(),
        name: "Premium Gold Hoodie",
        sku: "GF-HOOD-201",
        price: 89.0,
        category: "Limited",
        size: "L, XL",
        color: "Gold",
        status: "Draft",
        imageUrl: "https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=900&q=80",
        stock: 12
      }
    ],
    messages: [
      {
        id: uid(),
        customerName: "Sofia Moretti",
        email: "sofia@example.com",
        message: "Can I update the print color after placing my order?",
        status: "Unread",
        createdAt: todayString()
      },
      {
        id: uid(),
        customerName: "Noah Weber",
        email: "noah@example.com",
        message: "Please confirm estimated delivery time for Germany.",
        status: "Read",
        createdAt: "2026-04-22"
      }
    ]
  };

  document.addEventListener("DOMContentLoaded", init);

  function init() {
    const body = document.body;
    const page = body.dataset.adminPage || "login";

    seedStore();
    normalizeStore();

    bindLogout();
    bindSettings();
    bindTableActions();
    bindMobileAdminMenus();
    setPageTitle(page);

    switch (page) {
      case "login":
        initLoginPage();
        break;
      case "dashboard":
        initDashboardPage();
        break;
      case "products":
        initProductsPage();
        break;
      case "orders":
        initOrdersPage();
        break;
      case "invoices":
        initInvoicesPage();
        break;
      case "messages":
        initMessagesPage();
        break;
      case "settings":
        initSettingsPage();
        break;
      default:
        break;
    }
  }

  function bindMobileAdminMenus() {
    const body = document.body;
    if (!body || !body.classList.contains("admin-page")) {
      return;
    }

    const sidebar = document.querySelector(".admin-sidebar");

    if (!sidebar) {
      return;
    }

    const configuredBreakpoint = Number.parseInt(body.dataset.adminMenuBreakpoint || "1024", 10);
    const mobileBreakpoint = Number.isFinite(configuredBreakpoint) && configuredBreakpoint > 0 ? configuredBreakpoint : 1024;
    const mobileQuery = window.matchMedia(`(max-width: ${mobileBreakpoint}px)`);
    let menuToggle = document.querySelector(".admin-mobile-sidebar-toggle");
    let overlay = document.querySelector(".admin-mobile-overlay");

    const ensureUi = function () {
      if (!menuToggle) {
        menuToggle = document.createElement("button");
        menuToggle.type = "button";
        menuToggle.className = "admin-mobile-sidebar-toggle";
        menuToggle.setAttribute("aria-label", "Toggle admin sidebar");
        menuToggle.setAttribute("aria-expanded", "false");
        body.appendChild(menuToggle);
      }

      if (!overlay) {
        overlay = document.createElement("button");
        overlay.type = "button";
        overlay.className = "admin-mobile-overlay";
        overlay.setAttribute("aria-label", "Close admin sidebar");
        body.appendChild(overlay);
      }
    };

    const closeSidebar = function () {
      body.classList.remove("sidebar-open");
      if (menuToggle) {
        menuToggle.setAttribute("aria-expanded", "false");
      }
    };

    const toggleSidebar = function () {
      body.classList.toggle("sidebar-open");
      if (menuToggle) {
        menuToggle.setAttribute("aria-expanded", String(body.classList.contains("sidebar-open")));
      }
    };

    const bindUi = function () {
      ensureUi();

      if (menuToggle && !menuToggle.dataset.mobileBound) {
        menuToggle.dataset.mobileBound = "true";
        menuToggle.addEventListener("click", toggleSidebar);
      }

      if (overlay && !overlay.dataset.mobileBound) {
        overlay.dataset.mobileBound = "true";
        overlay.addEventListener("click", closeSidebar);
      }

      if (!sidebar.dataset.mobileBound) {
        sidebar.dataset.mobileBound = "true";
        sidebar.addEventListener("click", function (event) {
          const target = event.target;
          if (!(target instanceof Element) || !mobileQuery.matches) {
            return;
          }

          if (target.closest(".admin-nav-link, .admin-logout-button")) {
            closeSidebar();
          }
        });
      }
    };

    const sync = function () {
      bindUi();

      if (!mobileQuery.matches) {
        closeSidebar();
      }
    };

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeSidebar();
      }
    });

    sync();

    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", sync);
    } else if (typeof mobileQuery.addListener === "function") {
      mobileQuery.addListener(sync);
    }
  }

  function initLoginPage() {
    const status = document.getElementById("adminLoginStatus");

    clearLegacyAdminAuth();

    if (!status) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    const errorMessage = String(params.get("error") || "").trim();
    if (errorMessage) {
      setStatus(status, errorMessage, false);
    }
  }

  function initDashboardPage() {
    initDashboardAnalyticsExplorer();
    initDashboardVisitorAnalytics();
    initDashboardWeatherWidget();
    initDashboardWorldClock();
    if (document.body.dataset.adminDashboardSource === "database") {
      return;
    }
    renderCounts();
    renderDashboardLists();
  }

  function initDashboardAnalyticsExplorer() {
    const body = document.body;
    const root = document.querySelector("[data-admin-analytics-explorer]");
    if (!root) {
      return;
    }

    let analyticsData = null;
    try {
      analyticsData = JSON.parse(body.dataset.adminAnalytics || "{}");
    } catch (_error) {
      analyticsData = null;
    }

    if (!analyticsData) {
      return;
    }

    const summaryNode = root.querySelector("[data-analytics-summary]");
    const chartNode = root.querySelector("[data-analytics-chart]");
    const monthWrap = root.querySelector("[data-analytics-month-wrap]");
    const monthSelect = root.querySelector("[data-analytics-month]");
    const downloadButton = root.querySelector("[data-analytics-download-pdf]");
    const periodButtons = Array.from(root.querySelectorAll("[data-analytics-period]"));
    const yearButtons = Array.from(root.querySelectorAll("[data-analytics-year]"));
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const state = {
      period: "daily",
      year: Number(analyticsData.selectedYear || new Date().getFullYear()),
      month: Number(analyticsData.selectedMonth || (new Date().getMonth() + 1))
    };

    const getDataset = function () {
      if (state.period === "yearly") {
        return analyticsData.yearly || { summary: {}, series: [] };
      }

      if (state.period === "monthly") {
        return ((analyticsData.monthly || {})[String(state.year)]) || { summary: {}, series: [] };
      }

      return ((((analyticsData.daily || {})[String(state.year)] || {})[String(state.month)]) || { summary: {}, series: [] });
    };

    const syncControls = function () {
      periodButtons.forEach(function (button) {
        button.classList.toggle("is-active", button.dataset.analyticsPeriod === state.period);
      });
      yearButtons.forEach(function (button) {
        button.classList.toggle("is-active", Number(button.dataset.analyticsYear || 0) === state.year);
      });
      if (monthSelect) {
        monthSelect.value = String(state.month);
      }
      if (monthWrap) {
        monthWrap.hidden = state.period === "yearly";
      }
    };

    const renderSummary = function (dataset) {
      if (!summaryNode) {
        return;
      }

      const summary = dataset.summary || {};
      const periodLabel = state.period === "yearly"
        ? (dataset.label || "Yearly")
        : (state.period === "monthly" ? ("Year " + state.year) : (monthNames[state.month - 1] + " " + state.year));

      summaryNode.innerHTML = [
        { label: periodLabel, value: String(summary.orders || 0) + " orders" },
        { label: "Revenue", value: formatCurrency(summary.revenue || 0) },
        { label: "Invoices", value: String(summary.invoices || 0) },
        { label: "New Members", value: String(summary.members || 0) }
      ].map(function (item) {
        return '<div class="admin-analytics-summary-card"><span>' + escapeHtml(item.label) + '</span><strong>' + escapeHtml(item.value) + '</strong></div>';
      }).join("");
    };

    const renderChart = function (dataset) {
      if (!chartNode) {
        return;
      }

      const series = Array.isArray(dataset.series) ? dataset.series : [];
      const maxOrders = Math.max.apply(null, series.map(function (item) {
        return Number(item.orders || 0);
      }).concat([1]));

      chartNode.innerHTML = series.map(function (item) {
        const orders = Number(item.orders || 0);
        const width = orders <= 0 ? 0 : Math.max(6, Math.round((orders / maxOrders) * 100));

        return '<div class="admin-analytics-bar">' +
          '<span class="admin-analytics-bar-label">' + escapeHtml(item.label || '-') + '</span>' +
          '<div class="admin-analytics-bar-track"><span class="admin-analytics-bar-fill" style="width:' + width + '%"></span></div>' +
          '<span class="admin-analytics-bar-value">' + escapeHtml(String(orders)) + '</span>' +
          '</div>';
      }).join("");
    };

    const render = function () {
      const dataset = getDataset();
      syncControls();
      renderSummary(dataset);
      renderChart(dataset);
    };

    const buildReportTitle = function () {
      if (state.period === "yearly") {
        return "Yearly Stats Report " + ((getDataset() || {}).label || "2026 - 2030");
      }

      if (state.period === "monthly") {
        return "Monthly Stats Report " + state.year;
      }

      return "Daily Stats Report " + monthNames[state.month - 1] + " " + state.year;
    };

    const downloadPdfReport = function () {
      const dataset = getDataset() || { summary: {}, series: [] };
      const reportWindow = window.open("", "_blank", "width=1080,height=860");
      if (!reportWindow) {
        return;
      }

      const rows = (Array.isArray(dataset.series) ? dataset.series : []).map(function (item) {
        return "<tr>" +
          "<td>" + escapeHtml(item.label || "-") + "</td>" +
          "<td>" + escapeHtml(String(item.orders || 0)) + "</td>" +
          "<td>" + escapeHtml(formatCurrency(item.revenue || 0)) + "</td>" +
          "<td>" + escapeHtml(String(item.invoices || 0)) + "</td>" +
          "<td>" + escapeHtml(String(item.members || 0)) + "</td>" +
          "</tr>";
      }).join("");

      reportWindow.document.open();
      reportWindow.document.write(
        "<!DOCTYPE html><html><head><title>" + escapeHtml(buildReportTitle()) + "</title>" +
        "<style>body{font-family:Georgia,serif;padding:32px;color:#2b241b}h1{margin:0 0 12px;font-size:28px}p{margin:0 0 18px;color:#6b5a3b}.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0 24px}.card{border:1px solid #e7d7ad;border-radius:16px;padding:14px;background:#fffaf0}.card span{display:block;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#8a7753}.card strong{display:block;margin-top:8px;font-size:22px}table{width:100%;border-collapse:collapse}th,td{padding:10px 12px;border-bottom:1px solid #eadfca;text-align:left}th{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#6b5a3b} @media print {button{display:none}}</style></head><body>" +
        "<h1>" + escapeHtml(buildReportTitle()) + "</h1>" +
        "<p>Generated from GirffoN Admin Dashboard analytics.</p>" +
        "<div class='summary'>" +
        "<div class='card'><span>Orders</span><strong>" + escapeHtml(String((dataset.summary || {}).orders || 0)) + "</strong></div>" +
        "<div class='card'><span>Revenue</span><strong>" + escapeHtml(formatCurrency((dataset.summary || {}).revenue || 0)) + "</strong></div>" +
        "<div class='card'><span>Invoices</span><strong>" + escapeHtml(String((dataset.summary || {}).invoices || 0)) + "</strong></div>" +
        "<div class='card'><span>New Members</span><strong>" + escapeHtml(String((dataset.summary || {}).members || 0)) + "</strong></div>" +
        "</div>" +
        "<table><thead><tr><th>Period</th><th>Orders</th><th>Revenue</th><th>Invoices</th><th>New Members</th></tr></thead><tbody>" + rows + "</tbody></table>" +
        "<script>window.onload=function(){window.print();};</script></body></html>"
      );
      reportWindow.document.close();
    };

    periodButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        state.period = button.dataset.analyticsPeriod || "daily";
        render();
      });
    });

    yearButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        state.year = Number(button.dataset.analyticsYear || state.year);
        render();
      });
    });

    monthSelect && monthSelect.addEventListener("change", function () {
      state.month = Number(monthSelect.value || state.month);
      render();
    });

    downloadButton && downloadButton.addEventListener("click", downloadPdfReport);

    render();
  }

  function initDashboardVisitorAnalytics() {
    const root = document.querySelector("[data-admin-visitor-analytics-root]");
    if (!root) {
      return;
    }

    let initialAnalytics = null;
    try {
      initialAnalytics = JSON.parse(root.dataset.adminVisitorAnalytics || "null");
    } catch (_error) {
      initialAnalytics = null;
    }

    if (!initialAnalytics) {
      return;
    }

    const endpoint = String(root.dataset.adminVisitorAnalyticsEndpoint || "").trim();
    const liveNodes = Array.from(root.querySelectorAll("[data-visitor-live]"));
    const summaryNodes = Array.from(root.querySelectorAll("[data-visitor-summary]"));
    const listNodes = Array.from(root.querySelectorAll("[data-visitor-list]"));
    const rangeButtons = Array.from(root.querySelectorAll("[data-visitor-range]"));
    const customWrap = root.querySelector("[data-visitor-custom-wrap]");
    const startInput = root.querySelector("[data-visitor-start]");
    const endInput = root.querySelector("[data-visitor-end]");
    const applyRangeButton = root.querySelector("[data-visitor-apply-range]");
    const exportButtons = Array.from(root.querySelectorAll("[data-visitor-export]"));
    const rangeLabelNode = root.querySelector("[data-visitor-range-label]");
    const lastUpdatedNode = root.querySelector("[data-visitor-last-updated]");
    const refreshIntervalMs = 60 * 1000;

    const state = {
      range: String(initialAnalytics.range_key || "30days"),
      startDate: String(initialAnalytics.range_start || ""),
      endDate: String(initialAnalytics.range_end || ""),
      analytics: initialAnalytics,
      loading: false
    };

    const summaryFormatters = {
      conversion_rate: function (value) {
        return Number(value || 0).toFixed(1) + "%";
      },
      bounce_rate: function (value) {
        return Number(value || 0).toFixed(1) + "%";
      },
      average_session_duration_label: function (value) {
        return String(value || "0s");
      },
      average_time_per_page_label: function (value) {
        return String(value || "0s");
      }
    };

    const formatSummaryValue = function (key, value) {
      if (summaryFormatters[key]) {
        return summaryFormatters[key](value);
      }
      return String(value == null ? 0 : value);
    };

    const toListArray = function (value) {
      return Array.isArray(value) ? value : [];
    };

    const formatPageLabel = function (value) {
      let label = String(value || "/").trim();
      if (!label) {
        return "/";
      }

      try {
        if (/^https?:\/\//i.test(label)) {
          label = String(new URL(label).pathname || "/");
        }
      } catch (_error) {
      }

      const girffonIndex = label.indexOf("/GirffoN");
      if (girffonIndex > 0) {
        label = label.slice(girffonIndex);
      }

      label = label.replace(/\s+/g, "");
      if (!label.startsWith("/")) {
        label = "/" + label.replace(/^\/+/, "");
      }

      return label || "/";
    };

    const formatPercent = function (value) {
      return Number(value || 0).toFixed(0) + "%";
    };

    const formatVisitCount = function (value, singular, plural) {
      const count = Number(value || 0);
      return count + " " + (count === 1 ? singular : plural);
    };

    const listMax = function (rows) {
      return Math.max.apply(null, rows.map(function (row) {
        return Number(row && row.count || 0);
      }).concat([1]));
    };

    const renderBarList = function (node, rows, emptyMessage) {
      if (!node) {
        return;
      }

      const items = toListArray(rows);
      if (!items.length) {
        node.innerHTML = '<p class="admin-empty">' + escapeHtml(emptyMessage) + '</p>';
        return;
      }

      const maxValue = listMax(items);
      node.innerHTML = items.map(function (item) {
        const count = Number(item && item.count || 0);
        const percentage = count <= 0 ? 0 : Math.round((count / maxValue) * 100);
        const width = count <= 0 ? 0 : Math.max(8, percentage);
        return '<div class="admin-visitor-bar-row">' +
          '<span class="admin-visitor-bar-label">' + escapeHtml(String(item && item.label || '-')) + '</span>' +
          '<div class="admin-visitor-bar-track"><span class="admin-visitor-bar-fill" style="width:' + width + '%"></span></div>' +
          '<strong class="admin-visitor-bar-value">' + escapeHtml(String(count)) + '</strong>' +
          '<span class="admin-visitor-bar-percent">' + escapeHtml(formatPercent(percentage)) + '</span>' +
          '</div>';
      }).join("");
    };

    const renderRankedList = function (node, rows, emptyMessage, options) {
      if (!node) {
        return;
      }

      const items = toListArray(rows);
      if (!items.length) {
        node.innerHTML = '<p class="admin-empty">' + escapeHtml(emptyMessage) + '</p>';
        return;
      }

      const mode = options && options.mode ? options.mode : "count";
      node.innerHTML = items.map(function (item, index) {
        const label = formatPageLabel(item && item.label || "/");
        let valueHtml = '<strong class="admin-visitor-page-count">' + escapeHtml(formatVisitCount(item && item.count || 0, "visit", "visits")) + '</strong>';
        if (mode === "duration") {
          const exits = Number(item && item.count || 0);
          const averageLabel = String(item && item.average_label || "0s");
          valueHtml = '<strong class="admin-visitor-page-count">' + escapeHtml(averageLabel + ' avg') + '</strong><span class="admin-visitor-page-meta">' + escapeHtml(formatVisitCount(exits, "exit", "exits")) + '</span>';
        } else if (mode === "keyword") {
          valueHtml = '<strong class="admin-visitor-page-count">' + escapeHtml(formatVisitCount(item && item.count || 0, "hit", "hits")) + '</strong>';
        }

        return '<div class="admin-visitor-page-item">' +
          '<span class="admin-visitor-page-path" title="' + escapeHtml(label) + '">' + escapeHtml(label) + '</span>' +
          '<span class="admin-visitor-page-value-wrap">' + valueHtml + '</span>' +
          '</div>';
      }).join("");
    };

    const renderLists = function (analytics) {
      listNodes.forEach(function (node) {
        const key = String(node.dataset.visitorList || "");
        switch (key) {
          case "countries":
          case "referrers":
          case "browsers":
          case "devices":
            renderBarList(node, analytics[key], "No analytics captured yet.");
            break;
          case "page_durations":
            renderRankedList(node, analytics[key], "No page duration analytics yet.", { mode: "duration" });
            break;
          case "keywords":
            renderRankedList(node, analytics[key], "No search keywords available for this range.", { mode: "keyword" });
            break;
          default:
            renderRankedList(node, analytics[key], "No analytics captured yet.");
            break;
        }
      });
    };

    const syncControls = function () {
      rangeButtons.forEach(function (button) {
        button.classList.toggle("is-active", button.dataset.visitorRange === state.range);
      });
      if (customWrap) {
        customWrap.hidden = state.range !== "custom";
      }
      if (startInput) {
        startInput.value = state.startDate;
      }
      if (endInput) {
        endInput.value = state.endDate;
      }
    };

    const render = function () {
      const analytics = state.analytics || initialAnalytics;

      liveNodes.forEach(function (node) {
        const key = String(node.dataset.visitorLive || "");
        node.textContent = formatSummaryValue(key, analytics[key]);
      });
      summaryNodes.forEach(function (node) {
        const key = String(node.dataset.visitorSummary || "");
        node.textContent = formatSummaryValue(key, analytics[key]);
      });

      if (rangeLabelNode) {
        rangeLabelNode.textContent = String(analytics.range_label || "Last 30 Days");
      }
      if (lastUpdatedNode) {
        const generatedAt = String(analytics.generated_at || "");
        const renderedTime = generatedAt ? new Date(generatedAt).toLocaleString() : new Date().toLocaleString();
        lastUpdatedNode.textContent = "Updated " + renderedTime;
      }

      renderLists(analytics);
      syncControls();
    };

    const buildRequestUrl = function () {
      const url = new URL(endpoint, window.location.origin);
      url.searchParams.set("range", state.range);
      if (state.range === "custom") {
        if (state.startDate) {
          url.searchParams.set("start_date", state.startDate);
        }
        if (state.endDate) {
          url.searchParams.set("end_date", state.endDate);
        }
      }
      return url.toString();
    };

    const loadAnalytics = function () {
      if (!endpoint || state.loading) {
        return Promise.resolve();
      }

      state.loading = true;
      root.classList.add("is-loading");

      return fetch(buildRequestUrl(), {
        method: "GET",
        credentials: "same-origin",
        cache: "no-store"
      }).then(function (response) {
        return response.json().then(function (payload) {
          if (!response.ok || !payload || !payload.ok || !payload.analytics) {
            throw new Error("Unable to load visitor analytics.");
          }
          state.analytics = payload.analytics;
          render();
        });
      }).catch(function () {
        render();
      }).finally(function () {
        state.loading = false;
        root.classList.remove("is-loading");
      });
    };

    const buildCsvRows = function (analytics) {
      const rows = [
        ["Section", "Label", "Value", "Extra"]
      ];

      [
        ["Live", "Online", analytics.online, ""],
        ["Live", "Today", analytics.today, ""],
        ["Live", "This Week", analytics.week, ""],
        ["Live", "This Month", analytics.month, ""],
        ["Range", analytics.range_label, analytics.visitors, "visitors"],
        ["Range", "Conversion Rate", analytics.conversion_rate, "%"],
        ["Range", "Bounce Rate", analytics.bounce_rate, "%"],
        ["Range", "Average Session", analytics.average_session_duration_label, ""],
        ["Range", "Average Time Per Page", analytics.average_time_per_page_label, ""],
        ["Range", "Completed Orders", analytics.completed_orders, ""]
      ].forEach(function (row) {
        rows.push(row);
      });

      ["countries", "referrers", "browsers", "devices", "pages", "landing_pages", "exit_pages", "keywords"].forEach(function (sectionKey) {
        toListArray(analytics[sectionKey]).forEach(function (item) {
          rows.push([sectionKey, item.label || "", item.count || 0, ""]);
        });
      });

      toListArray(analytics.page_durations).forEach(function (item) {
        rows.push(["page_durations", item.label || "", item.average_label || "0s", String(item.count || 0) + " exits"]);
      });

      return rows;
    };

    const exportCsv = function () {
      const rows = buildCsvRows(state.analytics || initialAnalytics);
      const csv = rows.map(function (row) {
        return row.map(function (cell) {
          const value = String(cell == null ? "" : cell).replace(/"/g, '""');
          return '"' + value + '"';
        }).join(",");
      }).join("\r\n");

      const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = "girffon-visitor-analytics-" + String((state.analytics || initialAnalytics).range_key || "range") + ".csv";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    };

    const exportPdf = function () {
      const analytics = state.analytics || initialAnalytics;
      const reportWindow = window.open("", "_blank", "width=1180,height=860");
      if (!reportWindow) {
        return;
      }

      const summaryCards = [
        ["Range", analytics.range_label || "Last 30 Days"],
        ["Visitors", analytics.visitors || 0],
        ["Conversion", formatSummaryValue("conversion_rate", analytics.conversion_rate)],
        ["Avg Time/Page", analytics.average_time_per_page_label || "0s"],
        ["Bounce", formatSummaryValue("bounce_rate", analytics.bounce_rate)],
        ["Completed Orders", analytics.completed_orders || 0]
      ].map(function (item) {
        return "<div class='card'><span>" + escapeHtml(item[0]) + "</span><strong>" + escapeHtml(String(item[1])) + "</strong></div>";
      }).join("");

      const tableSections = [
        ["Top Pages", analytics.pages || []],
        ["Landing Pages", analytics.landing_pages || []],
        ["Exit Pages", analytics.exit_pages || []],
        ["Referrers", analytics.referrers || []],
        ["Countries", analytics.countries || []],
        ["Keywords", analytics.keywords || []]
      ].map(function (section) {
        const rows = toListArray(section[1]).map(function (item) {
          return "<tr><td>" + escapeHtml(String(item.label || "-")) + "</td><td>" + escapeHtml(String(item.count || 0)) + "</td></tr>";
        }).join("");
        return "<section><h2>" + escapeHtml(section[0]) + "</h2><table><thead><tr><th>Label</th><th>Count</th></tr></thead><tbody>" + rows + "</tbody></table></section>";
      }).join("");

      reportWindow.document.open();
      reportWindow.document.write(
        "<!DOCTYPE html><html><head><title>Visitor Analytics Report</title><style>body{font-family:Georgia,serif;padding:32px;color:#2b241b}h1{margin:0 0 12px;font-size:30px}h2{margin:28px 0 10px;font-size:18px}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0 24px}.card{border:1px solid #e7d7ad;border-radius:16px;padding:14px;background:#fffaf0}.card span{display:block;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#8a7753}.card strong{display:block;margin-top:8px;font-size:22px}table{width:100%;border-collapse:collapse}th,td{padding:10px 12px;border-bottom:1px solid #eadfca;text-align:left}th{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#6b5a3b}@media print{button{display:none}}</style></head><body>" +
        "<h1>GIRFFON Visitor Analytics</h1>" +
        "<p>Generated for " + escapeHtml(String(analytics.range_label || "Last 30 Days")) + ".</p>" +
        "<div class='summary'>" + summaryCards + "</div>" +
        tableSections +
        "<script>window.onload=function(){window.print();};</script></body></html>"
      );
      reportWindow.document.close();
    };

    rangeButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        state.range = String(button.dataset.visitorRange || "30days");
        syncControls();
        if (state.range !== "custom") {
          loadAnalytics();
        }
      });
    });

    startInput && startInput.addEventListener("change", function () {
      state.startDate = String(startInput.value || "");
    });
    endInput && endInput.addEventListener("change", function () {
      state.endDate = String(endInput.value || "");
    });

    applyRangeButton && applyRangeButton.addEventListener("click", function () {
      state.range = "custom";
      state.startDate = String(startInput && startInput.value || "");
      state.endDate = String(endInput && endInput.value || "");
      loadAnalytics();
    });

    exportButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        const type = String(button.dataset.visitorExport || "csv");
        if (type === "pdf") {
          exportPdf();
          return;
        }
        exportCsv();
      });
    });

    render();
    window.setInterval(loadAnalytics, refreshIntervalMs);
  }

  function initDashboardWeatherWidget() {
    const widget = document.querySelector("[data-admin-weather-widget]");
    if (!widget) {
      return;
    }

    const body = document.body;
    const defaultCity = String(body.dataset.adminWeatherCity || "Milan").trim() || "Milan";
    const defaultCountry = String(body.dataset.adminWeatherCountry || "Italy").trim() || "Italy";
    const conditionNode = widget.querySelector("[data-admin-weather-condition]");
    const badgeNode = widget.querySelector("[data-admin-weather-badge]");
    const iconNode = widget.querySelector("[data-admin-weather-icon]");
    const labelNode = widget.querySelector("[data-admin-weather-label]");
    const tempNode = widget.querySelector("[data-admin-weather-temp]");
    const windNode = widget.querySelector("[data-admin-weather-wind]");
    const cityNode = widget.querySelector("[data-admin-weather-city]");
    const countryNode = widget.querySelector("[data-admin-weather-country]");
    const forecastItems = Array.from(widget.querySelectorAll("[data-admin-weather-forecast-item]"));
    const regionSelect = widget.querySelector("[data-admin-weather-region]");
    const cityInput = widget.querySelector("[data-admin-weather-city-input]");
    const applyButton = widget.querySelector("[data-admin-weather-apply]");
    const clearButton = widget.querySelector("[data-admin-weather-clear]");
    const weatherStorageKey = "girffon_admin_dashboard_weather";
    const regionDefaults = {
      "Italy": { country: "Italy", city: "Milan" },
      "Iran": { country: "Iran", city: "Tehran" },
      "United States": { country: "United States", city: "New York" },
      "France": { country: "France", city: "Paris" },
      "Germany": { country: "Germany", city: "Berlin" },
      "Europe": { country: "Europe", city: "Brussels" },
      "Americas": { country: "Americas", city: "New York" },
      "Asia": { country: "Asia", city: "Tokyo" },
      "World": { country: "World", city: "London" }
    };

    const readSavedLocation = function () {
      try {
        return JSON.parse(localStorage.getItem(weatherStorageKey) || "null");
      } catch (_error) {
        return null;
      }
    };

    const writeSavedLocation = function (value) {
      try {
        if (!value) {
          localStorage.removeItem(weatherStorageKey);
          return;
        }
        localStorage.setItem(weatherStorageKey, JSON.stringify(value));
      } catch (_error) {
      }
    };

    let activeLocation = readSavedLocation() || {
      country: defaultCountry,
      city: defaultCity
    };

    const syncWeatherLabels = function () {
      if (cityNode) {
        cityNode.textContent = activeLocation.city || defaultCity;
      }
      if (countryNode) {
        countryNode.textContent = activeLocation.country || defaultCountry;
      }
      if (cityInput) {
        cityInput.value = activeLocation.city || defaultCity;
      }
      if (regionSelect) {
        const nextCountry = activeLocation.country || defaultCountry;
        regionSelect.value = Array.from(regionSelect.options).some(function (option) {
          return option.value === nextCountry;
        }) ? nextCountry : defaultCountry;
      }
    };

    const weatherCodes = {
      0: "Clear sky",
      1: "Mainly clear",
      2: "Partly cloudy",
      3: "Overcast",
      45: "Fog",
      48: "Rime fog",
      51: "Light drizzle",
      53: "Drizzle",
      55: "Dense drizzle",
      61: "Light rain",
      63: "Rain",
      65: "Heavy rain",
      71: "Light snow",
      73: "Snow",
      75: "Heavy snow",
      80: "Rain showers",
      81: "Showers",
      82: "Heavy showers",
      95: "Thunderstorm"
    };

    const weatherKinds = {
      clear: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7"></path></svg>',
        label: "Sunny"
      },
      cloudy: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 18.5h9.2a3.3 3.3 0 0 0 .3-6.6 5.2 5.2 0 0 0-9.9-1.5A4 4 0 0 0 7.5 18.5Z"></path></svg>',
        label: "Cloudy"
      },
      fog: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9.5h14"></path><path d="M3.5 13h17"></path><path d="M6.5 16.5h11"></path></svg>',
        label: "Foggy"
      },
      rain: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 14.5h9.2a3.3 3.3 0 0 0 .3-6.6 5.2 5.2 0 0 0-9.9-1.5A4 4 0 0 0 7.5 14.5Z"></path><path d="M9 17.5l-.8 2"></path><path d="M13 17.5l-.8 2"></path><path d="M17 17.5l-.8 2"></path></svg>',
        label: "Rainy"
      },
      snow: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 13.5h9.2a3.3 3.3 0 0 0 .3-6.6 5.2 5.2 0 0 0-9.9-1.5A4 4 0 0 0 7.5 13.5Z"></path><path d="M9 17.5h0"></path><path d="M12 17v3"></path><path d="M10.5 18.5h3"></path><path d="M10.9 16.9l2.2 2.2"></path><path d="M13.1 16.9l-2.2 2.2"></path><path d="M17 17v3"></path><path d="M15.5 18.5h3"></path><path d="M15.9 16.9l2.2 2.2"></path><path d="M18.1 16.9l-2.2 2.2"></path></svg>',
        label: "Snow"
      },
      storm: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 13.5h9.2a3.3 3.3 0 0 0 .3-6.6 5.2 5.2 0 0 0-9.9-1.5A4 4 0 0 0 7.5 13.5Z"></path><path d="M12.8 14l-2.2 4h2l-1.2 4 4-5h-2.2l1.6-3Z"></path></svg>',
        label: "Storm"
      },
      neutral: {
        icon: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="1.6"></circle><path d="M12 4v2.2M12 17.8V20M4 12h2.2M17.8 12H20M6.4 6.4l1.5 1.5M16.1 16.1l1.5 1.5M6.4 17.6l1.5-1.5M16.1 7.9l1.5-1.5"></path></svg>',
        label: "Loading"
      }
    };

    const resolveWeatherKind = function (weatherCode) {
      if (weatherCode === 0 || weatherCode === 1) {
        return "clear";
      }
      if (weatherCode === 2 || weatherCode === 3) {
        return "cloudy";
      }
      if (weatherCode === 45 || weatherCode === 48) {
        return "fog";
      }
      if ((weatherCode >= 51 && weatherCode <= 65) || (weatherCode >= 80 && weatherCode <= 82)) {
        return "rain";
      }
      if (weatherCode >= 71 && weatherCode <= 75) {
        return "snow";
      }
      if (weatherCode >= 95) {
        return "storm";
      }
      return "cloudy";
    };

    const getWeatherVisual = function (weatherCode) {
      const kindKey = typeof weatherCode === "number" ? resolveWeatherKind(weatherCode) : "neutral";
      return {
        key: kindKey,
        data: weatherKinds[kindKey] || weatherKinds.neutral
      };
    };

    const setWeatherState = function (temperature, condition, wind, weatherCode) {
      if (tempNode) {
        tempNode.textContent = temperature;
      }
      if (conditionNode) {
        conditionNode.textContent = condition;
      }
      if (windNode) {
        windNode.textContent = wind;
      }

      const weatherVisual = getWeatherVisual(weatherCode);
      const kindKey = weatherVisual.key;
      const kind = weatherVisual.data;
      if (badgeNode) {
        badgeNode.dataset.weatherKind = kindKey;
      }
      if (iconNode) {
        iconNode.innerHTML = kind.icon;
      }
      if (labelNode) {
        labelNode.textContent = kind.label;
      }
    };

    const setForecastState = function (forecastDays) {
      const labels = ["Today", "Tomorrow", "Day After"];
      forecastItems.forEach(function (item, index) {
        const dayData = Array.isArray(forecastDays) ? forecastDays[index] : null;
        const dayNode = item.querySelector(".admin-weather-forecast-day");
        const iconForecastNode = item.querySelector(".admin-weather-forecast-icon");
        const tempForecastNode = item.querySelector(".admin-weather-forecast-temp");
        const labelForecastNode = item.querySelector(".admin-weather-forecast-label");
        const weatherVisual = dayData ? getWeatherVisual(dayData.weatherCode) : getWeatherVisual();

        if (dayNode) {
          dayNode.textContent = labels[index] || "Forecast";
        }
        if (iconForecastNode) {
          iconForecastNode.innerHTML = weatherVisual.data.icon;
        }
        if (tempForecastNode) {
          tempForecastNode.textContent = dayData ? dayData.temperature : "-- / --";
        }
        if (labelForecastNode) {
          labelForecastNode.textContent = dayData ? dayData.label : "Loading";
        }
        item.dataset.weatherKind = weatherVisual.key;
      });
    };

    const loadWeather = function () {
      const city = String(activeLocation.city || defaultCity).trim() || defaultCity;
      syncWeatherLabels();
      setWeatherState("--", "Loading live weather...", "--");
      setForecastState(null);

      fetch("https://geocoding-api.open-meteo.com/v1/search?name=" + encodeURIComponent(city) + "&count=1&language=en&format=json")
        .then(function (response) {
          return response.json();
        })
        .then(function (geoPayload) {
          const result = geoPayload && Array.isArray(geoPayload.results) ? geoPayload.results[0] : null;
          if (!result) {
            throw new Error("City not found");
          }

          return fetch(
            "https://api.open-meteo.com/v1/forecast?latitude=" + encodeURIComponent(result.latitude) +
            "&longitude=" + encodeURIComponent(result.longitude) +
            "&current=temperature_2m,weather_code,wind_speed_10m" +
            "&daily=weather_code,temperature_2m_max,temperature_2m_min&forecast_days=3&timezone=auto"
          );
        })
        .then(function (response) {
          return response.json();
        })
        .then(function (weatherPayload) {
          const current = weatherPayload && weatherPayload.current ? weatherPayload.current : null;
          if (!current) {
            throw new Error("Weather unavailable");
          }

          const temperature = typeof current.temperature_2m === "number" ? current.temperature_2m.toFixed(1) + "°C" : "--";
          const condition = weatherCodes[current.weather_code] || "Live weather";
          const wind = typeof current.wind_speed_10m === "number" ? current.wind_speed_10m.toFixed(0) + " km/h" : "--";
          setWeatherState(temperature, condition, wind, Number(current.weather_code));

          const daily = weatherPayload && weatherPayload.daily ? weatherPayload.daily : null;
          const forecastDays = daily && Array.isArray(daily.weather_code) ? daily.weather_code.slice(0, 3).map(function (weatherCode, index) {
            const maxTemp = Array.isArray(daily.temperature_2m_max) ? daily.temperature_2m_max[index] : null;
            const minTemp = Array.isArray(daily.temperature_2m_min) ? daily.temperature_2m_min[index] : null;
            const maxText = typeof maxTemp === "number" ? Math.round(maxTemp) + "°" : "--";
            const minText = typeof minTemp === "number" ? Math.round(minTemp) + "°" : "--";

            return {
              weatherCode: Number(weatherCode),
              temperature: maxText + " / " + minText,
              label: weatherCodes[weatherCode] || "Forecast"
            };
          }) : [];
          setForecastState(forecastDays);
        })
        .catch(function () {
          setWeatherState("--", "Weather unavailable right now.", "--");
          setForecastState([]);
        });
    };

    regionSelect && regionSelect.addEventListener("change", function () {
      const selected = regionDefaults[regionSelect.value] || { city: defaultCity };
      if (cityInput && !cityInput.value.trim()) {
        cityInput.value = selected.city;
      }
    });

    applyButton && applyButton.addEventListener("click", function () {
      const selectedRegion = regionSelect ? regionSelect.value : defaultCountry;
      const fallback = regionDefaults[selectedRegion] || { country: selectedRegion, city: defaultCity };
      activeLocation = {
        country: selectedRegion || fallback.country || defaultCountry,
        city: cityInput && cityInput.value.trim() ? cityInput.value.trim() : fallback.city
      };
      writeSavedLocation(activeLocation);
      loadWeather();
    });

    clearButton && clearButton.addEventListener("click", function () {
      activeLocation = {
        country: defaultCountry,
        city: defaultCity
      };
      writeSavedLocation(null);
      loadWeather();
    });

    syncWeatherLabels();
    loadWeather();
  }

  function initDashboardWorldClock() {
    const widget = document.querySelector("[data-admin-world-clock-widget]");
    if (!widget) {
      return;
    }

    const select = widget.querySelector("[data-admin-world-clock-zone]");
    const timeNode = widget.querySelector("[data-admin-world-clock-time]");
    const dateNode = widget.querySelector("[data-admin-world-clock-date]");
    const labelNode = widget.querySelector("[data-admin-world-clock-label]");
    const offsetNode = widget.querySelector("[data-admin-world-clock-offset]");
    const statusNode = widget.querySelector("[data-admin-world-clock-status]");
    const quickGrid = widget.querySelector("[data-admin-world-clock-grid]");
    const storageKey = "girffon_admin_world_clock_zone";

    const featuredZones = [
      { label: "New York", zone: "America/New_York" },
      { label: "Toronto", zone: "America/Toronto" },
      { label: "Rome", zone: "Europe/Rome" },
      { label: "Berlin", zone: "Europe/Berlin" },
      { label: "Tehran", zone: "Asia/Tehran" },
      { label: "Tokyo", zone: "Asia/Tokyo" },
      { label: "Sydney", zone: "Australia/Sydney" },
      { label: "UTC", zone: "UTC" }
    ];

    const formatInZone = function (zone, options) {
      return new Intl.DateTimeFormat("en-GB", Object.assign({ timeZone: zone }, options)).format(new Date());
    };

    const getHourInZone = function (zone) {
      const parts = new Intl.DateTimeFormat("en-GB", {
        hour: "2-digit",
        hour12: false,
        timeZone: zone
      }).formatToParts(new Date());
      const hourPart = parts.find(function (part) {
        return part.type === "hour";
      });
      return Number(hourPart ? hourPart.value : 0);
    };

    const getOffsetLabel = function (zone) {
      try {
        const parts = new Intl.DateTimeFormat("en-US", {
          timeZone: zone,
          timeZoneName: "shortOffset",
          hour: "2-digit"
        }).formatToParts(new Date());
        const zonePart = parts.find(function (part) {
          return part.type === "timeZoneName";
        });
        return zonePart ? zonePart.value.replace("GMT", "UTC") : "UTC";
      } catch (_error) {
        return "UTC";
      }
    };

    const getDayPhase = function (zone) {
      const hour = getHourInZone(zone);
      if (hour >= 6 && hour < 12) {
        return "Morning";
      }
      if (hour >= 12 && hour < 18) {
        return "Afternoon";
      }
      if (hour >= 18 && hour < 22) {
        return "Evening";
      }
      return "Night";
    };

    const renderQuickGrid = function () {
      if (!quickGrid) {
        return;
      }

      quickGrid.innerHTML = featuredZones.map(function (item) {
        return '<div class="admin-world-clock-card">' +
          '<span class="admin-world-clock-card-label">' + escapeHtml(item.label) + '</span>' +
          '<strong class="admin-world-clock-card-time">' + escapeHtml(formatInZone(item.zone, { hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false })) + '</strong>' +
          '<small class="admin-world-clock-card-meta">' + escapeHtml(getOffsetLabel(item.zone) + ' • ' + getDayPhase(item.zone)) + '</small>' +
          '</div>';
      }).join("");
    };

    const formatClock = function () {
      const zone = select && select.value ? select.value : "UTC";
      const label = select && select.selectedOptions && select.selectedOptions[0] ? select.selectedOptions[0].textContent : zone;

      if (labelNode) {
        labelNode.textContent = label;
      }

      if (timeNode) {
        timeNode.textContent = formatInZone(zone, {
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
          hour12: false
        });
      }

      if (dateNode) {
        dateNode.textContent = new Intl.DateTimeFormat("en-GB", {
          weekday: "long",
          day: "2-digit",
          month: "long",
          year: "numeric",
          timeZone: zone
        }).format(new Date());
      }

      if (offsetNode) {
        offsetNode.textContent = getOffsetLabel(zone);
      }

      if (statusNode) {
        statusNode.textContent = getDayPhase(zone);
      }

      renderQuickGrid();
    };

    try {
      const savedZone = localStorage.getItem(storageKey);
      if (savedZone && select && Array.from(select.options).some(function (option) { return option.value === savedZone; })) {
        select.value = savedZone;
      }
    } catch (_error) {
    }

    select && select.addEventListener("change", function () {
      try {
        localStorage.setItem(storageKey, select.value);
      } catch (_error) {
      }
      formatClock();
    });

    formatClock();
    window.setInterval(formatClock, 1000);
  }

  function initProductsPage() {
    if (document.body.dataset.adminProductsSource === "database") {
      return;
    }
    renderProductsTable();
    const form = document.getElementById("adminProductsForm");
    const status = document.getElementById("adminProductsStatus");

    if (!form || !status) {
      return;
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const formData = new FormData(form);
      const products = readList(STORAGE_KEYS.products);
      products.unshift({
        id: uid(),
        name: String(formData.get("name") || "").trim(),
        sku: String(formData.get("sku") || "").trim(),
        price: Number(formData.get("price") || 0),
        category: String(formData.get("category") || "").trim(),
        size: String(formData.get("size") || "").trim(),
        color: String(formData.get("color") || "").trim(),
        status: String(formData.get("status") || "Active").trim(),
        imageUrl: String(formData.get("imageUrl") || "").trim(),
        stock: Number(formData.get("stock") || 0)
      });
      writeList(STORAGE_KEYS.products, products);
      form.reset();
      setStatus(status, "Product saved to localStorage.", true);
      renderProductsTable();
    });
  }

  function initOrdersPage() {
    const imageInput = document.getElementById("adminOrderImage");
    const preview = document.getElementById("adminOrderImagePreview");
    const form = document.getElementById("adminOrdersForm");

    if (!imageInput || !preview || !form) {
      return;
    }

    const renderEmptyPreview = function () {
      preview.innerHTML = "<span>No image selected</span>";
    };

    imageInput.addEventListener("change", function () {
      const [file] = Array.from(imageInput.files || []);
      if (!file) {
        renderEmptyPreview();
        return;
      }

      const objectUrl = URL.createObjectURL(file);
      preview.innerHTML = '<img src="' + objectUrl + '" alt="Order image preview">';
    });

    form.addEventListener("reset", function () {
      window.setTimeout(renderEmptyPreview, 0);
    });

    renderEmptyPreview();
  }

  function initInvoicesPage() {
    return;
  }

  function initMessagesPage() {
    document.body.dataset.adminMessagesReady = "initializing";
    initMessageFilters();
    initMessageModal();
    document.body.dataset.adminMessagesReady = "true";

    if (document.body.dataset.adminMessagesSource === "database") {
      return;
    }
    renderMessagesTable();
    const form = document.getElementById("adminMessagesForm");
    const status = document.getElementById("adminMessagesStatus");

    if (!form || !status) {
      return;
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const formData = new FormData(form);
      const messages = readList(STORAGE_KEYS.messages);
      messages.unshift({
        id: uid(),
        customerName: String(formData.get("customerName") || "").trim(),
        email: String(formData.get("email") || "").trim(),
        message: String(formData.get("message") || "").trim(),
        status: String(formData.get("status") || "Unread").trim(),
        createdAt: todayString()
      });
      writeList(STORAGE_KEYS.messages, messages);
      form.reset();
      setStatus(status, "Message saved to localStorage.", true);
      renderMessagesTable();
    });
  }

  function initMessageFilters() {
    const form = document.getElementById("adminMessagesSearchForm");
    const tableBody = document.getElementById("adminMessagesTableBody");
    const searchInput = document.getElementById("adminMessageSearchInput");
    const searchButton = document.getElementById("adminMessageSearchButton");
    const resetButton = document.getElementById("adminMessageSearchReset");
    const statusFilter = document.getElementById("adminMessageStatusFilter");
    const emptyState = document.getElementById("adminMessagesFilterStatus");
    const totalNode = document.getElementById("adminMessagesTotalCount");
    const unreadNode = document.getElementById("adminMessagesUnreadCount");
    const readNode = document.getElementById("adminMessagesReadCount");

    if (!tableBody || !searchInput || !statusFilter) {
      return;
    }

    const applyFilters = function () {
      const query = String(searchInput.value || "").trim().toLowerCase();
      const status = String(statusFilter.value || "all").trim().toLowerCase();
      const rows = Array.from(tableBody.querySelectorAll("tr[data-message-search]"));

      if (!rows.length) {
        if (emptyState) {
          emptyState.hidden = true;
        }
        return;
      }

      let visibleTotal = 0;
      let visibleUnread = 0;
      let visibleRead = 0;

      rows.forEach(function (row) {
        const rowSearch = String(row.dataset.messageSearch || "");
        const rowStatus = String(row.dataset.messageStatus || "unread").toLowerCase();
        const matchesQuery = !query || rowSearch.includes(query);
        const matchesStatus = status === "all" || rowStatus === status;
        const visible = matchesQuery && matchesStatus;

        row.classList.toggle("admin-message-row-hidden", !visible);

        if (!visible) {
          return;
        }

        visibleTotal += 1;
        if (rowStatus === "read") {
          visibleRead += 1;
        } else {
          visibleUnread += 1;
        }
      });

      if (totalNode) {
        totalNode.textContent = String(visibleTotal);
      }
      if (unreadNode) {
        unreadNode.textContent = String(visibleUnread);
      }
      if (readNode) {
        readNode.textContent = String(visibleRead);
      }
      if (emptyState) {
        const activeParts = [];
        if (query) {
          activeParts.push('search: "' + query + '"');
        }
        if (status !== "all") {
          activeParts.push("status: " + status);
        }

        if (visibleTotal === 0) {
          emptyState.textContent = activeParts.length
            ? "No messages found for " + activeParts.join(" | ") + "."
            : "No messages found.";
        } else if (activeParts.length) {
          emptyState.textContent = "Showing " + visibleTotal + " of " + rows.length + " messages for " + activeParts.join(" | ") + ".";
        } else {
          emptyState.textContent = "Showing all " + rows.length + " messages.";
        }

        emptyState.hidden = false;
        emptyState.classList.toggle("is-empty", visibleTotal === 0);
      }
    };

    if (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        applyFilters();
      });
    }

    searchInput.addEventListener("input", applyFilters);
    searchInput.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        applyFilters();
      }
    });
    if (searchButton) {
      searchButton.addEventListener("click", applyFilters);
    }
    if (resetButton) {
      resetButton.addEventListener("click", function () {
        searchInput.value = "";
        statusFilter.value = "all";
        applyFilters();
      });
    }
    statusFilter.addEventListener("change", applyFilters);
    applyFilters();
  }

  function initMessageModal() {
    const modal = document.getElementById("adminMessageModal");
    const closeButton = document.getElementById("adminMessageModalClose");
    const overlay = document.getElementById("adminMessageModalOverlay");
    const titleNode = document.getElementById("adminMessageModalTitle");

    if (!modal || !closeButton || !overlay) {
      return;
    }

    const detailFields = {
      name: document.getElementById("adminModalMessageName"),
      email: document.getElementById("adminModalMessageEmail"),
      phone: document.getElementById("adminModalMessagePhone"),
      country: document.getElementById("adminModalMessageCountry"),
      city: document.getElementById("adminModalMessageCity"),
      address: document.getElementById("adminModalMessageAddress"),
      subject: document.getElementById("adminModalMessageSubject"),
      status: document.getElementById("adminModalMessageStatus"),
      date: document.getElementById("adminModalMessageDate"),
      body: document.getElementById("adminModalMessageBody"),
      emailLink: document.getElementById("adminModalMessageEmailLink"),
      phoneLink: document.getElementById("adminModalMessagePhoneLink")
    };

    const getValue = function (value) {
      const text = String(value || "").trim();
      return text || "-";
    };

    const closeModal = function () {
      modal.hidden = true;
    };

    const openModal = function (row) {
      detailFields.name.textContent = getValue(row.dataset.messageName);
      detailFields.email.textContent = getValue(row.dataset.messageEmail);
      detailFields.phone.textContent = getValue(row.dataset.messagePhone);
      detailFields.country.textContent = getValue(row.dataset.messageCountry);
      detailFields.city.textContent = getValue(row.dataset.messageCity);
      detailFields.address.textContent = getValue(row.dataset.messageAddress);
      detailFields.subject.textContent = getValue(row.dataset.messageSubject);
      detailFields.status.textContent = getValue(row.dataset.messageStatus);
      detailFields.date.textContent = getValue(row.dataset.messageDate);
      detailFields.body.textContent = getValue(row.dataset.messageBody);
      detailFields.body.scrollTop = 0;

      if (titleNode) {
        titleNode.textContent = detailFields.name.textContent === "-"
          ? "Customer Message"
          : detailFields.name.textContent + " Message";
      }

      const emailValue = String(row.dataset.messageEmail || "").trim();
      detailFields.emailLink.href = emailValue ? "mailto:" + emailValue : "#";
      detailFields.emailLink.setAttribute("aria-disabled", emailValue ? "false" : "true");
      detailFields.emailLink.classList.toggle("admin-button-disabled", !emailValue);

      const phoneValue = String(row.dataset.messagePhone || "").trim();
      const normalizedPhone = phoneValue.replace(/\s+/g, "");
      detailFields.phoneLink.href = normalizedPhone ? "tel:" + normalizedPhone : "#";
      detailFields.phoneLink.setAttribute("aria-disabled", normalizedPhone ? "false" : "true");
      detailFields.phoneLink.classList.toggle("admin-button-disabled", !normalizedPhone);

      modal.hidden = false;
    };

    document.addEventListener("click", function (event) {
      const viewButton = event.target.closest("[data-message-view]");
      if (viewButton) {
        const row = viewButton.closest("tr[data-message-search]");
        if (row) {
          openModal(row);
        }
        return;
      }

      if (event.target === overlay || event.target === closeButton) {
        closeModal();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && !modal.hidden) {
        closeModal();
      }
    });
  }

  function initSettingsPage() {
    const form = document.getElementById("adminSettingsForm");
    const status = document.getElementById("adminSettingsStatus");

    if (!form || !status) {
      return;
    }

    populateSettingsForm(form, readSettings());

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const formData = new FormData(form);
      const settings = normalizeSettings({
        storeName: String(formData.get("storeName") || "").trim(),
        storeEmail: String(formData.get("storeEmail") || "").trim(),
        country: String(formData.get("country") || "").trim(),
        currency: String(formData.get("currency") || "").trim(),
        taxRate: Number(formData.get("taxRate") || 0),
        shippingCost: Number(formData.get("shippingCost") || 0),
        returnPolicyText: String(formData.get("returnPolicyText") || "").trim(),
        supportEmail: String(formData.get("supportEmail") || "").trim()
      });

      writeSettings(settings);
      populateSettingsForm(form, settings);
      setStatus(status, "Settings saved to localStorage.", true);
    });
  }

  function renderCounts() {
    setText("adminTotalProducts", readList(STORAGE_KEYS.products).length);
    setText("adminUnreadMessages", unreadMessagesCount());
  }

  function renderDashboardLists() {
    const messages = readList(STORAGE_KEYS.messages);
    const products = readList(STORAGE_KEYS.products);
    const lowStockProducts = products
      .filter(function (product) {
        return Number(product.stock) <= 15;
      })
      .sort(function (left, right) {
        return Number(left.stock) - Number(right.stock);
      });

    renderMiniList("adminRecentMessages", messages.slice(0, 4), function (message) {
      return "<div class=\"admin-mini-item\"><span>" + escapeHtml(message.customerName) + "</span><strong>" + escapeHtml(message.status) + "</strong></div>";
    }, "No messages yet.");

    renderMiniList("adminLowStockProducts", lowStockProducts.slice(0, 4), function (product) {
      return "<div class=\"admin-mini-item\"><span>" + escapeHtml(product.name) + "</span><strong>" + escapeHtml(String(product.stock)) + " pcs</strong></div>";
    }, "No products are currently low on stock.");
  }

  function renderProductsTable() {
    const products = readList(STORAGE_KEYS.products);
    const tableBody = document.getElementById("adminProductsTableBody");
    if (!tableBody) {
      return;
    }

    renderCounts();
    tableBody.innerHTML = products.length
      ? products.map(function (product) {
          return "<tr>" +
            "<td><strong>" + escapeHtml(product.name) + "</strong></td>" +
            "<td>" + escapeHtml(product.sku) + "</td>" +
            "<td>" + formatCurrency(product.price) + "</td>" +
            "<td>" + escapeHtml(product.category) + "</td>" +
            "<td>" + escapeHtml(product.size) + "</td>" +
            "<td>" + escapeHtml(product.color) + "</td>" +
            "<td>" + badge(product.status) + "</td>" +
            "<td><a href=\"" + escapeAttribute(product.imageUrl) + "\" target=\"_blank\" rel=\"noopener noreferrer\">View Image</a></td>" +
            "<td>" + escapeHtml(String(product.stock)) + "</td>" +
            "<td>" + actionGroup([
              actionButton("Edit", "edit-product", product.id),
              actionButton("Delete", "delete-product", product.id, "is-danger")
            ]) + "</td>" +
          "</tr>";
        }).join("")
      : emptyRow(10, "No products saved yet.");
  }

  function renderMessagesTable() {
    const messages = readList(STORAGE_KEYS.messages);
    const tableBody = document.getElementById("adminMessagesTableBody");
    if (!tableBody) {
      return;
    }

    renderCounts();
    tableBody.innerHTML = messages.length
      ? messages.map(function (message) {
          return "<tr>" +
            "<td><strong>" + escapeHtml(message.customerName) + "</strong><div>" + escapeHtml(message.email) + "</div></td>" +
            "<td>" + escapeHtml(message.message) + "</td>" +
            "<td>" + badge(message.status) + "</td>" +
            "<td>" + escapeHtml(normalizeDate(message.createdAt) || todayString()) + "</td>" +
            "<td>" + actionGroup([
              actionButton("Reply", "reply-message", message.id),
              actionButton("Mark as Read", "mark-message-read", message.id),
              actionButton("Delete", "delete-message", message.id, "is-danger")
            ]) + "</td>" +
          "</tr>";
        }).join("")
      : emptyRow(5, "No messages saved yet.");
  }

  function bindLogout() {
    const logoutButtons = document.querySelectorAll("[data-admin-logout]");
    logoutButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        clearLegacyAdminAuth();
        window.location.href = "backend/admin/logout.php";
      });
    });
  }

  function bindSettings() {
    const settingsButtons = document.querySelectorAll("[data-admin-settings]");
    settingsButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        const target = String(button.getAttribute("data-admin-settings-target") || "").trim();
        window.location.href = target !== "" ? target : "admin-settings.php";
      });
    });
  }

  function clearLegacyAdminAuth() {
    try {
      localStorage.removeItem("girffon_admin_auth");
    } catch (_error) {
      // Ignore localStorage failures during auth cleanup.
    }
  }

  function bindTableActions() {
    document.addEventListener("click", function (event) {
      const actionNode = event.target.closest("[data-action]");
      if (!actionNode) {
        return;
      }

      const action = actionNode.dataset.action;
      const id = actionNode.dataset.id;

      switch (action) {
        case "edit-product":
          editProduct(id);
          break;
        case "delete-product":
          deleteItem(STORAGE_KEYS.products, id, renderProductsTable, "Delete this product?");
          break;
        case "reply-message":
          replyToMessage(id);
          break;
        case "mark-message-read":
          markMessageRead(id);
          break;
        case "delete-message":
          deleteItem(STORAGE_KEYS.messages, id, renderMessagesTable, "Delete this message?");
          break;
        default:
          break;
      }
    });
  }

  function editProduct(id) {
    const products = readList(STORAGE_KEYS.products);
    const product = findById(products, id);
    if (!product) {
      return;
    }

    const updated = collectPrompts([
      { key: "name", label: "Product name", value: product.name },
      { key: "sku", label: "SKU", value: product.sku },
      { key: "price", label: "Price", value: String(product.price) },
      { key: "category", label: "Category", value: product.category },
      { key: "size", label: "Size", value: product.size },
      { key: "color", label: "Color", value: product.color },
      { key: "status", label: "Status", value: product.status },
      { key: "imageUrl", label: "Image URL", value: product.imageUrl },
      { key: "stock", label: "Stock", value: String(product.stock) }
    ]);

    if (!updated) {
      return;
    }

    replaceItem(STORAGE_KEYS.products, id, function () {
      return {
        id: product.id,
        name: updated.name,
        sku: updated.sku,
        price: Number(updated.price) || 0,
        category: updated.category,
        size: updated.size,
        color: updated.color,
        status: updated.status,
        imageUrl: updated.imageUrl,
        stock: Number(updated.stock) || 0
      };
    });

    renderProductsTable();
  }

  function replyToMessage(id) {
    const message = findById(readList(STORAGE_KEYS.messages), id);
    if (!message) {
      return;
    }

    const subject = encodeURIComponent("Reply from GirffoN Admin");
    const body = encodeURIComponent("Hello " + message.customerName + ",\n\nThank you for your message.\n\n");
    window.location.href = "mailto:" + encodeURIComponent(message.email) + "?subject=" + subject + "&body=" + body;
  }

  function markMessageRead(id) {
    replaceItem(STORAGE_KEYS.messages, id, function (message) {
      return {
        id: message.id,
        customerName: message.customerName,
        email: message.email,
        message: message.message,
        status: "Read",
        createdAt: message.createdAt || todayString()
      };
    });

    renderMessagesTable();
  }

  function deleteItem(key, id, callback, message) {
    if (!window.confirm(message)) {
      return;
    }

    const items = readList(key).filter(function (item) {
      return item.id !== id;
    });
    writeList(key, items);
    callback();
  }

  function replaceItem(key, id, updater) {
    const items = readList(key).map(function (item) {
      return item.id === id ? updater(item) : item;
    });
    writeList(key, items);
  }

  function collectPrompts(fields) {
    const result = {};

    for (let index = 0; index < fields.length; index += 1) {
      const field = fields[index];
      const value = window.prompt(field.label, field.value);

      if (value === null) {
        return null;
      }

      result[field.key] = String(value).trim();
    }

    return result;
  }

  function actionGroup(actions) {
    return "<div class=\"admin-table-actions\">" + actions.join("") + "</div>";
  }

  function actionButton(label, action, id, modifier) {
    const className = modifier ? "admin-action-button " + modifier : "admin-action-button";
    return "<button class=\"" + className + "\" type=\"button\" data-action=\"" + action + "\" data-id=\"" + escapeAttribute(id) + "\" aria-label=\"" + escapeAttribute(label) + "\" title=\"" + escapeAttribute(label) + "\">" + escapeHtml(label) + "</button>";
  }

  function setPageTitle(page) {
    const titleNode = document.getElementById("adminCurrentPage");
    if (titleNode && PAGE_TITLES[page]) {
      titleNode.textContent = PAGE_TITLES[page];
    }
  }

  function seedStore() {
    ensureList(STORAGE_KEYS.products, SEED_DATA.products);
    ensureList(STORAGE_KEYS.messages, SEED_DATA.messages);
    ensureObject(STORAGE_KEYS.settings, DEFAULT_SETTINGS);
  }

  function normalizeStore() {
    writeList(STORAGE_KEYS.products, readList(STORAGE_KEYS.products).map(normalizeProduct));
    writeList(STORAGE_KEYS.messages, readList(STORAGE_KEYS.messages).map(normalizeMessage));
    writeSettings(readSettings());
  }

  function normalizeProduct(product) {
    return {
      id: product.id || uid(),
      name: String(product.name || "Untitled Product"),
      sku: String(product.sku || "GF-NEW-000"),
      price: Number(product.price) || 0,
      category: String(product.category || "Uncategorized"),
      size: String(product.size || "One Size"),
      color: String(product.color || "Gold"),
      status: String(product.status || "Active"),
      imageUrl: String(product.imageUrl || "#"),
      stock: Number(product.stock) || 0
    };
  }


  function normalizeMessage(message) {
    return {
      id: message.id || uid(),
      customerName: String(message.customerName || "Visitor"),
      email: String(message.email || "visitor@example.com"),
      message: String(message.message || "No message content."),
      status: String(message.status || "Unread"),
      createdAt: normalizeDate(message.createdAt) || todayString()
    };
  }

  function ensureList(key, fallback) {
    if (!localStorage.getItem(key)) {
      localStorage.setItem(key, JSON.stringify(fallback));
    }
  }

  function ensureObject(key, fallback) {
    if (!localStorage.getItem(key)) {
      localStorage.setItem(key, JSON.stringify(fallback));
    }
  }

  function readList(key) {
    try {
      const raw = localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function writeList(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
  }

  function readSettings() {
    try {
      const raw = localStorage.getItem(STORAGE_KEYS.settings);
      const parsed = raw ? JSON.parse(raw) : DEFAULT_SETTINGS;
      return normalizeSettings(parsed);
    } catch (_error) {
      return normalizeSettings(DEFAULT_SETTINGS);
    }
  }

  function writeSettings(data) {
    localStorage.setItem(STORAGE_KEYS.settings, JSON.stringify(normalizeSettings(data)));
  }

  function populateSettingsForm(form, settings) {
    setFieldValue(form, "storeName", settings.storeName);
    setFieldValue(form, "storeEmail", settings.storeEmail);
    setFieldValue(form, "country", settings.country);
    setFieldValue(form, "currency", settings.currency);
    setFieldValue(form, "taxRate", settings.taxRate);
    setFieldValue(form, "shippingCost", settings.shippingCost);
    setFieldValue(form, "returnPolicyText", settings.returnPolicyText);
    setFieldValue(form, "supportEmail", settings.supportEmail);
  }

  function setFieldValue(form, name, value) {
    const field = form.elements.namedItem(name);
    if (field) {
      field.value = String(value);
    }
  }

  function normalizeSettings(settings) {
    return {
      storeName: String(settings.storeName || DEFAULT_SETTINGS.storeName).trim(),
      storeEmail: String(settings.storeEmail || DEFAULT_SETTINGS.storeEmail).trim(),
      country: String(settings.country || DEFAULT_SETTINGS.country).trim(),
      currency: String(settings.currency || DEFAULT_SETTINGS.currency).trim(),
      taxRate: numberOrFallback(settings.taxRate, DEFAULT_SETTINGS.taxRate),
      shippingCost: numberOrFallback(settings.shippingCost, DEFAULT_SETTINGS.shippingCost),
      returnPolicyText: String(settings.returnPolicyText || DEFAULT_SETTINGS.returnPolicyText).trim(),
      supportEmail: String(settings.supportEmail || DEFAULT_SETTINGS.supportEmail).trim()
    };
  }

  function numberOrFallback(value, fallback) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function unreadMessagesCount() {
    return readList(STORAGE_KEYS.messages).filter(function (item) {
      return String(item.status).toLowerCase() === "unread";
    }).length;
  }

  function renderMiniList(id, items, formatter, emptyMessage) {
    const node = document.getElementById(id);
    if (!node) {
      return;
    }
    node.innerHTML = items.length ? items.map(formatter).join("") : "<p class=\"admin-empty\">" + escapeHtml(emptyMessage) + "</p>";
  }

  function findById(items, id) {
    return items.find(function (item) {
      return item.id === id;
    });
  }

  function inferShippingStatus(status) {
    const lower = String(status || "").toLowerCase();
    if (lower === "shipped") {
      return "Shipped";
    }
    if (lower === "completed") {
      return "Delivered";
    }
    if (lower === "processing") {
      return "Packed";
    }
    return "Pending";
  }

  function normalizeDate(value) {
    const text = String(value || "").trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(text)) {
      return text;
    }

    const date = new Date(text);
    if (Number.isNaN(date.getTime())) {
      return "";
    }

    return date.toISOString().slice(0, 10);
  }

  function todayString() {
    return new Date().toISOString().slice(0, 10);
  }

  function createOrderNumber() {
    return "ORD-" + Date.now().toString().slice(-6);
  }

  function createInvoiceNumber() {
    return "INV-" + Date.now().toString().slice(-6);
  }

  function setStatus(node, message, success) {
    node.textContent = message;
    node.className = "admin-feedback " + (success ? "is-success" : "is-error");
  }

  function setText(id, value) {
    const node = document.getElementById(id);
    if (node) {
      node.textContent = String(value);
    }
  }

  function emptyRow(columns, message) {
    return "<tr><td colspan=\"" + columns + "\"><p class=\"admin-empty\">" + escapeHtml(message) + "</p></td></tr>";
  }

  function formatCurrency(value) {
    const number = Number(value) || 0;
    return new Intl.NumberFormat("en-GB", {
      style: "currency",
      currency: "EUR",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(number);
  }

  function badge(value) {
    const label = String(value || "Pending");
    const lower = label.toLowerCase();
    let kind = "is-neutral";

    if (["paid", "read", "completed", "delivered", "shipped", "active"].includes(lower)) {
      kind = "is-success";
    } else if (["pending", "processing", "unread", "packed", "draft"].includes(lower)) {
      kind = "is-warning";
    } else if (["cancelled", "overdue", "failed", "archived"].includes(lower)) {
      kind = "is-danger";
    }

    return "<span class=\"admin-badge " + kind + "\">" + escapeHtml(label) + "</span>";
  }

  function uid() {
    return "id-" + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function escapeAttribute(value) {
    return escapeHtml(value);
  }
})();
