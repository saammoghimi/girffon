// =========================
// File System - Complete with Open & Fixed Save As
// =========================

document.addEventListener('DOMContentLoaded', function() {
    // Shared state used across builders
    let fileMenu = null;
    let fileMenuOverlay = null;
    let fileMenuShell = null;
    let saveAsModal = null;
    let openModal = null;
    let openContextMenu = null;
    let newFolderModal = null;
    let currentProjectName = localStorage.getItem("cdpCurrentProject") || "Untitled";
    let currentFolder = localStorage.getItem("cdpCurrentFolder") || "";
    let currentOpenFolder = "";
    const cdpRemoteStorageUrl = "/GirffoN/backend/custom-design/storage.php";
    const cdpRemoteStorageSupported = window.location.protocol === "http:" || window.location.protocol === "https:";
    const pendingProjectPathKey = "cdpPendingProjectPath";
    const cleanupKeys = [
        "cdpCurrentProject",
        "cdpCurrentFolder",
        "cdpFolderStructure",
        "cdpLastSaved",
        "cdpPendingProjectPath",
        "cdpNote",
    ];
    const fileBtn = document.querySelector('[data-tool="file"]');
    const fileShortcutConfig = [
        { action: "new", key: "1", ctrl: true, shift: false, label: "Ctrl + 1" },
        { action: "open", key: "o", ctrl: true, shift: false, label: "Ctrl + O" },
        { action: "save", key: "s", ctrl: true, shift: false, label: "Ctrl + S" },
        { action: "saveas", key: "s", ctrl: true, shift: true, label: "Ctrl + Shift + S" }
    ];
    const fileShortcutMap = fileShortcutConfig.reduce((acc, entry) => {
        acc[entry.action] = entry;
        return acc;
    }, {});

    window.cdpDevCleanupStorage = function cdpDevCleanupStorage() {
        const removed = [];
        const untouched = [];
        Object.keys(localStorage).forEach((key) => {
            if (key.startsWith("cdpProject_") || cleanupKeys.includes(key)) {
                removed.push(key);
                localStorage.removeItem(key);
            } else {
                untouched.push(key);
            }
        });

        currentProjectName = "Untitled";
        currentFolder = "";
        currentOpenFolder = "";

        return {
            removed: removed.sort(),
            untouched: untouched.sort(),
        };
    };
        // =========================
        // Language System (Locale)
        // =========================
        const LOCALE_MAP = {
            us: 'us',
            it: 'it',
            fr: 'fr',
            de: 'de',
            es: 'es',
            nl: 'nl',
            pl: 'pl',
            se: 'sv',
            sv: 'sv',
            gb: 'gb',
            uk: 'gb',
            ch: 'ch',
            ca: 'ca',
            en: 'us'
        };

        function normalizeLang(lang) {
            const key = (lang || 'us').toString().toLowerCase();
            return LOCALE_MAP[key] || 'us';
        }

        function getLang() {
            // Always default to US English unless user sets language
            let lang = localStorage.getItem('cdpLang');
            if (!lang) {
                lang = 'us';
                localStorage.setItem('cdpLang', lang);
            }
            return normalizeLang(lang);
        }

        function setLang(lang, meta) {
            const normalized = normalizeLang(lang);
            localStorage.setItem('cdpLang', normalized);
            const extra = (meta && typeof meta === 'object') ? { ...meta } : {};
            extra.lang = normalized;
            window.dispatchEvent(new CustomEvent('cdp-locale-changed', { detail: extra }));
        }

        // Translation keys (minimal, can be extended)
        const LANGS = {
            us: {
                file_new: 'New', file_open: 'Open', file_save: 'Save', file_saveas: 'Save As',
                open_title: 'Open Project', saveas_title: 'Save As',
                open: 'Open', save_as: 'Save As',
                back: 'Back', cancel: 'Cancel', confirm: 'Confirm', ok: 'OK', save: 'Save', new_folder: 'New Folder', file_name: 'File Name',
                file_placeholder: 'Enter file name...', folder_name: 'Folder Name', folder_placeholder: 'Enter folder name...', create: 'Create',
                save_changes: 'Do you want to save changes?', dont_save: "Don't Save",
                project_saved_named: 'Project "{name}" saved successfully!', project_loaded_named: 'Project "{name}" loaded successfully!',
                folder_created_named: 'Folder "{name}" created!',
                root: 'Root', empty_folder: 'This folder is empty', empty_folder_title: 'This folder is empty', empty_folder_sub_saveas: 'Click "New Folder" to create a folder', no_projects: 'No projects found', click_new_folder: 'Click "New Folder" to create a folder',
                success: 'Success', error: 'Error', warning: 'Warning', info: 'Info',
                design_created: 'New design created!', design_saved: 'Design saved successfully',
                project_loaded: 'Project loaded successfully', project_not_found: 'Project not found', project_expired: 'Project has expired',
                project_expired_limit: 'Project has expired (90 days limit)!',
                save_success: 'Project saved successfully!', load_success: 'Project loaded successfully!',
                not_found: 'Project not found!', expired: 'Project has expired!', enter_name: 'Please enter a file name!', enter_folder: 'Please enter a folder name!', exists: 'This folder already exists!', max_folders: 'Maximum 30 folders allowed!', max_depth: 'Maximum folder depth (3 levels) reached!',
            },
            en: {
                file_new: 'New', file_open: 'Open', file_save: 'Save', file_saveas: 'Save as',
                open_title: 'Open project', saveas_title: 'Save as',
                open: 'Open', save_as: 'Save as',
                back: 'Back', cancel: 'Cancel', confirm: 'Confirm', ok: 'OK', save: 'Save', new_folder: 'New folder', file_name: 'File name',
                file_placeholder: 'Enter file name...', folder_name: 'Folder name', folder_placeholder: 'Enter folder name...', create: 'Create',
                save_changes: 'Do you want to save changes?', dont_save: "Don't save",
                project_saved_named: 'Project "{name}" saved successfully!', project_loaded_named: 'Project "{name}" loaded successfully!',
                folder_created_named: 'Folder "{name}" created!',
                root: 'Root', empty_folder: 'This folder is empty', empty_folder_title: 'This folder is empty', empty_folder_sub_saveas: 'Click "New folder" to create a folder', no_projects: 'No projects found', click_new_folder: 'Click "New folder" to create a folder',
                success: 'Success', error: 'Error', warning: 'Warning', info: 'Info',
                design_created: 'New design created!', design_saved: 'Design saved successfully',
                project_loaded: 'Project loaded successfully', project_not_found: 'Project not found', project_expired: 'Project has expired',
                save_success: 'Project saved successfully!', load_success: 'Project loaded successfully!',
                not_found: 'Project not found!', expired: 'Project has expired!', enter_name: 'Please enter a file name!', enter_folder: 'Please enter a folder name!', exists: 'This folder already exists!', max_folders: 'Maximum 30 folders allowed!', max_depth: 'Maximum folder depth (3 levels) reached!',
            },
            it: {
                file_new: 'Nuovo', file_open: 'Apri', file_save: 'Salva', file_saveas: 'Salva con nome',
                open_title: 'Apri progetto', saveas_title: 'Salva con nome',
                open: 'Apri', save_as: 'Salva con nome',
                back: 'Indietro', cancel: 'Annulla', confirm: 'Conferma', ok: 'OK', save: 'Salva', new_folder: 'Nuova cartella', file_name: 'Nome file',
                file_placeholder: 'Inserisci il nome del file...', folder_name: 'Nome cartella', folder_placeholder: 'Inserisci il nome della cartella...', create: 'Crea',
                save_changes: 'Vuoi salvare le modifiche?', dont_save: 'Non salvare',
                project_saved_named: 'Progetto "{name}" salvato con successo!', project_loaded_named: 'Progetto "{name}" caricato con successo!',
                folder_created_named: 'Cartella "{name}" creata!',
                root: 'Root', empty_folder: 'Questa cartella è vuota', empty_folder_title: 'Questa cartella è vuota', empty_folder_sub_saveas: 'Clicca "Nuova cartella" per creare una cartella', no_projects: 'Nessun progetto trovato', click_new_folder: 'Clicca "Nuova cartella" per creare una cartella',
                success: 'Successo', error: 'Errore', warning: 'Attenzione', info: 'Info',
                design_created: 'Nuovo design creato!', design_saved: 'Design salvato con successo',
                project_loaded: 'Progetto caricato con successo', project_not_found: 'Progetto non trovato', project_expired: 'Il progetto è scaduto',
                project_expired_limit: 'Il progetto è scaduto (limite 90 giorni)!',
                save_success: 'Progetto salvato!', load_success: 'Progetto caricato!',
                not_found: 'Progetto non trovato!', expired: 'Progetto scaduto!', enter_name: 'Inserisci il nome del file!', enter_folder: 'Inserisci il nome della cartella!', exists: 'Questa cartella esiste già!', max_folders: 'Massimo 30 cartelle consentite!', max_depth: 'Profondità massima (3 livelli) raggiunta!',
            },
            fr: {
                file_new: 'Nouveau', file_open: 'Ouvrir', file_save: 'Enregistrer', file_saveas: 'Enregistrer sous',
                open_title: 'Ouvrir le projet', saveas_title: 'Enregistrer sous',
                open: 'Ouvrir', save_as: 'Enregistrer sous',
                back: 'Retour', cancel: 'Annuler', confirm: 'Confirmer', ok: 'OK', save: 'Enregistrer', new_folder: 'Nouveau dossier', file_name: 'Nom du fichier',
                file_placeholder: 'Entrez le nom du fichier...', folder_name: 'Nom du dossier', folder_placeholder: 'Entrez le nom du dossier...', create: 'Créer',
                save_changes: 'Voulez-vous enregistrer les modifications ?', dont_save: 'Ne pas enregistrer',
                project_saved_named: 'Projet "{name}" enregistré avec succès !', project_loaded_named: 'Projet "{name}" chargé avec succès !',
                folder_created_named: 'Dossier "{name}" créé !',
                root: 'Racine', empty_folder: 'Ce dossier est vide', empty_folder_title: 'Ce dossier est vide', empty_folder_sub_saveas: 'Cliquez sur "Nouveau dossier" pour créer un dossier', no_projects: 'Aucun projet trouvé', click_new_folder: 'Cliquez sur "Nouveau dossier" pour créer un dossier',
                success: 'Succès', error: 'Erreur', warning: 'Attention', info: 'Info',
                design_created: 'Nouveau design créé !', design_saved: 'Design enregistré avec succès',
                project_loaded: 'Projet chargé avec succès', project_not_found: 'Projet introuvable', project_expired: 'Le projet a expiré',
                project_expired_limit: 'Le projet a expiré (limite de 90 jours) !',
                save_success: 'Projet enregistré !', load_success: 'Projet chargé !',
                not_found: 'Projet introuvable !', expired: 'Projet expiré !', enter_name: 'Veuillez entrer un nom de fichier !', enter_folder: 'Veuillez entrer un nom de dossier !', exists: 'Ce dossier existe déjà !', max_folders: 'Maximum 30 dossiers autorisés !', max_depth: 'Profondeur maximale (3 niveaux) atteinte !',
            },
            de: {
                file_new: 'Neu', file_open: 'Öffnen', file_save: 'Speichern', file_saveas: 'Speichern unter',
                open_title: 'Projekt öffnen', saveas_title: 'Speichern unter',
                open: 'Öffnen', save_as: 'Speichern unter',
                back: 'Zurück', cancel: 'Abbrechen', confirm: 'Bestätigen', ok: 'OK', save: 'Speichern', new_folder: 'Neuer Ordner', file_name: 'Dateiname',
                file_placeholder: 'Dateinamen eingeben...', folder_name: 'Ordnername', folder_placeholder: 'Ordnernamen eingeben...', create: 'Erstellen',
                save_changes: 'Möchten Sie die Änderungen speichern?', dont_save: 'Nicht speichern',
                project_saved_named: 'Projekt "{name}" erfolgreich gespeichert!', project_loaded_named: 'Projekt "{name}" erfolgreich geladen!',
                folder_created_named: 'Ordner "{name}" erstellt!',
                root: 'Stammordner', empty_folder: 'Dieser Ordner ist leer', empty_folder_title: 'Dieser Ordner ist leer', empty_folder_sub_saveas: 'Klicke auf "Neuer Ordner", um einen Ordner zu erstellen', no_projects: 'Keine Projekte gefunden', click_new_folder: 'Klicke auf "Neuer Ordner", um einen Ordner zu erstellen',
                success: 'Erfolg', error: 'Fehler', warning: 'Warnung', info: 'Info',
                design_created: 'Neues Design erstellt!', design_saved: 'Design erfolgreich gespeichert',
                project_loaded: 'Projekt erfolgreich geladen', project_not_found: 'Projekt nicht gefunden', project_expired: 'Projekt ist abgelaufen',
                project_expired_limit: 'Projekt ist abgelaufen (90-Tage-Limit)!',
                save_success: 'Projekt erfolgreich gespeichert!', load_success: 'Projekt erfolgreich geladen!',
                not_found: 'Projekt nicht gefunden!', expired: 'Projekt ist abgelaufen!', enter_name: 'Bitte gib einen Dateinamen ein!', enter_folder: 'Bitte gib einen Ordnernamen ein!', exists: 'Dieser Ordner existiert bereits!', max_folders: 'Maximal 30 Ordner erlaubt!', max_depth: 'Maximale Ordner-Tiefe (3 Ebenen) erreicht!',
            },
            es: {
                file_new: 'Nuevo', file_open: 'Abrir', file_save: 'Guardar', file_saveas: 'Guardar como',
                open_title: 'Abrir proyecto', saveas_title: 'Guardar como',
                open: 'Abrir', save_as: 'Guardar como',
                back: 'Atrás', cancel: 'Cancelar', confirm: 'Confirmar', ok: 'OK', save: 'Guardar', new_folder: 'Nueva carpeta', file_name: 'Nombre del archivo',
                file_placeholder: 'Ingresa el nombre del archivo...', folder_name: 'Nombre de la carpeta', folder_placeholder: 'Ingresa el nombre de la carpeta...', create: 'Crear',
                save_changes: '¿Quieres guardar los cambios?', dont_save: 'No guardar',
                project_saved_named: '¡Proyecto "{name}" guardado correctamente!', project_loaded_named: '¡Proyecto "{name}" cargado correctamente!',
                folder_created_named: '¡Carpeta "{name}" creada!',
                root: 'Raíz', empty_folder: 'Esta carpeta está vacía', empty_folder_title: 'Esta carpeta está vacía', empty_folder_sub_saveas: 'Haz clic en "Nueva carpeta" para crear una carpeta', no_projects: 'No se encontraron proyectos', click_new_folder: 'Haz clic en "Nueva carpeta" para crear una carpeta',
                success: 'Éxito', error: 'Error', warning: 'Advertencia', info: 'Info',
                design_created: '¡Nuevo diseño creado!', design_saved: 'Diseño guardado correctamente',
                project_loaded: 'Proyecto cargado correctamente', project_not_found: 'Proyecto no encontrado', project_expired: 'El proyecto ha expirado',
                project_expired_limit: '¡El proyecto ha caducado (límite de 90 días)!',
                save_success: '¡Proyecto guardado correctamente!', load_success: '¡Proyecto cargado correctamente!',
                not_found: '¡Proyecto no encontrado!', expired: '¡El proyecto ha expirado!', enter_name: '¡Introduce un nombre de archivo!', enter_folder: '¡Introduce un nombre de carpeta!', exists: '¡Esta carpeta ya existe!', max_folders: '¡Máximo 30 carpetas permitidas!', max_depth: '¡Se alcanzó la profundidad máxima (3 niveles)!',
            },
            nl: {
                file_new: 'Nieuw', file_open: 'Openen', file_save: 'Opslaan', file_saveas: 'Opslaan als',
                open_title: 'Project openen', saveas_title: 'Opslaan als',
                open: 'Openen', save_as: 'Opslaan als',
                back: 'Terug', cancel: 'Annuleren', confirm: 'Bevestigen', ok: 'OK', save: 'Opslaan', new_folder: 'Nieuwe map', file_name: 'Bestandsnaam',
                file_placeholder: 'Voer een bestandsnaam in...', folder_name: 'Mapnaam', folder_placeholder: 'Voer een mapnaam in...', create: 'Maken',
                save_changes: 'Wil je de wijzigingen opslaan?', dont_save: 'Niet opslaan',
                project_saved_named: 'Project "{name}" succesvol opgeslagen!', project_loaded_named: 'Project "{name}" succesvol geladen!',
                folder_created_named: 'Map "{name}" aangemaakt!',
                root: 'Hoofdmap', empty_folder: 'Deze map is leeg', empty_folder_title: 'Deze map is leeg', empty_folder_sub_saveas: 'Klik op "Nieuwe map" om een map te maken', no_projects: 'Geen projecten gevonden', click_new_folder: 'Klik op "Nieuwe map" om een map te maken',
                success: 'Succes', error: 'Fout', warning: 'Waarschuwing', info: 'Info',
                design_created: 'Nieuw ontwerp gemaakt!', design_saved: 'Ontwerp succesvol opgeslagen',
                project_loaded: 'Project succesvol geladen', project_not_found: 'Project niet gevonden', project_expired: 'Project is verlopen',
                project_expired_limit: 'Project is verlopen (limiet 90 dagen)!',
                save_success: 'Project succesvol opgeslagen!', load_success: 'Project succesvol geladen!',
                not_found: 'Project niet gevonden!', expired: 'Project is verlopen!', enter_name: 'Voer een bestandsnaam in!', enter_folder: 'Voer een mapnaam in!', exists: 'Deze map bestaat al!', max_folders: 'Maximaal 30 mappen toegestaan!', max_depth: 'Maximale mapdiepte (3 niveaus) bereikt!',
            },
            pl: {
                file_new: 'Nowy', file_open: 'Otwórz', file_save: 'Zapisz', file_saveas: 'Zapisz jako',
                open_title: 'Otwórz projekt', saveas_title: 'Zapisz jako',
                open: 'Otwórz', save_as: 'Zapisz jako',
                back: 'Wstecz', cancel: 'Anuluj', confirm: 'Potwierdź', ok: 'OK', save: 'Zapisz', new_folder: 'Nowy folder', file_name: 'Nazwa pliku',
                file_placeholder: 'Wpisz nazwę pliku...', folder_name: 'Nazwa folderu', folder_placeholder: 'Wpisz nazwę folderu...', create: 'Utwórz',
                save_changes: 'Czy chcesz zapisać zmiany?', dont_save: 'Nie zapisuj',
                project_saved_named: 'Projekt "{name}" zapisany pomyślnie!', project_loaded_named: 'Projekt "{name}" wczytany pomyślnie!',
                folder_created_named: 'Folder "{name}" utworzony!',
                root: 'Katalog główny', empty_folder: 'Ten folder jest pusty', empty_folder_title: 'Ten folder jest pusty', empty_folder_sub_saveas: 'Kliknij "Nowy folder", aby utworzyć folder', no_projects: 'Nie znaleziono projektów', click_new_folder: 'Kliknij "Nowy folder", aby utworzyć folder',
                success: 'Sukces', error: 'Błąd', warning: 'Ostrzeżenie', info: 'Info',
                design_created: 'Nowy projekt utworzony!', design_saved: 'Projekt zapisano pomyślnie',
                project_loaded: 'Projekt wczytano pomyślnie', project_not_found: 'Nie znaleziono projektu', project_expired: 'Projekt wygasł',
                project_expired_limit: 'Projekt wygasł (limit 90 dni)!',
                save_success: 'Projekt zapisano pomyślnie!', load_success: 'Projekt wczytano pomyślnie!',
                not_found: 'Nie znaleziono projektu!', expired: 'Projekt wygasł!', enter_name: 'Podaj nazwę pliku!', enter_folder: 'Podaj nazwę folderu!', exists: 'Taki folder już istnieje!', max_folders: 'Dozwolone maksymalnie 30 folderów!', max_depth: 'Osiągnięto maksymalną głębokość (3 poziomy)!',
            },
            sv: {
                file_new: 'Ny', file_open: 'Öppna', file_save: 'Spara', file_saveas: 'Spara som',
                open_title: 'Öppna projekt', saveas_title: 'Spara som',
                open: 'Öppna', save_as: 'Spara som',
                back: 'Tillbaka', cancel: 'Avbryt', confirm: 'Bekräfta', ok: 'OK', save: 'Spara', new_folder: 'Ny mapp', file_name: 'Filnamn',
                file_placeholder: 'Ange filnamn...', folder_name: 'Mappnamn', folder_placeholder: 'Ange mappnamn...', create: 'Skapa',
                save_changes: 'Vill du spara ändringarna?', dont_save: 'Spara inte',
                project_saved_named: 'Projekt "{name}" sparades!', project_loaded_named: 'Projekt "{name}" laddades!',
                folder_created_named: 'Mapp "{name}" skapad!',
                root: 'Rot', empty_folder: 'Den här mappen är tom', empty_folder_title: 'Den här mappen är tom', empty_folder_sub_saveas: 'Klicka på "Ny mapp" för att skapa en mapp', no_projects: 'Inga projekt hittades', click_new_folder: 'Klicka på "Ny mapp" för att skapa en mapp',
                success: 'Klart', error: 'Fel', warning: 'Varning', info: 'Info',
                design_created: 'Nytt design skapades!', design_saved: 'Design sparades framgångsrikt',
                project_loaded: 'Projektet lästes in!', project_not_found: 'Projektet hittades inte', project_expired: 'Projektet har gått ut',
                project_expired_limit: 'Projektet har gått ut (90 dagars gräns)!',
                save_success: 'Projektet sparades!', load_success: 'Projektet lästes in!',
                not_found: 'Projektet hittades inte!', expired: 'Projektet har gått ut!', enter_name: 'Ange ett filnamn!', enter_folder: 'Ange ett mappnamn!', exists: 'Den här mappen finns redan!', max_folders: 'Högst 30 mappar är tillåtna!', max_depth: 'Maximalt mapdjup (3 nivåer) uppnått!',
            },
        };

        const LANG_ALIASES = { gb: 'us', ca: 'us', ch: 'de' };
        Object.entries(LANG_ALIASES).forEach(([alias, source]) => {
            if (!LANGS[alias] && LANGS[source]) {
                LANGS[alias] = { ...LANGS[source] };
            }
        });

        const UI_MESSAGES = {
            design_created: {
                en: 'New design created!',
                it: 'Nuovo design creato!',
                fr: 'Nouveau design créé !',
                de: 'Neues Design erstellt!',
                es: '¡Nuevo diseño creado!'
            },
            design_saved: {
                en: 'Design saved successfully',
                it: 'Design salvato con successo',
                fr: 'Design enregistré avec succès',
                de: 'Design erfolgreich gespeichert',
                es: 'Diseño guardado correctamente'
            },
            project_loaded: {
                en: 'Project loaded successfully',
                it: 'Progetto caricato con successo',
                fr: 'Projet chargé avec succès',
                de: 'Projekt erfolgreich geladen',
                es: 'Proyecto cargado correctamente'
            },
            project_not_found: {
                en: 'Project not found',
                it: 'Progetto non trovato',
                fr: 'Projet introuvable',
                de: 'Projekt nicht gefunden',
                es: 'Proyecto no encontrado'
            },
            project_expired: {
                en: 'Project has expired',
                it: 'Il progetto è scaduto',
                fr: 'Le projet a expiré',
                de: 'Projekt ist abgelaufen',
                es: 'El proyecto ha expirado'
            },
            open: {
                en: 'Open',
                it: 'Apri',
                fr: 'Ouvrir',
                de: 'Öffnen',
                es: 'Abrir'
            },
            save: {
                en: 'Save',
                it: 'Salva',
                fr: 'Enregistrer',
                de: 'Speichern',
                es: 'Guardar'
            },
            save_as: {
                en: 'Save As',
                it: 'Salva con nome',
                fr: 'Enregistrer sous',
                de: 'Speichern unter',
                es: 'Guardar como'
            },
            open_project: {
                en: 'Open Project',
                it: 'Apri progetto',
                fr: 'Ouvrir le projet',
                de: 'Projekt öffnen',
                es: 'Abrir proyecto'
            },
            file_name: {
                en: 'File Name',
                it: 'Nome file',
                fr: 'Nom du fichier',
                de: 'Dateiname',
                es: 'Nombre del archivo'
            },
            new_folder: {
                en: 'New Folder',
                it: 'Nuova cartella',
                fr: 'Nouveau dossier',
                de: 'Neuer Ordner',
                es: 'Nueva carpeta'
            },
            empty_folder: {
                en: 'This folder is empty',
                it: 'Questa cartella è vuota',
                fr: 'Ce dossier est vide',
                de: 'Dieser Ordner ist leer',
                es: 'Esta carpeta está vacía'
            },
            no_projects: {
                en: 'No projects found',
                it: 'Nessun progetto trovato',
                fr: 'Aucun projet trouvé',
                de: 'Keine Projekte gefunden',
                es: 'No se encontraron proyectos'
            },
            cancel: {
                en: 'Cancel',
                it: 'Annulla',
                fr: 'Annuler',
                de: 'Abbrechen',
                es: 'Cancelar'
            },
            confirm: {
                en: 'Confirm',
                it: 'Conferma',
                fr: 'Confirmer',
                de: 'Bestätigen',
                es: 'Confirmar'
            },
            ok: {
                en: 'OK',
                it: 'OK',
                fr: 'OK',
                de: 'OK',
                es: 'OK'
            }
        };

        const MESSAGE_LANG_ALIASES = { us: 'en', gb: 'en', ca: 'en', ch: 'de' };
        Object.keys(UI_MESSAGES).forEach(key => {
            const bucket = UI_MESSAGES[key];
            Object.entries(MESSAGE_LANG_ALIASES).forEach(([alias, source]) => {
                if (!bucket[alias] && bucket[source]) {
                    bucket[alias] = bucket[source];
                }
            });
        });

        function trMessage(key) {
            const bucket = UI_MESSAGES[key];
            if (!bucket) return tr(key);
            const lang = getLang();
            return bucket[lang] || bucket['en'] || tr(key);
        }

        function tr(key) {
            const lang = getLang();
            return (LANGS[lang] && LANGS[lang][key]) ? LANGS[lang][key] : (LANGS['us'][key] || key);
        }


        // --- UI BUILDERS ---
        // Classic: create fileMenu once, always present, only update innerHTML on language change
        function buildFileMenu() {
            if (!fileMenu) {
                fileMenu = document.createElement('div');
                fileMenu.className = 'cdp-file-menu';
                Object.assign(fileMenu.style, {
                    position: 'fixed',
                    zIndex: '13650',
                    display: 'none',
                    minWidth: '180px',
                    padding: '8px 0'
                });
                document.body.appendChild(fileMenu);
            }
            if (!fileMenuOverlay) {
                fileMenuOverlay = document.createElement('button');
                fileMenuOverlay.type = 'button';
                fileMenuOverlay.className = 'cdp-file-menu-overlay';
                fileMenuOverlay.setAttribute('aria-label', 'Close file menu');
                fileMenuOverlay.style.display = 'none';
                fileMenuOverlay.addEventListener('click', hideFileMenu);
                document.body.appendChild(fileMenuOverlay);
            }
            if (!fileMenuShell) {
                fileMenuShell = document.createElement('div');
                fileMenuShell.className = 'cdp-file-menu-shell';
                fileMenuShell.style.display = 'none';
                fileMenuShell.appendChild(fileMenu);
                document.body.appendChild(fileMenuShell);
            }
            // Always update content
            const menuItems = [
                { action: "new", icon: "fa fa-file", label: tr('file_new') },
                { action: "open", icon: "fa fa-folder-open", label: tr('file_open') },
                { action: "save", icon: "fa fa-save", label: tr('file_save') },
                { action: "saveas", icon: "fa fa-copy", label: tr('file_saveas') }
            ];

            fileMenu.innerHTML = menuItems.map((item) => {
                const shortcutLabel = fileShortcutMap[item.action]?.label || "";
                return `
                    <button data-action="${item.action}">
                        <span class="cdp-file-menu-label">
                            <i class="${item.icon}"></i>
                            <span>${item.label}</span>
                        </span>
                        ${shortcutLabel ? `<span class="cdp-file-menu-shortcut">${shortcutLabel}</span>` : ""}
                    </button>
                `;
            }).join("");

            const menuButtons = fileMenu.querySelectorAll('button');
            menuButtons.forEach(btn => {
                btn.style.display = "flex";
                btn.style.alignItems = "center";
                btn.style.justifyContent = "space-between";
                btn.style.gap = "12px";
                btn.style.width = "100%";

                const labelWrap = btn.querySelector('.cdp-file-menu-label');
                if (labelWrap) {
                    labelWrap.style.display = "flex";
                    labelWrap.style.alignItems = "center";
                    labelWrap.style.gap = "10px";
                }

                const shortcutEl = btn.querySelector('.cdp-file-menu-shortcut');
                if (shortcutEl) {
                    shortcutEl.style.fontSize = "11px";
                    shortcutEl.style.fontWeight = "600";
                    shortcutEl.style.color = "#9ca3af";
                }

                btn.onclick = function() {
                    handleFileAction(btn.getAttribute('data-action'));
                    fileMenu.style.display = 'none';
                };
            });
            // Bind fileBtn
            if (!fileBtn) {
                // fallback: show at top left
                fileMenu.style.top = '60px';
                fileMenu.style.left = '20px';
                fileMenu.style.display = 'block';
                console.warn('File button not found, menu shown at top left');
            }
            // Hide menu on outside click
            if (!window._cdpFileMenuOutsideClick) {
                window._cdpFileMenuOutsideClick = function(e) {
                    if (fileMenu && fileMenu.style.display === 'block') {
                        const fileBtnEl = document.querySelector('[data-tool="file"]');
                        if (!fileMenu.contains(e.target) && e.target !== fileBtnEl) {
                            fileMenu.style.display = 'none';
                        }
                    }
                };
                document.addEventListener('click', window._cdpFileMenuOutsideClick);
            }
            // Hide menu on Escape
            if (!window._cdpFileMenuEsc) {
                window._cdpFileMenuEsc = function(e) {
                    if (e.key === 'Escape' && fileMenu && fileMenu.style.display === 'block') {
                        fileMenu.style.display = 'none';
                    }
                };
                document.addEventListener('keydown', window._cdpFileMenuEsc);
            }
            if (typeof window.applyFileMenuTheme === 'function') {
                window.applyFileMenuTheme();
            }
        }

        function buildOpenModal() {
            if (openModal) {
                openModal.remove();
                openModal = null;
            }
            if (typeof createOpenModal === "function") {
                createOpenModal();
            }
        }

        function buildSaveAsModal() {
            if (saveAsModal) {
                saveAsModal.remove();
                saveAsModal = null;
            }
            if (newFolderModal) {
                newFolderModal.remove();
                newFolderModal = null;
            }
            if (typeof createSaveAsModal === "function") {
                createSaveAsModal();
            }
        }

        function syncLangFromEvent(event) {
            if (!event || !event.detail) return null;
            const detail = event.detail;
            if (detail.lang) return normalizeLang(detail.lang);
            if (detail.locale) return normalizeLang(detail.locale);
            return null;
        }

        // --- Language change event: destroy and rebuild all UI ---
        window.addEventListener('cdp-locale-changed', function(e) {
            const inferredLang = syncLangFromEvent(e);
            const effectiveLang = inferredLang || getLang();
            if (effectiveLang) {
                localStorage.setItem('cdpLang', effectiveLang);
            }
            buildFileMenu();
            buildOpenModal();
            buildSaveAsModal();
            if (typeof window.applyFileMenuTheme === 'function') {
                window.applyFileMenuTheme();
            }
            cdpApplyFileModalsTheme();
        });

        // On page load, build UI in current language
        buildFileMenu();
        buildOpenModal();
        buildSaveAsModal();
        setupFileShortcuts();

        // Expose for other scripts
        window.cdpSetLang = setLang;
        window.cdpTr = tr;
        window.cdpMessage = trMessage;
    console.log("📁 file.js loaded");

    // =========================
    // Theme helpers (Open / Save As)
    // =========================
    function cdpIsDarkMode() {
        const root = document.documentElement;
        const body = document.body;
        const attr = (root.getAttribute("data-theme") || body.getAttribute("data-theme") || "").toLowerCase();
        if (attr === "dark") return true;

        const cls = (root.className + " " + body.className).toLowerCase();
        // common patterns
        return cls.includes("dark") || cls.includes("theme-dark") || cls.includes("dark-mode") || cls.includes("cdp-dark");
    }

    function cdpFileModalColors() {
        if (cdpIsDarkMode()) {
            return {
                panelBg: "#0b0b0b",
                surface: "#121212",
                surface2: "#1a1a1a",
                border: "#2a2a2a",
                text: "#f9fafb",
                muted: "#9ca3af",
                inputBg: "#161616",
                inputText: "#f9fafb",
                inputBorder: "#2a2a2a",
                cardBg: "#121212",
                cardBorder: "#2a2a2a",
                cardHover: "#FFD600",
            };
        }
        return {
            panelBg: "#fff",
            surface: "#fff",
            surface2: "#f9fafb",
            border: "#e5e7eb",
            text: "#111827",
            muted: "#6b7280",
            inputBg: "#fff",
            inputText: "#111827",
            inputBorder: "#e5e7eb",
            cardBg: "#fff",
            cardBorder: "#e5e7eb",
            cardHover: "#FFD600",
        };
    }

    function cdpApplyFileModalsTheme() {
        const c = cdpFileModalColors();

        // ===== Open modal =====
        if (openModal) {
            const panel = openModal.querySelector(".cdp-open-panel");
            const header = openModal.querySelector(".cdp-open-header");
            const toolbar = openModal.querySelector(".cdp-open-toolbar");
            const body = openModal.querySelector(".cdp-open-body");
            const footer = openModal.querySelector(".cdp-open-footer");

            const titleEl = openModal.querySelector(".cdp-open-header h3");
            const closeBtn = openModal.querySelector(".cdp-open-close");
            const path = openModal.querySelector("#cdpOpenCurrentPath");
            const pathBox = openModal.querySelector(".cdp-open-toolbar .cdp-path-display");
            const pathIcon = pathBox ? pathBox.querySelector("i") : null;
            const emptyIcon = openModal.querySelector("[data-cdp-empty]");
            const backBtn = openModal.querySelector("#cdpOpenBack");
            const cancelBtn = openModal.querySelector(".cdp-open-cancel");

            if (panel) panel.style.background = c.surface;
            if (header) {
                header.style.background = c.surface2;
                header.style.borderBottomColor = c.border;
            }
            if (toolbar) {
                toolbar.style.background = c.surface2;
                toolbar.style.borderBottomColor = c.border;
            }
            if (body) body.style.background = c.surface;
            if (footer) {
                footer.style.background = c.surface2;
                footer.style.borderTopColor = c.border;
            }

            if (titleEl) titleEl.style.color = c.text;
            if (path) path.style.color = c.text;
            if (pathBox) {
                pathBox.style.background = c.inputBg;
                pathBox.style.borderColor = c.border;
                pathBox.style.color = c.text;
            }
            if (pathIcon) pathIcon.style.color = c.muted;
            if (pathBox) {
                pathBox.style.background = c.inputBg;
                pathBox.style.borderColor = c.border;
                pathBox.style.color = c.text;
            }
            if (pathIcon) pathIcon.style.color = c.muted;
            if (emptyIcon) emptyIcon.style.color = c.muted;

            if (closeBtn) {
                closeBtn.style.color = c.muted;
                closeBtn.style.background = "transparent";
                closeBtn.style.border = "none";
            }

            if (backBtn) {
                backBtn.style.background = c.surface2;
                backBtn.style.borderColor = c.border;
                backBtn.style.color = c.text;
            }

            if (cancelBtn) {
                cancelBtn.style.background = c.surface2;
                cancelBtn.style.borderColor = c.border;
                cancelBtn.style.color = c.text;
            }
        }

        // ===== Save As modal =====
        if (saveAsModal) {
            const panel = saveAsModal.querySelector(".cdp-saveas-panel");
            const header = saveAsModal.querySelector(".cdp-saveas-header");
            const toolbar = saveAsModal.querySelector(".cdp-saveas-toolbar");
            const body = saveAsModal.querySelector(".cdp-saveas-body");
            const footer = saveAsModal.querySelector(".cdp-saveas-footer");

            const titleEl = saveAsModal.querySelector(".cdp-saveas-header h3");
            const closeBtn = saveAsModal.querySelector(".cdp-saveas-close");
            const path = saveAsModal.querySelector("#cdpSaveAsCurrentPath");
            const pathBox = saveAsModal.querySelector(".cdp-saveas-toolbar .cdp-path-display");
            const pathIcon = pathBox ? pathBox.querySelector("i") : null;
            const emptyIcon = saveAsModal.querySelector("[data-cdp-empty]");
            const backBtn = saveAsModal.querySelector("#cdpSaveAsBack");

            const nameLabel = saveAsModal.querySelector('.cdp-saveas-input-area label');
            const nameInput = saveAsModal.querySelector("#cdpSaveAsFileName");
            const inputArea = saveAsModal.querySelector(".cdp-saveas-input-area");

            const cancelBtn = saveAsModal.querySelector(".cdp-saveas-cancel");
            const saveBtn = saveAsModal.querySelector(".cdp-saveas-save");

            const saveColors = c;

            if (panel) panel.style.background = saveColors.surface;
            if (header) {
                header.style.background = saveColors.surface2;
                header.style.borderBottomColor = saveColors.border;
            }
            if (toolbar) {
                toolbar.style.background = saveColors.surface2;
                toolbar.style.borderBottomColor = saveColors.border;
            }
            if (body) body.style.background = saveColors.surface;
            if (footer) {
                footer.style.background = saveColors.surface2;
                footer.style.borderTopColor = saveColors.border;
            }
            if (inputArea) {
                inputArea.style.background = saveColors.surface;
                inputArea.style.borderTopColor = saveColors.border;
            }

            if (titleEl) titleEl.style.color = saveColors.text;
            if (path) path.style.color = saveColors.text;
            if (pathBox) {
                pathBox.style.background = saveColors.inputBg;
                pathBox.style.borderColor = saveColors.border;
                pathBox.style.color = saveColors.text;
            }
            if (pathIcon) pathIcon.style.color = saveColors.muted;
            if (emptyIcon) emptyIcon.style.color = saveColors.muted;

            if (closeBtn) {
                closeBtn.style.color = saveColors.muted;
                closeBtn.style.background = "transparent";
                closeBtn.style.border = "none";
            }

            if (backBtn) {
                backBtn.style.background = saveColors.surface2;
                backBtn.style.borderColor = saveColors.border;
                backBtn.style.color = saveColors.text;
            }

            if (nameLabel) nameLabel.style.color = saveColors.text;
            if (nameInput) {
                nameInput.style.background = saveColors.inputBg;
                nameInput.style.color = saveColors.inputText;
                nameInput.style.borderColor = saveColors.inputBorder;
            }

            if (cancelBtn) {
                cancelBtn.style.background = saveColors.cancelBg || saveColors.surface2;
                cancelBtn.style.borderColor = saveColors.border;
                cancelBtn.style.color = saveColors.text;
            }

            if (saveBtn) {
                saveBtn.style.background = "#d9a300";
                saveBtn.style.borderColor = "#d9a300";
                saveBtn.style.color = "#ffffff";
            }
        }

        // Re-theme all folder/file cards currently rendered
        document.querySelectorAll(".cdp-folder-card").forEach(card => {
            cdpApplyFolderCardTheme(card);
        });
    }

    function cdpApplyFolderCardTheme(card) {
        if (!card) return;
        const c = cdpFileModalColors();
        card.style.background = c.cardBg;
        card.style.borderColor = c.cardBorder;

        const nameEl = card.querySelector(".cdp-item-name");
        if (nameEl) nameEl.style.color = c.text;

        const iconI = card.querySelector("i");
        if (iconI) iconI.style.color = cdpIsDarkMode() ? "#e5e7eb" : "#111827";

        // store default border for mouseleave
        card.dataset.cdpBorder = c.cardBorder;
    }

    // Keep modals synced when theme changes (class or data-theme changes)
    const cdpThemeObserver = new MutationObserver(() => cdpApplyFileModalsTheme());
    cdpThemeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ["class", "data-theme"] });
    cdpThemeObserver.observe(document.body, { attributes: true, attributeFilter: ["class", "data-theme"] });

    window.addEventListener("cdp-theme-changed", () => cdpApplyFileModalsTheme());
 // برای Open Modal جداگانه

    if (!fileBtn) {
        console.error("❌ File button not found");
        return;
    }

    // =========================
    // Message System
    // =========================

    function showMessage(message, type = "success") {
        const existingMsg = document.querySelector(".cdp-message-box");
        if (existingMsg) existingMsg.remove();

        const messageBox = document.createElement("div");
        messageBox.className = "cdp-message-box";

        const icons = {
            success: "fa-circle-check",
            error: "fa-circle-xmark",
            warning: "fa-triangle-exclamation",
            info: "fa-circle-info"
        };

        const colors = {
            success: "#22c55e",
            error: "#ef4444",
            warning: "#f59e0b",
            info: "#d9a300"
        };

        messageBox.innerHTML = `
            <div class="cdp-message-backdrop"></div>
            <div class="cdp-message-panel">
                <div class="cdp-message-icon" style="color: ${colors[type]};">
                    <i class="fa-solid ${icons[type]}"></i>
                </div>
                <div class="cdp-message-text">${message}</div>
                <button type="button" class="cdp-message-btn">${tr('ok')}</button>
            </div>
        `;

        Object.assign(messageBox.style, {
            position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
            zIndex: "15000", display: "flex", alignItems: "center", justifyContent: "center"
        });

        const backdrop = messageBox.querySelector(".cdp-message-backdrop");
        Object.assign(backdrop.style, {
            position: "absolute", top: "0", left: "0", width: "100%", height: "100%",
            background: "rgba(0, 0, 0, 0.5)", backdropFilter: "blur(4px)"
        });

        const panel = messageBox.querySelector(".cdp-message-panel");
        Object.assign(panel.style, {
            position: "relative", background: "#fff", borderRadius: "16px",
            boxShadow: "0 25px 50px rgba(0, 0, 0, 0.25)", padding: "32px",
            maxWidth: "400px", width: "90%", textAlign: "center"
        });

        const icon = messageBox.querySelector(".cdp-message-icon");
        Object.assign(icon.style, { fontSize: "56px", marginBottom: "20px" });

        const text = messageBox.querySelector(".cdp-message-text");
        Object.assign(text.style, {
            fontSize: "16px", color: "#111827", lineHeight: "1.6",
            marginBottom: "24px", fontWeight: "500"
        });

        const btn = messageBox.querySelector(".cdp-message-btn");
        Object.assign(btn.style, {
            width: "100%", padding: "12px 24px", background: colors[type],
            border: "none", borderRadius: "10px", color: "#fff",
            fontSize: "15px", fontWeight: "600", cursor: "pointer"
        });

        btn.addEventListener("click", () => messageBox.remove());
        backdrop.addEventListener("click", () => messageBox.remove());
        document.body.appendChild(messageBox);

        setTimeout(() => {
            if (document.body.contains(messageBox)) messageBox.remove();
        }, 3000);
    }

    function showConfirm(message, onConfirm, onCancel) {
        const confirmBox = document.createElement("div");
        
        confirmBox.innerHTML = `
            <div class="cdp-confirm-backdrop"></div>
            <div class="cdp-confirm-panel">
                <div class="cdp-confirm-icon"><i class="fa-solid fa-circle-question"></i></div>
                <div class="cdp-confirm-text">${message}</div>
                <div class="cdp-confirm-buttons">
                    <button type="button" class="cdp-confirm-cancel">${tr('cancel')}</button>
                    <button type="button" class="cdp-confirm-ok">${tr('confirm')}</button>
                </div>
            </div>
        `;

        Object.assign(confirmBox.style, {
            position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
            zIndex: "15000", display: "flex", alignItems: "center", justifyContent: "center"
        });

        const backdrop = confirmBox.querySelector(".cdp-confirm-backdrop");
        Object.assign(backdrop.style, {
            position: "absolute", top: "0", left: "0", width: "100%", height: "100%",
            background: "rgba(0, 0, 0, 0.5)", backdropFilter: "blur(4px)"
        });

        const panel = confirmBox.querySelector(".cdp-confirm-panel");
        Object.assign(panel.style, {
            position: "relative", background: "#fff", borderRadius: "16px",
            boxShadow: "0 25px 50px rgba(0, 0, 0, 0.25)", padding: "32px",
            maxWidth: "420px", width: "90%", textAlign: "center"
        });

        const icon = confirmBox.querySelector(".cdp-confirm-icon");
        Object.assign(icon.style, { fontSize: "56px", color: "#f59e0b", marginBottom: "20px" });

        const text = confirmBox.querySelector(".cdp-confirm-text");
        Object.assign(text.style, {
            fontSize: "16px", color: "#111827", lineHeight: "1.6",
            marginBottom: "24px", fontWeight: "500"
        });

        const buttonsDiv = confirmBox.querySelector(".cdp-confirm-buttons");
        Object.assign(buttonsDiv.style, { display: "flex", gap: "12px" });

        const cancelBtn = confirmBox.querySelector(".cdp-confirm-cancel");
        const okBtn = confirmBox.querySelector(".cdp-confirm-ok");

        Object.assign(cancelBtn.style, {
            flex: "1", padding: "12px 24px", background: "#f3f4f6",
            border: "none", borderRadius: "10px", color: "#374151",
            fontSize: "15px", fontWeight: "600", cursor: "pointer"
        });

        Object.assign(okBtn.style, {
            flex: "1", padding: "12px 24px", background: "#d9a300",
            border: "none", borderRadius: "10px", color: "#fff",
            fontSize: "15px", fontWeight: "600", cursor: "pointer"
        });

        cancelBtn.addEventListener("click", () => {
            if (onCancel) onCancel();
            confirmBox.remove();
        });

        okBtn.addEventListener("click", () => {
            if (onConfirm) onConfirm();
            confirmBox.remove();
        });

        backdrop.addEventListener("click", () => {
            if (onCancel) onCancel();
            confirmBox.remove();
        });

        document.body.appendChild(confirmBox);
    }

    // =========================
    // File Menu
    // =========================

    function createFileMenu() {
        if (!fileMenu) {
            buildFileMenu();
        }
    }

    function positionFileMenu() {
        if (!fileMenu) return;
        const isCompactViewport = window.matchMedia('(max-width: 1024px)').matches;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

        if (isCompactViewport) {
            const menuWidth = Math.min(Math.max(viewportWidth - 24, 240), 340);

            fileMenu.classList.add('cdp-file-menu--compact');
            if (fileMenuShell && fileMenu.parentElement !== fileMenuShell) {
                fileMenuShell.appendChild(fileMenu);
            }
            if (fileMenuShell) {
                fileMenuShell.style.display = 'flex';
            }
            fileMenu.style.width = menuWidth + 'px';
            fileMenu.style.maxWidth = 'calc(100vw - 24px)';
            fileMenu.style.top = '0';
            fileMenu.style.left = '0';
            fileMenu.style.right = 'auto';
            fileMenu.style.maxHeight = 'min(360px, calc(100vh - 120px))';
            fileMenu.style.overflowY = 'auto';
            fileMenu.style.borderRadius = '18px';
            fileMenu.style.margin = '0 auto';
            fileMenu.style.transform = 'none';
            return;
        }

        const rect = fileBtn.getBoundingClientRect();
        const top = rect.bottom + 5;
        const menuWidth = Math.max(fileMenu.offsetWidth || 180, 180);
        const left = Math.min(rect.left, Math.max(12, viewportWidth - menuWidth - 12));

        fileMenu.classList.remove('cdp-file-menu--compact');
        if (fileMenuShell) {
            fileMenuShell.style.display = 'none';
        }
        if (fileMenu.parentElement !== document.body) {
            document.body.appendChild(fileMenu);
        }
        fileMenu.style.width = '';
        fileMenu.style.maxWidth = '';
        fileMenu.style.top = top + "px";
        fileMenu.style.left = left + "px";
        fileMenu.style.right = 'auto';
        fileMenu.style.maxHeight = '';
        fileMenu.style.overflowY = '';
        fileMenu.style.borderRadius = '';
        fileMenu.style.margin = '';
        fileMenu.style.transform = '';
    }

    function showFileMenu() {
        createFileMenu();
        document.body.classList.add('cdp-file-menu-open');
        if (window.matchMedia('(max-width: 1024px)').matches) {
            document.body.classList.remove('cdp-mobile-menu-open');
            if (fileMenuOverlay) {
                fileMenuOverlay.style.display = 'block';
            }
        }
        positionFileMenu();
        fileMenu.style.display = "block";
    }

    function hideFileMenu() {
        document.body.classList.remove('cdp-file-menu-open');
        if (fileMenu) {
            fileMenu.style.display = "none";
            fileMenu.classList.remove('cdp-file-menu--compact');
        }
        if (fileMenuShell) {
            fileMenuShell.style.display = 'none';
        }
        if (fileMenuOverlay) {
            fileMenuOverlay.style.display = 'none';
        }
    }

    // =========================
    // Folder Structure
    // =========================

    function normalizeFolderStructureNode(node) {
        const safeNode = node && typeof node === "object" ? node : {};
        const rawFolders = safeNode.folders && typeof safeNode.folders === "object" ? safeNode.folders : {};
        const normalizedFolders = {};

        Object.keys(rawFolders).forEach(folderName => {
            normalizedFolders[folderName] = normalizeFolderStructureNode(rawFolders[folderName]);
        });

        const rawFiles = Array.isArray(safeNode.files) ? safeNode.files : [];

        return {
            folders: normalizedFolders,
            files: rawFiles
                .map(entry => String(entry || "").trim())
                .filter(Boolean)
        };
    }

    function getFolderStructure() {
        const structure = localStorage.getItem("cdpFolderStructure");
        if (!structure) {
            return { folders: {}, files: [] };
        }

        try {
            return normalizeFolderStructureNode(JSON.parse(structure));
        } catch (_error) {
            return { folders: {}, files: [] };
        }
    }

    function saveFolderStructure(structure) {
        localStorage.setItem("cdpFolderStructure", JSON.stringify(normalizeFolderStructureNode(structure)));
    }

    async function cdpRemoteStorageRequest(action, payload = {}, method = "POST") {
        if (!cdpRemoteStorageSupported) {
            return { success: false, available: false, message: "Server storage is unavailable." };
        }

        const url = new URL(cdpRemoteStorageUrl, window.location.origin);
        const requestInit = {
            method,
            credentials: "same-origin",
            headers: {
                Accept: "application/json"
            }
        };

        if (method === "GET") {
            url.searchParams.set("action", action);
            Object.entries(payload).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    url.searchParams.set(key, String(value));
                }
            });
        } else {
            const formData = new FormData();
            formData.append("action", action);
            Object.entries(payload).forEach(([key, value]) => {
                if (value === undefined || value === null) {
                    return;
                }

                if (typeof value === "object") {
                    formData.append(key, JSON.stringify(value));
                    return;
                }

                formData.append(key, String(value));
            });
            requestInit.body = formData;
        }

        try {
            const response = await fetch(url.toString(), requestInit);
            const text = await response.text();
            let parsed = {};

            if (text) {
                try {
                    parsed = JSON.parse(text);
                } catch (_error) {
                    parsed = {};
                }
            }

            return {
                ...parsed,
                success: Boolean(parsed.success),
                available: parsed.available !== false && response.status !== 404,
                status: response.status
            };
        } catch (_error) {
            return { success: false, available: false, message: "Server storage request failed." };
        }
    }

    function cdpApplyRemoteStructure(structure) {
        if (structure && typeof structure === "object") {
            saveFolderStructure(normalizeFolderStructureNode(structure));
        }
    }

    async function cdpSyncFolderStructureFromServer() {
        const response = await cdpRemoteStorageRequest("structure", {}, "GET");
        if (response.success && response.structure) {
            cdpApplyRemoteStructure(response.structure);
            return true;
        }
        return false;
    }

    async function cdpSyncProjectFromServer(projectPath) {
        const response = await cdpRemoteStorageRequest("load", { path: projectPath }, "GET");
        if (!response.success || !response.data) {
            return false;
        }

        localStorage.setItem(`cdpProject_${projectPath}`, JSON.stringify(response.data));
        if (response.structure) {
            cdpApplyRemoteStructure(response.structure);
        }
        ensureProjectInFolderStructure(projectPath);
        return true;
    }

    async function cdpSaveProjectToServer(projectPath, data) {
        return cdpRemoteStorageRequest("save", { path: projectPath, data });
    }

    async function cdpPersistFolderStructureToServer(structure) {
        return cdpRemoteStorageRequest("save-structure", { structure });
    }

    async function cdpDeleteProjectFromServer(projectPath) {
        return cdpRemoteStorageRequest("delete-project", { path: projectPath });
    }

    async function cdpDeleteFolderFromServer(folderPath) {
        return cdpRemoteStorageRequest("delete-folder", { path: folderPath });
    }

    function ensureProjectInFolderStructure(projectPath) {
        const normalizedPath = String(projectPath || "").split('/').filter(Boolean);
        if (!normalizedPath.length) return;

        const fileName = normalizedPath.pop();
        const structure = getFolderStructure();
        let current = structure;

        normalizedPath.forEach(part => {
            if (!current.folders) current.folders = {};
            if (!current.folders[part]) {
                current.folders[part] = { folders: {}, files: [] };
            }
            current = current.folders[part];
        });

        if (!current.files) current.files = [];
        if (!current.files.includes(fileName)) {
            current.files.push(fileName);
        }

        saveFolderStructure(structure);
    }

    function getFolderContents(path) {
        const structure = getFolderStructure();
        if (!path) return structure;

        const parts = path.split('/').filter(p => p);
        let current = structure;

        for (const part of parts) {
            if (current.folders && current.folders[part]) {
                current = current.folders[part];
            } else {
                return { folders: {}, files: [] };
            }
        }

        return current;
    }

    function removeProjectFromFolderStructure(projectPath) {
        const normalizedPath = String(projectPath || "").split('/').filter(Boolean);
        if (!normalizedPath.length) {
            return false;
        }

        const fileName = normalizedPath.pop();
        const structure = getFolderStructure();
        let current = structure;

        for (const part of normalizedPath) {
            if (!current.folders || !current.folders[part]) {
                return false;
            }
            current = current.folders[part];
        }

        if (!Array.isArray(current.files)) {
            return false;
        }

        const nextFiles = current.files.filter(entry => entry !== fileName);
        if (nextFiles.length === current.files.length) {
            return false;
        }

        current.files = nextFiles;
        saveFolderStructure(structure);
        return true;
    }

    function collectProjectsFromFolderNode(node, prefix = "") {
        if (!node || typeof node !== "object") {
            return [];
        }

        const results = [];
        const files = Array.isArray(node.files) ? node.files : [];
        files.forEach(fileName => {
            if (fileName) {
                results.push(prefix ? `${prefix}/${fileName}` : fileName);
            }
        });

        const folders = node.folders && typeof node.folders === "object" ? node.folders : {};
        Object.keys(folders).forEach(folderName => {
            const nextPrefix = prefix ? `${prefix}/${folderName}` : folderName;
            results.push(...collectProjectsFromFolderNode(folders[folderName], nextPrefix));
        });

        return results;
    }

    async function deleteProjectAtPath(projectPath) {
        const normalizedPath = String(projectPath || "").trim();
        if (!normalizedPath) {
            return false;
        }

        if (cdpRemoteStorageSupported) {
            const response = await cdpDeleteProjectFromServer(normalizedPath);
            if (response.success && response.structure) {
                cdpApplyRemoteStructure(response.structure);
            } else if (response.available !== false) {
                showMessage(response.message || 'Unable to delete the file on the server.', 'error');
                return false;
            }
        }

        localStorage.removeItem(`cdpProject_${normalizedPath}`);
        removeProjectFromFolderStructure(normalizedPath);
        return true;
    }

    async function deleteFolderAtPath(folderPath) {
        const normalizedPath = String(folderPath || "").split('/').filter(Boolean);
        if (!normalizedPath.length) {
            return false;
        }

        if (cdpRemoteStorageSupported) {
            const response = await cdpDeleteFolderFromServer(normalizedPath.join('/'));
            if (response.success && response.structure) {
                cdpApplyRemoteStructure(response.structure);
            } else if (response.available !== false) {
                showMessage(response.message || 'Unable to delete the folder on the server.', 'error');
                return false;
            }
        }

        const structure = getFolderStructure();
        let current = structure;
        for (let index = 0; index < normalizedPath.length - 1; index += 1) {
            const part = normalizedPath[index];
            if (!current.folders || !current.folders[part]) {
                return false;
            }
            current = current.folders[part];
        }

        const folderName = normalizedPath[normalizedPath.length - 1];
        const folderNode = current.folders && current.folders[folderName];
        if (!folderNode) {
            return false;
        }

        collectProjectsFromFolderNode(folderNode, normalizedPath.join('/')).forEach(projectPath => {
            localStorage.removeItem(`cdpProject_${projectPath}`);
        });

        delete current.folders[folderName];
        saveFolderStructure(structure);
        return true;
    }

    function hideOpenContextMenu() {
        if (!openContextMenu) {
            return;
        }
        openContextMenu.hidden = true;
        openContextMenu.style.display = "none";
        openContextMenu.dataset.targetPath = "";
        openContextMenu.dataset.targetType = "";
    }

    function ensureOpenContextMenu() {
        if (openContextMenu) {
            return openContextMenu;
        }

        openContextMenu = document.createElement("div");
        openContextMenu.hidden = true;
        openContextMenu.innerHTML = '<button type="button" class="cdp-open-context-delete"><i class="fa-solid fa-trash"></i><span>Delete</span></button>';
        Object.assign(openContextMenu.style, {
            position: "fixed",
            zIndex: "12050",
            minWidth: "132px",
            background: "#ffffff",
            border: "1px solid #e5e7eb",
            borderRadius: "10px",
            boxShadow: "0 18px 36px rgba(15, 23, 42, 0.18)",
            padding: "6px",
            display: "none",
        });

        const deleteBtn = openContextMenu.querySelector(".cdp-open-context-delete");
        Object.assign(deleteBtn.style, {
            width: "100%",
            border: "none",
            background: "transparent",
            color: "#991b1b",
            borderRadius: "8px",
            cursor: "pointer",
            display: "flex",
            alignItems: "center",
            gap: "10px",
            padding: "10px 12px",
            fontSize: "14px",
            fontWeight: "600",
        });

        deleteBtn.addEventListener("mouseenter", () => {
            deleteBtn.style.background = "#fef2f2";
        });

        deleteBtn.addEventListener("mouseleave", () => {
            deleteBtn.style.background = "transparent";
        });

        deleteBtn.addEventListener("click", async () => {
            const targetPath = openContextMenu.dataset.targetPath || "";
            const isFolder = openContextMenu.dataset.targetType === "folder";
            hideOpenContextMenu();
            if (!targetPath) {
                return;
            }
            const confirmed = window.confirm(isFolder ? `Delete folder "${targetPath}" and all its files?` : `Delete file "${targetPath}"?`);
            if (!confirmed) {
                return;
            }
            const deleted = isFolder ? await deleteFolderAtPath(targetPath) : await deleteProjectAtPath(targetPath);
            if (!deleted) {
                showMessage('Item not found.', 'error');
                return;
            }
            displayOpenContents(currentOpenFolder);
            showMessage(isFolder ? 'Folder deleted.' : 'File deleted.', 'success');
        });

        document.body.appendChild(openContextMenu);
        document.addEventListener("click", hideOpenContextMenu);
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                hideOpenContextMenu();
            }
        });
        return openContextMenu;
    }

    function showOpenContextMenu(x, y, targetPath, isFolder) {
        const menu = ensureOpenContextMenu();
        menu.dataset.targetPath = targetPath;
        menu.dataset.targetType = isFolder ? "folder" : "file";
        menu.hidden = false;
        menu.style.display = "block";
        menu.style.left = `${x}px`;
        menu.style.top = `${y}px`;
    }

    // =========================
    // Create Folder Card
    // =========================

    function createFolderCard(name, isFolder = true, onClick = null, activationEvent = "dblclick", options = {}) {
        const card = document.createElement("div");
        const safeName = String(name || "");

        
        card.classList.add("cdp-folder-card");
        card.innerHTML = `
            <div class="cdp-item-icon">
                ${isFolder
                    ? `<svg viewBox="0 0 64 64" aria-hidden="true" style="width:48px;height:48px;display:block;">
                        <path d="M8 18a6 6 0 0 1 6-6h12l6 6h18a6 6 0 0 1 6 6v4H8z" fill="#f4bf3a"></path>
                        <path d="M8 24h48a4 4 0 0 1 3.89 4.95l-4.5 18A6 6 0 0 1 49.57 52H14.43a6 6 0 0 1-5.82-5.05l-4.5-18A4 4 0 0 1 8 24z" fill="#e0a21a"></path>
                        <path d="M10 26h46" stroke="#ffd56a" stroke-width="2" stroke-linecap="round"></path>
                        <path d="M17 17h10.5l4 4" stroke="#ffe6a3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>`
                    : `<i class="fa-solid fa-file"></i>`}
            </div>
            <div class="cdp-item-name"></div>
        `;

        // Make card a perfect square (3x3)
        Object.assign(card.style, {
            background: "#fff", border: "2px solid #e5e7eb",
            borderRadius: "0px", width: "96px", height: "96px",
            padding: "0px", cursor: "pointer",
            transition: "border-color 0.2s",
            display: "flex", flexDirection: "column",
            alignItems: "center", justifyContent: "center",
            gap: "6px", textAlign: "center", boxShadow: "none"
        });

        

        // Apply theme-aware colors
        cdpApplyFolderCardTheme(card);
const icon = card.querySelector(".cdp-item-icon");
        Object.assign(icon.style, {
            width: "48px", height: "48px",
            background: "none",
            borderRadius: "8px", display: "flex",
            alignItems: "center", justifyContent: "center"
        });

        const nameEl = card.querySelector(".cdp-item-name");
        if (nameEl) {
            nameEl.textContent = safeName;
        }
        Object.assign(nameEl.style, {
            fontSize: "13px", fontWeight: "600",
            color: cdpIsDarkMode() ? "#f9fafb" : "#222",
            overflow: "hidden", textOverflow: "ellipsis",
            whiteSpace: "nowrap", width: "90px", maxWidth: "90px"
        });

        card.addEventListener("mouseenter", () => {
            card.style.borderColor = "#FFD600";
        });

        card.addEventListener("mouseleave", () => {
            card.style.borderColor = card.dataset.cdpBorder || "#e5e7eb";
        });
        if (options.contextPath) {
            card.addEventListener("contextmenu", (event) => {
                event.preventDefault();
                event.stopPropagation();
                showOpenContextMenu(event.clientX, event.clientY, options.contextPath, isFolder);
            });
        }
if (onClick) {
            card.addEventListener(activationEvent, onClick);
        }

        return card;
    }

    // =========================
    // Open Modal
    // =========================

    function createOpenModal() {
        if (openModal) return;

        openModal = document.createElement("div");
        openModal.hidden = true;

        openModal.innerHTML = `
            <div class="cdp-open-backdrop"></div>
            <div class="cdp-open-panel">
                <header class="cdp-open-header">
                    <h3><i class="fa-regular fa-folder-open"></i> ${tr('open_title')}</h3>
                    <button type="button" class="cdp-open-close"><i class="fa-solid fa-xmark"></i></button>
                </header>
                <div class="cdp-open-toolbar">
                    <button type="button" id="cdpOpenBack" class="cdp-toolbar-btn">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div class="cdp-path-display">
                        <i class="fa-regular fa-folder" style="color: #9ca3af;" data-cdp-empty></i>
                        <span id="cdpOpenCurrentPath">${tr('root')}</span>
                    </div>
                </div>
                <div class="cdp-open-body">
                    <div id="cdpOpenGrid" class="cdp-open-grid"></div>
                </div>
                <footer class="cdp-open-footer">
                    <button type="button" class="cdp-open-cancel">${tr('cancel')}</button>
                </footer>
            </div>
        `;

        styleOpenModal();
        document.body.appendChild(openModal);
        openModal.dataset.cdpLang = getLang();

        const closeBtn = openModal.querySelector(".cdp-open-close");
        const cancelBtn = openModal.querySelector(".cdp-open-cancel");
        const backdrop = openModal.querySelector(".cdp-open-backdrop");
        const backBtn = openModal.querySelector("#cdpOpenBack");

        closeBtn.addEventListener("click", closeOpenModal);
        cancelBtn.addEventListener("click", closeOpenModal);
        backdrop.addEventListener("click", closeOpenModal);
        backBtn.addEventListener("click", handleOpenBack);
    }

    function styleOpenModal() {
        Object.assign(openModal.style, {
            position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
            zIndex: "11000", display: "none", alignItems: "center", justifyContent: "center"
        });

        const backdrop = openModal.querySelector(".cdp-open-backdrop");
        Object.assign(backdrop.style, {
            position: "absolute", top: "0", left: "0", width: "100%", height: "100%",
            background: "rgba(0, 0, 0, 0.5)", backdropFilter: "blur(4px)"
        });

        const panel = openModal.querySelector(".cdp-open-panel");
        Object.assign(panel.style, {
            position: "relative", background: "#fff", borderRadius: "12px",
            boxShadow: "0 25px 50px rgba(0,0,0,0.25)", width: "800px",
            maxWidth: "90vw", maxHeight: "80vh", display: "flex",
            flexDirection: "column", overflow: "hidden"
        });

        const header = openModal.querySelector(".cdp-open-header");
        Object.assign(header.style, {
            padding: "20px 24px", borderBottom: "1px solid #e5e7eb",
            display: "flex", alignItems: "center",
            justifyContent: "space-between", background: "#fff"
        });

        const h3 = header.querySelector("h3");
        Object.assign(h3.style, {
            margin: "0", fontSize: "18px", fontWeight: "600",
            color: "#111827", display: "flex",
            alignItems: "center", gap: "10px"
        });

        const closeBtn = openModal.querySelector(".cdp-open-close");
        Object.assign(closeBtn.style, {
            background: "transparent", border: "none",
            width: "32px", height: "32px", borderRadius: "6px",
            cursor: "pointer", fontSize: "18px",
            color: "#6b7280", transition: "all 0.2s"
        });

        const toolbar = openModal.querySelector(".cdp-open-toolbar");
        Object.assign(toolbar.style, {
            padding: "12px 24px", background: "#fff",
            borderBottom: "1px solid #e5e7eb", display: "flex",
            alignItems: "center", gap: "12px"
        });

        const backBtn = toolbar.querySelector("#cdpOpenBack");
        Object.assign(backBtn.style, {
            background: "#fff", border: "1px solid #d1d5db",
            padding: "8px 12px", borderRadius: "6px",
            cursor: "pointer", fontSize: "14px",
            color: "#374151", transition: "all 0.2s"
        });

        const pathDisplay = toolbar.querySelector(".cdp-path-display");
        Object.assign(pathDisplay.style, {
            flex: "1", padding: "8px 12px",
            background: "#f9fafb", border: "1px solid #e5e7eb",
            borderRadius: "6px", fontSize: "13px",
            color: "#374151", display: "flex",
            alignItems: "center", gap: "8px"
        });

        const body = openModal.querySelector(".cdp-open-body");
        Object.assign(body.style, {
            padding: "24px", flex: "1", overflowY: "auto", background: "#fff"
        });

        const grid = body.querySelector("#cdpOpenGrid");
        Object.assign(grid.style, {
            display: "grid",
            gridTemplateColumns: "repeat(auto-fill, minmax(96px, 1fr))",
            gap: "5px", minHeight: "300px"
        });

        const footer = openModal.querySelector(".cdp-open-footer");
        Object.assign(footer.style, {
            padding: "16px 24px", borderTop: "1px solid #e5e7eb",
            display: "flex", justifyContent: "flex-end",
            gap: "12px", background: "#fff"
        });

        const cancelBtn = footer.querySelector(".cdp-open-cancel");
        Object.assign(cancelBtn.style, {
            padding: "10px 20px", background: "#f3f4f6",
            border: "none", borderRadius: "8px",
            cursor: "pointer", fontSize: "14px",
            fontWeight: "500", color: "#374151"
        });
    
        cdpApplyFileModalsTheme();
}

    function displayOpenContents(path) {
        const grid = openModal.querySelector("#cdpOpenGrid");
        const pathDisplay = openModal.querySelector("#cdpOpenCurrentPath");
        const backBtn = openModal.querySelector("#cdpOpenBack");

        grid.innerHTML = "";
        pathDisplay.textContent = path || tr('root');
        backBtn.disabled = !path;
        backBtn.style.opacity = path ? "1" : "0.5";
        backBtn.style.cursor = path ? "pointer" : "not-allowed";

        const contents = getFolderContents(path);

        if (Object.keys(contents.folders).length === 0 && contents.files.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #9ca3af;" data-cdp-empty>
                    <i class="fa-regular fa-folder-open" style="font-size: 56px; margin-bottom: 16px; display: block; opacity: 0.5;"></i>
                    <p style="font-size: 15px; margin: 0; font-weight: 500;">${tr('empty_folder_title')}</p>
                    <p style="font-size: 13px; margin: 8px 0 0 0; opacity: 0.7;">${tr('no_projects')}</p>
                </div>
            `;
            return;
        }

        // نمایش پوشه‌ها
        Object.keys(contents.folders).forEach(folderName => {
            const newPath = path ? `${path}/${folderName}` : folderName;
            const card = createFolderCard(folderName, true, () => {
                currentOpenFolder = newPath;
                displayOpenContents(newPath);
            }, "dblclick", { contextPath: newPath });
            grid.appendChild(card);
        });

        // نمایش فایل‌ها
        contents.files.forEach(fileName => {
            const fullPath = path ? `${path}/${fileName}` : fileName;
            const card = createFolderCard(fileName, false, () => {
                loadProject(fullPath);
            }, "dblclick", { contextPath: fullPath });
            grid.appendChild(card);
        });
    }

    function handleOpenBack() {
        if (!currentOpenFolder) return;
        const parts = currentOpenFolder.split('/').filter(p => p);
        parts.pop();
        currentOpenFolder = parts.join('/');
        displayOpenContents(currentOpenFolder);
    }

    function showOpenModal() {
        createOpenModal();
        currentOpenFolder = "";
        displayOpenContents(currentOpenFolder);
        
        openModal.hidden = false;
        openModal.style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeOpenModal() {
        if (openModal) {
            openModal.hidden = true;
            openModal.style.display = "none";
            document.body.style.overflow = "";
        }
    }

    function createUploadElementFromStructuredData(layer) {
        const upload = layer?.upload;
        if (!upload) {
            return null;
        }

        const savedTransform = upload.transform || {};
        const strippedTransform = savedTransform.transformSansTranslate || stripTranslateFromTransform(savedTransform.transform || '');
        const useRenderedPosition = Number.isFinite(savedTransform.renderedLeft)
            && Number.isFinite(savedTransform.renderedTop)
            && !strippedTransform;
        const explicitWidth = savedTransform.width || (upload.width ? `${upload.width}px` : '200px');
        const explicitHeight = hasExplicitPixelSize(savedTransform.height)
            ? savedTransform.height
            : 'auto';
        const hasExplicitHeight = hasExplicitPixelSize(explicitHeight);

        const wrapper = document.createElement('div');
        wrapper.className = 'cdp-uploaded-image cdp-design-element';
        wrapper.id = `uploaded-${upload.layerId || Date.now()}`;
        wrapper.style.position = 'absolute';
        wrapper.style.cursor = 'grab';
        wrapper.style.userSelect = 'none';
        wrapper.style.pointerEvents = 'auto';
        wrapper.style.left = useRenderedPosition ? `${savedTransform.renderedLeft}px` : (savedTransform.left || '50%');
        wrapper.style.top = useRenderedPosition ? `${savedTransform.renderedTop}px` : (savedTransform.top || '50%');
        wrapper.style.transform = useRenderedPosition ? strippedTransform : (savedTransform.transform || 'translate(-50%, -50%)');
        wrapper.style.transformOrigin = savedTransform.transformOrigin || 'center';
        wrapper.style.zIndex = savedTransform.zIndex || '';
        wrapper.style.width = explicitWidth;
        wrapper.style.height = hasExplicitHeight ? explicitHeight : 'auto';
        wrapper.style.opacity = String(typeof upload.opacity === 'number' ? upload.opacity : 1);
        wrapper.dataset.originalSrc = upload.originalSrc || '';
        wrapper.dataset.optimizedSrc = upload.optimizedSrc || '';
        wrapper.dataset.originalBackup = upload.optimizedSrc || upload.originalSrc || '';
        wrapper.dataset.uploadName = upload.uploadName || 'Uploaded Image';
        wrapper.dataset.uploadType = upload.uploadType || 'image/jpeg';
        wrapper.dataset.layerType = layer.type || 'upload';
        wrapper.dataset.layerName = layer.name || upload.uploadName || 'Uploaded Image';
        wrapper.dataset.layerView = layer.view || upload.view || 'front';
        wrapper.dataset.locked = upload.locked ? 'true' : 'false';
        if (layer.layerId !== undefined && layer.layerId !== null) {
            wrapper.dataset.layerId = String(layer.layerId);
        }

        const imageEl = document.createElement('img');
        imageEl.src = upload.optimizedSrc || upload.originalSrc || '';
        imageEl.style.display = 'block';
        imageEl.style.pointerEvents = 'none';
        imageEl.style.width = '100%';
        imageEl.style.height = hasExplicitHeight ? explicitHeight : 'auto';
        imageEl.dataset.originalSrc = upload.originalSrc || '';
        imageEl.dataset.optimizedSrc = upload.optimizedSrc || '';
        wrapper.appendChild(imageEl);
        return wrapper;
    }

    function resolveProjectInitialView(parsed) {
        const savedView = parsed?.product?.view
            || parsed?.view
            || (Array.isArray(parsed?.layers) && parsed.layers.find(layer => layer && layer.view)?.view)
            || (Array.isArray(parsed?.layerMeta) && parsed.layerMeta.find(layer => layer && layer.view)?.view)
            || "front";
        return ["front", "back", "right", "left"].includes(savedView) ? savedView : "front";
    }

    function applyProjectView(view) {
        const normalizedView = ["front", "back", "right", "left"].includes(view) ? view : "front";
        const viewButton = document.querySelector(`.cdp-view-btn[data-view="${view}"]`);
        if (viewButton) {
            viewButton.click();
        }

        const layersViewLabel = document.getElementById("cdpLayersViewLabel");
        const boxes = Array.from(document.querySelectorAll(".cdp-print-box"));
        const buttonList = Array.from(document.querySelectorAll(".cdp-view-btn"));

        boxes.forEach((box) => {
            const boxView = box.dataset.view || "front";
            const isActiveView = boxView === normalizedView;
            box.classList.toggle("cdp-print-box--hidden", !isActiveView);
            box.style.display = isActiveView ? "block" : "none";
        });

        buttonList.forEach((btn) => {
            btn.classList.toggle("cdp-view-btn--active", btn.dataset.view === normalizedView);
        });

        if (layersViewLabel) {
            layersViewLabel.textContent = normalizedView.charAt(0).toUpperCase() + normalizedView.slice(1);
        }

        const shirtImage = document.getElementById("cdpShirtImage");
        if (shirtImage) {
            shirtImage.dataset.view = normalizedView;
        }
        if (!window.cdpState) {
            window.cdpState = {};
        }
        window.cdpState.currentView = normalizedView;
    }

    function normalizePrintBoxes() {
        const viewById = {
            boxFront: "front",
            boxBack: "back",
            boxRight: "right",
            boxLeft: "left",
        };

        return Array.from(document.querySelectorAll(".cdp-print-box")).map((box) => {
            box.innerHTML = "";
            box.removeAttribute("style");
            box.classList.remove("cdp-print-box--hidden");

            const fallbackView = viewById[box.id] || "front";
            box.dataset.view = box.dataset.view || fallbackView;
            return box;
        });
    }

    function consumePendingProjectPath() {
        return localStorage.getItem(pendingProjectPathKey) || "";
    }

    function clearPendingProjectPath() {
        localStorage.removeItem(pendingProjectPathKey);
    }

    function canAutoLoadPendingProject() {
        return Boolean(
            document.querySelector(".cdp-print-box")
            && document.getElementById("cdpShirtImage")
            && window.cdpLayers
            && typeof window.cdpLayers.refreshFromDOM === "function"
        );
    }

    function tryAutoLoadPendingProject(attempt = 0) {
        const pendingPath = consumePendingProjectPath();
        if (!pendingPath) {
            return;
        }
        if (!localStorage.getItem(`cdpProject_${pendingPath}`)) {
            clearPendingProjectPath();
            return;
        }
        if (!canAutoLoadPendingProject()) {
            if (attempt < 20) {
                setTimeout(() => tryAutoLoadPendingProject(attempt + 1), 75);
            }
            return;
        }
        loadProject(pendingPath);
        clearPendingProjectPath();
    }

    async function loadProject(projectPath) {
        if (cdpRemoteStorageSupported) {
            await cdpSyncProjectFromServer(projectPath);
        }

        const key = `cdpProject_${projectPath}`;
        const data = localStorage.getItem(key);
        
        if (!data) {
            showMessage(tr('project_not_found'), "error");
            return;
        }

        const parsed = JSON.parse(data);
        const expiryDate = new Date(parsed.expiryDate);
        
        if (new Date() > expiryDate) {
            showMessage(tr('project_expired_limit'), "warning");
            localStorage.removeItem(key);
            return;
        }

        // پاک کردن طرح فعلی
        const boxes = normalizePrintBoxes();
        if (window.cdpLayers && typeof window.cdpLayers.clearAll === "function") {
            window.cdpLayers.clearAll();
        } else if (window.cdpLayers && window.cdpLayers.layers) {
            window.cdpLayers.layers = [];
        }

        const viewLookup = new Map();
        boxes.forEach(box => {
            if (box.dataset?.view) {
                viewLookup.set(box.dataset.view, box);
            }
        });
        const fallbackViews = ["front", "back", "right", "left"];

        // بارگذاری لایه‌ها
        if (Array.isArray(parsed.layers) && parsed.layers.length > 0) {
            parsed.layers.forEach(layer => {
                if (!layer) return;
                const targetView = layer.view || fallbackViews[layer.box] || "front";
                const targetBox = viewLookup.get(targetView) || boxes[layer.box] || boxes[0];
                if (!targetBox) return;
                let appended = null;
                if (layer.type === 'upload' && layer.upload) {
                    appended = createUploadElementFromStructuredData(layer);
                    if (appended) {
                        targetBox.appendChild(appended);
                    }
                }
                if (!appended && layer.html) {
                    targetBox.insertAdjacentHTML('beforeend', layer.html);
                    appended = targetBox.lastElementChild;
                }
                if (appended) {
                    if (layer.layerId !== undefined && layer.layerId !== null && !Number.isNaN(Number(layer.layerId))) {
                        appended.dataset.layerId = String(layer.layerId);
                    }
                    appended.dataset.layerView = targetView;
                }
            });
        }

        // بارگذاری Note
        if (parsed.note) {
            localStorage.setItem("cdpNote", parsed.note);
            const noteText = document.getElementById("cdpNoteText");
            if (noteText) noteText.value = parsed.note;
        }

        // بارگذاری Product
        if (parsed.product) {
            const productName = document.querySelector(".cdp-product-name");
            const sizeValue = document.getElementById("cdpSizeValue");
            const colorName = document.getElementById("cdpColorName");
            if (productName && parsed.product.name) productName.textContent = parsed.product.name;
            if (sizeValue) sizeValue.textContent = parsed.product.size;
            if (colorName) colorName.textContent = parsed.product.color;
        }

        applyProjectView(resolveProjectInitialView(parsed));

        const parts = projectPath.split('/');
        const fileName = parts.pop();
        currentProjectName = parsed.projectName || fileName;
        currentFolder = parts.join('/');
        
        localStorage.setItem("cdpCurrentProject", currentProjectName);
        localStorage.setItem("cdpCurrentFolder", currentFolder);

        closeOpenModal();
        const loadedMessage = tr('project_loaded_named').replace('{name}', fileName);
        showMessage(loadedMessage, "success");

        // به‌روزرسانی لیست لایرها
        if (window.cdpLayers && typeof window.cdpLayers.refreshFromDOM === "function") {
            window.cdpLayers.refreshFromDOM(parsed.layerMeta || []);
        } else if (typeof window.refreshLayers === "function") {
            window.refreshLayers(parsed.layerMeta || []);
        }

        if (typeof window.updateLayersList === "function") {
            window.updateLayersList();
        }
    }

    // =========================
    // Save As Modal
    // =========================

    function createSaveAsModal() {
        if (saveAsModal) return;

        saveAsModal = document.createElement("div");
        saveAsModal.hidden = true;

        saveAsModal.innerHTML = `
            <div class="cdp-saveas-backdrop"></div>
            <div class="cdp-saveas-panel">
                <header class="cdp-saveas-header">
                    <h3><i class="fa-regular fa-floppy-disk"></i> ${tr('saveas_title')}</h3>
                    <button type="button" class="cdp-saveas-close"><i class="fa-solid fa-xmark"></i></button>
                </header>
                <div class="cdp-saveas-toolbar">
                    <button type="button" id="cdpSaveAsBack" class="cdp-toolbar-btn">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div class="cdp-path-display">
                        <i class="fa-regular fa-folder" style="color: #9ca3af;" data-cdp-empty></i>
                        <span id="cdpSaveAsCurrentPath">${tr('root')}</span>
                    </div>
                    <button type="button" id="cdpSaveAsNewFolder" class="cdp-toolbar-btn cdp-btn-primary">
                        <i class="fa-solid fa-folder-plus"></i>
                        <span>${tr('new_folder')}</span>
                    </button>
                </div>
                <div class="cdp-saveas-body">
                    <div id="cdpSaveAsGrid" class="cdp-saveas-grid"></div>
                </div>
                <div class="cdp-saveas-input-area">
                    <label for="cdpSaveAsFileName">${tr('file_name')}:</label>
                    <input type="text" id="cdpSaveAsFileName" placeholder="${tr('file_placeholder')}">
                </div>
                <footer class="cdp-saveas-footer">
                    <button type="button" class="cdp-saveas-cancel">${tr('cancel')}</button>
                    <button type="button" class="cdp-saveas-save">${tr('save')}</button>
                </footer>
            </div>
        `;

        styleSaveAsModal();
        document.body.appendChild(saveAsModal);
        saveAsModal.dataset.cdpLang = getLang();

        const closeBtn = saveAsModal.querySelector(".cdp-saveas-close");
        const cancelBtn = saveAsModal.querySelector(".cdp-saveas-cancel");
        const saveBtn = saveAsModal.querySelector(".cdp-saveas-save");
        const backdrop = saveAsModal.querySelector(".cdp-saveas-backdrop");
        const backBtn = saveAsModal.querySelector("#cdpSaveAsBack");
        const newFolderBtn = saveAsModal.querySelector("#cdpSaveAsNewFolder");

        closeBtn.addEventListener("click", closeSaveAsModal);
        cancelBtn.addEventListener("click", closeSaveAsModal);
        backdrop.addEventListener("click", closeSaveAsModal);
        saveBtn.addEventListener("click", handleSaveAsConfirm);
        backBtn.addEventListener("click", handleSaveAsBack);
        newFolderBtn.addEventListener("click", showNewFolderModal);
    }

    function styleSaveAsModal() {
        Object.assign(saveAsModal.style, {
            position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
            zIndex: "11000", display: "none", alignItems: "center", justifyContent: "center"
        });

        const backdrop = saveAsModal.querySelector(".cdp-saveas-backdrop");
        Object.assign(backdrop.style, {
            position: "absolute", top: "0", left: "0", width: "100%", height: "100%",
            background: "rgba(0, 0, 0, 0.5)", backdropFilter: "blur(4px)"
        });

        const panel = saveAsModal.querySelector(".cdp-saveas-panel");
        Object.assign(panel.style, {
            position: "relative", background: "#fff", borderRadius: "12px",
            boxShadow: "0 25px 50px rgba(0,0,0,0.25)", width: "800px",
            maxWidth: "90vw", maxHeight: "80vh", display: "flex",
            flexDirection: "column", overflow: "hidden"
        });

        const header = saveAsModal.querySelector(".cdp-saveas-header");
        Object.assign(header.style, {
            padding: "20px 24px", borderBottom: "1px solid #e5e7eb",
            display: "flex", alignItems: "center",
            justifyContent: "space-between", background: "#fff"
        });

        const h3 = header.querySelector("h3");
        Object.assign(h3.style, {
            margin: "0", fontSize: "18px", fontWeight: "600",
            color: "#111827", display: "flex",
            alignItems: "center", gap: "10px"
        });

        const closeBtn = saveAsModal.querySelector(".cdp-saveas-close");
        Object.assign(closeBtn.style, {
            background: "transparent", border: "none",
            width: "32px", height: "32px", borderRadius: "6px",
            cursor: "pointer", fontSize: "18px",
            color: "#6b7280", transition: "all 0.2s"
        });

        const toolbar = saveAsModal.querySelector(".cdp-saveas-toolbar");
        Object.assign(toolbar.style, {
            padding: "12px 24px", background: "#fff",
            borderBottom: "1px solid #e5e7eb", display: "flex",
            alignItems: "center", gap: "12px"
        });

        const backBtn = toolbar.querySelector("#cdpSaveAsBack");
        Object.assign(backBtn.style, {
            background: "#fff", border: "1px solid #d1d5db",
            padding: "8px 12px", borderRadius: "6px",
            cursor: "pointer", fontSize: "14px",
            color: "#374151", transition: "all 0.2s"
        });

        const pathDisplay = toolbar.querySelector(".cdp-path-display");
        Object.assign(pathDisplay.style, {
            flex: "1", padding: "8px 12px",
            background: "#f9fafb", border: "1px solid #e5e7eb",
            borderRadius: "6px", fontSize: "13px",
            color: "#374151", display: "flex",
            alignItems: "center", gap: "8px"
        });

        const newFolderBtn = toolbar.querySelector("#cdpSaveAsNewFolder");
        Object.assign(newFolderBtn.style, {
            background: "#d9a300", border: "none",
            padding: "8px 16px", borderRadius: "6px",
            cursor: "pointer", fontSize: "13px",
            color: "#fff", display: "flex",
            alignItems: "center", gap: "6px",
            transition: "background 0.2s", fontWeight: "500"
        });

        newFolderBtn.addEventListener("mouseenter", () => newFolderBtn.style.background = "#d8a712");
        newFolderBtn.addEventListener("mouseleave", () => newFolderBtn.style.background = "#d9a300");

        const body = saveAsModal.querySelector(".cdp-saveas-body");
        Object.assign(body.style, {
            padding: "24px", flex: "1", overflowY: "auto", background: "#fff"
        });

        const grid = body.querySelector("#cdpSaveAsGrid");
        Object.assign(grid.style, {
            display: "grid",
            gridTemplateColumns: "repeat(auto-fill, minmax(96px, 1fr))",
            gap: "5px", minHeight: "300px"
        });

        const inputArea = saveAsModal.querySelector(".cdp-saveas-input-area");
        Object.assign(inputArea.style, {
            padding: "16px 24px", borderTop: "1px solid #e5e7eb",
            background: "#fff", display: "flex",
            alignItems: "center", gap: "12px"
        });

        const label = inputArea.querySelector("label");
        Object.assign(label.style, {
            fontSize: "14px", fontWeight: "500",
            color: "#374151", minWidth: "80px"
        });

        const input = inputArea.querySelector("#cdpSaveAsFileName");
        Object.assign(input.style, {
            flex: "1", padding: "10px 12px",
            border: "1px solid #d1d5db", borderRadius: "8px",
            fontSize: "14px", transition: "border-color 0.2s"
        });

        input.addEventListener("focus", () => {
            input.style.borderColor = "#d9a300";
            input.style.outline = "none";
            input.style.boxShadow = "0 0 0 3px rgba(59, 130, 246, 0.1)";
        });
        input.addEventListener("blur", () => {
            input.style.borderColor = "#d1d5db";
            input.style.boxShadow = "none";
        });

        const footer = saveAsModal.querySelector(".cdp-saveas-footer");
        Object.assign(footer.style, {
            padding: "16px 24px", borderTop: "1px solid #e5e7eb",
            display: "flex", justifyContent: "flex-end",
            gap: "12px", background: "#fff"
        });

        const cancelBtn = footer.querySelector(".cdp-saveas-cancel");
        const saveBtn = footer.querySelector(".cdp-saveas-save");

        Object.assign(cancelBtn.style, {
            padding: "10px 20px", background: "#f3f4f6",
            border: "none", borderRadius: "8px",
            cursor: "pointer", fontSize: "14px",
            fontWeight: "500", color: "#374151"
        });

        Object.assign(saveBtn.style, {
            padding: "10px 20px", background: "#d9a300",
            border: "none", borderRadius: "8px",
            cursor: "pointer", fontSize: "14px",
            fontWeight: "500", color: "#fff"
        });

        cancelBtn.addEventListener("mouseenter", () => cancelBtn.style.background = "#e5e7eb");
        cancelBtn.addEventListener("mouseleave", () => cancelBtn.style.background = "#f3f4f6");
        saveBtn.addEventListener("mouseenter", () => saveBtn.style.background = "#d8a712");
        saveBtn.addEventListener("mouseleave", () => saveBtn.style.background = "#d9a300");
    
        cdpApplyFileModalsTheme();
}

    function displaySaveAsContents(path) {
        const grid = saveAsModal.querySelector("#cdpSaveAsGrid");
        const pathDisplay = saveAsModal.querySelector("#cdpSaveAsCurrentPath");
        const backBtn = saveAsModal.querySelector("#cdpSaveAsBack");

        grid.innerHTML = "";
        pathDisplay.textContent = path || tr('root');
        backBtn.disabled = !path;
        backBtn.style.opacity = path ? "1" : "0.5";
        backBtn.style.cursor = path ? "pointer" : "not-allowed";

        const contents = getFolderContents(path);

        if (Object.keys(contents.folders).length === 0 && contents.files.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #9ca3af;" data-cdp-empty>
                    <i class="fa-regular fa-folder-open" style="font-size: 56px; margin-bottom: 16px; display: block; opacity: 0.5;"></i>
                    <p style="font-size: 15px; margin: 0; font-weight: 500;">${tr('empty_folder_title')}</p>
                    <p style="font-size: 13px; margin: 8px 0 0 0; opacity: 0.7;">${tr('empty_folder_sub_saveas')}</p>
                </div>
            `;
            return;
        }

        // نمایش پوشه‌ها - فقط برای مشاهده، نمی‌توان وارد شد
        Object.keys(contents.folders).forEach(folderName => {
            const card = createFolderCard(folderName, true, () => {
                const newPath = path ? `${path}/${folderName}` : folderName;
                currentFolder = newPath;
                displaySaveAsContents(newPath);
            }, "click");
            grid.appendChild(card);
        });

        // نمایش فایل‌های موجود - فقط برای مشاهده
        contents.files.forEach(fileName => {
            const card = createFolderCard(fileName, false);
            grid.appendChild(card);
        });
    }

    function handleSaveAsBack() {
        if (!currentFolder) return;
        const parts = currentFolder.split('/').filter(p => p);
        parts.pop();
        currentFolder = parts.join('/');
        displaySaveAsContents(currentFolder);
    }

    async function showSaveAsModal(onSaveCallback = null) {
        if (cdpRemoteStorageSupported) {
            await cdpSyncFolderStructureFromServer();
        }

        createSaveAsModal();
        currentFolder = localStorage.getItem("cdpCurrentFolder") || "";
        displaySaveAsContents(currentFolder);
        
        const input = saveAsModal.querySelector("#cdpSaveAsFileName");
        input.value = currentProjectName;

        // ذخیره callback برای بعد از Save
        if (onSaveCallback) {
            saveAsModal.dataset.onSaveCallback = "pending";
            window.cdpSaveCallback = onSaveCallback;
        }

        saveAsModal.hidden = false;
        saveAsModal.style.display = "flex";
        document.body.style.overflow = "hidden";
        setTimeout(() => {
            input.focus();
            input.select();
        }, 100);
    }

    function closeSaveAsModal() {
        if (saveAsModal) {
            saveAsModal.hidden = true;
            saveAsModal.style.display = "none";
            document.body.style.overflow = "";
        }
    }

    async function handleSaveAsConfirm() {
        const input = saveAsModal.querySelector("#cdpSaveAsFileName");
        const fileName = input.value.trim();

        if (!fileName) {
            showMessage(tr('enter_name'), "error");
            return;
        }

        const fullPath = currentFolder ? `${currentFolder}/${fileName}` : fileName;
        currentProjectName = fileName;
        
        const data = await getCurrentDesignData(fullPath);
        if (!data) {
            showMessage(tr('design_empty') || 'Add at least one layer before saving.', "error");
            return;
        }

        if (cdpRemoteStorageSupported) {
            const remoteResponse = await cdpSaveProjectToServer(fullPath, compactProjectDataForRemoteStorage(data));
            if (remoteResponse.success && remoteResponse.structure) {
                cdpApplyRemoteStructure(remoteResponse.structure);
            } else if (remoteResponse.available !== false) {
                showMessage(remoteResponse.message || 'Unable to save the project on the server. Try refresh and save again.', 'error');
                return;
            }
        }

        if (!saveToLocalStorage(fullPath, data)) {
            return;
        }
        ensureProjectInFolderStructure(fullPath);
        localStorage.setItem("cdpCurrentFolder", currentFolder);

        closeSaveAsModal();
        const successMessage = tr('project_saved_named').replace('{name}', fileName);
        showMessage(successMessage, "success");

        // اگر callback داشته باشد، اجرا کن
        if (saveAsModal.dataset.onSaveCallback === "pending" && window.cdpSaveCallback) {
            setTimeout(() => {
                window.cdpSaveCallback();
                delete window.cdpSaveCallback;
                delete saveAsModal.dataset.onSaveCallback;
            }, 600);
        }
    }

    // =========================
    // New Folder Modal
    // =========================

    function createNewFolderModal() {
        if (newFolderModal) return;

        newFolderModal = document.createElement("div");
        newFolderModal.hidden = true;

        newFolderModal.innerHTML = `
            <div class="cdp-modal-backdrop"></div>
            <div class="cdp-modal-panel" style="max-width: 450px;">
                <header class="cdp-modal-header">
                    <h3><i class="fa-solid fa-folder-plus"></i> ${tr('new_folder')}</h3>
                    <button type="button" class="cdp-modal-close"><i class="fa-solid fa-xmark"></i></button>
                </header>
                <div class="cdp-modal-body">
                    <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: #374151;">${tr('folder_name')}:</label>
                    <input type="text" id="cdpNewFolderInput" placeholder="${tr('folder_placeholder')}" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                </div>
                <footer class="cdp-modal-footer">
                    <button type="button" class="cdp-btn-cancel">${tr('cancel')}</button>
                    <button type="button" class="cdp-btn-create">${tr('create')}</button>
                </footer>
            </div>
        `;

        styleNewFolderModal();
        document.body.appendChild(newFolderModal);

        const input = newFolderModal.querySelector("#cdpNewFolderInput");
        const closeBtn = newFolderModal.querySelector(".cdp-modal-close");
        const cancelBtn = newFolderModal.querySelector(".cdp-btn-cancel");
        const createBtn = newFolderModal.querySelector(".cdp-btn-create");
        const backdrop = newFolderModal.querySelector(".cdp-modal-backdrop");

        closeBtn.addEventListener("click", closeNewFolderModal);
        cancelBtn.addEventListener("click", closeNewFolderModal);
        backdrop.addEventListener("click", closeNewFolderModal);
        createBtn.addEventListener("click", confirmCreateFolder);

        input.addEventListener("keypress", (e) => {
            if (e.key === "Enter") confirmCreateFolder();
        });
    }

    function styleNewFolderModal() {
        Object.assign(newFolderModal.style, {
            position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
            zIndex: "12000", display: "none", alignItems: "center", justifyContent: "center"
        });

        const backdrop = newFolderModal.querySelector(".cdp-modal-backdrop");
        Object.assign(backdrop.style, {
            position: "absolute", top: "0", left: "0", width: "100%", height: "100%",
            background: "rgba(0, 0, 0, 0.5)", backdropFilter: "blur(4px)"
        });

        const panel = newFolderModal.querySelector(".cdp-modal-panel");
        Object.assign(panel.style, {
            position: "relative", background: "#fff", borderRadius: "12px",
            boxShadow: "0 25px 50px rgba(0,0,0,0.25)",
            width: "90%", overflow: "hidden"
        });

        const header = newFolderModal.querySelector(".cdp-modal-header");
        Object.assign(header.style, {
            padding: "20px 24px", borderBottom: "1px solid #e5e7eb",
            display: "flex", alignItems: "center",
            justifyContent: "space-between", background: "#fff"
        });

        const h3 = header.querySelector("h3");
        Object.assign(h3.style, {
            margin: "0", fontSize: "18px", fontWeight: "600",
            color: "#111827", display: "flex",
            alignItems: "center", gap: "10px"
        });

        const closeBtn = header.querySelector(".cdp-modal-close");
        Object.assign(closeBtn.style, {
            background: "transparent", border: "none",
            width: "32px", height: "32px", borderRadius: "6px",
            cursor: "pointer", fontSize: "18px", color: "#6b7280"
        });

        const body = newFolderModal.querySelector(".cdp-modal-body");
        Object.assign(body.style, { padding: "24px", background: "#fff" });

        const footer = newFolderModal.querySelector(".cdp-modal-footer");
        Object.assign(footer.style, {
            padding: "16px 24px", borderTop: "1px solid #e5e7eb",
            display: "flex", justifyContent: "flex-end",
            gap: "12px", background: "#fff"
        });

        const cancelBtn = footer.querySelector(".cdp-btn-cancel");
        const createBtn = footer.querySelector(".cdp-btn-create");

        Object.assign(cancelBtn.style, {
            padding: "10px 20px", background: "#f3f4f6",
            border: "none", borderRadius: "8px", cursor: "pointer",
            fontSize: "14px", fontWeight: "500", color: "#374151"
        });

        Object.assign(createBtn.style, {
            padding: "10px 20px", background: "#d9a300",
            border: "none", borderRadius: "8px", cursor: "pointer",
            fontSize: "14px", fontWeight: "500", color: "#fff"
        });
    }

    function showNewFolderModal() {
        createNewFolderModal();
        const input = newFolderModal.querySelector("#cdpNewFolderInput");
        input.value = "";
        newFolderModal.hidden = false;
        newFolderModal.style.display = "flex";
        input.focus();
    }

    function closeNewFolderModal() {
        if (newFolderModal) {
            newFolderModal.hidden = true;
            newFolderModal.style.display = "none";
        }
    }

    async function confirmCreateFolder() {
        const input = newFolderModal.querySelector("#cdpNewFolderInput");
        const folderName = input.value.trim();

        if (!folderName) {
            showMessage(tr('enter_folder'), "error");
            return;
        }

        const structure = getFolderStructure();
        let current = structure;

        if (currentFolder) {
            const parts = currentFolder.split('/').filter(p => p);
            for (const part of parts) {
                if (!current.folders[part]) {
                    current.folders[part] = { folders: {}, files: [] };
                }
                current = current.folders[part];
            }
        }

        if (!current.folders) current.folders = {};
        
        if (current.folders[folderName]) {
            showMessage(tr('exists'), "warning");
            return;
        }

        if (Object.keys(current.folders).length >= 30) {
            showMessage(tr('max_folders'), "warning");
            return;
        }

        const depth = currentFolder ? currentFolder.split('/').length : 0;
        if (depth >= 3) {
            showMessage(tr('max_depth'), "warning");
            return;
        }

        current.folders[folderName] = { folders: {}, files: [] };
        saveFolderStructure(structure);

        if (cdpRemoteStorageSupported) {
            const remoteResponse = await cdpPersistFolderStructureToServer(structure);
            if (remoteResponse.success && remoteResponse.structure) {
                cdpApplyRemoteStructure(remoteResponse.structure);
            } else if (remoteResponse.available !== false) {
                showMessage(remoteResponse.message || 'Unable to create the folder on the server.', 'error');
                return;
            }
        }

        displaySaveAsContents(currentFolder);
        closeNewFolderModal();
        const folderCreated = tr('folder_created_named').replace('{name}', folderName);
        showMessage(folderCreated, "success");
    }

    // =========================
    // Actions
    // =========================

    function handleFileAction(action) {
        switch(action) {
            case "new": handleNew(); break;
            case "open": handleOpen(); break;
            case "save": handleSave(); break;
            case "saveas": handleSaveAs(); break;
        }
    }

    function setupFileShortcuts() {
        if (window._cdpFileShortcutsReady) return;
        window._cdpFileShortcutsReady = true;

        const shortcutHandler = (event) => {
            if (!event.ctrlKey) return;
            if (event.metaKey || event.altKey) return;

            const normalizedKey = (event.key || '').toLowerCase();
            const shortcut = fileShortcutConfig.find(shortcutEntry => {
                return shortcutEntry.key === normalizedKey &&
                    (!!shortcutEntry.ctrl === !!event.ctrlKey) &&
                    (!!shortcutEntry.shift === !!event.shiftKey) &&
                    (!!shortcutEntry.alt === !!event.altKey) &&
                    (!!shortcutEntry.meta === !!event.metaKey);
            });

            if (!shortcut) return;

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            handleFileAction(shortcut.action);
        };

        document.addEventListener('keydown', shortcutHandler, true);
        window.addEventListener('keydown', shortcutHandler, true);
    }

    function captureProjectThumbnail() {
        if (window.cdpCart && typeof window.cdpCart.capturePreview === "function") {
            return window.cdpCart.capturePreview();
        }

        const activeBox = document.querySelector('.cdp-print-box:not(.cdp-print-box--hidden)');
        const cartSnapshot = window.cdpCart && typeof window.cdpCart.getSnapshot === "function"
            ? window.cdpCart.getSnapshot()
            : null;
        const fallbackPreview = (cartSnapshot && cartSnapshot.image) || "";

        if (!activeBox) {
            return Promise.resolve(fallbackPreview);
        }

        const boxRect = activeBox.getBoundingClientRect();
        const previewMarkup = buildPreviewMarkup(activeBox);

        if (!boxRect.width || !boxRect.height || !hasRenderableLayerContent(activeBox) || !previewMarkup) {
            return Promise.resolve(fallbackPreview);
        }

        const boxWidth = Math.round(boxRect.width);
        const boxHeight = Math.round(boxRect.height);
        const svgMarkup = ''
            + '<svg xmlns="http://www.w3.org/2000/svg" width="' + boxWidth + '" height="' + boxHeight + '" viewBox="0 0 ' + boxWidth + ' ' + boxHeight + '">'
            + '<foreignObject width="100%" height="100%">'
            + '<div xmlns="http://www.w3.org/1999/xhtml" style="position:relative;width:' + boxWidth + 'px;height:' + boxHeight + 'px;overflow:hidden;background:transparent;">'
            + previewMarkup
            + '</div>'
            + '</foreignObject>'
            + '</svg>';

        return new Promise((resolve) => {
            const image = new Image();
            image.onload = function () {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = boxWidth;
                    canvas.height = boxHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(image, 0, 0, boxWidth, boxHeight);
                    resolve(canvas.toDataURL('image/png'));
                } catch (_error) {
                    resolve(fallbackPreview);
                }
            };
            image.onerror = function () {
                resolve(fallbackPreview);
            };
            image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgMarkup);
        });
    }

    function hasRenderableLayerContent(box) {
        if (!(box instanceof Element)) {
            return false;
        }
        return Array.from(box.children || []).some((child) => child instanceof Element && child.dataset?.layerIgnore !== "true");
    }

    function buildPreviewMarkup(box) {
        if (!(box instanceof Element)) {
            return "";
        }
        const clone = box.cloneNode(true);
        const sanitizeNode = (node) => {
            if (!(node instanceof Element)) {
                return;
            }
            Array.from(node.attributes).forEach((attr) => {
                if (attr.name === "style" || attr.name === "src" || attr.name === "href") {
                    return;
                }
                node.removeAttribute(attr.name);
            });
            if (node.tagName === "IMG") {
                node.setAttribute("src", node.currentSrc || node.getAttribute("src") || "");
            }
            Array.from(node.children || []).forEach(sanitizeNode);
        };
        Array.from(clone.children || []).forEach(sanitizeNode);
        return clone.innerHTML;
    }

    function isGeneratedPreview(value) {
        return typeof value === "string" && /^data:image\//i.test(value.trim());
    }

    function parseNumericStyle(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function roundMetric(value) {
        return Number.isFinite(value) ? Math.round(value * 1000) / 1000 : null;
    }

    function stripTranslateFromTransform(value) {
        return String(value || "")
            .replace(/translate\([^)]*\)/gi, "")
            .replace(/\s{2,}/g, " ")
            .trim();
    }

    function hasExplicitPixelSize(value) {
        return typeof value === "string" && /\d/.test(value) && value.trim().toLowerCase() !== "auto";
    }

    function extractElementTransform(child) {
        if (!(child instanceof Element)) {
            return {};
        }
        const imageEl = child.tagName === "IMG" ? child : child.querySelector("img");
        const parentRect = child.parentElement && typeof child.parentElement.getBoundingClientRect === "function"
            ? child.parentElement.getBoundingClientRect()
            : null;
        const childRect = typeof child.getBoundingClientRect === "function"
            ? child.getBoundingClientRect()
            : null;
        return {
            left: child.style.left || "",
            top: child.style.top || "",
            width: child.style.width || imageEl?.style.width || "",
            height: child.style.height || imageEl?.style.height || "",
            transform: child.style.transform || "",
            transformSansTranslate: stripTranslateFromTransform(child.style.transform || ""),
            transformOrigin: child.style.transformOrigin || "",
            zIndex: child.style.zIndex || "",
            renderedLeft: parentRect && childRect ? roundMetric(childRect.left - parentRect.left) : null,
            renderedTop: parentRect && childRect ? roundMetric(childRect.top - parentRect.top) : null,
        };
    }

    function extractUploadLayerData(child, view, layerId, opacity, locked) {
        if (!(child instanceof Element)) {
            return null;
        }
        const imageEl = child.tagName === "IMG" ? child : child.querySelector("img");
        const childRect = typeof child.getBoundingClientRect === "function" ? child.getBoundingClientRect() : null;
        const imageRect = imageEl && typeof imageEl.getBoundingClientRect === "function"
            ? imageEl.getBoundingClientRect()
            : null;
        const explicitChildWidth = parseNumericStyle(child.style.width);
        const explicitImageWidth = parseNumericStyle(imageEl?.style.width);
        const explicitChildHeight = hasExplicitPixelSize(child.style.height)
            ? parseNumericStyle(child.style.height)
            : null;
        const explicitImageHeight = hasExplicitPixelSize(imageEl?.style.height)
            ? parseNumericStyle(imageEl.style.height)
            : null;
        const optimizedSrc = child.dataset?.optimizedSrc
            || imageEl?.dataset?.optimizedSrc
            || imageEl?.currentSrc
            || imageEl?.src
            || "";
        const originalSrc = child.dataset?.originalSrc
            || imageEl?.dataset?.originalSrc
            || child.dataset?.originalBackup
            || optimizedSrc
            || "";
        if (!originalSrc && !optimizedSrc) {
            return null;
        }
        return {
            originalSrc,
            optimizedSrc,
            uploadName: child.dataset?.uploadName || "Uploaded Image",
            uploadType: child.dataset?.uploadType || "image/jpeg",
            width: explicitChildWidth || explicitImageWidth || imageRect?.width || childRect?.width || null,
            height: explicitChildHeight || explicitImageHeight || null,
            view,
            layerId,
            transform: extractElementTransform(child),
            opacity,
            locked,
        };
    }

    function buildStructuredLayerRecord(child, boxIndex, view, layerLookup) {
        if (!(child instanceof Element)) {
            return null;
        }
        const rawId = child.dataset?.layerId;
        const numericId = rawId && !Number.isNaN(Number(rawId)) ? Number(rawId) : null;
        const layer = numericId !== null ? layerLookup.get(String(numericId)) : null;
        const type = layer?.type || child.dataset?.layerType || "layer";
        const name = layer?.name || child.dataset?.layerName || `${type} layer`;
        const opacity = typeof layer?.opacity === "number"
            ? layer.opacity
            : (child.style.opacity ? Number(child.style.opacity) : 1);
        const locked = typeof layer?.locked === "boolean"
            ? layer.locked
            : child.dataset?.locked === "true";
        const record = {
            box: boxIndex,
            view,
            html: type === "upload" ? "" : child.outerHTML,
            layerId: numericId,
            type,
            name,
            locked,
            opacity,
            transform: extractElementTransform(child),
        };
        if (type === "upload") {
            record.upload = extractUploadLayerData(child, view, numericId, opacity, locked);
        }
        return record;
    }

    function readExistingProjectData(projectPath) {
        if (!projectPath) {
            return null;
        }
        try {
            const saved = localStorage.getItem(`cdpProject_${projectPath}`);
            return saved ? JSON.parse(saved) : null;
        } catch (_error) {
            return null;
        }
    }

    function getPreservedPreview(nextPreview, existingProject) {
        if (isGeneratedPreview(nextPreview)) {
            return nextPreview;
        }
        const candidates = [
            existingProject?.thumbnail,
            existingProject?.previewImage,
            existingProject?.designPreview,
            existingProject?.canvasPreview,
        ];
        return candidates.find(isGeneratedPreview) || "";
    }

    function compactProjectDataForStorage(project) {
        if (!project || typeof project !== "object") {
            return project;
        }

        const compacted = {
            ...project,
            layers: Array.isArray(project.layers)
                ? project.layers.map((layer) => {
                    if (!layer || typeof layer !== "object") {
                        return layer;
                    }
                    if (layer.type === "upload" && layer.html) {
                        return { ...layer, html: "" };
                    }
                    return layer;
                })
                : project.layers,
        };

        const previewSource = [
            compacted.thumbnail,
            compacted.previewImage,
            compacted.designPreview,
            compacted.canvasPreview,
        ].find(isGeneratedPreview) || "";

        if (previewSource) {
            compacted.thumbnail = previewSource;
            if (compacted.previewImage === previewSource) {
                compacted.previewImage = "";
            }
            if (compacted.designPreview === previewSource) {
                compacted.designPreview = "";
            }
            if (compacted.canvasPreview === previewSource) {
                compacted.canvasPreview = "";
            }
        }

        return compacted;
    }

    function compactProjectDataForRemoteStorage(project) {
        const compacted = compactProjectDataForStorage(project);
        if (!compacted || typeof compacted !== "object") {
            return compacted;
        }

        return {
            ...compacted,
            invoiceAttachments: [],
            invoiceScanImage: "",
            previewImage: "",
            designPreview: "",
            canvasPreview: "",
            thumbnail: "",
            layers: Array.isArray(compacted.layers)
                ? compacted.layers.map((layer) => {
                    if (!layer || typeof layer !== "object" || layer.type !== "upload" || !layer.upload) {
                        return layer;
                    }

                    const upload = { ...layer.upload };
                    if (upload.originalSrc === upload.optimizedSrc) {
                        upload.originalSrc = "";
                    }

                    return {
                        ...layer,
                        upload,
                    };
                })
                : compacted.layers,
            layerMeta: Array.isArray(compacted.layerMeta)
                ? compacted.layerMeta.map((layer) => {
                    if (!layer || typeof layer !== "object" || layer.type !== "upload") {
                        return layer;
                    }

                    const meta = { ...layer };
                    if (meta.originalSrc === meta.optimizedSrc) {
                        meta.originalSrc = "";
                    }

                    return meta;
                })
                : compacted.layerMeta,
        };
    }
    function compactExistingProjectsForQuota(skipKey = "") {
        Object.keys(localStorage)
            .filter((key) => key.startsWith("cdpProject_") && key !== skipKey)
            .forEach((key) => {
                try {
                    const raw = localStorage.getItem(key);
                    if (!raw) {
                        return;
                    }
                    const parsed = JSON.parse(raw);
                    const compacted = compactProjectDataForStorage(parsed);
                    const nextRaw = JSON.stringify(compacted);
                    if (nextRaw !== raw) {
                        localStorage.setItem(key, nextRaw);
                    }
                } catch (_error) {
                    // Ignore malformed or locked entries while reclaiming space.
                }
            });
    }

    function syncLayersFromDOM() {
        if (window.cdpLayers && typeof window.cdpLayers.refreshFromDOM === "function") {
            window.cdpLayers.refreshFromDOM(window.cdpLayers.getLayers());
        }
    }

    function getCurrentPageHref() {
        if (!window.location || !window.location.href) {
            return "";
        }
        return window.location.href.split('#')[0].split('?')[0];
    }

    async function getCurrentDesignData(projectPath = "") {
        syncLayersFromDOM();
        const boxes = Array.from(document.querySelectorAll(".cdp-print-box"));
        const fallbackViews = ["front", "back", "right", "left"];
        const serializedLayers = [];
        const currentLayers = window.cdpLayers && typeof window.cdpLayers.getLayers === "function"
            ? window.cdpLayers.getLayers()
            : [];
        const layerLookup = new Map(currentLayers.map((layer) => [String(layer.id), layer]));
        const existingProject = readExistingProjectData(projectPath);

        boxes.forEach((box, index) => {
            const view = box.dataset?.view || fallbackViews[index] || "front";
            Array.from(box.children || []).forEach(child => {
                if (!(child instanceof Element)) return;
                if (child.dataset?.layerIgnore === "true") return;
                const record = buildStructuredLayerRecord(child, index, view, layerLookup);
                if (record) {
                    serializedLayers.push(record);
                }
            });
        });

        if (!serializedLayers.length) {
            return null;
        }

        const layerMeta = [];
        if (currentLayers.length) {
            currentLayers.forEach(layer => {
                const element = layer.element instanceof Element ? layer.element : null;
                const opacity = typeof layer.opacity === "number" ? layer.opacity : 1;
                const locked = !!layer.locked;
                const meta = {
                    id: layer.id,
                    layerId: layer.id,
                    type: layer.type,
                    name: layer.name,
                    view: layer.view,
                    locked,
                    opacity,
                    transform: extractElementTransform(element),
                };
                if (layer.type === "upload") {
                    const upload = extractUploadLayerData(element, layer.view, layer.id, opacity, locked);
                    if (upload) {
                        meta.originalSrc = upload.originalSrc;
                        meta.optimizedSrc = upload.optimizedSrc;
                        meta.uploadName = upload.uploadName;
                        meta.uploadType = upload.uploadType;
                        meta.width = upload.width;
                        meta.height = upload.height;
                    }
                }
                layerMeta.push(meta);
            });
        }

        const previewImage = getPreservedPreview(await captureProjectThumbnail(), existingProject);
        const invoiceAttachments = window.cdpCart && typeof window.cdpCart.getInvoiceAttachments === "function"
            ? window.cdpCart.getInvoiceAttachments()
            : [];

        return {
            projectName: currentProjectName,
            timestamp: new Date().toISOString(),
            expiryDate: new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString(),
            layers: serializedLayers,
            layerMeta,
            note: localStorage.getItem("cdpNote") || "",
            invoiceAttachments,
            invoiceScanImage: (invoiceAttachments.find(item => item && item.slot === "scan-box") || invoiceAttachments[0] || {}).dataUrl || "",
            thumbnail: previewImage,
            previewImage,
            designPreview: previewImage,
            canvasPreview: previewImage,
            product: {
                name: document.querySelector(".cdp-product-name")?.textContent?.trim() || "",
                size: document.getElementById("cdpSizeValue")?.textContent || "M",
                color: document.getElementById("cdpColorName")?.textContent || "White",
                view: document.getElementById("cdpShirtImage")?.dataset.view || window.cdpState?.currentView || "front",
                pageHref: getCurrentPageHref()
            }
        };
    }

    function saveToLocalStorage(projectPath, data) {
        const key = `cdpProject_${projectPath}`;
        const pathParts = String(projectPath || "").split('/').filter(Boolean);
        const savedProjectName = pathParts.pop() || currentProjectName;
        currentProjectName = savedProjectName;
        currentFolder = pathParts.join('/');
        const compactedData = compactProjectDataForStorage(data);
        try {
            localStorage.setItem(key, JSON.stringify(compactedData));
        } catch (error) {
            if (error && error.name === "QuotaExceededError") {
                try {
                    compactExistingProjectsForQuota(key);
                    localStorage.setItem(key, JSON.stringify(compactedData));
                    error = null;
                } catch (retryError) {
                    if (retryError && retryError.name === "QuotaExceededError") {
                        showMessage('Save failed: browser storage is full. Remove older Custom Design projects and try again.', "error");
                        return false;
                    }
                    throw retryError;
                }
                if (!error) {
                    localStorage.setItem("cdpCurrentProject", currentProjectName);
                    localStorage.setItem("cdpCurrentFolder", currentFolder);
                    localStorage.setItem("cdpLastSaved", new Date().toISOString());
                    return true;
                }
            }
            throw error;
        }
        localStorage.setItem("cdpCurrentProject", currentProjectName);
        localStorage.setItem("cdpCurrentFolder", currentFolder);
        localStorage.setItem("cdpLastSaved", new Date().toISOString());
        return true;
    }

    function handleNew() {
        // بررسی وجود لایه (icon, text, shape, flag, fill, upload)
        const boxes = document.querySelectorAll(".cdp-print-box");
        let hasLayers = false;
        boxes.forEach(box => {
            if (box.querySelector(".cdp-design-element") || box.querySelector(".cdp-uploaded-image")) {
                hasLayers = true;
            }
        });

        if (!hasLayers) {
            // اگر لایه‌ای نیست، مستقیم New
            clearDesign();
            return;
        }

        // اگر لایه دارد، سوال Save or Don't Save
        const confirmBox = document.createElement("div");
        
        confirmBox.innerHTML = `
            <div class="cdp-confirm-backdrop"></div>
            <div class="cdp-confirm-panel">
                <div class="cdp-confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="cdp-confirm-text">${tr('save_changes')}</div>
                <div class="cdp-confirm-buttons">
                    <button type="button" class="cdp-confirm-dont-save">${tr('dont_save')}</button>
                    <button type="button" class="cdp-confirm-save">${tr('save')}</button>
                </div>
            </div>
        `;

        Object.assign(confirmBox.style, {
            position: "fixed", top: "0", left: "0", width: "100%", height: "100%",
            zIndex: "15000", display: "flex", alignItems: "center", justifyContent: "center"
        });

        const backdrop = confirmBox.querySelector(".cdp-confirm-backdrop");
        Object.assign(backdrop.style, {
            position: "absolute", top: "0", left: "0", width: "100%", height: "100%",
            background: "rgba(0, 0, 0, 0.5)", backdropFilter: "blur(4px)"
        });

        const panel = confirmBox.querySelector(".cdp-confirm-panel");
        Object.assign(panel.style, {
            position: "relative", background: "#fff", borderRadius: "16px",
            boxShadow: "0 25px 50px rgba(0, 0, 0, 0.25)", padding: "32px",
            maxWidth: "420px", width: "90%", textAlign: "center"
        });

        const icon = confirmBox.querySelector(".cdp-confirm-icon");
        Object.assign(icon.style, { fontSize: "56px", color: "#f59e0b", marginBottom: "20px" });

        const text = confirmBox.querySelector(".cdp-confirm-text");
        Object.assign(text.style, {
            fontSize: "16px", color: "#111827", lineHeight: "1.6",
            marginBottom: "24px", fontWeight: "500"
        });

        const buttonsDiv = confirmBox.querySelector(".cdp-confirm-buttons");
        Object.assign(buttonsDiv.style, { display: "flex", gap: "12px" });

        const dontSaveBtn = confirmBox.querySelector(".cdp-confirm-dont-save");
        const saveBtn = confirmBox.querySelector(".cdp-confirm-save");

        Object.assign(dontSaveBtn.style, {
            flex: "1", padding: "12px 24px", background: "#f3f4f6",
            border: "none", borderRadius: "10px", color: "#374151",
            fontSize: "15px", fontWeight: "600", cursor: "pointer"
        });

        Object.assign(saveBtn.style, {
            flex: "1", padding: "12px 24px", background: "#d9a300",
            border: "none", borderRadius: "10px", color: "#fff",
            fontSize: "15px", fontWeight: "600", cursor: "pointer"
        });

        // Don't Save: مستقیم پاک کردن و New
        dontSaveBtn.addEventListener("click", () => {
            confirmBox.remove();
            clearDesign();
        });

        backdrop.addEventListener("click", () => {
            confirmBox.remove();
        });

        // Save: بررسی قبلاً سیو شده یا نه
        saveBtn.addEventListener("click", async () => {
            confirmBox.remove();

            // بررسی آیا پروژه در localStorage موجود است
            const fullPath = currentFolder ? `${currentFolder}/${currentProjectName}` : currentProjectName;
            const key = `cdpProject_${fullPath}`;
            const savedProject = localStorage.getItem(key);

            if (savedProject && currentProjectName !== "Untitled") {
                // قبلاً سیو شده، مستقیم Save
                const data = await getCurrentDesignData(fullPath);
                if (!data) {
                    showMessage(tr('design_empty') || 'Add at least one layer before saving.', "error");
                    return;
                }
                if (!saveToLocalStorage(fullPath, data)) {
                    return;
                }
                const savedMessage = tr('project_saved_named').replace('{name}', currentProjectName);
                showMessage(savedMessage, "success");
                
                // بعد از Save، پاک کردن
                setTimeout(() => {
                    clearDesign();
                }, 500);
            } else {
                // سیو نشده، Save As
                showSaveAsModal(() => {
                    // بعد از Save As، پاک کردن
                    clearDesign();
                });
            }
        });

        document.body.appendChild(confirmBox);
    }

    function clearDesign() {
        // بستن Edit Panel بدون Apply (Cancel)
        const editPanel = document.querySelector('.cdp-image-edit-panel');
        if (editPanel && editPanel.getAttribute('data-visible') === 'true') {
            const cancelBtn = editPanel.querySelector('button[type="button"]:not(.cdp-btn-primary)');
            if (cancelBtn) {
                cancelBtn.click(); // شبیه‌سازی کلیک Cancel
            } else {
                editPanel.setAttribute('data-visible', 'false');
            }
        }
        
        // بستن Upload Modal
        const uploadModal = document.querySelector('.cdp-upload-modal');
        if (uploadModal) {
            uploadModal.setAttribute('data-visible', 'false');
        }

        // پاک کردن canvas
        const boxes = normalizePrintBoxes();
        
        const layersList = document.getElementById("cdpLayersList");
        if (layersList) layersList.innerHTML = "";

        localStorage.removeItem("cdpNote");
        const noteText = document.getElementById("cdpNoteText");
        if (noteText) noteText.value = "";

        // پاک کردن تمام state
        currentProjectName = "Untitled";
        currentFolder = "";
        localStorage.setItem("cdpCurrentProject", "Untitled");
        localStorage.setItem("cdpCurrentFolder", "");
        localStorage.removeItem("cdpLastSaved");

        applyProjectView("front");
        
        // Reset layers
        if (window.cdpLayers && typeof window.cdpLayers.clearAll === "function") {
            window.cdpLayers.clearAll();
        } else if (window.cdpLayers && window.cdpLayers.layers) {
            window.cdpLayers.layers = [];
        }
        
        showMessage(tr('design_created'), "success");
    }

    async function handleOpen() {
        if (cdpRemoteStorageSupported) {
            await cdpSyncFolderStructureFromServer();
        }
        showOpenModal();
    }

    async function handleSave() {
        const fullPath = currentFolder ? `${currentFolder}/${currentProjectName}` : currentProjectName;

        if (!currentProjectName || currentProjectName === "Untitled" || !localStorage.getItem(`cdpProject_${fullPath}`)) {
            showSaveAsModal();
            return;
        }

        const data = await getCurrentDesignData(fullPath);
        if (!data) {
            showMessage(tr('design_empty') || 'Add at least one layer before saving.', "error");
            return;
        }

        if (cdpRemoteStorageSupported) {
            const remoteResponse = await cdpSaveProjectToServer(fullPath, compactProjectDataForRemoteStorage(data));
            if (remoteResponse.success && remoteResponse.structure) {
                cdpApplyRemoteStructure(remoteResponse.structure);
            } else if (remoteResponse.available !== false) {
                showMessage(remoteResponse.message || 'Unable to save the project on the server. Try refresh and save again.', 'error');
                return;
            }
        }

        if (!saveToLocalStorage(fullPath, data)) {
            return;
        }
        ensureProjectInFolderStructure(fullPath);
        const savedMessage = tr('project_saved_named').replace('{name}', currentProjectName);
        showMessage(savedMessage, "success");
    }

    async function handleSaveAs() {
        showSaveAsModal();
    }

    // =========================
    // Event Listeners
    // =========================

    fileBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        if (!fileMenu || fileMenu.style.display === "none") {
            showFileMenu();
        } else {
            hideFileMenu();
        }
    });

    document.addEventListener("click", (e) => {
        if (fileMenu && !fileMenu.contains(e.target) && e.target !== fileBtn) {
            hideFileMenu();
        }
    });

    window.addEventListener('resize', () => {
        if (fileMenu && fileMenu.style.display === 'block') {
            positionFileMenu();
        }
    });

    setTimeout(() => tryAutoLoadPendingProject(0), 0);

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            hideFileMenu();
            closeSaveAsModal();
            closeOpenModal();
            closeNewFolderModal();
        }
    });

    console.log("✅ File system ready!");
});
(function () {
  "use strict";

  function isDarkMode() {
    const root = document.documentElement;
    const body = document.body;

    const attr = (root.getAttribute("data-theme") || body.getAttribute("data-theme") || "").toLowerCase();
    if (attr === "dark") return true;

    const cls = (root.className + " " + body.className).toLowerCase();
    return cls.includes("dark") || cls.includes("theme-dark") || cls.includes("dark-mode") || cls.includes("cdp-dark");
  }

  function menuColors() {
    if (isDarkMode()) {
      return {
        bg: "#0b0b0f",
        border: "#2a2a33",
        text: "#ffffff",
        icon: "#ffffff",
        hoverBg: "#1a1a22",
        shadow: "0 10px 30px rgba(0,0,0,0.45)"
      };
    }
    return {
      bg: "#ffffff",
      border: "#e5e7eb",
      text: "#111827",
      icon: "#111827",
      hoverBg: "#f3f4f6",
      shadow: "0 10px 24px rgba(0,0,0,0.18)"
    };
  }

    function applyFileMenuTheme() {
    const fileMenu = document.querySelector(".cdp-file-menu");
    if (!fileMenu) return;

    const c = menuColors();
    fileMenu.style.background = c.bg;
    fileMenu.style.border = `1px solid ${c.border}`;
    fileMenu.style.boxShadow = c.shadow;

    const btns = fileMenu.querySelectorAll("button");
    btns.forEach((btn) => {
      btn.style.background = "transparent";
      btn.style.color = c.text;

      const icon = btn.querySelector("i");
      const text = btn.querySelector("span");
      if (icon) icon.style.color = c.icon;
      if (text) text.style.color = c.text;

      btn.onmouseenter = () => { btn.style.background = c.hoverBg; };
      btn.onmouseleave = () => { btn.style.background = "transparent"; };
    });
  }

    window.applyFileMenuTheme = applyFileMenuTheme;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", applyFileMenuTheme);
  } else {
    applyFileMenuTheme();
  }

  const obs = new MutationObserver(applyFileMenuTheme);
  obs.observe(document.documentElement, { attributes: true, attributeFilter: ["class", "data-theme"] });
  obs.observe(document.body, { attributes: true, attributeFilter: ["class", "data-theme"] });

  document.addEventListener("click", () => setTimeout(applyFileMenuTheme, 0), true);
  window.addEventListener("cdp-theme-changed", applyFileMenuTheme);
})();
// =========================
// i18n – File System (11 locales)
// Keys cover: File menu + Open modal + Save As modal + common prompts
// =========================

// Country code to language code mapping
const COUNTRY_TO_LANG = {
    IT: 'it', it: 'it',
    FR: 'fr', fr: 'fr',
    DE: 'de', de: 'de',
    ES: 'es', es: 'es',
    NL: 'nl', nl: 'nl',
    PL: 'pl', pl: 'pl',
    SE: 'sv', se: 'sv',
    SV: 'sv', sv: 'sv',
    CH: 'ch', ch: 'ch',
    US: 'us', us: 'us',
    GB: 'gb', gb: 'gb',
    UK: 'gb', uk: 'gb',
    CA: 'ca', ca: 'ca'
};

// Set language by country code
window.cdpSetCountryLang = function(countryCode) {
    const raw = (countryCode || '').toString();
    const upper = raw.toUpperCase();
    const lower = raw.toLowerCase();
    const lang = COUNTRY_TO_LANG[lower] || COUNTRY_TO_LANG[upper] || 'us';
    if (window.cdpSetLang) {
        window.cdpSetLang(lang, { locale: upper });
    } else {
        localStorage.setItem('cdpLang', lang);
        window.dispatchEvent(new CustomEvent('cdp-locale-changed', { detail: { lang, locale: upper } }));
    }
};

const CDP_LANG = {
  // 1) EN (Default)
  en: {
    file_new: "New",
    file_open: "Open",
    file_save: "Save",
    file_saveas: "Save As",

    open_title: "Open Project",
    saveas_title: "Save As",
    root: "Root",
    back: "Back",
    cancel: "Cancel",
    save: "Save",
    new_folder: "New Folder",
    file_name: "File Name:",

    empty_folder_title: "This folder is empty",
    empty_folder_sub_open: "No projects found",
    empty_folder_sub_saveas: 'Click "New Folder" to create a folder',

    ask_save_changes: "Do you want to save changes?",
    dont_save: "Don't Save",

    msg_saved: 'Project "{name}" saved successfully!',
    msg_loaded: 'Project "{name}" loaded successfully!',
    msg_not_found: "Project not found!",
    msg_expired: "Project has expired (90 days limit)!",
    msg_enter_filename: "Please enter a file name!",
    msg_folder_exists: "This folder already exists!",
    msg_folder_name_required: "Please enter a folder name!",
    msg_folder_created: 'Folder "{name}" created!',
    msg_new_design: "New design created!"
  },

  // 2) IT – Italy
  it: {
    file_new: "Nuovo",
    file_open: "Apri",
    file_save: "Salva",
    file_saveas: "Salva con nome",

    open_title: "Apri progetto",
    saveas_title: "Salva con nome",
    root: "Root",
    back: "Indietro",
    cancel: "Annulla",
    save: "Salva",
    new_folder: "Nuova cartella",
    file_name: "Nome file:",

    empty_folder_title: "Questa cartella è vuota",
    empty_folder_sub_open: "Nessun progetto trovato",
    empty_folder_sub_saveas: 'Clicca "Nuova cartella" per creare una cartella',

    ask_save_changes: "Vuoi salvare le modifiche?",
    dont_save: "Non salvare",

    msg_saved: 'Progetto "{name}" salvato con successo!',
    msg_loaded: 'Progetto "{name}" caricato con successo!',
    msg_not_found: "Progetto non trovato!",
    msg_expired: "Il progetto è scaduto (limite 90 giorni)!",
    msg_enter_filename: "Inserisci un nome file!",
    msg_folder_exists: "Questa cartella esiste già!",
    msg_folder_name_required: "Inserisci un nome cartella!",
    msg_folder_created: 'Cartella "{name}" creata!',
    msg_new_design: "Nuovo design creato!"
  },

  // 3) FR – France
  fr: {
    file_new: "Nouveau",
    file_open: "Ouvrir",
    file_save: "Enregistrer",
    file_saveas: "Enregistrer sous",

    open_title: "Ouvrir un projet",
    saveas_title: "Enregistrer sous",
    root: "Racine",
    back: "Retour",
    cancel: "Annuler",
    save: "Enregistrer",
    new_folder: "Nouveau dossier",
    file_name: "Nom du fichier :",

    empty_folder_title: "Ce dossier est vide",
    empty_folder_sub_open: "Aucun projet trouvé",
    empty_folder_sub_saveas: 'Cliquez sur "Nouveau dossier" pour créer un dossier',

    ask_save_changes: "Voulez-vous enregistrer les modifications ?",
    dont_save: "Ne pas enregistrer",

    msg_saved: 'Projet "{name}" enregistré avec succès !',
    msg_loaded: 'Projet "{name}" chargé avec succès !',
    msg_not_found: "Projet introuvable !",
    msg_expired: "Le projet a expiré (limite 90 jours) !",
    msg_enter_filename: "Veuillez saisir un nom de fichier !",
    msg_folder_exists: "Ce dossier existe déjà !",
    msg_folder_name_required: "Veuillez saisir un nom de dossier !",
    msg_folder_created: 'Dossier "{name}" créé !',
    msg_new_design: "Nouveau design créé !"
  },

  // 4) DE – Germany
  de: {
    file_new: "Neu",
    file_open: "Öffnen",
    file_save: "Speichern",
    file_saveas: "Speichern unter",

    open_title: "Projekt öffnen",
    saveas_title: "Speichern unter",
    root: "Root",
    back: "Zurück",
    cancel: "Abbrechen",
    save: "Speichern",
    new_folder: "Neuer Ordner",
    file_name: "Dateiname:",

    empty_folder_title: "Dieser Ordner ist leer",
    empty_folder_sub_open: "Keine Projekte gefunden",
    empty_folder_sub_saveas: 'Klicke auf "Neuer Ordner", um einen Ordner zu erstellen',

    ask_save_changes: "Möchten Sie die Änderungen speichern?",
    dont_save: "Nicht speichern",

    msg_saved: 'Projekt "{name}" erfolgreich gespeichert!',
    msg_loaded: 'Projekt "{name}" erfolgreich geladen!',
    msg_not_found: "Projekt nicht gefunden!",
    msg_expired: "Projekt ist abgelaufen (90-Tage-Limit)!",
    msg_enter_filename: "Bitte einen Dateinamen eingeben!",
    msg_folder_exists: "Dieser Ordner existiert bereits!",
    msg_folder_name_required: "Bitte einen Ordnernamen eingeben!",
    msg_folder_created: 'Ordner "{name}" erstellt!',
    msg_new_design: "Neues Design erstellt!"
  },

  // 5) ES – Spain
  es: {
    file_new: "Nuevo",
    file_open: "Abrir",
    file_save: "Guardar",
    file_saveas: "Guardar como",

    open_title: "Abrir proyecto",
    saveas_title: "Guardar como",
    root: "Raíz",
    back: "Atrás",
    cancel: "Cancelar",
    save: "Guardar",
    new_folder: "Nueva carpeta",
    file_name: "Nombre de archivo:",

    empty_folder_title: "Esta carpeta está vacía",
    empty_folder_sub_open: "No se encontraron proyectos",
    empty_folder_sub_saveas: 'Haz clic en "Nueva carpeta" para crear una carpeta',

    ask_save_changes: "¿Quieres guardar los cambios?",
    dont_save: "No guardar",

    msg_saved: '¡Proyecto "{name}" guardado correctamente!',
    msg_loaded: '¡Proyecto "{name}" cargado correctamente!',
    msg_not_found: "¡Proyecto no encontrado!",
    msg_expired: "¡El proyecto ha caducado (límite 90 días)!",
    msg_enter_filename: "¡Introduce un nombre de archivo!",
    msg_folder_exists: "¡Esta carpeta ya existe!",
    msg_folder_name_required: "¡Introduce un nombre de carpeta!",
    msg_folder_created: '¡Carpeta "{name}" creada!',
    msg_new_design: "¡Nuevo diseño creado!"
  },

  // 6) NL – Netherlands
  nl: {
    file_new: "Nieuw",
    file_open: "Openen",
    file_save: "Opslaan",
    file_saveas: "Opslaan als",

    open_title: "Project openen",
    saveas_title: "Opslaan als",
    root: "Hoofdmap",
    back: "Terug",
    cancel: "Annuleren",
    save: "Opslaan",
    new_folder: "Nieuwe map",
    file_name: "Bestandsnaam:",

    empty_folder_title: "Deze map is leeg",
    empty_folder_sub_open: "Geen projecten gevonden",
    empty_folder_sub_saveas: 'Klik op "Nieuwe map" om een map te maken',

    ask_save_changes: "Wil je de wijzigingen opslaan?",
    dont_save: "Niet opslaan",

    msg_saved: 'Project "{name}" succesvol opgeslagen!',
    msg_loaded: 'Project "{name}" succesvol geladen!',
    msg_not_found: "Project niet gevonden!",
    msg_expired: "Project is verlopen (limiet 90 dagen)!",
    msg_enter_filename: "Voer een bestandsnaam in!",
    msg_folder_exists: "Deze map bestaat al!",
    msg_folder_name_required: "Voer een mapnaam in!",
    msg_folder_created: 'Map "{name}" aangemaakt!',
    msg_new_design: "Nieuw ontwerp gemaakt!"
  },

  // 7) PL – Poland
  pl: {
    file_new: "Nowy",
    file_open: "Otwórz",
    file_save: "Zapisz",
    file_saveas: "Zapisz jako",

    open_title: "Otwórz projekt",
    saveas_title: "Zapisz jako",
    root: "Root",
    back: "Wstecz",
    cancel: "Anuluj",
    save: "Zapisz",
    new_folder: "Nowy folder",
    file_name: "Nazwa pliku:",

    empty_folder_title: "Ten folder jest pusty",
    empty_folder_sub_open: "Nie znaleziono projektów",
    empty_folder_sub_saveas: 'Kliknij „Nowy folder”, aby utworzyć folder',

    ask_save_changes: "Czy chcesz zapisać zmiany?",
    dont_save: "Nie zapisuj",

    msg_saved: 'Projekt "{name}" zapisany pomyślnie!',
    msg_loaded: 'Projekt "{name}" wczytany pomyślnie!',
    msg_not_found: "Nie znaleziono projektu!",
    msg_expired: "Projekt wygasł (limit 90 dni)!",
    msg_enter_filename: "Wpisz nazwę pliku!",
    msg_folder_exists: "Ten folder już istnieje!",
    msg_folder_name_required: "Wpisz nazwę folderu!",
    msg_folder_created: 'Folder "{name}" utworzony!',
    msg_new_design: "Utworzono nowy projekt!"
  },

  // 8) SV – Sweden
  sv: {
    file_new: "Ny",
    file_open: "Öppna",
    file_save: "Spara",
    file_saveas: "Spara som",

    open_title: "Öppna projekt",
    saveas_title: "Spara som",
    root: "Root",
    back: "Tillbaka",
    cancel: "Avbryt",
    save: "Spara",
    new_folder: "Ny mapp",
    file_name: "Filnamn:",

    empty_folder_title: "Den här mappen är tom",
    empty_folder_sub_open: "Inga projekt hittades",
    empty_folder_sub_saveas: 'Klicka på "Ny mapp" för att skapa en mapp',

    ask_save_changes: "Vill du spara ändringarna?",
    dont_save: "Spara inte",

    msg_saved: 'Projekt "{name}" sparades!',
    msg_loaded: 'Projekt "{name}" laddades!',
    msg_not_found: "Projektet hittades inte!",
    msg_expired: "Projektet har gått ut (90 dagars gräns)!",
    msg_enter_filename: "Ange ett filnamn!",
    msg_folder_exists: "Mappen finns redan!",
    msg_folder_name_required: "Ange ett mappnamn!",
    msg_folder_created: 'Mapp "{name}" skapad!',
    msg_new_design: "Ny design skapad!"
  },

  // 9) GB – United Kingdom (English)
  gb: {
    file_new: "New",
    file_open: "Open",
    file_save: "Save",
    file_saveas: "Save As",

    open_title: "Open Project",
    saveas_title: "Save As",
    root: "Root",
    back: "Back",
    cancel: "Cancel",
    save: "Save",
    new_folder: "New Folder",
    file_name: "File Name:",

    empty_folder_title: "This folder is empty",
    empty_folder_sub_open: "No projects found",
    empty_folder_sub_saveas: 'Click "New Folder" to create a folder',

    ask_save_changes: "Do you want to save changes?",
    dont_save: "Don't Save",

    msg_saved: 'Project "{name}" saved successfully!',
    msg_loaded: 'Project "{name}" loaded successfully!',
    msg_not_found: "Project not found!",
    msg_expired: "Project has expired (90 days limit)!",
    msg_enter_filename: "Please enter a file name!",
    msg_folder_exists: "This folder already exists!",
    msg_folder_name_required: "Please enter a folder name!",
    msg_folder_created: 'Folder "{name}" created!',
    msg_new_design: "New design created!"
  },

  // 10) US – United States (English)
  us: {
    file_new: "New",
    file_open: "Open",
    file_save: "Save",
    file_saveas: "Save As",

    open_title: "Open Project",
    saveas_title: "Save As",
    root: "Root",
    back: "Back",
    cancel: "Cancel",
    save: "Save",
    new_folder: "New Folder",
    file_name: "File Name:",

    empty_folder_title: "This folder is empty",
    empty_folder_sub_open: "No projects found",
    empty_folder_sub_saveas: 'Click "New Folder" to create a folder',

    ask_save_changes: "Do you want to save changes?",
    dont_save: "Don't Save",

    msg_saved: 'Project "{name}" saved successfully!',
    msg_loaded: 'Project "{name}" loaded successfully!',
    msg_not_found: "Project not found!",
    msg_expired: "Project has expired (90 days limit)!",
    msg_enter_filename: "Please enter a file name!",
    msg_folder_exists: "This folder already exists!",
    msg_folder_name_required: "Please enter a folder name!",
    msg_folder_created: 'Folder "{name}" created!',
    msg_new_design: "New design created!"
  },

  // 11) CH – Switzerland (German)
  ch: {
    file_new: "Neu",
    file_open: "Öffnen",
    file_save: "Speichern",
    file_saveas: "Speichern unter",

    open_title: "Projekt öffnen",
    saveas_title: "Speichern unter",
    root: "Root",
    back: "Zurück",
    cancel: "Abbrechen",
    save: "Speichern",
    new_folder: "Neuer Ordner",
    file_name: "Dateiname:",

    empty_folder_title: "Dieser Ordner ist leer",
    empty_folder_sub_open: "Keine Projekte gefunden",
    empty_folder_sub_saveas: 'Klicke auf "Neuer Ordner", um einen Ordner zu erstellen',

    ask_save_changes: "Möchten Sie die Änderungen speichern?",
    dont_save: "Nicht speichern",

    msg_saved: 'Projekt "{name}" erfolgreich gespeichert!',
    msg_loaded: 'Projekt "{name}" erfolgreich geladen!',
    msg_not_found: "Projekt nicht gefunden!",
    msg_expired: "Projekt ist abgelaufen (90-Tage-Limit)!",
    msg_enter_filename: "Bitte einen Dateinamen eingeben!",
    msg_folder_exists: "Dieser Ordner existiert bereits!",
    msg_folder_name_required: "Bitte einen Ordnernamen eingeben!",
    msg_folder_created: 'Ordner "{name}" erstellt!',
    msg_new_design: "Neues Design erstellt!"
  },

  // 12) CA – Canada (English)
  ca: {
    file_new: "New",
    file_open: "Open",
    file_save: "Save",
    file_saveas: "Save As",

    open_title: "Open Project",
    saveas_title: "Save As",
    root: "Root",
    back: "Back",
    cancel: "Cancel",
    save: "Save",
    new_folder: "New Folder",
    file_name: "File Name:",

    empty_folder_title: "This folder is empty",
    empty_folder_sub_open: "No projects found",
    empty_folder_sub_saveas: 'Click "New Folder" to create a folder',

    ask_save_changes: "Do you want to save changes?",
    dont_save: "Don't Save",

    msg_saved: 'Project "{name}" saved successfully!',
    msg_loaded: 'Project "{name}" loaded successfully!',
    msg_not_found: "Project not found!",
    msg_expired: "Project has expired (90 days limit)!",
    msg_enter_filename: "Please enter a file name!",
    msg_folder_exists: "This folder already exists!",
    msg_folder_name_required: "Please enter a folder name!",
    msg_folder_created: 'Folder "{name}" created!',
    msg_new_design: "New design created!"
  }
};
