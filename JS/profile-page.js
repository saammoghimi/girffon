document.addEventListener("DOMContentLoaded", function () {
  const isServerBackedProfilePage = Boolean(window.GIRFFON_PROFILE_PAGE_DATA);
  const CATALOG_SUBSCRIPTION_URL = "/GirffoN/backend/profile/catalog-subscription.php";
  const center = document.querySelector(".gf-account-center");
  if (!center) return;
  const bridge = window.GIRFFON_BRIDGE || null;
  const authApi = window.GIRFFON_AUTH || null;
  const profileHeaderName = center.querySelector(".gf-account-profile-meta h2");
  const profileHeaderEmail = center.querySelector('.gf-account-profile-link[href^="mailto:"]');
  const profileHeaderPhone = center.querySelector('.gf-account-profile-link[href^="tel:"]');
  const profileAvatarWrap = center.querySelector(".gf-account-avatar-wrap");
  const primaryAddressName = center.querySelector("[data-gf-primary-address-name]");
  const primaryAddressPhone = center.querySelector("[data-gf-primary-address-phone]");
  const primaryAddressCountry = center.querySelector("[data-gf-primary-address-country]");
  const primaryAddressCity = center.querySelector("[data-gf-primary-address-city]");
  const primaryAddressPostal = center.querySelector("[data-gf-primary-address-postal]");
  const primaryAddressFull = center.querySelector("[data-gf-primary-address-full]");
  const addressBook = center.querySelector("[data-gf-addresses]");
  const addressCount = center.querySelector("[data-gf-address-count]");
  const addAddressButton = center.querySelector("[data-gf-add-address]");
  const primaryCardholder = center.querySelector("[data-gf-primary-cardholder]");
  const paymentGrid = center.querySelector("[data-gf-payment-grid]");
  const paymentCount = center.querySelector("[data-gf-payment-count]");
  const addPaymentButton = center.querySelector("[data-gf-add-payment]");
  const securityLocation = center.querySelector("[data-gf-security-location]");
  const currentSession = center.querySelector("[data-gf-current-session]");

  const STORAGE_KEY = "gf-locale-country";
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

  const PROFILE_LOCALE_FALLBACK = {
    "en-US": "en-GB",
    "en-CA": "en-GB",
    "de-CH": "de-DE"
  };

  const PROFILE_TEXT_MAPS = {
    "it-IT": {
      "GirffoN Account Center": "Centro Account GirffoN",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Gestisci profilo, ordini, preferenze di stile e dettagli salvati in un unico spazio premium.",
      "This account page is designed like a luxury fashion service desk: clean, responsive, and built for personal details, profile photo updates, order visibility, addresses, payment tools, and communication preferences.": "Questa pagina account e progettata come un desk premium fashion: pulita, reattiva e pronta per dati personali, foto profilo, ordini, indirizzi, pagamenti e preferenze di comunicazione.",
      "Edit Profile": "Modifica Profilo",
      "View Orders": "Vedi Ordini",
      "Saved custom designs ready to reorder": "Design personalizzati salvati pronti al riordino",
      "Orders shipped in the last 90 days": "Ordini spediti negli ultimi 90 giorni",
      "Loyalty tier with priority support access": "Livello fedelta con supporto prioritario",
      "Profile Header": "Intestazione Profilo",
      "Verified Account": "Account Verificato",
      "Gold Loyalty": "Fedelta Gold",
      "Member since January 2025": "Membro da gennaio 2025",
      "Default market": "Mercato predefinito",
      "Preferred line": "Linea preferita",
      "Next delivery": "Prossima consegna",
      "Support tier": "Livello supporto",
      "Luxury Custom Apparel": "Abbigliamento Custom Premium",
      "Priority Concierge": "Concierge Prioritario",
      "Account Navigation": "Navigazione Account",
      "Profile Details": "Dettagli Profilo",
      "Profile Photo": "Foto Profilo",
      "Recent Orders": "Ordini Recenti",
      "Address Book": "Rubrica Indirizzi",
      "Payments": "Pagamenti",
      "Preferences": "Preferenze",
      "Security": "Sicurezza",
      "My Designs": "I Miei Design",
      "Saved Items": "Elementi Salvati",
      "Account Tools": "Strumenti Account",
      "Profile Completion": "Completamento Profilo",
      "Add a second phone number and backup shipping address to complete your account.": "Aggiungi un secondo numero di telefono e un indirizzo di spedizione di riserva per completare il tuo account.",
      "Luxury service, but practical tools.": "Servizio premium, ma strumenti pratici.",
      "Keep identity, billing, order history, and communication settings together in a single responsive dashboard.": "Mantieni identita, fatturazione, cronologia ordini e impostazioni comunicazione in un'unica dashboard reattiva.",
      "Save Changes": "Salva Modifiche",
      "Reset Draft": "Reimposta Bozza",
      "Member Privileges": "Privilegi Membro",
      "Exclusive Member Benefits": "Vantaggi Esclusivi Membri",
      "Premium access designed for GirffoN members who want earlier drops, more curated updates, and richer rewards.": "Accesso premium pensato per i membri GirffoN che vogliono uscite anticipate, aggiornamenti curati e premi piu ricchi.",
      "Early Access to New Collections": "Accesso Anticipato alle Nuove Collezioni",
      "Preview and shop selected releases before the public launch window opens.": "Scopri e acquista selezioni esclusive prima dell'apertura pubblica.",
      "Private Catalog Emails": "Email Catalogo Private",
      "Receive curated catalog editions, premium edits, and insider collection highlights by email.": "Ricevi via email edizioni catalogo curate, selezioni premium e highlight esclusivi.",
      "40% Birthday Discount": "40% di Sconto Compleanno",
      "Celebrate your special day with a generous member discount reserved for your birthday.": "Festeggia il tuo giorno speciale con uno sconto dedicato ai membri per il compleanno.",
      "Personal Information": "Informazioni Personali",
      "Update your core account identity, contact information, address details, and localization preferences.": "Aggiorna identita account, contatti, indirizzo e preferenze di localizzazione.",
      "Validation Ready": "Pronto per Validazione",
      "First Name": "Nome",
      "Last Name": "Cognome",
      "Email Address": "Indirizzo Email",
      "Phone Number": "Numero di Telefono",
      "Date of Birth": "Data di Nascita",
      "Gender": "Genere",
      "Country": "Paese",
      "City": "Citta",
      "Postal Code": "CAP",
      "Full Address": "Indirizzo Completo",
      "Preferred Language": "Lingua Preferita",
      "All fields are structured for validation and ready for future backend or local storage integration.": "Tutti i campi sono pronti per validazione e futura integrazione backend o local storage.",
      "Choose a profile image": "Scegli un'immagine profilo",
      "JPG, PNG, or WEBP.": "JPG, PNG o WEBP.",
      "Upload Photo": "Carica Foto",
      "Apply Photo": "Applica Foto",
      "Account Summary": "Riepilogo Account",
      "Track the essentials of your customer profile at a glance.": "Controlla a colpo d'occhio gli elementi essenziali del tuo profilo cliente.",
      "Active payment methods": "Metodi di pagamento attivi",
      "Saved delivery addresses": "Indirizzi di consegna salvati",
      "Wishlist pieces waiting for restock": "Articoli wishlist in attesa di riassortimento",
      "Order History Preview": "Anteprima Cronologia Ordini",
      "A premium snapshot of your latest GirffoN orders, with quick status visibility and detail access.": "Una panoramica premium dei tuoi ultimi ordini GirffoN, con stato rapido e accesso ai dettagli.",
      "Download Invoices": "Scarica Fatture",
      "Track Package": "Traccia Pacco",
      "Recent Order": "Ordine Recente",
      "In Transit": "In Transito",
      "Order Number": "Numero Ordine",
      "Date": "Data",
      "Total Amount": "Importo Totale",
      "Status": "Stato",
      "Courier confirmed for delivery": "Corriere confermato per la consegna",
      "View Details": "Vedi Dettagli",
      "Delivered": "Consegnato",
      "Delivered successfully": "Consegnato con successo",
      "Shipping Addresses": "Indirizzi di Spedizione",
      "Manage your delivery destinations with a clean premium layout built for fast checkout and reliable shipping.": "Gestisci le destinazioni con un layout premium pulito pensato per checkout rapido e spedizioni affidabili.",
      "Add New Address": "Aggiungi Nuovo Indirizzo",
      "Primary Shipping Address": "Indirizzo di Spedizione Principale",
      "Default": "Predefinito",
      "Edit": "Modifica",
      "Duplicate": "Duplica",
      "Secondary Shipping Address": "Indirizzo di Spedizione Secondario",
      "Set Default": "Imposta Predefinito",
      "Payment Methods": "Metodi di Pagamento",
      "Manage saved cards and billing methods with a secure, premium e-commerce payment experience.": "Gestisci carte salvate e metodi di fatturazione con un'esperienza pagamento premium e sicura.",
      "Add New Payment": "Aggiungi Nuovo Pagamento",
      "Secure": "Sicuro",
      "Primary": "Principale",
      "Cardholder": "Intestatario",
      "Expires": "Scadenza",
      "Billing Method": "Metodo di Fatturazione",
      "Personal Card": "Carta Personale",
      "Edit Card": "Modifica Carta",
      "Set Billing": "Imposta Fatturazione",
      "Verified": "Verificato",
      "Business": "Business",
      "Business Billing": "Fatturazione Business",
      "Use for Orders": "Usa per Ordini",
      "Communication Preferences": "Preferenze di Comunicazione",
      "Choose which premium updates, catalog drops, and customer messages GirffoN should send to you.": "Scegli quali aggiornamenti premium, uscite catalogo e messaggi cliente GirffoN deve inviarti.",
      "Luxury Notifications": "Notifiche Premium",
      "Fine-tune every message channel with a polished preference center built for a luxury fashion experience.": "Regola ogni canale con un centro preferenze raffinato pensato per un'esperienza fashion premium.",
      "Birthday Gift": "Regalo di Compleanno",
      "Add your birthday and receive a 40% discount on your special day.": "Aggiungi la tua data di nascita e ricevi uno sconto del 40% nel tuo giorno speciale.",
      "Birthday Date": "Data Compleanno",
      "Save Birthday": "Salva Compleanno",
      "New Collection": "Nuova Collezione",
      "Collection and Catalog Subscription": "Iscrizione Collezioni e Catalogo",
      "Receive our latest catalog, new arrivals, and exclusive offers by email.": "Ricevi via email il nostro ultimo catalogo, nuove uscite e offerte esclusive.",
      "Subscribe": "Iscriviti",
      "Preview Tool": "Strumento Anteprima",
      "Test Email": "Email di Test",
      "Preview how GirffoN email messages can appear for the current account before delivery tools are added.": "Visualizza come possono apparire i messaggi email GirffoN per l'account corrente prima che vengano aggiunti gli strumenti di invio.",
      "Send Test Email": "Invia Email di Test",
      "Promotional Emails": "Email Promozionali",
      "Exclusive campaigns, featured collections, and premium studio announcements.": "Campagne esclusive, collezioni in evidenza e annunci premium dello studio.",
      "Catalog Emails": "Email Catalogo",
      "Seasonal lookbooks, new catalog releases, and curated product highlights.": "Lookbook stagionali, nuove uscite catalogo e prodotti selezionati.",
      "Birthday Discount Emails": "Email Sconto Compleanno",
      "Receive annual birthday perks, private codes, and celebratory member offers.": "Ricevi vantaggi annuali compleanno, codici privati e offerte dedicate.",
      "Order Updates": "Aggiornamenti Ordine",
      "Important order confirmations, payment checks, shipping milestones, and delivery events.": "Conferme ordine, controlli pagamento, tappe spedizione ed eventi di consegna.",
      "Protect your GirffoN account with trusted controls, clear session visibility, and careful account actions.": "Proteggi il tuo account GirffoN con controlli affidabili, visibilita sessioni e azioni sicure.",
      "Account Protection": "Protezione Account",
      "Change Password": "Cambia Password",
      "Refresh your password regularly to keep account access secure.": "Aggiorna regolarmente la password per mantenere sicuro l'accesso.",
      "Two-Factor Authentication": "Autenticazione a Due Fattori",
      "Add a second verification step for sign-ins and sensitive account changes.": "Aggiungi un secondo passaggio di verifica per accessi e modifiche sensibili.",
      "Login Activity": "Attivita di Accesso",
      "Monitored": "Monitorato",
      "Active devices": "Dispositivi attivi",
      "Latest verified sign-in": "Ultimo accesso verificato",
      "Current secure location": "Posizione sicura attuale",
      "Recent Sessions": "Sessioni Recenti",
      "Live View": "Vista Live",
      "Current": "Attuale",
      "Recent": "Recente",
      "Delete Account": "Elimina Account",
      "This removes your saved profile data, design drafts, and account history from this experience.": "Questo rimuove dati profilo salvati, bozze design e cronologia account da questa esperienza.",
      "Review your saved custom t-shirt concepts, reopen drafts, or duplicate ideas for your next order.": "Rivedi i concept t-shirt salvati, riapri bozze o duplica idee per il prossimo ordine.",
      "Saved Studio": "Studio Salvato",
      "Delete": "Elimina",
      "Wishlist and Saved Items": "Wishlist ed Elementi Salvati",
      "Keep your favorite products close with a luxury e-commerce view of saved pieces, prices, and stock availability.": "Tieni vicini i tuoi prodotti preferiti con una vista premium di articoli salvati, prezzi e disponibilita.",
      "Curated Favorites": "Preferiti Curati",
      "In Stock": "Disponibile",
      "Low Stock": "Scorte Basse",
      "Add to Cart": "Aggiungi al Carrello",
      "Remove": "Rimuovi",
      "Quick access to the essential GirffoN account areas in a premium, easy-to-scan dashboard.": "Accesso rapido alle aree essenziali dell'account GirffoN in una dashboard premium e leggibile.",
      "Quick Actions": "Azioni Rapide",
      "Manage Account": "Gestisci Account",
      "Review login details, profile settings, and personal account controls.": "Controlla dettagli accesso, impostazioni profilo e controlli personali.",
      "Open Tool": "Apri Strumento",
      "Order History": "Cronologia Ordini",
      "Check previous purchases, shipment records, and reorder-ready items.": "Controlla acquisti precedenti, spedizioni e articoli pronti al riordino.",
      "Update primary destinations, delivery notes, and alternate addresses.": "Aggiorna destinazioni principali, note consegna e indirizzi alternativi.",
      "Wishlist": "Wishlist",
      "Track your favorite designs and monitor pieces waiting for restock.": "Segui i tuoi design preferiti e monitora gli articoli in attesa di riassortimento.",
      "Identity verified": "Identita verificata",
      "Primary payment method updated": "Metodo di pagamento principale aggiornato",
      "Draft changes are ready.": "Le modifiche in bozza sono pronte.",
      "Review your profile, upload a photo, then save the current account setup.": "Controlla il profilo, carica una foto, poi salva la configurazione attuale dell'account.",
      "Discard": "Annulla",
      "Save Account": "Salva Account"
    },
    "de-DE": {
      "GirffoN Account Center": "GirffoN Kontozentrum",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Verwalten Sie Profil, Bestellungen, Stilvorlieben und gespeicherte Daten in einem Premium-Bereich.",
      "This account page is designed like a luxury fashion service desk: clean, responsive, and built for personal details, profile photo updates, order visibility, addresses, payment tools, and communication preferences.": "Diese Kontoseite ist wie ein Premium-Fashion-Service gestaltet: klar, responsiv und fur personliche Daten, Profilfoto, Bestellungen, Adressen, Zahlungen und Kommunikation gebaut.",
      "Edit Profile": "Profil Bearbeiten",
      "View Orders": "Bestellungen Anzeigen",
      "Account Navigation": "Kontonavigation",
      "Profile Details": "Profildetails",
      "Profile Photo": "Profilfoto",
      "Recent Orders": "Letzte Bestellungen",
      "Address Book": "Adressbuch",
      "Payments": "Zahlungen",
      "Preferences": "Einstellungen",
      "Security": "Sicherheit",
      "My Designs": "Meine Designs",
      "Saved Items": "Gespeicherte Artikel",
      "Account Tools": "Kontowerkzeuge",
      "Profile Completion": "Profilfortschritt",
      "Save Changes": "Anderungen Speichern",
      "Reset Draft": "Entwurf Zurucksetzen",
      "Exclusive Member Benefits": "Exklusive Mitgliedervorteile",
      "Personal Information": "Personliche Daten",
      "Validation Ready": "Validierungsbereit",
      "First Name": "Vorname",
      "Last Name": "Nachname",
      "Email Address": "E-Mail-Adresse",
      "Phone Number": "Telefonnummer",
      "Date of Birth": "Geburtsdatum",
      "Gender": "Geschlecht",
      "Country": "Land",
      "City": "Stadt",
      "Postal Code": "Postleitzahl",
      "Full Address": "Vollstandige Adresse",
      "Preferred Language": "Bevorzugte Sprache",
      "Choose a profile image": "Profilbild auswahlen",
      "Upload Photo": "Foto Hochladen",
      "Apply Photo": "Foto Anwenden",
      "Account Summary": "Kontoubersicht",
      "Order History Preview": "Bestellverlauf Vorschau",
      "Download Invoices": "Rechnungen Herunterladen",
      "Track Package": "Paket Verfolgen",
      "Recent Order": "Letzte Bestellung",
      "In Transit": "Unterwegs",
      "Order Number": "Bestellnummer",
      "Date": "Datum",
      "Total Amount": "Gesamtbetrag",
      "Status": "Status",
      "View Details": "Details Anzeigen",
      "Delivered": "Geliefert",
      "Shipping Addresses": "Lieferadressen",
      "Add New Address": "Neue Adresse Hinzufugen",
      "Default": "Standard",
      "Edit": "Bearbeiten",
      "Duplicate": "Duplizieren",
      "Set Default": "Als Standard Setzen",
      "Payment Methods": "Zahlungsmethoden",
      "Add New Payment": "Neue Zahlung Hinzufugen",
      "Secure": "Sicher",
      "Primary": "Primar",
      "Cardholder": "Karteninhaber",
      "Expires": "Gultig bis",
      "Billing Method": "Abrechnungsmethode",
      "Edit Card": "Karte Bearbeiten",
      "Set Billing": "Abrechnung Festlegen",
      "Verified": "Verifiziert",
      "Use for Orders": "Fur Bestellungen Nutzen",
      "Communication Preferences": "Kommunikationseinstellungen",
      "Luxury Notifications": "Premium-Benachrichtigungen",
      "Birthday Gift": "Geburtstagsgeschenk",
      "Birthday Date": "Geburtstagsdatum",
      "Save Birthday": "Geburtstag Speichern",
      "New Collection": "Neue Kollektion",
      "Collection and Catalog Subscription": "Kollektion und Katalog Abo",
      "Subscribe": "Abonnieren",
      "Preview Tool": "Vorschauwerkzeug",
      "Test Email": "Test-E-Mail",
      "Send Test Email": "Test-E-Mail Senden",
      "Promotional Emails": "Werbe-E-Mails",
      "Catalog Emails": "Katalog-E-Mails",
      "Birthday Discount Emails": "Geburtstagsrabatt-E-Mails",
      "Order Updates": "Bestellupdates",
      "Account Protection": "Kontoschutz",
      "Change Password": "Passwort Andern",
      "Two-Factor Authentication": "Zwei-Faktor-Authentifizierung",
      "Login Activity": "Anmeldeaktivitat",
      "Monitored": "Uberwacht",
      "Active devices": "Aktive Gerate",
      "Latest verified sign-in": "Letzte verifizierte Anmeldung",
      "Current secure location": "Aktueller sicherer Standort",
      "Recent Sessions": "Letzte Sitzungen",
      "Live View": "Live-Ansicht",
      "Current": "Aktuell",
      "Recent": "Neu",
      "Delete Account": "Konto Loschen",
      "Saved Studio": "Gespeichertes Studio",
      "Delete": "Loschen",
      "Wishlist and Saved Items": "Wunschliste und Gespeicherte Artikel",
      "Curated Favorites": "Kurierte Favoriten",
      "In Stock": "Auf Lager",
      "Low Stock": "Wenig Lagerbestand",
      "Add to Cart": "In den Warenkorb",
      "Remove": "Entfernen",
      "Quick Actions": "Schnellaktionen",
      "Manage Account": "Konto Verwalten",
      "Open Tool": "Werkzeug Offnen",
      "Order History": "Bestellverlauf",
      "Wishlist": "Wunschliste",
      "Identity verified": "Identitat verifiziert",
      "Primary payment method updated": "Primare Zahlungsmethode aktualisiert",
      "Draft changes are ready.": "Entwurfsanderungen sind bereit.",
      "Discard": "Verwerfen",
      "Save Account": "Konto Speichern"
    },
    "fr-FR": {
      "GirffoN Account Center": "Centre de Compte GirffoN",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Gerez votre profil, vos commandes, vos preferences de style et vos details sauvegardes dans un espace premium.",
      "Edit Profile": "Modifier le Profil",
      "View Orders": "Voir les Commandes",
      "Account Navigation": "Navigation du Compte",
      "Profile Details": "Details du Profil",
      "Profile Photo": "Photo du Profil",
      "Recent Orders": "Commandes Recentes",
      "Address Book": "Carnet d'Adresses",
      "Payments": "Paiements",
      "Preferences": "Preferences",
      "Security": "Securite",
      "My Designs": "Mes Designs",
      "Saved Items": "Articles Sauvegardes",
      "Account Tools": "Outils du Compte",
      "Profile Completion": "Profil Complete",
      "Save Changes": "Enregistrer les Modifications",
      "Reset Draft": "Reinitialiser le Brouillon",
      "Exclusive Member Benefits": "Avantages Exclusifs Membre",
      "Personal Information": "Informations Personnelles",
      "Validation Ready": "Pret pour Validation",
      "First Name": "Prenom",
      "Last Name": "Nom",
      "Email Address": "Adresse Email",
      "Phone Number": "Numero de Telephone",
      "Date of Birth": "Date de Naissance",
      "Gender": "Genre",
      "Country": "Pays",
      "City": "Ville",
      "Postal Code": "Code Postal",
      "Full Address": "Adresse Complete",
      "Preferred Language": "Langue Preferee",
      "Choose a profile image": "Choisir une image de profil",
      "Upload Photo": "Televerser la Photo",
      "Apply Photo": "Appliquer la Photo",
      "Account Summary": "Resume du Compte",
      "Order History Preview": "Apercu de l'Historique des Commandes",
      "Download Invoices": "Telecharger les Factures",
      "Track Package": "Suivre le Colis",
      "Recent Order": "Commande Recente",
      "In Transit": "En Transit",
      "Order Number": "Numero de Commande",
      "Date": "Date",
      "Total Amount": "Montant Total",
      "Status": "Statut",
      "View Details": "Voir les Details",
      "Delivered": "Livre",
      "Shipping Addresses": "Adresses de Livraison",
      "Add New Address": "Ajouter une Nouvelle Adresse",
      "Default": "Par Defaut",
      "Edit": "Modifier",
      "Duplicate": "Dupliquer",
      "Set Default": "Definir par Defaut",
      "Payment Methods": "Moyens de Paiement",
      "Add New Payment": "Ajouter un Nouveau Paiement",
      "Secure": "Securise",
      "Primary": "Principal",
      "Cardholder": "Titulaire",
      "Expires": "Expire",
      "Billing Method": "Methode de Facturation",
      "Edit Card": "Modifier la Carte",
      "Set Billing": "Definir la Facturation",
      "Verified": "Verifie",
      "Use for Orders": "Utiliser pour les Commandes",
      "Communication Preferences": "Preferences de Communication",
      "Luxury Notifications": "Notifications Premium",
      "Birthday Gift": "Cadeau d'Anniversaire",
      "Birthday Date": "Date d'Anniversaire",
      "Save Birthday": "Enregistrer l'Anniversaire",
      "New Collection": "Nouvelle Collection",
      "Collection and Catalog Subscription": "Abonnement Collection et Catalogue",
      "Subscribe": "S'abonner",
      "Preview Tool": "Outil d'Apercu",
      "Test Email": "Email de Test",
      "Send Test Email": "Envoyer l'Email de Test",
      "Promotional Emails": "Emails Promotionnels",
      "Catalog Emails": "Emails Catalogue",
      "Birthday Discount Emails": "Emails Remise Anniversaire",
      "Order Updates": "Mises a Jour Commande",
      "Account Protection": "Protection du Compte",
      "Change Password": "Changer le Mot de Passe",
      "Two-Factor Authentication": "Authentification a Deux Facteurs",
      "Login Activity": "Activite de Connexion",
      "Monitored": "Surveille",
      "Active devices": "Appareils actifs",
      "Latest verified sign-in": "Derniere connexion verifiee",
      "Current secure location": "Localisation securisee actuelle",
      "Recent Sessions": "Sessions Recentes",
      "Live View": "Vue en Direct",
      "Current": "Actuel",
      "Recent": "Recent",
      "Delete Account": "Supprimer le Compte",
      "Saved Studio": "Studio Sauvegarde",
      "Delete": "Supprimer",
      "Wishlist and Saved Items": "Liste d'Envies et Articles Sauvegardes",
      "Curated Favorites": "Favoris Selectionnes",
      "In Stock": "En Stock",
      "Low Stock": "Stock Faible",
      "Add to Cart": "Ajouter au Panier",
      "Remove": "Retirer",
      "Quick Actions": "Actions Rapides",
      "Manage Account": "Gerer le Compte",
      "Open Tool": "Ouvrir l'Outil",
      "Order History": "Historique des Commandes",
      "Wishlist": "Liste d'Envies",
      "Identity verified": "Identite verifiee",
      "Primary payment method updated": "Methode de paiement principale mise a jour",
      "Draft changes are ready.": "Les modifications du brouillon sont pretes.",
      "Discard": "Ignorer",
      "Save Account": "Enregistrer le Compte"
    },
    "es-ES": {
      "GirffoN Account Center": "Centro de Cuenta GirffoN",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Gestiona tu perfil, pedidos, preferencias de estilo y datos guardados en un espacio premium.",
      "Edit Profile": "Editar Perfil",
      "View Orders": "Ver Pedidos",
      "Account Navigation": "Navegacion de la Cuenta",
      "Profile Details": "Detalles del Perfil",
      "Profile Photo": "Foto del Perfil",
      "Recent Orders": "Pedidos Recientes",
      "Address Book": "Libreta de Direcciones",
      "Payments": "Pagos",
      "Preferences": "Preferencias",
      "Security": "Seguridad",
      "My Designs": "Mis Disenos",
      "Saved Items": "Elementos Guardados",
      "Account Tools": "Herramientas de la Cuenta",
      "Profile Completion": "Perfil Completado",
      "Save Changes": "Guardar Cambios",
      "Reset Draft": "Restablecer Borrador",
      "Exclusive Member Benefits": "Beneficios Exclusivos para Miembros",
      "Personal Information": "Informacion Personal",
      "Validation Ready": "Listo para Validacion",
      "First Name": "Nombre",
      "Last Name": "Apellido",
      "Email Address": "Correo Electronico",
      "Phone Number": "Numero de Telefono",
      "Date of Birth": "Fecha de Nacimiento",
      "Gender": "Genero",
      "Country": "Pais",
      "City": "Ciudad",
      "Postal Code": "Codigo Postal",
      "Full Address": "Direccion Completa",
      "Preferred Language": "Idioma Preferido",
      "Choose a profile image": "Elige una imagen de perfil",
      "Upload Photo": "Subir Foto",
      "Apply Photo": "Aplicar Foto",
      "Account Summary": "Resumen de la Cuenta",
      "Order History Preview": "Vista Previa del Historial de Pedidos",
      "Download Invoices": "Descargar Facturas",
      "Track Package": "Rastrear Paquete",
      "Recent Order": "Pedido Reciente",
      "In Transit": "En Transito",
      "Order Number": "Numero de Pedido",
      "Date": "Fecha",
      "Total Amount": "Importe Total",
      "Status": "Estado",
      "View Details": "Ver Detalles",
      "Delivered": "Entregado",
      "Shipping Addresses": "Direcciones de Envio",
      "Add New Address": "Agregar Nueva Direccion",
      "Default": "Predeterminado",
      "Edit": "Editar",
      "Duplicate": "Duplicar",
      "Set Default": "Definir como Predeterminado",
      "Payment Methods": "Metodos de Pago",
      "Add New Payment": "Agregar Nuevo Pago",
      "Secure": "Seguro",
      "Primary": "Principal",
      "Cardholder": "Titular",
      "Expires": "Vence",
      "Billing Method": "Metodo de Facturacion",
      "Edit Card": "Editar Tarjeta",
      "Set Billing": "Definir Facturacion",
      "Verified": "Verificado",
      "Use for Orders": "Usar para Pedidos",
      "Communication Preferences": "Preferencias de Comunicacion",
      "Luxury Notifications": "Notificaciones Premium",
      "Birthday Gift": "Regalo de Cumpleanos",
      "Birthday Date": "Fecha de Cumpleanos",
      "Save Birthday": "Guardar Cumpleanos",
      "New Collection": "Nueva Coleccion",
      "Collection and Catalog Subscription": "Suscripcion a Colecciones y Catalogo",
      "Subscribe": "Suscribirse",
      "Preview Tool": "Herramienta de Vista Previa",
      "Test Email": "Correo de Prueba",
      "Send Test Email": "Enviar Correo de Prueba",
      "Promotional Emails": "Correos Promocionales",
      "Catalog Emails": "Correos de Catalogo",
      "Birthday Discount Emails": "Correos de Descuento de Cumpleanos",
      "Order Updates": "Actualizaciones del Pedido",
      "Account Protection": "Proteccion de la Cuenta",
      "Change Password": "Cambiar Contrasena",
      "Two-Factor Authentication": "Autenticacion de Dos Factores",
      "Login Activity": "Actividad de Inicio de Sesion",
      "Monitored": "Supervisado",
      "Active devices": "Dispositivos activos",
      "Latest verified sign-in": "Ultimo acceso verificado",
      "Current secure location": "Ubicacion segura actual",
      "Recent Sessions": "Sesiones Recientes",
      "Live View": "Vista en Vivo",
      "Current": "Actual",
      "Recent": "Reciente",
      "Delete Account": "Eliminar Cuenta",
      "Saved Studio": "Estudio Guardado",
      "Delete": "Eliminar",
      "Wishlist and Saved Items": "Wishlist y Elementos Guardados",
      "Curated Favorites": "Favoritos Seleccionados",
      "In Stock": "En Stock",
      "Low Stock": "Pocas Unidades",
      "Add to Cart": "Agregar al Carrito",
      "Remove": "Quitar",
      "Quick Actions": "Acciones Rapidas",
      "Manage Account": "Gestionar Cuenta",
      "Open Tool": "Abrir Herramienta",
      "Order History": "Historial de Pedidos",
      "Wishlist": "Wishlist",
      "Identity verified": "Identidad verificada",
      "Primary payment method updated": "Metodo de pago principal actualizado",
      "Draft changes are ready.": "Los cambios del borrador estan listos.",
      "Discard": "Descartar",
      "Save Account": "Guardar Cuenta"
    },
    "nl-NL": {
      "GirffoN Account Center": "GirffoN Accountcentrum",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Beheer je profiel, bestellingen, stijlvoorkeuren en opgeslagen gegevens in een premium ruimte.",
      "Edit Profile": "Profiel Bewerken",
      "View Orders": "Bestellingen Bekijken",
      "Account Navigation": "Accountnavigatie",
      "Profile Details": "Profielgegevens",
      "Profile Photo": "Profielfoto",
      "Recent Orders": "Recente Bestellingen",
      "Address Book": "Adresboek",
      "Payments": "Betalingen",
      "Preferences": "Voorkeuren",
      "Security": "Beveiliging",
      "My Designs": "Mijn Ontwerpen",
      "Saved Items": "Opgeslagen Items",
      "Account Tools": "Accounttools",
      "Profile Completion": "Profielvoltooiing",
      "Save Changes": "Wijzigingen Opslaan",
      "Reset Draft": "Concept Herstellen",
      "Exclusive Member Benefits": "Exclusieve Ledenvoordelen",
      "Personal Information": "Persoonlijke Informatie",
      "Validation Ready": "Klaar voor Validatie",
      "First Name": "Voornaam",
      "Last Name": "Achternaam",
      "Email Address": "E-mailadres",
      "Phone Number": "Telefoonnummer",
      "Date of Birth": "Geboortedatum",
      "Gender": "Geslacht",
      "Country": "Land",
      "City": "Stad",
      "Postal Code": "Postcode",
      "Full Address": "Volledig Adres",
      "Preferred Language": "Voorkeurstaal",
      "Choose a profile image": "Kies een profielfoto",
      "Upload Photo": "Foto Uploaden",
      "Apply Photo": "Foto Toepassen",
      "Account Summary": "Accountoverzicht",
      "Order History Preview": "Voorbeeld Bestelgeschiedenis",
      "Download Invoices": "Facturen Downloaden",
      "Track Package": "Pakket Volgen",
      "Recent Order": "Recente Bestelling",
      "In Transit": "Onderweg",
      "Order Number": "Bestelnummer",
      "Date": "Datum",
      "Total Amount": "Totaalbedrag",
      "Status": "Status",
      "View Details": "Details Bekijken",
      "Delivered": "Afgeleverd",
      "Shipping Addresses": "Verzendadressen",
      "Add New Address": "Nieuw Adres Toevoegen",
      "Default": "Standaard",
      "Edit": "Bewerken",
      "Duplicate": "Dupliceren",
      "Set Default": "Standaard Maken",
      "Payment Methods": "Betaalmethoden",
      "Add New Payment": "Nieuwe Betaling Toevoegen",
      "Secure": "Beveiligd",
      "Primary": "Primair",
      "Cardholder": "Kaarthouder",
      "Expires": "Verloopt",
      "Billing Method": "Factureringsmethode",
      "Edit Card": "Kaart Bewerken",
      "Set Billing": "Facturering Instellen",
      "Verified": "Geverifieerd",
      "Use for Orders": "Voor Bestellingen Gebruiken",
      "Communication Preferences": "Communicatievoorkeuren",
      "Luxury Notifications": "Premium Meldingen",
      "Birthday Gift": "Verjaardagscadeau",
      "Birthday Date": "Verjaardagsdatum",
      "Save Birthday": "Verjaardag Opslaan",
      "New Collection": "Nieuwe Collectie",
      "Collection and Catalog Subscription": "Collectie- en Catalogusabonnement",
      "Subscribe": "Abonneren",
      "Preview Tool": "Preview Tool",
      "Test Email": "Testmail",
      "Send Test Email": "Testmail Verzenden",
      "Promotional Emails": "Promotionele Emails",
      "Catalog Emails": "Catalogus Emails",
      "Birthday Discount Emails": "Verjaardagskorting Emails",
      "Order Updates": "Bestelupdates",
      "Account Protection": "Accountbescherming",
      "Change Password": "Wachtwoord Wijzigen",
      "Two-Factor Authentication": "Tweefactorauthenticatie",
      "Login Activity": "Inlogactiviteit",
      "Monitored": "Bewaakt",
      "Active devices": "Actieve apparaten",
      "Latest verified sign-in": "Laatste geverifieerde login",
      "Current secure location": "Huidige veilige locatie",
      "Recent Sessions": "Recente Sessies",
      "Live View": "Live Weergave",
      "Current": "Huidig",
      "Recent": "Recent",
      "Delete Account": "Account Verwijderen",
      "Saved Studio": "Opgeslagen Studio",
      "Delete": "Verwijderen",
      "Wishlist and Saved Items": "Verlanglijst en Opgeslagen Items",
      "Curated Favorites": "Geselecteerde Favorieten",
      "In Stock": "Op Voorraad",
      "Low Stock": "Lage Voorraad",
      "Add to Cart": "Toevoegen aan Winkelwagen",
      "Remove": "Verwijderen",
      "Quick Actions": "Snelle Acties",
      "Manage Account": "Account Beheren",
      "Open Tool": "Tool Openen",
      "Order History": "Bestelgeschiedenis",
      "Wishlist": "Verlanglijst",
      "Identity verified": "Identiteit geverifieerd",
      "Primary payment method updated": "Primaire betaalmethode bijgewerkt",
      "Draft changes are ready.": "Conceptwijzigingen zijn klaar.",
      "Discard": "Verwerpen",
      "Save Account": "Account Opslaan"
    },
    "pl-PL": {
      "GirffoN Account Center": "Centrum Konta GirffoN",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Zarzadzaj profilem, zamowieniami, preferencjami stylu i zapisanymi danymi w jednej premium przestrzeni.",
      "Edit Profile": "Edytuj Profil",
      "View Orders": "Zobacz Zamowienia",
      "Account Navigation": "Nawigacja Konta",
      "Profile Details": "Szczegoly Profilu",
      "Profile Photo": "Zdjecie Profilowe",
      "Recent Orders": "Ostatnie Zamowienia",
      "Address Book": "Ksiazka Adresowa",
      "Payments": "Platnosci",
      "Preferences": "Preferencje",
      "Security": "Bezpieczenstwo",
      "My Designs": "Moje Projekty",
      "Saved Items": "Zapisane Elementy",
      "Account Tools": "Narzedia Konta",
      "Profile Completion": "Uzupelnienie Profilu",
      "Save Changes": "Zapisz Zmiany",
      "Reset Draft": "Resetuj Wersje Robocza",
      "Exclusive Member Benefits": "Ekskluzywne Korzysci Czlonka",
      "Personal Information": "Dane Osobowe",
      "Validation Ready": "Gotowe do Walidacji",
      "First Name": "Imie",
      "Last Name": "Nazwisko",
      "Email Address": "Adres Email",
      "Phone Number": "Numer Telefonu",
      "Date of Birth": "Data Urodzenia",
      "Gender": "Plec",
      "Country": "Kraj",
      "City": "Miasto",
      "Postal Code": "Kod Pocztowy",
      "Full Address": "Pelny Adres",
      "Preferred Language": "Preferowany Jezyk",
      "Choose a profile image": "Wybierz zdjecie profilowe",
      "Upload Photo": "Przeslij Zdjecie",
      "Apply Photo": "Zastosuj Zdjecie",
      "Account Summary": "Podsumowanie Konta",
      "Order History Preview": "Podglad Historii Zamowien",
      "Download Invoices": "Pobierz Faktury",
      "Track Package": "Sledz Paczke",
      "Recent Order": "Ostatnie Zamowienie",
      "In Transit": "W Drodze",
      "Order Number": "Numer Zamowienia",
      "Date": "Data",
      "Total Amount": "Kwota Laczna",
      "Status": "Status",
      "View Details": "Zobacz Szczegoly",
      "Delivered": "Dostarczono",
      "Shipping Addresses": "Adresy Dostawy",
      "Add New Address": "Dodaj Nowy Adres",
      "Default": "Domyslny",
      "Edit": "Edytuj",
      "Duplicate": "Duplikuj",
      "Set Default": "Ustaw Domyslny",
      "Payment Methods": "Metody Platnosci",
      "Add New Payment": "Dodaj Nowa Platnosc",
      "Secure": "Bezpieczne",
      "Primary": "Glowna",
      "Cardholder": "Posiadacz Karty",
      "Expires": "Wygasa",
      "Billing Method": "Metoda Rozliczenia",
      "Edit Card": "Edytuj Karte",
      "Set Billing": "Ustaw Rozliczenie",
      "Verified": "Zweryfikowane",
      "Use for Orders": "Uzyj do Zamowien",
      "Communication Preferences": "Preferencje Komunikacji",
      "Luxury Notifications": "Powiadomienia Premium",
      "Birthday Gift": "Prezent Urodzinowy",
      "Birthday Date": "Data Urodzin",
      "Save Birthday": "Zapisz Urodziny",
      "New Collection": "Nowa Kolekcja",
      "Collection and Catalog Subscription": "Subskrypcja Kolekcji i Katalogu",
      "Subscribe": "Subskrybuj",
      "Preview Tool": "Narzędzie Podglądu",
      "Test Email": "Email Testowy",
      "Send Test Email": "Wyslij Email Testowy",
      "Promotional Emails": "Maile Promocyjne",
      "Catalog Emails": "Maile Katalogowe",
      "Birthday Discount Emails": "Maile ze Znizka Urodzinowa",
      "Order Updates": "Aktualizacje Zamowienia",
      "Account Protection": "Ochrona Konta",
      "Change Password": "Zmien Haslo",
      "Two-Factor Authentication": "Uwierzytelnianie Dwuskladnikowe",
      "Login Activity": "Aktywnosc Logowania",
      "Monitored": "Monitorowane",
      "Active devices": "Aktywne urzadzenia",
      "Latest verified sign-in": "Ostatnie zweryfikowane logowanie",
      "Current secure location": "Aktualna bezpieczna lokalizacja",
      "Recent Sessions": "Ostatnie Sesje",
      "Live View": "Widok Na Zywo",
      "Current": "Biezaca",
      "Recent": "Ostatnia",
      "Delete Account": "Usun Konto",
      "Saved Studio": "Zapisane Studio",
      "Delete": "Usun",
      "Wishlist and Saved Items": "Wishlist i Zapisane Elementy",
      "Curated Favorites": "Wybrane Ulubione",
      "In Stock": "W Magazynie",
      "Low Stock": "Niski Stan",
      "Add to Cart": "Dodaj do Koszyka",
      "Remove": "Usun",
      "Quick Actions": "Szybkie Akcje",
      "Manage Account": "Zarzadzaj Kontem",
      "Open Tool": "Otworz Narzedzie",
      "Order History": "Historia Zamowien",
      "Wishlist": "Wishlist",
      "Identity verified": "Tozsamosc zweryfikowana",
      "Primary payment method updated": "Zaktualizowano glowna metode platnosci",
      "Draft changes are ready.": "Zmiany robocze sa gotowe.",
      "Discard": "Odrzuc",
      "Save Account": "Zapisz Konto"
    },
    "sv-SE": {
      "GirffoN Account Center": "GirffoN Kontocenter",
      "Manage your profile, orders, style preferences, and saved details in one premium space.": "Hantera profil, bestallningar, stilpreferenser och sparade uppgifter i ett premiumutrymme.",
      "Edit Profile": "Redigera Profil",
      "View Orders": "Visa Bestallningar",
      "Account Navigation": "Kontonavigering",
      "Profile Details": "Profildetaljer",
      "Profile Photo": "Profilbild",
      "Recent Orders": "Senaste Bestallningar",
      "Address Book": "Adressbok",
      "Payments": "Betalningar",
      "Preferences": "Preferenser",
      "Security": "Sakerhet",
      "My Designs": "Mina Designer",
      "Saved Items": "Sparade Artiklar",
      "Account Tools": "Kontoverktyg",
      "Profile Completion": "Profilslutforande",
      "Save Changes": "Spara Andringar",
      "Reset Draft": "Aterstall Utkast",
      "Exclusive Member Benefits": "Exklusiva Medlemsfordelar",
      "Personal Information": "Personlig Information",
      "Validation Ready": "Redo for Validering",
      "First Name": "Fornamn",
      "Last Name": "Efternamn",
      "Email Address": "E-postadress",
      "Phone Number": "Telefonnummer",
      "Date of Birth": "Fodelsedatum",
      "Gender": "Kon",
      "Country": "Land",
      "City": "Stad",
      "Postal Code": "Postnummer",
      "Full Address": "Fullstandig Adress",
      "Preferred Language": "Foredraget Sprak",
      "Choose a profile image": "Valj en profilbild",
      "Upload Photo": "Ladda Upp Foto",
      "Apply Photo": "Anvand Foto",
      "Account Summary": "Kontosammanfattning",
      "Order History Preview": "Forhandsvisning av Orderhistorik",
      "Download Invoices": "Ladda Ned Fakturor",
      "Track Package": "Spara Paket",
      "Recent Order": "Senaste Order",
      "In Transit": "Pa Vag",
      "Order Number": "Ordernummer",
      "Date": "Datum",
      "Total Amount": "Totalt Belopp",
      "Status": "Status",
      "View Details": "Visa Detaljer",
      "Delivered": "Levererad",
      "Shipping Addresses": "Leveransadresser",
      "Add New Address": "Lagg Till Ny Adress",
      "Default": "Standard",
      "Edit": "Redigera",
      "Duplicate": "Duplicera",
      "Set Default": "Ange som Standard",
      "Payment Methods": "Betalningsmetoder",
      "Add New Payment": "Lagg Till Ny Betalning",
      "Secure": "Saker",
      "Primary": "Primar",
      "Cardholder": "Kortinnehavare",
      "Expires": "Galler till",
      "Billing Method": "Faktureringsmetod",
      "Edit Card": "Redigera Kort",
      "Set Billing": "Stall in Fakturering",
      "Verified": "Verifierad",
      "Use for Orders": "Anvand for Bestallningar",
      "Communication Preferences": "Kommunikationspreferenser",
      "Luxury Notifications": "Premiumnotiser",
      "Birthday Gift": "Fodelsedagspresent",
      "Birthday Date": "Fodelsedatum",
      "Save Birthday": "Spara Fodelsedag",
      "New Collection": "Ny Kollektion",
      "Collection and Catalog Subscription": "Prenumeration pa Kollektion och Katalog",
      "Subscribe": "Prenumerera",
      "Preview Tool": "Förhandsgranskningsverktyg",
      "Test Email": "Testmail",
      "Send Test Email": "Skicka Testmail",
      "Promotional Emails": "Kampanjmejl",
      "Catalog Emails": "Katalogmejl",
      "Birthday Discount Emails": "Fodelsedagsrabattmejl",
      "Order Updates": "Orderuppdateringar",
      "Account Protection": "Kontoskydd",
      "Change Password": "Byt Losenord",
      "Two-Factor Authentication": "Tvafaktorsautentisering",
      "Login Activity": "Inloggningsaktivitet",
      "Monitored": "Overvakad",
      "Active devices": "Aktiva enheter",
      "Latest verified sign-in": "Senaste verifierade inloggning",
      "Current secure location": "Nuvarande saker plats",
      "Recent Sessions": "Senaste Sessioner",
      "Live View": "Livevy",
      "Current": "Aktuell",
      "Recent": "Senaste",
      "Delete Account": "Radera Konto",
      "Saved Studio": "Sparad Studio",
      "Delete": "Radera",
      "Wishlist and Saved Items": "Onskelista och Sparade Artiklar",
      "Curated Favorites": "Utvalda Favoriter",
      "In Stock": "I Lager",
      "Low Stock": "Lagt Lager",
      "Add to Cart": "Lagg i Varukorg",
      "Remove": "Ta Bort",
      "Quick Actions": "Snabba Atgarder",
      "Manage Account": "Hantera Konto",
      "Open Tool": "Oppna Verktyg",
      "Order History": "Orderhistorik",
      "Wishlist": "Onskelista",
      "Identity verified": "Identitet verifierad",
      "Primary payment method updated": "Primar betalningsmetod uppdaterad",
      "Draft changes are ready.": "Utkastandringar ar klara.",
      "Discard": "Kassera",
      "Save Account": "Spara Konto"
    }
  };

  const PROFILE_RUNTIME_I18N = {
    "en-GB": {
      pageTitle: "GirffoN - Profile Page",
      birthdayHeading: "Birthday Gift",
      catalogPlaceholder: "name@example.com",
      testEmailPlaceholder: "name@example.com",
      imageOnlyToast: "Please choose an image file.",
      photoUpdatedToast: "Profile photo updated.",
      draftSavedToast: "Changes saved to your account draft.",
      profileNeedsAttentionToast: "Profile needs attention.",
      noProfileChangesToast: "No new profile changes.",
      profileSavedToast: "Profile details saved.",
      birthdayRequiredToast: "Birthday date required.",
      invalidBirthdayToast: "Invalid birthday date.",
      birthdayAlreadySavedToast: "Birthday already saved.",
      birthdaySavedToast: "Birthday saved.",
      draftRevertedToast: "Draft reverted.",
      invalidSubscriptionToast: "Subscription email is invalid.",
      alreadySubscribedToast: "Already subscribed.",
      subscriptionUpdatedToast: "Catalog subscription updated.",
      invalidTestEmailToast: "Preview email is invalid.",
      previewGeneratedToast: "Preview already generated.",
      testCompletedToast: "Email preview updated.",
      profileErrorTitle: "Profile not saved",
      profileErrorMessage: "Complete all required profile fields before saving your account details.",
      profileWarningTitle: "No new changes detected",
      profileWarningMessage: "Your personal information already matches the latest saved draft. Update a field before saving again.",
      profileSuccessTitle: "Profile draft saved",
      profileSuccessMessage: "Your personal information has been updated and is ready for the next secure sync step.",
      birthdayRequiredTitle: "Birthday required",
      birthdayRequiredMessage: "Choose your birthday date before saving to activate the member birthday reward setup.",
      birthdayInvalidTitle: "Invalid birthday date",
      birthdayInvalidMessage: "Use a valid date in the past so GirffoN can prepare your annual birthday benefit correctly.",
      birthdayWarningTitle: "Birthday already saved",
      birthdayWarningMessage: "This birthday is already stored in your current account draft, so no new change was applied.",
      birthdaySuccessTitle: "Birthday saved",
      birthdaySuccessMessage: "Your birthday reward profile is updated. GirffoN will keep the 40% birthday discount ready for your special day.",
      subscriptionErrorTitle: "Subscription not sent",
      subscriptionErrorMessage: "Please enter a valid email address before joining the GirffoN catalog list.",
      subscriptionWarningTitle: "Already subscribed",
      subscriptionWarningMessage: "This email is already on the premium catalog list. No duplicate subscription was added.",
      subscriptionSuccessTitle: "Subscription confirmed",
      subscriptionSuccessMessage: "You are subscribed. GirffoN will send new collections, catalog editions, and exclusive offers to your inbox.",
      testErrorTitle: "Email preview not sent",
      testErrorMessage: "Please enter a valid email address to preview the GirffoN email experience.",
      testWarningTitle: "Preview already sent",
      testWarningMessage: "A preview was already prepared for this address. Update the email if you want to refresh the preview details.",
      testSuccessTitle: "Email preview ready",
      testSuccessMessage: "Preview updated successfully. This prepares the current account email view without sending a live message yet."
    },
    "it-IT": {
      pageTitle: "GirffoN - Pagina Profilo",
      birthdayHeading: "Regalo di Compleanno",
      imageOnlyToast: "Seleziona un file immagine.",
      photoUpdatedToast: "Foto profilo aggiornata.",
      draftSavedToast: "Modifiche salvate nella bozza account.",
      profileNeedsAttentionToast: "Il profilo richiede attenzione.",
      noProfileChangesToast: "Nessuna nuova modifica al profilo.",
      profileSavedToast: "Dettagli profilo salvati.",
      birthdayRequiredToast: "Data di compleanno richiesta.",
      invalidBirthdayToast: "Data di compleanno non valida.",
      birthdayAlreadySavedToast: "Compleanno gia salvato.",
      birthdaySavedToast: "Compleanno salvato.",
      draftRevertedToast: "Bozza ripristinata.",
      invalidSubscriptionToast: "Email iscrizione non valida.",
      alreadySubscribedToast: "Gia iscritto.",
      subscriptionUpdatedToast: "Iscrizione catalogo aggiornata.",
      invalidTestEmailToast: "Email anteprima non valida.",
      previewGeneratedToast: "Anteprima gia generata.",
      testCompletedToast: "Anteprima email aggiornata.",
      profileErrorTitle: "Profilo non salvato",
      profileErrorMessage: "Completa tutti i campi obbligatori prima di salvare i dettagli account.",
      profileWarningTitle: "Nessuna nuova modifica rilevata",
      profileWarningMessage: "Le informazioni personali corrispondono gia all'ultima bozza salvata. Aggiorna un campo prima di salvare di nuovo.",
      profileSuccessTitle: "Bozza profilo salvata",
      profileSuccessMessage: "Le informazioni personali sono state aggiornate e sono pronte per il prossimo passaggio sicuro.",
      birthdayRequiredTitle: "Compleanno richiesto",
      birthdayRequiredMessage: "Scegli la data di compleanno prima di salvare per attivare il premio compleanno.",
      birthdayInvalidTitle: "Data compleanno non valida",
      birthdayInvalidMessage: "Usa una data valida nel passato per preparare correttamente il vantaggio compleanno.",
      birthdayWarningTitle: "Compleanno gia salvato",
      birthdayWarningMessage: "Questa data e gia presente nella bozza account corrente, quindi non e stata applicata nessuna modifica.",
      birthdaySuccessTitle: "Compleanno salvato",
      birthdaySuccessMessage: "Il profilo premio compleanno e aggiornato. GirffoN terra pronto lo sconto compleanno del 40%.",
      subscriptionErrorTitle: "Iscrizione non inviata",
      subscriptionErrorMessage: "Inserisci un indirizzo email valido prima di entrare nella lista catalogo GirffoN.",
      subscriptionWarningTitle: "Gia iscritto",
      subscriptionWarningMessage: "Questa email e gia nella lista catalogo premium. Nessuna iscrizione duplicata e stata aggiunta.",
      subscriptionSuccessTitle: "Iscrizione confermata",
      subscriptionSuccessMessage: "Iscrizione attiva. GirffoN inviera nuove collezioni, cataloghi e offerte esclusive alla tua inbox.",
      testErrorTitle: "Anteprima email non inviata",
      testErrorMessage: "Inserisci un indirizzo email valido per vedere l'anteprima esperienza email GirffoN.",
      testWarningTitle: "Anteprima gia inviata",
      testWarningMessage: "Era gia stata preparata un'anteprima per questo indirizzo. Aggiorna l'email se vuoi rigenerare i dettagli dell'anteprima.",
      testSuccessTitle: "Anteprima email pronta",
      testSuccessMessage: "Anteprima aggiornata con successo. Questo prepara la vista email dell'account senza inviare ancora un messaggio reale."
    }
  };

  const fileInput = document.getElementById("gfProfileAvatarInput");
  const avatarTargets = Array.from(document.querySelectorAll("[data-gf-avatar-target]"));
  const navLinks = Array.from(document.querySelectorAll(".gf-account-nav a"));
  const saveButtons = Array.from(document.querySelectorAll("[data-gf-save]"));
  const resetButton = document.querySelector("[data-gf-reset]");
  const toast = document.getElementById("gfAccountToast");
  const progressValue = document.querySelector("[data-gf-profile-progress]");
  const progressBar = document.querySelector(".gf-account-progress span");
  const toggleButtons = Array.from(document.querySelectorAll(".gf-account-toggle"));
  const profileForm = document.getElementById("gfAccountProfileForm");
  const profileSaveStatus = document.getElementById("gfProfileSaveStatus");
  const birthdayInput = document.getElementById("gfBirthdayGiftDate");
  const birthdaySaveButton = document.querySelector("[data-gf-save-birthday]");
  const birthdaySaveStatus = document.getElementById("gfBirthdaySaveStatus");
  const catalogForm = document.getElementById("gfCatalogSubscriptionForm");
  const catalogEmailInput = document.getElementById("gfCatalogSubscribeEmail");
  const catalogStatus = document.getElementById("gfCatalogSubscribeStatus");
  const testEmailForm = document.getElementById("gfTestEmailForm");
  const testEmailInput = document.getElementById("gfTestEmailInput");
  const testEmailStatus = document.getElementById("gfTestEmailStatus");
  const securitySaveStatus = document.getElementById("gfSecuritySaveStatus");
  const activeDevicesValue = center.querySelector("[data-gf-active-devices]");
  const lastSigninValue = center.querySelector("[data-gf-last-signin]");
  const sessionList = center.querySelector("[data-gf-session-list]");
  const localeCards = Array.from(document.querySelectorAll(".gf-locale-card"));
  const summaryGrid = center.querySelector(".gf-account-summary-grid");
  const recentOrdersActions = Array.from(document.querySelectorAll("#gfRecentOrders .gf-account-section-actions .gf-account-btn"));
  const ordersGrid = center.querySelector(".gf-account-orders");
  const designsGrid = center.querySelector(".gf-account-designs-grid");
  const savedGrid = center.querySelector(".gf-account-saved-grid");
  const CUSTOM_DESIGN_PAGE = "Image/Custom Design Pro/CustomDesignPro.html";
  const SHARED_KEYS = {
    wishlist: "girffon_wishlist",
    cart: "girffon_cart",
    orders: "girffon_orders",
    addresses: "girffon_addresses",
    paymentMethods: "girffon_payment_methods",
    user: "gfUserData",
    profile: "girffon_profile",
    accountState: "girffon_account_state",
    avatar: "girffon_profile_avatar",
    cdpProjectPrefix: "cdpProject_",
    cdpCurrentProject: "cdpCurrentProject",
    cdpCurrentFolder: "cdpCurrentFolder",
    cdpPendingProjectPath: "cdpPendingProjectPath"
  };
  const defaultProfileState = profileForm ? Object.fromEntries(new FormData(profileForm).entries()) : {};
  const EMPTY_AVATAR_DATA_URI = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='110' height='110'%3E%3C/svg%3E";
  const PROFILE_AVATAR_UPLOAD_URL = "/GirffoN/backend/profile/upload-avatar.php";
  let profileSnapshot = "";
  let savedBirthdayValue = birthdayInput ? birthdayInput.value : "";
  let subscribedCatalogEmail = "";
  let lastTestEmailSent = "";
  let currentRuntimeTexts = PROFILE_RUNTIME_I18N["en-GB"];

  function getDefaultAccountState() {
    const userState = readUserState() || {};
    const email = userState.email || "";

    return {
      preferences: {
        promotionalEmails: true,
        catalogEmails: true,
        birthdayDiscountEmails: true,
        orderUpdates: true,
        catalogSubscriptionEmail: email,
        testEmail: email
      },
      security: {
        twoFactorEnabled: true,
        passwordUpdatedAt: "",
        lastLoginAt: "",
        lastLoginLocation: "",
        activeDevices: 1,
        sessions: []
      }
    };
  }

  function safeReadArray(key) {
    try {
      const rawValue = localStorage.getItem(key);
      if (!rawValue) return [];
      const parsed = JSON.parse(rawValue);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function safeWriteArray(key, value) {
    localStorage.setItem(key, JSON.stringify(Array.isArray(value) ? value : []));
  }

  function safeReadObject(key) {
    try {
      const rawValue = localStorage.getItem(key);
      return rawValue ? JSON.parse(rawValue) : null;
    } catch (_error) {
      return null;
    }
  }

  function safeWriteObject(key, value) {
    if (!value || typeof value !== "object") {
      localStorage.removeItem(key);
      return;
    }

    localStorage.setItem(key, JSON.stringify(value));
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function readWishlist() {
    if (bridge && typeof bridge.readWishlist === "function") {
      return bridge.readWishlist();
    }
    return safeReadArray(SHARED_KEYS.wishlist);
  }

  function writeWishlist(items) {
    if (bridge && typeof bridge.writeWishlist === "function") {
      bridge.writeWishlist(items);
      return;
    }
    safeWriteArray(SHARED_KEYS.wishlist, items);
  }

  function readCart() {
    if (bridge && typeof bridge.readCart === "function") {
      return bridge.readCart();
    }
    return safeReadArray(SHARED_KEYS.cart);
  }

  function writeCart(items) {
    if (bridge && typeof bridge.writeCart === "function") {
      bridge.writeCart(items);
      return;
    }
    safeWriteArray(SHARED_KEYS.cart, items);
  }

  function readOrders() {
    const rawOrders = bridge && typeof bridge.readOrders === "function"
      ? bridge.readOrders()
      : safeReadArray(SHARED_KEYS.orders);

    return (Array.isArray(rawOrders) ? rawOrders : [])
      .filter(isRealCheckoutOrder)
      .sort(function (left, right) {
        return new Date(right?.createdAt || 0).getTime() - new Date(left?.createdAt || 0).getTime();
      });
  }

  function readPaymentMethods() {
    if (bridge && typeof bridge.readPaymentMethods === "function") {
      return bridge.readPaymentMethods();
    }

    return safeReadArray(SHARED_KEYS.paymentMethods);
  }

  function readExtraAddresses() {
    if (bridge && typeof bridge.readAddresses === "function") {
      return bridge.readAddresses();
    }

    return safeReadArray(SHARED_KEYS.addresses);
  }

  function writePaymentMethods(items) {
    if (bridge && typeof bridge.writePaymentMethods === "function") {
      bridge.writePaymentMethods(items);
      return;
    }

    safeWriteArray(SHARED_KEYS.paymentMethods, items);
  }

  function writeExtraAddresses(items) {
    if (bridge && typeof bridge.writeAddresses === "function") {
      bridge.writeAddresses(items);
      return;
    }

    safeWriteArray(SHARED_KEYS.addresses, items);
  }

  function readUserState() {
    if (bridge && typeof bridge.readUser === "function") {
      return bridge.readUser();
    }
    return safeReadObject(SHARED_KEYS.user);
  }

  function writeUserState(user) {
    if (bridge && typeof bridge.writeUser === "function") {
      bridge.writeUser(user);
      return;
    }
    safeWriteObject(SHARED_KEYS.user, user);
  }

  function readProfileState() {
    if (bridge && typeof bridge.readProfile === "function") {
      return bridge.readProfile();
    }
    return safeReadObject(SHARED_KEYS.profile);
  }

  function writeProfileState(profile) {
    if (bridge && typeof bridge.writeProfile === "function") {
      bridge.writeProfile(profile);
      return;
    }
    safeWriteObject(SHARED_KEYS.profile, profile);
  }

  function readAccountState() {
    if (bridge && typeof bridge.readAccountState === "function") {
      return bridge.readAccountState() || getDefaultAccountState();
    }

    const savedState = safeReadObject(SHARED_KEYS.accountState);
    const defaultState = getDefaultAccountState();
    return {
      ...defaultState,
      ...(savedState || {}),
      preferences: {
        ...defaultState.preferences,
        ...((savedState && savedState.preferences) || {})
      },
      security: {
        ...defaultState.security,
        ...((savedState && savedState.security) || {})
      }
    };
  }

  function writeAccountState(state) {
    if (bridge && typeof bridge.writeAccountState === "function") {
      bridge.writeAccountState(state);
      return;
    }
    safeWriteObject(SHARED_KEYS.accountState, state);
  }

  function readAvatarState() {
    if (bridge && typeof bridge.readProfileAvatar === "function") {
      return bridge.readProfileAvatar() || "";
    }
    return String(localStorage.getItem(SHARED_KEYS.avatar) || "");
  }

  function writeAvatarState(value) {
    if (bridge && typeof bridge.writeProfileAvatar === "function") {
      bridge.writeProfileAvatar(value || "");
      document.dispatchEvent(new CustomEvent("girffon:profile-updated"));
      return;
    }

    if (!value) {
      localStorage.removeItem(SHARED_KEYS.avatar);
      document.dispatchEvent(new CustomEvent("girffon:profile-updated"));
      return;
    }

    localStorage.setItem(SHARED_KEYS.avatar, value);
    document.dispatchEvent(new CustomEvent("girffon:profile-updated"));
  }

  function getNameParts(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    return {
      firstName: parts.shift() || "",
      lastName: parts.join(" ")
    };
  }

  function collectProfileState() {
    if (!profileForm) {
      return {};
    }

    const nextState = Object.fromEntries(new FormData(profileForm).entries());
    if (birthdayInput) {
      nextState.birthdayGiftDate = birthdayInput.value.trim();
    }
    return nextState;
  }

  function getProfileCompletionPercentage(profile) {
    const fields = [
      "firstName",
      "lastName",
      "email",
      "phone",
      "dateOfBirth",
      "gender",
      "country",
      "city",
      "postalCode",
      "fullAddress",
      "preferredLanguage"
    ];

    const completedCount = fields.reduce(function (count, key) {
      return String((profile && profile[key]) || "").trim() ? count + 1 : count;
    }, 0);

    return Math.round((completedCount / fields.length) * 100);
  }

  function renderProfileCompletion(profile) {
    const percentage = getProfileCompletionPercentage(profile);
    if (progressValue) {
      progressValue.textContent = percentage + "%";
    }
    if (progressBar) {
      progressBar.style.width = percentage + "%";
    }
  }

  function applyAvatarState() {
    const avatarSrc = readAvatarState();
    const savedProfile = readProfileState() || {};
    const hasPersistedProfile = Object.keys(savedProfile).some(function (key) {
      return String(savedProfile[key] || "").trim() !== "";
    });
    const nextAvatarSrc = hasPersistedProfile && avatarSrc ? avatarSrc : EMPTY_AVATAR_DATA_URI;

    avatarTargets.forEach(function (img) {
      img.src = nextAvatarSrc;
      img.classList.toggle("is-empty", nextAvatarSrc === EMPTY_AVATAR_DATA_URI);
    });

    if (profileAvatarWrap) {
      profileAvatarWrap.classList.toggle("is-empty", nextAvatarSrc === EMPTY_AVATAR_DATA_URI);
    }
  }

  function setFormValues(values) {
    if (!profileForm || !values) {
      return;
    }

    Array.from(profileForm.elements).forEach(function (field) {
      if (!field || !field.name || typeof field.value === "undefined") {
        return;
      }

      if (Object.prototype.hasOwnProperty.call(values, field.name) && values[field.name] != null) {
        field.value = values[field.name];
      }
    });
  }

  function updateToggleStateFromAccount(accountState) {
    const preferences = accountState && accountState.preferences || {};
    const security = accountState && accountState.security || {};

    toggleButtons.forEach(function (button) {
      const key = button.getAttribute("data-gf-pref-key") || "";
      if (!key) {
        return;
      }

      let nextValue;
      if (Object.prototype.hasOwnProperty.call(preferences, key)) {
        nextValue = Boolean(preferences[key]);
      } else if (Object.prototype.hasOwnProperty.call(security, key)) {
        nextValue = Boolean(security[key]);
      } else {
        nextValue = button.getAttribute("data-default-state") !== "false";
      }

      button.setAttribute("aria-checked", nextValue ? "true" : "false");
    });
  }

  function renderSecurityState(accountState) {
    const security = accountState && accountState.security || {};
    const sessions = Array.isArray(security.sessions) ? security.sessions : [];

    if (activeDevicesValue) {
      activeDevicesValue.textContent = String(security.activeDevices || Math.max(1, sessions.length || 1));
    }

    if (lastSigninValue) {
      lastSigninValue.textContent = security.lastLoginAt ? formatDate(security.lastLoginAt) : "Local";
    }

    if (securityLocation && security.lastLoginLocation) {
      securityLocation.textContent = security.lastLoginLocation;
    }

    if (currentSession && sessions.length) {
      const currentEntry = sessions[0];
      currentSession.textContent = (currentEntry.location || "Local workspace") + " · " + (currentEntry.isCurrent ? "Active now" : formatDate(currentEntry.lastSeen));
    }

    if (sessionList && sessions.length) {
      sessionList.innerHTML = sessions.slice(0, 3).map(function (session) {
        const badgeClass = session.isCurrent ? "gf-account-session-badge is-current" : "gf-account-session-badge";
        const badgeText = session.isCurrent ? "Current" : "Recent";
        const detail = (session.location || "Local workspace") + " · " + (session.isCurrent ? "Active now" : formatDate(session.lastSeen));

        return '<div class="gf-account-session-item" role="listitem"><div><strong>'
          + escapeHtml(session.label || "Local session")
          + '</strong><p class="gf-account-note">'
          + escapeHtml(detail)
          + '</p></div><span class="'
          + badgeClass
          + '">' + badgeText + '</span></div>';
      }).join("");
    }
  }

  function applyPersistedAccountState() {
    const accountState = readAccountState();
    const preferences = accountState.preferences || {};

    updateToggleStateFromAccount(accountState);
    renderSecurityState(accountState);

    if (catalogEmailInput) {
      catalogEmailInput.value = preferences.catalogSubscriptionEmail || "";
    }

    if (testEmailInput) {
      testEmailInput.value = preferences.testEmail || preferences.catalogSubscriptionEmail || "";
    }

    subscribedCatalogEmail = preferences.catalogSubscriptionEmail || "";
    lastTestEmailSent = preferences.testEmail || "";
  }

  function persistToggleState(button) {
    const key = button.getAttribute("data-gf-pref-key") || "";
    if (!key) {
      return;
    }

    const accountState = readAccountState();
    const nextState = {
      ...accountState,
      preferences: {
        ...(accountState.preferences || {})
      },
      security: {
        ...(accountState.security || {})
      }
    };
    const nextValue = button.getAttribute("aria-checked") === "true";

    if (key === "twoFactorEnabled") {
      nextState.security.twoFactorEnabled = nextValue;
    } else {
      nextState.preferences[key] = nextValue;
    }

    writeAccountState(nextState);
    applyPersistedAccountState();
  }

  function persistAccountPreferenceValues(values) {
    const accountState = readAccountState();
    writeAccountState({
      ...accountState,
      preferences: {
        ...(accountState.preferences || {}),
        ...values
      },
      security: {
        ...(accountState.security || {})
      }
    });
  }

  function ensureRecentSessionState() {
    const accountState = readAccountState();
    const security = {
      ...(accountState.security || {})
    };
    const now = new Date().toISOString();
    const fallbackLocation = [
      primaryAddressCity && primaryAddressCity.textContent,
      primaryAddressCountry && primaryAddressCountry.textContent
    ].filter(Boolean).join(", ") || getDefaultCountryLabel();
    const existingSessions = Array.isArray(security.sessions) ? security.sessions.slice() : [];
    const currentSessionEntry = {
      label: "Current account session",
      location: security.lastLoginLocation || fallbackLocation,
      lastSeen: now,
      isCurrent: true
    };
    const recentSessions = existingSessions
      .filter(function (session) {
        return session && !session.isCurrent;
      })
      .slice(0, 2);

    writeAccountState({
      ...accountState,
      security: {
        ...security,
        lastLoginAt: security.lastLoginAt || now,
        lastLoginLocation: security.lastLoginLocation || fallbackLocation,
        activeDevices: Math.max(1, security.activeDevices || existingSessions.length || 1),
        sessions: [currentSessionEntry].concat(recentSessions)
      }
    });
  }

  function saveSecurityState() {
    const accountState = readAccountState();
    writeAccountState({
      ...accountState,
      preferences: {
        ...(accountState.preferences || {})
      },
      security: {
        ...(accountState.security || {}),
        passwordUpdatedAt: new Date().toISOString()
      }
    });
    renderFeedback(securitySaveStatus, "success", "Security updated", "Local security state was updated and linked to the current GirffoN account.");
    showToast(currentRuntimeTexts.draftSavedToast);
    applyPersistedAccountState();
  }

  function updateProfileIdentity(profile) {
    const fullName = [profile && profile.firstName, profile && profile.lastName].filter(Boolean).join(" ").trim();
    const email = String(profile && profile.email || "");
    const phone = String(profile && (profile.phone || profile.phoneNumber) || "");
    const country = String(profile && profile.country || getDefaultCountryLabel());
    const city = String(profile && profile.city || "Add your city");
    const postalCode = String(profile && profile.postalCode || "Add your postal code");
    const address = String(profile && profile.fullAddress || "Save a delivery address to complete your local profile.");

    if (profileHeaderName) {
      profileHeaderName.textContent = fullName;
      profileHeaderName.hidden = !fullName;
    }

    if (profileHeaderEmail) {
      profileHeaderEmail.textContent = email;
      profileHeaderEmail.href = email ? ("mailto:" + email) : "#";
      profileHeaderEmail.hidden = !email;
    }

    if (profileHeaderPhone) {
      profileHeaderPhone.textContent = phone;
      profileHeaderPhone.href = phone ? ("tel:" + phone.replace(/\s+/g, "")) : "#";
      profileHeaderPhone.hidden = !phone;
    }

    if (primaryAddressName) {
      primaryAddressName.textContent = fullName;
    }

    if (primaryAddressPhone) {
      primaryAddressPhone.textContent = phone || "Add your phone number";
    }

    if (primaryAddressCountry) {
      primaryAddressCountry.textContent = country;
    }

    if (primaryAddressCity) {
      primaryAddressCity.textContent = city;
    }

    if (primaryAddressPostal) {
      primaryAddressPostal.textContent = postalCode;
    }

    if (primaryAddressFull) {
      primaryAddressFull.textContent = address;
    }

    if (primaryCardholder) {
      primaryCardholder.textContent = fullName;
    }

    if (securityLocation) {
      securityLocation.textContent = city && city !== "Add your city" ? city : getDefaultCountryLabel();
    }

    if (currentSession) {
      currentSession.textContent = (city && city !== "Add your city" ? city + ", " + country : "Current account session") + " · Active now";
    }
  }

  function applyPersistedProfileState() {
    const savedProfile = readProfileState() || {};
    const mergedProfile = {
      ...defaultProfileState,
      ...savedProfile,
      firstName: savedProfile.firstName || defaultProfileState.firstName || "",
      lastName: savedProfile.lastName || defaultProfileState.lastName || "",
      email: savedProfile.email || defaultProfileState.email || "",
      phone: savedProfile.phone || defaultProfileState.phone || "",
      country: savedProfile.country || defaultProfileState.country || getDefaultCountryLabel(),
      preferredLanguage: savedProfile.preferredLanguage || defaultProfileState.preferredLanguage || getDefaultLanguageLabel(),
      birthdayGiftDate: savedProfile.birthdayGiftDate || defaultProfileState.birthdayGiftDate || ""
    };

    const formProfile = {
      ...mergedProfile,
      firstName: savedProfile.firstName || defaultProfileState.firstName || "",
      email: savedProfile.email || defaultProfileState.email || ""
    };

    setFormValues(formProfile);
    if (birthdayInput) {
      birthdayInput.value = mergedProfile.birthdayGiftDate || "";
      savedBirthdayValue = birthdayInput.value;
    }
    updateProfileIdentity(mergedProfile);
    renderProfileCompletion(mergedProfile);
    applyAvatarState();
    profileSnapshot = profileForm ? new URLSearchParams(new FormData(profileForm)).toString() : "";
  }

  async function syncProfileStateFromForm() {
    const nextProfile = collectProfileState();
    if (authApi && typeof authApi.isRemoteEnabled === "function" && authApi.isRemoteEnabled() && typeof authApi.ensureSession === "function") {
      try {
        await authApi.ensureSession();
      } catch (_error) {
        return Promise.reject(new Error("Your session is not available right now."));
      }
    }

    const existingUser = readUserState() || {};
    const fullName = [nextProfile.firstName, nextProfile.lastName].filter(Boolean).join(" ").trim() || existingUser.name || "GirffoN Member";

    writeProfileState(nextProfile);
    writeUserState({
      ...existingUser,
      name: fullName,
      email: nextProfile.email || existingUser.email || "guest@girffon.com"
    });

    if (authApi && typeof authApi.isRemoteEnabled === "function" && authApi.isRemoteEnabled() && existingUser && (existingUser.email || existingUser.id)) {
      const remoteUser = await authApi.saveProfile(nextProfile);
      if (remoteUser && remoteUser.profile) {
        writeProfileState(remoteUser.profile);
        writeUserState({
          ...existingUser,
          id: remoteUser.id || existingUser.id,
          username: remoteUser.username || existingUser.username,
          name: remoteUser.name || fullName,
          email: remoteUser.email || nextProfile.email || existingUser.email || "guest@girffon.com",
          phone: remoteUser.phone || nextProfile.phone || existingUser.phone || "",
          provider: remoteUser.provider || existingUser.provider || "email"
        });
      }
    }

    updateProfileIdentity(nextProfile);
    renderProfileCompletion(nextProfile);
    document.dispatchEvent(new CustomEvent("girffon:profile-updated"));
  }

  function downloadInvoices() {
    const result = bridge && typeof bridge.exportInvoices === "function"
      ? bridge.exportInvoices()
      : null;

    if (result && Number(result.count) > 0) {
      showToast("Invoices downloaded: " + result.count);
      return;
    }

    const orders = readOrders();
    if (!orders.length) {
      showToast("Your invoices will appear after your first completed order.");
      return;
    }

    openInvoicesList(orders);
  }

  function getInvoiceNumber(order, index) {
    const reference = String(order?.reference || order?.orderNumber || ("GF-ORDER-" + String(index + 1).padStart(3, "0"))).trim();
    return "INV-" + reference.replace(/[^A-Z0-9-]+/gi, "-").replace(/^-+|-+$/g, "");
  }

  function buildInvoiceDocument(order, index, autoPrint) {
    const items = Array.isArray(order?.items) ? order.items : [];
    const lineItemsMarkup = items.length
      ? items.map(function (item) {
          const quantity = Number(item?.qty || item?.quantity || 1) || 1;
          const unitPrice = Number(item?.price || 0) || 0;
          const lineTotal = quantity * unitPrice;
          const detailParts = [item?.size ? ("Size " + item.size) : "", item?.color || ""].filter(Boolean);

          return ""
            + "<tr>"
            + "<td><strong>" + escapeHtml(item?.title || item?.name || item?.code || "GirffoN Item") + "</strong><div style=\"font-size:12px;color:#7c6a57;margin-top:4px;\">" + escapeHtml(detailParts.join(" · ") || "GirffoN order item") + "</div></td>"
            + "<td>" + escapeHtml(String(quantity)) + "</td>"
            + "<td>" + escapeHtml(formatMoney(unitPrice)) + "</td>"
            + "<td>" + escapeHtml(formatMoney(lineTotal)) + "</td>"
            + "</tr>";
        }).join("")
      : "<tr><td colspan=\"4\">No invoice lines available.</td></tr>";

    const invoiceNumber = getInvoiceNumber(order, index);
    const shipping = order?.shipping && typeof order.shipping === "object" ? order.shipping : {};
    const shippingParts = [shipping.address, shipping.city, shipping.postalCode, shipping.country].filter(Boolean);

    return "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>"
      + escapeHtml(invoiceNumber)
      + "</title><style>body{font-family:Georgia,serif;background:#f7f1e8;color:#33251c;padding:32px}main{max-width:920px;margin:0 auto;background:#fffdf8;border:1px solid #e9dcc0;border-radius:26px;padding:32px;box-shadow:0 20px 48px rgba(120,90,34,.12)}.top{display:flex;justify-content:space-between;gap:24px;align-items:flex-start}.brand{font-size:12px;letter-spacing:.24em;text-transform:uppercase;color:#8b6b28;margin-bottom:12px}h1{margin:0;font-size:30px}.meta{display:grid;grid-template-columns:repeat(2,minmax(180px,1fr));gap:14px;margin:28px 0}.meta-card{border:1px solid #eadfc8;border-radius:18px;padding:14px 16px;background:#fff}.meta-card span{display:block;font-size:12px;color:#8b7355;text-transform:uppercase;letter-spacing:.12em;margin-bottom:8px}.meta-card strong{font-size:18px;color:#2f241a}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{padding:14px 12px;border-bottom:1px solid #eee3cb;text-align:left;vertical-align:top}th{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#8b6b28}.total{margin-top:22px;display:flex;justify-content:flex-end}.total-card{min-width:260px;border:1px solid #eadfc8;border-radius:18px;padding:16px 18px;background:#fbf6eb}.total-card span{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#8b7355;margin-bottom:8px}.total-card strong{font-size:28px;color:#2f241a}.note{margin-top:24px;padding:14px 16px;border-radius:18px;background:#f6eddc;color:#7a6129;font-size:14px}</style></head><body><main><div class=\"brand\">GirffoN Invoice</div><div class=\"top\"><div><h1>"
      + escapeHtml(invoiceNumber)
      + "</h1><p style=\"margin:10px 0 0;color:#6d5a46;\">Prepared for "
      + escapeHtml(order?.customerName || "GirffoN Customer")
      + "</p></div><div style=\"text-align:right;color:#6d5a46;\"><div>"
      + escapeHtml(formatDate(order?.createdAt))
      + "</div><div style=\"margin-top:8px;\">"
      + escapeHtml(String(order?.email || shipping.email || "guest@girffon.com"))
      + "</div></div></div><div class=\"meta\"><div class=\"meta-card\"><span>Order Number</span><strong>"
      + escapeHtml(String(order?.reference || order?.orderNumber || "-"))
      + "</strong></div><div class=\"meta-card\"><span>Status</span><strong>"
      + escapeHtml(String(order?.status || "Processing"))
      + "</strong></div><div class=\"meta-card\"><span>Billing Email</span><strong>"
      + escapeHtml(String(order?.email || shipping.email || "guest@girffon.com"))
      + "</strong></div><div class=\"meta-card\"><span>Shipping Address</span><strong>"
      + escapeHtml(shippingParts.join(", ") || "Address provided during checkout")
      + "</strong></div></div><table><thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr></thead><tbody>"
      + lineItemsMarkup
      + "</tbody></table><div class=\"total\"><div class=\"total-card\"><span>Total Amount</span><strong>"
      + escapeHtml(formatMoney(order?.total || 0))
      + "</strong></div></div><div class=\"note\">Use your browser print dialog to save this invoice as PDF.</div></main>"
      + (autoPrint ? "<script>window.onload=function(){window.print();};<\/script>" : "")
      + "</body></html>";
  }

  function openInvoicePreview(order, index, autoPrint) {
    const invoiceWindow = window.open("", "_blank", "width=980,height=820,noopener");
    if (!invoiceWindow) {
      showToast("Popup blocked. Please allow popups to open invoices.");
      return false;
    }

    invoiceWindow.document.open();
    invoiceWindow.document.write(buildInvoiceDocument(order, index, autoPrint));
    invoiceWindow.document.close();
    invoiceWindow.focus();
    return true;
  }

  function openInvoicesList(orders) {
    const invoiceEntries = orders.slice(0, 12).map(function (order, index) {
      return {
        id: "invoice-" + index,
        invoiceNumber: getInvoiceNumber(order, index),
        reference: String(order?.reference || order?.orderNumber || "-"),
        dateLabel: formatDate(order?.createdAt),
        amountLabel: formatMoney(order?.total || 0),
        customerName: String(order?.customerName || "GirffoN Customer"),
        documentHtml: buildInvoiceDocument(order, index, false),
        printHtml: buildInvoiceDocument(order, index, true)
      };
    });
    const popup = window.open("", "_blank", "width=1040,height=840,noopener");

    if (!popup) {
      showToast("Popup blocked. Please allow popups to open invoices.");
      return;
    }

    popup.document.open();
    popup.document.write("<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>GirffoN Invoices</title><style>body{font-family:Georgia,serif;background:#f7f1e8;color:#33251c;padding:28px}main{max-width:980px;margin:0 auto;background:#fffdf8;border:1px solid #e8dcc3;border-radius:28px;padding:30px;box-shadow:0 20px 48px rgba(120,90,34,.12)}h1{margin:0 0 8px;font-size:32px}p{margin:0;color:#6c5947}.list{display:grid;gap:16px;margin-top:26px}.card{border:1px solid #eadfc8;border-radius:22px;padding:18px 20px;background:#fff}.top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.meta{display:grid;grid-template-columns:repeat(3,minmax(140px,1fr));gap:12px;margin-top:16px}.meta-item{border:1px solid #f0e6d4;border-radius:16px;padding:12px 14px;background:#fcf8f0}.meta-item span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.12em;color:#8b7355;margin-bottom:7px}.meta-item strong{font-size:16px;color:#2f241a}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.actions button{appearance:none;border:1px solid #ccb37a;border-radius:999px;padding:10px 16px;background:#fff;color:#4d3a20;font:inherit;font-weight:700;cursor:pointer}.actions button.primary{background:linear-gradient(135deg,#d7b15a,#ba8a24);color:#251708;border-color:transparent}</style></head><body><main><h1>GirffoN Invoices</h1><p>Your stored invoices are ready to preview or save as PDF.</p><div class=\"list\">"
      + invoiceEntries.map(function (entry) {
          return "<article class=\"card\"><div class=\"top\"><div><div style=\"font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:#8b6b28;margin-bottom:10px;\">Invoice</div><h2 style=\"margin:0;font-size:26px;\">" + escapeHtml(entry.invoiceNumber) + "</h2><p style=\"margin-top:10px;\">Prepared for " + escapeHtml(entry.customerName) + "</p></div><div style=\"text-align:right;color:#6c5947;\">" + escapeHtml(entry.dateLabel) + "</div></div><div class=\"meta\"><div class=\"meta-item\"><span>Order Number</span><strong>" + escapeHtml(entry.reference) + "</strong></div><div class=\"meta-item\"><span>Date</span><strong>" + escapeHtml(entry.dateLabel) + "</strong></div><div class=\"meta-item\"><span>Total Amount</span><strong>" + escapeHtml(entry.amountLabel) + "</strong></div></div><div class=\"actions\"><button type=\"button\" class=\"primary\" data-doc-id=\"" + escapeHtml(entry.id) + "\" data-auto-print=\"false\">Open Invoice</button><button type=\"button\" data-doc-id=\"" + escapeHtml(entry.id) + "\" data-auto-print=\"true\">Download PDF</button></div></article>";
        }).join("")
      + "</div><script>const invoiceDocs=" + JSON.stringify(invoiceEntries.reduce(function (bucket, entry) {
          bucket[entry.id] = { preview: entry.documentHtml, print: entry.printHtml };
          return bucket;
        }, {}))
      + ";document.addEventListener('click',function(event){const button=event.target.closest('[data-doc-id]');if(!button){return;}const docId=button.getAttribute('data-doc-id');const autoPrint=button.getAttribute('data-auto-print')==='true';const payload=invoiceDocs[docId];if(!payload){return;}const child=window.open('', '_blank', 'width=980,height=820,noopener');if(!child){window.alert('Popup blocked. Please allow popups to open invoices.');return;}child.document.open();child.document.write(autoPrint ? payload.print : payload.preview);child.document.close();child.focus();});<\/script></main></body></html>");
    popup.document.close();
    popup.focus();
  }

  function getLatestStoredOrder() {
    const orders = readOrders();
    return orders.length ? orders[0] : null;
  }

  function openTrackPanelForOrder(order) {
    const trackTrigger = document.getElementById("gfTrackTrigger");
    const trackForm = document.getElementById("gfTrackForm");
    const trackOrderNumber = document.getElementById("gfTrackOrderNumber");
    const trackEmail = document.getElementById("gfTrackEmail");

    if (!trackTrigger || !trackForm || !trackOrderNumber || !trackEmail) {
      showToast("Order tracking is not available right now.");
      return;
    }

    const targetOrder = order || getLatestStoredOrder();
    if (!targetOrder) {
      showToast("No stored GirffoN order yet. Complete checkout first.");
      return;
    }

    trackTrigger.click();
    window.setTimeout(function () {
      trackOrderNumber.value = targetOrder.reference || targetOrder.orderNumber || "";
      trackEmail.value = targetOrder.email || "guest@girffon.com";
      if (typeof trackForm.requestSubmit === "function") {
        trackForm.requestSubmit();
      } else {
        trackForm.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
      }
    }, 0);
  }

  function getCurrentCountry() {
    return localStorage.getItem(STORAGE_KEY) || DEFAULT_COUNTRY;
  }

  function getDefaultCountryLabel() {
    const labels = {
      IT: "Italy",
      DE: "Germany",
      FR: "France",
      ES: "Spain",
      NL: "Netherlands",
      PL: "Poland",
      SE: "Sweden",
      GB: "United Kingdom",
      US: "United States",
      CH: "Switzerland",
      CA: "Canada"
    };

    return labels[getCurrentCountry()] || "United Kingdom";
  }

  function getDefaultLanguageLabel() {
    const labels = {
      "en-GB": "English",
      "en-US": "English",
      "en-CA": "English",
      "it-IT": "Italiano",
      "de-DE": "Deutsch",
      "fr-FR": "Français",
      "es-ES": "Español",
      "nl-NL": "English",
      "pl-PL": "English",
      "sv-SE": "English",
      "de-CH": "Deutsch"
    };

    return labels[getLocaleCode()] || "English";
  }

  function getCurrencyCode() {
    const country = getCurrentCountry();
    const currencyMap = {
      GB: "GBP",
      US: "USD",
      CA: "CAD",
      CH: "CHF",
      SE: "SEK",
      PL: "PLN"
    };

    return currencyMap[country] || "EUR";
  }

  function formatMoney(value) {
    const amount = Number(value);
    const safeAmount = Number.isFinite(amount) ? amount : 0;
    try {
      return new Intl.NumberFormat(getLocaleCode(), {
        style: "currency",
        currency: getCurrencyCode(),
        maximumFractionDigits: 2
      }).format(safeAmount);
    } catch (error) {
      return getCurrencyCode() + safeAmount.toFixed(2);
    }
  }

  function formatDate(value) {
    if (!value) return "-";
    const parsedDate = new Date(value);
    if (Number.isNaN(parsedDate.getTime())) return "-";
    try {
      return new Intl.DateTimeFormat(getLocaleCode(), {
        day: "2-digit",
        month: "short",
        year: "numeric"
      }).format(parsedDate);
    } catch (error) {
      return parsedDate.toISOString().slice(0, 10);
    }
  }

  function getPaymentCardholderName(method) {
    const methodName = String(method?.cardholder || method?.name || "").trim();
    if (methodName) {
      return methodName;
    }

    const savedProfile = readProfileState() || {};
    const fallbackName = [savedProfile.firstName, savedProfile.lastName].filter(Boolean).join(" ").trim();
    return fallbackName || "Primary account holder";
  }

  function getPrimaryAddressRecord(profile) {
    const safeProfile = profile || readProfileState() || {};
    const fullName = [safeProfile.firstName, safeProfile.lastName].filter(Boolean).join(" ").trim() || "Primary Recipient";
    const phone = String(safeProfile.phone || "").trim() || "Add your phone number";
    const country = String(safeProfile.country || "").trim() || getDefaultCountryLabel();
    const city = String(safeProfile.city || "").trim() || "Add your city";
    const postalCode = String(safeProfile.postalCode || "").trim() || "Add your postal code";
    const fullAddress = String(safeProfile.fullAddress || "").trim() || "Save a delivery address to complete your local profile.";

    return {
      label: fullName,
      phone: phone,
      country: country,
      city: city,
      postalCode: postalCode,
      fullAddress: fullAddress
    };
  }

  function promptForValue(message, defaultValue) {
    const result = window.prompt(message, String(defaultValue || ""));
    return result == null ? null : String(result).trim();
  }

  function promptForAddressRecord(initial, options) {
    const source = initial || {};
    const settings = options || {};
    const label = settings.includeLabel
      ? promptForValue("Address label", source.label || "Additional Address")
      : String(source.label || "").trim();
    if (settings.includeLabel && label == null) return null;

    const phone = promptForValue("Phone Number", source.phone || "");
    if (phone == null) return null;
    const country = promptForValue("Country", source.country || "");
    if (country == null) return null;
    const city = promptForValue("City", source.city || "");
    if (city == null) return null;
    const postalCode = promptForValue("Postal Code", source.postalCode || "");
    if (postalCode == null) return null;
    const fullAddress = promptForValue("Full Address", source.fullAddress || "");
    if (fullAddress == null) return null;

    return {
      ...source,
      label: settings.includeLabel ? (label || source.label || "Additional Address") : (source.label || "Primary Recipient"),
      phone: phone || source.phone || "Add phone number",
      country: country || source.country || getDefaultCountryLabel(),
      city: city || source.city || "Add your city",
      postalCode: postalCode || source.postalCode || "Add your postal code",
      fullAddress: fullAddress || source.fullAddress || "Save a delivery address to complete your local profile."
    };
  }

  function writePrimaryAddressToProfile(address) {
    const existingProfile = {
      ...defaultProfileState,
      ...(readProfileState() || {})
    };
    const nextProfile = {
      ...existingProfile,
      phone: address.phone,
      country: address.country,
      city: address.city,
      postalCode: address.postalCode,
      fullAddress: address.fullAddress
    };

    writeProfileState(nextProfile);
    setFormValues(nextProfile);
    updateProfileIdentity(nextProfile);
    renderProfileCompletion(nextProfile);
  }

  function normalizeExtraAddresses(list) {
    return (Array.isArray(list) ? list : []).filter(function (item) {
      return item && typeof item === "object";
    }).map(function (item, index) {
      return {
        id: String(item.id || ("address-" + index)),
        label: String(item.label || ("Additional Address " + (index + 1))).trim() || ("Additional Address " + (index + 1)),
        phone: String(item.phone || "Add backup phone").trim() || "Add backup phone",
        country: String(item.country || "Add backup country").trim() || "Add backup country",
        city: String(item.city || "Add backup city").trim() || "Add backup city",
        postalCode: String(item.postalCode || "Add backup postal code").trim() || "Add backup postal code",
        fullAddress: String(item.fullAddress || "Use this space for a second delivery location when needed.").trim() || "Use this space for a second delivery location when needed.",
        chipLabel: String(item.chipLabel || "Optional").trim() || "Optional"
      };
    });
  }

  function buildExtraAddressMarkup(address, index) {
    return ''
      + '<article class="gf-account-address" data-gf-extra-address="true" data-gf-address-id="' + escapeHtml(address.id) + '">'
      + '<div class="gf-account-address-top"><div><h4>' + escapeHtml(address.label) + '</h4><p>Secondary Shipping Address</p></div><span class="gf-account-chip">' + escapeHtml(address.chipLabel) + '</span></div>'
      + '<div class="gf-account-address-meta">'
      + '<div class="gf-account-address-row"><span>Phone Number</span><strong>' + escapeHtml(address.phone) + '</strong></div>'
      + '<div class="gf-account-address-row"><span>Country</span><strong>' + escapeHtml(address.country) + '</strong></div>'
      + '<div class="gf-account-address-row"><span>City</span><strong>' + escapeHtml(address.city) + '</strong></div>'
      + '<div class="gf-account-address-row"><span>Postal Code</span><strong>' + escapeHtml(address.postalCode) + '</strong></div>'
      + '</div>'
      + '<p class="gf-account-address-full">' + escapeHtml(address.fullAddress) + '</p>'
        + '<div class="gf-account-address-actions"><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="edit-extra-address" data-gf-address-id="' + escapeHtml(address.id) + '">Edit</button><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="set-default-address" data-gf-address-id="' + escapeHtml(address.id) + '">Set Default</button><button type="button" class="gf-account-btn gf-account-btn-danger" data-gf-action="delete-extra-address" data-gf-address-id="' + escapeHtml(address.id) + '">Delete</button></div>'
      + '</article>';
  }

  function renderAddressBook() {
    if (!addressBook) {
      return;
    }

    const primaryAddress = addressBook.querySelector('.gf-account-address');
    const extraAddresses = normalizeExtraAddresses(readExtraAddresses());

    addressBook.querySelectorAll('.gf-account-address[data-gf-extra-address="true"]').forEach(function (node) {
      node.remove();
    });

    if (primaryAddress) {
      const primaryRecord = getPrimaryAddressRecord();
      if (primaryAddressName) {
        primaryAddressName.textContent = primaryRecord.label;
      }
      if (primaryAddressPhone) {
        primaryAddressPhone.textContent = primaryRecord.phone;
      }
      if (primaryAddressCountry) {
        primaryAddressCountry.textContent = primaryRecord.country;
      }
      if (primaryAddressCity) {
        primaryAddressCity.textContent = primaryRecord.city;
      }
      if (primaryAddressPostal) {
        primaryAddressPostal.textContent = primaryRecord.postalCode;
      }
      if (primaryAddressFull) {
        primaryAddressFull.textContent = primaryRecord.fullAddress;
      }
    }

    extraAddresses.forEach(function (address, index) {
      const wrapper = document.createElement('div');
      wrapper.dataset.gfExtraAddress = 'true';
      wrapper.innerHTML = buildExtraAddressMarkup(address, index);
      const article = wrapper.firstElementChild;
      if (article) {
        article.dataset.gfExtraAddress = 'true';
        addressBook.appendChild(article);
      }
    });

    if (addressCount) {
      addressCount.textContent = String(1 + extraAddresses.length);
    }
  }

  function addNewAddressCard() {
    const existing = normalizeExtraAddresses(readExtraAddresses());
    const nextIndex = existing.length + 1;
    const initialRecord = {
      id: 'address-' + Date.now().toString(36),
      label: nextIndex === 1 ? 'Optional Secondary Address' : ('Additional Address ' + nextIndex),
      phone: 'Add backup phone',
      country: 'Add backup country',
      city: 'Add backup city',
      postalCode: 'Add backup postal code',
      fullAddress: 'Use this space for a second delivery location when needed.',
      chipLabel: 'Optional'
    };
    const nextAddress = promptForAddressRecord(initialRecord, { includeLabel: true });
    if (!nextAddress) {
      return;
    }
    existing.push(nextAddress);
    writeExtraAddresses(existing);
    renderAddressBook();
    showToast(currentRuntimeTexts.draftSavedToast);
  }

  function editPrimaryAddress() {
    const nextAddress = promptForAddressRecord(getPrimaryAddressRecord(), { includeLabel: false });
    if (!nextAddress) {
      return;
    }
    writePrimaryAddressToProfile(nextAddress);
    applyPersistedProfileState();
    syncConnectedSections();
    showToast(currentRuntimeTexts.profileSavedToast);
  }

  function duplicatePrimaryAddress() {
    const existing = normalizeExtraAddresses(readExtraAddresses());
    const primary = getPrimaryAddressRecord();
    existing.push({
      id: 'address-' + Date.now().toString(36),
      label: 'Copied Address ' + (existing.length + 1),
      phone: primary.phone,
      country: primary.country,
      city: primary.city,
      postalCode: primary.postalCode,
      fullAddress: primary.fullAddress,
      chipLabel: 'Optional'
    });
    writeExtraAddresses(existing);
    renderAddressBook();
    showToast(currentRuntimeTexts.draftSavedToast);
  }

  function editExtraAddress(addressId) {
    const existing = normalizeExtraAddresses(readExtraAddresses());
    const index = existing.findIndex(function (item) {
      return item.id === addressId;
    });
    if (index < 0) {
      return;
    }

    const nextAddress = promptForAddressRecord(existing[index], { includeLabel: true });
    if (!nextAddress) {
      return;
    }

    existing[index] = nextAddress;
    writeExtraAddresses(existing);
    renderAddressBook();
    showToast(currentRuntimeTexts.profileSavedToast);
  }

  function setDefaultAddress(addressId) {
    const existing = normalizeExtraAddresses(readExtraAddresses());
    const index = existing.findIndex(function (item) {
      return item.id === addressId;
    });
    if (index < 0) {
      return;
    }

    const nextPrimary = existing[index];
    const currentPrimary = getPrimaryAddressRecord();
    const remaining = existing.filter(function (_item, itemIndex) {
      return itemIndex !== index;
    });

    remaining.unshift({
      id: 'address-' + Date.now().toString(36),
      label: currentPrimary.label || 'Previous Primary Address',
      phone: currentPrimary.phone,
      country: currentPrimary.country,
      city: currentPrimary.city,
      postalCode: currentPrimary.postalCode,
      fullAddress: currentPrimary.fullAddress,
      chipLabel: 'Optional'
    });

    writePrimaryAddressToProfile(nextPrimary);
    writeExtraAddresses(remaining);
    applyPersistedProfileState();
    syncConnectedSections();
    showToast(currentRuntimeTexts.profileSavedToast);
  }

  function deleteExtraAddress(addressId) {
    const existing = normalizeExtraAddresses(readExtraAddresses());
    const nextAddresses = existing.filter(function (item) {
      return item.id !== addressId;
    });

    if (nextAddresses.length === existing.length) {
      return;
    }

    writeExtraAddresses(nextAddresses);
    renderAddressBook();
    showToast(currentRuntimeTexts.draftRevertedToast);
  }

  function normalizePaymentMethods(list) {
    return (Array.isArray(list) ? list : []).filter(function (item) {
      return item && typeof item === "object";
    }).map(function (item, index) {
      const brand = String(item.brand || item.network || item.type || "Card").trim() || "Card";
      const last4Raw = String(item.last4 || item.lastDigits || item.cardLast4 || item.maskedNumber || "").replace(/\D/g, "");
      const last4 = last4Raw.slice(-4);
      const expiry = String(item.expiry || item.expires || item.expiryDate || "").trim();
      const billingLabel = String(item.billingLabel || item.billingMethod || item.label || "Personal Card").trim() || "Personal Card";
      const chipLabel = String(item.chipLabel || item.category || (item.isPrimary ? "Primary" : "Saved")).trim() || (item.isPrimary ? "Primary" : "Saved");

      if (!last4 && !expiry && !String(item.cardholder || item.name || "").trim()) {
        return null;
      }

      return {
        id: String(item.id || ("payment-" + index)),
        brand: brand.toUpperCase(),
        title: brand + (last4 ? (" ending " + last4) : ""),
        cardholder: getPaymentCardholderName(item),
        expiry: expiry || "--/--",
        billingLabel: billingLabel,
        chipLabel: chipLabel,
        isPrimary: Boolean(item.isPrimary || index === 0)
      };
    }).filter(Boolean);
  }

  function promptForPaymentMethod(initial) {
    const source = initial || {};
    const brand = promptForValue("Card brand", source.brand || "Visa");
    if (brand == null) return null;
    const last4 = promptForValue("Last 4 digits", source.last4 || source.title || "4242");
    if (last4 == null) return null;
    const expiry = promptForValue("Expiry (MM/YY)", source.expiry || "11/28");
    if (expiry == null) return null;
    const billingLabel = promptForValue("Billing label", source.billingLabel || "Personal Card");
    if (billingLabel == null) return null;

    const cardholder = getPaymentCardholderName(source);
    const cleanLast4 = String(last4).replace(/\D/g, "").slice(-4) || "4242";

    return {
      ...source,
      id: source.id || ('payment-' + Date.now().toString(36)),
      brand: brand,
      last4: cleanLast4,
      expiry: expiry || source.expiry || '--/--',
      billingLabel: billingLabel || source.billingLabel || 'Personal Card',
      cardholder: cardholder,
      isPrimary: Boolean(source.isPrimary)
    };
  }

  function addNewPaymentMethod() {
    const existing = normalizePaymentMethods(readPaymentMethods());
    const nextMethod = promptForPaymentMethod({
      brand: existing.length ? 'Mastercard' : 'Visa',
      last4: String(4000 + existing.length + 1),
      expiry: '11/28',
      billingLabel: existing.length ? 'Business Billing' : 'Personal Card',
      cardholder: getPaymentCardholderName({})
    });
    if (!nextMethod) {
      return;
    }

    const payload = existing.map(function (item) {
      return {
        ...item,
        isPrimary: item.isPrimary && existing.length > 0
      };
    });

    payload.push({
      ...nextMethod,
      isPrimary: !payload.length
    });
    writePaymentMethods(payload);
    renderPaymentMethods();
    showToast(currentRuntimeTexts.draftSavedToast);
  }

  function editPaymentMethod(paymentId) {
    const existing = normalizePaymentMethods(readPaymentMethods());
    const index = existing.findIndex(function (item) {
      return item.id === paymentId;
    });
    if (index < 0) {
      return;
    }

    const nextMethod = promptForPaymentMethod(existing[index]);
    if (!nextMethod) {
      return;
    }

    existing[index] = {
      ...existing[index],
      ...nextMethod,
      isPrimary: existing[index].isPrimary
    };
    writePaymentMethods(existing);
    renderPaymentMethods();
    showToast(currentRuntimeTexts.profileSavedToast);
  }

  function setPrimaryPaymentMethod(paymentId) {
    const existing = normalizePaymentMethods(readPaymentMethods());
    if (!existing.length) {
      addNewPaymentMethod();
      return;
    }

    let didChange = false;
    const nextMethods = existing.map(function (item) {
      const isPrimary = item.id === paymentId;
      if (item.isPrimary !== isPrimary) {
        didChange = true;
      }
      return {
        ...item,
        isPrimary: isPrimary,
        chipLabel: isPrimary ? 'Primary' : 'Saved'
      };
    });

    writePaymentMethods(nextMethods);
    renderPaymentMethods();
    showToast(didChange ? "Primary payment method updated" : "This card is already the default.");
  }

  function renderPaymentMethods() {
    if (!paymentGrid) {
      return;
    }

    const methods = normalizePaymentMethods(readPaymentMethods());

    if (paymentCount) {
      paymentCount.textContent = String(methods.length);
    }

    if (addPaymentButton) {
      addPaymentButton.innerHTML = methods.length
        ? '<i class="fa-solid fa-plus"></i> Add New Payment'
        : '<i class="fa-solid fa-plus"></i> Add your first card';
    }

    if (!methods.length) {
      paymentGrid.innerHTML = ''
        + '<article class="gf-account-payment-card gf-account-payment-card-featured">'
        + '<div class="gf-account-payment-visual"><span class="gf-account-payment-brand">CARD</span><span class="gf-account-payment-lock"><i class="fa-solid fa-lock"></i> Secure</span></div>'
        + '<div class="gf-account-payment-top"><strong>No payment methods yet</strong><span class="gf-account-chip">Empty</span></div>'
        + '<div class="gf-account-payment-meta">'
        + '<div class="gf-account-payment-row"><span>Cardholder</span><strong>-</strong></div>'
        + '<div class="gf-account-payment-row"><span>Expires</span><strong>-</strong></div>'
        + '<div class="gf-account-payment-row"><span>Billing Method</span><strong>Add your first card</strong></div>'
        + '</div>'
        + '<div class="gf-account-address-actions"><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="add-payment">Add your first card</button></div>'
        + '</article>';
      return;
    }

    paymentGrid.innerHTML = methods.map(function (method, index) {
      const visualClass = method.isPrimary ? 'gf-account-payment-visual' : 'gf-account-payment-visual gf-account-payment-visual-soft';
      const lockIcon = method.isPrimary ? 'fa-lock' : 'fa-shield-halved';
      const lockLabel = method.isPrimary ? 'Secure' : 'Verified';
      const articleClass = method.isPrimary
        ? 'gf-account-payment-card gf-account-payment-card-featured'
        : 'gf-account-payment-card';
      const secondaryAction = method.isPrimary ? 'Set Billing' : 'Use for Orders';

      return ''
        + '<article class="' + articleClass + '">'
        + '<div class="' + visualClass + '"><span class="gf-account-payment-brand">' + escapeHtml(method.brand) + '</span><span class="gf-account-payment-lock"><i class="fa-solid ' + lockIcon + '"></i> ' + lockLabel + '</span></div>'
        + '<div class="gf-account-payment-top"><strong>' + escapeHtml(method.title) + '</strong><span class="gf-account-chip">' + escapeHtml(method.chipLabel) + '</span></div>'
        + '<div class="gf-account-payment-meta">'
        + '<div class="gf-account-payment-row"><span>Cardholder</span><strong>' + escapeHtml(method.cardholder) + '</strong></div>'
        + '<div class="gf-account-payment-row"><span>Expires</span><strong>' + escapeHtml(method.expiry) + '</strong></div>'
        + '<div class="gf-account-payment-row"><span>Billing Method</span><strong>' + escapeHtml(method.billingLabel) + '</strong></div>'
        + '</div>'
        + '<div class="gf-account-address-actions"><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="edit-payment" data-gf-payment-id="' + escapeHtml(method.id) + '">Edit Card</button><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="set-primary-payment" data-gf-payment-id="' + escapeHtml(method.id) + '">' + escapeHtml(secondaryAction) + '</button><button type="button" class="gf-account-btn gf-account-btn-danger" data-gf-action="delete-payment" data-gf-payment-id="' + escapeHtml(method.id) + '">Delete</button></div>'
        + '</article>';
    }).join('');
  }

  function deletePaymentMethod(paymentId) {
    const existing = normalizePaymentMethods(readPaymentMethods());
    const target = existing.find(function (item) {
      return item.id === paymentId;
    });
    if (!target) {
      return;
    }

    let nextMethods = existing.filter(function (item) {
      return item.id !== paymentId;
    });

    if (target.isPrimary && nextMethods.length) {
      nextMethods = nextMethods.map(function (item, index) {
        return {
          ...item,
          isPrimary: index === 0,
          chipLabel: index === 0 ? 'Primary' : 'Saved'
        };
      });
    }

    writePaymentMethods(nextMethods);
    renderPaymentMethods();
    showToast(currentRuntimeTexts.draftRevertedToast);
  }

  function isRealCheckoutOrder(order) {
    if (!order || typeof order !== "object") {
      return false;
    }

    const reference = String(order.reference || order.orderNumber || "").trim();
    const createdAt = String(order.createdAt || "").trim();
    const total = Number(order.total);
    const items = Array.isArray(order.items) ? order.items : [];
    const hasValidReference = /^GF-\d{8}-\d{6}$/i.test(reference);
    const hasValidDate = !Number.isNaN(new Date(createdAt).getTime());
    const hasValidTotal = Number.isFinite(total) && total > 0;
    const hasValidItems = items.some(function (item) {
      if (!item || typeof item !== "object") {
        return false;
      }

      const title = String(item.title || item.name || item.code || "").trim();
      const quantity = Number(item.qty || item.quantity || 0);
      const price = Number(item.price || item.total || 0);
      return !!title && quantity > 0 && price >= 0;
    });

    return hasValidReference && hasValidDate && hasValidTotal && hasValidItems;
  }

  function getItemId(item, fallbackIndex) {
    return String(
      item?.id
      || item?.productId
      || item?.code
      || item?.sku
      || item?.slug
      || item?.name
      || item?.title
      || fallbackIndex
    );
  }

  function getItemTitle(item) {
    return item?.title || item?.name || item?.productName || item?.code || item?.sku || "Saved Item";
  }

  function isUsableImageValue(value) {
    return typeof value === "string" && value.trim() !== "";
  }

  function normalizeImageValue(value) {
    if (!isUsableImageValue(value)) {
      return "";
    }

    return String(value).trim().replace(/\\/g, "/");
  }

  function buildProductSizeVariants(value) {
    const normalized = normalizeImageValue(value);
    if (!normalized || /^data:image\//i.test(normalized) || /^blob:/i.test(normalized)) {
      return normalized ? [normalized] : [];
    }

    const variants = [normalized];
    if (/\/160(\.[a-z0-9]+)$/i.test(normalized)) {
      variants.unshift(normalized.replace(/\/160(\.[a-z0-9]+)$/i, "/400$1"));
    } else if (/\/400(\.[a-z0-9]+)$/i.test(normalized)) {
      variants.push(normalized.replace(/\/400(\.[a-z0-9]+)$/i, "/160$1"));
    }

    return variants;
  }

  function collectImageFieldValues(source, fieldName, bucket, visited, depth) {
    if (!source || depth > 4) {
      return;
    }

    if (typeof source !== "object") {
      return;
    }

    if (visited.has(source)) {
      return;
    }
    visited.add(source);

    if (Array.isArray(source)) {
      source.forEach(function (entry) {
        collectImageFieldValues(entry, fieldName, bucket, visited, depth + 1);
      });
      return;
    }

    if (Object.prototype.hasOwnProperty.call(source, fieldName)) {
      const fieldValue = source[fieldName];
      if (Array.isArray(fieldValue)) {
        fieldValue.forEach(function (entry) {
          if (isUsableImageValue(entry)) {
            bucket.push(entry);
          }
        });
      } else if (isUsableImageValue(fieldValue)) {
        bucket.push(fieldValue);
      }
    }

    Object.keys(source).forEach(function (key) {
      const value = source[key];
      if (value && typeof value === "object") {
        collectImageFieldValues(value, fieldName, bucket, visited, depth + 1);
      }
    });
  }

  function getImageCandidates(source) {
    const fieldPriority = [
      "thumbnail",
      "thumbnails",
      "preview",
      "previews",
      "previewImage",
      "productPreview",
      "frontImage",
      "designPreview",
      "canvasPreview",
      "productImage",
      "image",
      "images",
      "imageUrl",
      "img",
      "photo",
      "gallery"
    ];
    const visited = new Set();
    const candidates = [];
    const seen = new Set();

    fieldPriority.forEach(function (fieldName) {
      const values = [];
      collectImageFieldValues(source, fieldName, values, visited, 0);
      values.forEach(function (value) {
        buildProductSizeVariants(value).forEach(function (variant) {
          const normalized = normalizeImageValue(variant);
          if (!normalized || seen.has(normalized)) {
            return;
          }
          seen.add(normalized);
          candidates.push(normalized);
        });
      });
    });

    return candidates;
  }

  function getItemImage(item) {
    const candidates = getImageCandidates(item);
    return candidates[0] || "Image/Logo/Logo.png";
  }

  function getWishlistImageCandidates(item) {
    const candidates = [];
    const seen = new Set();

    [item?.image, item?.img].forEach(function (value) {
      const normalized = normalizeImageValue(value);
      if (!normalized || seen.has(normalized)) {
        return;
      }
      seen.add(normalized);
      candidates.push(normalized);
    });

    getImageCandidates(item).forEach(function (value) {
      const normalized = normalizeImageValue(value);
      if (!normalized || seen.has(normalized)) {
        return;
      }
      seen.add(normalized);
      candidates.push(normalized);
    });

    if (!candidates.length) {
      candidates.push("Cart/products/tshirt-men/Men france/Bk/80.jpg");
    }

    return candidates;
  }

  function buildImageFallbackAttribute(candidates) {
    return encodeURIComponent(JSON.stringify(candidates || []));
  }

  function attachImageFallbackHandlers(root) {
    if (!root) {
      return;
    }

    root.querySelectorAll("[data-gf-image-fallbacks]").forEach(function (img) {
      if (img.dataset.gfImageFallbackBound === "true") {
        return;
      }

      img.dataset.gfImageFallbackBound = "true";
      img.addEventListener("error", function handleImageError() {
        let fallbacks = [];
        try {
          fallbacks = JSON.parse(decodeURIComponent(img.getAttribute("data-gf-image-fallbacks") || "%5B%5D"));
        } catch (_error) {
          fallbacks = [];
        }

        const currentIndex = Number(img.dataset.gfImageIndex || "0");
        const nextIndex = currentIndex + 1;
        if (nextIndex < fallbacks.length) {
          img.dataset.gfImageIndex = String(nextIndex);
          img.src = fallbacks[nextIndex];
          return;
        }

        if (img.dataset.gfImageMode === "design") {
          img.style.display = "none";
          return;
        }

        img.src = "Image/Logo/Logo.png";
      });
    });
  }

  function buildOrderPreviewImage(order, fallbackIndex) {
    const primaryItem = Array.isArray(order?.items) && order.items.length ? order.items[0] : null;
    const candidates = getImageCandidates(primaryItem || order);
    if (!candidates.length) {
      return "";
    }

    return '<img class="gf-account-saved-image" style="height:210px;" src="'
      + escapeHtml(candidates[0])
      + '" alt="'
      + escapeHtml(getItemTitle(primaryItem || order) || ('Order ' + (fallbackIndex + 1)))
      + '" data-gf-image-mode="order" data-gf-image-index="0" data-gf-image-fallbacks="'
      + escapeHtml(buildImageFallbackAttribute(candidates))
      + '">';
  }

  function buildDesignPreviewMarkup(project, thumbClass, fallbackIndex) {
    const candidates = getImageCandidates(project);
    const overlay = candidates.length
      ? '<img class="gf-account-saved-image" style="position:absolute;inset:0;height:100%;z-index:2;" src="'
        + escapeHtml(candidates[0])
        + '" alt="'
        + escapeHtml(project.projectName || ('Design ' + (fallbackIndex + 1)))
        + '" data-gf-image-mode="design" data-gf-image-index="0" data-gf-image-fallbacks="'
        + escapeHtml(buildImageFallbackAttribute(candidates))
        + '">'
      : '';

    return '<div class="gf-account-design-thumb ' + thumbClass + '" aria-hidden="true">'
      + overlay
      + '<span class="gf-account-design-shirt"></span><span class="gf-account-design-mark"></span></div>';
  }

  function parsePriceValue(value) {
    if (typeof value === "number") {
      return Number.isFinite(value) ? value : null;
    }

    if (typeof value !== "string") {
      return null;
    }

    const normalized = value.trim();
    if (!normalized) {
      return null;
    }

    const numeric = normalized
      .replace(/[^\d.,-]/g, "")
      .replace(/,(?=\d{1,2}$)/, ".")
      .replace(/,/g, "");
    const parsed = Number(numeric);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function getWishlistPriceDisplay(item) {
    const rawCandidates = [item?.price, item?.unitPrice, item?.amount, item?.total];
    for (let index = 0; index < rawCandidates.length; index += 1) {
      const value = rawCandidates[index];
      if (typeof value === "string" && /[€£$]/.test(value) && value.trim()) {
        return value.trim();
      }
    }

    const numericPrice = getItemPrice(item);
    if (numericPrice !== null) {
      return formatMoney(numericPrice);
    }

    return "Price unavailable";
  }

  function getItemPrice(item) {
    const candidates = [item?.priceNumber, item?.price, item?.unitPrice, item?.amount, item?.total];
    for (let index = 0; index < candidates.length; index += 1) {
      const amount = parsePriceValue(candidates[index]);
      if (amount !== null) return amount;
    }
    return null;
  }

  function getItemQuantity(item) {
    const quantity = Number(item?.quantity);
    return Number.isFinite(quantity) && quantity > 0 ? quantity : 1;
  }

  function getWishlistBadgeState(item) {
    const stockText = String(item?.stockStatus || item?.stock || item?.availability || "").toLowerCase();
    if (stockText.includes("low")) {
      return { className: "is-low-stock", label: "Low Stock" };
    }
    if (stockText.includes("out") || stockText.includes("sold")) {
      return { className: "is-out-stock", label: "Out of Stock" };
    }
    return { className: "is-in-stock", label: "In Stock" };
  }

  function buildCartItemFromWishlist(item) {
    return {
      ...item,
      id: item?.id || item?.productId || item?.code || item?.sku || String(Date.now()),
      title: getItemTitle(item),
      image: getItemImage(item),
      price: getItemPrice(item),
      quantity: getItemQuantity(item)
    };
  }

  function addWishlistItemToCart(itemId) {
    const wishlist = readWishlist();
    const targetItem = wishlist.find(function (item, index) {
      return getItemId(item, index) === itemId;
    });
    if (!targetItem) return;

    const cart = readCart();
    const incoming = buildCartItemFromWishlist(targetItem);
    const existingIndex = cart.findIndex(function (item, index) {
      return getItemId(item, index) === getItemId(incoming, index)
        || (String(getItemTitle(item)).toLowerCase() === String(getItemTitle(incoming)).toLowerCase()
          && String(item?.code || item?.sku || "").toLowerCase() === String(incoming?.code || incoming?.sku || "").toLowerCase());
    });

    if (existingIndex >= 0) {
      const currentQuantity = getItemQuantity(cart[existingIndex]);
      cart[existingIndex] = {
        ...cart[existingIndex],
        quantity: currentQuantity + getItemQuantity(incoming),
        price: getItemPrice(cart[existingIndex]) || getItemPrice(incoming),
        image: getItemImage(cart[existingIndex]) || getItemImage(incoming)
      };
    } else {
      cart.unshift(incoming);
    }

    writeCart(cart);
  }

  function removeWishlistItem(itemId) {
    const nextWishlist = readWishlist().filter(function (item, index) {
      return getItemId(item, index) !== itemId;
    });
    writeWishlist(nextWishlist);
  }

  function getSavedProjects() {
    const projects = [];

    for (let index = 0; index < localStorage.length; index += 1) {
      const key = localStorage.key(index);
      if (!key || !key.startsWith(SHARED_KEYS.cdpProjectPrefix)) continue;

      try {
        const projectPath = key.slice(SHARED_KEYS.cdpProjectPrefix.length);
        const rawValue = localStorage.getItem(key);
        if (!rawValue) continue;
        const parsed = JSON.parse(rawValue);
        const parts = projectPath.split("/").filter(Boolean);
        const fileName = parts.pop() || parsed?.projectName || "Untitled";
        const folder = parts.join("/");
        projects.push({
          path: projectPath,
          fileName,
          folder,
          projectName: parsed?.projectName || fileName,
          timestamp: parsed?.timestamp || parsed?.savedAt || parsed?.updatedAt || "",
          expiryDate: parsed?.expiryDate || "",
          layerCount: Array.isArray(parsed?.layers) ? parsed.layers.length : 0,
          note: parsed?.note || "",
          invoiceAttachments: normalizeInvoiceAttachments(parsed?.invoiceAttachments),
          thumbnail: parsed?.thumbnail || parsed?.preview || parsed?.previewImage || parsed?.productPreview || parsed?.frontImage || parsed?.designPreview || parsed?.canvasPreview || "",
          product: parsed?.product || {}
        });
      } catch (error) {
        continue;
      }
    }

    return projects.sort(function (left, right) {
      return new Date(right.timestamp || 0).getTime() - new Date(left.timestamp || 0).getTime();
    });
  }

  function normalizeInvoiceAttachments(list) {
    return Array.isArray(list)
      ? list.filter(function (item) {
          return item && typeof item.dataUrl === 'string' && item.dataUrl;
        }).map(function (item, index) {
          return {
            id: item.id || ('invoice-' + (index + 1)),
            name: item.name || ('Invoice file ' + (index + 1)),
            dataUrl: item.dataUrl
          };
        })
      : [];
  }

  function buildInvoiceAttachmentStrip(entity) {
    const attachments = normalizeInvoiceAttachments(entity && entity.invoiceAttachments);
    if (!attachments.length) {
      return '';
    }

    return ''
      + '<div style="display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 0;">'
      + attachments.slice(0, 2).map(function (attachment) {
          return ''
            + '<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border:1px solid rgba(148,163,184,0.28);border-radius:999px;background:rgba(255,255,255,0.84);max-width:100%;">'
            + '<img src="' + escapeHtml(attachment.dataUrl) + '" alt="' + escapeHtml(attachment.name) + '" style="width:32px;height:32px;border-radius:999px;object-fit:cover;display:block;">'
            + '<span style="font-size:12px;font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;display:block;">' + escapeHtml(attachment.name) + '</span>'
            + '</div>';
        }).join('')
      + '</div>';
  }

  function duplicateProject(projectPath) {
    const sourceKey = SHARED_KEYS.cdpProjectPrefix + projectPath;
    const rawValue = localStorage.getItem(sourceKey);
    if (!rawValue) return;

    try {
      const parsed = JSON.parse(rawValue);
      const parts = projectPath.split("/").filter(Boolean);
      const originalName = parts.pop() || parsed.projectName || "Untitled";
      const folder = parts.join("/");
      let nextName = originalName + " Copy";
      let nextPath = folder ? folder + "/" + nextName : nextName;
      let suffix = 2;

      while (localStorage.getItem(SHARED_KEYS.cdpProjectPrefix + nextPath)) {
        nextName = originalName + " Copy " + suffix;
        nextPath = folder ? folder + "/" + nextName : nextName;
        suffix += 1;
      }

      parsed.projectName = nextName;
      parsed.timestamp = new Date().toISOString();
      localStorage.setItem(SHARED_KEYS.cdpProjectPrefix + nextPath, JSON.stringify(parsed));
    } catch (error) {
      return;
    }
  }

  function deleteProject(projectPath) {
    localStorage.removeItem(SHARED_KEYS.cdpProjectPrefix + projectPath);
  }

  function getProjectEditorPage(projectPath) {
    try {
      const rawValue = localStorage.getItem(SHARED_KEYS.cdpProjectPrefix + projectPath);
      if (!rawValue) return CUSTOM_DESIGN_PAGE;
      const parsed = JSON.parse(rawValue);
      const pageHref = String(parsed?.product?.pageHref || parsed?.pageHref || "").trim();
      return pageHref || CUSTOM_DESIGN_PAGE;
    } catch (_error) {
      return CUSTOM_DESIGN_PAGE;
    }
  }

  function openDesignProject(projectPath) {
    const parts = projectPath.split("/").filter(Boolean);
    const projectName = parts.pop() || "Untitled";
    localStorage.setItem(SHARED_KEYS.cdpCurrentProject, projectName);
    localStorage.setItem(SHARED_KEYS.cdpCurrentFolder, parts.join("/"));
    localStorage.setItem(SHARED_KEYS.cdpPendingProjectPath, projectPath);
    window.location.href = getProjectEditorPage(projectPath);
  }

  function renderSummaryGrid() {
    if (!summaryGrid) return;

    const wishlist = readWishlist();
    const cart = readCart();
    const projects = getSavedProjects();

    summaryGrid.innerHTML = [
      {
        value: projects.length,
        label: "Saved custom designs ready to reorder"
      },
      {
        value: cart.reduce(function (total, item) { return total + getItemQuantity(item); }, 0),
        label: "CartTest items ready for checkout"
      },
      {
        value: wishlist.length,
        label: "Wishlist pieces waiting for restock"
      }
    ].map(function (entry) {
      return '<article class="gf-account-card"><strong>' + escapeHtml(entry.value) + '</strong><span>' + escapeHtml(entry.label) + '</span></article>';
    }).join("");
  }

  function renderOrdersGrid() {
    if (!ordersGrid) return;

    const orders = readOrders();
    if (orders.length) {
      ordersGrid.innerHTML = orders.slice(0, 4).map(function (order, index) {
        const status = String(order?.status || 'Processing').toLowerCase();
        const statusClass = status.includes('deliver') ? 'is-delivered' : (status.includes('process') ? 'is-progress' : 'is-progress');
        return ''
          + '<article class="gf-account-order-card">'
          + '<span class="gf-account-order-kicker">Recent Order</span>'
          + buildOrderPreviewImage(order, index)
          + '<div class="gf-account-order-top"><div><h4>' + escapeHtml(order.reference || 'GF-ORDER') + '</h4><p class="gf-account-order-meta">' + escapeHtml(formatDate(order.createdAt)) + '</p></div><span class="gf-account-order-status ' + statusClass + '">' + escapeHtml(order.status || 'Processing') + '</span></div>'
          + '<div class="gf-account-order-summary">'
          + '<div class="gf-account-order-detail"><span>Order Number</span><strong>' + escapeHtml(order.reference || '-') + '</strong></div>'
          + '<div class="gf-account-order-detail"><span>Date</span><strong>' + escapeHtml(formatDate(order.createdAt)) + '</strong></div>'
          + '<div class="gf-account-order-detail"><span>Total Amount</span><strong>' + escapeHtml(formatMoney(order.total || 0)) + '</strong></div>'
          + '</div>'
          + '<p>' + escapeHtml(((order.items || []).map(function (item) { return item.title || item.name || item.code || 'GirffoN item'; }).join(' · ')) || 'GirffoN order') + '</p>'
          + buildInvoiceAttachmentStrip(order)
          + '<div class="gf-account-order-line"><span>Status</span><strong>' + escapeHtml((order.items || []).length + ' line' + (((order.items || []).length === 1) ? '' : 's') + ' saved in local order history') + '</strong></div>'
            + '<div class="gf-account-address-actions"><button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="track-order" data-gf-order-reference="' + escapeHtml(order.reference || '') + '" data-gf-order-email="' + escapeHtml(order.email || 'guest@girffon.com') + '">View Details</button></div>'
          + '</article>';
      }).join('');
      attachImageFallbackHandlers(ordersGrid);
      return;
    }

    ordersGrid.innerHTML = ''
      + '<article class="gf-account-order-card">'
      + '<span class="gf-account-order-kicker">Recent Order</span>'
      + '<div class="gf-account-order-top"><div><h4>No orders yet</h4><p class="gf-account-order-meta">-</p></div><span class="gf-account-order-status is-progress">Awaiting Checkout</span></div>'
      + '<div class="gf-account-order-summary">'
      + '<div class="gf-account-order-detail"><span>Order Number</span><strong>-</strong></div>'
      + '<div class="gf-account-order-detail"><span>Date</span><strong>-</strong></div>'
      + '<div class="gf-account-order-detail"><span>Total Amount</span><strong>-</strong></div>'
      + '</div>'
      + '<p>Your completed orders will appear here after checkout.</p>'
      + '<div class="gf-account-order-line"><span>Status</span><strong>No completed orders recorded</strong></div>'
      + '<div class="gf-account-address-actions"><button type="button" class="gf-account-btn gf-account-btn-primary" data-gf-action="open-cart">View Details</button></div>'
      + '</article>';
  }

  function renderDesignsGrid() {
    if (!designsGrid) return;

    const projects = getSavedProjects();
    if (!projects.length) {
      designsGrid.innerHTML = ''
        + '<article class="gf-account-card">'
        + '<strong>0</strong>'
        + '<h4>Saved Studio</h4>'
        + '<p class="gf-account-note">No saved custom design projects were found yet. Open Custom Design Pro and save your first folder or project.</p>'
        + '<div class="gf-account-design-actions"><button type="button" class="gf-account-btn gf-account-btn-primary" data-gf-action="open-design-studio">Edit</button></div>'
        + '</article>';
      return;
    }

    designsGrid.innerHTML = projects.slice(0, 6).map(function (project, index) {
      const thumbClass = ["gf-account-design-thumb-one", "gf-account-design-thumb-two", "gf-account-design-thumb-three"][index % 3];
      const noteParts = [];
      if (project.folder) noteParts.push('Folder: ' + project.folder);
      noteParts.push('Saved on ' + formatDate(project.timestamp));
      if (project.layerCount) noteParts.push(project.layerCount + ' layers');
      if (project.invoiceAttachments && project.invoiceAttachments.length) noteParts.push(project.invoiceAttachments.length + ' invoice files');
      if (project.product?.color || project.product?.size) {
        noteParts.push([project.product?.color, project.product?.size].filter(Boolean).join(' / '));
      }

      return ''
        + '<article class="gf-account-design-card">'
        + buildDesignPreviewMarkup(project, thumbClass, index)
        + '<div class="gf-account-design-body">'
        + '<h4>' + escapeHtml(project.projectName) + '</h4>'
        + '<p class="gf-account-note">' + escapeHtml(noteParts.join(' · ')) + '</p>'
        + buildInvoiceAttachmentStrip(project)
        + '<div class="gf-account-design-actions">'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="edit-design" data-gf-path="' + escapeHtml(project.path) + '">Edit</button>'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="duplicate-design" data-gf-path="' + escapeHtml(project.path) + '">Duplicate</button>'
        + '<button type="button" class="gf-account-btn gf-account-btn-danger" data-gf-action="delete-design" data-gf-path="' + escapeHtml(project.path) + '">Delete</button>'
        + '</div>'
        + '</div>'
        + '</article>';
    }).join("");
    attachImageFallbackHandlers(designsGrid);
  }

  function renderSavedGrid() {
    if (!savedGrid) return;

    const wishlist = readWishlist();
    if (!wishlist.length) {
      savedGrid.innerHTML = ''
        + '<article class="gf-account-card">'
        + '<strong>0</strong>'
        + '<h4>Wishlist and Saved Items</h4>'
        + '<p class="gf-account-note">Your shared wishlist is empty. Save products from the catalog to keep them here and move them into CartTest later.</p>'
        + '<div class="gf-account-saved-actions"><button type="button" class="gf-account-btn gf-account-btn-primary" data-gf-action="open-wishlist">View Details</button></div>'
        + '</article>';
      return;
    }

    savedGrid.innerHTML = wishlist.slice(0, 6).map(function (item, index) {
      const badge = getWishlistBadgeState(item);
      const priceDisplay = getWishlistPriceDisplay(item);
      const title = getItemTitle(item);
      const metaId = getItemId(item, index);
      const subtitle = [item?.code || item?.sku, item?.size, item?.color].filter(Boolean).join(' · ');
      const imageCandidates = getWishlistImageCandidates(item);
      const imageSrc = imageCandidates[0] || "Cart/products/tshirt-men/Men france/Bk/80.jpg";

      return ''
        + '<article class="gf-account-saved-card">'
        + '<img class="gf-account-saved-image" src="' + escapeHtml(imageSrc) + '" alt="' + escapeHtml(title) + '" data-gf-image-mode="saved" data-gf-image-index="0" data-gf-image-fallbacks="' + escapeHtml(buildImageFallbackAttribute(imageCandidates)) + '">'
        + '<div class="gf-account-saved-body">'
        + '<div class="gf-account-saved-top"><h4>' + escapeHtml(title) + '</h4><span class="gf-account-stock-badge ' + badge.className + '">' + escapeHtml(badge.label) + '</span></div>'
        + (subtitle ? '<p class="gf-account-note">' + escapeHtml(subtitle) + '</p>' : '')
        + '<p class="gf-account-saved-price">' + escapeHtml(priceDisplay) + '</p>'
        + '<div class="gf-account-saved-actions">'
        + '<button type="button" class="gf-account-btn gf-account-btn-primary" data-gf-action="wishlist-to-cart" data-gf-item-id="' + escapeHtml(metaId) + '">Add to Cart</button>'
        + '<button type="button" class="gf-account-btn gf-account-btn-secondary" data-gf-action="wishlist-remove" data-gf-item-id="' + escapeHtml(metaId) + '">Remove</button>'
        + '</div>'
        + '</div>'
        + '</article>';
    }).join("");
    attachImageFallbackHandlers(savedGrid);
  }

  function syncConnectedSections() {
    renderAddressBook();
    renderPaymentMethods();
    renderSummaryGrid();
    renderOrdersGrid();
    renderDesignsGrid();
    renderSavedGrid();
    applyProfileTranslations();
  }

  function getLocaleCode() {
    const countryCode = localStorage.getItem(STORAGE_KEY) || DEFAULT_COUNTRY;
    return COUNTRY_TO_LOCALE[countryCode] || "en-GB";
  }

  function getProfileTextMap() {
    const localeCode = getLocaleCode();
    const resolvedLocale = PROFILE_TEXT_MAPS[localeCode]
      ? localeCode
      : (PROFILE_LOCALE_FALLBACK[localeCode] || "en-GB");

    return PROFILE_TEXT_MAPS[resolvedLocale] || {};
  }

  function getRuntimeTexts() {
    const localeCode = getLocaleCode();
    const resolvedLocale = PROFILE_RUNTIME_I18N[localeCode]
      ? localeCode
      : (PROFILE_LOCALE_FALLBACK[localeCode] || "en-GB");

    return PROFILE_RUNTIME_I18N[resolvedLocale] || PROFILE_RUNTIME_I18N["en-GB"];
  }

  function setBaseText(node) {
    if (!node || node.dataset.gfBaseText) return;
    const baseText = node.childElementCount
      ? Array.from(node.childNodes).filter(function (child) {
          return child.nodeType === Node.TEXT_NODE;
        }).map(function (child) {
          return child.textContent || "";
        }).join(" ").trim()
      : (node.textContent || "").trim();
    node.dataset.gfBaseText = baseText;
  }

  function setTranslatedText(node, map) {
    if (!node) return;
    setBaseText(node);
    const baseText = node.dataset.gfBaseText || "";
    const translatedText = Object.prototype.hasOwnProperty.call(map, baseText) ? map[baseText] : baseText;

    if (node.childElementCount) {
      const textNodes = Array.from(node.childNodes).filter(function (child) {
        return child.nodeType === Node.TEXT_NODE;
      });
      const hasDirectText = textNodes.some(function (child) {
        return String(child.textContent || "").trim();
      });

      if (!hasDirectText) {
        return;
      }

      textNodes.forEach(function (child, index) {
        child.textContent = index === 0 ? ' ' + translatedText : '';
      });
      return;
    }

    node.textContent = translatedText;
  }

  function applyTextMap(map) {
    center.querySelectorAll("h1, h2, h3, h4, h5, p, span, strong, label, button, a").forEach(function (node) {
      if (node.closest(".gf-account-profile-link") || node.closest(".gf-account-user-meta")) return;
      setTranslatedText(node, map);
    });
  }

  function applyOptionTranslations(map) {
    ["#gfProfileGender option", "#gfProfileCountry option", "#gfProfileLanguage option"].forEach(function (selector) {
      document.querySelectorAll(selector).forEach(function (option) {
        setTranslatedText(option, map);
      });
    });
  }

  function applyProfileTranslations() {
    const textMap = getProfileTextMap();
    currentRuntimeTexts = getRuntimeTexts();

    document.title = currentRuntimeTexts.pageTitle;
    applyTextMap(textMap);
    applyOptionTranslations(textMap);

    if (catalogEmailInput) {
      catalogEmailInput.placeholder = currentRuntimeTexts.catalogPlaceholder || PROFILE_RUNTIME_I18N["en-GB"].catalogPlaceholder;
    }
    if (testEmailInput) {
      testEmailInput.placeholder = currentRuntimeTexts.testEmailPlaceholder || PROFILE_RUNTIME_I18N["en-GB"].testEmailPlaceholder;
    }

    const birthdayTitle = document.getElementById("gfBirthdayGiftTitle");
    if (birthdayTitle && currentRuntimeTexts.birthdayHeading) {
      birthdayTitle.textContent = currentRuntimeTexts.birthdayHeading;
    }

    center.setAttribute("data-gf-profile-locale", getLocaleCode());
  }

  function renderFeedback(node, state, title, message) {
    if (!node) return;
    const iconClass = state === "success"
      ? "fa-circle-check"
      : state === "warning"
        ? "fa-circle-exclamation"
        : "fa-triangle-exclamation";

    const icon = document.createElement("span");
    icon.className = "gf-account-feedback-icon";
    icon.setAttribute("aria-hidden", "true");
    icon.innerHTML = '<i class="fa-solid ' + iconClass + '"></i>';

    const body = document.createElement("div");
    body.className = "gf-account-feedback-body";

    const strong = document.createElement("strong");
    strong.textContent = title;

    const copy = document.createElement("p");
    copy.textContent = message;

    body.append(strong, copy);
    node.className = "gf-account-feedback is-" + state;
    node.replaceChildren(icon, body);
    node.hidden = false;
  }

  function clearFeedback(node) {
    if (!node) return;
    node.hidden = true;
    node.className = "gf-account-feedback";
    node.replaceChildren();
  }

  function showToast(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.hidden = false;
    window.clearTimeout(showToast.timeoutId);
    showToast.timeoutId = window.setTimeout(function () {
      toast.hidden = true;
    }, 2200);
  }

  async function uploadProfileAvatar(file) {
    if (!file) return null;

    const url = PROFILE_AVATAR_UPLOAD_URL;
    console.log("AVATAR UPLOAD URL:", url);

    const formData = new FormData();
    formData.append("avatar", file);

    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      body: formData
    });

    const payload = await response.json();
    console.log("AVATAR RESPONSE:", payload);
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || "Unable to upload profile photo.");
    }

    if (!String(payload.avatar_in_db || "").trim()) {
      throw new Error("Avatar uploaded but database was not updated.");
    }

    return payload;
  }

  if (fileInput) {
    fileInput.addEventListener("change", function () {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;

      if (!file.type.startsWith("image/")) {
        showToast(currentRuntimeTexts.imageOnlyToast);
        fileInput.value = "";
        return;
      }

      const reader = new FileReader();
      reader.onload = function (event) {
        const nextSrc = event.target && typeof event.target.result === "string" ? event.target.result : "";
        if (!nextSrc) return;
        avatarTargets.forEach(function (img) {
          img.src = nextSrc;
        });
        writeAvatarState(nextSrc);
        showToast(currentRuntimeTexts.photoUpdatedToast);
      };
      reader.readAsDataURL(file);
    });
  }

  const applyPhotoButton = document.querySelector("#gfProfilePhoto [data-gf-save]");
  const photoStatus = document.getElementById("gfProfilePhotoStatus");

  if (!isServerBackedProfilePage && applyPhotoButton && fileInput) {
    applyPhotoButton.addEventListener("click", async function (event) {
      event.preventDefault();

      const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!file) {
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = "Choose a profile image first.";
        }
        return;
      }

      try {
        const avatarUploadResponse = await uploadProfileAvatar(file);
        const savedAvatar = String(
          avatarUploadResponse.avatar_in_db
            || avatarUploadResponse.saved_avatar
            || avatarUploadResponse.avatar
            || ""
        ).trim();
        writeAvatarState(savedAvatar);
        avatarTargets.forEach(function (img) {
          img.src = savedAvatar || EMPTY_AVATAR_DATA_URI;
        });
        if (typeof applyAvatarState === "function") {
          applyAvatarState();
        }
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = "Profile photo uploaded successfully.";
        }
        fileInput.value = "";
        showToast(currentRuntimeTexts.photoUpdatedToast);
      } catch (error) {
        if (photoStatus) {
          photoStatus.hidden = false;
          photoStatus.textContent = error && error.message ? error.message : "Unable to upload profile photo.";
        }
      }
    });
  }

  toggleButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const active = button.getAttribute("aria-checked") === "true";
      button.setAttribute("aria-checked", active ? "false" : "true");
      persistToggleState(button);
    });
  });

  center.addEventListener("click", function (event) {
    const actionButton = event.target.closest("[data-gf-action]");
    if (!actionButton) return;

    const action = actionButton.getAttribute("data-gf-action");
    const itemId = actionButton.getAttribute("data-gf-item-id") || "";
    const projectPath = actionButton.getAttribute("data-gf-path") || "";
    const addressId = actionButton.getAttribute("data-gf-address-id") || "";
    const paymentId = actionButton.getAttribute("data-gf-payment-id") || "";

    if (action === "wishlist-to-cart") {
      addWishlistItemToCart(itemId);
      syncConnectedSections();
      showToast(currentRuntimeTexts.draftSavedToast);
      return;
    }

    if (action === "wishlist-remove") {
      removeWishlistItem(itemId);
      syncConnectedSections();
      showToast(currentRuntimeTexts.draftRevertedToast);
      return;
    }

    if (action === "edit-design") {
      openDesignProject(projectPath);
      return;
    }

    if (action === "duplicate-design") {
      duplicateProject(projectPath);
      syncConnectedSections();
      showToast(currentRuntimeTexts.draftSavedToast);
      return;
    }

    if (action === "delete-design") {
      deleteProject(projectPath);
      syncConnectedSections();
      showToast(currentRuntimeTexts.draftRevertedToast);
      return;
    }

    if (action === "open-cart") {
      window.location.href = "CartTest.html";
      return;
    }

    if (action === "track-latest-order") {
      openTrackPanelForOrder(getLatestStoredOrder());
      return;
    }

    if (action === "track-order") {
      openTrackPanelForOrder({
        reference: actionButton.getAttribute("data-gf-order-reference") || "",
        email: actionButton.getAttribute("data-gf-order-email") || "guest@girffon.com"
      });
      return;
    }

    if (action === "edit-primary-address") {
      editPrimaryAddress();
      return;
    }

    if (action === "duplicate-primary-address") {
      duplicatePrimaryAddress();
      return;
    }

    if (action === "edit-extra-address") {
      editExtraAddress(addressId);
      return;
    }

    if (action === "set-default-address") {
      setDefaultAddress(addressId);
      return;
    }

    if (action === "delete-extra-address") {
      deleteExtraAddress(addressId);
      return;
    }

    if (action === "add-payment") {
      addNewPaymentMethod();
      return;
    }

    if (action === "edit-payment") {
      editPaymentMethod(paymentId);
      return;
    }

    if (action === "set-primary-payment") {
      setPrimaryPaymentMethod(paymentId);
      return;
    }

    if (action === "delete-payment") {
      deletePaymentMethod(paymentId);
      return;
    }

    if (action === "open-wishlist") {
      window.location.href = "WishlistPage.html";
      return;
    }

    if (action === "open-design-studio") {
      window.location.href = CUSTOM_DESIGN_PAGE;
      return;
    }

    if (action === "change-password") {
      saveSecurityState();
    }
  });

  saveButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      if (button.hasAttribute("data-gf-save-birthday")) return;
      if (button.closest("#gfProfilePhoto")) return;
      if (button.type === "submit" && button.form && button.form.id === "gfAccountProfileForm") return;
      showToast(currentRuntimeTexts.draftSavedToast);
    });
  });

  if (addAddressButton) {
    addAddressButton.addEventListener("click", function () {
      addNewAddressCard();
    });
  }

  if (addPaymentButton) {
    addPaymentButton.addEventListener("click", function () {
      addNewPaymentMethod();
    });
  }

  if (!isServerBackedProfilePage && profileForm && profileSaveStatus) {
    profileForm.addEventListener("submit", async function (event) {
      event.preventDefault();

      if (!profileForm.checkValidity()) {
        renderFeedback(profileSaveStatus, "error", currentRuntimeTexts.profileErrorTitle, currentRuntimeTexts.profileErrorMessage);
        showToast(currentRuntimeTexts.profileNeedsAttentionToast);
        return;
      }

      const nextSnapshot = new URLSearchParams(new FormData(profileForm)).toString();
      if (nextSnapshot === profileSnapshot) {
        renderFeedback(profileSaveStatus, "warning", currentRuntimeTexts.profileWarningTitle, currentRuntimeTexts.profileWarningMessage);
        showToast(currentRuntimeTexts.noProfileChangesToast);
        return;
      }

      try {
        await syncProfileStateFromForm();
        profileSnapshot = nextSnapshot;
        savedBirthdayValue = birthdayInput ? birthdayInput.value.trim() : savedBirthdayValue;
        renderFeedback(profileSaveStatus, "success", currentRuntimeTexts.profileSuccessTitle, currentRuntimeTexts.profileSuccessMessage);
        showToast(currentRuntimeTexts.profileSavedToast);
      } catch (error) {
        renderFeedback(profileSaveStatus, "error", currentRuntimeTexts.profileErrorTitle, error && error.message ? error.message : currentRuntimeTexts.profileErrorMessage);
        showToast(error && error.message ? error.message : currentRuntimeTexts.profileNeedsAttentionToast);
      }
    });
  }

  if (birthdayInput && birthdaySaveButton && birthdaySaveStatus) {
    birthdaySaveButton.addEventListener("click", function () {
      const nextBirthday = birthdayInput.value.trim();
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (!nextBirthday) {
        renderFeedback(birthdaySaveStatus, "error", currentRuntimeTexts.birthdayRequiredTitle, currentRuntimeTexts.birthdayRequiredMessage);
        showToast(currentRuntimeTexts.birthdayRequiredToast);
        return;
      }

      const selectedDate = new Date(nextBirthday + "T00:00:00");
      if (Number.isNaN(selectedDate.getTime()) || selectedDate > today) {
        renderFeedback(birthdaySaveStatus, "error", currentRuntimeTexts.birthdayInvalidTitle, currentRuntimeTexts.birthdayInvalidMessage);
        showToast(currentRuntimeTexts.invalidBirthdayToast);
        return;
      }

      if (nextBirthday === savedBirthdayValue) {
        renderFeedback(birthdaySaveStatus, "warning", currentRuntimeTexts.birthdayWarningTitle, currentRuntimeTexts.birthdayWarningMessage);
        showToast(currentRuntimeTexts.birthdayAlreadySavedToast);
        return;
      }

      syncProfileStateFromForm();
      savedBirthdayValue = nextBirthday;
      renderFeedback(birthdaySaveStatus, "success", currentRuntimeTexts.birthdaySuccessTitle, currentRuntimeTexts.birthdaySuccessMessage);
      showToast(currentRuntimeTexts.birthdaySavedToast);
    });
  }

  if (resetButton) {
    resetButton.addEventListener("click", function () {
      if (profileForm) {
        applyPersistedProfileState();
      }
      applyPersistedAccountState();
      clearFeedback(profileSaveStatus);
      clearFeedback(catalogStatus);
      clearFeedback(testEmailStatus);
      clearFeedback(securitySaveStatus);
      clearFeedback(birthdaySaveStatus);
      if (birthdayInput) savedBirthdayValue = birthdayInput.value;
      showToast(currentRuntimeTexts.draftRevertedToast);
    });
  }

  if (catalogForm && catalogEmailInput && catalogStatus) {
    catalogForm.addEventListener("submit", function (event) {
      event.preventDefault();

      const nextEmail = catalogEmailInput.value.trim().toLowerCase();

      if (!nextEmail || !catalogEmailInput.checkValidity()) {
        renderFeedback(catalogStatus, "error", currentRuntimeTexts.subscriptionErrorTitle, currentRuntimeTexts.subscriptionErrorMessage);
        showToast(currentRuntimeTexts.invalidSubscriptionToast);
        return;
      }

      if (nextEmail === subscribedCatalogEmail) {
        renderFeedback(catalogStatus, "warning", currentRuntimeTexts.subscriptionWarningTitle, currentRuntimeTexts.subscriptionWarningMessage);
        showToast(currentRuntimeTexts.alreadySubscribedToast);
        return;
      }

      const submitButton = catalogForm.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
      }

      fetch(CATALOG_SUBSCRIPTION_URL, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ email: nextEmail })
      }).then(async function (response) {
        const payload = await response.json().catch(function () {
          return {};
        });

        if (!response.ok || !payload.success) {
          throw new Error(payload.message || "Unable to save catalog subscription.");
        }

        subscribedCatalogEmail = String(payload.email || nextEmail).trim().toLowerCase();
        persistAccountPreferenceValues({
          catalogSubscriptionEmail: subscribedCatalogEmail,
          catalogEmails: true
        });
        renderFeedback(catalogStatus, "success", currentRuntimeTexts.subscriptionSuccessTitle, currentRuntimeTexts.subscriptionSuccessMessage);
        showToast(currentRuntimeTexts.subscriptionUpdatedToast);
        applyPersistedAccountState();
      }).catch(function (error) {
        renderFeedback(catalogStatus, "error", currentRuntimeTexts.subscriptionErrorTitle, error && error.message ? error.message : currentRuntimeTexts.subscriptionErrorMessage);
        showToast(error && error.message ? error.message : currentRuntimeTexts.invalidSubscriptionToast);
      }).finally(function () {
        if (submitButton) {
          submitButton.disabled = false;
        }
      });
    });
  }

  if (testEmailForm && testEmailInput && testEmailStatus) {
    testEmailForm.addEventListener("submit", function (event) {
      event.preventDefault();

      const nextEmail = testEmailInput.value.trim().toLowerCase();

      if (!nextEmail || !testEmailInput.checkValidity()) {
        renderFeedback(testEmailStatus, "error", currentRuntimeTexts.testErrorTitle, currentRuntimeTexts.testErrorMessage);
        showToast(currentRuntimeTexts.invalidTestEmailToast);
        return;
      }

      if (nextEmail === lastTestEmailSent) {
        renderFeedback(testEmailStatus, "warning", currentRuntimeTexts.testWarningTitle, currentRuntimeTexts.testWarningMessage);
        showToast(currentRuntimeTexts.previewGeneratedToast);
        return;
      }

      lastTestEmailSent = nextEmail;
      persistAccountPreferenceValues({ testEmail: nextEmail });
      renderFeedback(testEmailStatus, "success", currentRuntimeTexts.testSuccessTitle, currentRuntimeTexts.testSuccessMessage);
      showToast(currentRuntimeTexts.testCompletedToast);
      applyPersistedAccountState();
    });
  }

  function setActiveNav() {
    const threshold = 180;
    let activeId = "";

    navLinks.forEach(function (link) {
      const href = link.getAttribute("href") || "";
      if (!href.startsWith("#")) return;
      const section = document.querySelector(href);
      if (!section) return;
      const rect = section.getBoundingClientRect();
      if (rect.top <= threshold && rect.bottom >= threshold) {
        activeId = href;
      }
    });

    navLinks.forEach(function (link) {
      link.classList.toggle("is-active", link.getAttribute("href") === activeId);
    });
  }

  window.addEventListener("scroll", setActiveNav, { passive: true });
  setActiveNav();

  recentOrdersActions.forEach(function (button) {
    button.addEventListener("click", function () {
      const action = button.getAttribute("data-gf-action") || "";
      if (action === "track-latest-order") {
        openTrackPanelForOrder(getLatestStoredOrder());
        return;
      }
      if (action === "download-invoices") {
        downloadInvoices();
        return;
      }
      window.location.href = "CartTest.html";
    });
  });

  document.addEventListener("girffon:orders-updated", syncConnectedSections);
  document.addEventListener("girffon:auth-updated", applyPersistedProfileState);
  document.addEventListener("girffon:profile-updated", applyPersistedProfileState);
  document.addEventListener("girffon:auth-updated", applyPersistedAccountState);
  document.addEventListener("girffon:profile-updated", applyPersistedAccountState);
  document.addEventListener("girffon:account-state-updated", applyPersistedAccountState);
  window.addEventListener("focus", syncConnectedSections);
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
      syncConnectedSections();
    }
  });

  applyPersistedProfileState();
  ensureRecentSessionState();
  applyPersistedAccountState();
  syncConnectedSections();
  applyProfileTranslations();

  localeCards.forEach(function (card) {
    card.addEventListener("click", function () {
      window.setTimeout(applyProfileTranslations, 0);
    });
  });

  const langObserver = new MutationObserver(function () {
    applyProfileTranslations();
  });
  langObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
});