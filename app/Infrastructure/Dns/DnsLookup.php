<?php

declare(strict_types=1);

namespace App\Infrastructure\Dns;

use Throwable;

/**
 * Live DNS answers for the custom-domain check (BQ-154). Kept behind this class so the rule in
 * `App\Domain\Tenant\CustomDomain` stays free of network calls and tests can answer for the resolver.
 */
class DnsLookup
{
    /**
     * The CNAME targets of a host, lower case without the trailing dot.
     *
     * @return list<string>
     */
    public function cnameTargets(string $host): array
    {
        return $this->field($host, DNS_CNAME, 'target');
    }

    /**
     * The IPv4 addresses of a host.
     *
     * @return list<string>
     */
    public function ipv4(string $host): array
    {
        return $this->field($host, DNS_A, 'ip');
    }

    /**
     * @return list<string>
     */
    private function field(string $host, int $type, string $key): array
    {
        set_error_handler(static fn (): bool => true);
        try {
            $records = dns_get_record($host, $type);
        } catch (Throwable) {
            $records = false;
        } finally {
            restore_error_handler();
        }

        if (! is_array($records)) {
            return [];
        }

        $values = [];
        foreach ($records as $record) {
            if (isset($record[$key]) && is_string($record[$key])) {
                $values[] = rtrim(mb_strtolower($record[$key]), '.');
            }
        }

        return array_values(array_unique($values));
    }
}
