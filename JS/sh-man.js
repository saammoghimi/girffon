document.addEventListener("DOMContentLoaded", function () {
  function safeReadArray(key) {
    try {
      const raw = localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function safeWriteArray(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (_error) {
      // Ignore storage failures.
    }
  }

  function parsePrice(value) {
    return Number.parseFloat(String(value || "0").replace(/[^0-9.,]/g, "").replace(",", ".")) || 0;
  }

  function buildSku(category, product) {
    return `${category}-${String(product.title || "girffon-product")}`
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "") || "girffon-product";
  }

  async function addToCart(category, product, colorValue, imageUrl) {
    const priceNumber = parsePrice(product.price);
    const sku = buildSku(category, product);
    const payload = {
      id: sku,
      sku: sku,
      name: product.title,
      title: product.title,
      price: priceNumber,
      priceNumber: priceNumber,
      color: colorValue,
      colorName: colorValue,
      size: "One Size",
      image: imageUrl,
      img: imageUrl,
      quantity: 1,
      qty: 1
    };

    if (window.GirffonCartApi && typeof window.GirffonCartApi.addItem === "function") {
      await window.GirffonCartApi.addItem(payload);
      return;
    }

    const cartItems = safeReadArray("girffon_cart");
    const existingIndex = cartItems.findIndex(function (entry) {
      return String(entry.id || entry.code || "") === sku && String(entry.color || "") === String(colorValue || "");
    });

    if (existingIndex >= 0) {
      cartItems[existingIndex].qty = (Number(cartItems[existingIndex].qty) || 1) + 1;
    } else {
      cartItems.push({
        id: sku,
        code: sku,
        title: product.title,
        name: product.title,
        price: priceNumber,
        priceNumber: priceNumber,
        color: colorValue,
        colorName: colorValue,
        size: "One Size",
        image: imageUrl,
        img: imageUrl,
        qty: 1,
        quantity: 1
      });
    }

    safeWriteArray("girffon_cart", cartItems);
  }

  const colorSet = [
    "#f1c9bb", "#e8d9c9", "#d7d7d7", "#caa27f",
    "#b9c7d9", "#2d3a56", "#8d9db6", "#d68c8c",
    "#8b6f61", "#5c5c5c", "#f5f0ea", "#000000"
  ];

const productData = {
  men: [
    {
      title: "Men Essential T-Shirt",
      price: "39,00 €",
      images: [
        "Cart/products/tshirt-men/50/400.png",
        "Cart/products/tshirt-men/50/400-1.png",
        "Cart/products/tshirt-men/50/400-2.png",
        "Cart/products/tshirt-men/50/400-3.png"
      ]
    },
    {
      title: "Men France T-Shirt",
      price: "45,00 €",
      images: [
        "Cart/products/tshirt-men/Men france/Bk/400.jpg",
        "Cart/products/tshirt-men/Men france/Bk/400-1.jpg",
        "Cart/products/tshirt-men/Men france/Bk/400-2.jpg",
        "Cart/products/tshirt-men/Men france/Bk/400-3.jpg"
      ]
    },
    {
      title: "Men Italy T-Shirt",
      price: "42,00 €",
      images: [
        "Cart/products/tshirt-men/men italy/Bk/400.jpg",
        "Cart/products/tshirt-men/men italy/Bk/400-1.jpg",
        "Cart/products/tshirt-men/men italy/Bk/400-2.jpg",
        "Cart/products/tshirt-men/men italy/Bk/400-3.jpg"
      ]
    },
    {
      title: "Men USA T-Shirt",
      price: "44,00 €",
      images: [
        "Cart/products/tshirt-men/men usa/Bk/400.jpg",
        "Cart/products/tshirt-men/men usa/Bk/400-1.jpg",
        "Cart/products/tshirt-men/men usa/Bk/400-2.jpg",
        "Cart/products/tshirt-men/men usa/Bk/400-3.jpg"
      ]
    }
  ],

  women: [
    {
      title: "Women Essential T-Shirt",
      price: "41,00 €",
      images: [
        "Cart/products/tshirt-women/50/400.png",
        "Cart/products/tshirt-women/50/400-1.png",
        "Cart/products/tshirt-women/50/400-2.png",
        "Cart/products/tshirt-women/50/400-3.png"
      ]
    },
    {
      title: "Women France T-Shirt",
      price: "43,00 €",
      images: [
        "Cart/products/tshirt-women/women france/Bk/400.jpg",
        "Cart/products/tshirt-women/women france/Bk/400-1.jpg",
        "Cart/products/tshirt-women/women france/Bk/400-2.jpg",
        "Cart/products/tshirt-women/women france/Bk/400-3.jpg"
      ]
    },
    {
      title: "Women Italy T-Shirt",
      price: "44,00 €",
      images: [
        "Cart/products/tshirt-women/Women italy/Bk/400.jpg",
        "Cart/products/tshirt-women/Women italy/Bk/400-1.jpg",
        "Cart/products/tshirt-women/Women italy/Bk/400-2.jpg",
        "Cart/products/tshirt-women/Women italy/Bk/400-3.jpg"
      ]
    },
    {
      title: "Women Japan T-Shirt",
      price: "48,00 €",
      images: [
        "Cart/products/tshirt-women/Women japon/Bk/400.jpg",
        "Cart/products/tshirt-women/Women japon/Bk/400-1.jpg",
        "Cart/products/tshirt-women/Women japon/Bk/400-2.jpg",
        "Cart/products/tshirt-women/Women japon/Bk/400-3.jpg"
      ]
    }
  ],

  kids: [
    {
      title: "Kids Basic T-Shirt",
      price: "29,00 €",
      images: [
        "Cart/products/tshirt-men/47/400.png",
        "Cart/products/tshirt-men/47/400-1.png",
        "Cart/products/tshirt-men/47/400-2.png",
        "Cart/products/tshirt-men/47/400-3.png"
      ]
    },
    {
      title: "Kids Color T-Shirt",
      price: "31,00 €",
      images: [
        "Cart/products/tshirt-men/48/400.png",
        "Cart/products/tshirt-men/48/400-1.png",
        "Cart/products/tshirt-men/48/400-2.png",
        "Cart/products/tshirt-men/48/400-3.png"
      ]
    },
    {
      title: "Kids Smile T-Shirt",
      price: "30,00 €",
      images: [
        "Cart/products/tshirt-men/49/400.png",
        "Cart/products/tshirt-men/49/400-1.png",
        "Cart/products/tshirt-men/49/400-2.png",
        "Cart/products/tshirt-men/49/400-3.png"
      ]
    }
  ]
};
  function createCard(product) {
    const colorsHtml = colorSet.map((color, index) => `
      <span class="gx25-color ${index === 0 ? "active" : ""}" style="background:${color};"></span>
    `).join("");

    return `
      <article class="gx25-card">
        <span class="gx25-badge">Novità</span>

        <button class="gx25-fav" aria-label="Add to wishlist">
          <i class="fa-regular fa-heart"></i>
        </button>

        <div class="gx25-image-box">
          <button class="gx25-inner-nav gx25-inner-prev" aria-label="Previous image">
            <span>&#10094;</span>
          </button>

          <img class="gx25-main-image" src="${product.images[0]}" alt="${product.title}">

          <button class="gx25-inner-nav gx25-inner-next" aria-label="Next image">
            <span>&#10095;</span>
          </button>
        </div>

        <h3 class="gx25-title">${product.title}</h3>
        <p class="gx25-price">${product.price}</p>

        <div class="gx25-colors">${colorsHtml}</div>

        <button class="gx25-enter" type="button">Enter</button>
      </article>
    `;
  }

  document.querySelectorAll(".gx25-category-section").forEach(section => {
    const category = section.dataset.category;
    const products = productData[category] || [];
    const track = section.querySelector(".gx25-track");
    const prevBtn = section.querySelector(".gx25-outer-prev");
    const nextBtn = section.querySelector(".gx25-outer-next");

    if (!track || !prevBtn || !nextBtn) return;

    track.innerHTML = products.map(createCard).join("");

    let outerIndex = 0;
    const gap = 24;

    function getVisibleCards() {
      if (window.innerWidth <= 600) return 1;
      if (window.innerWidth <= 900) return 2;
      if (window.innerWidth <= 1280) return 3;
      return 5;
    }

    function getCards() {
      return track.querySelectorAll(".gx25-card");
    }

    function getCardWidth() {
      const firstCard = track.querySelector(".gx25-card");
      if (!firstCard) return 0;
      return firstCard.offsetWidth + gap;
    }

    function getMaxOuterIndex() {
      return Math.max(0, getCards().length - getVisibleCards());
    }

    function updateOuterSlider() {
      const max = getMaxOuterIndex();
      if (outerIndex < 0) outerIndex = 0;
      if (outerIndex > max) outerIndex = max;
      track.style.transform = `translateX(-${outerIndex * getCardWidth()}px)`;
    }

    nextBtn.addEventListener("click", function () {
      if (outerIndex < getMaxOuterIndex()) {
        outerIndex++;
        updateOuterSlider();
      }
    });

    prevBtn.addEventListener("click", function () {
      if (outerIndex > 0) {
        outerIndex--;
        updateOuterSlider();
      }
    });

    window.addEventListener("resize", updateOuterSlider);

    const cards = track.querySelectorAll(".gx25-card");

    cards.forEach((card, index) => {
      const product = products[index];
      const favBtn = card.querySelector(".gx25-fav");
      const favIcon = favBtn.querySelector("i");
      const img = card.querySelector(".gx25-main-image");
      const innerPrev = card.querySelector(".gx25-inner-prev");
      const innerNext = card.querySelector(".gx25-inner-next");
      const colorDots = card.querySelectorAll(".gx25-color");
      const enterBtn = card.querySelector(".gx25-enter");

      let imageIndex = 0;
      let activeColor = colorSet[0] || "";

      favBtn.addEventListener("click", function () {
        this.classList.toggle("active");
        if (this.classList.contains("active")) {
          favIcon.classList.remove("fa-regular");
          favIcon.classList.add("fa-solid");
        } else {
          favIcon.classList.remove("fa-solid");
          favIcon.classList.add("fa-regular");
        }
      });

      innerPrev.addEventListener("click", function (e) {
        e.preventDefault();
        imageIndex = (imageIndex - 1 + product.images.length) % product.images.length;
        img.style.opacity = "0.35";
        setTimeout(() => {
          img.src = product.images[imageIndex];
          img.style.opacity = "1";
        }, 120);
      });

      innerNext.addEventListener("click", function (e) {
        e.preventDefault();
        imageIndex = (imageIndex + 1) % product.images.length;
        img.style.opacity = "0.35";
        setTimeout(() => {
          img.src = product.images[imageIndex];
          img.style.opacity = "1";
        }, 120);
      });

      colorDots.forEach(dot => {
        dot.addEventListener("click", function () {
          colorDots.forEach(d => d.classList.remove("active"));
          this.classList.add("active");
          activeColor = this.style.background || this.getAttribute("style") || activeColor;
        });
      });

      if (enterBtn) {
        enterBtn.addEventListener("click", async function () {
          try {
            await addToCart(category, product, activeColor, product.images[imageIndex] || product.images[0] || "");
          } catch (_error) {
            // Keep the page usable if cart sync fails.
          }
        });
      }
    });

    updateOuterSlider();
  });
});