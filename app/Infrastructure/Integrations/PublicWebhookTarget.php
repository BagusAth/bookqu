<?php

declare(strict_types=1);

namespace App\Infrastructure\Integrations;

use App\Infrastructure\Dns\DnsLookup;

final class PublicWebhookTarget
{
    public function __construct(private DnsLookup $dns) {}

    /** @return array{host: string, port: int, address: string, literal: bool}|null */
    public function resolve(string $url): ?array
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url) === 1) {
            return null;
        }
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return null;
        }
        $host = trim(strtolower($parts['host']), '[]');
        $port = $parts['port'] ?? 443;
        if ($port < 1) {
            return null;
        }

        $literal = filter_var($host, FILTER_VALIDATE_IP) !== false;
        if (! $literal && (! str_contains($host, '.') || preg_match('/\.[a-z][a-z0-9-]*$/D', $host) !== 1
            || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)) {
            return null;
        }
        $addresses = $literal ? [$host] : [...$this->dns->ipv4($host), ...$this->dns->ipv6($host)];
        if ($addresses === [] || array_any($addresses, fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false)) {
            return null;
        }

        return ['host' => $host, 'port' => $port, 'address' => $addresses[0], 'literal' => $literal];
    }
}
