
(function () {
    function init() {
        var sidebar = document.getElementById('appSidebar');
        var toggle = document.getElementById('mobileMenuToggle');
        var close = document.getElementById('mobileMenuClose');
        var backdrop = document.getElementById('mobileMenuBackdrop');
        if (!sidebar || !toggle || !close || !backdrop) return;

        function isMobile() {
            return window.innerWidth <= 768;
        }

        function closeMenu() {
            sidebar.classList.remove('mobile-menu-open');
            backdrop.classList.remove('mobile-menu-visible');
            document.body.classList.remove('mobile-menu-active');
            document.body.style.removeProperty('overflow');
            toggle.setAttribute('aria-expanded', 'false');
        }

        function openMenu() {
            if (!isMobile()) return;
            sidebar.classList.add('mobile-menu-open');
            backdrop.classList.add('mobile-menu-visible');
            document.body.classList.add('mobile-menu-active');
            document.body.style.setProperty('overflow', 'hidden');
            toggle.setAttribute('aria-expanded', 'true');
        }

        function checkScreen() {
            if (!isMobile()) {
                closeMenu();
            }
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            if (toggle.getAttribute('aria-expanded') === 'true') {
                closeMenu();
            } else {
                openMenu();
            }
        });

        close.addEventListener('click', closeMenu);
        backdrop.addEventListener('click', closeMenu);

        sidebar.querySelectorAll('.sidebar-nav a, .sidebar-profile-dropdown a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeMenu();
        });

        window.addEventListener('resize', checkScreen);
        closeMenu();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
