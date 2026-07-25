(() => {
    'use strict';

    const tabLinks = [...document.querySelectorAll('#tabs a[href^="#"]')];
    const tabContents = tabLinks
        .map((link) => document.getElementById(link.hash.slice(1)))
        .filter(Boolean);

    const showTab = (selectedLink) => {
        const selectedId = selectedLink.hash.slice(1);

        tabLinks.forEach((link) => {
            link.classList.toggle('active', link === selectedLink);
        });

        tabContents.forEach((content) => {
            content.classList.toggle('hide', content.id !== selectedId);
        });
    };

    tabLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            showTab(link);
        });
    });

    if (tabLinks[0]) showTab(tabLinks[0]);

    document.querySelectorAll('.debug-arguments-toggle[data-target]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const target = document.getElementById(link.dataset.target);
            if (!target) return;

            const isVisible = window.getComputedStyle(target).display === 'block';
            target.style.display = isVisible ? 'none' : 'block';
            link.setAttribute('aria-expanded', String(!isVisible));
        });
    });
})();
