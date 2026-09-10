<?php

declare(strict_types=1);

namespace App\Health\RequestHandler;

use App\Support\ApiJson;
use App\Support\RequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Answers whether the process is up. It deliberately touches neither the
 * database nor Redis: container orchestration uses this to decide whether to
 * route traffic here, and a slow database should not take the process out.
 */
final class HealthcheckHandler implements RequestHandler
{
    #[Route('/_healthcheck', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiJson::response(['message' => 'ok']);
    }
}
