<?php
$date = static fn (?string $value, string $format = 'd/m/Y H:i'): string => $value && strtotime($value) !== false ? date($format, strtotime($value)) : '-';
$statusLabels = ['active' => 'Aktif', 'permanent' => 'Permanen', 'expired' => 'Berakhir', 'revoked' => 'Dicabut'];
$historyLabels = ['blacklisted' => 'Ditambahkan', 'updated' => 'Diperbarui', 'revoked' => 'Dicabut', 'reactivated' => 'Diaktifkan kembali'];
$paginationQuery = array_filter($filters, static fn ($value): bool => $value !== '');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Blacklist Pelamar | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=67">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'applicant-blacklist']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button><div><span>Applicant Control</span><strong>Blacklist Pelamar</strong></div><a class="view-career-link" href="<?= site_url('adminhrdmannakampus/list-pelamar') ?>">Pilih dari list pelamar</a></header>

        <div class="admin-content blacklist-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading blacklist-heading">
                <div><span class="login-eyebrow">Applicant Restriction</span><h1>Blacklist Pelamar</h1><p>Pelamar dengan blacklist aktif tidak dapat mengirim lamaran ke lowongan mana pun.</p></div>
                <?php if ($canManage): ?><a class="new-user-jump blacklist-pick-link" href="<?= site_url('adminhrdmannakampus/list-pelamar') ?>">+ Pilih pelamar</a><?php else: ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
            </section>

            <section class="access-summary candidate-summary" aria-label="Ringkasan blacklist">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5"/></svg></i><strong><?= (int) $summary['total'] ?></strong><span>Total tercatat</span></article>
                <article><i class="summary-card-icon icon-red" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></i><strong><?= (int) $summary['active'] ?></strong><span>Aktif sementara</span></article>
                <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 7h10v10H7z"/><path d="m7 7 10 10M17 7 7 17"/></svg></i><strong><?= (int) $summary['permanent'] ?></strong><span>Aktif permanen</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= (int) $summary['ended'] ?></strong><span>Berakhir/dicabut</span></article>
            </section>

            <section class="settings-card department-toolbar-card">
                <form class="blacklist-filter-form" action="<?= site_url('adminhrdmannakampus/blacklist-pelamar') ?>" method="get">
                    <input type="search" name="keyword" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Cari nama, email, telepon, atau alasan">
                    <select name="status"><option value="">Semua status</option><?php foreach ($statusLabels as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['status'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <button type="submit">Terapkan</button><a href="<?= site_url('adminhrdmannakampus/blacklist-pelamar') ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card blacklist-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></span><div><h2>Daftar blacklist</h2><p>Data tidak dihapus saat masa berlaku berakhir atau blacklist dicabut.</p></div><span class="device-count"><?= count($blacklists) ?> / <?= (int) $pagination['total'] ?></span></div>
                <div class="department-table-wrap"><table class="department-table blacklist-table"><thead><tr><th>No.</th><th>Pelamar</th><th>Alasan</th><th>Masa berlaku</th><th>Lowongan yang dilamar</th><th>Dicatat oleh</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($blacklists === []): ?><tr><td colspan="7" class="department-empty">Data blacklist tidak ditemukan.</td></tr><?php endif ?>
                    <?php foreach ($blacklists as $index => $blacklist): $isActive = in_array($blacklist['computed_status'], ['active', 'permanent'], true); ?>
                        <tr>
                            <td><?= (int) $pagination['offset'] + $index + 1 ?></td>
                            <td><div class="report-applicant"><strong><?= esc($blacklist['full_name']) ?></strong><a href="mailto:<?= esc($blacklist['email'], 'attr') ?>"><?= esc($blacklist['email']) ?></a><small><?= esc($blacklist['phone'] ?: '-') ?></small></div></td>
                            <td class="blacklist-reason-cell"><strong><?= esc($blacklist['reason']) ?></strong><?php if (trim((string) $blacklist['internal_notes']) !== ''): ?><small><?= esc($blacklist['internal_notes']) ?></small><?php endif ?></td>
                            <td><div class="blacklist-period"><strong><?= (int) $blacklist['is_permanent'] === 1 ? 'Permanen' : esc($date($blacklist['ends_at'], 'd M Y')) ?></strong><small>Mulai <?= esc($date($blacklist['starts_at'], 'd M Y')) ?></small></div></td>
                            <td><div class="blacklist-vacancy-list"><?php if ($blacklist['applied_vacancies'] === []): ?><span>Belum ada riwayat lowongan</span><?php endif ?><?php foreach ($blacklist['applied_vacancies'] as $vacancy): ?><div><strong><?= esc($vacancy['vacancy_title']) ?></strong><small><?= esc($vacancy['application_number']) ?> · <?= esc($date($vacancy['submitted_at'], 'd M Y')) ?></small></div><?php endforeach ?></div></td>
                            <td><div class="blacklist-author"><strong><?= esc($blacklist['updated_by_name'] ?: $blacklist['created_by_name'] ?: 'Sistem') ?></strong><small><?= esc($date($blacklist['updated_at'])) ?></small></div></td>
                            <td><div class="candidate-table-actions blacklist-actions"><?php if ($canViewCandidate): ?><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $blacklist['applicant_id']) ?>" target="_blank" rel="noopener noreferrer">Detail</a><?php endif ?><button class="blacklist-history-trigger" type="button" data-admin-modal-open="blacklist-history-<?= (int) $blacklist['id'] ?>">Riwayat</button><?php if ($canManage): ?><button class="candidate-process-link" type="button" data-admin-modal-open="blacklist-edit-<?= (int) $blacklist['id'] ?>"><?= $isActive ? 'Ubah' : 'Aktifkan kembali' ?></button><?php if ($isActive): ?><button class="candidate-cancel-assignment" type="button" data-admin-modal-open="blacklist-revoke-<?= (int) $blacklist['id'] ?>">Cabut</button><?php endif ?><?php endif ?></div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody></table></div>
                <?= view('admin/partials/pagination', ['pagination' => $pagination, 'baseUrl' => site_url('adminhrdmannakampus/blacklist-pelamar'), 'query' => $paginationQuery, 'unit' => 'data blacklist']) ?>
            </section>
        </div>
    <?= view('admin/partials/footer') ?>
    </main>
</div>

<?php foreach ($blacklists as $blacklist): $isActive = in_array($blacklist['computed_status'], ['active', 'permanent'], true); $histories = $historiesByBlacklist[(int) $blacklist['id']] ?? []; ?>
<dialog class="admin-modal blacklist-history-modal" id="blacklist-history-<?= (int) $blacklist['id'] ?>" aria-labelledby="blacklist-history-title-<?= (int) $blacklist['id'] ?>"><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span><div><h2 id="blacklist-history-title-<?= (int) $blacklist['id'] ?>">Riwayat <?= esc($blacklist['full_name']) ?></h2><p>Audit perubahan blacklist pelamar.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><div class="blacklist-history-list"><?php if ($histories === []): ?><p class="candidate-empty">Belum ada histori.</p><?php endif ?><?php foreach ($histories as $history): ?><article><i></i><div><strong><?= esc($historyLabels[$history['action']] ?? $history['action']) ?></strong><p><?= esc($history['action_notes'] ?: '-') ?></p><small><?= esc($date($history['created_at'])) ?> · <?= esc($history['changed_by_name'] ?: 'Sistem') ?></small></div></article><?php endforeach ?></div></div></dialog>

<?php if ($canManage): ?>
<dialog class="admin-modal blacklist-form-modal" id="blacklist-edit-<?= (int) $blacklist['id'] ?>" aria-labelledby="blacklist-edit-title-<?= (int) $blacklist['id'] ?>"><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></span><div><h2 id="blacklist-edit-title-<?= (int) $blacklist['id'] ?>"><?= $isActive ? 'Ubah blacklist' : 'Aktifkan kembali' ?> <?= esc($blacklist['full_name']) ?></h2><p>Pemblokiran berlaku untuk seluruh lowongan.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="blacklist-form" action="<?= $isActive ? site_url('adminhrdmannakampus/blacklist-pelamar/' . $blacklist['id']) : site_url('adminhrdmannakampus/blacklist-pelamar/pelamar/' . $blacklist['applicant_id']) ?>" method="post"><?= csrf_field() ?><label>Alasan blacklist<textarea name="reason" rows="3" minlength="5" maxlength="1000" required><?= esc($blacklist['reason']) ?></textarea><small>Catatan ini hanya terlihat oleh tim HRD.</small></label><label>Catatan internal<textarea name="internal_notes" rows="3" maxlength="5000"><?= esc($blacklist['internal_notes']) ?></textarea></label><label>Masa berlaku<select name="duration" required data-blacklist-duration><option value="">Pilih masa berlaku</option><?php foreach ($durationLabels as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $code === ((int) $blacklist['is_permanent'] === 1 ? 'permanent' : 'custom') ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><label data-blacklist-custom-date <?= (int) $blacklist['is_permanent'] === 1 ? 'hidden' : '' ?>>Berakhir pada<input type="date" name="ends_on" value="<?= $blacklist['ends_at'] ? esc(date('Y-m-d', strtotime($blacklist['ends_at'])), 'attr') : '' ?>" min="<?= date('Y-m-d') ?>"></label><div class="blacklist-form-warning"><strong>Konsekuensi</strong><span>Pelamar tidak dapat mendaftar ke lowongan mana pun selama blacklist aktif.</span></div><div class="department-modal-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="<?= $isActive ? 'Simpan perubahan blacklist?' : 'Aktifkan kembali blacklist?' ?>" data-confirm="Pemblokiran akan berlaku untuk seluruh pendaftaran lowongan." data-confirm-button="Ya, simpan" data-confirm-color="#dc2626"><?= $isActive ? 'Simpan perubahan' : 'Aktifkan blacklist' ?></button></div></form></div></dialog>

<?php if ($isActive): ?><dialog class="admin-modal blacklist-form-modal" id="blacklist-revoke-<?= (int) $blacklist['id'] ?>" aria-labelledby="blacklist-revoke-title-<?= (int) $blacklist['id'] ?>"><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><div><h2 id="blacklist-revoke-title-<?= (int) $blacklist['id'] ?>">Cabut blacklist <?= esc($blacklist['full_name']) ?></h2><p>Pelamar akan dapat mendaftar kembali.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="blacklist-form" action="<?= site_url('adminhrdmannakampus/blacklist-pelamar/' . $blacklist['id'] . '/cabut') ?>" method="post"><?= csrf_field() ?><label>Alasan pencabutan<textarea name="revocation_reason" rows="4" minlength="5" maxlength="1000" required placeholder="Jelaskan alasan blacklist dicabut"></textarea></label><div class="department-modal-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="Cabut blacklist pelamar?" data-confirm="<?= esc($blacklist['full_name'], 'attr') ?> akan dapat mendaftar ke seluruh lowongan kembali." data-confirm-button="Ya, cabut blacklist">Cabut blacklist</button></div></form></div></dialog><?php endif ?>
<?php endif ?>
<?php endforeach ?>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=9" defer></script>
</body>
</html>
