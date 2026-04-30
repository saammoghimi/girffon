(function () {
  const NOTIFICATION_PREFERENCES_URL = '/GirffoN/backend/auth/save-notification-preferences.php';
  const NOTIFICATION_PREFERENCE_KEYS = [
    'promotionalEmails',
    'catalogEmails',
    'birthdayDiscountEmails',
    'orderUpdates',
    'smsNotifications',
    'twoFactorEnabled'
  ];

  function formatDate(value, options) {
    if (!value) {
      return "-";
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      return String(value);
    }

    return parsed.toLocaleDateString("en-GB", options || {
      day: "2-digit",
      month: "short",
      year: "numeric"
    });
  }

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function setFieldValue(id, value) {
    const field = document.getElementById(id);
    if (field) {
      field.value = value || "";
    }
  }

  function setText(target, value) {
    if (target) {
      target.textContent = value;
    }
  }

  function normalizeNotificationPreferences(preferences) {
    const normalized = {};

    NOTIFICATION_PREFERENCE_KEYS.forEach(function (key) {
      if (Object.prototype.hasOwnProperty.call(preferences || {}, key)) {
        normalized[key] = Boolean(preferences[key]);
      }
    });

    return normalized;
  }

  function setToggleCheckedState(toggle, isChecked) {
    if (!toggle) {
      return;
    }

    toggle.setAttribute('aria-checked', isChecked ? 'true' : 'false');
  }

  function applyNotificationPreferences(preferences) {
    document.querySelectorAll('.gf-account-toggle[data-gf-pref-key]').forEach(function (toggle) {
      const key = toggle.getAttribute('data-gf-pref-key') || '';
      const fallbackValue = toggle.getAttribute('data-default-state') !== 'false';
      const nextValue = Object.prototype.hasOwnProperty.call(preferences || {}, key)
        ? Boolean(preferences[key])
        : fallbackValue;

      setToggleCheckedState(toggle, nextValue);
    });
  }

  async function saveNotificationPreferences(preferences) {
    const response = await fetch(NOTIFICATION_PREFERENCES_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        preferences: preferences
      })
    });

    const payload = await response.json();
    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || 'Unable to save notification preferences.');
    }

    return normalizeNotificationPreferences(payload.preferences || preferences);
  }

  function calculateProfileCompletion(user) {
    const fields = [
      user.first_name,
      user.last_name,
      user.email,
      user.phone,
      user.country,
      user.city,
      user.address
    ];

    const completed = fields.filter(function (item) {
      return String(item || "").trim() !== "";
    }).length;

    return Math.round((completed / fields.length) * 100);
  }

  function statusClass(status) {
    const normalized = String(status || "").toLowerCase();
    if (normalized === "delivered" || normalized === "paid") {
      return "is-delivered";
    }

    return "is-progress";
  }

  function applyUserData(user, orders) {
    const fullName = [user.first_name, user.last_name].filter(Boolean).join(" ").trim() || user.username || "GirffoN Member";
    const headerName = document.querySelector(".gf-account-profile-meta h2");
    const headerEmail = document.querySelector('.gf-account-profile-link[href^="mailto:"]');
    const headerPhone = document.querySelector('.gf-account-profile-link[href^="tel:"]');
    const memberSince = document.querySelector(".gf-account-member-since");
    const progressStrong = document.querySelector("[data-gf-profile-progress]");
    const progressBar = document.querySelector(".gf-account-progress span");
    const accountStats = document.querySelectorAll(".gf-account-stat strong");
    const summaryCounts = document.querySelectorAll(".gf-account-summary-grid strong");
    const profileMiniItems = document.querySelectorAll(".gf-account-profile-details-grid .gf-account-mini-item strong");

    setText(headerName, fullName);
    if (headerEmail) {
      headerEmail.textContent = user.email || "-";
      headerEmail.href = user.email ? "mailto:" + user.email : "#";
    }
    if (headerPhone) {
      const phoneValue = user.phone || "Add your phone number";
      headerPhone.textContent = phoneValue;
      headerPhone.href = user.phone ? "tel:" + user.phone : "#";
    }
    setText(memberSince, user.created_at ? "Member since " + formatDate(user.created_at, { month: "long", year: "numeric" }) : "Account details update here after sign-in.");

    const completion = calculateProfileCompletion(user);
    setText(progressStrong, completion + "%");
    if (progressBar) {
      progressBar.style.width = completion + "%";
    }

    if (accountStats[0]) accountStats[0].textContent = String(orders.length);
    if (accountStats[1]) accountStats[1].textContent = String(orders.filter(function (order) { return String(order.order_status || "").toLowerCase() === "shipped"; }).length);
    if (accountStats[2]) accountStats[2].textContent = user.country || "Member";

    if (summaryCounts[0]) summaryCounts[0].textContent = "0";
    if (summaryCounts[1]) summaryCounts[1].textContent = user.address ? "1" : "0";

    if (profileMiniItems[0]) profileMiniItems[0].textContent = (user.country || "Italy") + " / EUR";
    if (profileMiniItems[1]) profileMiniItems[1].textContent = "Luxury Custom Apparel";
    if (profileMiniItems[2]) profileMiniItems[2].textContent = orders[0] ? formatDate(orders[0].created_at) : "No order yet";
    if (profileMiniItems[3]) profileMiniItems[3].textContent = "Priority Concierge";

    setFieldValue("gfProfileFirstName", user.first_name);
    setFieldValue("gfProfileLastName", user.last_name);
    setFieldValue("gfProfileEmail", user.email);
    setFieldValue("gfProfilePhone", user.phone);
    setFieldValue("gfProfileCountry", user.country);
    setFieldValue("gfProfileCity", user.city);
    setFieldValue("gfProfilePostalCode", user.postal_code);
    setFieldValue("gfProfileAddress", user.address);
    setFieldValue("gfProfileBirthDate", user.date_of_birth);
    setFieldValue("gfProfileGender", user.gender);
    setFieldValue("gfProfileLanguage", user.preferred_language);

    setText(document.querySelector("[data-gf-primary-address-name]"), fullName || "Primary Recipient");
    setText(document.querySelector("[data-gf-primary-address-phone]"), user.phone || "Add phone number");
    setText(document.querySelector("[data-gf-primary-address-country]"), user.country || "Select country");
    setText(document.querySelector("[data-gf-primary-address-city]"), user.city || "Add your city");
    setText(document.querySelector("[data-gf-primary-address-postal]"), user.postal_code || "Add postal code");
    setText(document.querySelector("[data-gf-primary-address-full]"), user.address || "Save a delivery address to complete your profile.");
    setText(document.querySelector("[data-gf-primary-cardholder]"), fullName || "Primary account holder");
    setText(document.querySelector("[data-gf-security-location]"), [user.city, user.country].filter(Boolean).join(", ") || "Profile location pending");
    setText(document.querySelector("[data-gf-current-session]"), user.last_login_at ? "Last sign-in: " + formatDate(user.last_login_at, { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }) : "Signed in and secured from your current account session.");
    setText(document.querySelector("[data-gf-last-signin]"), user.last_login_at ? formatDate(user.last_login_at, { day: "2-digit", month: "short", year: "numeric" }) : "Local");
  }

  function renderOrders(orders) {
    const ordersContainer = document.querySelector(".gf-account-orders");
    const downloadButton = document.querySelector('[data-gf-action="download-invoices"]');
    const trackButton = document.querySelector('[data-gf-action="track-latest-order"]');

    if (!ordersContainer) {
      return;
    }

    if (!orders.length) {
      ordersContainer.innerHTML = '<article class="gf-account-order-card"><span class="gf-account-order-kicker">Order History</span><div class="gf-account-order-top"><div><h4>No orders yet</h4><p class="gf-account-order-meta">When you place your first order, it will appear here.</p></div><span class="gf-account-order-status is-progress">Empty</span></div><p>Your profile will show order number, payment status, tracking code, and invoice downloads here.</p></article>';
      if (downloadButton) downloadButton.disabled = true;
      if (trackButton) trackButton.disabled = true;
      return;
    }

    ordersContainer.innerHTML = orders.map(function (order, index) {
      const invoices = Array.isArray(order.invoices) ? order.invoices : [];
      const invoiceButtons = invoices.length
        ? '<div class="gf-account-address-actions">' + invoices.map(function (invoice) {
            return '<a class="gf-account-btn gf-account-btn-secondary" href="/GirffoN/backend/admin/invoice-pdf.php?id=' + encodeURIComponent(invoice.id) + '">Download Invoice</a>';
          }).join("") + '</div>'
        : '<div class="gf-account-address-actions"><span class="gf-account-note">No invoice available yet.</span></div>';

      return '<article class="gf-account-order-card">'
        + '<span class="gf-account-order-kicker">' + (index === 0 ? 'Recent Order' : 'Order') + '</span>'
        + '<div class="gf-account-order-top"><div><h4>' + escapeHtml(order.order_number) + '</h4><p class="gf-account-order-meta">' + escapeHtml(formatDate(order.created_at)) + '</p></div><span class="gf-account-order-status ' + statusClass(order.order_status) + '">' + escapeHtml(order.order_status_label) + '</span></div>'
        + '<div class="gf-account-order-summary">'
        + '<div class="gf-account-order-detail"><span>Order Number</span><strong>' + escapeHtml(order.order_number) + '</strong></div>'
        + '<div class="gf-account-order-detail"><span>Date</span><strong>' + escapeHtml(formatDate(order.created_at)) + '</strong></div>'
        + '<div class="gf-account-order-detail"><span>Total Amount</span><strong>' + escapeHtml(order.total_amount) + '</strong></div>'
        + '</div>'
        + '<div class="gf-account-order-line"><span>Payment Status</span><strong>' + escapeHtml(order.payment_status_label) + '</strong></div>'
        + '<div class="gf-account-order-line"><span>Tracking Code</span><strong>' + escapeHtml(order.tracking_code || '-') + '</strong></div>'
        + '<div class="gf-account-order-line"><span>Status</span><strong>' + escapeHtml(order.order_status_label) + '</strong></div>'
        + invoiceButtons
        + '</article>';
    }).join("");

    const firstInvoice = orders.flatMap(function (order) { return order.invoices || []; })[0] || null;
    if (downloadButton) {
      if (firstInvoice) {
        downloadButton.disabled = false;
        downloadButton.addEventListener('click', function () {
          window.location.href = '/GirffoN/backend/admin/invoice-pdf.php?id=' + encodeURIComponent(firstInvoice.id);
        });
      } else {
        downloadButton.disabled = true;
      }
    }

    if (trackButton) {
      trackButton.disabled = false;
      trackButton.addEventListener('click', function () {
        const latestOrder = orders[0] || null;
        if (latestOrder && latestOrder.order_number) {
          window.location.href = '/GirffoN/TrackOrder.php?order_number=' + encodeURIComponent(latestOrder.order_number);
        }
      });
    }
  }

  function bindProfileSave(userState, orders) {
    const form = document.getElementById('gfAccountProfileForm');
    const status = document.getElementById('gfProfileSaveStatus');
    if (!form || !status) {
      return;
    }

    const submitProfile = async function () {
      const formData = new FormData(form);
      const profile = Object.fromEntries(formData.entries());
      try {
        const response = await fetch('/GirffoN/backend/auth/save-profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ profile: profile })
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || 'Unable to save profile.');
        }

        userState.first_name = payload.user.first_name || '';
        userState.last_name = payload.user.last_name || '';
        userState.email = payload.user.email || '';
        userState.phone = payload.user.phone || '';
        userState.country = payload.user.country || '';
        userState.city = payload.user.city || '';
        userState.postal_code = payload.user.postal_code || userState.postal_code || '';
        userState.address = payload.user.address || '';
        userState.preferred_language = payload.user.preferred_language || userState.preferred_language || '';
        userState.date_of_birth = payload.user.date_of_birth || userState.date_of_birth || '';
        userState.gender = payload.user.gender || userState.gender || '';
        userState.last_login_at = payload.user.last_login_at || userState.last_login_at || '';
        userState.updated_at = payload.user.updated_at || userState.updated_at || '';

        applyUserData(userState, orders);
        status.hidden = false;
        status.textContent = payload.message || 'Profile saved successfully.';
      } catch (error) {
        status.hidden = false;
        status.textContent = error instanceof Error ? error.message : 'Unable to save profile.';
      }
    };

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitProfile();
    });

    document.querySelectorAll('[data-gf-save]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        const target = event.currentTarget;
        if (!(target instanceof HTMLElement)) {
          return;
        }

        if (target.hasAttribute('data-gf-save-birthday') || target.closest('#gfProfilePhoto') || target.getAttribute('data-gf-action') === 'change-password') {
          return;
        }

        if (target.closest('#gfProfileDetails') || target.closest('.gf-account-banner') || target.textContent.indexOf('Save Account') !== -1) {
          event.preventDefault();
          form.requestSubmit();
        }
      });
    });
  }

  function bindNotificationPreferenceSave(userState) {
    const toggles = Array.from(document.querySelectorAll('.gf-account-toggle[data-gf-pref-key]'));
    if (!toggles.length) {
      return;
    }

    const status = document.getElementById('gfSecuritySaveStatus');
    let isSaving = false;

    toggles.forEach(function (toggle) {
      toggle.addEventListener('click', async function () {
        if (isSaving) {
          return;
        }

        const key = toggle.getAttribute('data-gf-pref-key') || '';
        if (!key) {
          return;
        }

        const previousValue = Object.prototype.hasOwnProperty.call(userState.notificationPreferences || {}, key)
          ? Boolean(userState.notificationPreferences[key])
          : toggle.getAttribute('aria-checked') === 'true';
        const nextValue = !previousValue;

        setToggleCheckedState(toggle, nextValue);

        const nextPreferences = {
          ...(userState.notificationPreferences || {}),
          [key]: nextValue
        };

        isSaving = true;
        toggle.disabled = true;

        try {
          const savedPreferences = await saveNotificationPreferences(nextPreferences);
          userState.notificationPreferences = {
            ...(userState.notificationPreferences || {}),
            ...savedPreferences
          };
          applyNotificationPreferences(userState.notificationPreferences);

          if (status) {
            status.hidden = false;
            status.textContent = 'Preferences updated.';
          }
        } catch (error) {
          setToggleCheckedState(toggle, previousValue);

          if (status) {
            status.hidden = false;
            status.textContent = error instanceof Error ? error.message : 'Unable to save notification preferences.';
          }
        } finally {
          toggle.disabled = false;
          isSaving = false;
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const payload = window.GIRFFON_PROFILE_PAGE_DATA || null;
    if (!payload || !payload.user) {
      return;
    }

    const user = payload.user;
    const orders = Array.isArray(payload.orders) ? payload.orders : [];
    user.notificationPreferences = normalizeNotificationPreferences(payload.notificationPreferences || {});
    applyUserData(user, orders);
    applyNotificationPreferences(user.notificationPreferences);
    renderOrders(orders);
    bindProfileSave(user, orders);
    bindNotificationPreferenceSave(user);
  });
})();