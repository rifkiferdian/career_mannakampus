<?php
$statusLabels = [
    'submitted' => 'Lamaran diterima', 'screening_passed' => 'Lolos screening', 'screening_failed' => 'Tidak lolos screening',
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
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Detail <?= esc($applicant['full_name']) ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=20">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'candidates']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Candidate Profile</span><strong>Detail Pelamar</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/laporan-pelamar') ?>">Kembali ke laporan</a>
        </header>

        <div class="admin-content candidate-detail-content">
            <section class="candidate-profile-card">
                <div class="candidate-avatar" aria-hidden="true"><?= esc($initial) ?></div>
                <div class="candidate-profile-copy">
                    <span class="login-eyebrow">Applicant Profile</span>
                    <h1><?= esc($applicant['full_name']) ?></h1>
                    <p><?= esc($applicant['email']) ?> <i></i> <?= esc($applicant['phone']) ?></p>
                </div>
                <div class="candidate-profile-actions">
                    <span class="account-status <?= (int) $applicant['is_active'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $applicant['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                    <a href="mailto:<?= esc($applicant['email'], 'attr') ?>">Kirim email</a>
                </div>
            </section>

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
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M6 4h9l3 3v13H6V4Z"/><path d="M14 4v4h4M9 12h6M9 16h6"/></svg></span><div><h2>Dokumen pelamar</h2><p>CV dan dokumen pendukung dari seluruh batch pendaftaran.</p></div><span class="device-count"><?= count($documents) ?></span></div>
                <div class="candidate-document-list">
                    <?php if ($documents === []): ?><p class="candidate-empty">Belum ada dokumen tersimpan.</p><?php endif ?>
                    <?php foreach ($documents as $document): ?>
                        <article><span class="candidate-document-icon"><svg viewBox="0 0 24 24"><path d="M6 4h9l3 3v13H6V4Z"/><path d="M14 4v4h4"/></svg></span><div><strong><?= esc($document['original_name']) ?></strong><small><?= $document['document_type'] === 'cv' ? 'Curriculum Vitae' : 'Dokumen pendukung' ?> · <?= esc($document['batch_number']) ?> · <?= esc(number_format(((int) $document['file_size']) / 1024, 1, ',', '.')) ?> KB</small></div><?php if ($canDownloadDocuments): ?><a href="<?= site_url('adminhrdmannakampus/pelamar/' . $applicant['id'] . '/dokumen/' . $document['id']) ?>">Unduh</a><?php else: ?><span class="protected-label">Tanpa akses unduh</span><?php endif ?></article>
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
                $portfolioUrl = trim((string) ($application['portfolio_url'] ?? ''));
                $portfolioScheme = mb_strtolower((string) parse_url($portfolioUrl, PHP_URL_SCHEME));
                $portfolioUrl = filter_var($portfolioUrl, FILTER_VALIDATE_URL) && in_array($portfolioScheme, ['http', 'https'], true) ? $portfolioUrl : '';
            ?>
                <section class="settings-card candidate-application-card">
                    <div class="candidate-application-heading">
                        <div><span>Prioritas <?= (int) $application['preference_order'] ?> · <?= esc($application['department_name']) ?></span><h3><?= esc($application['vacancy_title']) ?></h3><code><?= esc($application['application_number']) ?></code></div>
                        <span class="report-application-status"><?= esc($statusLabels[$application['application_status']] ?? ucwords(str_replace('_', ' ', $application['application_status']))) ?></span>
                    </div>
                    <div class="candidate-application-metrics">
                        <div><span>Tanggal daftar</span><strong><?= esc($date($application['submitted_at'])) ?></strong></div>
                        <div><span>Hasil screening</span><strong class="candidate-screening-text screening-text-<?= esc($screening, 'attr') ?>"><?= $screening === 'passed' ? 'Lolos' : ($screening === 'failed' ? 'Tidak lolos' : 'Belum dinilai') ?></strong></div>
                        <div><span>Nilai screening</span><strong><?= $application['screening_score'] !== null ? esc(number_format((float) $application['screening_score'], 2, ',', '.')) : '-' ?></strong></div>
                        <div><span>Batch pendaftaran</span><strong><?= esc($application['batch_number']) ?></strong></div>
                    </div>
                    <div class="candidate-narrative-grid">
                        <div><span>Pengalaman kerja</span><p><?= nl2br(esc($value($application['work_experience']))) ?></p></div>
                        <div><span>Keahlian</span><p><?= nl2br(esc($value($application['skills']))) ?></p></div>
                        <div><span>Motivasi kerja</span><p><?= nl2br(esc($value($application['work_motivation']))) ?></p></div>
                        <div><span>Tujuan karier</span><p><?= nl2br(esc($value($application['career_goal']))) ?></p></div>
                    </div>
                    <?php if ($portfolioUrl !== ''): ?><div class="candidate-portfolio"><span>Portfolio</span><a href="<?= esc($portfolioUrl, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($portfolioUrl) ?></a></div><?php endif ?>

                    <div class="candidate-application-columns">
                        <div class="candidate-subsection"><div class="candidate-subsection-title"><h4>Jawaban screening</h4><span><?= count($applicationAnswers) ?> jawaban</span></div><div class="department-table-wrap"><table class="department-table candidate-answer-table"><thead><tr><th>Pertanyaan</th><th>Jawaban</th><th>Hasil</th><th>Nilai</th></tr></thead><tbody><?php if ($applicationAnswers === []): ?><tr><td colspan="4" class="department-empty">Tidak ada jawaban screening.</td></tr><?php endif ?><?php foreach ($applicationAnswers as $answer): ?><tr><td><?= esc($answer['question_text']) ?><?= (int) $answer['is_knockout'] === 1 ? '<small>Knockout</small>' : '' ?></td><td><strong><?= esc($answerValue($answer)) ?></strong></td><td><span class="account-status <?= (int) $answer['is_eligible'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $answer['is_eligible'] === 1 ? 'Sesuai' : 'Tidak sesuai' ?></span></td><td><?= $answer['score'] !== null ? esc(number_format((float) $answer['score'], 2, ',', '.')) : '-' ?></td></tr><?php endforeach ?></tbody></table></div></div>
                        <div class="candidate-subsection"><div class="candidate-subsection-title"><h4>Riwayat status</h4><span><?= count($applicationHistories) ?> aktivitas</span></div><div class="candidate-timeline"><?php if ($applicationHistories === []): ?><p class="candidate-empty">Belum ada perubahan status.</p><?php endif ?><?php foreach ($applicationHistories as $history): ?><article><i></i><div><strong><?= esc($statusLabels[$history['new_status']] ?? ucwords(str_replace('_', ' ', $history['new_status']))) ?></strong><p><?= esc($value($history['notes'])) ?></p><small><?= esc($date($history['created_at'])) ?> · <?= esc($history['changed_by_name'] ?: 'Sistem') ?></small></div></article><?php endforeach ?></div></div>
                    </div>
                </section>
            <?php endforeach ?>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
