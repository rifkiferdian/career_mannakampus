<?php $activeMenu = (string) ($activeMenu ?? ''); ?>
<header class="site-header"<?= $activeMenu === 'homepage' ? ' id="homepage"' : '' ?>>
    <div class="container nav-wrap">
        <a class="brand header-brand" href="<?= base_url() ?>#homepage" aria-label="Manna Kampus - kembali ke beranda">
            <img class="header-logo" src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
            <span class="sr-only">Buka menu</span>
            <span></span><span></span><span></span>
        </button>

        <nav class="primary-nav" id="primary-nav" aria-label="Navigasi utama">
            <a<?= $activeMenu === 'homepage' ? ' class="active" aria-current="page"' : '' ?> href="<?= base_url() ?>#homepage">Homepage</a>
            <a<?= $activeMenu === 'vacancies' ? ' class="active" aria-current="page"' : '' ?> href="<?= site_url('lowongan') ?>">Lowongan</a>
            <a<?= $activeMenu === 'selection' ? ' class="active" aria-current="page"' : '' ?> href="<?= site_url('tahapan-seleksi') ?>">Tahapan Seleksi</a>
            <a<?= $activeMenu === 'status' ? ' class="active" aria-current="page"' : '' ?> href="<?= site_url('lamaran/status') ?>">Cek Status</a>
            <a href="<?= base_url() ?>#faq">FAQ</a>
        </nav>
    </div>
</header>
