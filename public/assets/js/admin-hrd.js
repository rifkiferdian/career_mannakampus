(() => {
    'use strict';

    const passwordInput = document.querySelector('#password');
    const passwordToggle = document.querySelector('.password-toggle');

    passwordToggle?.addEventListener('click', () => {
        const willShow = passwordInput?.type === 'password';
        if (!passwordInput) return;
        passwordInput.type = willShow ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(willShow));
        passwordToggle.setAttribute('aria-label', willShow ? 'Sembunyikan password' : 'Tampilkan password');
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
})();
