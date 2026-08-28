(() => {
    'use strict';

    const header = document.querySelector('.site-header');
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.primary-nav');
    const navLinks = [...document.querySelectorAll('.primary-nav a')];

    const recruitmentNotice = document.querySelector('#recruitment-notice');
    const recruitmentNoticeAccept = recruitmentNotice?.querySelector('[data-recruitment-notice-accept]');
    const recruitmentNoticeStorageKey = 'mannaRecruitmentNoticeAcceptedV1';

    const hasAcceptedRecruitmentNotice = () => {
        try {
            return window.localStorage.getItem(recruitmentNoticeStorageKey) === 'true';
        } catch (error) {
            return false;
        }
    };

    const acceptRecruitmentNotice = () => {
        try {
            window.localStorage.setItem(recruitmentNoticeStorageKey, 'true');
        } catch (error) {
            // The notice still closes for this visit when browser storage is unavailable.
        }

        recruitmentNotice?.close();
    };

    if (recruitmentNotice && !hasAcceptedRecruitmentNotice()) {
        recruitmentNotice.showModal();
    }

    recruitmentNotice?.addEventListener('cancel', (event) => event.preventDefault());
    recruitmentNoticeAccept?.addEventListener('click', acceptRecruitmentNotice);

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
