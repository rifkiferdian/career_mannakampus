<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function selectionProcess(): string
    {
        return view('selection_process');
    }

    public function jobs(): string
    {
        return view('jobs');
    }

    public function register(): string
    {
        $session = session();

        if (! $session->has('career_captcha') || $this->request->getGet('refresh') === '1') {
            $session->set('career_captcha', $this->generateCaptcha());
        }

        return view('register', [
            'captcha' => $session->get('career_captcha'),
        ]);
    }

    public function createAccount(): RedirectResponse
    {
        $post = $this->request->getPost();
        $validation = service('validation');
        $validation->setRules([
            'full_name' => [
                'label' => 'Nama lengkap',
                'rules' => 'required|min_length[3]|max_length[150]',
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[150]|is_unique[applicants.email]',
                'errors' => ['is_unique' => 'Email tersebut sudah terdaftar.'],
            ],
            'phone' => [
                'label' => 'Nomor WhatsApp',
                'rules' => 'required|min_length[9]|max_length[20]|regex_match[/^\+?[0-9\s-]+$/]',
                'errors' => ['regex_match' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda +, atau tanda -.'],
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|max_length[72]',
            ],
            'password_confirm' => [
                'label' => 'Konfirmasi password',
                'rules' => 'required|matches[password]',
                'errors' => ['matches' => 'Konfirmasi password tidak sama.'],
            ],
            'captcha' => [
                'label' => 'Kode keamanan',
                'rules' => 'required',
            ],
            'terms' => [
                'label' => 'Persetujuan',
                'rules' => 'required',
                'errors' => ['required' => 'Anda harus menyetujui syarat dan ketentuan.'],
            ],
        ]);

        $isValid = $validation->run($post);
        $captchaIsValid = strtoupper(trim((string) ($post['captcha'] ?? '')))
            === (string) session()->get('career_captcha');

        if (! $captchaIsValid) {
            $validation->setError('captcha', 'Kode keamanan tidak sesuai.');
        }

        if (! $isValid || ! $captchaIsValid) {
            session()->set('career_captcha', $this->generateCaptcha());

            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $database = db_connect();

        try {
            $database->transException(true)->transStart();
            $registeredAt = date('Y-m-d H:i:s');

            $database->table('applicants')->insert([
                'uuid'                    => $this->generateUuid(),
                'email'                   => strtolower(trim((string) $post['email'])),
                'password_hash'           => password_hash((string) $post['password'], PASSWORD_DEFAULT),
                'full_name'               => trim((string) $post['full_name']),
                'phone'                   => preg_replace('/[^0-9+]/', '', (string) $post['phone']),
                'privacy_consent_at'      => $registeredAt,
                'privacy_policy_version' => '2026-07',
                'registration_ip'         => $this->request->getIPAddress(),
                'registration_user_agent' => substr($this->request->getUserAgent()->getAgentString(), 0, 500),
                'is_active'               => 1,
                'created_at'              => $registeredAt,
                'updated_at'              => $registeredAt,
            ]);

            $database->transComplete();
        } catch (Throwable $exception) {
            log_message('error', 'Registrasi akun karier gagal: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('register_error', 'Akun belum dapat dibuat. Silakan coba kembali.');
        }

        session()->remove('career_captcha');

        return redirect()->to(site_url('daftar'))->with('register_success', 'Akun karier berhasil dibuat. Anda dapat melanjutkan proses lamaran.');
    }

    public function login(): string
    {
        $session = session();

        if (! $session->has('login_captcha') || $this->request->getGet('refresh') === '1') {
            $session->set('login_captcha', $this->generateCaptcha());
        }

        if (session()->get('applicant_logged_in') === true) {
            return view('login', [
                'alreadyLoggedIn' => true,
                'captcha'         => $session->get('login_captcha'),
            ]);
        }

        return view('login', [
            'alreadyLoggedIn' => false,
            'captcha'         => $session->get('login_captcha'),
        ]);
    }

    public function authenticate(): RedirectResponse
    {
        $post = $this->request->getPost();
        $validation = service('validation');
        $validation->setRules([
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[150]',
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|max_length[72]',
            ],
            'captcha' => [
                'label' => 'Kode keamanan',
                'rules' => 'required',
            ],
        ]);

        $isValid = $validation->run($post);
        $captchaIsValid = strtoupper(trim((string) ($post['captcha'] ?? '')))
            === (string) session()->get('login_captcha');

        if (! $captchaIsValid) {
            $validation->setError('captcha', 'Kode keamanan tidak sesuai.');
        }

        if (! $isValid || ! $captchaIsValid) {
            session()->set('login_captcha', $this->generateCaptcha());

            return redirect()->back()->withInput()->with('login_errors', $validation->getErrors());
        }

        $database = db_connect();
        $email = strtolower(trim((string) $post['email']));
        $applicant = $database->table('applicants')
            ->where('email', $email)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($applicant === null || (int) $applicant['is_active'] !== 1) {
            session()->set('login_captcha', $this->generateCaptcha());

            return redirect()->back()->withInput()->with('login_error', 'Email atau password tidak sesuai.');
        }

        if ($applicant['locked_until'] !== null && strtotime((string) $applicant['locked_until']) > time()) {
            session()->set('login_captcha', $this->generateCaptcha());

            return redirect()->back()->withInput()->with('login_error', 'Akun terkunci sementara. Silakan coba kembali setelah 15 menit.');
        }

        if (! password_verify((string) $post['password'], (string) $applicant['password_hash'])) {
            $failedAttempts = (int) $applicant['failed_login_attempts'] + 1;
            $loginUpdate = ['failed_login_attempts' => $failedAttempts];

            if ($failedAttempts >= 5) {
                $loginUpdate['locked_until'] = date('Y-m-d H:i:s', time() + 900);
            }

            $database->table('applicants')->where('id', $applicant['id'])->update($loginUpdate);

            $message = $failedAttempts >= 5
                ? 'Terlalu banyak percobaan gagal. Akun dikunci selama 15 menit.'
                : 'Email atau password tidak sesuai.';

            session()->set('login_captcha', $this->generateCaptcha());

            return redirect()->back()->withInput()->with('login_error', $message);
        }

        $database->table('applicants')->where('id', $applicant['id'])->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_login_at'         => date('Y-m-d H:i:s'),
            'last_login_ip'         => $this->request->getIPAddress(),
        ]);

        session()->regenerate(true);
        session()->set([
            'applicant_id'        => (int) $applicant['id'],
            'applicant_uuid'      => $applicant['uuid'],
            'applicant_name'      => $applicant['full_name'],
            'applicant_email'     => $applicant['email'],
            'applicant_logged_in' => true,
        ]);
        session()->remove('login_captcha');

        return redirect()->to(site_url('lowongan'))->with('login_success', 'Selamat datang kembali, ' . $applicant['full_name'] . '.');
    }

    private function generateCaptcha(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $captcha = '';

        for ($index = 0; $index < 5; $index++) {
            $captcha .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $captcha;
    }

    private function generateUuid(): string
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
            substr($hex, 20)
        );
    }
}
