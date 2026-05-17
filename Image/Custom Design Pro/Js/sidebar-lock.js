(function () {
    'use strict';

    const MOBILE_QUERY = '(max-width: 1024px)';

    function initMobileSidebar() {
        const sidebar = document.querySelector('.cdp-sidebar');
        if (!sidebar || document.querySelector('.cdp-mobile-menu-btn')) {
            return;
        }

        const menuButton = document.createElement('button');
        menuButton.type = 'button';
        menuButton.className = 'cdp-mobile-menu-btn';
        menuButton.setAttribute('aria-label', 'Open tools menu');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.innerHTML = '<i class="fa-solid fa-bars"></i>';

        const overlay = document.createElement('button');
        overlay.type = 'button';
        overlay.className = 'cdp-mobile-sidebar-overlay';
        overlay.setAttribute('aria-label', 'Close tools menu');

        document.body.appendChild(menuButton);
        document.body.appendChild(overlay);

        const mediaQuery = window.matchMedia(MOBILE_QUERY);

        function closeMenu() {
            document.body.classList.remove('cdp-mobile-menu-open');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.innerHTML = '<i class="fa-solid fa-bars"></i>';
        }

        function openMenu() {
            if (!mediaQuery.matches) {
                return;
            }
            document.body.classList.add('cdp-mobile-menu-open');
            menuButton.setAttribute('aria-expanded', 'true');
            menuButton.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        }

        function syncShell() {
            if (mediaQuery.matches) {
                document.body.classList.add('cdp-mobile-shell');
            } else {
                document.body.classList.remove('cdp-mobile-shell');
                closeMenu();
            }
        }

        menuButton.addEventListener('click', function () {
            if (document.body.classList.contains('cdp-mobile-menu-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        overlay.addEventListener('click', closeMenu);

        sidebar.addEventListener('click', function (event) {
            if (!mediaQuery.matches) {
                return;
            }
            const iconButton = event.target.closest('.cdp-icon-btn');
            if (!iconButton) {
                return;
            }
            if (iconButton.matches('[data-tool="file"]')) {
                return;
            }
            if (iconButton) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', syncShell);
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(syncShell);
        }

        syncShell();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileSidebar);
    } else {
        initMobileSidebar();
    }
})();