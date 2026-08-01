<?php
$formatJakarta = static function (string $date, string $format): string {
    return (new DateTimeImmutable($date, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Asia/Jakarta'))
        ->format($format);
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Profil &amp; Keamanan | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=3">
</head>
<body class="admin-dashboard-page">
    <div class="dashboard-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <a href="<?= site_url('adminhrdmannakampus/dashboard') ?>" class="admin-brand sidebar-brand">
                <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
            </a>
            <span class="sidebar-caption">HRD Administration</span>

            <nav class="admin-nav" aria-label="Navigasi dashboard HRD">
                <a href="<?= site_url('adminhrdmannakampus/dashboard') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/></svg>
                    Dashboard
                </a>
                <a class="active" href="<?= site_url('adminhrdmannakampus/profil') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                    Profil &amp; Keamanan
                </a>
                <span class="admin-nav-disabled" title="Segera tersedia">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>
                    Kandidat <small>Segera</small>
                </span>
                <span class="admin-nav-disabled" title="Segera tersedia">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg>
                    Lowongan <small>Segera</small>
                </span>
            </nav>

            <div class="sidebar-user">
                <span class="user-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($auth['name'] ?? 'H'), 0, 1))) ?></span>
                <span><strong><?= esc($auth['name'] ?? 'Admin HRD') ?></strong><small><?= esc($auth['email'] ?? '') ?></small></span>
            </div>
            <form action="<?= site_url('adminhrdmannakampus/logout') ?>" method="post">
                <?= csrf_field() ?>
                <button class="logout-button" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>
                    Keluar
                </button>
            </form>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi">
                    <span></span><span></span><span></span>
                </button>
                <div><span>Pengaturan Akun</span><strong>Profil &amp; Keamanan</strong></div>
                <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/dashboard') ?>">Kembali ke dashboard</a>
            </header>

            <div class="admin-content profile-content">
                <?php if (! empty($success)): ?>
                    <div class="admin-alert admin-alert-success dashboard-alert" role="status"><?= esc($success) ?></div>
                <?php endif ?>
                <?php if (! empty($error)): ?>
                    <div class="admin-alert admin-alert-error dashboard-alert" role="alert"><?= esc($error) ?></div>
                <?php endif ?>

                <section class="dashboard-welcome profile-heading" aria-labelledby="profile-title">
                    <div>
                        <span class="login-eyebrow">Pengaturan Akun</span>
                        <h1 id="profile-title">Profil &amp; Keamanan</h1>
                        <p>Kelola informasi pribadi, password, dan perangkat yang memiliki akses ke akun Anda.</p>
                    </div>
                </section>

                <div class="profile-grid">
                    <div class="profile-column">
                        <section class="settings-card" aria-labelledby="personal-title">
                            <div class="settings-card-heading">
                                <span class="settings-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg></span>
                                <div><h2 id="personal-title">Informasi profil</h2><p>Informasi ini digunakan pada portal internal HRD.</p></div>
                            </div>
                            <form class="settings-form" action="<?= site_url('adminhrdmannakampus/profil') ?>" method="post" novalidate>
                                <?= csrf_field() ?>
                                <label for="full_name">Nama lengkap</label>
                                <div class="admin-input <?= isset($profileErrors['full_name']) ? 'has-error' : '' ?>">
                                    <input id="full_name" name="full_name" type="text" value="<?= esc(old('full_name') ?: ($user['name'] ?? ''), 'attr') ?>" maxlength="150" autocomplete="name" required>
                                </div>
                                <?php if (isset($profileErrors['full_name'])): ?><p class="field-error"><?= esc($profileErrors['full_name']) ?></p><?php endif ?>

                                <label for="profile_email">Email</label>
                                <div class="admin-input <?= isset($profileErrors['email']) ? 'has-error' : '' ?>">
                                    <input id="profile_email" name="email" type="email" value="<?= esc(old('email') ?: ($user['email'] ?? ''), 'attr') ?>" maxlength="150" autocomplete="email" required>
                                </div>
                                <?php if (isset($profileErrors['email'])): ?><p class="field-error"><?= esc($profileErrors['email']) ?></p><?php endif ?>

                                <label for="phone">Nomor WhatsApp</label>
                                <div class="admin-input <?= isset($profileErrors['phone']) ? 'has-error' : '' ?>">
                                    <input id="phone" name="phone" type="tel" value="<?= esc(old('phone') ?: ($user['phone'] ?? ''), 'attr') ?>" placeholder="Contoh: +62 812 3456 7890" maxlength="30" autocomplete="tel">
                                </div>
                                <?php if (isset($profileErrors['phone'])): ?><p class="field-error"><?= esc($profileErrors['phone']) ?></p><?php endif ?>

                                <button class="settings-submit" type="submit">Simpan perubahan</button>
                            </form>
                        </section>

                        <section class="settings-card" id="password" aria-labelledby="password-title">
                            <div class="settings-card-heading">
                                <span class="settings-icon settings-icon-orange"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                                <div><h2 id="password-title">Ubah password</h2><p>Mengubah password akan mengeluarkan seluruh perangkat lain.</p></div>
                            </div>
                            <form class="settings-form" action="<?= site_url('adminhrdmannakampus/profil/password') ?>" method="post" novalidate>
                                <?= csrf_field() ?>
                                <?php foreach ([
                                    ['current_password', 'Password saat ini', 'current-password'],
                                    ['new_password', 'Password baru', 'new-password'],
                                    ['password_confirm', 'Konfirmasi password baru', 'new-password'],
                                ] as [$field, $label, $autocomplete]): ?>
                                    <label for="<?= esc($field, 'attr') ?>"><?= esc($label) ?></label>
                                    <div class="admin-input <?= isset($passwordErrors[$field]) ? 'has-error' : '' ?>">
                                        <input id="<?= esc($field, 'attr') ?>" name="<?= esc($field, 'attr') ?>" type="password" maxlength="255" autocomplete="<?= esc($autocomplete, 'attr') ?>" required>
                                        <button class="password-toggle" type="button" data-password-toggle="<?= esc($field, 'attr') ?>" aria-label="Tampilkan password" aria-pressed="false">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        </button>
                                    </div>
                                    <?php if (isset($passwordErrors[$field])): ?><p class="field-error"><?= esc($passwordErrors[$field]) ?></p><?php endif ?>
                                <?php endforeach ?>
                                <p class="password-hint">Minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol.</p>
                                <button class="settings-submit" type="submit">Perbarui password</button>
                            </form>
                        </section>
                    </div>

                    <div class="profile-column">
                        <section class="settings-card" id="devices" aria-labelledby="devices-title">
                            <div class="settings-card-heading settings-heading-action">
                                <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span>
                                <div><h2 id="devices-title">Perangkat aktif</h2><p>Sesi yang saat ini memiliki akses ke akun Anda.</p></div>
                                <span class="device-count"><?= count($activeSessions) ?></span>
                            </div>
                            <div class="device-list">
                                <?php foreach ($activeSessions as $device): ?>
                                    <article class="device-item">
                                        <span class="device-symbol"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span>
                                        <div>
                                            <strong><?= esc($device['device_label']) ?><?php if ($device['is_current']): ?> <em>Perangkat ini</em><?php endif ?></strong>
                                            <span>IP <?= esc($device['ip_address']) ?> · Aktif <?= esc($formatJakarta((string) $device['last_activity_at'], 'd M Y, H:i')) ?> WIB</span>
                                        </div>
                                        <form action="<?= site_url('adminhrdmannakampus/profil/perangkat/' . $device['id'] . '/revoke') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button class="revoke-device" type="submit" data-confirm="Keluarkan perangkat ini dari akun?">Keluarkan</button>
                                        </form>
                                    </article>
                                <?php endforeach ?>
                            </div>
                            <form class="revoke-all-form" action="<?= site_url('adminhrdmannakampus/profil/perangkat/revoke-all') ?>" method="post">
                                <?= csrf_field() ?>
                                <div><strong>Keluar dari semua perangkat</strong><span>Seluruh sesi termasuk perangkat ini akan dihentikan.</span></div>
                                <button type="submit" data-confirm="Keluar dari semua perangkat? Anda harus login kembali.">Keluar dari semua</button>
                            </form>
                        </section>

                        <section class="settings-card" aria-labelledby="history-title">
                            <div class="settings-card-heading">
                                <span class="settings-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg></span>
                                <div><h2 id="history-title">Riwayat keamanan</h2><p>Aktivitas terbaru pada akun Anda.</p></div>
                            </div>
                            <?php
                                $eventLabels = [
                                    'login'             => 'Login akun',
                                    'logout'            => 'Logout akun',
                                    'profile_updated'   => 'Profil diperbarui',
                                    'password_changed'  => 'Password diubah',
                                    'sessions_revoked'  => 'Semua perangkat dikeluarkan',
                                ];
                            ?>
                            <div class="security-history">
                                <?php foreach ($loginHistory as $history): ?>
                                    <article class="history-item">
                                        <span class="history-status <?= $history['was_successful'] ? 'success' : 'failed' ?>"></span>
                                        <div><strong><?= esc($eventLabels[$history['event_type']] ?? 'Aktivitas akun') ?></strong><span><?= esc($history['device_label']) ?> · IP <?= esc($history['ip_address']) ?></span></div>
                                        <time datetime="<?= esc($history['occurred_at'], 'attr') ?>"><?= esc($formatJakarta((string) $history['occurred_at'], 'd M, H:i')) ?> WIB</time>
                                    </article>
                                <?php endforeach ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=2" defer></script>
</body>
</html>
