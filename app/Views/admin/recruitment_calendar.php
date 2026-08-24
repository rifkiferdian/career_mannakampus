<?php
$typeLabels = ['interview' => 'Wawancara', 'test' => 'Tes seleksi', 'vacancy_open' => 'Lowongan dibuka', 'vacancy_close' => 'Lowongan ditutup', 'candidate_deadline' => 'Deadline kandidat'];
$dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Kalender Rekrutmen | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=61">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'recruitment-calendar']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Recruitment Agenda</span><strong>Kalender Rekrutmen</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
        </header>

        <div class="admin-content recruitment-calendar-content">
            <section class="dashboard-welcome recruitment-calendar-heading">
                <div><span class="login-eyebrow">Kalender terpusat</span><h1><?= esc($monthLabel) ?></h1><p>Pantau jadwal wawancara, tes, periode lowongan, dan batas konfirmasi kandidat.</p></div>
                <div class="recruitment-calendar-navigation" aria-label="Navigasi bulan">
                    <a href="<?= site_url('adminhrdmannakampus/kalender-rekrutmen?month=' . $previousMonth) ?>" aria-label="Bulan sebelumnya">&larr;</a>
                    <a class="is-today" href="<?= site_url('adminhrdmannakampus/kalender-rekrutmen') ?>">Hari ini</a>
                    <a href="<?= site_url('adminhrdmannakampus/kalender-rekrutmen?month=' . $nextMonth) ?>" aria-label="Bulan berikutnya">&rarr;</a>
                </div>
            </section>

            <section class="access-summary recruitment-calendar-summary" aria-label="Ringkasan kalender bulan ini">
                <article><i class="summary-card-icon calendar-summary-interview" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 7h5M16 11h5"/></svg></i><strong><?= (int) $summary['interview'] ?></strong><span>Wawancara</span></article>
                <article><i class="summary-card-icon calendar-summary-test" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/></svg></i><strong><?= (int) $summary['test'] ?></strong><span>Tes seleksi</span></article>
                <article><i class="summary-card-icon calendar-summary-vacancy" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg></i><strong><?= (int) $summary['vacancy'] ?></strong><span>Buka / tutup lowongan</span></article>
                <article><i class="summary-card-icon calendar-summary-deadline" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg></i><strong><?= (int) $summary['deadline'] ?></strong><span>Deadline kandidat</span></article>
            </section>

            <div class="recruitment-calendar-layout">
                <section class="settings-card recruitment-calendar-card">
                    <div class="recruitment-calendar-legend" aria-label="Keterangan acara">
                        <?php foreach ($typeLabels as $type => $label): ?><span class="calendar-event-type-<?= esc($type, 'attr') ?>"><i></i><?= esc($label) ?></span><?php endforeach ?>
                    </div>
                    <div class="recruitment-calendar-grid" role="grid" aria-label="Kalender <?= esc($monthLabel, 'attr') ?>">
                        <?php foreach ($dayNames as $dayName): ?><div class="recruitment-calendar-weekday" role="columnheader"><?= esc($dayName) ?></div><?php endforeach ?>
                        <?php foreach ($days as $day): ?>
                            <div class="recruitment-calendar-day <?= $day['in_month'] ? '' : 'is-outside' ?> <?= $day['is_today'] ? 'is-today' : '' ?>" role="gridcell" aria-label="<?= esc(date('d-m-Y', strtotime($day['date'])), 'attr') ?>">
                                <time datetime="<?= esc($day['date'], 'attr') ?>"><?= (int) $day['day'] ?></time>
                                <div class="recruitment-calendar-events">
                                    <?php foreach ($day['events'] as $event): ?>
                                        <a class="recruitment-calendar-event calendar-event-type-<?= esc($event['type'], 'attr') ?>" href="<?= esc($event['url'], 'attr') ?>" title="<?= esc($event['title'] . ' — ' . $event['meta'], 'attr') ?>">
                                            <span><b><?= esc($event['time']) ?></b><?= esc($typeLabels[$event['type']] ?? 'Agenda') ?></span>
                                            <strong><?= esc($event['title']) ?></strong>
                                            <small><?= esc($event['meta']) ?></small>
                                        </a>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </section>

                <aside class="settings-card recruitment-upcoming-card">
                    <div class="settings-card-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 2v4M16 2v4M5 9h14"/></svg></span><div><h2>Agenda terdekat</h2><p>Delapan agenda selanjutnya.</p></div></div>
                    <div class="recruitment-upcoming-list">
                        <?php if ($upcoming === []): ?><div class="recruitment-calendar-empty">Belum ada agenda mendatang.</div><?php endif ?>
                        <?php foreach ($upcoming as $event): ?>
                            <a href="<?= esc($event['url'], 'attr') ?>" class="recruitment-upcoming-item calendar-event-type-<?= esc($event['type'], 'attr') ?>">
                                <time datetime="<?= esc(date('c', (int) $event['timestamp']), 'attr') ?>"><strong><?= esc(date('d', (int) $event['timestamp'])) ?></strong><span><?= esc(date('M', (int) $event['timestamp'])) ?></span></time>
                                <div><span><?= esc($typeLabels[$event['type']] ?? 'Agenda') ?> · <?= esc($event['time']) ?> WIB</span><strong><?= esc($event['title']) ?></strong><small><?= esc($event['subtitle']) ?></small><em><?= esc($event['meta']) ?></em></div>
                            </a>
                        <?php endforeach ?>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=7" defer></script>
</body>
</html>
