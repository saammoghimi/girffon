(function () {
    "use strict";

    const ACCOUNT_STRINGS = {
        us: {
            panelTitle: "Account",
            signIn: "Sign in",
            subtitle: "Use your GirffoN account to access saved designs, orders, and your premium profile.",
            identifierLabel: "Username, email, or mobile",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Password",
            passwordPlaceholder: "Enter your password",
            login: "Login",
            staySignedIn: "Stay signed in",
            forgotUsername: "Forgot username?",
            createAccount: "Create an account",
            or: "or",
            google: "Sign in with Google",
            apple: "Sign in with Apple",
            userFallback: "User",
            userEmailFallback: "user@example.com",
            optionsTitle: "Account Options",
            manageAccount: "Manage Account",
            myDesigns: "My Designs",
            orderHistory: "Order History",
            paymentMethods: "Payment Methods",
            shippingAddresses: "Shipping Addresses",
            logout: "Logout"
        },
        it: {
            panelTitle: "Account",
            signIn: "Accedi",
            subtitle: "Usa il tuo account GirffoN per accedere a design salvati, ordini e al tuo profilo premium.",
            identifierLabel: "Nome utente, email o cellulare",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Password",
            passwordPlaceholder: "Inserisci la tua password",
            login: "Accedi",
            staySignedIn: "Resta connesso",
            forgotUsername: "Username dimenticato?",
            createAccount: "Crea un account",
            or: "oppure",
            google: "Accedi con Google",
            apple: "Accedi con Apple",
            userFallback: "Utente",
            userEmailFallback: "utente@example.com",
            optionsTitle: "Opzioni account",
            manageAccount: "Gestisci account",
            myDesigns: "I miei design",
            orderHistory: "Cronologia ordini",
            paymentMethods: "Metodi di pagamento",
            shippingAddresses: "Indirizzi di spedizione",
            logout: "Esci"
        },
        de: {
            panelTitle: "Konto",
            signIn: "Anmelden",
            subtitle: "Nutzen Sie Ihr GirffoN-Konto, um auf gespeicherte Designs, Bestellungen und Ihr Premium-Profil zuzugreifen.",
            identifierLabel: "Benutzername, E-Mail oder Mobilnummer",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Passwort",
            passwordPlaceholder: "Passwort eingeben",
            login: "Anmelden",
            staySignedIn: "Angemeldet bleiben",
            forgotUsername: "Benutzernamen vergessen?",
            createAccount: "Konto erstellen",
            or: "oder",
            google: "Mit Google anmelden",
            apple: "Mit Apple anmelden",
            userFallback: "Benutzer",
            userEmailFallback: "user@example.com",
            optionsTitle: "Kontooptionen",
            manageAccount: "Konto verwalten",
            myDesigns: "Meine Designs",
            orderHistory: "Bestellverlauf",
            paymentMethods: "Zahlungsmethoden",
            shippingAddresses: "Lieferadressen",
            logout: "Abmelden"
        },
        fr: {
            panelTitle: "Compte",
            signIn: "Se connecter",
            subtitle: "Utilisez votre compte GirffoN pour accéder à vos designs enregistrés, vos commandes et votre profil premium.",
            identifierLabel: "Nom d'utilisateur, e-mail ou mobile",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Mot de passe",
            passwordPlaceholder: "Entrez votre mot de passe",
            login: "Connexion",
            staySignedIn: "Rester connecté",
            forgotUsername: "Nom d'utilisateur oublié ?",
            createAccount: "Créer un compte",
            or: "ou",
            google: "Se connecter avec Google",
            apple: "Se connecter avec Apple",
            userFallback: "Utilisateur",
            userEmailFallback: "utilisateur@example.com",
            optionsTitle: "Options du compte",
            manageAccount: "Gérer le compte",
            myDesigns: "Mes designs",
            orderHistory: "Historique des commandes",
            paymentMethods: "Moyens de paiement",
            shippingAddresses: "Adresses de livraison",
            logout: "Se déconnecter"
        },
        es: {
            panelTitle: "Cuenta",
            signIn: "Iniciar sesión",
            subtitle: "Usa tu cuenta GirffoN para acceder a diseños guardados, pedidos y tu perfil premium.",
            identifierLabel: "Usuario, correo o móvil",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Contraseña",
            passwordPlaceholder: "Introduce tu contraseña",
            login: "Entrar",
            staySignedIn: "Mantener sesión iniciada",
            forgotUsername: "¿Olvidaste el usuario?",
            createAccount: "Crear una cuenta",
            or: "o",
            google: "Entrar con Google",
            apple: "Entrar con Apple",
            userFallback: "Usuario",
            userEmailFallback: "usuario@example.com",
            optionsTitle: "Opciones de la cuenta",
            manageAccount: "Gestionar cuenta",
            myDesigns: "Mis diseños",
            orderHistory: "Historial de pedidos",
            paymentMethods: "Métodos de pago",
            shippingAddresses: "Direcciones de envío",
            logout: "Cerrar sesión"
        },
        nl: {
            panelTitle: "Account",
            signIn: "Inloggen",
            subtitle: "Gebruik je GirffoN-account voor opgeslagen ontwerpen, bestellingen en je premiumprofiel.",
            identifierLabel: "Gebruikersnaam, e-mail of mobiel",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Wachtwoord",
            passwordPlaceholder: "Voer je wachtwoord in",
            login: "Inloggen",
            staySignedIn: "Aangemeld blijven",
            forgotUsername: "Gebruikersnaam vergeten?",
            createAccount: "Account aanmaken",
            or: "of",
            google: "Inloggen met Google",
            apple: "Inloggen met Apple",
            userFallback: "Gebruiker",
            userEmailFallback: "user@example.com",
            optionsTitle: "Accountopties",
            manageAccount: "Account beheren",
            myDesigns: "Mijn ontwerpen",
            orderHistory: "Bestelgeschiedenis",
            paymentMethods: "Betaalmethoden",
            shippingAddresses: "Verzendadressen",
            logout: "Uitloggen"
        },
        pl: {
            panelTitle: "Konto",
            signIn: "Zaloguj się",
            subtitle: "Użyj konta GirffoN, aby uzyskać dostęp do zapisanych projektów, zamówień i profilu premium.",
            identifierLabel: "Nazwa użytkownika, e-mail lub telefon",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Hasło",
            passwordPlaceholder: "Wpisz hasło",
            login: "Zaloguj się",
            staySignedIn: "Pozostań zalogowany",
            forgotUsername: "Nie pamiętasz nazwy użytkownika?",
            createAccount: "Utwórz konto",
            or: "lub",
            google: "Zaloguj się przez Google",
            apple: "Zaloguj się przez Apple",
            userFallback: "Użytkownik",
            userEmailFallback: "user@example.com",
            optionsTitle: "Opcje konta",
            manageAccount: "Zarządzaj kontem",
            myDesigns: "Moje projekty",
            orderHistory: "Historia zamówień",
            paymentMethods: "Metody płatności",
            shippingAddresses: "Adresy dostawy",
            logout: "Wyloguj się"
        },
        sv: {
            panelTitle: "Konto",
            signIn: "Logga in",
            subtitle: "Använd ditt GirffoN-konto för att nå sparade designer, beställningar och din premiumprofil.",
            identifierLabel: "Användarnamn, e-post eller mobil",
            identifierPlaceholder: "girffon_2025",
            passwordLabel: "Lösenord",
            passwordPlaceholder: "Ange ditt lösenord",
            login: "Logga in",
            staySignedIn: "Fortsätt vara inloggad",
            forgotUsername: "Glömt användarnamn?",
            createAccount: "Skapa ett konto",
            or: "eller",
            google: "Logga in med Google",
            apple: "Logga in med Apple",
            userFallback: "Användare",
            userEmailFallback: "user@example.com",
            optionsTitle: "Kontoalternativ",
            manageAccount: "Hantera konto",
            myDesigns: "Mina designer",
            orderHistory: "Orderhistorik",
            paymentMethods: "Betalningsmetoder",
            shippingAddresses: "Leveransadresser",
            logout: "Logga ut"
        }
    };

    const ACCOUNT_FALLBACKS = {
        gb: "us",
        ca: "us",
        ch: "de"
    };

    function resolveAccountLang() {
        const raw = (localStorage.getItem("cdpLang") || "us").toLowerCase();
        if (ACCOUNT_STRINGS[raw]) {
            return raw;
        }
        return ACCOUNT_FALLBACKS[raw] || "us";
    }

    document.addEventListener("DOMContentLoaded", () => {
        const trigger = document.querySelector('[data-tool="account"]');
        const panel = document.getElementById("cdpAccountPanel");
        const closeBtn = panel?.querySelector(".cdp-settings-close");

        if (!trigger || !panel) {
            return;
        }

        const guestView = document.getElementById("cdpAccountGuest");
        const userView = document.getElementById("cdpAccountUser");

        const loginForm = document.getElementById("cdpAccountLoginForm");
        const loginIdentifierInput = document.getElementById("cdpLoginIdentifier");
        const loginPasswordInput = document.getElementById("cdpLoginPassword");
        const signupBtn = document.getElementById("cdpSignupBtn");
        const forgotBtn = document.getElementById("cdpForgotAccountBtn");
        const googleLoginBtn = document.getElementById("cdpGoogleLoginBtn");
        const appleLoginBtn = document.getElementById("cdpAppleLoginBtn");
        const logoutBtn = document.getElementById("cdpLogoutBtn");
        const manageAccountBtn = document.getElementById("cdpManageAccountBtn");
        const myDesignsBtn = document.getElementById("cdpMyDesignsBtn");
        const orderHistoryBtn = document.getElementById("cdpOrderHistoryBtn");
        const paymentMethodsBtn = document.getElementById("cdpPaymentMethodsBtn");
        const shippingAddressesBtn = document.getElementById("cdpShippingAddressesBtn");

        const userNameEl = document.getElementById("cdpUserName");
        const userEmailEl = document.getElementById("cdpUserEmail");

        const LOGIN_KEY = "cdpLoggedIn";
        const USER_KEY = "cdpUserData";

        let isLoggedIn = false;
        let userData = null;

        function t(key) {
            const lang = resolveAccountLang();
            const dict = ACCOUNT_STRINGS[lang] || ACCOUNT_STRINGS.us;
            return dict[key] || ACCOUNT_STRINGS.us[key] || "";
        }

        function applyAccountTranslations() {
            const titleEl = panel.querySelector(".cdp-settings-header h3");
            if (titleEl) {
                titleEl.innerHTML = '<i class="fa-regular fa-circle-user"></i> ' + t("panelTitle");
            }

            const signInHeading = panel.querySelector(".cdp-account-auth-head h4");
            if (signInHeading) signInHeading.textContent = t("signIn");

            const subtitle = panel.querySelector(".cdp-account-auth-head p");
            if (subtitle) subtitle.textContent = t("subtitle");

            const identifierLabel = panel.querySelector('label[for="cdpLoginIdentifier"]');
            if (identifierLabel) identifierLabel.textContent = t("identifierLabel");

            if (loginIdentifierInput) {
                loginIdentifierInput.placeholder = t("identifierPlaceholder");
            }

            const passwordLabel = panel.querySelector('label[for="cdpLoginPassword"]');
            if (passwordLabel) passwordLabel.textContent = t("passwordLabel");

            if (loginPasswordInput) {
                loginPasswordInput.placeholder = t("passwordPlaceholder");
            }

            const loginLabel = loginForm?.querySelector('#cdpLoginBtn span');
            if (loginLabel) loginLabel.textContent = t("login");

            const staySignedIn = panel.querySelector('label[for="cdpStaySignedIn"] span');
            if (staySignedIn) staySignedIn.textContent = t("staySignedIn");

            if (forgotBtn) forgotBtn.textContent = t("forgotUsername");

            const createAccountLabel = signupBtn?.querySelector("span");
            if (createAccountLabel) createAccountLabel.textContent = t("createAccount");

            const divider = panel.querySelector(".cdp-account-divider span");
            if (divider) divider.textContent = t("or");

            const googleLabel = googleLoginBtn?.querySelector("span");
            if (googleLabel) googleLabel.textContent = t("google");

            const appleLabel = appleLoginBtn?.querySelector("span");
            if (appleLabel) appleLabel.textContent = t("apple");

            const optionsTitle = panel.querySelector(".cdp-account-options-title");
            if (optionsTitle) optionsTitle.textContent = t("optionsTitle");

            const manageLabel = manageAccountBtn?.querySelector(".cdp-account-option-left span:last-child");
            if (manageLabel) manageLabel.textContent = t("manageAccount");

            const designsLabel = myDesignsBtn?.querySelector(".cdp-account-option-left span:last-child");
            if (designsLabel) designsLabel.textContent = t("myDesigns");

            const historyLabel = orderHistoryBtn?.querySelector(".cdp-account-option-left span:last-child");
            if (historyLabel) historyLabel.textContent = t("orderHistory");

            const paymentsLabel = paymentMethodsBtn?.querySelector(".cdp-account-option-left span:last-child");
            if (paymentsLabel) paymentsLabel.textContent = t("paymentMethods");

            const shippingLabel = shippingAddressesBtn?.querySelector(".cdp-account-option-left span:last-child");
            if (shippingLabel) shippingLabel.textContent = t("shippingAddresses");

            const logoutLabel = logoutBtn?.querySelector("span");
            if (logoutLabel) logoutLabel.textContent = t("logout");

            if (trigger) {
                trigger.setAttribute("title", t("panelTitle"));
                trigger.setAttribute("aria-label", t("panelTitle"));
            }

            updateAccountView();
        }

        function setPanelVisibility(visible) {
            panel.setAttribute("data-visible", visible ? "true" : "false");
        }

        function setActiveView(viewName) {
            const nextView = typeof viewName === "string" ? viewName : "guest";

            if (guestView) {
                guestView.style.display = nextView === "guest" ? "flex" : "none";
            }

            if (userView) {
                userView.style.display = nextView === "user" ? "flex" : "none";
            }
        }

        function loadAuthState() {
            isLoggedIn = localStorage.getItem(LOGIN_KEY) === "true";
            userData = null;

            if (isLoggedIn) {
                const saved = localStorage.getItem(USER_KEY);

                if (saved) {
                    try {
                        userData = JSON.parse(saved);
                    } catch (_error) {
                        isLoggedIn = false;
                    }
                }
            }
        }

        function saveAuthState() {
            if (!isLoggedIn || !userData) {
                localStorage.removeItem(LOGIN_KEY);
                localStorage.removeItem(USER_KEY);
                return;
            }

            localStorage.setItem(LOGIN_KEY, "true");
            localStorage.setItem(USER_KEY, JSON.stringify(userData));
        }

        function updateAccountView() {
            if (isLoggedIn && userData) {
                setActiveView("user");

                if (userNameEl) {
                    userNameEl.textContent = userData.name || t("userFallback");
                }

                if (userEmailEl) {
                    userEmailEl.textContent = userData.email || t("userEmailFallback");
                }
            } else {
                setActiveView("guest");
            }
        }

        function loginWith(user) {
            userData = user;
            isLoggedIn = true;
            saveAuthState();
            updateAccountView();
        }

        function doLogout() {
            isLoggedIn = false;
            userData = null;
            saveAuthState();
            updateAccountView();
        }

        function goToAccountSection(sectionId) {
            const normalizedSection = typeof sectionId === "string" ? sectionId.trim() : "";
            const targetHash = normalizedSection.startsWith("#") ? normalizedSection : "#gfProfileDetails";

            setPanelVisibility(false);
            window.location.href = "../../ProfilePage.php" + targetHash;
        }

        trigger.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            loadAuthState();
            updateAccountView();
            setPanelVisibility(true);
        });

        closeBtn?.addEventListener("click", () => setPanelVisibility(false));

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && panel.getAttribute("data-visible") === "true") {
                setPanelVisibility(false);
            }
        });

        loginForm?.addEventListener("submit", (event) => {
            event.preventDefault();

            const identifier = (loginIdentifierInput?.value || "").trim();
            const password = (loginPasswordInput?.value || "").trim();

            if (!identifier || !password) {
                if (!identifier) {
                    loginIdentifierInput?.focus();
                } else {
                    loginPasswordInput?.focus();
                }
                return;
            }

            const displayName = identifier.includes("@")
                ? identifier.split("@")[0]
                : identifier;

            loginWith({
                name: displayName.replace(/[._-]+/g, " ").replace(/\b\w/g, (match) => match.toUpperCase()) || "GirffoN Member",
                email: identifier.includes("@") ? identifier : identifier + "@girffon.com",
                provider: "email"
            });

            loginForm.reset();
        });

        signupBtn?.addEventListener("click", () => {
            loginWith({ name: "New User", email: "newuser@example.com", provider: "email" });
        });

        forgotBtn?.addEventListener("click", () => {
            loginIdentifierInput?.focus();
        });

        googleLoginBtn?.addEventListener("click", () => {
            loginWith({ name: "Google User", email: "user@gmail.com", provider: "google" });
        });

        appleLoginBtn?.addEventListener("click", () => {
            loginWith({ name: "Apple User", email: "user@icloud.com", provider: "apple" });
        });

        logoutBtn?.addEventListener("click", doLogout);

        manageAccountBtn?.addEventListener("click", () => {
            goToAccountSection("#gfProfileDetails");
        });

        myDesignsBtn?.addEventListener("click", () => {
            goToAccountSection("#gfMyDesigns");
        });

        orderHistoryBtn?.addEventListener("click", () => {
            goToAccountSection("#gfRecentOrders");
        });

        paymentMethodsBtn?.addEventListener("click", () => {
            goToAccountSection("#gfPayments");
        });

        shippingAddressesBtn?.addEventListener("click", () => {
            goToAccountSection("#gfAddressBook");
        });

        window.addEventListener("storage", (event) => {
            if (event.key === LOGIN_KEY || event.key === USER_KEY) {
                loadAuthState();
                updateAccountView();
            }
        });

        window.addEventListener("cdp-locale-changed", applyAccountTranslations);

        loadAuthState();
        updateAccountView();
        applyAccountTranslations();
    });
})();
