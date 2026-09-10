<?php

declare(strict_types=1);

namespace App\Domain\Stations\Command;

use App\Domain\Stations\Contract\StationInformationService;
use App\Domain\Stations\Repository\StationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'stations:load',
    description: 'Load the docking stations from the operator feed.',
)]
final class LoadStationsCommand extends Command
{
    public function __construct(
        private readonly StationInformationService $stations,
        private readonly StationRepository $repository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fetched = $this->stations->fetchStations();
        $created = 0;

        foreach ($fetched as $information) {
            if ($this->repository->upsert($information)) {
                $created++;
            }
        }

        $this->repository->flush();

        $io->success(sprintf(
            'Loaded %d stations (%d created, %d updated).',
            count($fetched),
            $created,
            count($fetched) - $created,
        ));

        return Command::SUCCESS;
    }
}
