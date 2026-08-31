<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Security\Security;

class CsrfFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?RedirectResponse
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        /** @var Security $security */
        $security = service('security');

        try {
            $security->verify($request);
        } catch (SecurityException $exception) {
            if ($this->isApplicationSubmission($request)) {
                log_message('warning', '[Recruitment] Pengiriman lamaran ditolak karena token CSRF tidak valid. Path: {path}; IP: {ip}', [
                    'path' => $request->getUri()->getPath(),
                    'ip'   => $request->getIPAddress(),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with(
                        'form_error',
                        'Sesi formulir telah berakhir. Data Anda sudah dipulihkan; silakan pilih kembali berkas lamaran, lalu kirim ulang.',
                    );
            }

            if ($security->shouldRedirect() && ! $request->isAJAX()) {
                return redirect()->back()->with('error', $exception->getMessage());
            }

            throw $exception;
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): null
    {
        return null;
    }

    private function isApplicationSubmission(IncomingRequest $request): bool
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return false;
        }

        $path = trim($request->getUri()->getPath(), '/');

        return preg_match('#(?:^|/)lowongan/[^/]+/lamar$#', $path) === 1;
    }
}
