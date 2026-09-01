<?php

namespace App\Libraries;

final class StorageSyncSignature
{
    public static function canonical(
        string $clientId,
        string $method,
        string $path,
        string $timestamp,
        string $nonce,
        string $body,
    ): string {
        return implode("\n", [
            $clientId,
            strtoupper($method),
            $path,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);
    }

    public static function sign(
        string $secret,
        string $clientId,
        string $method,
        string $path,
        string $timestamp,
        string $nonce,
        string $body,
    ): string {
        return hash_hmac('sha256', self::canonical(
            $clientId,
            $method,
            $path,
            $timestamp,
            $nonce,
            $body,
        ), $secret);
    }
}
