<?php foreach ($vacancies ?? [] as $vacancy): ?>
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
                <p>Jadilah bagian dari tim <?= esc($vacancy['department'] ?: 'Manna Kampus') ?> sebagai <?= esc($vacancy['title']) ?> di <?= esc($vacancy['location'] ?: 'Yogyakarta') ?>.</p>
            </div>
            <div>
                <h3>Persyaratan Awal</h3>
                <?php if ($vacancy['screening_questions'] !== []): ?>
                    <ul>
                        <?php foreach ($vacancy['screening_questions'] as $question): ?>
                            <li><?= esc($question['question_text']) ?></li>
                        <?php endforeach ?>
                    </ul>
                <?php else: ?>
                    <p>Persyaratan lengkap akan diinformasikan pada saat proses lamaran.</p>
                <?php endif ?>
            </div>
            <a class="button button-primary" href="#cara-melamar">Cara Melamar <span aria-hidden="true">→</span></a>
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
