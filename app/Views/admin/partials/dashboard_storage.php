<?php
$storageStatus = $diskUsage['status'] ?? 'unknown';
$storageLabels = ['healthy' => 'Kapasitas tersedia', 'warning' => 'Mulai menipis', 'critical' => 'Hampir penuh', 'full' => 'Penyimpanan penuh', 'unknown' => 'Tidak tersedia'];
$storageMessages = [
    'healthy' => 'Ruang penyimpanan masih tersedia untuk aktivitas aplikasi.',
    'warning' => 'Penggunaan mencapai 80%. Mulai tinjau file yang sudah tidak diperlukan.',
    'critical' => 'Penggunaan mencapai 90%. Segera kosongkan ruang atau tambah kapasitas server.',
    'full' => 'Tidak ada ruang tersisa. Segera kosongkan ruang agar penyimpanan file dapat berjalan kembali.',
    'unknown' => 'Informasi kapasitas tidak dapat dibaca. Periksa izin akses atau batasan hosting.',
];
?>
<section class="storage-card storage-<?= esc($storageStatus, 'attr') ?>" aria-labelledby="storage-title">
    <div class="storage-heading">
        <div class="storage-title-group">
            <span class="storage-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.5h.01M7 16.5h.01M12 7.5h5M12 16.5h5" stroke-linecap="round"/></svg></span>
            <div><span class="storage-eyebrow">Monitor server</span><h2 id="storage-title">Penyimpanan server</h2></div>
        </div>
        <span class="storage-badge"><i aria-hidden="true"></i><?= esc($storageLabels[$storageStatus]) ?></span>
    </div>
    <?php if ($diskUsage !== null): ?>
        <div class="storage-body">
            <div class="storage-remaining"><span>Ruang tersisa</span><strong><?= esc($diskUsage['free']) ?></strong><small>dari <?= esc($diskUsage['total']) ?> kapasitas total</small></div>
            <div class="storage-meter">
                <div class="storage-meter-label"><span>Penggunaan disk</span><strong><?= esc(number_format($diskUsage['percent'], 1, ',', '.')) ?>%</strong></div>
                <div class="storage-track" role="progressbar" aria-label="Penggunaan penyimpanan server" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= esc((string) $diskUsage['percent'], 'attr') ?>"><span style="width: <?= esc((string) $diskUsage['percent'], 'attr') ?>%"></span></div>
                <div class="storage-meter-details"><span>Terpakai <b><?= esc($diskUsage['used']) ?></b></span><span>Total <b><?= esc($diskUsage['total']) ?></b></span></div>
            </div>
        </div>
    <?php endif ?>
    <p class="storage-message"><?= esc($storageMessages[$storageStatus]) ?></p>
    <footer class="storage-footnote">Disk penyimpanan aplikasi pada server ini, termasuk penggunaan oleh file lain pada disk yang sama. Server file terpisah dan kuota akun hosting tidak diukur. Diperbarui setiap halaman dimuat.</footer>
</section>
