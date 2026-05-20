(function () {
  "use strict";

  console.log("ACCOUNT PANEL JS FORGOT PASSWORD ACTIVE");

  function detectAppBasePath() {
    const scriptNode = Array.from(document.scripts).find(function (node) {
      return /\/JS\/account-panel\.js(?:\?|$)/i.test(node.src || "");
    });

    if (scriptNode && scriptNode.src) {
      const scriptUrl = new URL(scriptNode.src, window.location.href);
      return scriptUrl.pathname.replace(/\/JS\/account-panel\.js$/i, "");
    }

    const currentPath = window.location.pathname || "/";
    return currentPath.replace(/\/[^/]*$/, "") || "/";
  }

  function buildAppPath(relativePath) {
    const normalizedPath = String(relativePath || "").replace(/^\/+/, "");
    const basePath = detectAppBasePath().replace(/\/+$/, "");

    if (!basePath) {
      return `/${normalizedPath}`;
    }

    return `${basePath}/${normalizedPath}`.replace(/\/+/g, "/");
  }

  const trigger = document.getElementById("gfAccountTrigger");
  const panel = document.getElementById("gfAccountPanel");
  const overlay = document.getElementById("gfAccountOverlay");
  const closeBtn = panel ? panel.querySelector(".gf-account-close") : null;

  if (!trigger || !panel || !overlay) {
    return;
  }

  const guestView = document.getElementById("gfAccountGuest");
  const userView = document.getElementById("gfAccountUser");
  const loginForm = document.getElementById("gfAccountLoginForm");
  const loginIdentifierInput = document.getElementById("gfLoginIdentifier");
  const loginPasswordInput = document.getElementById("gfLoginPassword");
  const loginBtn = document.getElementById("gfLoginBtn");
  const signupBtn = document.getElementById("gfSignupBtn");
  const forgotBtn = document.getElementById("gfForgotAccountBtn");
  const googleLoginBtn = document.getElementById("gfGoogleLoginBtn");
  const appleLoginBtn = document.getElementById("gfAppleLoginBtn");
  const logoutBtn = document.getElementById("gfLogoutBtn");
  const manageAccountBtn = document.getElementById("gfManageAccountBtn");
  const myDesignsBtn = document.getElementById("gfMyDesignsBtn");
  const orderHistoryBtn = document.getElementById("gfOrderHistoryBtn");
  const paymentMethodsBtn = document.getElementById("gfPaymentMethodsBtn");
  const shippingAddressesBtn = document.getElementById("gfShippingAddressesBtn");
  const userNameEl = document.getElementById("gfUserName");
  const userEmailEl = document.getElementById("gfUserEmail");
  const userAvatarWrap = panel.querySelector(".gf-account-profile-icon");
  const authHeadTitle = panel.querySelector(".gf-account-auth-head h4");
  const authHeadCopy = panel.querySelector(".gf-account-auth-head p");

  const LOGIN_URL = buildAppPath("backend/auth/login.php");
  const REGISTER_URL = buildAppPath("backend/auth/register.php");
  const FORGOT_PASSWORD_URL = buildAppPath("backend/auth/forgot-password.php");
  const SESSION_URL = buildAppPath("backend/auth/session.php");
  const LOGOUT_URL = buildAppPath("logout.php");
  const PROFILE_DATA_URL = buildAppPath("backend/profile/profile-data.php");
  const PROFILE_URL = buildAppPath("ProfilePage.php");
  const AVATAR_STORAGE_KEY = "girffon_profile_avatar";
  const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const REGISTER_SUCCESS_MESSAGE = "Congratulations! Your GirffoN account has been created. Please check your email.";

  let authStatus = document.getElementById("gfAccountAuthStatus");
  let registerNameInput = document.getElementById("gfRegisterName");
  let registerEmailInput = document.getElementById("gfRegisterEmail");
  let registerPhoneInput = document.getElementById("gfRegisterPhone");
  let registerPromotionalEmailsInput = document.getElementById("gfRegisterPromotionalEmails");
  let registerCatalogEmailsInput = document.getElementById("gfRegisterCatalogEmails");
  let forgotEmailInput = document.getElementById("gfForgotPasswordEmail");
  let isRegisterMode = false;
  let isForgotPasswordMode = false;
  let isAuthenticated = false;
  let currentUser = null;
  let isSyncingSession = false;

  function dispatchAuthUpdated() {
    document.dispatchEvent(new CustomEvent("girffon:auth-updated"));
  }

  function findInputGroup(input) {
    return input ? input.closest(".gf-account-input-group") : null;
  }

  function ensureAuthStatusNode() {
    if (authStatus || !loginForm) {
      return authStatus;
    }

    authStatus = document.createElement("div");
    authStatus.id = "gfAccountAuthStatus";
    authStatus.setAttribute("role", "status");
    authStatus.setAttribute("aria-live", "polite");
    authStatus.style.minHeight = "1.25rem";
    authStatus.style.fontSize = "0.95rem";
    authStatus.style.lineHeight = "1.4";
    loginForm.appendChild(authStatus);
    return authStatus;
  }

  function setAuthStatus(message, isError) {
    ensureAuthStatusNode();
    if (!authStatus) {
      return;
    }

    authStatus.textContent = String(message || "");
    authStatus.style.color = isError ? "#d46a6a" : "";
  }

  function createInputGroup(config) {
    if (!loginForm) {
      return null;
    }

    const group = document.createElement("div");
    group.className = "gf-account-input-group";

    const label = document.createElement("label");
    label.className = "gf-account-input-label";
    label.htmlFor = config.id;
    label.textContent = config.label;

    const wrap = document.createElement("div");
    wrap.className = "gf-account-input-wrap";

    const icon = document.createElement("i");
    icon.className = config.iconClass;
    icon.setAttribute("aria-hidden", "true");

    const input = document.createElement("input");
    input.type = config.type;
    input.id = config.id;
    input.name = config.name;
    input.placeholder = config.placeholder;
    input.autocomplete = config.autocomplete;

    wrap.appendChild(icon);
    wrap.appendChild(input);
    group.appendChild(label);
    group.appendChild(wrap);

    const passwordGroup = findInputGroup(loginPasswordInput);
    if (passwordGroup && passwordGroup.parentNode === loginForm) {
      loginForm.insertBefore(group, passwordGroup);
    } else {
      loginForm.appendChild(group);
    }

    return input;
  }

  function createRegisterPreferenceGroup(config) {
    if (!loginForm) {
      return null;
    }

    const group = document.createElement("div");
    group.className = "gf-account-input-group gf-account-preference-group";
    group.style.display = "none";

    const label = document.createElement("label");
    label.htmlFor = config.id;
    label.style.display = "grid";
    label.style.gridTemplateColumns = "20px 1fr";
    label.style.alignItems = "start";
    label.style.gap = "10px";
    label.style.cursor = "pointer";

    const input = document.createElement("input");
    input.type = "checkbox";
    input.id = config.id;
    input.name = config.name;
    input.value = "1";
    input.style.marginTop = "4px";
    input.style.width = "16px";
    input.style.height = "16px";
    input.style.accentColor = "#c7a54b";

    const copy = document.createElement("span");
    copy.style.display = "grid";
    copy.style.gap = "4px";

    const title = document.createElement("strong");
    title.textContent = config.label;
    title.style.fontSize = "0.95rem";

    const note = document.createElement("span");
    note.textContent = config.note;
    note.style.fontSize = "0.86rem";
    note.style.lineHeight = "1.45";
    note.style.color = "rgba(255,255,255,0.78)";

    copy.appendChild(title);
    copy.appendChild(note);
    label.appendChild(input);
    label.appendChild(copy);
    group.appendChild(label);

    const authRowNode = loginForm.querySelector(".gf-account-auth-row");
    if (authRowNode && authRowNode.parentNode === loginForm) {
      loginForm.insertBefore(group, authRowNode);
    } else {
      loginForm.appendChild(group);
    }

    return input;
  }

  function ensureRegisterInputs() {
    registerNameInput = registerNameInput || createInputGroup({
      id: "gfRegisterName",
      name: "registerName",
      type: "text",
      label: "Full name",
      placeholder: "Your full name",
      autocomplete: "name",
      iconClass: "fa-regular fa-id-card"
    });

    registerEmailInput = registerEmailInput || createInputGroup({
      id: "gfRegisterEmail",
      name: "registerEmail",
      type: "email",
      label: "Email address",
      placeholder: "name@example.com",
      autocomplete: "email",
      iconClass: "fa-regular fa-envelope"
    });

    registerPhoneInput = registerPhoneInput || createInputGroup({
      id: "gfRegisterPhone",
      name: "registerPhone",
      type: "tel",
      label: "Mobile number",
      placeholder: "Optional mobile number",
      autocomplete: "tel",
      iconClass: "fa-solid fa-mobile-screen-button"
    });

    registerPromotionalEmailsInput = registerPromotionalEmailsInput || createRegisterPreferenceGroup({
      id: "gfRegisterPromotionalEmails",
      name: "accepts_promotional_emails",
      label: "Promotional emails",
      note: "Optional. Receive offers, discounts, campaigns, and marketing updates from GirffoN."
    });

    registerCatalogEmailsInput = registerCatalogEmailsInput || createRegisterPreferenceGroup({
      id: "gfRegisterCatalogEmails",
      name: "accepts_catalog_emails",
      label: "Catalog emails",
      note: "Optional. Receive catalog, lookbook, and new collection emails only."
    });

    forgotEmailInput = forgotEmailInput || createInputGroup({
      id: "gfForgotPasswordEmail",
      name: "forgotPasswordEmail",
      type: "email",
      label: "Email address",
      placeholder: "name@example.com",
      autocomplete: "email",
      iconClass: "fa-regular fa-envelope"
    });
  }

  ensureAuthStatusNode();
  ensureRegisterInputs();

  const loginIdentifierGroup = findInputGroup(loginIdentifierInput);
  const loginPasswordGroup = findInputGroup(loginPasswordInput);
  const registerNameGroup = findInputGroup(registerNameInput);
  const registerEmailGroup = findInputGroup(registerEmailInput);
  const registerPhoneGroup = findInputGroup(registerPhoneInput);
  const registerPromotionalEmailsGroup = findInputGroup(registerPromotionalEmailsInput);
  const registerCatalogEmailsGroup = findInputGroup(registerCatalogEmailsInput);
  const forgotEmailGroup = findInputGroup(forgotEmailInput);
  const authRow = loginForm ? loginForm.querySelector(".gf-account-auth-row") : null;
  let registerSuccessDialog = null;
  let registerSuccessHideTimer = 0;
  let registerSuccessToastVisible = false;

  function getEmailProviderUrl(email) {
    const normalizedEmail = String(email || "").trim().toLowerCase();
    const domain = normalizedEmail.split("@")[1] || "";

    if (domain === "gmail.com" || domain === "googlemail.com") {
      return "https://mail.google.com";
    }
    if (["outlook.com", "hotmail.com", "live.com", "msn.com"].includes(domain)) {
      return "https://outlook.live.com/mail";
    }
    if (domain.indexOf("yahoo") === 0 || domain.endsWith(".yahoo.com")) {
      return "https://mail.yahoo.com";
    }
    if (["icloud.com", "me.com", "mac.com"].includes(domain)) {
      return "https://www.icloud.com/mail";
    }

    return normalizedEmail ? `mailto:${normalizedEmail}` : "https://mail.google.com";
  }

  function ensureRegisterSuccessDialog() {
    if (registerSuccessDialog) {
      return registerSuccessDialog;
    }

    const overlayNode = document.createElement("div");
    overlayNode.hidden = true;
    overlayNode.setAttribute("aria-hidden", "true");
    overlayNode.style.position = "fixed";
    overlayNode.style.top = "18px";
    overlayNode.style.right = "18px";
    overlayNode.style.zIndex = "10010";
    overlayNode.style.width = "min(320px, calc(100vw - 20px))";
    overlayNode.style.maxWidth = "calc(100vw - 20px)";
    overlayNode.style.pointerEvents = "none";

    const dialogNode = document.createElement("div");
    dialogNode.setAttribute("role", "dialog");
    dialogNode.setAttribute("aria-modal", "false");
    dialogNode.style.width = "100%";
    dialogNode.style.borderRadius = "18px";
    dialogNode.style.padding = "15px 15px 14px";
    dialogNode.style.background = "linear-gradient(180deg, #fffaf2 0%, #f7efe1 100%)";
    dialogNode.style.color = "#2b241b";
    dialogNode.style.border = "1px solid rgba(199, 165, 75, 0.24)";
    dialogNode.style.boxShadow = "0 14px 34px rgba(67, 49, 20, 0.16)";
    dialogNode.style.pointerEvents = "auto";

    const topRow = document.createElement("div");
    topRow.style.display = "flex";
    topRow.style.alignItems = "start";
    topRow.style.justifyContent = "space-between";
    topRow.style.gap = "12px";

    const titleNode = document.createElement("h3");
    titleNode.textContent = "Congratulations!";
    titleNode.style.margin = "0";
    titleNode.style.fontSize = "0.98rem";
    titleNode.style.lineHeight = "1.3";

    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.setAttribute("aria-label", "Close notification");
    closeButton.textContent = "\u00D7";
    closeButton.style.border = "none";
    closeButton.style.background = "transparent";
    closeButton.style.color = "#8a7753";
    closeButton.style.cursor = "pointer";
    closeButton.style.fontSize = "1.45rem";
    closeButton.style.lineHeight = "1";
    closeButton.style.padding = "0";
    closeButton.style.width = "28px";
    closeButton.style.height = "28px";
    closeButton.style.flex = "0 0 28px";

    topRow.appendChild(titleNode);
    topRow.appendChild(closeButton);

    const messageNode = document.createElement("p");
    messageNode.textContent = REGISTER_SUCCESS_MESSAGE;
    messageNode.style.margin = "8px 0 0";
    messageNode.style.fontSize = "0.87rem";
    messageNode.style.lineHeight = "1.5";
    messageNode.style.color = "#5d5141";

    const buttonWrap = document.createElement("div");
    buttonWrap.style.display = "flex";
    buttonWrap.style.flexWrap = "wrap";
    buttonWrap.style.gap = "8px";
    buttonWrap.style.marginTop = "12px";

    const checkEmailButton = document.createElement("button");
    checkEmailButton.type = "button";
    checkEmailButton.textContent = "Check Email";
    checkEmailButton.style.padding = "9px 11px";
    checkEmailButton.style.borderRadius = "12px";
    checkEmailButton.style.border = "none";
    checkEmailButton.style.background = "linear-gradient(180deg, #d4b15a 0%, #c79a2b 100%)";
    checkEmailButton.style.color = "#fffaf2";
    checkEmailButton.style.cursor = "pointer";
    checkEmailButton.style.fontSize = "0.82rem";

    const watchTutorialButton = document.createElement("button");
    watchTutorialButton.type = "button";
    watchTutorialButton.textContent = "Watch Tutorial";
    watchTutorialButton.style.padding = "9px 11px";
    watchTutorialButton.style.borderRadius = "12px";
    watchTutorialButton.style.border = "1px solid rgba(199, 165, 75, 0.28)";
    watchTutorialButton.style.background = "#ffffff";
    watchTutorialButton.style.color = "#2b241b";
    watchTutorialButton.style.cursor = "pointer";
    watchTutorialButton.style.fontSize = "0.82rem";

    buttonWrap.appendChild(checkEmailButton);
    buttonWrap.appendChild(watchTutorialButton);
    dialogNode.appendChild(topRow);
    dialogNode.appendChild(messageNode);
    dialogNode.appendChild(buttonWrap);
    overlayNode.appendChild(dialogNode);
    document.body.appendChild(overlayNode);

    registerSuccessDialog = {
      overlayNode: overlayNode,
      closeButton: closeButton,
      messageNode: messageNode,
      checkEmailButton: checkEmailButton,
      watchTutorialButton: watchTutorialButton
    };

    closeButton.addEventListener("click", function () {
      hideRegisterSuccessPopup();
    });

    return registerSuccessDialog;
  }

  function showRegisterSuccessPopup(email) {
    if (registerSuccessToastVisible) {
      return;
    }

    const dialog = ensureRegisterSuccessDialog();
    dialog.messageNode.textContent = REGISTER_SUCCESS_MESSAGE;
    dialog.overlayNode.hidden = false;
    dialog.overlayNode.setAttribute("aria-hidden", "false");
    registerSuccessToastVisible = true;

    if (registerSuccessHideTimer) {
      window.clearTimeout(registerSuccessHideTimer);
    }
    registerSuccessHideTimer = window.setTimeout(function () {
      hideRegisterSuccessPopup();
    }, 4000);

    dialog.checkEmailButton.onclick = function () {
      const targetUrl = getEmailProviderUrl(email);
      window.open(targetUrl, "_blank", "noopener");
    };

    dialog.watchTutorialButton.onclick = function () {
      window.open("https://youtube.com/@GirffoN", "_blank", "noopener");
    };
  }

  function hideRegisterSuccessPopup() {
    if (!registerSuccessDialog) {
      return;
    }

    if (registerSuccessHideTimer) {
      window.clearTimeout(registerSuccessHideTimer);
      registerSuccessHideTimer = 0;
    }

    registerSuccessDialog.overlayNode.hidden = true;
    registerSuccessDialog.overlayNode.setAttribute("aria-hidden", "true");
    registerSuccessToastVisible = false;
  }

  function splitDisplayName(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    return {
      firstName: parts.shift() || "",
      lastName: parts.join(" ")
    };
  }

  function readStoredAvatar() {
    try {
      return String(window.localStorage.getItem(AVATAR_STORAGE_KEY) || "").trim();
    } catch (_error) {
      return "";
    }
  }

  function normalizeAvatarSource(value) {
    const avatarPath = String(value || "").trim();
    if (!avatarPath) {
      return "";
    }

    if (/^(?:https?:)?\/\//i.test(avatarPath) || avatarPath.startsWith("data:")) {
      return avatarPath;
    }

    return buildAppPath(avatarPath);
  }

  function resolveAvatarSource() {
    if (!currentUser) {
      return "";
    }

    return normalizeAvatarSource(currentUser.avatar || "");
  }

  function renderUserAvatar() {
    if (!userAvatarWrap) {
      return;
    }

    const avatarSrc = resolveAvatarSource();

    if (avatarSrc !== "") {
      const avatarImage = document.createElement("img");
      avatarImage.className = "gf-account-profile-photo";
      avatarImage.src = avatarSrc;
      avatarImage.width = 56;
      avatarImage.height = 56;
      avatarImage.alt = (currentUser && currentUser.name ? currentUser.name : "GirffoN Member") + " avatar";
      userAvatarWrap.replaceChildren(avatarImage);
      return;
    }

    userAvatarWrap.innerHTML = '<i class="fa-solid fa-user"></i>';
  }

  function setActiveView(viewName) {
    if (guestView) {
      guestView.style.display = viewName === "guest" ? "flex" : "none";
    }

    if (userView) {
      userView.style.display = viewName === "user" ? "flex" : "none";
    }
  }

  function setPanelVisibility(visible) {
    panel.setAttribute("data-visible", visible ? "true" : "false");
    panel.setAttribute("aria-hidden", visible ? "false" : "true");
    overlay.hidden = !visible;
    document.body.style.overflow = visible ? "hidden" : "";
  }

  function normalizeUser(user) {
    if (!user || typeof user !== "object") {
      return null;
    }

    const firstName = String(user.first_name || "").trim();
    const lastName = String(user.last_name || "").trim();
    const fullName = [firstName, lastName].filter(Boolean).join(" ").trim();

    return {
      id: Number(user.id || 0),
      username: String(user.username || "").trim(),
      name: fullName || String(user.username || "GirffoN Member"),
      email: String(user.email || "").trim(),
      phone: String(user.phone || "").trim(),
      country: String(user.country || "").trim(),
      city: String(user.city || "").trim(),
      address: String(user.address || "").trim(),
      avatar: String(user.avatar || "").trim()
    };
  }

  async function syncProfileAvatar() {
    if (!isAuthenticated || !currentUser) {
      renderUserAvatar();
      return "";
    }

    try {
      const response = await fetch(PROFILE_DATA_URL, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json"
        }
      });
      const payload = await readTextResponse(response);

      if (!payload.ok || !payload.json || !payload.json.success || !payload.json.user) {
        renderUserAvatar();
        return "";
      }

      const avatar = String(payload.json.user.avatar || "").trim();
      currentUser.avatar = avatar;
      if (avatar) {
        try {
          window.localStorage.setItem(AVATAR_STORAGE_KEY, avatar);
        } catch (_error) {
        }
      }

      renderUserAvatar();
      return avatar;
    } catch (_error) {
      renderUserAvatar();
      return "";
    }
  }

  function updateAccountView() {
    if (isAuthenticated && currentUser) {
      setActiveView("user");
      if (userNameEl) {
        userNameEl.textContent = currentUser.name || "GirffoN Member";
      }
      if (userEmailEl) {
        userEmailEl.textContent = currentUser.email || currentUser.username || "member@girffon.local";
      }
    } else {
      setActiveView("guest");
      if (userNameEl) {
        userNameEl.textContent = "User";
      }
      if (userEmailEl) {
        userEmailEl.textContent = "user@example.com";
      }
    }

    renderUserAvatar();
  }

  function setAuthUser(user, shouldDispatch) {
    currentUser = user ? normalizeUser(user) : null;
    isAuthenticated = Boolean(currentUser);
    updateAccountView();

    if (isAuthenticated) {
      syncProfileAvatar().catch(function () {
        return "";
      });
    }

    if (shouldDispatch !== false) {
      dispatchAuthUpdated();
    }
  }

  function setButtonLabel(button, text) {
    if (!button) {
      return;
    }

    const label = button.querySelector("span");
    if (label) {
      label.textContent = text;
      return;
    }

    button.textContent = text;
  }

  function toggleGroup(group, visible) {
    if (!group) {
      return;
    }

    group.style.display = visible ? "" : "none";
  }

  function setRegisterMode(enabled) {
    isRegisterMode = Boolean(enabled);
    isForgotPasswordMode = false;

    toggleGroup(loginIdentifierGroup, !isRegisterMode);
    toggleGroup(loginPasswordGroup, true);
    toggleGroup(registerNameGroup, isRegisterMode);
    toggleGroup(registerEmailGroup, isRegisterMode);
    toggleGroup(registerPhoneGroup, isRegisterMode);
    toggleGroup(registerPromotionalEmailsGroup, isRegisterMode);
    toggleGroup(registerCatalogEmailsGroup, isRegisterMode);
    toggleGroup(forgotEmailGroup, false);

    if (authRow) {
      authRow.style.display = isRegisterMode ? "none" : "";
    }

    if (loginBtn) {
      loginBtn.style.display = isRegisterMode ? "none" : "";
      setButtonLabel(loginBtn, "Sign in");
    }

    if (loginIdentifierInput) {
      loginIdentifierInput.required = !isRegisterMode;
    }

    if (loginPasswordInput) {
      loginPasswordInput.autocomplete = isRegisterMode ? "new-password" : "current-password";
    }

    if (registerNameInput) {
      registerNameInput.required = isRegisterMode;
    }

    if (registerEmailInput) {
      registerEmailInput.required = isRegisterMode;
    }

    if (forgotEmailInput) {
      forgotEmailInput.required = false;
    }

    if (authHeadTitle) {
      authHeadTitle.textContent = isRegisterMode ? "Create account" : "Sign in";
    }

    if (authHeadCopy) {
      authHeadCopy.textContent = isRegisterMode
        ? "Create your GirffoN account to access saved designs, orders, and your customer profile."
        : "Use your GirffoN account to access saved designs, orders, and your premium profile.";
    }

    setButtonLabel(signupBtn, isRegisterMode ? "Create my account" : "Create an account");
    if (forgotBtn) {
      forgotBtn.style.display = isRegisterMode ? "none" : "";
      setButtonLabel(forgotBtn, "Forgot password?");
    }
  }

  function setForgotPasswordMode(enabled) {
    isForgotPasswordMode = Boolean(enabled);
    isRegisterMode = false;

    toggleGroup(loginIdentifierGroup, !isForgotPasswordMode);
    toggleGroup(loginPasswordGroup, !isForgotPasswordMode);
    toggleGroup(registerNameGroup, false);
    toggleGroup(registerEmailGroup, false);
    toggleGroup(registerPhoneGroup, false);
    toggleGroup(registerPromotionalEmailsGroup, false);
    toggleGroup(registerCatalogEmailsGroup, false);
    toggleGroup(forgotEmailGroup, isForgotPasswordMode);

    if (authRow) {
      authRow.style.display = isForgotPasswordMode ? "none" : "";
    }

    if (loginBtn) {
      loginBtn.style.display = "";
      setButtonLabel(loginBtn, isForgotPasswordMode ? "Send reset link" : "Sign in");
    }

    if (signupBtn) {
      signupBtn.style.display = "";
      setButtonLabel(signupBtn, isForgotPasswordMode ? "Back to login" : "Create an account");
    }

    if (loginIdentifierInput) {
      loginIdentifierInput.required = !isForgotPasswordMode;
    }

    if (loginPasswordInput) {
      loginPasswordInput.required = !isForgotPasswordMode;
      loginPasswordInput.autocomplete = "current-password";
    }

    if (registerNameInput) {
      registerNameInput.required = false;
    }

    if (registerEmailInput) {
      registerEmailInput.required = false;
    }

    if (forgotEmailInput) {
      forgotEmailInput.required = isForgotPasswordMode;
    }

    if (authHeadTitle) {
      authHeadTitle.textContent = isForgotPasswordMode ? "Forgot password" : "Sign in";
    }

    if (authHeadCopy) {
      authHeadCopy.textContent = isForgotPasswordMode
        ? "Enter your email address and GirffoN will send you a secure password reset link."
        : "Use your GirffoN account to access saved designs, orders, and your premium profile.";
    }

    if (forgotBtn) {
      forgotBtn.style.display = isForgotPasswordMode ? "none" : "";
      setButtonLabel(forgotBtn, "Forgot password?");
    }
  }

  function resetGuestState() {
    setForgotPasswordMode(false);
    setRegisterMode(false);
    if (loginForm) {
      loginForm.reset();
    }
    if (forgotEmailInput) {
      forgotEmailInput.value = "";
    }
    if (registerPromotionalEmailsInput) {
      registerPromotionalEmailsInput.checked = false;
    }
    if (registerCatalogEmailsInput) {
      registerCatalogEmailsInput.checked = false;
    }
    setAuthStatus("Sign in to continue with your GirffoN account, or create one to save profile and preference details.", false);
  }

  async function readTextResponse(response) {
    const text = await response.text();
    let json = null;

    try {
      json = text ? JSON.parse(text) : null;
    } catch (_error) {
      json = null;
    }

    return {
      ok: response.ok,
      text: text,
      json: json
    };
  }

  async function syncSession() {
    if (isSyncingSession) {
      return currentUser;
    }

    isSyncingSession = true;

    try {
      const response = await fetch(SESSION_URL, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json"
        }
      });
      const payload = await readTextResponse(response);

      if (payload.json && payload.json.authenticated && payload.json.user) {
        setAuthUser(payload.json.user, false);
        return currentUser;
      }

      setAuthUser(null, false);
      return null;
    } catch (_error) {
      return currentUser;
    } finally {
      isSyncingSession = false;
      updateAccountView();
    }
  }

  async function loginRemote(identifier, password) {
    const formData = new URLSearchParams();
    formData.set("identifier", identifier);
    formData.set("username", identifier);
    formData.set("password", password);

    const response = await fetch(LOGIN_URL, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      body: formData.toString()
    });

    const payload = await readTextResponse(response);

    if (!payload.ok) {
      throw new Error((payload.json && payload.json.message) || payload.text.trim() || "Unable to sign in.");
    }

    if (payload.json && payload.json.ok && payload.json.user) {
      setAuthUser(payload.json.user);
      return currentUser;
    }

    const sessionUser = await syncSession();
    if (sessionUser) {
      return sessionUser;
    }

    throw new Error(payload.text.trim() || "Unable to sign in.");
  }

  async function registerRemote(name, email, phone, password, preferences) {
    const nameParts = splitDisplayName(name);
    const formData = new URLSearchParams();
    formData.set("first_name", nameParts.firstName || name);
    formData.set("last_name", nameParts.lastName || "-");
    formData.set("email", email);
    formData.set("phone", phone);
    formData.set("password", password);
    formData.set("accepts_promotional_emails", preferences && preferences.acceptsPromotionalEmails ? "1" : "0");
    formData.set("accepts_catalog_emails", preferences && preferences.acceptsCatalogEmails ? "1" : "0");

    const response = await fetch(REGISTER_URL, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      body: formData.toString()
    });

    const payload = await readTextResponse(response);

    if (!payload.ok) {
      throw new Error((payload.json && payload.json.message) || payload.text.trim() || "Unable to create your account.");
    }

    if (payload.json && payload.json.ok && payload.json.user) {
      setAuthUser(payload.json.user);
      return {
        user: currentUser,
        payload: payload.json
      };
    }

    const sessionUser = await syncSession();
    if (sessionUser) {
      return {
        user: sessionUser,
        payload: payload.json || null
      };
    }

    const loggedInUser = await loginRemote(email, password);
    return {
      user: loggedInUser,
      payload: payload.json || null
    };
  }

  async function logoutRemote() {
    const response = await fetch(LOGOUT_URL, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json"
      },
      body: JSON.stringify({})
    });

    const payload = await readTextResponse(response);
    if (!payload.ok) {
      throw new Error((payload.json && payload.json.message) || payload.text.trim() || "Unable to sign out.");
    }

    setAuthUser(null);
    try {
      window.localStorage.removeItem(AVATAR_STORAGE_KEY);
    } catch (_error) {
    }
  }

  async function handleLoginSubmit(event) {
    event.preventDefault();
    setAuthStatus("", false);

    if (isForgotPasswordMode) {
      await handleForgotPasswordSubmit();
      return;
    }

    if (isRegisterMode) {
      await handleRegisterSubmit();
      return;
    }

    const identifier = String(loginIdentifierInput ? loginIdentifierInput.value : "").trim();
    const password = String(loginPasswordInput ? loginPasswordInput.value : "").trim();

    if (!identifier) {
      setAuthStatus("Enter your username, email, or mobile to sign in.", true);
      loginIdentifierInput && loginIdentifierInput.focus();
      return;
    }

    if (!password) {
      setAuthStatus("Enter your password to sign in.", true);
      loginPasswordInput && loginPasswordInput.focus();
      return;
    }

    try {
      await loginRemote(identifier, password);
      setAuthStatus("Signed in successfully.", false);
      updateAccountView();
    } catch (error) {
      setAuthStatus(error && error.message ? error.message : "Unable to sign in.", true);
    }
  }

  async function handleRegisterSubmit() {
    const fullName = String(registerNameInput ? registerNameInput.value : "").trim();
    const email = String(registerEmailInput ? registerEmailInput.value : "").trim().toLowerCase();
    const phone = String(registerPhoneInput ? registerPhoneInput.value : "").trim();
    const password = String(loginPasswordInput ? loginPasswordInput.value : "").trim();
    const acceptsPromotionalEmails = Boolean(registerPromotionalEmailsInput && registerPromotionalEmailsInput.checked);
    const acceptsCatalogEmails = Boolean(registerCatalogEmailsInput && registerCatalogEmailsInput.checked);

    if (!fullName) {
      setAuthStatus("Full name is required.", true);
      registerNameInput && registerNameInput.focus();
      return;
    }

    if (!EMAIL_PATTERN.test(email)) {
      setAuthStatus("Enter a valid email address.", true);
      registerEmailInput && registerEmailInput.focus();
      return;
    }

    if (password.length < 6) {
      setAuthStatus("Password must be at least 6 characters.", true);
      loginPasswordInput && loginPasswordInput.focus();
      return;
    }

    try {
      const result = await registerRemote(fullName, email, phone, password, {
        acceptsPromotionalEmails: acceptsPromotionalEmails,
        acceptsCatalogEmails: acceptsCatalogEmails
      });
      setRegisterMode(false);
      setAuthStatus("Congratulations! Your GirffoN account has been created. Please check your email.", false);
      updateAccountView();
      setPanelVisibility(false);
      showRegisterSuccessPopup(email);
      return result;
    } catch (error) {
      setAuthStatus(error && error.message ? error.message : "Unable to create your account.", true);
    }
  }

  async function forgotPasswordRemote(email) {
    const response = await fetch(FORGOT_PASSWORD_URL, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        email: email
      })
    });

    const payload = await readTextResponse(response);
    if (!payload.ok) {
      throw new Error((payload.json && payload.json.message) || payload.text.trim() || "Unable to send reset link.");
    }

    if (!payload.json || !payload.json.success) {
      throw new Error((payload.json && payload.json.message) || "Unable to send reset link.");
    }

    return payload.json;
  }

  async function handleForgotPasswordSubmit() {
    const email = String(forgotEmailInput ? forgotEmailInput.value : "").trim().toLowerCase();

    if (!EMAIL_PATTERN.test(email)) {
      setAuthStatus("Enter a valid email address.", true);
      forgotEmailInput && forgotEmailInput.focus();
      return;
    }

    try {
      const payload = await forgotPasswordRemote(email);
      setAuthStatus(payload.message || "If that email address exists, a reset link has been sent.", false);
    } catch (error) {
      setAuthStatus(error && error.message ? error.message : "Unable to send reset link.", true);
    }
  }

  function goToProfileSection(hash) {
    const sectionHash = typeof hash === "string" && hash ? hash : "#gfProfileDetails";
    setPanelVisibility(false);
    window.location.href = PROFILE_URL + sectionHash;
  }

  trigger.addEventListener("click", async function (event) {
    event.preventDefault();
    await syncSession();
    if (!isAuthenticated) {
      resetGuestState();
    }
    updateAccountView();
    if (isAuthenticated) {
      syncProfileAvatar().catch(function () {
        return "";
      });
    }
    setPanelVisibility(true);
  });

  closeBtn && closeBtn.addEventListener("click", function () {
    setPanelVisibility(false);
  });

  overlay.addEventListener("click", function () {
    setPanelVisibility(false);
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      hideRegisterSuccessPopup();
      setPanelVisibility(false);
    }
  });

  loginForm && loginForm.addEventListener("submit", handleLoginSubmit);

  signupBtn && signupBtn.addEventListener("click", async function () {
    if (isForgotPasswordMode) {
      setForgotPasswordMode(false);
      setAuthStatus("Use your username, email, or mobile number to sign in.", false);
      loginIdentifierInput && loginIdentifierInput.focus();
      return;
    }

    if (!isRegisterMode) {
      setRegisterMode(true);
      setAuthStatus("Complete the fields below to create your account.", false);
      registerNameInput && registerNameInput.focus();
      return;
    }

    await handleRegisterSubmit();
  });

  forgotBtn && forgotBtn.addEventListener("click", function () {
    setForgotPasswordMode(true);
    setAuthStatus("Enter your email address and GirffoN will send a secure reset link.", false);
    forgotEmailInput && forgotEmailInput.focus();
  });

  googleLoginBtn && googleLoginBtn.addEventListener("click", function () {
    setAuthStatus("Google login will be available soon.", false);
  });

  appleLoginBtn && appleLoginBtn.addEventListener("click", function () {
    setAuthStatus("Apple login will be available soon.", false);
  });

  logoutBtn && logoutBtn.addEventListener("click", async function (event) {
    event.preventDefault();

    try {
      await logoutRemote();
      resetGuestState();
      setAuthStatus("Signed out from your GirffoN account.", false);
      setPanelVisibility(false);
    } catch (error) {
      setAuthStatus(error && error.message ? error.message : "Unable to sign out.", true);
    }
  });

  manageAccountBtn && manageAccountBtn.addEventListener("click", function () {
    goToProfileSection("#gfProfileDetails");
  });

  myDesignsBtn && myDesignsBtn.addEventListener("click", function () {
    goToProfileSection("#gfMyDesigns");
  });

  orderHistoryBtn && orderHistoryBtn.addEventListener("click", function () {
    goToProfileSection("#gfRecentOrders");
  });

  paymentMethodsBtn && paymentMethodsBtn.addEventListener("click", function () {
    goToProfileSection("#gfPayments");
  });

  shippingAddressesBtn && shippingAddressesBtn.addEventListener("click", function () {
    goToProfileSection("#gfAddressBook");
  });

  document.addEventListener("girffon:auth-updated", function () {
    syncSession().catch(function () {
      return null;
    });
  });

  resetGuestState();
  updateAccountView();
  syncSession().catch(function () {
    return null;
  });

  window.GIRFFON_AUTH = {
    ensureSession: syncSession,
    login: loginRemote,
    register: registerRemote,
    logout: logoutRemote
  };
})();