// ===========================
// FLAG SYSTEM
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    console.log("🚩 flag.js loaded");

    const flagBtn = document.querySelector('[data-tool="flag"]');
    const FALLBACK_FLAG_TUTORIAL_URL = 'https://www.youtube.com/watch?v=6TxR7yZLQqY';
    let flagPanel = null;
    let resizePanel = null;
    let selectedFlag = null;
    let currentResizingFlag = null;
    let currentResizingLayer = null;
    let originalResizingWidth = null;
    let originalResizingHeight = null;
    const FLAG_PREVIEW_MAX_WIDTH = 280;

    let isDragging = false;
    let dragElement = null;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    let dragTransformExtras = '';
    let flagTutorialToastTimeout = null;

    if (!flagBtn) {
        console.error("❌ Flag button not found!");
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

    if (typeof window.cdpFlagTutorialUrl !== 'string' || !window.cdpFlagTutorialUrl.trim()) {
        window.cdpFlagTutorialUrl = FALLBACK_FLAG_TUTORIAL_URL;
    }

    const LOCAL_BASE = './country-flags-main/country-flags-main/svg';

    function handleFlagTutorialClick() {
        if (typeof window.cdpFlagTutorialHandler === 'function') {
            try {
                window.cdpFlagTutorialHandler();
                return;
            } catch (err) {
                console.error('Flag tutorial handler error', err);
            }
        }

        const tutorialUrl = window.cdpFlagTutorialUrl || FALLBACK_FLAG_TUTORIAL_URL;
        if (typeof tutorialUrl === 'string' && tutorialUrl.trim().length > 0) {
            window.open(tutorialUrl, '_blank', 'noopener');
            return;
        }

        showFlagTutorialToast('🎬 Flag tutorial coming soon');
    }

    function showFlagTutorialToast(message) {
        if (!message) return;

        let toast = document.getElementById('cdpFlagTutorialToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cdpFlagTutorialToast';
            toast.className = 'cdp-flag-tutorial-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.setAttribute('data-visible', 'true');

        if (flagTutorialToastTimeout) {
            clearTimeout(flagTutorialToastTimeout);
        }

        flagTutorialToastTimeout = setTimeout(() => {
            toast.setAttribute('data-visible', 'false');
        }, 2800);
    }

    const FLAGS = [
        { code: 'ad', name: 'Andorra' },
        { code: 'ae', name: 'United Arab Emirates' },
        { code: 'af', name: 'Afghanistan' },
        { code: 'ag', name: 'Antigua and Barbuda' },
        { code: 'ai', name: 'Anguilla' },
        { code: 'al', name: 'Albania' },
        { code: 'am', name: 'Armenia' },
        { code: 'ao', name: 'Angola' },
        { code: 'aq', name: 'Antarctica' },
        { code: 'ar', name: 'Argentina' },
        { code: 'as', name: 'American Samoa' },
        { code: 'at', name: 'Austria' },
        { code: 'au', name: 'Australia' },
        { code: 'aw', name: 'Aruba' },
        { code: 'ax', name: 'Åland Islands' },
        { code: 'az', name: 'Azerbaijan' },
        { code: 'ba', name: 'Bosnia and Herzegovina' },
        { code: 'bb', name: 'Barbados' },
        { code: 'bd', name: 'Bangladesh' },
        { code: 'be', name: 'Belgium' },
        { code: 'bf', name: 'Burkina Faso' },
        { code: 'bg', name: 'Bulgaria' },
        { code: 'bh', name: 'Bahrain' },
        { code: 'bi', name: 'Burundi' },
        { code: 'bj', name: 'Benin' },
        { code: 'bl', name: 'Saint Barthélemy' },
        { code: 'bm', name: 'Bermuda' },
        { code: 'bn', name: 'Brunei Darussalam' },
        { code: 'bo', name: 'Bolivia' },
        { code: 'bq', name: 'Caribbean Netherlands' },
        { code: 'br', name: 'Brazil' },
        { code: 'bs', name: 'Bahamas' },
        { code: 'bt', name: 'Bhutan' },
        { code: 'bw', name: 'Botswana' },
        { code: 'by', name: 'Belarus' },
        { code: 'bz', name: 'Belize' },
        { code: 'ca', name: 'Canada' },
        { code: 'cd', name: 'Democratic Republic of the Congo' },
        { code: 'cf', name: 'Central African Republic' },
        { code: 'cg', name: 'Republic of the Congo' },
        { code: 'ch', name: 'Switzerland' },
        { code: 'ci', name: "Côte d'Ivoire" },
        { code: 'ck', name: 'Cook Islands' },
        { code: 'cl', name: 'Chile' },
        { code: 'cm', name: 'Cameroon' },
        { code: 'cn', name: "China" },
        { code: 'co', name: 'Colombia' },
        { code: 'cr', name: 'Costa Rica' },
        { code: 'cu', name: 'Cuba' },
        { code: 'cv', name: 'Cape Verde' },
        { code: 'cw', name: 'Curaçao' },
        { code: 'cy', name: 'Cyprus' },
        { code: 'cz', name: 'Czech Republic' },
        { code: 'de', name: 'Germany' },
        { code: 'dj', name: 'Djibouti' },
        { code: 'dk', name: 'Denmark' },
        { code: 'dm', name: 'Dominica' },
        { code: 'do', name: 'Dominican Republic' },
        { code: 'dz', name: 'Algeria' },
        { code: 'ec', name: 'Ecuador' },
        { code: 'ee', name: 'Estonia' },
        { code: 'eg', name: 'Egypt' },
        { code: 'eh', name: 'Western Sahara' },
        { code: 'er', name: 'Eritrea' },
        { code: 'es', name: 'Spain' },
        { code: 'et', name: 'Ethiopia' },
        { code: 'eu', name: 'Europe' },
        { code: 'fi', name: 'Finland' },
        { code: 'fj', name: 'Fiji' },
        { code: 'fk', name: 'Falkland Islands' },
        { code: 'fm', name: 'Micronesia' },
        { code: 'fo', name: 'Faroe Islands' },
        { code: 'fr', name: 'France' },
        { code: 'ga', name: 'Gabon' },
        { code: 'gb', name: 'United Kingdom' },
        { code: 'gb-eng', name: 'England' },
        { code: 'gb-nir', name: 'Northern Ireland' },
        { code: 'gb-sct', name: 'Scotland' },
        { code: 'gb-wls', name: 'Wales' },
        { code: 'gd', name: 'Grenada' },
        { code: 'ge', name: 'Georgia' },
        { code: 'gh', name: 'Ghana' },
        { code: 'gi', name: 'Gibraltar' },
        { code: 'gl', name: 'Greenland' },
        { code: 'gm', name: 'Gambia' },
        { code: 'gn', name: 'Guinea' },
        { code: 'gq', name: 'Equatorial Guinea' },
        { code: 'gr', name: 'Greece' },
        { code: 'gt', name: 'Guatemala' },
        { code: 'gu', name: 'Guam' },
        { code: 'gw', name: 'Guinea-Bissau' },
        { code: 'gy', name: 'Guyana' },
        { code: 'hk', name: 'Hong Kong' },
        { code: 'hn', name: 'Honduras' },
        { code: 'hr', name: 'Croatia' },
        { code: 'ht', name: 'Haiti' },
        { code: 'hu', name: 'Hungary' },
        { code: 'id', name: 'Indonesia' },
        { code: 'ie', name: 'Ireland' },
        { code: 'il', name: 'Israel' },
        { code: 'im', name: 'Isle of Man' },
        { code: 'in', name: 'India' },
        { code: 'iq', name: 'Iraq' },
        { code: 'ir', name: 'Iran' },
        { code: 'is', name: 'Iceland' },
        { code: 'it', name: 'Italy' },
        { code: 'je', name: 'Jersey' },
        { code: 'jm', name: 'Jamaica' },
        { code: 'jo', name: 'Jordan' },
        { code: 'jp', name: 'Japan' },
        { code: 'ke', name: 'Kenya' },
        { code: 'kg', name: 'Kyrgyzstan' },
        { code: 'kh', name: 'Cambodia' },
        { code: 'ki', name: 'Kiribati' },
        { code: 'km', name: 'Comoros' },
        { code: 'kn', name: 'Saint Kitts and Nevis' },
        { code: 'kp', name: "North Korea" },
        { code: 'kr', name: 'South Korea' },
        { code: 'kw', name: 'Kuwait' },
        { code: 'ky', name: 'Cayman Islands' },
        { code: 'kz', name: 'Kazakhstan' },
        { code: 'la', name: "Laos" },
        { code: 'lb', name: 'Lebanon' },
        { code: 'lc', name: 'Saint Lucia' },
        { code: 'li', name: 'Liechtenstein' },
        { code: 'lk', name: 'Sri Lanka' },
        { code: 'lr', name: 'Liberia' },
        { code: 'ls', name: 'Lesotho' },
        { code: 'lt', name: 'Lithuania' },
        { code: 'lu', name: 'Luxembourg' },
        { code: 'lv', name: 'Latvia' },
        { code: 'ly', name: 'Libya' },
        { code: 'ma', name: 'Morocco' },
        { code: 'mc', name: 'Monaco' },
        { code: 'md', name: 'Moldova' },
        { code: 'me', name: 'Montenegro' },
        { code: 'mf', name: 'Saint Martin' },
        { code: 'mg', name: 'Madagascar' },
        { code: 'mh', name: 'Marshall Islands' },
        { code: 'mk', name: 'North Macedonia' },
        { code: 'ml', name: 'Mali' },
        { code: 'mm', name: 'Myanmar' },
        { code: 'mn', name: 'Mongolia' },
        { code: 'mo', name: 'Macao' },
        { code: 'mp', name: 'Northern Mariana Islands' },
        { code: 'mq', name: 'Martinique' },
        { code: 'mr', name: 'Mauritania' },
        { code: 'ms', name: 'Montserrat' },
        { code: 'mt', name: 'Malta' },
        { code: 'mu', name: 'Mauritius' },
        { code: 'mv', name: 'Maldives' },
        { code: 'mw', name: 'Malawi' },
        { code: 'mx', name: 'Mexico' },
        { code: 'my', name: 'Malaysia' },
        { code: 'mz', name: 'Mozambique' },
        { code: 'na', name: 'Namibia' },
        { code: 'nc', name: 'New Caledonia' },
        { code: 'ne', name: 'Niger' },
        { code: 'nf', name: 'Norfolk Island' },
        { code: 'ng', name: 'Nigeria' },
        { code: 'ni', name: 'Nicaragua' },
        { code: 'nl', name: 'Netherlands' },
        { code: 'no', name: 'Norway' },
        { code: 'np', name: 'Nepal' },
        { code: 'nr', name: 'Nauru' },
        { code: 'nu', name: 'Niue' },
        { code: 'nz', name: 'New Zealand' },
        { code: 'om', name: 'Oman' },
        { code: 'pa', name: 'Panama' },
        { code: 'pe', name: 'Peru' },
        { code: 'pf', name: 'French Polynesia' },
        { code: 'pg', name: 'Papua New Guinea' },
        { code: 'ph', name: 'Philippines' },
        { code: 'pk', name: 'Pakistan' },
        { code: 'pl', name: 'Poland' },
        { code: 'pm', name: 'Saint Pierre and Miquelon' },
        { code: 'pn', name: 'Pitcairn' },
        { code: 'pr', name: 'Puerto Rico' },
        { code: 'ps', name: 'Palestine' },
        { code: 'pt', name: 'Portugal' },
        { code: 'pw', name: 'Palau' },
        { code: 'py', name: 'Paraguay' },
        { code: 'qa', name: 'Qatar' },
        { code: 're', name: 'Réunion' },
        { code: 'ro', name: 'Romania' },
        { code: 'rs', name: 'Serbia' },
        { code: 'ru', name: 'Russian Federation' },
        { code: 'rw', name: 'Rwanda' },
        { code: 'sa', name: 'Saudi Arabia' },
        { code: 'sb', name: 'Solomon Islands' },
        { code: 'sc', name: 'Seychelles' },
        { code: 'sd', name: 'Sudan' },
        { code: 'se', name: 'Sweden' },
        { code: 'sg', name: 'Singapore' },
        { code: 'si', name: 'Slovenia' },
        { code: 'sk', name: 'Slovakia' },
        { code: 'sl', name: 'Sierra Leone' },
        { code: 'sm', name: 'San Marino' },
        { code: 'sn', name: 'Senegal' },
        { code: 'so', name: 'Somalia' },
        { code: 'sr', name: 'Suriname' },
        { code: 'ss', name: 'South Sudan' },
        { code: 'st', name: 'Sao Tome and Principe' },
        { code: 'sv', name: 'El Salvador' },
        { code: 'sy', name: 'Syrian Arab Republic' },
        { code: 'sz', name: 'Eswatini' },
        { code: 'tc', name: 'Turks and Caicos Islands' },
        { code: 'td', name: 'Chad' },
        { code: 'tg', name: 'Togo' },
        { code: 'th', name: 'Thailand' },
        { code: 'tj', name: 'Tajikistan' },
        { code: 'tk', name: 'Tokelau' },
        { code: 'tl', name: 'Timor-Leste' },
        { code: 'tm', name: 'Turkmenistan' },
        { code: 'tn', name: 'Tunisia' },
        { code: 'to', name: 'Tonga' },
        { code: 'tr', name: 'Turkey' },
        { code: 'tt', name: 'Trinidad and Tobago' },
        { code: 'tv', name: 'Tuvalu' },
        { code: 'tw', name: 'Taiwan' },
        { code: 'tz', name: 'Tanzania' },
        { code: 'ua', name: 'Ukraine' },
        { code: 'ug', name: 'Uganda' },
        { code: 'um', name: 'US Minor Outlying Islands' },
        { code: 'us', name: 'United States' },
        { code: 'uy', name: 'Uruguay' },
        { code: 'uz', name: 'Uzbekistan' },
        { code: 'va', name: 'Vatican City' },
        { code: 'vc', name: 'Saint Vincent and the Grenadines' },
        { code: 've', name: 'Venezuela' },
        { code: 'vg', name: 'Virgin Islands, British' },
        { code: 'vi', name: 'Virgin Islands, U.S.' },
        { code: 'vn', name: 'Vietnam' },
        { code: 'vu', name: 'Vanuatu' },
        { code: 'wf', name: 'Wallis and Futuna' },
        { code: 'ws', name: 'Samoa' },
        { code: 'xk', name: 'Kosovo' },
        { code: 'ye', name: 'Yemen' },
        { code: 'yt', name: 'Mayotte' },
        { code: 'za', name: 'South Africa' },
        { code: 'zm', name: 'Zambia' },
        { code: 'zw', name: 'Zimbabwe' }
    ];

    function stripTranslate(transformStr) {
        if (!transformStr || transformStr === 'none') return '';
        return transformStr.replace(/translate\([^)]+\)\s*/g, '').trim();
    }

    window.addEventListener('mousemove', function(e) {
        if (isDragging && dragElement) {
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            dragElement.style.left = (startLeft + deltaX) + 'px';
            dragElement.style.top = (startTop + deltaY) + 'px';
            const extras = dragTransformExtras || '';
            dragElement.style.transform = extras || 'none';
        }
    });

    window.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            if (dragElement) {
                dragElement.style.cursor = 'grab';
                dragElement = null;
            }
            dragTransformExtras = '';
        }
    });

function attachFlagEvents(flagEl, layerData) {
    // Mark that events are attached to prevent multiple attachments
    if (flagEl.dataset.eventsAttached === 'true') {
        console.log("⚠️ Events already attached to", flagEl.id);
        return;
    }
    
    flagEl.dataset.eventsAttached = 'true';
    
    flagEl.addEventListener('mousedown', function(e) {
        if (layerData.locked) {
            console.log("🔒 Flag locked:", flagEl.id);
            return;
        }
        
        console.log("✅ Dragging flag:", flagEl.id, "locked =", layerData.locked);
        
        isDragging = true;
        dragElement = flagEl;
        startX = e.clientX;
        startY = e.clientY;

        const inlineTransform = flagEl.style.transform || '';
        dragTransformExtras = stripTranslate(inlineTransform);

        const rect = flagEl.getBoundingClientRect();
        const parent = flagEl.parentElement.getBoundingClientRect();
        startLeft = rect.left - parent.left;
        startTop = rect.top - parent.top;

        flagEl.style.cursor = 'grabbing';
        e.preventDefault();
        e.stopPropagation();
    });

    flagEl.addEventListener('dblclick', function(e) {
        if (layerData.locked) return;
        
        currentResizingFlag = flagEl;
        currentResizingLayer = layerData;
        showResizePanel();
        
        e.preventDefault();
        e.stopPropagation();
    });
    
    console.log("✅ Attached events to", flagEl.id);
}
    window.attachFlagEventsInternal = attachFlagEvents;

    function getTransformParts(transformStr) {
        const raw = (transformStr || '').trim();
        const rotateMatch = raw.match(/rotate\((-?[\d.]+)deg\)/);
        const scaleMatch = raw.match(/scale\((-?[\d.]+),\s*(-?[\d.]+)\)/);
        const translateMatches = raw.match(/translate\([^)]+\)/g);

        return {
            rotation: rotateMatch ? parseFloat(rotateMatch[1]) : 0,
            scaleX: scaleMatch ? parseFloat(scaleMatch[1]) : 1,
            scaleY: scaleMatch ? parseFloat(scaleMatch[2]) : 1,
            translate: translateMatches ? translateMatches.join(' ') + ' ' : ''
        };
    }

    function applyPreviewTransform(previewEl, rotation, scaleX, scaleY) {
        if (!previewEl) return;
        previewEl.style.transformOrigin = 'center';
        previewEl.style.transform = `rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`;
    }

    function renderFlagPreview(previewEl, width) {
        if (!previewEl || !currentResizingFlag) return null;
        const img = currentResizingFlag.querySelector('img');
        if (!img) return null;

        const clone = img.cloneNode(true);
        clone.style.width = '100%';
        clone.style.height = '100%';
        clone.style.objectFit = 'contain';
        clone.style.display = 'block';

        const previewWidth = Math.min(width, FLAG_PREVIEW_MAX_WIDTH);
        const previewHeight = Math.round(previewWidth * 0.75);

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'cdp-flag-preview-item';
        previewWrapper.style.width = previewWidth + 'px';
        previewWrapper.style.height = previewHeight + 'px';
        previewWrapper.style.display = 'flex';
        previewWrapper.style.alignItems = 'center';
        previewWrapper.style.justifyContent = 'center';

        previewWrapper.appendChild(clone);
        previewEl.innerHTML = '';
        previewEl.appendChild(previewWrapper);

        return previewWrapper;
    }

    function createResizePanel() {
        if (resizePanel) return;

        resizePanel = document.createElement('div');
        resizePanel.className = 'cdp-icon-resize-panel';
        resizePanel.innerHTML = `
            <div class="cdp-icon-resize-content">
                <div class="cdp-icon-resize-header">
                    <h3>Resize Flag</h3>
                    <button type="button" class="cdp-icon-resize-close">&times;</button>
                </div>
                <div class="cdp-icon-resize-body">
                    <div class="cdp-icon-resize-group">
                        <label>Width: <span id="cdpFlagWidthValue">160</span>px</label>
                        <input type="range" id="cdpFlagWidthSlider" min="80" max="400" value="160" step="10">
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
                        <div id="cdpFlagResizePreview"></div>
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
                padding-left: 100px;
                z-index: 100000;
                pointer-events: none;
                --flag-resize-surface: #ffffff;
                --flag-resize-border: #e5e7eb;
                --flag-resize-divider: #e5e7eb;
                --flag-resize-text: #0f172a;
                --flag-resize-muted: #6b7280;
                --flag-resize-track: #e5e7eb;
                --flag-resize-thumb: #fbbf24;
                --flag-resize-shadow: rgba(15, 23, 42, 0.15);
                --flag-resize-preview-bg: #f8fafc;
                --flag-resize-preview-border: #e2e8f0;
                --flag-resize-button-bg: #f8fafc;
                --flag-resize-button-border: #d1d5db;
                --flag-resize-button-text: #0f172a;
            }
            body.dark-mode .cdp-icon-resize-panel {
                --flag-resize-surface: #050914;
                --flag-resize-border: #10182b;
                --flag-resize-divider: #10182b;
                --flag-resize-text: #f8fafc;
                --flag-resize-muted: #cbd5f5;
                --flag-resize-track: #172033;
                --flag-resize-thumb: #fbbf24;
                --flag-resize-shadow: rgba(0, 0, 0, 0.55);
                --flag-resize-preview-bg: #0b1220;
                --flag-resize-preview-border: #1e293b;
                --flag-resize-button-bg: #0b1220;
                --flag-resize-button-border: #1a2740;
                --flag-resize-button-text: #f8fafc;
            }
            .cdp-icon-resize-panel[data-visible="true"] {
                display: flex;
            }
            .cdp-icon-resize-content {
                background: var(--flag-resize-surface);
                border-radius: 14px;
                width: 520px;
                max-width: 90%;
                box-shadow: 0 30px 60px var(--flag-resize-shadow);
                border: 1px solid var(--flag-resize-border);
                pointer-events: auto;
                color: var(--flag-resize-text);
            }
            .cdp-icon-resize-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 18px 24px;
                border-bottom: 1px solid var(--flag-resize-divider);
            }
            .cdp-icon-resize-header h3 {
                margin: 0;
                color: var(--flag-resize-text);
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.04em;
            }
            .cdp-icon-resize-close {
                background: none;
                border: none;
                color: var(--flag-resize-muted);
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
                color: #fbbf24;
            }
            .cdp-icon-resize-body {
                padding: 22px 24px 18px;
                background: var(--flag-resize-surface);
            }
            .cdp-icon-resize-group {
                margin-bottom: 20px;
            }
            .cdp-icon-resize-group label {
                display: block;
                color: var(--flag-resize-muted);
                margin-bottom: 10px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            #cdpFlagWidthSlider {
                width: 100%;
                height: 6px;
                border-radius: 999px;
                background: var(--flag-resize-track);
                outline: none;
                -webkit-appearance: none;
            }
            #cdpFlagWidthSlider::-webkit-slider-runnable-track {
                height: 6px;
                border-radius: 999px;
                background: var(--flag-resize-track);
            }
            #cdpFlagWidthSlider::-moz-range-track {
                height: 6px;
                border-radius: 999px;
                background: var(--flag-resize-track);
            }
            #cdpFlagWidthSlider::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--flag-resize-thumb);
                cursor: pointer;
                box-shadow: 0 6px 18px rgba(251, 191, 36, 0.45);
                margin-top: -6px;
            }
            #cdpFlagWidthSlider::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--flag-resize-thumb);
                cursor: pointer;
                border: none;
                box-shadow: 0 6px 18px rgba(251, 191, 36, 0.45);
            }
            .cdp-icon-resize-transform {
                margin-bottom: 18px;
            }
            .cdp-icon-resize-transform label {
                display: block;
                color: var(--flag-resize-muted);
                margin-bottom: 10px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }
            .cdp-transform-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .cdp-transform-btn {
                flex: 1 1 calc(20% - 8px);
                min-width: 56px;
                padding: 10px 12px;
                background: var(--flag-resize-button-bg);
                border: 1px solid var(--flag-resize-button-border);
                border-radius: 10px;
                cursor: pointer;
                font-size: 18px;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--flag-resize-button-text);
                box-shadow: 0 8px 20px rgba(2, 6, 23, 0.08);
            }
            body.dark-mode .cdp-transform-btn {
                box-shadow: 0 8px 20px rgba(2, 6, 23, 0.65);
            }
            .cdp-transform-btn:hover {
                border-color: #fbbf24;
                color: #fbbf24;
            }
            .cdp-transform-btn:active {
                background: #fbbf24;
                color: #0b1220;
                border-color: #fbbf24;
            }
            .cdp-icon-resize-preview {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 150px;
                height: 280px;
                width: 100%;
                max-width: 360px;
                background: var(--flag-resize-preview-bg);
                border-radius: 16px;
                padding: 20px;
                border: 2px solid var(--flag-resize-preview-border);
                box-shadow: 0 15px 35px rgba(5, 9, 20, 0.15);
                overflow: hidden;
            }
            .cdp-icon-resize-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                padding: 18px 24px;
                border-top: 1px solid var(--flag-resize-divider);
                background: var(--flag-resize-surface);
            }
            .cdp-icon-resize-footer .cdp-icon-btn {
                min-width: 120px;
                padding: 10px 18px;
                border-radius: 999px;
                border: 1px solid var(--flag-resize-button-border);
                background: var(--flag-resize-button-bg);
                color: var(--flag-resize-button-text);
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                transition: all 0.2s ease;
            }
            .cdp-icon-resize-footer .cdp-icon-btn--cancel:hover {
                border-color: #fbbf24;
                color: #fbbf24;
            }
            .cdp-icon-resize-footer .cdp-icon-btn--apply {
                background: #fbbf24;
                color: #0b1220;
                border-color: #fbbf24;
                box-shadow: 0 15px 35px rgba(251, 191, 36, 0.25);
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
        const widthSlider = document.getElementById('cdpFlagWidthSlider');
        const widthValue = document.getElementById('cdpFlagWidthValue');
        const preview = document.getElementById('cdpFlagResizePreview');

        closeBtn.addEventListener('click', () => closeResizePanel());
        cancelBtn.addEventListener('click', () => closeResizePanel());

        widthSlider.addEventListener('input', function() {
            const width = parseInt(this.value, 10);
            widthValue.textContent = width;
            
            if (currentResizingFlag) {
                const height = Math.round(width * 0.75);
                currentResizingFlag.style.width = width + 'px';
                currentResizingFlag.style.height = height + 'px';

                if (currentResizingLayer) {
                    currentResizingLayer.width = width;
                    currentResizingLayer.height = height;
                }

                const previewFlag = renderFlagPreview(preview, width);
                const { rotation, scaleX, scaleY } = getTransformParts(currentResizingFlag.style.transform || '');
                applyPreviewTransform(previewFlag, rotation, scaleX, scaleY);
            }
        });

        // Transform buttons
        const transformButtons = resizePanel.querySelectorAll('.cdp-transform-btn');
        transformButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!currentResizingFlag) return;
                
                const action = this.getAttribute('data-action');
                const rawTransform = (currentResizingFlag.style.transform || '').trim();
                const currentTransform = rawTransform === 'none' ? '' : rawTransform;
                const parts = getTransformParts(currentTransform);
                let { rotation, scaleX, scaleY, translate } = parts;
                
                if (action === 'rotate-right') {
                    rotation += 1;
                } else if (action === 'rotate-left') {
                    rotation -= 1;
                } else if (action === 'rotate-90') {
                    rotation += 90;
                } else if (action === 'flip-horizontal') {
                    scaleX *= -1;
                } else if (action === 'flip-vertical') {
                    scaleY *= -1;
                }
                
                const newTransform = `${translate}rotate(${rotation}deg) scale(${scaleX}, ${scaleY})`.trim();
                currentResizingFlag.style.transformOrigin = 'center';
                currentResizingFlag.style.transform = newTransform || 'none';
                
                const previewFlag = preview.firstElementChild;
                applyPreviewTransform(previewFlag, rotation, scaleX, scaleY);
            });
        });

        applyBtn.addEventListener('click', function() {
            if (currentResizingFlag) {
                const width = parseInt(widthSlider.value, 10);
                const height = Math.round(width * 0.75);
                
                currentResizingFlag.style.width = width + 'px';
                currentResizingFlag.style.height = height + 'px';

                if (currentResizingLayer) {
                    currentResizingLayer.width = width;
                    currentResizingLayer.height = height;
                }
            }
            closeResizePanel({ commit: true });
        });
    }

    function showResizePanel() {
        if (!resizePanel) createResizePanel();
        
        if (!currentResizingFlag) return;

        const widthSlider = document.getElementById('cdpFlagWidthSlider');
        const widthValue = document.getElementById('cdpFlagWidthValue');
        const preview = document.getElementById('cdpFlagResizePreview');

        let currentWidth = 160;
        let currentHeight = Math.round(currentWidth * 0.75);
        if (currentResizingLayer && currentResizingLayer.width) {
            currentWidth = currentResizingLayer.width;
            currentHeight = currentResizingLayer.height || Math.round(currentWidth * 0.75);
        } else {
            const styles = window.getComputedStyle(currentResizingFlag);
            currentWidth = parseInt(styles.width, 10);
            currentHeight = parseInt(styles.height, 10);
        }

        widthSlider.value = currentWidth;
        widthValue.textContent = currentWidth;
        originalResizingWidth = currentWidth;
        originalResizingHeight = currentHeight;

        const previewFlag = renderFlagPreview(preview, currentWidth);
        const { rotation, scaleX, scaleY } = getTransformParts(currentResizingFlag.style.transform || '');
        applyPreviewTransform(previewFlag, rotation, scaleX, scaleY);

        resizePanel.setAttribute('data-visible', 'true');
    }

    function closeResizePanel(options = {}) {
        if (!resizePanel) return;

        const commit = options.commit === true;

        if (!commit && currentResizingFlag && originalResizingWidth !== null && originalResizingHeight !== null) {
            currentResizingFlag.style.width = originalResizingWidth + 'px';
            currentResizingFlag.style.height = originalResizingHeight + 'px';

            if (currentResizingLayer) {
                currentResizingLayer.width = originalResizingWidth;
                currentResizingLayer.height = originalResizingHeight;
            }
        }

        resizePanel.setAttribute('data-visible', 'false');
        currentResizingFlag = null;
        currentResizingLayer = null;
        originalResizingWidth = null;
        originalResizingHeight = null;
    }

    function createPanel() {
        flagPanel = document.getElementById('cdpFlagPanel');
        if (!flagPanel) return;

        const searchInput = document.getElementById('cdpFlagSearch');
        const clearBtn = document.getElementById('cdpFlagClear');
        const closeBtn = flagPanel.querySelector('.cdp-icon-panel-close');
        const cancelBtn = flagPanel.querySelector('.cdp-icon-btn--cancel');
        const addBtn = flagPanel.querySelector('.cdp-icon-btn--add');
        const grid = document.getElementById('cdpFlagGrid');
        const tutorialBtn = document.getElementById('cdpFlagTutorial');

        closeBtn.addEventListener('click', closePanel);
        cancelBtn.addEventListener('click', closePanel);
        if (tutorialBtn) {
            tutorialBtn.addEventListener('click', handleFlagTutorialClick);
        }

        searchInput.addEventListener('input', renderFlags);
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            renderFlags();
            searchInput.focus();
        });

        grid.addEventListener('click', (e) => {
            const btn = e.target.closest('.cdp-flag-item');
            if (!btn) return;
            
            grid.querySelectorAll('.cdp-flag-item').forEach(b => b.classList.remove('cdp-flag-item--selected'));
            
            btn.classList.add('cdp-flag-item--selected');
            selectedFlag = { code: btn.dataset.code, name: btn.dataset.name };
        });

        addBtn.addEventListener('click', () => {
            addFlag();
            closePanel();
        });

        renderFlags();
    }

    function renderFlags() {
        if (!flagPanel) return;

        const searchInput = document.getElementById('cdpFlagSearch');
        const query = searchInput.value.toLowerCase().trim();
        const grid = document.getElementById('cdpFlagGrid');

        let flagList = FLAGS;
        
        if (query) {
            flagList = FLAGS.filter(f => 
                f.name.toLowerCase().includes(query) || 
                f.code.toLowerCase().includes(query)
            );
        }
        
        grid.innerHTML = '';
        const frag = document.createDocumentFragment();
        
        flagList.forEach(flag => {
            const btn = document.createElement('button');
            btn.className = 'cdp-flag-item';
            btn.type = 'button';
            btn.dataset.code = flag.code;
            btn.dataset.name = flag.name;
            btn.title = flag.name;

            const img = document.createElement('img');
            img.src = `${LOCAL_BASE}/${flag.code.toLowerCase()}.svg`;
            img.alt = flag.name;
            img.loading = 'lazy';
            
            img.onerror = () => {
                btn.textContent = flag.code.toUpperCase();
                btn.style.fontSize = '10px';
                btn.style.fontWeight = '600';
                btn.style.color = '#999';
            };

            btn.appendChild(img);
            frag.appendChild(btn);
        });
        
        grid.appendChild(frag);
    }

    function showPanel() {
        if (!flagPanel) createPanel();
        if (!flagPanel) return;

        flagPanel.style.transition = 'none';
        flagPanel.setAttribute('data-visible', 'true');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'none';
        }
    }

    function closePanel() {
        if (!flagPanel) return;
        
        flagPanel.style.transition = 'none';
        flagPanel.setAttribute('data-visible', 'false');

        const sidebar = document.querySelector('.cdp-sidebar');
        if (sidebar) {
            sidebar.style.pointerEvents = 'auto';
        }
    }

    function addFlag() {
        if (!selectedFlag) {
            const firstFlag = document.querySelector('#cdpFlagGrid .cdp-flag-item');
            if (firstFlag) {
                selectedFlag = { code: firstFlag.dataset.code, name: firstFlag.dataset.name };
            }
        }

        if (!selectedFlag) return;

        const view = window.cdpState.currentView || 'front';
        const boxMap = { front: 'boxFront', back: 'boxBack', right: 'boxRight', left: 'boxLeft' };
        const printBox = document.getElementById(boxMap[view]);

        if (!printBox) return;

        const flagEl = document.createElement('div');
        flagEl.className = 'cdp-flag-element';
        flagEl.id = 'flag-' + Date.now();

        flagEl.style.position = 'absolute';
        flagEl.style.left = '50%';
        flagEl.style.top = '50%';
        flagEl.style.transform = 'translate(-50%, -50%)';
        flagEl.style.cursor = 'grab';
        flagEl.style.transformOrigin = 'center';
        flagEl.style.zIndex = 9999;
        flagEl.style.userSelect = 'none';
        flagEl.style.pointerEvents = 'auto';
        flagEl.style.width = '160px';
        flagEl.style.height = '120px';

        const img = document.createElement('img');
        img.src = `${LOCAL_BASE}/${selectedFlag.code.toLowerCase()}.svg`;
        img.alt = selectedFlag.name;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'contain';
        img.style.display = 'block';

        flagEl.appendChild(img);

        printBox.appendChild(flagEl);

        // استفاده از API مرکزی برای اضافه کردن لایه
        const layerData = window.cdpLayers ? window.cdpLayers.addLayer({
            element: flagEl,
            name: `Flag: ${selectedFlag.name}`,
            type: 'flag',
            view: view
        }) : null;

        if (layerData) {
            layerData.code = selectedFlag.code;
            layerData.width = 160;
            layerData.height = 120;
            attachFlagEvents(flagEl, layerData);
        } else {
            // fallback - روش قدیمی
            const fallbackLayerData = {
                id: flagEl.id,
                name: `Flag: ${selectedFlag.name}`,
                type: 'flag',
                code: selectedFlag.code,
                view: view,
                visible: true,
                locked: false,
                width: 160,
                height: 120,
                element: flagEl
            };
            attachFlagEvents(flagEl, fallbackLayerData);
            window.layersByView[view].push(fallbackLayerData);
        }
    }

    console.log('🚩 Flag button element:', flagBtn);
    console.log('🚩 Flag button disabled?', flagBtn.hasAttribute('disabled'));
    
    flagBtn.addEventListener('click', function(e) {
        console.log('🚩 FLAG BUTTON CLICKED!');
        console.log('🚩 Button disabled?', this.hasAttribute('disabled'));
        console.log('🚩 Button:', this);
        showPanel();
    });

    document.addEventListener('keydown', (e) => {
        if (flagPanel && flagPanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closePanel();
        }
        if (resizePanel && resizePanel.getAttribute('data-visible') === 'true' && e.key === 'Escape') {
            closeResizePanel();
        }
    });

    console.log("✅ Flag system ready!");
});

window.reattachFlagEvents = function(flagElement) {
    const view = window.cdpState.currentView || 'front';
    const layerData = window.layersByView[view].find(l => l.element === flagElement);
    
    if (layerData && window.attachFlagEventsInternal) {
        window.attachFlagEventsInternal(flagElement, layerData);
    }
};

window.reattachFlagEventsWithData = function(flagElement, layerData) {
    console.log("🔄 Reattaching flag events with data:", {
        element: flagElement.id,
        locked: layerData.locked
    });
    if (window.attachFlagEventsInternal) {
        window.attachFlagEventsInternal(flagElement, layerData);
    }
    console.log("✅ Flag events attached!");
};