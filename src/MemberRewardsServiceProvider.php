<?php

namespace RCI\MemberRewards;

use Seat\Services\AbstractSeatPlugin;
use RCI\MemberRewards\Services\ActivityCollectionService;
use RCI\MemberRewards\Services\AggregationService;
use RCI\MemberRewards\Services\ESIActivityService;
use RCI\MemberRewards\Services\TaxWalletActivityService;
use RCI\MemberRewards\Services\MiningActivityService;

class MemberRewardsServiceProvider extends AbstractSeatPlugin
{
    public function getName(): string
    {
        return 'SeAT Member Rewards Programme';
    }

    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/rational-chaos-inc/seat-mrp';
    }

    public function getPackagistPackageName(): string
    {
        return 'rci/member-rewards';
    }

    public function getPackagistVendorName(): string
    {
        return 'rci';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/member-rewards.php', 'member-rewards');
        $this->registerPermissions(__DIR__ . '/Config/Permissions/member-rewards.permissions.php', 'member-rewards');
        $this->mergeConfigFrom(__DIR__ . '/Config/Menu/package.sidebar.php', 'package.sidebar');

        $this->registerServices();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'member-rewards');
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
        $this->registerSchedules();
    }

    private function registerServices(): void
    {
        $this->app->singleton(ESIActivityService::class);
        $this->app->singleton(TaxWalletActivityService::class);
        $this->app->singleton(MiningActivityService::class);
        $this->app->singleton(ActivityCollectionService::class);
        $this->app->singleton(AggregationService::class);
        $this->app->singleton(\RCI\MemberRewards\Services\AlertService::class);
        $this->app->singleton(\RCI\MemberRewards\Services\DataCollectionService::class);
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/member-rewards.php' => config_path('member-rewards.php'),
        ], 'config');
    }

    private function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');
    }

    private function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'member-rewards');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/member-rewards'),
        ], 'views');
    }

    private function registerCommands(): void
    {
        $this->commands([
            \RCI\MemberRewards\Commands\SyncActivitiesCommand::class,
            \RCI\MemberRewards\Commands\GenerateTestDataCommand::class,
            \RCI\MemberRewards\Commands\CollectActivitiesCommand::class,
            \RCI\MemberRewards\Commands\CacheAggregationsCommand::class,
            \RCI\MemberRewards\Commands\CheckAlertsCommand::class,
        ]);
    }

    private function registerSchedules(): void
    {
        // Register database seeders with schedule definitions
        // SeAT handles scheduling through its own schedule management system
        $this->registerDatabaseSeeders(\RCI\MemberRewards\Database\Seeders\ScheduleSeeder::class);
    }

}
