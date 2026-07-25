-- ============================================================
-- Panduan menelusuri 1 pelamar contoh dari awal sampai akhir.
-- Data utama:
--   Email       : budi.santoso.contoh@example.test
--   Batch       : SAMPLE-BATCH-001
--   NIK contoh  : 3404071505980001
-- ============================================================

-- 1. Biodata pelamar terkini.
-- NIK asli tidak disimpan sebagai teks biasa: nik_hash untuk pencarian,
-- nik_encrypted untuk kebutuhan internal yang terotorisasi.
SELECT
    id,
    uuid,
    full_name,
    email,
    phone,
    birth_date,
    last_education,
    institution,
    major,
    nik_hash,
    nik_encrypted,
    created_at,
    updated_at
FROM applicants
WHERE email = 'budi.santoso.contoh@example.test';

-- 2. Satu kali submit membentuk satu batch.
-- applicant_snapshot menyimpan versi biodata saat lamaran dikirim,
-- sehingga riwayat lama tidak berubah saat biodata utama diperbarui.
SELECT
    b.id,
    b.batch_number,
    rg.name AS requirement_group,
    b.position_count,
    b.snapshot_version,
    JSON_UNQUOTE(JSON_EXTRACT(b.applicant_snapshot, '$.identity.full_name')) AS snapshot_name,
    JSON_UNQUOTE(JSON_EXTRACT(b.applicant_snapshot, '$.identity.nik_masked')) AS snapshot_nik,
    JSON_UNQUOTE(JSON_EXTRACT(b.applicant_snapshot, '$.education.level')) AS snapshot_education,
    b.submitted_at
FROM application_batches b
JOIN requirement_groups rg ON rg.id = b.requirement_group_id
WHERE b.batch_number = 'SAMPLE-BATCH-001';

-- 3. Posisi di dalam batch, urutan prioritas, dan hasil tiap screening.
SELECT
    b.batch_number,
    a.application_number,
    a.preference_order,
    v.title AS vacancy,
    d.name AS department,
    a.screening_score,
    a.screening_status,
    a.application_status,
    a.public_message
FROM applications a
JOIN application_batches b ON b.id = a.batch_id
JOIN vacancies v ON v.id = a.vacancy_id
JOIN departments d ON d.id = v.department_id
WHERE b.batch_number = 'SAMPLE-BATCH-001'
ORDER BY a.preference_order;

-- 4. Jawaban screening per posisi. Cari is_eligible = 0 untuk melihat
-- alasan UI/UX Designer tidak lolos.
SELECT
    a.application_number,
    v.title AS vacancy,
    q.question_code,
    q.question_text,
    ans.answer_value,
    q.expected_value,
    q.comparison_operator,
    q.is_knockout,
    ans.is_eligible,
    ans.score
FROM application_screening_answers ans
JOIN applications a ON a.id = ans.application_id
JOIN application_batches b ON b.id = a.batch_id
JOIN vacancies v ON v.id = a.vacancy_id
JOIN vacancy_screening_questions q ON q.id = ans.question_id
WHERE b.batch_number = 'SAMPLE-BATCH-001'
ORDER BY a.preference_order, q.display_order;

-- 5. Audit perubahan status setiap lamaran.
SELECT
    a.application_number,
    v.title AS vacancy,
    h.status_type,
    h.previous_status,
    h.new_status,
    h.notes,
    h.created_at
FROM application_status_histories h
JOIN applications a ON a.id = h.application_id
JOIN application_batches b ON b.id = a.batch_id
JOIN vacancies v ON v.id = a.vacancy_id
WHERE b.batch_number = 'SAMPLE-BATCH-001'
ORDER BY a.preference_order, h.created_at;

-- 6. Dokumen dimiliki batch, bukan diduplikasi ke setiap posisi.
SELECT
    b.batch_number,
    doc.document_type,
    doc.original_name,
    doc.file_path,
    doc.mime_type,
    doc.file_size,
    doc.created_at
FROM applicant_documents doc
JOIN application_batches b ON b.id = doc.batch_id
WHERE b.batch_number = 'SAMPLE-BATCH-001'
ORDER BY doc.document_type;

-- 7. Ringkasan hubungan utama dalam satu hasil.
SELECT
    p.full_name,
    b.batch_number,
    a.application_number,
    a.preference_order,
    v.title,
    a.screening_status,
    a.application_status,
    COUNT(DISTINCT ans.id) AS answer_count,
    SUM(ans.is_eligible = 0) AS ineligible_answer_count
FROM applicants p
JOIN application_batches b ON b.applicant_id = p.id
JOIN applications a ON a.batch_id = b.id
JOIN vacancies v ON v.id = a.vacancy_id
LEFT JOIN application_screening_answers ans ON ans.application_id = a.id
WHERE p.email = 'budi.santoso.contoh@example.test'
  AND b.batch_number = 'SAMPLE-BATCH-001'
GROUP BY
    p.id,
    b.id,
    a.id,
    v.id
ORDER BY a.preference_order;
