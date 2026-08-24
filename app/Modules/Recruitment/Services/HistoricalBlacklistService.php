<?php

namespace App\Modules\Recruitment\Services;

use CodeIgniter\Database\BaseConnection;

class HistoricalBlacklistService
{
    public function __construct(private readonly BaseConnection $database)
    {
    }

    /** @return array<string, mixed>|null */
    public function matchActive(string $nikHash, string $email, string $phone, ?string $now = null): ?array
    {
        $now ??= date('Y-m-d H:i:s');
        $email = self::normalizeEmail($email);
        $phone = self::normalizePhone($phone);

        $builder = $this->database->table('historical_blacklists')
            ->where('revoked_at', null)
            ->where('starts_at <=', $now)
            ->groupStart()->where('is_permanent', 1)->orWhere('ends_at >=', $now)->groupEnd()
            ->groupStart();

        $hasIdentifier = false;
        if ($nikHash !== '') {
            $builder->where('nik_hash', $nikHash);
            $hasIdentifier = true;
        }
        if ($email !== '') {
            $hasIdentifier ? $builder->orWhere('email', $email) : $builder->where('email', $email);
            $hasIdentifier = true;
        }
        if ($phone !== '') {
            $hasIdentifier ? $builder->orWhere('phone', $phone) : $builder->where('phone', $phone);
            $hasIdentifier = true;
        }
        if (! $hasIdentifier) {
            return null;
        }

        $match = $builder->groupEnd()->orderBy('id', 'DESC')->get()->getRowArray();
        if ($match !== null) {
            $this->database->table('historical_blacklists')->where('id', $match['id'])->set('match_count', 'match_count + 1', false)->update([
                'last_matched_at' => $now,
            ]);
        }

        return $match;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
}
