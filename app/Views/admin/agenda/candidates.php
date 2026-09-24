<?php
$base = site_url('adminhrdmannakampus/agenda/' . $agenda['id']);
$acceptsParticipants = $agenda['status'] === 'scheduled';
$hasStarted = $agenda['starts_at'] <= date('Y-m-d H:i:s');
$selected = old('application_ids', [], false);
$selected = is_array($selected) ? array_map('intval', $selected) : [];
?>
<section class="dashboard-welcome agenda-heading">
    <div><span class="login-eyebrow"><?= esc($agenda['name']) ?></span><h1>Tambah Peserta</h1><p><?= esc($agenda['stage_name']) ?> · <?= esc(date('d/m/Y H:i', strtotime($agenda['starts_at']))) ?><?= $agenda['ends_at'] ? '–' . esc(date('H:i', strtotime($agenda['ends_at']))) : '' ?> WIB · <?= esc($agenda['venue']) ?></p></div>
    <a class="agenda-button agenda-button-secondary" href="<?= $base ?>">Kembali ke Agenda</a>
</section>
<?php if (! $acceptsParticipants): ?>
<div class="agenda-note">Peserta hanya dapat ditambahkan ke agenda berstatus Terjadwal.</div>
<?php else: ?>
<section class="settings-card agenda-card">
    <form method="get" action="<?= $base ?>/peserta" class="agenda-filters">
        <label>Cari pelamar<input type="search" name="keyword" maxlength="100" value="<?= esc($keyword, 'attr') ?>" placeholder="Nama atau nomor lamaran"></label>
        <label>Posisi<select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $vacancyId === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>
        <button class="agenda-button" type="submit">Cari</button>
    </form>
    <p>Menampilkan pelamar yang tahap berikutnya adalah <?= esc($agenda['stage_name']) ?>, serta pelamar pada tahap tersebut yang belum memiliki agenda.</p>
</section>
<section class="settings-card agenda-card">
    <div class="agenda-note">Pelamar terpilih akan diloloskan ke <?= esc($agenda['stage_name']) ?> sekaligus mendapat jadwal agenda ini. Jadwal tahap sebelumnya akan ditutup otomatis. Pelamar dengan urutan tahap yang tidak sesuai tetap tidak ditampilkan.</div>
    <form method="post" action="<?= $base ?>/peserta" data-agenda-participants>
        <?= csrf_field() ?>
        <div class="agenda-selection-bar"><label class="agenda-checkbox"><input type="checkbox" data-agenda-select-all> Pilih semua pada halaman ini</label><span data-agenda-selection-count aria-live="polite">0 peserta dipilih</span></div>
        <div class="department-table-wrap"><table class="department-table agenda-table agenda-table--candidates"><thead><tr><th>Pilih</th><th>Pelamar</th><th>Posisi</th><th>Tahap saat ini</th><th>Hasil terakhir</th></tr></thead><tbody>
        <?php if ($rows === []): ?><tr><td colspan="5" class="department-empty">Belum ada pelamar yang tahap berikutnya sesuai dengan agenda ini.</td></tr><?php endif ?>
        <?php foreach ($rows as $row): ?><tr><td><input type="checkbox" name="application_ids[]" value="<?= (int) $row['id'] ?>" aria-label="Pilih <?= esc($row['full_name'], 'attr') ?> untuk <?= esc($row['vacancy_title'], 'attr') ?>" <?= in_array((int) $row['id'], $selected, true) ? 'checked' : '' ?>></td><td data-label="Pelamar"><strong><?= esc($row['full_name']) ?></strong><small><?= esc($row['application_number']) ?></small></td><td data-label="Posisi"><?= esc($row['vacancy_title']) ?></td><td data-label="Tahap saat ini"><?= (int) $row['advances_stage'] === 1 ? esc($row['current_stage_name'] ?: $row['current_stage_code']) : esc($agenda['stage_name']) ?></td><td data-label="Hasil terakhir"><?php if ((int) $row['advances_stage'] === 0): ?><span class="agenda-badge">Belum dijadwalkan</span><?php elseif ($row['previous_result']): ?><span class="agenda-badge agenda-badge-<?= esc($row['previous_result'], 'attr') ?>"><?= esc($participantStatuses[$row['previous_result']] ?? ucwords(str_replace('_', ' ', $row['previous_result']))) ?></span><?php else: ?><span class="agenda-badge">Belum ada hasil</span><?php endif ?></td></tr><?php endforeach ?>
        </tbody></table></div>
        <div class="agenda-filters agenda-submit-bar <?= $hasStarted ? 'agenda-submit-bar--simple' : '' ?>">
            <div class="agenda-submit-info"><strong><?= $hasStarted ? 'Agenda sudah berlangsung' : 'Pilih peserta pada halaman ini' ?></strong><small><?= $hasStarted ? 'Peserta yang ditambahkan dapat langsung dicatat kehadirannya. ' : '' ?>Pilihan hanya berlaku pada halaman ini.</small></div>
            <?php if (! $hasStarted): ?><label>Batas konfirmasi peserta (WIB)<input type="datetime-local" name="confirmation_deadline_at" value="<?= esc(old('confirmation_deadline_at', '', false), 'attr') ?>" max="<?= esc(date('Y-m-d\TH:i', strtotime($agenda['starts_at']) - 60), 'attr') ?>" required></label><?php endif ?>
            <button class="agenda-button" type="submit" data-agenda-add>Loloskan &amp; Tambahkan</button>
        </div>
    </form>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base . '/peserta', 'parameters' => ['keyword' => $keyword, 'vacancy_id' => $vacancyId]]) ?>
</section>
<?php endif ?>
