<?php

declare(strict_types=1);

namespace App\Domain\Stations\RequestHandler;

use App\Domain\Stations\Repository\StationRepository;
use App\Support\ApiJson;
use App\Support\RequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class ListStationsHandler implements RequestHandler
{
    public function __construct(
        private StationRepository $stations,
        private NormalizerInterface $normalizer,
    ) {
    }

    #[Route('/stations', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiJson::response([
            'results' => $this->normalizer->normalize($this->stations->findAllForApi()),
        ]);
    }
}
