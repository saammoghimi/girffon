// ===========================
// ICON SYSTEM
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    console.log("🎭 icon.js loaded");

    const iconBtn = document.querySelector('[data-tool="emoji"]');
    const FALLBACK_ICON_TUTORIAL_URL = 'https://www.youtube.com/watch?v=Bd1UkjF4qGQ&t=4s';
    let iconPanel = null;
    let resizePanel = null;
    let selectedIcon = null;
    let currentCategory = 'all';
    let currentResizingIcon = null;
    let currentResizingLayer = null;
    let originalResizingSize = null;
    const ICON_PREVIEW_MAX_SIZE = 220;

    // Global drag state
    let isDragging = false;
    let dragElement = null;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    let dragTransformExtras = '';
    let iconTutorialToastTimeout = null;
    
    // Export drag state for reattached events
    window.iconDragState = {
        isDragging: false,
        dragElement: null,
        startX: 0,
        startY: 0,
        startLeft: 0,
        startTop: 0,
        transformExtras: ''
    };

    if (!iconBtn) {
        console.error("❌ Icon button not found!");
        return;
    }

    if (!window.cdpState) {
        window.cdpState = { currentView: "front" };
    }

    if (!window.layersByView) {
        window.layersByView = {
            front: [],
            back: [],
            left: [],
            right: []
        };
    }

    if (typeof window.cdpIconTutorialUrl !== 'string' || !window.cdpIconTutorialUrl.trim()) {
        window.cdpIconTutorialUrl = FALLBACK_ICON_TUTORIAL_URL;
    }

    // ===========================
    // ICON DATA - Color Emoji Only
    // ===========================

    const EMOJI_DATA = {
        faces: ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐','😕','😟','🙁','☹️','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','😤','😡','😠','🤬','😈','👿','💀','☠️','💩','🤡','👹','👺','👻','👽','👾','🤖'],
        emoji: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉️','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','🆔','⚛️','🉑','☢️','☣️','📴','📳','🈶','🈚','🈸','🈺','🈷️','✴️','🆚','💮','🉐','㊙️','㊗️','🈴','🈵','🈹','🈲','🅰️','🅱️','🆎','🆑','🅾️','🆘','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨️','🚷','🚯','🚳','🚱','🔞','📵','🚭','❗','❕','❓','❔','‼️','⁉️','🔅','🔆','〽️','⚠️','🚸','🔱','⚜️','🔰','♻️','✅','🈯','💹','❇️','✳️','❎','🌐','💠','Ⓜ️','🌀','💤','🏧','🚾','♿','🅿️','🈳','🈂️','🛂','🛃','🛄','🛅','🚹','🚺','🚼','🚻','🚮','🎦','📶','🈁','🔣','ℹ️','🔤','🔡','🔠','🆖','🆗','🆙','🆒','🆕','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟','🔢','#️⃣','*️⃣','⏏️','▶️','⏸️','⏯️','⏹️','⏺️','⏭️','⏮️','⏩','⏪','⏫','⏬','◀️','🔼','🔽','➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️','↕️','↔️','↪️','↩️','⤴️','⤵️','🔀','🔁','🔂','🔄','🔃','🎵','🎶','➕','➖','➗','✖️','♾️','💲','💱','™️','©️','®️','👁️‍🗨️','🔚','🔙','🔛','🔝','🔜','〰️','➰','➿','✔️','☑️','🔘','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪','🟤','🔺','🔻','🔸','🔹','🔶','🔷','🔳','🔲','▪️','▫️','◾','◽','◼️','◻️','🟥','🟧','🟨','🟩','🟦','🟪','⬛','⬜','🟫','🔈','🔇','🔉','🔊','🔔','🔕','📣','📢','💬','💭','🗯️','♠️','♣️','♥️','♦️','🃏','🎴','🀄','🕐','🕑','🕒','🕓','🕔','🕕','🕖','🕗','🕘','🕙','🕚','🕛','🕜','🕝','🕞','🕟','🕠','🕡','🕢','🕣','🕤','🕥','🕦','🕧','⭐','🌟','✨','⚡','☄️','💥','🔥','🌈','☀️','🌤️','⛅','🌥️','☁️','🌦️','🌧️','⛈️','🌩️','🌨️','❄️','☃️','⛄','🌬️','💨','💧','💦','☔','🌊','🌫️'],
        arrows: ['⬆️','⬇️','⬅️','➡️','↗️','↘️','↙️','↖️','↕️','↔️','↪️','↩️','⤴️','⤵️','🔃','🔄','🔙','🔚','🔛','🔜','🔝','🔀','🔁','🔂','▶️','⏸️','⏹️','⏺️','⏏️','⏭️','⏮️','⏩','⏪','⏫','⏬','◀️','🔼','🔽','➕','➖','✖️','➗'],
        shapes: ['⭕','⛔','🚫','❌','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪','🟤','🟥','🟧','🟨','🟩','🟦','🟪','⬛','⬜','🟫','◼️','◻️','◾','◽','▪️','▫️','🔶','🔷','🔸','🔹','🔺','🔻','🔲','🔳','🔘','💠','💎'],
        sports: ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸','🏒','🏑','🥍','🏏','🥅','⛳','🪁','🏹','🎣','🤿','🥊','🥋','🎽','🛹','🛼','🛷','⛸️','🥌','🎿','⛷️','🏂','🪂','🏋️','🤼','🤸','⛹️','🤺','🤾','🏌️','🏇','🧘','🏄','🏊','🤽','🚣','🧗','🚵','🚴','🏆','🥇','🥈','🥉','🏅','🎖️','🎗️','🎫','🎟️','🎪','🎭','🎨','🎬','🎤','🎧','🎼','🎹','🥁','🎷','🎺','🎸','🪕','🎻','🎲','♟️','🎯','🎮','🎰','🧩'],
        animals: ['🐵','🐒','🦍','🦧','🐶','🐕','🦮','🐕‍🦺','🐩','🐺','🦊','🦝','🐱','🐈','🐈‍⬛','🦁','🐯','🐅','🐆','🐴','🐎','🦄','🦓','🦌','🦬','🐮','🐂','🐃','🐄','🐷','🐖','🐗','🐽','🐏','🐑','🐐','🐪','🐫','🦙','🦒','🐘','🦣','🦏','🦛','🐭','🐁','🐀','🐹','🐰','🐇','🐿️','🦫','🦔','🦇','🐻','🐻‍❄️','🐨','🐼','🦥','🦦','🦨','🦘','🦡','🐾','🦃','🐔','🐓','🐣','🐤','🐥','🐦','🐧','🕊️','🦅','🦆','🦢','🦉','🦤','🪶','🦩','🦚','🦜','🐸','🐊','🐢','🦎','🐍','🐲','🐉','🦕','🦖','🐳','🐋','🐬','🦭','🐟','🐠','🐡','🦈','🐙','🐚','🐌','🦋','🐛','🐜','🐝','🪲','🐞','🦗','🪳','🕷️','🕸️','🦂','🦟','🪰','🪱','🦠'],
        food: ['🍇','🍈','🍉','🍊','🍋','🍌','🍍','🥭','🍎','🍏','🍐','🍑','🍒','🍓','🫐','🥝','🍅','🫒','🥥','🥑','🍆','🥔','🥕','🌽','🌶️','🫑','🥒','🥬','🥦','🧄','🧅','🍄','🥜','🌰','🍞','🥐','🥖','🫓','🥨','🥯','🥞','🧇','🧀','🍖','🍗','🥩','🥓','🍔','🍟','🍕','🌭','🥪','🌮','🌯','🫔','🥙','🧆','🥚','🍳','🥘','🍲','🫕','🥣','🥗','🍿','🧈','🧂','🥫','🍱','🍘','🍙','🍚','🍛','🍜','🍝','🍠','🍢','🍣','🍤','🍥','🥮','🍡','🥟','🥠','🥡','🦀','🦞','🦐','🦑','🦪','🍦','🍧','🍨','🍩','🍪','🎂','🍰','🧁','🥧','🍫','🍬','🍭','🍮','🍯','🍼','🥛','☕','🫖','🍵','🍶','🍾','🍷','🍸','🍹','🍺','🍻','🥂','🥃','🥤','🧋','🧃','🧉','🧊'],
        travel: ['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🦯','🦽','🦼','🛴','🚲','🛵','🏍️','🛺','🚨','🚔','🚍','🚘','🚖','🚡','🚠','🚟','🚃','🚋','🚞','🚝','🚄','🚅','🚈','🚂','🚆','🚇','🚊','🚉','✈️','🛫','🛬','🛩️','💺','🛰️','🚀','🛸','🚁','🛶','⛵','🚤','🛥️','🛳️','⛴️','🚢','⚓','⛽','🚧','🚦','🚥','🚏','🗺️','🗿','🗽','🗼','🏰','🏯','🏟️','🎡','🎢','🎠','⛲','⛱️','🏖️','🏝️','🏜️','🌋','⛰️','🏔️','🗻','🏕️','⛺','🛖','🏠','🏡','🏘️','🏚️','🏗️','🏭','🏢','🏬','🏣','🏤','🏥','🏦','🏨','🏪','🏫','🏩','💒','🏛️','⛪','🕌','🕍','🛕','🕋','⛩️','🛤️','🛣️','🗾','🎑','🏞️','🌅','🌄','🌠','🎇','🎆','🌇','🌆','🏙️','🌃','🌌','🌉','🌁']
    };

    EMOJI_DATA.all = [
        ...EMOJI_DATA.faces,
        ...EMOJI_DATA.emoji,
        ...EMOJI_DATA.arrows,
        ...EMOJI_DATA.shapes,
        ...EMOJI_DATA.sports,
        ...EMOJI_DATA.animals,
        ...EMOJI_DATA.food,
        ...EMOJI_DATA.travel
    ];

    function handleIconTutorialClick() {
        if (typeof window.cdpIconTutorialHandler === 'function') {
            try {
                window.cdpIconTutorialHandler();
                return;
            } catch (err) {
                console.error('Icon tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpIconTutorialUrl || FALLBACK_ICON_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showIconTutorialToast('🎬 Icon tutorial coming soon');
    }

    function showIconTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpIconTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpIconTutorialToast';
            toast.className = 'cdp-icon-tutorial-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (iconTutorialToastTimeout) {
            clearTimeout(iconTutorialToastTimeout);
        }

        iconTutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2800);
    }

    // Search keywords (English only)
    const SEARCH_KEYWORDS = {
        'smile': ['😀','😃','😄','😁','😊','🙂','😉'],
        'happy': ['😀','😃','😄','😁','😆','😅','😊','🥳'],
        'love': ['❤️','💕','💖','💗','💘','💝','😍','🥰','😘'],
        'heart': ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','💕','💞','💓','💗','💖','💘','💝'],
        'sad': ['😢','😭','😔','😞','☹️','🙁'],
        'angry': ['😠','😡','🤬','😤'],
        'fire': ['🔥'],
        'star': ['⭐','🌟','✨'],
        'arrow': ['➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️'],
        'check': ['✅','✔️','☑️'],
        'cross': ['❌','❎'],
        'circle': ['⭕','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪','🟤'],
        'square': ['🟥','🟧','🟨','🟩','🟦','🟪','⬛','⬜','🟫'],
        'food': EMOJI_DATA.food,
        'animal': EMOJI_DATA.animals,
        'sport': EMOJI_DATA.sports,
        'travel': EMOJI_DATA.travel
    };

    function stripTranslate(transformStr) {
        if (!transformStr || transformStr === 'none') return '';
        return transformStr.replace(/translate\([^)]+\)\s*/g, '').trim();
    }

    function getIconTransformParts(transformStr) {
        const raw = (transformStr || '').trim();
        const rotateMatch = raw.match(/rotate\((-?[\d.]+)deg\)/);
        const scalePairMatch = raw.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
        const scaleXMatch = raw.match(/scaleX\((-?[\d.]+)\)/);
        const scaleYMatch = raw.match(/scaleY\((-?[\d.]+)\)/);
        const translateMatches = raw.match(/translate\([^)]+\)/g);

        const rotation = rotateMatch ? parseFloat(rotateMatch[1]) : 0;
        let scaleX = 1;
        let scaleY = 1;

        if (scalePairMatch) {
            scaleX = parseFloat(scalePairMatch[1]);
            scaleY = parseFloat(scalePairMatch[2]);
        } else {
            scaleX = scaleXMatch ? parseFloat(scaleXMatch[1]) : 1;
            scaleY = scaleYMatch ? parseFloat(scaleYMatch[1]) : 1;
        }

        return {
            rotation,
            scaleX,
            scaleY,
            translate: translateMatches ? translateMatches.join(' ') + ' ' : ''
        };
    }

    function renderIconPreview(previewEl, size) {
        if (!previewEl || !currentResizingIcon) return null;

        const previewSize = Math.min(size, ICON_PREVIEW_MAX_SIZE);
        const previewItem = document.createElement('div');
        previewItem.className = 'cdp-icon-preview-item';
        previewItem.innerHTML = currentResizingIcon.innerHTML;
        previewItem.style.fontSize = previewSize + 'px';
        previewItem.style.lineHeight = '1';
        previewItem.style.display = 'flex';
        previewItem.style.alignItems = 'center';
        previewItem.style.justifyContent = 'center';
        previewItem.style.transformOrigin = 'center';

        previewEl.innerHTML = '';
        previewEl.appendChild(previewItem);

        return previewItem;
    }

    function applyIconPreviewTransform(previewItem, rotation, scaleX, scaleY) {
        if (!previewItem) return;
        previewItem.style.transformOrigin = 'center';
        previewItem.style.transform = `rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`;
    }

    function getEventPoint(event) {
        const source = event.touches?.[0] || event.changedTouches?.[0] || event;
        return {
            clientX: source.clientX,
            clientY: source.clientY
        };
    }

    function attachDoubleTapHandler(element, onActivate, isBlocked = () => false) {
        let lastTapTime = 0;
        let lastTapX = 0;
        let lastTapY = 0;

        element.addEventListener('touchend', function(e) {
            if (isBlocked()) {
                lastTapTime = 0;
                return;
            }

            const touch = e.changedTouches?.[0];
            if (!touch) return;

            const now = Date.now();
            const withinTime = now - lastTapTime <= 320;
            const withinDistance = Math.abs(touch.clientX - lastTapX) <= 24 && Math.abs(touch.clientY - lastTapY) <= 24;

            lastTapTime = now;
            lastTapX = touch.clientX;
            lastTapY = touch.clientY;

            if (!withinTime || !withinDistance) return;

            lastTapTime = 0;
            e.preventDefault();
            e.stopPropagation();
            onActivate(e);
        }, { passive: false });
    }

    function beginIconDrag(state, element, event, useGlobalState = false) {
        const point = getEventPoint(event);
        state.isDragging = true;
        state.dragElement = element;
        state.startX = point.clientX;
        state.startY = point.clientY;

        const rect = element.getBoundingClientRect();
        const parent = element.parentElement.getBoundingClientRect();
        state.startLeft = rect.left - parent.left;
        state.startTop = rect.top - parent.top;
        const inlineTransform = element.style.transform || '';
        state.transformExtras = inlineTransform ? inlineTransform.replace(/translate\([^)]+\)\s*/g, '').trim() : '';

        if (!useGlobalState) {
            isDragging = state.isDragging;
            dragElement = state.dragElement;
            startX = state.startX;
            startY = state.startY;
            startLeft = state.startLeft;
            startTop = state.startTop;
            dragTransformExtras = state.transformExtras;
        }
    }

    function moveIconDragState(state, event) {
        if (!state.isDragging || !state.dragElement) return;
        if ((event.type === 'touchmove' || event.type === 'pointermove') && event.cancelable) {
            event.preventDefault();
        }

        const point = getEventPoint(event);
        const deltaX = point.clientX - state.startX;
        const deltaY = point.clientY - state.startY;
        
        state.dragElement.style.left = (state.startLeft + deltaX) + 'px';
        state.dragElement.style.top = (state.startTop + deltaY) + 'px';
        const extras = state.transformExtras || '';
        state.dragElement.style.transform = extras || 'none';
    }

    function stopIconDragState(state) {
        if (!state.isDragging) return;
        state.isDragging = false;
        if (state.dragElement) {
            state.dragElement.style.cursor = 'grab';
            state.dragElement = null;
        }
        state.transformExtras = '';
    }

    // ===========================
    // Global Mouse Events
    // ===========================

    function handleAnyIconDragMove(e) {
        if ((window.iconDragState.isDragging || (isDragging && dragElement)) && (e.type === 'touchmove' || e.type === 'pointermove') && e.cancelable) {
            e.preventDefault();
        }

        moveIconDragState({
            isDragging,
            dragElement,
            startX,
            startY,
            startLeft,
            startTop,
            transformExtras: dragTransformExtras
        }, e);
        moveIconDragState(window.iconDragState, e);
        if (isDragging && dragElement) {
            const point = getEventPoint(e);
            const deltaX = point.clientX - startX;
            const deltaY = point.clientY - startY;
            dragElement.style.left = (startLeft + deltaX) + 'px';
            dragElement.style.top = (startTop + deltaY) + 'px';
            const extras = dragTransformExtras || '';
            dragElement.style.transform = extras || 'none';
        }
    }

    function handleAnyIconDragEnd() {
        if (isDragging) {
            isDragging = false;
            if (dragElement) {
                dragElement.style.cursor = 'grab';
                dragElement = null;
            }
            dragTransformExtras = '';
        }
        stopIconDragState(window.iconDragState);
    }

    document.addEventListener('mousemove', handleAnyIconDragMove);
    document.addEventListener('pointermove', handleAnyIconDragMove, { passive: false });
    document.addEventListener('touchmove', handleAnyIconDragMove, { passive: false });

    document.addEventListener('mouseup', handleAnyIconDragEnd);
    document.addEventListener('pointerup', handleAnyIconDragEnd);
    document.addEventListener('pointercancel', handleAnyIconDragEnd);
    document.addEventListener('touchend', handleAnyIconDragEnd);
    document.addEventListener('touchcancel', handleAnyIconDragEnd);

    // ===========================
    // Attach Events to Icon Element
    // ===========================

    function attachIconEvents(iconEl, layerData) {
        console.log("📎 Attaching icon events:", iconEl.id, "locked =", layerData.locked);
        
        const handleIconDragStart = function(e) {
            console.log("🖱️ Icon mousedown:", iconEl.id, "locked =", layerData.locked);
            if (layerData.locked) {
                console.log("🔒 Icon is locked!");
                return;
            }
            if (e.type === 'mousedown' && e.button !== 0) return;
            
            beginIconDrag({
                isDragging,
                dragElement,
                startX,
                startY,
                startLeft,
                startTop,
                transformExtras: dragTransformExtras
            }, iconEl, e);

            const point = getEventPoint(e);
            startX = point.clientX;
            startY = point.clientY;
            const inlineTransform = iconEl.style.transform || '';
            dragTransformExtras = stripTranslate(inlineTransform);
            const rect = iconEl.getBoundingClientRect();
            const parent = iconEl.parentElement.getBoundingClientRect();
            startLeft = rect.left - parent.left;
            startTop = rect.top - parent.top;

            iconEl.style.cursor = 'grabbing';
            e.preventDefault();
            e.stopPropagation();
        };

        iconEl.style.touchAction = 'none';
        iconEl.addEventListener('mousedown', handleIconDragStart, false);
        iconEl.addEventListener('pointerdown', handleIconDragStart, false);
        iconEl.addEventListener('touchstart', handleIconDragStart, { passive: false });

        iconEl.addEventListener('dblclick', function(e) {
            if (layerData.locked) return;
            
            currentResizingIcon = iconEl;
            currentResizingLayer = layerData;
            showResizePanel();
            
            e.preventDefault();
            e.stopPropagation();
        }, false);

        attachDoubleTapHandler(iconEl, function(e) {
            if (layerData.locked) return;
            currentResizingIcon = iconEl;
            currentResizingLayer = layerData;
            showResizePanel();
        }, () => isDragging || layerData.locked);
    }

    // ===========================
    // Resize Panel
    // ===========================

    function createResizePanel() {
        if (resizePanel) return;

        resizePanel = document.createElement('div');
        resizePanel.className = 'cdp-icon-resize-panel';
        resizePanel.innerHTML = `
            <div class="cdp-icon-resize-content">
                <div class="cdp-icon-resize-header">
                    <h3>Resize Icon</h3>
                    <button type="button" class="cdp-icon-resize-close">&times;</button>
                </div>
                <div class="cdp-icon-resize-body">
                    <div class="cdp-icon-resize-group">
                        <label>Size: <span id="cdpIconSizeValue">64</span>px</label>
                        <input type="range" id="cdpIconSizeSlider" min="16" max="256" value="64" step="1">
                        <div class="cdp-icon-size-precision">
                            <button type="button" class="cdp-size-nudge" data-nudge="-1" title="-1px">−</button>
                            <div class="cdp-icon-size-field">
                                <input type="number" id="cdpIconSizeInput" min="16" max="256" step="1" value="64" aria-label="Icon size in pixels">
                                <span>px</span>
                            </div>
                            <button type="button" class="cdp-size-nudge" data-nudge="1" title="+1px">+</button>
                        </div>
                    </div>
                    <div class="cdp-icon-resize-transform">
                        <label>Transform:</label>
                        <div class="cdp-transform-buttons">
                            <button type="button" class="cdp-transform-btn" data-action="rotate-right" title="Rotate Right">↻</button>
                            <button type="button" class="cdp-transform-btn" data-action="rotate-left" title="Rotate Left">↺</button>
                            <button type="button" class="cdp-transform-btn" data-action="rotate-90" title="Rotate 90°">90°</button>
                            <button type="button" class="cdp-transform-btn" data-action="flip-vertical" title="Flip Vertical">⇅</button>
                            <button type="button" class="cdp-transform-btn" data-action="flip-horizontal" title="Flip Horizontal">⇄</button>
                        </div>
                    </div>
                    <div class="cdp-icon-resize-preview">
                        <div id="cdpIconResizePreview"></div>
                    </div>
                </div>
                <div class="cdp-icon-resize-footer">
                    <button type="button" class="cdp-icon-btn cdp-icon-btn--cancel">Cancel</button>
                    <button type="button" class="cdp-icon-btn cdp-icon-btn--apply">Apply</button>
                </div>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            :root {
                --cdp-icon-resize-surface: #ffffff;
                --cdp-icon-resize-surface-alt: #f8fafc;
                --cdp-icon-resize-border: rgba(15, 23, 42, 0.08);
                --cdp-icon-resize-shadow: 0 30px 60px rgba(15, 23, 42, 0.18);
                --cdp-icon-resize-text: #0f172a;
                --cdp-icon-resize-muted: #475569;
                --cdp-icon-resize-button-bg: #ffffff;
                --cdp-icon-resize-button-border: rgba(15, 23, 42, 0.08);
                --cdp-icon-resize-button-hover-bg: #f8fafc;
                --cdp-icon-resize-transform-bg: #ffffff;
                --cdp-icon-resize-transform-border: rgba(15, 23, 42, 0.12);
                --cdp-icon-resize-preview-bg: #ffffff;
                --cdp-icon-resize-footer-bg: #ffffff;
                --cdp-icon-resize-track: #e2e8f0;
                --cdp-icon-resize-thumb: #fbbf24;
                --cdp-icon-resize-thumb-shadow: rgba(251, 191, 36, 0.25);
                --cdp-icon-resize-close: #94a3b8;
                --cdp-icon-resize-close-hover: #f59e0b;
            }
            body.dark-mode {
                --cdp-icon-resize-surface: #050914;
                --cdp-icon-resize-surface-alt: #050914;
                --cdp-icon-resize-border: #10182b;
                --cdp-icon-resize-shadow: 0 30px 60px rgba(0, 0, 0, 0.55);
                --cdp-icon-resize-text: #f3f4f6;
                --cdp-icon-resize-muted: #cbd5f5;
                --cdp-icon-resize-button-bg: #0b1220;
                --cdp-icon-resize-button-border: #1a2740;
                --cdp-icon-resize-button-hover-bg: #16233d;
                --cdp-icon-resize-transform-bg: #0b1220;
                --cdp-icon-resize-transform-border: #1a2740;
                --cdp-icon-resize-preview-bg: #ffffff;
                --cdp-icon-resize-footer-bg: #050914;
                --cdp-icon-resize-track: #172033;
                --cdp-icon-resize-thumb: #fbbf24;
                --cdp-icon-resize-thumb-shadow: rgba(251, 191, 36, 0.45);
                --cdp-icon-resize-close: #cbd5f5;
                --cdp-icon-resize-close-hover: #fbbf24;
            }
            .cdp-icon-resize-panel {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: transparent;
                display: none;
                align-items: center;
                justify-content: flex-start;
                padding-left: clamp(24px, 8vw, 80px);
                z-index: 100000;
                pointer-events: none;
            }
            .cdp-icon-resize-panel[data-visible="true"] {
                display: flex;
            }
            .cdp-icon-resize-content {
                background: var(--cdp-icon-resize-surface);
                border-radius: 18px;
                width: min(360px, 92vw);
                box-shadow: var(--cdp-icon-resize-shadow);
                border: 1px solid var(--cdp-icon-resize-border);
                pointer-events: auto;
                color: var(--cdp-icon-resize-text);
            }
            .cdp-icon-resize-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px 20px;
                border-bottom: 1px solid var(--cdp-icon-resize-border);
                background: var(--cdp-icon-resize-surface-alt);
            }
            .cdp-icon-resize-header h3 {
                margin: 0;
                color: var(--cdp-icon-resize-text);
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.04em;
            }
            .cdp-icon-resize-close {
                background: none;
                border: none;
                color: var(--cdp-icon-resize-close);
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s ease;
            }
            .cdp-icon-resize-close:hover {
                color: var(--cdp-icon-resize-close-hover);
            }
            .cdp-icon-resize-body {
                padding: 18px 20px 22px;
                background: var(--cdp-icon-resize-surface);
            }
            .cdp-icon-resize-group {
                margin-bottom: 18px;
            }
            .cdp-icon-resize-group label {
                display: block;
                color: var(--cdp-icon-resize-muted);
                margin-bottom: 10px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            #cdpIconSizeValue {
                color: var(--cdp-icon-resize-text);
            }
            #cdpIconSizeSlider {
                width: 100%;
                height: 6px;
                border-radius: 999px;
                background: var(--cdp-icon-resize-track);
                outline: none;
                -webkit-appearance: none;
            }
            #cdpIconSizeSlider::-webkit-slider-runnable-track {
                height: 6px;
                border-radius: 999px;
                background: var(--cdp-icon-resize-track);
            }
            #cdpIconSizeSlider::-moz-range-track {
                height: 6px;
                border-radius: 999px;
                background: var(--cdp-icon-resize-track);
            }
            #cdpIconSizeSlider::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--cdp-icon-resize-thumb);
                cursor: pointer;
                box-shadow: 0 6px 18px var(--cdp-icon-resize-thumb-shadow);
                margin-top: -6px;
            }
            #cdpIconSizeSlider::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--cdp-icon-resize-thumb);
                cursor: pointer;
                border: none;
                box-shadow: 0 6px 18px var(--cdp-icon-resize-thumb-shadow);
            }
            .cdp-icon-size-precision {
                margin-top: 12px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .cdp-icon-size-field {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 0 14px;
                border-radius: 999px;
                border: 1px solid var(--cdp-icon-resize-button-border);
                background: var(--cdp-icon-resize-button-bg);
            }
            .cdp-icon-size-field input {
                border: none;
                background: transparent;
                width: 80px;
                text-align: center;
                font-size: 14px;
                font-weight: 600;
                color: var(--cdp-icon-resize-text);
                padding: 8px 0;
                outline: none;
            }
            .cdp-icon-size-field span {
                font-size: 12px;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--cdp-icon-resize-muted);
            }
            .cdp-size-nudge {
                width: 42px;
                height: 42px;
                border-radius: 50%;
                border: 1px solid var(--cdp-icon-resize-button-border);
                background: var(--cdp-icon-resize-button-bg);
                color: var(--cdp-icon-resize-text);
                font-size: 18px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
            }
            .cdp-size-nudge:hover {
                border-color: var(--cdp-icon-resize-thumb);
                color: var(--cdp-icon-resize-thumb);
            }
            .cdp-icon-resize-transform {
                margin-bottom: 20px;
            }
            .cdp-icon-resize-transform label {
                display: block;
                color: var(--cdp-icon-resize-muted);
                margin-bottom: 10px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            .cdp-transform-buttons {
                display: flex;
                gap: 8px;
                width: 100%;
                justify-content: space-between;
            }
            .cdp-transform-btn {
                flex: 1;
                padding: 8px 14px;
                min-height: 44px;
                background: var(--cdp-icon-resize-transform-bg);
                border: 1px solid var(--cdp-icon-resize-transform-border);
                border-radius: 9px;
                cursor: pointer;
                font-size: 16px;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--cdp-icon-resize-text);
                box-shadow: 0 8px 20px rgba(2, 6, 23, 0.08);
            }
            body.dark-mode .cdp-transform-btn {
                box-shadow: 0 8px 20px rgba(2, 6, 23, 0.65);
            }
            .cdp-transform-btn:hover {
                background: var(--cdp-icon-resize-button-hover-bg);
                border-color: var(--cdp-icon-resize-thumb);
                color: var(--cdp-icon-resize-thumb);
            }
            .cdp-transform-btn:active {
                background: var(--cdp-icon-resize-thumb);
                color: #0b1220;
                border-color: var(--cdp-icon-resize-thumb);
            }
            .cdp-icon-resize-preview {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 150px;
                height: 280px;
                width: 100%;
                max-width: 320px;
                background: var(--cdp-icon-resize-preview-bg);
                border-radius: 16px;
                padding: 20px;
                border: 2px solid var(--cdp-icon-resize-border);
                box-shadow: 0 15px 35px rgba(5, 9, 20, 0.15);
                overflow: hidden;
                margin: 0 auto;
            }
            .cdp-icon-preview-item {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }
            .cdp-icon-resize-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                padding: 16px 20px 18px;
                border-top: 1px solid var(--cdp-icon-resize-border);
                background: var(--cdp-icon-resize-footer-bg);
            }
            .cdp-icon-resize-footer .cdp-icon-btn {
                min-width: 120px;
                padding: 10px 18px;
                border-radius: 999px;
                border: 1px solid var(--cdp-icon-resize-button-border);
                background: var(--cdp-icon-resize-button-bg);
                color: var(--cdp-icon-resize-text);
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                transition: all 0.2s ease;
            }
            .cdp-icon-resize-footer .cdp-icon-btn--cancel:hover {
                border-color: var(--cdp-icon-resize-thumb);
                color: var(--cdp-icon-resize-thumb);
            }
            .cdp-icon-resize-footer .cdp-icon-btn--apply {
                background: linear-gradient(130deg, #f8d49d, #f1a452);
                color: #0b1220;
                border-color: transparent;
                box-shadow: 0 15px 35px rgba(241, 164, 82, 0.35);
            }
            body.dark-mode .cdp-icon-resize-footer .cdp-icon-btn--apply {
                color: #1b1f2e;
            }
            .cdp-icon-resize-footer .cdp-icon-btn--apply:hover {
                filter: brightness(1.05);
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(resizePanel);

        const closeBtn = resizePanel.querySelector('.cdp-icon-resize-close');
        const cancelBtn = resizePanel.querySelector('.cdp-icon-btn--cancel');
        const applyBtn = resizePanel.querySelector('.cdp-icon-btn--apply');
        const sizeSlider = document.getElementById('cdpIconSizeSlider');
        const sizeValue = document.getElementById('cdpIconSizeValue');
        const preview = document.getElementById('cdpIconResizePreview');
        const sizeInput = document.getElementById('cdpIconSizeInput');
        const sizeNudgeBtns = resizePanel.querySelectorAll('.cdp-size-nudge');
        const MIN_ICON_SIZE = parseInt(sizeSlider.min, 10) || 16;
        const MAX_ICON_SIZE = parseInt(sizeSlider.max, 10) || 256;

        const clampIconSize = (value) => {
            if (Number.isNaN(value)) return MIN_ICON_SIZE;
            return Math.min(MAX_ICON_SIZE, Math.max(MIN_ICON_SIZE, value));
        };

        const refreshIconPreview = (size) => {
            if (!currentResizingIcon || !preview) return;
            const previewItem = renderIconPreview(preview, size);
            const { rotation, scaleX, scaleY } = getIconTransformParts(currentResizingIcon.style.transform || '');
            applyIconPreviewTransform(previewItem, rotation, scaleX, scaleY);
        };

        const applySizeChange = (nextSize, options = {}) => {
            const clamped = clampIconSize(Math.round(nextSize));
            sizeSlider.value = clamped;
            sizeInput.value = clamped;
            sizeValue.textContent = clamped;

            if (currentResizingIcon && options.skipIconUpdate !== true) {
                currentResizingIcon.style.fontSize = clamped + 'px';
                if (currentResizingLayer) {
                    currentResizingLayer.size = clamped;
                }
            }

            refreshIconPreview(clamped);
            return clamped;
        };

        const handleManualSize = () => {
            const typed = parseInt(sizeInput.value, 10);
            if (Number.isNaN(typed)) {
                sizeInput.value = sizeSlider.value;
                return;
            }
            applySizeChange(typed);
        };

        closeBtn.addEventListener('click', () => closeResizePanel());
        cancelBtn.addEventListener('click', () => closeResizePanel());

        // Transform buttons
        const transformBtns = resizePanel.querySelectorAll('.cdp-transform-btn');
        transformBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!currentResizingIcon) return;
                
                const action = this.getAttribute('data-action');
                const rawTransform = (currentResizingIcon.style.transform || '').trim();
                const currentTransform = rawTransform === 'none' ? '' : rawTransform;
                const { rotation: currentRotation, scaleX: currentScaleX, scaleY: currentScaleY, translate } = getIconTransformParts(currentTransform);
                let rotation = currentRotation;
                let scaleX = currentScaleX;
                let scaleY = currentScaleY;
                
                if (action === 'rotate-right') {
                    rotation += 1;
                } else if (action === 'rotate-left') {
                    rotation -= 1;
                } else if (action === 'rotate-90') {
                    rotation += 90;
                } else if (action === 'flip-vertical') {
                    scaleY *= -1;
                } else if (action === 'flip-horizontal') {
                    scaleX *= -1;
                }
                
                const newTransform = `${translate}rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`.trim();
                currentResizingIcon.style.transformOrigin = 'center';
                currentResizingIcon.style.transform = newTransform || 'none';
                const previewItem = preview.querySelector('.cdp-icon-preview-item');
                applyIconPreviewTransform(previewItem, rotation, scaleX, scaleY);
            });
        });

        sizeSlider.addEventListener('input', () => {
            applySizeChange(parseInt(sizeSlider.value, 10));
        });

        sizeInput.addEventListener('change', handleManualSize);
        sizeInput.addEventListener('blur', handleManualSize);
        sizeInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleManualSize();
            }
        });

        sizeNudgeBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const delta = parseInt(btn.dataset.nudge, 10) || 0;
                const currentValue = parseInt(sizeSlider.value, 10) || MIN_ICON_SIZE;
                applySizeChange(currentValue + delta);
            });
        });

        window.cdpIconSizeHelpers = {
            applySizeChange,
            refreshIconPreview,
            handleManualSize
        };

        applyBtn.addEventListener('click', function() {
            if (currentResizingIcon) {
                const size = parseInt(sizeSlider.value, 10);
                currentResizingIcon.style.fontSize = size + 'px';

                if (currentResizingLayer) {
                    currentResizingLayer.size = size;
                }
            }
            closeResizePanel({ commit: true });
        });
    }

    function showResizePanel() {
        if (!resizePanel) createResizePanel();
        
        if (!currentResizingIcon) return;

        const sizeSlider = document.getElementById('cdpIconSizeSlider');
        const sizeValue = document.getElementById('cdpIconSizeValue');
        const preview = document.getElementById('cdpIconResizePreview');

        let currentSize = 64;
        if (currentResizingLayer && currentResizingLayer.size) {
            currentSize = currentResizingLayer.size;
        } else {
            const computedSize = window.getComputedStyle(currentResizingIcon).fontSize;
            currentSize = parseInt(computedSize);
        }

        originalResizingSize = currentSize;
        if (window.cdpIconSizeHelpers && typeof window.cdpIconSizeHelpers.applySizeChange === 'function') {
            window.cdpIconSizeHelpers.applySizeChange(currentSize, { skipIconUpdate: true });
        } else {
            if (sizeSlider) sizeSlider.value = currentSize;
            if (sizeValue) sizeValue.textContent = currentSize;
            const previewItem = renderIconPreview(preview, currentSize);
            const { rotation, scaleX, scaleY } = getIconTransformParts(currentResizingIcon.style.transform || '');
            applyIconPreviewTransform(previewItem, rotation, scaleX, scaleY);
        }

        resizePanel.setAttribute('data-visible', 'true');
    }

    function closeResizePanel(options = {}) {
        if (!resizePanel) return;

        const commit = options.commit === true;

        if (!commit && currentResizingIcon && originalResizingSize !== null) {
            currentResizingIcon.style.fontSize = originalResizingSize + 'px';

            if (currentResizingLayer) {
                currentResizingLayer.size = originalResizingSize;
            }
        }

        resizePanel.setAttribute('data-visible', 'false');
        currentResizingIcon = null;
        currentResizingLayer = null;
        originalResizingSize = null;
    }

    // ===========================
    // Create Panel
    // ===========================

    function createPanel() {
        iconPanel = document.getElementById('cdpIconPanel');
        if (!iconPanel) return;

        const searchInput = document.getElementById('cdpIconSearch');
        const clearBtn = document.getElementById('cdpIconClear');
        const closeBtn = iconPanel.querySelector('.cdp-icon-panel-close');
        const cancelBtn = iconPanel.querySelector('.cdp-icon-btn--cancel');
        const addBtn = iconPanel.querySelector('.cdp-icon-btn--add');
        const tabs = iconPanel.querySelectorAll('.cdp-icon-tab');
        const gridColor = document.getElementById('cdpIconGridColor');
        const tutorialBtn = document.getElementById('cdpIconTutorial');

        closeBtn.addEventListener('click', closePanel);
        cancelBtn.addEventListener('click', closePanel);
        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleIconTutorialClick);
        }

        searchInput.addEventListener('input', renderIcons);
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            renderIcons();
            searchInput.focus();
        });

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('cdp-icon-tab--active'));
                tab.classList.add('cdp-icon-tab--active');
                currentCategory = tab.dataset.category;
                renderIcons();
            });
        });

        gridColor.addEventListener('click', (e) => {
            const btn = e.target.closest('.cdp-icon-item');
            if (!btn) return;
            
            gridColor.querySelectorAll('.cdp-icon-item').forEach(b => b.classList.remove('cdp-icon-item--selected'));
            
            btn.classList.add('cdp-icon-item--selected');
            selectedIcon = { type: 'emoji', char: btn.dataset.char };
        });

        addBtn.addEventListener('click', () => {
            addIcon();
            closePanel();
        });

        renderIcons();
    }

    function renderIcons() {
        if (!iconPanel) return;

        const searchInput = document.getElementById('cdpIconSearch');
        const query = searchInput.value.toLowerCase().trim();
        const gridColor = document.getElementById('cdpIconGridColor');

        let emojiList = currentCategory === 'all' ? EMOJI_DATA.all : (EMOJI_DATA[currentCategory] || []);
        
        // SEARCH FUNCTIONALITY - English keywords
        if (query) {
            let searchResults = [];
            
            // Check if query matches any keyword
            for (const [keyword, emojis] of Object.entries(SEARCH_KEYWORDS)) {
                if (keyword.includes(query) || query.includes(keyword)) {
                    searchResults.push(...emojis);
                }
            }
            
            // If we found matches, use them
            if (searchResults.length > 0) {
                emojiList = [...new Set(searchResults)]; // Remove duplicates
            } else {
                // No matches found, show empty
                emojiList = [];
            }
        }
        
        gridColor.innerHTML = '';
        const fragColor = document.createDocumentFragment();
        emojiList.forEach(char => {
            const btn = document.createElement('button');
            btn.className = 'cdp-icon-item';
            btn.type = 'button';
            btn.dataset.char = char;
            btn.textContent = char;
            fragColor.appendChild(btn);
        });
        gridColor.appendChild(fragColor);
    }

    function showPanel() {
        if (!iconPanel) createPanel();
        if (!iconPanel) return;

        iconPanel.style.transition = 'none';
        iconPanel.setAttribute('data-visible', 'true');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'none';
        }
    }

    function closePanel() {
        if (!iconPanel) return;
        
        iconPanel.style.transition = 'none';
        iconPanel.setAttribute('data-visible', 'false');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'auto';
        }
    }

    function addIcon() {
        if (!selectedIcon) {
            const firstColor = document.querySelector('#cdpIconGridColor .cdp-icon-item');
            if (firstColor) {
                selectedIcon = { type: 'emoji', char: firstColor.dataset.char };
            }
        }

        if (!selectedIcon) return;

        const view = window.cdpState.currentView || 'front';
        const boxMap = { front: 'boxFront', back: 'boxBack', right: 'boxRight', left: 'boxLeft' };
        const printBox = document.getElementById(boxMap[view]);

        if (!printBox) return;

        const iconEl = document.createElement('div');
        iconEl.className = 'cdp-icon-element';
        iconEl.id = 'icon-' + Date.now();

        iconEl.style.position = 'absolute';
        iconEl.style.left = '50%';
        iconEl.style.top = '50%';
        iconEl.style.transform = 'translate(-50%, -50%)';
        iconEl.style.transformOrigin = 'center';
        iconEl.style.cursor = 'grab';
        iconEl.style.zIndex = 9999;
        iconEl.style.userSelect = 'none';
        iconEl.style.pointerEvents = 'auto';
        iconEl.style.fontSize = '64px';
        iconEl.textContent = selectedIcon.char;

        printBox.appendChild(iconEl);

        // استفاده از API مرکزی برای اضافه کردن لایه
        const layerData = window.cdpLayers ? window.cdpLayers.addLayer({
            element: iconEl,
            name: `Icon: ${selectedIcon.char}`,
            type: 'icon',
            view: view
        }) : null;

        if (layerData) {
            layerData.iconType = selectedIcon.type;
            layerData.size = 64;
            attachIconEvents(iconEl, layerData);
        } else {
            // fallback - روش قدیمی
            const fallbackLayerData = {
                id: iconEl.id,
                name: `Icon: ${selectedIcon.char}`,
                type: 'icon',
                iconType: selectedIcon.type,
                view: view,
                visible: true,
                locked: false,
                size: 64,
                element: iconEl
            };
            attachIconEvents(iconEl, fallbackLayerData);
            window.layersByView[view].push(fallbackLayerData);
        }
    }

    // Fix for duplicate - reattach events
    window.reattachIconEvents = function(iconElement) {
        const view = window.cdpState.currentView || 'front';
        const layerData = window.layersByView[view].find(l => l.element === iconElement);
        
        if (layerData) {
            attachIconEvents(iconElement, layerData);
        }
    };

    iconBtn.addEventListener('click', showPanel);

    document.addEventListener('keydown', (e) => {
        if (iconPanel && iconPanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closePanel();
        }
        if (resizePanel && resizePanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closeResizePanel();
        }
    });

    // Export for external use (reattached icons)
    window.showIconResizePanel = function(iconEl, layerData) {
        currentResizingIcon = iconEl;
        currentResizingLayer = layerData;
        showResizePanel();
    };

    console.log("✅ Icon system ready!");
});


// Fix for duplicate
window.reattachIconEvents = function(iconElement) {
    const view = window.cdpState.currentView || 'front';
    const layerData = window.layersByView[view].find(l => l.element === iconElement);
    
    console.log("🔄 Reattaching icon events:", {
        element: iconElement.id,
        layerData: layerData,
        locked: layerData?.locked
    });
    
    if (layerData) {
        attachIconEvents(iconElement, layerData);
        console.log("✅ Icon events attached, locked =", layerData.locked);
    } else {
        console.error("❌ Icon layer data not found!");
    }
};

// New function that accepts layerData directly  
window.reattachIconEventsWithData = function(iconElement, layerData) {
    console.log("🔄 Reattaching icon events with data:", {
        element: iconElement.id,
        locked: layerData.locked
    });
    
    // چون attachIconEvents داخل closure است، باید دوباره بسازیم
    const handleReattachedIconDragStart = function(e) {
        console.log("🖱️ Icon mousedown (reattached):", iconElement.id, "locked =", layerData.locked);
        if (layerData.locked) {
            console.log("🔒 Icon is locked!");
            return;
        }
        if (e.type === 'mousedown' && e.button !== 0) return;
        
        // استفاده از متغیرهای global drag
        if (typeof window.iconDragState !== 'undefined') {
            beginIconDrag(window.iconDragState, iconElement, e, true);
        }

        iconElement.style.cursor = 'grabbing';
        e.preventDefault();
        e.stopPropagation();
    };

    iconElement.style.touchAction = 'none';
    iconElement.addEventListener('mousedown', handleReattachedIconDragStart, false);
    iconElement.addEventListener('pointerdown', handleReattachedIconDragStart, false);
    iconElement.addEventListener('touchstart', handleReattachedIconDragStart, { passive: false });

    iconElement.addEventListener('dblclick', function(e) {
        if (layerData.locked) return;
        
        if (typeof window.showIconResizePanel === 'function') {
            window.showIconResizePanel(iconElement, layerData);
        }
        
        e.preventDefault();
        e.stopPropagation();
    }, false);

    attachDoubleTapHandler(iconElement, function(e) {
        if (layerData.locked) return;
        if (typeof window.showIconResizePanel === 'function') {
            window.showIconResizePanel(iconElement, layerData);
        }
    }, () => window.iconDragState.isDragging || layerData.locked);
    
    console.log("✅ Icon events attached!");
};