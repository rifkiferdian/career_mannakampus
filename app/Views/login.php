<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke akun karier Manna Kampus untuk melanjutkan proses lamaran.">
    <meta name="theme-color" content="#fbfaf8">
    <title>Masuk Akun Karier | Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=10">
    <link rel="stylesheet" href="<?= base_url('assets/css/register.css') ?>?v=7">
</head>
<body>
    <a class="skip-link" href="#login-form">Langsung ke formulir login</a>

    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand header-brand" href="<?= base_url() ?>#homepage" aria-label="Manna Kampus - kembali ke beranda">
                <img class="header-logo" src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                <span class="sr-only">Buka menu</span>
                <span></span><span></span><span></span>
            </button>

            <nav class="primary-nav" id="primary-nav" aria-label="Navigasi utama">
                <a href="<?= base_url() ?>#homepage">Homepage</a>
                <a href="<?= site_url('lowongan') ?>">Lowongan</a>
                <a href="<?= site_url('tahapan-seleksi') ?>">Tahapan Seleksi</a>
                <a href="<?= base_url() ?>#faq">FAQ</a>
                <a class="nav-auth nav-login" href="<?= site_url('masuk') ?>">Masuk</a>
                <a class="nav-auth nav-register" href="<?= site_url('daftar') ?>">Daftar</a>
            </nav>
        </div>
    </header>

    <main class="register-page login-layout">
        <section class="register-panel login-panel" aria-labelledby="login-title">
            <div class="register-card login-card">
                <div class="register-card-heading">
                    <span class="form-mark" aria-hidden="true">MK</span>
                    <div>
                        <h1 id="login-title">Masuk Akun Karier</h1>
                        <p>Lanjutkan perjalanan karier Anda bersama kami.</p>
                    </div>
                </div>

                <?php if ($alreadyLoggedIn): ?>
                    <div class="form-alert form-alert-success" role="status">
                        <strong>Anda sudah masuk</strong>
                        <span>Silakan lanjutkan untuk melihat lowongan yang tersedia.</span>
                    </div>
                    <a class="register-submit login-continue" href="<?= site_url('lowongan') ?>">Lihat Lowongan <span aria-hidden="true">→</span></a>
                <?php else: ?>
                    <?php if (session('login_error')): ?>
                        <div class="form-alert form-alert-error" role="alert">
                            <strong>Login belum berhasil</strong>
                            <span><?= esc(session('login_error')) ?></span>
                        </div>
                    <?php endif ?>

                    <?php $errors = session('login_errors') ?? []; ?>

                    <form id="login-form" action="<?= site_url('masuk') ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" placeholder="Alamat email terdaftar" autocomplete="email" required maxlength="150" autofocus>
                            <?php if (isset($errors['email'])): ?><small><?= esc($errors['email']) ?></small><?php endif ?>
                        </div>

                        <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                            <div class="login-label-row">
                                <label for="password">Password</label>
                                <span>Lupa password?</span>
                            </div>
                            <div class="password-field">
                                <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required maxlength="72">
                                <button class="password-toggle" type="button" data-target="password" aria-label="Tampilkan password">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?><small><?= esc($errors['password']) ?></small><?php endif ?>
                        </div>

                        <fieldset class="security-box login-security-box <?= isset($errors['captcha']) ? 'has-error' : '' ?>">
                            <legend>Verifikasi Keamanan</legend>
                            <div class="captcha-row">
                                <span class="captcha-code" aria-label="Kode keamanan <?= esc(implode(' ', str_split($captcha))) ?>"><?= esc($captcha) ?></span>
                                <label class="sr-only" for="captcha">Masukkan kode keamanan</label>
                                <input id="captcha" name="captcha" type="text" placeholder="Ketik kode" autocomplete="off" required maxlength="5">
                                <a class="captcha-refresh" href="<?= site_url('masuk') ?>?refresh=1" aria-label="Buat kode keamanan baru">↻</a>
                            </div>
                            <?php if (isset($errors['captcha'])): ?><small><?= esc($errors['captcha']) ?></small><?php endif ?>
                        </fieldset>

                        <button class="register-submit" type="submit">Masuk Sekarang <span aria-hidden="true">→</span></button>
                    </form>

                    <div class="login-divider"><span>atau</span></div>
                    <p class="login-prompt login-register-prompt">Belum punya akun? <a href="<?= site_url('daftar') ?>">Daftar sekarang</a></p>
                <?php endif ?>
            </div>
        </section>

        <section class="register-story login-story" aria-labelledby="story-title">
            <div class="story-copy">
                <span class="story-eyebrow">Selamat Datang Kembali</span>
                <h2 id="story-title">Lanjutkan langkahmu<br>menuju <em>karir<br>impian.</em></h2>
                <p>Masuk untuk melengkapi profil, melihat perkembangan lamaran, dan menemukan kesempatan baru di Manna Kampus.</p>
            </div>

            <div class="story-photo login-story-photo" role="img" aria-label="Karyawan Manna Kampus berkolaborasi di kantor"></div>

            <div class="story-social-proof">
                <div class="story-avatars" aria-hidden="true"><i>MK</i><i>HR</i><i>+1k</i></div>
                <p><strong>1.000+</strong> karyawan telah menjadi bagian dari perjalanan kami.</p>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-top">
            <a class="brand brand-light" href="<?= base_url() ?>#homepage">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 42 42"><path d="M9 30V13l12 9 12-9v17"/><path d="M9 13l12 17 12-17"/></svg></span>
                <span>Manna <strong>Kampus</strong></span>
            </a>
            <p>Ruang untuk belajar, bertumbuh, dan memberi dampak.</p>
            <a class="back-top" href="#login-form">Kembali ke formulir ↑</a>
        </div>
        <div class="container footer-bottom">
            <span>© <?= date('Y') ?> Manna Kampus. All rights reserved.</span>
            <div><a href="<?= site_url('lowongan') ?>">Karier</a><a href="<?= base_url() ?>#faq">FAQ</a></div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/career.js') ?>?v=7" defer></script>
    <script src="<?= base_url('assets/js/register.js') ?>?v=4" defer></script>
</body>
</html>
