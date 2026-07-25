<?php
$appConfig = config('App');
$baseUrl = rtrim((string) $appConfig->baseURL, '/');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#fbf8f1">
    <title>Halaman Tidak Ditemukan | Manna Kampus</title>
    <link rel="icon" href="<?= esc($baseUrl) ?>/favicon.ico">
    <link rel="stylesheet" href="<?= esc($baseUrl) ?>/assets/css/career.css?v=18">
    <link rel="stylesheet" href="<?= esc($baseUrl) ?>/assets/css/not-found.css?v=1">
</head>
<body>
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand header-brand" href="<?= esc($baseUrl) ?>/#homepage" aria-label="Manna Kampus - kembali ke beranda">
                <img class="header-logo" src="<?= esc($baseUrl) ?>/assets/img/Logo_Manna_Kampus.png" alt="Manna Kampus">
            </a>

            <nav class="not-found-nav" aria-label="Navigasi halaman tidak ditemukan">
                <a href="<?= esc($baseUrl) ?>/">Homepage</a>
                <a href="<?= esc($baseUrl) ?>/lowongan">Lowongan</a>
            </nav>
        </div>
    </header>

    <main class="not-found-main">
        <div class="not-found-pattern" aria-hidden="true"></div>
        <div class="container not-found-layout">
            <section class="not-found-copy" aria-labelledby="not-found-title">
                <span class="not-found-eyebrow"><i></i> Error 404</span>
                <h1 id="not-found-title">Sepertinya Anda<br><em>tersesat sedikit.</em></h1>
                <p>Halaman yang Anda cari tidak tersedia, sudah dipindahkan, atau alamat yang dimasukkan kurang tepat.</p>
                <div class="not-found-actions">
                    <a class="not-found-button not-found-button-primary" href="<?= esc($baseUrl) ?>/">Kembali ke Homepage <span aria-hidden="true">→</span></a>
                    <a class="not-found-button not-found-button-secondary" href="<?= esc($baseUrl) ?>/lowongan">Lihat Lowongan</a>
                </div>
                <span class="not-found-help">Masih mengalami kendala? Periksa kembali alamat URL Anda.</span>
            </section>

            <div class="not-found-visual" aria-hidden="true">
                <span class="number number-four">4</span>
                <div class="compass">
                    <span class="compass-ring"></span>
                    <span class="compass-needle"></span>
                    <strong>MK</strong>
                </div>
                <span class="number number-zero">0</span>
                <span class="number number-last">4</span>
                <div class="route-line"><i></i><i></i><i></i></div>
                <span class="visual-label">Halaman tidak ditemukan</span>
            </div>
        </div>
    </main>

    <footer class="not-found-footer">
        <div class="container">
            <span>© <?= date('Y') ?> Manna Kampus</span>
            <span>Ruang untuk belajar, bertumbuh, dan memberi dampak.</span>
        </div>
    </footer>
</body>
</html>
