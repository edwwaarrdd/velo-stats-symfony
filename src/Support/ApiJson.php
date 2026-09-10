<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiJson
{
    /**
     * Zero-fraction preservation is the one that matters: without it a
     * distance of 1500.0 metres is encoded as the integer 1500, and clients
     * see the type of a field change with its value.
     */
    public const int ENCODING_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param array<string, mixed> $data
     */
    public static function response(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse(null, $status);
        $response->setEncodingOptions(self::ENCODING_OPTIONS);
        $response->setData($data);

        return $response;
    }
}
