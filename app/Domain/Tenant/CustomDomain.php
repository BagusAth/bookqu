<?php

declare(strict_types=1);

namespace App\Domain\Tenant;

/**
 * The own domain of a Pro business (BQ-154, D-005, owner-portal.md §8.9).
 *
 * A domain is stored normalised (lower case, without scheme, path, port or trailing dot), is unique
 * across businesses (AC-HBK-04) and never names the platform itself. It resolves to the storefront
 * only while its last DNS check is `connected` (INV-TEN-01: one domain, one business).
 */
final class CustomDomain
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_NOT_CONNECTED = 'not_connected';

    private const PLATFORM_DOMAINS = ['bookqu.my.id', 'bookqu.test', 'localhost'];

    /**
     * What the owner typed, reduced to a host name; '' when nothing is left.
     */
    public static function normalize(string $input): string
    {
        $host = mb_strtolower(trim($input));
        $host = (string) preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $host);
        $host = (string) preg_replace('#[/?\#].*$#', '', $host);

        return rtrim($host, '.');
    }

    /**
     * Whether a normalised host may be used as a business's own domain.
     */
    public static function isAllowed(string $host): bool
    {
        if ($host === '' || strlen($host) > 253 || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        $label = '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?';
        if (preg_match('/^(?:'.$label.'\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{1,59})$/', $host) !== 1) {
            return false;
        }

        foreach (self::platformDomains() as $platform) {
            if ($host === $platform || str_ends_with($host, '.'.$platform)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the DNS answer points the domain at the platform: its CNAME is the configured target,
     * or one of its A records is a configured address. Nothing connects while neither is configured.
     *
     * @param  list<string>  $cnameTargets
     * @param  list<string>  $ipv4
     * @param  list<string>  $configuredAddresses
     */
    public static function pointsToPlatform(array $cnameTargets, array $ipv4, string $configuredTarget, array $configuredAddresses): bool
    {
        $target = self::normalize($configuredTarget);
        $cnames = array_map(self::normalize(...), $cnameTargets);

        return ($target !== '' && in_array($target, $cnames, true))
            || array_intersect($ipv4, $configuredAddresses) !== [];
    }

    /**
     * @return list<string>
     */
    private static function platformDomains(): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return array_values(array_unique(array_filter([...self::PLATFORM_DOMAINS, is_string($appHost) ? mb_strtolower($appHost) : null])));
    }
}
