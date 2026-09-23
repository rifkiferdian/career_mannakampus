<?php
$base = site_url('adminhrdmannakampus/agenda/' . $agenda['id']);
$acceptsParticipants = $agenda['status'] === 'scheduled';
$hasStarted = $agenda['starts_at'] <= date('Y-m-d H:i:s');
$selected = old('application_ids', [], false);
$selected = is_array($selected) ? array_map('intval', $selected) : [];
?>
<section class="dashboard-welcome agenda-heading"><div><span class="login-eyebrow"><?= esc($agenda['name']) ?></span><h1>Tambah Peserta</h1><p><?= esc($agenda['stage_name']) ?> · <?= esc(date('d/m/Y H:i', strtotime($agenda['starts_at']))) ?> WIB</p></div><a class="agenda-button agenda-button-secondary" href="<?= $base ?>">Kembali ke Agenda</a></section>
<?php if (! $acceptsParticipants): ?>
<div class="agenda-note">Peserta hanya dapat ditambahkan ke agenda berstatus Terjadwal.</div>
<?php else: ?>
<section class="settings-card agenda-card">
    <form method="get" action="<?= $base ?>/peserta" class="agenda-filters">
        <label>Cari pelamar<input type="search" name="keyword" maxlength="100" value="<?= esc($keyword, 'attr') ?>" placeholder="Nama atau nomor lamaran"></label>
        <label>Posisi<select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $vacancyId === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>
        <button class="agenda-button" type="submit">Cari</button>
    </form>
    <p>Menampilkan pelamar pada tahap <?= esc($agenda['stage_name']) ?> yang belum terdaftar di agenda ini dan sesuai akses tim Anda.</p>
</section>
<section class="settings-card agenda-card">
    <div class="agenda-note">Semua peserta memakai jam mulai agenda. Jadwal aktif pada tahap yang sama akan dipindahkan dan meminta konfirmasi ulang. Jadwal pada agenda lain harus diselesaikan atau dibatalkan dahulu.</div>
    <form method="post" action="<?= $base ?>/peserta" data-agenda-participants>
        <?= csrf_field() ?>
        <div class="agenda-selection-bar"><label class="agenda-checkbox"><input type="checkbox" data-agenda-select-all> Pilih semua pada halaman ini</label><span data-agenda-selection-count aria-live="polite">0 peserta dipilih</span></div>
        <div class="department-table-wrap"><table class="department-table agenda-table agenda-table--candidates"><thead><tr><th>Pilih</th><th>Pelamar</th><th>Nomor lamaran</th><th>Posisi</th></tr></thead><tbody>
        <?php if ($rows === []): ?><tr><td colspan="4" class="department-empty">Tidak ada pelamar yang sesuai. Pastikan tahap seleksi pelamar sudah sesuai dengan agenda.</td></tr><?php endif ?>
        <?php foreach ($rows as $row): ?><tr><td><input type="checkbox" name="application_ids[]" value="<?= (int) $row['id'] ?>" aria-label="Pilih <?= esc($row['full_name'], 'attr') ?> untuk <?= esc($row['vacancy_title'], 'attr') ?>" <?= in_array((int) $row['id'], $selected, true) ? 'checked' : '' ?>></td><td data-label="Pelamar"><strong><?= esc($row['full_name']) ?></strong></td><td data-label="Lamaran"><?= esc($row['application_number']) ?></td><td data-label="Posisi"><?= esc($row['vacancy_title']) ?></td></tr><?php endforeach ?>
        </tbody></table></div>
        <p class="agenda-muted">Pilihan berlaku untuk halaman ini. Tambahkan peserta sebelum berpindah halaman atau mengganti filter.</p>
        <div class="agenda-filters agenda-submit-bar"><?php if (! $hasStarted): ?><label>Batas konfirmasi peserta (WIB)<input type="datetime-local" name="confirmation_deadline_at" value="<?= esc(old('confirmation_deadline_at', '', false), 'attr') ?>" max="<?= esc(date('Y-m-d\TH:i', strtotime($agenda['starts_at']) - 60), 'attr') ?>" required></label><?php else: ?><p class="agenda-muted">Agenda sudah berlangsung. Peserta yang ditambahkan dapat langsung dicatat kehadirannya.</p><?php endif ?><button class="agenda-button" type="submit" data-agenda-add>Tambahkan Peserta</button></div>
    </form>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base . '/peserta', 'parameters' => ['keyword' => $keyword, 'vacancy_id' => $vacancyId]]) ?>
</section>
<?php endif ?>
