<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Daftar akun karier Manna Kampus dan mulai perjalanan karier Anda.">
    <meta name="theme-color" content="#fbfaf8">
    <title>Daftar Akun Karier | Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=10">
    <link rel="stylesheet" href="<?= base_url('assets/css/register.css') ?>?v=7">
</head>
<body>
    <a class="skip-link" href="#register-form">Langsung ke formulir pendaftaran</a>

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

    <main class="register-page">
        <section class="register-story" aria-labelledby="register-title">
            <div class="story-copy">
                <span class="story-eyebrow">Karier Manna Kampus</span>
                <h1 id="register-title">Mulai Karir Anda di<br><em>Rumah Belanja<br>Terpercaya.</em></h1>
                <p>Bergabunglah dengan tim profesional kami untuk menciptakan pengalaman belanja terbaik bagi pelanggan dan tumbuh bersama Manna Kampus.</p>
            </div>

            <div class="story-photo" role="img" aria-label="Tim bekerja bersama di kantor yang modern"></div>

            <div class="story-social-proof">
                <div class="story-avatars" aria-hidden="true"><i>MK</i><i>HR</i><i>+1k</i></div>
                <p><strong>1.000+</strong> karyawan telah bergabung dan bertumbuh bersama kami.</p>
            </div>
        </section>

        <section class="register-panel" aria-labelledby="form-title">
            <div class="register-card">
                <div class="register-card-heading">
                    <span class="form-mark" aria-hidden="true">MK</span>
                    <div>
                        <h2 id="form-title">Daftar Akun Karier</h2>
                        <p>Lengkapi data diri Anda untuk memulai lamaran.</p>
                    </div>
                </div>

                <?php if (session('register_success')): ?>
                    <div class="form-alert form-alert-success" role="status">
                        <strong>Registrasi berhasil</strong>
                        <span><?= esc(session('register_success')) ?></span>
                    </div>
                <?php endif ?>

                <?php if (session('register_error')): ?>
                    <div class="form-alert form-alert-error" role="alert">
                        <strong>Terjadi kendala</strong>
                        <span><?= esc(session('register_error')) ?></span>
                    </div>
                <?php endif ?>

                <?php $errors = session('errors') ?? []; ?>

                <form id="register-form" action="<?= site_url('daftar') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                        <label for="full-name">Nama Lengkap</label>
                        <input id="full-name" name="full_name" type="text" value="<?= esc(old('full_name')) ?>" placeholder="Sesuai identitas resmi" autocomplete="name" required maxlength="150">
                        <?php if (isset($errors['full_name'])): ?><small><?= esc($errors['full_name']) ?></small><?php endif ?>
                    </div>

                    <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" placeholder="Alamat email aktif" autocomplete="email" required maxlength="150">
                        <?php if (isset($errors['email'])): ?><small><?= esc($errors['email']) ?></small><?php endif ?>
                    </div>

                    <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                        <label for="phone">Nomor WhatsApp</label>
                        <input id="phone" name="phone" type="tel" value="<?= esc(old('phone')) ?>" placeholder="Contoh: 08123456789" autocomplete="tel" required maxlength="20">
                        <?php if (isset($errors['phone'])): ?><small><?= esc($errors['phone']) ?></small><?php endif ?>
                    </div>

                    <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required minlength="8" maxlength="72">
                            <button class="password-toggle" type="button" data-target="password" aria-label="Tampilkan password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                        <div class="password-strength" aria-hidden="true"><i></i><i></i><i></i></div>
                        <?php if (isset($errors['password'])): ?><small><?= esc($errors['password']) ?></small><?php endif ?>
                    </div>

                    <div class="form-group <?= isset($errors['password_confirm']) ? 'has-error' : '' ?>">
                        <label for="password-confirm">Konfirmasi Password</label>
                        <div class="password-field">
                            <input id="password-confirm" name="password_confirm" type="password" placeholder="Ulangi password" autocomplete="new-password" required minlength="8" maxlength="72">
                            <button class="password-toggle" type="button" data-target="password-confirm" aria-label="Tampilkan konfirmasi password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?><small><?= esc($errors['password_confirm']) ?></small><?php endif ?>
                    </div>

                    <fieldset class="security-box <?= isset($errors['captcha']) ? 'has-error' : '' ?>">
                        <legend>Verifikasi Keamanan</legend>
                        <div class="captcha-row">
                            <span class="captcha-code" aria-label="Kode keamanan <?= esc(implode(' ', str_split($captcha))) ?>"><?= esc($captcha) ?></span>
                            <label class="sr-only" for="captcha">Masukkan kode keamanan</label>
                            <input id="captcha" name="captcha" type="text" placeholder="Ketik kode" autocomplete="off" required maxlength="5">
                            <a class="captcha-refresh" href="<?= site_url('daftar') ?>?refresh=1" aria-label="Buat kode keamanan baru">↻</a>
                        </div>
                        <?php if (isset($errors['captcha'])): ?><small><?= esc($errors['captcha']) ?></small><?php endif ?>
                    </fieldset>

                    <div class="terms-field <?= isset($errors['terms']) ? 'has-error' : '' ?>">
                        <label>
                            <input name="terms" type="checkbox" value="1" <?= old('terms') ? 'checked' : '' ?> required>
                            <span class="custom-checkbox" aria-hidden="true"></span>
                            <span>Saya menyetujui syarat dan ketentuan serta pemrosesan data pribadi sesuai kebijakan privasi Manna Kampus.</span>
                        </label>
                        <?php if (isset($errors['terms'])): ?><small><?= esc($errors['terms']) ?></small><?php endif ?>
                    </div>

                    <button class="register-submit" type="submit">Daftar Sekarang <span aria-hidden="true">→</span></button>
                </form>

                <p class="login-prompt">Sudah punya akun? <a href="<?= site_url('masuk') ?>">Masuk di sini</a></p>
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
            <a class="back-top" href="#register-form">Kembali ke formulir ↑</a>
        </div>
        <div class="container footer-bottom">
            <span>© <?= date('Y') ?> Manna Kampus. All rights reserved.</span>
            <div><a href="<?= site_url('lowongan') ?>">Karier</a><a href="<?= base_url() ?>#faq">FAQ</a></div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/career.js') ?>?v=6" defer></script>
    <script src="<?= base_url('assets/js/register.js') ?>?v=2" defer></script>
</body>
</html>
