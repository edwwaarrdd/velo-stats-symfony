<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Marks a class as serving one HTTP endpoint from its __invoke method.
 *
 * It carries no methods. Its job is to let the container tag every handler at
 * once, so a new endpoint is a new class and nothing else.
 */
interface RequestHandler
{
}
