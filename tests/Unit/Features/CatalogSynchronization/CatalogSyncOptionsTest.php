<?php

namespace Tests\Unit\Features\CatalogSynchronization;

use App\Features\CatalogSynchronization\Options\CatalogSyncOptions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CatalogSyncOptionsTest extends TestCase
{
    public function test_the_default_options_are_a_dry_run_without_a_limit(): void
    {
        $options = new CatalogSyncOptions;

        $this->assertTrue($options->dryRun);
        $this->assertNull($options->limit);
        $this->assertFalse($options->willExecute());
    }

    public function test_it_creates_an_explicit_limited_dry_run(): void
    {
        $options = CatalogSyncOptions::dryRun(limit: 10);

        $this->assertTrue($options->dryRun);
        $this->assertSame(10, $options->limit);
        $this->assertFalse($options->willExecute());
    }

    public function test_it_creates_an_explicit_limited_execution(): void
    {
        $options = CatalogSyncOptions::execute(limit: 1);

        $this->assertFalse($options->dryRun);
        $this->assertSame(1, $options->limit);
        $this->assertTrue($options->willExecute());
    }

    public function test_it_rejects_a_zero_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Catalog synchronization limit must be a positive integer.'
        );

        CatalogSyncOptions::dryRun(limit: 0);
    }

    public function test_it_rejects_a_negative_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CatalogSyncOptions::execute(limit: -1);
    }
}
