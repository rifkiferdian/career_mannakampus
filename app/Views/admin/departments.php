<?php
$activeCount = count(array_filter($departments, static fn (array $department): bool => (int) $department['is_active'] === 1));
$vacancyCount = array_sum(array_map(static fn (array $department): int => (int) $department['vacancy_count'], $departments));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Departemen | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=13">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'departments']) ?>

        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
                <div><span>Master Data</span><strong>Departemen</strong></div>
                <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
            </header>

            <div class="admin-content department-content">
                <?php if (! empty($success)): ?><div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div><?php endif ?>
                <?php if (! empty($error)): ?><div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div><?php endif ?>

                <section class="dashboard-welcome department-heading" aria-labelledby="department-title">
                    <div><span class="login-eyebrow">Organization Structure</span><h1 id="department-title">Departemen</h1><p>Kelola departemen yang digunakan untuk mengelompokkan lowongan pekerjaan.</p></div>
                    <?php if (! $canManage): ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
                </section>

                <section class="access-summary department-summary" aria-label="Ringkasan departemen">
                    <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M8 10h2M14 10h2"/></svg></i><strong><?= count($departments) ?></strong><span>Hasil ditampilkan</span></article>
                    <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5"/><path d="m9 11 2 2 4-4"/></svg></i><strong><?= $activeCount ?></strong><span>Departemen aktif</span></article>
                    <article><i class="summary-card-icon icon-red" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M9 11h6"/></svg></i><strong><?= count($departments) - $activeCount ?></strong><span>Nonaktif</span></article>
                    <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg></i><strong><?= $vacancyCount ?></strong><span>Lowongan terkait</span></article>
                </section>

                <section class="settings-card department-toolbar-card">
                    <form class="department-search" action="<?= site_url('adminhrdmannakampus/departemen') ?>" method="get" role="search">
                        <input type="search" name="keyword" value="<?= esc($keyword, 'attr') ?>" placeholder="Cari nama, kode, atau deskripsi departemen">
                        <button type="submit">Cari</button>
                        <?php if ($keyword !== ''): ?><a href="<?= site_url('adminhrdmannakampus/departemen') ?>">Reset</a><?php endif ?>
                    </form>
                </section>

                <?php if ($canManage): ?>
                    <section class="settings-card department-create-card" aria-labelledby="create-department-title">
                        <div class="settings-card-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="create-department-title">Tambah departemen</h2><p>Kode digunakan sebagai identitas pada filter lowongan.</p></div></div>
                        <form class="department-create-form" action="<?= site_url('adminhrdmannakampus/departemen') ?>" method="post">
                            <?= csrf_field() ?>
                            <label>Nama departemen<input type="text" name="name" maxlength="100" placeholder="Contoh: Customer Experience" required></label>
                            <label>Kode<input type="text" name="code" maxlength="50" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="customer-experience" required></label>
                            <label>Urutan<input type="number" name="display_order" min="0" max="999" value="<?= count($departments) + 1 ?>" required></label>
                            <label class="department-description-field">Deskripsi<textarea name="description" rows="3" maxlength="500" placeholder="Jelaskan ruang lingkup departemen"></textarea></label>
                            <label class="department-active-check"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                            <button type="submit">Tambah departemen</button>
                        </form>
                    </section>
                <?php endif ?>

                <section class="settings-card department-list-card" aria-labelledby="department-list-title">
                    <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h14"/></svg></span><div><h2 id="department-list-title">Daftar departemen</h2><p><?= $keyword !== '' ? 'Hasil pencarian untuk ' . esc($keyword) . '.' : 'Data diurutkan berdasarkan nomor urutan.' ?></p></div><span class="device-count"><?= count($departments) ?></span></div>
                    <div class="department-table-wrap">
                        <table class="department-table">
                            <thead><tr><th>Urutan</th><th>Departemen</th><th>Deskripsi</th><th>Lowongan</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php if ($departments === []): ?><tr><td class="department-empty" colspan="6">Departemen tidak ditemukan.</td></tr><?php endif ?>
                                <?php foreach ($departments as $department): ?>
                                    <tr class="<?= (int) $department['is_active'] === 0 ? 'department-row-inactive' : '' ?>">
                                        <td><span class="department-order"><?= esc((string) $department['display_order']) ?></span></td>
                                        <td><div class="department-name-cell"><strong><?= esc($department['name']) ?></strong><code><?= esc($department['code']) ?></code></div></td>
                                        <td class="department-description-cell"><?= esc((string) ($department['description'] ?: '-')) ?></td>
                                        <td><span class="department-vacancy-count"><?= esc((string) $department['vacancy_count']) ?> lowongan</span></td>
                                        <td><span class="account-status <?= (int) $department['is_active'] === 1 ? 'active' : 'inactive' ?>"><i></i><?= (int) $department['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                                        <td>
                                            <div class="department-table-actions">
                                                <?php if ($canManage): ?>
                                                    <a href="#edit-department-<?= esc((string) $department['id'], 'attr') ?>">Edit</a>
                                                <?php endif ?>
                                                <?php if ($canDelete): ?><form action="<?= site_url('adminhrdmannakampus/departemen/' . $department['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus departemen ini? Tindakan ini tidak dapat dibatalkan.')"><?= csrf_field() ?><button class="department-delete-button" type="submit" <?= (int) $department['vacancy_count'] > 0 ? 'disabled title="Masih digunakan oleh lowongan"' : '' ?>>Delete</button></form><?php endif ?>
                                                <?php if (! $canManage && ! $canDelete): ?><span class="protected-label">Lihat saja</span><?php endif ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if ($canManage): ?>
                                        <tr class="department-edit-table-row" id="edit-department-<?= esc((string) $department['id'], 'attr') ?>">
                                            <td colspan="6">
                                                <form class="department-table-edit-form" action="<?= site_url('adminhrdmannakampus/departemen/' . $department['id']) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <label>Nama<input type="text" name="name" value="<?= esc($department['name'], 'attr') ?>" maxlength="100" required></label>
                                                    <label>Kode<input type="text" name="code" value="<?= esc($department['code'], 'attr') ?>" maxlength="50" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required></label>
                                                    <label>Urutan<input type="number" name="display_order" value="<?= esc((string) $department['display_order'], 'attr') ?>" min="0" max="999" required></label>
                                                    <label class="department-description-field">Deskripsi<textarea name="description" rows="2" maxlength="500"><?= esc((string) ($department['description'] ?? '')) ?></textarea></label>
                                                    <label class="department-active-check"><input type="checkbox" name="is_active" value="1" <?= (int) $department['is_active'] === 1 ? 'checked' : '' ?>> Aktif</label>
                                                    <div class="department-edit-buttons"><a href="#department-list-title">Batal</a><button type="submit">Simpan perubahan</button></div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endif ?>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
