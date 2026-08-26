<?php
$aspect = is_array($aspect ?? null) ? $aspect : [];
$options = json_decode((string) ($aspect['options_json'] ?? ''), true);
$optionsText = is_array($options) ? implode(PHP_EOL, $options) : '';
$currentType = (string) $fieldValue($modalKey, 'input_type', $aspect['input_type'] ?? 'scale_1_5');
?>
<form class="recommendation-aspect-form" action="<?= esc($action, 'attr') ?>" method="post">
    <?= csrf_field() ?>
    <label class="aspect-form-name">Nama aspek<input type="text" name="name" maxlength="180" value="<?= esc((string) $fieldValue($modalKey, 'name', $aspect['name'] ?? ''), 'attr') ?>" placeholder="Contoh: Kemampuan memimpin" required></label>
    <label>Jenis jawaban<select name="input_type" required><?php foreach ($inputTypes as $type => $label): ?><option value="<?= esc($type, 'attr') ?>" <?= $currentType === $type ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
    <label>Urutan<input type="number" name="display_order" min="1" max="999" value="<?= esc((string) $fieldValue($modalKey, 'display_order', $defaultOrder), 'attr') ?>" required></label>
    <label class="aspect-form-description">Petunjuk penilaian<textarea name="description" rows="3" maxlength="500" placeholder="Jelaskan hal yang perlu diperhatikan recruiter saat memberi nilai."><?= esc((string) $fieldValue($modalKey, 'description', $aspect['description'] ?? '')) ?></textarea></label>
    <label class="aspect-form-options">Opsi pilihan<textarea name="options" rows="3" maxlength="2000" placeholder="Satu opsi per baris. Hanya digunakan untuk jenis Pilihan khusus."><?= esc((string) $fieldValue($modalKey, 'options', $optionsText)) ?></textarea><small>Minimal dua opsi. Untuk Ya/Tidak, opsi dibuat otomatis.</small></label>
    <div class="screening-flags aspect-form-flags"><label><input type="checkbox" name="is_required" value="1" <?= (bool) $fieldValue($modalKey, 'is_required', $aspect === [] ? 1 : (int) $aspect['is_required']) ? 'checked' : '' ?>> Wajib diisi</label><label><input type="checkbox" name="is_active" value="1" <?= (bool) $fieldValue($modalKey, 'is_active', $aspect === [] ? 1 : (int) $aspect['is_active']) ? 'checked' : '' ?>> Aktif</label></div>
    <div class="department-modal-actions aspect-form-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan aspek</button></div>
</form>
