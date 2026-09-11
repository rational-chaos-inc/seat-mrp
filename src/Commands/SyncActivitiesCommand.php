<?php

namespace RCI\MemberRewards\Commands;

use Illuminate\Console\Command;
use RCI\MemberRewards\Services\DataCollectionService;
use Carbon\Carbon;

class SyncActivitiesCommand extends Command
{
    protected $signature = 'member-rewards:sync {--days=90 : Days back to sync}';

    protected $description = 'Sync member activities from SeAT data sources';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = Carbon::now()->subDays($days);

        $this->info("Syncing activities from the last {$days} days...");

        $service = app(DataCollectionService::class);
        $results = $service->collectAll($since);

        $this->info('Sync Complete!');
        $this->line('Results:');
        $this->line("  Mining: {$results['mining']} records");
        $this->line("  Kills: {$results['kills']} records");
        $this->line("  Losses: {$results['losses']} records");
        $this->line("  Tax/Bounty: {$results['tax']} records");
        $this->line("  Total: " . array_sum($results) . " records");

        return self::SUCCESS;
    }
}
