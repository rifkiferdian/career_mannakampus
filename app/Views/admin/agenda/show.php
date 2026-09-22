<?php
$base = site_url('adminhrdmannakampus/agenda/' . $agenda['id']);
$editable = in_array($agenda['status'], ['draft', 'scheduled'], true) && $agenda['starts_at'] > date('Y-m-d H:i:s');
$activeCount = array_sum($summary) - (int) ($summary['cancelled'] ?? 0);
?>
<section class="dashboard-welcome agenda-heading">
    <div><span class="login-eyebrow"><?= esc($agenda['stage_name']) ?></span><h1><?= esc($agenda['name']) ?></h1><p><span class="agenda-badge agenda-badge-<?= esc($agenda['status'], 'attr') ?>"><?= esc($statuses[$agenda['status']] ?? $agenda['status']) ?></span></p></div>
    <div class="agenda-actions"><a class="agenda-button agenda-button-secondary" href="<?= site_url('adminhrdmannakampus/agenda') ?>?date=<?= esc(substr($agenda['starts_at'], 0, 10), 'attr') ?>">Daftar Agenda</a><?php if ($canManage && $editable): ?><a class="agenda-button agenda-button-secondary" href="<?= $base ?>/edit">Ubah Agenda</a><?php if ($agenda['status'] === 'scheduled'): ?><a class="agenda-button" href="<?= $base ?>/peserta">+ Tambah Peserta</a><?php endif ?><?php endif ?></div>
</section>
<section class="settings-card agenda-card">
    <dl class="agenda-details">
        <div><dt>Pelaksanaan (WIB)</dt><dd><?= esc(date('d/m/Y H:i', strtotime($agenda['starts_at']))) ?><?= $agenda['ends_at'] ? ' – ' . esc(date('d/m/Y H:i', strtotime($agenda['ends_at']))) : '' ?></dd></div>
        <div><dt>Lokasi / tautan</dt><dd><?= esc($agenda['venue']) ?></dd></div>
        <div><dt>PIC</dt><dd><?= esc($agenda['pic_name']) ?></dd></div>
        <div><dt>Peserta aktif / kuota</dt><dd><?= (int) $activeCount ?> / <?= $agenda['capacity'] === null ? 'Tanpa batas' : (int) $agenda['capacity'] ?></dd></div>
    </dl>
    <?php if ($agenda['instructions']): ?><div class="agenda-note"><strong>Petunjuk peserta</strong><p class="agenda-preline"><?= esc($agenda['instructions']) ?></p></div><?php endif ?>
    <?php if ($agenda['status'] === 'draft'): ?><p class="agenda-note">Ubah status menjadi Terjadwal untuk mulai menambahkan peserta.</p><?php endif ?>
    <div class="agenda-summary"><span><strong><?= (int) ($summary['confirmed'] ?? 0) ?></strong> Terkonfirmasi</span><span><strong><?= (int) ($summary['present'] ?? 0) ?></strong> Hadir</span><span><strong><?= (int) ($summary['absent'] ?? 0) ?></strong> Tidak hadir</span><span><strong><?= (int) ($summary['scheduled'] ?? 0) ?></strong> Menunggu konfirmasi</span><span><strong><?= (int) ($summary['reschedule_requested'] ?? 0) ?></strong> Minta jadwal ulang</span></div>
</section>
<section class="settings-card agenda-card">
    <h2>Daftar Peserta</h2>
    <div class="department-table-wrap"><table class="department-table agenda-table agenda-table--participants"><thead><tr><th>Pelamar</th><th>Posisi</th><th>Jam (WIB)</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php if ($rows === []): ?><tr><td colspan="5" class="department-empty">Belum ada peserta. Klik Tambah Peserta untuk memilih pelamar.</td></tr><?php endif ?>
    <?php foreach ($rows as $row): ?>
        <tr><td data-label="Pelamar"><strong><?= esc($row['full_name']) ?></strong><small><?= esc($row['application_number']) ?></small></td><td data-label="Posisi"><?= esc($row['vacancy_title']) ?></td><td data-label="Jam"><strong class="agenda-time"><?= esc(date('H:i', strtotime($row['scheduled_at']))) ?></strong></td><td data-label="Status"><span class="agenda-badge agenda-badge-<?= esc($row['status'], 'attr') ?>"><?= esc($participantStatuses[$row['status']] ?? $row['status']) ?></span><?php if ($row['candidate_note']): ?><small><?= esc($row['candidate_note']) ?></small><?php endif ?></td>
        <td><div class="agenda-actions">
            <?php if ($canViewApplicant): ?><a class="agenda-action-link" href="<?= site_url('adminhrdmannakampus/pelamar/' . $row['applicant_id']) ?>?source=division&amp;team_id=<?= (int) $row['assigned_hrd_team_id'] ?>">Profil</a><?php endif ?>
            <?php if ($canRecordAttendance && $agenda['status'] === 'scheduled' && $row['status'] !== 'cancelled' && $row['scheduled_at'] <= date('Y-m-d H:i:s')): ?>
                <?php foreach (['present' => 'Hadir', 'absent' => 'Tidak hadir'] as $status => $label): ?>
                <form method="post" action="<?= $base ?>/peserta/<?= (int) $row['id'] ?>/kehadiran"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $status ?>"><button class="agenda-button agenda-button-small agenda-button-secondary" data-confirm="Catat <?= esc($row['full_name'], 'attr') ?>: <?= esc($label, 'attr') ?>?" type="submit"><?= $label ?></button></form>
                <?php endforeach ?>
            <?php endif ?>
            <?php if ($canManage && $agenda['status'] === 'scheduled' && in_array($row['status'], ['scheduled', 'confirmed', 'reschedule_requested'], true)): ?><form method="post" action="<?= $base ?>/peserta/<?= (int) $row['id'] ?>/batal"><?= csrf_field() ?><button class="agenda-button agenda-button-small agenda-button-danger" data-confirm="Batalkan jadwal peserta ini?" type="submit">Batalkan</button></form><?php endif ?>
        </div></td></tr>
    <?php endforeach ?>
    </tbody></table></div>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base, 'parameters' => []]) ?>
</section>
<?php if ($canManage && in_array($agenda['status'], ['draft', 'scheduled'], true)): ?>
<section class="settings-card agenda-card"><h2>Kelola Status Agenda</h2><p>Agenda dapat diselesaikan setelah kehadiran semua peserta dicatat. Pembatalan agenda akan membatalkan seluruh jadwal peserta yang masih aktif.</p><div class="agenda-actions">
    <?php if ($agenda['status'] === 'scheduled' && $agenda['starts_at'] <= date('Y-m-d H:i:s')): ?><form method="post" action="<?= $base ?>/status"><?= csrf_field() ?><input type="hidden" name="status" value="completed"><button class="agenda-button" type="submit" data-confirm="Tandai agenda ini selesai?">Selesaikan Agenda</button></form><?php endif ?>
    <form method="post" action="<?= $base ?>/status"><?= csrf_field() ?><input type="hidden" name="status" value="cancelled"><button class="agenda-button agenda-button-danger" type="submit" data-confirm="Batalkan agenda dan seluruh jadwal peserta yang masih aktif?">Batalkan Agenda</button></form>
</div></section>
<?php endif ?>
