<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="Image/Logo/logo for gif.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title id="gfGiftCardPageTitle">GIRFFON Gift Card</title>
  <meta id="gfGiftCardMetaDescription" name="description" content="Buy a GIRFFON Gift Card in digital or physical format and add it to the existing checkout flow." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <link rel="stylesheet" href="CSS/style.css" />
  <link rel="stylesheet" href="CSS/Hero.css" />
  <link rel="stylesheet" href="CSS/Catalog5botton.css" />
  <link rel="stylesheet" href="CSS/Rel.css" />
  <link rel="stylesheet" href="CSS/SH.css" />
  <link rel="stylesheet" href="CSS/aboutcustom.css" />
  <link rel="stylesheet" href="CSS/Gifthh.css" />
  <link rel="stylesheet" href="CSS/anotherfile.css" />
  <link rel="stylesheet" href="CSS/settings-panel.css" />
  <link rel="stylesheet" href="CSS/locale-panel.css" />
  <link rel="stylesheet" href="CSS/account-panel.css" />
  <link rel="stylesheet" href="CSS/gift-card-page.css?v=20260807r2" />
</head>
<body class="gift-card-page">
  <div class="top-bar">
    <div class="top-bar-left"></div>

    <form class="top-search" id="gfSiteSearch" role="search" autocomplete="off">
      <input type="text" id="gfSiteSearchInput" placeholder="Search" aria-label="Search products by name or code" />
      <button type="submit" id="gfSiteSearchButton" aria-label="Search products"><i class="fa-solid fa-magnifying-glass"></i></button>
      <div class="gf-search-results" id="gfSearchResults" hidden>
        <div class="gf-search-results-head">
          <span id="gfSearchResultsTitle">Search Results</span>
          <button type="button" class="gf-search-close" id="gfSearchClose" aria-label="Close search results">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="gf-search-results-list" id="gfSearchResultsList"></div>
      </div>
    </form>

    <div class="top-actions">

      <a href="#" id="gfLocaleTrigger" class="icon-link" title="Language / Currency" aria-label="Open language and currency panel">
        <i class="fa-solid fa-globe"></i>
        <i class="fa-solid fa-dollar-sign"></i>
      </a>

      <a href="#" id="gfAccountTrigger" aria-label="Open account panel"><i class="fa-solid fa-user" title="Account"></i> Account</a>

      <a href="#" id="gfCartTrigger" class="icon-link" title="Cart" aria-label="Open cart">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="count-badge">0</span>
      </a>

      <a href="#" id="gfWishlistTrigger" class="icon-link" title="Wishlist" aria-label="Open wishlist">
        <i class="fa-solid fa-heart"></i>
        <span class="count-badge">0</span>
      </a>

      <a href="#" id="gfSettingsTrigger" class="icon-link" title="Settings" aria-label="Open settings panel"><i class="fa-solid fa-sliders-h"></i></a>
    </div>
  </div>

  <header class="main-header">
    <div class="logo-area">
      <img src="Image/Logo/Logo.png" alt="GirffoN Logo" />
    </div>
    <nav class="main-nav">
      <a href="index.html">HOME</a>
      <a href="about.html">ABOUT</a>

      <div class="menu-item">
        <a href="#">SHOP</a>
        <div class="menu-box">
          <a href="men.html">MAN</a>
          <a href="woman.html">WOMAN</a>
          <a href="kids.html">KIDS & BABIES</a>
          <a href="accessories.html">ACCESSORIES</a>
          <a href="home-living.html">HOME & LIVING</a>
        </div>
      </div>

      <div class="menu-item">
        <a href="Image/Custom%20Design%20Pro/CustomDesignPro.html">CUSTOM DESIGN</a>
        <div class="menu-box">
          <a href="Image/Custom%20Design%20Pro/OrganicUnisexT-Shirt.html">MEN</a>
          <a href="Image/Custom%20Design%20Pro/WomenPremiumT-Shirt.html">WOMEN</a>
          <a href="Image/Custom%20Design%20Pro/KidsT-Shirt.html">KIDS</a>
          <a href="Image/Custom%20Design%20Pro/BabyT-Shirt.html">NEONATI</a>
          <a href="Image/Custom%20Design%20Pro/FlexfitCap.html">ACCESSORIES</a>
          <a href="Image/Custom%20Design%20Pro/CushionCoverNatural.html">HOME & LIVING</a>
        </div>
      </div>

      <a href="catalog.html">CATALOG</a>
      <a href="#" data-gf-contact-trigger aria-label="Open contact form">CONTACT</a>
    </nav>
    <div class="header-spacer"></div>
  </header>

  <main class="gift-card-shell">
    <section class="gift-card-hero">
      <article class="gift-card-panel">
        <span class="gift-card-kicker" id="gfGiftCardKicker"><i class="fa-solid fa-gift"></i> <span id="gfGiftCardKickerText">GIRFFON Gift Card</span></span>
        <h1 id="gfGiftCardHeroTitle">Luxury gifting that moves through the same GIRFFON cart and checkout flow.</h1>
        <p id="gfGiftCardHeroText">Choose a digital gift card with zero shipping or a physical branded card with QR code, barcode, and premium print presentation. English and Italian labels are included directly inside the experience.</p>
        <div class="gift-card-pill-row">
          <span class="gift-card-pill" id="gfGiftCardHeroPillDigital">Digital Gift Card / Carta regalo digitale</span>
          <span class="gift-card-pill" id="gfGiftCardHeroPillPhysical">Physical Gift Card / Carta regalo fisica</span>
          <span class="gift-card-pill" id="gfGiftCardHeroPillRedeem">Redeem later with partial balance support</span>
        </div>
      </article>

      <aside class="gift-card-preview">
        <div class="gift-card-preview-card">
          <span id="gfGiftCardPreviewLabel" style="display:block;letter-spacing:.16em;text-transform:uppercase;font-size:.82rem;opacity:.8;">Gift Value</span>
          <strong>€25 · €50 · €100</strong>
          <p id="gfGiftCardPreviewText" style="margin:0;line-height:1.7;opacity:.9;">Or enter a custom amount between €10 and €1000.</p>
        </div>
        <div class="gift-card-preview-grid">
          <div class="gift-card-preview-stat"><span id="gfGiftCardPreviewDigitalLabel">Digital</span><strong id="gfGiftCardPreviewDigitalValue">No shipping cost</strong></div>
          <div class="gift-card-preview-stat"><span id="gfGiftCardPreviewPhysicalLabel">Physical</span><strong id="gfGiftCardPreviewPhysicalValue">Shipping added at checkout</strong></div>
          <div class="gift-card-preview-stat"><span id="gfGiftCardPreviewCodeLabel">Code Format</span><strong>GF-XXXX-XXXX-XXXX</strong></div>
          <div class="gift-card-preview-stat"><span id="gfGiftCardPreviewRedemptionLabel">Redemption</span><strong id="gfGiftCardPreviewRedemptionValue">Partial and full</strong></div>
        </div>
      </aside>
    </section>

    <section class="gift-card-form-card">
      <div class="gift-card-form-head">
        <div>
          <h2 id="gfGiftCardFormTitle">Build Your Gift Card</h2>
          <p id="gfGiftCardFormIntro">Fill out the buyer and recipient details, choose the delivery format, then add the card directly to the existing GIRFFON cart.</p>
        </div>
        <div class="gift-card-pill" id="gfGiftCardFormCodePill">Gift Card Code / Codice carta regalo</div>
      </div>

      <section class="gift-card-shop-strip" id="gfGiftCardShopStrip" aria-label="Gift card values">
        <article class="gift-card-shop-card is-selected" id="gfGiftCardProduct25Card" data-gift-card-select="25" tabindex="0" role="button" aria-label="Select GIRFFON 25 euro gift card">
          <div class="gift-card-shop-image">
            <img id="gfGiftCardProduct25Image" src="Image/Gift/gift-25.png" alt="GIRFFON €25 Gift Card">
          </div>
          <div class="gift-card-shop-copy">
            <h3 id="gfGiftCardProduct25Title">€25 Gift Card</h3>
            <p id="gfGiftCardProduct25Text">A refined entry gift for personal surprises and premium gestures.</p>
          </div>
          <button class="gift-card-shop-button" id="gfGiftCardProduct25Button" type="button" data-gift-card-select="25">Buy Gift Card</button>
        </article>

        <article class="gift-card-shop-card" id="gfGiftCardProduct50Card" data-gift-card-select="50" tabindex="0" role="button" aria-label="Select GIRFFON 50 euro gift card">
          <div class="gift-card-shop-image">
            <img id="gfGiftCardProduct50Image" src="Image/Gift/gift-50.png" alt="GIRFFON €50 Gift Card">
          </div>
          <div class="gift-card-shop-copy">
            <h3 id="gfGiftCardProduct50Title">€50 Gift Card</h3>
            <p id="gfGiftCardProduct50Text">A balanced premium option for birthdays, celebrations, and thank-you gifts.</p>
          </div>
          <button class="gift-card-shop-button" id="gfGiftCardProduct50Button" type="button" data-gift-card-select="50">Buy Gift Card</button>
        </article>

        <article class="gift-card-shop-card" id="gfGiftCardProduct100Card" data-gift-card-select="100" tabindex="0" role="button" aria-label="Select GIRFFON 100 euro gift card">
          <div class="gift-card-shop-image">
            <img id="gfGiftCardProduct100Image" src="Image/Gift/gift-100.png" alt="GIRFFON €100 Gift Card">
          </div>
          <div class="gift-card-shop-copy">
            <h3 id="gfGiftCardProduct100Title">€100 Gift Card</h3>
            <p id="gfGiftCardProduct100Text">A standout luxury gift for milestone moments and elevated gifting.</p>
          </div>
          <button class="gift-card-shop-button" id="gfGiftCardProduct100Button" type="button" data-gift-card-select="100">Buy Gift Card</button>
        </article>
      </section>

      <form id="gfGiftCardForm" class="gift-card-form-grid" novalidate>
        <div class="gift-card-field gift-card-field-wide">
          <label id="gfGiftCardAmountLabel">Amount / Importo</label>
          <div class="gift-card-amount-grid">
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="25" checked><span>€25</span></label>
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="50"><span>€50</span></label>
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="100"><span>€100</span></label>
            <label class="gift-card-choice"><input type="radio" name="gift_amount_preset" value="custom"><span id="gfGiftCardCustomPresetLabel">Custom / Personalizzato</span></label>
          </div>
        </div>

        <div class="gift-card-field gift-card-field-wide">
          <label for="gfGiftCardCustomAmount" id="gfGiftCardCustomAmountLabel">Custom Amount / Importo personalizzato</label>
          <input class="gift-card-custom-input" id="gfGiftCardCustomAmount" name="custom_amount" type="number" min="10" max="1000" step="0.01" placeholder="Optional if a preset amount is selected">
        </div>

        <div class="gift-card-field gift-card-field-wide">
          <label id="gfGiftCardDeliveryTypeLabel">Delivery Type / Tipo di consegna</label>
          <div class="gift-card-delivery-grid">
            <label class="gift-card-choice"><input type="radio" name="delivery_type" value="digital" checked><span id="gfGiftCardDeliveryDigitalLabel">Digital Gift Card<br>Zero shipping cost</span></label>
            <label class="gift-card-choice"><input type="radio" name="delivery_type" value="physical"><span id="gfGiftCardDeliveryPhysicalLabel">Physical Gift Card<br>Printed and shipped</span></label>
          </div>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftBuyerName" id="gfGiftBuyerNameLabel">Buyer Name / Nome acquirente</label>
          <input id="gfGiftBuyerName" name="buyer_name" type="text" placeholder="Buyer full name" required>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftBuyerEmail" id="gfGiftBuyerEmailLabel">Buyer Email / Email acquirente</label>
          <input id="gfGiftBuyerEmail" name="buyer_email" type="email" placeholder="buyer@example.com" required>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftRecipientName" id="gfGiftRecipientNameLabel">Recipient Name / Nome destinatario</label>
          <input id="gfGiftRecipientName" name="recipient_name" type="text" placeholder="Recipient full name" required>
        </div>

        <div class="gift-card-field">
          <label for="gfGiftRecipientEmail" id="gfGiftRecipientEmailLabel">Recipient Email / Email destinatario</label>
          <input id="gfGiftRecipientEmail" name="recipient_email" type="email" placeholder="recipient@example.com" required>
        </div>

        <div class="gift-card-field gift-card-field-wide">
          <label for="gfGiftMessage" id="gfGiftMessageLabel">Personal Gift Message / Messaggio regalo</label>
          <textarea id="gfGiftMessage" name="gift_message" placeholder="Write a premium message for the recipient"></textarea>
        </div>

        <div class="gift-card-field gift-card-field-wide" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;">
          <button class="gift-card-submit" id="gfGiftCardSubmit" type="submit"><i class="fa-solid fa-cart-plus"></i> <span id="gfGiftCardSubmitText">Add Gift Card to Cart</span></button>
          <p class="gift-card-status" id="gfGiftCardStatus">Digital cards are emailed after successful payment. Physical cards are created as shippable branded orders.</p>
        </div>
      </form>
    </section>

    <section class="gift-card-benefits">
      <div class="gift-card-benefits-grid">
        <article class="gift-card-benefit"><h3 id="gfGiftCardBenefitDigitalTitle">Digital Fulfilment</h3><p id="gfGiftCardBenefitDigitalText">Recipient email includes the secure code, QR code, amount, gift message, and expiration date after checkout.</p></article>
        <article class="gift-card-benefit"><h3 id="gfGiftCardBenefitPhysicalTitle">Physical Presentation</h3><p id="gfGiftCardBenefitPhysicalText">Physical cards are saved as normal orders and can be printed by the admin team with GIRFFON branding and barcode details.</p></article>
        <article class="gift-card-benefit"><h3 id="gfGiftCardBenefitRedeemTitle">Flexible Redemption</h3><p id="gfGiftCardBenefitRedeemText">Customers can apply a gift card at checkout, use only part of the balance, and keep the remainder for future orders.</p></article>
      </div>
    </section>
  </main>

<aside id="gfSettingsPanel" class="gf-settings-panel" data-visible="false" aria-hidden="true">
  <header class="gf-settings-header">
    <h3>
      <i class="fa-solid fa-gear"></i>
      <span>Settings</span>
    </h3>
    <button type="button" class="gf-settings-close" aria-label="Close settings">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </header>

  <div class="gf-settings-body">
    <section class="gf-settings-section">
      <h4 class="gf-settings-section-title">DISPLAY</h4>

      <div class="gf-settings-item gf-settings-item-inline">
        <div class="gf-settings-label">
          <span class="gf-settings-label-text">Theme</span>
          <span class="gf-settings-label-desc">Choose light or dark mode</span>
        </div>
        <div class="gf-toggle-group gf-theme-toggle" role="group" aria-label="Theme">
          <button type="button" class="gf-toggle-btn active" data-setting="theme" data-value="light">
            <i class="fa-solid fa-sun"></i>
            <span>Light</span>
          </button>
          <button type="button" class="gf-toggle-btn" data-setting="theme" data-value="dark">
            <i class="fa-solid fa-moon"></i>
            <span>Dark</span>
          </button>
        </div>
      </div>

      <div class="gf-settings-item gf-settings-item-inline">
        <div class="gf-settings-label">
          <span class="gf-settings-label-text">Font Size</span>
          <span class="gf-settings-label-desc">Adjust the text size</span>
        </div>
        <div class="gf-toggle-group gf-font-toggle" role="group" aria-label="Font size">
          <button type="button" class="gf-size-btn active" data-setting="font" data-value="small">Small</button>
          <button type="button" class="gf-size-btn" data-setting="font" data-value="medium">Medium</button>
          <button type="button" class="gf-size-btn" data-setting="font" data-value="large">Large</button>
        </div>
      </div>
    </section>

    <section class="gf-settings-section">
      <h4 class="gf-settings-section-title">AUDIO</h4>

      <div class="gf-settings-item gf-settings-item-inline">
        <div class="gf-settings-label">
          <span class="gf-settings-label-text">Background Music</span>
          <span class="gf-settings-label-desc">Play music while browsing</span>
        </div>
        <div class="gf-toggle-group" role="group" aria-label="Background music">
          <button type="button" class="gf-toggle-btn active" data-setting="music" data-value="off">
            <i class="fa-solid fa-volume-xmark"></i>
            <span>Off</span>
          </button>
          <button type="button" class="gf-toggle-btn" data-setting="music" data-value="on">
            <i class="fa-solid fa-volume-high"></i>
            <span>On</span>
          </button>
        </div>
      </div>

      <div class="gf-settings-item">
        <div class="gf-settings-label">
          <span class="gf-settings-label-text">Music Track</span>
          <span class="gf-settings-label-desc">Choose the background track</span>
        </div>
        <select id="gfMusicTrack" class="gf-music-select" aria-label="Choose music track"></select>
      </div>

      <div class="gf-settings-item gf-settings-item-inline">
        <div class="gf-settings-label">
          <span class="gf-settings-label-text">Sound Effects</span>
          <span class="gf-settings-label-desc">Play sounds for actions</span>
        </div>
        <div class="gf-toggle-group" role="group" aria-label="Sound effects">
          <button type="button" class="gf-toggle-btn active" data-setting="sound" data-value="off">
            <i class="fa-solid fa-bell-slash"></i>
            <span>Off</span>
          </button>
          <button type="button" class="gf-toggle-btn" data-setting="sound" data-value="on">
            <i class="fa-solid fa-bell"></i>
            <span>On</span>
          </button>
        </div>
      </div>

      <div class="gf-settings-item gf-settings-item-volume">
        <div class="gf-settings-label">
          <span class="gf-settings-label-text">Volume</span>
          <span class="gf-settings-label-desc">Adjust the audio volume</span>
        </div>
        <div class="gf-volume-control">
          <i class="fa-solid fa-volume-low"></i>
          <input type="range" id="gfVolumeSlider" min="0" max="100" value="100" aria-label="Volume slider">
          <span id="gfVolumeValue">100%</span>
        </div>
      </div>
    </section>
  </div>
</aside>

<div id="gfSettingsOverlay" class="gf-settings-overlay" hidden></div>
<audio id="gfBackgroundMusic" loop preload="none"></audio>

<aside id="gfLocalePanel" class="gf-locale-panel" data-visible="false" aria-hidden="true">
  <header class="gf-locale-header">
    <h3>
      <i class="fa-solid fa-globe"></i>
      <span id="gfLocaleTitle">Language / Locale</span>
    </h3>
    <button type="button" class="gf-locale-close" aria-label="Close language and locale panel">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </header>

  <div class="gf-locale-body">
    <h4 class="gf-locale-subtitle" id="gfLocaleSubtitle">Select Country / Language</h4>

    <div class="gf-locale-grid" role="listbox" aria-label="Country and language selector">
      <button type="button" class="gf-locale-card" data-country="IT" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/it.svg" alt="Italy flag"></span>
        <span class="gf-locale-name">Italy</span>
        <span class="gf-locale-code">IT</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="DE" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/de.svg" alt="Germany flag"></span>
        <span class="gf-locale-name">Germany</span>
        <span class="gf-locale-code">DE</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="FR" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/fr.svg" alt="France flag"></span>
        <span class="gf-locale-name">France</span>
        <span class="gf-locale-code">FR</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="ES" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/es.svg" alt="Spain flag"></span>
        <span class="gf-locale-name">Spain</span>
        <span class="gf-locale-code">ES</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="NL" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/nl.svg" alt="Netherlands flag"></span>
        <span class="gf-locale-name">Netherlands</span>
        <span class="gf-locale-code">NL</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="PL" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/pl.svg" alt="Poland flag"></span>
        <span class="gf-locale-name">Poland</span>
        <span class="gf-locale-code">PL</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="SE" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/se.svg" alt="Sweden flag"></span>
        <span class="gf-locale-name">Sweden</span>
        <span class="gf-locale-code">SE</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="GB" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/gb.svg" alt="United Kingdom flag"></span>
        <span class="gf-locale-name">United Kingdom</span>
        <span class="gf-locale-code">GB</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="US" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/us.svg" alt="United States flag"></span>
        <span class="gf-locale-name">United States</span>
        <span class="gf-locale-code">US</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="CH" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/ch.svg" alt="Switzerland flag"></span>
        <span class="gf-locale-name">Switzerland</span>
        <span class="gf-locale-code">CH</span>
      </button>

      <button type="button" class="gf-locale-card" data-country="CA" role="option" aria-selected="false">
        <span class="gf-locale-flag" aria-hidden="true"><img src="Image/flags/ca.svg" alt="Canada flag"></span>
        <span class="gf-locale-name">Canada</span>
        <span class="gf-locale-code">CA</span>
      </button>
    </div>
  </div>

  <footer class="gf-locale-footer">
    <button type="button" class="gf-locale-close-btn" id="gfLocaleCloseBtn">Close</button>
  </footer>
</aside>

<div id="gfLocaleOverlay" class="gf-locale-overlay" hidden></div>

<aside id="gfAccountPanel" class="gf-account-panel" data-visible="false" aria-hidden="true">
  <header class="gf-account-header">
    <h3><i class="fa-regular fa-circle-user"></i> Account</h3>
    <button type="button" class="gf-account-close" aria-label="Close account panel">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </header>

  <div class="gf-account-body">
    <div id="gfAccountGuest" class="gf-account-view">
      <div class="gf-account-auth-shell">
        <div class="gf-account-auth-brand"><span>GirffoN</span></div>

        <div class="gf-account-auth-head">
          <h4>Sign in</h4>
          <p>Use your GirffoN account to access saved designs, orders, and your premium profile.</p>
        </div>

        <form id="gfAccountLoginForm" class="gf-account-auth-form" action="/GirffoN/backend/auth/login.php" method="POST" novalidate>
          <div class="gf-account-input-group">
            <label class="gf-account-input-label" for="gfLoginIdentifier">Username, email, or mobile</label>
            <div class="gf-account-input-wrap">
              <i class="fa-regular fa-envelope" aria-hidden="true"></i>
              <input type="text" id="gfLoginIdentifier" name="username" placeholder="girffon_2025" autocomplete="username" required>
            </div>
          </div>

          <div class="gf-account-input-group">
            <label class="gf-account-input-label" for="gfLoginPassword">Password</label>
            <div class="gf-account-input-wrap">
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              <input type="password" id="gfLoginPassword" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            </div>
          </div>

          <button type="submit" class="gf-account-btn gf-account-btn-primary" id="gfLoginBtn">
            <span>Login</span>
          </button>

          <div class="gf-account-auth-row">
            <label class="gf-account-check" for="gfStaySignedIn">
              <input type="checkbox" id="gfStaySignedIn" name="staySignedIn" checked>
              <span>Stay signed in</span>
            </label>

            <button type="button" class="gf-account-link-btn" id="gfForgotAccountBtn">Forgot password?</button>
          </div>
        </form>

        <div class="gf-account-actions">
          <button type="button" class="gf-account-btn gf-account-create-btn" id="gfSignupBtn">
            <span>Create an account</span>
          </button>
        </div>

        <div class="gf-account-divider"><span>or</span></div>

        <div class="gf-account-actions">
          <button type="button" class="gf-account-btn gf-account-btn-google" id="gfGoogleLoginBtn">
            <i class="fa-brands fa-google" aria-hidden="true"></i>
            <span>Sign in with Google</span>
          </button>
          <button type="button" class="gf-account-btn gf-account-btn-apple" id="gfAppleLoginBtn">
            <i class="fa-brands fa-apple" aria-hidden="true"></i>
            <span>Sign in with Apple</span>
          </button>
        </div>
      </div>
    </div>

    <div id="gfAccountUser" class="gf-account-view gf-account-user-view" style="display:none;">
      <section class="gf-account-user-card" aria-label="User profile summary">
        <div class="gf-account-profile-icon"><i class="fa-solid fa-user"></i></div>
        <div class="gf-account-user-meta">
          <h4 id="gfUserName">User</h4>
          <p id="gfUserEmail">user@example.com</p>
        </div>
      </section>

      <h5 class="gf-account-options-title">Account Options</h5>

      <div class="gf-account-options" role="list">
        <button type="button" class="gf-account-option" id="gfManageAccountBtn" role="listitem">
          <span class="gf-account-option-left"><i class="fa-solid fa-user-gear"></i><span>Manage Account</span></span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>

        <button type="button" class="gf-account-option" id="gfMyDesignsBtn" role="listitem">
          <span class="gf-account-option-left"><i class="fa-solid fa-folder"></i><span>My Designs</span></span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>

        <button type="button" class="gf-account-option" id="gfOrderHistoryBtn" role="listitem">
          <span class="gf-account-option-left"><i class="fa-solid fa-clock-rotate-left"></i><span>Order History</span></span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>

        <button type="button" class="gf-account-option" id="gfPaymentMethodsBtn" role="listitem">
          <span class="gf-account-option-left"><i class="fa-solid fa-credit-card"></i><span>Payment Methods</span></span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>

        <button type="button" class="gf-account-option" id="gfShippingAddressesBtn" role="listitem">
          <span class="gf-account-option-left"><i class="fa-solid fa-location-dot"></i><span>Shipping Addresses</span></span>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>

      <button type="button" class="gf-account-btn gf-account-btn-danger gf-account-logout" id="gfLogoutBtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
      </button>
    </div>
  </div>
</aside>

<div id="gfAccountOverlay" class="gf-account-overlay" hidden></div>

<footer class="site-footer">
  <div class="footer-inner">

    <div class="footer-logo">
      <img src="Image/Logo/Logo.png" alt="GirffoN Logo">
    </div>

    <div class="footer-links">
      <a href="#" id="gfTrackTrigger" aria-label="Open order tracking panel"><i class="fa-solid fa-box"></i> Track Order</a>
      <a href="#" id="gfReturnTrigger" aria-label="Open return policy panel"><i class="fa-solid fa-rotate-left"></i> Return Policy</a>
      <a href="#" id="gfFaqTrigger" aria-label="Open FAQ panel"><i class="fa-solid fa-circle-question"></i> FAQs</a>
      <a href="#" id="gfContactTrigger" data-gf-contact-trigger aria-label="Open contact form"><i class="fa-solid fa-envelope"></i> Contact Us</a>
    </div>

  </div>

  <div class="footer-bottom">

    <div class="footer-social">
      <a href="https://www.instagram.com/Girffonstudio" target="_blank" rel="noopener noreferrer" aria-label="Open Instagram profile" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
      <a href="https://www.tiktok.com/@Girffon" target="_blank" rel="noopener noreferrer" aria-label="Open TikTok profile" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
      <a href="https://www.facebook.com/girffon.shop" target="_blank" rel="noopener noreferrer" aria-label="Open Facebook page" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="https://x.com/GirffoN2025" target="_blank" rel="noopener noreferrer" aria-label="Open X profile" title="X"><i class="fa-brands fa-twitter"></i></a>
      <a href="https://www.youtube.com/@GirffoNStudio" target="_blank" rel="noopener noreferrer" aria-label="Open YouTube" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
      <a href="https://t.me/GirffoNStudio" target="_blank" rel="noopener noreferrer" aria-label="Open Telegram" title="Telegram"><i class="fa-brands fa-telegram"></i></a>
      <a href="https://wa.me/393444268927" target="_blank" rel="noopener noreferrer" aria-label="Open WhatsApp chat" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
    </div>

    <p>
      © 2025 Design By
      <a href="../admin-moghimi/AdminMoghimi_System/auth/login.php" target="_blank">Admin Moghimi</a>.
      Designed in Italy - All Right Reserved
    </p>

  </div>
</footer>

<div id="gfReturnOverlay" class="gf-return-overlay" hidden></div>

<section
  id="gfReturnModal"
  class="gf-return-modal"
  data-visible="false"
  role="dialog"
  aria-modal="true"
  aria-hidden="true"
  aria-labelledby="gfReturnTitle"
>
  <header class="gf-return-header">
    <h3 id="gfReturnTitle"><i class="fa-solid fa-rotate-left"></i> Return Policy</h3>
    <button type="button" id="gfReturnClose" class="gf-return-close" aria-label="Close return policy panel">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </header>

  <div class="gf-return-content">
    <p>We want you to be happy with your order. Please review the key return conditions below before sending any item back.</p>

    <ul class="gf-return-points">
      <li>Returns are accepted within 14 days of delivery.</li>
      <li>Items must be unused and in original condition.</li>
      <li>Custom-made or personalized products may not be eligible unless damaged or incorrect.</li>
      <li>Refunds are processed after inspection of the returned item.</li>
      <li>Please contact support before sending any return request.</li>
    </ul>

    <h4>How to request a return</h4>
    <p>Send your order number and reason for return through our Contact Us form. We will confirm next steps and the return address.</p>

    <h4>Refund timing and shipping</h4>
    <p>Approved refunds are usually completed within 5-10 business days. Return shipping responsibility depends on the return reason.</p>
  </div>
</section>

<div id="gfFaqOverlay" class="gf-faq-overlay" hidden></div>

<section
  id="gfFaqModal"
  class="gf-faq-modal"
  data-visible="false"
  role="dialog"
  aria-modal="true"
  aria-hidden="true"
  aria-labelledby="gfFaqTitle"
>
  <header class="gf-faq-header">
    <h3 id="gfFaqTitle"><i class="fa-solid fa-circle-question"></i> Frequently Asked Questions</h3>
    <button type="button" id="gfFaqClose" class="gf-faq-close" aria-label="Close FAQ panel">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </header>

  <div class="gf-faq-list" id="gfFaqList">
    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">How long does shipping take?</button>
      <div class="gf-faq-answer" hidden>
        <p>Most orders are delivered in 2-4 business days in Italy, 3-6 days in Europe, and 5-10 days worldwide.</p>
      </div>
    </article>

    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">Do you ship internationally?</button>
      <div class="gf-faq-answer" hidden>
        <p>Yes, we ship internationally. Delivery times vary by destination and shipping method.</p>
      </div>
    </article>

    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">Can I return custom products?</button>
      <div class="gf-faq-answer" hidden>
        <p>Returns are accepted for damaged or incorrect items. For fully custom items, return conditions may be limited.</p>
      </div>
    </article>

    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">How does custom design work?</button>
      <div class="gf-faq-answer" hidden>
        <p>Choose your product, add text or graphics, upload artwork, and preview your design before placing the order.</p>
      </div>
    </article>

    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">What payment methods do you accept?</button>
      <div class="gf-faq-answer" hidden>
        <p>We support major cards and commonly used online payment options depending on your country.</p>
      </div>
    </article>

    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">How can I track my order?</button>
      <div class="gf-faq-answer" hidden>
        <p>After shipping, you will receive a tracking link by email so you can follow your order status in real time.</p>
      </div>
    </article>

    <article class="gf-faq-item">
      <button type="button" class="gf-faq-question" aria-expanded="false">How do I contact support?</button>
      <div class="gf-faq-answer" hidden>
        <p>You can open the Contact Us form in the footer and send us your request directly from this page.</p>
      </div>
    </article>
  </div>
</section>

<div id="gfContactOverlay" class="gf-contact-overlay" hidden></div>

<section
  id="gfContactModal"
  class="gf-contact-modal"
  data-visible="false"
  role="dialog"
  aria-modal="true"
  aria-hidden="true"
  aria-labelledby="gfContactTitle"
>
  <header class="gf-contact-header">
    <h3 id="gfContactTitle"><i class="fa-solid fa-envelope"></i> Contact Us</h3>
    <button type="button" id="gfContactClose" class="gf-contact-close" aria-label="Close contact form">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </header>

  <form id="gfContactForm" class="gf-contact-form" novalidate>
    <label class="gf-contact-field" for="gfContactName">
      <span>Full Name</span>
      <input type="text" id="gfContactName" name="fullName" autocomplete="name" required>
    </label>

    <label class="gf-contact-field" for="gfContactEmail">
      <span>Email Address</span>
      <input type="email" id="gfContactEmail" name="email" autocomplete="email" required>
    </label>

    <label class="gf-contact-field" for="gfContactSubject">
      <span>Subject</span>
      <input type="text" id="gfContactSubject" name="subject" required>
    </label>

    <label class="gf-contact-field" for="gfContactMessage">
      <span>Message</span>
      <textarea id="gfContactMessage" name="message" rows="5" required></textarea>
    </label>

    <button type="submit" class="gf-contact-submit">Send</button>
    <p id="gfContactStatus" class="gf-contact-status" role="status" aria-live="polite"></p>
  </form>
</section>

  <script src="JS/analytics.js?v=20260804r21"></script>
  <script src="JS/cart.js?v=20260804r23"></script>
  <script src="JS/script.js?v=20260819-app-modal-1"></script>
  <script src="JS/contact-panel.js"></script>
  <script src="JS/settings-panel.js"></script>
  <script src="JS/locale-panel.js?v=20260807r4"></script>
  <script src="JS/account-panel.js?v=forgot-password-1"></script>
  <script src="JS/site-search.js"></script>
  <script src="JS/gift-card-page.js?v=20260806r1"></script>
</body>
</html>