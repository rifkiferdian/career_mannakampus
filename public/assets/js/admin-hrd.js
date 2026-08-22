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
        const updateRejectionFields = () => {
            const isRejection = ['rejected', 'screening_failed'].includes(stageSelect.value);
            if (context instanceof HTMLElement) context.hidden = !isRejection;
            if (reasonField instanceof HTMLElement) reasonField.hidden = !isRejection;
            if (reasonSelect instanceof HTMLSelectElement) {
                reasonSelect.required = isRejection;
                if (!isRejection) reasonSelect.value = '';
            }
        };

        stageSelect.addEventListener('change', updateRejectionFields);
        updateRejectionFields();
    });
})();
