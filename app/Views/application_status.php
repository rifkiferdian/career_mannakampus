<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Periksa perkembangan status lamaran kerja Anda di Manna Kampus.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#12372a">
    <title>Cek Status Lamaran | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=25">
    <link rel="stylesheet" href="<?= base_url('assets/css/application-status.css') ?>?v=8">
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <?= view('partials/public_header', ['activeMenu' => 'status']) ?>

    <main id="main-content" class="status-page">
        <section class="status-hero" aria-labelledby="status-title">
            <div class="container status-layout">
                <div class="status-intro">
                    <span class="status-eyebrow"><span></span> Status Lamaran</span>
                    <h1 id="status-title">Pantau proses<br><em>lamaranmu.</em></h1>
                    <p>Masukkan 16 digit NIK untuk melihat seluruh perkembangan lamaran yang pernah Anda kirim.</p>

                    <div class="status-security-note">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 10V8a5 5 0 0 1 10 0v2"/><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M12 14v2"/>
                        </svg>
                        <span>
                            <strong>Pengecekan aman</strong>
                            NIK tidak ditampilkan dan tidak dikirim melalui alamat URL.
                        </span>
                    </div>
                </div>

                <div class="status-form-card">
                    <div class="status-form-heading">
                        <span>Periksa pengajuan</span>
                        <h2>Masukkan NIK pelamar</h2>
                        <p>Sistem akan mencocokkan NIK dengan seluruh riwayat lamaran Anda.</p>
                    </div>

                    <?php if (! empty($error)): ?>
                        <div class="status-alert" role="alert"><?= esc($error) ?></div>
                    <?php endif ?>

                    <form action="<?= site_url('lamaran/status') ?>" method="post" autocomplete="off" data-status-form>
                        <?= csrf_field() ?>

                        <label class="status-field" for="status-nik">
                            <span>NIK <b>*</b></span>
                            <input
                                id="status-nik"
                                name="nik"
                                type="password"
                                inputmode="numeric"
                                pattern="[0-9]{16}"
                                minlength="16"
                                maxlength="16"
                                placeholder="Masukkan 16 digit NIK"
                                aria-describedby="status-nik-help<?= isset($errors['nik']) ? ' status-nik-error' : '' ?>"
                                <?= isset($errors['nik']) ? 'aria-invalid="true"' : '' ?>
                                required
                            >
                            <small id="status-nik-help">NIK disembunyikan saat diketik.</small>
                            <?php if (isset($errors['nik'])): ?>
                                <em id="status-nik-error"><?= esc($errors['nik']) ?></em>
                            <?php endif ?>
                        </label>

                        <button class="status-submit" type="submit" data-status-submit>
                            <span data-status-submit-label>Cek Status Lamaran</span>
                            <span aria-hidden="true">&rarr;</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <?php if (is_array($result ?? null)): ?>
            <dialog class="status-result-modal" data-status-result-modal aria-labelledby="result-title">
                <div class="status-result-modal-panel">
                    <div class="status-result-modal-toolbar">
                        <span>Hasil status lamaran</span>
                        <button class="status-result-close" type="button" data-status-result-close aria-label="Tutup hasil pengecekan">&times;</button>
                    </div>
                    <section class="status-result-section">
                        <div class="container status-result">
                    <div class="status-result-heading">
                        <div>
                            <span class="status-eyebrow"><span></span> Hasil Pencarian</span>
                            <h2 id="result-title">Riwayat lamaran ditemukan</h2>
                        </div>
                        <span class="status-found-badge">Data terverifikasi</span>
                    </div>

                    <dl class="status-summary">
                        <div>
                            <dt>Total Pengajuan</dt>
                            <dd><?= (int) $result['batch_count'] ?> pengajuan</dd>
                        </div>
                        <div>
                            <dt>Nama Pelamar</dt>
                            <dd><?= esc($result['applicant_name']) ?></dd>
                        </div>
                        <?php if ($result['applicant_email'] !== ''): ?>
                            <div>
                                <dt>Email</dt>
                                <dd><?= esc($result['applicant_email']) ?></dd>
                            </div>
                        <?php endif ?>
                        <?php if ($result['applicant_phone'] !== ''): ?>
                            <div>
                                <dt>Nomor WhatsApp</dt>
                                <dd><?= esc($result['applicant_phone']) ?></dd>
                            </div>
                        <?php endif ?>
                        <div>
                            <dt>Terakhir Melamar</dt>
                            <dd><?= esc($result['submitted_at']) ?></dd>
                        </div>
                    </dl>

                    <div class="status-position-heading">
                        <h3>Status setiap posisi</h3>
                        <span><?= (int) $result['position_count'] ?> posisi dilamar</span>
                    </div>

                    <div class="status-applications">
                        <?php foreach ($result['applications'] as $application): ?>
                            <article class="status-application-card">
                                <div class="status-priority">
                                    <span><?= (int) $application['preference_order'] ?></span>
                                    <small>Prioritas</small>
                                </div>
                                <div class="status-application-main">
                                    <span class="status-department"><?= esc($application['department_name']) ?></span>
                                    <h3><?= esc($application['vacancy_title']) ?></h3>
                                    <p><?= esc($application['status_description']) ?></p>
                                    <?php if ($application['public_message'] !== ''): ?>
                                        <div class="status-public-message"><?= esc($application['public_message']) ?></div>
                                    <?php endif ?>
                                </div>
                                <div class="status-application-meta">
                                    <span class="status-badge status-badge-<?= esc($application['status_tone'], 'attr') ?>">
                                        <?= esc($application['status_label']) ?>
                                    </span>
                                    <small>Diperbarui <?= esc($application['updated_at']) ?></small>
                                </div>
                            </article>
                        <?php endforeach ?>
                    </div>

                    <p class="status-result-note">Perkembangan berikutnya akan disampaikan melalui email atau WhatsApp yang dicantumkan saat melamar.</p>
                        </div>
                    </section>
                </div>
            </dialog>
        <?php endif ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-top">
            <a class="brand brand-light" href="<?= base_url() ?>#homepage">
                <img class="footer-logo" src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
            </a>
            <p>Ruang untuk belajar, bertumbuh, dan memberi dampak.</p>
            <a class="back-top" href="#main-content">Kembali ke atas &uarr;</a>
        </div>
        <div class="container footer-bottom">
            <span>&copy; <?= date('Y') ?> Manna Kampus. All rights reserved.</span>
            <div>
                <a href="<?= site_url('lowongan') ?>">Karier</a>
                <a href="<?= site_url('lamaran/status') ?>">Cek Status</a>
                <a href="<?= base_url() ?>#faq">FAQ</a>
            </div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/career.js') ?>?v=11" defer></script>
    <script src="<?= base_url('assets/js/application-status.js') ?>?v=3" defer></script>
</body>
</html>
