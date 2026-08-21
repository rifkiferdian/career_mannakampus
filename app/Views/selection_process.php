<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pelajari tahapan seleksi dan proses rekrutmen di Manna Kampus.">
    <meta name="theme-color" content="#12372a">
    <title>Tahapan Seleksi | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=25">
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <?= view('partials/public_header', ['activeMenu' => 'selection']) ?>

    <main id="main-content" class="selection-page">
        <section class="selection-hero" aria-labelledby="selection-title">
            <div class="container selection-hero-content reveal">
                <span class="selection-kicker">Mulai Karirmu</span>
                <h1 id="selection-title">Tahapan Seleksi Karir</h1>
                <p>Kami mencari individu yang berdedikasi untuk bergabung dengan keluarga besar Manna Kampus. Ikuti panduan langkah demi langkah proses rekrutmen kami di bawah ini.</p>
            </div>
        </section>

        <section class="section process-section selection-process" id="tahapan-seleksi" aria-labelledby="process-title">
            <div class="container">
                <h2 class="sr-only" id="process-title">Lima tahapan seleksi karir Manna Kampus</h2>

                <ol class="selection-timeline reveal" aria-label="Alur tahapan seleksi">
                    <li class="active"><span>1</span><strong>Administrasi</strong></li>
                    <li><span>2</span><strong>Tes Tertulis</strong></li>
                    <li><span>3</span><strong>Interview HR</strong></li>
                    <li><span>4</span><strong>Interview User</strong></li>
                    <li><span>5</span><strong>Onboarding</strong></li>
                </ol>

                <div class="selection-cards">
                    <article class="selection-card featured reveal">
                        <div class="selection-card-heading">
                            <span class="selection-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7zM14 3v5h5M10 12h5M10 16h5"/></svg></span>
                            <h3>Administrasi &amp;<br>Screening</h3>
                        </div>
                        <p>Tim rekrutmen kami akan meninjau berkas lamaran PDF dan kelengkapan dokumen Anda sesuai kualifikasi posisi.</p>
                        <ul><li>Pastikan seluruh dokumen terbaru dan dapat dibaca</li><li>Gabungkan semua berkas dalam satu file PDF</li></ul>
                    </article>

                    <article class="selection-card reveal">
                        <div class="selection-card-heading">
                            <span class="selection-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM10 4V2h4v2M10 9h4M10 13h4M10 17h2"/></svg></span>
                            <h3>Tes Tertulis / Psikotes</h3>
                        </div>
                        <p>Uji potensi akademik, kepribadian, dan kemampuan teknis dasar sesuai dengan bidang yang Anda lamar.</p>
                        <ul><li>Siapkan alat tulis lengkap</li><li>Hadir 15 menit sebelum jadwal</li></ul>
                    </article>

                    <article class="selection-card reveal">
                        <div class="selection-card-heading">
                            <span class="selection-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H9l-5 4V5ZM8 9h8M8 12h5"/></svg></span>
                            <h3>Interview HR</h3>
                        </div>
                        <p>Diskusi mendalam mengenai motivasi, budaya kerja, dan kecocokan nilai-nilai pribadi Anda dengan Manna Kampus.</p>
                        <ul><li>Berpakaian rapi dan sopan</li><li>Pahami profil Manna Kampus</li></ul>
                    </article>

                    <article class="selection-card reveal">
                        <div class="selection-card-heading">
                            <span class="selection-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 20c0-4 2.5-6 6-6s6 2 6 6M15 15c3 0 5 1.7 5 5"/></svg></span>
                            <h3>Interview User / Panel</h3>
                        </div>
                        <p>Wawancara teknis bersama calon atasan atau panel ahli untuk menilai kompetensi spesifik pada peran tersebut.</p>
                        <ul><li>Siapkan studi kasus atau contoh kerja</li><li>Tanyakan detail operasional peran</li></ul>
                    </article>

                    <article class="selection-card reveal">
                        <div class="selection-card-heading">
                            <span class="selection-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m4 15 3-3 3 3 7-8 3 3-10 10-6-5Z"/><path d="M16 3v4M20 5l-3 3M9 4l1 4"/></svg></span>
                            <h3>Offering &amp; Onboarding</h3>
                        </div>
                        <p>Pemberian surat penawaran kerja, penandatanganan kontrak, dan pengenalan lingkungan kerja.</p>
                        <ul><li>Siapkan dokumen asli untuk verifikasi</li><li>Selamat bergabung di Manna Kampus!</li></ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="selection-preparation" aria-labelledby="preparation-title">
            <div class="container">
                <div class="preparation-heading reveal">
                    <span class="eyebrow"><span></span> Sebelum Memulai</span>
                    <h2 id="preparation-title">Persiapkan versi terbaikmu.</h2>
                    <p>Beberapa hal sederhana ini akan membantumu menjalani proses dengan lebih percaya diri.</p>
                </div>
                <div class="preparation-grid">
                    <article class="preparation-card reveal">
                        <span aria-hidden="true">01</span>
                        <h3>Perbarui profilmu</h3>
                        <p>Pastikan berkas lamaran menampilkan pengalaman serta pencapaian yang paling relevan.</p>
                    </article>
                    <article class="preparation-card reveal">
                        <span aria-hidden="true">02</span>
                        <h3>Kenali perannya</h3>
                        <p>Pelajari deskripsi posisi dan siapkan contoh pengalaman yang menunjukkan kemampuanmu.</p>
                    </article>
                    <article class="preparation-card reveal">
                        <span aria-hidden="true">03</span>
                        <h3>Jadilah dirimu sendiri</h3>
                        <p>Ceritakan pengalaman dengan jujur. Kami ingin mengenal cara berpikir dan keunikanmu.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="selection-cta-section">
            <div class="container selection-cta reveal">
                <div>
                    <span class="eyebrow eyebrow-light"><span></span> Mulai Perjalananmu</span>
                    <h2>Sudah siap bergabung?</h2>
                    <p>Temukan posisi yang sesuai dengan kemampuan dan aspirasimu.</p>
                </div>
                <a class="button button-primary" href="<?= site_url('lowongan') ?>">Lihat Lowongan <span aria-hidden="true">→</span></a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-top">
            <a class="brand brand-light" href="<?= base_url() ?>#homepage">
                <img class="footer-logo" src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
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
</body>
</html>
