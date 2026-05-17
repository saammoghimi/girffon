document.addEventListener('DOMContentLoaded', () => {
  const promptEl = document.getElementById('cdpWelcomePrompt');
  if (!promptEl) return;

  const helpBtn = document.querySelector('[data-tool="help"]');
  const cardEl = promptEl.querySelector('.cdp-welcome-card');
  const crestImageEl = promptEl.querySelector('.cdp-welcome-crest img');
  const bodyEl = promptEl.querySelector('.cdp-welcome-body');
  const titleEl = promptEl.querySelector('.cdp-welcome-body h2');
  const descEl = promptEl.querySelector('.cdp-welcome-desc');
  const kickerEl = promptEl.querySelector('.cdp-welcome-kicker');
  const actionsEl = promptEl.querySelector('.cdp-welcome-actions');
  const closeBtn = promptEl.querySelector('.cdp-welcome-close');
  const closeBtns = promptEl.querySelectorAll('[data-welcome-dismiss], [data-welcome-start]');
  const actionBtns = promptEl.querySelectorAll('.cdp-welcome-btn');
  const openHelpBtn = promptEl.querySelector('[data-welcome-help]');
  const startBtn = promptEl.querySelector('[data-welcome-start]');

  let timerId = null;

  function isPhoneLayout() {
    const coarsePointer = window.matchMedia('(pointer: coarse)').matches;
    const noHover = window.matchMedia('(hover: none)').matches;
    const width = Math.min(window.innerWidth || Number.MAX_SAFE_INTEGER, window.screen?.width || Number.MAX_SAFE_INTEGER);
    return coarsePointer && noHover && width <= 430;
  }

  function applyPromptLayout() {
    if (!cardEl) return;

    if (!isPhoneLayout()) {
      promptEl.style.padding = '';
      cardEl.style.width = '';
      cardEl.style.borderRadius = '';
      cardEl.style.padding = '';
      cardEl.style.gap = '';

      if (closeBtn) {
        closeBtn.style.top = '';
        closeBtn.style.right = '';
        closeBtn.style.width = '';
        closeBtn.style.height = '';
      }

      if (crestImageEl) {
        crestImageEl.style.maxWidth = '';
      }

      if (bodyEl) {
        bodyEl.style.maxWidth = '';
      }

      if (kickerEl) {
        kickerEl.style.fontSize = '';
        kickerEl.style.letterSpacing = '';
      }

      if (titleEl) {
        titleEl.style.margin = '';
        titleEl.style.fontSize = '';
        titleEl.style.lineHeight = '';
      }

      if (descEl) {
        descEl.style.fontSize = '';
        descEl.style.lineHeight = '';
      }

      if (actionsEl) {
        actionsEl.style.width = '';
        actionsEl.style.gap = '';
      }

      actionBtns.forEach((button) => {
        button.style.minHeight = '';
        button.style.padding = '';
        button.style.fontSize = '';
        button.style.borderRadius = '';
      });

      return;
    }

    promptEl.style.padding = '12px';
    cardEl.style.width = 'min(270px, calc(100vw - 44px))';
    cardEl.style.borderRadius = '18px';
    cardEl.style.padding = '14px 12px 12px';
    cardEl.style.gap = '9px';

    if (closeBtn) {
      closeBtn.style.top = '7px';
      closeBtn.style.right = '7px';
      closeBtn.style.width = '26px';
      closeBtn.style.height = '26px';
    }

    if (crestImageEl) {
      crestImageEl.style.maxWidth = '54px';
    }

    if (bodyEl) {
      bodyEl.style.maxWidth = '216px';
    }

    if (kickerEl) {
      kickerEl.style.fontSize = '8px';
      kickerEl.style.letterSpacing = '0.16em';
    }

    if (titleEl) {
      titleEl.style.margin = '0 0 4px';
      titleEl.style.fontSize = '14px';
      titleEl.style.lineHeight = '1.12';
    }

    if (descEl) {
      descEl.style.fontSize = '10.5px';
      descEl.style.lineHeight = '1.3';
    }

    if (actionsEl) {
      actionsEl.style.width = 'min(100%, 164px)';
      actionsEl.style.gap = '8px';
    }

    actionBtns.forEach((button) => {
      button.style.minHeight = '34px';
      button.style.padding = '7px 10px';
      button.style.fontSize = '10.5px';
      button.style.borderRadius = '9px';
    });
  }

  function showPrompt() {
    if (!promptEl || promptEl.dataset.visible === 'true') return;
    applyPromptLayout();
    promptEl.dataset.visible = 'true';
    promptEl.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function hidePrompt() {
    if (!promptEl) return;
    promptEl.dataset.visible = 'false';
    promptEl.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function schedulePrompt() {
    clearTimeout(timerId);
    timerId = setTimeout(showPrompt, 15000);
  }

  schedulePrompt();
  applyPromptLayout();

  window.addEventListener('resize', applyPromptLayout, { passive: true });
  window.addEventListener('orientationchange', applyPromptLayout, { passive: true });

  closeBtns.forEach((btn) => {
    btn.addEventListener('click', hidePrompt);
  });

  if (openHelpBtn) {
    openHelpBtn.addEventListener('click', () => {
      hidePrompt();
      window.open('https://www.youtube.com/@GirffoNStudio', '_blank', 'noopener,noreferrer');
    });
  }

  if (startBtn) {
    startBtn.addEventListener('click', hidePrompt);
  }
});
