document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const navLinks = Array.from(document.querySelectorAll('.sidebar .nav-link'));
    const desktopQuery = window.matchMedia('(min-width: 1024px)');
    const STORAGE_KEY = 'studio_sidebar_collapsed';

    function setDesktopCollapsed(collapsed) {
        if (!body) return;

        body.classList.toggle('sidebar-collapsed', collapsed);

        const toggleButtons = [desktopSidebarToggle, sidebarCollapseBtn].filter(Boolean);
        toggleButtons.forEach(function (button) {
            if (!button) return;
            const isExpanded = !collapsed;
            button.setAttribute('aria-expanded', String(isExpanded));
            button.title = isExpanded ? 'Contraer menú' : 'Expandir menú';
            button.setAttribute('aria-label', isExpanded ? 'Contraer o expandir menú' : 'Expandir o contraer menú');

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-bars', 'fa-angle-double-left', 'fa-angle-double-right');
                icon.classList.add(isExpanded ? 'fa-angle-double-left' : 'fa-angle-double-right');
            }
        });

        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (error) {
            console.warn('No se pudo guardar el estado del sidebar:', error);
        }
    }

    function isDesktop() {
        return desktopQuery.matches;
    }

    function closeMobileMenu() {
        body.classList.remove('sidebar-open');

        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }

        if (backdrop) {
            backdrop.setAttribute('aria-hidden', 'true');
        }
    }

    function openMobileMenu() {
        body.classList.add('sidebar-open');

        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-expanded', 'true');
        }

        if (backdrop) {
            backdrop.setAttribute('aria-hidden', 'false');
        }
    }

    function updateActiveLink() {
        const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';

        navLinks.forEach(function (link) {
            const href = link.getAttribute('href');
            if (!href) return;

            try {
                const linkUrl = new URL(href, window.location.origin);
                const normalizedHref = linkUrl.pathname.replace(/\/+$/, '') || '/';
                const isActive = normalizedHref === currentPath || (
                    currentPath !== '/' && normalizedHref !== '/' && currentPath.indexOf(normalizedHref) === 0
                );

                link.classList.toggle('active', isActive);
            } catch (error) {
                link.classList.remove('active');
            }
        });
    }

    function syncSidebarState() {
        if (!isDesktop()) {
            body.classList.remove('sidebar-collapsed');
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', body.classList.contains('sidebar-open') ? 'true' : 'false');
            }
            return;
        }

        const savedState = localStorage.getItem(STORAGE_KEY);
        if (savedState !== null) {
            setDesktopCollapsed(savedState === '1');
        } else {
            setDesktopCollapsed(false);
        }
    }

    if (desktopSidebarToggle) {
        desktopSidebarToggle.addEventListener('click', function () {
            const willCollapse = !body.classList.contains('sidebar-collapsed');
            setDesktopCollapsed(willCollapse);
        });
    }

    if (sidebarCollapseBtn) {
        sidebarCollapseBtn.addEventListener('click', function () {
            const willCollapse = !body.classList.contains('sidebar-collapsed');
            setDesktopCollapsed(willCollapse);
        });
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () {
            if (body.classList.contains('sidebar-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileMenu);
    }

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (!isDesktop()) {
                closeMobileMenu();
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && body.classList.contains('sidebar-open')) {
            closeMobileMenu();
        }
    });

    updateActiveLink();
    syncSidebarState();

    window.addEventListener('resize', function () {
        if (isDesktop()) {
            if (!body.classList.contains('sidebar-open')) {
                body.classList.remove('sidebar-open');
            }
            syncSidebarState();
        } else {
            body.classList.remove('sidebar-collapsed');
            closeMobileMenu();
        }
    });
});