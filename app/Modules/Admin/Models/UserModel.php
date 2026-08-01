<?php

namespace App\Modules\Admin\Models;

use App\Modules\Admin\Services\AuthorizationService;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'full_name',
        'email',
        'phone',
        'password_hash',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveHrdByEmail(string $email): ?array
    {
        return $this->findActiveHrd('users.email', mb_strtolower(trim($email)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveHrdById(int $id): ?array
    {
        return $this->findActiveHrd('users.id', $id);
    }

    /**
     * @param int|string $value
     *
     * @return array<string, mixed>|null
     */
    private function findActiveHrd(string $field, $value): ?array
    {
        return $this->builder()
            ->select('users.id, users.full_name AS name, users.email, users.phone, users.password_hash, users.failed_login_attempts, users.locked_until, roles.code AS role')
            ->join('user_roles', 'user_roles.user_id = users.id')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where($field, $value)
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->whereIn('roles.code', AuthorizationService::PORTAL_ROLES)
            ->where('roles.is_active', 1)
            ->groupStart()
                ->where('user_roles.expires_at', null)
                ->orWhere('user_roles.expires_at >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy("CASE roles.code WHEN 'SUPER_ADMIN' THEN 1 WHEN 'HRD_MANAGER' THEN 2 WHEN 'RECRUITER' THEN 3 ELSE 4 END", '', false)
            ->get()
            ->getRowArray();
    }
}
