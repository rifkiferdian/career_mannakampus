<?php
$base = site_url('adminhrdmannakampus/rekap-agenda');
$recorded = (int) $summary['present'] + (int) $summary['absent'];
$summaryIcons = [
    'Agenda' => ['orange', '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h3M8 17h6"/>'],
    'Keikutsertaan' => ['blue', '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3h6v4H9zM9 12h6M9 16h6"/>'],
    'Pelamar unik' => ['purple', '<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>'],
    'Hadir' => ['green', '<circle cx="12" cy="12" r="9"/><path d="m7.5 12 3 3 6-6"/>'],
    'Tidak hadir' => ['red', '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/>'],
    'Kehadiran' => ['teal', '<path d="M4 4v16h16M8 16v-4M12 16V9M16 16V6"/>'],
];
$rate = $recorded > 0 ? number_format(100 * $summary['present'] / $recorded, 1, ',', '.') . '%' : '—';
?>
<section class="dashboard-welcome agenda-heading">
    <div><span class="login-eyebrow">Laporan seleksi</span><h1>Rekap Agenda</h1><p>Ringkasan agenda dan kehadiran peserta berdasarkan tanggal mulai agenda.</p></div>
    <a class="agenda-button" href="<?= esc($base . '/export?' . http_build_query($filters), 'attr') ?>">Unduh Excel</a>
</section>
<section class="settings-card agenda-card">
    <form method="get" action="<?= $base ?>" class="agenda-recap-filter">
        <div class="agenda-filters">
            <label>Dari tanggal<input type="date" name="date_from" value="<?= esc($filters['date_from'], 'attr') ?>" required></label>
            <label>Sampai tanggal<input type="date" name="date_to" value="<?= esc($filters['date_to'], 'attr') ?>" required></label>
            <label>Tahap<select name="stage_id"><option value="">Semua tahap</option><?php foreach ($stages as $stage): ?><option value="<?= (int) $stage['id'] ?>" <?= $filters['stage_id'] === (int) $stage['id'] ? 'selected' : '' ?>><?= esc($stage['name']) ?></option><?php endforeach ?></select></label>
            <label>PIC<select name="pic_user_id"><option value="">Semua PIC</option><?php foreach ($pics as $pic): ?><option value="<?= (int) $pic['id'] ?>" <?= $filters['pic_user_id'] === (int) $pic['id'] ? 'selected' : '' ?>><?= esc($pic['full_name']) ?></option><?php endforeach ?></select></label>
            <label>Status agenda<select name="status"><option value="">Semua status</option><?php foreach ($statuses as $key => $label): ?><option value="<?= esc($key, 'attr') ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
        </div>
        <div class="agenda-recap-filter-actions"><a class="agenda-button agenda-button-secondary" href="<?= $base ?>">Reset</a><button class="agenda-button" type="submit">Tampilkan</button></div>
    </form>
</section>
<section class="agenda-recap-summary" aria-label="Ringkasan rekap">
    <?php foreach (['Agenda' => number_format((int) $summary['agendas']), 'Keikutsertaan' => number_format((int) $summary['participants']), 'Pelamar unik' => number_format((int) $summary['unique_applicants']), 'Hadir' => number_format((int) $summary['present']), 'Tidak hadir' => number_format((int) $summary['absent']), 'Kehadiran' => $rate] as $label => $value): ?>
        <article class="settings-card">
            <div class="agenda-recap-metric-heading"><span><?= esc($label) ?></span><span class="agenda-recap-metric-icon is-<?= esc($summaryIcons[$label][0], 'attr') ?>" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $summaryIcons[$label][1] ?></svg></span></div>
            <strong><?= esc($value) ?></strong>
        </article>
    <?php endforeach ?>
</section>
<section class="settings-card agenda-card">
    <div class="agenda-section-heading"><h2>Daftar Agenda</h2><span><?= (int) $total ?> agenda</span></div>
    <p class="agenda-recap-help">Keikutsertaan mencakup seluruh pendaftaran, termasuk yang dibatalkan. Satu pelamar bisa mengikuti beberapa agenda. Kehadiran = hadir ÷ (hadir + tidak hadir).</p>
    <div class="department-table-wrap"><table class="department-table agenda-recap-table">
        <thead><tr><th>Agenda</th><th>Mulai (WIB)</th><th>PIC</th><th>Status</th><th>Terdaftar</th><th>Hadir</th><th>Tidak hadir</th><th>Belum dicatat / akan datang</th><th>Dibatalkan</th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="9" class="department-empty">Tidak ada agenda sesuai filter. Coba ubah periode atau reset filter.</td></tr><?php endif ?>
        <?php foreach ($rows as $row): ?>
            <tr><td><a class="agenda-title-link" href="<?= site_url('adminhrdmannakampus/agenda/' . (int) $row['id']) ?>"><?= esc($row['name']) ?></a><small><?= esc($row['stage_name']) ?></small></td>
                <td><?= esc(date('d/m/Y', strtotime($row['starts_at']))) ?><small><?= esc(date('H:i', strtotime($row['starts_at']))) ?></small></td>
                <td><?= esc($row['pic_name']) ?></td><td><span class="agenda-badge agenda-badge-<?= esc($row['status'], 'attr') ?>"><?= esc($statuses[$row['status']] ?? $row['status']) ?></span></td>
                <?php foreach (['participants', 'present', 'absent', 'pending', 'cancelled'] as $key): ?><td class="agenda-recap-number"><?= (int) $row[$key] ?></td><?php endforeach ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base, 'parameters' => $filters]) ?>
</section>
