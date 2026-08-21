<?php
$isEdit = $vacancy !== null;
$value = static function (string $key, mixed $default = '') use ($vacancy): mixed {
    $old = old($key);

    return $old !== null ? $old : ($vacancy[$key] ?? $default);
};
$dateTimeLocal = static fn (?string $date): string => $date ? date('Y-m-d\TH:i', strtotime($date)) : '';
$displayDate = static fn (?string $date): string => $date ? date('d/m/Y H:i', strtotime($date)) : 'Tanpa batas';
$statusLabels = ['draft' => 'Draft', 'open' => 'Dibuka', 'closed' => 'Ditutup', 'archived' => 'Diarsipkan'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title><?= $isEdit ? 'Edit ' . esc($vacancy['title']) : 'Tambah Lowongan' ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=36">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'vacancies']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span></span><span></span><span></span></button>
            <div><span>Recruitment</span><strong><?= $isEdit ? 'Detail & Edit Lowongan' : 'Tambah Lowongan' ?></strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/lowongan') ?>">Kembali ke daftar</a>
        </header>
        <div class="admin-content vacancy-form-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading">
                <div><span class="login-eyebrow">Vacancy Editor</span><h1><?= $isEdit ? esc($vacancy['title']) : 'Lowongan Baru' ?></h1><p>Lengkapi informasi posisi, publikasi, sesi, dan screening dalam satu detail lowongan.</p></div>
                <?php if ($isEdit): ?><span class="vacancy-status status-<?= esc($vacancy['status'], 'attr') ?>"><?= esc($statusLabels[$vacancy['status']] ?? $vacancy['status']) ?></span><?php endif ?>
            </section>

            <form class="vacancy-main-form" action="<?= $isEdit ? site_url('adminhrdmannakampus/lowongan/' . $vacancy['id']) : site_url('adminhrdmannakampus/lowongan') ?>" method="post">
                <?= csrf_field() ?>
                <section class="settings-card vacancy-form-card">
                    <div class="settings-card-heading"><span class="settings-icon"><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5zM8 9h8M8 13h6"/></svg></span><div><h2>Informasi utama</h2><p>Identitas, departemen, dan tahapan lowongan.</p></div></div>
                    <div class="vacancy-form-grid">
                        <label>Judul posisi<input name="title" value="<?= esc((string) $value('title'), 'attr') ?>" maxlength="150" required><small><?= $isEdit ? 'Alamat lamaran: /lowongan/' . esc((string) $vacancy['code']) . '/lamar' : 'Alamat lamaran dibuat otomatis dari judul posisi.' ?></small></label>
                        <label>Departemen<select name="department_id" required><option value="">Pilih departemen</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) $value('department_id') === (int) $department['id'] ? 'selected' : '' ?>><?= esc($department['name']) ?></option><?php endforeach ?></select></label>
                        <label>Template tahapan<select name="recruitment_process_template_id" required><option value="">Pilih template tahapan</option><?php foreach ($processTemplates as $template): ?><option value="<?= (int) $template['id'] ?>" <?= (int) $value('recruitment_process_template_id') === (int) $template['id'] ? 'selected' : '' ?>><?= esc($template['name']) ?></option><?php endforeach ?></select><small>Menentukan urutan seleksi kandidat untuk lowongan ini.</small></label>
                        <label class="vacancy-wide-field">Ringkasan<input name="summary" value="<?= esc((string) $value('summary'), 'attr') ?>" maxlength="500" placeholder="Ringkasan singkat untuk kartu lowongan"></label>
                    </div>
                </section>

                <section class="settings-card vacancy-form-card">
                    <div class="settings-card-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8"/></svg></span><div><h2>Deskripsi pekerjaan</h2><p>Informasi yang akan dibaca calon pelamar.</p></div></div>
                    <div class="vacancy-text-fields">
                        <label>Deskripsi pekerjaan<textarea name="job_description" rows="5" maxlength="10000"><?= esc((string) $value('job_description')) ?></textarea></label>
                        <label>Tugas dan tanggung jawab<textarea name="responsibilities" rows="6" maxlength="10000" placeholder="Satu poin per baris"><?= esc((string) $value('responsibilities')) ?></textarea></label>
                        <label>Kualifikasi<textarea name="qualifications" rows="6" maxlength="10000" placeholder="Satu poin per baris"><?= esc((string) $value('qualifications')) ?></textarea></label>
                    </div>
                </section>

                <section class="settings-card vacancy-form-card">
                    <div class="settings-card-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 12h16M12 4v16"/></svg></span><div><h2>Persyaratan dan kebutuhan</h2><p>Detail penempatan, pendidikan, usia, dan kompensasi.</p></div></div>
                    <div class="vacancy-form-grid">
                        <label>Lokasi<input name="location" value="<?= esc((string) $value('location', 'Yogyakarta'), 'attr') ?>" maxlength="100"></label>
                        <label>Tipe pekerjaan<select name="employment_type"><option value="">Pilih tipe</option><?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $type): ?><option value="<?= $type ?>" <?= $value('employment_type') === $type ? 'selected' : '' ?>><?= $type ?></option><?php endforeach ?></select></label>
                        <label>Pendidikan minimum<input name="minimum_education" value="<?= esc((string) $value('minimum_education'), 'attr') ?>" maxlength="50" placeholder="SMA/SMK, D3, S1"></label>
                        <label>Usia minimum<input type="number" name="minimum_age" value="<?= esc((string) $value('minimum_age'), 'attr') ?>" min="15" max="80"></label>
                        <label>Usia maksimum<input type="number" name="maximum_age" value="<?= esc((string) $value('maximum_age'), 'attr') ?>" min="15" max="80"></label>
                        <label><?= $isEdit ? 'Kebutuhan default' : 'Jumlah kebutuhan sesi awal' ?><input type="number" name="headcount" value="<?= esc((string) $value('headcount', 1), 'attr') ?>" min="1" max="9999" required></label>
                        <label>Gaji minimum<input type="number" name="salary_min" value="<?= esc((string) $value('salary_min'), 'attr') ?>" min="0" step="1000"></label>
                        <label>Gaji maksimum<input type="number" name="salary_max" value="<?= esc((string) $value('salary_max'), 'attr') ?>" min="0" step="1000"></label>
                        <label class="vacancy-checkbox"><input type="checkbox" name="show_salary" value="1" <?= (int) $value('show_salary', 0) === 1 ? 'checked' : '' ?>> Tampilkan gaji di halaman karier</label>
                    </div>
                </section>

                <section class="settings-card vacancy-form-card">
                    <div class="settings-card-heading"><span class="settings-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg></span><div><h2><?= $isEdit ? 'Publikasi dan sesi' : 'Sesi awal' ?></h2><p><?= $isEdit ? 'Ringkasan seluruh periode publikasi lowongan ini.' : 'Buat periode pertama bersamaan dengan lowongan ini.' ?></p></div><?php if ($isEdit): ?><span class="device-count"><?= count($vacancyPeriods) ?></span><?php endif ?></div>
                    <?php if (! $isEdit): ?>
                        <div class="vacancy-form-grid">
                            <label>Mulai ditampilkan<input type="datetime-local" name="opened_at" value="<?= esc($dateTimeLocal($value('opened_at') ?: null), 'attr') ?>"></label>
                            <label>Batas akhir<input type="datetime-local" name="closed_at" value="<?= esc($dateTimeLocal($value('closed_at') ?: null), 'attr') ?>"></label>
                            <?php if ($canPublish): ?><label>Status sesi<select name="status"><?php foreach ($statusLabels as $status => $label): ?><option value="<?= $status ?>" <?= $value('status', 'draft') === $status ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><?php else: ?><input type="hidden" name="status" value="draft"><?php endif ?>
                            <label class="vacancy-checkbox vacancy-wide-field"><input type="checkbox" name="use_default_screening" value="1" checked> Salin pertanyaan screening default setelah lowongan dibuat</label>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="opened_at" value="<?= esc($dateTimeLocal($value('opened_at') ?: null), 'attr') ?>">
                        <input type="hidden" name="closed_at" value="<?= esc($dateTimeLocal($value('closed_at') ?: null), 'attr') ?>">
                        <input type="hidden" name="status" value="<?= esc((string) $value('status', 'draft'), 'attr') ?>">
                        <div class="vacancy-period-list">
                            <?php if ($vacancyPeriods === []): ?><p class="department-empty">Belum ada sesi untuk lowongan ini.</p><?php endif ?>
                            <?php foreach ($vacancyPeriods as $period): ?>
                                <article>
                                    <div><strong><?= esc($period['period_name']) ?></strong></div>
                                    <span><small>Mulai</small><strong><?= esc($displayDate($period['opened_at'])) ?></strong></span>
                                    <span><small>Selesai</small><strong><?= esc($displayDate($period['closed_at'])) ?></strong></span>
                                    <span><small>Kebutuhan</small><strong><?= (int) $period['headcount'] ?> orang</strong></span>
                                    <b class="vacancy-status status-<?= esc($period['status'], 'attr') ?>"><?= esc($statusLabels[$period['status']] ?? $period['status']) ?></b>
                                </article>
                            <?php endforeach ?>
                        </div>
                        <?php if ($canViewVacancyPeriods): ?><a class="vacancy-detail-manage-link" href="<?= site_url('adminhrdmannakampus/sesi-lowongan?vacancy_id=' . $vacancy['id']) ?>">Kelola publikasi dan sesi</a><?php endif ?>
                    <?php endif ?>
                </section>

                <?php if ($isEdit && $canViewScreeningQuestions): ?>
                    <section class="settings-card vacancy-form-card">
                        <div class="settings-card-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8"/></svg></span><div><h2>Screening lowongan</h2><p>Pertanyaan yang akan digunakan pada screening posisi ini.</p></div><span class="device-count"><?= count($questions) ?></span></div>
                        <div class="vacancy-screening-preview">
                            <?php if ($questions === []): ?><p class="department-empty">Belum ada pertanyaan screening.</p><?php endif ?>
                            <?php foreach ($questions as $index => $question): ?>
                                <article class="<?= ! $question['is_active'] ? 'inactive' : '' ?>">
                                    <span><?= $index + 1 ?></span>
                                    <div><strong><?= esc($question['question_text']) ?></strong><small><?= esc($question['answer_type']) ?><?= $question['is_required'] ? ' · Wajib' : ' · Opsional' ?><?= $question['is_knockout'] ? ' · Knockout' : '' ?></small></div>
                                    <b><?= $question['is_active'] ? 'Aktif' : 'Nonaktif' ?></b>
                                </article>
                            <?php endforeach ?>
                        </div>
                        <a class="vacancy-detail-manage-link" href="<?= site_url('adminhrdmannakampus/pertanyaan-screening?vacancy_id=' . $vacancy['id'] . '#vacancy-questions') ?>">Kelola pertanyaan screening</a>
                    </section>
                <?php endif ?>

                <section class="settings-card vacancy-form-actions-card">
                    <p><?= $isEdit ? 'Pastikan seluruh perubahan sudah benar sebelum disimpan.' : 'Pastikan seluruh data lowongan sudah lengkap.' ?></p>
                    <div class="vacancy-form-submit"><a href="<?= site_url('adminhrdmannakampus/lowongan') ?>">Batal</a><button type="submit"><?= $isEdit ? 'Simpan perubahan' : 'Buat lowongan' ?></button></div>
                </section>
            </form>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=7" defer></script>
</body>
</html>
