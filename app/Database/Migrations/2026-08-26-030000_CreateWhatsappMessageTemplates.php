<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsappMessageTemplates extends Migration
{
    private const PERMISSIONS = ['whatsapp.templates.view', 'whatsapp.templates.manage'];

    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'category' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'other'],
            'message_text' => ['type' => 'TEXT'],
            'display_order' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 1],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_whatsapp_template_code');
        $this->forge->addKey(['is_active', 'display_order'], false, false, 'idx_whatsapp_template_order');
        $this->forge->createTable('whatsapp_message_templates');

        $now = date('Y-m-d H:i:s');
        $templates = [
            ['code' => 'kontak_awal', 'name' => 'Kontak awal', 'category' => 'contact', 'display_order' => 1, 'message_text' => "Halo {{nama_pelamar}},\n\nPerkenalkan, saya {{nama_recruiter}} dari Tim Rekrutmen Manna Kampus. Kami ingin menghubungi Anda terkait lamaran untuk posisi {{nama_lowongan}}.\n\nApakah saat ini Anda bersedia melanjutkan proses rekrutmen?\n\nTerima kasih."],
            ['code' => 'undangan_seleksi', 'name' => 'Undangan tes atau wawancara', 'category' => 'schedule', 'display_order' => 2, 'message_text' => "Halo {{nama_pelamar}},\n\nKami mengundang Anda untuk mengikuti {{nama_tahap}} dalam proses rekrutmen posisi {{nama_lowongan}}.\n\nTanggal: {{tanggal}}\nWaktu: {{jam}} WIB\nLokasi/Link: {{lokasi}}\nPIC: {{nama_pic}}\n\nCatatan:\n{{instruksi}}\n\nMohon konfirmasi kehadiran paling lambat {{batas_konfirmasi}}.\n\nTerima kasih.\nTim Rekrutmen Manna Kampus"],
            ['code' => 'pengingat_jadwal', 'name' => 'Pengingat jadwal', 'category' => 'reminder', 'display_order' => 3, 'message_text' => "Halo {{nama_pelamar}},\n\nKami mengingatkan kembali mengenai jadwal {{nama_tahap}} untuk posisi {{nama_lowongan}}.\n\nTanggal: {{tanggal}}\nWaktu: {{jam}} WIB\nLokasi/Link: {{lokasi}}\n\nMohon hadir 10-15 menit sebelum jadwal. Jika ada kendala, silakan menghubungi kami melalui WhatsApp ini.\n\nTerima kasih."],
            ['code' => 'belum_konfirmasi', 'name' => 'Belum konfirmasi kehadiran', 'category' => 'confirmation', 'display_order' => 4, 'message_text' => "Halo {{nama_pelamar}},\n\nKami belum menerima konfirmasi kehadiran Anda untuk jadwal {{nama_tahap}} posisi {{nama_lowongan}} pada {{tanggal}}, pukul {{jam}} WIB.\n\nMohon informasikan apakah Anda bersedia hadir, tidak dapat hadir, atau memerlukan jadwal ulang.\n\nKami menunggu konfirmasi Anda paling lambat {{batas_konfirmasi}}.\n\nTerima kasih."],
            ['code' => 'lolos_tahap', 'name' => 'Lolos ke tahap berikutnya', 'category' => 'progress', 'display_order' => 5, 'message_text' => "Halo {{nama_pelamar}},\n\nTerima kasih telah mengikuti proses {{tahap_sebelumnya}} untuk posisi {{nama_lowongan}}.\n\nKami informasikan bahwa Anda dapat melanjutkan ke tahap {{tahap_berikutnya}}. Informasi mengenai jadwal dan persiapan akan kami sampaikan berikutnya.\n\nTerima kasih.\nTim Rekrutmen Manna Kampus"],
            ['code' => 'tidak_melanjutkan', 'name' => 'Tidak melanjutkan proses', 'category' => 'result', 'display_order' => 6, 'message_text' => "Halo {{nama_pelamar}},\n\nTerima kasih telah mengikuti proses rekrutmen untuk posisi {{nama_lowongan}} di Manna Kampus.\n\nSetelah mempertimbangkan hasil seleksi, saat ini kami belum dapat melanjutkan proses Anda ke tahap berikutnya. Keputusan ini tidak mengurangi apresiasi kami terhadap waktu dan ketertarikan Anda untuk bergabung bersama Manna Kampus.\n\nKami mendoakan kesuksesan Anda ke depannya. Terima kasih."],
        ];
        foreach ($templates as $template) {
            $this->db->table('whatsapp_message_templates')->insert($template + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissionRows = [
            ['name' => 'Melihat template WhatsApp', 'code' => self::PERMISSIONS[0], 'module' => 'communications', 'description' => 'Melihat template pesan WhatsApp untuk komunikasi pelamar.'],
            ['name' => 'Mengelola template WhatsApp', 'code' => self::PERMISSIONS[1], 'module' => 'communications', 'description' => 'Menambah, mengubah, mengurutkan, dan menonaktifkan template WhatsApp.'],
        ];
        foreach ($permissionRows as $row) {
            $this->db->table('permissions')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $roles = array_column($this->db->table('roles')->select('id, code')->get()->getResultArray(), 'id', 'code');
        $permissions = array_column($this->db->table('permissions')->select('id, code')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id', 'code');
        $mapping = ['SUPER_ADMIN' => self::PERMISSIONS, 'HRD_MANAGER' => self::PERMISSIONS, 'RECRUITER' => [self::PERMISSIONS[0]]];
        foreach ($mapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                if (isset($roles[$roleCode], $permissions[$permissionCode])) {
                    $this->db->table('role_permissions')->insert(['role_id' => $roles[$roleCode], 'permission_id' => $permissions[$permissionCode], 'assigned_by' => null, 'assigned_at' => $now]);
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = array_column($this->db->table('permissions')->select('id')->whereIn('code', self::PERMISSIONS)->get()->getResultArray(), 'id');
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
        $this->forge->dropTable('whatsapp_message_templates', true);
    }
}
