<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Pengaturan Rekrutmen | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=9">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <a href="<?= site_url('adminhrdmannakampus/dashboard') ?>" class="admin-brand sidebar-brand"><img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus"></a>
            <span class="sidebar-caption">HRD Administration</span>
            <nav class="admin-nav" aria-label="Navigasi dashboard HRD">
                <a href="<?= site_url('adminhrdmannakampus/dashboard') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/></svg>Dashboard</a>
                <a href="<?= site_url('adminhrdmannakampus/profil') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>Profil &amp; Keamanan</a>
                <?php if (($auth['role'] ?? '') === 'SUPER_ADMIN'): ?>
                    <a href="<?= site_url('adminhrdmannakampus/akses') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><path d="M3 19a5 5 0 0 1 10 0M16 7h5M18.5 4.5v5M15 15h6M18 12v6"/></svg>User &amp; Akses</a>
                <?php endif ?>
                <?php if (! empty($canViewDepartments)): ?><a href="<?= site_url('adminhrdmannakampus/departemen') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M8 10h2M14 10h2"/></svg>Departemen</a><?php endif ?>
                <a class="active" href="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg>Pengaturan Rekrutmen</a>
                <span class="admin-nav-disabled"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>Kandidat <small>Segera</small></span>
                <span class="admin-nav-disabled"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg>Lowongan <small>Segera</small></span>
            </nav>
            <div class="sidebar-user"><span class="user-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($auth['name'] ?? 'H'), 0, 1))) ?></span><span><strong><?= esc($auth['name'] ?? 'Admin HRD') ?></strong><small><?= esc($auth['email'] ?? '') ?></small></span></div>
            <form action="<?= site_url('adminhrdmannakampus/logout') ?>" method="post"><?= csrf_field() ?><button class="logout-button" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>Keluar</button></form>
        </aside>

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
                    <div><span class="login-eyebrow">Recruitment Workflow</span><h1 id="settings-title">Pengaturan Rekrutmen</h1><p>Atur tahapan seleksi, batas waktu proses, alasan penolakan, dan pertanyaan screening standar.</p></div>
                    <?php if (! $canManage): ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
                </section>

                <nav class="settings-anchor-nav" aria-label="Bagian pengaturan rekrutmen">
                    <a href="#stages">Tahapan seleksi</a><a href="#rejections">Alasan penolakan</a><a href="#screening">Screening default</a>
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
                                <td><span class="department-order"><?= esc((string) $stage['display_order']) ?></span></td>
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
                        <span class="device-count"><?= count($rejectionTemplates) ?></span>
                    </div>
                    <div class="department-table-wrap"><table class="department-table recruitment-table">
                        <thead><tr><th>Urutan</th><th>Template</th><th>Alasan penolakan</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody><?php foreach ($rejectionTemplates as $template): ?>
                            <tr class="<?= ! $template['is_active'] ? 'department-row-inactive' : '' ?>"><td><span class="department-order"><?= esc((string) $template['display_order']) ?></span></td><td><div class="department-name-cell"><strong><?= esc($template['title']) ?></strong></div></td><td class="department-description-cell"><?= esc($template['reason_text']) ?></td><td><span class="account-status <?= $template['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $template['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><a href="#edit-rejection-<?= esc((string) $template['id'], 'attr') ?>">Edit</a><form action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan/' . $template['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus template alasan penolakan ini?')"><?= csrf_field() ?><button class="department-delete-button" type="submit">Delete</button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr>
                            <?php if ($canManage): ?><tr class="department-edit-table-row" id="edit-rejection-<?= esc((string) $template['id'], 'attr') ?>"><td colspan="5"><form class="department-table-edit-form rejection-table-edit-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan/' . $template['id']) ?>" method="post"><?= csrf_field() ?><label>Judul<input type="text" name="title" value="<?= esc($template['title'], 'attr') ?>" maxlength="150" required></label><label>Urutan<input type="number" name="display_order" value="<?= esc((string) $template['display_order'], 'attr') ?>" min="1" max="999" required></label><label class="department-description-field">Isi alasan<textarea name="reason_text" rows="3" maxlength="1000" required><?= esc($template['reason_text']) ?></textarea></label><label class="department-active-check"><input type="checkbox" name="is_active" value="1" <?= $template['is_active'] ? 'checked' : '' ?>> Aktif</label><div class="department-edit-buttons"><a href="#rejections-title">Batal</a><button type="submit">Simpan</button></div></form></td></tr><?php endif ?>
                        <?php endforeach ?></tbody>
                    </table></div>
                    <?php if ($canManage): ?>
                        <form class="new-config-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/penolakan') ?>" method="post">
                            <?= csrf_field() ?><strong>Tambah template baru</strong>
                            <div class="new-template-grid"><input type="number" name="display_order" value="<?= count($rejectionTemplates) + 1 ?>" min="1" max="999" aria-label="Urutan"><input type="text" name="title" placeholder="Judul alasan" maxlength="150" required><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
                            <textarea name="reason_text" rows="3" placeholder="Pesan alasan penolakan yang akan disampaikan kepada kandidat" maxlength="1000" required></textarea><button type="submit">Tambah template</button>
                        </form>
                    <?php endif ?>
                </section>

                <section class="settings-card recruitment-config-card" id="screening" aria-labelledby="screening-title">
                    <div class="settings-card-heading settings-heading-action">
                        <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span>
                        <div><h2 id="screening-title">Pertanyaan screening default</h2><p>Bank pertanyaan untuk digunakan ketika membuat lowongan baru.</p></div>
                        <span class="device-count"><?= count($screeningQuestions) ?></span>
                    </div>
                    <div class="department-table-wrap"><table class="department-table recruitment-table screening-table">
                        <thead><tr><th>Urutan</th><th>Pertanyaan</th><th>Tipe</th><th>Evaluasi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody><?php foreach ($screeningQuestions as $question): ?>
                            <?php $decodedOptions = json_decode((string) ($question['answer_options'] ?? ''), true); $optionsText = is_array($decodedOptions) ? implode(', ', $decodedOptions) : ''; ?>
                            <tr class="<?= ! $question['is_active'] ? 'department-row-inactive' : '' ?>"><td><span class="department-order"><?= esc((string) $question['display_order']) ?></span></td><td><div class="department-name-cell screening-question-cell"><strong><?= esc($question['question_text']) ?></strong><code><?= esc($question['question_code']) ?></code></div></td><td><span class="screening-type-badge"><?= esc($question['answer_type']) ?></span></td><td class="screening-evaluation-cell"><?= $question['is_required'] ? 'Wajib' : 'Opsional' ?><?= $question['is_knockout'] ? ' · Knockout' : '' ?><?php if (! empty($question['expected_value'])): ?><small>Jawaban: <?= esc($question['expected_value']) ?></small><?php endif ?></td><td><span class="account-status <?= $question['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $question['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><a href="#edit-screening-<?= esc((string) $question['id'], 'attr') ?>">Edit</a><form action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening/' . $question['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus pertanyaan screening ini?')"><?= csrf_field() ?><button class="department-delete-button" type="submit">Delete</button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr>
                            <?php if ($canManage): ?><tr class="department-edit-table-row" id="edit-screening-<?= esc((string) $question['id'], 'attr') ?>"><td colspan="6"><form class="department-table-edit-form screening-table-edit-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening/' . $question['id']) ?>" method="post"><?= csrf_field() ?><label class="screening-edit-question">Pertanyaan<input type="text" name="question_text" value="<?= esc($question['question_text'], 'attr') ?>" maxlength="500" required></label><label>Tipe<select name="answer_type"><?php foreach (['text' => 'Teks', 'number' => 'Angka', 'yes_no' => 'Ya / Tidak', 'choice' => 'Pilihan'] as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= $question['answer_type'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><label>Operator<select name="comparison_operator"><?php foreach (['' => 'Tanpa evaluasi', 'equals' => 'Sama dengan', 'between' => 'Di antara', 'greater_than_or_equal' => 'Minimal', 'minimum_education' => 'Pendidikan minimal'] as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= ($question['comparison_operator'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label><label>Jawaban harapan<input type="text" name="expected_value" value="<?= esc((string) ($question['expected_value'] ?? ''), 'attr') ?>" maxlength="255"></label><label>Opsi<input type="text" name="answer_options" value="<?= esc($optionsText, 'attr') ?>"></label><label>Urutan<input type="number" name="display_order" value="<?= esc((string) $question['display_order'], 'attr') ?>" min="1" max="999" required></label><div class="screening-flags"><label><input type="checkbox" name="is_required" value="1" <?= $question['is_required'] ? 'checked' : '' ?>> Wajib</label><label><input type="checkbox" name="is_knockout" value="1" <?= $question['is_knockout'] ? 'checked' : '' ?>> Knockout</label><label><input type="checkbox" name="is_active" value="1" <?= $question['is_active'] ? 'checked' : '' ?>> Aktif</label></div><div class="department-edit-buttons"><a href="#screening-title">Batal</a><button type="submit">Simpan</button></div></form></td></tr><?php endif ?>
                        <?php endforeach ?></tbody>
                    </table></div>
                    <?php if ($canManage): ?>
                        <form class="new-config-form new-screening-form" action="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen/screening') ?>" method="post">
                            <?= csrf_field() ?><strong>Tambah pertanyaan default</strong>
                            <input type="text" name="question_text" placeholder="Tulis pertanyaan screening" maxlength="500" required>
                            <div class="new-screening-grid"><select name="answer_type"><option value="text">Teks</option><option value="number">Angka</option><option value="yes_no">Ya / Tidak</option><option value="choice">Pilihan</option></select><select name="comparison_operator"><option value="">Tanpa evaluasi</option><option value="equals">Sama dengan</option><option value="between">Di antara</option><option value="greater_than_or_equal">Minimal</option><option value="minimum_education">Pendidikan minimal</option></select><input type="text" name="expected_value" placeholder="Jawaban harapan"><input type="text" name="answer_options" placeholder="Opsi dipisahkan koma"><input type="number" name="display_order" value="<?= count($screeningQuestions) + 1 ?>" min="1" max="999" aria-label="Urutan"></div>
                            <div class="screening-flags"><label><input type="checkbox" name="is_required" value="1" checked> Wajib</label><label><input type="checkbox" name="is_knockout" value="1"> Knockout</label><label><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div><button type="submit">Tambah pertanyaan</button>
                        </form>
                    <?php endif ?>
                </section>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
