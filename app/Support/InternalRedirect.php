<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Keeps a redirect target inside this application (BQ-102 hardening). A same-site path ("/owner/…")
 * or an absolute URL on the current scheme and host is accepted; anything else, including
 * protocol-relative "//host" paths, gives null so the caller falls back to its own page.
 */
final class InternalRedirect
{
    public static function target(mixed $candidate, string $currentOrigin): ?string
    {
        if (! is_string($candidate) || trim($candidate) === '') {
            return null;
        }
        $candidate = trim($candidate);

        if (str_starts_with($candidate, '/')) {
            return str_starts_with($candidate, '//') || str_starts_with($candidate, '/\\') ? null : $candidate;
        }

        $parts = parse_url($candidate);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }
        $origin = strtolower($parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''));

        return $origin === strtolower(rtrim($currentOrigin, '/')) ? $candidate : null;
    }
}
