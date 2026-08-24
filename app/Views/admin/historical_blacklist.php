<?php
$date = static fn (?string $value, string $format = 'd/m/Y H:i'): string => $value && strtotime($value) !== false ? date($format, strtotime($value)) : '-';
$statusLabels = ['active' => 'Aktif', 'permanent' => 'Permanen', 'expired' => 'Berakhir', 'revoked' => 'Dicabut'];
$historyLabels = ['blacklisted' => 'Ditambahkan', 'updated' => 'Diperbarui', 'revoked' => 'Dicabut', 'reactivated' => 'Diaktifkan kembali'];
$openModal = (string) old('form_origin');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#102a43">
    <title>Blacklist Historis | HRD Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>?v=11.26.25">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin-hrd.css') ?>?v=56">
</head>
<body class="admin-dashboard-page">
<div class="dashboard-shell">
    <?= view('admin/partials/sidebar', ['auth' => $auth, 'activeMenu' => 'historical-blacklist']) ?>
    <main class="admin-main">
        <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka navigasi"><span></span><span></span><span></span></button><div><span>Applicant Control</span><strong>Blacklist Historis</strong></div><a class="view-career-link" href="<?= site_url('adminhrdmannakampus/blacklist-pelamar') ?>">Blacklist pelamar</a></header>

        <div class="admin-content blacklist-content">
            <?php if ($success): ?><div class="admin-alert admin-alert-success dashboard-alert" data-swal-toast="success" role="status"><?= esc($success) ?></div><?php endif ?>
            <?php if ($error): ?><div class="admin-alert admin-alert-error dashboard-alert" data-swal-toast="error" role="alert"><?= esc($error) ?></div><?php endif ?>

            <section class="dashboard-welcome department-heading blacklist-heading historical-blacklist-heading">
                <div><span class="login-eyebrow">Pre-system Restriction</span><h1>Blacklist Historis</h1><p>Catat identitas yang telah diblacklist sebelum aplikasi ini digunakan. Sistem memeriksa NIK, email, dan telepon saat form lamaran dikirim.</p></div>
                <?php if ($canManage): ?><div class="historical-import-actions"><a class="new-user-jump historical-template-link" href="<?= site_url('adminhrdmannakampus/blacklist-historis/template') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 19h14"/></svg><span>Unduh Template</span></a><button class="new-user-jump historical-upload-button" type="button" data-admin-modal-open="historical-blacklist-import"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V5m0 0L8 9m4-4 4 4M5 15v4h14v-4"/></svg><span>Upload Excel</span></button><button class="new-user-jump historical-manual-button" type="button" data-admin-modal-open="historical-blacklist-create"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Tambah Manual</span></button></div><?php else: ?><span class="read-only-badge">Mode lihat saja</span><?php endif ?>
            </section>

            <section class="access-summary candidate-summary" aria-label="Ringkasan blacklist historis">
                <article><i class="summary-card-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></i><strong><?= (int) $summary['total'] ?></strong><span>Total tercatat</span></article>
                <article><i class="summary-card-icon icon-red" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></i><strong><?= (int) $summary['active'] ?></strong><span>Aktif sementara</span></article>
                <article><i class="summary-card-icon icon-orange" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 7h10v10H7z"/><path d="m7 7 10 10M17 7 7 17"/></svg></i><strong><?= (int) $summary['permanent'] ?></strong><span>Aktif permanen</span></article>
                <article><i class="summary-card-icon icon-green" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></i><strong><?= (int) $summary['ended'] ?></strong><span>Berakhir/dicabut</span></article>
            </section>

            <section class="settings-card department-toolbar-card">
                <form class="blacklist-filter-form" action="<?= site_url('adminhrdmannakampus/blacklist-historis') ?>" method="get">
                    <input type="search" name="keyword" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Cari nama, email, telepon, alasan, atau sumber">
                    <select name="status"><option value="">Semua status</option><?php foreach ($statusLabels as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $filters['status'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
                    <button type="submit">Terapkan</button><a href="<?= site_url('adminhrdmannakampus/blacklist-historis') ?>">Reset</a>
                </form>
            </section>

            <section class="settings-card blacklist-table-card">
                <div class="settings-card-heading settings-heading-action"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span><div><h2>Daftar identitas historis</h2><p>NIK tidak disimpan utuh dan hanya empat digit terakhir yang ditampilkan.</p></div><span class="device-count"><?= count($entries) ?></span></div>
                <div class="department-table-wrap"><table class="department-table blacklist-table"><thead><tr><th>No.</th><th>Identitas</th><th>Alasan &amp; sumber</th><th>Masa berlaku</th><th>Pencocokan</th><th>Dicatat oleh</th><th>Aksi</th></tr></thead><tbody>
                    <?php if ($entries === []): ?><tr><td colspan="7" class="department-empty">Data blacklist historis tidak ditemukan.</td></tr><?php endif ?>
                    <?php foreach ($entries as $index => $entry): $isActive = in_array($entry['computed_status'], ['active', 'permanent'], true); ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><div class="report-applicant"><strong><?= esc($entry['full_name']) ?></strong><?php if ($entry['nik_last_four']): ?><small>NIK •••• •••• •••• <?= esc($entry['nik_last_four']) ?></small><?php endif ?><?php if ($entry['email']): ?><a href="mailto:<?= esc($entry['email'], 'attr') ?>"><?= esc($entry['email']) ?></a><?php endif ?><small><?= esc($entry['phone'] ?: '-') ?></small></div></td>
                            <td class="blacklist-reason-cell"><strong><?= esc($entry['reason']) ?></strong><small><?= esc($entry['source'] ? 'Sumber: ' . $entry['source'] : 'Sumber tidak dicantumkan') ?></small></td>
                            <td><div class="blacklist-period"><span class="blacklist-status status-<?= esc($entry['computed_status'], 'attr') ?>"><i></i><?= esc($statusLabels[$entry['computed_status']] ?? '-') ?></span><strong><?= (int) $entry['is_permanent'] === 1 ? 'Permanen' : esc($date($entry['ends_at'], 'd M Y')) ?></strong><small>Mulai <?= esc($date($entry['starts_at'], 'd M Y')) ?></small></div></td>
                            <td><div class="blacklist-author"><strong><?= (int) $entry['match_count'] ?> kali cocok</strong><small>Terakhir <?= esc($date($entry['last_matched_at'])) ?></small></div></td>
                            <td><div class="blacklist-author"><strong><?= esc($entry['updated_by_name'] ?: $entry['created_by_name'] ?: 'Sistem') ?></strong><small><?= esc($date($entry['updated_at'])) ?></small></div></td>
                            <td><div class="candidate-table-actions blacklist-actions"><button class="blacklist-history-trigger" type="button" data-admin-modal-open="historical-history-<?= (int) $entry['id'] ?>">Riwayat</button><?php if ($canManage): ?><button class="candidate-process-link" type="button" data-admin-modal-open="historical-edit-<?= (int) $entry['id'] ?>"><?= $isActive ? 'Ubah' : 'Aktifkan kembali' ?></button><?php if ($isActive): ?><button class="candidate-cancel-assignment" type="button" data-admin-modal-open="historical-revoke-<?= (int) $entry['id'] ?>">Cabut</button><?php endif ?><?php endif ?></div></td>
                        </tr>
                    <?php endforeach ?>
                </tbody></table></div>
            </section>
        </div>
    </main>
</div>

<?php if ($canManage): ?>
<dialog class="admin-modal blacklist-form-modal" id="historical-blacklist-import" aria-labelledby="historical-import-title" <?= $openModal === 'import' ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><path d="M12 16V5m0 0L8 9m4-4 4 4M5 15v4h14v-4"/></svg></span><div><h2 id="historical-import-title">Upload blacklist dari Excel</h2><p>Gunakan template resmi agar susunan kolom dan format identitas tetap benar.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="blacklist-form historical-import-form" action="<?= site_url('adminhrdmannakampus/blacklist-historis/import') ?>" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="form_origin" value="import">
        <label>File Excel (.xlsx)<input type="file" name="import_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required><small>Maksimal 5 MB dan 1.000 baris data.</small></label>
        <div class="blacklist-form-warning historical-import-guide"><strong>Petunjuk pengisian</strong><span>Jangan mengubah nama atau urutan kolom. Isi data mulai baris 5. Minimal salah satu dari NIK, email, atau telepon harus diisi. Nilai masa berlaku: permanen, 1 bulan, 3 bulan, 6 bulan, 1 tahun, 2 tahun, atau tanggal khusus.</span></div>
        <div class="department-modal-actions"><a class="admin-modal-cancel historical-modal-template" href="<?= site_url('adminhrdmannakampus/blacklist-historis/template') ?>">Unduh template</a><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="Import blacklist historis?" data-confirm="Semua baris valid akan langsung digunakan untuk memeriksa pendaftaran baru." data-confirm-button="Ya, import" data-confirm-color="#dc2626">Upload dan import</button></div>
    </form>
</div></dialog>

<dialog class="admin-modal blacklist-form-modal" id="historical-blacklist-create" aria-labelledby="historical-create-title" <?= $openModal === 'create' ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></span><div><h2 id="historical-create-title">Tambah blacklist historis</h2><p>Isi minimal salah satu dari NIK, email, atau nomor telepon.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="blacklist-form historical-blacklist-form" action="<?= site_url('adminhrdmannakampus/blacklist-historis') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="form_origin" value="create">
        <label>Nama lengkap<input type="text" name="full_name" minlength="3" maxlength="150" value="<?= esc(old('full_name'), 'attr') ?>" required></label>
        <label>NIK<input type="text" name="nik" inputmode="numeric" minlength="16" maxlength="16" pattern="[0-9]{16}" value="<?= esc(old('nik'), 'attr') ?>" placeholder="16 angka"></label>
        <label>Email<input type="email" name="email" maxlength="190" value="<?= esc(old('email'), 'attr') ?>"></label>
        <label>Nomor telepon<input type="tel" name="phone" maxlength="20" value="<?= esc(old('phone'), 'attr') ?>" placeholder="081234567890"></label>
        <label>Alasan blacklist<textarea name="reason" rows="3" minlength="5" maxlength="1000" required><?= esc(old('reason')) ?></textarea></label>
        <label>Catatan internal<textarea name="internal_notes" rows="3" maxlength="5000"><?= esc(old('internal_notes')) ?></textarea></label>
        <label>Sumber data<input type="text" name="source" maxlength="150" value="<?= esc(old('source'), 'attr') ?>" placeholder="Contoh: Arsip HRD 2024"></label>
        <label>Masa berlaku<select name="duration" required data-blacklist-duration><option value="">Pilih masa berlaku</option><?php foreach ($durationLabels as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= old('duration') === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
        <label data-blacklist-custom-date hidden>Berakhir pada<input type="date" name="ends_on" value="<?= esc(old('ends_on'), 'attr') ?>" min="<?= date('Y-m-d') ?>"></label>
        <div class="blacklist-form-warning"><strong>Pemeriksaan otomatis</strong><span>Identitas yang cocok akan ditolak oleh backend sebelum profil dan lamaran baru dibuat.</span></div>
        <div class="department-modal-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="Tambahkan blacklist historis?" data-confirm="Identitas yang cocok tidak dapat mengirim lamaran." data-confirm-button="Ya, tambahkan" data-confirm-color="#dc2626">Simpan blacklist</button></div>
    </form>
</div></dialog>
<?php endif ?>

<?php foreach ($entries as $entry): $isActive = in_array($entry['computed_status'], ['active', 'permanent'], true); $histories = $historiesByEntry[(int) $entry['id']] ?? []; ?>
<dialog class="admin-modal blacklist-history-modal" id="historical-history-<?= (int) $entry['id'] ?>" aria-labelledby="historical-history-title-<?= (int) $entry['id'] ?>"><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span><div><h2 id="historical-history-title-<?= (int) $entry['id'] ?>">Riwayat <?= esc($entry['full_name']) ?></h2><p>Audit perubahan blacklist historis.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><div class="blacklist-history-list"><?php if ($histories === []): ?><p class="candidate-empty">Belum ada histori.</p><?php endif ?><?php foreach ($histories as $history): ?><article><i></i><div><strong><?= esc($historyLabels[$history['action']] ?? $history['action']) ?></strong><p><?= esc($history['action_notes'] ?: '-') ?></p><small><?= esc($date($history['created_at'])) ?> · <?= esc($history['changed_by_name'] ?: 'Sistem') ?></small></div></article><?php endforeach ?></div></div></dialog>

<?php if ($canManage): ?>
<dialog class="admin-modal blacklist-form-modal" id="historical-edit-<?= (int) $entry['id'] ?>" aria-labelledby="historical-edit-title-<?= (int) $entry['id'] ?>" <?= $openModal === 'edit-' . $entry['id'] ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg></span><div><h2 id="historical-edit-title-<?= (int) $entry['id'] ?>"><?= $isActive ? 'Ubah' : 'Aktifkan kembali' ?> blacklist</h2><p><?= esc($entry['full_name']) ?></p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div>
    <form class="blacklist-form historical-blacklist-form" action="<?= site_url('adminhrdmannakampus/blacklist-historis/' . $entry['id']) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="form_origin" value="edit-<?= (int) $entry['id'] ?>">
        <label>Nama lengkap<input type="text" name="full_name" minlength="3" maxlength="150" value="<?= esc($entry['full_name'], 'attr') ?>" required></label>
        <label>NIK baru<input type="text" name="nik" inputmode="numeric" minlength="16" maxlength="16" pattern="[0-9]{16}" placeholder="Kosongkan untuk mempertahankan NIK •••• <?= esc($entry['nik_last_four'] ?: '-', 'attr') ?>"><small>NIK lama tidak dapat ditampilkan kembali.</small></label>
        <label>Email<input type="email" name="email" maxlength="190" value="<?= esc($entry['email'], 'attr') ?>"></label>
        <label>Nomor telepon<input type="tel" name="phone" maxlength="20" value="<?= esc($entry['phone'], 'attr') ?>"></label>
        <label>Alasan blacklist<textarea name="reason" rows="3" minlength="5" maxlength="1000" required><?= esc($entry['reason']) ?></textarea></label>
        <label>Catatan internal<textarea name="internal_notes" rows="3" maxlength="5000"><?= esc($entry['internal_notes']) ?></textarea></label>
        <label>Sumber data<input type="text" name="source" maxlength="150" value="<?= esc($entry['source'], 'attr') ?>"></label>
        <label>Masa berlaku<select name="duration" required data-blacklist-duration><option value="">Pilih masa berlaku</option><?php foreach ($durationLabels as $code => $label): ?><option value="<?= esc($code, 'attr') ?>" <?= $code === ((int) $entry['is_permanent'] === 1 ? 'permanent' : 'custom') ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
        <label data-blacklist-custom-date <?= (int) $entry['is_permanent'] === 1 ? 'hidden' : '' ?>>Berakhir pada<input type="date" name="ends_on" value="<?= $entry['ends_at'] ? esc(date('Y-m-d', strtotime($entry['ends_at'])), 'attr') : '' ?>" min="<?= date('Y-m-d') ?>"></label>
        <div class="blacklist-form-warning"><strong>Konsekuensi</strong><span>Menyimpan data yang sudah berakhir atau dicabut akan mengaktifkan blacklist kembali.</span></div>
        <div class="department-modal-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="Simpan perubahan blacklist?" data-confirm="Pemeriksaan identitas akan langsung menggunakan data terbaru." data-confirm-button="Ya, simpan" data-confirm-color="#dc2626">Simpan perubahan</button></div>
    </form>
</div></dialog>
<?php if ($isActive): ?><dialog class="admin-modal blacklist-form-modal" id="historical-revoke-<?= (int) $entry['id'] ?>" aria-labelledby="historical-revoke-title-<?= (int) $entry['id'] ?>" <?= $openModal === 'revoke-' . $entry['id'] ? 'data-auto-open' : '' ?>><div class="admin-modal-panel"><div class="settings-card-heading admin-modal-heading"><span class="settings-icon settings-icon-green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><div><h2 id="historical-revoke-title-<?= (int) $entry['id'] ?>">Cabut blacklist <?= esc($entry['full_name']) ?></h2><p>Identitas ini tidak lagi diblokir oleh daftar historis.</p></div><button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal">&times;</button></div><form class="blacklist-form" action="<?= site_url('adminhrdmannakampus/blacklist-historis/' . $entry['id'] . '/cabut') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="form_origin" value="revoke-<?= (int) $entry['id'] ?>"><label>Alasan pencabutan<textarea name="revocation_reason" rows="4" minlength="5" maxlength="1000" required></textarea></label><div class="department-modal-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit" data-confirm-title="Cabut blacklist historis?" data-confirm="Identitas ini dapat mengirim lamaran jika tidak memiliki pembatasan lain." data-confirm-button="Ya, cabut">Cabut blacklist</button></div></form></div></dialog><?php endif ?>
<?php endif ?>
<?php endforeach ?>

<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>?v=11.26.25" defer></script>
<script src="<?= base_url('assets/js/admin-hrd.js') ?>?v=10" defer></script>
</body>
</html>
