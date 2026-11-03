<?php

namespace Tests\Guardrails;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Enforces the module rules of ADR-0012 by scanning source files.
 *
 * Existing violations are listed per file with their count in
 * architecture-allowlist.php. The list may only shrink: a new violation fails,
 * and so does a fixed one until its allowlist entry is lowered or removed.
 */
class ArchitectureRulesTest extends TestCase
{
    public function test_provider_sdk_is_used_only_in_infrastructure(): void
    {
        $this->assertRule('provider-sdk', $this->countMatches(
            ['app', 'routes'],
            '/(?<![\w\\\\])\\\\?Midtrans\\\\[A-Z]/',
            ['app/Infrastructure/'],
        ));
    }

    public function test_blade_views_do_not_query_models(): void
    {
        $this->assertRule('blade-queries', $this->countMatches(
            ['resources/views'],
            '/\bDB::|\b[A-Z]\w*::(?:where\w*|find\w*|query|all|count|first|with|latest|create|update|delete)\(/',
        ));
    }

    public function test_slot_occupancy_rule_lives_only_in_booking_rules(): void
    {
        $graceWindow = $this->countMatches(['app'], '/\b(?:sub|add)Minutes\(\s*15\s*\)/', ['app/Domain/Booking/']);
        $activeStatusLists = $this->countCallback(['app'], function (string $source): int {
            preg_match_all("/whereIn\(\s*'(?:bookings\.)?status'\s*,\s*\[([^\]]*)\]/", $source, $lists);

            return count(array_filter($lists[1], fn (string $list): bool => preg_match('/pending/i', $list)
                && preg_match('/paid/i', $list)
                && preg_match('/completed/i', $list)));
        }, ['app/Domain/Booking/']);

        $this->assertRule('slot-occupancy', $this->sumCounts($graceWindow, $activeStatusLists));
    }

    public function test_plan_names_are_checked_only_in_entitlement_rules(): void
    {
        $this->assertRule('plan-name-checks', $this->countMatches(
            ['app', 'resources/views'],
            "/namapaket\)*\s*(?:===|!==|==|!=|,)\s*'/",
            ['app/Domain/Subscription/EntitlementRules.php'],
        ));
    }

    public function test_scope_bypasses_carry_a_reason_comment(): void
    {
        $this->assertRule('scope-bypass-reason', $this->countCallback(['app', 'routes'], function (string $source): int {
            $lines = explode("\n", $source);
            $unexplained = 0;

            foreach ($lines as $number => $line) {
                if (! preg_match('/withoutGlobalScopes?\(/', $line)) {
                    continue;
                }

                $context = implode("\n", array_slice($lines, max(0, $number - 2), min(3, $number + 1)));
                if (! str_contains($context, 'scope-bypass:')) {
                    $unexplained++;
                }
            }

            return $unexplained;
        }));
    }

    public function test_payment_success_and_failure_are_written_only_by_the_payment_lifecycle(): void
    {
        $this->assertRule('payment-status-writers', $this->countMatches(
            ['app'],
            "/'status'\s*=>\s*(?:PaymentState::STATUS_(?:SUKSES|GAGAL)|'(?:sukses|gagal)')|->status\s*=\s*(?:PaymentState::STATUS_(?:SUKSES|GAGAL)|'(?:sukses|gagal)')/",
            ['app/Domain/Payment/', 'app/Actions/Payment/PaymentLifecycle.php', 'app/Actions/Payment/Lifecycle/'],
        ));
    }

    /**
     * @param  array<string, int>  $violations
     */
    private function assertRule(string $rule, array $violations): void
    {
        /** @var array<string, array<string, int>> $allowlist */
        $allowlist = require __DIR__.'/architecture-allowlist.php';
        $allowed = $allowlist[$rule] ?? [];

        $grown = [];
        $shrunk = [];

        foreach ($violations as $path => $count) {
            $known = $allowed[$path] ?? 0;
            if ($count > $known) {
                $grown[] = "{$path}: {$count} (allowlisted {$known})";
            }
        }

        foreach ($allowed as $path => $known) {
            $count = $violations[$path] ?? 0;
            if ($count < $known) {
                $shrunk[] = "{$path}: now {$count}, allowlisted {$known}";
            }
        }

        $this->assertSame([], $grown, "New '{$rule}' violations (ADR-0012). Fix them instead of extending the allowlist:\n".implode("\n", $grown));
        $this->assertSame([], $shrunk, "'{$rule}' violations were fixed. Lower or remove these entries in tests/Guardrails/architecture-allowlist.php:\n".implode("\n", $shrunk));
    }

    /**
     * @param  list<string>  $directories
     * @param  list<string>  $exemptPrefixes
     * @return array<string, int>
     */
    private function countMatches(array $directories, string $pattern, array $exemptPrefixes = []): array
    {
        return $this->countCallback(
            $directories,
            fn (string $source): int => preg_match_all($pattern, $source),
            $exemptPrefixes,
        );
    }

    /**
     * @param  list<string>  $directories
     * @param  callable(string): int  $counter
     * @param  list<string>  $exemptPrefixes
     * @return array<string, int>
     */
    private function countCallback(array $directories, callable $counter, array $exemptPrefixes = []): array
    {
        $root = dirname(__DIR__, 2);
        $counts = [];

        foreach ($directories as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}", RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                foreach ($exemptPrefixes as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        continue 2;
                    }
                }

                $count = $counter(str_replace("\r\n", "\n", (string) file_get_contents($file->getPathname())));
                if ($count > 0) {
                    $counts[$path] = $count;
                }
            }
        }

        ksort($counts);

        return $counts;
    }

    /**
     * @param  array<string, int>  ...$countSets
     * @return array<string, int>
     */
    private function sumCounts(array ...$countSets): array
    {
        $total = [];
        foreach ($countSets as $counts) {
            foreach ($counts as $path => $count) {
                $total[$path] = ($total[$path] ?? 0) + $count;
            }
        }
        ksort($total);

        return $total;
    }
}
