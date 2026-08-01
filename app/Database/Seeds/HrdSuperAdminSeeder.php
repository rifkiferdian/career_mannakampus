<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HrdSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $role = $this->db->table('roles')->where('code', 'SUPER_ADMIN')->get()->getRowArray();
        if ($role === null) {
            echo 'Role SUPER_ADMIN belum tersedia. Jalankan migration terlebih dahulu.' . PHP_EOL;
            return;
        }

        $email = mb_strtolower(trim((string) env('HRD_SUPER_ADMIN_EMAIL', 'superadmin@mannakampus.test')));
        $user = $this->db->table('users')->where('email', $email)->get()->getRowArray();

        if ($user === null) {
            $configuredPassword = trim((string) env('HRD_SUPER_ADMIN_PASSWORD', ''));
            $password = $configuredPassword !== '' ? $configuredPassword : bin2hex(random_bytes(9)) . '!Sa8';
            $this->db->table('users')->insert([
                'uuid'              => $this->uuidV4(),
                'email'             => $email,
                'password_hash'     => password_hash($password, PASSWORD_DEFAULT),
                'full_name'         => 'Super Admin HRD',
                'email_verified_at' => $now,
                'is_active'         => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $userId = (int) $this->db->insertID();
            echo PHP_EOL . 'Akun Super Admin HRD dibuat:' . PHP_EOL;
            echo 'Email    : ' . $email . PHP_EOL;
            echo 'Password : ' . $password . PHP_EOL;
            echo 'Simpan password ini, karena tidak dapat ditampilkan kembali.' . PHP_EOL;
        } else {
            $userId = (int) $user['id'];
            echo PHP_EOL . 'Akun Super Admin HRD sudah tersedia; password tidak diubah.' . PHP_EOL;
        }

        $assigned = $this->db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $role['id'])
            ->countAllResults() > 0;
        if (! $assigned) {
            $this->db->table('user_roles')->insert([
                'user_id'     => $userId,
                'role_id'     => $role['id'],
                'assigned_by' => null,
                'assigned_at' => $now,
            ]);
        }
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}
