<?php

namespace App\Modules\Admin\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Session as SessionConfig;

class HrdSessionService
{
    private const TOKEN_SESSION_KEY = 'hrd_session_token';

    public function __construct(
        private readonly BaseConnection $database,
    ) {
    }

    public function register(int $userId, string $email, string $ipAddress, string $userAgent): void
    {
        $now = date('Y-m-d H:i:s');
        $sessionHash = $this->issueStableSessionHash();
        $data = [
            'user_id'          => $userId,
            'session_hash'     => $sessionHash,
            'ip_address'       => mb_substr($ipAddress, 0, 45),
            'user_agent'       => mb_substr($userAgent, 0, 500),
            'device_label'     => $this->deviceLabel($userAgent),
            'last_activity_at' => $now,
            'expires_at'       => $this->expirationTime(),
            'revoked_at'       => null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $existing = $this->database->table('user_sessions')
            ->select('id')
            ->where('session_hash', $sessionHash)
            ->get()
            ->getRowArray();

        if ($existing === null) {
            $this->database->table('user_sessions')->insert($data);
        } else {
            unset($data['created_at']);
            $this->database->table('user_sessions')->where('id', $existing['id'])->update($data);
        }

        $this->recordEvent($userId, $email, 'login', true, $ipAddress, $userAgent);
    }

    public function validateAndTouch(int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        $hasStableToken = $this->hasStableToken();
        $session = $this->database->table('user_sessions')
            ->select('id, last_activity_at')
            ->where('user_id', $userId)
            ->where('session_hash', $this->currentSessionHash())
            ->where('revoked_at', null)
            ->where('expires_at >', $now)
            ->get()
            ->getRowArray();

        if ($session === null) {
            return false;
        }

        // Upgrade sessions created before stable tokens were introduced. Once
        // upgraded, CodeIgniter may rotate its own session ID without logging
        // the HRD user out of the portal.
        if (! $hasStableToken) {
            $this->database->table('user_sessions')->where('id', $session['id'])->update([
                'session_hash' => $this->issueStableSessionHash(),
                'updated_at' => $now,
            ]);
        }

        if (strtotime((string) $session['last_activity_at']) < time() - 60) {
            $this->database->table('user_sessions')->where('id', $session['id'])->update([
                'last_activity_at' => $now,
                'expires_at'       => $this->expirationTime(),
                'updated_at'       => $now,
            ]);
        }

        return true;
    }

    public function revokeCurrent(int $userId): void
    {
        $this->database->table('user_sessions')
            ->where('user_id', $userId)
            ->where('session_hash', $this->currentSessionHash())
            ->where('revoked_at', null)
            ->update(['revoked_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function clearCurrentToken(): void
    {
        session()->remove(self::TOKEN_SESSION_KEY);
    }

    public function revokeAll(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->database->table('user_sessions')
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->update(['revoked_at' => $now, 'updated_at' => $now]);
    }

    public function revokeOthers(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->database->table('user_sessions')
            ->where('user_id', $userId)
            ->where('session_hash !=', $this->currentSessionHash())
            ->where('revoked_at', null)
            ->update(['revoked_at' => $now, 'updated_at' => $now]);
    }

    public function revokeById(int $userId, int $sessionId): bool
    {
        $session = $this->database->table('user_sessions')
            ->select('session_hash')
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->get()
            ->getRowArray();

        if ($session === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->database->table('user_sessions')->where('id', $sessionId)->update([
            'revoked_at' => $now,
            'updated_at' => $now,
        ]);

        return hash_equals((string) $session['session_hash'], $this->currentSessionHash());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeSessions(int $userId): array
    {
        $sessions = $this->database->table('user_sessions')
            ->select('id, session_hash, ip_address, device_label, last_activity_at, created_at')
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('last_activity_at', 'DESC')
            ->get()
            ->getResultArray();
        $currentHash = $this->currentSessionHash();

        return array_map(static function (array $session) use ($currentHash): array {
            $session['is_current'] = hash_equals((string) $session['session_hash'], $currentHash);
            unset($session['session_hash']);

            return $session;
        }, $sessions);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loginHistory(int $userId, int $limit = 12): array
    {
        return $this->database->table('user_login_history')
            ->select('event_type, was_successful, ip_address, device_label, occurred_at')
            ->where('user_id', $userId)
            ->orderBy('occurred_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function recordEvent(
        ?int $userId,
        string $email,
        string $eventType,
        bool $wasSuccessful,
        string $ipAddress,
        string $userAgent,
    ): void {
        $this->database->table('user_login_history')->insert([
            'user_id'        => $userId,
            'email'          => mb_substr(mb_strtolower(trim($email)), 0, 190),
            'event_type'     => mb_substr($eventType, 0, 40),
            'was_successful' => $wasSuccessful ? 1 : 0,
            'ip_address'     => mb_substr($ipAddress, 0, 45),
            'user_agent'     => mb_substr($userAgent, 0, 500),
            'device_label'   => $this->deviceLabel($userAgent),
            'occurred_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    private function currentSessionHash(): string
    {
        $token = session()->get(self::TOKEN_SESSION_KEY);
        if ($this->validToken($token)) {
            return hash('sha256', $token);
        }

        return hash('sha256', session_id());
    }

    private function issueStableSessionHash(): string
    {
        $token = session()->get(self::TOKEN_SESSION_KEY);
        if (! $this->validToken($token)) {
            $token = bin2hex(random_bytes(32));
            session()->set(self::TOKEN_SESSION_KEY, $token);
        }

        return hash('sha256', $token);
    }

    private function hasStableToken(): bool
    {
        return $this->validToken(session()->get(self::TOKEN_SESSION_KEY));
    }

    private function validToken(mixed $token): bool
    {
        return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
    }

    private function expirationTime(): string
    {
        $expiration = (new SessionConfig())->expiration;
        $expiration = $expiration > 0 ? $expiration : 7200;

        return date('Y-m-d H:i:s', time() + $expiration);
    }

    private function deviceLabel(string $userAgent): string
    {
        $browser = 'Browser tidak dikenal';
        $platform = 'Perangkat tidak dikenal';

        foreach (['Edg' => 'Microsoft Edge', 'OPR' => 'Opera', 'Chrome' => 'Google Chrome', 'Firefox' => 'Mozilla Firefox', 'Safari' => 'Safari'] as $needle => $label) {
            if (stripos($userAgent, $needle) !== false) {
                $browser = $label;
                break;
            }
        }

        foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad', 'Macintosh' => 'macOS', 'Linux' => 'Linux'] as $needle => $label) {
            if (stripos($userAgent, $needle) !== false) {
                $platform = $label;
                break;
            }
        }

        return $browser . ' · ' . $platform;
    }
}
