<?php
$fieldValue = static fn (string $modal, string $field, mixed $fallback = ''): mixed => $openModal === $modal ? old($field, $fallback) : $fallback;
$categoryClasses = ['contact' => 'blue', 'schedule' => 'purple', 'reminder' => 'orange', 'confirmation' => 'cyan', 'progress' => 'green', 'result' => 'red', 'other' => 'gray'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Template WhatsApp | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=78">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'whatsapp-templates']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button><div><span>Candidate Communication</span><strong>Template WhatsApp</strong></div><a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a></header>

        <div class="admin-content whatsapp-template-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading whatsapp-template-heading">
                <div><span class="login-eyebrow">Komunikasi Kandidat</span><h1>Template WhatsApp</h1><p>Kelola teks pesan yang dapat dipilih recruiter saat menghubungi pelamar melalui WhatsApp.</p></div>
                <?php if ($canManage): ?><button class="new-user-jump" type="button" data-admin-modal-open="whatsapp-template-create">+ Tambah template</button><?php else: ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
            </section>

            <section class="access-summary whatsapp-template-summary" aria-label="Ringkasan template WhatsApp">
                <article><i class="summary-card-icon icon-blue"><svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3V5Z"/><path d="M8 9h8M8 13h5"/></svg></i><strong><?= count($templates) ?></strong><span>Total template</span></article>
                <article><i class="summary-card-icon icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= count(array_filter($templates, static fn (array $template): bool => (int) $template['is_active'] === 1)) ?></strong><span>Template aktif</span></article>
                <article><i class="summary-card-icon icon-orange"><svg viewBox="0 0 24 24"><path d="M7 5h10v14H7zM10 9h4M10 13h4"/></svg></i><strong><?= count(array_unique(array_column($templates, 'category'))) ?></strong><span>Kategori digunakan</span></article>
                <article><i class="summary-card-icon icon-purple"><svg viewBox="0 0 24 24"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h3"/></svg></i><strong>2.000</strong><span>Maksimal karakter</span></article>
            </section>

            <section class="settings-card whatsapp-template-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3V5Z"/><path d="M8 9h8M8 13h5"/></svg></span><div><h2>Daftar template pesan</h2><p>Template aktif nantinya tersedia saat recruiter membuka form WhatsApp pelamar.</p></div><span class="device-count"><?= count($templates) ?></span></div>
                <div class="department-table-wrap"><table class="department-table whatsapp-template-table"><thead><tr><th>Urutan</th><th>Template</th><th>Kategori</th><th>Isi pesan</th><th>Panjang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($templates === []): ?><tr><td colspan="7" class="department-empty">Belum ada template WhatsApp.</td></tr><?php endif ?>
                    <?php foreach ($templates as $template): ?>
                        <tr class="<?= (int) $template['is_active'] === 1 ? '' : 'department-row-inactive' ?>">
                            <td><strong class="aspect-order"><?= (int) $template['display_order'] ?></strong></td>
                            <td><div class="department-name-cell"><strong><?= esc($template['name']) ?></strong><code><?= esc($template['code']) ?></code></div></td>
                            <td><span class="whatsapp-category category-<?= esc($categoryClasses[$template['category']] ?? 'gray', 'attr') ?>"><?= esc($categories[$template['category']] ?? $template['category']) ?></span></td>
                            <td><p class="whatsapp-message-excerpt"><?= esc($template['message_text']) ?></p></td>
                            <td><strong class="whatsapp-message-length"><?= mb_strlen((string) $template['message_text']) ?></strong><small>karakter</small></td>
                            <td><span class="account-status <?= (int) $template['is_active'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $template['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td><div class="department-table-actions"><?php if ($canManage): ?><button class="table-action-icon table-action-edit" type="button" data-admin-modal-open="whatsapp-template-edit-<?= (int) $template['id'] ?>" aria-label="Edit template <?= esc($template['name'], 'attr') ?>"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/template-whatsapp/' . $template['id'] . '/status') ?>" method="post"><?= csrf_field() ?><button class="table-status-button <?= (int) $template['is_active'] === 1 ? 'active' : 'inactive' ?>" type="submit"><?= (int) $template['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><form action="<?= site_url('adminhrdmannakampus/template-whatsapp/' . $template['id'] . '/hapus') ?>" method="post"><?= csrf_field() ?><button class="department-delete-button table-action-icon table-action-delete" type="submit" data-confirm="Hapus template <?= esc($template['name'], 'attr') ?>?" aria-label="Hapus template"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody></table></div>
            </section>
        </div>
        <?= view('admin/partials/footer') ?>
    </main>
</div>

<?php if ($canManage): ?>
    <dialog class="admin-modal whatsapp-template-modal" id="whatsapp-template-create" aria-labelledby="whatsapp-template-create-title" <?= $openModal === 'create' ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="whatsapp-template-create-title">Tambah template WhatsApp</h2><p>Buat pesan yang dapat digunakan kembali oleh recruiter.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close>&times;</button></div>
        <?= view('admin/partials/whatsapp_template_form', ['action' => site_url('adminhrdmannakampus/template-whatsapp'), 'modalKey' => 'create', 'template' => null, 'categories' => $categories, 'variables' => $variables, 'fieldValue' => $fieldValue, 'defaultOrder' => count($templates) + 1]) ?>
    </div></dialog>
    <?php foreach ($templates as $template): $modalKey = 'edit-' . $template['id']; ?>
        <dialog class="admin-modal whatsapp-template-modal" id="whatsapp-template-edit-<?= (int) $template['id'] ?>" aria-labelledby="whatsapp-template-edit-title-<?= (int) $template['id'] ?>" <?= $openModal === $modalKey ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 20h4l11-11-4-4L4 16v4Z"/></svg></span><div><h2 id="whatsapp-template-edit-title-<?= (int) $template['id'] ?>">Edit template WhatsApp</h2><p><?= esc($template['code']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close>&times;</button></div>
            <?= view('admin/partials/whatsapp_template_form', ['action' => site_url('adminhrdmannakampus/template-whatsapp/' . $template['id']), 'modalKey' => $modalKey, 'template' => $template, 'categories' => $categories, 'variables' => $variables, 'fieldValue' => $fieldValue, 'defaultOrder' => (int) $template['display_order']]) ?>
        </div></dialog>
    <?php endforeach ?>
<?php endif ?>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=12" defer></script>
</body>
</html>
