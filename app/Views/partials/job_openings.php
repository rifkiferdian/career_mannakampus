<?php foreach ($vacancies ?? [] as $vacancy): ?>
    <?php $responsibilities = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) ($vacancy['responsibilities'] ?? '')) ?: []))); $qualifications = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) ($vacancy['qualifications'] ?? '')) ?: []))); ?>
    <details
        class="job-opening reveal"
        id="vacancy-<?= esc($vacancy['code'], 'attr') ?>"
    >
        <summary>
            <span class="job-icon <?= esc($vacancy['icon_class'], 'attr') ?>" aria-hidden="true"><?= esc($vacancy['icon_text']) ?></span>
            <span class="job-opening-title">
                <small><?= esc($vacancy['department'] ?: 'Umum') ?> · <?= esc($vacancy['employment_type'] ?: 'Full-time') ?></small>
                <strong><?= esc($vacancy['title']) ?></strong>
                <em><?= esc($vacancy['location'] ?: 'Yogyakarta') ?></em>
                <span class="job-opening-requirements">
                    <span><?= esc($vacancy['age_requirement']) ?></span>
                    <span><?= esc($vacancy['education_requirement']) ?></span>
                </span>
            </span>
            <span class="job-opening-toggle" aria-hidden="true"></span>
        </summary>
        <div class="job-opening-details">
            <div>
                <h3>Tentang Peran</h3>
                <p><?= esc($vacancy['job_description'] ?: ($vacancy['summary'] ?: 'Jadilah bagian dari tim ' . ($vacancy['department'] ?: 'Manna Kampus') . ' sebagai ' . $vacancy['title'] . ' di ' . ($vacancy['location'] ?: 'Yogyakarta') . '.')) ?></p>
            </div>
            <?php if ($responsibilities !== []): ?><div><h3>Tanggung Jawab</h3><ul><?php foreach ($responsibilities as $item): ?><li><?= esc($item) ?></li><?php endforeach ?></ul></div><?php endif ?>
            <div>
                <h3>Kualifikasi</h3>
                <?php if ($qualifications !== []): ?><ul><?php foreach ($qualifications as $item): ?><li><?= esc($item) ?></li><?php endforeach ?></ul>
                <?php elseif ($vacancy['screening_questions'] !== []): ?>
                    <ul>
                        <?php foreach ($vacancy['screening_questions'] as $question): ?>
                            <li><?= esc($question['question_text']) ?></li>
                        <?php endforeach ?>
                    </ul>
                <?php else: ?>
                    <p>Persyaratan lengkap akan diinformasikan pada saat proses lamaran.</p>
                <?php endif ?>
            </div>
            <?php if ((int) ($vacancy['show_salary'] ?? 0) === 1 && ! empty($vacancy['salary_min'])): ?><p class="job-salary">Gaji Rp<?= number_format((float) $vacancy['salary_min'], 0, ',', '.') ?><?= ! empty($vacancy['salary_max']) ? ' – Rp' . number_format((float) $vacancy['salary_max'], 0, ',', '.') : '' ?></p><?php endif ?>
            <a class="button button-primary" href="<?= site_url('lowongan/' . $vacancy['code'] . '/lamar') ?>">Lamar Sekarang <span aria-hidden="true">→</span></a>
        </div>
    </details>
<?php endforeach ?>

<?php if (($vacancies ?? []) === []): ?>
    <div class="jobs-empty" id="jobs-empty">
        <span aria-hidden="true">⌕</span>
        <h3>Lowongan tidak ditemukan</h3>
        <p>Coba gunakan kata kunci atau departemen yang berbeda.</p>
    </div>
<?php endif ?>
