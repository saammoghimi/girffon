(function() {
    const MINUTE = 60 * 1000;
    const GLOW_DURATION = 2000;
    const glowControllers = new WeakMap();

    function triggerGlow(button, duration = GLOW_DURATION) {
        if (!button) return;
        button.classList.remove('cdp-help-glow');
        void button.offsetWidth; // restart animation
        button.classList.add('cdp-help-glow');
        setTimeout(() => button.classList.remove('cdp-help-glow'), duration);
    }

    function scheduleSequence(button, delay, repeats = 1, gap = GLOW_DURATION + 500) {
        if (!button) return;
        setTimeout(() => {
            let count = 0;
            const pulse = () => {
                count += 1;
                triggerGlow(button);
                if (count < repeats) {
                    setTimeout(pulse, gap);
                }
            };
            pulse();
        }, delay);
    }

    function stopGlowLoop(button) {
        if (!button) return;
        const state = glowControllers.get(button);
        if (!state) return;
        if (state.timeoutId) clearTimeout(state.timeoutId);
        glowControllers.delete(button);
    }

    function startGlowLoop(button, options = {}) {
        if (!button) return;

        const {
            maxPulses = 10,
            interval = 2 * MINUTE
        } = options;

        stopGlowLoop(button);

        const state = { pulses: 0 };
        glowControllers.set(button, state);

        const runPulse = () => {
            if (!button.isConnected) {
                stopGlowLoop(button);
                return;
            }

            triggerGlow(button);
            state.pulses += 1;

            if (state.pulses >= maxPulses) {
                stopGlowLoop(button);
                return;
            }

            state.timeoutId = setTimeout(runPulse, interval);
        };

        runPulse();
    }

    function initMainHelpGlow() {
        const helpBtn = document.querySelector('[data-tool="help"]');
        if (!helpBtn) return;

        scheduleSequence(helpBtn, 0, 1);
        scheduleSequence(helpBtn, 3 * MINUTE, 1);
        scheduleSequence(helpBtn, 6 * MINUTE, 1);
        scheduleSequence(helpBtn, 12 * MINUTE, 4, GLOW_DURATION + 800);
    }

    function initUploadHelpGlow() {
        const tryAttach = () => {
            const button = document.querySelector('.cdp-upload-help');
            if (button) {
                startGlowLoop(button, { maxPulses: 10, interval: 2 * MINUTE });
                return true;
            }
            return false;
        };

        if (tryAttach()) {
            return;
        }

        const observer = new MutationObserver(() => {
            if (tryAttach()) {
                observer.disconnect();
            }
        });

        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    function initAddDesignHelpGlow() {
        const panel = document.getElementById('cdpAddDesignPanel');
        if (!panel) return;

        const getButtons = () => [
            document.getElementById('addDesignTutorial')
        ].filter(Boolean);

        const startButtons = () => {
            getButtons().forEach(btn => startGlowLoop(btn, { maxPulses: 10, interval: 2 * MINUTE }));
        };

        const stopButtons = () => {
            getButtons().forEach(stopGlowLoop);
        };

        if (panel.getAttribute('data-visible') === 'true') {
            startButtons();
        }

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'data-visible') {
                    const isVisible = panel.getAttribute('data-visible') === 'true';
                    if (isVisible) {
                        startButtons();
                    } else {
                        stopButtons();
                    }
                }
            });
        });

        observer.observe(panel, { attributes: true });
    }

    function initFillHelpGlow() {
        const attach = () => {
            const modal = document.getElementById('cdpFillModal') || document.querySelector('.cdp-fill-modal');
            if (!modal) return false;

            const button = modal.querySelector('#cdpFillTutorial');
            if (!button) return false;

            const startButton = () => startGlowLoop(button, { maxPulses: 10, interval: 2 * MINUTE });
            const stopButton = () => stopGlowLoop(button);

            if (modal.getAttribute('data-visible') === 'true') {
                startButton();
            }

            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'data-visible') {
                        const isVisible = modal.getAttribute('data-visible') === 'true';
                        if (isVisible) {
                            startButton();
                        } else {
                            stopButton();
                        }
                    }
                });
            });

            observer.observe(modal, { attributes: true });
            return true;
        };

        if (attach()) {
            return;
        }

        const observer = new MutationObserver(() => {
            if (attach()) {
                observer.disconnect();
            }
        });

        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    function initTextHelpGlow() {
        const attach = () => {
            const panel = document.querySelector('.cdp-text-panel');
            if (!panel) return false;

            const button = panel.querySelector('#cdpTextTutorial');
            if (!button) return false;

            const startButton = () => startGlowLoop(button, { maxPulses: 10, interval: 2 * MINUTE });
            const stopButton = () => stopGlowLoop(button);

            if (panel.getAttribute('data-visible') === 'true') {
                startButton();
            }

            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'data-visible') {
                        const isVisible = panel.getAttribute('data-visible') === 'true';
                        if (isVisible) {
                            startButton();
                        } else {
                            stopButton();
                        }
                    }
                });
            });

            observer.observe(panel, { attributes: true });
            return true;
        };

        if (attach()) {
            return;
        }

        const observer = new MutationObserver(() => {
            if (attach()) {
                observer.disconnect();
            }
        });

        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    function initShapeHelpGlow() {
        const panel = document.getElementById('cdpShapePanel');
        if (!panel) return;

        const getButtons = () => [
            document.getElementById('cdpShapeTutorial')
        ].filter(Boolean);

        const startButtons = () => {
            getButtons().forEach(btn => startGlowLoop(btn, { maxPulses: 10, interval: 2 * MINUTE }));
        };

        const stopButtons = () => {
            getButtons().forEach(stopGlowLoop);
        };

        if (panel.getAttribute('data-visible') === 'true') {
            startButtons();
        }

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.attributeName === 'data-visible') {
                    const isVisible = panel.getAttribute('data-visible') === 'true';
                    if (isVisible) {
                        startButtons();
                    } else {
                        stopButtons();
                    }
                }
            });
        });

        observer.observe(panel, { attributes: true });
    }

    function initFlagHelpGlow() {
        const panel = document.getElementById('cdpFlagPanel');
        if (!panel) return;

        const getButtons = () => [
            document.getElementById('cdpFlagTutorial')
        ].filter(Boolean);

        const startButtons = () => {
            getButtons().forEach(btn => startGlowLoop(btn, { maxPulses: 10, interval: 2 * MINUTE }));
        };

        const stopButtons = () => {
            getButtons().forEach(stopGlowLoop);
        };

        if (panel.getAttribute('data-visible') === 'true') {
            startButtons();
        }

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.attributeName === 'data-visible') {
                    const isVisible = panel.getAttribute('data-visible') === 'true';
                    if (isVisible) {
                        startButtons();
                    } else {
                        stopButtons();
                    }
                }
            });
        });

        observer.observe(panel, { attributes: true });
    }

    function initIconHelpGlow() {
        const panel = document.getElementById('cdpIconPanel');
        if (!panel) return;

        const getButtons = () => [
            document.getElementById('cdpIconTutorial')
        ].filter(Boolean);

        const startButtons = () => {
            getButtons().forEach(btn => startGlowLoop(btn, { maxPulses: 10, interval: 2 * MINUTE }));
        };

        const stopButtons = () => {
            getButtons().forEach(stopGlowLoop);
        };

        if (panel.getAttribute('data-visible') === 'true') {
            startButtons();
        }

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.attributeName === 'data-visible') {
                    const isVisible = panel.getAttribute('data-visible') === 'true';
                    if (isVisible) {
                        startButtons();
                    } else {
                        stopButtons();
                    }
                }
            });
        });

        observer.observe(panel, { attributes: true });
    }

    function runAfterDomReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            fn();
        }
    }

    runAfterDomReady(() => {
        initMainHelpGlow();
        initUploadHelpGlow();
        initAddDesignHelpGlow();
        initFillHelpGlow();
        initTextHelpGlow();
        initShapeHelpGlow();
        initFlagHelpGlow();
        initIconHelpGlow();
    });
})();
