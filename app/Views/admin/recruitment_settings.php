<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Template Penolakan | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=26">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'recruitment-settings']) ?>

        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
                <div><span>Recruitment</span><strong>Template Penolakan</strong></div>
                <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
            </header>

            <div class="admin-content recruitment-settings-content">
                <?php if (! empty($success)): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
                <?php if (! empty($error)): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

                <section class="dashboard-welcome recruitment-settings-heading" aria-labelledby="settings-title">
                    <div><span class="login-eyebrow">Pesan Kandidat</span><h1 id="settings-title">Template Penolakan</h1><p>Kelola pesan standar yang digunakan ketika kandidat tidak dilanjutkan.</p></div>
                    <?php if (! $canManage): ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
                </section>

                <nav class="settings-anchor-nav" aria-label="Bagian template penolakan">
                    <a href="#rejections">Alasan penolakan</a><a href="<?= site_url('adminhrdmannakampus/pertanyaan-screening') ?>">Pertanyaan screening</a><a href="<?= site_url('adminhrdmannakampus/template-tahapan') ?>">Template tahapan</a>
                </nav>

                <section class="settings-card recruitment-config-card" id="rejections" aria-labelledby="rejections-title">
                    <div class="settings-card-heading settings-heading-action">
                        <span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v14H5zM8 9h8M8 13h6"/></svg></span>
                        <div><h2 id="rejections-title">Template alasan penolakan</h2><p>Pesan standar yang dapat dipilih saat menolak kandidat.</p></div>
                        <div class="settings-heading-tools"><span class="device-count"><?= count($rejectionTemplates) ?></span><?php if ($canManage): ?><button type="button" data-admin-modal-open="create-rejection-modal">+ Tambah template</button><?php endif ?></div>
                    </div>
                    <div class="department-table-wrap"><table class="department-table recruitment-table">
                        <thead><tr><th>Urutan</th><th>Template</th><th>Alasan penolakan</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody><?php foreach ($rejectionTemplates as $template): ?>
                            <tr class="<?= ! $template['is_active'] ? 'department-row-inactive' : '' ?>"><td><?= esc((string) $template['display_order']) ?></td><td><div class="department-name-cell"><strong><?= esc($template['title']) ?></strong></div></td><td class="department-description-cell"><?= esc($template['reason_text']) ?></td><td><span class="account-status <?= $template['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $template['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><button class="settings-edit-trigger table-action-icon table-action-edit" type="button" data-admin-modal-open="edit-rejection-modal-<?= (int) $template['id'] ?>" aria-label="Edit template penolakan" title="Edit template penolakan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan/' . $template['id'] . '/hapus') ?>" method="post" data-confirm="Hapus template alasan penolakan ini?"><?= csrf_field() ?><button class="department-delete-button table-action-icon table-action-delete" type="submit" aria-label="Hapus template penolakan" title="Hapus template penolakan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr>
                        <?php endforeach ?></tbody>
                    </table></div>
                    <?php if ($canManage): ?>
                        <dialog class="admin-modal settings-form-modal" id="create-rejection-modal" aria-labelledby="create-rejection-title" <?= $openSettingsModal === 'rejection-create' ? 'data-auto-open' : '' ?>>
                            <div class="admin-modal-panel">
                                <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="create-rejection-title">Tambah template penolakan</h2><p>Buat pesan standar untuk kandidat yang tidak dilanjutkan.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                                <form class="department-table-edit-form settings-modal-form rejection-modal-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan') ?>" method="post">
                                    <?= csrf_field() ?><input type="hidden" name="settings_form" value="rejection-create">
                                    <label>Judul<input type="text" name="title" value="<?= $openSettingsModal === 'rejection-create' ? esc((string) old('title'), 'attr') : '' ?>" maxlength="150" required autofocus></label>
                                    <label>Urutan<input type="number" name="display_order" value="<?= $openSettingsModal === 'rejection-create' ? esc((string) old('display_order'), 'attr') : count($rejectionTemplates) + 1 ?>" min="1" max="999" required></label>
                                    <label class="settings-modal-wide">Isi alasan<textarea name="reason_text" rows="5" maxlength="1000" required><?= $openSettingsModal === 'rejection-create' ? esc((string) old('reason_text')) : '' ?></textarea></label>
                                    <div class="screening-flags settings-modal-wide"><label><input type="checkbox" name="is_active" value="1" <?= $openSettingsModal === 'rejection-create' ? (old('is_active') === '1' ? 'checked' : '') : 'checked' ?>> Aktif</label></div>
                                    <div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Tambah template</button></div>
                                </form>
                            </div>
                        </dialog>
                        <?php foreach ($rejectionTemplates as $template): ?><?php $rejectionModalKey = 'rejection-edit-' . $template['id']; ?>
                            <dialog class="admin-modal settings-form-modal" id="edit-rejection-modal-<?= (int) $template['id'] ?>" aria-labelledby="edit-rejection-title-<?= (int) $template['id'] ?>" <?= $openSettingsModal === $rejectionModalKey ? 'data-auto-open' : '' ?>>
                                <div class="admin-modal-panel">
                                    <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l11-11-4-4L4 16v4ZM13 7l4 4"/></svg></span><div><h2 id="edit-rejection-title-<?= (int) $template['id'] ?>">Edit template penolakan</h2><p><?= esc($template['title']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                                    <form class="department-table-edit-form settings-modal-form rejection-modal-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan/' . $template['id']) ?>" method="post">
                                        <?= csrf_field() ?><input type="hidden" name="settings_form" value="<?= esc($rejectionModalKey, 'attr') ?>">
                                        <label>Judul<input type="text" name="title" value="<?= esc((string) ($openSettingsModal === $rejectionModalKey ? old('title') : $template['title']), 'attr') ?>" maxlength="150" required></label>
                                        <label>Urutan<input type="number" name="display_order" value="<?= esc((string) ($openSettingsModal === $rejectionModalKey ? old('display_order') : $template['display_order']), 'attr') ?>" min="1" max="999" required></label>
                                        <label class="settings-modal-wide">Isi alasan<textarea name="reason_text" rows="5" maxlength="1000" required><?= esc((string) ($openSettingsModal === $rejectionModalKey ? old('reason_text') : $template['reason_text'])) ?></textarea></label>
                                        <div class="screening-flags settings-modal-wide"><label><input type="checkbox" name="is_active" value="1" <?= ($openSettingsModal === $rejectionModalKey ? old('is_active') === '1' : (bool) $template['is_active']) ? 'checked' : '' ?>> Aktif</label></div>
                                        <div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div>
                                    </form>
                                </div>
                            </dialog>
                        <?php endforeach ?>
                    <?php endif ?>
                </section>

                <?php if (false): ?><section class="settings-card recruitment-config-card" id="screening" aria-labelledby="screening-title">
                    <div class="settings-card-heading settings-heading-action">
                        <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span>
                        <div><h2 id="screening-title">Pertanyaan screening default</h2><p>Bank pertanyaan untuk digunakan ketika membuat lowongan baru.</p></div>
                        <div class="settings-heading-tools"><span class="device-count"><?= count($screeningQuestions) ?></span><?php if ($canManage): ?><button type="button" data-admin-modal-open="create-screening-modal">+ Tambah pertanyaan</button><?php endif ?></div>
                    </div>
                    <div class="department-table-wrap"><table class="department-table recruitment-table screening-table">
                        <thead><tr><th>Urutan</th><th>Pertanyaan</th><th>Tipe</th><th>Evaluasi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody><?php foreach ($screeningQuestions as $question): ?>
                            <?php $decodedOptions = json_decode((string) ($question['answer_options'] ?? ''), true); $optionsText = is_array($decodedOptions) ? implode(', ', $decodedOptions) : ''; ?>
                            <tr class="<?= ! $question['is_active'] ? 'department-row-inactive' : '' ?>"><td><?= esc((string) $question['display_order']) ?></td><td><div class="department-name-cell screening-question-cell"><strong><?= esc($question['question_text']) ?></strong><code><?= esc($question['question_code']) ?></code></div></td><td><span class="screening-type-badge"><?= esc($question['answer_type']) ?></span></td><td class="screening-evaluation-cell"><?= $question['is_required'] ? 'Wajib' : 'Opsional' ?><?= $question['is_knockout'] ? ' · Knockout' : '' ?><?php if (! empty($question['expected_value'])): ?><small>Jawaban: <?= esc($question['expected_value']) ?></small><?php endif ?></td><td><span class="account-status <?= $question['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $question['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><button class="settings-edit-trigger table-action-icon table-action-edit" type="button" data-admin-modal-open="edit-screening-modal-<?= (int) $question['id'] ?>" aria-label="Edit pertanyaan screening" title="Edit pertanyaan screening"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening/' . $question['id'] . '/hapus') ?>" method="post" data-confirm="Hapus pertanyaan screening ini?"><?= csrf_field() ?><button class="department-delete-button table-action-icon table-action-delete" type="submit" aria-label="Hapus pertanyaan screening" title="Hapus pertanyaan screening"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr>
                        <?php endforeach ?></tbody>
                    </table></div>
                    <?php if ($canManage): ?>
                        <?php
                            $answerTypes = ['text' => 'Teks', 'number' => 'Angka', 'yes_no' => 'Ya / Tidak', 'choice' => 'Pilihan'];
                            $operators = ['' => 'Tanpa evaluasi', 'equals' => 'Sama dengan', 'between' => 'Di antara', 'greater_than_or_equal' => 'Minimal', 'minimum_education' => 'Pendidikan minimal'];
                        ?>
                        <dialog class="admin-modal settings-form-modal" id="create-screening-modal" aria-labelledby="create-screening-title" <?= $openSettingsModal === 'screening-create' ? 'data-auto-open' : '' ?>>
                            <div class="admin-modal-panel">
                                <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="create-screening-title">Tambah pertanyaan screening</h2><p>Buat pertanyaan default untuk lowongan baru.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                                <form class="department-table-edit-form screening-table-edit-form settings-modal-form screening-modal-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening') ?>" method="post">
                                    <?= csrf_field() ?><input type="hidden" name="settings_form" value="screening-create">
                                    <label class="screening-edit-question">Pertanyaan<input type="text" name="question_text" value="<?= $openSettingsModal === 'screening-create' ? esc((string) old('question_text'), 'attr') : '' ?>" maxlength="500" required autofocus></label>
                                    <label>Tipe<select name="answer_type"><?php foreach ($answerTypes as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= ($openSettingsModal === 'screening-create' ? old('answer_type') : 'text') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                                    <label>Operator<select name="comparison_operator"><?php foreach ($operators as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= ($openSettingsModal === 'screening-create' ? (string) old('comparison_operator') : '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                                    <label>Jawaban harapan<input type="text" name="expected_value" value="<?= $openSettingsModal === 'screening-create' ? esc((string) old('expected_value'), 'attr') : '' ?>" maxlength="255"></label>
                                    <label>Opsi pilihan<input type="text" name="answer_options" value="<?= $openSettingsModal === 'screening-create' ? esc((string) old('answer_options'), 'attr') : '' ?>" placeholder="Pisahkan dengan koma"></label>
                                    <label>Urutan<input type="number" name="display_order" value="<?= $openSettingsModal === 'screening-create' ? esc((string) old('display_order'), 'attr') : count($screeningQuestions) + 1 ?>" min="1" max="999" required></label>
                                    <div class="screening-flags settings-modal-wide"><label><input type="checkbox" name="is_required" value="1" <?= $openSettingsModal === 'screening-create' ? (old('is_required') === '1' ? 'checked' : '') : 'checked' ?>> Wajib</label><label><input type="checkbox" name="is_knockout" value="1" <?= $openSettingsModal === 'screening-create' && old('is_knockout') === '1' ? 'checked' : '' ?>> Knockout</label><label><input type="checkbox" name="is_active" value="1" <?= $openSettingsModal === 'screening-create' ? (old('is_active') === '1' ? 'checked' : '') : 'checked' ?>> Aktif</label></div>
                                    <div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Tambah pertanyaan</button></div>
                                </form>
                            </div>
                        </dialog>
                        <?php foreach ($screeningQuestions as $question): ?>
                            <?php $screeningModalKey = 'screening-edit-' . $question['id']; $decodedOptions = json_decode((string) ($question['answer_options'] ?? ''), true); $optionsText = is_array($decodedOptions) ? implode(', ', $decodedOptions) : ''; ?>
                            <dialog class="admin-modal settings-form-modal" id="edit-screening-modal-<?= (int) $question['id'] ?>" aria-labelledby="edit-screening-title-<?= (int) $question['id'] ?>" <?= $openSettingsModal === $screeningModalKey ? 'data-auto-open' : '' ?>>
                                <div class="admin-modal-panel">
                                    <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l11-11-4-4L4 16v4ZM13 7l4 4"/></svg></span><div><h2 id="edit-screening-title-<?= (int) $question['id'] ?>">Edit pertanyaan screening</h2><p><?= esc($question['question_code']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
                                    <form class="department-table-edit-form screening-table-edit-form settings-modal-form screening-modal-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening/' . $question['id']) ?>" method="post">
                                        <?= csrf_field() ?><input type="hidden" name="settings_form" value="<?= esc($screeningModalKey, 'attr') ?>">
                                        <label class="screening-edit-question">Pertanyaan<input type="text" name="question_text" value="<?= esc((string) ($openSettingsModal === $screeningModalKey ? old('question_text') : $question['question_text']), 'attr') ?>" maxlength="500" required></label>
                                        <label>Tipe<select name="answer_type"><?php foreach ($answerTypes as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= ($openSettingsModal === $screeningModalKey ? old('answer_type') : $question['answer_type']) === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                                        <label>Operator<select name="comparison_operator"><?php foreach ($operators as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= ($openSettingsModal === $screeningModalKey ? (string) old('comparison_operator') : (string) ($question['comparison_operator'] ?? '')) === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                                        <label>Jawaban harapan<input type="text" name="expected_value" value="<?= esc((string) ($openSettingsModal === $screeningModalKey ? old('expected_value') : ($question['expected_value'] ?? '')), 'attr') ?>" maxlength="255"></label>
                                        <label>Opsi pilihan<input type="text" name="answer_options" value="<?= esc((string) ($openSettingsModal === $screeningModalKey ? old('answer_options') : $optionsText), 'attr') ?>" placeholder="Pisahkan dengan koma"></label>
                                        <label>Urutan<input type="number" name="display_order" value="<?= esc((string) ($openSettingsModal === $screeningModalKey ? old('display_order') : $question['display_order']), 'attr') ?>" min="1" max="999" required></label>
                                        <div class="screening-flags settings-modal-wide"><label><input type="checkbox" name="is_required" value="1" <?= ($openSettingsModal === $screeningModalKey ? old('is_required') === '1' : (bool) $question['is_required']) ? 'checked' : '' ?>> Wajib</label><label><input type="checkbox" name="is_knockout" value="1" <?= ($openSettingsModal === $screeningModalKey ? old('is_knockout') === '1' : (bool) $question['is_knockout']) ? 'checked' : '' ?>> Knockout</label><label><input type="checkbox" name="is_active" value="1" <?= ($openSettingsModal === $screeningModalKey ? old('is_active') === '1' : (bool) $question['is_active']) ? 'checked' : '' ?>> Aktif</label></div>
                                        <div class="department-modal-actions settings-modal-wide"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div>
                                    </form>
                                </div>
                            </dialog>
                        <?php endforeach ?>
                    <?php endif ?>
                </section><?php endif ?>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=5" defer></script>
</body>
</html>
