<?php
$selectedStages = array_column((array) ($template['stages'] ?? []), 'display_order', 'stage_id');
?>
<div class="template-form-grid">
    <label>Nama template<input name="name" value="<?= esc((string) ($template['name'] ?? ''), 'attr') ?>" maxlength="150" required></label>
    <label>Kode<input name="code" value="<?= esc((string) ($template['code'] ?? ''), 'attr') ?>" maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="contoh-tahapan" required></label>
    <label class="template-wide-field">Keterangan<textarea name="description" rows="2" maxlength="500"><?= esc((string) ($template['description'] ?? '')) ?></textarea></label>
</div>
<div class="template-stage-picker"><strong>Pilih dan tentukan urutan tahap</strong><p>Centang tahap yang digunakan, lalu isi nomor urut. Tahap Diterima otomatis ditempatkan paling akhir.</p>
    <div class="template-stage-options">
    <?php foreach ($stages as $stage): $order = $selectedStages[(int) $stage['id']] ?? ''; ?>
        <label class="template-stage-option"><input type="checkbox" name="stage_ids[]" value="<?= (int) $stage['id'] ?>" <?= $order !== '' ? 'checked' : '' ?>><span style="--stage-color:<?= esc($stage['color_hex'], 'attr') ?>"><i></i><?= esc($stage['name']) ?></span><input type="number" name="stage_order[<?= (int) $stage['id'] ?>]" value="<?= esc((string) $order, 'attr') ?>" min="1" max="99" aria-label="Urutan <?= esc($stage['name'], 'attr') ?>"></label>
    <?php endforeach ?>
    </div>
</div>
<label class="vacancy-checkbox template-active-check"><input type="checkbox" name="is_active" value="1" <?= ! isset($template['is_active']) || (int) $template['is_active'] === 1 ? 'checked' : '' ?>><span><strong>Template aktif</strong><small>Dapat dipilih pada form lowongan baru maupun saat mengedit lowongan.</small></span></label>
