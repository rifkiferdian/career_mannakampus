<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= esc($title) ?> | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=84">
    <link rel="stylesheet" href="<?= base_url('assets/css/recruitment-agenda.css') ?>?v=24">
</head>
<body class="admin-dashboard-page agenda-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'recruitment-agenda']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>Rekrutmen</span><strong>Agenda Seleksi</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/kalender-rekrutmen') ?>">Kalender Rekrutmen</a>
        </header>
        <div class="admin-content agenda-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error" role="alert"><?= esc($error) ?></div><?php endif ?>
            <?= view($contentView) ?>
        </div>
        <?= view('admin/partials/footer') ?>
    </main>
</div>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=14" defer></script>
<script src="<?= base_url('assets/js/recruitment-agenda.js') ?>?v=3" defer></script>
</body>
</html>
