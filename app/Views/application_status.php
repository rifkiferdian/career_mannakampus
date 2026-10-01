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
    <link rel="stylesheet" href="<?= base_url('assets/css/application-status.css') ?>?v=12">
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
                    <?php if (! empty($statusMessage)): ?><div class="status-alert" role="alert"><?= esc($statusMessage) ?></div><?php endif ?>
                    <?php if (! empty($statusSuccess)): ?><div class="status-alert status-alert-success" role="status"><?= esc($statusSuccess) ?></div><?php endif ?>

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
                        <div class="status-modal-title">
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2Z"/><path d="m9 10 2 2 4-4"/></svg>
                            </span>
                            <div><strong>Hasil pengecekan</strong><small>Informasi terbaru proses rekrutmen Anda</small></div>
                        </div>
                        <button class="status-result-close" type="button" data-status-result-close aria-label="Tutup hasil pengecekan">&times;</button>
                    </div>
                    <section class="status-result-section">
                        <div class="container status-result">
                    <div class="status-result-heading">
                        <span class="status-result-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m6.5 12 3.5 3.5L18 7.5"/><circle cx="12" cy="12" r="9"/></svg>
                        </span>
                        <div>
                            <span class="status-result-kicker">Status berhasil ditemukan</span>
                            <h2 id="result-title">Halo, <?= esc($result['applicant_name']) ?></h2>
                            <p>Kami menemukan <?= (int) $result['position_count'] ?> posisi dari <?= (int) $result['batch_count'] ?> pengajuan. Berikut perkembangan terbarunya.</p>
                        </div>
                        <span class="status-found-badge"><i></i> Data terverifikasi</span>
                    </div>

                    <dl class="status-summary">
                        <div>
                            <dt>Pengajuan terbaru</dt>
                            <dd><?= esc($result['batch_number']) ?></dd>
                        </div>
                        <div>
                            <dt>Terakhir melamar</dt>
                            <dd><?= esc($result['submitted_at']) ?></dd>
                        </div>
                        <div>
                            <dt>Total posisi</dt>
                            <dd><?= (int) $result['position_count'] ?> posisi</dd>
                        </div>
                        <?php if ($result['applicant_email'] !== ''): ?>
                            <div>
                                <dt>Email terdaftar</dt>
                                <dd><?= esc($result['applicant_email']) ?></dd>
                            </div>
                        <?php endif ?>
                        <?php if ($result['applicant_phone'] !== ''): ?>
                            <div>
                                <dt>WhatsApp terdaftar</dt>
                                <dd><?= esc($result['applicant_phone']) ?></dd>
                            </div>
                        <?php endif ?>
                    </dl>

                    <div class="status-position-heading">
                        <div><span>Riwayat proses</span><h3>Status setiap posisi</h3></div>
                        <span><?= (int) $result['position_count'] ?> posisi</span>
                    </div>

                    <div class="status-applications">
                        <?php foreach ($result['applications'] as $application): ?>
                            <article class="status-application-card">
                                <header class="status-application-header">
                                    <div class="status-application-title">
                                        <div class="status-application-eyebrow">
                                            <span class="status-priority-label">Pilihan <?= (int) $application['preference_order'] ?></span>
                                            <?php if ($application['department_name'] !== ''): ?><span class="status-department"><?= esc($application['department_name']) ?></span><?php endif ?>
                                        </div>
                                        <h3><?= esc($application['vacancy_title']) ?></h3>
                                    </div>
                                    <span class="status-badge status-badge-<?= esc($application['status_tone'], 'attr') ?>">
                                        <i></i><?= esc($application['status_label']) ?>
                                    </span>
                                </header>
                                <div class="status-application-reference">
                                    <span><small>Nomor lamaran</small><strong><?= esc($application['application_number']) ?></strong></span>
                                    <span><small>Terakhir diperbarui</small><strong><?= esc($application['updated_at']) ?></strong></span>
                                </div>
                                <div class="status-application-main">
                                    <div class="status-description">
                                        <span aria-hidden="true">i</span>
                                        <p><?= esc($application['status_description']) ?></p>
                                    </div>
                                    <?php if ($application['public_message'] !== ''): ?>
                                        <div class="status-public-message"><strong>Informasi dari HRD</strong><span><?= esc($application['public_message']) ?></span></div>
                                    <?php endif ?>
                                    <?php if (is_array($application['schedule'] ?? null)): $schedule = $application['schedule']; $canRespond = $schedule['status'] === 'scheduled' && strtotime($schedule['confirmation_deadline_raw']) >= time(); ?>
                                        <section class="public-schedule-card">
                                            <div class="public-schedule-heading"><span>Jadwal seleksi</span><strong><?= esc($schedule['stage_name']) ?></strong></div>
                                            <dl><div><dt>Pelaksanaan</dt><dd><?= esc($schedule['scheduled_at']) ?> WIB</dd></div><div><dt>Lokasi / meeting</dt><dd><?php if (preg_match('#^https?://#i', $schedule['venue']) === 1): ?><a href="<?= esc($schedule['venue'], 'attr') ?>" target="_blank" rel="noopener noreferrer">Buka link meeting</a><?php else: ?><?= esc($schedule['venue']) ?><?php endif ?></dd></div><div><dt>PIC</dt><dd><?= esc($schedule['pic_name']) ?></dd></div><div><dt>Batas konfirmasi</dt><dd><?= esc($schedule['confirmation_deadline']) ?> WIB</dd></div></dl>
                                            <?php if ($schedule['instructions'] !== ''): ?><p><strong>Instruksi</strong><?= nl2br(esc($schedule['instructions'])) ?></p><?php endif ?>
                                            <?php $publicScheduleLabels = ['scheduled' => 'Menunggu konfirmasi', 'confirmed' => 'Anda sudah mengonfirmasi hadir', 'reschedule_requested' => 'Permintaan jadwal ulang sedang ditinjau']; ?>
                                            <span class="public-schedule-status status-<?= esc($schedule['status'], 'attr') ?>"><?= esc($publicScheduleLabels[$schedule['status']] ?? $schedule['status']) ?></span>
                                            <?php if ($canRespond): ?><div class="public-schedule-actions"><form action="<?= site_url('lamaran/status/jadwal/' . $schedule['id']) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="response" value="confirmed"><button type="submit">Saya bersedia hadir</button></form><form class="public-reschedule-form" action="<?= site_url('lamaran/status/jadwal/' . $schedule['id']) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="response" value="reschedule_requested"><label>Alasan meminta jadwal ulang<textarea name="candidate_note" minlength="5" maxlength="2000" rows="2" required></textarea></label><button type="submit">Ajukan jadwal ulang</button></form></div><?php elseif ($schedule['status'] === 'scheduled'): ?><small class="public-schedule-expired">Batas konfirmasi sudah berakhir. Silakan hubungi tim HRD.</small><?php endif ?>
                                        </section>
                                    <?php endif ?>
                                </div>
                            </article>
                        <?php endforeach ?>
                    </div>

                    <div class="status-result-note">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="m4 7 8 6 8-6"/></svg></span>
                        <div><strong>Tetap pantau informasi dari kami</strong><p>Perkembangan berikutnya akan disampaikan melalui email atau WhatsApp yang Anda cantumkan saat melamar.</p></div>
                    </div>
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
            <span>&copy; <?= date('Y') ?> Created by Manna Kampus Software Engineering Division
-- Rifki Ahmad P</span>
            <div>
                <a href="<?= site_url('lowongan') ?>">Karier</a>
                <a href="<?= site_url('lamaran/status') ?>">Cek Status</a>
                <a href="<?= base_url() ?>#faq">FAQ</a>
            </div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/career.js') ?>?v=11" defer></script>
    <script src="<?= base_url('assets/js/application-status.js') ?>?v=4" defer></script>
</body>
</html>
