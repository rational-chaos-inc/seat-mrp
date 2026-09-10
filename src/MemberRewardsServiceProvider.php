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
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/member-rewards.php', 'member-rewards');

        $this->registerServices();
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerRoutes();
        $this->registerViews();
        $this->bootPermissions();
        $this->registerCommands();
        $this->registerSchedules();
        $this->registerSidebar();
        $this->registerMenus();
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

    private function bootPermissions(): void
    {
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            $permissions = [
                'view_own_activities',
                'view_all_activities',
                'configure_alerts',
                'manage_member_rewards',
            ];

            foreach ($permissions as $permission) {
                if (!\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
                    \Spatie\Permission\Models\Permission::create(['name' => $permission]);
                }
            }
        }
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

    private function registerSidebar(): void
    {
        // Register sidebar configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/member-rewards.sidebar.php',
            'package.sidebar'
        );
    }

    private function registerMenus(): void
    {
        // Register character submenu
        $this->mergeConfigFrom(
            __DIR__ . '/../config/member-rewards.character.menu.php',
            'web.character.menu_items'
        );

        // Register corporation submenu
        $this->mergeConfigFrom(
            __DIR__ . '/../config/member-rewards.corporation.menu.php',
            'web.corporation.menu_items'
        );
    }

    private function registerManagerCoreIntegration(): void
    {
        if (class_exists(\ManagerCore\Topics::class)) {
            $this->app->make(ManagerCoreIntegrationService::class)->register();
        }
    }
}
