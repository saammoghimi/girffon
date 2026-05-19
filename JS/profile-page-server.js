(function () {
  console.log('PROFILE SERVER JS VERSION 100 LOADED');

  const PROFILE_DATA_URL = '/GirffoN/backend/profile/profile-data.php';
  const PROFILE_UPDATE_URL = '/GirffoN/backend/profile/update-profile.php';
  const PROFILE_AVATAR_UPLOAD_URL = '/GirffoN/backend/profile/upload-avatar.php';
  const CHANGE_PASSWORD_URL = '/GirffoN/backend/profile/change-password.php';
  const ADDRESSES_URL = '/GirffoN/backend/profile/addresses.php';
  const CATALOG_SUBSCRIPTION_URL = '/GirffoN/backend/profile/catalog-subscription.php';
  const PAYMENT_METHODS_URL = '/GirffoN/backend/profile/payment-methods.php';
  const WISHLIST_URL = '/GirffoN/backend/profile/wishlist.php';
  const DESIGNS_URL = '/GirffoN/backend/profile/designs.php';
  const DELETE_ACCOUNT_URL = '/GirffoN/backend/profile/delete-account.php';
  const CART_ADD_URL = '/GirffoN/backend/cart/add-to-cart.php';
  const NOTIFICATION_PREFERENCES_URL = '/GirffoN/backend/auth/save-notification-preferences.php';
  const AVATAR_STORAGE_KEY = 'girffon_profile_avatar';
  const EMPTY_AVATAR_DATA_URI = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'110\' height=\'110\'%3E%3C/svg%3E';
  const NOTIFICATION_PREFERENCE_KEYS = [
    'promotionalEmails',
    'catalogEmails',
    'birthdayDiscountEmails',
    'orderUpdates',
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

  function normalizeOptionToken(value) {
    return String(value || '')
      .trim()
      .toLowerCase()
      .replace(/[\s_-]+/g, ' ')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function normalizeProfileSelectValue(type, value) {
    const normalized = normalizeOptionToken(value);
    if (!normalized) {
      return '';
    }

    if (type === 'gender') {
      if (normalized === 'male') return 'male';
      if (normalized === 'female') return 'female';
      if (normalized === 'other' || normalized === 'non binary' || normalized === 'nonbinary') return 'non binary';
      if (normalized === 'prefer not to say' || normalized === 'prefer not say') return 'prefer not to say';
    }

    if (type === 'language') {
      if (normalized === 'english') return 'english';
      if (normalized === 'italian' || normalized === 'italiano') return 'italiano';
      if (normalized === 'french' || normalized === 'francais') return 'francais';
      if (normalized === 'german' || normalized === 'deutsch') return 'deutsch';
      if (normalized === 'spanish' || normalized === 'espanol') return 'espanol';
    }

    return normalized;
  }

  function setSelectValue(id, value, type) {
    const field = document.getElementById(id);
    if (!field) {
      return;
    }

    const targetValue = normalizeProfileSelectValue(type, value);
    if (!targetValue) {
      field.value = '';
      return;
    }

    const matchingOption = Array.from(field.options || []).find(function (option) {
      const optionValue = normalizeOptionToken(option.value || option.textContent || '');
      return optionValue === targetValue;
    });

    field.value = matchingOption ? matchingOption.value : '';
  }

  function readSelectValue(id) {
    const field = document.getElementById(id);
    return field ? String(field.value || '').trim() : '';
  }

  function readFieldValueByIds(ids) {
    const fieldIds = Array.isArray(ids) ? ids : [ids];

    for (const id of fieldIds) {
      const field = document.getElementById(String(id || ''));
      if (!field) {
        continue;
      }

      const value = String(field.value || '').trim();
      if (value !== '') {
        return value;
      }
    }

    return '';
  }

  function readSelectValueByIds(ids) {
    const fieldIds = Array.isArray(ids) ? ids : [ids];

    for (const id of fieldIds) {
      const value = readSelectValue(String(id || ''));
      if (value !== '') {
        return value;
      }
    }

    return '';
  }

  function readSavedAvatarPath(userState) {
    const avatarCandidates = [
      readAvatarState(),
      userState && userState.avatar ? userState.avatar : '',
      readCurrentAvatarPreview()
    ];

    const savedAvatar = avatarCandidates.find(function (value) {
      const candidate = String(value || '').trim();
      return candidate !== '' && candidate !== EMPTY_AVATAR_DATA_URI && !candidate.startsWith('data:');
    });

    return String(savedAvatar || '').trim();
  }

  function readCurrentAvatarPreview() {
    const headerAvatar = document.querySelector('[data-gf-avatar-target]');
    const uploadPreviewImage = document.querySelector('.gf-account-upload-preview img');
    const sources = [
      uploadPreviewImage ? uploadPreviewImage.getAttribute('src') : '',
      headerAvatar ? headerAvatar.getAttribute('src') : '',
      readAvatarState()
    ];

    const candidate = sources.find(function (value) {
      const src = String(value || '').trim();
      return src !== '' && src !== EMPTY_AVATAR_DATA_URI;
    });

    return String(candidate || '').trim();
  }

  function setText(target, value) {
    if (target) {
      target.textContent = value;
    }
  }

  function setFeedback(target, message, isError) {
    if (!target) {
      return;
    }

    target.hidden = false;
    target.textContent = message || '';
    target.classList.toggle('is-error', Boolean(isError));
  }

  function splitName(value) {
    const parts = String(value || '').trim().split(/\s+/).filter(Boolean);
    return {
      firstName: parts.shift() || '',
      lastName: parts.join(' ')
    };
  }

  function normalizeProfileUser(user) {
    const normalizedUser = user || {};
    const split = splitName(normalizedUser.name);

    return {
      ...normalizedUser,
      first_name: normalizedUser.first_name || split.firstName || '',
      last_name: normalizedUser.last_name || split.lastName || '',
      postal_code: normalizedUser.postal_code || normalizedUser.postcode || '',
      postcode: normalizedUser.postcode || normalizedUser.postal_code || '',
      preferred_language: normalizedUser.preferred_language || '',
      date_of_birth: normalizedUser.date_of_birth || '',
      gender: normalizedUser.gender || '',
      avatar: normalizedUser.avatar || ''
    };
  }

  function escapeAttribute(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  async function postJson(url, body) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify(body || {})
    });

    const payload = await response.json();
    if (!response.ok || !(payload.success || payload.ok)) {
      throw new Error(payload.message || 'Request failed.');
    }

    return payload;
  }

  async function uploadProfileAvatar(file) {
    if (!file) {
      return null;
    }

    const url = PROFILE_AVATAR_UPLOAD_URL;
    console.log('AVATAR UPLOAD URL:', url);

    const formData = new FormData();
    formData.append('avatar', file);

    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    });

    const payload = await response.json();
    console.log('AVATAR RESPONSE:', payload);
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to upload profile photo.');
    }

    if (!String(payload.avatar_in_db || '').trim()) {
      throw new Error('Avatar uploaded but database was not updated.');
    }

    return payload;
  }

  function readAvatarState() {
    try {
      return String(window.localStorage.getItem(AVATAR_STORAGE_KEY) || '');
    } catch (_error) {
      return '';
    }
  }

  function writeAvatarState(value) {
    try {
      if (!value) {
        window.localStorage.removeItem(AVATAR_STORAGE_KEY);
        return;
      }

      window.localStorage.setItem(AVATAR_STORAGE_KEY, value);
    } catch (_error) {
      // Ignore storage failures and keep the in-memory preview.
    }
  }

  function applyAvatarPreview(src) {
    const nextAvatarSrc = src || EMPTY_AVATAR_DATA_URI;

    document.querySelectorAll('[data-gf-avatar-target]').forEach(function (img) {
      img.src = nextAvatarSrc;
      img.classList.toggle('is-empty', nextAvatarSrc === EMPTY_AVATAR_DATA_URI);
    });

    const avatarWrap = document.querySelector('.gf-account-avatar-wrap');
    if (avatarWrap) {
      avatarWrap.classList.toggle('is-empty', nextAvatarSrc === EMPTY_AVATAR_DATA_URI);
    }

    const uploadPreview = document.querySelector('.gf-account-upload-preview');
    if (uploadPreview) {
      uploadPreview.innerHTML = nextAvatarSrc === EMPTY_AVATAR_DATA_URI
        ? '<i class="fa-solid fa-camera"></i>'
        : '<img src="' + escapeAttribute(nextAvatarSrc) + '" alt="Saved profile photo" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;">';
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

  async function loadProfileData() {
    const response = await fetch(PROFILE_DATA_URL, {
      credentials: 'same-origin'
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to load profile data.');
    }

    return normalizeProfileUser(payload.user || {});
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

  async function loadAddresses() {
    const response = await fetch(ADDRESSES_URL, { credentials: 'same-origin' });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to load addresses.');
    }

    return payload;
  }

  function renderAddresses(payload) {
    const container = document.querySelector('[data-gf-addresses]');
    const summaryCounts = document.querySelectorAll('.gf-account-summary-grid strong');
    if (!container) {
      return;
    }

    const addresses = Array.isArray(payload.addresses) ? payload.addresses : [];
    if (summaryCounts[1]) {
      summaryCounts[1].textContent = String(addresses.length || 0);
    }

    if (!addresses.length) {
      container.innerHTML = '<article class="gf-account-address"><div class="gf-account-address-top"><div><h4>No addresses saved yet</h4><p>Shipping Address</p></div><span class="gf-account-chip">Empty</span></div><p class="gf-account-address-full">Add a delivery address to complete your profile.</p></article>';
      return;
    }

    container.innerHTML = addresses.map(function (address) {
      const title = address.recipient_name || 'Saved Recipient';
      const label = address.is_primary ? 'Primary Shipping Address' : 'Saved Shipping Address';
      const chip = address.is_primary ? 'Default' : (address.label || 'Saved');
      const deleteButton = address.is_primary ? '' : '<button type="button" class="gf-account-btn gf-account-btn-danger" data-gf-address-action="delete" data-gf-address-id="' + escapeAttribute(address.id) + '">Delete</button>';

      return '<article class="gf-account-address">'
        + '<div class="gf-account-address-top"><div><h4>' + escapeHtml(title) + '</h4><p>' + escapeHtml(label) + '</p></div><span class="gf-account-chip">' + escapeHtml(chip) + '</span></div>'
        + '<div class="gf-account-address-meta">'
        + '<div class="gf-account-address-row"><span>Phone Number</span><strong>' + escapeHtml(address.phone || 'Add phone number') + '</strong></div>'
        + '<div class="gf-account-address-row"><span>Country</span><strong>' + escapeHtml(address.country || 'Select country') + '</strong></div>'
        + '<div class="gf-account-address-row"><span>City</span><strong>' + escapeHtml(address.city || 'Add your city') + '</strong></div>'
        + '<div class="gf-account-address-row"><span>Postal Code</span><strong>' + escapeHtml(address.postcode || 'Add postal code') + '</strong></div>'
        + '</div>'
        + '<p class="gf-account-address-full">' + escapeHtml(address.address_line || 'Save a delivery address to complete your profile.') + '</p>'
        + '<div class="gf-account-address-actions">'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-address-action="edit" data-gf-address-id="' + escapeAttribute(address.id) + '" data-gf-address-name="' + escapeAttribute(address.recipient_name) + '" data-gf-address-phone="' + escapeAttribute(address.phone) + '" data-gf-address-country="' + escapeAttribute(address.country) + '" data-gf-address-city="' + escapeAttribute(address.city) + '" data-gf-address-postcode="' + escapeAttribute(address.postcode) + '" data-gf-address-line="' + escapeAttribute(address.address_line) + '" data-gf-address-primary="' + (address.is_primary ? '1' : '0') + '">Edit</button>'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-address-action="duplicate" data-gf-address-id="' + escapeAttribute(address.id) + '"' + (address.id ? '' : ' disabled') + '>Duplicate</button>'
        + deleteButton
        + '</div></article>';
    }).join('');
  }

  function bindAddresses(userState) {
    const section = document.getElementById('gfAddressBook');
    const addButton = document.querySelector('[data-gf-add-address]');
    if (!section || !addButton) {
      return function () {
        return Promise.resolve();
      };
    }

    const refresh = function () {
      return loadAddresses().then(function (payload) {
        renderAddresses(payload);
        return payload;
      }).catch(function () {
        renderAddresses({ addresses: [] });
      });
    };

    const askAddress = function (seed) {
      const recipientName = window.prompt('Recipient name', seed.recipient_name || userState.name || '');
      if (recipientName === null) return null;
      const phone = window.prompt('Phone number', seed.phone || userState.phone || '');
      if (phone === null) return null;
      const country = window.prompt('Country', seed.country || userState.country || '');
      if (country === null) return null;
      const city = window.prompt('City', seed.city || userState.city || '');
      if (city === null) return null;
      const postcode = window.prompt('Postal code', seed.postcode || userState.postcode || userState.postal_code || '');
      if (postcode === null) return null;
      const addressLine = window.prompt('Full address', seed.address_line || userState.address || '');
      if (addressLine === null) return null;

      return {
        action: 'save',
        id: seed.id || 0,
        recipient_name: recipientName,
        phone: phone,
        country: country,
        city: city,
        postcode: postcode,
        address_line: addressLine,
        is_primary: Boolean(seed.is_primary)
      };
    };

    refresh();

    addButton.addEventListener('click', function () {
      const payload = askAddress({});
      if (!payload) return;
      postJson(ADDRESSES_URL, payload).then(function () {
        refresh();
      }).catch(function (error) {
        const status = document.getElementById('gfProfileSaveStatus');
        if (status) {
          status.hidden = false;
          status.textContent = error.message;
        }
      });
    });

    section.addEventListener('click', function (event) {
      const button = event.target.closest('[data-gf-address-action]');
      if (!button) return;

      const action = button.getAttribute('data-gf-address-action');
      const id = Number(button.getAttribute('data-gf-address-id') || '0');

      if (action === 'duplicate' && id > 0) {
        postJson(ADDRESSES_URL, { action: 'duplicate', id: id }).then(refresh).catch(function () {});
        return;
      }

      if (action === 'delete' && id > 0) {
        if (!window.confirm('Delete this saved address?')) return;
        postJson(ADDRESSES_URL, { action: 'delete', id: id }).then(refresh).catch(function () {});
        return;
      }

      if (action === 'edit') {
        const payload = askAddress({
          id: id,
          recipient_name: button.getAttribute('data-gf-address-name') || '',
          phone: button.getAttribute('data-gf-address-phone') || '',
          country: button.getAttribute('data-gf-address-country') || '',
          city: button.getAttribute('data-gf-address-city') || '',
          postcode: button.getAttribute('data-gf-address-postcode') || '',
          address_line: button.getAttribute('data-gf-address-line') || '',
          is_primary: button.getAttribute('data-gf-address-primary') === '1'
        });
        if (!payload) return;
        postJson(ADDRESSES_URL, payload).then(refresh).catch(function () {});
      }
    });

    return refresh;
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
    setSelectValue("gfProfileGender", user.gender, 'gender');
    setSelectValue("gfProfileLanguage", user.preferred_language, 'language');
    setFieldValue('gfCatalogSubscribeEmail', user.email);

    if (user.avatar) {
      writeAvatarState(user.avatar);
      applyAvatarPreview(user.avatar);
    } else {
      applyAvatarPreview(readAvatarState());
    }

    const birthdayInput = document.getElementById('gfBirthdayGiftDate');
    if (birthdayInput && !birthdayInput.value) {
      birthdayInput.value = user.date_of_birth || '';
    }

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
      ordersContainer.innerHTML = '<article class="gf-account-order-card"><span class="gf-account-order-kicker">Order History</span><div class="gf-account-order-top"><div><h4>No orders yet</h4><p class="gf-account-order-meta">When you place your first order, it will appear here.</p></div><span class="gf-account-order-status is-progress">Empty</span></div><p>Your profile will show order number, payment status, tracking number, courier, estimated delivery, timeline, and invoice downloads here.</p></article>';
      if (downloadButton) downloadButton.disabled = true;
      if (trackButton) trackButton.disabled = true;
      return;
    }

    ordersContainer.innerHTML = orders.map(function (order, index) {
      const invoices = Array.isArray(order.invoices) ? order.invoices : [];
      const timeline = Array.isArray(order.timeline) ? order.timeline : [];
      const timelineMarkup = timeline.length
        ? '<div class="gf-account-order-timeline">' + timeline.map(function (step) {
            const classes = ['gf-account-order-timeline-step'];
            if (step.completed) {
              classes.push('is-complete');
            }
            if (step.current) {
              classes.push('is-current');
            }

            return '<span class="' + classes.join(' ') + '">' + escapeHtml(step.label || 'Update') + '</span>';
          }).join('') + '</div>'
        : '';
      const invoiceButtons = invoices.length
        ? '<div class="gf-account-address-actions">' + invoices.map(function (invoice) {
            return '<a class="gf-account-btn gf-account-btn-secondary" href="/GirffoN/invoice-view.php?id=' + encodeURIComponent(invoice.id) + '">View Invoice</a>'
              + '<a class="gf-account-btn gf-account-btn-secondary" href="/GirffoN/invoice-print.php?id=' + encodeURIComponent(invoice.id) + '">Print Invoice</a>'
              + '<a class="gf-account-btn gf-account-btn-secondary" href="/GirffoN/invoice-pdf.php?id=' + encodeURIComponent(invoice.id) + '">Download PDF</a>';
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
        + '<div class="gf-account-order-line"><span>Tracking Number</span><strong>' + escapeHtml(order.tracking_code || '-') + '</strong></div>'
        + '<div class="gf-account-order-line"><span>Courier</span><strong>' + escapeHtml(order.courier_name || '-') + '</strong></div>'
        + '<div class="gf-account-order-line"><span>Estimated Delivery</span><strong>' + escapeHtml(order.estimated_delivery_date ? formatDate(order.estimated_delivery_date) : '-') + '</strong></div>'
        + '<div class="gf-account-order-line"><span>Status</span><strong>' + escapeHtml(order.order_status_label) + '</strong></div>'
        + (order.admin_note ? '<div class="gf-account-order-line"><span>Latest Update</span><strong>' + escapeHtml(order.admin_note) + '</strong></div>' : '')
        + timelineMarkup
        + invoiceButtons
        + '</article>';
    }).join("");

    const firstInvoice = orders.flatMap(function (order) { return order.invoices || []; })[0] || null;
    if (downloadButton) {
      if (firstInvoice) {
        downloadButton.disabled = false;
        downloadButton.addEventListener('click', function () {
          window.location.href = '/GirffoN/invoice-pdf.php?id=' + encodeURIComponent(firstInvoice.id);
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
    const oldForm = document.getElementById('gfAccountProfileForm');
    if (!oldForm || !oldForm.parentNode) {
      return;
    }

    const preservedValues = {
      firstName: (document.getElementById('gfProfileFirstName')?.value || '').trim(),
      lastName: (document.getElementById('gfProfileLastName')?.value || '').trim(),
      email: (document.getElementById('gfProfileEmail')?.value || '').trim(),
      phone: (document.getElementById('gfProfilePhone')?.value || '').trim(),
      country: (document.getElementById('gfProfileCountry')?.value || '').trim(),
      city: (document.getElementById('gfProfileCity')?.value || '').trim(),
      postcode: (document.getElementById('gfProfilePostalCode')?.value || '').trim(),
      address: (document.getElementById('gfProfileAddress')?.value || '').trim(),
      birthDate: readFieldValueByIds(['gfProfileDob', 'gfProfileBirthDate'])
    };

    const form = oldForm.cloneNode(true);
    oldForm.parentNode.replaceChild(form, oldForm);

    setFieldValue('gfProfileFirstName', preservedValues.firstName);
    setFieldValue('gfProfileLastName', preservedValues.lastName);
    setFieldValue('gfProfileEmail', preservedValues.email);
    setFieldValue('gfProfilePhone', preservedValues.phone);
    setFieldValue('gfProfileCountry', preservedValues.country);
    setFieldValue('gfProfileCity', preservedValues.city);
    setFieldValue('gfProfilePostalCode', preservedValues.postcode);
    setFieldValue('gfProfileAddress', preservedValues.address);
    setFieldValue('gfProfileBirthDate', preservedValues.birthDate);

    const status = document.getElementById('gfProfileSaveStatus');
    const emailField = document.getElementById('gfProfileEmail');
    if (!status) {
      return;
    }

    if (emailField) {
      emailField.readOnly = true;
    }

    console.log('PROFILE SERVER SAVE HANDLER ACTIVE');
    if (!window.__girffonProfileServerSaveHandlerAlerted && typeof window.alert === 'function') {
      window.__girffonProfileServerSaveHandlerAlerted = true;
      window.alert('PROFILE SERVER SAVE HANDLER ACTIVE');
    }

    const submitProfile = async function () {
      const firstName = (document.getElementById('gfProfileFirstName')?.value || '').trim();
      const lastName = (document.getElementById('gfProfileLastName')?.value || '').trim();
      const birthDate = readFieldValueByIds(['gfProfileDob', 'gfProfileBirthDate', 'gfBirthdayGiftDate']) || userState.date_of_birth || '';
      const genderValue = readSelectValueByIds(['gfProfileGender']) || userState.gender || '';
      const preferredLanguageValue = readSelectValueByIds(['gfProfileLanguage']) || userState.preferred_language || '';
      const avatarInput = document.getElementById('gfProfileAvatarInput');
      const selectedAvatarFile = avatarInput && avatarInput.files ? avatarInput.files[0] || null : null;
      try {
        let avatarValue = readSavedAvatarPath(userState);

        if (selectedAvatarFile) {
          const avatarUploadResponse = await uploadProfileAvatar(selectedAvatarFile);
          avatarValue = String(
            avatarUploadResponse.avatar_in_db
              || avatarUploadResponse.saved_avatar
              || avatarUploadResponse.avatar
              || ''
          ).trim();
          writeAvatarState(avatarValue);
          applyAvatarPreview(avatarValue);
          if (avatarInput) {
            avatarInput.value = '';
          }
        }

        const profile = {
          name: [firstName, lastName].filter(Boolean).join(' ').trim(),
          email: (document.getElementById('gfProfileEmail')?.value || '').trim(),
          phone: (document.getElementById('gfProfilePhone')?.value || '').trim(),
          address: (document.getElementById('gfProfileAddress')?.value || '').trim(),
          city: (document.getElementById('gfProfileCity')?.value || '').trim(),
          country: (document.getElementById('gfProfileCountry')?.value || '').trim(),
          postcode: (document.getElementById('gfProfilePostalCode')?.value || '').trim(),
          date_of_birth: birthDate,
          preferred_language: preferredLanguageValue,
          gender: genderValue,
          avatar: avatarValue
        };

        console.log('Profile save payload', {
          date_of_birth: profile.date_of_birth,
          gender: profile.gender,
          preferred_language: profile.preferred_language,
          avatar: profile.avatar
        });

        const response = await fetch(PROFILE_UPDATE_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'same-origin',
          body: JSON.stringify(profile)
        });
        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Unable to save profile.');
        }

        const normalizedUser = normalizeProfileUser(payload.user || {});
        const savedDateOfBirth = String(payload.saved_date_of_birth || normalizedUser.date_of_birth || '');
        const savedGender = String(payload.saved_gender || normalizedUser.gender || '');
        const savedPreferredLanguage = String(payload.saved_preferred_language || normalizedUser.preferred_language || '');
        const savedAvatar = String(payload.saved_avatar || normalizedUser.avatar || '');

        userState.first_name = normalizedUser.first_name || '';
        userState.last_name = normalizedUser.last_name || '';
        userState.name = normalizedUser.name || '';
        userState.email = normalizedUser.email || userState.email || '';
        userState.phone = normalizedUser.phone || '';
        userState.country = normalizedUser.country || '';
        userState.city = normalizedUser.city || '';
        userState.postal_code = normalizedUser.postal_code || '';
        userState.postcode = normalizedUser.postcode || normalizedUser.postal_code || '';
        userState.address = normalizedUser.address || '';
        userState.date_of_birth = savedDateOfBirth;
        userState.preferred_language = savedPreferredLanguage;
        userState.gender = savedGender;
        userState.avatar = savedAvatar;
        userState.last_login_at = normalizedUser.last_login_at || userState.last_login_at || '';
        userState.updated_at = normalizedUser.updated_at || userState.updated_at || '';

        writeAvatarState(userState.avatar);

        applyUserData(userState, orders);
        setFieldValue('gfProfileBirthDate', savedDateOfBirth);
        setSelectValue('gfProfileGender', savedGender, 'gender');
        setSelectValue('gfProfileLanguage', savedPreferredLanguage, 'language');
        applyAvatarPreview(userState.avatar || readAvatarState());
        console.log('Profile saved response', {
          saved_date_of_birth: savedDateOfBirth,
          saved_gender: savedGender,
          saved_preferred_language: savedPreferredLanguage,
          saved_avatar: savedAvatar
        });
        status.hidden = false;
        status.textContent = payload.message || 'Profile saved successfully.';
        const birthdayInput = document.getElementById('gfBirthdayGiftDate');
        if (birthdayInput) {
          birthdayInput.value = userState.date_of_birth || '';
        }
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

        if (key === 'twoFactorEnabled') {
          if (status) {
            status.hidden = false;
            status.textContent = 'Two-Factor Authentication is available soon.';
          }
          setToggleCheckedState(toggle, false);
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
            status.textContent = nextValue ? 'Enabled' : 'Disabled';
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

  function bindCatalogSubscription(userState) {
    const form = document.getElementById('gfCatalogSubscriptionForm');
    const emailInput = document.getElementById('gfCatalogSubscribeEmail');
    const status = document.getElementById('gfCatalogSubscribeStatus');

    if (!form || !emailInput) {
      return;
    }

    if (!emailInput.value && userState.email) {
      emailInput.value = userState.email;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const email = String(emailInput.value || '').trim();
      if (!email) {
        setFeedback(status, 'Enter an email address first.', true);
        return;
      }

      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
      }

      postJson(CATALOG_SUBSCRIPTION_URL, { email: email }).then(function () {
        setFeedback(status, 'Catalog subscription saved successfully. Your email is now available in the admin newsletter campaign page.', false);
        userState.notificationPreferences = {
          ...(userState.notificationPreferences || {}),
          catalogEmails: true
        };
        applyNotificationPreferences(userState.notificationPreferences);
      }).catch(function (error) {
        setFeedback(status, error.message || 'Unable to save catalog subscription.', true);
      }).finally(function () {
        if (submitButton) {
          submitButton.disabled = false;
        }
      });
    });
  }

  function bindProfilePhoto() {
    const fileInput = document.getElementById('gfProfileAvatarInput');
    const photoStatus = document.getElementById('gfProfilePhotoStatus');
    const applyButton = document.querySelector('#gfProfilePhoto [data-gf-save]');
    let pendingAvatarSrc = '';

    applyAvatarPreview(readAvatarState());

    if (!fileInput || !applyButton) {
      return;
    }

    fileInput.addEventListener('change', function () {
      const file = fileInput.files && fileInput.files[0];
      if (!file) {
        return;
      }

      if (!file.type || file.type.indexOf('image/') !== 0) {
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = 'Please choose a JPG, PNG, or WEBP image.';
        }
        fileInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (event) {
        pendingAvatarSrc = event.target && typeof event.target.result === 'string' ? event.target.result : '';
        if (!pendingAvatarSrc) {
          return;
        }

        applyAvatarPreview(pendingAvatarSrc);
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = 'Photo ready. Click Apply Photo to save it on this device.';
        }
      };
      reader.readAsDataURL(file);
    });

    applyButton.addEventListener('click', async function () {
      const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!pendingAvatarSrc || !file) {
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = 'Choose a profile image first.';
        }
        return;
      }

      try {
        const avatarUploadResponse = await uploadProfileAvatar(file);
        const savedAvatar = String(
          avatarUploadResponse.avatar_in_db
            || avatarUploadResponse.saved_avatar
            || avatarUploadResponse.avatar
            || ''
        ).trim();
        pendingAvatarSrc = savedAvatar;
        writeAvatarState(savedAvatar);
        applyAvatarPreview(savedAvatar);
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = 'Profile photo uploaded and saved successfully.';
        }
        fileInput.value = '';
      } catch (error) {
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = error instanceof Error ? error.message : 'Unable to upload profile photo.';
        }
      }
    });
  }

  async function loadPaymentMethods() {
    const response = await fetch(PAYMENT_METHODS_URL, { credentials: 'same-origin' });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to load payment methods.');
    }

    return payload;
  }

  function renderPaymentMethods(payload, userState) {
    const grid = document.querySelector('[data-gf-payment-grid]');
    const countTarget = document.querySelector('[data-gf-payment-count]');
    if (!grid) {
      return;
    }

    const methods = Array.isArray(payload.methods) ? payload.methods : [];
    if (countTarget) {
      countTarget.textContent = String(methods.length);
    }

    if (!methods.length) {
      grid.innerHTML = '<article class="gf-account-payment-card"><div class="gf-account-payment-top"><strong>Saved billing display only</strong><span class="gf-account-chip">Empty</span></div><p class="gf-account-note">No saved payment methods yet.</p><p class="gf-account-note">Full card numbers and CVC are never stored here.</p></article>';
      return;
    }

    grid.innerHTML = methods.map(function (method) {
      return '<article class="gf-account-payment-card' + (method.is_primary ? ' gf-account-payment-card-featured' : '') + '">'
        + '<div class="gf-account-payment-visual"><span class="gf-account-payment-brand">' + escapeHtml(method.card_brand || 'CARD') + '</span><span class="gf-account-payment-lock"><i class="fa-solid fa-lock"></i> Saved</span></div>'
        + '<div class="gf-account-payment-top"><strong>' + escapeHtml((method.card_brand || 'Card') + ' ending ' + (method.last4 || '----')) + '</strong><span class="gf-account-chip">' + (method.is_primary ? 'Primary' : escapeHtml(method.billing_method || 'Billing')) + '</span></div>'
        + '<div class="gf-account-payment-meta">'
        + '<div class="gf-account-payment-row"><span>Cardholder</span><strong>' + escapeHtml(method.cardholder_name || userState.name || 'Primary account holder') + '</strong></div>'
        + '<div class="gf-account-payment-row"><span>Expires</span><strong>' + escapeHtml((method.expiry_month || '--') + '/' + (method.expiry_year || '--')) + '</strong></div>'
        + '<div class="gf-account-payment-row"><span>Billing Method</span><strong>' + escapeHtml(method.billing_method || 'Saved Billing') + '</strong></div>'
        + '</div>'
        + '<div class="gf-account-address-actions">'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-payment-action="edit" data-gf-payment-id="' + escapeAttribute(method.id) + '" data-gf-payment-brand="' + escapeAttribute(method.card_brand) + '" data-gf-payment-cardholder="' + escapeAttribute(method.cardholder_name) + '" data-gf-payment-expiry-month="' + escapeAttribute(method.expiry_month) + '" data-gf-payment-expiry-year="' + escapeAttribute(method.expiry_year) + '" data-gf-payment-billing="' + escapeAttribute(method.billing_method) + '">Edit Card</button>'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-payment-action="primary" data-gf-payment-id="' + escapeAttribute(method.id) + '">Set Primary</button>'
        + '<button type="button" class="gf-account-btn gf-account-btn-danger" data-gf-payment-action="delete" data-gf-payment-id="' + escapeAttribute(method.id) + '">Delete</button>'
        + '</div>'
        + '</article>';
    }).join('');
  }

  function bindPaymentMethods(userState) {
    const section = document.getElementById('gfPayments');
    const addButton = document.querySelector('[data-gf-add-payment]');
    if (!section || !addButton) {
      return;
    }

    const refresh = function () {
      return loadPaymentMethods().then(function (payload) {
        renderPaymentMethods(payload, userState);
      });
    };

    refresh().catch(function () {
      renderPaymentMethods({ methods: [] }, userState);
    });

    const askPayment = function (seed) {
      const cardholderName = window.prompt('Cardholder name', seed.cardholder_name || userState.name || '');
      if (cardholderName === null) return null;
      const cardBrand = window.prompt('Card brand', seed.card_brand || 'Visa');
      if (cardBrand === null) return null;
      const cardNumber = window.prompt('Card number (only last 4 will be stored)', '');
      if (cardNumber === null && !seed.id) return null;
      const expiryMonth = window.prompt('Expiry month (MM)', seed.expiry_month || '12');
      if (expiryMonth === null) return null;
      const expiryYear = window.prompt('Expiry year (YYYY)', seed.expiry_year || '2028');
      if (expiryYear === null) return null;
      const billingMethod = window.prompt('Billing method', seed.billing_method || 'Personal Card');
      if (billingMethod === null) return null;

      return {
        action: 'save',
        id: seed.id || 0,
        cardholder_name: cardholderName,
        card_brand: cardBrand,
        card_number: cardNumber || '',
        expiry_month: expiryMonth,
        expiry_year: expiryYear,
        billing_method: billingMethod,
        is_primary: seed.is_primary || false
      };
    };

    addButton.addEventListener('click', function () {
      const payload = askPayment({});
      if (!payload) return;
      postJson(PAYMENT_METHODS_URL, payload).then(refresh).catch(function (error) {
        const status = document.getElementById('gfSecuritySaveStatus');
        if (status) {
          status.hidden = false;
          status.textContent = error.message;
        }
      });
    });

    section.addEventListener('click', function (event) {
      const button = event.target.closest('[data-gf-payment-action]');
      if (!button) return;
      const action = button.getAttribute('data-gf-payment-action');
      const id = Number(button.getAttribute('data-gf-payment-id') || '0');

      if (action === 'delete') {
        if (!window.confirm('Delete this saved payment method?')) return;
        postJson(PAYMENT_METHODS_URL, { action: 'delete', id: id }).then(refresh).catch(function () {});
        return;
      }

      if (action === 'primary') {
        postJson(PAYMENT_METHODS_URL, { action: 'set-primary', id: id }).then(refresh).catch(function () {});
        return;
      }

      if (action === 'edit') {
        const payload = askPayment({
          id: id,
          card_brand: button.getAttribute('data-gf-payment-brand') || '',
          cardholder_name: button.getAttribute('data-gf-payment-cardholder') || '',
          expiry_month: button.getAttribute('data-gf-payment-expiry-month') || '',
          expiry_year: button.getAttribute('data-gf-payment-expiry-year') || '',
          billing_method: button.getAttribute('data-gf-payment-billing') || ''
        });
        if (!payload) return;
        postJson(PAYMENT_METHODS_URL, payload).then(refresh).catch(function () {});
      }
    });
  }

  async function loadWishlist() {
    const response = await fetch(WISHLIST_URL, { credentials: 'same-origin' });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to load wishlist.');
    }
    return payload;
  }

  function renderWishlist(payload) {
    const grid = document.querySelector('.gf-account-saved-grid');
    const summaryCounts = document.querySelectorAll('.gf-account-summary-grid strong');
    if (!grid) {
      return;
    }

    const items = Array.isArray(payload.items) ? payload.items : [];
    if (summaryCounts[2]) {
      summaryCounts[2].textContent = String(items.length);
    }

    if (!items.length) {
      grid.innerHTML = '<article class="gf-account-saved-card"><div class="gf-account-saved-body"><div class="gf-account-saved-top"><h4>No saved items yet.</h4><span class="gf-account-stock-badge">Empty</span></div><p class="gf-account-note">When you save products to wishlist, they will appear here.</p></div></article>';
      return;
    }

    grid.innerHTML = items.map(function (item) {
      const image = item.image ? '<img class="gf-account-saved-image" src="' + escapeAttribute(item.image) + '" alt="' + escapeAttribute(item.name) + '">' : '<div class="gf-account-saved-image"></div>';
      return '<article class="gf-account-saved-card">' + image + '<div class="gf-account-saved-body"><div class="gf-account-saved-top"><h4>' + escapeHtml(item.name) + '</h4><span class="gf-account-stock-badge ' + escapeAttribute(item.stock_class) + '">' + escapeHtml(item.stock_label) + '</span></div><p class="gf-account-saved-price">' + escapeHtml(item.price_label) + '</p><div class="gf-account-saved-actions"><button type="button" class="gf-account-btn gf-account-btn-primary" data-gf-wishlist-action="cart" data-gf-wishlist-id="' + escapeAttribute(item.id) + '" data-gf-sku="' + escapeAttribute(item.sku) + '" data-gf-name="' + escapeAttribute(item.name) + '" data-gf-price="' + escapeAttribute(item.price) + '" data-gf-image="' + escapeAttribute(item.image) + '" data-gf-size="' + escapeAttribute(item.size) + '" data-gf-color="' + escapeAttribute(item.color) + '"' + (item.can_add_to_cart ? '' : ' disabled') + '>Add to Cart</button><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-wishlist-action="remove" data-gf-wishlist-id="' + escapeAttribute(item.id) + '">Remove</button></div></div></article>';
    }).join('');
  }

  function bindWishlist() {
    const section = document.getElementById('gfSavedItems');
    if (!section) {
      return;
    }

    const refresh = function () {
      return loadWishlist().then(renderWishlist).catch(function () {
        renderWishlist({ items: [] });
      });
    };

    refresh();

    section.addEventListener('click', function (event) {
      const button = event.target.closest('[data-gf-wishlist-action]');
      if (!button) return;
      const action = button.getAttribute('data-gf-wishlist-action');
      const id = Number(button.getAttribute('data-gf-wishlist-id') || '0');

      if (action === 'remove') {
        postJson(WISHLIST_URL, { action: 'remove', id: id }).then(refresh).catch(function () {});
        return;
      }

      if (action === 'cart') {
        postJson(CART_ADD_URL, {
          sku: button.getAttribute('data-gf-sku') || '',
          name: button.getAttribute('data-gf-name') || '',
          price: button.getAttribute('data-gf-price') || '',
          image: button.getAttribute('data-gf-image') || '',
          size: button.getAttribute('data-gf-size') || '',
          color: button.getAttribute('data-gf-color') || '',
          quantity: 1
        }).then(function () {
          button.textContent = 'Added';
        }).catch(function () {});
      }
    });
  }

  async function loadDesigns() {
    const response = await fetch(DESIGNS_URL, { credentials: 'same-origin' });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Unable to load designs.');
    }
    return payload;
  }

  function renderDesigns(payload) {
    const grid = document.querySelector('.gf-account-designs-grid');
    if (!grid) {
      return;
    }

    const items = Array.isArray(payload.items) ? payload.items : [];
    if (!items.length) {
      grid.innerHTML = '<article class="gf-account-design-card"><div class="gf-account-design-body"><h4>No saved designs yet.</h4><p class="gf-account-note">When you save a custom design, it will appear here.</p></div></article>';
      return;
    }

    grid.innerHTML = items.map(function (item) {
      const orderNumber = item.order_number || ('Custom Order #' + String(item.id || ''));
      const productName = item.product_name || 'Custom Product';
      const statusLabel = item.status_label || item.status || 'New';
      const previewAlt = orderNumber + ' front preview';
      const thumb = item.preview_image
        ? '<div class="gf-account-design-thumb" aria-hidden="true"><img src="' + escapeAttribute(item.preview_image) + '" alt="' + escapeAttribute(previewAlt) + '" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"></div>'
        : '<div class="gf-account-design-thumb" aria-hidden="true"><span class="gf-account-design-shirt"></span><span class="gf-account-design-mark"></span></div>';

      const viewAction = item.view_url
        ? '<a class="gf-account-btn gf-account-btn-secondary" href="' + escapeAttribute(item.view_url) + '" target="_blank" rel="noopener">View Design</a>'
        : '<span class="gf-account-btn gf-account-btn-secondary" aria-disabled="true">View Design</span>';
      const downloadAction = item.download_url
        ? '<a class="gf-account-btn gf-account-btn-secondary" href="' + escapeAttribute(item.download_url) + '" download="' + escapeAttribute(item.download_name || 'custom-order-preview.png') + '">Download Preview</a>'
        : '<span class="gf-account-btn gf-account-btn-secondary" aria-disabled="true">Download Preview</span>';

      return '<article class="gf-account-design-card">'
        + thumb
        + '<div class="gf-account-design-body">'
        + '<h4>' + escapeHtml(orderNumber) + '</h4>'
        + '<p class="gf-account-note">' + escapeHtml(productName) + '</p>'
        + '<p class="gf-account-note">Status: ' + escapeHtml(statusLabel) + '</p>'
        + '<p class="gf-account-note">Date: ' + escapeHtml(formatDate(item.created_at)) + '</p>'
        + '<div class="gf-account-design-actions">' + viewAction + downloadAction + '</div>'
        + '</div>'
        + '</article>';
    }).join('');
  }

  function bindDesigns() {
    const section = document.getElementById('gfMyDesigns');
    if (!section) {
      return;
    }

    const refresh = function () {
      return loadDesigns().then(renderDesigns).catch(function () {
        renderDesigns({ items: [] });
      });
    };

    refresh();
  }

  function bindBirthdaySave(userState, orders) {
    const birthdayInput = document.getElementById('gfBirthdayGiftDate');
    const birthdayButton = document.querySelector('[data-gf-save-birthday]');
    const birthdayStatus = document.getElementById('gfBirthdaySaveStatus');
    if (!birthdayInput || !birthdayButton || !birthdayStatus) {
      return;
    }

    birthdayButton.addEventListener('click', function () {
      postJson(PROFILE_UPDATE_URL, {
        name: userState.name || [userState.first_name, userState.last_name].filter(Boolean).join(' ').trim(),
        phone: userState.phone || '',
        address: userState.address || '',
        city: userState.city || '',
        country: userState.country || '',
        postcode: userState.postcode || userState.postal_code || '',
        preferred_language: userState.preferred_language || '',
        date_of_birth: birthdayInput.value || '',
        gender: userState.gender || '',
        avatar: userState.avatar || readAvatarState()
      }).then(function (payload) {
        const normalizedUser = normalizeProfileUser(payload.user || {});
        userState.date_of_birth = normalizedUser.date_of_birth || '';
        applyUserData(userState, orders);
        birthdayStatus.hidden = false;
        birthdayStatus.textContent = 'Birthday saved successfully.';
      }).catch(function (error) {
        birthdayStatus.hidden = false;
        birthdayStatus.textContent = error.message;
      });
    });
  }

  function bindSecurityActions(userState) {
    const securityStatus = document.getElementById('gfSecuritySaveStatus');
    const changePasswordButton = document.querySelector('[data-gf-action="change-password"]');
    const deleteAccountButton = document.querySelector('.gf-account-security-danger .gf-account-btn-danger');

    if (changePasswordButton) {
      changePasswordButton.addEventListener('click', function () {
        const currentPassword = window.prompt('Current password');
        if (currentPassword === null) return;
        const newPassword = window.prompt('New password (minimum 6 characters)');
        if (newPassword === null) return;
        const confirmPassword = window.prompt('Confirm new password');
        if (confirmPassword === null) return;

        postJson(CHANGE_PASSWORD_URL, {
          current_password: currentPassword,
          new_password: newPassword,
          confirm_password: confirmPassword
        }).then(function (payload) {
          if (securityStatus) {
            securityStatus.hidden = false;
            securityStatus.textContent = payload.message || 'Password changed successfully.';
          }
        }).catch(function (error) {
          if (securityStatus) {
            securityStatus.hidden = false;
            securityStatus.textContent = error.message;
          }
        });
      });
    }

    if (deleteAccountButton) {
      deleteAccountButton.addEventListener('click', function () {
        const confirmation = window.prompt('Type DELETE to disable your account');
        if (confirmation === null) return;
        postJson(DELETE_ACCOUNT_URL, { confirmation: confirmation }).then(function (payload) {
          if (securityStatus) {
            securityStatus.hidden = false;
            securityStatus.textContent = payload.message || 'Account disabled successfully.';
          }
          window.location.href = '/GirffoN/Index.html';
        }).catch(function (error) {
          if (securityStatus) {
            securityStatus.hidden = false;
            securityStatus.textContent = error.message;
          }
        });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const payload = window.GIRFFON_PROFILE_PAGE_DATA || {};
    const initialUser = normalizeProfileUser(payload.user || {});
    const orders = Array.isArray(payload.orders) ? payload.orders : [];

    if (!payload || !payload.notificationPreferences) {
      initialUser.notificationPreferences = normalizeNotificationPreferences({});
    } else {
      initialUser.notificationPreferences = normalizeNotificationPreferences(payload.notificationPreferences || {});
    }

    if (!initialUser || (!initialUser.id && !initialUser.email && !initialUser.username)) {
      return;
    }

    applyUserData(initialUser, orders);
    applyNotificationPreferences(initialUser.notificationPreferences);
    renderOrders(orders);
    const refreshAddresses = bindAddresses(initialUser);
    bindProfileSave(initialUser, orders);
    bindNotificationPreferenceSave(initialUser);
    bindCatalogSubscription(initialUser);
    bindProfilePhoto();
    bindPaymentMethods(initialUser);
    bindWishlist();
    bindDesigns();
    bindBirthdaySave(initialUser, orders);
    bindSecurityActions(initialUser);

    loadProfileData().then(function (loadedUser) {
      initialUser.first_name = loadedUser.first_name || '';
      initialUser.last_name = loadedUser.last_name || '';
      initialUser.name = loadedUser.name || '';
      initialUser.email = loadedUser.email || initialUser.email || '';
      initialUser.phone = loadedUser.phone || '';
      initialUser.address = loadedUser.address || '';
      initialUser.city = loadedUser.city || '';
      initialUser.country = loadedUser.country || '';
      initialUser.postal_code = loadedUser.postal_code || '';
      initialUser.postcode = loadedUser.postcode || loadedUser.postal_code || '';
      initialUser.preferred_language = loadedUser.preferred_language || '';
      initialUser.date_of_birth = loadedUser.date_of_birth || '';
      initialUser.gender = loadedUser.gender || '';
      initialUser.avatar = loadedUser.avatar || '';
      initialUser.username = loadedUser.username || initialUser.username || '';
      initialUser.created_at = loadedUser.created_at || initialUser.created_at || '';
      initialUser.updated_at = loadedUser.updated_at || initialUser.updated_at || '';
      initialUser.last_login_at = loadedUser.last_login_at || initialUser.last_login_at || '';
      applyUserData(initialUser, orders);
      refreshAddresses();
    }).catch(function (error) {
      const status = document.getElementById('gfProfileSaveStatus');
      if (status && error instanceof Error) {
        status.hidden = false;
        status.textContent = error.message;
      }
    });
  });
})();