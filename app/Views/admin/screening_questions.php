<?php
$answerTypeLabels = [
    'text' => 'Teks', 'number' => 'Angka', 'boolean' => 'Ya/Tidak (1/0)',
    'yes_no' => 'Ya/Tidak', 'choice' => 'Pilihan', 'education_level' => 'Pendidikan',
];
$modalQuestion = static function (string $key, array $question = []) use ($openModal): array {
    if ($openModal !== $key) {
        return $question;
    }
    return [
        'question_text' => old('question_text'),
        'answer_type' => old('answer_type') ?: 'text',
        'comparison_operator' => old('comparison_operator'),
        'expected_value' => old('expected_value'),
        'display_order' => old('display_order'),
        'is_required' => old('is_required') === '1',
        'is_knockout' => old('is_knockout') === '1',
        'is_active' => old('is_active') === '1',
    ];
};
$modalOptions = static function (string $key, array $question = []) use ($openModal): string {
    if ($openModal === $key) {
        return (string) old('answer_options');
    }
    $decoded = json_decode((string) ($question['answer_options'] ?? ''), true);
    return is_array($decoded) ? implode(', ', $decoded) : '';
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Pertanyaan Screening | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=27">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'screening-questions']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Recruitment</span><strong>Pertanyaan Screening</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
        </header>

        <div class="admin-content screening-management-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading" aria-labelledby="screening-page-title">
                <div><span class="login-eyebrow">Screening Management</span><h1 id="screening-page-title">Pertanyaan Screening</h1><p>Kelola bank pertanyaan default dan pertanyaan khusus untuk setiap lowongan.</p></div>
            </section>

            <nav class="settings-anchor-nav" aria-label="Bagian pertanyaan screening">
                <a href="#default-questions">Bank pertanyaan default</a><a href="#vacancy-questions">Screening per lowongan</a>
            </nav>

            <section class="settings-card recruitment-config-card" id="default-questions" aria-labelledby="default-title">
                <div class="settings-card-heading settings-heading-action">
                    <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span>
                    <div><h2 id="default-title">Bank pertanyaan default</h2><p>Template aktif dapat disalin ke lowongan. Perubahan tidak mengubah salinan lama.</p></div>
                    <div class="settings-heading-tools"><span class="device-count"><?= count($defaultQuestions) ?></span><?php if ($canManageDefaults): ?><button type="button" data-admin-modal-open="default-create-modal">+ Tambah default</button><?php endif ?></div>
                </div>
                <div class="department-table-wrap"><table class="department-table screening-management-table">
                    <thead><tr><th>No.</th><th>Pertanyaan</th><th>Urutan</th><th>Tipe</th><th>Evaluasi</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php if ($defaultQuestions === []): ?><tr><td class="department-empty" colspan="7">Belum ada pertanyaan screening default.</td></tr><?php endif ?>
                    <?php foreach ($defaultQuestions as $defaultIndex => $question): ?>
                        <tr class="<?= ! $question['is_active'] ? 'department-row-inactive' : '' ?>">
                            <td><?= $defaultIndex + 1 ?></td>
                            <td><div class="department-name-cell screening-question-cell"><strong><?= esc($question['question_text']) ?></strong><code><?= esc($question['question_code']) ?></code></div></td>
                            <td><?= (int) $question['display_order'] ?></td>
                            <td><span class="screening-type-badge"><?= esc($answerTypeLabels[$question['answer_type']] ?? $question['answer_type']) ?></span></td>
                            <td class="screening-evaluation-cell"><?= $question['is_required'] ? 'Wajib' : 'Opsional' ?><?= $question['is_knockout'] ? ' · Knockout' : '' ?><small><?= esc((string) ($question['expected_value'] ?: 'Tanpa jawaban harapan')) ?></small></td>
                            <td><span class="account-status <?= $question['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $question['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td><div class="department-table-actions"><?php if ($canManageDefaults): ?><button class="settings-edit-trigger table-action-icon table-action-edit" type="button" data-admin-modal-open="default-edit-modal-<?= (int) $question['id'] ?>" aria-label="Edit pertanyaan default" title="Edit pertanyaan default"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/default/' . $question['id'] . '/hapus') ?>" method="post" data-confirm="Hapus permanen pertanyaan default ini? Salinan yang sudah ada pada lowongan tetap disimpan sebagai pertanyaan khusus."><?= csrf_field() ?><button class="department-delete-button table-action-icon table-action-delete" type="submit" aria-label="Hapus pertanyaan default" title="Hapus pertanyaan default"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table></div>
            </section>

            <section class="settings-card recruitment-config-card" id="vacancy-questions" aria-labelledby="vacancy-screening-title">
                <div class="settings-card-heading">
                    <span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg></span>
                    <div><h2 id="vacancy-screening-title">Screening per lowongan</h2><p>Pilih lowongan untuk melihat pertanyaan default yang disalin dan pertanyaan khususnya.</p></div>
                </div>
                <form class="screening-vacancy-picker" action="<?= site_url('adminhrdmannakampus/pertanyaan-screening') ?>#vacancy-questions" method="get">
                    <label for="vacancy_id">Lowongan</label>
                    <select id="vacancy_id" name="vacancy_id" required><option value="">Pilih lowongan</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $selectedVacancyId === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?> — <?= esc($vacancy['department_name'] ?: 'Tanpa departemen') ?></option><?php endforeach ?></select>
                    <button type="submit">Tampilkan</button>
                </form>

                <?php if ($selectedVacancy): ?>
                    <div class="screening-vacancy-toolbar">
                        <div><strong><?= esc($selectedVacancy['title']) ?></strong><span><?= esc($selectedVacancy['code']) ?> · <?= esc($selectedVacancy['department_name'] ?: 'Tanpa departemen') ?></span></div>
                        <?php if ($canManageVacancyQuestions): ?>
                            <button type="button" data-admin-modal-open="vacancy-copy-modal">Salin dari default</button>
                            <button type="button" data-admin-modal-open="vacancy-create-modal">+ Pertanyaan khusus</button>
                        <?php endif ?>
                    </div>
                    <div class="department-table-wrap"><table class="department-table screening-management-table">
                        <thead><tr><th>No.</th><th>Pertanyaan</th><th>Urutan</th><th>Tipe</th><th>Evaluasi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php if ($vacancyQuestions === []): ?><tr><td class="department-empty" colspan="7">Lowongan ini belum memiliki pertanyaan screening.</td></tr><?php endif ?>
                        <?php foreach ($vacancyQuestions as $vacancyIndex => $question): ?>
                            <tr class="<?= ! $question['is_active'] ? 'department-row-inactive' : '' ?>">
                                <td><?= $vacancyIndex + 1 ?></td>
                                <td><div class="department-name-cell screening-question-cell"><strong><?= esc($question['question_text']) ?></strong><code><?= esc($question['question_code']) ?></code></div></td>
                                <td><?= (int) $question['display_order'] ?></td>
                                <td><span class="screening-type-badge"><?= esc($answerTypeLabels[$question['answer_type']] ?? $question['answer_type']) ?></span></td>
                                <td class="screening-evaluation-cell"><?= $question['is_required'] ? 'Wajib' : 'Opsional' ?><?= $question['is_knockout'] ? ' · Knockout' : '' ?><small><?= esc((string) ($question['expected_value'] ?: 'Tanpa jawaban harapan')) ?></small></td>
                                <td><span class="account-status <?= $question['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $question['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td><div class="department-table-actions"><?php if ($canManageVacancyQuestions): ?><button class="settings-edit-trigger table-action-icon table-action-edit" type="button" data-admin-modal-open="vacancy-edit-modal-<?= (int) $question['id'] ?>" aria-label="Edit pertanyaan lowongan" title="Edit pertanyaan lowongan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/lowongan/' . $selectedVacancyId . '/' . $question['id'] . '/hapus') ?>" method="post" data-confirm="Hapus permanen pertanyaan ini? Seluruh jawaban kandidat yang terhubung juga akan terhapus."><?= csrf_field() ?><button class="department-delete-button table-action-icon table-action-delete" type="submit" aria-label="Hapus pertanyaan lowongan" title="Hapus pertanyaan lowongan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table></div>
                <?php else: ?>
                    <div class="screening-select-empty">Pilih lowongan terlebih dahulu untuk mengelola pertanyaan khusus.</div>
                <?php endif ?>
            </section>
        </div>
    </main>
</div>

<?php if ($canManageDefaults): ?>
    <?php $createDefault = $modalQuestion('default-create', ['display_order' => count($defaultQuestions) + 1]); ?>
    <dialog class="admin-modal settings-form-modal" id="default-create-modal" aria-labelledby="default-create-title" <?= $openModal === 'default-create' ? 'data-auto-open' : '' ?>>
        <div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="default-create-title">Tambah pertanyaan default</h2><p>Template ini dapat disalin ke semua lowongan.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
            <form class="department-table-edit-form screening-table-edit-form settings-modal-form screening-modal-form" action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/default') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="screening_form" value="default-create"><?= view('admin/partials/screening_question_fields', ['question' => $createDefault, 'optionText' => $modalOptions('default-create')]) ?><div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Tambah default</button></div></form>
        </div>
    </dialog>
    <?php foreach ($defaultQuestions as $question): ?><?php $key = 'default-edit-' . $question['id']; $formQuestion = $modalQuestion($key, $question); ?>
        <dialog class="admin-modal settings-form-modal" id="default-edit-modal-<?= (int) $question['id'] ?>" aria-labelledby="default-edit-title-<?= (int) $question['id'] ?>" <?= $openModal === $key ? 'data-auto-open' : '' ?>>
            <div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 20h4l11-11-4-4L4 16v4Z"/></svg></span><div><h2 id="default-edit-title-<?= (int) $question['id'] ?>">Edit pertanyaan default</h2><p><?= esc($question['question_code']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                <form class="department-table-edit-form screening-table-edit-form settings-modal-form screening-modal-form" action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/default/' . $question['id']) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="screening_form" value="<?= esc($key, 'attr') ?>"><?= view('admin/partials/screening_question_fields', ['question' => $formQuestion, 'optionText' => $modalOptions($key, $question)]) ?><div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div></form>
            </div>
        </dialog>
    <?php endforeach ?>
<?php endif ?>

<?php if ($canManageVacancyQuestions && $selectedVacancy): ?>
    <dialog class="admin-modal settings-form-modal" id="vacancy-copy-modal" aria-labelledby="vacancy-copy-title" <?= $openModal === 'vacancy-copy' ? 'data-auto-open' : '' ?>>
        <div class="admin-modal-panel">
            <div class="settings-card-heading admin-modal-heading">
                <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h11v11H8z"/><path d="M5 16H4V4h12v1"/></svg></span>
                <div><h2 id="vacancy-copy-title">Salin pertanyaan default</h2><p>Pilih pertanyaan untuk <?= esc($selectedVacancy['title']) ?>.</p></div>
                <button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button>
            </div>
            <form class="settings-modal-form screening-copy-form" action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/lowongan/' . $selectedVacancyId . '/salin-default') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="screening_form" value="vacancy-copy">
                <fieldset class="screening-copy-list">
                    <legend>Pertanyaan yang akan disalin</legend>
                    <div class="department-table-wrap screening-copy-table-wrap">
                        <table class="department-table screening-copy-table">
                            <thead><tr><th><label class="screening-check-all"><input type="checkbox" data-check-all="default-question-copy" <?= ! array_filter($copyableDefaultQuestions, static fn (array $question): bool => $question['is_copyable']) ? 'disabled' : '' ?>><span>Semua</span></label></th><th>Pertanyaan</th><th>Tipe</th><th>Urutan</th></tr></thead>
                            <tbody>
                            <?php if ($copyableDefaultQuestions === []): ?><tr><td class="department-empty" colspan="4">Belum ada pertanyaan default aktif.</td></tr><?php endif ?>
                            <?php foreach ($copyableDefaultQuestions as $question): ?>
                                <tr class="<?= ! $question['is_copyable'] ? 'department-row-inactive' : '' ?>">
                                    <td><input class="screening-copy-checkbox" type="checkbox" name="default_question_ids[]" value="<?= (int) $question['id'] ?>" data-check-item="default-question-copy" aria-label="Pilih <?= esc($question['question_text'], 'attr') ?>" <?= ! $question['is_copyable'] ? 'disabled' : '' ?>></td>
                                    <td><div class="department-name-cell screening-question-cell"><strong><?= esc($question['question_text']) ?></strong><code><?= esc($question['question_code']) ?></code></div></td>
                                    <td><span class="screening-type-badge"><?= esc($answerTypeLabels[$question['answer_type']] ?? $question['answer_type']) ?></span></td>
                                    <td><?= (int) $question['display_order'] ?></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </fieldset>
                <div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" <?= ! array_filter($copyableDefaultQuestions, static fn (array $question): bool => $question['is_copyable']) ? 'disabled' : '' ?>>Salin pertanyaan terpilih</button></div>
            </form>
        </div>
    </dialog>
    <?php $createVacancy = $modalQuestion('vacancy-create', ['display_order' => count($vacancyQuestions) + 1]); ?>
    <dialog class="admin-modal settings-form-modal" id="vacancy-create-modal" aria-labelledby="vacancy-create-title" <?= $openModal === 'vacancy-create' ? 'data-auto-open' : '' ?>>
        <div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="vacancy-create-title">Tambah pertanyaan khusus</h2><p><?= esc($selectedVacancy['title']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
            <form class="department-table-edit-form screening-table-edit-form settings-modal-form screening-modal-form" action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/lowongan/' . $selectedVacancyId) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="screening_form" value="vacancy-create"><?= view('admin/partials/screening_question_fields', ['question' => $createVacancy, 'optionText' => $modalOptions('vacancy-create')]) ?><div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Tambah pertanyaan</button></div></form>
        </div>
    </dialog>
    <?php foreach ($vacancyQuestions as $question): ?><?php $key = 'vacancy-edit-' . $question['id']; $formQuestion = $modalQuestion($key, $question); ?>
        <dialog class="admin-modal settings-form-modal" id="vacancy-edit-modal-<?= (int) $question['id'] ?>" aria-labelledby="vacancy-edit-title-<?= (int) $question['id'] ?>" <?= $openModal === $key ? 'data-auto-open' : '' ?>>
            <div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M4 20h4l11-11-4-4L4 16v4Z"/></svg></span><div><h2 id="vacancy-edit-title-<?= (int) $question['id'] ?>">Edit screening lowongan</h2><p><?= $question['source_default_question_id'] ? 'Salinan default' : 'Pertanyaan khusus' ?> · <?= esc($selectedVacancy['title']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                <form class="department-table-edit-form screening-table-edit-form settings-modal-form screening-modal-form" action="<?= site_url('adminhrdmannakampus/pertanyaan-screening/lowongan/' . $selectedVacancyId . '/' . $question['id']) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="screening_form" value="<?= esc($key, 'attr') ?>"><?= view('admin/partials/screening_question_fields', ['question' => $formQuestion, 'optionText' => $modalOptions($key, $question)]) ?><div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div></form>
            </div>
        </dialog>
    <?php endforeach ?>
<?php endif ?>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>

<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=5" defer></script>
</body>
</html>
