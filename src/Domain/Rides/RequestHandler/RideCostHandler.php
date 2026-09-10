<?php

declare(strict_types=1);

namespace App\Domain\Rides\RequestHandler;

use App\Domain\Rides\Service\RideCostCalculator;
use App\Support\ApiJson;
use App\Support\RequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RideCostHandler implements RequestHandler
{
    public function __construct(
        private RideCostCalculator $calculator,
    ) {
    }

    #[Route('/rides/cost', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiJson::response($this->calculator->calculate());
    }
}
