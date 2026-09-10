<?php

declare(strict_types=1);

namespace App\Domain\Rides\RequestHandler;

use App\Domain\Rides\Service\RideListProvider;
use App\Support\ApiJson;
use App\Support\RequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Every ride, newest first, with its cached distance and weather.
 */
final readonly class ListRidesHandler implements RequestHandler
{
    public function __construct(
        private RideListProvider $rides,
        private NormalizerInterface $normalizer,
    ) {
    }

    #[Route('/rides', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiJson::response([
            'results' => $this->normalizer->normalize($this->rides->list()),
        ]);
    }
}
