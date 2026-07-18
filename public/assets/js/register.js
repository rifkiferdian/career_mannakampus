(() => {
    const password = document.querySelector('#password');
    const strength = document.querySelector('.password-strength');

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.querySelector(`#${button.dataset.target}`);
            const isVisible = input.type === 'text';

            input.type = isVisible ? 'password' : 'text';
            button.classList.toggle('active', !isVisible);
            button.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });

    password?.addEventListener('input', () => {
        const value = password.value;
        let level = 0;

        if (value.length >= 8) level += 1;
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) level += 1;
        if (/\d/.test(value) && /[^A-Za-z0-9]/.test(value)) level += 1;

        if (strength) strength.dataset.level = String(level);
    });
})();
