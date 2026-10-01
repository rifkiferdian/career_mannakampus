<?php

namespace App\Modules\Recruitment\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Throwable;

class ApplicationReceiptPdf
{
    /** @param array<string, mixed> $receipt */
    public function generate(array $receipt): string
    {
        $profile = (array) ($receipt['profile'] ?? []);
        $applications = (array) ($receipt['applications'] ?? []);
        $batchNumber = (string) ($receipt['batch_number'] ?? '-');
        $submittedAt = $this->indonesianDate((string) ($receipt['submitted_at'] ?? ''));
        $statusUrl = trim((string) ($receipt['status_url'] ?? ''));
        if ($statusUrl === '' && function_exists('site_url')) {
            $statusUrl = site_url('lamaran/status');
        }
        $logoJpeg = $this->logoAsJpeg();
        $qrJpeg = $statusUrl === '' ? null : $this->qrCodeAsJpeg($statusUrl);

        $commands = [
            // Warm neutral page with the Manna Kampus orange accent.
            $this->fillRect(0, 0, 612, 792, '0.984 0.980 0.969'),
            $this->fillRect(0, 784, 612, 8, '0.973 0.463 0.220'),

            // Brand and document identity.
            $logoJpeg === null
                ? $this->text(48, 747, 14, 'MANNA KAMPUS', true, '0.851 0.337 0.114')
                : $this->image(48, 731, 150, 26, 'Logo'),
            $this->fillRect(350, 731, 100, 28, '0.902 0.961 0.925'),
            $this->text(369, 741, 10, 'TERKIRIM', true, '0.075 0.451 0.200'),
            $qrJpeg === null ? '' : $this->image(484, 690, 72, 72, 'Qr'),
            $qrJpeg === null ? '' : $this->text(489, 676, 7, 'SCAN CEK STATUS', true, '0.43 0.45 0.44'),
            $this->text(48, 696, 25, 'Bukti Lamaran', true, '0.105 0.125 0.118'),
            $this->text(48, 676, 10, 'Dokumen konfirmasi pengajuan lamaran kerja Anda.'),

            // Submission reference card.
            $this->fillRect(48, 602, 516, 54, '1 1 1'),
            $this->strokeRect(48, 602, 516, 54, '0.875 0.855 0.824'),
            $this->text(64, 635, 8, 'NOMOR PENGAJUAN', true, '0.43 0.45 0.44'),
            $this->text(64, 615, 15, $batchNumber, true, '0.851 0.337 0.114', 34),
            $this->text(374, 635, 8, 'TANGGAL DIKIRIM', true, '0.43 0.45 0.44'),
            $this->text(374, 616, 10, $submittedAt, true, '0.105 0.125 0.118', 28),

            $this->sectionHeading(48, 572, 'DATA PELAMAR'),
            $this->fillRect(48, 416, 516, 136, '1 1 1'),
            $this->strokeRect(48, 416, 516, 136, '0.875 0.855 0.824'),
            $this->line(306, 432, 306, 536, '0.91 0.90 0.88'),
        ];

        $leftProfileRows = [
            ['Nama Lengkap', $profile['full_name'] ?? '-'],
            ['Email', $profile['email'] ?? '-'],
            ['Nomor WhatsApp', $profile['phone'] ?? '-'],
        ];
        $rightProfileRows = [
            ['Tempat, Tanggal Lahir', trim((string) ($profile['birth_place'] ?? '-')) . ', ' . $this->indonesianDate((string) ($profile['birth_date'] ?? ''))],
            ['Pendidikan Terakhir', $profile['last_education'] ?? '-'],
            ['Institusi / Jurusan', trim((string) ($profile['institution'] ?? '-')) . ' / ' . trim((string) ($profile['major'] ?? '-'))],
        ];

        $profileY = 526;
        foreach ($leftProfileRows as [$label, $value]) {
            $commands[] = $this->text(64, $profileY, 8, (string) $label, true, '0.43 0.45 0.44');
            $commands[] = $this->text(64, $profileY - 15, 10, (string) $value, true, '0.105 0.125 0.118', 39);
            $profileY -= 39;
        }

        $profileY = 526;
        foreach ($rightProfileRows as [$label, $value]) {
            $commands[] = $this->text(322, $profileY, 8, (string) $label, true, '0.43 0.45 0.44');
            $commands[] = $this->text(322, $profileY - 15, 9, (string) $value, true, '0.105 0.125 0.118', 46);
            $profileY -= 39;
        }

        $commands[] = $this->sectionHeading(48, 386, 'POSISI YANG DILAMAR');
        $applicationY = 333;

        foreach ($applications as $application) {
            $priority = max(1, (int) ($application['preference_order'] ?? 1));
            $commands[] = $this->fillRect(48, $applicationY, 516, 43, '1 1 1');
            $commands[] = $this->strokeRect(48, $applicationY, 516, 43, '0.875 0.855 0.824');
            $commands[] = $this->fillRect(60, $applicationY + 9, 26, 26, '1.000 0.933 0.894');
            $commands[] = $this->text(69, $applicationY + 18, 10, (string) $priority, true, '0.851 0.337 0.114', 2);
            $commands[] = $this->text(98, $applicationY + 24, 11, (string) ($application['title'] ?? '-'), true, '0.105 0.125 0.118', 48);
            $commands[] = $this->text(98, $applicationY + 10, 8, 'Prioritas ' . $priority, false, '0.43 0.45 0.44');
            $commands[] = $this->text(405, $applicationY + 27, 7, 'NOMOR LAMARAN', true, '0.43 0.45 0.44');
            $commands[] = $this->text(405, $applicationY + 12, 9, (string) ($application['application_number'] ?? '-'), true, '0.851 0.337 0.114', 27);
            $applicationY -= 52;
        }

        $commands[] = $this->sectionHeading(48, 218, 'LANGKAH BERIKUTNYA');
        $commands[] = $this->fillRect(48, 91, 516, 107, '1 0.973 0.953');
        $commands[] = $this->strokeRect(48, 91, 516, 107, '0.953 0.753 0.624');
        $commands[] = $this->step(64, 170, '1', 'Simpan bukti ini', 'Gunakan sebagai referensi jika perlu menghubungi tim HRD.');
        $commands[] = $this->step(64, 138, '2', 'Pantau status lamaran', 'Buka menu Cek Status menggunakan nomor lamaran yang tercantum.');
        $commands[] = $this->step(64, 106, '3', 'Periksa WhatsApp dan email', 'Informasi seleksi akan dikirim melalui kontak yang Anda daftarkan.');

        $commands[] = $this->line(48, 66, 564, 66, '0.86 0.85 0.82');
        $commands[] = $this->text(48, 47, 8, 'Dokumen ini dibuat otomatis oleh Sistem Rekrutmen Manna Kampus.');
        $commands[] = $this->text(397, 47, 8, 'Simpan untuk referensi Anda.', true, '0.43 0.45 0.44');

        return $this->buildPdf(implode("\n", $commands) . "\n", $logoJpeg, $qrJpeg);
    }

    private function text(
        int $x,
        int $y,
        int $size,
        string $value,
        bool $bold = false,
        string $color = '0.16 0.19 0.18',
        ?int $maximumLength = null,
    ): string {
        $maximumLength ??= $x >= 180 ? 62 : 85;
        $value = mb_strimwidth($value, 0, $maximumLength, '...');
        $encoded = function_exists('iconv') ? iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) : $value;
        $encoded = $encoded === false ? $value : $encoded;
        $encoded = preg_replace('/[\x00-\x1F\x7F]/', ' ', $encoded) ?? '';
        $encoded = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded);

        return "BT {$color} rg /" . ($bold ? 'F2' : 'F1') . " {$size} Tf 1 0 0 1 {$x} {$y} Tm ({$encoded}) Tj ET";
    }

    private function fillRect(int $x, int $y, int $width, int $height, string $color): string
    {
        return "{$color} rg {$x} {$y} {$width} {$height} re f";
    }

    private function strokeRect(int $x, int $y, int $width, int $height, string $color): string
    {
        return "{$color} RG 0.7 w {$x} {$y} {$width} {$height} re S";
    }

    private function line(int $x1, int $y1, int $x2, int $y2, string $color): string
    {
        return "{$color} RG 0.7 w {$x1} {$y1} m {$x2} {$y2} l S";
    }

    private function sectionHeading(int $x, int $y, string $label): string
    {
        return $this->text($x, $y, 10, $label, true, '0.851 0.337 0.114');
    }

    private function step(int $x, int $y, string $number, string $title, string $description): string
    {
        return implode("\n", [
            $this->fillRect($x, $y - 2, 21, 21, '0.973 0.463 0.220'),
            $this->text($x + 7, $y + 5, 9, $number, true, '1 1 1', 2),
            $this->text($x + 32, $y + 9, 9, $title, true, '0.105 0.125 0.118', 35),
            $this->text($x + 32, $y - 4, 8, $description, false, '0.38 0.40 0.39', 72),
        ]);
    }

    private function image(int $x, int $y, int $width, int $height, string $name): string
    {
        return "q {$width} 0 0 {$height} {$x} {$y} cm /{$name} Do Q";
    }

    private function logoAsJpeg(): ?string
    {
        if (! function_exists('imagecreatefrompng') || ! function_exists('imagejpeg')) {
            return null;
        }

        $publicPath = defined('FCPATH')
            ? rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR
            : dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $logoPath = $publicPath . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'Logo_Manna_Kampus.png';
        if (! is_file($logoPath)) {
            return null;
        }

        $source = @imagecreatefrompng($logoPath);
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

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagealphablending($canvas, true);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        $encoded = imagejpeg($canvas, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($canvas);
        imagedestroy($source);

        return $encoded && is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    /** @return array{data: string, width: int, height: int}|null */
    private function qrCodeAsJpeg(string $data): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(320)
                ->margin(16)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->foregroundColor(new Color(25, 30, 28))
                ->backgroundColor(new Color(255, 255, 255))
                ->validateResult(false)
                ->build();

            $source = imagecreatefromstring($result->getString());
            if ($source === false) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            ob_start();
            $encoded = imagejpeg($source, null, 100);
            $jpeg = ob_get_clean();
            imagedestroy($source);

            if (! $encoded || ! is_string($jpeg) || $jpeg === '') {
                return null;
            }

            return ['data' => $jpeg, 'width' => $width, 'height' => $height];
        } catch (Throwable) {
            return null;
        }
    }

    private function indonesianDate(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return '-';
        }

        $date = null;
        foreach (['!d/m/Y H:i', '!d/m/Y', '!Y-m-d H:i:s', '!Y-m-d'] as $format) {
            $candidate = \DateTimeImmutable::createFromFormat($format, $value);
            if ($candidate !== false) {
                $date = $candidate;
                break;
            }
        }

        if ($date === null) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return $value;
            }
            $date = (new \DateTimeImmutable())->setTimestamp($timestamp);
        }

        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $date->format('d') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    /** @param array{data: string, width: int, height: int}|null $qrJpeg */
    private function buildPdf(string $stream, ?string $logoJpeg = null, ?array $qrJpeg = null): string
    {
        $imageReferences = [];
        $imageObjects = [];
        $nextObjectNumber = 7;
        if ($logoJpeg !== null) {
            $imageReferences[] = '/Logo ' . $nextObjectNumber . ' 0 R';
            $imageObjects[] = $this->imageObject($logoJpeg, 568, 100);
            $nextObjectNumber++;
        }
        if ($qrJpeg !== null) {
            $imageReferences[] = '/Qr ' . $nextObjectNumber . ' 0 R';
            $imageObjects[] = $this->imageObject($qrJpeg['data'], $qrJpeg['width'], $qrJpeg['height']);
        }
        $imageResources = $imageReferences === []
            ? ''
            : ' /XObject << ' . implode(' ', $imageReferences) . ' >>';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >>' . $imageResources . ' >> >>',
            "<< /Length " . strlen($stream) . ">>\nstream\n{$stream}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        array_push($objects, ...$imageObjects);
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

    private function imageObject(string $jpeg, int $width, int $height): string
    {
        return "<< /Type /XObject /Subtype /Image /Width {$width} /Height {$height} /ColorSpace /DeviceRGB"
            . ' /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($jpeg) . " >>\nstream\n"
            . $jpeg . "\nendstream";
    }
}
