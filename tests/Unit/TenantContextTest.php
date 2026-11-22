<?php

namespace Tests\Unit;

use App\Support\TenantContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TenantContextTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_runner_restores_context_after_nested_work_and_exceptions(): void
    {
        $context = new TenantContext;
        $context->setTenantId(10);

        try {
            $context->run(20, function () use ($context): void {
                $this->assertSame(20, $context->getTenantId());
                $this->assertSame(30, $context->run(30, $context->getTenantId(...)));
                $this->assertSame(20, $context->getTenantId());

                throw new RuntimeException('fixture');
            });
            $this->fail('Expected the runner to propagate the exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('fixture', $exception->getMessage());
        }

        $this->assertSame(10, $context->getTenantId());
        $context->clear();
        $this->assertSame(20, $context->run(20, $context->getTenantId(...)));
        $this->assertNull($context->getTenantId());
    }
}
