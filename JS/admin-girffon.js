(function () {
  const STORAGE_KEYS = {
    products: "girffon_admin_products",
    messages: "girffon_admin_messages",
    settings: "girffon_admin_settings"
  };

  const PAGE_TITLES = {
    dashboard: "Dashboard",
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

    const mobileQuery = window.matchMedia("(max-width: 1024px)");
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
    initDashboardWeatherWidget();
    if (document.body.dataset.adminDashboardSource === "database") {
      return;
    }
    renderCounts();
    renderDashboardLists();
  }

  function initDashboardWeatherWidget() {
    const widget = document.querySelector("[data-admin-weather-widget]");
    if (!widget) {
      return;
    }

    const body = document.body;
    const city = String(body.dataset.adminWeatherCity || "Milan").trim() || "Milan";
    const conditionNode = widget.querySelector("[data-admin-weather-condition]");
    const tempNode = widget.querySelector("[data-admin-weather-temp]");
    const windNode = widget.querySelector("[data-admin-weather-wind]");
    const cityNode = widget.querySelector("[data-admin-weather-city]");

    if (cityNode) {
      cityNode.textContent = city;
    }

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

    const setWeatherState = function (temperature, condition, wind) {
      if (tempNode) {
        tempNode.textContent = temperature;
      }
      if (conditionNode) {
        conditionNode.textContent = condition;
      }
      if (windNode) {
        windNode.textContent = wind;
      }
    };

    setWeatherState("--", "Loading live weather...", "--");

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
          "&current=temperature_2m,weather_code,wind_speed_10m&timezone=auto"
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
        setWeatherState(temperature, condition, wind);
      })
      .catch(function () {
        setWeatherState("--", "Weather unavailable right now.", "--");
      });
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
        window.location.href = "admin-settings.php";
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
