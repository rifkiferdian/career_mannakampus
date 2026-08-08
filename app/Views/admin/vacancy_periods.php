<?php
$dateTimeLocal = static function (mixed $value): string {
    if (! $value) {
        return '';
    }
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
};
$dateLabel = static function (mixed $value): string {
    if (! $value) {
        return 'Tanpa batas';
    }
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? '-' : date('d/m/Y H:i', $timestamp);
};
$oldFor = static function (string $modal, string $field, mixed $fallback = '') use ($openModal): mixed {
    return $openModal === $modal ? old($field, $fallback) : $fallback;
};
$periodStatusCounts = array_count_values(array_column($periods, 'status'));
$periodApplicationCount = array_sum(array_map('intval', array_column($periods, 'application_count')));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Sesi Lowongan | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=27">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'vacancy-periods']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button><div><span>Recruitment Period</span><strong>Sesi Lowongan</strong></div><a class="view-career-link" href="<?= site_url('adminhrdmannakampus/lowongan') ?>">Kelola lowongan</a></header>
        <div class="admin-content vacancy-period-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading">
                <div><span class="login-eyebrow">Periode Rekrutmen</span><h1>Sesi Lowongan</h1><p>Buka lowongan yang sama dalam beberapa periode tanpa mencampur kandidat dan laporan.</p></div>
                <?php if ($canManage): ?><button class="new-user-jump vacancy-create-link" type="button" data-admin-modal-open="period-create-modal">+ Tambah sesi</button><?php endif ?>
            </section>

            <section class="access-summary vacancy-summary" aria-label="Ringkasan sesi lowongan">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5zM8 3v4M16 3v4M8 11h8M8 15h5"/></svg></i><strong><?= count($periods) ?></strong><span>Hasil ditampilkan</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5zM8 3v4M16 3v4"/><path d="m8 13 2.5 2.5L16 10"/></svg></i><strong><?= (int) ($periodStatusCounts['open'] ?? 0) ?></strong><span>Sedang dibuka</span></article>
                <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></i><strong><?= (int) ($periodStatusCounts['scheduled'] ?? 0) ?></strong><span>Terjadwal</span></article>
                <article><i class="summary-card-icon icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg></i><strong><?= $periodApplicationCount ?></strong><span>Total pelamar</span></article>
            </section>

            <section class="settings-card department-toolbar-card">
                <form class="vacancy-period-filter" method="get" action="<?= site_url('adminhrdmannakampus/sesi-lowongan') ?>">
                    <select name="vacancy_id"><option value="">Semua lowongan</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $selectedVacancyId === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select>
                    <select name="status"><option value="">Semua status</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <button type="submit">Terapkan</button><a href="<?= site_url('adminhrdmannakampus/sesi-lowongan') ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card vacancy-period-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5zM8 3v4M16 3v4M8 11h8M8 15h5"/></svg></span><div><h2>Daftar sesi rekrutmen</h2><p>Kandidat hanya dapat melamar satu kali pada setiap sesi.</p></div><span class="device-count"><?= count($periods) ?></span></div>
                <div class="department-table-wrap"><table class="department-table vacancy-period-table"><thead><tr><th class="period-order">No.</th><th>Lowongan</th><th>Sesi</th><th>Periode</th><th>Kebutuhan</th><th>Pelamar</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($periods === []): ?><tr><td colspan="8" class="department-empty">Belum ada sesi lowongan yang sesuai dengan filter.</td></tr><?php endif ?>
                    <?php foreach ($periods as $index => $period): ?>
                        <tr>
                            <td class="period-order"><?= $index + 1 ?></td>
                            <td><div class="department-name-cell"><strong><?= esc($period['vacancy_title']) ?></strong><code><?= esc($period['department_name'] ?: '-') ?></code></div></td>
                            <td><div class="period-name-cell"><strong><?= esc($period['period_name']) ?></strong><small><?= esc($period['period_code']) ?></small></div></td>
                            <td><div class="period-date-cell"><span><?= esc($dateLabel($period['opened_at'])) ?></span><small>sampai <?= esc($dateLabel($period['closed_at'])) ?></small></div></td>
                            <td><?= (int) $period['headcount'] ?> orang</td>
                            <td><strong><?= (int) $period['application_count'] ?></strong></td>
                            <td><span class="period-status period-status-<?= esc($period['status'], 'attr') ?>"><i></i><?= esc($statusLabels[$period['status']] ?? $period['status']) ?></span></td>
                            <td><div class="department-table-actions period-actions">
                                <?php if ($canManage): ?><button class="table-action-icon table-action-edit" type="button" data-admin-modal-open="period-edit-modal-<?= (int) $period['id'] ?>" aria-label="Edit sesi lowongan" title="Edit sesi lowongan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><?php endif ?>
                                <?php if ($canManage && (int) $period['application_count'] === 0): ?><form action="<?= site_url('adminhrdmannakampus/sesi-lowongan/' . $period['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus sesi lowongan ini?')"><?= csrf_field() ?><input type="hidden" name="vacancy_id" value="<?= (int) $period['vacancy_id'] ?>"><button class="department-delete-button table-action-icon table-action-delete" type="submit" aria-label="Hapus sesi lowongan" title="Hapus sesi lowongan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php endif ?>
                            </div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody></table></div>
            </section>
        </div>
    </main>
</div>

<?php if ($canManage): ?>
<dialog class="admin-modal" id="period-create-modal" aria-labelledby="period-create-title" <?= $openModal === 'create' ? 'data-auto-open' : '' ?>>
    <div class="admin-modal-panel vacancy-period-modal"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="period-create-title">Tambah sesi lowongan</h2><p>Buat periode baru agar kandidat dapat melamar kembali.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
        <form class="vacancy-period-form" action="<?= site_url('adminhrdmannakampus/sesi-lowongan') ?>" method="post"><?= csrf_field() ?>
            <label>Lowongan<select name="vacancy_id" required><option value="">Pilih lowongan</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= (int) $oldFor('create', 'vacancy_id', $selectedVacancyId) === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>
            <label>Nama sesi<input name="period_name" maxlength="150" value="<?= esc((string) $oldFor('create', 'period_name'), 'attr') ?>" placeholder="Contoh: Periode Oktober–Desember 2026" required></label>
            <label>Kode periode<input name="period_code" maxlength="80" value="<?= esc((string) $oldFor('create', 'period_code'), 'attr') ?>" placeholder="oktober-desember-2026"></label>
            <label>Jumlah kebutuhan<input type="number" name="headcount" min="1" max="9999" value="<?= esc((string) $oldFor('create', 'headcount', 1), 'attr') ?>" required></label>
            <label>Mulai dibuka<input type="datetime-local" name="opened_at" value="<?= esc((string) $oldFor('create', 'opened_at'), 'attr') ?>"></label>
            <label>Batas akhir<input type="datetime-local" name="closed_at" value="<?= esc((string) $oldFor('create', 'closed_at'), 'attr') ?>"></label>
            <?php if ($canPublish): ?><label>Status<select name="status"><?php foreach ($statusLabels as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= $oldFor('create', 'status', 'draft') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><?php else: ?><input type="hidden" name="status" value="draft"><?php endif ?>
            <label class="period-form-wide">Catatan internal<textarea name="notes" rows="3" maxlength="2000"><?= esc((string) $oldFor('create', 'notes')) ?></textarea></label>
            <div class="period-form-actions"><button type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan sesi</button></div>
        </form>
    </div>
</dialog>

<?php foreach ($periods as $period): $modal = 'edit-' . (int) $period['id']; ?>
<dialog class="admin-modal" id="period-edit-modal-<?= (int) $period['id'] ?>" aria-labelledby="period-edit-title-<?= (int) $period['id'] ?>" <?= $openModal === $modal ? 'data-auto-open' : '' ?>>
    <div class="admin-modal-panel vacancy-period-modal"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 20h4l11-11-4-4L4 16v4Z"/></svg></span><div><h2 id="period-edit-title-<?= (int) $period['id'] ?>">Edit sesi lowongan</h2><p><?= esc($period['vacancy_title']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
        <form class="vacancy-period-form" action="<?= site_url('adminhrdmannakampus/sesi-lowongan/' . $period['id']) ?>" method="post"><?= csrf_field() ?>
            <label>Lowongan<select name="vacancy_id" required><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= (int) $oldFor($modal, 'vacancy_id', $period['vacancy_id']) === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>
            <label>Nama sesi<input name="period_name" maxlength="150" value="<?= esc((string) $oldFor($modal, 'period_name', $period['period_name']), 'attr') ?>" required></label>
            <label>Kode periode<input name="period_code" maxlength="80" value="<?= esc((string) $oldFor($modal, 'period_code', $period['period_code']), 'attr') ?>" required></label>
            <label>Jumlah kebutuhan<input type="number" name="headcount" min="1" max="9999" value="<?= esc((string) $oldFor($modal, 'headcount', $period['headcount']), 'attr') ?>" required></label>
            <label>Mulai dibuka<input type="datetime-local" name="opened_at" value="<?= esc((string) $oldFor($modal, 'opened_at', $dateTimeLocal($period['opened_at'])), 'attr') ?>"></label>
            <label>Batas akhir<input type="datetime-local" name="closed_at" value="<?= esc((string) $oldFor($modal, 'closed_at', $dateTimeLocal($period['closed_at'])), 'attr') ?>"></label>
            <?php if ($canPublish): ?><label>Status<select name="status"><?php foreach ($statusLabels as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= $oldFor($modal, 'status', $period['status']) === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><?php else: ?><input type="hidden" name="status" value="<?= esc($period['status'], 'attr') ?>"><?php endif ?>
            <label class="period-form-wide">Catatan internal<textarea name="notes" rows="3" maxlength="2000"><?= esc((string) $oldFor($modal, 'notes', $period['notes'])) ?></textarea></label>
            <div class="period-form-actions"><button type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div>
        </form>
    </div>
</dialog>
<?php endforeach ?>
<?php endif ?>

<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
