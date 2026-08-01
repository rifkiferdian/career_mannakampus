<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthController extends BaseController
{
    private const DUMMY_PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function login(): string|RedirectResponse
    {
        $this->disableClientCaching();

        if ($this->authenticatedUser() !== null) {
            return redirect()->to(site_url('adminhrdmannakampus/dashboard'));
        }

        return view('admin/auth/login', [
            'error' => session()->getFlashdata('auth_error'),
        ]);
    }

    public function authenticate(): string|RedirectResponse
    {
        $this->disableClientCaching();

        $email = mb_strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $throttleKey = 'hrd_login_' . hash(
            'sha256',
            $this->request->getIPAddress() . '|' . $email,
        );

        if (! Services::throttler()->check($throttleKey, 5, 60)) {
            $this->response->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS);

            return view('admin/auth/login', [
                'error' => 'Terlalu banyak percobaan masuk. Tunggu satu menit lalu coba kembali.',
                'email' => $email,
            ]);
        }

        $input = ['email' => $email, 'password' => $password];
        $rules = [
            'email'    => 'required|valid_email|max_length[190]',
            'password' => 'required|max_length[255]',
        ];
        $messages = [
            'email' => [
                'required'    => 'Email wajib diisi.',
                'valid_email' => 'Format email tidak valid.',
                'max_length'  => 'Email tidak valid.',
            ],
            'password' => [
                'required'   => 'Password wajib diisi.',
                'max_length' => 'Password tidak valid.',
            ],
        ];

        if (! $this->validateData($input, $rules, $messages)) {
            return view('admin/auth/login', [
                'errors' => $this->validator->getErrors(),
                'email'  => $email,
            ]);
        }

        $userModel = new UserModel();
        $user = $userModel->findActiveHrdByEmail($email);
        $passwordHash = (string) ($user['password_hash'] ?? self::DUMMY_PASSWORD_HASH);
        $passwordMatches = password_verify($password, $passwordHash);
        $isLocked = $user !== null
            && ! empty($user['locked_until'])
            && strtotime((string) $user['locked_until']) > time();

        if ($user === null || ! $passwordMatches || $isLocked) {
            if ($user !== null && ! $isLocked) {
                $failedAttempts = (int) $user['failed_login_attempts'] + 1;
                $userModel->update((int) $user['id'], [
                    'failed_login_attempts' => $failedAttempts >= 5 ? 0 : $failedAttempts,
                    'locked_until'          => $failedAttempts >= 5
                        ? date('Y-m-d H:i:s', time() + 900)
                        : null,
                ]);
            }

            return view('admin/auth/login', [
                'error' => 'Email atau password tidak sesuai.',
                'email' => $email,
            ]);
        }

        session()->regenerate(true);
        session()->set('hrd_auth', [
            'user_id' => (int) $user['id'],
            'name'    => (string) $user['name'],
            'email'   => (string) $user['email'],
            'role'    => (string) $user['role'],
        ]);

        $userModel->update((int) $user['id'], [
            'last_login_at'        => date('Y-m-d H:i:s'),
            'last_login_ip'        => $this->request->getIPAddress(),
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);

        return redirect()->to(site_url('adminhrdmannakampus/dashboard'))
            ->with('auth_success', 'Selamat datang kembali, ' . $user['name'] . '.');
    }

    public function logout(): RedirectResponse
    {
        session()->remove('hrd_auth');
        session()->regenerate(true);

        return redirect()->to(site_url('adminhrdmannakampus'))
            ->with('auth_success', 'Anda telah keluar dengan aman.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authenticatedUser(): ?array
    {
        $auth = session()->get('hrd_auth');
        $userId = is_array($auth) ? (int) ($auth['user_id'] ?? 0) : 0;

        return $userId > 0 ? (new UserModel())->findActiveHrdById($userId) : null;
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
