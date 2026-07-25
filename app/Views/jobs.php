<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Lihat seluruh lowongan kerja yang sedang dibuka di Manna Kampus.">
    <meta name="theme-color" content="#12372a">
    <title>Lowongan Kerja | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=18">
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
                <a class="active" href="<?= site_url('lowongan') ?>" aria-current="page">Lowongan</a>
                <a href="<?= site_url('tahapan-seleksi') ?>">Tahapan Seleksi</a>
                <a href="<?= base_url() ?>#faq">FAQ</a>
            </nav>
        </div>
    </header>

    <main id="main-content" class="jobs-page">
        <section class="jobs-page-hero" aria-labelledby="jobs-page-title">
            <div class="container jobs-hero-content reveal">
                <h1 id="jobs-page-title">Temukan Karir Impianmu di<br><em>Manna Kampus</em></h1>
                <p>Bergabunglah dengan tim yang berdedikasi untuk memberikan pengalaman belanja terbaik dan dipercaya oleh masyarakat Yogyakarta &amp; Solo.</p>

                <form class="job-search" id="job-search-form" role="search" data-search-url="<?= site_url('lowongan/cari') ?>">
                    <label class="job-search-field" for="job-keyword">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
                        <span class="sr-only">Cari posisi, lokasi, atau pendidikan</span>
                        <input id="job-keyword" name="keyword" type="search" placeholder="Posisi, lokasi, atau pendidikan..." autocomplete="off">
                    </label>
                    <div class="job-search-field job-department-field custom-select" id="department-select">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h6v-6H4v6Zm10 0h6v-6h-6v6ZM9 10h6L12 4l-3 6Z"/></svg>
                        <input id="job-department" name="department" type="hidden" value="">
                        <button class="custom-select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="department-options">
                            <span>Semua Departemen</span>
                            <svg class="custom-select-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg>
                        </button>
                        <div class="custom-select-menu" id="department-options" role="listbox" aria-label="Pilih departemen">
                            <button class="custom-select-option selected" type="button" role="option" aria-selected="true" data-value="">Semua Departemen</button>
                            <?php foreach ($departments ?? [] as $departmentSlug => $departmentName): ?>
                                <button class="custom-select-option" type="button" role="option" aria-selected="false" data-value="<?= esc($departmentSlug, 'attr') ?>"><?= esc($departmentName) ?></button>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <button class="job-search-button" type="submit">Cari Lowongan</button>
                </form>
            </div>
        </section>

        <section class="jobs-board" aria-labelledby="open-positions-title">
            <div class="container">
                <div class="jobs-board-heading reveal">
                    <div>
                        <span class="eyebrow"><span></span> Open Positions</span>
                        <h2 id="open-positions-title">Lowongan yang tersedia</h2>
                    </div>
                    <span class="jobs-count" id="jobs-count" aria-live="polite"><?= count($vacancies ?? []) ?> posisi ditemukan</span>
                </div>

                <div class="job-openings" id="job-openings" aria-live="polite">
                    <?= view('partials/job_openings', ['vacancies' => $vacancies ?? []]) ?>
                </div>
            </div>
        </section>

        <section class="application-guide" id="cara-melamar" aria-labelledby="application-title">
            <div class="container application-card reveal">
                <div>
                    <span class="eyebrow eyebrow-light"><span></span> Cara Melamar</span>
                    <h2 id="application-title">Siapkan profil terbaikmu.</h2>
                    <p>Pilih posisi yang sesuai lalu klik Lamar Sekarang. Isi biodata dan screening tanpa perlu membuat akun, kemudian unggah CV dalam format PDF.</p>
                </div>
                <div class="application-actions">
                    <a class="button button-primary" href="#open-positions-title">Pilih Lowongan <span aria-hidden="true">↑</span></a>
                    <a class="application-process-link" href="<?= site_url('tahapan-seleksi') ?>">Pelajari Tahapan Seleksi</a>
                </div>
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
            <a class="back-top" href="#main-content">Kembali ke atas ↑</a>
        </div>
        <div class="container footer-bottom">
            <span>© <?= date('Y') ?> Manna Kampus. All rights reserved.</span>
            <div><a href="<?= site_url('lowongan') ?>">Karier</a><a href="<?= base_url() ?>#faq">FAQ</a></div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/career.js') ?>?v=11" defer></script>
    <script src="<?= base_url('assets/js/jobs.js') ?>?v=1" defer></script>
</body>
</html>
