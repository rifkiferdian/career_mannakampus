<?php

namespace App\Filters;

use App\Modules\Admin\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $permission = (string) ($arguments[0] ?? '');
        $auth = session()->get('hrd_auth');
        $userId = is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0;
        $user = $userId > 0 ? (new UserModel())->findActiveHrdById($userId) : null;

        if ($user === null || ! Services::hrdSession()->validateAndTouch($userId)) {
            session()->remove('hrd_auth');

            return redirect()->to(site_url('adminhrdmannakampus'))
                ->with('auth_error', 'Silakan masuk menggunakan akun admin yang aktif.');
        }

        if ($permission === '' || ! Services::authorization()->can($userId, $permission)) {
            return redirect()->to(site_url('adminhrdmannakampus/dashboard'))
                ->with('access_error', 'Akun Anda tidak memiliki permission untuk mengakses halaman tersebut.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
