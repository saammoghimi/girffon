<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GIRFFON Gift Card</title>
  <meta name="description" content="Buy a GIRFFON Gift Card in digital or physical format and add it to the existing checkout flow." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="CSS/style.css" />
  <link rel="stylesheet" href="CSS/Hero.css" />
  <link rel="stylesheet" href="CSS/Catalog5botton.css" />
  <link rel="stylesheet" href="CSS/Rel.css" />
  <link rel="stylesheet" href="CSS/SH.css" />
  <link rel="stylesheet" href="CSS/aboutcustom.css" />
  <link rel="stylesheet" href="CSS/Gifthh.css" />
  <link rel="stylesheet" href="CSS/anotherfile.css" />
  <link rel="stylesheet" href="CSS/gift-card-page.css" />
</head>
<body class="gift-card-page">
  <div class="top-bar">
    <div class="top-bar-left"></div>
    <div class="top-search">
      <input type="text" placeholder="Search" />
      <button type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
    <div class="top-actions">
      <a href="catalog.html" class="icon-link" title="Catalog" aria-label="Open catalog"><i class="fa-solid fa-globe"></i><i class="fa-solid fa-bag-shopping"></i></a>
      <a href="ProfilePage.php" aria-label="Open account page"><i class="fa-solid fa-user" title="Account"></i> Account</a>
      <a href="CartTest.html" id="gfCartTrigger" class="icon-link" title="Cart" aria-label="Open cart"><i class="fa-solid fa-cart-shopping"></i><span class="count-badge">0</span></a>
      <a href="WishlistPage.html" class="icon-link" title="Wishlist" aria-label="Open wishlist"><i class="fa-solid fa-heart"></i><span class="count-badge">0</span></a>
      <a href="about.html" class="icon-link" title="About" aria-label="Open about page"><i class="fa-solid fa-sliders-h"></i></a>
    </div>
  </div>

  <header class="main-header">
    <div class="logo-area">
      <img src="Image/Logo/Logo.png" alt="GirffoN Logo" />
    </div>
    <nav class="main-nav">
      <a href="index.html">HOME</a>
      <a href="about.html">ABOUT</a>
      <a href="catalog.html">CATALOG</a>
      <a href="gift-card.php">GIFT CARD</a>
      <a href="CartTest.html">CHECKOUT</a>
      <a href="ProfilePage.php">ACCOUNT</a>
    </nav>
    <div class="header-spacer"></div>
  </header>

  <main class="gift-card-shell">
    <section class="gift-card-hero">
      <article class="gift-card-panel">
        <span class="gift-card-kicker"><i class="fa-solid fa-gift"></i> GIRFFON Gift Card</span>
        <h1>Luxury gifting that moves through the same GIRFFON cart and checkout flow.</h1>
        <p>Choose a digital gift card with zero shipping or a physical branded card with QR code, barcode, and premium print presentation. English and Italian labels are included directly inside the experience.</p>
        <div class="gift-card-pill-row">
          <span class="gift-card-pill">Digital Gift Card / Carta regalo digitale</span>
          <span class="gift-card-pill">Physical Gift Card / Carta regalo fisica</span>
          <span class="gift-card-pill">Redeem later with partial balance support</span>
        </div>
      </article>

      <aside class="gift-card-preview">
        <div class="gift-card-preview-card">
          <span style="display:block;letter-spacing:.16em;text-transform:uppercase;font-size:.82rem;opacity:.8;">Gift Value</span>
          <strong>€25 · €50 · €100</strong>
          <p style="margin:0;line-height:1.7;opacity:.9;">Or enter a custom amount between €10 and €1000.</p>
        </div>
        <div class="gift-card-preview-grid">
          <div class="gift-card-preview-stat"><span>Digital</span><strong>No shipping cost</strong></div>
          <div class="gift-card-preview-stat"><span>Physical</span><strong>Shipping added at checkout</strong></div>
          <div class="gift-card-preview-stat"><span>Code Format</span><strong>GF-XXXX-XXXX-XXXX</strong></div>
          <div class="gift-card-preview-stat"><span>Redemption</span><strong>Partial and full</strong></div>
        </div>
      </aside>
    </section>

    <section class="gift-card-form-card">
      <div class="gift-card-form-head">
        <div>
          <h2>Build Your Gift Card</h2>
          <p>Fill out the buyer and recipient details, choose the delivery format, then add the card directly to the existing GIRFFON cart.</p>
        </div>
        <div class="gift-card-pill">Gift Card Code / Codice carta regalo</div>
      </div>

      <form id="gfGiftCardForm" class="gift-card-form-grid" novalidate>
        <div class="gift-card-field gift-card-field-wide">
          <label>Amount / Importo</label>
          <div class="gift-card-amount-grid">
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="25" checked><span>€25</span></label>
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="50"><span>€50</span></label>
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="100"><span>€100</span></label>
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="custom"><span>Custom / Personalizzato</span></label>
          </div>
        </div>

        <div class="gift-card-field gift-card-field-wide">
          <label for="gfGiftCardCustomAmount">Custom Amount / Importo personalizzato</label>
          <input class="gift-card-custom-input" id="gfGiftCardCustomAmount" name="custom_amount" type="number" min="10" max="1000" step="0.01" placeholder="Optional if a preset amount is selected">
        </div>

        <div class="gift-card-field gift-card-field-wide">
          <label>Delivery Type / Tipo di consegna</label>
          <div class="gift-card-delivery-grid">
            <label class="gift-card-choice"><input type="radio" name="delivery_type" value="digital" checked><span>Digital Gift Card<br>Zero shipping cost</span></label>
            <label class="gift-card-choice"><input type="radio" name="delivery_type" value="physical"><span>Physical Gift Card<br>Printed and shipped</span></label>
          </div>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftBuyerName">Buyer Name / Nome acquirente</label>
          <input id="gfGiftBuyerName" name="buyer_name" type="text" placeholder="Buyer full name" required>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftBuyerEmail">Buyer Email / Email acquirente</label>
          <input id="gfGiftBuyerEmail" name="buyer_email" type="email" placeholder="buyer@example.com" required>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftRecipientName">Recipient Name / Nome destinatario</label>
          <input id="gfGiftRecipientName" name="recipient_name" type="text" placeholder="Recipient full name" required>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftRecipientEmail">Recipient Email / Email destinatario</label>
          <input id="gfGiftRecipientEmail" name="recipient_email" type="email" placeholder="recipient@example.com" required>
        </div>

        <div class="gift-card-field gift-card-field-wide">
          <label for="gfGiftMessage">Personal Gift Message / Messaggio regalo</label>
          <textarea id="gfGiftMessage" name="gift_message" placeholder="Write a premium message for the recipient"></textarea>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftExpiresAt">Expiration Date / Scadenza</label>
          <input id="gfGiftExpiresAt" name="expires_at" type="date" required>
        </div>

        <div class="gift-card-field">
          <label>Checkout Notes / Note checkout</label>
          <select disabled>
            <option>Server validates amount, status, expiry, and remaining balance</option>
          </select>
        </div>

        <div class="gift-card-field gift-card-field-wide" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;">
          <button class="gift-card-submit" id="gfGiftCardSubmit" type="submit"><i class="fa-solid fa-cart-plus"></i> Add Gift Card to Cart</button>
          <p class="gift-card-status" id="gfGiftCardStatus">Digital cards are emailed after successful payment. Physical cards are created as shippable branded orders.</p>
        </div>
      </form>
    </section>

    <section class="gift-card-benefits">
      <div class="gift-card-benefits-grid">
        <article class="gift-card-benefit"><h3>Digital Fulfilment</h3><p>Recipient email includes the secure code, QR code, amount, gift message, and expiration date after checkout.</p></article>
        <article class="gift-card-benefit"><h3>Physical Presentation</h3><p>Physical cards are saved as normal orders and can be printed by the admin team with GIRFFON branding and barcode details.</p></article>
        <article class="gift-card-benefit"><h3>Flexible Redemption</h3><p>Customers can apply a gift card at checkout, use only part of the balance, and keep the remainder for future orders.</p></article>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-logo">
        <img src="Image/Logo/Logo.png" alt="GirffoN Logo">
      </div>
      <div class="footer-links">
        <a href="CartTest.html"><i class="fa-solid fa-box"></i> Checkout</a>
        <a href="catalog.html"><i class="fa-solid fa-book-open"></i> Catalog</a>
        <a href="TrackOrder.php"><i class="fa-solid fa-route"></i> Track Order</a>
        <a href="about.html"><i class="fa-solid fa-envelope"></i> Contact Us</a>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-social">
        <a href="https://www.instagram.com/Girffonstudio" target="_blank" rel="noopener noreferrer" aria-label="Open Instagram profile" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
        <a href="https://www.tiktok.com/@Girffon" target="_blank" rel="noopener noreferrer" aria-label="Open TikTok profile" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
        <a href="https://www.facebook.com/GirffonStudio" target="_blank" rel="noopener noreferrer" aria-label="Open Facebook page" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="https://x.com/GirffoN2025" target="_blank" rel="noopener noreferrer" aria-label="Open X profile" title="X"><i class="fa-brands fa-twitter"></i></a>
      </div>
      <p>© 2025 Design By <a href="../admin-moghimi/AdminMoghimi_System/auth/login.php" target="_blank">Admin Moghimi</a>. Designed in Italy - All Right Reserved</p>
    </div>
  </footer>

  <script src="JS/cart.js?v=20260429b"></script>
  <script src="JS/gift-card-page.js"></script>
</body>
</html>