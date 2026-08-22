<?php
$candidateBaseUrl = site_url('adminhrdmannakampus/kandidat');
$candidateTeamUrl = $candidateBaseUrl . ($selectedTeamId > 0 ? '?team_id=' . $selectedTeamId : '');
$progressLabels = ['previous' => 'Sebelumnya', 'current' => 'Posisi saat ini', 'next' => 'Selanjutnya', 'upcoming' => 'Akan datang'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Pelamar <?= esc($selectedTeam['name'] ?? 'Divisi') ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=46">
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
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading">
                <div><span class="login-eyebrow">Pelamar Divisi</span><h1><?= $selectedTeam ? 'Pelamar ' . esc($selectedTeam['name']) : 'Pelamar Divisi' ?></h1><p>Pelamar yang telah dipilih dan menjadi tanggung jawab divisi ini.</p></div>
                <div class="candidate-heading-actions"><a class="talent-pool-page-link" href="<?= site_url('adminhrdmannakampus/talent-pool' . ($selectedTeamId > 0 ? '?team_id=' . $selectedTeamId : '')) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v15l-7-3-7 3V5Z"/><path d="M9 9h6M9 12h6"/></svg>Talent Pool</a><?php if (! $canUpdateStatus): ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?></div>
            </section>

            <?php if ($selectedTeam === null): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert">Akun Anda belum memiliki divisi HRD. Hubungi pengelola Tim HRD agar dapat melihat dan memproses pelamar divisi.</div><?php endif ?>

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
                    <input class="candidate-age-filter" type="number" name="age" value="<?= $filters['age'] > 0 ? (int) $filters['age'] : '' ?>" min="15" max="80" inputmode="numeric" placeholder="Umur">
                    <select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select>
                    <select name="vacancy_period_id"><option value="">Semua sesi</option><?php foreach ($periods as $period): ?><option value="<?= (int) $period['id'] ?>" <?= $filters['vacancy_period_id'] === (int) $period['id'] ? 'selected' : '' ?>><?= esc($period['vacancy_title'] . ' — ' . $period['period_name']) ?></option><?php endforeach ?></select>
                    <select name="department_id"><option value="">Semua departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= $filters['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select>
                    <select name="status"><option value="">Semua tahapan</option><?php foreach ($statusOptions as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['status'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <select name="rejection_stage_code"><option value="">Semua tahap gugur</option><?php foreach ($rejectionStageOptions as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['rejection_stage_code'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <button type="submit">Terapkan</button><a href="<?= $candidateTeamUrl ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card candidate-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></span><div><h2>Pipeline <?= esc($selectedTeam['name'] ?? 'divisi') ?></h2><p>Hanya pelamar yang sudah dipilih untuk divisi ini.</p></div><span class="device-count"><?= count($applications) ?></span></div>
                <div class="department-table-wrap">
                    <table class="department-table candidate-table">
                        <thead><tr><th class="candidate-order">No.</th><th>Kandidat</th><th>Umur</th><th>No. Telepon / WA</th><th>Posisi</th><th>Tahap saat ini</th><th>Tahap gugur</th><th>Tanggal daftar</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if ($applications === []): ?><tr><td colspan="9" class="department-empty">Belum ada pelamar pada divisi ini.</td></tr><?php endif ?>
                            <?php foreach ($applications as $index => $application): ?>
                                <tr>
                                    <td class="candidate-order"><?= $index + 1 ?></td>
                                    <td><div class="report-applicant"><strong><?= esc($application['full_name']) ?></strong><a href="mailto:<?= esc($application['email'], 'attr') ?>"><?= esc($application['email']) ?></a></div></td>
                                    <td><?= $application['age'] === null ? '-' : (int) $application['age'] . ' tahun' ?></td>
                                    <td><?php if ($application['whatsapp_number'] !== ''): ?><a class="candidate-whatsapp-link" href="https://wa.me/<?= esc($application['whatsapp_number'], 'attr') ?>" target="_blank" rel="noopener noreferrer" aria-label="Hubungi <?= esc($application['full_name'], 'attr') ?> melalui WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L0 24l6.5-1.7c1.7.9 3.6 1.4 5.6 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.2-3.4-8.4Zm-8.4 18.2c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 1 1 8.4 4.7Zm5.4-7.3c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.2-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-1.7-.8-2.8-1.5-3.9-3.4-.3-.5.3-.5.8-1.5.1-.2 0-.4 0-.6l-.9-2.1c-.2-.5-.5-.5-.7-.5H8c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.9 5.2 2.2.9 3.1 1 4.2.8.7-.1 1.7-.7 1.9-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3l-1.4-.7Z"/></svg><span><?= esc($application['phone']) ?></span></a><?php else: ?>-<?php endif ?></td>
                                    <td><div class="department-name-cell"><strong><?= esc($application['vacancy_title']) ?></strong><code><?= esc($application['period_name'] . ' · ' . $application['department_name']) ?></code><small><?= esc($application['process_template_name'] ?: 'Template belum ditentukan') ?></small></div></td>
                                    <td><span class="candidate-stage-pill" style="--candidate-color: <?= esc($application['stage_color'], 'attr') ?>"><i></i><?= esc($application['status_label']) ?></span></td>
                                    <td><?php if (! empty($application['rejected_stage_name'])): ?><span class="candidate-rejected-stage"><strong><?= $application['rejected_stage_order'] !== null ? 'Tahap ' . (int) $application['rejected_stage_order'] : 'Tahap' ?></strong><?= esc($application['rejected_stage_name']) ?><small><?= esc($application['rejection_reason_title']) ?></small></span><?php else: ?><span class="candidate-not-rejected">—</span><?php endif ?></td>
                                    <td class="report-date"><?= esc(date('d/m/Y', strtotime($application['submitted_at']))) ?><small><?= esc(date('H:i', strtotime($application['submitted_at']))) ?></small></td>
                                    <td><div class="candidate-table-actions"><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $application['applicant_id']) ?>">Detail</a><?php if ($canUpdateStatus && $application['available_stages'] !== []): ?><button class="candidate-process-link" type="button" data-admin-modal-open="candidate-stage-modal-<?= (int) $application['id'] ?>">Ubah tahap</button><?php endif ?><?php if (! empty($application['talent_pool_id'])): ?><a class="candidate-talent-saved" href="<?= site_url('adminhrdmannakampus/talent-pool?team_id=' . $selectedTeamId) ?>">Cadangan</a><?php elseif ($canUpdateStatus): ?><button class="candidate-talent-trigger" type="button" data-admin-modal-open="candidate-talent-modal-<?= (int) $application['id'] ?>">Simpan cadangan</button><?php endif ?><?php if ($canCancelAssignment): ?><form action="<?= site_url('adminhrdmannakampus/kandidat/pelamar/' . $application['applicant_id'] . '/batal-pilih') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="team_id" value="<?= $selectedTeamId ?>"><button class="candidate-cancel-assignment" type="submit" data-confirm-title="Batalkan pilihan pelamar?" data-confirm="<?= esc($application['full_name'], 'attr') ?> akan dikeluarkan dari <?= esc($selectedTeam['name'], 'attr') ?>." data-confirm-details="Divisi Pusat menjadi Belum dipilih.|Data Cadangan dan seluruh riwayat Talent Pool dihapus permanen.|Seluruh Alur Rekrutmen direset kembali ke Lamaran Baru." data-confirm-button="Ya, batalkan pilihan" data-cancel-button="Jangan batalkan" data-confirm-color="#dc2626">Batal pilih</button></form><?php endif ?></div></td>
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
                    <?php if ($application['process_steps'] !== []): ?>
                        <div class="candidate-process-progress">
                            <div class="candidate-process-progress-heading">
                                <div><strong>Alur rekrutmen</strong><small><?= esc($application['process_template_name'] ?: 'Template proses lowongan') ?></small></div>
                                <div class="candidate-process-legend" aria-label="Keterangan tahap"><span class="previous"><i></i>Sebelumnya</span><span class="current"><i></i>Saat ini</span><span class="next"><i></i>Selanjutnya</span></div>
                            </div>
                            <div class="candidate-process-progress-scroll">
                                <ol class="candidate-process-stepper" style="--candidate-step-count: <?= count($application['process_steps']) ?>" aria-label="Urutan tahapan rekrutmen">
                                    <?php foreach ($application['process_steps'] as $step): $progressState = (string) $step['progress_state']; ?>
                                        <li class="is-<?= esc($progressState, 'attr') ?>" <?= $progressState === 'current' ? 'aria-current="step"' : '' ?>>
                                            <span class="candidate-process-marker"><?= (int) $step['display_order'] ?></span>
                                            <strong><?= esc($step['name']) ?></strong>
                                            <small><?= esc($progressLabels[$progressState] ?? 'Akan datang') ?></small>
                                        </li>
                                    <?php endforeach ?>
                                </ol>
                            </div>
                        </div>
                    <?php endif ?>
                    <label>Tahap berikutnya<select name="stage" required data-candidate-stage-select><option value="">Pilih tahapan</option><?php foreach ($application['available_stages'] as $stage): ?><option value="<?= esc($stage['code'], 'attr') ?>"><?= esc($stage['name']) ?></option><?php endforeach ?></select><small>Hanya tahap berikutnya sesuai template lowongan yang dapat dipilih.</small></label>
                    <div class="candidate-rejection-context" data-candidate-rejection-context hidden><span>Keputusan akan tercatat sebagai</span><strong>Gugur di <?= esc($application['rejection_stage_label']) ?></strong><small>Nama dan urutan tahap disimpan permanen sebagai histori.</small></div>
                    <label data-candidate-rejection-reason hidden>Alasan gugur<select name="rejection_template_id"><option value="">Pilih alasan gugur</option><?php foreach ($rejectionTemplates as $template): ?><option value="<?= (int) $template['id'] ?>"><?= esc($template['title']) ?></option><?php endforeach ?></select></label>
                    <label class="candidate-process-note">Catatan internal<textarea name="notes" rows="4" maxlength="2000" placeholder="Opsional, tersimpan pada riwayat status"></textarea></label>
                    <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm="Ubah tahapan kandidat ini?">Simpan tahapan</button></div>
                </form>
            </div>
        </dialog>
    <?php endforeach ?>
    <?php foreach ($applications as $application): if (! empty($application['talent_pool_id'])) { continue; } ?>
        <dialog class="admin-modal talent-pool-save-modal" id="candidate-talent-modal-<?= (int) $application['id'] ?>" aria-labelledby="candidate-talent-title-<?= (int) $application['id'] ?>">
            <div class="admin-modal-panel">
                <div class="settings-card-heading admin-modal-heading">
                    <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v15l-7-3-7 3V5Z"/><path d="M9 9h6M9 12h6"/></svg></span>
                    <div><h2 id="candidate-talent-title-<?= (int) $application['id'] ?>">Simpan <?= esc($application['full_name']) ?> sebagai cadangan</h2><p><?= esc($application['vacancy_title']) ?> · <?= esc($application['application_number']) ?></p></div>
                    <button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button>
                </div>
                <form class="talent-pool-form" action="<?= site_url('adminhrdmannakampus/talent-pool/simpan/' . $application['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <label>Posisi rekomendasi<input type="text" name="recommended_position" value="<?= esc($application['vacancy_title'], 'attr') ?>" minlength="3" maxlength="150" required></label>
                    <label>Departemen rekomendasi<select name="target_department_id" required><option value="">Pilih departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $application['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select></label>
                    <label>Prioritas<select name="priority" required><option value="high">Tinggi</option><option value="normal" selected>Normal</option><option value="low">Rendah</option></select></label>
                    <label>Tersedia mulai<input type="date" name="available_from"><small class="talent-field-help">Tanggal kandidat siap bekerja atau siap mengikuti proses rekrutmen kembali. Boleh dikosongkan jika belum diketahui.</small></label>
                    <label>Tindak lanjut<input type="date" name="follow_up_at"><small class="talent-field-help">Tanggal pengingat bagi HRD untuk menghubungi kandidat kembali. Kandidat akan ditandai jika tanggalnya sudah jatuh tempo.</small></label>
                    <label class="talent-pool-wide">Alasan disimpan<textarea name="reason" rows="3" minlength="5" maxlength="1000" required placeholder="Contoh: posisi sudah terisi, tetapi kandidat memenuhi kualifikasi"></textarea></label>
                    <label class="talent-pool-wide">Kelebihan kandidat<textarea name="strength_notes" rows="3" maxlength="4000" placeholder="Keahlian, pengalaman, atau karakter yang menonjol"></textarea></label>
                    <label class="talent-pool-wide">Catatan internal<textarea name="internal_notes" rows="3" maxlength="4000" placeholder="Catatan hanya untuk tim HRD"></textarea></label>
                    <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm="Simpan pelamar ini ke Talent Pool?">Simpan Talent Pool</button></div>
                </form>
            </div>
        </dialog>
    <?php endforeach ?>
<?php endif ?>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=8" defer></script>
</body>
</html>
