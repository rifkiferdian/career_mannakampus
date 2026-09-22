<?php
$base = site_url('adminhrdmannakampus/agenda');
$dateUrl = static fn (string $date): string => $base . '?' . http_build_query(array_replace($filters, ['date' => $date]));
$viewUrl = static fn (string $view): string => $base . '?' . http_build_query(array_replace($filters, ['view' => $view]));
$resetUrl = $base . '?' . http_build_query(array_replace($filters, ['stage_id' => 0, 'pic_user_id' => 0, 'status' => '']));
$activeFilterCount = (int) ($filters['stage_id'] > 0) + (int) ($filters['pic_user_id'] > 0) + (int) ($filters['status'] !== '');
$dayNames = [1 => 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
?>
<section class="dashboard-welcome agenda-heading">
    <div><span class="login-eyebrow">Jadwal tim HRD</span><h1>Agenda Seleksi</h1><p>Pilih agenda untuk melihat peserta tes atau wawancara.</p></div>
    <?php if ($canManage): ?><a class="agenda-button" href="<?= $base ?>/baru">+ Buat Agenda</a><?php endif ?>
</section>
<section class="settings-card agenda-card">
    <nav class="agenda-view-switch" aria-label="Tampilan agenda">
        <?php foreach (['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan'] as $view => $label): ?>
            <a href="<?= esc($viewUrl($view), 'attr') ?>" class="<?= $filters['view'] === $view ? 'is-active' : '' ?>" <?= $filters['view'] === $view ? 'aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach ?>
    </nav>
    <form method="get" action="<?= $base ?>" class="agenda-filter-form">
        <input type="hidden" name="view" value="<?= esc($filters['view'], 'attr') ?>">
        <div class="agenda-primary-filter"><label><?= $filters['view'] === 'day' ? 'Tanggal agenda' : 'Pilih tanggal' ?><input type="date" name="date" value="<?= esc($filters['date'], 'attr') ?>" required></label><button class="agenda-button" type="submit">Tampilkan agenda</button></div>
        <div class="agenda-more-filters">
            <div class="agenda-filter-heading"><span class="agenda-filter-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16M7 12h10M10 17h4"/></svg></span><span class="agenda-filter-title"><strong>Filter agenda</strong><small>Tahap seleksi, PIC, dan status</small></span><?php if ($activeFilterCount > 0): ?><span class="agenda-filter-count"><?= $activeFilterCount ?> aktif</span><?php endif ?></div>
            <div class="agenda-filters">
                <label>Tahap seleksi<select name="stage_id"><option value="">Semua tahapan</option><?php foreach ($stages as $stage): ?><option value="<?= (int) $stage['id'] ?>" <?= (int) $stage['id'] === $filters['stage_id'] ? 'selected' : '' ?>><?= esc($stage['name']) ?></option><?php endforeach ?></select></label>
                <label>PIC<select name="pic_user_id"><option value="">Semua PIC</option><?php foreach ($pics as $pic): ?><option value="<?= (int) $pic['id'] ?>" <?= (int) $pic['id'] === $filters['pic_user_id'] ? 'selected' : '' ?>><?= esc($pic['full_name']) ?></option><?php endforeach ?></select></label>
                <label>Status<select name="status"><option value="">Semua status</option><?php foreach ($statuses as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= $value === $filters['status'] ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
            </div>
            <div class="agenda-filter-actions"><a class="agenda-button agenda-button-secondary" href="<?= esc($resetUrl, 'attr') ?>">Reset filter</a><button class="agenda-button" type="submit">Terapkan filter</button></div>
        </div>
    </form>
    <nav class="agenda-date-navigation" aria-label="Pindah periode">
        <a href="<?= esc($dateUrl($period->previous()), 'attr') ?>">&larr; Sebelumnya</a>
        <a href="<?= esc($dateUrl($period->next()), 'attr') ?>">Berikutnya &rarr;</a>
    </nav>
</section>
<section class="settings-card agenda-card">
    <div class="agenda-heading"><div><h2><?= esc($period->label()) ?></h2><p><?= (int) $total ?> agenda · <?= $canViewAll ? 'Seluruh tim HRD' : 'Agenda Anda dan tim PIC Anda' ?></p></div></div>
    <?php if ($filters['view'] === 'day'): ?>
    <div class="department-table-wrap"><table class="department-table agenda-table agenda-table--sessions">
        <thead><tr><th>Jam (WIB)</th><th>Agenda</th><th>Lokasi</th><th>PIC</th><th>Peserta aktif</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="7" class="department-empty">Belum ada agenda pada tanggal ini. Pilih tanggal lain atau buat agenda baru.</td></tr><?php endif ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td data-label="Jam"><strong class="agenda-time"><?= esc(date('H:i', strtotime($row['starts_at']))) ?><?= $row['ends_at'] ? '–' . esc(date('H:i', strtotime($row['ends_at']))) : '' ?></strong></td>
                <td data-label="Agenda"><a class="agenda-title-link" href="<?= $base ?>/<?= (int) $row['id'] ?>"><?= esc($row['name']) ?></a><small><?= esc($row['stage_name']) ?></small></td>
                <td data-label="Lokasi"><?= esc($row['venue']) ?></td><td data-label="PIC"><?= esc($row['pic_name']) ?></td>
                <td data-label="Peserta"><?= (int) ($counts[$row['id']] ?? 0) ?><?= $row['capacity'] !== null ? ' / ' . (int) $row['capacity'] : '' ?> orang</td>
                <td data-label="Status"><span class="agenda-badge agenda-badge-<?= esc($row['status'], 'attr') ?>"><?= esc($statuses[$row['status']] ?? $row['status']) ?></span></td>
                <td><a class="agenda-row-link" href="<?= $base ?>/<?= (int) $row['id'] ?>">Lihat peserta <span aria-hidden="true">&rarr;</span></a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base, 'parameters' => $filters]) ?>
    <?php else: ?>
        <?php if ($total === 0): ?><p class="agenda-calendar-empty">Belum ada agenda pada periode ini. Pilih periode lain atau buat agenda baru.</p><?php endif ?>
        <div class="agenda-calendar-scroll">
            <div class="agenda-calendar-grid agenda-calendar-grid--<?= esc($filters['view'], 'attr') ?>" aria-label="Agenda <?= esc($period->label(), 'attr') ?>">
                <?php foreach ($dayNames as $dayName): ?><div class="agenda-calendar-weekday"><?= $dayName ?></div><?php endforeach ?>
                <?php foreach ($period->days() as $day): ?>
                    <?php $dayRows = $rowsByDate[$day['date']] ?? []; ?>
                    <article class="agenda-calendar-day <?= $day['in_month'] ? '' : 'is-outside' ?> <?= $day['is_today'] ? 'is-today' : '' ?>" aria-label="<?= esc(date('d/m/Y', strtotime($day['date'])), 'attr') ?>, <?= count($dayRows) ?> agenda">
                        <a class="agenda-calendar-date" href="<?= esc($base . '?' . http_build_query(array_replace($filters, ['date' => $day['date'], 'view' => 'day'])), 'attr') ?>" aria-label="Lihat agenda <?= esc(date('d/m/Y', strtotime($day['date'])), 'attr') ?>"><span class="agenda-calendar-weekday-mobile"><?= $dayNames[(int) date('N', strtotime($day['date']))] ?></span><time datetime="<?= esc($day['date'], 'attr') ?>"><?= (int) $day['day'] ?></time><span class="agenda-calendar-month-mobile">/<?= esc(date('m', strtotime($day['date']))) ?></span><span class="agenda-calendar-count"><?= $dayRows !== [] ? count($dayRows) : '' ?></span></a>
                        <div class="agenda-calendar-events">
                            <?php foreach (array_slice($dayRows, 0, $filters['view'] === 'month' ? 3 : null) as $row): ?>
                                <a class="agenda-calendar-event agenda-calendar-event--<?= esc($row['status'], 'attr') ?>" href="<?= $base ?>/<?= (int) $row['id'] ?>" title="<?= esc(date('H:i', strtotime($row['starts_at'])) . ' · ' . $row['name'] . ' · ' . $row['stage_name'], 'attr') ?>"><strong><?= esc(date('H:i', strtotime($row['starts_at']))) ?> <?= esc($row['name']) ?></strong><small><?= esc($row['stage_name']) ?> · <?= (int) ($counts[$row['id']] ?? 0) ?> peserta</small></a>
                            <?php endforeach ?>
                            <?php if ($filters['view'] === 'month' && count($dayRows) > 3): ?><a class="agenda-calendar-more" href="<?= esc($base . '?' . http_build_query(array_replace($filters, ['date' => $day['date'], 'view' => 'day'])), 'attr') ?>">+<?= count($dayRows) - 3 ?> agenda lainnya</a><?php endif ?>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        </div>
        <p class="agenda-calendar-help"><?= $filters['view'] === 'month' ? 'Klik agenda untuk melihat peserta. Di ponsel, ketuk tanggal untuk membuka daftar agenda hari itu.' : 'Klik agenda untuk melihat peserta pada hari tersebut.' ?></p>
    <?php endif ?>
</section>
