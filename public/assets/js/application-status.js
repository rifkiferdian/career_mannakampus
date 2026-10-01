(() => {
    'use strict';

    const form = document.querySelector('[data-status-form]');
    const submitButton = form?.querySelector('[data-status-submit]');
    const submitLabel = submitButton?.querySelector('[data-status-submit-label]');

    form?.addEventListener('submit', () => {
        if (!(submitButton instanceof HTMLButtonElement)) return;

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        if (submitLabel) submitLabel.textContent = 'Memeriksa...';
    });

})();
