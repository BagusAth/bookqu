<?php

namespace Tests\Feature\Ops;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class TenantCredentialRollbackTest extends TestCase
{
    /** @return array<string, array{bool, bool, bool, bool}> */
    public static function rollbackCases(): array
    {
        return [
            'first encryption release' => [false, true, false, true],
            'subsequent encrypted release' => [true, true, false, false],
            'failure before migrations' => [false, false, false, false],
            'crypto rollback failure' => [false, true, true, true],
        ];
    }

    #[DataProvider('rollbackCases')]
    public function test_automatic_rollback_preserves_credential_and_code_compatibility(bool $oldCodeEncrypted, bool $migrationsStarted, bool $cryptoFailure, bool $needsDecryption): void
    {
        $bash = PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/Git/bin/bash.exe' : '/bin/bash';
        if (! is_file($bash)) {
            $this->markTestSkipped('Bash is required to exercise the deployment rollback function.');
        }
        $source = file_get_contents(dirname(__DIR__, 3).'/.github/workflows/deploy-production.yml');
        $this->assertSame(1, preg_match('/(^            rollback\(\) \{.*?^            \})\r?\n            trap rollback EXIT/ms', $source, $body));
        $this->assertSame(1, preg_match('/CREDENTIALS_MIGRATION="([^"]+)"/', $source, $migration));
        $root = sys_get_temp_dir().'/bookqu-bq004-'.bin2hex(random_bytes(8));
        mkdir($root.'/current/database/migrations', 0700, true);
        mkdir($root.'/backup/database/migrations', 0700, true);
        mkdir($root.'/public', 0700, true);
        file_put_contents($root.'/current/'.$migration[1], 'new migration fixture');
        if ($oldCodeEncrypted) {
            file_put_contents($root.'/backup/'.$migration[1], 'existing migration fixture');
        }
        $script = <<<'BASH'
ROOT="$1"
MIGRATIONS_STARTED="$2"
FAIL_CRYPTO_DOWN="$3"
TRACE="$ROOT/trace"
LARAVEL_DIR="$ROOT/current"
BACKUP_DIR="$ROOT/backup"
PUBLIC_DIR="$ROOT/public"
DB_CNF=""
DB_BACKUP_FILE=""
DEPLOY_STARTED=1
MAINTENANCE_ON=0
php() {
    printf 'php:%s\n' "$*" >> "$TRACE"
    if [ "$FAIL_CRYPTO_DOWN" -eq 1 ] && [ "$2" = "migrate:rollback" ]; then return 1; fi
    return 0
}
cp() { printf 'copy:%s\n' "$*" >> "$TRACE"; }
rm() { printf 'remove:%s\n' "$*" >> "$TRACE"; }
BASH;
        $script .= "\nCREDENTIALS_MIGRATION=".escapeshellarg($migration[1])."\n".$body[1]."\nfalse\nrollback\n";
        file_put_contents($root.'/rollback.sh', $script);
        try {
            $process = new Process([$bash, $root.'/rollback.sh', $root, $migrationsStarted ? '1' : '0', $cryptoFailure ? '1' : '0']);
            $process->setTimeout(10);
            $process->run();
            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput());
            $calls = explode("\n", trim(file_get_contents($root.'/trace')));
            $reverse = null;
            $copy = null;
            foreach ($calls as $index => $call) {
                if (str_starts_with($call, 'php:artisan migrate:rollback ')) {
                    $reverse = $index;
                }
                if ($copy === null && str_starts_with($call, 'copy:')) {
                    $copy = $index;
                }
            }
            if ($needsDecryption) {
                $this->assertNotNull($reverse);
            } else {
                $this->assertNull($reverse);
            }
            if ($cryptoFailure) {
                $this->assertNull($copy);
            } else {
                $this->assertNotNull($copy);
                if ($needsDecryption) {
                    $this->assertLessThan($copy, $reverse);
                }
            }
        } finally {
            $resolved = realpath($root);
            $this->assertNotFalse($resolved);
            $this->assertStringStartsWith(realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'bookqu-bq004-', $resolved);
            (new Filesystem)->deleteDirectory($resolved);
        }
    }
}
