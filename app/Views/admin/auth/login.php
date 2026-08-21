<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal internal HRD Manna Kampus.">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Login HRD | Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=25">
</head>
<body class="admin-login-page">
    <main class="login-shell">
        <section class="login-brand-panel" aria-label="Portal HRD Manna Kampus">
            <div class="login-brand-content">
                <a href="<?= base_url() ?>" class="admin-brand admin-brand-light" aria-label="Kembali ke halaman karier">
                    <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
                </a>
                <span class="internal-badge">Portal Internal</span>
                <h1>Kelola proses rekrutmen dalam satu ruang kerja.</h1>
                <p>Akses khusus tim HRD untuk memantau kandidat, lowongan, dan perjalanan seleksi.</p>
                <div class="login-security-note">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v6c0 5-3.4 8.2-8 9.8C7.4 20.2 4 17 4 12V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                    <span><strong>Akses terlindungi</strong>Hanya akun HRD aktif yang dapat masuk.</span>
                </div>
            </div>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-card">
                <div class="mobile-login-logo">
                    <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
                </div>
                <span class="login-eyebrow">HRD Administration</span>
                <h2 id="login-title">Selamat datang kembali</h2>
                <p class="login-intro">Masukkan akun HRD Anda untuk melanjutkan ke dashboard.</p>

                <?php if ($message = session()->getFlashdata('auth_success')): ?>
                    <div class="admin-alert admin-alert-success" data-swal-toast="success" role="status"><?= esc($message) ?></div>
                <?php endif ?>

                <?php if (! empty($error)): ?>
                    <div class="admin-alert admin-alert-error" data-swal-toast="error" role="alert"><?= esc($error) ?></div>
                <?php endif ?>

                <form class="admin-login-form" action="<?= site_url('adminhrdmannakampus') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <label for="email">Email HRD</label>
                    <div class="admin-input <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                        <input id="email" name="email" type="email" value="<?= esc($email ?? old('email'), 'attr') ?>" placeholder="nama@mannakampus.id" maxlength="190" autocomplete="username" required autofocus>
                    </div>
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= esc($errors['email']) ?></p><?php endif ?>

                    <label for="password">Password</label>
                    <div class="admin-input <?= isset($errors['password']) ? 'has-error' : '' ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" placeholder="Masukkan password" maxlength="255" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" aria-label="Tampilkan password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?><p class="field-error"><?= esc($errors['password']) ?></p><?php endif ?>

                    <button class="admin-primary-button" type="submit">Masuk ke Dashboard <span aria-hidden="true">→</span></button>
                </form>

                <p class="login-help">Kesulitan mengakses akun? Hubungi administrator sistem internal.</p>
            </div>
        </section>
    </main>
    <script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=7" defer></script>
</body>
</html>
