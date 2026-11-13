<?php

declare(strict_types=1);

namespace App\Service;

final class PasswordChangeService
{
    public function isCurrentPasswordValid(string $password, ?string $hash): bool
    {
        return $hash !== null && (new PasswordService())->verify($password, $hash);
    }

    public function validatePasswordPair(string $password, string $confirmation): ?string
    {
        if ($password !== $confirmation) {
            return 'mismatch';
        }
        if (strlen($password) < 6) {
            return 'short';
        }
        return null;
    }

    public function validateCode(
        string $provided,
        string $expected,
        int $expiration,
        int $now
    ): ?string {
        if ($provided != $expected) {
            return 'invalid';
        }
        if ($now > $expiration) {
            return 'expired';
        }
        return null;
    }

    public function secondsRemaining(int $expiration, int $now): int
    {
        return max(0, $expiration - $now);
    }
}
