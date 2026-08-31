<?php
$query = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0);
$queryString = $query === [] ? '' : '?' . http_build_query($query);
$pageUrl = static fn (int $page): string => site_url('adminhrdmannakampus/rekap-kehadiran') . '?' . http_build_query($query + ['page' => max(1, $page)]);
$pageStart = max(1, (int) $pagination['page'] - 2);
$pageEnd = min((int) $pagination['total_pages'], (int) $pagination['page'] + 2);
$returnQuery = http_build_query($query + ['page' => (int) $pagination['page']]);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Rekap Kehadiran | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=84">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'attendance-recap']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Recruitment Report</span><strong>Rekap Kehadiran</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/kalender-rekrutmen') ?>">Lihat kalender</a>
        </header>

        <div class="admin-content attendance-recap-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading attendance-recap-heading">
                <div><span class="login-eyebrow">Monitoring Seleksi</span><h1>Rekap Kehadiran</h1><p>Pantau kehadiran pelamar pada tes, wawancara, medical check-up, dan tahapan terjadwal lainnya.</p></div>
                <a class="new-user-jump report-export-link" href="<?= esc(site_url('adminhrdmannakampus/rekap-kehadiran/export') . $queryString, 'attr') ?>">Unduh Excel</a>
            </section>

            <section class="access-summary attendance-recap-summary" aria-label="Ringkasan kehadiran sesuai filter">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></i><strong><?= (int) $summary['total'] ?></strong><span>Total jadwal</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= (int) $summary['present'] ?></strong><span>Hadir</span></article>
                <article><i class="summary-card-icon icon-red" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></i><strong><?= (int) $summary['absent'] ?></strong><span>Tidak hadir</span></article>
                <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg></i><strong><?= (int) $summary['unrecorded'] ?></strong><span>Belum dicatat</span></article>
                <article><i class="summary-card-icon icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 2v4M16 2v4M5 9h14"/></svg></i><strong><?= (int) $summary['upcoming'] ?></strong><span>Akan datang</span></article>
            </section>

            <?php if ((int) $summary['unrecorded'] > 0): ?>
                <a class="attendance-unrecorded-alert" href="<?= site_url('adminhrdmannakampus/rekap-kehadiran') ?>?status=unrecorded&amp;date_from=<?= esc($filters['date_from'], 'attr') ?>&amp;date_to=<?= esc($filters['date_to'], 'attr') ?>"><strong><?= (int) $summary['unrecorded'] ?> jadwal telah lewat tetapi kehadirannya belum dicatat.</strong><span>Tampilkan dan selesaikan pencatatan sekarang &rarr;</span></a>
            <?php endif ?>

            <section class="settings-card attendance-filter-card">
                <form class="attendance-filter-form" action="<?= site_url('adminhrdmannakampus/rekap-kehadiran') ?>" method="get">
                    <input type="search" name="keyword" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Cari pelamar, email, atau nomor lamaran">
                    <select name="stage_id"><option value="">Semua tahapan</option><?php foreach ($stages as $stage): ?><option value="<?= (int) $stage['id'] ?>" <?= $filters['stage_id'] === (int) $stage['id'] ? 'selected' : '' ?>><?= esc($stage['name']) ?></option><?php endforeach ?></select>
                    <select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select>
                    <?php if ($canViewAll): ?><select name="team_id"><option value="">Semua divisi HRD</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= $filters['team_id'] === (int) $team['id'] ? 'selected' : '' ?>><?= esc($team['name']) ?></option><?php endforeach ?></select><?php endif ?>
                    <select name="status"><option value="">Semua status</option><option value="present" <?= $filters['status'] === 'present' ? 'selected' : '' ?>>Hadir</option><option value="absent" <?= $filters['status'] === 'absent' ? 'selected' : '' ?>>Tidak hadir</option><option value="unrecorded" <?= $filters['status'] === 'unrecorded' ? 'selected' : '' ?>>Belum dicatat</option><option value="upcoming" <?= $filters['status'] === 'upcoming' ? 'selected' : '' ?>>Akan datang</option><option value="reschedule_requested" <?= $filters['status'] === 'reschedule_requested' ? 'selected' : '' ?>>Minta jadwal ulang</option><option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option></select>
                    <label><span>Dari tanggal</span><input type="date" name="date_from" value="<?= esc($filters['date_from'], 'attr') ?>"></label>
                    <label><span>Sampai tanggal</span><input type="date" name="date_to" value="<?= esc($filters['date_to'], 'attr') ?>"></label>
                    <button type="submit">Terapkan</button><a href="<?= site_url('adminhrdmannakampus/rekap-kehadiran') ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card attendance-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></span><div><h2>Daftar jadwal seleksi</h2><p><?= $canViewAll ? 'Menampilkan jadwal seluruh divisi sesuai filter.' : 'Menampilkan jadwal yang menjadi tanggung jawab Anda sebagai PIC.' ?></p></div><span class="device-count"><?= count($rows) ?> / <?= (int) $pagination['total'] ?></span></div>
                <div class="department-table-wrap"><table class="department-table attendance-table"><thead><tr><th>No.</th><th>Waktu</th><th>Pelamar</th><th>Tahapan / Posisi</th><th>Divisi / PIC</th><th>Lokasi / Link</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                <?php if ($rows === []): ?><tr><td colspan="8" class="department-empty">Belum ada jadwal yang sesuai dengan filter.</td></tr><?php endif ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php $canMark = $canRecordAttendance && strtotime((string) $row['scheduled_at']) <= time() && ! in_array($row['status'], ['cancelled', 'reschedule_requested'], true); ?>
                    <tr>
                        <td class="report-order"><?= (int) $pagination['offset'] + $index + 1 ?></td>
                        <td class="attendance-date"><strong><?= esc(date('d/m/Y', strtotime((string) $row['scheduled_at']))) ?></strong><span><?= esc(date('H:i', strtotime((string) $row['scheduled_at']))) ?> WIB</span></td>
                        <td><div class="report-applicant"><strong><?= esc($row['full_name']) ?></strong><a href="mailto:<?= esc($row['email'], 'attr') ?>"><?= esc($row['application_number']) ?></a></div></td>
                        <td><div class="department-name-cell"><strong><?= esc($row['stage_name']) ?></strong><small><?= esc($row['vacancy_title']) ?></small></div></td>
                        <td><div class="department-name-cell"><strong><?= esc($row['hrd_team_name'] ?: 'Belum ada divisi') ?></strong><small>PIC: <?= esc($row['pic_name']) ?></small></div></td>
                        <td class="attendance-venue"><?= esc($row['venue']) ?></td>
                        <td><span class="attendance-status attendance-status-<?= esc($row['display_status_code'], 'attr') ?>"><?= esc($row['display_status']) ?></span></td>
                        <td><div class="attendance-actions"><?php if ($canMark): ?><form action="<?= site_url('adminhrdmannakampus/jadwal/' . $row['id'] . '/kehadiran') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" value="attendance-recap"><input type="hidden" name="return_query" value="<?= esc($returnQuery, 'attr') ?>"><input type="hidden" name="status" value="present"><button class="attendance-mark-present" type="submit" data-confirm="Catat <?= esc($row['full_name'], 'attr') ?> sebagai hadir?">Hadir</button></form><form action="<?= site_url('adminhrdmannakampus/jadwal/' . $row['id'] . '/kehadiran') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" value="attendance-recap"><input type="hidden" name="return_query" value="<?= esc($returnQuery, 'attr') ?>"><input type="hidden" name="status" value="absent"><button class="attendance-mark-absent" type="submit" data-confirm="Catat <?= esc($row['full_name'], 'attr') ?> sebagai tidak hadir?">Tidak hadir</button></form><?php endif ?><?php if ($canViewApplicant): ?><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $row['applicant_id']) ?>?source=list" target="_blank" rel="noopener noreferrer">Detail</a><?php endif ?><?php if (! $canMark && ! $canViewApplicant): ?>-<?php endif ?></div></td>
                    </tr>
                <?php endforeach ?>
                </tbody></table></div>
                <?php if ((int) $pagination['total_pages'] > 1): ?><nav class="vacancy-period-pagination" aria-label="Pagination rekap kehadiran"><span>Menampilkan <?= (int) $pagination['offset'] + 1 ?>-<?= min((int) $pagination['offset'] + (int) $pagination['per_page'], (int) $pagination['total']) ?> dari <?= (int) $pagination['total'] ?> jadwal</span><div><a class="pagination-direction <?= (int) $pagination['page'] === 1 ? 'is-disabled' : '' ?>" href="<?= (int) $pagination['page'] === 1 ? '#' : esc($pageUrl((int) $pagination['page'] - 1), 'attr') ?>" <?= (int) $pagination['page'] === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>&larr; Sebelumnya</a><?php if ($pageStart > 1): ?><a href="<?= esc($pageUrl(1), 'attr') ?>">1</a><?php if ($pageStart > 2): ?><i>&hellip;</i><?php endif ?><?php endif ?><?php for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++): ?><a class="<?= $pageNumber === (int) $pagination['page'] ? 'is-active' : '' ?>" href="<?= esc($pageUrl($pageNumber), 'attr') ?>" <?= $pageNumber === (int) $pagination['page'] ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a><?php endfor ?><?php if ($pageEnd < (int) $pagination['total_pages']): ?><?php if ($pageEnd < (int) $pagination['total_pages'] - 1): ?><i>&hellip;</i><?php endif ?><a href="<?= esc($pageUrl((int) $pagination['total_pages']), 'attr') ?>"><?= (int) $pagination['total_pages'] ?></a><?php endif ?><a class="pagination-direction <?= (int) $pagination['page'] === (int) $pagination['total_pages'] ? 'is-disabled' : '' ?>" href="<?= (int) $pagination['page'] === (int) $pagination['total_pages'] ? '#' : esc($pageUrl((int) $pagination['page'] + 1), 'attr') ?>" <?= (int) $pagination['page'] === (int) $pagination['total_pages'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Berikutnya &rarr;</a></div></nav><?php endif ?>
            </section>
        </div>
        <?= view('admin/partials/footer') ?>
    </main>
</div>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=14" defer></script>
</body>
</html>
