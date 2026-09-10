<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Support\ApiJson;
use PHPUnit\Framework\TestCase;

final class ApiJsonTest extends TestCase
{
    /**
     * Without JSON_PRESERVE_ZERO_FRACTION a distance of 1500.0 metres encodes
     * as the integer 1500, so a field's type would change with its value.
     */
    public function testItKeepsTheFractionOnWholeFloats(): void
    {
        $response = ApiJson::response(['distance_meters' => 1500.0]);

        self::assertSame('{"distance_meters":1500.0}', $response->getContent());
    }

    public function testItLeavesSlashesAndUnicodeUnescaped(): void
    {
        $response = ApiJson::response(['name' => 'Sint-Andries / Café Zoë']);

        self::assertSame('{"name":"Sint-Andries / Café Zoë"}', $response->getContent());
    }

    public function testItDefaultsToOkAndAcceptsAnotherStatus(): void
    {
        self::assertSame(200, ApiJson::response([])->getStatusCode());
        self::assertSame(503, ApiJson::response([], 503)->getStatusCode());
    }
}
