document.addEventListener('DOMContentLoaded', () => {
  const promptEl = document.getElementById('cdpWelcomePrompt');
  if (!promptEl) return;

  const helpBtn = document.querySelector('[data-tool="help"]');
  const closeBtns = promptEl.querySelectorAll('[data-welcome-dismiss], [data-welcome-start]');
  const openHelpBtn = promptEl.querySelector('[data-welcome-help]');
  const startBtn = promptEl.querySelector('[data-welcome-start]');

  let timerId = null;

  function showPrompt() {
    if (!promptEl || promptEl.dataset.visible === 'true') return;
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
