<?php
$screening = $mode === 'screening';
$metrics = $screening
    ? [['Belum screening', $summary['total'], 'blue'], ['Belum dibagikan', $summary['unassigned'], 'orange'], ['Sudah di divisi', $summary['total'] - $summary['unassigned'], 'green'], ['Menunggu terlama', $summary['longest'] . ' hari', 'purple']]
    : [['Perlu tindak lanjut', $summary['total'], 'blue'], ['Menunggu keputusan', $summary['decision'], 'orange'], ['Menunggu jadwal', $summary['schedule'], 'green'], ['Menunggu terlama', $summary['longest'] . ' hari', 'purple']];
$icons = ['blue' => '<path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h5"/>', 'orange' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', 'green' => '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4M16 3v4M4 10h16m4 5 2 2 5-5"/>', 'purple' => '<path d="M4 5v15h16M8 16v-4M12 16V9M16 16V5"/>'];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/applicant-reports.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/applicant-reports.css') ?>">
<section class="dashboard-welcome agenda-heading">
    <div><span class="login-eyebrow">Report rekrutmen</span><h1><?= esc($title) ?></h1><p><?= $screening ? 'Pantau lamaran yang belum mendapat keputusan screening berkas.' : 'Pantau keputusan dan jadwal berikutnya setelah tes atau wawancara.' ?></p></div>
    <?php if (! $screening): ?><a class="agenda-button" href="<?= esc($base . '/export?' . http_build_query($filters), 'attr') ?>">Unduh Excel</a><?php endif ?>
</section>
<section class="settings-card agenda-card simple-filter-card">
    <form method="get" action="<?= $base ?>" class="followup-filter followup-filter-simple">
        <div class="agenda-filters">
            <label><span class="followup-field-label">Nama / nomor lamaran</span><input type="search" name="keyword" maxlength="100" placeholder="Cari nama / nomor lamaran" value="<?= esc($filters['keyword'], 'attr') ?>"></label>
            <label><span class="followup-field-label">Posisi</span><select name="vacancy_id"><option value="">Semua posisi</option><?php foreach ($vacancies as $vacancy): ?><option value="<?= (int) $vacancy['id'] ?>" <?= $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>><?= esc($vacancy['title']) ?></option><?php endforeach ?></select></label>

        </div>
        <div class="followup-actions"><a class="agenda-button agenda-button-secondary" href="<?= $base ?>">Reset</a><button class="agenda-button" type="submit">Tampilkan</button></div>
    </form>
</section>
<section class="followup-summary" aria-label="Ringkasan sesuai filter">
    <?php foreach ($metrics as [$label, $value, $color]): ?>
        <article class="settings-card"><span class="followup-icon is-<?= $color ?>" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $icons[$color] ?></svg></span><div><span><?= esc($label) ?></span><strong><?= esc($value) ?></strong></div></article>
    <?php endforeach ?>
</section>
<section class="settings-card agenda-card">
    <div class="agenda-section-heading"><h2>Daftar pelamar</h2><span><?= (int) $total ?> lamaran</span></div>
    <p class="followup-description">Urutan dari yang paling lama menunggu. Satu baris mewakili satu lamaran, sehingga satu pelamar bisa muncul untuk beberapa posisi.</p>
    <details class="followup-explanation"><summary>Cara membaca laporan</summary>
        <?php if ($screening): ?><p>Belum screening berarti lamaran masih berada pada tahap awal atau screening berkas dan belum berstatus lolos/gagal screening. Lama menunggu dihitung sejak tanggal melamar, bukan sejak profil dibuka. Pelamar yang belum mendapat divisi diberi penanda tersendiri.</p>
        <?php else: ?><p><strong>Menunggu keputusan:</strong> peserta sudah dicatat hadir pada tahap saat ini dan belum dipindahkan ke tahap berikutnya atau digugurkan. Acuan waktu adalah jadwal pelaksanaan peserta, karena waktu selesai tes belum dicatat secara khusus.</p><p><strong>Menunggu jadwal:</strong> ada riwayat perpindahan dari tahap tes/wawancara yang sudah dihadiri ke tahap berikutnya, tetapi belum ada jadwal aktif. Lama menunggu dihitung sejak perpindahan tahap tersebut.</p><p>Screening berkas, pelamar gugur/diterima/mengundurkan diri, peserta dengan jadwal aktif, dan peserta yang belum dicatat hadir tidak termasuk. Jika bukti kehadiran atau riwayat perpindahan belum lengkap, pelamar belum dapat dikategorikan di laporan ini. PIC yang ditampilkan adalah PIC tahap terakhir, bukan penugasan baru.</p><?php endif ?>
        <p>Lama menunggu dihitung dalam hari penuh (24 jam). <?php if (! $screening): ?>Filter tanggal memakai tanggal acuan pada tabel.<?php endif ?> Angka ringkasan dan Excel mengikuti filter serta hak akses divisi.</p>
    </details>
    <div class="department-table-wrap"><table class="department-table followup-table">
        <thead><tr><th>Pelamar / posisi</th><th>Divisi<?= $screening ? '' : ' / PIC terakhir' ?></th><th>Tahap terakhir</th><th>Kondisi / tindak lanjut</th><th>Tanggal acuan (WIB)</th><th>Menunggu</th><th></th></tr></thead>
        <tbody>
        <?php if ($rows === []): ?><tr><td colspan="7" class="department-empty">Tidak ada lamaran tertunda sesuai filter. Coba reset filter untuk melihat seluruh data yang dapat Anda akses.</td></tr><?php endif ?>
        <?php foreach ($rows as $row): ?>
            <tr><td><strong><?= esc($row['full_name']) ?></strong><small><?= esc($row['application_number']) ?></small><small><?= esc($row['vacancy_title']) ?></small></td>
                <td><?php if ($row['team_name']): ?><?= esc($row['team_name']) ?><?php else: ?><span class="followup-badge is-orange">Belum dibagikan</span><?php endif ?><?php if (! $screening): ?><small>PIC: <?= esc($row['pic_name'] ?: 'Belum tercatat') ?></small><?php endif ?></td>
                <td><?= esc($row['last_stage']) ?></td>
                <td><span class="followup-badge <?= $row['condition'] === 'schedule' ? 'is-green' : 'is-orange' ?>"><?= esc($conditions[$row['condition']]) ?></span><small><?= esc($row['next_stage']) ?></small></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['reference_at']))) ?><small><?= esc($row['reference_label']) ?></small></td>
                <td><strong class="followup-wait"><?= (int) $row['wait_days'] ?> hari</strong></td>
                <td><a class="agenda-row-link" href="<?= esc(site_url('adminhrdmannakampus/pelamar/' . (int) $row['applicant_id']) . ($row['assigned_hrd_team_id'] ? '?source=division&team_id=' . (int) $row['assigned_hrd_team_id'] : ''), 'attr') ?>">Lihat profil &rarr;</a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
    <?= view('admin/agenda/pagination', ['page' => $page, 'total' => $total, 'baseUrl' => $base, 'parameters' => $filters]) ?>
</section>
