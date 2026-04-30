(function () {
  if (window.__gfServiceModalInit === true) {
    return;
  }

  const fallbackDetailsByKey = {
    shipping: {
      title: "Free Shipping",
      lead: "Enjoy free delivery on qualifying orders across Italy and selected EU destinations.",
      points: [
        "Italy: Free shipping on orders over EUR50",
        "Europe: Free shipping on orders over EUR90",
        "United States: Free shipping on orders over EUR140",
        "Australia: Free shipping on orders over EUR160",
        "Fully tracked delivery",
        "Fast dispatch with GLS / BRT / DHL",
        "Estimated delivery: 1-3 business days in Italy",
        "International delivery: 3-8 business days"
      ],
      outro: "We process orders quickly so your items arrive fast, secure, and fully tracked worldwide."
    },
    limited: {
      title: "Limited Editions",
      lead: "Exclusive drops made for customers who want something unique.",
      points: [
        "Special designs in limited quantities",
        "Premium collections and seasonal releases",
        "Once sold out, they may not return",
        "Unique styles not found in regular stock",
        "Early access launches and new arrivals"
      ],
      outro: "Designed to stand out. Made for those who move first."
    },
    secure: {
      title: "Secure Checkout",
      lead: "Your payment and personal data are protected with trusted security standards.",
      points: [
        "SSL encrypted checkout",
        "Secure payments by trusted providers",
        "Safe customer data handling",
        "Instant order confirmation by email",
        "Reliable and transparent checkout process"
      ],
      outro: "Shop safely with confidence from order to delivery."
    },
    returns: {
      title: "Easy Returns",
      lead: "Shop with confidence. If something is not right, returning your order is simple.",
      points: [
        "Return window: 14 days",
        "Items must be unused and in original condition",
        "Quick return request by email or contact form",
        "Return shipping available via GLS / BRT / DHL",
        "Refund processed after inspection"
      ],
      outro: "Our support team makes the return process smooth and hassle-free."
    }
  };

  function initServiceModal() {
    const serviceModal = document.getElementById("gfServiceModal");
    const serviceModalTitle = document.getElementById("gfServiceModalTitle");
    const serviceModalLead = document.getElementById("gfServiceModalLead");
    const serviceModalList = document.getElementById("gfServiceModalList");
    const serviceModalOutro = document.getElementById("gfServiceModalOutro");
    const serviceModalClose = document.getElementById("gfServiceModalClose");
    const serviceTriggers = Array.from(document.querySelectorAll(".service-btn[data-service-key], .service-card[data-service-key]"));

    if (!serviceModal || !serviceModalTitle || !serviceModalLead || !serviceModalList || !serviceModalOutro || !serviceTriggers.length) {
      return;
    }

    window.__gfServiceModalInit = true;

    let lastTrigger = null;

    function setModalVisibility(visible) {
      serviceModal.hidden = !visible;
      serviceModal.setAttribute("data-visible", visible ? "true" : "false");
      serviceModal.setAttribute("aria-hidden", visible ? "false" : "true");
      document.body.style.overflow = visible ? "hidden" : "";

      if (!visible && lastTrigger) {
        lastTrigger.focus();
      }
    }

    function resolveDetails(card) {
      const serviceKey = card.dataset.serviceKey || "";
      const fallback = fallbackDetailsByKey[serviceKey] || null;
      const title = card.querySelector(".service-front h3")?.textContent?.trim()
        || card.querySelector(".service-back h4")?.textContent?.trim()
        || fallback?.title
        || "Service details";
      const lead = card.querySelector(".service-back > p")?.textContent?.trim()
        || fallback?.lead
        || "";

      return {
        title,
        lead,
        points: Array.isArray(fallback?.points) ? fallback.points : [],
        outro: fallback?.outro || ""
      };
    }

    function openServiceModal(card, triggerEl) {
      const details = resolveDetails(card);

      lastTrigger = triggerEl || null;
      serviceModalTitle.textContent = details.title;
      serviceModalLead.textContent = details.lead;
      serviceModalOutro.textContent = details.outro;
      serviceModalList.innerHTML = details.points.map((point) => `<li>${point}</li>`).join("");
      setModalVisibility(true);
    }

    function closeServiceModal() {
      setModalVisibility(false);
    }

    serviceTriggers.forEach((trigger) => {
      trigger.addEventListener("click", (event) => {
        const card = trigger.closest(".service-card") || trigger;
        if (!card) {
          return;
        }

        event.preventDefault();
        openServiceModal(card, trigger);
      });

      if (trigger.matches("button")) {
        return;
      }

      trigger.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" && event.key !== " ") {
          return;
        }

        const card = trigger.closest(".service-card") || trigger;
        if (!card) {
          return;
        }

        event.preventDefault();
        openServiceModal(card, trigger);
      });
    });

    serviceModal.addEventListener("click", (event) => {
      if (event.target.closest("[data-service-close]")) {
        closeServiceModal();
      }
    });

    serviceModalClose?.addEventListener("click", closeServiceModal);

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && serviceModal.getAttribute("data-visible") === "true") {
        closeServiceModal();
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initServiceModal, { once: true });
  } else {
    initServiceModal();
  }
})();