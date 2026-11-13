<?php

declare(strict_types=1);

namespace App\Service;

final class SecurityService
{
    public function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function isValidCsrfToken(string $expected, string $provided): bool
    {
        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }

    public function isRateLimited(array $entry, int $now): bool
    {
        return ($entry['bloqueado_hasta'] ?? 0) > $now;
    }

    public function registerFailure(array $entry, int $now, int $maximum, int $seconds): array
    {
        if ($this->isRateLimited($entry, $now)) {
            return $entry;
        }

        $entry['intentos'] = ($entry['intentos'] ?? 0) + 1;
        $entry['bloqueado_hasta'] = $entry['bloqueado_hasta'] ?? 0;
        if ($entry['intentos'] >= $maximum) {
            $entry['bloqueado_hasta'] = $now + $seconds;
            $entry['intentos'] = 0;
        }

        return $entry;
    }
}
