(function () {
  "use strict";

  const trigger = document.getElementById("gfLocaleTrigger");
  const panel = document.getElementById("gfLocalePanel");
  const overlay = document.getElementById("gfLocaleOverlay");
  const closeBtn = panel?.querySelector(".gf-locale-close");
  const closeFooterBtn = document.getElementById("gfLocaleCloseBtn");

  if (!trigger || !panel || !overlay) {
    return;
  }

  const STORAGE_KEY = "gf-locale-country";
  const DEFAULT_COUNTRY = "GB";

  const COUNTRY_CONFIG = {
    IT: { locale: "it-IT", currency: "EUR", rateFromEUR: 1.0 },
    DE: { locale: "de-DE", currency: "EUR", rateFromEUR: 1.0 },
    FR: { locale: "fr-FR", currency: "EUR", rateFromEUR: 1.0 },
    ES: { locale: "es-ES", currency: "EUR", rateFromEUR: 1.0 },
    NL: { locale: "nl-NL", currency: "EUR", rateFromEUR: 1.0 },
    PL: { locale: "pl-PL", currency: "PLN", rateFromEUR: 4.32 },
    SE: { locale: "sv-SE", currency: "SEK", rateFromEUR: 11.4 },
    GB: { locale: "en-GB", currency: "GBP", rateFromEUR: 0.86 },
    US: { locale: "en-US", currency: "USD", rateFromEUR: 1.09 },
    CH: { locale: "de-CH", currency: "CHF", rateFromEUR: 0.97 },
    CA: { locale: "en-CA", currency: "CAD", rateFromEUR: 1.48 }
  };

  const BASE_TEXT = {
    account: "Account",
    cart: "Cart",
    wishlist: "Wishlist",
    settings: "Settings",
    home: "HOME",
    about: "ABOUT",
    shop: "SHOP",
    custom: "CUSTOM DESIGN",
    catalog: "CATALOG",
    contact: "CONTACT",
    search: "Search",
    localeTitle: "Language / Locale",
    localeSubtitle: "Select Country / Language",
    close: "Close",
    allCollections: "All Collections",
    men: "Men",
    women: "Women",
    kids: "Kids",
    aboutCustomDesign: "ABOUT CUSTOM DESIGN",
    freeShipping: "Free Shipping",
    limitedEditions: "Limited Editions",
    secureCheckout: "Secure Checkout",
    easyReturns: "Easy Returns",
    shopNow: "Shop Now",
    createNow: "Create Now",
    readMore: "Read More",
    trackOrder: "Track Order",
    returnPolicy: "Return Policy",
    faqs: "FAQs",
    contactUs: "Contact Us",
    shopMen: "MAN",
    shopWomen: "WOMAN",
    shopKidsBabies: "KIDS & BABIES",
    shopAccessories: "ACCESSORIES",
    shopHomeLiving: "HOME & LIVING",
    customMen: "MEN",
    customWomen: "WOMEN",
    customKids: "KIDS",
    customNeonati: "NEONATI",
    customAccessories: "ACCESSORIES",
    customHomeLiving: "HOME & LIVING",
    heroTarotTitle: "Tarot Collection",
    heroTarotDesc: "Discover mystical tarot-inspired designs for your unique style.",
    heroTarotBtn: "Shop Tarot",
    heroMenWomenTitle: "Men & Women",
    heroMenWomenDesc: "Modern premium t-shirts designed for both men and women.",
    heroMenWomenBtn: "Shop Now",
    heroKidsTitle: "Boys & Girls",
    heroKidsDesc: "Creative and fun designs for kids, boys and girls alike.",
    heroKidsBtn: "Explore Kids",
    heroAccHomeTitle: "Accessories & Home Living",
    heroAccHomeDesc: "Explore stylish accessories and artistic home living products.",
    heroAccHomeBtn: "Discover More",
    categoryAnimalTitle: "Animal Designs",
    categoryAnimalDesc: "Creative animal artwork for unique styles.",
    categoryMenTitle: "Men T-Shirts",
    categoryMenDesc: "Explore stylish t-shirts for men.",
    categoryWomenTitle: "Women T-Shirts",
    categoryWomenDesc: "Discover trendy designs for women.",
    categoryKidsTitle: "Kids T-Shirts",
    categoryKidsDesc: "Cute and fun tees for kids.",
    categoryAnimationTitle: "Animation",
    categoryAnimationDesc: "Create dynamic animations for your designs.",
    bannerAccessoriesTitle: "Accessories",
    bannerAccessoriesDesc: "Complete your custom style with GirffoN accessories - bags, hats, scarves, bottles, pins, and more designed to match your unique look.",
    bannerHomeTitle: "Home & Living",
    bannerHomeDesc: "Transform your space with unique designs - cushions, mugs, wall art, and decor made to match your personal style.",
    aboutPageVideoAria: "About video",
    aboutPageSectionAria: "About GirffoN",
    aboutPageHeadingHtml: "About <span>GirffoN</span>",
    aboutPageP1: "At GirffoN, we believe a T-shirt is not just clothing - it is a voice that speaks.",
    aboutPageP2: "Every one of our designs tells a story - of culture, history, identity, and beauty.",
    aboutPageP3: "From the Persians to Rome, to the soul of Paris and the pride of Vienna...",
    aboutPageP4: "Our T-shirts are not designed to be seen. They are designed to be felt.",
    aboutPageP5: "Every piece of GirffoN is crafted with meaning - for those who proudly wear their culture and their style.",
    aboutPageSignatureHeading: "GirffoN -",
    aboutPageS1: "Wear your legacy.",
    aboutPageS2: "Feel your power.",
    aboutPageS3: "Live your style.",
    aboutPara1: "GirffoN Custom Design offers a professional creative platform to personalise t-shirts, accessories, and lifestyle products with ease. Create original work with text, graphics, uploaded artwork, and flexible design tools made for both simple and advanced customisation.",
    aboutPara2Html: "Explore more than <strong>10,000+ creative combinations</strong> with product styles, colours, layouts, and visual elements. Choose from <strong>thousands of font styles</strong>, use the <strong>Add Design</strong> tool for logos and artwork, and build unique personalised products with a clean and powerful design experience.",
    serviceShortShipping: "Enjoy free delivery on qualifying orders across Italy and selected EU destinations.",
    serviceShortLimited: "Exclusive drops made for customers who want something unique.",
    serviceShortSecure: "Your payment and personal data are protected with trusted security standards.",
    serviceShortReturns: "Shop with confidence. If something is not right, returning your order is simple.",
    serviceFullShipping1: "Free shipping on all orders over EUR50 or when you order 3+ T-shirts.",
    serviceFullShipping2: "Italy: 2-4 days | Europe: 3-6 days | Worldwide: 5-10 days",
    serviceFullShipping3: "You will receive a tracking email after shipping.",
    serviceFullLimited1: "Each piece is inspired by legends and lost cities.",
    serviceFullLimited2: "Designed with care. These items will not return.",
    serviceFullSecure1: "Your data is protected with high security standards.",
    serviceFullSecure2: "Safe and confidential payment process.",
    serviceFullReturns1: "Simple and fast return process.",
    serviceFullReturns2: "Contact us and we guide you step by step.",
    reviewEasyBestTitle: "Easy Best",
    reviewEasyBestText: "Easy designs, concise timely communication, fair pricing, rapid delivery, as designed. Definitely recommend!",
    reviewDesignTitle: "Design T-Shirt",
    reviewDesignText: "Fast and high quality service, great communication. Will order again for sure!",
    reviewMugTitle: "Great Mug",
    reviewMugText: "Great mug. Very well done. Got sent out quickly. Super service!",
    catalogKicker: "GirffoN Seasonal Editions",
    catalogHeading: "Catalog Archive",
    catalogIntro: "Browse each monthly drop, open the available flipbook, or download the file directly.",
    catalogMarchTitle: "March 2025 Catalog",
    catalogMarchSubtitle: "Foundations Collection",
    catalogAprilTitle: "April 2025 Catalog",
    catalogAprilSubtitle: "Spring Motion Drop",
    catalogMayTitle: "May 2025 Catalog",
    catalogMaySubtitle: "Early Summer Drop",
    catalogJuneBadge: "Live Flipbook",
    catalogJuneTitle: "June 2025 Catalog",
    catalogJuneSubtitle: "Exclusive Mid-Summer",
    catalogViewCover: "View Cover",
    catalogDownloadCover: "Download Cover",
    catalogViewFlipbook: "View Flipbook",
    catalogDownloadPdf: "Download PDF",
    settingsDisplay: "DISPLAY",
    settingsAudio: "AUDIO",
    settingsTheme: "Theme",
    settingsThemeDesc: "Choose light or dark mode",
    settingsFontSize: "Font Size",
    settingsFontSizeDesc: "Adjust the text size",
    settingsBgMusic: "Background Music",
    settingsBgMusicDesc: "Play music while browsing",
    settingsTrack: "Music Track",
    settingsTrackDesc: "Choose the background track",
    settingsSoundFx: "Sound Effects",
    settingsSoundFxDesc: "Play sounds for actions",
    settingsVolume: "Volume",
    settingsVolumeDesc: "Adjust the audio volume",
    light: "Light",
    dark: "Dark",
    small: "Small",
    medium: "Medium",
    large: "Large",
    on: "On",
    off: "Off"
  };

  const SERVICE_BLOCK_I18N = {
    "en-GB": [
      {
        title: "Free Shipping",
        summary: "Enjoy free delivery on qualifying orders across Italy and selected EU destinations.",
        items: [
          "Italy: Free shipping on orders over <strong>EUR50</strong>",
          "Europe: Free shipping on orders over <strong>EUR90</strong>",
          "Fully tracked delivery",
          "Fast dispatch with <strong>GLS / BRT / DHL</strong>",
          "Estimated delivery: <strong>1-3 business days in Italy</strong>"
        ],
        outro: "We process orders quickly so your items arrive fast and safely."
      },
      {
        title: "Limited Editions",
        summary: "Exclusive drops made for customers who want something unique.",
        items: [
          "Special designs in limited quantities",
          "Premium collections and seasonal releases",
          "Once sold out, they may not return",
          "Unique styles not found in regular stock",
          "Early access launches and new arrivals"
        ],
        outro: "Designed to stand out. Made for those who move first."
      },
      {
        title: "Secure Checkout",
        summary: "Your payment and personal data are protected with trusted security standards.",
        items: [
          "SSL encrypted checkout",
          "Secure payments by trusted providers",
          "Safe customer data handling",
          "Instant order confirmation by email",
          "Reliable and transparent checkout process"
        ],
        outro: "Shop safely with confidence from order to delivery."
      },
      {
        title: "Easy Returns",
        summary: "Shop with confidence. If something is not right, returning your order is simple.",
        items: [
          "Return window: <strong>14 days</strong>",
          "Items must be unused and in original condition",
          "Quick return request by email or contact form",
          "Return shipping available via <strong>GLS / BRT / DHL</strong>",
          "Refund processed after inspection"
        ],
        outro: "Our support team makes the return process smooth and hassle-free."
      }
    ],
    "it-IT": [
      {
        title: "Spedizione Gratuita",
        summary: "Approfitta della consegna gratuita per ordini idonei in Italia e in alcune destinazioni UE.",
        items: [
          "Italia: spedizione gratuita per ordini oltre <strong>EUR50</strong>",
          "Europa: spedizione gratuita per ordini oltre <strong>EUR90</strong>",
          "Consegna completamente tracciata",
          "Spedizione rapida con <strong>GLS / BRT / DHL</strong>",
          "Consegna stimata: <strong>1-3 giorni lavorativi in Italia</strong>"
        ],
        outro: "Prepariamo gli ordini rapidamente per far arrivare i tuoi articoli in modo veloce e sicuro."
      },
      {
        title: "Edizioni Limitate",
        summary: "Drop esclusivi pensati per chi desidera qualcosa di unico.",
        items: [
          "Design speciali in quantita limitate",
          "Collezioni premium e uscite stagionali",
          "Una volta esauriti potrebbero non tornare",
          "Stili unici non presenti nello stock regolare",
          "Accesso anticipato a lanci e novita"
        ],
        outro: "Pensati per distinguersi. Creati per chi arriva per primo."
      },
      {
        title: "Checkout Sicuro",
        summary: "Il tuo pagamento e i tuoi dati personali sono protetti con standard di sicurezza affidabili.",
        items: [
          "Checkout con crittografia SSL",
          "Pagamenti sicuri tramite provider affidabili",
          "Gestione sicura dei dati cliente",
          "Conferma ordine immediata via email",
          "Processo di acquisto affidabile e trasparente"
        ],
        outro: "Acquista in sicurezza con fiducia dall'ordine alla consegna."
      },
      {
        title: "Reso Facile",
        summary: "Acquista con fiducia. Se qualcosa non va, restituire il tuo ordine e semplice.",
        items: [
          "Finestra di reso: <strong>14 giorni</strong>",
          "Gli articoli devono essere inutilizzati e nelle condizioni originali",
          "Richiesta di reso rapida via email o modulo contatti",
          "Spedizione di reso disponibile con <strong>GLS / BRT / DHL</strong>",
          "Rimborso elaborato dopo l'ispezione"
        ],
        outro: "Il nostro supporto rende il processo di reso semplice e senza stress."
      }
    ],
    "de-DE": [
      {
        title: "Kostenloser Versand",
        summary: "Profitieren Sie von kostenfreier Lieferung fur berechtigte Bestellungen in Italien und ausgewahlte EU-Ziele.",
        items: [
          "Italien: kostenloser Versand ab <strong>EUR50</strong>",
          "Europa: kostenloser Versand ab <strong>EUR90</strong>",
          "Vollstandig nachverfolgbare Lieferung",
          "Schneller Versand mit <strong>GLS / BRT / DHL</strong>",
          "Geschatzte Lieferzeit: <strong>1-3 Werktage in Italien</strong>"
        ],
        outro: "Wir bearbeiten Bestellungen schnell, damit Ihre Artikel sicher und zukunftig ankommen."
      },
      {
        title: "Limitierte Editionen",
        summary: "Exklusive Drops fur Kundinnen und Kunden, die etwas Einzigartiges wollen.",
        items: [
          "Besondere Designs in begrenzter Menge",
          "Premium-Kollektionen und saisonale Releases",
          "Nach Ausverkauf moglicherweise nicht mehr verfugbar",
          "Einzigartige Styles ausserhalb des Standardsortiments",
          "Fruher Zugang zu Neuheiten und Launches"
        ],
        outro: "Gemacht, um aufzufallen. Fur alle, die zuerst handeln."
      },
      {
        title: "Sicherer Checkout",
        summary: "Ihre Zahlung und personlichen Daten sind durch zuverlassige Sicherheitsstandards geschutzt.",
        items: [
          "SSL-verschlusselter Checkout",
          "Sichere Zahlungen uber vertrauenswurdige Anbieter",
          "Sichere Verarbeitung von Kundendaten",
          "Sofortige Bestellbestatigung per E-Mail",
          "Zuverlassiger und transparenter Bezahlvorgang"
        ],
        outro: "Kaufen Sie sicher und mit Vertrauen vom Warenkorb bis zur Lieferung."
      },
      {
        title: "Einfache Ruckgabe",
        summary: "Kaufen Sie mit Vertrauen. Wenn etwas nicht stimmt, ist die Ruckgabe einfach.",
        items: [
          "Ruckgabefrist: <strong>14 Tage</strong>",
          "Artikel mussen unbenutzt und im Originalzustand sein",
          "Schnelle Ruckgabeanfrage per E-Mail oder Kontaktformular",
          "Ruckversand verfugbar mit <strong>GLS / BRT / DHL</strong>",
          "Erstattung nach Prufung"
        ],
        outro: "Unser Support macht den Ruckgabeprozess unkompliziert und stressfrei."
      }
    ],
    "fr-FR": [
      {
        title: "Livraison Gratuite",
        summary: "Profitez de la livraison gratuite sur les commandes eligibles en Italie et dans certaines destinations de l'UE.",
        items: [
          "Italie : livraison gratuite a partir de <strong>EUR50</strong>",
          "Europe : livraison gratuite a partir de <strong>EUR90</strong>",
          "Livraison entierement suivie",
          "Expedition rapide avec <strong>GLS / BRT / DHL</strong>",
          "Delai estime : <strong>1-3 jours ouvrables en Italie</strong>"
        ],
        outro: "Nous traitons rapidement les commandes pour que vos articles arrivent vite et en toute securite."
      },
      {
        title: "Editions Limitees",
        summary: "Des sorties exclusives pour les clients qui veulent quelque chose d'unique.",
        items: [
          "Designs speciaux en quantites limitees",
          "Collections premium et sorties saisonnieres",
          "Une fois epuises, ils peuvent ne pas revenir",
          "Styles uniques absents du stock regulier",
          "Acces anticipe aux nouveautes et lancements"
        ],
        outro: "Pense pour se distinguer. Cree pour ceux qui avancent les premiers."
      },
      {
        title: "Paiement Securise",
        summary: "Votre paiement et vos donnees personnelles sont proteges par des standards de securite fiables.",
        items: [
          "Paiement chiffre SSL",
          "Paiements securises via des prestataires fiables",
          "Traitement securise des donnees clients",
          "Confirmation immediate de commande par e-mail",
          "Processus de paiement fiable et transparent"
        ],
        outro: "Achetez en toute confiance de la commande a la livraison."
      },
      {
        title: "Retours Faciles",
        summary: "Achetez en toute confiance. Si quelque chose ne va pas, le retour est simple.",
        items: [
          "Delai de retour : <strong>14 jours</strong>",
          "Les articles doivent etre inutilises et dans leur etat d'origine",
          "Demande de retour rapide par e-mail ou formulaire de contact",
          "Expedition de retour disponible via <strong>GLS / BRT / DHL</strong>",
          "Remboursement apres inspection"
        ],
        outro: "Notre equipe rend le processus de retour simple et sans complication."
      }
    ],
    "es-ES": [
      {
        title: "Envio Gratis",
        summary: "Disfruta de envio gratuito en pedidos validos dentro de Italia y destinos seleccionados de la UE.",
        items: [
          "Italia: envio gratuito en pedidos superiores a <strong>EUR50</strong>",
          "Europa: envio gratuito en pedidos superiores a <strong>EUR90</strong>",
          "Entrega totalmente rastreada",
          "Despacho rapido con <strong>GLS / BRT / DHL</strong>",
          "Entrega estimada: <strong>1-3 dias habiles en Italia</strong>"
        ],
        outro: "Procesamos los pedidos rapidamente para que tus articulos lleguen rapido y de forma segura."
      },
      {
        title: "Ediciones Limitadas",
        summary: "Lanzamientos exclusivos para clientes que buscan algo unico.",
        items: [
          "Disenos especiales en cantidades limitadas",
          "Colecciones premium y lanzamientos de temporada",
          "Una vez agotados, puede que no regresen",
          "Estilos unicos no disponibles en el stock regular",
          "Acceso anticipado a lanzamientos y novedades"
        ],
        outro: "Pensado para destacar. Hecho para quienes se adelantan."
      },
      {
        title: "Pago Seguro",
        summary: "Tu pago y tus datos personales estan protegidos con estandares de seguridad confiables.",
        items: [
          "Pago cifrado con SSL",
          "Pagos seguros con proveedores confiables",
          "Gestion segura de datos del cliente",
          "Confirmacion instantanea del pedido por correo",
          "Proceso de compra fiable y transparente"
        ],
        outro: "Compra con tranquilidad desde el pedido hasta la entrega."
      },
      {
        title: "Devoluciones Faciles",
        summary: "Compra con confianza. Si algo no esta bien, devolver tu pedido es sencillo.",
        items: [
          "Plazo de devolucion: <strong>14 dias</strong>",
          "Los articulos deben estar sin usar y en su estado original",
          "Solicitud de devolucion rapida por email o formulario de contacto",
          "Envio de devolucion disponible con <strong>GLS / BRT / DHL</strong>",
          "Reembolso procesado tras la inspeccion"
        ],
        outro: "Nuestro equipo hace que el proceso de devolucion sea simple y sin complicaciones."
      }
    ],
    "nl-NL": [
      {
        title: "Gratis Verzending",
        summary: "Geniet van gratis levering bij geldige bestellingen in Italie en geselecteerde EU-bestemmingen.",
        items: [
          "Italie: gratis verzending bij bestellingen boven <strong>EUR50</strong>",
          "Europa: gratis verzending bij bestellingen boven <strong>EUR90</strong>",
          "Volledig traceerbare levering",
          "Snelle verzending met <strong>GLS / BRT / DHL</strong>",
          "Geschatte levering: <strong>1-3 werkdagen in Italie</strong>"
        ],
        outro: "Wij verwerken bestellingen snel zodat je artikelen snel en veilig aankomen."
      },
      {
        title: "Gelimiteerde Edities",
        summary: "Exclusieve drops voor klanten die iets unieks willen.",
        items: [
          "Speciale ontwerpen in beperkte oplage",
          "Premium collecties en seizoensreleases",
          "Eenmaal uitverkocht komen ze mogelijk niet terug",
          "Unieke stijlen buiten de reguliere voorraad",
          "Vroege toegang tot launches en nieuwe items"
        ],
        outro: "Ontworpen om op te vallen. Gemaakt voor wie voorop loopt."
      },
      {
        title: "Veilig Afrekenen",
        summary: "Je betaling en persoonsgegevens zijn beschermd met betrouwbare veiligheidsstandaarden.",
        items: [
          "SSL-versleutelde checkout",
          "Veilige betalingen via betrouwbare aanbieders",
          "Veilige verwerking van klantgegevens",
          "Directe orderbevestiging per e-mail",
          "Betrouwbaar en transparant afrekenproces"
        ],
        outro: "Winkel met vertrouwen van bestelling tot levering."
      },
      {
        title: "Gemakkelijk Retour",
        summary: "Shop met vertrouwen. Als iets niet klopt, is retourneren eenvoudig.",
        items: [
          "Retourtermijn: <strong>14 dagen</strong>",
          "Artikelen moeten ongebruikt en in originele staat zijn",
          "Snelle retouraanvraag via e-mail of contactformulier",
          "Retourzending beschikbaar via <strong>GLS / BRT / DHL</strong>",
          "Terugbetaling na inspectie"
        ],
        outro: "Ons supportteam maakt het retourproces soepel en eenvoudig."
      }
    ],
    "pl-PL": [
      {
        title: "Darmowa Dostawa",
        summary: "Skorzystaj z darmowej dostawy dla kwalifikujacych sie zamowien we Wloszech i wybranych krajach UE.",
        items: [
          "Wlochy: darmowa dostawa od <strong>EUR50</strong>",
          "Europa: darmowa dostawa od <strong>EUR90</strong>",
          "W pelni sledzona dostawa",
          "Szybka wysylka przez <strong>GLS / BRT / DHL</strong>",
          "Szacowany czas dostawy: <strong>1-3 dni robocze we Wloszech</strong>"
        ],
        outro: "Realizujemy zamowienia szybko, aby Twoje produkty dotarly szybko i bezpiecznie."
      },
      {
        title: "Edycje Limitowane",
        summary: "Ekskluzywne dropy dla klientow, ktorzy szukaja czegos wyjatkowego.",
        items: [
          "Specjalne wzory w ograniczonych ilosciach",
          "Kolekcje premium i sezonowe premiery",
          "Po wyprzedaniu moga nie wrocic",
          "Unikalne style niedostepne w regularnej ofercie",
          "Wczesniejszy dostep do premier i nowosci"
        ],
        outro: "Stworzone, by sie wyrozniac. Dla tych, ktorzy dzialaja pierwsi."
      },
      {
        title: "Bezpieczna Platnosc",
        summary: "Twoja platnosc i dane osobowe sa chronione przez zaufane standardy bezpieczenstwa.",
        items: [
          "Szyfrowany checkout SSL",
          "Bezpieczne platnosci przez zaufanych operatorow",
          "Bezpieczna obsluga danych klienta",
          "Natychmiastowe potwierdzenie zamowienia e-mailem",
          "Przejrzysty i niezawodny proces zakupu"
        ],
        outro: "Kupuj bezpiecznie i z zaufaniem od zamowienia po dostawe."
      },
      {
        title: "Latwe Zwroty",
        summary: "Kupuj z pewnoscia. Jesli cos jest nie tak, zwrot zamowienia jest prosty.",
        items: [
          "Okno zwrotu: <strong>14 dni</strong>",
          "Produkty musza byc nieuzywane i w oryginalnym stanie",
          "Szybkie zgloszenie zwrotu przez e-mail lub formularz kontaktowy",
          "Wysylka zwrotna dostepna przez <strong>GLS / BRT / DHL</strong>",
          "Zwrot srodkow po kontroli"
        ],
        outro: "Nasz zespol wsparcia sprawia, ze proces zwrotu jest prosty i bezproblemowy."
      }
    ],
    "sv-SE": [
      {
        title: "Fri Frakt",
        summary: "Fa gratis leverans pa kvalificerade bestallningar i Italien och utvalda EU-destinationer.",
        items: [
          "Italien: fri frakt over <strong>EUR50</strong>",
          "Europa: fri frakt over <strong>EUR90</strong>",
          "Fullt sparbar leverans",
          "Snabb leverans med <strong>GLS / BRT / DHL</strong>",
          "Beraknad leverans: <strong>1-3 arbetsdagar i Italien</strong>"
        ],
        outro: "Vi behandlar bestallningar snabbt sa att dina varor kommer fram fort och sakert."
      },
      {
        title: "Begransade Editioner",
        summary: "Exklusiva slapp for kunder som vill ha nagot unikt.",
        items: [
          "Speciella designer i begransade upplagor",
          "Premiumkollektioner och sasongslanseringar",
          "Nar de ar slutsalda kanske de inte kommer tillbaka",
          "Unika stilar som inte finns i ordinarie sortiment",
          "Tidigare tillgang till lanseringar och nyheter"
        ],
        outro: "Skapad for att sticka ut. Gjord for dem som ar forst."
      },
      {
        title: "Saker Betalning",
        summary: "Din betalning och personliga data skyddas med tillforlitliga sakerhetsstandarder.",
        items: [
          "SSL-krypterad checkout",
          "Sakra betalningar via betrodda leverantorer",
          "Saker hantering av kunddata",
          "Omedelbar orderbekraftelse via e-post",
          "Tillforlitlig och transparent checkout"
        ],
        outro: "Handla tryggt med fortroende fran bestallning till leverans."
      },
      {
        title: "Enkla Returer",
        summary: "Handla tryggt. Om nagot inte stammer ar det enkelt att returnera din bestallning.",
        items: [
          "Returfonsster: <strong>14 dagar</strong>",
          "Varorna maste vara oanvanda och i originalskick",
          "Snabb returforfragan via e-post eller kontaktformular",
          "Returfrakt tillganglig via <strong>GLS / BRT / DHL</strong>",
          "Aterbetalning efter kontroll"
        ],
        outro: "Vart supportteam gor returprocessen smidig och enkel."
      }
    ],
    "en-US": null,
    "de-CH": null,
    "en-CA": null
  };

  const ACCOUNT_PANEL_I18N = {
    "en-GB": {
      signIn: "Sign in",
      accountIntro: "Use your GirffoN account to access saved designs, orders, and your premium profile.",
      identifierLabel: "Username, email, or mobile",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Password",
      passwordPlaceholder: "Enter your password",
      login: "Login",
      staySignedIn: "Stay signed in",
      forgotUsername: "Forgot password?",
      createAccount: "Create an account",
      dividerOr: "or",
      signInGoogle: "Sign in with Google",
      signInApple: "Sign in with Apple",
      accountOptions: "Account Options",
      manageAccount: "Manage Account",
      myDesigns: "My Designs",
      orderHistory: "Order History",
      paymentMethods: "Payment Methods",
      shippingAddresses: "Shipping Addresses",
      logout: "Logout"
    },
    "it-IT": {
      signIn: "Accedi",
      accountIntro: "Usa il tuo account GirffoN per accedere a design salvati, ordini e profilo premium.",
      identifierLabel: "Nome utente, email o cellulare",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Password",
      passwordPlaceholder: "Inserisci la tua password",
      login: "Accedi",
      staySignedIn: "Resta connesso",
      forgotUsername: "Hai dimenticato il nome utente?",
      createAccount: "Crea un account",
      dividerOr: "oppure",
      signInGoogle: "Accedi con Google",
      signInApple: "Accedi con Apple",
      accountOptions: "Opzioni Account",
      manageAccount: "Gestisci Account",
      myDesigns: "I Miei Design",
      orderHistory: "Storico Ordini",
      paymentMethods: "Metodi di Pagamento",
      shippingAddresses: "Indirizzi di Spedizione",
      logout: "Esci"
    },
    "de-DE": {
      signIn: "Anmelden",
      accountIntro: "Nutzen Sie Ihr GirffoN-Konto fur gespeicherte Designs, Bestellungen und Ihr Premium-Profil.",
      identifierLabel: "Benutzername, E-Mail oder Mobilnummer",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Passwort",
      passwordPlaceholder: "Passwort eingeben",
      login: "Anmelden",
      staySignedIn: "Angemeldet bleiben",
      forgotUsername: "Benutzername vergessen?",
      createAccount: "Konto erstellen",
      dividerOr: "oder",
      signInGoogle: "Mit Google anmelden",
      signInApple: "Mit Apple anmelden",
      accountOptions: "Kontooptionen",
      manageAccount: "Konto Verwalten",
      myDesigns: "Meine Designs",
      orderHistory: "Bestellverlauf",
      paymentMethods: "Zahlungsmethoden",
      shippingAddresses: "Lieferadressen",
      logout: "Abmelden"
    },
    "fr-FR": {
      signIn: "Connexion",
      accountIntro: "Utilisez votre compte GirffoN pour acceder a vos designs sauvegardes, commandes et profil premium.",
      identifierLabel: "Nom d'utilisateur, email ou mobile",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Mot de passe",
      passwordPlaceholder: "Entrez votre mot de passe",
      login: "Se connecter",
      staySignedIn: "Rester connecte",
      forgotUsername: "Nom d'utilisateur oublie ?",
      createAccount: "Creer un compte",
      dividerOr: "ou",
      signInGoogle: "Se connecter avec Google",
      signInApple: "Se connecter avec Apple",
      accountOptions: "Options du Compte",
      manageAccount: "Gerer le Compte",
      myDesigns: "Mes Designs",
      orderHistory: "Historique des Commandes",
      paymentMethods: "Moyens de Paiement",
      shippingAddresses: "Adresses de Livraison",
      logout: "Deconnexion"
    },
    "es-ES": {
      signIn: "Iniciar sesion",
      accountIntro: "Usa tu cuenta GirffoN para acceder a disenos guardados, pedidos y tu perfil premium.",
      identifierLabel: "Usuario, email o movil",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Contrasena",
      passwordPlaceholder: "Introduce tu contrasena",
      login: "Entrar",
      staySignedIn: "Mantener sesion iniciada",
      forgotUsername: "Olvidaste tu usuario?",
      createAccount: "Crear una cuenta",
      dividerOr: "o",
      signInGoogle: "Entrar con Google",
      signInApple: "Entrar con Apple",
      accountOptions: "Opciones de la Cuenta",
      manageAccount: "Gestionar Cuenta",
      myDesigns: "Mis Disenos",
      orderHistory: "Historial de Pedidos",
      paymentMethods: "Metodos de Pago",
      shippingAddresses: "Direcciones de Envio",
      logout: "Cerrar sesion"
    },
    "nl-NL": {
      signIn: "Inloggen",
      accountIntro: "Gebruik je GirffoN-account voor opgeslagen ontwerpen, bestellingen en je premiumprofiel.",
      identifierLabel: "Gebruikersnaam, e-mail of mobiel",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Wachtwoord",
      passwordPlaceholder: "Voer je wachtwoord in",
      login: "Inloggen",
      staySignedIn: "Ingelogd blijven",
      forgotUsername: "Gebruikersnaam vergeten?",
      createAccount: "Account aanmaken",
      dividerOr: "of",
      signInGoogle: "Inloggen met Google",
      signInApple: "Inloggen met Apple",
      accountOptions: "Accountopties",
      manageAccount: "Account Beheren",
      myDesigns: "Mijn Ontwerpen",
      orderHistory: "Bestelgeschiedenis",
      paymentMethods: "Betaalmethoden",
      shippingAddresses: "Verzendadressen",
      logout: "Uitloggen"
    },
    "pl-PL": {
      signIn: "Zaloguj sie",
      accountIntro: "Uzyj konta GirffoN, aby uzyskac dostep do zapisanych projektow, zamowien i profilu premium.",
      identifierLabel: "Nazwa uzytkownika, email lub telefon",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Haslo",
      passwordPlaceholder: "Wpisz haslo",
      login: "Zaloguj sie",
      staySignedIn: "Pozostan zalogowany",
      forgotUsername: "Nie pamietasz nazwy uzytkownika?",
      createAccount: "Utworz konto",
      dividerOr: "lub",
      signInGoogle: "Zaloguj przez Google",
      signInApple: "Zaloguj przez Apple",
      accountOptions: "Opcje Konta",
      manageAccount: "Zarzadzaj Kontem",
      myDesigns: "Moje Projekty",
      orderHistory: "Historia Zamowien",
      paymentMethods: "Metody Platnosci",
      shippingAddresses: "Adresy Dostawy",
      logout: "Wyloguj sie"
    },
    "sv-SE": {
      signIn: "Logga in",
      accountIntro: "Anvand ditt GirffoN-konto for att komma at sparade designer, bestallningar och din premiumprofil.",
      identifierLabel: "Anvandarnamn, e-post eller mobil",
      identifierPlaceholder: "name@example.com",
      passwordLabel: "Losenord",
      passwordPlaceholder: "Ange ditt losenord",
      login: "Logga in",
      staySignedIn: "Halla mig inloggad",
      forgotUsername: "Glomt anvandarnamn?",
      createAccount: "Skapa ett konto",
      dividerOr: "eller",
      signInGoogle: "Logga in med Google",
      signInApple: "Logga in med Apple",
      accountOptions: "Kontoval",
      manageAccount: "Hantera Konto",
      myDesigns: "Mina Designer",
      orderHistory: "Orderhistorik",
      paymentMethods: "Betalningsmetoder",
      shippingAddresses: "Leveransadresser",
      logout: "Logga ut"
    }
  };

  const ACCOUNT_PANEL_LOCALE_FALLBACK = {
    "en-US": "en-GB",
    "en-CA": "en-GB",
    "de-CH": "de-DE"
  };

  const EXTRA_I18N = {
    "it-IT": {
      catalogKicker: "Edizioni Stagionali GirffoN",
      catalogHeading: "Archivio Cataloghi",
      catalogIntro: "Sfoglia ogni uscita mensile, apri il flipbook disponibile o scarica direttamente il file.",
      catalogMarchSubtitle: "Collezione Fondamenti",
      catalogAprilSubtitle: "Drop Movimento Primaverile",
      catalogMaySubtitle: "Drop Inizio Estate",
      catalogJuneBadge: "Flipbook Live",
      catalogJuneSubtitle: "Esclusiva di Meta Estate",
      catalogViewCover: "Apri Copertina",
      catalogDownloadCover: "Scarica Copertina",
      catalogViewFlipbook: "Apri Flipbook",
      catalogDownloadPdf: "Scarica PDF",
      men: "Uomo",
      women: "Donna",
      kids: "Bambini",
      aboutCustomDesign: "DESIGN PERSONALIZZATO",
      aboutPara1: "GirffoN Custom Design offre una piattaforma creativa professionale per personalizzare t-shirt, accessori e prodotti lifestyle con facilita. Crea lavori originali con testo, grafiche, immagini caricate e strumenti flessibili pensati per personalizzazioni semplici e avanzate.",
      aboutPara2Html: "Esplora oltre <strong>10.000+ combinazioni creative</strong> con stili prodotto, colori, layout ed elementi visivi. Scegli tra <strong>migliaia di stili di font</strong>, usa lo strumento <strong>Add Design</strong> per loghi e artwork, e crea prodotti personalizzati unici con un'esperienza di design pulita e potente.",
      shopMen: "UOMO",
      shopWomen: "DONNA",
      shopKidsBabies: "BAMBINI & NEONATI",
      shopAccessories: "ACCESSORI",
      shopHomeLiving: "CASA & LIVING",
      customNeonati: "NEONATI",
      heroTarotTitle: "Collezione Tarot",
      heroTarotBtn: "Acquista Tarot",
      heroMenWomenTitle: "Uomo & Donna",
      heroMenWomenBtn: "Acquista Ora",
      heroKidsTitle: "Bambini",
      heroKidsBtn: "Esplora Bambini",
      heroAccHomeTitle: "Accessori & Casa",
      heroAccHomeBtn: "Scopri di Piu",
      bannerAccessoriesTitle: "Accessori",
      bannerHomeTitle: "Casa & Living",
      settingsDisplay: "SCHERMO",
      settingsAudio: "AUDIO",
      settingsTheme: "Tema",
      settingsFontSize: "Dimensione Font",
      settingsBgMusic: "Musica di Sottofondo",
      settingsTrack: "Traccia Musicale",
      settingsSoundFx: "Effetti Sonori",
      settingsVolume: "Volume",
      light: "Chiaro",
      dark: "Scuro",
      small: "Piccolo",
      medium: "Medio",
      large: "Grande",
      on: "On",
      off: "Off"
    },
    "de-DE": {
      catalogKicker: "GirffoN Saisonale Editionen",
      catalogHeading: "Katalogarchiv",
      catalogIntro: "Durchsuchen Sie jeden Monats-Drop, offnen Sie das verfugbare Flipbook oder laden Sie die Datei direkt herunter.",
      catalogMarchSubtitle: "Grundlagen Kollektion",
      catalogAprilSubtitle: "Fruhlingsbewegung Drop",
      catalogMaySubtitle: "Fruher Sommer Drop",
      catalogJuneBadge: "Live Flipbook",
      catalogJuneSubtitle: "Exklusiver Hochsommer",
      catalogViewCover: "Cover Ansehen",
      catalogDownloadCover: "Cover Herunterladen",
      catalogViewFlipbook: "Flipbook Offnen",
      catalogDownloadPdf: "PDF Herunterladen",
      men: "Herren",
      women: "Damen",
      kids: "Kinder",
      aboutCustomDesign: "UBER INDIVIDUELLES DESIGN",
      aboutPara1: "GirffoN Custom Design bietet eine professionelle kreative Plattform, um T-Shirts, Accessoires und Lifestyle-Produkte einfach zu personalisieren. Erstellen Sie originelle Designs mit Text, Grafiken, hochgeladenen Motiven und flexiblen Tools fur einfache und fortgeschrittene Anpassungen.",
      aboutPara2Html: "Entdecken Sie mehr als <strong>10.000+ kreative Kombinationen</strong> mit Produktstilen, Farben, Layouts und visuellen Elementen. Wahlen Sie aus <strong>Tausenden von Schriftarten</strong>, nutzen Sie das <strong>Add Design</strong>-Tool fur Logos und Artwork und erstellen Sie einzigartige personalisierte Produkte mit einem klaren und leistungsstarken Design-Erlebnis.",
      shopMen: "HERREN",
      shopWomen: "DAMEN",
      shopKidsBabies: "KINDER & BABYS",
      shopAccessories: "ACCESSOIRES",
      shopHomeLiving: "HOME & LIVING",
      customNeonati: "BABYS",
      heroTarotTitle: "Tarot Kollektion",
      heroTarotBtn: "Tarot Kaufen",
      heroMenWomenTitle: "Herren & Damen",
      heroMenWomenBtn: "Jetzt Kaufen",
      heroKidsTitle: "Jungen & Madchen",
      heroKidsBtn: "Kinder Entdecken",
      heroAccHomeTitle: "Accessoires & Wohnen",
      heroAccHomeBtn: "Mehr Entdecken",
      bannerAccessoriesTitle: "Accessoires",
      bannerHomeTitle: "Home & Living",
      settingsDisplay: "ANZEIGE",
      settingsTheme: "Design",
      settingsFontSize: "Schriftgrosse",
      settingsBgMusic: "Hintergrundmusik",
      settingsTrack: "Musik Track",
      settingsSoundFx: "Sound Effekte",
      light: "Hell",
      dark: "Dunkel",
      small: "Klein",
      medium: "Mittel",
      large: "Gross",
      on: "Ein",
      off: "Aus"
    },
    "fr-FR": {
      catalogKicker: "Editions Saisonnieres GirffoN",
      catalogHeading: "Archive du Catalogue",
      catalogIntro: "Parcourez chaque sortie mensuelle, ouvrez le flipbook disponible ou telechargez directement le fichier.",
      catalogMarchSubtitle: "Collection Fondations",
      catalogAprilSubtitle: "Drop Mouvement de Printemps",
      catalogMaySubtitle: "Drop Debut d'Ete",
      catalogJuneBadge: "Flipbook Live",
      catalogJuneSubtitle: "Exclusivite Mi-Ete",
      catalogViewCover: "Voir la Couverture",
      catalogDownloadCover: "Telecharger la Couverture",
      catalogViewFlipbook: "Voir le Flipbook",
      catalogDownloadPdf: "Telecharger le PDF",
      men: "Homme",
      women: "Femme",
      kids: "Enfants",
      aboutCustomDesign: "A PROPOS DU DESIGN PERSONNALISE",
      aboutPara1: "GirffoN Custom Design propose une plateforme creative professionnelle pour personnaliser facilement des t-shirts, accessoires et produits lifestyle. Creez des compositions originales avec du texte, des graphiques, des visuels importes et des outils flexibles adaptes aux personnalisations simples et avancees.",
      aboutPara2Html: "Explorez plus de <strong>10 000+ combinaisons creatives</strong> avec styles de produit, couleurs, mises en page et elements visuels. Choisissez parmi <strong>des milliers de styles de police</strong>, utilisez l'outil <strong>Add Design</strong> pour les logos et artworks, et creez des produits personnalises uniques avec une experience de design claire et puissante.",
      shopMen: "HOMME",
      shopWomen: "FEMME",
      shopKidsBabies: "ENFANTS & BEBES",
      shopAccessories: "ACCESSOIRES",
      shopHomeLiving: "MAISON & LIVING",
      customNeonati: "NOUVEAU-NES",
      heroTarotTitle: "Collection Tarot",
      heroTarotBtn: "Acheter Tarot",
      heroMenWomenTitle: "Homme & Femme",
      heroMenWomenBtn: "Acheter",
      heroKidsTitle: "Garcons & Filles",
      heroKidsBtn: "Explorer Enfants",
      heroAccHomeTitle: "Accessoires & Maison",
      heroAccHomeBtn: "Decouvrir",
      bannerAccessoriesTitle: "Accessoires",
      bannerHomeTitle: "Maison & Living",
      settingsDisplay: "AFFICHAGE",
      settingsTheme: "Theme",
      settingsFontSize: "Taille de Police",
      settingsBgMusic: "Musique de Fond",
      settingsTrack: "Piste Musicale",
      settingsSoundFx: "Effets Sonores",
      light: "Clair",
      dark: "Sombre",
      small: "Petit",
      medium: "Moyen",
      large: "Grand",
      on: "On",
      off: "Off"
    },
    "es-ES": {
      catalogKicker: "Ediciones de Temporada GirffoN",
      catalogHeading: "Archivo de Catalogos",
      catalogIntro: "Explora cada lanzamiento mensual, abre el flipbook disponible o descarga el archivo directamente.",
      catalogMarchSubtitle: "Coleccion Fundamentos",
      catalogAprilSubtitle: "Lanzamiento Movimiento de Primavera",
      catalogMaySubtitle: "Lanzamiento de Inicio de Verano",
      catalogJuneBadge: "Flipbook en Vivo",
      catalogJuneSubtitle: "Exclusivo de Mitad de Verano",
      catalogViewCover: "Ver Portada",
      catalogDownloadCover: "Descargar Portada",
      catalogViewFlipbook: "Ver Flipbook",
      catalogDownloadPdf: "Descargar PDF",
      men: "Hombre",
      women: "Mujer",
      kids: "Ninos",
      aboutCustomDesign: "SOBRE DISENO PERSONALIZADO",
      aboutPara1: "GirffoN Custom Design ofrece una plataforma creativa profesional para personalizar camisetas, accesorios y productos lifestyle con facilidad. Crea trabajos originales con texto, graficos, imagenes cargadas y herramientas flexibles para personalizacion simple y avanzada.",
      aboutPara2Html: "Explora mas de <strong>10.000+ combinaciones creativas</strong> con estilos de producto, colores, composiciones y elementos visuales. Elige entre <strong>miles de estilos de fuente</strong>, usa la herramienta <strong>Add Design</strong> para logos y artwork, y crea productos personalizados unicos con una experiencia de diseno limpia y potente.",
      shopMen: "HOMBRE",
      shopWomen: "MUJER",
      shopKidsBabies: "NINOS & BEBES",
      shopAccessories: "ACCESORIOS",
      shopHomeLiving: "HOGAR & LIVING",
      customNeonati: "BEBES",
      heroTarotTitle: "Coleccion Tarot",
      heroTarotBtn: "Comprar Tarot",
      heroMenWomenTitle: "Hombre & Mujer",
      heroMenWomenBtn: "Comprar",
      heroKidsTitle: "Ninos & Ninas",
      heroKidsBtn: "Explorar Ninos",
      heroAccHomeTitle: "Accesorios & Hogar",
      heroAccHomeBtn: "Descubrir",
      bannerAccessoriesTitle: "Accesorios",
      bannerHomeTitle: "Hogar & Living",
      settingsDisplay: "PANTALLA",
      settingsTheme: "Tema",
      settingsFontSize: "Tamano de Fuente",
      settingsBgMusic: "Musica de Fondo",
      settingsTrack: "Pista Musical",
      settingsSoundFx: "Efectos de Sonido",
      light: "Claro",
      dark: "Oscuro",
      small: "Pequeno",
      medium: "Mediano",
      large: "Grande",
      on: "On",
      off: "Off"
    },
    "nl-NL": {
      catalogKicker: "GirffoN Seizoensedities",
      catalogHeading: "Catalogusarchief",
      catalogIntro: "Bekijk elke maandelijkse drop, open het beschikbare flipbook of download het bestand direct.",
      catalogMarchSubtitle: "Basiscollectie",
      catalogAprilSubtitle: "Lentebeweging Drop",
      catalogMaySubtitle: "Vroege Zomer Drop",
      catalogJuneBadge: "Live Flipbook",
      catalogJuneSubtitle: "Exclusieve Midzomer",
      catalogViewCover: "Cover Bekijken",
      catalogDownloadCover: "Cover Downloaden",
      catalogViewFlipbook: "Flipbook Bekijken",
      catalogDownloadPdf: "PDF Downloaden",
      men: "Heren",
      women: "Dames",
      kids: "Kinderen",
      aboutCustomDesign: "OVER CUSTOM DESIGN",
      aboutPara1: "GirffoN Custom Design biedt een professioneel creatief platform om t-shirts, accessoires en lifestyleproducten eenvoudig te personaliseren. Maak originele ontwerpen met tekst, graphics, geuploade beelden en flexibele tools voor eenvoudige en geavanceerde personalisatie.",
      aboutPara2Html: "Ontdek meer dan <strong>10.000+ creatieve combinaties</strong> met productstijlen, kleuren, lay-outs en visuele elementen. Kies uit <strong>duizenden lettertypes</strong>, gebruik de <strong>Add Design</strong>-tool voor logo's en artwork, en bouw unieke gepersonaliseerde producten met een strakke en krachtige designervaring.",
      shopMen: "HEREN",
      shopWomen: "DAMES",
      shopKidsBabies: "KIDS & BABYS",
      shopAccessories: "ACCESSOIRES",
      shopHomeLiving: "HOME & LIVING",
      customNeonati: "BABYS",
      settingsDisplay: "WEERGAVE",
      settingsTheme: "Thema",
      settingsFontSize: "Lettergrootte",
      settingsBgMusic: "Achtergrondmuziek",
      settingsTrack: "Muzieknummer",
      settingsSoundFx: "Geluidseffecten",
      light: "Licht",
      dark: "Donker",
      small: "Klein",
      medium: "Middel",
      large: "Groot",
      on: "Aan",
      off: "Uit"
    },
    "pl-PL": {
      catalogKicker: "Sezonowe Edycje GirffoN",
      catalogHeading: "Archiwum Katalogow",
      catalogIntro: "Przegladaj kazdy miesieczny drop, otwieraj dostepny flipbook albo pobierz plik bezposrednio.",
      catalogMarchSubtitle: "Kolekcja Fundamenty",
      catalogAprilSubtitle: "Wiosenny Drop Ruchu",
      catalogMaySubtitle: "Wczesny Letni Drop",
      catalogJuneBadge: "Flipbook Live",
      catalogJuneSubtitle: "Ekskluzywne Srod-Lato",
      catalogViewCover: "Zobacz Okladke",
      catalogDownloadCover: "Pobierz Okladke",
      catalogViewFlipbook: "Otworz Flipbook",
      catalogDownloadPdf: "Pobierz PDF",
      men: "Mezczyzni",
      women: "Kobiety",
      kids: "Dzieci",
      aboutCustomDesign: "O PROJEKCIE WLASNYM",
      aboutPara1: "GirffoN Custom Design oferuje profesjonalna platforme kreatywna do latwej personalizacji koszulek, akcesoriow i produktow lifestyle. Tworz oryginalne projekty z tekstem, grafika, wgranymi obrazami i elastycznymi narzedziami do prostych oraz zaawansowanych modyfikacji.",
      aboutPara2Html: "Odkryj ponad <strong>10 000+ kreatywnych kombinacji</strong> stylow produktow, kolorow, ukladow i elementow wizualnych. Wybieraj sposrod <strong>tysiecy stylow czcionek</strong>, korzystaj z narzedzia <strong>Add Design</strong> dla logo i artworku oraz tworz unikalne produkty personalizowane w czystym i wydajnym srodowisku projektowym.",
      shopMen: "MEZCZYZNI",
      shopWomen: "KOBIETY",
      shopKidsBabies: "DZIECI & NIEMOWLETA",
      shopAccessories: "AKCESORIA",
      shopHomeLiving: "DOM & LIVING",
      customNeonati: "NIEMOWLETA",
      settingsDisplay: "WYSWIETLANIE",
      settingsTheme: "Motyw",
      settingsFontSize: "Rozmiar Czcionki",
      settingsBgMusic: "Muzyka Tla",
      settingsTrack: "Sciezka Muzyczna",
      settingsSoundFx: "Efekty Dzwiekowe",
      light: "Jasny",
      dark: "Ciemny",
      small: "Maly",
      medium: "Sredni",
      large: "Duzy",
      on: "Wl",
      off: "Wyl"
    },
    "sv-SE": {
      catalogKicker: "GirffoN Sasongsutgavor",
      catalogHeading: "Katalogarkiv",
      catalogIntro: "Bladdra bland varje manadsslap, oppna tillganglig flipbook eller ladda ner filen direkt.",
      catalogMarchSubtitle: "Grundkollektion",
      catalogAprilSubtitle: "Varrorelse Drop",
      catalogMaySubtitle: "Tidigt Sommardrop",
      catalogJuneBadge: "Live Flipbook",
      catalogJuneSubtitle: "Exklusiv Mid-Sommar",
      catalogViewCover: "Visa Omslag",
      catalogDownloadCover: "Ladda Ner Omslag",
      catalogViewFlipbook: "Visa Flipbook",
      catalogDownloadPdf: "Ladda Ner PDF",
      men: "Man",
      women: "Kvinnor",
      kids: "Barn",
      aboutCustomDesign: "OM ANPASSAD DESIGN",
      aboutPara1: "GirffoN Custom Design erbjuder en professionell kreativ plattform for att enkelt anpassa t-shirts, accessoarer och livsstilsprodukter. Skapa original med text, grafik, uppladdade bilder och flexibla verktyg for enkel och avancerad anpassning.",
      aboutPara2Html: "Utforska mer an <strong>10 000+ kreativa kombinationer</strong> med produktstilar, farger, layouter och visuella element. Valj bland <strong>tusentals teckensnitt</strong>, anvand verktyget <strong>Add Design</strong> for logotyper och artwork, och skapa unika personliga produkter med en ren och kraftfull designupplevelse.",
      shopMen: "MAN",
      shopWomen: "KVINNOR",
      shopKidsBabies: "BARN & BABY",
      shopAccessories: "ACCESSOARER",
      shopHomeLiving: "HEM & LIVING",
      customNeonati: "BABY",
      settingsDisplay: "VISNING",
      settingsTheme: "Tema",
      settingsFontSize: "Textstorlek",
      settingsBgMusic: "Bakgrundsmusik",
      settingsTrack: "Musikspar",
      settingsSoundFx: "Ljudeffekter",
      light: "Ljus",
      dark: "Mork",
      small: "Liten",
      medium: "Medium",
      large: "Stor",
      on: "Pa",
      off: "Av"
    },
    "de-CH": {
      catalogKicker: "GirffoN Saisonale Editionen",
      catalogHeading: "Katalogarchiv",
      catalogIntro: "Durchsuchen Sie jeden Monats-Drop, offnen Sie das verfugbare Flipbook oder laden Sie die Datei direkt herunter.",
      catalogMarchSubtitle: "Grundlagen Kollektion",
      catalogAprilSubtitle: "Fruhlingsbewegung Drop",
      catalogMaySubtitle: "Fruher Sommer Drop",
      catalogJuneBadge: "Live Flipbook",
      catalogJuneSubtitle: "Exklusiver Hochsommer",
      catalogViewCover: "Cover Ansehen",
      catalogDownloadCover: "Cover Herunterladen",
      catalogViewFlipbook: "Flipbook Offnen",
      catalogDownloadPdf: "PDF Herunterladen",
      men: "Herren",
      women: "Damen",
      kids: "Kinder",
      aboutCustomDesign: "UBER INDIVIDUELLES DESIGN",
      aboutPara1: "GirffoN Custom Design bietet eine professionelle kreative Plattform, um T-Shirts, Accessoires und Lifestyle-Produkte einfach zu personalisieren. Erstellen Sie originelle Arbeiten mit Text, Grafiken, hochgeladenen Bildern und flexiblen Tools fur einfache und fortgeschrittene Anpassungen.",
      aboutPara2Html: "Entdecken Sie uber <strong>10.000+ kreative Kombinationen</strong> mit Produktstilen, Farben, Layouts und visuellen Elementen. Wahlen Sie aus <strong>Tausenden von Schriftarten</strong>, nutzen Sie das <strong>Add Design</strong>-Tool fur Logos und Artwork und erstellen Sie einzigartige personalisierte Produkte mit einem klaren und leistungsstarken Design-Erlebnis.",
      shopMen: "HERREN",
      shopWomen: "DAMEN",
      shopKidsBabies: "KINDER & BABYS",
      shopAccessories: "ACCESSOIRES",
      shopHomeLiving: "HOME & LIVING",
      customNeonati: "BABYS",
      settingsDisplay: "ANZEIGE",
      settingsTheme: "Design",
      settingsFontSize: "Schriftgrosse",
      settingsBgMusic: "Hintergrundmusik",
      settingsTrack: "Musik Track",
      settingsSoundFx: "Sound Effekte",
      light: "Hell",
      dark: "Dunkel",
      small: "Klein",
      medium: "Mittel",
      large: "Gross",
      on: "Ein",
      off: "Aus"
    }
  };

  const I18N = {
    "en-GB": {},
    "it-IT": {
      cart: "Carrello",
      wishlist: "Preferiti",
      settings: "Impostazioni",
      about: "CHI SIAMO",
      shop: "NEGOZIO",
      custom: "DESIGN PERSONALIZZATO",
      catalog: "CATALOGO",
      contact: "CONTATTI",
      search: "Cerca",
      localeTitle: "Lingua / Locale",
      localeSubtitle: "Seleziona Paese / Lingua",
      close: "Chiudi",
      allCollections: "Tutte le Collezioni",
      freeShipping: "Spedizione Gratuita",
      limitedEditions: "Edizioni Limitate",
      secureCheckout: "Checkout Sicuro",
      easyReturns: "Reso Facile",
      createNow: "Crea Ora",
      readMore: "Leggi di piu",
      trackOrder: "Traccia Ordine",
      returnPolicy: "Politica Resi",
      contactUs: "Contattaci"
    },
    "de-DE": {
      account: "Konto",
      cart: "Warenkorb",
      wishlist: "Wunschliste",
      settings: "Einstellungen",
      home: "START",
      about: "UBER UNS",
      custom: "INDIVIDUELLES DESIGN",
      catalog: "KATALOG",
      contact: "KONTAKT",
      search: "Suchen",
      localeTitle: "Sprache / Region",
      localeSubtitle: "Land / Sprache wahlen",
      close: "Schliessen",
      allCollections: "Alle Kollektionen",
      freeShipping: "Kostenloser Versand",
      limitedEditions: "Limitierte Editionen",
      secureCheckout: "Sicherer Checkout",
      easyReturns: "Einfache Ruckgabe",
      shopNow: "Jetzt Kaufen",
      createNow: "Jetzt Erstellen",
      readMore: "Mehr Lesen",
      trackOrder: "Bestellung Verfolgen",
      returnPolicy: "Ruckgaberecht",
      contactUs: "Kontakt"
    },
    "fr-FR": {
      account: "Compte",
      cart: "Panier",
      wishlist: "Favoris",
      settings: "Parametres",
      home: "ACCUEIL",
      about: "A PROPOS",
      shop: "BOUTIQUE",
      custom: "DESIGN PERSONNALISE",
      catalog: "CATALOGUE",
      search: "Rechercher",
      localeTitle: "Langue / Region",
      localeSubtitle: "Selectionnez le pays / langue",
      close: "Fermer",
      allCollections: "Toutes les Collections",
      freeShipping: "Livraison Gratuite",
      limitedEditions: "Editions Limitees",
      secureCheckout: "Paiement Securise",
      easyReturns: "Retours Faciles",
      shopNow: "Acheter",
      createNow: "Creer Maintenant",
      readMore: "Lire Plus",
      trackOrder: "Suivre la Commande",
      returnPolicy: "Politique de Retour",
      contactUs: "Contactez-nous"
    },
    "es-ES": {
      account: "Cuenta",
      cart: "Carrito",
      wishlist: "Favoritos",
      settings: "Configuracion",
      home: "INICIO",
      about: "SOBRE NOSOTROS",
      shop: "TIENDA",
      custom: "DISENO PERSONALIZADO",
      catalog: "CATALOGO",
      contact: "CONTACTO",
      search: "Buscar",
      localeTitle: "Idioma / Region",
      localeSubtitle: "Seleccione pais / idioma",
      close: "Cerrar",
      allCollections: "Todas las Colecciones",
      freeShipping: "Envio Gratis",
      limitedEditions: "Ediciones Limitadas",
      secureCheckout: "Pago Seguro",
      easyReturns: "Devoluciones Faciles",
      shopNow: "Comprar",
      createNow: "Crear Ahora",
      readMore: "Leer Mas",
      trackOrder: "Rastrear Pedido",
      returnPolicy: "Politica de Devolucion",
      contactUs: "Contactanos"
    },
    "nl-NL": {
      cart: "Winkelwagen",
      wishlist: "Verlanglijst",
      settings: "Instellingen",
      about: "OVER ONS",
      shop: "WINKEL",
      catalog: "CATALOGUS",
      search: "Zoeken",
      localeTitle: "Taal / Regio",
      localeSubtitle: "Selecteer land / taal",
      close: "Sluiten",
      allCollections: "Alle Collecties",
      freeShipping: "Gratis Verzending",
      limitedEditions: "Gelimiteerde Edities",
      secureCheckout: "Veilig Afrekenen",
      easyReturns: "Gemakkelijk Retour",
      readMore: "Meer Lezen",
      trackOrder: "Bestelling Volgen",
      returnPolicy: "Retourbeleid"
    },
    "pl-PL": {
      account: "Konto",
      cart: "Koszyk",
      wishlist: "Lista Zyczen",
      settings: "Ustawienia",
      home: "START",
      about: "O NAS",
      shop: "SKLEP",
      custom: "PROJEKT WLASNY",
      catalog: "KATALOG",
      contact: "KONTAKT",
      search: "Szukaj",
      localeTitle: "Jezyk / Region",
      localeSubtitle: "Wybierz kraj / jezyk",
      close: "Zamknij",
      allCollections: "Wszystkie Kolekcje",
      freeShipping: "Darmowa Dostawa",
      limitedEditions: "Edycje Limitowane",
      secureCheckout: "Bezpieczna Platnosc",
      easyReturns: "Latwe Zwroty"
    },
    "sv-SE": {
      account: "Konto",
      cart: "Varukorg",
      wishlist: "Onskelista",
      settings: "Installningar",
      home: "HEM",
      about: "OM OSS",
      shop: "BUTIK",
      custom: "ANPASSAD DESIGN",
      catalog: "KATALOG",
      contact: "KONTAKT",
      search: "Sok",
      localeTitle: "Sprak / Region",
      localeSubtitle: "Valj land / sprak",
      close: "Stang",
      allCollections: "Alla Kollektioner",
      freeShipping: "Fri Frakt",
      limitedEditions: "Begransade Editioner",
      secureCheckout: "Saker Betalning",
      easyReturns: "Enkla Returer"
    },
    "en-US": {},
    "de-CH": {
      account: "Konto",
      cart: "Warenkorb",
      wishlist: "Wunschliste",
      settings: "Einstellungen",
      home: "START",
      about: "UBER UNS",
      custom: "INDIVIDUELLES DESIGN",
      catalog: "KATALOG",
      contact: "KONTAKT",
      search: "Suchen",
      localeTitle: "Sprache / Region",
      localeSubtitle: "Land / Sprache wahlen",
      close: "Schliessen"
    },
    "en-CA": {
      cart: "Cart",
      wishlist: "Wishlist",
      settings: "Settings"
    }
  };

  const PANEL_I18N = {
    "en-GB": {},
    "en-US": {},
    "en-CA": {},
    "it-IT": {
      trackPanelTitle: "Traccia il tuo ordine",
      trackPanelHelp: "Inserisci numero ordine ed email per controllare lo stato.",
      trackOrderNumberLabel: "Numero Ordine",
      trackEmailLabel: "Indirizzo Email",
      trackButtonLabel: "Traccia Ordine",
      trackStatusFound: "Stato ordine trovato.",
      trackStatusNotFound: "Ordine non trovato. Controlla numero ordine ed email.",
      trackStatusError: "Qualcosa e andato storto. Riprova.",
      formRequiredError: "Compila tutti i campi obbligatori.",
      formEmailError: "Inserisci un indirizzo email valido.",
      returnPanelTitle: "Politica Resi",
      returnIntro: "Vogliamo che tu sia soddisfatto del tuo ordine. Leggi le condizioni di reso qui sotto prima di inviare qualsiasi articolo.",
      returnPoint1: "I resi sono accettati entro 14 giorni dalla consegna.",
      returnPoint2: "Gli articoli devono essere inutilizzati e nelle condizioni originali.",
      returnPoint3: "I prodotti personalizzati potrebbero non essere idonei, salvo danni o errore prodotto.",
      returnPoint4: "I rimborsi vengono elaborati dopo l'ispezione dell'articolo reso.",
      returnPoint5: "Contatta il supporto prima di inviare qualsiasi richiesta di reso.",
      returnHowTitle: "Come richiedere un reso",
      returnHowText: "Invia numero ordine e motivo del reso tramite il modulo Contattaci. Ti confermeremo i prossimi passaggi.",
      returnRefundTitle: "Tempi rimborso e spedizione",
      returnRefundText: "I rimborsi approvati vengono completati di solito in 5-10 giorni lavorativi. Le spese di spedizione dipendono dal motivo del reso.",
      faqPanelTitle: "Domande Frequenti",
      faqQ1: "Quanto tempo richiede la spedizione?",
      faqA1: "La maggior parte degli ordini arriva in 2-4 giorni in Italia, 3-6 giorni in Europa e 5-10 giorni nel resto del mondo.",
      faqQ2: "Spedite a livello internazionale?",
      faqA2: "Si, spediamo a livello internazionale. I tempi variano in base alla destinazione.",
      faqQ3: "Posso restituire prodotti personalizzati?",
      faqA3: "I resi dei prodotti personalizzati sono accettati solo se danneggiati o errati.",
      faqQ4: "Come funziona il design personalizzato?",
      faqA4: "Scegli il prodotto, aggiungi testo o grafica, carica file e visualizza l'anteprima prima dell'ordine.",
      faqQ5: "Quali metodi di pagamento accettate?",
      faqA5: "Accettiamo le principali carte e metodi di pagamento online disponibili nel tuo paese.",
      faqQ6: "Come posso tracciare il mio ordine?",
      faqA6: "Dopo la spedizione riceverai un link di tracciamento via email.",
      faqQ7: "Come posso contattare il supporto?",
      faqA7: "Apri il modulo Contattaci nel footer e inviaci la tua richiesta.",
      contactPanelTitle: "Contattaci",
      contactFullNameLabel: "Nome Completo",
      contactSubjectLabel: "Oggetto",
      contactMessageLabel: "Messaggio",
      contactSendLabel: "Invia",
      contactSuccessMessage: "Il tuo messaggio e stato inviato con successo."
    },
    "de-DE": {
      trackPanelTitle: "Bestellung verfolgen",
      trackPanelHelp: "Geben Sie Bestellnummer und E-Mail ein, um den Status zu prufen.",
      trackOrderNumberLabel: "Bestellnummer",
      trackEmailLabel: "E-Mail-Adresse",
      trackButtonLabel: "Bestellung verfolgen",
      trackStatusFound: "Bestellstatus gefunden.",
      trackStatusNotFound: "Bestellung nicht gefunden. Bitte Bestellnummer und E-Mail prufen.",
      trackStatusError: "Etwas ist schiefgelaufen. Bitte erneut versuchen.",
      formRequiredError: "Bitte alle Pflichtfelder ausfullen.",
      formEmailError: "Bitte eine gultige E-Mail-Adresse eingeben.",
      returnPanelTitle: "Ruckgaberecht",
      returnIntro: "Wir mochten, dass Sie mit Ihrer Bestellung zufrieden sind. Bitte lesen Sie die Ruckgabebedingungen.",
      returnPoint1: "Ruckgaben sind innerhalb von 14 Tagen nach Lieferung moglich.",
      returnPoint2: "Artikel mussen unbenutzt und im Originalzustand sein.",
      returnPoint3: "Personalisierte Produkte sind nur bei Schaden oder Fehlern ruckgabefahig.",
      returnPoint4: "Ruckerstattungen erfolgen nach Prufung der Rucksendung.",
      returnPoint5: "Kontaktieren Sie den Support vor dem Versand einer Ruckgabe.",
      returnHowTitle: "So fordern Sie eine Ruckgabe an",
      returnHowText: "Senden Sie Bestellnummer und Grund uber das Kontaktformular. Wir senden die nachsten Schritte.",
      returnRefundTitle: "Ruckerstattung und Versandkosten",
      returnRefundText: "Genehmigte Ruckerstattungen dauern meist 5-10 Werktage. Versandkosten hangen vom Grund ab.",
      faqPanelTitle: "Haufige Fragen",
      faqQ1: "Wie lange dauert der Versand?",
      faqA1: "Die meisten Bestellungen kommen in Italien in 2-4 Tagen, in Europa in 3-6 Tagen und weltweit in 5-10 Tagen an.",
      faqQ2: "Liefern Sie international?",
      faqA2: "Ja, wir liefern international. Die Lieferzeit hangt vom Zielland ab.",
      faqQ3: "Kann ich personalisierte Produkte zuruckgeben?",
      faqA3: "Personalisierte Produkte konnen nur bei Schaden oder Fehlern zuruckgegeben werden.",
      faqQ4: "Wie funktioniert das individuelle Design?",
      faqA4: "Produkt auswahlen, Text oder Grafik hinzufugen, Datei hochladen und Vorschau vor der Bestellung prufen.",
      faqQ5: "Welche Zahlungsmethoden akzeptieren Sie?",
      faqA5: "Wir akzeptieren die wichtigsten Karten und verfugbare Online-Zahlungsmethoden.",
      faqQ6: "Wie kann ich meine Bestellung verfolgen?",
      faqA6: "Nach dem Versand erhalten Sie einen Tracking-Link per E-Mail.",
      faqQ7: "Wie kontaktiere ich den Support?",
      faqA7: "Offnen Sie das Kontaktformular im Footer und senden Sie Ihre Anfrage.",
      contactPanelTitle: "Kontakt",
      contactFullNameLabel: "Vollstandiger Name",
      contactSubjectLabel: "Betreff",
      contactMessageLabel: "Nachricht",
      contactSendLabel: "Senden",
      contactSuccessMessage: "Ihre Nachricht wurde erfolgreich gesendet."
    },
    "fr-FR": {
      trackPanelTitle: "Suivre votre commande",
      trackPanelHelp: "Entrez votre numero de commande et votre email pour verifier le statut.",
      trackOrderNumberLabel: "Numero de commande",
      trackEmailLabel: "Adresse email",
      trackButtonLabel: "Suivre la commande",
      trackStatusFound: "Statut de commande trouve.",
      trackStatusNotFound: "Commande introuvable. Verifiez numero de commande et email.",
      trackStatusError: "Une erreur est survenue. Veuillez reessayer.",
      formRequiredError: "Veuillez remplir tous les champs obligatoires.",
      formEmailError: "Veuillez saisir une adresse email valide.",
      returnPanelTitle: "Politique de retour",
      returnIntro: "Nous voulons que vous soyez satisfait de votre commande. Veuillez lire les conditions de retour.",
      returnPoint1: "Les retours sont acceptes sous 14 jours apres livraison.",
      returnPoint2: "Les articles doivent etre inutilises et en etat d'origine.",
      returnPoint3: "Les produits personnalises peuvent etre refuses sauf en cas de dommage ou erreur.",
      returnPoint4: "Les remboursements sont traites apres inspection.",
      returnPoint5: "Contactez le support avant d'envoyer un retour.",
      returnHowTitle: "Comment demander un retour",
      returnHowText: "Envoyez numero de commande et motif via le formulaire Contact. Nous confirmerons la suite.",
      returnRefundTitle: "Delai remboursement et livraison",
      returnRefundText: "Les remboursements approuves prennent en general 5-10 jours ouvres.",
      faqPanelTitle: "Questions frequentes",
      faqQ1: "Combien de temps prend la livraison ?",
      faqA1: "La plupart des commandes arrivent en 2-4 jours en Italie, 3-6 jours en Europe, 5-10 jours dans le monde.",
      faqQ2: "Livrez-vous a l'international ?",
      faqA2: "Oui, nous livrons a l'international. Les delais varient selon la destination.",
      faqQ3: "Puis-je retourner des produits personnalises ?",
      faqA3: "Les retours personnalises sont acceptes uniquement en cas de produit endommage ou incorrect.",
      faqQ4: "Comment fonctionne le design personnalise ?",
      faqA4: "Choisissez le produit, ajoutez texte ou graphisme, importez vos fichiers et validez l'aperu.",
      faqQ5: "Quels moyens de paiement acceptez-vous ?",
      faqA5: "Nous acceptons les principales cartes et options de paiement en ligne disponibles.",
      faqQ6: "Comment suivre ma commande ?",
      faqA6: "Apres expedition, vous recevrez un lien de suivi par email.",
      faqQ7: "Comment contacter le support ?",
      faqA7: "Ouvrez le formulaire Contact dans le footer et envoyez votre demande.",
      contactPanelTitle: "Contactez-nous",
      contactFullNameLabel: "Nom complet",
      contactSubjectLabel: "Sujet",
      contactMessageLabel: "Message",
      contactSendLabel: "Envoyer",
      contactSuccessMessage: "Votre message a ete envoye avec succes."
    },
    "es-ES": {
      trackPanelTitle: "Rastrear tu pedido",
      trackPanelHelp: "Ingresa numero de pedido y correo para ver el estado.",
      trackOrderNumberLabel: "Numero de pedido",
      trackEmailLabel: "Correo electronico",
      trackButtonLabel: "Rastrear pedido",
      trackStatusFound: "Estado del pedido encontrado.",
      trackStatusNotFound: "Pedido no encontrado. Verifica numero de pedido y correo.",
      trackStatusError: "Algo salio mal. Intentalo de nuevo.",
      formRequiredError: "Completa todos los campos obligatorios.",
      formEmailError: "Ingresa un correo electronico valido.",
      returnPanelTitle: "Politica de devolucion",
      returnIntro: "Queremos que estes satisfecho con tu pedido. Revisa las condiciones de devolucion.",
      returnPoint1: "Se aceptan devoluciones dentro de 14 dias desde la entrega.",
      returnPoint2: "Los articulos deben estar sin uso y en estado original.",
      returnPoint3: "Los productos personalizados pueden no ser elegibles salvo dano o error.",
      returnPoint4: "Los reembolsos se procesan despues de la inspeccion.",
      returnPoint5: "Contacta soporte antes de enviar una devolucion.",
      returnHowTitle: "Como solicitar una devolucion",
      returnHowText: "Envia numero de pedido y motivo por el formulario de contacto.",
      returnRefundTitle: "Tiempo de reembolso y envio",
      returnRefundText: "Los reembolsos aprobados suelen completarse en 5-10 dias habiles.",
      faqPanelTitle: "Preguntas frecuentes",
      faqQ1: "Cuanto tarda el envio?",
      faqA1: "La mayoria llega en 2-4 dias en Italia, 3-6 en Europa y 5-10 en el resto del mundo.",
      faqQ2: "Hacen envios internacionales?",
      faqA2: "Si, hacemos envios internacionales. El tiempo depende del destino.",
      faqQ3: "Puedo devolver productos personalizados?",
      faqA3: "Solo se aceptan si llegan danados o incorrectos.",
      faqQ4: "Como funciona el diseno personalizado?",
      faqA4: "Elige producto, agrega texto o graficos, sube archivos y revisa la vista previa.",
      faqQ5: "Que metodos de pago aceptan?",
      faqA5: "Aceptamos tarjetas principales y metodos online disponibles en tu pais.",
      faqQ6: "Como puedo rastrear mi pedido?",
      faqA6: "Despues del envio recibiras un enlace de seguimiento por correo.",
      faqQ7: "Como contacto a soporte?",
      faqA7: "Abre el formulario Contact Us en el footer y envia tu solicitud.",
      contactPanelTitle: "Contactanos",
      contactFullNameLabel: "Nombre completo",
      contactSubjectLabel: "Asunto",
      contactMessageLabel: "Mensaje",
      contactSendLabel: "Enviar",
      contactSuccessMessage: "Tu mensaje fue enviado correctamente."
    },
    "nl-NL": {
      trackPanelTitle: "Volg je bestelling",
      trackPanelHelp: "Vul je bestelnummer en e-mail in om de status te bekijken.",
      trackOrderNumberLabel: "Bestelnummer",
      trackEmailLabel: "E-mailadres",
      trackButtonLabel: "Bestelling volgen",
      trackStatusFound: "Bestelstatus gevonden.",
      trackStatusNotFound: "Bestelling niet gevonden. Controleer bestelnummer en e-mail.",
      trackStatusError: "Er is iets misgegaan. Probeer opnieuw.",
      formRequiredError: "Vul alle verplichte velden in.",
      formEmailError: "Vul een geldig e-mailadres in.",
      returnPanelTitle: "Retourbeleid",
      returnIntro: "We willen dat je tevreden bent met je bestelling. Lees de retourvoorwaarden.",
      returnPoint1: "Retourneren kan binnen 14 dagen na levering.",
      returnPoint2: "Artikelen moeten ongebruikt en in originele staat zijn.",
      returnPoint3: "Gepersonaliseerde producten kunnen beperkt retourneerbaar zijn, behalve bij schade of fout.",
      returnPoint4: "Terugbetalingen worden verwerkt na controle.",
      returnPoint5: "Neem contact op met support voordat je iets terugstuurt.",
      returnHowTitle: "Hoe vraag je een retour aan",
      returnHowText: "Stuur bestelnummer en reden via het contactformulier. We sturen de volgende stappen.",
      returnRefundTitle: "Terugbetaling en verzendkosten",
      returnRefundText: "Goedgekeurde terugbetalingen duren meestal 5-10 werkdagen.",
      faqPanelTitle: "Veelgestelde vragen",
      faqQ1: "Hoe lang duurt verzending?",
      faqA1: "Meestal 2-4 dagen in Italie, 3-6 dagen in Europa en 5-10 dagen wereldwijd.",
      faqQ2: "Verzenden jullie internationaal?",
      faqA2: "Ja, we verzenden internationaal. Levertijd hangt af van bestemming.",
      faqQ3: "Kan ik gepersonaliseerde producten retourneren?",
      faqA3: "Alleen bij beschadiging of foutief product.",
      faqQ4: "Hoe werkt custom design?",
      faqA4: "Kies product, voeg tekst of grafiek toe, upload je ontwerp en bekijk de preview.",
      faqQ5: "Welke betaalmethoden accepteren jullie?",
      faqA5: "Wij accepteren grote kaarten en beschikbare online betaalmethoden.",
      faqQ6: "Hoe volg ik mijn bestelling?",
      faqA6: "Na verzending ontvang je een trackinglink per e-mail.",
      faqQ7: "Hoe neem ik contact op met support?",
      faqA7: "Open Contact Us in de footer en stuur je bericht.",
      contactPanelTitle: "Contact",
      contactFullNameLabel: "Volledige naam",
      contactSubjectLabel: "Onderwerp",
      contactMessageLabel: "Bericht",
      contactSendLabel: "Verzenden",
      contactSuccessMessage: "Je bericht is succesvol verzonden."
    },
    "pl-PL": {
      trackPanelTitle: "Sledz zamowienie",
      trackPanelHelp: "Wpisz numer zamowienia i email, aby sprawdzic status.",
      trackOrderNumberLabel: "Numer zamowienia",
      trackEmailLabel: "Adres email",
      trackButtonLabel: "Sledz zamowienie",
      trackStatusFound: "Status zamowienia znaleziony.",
      trackStatusNotFound: "Nie znaleziono zamowienia. Sprawdz numer i email.",
      trackStatusError: "Wystapil blad. Sprobuj ponownie.",
      formRequiredError: "Wypelnij wszystkie wymagane pola.",
      formEmailError: "Podaj poprawny adres email.",
      returnPanelTitle: "Polityka zwrotow",
      returnIntro: "Chcemy, abys byl zadowolony z zamowienia. Przeczytaj zasady zwrotu.",
      returnPoint1: "Zwroty sa akceptowane w ciagu 14 dni od dostawy.",
      returnPoint2: "Produkty musza byc nieuzywane i w oryginalnym stanie.",
      returnPoint3: "Produkty personalizowane moga byc wykluczone, chyba ze uszkodzone lub bledne.",
      returnPoint4: "Zwrot srodkow jest realizowany po inspekcji.",
      returnPoint5: "Skontaktuj sie z supportem przed odeslaniem produktu.",
      returnHowTitle: "Jak zglosic zwrot",
      returnHowText: "Wyslij numer zamowienia i powod przez formularz kontaktowy.",
      returnRefundTitle: "Czas zwrotu i koszty wysylki",
      returnRefundText: "Zaakceptowane zwroty sa realizowane zwykle w 5-10 dni roboczych.",
      faqPanelTitle: "Najczestsze pytania",
      faqQ1: "Jak dlugo trwa wysylka?",
      faqA1: "Wiekszosc zamowien dociera w 2-4 dni we Wloszech, 3-6 dni w Europie i 5-10 dni na swiecie.",
      faqQ2: "Czy wysylacie miedzynarodowo?",
      faqA2: "Tak, wysylamy miedzynarodowo. Czas zalezy od kraju docelowego.",
      faqQ3: "Czy moge zwrocic produkt personalizowany?",
      faqA3: "Tylko gdy produkt jest uszkodzony lub niezgodny.",
      faqQ4: "Jak dziala custom design?",
      faqA4: "Wybierz produkt, dodaj tekst lub grafike, przeslij plik i sprawdz podglad.",
      faqQ5: "Jakie metody platnosci akceptujecie?",
      faqA5: "Akceptujemy glowne karty i dostepne platnosci online.",
      faqQ6: "Jak moge sledzic zamowienie?",
      faqA6: "Po wysylce otrzymasz link sledzenia na email.",
      faqQ7: "Jak skontaktowac sie z supportem?",
      faqA7: "Otworz formularz Contact Us w stopce i wyslij wiadomosc.",
      contactPanelTitle: "Skontaktuj sie z nami",
      contactFullNameLabel: "Imie i nazwisko",
      contactSubjectLabel: "Temat",
      contactMessageLabel: "Wiadomosc",
      contactSendLabel: "Wyslij",
      contactSuccessMessage: "Twoja wiadomosc zostala pomyslnie wyslana."
    },
    "sv-SE": {
      trackPanelTitle: "Spara din order",
      trackPanelHelp: "Ange ordernummer och e-post for att kontrollera status.",
      trackOrderNumberLabel: "Ordernummer",
      trackEmailLabel: "E-postadress",
      trackButtonLabel: "Spar order",
      trackStatusFound: "Orderstatus hittad.",
      trackStatusNotFound: "Order hittades inte. Kontrollera ordernummer och e-post.",
      trackStatusError: "Nagot gick fel. Forsok igen.",
      formRequiredError: "Fyll i alla obligatoriska falt.",
      formEmailError: "Ange en giltig e-postadress.",
      returnPanelTitle: "Returpolicy",
      returnIntro: "Vi vill att du ska vara nojd med din bestallning. Las returvillkoren.",
      returnPoint1: "Returer accepteras inom 14 dagar efter leverans.",
      returnPoint2: "Varor maste vara oanvanda och i originalskick.",
      returnPoint3: "Personliga produkter kanske inte kan returneras utom vid skada eller fel.",
      returnPoint4: "Aterbetalning behandlas efter inspektion.",
      returnPoint5: "Kontakta support innan du skickar en retur.",
      returnHowTitle: "Hur du begar retur",
      returnHowText: "Skicka ordernummer och orsak via kontaktformularet.",
      returnRefundTitle: "Aterbetalning och frakt",
      returnRefundText: "Godkanda aterbetalningar tar vanligtvis 5-10 arbetsdagar.",
      faqPanelTitle: "Vanliga fragor",
      faqQ1: "Hur lang tid tar frakten?",
      faqA1: "De flesta bestallningar levereras pa 2-4 dagar i Italien, 3-6 dagar i Europa och 5-10 dagar globalt.",
      faqQ2: "Levererar ni internationellt?",
      faqA2: "Ja, vi levererar internationellt. Leveranstid beror pa destination.",
      faqQ3: "Kan jag returnera personliga produkter?",
      faqA3: "Endast om produkten ar skadad eller felaktig.",
      faqQ4: "Hur fungerar custom design?",
      faqA4: "Valj produkt, lagg till text eller grafik, ladda upp fil och granska forhandsvisning.",
      faqQ5: "Vilka betalningsmetoder accepterar ni?",
      faqA5: "Vi accepterar de vanligaste korten och onlinebetalningar.",
      faqQ6: "Hur sporar jag min bestallning?",
      faqA6: "Efter leverans far du en sparningslank via e-post.",
      faqQ7: "Hur kontaktar jag support?",
      faqA7: "Oppna Contact Us-formularet i footern och skicka din fraga.",
      contactPanelTitle: "Kontakta oss",
      contactFullNameLabel: "Fullstandigt namn",
      contactSubjectLabel: "Amne",
      contactMessageLabel: "Meddelande",
      contactSendLabel: "Skicka",
      contactSuccessMessage: "Ditt meddelande har skickats."
    },
    "de-CH": {
      trackPanelTitle: "Bestellung verfolgen",
      trackPanelHelp: "Geben Sie Bestellnummer und E-Mail ein, um den Status zu prufen.",
      trackOrderNumberLabel: "Bestellnummer",
      trackEmailLabel: "E-Mail-Adresse",
      trackButtonLabel: "Bestellung verfolgen",
      trackStatusFound: "Bestellstatus gefunden.",
      trackStatusNotFound: "Bestellung nicht gefunden. Bitte Bestellnummer und E-Mail prufen.",
      trackStatusError: "Etwas ist schiefgelaufen. Bitte erneut versuchen.",
      formRequiredError: "Bitte alle Pflichtfelder ausfullen.",
      formEmailError: "Bitte eine gultige E-Mail-Adresse eingeben.",
      returnPanelTitle: "Ruckgaberecht",
      returnIntro: "Wir mochten, dass Sie mit Ihrer Bestellung zufrieden sind. Bitte lesen Sie die Ruckgabebedingungen.",
      returnPoint1: "Ruckgaben sind innerhalb von 14 Tagen nach Lieferung moglich.",
      returnPoint2: "Artikel mussen unbenutzt und im Originalzustand sein.",
      returnPoint3: "Personalisierte Produkte sind nur bei Schaden oder Fehlern ruckgabefahig.",
      returnPoint4: "Ruckerstattungen erfolgen nach Prufung der Rucksendung.",
      returnPoint5: "Kontaktieren Sie den Support vor dem Versand einer Ruckgabe.",
      returnHowTitle: "So fordern Sie eine Ruckgabe an",
      returnHowText: "Senden Sie Bestellnummer und Grund uber das Kontaktformular. Wir senden die nachsten Schritte.",
      returnRefundTitle: "Ruckerstattung und Versandkosten",
      returnRefundText: "Genehmigte Ruckerstattungen dauern meist 5-10 Werktage. Versandkosten hangen vom Grund ab.",
      faqPanelTitle: "Haufige Fragen",
      faqQ1: "Wie lange dauert der Versand?",
      faqA1: "Die meisten Bestellungen kommen in Italien in 2-4 Tagen, in Europa in 3-6 Tagen und weltweit in 5-10 Tagen an.",
      faqQ2: "Liefern Sie international?",
      faqA2: "Ja, wir liefern international. Die Lieferzeit hangt vom Zielland ab.",
      faqQ3: "Kann ich personalisierte Produkte zuruckgeben?",
      faqA3: "Personalisierte Produkte konnen nur bei Schaden oder Fehlern zuruckgegeben werden.",
      faqQ4: "Wie funktioniert das individuelle Design?",
      faqA4: "Produkt auswahlen, Text oder Grafik hinzufugen, Datei hochladen und Vorschau vor der Bestellung prufen.",
      faqQ5: "Welche Zahlungsmethoden akzeptieren Sie?",
      faqA5: "Wir akzeptieren die wichtigsten Karten und verfugbare Online-Zahlungsmethoden.",
      faqQ6: "Wie kann ich meine Bestellung verfolgen?",
      faqA6: "Nach dem Versand erhalten Sie einen Tracking-Link per E-Mail.",
      faqQ7: "Wie kontaktiere ich den Support?",
      faqA7: "Offnen Sie das Kontaktformular im Footer und senden Sie Ihre Anfrage.",
      contactPanelTitle: "Kontakt",
      contactFullNameLabel: "Vollstandiger Name",
      contactSubjectLabel: "Betreff",
      contactMessageLabel: "Nachricht",
      contactSendLabel: "Senden",
      contactSuccessMessage: "Ihre Nachricht wurde erfolgreich gesendet."
    }
  };

  const ABOUT_PAGE_I18N = {
    "it-IT": {
      aboutPageVideoAria: "Video Chi Siamo",
      aboutPageSectionAria: "Chi e GirffoN",
      aboutPageHeadingHtml: "Chi e <span>GirffoN</span>",
      aboutPageP1: "In GirffoN crediamo che una T-shirt non sia solo un capo: e una voce che parla.",
      aboutPageP2: "Ogni nostro design racconta una storia di cultura, storia, identita e bellezza.",
      aboutPageP3: "Dalla Persia a Roma, fino all'anima di Parigi e all'orgoglio di Vienna...",
      aboutPageP4: "Le nostre T-shirt non sono create solo per essere viste. Sono create per essere sentite.",
      aboutPageP5: "Ogni pezzo GirffoN e realizzato con significato, per chi indossa con orgoglio la propria cultura e il proprio stile.",
      aboutPageS1: "Indossa la tua eredita.",
      aboutPageS2: "Senti la tua forza.",
      aboutPageS3: "Vivi il tuo stile."
    },
    "de-DE": {
      aboutPageVideoAria: "Uber uns Video",
      aboutPageSectionAria: "Uber GirffoN",
      aboutPageHeadingHtml: "Uber <span>GirffoN</span>",
      aboutPageP1: "Bei GirffoN glauben wir: Ein T-Shirt ist nicht nur Kleidung, sondern eine Stimme.",
      aboutPageP2: "Jedes unserer Designs erzahlt eine Geschichte von Kultur, Geschichte, Identitat und Schonheit.",
      aboutPageP3: "Von Persien uber Rom bis zur Seele von Paris und dem Stolz von Wien...",
      aboutPageP4: "Unsere T-Shirts sind nicht nur dafur gemacht, gesehen zu werden. Sie sollen gefuhlt werden.",
      aboutPageP5: "Jedes GirffoN-Stuck wird mit Bedeutung gefertigt fur Menschen, die Kultur und Stil mit Stolz tragen.",
      aboutPageS1: "Trage dein Erbe.",
      aboutPageS2: "Spure deine Kraft.",
      aboutPageS3: "Lebe deinen Stil."
    },
    "fr-FR": {
      aboutPageVideoAria: "Video A propos",
      aboutPageSectionAria: "A propos de GirffoN",
      aboutPageHeadingHtml: "A propos de <span>GirffoN</span>",
      aboutPageP1: "Chez GirffoN, nous pensons qu'un T-shirt n'est pas seulement un vetement, c'est une voix.",
      aboutPageP2: "Chacun de nos designs raconte une histoire de culture, d'histoire, d'identite et de beaute.",
      aboutPageP3: "Des Perses a Rome, jusqu'a l'ame de Paris et la fierte de Vienne...",
      aboutPageP4: "Nos T-shirts ne sont pas seulement faits pour etre vus. Ils sont faits pour etre ressentis.",
      aboutPageP5: "Chaque piece GirffoN est creee avec du sens, pour ceux qui portent leur culture et leur style avec fierte.",
      aboutPageS1: "Portez votre heritage.",
      aboutPageS2: "Ressentez votre force.",
      aboutPageS3: "Vivez votre style."
    },
    "es-ES": {
      aboutPageVideoAria: "Video Sobre nosotros",
      aboutPageSectionAria: "Sobre GirffoN",
      aboutPageHeadingHtml: "Sobre <span>GirffoN</span>",
      aboutPageP1: "En GirffoN creemos que una camiseta no es solo ropa: es una voz que habla.",
      aboutPageP2: "Cada uno de nuestros disenos cuenta una historia de cultura, historia, identidad y belleza.",
      aboutPageP3: "Desde Persia hasta Roma, hasta el alma de Paris y el orgullo de Viena...",
      aboutPageP4: "Nuestras camisetas no estan hechas solo para verse. Estan hechas para sentirse.",
      aboutPageP5: "Cada pieza de GirffoN se crea con significado, para quienes llevan su cultura y su estilo con orgullo.",
      aboutPageS1: "Lleva tu legado.",
      aboutPageS2: "Siente tu poder.",
      aboutPageS3: "Vive tu estilo."
    },
    "nl-NL": {
      aboutPageVideoAria: "Over ons video",
      aboutPageSectionAria: "Over GirffoN",
      aboutPageHeadingHtml: "Over <span>GirffoN</span>",
      aboutPageP1: "Bij GirffoN geloven we dat een T-shirt niet alleen kleding is - het is een stem die spreekt.",
      aboutPageP2: "Elk van onze ontwerpen vertelt een verhaal over cultuur, geschiedenis, identiteit en schoonheid.",
      aboutPageP3: "Van de Perzen tot Rome, tot de ziel van Parijs en de trots van Wenen...",
      aboutPageP4: "Onze T-shirts zijn niet alleen gemaakt om gezien te worden. Ze zijn gemaakt om gevoeld te worden.",
      aboutPageP5: "Elk GirffoN-item is gemaakt met betekenis, voor wie zijn cultuur en stijl met trots draagt.",
      aboutPageS1: "Draag je erfenis.",
      aboutPageS2: "Voel je kracht.",
      aboutPageS3: "Leef je stijl."
    },
    "pl-PL": {
      aboutPageVideoAria: "Film O nas",
      aboutPageSectionAria: "O GirffoN",
      aboutPageHeadingHtml: "O <span>GirffoN</span>",
      aboutPageP1: "W GirffoN wierzymy, ze T-shirt to nie tylko ubranie - to glos, ktory przemawia.",
      aboutPageP2: "Kazdy nasz projekt opowiada historie kultury, dziejow, tozsamosci i piekna.",
      aboutPageP3: "Od Persji po Rzym, po dusze Paryza i dume Wiednia...",
      aboutPageP4: "Nasze T-shirty nie sa tworzone tylko po to, by je ogladac. Sa tworzone po to, by je czuc.",
      aboutPageP5: "Kazdy produkt GirffoN ma znaczenie, dla tych, ktorzy z duma nosza swoja kulture i styl.",
      aboutPageS1: "Nos swoje dziedzictwo.",
      aboutPageS2: "Poczuj swoja sile.",
      aboutPageS3: "Zyj swoim stylem."
    },
    "sv-SE": {
      aboutPageVideoAria: "Om oss video",
      aboutPageSectionAria: "Om GirffoN",
      aboutPageHeadingHtml: "Om <span>GirffoN</span>",
      aboutPageP1: "Pa GirffoN tror vi att en T-shirt inte bara ar klader - den ar en rost som talar.",
      aboutPageP2: "Varje design vi skapar berattar en historia om kultur, historia, identitet och skonhet.",
      aboutPageP3: "Fran Persien till Rom, till Parissjal och Wiens stolthet...",
      aboutPageP4: "Vara T-shirts ar inte bara skapade for att synas. De ar skapade for att kannas.",
      aboutPageP5: "Varje GirffoN-plagg ar skapat med mening, for dem som bar sin kultur och stil med stolthet.",
      aboutPageS1: "Bar ditt arv.",
      aboutPageS2: "Kann din kraft.",
      aboutPageS3: "Lev din stil."
    },
    "de-CH": {
      aboutPageVideoAria: "Uber uns Video",
      aboutPageSectionAria: "Uber GirffoN",
      aboutPageHeadingHtml: "Uber <span>GirffoN</span>",
      aboutPageP1: "Bei GirffoN glauben wir: Ein T-Shirt ist nicht nur Kleidung, sondern eine Stimme.",
      aboutPageP2: "Jedes unserer Designs erzahlt eine Geschichte von Kultur, Geschichte, Identitat und Schonheit.",
      aboutPageP3: "Von Persien uber Rom bis zur Seele von Paris und dem Stolz von Wien...",
      aboutPageP4: "Unsere T-Shirts sind nicht nur dafur gemacht, gesehen zu werden. Sie sollen gefuhlt werden.",
      aboutPageP5: "Jedes GirffoN-Stuck wird mit Bedeutung gefertigt fur Menschen, die Kultur und Stil mit Stolz tragen.",
      aboutPageS1: "Trage dein Erbe.",
      aboutPageS2: "Spure deine Kraft.",
      aboutPageS3: "Lebe deinen Stil."
    }
  };

  function getTexts(localeCode) {
    return {
      ...BASE_TEXT,
      ...(I18N[localeCode] || {}),
      ...(EXTRA_I18N[localeCode] || {}),
      ...(PANEL_I18N[localeCode] || {}),
      ...(ABOUT_PAGE_I18N[localeCode] || {})
    };
  }

  function setNodeText(node, value) {
    if (node && typeof value === "string") {
      node.textContent = value;
    }
  }

  function setNodeHTML(node, value) {
    if (node && typeof value === "string") {
      node.innerHTML = value;
    }
  }

  function getServiceBlocks(localeCode) {
    return SERVICE_BLOCK_I18N[localeCode] || SERVICE_BLOCK_I18N["en-GB"];
  }

  function getAccountPanelTexts(localeCode) {
    const resolvedLocale = ACCOUNT_PANEL_I18N[localeCode]
      ? localeCode
      : (ACCOUNT_PANEL_LOCALE_FALLBACK[localeCode] || "en-GB");

    return ACCOUNT_PANEL_I18N[resolvedLocale] || ACCOUNT_PANEL_I18N["en-GB"];
  }

  function replaceExactText(nodeList, map) {
    nodeList.forEach((node) => {
      const current = (node.textContent || "").trim();
      if (map[current]) {
        node.textContent = map[current];
      }
    });
  }

  function setPanelVisibility(visible) {
    panel.setAttribute("data-visible", visible ? "true" : "false");
    panel.setAttribute("aria-hidden", visible ? "false" : "true");
    overlay.hidden = !visible;
    document.body.style.overflow = visible ? "hidden" : "";
  }

  function parseEuroPrice(text) {
    const cleaned = text.replace(/[^\d,.-]/g, "").replace(".", "").replace(",", ".");
    const parsed = Number.parseFloat(cleaned);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function formatByLocale(valueEUR, countryCode) {
    const config = COUNTRY_CONFIG[countryCode] || COUNTRY_CONFIG[DEFAULT_COUNTRY];
    const converted = valueEUR * config.rateFromEUR;

    return new Intl.NumberFormat(config.locale, {
      style: "currency",
      currency: config.currency,
      maximumFractionDigits: 2,
      minimumFractionDigits: 2
    }).format(converted);
  }

  function updateAllPrices(countryCode) {
    document.querySelectorAll(".gx25-price").forEach((priceNode) => {
      if (!priceNode.dataset.baseEur) {
        const amount = parseEuroPrice(priceNode.textContent || "");
        if (amount !== null) {
          priceNode.dataset.baseEur = String(amount);
        }
      }

      const baseEur = Number.parseFloat(priceNode.dataset.baseEur || "");
      if (Number.isFinite(baseEur)) {
        priceNode.textContent = formatByLocale(baseEur, countryCode);
      }
    });
  }

  function applyTranslations(countryCode) {
    const config = COUNTRY_CONFIG[countryCode] || COUNTRY_CONFIG[DEFAULT_COUNTRY];
    const lang = getTexts(config.locale);
    const accountLang = getAccountPanelTexts(config.locale);

    const accountLink = document.querySelector(".top-actions a:not(.icon-link)");
    const navLinks = document.querySelectorAll(".main-nav > a");
    const searchInput = document.querySelector(".top-search input");
    const topIconLinks = document.querySelectorAll(".top-actions .icon-link");

    if (accountLink) accountLink.innerHTML = '<i class="fa-solid fa-user" title="Account"></i> ' + lang.account;
    if (topIconLinks[1]) topIconLinks[1].setAttribute("title", lang.cart);
    if (topIconLinks[2]) topIconLinks[2].setAttribute("title", lang.wishlist);
    if (topIconLinks[3]) {
      topIconLinks[3].setAttribute("title", lang.settings);
      topIconLinks[3].setAttribute("aria-label", lang.settings);
    }

    trigger.setAttribute("title", lang.localeTitle);
    trigger.setAttribute("aria-label", lang.localeTitle);

    if (navLinks[0]) navLinks[0].textContent = lang.home;
    if (navLinks[1]) navLinks[1].textContent = lang.about;
    if (navLinks[2]) navLinks[2].textContent = lang.catalog;
    if (navLinks[3]) navLinks[3].textContent = lang.contact;

    const menuAnchors = document.querySelectorAll(".menu-item > a");
    if (menuAnchors[0]) menuAnchors[0].textContent = lang.shop;
    if (menuAnchors[1]) menuAnchors[1].textContent = lang.custom;

    const menuBoxes = document.querySelectorAll(".menu-item .menu-box");
    if (menuBoxes[0]) {
      const shopItems = menuBoxes[0].querySelectorAll("a");
      setNodeText(shopItems[0], lang.shopMen);
      setNodeText(shopItems[1], lang.shopWomen);
      setNodeText(shopItems[2], lang.shopKidsBabies);
      setNodeText(shopItems[3], lang.shopAccessories);
      setNodeText(shopItems[4], lang.shopHomeLiving);
    }
    if (menuBoxes[1]) {
      const customItems = menuBoxes[1].querySelectorAll("a");
      setNodeText(customItems[0], lang.customMen);
      setNodeText(customItems[1], lang.customWomen);
      setNodeText(customItems[2], lang.customKids);
      setNodeText(customItems[3], lang.customNeonati);
      setNodeText(customItems[4], lang.customAccessories);
      setNodeText(customItems[5], lang.customHomeLiving);
    }

    if (searchInput) searchInput.placeholder = lang.search;

    const localeTitle = document.getElementById("gfLocaleTitle");
    const localeSubtitle = document.getElementById("gfLocaleSubtitle");
    if (localeTitle) localeTitle.textContent = lang.localeTitle;
    if (localeSubtitle) localeSubtitle.textContent = lang.localeSubtitle;

    const closeButton = document.getElementById("gfLocaleCloseBtn");
    if (closeButton) closeButton.textContent = lang.close;

    setNodeText(document.querySelector("#gfAccountPanel .gf-account-header h3"), lang.account);
    setNodeText(document.querySelector("#gfAccountGuest .gf-account-auth-head h4"), accountLang.signIn);
    setNodeText(document.querySelector("#gfAccountGuest .gf-account-auth-head p"), accountLang.accountIntro);
    setNodeText(document.querySelector('label[for="gfLoginIdentifier"]'), accountLang.identifierLabel);
    setNodeText(document.querySelector('label[for="gfLoginPassword"]'), accountLang.passwordLabel);

    const loginIdentifier = document.getElementById("gfLoginIdentifier");
    const loginPassword = document.getElementById("gfLoginPassword");
    if (loginIdentifier) loginIdentifier.placeholder = accountLang.identifierPlaceholder;
    if (loginPassword) loginPassword.placeholder = accountLang.passwordPlaceholder;

    setNodeText(document.querySelector("#gfLoginBtn span"), accountLang.login);
    setNodeText(document.querySelector('label[for="gfStaySignedIn"] span'), accountLang.staySignedIn);
    setNodeText(document.getElementById("gfForgotAccountBtn"), accountLang.forgotUsername);
    setNodeText(document.querySelector("#gfSignupBtn span"), accountLang.createAccount);
    setNodeText(document.querySelector("#gfAccountGuest .gf-account-divider span"), accountLang.dividerOr);
    setNodeText(document.querySelector("#gfGoogleLoginBtn span"), accountLang.signInGoogle);
    setNodeText(document.querySelector("#gfAppleLoginBtn span"), accountLang.signInApple);
    setNodeText(document.querySelector(".gf-account-options-title"), accountLang.accountOptions);
    setNodeText(document.querySelector("#gfManageAccountBtn .gf-account-option-left span:last-child"), accountLang.manageAccount);
    setNodeText(document.querySelector("#gfMyDesignsBtn .gf-account-option-left span:last-child"), accountLang.myDesigns);
    setNodeText(document.querySelector("#gfOrderHistoryBtn .gf-account-option-left span:last-child"), accountLang.orderHistory);
    setNodeText(document.querySelector("#gfPaymentMethodsBtn .gf-account-option-left span:last-child"), accountLang.paymentMethods);
    setNodeText(document.querySelector("#gfShippingAddressesBtn .gf-account-option-left span:last-child"), accountLang.shippingAddresses);
    setNodeText(document.querySelector("#gfLogoutBtn span"), accountLang.logout);

    const sectionTitles = document.querySelectorAll(".gx25-title-main");
    if (sectionTitles[0]) sectionTitles[0].textContent = lang.allCollections;
    if (sectionTitles[1]) sectionTitles[1].textContent = lang.men;
    if (sectionTitles[2]) sectionTitles[2].textContent = lang.women;
    if (sectionTitles[3]) sectionTitles[3].textContent = lang.kids;

    const aboutTitle = document.querySelector(".about-custom-design-title");
    if (aboutTitle) aboutTitle.textContent = lang.aboutCustomDesign;

    const createBtn = document.querySelector(".about-custom-design-btn-dark");
    if (createBtn) createBtn.textContent = lang.createNow;

    const aboutParas = document.querySelectorAll(".about-custom-design-feature p");
    setNodeText(aboutParas[0], lang.aboutPara1);
    setNodeHTML(aboutParas[1], lang.aboutPara2Html);

    setNodeText(document.getElementById("gfCatalogKicker"), lang.catalogKicker);
    setNodeText(document.getElementById("gfCatalogHeading"), lang.catalogHeading);
    setNodeText(document.getElementById("gfCatalogIntroText"), lang.catalogIntro);
    setNodeText(document.getElementById("gfCatalogMarchTitle"), lang.catalogMarchTitle);
    setNodeText(document.getElementById("gfCatalogMarchSubtitle"), lang.catalogMarchSubtitle);
    setNodeText(document.getElementById("gfCatalogMarchView"), lang.catalogViewCover);
    setNodeText(document.getElementById("gfCatalogMarchDownload"), lang.catalogDownloadCover);
    setNodeText(document.getElementById("gfCatalogAprilTitle"), lang.catalogAprilTitle);
    setNodeText(document.getElementById("gfCatalogAprilSubtitle"), lang.catalogAprilSubtitle);
    setNodeText(document.getElementById("gfCatalogAprilView"), lang.catalogViewCover);
    setNodeText(document.getElementById("gfCatalogAprilDownload"), lang.catalogDownloadCover);
    setNodeText(document.getElementById("gfCatalogMayTitle"), lang.catalogMayTitle);
    setNodeText(document.getElementById("gfCatalogMaySubtitle"), lang.catalogMaySubtitle);
    setNodeText(document.getElementById("gfCatalogMayView"), lang.catalogViewCover);
    setNodeText(document.getElementById("gfCatalogMayDownload"), lang.catalogDownloadCover);
    setNodeText(document.getElementById("gfCatalogJuneBadge"), lang.catalogJuneBadge);
    setNodeText(document.getElementById("gfCatalogJuneTitle"), lang.catalogJuneTitle);
    setNodeText(document.getElementById("gfCatalogJuneSubtitle"), lang.catalogJuneSubtitle);
    setNodeText(document.getElementById("gfCatalogJuneView"), lang.catalogViewFlipbook);
    setNodeText(document.getElementById("gfCatalogJuneDownload"), lang.catalogDownloadPdf);

    const aboutVideoWrap = document.getElementById("gfAboutVideoWrap");
    const aboutCopyWrap = document.getElementById("gfAboutCopyWrap");
    if (aboutVideoWrap) {
      aboutVideoWrap.setAttribute("aria-label", lang.aboutPageVideoAria || "About video");
    }
    if (aboutCopyWrap) {
      aboutCopyWrap.setAttribute("aria-label", lang.aboutPageSectionAria || "About GirffoN");
    }

    setNodeHTML(document.getElementById("gfAboutPageHeading"), lang.aboutPageHeadingHtml || "About <span>GirffoN</span>");
    setNodeText(document.getElementById("gfAboutPageP1"), lang.aboutPageP1 || "");
    setNodeText(document.getElementById("gfAboutPageP2"), lang.aboutPageP2 || "");
    setNodeText(document.getElementById("gfAboutPageP3"), lang.aboutPageP3 || "");
    setNodeText(document.getElementById("gfAboutPageP4"), lang.aboutPageP4 || "");
    setNodeText(document.getElementById("gfAboutPageP5"), lang.aboutPageP5 || "");
    setNodeText(document.getElementById("gfAboutPageSignatureHeading"), lang.aboutPageSignatureHeading || "GirffoN -");
    setNodeText(document.getElementById("gfAboutPageS1"), lang.aboutPageS1 || "");
    setNodeText(document.getElementById("gfAboutPageS2"), lang.aboutPageS2 || "");
    setNodeText(document.getElementById("gfAboutPageS3"), lang.aboutPageS3 || "");

    const slides = document.querySelectorAll(".hero-slider .slide .slide-content");
    if (slides[0]) {
      const n = slides[0];
      setNodeText(n.querySelector("h2"), lang.heroTarotTitle);
      setNodeText(n.querySelector("p"), lang.heroTarotDesc);
      setNodeText(n.querySelector(".slide-btn"), lang.heroTarotBtn);
    }
    if (slides[1]) {
      const n = slides[1];
      setNodeText(n.querySelector("h2"), lang.heroMenWomenTitle);
      setNodeText(n.querySelector("p"), lang.heroMenWomenDesc);
      setNodeText(n.querySelector(".slide-btn"), lang.heroMenWomenBtn);
    }
    if (slides[2]) {
      const n = slides[2];
      setNodeText(n.querySelector("h2"), lang.heroKidsTitle);
      setNodeText(n.querySelector("p"), lang.heroKidsDesc);
      setNodeText(n.querySelector(".slide-btn"), lang.heroKidsBtn);
    }
    if (slides[3]) {
      const n = slides[3];
      setNodeText(n.querySelector("h2"), lang.heroAccHomeTitle);
      setNodeText(n.querySelector("p"), lang.heroAccHomeDesc);
      setNodeText(n.querySelector(".slide-btn"), lang.heroAccHomeBtn);
    }

    const categoryCards = document.querySelectorAll(".category-showcase .category-card .card-content");
    if (categoryCards[0]) {
      const n = categoryCards[0];
      setNodeText(n.querySelector("h3"), lang.categoryAnimalTitle);
      setNodeText(n.querySelector("p"), lang.categoryAnimalDesc);
    }
    if (categoryCards[1]) {
      const n = categoryCards[1];
      setNodeText(n.querySelector("h3"), lang.categoryMenTitle);
      setNodeText(n.querySelector("p"), lang.categoryMenDesc);
    }
    if (categoryCards[2]) {
      const n = categoryCards[2];
      setNodeText(n.querySelector("h3"), lang.categoryWomenTitle);
      setNodeText(n.querySelector("p"), lang.categoryWomenDesc);
    }
    if (categoryCards[3]) {
      const n = categoryCards[3];
      setNodeText(n.querySelector("h3"), lang.categoryKidsTitle);
      setNodeText(n.querySelector("p"), lang.categoryKidsDesc);
    }
    if (categoryCards[4]) {
      const n = categoryCards[4];
      setNodeText(n.querySelector("h3"), lang.categoryAnimationTitle);
      setNodeText(n.querySelector("p"), lang.categoryAnimationDesc);
    }

    const wideBanners = document.querySelectorAll(".wide-banner .banner-content .banner-text");
    if (wideBanners[0]) {
      const n = wideBanners[0];
      setNodeText(n.querySelector("h3"), lang.bannerAccessoriesTitle);
      setNodeText(n.querySelector("p"), lang.bannerAccessoriesDesc);
    }
    if (wideBanners[1]) {
      const n = wideBanners[1];
      setNodeText(n.querySelector("h3"), lang.bannerHomeTitle);
      setNodeText(n.querySelector("p"), lang.bannerHomeDesc);
    }

    const serviceFront = document.querySelectorAll(".service-front h3");
    const serviceBack = document.querySelectorAll(".service-back h4");
    const serviceCards = document.querySelectorAll(".service-card");
    const serviceBlocks = getServiceBlocks(config.locale);

    serviceCards.forEach((card, index) => {
      const block = serviceBlocks[index];
      if (!block) {
        return;
      }

      setNodeText(serviceFront[index], block.title);
      setNodeText(serviceBack[index], block.title);
      setNodeText(card.querySelector(".service-back > p"), block.summary);

      const full = card.querySelector(".service-full");
      if (!full) {
        return;
      }

      const fullParagraphs = full.querySelectorAll("p");
      setNodeHTML(fullParagraphs[0], `<strong>${block.title}</strong>`);
      setNodeText(fullParagraphs[1], block.summary);

      const fullList = full.querySelector("ul");
      if (fullList) {
        fullList.innerHTML = block.items.map((item) => `<li>${item}</li>`).join("");
      }

      setNodeText(fullParagraphs[2], block.outro);
    });

    document.querySelectorAll(".card-btn, .banner-btn").forEach((btn) => {
      btn.textContent = lang.shopNow;
    });

    replaceExactText(document.querySelectorAll(".review-card h3"), {
      [BASE_TEXT.reviewEasyBestTitle]: lang.reviewEasyBestTitle,
      [BASE_TEXT.reviewDesignTitle]: lang.reviewDesignTitle,
      [BASE_TEXT.reviewMugTitle]: lang.reviewMugTitle
    });

    replaceExactText(document.querySelectorAll(".review-card p"), {
      [BASE_TEXT.reviewEasyBestText]: lang.reviewEasyBestText,
      [BASE_TEXT.reviewDesignText]: lang.reviewDesignText,
      [BASE_TEXT.reviewMugText]: lang.reviewMugText
    });

    const settingsHeader = document.querySelector("#gfSettingsPanel .gf-settings-header h3 span");
    setNodeText(settingsHeader, lang.settings);

    const settingsSections = document.querySelectorAll("#gfSettingsPanel .gf-settings-section-title");
    setNodeText(settingsSections[0], lang.settingsDisplay);
    setNodeText(settingsSections[1], lang.settingsAudio);

    const labelTexts = document.querySelectorAll("#gfSettingsPanel .gf-settings-label-text");
    setNodeText(labelTexts[0], lang.settingsTheme);
    setNodeText(labelTexts[1], lang.settingsFontSize);
    setNodeText(labelTexts[2], lang.settingsBgMusic);
    setNodeText(labelTexts[3], lang.settingsTrack);
    setNodeText(labelTexts[4], lang.settingsSoundFx);
    setNodeText(labelTexts[5], lang.settingsVolume);

    const labelDescs = document.querySelectorAll("#gfSettingsPanel .gf-settings-label-desc");
    setNodeText(labelDescs[0], lang.settingsThemeDesc);
    setNodeText(labelDescs[1], lang.settingsFontSizeDesc);
    setNodeText(labelDescs[2], lang.settingsBgMusicDesc);
    setNodeText(labelDescs[3], lang.settingsTrackDesc);
    setNodeText(labelDescs[4], lang.settingsSoundFxDesc);
    setNodeText(labelDescs[5], lang.settingsVolumeDesc);

    setNodeText(document.querySelector('[data-setting="theme"][data-value="light"] span'), lang.light);
    setNodeText(document.querySelector('[data-setting="theme"][data-value="dark"] span'), lang.dark);
    setNodeText(document.querySelector('[data-setting="font"][data-value="small"]'), lang.small);
    setNodeText(document.querySelector('[data-setting="font"][data-value="medium"]'), lang.medium);
    setNodeText(document.querySelector('[data-setting="font"][data-value="large"]'), lang.large);
    setNodeText(document.querySelector('[data-setting="music"][data-value="off"] span'), lang.off);
    setNodeText(document.querySelector('[data-setting="music"][data-value="on"] span'), lang.on);
    setNodeText(document.querySelector('[data-setting="sound"][data-value="off"] span'), lang.off);
    setNodeText(document.querySelector('[data-setting="sound"][data-value="on"] span'), lang.on);

    const footerLinks = document.querySelectorAll(".footer-links a");
    if (footerLinks[0]) footerLinks[0].innerHTML = '<i class="fa-solid fa-box"></i> ' + lang.trackOrder;
    if (footerLinks[1]) footerLinks[1].innerHTML = '<i class="fa-solid fa-rotate-left"></i> ' + lang.returnPolicy;
    if (footerLinks[2]) footerLinks[2].innerHTML = '<i class="fa-solid fa-circle-question"></i> ' + lang.faqs;
    if (footerLinks[3]) footerLinks[3].innerHTML = '<i class="fa-solid fa-envelope"></i> ' + lang.contactUs;

    setNodeText(document.getElementById("gfTrackTitle")?.lastChild, " " + (lang.trackPanelTitle || "Track Your Order"));
    setNodeText(document.querySelector(".gf-track-help"), lang.trackPanelHelp || "Enter your order number and email address to check your order status.");
    setNodeText(document.querySelector('label[for="gfTrackOrderNumber"] span'), lang.trackOrderNumberLabel || "Order Number");
    setNodeText(document.querySelector('label[for="gfTrackEmail"] span'), lang.trackEmailLabel || "Email Address");
    setNodeText(document.querySelector(".gf-track-submit"), lang.trackButtonLabel || "Track Order");

    setNodeText(document.getElementById("gfReturnTitle")?.lastChild, " " + (lang.returnPanelTitle || "Return Policy"));
    setNodeText(document.querySelector(".gf-return-content p"), lang.returnIntro || "We want you to be happy with your order. Please review the key return conditions below before sending any item back.");
    const returnPoints = document.querySelectorAll(".gf-return-points li");
    setNodeText(returnPoints[0], lang.returnPoint1 || returnPoints[0]?.textContent || "");
    setNodeText(returnPoints[1], lang.returnPoint2 || returnPoints[1]?.textContent || "");
    setNodeText(returnPoints[2], lang.returnPoint3 || returnPoints[2]?.textContent || "");
    setNodeText(returnPoints[3], lang.returnPoint4 || returnPoints[3]?.textContent || "");
    setNodeText(returnPoints[4], lang.returnPoint5 || returnPoints[4]?.textContent || "");
    const returnHeadings = document.querySelectorAll(".gf-return-content h4");
    setNodeText(returnHeadings[0], lang.returnHowTitle || "How to request a return");
    setNodeText(returnHeadings[1], lang.returnRefundTitle || "Refund timing and shipping");
    const returnParas = document.querySelectorAll(".gf-return-content p");
    setNodeText(returnParas[1], lang.returnHowText || returnParas[1]?.textContent || "");
    setNodeText(returnParas[2], lang.returnRefundText || returnParas[2]?.textContent || "");

    setNodeText(document.getElementById("gfFaqTitle")?.lastChild, " " + (lang.faqPanelTitle || "Frequently Asked Questions"));
    const faqQuestions = document.querySelectorAll(".gf-faq-question");
    const faqAnswers = document.querySelectorAll(".gf-faq-answer p");
    setNodeText(faqQuestions[0], lang.faqQ1 || faqQuestions[0]?.textContent || "");
    setNodeText(faqQuestions[1], lang.faqQ2 || faqQuestions[1]?.textContent || "");
    setNodeText(faqQuestions[2], lang.faqQ3 || faqQuestions[2]?.textContent || "");
    setNodeText(faqQuestions[3], lang.faqQ4 || faqQuestions[3]?.textContent || "");
    setNodeText(faqQuestions[4], lang.faqQ5 || faqQuestions[4]?.textContent || "");
    setNodeText(faqQuestions[5], lang.faqQ6 || faqQuestions[5]?.textContent || "");
    setNodeText(faqQuestions[6], lang.faqQ7 || faqQuestions[6]?.textContent || "");
    setNodeText(faqAnswers[0], lang.faqA1 || faqAnswers[0]?.textContent || "");
    setNodeText(faqAnswers[1], lang.faqA2 || faqAnswers[1]?.textContent || "");
    setNodeText(faqAnswers[2], lang.faqA3 || faqAnswers[2]?.textContent || "");
    setNodeText(faqAnswers[3], lang.faqA4 || faqAnswers[3]?.textContent || "");
    setNodeText(faqAnswers[4], lang.faqA5 || faqAnswers[4]?.textContent || "");
    setNodeText(faqAnswers[5], lang.faqA6 || faqAnswers[5]?.textContent || "");
    setNodeText(faqAnswers[6], lang.faqA7 || faqAnswers[6]?.textContent || "");

    setNodeText(document.getElementById("gfContactTitle")?.lastChild, " " + (lang.contactPanelTitle || "Contact Us"));
    setNodeText(document.querySelector('label[for="gfContactName"] span'), lang.contactFullNameLabel || "Full Name");
    setNodeText(document.querySelector('label[for="gfContactEmail"] span'), lang.trackEmailLabel || "Email Address");
    setNodeText(document.querySelector('label[for="gfContactSubject"] span'), lang.contactSubjectLabel || "Subject");
    setNodeText(document.querySelector('label[for="gfContactMessage"] span'), lang.contactMessageLabel || "Message");
    setNodeText(document.querySelector(".gf-contact-submit"), lang.contactSendLabel || "Send");

    window.gfLocaleTexts = lang;

    document.documentElement.setAttribute("lang", (config.locale || "en-GB").split("-")[0]);
  }

  function scheduleReapply(countryCode) {
    [300, 1200, 2800].forEach((delay) => {
      window.setTimeout(() => {
        updateAllPrices(countryCode);
        applyTranslations(countryCode);
      }, delay);
    });
  }

  function setActiveCountry(countryCode) {
    const validCountry = COUNTRY_CONFIG[countryCode] ? countryCode : DEFAULT_COUNTRY;
    const cards = panel.querySelectorAll(".gf-locale-card");

    cards.forEach((card) => {
      const active = card.dataset.country === validCountry;
      card.classList.toggle("active", active);
      card.setAttribute("aria-selected", active ? "true" : "false");
    });

    localStorage.setItem(STORAGE_KEY, validCountry);
    updateAllPrices(validCountry);
    applyTranslations(validCountry);
    scheduleReapply(validCountry);
  }

  function applySavedCountry() {
    const savedCountry = localStorage.getItem(STORAGE_KEY) || DEFAULT_COUNTRY;
    setActiveCountry(savedCountry);
  }

  trigger.addEventListener("click", (event) => {
    event.preventDefault();
    const isOpen = panel.getAttribute("data-visible") === "true";
    setPanelVisibility(!isOpen);
  });

  closeBtn?.addEventListener("click", () => setPanelVisibility(false));
  closeFooterBtn?.addEventListener("click", () => setPanelVisibility(false));
  overlay.addEventListener("click", () => setPanelVisibility(false));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      setPanelVisibility(false);
    }
  });

  panel.addEventListener("click", (event) => {
    const card = event.target.closest(".gf-locale-card");
    if (!card) {
      return;
    }
    setActiveCountry(card.dataset.country || DEFAULT_COUNTRY);
  });

  applySavedCountry();
})();
