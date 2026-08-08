(() => {
    'use strict';

    document.querySelectorAll('.password-toggle').forEach((passwordToggle) => {
        passwordToggle.addEventListener('click', () => {
            const targetId = passwordToggle.dataset.passwordToggle || 'password';
            const passwordInput = document.getElementById(targetId);
            const willShow = passwordInput?.type === 'password';
            if (!passwordInput) return;
            passwordInput.type = willShow ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(willShow));
            passwordToggle.setAttribute('aria-label', willShow ? 'Sembunyikan password' : 'Tampilkan password');
        });
    });

    const sidebar = document.querySelector('#admin-sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');

    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        sidebarToggle?.setAttribute('aria-expanded', 'false');
    };

    sidebarToggle?.addEventListener('click', () => {
        const isOpen = sidebar?.classList.toggle('open') ?? false;
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSidebar();
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm(button.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-admin-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.adminModalOpen || '');
            if (modal instanceof HTMLDialogElement && !modal.open) modal.showModal();
        });
    });

    document.querySelectorAll('.admin-modal').forEach((modal) => {
        if (!(modal instanceof HTMLDialogElement)) return;

        modal.querySelectorAll('[data-admin-modal-close]').forEach((button) => {
            button.addEventListener('click', () => modal.close());
        });
        modal.addEventListener('click', (event) => {
            if (event.target === modal) modal.close();
        });
        if (modal.hasAttribute('data-auto-open')) modal.showModal();
    });
})();
