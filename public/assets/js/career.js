(() => {
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

    const jobSearchForm = document.querySelector('#job-search-form');

    if (jobSearchForm) {
        const keywordInput = document.querySelector('#job-keyword');
        const departmentSelect = document.querySelector('#job-department');
        const customSelect = document.querySelector('#department-select');
        const customSelectTrigger = customSelect?.querySelector('.custom-select-trigger');
        const customSelectLabel = customSelectTrigger?.querySelector('span');
        const customSelectOptions = [...(customSelect?.querySelectorAll('.custom-select-option') || [])];
        const jobOpenings = [...document.querySelectorAll('.job-opening')];
        const jobsCount = document.querySelector('#jobs-count');
        const jobsEmpty = document.querySelector('#jobs-empty');

        const filterJobs = () => {
            const keyword = keywordInput.value.trim().toLocaleLowerCase('id');
            const department = departmentSelect.value;
            let visibleJobs = 0;

            jobOpenings.forEach((opening) => {
                const matchesKeyword = opening.dataset.title.toLocaleLowerCase('id').includes(keyword);
                const matchesDepartment = !department || opening.dataset.department === department;
                const isVisible = matchesKeyword && matchesDepartment;

                opening.hidden = !isVisible;
                if (isVisible) visibleJobs += 1;
            });

            jobsCount.textContent = `${visibleJobs} posisi ditemukan`;
            jobsEmpty.hidden = visibleJobs !== 0;
        };

        jobSearchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            filterJobs();
            document.querySelector('#open-positions-title')?.scrollIntoView({ behavior: 'smooth' });
        });

        keywordInput.addEventListener('input', filterJobs);

        const closeCustomSelect = () => {
            customSelect?.classList.remove('open');
            customSelectTrigger?.setAttribute('aria-expanded', 'false');
        };

        const openCustomSelect = () => {
            customSelect?.classList.add('open');
            customSelectTrigger?.setAttribute('aria-expanded', 'true');
            const selectedOption = customSelectOptions.find((option) => option.classList.contains('selected'));
            (selectedOption || customSelectOptions[0])?.focus();
        };

        const chooseDepartment = (option) => {
            departmentSelect.value = option.dataset.value;
            customSelectLabel.textContent = option.textContent.trim();

            customSelectOptions.forEach((item) => {
                const isSelected = item === option;
                item.classList.toggle('selected', isSelected);
                item.setAttribute('aria-selected', String(isSelected));
            });

            closeCustomSelect();
            customSelectTrigger.focus();
            filterJobs();
        };

        customSelectTrigger?.addEventListener('click', () => {
            if (customSelect.classList.contains('open')) closeCustomSelect();
            else openCustomSelect();
        });

        customSelectTrigger?.addEventListener('keydown', (event) => {
            if (['ArrowDown', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                openCustomSelect();
            }
        });

        customSelectOptions.forEach((option, index) => {
            option.addEventListener('click', () => chooseDepartment(option));
            option.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    customSelectOptions[(index + 1) % customSelectOptions.length].focus();
                }
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    customSelectOptions[(index - 1 + customSelectOptions.length) % customSelectOptions.length].focus();
                }
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeCustomSelect();
                    customSelectTrigger.focus();
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!customSelect?.contains(event.target)) closeCustomSelect();
        });
    }
})();
