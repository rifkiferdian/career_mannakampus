(() => {
    'use strict';

    const jobSearchForm = document.querySelector('#job-search-form');
    if (!jobSearchForm) return;

    const keywordInput = jobSearchForm.querySelector('#job-keyword');
    const departmentSelect = jobSearchForm.querySelector('#job-department');
    const customSelect = jobSearchForm.querySelector('#department-select');
    const customSelectTrigger = customSelect?.querySelector('.custom-select-trigger');
    const customSelectLabel = customSelectTrigger?.querySelector('span');
    const customSelectOptions = [...(customSelect?.querySelectorAll('.custom-select-option') || [])];
    const jobOpeningsContainer = document.querySelector('#job-openings');
    const jobsCount = document.querySelector('#jobs-count');
    const searchButton = jobSearchForm.querySelector('.job-search-button');
    const searchUrl = jobSearchForm.dataset.searchUrl;

    if (!keywordInput || !departmentSelect || !jobOpeningsContainer || !jobsCount || !searchButton || !searchUrl) {
        return;
    }

    let searchTimer;
    let activeRequest;

    const targetedOpening = window.location.hash
        ? document.getElementById(window.location.hash.slice(1))
        : null;

    if (targetedOpening?.classList.contains('job-opening')) {
        targetedOpening.open = true;
    }

    const searchJobs = async ({ scrollToResults = false } = {}) => {
        activeRequest?.abort();
        const request = new AbortController();
        activeRequest = request;

        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('keyword', keywordInput.value.trim());
        url.searchParams.set('department', departmentSelect.value);

        jobOpeningsContainer.setAttribute('aria-busy', 'true');
        searchButton.disabled = true;

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: request.signal,
            });

            if (!response.ok) {
                throw new Error(`Pencarian gagal dengan status ${response.status}`);
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Respons pencarian tidak valid');
            }

            const result = await response.json();
            if (typeof result.html !== 'string' || !Number.isInteger(result.count)) {
                throw new Error('Data pencarian tidak valid');
            }

            jobOpeningsContainer.innerHTML = result.html;
            jobOpeningsContainer.querySelectorAll('.reveal').forEach((element) => {
                element.classList.add('visible');
            });
            jobsCount.textContent = `${result.count} posisi ditemukan`;

            if (scrollToResults) {
                document.querySelector('#open-positions-title')?.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                jobsCount.textContent = 'Pencarian gagal, silakan coba lagi';
            }
        } finally {
            if (activeRequest === request) {
                jobOpeningsContainer.removeAttribute('aria-busy');
                searchButton.disabled = false;
            }
        }
    };

    const queueSearch = () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => searchJobs(), 350);
    };

    jobSearchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(searchTimer);
        searchJobs({ scrollToResults: true });
    });

    keywordInput.addEventListener('input', queueSearch);

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
        if (customSelectLabel) customSelectLabel.textContent = option.textContent.trim();

        customSelectOptions.forEach((item) => {
            const isSelected = item === option;
            item.classList.toggle('selected', isSelected);
            item.setAttribute('aria-selected', String(isSelected));
        });

        closeCustomSelect();
        customSelectTrigger?.focus();
        searchJobs();
    };

    customSelectTrigger?.addEventListener('click', () => {
        if (customSelect?.classList.contains('open')) closeCustomSelect();
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
                customSelectTrigger?.focus();
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!customSelect?.contains(event.target)) closeCustomSelect();
    });
})();
