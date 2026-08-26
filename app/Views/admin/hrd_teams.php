<?php
$activeTeamCount = count(array_filter($teams, static fn (array $team): bool => (bool) $team['is_active']));
$assignedUserCount = count(array_filter($users, static fn (array $user): bool => ! empty($user['hrd_team_id'])));
$oldFor = static fn (string $modal, string $field, mixed $fallback = ''): mixed => $openModal === $modal ? old($field, $fallback) : $fallback;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Tim HRD | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=66">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'hrd-teams']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button><div><span>Recruitment Team</span><strong>Tim HRD</strong></div><a class="view-career-link" href="<?= site_url('adminhrdmannakampus/list-pelamar') ?>">Lihat pelamar</a></header>
        <div class="admin-content access-content hrd-team-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading">
                <div><span class="login-eyebrow">Pembagian Pelamar</span><h1>Tim HRD</h1><p>Atur Divisi 1, Divisi 2, dan anggota yang berhak memilih serta memproses pelamar.</p></div>
                <?php if ($canManage): ?><button class="new-user-jump department-create-trigger" type="button" data-admin-modal-open="team-create-modal">+ Tambah divisi</button><?php else: ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
            </section>

            <section class="access-summary vacancy-summary" aria-label="Ringkasan tim HRD">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><path d="M3 19a5 5 0 0 1 10 0M16 8h5M18.5 5.5v5"/></svg></i><strong><?= count($teams) ?></strong><span>Total divisi</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= $activeTeamCount ?></strong><span>Divisi aktif</span></article>
                <article><i class="summary-card-icon icon-purple" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 10h5M16 14h5"/></svg></i><strong><?= $assignedUserCount ?></strong><span>User sudah dibagi</span></article>
                <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M17 12h4"/></svg></i><strong><?= count($users) - $assignedUserCount ?></strong><span>Belum memiliki divisi</span></article>
            </section>

            <section class="settings-card hrd-team-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5"/></svg></span><div><h2>Daftar divisi</h2><p>Satu user hanya dapat menjadi anggota satu divisi HRD.</p></div><span class="device-count"><?= count($teams) ?></span></div>
                <div class="department-table-wrap"><table class="department-table"><thead><tr><th>No.</th><th>Divisi</th><th>Keterangan</th><th>Anggota</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    <?php foreach ($teams as $index => $team): ?><tr class="<?= ! $team['is_active'] ? 'department-row-inactive' : '' ?>"><td><?= $index + 1 ?></td><td><div class="department-name-cell"><strong><?= esc($team['name']) ?></strong><code><?= esc($team['code']) ?></code></div></td><td class="department-description-cell"><?= esc($team['description'] ?: '-') ?></td><td><span class="department-vacancy-count"><?= (int) $team['member_count'] ?> user</span></td><td><span class="account-status <?= $team['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $team['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="department-table-actions"><?php if ($canManage): ?><button class="settings-edit-trigger table-action-icon table-action-edit" type="button" data-admin-modal-open="team-edit-modal-<?= (int) $team['id'] ?>" aria-label="Edit divisi" title="Edit divisi"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></button><?php else: ?><span class="protected-label">Lihat saja</span><?php endif ?></div></td></tr><?php endforeach ?>
                </tbody></table></div>
            </section>

            <section class="settings-card hrd-team-members-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg></span><div><h2>Anggota tim HRD</h2><p>Pilih divisi untuk setiap akun. User tanpa divisi belum dapat mengambil pelamar.</p></div><span class="device-count"><?= count($users) ?></span></div>
                <div class="department-table-wrap"><table class="department-table hrd-team-user-table"><thead><tr><th>No.</th><th>User</th><th>Status akun</th><th>Divisi HRD</th></tr></thead><tbody>
                    <?php foreach ($users as $index => $user): ?><tr><td><?= $index + 1 ?></td><td><div class="report-applicant"><strong><?= esc($user['full_name']) ?></strong><a href="mailto:<?= esc($user['email'], 'attr') ?>"><?= esc($user['email']) ?></a></div></td><td><span class="account-status <?= $user['is_active'] ? 'active' : 'inactive' ?>"><i></i><?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><?php if ($canManage): ?><form class="hrd-team-assignment-form" action="<?= site_url('adminhrdmannakampus/tim-hrd/user/' . $user['id']) ?>" method="post"><?= csrf_field() ?><select name="hrd_team_id" aria-label="Divisi <?= esc($user['full_name'], 'attr') ?>"><option value="">Belum memiliki divisi</option><?php foreach ($teams as $team): ?><?php if ($team['is_active'] || (int) $user['hrd_team_id'] === (int) $team['id']): ?><option value="<?= (int) $team['id'] ?>" <?= (int) $user['hrd_team_id'] === (int) $team['id'] ? 'selected' : '' ?>><?= esc($team['name']) ?><?= ! $team['is_active'] ? ' (Nonaktif)' : '' ?></option><?php endif ?><?php endforeach ?></select><button type="submit">Simpan</button></form><?php else: ?><span class="hrd-team-pill"><?= esc($user['hrd_team_name'] ?: 'Belum memiliki divisi') ?></span><?php endif ?></td></tr><?php endforeach ?>
                </tbody></table></div>
            </section>
        </div>
    <?= view('admin/partials/footer') ?>
    </main>
</div>

<?php if ($canManage): ?>
<dialog class="admin-modal" id="team-create-modal" aria-labelledby="team-create-title" <?= $openModal === 'create' ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span><div><h2 id="team-create-title">Tambah divisi HRD</h2><p>Buat kelompok pemilik proses pelamar.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="department-create-form hrd-team-form" action="<?= site_url('adminhrdmannakampus/tim-hrd') ?>" method="post"><?= csrf_field() ?><label>Nama divisi<input name="name" maxlength="120" value="<?= esc((string) $oldFor('create', 'name'), 'attr') ?>" placeholder="Contoh: Divisi 3" required></label><label>Kode<input name="code" maxlength="60" value="<?= esc((string) $oldFor('create', 'code'), 'attr') ?>" placeholder="divisi-3" required></label><label class="department-modal-description">Keterangan<textarea name="description" rows="3" maxlength="255"><?= esc((string) $oldFor('create', 'description')) ?></textarea></label><div class="department-modal-actions"><label class="department-active-check"><input type="checkbox" name="is_active" value="1" <?= (string) $oldFor('create', 'is_active', '1') === '1' ? 'checked' : '' ?>> Aktif</label><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Tambah divisi</button></div></form></div></dialog>
<?php foreach ($teams as $team): $modal = 'edit-' . $team['id']; ?>
<dialog class="admin-modal" id="team-edit-modal-<?= (int) $team['id'] ?>" aria-labelledby="team-edit-title-<?= (int) $team['id'] ?>" <?= $openModal === $modal ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/></svg></span><div><h2 id="team-edit-title-<?= (int) $team['id'] ?>">Edit <?= esc($team['name']) ?></h2><p>Perbarui nama, kode, atau status divisi.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="department-create-form hrd-team-form" action="<?= site_url('adminhrdmannakampus/tim-hrd/' . $team['id']) ?>" method="post"><?= csrf_field() ?><label>Nama divisi<input name="name" maxlength="120" value="<?= esc((string) $oldFor($modal, 'name', $team['name']), 'attr') ?>" required></label><label>Kode<input name="code" maxlength="60" value="<?= esc((string) $oldFor($modal, 'code', $team['code']), 'attr') ?>" required></label><label class="department-modal-description">Keterangan<textarea name="description" rows="3" maxlength="255"><?= esc((string) $oldFor($modal, 'description', $team['description'])) ?></textarea></label><div class="department-modal-actions"><label class="department-active-check"><input type="checkbox" name="is_active" value="1" <?= (string) $oldFor($modal, 'is_active', (string) $team['is_active']) === '1' ? 'checked' : '' ?>> Aktif</label><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan perubahan</button></div></form></div></dialog>
<?php endforeach ?>
<?php endif ?>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=7" defer></script>
</body>
</html>
