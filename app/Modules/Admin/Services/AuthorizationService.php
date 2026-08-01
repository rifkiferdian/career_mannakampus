<?php

namespace App\Modules\Admin\Services;

use CodeIgniter\Database\BaseConnection;

class AuthorizationService
{
    public const PORTAL_ROLES = ['SUPER_ADMIN', 'HRD_MANAGER', 'RECRUITER', 'VIEWER'];

    public function __construct(
        private readonly BaseConnection $database,
    ) {
    }

    public function can(int $userId, string $permissionCode): bool
    {
        if ($this->isSuperAdmin($userId)) {
            return true;
        }

        return $this->database->table('user_roles')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->join('role_permissions', 'role_permissions.role_id = roles.id')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.is_active', 1)
            ->where('permissions.is_active', 1)
            ->where('permissions.code', $permissionCode)
            ->groupStart()
                ->where('user_roles.expires_at', null)
                ->orWhere('user_roles.expires_at >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    public function isSuperAdmin(int $userId): bool
    {
        return $this->database->table('user_roles')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.code', 'SUPER_ADMIN')
            ->where('roles.is_active', 1)
            ->groupStart()
                ->where('user_roles.expires_at', null)
                ->orWhere('user_roles.expires_at >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->countAllResults() > 0;
    }
}
