(function () {
  const LOCALE_STORAGE_KEY = "gf-locale-country";
  const DEFAULT_COUNTRY = "GB";
  const COUNTRY_TO_LOCALE = {
    IT: "it-IT",
    DE: "de-DE",
    FR: "fr-FR",
    ES: "es-ES",
    NL: "nl-NL",
    PL: "pl-PL",
    SE: "sv-SE",
    GB: "en-GB",
    US: "en-US",
    CH: "de-CH",
    CA: "en-CA"
  };
  const LOCALE_FALLBACK = {
    "en-US": "en-GB",
    "en-CA": "en-GB",
    "de-CH": "de-DE"
  };
  const LOCALE_CONFIG = {
    "it-IT": { currency: "EUR", rateFromEUR: 1.0 },
    "de-DE": { currency: "EUR", rateFromEUR: 1.0 },
    "fr-FR": { currency: "EUR", rateFromEUR: 1.0 },
    "es-ES": { currency: "EUR", rateFromEUR: 1.0 },
    "nl-NL": { currency: "EUR", rateFromEUR: 1.0 },
    "pl-PL": { currency: "PLN", rateFromEUR: 4.32 },
    "sv-SE": { currency: "SEK", rateFromEUR: 11.4 },
    "en-GB": { currency: "GBP", rateFromEUR: 0.86 },
    "en-US": { currency: "USD", rateFromEUR: 1.09 },
    "de-CH": { currency: "CHF", rateFromEUR: 0.97 },
    "en-CA": { currency: "CAD", rateFromEUR: 1.48 }
  };
  const UI_TEXTS = {
    "en-GB": {
      badge: "New",
      addToCart: "Add To Cart",
      securePayment: "Secure Payment",
      cardholder: "Cardholder Name",
      fullName: "Full Name",
      cardNumber: "Card Number",
      expiry: "Expiry",
      cvc: "CVC",
      payNow: "Pay Now",
      paymentSuccess: "Payment successful! (Demo)"
    },
    "it-IT": {
      badge: "Nuovo",
      addToCart: "Aggiungi al Carrello",
      securePayment: "Pagamento Sicuro",
      cardholder: "Nome Intestatario",
      fullName: "Nome Completo",
      cardNumber: "Numero Carta",
      expiry: "Scadenza",
      cvc: "CVC",
      payNow: "Paga Ora",
      paymentSuccess: "Pagamento completato! (Demo)"
    },
    "de-DE": {
      badge: "Neu",
      addToCart: "In den Warenkorb",
      securePayment: "Sichere Zahlung",
      cardholder: "Karteninhaber",
      fullName: "Vollständiger Name",
      cardNumber: "Kartennummer",
      expiry: "Ablauf",
      cvc: "CVC",
      payNow: "Jetzt Bezahlen",
      paymentSuccess: "Zahlung erfolgreich! (Demo)"
    },
    "fr-FR": {
      badge: "Nouveau",
      addToCart: "Ajouter au Panier",
      securePayment: "Paiement Sécurisé",
      cardholder: "Nom du Titulaire",
      fullName: "Nom Complet",
      cardNumber: "Numéro de Carte",
      expiry: "Expiration",
      cvc: "CVC",
      payNow: "Payer Maintenant",
      paymentSuccess: "Paiement réussi ! (Demo)"
    },
    "es-ES": {
      badge: "Nuevo",
      addToCart: "Añadir al Carrito",
      securePayment: "Pago Seguro",
      cardholder: "Titular de la Tarjeta",
      fullName: "Nombre Completo",
      cardNumber: "Número de Tarjeta",
      expiry: "Caducidad",
      cvc: "CVC",
      payNow: "Pagar Ahora",
      paymentSuccess: "Pago realizado con éxito! (Demo)"
    },
    "nl-NL": {
      badge: "Nieuw",
      addToCart: "Toevoegen aan Winkelwagen",
      securePayment: "Veilige Betaling",
      cardholder: "Naam Kaarthouder",
      fullName: "Volledige Naam",
      cardNumber: "Kaartnummer",
      expiry: "Vervaldatum",
      cvc: "CVC",
      payNow: "Nu Betalen",
      paymentSuccess: "Betaling geslaagd! (Demo)"
    },
    "pl-PL": {
      badge: "Nowość",
      addToCart: "Dodaj do Koszyka",
      securePayment: "Bezpieczna Płatność",
      cardholder: "Imię i Nazwisko Właściciela",
      fullName: "Imię i Nazwisko",
      cardNumber: "Numer Karty",
      expiry: "Data Ważności",
      cvc: "CVC",
      payNow: "Zapłać Teraz",
      paymentSuccess: "Płatność zakończona sukcesem! (Demo)"
    },
    "sv-SE": {
      badge: "Ny",
      addToCart: "Lägg i Varukorg",
      securePayment: "Säker Betalning",
      cardholder: "Kortinnehavarens Namn",
      fullName: "Fullständigt Namn",
      cardNumber: "Kortnummer",
      expiry: "Utgång",
      cvc: "CVC",
      payNow: "Betala Nu",
      paymentSuccess: "Betalningen lyckades! (Demo)"
    }
  };
  const PAGE_DATA = {
    women: {
      titles: {
        "en-GB": "GirffoN - Women",
        "it-IT": "GirffoN - Donna",
        "de-DE": "GirffoN - Damen",
        "fr-FR": "GirffoN - Femme",
        "es-ES": "GirffoN - Mujer",
        "nl-NL": "GirffoN - Dames",
        "pl-PL": "GirffoN - Kobiety",
        "sv-SE": "GirffoN - Dam"
      },
      sections: {
        Food: { "it-IT": "Cibo", "de-DE": "Essen", "fr-FR": "Cuisine", "es-ES": "Comida", "nl-NL": "Eten", "pl-PL": "Jedzenie", "sv-SE": "Mat" },
        Pink: { "it-IT": "Rosa", "de-DE": "Pink", "fr-FR": "Rose", "es-ES": "Rosa", "nl-NL": "Roze", "pl-PL": "Róż", "sv-SE": "Rosa" },
        Love: { "it-IT": "Amore", "de-DE": "Liebe", "fr-FR": "Amour", "es-ES": "Amor", "nl-NL": "Liefde", "pl-PL": "Miłość", "sv-SE": "Kärlek" },
        France: { "it-IT": "Francia", "de-DE": "Frankreich", "fr-FR": "France", "es-ES": "Francia", "nl-NL": "Frankrijk", "pl-PL": "Francja", "sv-SE": "Frankrike" },
        War: { "it-IT": "Guerra", "de-DE": "Krieg", "fr-FR": "Guerre", "es-ES": "Guerra", "nl-NL": "Oorlog", "pl-PL": "Wojna", "sv-SE": "Krig" },
        Hard: { "it-IT": "Hard", "de-DE": "Hart", "fr-FR": "Intense", "es-ES": "Duro", "nl-NL": "Hard", "pl-PL": "Mocne", "sv-SE": "Hård" },
        Waterpolo: { "it-IT": "Pallanuoto", "de-DE": "Wasserball", "fr-FR": "Water-polo", "es-ES": "Waterpolo", "nl-NL": "Waterpolo", "pl-PL": "Piłka wodna", "sv-SE": "Vattenpolo" }
      },
      products: {
        "Women France T-Shirt": { "it-IT": "T-Shirt Francia Donna", "de-DE": "Frankreich T-Shirt Damen", "fr-FR": "T-Shirt France Femme", "es-ES": "Camiseta Francia Mujer", "nl-NL": "Frankrijk T-Shirt Dames", "pl-PL": "Damski T-Shirt Francja", "sv-SE": "Frankrike T-Shirt Dam" },
        "Women Italy T-Shirt": { "it-IT": "T-Shirt Italia Donna", "de-DE": "Italien T-Shirt Damen", "fr-FR": "T-Shirt Italie Femme", "es-ES": "Camiseta Italia Mujer", "nl-NL": "Italië T-Shirt Dames", "pl-PL": "Damski T-Shirt Włochy", "sv-SE": "Italien T-Shirt Dam" },
        "Women Japan T-Shirt": { "it-IT": "T-Shirt Giappone Donna", "de-DE": "Japan T-Shirt Damen", "fr-FR": "T-Shirt Japon Femme", "es-ES": "Camiseta Japón Mujer", "nl-NL": "Japan T-Shirt Dames", "pl-PL": "Damski T-Shirt Japonia", "sv-SE": "Japan T-Shirt Dam" },
        "Women France Premium": { "it-IT": "Premium Francia Donna", "de-DE": "Premium Frankreich Damen", "fr-FR": "Premium France Femme", "es-ES": "Premium Francia Mujer", "nl-NL": "Premium Frankrijk Dames", "pl-PL": "Premium Francja Damski", "sv-SE": "Premium Frankrike Dam" },
        "Women Italy Premium": { "it-IT": "Premium Italia Donna", "de-DE": "Premium Italien Damen", "fr-FR": "Premium Italie Femme", "es-ES": "Premium Italia Mujer", "nl-NL": "Premium Italië Dames", "pl-PL": "Premium Włochy Damski", "sv-SE": "Premium Italien Dam" },
        "Women Japan Premium": { "it-IT": "Premium Giappone Donna", "de-DE": "Premium Japan Damen", "fr-FR": "Premium Japon Femme", "es-ES": "Premium Japón Mujer", "nl-NL": "Premium Japan Dames", "pl-PL": "Premium Japonia Damski", "sv-SE": "Premium Japan Dam" }
      }
    },
    men: {
      titles: {
        "en-GB": "GirffoN - Men",
        "it-IT": "GirffoN - Uomo",
        "de-DE": "GirffoN - Herren",
        "fr-FR": "GirffoN - Homme",
        "es-ES": "GirffoN - Hombre",
        "nl-NL": "GirffoN - Heren",
        "pl-PL": "GirffoN - Mężczyźni",
        "sv-SE": "GirffoN - Herr"
      },
      sections: {
        Fish: { "it-IT": "Pesce", "de-DE": "Fisch", "fr-FR": "Poisson", "es-ES": "Pez", "nl-NL": "Vis", "pl-PL": "Ryba", "sv-SE": "Fisk" },
        Cat: { "it-IT": "Gatto", "de-DE": "Katze", "fr-FR": "Chat", "es-ES": "Gato", "nl-NL": "Kat", "pl-PL": "Kot", "sv-SE": "Katt" },
        Dog: { "it-IT": "Cane", "de-DE": "Hund", "fr-FR": "Chien", "es-ES": "Perro", "nl-NL": "Hond", "pl-PL": "Pies", "sv-SE": "Hund" },
        Sea: { "it-IT": "Mare", "de-DE": "Meer", "fr-FR": "Mer", "es-ES": "Mar", "nl-NL": "Zee", "pl-PL": "Morze", "sv-SE": "Hav" },
        World: { "it-IT": "Mondo", "de-DE": "Welt", "fr-FR": "Monde", "es-ES": "Mundo", "nl-NL": "Wereld", "pl-PL": "Świat", "sv-SE": "Värld" },
        Art: { "it-IT": "Arte", "de-DE": "Kunst", "fr-FR": "Art", "es-ES": "Arte", "nl-NL": "Kunst", "pl-PL": "Sztuka", "sv-SE": "Konst" },
        Football: { "it-IT": "Calcio", "de-DE": "Fußball", "fr-FR": "Football", "es-ES": "Fútbol", "nl-NL": "Voetbal", "pl-PL": "Piłka nożna", "sv-SE": "Fotboll" }
      },
      products: {
        "France T-Shirt": { "it-IT": "T-Shirt Francia", "de-DE": "Frankreich T-Shirt", "fr-FR": "T-Shirt France", "es-ES": "Camiseta Francia", "nl-NL": "Frankrijk T-Shirt", "pl-PL": "T-Shirt Francja", "sv-SE": "Frankrike T-Shirt" },
        "Italy T-Shirt": { "it-IT": "T-Shirt Italia", "de-DE": "Italien T-Shirt", "fr-FR": "T-Shirt Italie", "es-ES": "Camiseta Italia", "nl-NL": "Italië T-Shirt", "pl-PL": "T-Shirt Włochy", "sv-SE": "Italien T-Shirt" },
        "USA T-Shirt": { "it-IT": "T-Shirt USA", "de-DE": "USA T-Shirt", "fr-FR": "T-Shirt USA", "es-ES": "Camiseta EE. UU.", "nl-NL": "VS T-Shirt", "pl-PL": "T-Shirt USA", "sv-SE": "USA T-Shirt" },
        "Women France T-Shirt": { "it-IT": "T-Shirt Francia Donna", "de-DE": "Frankreich T-Shirt Damen", "fr-FR": "T-Shirt France Femme", "es-ES": "Camiseta Francia Mujer", "nl-NL": "Frankrijk T-Shirt Dames", "pl-PL": "Damski T-Shirt Francja", "sv-SE": "Frankrike T-Shirt Dam" },
        "Women Italy T-Shirt": { "it-IT": "T-Shirt Italia Donna", "de-DE": "Italien T-Shirt Damen", "fr-FR": "T-Shirt Italie Femme", "es-ES": "Camiseta Italia Mujer", "nl-NL": "Italië T-Shirt Dames", "pl-PL": "Damski T-Shirt Włochy", "sv-SE": "Italien T-Shirt Dam" },
        "Women Japan T-Shirt": { "it-IT": "T-Shirt Giappone Donna", "de-DE": "Japan T-Shirt Damen", "fr-FR": "T-Shirt Japon Femme", "es-ES": "Camiseta Japón Mujer", "nl-NL": "Japan T-Shirt Dames", "pl-PL": "Damski T-Shirt Japonia", "sv-SE": "Japan T-Shirt Dam" }
      }
    },
    kids: {
      titles: {
        "en-GB": "GirffoN - Kids",
        "it-IT": "GirffoN - Bambini",
        "de-DE": "GirffoN - Kinder",
        "fr-FR": "GirffoN - Enfants",
        "es-ES": "GirffoN - Niños",
        "nl-NL": "GirffoN - Kinderen",
        "pl-PL": "GirffoN - Dzieci",
        "sv-SE": "GirffoN - Barn"
      },
      sections: {
        "Teddy Bear": { "it-IT": "Orsetto", "de-DE": "Teddybär", "fr-FR": "Ours en Peluche", "es-ES": "Osito", "nl-NL": "Teddybeer", "pl-PL": "Miś", "sv-SE": "Nallebjörn" },
        "Fun Park": { "it-IT": "Parco Divertimenti", "de-DE": "Freizeitpark", "fr-FR": "Parc d'Attractions", "es-ES": "Parque de Diversión", "nl-NL": "Pretpark", "pl-PL": "Park Rozrywki", "sv-SE": "Nöjespark" },
        Unicorn: { "it-IT": "Unicorno", "de-DE": "Einhorn", "fr-FR": "Licorne", "es-ES": "Unicornio", "nl-NL": "Eenhoorn", "pl-PL": "Jednorożec", "sv-SE": "Enhörning" },
        Dinosaur: { "it-IT": "Dinosauro", "de-DE": "Dinosaurier", "fr-FR": "Dinosaure", "es-ES": "Dinosaurio", "nl-NL": "Dinosaurus", "pl-PL": "Dinozaur", "sv-SE": "Dinosaurie" },
        Space: { "it-IT": "Spazio", "de-DE": "Weltraum", "fr-FR": "Espace", "es-ES": "Espacio", "nl-NL": "Ruimte", "pl-PL": "Kosmos", "sv-SE": "Rymden" },
        Candy: { "it-IT": "Caramelle", "de-DE": "Süßigkeiten", "fr-FR": "Bonbons", "es-ES": "Dulces", "nl-NL": "Snoep", "pl-PL": "Cukierki", "sv-SE": "Godis" },
        "Super Hero": { "it-IT": "Supereroe", "de-DE": "Superheld", "fr-FR": "Super Héros", "es-ES": "Superhéroe", "nl-NL": "Superheld", "pl-PL": "Superbohater", "sv-SE": "Superhjälte" }
      },
      products: {
        "Teddy Bear Tee": { "it-IT": "Tee Orsetto", "de-DE": "Teddybär Shirt", "fr-FR": "Tee Ours en Peluche", "es-ES": "Camiseta Osito", "nl-NL": "Teddybeer Shirt", "pl-PL": "Koszulka Miś", "sv-SE": "Nalle T-shirt" },
        "Fun Park Tee": { "it-IT": "Tee Parco Divertimenti", "de-DE": "Freizeitpark Shirt", "fr-FR": "Tee Parc d'Attractions", "es-ES": "Camiseta Parque", "nl-NL": "Pretpark Shirt", "pl-PL": "Koszulka Park Rozrywki", "sv-SE": "Nöjespark T-shirt" },
        "Unicorn Dream Tee": { "it-IT": "Tee Sogno Unicorno", "de-DE": "Einhorntraum Shirt", "fr-FR": "Tee Rêve Licorne", "es-ES": "Camiseta Sueño Unicornio", "nl-NL": "Eenhoorn Droom Shirt", "pl-PL": "Koszulka Jednorożec", "sv-SE": "Enhörning Dröm T-shirt" },
        "Dino Squad Tee": { "it-IT": "Tee Squadra Dino", "de-DE": "Dino Squad Shirt", "fr-FR": "Tee Équipe Dino", "es-ES": "Camiseta Dino Squad", "nl-NL": "Dino Squad Shirt", "pl-PL": "Koszulka Dino Squad", "sv-SE": "Dino Squad T-shirt" },
        "Space Rocket Tee": { "it-IT": "Tee Razzo Spaziale", "de-DE": "Weltraumrakete Shirt", "fr-FR": "Tee Fusée Spatiale", "es-ES": "Camiseta Cohete Espacial", "nl-NL": "Ruimteraket Shirt", "pl-PL": "Koszulka Rakieta Kosmiczna", "sv-SE": "Rymdraket T-shirt" },
        "Candy Pop Tee": { "it-IT": "Tee Candy Pop", "de-DE": "Candy Pop Shirt", "fr-FR": "Tee Candy Pop", "es-ES": "Camiseta Candy Pop", "nl-NL": "Candy Pop Shirt", "pl-PL": "Koszulka Candy Pop", "sv-SE": "Candy Pop T-shirt" }
      }
    },
    "home-living": {
      titles: {
        "en-GB": "GirffoN - Home & Living",
        "it-IT": "GirffoN - Casa & Living",
        "de-DE": "GirffoN - Wohnen",
        "fr-FR": "GirffoN - Maison & Vie",
        "es-ES": "GirffoN - Hogar y Vida",
        "nl-NL": "GirffoN - Wonen",
        "pl-PL": "GirffoN - Dom i Wnętrze",
        "sv-SE": "GirffoN - Hem & Liv"
      },
      sections: {
        Cushions: { "it-IT": "Cuscini", "de-DE": "Kissen", "fr-FR": "Coussins", "es-ES": "Cojines", "nl-NL": "Kussens", "pl-PL": "Poduszki", "sv-SE": "Kuddar" },
        "Ceramic Mugs": { "it-IT": "Tazze in Ceramica", "de-DE": "Keramiktassen", "fr-FR": "Mugs en Céramique", "es-ES": "Tazas de Cerámica", "nl-NL": "Keramische Mokken", "pl-PL": "Ceramiczne Kubki", "sv-SE": "Keramiska Muggar" },
        Coasters: { "it-IT": "Sottobicchieri", "de-DE": "Untersetzer", "fr-FR": "Sous-verres", "es-ES": "Posavasos", "nl-NL": "Onderzetters", "pl-PL": "Podkładki", "sv-SE": "Glasunderlägg" },
        "Wall Art": { "it-IT": "Arte da Parete", "de-DE": "Wandkunst", "fr-FR": "Art Mural", "es-ES": "Arte de Pared", "nl-NL": "Wandkunst", "pl-PL": "Sztuka Ścienna", "sv-SE": "Väggkonst" },
        Blankets: { "it-IT": "Coperte", "de-DE": "Decken", "fr-FR": "Couvertures", "es-ES": "Mantas", "nl-NL": "Dekens", "pl-PL": "Koce", "sv-SE": "Filtar" },
        Candles: { "it-IT": "Candele", "de-DE": "Kerzen", "fr-FR": "Bougies", "es-ES": "Velas", "nl-NL": "Kaarsen", "pl-PL": "Świece", "sv-SE": "Ljus" },
        Decor: { "it-IT": "Decor", "de-DE": "Dekor", "fr-FR": "Décor", "es-ES": "Decoración", "nl-NL": "Decor", "pl-PL": "Dekoracje", "sv-SE": "Dekor" }
      },
      products: {
        "Cushion Cover": { "it-IT": "Federa Cuscino", "de-DE": "Kissenbezug", "fr-FR": "Housse de Coussin", "es-ES": "Funda de Cojín", "nl-NL": "Kussenhoes", "pl-PL": "Poszewka na Poduszkę", "sv-SE": "Kuddfodral" },
        "Ceramic Mug": { "it-IT": "Tazza in Ceramica", "de-DE": "Keramiktasse", "fr-FR": "Mug en Céramique", "es-ES": "Taza de Cerámica", "nl-NL": "Keramische Mok", "pl-PL": "Kubek Ceramiczny", "sv-SE": "Keramisk Mugg" },
        "Coasters Set": { "it-IT": "Set Sottobicchieri", "de-DE": "Untersetzer Set", "fr-FR": "Set de Sous-verres", "es-ES": "Set de Posavasos", "nl-NL": "Onderzetter Set", "pl-PL": "Zestaw Podkładek", "sv-SE": "Glasunderlägg Set" },
        "Wall Art Poster": { "it-IT": "Poster da Parete", "de-DE": "Wandposter", "fr-FR": "Poster Mural", "es-ES": "Póster de Pared", "nl-NL": "Wandposter", "pl-PL": "Plakat Ścienny", "sv-SE": "Väggposter" },
        "Soft Blanket": { "it-IT": "Coperta Morbida", "de-DE": "Weiche Decke", "fr-FR": "Couverture Douce", "es-ES": "Manta Suave", "nl-NL": "Zachte Deken", "pl-PL": "Miękki Koc", "sv-SE": "Mjuk Filt" },
        "Decor Candle": { "it-IT": "Candela Decorativa", "de-DE": "Dekokerze", "fr-FR": "Bougie Décorative", "es-ES": "Vela Decorativa", "nl-NL": "Decor Kaars", "pl-PL": "Świeca Dekoracyjna", "sv-SE": "Dekorljus" }
      }
    },
    accessories: {
      titles: {
        "en-GB": "GirffoN - Accessories",
        "it-IT": "GirffoN - Accessori",
        "de-DE": "GirffoN - Accessoires",
        "fr-FR": "GirffoN - Accessoires",
        "es-ES": "GirffoN - Accesorios",
        "nl-NL": "GirffoN - Accessoires",
        "pl-PL": "GirffoN - Akcesoria",
        "sv-SE": "GirffoN - Accessoarer"
      },
      sections: {
        "Caps & Hats": { "it-IT": "Cappelli & Berretti", "de-DE": "Caps & Hüte", "fr-FR": "Casquettes & Chapeaux", "es-ES": "Gorras y Sombreros", "nl-NL": "Caps & Hoeden", "pl-PL": "Czapki i Kapelusze", "sv-SE": "Kepsar & Hattar" },
        "Tote Bags": { "it-IT": "Borse Tote", "de-DE": "Tote Bags", "fr-FR": "Sacs Tote", "es-ES": "Bolsas Tote", "nl-NL": "Tote Bags", "pl-PL": "Torby Tote", "sv-SE": "Tygkassar" },
        Bottles: { "it-IT": "Borracce", "de-DE": "Flaschen", "fr-FR": "Bouteilles", "es-ES": "Botellas", "nl-NL": "Flessen", "pl-PL": "Butelki", "sv-SE": "Flaskor" },
        "Phone Cases": { "it-IT": "Cover per Telefono", "de-DE": "Handyhüllen", "fr-FR": "Coques de Téléphone", "es-ES": "Fundas para Móvil", "nl-NL": "Telefoonhoesjes", "pl-PL": "Etui na Telefon", "sv-SE": "Mobilskal" },
        Socks: { "it-IT": "Calzini", "de-DE": "Socken", "fr-FR": "Chaussettes", "es-ES": "Calcetines", "nl-NL": "Sokken", "pl-PL": "Skarpetki", "sv-SE": "Strumpor" },
        Pins: { "it-IT": "Spille", "de-DE": "Pins", "fr-FR": "Pins", "es-ES": "Pins", "nl-NL": "Pins", "pl-PL": "Piny", "sv-SE": "Pins" },
        Scarves: { "it-IT": "Sciarpe", "de-DE": "Schals", "fr-FR": "Écharpes", "es-ES": "Bufandas", "nl-NL": "Sjaals", "pl-PL": "Szale", "sv-SE": "Halsdukar" }
      },
      products: {
        "Flexfit Cap": { "it-IT": "Cappellino Flexfit", "de-DE": "Flexfit Cap", "fr-FR": "Casquette Flexfit", "es-ES": "Gorra Flexfit", "nl-NL": "Flexfit Cap", "pl-PL": "Czapka Flexfit", "sv-SE": "Flexfit Keps" },
        "Classic Tote Bag": { "it-IT": "Borsa Tote Classica", "de-DE": "Klassische Tote Bag", "fr-FR": "Sac Tote Classique", "es-ES": "Bolsa Tote Clásica", "nl-NL": "Klassieke Tote Bag", "pl-PL": "Klasyczna Torba Tote", "sv-SE": "Klassisk Tygkasse" },
        "Bottle Design": { "it-IT": "Design Borraccia", "de-DE": "Flaschen-Design", "fr-FR": "Design Bouteille", "es-ES": "Diseño de Botella", "nl-NL": "Flesontwerp", "pl-PL": "Projekt Butelki", "sv-SE": "Flaskdesign" },
        "Phone Case": { "it-IT": "Cover Telefono", "de-DE": "Handyhülle", "fr-FR": "Coque Téléphone", "es-ES": "Funda de Teléfono", "nl-NL": "Telefoonhoesje", "pl-PL": "Etui na Telefon", "sv-SE": "Mobilskal" },
        "Street Socks": { "it-IT": "Calzini Street", "de-DE": "Street Socken", "fr-FR": "Chaussettes Street", "es-ES": "Calcetines Street", "nl-NL": "Street Sokken", "pl-PL": "Skarpetki Street", "sv-SE": "Street Strumpor" },
        "Pins & Patches": { "it-IT": "Spille & Patch", "de-DE": "Pins & Patches", "fr-FR": "Pins & Patchs", "es-ES": "Pins y Parches", "nl-NL": "Pins & Patches", "pl-PL": "Piny i Naszywki", "sv-SE": "Pins & Märken" }
      }
    }
  };

  function getLocaleCode() {
    const countryCode = localStorage.getItem(LOCALE_STORAGE_KEY) || DEFAULT_COUNTRY;
    return COUNTRY_TO_LOCALE[countryCode] || "en-GB";
  }

  function resolveLocaleCode() {
    const localeCode = getLocaleCode();
    return UI_TEXTS[localeCode] ? localeCode : (LOCALE_FALLBACK[localeCode] || "en-GB");
  }

  function getPageValue(map, localeCode, fallbackValue) {
    if (!map) return fallbackValue;
    return map[localeCode] || map[LOCALE_FALLBACK[localeCode]] || fallbackValue;
  }

  function formatPrice(value, localeCode) {
    const config = LOCALE_CONFIG[localeCode] || LOCALE_CONFIG["en-GB"];
    const converted = value * config.rateFromEUR;
    return new Intl.NumberFormat(localeCode, {
      style: "currency",
      currency: config.currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(converted);
  }

  function create(pageKey) {
    const pageData = PAGE_DATA[pageKey];
    if (!pageData) return null;

    function localeCode() {
      return resolveLocaleCode();
    }

    function uiTexts() {
      return UI_TEXTS[localeCode()] || UI_TEXTS["en-GB"];
    }

    function translateSection(name) {
      return getPageValue(pageData.sections[name], localeCode(), name);
    }

    function translateProduct(name) {
      return getPageValue(pageData.products[name], localeCode(), name);
    }

    function composeTitle(sectionName, productName) {
      return translateSection(sectionName) + " - " + translateProduct(productName);
    }

    function applyCardTexts(root) {
      const texts = uiTexts();
      root.querySelectorAll(".gx25-section").forEach(function (section) {
        const titleNode = section.querySelector(".gx25-men-title, .gx25-women-title, .gx25-kids-title, .gx25-home-title, .gx25-accessories-title");
        const sectionName = (section.dataset.sectionName || "").trim();
        if (titleNode && sectionName) {
          titleNode.textContent = translateSection(sectionName);
        }
      });

      root.querySelectorAll(".gx25-card").forEach(function (card) {
        const sectionName = card.dataset.sectionName || "";
        const baseTitle = card.dataset.baseTitle || "";
        const priceEur = Number(card.dataset.priceEur || 0);
        const badgeNode = card.querySelector(".gx25-badge");
        const titleNode = card.querySelector(".gx25-title");
        const priceNode = card.querySelector(".gx25-price");
        const buttonNode = card.querySelector(".gx25-enter");
        const favButton = card.querySelector(".gx25-fav");
        const imageNode = card.querySelector(".gx25-main-image");
        const prevButton = card.querySelector(".gx25-inner-prev");
        const nextButton = card.querySelector(".gx25-inner-next");
        const title = composeTitle(sectionName, baseTitle);

        if (badgeNode) badgeNode.textContent = texts.badge;
        if (titleNode) titleNode.textContent = title;
        if (priceNode) priceNode.textContent = formatPrice(priceEur, localeCode());
        if (buttonNode) buttonNode.textContent = texts.addToCart;
        if (favButton) favButton.setAttribute("aria-label", texts.addToCart);
        if (imageNode) imageNode.alt = title;
        if (prevButton) prevButton.setAttribute("aria-label", "Previous image");
        if (nextButton) nextButton.setAttribute("aria-label", "Next image");
      });
    }

    function applyBankModal(root) {
      const modal = root.getElementById("bankModal");
      if (!modal) return;

      const texts = uiTexts();
      const heading = modal.querySelector(".bank-modal-content h2");
      const labels = modal.querySelectorAll(".bank-form label");
      const inputs = modal.querySelectorAll(".bank-form input");
      const payButton = modal.querySelector(".bank-form button.checkout-btn");

      if (heading) heading.textContent = texts.securePayment;
      if (labels[0]) labels[0].childNodes[0].textContent = texts.cardholder + "\n          ";
      if (labels[1]) labels[1].childNodes[0].textContent = texts.cardNumber + "\n          ";
      if (labels[2]) labels[2].childNodes[0].textContent = texts.expiry + "\n            ";
      if (labels[3]) labels[3].childNodes[0].textContent = texts.cvc + "\n            ";
      if (inputs[0]) inputs[0].placeholder = texts.fullName;
      if (payButton) payButton.innerHTML = texts.payNow + ' <i class="fa-solid fa-lock"></i>';
    }

    function applyPage(root) {
      const locale = localeCode();
      document.title = getPageValue(pageData.titles, locale, pageData.titles["en-GB"]);
      applyCardTexts(root || document);
      applyBankModal(root || document);
    }

    function bindBankModal(root) {
      const form = (root || document).getElementById("bankForm");
      const modal = (root || document).getElementById("bankModal");
      if (!form || !modal) return;

      form.onsubmit = function (event) {
        event.preventDefault();
        modal.style.display = "none";
        window.location.href = "CartTest.html";
      };
    }

    function watch(onChange) {
      document.querySelectorAll(".gf-locale-card").forEach(function (card) {
        card.addEventListener("click", function () {
          window.setTimeout(onChange, 0);
        });
      });

      const observer = new MutationObserver(function () {
        onChange();
      });
      observer.observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
      return observer;
    }

    return {
      formatPrice: function (value) {
        return formatPrice(value, localeCode());
      },
      texts: uiTexts,
      composeTitle: composeTitle,
      translateSection: translateSection,
      applyPage: applyPage,
      bindBankModal: bindBankModal,
      watch: watch
    };
  }

  window.GFCategoryLocaleHelper = { create: create };
})();