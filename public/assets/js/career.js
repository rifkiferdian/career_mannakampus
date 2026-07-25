(() => {
    'use strict';

    const header = document.querySelector('.site-header');
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.primary-nav');
    const navLinks = [...document.querySelectorAll('.primary-nav a')];

    const closeMenu = () => {
        toggle?.setAttribute('aria-expanded', 'false');
        nav?.classList.remove('open');
        document.body.classList.remove('menu-open');
    };

    toggle?.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!isOpen));
        nav?.classList.toggle('open', !isOpen);
        document.body.classList.toggle('menu-open', !isOpen);
    });

    navLinks.forEach((link) => link.addEventListener('click', closeMenu));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu();
    });

    const sections = navLinks
        .map((link) => {
            const href = link.getAttribute('href');
            return href?.startsWith('#') ? document.querySelector(href) : null;
        })
        .filter(Boolean);

    const setActiveLink = () => {
        if (!sections.length) return;

        const offset = (header?.offsetHeight || 0) + 100;
        let currentId = sections[0]?.id;

        sections.forEach((section) => {
            if (window.scrollY >= section.offsetTop - offset) currentId = section.id;
        });

        navLinks.forEach((link) => {
            link.classList.toggle('active', link.getAttribute('href') === `#${currentId}`);
        });
    };

    const revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window) {
        revealElements.forEach((element) => element.classList.add('reveal-pending'));

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        revealElements.forEach((element) => revealObserver.observe(element));
    }

    window.addEventListener('scroll', setActiveLink, { passive: true });
    setActiveLink();
})();
