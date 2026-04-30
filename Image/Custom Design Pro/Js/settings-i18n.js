(function () {
  'use strict';

  const STRINGS = {
    us: {
      settings_panel_title: 'Settings',
      settings_section_appearance: 'Appearance',
      settings_section_audio: 'Audio',
      settings_theme_label: 'Theme',
      settings_theme_desc: 'Choose light or dark mode',
      settings_theme_light: 'Light',
      settings_theme_dark: 'Dark',
      settings_font_label: 'Font Size',
      settings_font_desc: 'Adjust text size',
      settings_font_small: 'Small',
      settings_font_medium: 'Medium',
      settings_font_large: 'Large',
      settings_bg_music_label: 'Background Music',
      settings_bg_music_desc: 'Play music while working',
      settings_music_track_label: 'Music Track',
      settings_music_track_desc: 'Choose background music',
      settings_sound_label: 'Sound Effects',
      settings_sound_desc: 'Play sounds on actions',
      settings_volume_label: 'Volume',
      settings_volume_desc: 'Adjust audio volume',
      settings_toggle_off: 'Off',
      settings_toggle_on: 'On'
    },
    it: {
      settings_panel_title: 'Impostazioni',
      settings_section_appearance: 'Aspetto',
      settings_section_audio: 'Audio',
      settings_theme_label: 'Tema',
      settings_theme_desc: 'Scegli modalità chiara o scura',
      settings_theme_light: 'Chiaro',
      settings_theme_dark: 'Scuro',
      settings_font_label: 'Dimensione testo',
      settings_font_desc: 'Regola la dimensione del testo',
      settings_font_small: 'Piccolo',
      settings_font_medium: 'Medio',
      settings_font_large: 'Grande',
      settings_bg_music_label: 'Musica di sottofondo',
      settings_bg_music_desc: 'Riproduci musica mentre lavori',
      settings_music_track_label: 'Brano musicale',
      settings_music_track_desc: 'Scegli la traccia di sottofondo',
      settings_sound_label: 'Effetti sonori',
      settings_sound_desc: 'Riproduci suoni per le azioni',
      settings_volume_label: 'Volume',
      settings_volume_desc: 'Regola il volume audio',
      settings_toggle_off: 'Spento',
      settings_toggle_on: 'Acceso'
    },
    de: {
      settings_panel_title: 'Einstellungen',
      settings_section_appearance: 'Darstellung',
      settings_section_audio: 'Audio',
      settings_theme_label: 'Design',
      settings_theme_desc: 'Hell- oder Dunkelmodus auswählen',
      settings_theme_light: 'Hell',
      settings_theme_dark: 'Dunkel',
      settings_font_label: 'Schriftgröße',
      settings_font_desc: 'Textgröße anpassen',
      settings_font_small: 'Klein',
      settings_font_medium: 'Mittel',
      settings_font_large: 'Groß',
      settings_bg_music_label: 'Hintergrundmusik',
      settings_bg_music_desc: 'Musik während der Arbeit abspielen',
      settings_music_track_label: 'Titel auswählen',
      settings_music_track_desc: 'Hintergrundmusik wählen',
      settings_sound_label: 'Soundeffekte',
      settings_sound_desc: 'Klänge bei Aktionen abspielen',
      settings_volume_label: 'Lautstärke',
      settings_volume_desc: 'Audio-Lautstärke einstellen',
      settings_toggle_off: 'Aus',
      settings_toggle_on: 'Ein'
    },
    fr: {
      settings_panel_title: 'Paramètres',
      settings_section_appearance: 'Apparence',
      settings_section_audio: 'Audio',
      settings_theme_label: 'Thème',
      settings_theme_desc: 'Choisissez le mode clair ou sombre',
      settings_theme_light: 'Clair',
      settings_theme_dark: 'Sombre',
      settings_font_label: 'Taille du texte',
      settings_font_desc: 'Ajustez la taille du texte',
      settings_font_small: 'Petit',
      settings_font_medium: 'Moyen',
      settings_font_large: 'Grand',
      settings_bg_music_label: "Musique d'ambiance",
      settings_bg_music_desc: 'Écoutez de la musique pendant le travail',
      settings_music_track_label: 'Piste musicale',
      settings_music_track_desc: 'Choisissez la musique de fond',
      settings_sound_label: 'Effets sonores',
      settings_sound_desc: 'Jouer des sons lors des actions',
      settings_volume_label: 'Volume',
      settings_volume_desc: 'Ajuster le volume audio',
      settings_toggle_off: 'Arrêt',
      settings_toggle_on: 'Marche'
    },
    es: {
      settings_panel_title: 'Configuración',
      settings_section_appearance: 'Apariencia',
      settings_section_audio: 'Audio',
      settings_theme_label: 'Tema',
      settings_theme_desc: 'Elegir modo claro u oscuro',
      settings_theme_light: 'Claro',
      settings_theme_dark: 'Oscuro',
      settings_font_label: 'Tamaño de fuente',
      settings_font_desc: 'Ajusta el tamaño del texto',
      settings_font_small: 'Pequeño',
      settings_font_medium: 'Medio',
      settings_font_large: 'Grande',
      settings_bg_music_label: 'Música de fondo',
      settings_bg_music_desc: 'Reproduce música mientras trabajas',
      settings_music_track_label: 'Pista musical',
      settings_music_track_desc: 'Elige la música de fondo',
      settings_sound_label: 'Efectos de sonido',
      settings_sound_desc: 'Reproduce sonidos en las acciones',
      settings_volume_label: 'Volumen',
      settings_volume_desc: 'Ajusta el volumen de audio',
      settings_toggle_off: 'Apagado',
      settings_toggle_on: 'Encendido'
    },
    nl: {
      settings_panel_title: 'Instellingen',
      settings_section_appearance: 'Weergave',
      settings_section_audio: 'Audio',
      settings_theme_label: 'Thema',
      settings_theme_desc: 'Kies lichte of donkere modus',
      settings_theme_light: 'Licht',
      settings_theme_dark: 'Donker',
      settings_font_label: 'Lettergrootte',
      settings_font_desc: 'Pas de tekstgrootte aan',
      settings_font_small: 'Klein',
      settings_font_medium: 'Middel',
      settings_font_large: 'Groot',
      settings_bg_music_label: 'Achtergrondmuziek',
      settings_bg_music_desc: 'Speel muziek af tijdens het werken',
      settings_music_track_label: 'Muzieknummer',
      settings_music_track_desc: 'Kies de achtergrondmuziek',
      settings_sound_label: 'Geluidseffecten',
      settings_sound_desc: 'Speel geluiden bij acties af',
      settings_volume_label: 'Volume',
      settings_volume_desc: 'Pas het audiovolume aan',
      settings_toggle_off: 'Uit',
      settings_toggle_on: 'Aan'
    },
    pl: {
      settings_panel_title: 'Ustawienia',
      settings_section_appearance: 'Wygląd',
      settings_section_audio: 'Dźwięk',
      settings_theme_label: 'Motyw',
      settings_theme_desc: 'Wybierz tryb jasny lub ciemny',
      settings_theme_light: 'Jasny',
      settings_theme_dark: 'Ciemny',
      settings_font_label: 'Rozmiar czcionki',
      settings_font_desc: 'Dostosuj wielkość tekstu',
      settings_font_small: 'Mały',
      settings_font_medium: 'Średni',
      settings_font_large: 'Duży',
      settings_bg_music_label: 'Muzyka w tle',
      settings_bg_music_desc: 'Odtwarzaj muzykę podczas pracy',
      settings_music_track_label: 'Utwór muzyczny',
      settings_music_track_desc: 'Wybierz muzykę w tle',
      settings_sound_label: 'Efekty dźwiękowe',
      settings_sound_desc: 'Odtwarzaj dźwięki przy akcjach',
      settings_volume_label: 'Głośność',
      settings_volume_desc: 'Dostosuj poziom głośności',
      settings_toggle_off: 'Wył.',
      settings_toggle_on: 'Wł.'
    },
    sv: {
      settings_panel_title: 'Inställningar',
      settings_section_appearance: 'Utseende',
      settings_section_audio: 'Ljud',
      settings_theme_label: 'Tema',
      settings_theme_desc: 'Välj ljust eller mörkt läge',
      settings_theme_light: 'Ljus',
      settings_theme_dark: 'Mörk',
      settings_font_label: 'Textstorlek',
      settings_font_desc: 'Justera textstorleken',
      settings_font_small: 'Liten',
      settings_font_medium: 'Mellan',
      settings_font_large: 'Stor',
      settings_bg_music_label: 'Bakgrundsmusik',
      settings_bg_music_desc: 'Spela musik medan du arbetar',
      settings_music_track_label: 'Musikspår',
      settings_music_track_desc: 'Välj bakgrundsmusik',
      settings_sound_label: 'Ljudeffekter',
      settings_sound_desc: 'Spela ljud vid åtgärder',
      settings_volume_label: 'Volym',
      settings_volume_desc: 'Justera ljudvolymen',
      settings_toggle_off: 'Av',
      settings_toggle_on: 'På'
    }
  };

  const FALLBACKS = {
    gb: 'us',
    ca: 'us',
    ch: 'de'
  };

  function resolveLang(code) {
    const normalized = (code || 'us').toLowerCase();
    if (STRINGS[normalized]) {
      return normalized;
    }
    return FALLBACKS[normalized] || 'us';
  }

  function getCurrentLang() {
    const stored = localStorage.getItem('cdpLang');
    return resolveLang(stored || 'us');
  }

  function applySettingsTranslations() {
    const lang = getCurrentLang();
    const dictionary = STRINGS[lang] || STRINGS.us;
    const fallback = STRINGS.us;

    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      if (!key) return;
      const text = dictionary[key] || fallback[key];
      if (typeof text === 'string') {
        el.textContent = text;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', applySettingsTranslations);
  window.addEventListener('cdp-locale-changed', applySettingsTranslations);

  window.cdpApplySettingsTranslations = applySettingsTranslations;
})();
