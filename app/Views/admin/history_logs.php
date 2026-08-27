<?php
$sourceStyles = [
    'security' => ['label' => 'Keamanan akun', 'class' => 'security'],
    'application' => ['label' => 'Status lamaran', 'class' => 'application'],
    'assignment' => ['label' => 'Assignment HRD', 'class' => 'assignment'],
    'blacklist' => ['label' => 'Blacklist', 'class' => 'blacklist'],
    'schedule' => ['label' => 'Jadwal seleksi', 'class' => 'schedule'],
    'talent_pool' => ['label' => 'Talent Pool', 'class' => 'talent'],
];
$actionLabels = [
    'login' => 'Login', 'logout' => 'Logout', 'profile_updated' => 'Profil diperbarui',
    'password_changed' => 'Password diubah', 'sessions_revoked' => 'Semua sesi dicabut',
    'application' => 'Tahap lamaran berubah', 'correction' => 'Perubahan tahap diurungkan',
    'assigned' => 'Ditugaskan', 'unassigned' => 'Assignment dibatalkan',
    'blacklisted' => 'Ditambahkan ke blacklist', 'updated' => 'Data diperbarui',
    'revoked' => 'Dicabut', 'reactivated' => 'Diaktifkan kembali',
    'created' => 'Jadwal dibuat', 'rescheduled' => 'Jadwal diubah',
    'confirmed' => 'Jadwal dikonfirmasi', 'reschedule_requested' => 'Meminta jadwal ulang',
    'present' => 'Hadir', 'absent' => 'Tidak hadir', 'cancelled' => 'Jadwal dibatalkan',
    'saved' => 'Disimpan ke Talent Pool', 'status_changed' => 'Status Talent Pool berubah',
    'invited_to_vacancy' => 'Dipanggil ke lowongan',
];
$filterQuery = array_filter([
    'keyword' => $filters['keyword'], 'source' => $filters['source'],
    'user_id' => $filters['user_id'] ?: '', 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to'],
], static fn ($value): bool => $value !== '');
$formatTime = static fn (string $value): string => date('d M Y, H:i', strtotime($value));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#102a43">
    <title>History Log | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=67">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'history-logs']) ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button>
            <div><span>System Audit</span><strong>History Log</strong></div>
            <a class="view-career-link" href="<?= site_url('adminhrdmannakampus/akses') ?>">User &amp; Akses</a>
        </header>

        <div class="admin-content history-log-content">
            <section class="dashboard-welcome history-log-heading" aria-labelledby="history-log-title">
                <div><span class="login-eyebrow">Khusus Super Admin</span><h1 id="history-log-title">Riwayat aktivitas sistem</h1><p>Pantau perubahan status lamaran, assignment HRD, blacklist, jadwal, Talent Pool, dan keamanan akun.</p></div>
                <span class="history-log-total"><strong><?= number_format((int) $pagination['total'], 0, ',', '.') ?></strong> aktivitas ditemukan</span>
            </section>

            <section class="settings-card history-log-filter-card" aria-labelledby="history-filter-title">
                <div class="settings-card-heading">
                    <span class="settings-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg></span>
                    <div><h2 id="history-filter-title">Filter log</h2><p>Persempit aktivitas berdasarkan kategori, pelaku, atau rentang waktu.</p></div>
                </div>
                <form class="history-log-filter" action="<?= site_url('adminhrdmannakampus/history-log') ?>" method="get">
                    <label><span>Pencarian</span><input type="search" name="keyword" value="<?= esc($filters['keyword'], 'attr') ?>" maxlength="100" placeholder="Nama, lamaran, lowongan, catatan..."></label>
                    <label><span>Kategori</span><select name="source"><option value="">Semua kategori</option><?php foreach ($sources as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['source'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                    <label><span>Pelaku</span><select name="user_id"><option value="">Semua user</option><?php foreach ($users as $user): ?><option value="<?= (int) $user['id'] ?>" <?= (int) $filters['user_id'] === (int) $user['id'] ? 'selected' : '' ?>><?= esc($user['full_name']) ?> — <?= esc($user['email']) ?></option><?php endforeach ?></select></label>
                    <label><span>Dari tanggal</span><input type="date" name="date_from" value="<?= esc($filters['date_from'], 'attr') ?>"></label>
                    <label><span>Sampai tanggal</span><input type="date" name="date_to" value="<?= esc($filters['date_to'], 'attr') ?>"></label>
                    <div class="history-log-filter-actions"><button type="submit">Terapkan</button><a href="<?= site_url('adminhrdmannakampus/history-log') ?>">Reset</a></div>
                </form>
            </section>

            <section class="settings-card history-log-card" aria-labelledby="history-list-title">
                <div class="settings-card-heading">
                    <span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <div><h2 id="history-list-title">Timeline aktivitas</h2><p>Log terbaru ditampilkan paling atas.</p></div>
                </div>
                <?php if ($logs === []): ?>
                    <div class="history-log-empty"><strong>Aktivitas tidak ditemukan</strong><p>Coba ubah atau reset filter pencarian.</p></div>
                <?php else: ?>
                    <div class="history-log-table-wrap">
                        <table class="history-log-table">
                            <thead><tr><th>Waktu</th><th>Kategori &amp; aksi</th><th>Objek</th><th>Detail perubahan</th><th>Pelaku</th></tr></thead>
                            <tbody>
                            <?php foreach ($logs as $log): ?>
                                <?php $source = $sourceStyles[$log['source']] ?? ['label' => $log['source'], 'class' => 'default']; ?>
                                <tr>
                                    <td><time datetime="<?= esc($log['occurred_at'], 'attr') ?>"><?= esc($formatTime($log['occurred_at'])) ?></time><small>WIB</small></td>
                                    <td><span class="history-source history-source-<?= esc($source['class'], 'attr') ?>"><?= esc($source['label']) ?></span><strong class="history-action"><?= esc($actionLabels[$log['action']] ?? ucwords(str_replace('_', ' ', $log['action']))) ?></strong></td>
                                    <td><strong><?= esc($log['subject'] ?: '-') ?></strong><small><?= esc($log['reference_text'] ?: '-') ?></small></td>
                                    <td><p><?= esc($log['description'] ?: '-') ?></p></td>
                                    <td><span class="history-actor"><i><?= esc(mb_strtoupper(mb_substr((string) ($log['actor_name'] ?: 'S'), 0, 1))) ?></i><strong><?= esc($log['actor_name'] ?: 'Sistem') ?></strong></span></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
                <?= view('admin/partials/pagination', ['pagination' => $pagination, 'baseUrl' => site_url('adminhrdmannakampus/history-log'), 'query' => $filterQuery, 'unit' => 'aktivitas']) ?>
            </section>
        </div>
        <?= view('admin/partials/footer') ?>
    </main>
</div>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=7" defer></script>
</body>
</html>
