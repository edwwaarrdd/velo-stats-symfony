<?php

declare(strict_types=1);

namespace App\Domain\Routing\Enum;

enum TravelMode: string
{
    case Foot = 'foot';
    case Bike = 'bike';

    /**
     * The demo server at router.project-osrm.org only hosts the car profile and
     * silently ignores the profile named in the URL, so every mode came back
     * with car driving times. FOSSGIS runs a separate instance per profile, and
     * the profile is selected by this path rather than by the URL segment.
     */
    public function osrmInstancePath(): string
    {
        return match ($this) {
            self::Foot => 'routed-foot',
            self::Bike => 'routed-bike',
        };
    }
}
