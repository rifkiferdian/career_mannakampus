<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Periksa perkembangan status lamaran kerja Anda di Manna Kampus.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#12372a">
    <title>Cek Status Lamaran | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=18">
    <link rel="stylesheet" href="<?= base_url('assets/css/application-status.css') ?>?v=1">
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

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
                <a class="active" href="<?= site_url('lamaran/status') ?>" aria-current="page">Cek Status</a>
                <a href="<?= base_url() ?>#faq">FAQ</a>
            </nav>
        </div>
    </header>

    <main id="main-content" class="status-page">
        <section class="status-hero" aria-labelledby="status-title">
            <div class="container status-layout">
                <div class="status-intro">
                    <span class="status-eyebrow"><span></span> Status Lamaran</span>
                    <h1 id="status-title">Pantau proses<br><em>lamaranmu.</em></h1>
                    <p>Masukkan NIK dan nomor pengajuan yang diterima setelah mengirim lamaran. Data ini digunakan hanya untuk mencocokkan riwayat pengajuanmu.</p>

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
                        <h2>Masukkan data lamaran</h2>
                        <p>Nomor pengajuan memiliki format seperti <strong>MKB-260725-XXXXXXXX</strong>.</p>
                    </div>

                    <?php if (! empty($error)): ?>
                        <div class="status-alert" role="alert"><?= esc($error) ?></div>
                    <?php endif ?>

                    <form action="<?= site_url('lamaran/status') ?>" method="post" autocomplete="off">
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

                        <label class="status-field" for="status-batch-number">
                            <span>Nomor Pengajuan <b>*</b></span>
                            <input
                                id="status-batch-number"
                                name="batch_number"
                                type="text"
                                value="<?= esc($batch_number ?? '', 'attr') ?>"
                                maxlength="30"
                                placeholder="Contoh: MKB-260725-XXXXXXXX"
                                aria-describedby="status-batch-help<?= isset($errors['batch_number']) ? ' status-batch-error' : '' ?>"
                                <?= isset($errors['batch_number']) ? 'aria-invalid="true"' : '' ?>
                                required
                            >
                            <small id="status-batch-help">Tersedia pada halaman setelah lamaran dikirim.</small>
                            <?php if (isset($errors['batch_number'])): ?>
                                <em id="status-batch-error"><?= esc($errors['batch_number']) ?></em>
                            <?php endif ?>
                        </label>

                        <button class="status-submit" type="submit">
                            Cek Status Lamaran
                            <span aria-hidden="true">&rarr;</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <?php if (is_array($result ?? null)): ?>
            <section class="status-result-section" aria-labelledby="result-title">
                <div class="container status-result">
                    <div class="status-result-heading">
                        <div>
                            <span class="status-eyebrow"><span></span> Hasil Pencarian</span>
                            <h2 id="result-title">Status pengajuan ditemukan</h2>
                        </div>
                        <span class="status-found-badge">Data terverifikasi</span>
                    </div>

                    <dl class="status-summary">
                        <div>
                            <dt>Nomor Pengajuan</dt>
                            <dd><?= esc($result['batch_number']) ?></dd>
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
                        <div>
                            <dt>Tanggal Melamar</dt>
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
                                    <small><?= esc($application['application_number']) ?></small>
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
        <?php endif ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-top">
            <a class="brand brand-light" href="<?= base_url() ?>#homepage">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 42 42"><path d="M9 30V13l12 9 12-9v17"/><path d="M9 13l12 17 12-17"/></svg></span>
                <span>Manna <strong>Kampus</strong></span>
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
</body>
</html>
