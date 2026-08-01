<?php

namespace App\Filters;

use App\Modules\Admin\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class SuperAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session()->get('hrd_auth');
        $userId = is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0;
        $user = $userId > 0 ? (new UserModel())->findActiveHrdById($userId) : null;

        if ($user === null || ! Services::hrdSession()->validateAndTouch($userId)) {
            session()->remove('hrd_auth');
            Services::hrdSession()->clearCurrentToken();

            return redirect()->to(site_url('adminhrdmannakampus'))
                ->with('auth_error', 'Silakan masuk menggunakan akun admin yang aktif.');
        }

        if (! Services::authorization()->isSuperAdmin($userId)) {
            return redirect()->to(site_url('adminhrdmannakampus/dashboard'))
                ->with('access_error', 'Hanya Super Admin yang dapat mengelola user, role, dan permission.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
