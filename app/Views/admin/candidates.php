<?php
$screeningLabels = ['passed' => 'Lolos', 'failed' => 'Tidak lolos', 'pending' => 'Belum dinilai'];
$candidateBaseUrl = site_url('adminhrdmannakampus/kandidat');
$candidateTeamUrl = $candidateBaseUrl . ($selectedTeamId > 0 ? '?team_id=' . $selectedTeamId : '');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Pelamar <?= esc($selectedTeam['name'] ?? 'Divisi') ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=32">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'candidates']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Recruitment Pipeline</span><strong>Pelamar <?= esc($selectedTeam['name'] ?? 'Divisi') ?></strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/list-pelamar') ?>">List pelamar baru</a>
        </header>

        <div class="admin-content candidates-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading">
                <div><span class="login-eyebrow">Pelamar Divisi</span><h1><?= $selectedTeam ? 'Pelamar ' . esc($selectedTeam['name']) : 'Pelamar Divisi' ?></h1><p>Pelamar yang telah dipilih dan menjadi tanggung jawab divisi ini.</p></div>
                <?php if (! $canUpdateStatus): ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
            </section>

            <?php if ($selectedTeam === null): ?><div class="admin-alert admin-alert-error dashboard-alert" role="alert">Akun Anda belum memiliki divisi HRD. Hubungi pengelola Tim HRD agar dapat melihat dan memproses pelamar divisi.</div><?php endif ?>

            <section class="access-summary candidate-summary">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg></i><strong><?= (int) $summary['total'] ?></strong><span>Hasil ditampilkan</span></article>
                <article><i class="summary-card-icon icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M14 7l5 5-5 5"/></svg></i><strong><?= (int) $summary['active'] ?></strong><span>Dalam proses</span></article>
                <article><i class="summary-card-icon icon-red" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg></i><strong><?= (int) $summary['overdue'] ?></strong><span>Melewati SLA</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= (int) $summary['accepted'] ?></strong><span>Diterima</span></article>
            </section>

            <section class="settings-card department-toolbar-card">
                <form class="candidate-filter-form <?= $canManageTeams ? 'candidate-filter-with-team' : '' ?>" action="<?= $candidateBaseUrl ?>" method="get">
                    <?php if ($canManageTeams): ?><select name="team_id"><option value="">Pilih divisi</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= $selectedTeamId === (int) $team['id'] ? 'selected' : '' ?>><?= esc($team['name']) ?></option><?php endforeach ?></select><?php endif ?>
                    <input type="search" name="keyword" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Cari nama, email, WA, atau nomor lamaran">
                    <select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select>
                    <select name="vacancy_period_id"><option value="">Semua sesi</option><?php foreach ($periods as $period): ?><option value="<?= (int) $period['id'] ?>" <?= $filters['vacancy_period_id'] === (int) $period['id'] ? 'selected' : '' ?>><?= esc($period['vacancy_title'] . ' — ' . $period['period_name']) ?></option><?php endforeach ?></select>
                    <select name="department_id"><option value="">Semua departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= $filters['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select>
                    <select name="status"><option value="">Semua tahapan</option><?php foreach ($statusOptions as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['status'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <button type="submit">Terapkan</button><a href="<?= $candidateTeamUrl ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card candidate-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></span><div><h2>Pipeline <?= esc($selectedTeam['name'] ?? 'divisi') ?></h2><p>Hanya pelamar yang sudah dipilih untuk divisi ini.</p></div><span class="device-count"><?= count($applications) ?></span></div>
                <div class="department-table-wrap">
                    <table class="department-table candidate-table">
                        <thead><tr><th class="candidate-order">No.</th><th>Kandidat</th><th>Posisi</th><th>Screening</th><th>Tahap saat ini</th><th>Lama di tahap</th><th>Tanggal daftar</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if ($applications === []): ?><tr><td colspan="8" class="department-empty">Belum ada pelamar pada divisi ini.</td></tr><?php endif ?>
                            <?php foreach ($applications as $index => $application): $screening = (string) ($application['screening_status'] ?: 'pending'); ?>
                                <tr>
                                    <td class="candidate-order"><?= $index + 1 ?></td>
                                    <td><div class="report-applicant"><strong><?= esc($application['full_name']) ?></strong><a href="mailto:<?= esc($application['email'], 'attr') ?>"><?= esc($application['email']) ?></a><small><?= esc($application['phone']) ?></small></div></td>
                                    <td><div class="department-name-cell"><strong><?= esc($application['vacancy_title']) ?></strong><code><?= esc($application['period_name'] . ' · ' . $application['department_name']) ?></code><small><?= esc($application['process_template_name'] ?: 'Template belum ditentukan') ?></small><small class="candidate-assignment-meta">Dipilih <?= esc($application['assigned_by_name'] ?: '-') ?><?= $application['assigned_at'] ? ' · ' . esc(date('d/m/Y H:i', strtotime($application['assigned_at']))) : '' ?></small></div></td>
                                    <td><span class="report-screening screening-<?= esc($screening, 'attr') ?>"><?= esc($screeningLabels[$screening] ?? 'Belum dinilai') ?></span><small class="report-score"><?= $application['screening_score'] !== null ? esc(number_format((float) $application['screening_score'], 2, ',', '.')) : '-' ?></small></td>
                                    <td><span class="candidate-stage-pill" style="--candidate-color: <?= esc($application['stage_color'], 'attr') ?>"><i></i><?= esc($application['status_label']) ?></span></td>
                                    <td><span class="candidate-stage-age <?= $application['is_overdue'] ? 'overdue' : '' ?>"><?= (int) $application['days_in_stage'] ?> hari</span><?php if ((int) $application['sla_days'] > 0): ?><small class="candidate-sla">SLA <?= (int) $application['sla_days'] ?> hari</small><?php endif ?></td>
                                    <td class="report-date"><?= esc(date('d/m/Y', strtotime($application['submitted_at']))) ?><small><?= esc(date('H:i', strtotime($application['submitted_at']))) ?></small></td>
                                    <td><div class="candidate-table-actions"><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $application['applicant_id']) ?>">Detail</a><?php if ($canUpdateStatus && $application['available_stages'] !== []): ?><button class="candidate-process-link" type="button" data-admin-modal-open="candidate-stage-modal-<?= (int) $application['id'] ?>">Ubah tahap</button><?php endif ?></div></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php if ($canUpdateStatus): ?>
    <?php foreach ($applications as $application): if ($application['available_stages'] === []) { continue; } ?>
        <dialog class="admin-modal candidate-stage-modal" id="candidate-stage-modal-<?= (int) $application['id'] ?>" aria-labelledby="candidate-stage-title-<?= (int) $application['id'] ?>">
            <div class="admin-modal-panel">
                <div class="settings-card-heading admin-modal-heading">
                    <span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg></span>
                    <div><h2 id="candidate-stage-title-<?= (int) $application['id'] ?>">Ubah tahap <?= esc($application['full_name']) ?></h2><p><?= esc($application['vacancy_title']) ?> · <?= esc($application['application_number']) ?></p></div>
                    <button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button>
                </div>
                <form class="candidate-process-form candidate-modal-form" action="<?= site_url('adminhrdmannakampus/kandidat/lamaran/' . $application['id'] . '/tahap') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="team_id" value="<?= $selectedTeamId ?>">
                    <div class="candidate-current-stage"><span>Tahap saat ini</span><strong><?= esc($application['status_label']) ?></strong></div>
                    <label>Tahap berikutnya<select name="stage" required><option value="">Pilih tahapan</option><?php foreach ($application['available_stages'] as $stage): ?><option value="<?= esc($stage['code'], 'attr') ?>"><?= esc($stage['name']) ?></option><?php endforeach ?></select><small>Hanya tahap berikutnya sesuai template lowongan yang dapat dipilih.</small></label>
                    <label>Alasan penolakan<select name="rejection_template_id"><option value="">Wajib jika memilih Ditolak</option><?php foreach ($rejectionTemplates as $template): ?><option value="<?= (int) $template['id'] ?>"><?= esc($template['title']) ?></option><?php endforeach ?></select></label>
                    <label class="candidate-process-note">Catatan internal<textarea name="notes" rows="4" maxlength="2000" placeholder="Opsional, tersimpan pada riwayat status"></textarea></label>
                    <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm="Ubah tahapan kandidat ini?">Simpan tahapan</button></div>
                </form>
            </div>
        </dialog>
    <?php endforeach ?>
<?php endif ?>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
