<?php

namespace App\Modules\Admin\Services;

class CandidateScorecardPdf
{
    /** @var list<list<string>> */
    private array $pages = [];
    /** @var list<string> */
    private array $commands = [];
    private float $y = 757;
    /** @var array{data:string,width:int,height:int}|null */
    private ?array $logo = null;

    /** @param array<string, mixed> $data */
    public function generate(array $data): string
    {
        $this->pages = [];
        $this->commands = [];
        $this->y = 757;
        $this->logo = $this->loadLogo((string) ($data['logo_path'] ?? ''));
        $applicant = (array) ($data['applicant'] ?? []);
        $recommendation = is_array($data['recommendation'] ?? null) ? $data['recommendation'] : null;
        $recommendationLabels = ['continue' => 'LANJUT', 'hold' => 'TAHAN', 'reject' => 'TOLAK'];

        $this->text(42, $this->y, 20, 'SCORECARD KANDIDAT', true, '0.949 0.420 0.086');
        $this->y -= 22;
        $this->text(42, $this->y, 10, 'Penilaian, rekomendasi, dan biodata pelamar', false, '0.33 0.42 0.48');
        $this->y -= 29;
        $this->box(42, $this->y - 48, 511, 55, '1 0.965 0.925');
        $this->text(56, $this->y - 11, 9, 'KANDIDAT', false, '0.60 0.34 0.16');
        $this->text(56, $this->y - 30, 14, (string) ($applicant['full_name'] ?? '-'), true, '0.38 0.20 0.10');
        $decision = $recommendation === null ? 'BELUM DINILAI' : ($recommendationLabels[(string) ($recommendation['recommendation'] ?? '')] ?? 'BELUM DINILAI');
        $this->text(390, $this->y - 11, 9, 'REKOMENDASI AKHIR', false, '0.35 0.46 0.40');
        $this->text(390, $this->y - 30, 13, $decision, true, $decision === 'TOLAK' ? '0.70 0.18 0.18' : ($decision === 'TAHAN' ? '0.70 0.42 0.08' : '0.10 0.43 0.29'));
        $this->y -= 72;

        $this->section('BIODATA PELAMAR');
        $birth = trim((string) ($applicant['birth_place'] ?? '')) . (($applicant['birth_date'] ?? '') !== '' ? ', ' . $this->date((string) $applicant['birth_date'], false) : '');
        $this->keyValue('Nama lengkap', (string) ($applicant['full_name'] ?? '-'));
        $this->keyValue('Email', (string) ($applicant['email'] ?? '-'));
        $this->keyValue('Telepon / WhatsApp', (string) ($applicant['phone'] ?? '-'));
        $this->keyValue('Tempat, tanggal lahir', trim($birth, ', ') ?: '-');
        $this->keyValue('Jenis kelamin', (string) ($applicant['gender'] ?? '-'));
        $this->keyValue('Status pernikahan', (string) ($applicant['marital_status'] ?? '-'));
        $this->keyValue('Agama', (string) ($applicant['religion'] ?? '-'));
        $this->keyValue('Tinggi badan', ! empty($applicant['height_cm']) ? (string) $applicant['height_cm'] . ' cm' : '-');
        $this->keyValue('Alamat', (string) ($applicant['address'] ?? '-'));
        $this->keyValue('Divisi HRD', (string) ($applicant['assigned_hrd_team_name'] ?? 'Belum dipilih'));

        $this->section('PENDIDIKAN & PELATIHAN');
        $this->keyValue('Pendidikan terakhir', (string) ($applicant['last_education'] ?? '-'));
        $this->keyValue('Institusi', (string) ($applicant['institution'] ?? '-'));
        $this->keyValue('Jurusan', (string) ($applicant['major'] ?? '-'));
        $isSchoolGrade = in_array((string) ($applicant['last_education'] ?? ''), ['SMP', 'SMA/SMK'], true);
        $gradeValue = (string) ($applicant['gpa'] ?? '-');
        if ($isSchoolGrade && is_numeric($gradeValue)) {
            $gradeValue .= (float) $gradeValue <= 10 ? ' / 10' : ' / 100';
        } elseif (! $isSchoolGrade && is_numeric($gradeValue)) {
            $gradeValue .= ' / 4';
        }
        $this->keyValue($isSchoolGrade ? 'Nilai akhir' : 'IPK', $gradeValue);
        $this->keyValue('Pelatihan / sertifikasi', (string) ($applicant['training_experience'] ?? '-'));

        $experiences = (array) ($data['experiences'] ?? []);
        $this->section('PENGALAMAN KERJA');
        if ($experiences === []) {
            $this->paragraph('Belum ada pengalaman kerja terstruktur yang dicatat.', true);
        } else {
            foreach ($experiences as $index => $experience) {
                $experience = (array) $experience;
                $period = (string) ($experience['start_year'] ?? '-') . ' - ' . ((string) ($experience['end_year'] ?? '') !== '' ? (string) $experience['end_year'] : 'Sekarang');
                $this->subheading(($index + 1) . '. ' . (string) ($experience['position_title'] ?? 'Posisi') . ' - ' . (string) ($experience['company_name'] ?? '-'));
                $this->keyValue('Periode', $period);
                $this->keyValue('Tanggung jawab', (string) ($experience['responsibilities'] ?? '-'));
            }
        }

        $applications = (array) ($data['applications'] ?? []);
        $answersByApplication = (array) ($data['answers_by_application'] ?? []);
        $this->section('RIWAYAT LAMARAN & SCREENING');
        if ($applications === []) {
            $this->paragraph('Belum ada data lamaran.', true);
        }
        foreach ($applications as $index => $application) {
            $application = (array) $application;
            $applicationId = (int) ($application['id'] ?? 0);
            $this->subheading(($index + 1) . '. ' . (string) ($application['vacancy_title'] ?? '-') . ' / ' . (string) ($application['department_name'] ?? '-'));
            $this->keyValue('Nomor lamaran', (string) ($application['application_number'] ?? '-'));
            $this->keyValue('Sesi lowongan', (string) ($application['period_name'] ?? '-'));
            $this->keyValue('Tahap saat ini', (string) ($application['status_label'] ?? $application['application_status'] ?? '-'));
            $this->keyValue('Tanggal melamar', $this->date((string) ($application['submitted_at'] ?? '')));
            $this->keyValue('Hasil screening', (string) ($application['screening_label'] ?? '-') . (! empty($application['screening_score']) ? ' · Nilai ' . $application['screening_score'] : ''));
            $this->keyValue('Catatan screening', (string) ($application['screening_notes'] ?? '-'));
            $this->keyValue('Pengalaman ringkas', (string) ($application['work_experience'] ?? '-'));
            $this->keyValue('Motivasi kerja', (string) ($application['work_motivation'] ?? '-'));
            $this->keyValue('Tujuan karier', (string) ($application['career_goal'] ?? '-'));
            $answers = (array) ($answersByApplication[$applicationId] ?? []);
            if ($answers !== []) {
                $this->minorHeading('Jawaban screening');
                foreach ($answers as $answer) {
                    $answer = (array) $answer;
                    $answerValue = (string) ($answer['answer_value'] ?? '-');
                    if ((string) ($answer['answer_type'] ?? '') === 'boolean') {
                        $answerValue = in_array(mb_strtolower($answerValue), ['1', 'true', 'yes', 'ya'], true) ? 'Ya' : 'Tidak';
                    }
                    $this->keyValue((string) ($answer['question_text'] ?? 'Pertanyaan'), $answerValue);
                }
            }
        }

        $this->section('PENILAIAN & REKOMENDASI');
        if ($recommendation === null) {
            $this->paragraph('Scorecard belum diisi oleh tim HRD.', true);
        } else {
            $aspects = (array) ($data['aspects'] ?? []);
            $scores = [];
            foreach ($aspects as $aspect) {
                $aspect = (array) $aspect;
                $answer = trim((string) ($aspect['answer_value'] ?? ''));
                if ((string) ($aspect['input_type'] ?? '') === 'scale_1_5' && ctype_digit($answer)) {
                    $scores[] = (int) $answer;
                    $this->rating((string) ($aspect['name'] ?? 'Aspek'), (float) $answer, $answer . ' / 5');
                    continue;
                }
                $this->keyValue((string) ($aspect['name'] ?? 'Aspek'), $answer !== '' ? $answer : 'Belum diisi');
            }
            $average = $scores === [] ? '-' : number_format(array_sum($scores) / count($scores), 1, ',', '.') . ' / 5';
            if ($scores === []) {
                $this->keyValue('Rata-rata nilai', $average);
            } else {
                $this->rating('Rata-rata nilai', array_sum($scores) / count($scores), $average);
            }
            $this->keyValue('Rekomendasi akhir', $recommendationLabels[(string) ($recommendation['recommendation'] ?? '')] ?? '-');
            $this->keyValue('Catatan HRD', (string) ($recommendation['notes'] ?? '-'));
            $this->keyValue('Dinilai / diperbarui oleh', (string) ($recommendation['updated_by_name'] ?? '-'));
            $this->keyValue('Terakhir diperbarui', $this->date((string) ($recommendation['updated_at'] ?? '')));
        }

        $this->section('PENGESAHAN');
        $this->paragraph('Dokumen ini merupakan ringkasan internal proses rekrutmen dan bersifat rahasia.', true);
        $this->ensure(74);
        $signatureY = $this->y - 52;
        $this->line(55, $signatureY, 235, $signatureY, '0.65 0.70 0.73');
        $this->line(360, $signatureY, 540, $signatureY, '0.65 0.70 0.73');
        $this->text(55, $signatureY - 15, 8, 'Recruiter / HRD');
        $this->text(360, $signatureY - 15, 8, 'Atasan / User');
        $this->y -= 78;
        $this->finishPage();

        return $this->buildPdf((string) ($data['printed_by'] ?? '-'), (string) ($data['printed_at'] ?? date('d/m/Y H:i')));
    }

    private function section(string $title): void
    {
        $this->ensure(36);
        $this->y -= 9;
        $this->box(42, $this->y - 17, 511, 24, '0.949 0.420 0.086');
        $this->text(52, $this->y - 10, 10, $title, true, '1 1 1');
        $this->y -= 34;
    }

    private function subheading(string $value): void
    {
        $lines = $this->wrap($value, 83);
        $this->ensure(count($lines) * 13 + 13);
        foreach ($lines as $line) {
            $this->text(48, $this->y, 10, $line, true, '0.68 0.29 0.07');
            $this->y -= 13;
        }
        $this->y -= 5;
    }

    private function minorHeading(string $value): void
    {
        $this->ensure(24);
        $this->text(55, $this->y, 9, $value, true, '0.78 0.34 0.07');
        $this->y -= 18;
    }

    private function keyValue(string $label, string $value): void
    {
        $value = trim($value) !== '' ? trim($value) : '-';
        $labelLines = $this->wrap($label, 25);
        $valueLines = $this->wrap($value, 67);
        $continuation = false;
        while ($labelLines !== [] || $valueLines !== []) {
            $this->ensure(21);
            $capacity = max(1, (int) floor(($this->y - 64) / 13));
            $lineCount = min(max(count($labelLines), count($valueLines)), $capacity);
            $labelChunk = array_splice($labelLines, 0, $lineCount);
            $valueChunk = array_splice($valueLines, 0, $lineCount);
            if ($continuation && $labelChunk === []) {
                $labelChunk = $this->wrap($label . ' (lanjutan)', 25);
            }
            foreach ($labelChunk as $index => $line) {
                $this->text(52, $this->y - ($index * 13), 8, $line, false, '0.38 0.45 0.49');
            }
            foreach ($valueChunk as $index => $line) {
                $this->text(190, $this->y - ($index * 13), 9, $line, ! $continuation && $index === 0, '0.13 0.19 0.22');
            }
            $height = max(count($labelChunk), count($valueChunk)) * 13 + 7;
            $this->y -= $height;
            $this->line(52, $this->y + 3, 543, $this->y + 3, '0.91 0.93 0.94');
            if ($labelLines !== [] || $valueLines !== []) {
                $this->finishPage();
                $this->commands = [];
                $this->y = 757;
                $continuation = true;
            }
        }
    }

    private function rating(string $label, float $score, string $display): void
    {
        $labelLines = $this->wrap($label, 25);
        $height = max(24, count($labelLines) * 13 + 7);
        $this->ensure($height);
        foreach ($labelLines as $index => $line) {
            $this->text(52, $this->y - ($index * 13), 8, $line, false, '0.38 0.45 0.49');
        }
        $filledStars = max(0, min(5, (int) round($score)));
        for ($star = 1; $star <= 5; $star++) {
            $this->star(197 + (($star - 1) * 17), $this->y + 2, $star <= $filledStars);
        }
        $this->text(291, $this->y - 2, 9, $display, true, '0.68 0.29 0.07');
        $this->y -= $height;
        $this->line(52, $this->y + 3, 543, $this->y + 3, '0.94 0.88 0.83');
    }

    private function paragraph(string $value, bool $muted = false): void
    {
        $lines = $this->wrap($value, 88);
        while ($lines !== []) {
            $this->ensure(22);
            $capacity = max(1, (int) floor(($this->y - 65) / 14));
            $chunk = array_splice($lines, 0, $capacity);
            foreach ($chunk as $line) {
                $this->text(52, $this->y, 9, $line, false, $muted ? '0.42 0.48 0.51' : '0.15 0.20 0.23');
                $this->y -= 14;
            }
            $this->y -= 8;
            if ($lines !== []) {
                $this->finishPage();
                $this->commands = [];
                $this->y = 757;
            }
        }
    }

    private function ensure(float $height): void
    {
        if ($this->y - $height >= 57) {
            return;
        }
        $this->finishPage();
        $this->commands = [];
        $this->y = 757;
    }

    private function finishPage(): void
    {
        if ($this->commands !== []) {
            $this->pages[] = $this->commands;
        }
    }

    /** @return list<string> */
    private function wrap(string $value, int $maximumCharacters): array
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if ($value === '') {
            return ['-'];
        }
        $words = preg_split('/\s+/u', $value) ?: [$value];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            while (mb_strlen($word) > $maximumCharacters) {
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                $lines[] = mb_substr($word, 0, $maximumCharacters);
                $word = mb_substr($word, $maximumCharacters);
            }
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if (mb_strlen($candidate) > $maximumCharacters) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: ['-'];
    }

    private function text(float $x, float $y, int $size, string $value, bool $bold = false, string $color = '0.16 0.19 0.18'): void
    {
        $encoded = $this->encode($value);
        $this->commands[] = "BT {$color} rg /" . ($bold ? 'F2' : 'F1') . " {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$encoded}) Tj ET";
    }

    private function box(float $x, float $y, float $width, float $height, string $color): void
    {
        $this->commands[] = "{$color} rg {$x} {$y} {$width} {$height} re f";
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $color): void
    {
        $this->commands[] = "{$color} RG {$x1} {$y1} m {$x2} {$y2} l S";
    }

    private function star(float $centerX, float $centerY, bool $filled): void
    {
        $points = [];
        for ($point = 0; $point < 10; $point++) {
            $radius = $point % 2 === 0 ? 6.0 : 2.6;
            $angle = deg2rad(-90 + ($point * 36));
            $points[] = [$centerX + cos($angle) * $radius, $centerY + sin($angle) * $radius];
        }
        $path = sprintf('%.2F %.2F m', $points[0][0], $points[0][1]);
        foreach (array_slice($points, 1) as $point) {
            $path .= sprintf(' %.2F %.2F l', $point[0], $point[1]);
        }
        $path .= ' h';
        $this->commands[] = $filled
            ? '0.949 0.420 0.086 rg ' . $path . ' f'
            : '0.78 0.80 0.81 RG 0.8 w ' . $path . ' S';
    }

    private function encode(string $value): string
    {
        $encoded = function_exists('iconv') ? iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) : $value;
        $encoded = $encoded === false ? $value : $encoded;
        $encoded = preg_replace('/[\x00-\x1F\x7F]/', ' ', $encoded) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    private function date(string $value, bool $withTime = true): string
    {
        $timestamp = strtotime($value);

        return $value !== '' && $timestamp !== false ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp) : '-';
    }

    /** @return array{data:string,width:int,height:int}|null */
    private function loadLogo(string $path): ?array
    {
        if ($path === '' || ! is_file($path) || ! extension_loaded('gd')) {
            return null;
        }
        $sourceData = file_get_contents($path);
        $source = $sourceData === false ? false : @imagecreatefromstring($sourceData);
        if ($source === false) {
            return null;
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            imagedestroy($source);
            return null;
        }
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);
        ob_start();
        imagejpeg($canvas, null, 92);
        $jpeg = ob_get_clean();
        imagedestroy($canvas);
        imagedestroy($source);

        return is_string($jpeg) && $jpeg !== '' ? ['data' => $jpeg, 'width' => $width, 'height' => $height] : null;
    }

    private function buildPdf(string $printedBy, string $printedAt): string
    {
        $pageCount = count($this->pages);
        $pageObjectStart = 3;
        $contentObjectStart = $pageObjectStart + $pageCount;
        $fontRegularObject = $contentObjectStart + $pageCount;
        $fontBoldObject = $fontRegularObject + 1;
        $logoObject = $this->logo !== null ? $fontBoldObject + 1 : null;
        $kids = [];
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
        ];
        for ($index = 0; $index < $pageCount; $index++) {
            $pageObject = $pageObjectStart + $index;
            $contentObject = $contentObjectStart + $index;
            $kids[] = $pageObject . ' 0 R';
            $xObject = $logoObject !== null ? " /XObject << /Logo {$logoObject} 0 R >>" : '';
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents {$contentObject} 0 R /Resources << /Font << /F1 {$fontRegularObject} 0 R /F2 {$fontBoldObject} 0 R >>{$xObject} >> >>";
        }
        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';
        foreach ($this->pages as $index => $commands) {
            $header = ['1 1 1 rg 0 797 595 45 re f', '0.949 0.420 0.086 rg 0 797 595 5 re f'];
            if ($this->logo !== null) {
                $header[] = 'q 150 0 0 26.41 42 807 cm /Logo Do Q';
            } else {
                $header[] = 'BT 0.949 0.420 0.086 rg /F2 12 Tf 1 0 0 1 42 817 Tm (MANNA KAMPUS) Tj ET';
            }
            $header[] = 'BT 0.68 0.29 0.07 rg /F2 8 Tf 1 0 0 1 470 817 Tm (REKRUTMEN) Tj ET';
            array_unshift($commands, ...$header);
            $commands[] = '0.949 0.420 0.086 RG 42 38 m 553 38 l S';
            $commands[] = 'BT 0.40 0.46 0.49 rg /F1 7 Tf 1 0 0 1 42 24 Tm (' . $this->encode('Dicetak oleh ' . $printedBy . ' pada ' . $printedAt . ' WIB') . ') Tj ET';
            $commands[] = 'BT 0.40 0.46 0.49 rg /F1 7 Tf 1 0 0 1 490 24 Tm (' . $this->encode('Hal. ' . ($index + 1) . '/' . $pageCount) . ') Tj ET';
            $stream = implode("\n", $commands) . "\n";
            $objects[] = "<< /Length " . strlen($stream) . ">>\nstream\n{$stream}endstream";
        }
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        if ($this->logo !== null) {
            $objects[] = '<< /Type /XObject /Subtype /Image /Width ' . $this->logo['width'] . ' /Height ' . $this->logo['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($this->logo['data']) . ">>\nstream\n" . $this->logo['data'] . "\nendstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }
}
