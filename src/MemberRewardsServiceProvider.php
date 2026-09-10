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

        $this->registerServices();
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->guardSpatiePermissionMigrations();
        $this->listenForMigrationEvents();
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

    private function guardSpatiePermissionMigrations(): void
    {
        // Spatie republishes its migration stub with a new timestamp, causing conflicts.
        // Mark Spatie permission migrations as "handled" in migrations table if tables exist.
        if (! \Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            return;
        }

        $db = \Illuminate\Support\Facades\DB::table('migrations');
        $migrationPath = database_path('migrations');
        $spatieMigrations = glob($migrationPath . '/*_create_permission_tables.php');

        // If permissions table exists and Spatie migration hasn't run yet, mark it as run
        if (\Illuminate\Support\Facades\Schema::hasTable('permissions')) {
            foreach ($spatieMigrations as $file) {
                $basename = basename($file);
                $exists = $db->where('migration', $basename)->exists();

                if (! $exists) {
                    // Mark this Spatie migration as already run to skip it
                    $db->insert([
                        'migration' => $basename,
                        'batch' => $db->max('batch') + 1,
                    ]);
                }
            }
        }
    }

    private function listenForMigrationEvents(): void
    {
        // As a backup, watch for any Spatie permission table creation errors
        // This handles edge cases where the migration somehow still tries to run
        if (class_exists(\Illuminate\Database\Events\MigrationsStarted::class)) {
            $this->app['events']->listen(\Illuminate\Database\Events\MigrationsStarted::class, function () {
                $this->guardSpatiePermissionMigrations();
            });
        }
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
            try {
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
            } catch (\Exception $e) {
                // Table may not exist yet during initial installation
                // Permissions will be created after migrations run
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
