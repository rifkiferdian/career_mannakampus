<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Lamaran Terkirim | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/application.css') ?>?v=12">
</head>
<body class="application-page success-page">
    <main class="success-card">
        <span class="success-icon" aria-hidden="true">&#10003;</span>
        <p class="panel-eyebrow">Pengajuan diterima</p>
        <h1>Lamaran berhasil dikirim</h1>
        <p>Terima kasih. Lamaran Anda telah kami terima dan akan diproses oleh tim rekrutmen.</p>
        <div class="application-number"><span>Nomor Pengajuan</span><strong><?= esc($result['batch_number']) ?></strong></div>
        <div class="submitted-applications">
            <?php foreach ($result['applications'] as $application): ?>
                <div>
                    <span>
                        <strong>Prioritas <?= (int) $application['preference_order'] ?> · <?= esc($application['title']) ?></strong>
                        <small>Nomor Lamaran: <?= esc($application['application_number']) ?></small>
                    </span>
                </div>
            <?php endforeach ?>
        </div>
        <p class="success-note"><strong>Simpan nomor pengajuan dan nomor setiap lamaran.</strong> Nomor tersebut diperlukan untuk mengecek status dan mengikuti proses rekrutmen selanjutnya.</p>
        <a class="button-primary success-link" href="<?= site_url('lamaran/bukti/' . $result['receipt_token']) ?>">Download Bukti Lamaran PDF</a>
        <a class="button-secondary success-link" href="<?= site_url('lamaran/status') ?>">Cek Status Lamaran</a>
        <a class="button-secondary success-link" href="<?= site_url('lowongan') ?>">Kembali ke Lowongan</a>
    </main>
</body>
</html>
