<?php
$talentPoolBaseUrl = site_url('adminhrdmannakampus/talent-pool');
$talentPoolTeamUrl = $talentPoolBaseUrl . ($selectedTeamId > 0 ? '?team_id=' . $selectedTeamId : '');
$date = static fn (?string $value, string $format = 'd M Y'): string => $value && strtotime($value) !== false ? date($format, strtotime($value)) : '-';
$historyActions = [
    'saved' => 'Disimpan ke Talent Pool',
    'updated' => 'Data diperbarui',
    'status_changed' => 'Status diperbarui',
    'contacted_for_vacancy' => 'Dihubungi untuk lowongan',
    'invited_to_vacancy' => 'Lamaran baru dari Talent Pool',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Talent Pool <?= esc($selectedTeam['name'] ?? 'Divisi') ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=66">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'candidates']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Candidate Reserve</span><strong>Talent Pool <?= esc($selectedTeam['name'] ?? 'Divisi') ?></strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/kandidat' . ($selectedTeamId > 0 ? '?team_id=' . $selectedTeamId : '')) ?>">Kembali ke Lamaran Divisi</a>
        </header>

        <div class="admin-content talent-pool-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome talent-pool-heading">
                <div><span class="login-eyebrow">Kandidat Cadangan</span><h1>Talent Pool <?= esc($selectedTeam['name'] ?? 'Divisi') ?></h1><p>Kandidat potensial yang dapat dihubungi kembali saat tersedia kebutuhan staf baru.</p></div>
                <a class="talent-pool-back-link" href="<?= site_url('adminhrdmannakampus/kandidat' . ($selectedTeamId > 0 ? '?team_id=' . $selectedTeamId : '')) ?>">+ Tambah dari Lamaran Divisi</a>
            </section>

            <section class="access-summary talent-pool-summary">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 5h14v15l-7-3-7 3V5Z"/></svg></i><strong><?= (int) $summary['total'] ?></strong><span>Hasil ditampilkan</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= (int) $summary['available'] ?></strong><span>Kandidat tersedia</span></article>
                <article><i class="summary-card-icon icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3v18M7 8h10M7 16h10"/></svg></i><strong><?= (int) $summary['high'] ?></strong><span>Prioritas tinggi</span></article>
                <article><i class="summary-card-icon icon-red" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg></i><strong><?= (int) $summary['due'] ?></strong><span>Perlu ditindaklanjuti</span></article>
            </section>

            <section class="settings-card talent-pool-filter-card">
                <form class="talent-pool-filter <?= $canManageTeams ? 'talent-pool-filter-with-team' : '' ?>" action="<?= $talentPoolBaseUrl ?>" method="get">
                    <?php if ($canManageTeams): ?><select name="team_id"><option value="">Pilih divisi</option><?php foreach ($teams as $team): ?><option value="<?= (int) $team['id'] ?>" <?= $selectedTeamId === (int) $team['id'] ? 'selected' : '' ?>><?= esc($team['name']) ?></option><?php endforeach ?></select><?php endif ?>
                    <input type="search" name="keyword" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Cari nama, email, WA, atau posisi rekomendasi">
                    <select name="pool_status"><option value="">Semua status</option><?php foreach ($statuses as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['pool_status'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <select name="priority"><option value="">Semua prioritas</option><?php foreach ($priorities as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['priority'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <select name="department_id"><option value="">Semua departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= $filters['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select>
                    <button type="submit">Terapkan</button><a href="<?= $talentPoolTeamUrl ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card talent-pool-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></span><div><h2>Daftar kandidat cadangan</h2><p>Informasi utama ditampilkan ringkas. Gunakan tombol Detail untuk melihat catatan lengkap.</p></div><span class="device-count"><?= count($candidates) ?></span></div>
                <div class="department-table-wrap talent-pool-table-wrap">
                    <table class="department-table talent-pool-table">
                        <thead><tr><th>No.</th><th>Kandidat</th><th>Rekomendasi</th><th>Status</th><th>Sumber lamaran</th><th>Ketersediaan &amp; tindak lanjut</th><th>WhatsApp</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if ($candidates === []): ?><tr><td colspan="8" class="department-empty">Belum ada kandidat cadangan. Tambahkan melalui halaman Lamaran Divisi.</td></tr><?php endif ?>
                            <?php foreach ($candidates as $index => $candidate): ?>
                                <tr class="<?= $candidate['is_follow_up_due'] ? 'talent-row-due' : '' ?>">
                                    <td class="talent-table-number"><?= $index + 1 ?></td>
                                    <td><div class="talent-table-person"><span class="talent-table-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $candidate['full_name'], 0, 1))) ?></span><div><a class="talent-table-name" href="<?= site_url('adminhrdmannakampus/pelamar/' . $candidate['applicant_id']) ?>"><?= esc($candidate['full_name']) ?></a><a class="talent-table-email" href="mailto:<?= esc($candidate['email'], 'attr') ?>"><?= esc($candidate['email']) ?></a><small><?= esc($candidate['phone']) ?><?= $candidate['age'] !== null ? ' · ' . (int) $candidate['age'] . ' tahun' : '' ?></small></div></div></td>
                                    <td><div class="talent-table-copy"><strong><?= esc($candidate['recommended_position']) ?></strong><small><?= esc($candidate['target_department_name'] ?: 'Departemen belum ditentukan') ?></small><span class="talent-priority priority-<?= esc($candidate['priority'], 'attr') ?>">Prioritas <?= esc($candidate['priority_label']) ?></span></div></td>
                                    <td><span class="talent-status status-<?= esc($candidate['pool_status'], 'attr') ?>"><?= esc($candidate['status_label']) ?></span></td>
                                    <td><div class="talent-table-copy"><strong><?= esc($candidate['source_vacancy_title']) ?></strong><small><?= esc($candidate['application_number']) ?></small></div></td>
                                    <td><div class="talent-table-dates"><span>Siap: <strong><?= esc($date($candidate['available_from'])) ?></strong></span><span class="<?= $candidate['is_follow_up_due'] ? 'due' : '' ?>">Follow-up: <strong><?= esc($date($candidate['follow_up_at'])) ?></strong></span><?php if ($candidate['is_follow_up_due']): ?><small>Perlu ditindaklanjuti</small><?php endif ?></div></td>
                                    <td><?php if ($candidate['whatsapp_number'] !== ''): ?><a class="talent-table-whatsapp" href="https://wa.me/<?= esc($candidate['whatsapp_number'], 'attr') ?>" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L0 24l6.5-1.7c1.7.9 3.6 1.4 5.6 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.2-3.4-8.4Z"/><path d="M8 7.8c.3-.4.6-.4.9-.1l1.1 2.5c.1.3 0 .6-.2.8l-.7.8c1 1.9 2.4 3.2 4.3 4.1l.8-1c.2-.3.5-.3.8-.2l2.5 1.2c.3.2.4.4.3.8-.3 1.2-1.5 2-2.8 2-2.4 0-5.2-1.7-7.2-3.8-2-2.1-3.4-4.8-3.3-6.7 0-1.2.7-2.2 1.7-2.6"/></svg>Hubungi</a><?php else: ?>-<?php endif ?></td>
                                    <td><div class="talent-table-actions"><button class="talent-detail-button" type="button" data-admin-modal-open="talent-detail-<?= (int) $candidate['id'] ?>">Detail</button><button type="button" data-admin-modal-open="talent-history-<?= (int) $candidate['id'] ?>">Histori</button><?php if ($canManage): ?><button type="button" data-admin-modal-open="talent-edit-<?= (int) $candidate['id'] ?>">Edit</button><button class="talent-call-button" type="button" data-admin-modal-open="talent-call-<?= (int) $candidate['id'] ?>">Panggil</button><?php endif ?></div></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    <?= view('admin/partials/footer') ?>
    </main>
</div>

<?php foreach ($candidates as $candidate): $candidateHistories = $historiesByCandidate[(int) $candidate['id']] ?? []; ?>
    <dialog class="admin-modal talent-detail-modal" id="talent-detail-<?= (int) $candidate['id'] ?>" aria-labelledby="talent-detail-title-<?= (int) $candidate['id'] ?>">
        <div class="admin-modal-panel">
            <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg></span><div><h2 id="talent-detail-title-<?= (int) $candidate['id'] ?>"><?= esc($candidate['full_name']) ?></h2><p>Detail kandidat cadangan dan pertimbangan tim HRD.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
            <div class="talent-detail-body">
                <div class="talent-detail-profile"><span class="talent-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $candidate['full_name'], 0, 1))) ?></span><div><strong><?= esc($candidate['full_name']) ?></strong><a href="mailto:<?= esc($candidate['email'], 'attr') ?>"><?= esc($candidate['email']) ?></a><small><?= esc($candidate['phone']) ?><?= $candidate['age'] !== null ? ' · ' . (int) $candidate['age'] . ' tahun' : '' ?></small></div><span class="talent-status status-<?= esc($candidate['pool_status'], 'attr') ?>"><?= esc($candidate['status_label']) ?></span></div>
                <dl class="talent-detail-grid"><div><dt>Posisi rekomendasi</dt><dd><?= esc($candidate['recommended_position']) ?></dd></div><div><dt>Departemen</dt><dd><?= esc($candidate['target_department_name'] ?: '-') ?></dd></div><div><dt>Prioritas</dt><dd><?= esc($candidate['priority_label']) ?></dd></div><div><dt>Sumber lamaran</dt><dd><?= esc($candidate['source_vacancy_title']) ?><small><?= esc($candidate['application_number']) ?></small></dd></div><div><dt>Tersedia mulai</dt><dd><?= esc($date($candidate['available_from'])) ?></dd></div><div class="<?= $candidate['is_follow_up_due'] ? 'due' : '' ?>"><dt>Tindak lanjut</dt><dd><?= esc($date($candidate['follow_up_at'])) ?><?= $candidate['is_follow_up_due'] ? '<small>Perlu ditindaklanjuti</small>' : '' ?></dd></div><div><dt>Terakhir dihubungi</dt><dd><?= esc($date($candidate['last_contacted_at'], 'd M Y, H:i')) ?></dd></div><div><dt>Disimpan oleh</dt><dd><?= esc($candidate['saved_by_name'] ?: 'Sistem') ?><small><?= esc($date($candidate['saved_at'], 'd M Y, H:i')) ?></small></dd></div></dl>
                <section class="talent-detail-note"><span>Alasan disimpan</span><p><?= nl2br(esc($candidate['reason'])) ?></p></section>
                <section class="talent-detail-note strength"><span>Kelebihan kandidat</span><p><?= nl2br(esc(trim((string) ($candidate['strength_notes'] ?? '')) !== '' ? $candidate['strength_notes'] : '-')) ?></p></section>
                <section class="talent-detail-note internal"><span>Catatan internal HRD</span><p><?= nl2br(esc(trim((string) ($candidate['internal_notes'] ?? '')) !== '' ? $candidate['internal_notes'] : '-')) ?></p></section>
                <div class="talent-detail-footer"><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $candidate['applicant_id']) ?>">Buka profil lengkap</a><?php if ($candidate['whatsapp_number'] !== ''): ?><a class="talent-whatsapp" href="https://wa.me/<?= esc($candidate['whatsapp_number'], 'attr') ?>" target="_blank" rel="noopener noreferrer">Hubungi WhatsApp</a><?php endif ?><button type="button" data-admin-modal-close>Tutup</button></div>
            </div>
        </div>
    </dialog>

    <dialog class="admin-modal talent-history-modal" id="talent-history-<?= (int) $candidate['id'] ?>" aria-labelledby="talent-history-title-<?= (int) $candidate['id'] ?>">
        <div class="admin-modal-panel">
            <div class="settings-card-heading admin-modal-heading"><span class="settings-icon"><svg viewBox="0 0 24 24"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg></span><div><h2 id="talent-history-title-<?= (int) $candidate['id'] ?>">Histori <?= esc($candidate['full_name']) ?></h2><p><?= count($candidateHistories) ?> aktivitas Talent Pool</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
            <div class="talent-history-list">
                <?php if ($candidateHistories === []): ?><p class="candidate-empty">Belum ada histori.</p><?php endif ?>
                <?php foreach ($candidateHistories as $history): ?><article><i></i><div><strong><?= esc($historyActions[$history['action_code']] ?? ucwords(str_replace('_', ' ', $history['action_code']))) ?></strong><p><?= nl2br(esc($history['notes'] ?: '-')) ?></p><?php if ($history['related_vacancy_title']): ?><span>Lowongan: <?= esc($history['related_vacancy_title']) ?><?= $history['related_application_number'] ? ' · ' . esc($history['related_application_number']) : '' ?></span><?php endif ?><small><?= esc($date($history['created_at'], 'd M Y, H:i')) ?> · <?= esc($history['changed_by_name'] ?: 'Sistem') ?></small></div></article><?php endforeach ?>
            </div>
        </div>
    </dialog>

    <?php if ($canManage): ?>
        <dialog class="admin-modal talent-edit-modal" id="talent-edit-<?= (int) $candidate['id'] ?>" aria-labelledby="talent-edit-title-<?= (int) $candidate['id'] ?>">
            <div class="admin-modal-panel">
                <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4ZM13 7l4 4"/></svg></span><div><h2 id="talent-edit-title-<?= (int) $candidate['id'] ?>">Edit <?= esc($candidate['full_name']) ?></h2><p>Perbarui kesiapan dan rekomendasi kandidat.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                <form class="talent-pool-form" action="<?= site_url('adminhrdmannakampus/talent-pool/' . $candidate['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <label>Status<select name="pool_status" required><?php foreach ($statuses as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $candidate['pool_status'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                    <label>Prioritas<select name="priority" required><?php foreach ($priorities as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $candidate['priority'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                    <label>Posisi rekomendasi<input type="text" name="recommended_position" value="<?= esc($candidate['recommended_position'], 'attr') ?>" minlength="3" maxlength="150" required></label>
                    <label>Departemen rekomendasi<select name="target_department_id" required><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $candidate['target_department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select></label>
                    <label>Tersedia mulai<input type="date" name="available_from" value="<?= esc($candidate['available_from'] ?? '', 'attr') ?>"><small class="talent-field-help">Tanggal kandidat siap bekerja atau siap mengikuti proses rekrutmen kembali. Boleh dikosongkan jika belum diketahui.</small></label>
                    <label>Tindak lanjut<input type="date" name="follow_up_at" value="<?= esc($candidate['follow_up_at'] ?? '', 'attr') ?>"><small class="talent-field-help">Tanggal pengingat bagi HRD untuk menghubungi kandidat kembali. Kandidat akan ditandai jika tanggalnya sudah jatuh tempo.</small></label>
                    <label class="talent-pool-wide">Alasan disimpan<textarea name="reason" rows="3" minlength="5" maxlength="1000" required><?= esc($candidate['reason']) ?></textarea></label>
                    <label class="talent-pool-wide">Kelebihan kandidat<textarea name="strength_notes" rows="3" maxlength="4000"><?= esc($candidate['strength_notes'] ?? '') ?></textarea></label>
                    <label class="talent-pool-wide">Catatan internal<textarea name="internal_notes" rows="3" maxlength="4000"><?= esc($candidate['internal_notes'] ?? '') ?></textarea></label>
                    <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm="Simpan perubahan kandidat cadangan?">Simpan perubahan</button></div>
                </form>
            </div>
        </dialog>

        <dialog class="admin-modal talent-call-modal" id="talent-call-<?= (int) $candidate['id'] ?>" aria-labelledby="talent-call-title-<?= (int) $candidate['id'] ?>">
            <div class="admin-modal-panel">
                <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 12h14M14 7l5 5-5 5"/></svg></span><div><h2 id="talent-call-title-<?= (int) $candidate['id'] ?>">Panggil <?= esc($candidate['full_name']) ?></h2><p>Catat lowongan dan hasil komunikasi awal.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                <form class="talent-call-form" action="<?= site_url('adminhrdmannakampus/talent-pool/' . $candidate['id'] . '/panggil') ?>" method="post">
                    <?= csrf_field() ?>
                    <label>Lowongan dan sesi tujuan<select name="vacancy_period_id" required><option value="">Pilih sesi lowongan aktif</option><?php foreach ($recruitmentPeriods as $period): ?><option value="<?= (int) $period['id'] ?>"><?= esc($period['vacancy_title']) ?> · <?= esc($period['period_name']) ?> (<?= esc(ucfirst($period['period_status'])) ?>)</option><?php endforeach ?></select></label>
                    <label>Tindak lanjut berikutnya<input type="date" name="follow_up_at"><small class="talent-field-help">Pilih kapan HRD perlu mengecek respons atau menghubungi kandidat lagi setelah pemanggilan ini.</small></label>
                    <label class="talent-pool-wide">Catatan pemanggilan<textarea name="contact_notes" rows="5" minlength="5" maxlength="1000" required placeholder="Contoh: Kandidat dihubungi melalui WhatsApp dan bersedia mengikuti proses seleksi."></textarea></label>
                    <label class="talent-confirm-check"><input type="checkbox" name="candidate_confirmed" value="1" required><span>Saya mengonfirmasi kandidat sudah dihubungi dan bersedia diproses untuk lowongan ini.</span></label>
                    <div class="talent-call-help"><strong>Apa yang terjadi?</strong><span>Sistem membuat lamaran baru dengan nomor baru, menyalin profil dan pengalaman kerja dari lamaran sumber, lalu mencatat hubungan tersebut pada histori Talent Pool.</span></div>
                    <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm="Buat lamaran baru untuk kandidat pada lowongan ini?">Panggil &amp; buat lamaran</button></div>
                </form>
            </div>
        </dialog>
    <?php endif ?>
<?php endforeach ?>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=7" defer></script>
</body>
</html>
