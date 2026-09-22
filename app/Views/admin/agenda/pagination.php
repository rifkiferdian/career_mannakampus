<?php $pages = max(1, (int) ceil($total / 50)); ?>
<?php if ($pages > 1): ?>
<nav class="agenda-pagination" aria-label="Halaman daftar">
    <span>Halaman <?= (int) $page ?> dari <?= $pages ?> · <?= (int) $total ?> data</span>
    <?php if ($page > 1): ?><a href="<?= esc($baseUrl . '?' . http_build_query($parameters + ['page' => $page - 1]), 'attr') ?>">&larr; Sebelumnya</a><?php endif ?>
    <?php if ($page < $pages): ?><a href="<?= esc($baseUrl . '?' . http_build_query($parameters + ['page' => $page + 1]), 'attr') ?>">Berikutnya &rarr;</a><?php endif ?>
</nav>
<?php endif ?>
