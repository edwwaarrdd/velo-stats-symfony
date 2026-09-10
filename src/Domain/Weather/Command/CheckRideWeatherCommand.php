<?php

declare(strict_types=1);

namespace App\Domain\Weather\Command;

use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Weather\Message\CheckRideWeather;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'rides:check-weather',
    description: 'Queue a weather lookup for every ride that has not had one.',
)]
final class CheckRideWeatherCommand extends Command
{
    public function __construct(
        private readonly RideRepository $rides,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Re-fetch weather for every ride, even if already checked',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = (bool) $input->getOption('force');
        $rideIds = $this->rides->idsAwaitingWeatherCheck($force);

        foreach ($rideIds as $rideId) {
            $this->bus->dispatch(new CheckRideWeather($rideId, $force));
        }

        $io->success(sprintf('Dispatched %d ride weather check task(s).', count($rideIds)));

        return Command::SUCCESS;
    }
}
