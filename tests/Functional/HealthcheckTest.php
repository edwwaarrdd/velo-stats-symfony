<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class HealthcheckTest extends DatabaseTestCase
{
    public function testItReportsOk(): void
    {
        self::assertSame(['message' => 'ok'], $this->getJson('/_healthcheck'));
    }
}
