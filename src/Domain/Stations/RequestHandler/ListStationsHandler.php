<?php

declare(strict_types=1);

namespace App\Domain\Stations\RequestHandler;

use App\Domain\Stations\Repository\StationRepository;
use App\Domain\Stations\Response\StationResponse;
use App\Support\ApiJson;
use App\Support\RequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Every known docking station, with just enough to place it on a map.
 */
final readonly class ListStationsHandler implements RequestHandler
{
    public function __construct(
        private StationRepository $stations,
    ) {
    }

    #[Route('/stations', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiJson::response([
            'results' => array_map(
                StationResponse::fromRow(...),
                $this->stations->findAllForApi(),
            ),
        ]);
    }
}
