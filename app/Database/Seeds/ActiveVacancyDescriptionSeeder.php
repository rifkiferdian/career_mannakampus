<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ActiveVacancyDescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $content = [
            'programmer' => [
                'job_description' => 'Bertanggung jawab mengembangkan, memelihara, dan meningkatkan aplikasi serta sistem internal Manna Kampus. Posisi ini bekerja sama dengan tim Information Technology untuk menerjemahkan kebutuhan bisnis menjadi solusi perangkat lunak yang aman, stabil, dan mudah dikembangkan.',
                'responsibilities' => "Mengembangkan dan memelihara aplikasi sesuai kebutuhan bisnis.\nMenganalisis, memperbaiki, dan mencegah masalah pada aplikasi.\nMembuat kode yang bersih, aman, serta mudah dipelihara.\nMelakukan pengujian dan dokumentasi terhadap fitur yang dikembangkan.\nBerkolaborasi dengan tim Information Technology dan pengguna terkait.",
                'qualifications' => "Pendidikan minimal D3/S1 bidang Teknologi Informasi, Ilmu Komputer, atau bidang terkait.\nMemiliki pengalaman minimal 1 tahun pada bidang pengembangan perangkat lunak.\nMenguasai salah satu teknologi Golang, Java, PostgreSQL, Laravel, atau PHP.\nMemahami database, API, version control, dan dasar keamanan aplikasi.\nMampu bekerja mandiri maupun bersama tim serta memiliki kemauan belajar yang baik.",
            ],
            'pramuniaga' => [
                'job_description' => 'Memberikan pelayanan terbaik kepada pelanggan, membantu proses penjualan, menata dan memastikan ketersediaan produk, serta menjaga kebersihan dan kerapian area toko sesuai standar operasional Manna Kampus.',
                'responsibilities' => "Melayani pelanggan dengan ramah, cepat, dan sesuai standar pelayanan.\nMembantu pelanggan menemukan produk yang dibutuhkan.\nMenata, mengecek, dan menjaga ketersediaan barang pada area penjualan.\nMemastikan label harga dan informasi produk terpasang dengan benar.\nMenjaga kebersihan, keamanan, dan kerapian area toko.",
                'qualifications' => "Pendidikan minimal SMA/SMK atau sederajat.\nMemiliki kemampuan komunikasi dan pelayanan pelanggan yang baik.\nJujur, disiplin, teliti, dan bertanggung jawab.\nMampu bekerja dalam tim dan mengikuti target operasional.\nBersedia bekerja dengan sistem shift dan ditempatkan di cabang Manna Kampus Yogyakarta.",
            ],
        ];
        $now = date('Y-m-d H:i:s');
        $activeVacancies = $this->db->table('vacancies AS vacancies')
            ->select('DISTINCT vacancies.id, vacancies.code, vacancies.job_description, vacancies.responsibilities, vacancies.qualifications', false)
            ->join('vacancy_recruitment_periods AS periods', 'periods.vacancy_id = vacancies.id')
            ->where('vacancies.deleted_at', null)
            ->where('periods.deleted_at', null)
            ->whereIn('periods.status', ['open', 'scheduled'])
            ->groupStart()
                ->where('periods.opened_at', null)
                ->orWhere('periods.opened_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('periods.closed_at', null)
                ->orWhere('periods.closed_at >=', $now)
            ->groupEnd()
            ->get()
            ->getResultArray();

        foreach ($activeVacancies as $vacancy) {
            $vacancyContent = $content[(string) $vacancy['code']] ?? null;
            if ($vacancyContent === null) {
                continue;
            }

            $updates = [];
            foreach (['job_description', 'responsibilities', 'qualifications'] as $field) {
                if (trim((string) ($vacancy[$field] ?? '')) === '') {
                    $updates[$field] = $vacancyContent[$field];
                }
            }
            if ($updates !== []) {
                $updates['updated_at'] = $now;
                $this->db->table('vacancies')->where('id', (int) $vacancy['id'])->update($updates);
            }
        }
    }
}
