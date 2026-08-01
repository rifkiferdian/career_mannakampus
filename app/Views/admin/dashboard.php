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
        <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'dashboard']) ?>

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
