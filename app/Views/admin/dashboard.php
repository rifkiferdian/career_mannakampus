<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Dashboard HRD | Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=4">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <a href="<?= site_url('adminhrdmannakampus/dashboard') ?>" class="admin-brand sidebar-brand">
                <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
            </a>
            <span class="sidebar-caption">HRD Administration</span>

            <nav class="admin-nav" aria-label="Navigasi dashboard HRD">
                <a class="active" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/></svg>
                    Dashboard
                </a>
                <a href="<?= site_url('adminhrdmannakampus/profil') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                    Profil &amp; Keamanan
                </a>
                <?php if (($auth['role'] ?? '') === 'SUPER_ADMIN'): ?>
                    <a href="<?= site_url('adminhrdmannakampus/akses') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><path d="M3 19a5 5 0 0 1 10 0M16 7h5M18.5 4.5v5M15 15h6M18 12v6"/></svg>
                        User &amp; Akses
                    </a>
                <?php endif ?>
                <?php if (! empty($canViewDepartments)): ?>
                    <a href="<?= site_url('adminhrdmannakampus/departemen') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M8 10h2M14 10h2"/></svg>
                        Departemen
                    </a>
                <?php endif ?>
                <?php if (! empty($canViewRecruitmentSettings)): ?>
                    <a href="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen') ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg>
                        Pengaturan Rekrutmen
                    </a>
                <?php endif ?>
                <span class="admin-nav-disabled" title="Segera tersedia">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>
                    Kandidat <small>Segera</small>
                </span>
                <span class="admin-nav-disabled" title="Segera tersedia">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg>
                    Lowongan <small>Segera</small>
                </span>
            </nav>

            <div class="sidebar-user">
                <span class="user-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($auth['name'] ?? 'H'), 0, 1))) ?></span>
                <span><strong><?= esc($auth['name'] ?? 'Admin HRD') ?></strong><small><?= esc($auth['email'] ?? '') ?></small></span>
            </div>
            <form action="<?= site_url('adminhrdmannakampus/logout') ?>" method="post">
                <?= csrf_field() ?>
                <button class="logout-button" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>
                    Keluar
                </button>
            </form>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi">
                    <span></span><span></span><span></span>
                </button>
                <div><span>Portal Rekrutmen</span><strong>Dashboard HRD</strong></div>
                <a class="view-career-link" href="<?= base_url() ?>" target="_blank" rel="noopener">Lihat situs karier ↗</a>
            </header>

            <div class="admin-content">
                <?php if (! empty($success)): ?>
                    <div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div>
                <?php endif ?>
                <?php if (! empty($error)): ?>
                    <div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div>
                <?php endif ?>

                <section class="dashboard-welcome" aria-labelledby="dashboard-title">
                    <div>
                        <span class="login-eyebrow">Ringkasan Rekrutmen</span>
                        <h1 id="dashboard-title">Halo, <?= esc(explode(' ', (string) ($auth['name'] ?? 'Admin'))[0]) ?>.</h1>
                        <p>Pantau aktivitas rekrutmen Manna Kampus dari satu dashboard.</p>
                    </div>
                    <span class="dashboard-date"><?= esc(date('d M Y')) ?></span>
                </section>

                <section class="metric-grid" aria-label="Ringkasan data rekrutmen">
                    <article class="metric-card metric-orange">
                        <span class="metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg></span>
                        <p><span>Lowongan aktif</span><strong><?= esc((string) $openVacancies) ?></strong></p>
                    </article>
                    <article class="metric-card metric-blue">
                        <span class="metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg></span>
                        <p><span>Total kandidat</span><strong><?= esc((string) $applicantCount) ?></strong></p>
                    </article>
                    <article class="metric-card metric-green">
                        <span class="metric-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span>
                        <p><span>Total pengajuan</span><strong><?= esc((string) $applicationCount) ?></strong></p>
                    </article>
                </section>

                <section class="dashboard-panel" aria-labelledby="recent-title">
                    <div class="panel-heading">
                        <div><span>Aktivitas terbaru</span><h2 id="recent-title">Pengajuan lamaran terbaru</h2></div>
                    </div>

                    <?php if ($recentApplications === []): ?>
                        <div class="dashboard-empty"><span>◎</span><strong>Belum ada pengajuan</strong><p>Pengajuan kandidat terbaru akan tampil di sini.</p></div>
                    <?php else: ?>
                        <div class="application-table-wrap">
                            <table class="application-table">
                                <thead><tr><th>Kandidat</th><th>Nomor pengajuan</th><th>Posisi</th><th>Tanggal</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recentApplications as $application): ?>
                                        <tr>
                                            <td><strong><?= esc($application['full_name']) ?></strong><small><?= esc($application['email']) ?></small></td>
                                            <td><code><?= esc($application['batch_number']) ?></code></td>
                                            <td><?= esc((string) $application['position_count']) ?> posisi</td>
                                            <td><?= esc(date('d M Y', strtotime((string) $application['submitted_at']))) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </section>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=1" defer></script>
</body>
</html>
