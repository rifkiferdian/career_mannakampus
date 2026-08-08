<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Pengaturan Rekrutmen | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=21">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'recruitment-settings']) ?>

        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
                <div><span>Konfigurasi</span><strong>Pengaturan Rekrutmen</strong></div>
                <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
            </header>

            <div class="admin-content recruitment-settings-content">
                <?php if (! empty($success)): ?><div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div><?php endif ?>
                <?php if (! empty($error)): ?><div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div><?php endif ?>

                <section class="dashboard-welcome recruitment-settings-heading" aria-labelledby="settings-title">
                    <div><span class="login-eyebrow">Recruitment Workflow</span><h1 id="settings-title">Pengaturan Rekrutmen</h1><p>Atur tahapan seleksi, batas waktu proses, dan alasan penolakan kandidat.</p></div>
                    <?php if (! $canManage): ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
                </section>

                <nav class="settings-anchor-nav" aria-label="Bagian pengaturan rekrutmen">
                    <a href="#stages">Tahapan seleksi</a><a href="#rejections">Alasan penolakan</a><a href="<?= site_url('adminhrdmannakampus/pertanyaan-screening') ?>">Pertanyaan screening</a>
                </nav>

                <section class="settings-card recruitment-config-card" id="stages" aria-labelledby="stages-title">
                    <div class="settings-card-heading settings-heading-action">
                        <span class="settings-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h14"/><circle cx="8" cy="6" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="16" cy="18" r="1"/></svg></span>
                        <div><h2 id="stages-title">Tahapan seleksi</h2><p>Urutan, warna, dan SLA dihitung dalam hari kalender.</p></div>
                        <span class="device-count"><?= count($stages) ?></span>
                    </div>
                    <div class="department-table-wrap"><table class="department-table recruitment-table">
                        <thead><tr><th>Urutan</th><th>Tahapan</th><th>Warna</th><th>Batas waktu</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody><?php foreach ($stages as $stage): ?>
                            <tr class="<?= (int) $stage['is_active'] === 0 ? 'department-row-inactive' : '' ?>">
                                <td><?= esc((string) $stage['display_order']) ?></td>
                                <td><div class="department-name-cell"><strong><?= esc($stage['name']) ?></strong><code><?= esc($stage['code']) ?></code></div></td>
                                <td><span class="stage-color-value"><i style="--stage-color: <?= esc($stage['color_hex'], 'attr') ?>"></i><?= esc($stage['color_hex']) ?></span></td>
                                <td><?= (int) $stage['is_terminal'] === 1 ? '-' : esc((string) $stage['sla_days']) . ' hari' ?></td>
                                <td><span class="account-status <?= (int) $stage['is_active'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $stage['is_terminal'] === 1 ? 'Terminal' : ((int) $stage['is_active'] === 1 ? 'Aktif' : 'Nonaktif') ?></span></td>
                                <td><div class="department-table-actions"><?php if ($canManage): ?><a href="#edit-stage-<?= esc((string) $stage['id'], 'attr') ?>">Edit</a><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td>
                            </tr>
                            <?php if ($canManage): ?><tr class="department-edit-table-row" id="edit-stage-<?= esc((string) $stage['id'], 'attr') ?>"><td colspan="6">
                                <form class="department-table-edit-form recruitment-table-edit-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/tahapan/' . $stage['id']) ?>" method="post">
                                    <?= csrf_field() ?><label>Nama<input type="text" name="name" value="<?= esc($stage['name'], 'attr') ?>" maxlength="100" required></label><label>Warna<input type="color" name="color_hex" value="<?= esc($stage['color_hex'], 'attr') ?>" required></label><label>Urutan<input type="number" name="display_order" value="<?= esc((string) $stage['display_order'], 'attr') ?>" min="1" max="99" required></label><label>Batas waktu<input type="number" name="sla_days" value="<?= esc((string) $stage['sla_days'], 'attr') ?>" min="0" max="365" <?= (int) $stage['is_terminal'] === 1 ? 'disabled' : '' ?>></label><label class="department-active-check"><input type="checkbox" name="is_active" value="1" <?= (int) $stage['is_active'] === 1 ? 'checked' : '' ?> <?= (int) $stage['is_terminal'] === 1 ? 'disabled' : '' ?>> Aktif</label><div class="department-edit-buttons"><a href="#stages-title">Batal</a><button type="submit">Simpan</button></div>
                                </form>
                            </td></tr><?php endif ?>
                        <?php endforeach ?></tbody>
                    </table></div>
                </section>

                <section class="settings-card recruitment-config-card" id="rejections" aria-labelledby="rejections-title">
                    <div class="settings-card-heading settings-heading-action">
                        <span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v14H5zM8 9h8M8 13h6"/></svg></span>
                        <div><h2 id="rejections-title">Template alasan penolakan</h2><p>Pesan standar yang dapat dipilih saat menolak kandidat.</p></div>
                        <div class="settings-heading-tools"><span class="device-count"><?= count($rejectionTemplates) ?></span><?php if ($canManage): ?><button type="button" data-admin-modal-open="create-rejection-modal">+ Tambah template</button><?php endif ?></div>
                    </div>
                    <div class="department-table-wrap"><table class="department-table recruitment-table">
                        <thead><tr><th>Urutan</th><th>Template</th><th>Alasan penolakan</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody><?php foreach ($rejectionTemplates as $template): ?>
                            <tr class="<?= ! $template['is_active'] ? 'department-row-inactive' : '' ?>"><td><?= esc((string) $template['display_order']) ?></td><td><div class="department-name-cell"><strong><?= esc($template['title']) ?></strong></div></td><td class="department-description-cell"><?= esc($template['reason_text']) ?></td><td><span class="account-status <?= $template['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $template['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><button class="settings-edit-trigger" type="button" data-admin-modal-open="edit-rejection-modal-<?= (int) $template['id'] ?>">Edit</button><form action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan/' . $template['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus template alasan penolakan ini?')"><?= csrf_field() ?><button class="department-delete-button" type="submit">Delete</button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr>
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
                            <tr class="<?= ! $question['is_active'] ? 'department-row-inactive' : '' ?>"><td><?= esc((string) $question['display_order']) ?></td><td><div class="department-name-cell screening-question-cell"><strong><?= esc($question['question_text']) ?></strong><code><?= esc($question['question_code']) ?></code></div></td><td><span class="screening-type-badge"><?= esc($question['answer_type']) ?></span></td><td class="screening-evaluation-cell"><?= $question['is_required'] ? 'Wajib' : 'Opsional' ?><?= $question['is_knockout'] ? ' · Knockout' : '' ?><?php if (! empty($question['expected_value'])): ?><small>Jawaban: <?= esc($question['expected_value']) ?></small><?php endif ?></td><td><span class="account-status <?= $question['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $question['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><button class="settings-edit-trigger" type="button" data-admin-modal-open="edit-screening-modal-<?= (int) $question['id'] ?>">Edit</button><form action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening/' . $question['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus pertanyaan screening ini?')"><?= csrf_field() ?><button class="department-delete-button" type="submit">Delete</button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr>
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
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=3" defer></script>
</body>
</html>
