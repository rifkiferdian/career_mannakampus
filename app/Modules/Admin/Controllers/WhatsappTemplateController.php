<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;

class WhatsappTemplateController extends BaseController
{
    private const CATEGORIES = [
        'contact' => 'Kontak awal',
        'schedule' => 'Jadwal seleksi',
        'reminder' => 'Pengingat',
        'confirmation' => 'Konfirmasi',
        'progress' => 'Tahap rekrutmen',
        'result' => 'Hasil seleksi',
        'other' => 'Lainnya',
    ];

    private const VARIABLES = [
        'nama_pelamar' => 'Nama pelamar',
        'nama_recruiter' => 'Nama recruiter',
        'nama_lowongan' => 'Nama lowongan',
        'nama_tahap' => 'Nama tahap',
        'tanggal' => 'Tanggal pelaksanaan',
        'jam' => 'Jam pelaksanaan',
        'lokasi' => 'Lokasi atau link meeting',
        'nama_pic' => 'Nama PIC',
        'instruksi' => 'Instruksi kandidat',
        'batas_konfirmasi' => 'Batas konfirmasi',
        'tahap_sebelumnya' => 'Tahap sebelumnya',
        'tahap_berikutnya' => 'Tahap berikutnya',
    ];

    public function index(): string
    {
        $this->disableClientCaching();
        $auth = session()->get('hrd_auth');
        $userId = (int) ($auth['user_id'] ?? 0);

        return view('admin/whatsapp_templates', [
            'auth' => $auth,
            'templates' => db_connect()->table('whatsapp_message_templates')->where('deleted_at', null)->orderBy('display_order')->orderBy('id')->get()->getResultArray(),
            'categories' => self::CATEGORIES,
            'variables' => self::VARIABLES,
            'canManage' => Services::authorization()->can($userId, 'whatsapp.templates.manage'),
            'success' => session()->getFlashdata('whatsapp_template_success'),
            'error' => session()->getFlashdata('whatsapp_template_error'),
            'openModal' => (string) (session()->getFlashdata('whatsapp_template_form') ?? ''),
        ]);
    }

    public function create(): RedirectResponse
    {
        return $this->save(null);
    }

    public function update(int $templateId): RedirectResponse
    {
        return $this->save($templateId);
    }

    public function toggle(int $templateId): RedirectResponse
    {
        $template = $this->find($templateId);
        if ($template === null) {
            return $this->failure('Template WhatsApp tidak ditemukan.');
        }
        db_connect()->table('whatsapp_message_templates')->where('id', $templateId)->update([
            'is_active' => (int) $template['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success('Status template WhatsApp berhasil diubah.');
    }

    public function delete(int $templateId): RedirectResponse
    {
        if ($this->find($templateId) === null) {
            return $this->failure('Template WhatsApp tidak ditemukan.');
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('whatsapp_message_templates')->where('id', $templateId)->update(['is_active' => 0, 'deleted_at' => $now, 'updated_at' => $now]);

        return $this->success('Template WhatsApp berhasil dihapus.');
    }

    private function save(?int $templateId): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $category = trim((string) $this->request->getPost('category'));
        $message = trim((string) $this->request->getPost('message_text'));
        $order = (int) $this->request->getPost('display_order');
        if ($name === '' || mb_strlen($name) > 150) {
            return $this->failure('Nama template wajib diisi dan maksimal 150 karakter.', $templateId);
        }
        if (! array_key_exists($category, self::CATEGORIES)) {
            return $this->failure('Kategori template tidak valid.', $templateId);
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
            return $this->failure('Isi pesan harus antara 10-2.000 karakter.', $templateId);
        }
        if ($order < 1 || $order > 999) {
            return $this->failure('Urutan template harus antara 1-999.', $templateId);
        }
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/iu', $message, $matches);
        $unknownVariables = array_values(array_diff(array_unique($matches[1] ?? []), array_keys(self::VARIABLES)));
        if ($unknownVariables !== []) {
            return $this->failure('Variabel tidak dikenal: {{' . implode('}}, {{', $unknownVariables) . '}}.', $templateId);
        }

        $data = [
            'name' => $name,
            'category' => $category,
            'message_text' => $message,
            'display_order' => $order,
            'is_active' => $this->request->getPost('is_active') !== null ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($templateId === null) {
            $data['code'] = $this->uniqueCode($name);
            $data['created_at'] = $data['updated_at'];
            db_connect()->table('whatsapp_message_templates')->insert($data);
        } else {
            if ($this->find($templateId) === null) {
                return $this->failure('Template WhatsApp tidak ditemukan.', $templateId);
            }
            db_connect()->table('whatsapp_message_templates')->where('id', $templateId)->update($data);
        }

        return $this->success('Template WhatsApp berhasil disimpan.');
    }

    /** @return array<string, mixed>|null */
    private function find(int $templateId): ?array
    {
        return db_connect()->table('whatsapp_message_templates')->where('id', $templateId)->where('deleted_at', null)->get()->getRowArray() ?: null;
    }

    private function uniqueCode(string $name): string
    {
        $base = mb_substr(mb_strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $name), '_')), 0, 65) ?: 'template_wa';
        $code = $base;
        $suffix = 2;
        while (db_connect()->table('whatsapp_message_templates')->where('code', $code)->countAllResults() > 0) {
            $code = mb_substr($base, 0, 70) . '_' . $suffix++;
        }

        return $code;
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/template-whatsapp'))->with('whatsapp_template_success', $message);
    }

    private function failure(string $message, ?int $templateId = null): RedirectResponse
    {
        return redirect()->to(site_url('adminhrdmannakampus/template-whatsapp'))
            ->with('whatsapp_template_error', $message)
            ->with('whatsapp_template_form', $templateId === null ? 'create' : 'edit-' . $templateId)
            ->withInput();
    }

    private function disableClientCaching(): void
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->setHeader('Pragma', 'no-cache');
    }
}
