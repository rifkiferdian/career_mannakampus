<?php
$template = is_array($template ?? null) ? $template : [];
$currentMessage = (string) $fieldValue($modalKey, 'message_text', $template['message_text'] ?? '');
?>
<form class="whatsapp-template-form" action="<?= esc($action, 'attr') ?>" method="post" data-whatsapp-template-form>
    <?= csrf_field() ?>
    <label>Nama template<input type="text" name="name" maxlength="150" value="<?= esc((string) $fieldValue($modalKey, 'name', $template['name'] ?? ''), 'attr') ?>" placeholder="Contoh: Undangan wawancara" required></label>
    <label>Kategori<select name="category" required><?php $currentCategory = (string) $fieldValue($modalKey, 'category', $template['category'] ?? 'contact'); foreach ($categories as $category => $categoryLabel): ?><option value="<?= esc($category, 'attr') ?>" <?= $currentCategory === $category ? 'selected' : '' ?>><?= esc($categoryLabel) ?></option><?php endforeach ?></select></label>
    <label>Urutan<input type="number" name="display_order" min="1" max="999" value="<?= esc((string) $fieldValue($modalKey, 'display_order', $defaultOrder), 'attr') ?>" required></label>
    <div class="whatsapp-variable-field">
        <span>Masukkan data otomatis</span>
        <div class="whatsapp-variable-list"><?php foreach ($variables as $variable => $variableLabel): ?><button type="button" data-whatsapp-variable="{{<?= esc($variable, 'attr') ?>}}" title="<?= esc($variableLabel, 'attr') ?>">{{<?= esc($variable) ?>}}</button><?php endforeach ?></div>
        <small>Klik variabel untuk memasukkannya pada posisi kursor di dalam pesan.</small>
    </div>
    <label class="whatsapp-message-field">Isi pesan
        <textarea name="message_text" rows="13" minlength="10" maxlength="2000" data-whatsapp-message placeholder="Tulis pesan WhatsApp. Gunakan variabel di atas untuk data pelamar." required><?= esc($currentMessage) ?></textarea>
        <span class="whatsapp-character-count"><b data-whatsapp-character-count><?= mb_strlen($currentMessage) ?></b>/2.000 karakter</span>
    </label>
    <section class="whatsapp-template-preview" aria-label="Preview pesan"><span>Preview pesan</span><p data-whatsapp-preview><?= esc($currentMessage) ?></p></section>
    <div class="screening-flags whatsapp-template-flags"><label><input type="checkbox" name="is_active" value="1" <?= (bool) $fieldValue($modalKey, 'is_active', $template === [] ? 1 : (int) $template['is_active']) ? 'checked' : '' ?>> Template aktif</label></div>
    <div class="department-modal-actions whatsapp-template-actions"><button class="admin-modal-cancel" type="button" data-admin-modal-close>Batal</button><button type="submit">Simpan template</button></div>
</form>
