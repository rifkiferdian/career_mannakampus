<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Formulir lamaran <?= esc($vacancy['title'], 'attr') ?> di Manna Kampus.">
    <meta name="theme-color" content="#f5f7f8">
    <title>Lamar <?= esc($vacancy['title']) ?> | Karier Manna Kampus</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/application.css') ?>?v=16">
</head>
<body class="application-page">
    <header class="application-header">
        <div class="application-header-inner">
            <a href="<?= site_url('lowongan') ?>#vacancy-<?= esc($vacancy['code'], 'attr') ?>" class="application-brand" aria-label="Kembali ke lowongan">
                <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
            </a>
            <div class="application-vacancy">
                <span>Posisi yang dilamar · <?= esc($vacancy['recruitment_period_name'] ?? 'Sesi aktif') ?></span>
                <strong><?= esc($vacancy['title']) ?></strong>
            </div>
            <a class="application-close" href="<?= site_url('lowongan') ?>" aria-label="Tutup formulir">×</a>
        </div>
    </header>

    <main>
        <?php
        $steps = [
            ['Identitas', 'Identity'],
            ['Alamat', 'Address'],
            ['Pendidikan', 'Education'],
            ['Pengalaman', 'Experience'],
            ['Screening', 'Screening'],
            ['Motivasi', 'Motivation'],
            ['Dokumen', 'Documents'],
            ['Tinjau', 'Review'],
        ];
        $errors = session('errors') ?? [];
        $oldVacancyIds = old('vacancy_ids');
        $selectedVacancyIds = is_array($oldVacancyIds)
            ? array_map('intval', $oldVacancyIds)
            : [(int) $vacancy['id']];
        $oldPriorities = old('position_priorities');
        $positionPriorities = is_array($oldPriorities)
            ? array_map('intval', $oldPriorities)
            : [(int) $vacancy['id'] => 1];
        $oldWorkExperiences = old('work_experiences');
        $workExperiences = is_array($oldWorkExperiences) && $oldWorkExperiences !== []
            ? array_map(static fn (mixed $experience): array => is_array($experience) ? $experience : [], array_values($oldWorkExperiences))
            : [['company_name' => '', 'position_title' => '', 'start_year' => '', 'end_year' => '', 'responsibilities' => '']];
        ?>

        <nav class="wizard-progress" aria-label="Tahapan formulir lamaran">
            <ol>
                <?php foreach ($steps as $index => [$label, $english]): ?>
                    <li class="<?= $index === 0 ? 'active' : '' ?>" data-step-indicator="<?= $index + 1 ?>">
                        <span><?= $index + 1 ?></span>
                        <small><?= esc($label) ?></small>
                        <em><?= esc($english) ?></em>
                    </li>
                <?php endforeach ?>
            </ol>
        </nav>

        <form
            class="application-wizard"
            id="application-wizard"
            action="<?= site_url('lowongan/' . $vacancy['code'] . '/lamar') ?>"
            method="post"
            enctype="multipart/form-data"
            novalidate
            data-validation-errors="<?= esc(json_encode($errors, JSON_UNESCAPED_UNICODE), 'attr') ?>"
        >
            <?= csrf_field() ?>

            <?php if (session('form_error')): ?>
                <div class="form-alert" role="alert"><?= esc(session('form_error')) ?></div>
            <?php endif ?>
            <?php if ($errors !== []): ?>
                <div class="form-alert" role="alert">
                    <strong>Beberapa data belum sesuai:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>

            <section class="wizard-panel active" data-step="1" aria-labelledby="step-title-1">
                <div class="panel-heading">
                    <div>
                        <span class="panel-eyebrow">Langkah 1 dari 8</span>
                        <h1 id="step-title-1">Biodata &amp; Identitas</h1>
                        <p>Masukkan data sesuai KTP. NIK akan dienkripsi sebelum disimpan.</p>
                    </div>
                    <span class="save-status">Data terlindungi</span>
                </div>

                <div class="position-selection">
                    <div>
                        <strong>Pilih posisi yang dilamar</strong>
                        <span>Maksimal 3 posisi aktif dari departemen mana pun. Urutan memilih menentukan prioritas.</span>
                    </div>
                    <span class="position-counter"><b id="selected-position-count">1</b>/3 posisi</span>
                    <div class="position-options">
                        <?php foreach ($selectableVacancies as $selectableVacancy): ?>
                            <?php $isPrimary = (int) $selectableVacancy['id'] === (int) $vacancy['id']; ?>
                            <label class="position-option">
                                <input
                                    type="checkbox"
                                    value="<?= (int) $selectableVacancy['id'] ?>"
                                    data-vacancy-choice
                                    <?= $isPrimary ? 'checked disabled' : '' ?>
                                    <?= !$isPrimary && in_array((int) $selectableVacancy['id'], $selectedVacancyIds, true) ? 'checked' : '' ?>
                                    <?= $isPrimary ? '' : 'name="vacancy_ids[]"' ?>
                                >
                                <span>
                                    <strong><?= esc($selectableVacancy['title']) ?></strong>
                                    <small><?= esc($selectableVacancy['department']) ?> · <?= esc($selectableVacancy['location']) ?> · <?= esc($selectableVacancy['recruitment_period_name'] ?? 'Sesi aktif') ?></small>
                                </span>
                                <?php $priority = (int) ($positionPriorities[(int) $selectableVacancy['id']] ?? 0); ?>
                                <b class="priority-badge" data-priority-badge="<?= (int) $selectableVacancy['id'] ?>" <?= $priority > 0 ? '' : 'hidden' ?>>
                                    Prioritas <?= $priority > 0 ? $priority : '' ?>
                                </b>
                            </label>
                            <input
                                type="hidden"
                                name="position_priorities[<?= (int) $selectableVacancy['id'] ?>]"
                                value="<?= $priority > 0 ? $priority : '' ?>"
                                data-priority-input="<?= (int) $selectableVacancy['id'] ?>"
                            >
                            <?php if ($isPrimary): ?>
                                <input type="hidden" name="vacancy_ids[]" value="<?= (int) $selectableVacancy['id'] ?>">
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                </div>

                <label class="photo-upload">
                    <span class="photo-preview" id="photo-preview" aria-hidden="true">◎</span>
                    <span>
                        <strong>Foto Profil <small>Opsional</small></strong>
                        <em>JPG atau PNG, maksimal 2 MB. Gunakan foto formal dengan latar polos.</em>
                        <b id="photo-label">Pilih Foto</b>
                    </span>
                    <input type="file" name="profile_photo" id="profile-photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                </label>

                <div class="form-grid">
                    <label class="field">
                        <span>NIK <b>*</b></span>
                        <input name="nik" type="text" value="<?= esc(old('nik'), 'attr') ?>" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" autocomplete="off" required>
                        <?php if (isset($errors['nik'])): ?><small><?= esc($errors['nik']) ?></small><?php endif ?>
                    </label>
                    <label class="field">
                        <span>Nama lengkap sesuai KTP <b>*</b></span>
                        <input name="full_name" type="text" value="<?= esc(old('full_name'), 'attr') ?>" maxlength="150" autocomplete="name" required>
                        <?php if (isset($errors['full_name'])): ?><small><?= esc($errors['full_name']) ?></small><?php endif ?>
                    </label>
                    <fieldset class="field radio-field">
                        <legend>Jenis kelamin <b>*</b></legend>
                        <label><input name="gender" type="radio" value="PRIA" <?= old('gender') === 'PRIA' ? 'checked' : '' ?> required><span>Pria</span></label>
                        <label><input name="gender" type="radio" value="WANITA" <?= old('gender') === 'WANITA' ? 'checked' : '' ?> required><span>Wanita</span></label>
                    </fieldset>
                    <label class="field">
                        <span>Tempat lahir <b>*</b></span>
                        <input name="birth_place" type="text" value="<?= esc(old('birth_place'), 'attr') ?>" maxlength="100" required>
                    </label>
                    <label class="field">
                        <span>Tanggal lahir <b>*</b></span>
                        <input name="birth_date" id="birth-date" type="date" value="<?= esc(old('birth_date'), 'attr') ?>" max="<?= date('Y-m-d') ?>" required>
                    </label>
                    <label class="field">
                        <span>Usia</span>
                        <input id="applicant-age" type="text" value="Otomatis dari tanggal lahir" readonly>
                    </label>
                    <label class="field">
                        <span>Status pernikahan <b>*</b></span>
                        <select name="marital_status" required>
                            <option value="">Pilih status</option>
                            <?php foreach (['BELUM MENIKAH' => 'Belum Menikah', 'MENIKAH' => 'Menikah', 'CERAI' => 'Cerai'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= old('marital_status') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Agama <b>*</b></span>
                        <select name="religion" required>
                            <option value="">Pilih agama</option>
                            <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'] as $religion): ?>
                                <option value="<?= $religion ?>" <?= old('religion') === $religion ? 'selected' : '' ?>><?= $religion ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Nomor WhatsApp <b>*</b></span>
                        <input name="phone" type="tel" value="<?= esc(old('phone'), 'attr') ?>" placeholder="08xxxxxxxxxx" autocomplete="tel" pattern="(?:\+?62|0)[0-9]{8,13}" title="Gunakan nomor Indonesia, contoh 081234567890" required>
                    </label>
                    <label class="field">
                        <span>Email aktif <b>*</b></span>
                        <input name="email" type="email" value="<?= esc(old('email'), 'attr') ?>" maxlength="150" autocomplete="email" required>
                    </label>
                    <label class="field">
                        <span>Tinggi badan (cm)</span>
                        <input name="height_cm" type="number" value="<?= esc(old('height_cm'), 'attr') ?>" min="100" max="250">
                    </label>
                </div>
            </section>

            <section class="wizard-panel" data-step="2" aria-labelledby="step-title-2" hidden>
                <div class="panel-heading">
                    <div><span class="panel-eyebrow">Langkah 2 dari 8</span><h2 id="step-title-2">Alamat Domisili</h2><p>Alamat digunakan untuk kebutuhan administrasi dan penempatan.</p></div>
                </div>
                <label class="field field-full">
                    <span>Alamat lengkap saat ini <b>*</b></span>
                    <textarea name="address" rows="7" minlength="10" maxlength="1000" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota/kabupaten, dan provinsi" required><?= esc(old('address')) ?></textarea>
                </label>
            </section>

            <section class="wizard-panel" data-step="3" aria-labelledby="step-title-3" hidden>
                <div class="panel-heading">
                    <div><span class="panel-eyebrow">Langkah 3 dari 8</span><h2 id="step-title-3">Pendidikan Terakhir</h2><p>Isi pendidikan formal terakhir yang telah atau sedang diselesaikan.</p></div>
                </div>
                <div class="form-grid">
                    <label class="field"><span>Jenjang pendidikan <b>*</b></span>
                        <select name="last_education" id="last-education" required>
                            <option value="">Pilih jenjang</option>
                            <?php foreach (['SMP', 'SMA/SMK', 'D1', 'D3', 'S1', 'S2'] as $education): ?>
                                <option value="<?= $education ?>" <?= old('last_education') === $education ? 'selected' : '' ?>><?= $education ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label class="field"><span>Nama sekolah/perguruan tinggi <b>*</b></span><input name="institution" type="text" value="<?= esc(old('institution'), 'attr') ?>" maxlength="150" required></label>
                    <label class="field"><span>Jurusan <b>*</b></span><input name="major" type="text" value="<?= esc(old('major'), 'attr') ?>" maxlength="150" required></label>
                    <label class="field"><span>IPK/Nilai akhir</span><input name="gpa" type="number" value="<?= esc(old('gpa'), 'attr') ?>" min="0" max="4" step="0.01" placeholder="Contoh: 3.50"></label>
                    <label class="field field-full"><span>Pelatihan atau sertifikasi</span><textarea name="training_experience" rows="5" maxlength="3000" placeholder="Tuliskan pelatihan, sertifikasi, atau kursus yang relevan"><?= esc(old('training_experience')) ?></textarea></label>
                </div>
            </section>

            <section class="wizard-panel" data-step="4" aria-labelledby="step-title-4" hidden>
                <div class="panel-heading"><div><span class="panel-eyebrow">Langkah 4 dari 8</span><h2 id="step-title-4">Pengalaman Kerja</h2><p>Tambahkan riwayat perusahaan tempat Anda pernah bekerja. Bagian ini opsional bagi pelamar tanpa pengalaman kerja.</p></div></div>
                <div class="work-experience-list" data-work-experience-list>
                    <?php foreach ($workExperiences as $experienceIndex => $experience): ?>
                        <article class="work-experience-entry" data-work-experience-entry>
                            <div class="work-experience-heading"><strong>Perusahaan <span data-work-experience-number><?= $experienceIndex + 1 ?></span></strong><button type="button" data-remove-work-experience>Hapus</button></div>
                            <div class="form-grid">
                                <label class="field field-full"><span>Nama PT/perusahaan</span><input name="work_experiences[<?= $experienceIndex ?>][company_name]" type="text" value="<?= esc((string) ($experience['company_name'] ?? ''), 'attr') ?>" maxlength="150" data-experience-field></label>
                                <label class="field field-full"><span>Jabatan/posisi</span><input name="work_experiences[<?= $experienceIndex ?>][position_title]" type="text" value="<?= esc((string) ($experience['position_title'] ?? ''), 'attr') ?>" maxlength="150" placeholder="Contoh: Staff Administrasi" data-experience-field></label>
                                <label class="field"><span>Tahun masuk</span><input name="work_experiences[<?= $experienceIndex ?>][start_year]" type="number" value="<?= esc((string) ($experience['start_year'] ?? ''), 'attr') ?>" min="1950" max="<?= date('Y') ?>" placeholder="Contoh: 2020" data-experience-field></label>
                                <label class="field"><span>Tahun akhir</span><input name="work_experiences[<?= $experienceIndex ?>][end_year]" type="number" value="<?= esc((string) ($experience['end_year'] ?? ''), 'attr') ?>" min="1950" max="<?= date('Y') + 1 ?>" placeholder="Kosongkan jika masih bekerja" data-experience-field></label>
                                <label class="field field-full"><span>Deskripsi tugas dan tanggung jawab</span><textarea name="work_experiences[<?= $experienceIndex ?>][responsibilities]" rows="5" maxlength="5000" placeholder="Jelaskan posisi, tugas utama, dan tanggung jawab Anda" data-experience-field><?= esc((string) ($experience['responsibilities'] ?? '')) ?></textarea></label>
                            </div>
                        </article>
                    <?php endforeach ?>
                </div>
                <button class="add-work-experience" type="button" data-add-work-experience>+ Tambah PT/perusahaan</button>
                <template id="work-experience-template">
                    <article class="work-experience-entry" data-work-experience-entry>
                        <div class="work-experience-heading"><strong>Perusahaan <span data-work-experience-number></span></strong><button type="button" data-remove-work-experience>Hapus</button></div>
                        <div class="form-grid">
                            <label class="field field-full"><span>Nama PT/perusahaan</span><input name="work_experiences[__INDEX__][company_name]" type="text" maxlength="150" data-experience-field></label>
                            <label class="field field-full"><span>Jabatan/posisi</span><input name="work_experiences[__INDEX__][position_title]" type="text" maxlength="150" placeholder="Contoh: Staff Administrasi" data-experience-field></label>
                            <label class="field"><span>Tahun masuk</span><input name="work_experiences[__INDEX__][start_year]" type="number" min="1950" max="<?= date('Y') ?>" placeholder="Contoh: 2020" data-experience-field></label>
                            <label class="field"><span>Tahun akhir</span><input name="work_experiences[__INDEX__][end_year]" type="number" min="1950" max="<?= date('Y') + 1 ?>" placeholder="Kosongkan jika masih bekerja" data-experience-field></label>
                            <label class="field field-full"><span>Deskripsi tugas dan tanggung jawab</span><textarea name="work_experiences[__INDEX__][responsibilities]" rows="5" maxlength="5000" placeholder="Jelaskan posisi, tugas utama, dan tanggung jawab Anda" data-experience-field></textarea></label>
                        </div>
                    </article>
                </template>
            </section>

            <section class="wizard-panel" data-step="5" aria-labelledby="step-title-5" hidden>
                <div class="panel-heading"><div><span class="panel-eyebrow">Langkah 5 dari 8</span><h2 id="step-title-5">Screening Awal</h2><p>Jawab dengan jujur. Jawaban digunakan untuk menilai kesesuaian awal.</p></div></div>
                <div class="screening-list">
                    <?php foreach ($selectableVacancies as $screeningVacancy): ?>
                        <?php $isSelected = in_array((int) $screeningVacancy['id'], $selectedVacancyIds, true); ?>
                        <section class="screening-position" data-screening-vacancy="<?= (int) $screeningVacancy['id'] ?>" <?= $isSelected ? '' : 'hidden' ?>>
                            <div class="screening-position-heading">
                                <span>Screening Posisi</span>
                                <strong><?= esc($screeningVacancy['title']) ?></strong>
                            </div>
                            <?php foreach ($screeningVacancy['screening_questions'] as $question): ?>
                                <?php $fieldName = 'screening[' . $question['id'] . ']'; $oldAnswer = old('screening.' . $question['id']); ?>
                                <?php if (in_array($question['question_code'], ['gender', 'age', 'marital_status', 'education_level'], true)): ?>
                                    <input
                                        type="hidden"
                                        name="<?= esc($fieldName, 'attr') ?>"
                                        value="<?= esc($oldAnswer, 'attr') ?>"
                                        data-autofill="<?= esc($question['question_code'] === 'education_level' ? 'education' : $question['question_code'], 'attr') ?>"
                                        <?= $isSelected ? '' : 'disabled' ?>
                                    >
                                    <?php continue; ?>
                                <?php endif ?>
                                <div class="screening-question">
                                    <label for="screening-<?= (int) $question['id'] ?>"><?= esc($question['question_text']) ?> <?= (int) $question['is_required'] === 1 ? '<b>*</b>' : '' ?></label>
                                    <?php if ($question['answer_type'] === 'boolean'): ?>
                                        <select id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" <?= (int) $question['is_required'] === 1 ? 'required' : '' ?> <?= $isSelected ? '' : 'disabled' ?>>
                                            <option value="">Pilih jawaban</option>
                                            <option value="1" <?= $oldAnswer === '1' ? 'selected' : '' ?>>Ya</option>
                                            <option value="0" <?= $oldAnswer === '0' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    <?php elseif ($question['answer_type'] === 'yes_no'): ?>
                                        <select id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" <?= (int) $question['is_required'] === 1 ? 'required' : '' ?> <?= $isSelected ? '' : 'disabled' ?>>
                                            <option value="">Pilih jawaban</option><option value="YA" <?= $oldAnswer === 'YA' ? 'selected' : '' ?>>Ya</option><option value="TIDAK" <?= $oldAnswer === 'TIDAK' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    <?php elseif ($question['answer_type'] === 'education_level'): ?>
                                        <select id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" data-autofill="education" <?= (int) $question['is_required'] === 1 ? 'required' : '' ?> <?= $isSelected ? '' : 'disabled' ?>>
                                            <option value="">Pilih pendidikan</option>
                                            <?php foreach (['SMP', 'SMA/SMK', 'D1', 'D3', 'S1', 'S2'] as $education): ?>
                                                <option value="<?= $education ?>" <?= $oldAnswer === $education ? 'selected' : '' ?>><?= $education ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php elseif ($question['question_code'] === 'gender'): ?>
                                        <select id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" data-autofill="gender" required <?= $isSelected ? '' : 'disabled' ?>><option value="">Pilih jenis kelamin</option><option value="PRIA">Pria</option><option value="WANITA">Wanita</option></select>
                                    <?php elseif ($question['question_code'] === 'marital_status'): ?>
                                        <select id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" data-autofill="marital_status" required <?= $isSelected ? '' : 'disabled' ?>><option value="">Pilih status</option><option value="BELUM MENIKAH">Belum Menikah</option><option value="MENIKAH">Menikah</option><option value="CERAI">Cerai</option></select>
                                    <?php elseif ($question['answer_type'] === 'choice'): ?>
                                        <?php $answerOptions = json_decode((string) ($question['answer_options'] ?? ''), true); $answerOptions = is_array($answerOptions) ? $answerOptions : []; ?>
                                        <select id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" <?= (int) $question['is_required'] === 1 ? 'required' : '' ?> <?= $isSelected ? '' : 'disabled' ?>><option value="">Pilih jawaban</option><?php foreach ($answerOptions as $option): ?><option value="<?= esc((string) $option, 'attr') ?>" <?= $oldAnswer === (string) $option ? 'selected' : '' ?>><?= esc((string) $option) ?></option><?php endforeach ?></select>
                                    <?php else: ?>
                                        <input id="screening-<?= (int) $question['id'] ?>" name="<?= esc($fieldName, 'attr') ?>" type="<?= $question['answer_type'] === 'number' ? 'number' : 'text' ?>" value="<?= esc($oldAnswer, 'attr') ?>" <?= $question['answer_type'] === 'text' ? 'maxlength="255"' : '' ?> <?= $question['question_code'] === 'age' ? 'data-autofill="age" readonly' : '' ?> <?= (int) $question['is_required'] === 1 ? 'required' : '' ?> <?= $isSelected ? '' : 'disabled' ?>>
                                    <?php endif ?>
                                </div>
                            <?php endforeach ?>
                        </section>
                    <?php endforeach ?>
                </div>
            </section>

            <section class="wizard-panel" data-step="6" aria-labelledby="step-title-6" hidden>
                <div class="panel-heading"><div><span class="panel-eyebrow">Langkah 6 dari 8</span><h2 id="step-title-6">Motivasi</h2><p>Bantu kami memahami alasan dan tujuan kariermu.</p></div></div>
                <div class="form-stack">
                    <label class="field"><span>MOTIVASI BEKERJA DAN ALASAN INGIN BERGABUNG DENGAN MANNA KAMPUS <b>*</b></span><textarea name="work_motivation" rows="7" minlength="20" maxlength="5000" required><?= esc(old('work_motivation')) ?></textarea></label>
                    <label class="field"><span>TARGET/IMPIAN YANG AKAN DICAPAI <b>*</b></span><textarea name="career_goal" rows="7" minlength="20" maxlength="5000" required><?= esc(old('career_goal')) ?></textarea></label>
                </div>
            </section>

            <section class="wizard-panel" data-step="7" aria-labelledby="step-title-7" hidden>
                <div class="panel-heading"><div><span class="panel-eyebrow">Langkah 7 dari 8</span><h2 id="step-title-7">Dokumen Pendukung</h2><p>Dokumen disimpan di area privat dan tidak dapat diakses langsung dari internet.</p></div></div>
                <div class="document-grid">
                    <label class="document-upload"><span>PDF</span><strong>SILAKAN POSTING BERKAS DALAM 1 FILE PDF <b>*</b></strong><em>SURAT LAMARAN, CV, KTP, KK, IJAZAH, TRANSKRIP NILAI, SERTIF VAKSIN, PAS FOTO BERWARNA, SERTIF SECURITY BAGI PELAMAR SECURITY. Maksimal 10 MB.</em><input type="file" name="application_bundle" accept=".pdf,application/pdf" required><small>Pilih Berkas PDF</small></label>
                </div>
            </section>

            <section class="wizard-panel" data-step="8" aria-labelledby="step-title-8" hidden>
                <div class="panel-heading"><div><span class="panel-eyebrow">Langkah 8 dari 8</span><h2 id="step-title-8">Tinjau &amp; Kirim</h2><p>Pastikan data yang Anda berikan benar sebelum mengirim lamaran.</p></div></div>
                <div class="review-card" id="application-review"></div>
                <label class="consent-field">
                    <input type="checkbox" name="privacy_consent" value="1" <?= old('privacy_consent') === '1' ? 'checked' : '' ?> required>
                    <span>Saya menyatakan data yang diberikan benar dan menyetujui pemrosesan data pribadi untuk keperluan rekrutmen Manna Kampus. <b>*</b></span>
                </label>
            </section>

            <footer class="wizard-actions">
                <button class="button-secondary" type="button" data-previous hidden>← Kembali</button>
                <span>Kolom bertanda <b>*</b> wajib diisi</span>
                <button class="button-primary" type="button" data-next>Lanjutkan →</button>
                <button class="button-primary" type="submit" data-submit hidden>Kirim Lamaran →</button>
            </footer>
        </form>
    </main>

    <footer class="application-footer">
        <div class="application-footer-inner">
            <div>
                <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
                <p>Ruang untuk belajar, bertumbuh, dan memberi dampak.</p>
            </div>
            <nav aria-label="Navigasi footer formulir">
                <a href="<?= site_url('lowongan') ?>">Lowongan</a>
                <a href="<?= site_url('tahapan-seleksi') ?>">Tahapan Seleksi</a>
                <a href="<?= site_url('lamaran/status') ?>">Cek Status</a>
                <a href="<?= base_url() ?>#faq">FAQ</a>
            </nav>
            <span>© <?= date('Y') ?> Manna Kampus. Data Anda diproses khusus untuk kebutuhan rekrutmen.</span>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/application.js') ?>?v=9" defer></script>
</body>
</html>
