<?php

declare(strict_types=1);

namespace App\Domain\Rides\Command;

use App\Domain\Rides\Message\CheckRideDistance;
use App\Domain\Rides\Repository\RideRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'rides:check-distances',
    description: 'Queue a cycling distance lookup for every ride that has not had one.',
)]
final class CheckRideDistancesCommand extends Command
{
    public function __construct(
        private readonly RideRepository $rides,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rideIds = $this->rides->idsAwaitingDistanceCheck();

        foreach ($rideIds as $rideId) {
            $this->bus->dispatch(new CheckRideDistance($rideId));
        }

        $io->success(sprintf('Dispatched %d ride distance check task(s).', count($rideIds)));

        return Command::SUCCESS;
    }
}
