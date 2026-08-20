(() => {
    'use strict';

    const form = document.querySelector('#application-wizard');
    if (!form) return;

    const panels = [...form.querySelectorAll('.wizard-panel')];
    const indicators = [...document.querySelectorAll('[data-step-indicator]')];
    const previousButton = form.querySelector('[data-previous]');
    const nextButton = form.querySelector('[data-next]');
    const submitButton = form.querySelector('[data-submit]');
    const birthDateInput = form.querySelector('#birth-date');
    const ageOutput = form.querySelector('#applicant-age');
    const educationInput = form.querySelector('#last-education');
    const photoInput = form.querySelector('#profile-photo');
    const photoPreview = form.querySelector('#photo-preview');
    const photoLabel = form.querySelector('#photo-label');
    const positionChoices = [...form.querySelectorAll('[data-vacancy-choice]')];
    const positionCount = form.querySelector('#selected-position-count');
    const priorityInputs = [...form.querySelectorAll('[data-priority-input]')];
    let positionOrder = positionChoices
        .filter((choice) => choice.checked)
        .sort((first, second) => {
            const firstPriority = Number(form.querySelector(`[data-priority-input="${first.value}"]`)?.value || 99);
            const secondPriority = Number(form.querySelector(`[data-priority-input="${second.value}"]`)?.value || 99);
            return firstPriority - secondPriority;
        })
        .map((choice) => choice.value);
    let currentStep = 1;

    const ageFromDate = (dateValue) => {
        if (!dateValue) return '';
        const birthDate = new Date(`${dateValue}T00:00:00`);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDifference = today.getMonth() - birthDate.getMonth();

        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        return age >= 0 ? String(age) : '';
    };

    const synchronizeScreening = () => {
        const age = ageFromDate(birthDateInput?.value);
        if (ageOutput) ageOutput.value = age ? `${age} tahun` : 'Otomatis dari tanggal lahir';

        form.querySelectorAll('[data-autofill="age"]').forEach((input) => {
            input.value = age;
        });
        form.querySelectorAll('[data-autofill="education"]').forEach((input) => {
            input.value = educationInput?.value || '';
        });
        form.querySelectorAll('[data-autofill="gender"]').forEach((input) => {
            input.value = form.querySelector('[name="gender"]:checked')?.value || '';
        });
        form.querySelectorAll('[data-autofill="marital_status"]').forEach((input) => {
            input.value = form.querySelector('[name="marital_status"]')?.value || '';
        });
    };

    const synchronizePositions = () => {
        const selectedIds = positionChoices
            .filter((choice) => choice.checked)
            .map((choice) => choice.value);

        positionOrder = positionOrder.filter((vacancyId) => selectedIds.includes(vacancyId));
        selectedIds.forEach((vacancyId) => {
            if (!positionOrder.includes(vacancyId)) positionOrder.push(vacancyId);
        });

        priorityInputs.forEach((input) => {
            const priority = positionOrder.indexOf(input.dataset.priorityInput) + 1;
            input.value = priority > 0 ? String(priority) : '';

            const badge = form.querySelector(`[data-priority-badge="${input.dataset.priorityInput}"]`);
            if (badge) {
                badge.hidden = priority === 0;
                badge.textContent = priority > 0 ? `Prioritas ${priority}` : '';
            }
        });

        if (positionCount) positionCount.textContent = String(selectedIds.length);

        form.querySelectorAll('[data-screening-vacancy]').forEach((section) => {
            const isSelected = selectedIds.includes(section.dataset.screeningVacancy);
            section.hidden = !isSelected;
            section.querySelectorAll('input, select, textarea').forEach((field) => {
                field.disabled = !isSelected;
            });
        });

        synchronizeScreening();
    };

    const validatePanel = (panel) => {
        synchronizeScreening();
        const fields = [...panel.querySelectorAll('input, select, textarea')];
        const invalidField = fields.find((field) => !field.checkValidity());

        if (!invalidField) return true;
        invalidField.reportValidity();
        invalidField.focus();
        return false;
    };

    const buildReview = () => {
        const review = form.querySelector('#application-review');
        if (!review) return;

        const selectedText = (name) => {
            const field = form.elements.namedItem(name);
            if (!field) return '-';
            if (field instanceof RadioNodeList) {
                return field.value || '-';
            }
            if (field instanceof HTMLSelectElement) {
                return field.selectedOptions[0]?.textContent || '-';
            }
            return field.value || '-';
        };

        const items = [
            ['Prioritas Posisi', positionOrder.map((vacancyId, index) => {
                const choice = positionChoices.find((item) => item.value === vacancyId);
                const title = choice?.closest('.position-option')?.querySelector('strong')?.textContent;
                return title ? `${index + 1}. ${title}` : null;
            }).filter(Boolean).join(', ')],
            ['Nama Lengkap', selectedText('full_name')],
            ['NIK', selectedText('nik').replace(/^(.{4}).*(.{4})$/, '$1********$2')],
            ['Email', selectedText('email')],
            ['WhatsApp', selectedText('phone')],
            ['Pendidikan', selectedText('last_education')],
            ['Institusi', selectedText('institution')],
            ['Jurusan', selectedText('major')],
        ];

        review.replaceChildren(...items.map(([label, value]) => {
            const item = document.createElement('div');
            const labelElement = document.createElement('span');
            const valueElement = document.createElement('strong');
            item.className = 'review-item';
            labelElement.textContent = label;
            valueElement.textContent = value;
            item.append(labelElement, valueElement);
            return item;
        }));
    };

    const showStep = (step) => {
        currentStep = Math.min(Math.max(step, 1), panels.length);

        panels.forEach((panel, index) => {
            const isActive = index + 1 === currentStep;
            panel.hidden = !isActive;
            panel.classList.toggle('active', isActive);
        });

        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index + 1 === currentStep);
            indicator.classList.toggle('completed', index + 1 < currentStep);
            if (index + 1 === currentStep) indicator.setAttribute('aria-current', 'step');
            else indicator.removeAttribute('aria-current');
        });

        previousButton.hidden = currentStep === 1;
        nextButton.hidden = currentStep === panels.length;
        submitButton.hidden = currentStep !== panels.length;

        if (currentStep === panels.length) buildReview();
        document.querySelector('.wizard-progress')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    nextButton?.addEventListener('click', () => {
        const panel = panels[currentStep - 1];
        if (panel && validatePanel(panel)) showStep(currentStep + 1);
    });

    previousButton?.addEventListener('click', () => showStep(currentStep - 1));

    birthDateInput?.addEventListener('change', synchronizeScreening);
    educationInput?.addEventListener('change', synchronizeScreening);
    form.querySelectorAll('[name="gender"], [name="marital_status"]').forEach((field) => {
        field.addEventListener('change', synchronizeScreening);
    });

    positionChoices.forEach((choice) => {
        choice.addEventListener('change', () => {
            const selectedCount = positionChoices.filter((item) => item.checked).length;
            if (selectedCount > 3) {
                choice.checked = false;
                choice.setCustomValidity('Maksimal tiga posisi dapat dipilih.');
                choice.reportValidity();
                choice.setCustomValidity('');
            }
            synchronizePositions();
        });
    });

    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (!file) return;
        photoLabel.textContent = file.name;

        const image = document.createElement('img');
        image.alt = '';
        image.src = URL.createObjectURL(file);
        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
        photoPreview.replaceChildren(image);
    });

    form.querySelectorAll('.document-upload input').forEach((input) => {
        input.addEventListener('change', () => {
            const label = input.closest('.document-upload')?.querySelector('small');
            if (label && input.files?.[0]) label.textContent = input.files[0].name;
        });
    });

    form.addEventListener('submit', (event) => {
        if (!validatePanel(panels[panels.length - 1])) {
            event.preventDefault();
            return;
        }
        submitButton.disabled = true;
        submitButton.textContent = 'Mengirim...';
    });

    synchronizePositions();
    showStep(1);
})();
