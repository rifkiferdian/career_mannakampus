<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class ProfileController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $user = (new UserModel())->findActiveHrdById($userId);

        return view('admin/profile', [
            'auth'           => $auth,
            'user'           => $user,
            'canViewRecruitmentSettings' => Services::authorization()->can($userId, 'recruitment.settings.view'),
            'canViewDepartments' => Services::authorization()->can($userId, 'departments.view'),
            'canViewVacancies' => Services::authorization()->can($userId, 'vacancies.view'),
            'activeSessions' => Services::hrdSession()->activeSessions($userId),
            'loginHistory'   => Services::hrdSession()->loginHistory($userId),
            'success'        => session()->getFlashdata('profile_success'),
            'error'          => session()->getFlashdata('profile_error'),
            'profileErrors'  => session()->getFlashdata('profile_errors') ?? [],
            'passwordErrors' => session()->getFlashdata('password_errors') ?? [],
        ]);
    }

    public function update(): RedirectResponse
    {
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $input = [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'email'     => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'phone'     => trim((string) $this->request->getPost('phone')),
        ];
        $rules = [
            'full_name' => 'required|min_length[3]|max_length[150]',
            'email'     => 'required|valid_email|max_length[150]',
            'phone'     => 'permit_empty|max_length[30]|regex_match[/^\+?[0-9][0-9\s-]{7,29}$/]',
        ];
        $messages = [
            'full_name' => [
                'required'   => 'Nama lengkap wajib diisi.',
                'min_length' => 'Nama lengkap minimal 3 karakter.',
                'max_length' => 'Nama lengkap maksimal 150 karakter.',
            ],
            'email' => [
                'required'    => 'Email wajib diisi.',
                'valid_email' => 'Format email tidak valid.',
                'max_length'  => 'Email maksimal 150 karakter.',
            ],
            'phone' => [
                'max_length'  => 'Nomor WhatsApp maksimal 30 karakter.',
                'regex_match' => 'Format nomor WhatsApp tidak valid.',
            ],
        ];

        if (! $this->validateData($input, $rules, $messages)) {
            return redirect()->back()->withInput()->with('profile_errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $emailOwner = $userModel->withDeleted()->where('email', $input['email'])->first();
        if ($emailOwner !== null && (int) $emailOwner['id'] !== $userId) {
            return redirect()->back()->withInput()->with('profile_errors', [
                'email' => 'Email sudah digunakan oleh akun lain.',
            ]);
        }

        $userModel->update($userId, [
            'full_name' => $input['full_name'],
            'email'     => $input['email'],
            'phone'     => $input['phone'] !== '' ? $input['phone'] : null,
        ]);
        Services::hrdSession()->recordEvent(
            $userId,
            $input['email'],
            'profile_updated',
            true,
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent(),
        );

        return redirect()->to(site_url('adminhrdmannakampus/profil'))
            ->with('profile_success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(): RedirectResponse
    {
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $input = [
            'current_password' => (string) $this->request->getPost('current_password'),
            'new_password'     => (string) $this->request->getPost('new_password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
        ];
        $rules = [
            'current_password' => 'required|max_length[255]',
            'new_password'     => 'required|min_length[12]|max_length[255]',
            'password_confirm' => 'required|matches[new_password]',
        ];
        $messages = [
            'current_password' => ['required' => 'Password saat ini wajib diisi.'],
            'new_password' => [
                'required'   => 'Password baru wajib diisi.',
                'min_length' => 'Password baru minimal 12 karakter.',
                'max_length' => 'Password baru terlalu panjang.',
            ],
            'password_confirm' => [
                'required' => 'Konfirmasi password wajib diisi.',
                'matches'  => 'Konfirmasi password tidak sama.',
            ],
        ];

        if (! $this->validateData($input, $rules, $messages)) {
            return redirect()->to(site_url('adminhrdmannakampus/profil#password'))
                ->with('password_errors', $this->validator->getErrors());
        }

        if (! $this->isStrongPassword($input['new_password'])) {
            return redirect()->to(site_url('adminhrdmannakampus/profil#password'))
                ->with('password_errors', [
                    'new_password' => 'Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol.',
                ]);
        }

        $userModel = new UserModel();
        $user = $userModel->findActiveHrdById($userId);
        if ($user === null || ! password_verify($input['current_password'], (string) $user['password_hash'])) {
            return redirect()->to(site_url('adminhrdmannakampus/profil#password'))
                ->with('password_errors', ['current_password' => 'Password saat ini tidak sesuai.']);
        }

        if (password_verify($input['new_password'], (string) $user['password_hash'])) {
            return redirect()->to(site_url('adminhrdmannakampus/profil#password'))
                ->with('password_errors', ['new_password' => 'Password baru harus berbeda dari password saat ini.']);
        }

        $userModel->update($userId, ['password_hash' => password_hash($input['new_password'], PASSWORD_DEFAULT)]);
        Services::hrdSession()->revokeOthers($userId);
        Services::hrdSession()->recordEvent(
            $userId,
            (string) $user['email'],
            'password_changed',
            true,
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent(),
        );

        return redirect()->to(site_url('adminhrdmannakampus/profil#password'))
            ->with('profile_success', 'Password berhasil diubah. Perangkat lain telah dikeluarkan.');
    }

    public function revokeSession(int $sessionId): RedirectResponse
    {
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $revokedCurrent = Services::hrdSession()->revokeById($userId, $sessionId);

        if ($revokedCurrent) {
            session()->remove('hrd_auth');
            Services::hrdSession()->clearCurrentToken();
            session()->regenerate(true);

            return redirect()->to(site_url('adminhrdmannakampus'))
                ->with('auth_success', 'Perangkat ini telah dikeluarkan.');
        }

        return redirect()->to(site_url('adminhrdmannakampus/profil#devices'))
            ->with('profile_success', 'Akses perangkat berhasil dicabut.');
    }

    public function revokeAllSessions(): RedirectResponse
    {
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        Services::hrdSession()->recordEvent(
            $userId,
            (string) ($auth['email'] ?? ''),
            'sessions_revoked',
            true,
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent(),
        );
        Services::hrdSession()->revokeAll($userId);
        session()->remove('hrd_auth');
        Services::hrdSession()->clearCurrentToken();
        session()->regenerate(true);

        return redirect()->to(site_url('adminhrdmannakampus'))
            ->with('auth_success', 'Semua perangkat telah dikeluarkan. Silakan masuk kembali.');
    }

    private function isStrongPassword(string $password): bool
    {
        return preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^a-zA-Z0-9]/', $password) === 1;
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
