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

    document.querySelectorAll('[data-swal-toast]').forEach((alert) => {
        if (typeof window.Swal === 'undefined') return;

        alert.hidden = true;
        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: alert.dataset.swalToast === 'success' ? 'success' : 'error',
            title: alert.textContent.trim(),
            showConfirmButton: false,
            timer: alert.dataset.swalToast === 'success' ? 3500 : 5000,
            timerProgressBar: true,
        });
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === 'true') return;

        const submitter = event.submitter;
        const message = submitter?.dataset.confirm || form.dataset.confirm;
        if (!message) return;

        const confirmSource = submitter || form;
        const confirmTitle = confirmSource.dataset.confirmTitle || 'Apakah Anda yakin?';
        const confirmButtonText = confirmSource.dataset.confirmButton || 'Ya, lanjutkan';
        const cancelButtonText = confirmSource.dataset.cancelButton || 'Batal';
        const confirmButtonColor = confirmSource.dataset.confirmColor || '#f87638';
        const consequences = (confirmSource.dataset.confirmDetails || '')
            .split('|')
            .map((item) => item.trim())
            .filter(Boolean);
        const fallbackMessage = consequences.length === 0
            ? message
            : `${message}\n\nKonsekuensi:\n- ${consequences.join('\n- ')}`;

        event.preventDefault();

        const activeModal = form.closest('dialog[open]');
        if (activeModal instanceof HTMLDialogElement) activeModal.close();

        const isConfirmed = typeof window.Swal === 'undefined'
            ? window.confirm(fallbackMessage)
            : (await window.Swal.fire({
                title: confirmTitle,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText,
                cancelButtonText,
                confirmButtonColor,
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
                returnFocus: false,
                customClass: consequences.length > 0 ? { popup: 'admin-confirm-popup' } : {},
                didOpen: () => {
                    if (consequences.length === 0) return;
                    const container = window.Swal.getHtmlContainer();
                    if (!container) return;
                    const consequenceBox = document.createElement('div');
                    consequenceBox.className = 'admin-confirm-consequences';
                    const heading = document.createElement('strong');
                    heading.textContent = 'Konsekuensi batal pilih:';
                    const list = document.createElement('ul');
                    consequences.forEach((item) => {
                        const listItem = document.createElement('li');
                        listItem.textContent = item;
                        list.appendChild(listItem);
                    });
                    consequenceBox.append(heading, list);
                    container.appendChild(consequenceBox);
                },
            })).isConfirmed;

        if (!isConfirmed) {
            if (activeModal instanceof HTMLDialogElement && !activeModal.open) {
                activeModal.showModal();
                if (submitter instanceof HTMLElement) submitter.focus();
            }
            return;
        }

        form.dataset.confirmed = 'true';
        if (submitter instanceof HTMLElement) {
            form.requestSubmit(submitter);
        } else {
            form.submit();
        }
    });

    document.querySelectorAll('.report-table .candidate-whatsapp-link[href^="https://wa.me/"]').forEach((link) => {
        const phone = link.getAttribute('href').replace('https://wa.me/', '').split('?')[0];
        const modalId = `report-whatsapp-modal-${phone}`;
        if (!document.getElementById(modalId)) return;
        link.setAttribute('href', '#');
        link.removeAttribute('target');
        link.removeAttribute('rel');
        link.setAttribute('role', 'button');
        link.setAttribute('data-admin-modal-open', modalId);
        link.setAttribute('aria-label', `Siapkan WhatsApp untuk ${link.closest('tr')?.querySelector('.report-applicant strong')?.textContent || 'pelamar'}`);
    });

    document.querySelectorAll('[data-admin-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.adminModalOpen || '');
            if (modal instanceof HTMLDialogElement && !modal.open) modal.showModal();
        });
    });

    document.querySelectorAll('[data-blacklist-duration]').forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) return;
        const form = select.closest('form');
        const customDateField = form?.querySelector('[data-blacklist-custom-date]');
        const customDateInput = customDateField?.querySelector('input[type="date"]');
        const updateCustomDate = () => {
            const isCustom = select.value === 'custom';
            if (customDateField instanceof HTMLElement) customDateField.hidden = !isCustom;
            if (customDateInput instanceof HTMLInputElement) customDateInput.required = isCustom;
        };
        select.addEventListener('change', updateCustomDate);
        updateCustomDate();
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

    document.querySelectorAll('[data-check-all]').forEach((checkAll) => {
        if (!(checkAll instanceof HTMLInputElement)) return;

        const group = checkAll.dataset.checkAll;
        const items = [...document.querySelectorAll(`[data-check-item="${group}"]`)]
            .filter((item) => item instanceof HTMLInputElement && !item.disabled);
        const updateCheckAll = () => {
            const selectedCount = items.filter((item) => item.checked).length;
            checkAll.checked = items.length > 0 && selectedCount === items.length;
            checkAll.indeterminate = selectedCount > 0 && selectedCount < items.length;
        };

        checkAll.addEventListener('change', () => {
            items.forEach((item) => { item.checked = checkAll.checked; });
            updateCheckAll();
        });
        items.forEach((item) => item.addEventListener('change', updateCheckAll));
        updateCheckAll();
    });

    document.querySelectorAll('[data-candidate-stage-select]').forEach((stageSelect) => {
        if (!(stageSelect instanceof HTMLSelectElement)) return;

        const form = stageSelect.closest('form');
        const context = form?.querySelector('[data-candidate-rejection-context]');
        const reasonField = form?.querySelector('[data-candidate-rejection-reason]');
        const reasonSelect = reasonField?.querySelector('select');
        const scheduleFields = form?.querySelector('[data-candidate-schedule-fields]');
        const scheduleRequiredFields = scheduleFields?.querySelectorAll('[data-schedule-required]') || [];
        const updateRejectionFields = () => {
            const isRejection = ['rejected', 'screening_failed'].includes(stageSelect.value);
            const isSchedulable = stageSelect.selectedOptions[0]?.dataset.schedulable === '1';
            if (context instanceof HTMLElement) context.hidden = !isRejection;
            if (reasonField instanceof HTMLElement) reasonField.hidden = !isRejection;
            if (scheduleFields instanceof HTMLElement) scheduleFields.hidden = !isSchedulable;
            scheduleRequiredFields.forEach((field) => {
                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) field.required = isSchedulable;
            });
            if (reasonSelect instanceof HTMLSelectElement) {
                reasonSelect.required = isRejection;
                if (!isRejection) reasonSelect.value = '';
            }
        };

        stageSelect.addEventListener('change', updateRejectionFields);
        updateRejectionFields();
    });

    document.querySelectorAll('[data-whatsapp-template-form]').forEach((form) => {
        const message = form.querySelector('[data-whatsapp-message]');
        const counter = form.querySelector('[data-whatsapp-character-count]');
        const preview = form.querySelector('[data-whatsapp-preview]');
        if (!(message instanceof HTMLTextAreaElement)) return;

        const sampleValues = {
            nama_pelamar: 'Ahmad Pratama',
            nama_recruiter: 'Admin HRD',
            nama_lowongan: 'Staff Administrasi',
            nama_tahap: 'Wawancara HRD',
            tanggal: '28 Agustus 2026',
            jam: '09.00',
            lokasi: 'Kantor Pusat Manna Kampus',
            nama_pic: 'Admin HRD',
            instruksi: 'Harap hadir 15 menit sebelum jadwal.',
            batas_konfirmasi: '27 Agustus 2026, 16.00 WIB',
            tahap_sebelumnya: 'Screening Dokumen',
            tahap_berikutnya: 'Wawancara HRD',
        };
        const updateWhatsappPreview = () => {
            if (counter instanceof HTMLElement) counter.textContent = String(message.value.length);
            if (preview instanceof HTMLElement) {
                preview.textContent = message.value.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/giu, (token, variable) => sampleValues[variable] || token);
            }
        };

        form.querySelectorAll('[data-whatsapp-variable]').forEach((button) => {
            button.addEventListener('click', () => {
                const variable = button.dataset.whatsappVariable || '';
                const start = message.selectionStart ?? message.value.length;
                const end = message.selectionEnd ?? start;
                message.setRangeText(variable, start, end, 'end');
                message.focus();
                updateWhatsappPreview();
            });
        });
        message.addEventListener('input', updateWhatsappPreview);
        updateWhatsappPreview();
    });

    document.querySelectorAll('[data-applicant-whatsapp-composer]').forEach((composer) => {
        const templateSelect = composer.querySelector('[data-applicant-whatsapp-template]');
        const applicationSelect = composer.querySelector('[data-applicant-whatsapp-application]');
        const scheduleSelect = composer.querySelector('[data-applicant-whatsapp-schedule]');
        const message = composer.querySelector('[data-applicant-whatsapp-message]');
        const counter = composer.querySelector('[data-applicant-whatsapp-count]');
        const openButton = composer.querySelector('[data-applicant-whatsapp-open]');
        if (!(templateSelect instanceof HTMLSelectElement)
            || !(applicationSelect instanceof HTMLSelectElement)
            || !(scheduleSelect instanceof HTMLSelectElement)
            || !(message instanceof HTMLTextAreaElement)
            || !(openButton instanceof HTMLAnchorElement)) return;

        const updateLink = () => {
            const text = message.value.trim();
            if (counter instanceof HTMLElement) counter.textContent = String(message.value.length);
            openButton.href = text === '' ? '#' : `https://wa.me/${composer.dataset.phone || ''}?text=${encodeURIComponent(text)}`;
            openButton.classList.toggle('is-disabled', text === '' || message.value.length > 2000);
            openButton.setAttribute('aria-disabled', text === '' || message.value.length > 2000 ? 'true' : 'false');
        };
        const selectRelevantSchedule = () => {
            const applicationId = applicationSelect.value;
            const visibleSchedules = [...scheduleSelect.options].filter((option) => {
                const visible = option.dataset.applicationId === '0' || option.dataset.applicationId === applicationId;
                option.hidden = !visible;
                option.disabled = !visible;
                return visible && option.dataset.applicationId !== '0';
            });
            const selectedSchedule = scheduleSelect.selectedOptions[0];
            if (!selectedSchedule || selectedSchedule.disabled) scheduleSelect.value = '';
            const category = templateSelect.selectedOptions[0]?.dataset.category || '';
            if (['schedule', 'reminder', 'confirmation'].includes(category) && scheduleSelect.value === '' && visibleSchedules[0]) {
                scheduleSelect.value = visibleSchedules[0].value;
            }
        };
        const generateMessage = () => {
            const template = templateSelect.selectedOptions[0]?.dataset.message || '';
            const application = applicationSelect.selectedOptions[0];
            const schedule = scheduleSelect.selectedOptions[0];
            const hasSchedule = schedule && schedule.value !== '';
            const values = {
                nama_pelamar: composer.dataset.applicant || '-',
                nama_recruiter: composer.dataset.recruiter || '-',
                nama_lowongan: application?.dataset.vacancy || '-',
                nama_tahap: hasSchedule ? (schedule.dataset.stage || '-') : (application?.dataset.stage || '-'),
                tanggal: hasSchedule ? (schedule.dataset.date || '-') : '-',
                jam: hasSchedule ? (schedule.dataset.time || '-') : '-',
                lokasi: hasSchedule ? (schedule.dataset.location || '-') : '-',
                nama_pic: hasSchedule ? (schedule.dataset.pic || composer.dataset.recruiter || '-') : (composer.dataset.recruiter || '-'),
                instruksi: hasSchedule ? (schedule.dataset.instructions || '-') : '-',
                batas_konfirmasi: hasSchedule ? (schedule.dataset.deadline || '-') : '-',
                tahap_sebelumnya: application?.dataset.previousStage || '-',
                tahap_berikutnya: application?.dataset.nextStage || '-',
            };
            message.value = template.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/giu, (token, variable) => values[variable] ?? token);
            updateLink();
        };

        templateSelect.addEventListener('change', () => { selectRelevantSchedule(); generateMessage(); });
        applicationSelect.addEventListener('change', () => { scheduleSelect.value = ''; selectRelevantSchedule(); generateMessage(); });
        scheduleSelect.addEventListener('change', generateMessage);
        message.addEventListener('input', updateLink);
        openButton.addEventListener('click', (event) => {
            if (openButton.classList.contains('is-disabled')) event.preventDefault();
        });
        selectRelevantSchedule();
        generateMessage();
    });
})();
