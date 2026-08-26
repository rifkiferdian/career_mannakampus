<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class RecommendationAspectController extends BaseController
{
    private const INPUT_TYPES = [
        'scale_1_5' => 'Nilai 1–5',
        'yes_no' => 'Ya / Tidak',
        'choice' => 'Pilihan khusus',
        'text' => 'Teks singkat',
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);

        return view('admin/recommendation_aspects', [
            'auth' => $auth,
            'aspects' => db_connect()->table('recommendation_aspects')->where('deleted_at', null)->orderBy('display_order')->orderBy('id')->get()->getResultArray(),
            'inputTypes' => self::INPUT_TYPES,
            'canManage' => Services::authorization()->can($userId, 'recommendation.aspects.manage'),
            'success' => session()->getFlashdata('aspect_success'),
            'error' => session()->getFlashdata('aspect_error'),
            'openModal' => (string) (session()->getFlashdata('aspect_form') ?? ''),
        ]);
    }

    public function create(): RedirectResponse
    {
        return $this->save(null);
    }

    public function update(int $aspectId): RedirectResponse
    {
        return $this->save($aspectId);
    }

    public function toggle(int $aspectId): RedirectResponse
    {
        $aspect = $this->find($aspectId);
        if ($aspect === null) {
            return $this->failure('Aspek nilai tidak ditemukan.');
        }
        db_connect()->table('recommendation_aspects')->where('id', $aspectId)->update(['is_active' => (int) $aspect['is_active'] === 1 ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->success('Status aspek nilai berhasil diubah.');
    }

    public function delete(int $aspectId): RedirectResponse
    {
        if ($this->find($aspectId) === null) {
            return $this->failure('Aspek nilai tidak ditemukan.');
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('recommendation_aspects')->where('id', $aspectId)->update(['is_active' => 0, 'deleted_at' => $now, 'updated_at' => $now]);

        return $this->success('Aspek nilai berhasil dihapus.');
    }

    private function save(?int $aspectId): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description'));
        $inputType = trim((string) $this->request->getPost('input_type'));
        $order = (int) $this->request->getPost('display_order');
        $options = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', trim((string) $this->request->getPost('options'))) ?: []), static fn (string $option): bool => $option !== '')));
        if ($name === '' || mb_strlen($name) > 180) {
            return $this->failure('Nama aspek wajib diisi dan maksimal 180 karakter.', $aspectId);
        }
        if (mb_strlen($description) > 500) {
            return $this->failure('Petunjuk aspek maksimal 500 karakter.', $aspectId);
        }
        if (! array_key_exists($inputType, self::INPUT_TYPES)) {
            return $this->failure('Jenis jawaban aspek tidak valid.', $aspectId);
        }
        if ($order < 1 || $order > 999) {
            return $this->failure('Urutan aspek harus antara 1–999.', $aspectId);
        }
        if ($inputType === 'choice' && count($options) < 2) {
            return $this->failure('Pilihan khusus memerlukan minimal dua opsi.', $aspectId);
        }
        if (count($options) > 20 || array_filter($options, static fn (string $option): bool => mb_strlen($option) > 100) !== []) {
            return $this->failure('Maksimal 20 opsi dan 100 karakter untuk setiap opsi.', $aspectId);
        }
        if ($inputType === 'yes_no') {
            $options = ['Ya', 'Tidak'];
        } elseif ($inputType !== 'choice') {
            $options = [];
        }

        $data = [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'input_type' => $inputType,
            'options_json' => $options !== [] ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'is_required' => $this->request->getPost('is_required') !== null ? 1 : 0,
            'display_order' => $order,
            'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($aspectId === null) {
            $data['code'] = $this->uniqueCode($name);
            $data['created_at'] = $data['updated_at'];
            db_connect()->table('recommendation_aspects')->insert($data);
        } else {
            if ($this->find($aspectId) === null) {
                return $this->failure('Aspek nilai tidak ditemukan.', $aspectId);
            }
            db_connect()->table('recommendation_aspects')->where('id', $aspectId)->update($data);
        }

        return $this->success('Aspek nilai berhasil disimpan.');
    }

    /** @return array<string, mixed>|null */
    private function find(int $aspectId): ?array
    {
        return db_connect()->table('recommendation_aspects')->where('id', $aspectId)->where('deleted_at', null)->get()->getRowArray() ?: null;
    }

    private function uniqueCode(string $name): string
    {
        $base = mb_substr(mb_strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $name), '_')), 0, 65) ?: 'aspek';
        $code = $base;
        $suffix = 2;
        while (db_connect()->table('recommendation_aspects')->where('code', $code)->countAllResults() > 0) {
            $code = mb_substr($base, 0, 70) . '_' . $suffix++;
        }

        return $code;
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/aspek-penilaian'))->with('aspect_success', $message);
    }

    private function failure(string $message, ?int $aspectId = null): RedirectResponse
    {
        $form = $aspectId === null ? 'create' : 'edit-' . $aspectId;

        return redirect()->to(site_url('adminhrdmannakampus/aspek-penilaian'))->with('aspect_error', $message)->with('aspect_form', $form)->withInput();
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
