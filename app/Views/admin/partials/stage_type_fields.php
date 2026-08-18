<?php
$isEdit = ! empty($stage);
$isProtected = $isEdit && in_array((string) $stage['code'], ['accepted', 'rejected'], true);
$isUsed = $isEdit && (int) ($stage['template_count'] ?? 0) > 0;
?>
<div class="stage-type-form-grid">
    <label>Nama tahap<input name="name" value="<?= esc((string) ($stage['name'] ?? ''), 'attr') ?>" maxlength="100" placeholder="Contoh: Tes Kemampuan Dasar" required></label>
    <label>Kode tahap<input name="code" value="<?= esc((string) ($stage['code'] ?? ''), 'attr') ?>" maxlength="50" pattern="[a-z0-9]+(?:_[a-z0-9]+)*" placeholder="tes_kemampuan_dasar" <?= $isEdit ? 'readonly' : '' ?> required><small><?= $isEdit ? 'Kode tidak dapat diubah setelah tahap dibuat.' : 'Gunakan huruf kecil dan garis bawah, tanpa spasi.' ?></small></label>
    <label>Warna penanda<span class="stage-type-color-input"><input type="color" name="color_hex" value="<?= esc((string) ($stage['color_hex'] ?? '#F87638'), 'attr') ?>" required><code><?= esc((string) ($stage['color_hex'] ?? '#F87638')) ?></code></span></label>
    <label>Batas waktu (SLA)<span class="stage-type-sla-input"><input type="number" name="sla_days" value="<?= (int) ($stage['sla_days'] ?? 3) ?>" min="0" max="365" <?= $isProtected ? 'readonly' : '' ?>><em>hari</em></span><small>Isi 0 jika tahap tidak memiliki batas waktu.</small></label>
</div>
<label class="vacancy-checkbox template-active-check"><input type="checkbox" name="is_active" value="1" <?= ! isset($stage['is_active']) || (int) $stage['is_active'] === 1 ? 'checked' : '' ?> <?= $isProtected || $isUsed ? 'disabled' : '' ?>><span><strong>Jenis tahap aktif</strong><small><?= $isProtected ? 'Tahap sistem wajib selalu aktif.' : ($isUsed ? 'Sedang digunakan template sehingga wajib tetap aktif.' : 'Dapat dipilih saat menyusun template tahapan.') ?></small></span></label>
<?php if ($isProtected || $isUsed): ?><input type="hidden" name="is_active" value="1"><?php endif ?>
