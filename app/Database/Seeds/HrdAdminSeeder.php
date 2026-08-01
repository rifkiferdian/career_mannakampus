<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HrdAdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $role = $this->db->table('roles')
            ->where('code', 'HRD')
            ->get()
            ->getRowArray();

        if ($role === null) {
            $this->db->table('roles')->insert([
                'name'        => 'Human Resources Development',
                'code'        => 'HRD',
                'description' => 'Akses internal untuk mengelola proses rekrutmen Manna Kampus.',
                'is_system'   => 1,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $roleId = (int) $this->db->insertID();
        } else {
            $roleId = (int) $role['id'];
        }

        $email = mb_strtolower(trim((string) env('HRD_ADMIN_EMAIL', 'hrd@mannakampus.test')));
        $user = $this->db->table('users')->where('email', $email)->get()->getRowArray();

        if ($user === null) {
            $configuredPassword = trim((string) env('HRD_ADMIN_PASSWORD', ''));
            $password = $configuredPassword !== ''
                ? $configuredPassword
                : bin2hex(random_bytes(8)) . '!Aa9';

            $this->db->table('users')->insert([
                'uuid'          => $this->uuidV4(),
                'full_name'     => 'Admin HRD Manna Kampus',
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_active'     => 1,
                'email_verified_at' => $now,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $userId = (int) $this->db->insertID();

            echo PHP_EOL;
            echo 'Akun HRD dibuat:' . PHP_EOL;
            echo 'Email    : ' . $email . PHP_EOL;
            echo 'Password : ' . $password . PHP_EOL;
            echo 'Simpan password ini, karena tidak dapat ditampilkan kembali.' . PHP_EOL;
        } else {
            $userId = (int) $user['id'];
            echo PHP_EOL . 'Akun HRD sudah tersedia; password tidak diubah.' . PHP_EOL;
        }

        $hasRole = $this->db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->countAllResults() > 0;

        if (! $hasRole) {
            $this->db->table('user_roles')->insert([
                'user_id'     => $userId,
                'role_id'     => $roleId,
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

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}
