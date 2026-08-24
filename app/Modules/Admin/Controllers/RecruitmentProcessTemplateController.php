<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class RecruitmentProcessTemplateController extends BaseController
{
    public function index(): string
    {
        $this->disableClientCaching();
        $database = db_connect();
        $templates = $database->table('recruitment_process_templates AS templates')
            ->select('templates.*, COUNT(DISTINCT vacancies.id) AS vacancy_count')
            ->join('vacancies', 'vacancies.recruitment_process_template_id = templates.id AND vacancies.deleted_at IS NULL', 'left')
            ->groupBy('templates.id')->orderBy('templates.name')->get()->getResultArray();
        $stageRows = $database->table('recruitment_process_template_stages AS links')
            ->select('links.template_id, links.stage_id, links.display_order, stages.code, stages.name, stages.color_hex')
            ->join('recruitment_stages AS stages', 'stages.id = links.stage_id')
            ->orderBy('links.template_id')->orderBy('links.display_order')->get()->getResultArray();
        $stagesByTemplate = [];
        foreach ($stageRows as $row) {
            $stagesByTemplate[(int) $row['template_id']][] = $row;
        }
        foreach ($templates as &$template) {
            $template['stages'] = $stagesByTemplate[(int) $template['id']] ?? [];
        }
        unset($template);
        $stageCatalog = $database->table('recruitment_stages AS stages')
            ->select('stages.*, (SELECT COUNT(*) FROM recruitment_process_template_stages links WHERE links.stage_id = stages.id) AS template_count, (SELECT COUNT(*) FROM applications apps WHERE apps.application_status = stages.code AND apps.deleted_at IS NULL) AS application_count', false)
            ->orderBy('stages.display_order')->get()->getResultArray();

        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);
        return view('admin/recruitment_process_templates', [
            'auth' => $auth,
            'templates' => $templates,
            'stageCatalog' => $stageCatalog,
            'stages' => $database->table('recruitment_stages')->where('is_active', 1)->where('code !=', 'rejected')->orderBy('display_order')->get()->getResultArray(),
            'canManage' => Services::authorization()->can($userId, 'recruitment.templates.manage'),
            'success' => session()->getFlashdata('template_success'),
            'error' => session()->getFlashdata('template_error'),
        ]);
    }

    public function create(): RedirectResponse
    {
        $data = $this->validatedInput();
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        [$template, $stages] = $data;
        $database = db_connect();
        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('recruitment_process_templates')->insert($template + ['created_at' => $now, 'updated_at' => $now]);
        $this->replaceStages((int) $database->insertID(), $stages, $now);
        $database->transComplete();
        return $database->transStatus() ? $this->success('Template tahapan berhasil ditambahkan.') : $this->error('Template tahapan gagal ditambahkan.');
    }

    public function update(int $id): RedirectResponse
    {
        if (! $this->exists($id)) {
            return $this->error('Template tahapan tidak ditemukan.');
        }
        $data = $this->validatedInput($id);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        [$template, $stages] = $data;
        $database = db_connect();
        $now = date('Y-m-d H:i:s');
        $database->transStart();
        $database->table('recruitment_process_templates')->where('id', $id)->update($template + ['updated_at' => $now]);
        $database->table('recruitment_process_template_stages')->where('template_id', $id)->delete();
        $this->replaceStages($id, $stages, $now);
        $database->transComplete();
        return $database->transStatus() ? $this->success('Template tahapan berhasil diperbarui.') : $this->error('Template tahapan gagal diperbarui.');
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->exists($id)) {
            return $this->error('Template tahapan tidak ditemukan.');
        }
        if (db_connect()->table('vacancies')->where('recruitment_process_template_id', $id)->where('deleted_at', null)->countAllResults() > 0) {
            return $this->error('Template sedang dipakai lowongan dan tidak dapat dihapus. Ubah template lowongannya terlebih dahulu.');
        }
        db_connect()->table('recruitment_process_templates')->where('id', $id)->delete();
        return $this->success('Template tahapan berhasil dihapus permanen.');
    }

    public function createStage(): RedirectResponse
    {
        $data = $this->validatedStageInput();
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        $database = db_connect();
        $data['display_order'] = (int) ($database->table('recruitment_stages')->selectMax('display_order')->get()->getRowArray()['display_order'] ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        try {
            $database->table('recruitment_stages')->insert($data + ['is_terminal' => 0, 'created_at' => $now, 'updated_at' => $now]);
        } catch (\Throwable) {
            return $this->error('Jenis tahap gagal ditambahkan. Pastikan kodenya belum digunakan.', '#stage-types');
        }
        return $this->success('Jenis tahap berhasil ditambahkan.', '#stage-types');
    }

    public function updateStage(int $id): RedirectResponse
    {
        $stage = db_connect()->table('recruitment_stages')->where('id', $id)->get()->getRowArray();
        if ($stage === null) {
            return $this->error('Jenis tahap tidak ditemukan.', '#stage-types');
        }
        $data = $this->validatedStageInput($stage);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        unset($data['code']);
        $isProtected = in_array((string) $stage['code'], ['accepted', 'rejected'], true);
        $isUsed = db_connect()->table('recruitment_process_template_stages')->where('stage_id', $id)->countAllResults() > 0;
        if (($isProtected || $isUsed) && (int) $data['is_active'] === 0) {
            return $this->error($isProtected ? 'Tahap sistem wajib tetap aktif.' : 'Jenis tahap masih digunakan template dan tidak dapat dinonaktifkan.', '#stage-types');
        }
        if ($isProtected) {
            $data['is_active'] = 1;
            $data['sla_days'] = 0;
        }
        db_connect()->table('recruitment_stages')->where('id', $id)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
        return $this->success('Jenis tahap berhasil diperbarui.', '#stage-types');
    }

    public function deleteStage(int $id): RedirectResponse
    {
        $stage = db_connect()->table('recruitment_stages')->where('id', $id)->get()->getRowArray();
        if ($stage === null) {
            return $this->error('Jenis tahap tidak ditemukan.', '#stage-types');
        }
        if (in_array((string) $stage['code'], ['accepted', 'rejected'], true)) {
            return $this->error('Tahap Diterima dan Ditolak merupakan tahap sistem dan tidak dapat dihapus.', '#stage-types');
        }
        $templateCount = db_connect()->table('recruitment_process_template_stages')->where('stage_id', $id)->countAllResults();
        $applicationCount = db_connect()->table('applications')->where('application_status', $stage['code'])->where('deleted_at', null)->countAllResults();
        if ($templateCount > 0 || $applicationCount > 0) {
            return $this->error('Jenis tahap sedang digunakan template atau kandidat dan tidak dapat dihapus.', '#stage-types');
        }
        db_connect()->table('recruitment_stages')->where('id', $id)->delete();
        return $this->success('Jenis tahap berhasil dihapus permanen.', '#stage-types');
    }

    /** @return array{0: array<string, mixed>, 1: list<int>}|RedirectResponse */
    private function validatedInput(?int $currentId = null): array|RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $code = mb_strtolower(trim((string) $this->request->getPost('code')));
        $description = mb_substr(trim((string) $this->request->getPost('description')), 0, 500);
        if ($name === '' || mb_strlen($name) > 150 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $code) !== 1 || mb_strlen($code) > 80) {
            return $this->error('Nama atau kode template tidak valid.');
        }
        $duplicate = db_connect()->table('recruitment_process_templates')->where('code', $code);
        if ($currentId !== null) {
            $duplicate->where('id !=', $currentId);
        }
        if ($duplicate->countAllResults() > 0) {
            return $this->error('Kode template sudah digunakan.');
        }
        $selected = array_values(array_unique(array_map('intval', (array) $this->request->getPost('stage_ids'))));
        $orders = (array) $this->request->getPost('stage_order');
        $validRows = $selected === [] ? [] : db_connect()->table('recruitment_stages')->select('id, code')->whereIn('id', $selected)->where('is_active', 1)->where('code !=', 'rejected')->get()->getResultArray();
        if (count($validRows) !== count($selected)) {
            return $this->error('Pilihan tahapan tidak valid.');
        }
        $validCodes = array_column($validRows, 'code', 'id');
        if (count($selected) < 2 || ! in_array('accepted', $validCodes, true)) {
            return $this->error('Template minimal memiliki dua tahap dan wajib diakhiri dengan Diterima.');
        }
        usort($selected, static fn (int $a, int $b): int => ((int) ($orders[$a] ?? 999)) <=> ((int) ($orders[$b] ?? 999)) ?: $a <=> $b);
        $acceptedId = (int) array_search('accepted', $validCodes, true);
        $selected = array_values(array_filter($selected, static fn (int $id): bool => $id !== $acceptedId));
        $selected[] = $acceptedId;
        return [[
            'code' => $code,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0,
        ], $selected];
    }

    /** @param list<int> $stages */
    private function replaceStages(int $templateId, array $stages, string $now): void
    {
        foreach ($stages as $index => $stageId) {
            db_connect()->table('recruitment_process_template_stages')->insert(['template_id' => $templateId, 'stage_id' => $stageId, 'display_order' => $index + 1, 'created_at' => $now]);
        }
    }

    /** @param array<string, mixed>|null $existing */
    private function validatedStageInput(?array $existing = null): array|RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $code = mb_strtolower(trim((string) $this->request->getPost('code')));
        $color = mb_strtoupper(trim((string) $this->request->getPost('color_hex')));
        $slaDays = (int) $this->request->getPost('sla_days');
        if ($name === '' || mb_strlen($name) > 100 || preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $code) !== 1 || mb_strlen($code) > 50 || preg_match('/^#[0-9A-F]{6}$/', $color) !== 1 || $slaDays < 0 || $slaDays > 365) {
            return $this->error('Nama, kode, warna, atau batas waktu jenis tahap tidak valid.', '#stage-types');
        }
        if ($existing === null && db_connect()->table('recruitment_stages')->where('code', $code)->countAllResults() > 0) {
            return $this->error('Kode jenis tahap sudah digunakan.', '#stage-types');
        }
        return [
            'code' => $existing['code'] ?? $code,
            'name' => $name,
            'color_hex' => $color,
            'sla_days' => $slaDays,
            'is_schedulable' => $this->request->getPost('is_schedulable') !== null ? 1 : 0,
            'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0,
        ];
    }

    private function exists(int $id): bool
    {
        return db_connect()->table('recruitment_process_templates')->where('id', $id)->countAllResults() > 0;
    }

    private function success(string $message, string $fragment = ''): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/template-tahapan') . $fragment)->with('template_success', $message);
    }

    private function error(string $message, string $fragment = ''): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/template-tahapan') . $fragment)->withInput()->with('template_error', $message);
    }

    private function disableClientCaching(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
