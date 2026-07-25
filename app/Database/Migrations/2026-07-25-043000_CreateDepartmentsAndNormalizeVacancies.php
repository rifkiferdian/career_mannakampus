<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class CreateDepartmentsAndNormalizeVacancies extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'display_order' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('name');
        $this->forge->addKey(['is_active', 'display_order']);
        $this->forge->createTable('departments');

        $now = date('Y-m-d H:i:s');
        $this->db->table('departments')->insertBatch([
            $this->department('operation', 'Operation', 'Operasional toko dan pelayanan pelanggan.', 1, $now),
            $this->department('merchandising', 'Merchandising & Buying', 'Pengelolaan produk, harga, promosi, dan supplier.', 2, $now),
            $this->department('supply-chain', 'Logistic & Supply Chain', 'Gudang, distribusi, pengiriman, dan persediaan.', 3, $now),
            $this->department('marketing', 'Marketing', 'Promosi, komunikasi, pemasaran digital, dan CRM.', 4, $now),
            $this->department('information-technology', 'Information Technology', 'Sistem informasi, aplikasi, produk digital, dan data.', 5, $now),
            $this->department('human-capital', 'Human Capital', 'Rekrutmen, pelatihan, dan pengembangan karyawan.', 6, $now),
            $this->department('finance-accounting', 'Finance & Accounting', 'Keuangan, akuntansi, pajak, dan anggaran.', 7, $now),
            $this->department('procurement', 'Procurement', 'Pengadaan barang dan jasa pendukung perusahaan.', 8, $now),
            $this->department('property-general-affairs', 'Property & General Affairs', 'Gedung, fasilitas, perawatan, dan kebutuhan umum.', 9, $now),
            $this->department('security-loss-prevention', 'Security & Loss Prevention', 'Keamanan dan pencegahan kehilangan.', 10, $now),
            $this->department('service-quality', 'Service Quality', 'Standar pelayanan dan pengalaman pelanggan.', 11, $now),
            $this->department('corporate-affairs', 'Legal, Audit & Corporate Affairs', 'Legalitas, audit, perizinan, dan kepatuhan.', 12, $now),
        ]);

        $this->forge->addColumn('vacancies', [
            'department_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'title',
            ],
        ]);

        $departmentIds = array_column(
            $this->db->table('departments')->select('id, code')->get()->getResultArray(),
            'id',
            'code',
        );
        $legacyMapping = [
            'Retail Operations'    => 'operation',
            'Product & Technology' => 'information-technology',
            'Marketing'            => 'marketing',
            'People Operations'    => 'human-capital',
        ];

        foreach ($legacyMapping as $legacyName => $departmentCode) {
            $this->db->table('vacancies')
                ->where('department', $legacyName)
                ->update(['department_id' => $departmentIds[$departmentCode]]);
        }

        $unmappedVacancies = $this->db->table('vacancies')
            ->where('department_id', null)
            ->countAllResults();

        if ($unmappedVacancies > 0) {
            throw new RuntimeException(
                "Terdapat {$unmappedVacancies} lowongan dengan departemen yang belum dipetakan.",
            );
        }

        $this->db->query(
            'ALTER TABLE `vacancies` MODIFY `department_id` BIGINT UNSIGNED NOT NULL',
        );
        $this->db->query(
            'ALTER TABLE `vacancies` ADD CONSTRAINT `fk_vacancies_department` '
            . 'FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT',
        );
        $this->forge->dropColumn('vacancies', 'department');
    }

    public function down(): void
    {
        $this->forge->addColumn('vacancies', [
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'department_id',
            ],
        ]);

        $this->db->query(
            'UPDATE `vacancies` AS `v` '
            . 'INNER JOIN `departments` AS `d` ON `d`.`id` = `v`.`department_id` '
            . 'SET `v`.`department` = `d`.`name`',
        );
        $this->db->query(
            'ALTER TABLE `vacancies` MODIFY `department` VARCHAR(100) NOT NULL',
        );
        $this->db->query(
            'ALTER TABLE `vacancies` DROP FOREIGN KEY `fk_vacancies_department`',
        );
        $this->forge->dropColumn('vacancies', 'department_id');
        $this->forge->dropTable('departments', true);
    }

    /**
     * @return array<string, int|string>
     */
    private function department(
        string $code,
        string $name,
        string $description,
        int $displayOrder,
        string $now,
    ): array {
        return [
            'code'          => $code,
            'name'          => $name,
            'description'   => $description,
            'display_order' => $displayOrder,
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];
    }
}
