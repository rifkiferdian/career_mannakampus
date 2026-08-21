<?php
$activeTemplateCount = count(array_filter($templates, static fn (array $template): bool => (int) $template['is_active'] === 1));
$usedVacancyCount = array_sum(array_map(static fn (array $template): int => (int) $template['vacancy_count'], $templates));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive">
    <title>Template Tahapan | HRD Manna Kampus</title><link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>"><link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25"><link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=35">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'process-templates']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false"><span></span><span></span><span></span></button><div><span>Recruitment</span><strong>Template Tahapan</strong></div></header>
        <div class="admin-content process-template-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>
            <section class="dashboard-welcome department-heading"><div><span class="login-eyebrow">Recruitment Pipeline</span><h1>Template Tahapan</h1><p>Atur urutan proses seleksi, kemudian pilih templatenya pada setiap lowongan.</p></div></section>
            <section class="access-summary vacancy-summary process-template-summary" aria-label="Ringkasan template tahapan">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 5h4v4H5zM15 5h4v4h-4zM5 15h4v4H5zM15 15h4v4h-4zM9 7h6M7 9v6M9 17h6M17 9v6"/></svg></i><strong><?= count($templates) ?></strong><span>Total template</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg></i><strong><?= $activeTemplateCount ?></strong><span>Template aktif</span></article>
                <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg></i><strong><?= $usedVacancyCount ?></strong><span>Lowongan menggunakan</span></article>
            </section>
            <section class="settings-card process-template-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></span><div><h2>Daftar template tahapan</h2><p>Urutan menentukan proses kandidat pada masing-masing lowongan.</p></div><div class="process-template-heading-actions"><span class="device-count"><?= count($templates) ?></span><?php if ($canManage): ?><button class="new-user-jump vacancy-create-link" type="button" data-admin-modal-open="process-template-create"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Tambah template</button><?php endif ?></div></div>
                <div class="department-table-wrap"><table class="department-table recruitment-table process-template-table">
                    <thead><tr><th>No.</th><th>Template</th><th>Urutan tahapan</th><th>Lowongan</th><th>Status</th><?php if ($canManage): ?><th>Aksi</th><?php endif ?></tr></thead>
                    <tbody>
                    <?php if ($templates === []): ?><tr><td colspan="6" class="department-empty">Belum ada template tahapan.</td></tr><?php endif ?>
                    <?php foreach ($templates as $index => $template): ?><tr class="<?= ! $template['is_active'] ? 'department-row-inactive' : '' ?>">
                        <td class="process-template-order"><?= $index + 1 ?></td>
                        <td><div class="department-name-cell process-template-name"><strong><?= esc($template['name']) ?></strong><code><?= esc($template['code']) ?></code><small><?= esc((string) ($template['description'] ?: 'Tanpa keterangan')) ?></small></div></td>
                        <td><div class="template-stage-flow"><?php foreach ($template['stages'] as $stage): ?><span style="--stage-color:<?= esc($stage['color_hex'], 'attr') ?>"><b><?= (int) $stage['display_order'] ?></b><i></i><?= esc($stage['name']) ?></span><?php endforeach ?></div></td>
                        <td><span class="department-vacancy-count"><?= (int) $template['vacancy_count'] ?> lowongan</span></td>
                        <td><span class="account-status <?= $template['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $template['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <?php if ($canManage): ?><td><div class="department-table-actions"><button type="button" class="table-action-icon table-action-edit" data-admin-modal-open="process-template-edit-<?= (int) $template['id'] ?>" aria-label="Edit <?= esc($template['name'], 'attr') ?>" title="Edit template"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/template-tahapan/' . $template['id'] . '/hapus') ?>" method="post" data-confirm="Hapus template ini secara permanen?"><?= csrf_field() ?><button class="table-action-icon table-action-delete" type="submit" aria-label="Hapus <?= esc($template['name'], 'attr') ?>" title="<?= (int) $template['vacancy_count'] > 0 ? 'Template sedang dipakai lowongan' : 'Hapus template' ?>" <?= (int) $template['vacancy_count'] > 0 ? 'disabled' : '' ?>><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form></div></td><?php endif ?>
                    </tr><?php endforeach ?>
                    </tbody>
                </table></div>
            </section>
            <section class="settings-card process-template-table-card stage-type-card" id="stage-types">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5"/></svg></span><div><h2>Jenis tahap</h2><p>Kelola pilihan tahap yang dapat digunakan saat menyusun template.</p></div><div class="process-template-heading-actions"><span class="device-count"><?= count($stageCatalog) ?></span><?php if ($canManage): ?><button class="new-user-jump vacancy-create-link" type="button" data-admin-modal-open="stage-type-create"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Tambah jenis tahap</button><?php endif ?></div></div>
                <div class="department-table-wrap"><table class="department-table recruitment-table stage-type-table">
                    <thead><tr><th>No.</th><th>Jenis tahap</th><th>Warna</th><th>SLA</th><th>Digunakan</th><th>Status</th><?php if ($canManage): ?><th>Aksi</th><?php endif ?></tr></thead>
                    <tbody>
                    <?php if ($stageCatalog === []): ?><tr><td colspan="<?= $canManage ? 7 : 6 ?>" class="department-empty">Belum ada jenis tahap.</td></tr><?php endif ?>
                    <?php foreach ($stageCatalog as $index => $stage): $protectedStage = in_array((string) $stage['code'], ['accepted', 'rejected'], true); $canDeleteStage = ! $protectedStage && (int) $stage['template_count'] === 0 && (int) $stage['application_count'] === 0; ?><tr class="<?= ! $stage['is_active'] ? 'department-row-inactive' : '' ?>">
                        <td class="process-template-order"><?= $index + 1 ?></td>
                        <td><div class="department-name-cell"><strong><?= esc($stage['name']) ?></strong><code><?= esc($stage['code']) ?></code><?php if ($protectedStage): ?><small class="stage-system-label">Tahap sistem</small><?php endif ?></div></td>
                        <td><span class="stage-type-color"><i style="--stage-color:<?= esc($stage['color_hex'], 'attr') ?>"></i><?= esc($stage['color_hex']) ?></span></td>
                        <td><?= (int) $stage['sla_days'] > 0 ? (int) $stage['sla_days'] . ' hari' : '-' ?></td>
                        <td><div class="stage-type-usage"><span><?= (int) $stage['template_count'] ?> template</span><small><?= (int) $stage['application_count'] ?> kandidat aktif</small></div></td>
                        <td><span class="account-status <?= $stage['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $stage['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <?php if ($canManage): ?><td><div class="department-table-actions"><button type="button" class="table-action-icon table-action-edit" data-admin-modal-open="stage-type-edit-<?= (int) $stage['id'] ?>" aria-label="Edit <?= esc($stage['name'], 'attr') ?>" title="Edit jenis tahap"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><form action="<?= site_url('adminhrdmannakampus/template-tahapan/jenis/' . $stage['id'] . '/hapus') ?>" method="post" data-confirm="Hapus jenis tahap ini secara permanen?"><?= csrf_field() ?><button class="table-action-icon table-action-delete" type="submit" aria-label="Hapus <?= esc($stage['name'], 'attr') ?>" title="<?= $canDeleteStage ? 'Hapus jenis tahap' : 'Jenis tahap masih digunakan atau dilindungi' ?>" <?= $canDeleteStage ? '' : 'disabled' ?>><svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button></form></div></td><?php endif ?>
                    </tr><?php endforeach ?>
                    </tbody>
                </table></div>
            </section>
        </div>
    </main>
</div>
<?php if ($canManage): ?>
<dialog class="admin-modal process-template-modal" id="process-template-create" aria-labelledby="process-template-create-title"><div class="admin-modal-panel">
    <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="process-template-create-title">Tambah template tahapan</h2><p>Lengkapi identitas lalu pilih urutan proses seleksi.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="process-template-form" action="<?= site_url('adminhrdmannakampus/template-tahapan') ?>" method="post"><?= csrf_field() ?><?= view('admin/partials/process_template_fields', ['template' => [], 'stages' => $stages]) ?><div class="candidate-process-buttons"><button type="button" class="candidate-modal-cancel" data-admin-modal-close>Batal</button><button type="submit">Simpan template</button></div></form>
</div></dialog>
<?php foreach ($templates as $template): ?><dialog class="admin-modal process-template-modal" id="process-template-edit-<?= (int) $template['id'] ?>" aria-labelledby="process-template-edit-title-<?= (int) $template['id'] ?>"><div class="admin-modal-panel">
    <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></span><div><h2 id="process-template-edit-title-<?= (int) $template['id'] ?>">Edit <?= esc($template['name']) ?></h2><p>Perubahan berlaku untuk lowongan yang menggunakan template ini.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="process-template-form" action="<?= site_url('adminhrdmannakampus/template-tahapan/' . $template['id']) ?>" method="post"><?= csrf_field() ?><?= view('admin/partials/process_template_fields', ['template' => $template, 'stages' => $stages]) ?><div class="candidate-process-buttons"><button type="button" class="candidate-modal-cancel" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div></form>
</div></dialog><?php endforeach ?>
<dialog class="admin-modal process-template-modal stage-type-modal" id="stage-type-create" aria-labelledby="stage-type-create-title"><div class="admin-modal-panel">
    <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="stage-type-create-title">Tambah jenis tahap</h2><p>Jenis tahap baru akan tersedia pada form template tahapan.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="process-template-form stage-type-form" action="<?= site_url('adminhrdmannakampus/template-tahapan/jenis') ?>" method="post"><?= csrf_field() ?><?= view('admin/partials/stage_type_fields', ['stage' => []]) ?><div class="candidate-process-buttons"><button type="button" class="candidate-modal-cancel" data-admin-modal-close>Batal</button><button type="submit">Simpan jenis tahap</button></div></form>
</div></dialog>
<?php foreach ($stageCatalog as $stage): ?><dialog class="admin-modal process-template-modal stage-type-modal" id="stage-type-edit-<?= (int) $stage['id'] ?>" aria-labelledby="stage-type-edit-title-<?= (int) $stage['id'] ?>"><div class="admin-modal-panel">
    <div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></span><div><h2 id="stage-type-edit-title-<?= (int) $stage['id'] ?>">Edit <?= esc($stage['name']) ?></h2><p>Perbarui nama, warna, SLA, atau status jenis tahap.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="process-template-form stage-type-form" action="<?= site_url('adminhrdmannakampus/template-tahapan/jenis/' . $stage['id']) ?>" method="post"><?= csrf_field() ?><?= view('admin/partials/stage_type_fields', ['stage' => $stage]) ?><div class="candidate-process-buttons"><button type="button" class="candidate-modal-cancel" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div></form>
</div></dialog><?php endforeach ?>
<?php endif ?>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=5" defer></script>
</body></html>
