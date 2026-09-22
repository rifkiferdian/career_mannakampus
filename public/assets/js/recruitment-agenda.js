(() => {
    'use strict';
    const form = document.querySelector('[data-agenda-participants]');
    if (!form) return;
    const all = form.querySelector('[data-agenda-select-all]');
    const boxes = [...form.querySelectorAll('input[name="application_ids[]"]')];
    const count = form.querySelector('[data-agenda-selection-count]');
    const submit = form.querySelector('[data-agenda-add]');
    const refresh = () => {
        const selected = boxes.filter(box => box.checked).length;
        count.textContent = `${selected} peserta dipilih`;
        submit.textContent = `Tambahkan ${selected} Peserta`;
        submit.disabled = selected === 0;
        all.checked = boxes.length > 0 && selected === boxes.length;
        all.indeterminate = selected > 0 && selected < boxes.length;
        all.disabled = boxes.length === 0;
    };
    all.addEventListener('change', () => {
        boxes.forEach(box => { box.checked = all.checked; });
        refresh();
    });
    boxes.forEach(box => box.addEventListener('change', refresh));
    form.addEventListener('submit', () => {
        submit.disabled = true;
        submit.textContent = 'Menyimpan peserta…';
    });
    refresh();
})();
