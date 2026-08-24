<?php
$statusLabels = [
    'lamaran_baru' => 'Lamaran Baru', 'submitted' => 'Lamaran diterima', 'document_screening' => 'Sedang Screening', 'screening_passed' => 'Lolos screening', 'screening_failed' => 'Tidak lolos screening',
    'administration' => 'Administrasi', 'under_review' => 'Sedang ditinjau', 'reviewed' => 'Sedang ditinjau',
    'test_scheduled' => 'Jadwal tes', 'assessment' => 'Tahap asesmen', 'interview_scheduled' => 'Interview HRD',
    'interview_hr' => 'Interview HRD', 'hrd_interview' => 'Interview HRD', 'interview_user' => 'Interview User',
    'user_interview' => 'Interview User', 'psychotest' => 'Psikotes', 'medical_checkup' => 'Medical Check-up',
    'accepted' => 'Diterima', 'hired' => 'Diterima', 'rejected' => 'Ditolak', 'withdrawn' => 'Dibatalkan',
];
$date = static fn (?string $value, string $format = 'd M Y, H:i'): string => $value && strtotime($value) !== false ? date($format, strtotime($value)) : '-';
$value = static fn (mixed $value): string => trim((string) $value) !== '' ? (string) $value : '-';
$answerValue = static function (array $answer): string {
    $raw = trim((string) ($answer['answer_value'] ?? ''));
    if (($answer['answer_type'] ?? '') === 'boolean') {
        return in_array(mb_strtolower($raw), ['1', 'true', 'yes', 'ya'], true) ? 'Ya' : 'Tidak';
    }
    return $raw !== '' ? $raw : '-';
};
$initial = mb_strtoupper(mb_substr((string) $applicant['full_name'], 0, 1));
$blacklistStatusLabels = ['active' => 'Aktif', 'permanent' => 'Permanen', 'expired' => 'Berakhir', 'revoked' => 'Dicabut'];
$blacklistHistoryLabels = ['blacklisted' => 'Ditambahkan', 'updated' => 'Diperbarui', 'revoked' => 'Dicabut', 'reactivated' => 'Diaktifkan kembali'];
$blacklistDurationLabels = ['1_month' => '1 bulan', '3_months' => '3 bulan', '6_months' => '6 bulan', '1_year' => '1 tahun', '2_years' => '2 tahun', 'custom' => 'Tanggal khusus', 'permanent' => 'Permanen'];
$isBlacklistActive = $blacklist !== null && in_array($blacklist['computed_status'], ['active', 'permanent'], true);
$whatsAppNumber = preg_replace('/\D+/', '', (string) ($applicant['phone'] ?? '')) ?? '';
if (str_starts_with($whatsAppNumber, '0')) {
    $whatsAppNumber = '62' . substr($whatsAppNumber, 1);
} elseif (str_starts_with($whatsAppNumber, '8')) {
    $whatsAppNumber = '62' . $whatsAppNumber;
}
$whatsAppMessage = 'Halo ' . $applicant['full_name'] . ",\n\nKami dari Tim Rekrutmen Manna Kampus ingin menghubungi Anda terkait proses lamaran kerja. Apakah Anda bersedia melanjutkan komunikasi melalui WhatsApp?\n\nTerima kasih.";
$progressLabels = ['previous' => 'Sebelumnya', 'current' => 'Posisi saat ini', 'next' => 'Selanjutnya', 'upcoming' => 'Akan datang'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Detail <?= esc($applicant['full_name']) ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=60">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'candidates']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Candidate Profile</span><strong>Detail Pelamar</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/list-pelamar') ?>">Kembali ke list pelamar</a>
        </header>

        <div class="admin-content candidate-detail-content">
            <?php if ($blacklistSuccess): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($blacklistSuccess) ?></div><?php endif ?>
            <?php if ($blacklistError): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($blacklistError) ?></div><?php endif ?>
            <?php if ($candidateSuccess): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($candidateSuccess) ?></div><?php endif ?>
            <?php if ($candidateError): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($candidateError) ?></div><?php endif ?>
            <?php $applicationsWithProcess = array_values(array_filter($applications, static fn (array $application): bool => $application['process_steps'] !== [])); ?>
            <?php if ($applicationsWithProcess !== []): ?><section class="candidate-top-processes" aria-label="Alur rekrutmen pelamar"><?php foreach ($applicationsWithProcess as $processApplication): $processApplicationId = (int) $processApplication['id']; ?><article class="candidate-process-progress candidate-detail-process-progress candidate-top-process-card"><div class="candidate-process-progress-heading"><div><span class="candidate-top-process-eyebrow">Alur rekrutmen</span><strong><?= esc($processApplication['vacancy_title']) ?></strong><small><?= esc($processApplication['application_number']) ?> · <?= esc($statusLabels[$processApplication['application_status']] ?? ucwords(str_replace('_', ' ', $processApplication['application_status']))) ?></small></div><div class="candidate-detail-process-actions"><div class="candidate-process-legend" aria-label="Keterangan tahap"><span class="previous"><i></i>Sebelumnya</span><span class="current"><i></i>Saat ini</span><span class="next"><i></i>Selanjutnya</span></div><?php if ($processApplication['can_undo_stage']): ?><button class="candidate-detail-undo-button" type="button" data-admin-modal-open="applicant-undo-modal-<?= $processApplicationId ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7 4 12l5 5"/><path d="M5 12h9a5 5 0 0 1 5 5v2"/></svg>Urungkan perubahan</button><?php endif ?></div></div><div class="candidate-process-progress-scroll"><ol class="candidate-process-stepper" style="--candidate-step-count: <?= count($processApplication['process_steps']) ?>" aria-label="Urutan tahapan rekrutmen <?= esc($processApplication['vacancy_title'], 'attr') ?>"><?php foreach ($processApplication['process_steps'] as $step): $progressState = (string) $step['progress_state']; ?><li class="is-<?= esc($progressState, 'attr') ?>" <?= $progressState === 'current' ? 'aria-current="step"' : '' ?>><span class="candidate-process-marker"><?= (int) $step['display_order'] ?></span><strong><?= esc($step['name']) ?></strong><small><?= esc($progressLabels[$progressState] ?? 'Akan datang') ?></small></li><?php endforeach ?></ol></div></article><?php endforeach ?></section><?php endif ?>
            <section class="candidate-profile-card <?= $isBlacklistActive ? 'is-blacklisted' : 'is-clear' ?>">
                <div class="candidate-avatar" aria-hidden="true"><?= esc($initial) ?></div>
                <div class="candidate-profile-copy">
                    <span class="login-eyebrow">Applicant Profile</span>
                    <h1><?= esc($applicant['full_name']) ?></h1>
                    <p><?= esc($applicant['email']) ?> <i></i> <?= esc($applicant['phone']) ?></p>
                </div>
                <div class="candidate-profile-actions">
                    <span class="account-status <?= (int) $applicant['is_active'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $applicant['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                    <div class="candidate-profile-action-buttons">
                        <?php if ($canManageBlacklist): ?><button class="candidate-profile-action candidate-blacklist-button <?= $isBlacklistActive ? 'is-active' : '' ?>" type="button" data-admin-modal-open="applicant-blacklist-modal"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg><span><?= $isBlacklistActive ? 'Kelola blacklist' : ($blacklist === null ? 'Masukkan blacklist' : 'Aktifkan blacklist') ?></span></button><?php endif ?>
                        <a class="candidate-profile-action candidate-email-button" href="mailto:<?= esc($applicant['email'], 'attr') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg><span>Kirim email</span></a>
                        <?php if ($whatsAppNumber !== ''): ?><a class="candidate-profile-action candidate-whatsapp-button" href="https://wa.me/<?= esc($whatsAppNumber, 'attr') ?>?text=<?= esc(rawurlencode($whatsAppMessage), 'attr') ?>" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L0 24l6.5-1.7c1.7.9 3.6 1.4 5.6 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.2-3.4-8.4Z"/><path d="M8.1 6.8c-.3 0-.7.1-1 .5-.4.4-1.3 1.3-1.3 3s1.3 3.5 1.5 3.7c.2.2 2.5 3.9 6.1 5.3 3 .9 3.7.7 4.4.6.7-.1 2.1-.9 2.4-1.7.3-.8.3-1.4.2-1.6-.1-.2-.4-.3-.8-.5l-2.3-1.1c-.3-.1-.6-.2-.8.2l-1 1.2c-.2.3-.4.3-.8.1-2.1-1-3.5-2.6-4-3.5-.2-.4 0-.6.2-.8l.7-.8c.2-.2.2-.5.3-.7.1-.2 0-.5-.1-.7l-1-2.4c-.2-.6-.5-.8-.7-.8Z"/></svg><span>Kirim WhatsApp</span></a><?php endif ?>
                        <?php if ($canCancelAssignment): ?><form class="candidate-profile-action-form" action="<?= site_url('adminhrdmannakampus/kandidat/pelamar/' . $applicant['id'] . '/batal-pilih') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="team_id" value="<?= (int) $applicant['assigned_hrd_team_id'] ?>"><input type="hidden" name="return_to" value="detail"><button class="candidate-profile-action candidate-cancel-detail-button" type="submit" data-confirm-title="Batalkan pilihan pelamar?" data-confirm="<?= esc($applicant['full_name'], 'attr') ?> akan dikeluarkan dari <?= esc((string) $applicant['assigned_hrd_team_name'], 'attr') ?>." data-confirm-details="Divisi Pusat menjadi Belum dipilih.|Data Cadangan dan seluruh riwayat Talent Pool dihapus permanen.|Seluruh Alur Rekrutmen direset kembali ke Lamaran Baru.|Riwayat keputusan gugur tetap disimpan untuk masa tunggu pendaftaran." data-confirm-button="Ya, batalkan pilihan" data-cancel-button="Jangan batalkan" data-confirm-color="#dc2626"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7H5v4M5.5 10a7 7 0 1 0 2-4"/><path d="M8 12h8"/></svg><span>Batal pilih</span></button></form><?php endif ?>
                    </div>
                </div>
            </section>

            <?php if ($canViewBlacklist && $blacklist !== null): ?>
                <section class="candidate-blacklist-card status-<?= esc($blacklist['computed_status'], 'attr') ?>">
                    <span class="candidate-blacklist-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></span>
                    <div><span>Status blacklist</span><h2><?= esc($blacklistStatusLabels[$blacklist['computed_status']] ?? 'Tidak diketahui') ?></h2><p><?= esc($blacklist['reason']) ?></p><small><?= (int) $blacklist['is_permanent'] === 1 ? 'Berlaku permanen' : 'Berlaku sampai ' . esc($date($blacklist['ends_at'], 'd M Y')) ?> · diperbarui oleh <?= esc($blacklist['updated_by_name'] ?: $blacklist['created_by_name'] ?: 'Sistem') ?></small></div>
                    <a href="<?= site_url('adminhrdmannakampus/blacklist-pelamar?keyword=' . rawurlencode($applicant['email'])) ?>">Lihat riwayat</a>
                </section>
            <?php endif ?>

            <div class="candidate-detail-grid">
                <section class="settings-card candidate-info-card">
                    <div class="settings-card-heading"><span class="settings-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg></span><div><h2>Data pribadi</h2><p>Informasi identitas yang diberikan saat pendaftaran.</p></div></div>
                    <div class="candidate-info-list">
                        <div><span>Tempat, tanggal lahir</span><strong><?= esc($value($applicant['birth_place'])) ?>, <?= esc($date($applicant['birth_date'], 'd M Y')) ?></strong></div>
                        <div><span>Jenis kelamin</span><strong><?= esc(ucwords(mb_strtolower($value($applicant['gender'])))) ?></strong></div>
                        <div><span>Tinggi badan</span><strong><?= $applicant['height_cm'] ? (int) $applicant['height_cm'] . ' cm' : '-' ?></strong></div>
                        <div><span>Status pernikahan</span><strong><?= esc(ucwords(mb_strtolower($value($applicant['marital_status'])))) ?></strong></div>
                        <div><span>Agama</span><strong><?= esc($value($applicant['religion'])) ?></strong></div>
                        <div class="candidate-info-wide"><span>Alamat</span><strong><?= nl2br(esc($value($applicant['address']))) ?></strong></div>
                    </div>
                </section>

                <section class="settings-card candidate-info-card">
                    <div class="settings-card-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="m3 9 9-5 9 5-9 5-9-5Z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 9v6"/></svg></span><div><h2>Pendidikan</h2><p>Riwayat pendidikan dan pelatihan pelamar.</p></div></div>
                    <div class="candidate-info-list">
                        <div><span>Pendidikan terakhir</span><strong><?= esc($value($applicant['last_education'])) ?></strong></div>
                        <div><span>Institusi</span><strong><?= esc($value($applicant['institution'])) ?></strong></div>
                        <div><span>Jurusan</span><strong><?= esc($value($applicant['major'])) ?></strong></div>
                        <div><span>IPK</span><strong><?= $applicant['gpa'] !== null ? esc(number_format((float) $applicant['gpa'], 2, ',', '.')) : '-' ?></strong></div>
                        <div class="candidate-info-wide"><span>Pelatihan dan sertifikasi</span><strong><?= nl2br(esc($value($applicant['training_experience']))) ?></strong></div>
                    </div>
                </section>
            </div>

            <section class="settings-card candidate-documents-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M6 4h9l3 3v13H6V4Z"/><path d="M14 4v4h4M9 12h6M9 16h6"/></svg></span><div><h2>Dokumen pelamar</h2><p>Berkas lamaran PDF dari seluruh batch pendaftaran.</p></div><span class="device-count"><?= count($documents) ?></span></div>
                <div class="candidate-document-list">
                    <?php if ($documents === []): ?><p class="candidate-empty">Belum ada dokumen tersimpan.</p><?php endif ?>
                    <?php foreach ($documents as $document): ?>
                        <article><span class="candidate-document-icon"><svg viewBox="0 0 24 24"><path d="M6 4h9l3 3v13H6V4Z"/><path d="M14 4v4h4"/></svg></span><div><strong><?= esc($document['original_name']) ?></strong><small><?= $document['document_type'] === 'application_bundle' ? 'Berkas lamaran lengkap' : 'Dokumen lama' ?> · <?= esc($document['batch_number']) ?> · <?= esc(number_format(((int) $document['file_size']) / 1024, 1, ',', '.')) ?> KB</small></div><?php if ($canDownloadDocuments): ?><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $applicant['id'] . '/dokumen/' . $document['id']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Lihat <?= esc($document['original_name'], 'attr') ?> di tab baru">Lihat</a><?php else: ?><span class="protected-label">Tanpa akses lihat</span><?php endif ?></article>
                    <?php endforeach ?>
                </div>
            </section>

            <div class="candidate-section-heading"><span class="login-eyebrow">Application Journey</span><h2>Riwayat lamaran</h2><p><?= count($applications) ?> posisi yang pernah dilamar oleh kandidat ini.</p></div>
            <?php if ($applications === []): ?><section class="settings-card candidate-empty">Pelamar belum memiliki lamaran aktif.</section><?php endif ?>
            <?php foreach ($applications as $application):
                $applicationId = (int) $application['id'];
                $screening = (string) ($application['screening_status'] ?: 'pending');
                $applicationAnswers = $answersByApplication[$applicationId] ?? [];
                $applicationHistories = $historiesByApplication[$applicationId] ?? [];
                $applicationSchedules = $schedulesByApplication[$applicationId] ?? [];
                $applicationWorkExperiences = $workExperiencesByBatch[(int) $application['batch_id']] ?? [];
            ?>
                <section class="settings-card candidate-application-card">
                    <div class="candidate-application-heading">
                        <div><span>Prioritas <?= (int) $application['preference_order'] ?> · <?= esc($application['department_name']) ?></span><h3><?= esc($application['vacancy_title']) ?></h3><code><?= esc($application['application_number']) ?></code></div>
                        <div class="candidate-application-heading-actions">
                            <span class="report-application-status"><?= esc($statusLabels[$application['application_status']] ?? ucwords(str_replace('_', ' ', $application['application_status']))) ?></span>
                            <?php if (! empty($application['talent_pool_id'])): ?>
                                <a class="candidate-detail-talent-saved" href="<?= site_url('adminhrdmannakampus/talent-pool?team_id=' . (int) $applicant['assigned_hrd_team_id']) ?>">Sudah dicadangkan</a>
                            <?php elseif ($canSaveTalentPool && ! $isBlacklistActive): ?>
                                <button class="candidate-detail-talent-button" type="button" data-admin-modal-open="applicant-talent-modal-<?= $applicationId ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v15l-7-3-7 3V5Z"/><path d="M9 9h6M9 12h6"/></svg>Simpan cadangan</button>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="candidate-application-metrics">
                        <div><span>Tanggal daftar</span><strong><?= esc($date($application['submitted_at'])) ?></strong></div>
                        <div><span>Hasil screening</span><strong class="candidate-screening-text screening-text-<?= esc($screening, 'attr') ?>"><?= $screening === 'passed' ? 'Lolos' : ($screening === 'failed' ? 'Tidak lolos' : 'Belum dinilai') ?></strong></div>
                        <div><span>Nilai screening</span><strong><?= $application['screening_score'] !== null ? esc(number_format((float) $application['screening_score'], 2, ',', '.')) : '-' ?></strong></div>
                        <div><span>Batch pendaftaran</span><strong><?= esc($application['batch_number']) ?></strong></div>
                    </div>
                    <?php if (! empty($application['rejected_stage_name'])): ?>
                        <div class="candidate-rejection-card">
                            <span>Keputusan gugur</span>
                            <h4>Gugur di <?= $application['rejected_stage_order'] !== null ? 'Tahap ' . (int) $application['rejected_stage_order'] . ' — ' : '' ?><?= esc($application['rejected_stage_name']) ?></h4>
                            <dl>
                                <div><dt>Alasan</dt><dd><strong><?= esc($application['rejection_reason_title']) ?></strong></dd></div>
                                <div><dt>Diputuskan oleh</dt><dd><?= esc($application['rejected_by_name'] ?: 'Sistem') ?></dd></div>
                                <div><dt>Waktu keputusan</dt><dd><?= esc($date($application['rejected_at'])) ?></dd></div>
                                <div class="candidate-rejection-wide"><dt>Pesan untuk pelamar</dt><dd><?= nl2br(esc($value($application['rejection_reason_text']))) ?></dd></div>
                                <?php if (trim((string) ($application['rejection_internal_notes'] ?? '')) !== ''): ?><div class="candidate-rejection-wide"><dt>Catatan internal</dt><dd><?= nl2br(esc($application['rejection_internal_notes'])) ?></dd></div><?php endif ?>
                            </dl>
                        </div>
                    <?php endif ?>
                    <?php if ($applicationSchedules !== []): ?><section class="candidate-detail-schedules"><div class="candidate-subsection-title"><h4>Jadwal seleksi</h4><span><?= count($applicationSchedules) ?> jadwal</span></div><?php $detailScheduleLabels = ['scheduled' => 'Menunggu konfirmasi', 'confirmed' => 'Bersedia hadir', 'reschedule_requested' => 'Minta jadwal ulang', 'present' => 'Hadir', 'absent' => 'Tidak hadir', 'cancelled' => 'Dibatalkan']; foreach ($applicationSchedules as $schedule): ?><article><div><strong><?= esc($schedule['stage_name']) ?></strong><span><?= esc($date($schedule['scheduled_at'])) ?> WIB · <?= esc($schedule['venue']) ?></span><small>PIC <?= esc($schedule['pic_name']) ?> · batas konfirmasi <?= esc($date($schedule['confirmation_deadline_at'])) ?></small><?php if ($schedule['candidate_note']): ?><p>Catatan kandidat: <?= esc($schedule['candidate_note']) ?></p><?php endif ?></div><span class="schedule-status schedule-status-<?= esc($schedule['status'], 'attr') ?>"><?= esc($detailScheduleLabels[$schedule['status']] ?? $schedule['status']) ?></span></article><?php endforeach ?></section><?php endif ?>
                    <div class="candidate-narrative-grid">
                        <div class="candidate-work-experience"><span>Pengalaman kerja</span><?php if ($applicationWorkExperiences === []): ?><p><?= nl2br(esc($value($application['work_experience']))) ?></p><?php else: ?><?php foreach ($applicationWorkExperiences as $experience): ?><article><strong><?= esc($experience['company_name']) ?></strong><b><?= esc($value($experience['position_title'])) ?></b><small><?= (int) $experience['start_year'] ?>–<?= $experience['end_year'] === null ? 'Sekarang' : (int) $experience['end_year'] ?></small><p><?= nl2br(esc($experience['responsibilities'])) ?></p></article><?php endforeach ?><?php endif ?></div>
                        <div><span>Motivasi bekerja dan alasan ingin bergabung dengan Manna Kampus</span><p><?= nl2br(esc($value($application['work_motivation']))) ?></p></div>
                        <div><span>Target/impian yang akan dicapai</span><p><?= nl2br(esc($value($application['career_goal']))) ?></p></div>
                    </div>
                    <div class="candidate-application-columns">
                        <div class="candidate-subsection"><div class="candidate-subsection-title"><h4>Jawaban screening</h4><span><?= count($applicationAnswers) ?> jawaban</span></div><div class="department-table-wrap"><table class="department-table candidate-answer-table"><thead><tr><th>Pertanyaan</th><th>Jawaban</th><th>Hasil</th><th>Nilai</th></tr></thead><tbody><?php if ($applicationAnswers === []): ?><tr><td colspan="4" class="department-empty">Tidak ada jawaban screening.</td></tr><?php endif ?><?php foreach ($applicationAnswers as $answer): $eligibility = $answer['is_eligible']; ?><tr><td><?= esc($answer['question_text']) ?><?= (int) $answer['is_knockout'] === 1 ? '<small>Knockout</small>' : '' ?></td><td><strong><?= esc($answerValue($answer)) ?></strong></td><td><span class="account-status <?= $eligibility === null ? 'pending' : ((int) $eligibility === 1 ? 'active' : 'inactive') ?>"><i></i><?= $eligibility === null ? 'Belum dinilai' : ((int) $eligibility === 1 ? 'Sesuai' : 'Tidak sesuai') ?></span></td><td><?= $answer['score'] !== null ? esc(number_format((float) $answer['score'], 2, ',', '.')) : '-' ?></td></tr><?php endforeach ?></tbody></table></div></div>
                        <div class="candidate-subsection"><div class="candidate-subsection-title"><h4>Riwayat status</h4><span><?= count($applicationHistories) ?> aktivitas</span></div><div class="candidate-timeline"><?php if ($applicationHistories === []): ?><p class="candidate-empty">Belum ada perubahan status.</p><?php endif ?><?php foreach ($applicationHistories as $history): ?><article><i></i><div><strong><?= esc($statusLabels[$history['new_status']] ?? ucwords(str_replace('_', ' ', $history['new_status']))) ?></strong><p><?= esc($value($history['notes'])) ?></p><small><?= esc($date($history['created_at'])) ?> · <?= esc($history['changed_by_name'] ?: 'Sistem') ?></small></div></article><?php endforeach ?></div></div>
                    </div>
                </section>
            <?php endforeach ?>
        </div>
    </main>
</div>
<?php foreach ($applications as $application): if (! $application['can_undo_stage']) { continue; } $undoChange = $application['last_stage_change']; ?>
<dialog class="admin-modal candidate-undo-modal" id="applicant-undo-modal-<?= (int) $application['id'] ?>" aria-labelledby="applicant-undo-title-<?= (int) $application['id'] ?>">
    <div class="admin-modal-panel">
        <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><path d="M9 7 4 12l5 5"/><path d="M5 12h9a5 5 0 0 1 5 5v2"/></svg></span><div><h2 id="applicant-undo-title-<?= (int) $application['id'] ?>">Urungkan perubahan tahap</h2><p><?= esc($applicant['full_name']) ?> · <?= esc($application['vacancy_title']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
        <div class="candidate-undo-summary"><span>Perubahan yang akan dikoreksi</span><div><strong><?= esc($statusLabels[$application['application_status']] ?? ucwords(str_replace('_', ' ', $application['application_status']))) ?></strong><i aria-hidden="true">→</i><strong><?= esc($statusLabels[$undoChange['previous_status']] ?? ucwords(str_replace('_', ' ', $undoChange['previous_status']))) ?></strong></div><p>Gunakan hanya jika Anda tidak sengaja mengubah tahap pelamar ini. Jadwal yang belum dikonfirmasi akan dibatalkan otomatis.</p></div>
        <form class="candidate-undo-form" action="<?= site_url('adminhrdmannakampus/kandidat/lamaran/' . $application['id'] . '/urungkan-tahap') ?>" method="post">
            <?= csrf_field() ?><input type="hidden" name="team_id" value="<?= (int) $applicant['assigned_hrd_team_id'] ?>"><input type="hidden" name="return_to" value="detail">
            <label>Alasan koreksi<textarea name="reason" rows="4" minlength="5" maxlength="1000" placeholder="Contoh: Saya tidak sengaja mengubah tahap pelamar ini." required></textarea><small>Alasan dan nama pengguna akan tersimpan dalam histori kandidat.</small></label>
            <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button class="candidate-undo-submit" type="submit" data-confirm-title="Urungkan perubahan tahap?" data-confirm="Tahap kandidat akan dikembalikan dan jadwal yang belum dikonfirmasi dibatalkan." data-confirm-button="Ya, urungkan" data-confirm-color="#b84d45">Urungkan perubahan</button></div>
        </form>
    </div>
</dialog>
<?php endforeach ?>
<?php if ($canSaveTalentPool && ! $isBlacklistActive): ?>
    <?php foreach ($applications as $application): if (! empty($application['talent_pool_id'])) { continue; } ?>
        <dialog class="admin-modal talent-pool-save-modal" id="applicant-talent-modal-<?= (int) $application['id'] ?>" aria-labelledby="applicant-talent-title-<?= (int) $application['id'] ?>">
            <div class="admin-modal-panel">
                <div class="settings-card-heading admin-modal-heading">
                    <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v15l-7-3-7 3V5Z"/><path d="M9 9h6M9 12h6"/></svg></span>
                    <div><h2 id="applicant-talent-title-<?= (int) $application['id'] ?>">Simpan <?= esc($applicant['full_name']) ?> sebagai cadangan</h2><p><?= esc($application['vacancy_title']) ?> · <?= esc($application['application_number']) ?></p></div>
                    <button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button>
                </div>
                <form class="talent-pool-form" action="<?= site_url('adminhrdmannakampus/talent-pool/simpan/' . $application['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="detail">
                    <label>Posisi rekomendasi<input type="text" name="recommended_position" value="<?= esc($application['vacancy_title'], 'attr') ?>" minlength="3" maxlength="150" required></label>
                    <label>Departemen rekomendasi<select name="target_department_id" required><option value="">Pilih departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $application['department_id'] === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select></label>
                    <label>Prioritas<select name="priority" required><option value="high">Tinggi</option><option value="normal" selected>Normal</option><option value="low">Rendah</option></select></label>
                    <label>Tersedia mulai<input type="date" name="available_from"><small class="talent-field-help">Boleh dikosongkan jika belum diketahui.</small></label>
                    <label>Tindak lanjut<input type="date" name="follow_up_at"><small class="talent-field-help">Tanggal pengingat HRD menghubungi kandidat.</small></label>
                    <label class="talent-pool-wide">Alasan disimpan<textarea name="reason" rows="3" minlength="5" maxlength="1000" required placeholder="Contoh: posisi sudah terisi, tetapi kandidat memenuhi kualifikasi"></textarea></label>
                    <label class="talent-pool-wide">Kelebihan kandidat<textarea name="strength_notes" rows="3" maxlength="4000" placeholder="Keahlian, pengalaman, atau karakter yang menonjol"></textarea></label>
                    <label class="talent-pool-wide">Catatan internal<textarea name="internal_notes" rows="3" maxlength="4000" placeholder="Catatan hanya untuk tim HRD"></textarea></label>
                    <div class="candidate-process-buttons"><button class="candidate-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm="Simpan pelamar ini ke Talent Pool?">Simpan Talent Pool</button></div>
                </form>
            </div>
        </dialog>
    <?php endforeach ?>
<?php endif ?>
<?php if ($canManageBlacklist): ?>
<dialog class="admin-modal blacklist-form-modal" id="applicant-blacklist-modal" aria-labelledby="applicant-blacklist-title"><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></span><div><h2 id="applicant-blacklist-title"><?= $isBlacklistActive ? 'Kelola blacklist' : ($blacklist === null ? 'Masukkan ke blacklist' : 'Aktifkan kembali blacklist') ?></h2><p><?= esc($applicant['full_name']) ?> tidak dapat mendaftar ke seluruh lowongan selama blacklist aktif.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="blacklist-form" action="<?= $isBlacklistActive ? site_url('adminhrdmannakampus/blacklist-pelamar/' . $blacklist['id']) : site_url('adminhrdmannakampus/blacklist-pelamar/pelamar/' . $applicant['id']) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" value="detail"><label>Alasan blacklist<textarea name="reason" rows="3" minlength="5" maxlength="1000" required placeholder="Jelaskan alasan pelamar diblokir"><?= esc((string) ($blacklist['reason'] ?? '')) ?></textarea><small>Hanya terlihat oleh tim HRD dan tidak ditampilkan kepada pelamar.</small></label><label>Catatan internal<textarea name="internal_notes" rows="3" maxlength="5000"><?= esc((string) ($blacklist['internal_notes'] ?? '')) ?></textarea></label><label>Masa berlaku<select name="duration" required data-blacklist-duration><option value="">Pilih masa berlaku</option><?php foreach ($blacklistDurationLabels as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $code === ((int) ($blacklist['is_permanent'] ?? 0) === 1 ? 'permanent' : ($blacklist === null ? '' : 'custom')) ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><label data-blacklist-custom-date <?= $blacklist === null || (int) ($blacklist['is_permanent'] ?? 0) === 1 ? 'hidden' : '' ?>>Berakhir pada<input type="date" name="ends_on" value="<?= ! empty($blacklist['ends_at']) ? esc(date('Y-m-d', strtotime($blacklist['ends_at'])), 'attr') : '' ?>" min="<?= date('Y-m-d') ?>"></label><div class="blacklist-form-warning"><strong>Konsekuensi blacklist</strong><span>Semua pendaftaran baru menggunakan identitas pelamar ini akan ditolak oleh backend.</span></div><div class="department-modal-actions"><?php if ($isBlacklistActive): ?><button class="blacklist-revoke-open" type="button" data-admin-modal-open="applicant-blacklist-revoke-modal">Cabut blacklist</button><?php endif ?><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="<?= $isBlacklistActive ? 'Simpan perubahan blacklist?' : 'Aktifkan blacklist pelamar?' ?>" data-confirm="<?= esc($applicant['full_name'], 'attr') ?> tidak akan dapat mendaftar ke lowongan mana pun." data-confirm-details="Pemblokiran diperiksa langsung oleh backend.|Alasan internal tidak ditampilkan kepada pelamar." data-confirm-button="<?= $isBlacklistActive ? 'Ya, simpan perubahan' : 'Ya, aktifkan blacklist' ?>" data-confirm-color="#dc2626">Simpan blacklist</button></div></form></div></dialog>
<?php if ($isBlacklistActive): ?><dialog class="admin-modal blacklist-form-modal" id="applicant-blacklist-revoke-modal" aria-labelledby="applicant-blacklist-revoke-title"><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><div><h2 id="applicant-blacklist-revoke-title">Cabut blacklist</h2><p><?= esc($applicant['full_name']) ?> akan dapat mendaftar kembali.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="blacklist-form" action="<?= site_url('adminhrdmannakampus/blacklist-pelamar/' . $blacklist['id'] . '/cabut') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" value="detail"><label>Alasan pencabutan<textarea name="revocation_reason" rows="4" minlength="5" maxlength="1000" required></textarea></label><div class="department-modal-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="Cabut blacklist pelamar?" data-confirm="Pelamar akan dapat mendaftar ke seluruh lowongan kembali." data-confirm-button="Ya, cabut blacklist">Cabut blacklist</button></div></form></div></dialog><?php endif ?>
<?php endif ?>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=9" defer></script>
</body>
</html>
