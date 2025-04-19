<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Commands;

use Core\BoundedContext\SubaccountManagement\Infrastructure\Factories\SubaccountFactory;
use Illuminate\Console\Command;

final class SeedSubaccountsCommand extends Command
{
    protected $signature = 'subaccounts:seed';

    protected $description = 'Seed the subaccounts table with data from config/subaccounts.php';

    public function __construct(
        private SubaccountFactory $factory
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Seeding subaccounts from configuration...');

        try {
            $this->factory->seedFromConfig();
            $this->info('Subaccounts seeded successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error seeding subaccounts: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
