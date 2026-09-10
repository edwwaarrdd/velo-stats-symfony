<?php

declare(strict_types=1);

namespace App\Domain\Rides\RequestHandler;

use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Rides\Response\RideResponse;
use App\Support\ApiJson;
use App\Support\RequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Every ride, newest first, with its cached distance and weather.
 */
final readonly class ListRidesHandler implements RequestHandler
{
    public function __construct(
        private RideRepository $rides,
    ) {
    }

    #[Route('/rides', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiJson::response([
            'results' => array_map(
                RideResponse::fromRow(...),
                $this->rides->findAllForApi(),
            ),
        ]);
    }
}
