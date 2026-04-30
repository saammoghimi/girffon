/* ===================================
   SETTINGS.JS - Application Settings
   =================================== */

(function() {
    'use strict';

    // Current settings
    let currentTheme = 'light';
    let currentFontSize = 'medium';
    let musicEnabled = true;
    let soundEnabled = true;
    let currentVolume = 50;
    let currentTrack = 1;
    let backgroundMusic = null;

    // DOM Elements
    const settingsBtn = document.querySelector('[data-tool="settings"]');
    const settingsPanel = document.getElementById('cdpSettingsPanel');
    const closeBtn = settingsPanel?.querySelector('.cdp-settings-close');
    const themeBtns = settingsPanel?.querySelectorAll('.cdp-theme-btn');
    const fontsizeBtns = settingsPanel?.querySelectorAll('.cdp-fontsize-btn');
    const musicBtns = settingsPanel?.querySelectorAll('.cdp-music-btn');
    const soundBtns = settingsPanel?.querySelectorAll('.cdp-sound-btn');
    const musicTrackSelect = document.getElementById('cdpMusicTrack');
    const volumeSlider = document.getElementById('cdpVolumeSlider');
    const volumeValue = document.getElementById('cdpVolumeValue');

    // Initialize
    function init() {
        if (!settingsBtn || !settingsPanel) {
            console.error('Settings elements not found');
            return;
        }

        populateTrackOptions();
        loadSavedSettings();
        attachEventListeners();
        console.log('✅ Settings system initialized');
    }

    // Audio tracks configuration
    const audioTrackList = [
        { id: 1, label: 'Very Happy Christmas', file: 'audio/music/mixkit-a-very-happy-christmas-897.mp3' },
        { id: 2, label: 'Beautiful Dream', file: 'audio/music/mixkit-beautiful-dream-493.mp3' },
        { id: 3, label: "Can't Get You Off My Mind", file: 'audio/music/mixkit-cant-get-you-off-my-mind-1210.mp3' },
        { id: 4, label: 'Cat Walk', file: 'audio/music/mixkit-cat-walk-371.mp3' },
        { id: 5, label: 'CBPD Pulse', file: 'audio/music/mixkit-cbpd-400.mp3' },
        { id: 6, label: 'Complicated', file: 'audio/music/mixkit-complicated-281.mp3' },
        { id: 7, label: 'Deep Urban', file: 'audio/music/mixkit-deep-urban-623.mp3' },
        { id: 8, label: "Dirty Thinkin'", file: 'audio/music/mixkit-dirty-thinkin-989.mp3' },
        { id: 9, label: 'Discover', file: 'audio/music/mixkit-discover-587.mp3' },
        { id: 10, label: 'Driving Ambition', file: 'audio/music/mixkit-driving-ambition-32.mp3' },
        { id: 11, label: 'Epical Drums', file: 'audio/music/mixkit-epical-drums-01-676.mp3' },
        { id: 12, label: 'Forest Treasure', file: 'audio/music/mixkit-forest-treasure-138.mp3' },
        { id: 13, label: 'Fright Night', file: 'audio/music/mixkit-fright-night-871.mp3' },
        { id: 14, label: 'Games Worldbeat', file: 'audio/music/mixkit-games-worldbeat-466.mp3' },
        { id: 15, label: 'Gimme That Groove', file: 'audio/music/mixkit-gimme-that-groove-872.mp3' },
        { id: 16, label: 'Hazy After Hours', file: 'audio/music/mixkit-hazy-after-hours-132.mp3' },
        { id: 17, label: 'Hip Hop 02', file: 'audio/music/mixkit-hip-hop-02-738.mp3' },
        { id: 18, label: 'Island Beat', file: 'audio/music/mixkit-island-beat-250.mp3' },
        { id: 19, label: 'Latin Lovers', file: 'audio/music/mixkit-latin-lovers-39.mp3' },
        { id: 20, label: 'Night Sky Hip Hop', file: 'audio/music/mixkit-night-sky-hip-hop-970.mp3' },
        { id: 21, label: 'Piano Horror', file: 'audio/music/mixkit-piano-horror-671.mp3' },
        { id: 22, label: 'Pop 05', file: 'audio/music/mixkit-pop-05-695.mp3' },
        { id: 23, label: 'Praise the Lord', file: 'audio/music/mixkit-praise-the-lord-262.mp3' },
        { id: 24, label: 'Relaxing in Nature', file: 'audio/music/mixkit-relaxing-in-nature-522.mp3' },
        { id: 25, label: 'Romantic 01', file: 'audio/music/mixkit-romantic-01-752.mp3' },
        { id: 26, label: 'Romantic 05', file: 'audio/music/mixkit-romantic-05-759.mp3' },
        { id: 27, label: 'Romantic 659', file: 'audio/music/mixkit-romantic-659.mp3' },
        { id: 28, label: 'Serene View', file: 'audio/music/mixkit-serene-view-443.mp3' },
        { id: 29, label: 'Silent Descent', file: 'audio/music/mixkit-silent-descent-614.mp3' },
        { id: 30, label: 'Spirit in the Woods', file: 'audio/music/mixkit-spirit-in-the-woods-139.mp3' },
        { id: 31, label: 'Sports Highlights', file: 'audio/music/mixkit-sports-highlights-51.mp3' },
        { id: 32, label: 'Sun and His Daughter', file: 'audio/music/mixkit-sun-and-his-daughter-580.mp3' },
        { id: 33, label: 'Tech House Vibes', file: 'audio/music/mixkit-tech-house-vibes-130.mp3' },
        { id: 34, label: 'Valley Sunset', file: 'audio/music/mixkit-valley-sunset-127.mp3' },
        { id: 35, label: 'Villa Penthouse', file: 'audio/music/mixkit-villa-penthouse-339.mp3' },
        { id: 36, label: 'Wedding 01', file: 'audio/music/mixkit-wedding-01-657.mp3' }
    ];

    const audioTracks = audioTrackList.reduce((acc, track) => {
        acc[track.id] = track.file;
        return acc;
    }, {});

    const defaultTrackId = audioTrackList[0]?.id || 1;
    currentTrack = defaultTrackId;

    function populateTrackOptions() {
        if (!musicTrackSelect) return;

        const previousValue = musicTrackSelect.value;
        musicTrackSelect.innerHTML = '';

        audioTrackList.forEach(track => {
            const option = document.createElement('option');
            option.value = track.id.toString();
            option.textContent = track.label;
            option.title = track.label;
            musicTrackSelect.appendChild(option);
        });

        const preferredValue = previousValue && audioTracks[parseInt(previousValue, 10)]
            ? previousValue
            : currentTrack.toString();
        musicTrackSelect.value = preferredValue;
    }

    // Initialize background music
    function initializeMusic() {
        if (!backgroundMusic && currentTrack) {
            const trackSrc = audioTracks[currentTrack] || audioTracks[defaultTrackId];
            if (!trackSrc) {
                console.warn('⚠️ No audio track available');
                return;
            }
            backgroundMusic = new Audio(trackSrc);
            backgroundMusic.loop = true;
            backgroundMusic.volume = currentVolume / 100;
        }
    }

    // Load saved settings
    function loadSavedSettings() {
        // Load theme
        const savedTheme = localStorage.getItem('cdp-theme');
        if (savedTheme && (savedTheme === 'light' || savedTheme === 'dark')) {
            currentTheme = savedTheme;
            applyTheme(currentTheme);
        }

        // Load font size
        const savedFontSize = localStorage.getItem('cdp-fontsize');
        if (savedFontSize && ['small', 'medium', 'large'].includes(savedFontSize)) {
            currentFontSize = savedFontSize;
            applyFontSize(currentFontSize);
        }

        // Load audio settings
        const savedMusicEnabled = localStorage.getItem('cdp-music');
        musicEnabled = savedMusicEnabled === null ? true : savedMusicEnabled === 'true';

        const savedSoundEnabled = localStorage.getItem('cdp-sound');
        soundEnabled = savedSoundEnabled === null ? true : savedSoundEnabled === 'true';

        const savedVolume = localStorage.getItem('cdp-volume');
        currentVolume = savedVolume ? parseInt(savedVolume, 10) : 50;

        const savedTrack = localStorage.getItem('cdp-track');
        const parsedTrack = savedTrack ? parseInt(savedTrack, 10) : defaultTrackId;
        currentTrack = audioTracks[parsedTrack] ? parsedTrack : defaultTrackId;

        // Apply audio settings
        applyAudioSettings();

        console.log('📋 Loaded settings:', { 
            theme: currentTheme, 
            fontSize: currentFontSize,
            music: musicEnabled,
            sound: soundEnabled,
            volume: currentVolume,
            track: currentTrack
        });
    }

    // Apply theme
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }

        // Update button states
        themeBtns.forEach(btn => {
            if (btn.dataset.theme === theme) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        currentTheme = theme;
        localStorage.setItem('cdp-theme', theme);
        console.log('🎨 Theme changed to:', theme);
    }

    // Apply font size
    function applyFontSize(size) {
        // Remove all font size classes
        document.body.classList.remove('font-small', 'font-medium', 'font-large');
        
        // Add new font size class
        document.body.classList.add(`font-${size}`);

        // Update button states
        fontsizeBtns.forEach(btn => {
            if (btn.dataset.fontsize === size) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        currentFontSize = size;
        localStorage.setItem('cdp-fontsize', size);
        console.log('📏 Font size changed to:', size);
    }

    // Apply audio settings
    function applyAudioSettings() {
        // Update music button states
        if (musicBtns.length >= 2) {
            if (musicEnabled) {
                musicBtns[0].classList.add('active'); // On button
                musicBtns[1].classList.remove('active');
            } else {
                musicBtns[0].classList.remove('active');
                musicBtns[1].classList.add('active'); // Off button
            }
        }

        // Update sound button states
        if (soundBtns.length >= 2) {
            if (soundEnabled) {
                soundBtns[0].classList.add('active'); // On button
                soundBtns[1].classList.remove('active');
            } else {
                soundBtns[0].classList.remove('active');
                soundBtns[1].classList.add('active'); // Off button
            }
        }

        // Update track select
        if (musicTrackSelect) {
            musicTrackSelect.value = currentTrack.toString();
        }

        // Update volume slider
        if (volumeSlider && volumeValue) {
            volumeSlider.value = currentVolume;
            volumeValue.textContent = `${currentVolume}%`;
        }

        // Initialize and play music if enabled
        if (musicEnabled) {
            initializeMusic();
            if (backgroundMusic && backgroundMusic.paused) {
                backgroundMusic.play().catch(err => {
                    console.warn('⚠️ Autoplay prevented:', err);
                });
            }
        }
    }

    // Play music
    function playMusic() {
        if (!backgroundMusic) {
            initializeMusic();
        }
        if (backgroundMusic && musicEnabled) {
            backgroundMusic.play().catch(err => {
                console.error('❌ Music play error:', err);
            });
            console.log('▶️ Music playing');
        }
    }

    // Pause music
    function pauseMusic() {
        if (backgroundMusic && !backgroundMusic.paused) {
            backgroundMusic.pause();
            console.log('⏸️ Music paused');
        }
    }

    // Switch music track
    function switchTrack(trackNumber) {
        if (!audioTracks[trackNumber]) {
            console.warn('⚠️ Requested track not found, reverting to default');
            trackNumber = defaultTrackId;
        }

        const wasPlaying = backgroundMusic && !backgroundMusic.paused;
        
        // Stop current music
        if (backgroundMusic) {
            backgroundMusic.pause();
            backgroundMusic.currentTime = 0;
        }

        // Load new track
        currentTrack = trackNumber;
        const trackSrc = audioTracks[trackNumber];
        if (!trackSrc) {
            console.error('❌ Unable to load track source');
            return;
        }
        backgroundMusic = new Audio(trackSrc);
        backgroundMusic.loop = true;
        backgroundMusic.volume = currentVolume / 100;

        // Play if was playing
        if (wasPlaying && musicEnabled) {
            backgroundMusic.play().catch(err => {
                console.error('❌ Track switch error:', err);
            });
        }

        localStorage.setItem('cdp-track', trackNumber);
        console.log('🎵 Switched to track:', trackNumber);
    }

    // Update volume
    function updateVolume(value) {
        currentVolume = parseInt(value);
        
        if (backgroundMusic) {
            backgroundMusic.volume = currentVolume / 100;
        }

        if (volumeValue) {
            volumeValue.textContent = `${currentVolume}%`;
        }

        localStorage.setItem('cdp-volume', currentVolume);
    }

    // Play sound effect
    function playSound(soundName) {
        if (!soundEnabled) return;

        const soundEffects = {
            click: 'audio/sounds/click.mp3',
            success: 'audio/sounds/success.mp3',
            error: 'audio/sounds/error.mp3'
        };

        if (soundEffects[soundName]) {
            const sound = new Audio(soundEffects[soundName]);
            sound.volume = currentVolume / 100;
            sound.play().catch(err => {
                console.warn('⚠️ Sound effect error:', err);
            });
        }
    }

    // Show panel
    function showPanel() {
        settingsPanel.setAttribute('data-visible', 'true');
        document.body.style.overflow = 'hidden';
        console.log('⚙️ Settings panel opened');
    }

    // Close panel
    function closePanel() {
        settingsPanel.setAttribute('data-visible', 'false');
        document.body.style.overflow = '';
        console.log('⚙️ Settings panel closed');
    }

    // Event listeners
    function attachEventListeners() {
        // Toggle panel (open/close)
        settingsBtn.addEventListener('click', () => {
            const isOpen = settingsPanel.getAttribute('data-visible') === 'true';
            if (isOpen) {
                closePanel();
            } else {
                showPanel();
            }
        });

        // Close panel
        closeBtn.addEventListener('click', closePanel);

        // Close on backdrop click
        settingsPanel.addEventListener('click', (e) => {
            if (e.target === settingsPanel) {
                closePanel();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && settingsPanel.getAttribute('data-visible') === 'true') {
                closePanel();
            }
        });

        // Theme buttons
        themeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                applyTheme(btn.dataset.theme);
            });
        });

        // Font size buttons
        fontsizeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                applyFontSize(btn.dataset.fontsize);
            });
        });

        // Music buttons
        musicBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const isOn = btn.dataset.music === 'on';
                musicEnabled = isOn;
                
                if (isOn) {
                    playMusic();
                } else {
                    pauseMusic();
                }

                localStorage.setItem('cdp-music', musicEnabled);
                applyAudioSettings();
                playSound('click');
                console.log('🎵 Music:', musicEnabled ? 'ON' : 'OFF');
            });
        });

        // Sound buttons
        soundBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const isOn = btn.dataset.sound === 'on';
                soundEnabled = isOn;
                
                localStorage.setItem('cdp-sound', soundEnabled);
                applyAudioSettings();
                console.log('🔔 Sound Effects:', soundEnabled ? 'ON' : 'OFF');
            });
        });

        // Track selection
        if (musicTrackSelect) {
            musicTrackSelect.addEventListener('change', (e) => {
                const trackNumber = parseInt(e.target.value, 10);
                switchTrack(trackNumber);
                playSound('click');
            });
        }

        // Volume slider
        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => {
                updateVolume(e.target.value);
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export API
    window.cdpSettings = {
        getTheme: () => currentTheme,
        setTheme: (theme) => {
            if (theme === 'light' || theme === 'dark') {
                applyTheme(theme);
            }
        },
        getFontSize: () => currentFontSize,
        setFontSize: (size) => {
            if (['small', 'medium', 'large'].includes(size)) {
                applyFontSize(size);
            }
        },
        playSound: (soundName) => playSound(soundName),
        getMusicEnabled: () => musicEnabled,
        getSoundEnabled: () => soundEnabled,
        getVolume: () => currentVolume,
        getCurrentTrack: () => currentTrack
    };

})();
