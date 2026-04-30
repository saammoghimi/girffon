document.addEventListener("DOMContentLoaded", function () {
  const track = document.getElementById("gx25Track");
  const outerPrev = document.getElementById("gx25OuterPrev");
  const outerNext = document.getElementById("gx25OuterNext");

  if (!track || !outerPrev || !outerNext) return;

  const colorMap = {
    Bk: "#000000",
    Bl: "#2d3a56",
    Bu: "#4a6fdc",
    Gr: "#6f7d5c",
    Gy: "#bdbdbd",
    Ka: "#caa27f",
    Na: "#f1d4a5",
    Or: "#d68c8c",
    Pp: "#d9b3d9",
    Rd: "#c43b3b",
    Wh: "#ffffff",
    Ye: "#f2c94c"
  };

  const products = [
    {
      id: 1,
      badge: "Novità",
      title: "Men France Black T-Shirt",
      price: "200,00 €",
      folder: "Cart/products/tshirt-men/Men france/Bk/",
      images: [
        "Cart/products/tshirt-men/Men france/Bk/400.jpg",
        "Cart/products/tshirt-men/Men france/Bk/400-1.jpg",
        "Cart/products/tshirt-men/Men france/Bk/400-2.jpg",
        "Cart/products/tshirt-men/Men france/Bk/400-3.jpg"
      ],
      colors: ["Bk", "Bl", "Bu", "Gr", "Gy", "Ka", "Na", "Or", "Pp", "Rd", "Wh", "Ye"]
    },
        {
      id: 2,
      badge: "Novità",
      title: "ٌWomen Italy Black T-Shirt",
      price: "220,00 €",
      folder: "Cart/products/tshirt-women/Women italy/Or/",
      images: [
        "Cart/products/tshirt-women/Women italy/Or/400.jpg",
        "Cart/products/tshirt-women/Women italy/Or/400-1.jpg",
        "Cart/products/tshirt-women/Women italy/Or/400-2.jpg",
        "Cart/products/tshirt-women/Women italy/Or/400-3.jpg"
      ],
      colors: ["Bk", "Bl", "Bu", "Gr", "Gy", "Ka", "Na", "Or", "Pp", "Rd", "Wh", "Ye"]
    },

    {
      id: 3,
      badge: "Novità",
      title: "Men Italy Black T-Shirt",
      price: "220,00 €",
      folder: "Cart/products/tshirt-men/men italy/Gy/",
      images: [
        "Cart/products/tshirt-men/men italy/Gy/400.jpg",
        "Cart/products/tshirt-men/men italy/Gy/400-1.jpg",
        "Cart/products/tshirt-men/men italy/Gy/400-2.jpg",
        "Cart/products/tshirt-men/men italy/Gy/400-3.jpg"
      ],
      colors: ["Bk", "Bl", "Bu", "Gr", "Gy", "Ka", "Na", "Or", "Pp", "Rd", "Wh", "Ye"]
    },
        {
      id: 4,
      badge: "Novità",
      title: "Women France Black T-Shirt",
      price: "220,00 €",
      folder: "Cart/products/tshirt-women/women france/Bu/",
      images: [
        "Cart/products/tshirt-women/women france/Bu/400.jpg",
        "Cart/products/tshirt-women/women france/Bu/400-1.jpg",
        "Cart/products/tshirt-women/women france/Bu/400-2.jpg",
        "Cart/products/tshirt-women/women france/Bu/400-3.jpg"
      ],
      colors: ["Bk", "Bl", "Bu", "Gr", "Gy", "Ka", "Na", "Or", "Pp", "Rd", "Wh", "Ye"]
    },

    {
      id: 5,
      badge: "Novità",
      title: "Men USA Black T-Shirt",
      price: "230,00 €",
      folder: "Cart/products/tshirt-men/men usa/Ka/",
      images: [
        "Cart/products/tshirt-men/men usa/Ka/400.jpg",
        "Cart/products/tshirt-men/men usa/Ka/400-1.jpg",
        "Cart/products/tshirt-men/men usa/Ka/400-2.jpg",
        "Cart/products/tshirt-men/men usa/Ka/400-3.jpg"
      ],
      colors: ["Bk", "Bl", "Bu", "Gr", "Gy", "Ka", "Na", "Or", "Pp", "Rd", "Wh", "Ye"]
    },
{
  id: 6,
  badge: "Novità",
  title: "Women Japan Black T-Shirt",
  price: "220,00 €",
  folder: "Cart/products/tshirt-women/Women japon/Bk/",
  images: [
    "Cart/products/tshirt-women/Women japon/Bk/400.jpg",
    "Cart/products/tshirt-women/Women japon/Bk/400-1.jpg",
    "Cart/products/tshirt-women/Women japon/Bk/400-2.jpg",
    "Cart/products/tshirt-women/Women japon/Bk/400-3.jpg"
  ],
  colors: ["Bk", "Bl", "Bu", "Gr", "Gy", "Ka", "Na", "Or", "Pp", "Rd", "Wh", "Ye"]
}

  ];

  function getDefaultColor(product) {
    const parts = product.folder.split("/").filter(Boolean);
    return parts[parts.length - 1] || product.colors[0] || "Bk";
  }

  function getBaseFolder(product) {
    const parts = product.folder.split("/").filter(Boolean);
    return `${parts.slice(0, -1).join("/")}/`;
  }

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

  function buildSku(product) {
    return String(product.title || product.id || "girffon-product")
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "") || "girffon-product";
  }

  async function addToCart(product, colorCode, imageUrl) {
    const priceNumber = parsePrice(product.price);
    const sku = buildSku(product);
    const payload = {
      id: sku,
      sku: sku,
      name: product.title,
      title: product.title,
      price: priceNumber,
      priceNumber: priceNumber,
      color: colorCode,
      colorName: colorCode,
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
      return String(entry.id || entry.code || "") === sku && String(entry.color || "") === String(colorCode || "");
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
        color: colorCode,
        colorName: colorCode,
        size: "One Size",
        image: imageUrl,
        img: imageUrl,
        qty: 1,
        quantity: 1
      });
    }

    safeWriteArray("girffon_cart", cartItems);
  }

  function createColorDots(colorCodes, activeColor) {
    return colorCodes.map((code, index) => {
      const hex = colorMap[code] || "#cccccc";
      return `
        <span 
          class="gx25-color ${code === activeColor || (!activeColor && index === 0) ? "active" : ""}" 
          data-color="${code}"
          style="background:${hex};">
        </span>
      `;
    }).join("");
  }

  function createCard(product) {
    const defaultColor = getDefaultColor(product);
    return `
      <article class="gx25-card" data-id="${product.id}">
        <span class="gx25-badge">${product.badge}</span>

        <button class="gx25-fav" aria-label="Add to wishlist">
          <i class="fa-regular fa-heart"></i>
        </button>

        <div class="gx25-image-box">
          <button class="gx25-inner-nav gx25-inner-prev" type="button" aria-label="Previous image">
            <span>&#10094;</span>
          </button>

          <img class="gx25-main-image" src="${product.images[0]}" alt="${product.title}">

          <button class="gx25-inner-nav gx25-inner-next" type="button" aria-label="Next image">
            <span>&#10095;</span>
          </button>
        </div>

        <h3 class="gx25-title">${product.title}</h3>
        <p class="gx25-price">${product.price}</p>

        <div class="gx25-colors">
          ${createColorDots(product.colors, defaultColor)}
        </div>

        <button class="gx25-enter" type="button">Enter</button>
      </article>
    `;
  }

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
    const total = getCards().length;
    const visible = getVisibleCards();
    return Math.max(0, total - visible);
  }

  function updateOuterSlider() {
    const max = getMaxOuterIndex();

    if (outerIndex < 0) outerIndex = 0;
    if (outerIndex > max) outerIndex = max;

    const moveX = outerIndex * getCardWidth();
    track.style.transform = `translateX(-${moveX}px)`;
  }

  outerNext.addEventListener("click", function () {
    if (outerIndex < getMaxOuterIndex()) {
      outerIndex++;
      updateOuterSlider();
    }
  });

  outerPrev.addEventListener("click", function () {
    if (outerIndex > 0) {
      outerIndex--;
      updateOuterSlider();
    }
  });

  window.addEventListener("resize", updateOuterSlider);

  const cards = track.querySelectorAll(".gx25-card");

  cards.forEach((card, cardIndex) => {
    const product = products[cardIndex];

    const favBtn = card.querySelector(".gx25-fav");
    const favIcon = favBtn.querySelector("i");
    const img = card.querySelector(".gx25-main-image");
    const prevBtn = card.querySelector(".gx25-inner-prev");
    const nextBtn = card.querySelector(".gx25-inner-next");
    const colorDots = card.querySelectorAll(".gx25-color");
    const enterBtn = card.querySelector(".gx25-enter");

    let imageIndex = 0;
    let currentColor = getDefaultColor(product);
    const baseFolder = getBaseFolder(product);

    function getImagesByColor(colorCode) {
      return [
        `${baseFolder}${colorCode}/400.jpg`,
        `${baseFolder}${colorCode}/400-1.jpg`,
        `${baseFolder}${colorCode}/400-2.jpg`,
        `${baseFolder}${colorCode}/400-3.jpg`
      ];
    }

    let currentImages = getImagesByColor(currentColor);

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

    prevBtn.addEventListener("click", function (e) {
      e.preventDefault();

      imageIndex = (imageIndex - 1 + currentImages.length) % currentImages.length;
      img.style.opacity = "0.35";

      setTimeout(() => {
        img.src = currentImages[imageIndex];
        img.style.opacity = "1";
      }, 120);
    });

    nextBtn.addEventListener("click", function (e) {
      e.preventDefault();

      imageIndex = (imageIndex + 1) % currentImages.length;
      img.style.opacity = "0.35";

      setTimeout(() => {
        img.src = currentImages[imageIndex];
        img.style.opacity = "1";
      }, 120);
    });

    colorDots.forEach(dot => {
      dot.addEventListener("click", function () {
        colorDots.forEach(d => d.classList.remove("active"));
        this.classList.add("active");

        currentColor = this.dataset.color;
        currentImages = getImagesByColor(currentColor);
        imageIndex = 0;

        img.style.opacity = "0.35";

        setTimeout(() => {
          img.src = currentImages[0];
          img.style.opacity = "1";
        }, 120);
      });
    });

    enterBtn.addEventListener("click", async function () {
      try {
        await addToCart(product, currentColor, currentImages[0]);
      } catch (_error) {
        // Keep the page usable if cart sync fails.
      }
    });
  });

  updateOuterSlider();
});