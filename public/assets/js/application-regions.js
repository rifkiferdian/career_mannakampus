(() => {
    'use strict';
    const container = document.querySelector('[data-region-fields]');
    if (!container) return;
    const fields = [...container.querySelectorAll('[data-region-select]')];
    const levels = ['provinces', 'regencies', 'districts', 'villages'];
    const labels = ['provinsi', 'kabupaten/kota', 'kecamatan', 'kelurahan/desa'];
    const status = container.querySelector('[data-region-status]');
    const retry = container.querySelector('[data-region-retry]');
    let requestVersion = 0;
    let failedIndex = null;
    let pendingRequest;

    const loadChildren = async (index) => {
        const version = ++requestVersion;
        pendingRequest?.abort();
        retry.hidden = true;
        failedIndex = null;
        fields.slice(index + 1).forEach((field, offset) => {
            field.replaceChildren(new Option(`Pilih ${labels[index + offset + 1]}`, ''));
            field.removeAttribute('aria-busy');
        });
        if (!fields[index].value || index === fields.length - 1) {
            status.textContent = 'Pilih wilayah berurutan dari provinsi hingga kelurahan/desa.';
            return;
        }
        const child = fields[index + 1];
        child.setAttribute('aria-busy', 'true');
        status.textContent = `Memuat ${labels[index + 1]}…`;
        const controller = new AbortController();
        pendingRequest = controller;
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const url = `${container.dataset.regionUrl}/${levels[index + 1]}?parent=${encodeURIComponent(fields[index].value)}`;
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Region request failed');
            const data = await response.json();
            if (!Array.isArray(data.regions)) throw new Error('Invalid region response');
            if (version !== requestVersion) return;
            data.regions.forEach((region) => child.add(new Option(region.name, region.code)));
            status.textContent = data.regions.length ? `Silakan pilih ${labels[index + 1]}.` : `Data ${labels[index + 1]} belum tersedia untuk wilayah ini.`;
        } catch (error) {
            if (version !== requestVersion) return;
            status.textContent = 'Wilayah gagal dimuat. Periksa koneksi lalu coba lagi.';
            failedIndex = index;
            retry.hidden = false;
        } finally {
            clearTimeout(timeout);
            if (version === requestVersion) child.removeAttribute('aria-busy');
        }
    };
    fields.forEach((field, index) => field.addEventListener('change', () => loadChildren(index)));
    retry.addEventListener('click', () => {
        if (failedIndex !== null) loadChildren(failedIndex);
    });
})();
