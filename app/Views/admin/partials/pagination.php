<?php
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$currentPage = (int) ($pagination['page'] ?? 1);
$pageStart = max(1, $currentPage - 2);
$pageEnd = min($totalPages, $currentPage + 2);
$query = is_array($query ?? null) ? $query : [];
$unit = trim((string) ($unit ?? 'data')) ?: 'data';
$pageUrl = static fn (int $page): string => $baseUrl . '?' . http_build_query($query + ['page' => max(1, $page)]);
?>
<?php if ($totalPages > 1): ?>
    <nav class="vacancy-period-pagination" aria-label="Pagination <?= esc($unit, 'attr') ?>">
        <span>Menampilkan <?= (int) $pagination['offset'] + 1 ?>–<?= min((int) $pagination['offset'] + (int) $pagination['per_page'], (int) $pagination['total']) ?> dari <?= (int) $pagination['total'] ?> <?= esc($unit) ?></span>
        <div>
            <a class="pagination-direction <?= $currentPage === 1 ? 'is-disabled' : '' ?>" href="<?= $currentPage === 1 ? '#' : esc($pageUrl($currentPage - 1), 'attr') ?>" <?= $currentPage === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>&larr; Sebelumnya</a>
            <?php if ($pageStart > 1): ?><a href="<?= esc($pageUrl(1), 'attr') ?>">1</a><?php if ($pageStart > 2): ?><i>…</i><?php endif ?><?php endif ?>
            <?php for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++): ?><a class="<?= $pageNumber === $currentPage ? 'is-active' : '' ?>" href="<?= esc($pageUrl($pageNumber), 'attr') ?>" <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a><?php endfor ?>
            <?php if ($pageEnd < $totalPages): ?><?php if ($pageEnd < $totalPages - 1): ?><i>…</i><?php endif ?><a href="<?= esc($pageUrl($totalPages), 'attr') ?>"><?= $totalPages ?></a><?php endif ?>
            <a class="pagination-direction <?= $currentPage === $totalPages ? 'is-disabled' : '' ?>" href="<?= $currentPage === $totalPages ? '#' : esc($pageUrl($currentPage + 1), 'attr') ?>" <?= $currentPage === $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Berikutnya &rarr;</a>
        </div>
    </nav>
<?php endif ?>
