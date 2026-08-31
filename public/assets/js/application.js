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
    const workExperienceList = form.querySelector('[data-work-experience-list]');
    const workExperienceTemplate = form.querySelector('#work-experience-template');
    const addWorkExperienceButton = form.querySelector('[data-add-work-experience]');
    let nextWorkExperienceIndex = form.querySelectorAll('[data-work-experience-entry]').length;
    let serverValidationErrors = {};
    try {
        serverValidationErrors = JSON.parse(form.dataset.validationErrors || '{}');
    } catch (error) {
        serverValidationErrors = {};
    }
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

    const synchronizeWorkExperienceEntry = (entry) => {
        const fields = [...entry.querySelectorAll('[data-experience-field]')];
        const hasValue = fields.some((field) => field.value.trim() !== '');
        fields.forEach((field) => {
            const fieldName = field.name.match(/\[([^\]]+)]$/)?.[1];
            field.required = hasValue && fieldName !== 'end_year';
        });
        const startYear = entry.querySelector('[name$="[start_year]"]');
        const endYear = entry.querySelector('[name$="[end_year]"]');
        if (endYear) {
            const invalidPeriod = startYear?.value && endYear.value && Number(endYear.value) < Number(startYear.value);
            endYear.setCustomValidity(invalidPeriod ? 'Tahun akhir harus sama atau setelah tahun masuk.' : '');
        }
    };

    const fieldNameFromErrorKey = (errorKey) => {
        const parts = errorKey.split('.');
        return parts.length === 1 ? parts[0] : `${parts.shift()}${parts.map((part) => `[${part}]`).join('')}`;
    };

    const applyServerValidationErrors = () => {
        let firstInvalidField = null;
        Object.entries(serverValidationErrors).forEach(([errorKey, message]) => {
            const fieldName = fieldNameFromErrorKey(errorKey);
            const field = [...form.elements].find((element) => element.name === fieldName);
            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;

            field.setCustomValidity(String(message));
            firstInvalidField ||= field;
            const clearServerError = () => {
                field.setCustomValidity('');
                field.removeEventListener('input', clearServerError);
                field.removeEventListener('change', clearServerError);
            };
            field.addEventListener('input', clearServerError);
            field.addEventListener('change', clearServerError);
        });
        return firstInvalidField;
    };

    const renumberWorkExperiences = () => {
        const entries = [...form.querySelectorAll('[data-work-experience-entry]')];
        entries.forEach((entry, index) => {
            const number = entry.querySelector('[data-work-experience-number]');
            if (number) number.textContent = String(index + 1);
            synchronizeWorkExperienceEntry(entry);
        });
        if (addWorkExperienceButton) addWorkExperienceButton.disabled = entries.length >= 10;
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

        const fieldText = (field) => {
            if (!field) return '-';
            if (field instanceof RadioNodeList) {
                return field.value || '-';
            }
            if (field instanceof HTMLSelectElement) {
                return field.selectedOptions[0]?.textContent || '-';
            }
            if (field instanceof HTMLInputElement && field.type === 'file') {
                return field.files?.[0]?.name || '-';
            }
            return field.value || '-';
        };
        const selectedText = (name) => fieldText(form.elements.namedItem(name));
        const reviewItem = (label, value) => {
            const item = document.createElement('div');
            const labelElement = document.createElement('span');
            const valueElement = document.createElement('strong');
            const normalizedValue = value || '-';
            item.className = 'review-item';
            if (normalizedValue.length > 90 || normalizedValue.includes('\n')) item.classList.add('review-item-wide');
            if (normalizedValue === '-') item.classList.add('review-item-empty');
            labelElement.textContent = label;
            valueElement.textContent = normalizedValue;
            item.append(labelElement, valueElement);
            return item;
        };
        const reviewSection = (title, items) => {
            const section = document.createElement('section');
            const heading = document.createElement('div');
            const number = document.createElement('span');
            const headingText = document.createElement('div');
            const headingTitle = document.createElement('h3');
            const count = document.createElement('small');
            const grid = document.createElement('div');
            section.className = 'review-section';
            if (items.length > 4 || items.some(([, value]) => (value || '').length > 90)) {
                section.classList.add('review-section-wide');
            }
            heading.className = 'review-section-heading';
            number.className = 'review-section-number';
            headingText.className = 'review-section-title';
            headingTitle.textContent = title;
            count.textContent = `${items.length} data ditinjau`;
            headingText.append(headingTitle, count);
            heading.append(number, headingText);
            grid.className = 'review-grid';
            grid.append(...items.map(([label, value]) => reviewItem(label, value)));
            section.append(heading, grid);
            return section;
        };
        const maskedNik = selectedText('nik').replace(/^(\d{4})\d{8}(\d{4})$/, '$1********$2');
        const birthPlaceAndDate = [selectedText('birth_place'), selectedText('birth_date')]
            .filter((value) => value !== '-')
            .join(', ') || '-';
        const positionItems = positionOrder.map((vacancyId, index) => {
            const choice = positionChoices.find((item) => item.value === vacancyId);
            const title = choice?.closest('.position-option')?.querySelector('strong')?.textContent || '-';
            return [`Prioritas ${index + 1}`, title];
        });
        const sections = [
            reviewSection('Posisi yang Dilamar', positionItems),
            reviewSection('Biodata & Identitas', [
                ['NIK', maskedNik],
                ['Nama lengkap', selectedText('full_name')],
                ['Jenis kelamin', selectedText('gender')],
                ['Tempat, tanggal lahir', birthPlaceAndDate],
                ['Usia', ageOutput?.value || '-'],
                ['Status pernikahan', selectedText('marital_status')],
                ['Agama', selectedText('religion')],
                ['Tinggi badan', selectedText('height_cm') === '-' ? '-' : `${selectedText('height_cm')} cm`],
                ['Nomor WhatsApp', selectedText('phone')],
                ['Email aktif', selectedText('email')],
                ['Foto profil', selectedText('profile_photo')],
            ]),
            reviewSection('Alamat Domisili', [
                ['Alamat lengkap saat ini', selectedText('address')],
            ]),
            reviewSection('Pendidikan Terakhir', [
                ['Jenjang pendidikan', selectedText('last_education')],
                ['Sekolah/perguruan tinggi', selectedText('institution')],
                ['Jurusan', selectedText('major')],
                ['IPK/Nilai akhir', selectedText('gpa')],
                ['Pelatihan atau sertifikasi', selectedText('training_experience')],
            ]),
        ];

        const experienceItems = [...form.querySelectorAll('[data-work-experience-entry]')]
            .map((entry) => {
                const company = fieldText(entry.querySelector('[name$="[company_name]"]'));
                if (company === '-') return null;
                const positionTitle = fieldText(entry.querySelector('[name$="[position_title]"]'));
                const startYear = fieldText(entry.querySelector('[name$="[start_year]"]'));
                const endYear = fieldText(entry.querySelector('[name$="[end_year]"]'));
                const responsibilities = fieldText(entry.querySelector('[name$="[responsibilities]"]'));
                return [company, `${positionTitle}\n${startYear}–${endYear === '-' ? 'Sekarang' : endYear}\n${responsibilities}`];
            })
            .filter(Boolean);
        sections.push(reviewSection('Pengalaman Kerja', experienceItems.length > 0
            ? experienceItems
            : [['Riwayat perusahaan', 'Belum memiliki pengalaman kerja']]));

        form.querySelectorAll('[data-screening-vacancy]').forEach((screeningSection) => {
            if (screeningSection.hidden) return;

            const vacancyTitle = screeningSection.querySelector('.screening-position-heading strong')?.textContent || 'Posisi';
            const screeningItems = [...screeningSection.querySelectorAll('.screening-question')].map((question) => {
                const label = question.querySelector('label')?.textContent?.replace(/\s*\*\s*$/, '').trim() || 'Pertanyaan';
                const field = question.querySelector('input, select, textarea');
                return [label, fieldText(field)];
            });
            if (screeningItems.length > 0) {
                sections.push(reviewSection(`Screening Awal — ${vacancyTitle}`, screeningItems));
            }
        });

        sections.push(
            reviewSection('Motivasi', [
                ['Motivasi bekerja dan alasan ingin bergabung', selectedText('work_motivation')],
                ['Target/impian yang akan dicapai', selectedText('career_goal')],
            ]),
            reviewSection('Dokumen Pendukung', [
                ['Berkas lamaran lengkap (PDF)', selectedText('application_bundle')],
            ]),
        );

        const introduction = document.createElement('div');
        const introductionIcon = document.createElement('span');
        const introductionCopy = document.createElement('div');
        const introductionTitle = document.createElement('strong');
        const introductionText = document.createElement('p');
        introduction.className = 'review-introduction';
        introductionIcon.textContent = '✓';
        introductionTitle.textContent = 'Data lamaran siap diperiksa';
        introductionText.textContent = 'Periksa kembali setiap bagian. Gunakan tombol Kembali jika masih ada data yang perlu diperbaiki.';
        introductionCopy.append(introductionTitle, introductionText);
        introduction.append(introductionIcon, introductionCopy);

        sections.forEach((section, index) => {
            const number = section.querySelector('.review-section-number');
            if (number) number.textContent = String(index + 1).padStart(2, '0');
        });
        review.replaceChildren(introduction, ...sections);
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

    addWorkExperienceButton?.addEventListener('click', () => {
        if (!(workExperienceTemplate instanceof HTMLTemplateElement) || !workExperienceList) return;
        if (form.querySelectorAll('[data-work-experience-entry]').length >= 10) return;

        const wrapper = document.createElement('template');
        wrapper.innerHTML = workExperienceTemplate.innerHTML.replaceAll('__INDEX__', String(nextWorkExperienceIndex++));
        workExperienceList.append(wrapper.content.cloneNode(true));
        renumberWorkExperiences();
        workExperienceList.lastElementChild?.querySelector('input')?.focus();
    });

    workExperienceList?.addEventListener('input', (event) => {
        const entry = event.target.closest?.('[data-work-experience-entry]');
        if (entry) synchronizeWorkExperienceEntry(entry);
    });
    workExperienceList?.addEventListener('click', (event) => {
        const removeButton = event.target.closest?.('[data-remove-work-experience]');
        if (!removeButton) return;
        removeButton.closest('[data-work-experience-entry]')?.remove();
        renumberWorkExperiences();
    });

    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        photoInput.setCustomValidity('');
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            photoInput.setCustomValidity('Foto profil harus berformat JPG atau PNG.');
        } else if (file.size > 2 * 1024 * 1024) {
            photoInput.setCustomValidity('Ukuran foto profil maksimal 2 MB.');
        }
        if (!photoInput.checkValidity()) {
            photoInput.reportValidity();
            return;
        }
        photoLabel.textContent = file.name;

        const image = document.createElement('img');
        image.alt = '';
        image.src = URL.createObjectURL(file);
        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
        photoPreview.replaceChildren(image);
    });

    form.querySelectorAll('.document-upload input').forEach((input) => {
        input.addEventListener('change', () => {
            input.setCustomValidity('');
            const file = input.files?.[0];
            const label = input.closest('.document-upload')?.querySelector('small');
            if (file && file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                input.setCustomValidity('Berkas lamaran harus berformat PDF. Silakan pilih berkas lain.');
                if (label) label.textContent = 'File ditolak: pilih berkas berformat PDF.';
            } else if (file && file.size > 2 * 1024 * 1024) {
                input.setCustomValidity('Ukuran berkas lamaran maksimal 2 MB. Silakan kompres atau pilih berkas lain.');
                if (label) label.textContent = 'File ditolak: ukuran melebihi 2 MB.';
            } else if (label && file) {
                label.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
            } else if (label) {
                label.textContent = 'Pilih Berkas PDF maksimal 2 MB';
            }
            if (!input.checkValidity()) input.reportValidity();
        });
    });

    let csrfReady = false;

    form.addEventListener('submit', async (event) => {
        synchronizeScreening();
        const invalidPanelIndex = panels.findIndex((panel) =>
            [...panel.querySelectorAll('input, select, textarea')].some((field) => !field.checkValidity()));
        if (invalidPanelIndex >= 0) {
            event.preventDefault();
            showStep(invalidPanelIndex + 1);
            validatePanel(panels[invalidPanelIndex]);
            return;
        }

        if (!csrfReady && form.dataset.csrfUrl) {
            event.preventDefault();
            submitButton.disabled = true;
            submitButton.textContent = 'Menyiapkan pengiriman...';

            try {
                const response = await fetch(form.dataset.csrfUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error('CSRF refresh failed');

                const token = await response.json();
                const csrfField = form.elements.namedItem(token.tokenName);
                if (!(csrfField instanceof HTMLInputElement) || !token.tokenHash) {
                    throw new Error('Invalid CSRF response');
                }

                csrfField.value = token.tokenHash;
                csrfReady = true;
                submitButton.disabled = false;
                form.requestSubmit(submitButton);
            } catch (error) {
                submitButton.disabled = false;
                submitButton.textContent = 'Kirim Lamaran';

                let alert = form.querySelector('[data-csrf-error]');
                if (!alert) {
                    alert = document.createElement('div');
                    alert.className = 'form-alert';
                    alert.dataset.csrfError = '';
                    alert.setAttribute('role', 'alert');
                    form.prepend(alert);
                }
                alert.textContent = 'Sesi pengiriman belum dapat diperbarui. Periksa koneksi internet, lalu coba kirim kembali.';
                alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Mengirim...';
    });

    synchronizePositions();
    renumberWorkExperiences();
    const firstServerInvalidField = applyServerValidationErrors();
    const errorPanelIndex = firstServerInvalidField
        ? panels.indexOf(firstServerInvalidField.closest('.wizard-panel'))
        : -1;
    showStep(errorPanelIndex >= 0 ? errorPanelIndex + 1 : 1);
    if (firstServerInvalidField) {
        window.setTimeout(() => firstServerInvalidField.reportValidity(), 100);
    }
})();
