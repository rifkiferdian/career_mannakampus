<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>User &amp; Akses | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=22">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'access']) ?>

        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
                <div><span>Access Control</span><strong>User, Role &amp; Permission</strong></div>
                <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
            </header>

            <div class="admin-content access-content">
                <?php if (! empty($success)): ?><div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div><?php endif ?>
                <?php if (! empty($error)): ?><div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div><?php endif ?>

                <section class="dashboard-welcome access-heading" aria-labelledby="access-title">
                    <div><span class="login-eyebrow">Khusus Super Admin</span><h1 id="access-title">User &amp; Akses</h1><p>Kelola akun internal, role, dan batas akses setiap anggota tim rekrutmen.</p></div>
                    <a class="new-user-jump" href="#new-user">+ Tambah akun</a>
                </section>

                <section class="access-summary" aria-label="Ringkasan akses">
                    <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M17 9h4M19 7v4"/></svg></i><strong><?= count($users) ?></strong><span>Total akun internal</span></article>
                    <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0m2-5 2 2 4-5"/></svg></i><strong><?= count(array_filter($users, static fn (array $user): bool => (bool) $user['is_active'])) ?></strong><span>Akun aktif</span></article>
                    <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 4.8 3.3 7.7 8 9 4.7-1.3 8-4.2 8-9V7l-8-4Z"/><path d="M9 12h6M12 9v6"/></svg></i><strong><?= count($roles) ?></strong><span>Role sistem</span></article>
                    <article><i class="summary-card-icon icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="8" cy="12" r="3"/><path d="M11 12h10M17 12v3M20 12v2"/></svg></i><strong><?= count($permissions) ?></strong><span>Permission rekrutmen</span></article>
                </section>

                <section class="settings-card access-users-card" aria-labelledby="users-title">
                    <div class="settings-card-heading settings-heading-action">
                        <span class="settings-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><path d="M3 19a5 5 0 0 1 10 0M16 8h5M18.5 5.5v5"/></svg></span>
                        <div><h2 id="users-title">Akun internal</h2><p>Role yang diubah akan mengeluarkan user dari seluruh perangkat.</p></div>
                        <span class="device-count"><?= count($users) ?></span>
                    </div>
                    <div class="access-table-wrap">
                        <table class="access-table">
                            <thead><tr><th>User</th><th>Role</th><th>Login terakhir</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach ($users as $managedUser): ?>
                                    <?php $isSelf = (int) $managedUser['id'] === (int) ($auth['user_id'] ?? 0); ?>
                                    <tr>
                                        <td><span class="access-user-cell"><i><?= esc(mb_strtoupper(mb_substr((string) $managedUser['full_name'], 0, 1))) ?></i><span><strong><?= esc($managedUser['full_name']) ?><?= $isSelf ? ' (Anda)' : '' ?></strong><small><?= esc($managedUser['email']) ?></small></span></span></td>
                                        <td>
                                            <?php if ($isSelf): ?>
                                                <span class="role-pill role-<?= esc(mb_strtolower((string) $managedUser['role_code']), 'attr') ?>"><?= esc($managedUser['role_name']) ?></span>
                                            <?php else: ?>
                                                <form class="inline-role-form" action="<?= site_url('adminhrdmannakampus/akses/users/' . $managedUser['id'] . '/role') ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <select name="role_id" aria-label="Role <?= esc($managedUser['full_name'], 'attr') ?>">
                                                        <?php foreach ($roles as $role): ?><option value="<?= esc((string) $role['id'], 'attr') ?>" <?= (int) $managedUser['role_id'] === (int) $role['id'] ? 'selected' : '' ?>><?= esc($role['name']) ?></option><?php endforeach ?>
                                                    </select>
                                                    <button type="submit">Simpan</button>
                                                </form>
                                            <?php endif ?>
                                        </td>
                                        <td><?= $managedUser['last_login_at'] ? esc(date('d M Y, H:i', strtotime((string) $managedUser['last_login_at']))) : '<span class="never-login">Belum pernah</span>' ?></td>
                                        <td><span class="account-status <?= $managedUser['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $managedUser['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                                        <td>
                                            <?php if (! $isSelf): ?>
                                                <form action="<?= site_url('adminhrdmannakampus/akses/users/' . $managedUser['id'] . '/status') ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <button class="status-action <?= $managedUser['is_active'] ? 'deactivate' : 'activate' ?>" type="submit" data-confirm="<?= $managedUser['is_active'] ? 'Nonaktifkan akun ini dan keluarkan dari semua perangkat?' : 'Aktifkan kembali akun ini?' ?>"><?= $managedUser['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                                </form>
                                            <?php else: ?><span class="protected-label">Dilindungi</span><?php endif ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="settings-card new-user-card" id="new-user" aria-labelledby="new-user-title">
                    <div class="settings-card-heading">
                        <span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M17 8h4M19 6v4"/></svg></span>
                        <div><h2 id="new-user-title">Tambah akun HRD</h2><p>Buat akun baru dengan satu role awal.</p></div>
                    </div>
                    <form class="new-user-form" action="<?= site_url('adminhrdmannakampus/akses/users') ?>" method="post" novalidate>
                        <?= csrf_field() ?>
                        <div><label for="new_full_name">Nama lengkap</label><div class="admin-input <?= isset($createErrors['full_name']) ? 'has-error' : '' ?>"><input id="new_full_name" name="full_name" type="text" value="<?= esc(old('full_name'), 'attr') ?>" maxlength="150" required></div><?php if (isset($createErrors['full_name'])): ?><p class="field-error"><?= esc($createErrors['full_name']) ?></p><?php endif ?></div>
                        <div><label for="new_email">Email</label><div class="admin-input <?= isset($createErrors['email']) ? 'has-error' : '' ?>"><input id="new_email" name="email" type="email" value="<?= esc(old('email'), 'attr') ?>" maxlength="150" required></div><?php if (isset($createErrors['email'])): ?><p class="field-error"><?= esc($createErrors['email']) ?></p><?php endif ?></div>
                        <div><label for="new_phone">Nomor WhatsApp</label><div class="admin-input <?= isset($createErrors['phone']) ? 'has-error' : '' ?>"><input id="new_phone" name="phone" type="tel" value="<?= esc(old('phone'), 'attr') ?>" maxlength="30" placeholder="+62 812 3456 7890"></div><?php if (isset($createErrors['phone'])): ?><p class="field-error"><?= esc($createErrors['phone']) ?></p><?php endif ?></div>
                        <div><label for="new_role">Role</label><div class="admin-input admin-select <?= isset($createErrors['role_id']) ? 'has-error' : '' ?>"><select id="new_role" name="role_id" required><option value="">Pilih role</option><?php foreach ($roles as $role): ?><?php if ($role['code'] !== 'SUPER_ADMIN'): ?><option value="<?= esc((string) $role['id'], 'attr') ?>" <?= (int) old('role_id') === (int) $role['id'] ? 'selected' : '' ?>><?= esc($role['name']) ?></option><?php endif ?><?php endforeach ?></select></div><?php if (isset($createErrors['role_id'])): ?><p class="field-error"><?= esc($createErrors['role_id']) ?></p><?php endif ?></div>
                        <div class="new-user-password"><label for="new_user_password">Password sementara</label><div class="admin-input <?= isset($createErrors['password']) ? 'has-error' : '' ?>"><input id="new_user_password" name="password" type="password" maxlength="255" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle="new_user_password" aria-label="Tampilkan password"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div><p class="password-hint">Minimal 12 karakter: huruf besar, kecil, angka, dan simbol.</p><?php if (isset($createErrors['password'])): ?><p class="field-error"><?= esc($createErrors['password']) ?></p><?php endif ?></div>
                        <button class="settings-submit new-user-submit" type="submit">Buat akun</button>
                    </form>
                </section>

                <section class="permission-section" id="permissions" aria-labelledby="permissions-title">
                    <div class="permission-heading"><span class="login-eyebrow">Role Permission</span><h2 id="permissions-title">Permission matrix</h2><p>Permission pengelolaan user dan role selalu eksklusif untuk Super Admin.</p></div>
                    <div class="role-permission-grid">
                        <?php foreach ($roles as $role): ?>
                            <?php $isSuperRole = $role['code'] === 'SUPER_ADMIN'; $assigned = $rolePermissions[(int) $role['id']] ?? []; ?>
                            <form class="role-permission-card <?= $isSuperRole ? 'super-role-card' : '' ?>" action="<?= site_url('adminhrdmannakampus/akses/roles/' . $role['id'] . '/permissions') ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="role-card-heading"><span class="role-mark"><?= esc(mb_substr((string) $role['name'], 0, 2)) ?></span><div><h3><?= esc($role['name']) ?></h3><p><?= esc($role['description']) ?></p></div></div>
                                <div class="permission-options">
                                    <?php foreach ($permissions as $permission): ?>
                                        <label><input type="checkbox" name="permissions[]" value="<?= esc((string) $permission['id'], 'attr') ?>" <?= $isSuperRole || in_array((int) $permission['id'], $assigned, true) ? 'checked' : '' ?> <?= $isSuperRole ? 'disabled' : '' ?>><span><strong><?= esc($permission['name']) ?></strong><small><?= esc($permission['description']) ?></small></span></label>
                                    <?php endforeach ?>
                                </div>
                                <?php if ($isSuperRole): ?><span class="all-access-note">Akses penuh permanen</span><?php else: ?><button class="save-permissions" type="submit">Simpan permission</button><?php endif ?>
                            </form>
                        <?php endforeach ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
