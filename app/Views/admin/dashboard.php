<?php
$chartWidth = 760;
$chartHeight = 220;
$plotLeft = 38;
$plotRight = 18;
$plotTop = 20;
$plotBottom = 35;
$plotWidth = $chartWidth - $plotLeft - $plotRight;
$plotHeight = $chartHeight - $plotTop - $plotBottom;
$trendValues = $trend['values'];
$trendLabels = $trend['labels'];
$trendCount = count($trendValues);
$trendMax = max(1, $trendValues === [] ? 1 : max($trendValues));
$trendPoints = [];
foreach ($trendValues as $index => $chartValue) {
    $x = $plotLeft + ($trendCount <= 1 ? $plotWidth / 2 : ($index * $plotWidth / ($trendCount - 1)));
    $y = $plotTop + $plotHeight - (((int) $chartValue / $trendMax) * $plotHeight);
    $trendPoints[] = ['x' => round($x, 2), 'y' => round($y, 2), 'value' => (int) $chartValue, 'label' => $trendLabels[$index]];
}
$polyline = implode(' ', array_map(static fn (array $point): string => $point['x'] . ',' . $point['y'], $trendPoints));
$baseline = $plotTop + $plotHeight;
$area = $trendPoints === [] ? '' : $plotLeft . ',' . $baseline . ' ' . $polyline . ' ' . ($plotLeft + $plotWidth) . ',' . $baseline;
$labelInterval = max(1, (int) ceil(max(1, $trendCount) / 6));
$screenTotal = max(1, (int) $screening['total']);
$passedDegrees = ((int) $screening['passed'] / $screenTotal) * 360;
$failedDegrees = ((int) $screening['failed'] / $screenTotal) * 360;
$vacancyMax = max(1, $vacancyChart === [] ? 1 : max(array_map('intval', array_column($vacancyChart, 'application_count'))));
$pipelineMax = max(1, $pipeline === [] ? 1 : max(array_map('intval', array_column($pipeline, 'value'))));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Dashboard HRD | Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=25">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'dashboard']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Portal Rekrutmen</span><strong>Dashboard HRD</strong></div>
            <a class="view-career-link" href="<?= base_url() ?>" target="_blank" rel="noopener">Lihat situs karier</a>
        </header>

        <div class="admin-content analytics-dashboard-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome analytics-welcome">
                <div><span class="login-eyebrow">Recruitment Overview</span><h1>Halo, <?= esc(explode(' ', (string) ($auth['name'] ?? 'Admin'))[0]) ?>.</h1><p>Ringkasan performa dan pekerjaan rekrutmen yang perlu ditindaklanjuti.</p></div>
                <span class="dashboard-period-badge"><small>Periode laporan</small><strong><?= esc($filters['period_label']) ?></strong></span>
            </section>

            <section class="settings-card dashboard-filter-card">
                <form class="dashboard-filter-form" action="<?= site_url('adminhrdmannakampus/dashboard') ?>" method="get">
                    <label>Periode<select name="period"><option value="7" <?= $filters['period'] === '7' ? 'selected' : '' ?>>7 hari</option><option value="30" <?= $filters['period'] === '30' ? 'selected' : '' ?>>30 hari</option><option value="90" <?= $filters['period'] === '90' ? 'selected' : '' ?>>90 hari</option><option value="all" <?= $filters['period'] === 'all' ? 'selected' : '' ?>>Semua periode</option><option value="custom" <?= $filters['period'] === 'custom' ? 'selected' : '' ?>>Tanggal khusus</option></select></label>
                    <label>Dari<input type="date" name="date_from" value="<?= esc($filters['date_from'], 'attr') ?>"></label>
                    <label>Sampai<input type="date" name="date_to" value="<?= esc($filters['date_to'], 'attr') ?>"></label>
                    <label>Departemen<select name="department_id"><option value="">Semua departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= $filters['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select></label>
                    <label>Lowongan<select name="vacancy_id"><option value="">Semua lowongan</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>
                    <button type="submit">Terapkan</button><a href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Reset</a>
                </form>
            </section>

            <section class="analytics-metric-grid" aria-label="Ringkasan rekrutmen">
                <article><span class="analytics-metric-icon icon-orange"><svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg></span><div><small>Lowongan aktif</small><strong><?= (int) $metrics['open_vacancies'] ?></strong><p>Kondisi saat ini</p></div></article>
                <article><span class="analytics-metric-icon icon-blue"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg></span><div><small>Pelamar unik</small><strong><?= (int) $metrics['applicants'] ?></strong><p><?= esc($filters['period_label']) ?></p></div></article>
                <article><span class="analytics-metric-icon icon-purple"><svg viewBox="0 0 24 24"><path d="M6 4h9l3 3v13H6V4Z"/><path d="M14 4v4h4M9 12h6M9 16h6"/></svg></span><div><small>Total lamaran</small><strong><?= (int) $metrics['applications'] ?></strong><p>Semua posisi dipilih</p></div></article>
                <article><span class="analytics-metric-icon icon-cyan"><svg viewBox="0 0 24 24"><path d="M5 12h14M14 7l5 5-5 5"/></svg></span><div><small>Dalam proses</small><strong><?= (int) $metrics['active'] ?></strong><p>Belum terminal</p></div></article>
                <article><span class="analytics-metric-icon icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><div><small>Diterima</small><strong><?= (int) $metrics['accepted'] ?></strong><p>Kandidat berhasil</p></div></article>
                <article class="<?= (int) $metrics['overdue'] > 0 ? 'metric-attention' : '' ?>"><span class="analytics-metric-icon icon-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg></span><div><small>Melewati SLA</small><strong><?= (int) $metrics['overdue'] ?></strong><p>Perlu perhatian</p></div></article>
            </section>

            <section class="analytics-chart-grid analytics-chart-grid-primary">
                <article class="analytics-card trend-chart-card">
                    <header><div><span>Application Trend</span><h2>Tren lamaran</h2><p>Pergerakan lamaran pada <?= esc(mb_strtolower($filters['period_label'])) ?>.</p></div><strong><?= (int) $trend['total'] ?><small>lamaran</small></strong></header>
                    <div class="trend-chart-wrap">
                        <svg class="trend-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Grafik tren lamaran">
                            <defs><linearGradient id="trend-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f87638" stop-opacity=".28"/><stop offset="1" stop-color="#f87638" stop-opacity=".02"/></linearGradient></defs>
                            <?php for ($line = 0; $line <= 4; $line++): $gridY = $plotTop + ($line * $plotHeight / 4); ?><line class="trend-grid-line" x1="<?= $plotLeft ?>" y1="<?= $gridY ?>" x2="<?= $plotLeft + $plotWidth ?>" y2="<?= $gridY ?>"/><?php endfor ?>
                            <?php if ($area !== ''): ?><polygon class="trend-area" points="<?= esc($area, 'attr') ?>"/><polyline class="trend-line" points="<?= esc($polyline, 'attr') ?>"/><?php endif ?>
                            <?php foreach ($trendPoints as $index => $point): ?><circle class="trend-point" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="4"><title><?= esc($point['label']) ?>: <?= $point['value'] ?> lamaran</title></circle><?php if ($index % $labelInterval === 0 || $index === $trendCount - 1): ?><text class="trend-axis-label" x="<?= $point['x'] ?>" y="<?= $chartHeight - 8 ?>" text-anchor="middle"><?= esc($point['label']) ?></text><?php endif ?><?php endforeach ?>
                            <text class="trend-value-label" x="5" y="<?= $plotTop + 4 ?>"><?= $trendMax ?></text><text class="trend-value-label" x="18" y="<?= $baseline + 4 ?>">0</text>
                        </svg>
                    </div>
                </article>

                <article class="analytics-card screening-chart-card">
                    <header><div><span>Screening</span><h2>Hasil screening</h2><p>Komposisi hasil seleksi awal.</p></div></header>
                    <div class="screening-donut-wrap">
                        <div class="screening-donut" style="--passed-deg: <?= round($passedDegrees, 2) ?>deg; --failed-deg: <?= round($passedDegrees + $failedDegrees, 2) ?>deg"><span><strong><?= (int) $screening['total'] ?></strong><small>Lamaran</small></span></div>
                        <div class="screening-legend"><p><i class="legend-passed"></i><span>Lolos</span><strong><?= (int) $screening['passed'] ?></strong></p><p><i class="legend-failed"></i><span>Tidak lolos</span><strong><?= (int) $screening['failed'] ?></strong></p><p><i class="legend-pending"></i><span>Belum dinilai</span><strong><?= (int) $screening['pending'] ?></strong></p></div>
                    </div>
                </article>
            </section>

            <section class="analytics-chart-grid">
                <article class="analytics-card pipeline-chart-card"><header><div><span>Candidate Pipeline</span><h2>Distribusi tahapan</h2><p>Jumlah kandidat pada setiap tahapan seleksi.</p></div><?php if ($canViewCandidates): ?><a href="<?= site_url('adminhrdmannakampus/kandidat') ?>">Buka pipeline</a><?php endif ?></header><div class="horizontal-chart"><?php foreach ($pipeline as $row): ?><div class="horizontal-chart-row"><span><?= esc($row['label']) ?></span><div><i style="--bar-color: <?= esc($row['color'], 'attr') ?>; width: <?= round(((int) $row['value'] / $pipelineMax) * 100, 2) ?>%"></i></div><strong><?= (int) $row['value'] ?></strong></div><?php endforeach ?></div></article>
                <article class="analytics-card vacancy-chart-card"><header><div><span>Open Vacancies</span><h2>Lamaran per lowongan</h2><p>Lowongan aktif dengan lamaran terbanyak.</p></div><?php if ($canViewVacancies): ?><a href="<?= site_url('adminhrdmannakampus/lowongan') ?>">Kelola lowongan</a><?php endif ?></header><div class="horizontal-chart vacancy-horizontal-chart"><?php if ($vacancyChart === []): ?><p class="analytics-empty">Belum ada lowongan aktif.</p><?php endif ?><?php foreach ($vacancyChart as $row): ?><div class="horizontal-chart-row"><span title="<?= esc($row['title'], 'attr') ?>"><?= esc($row['title']) ?></span><div><i style="--bar-color: #f87638; width: <?= round(((int) $row['application_count'] / $vacancyMax) * 100, 2) ?>%"></i></div><strong><?= (int) $row['application_count'] ?></strong></div><?php endforeach ?></div></article>
            </section>

            <section class="analytics-card follow-up-card">
                <header><div><span>Action Required</span><h2>Kandidat yang perlu ditindaklanjuti</h2><p>Diprioritaskan berdasarkan pelanggaran SLA dan lama berada pada tahap.</p></div><?php if ($canViewCandidates): ?><a href="<?= site_url('adminhrdmannakampus/kandidat') ?>">Lihat semua</a><?php endif ?></header>
                <div class="department-table-wrap"><table class="department-table dashboard-follow-table"><thead><tr><th>Kandidat</th><th>Posisi</th><th>Tahap saat ini</th><th>Lama di tahap</th><th>SLA</th><th>Aksi</th></tr></thead><tbody><?php if ($followUps === []): ?><tr><td colspan="6" class="department-empty">Tidak ada kandidat yang perlu ditindaklanjuti pada periode ini.</td></tr><?php endif ?><?php foreach ($followUps as $row): ?><tr><td><div class="report-applicant"><strong><?= esc($row['full_name']) ?></strong><small><?= esc($row['email']) ?></small></div></td><td><div class="department-name-cell"><strong><?= esc($row['vacancy_title']) ?></strong><code><?= esc($row['department_name']) ?></code></div></td><td><span class="candidate-stage-pill" style="--candidate-color: <?= esc($row['stage_color'], 'attr') ?>"><i></i><?= esc($row['status_label']) ?></span></td><td><strong class="dashboard-stage-days <?= $row['is_overdue'] ? 'overdue' : '' ?>"><?= (int) $row['days_in_stage'] ?> hari</strong></td><td><?= (int) $row['sla_days'] > 0 ? (int) $row['sla_days'] . ' hari' : '-' ?></td><td><?php if ($canViewCandidates): ?><a class="dashboard-table-link" href="<?= site_url('adminhrdmannakampus/pelamar/' . $row['applicant_id']) ?>">Detail</a><?php else: ?>-<?php endif ?></td></tr><?php endforeach ?></tbody></table></div>
            </section>

            <section class="dashboard-bottom-grid">
                <article class="analytics-card activity-card"><header><div><span>Recent Activity</span><h2>Aktivitas rekrutmen</h2><p>Perubahan tahapan kandidat terbaru.</p></div></header><div class="dashboard-activity-list"><?php if ($activities === []): ?><p class="analytics-empty">Belum ada aktivitas pada periode ini.</p><?php endif ?><?php foreach ($activities as $activity): ?><div><i style="--activity-color: <?= esc($activity['stage_color'], 'attr') ?>"></i><p><strong><?= esc($activity['full_name']) ?></strong><span><?= esc($activity['status_label']) ?> untuk <?= esc($activity['vacancy_title']) ?></span><small><?= esc(date('d M Y, H:i', strtotime($activity['created_at']))) ?> · <?= esc($activity['changed_by_name'] ?: 'Sistem') ?></small></p></div><?php endforeach ?></div></article>
                <article class="analytics-card active-vacancy-card"><header><div><span>Vacancy Monitor</span><h2>Lowongan aktif</h2><p>Kebutuhan dan jumlah lamaran pada periode terpilih.</p></div></header><div class="dashboard-vacancy-list"><?php if ($openVacancyRows === []): ?><p class="analytics-empty">Belum ada lowongan aktif.</p><?php endif ?><?php foreach ($openVacancyRows as $vacancy): ?><div><p><strong><?= esc($vacancy['title']) ?></strong><span><?= esc($vacancy['period_name']) ?> · <?= esc($vacancy['department_name']) ?> · kebutuhan <?= (int) $vacancy['headcount'] ?> orang</span></p><strong><?= (int) $vacancy['application_count'] ?><small>lamaran</small></strong></div><?php endforeach ?></div></article>
            </section>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
