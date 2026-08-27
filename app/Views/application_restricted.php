<?php
$isCooldown = ($restriction['type'] ?? '') === 'cooldown';
$stageName = trim((string) ($restriction['stage_name'] ?? 'tahap seleksi'));
$matchedIdentifier = trim((string) ($restriction['matched_identifier'] ?? 'identitas')) ?: 'identitas';
$identifierHint = trim((string) ($restriction['identifier_hint'] ?? ''));
$restrictionReference = trim((string) ($restriction['reference'] ?? '-')) ?: '-';
$expiryLabel = trim((string) ($restriction['expiry_label'] ?? '-')) ?: '-';
$restrictionSource = ($restriction['source'] ?? '') === 'historical'
    ? 'blacklist historis yang dikelola HRD'
    : 'blacklist pelamar aktif';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Lamaran Belum Dapat Diproses | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/application.css') ?>?v=18">
</head>
<body class="application-page restricted-page">
    <main class="restricted-card <?= $isCooldown ? 'restricted-card-cooldown' : 'restricted-card-access' ?>">
        <div class="restricted-status-icon" aria-hidden="true">
            <?php if ($isCooldown): ?>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/></svg>
            <?php else: ?>
                <svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.7 2.7 8.2 7 10 4.3-1.8 7-5.3 7-10V6l-7-3Z"/><path d="m9 9 6 6m0-6-6 6"/></svg>
            <?php endif ?>
        </div>

        <span class="restricted-eyebrow"><?= $isCooldown ? 'Masa tunggu rekrutmen' : 'Blacklist aktif' ?></span>
        <h1><?= $isCooldown ? 'Anda belum dapat melamar kembali' : 'Identitas Anda masuk blacklist' ?></h1>

        <?php if ($isCooldown): ?>
            <p class="restricted-lead">Riwayat seleksi menunjukkan Anda belum lolos pada tahap <strong><?= esc($stageName) ?></strong>. Sesuai ketentuan, pendaftaran baru dapat dilakukan setelah masa tunggu tiga bulan selesai.</p>

            <section class="restricted-date" aria-label="Tanggal dapat melamar kembali">
                <span>Dapat melamar kembali mulai</span>
                <strong><?= esc((string) $restriction['available_date_label']) ?></strong>
                <small>Penolakan tercatat pada <?= esc((string) $restriction['rejected_date_label']) ?></small>
            </section>

            <div class="restricted-information">
                <article><span>1</span><div><strong>Data tetap aman</strong><p>Profil dan riwayat lamaran Anda tetap tersimpan di sistem.</p></div></article>
                <article><span>2</span><div><strong>Tunggu selama 3 bulan</strong><p>Masa tunggu dihitung dari tanggal keputusan tidak lolos.</p></div></article>
                <article><span>3</span><div><strong>Coba kembali</strong><p>Setelah tanggal di atas, Anda dapat melamar lowongan yang tersedia.</p></div></article>
            </div>

            <p class="restricted-note">Masa tunggu ini bukan blacklist dan akan berakhir otomatis tanpa perlu menghubungi tim rekrutmen.</p>
        <?php else: ?>
            <p class="restricted-lead"><strong>Anda belum dapat melamar karena identitas Anda sedang masuk blacklist.</strong> <?= esc(ucfirst($matchedIdentifier)) ?> yang dimasukkan cocok dengan <?= esc($restrictionSource) ?>, sehingga lamaran tidak disimpan.</p>

            <section class="restricted-match-details" aria-label="Informasi pembatasan pendaftaran">
                <article><span>Data yang cocok</span><strong><?= esc(ucfirst($matchedIdentifier)) ?><?= $identifierHint !== '' ? ' · ' . esc($identifierHint) : '' ?></strong></article>
                <article><span>Masa berlaku</span><strong><?= esc($expiryLabel) ?></strong></article>
                <article><span>Kode referensi</span><strong><?= esc($restrictionReference) ?></strong></article>
            </section>

            <p class="restricted-privacy-note">Alasan dan catatan internal tidak ditampilkan untuk melindungi kerahasiaan proses rekrutmen.</p>
            <section class="restricted-contact">
                <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Z"/><path d="m4 7 8 6 8-6"/></svg></span>
                <div><strong>Merasa data ini tidak sesuai?</strong><p>Hubungi tim rekrutmen melalui kanal resmi Manna Kampus dan sertakan kode referensi <b><?= esc($restrictionReference) ?></b> agar pemeriksaan dapat dilakukan lebih cepat.</p></div>
            </section>
        <?php endif ?>

        <a class="button-primary restricted-action" href="<?= site_url('lowongan') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 7-5 5 5 5"/></svg>
            Kembali ke Lowongan
        </a>
    </main>
</body>
</html>
