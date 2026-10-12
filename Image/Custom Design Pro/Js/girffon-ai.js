document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const dialog = document.getElementById('cdpAiDialog');
    const continueButton = document.getElementById('cdpAiContinue');
    const selectionLabel = document.getElementById('cdpAiSelection');
    const photoTimePanel = document.getElementById('cdpAiPhotoTime');
    const authStep = document.getElementById('cdpAiAuthStep');
    const selectStep = document.getElementById('cdpAiSelectStep');
    const processingStep = document.getElementById('cdpAiProcessingStep');
    const resultStep = document.getElementById('cdpAiResultStep');
    const tryAnotherButton = document.getElementById('cdpAiTryAnother');
    const useResultButton = document.getElementById('cdpAiUseResult');
    const sourceThumb = document.getElementById('cdpAiSourceThumb');
    const sourceName = document.getElementById('cdpAiSourceName');
    const beforeImage = document.getElementById('cdpAiBeforeImage');
    const resultImage = document.getElementById('cdpAiResultImage');
    const resultFrame = document.getElementById('cdpAiResultFrame');
    const resultMode = document.getElementById('cdpAiResultMode');
    const resultCaption = document.getElementById('cdpAiResultCaption');
    const processingLabel = document.getElementById('cdpAiProcessingLabel');
    const creditsLabel = document.getElementById('cdpAiCredits');
    const availabilityLabel = document.getElementById('cdpAiAvailability');
    const purchaseButton = document.getElementById('cdpAiPurchase');
    const signInButton = document.getElementById('cdpAiSignIn');
    const createAccountButton = document.getElementById('cdpAiCreateAccount');

    if (!dialog || !continueButton || !selectionLabel || !photoTimePanel ||
        !authStep || !selectStep || !processingStep || !resultStep ||
        !tryAnotherButton || !useResultButton || !sourceThumb || !sourceName || !beforeImage ||
        !resultImage || !resultFrame || !resultMode || !resultCaption || !processingLabel ||
        !creditsLabel || !availabilityLabel || !purchaseButton || !signInButton || !createAccountButton) {
        return;
    }

    const closeButtons = dialog.querySelectorAll('[data-ai-close]');
    const optionButtons = Array.from(dialog.querySelectorAll('[data-ai-mode]'));
    const directionButtons = Array.from(dialog.querySelectorAll('[data-ai-direction]'));
    let selectedMode = '';
    let selectedDirection = '';
    let selectedUpload = null;
    let previewTimer = null;
    let previouslyFocused = null;
    let awaitingAuthentication = false;
    let creditState = {
        loaded: false,
        authenticated: false,
        credits: 0,
        settings: null
    };
    const creditEndpoint = '../../backend/ai/credits.php';

    const previewFilters = {
        embroidery: 'contrast(1.28) saturate(1.18) brightness(1.04)',
        knitted: 'contrast(0.92) saturate(0.78) sepia(0.16) brightness(1.08)',
        'toy-doll': 'contrast(1.08) saturate(1.38) brightness(1.08)',
        cartoon: 'contrast(1.42) saturate(1.55) brightness(1.04)',
        '3d-cartoon': 'contrast(1.18) saturate(1.32) brightness(1.12)',
        'old-to-modern': 'contrast(1.12) saturate(1.42) brightness(1.08)',
        'modern-to-vintage': 'grayscale(1) sepia(0.2) contrast(1.22) brightness(0.94)'
    };

    function getModeLabel(mode) {
        const option = optionButtons.find(function (button) {
            return button.dataset.aiMode === mode;
        });
        const label = option ? option.querySelector('strong') : null;
        return label ? label.textContent.trim() : '';
    }

    function getDirectionLabel(direction) {
        const labels = {
            'old-to-modern': 'Old to Modern / Color',
            'modern-to-vintage': 'Modern to Vintage B&W'
        };
        return labels[direction] || '';
    }

    function getPreviewKey() {
        return selectedMode === 'photo-time' ? selectedDirection : selectedMode;
    }

    function formatPrice(value) {
        return Number(value || 0).toLocaleString('en-IE', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function canGenerate() {
        const settings = creditState.settings;
        return Boolean(
            creditState.loaded &&
            creditState.authenticated &&
            settings &&
            settings.feature_enabled &&
            settings.generation_enabled &&
            creditState.credits > 0
        );
    }

    function setAvailability(message, kind) {
        availabilityLabel.textContent = message || '';
        availabilityLabel.hidden = !message;
        availabilityLabel.dataset.state = kind || '';
    }

    function renderCreditState(message, kind) {
        const settings = creditState.settings;
        creditsLabel.hidden = !creditState.authenticated;
        creditsLabel.textContent = '✨ AI Credits: ' + (creditState.authenticated ? creditState.credits : '--');
        optionButtons.forEach(function (button) {
            button.disabled = !creditState.loaded || !settings || !settings.feature_enabled;
        });

        purchaseButton.hidden = true;
        purchaseButton.disabled = true;
        purchaseButton.title = 'AI package purchases are currently unavailable';

        if (message) {
            setAvailability(message, kind);
        } else if (!creditState.loaded) {
            setAvailability('Loading account credits...', 'info');
        } else if (!creditState.authenticated) {
            setAvailability('', '');
        } else if (!settings || !settings.feature_enabled) {
            setAvailability('GIRFFON AI is currently turned off.', 'warning');
        } else if (!settings.generation_enabled) {
            setAvailability('AI generation is temporarily paused.', 'warning');
        } else if (creditState.credits === 0) {
            setAvailability('AI package purchases are currently unavailable. Please contact support.', 'warning');
        } else {
            setAvailability('', '');
        }
        updateSelection();
    }

    async function creditRequest(options) {
        const response = await fetch(creditEndpoint, options);
        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error(data.message || 'Unable to update AI credits.');
        }
        return data;
    }

    async function refreshCredits() {
        creditState.loaded = false;
        renderCreditState();
        try {
            const data = await creditRequest({ headers: { Accept: 'application/json' } });
            creditState = {
                loaded: true,
                authenticated: Boolean(data.authenticated),
                credits: Number(data.credits || 0),
                settings: data.settings || null
            };
            renderCreditState();
        } catch (error) {
            creditState.loaded = true;
            creditState.settings = null;
            renderCreditState(error.message, 'error');
        }
    }

    function createUploadTarget(element) {
        if (!(element instanceof HTMLElement) || !element.classList.contains('cdp-uploaded-image')) {
            return null;
        }
        const image = element.querySelector('img');
        const src = image && (image.currentSrc || image.src);
        if (!image || !src) {
            return null;
        }

        return {
            element: element,
            image: image,
            src: src,
            name: element.dataset.uploadName || 'Uploaded Photo'
        };
    }

    function showStep(stepName) {
        authStep.hidden = stepName !== 'auth';
        selectStep.hidden = stepName !== 'select';
        processingStep.hidden = stepName !== 'processing';
        resultStep.hidden = stepName !== 'result';
        continueButton.hidden = stepName !== 'select';
        tryAnotherButton.hidden = stepName !== 'result';
        useResultButton.hidden = stepName !== 'result';
        if (stepName !== 'select') {
            purchaseButton.hidden = true;
        }
    }

    function loadSelectedUpload(element) {
        selectedUpload = createUploadTarget(element);
        if (!selectedUpload) return false;
        sourceThumb.src = selectedUpload.src;
        sourceName.textContent = selectedUpload.name;
        showStep('select');
        return true;
    }

    function updateSelection() {
        const needsDirection = selectedMode === 'photo-time';
        const isReady = canGenerate() && Boolean(selectedMode) && (!needsDirection || Boolean(selectedDirection));

        continueButton.disabled = !isReady;

        if (!selectedMode) {
            selectionLabel.textContent = 'Select an AI style';
            return;
        }

        if (needsDirection && !selectedDirection) {
            selectionLabel.textContent = 'Choose a Photo Time direction';
            return;
        }

        const directionLabel = getDirectionLabel(selectedDirection);
        selectionLabel.textContent = directionLabel
            ? getModeLabel(selectedMode) + ': ' + directionLabel
            : getModeLabel(selectedMode) + ' selected';
    }

    function resetSelection() {
        selectedMode = '';
        selectedDirection = '';
        optionButtons.forEach(function (button) {
            button.setAttribute('aria-pressed', 'false');
        });
        directionButtons.forEach(function (button) {
            button.setAttribute('aria-pressed', 'false');
        });
        photoTimePanel.hidden = true;
        updateSelection();
    }

    async function openDialog(element) {
        if (!loadSelectedUpload(element)) return;
        previouslyFocused = document.activeElement;
        resetSelection();
        showStep('auth');
        creditsLabel.hidden = true;
        selectionLabel.textContent = 'Sign in to continue';
        dialog.setAttribute('data-visible', 'true');
        dialog.setAttribute('aria-hidden', 'false');
        document.body.classList.add('cdp-ai-opened');
        const closeButton = dialog.querySelector('.cdp-ai-close');
        if (closeButton) {
            closeButton.focus();
        }
        await refreshCredits();
        if (creditState.authenticated) {
            showStep('select');
            renderCreditState();
        } else {
            showStep('auth');
            selectionLabel.textContent = 'Account required';
        }
    }

    function closeDialog() {
        if (previewTimer) {
            window.clearTimeout(previewTimer);
            previewTimer = null;
        }
        dialog.setAttribute('data-visible', 'false');
        dialog.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('cdp-ai-opened');
        if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
            previouslyFocused.focus();
        }
    }

    function keepFocusInside(event) {
        if (event.key !== 'Tab' || dialog.getAttribute('data-visible') !== 'true') {
            return;
        }

        const focusable = Array.from(dialog.querySelectorAll('button:not([disabled]):not([hidden])'))
            .filter(function (element) {
                return element.offsetParent !== null && !element.classList.contains('cdp-ai-backdrop');
            });

        if (!focusable.length) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    document.addEventListener('girffon-ai:open', function (event) {
        openDialog(event.detail && event.detail.element);
    });

    function beginAuthentication(mode) {
        awaitingAuthentication = true;
        closeDialog();

        if (mode === 'signin') {
            const accountTrigger = document.querySelector('[data-tool="account"]');
            if (accountTrigger) accountTrigger.click();
            return;
        }

        const registerUrl = new URL('../../index.html', window.location.href);
        registerUrl.searchParams.set('account', 'register');
        window.open(registerUrl.href, 'girffon-register');
    }

    async function resumeAfterAuthentication() {
        if (!awaitingAuthentication || !selectedUpload || !document.body.contains(selectedUpload.element)) return;
        try {
            const data = await creditRequest({ headers: { Accept: 'application/json' } });
            if (!data.authenticated) return;
            awaitingAuthentication = false;
            const accountPanel = document.getElementById('cdpAccountPanel');
            if (accountPanel) {
                accountPanel.setAttribute('data-visible', 'false');
            }
            openDialog(selectedUpload.element);
        } catch (error) {
        }
    }

    signInButton.addEventListener('click', function () {
        beginAuthentication('signin');
    });

    createAccountButton.addEventListener('click', function () {
        beginAuthentication('register');
    });

    document.addEventListener('girffon:auth-updated', resumeAfterAuthentication);
    window.addEventListener('message', function (event) {
        if (event.origin === window.location.origin && event.data && event.data.type === 'girffon:auth-complete') {
            resumeAfterAuthentication();
        }
    });
    window.addEventListener('focus', resumeAfterAuthentication);

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeDialog);
    });

    purchaseButton.addEventListener('click', function () {
        renderCreditState('AI package purchases are currently unavailable. Please contact support.', 'warning');
    });

    optionButtons.forEach(function (button) {
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', function () {
            selectedMode = button.dataset.aiMode || '';
            selectedDirection = '';

            optionButtons.forEach(function (option) {
                option.setAttribute('aria-pressed', String(option === button));
            });
            directionButtons.forEach(function (direction) {
                direction.setAttribute('aria-pressed', 'false');
            });

            photoTimePanel.hidden = selectedMode !== 'photo-time';
            updateSelection();

            if (selectedMode === 'photo-time') {
                const firstDirection = directionButtons[0];
                if (firstDirection) {
                    firstDirection.focus();
                }
            }
        });
    });

    directionButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            selectedDirection = button.dataset.aiDirection || '';
            directionButtons.forEach(function (direction) {
                direction.setAttribute('aria-pressed', String(direction === button));
            });
            updateSelection();
        });
    });

    async function prepareResult() {
        if (!selectedUpload || !document.body.contains(selectedUpload.element)) {
            closeDialog();
            return;
        }

        const modeLabel = getModeLabel(selectedMode);
        const directionLabel = getDirectionLabel(selectedDirection);
        const previewLabel = directionLabel || modeLabel;
        const previewKey = getPreviewKey();

        showStep('processing');
        selectionLabel.textContent = 'Preparing ' + previewLabel + ' preview';
        processingLabel.textContent = 'Applying ' + previewLabel + '...';

        try {
            const data = await creditRequest({
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'consume',
                    mode: selectedMode
                })
            });
            creditState.credits = Number(data.credits || 0);
            renderCreditState();
        } catch (error) {
            showStep('select');
            renderCreditState(error.message, 'error');
            return;
        }

        previewTimer = window.setTimeout(function () {
            beforeImage.src = selectedUpload.src;
            resultImage.src = selectedUpload.src;
            resultImage.style.filter = previewFilters[previewKey] || 'none';
            resultFrame.dataset.aiPreview = previewKey;
            resultMode.textContent = previewLabel;
            resultCaption.textContent = previewLabel;
            selectionLabel.textContent = 'Preview ready';
            showStep('result');
            useResultButton.focus();
            previewTimer = null;
        }, 650);
    }

    continueButton.addEventListener('click', function () {
        if (continueButton.disabled) {
            return;
        }

        document.dispatchEvent(new CustomEvent('girffon-ai:select', {
            detail: {
                mode: selectedMode,
                direction: selectedDirection || null
            }
        }));
        prepareResult();
    });

    tryAnotherButton.addEventListener('click', function () {
        resetSelection();
        showStep('select');
        selectionLabel.textContent = 'Select an AI style';
        const firstOption = optionButtons[0];
        if (firstOption) {
            firstOption.focus();
        }
    });

    function createMockResult(source, filter) {
        return new Promise(function (resolve) {
            const image = new Image();
            image.onload = function () {
                const canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;
                const context = canvas.getContext('2d');
                if (!context) {
                    resolve(source);
                    return;
                }
                context.filter = filter || 'none';
                context.drawImage(image, 0, 0);
                try {
                    resolve(canvas.toDataURL('image/png'));
                } catch (error) {
                    resolve(source);
                }
            };
            image.onerror = function () {
                resolve(source);
            };
            image.src = source;
        });
    }

    useResultButton.addEventListener('click', async function () {
        if (!selectedUpload || !document.body.contains(selectedUpload.element)) {
            return;
        }

        useResultButton.disabled = true;
        useResultButton.textContent = 'Applying...';
        const previewKey = getPreviewKey();
        const resultSource = await createMockResult(selectedUpload.src, previewFilters[previewKey]);

        if (!selectedUpload.element.dataset.aiOriginalSrc) {
            selectedUpload.element.dataset.aiOriginalSrc = selectedUpload.src;
        }
        selectedUpload.image.src = resultSource;
        selectedUpload.element.dataset.optimizedSrc = resultSource;
        selectedUpload.element.dataset.originalBackup = resultSource;
        selectedUpload.element.dataset.aiMode = selectedMode;
        selectedUpload.element.dataset.aiDirection = selectedDirection;

        document.dispatchEvent(new CustomEvent('girffon-ai:applied', {
            detail: {
                mode: selectedMode,
                direction: selectedDirection || null,
                element: selectedUpload.element,
                simulated: true
            }
        }));

        useResultButton.disabled = false;
        useResultButton.textContent = 'Use Result';
        closeDialog();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && dialog.getAttribute('data-visible') === 'true') {
            closeDialog();
            return;
        }
        keepFocusInside(event);
    });
});
