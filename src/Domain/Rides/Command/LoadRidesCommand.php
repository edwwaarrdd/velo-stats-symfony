<?php

declare(strict_types=1);

namespace App\Domain\Rides\Command;

use App\Domain\Rides\Contract\RideDataSource;
use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Rides\Service\JsonFileRideService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rides:load',
    description: 'Load the ride history from the JSON export.',
)]
final class LoadRidesCommand extends Command
{
    public function __construct(
        private readonly RideDataSource $rides,
        private readonly RideRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'path',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to the rides JSON export (defaults to the configured export)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = $input->getOption('path');
        $source = $path === null ? $this->rides : new JsonFileRideService((string) $path);

        $fetched = $source->fetchRides();
        $created = 0;

        foreach ($fetched as $record) {
            if ($this->repository->upsert($record)) {
                $created++;
            }
        }

        $this->repository->flush();

        $io->success(sprintf(
            'Loaded %d rides (%d created, %d updated).',
            count($fetched),
            $created,
            count($fetched) - $created,
        ));

        return Command::SUCCESS;
    }
}
