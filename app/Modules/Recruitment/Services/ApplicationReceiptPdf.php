<?php

namespace App\Modules\Recruitment\Services;

class ApplicationReceiptPdf
{
    /** @param array<string, mixed> $receipt */
    public function generate(array $receipt): string
    {
        $profile = (array) ($receipt['profile'] ?? []);
        $applications = (array) ($receipt['applications'] ?? []);
        $commands = [
            '0.071 0.184 0.145 rg 0 742 612 50 re f',
            $this->text(48, 760, 18, 'BUKTI PENGAJUAN LAMARAN', true, '1 1 1'),
            $this->text(48, 724, 10, 'Karier Manna Kampus', true),
            $this->text(48, 696, 9, 'Nomor Pengajuan'),
            $this->text(48, 676, 16, (string) ($receipt['batch_number'] ?? '-'), true, '0.714 0.290 0'),
            $this->text(330, 696, 9, 'Tanggal Pengajuan'),
            $this->text(330, 676, 11, (string) ($receipt['submitted_at'] ?? '-'), true),
            '0.88 0.90 0.89 RG 48 657 m 564 657 l S',
            $this->text(48, 630, 12, 'PROFIL PELAMAR', true, '0.071 0.184 0.145'),
        ];

        $profileRows = [
            ['Nama Lengkap', $profile['full_name'] ?? '-'],
            ['Email', $profile['email'] ?? '-'],
            ['Nomor WhatsApp', $profile['phone'] ?? '-'],
            ['Tempat, Tanggal Lahir', trim((string) ($profile['birth_place'] ?? '-')) . ', ' . (string) ($profile['birth_date'] ?? '-')],
            ['Pendidikan Terakhir', $profile['last_education'] ?? '-'],
            ['Institusi / Jurusan', trim((string) ($profile['institution'] ?? '-')) . ' / ' . trim((string) ($profile['major'] ?? '-'))],
        ];
        $y = 605;
        foreach ($profileRows as [$label, $value]) {
            $commands[] = $this->text(48, $y, 9, (string) $label);
            $commands[] = $this->text(180, $y, 10, (string) $value, true);
            $y -= 24;
        }

        $commands[] = '0.88 0.90 0.89 RG 48 ' . ($y + 7) . ' m 564 ' . ($y + 7) . ' l S';
        $commands[] = $this->text(48, $y - 18, 12, 'LAMARAN YANG DIKIRIM', true, '0.071 0.184 0.145');
        $y -= 46;

        foreach ($applications as $application) {
            $title = 'Prioritas ' . (int) ($application['preference_order'] ?? 0) . ' - ' . (string) ($application['title'] ?? '-');
            $commands[] = $this->text(58, $y, 10, $title, true);
            $commands[] = $this->text(58, $y - 17, 9, 'Nomor Lamaran: ' . (string) ($application['application_number'] ?? '-'), false, '0.714 0.290 0');
            $y -= 47;
        }

        $commands[] = '0.965 0.976 0.972 rg 48 73 516 58 re f';
        $commands[] = $this->text(62, 108, 10, 'PENTING: SIMPAN NOMOR PENGAJUAN DAN DOKUMEN INI.', true, '0.071 0.184 0.145');
        $commands[] = $this->text(62, 88, 9, 'Nomor tersebut diperlukan untuk mengecek status dan proses rekrutmen selanjutnya.');

        return $this->buildPdf(implode("\n", $commands) . "\n");
    }

    private function text(
        int $x,
        int $y,
        int $size,
        string $value,
        bool $bold = false,
        string $color = '0.16 0.19 0.18',
    ): string {
        $maximumLength = $x >= 180 ? 62 : 85;
        $value = mb_strimwidth($value, 0, $maximumLength, '...');
        $encoded = function_exists('iconv') ? iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) : $value;
        $encoded = $encoded === false ? $value : $encoded;
        $encoded = preg_replace('/[\x00-\x1F\x7F]/', ' ', $encoded) ?? '';
        $encoded = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded);

        return "BT {$color} rg /" . ($bold ? 'F2' : 'F1') . " {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$encoded}) Tj ET";
    }

    private function buildPdf(string $stream): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>',
            "<< /Length " . strlen($stream) . ">>\nstream\n{$stream}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . 'trailer' . "\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }
}
