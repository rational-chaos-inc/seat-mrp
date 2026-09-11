<?php

namespace RCI\MemberRewards;

use Seat\Services\AbstractSeatPlugin;
use RCI\MemberRewards\Services\ActivityCollectionService;
use RCI\MemberRewards\Services\AggregationService;
use RCI\MemberRewards\Services\ESIActivityService;
use RCI\MemberRewards\Services\TaxWalletActivityService;
use RCI\MemberRewards\Services\MiningActivityService;
use RCI\MemberRewards\Services\ManagerCoreIntegrationService;

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
        $this->mergeConfigFrom(__DIR__ . '/../config/member-rewards.sidebar.php', 'package.sidebar');
        $this->mergeConfigFrom(__DIR__ . '/../config/member-rewards.character.menu.php', 'web.character.menu_items');
        $this->mergeConfigFrom(__DIR__ . '/../config/member-rewards.corporation.menu.php', 'web.corporation.menu_items');

        // Delete Spatie's republished permission migrations before Laravel discovers them.
        // Spatie publishes with new timestamp on each vendor:publish, causing conflicts.
        // Our 2000_01_01_000000 migration handles all junction tables with proper guards.
        $this->cleanupSpatiePermissionMigrations();

        $this->registerServices();
    }

    private function cleanupSpatiePermissionMigrations(): void
    {
        $migrationPath = @database_path('migrations');
        if (!is_dir($migrationPath)) {
            return;
        }

        // Find and delete unguarded Spatie permission migrations
        $files = @glob($migrationPath . '/*_create_permission_tables.php') ?: [];
        foreach ($files as $file) {
            $content = @file_get_contents($file) ?: '';
            // Skip if already guarded or if it's not a Spatie migration
            if (strpos($content, 'if (!Schema::hasTable') !== false ||
                strpos($content, "Schema::create('permissions'") === false) {
                continue;
            }
            // Delete unguarded Spatie migration to prevent conflicts
            @unlink($file);
        }
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
        $this->registerSchedules();
        $this->registerManagerCoreIntegration();
    }

    private function registerServices(): void
    {
        $this->app->singleton(ESIActivityService::class);
        $this->app->singleton(TaxWalletActivityService::class);
        $this->app->singleton(MiningActivityService::class);
        $this->app->singleton(ActivityCollectionService::class);
        $this->app->singleton(AggregationService::class);
        $this->app->singleton(ManagerCoreIntegrationService::class);
        $this->app->singleton(\RCI\MemberRewards\Services\AlertService::class);
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
        // Don't load routes if they're cached
        if ($this->app->routesAreCached()) {
            return;
        }

        // Load web routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load API routes
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

    private function registerManagerCoreIntegration(): void
    {
        if (class_exists(\ManagerCore\Topics::class)) {
            $this->app->make(ManagerCoreIntegrationService::class)->register();
        }
    }
}
