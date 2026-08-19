<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Temukan peluang karier dan tumbuh bersama Manna Kampus.">
    <meta name="theme-color" content="#12372a">
    <title>Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/career.css') ?>?v=25">
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <?= view('partials/public_header', ['activeMenu' => 'homepage']) ?>

    <main id="main-content">
        <section class="hero" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div class="hero-copy reveal">
                    <span class="hero-badge">#GrowWithManna</span>
                    <h1 id="hero-title">Temukan Karier Terbaik<br>Bersama <em>Manna Kampus</em></h1>
                    <p>Bergabunglah dengan ekosistem retail modern yang dinamis. Kami mencari talenta berbakat untuk memberikan pengalaman berbelanja terbaik bagi masyarakat.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="<?= site_url('lowongan') ?>">Lihat Lowongan</a>
                        <a class="button hero-status-button" href="<?= site_url('lamaran/status') ?>">Cek Status Lamaran</a>
                    </div>

                </div>

                <div class="hero-visual reveal">
                    <img src="<?= base_url('assets/img/career-hero-team.webp') ?>" alt="Empat profesional Manna Kampus berdiri bersama di kantor modern" width="900" height="1897" fetchpriority="high">
                </div>
            </div>
        </section>

        <section class="section jobs-section" id="lowongan" aria-labelledby="jobs-title">
            <div class="container">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow"><span></span> Peluang Karier</span>
                        <h2 id="jobs-title">Temukan posisi yang<br>sesuai denganmu.</h2>
                    </div>
                    <div class="section-heading-action">
                        <p>Jadilah bagian dari tim yang dinamis dan bantu kami menghadirkan pengalaman belanja yang lebih baik bagi setiap pelanggan.</p>
                        <a class="button button-outline" href="<?= site_url('lowongan') ?>">Lihat Semua Lowongan <span aria-hidden="true">→</span></a>
                    </div>
                </div>

                <div class="job-list">
                    <?php foreach ($vacancies ?? [] as $vacancy): ?>
                        <article class="job-card reveal">
                            <div class="job-icon <?= esc($vacancy['icon_class'], 'attr') ?>" aria-hidden="true"><?= esc($vacancy['icon_text']) ?></div>
                            <div class="job-main">
                                <div class="job-meta">
                                    <span><?= esc($vacancy['department'] ?: 'Umum') ?></span>
                                    <span><?= esc($vacancy['employment_type'] ?: 'Full-time') ?></span>
                                </div>
                                <h3><?= esc($vacancy['title']) ?></h3>
                                <p>Bergabung dan berkembang bersama tim <?= esc($vacancy['department'] ?: 'Manna Kampus') ?>.</p>
                                <div class="job-requirements" aria-label="Persyaratan minimum">
                                    <span><?= esc($vacancy['age_requirement']) ?></span>
                                    <span><?= esc($vacancy['education_requirement']) ?></span>
                                </div>
                            </div>
                            <div class="job-location"><span aria-hidden="true">⌖</span> <?= esc($vacancy['location'] ?: 'Yogyakarta') ?></div>
                            <a class="job-arrow" href="<?= site_url('lowongan') ?>#vacancy-<?= esc($vacancy['code'], 'attr') ?>" aria-label="Lihat detail lowongan <?= esc($vacancy['title'], 'attr') ?>">↗</a>
                        </article>
                    <?php endforeach ?>
                </div>

                <?php if (($vacancies ?? []) === []): ?>
                    <p class="jobs-note reveal">Belum ada lowongan yang sedang dibuka. Tetap pantau halaman ini untuk peluang berikutnya.</p>
                <?php else: ?>
                    <p class="jobs-note reveal">Belum menemukan posisi yang cocok? Tetap pantau halaman ini untuk peluang berikutnya.</p>
                <?php endif ?>
            </div>
        </section>

        <section class="benefits-section" aria-labelledby="benefits-title">
            <div class="container">
                <div class="benefits-heading reveal">
                    <h2 id="benefits-title">Kenapa Bergabung Dengan Kami?</h2>
                    <p>Kami membangun fondasi karir di atas nilai-nilai yang kuat untuk memastikan setiap<br class="desktop-break"> individu berkembang secara profesional dan personal.</p>
                </div>

                <div class="benefits-grid">
                    <article class="benefit-card reveal">
                        <span class="benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 32 32">
                                <path d="M10.5 20.5c-2-1.6-3.2-4-3.2-6.6A8.7 8.7 0 0 1 16 5.2a8.7 8.7 0 0 1 8.7 8.7c0 2.7-1.2 5-3.2 6.6-1.1.9-1.7 1.9-1.8 3.1h-7.4c-.1-1.2-.7-2.2-1.8-3.1Z"/>
                                <path d="M12.6 27h6.8M12.3 23.6h7.4"/>
                            </svg>
                        </span>
                        <h3>Innovation</h3>
                        <p>Kami selalu terbuka pada ide baru dan teknologi terkini untuk terus menjadi pemimpin di industri retail modern.</p>
                    </article>

                    <article class="benefit-card reveal">
                        <span class="benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 32 32">
                                <path d="M16 4.5 25 8v6.8c0 5.7-3.6 10.5-9 12.7-5.4-2.2-9-7-9-12.7V8l9-3.5Z"/>
                                <path d="m12 15.8 2.7 2.8 5.7-6"/>
                            </svg>
                        </span>
                        <h3>Integrity</h3>
                        <p>Kepercayaan pelanggan dimulai dari kejujuran dan etika kerja tinggi dari setiap anggota tim kami.</p>
                    </article>

                    <article class="benefit-card reveal">
                        <span class="benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 32 32">
                                <path d="m5.5 23 7.2-7.3 4.4 4.2 9.4-10"/>
                                <path d="M20.7 9.9h5.8v5.8"/>
                            </svg>
                        </span>
                        <h3>Growth</h3>
                        <p>Program pengembangan karir berkelanjutan untuk membantu Anda mencapai potensi maksimal di setiap level.</p>
                    </article>

                    <article class="benefit-card reveal">
                        <span class="benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 32 32">
                                <circle cx="11" cy="10" r="3.5"/>
                                <circle cx="22" cy="11.5" r="3"/>
                                <path d="M4.5 25v-2.2a6.5 6.5 0 0 1 13 0V25"/>
                                <path d="M18 18.5a5.5 5.5 0 0 1 8.5 4.6V25"/>
                            </svg>
                        </span>
                        <h3>Collaboration</h3>
                        <p>Kami tumbuh melalui kerja sama, komunikasi terbuka, dan saling mendukung untuk mencapai tujuan bersama.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="wellbeing-section" aria-labelledby="wellbeing-title">
            <div class="container wellbeing-layout">
                <div class="wellbeing-content reveal">
                    <h2 id="wellbeing-title">Kesejahteraan Karyawan</h2>
                    <p class="wellbeing-lead">Kesejahteraan Anda adalah prioritas kami. Manna Kampus menyediakan berbagai manfaat untuk mendukung kehidupan kerja yang seimbang dan produktif.</p>

                    <div class="wellbeing-benefits">
                        <div class="wellbeing-item">
                            <span class="wellbeing-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><path d="M5 9h18v14H5zM9 9V6.5A2.5 2.5 0 0 1 11.5 4h5A2.5 2.5 0 0 1 19 6.5V9M14 12v8M10 16h8"/></svg>
                            </span>
                            <p><strong>Health Insurance</strong><span>Perlindungan kesehatan lengkap.</span></p>
                        </div>
                        <div class="wellbeing-item">
                            <span class="wellbeing-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><path d="m3.5 10 10.5-6 10.5 6L14 16 3.5 10Z"/><path d="M7 12.2V18c3.7 3.1 10.3 3.1 14 0v-5.8M24.5 10v7"/></svg>
                            </span>
                            <p><strong>Training Program</strong><span>Sertifikasi dan pelatihan rutin.</span></p>
                        </div>
                        <div class="wellbeing-item">
                            <span class="wellbeing-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><path d="M5 9h18l-1 15H6L5 9Z"/><path d="M10 10V7a4 4 0 0 1 8 0v3"/></svg>
                            </span>
                            <p><strong>Employee Discounts</strong><span>Harga khusus belanja karyawan.</span></p>
                        </div>
                        <div class="wellbeing-item">
                            <span class="wellbeing-icon" aria-hidden="true">
                                <svg viewBox="0 0 28 28"><path d="M14 5v19M5 24h18M8 8h12M8 8l-5 8h10L8 8ZM20 8l-5 8h10l-5-8Z"/></svg>
                            </span>
                            <p><strong>Work-Life Balance</strong><span>Fleksibilitas dan cuti tahunan.</span></p>
                        </div>
                    </div>
                </div>

                <div class="wellbeing-photos reveal" aria-label="Suasana kerja kolaboratif di Manna Kampus">
                    <div class="wellbeing-photo wellbeing-photo-employee" role="img" aria-label="Karyawan berdiskusi dalam pertemuan"></div>
                    <div class="wellbeing-photo wellbeing-photo-office" role="img" aria-label="Tim berkolaborasi di ruang kerja modern"></div>
                </div>
            </div>
        </section>

        <section class="section join-section" id="join-us" aria-labelledby="join-title">
            <div class="container join-card reveal">
                <div class="join-pattern" aria-hidden="true"></div>
                <div class="join-copy">
                    <span class="eyebrow"><span></span> Join Our Team</span>
                    <h2 id="join-title">Siap menulis cerita<br>berikutnya bersama kami?</h2>
                    <p>Kami mencari orang-orang yang punya rasa ingin tahu, semangat bertumbuh, dan keberanian untuk membawa ide baru.</p>
                    <a class="button button-dark" href="<?= site_url('lowongan') ?>">Temukan Peranmu <span aria-hidden="true">→</span></a>
                </div>
                <div class="join-quote">
                    <span class="quote-mark" aria-hidden="true">“</span>
                    <blockquote>Di sini, setiap ide punya ruang untuk tumbuh dan setiap orang punya kesempatan untuk berdampak.</blockquote>
                    <div class="quote-author"><i aria-hidden="true">MK</i><span><strong>People Team</strong><small>Manna Kampus</small></span></div>
                </div>
            </div>
        </section>

        <section class="section faq-section" id="faq" aria-labelledby="faq-title">
            <div class="container faq-layout">
                <div class="faq-intro reveal">
                    <span class="eyebrow"><span></span> Frequently Asked</span>
                    <h2 id="faq-title">Masih ada yang ingin ditanyakan?</h2>
                    <p>Temukan jawaban untuk pertanyaan yang paling sering ditanyakan kandidat.</p>
                </div>
                <div class="faq-list reveal">
                    <details open>
                        <summary>Bagaimana cara melamar posisi di Manna Kampus?<span aria-hidden="true"></span></summary>
                        <p>Pilih posisi yang sesuai pada bagian Lowongan, lalu ikuti petunjuk lamaran yang tersedia. Pastikan seluruh berkas lamaran PDF sudah lengkap dan terbaru.</p>
                    </details>
                    <details>
                        <summary>Berapa lama proses rekrutmen berlangsung?<span aria-hidden="true"></span></summary>
                        <p>Durasi dapat berbeda untuk setiap posisi. Secara umum proses berlangsung sekitar dua hingga empat minggu sejak seleksi administrasi.</p>
                    </details>
                    <details>
                        <summary>Apakah fresh graduate boleh melamar?<span aria-hidden="true"></span></summary>
                        <p>Tentu. Perhatikan kualifikasi pada setiap posisi karena beberapa peran terbuka untuk fresh graduate maupun kandidat berpengalaman.</p>
                    </details>
                    <details>
                        <summary>Apakah tersedia sistem kerja hybrid atau remote?<span aria-hidden="true"></span></summary>
                        <p>Kebijakan tempat kerja mengikuti kebutuhan masing-masing posisi. Informasinya dicantumkan pada setiap kartu lowongan.</p>
                    </details>
                    <details>
                        <summary>Bagaimana saya mengetahui status lamaran?<span aria-hidden="true"></span></summary>
                        <p>Gunakan NIK dan nomor pengajuan pada halaman <a href="<?= site_url('lamaran/status') ?>">Cek Status Lamaran</a>. Tim rekrutmen juga akan menghubungi kandidat melalui email atau WhatsApp.</p>
                    </details>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-top">
            <a class="brand brand-light" href="#homepage">
                <img class="footer-logo" src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
            </a>
            <p>Ruang untuk belajar, bertumbuh, dan memberi dampak.</p>
            <a class="back-top" href="#homepage">Kembali ke atas ↑</a>
        </div>
        <div class="container footer-bottom">
            <span>© <?= date('Y') ?> Manna Kampus. All rights reserved.</span>
            <div><a href="<?= site_url('lowongan') ?>">Karier</a><a href="#faq">FAQ</a></div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/career.js') ?>?v=11" defer></script>
</body>
</html>
