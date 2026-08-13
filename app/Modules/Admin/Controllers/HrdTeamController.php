<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class HrdTeamController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        $teams = $database->table('hrd_teams AS teams')
            ->select('teams.*, COUNT(team_users.user_id) AS member_count')
            ->join('hrd_team_users AS team_users', 'team_users.hrd_team_id = teams.id', 'left')
            ->groupBy('teams.id')
            ->orderBy('teams.name')
            ->get()->getResultArray();
        $users = $database->table('users AS users')
            ->distinct()
            ->select('users.id, users.full_name, users.email, users.is_active, teams.id AS hrd_team_id, teams.name AS hrd_team_name')
            ->join('hrd_team_users AS team_users', 'team_users.user_id = users.id', 'left')
            ->join('hrd_teams AS teams', 'teams.id = team_users.hrd_team_id', 'left')
            ->join('user_roles AS user_roles', 'user_roles.user_id = users.id')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('users.deleted_at', null)
            ->whereIn('roles.code', ['SUPER_ADMIN', 'HRD_MANAGER', 'RECRUITER', 'VIEWER'])
            ->orderBy('users.full_name')
            ->get()->getResultArray();

        return view('admin/hrd_teams', [
            'auth' => $auth,
            'teams' => $teams,
            'users' => $users,
            'canManage' => Services::authorization()->can($userId, 'hrd.teams.manage'),
            'success' => session()->getFlashdata('hrd_team_success'),
            'error' => session()->getFlashdata('hrd_team_error'),
            'openModal' => (string) (session()->getFlashdata('hrd_team_form') ?? ''),
        ]);
    }

    public function create(): RedirectResponse
    {
        $data = $this->teamInput();
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        try {
            db_connect()->table('hrd_teams')->insert($data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        } catch (DatabaseException) {
            return $this->formError('Kode divisi sudah digunakan.', 'create');
        }

        return $this->success('Divisi HRD berhasil ditambahkan.');
    }

    public function update(int $teamId): RedirectResponse
    {
        $team = db_connect()->table('hrd_teams')->where('id', $teamId)->get()->getRowArray();
        if ($team === null) {
            return $this->error('Divisi HRD tidak ditemukan.');
        }
        $data = $this->teamInput('edit-' . $teamId);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        if ((int) $data['is_active'] === 0 && db_connect()->table('applications')->where('assigned_hrd_team_id', $teamId)->where('deleted_at', null)->countAllResults() > 0) {
            return $this->formError('Divisi masih memiliki pelamar dan tidak dapat dinonaktifkan.', 'edit-' . $teamId);
        }
        try {
            db_connect()->table('hrd_teams')->where('id', $teamId)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
        } catch (DatabaseException) {
            return $this->formError('Kode divisi sudah digunakan.', 'edit-' . $teamId);
        }

        return $this->success('Divisi HRD berhasil diperbarui.');
    }

    public function assignUser(int $userId): RedirectResponse
    {
        $database = db_connect();
        $user = $database->table('users')->where('id', $userId)->where('deleted_at', null)->get()->getRowArray();
        $teamId = max(0, (int) $this->request->getPost('hrd_team_id'));
        if ($user === null) {
            return $this->error('User HRD tidak ditemukan.');
        }
        if ($teamId > 0 && $database->table('hrd_teams')->where('id', $teamId)->where('is_active', 1)->countAllResults() === 0) {
            return $this->error('Divisi HRD tidak valid atau sedang nonaktif.');
        }

        $database->transStart();
        $database->table('hrd_team_users')->where('user_id', $userId)->delete();
        if ($teamId > 0) {
            $database->table('hrd_team_users')->insert([
                'hrd_team_id' => $teamId,
                'user_id' => $userId,
                'assigned_by' => $this->currentUserId(),
                'assigned_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $database->transComplete();
        if (! $database->transStatus()) {
            return $this->error('Keanggotaan user gagal diperbarui.');
        }

        return $this->success($teamId > 0 ? 'User berhasil ditempatkan ke divisi HRD.' : 'User berhasil dikeluarkan dari divisi HRD.');
    }

    private function teamInput(string $form = 'create'): array|RedirectResponse
    {
        $name = mb_substr(trim((string) $this->request->getPost('name')), 0, 120);
        $code = mb_strtolower(trim((string) $this->request->getPost('code')));
        $code = preg_replace('/[^a-z0-9]+/', '-', $code) ?? '';
        $code = trim(mb_substr($code, 0, 60), '-');
        if ($name === '' || $code === '') {
            return $this->formError('Nama dan kode divisi wajib diisi.', $form);
        }

        return [
            'name' => $name,
            'code' => $code,
            'description' => mb_substr(trim((string) $this->request->getPost('description')), 0, 255) ?: null,
            'is_active' => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ];
    }

    private function currentUserId(): int
    {
        return (int) (session()->get('hrd_auth')['user_id'] ?? 0);
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/tim-hrd'))->with('hrd_team_success', $message);
    }

    private function error(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/tim-hrd'))->with('hrd_team_error', $message);
    }

    private function formError(string $message, string $form): RedirectResponse
    {
        return $this->error($message)->withInput()->with('hrd_team_form', $form);
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
