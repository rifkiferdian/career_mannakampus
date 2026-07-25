<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Lamaran Terkirim | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/application.css') ?>?v=8">
</head>
<body class="application-page success-page">
    <main class="success-card">
        <span class="success-icon" aria-hidden="true"><?= $result['screening_status'] === 'passed' ? '✓' : 'i' ?></span>
        <p class="panel-eyebrow">Lamaran berhasil dikirim</p>
        <h1><?= $result['screening_status'] === 'passed' ? 'Selamat, Anda lolos screening awal.' : 'Terima kasih sudah melamar.' ?></h1>
        <p><?= esc($result['public_message']) ?></p>
        <div class="application-number"><span>Nomor Pengajuan</span><strong><?= esc($result['batch_number']) ?></strong></div>
        <div class="submitted-applications">
            <?php foreach ($result['applications'] as $application): ?>
                <div>
                    <span>
                        <strong>Prioritas <?= (int) $application['preference_order'] ?> · <?= esc($application['title']) ?></strong>
                        <small><?= esc($application['application_number']) ?></small>
                    </span>
                    <b class="<?= $application['screening_status'] === 'passed' ? 'passed' : 'failed' ?>">
                        <?= $application['screening_status'] === 'passed' ? 'Lolos awal' : 'Belum lolos' ?>
                    </b>
                </div>
            <?php endforeach ?>
        </div>
        <p class="success-note">Simpan nomor pengajuan dan nomor setiap lamaran sebagai referensi proses rekrutmen Anda.</p>
        <a class="button-primary success-link" href="<?= site_url('lowongan') ?>">Kembali ke Lowongan</a>
    </main>
</body>
</html>
