<?php
$id = (int) ($agenda['id'] ?? 0);
$base = site_url('adminhrdmannakampus/agenda');
$value = static fn (string $key, mixed $default = ''): mixed => old($key, $agenda[$key] ?? $default, false);
$datetime = static fn (mixed $value): string => $value ? date('Y-m-d\TH:i', strtotime((string) $value)) : '';
?>
<section class="dashboard-welcome agenda-heading"><div><span class="login-eyebrow">Agenda Seleksi</span><h1><?= esc($title) ?></h1><p>Tentukan waktu, lokasi, dan penanggung jawab. Peserta ditambahkan setelah agenda disimpan.</p></div><a class="agenda-button agenda-button-secondary" href="<?= $base ?><?= $id ? '/' . $id : '' ?>">Kembali</a></section>
<section class="settings-card agenda-card">
    <form method="post" action="<?= $base ?><?= $id ? '/' . $id : '' ?>" class="agenda-form">
        <?= csrf_field() ?>
        <label class="agenda-wide">Nama agenda<input name="name" maxlength="200" value="<?= esc($value('name'), 'attr') ?>" placeholder="Contoh: Tes Tertulis Gelombang 1" required></label>
        <label>Tahap seleksi<select name="stage_id" required><option value="">Pilih tahap</option><?php foreach ($stages as $stage): ?><option value="<?= (int) $stage['id'] ?>" <?= (int) $value('stage_id') === (int) $stage['id'] ? 'selected' : '' ?>><?= esc($stage['name']) ?></option><?php endforeach ?></select></label>
        <label>PIC<select name="pic_user_id" required><option value="">Pilih penanggung jawab</option><?php foreach ($pics as $pic): ?><option value="<?= (int) $pic['id'] ?>" <?= (int) $value('pic_user_id', $auth['user_id'] ?? 0) === (int) $pic['id'] ? 'selected' : '' ?>><?= esc($pic['full_name']) ?></option><?php endforeach ?></select></label>
        <label>Mulai (WIB)<input type="datetime-local" name="starts_at" value="<?= esc($datetime($value('starts_at')), 'attr') ?>" required></label>
        <label>Selesai (WIB, opsional)<input type="datetime-local" name="ends_at" value="<?= esc($datetime($value('ends_at')), 'attr') ?>"></label>
        <label class="agenda-wide">Lokasi / tautan pertemuan<input name="venue" maxlength="1000" value="<?= esc($value('venue'), 'attr') ?>" required></label>
        <label>Kuota peserta<input type="number" name="capacity" min="1" max="100000" value="<?= esc($value('capacity'), 'attr') ?>" placeholder="Kosongkan jika tidak dibatasi"></label>
        <label>Status<select name="status"><option value="draft" <?= $value('status', 'scheduled') === 'draft' ? 'selected' : '' ?>>Draf</option><option value="scheduled" <?= $value('status', 'scheduled') === 'scheduled' ? 'selected' : '' ?>>Terjadwal</option></select></label>
        <label class="agenda-wide">Petunjuk peserta<textarea name="instructions" rows="4" maxlength="5000" placeholder="Contoh: Hadir 15 menit sebelum tes dan membawa alat tulis."><?= esc($value('instructions')) ?></textarea></label>
        <?php if ($id): ?>
            <div class="agenda-note agenda-wide">Perubahan waktu mulai, lokasi, PIC, atau petunjuk akan diterapkan ke peserta aktif dan meminta konfirmasi ulang. Isi batas konfirmasi baru jika mengubah informasi tersebut. Tahap seleksi tidak dapat diganti setelah ada peserta.</div>
            <label>Batas konfirmasi baru (WIB)<input type="datetime-local" name="confirmation_deadline_at" value="<?= esc($value('confirmation_deadline_at'), 'attr') ?>"></label>
        <?php endif ?>
        <div class="agenda-actions agenda-wide"><button class="agenda-button" type="submit">Simpan Agenda</button><a class="agenda-button agenda-button-secondary" href="<?= $base ?><?= $id ? '/' . $id : '' ?>">Batal</a></div>
    </form>
</section>
