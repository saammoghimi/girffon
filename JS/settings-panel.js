(function () {
  "use strict";

  const settingsTrigger = document.getElementById("gfSettingsTrigger");
  const settingsPanel = document.getElementById("gfSettingsPanel");
  const settingsOverlay = document.getElementById("gfSettingsOverlay");
  const closeBtn = settingsPanel?.querySelector(".gf-settings-close");
  const audioEl = document.getElementById("gfBackgroundMusic");
  const trackSelect = document.getElementById("gfMusicTrack");
  const volumeSlider = document.getElementById("gfVolumeSlider");
  const volumeValue = document.getElementById("gfVolumeValue");

  if (!settingsTrigger || !settingsPanel || !settingsOverlay) {
    return;
  }

  const STORAGE_KEYS = {
    theme: "gf-theme",
    font: "gf-font-size",
    music: "gf-music",
    sound: "gf-sound",
    volume: "gf-volume",
    track: "gf-track"
  };

  const musicPath = "Image/settings/audio/music/";
  const fontScaleMap = {
    small: 0.92,
    medium: 1,
    large: 1.1
  };
  const FONT_TARGET_SELECTOR = [
    "h1", "h2", "h3", "h4", "h5", "h6",
    "p", "a", "button", "span", "li", "label",
    "input", "select", "textarea"
  ].join(",");
  const availableTracks = [
    "mixkit-a-very-happy-christmas-897.mp3",
    "mixkit-beautiful-dream-493.mp3",
    "mixkit-cant-get-you-off-my-mind-1210.mp3",
    "mixkit-cat-walk-371.mp3",
    "mixkit-cbpd-400.mp3",
    "mixkit-complicated-281.mp3",
    "mixkit-deep-urban-623.mp3",
    "mixkit-dirty-thinkin-989.mp3",
    "mixkit-discover-587.mp3",
    "mixkit-driving-ambition-32.mp3",
    "mixkit-epical-drums-01-676.mp3",
    "mixkit-forest-treasure-138.mp3",
    "mixkit-fright-night-871.mp3",
    "mixkit-games-worldbeat-466.mp3",
    "mixkit-gimme-that-groove-872.mp3",
    "mixkit-hazy-after-hours-132.mp3",
    "mixkit-hip-hop-02-738.mp3",
    "mixkit-island-beat-250.mp3",
    "mixkit-latin-lovers-39.mp3",
    "mixkit-night-sky-hip-hop-970.mp3",
    "mixkit-piano-horror-671.mp3",
    "mixkit-pop-05-695.mp3",
    "mixkit-praise-the-lord-262.mp3",
    "mixkit-relaxing-in-nature-522.mp3",
    "mixkit-romantic-01-752.mp3",
    "mixkit-romantic-05-759.mp3",
    "mixkit-romantic-659.mp3",
    "mixkit-serene-view-443.mp3",
    "mixkit-silent-descent-614.mp3",
    "mixkit-spirit-in-the-woods-139.mp3",
    "mixkit-sports-highlights-51.mp3",
    "mixkit-sun-and-his-daughter-580.mp3",
    "mixkit-tech-house-vibes-130.mp3",
    "mixkit-valley-sunset-127.mp3",
    "mixkit-villa-penthouse-339.mp3",
    "mixkit-wedding-01-657.mp3"
  ];
  const defaultTrack = "mixkit-tech-house-vibes-130.mp3";
  const state = {
    theme: "light",
    font: "small",
    music: "off",
    sound: "off",
    volume: 100,
    track: defaultTrack
  };

  function formatTrackLabel(fileName) {
    return fileName
      .replace(/^mixkit-/, "")
      .replace(/\.mp3$/, "")
      .replace(/-\d+(?:-\d+)?$/, "")
      .replace(/-/g, " ")
      .replace(/\b\w/g, (char) => char.toUpperCase());
  }

  function populateTrackOptions() {
    if (!trackSelect) {
      return;
    }

    trackSelect.innerHTML = "";

    availableTracks.forEach((trackFile) => {
      const option = document.createElement("option");
      option.value = trackFile;
      option.textContent = formatTrackLabel(trackFile);
      trackSelect.appendChild(option);
    });
  }

  function setPanelVisibility(visible) {
    settingsPanel.setAttribute("data-visible", visible ? "true" : "false");
    settingsPanel.setAttribute("aria-hidden", visible ? "false" : "true");
    settingsOverlay.hidden = !visible;
    document.body.style.overflow = visible ? "hidden" : "";
  }

  function activateGroup(setting, value) {
    const buttons = settingsPanel.querySelectorAll(`[data-setting="${setting}"]`);
    buttons.forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.value === value);
    });
  }

  function applyTheme(theme) {
    state.theme = theme;
    document.body.classList.toggle("gf-dark-mode", theme === "dark");
    activateGroup("theme", theme);
    localStorage.setItem(STORAGE_KEYS.theme, theme);
  }

  function applyGlobalFontScale(fontKey) {
    const scale = fontScaleMap[fontKey] || 1;
    const textNodes = document.querySelectorAll(FONT_TARGET_SELECTOR);

    textNodes.forEach((node) => {
      if (node.closest("#gfAccountPanel")) {
        return;
      }

      if (!node.dataset.gfBaseFont) {
        const baseSize = Number.parseFloat(window.getComputedStyle(node).fontSize);
        if (Number.isFinite(baseSize) && baseSize > 0) {
          node.dataset.gfBaseFont = String(baseSize);
        }
      }

      const base = Number.parseFloat(node.dataset.gfBaseFont || "");
      if (!Number.isFinite(base)) {
        return;
      }

      node.style.fontSize = `${(base * scale).toFixed(2)}px`;
    });
  }

  function applyFont(font) {
    state.font = font;
    document.body.classList.remove("gf-font-small", "gf-font-medium", "gf-font-large");
    document.body.classList.add(`gf-font-${font}`);
    applyGlobalFontScale(font);
    activateGroup("font", font);
    localStorage.setItem(STORAGE_KEYS.font, font);
  }

  function updateAudioSource(trackFile) {
    state.track = availableTracks.includes(trackFile) ? trackFile : defaultTrack;
    if (trackSelect) {
      trackSelect.value = state.track;
    }
    if (audioEl) {
      audioEl.src = `${musicPath}${state.track}`;
    }
    localStorage.setItem(STORAGE_KEYS.track, state.track);
  }

  function applyMusic(mode) {
    state.music = mode;
    activateGroup("music", mode);
    localStorage.setItem(STORAGE_KEYS.music, mode);

    if (!audioEl) {
      return;
    }

    if (mode === "on") {
      const playPromise = audioEl.play();
      if (playPromise && typeof playPromise.catch === "function") {
        playPromise.catch(() => {
          state.music = "off";
          activateGroup("music", "off");
          localStorage.setItem(STORAGE_KEYS.music, "off");
        });
      }
      return;
    }

    audioEl.pause();
    audioEl.currentTime = 0;
  }

  function applySound(mode) {
    state.sound = mode;
    activateGroup("sound", mode);
    localStorage.setItem(STORAGE_KEYS.sound, mode);
  }

  function applyVolume(volume) {
    state.volume = volume;
    if (audioEl) {
      audioEl.volume = Math.max(0, Math.min(1, volume / 100));
    }
    if (volumeSlider) {
      volumeSlider.value = String(volume);
    }
    if (volumeValue) {
      volumeValue.textContent = `${volume}%`;
    }
    localStorage.setItem(STORAGE_KEYS.volume, String(volume));
  }

  function loadState() {
    populateTrackOptions();

    const savedTheme = localStorage.getItem(STORAGE_KEYS.theme);
    const savedFont = localStorage.getItem(STORAGE_KEYS.font);
    const savedMusic = localStorage.getItem(STORAGE_KEYS.music);
    const savedSound = localStorage.getItem(STORAGE_KEYS.sound);
    const savedVolume = localStorage.getItem(STORAGE_KEYS.volume);
    const savedTrack = localStorage.getItem(STORAGE_KEYS.track);

    if (savedTheme === "dark" || savedTheme === "light") {
      state.theme = savedTheme;
    }
    if (["small", "medium", "large"].includes(savedFont)) {
      state.font = savedFont;
    }
    if (savedMusic === "on" || savedMusic === "off") {
      state.music = savedMusic;
    }
    if (savedSound === "on" || savedSound === "off") {
      state.sound = savedSound;
    }
    if (savedTrack && availableTracks.includes(savedTrack)) {
      state.track = savedTrack;
    }

    const parsedVolume = Number.parseInt(savedVolume || "100", 10);
    if (Number.isFinite(parsedVolume) && parsedVolume >= 0 && parsedVolume <= 100) {
      state.volume = parsedVolume;
    }

    updateAudioSource(state.track);
    applyTheme(state.theme);
    applyFont(state.font);
    applySound(state.sound);
    applyVolume(state.volume);
    applyMusic(state.music);
  }

  settingsTrigger.addEventListener("click", (event) => {
    event.preventDefault();
    const isOpen = settingsPanel.getAttribute("data-visible") === "true";
    setPanelVisibility(!isOpen);
  });

  closeBtn?.addEventListener("click", () => setPanelVisibility(false));
  settingsOverlay.addEventListener("click", () => setPanelVisibility(false));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      setPanelVisibility(false);
    }
  });

  settingsPanel.addEventListener("click", (event) => {
    const targetBtn = event.target.closest("button[data-setting]");
    if (!targetBtn) {
      return;
    }

    const setting = targetBtn.dataset.setting;
    const value = targetBtn.dataset.value;

    if (setting === "theme") {
      applyTheme(value);
    }
    if (setting === "font") {
      applyFont(value);
    }
    if (setting === "music") {
      applyMusic(value);
    }
    if (setting === "sound") {
      applySound(value);
    }
  });

  trackSelect?.addEventListener("change", (event) => {
    updateAudioSource(event.target.value);
    if (state.music === "on") {
      applyMusic("on");
    }
  });

  volumeSlider?.addEventListener("input", (event) => {
    applyVolume(Number.parseInt(event.target.value, 10));
  });

  loadState();
})();