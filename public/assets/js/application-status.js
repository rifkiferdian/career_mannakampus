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

    const modal = document.querySelector('[data-status-result-modal]');
    if (!(modal instanceof HTMLDialogElement)) return;

    const closeModal = () => {
        modal.close();
        document.body.classList.remove('status-modal-open');
    };

    modal.querySelector('[data-status-result-close]')?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    modal.addEventListener('close', () => document.body.classList.remove('status-modal-open'));

    modal.showModal();
    document.body.classList.add('status-modal-open');
})();
