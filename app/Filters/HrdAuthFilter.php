<?php

namespace App\Filters;

use App\Modules\Admin\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HrdAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = session()->get('hrd_auth');
        $userId = is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0;
        $user = $userId > 0 ? (new UserModel())->findActiveHrdById($userId) : null;

        if ($user === null) {
            session()->remove('hrd_auth');

            return redirect()->to(site_url('adminhrdmannakampus'))
                ->with('auth_error', 'Silakan masuk menggunakan akun HRD yang aktif.');
        }

        session()->set('hrd_auth', [
            'user_id' => (int) $user['id'],
            'name'    => (string) $user['name'],
            'email'   => (string) $user['email'],
            'role'    => (string) $user['role'],
        ]);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
