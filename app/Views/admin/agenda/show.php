<?php
$base = site_url('adminhrdmannakampus/agenda/' . $agenda['id']);
$editable = in_array($agenda['status'], ['draft', 'scheduled'], true);
$canAddParticipants = $editable;
$activeCount = array_sum($summary) - (int) ($summary['cancelled'] ?? 0);
$formatWhatsApp = static function (?string $phone): string {
    $number = preg_replace('/\D+/', '', (string) $phone) ?? '';
    if (str_starts_with($number, '0')) {
        return '62' . substr($number, 1);
    }
    return str_starts_with($number, '8') ? '62' . $number : $number;
};
?>
<?php if ($canManage && $agenda['status'] === 'scheduled'): ?>
    <?php foreach ($rows as $row): if (! in_array($row['status'], ['scheduled', 'confirmed', 'reschedule_requested'], true)) { continue; } ?>
    <dialog class="admin-modal agenda-time-modal" id="agenda-time-<?= (int) $row['id'] ?>" aria-labelledby="agenda-time-title-<?= (int) $row['id'] ?>">
        <div class="admin-modal-panel">
            <header class="agenda-time-heading">
                <span class="agenda-time-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <div><h2 id="agenda-time-title-<?= (int) $row['id'] ?>">Ganti jam peserta</h2><p>Atur waktu pelaksanaan dalam agenda ini.</p></div>
                <button class="admin-modal-close" type="button" data-admin-modal-close aria-label="Tutup modal ganti jam">&times;</button>
            </header>
            <form method="post" action="<?= $base ?>/peserta/<?= (int) $row['id'] ?>/jam" class="agenda-time-form">
                <?= csrf_field() ?>
                <div class="agenda-time-body">
                    <section class="agenda-time-summary" aria-label="Jadwal saat ini">
                        <span class="agenda-time-eyebrow">PESERTA</span>
                        <strong><?= esc($row['full_name']) ?></strong>
                        <span class="agenda-time-position"><?= esc($row['vacancy_title']) ?></span>
                        <div class="agenda-time-current"><span>Jadwal saat ini</span><strong><?= esc(date('d/m/Y', strtotime($row['scheduled_at']))) ?> <b><?= esc(date('H:i', strtotime($row['scheduled_at']))) ?> WIB</b></strong></div>
                    </section>
                    <div class="agenda-time-range"><strong><?= esc($agenda['name']) ?></strong><span>Rentang agenda: <?= esc(date('d/m/Y H:i', strtotime($agenda['starts_at']))) ?><?= $agenda['ends_at'] ? ' – ' . esc(date('d/m/Y H:i', strtotime($agenda['ends_at']))) : ' – akhir hari yang sama' ?> WIB</span></div>
                    <label class="agenda-time-field"><span>Waktu peserta yang baru <em>WIB</em></span><input type="datetime-local" name="scheduled_at" value="<?= esc(date('Y-m-d\TH:i', strtotime($row['scheduled_at'])), 'attr') ?>" min="<?= esc(date('Y-m-d\TH:i', strtotime($agenda['starts_at'])), 'attr') ?>" required><small>Pilih waktu setelah sekarang dan dalam rentang agenda.</small></label>
                    <label class="agenda-time-field"><span>Batas konfirmasi <em>WIB</em></span><input type="datetime-local" name="confirmation_deadline_at" value="<?= esc(date('Y-m-d\TH:i', strtotime($row['confirmation_deadline_at'])), 'attr') ?>" required><small>Setelah waktu sekarang, sebelum jadwal peserta yang baru.</small></label>
                    <div class="agenda-time-notice"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7v1"/></svg><p>Peserta akan diminta <strong>konfirmasi ulang</strong> setelah jam diubah. Jadwal peserta lainnya tetap.</p></div>
                </div>
                <footer class="agenda-time-footer"><button class="agenda-button agenda-button-secondary" type="button" data-admin-modal-close>Batal</button><button class="agenda-button" type="submit">Simpan perubahan</button></footer>
            </form>
        </div>
    </dialog>
    <?php endforeach ?>
<?php endif ?>
<section class="dashboard-welcome agenda-heading">
    <div><span class="login-eyebrow"><?= esc($agenda['stage_name']) ?></span><h1><?= esc($agenda['name']) ?></h1><p><span class="agenda-badge agenda-badge-<?= esc($agenda['status'], 'attr') ?>"><?= esc($statuses[$agenda['status']] ?? $agenda['status']) ?></span></p></div>
    <div class="agenda-actions"><a class="agenda-button agenda-button-secondary" href="<?= site_url('adminhrdmannakampus/agenda') ?>?date=<?= esc(substr($agenda['starts_at'], 0, 10), 'attr') ?>">Daftar Agenda</a></div>
</section>
<section class="agenda-status-overview" aria-label="Ringkasan status peserta">
    <article class="agenda-status-card is-confirmed"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><div><strong><?= (int) ($summary['confirmed'] ?? 0) ?></strong><small>Terkonfirmasi</small></div></article>
    <article class="agenda-status-card is-present"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0m2-5 2 2 3-4"/></svg></span><div><strong><?= (int) ($summary['present'] ?? 0) ?></strong><small>Hadir</small></div></article>
    <article class="agenda-status-card is-absent"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0m2-6 5 5m0-5-5 5"/></svg></span><div><strong><?= (int) ($summary['absent'] ?? 0) ?></strong><small>Tidak hadir</small></div></article>
    <article class="agenda-status-card is-scheduled"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><strong><?= (int) ($summary['scheduled'] ?? 0) ?></strong><small>Menunggu konfirmasi</small></div></article>
    <article class="agenda-status-card is-reschedule"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 5v6h-6"/><path d="M12 8v4l2.5 1.5"/></svg></span><div><strong><?= (int) ($summary['reschedule_requested'] ?? 0) ?></strong><small>Minta jadwal ulang</small></div></article>
</section>
<section class="settings-card agenda-card">
    <div class="agenda-section-heading">
        <div><h2>Informasi Agenda</h2><span class="agenda-record-id">ID #<?= (int) $agenda['id'] ?></span></div>
        <?php if ($canManage && $editable): ?><a class="agenda-button" href="<?= $base ?>/edit">Ubah Agenda</a><?php endif ?>
    </div>
    <ul class="agenda-info-list">
        <li><span>Tanggal</span><strong><?= esc(date('d/m/Y', strtotime($agenda['starts_at']))) ?></strong></li>
        <li><span>Waktu</span><strong><?= esc(date('H:i', strtotime($agenda['starts_at']))) ?>–<?= $agenda['ends_at'] ? esc(date('H:i', strtotime($agenda['ends_at']))) : 'selesai' ?> WIB</strong></li>
        <li><span>Tahap</span><strong><?= esc($agenda['stage_name']) ?></strong></li>
        <li><span>Status</span><strong><span class="agenda-badge agenda-badge-<?= esc($agenda['status'], 'attr') ?>"><?= esc($statuses[$agenda['status']] ?? $agenda['status']) ?></span></strong></li>
        <li><span>PIC</span><strong><?= esc($agenda['pic_name']) ?></strong></li>
        <li><span>Lokasi / tautan</span><strong><?= esc($agenda['venue']) ?></strong></li>
        <li><span>Peserta</span><strong><?= (int) $activeCount ?> aktif / <?= $agenda['capacity'] === null ? 'tanpa batas' : (int) $agenda['capacity'] . ' kuota' ?><small><?= array_sum($summary) ?> terdaftar · <?= (int) ($summary['cancelled'] ?? 0) ?> dibatalkan</small></strong></li>
        <li><span>Batas konfirmasi</span><strong><?php if (! empty($deadline['minimum'])): ?><?= esc(date('d/m/Y H:i', strtotime($deadline['minimum']))) ?> WIB<?php if ($deadline['maximum'] !== $deadline['minimum']): ?><small>Terakhir <?= esc(date('d/m/Y H:i', strtotime($deadline['maximum']))) ?> WIB</small><?php endif ?><?php else: ?>Belum ditentukan<?php endif ?></strong></li>
        <li><span>Dibuat oleh</span><strong><?= esc($agenda['created_by_name']) ?></strong></li>
    </ul>
    <div class="agenda-note"><strong>Petunjuk peserta</strong><p class="agenda-preline"><?= $agenda['instructions'] ? esc($agenda['instructions']) : 'Tidak ada petunjuk khusus.' ?></p></div>
    <?php if ($agenda['status'] === 'draft'): ?><p class="agenda-note">Ubah status menjadi Terjadwal untuk mulai menambahkan peserta.</p><?php endif ?>

</section>
<section class="settings-card agenda-card">
    <div class="agenda-section-heading">
        <h2 id="daftar-peserta">Daftar Peserta</h2>
        <?php if ($canManage && $canAddParticipants && $agenda['status'] === 'scheduled'): ?><a class="agenda-button" href="<?= $base ?>/peserta">+ Tambah Peserta</a><?php endif ?>
    </div>
    <form method="get" action="<?= $base ?>#daftar-peserta" class="agenda-filter-form agenda-participant-filters" role="search" aria-label="Cari dan filter peserta">
        <div class="agenda-filters">
            <label>Nama peserta<input type="search" name="keyword" maxlength="100" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Cari nama peserta"></label>
            <label>Posisi<select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>
            <label>Status jadwal<select name="status"><option value="">Semua status</option><?php foreach ($participantStatuses as $status => $label): ?><option value="<?= esc($status, 'attr') ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
        </div>
        <div class="agenda-filter-actions"><button class="agenda-button" type="submit">Cari</button><a class="agenda-button agenda-button-secondary" href="<?= $base ?>#daftar-peserta">Reset</a></div>
    </form>
    <p class="agenda-participant-filter-result" role="status"><?= (int) $total ?> dari <?= (int) $participantCount ?> peserta</p>
    <div class="department-table-wrap"><table class="department-table agenda-table agenda-table--participants"><thead><tr><th class="agenda-number">No.</th><th>Pelamar</th><th>Posisi</th><th>Tahap Saat Ini</th><th>Status Jadwal</th><th>Waktu (WIB)</th><th>WhatsApp</th><th>Aksi</th></tr></thead><tbody>
    <?php if ($rows === []): ?><tr><td colspan="8" class="department-empty"><?= $participantCount > 0 ? 'Tidak ada peserta yang sesuai. Ubah kata pencarian atau reset filter untuk melihat semua peserta.' : 'Belum ada peserta dalam agenda ini.' ?></td></tr><?php endif ?>
    <?php foreach ($rows as $index => $row): $whatsAppNumber = $formatWhatsApp($row['phone'] ?? null); ?>
        <tr><td class="agenda-number" data-label="No."><?= (($page - 1) * 50) + $index + 1 ?></td><td data-label="Pelamar"><strong><?= esc($row['full_name']) ?></strong><small><?= esc($row['application_number']) ?></small></td><td data-label="Posisi"><?= esc($row['vacancy_title']) ?></td><td data-label="Tahap saat ini"><span class="agenda-badge"><?= esc($row['current_stage_name'] ?: ucwords(str_replace('_', ' ', $row['application_status']))) ?></span></td><td data-label="Status jadwal"><span class="agenda-badge agenda-badge-<?= esc($row['status'], 'attr') ?>"><?= esc($participantStatuses[$row['status']] ?? $row['status']) ?></span><?php if ($row['candidate_note']): ?><small><?= esc($row['candidate_note']) ?></small><?php endif ?></td><td data-label="Waktu (WIB)"><strong class="agenda-time"><?= esc(date('H:i', strtotime($row['scheduled_at']))) ?></strong><small><?= esc(date('d/m/Y', strtotime($row['scheduled_at']))) ?></small></td><td data-label="WhatsApp"><?php if ($whatsAppNumber !== ''): ?><?php if ($canUseWhatsappTemplates && $whatsappTemplates !== []): ?><button class="agenda-whatsapp-link" type="button" data-admin-modal-open="agenda-whatsapp-modal-<?= (int) $row['id'] ?>" aria-label="Siapkan WhatsApp untuk <?= esc($row['full_name'], 'attr') ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L0 24l6.5-1.7c1.7.9 3.6 1.4 5.6 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.2-3.4-8.4Z"/></svg><span><?= esc($row['phone']) ?></span></button><?php else: ?><a class="agenda-whatsapp-link" href="https://wa.me/<?= esc($whatsAppNumber, 'attr') ?>" target="_blank" rel="noopener noreferrer" aria-label="Hubungi <?= esc($row['full_name'], 'attr') ?> melalui WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L0 24l6.5-1.7c1.7.9 3.6 1.4 5.6 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.2-3.4-8.4Z"/></svg><span><?= esc($row['phone']) ?></span></a><?php endif ?><?php else: ?>-<?php endif ?></td>
        <td><div class="agenda-actions">
            <?php if ($canManage && $agenda['status'] === 'scheduled' && in_array($row['status'], ['scheduled', 'confirmed', 'reschedule_requested'], true)): ?><button class="agenda-button agenda-button-small agenda-button-compact" type="button" data-admin-modal-open="agenda-time-<?= (int) $row['id'] ?>">Ganti jam</button><?php endif ?>
            <?php if ($canViewApplicant): ?><a class="agenda-action-link agenda-action-link-small agenda-participant-action action-profile" href="<?= site_url('adminhrdmannakampus/pelamar/' . $row['applicant_id']) ?>?source=division&amp;team_id=<?= (int) $row['assigned_hrd_team_id'] ?>" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>Profil</a><?php endif ?>
            <?php if ($canRecordAttendance && $agenda['status'] === 'scheduled' && $row['status'] !== 'cancelled' && $row['scheduled_at'] <= date('Y-m-d H:i:s')): ?>
                <?php foreach (['present' => 'Hadir', 'absent' => 'Tidak hadir'] as $status => $label): ?>
                <form method="post" action="<?= $base ?>/peserta/<?= (int) $row['id'] ?>/kehadiran"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $status ?>"><button class="agenda-button agenda-button-small agenda-button-compact agenda-participant-action action-<?= esc($status, 'attr') ?>" data-confirm="Catat <?= esc($row['full_name'], 'attr') ?>: <?= esc($label, 'attr') ?>?" type="submit"><?php if ($status === 'present'): ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><?php else: ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg><?php endif ?><?= $label ?></button></form>
                <?php endforeach ?>
            <?php endif ?>
            <?php if ($canManage && $agenda['status'] === 'scheduled' && in_array($row['status'], ['scheduled', 'confirmed', 'reschedule_requested'], true)): ?><form method="post" action="<?= $base ?>/peserta/<?= (int) $row['id'] ?>/batal"><?= csrf_field() ?><button class="agenda-button agenda-button-small agenda-button-compact agenda-participant-action action-cancel" data-confirm="Batalkan jadwal peserta ini?" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 8 8 8"/></svg>Batalkan</button></form><?php endif ?>
        </div></td></tr>
    <?php endforeach ?>
    </tbody></table></div>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base, 'parameters' => $filters]) ?>
</section>
<?php if ($canManage && in_array($agenda['status'], ['draft', 'scheduled'], true)): ?>
<section class="settings-card agenda-card"><h2>Kelola Status Agenda</h2><p>Agenda dapat diselesaikan setelah kehadiran semua peserta dicatat. Pembatalan agenda akan membatalkan seluruh jadwal peserta yang masih aktif.</p><div class="agenda-actions">
    <?php if ($agenda['status'] === 'scheduled' && $agenda['starts_at'] <= date('Y-m-d H:i:s')): ?><form method="post" action="<?= $base ?>/status"><?= csrf_field() ?><input type="hidden" name="status" value="completed"><button class="agenda-button" type="submit" data-confirm="Tandai agenda ini selesai?">Selesaikan Agenda</button></form><?php endif ?>
    <form method="post" action="<?= $base ?>/status"><?= csrf_field() ?><input type="hidden" name="status" value="cancelled"><button class="agenda-button agenda-button-danger" type="submit" data-confirm="Batalkan agenda dan seluruh jadwal peserta yang masih aktif?">Batalkan Agenda</button></form>
</div></section>
<?php endif ?>
<?php if ($canUseWhatsappTemplates && $whatsappTemplates !== []): ?>
    <?php foreach ($rows as $row): $whatsAppNumber = $formatWhatsApp($row['phone'] ?? null); if ($whatsAppNumber === '') { continue; } $whatsappContext = [[
        'id' => (int) $row['application_id'],
        'vacancy_title' => $row['vacancy_title'],
        'stage_label' => $row['current_stage_name'] ?: ucwords(str_replace('_', ' ', $row['application_status'])),
        'previous_stage' => '-',
        'next_stage' => '-',
        'schedules' => [[
            'id' => (int) $row['id'], 'status' => $row['status'], 'stage_name' => $agenda['stage_name'],
            'scheduled_at' => $row['scheduled_at'], 'venue' => $row['venue'], 'pic_name' => $agenda['pic_name'],
            'instructions' => $row['instructions'], 'confirmation_deadline_at' => $row['confirmation_deadline_at'],
        ]],
    ]]; ?>
        <?= view('admin/partials/applicant_whatsapp_modal', [
            'modalId' => 'agenda-whatsapp-modal-' . (int) $row['id'], 'phone' => $whatsAppNumber,
            'applicantName' => $row['full_name'], 'recruiterName' => (string) ($auth['name'] ?? 'Admin HRD'),
            'templates' => $whatsappTemplates, 'contexts' => $whatsappContext,
        ]) ?>
    <?php endforeach ?>
<?php endif ?>
