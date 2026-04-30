document.addEventListener("DOMContentLoaded", () => {
  const footerPanels = window.gfFooterPanels;
  if (!footerPanels) {
    return;
  }

  function detectAppBasePath() {
    const scriptNode = Array.from(document.scripts).find((node) =>
      /\/JS\/contact-panel\.js(?:\?|$)/i.test(node.src || "")
    );

    if (scriptNode?.src) {
      const scriptUrl = new URL(scriptNode.src, window.location.href);
      return scriptUrl.pathname.replace(/\/JS\/contact-panel\.js$/i, "");
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

  const contactTriggerNodes = Array.from(
    new Set([
      ...document.querySelectorAll("[data-gf-contact-trigger]"),
      ...document.querySelectorAll("#gfContactTrigger")
    ])
  );
  const contactModal = document.getElementById("gfContactModal");
  const contactOverlay = document.getElementById("gfContactOverlay");
  const contactClose = document.getElementById("gfContactClose");
  const contactForm = document.getElementById("gfContactForm");
  const contactStatus = document.getElementById("gfContactStatus");
  const contactName = document.getElementById("gfContactName");
  const contactEmail = document.getElementById("gfContactEmail");
  const contactSubject = document.getElementById("gfContactSubject");
  const contactMessage = document.getElementById("gfContactMessage");

  if (!contactTriggerNodes.length || !contactModal || !contactOverlay || !contactClose || !contactForm) {
    return;
  }

  let lastFocusedElement = null;

  window.gfContactFormConfig = window.gfContactFormConfig || {
    enabled: true,
    endpoint: buildAppPath("backend/contact/save-message.php"),
    method: "POST",
    headers: {}
  };

  function t(key, fallback) {
    return footerPanels.t ? footerPanels.t(key, fallback) : fallback;
  }

  function markFieldInvalid(field, isInvalid) {
    if (!field) {
      return;
    }

    field.setAttribute("aria-invalid", isInvalid ? "true" : "false");
  }

  function setContactStatus(type, message) {
    if (!contactStatus) {
      return;
    }

    contactStatus.textContent = message;
    contactStatus.classList.remove("is-success", "is-error");

    if (type === "success") {
      contactStatus.classList.add("is-success");
    }

    if (type === "error") {
      contactStatus.classList.add("is-error");
    }
  }

  function isOpen() {
    return contactModal.dataset.visible === "true";
  }

  function open() {
    if (typeof footerPanels.closeFaqModal === "function") {
      footerPanels.closeFaqModal(false);
    }
    if (typeof footerPanels.closeReturnModal === "function") {
      footerPanels.closeReturnModal(false);
    }
    if (typeof footerPanels.closeTrackModal === "function") {
      footerPanels.closeTrackModal(false);
    }

    lastFocusedElement = document.activeElement;
    contactOverlay.hidden = false;
    contactModal.dataset.visible = "true";
    contactModal.setAttribute("aria-hidden", "false");
    footerPanels.refreshFooterPanelBodyState();

    window.setTimeout(() => {
      contactName?.focus();
    }, 0);
  }

  function close(restoreFocus = true) {
    contactOverlay.hidden = true;
    contactModal.dataset.visible = "false";
    contactModal.setAttribute("aria-hidden", "true");
    footerPanels.refreshFooterPanelBodyState();

    if (restoreFocus && lastFocusedElement && typeof lastFocusedElement.focus === "function") {
      lastFocusedElement.focus();
    }
  }

  function validateForm() {
    if (!contactName || !contactEmail || !contactSubject || !contactMessage) {
      return false;
    }

    const requiredFields = [contactName, contactEmail, contactSubject, contactMessage];
    requiredFields.forEach((field) => markFieldInvalid(field, false));

    if (
      !contactName.value.trim() ||
      !contactEmail.value.trim() ||
      !contactSubject.value.trim() ||
      !contactMessage.value.trim()
    ) {
      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          markFieldInvalid(field, true);
        }
      });
      setContactStatus("error", t("formRequiredError", "Please fill in all required fields."));
      return false;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailPattern.test(contactEmail.value.trim())) {
      markFieldInvalid(contactEmail, true);
      setContactStatus("error", t("formEmailError", "Please enter a valid email address."));
      return false;
    }

    return true;
  }

  async function submitContact(payload) {
    const config = window.gfContactFormConfig || {};

    if (!config.enabled || !config.endpoint) {
      return { mode: "preview" };
    }

    const response = await fetch(config.endpoint, {
      method: config.method || "POST",
      headers: {
        "Content-Type": "application/json",
        ...(config.headers || {})
      },
      body: JSON.stringify(payload)
    });

    const result = await response.json().catch(() => null);
    if (!response.ok || !(result && result.ok)) {
      throw new Error((result && result.message) || `Contact request failed with status ${response.status}`);
    }

    return { mode: "live", payload: result };
  }

  window.gfContactPanel = {
    close,
    isOpen,
    open,
    submitContact
  };

  contactTriggerNodes.forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
      event.preventDefault();
      setContactStatus("", "");
      open();
    });
  });

  contactClose.addEventListener("click", () => close(true));
  contactOverlay.addEventListener("click", () => close(true));

  contactForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (!validateForm()) {
      return;
    }

    const payload = {
      fullName: contactName.value.trim(),
      email: contactEmail.value.trim(),
      subject: contactSubject.value.trim(),
      message: contactMessage.value.trim()
    };

    try {
      await submitContact(payload);
      contactForm.reset();
      [contactName, contactEmail, contactSubject, contactMessage].forEach((field) => {
        markFieldInvalid(field, false);
      });
      setContactStatus("success", t("contactSuccessMessage", "Your message was sent successfully."));
    } catch (error) {
      setContactStatus("error", t("trackStatusError", "Something went wrong. Please try again."));
    }
  });
});