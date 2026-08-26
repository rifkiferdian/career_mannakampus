<?php
$typeIcons = ['scale_1_5' => '1–5', 'yes_no' => 'Y/T', 'choice' => 'Opsi', 'text' => 'Teks'];
$decodeOptions = static function (?string $json): array {
    $options = json_decode((string) $json, true);

    return is_array($options) ? array_values(array_filter(array_map('strval', $options))) : [];
};
$fieldValue = static fn (string $modal, string $field, mixed $fallback = ''): mixed => $openModal === $modal ? old($field, $fallback) : $fallback;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Aspek Nilai | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=68">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'recommendation-aspects']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button><div><span>Candidate Assessment</span><strong>Aspek Nilai</strong></div><a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a></header>

        <div class="admin-content recommendation-aspect-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading recommendation-aspect-heading">
                <div><span class="login-eyebrow">Master Penilaian</span><h1>Aspek Nilai</h1><p>Susun pertanyaan yang nantinya digunakan HRD untuk menilai dan memberi rekomendasi pada biodata pelamar.</p></div>
                <?php if ($canManage): ?><button class="new-user-jump" type="button" data-admin-modal-open="recommendation-aspect-create">+ Tambah aspek</button><?php else: ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
            </section>

            <section class="access-summary recommendation-aspect-summary" aria-label="Ringkasan aspek nilai">
                <article><i class="summary-card-icon icon-blue"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></i><strong><?= count($aspects) ?></strong><span>Total aspek</span></article>
                <article><i class="summary-card-icon icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= count(array_filter($aspects, static fn (array $aspect): bool => (int) $aspect['is_active'] === 1)) ?></strong><span>Aspek aktif</span></article>
                <article><i class="summary-card-icon icon-purple"><svg viewBox="0 0 24 24"><path d="M6 18V9M12 18V5M18 18v-6"/></svg></i><strong><?= count(array_filter($aspects, static fn (array $aspect): bool => $aspect['input_type'] === 'scale_1_5')) ?></strong><span>Penilaian 1–5</span></article>
                <article><i class="summary-card-icon icon-orange"><svg viewBox="0 0 24 24"><path d="M7 7h10v10H7zM4 4h3M17 4h3M4 20h3M17 20h3"/></svg></i><strong><?= count(array_filter($aspects, static fn (array $aspect): bool => (int) $aspect['is_required'] === 1)) ?></strong><span>Wajib diisi</span></article>
            </section>

            <section class="settings-card recommendation-aspect-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h5M8 12h8M8 16h4"/></svg></span><div><h2>Daftar aspek penilaian</h2><p>Urutan ini akan digunakan pada form rekomendasi pelamar.</p></div><span class="device-count"><?= count($aspects) ?></span></div>
                <div class="department-table-wrap"><table class="department-table recommendation-aspect-table"><thead><tr><th>Urutan</th><th>Aspek</th><th>Jenis jawaban</th><th>Opsi</th><th>Pengisian</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($aspects === []): ?><tr><td colspan="7" class="department-empty">Belum ada aspek penilaian.</td></tr><?php endif ?>
                    <?php foreach ($aspects as $aspect): $options = $decodeOptions($aspect['options_json']); ?>
                        <tr class="<?= (int) $aspect['is_active'] === 1 ? '' : 'department-row-inactive' ?>">
                            <td><strong class="aspect-order"><?= (int) $aspect['display_order'] ?></strong></td>
                            <td><div class="department-name-cell"><strong><?= esc($aspect['name']) ?></strong><code><?= esc($aspect['code']) ?></code><?php if ($aspect['description']): ?><small><?= esc($aspect['description']) ?></small><?php endif ?></div></td>
                            <td><span class="aspect-type aspect-type-<?= esc($aspect['input_type'], 'attr') ?>"><i><?= esc($typeIcons[$aspect['input_type']] ?? '?') ?></i><?= esc($inputTypes[$aspect['input_type']] ?? $aspect['input_type']) ?></span></td>
                            <td><div class="aspect-options"><?php if ($options === []): ?><span>—</span><?php endif ?><?php foreach ($options as $option): ?><em><?= esc($option) ?></em><?php endforeach ?></div></td>
                            <td><span class="account-status <?= (int) $aspect['is_required'] === 1 ? 'active' : 'pending' ?>"><i></i><?= (int) $aspect['is_required'] === 1 ? 'Wajib' : 'Opsional' ?></span></td>
                            <td><span class="account-status <?= (int) $aspect['is_active'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $aspect['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td><div class="department-table-actions"><?php if ($canManage): ?><button class="table-action-icon table-action-edit" type="button" data-admin-modal-open="recommendation-aspect-edit-<?= (int) $aspect['id'] ?>" aria-label="Edit aspek <?= esc($aspect['name'], 'attr') ?>"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/aspek-penilaian/' . $aspect['id'] . '/status') ?>" method="post"><?= csrf_field() ?><button class="table-status-button <?= (int) $aspect['is_active'] === 1 ? 'active' : 'inactive' ?>" type="submit"><?= (int) $aspect['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><form action="<?= site_url('adminhrdmannakampus/aspek-penilaian/' . $aspect['id'] . '/hapus') ?>" method="post"><?= csrf_field() ?><button class="department-delete-button table-action-icon table-action-delete" type="submit" data-confirm="Hapus aspek <?= esc($aspect['name'], 'attr') ?>? Aspek akan disembunyikan dari form penilaian." aria-label="Hapus aspek"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody></table></div>
            </section>
        </div>
        <?= view('admin/partials/footer') ?>
    </main>
</div>

<?php if ($canManage): ?>
    <dialog class="admin-modal recommendation-aspect-modal" id="recommendation-aspect-create" aria-labelledby="recommendation-aspect-create-title" <?= $openModal === 'create' ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="recommendation-aspect-create-title">Tambah aspek nilai</h2><p>Aspek baru akan tersedia untuk scorecard pelamar.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close>&times;</button></div>
        <?= view('admin/partials/recommendation_aspect_form', ['action' => site_url('adminhrdmannakampus/aspek-penilaian'), 'modalKey' => 'create', 'aspect' => null, 'inputTypes' => $inputTypes, 'fieldValue' => $fieldValue, 'defaultOrder' => count($aspects) + 1]) ?>
    </div></dialog>
    <?php foreach ($aspects as $aspect): $modalKey = 'edit-' . $aspect['id']; ?>
        <dialog class="admin-modal recommendation-aspect-modal" id="recommendation-aspect-edit-<?= (int) $aspect['id'] ?>" aria-labelledby="recommendation-aspect-edit-title-<?= (int) $aspect['id'] ?>" <?= $openModal === $modalKey ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 20h4l11-11-4-4L4 16v4Z"/></svg></span><div><h2 id="recommendation-aspect-edit-title-<?= (int) $aspect['id'] ?>">Edit aspek nilai</h2><p><?= esc($aspect['code']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close>&times;</button></div>
            <?= view('admin/partials/recommendation_aspect_form', ['action' => site_url('adminhrdmannakampus/aspek-penilaian/' . $aspect['id']), 'modalKey' => $modalKey, 'aspect' => $aspect, 'inputTypes' => $inputTypes, 'fieldValue' => $fieldValue, 'defaultOrder' => (int) $aspect['display_order']]) ?>
        </div></dialog>
    <?php endforeach ?>
<?php endif ?>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=11" defer></script>
</body>
</html>
