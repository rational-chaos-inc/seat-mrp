<?php

namespace RCI\MemberRewards\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ManagerCoreIntegrationService
{
    private bool $isAvailable = false;
    private const PLUGIN_KEY = 'member-rewards';

    public function __construct()
    {
        $this->isAvailable = class_exists(\ManagerCore\Topics::class)
            && config('member-rewards.manager_core_integration', true);
    }

    public function register(): void
    {
        if (!$this->isAvailable) {
            Log::debug("Manager-Core not available, skipping integration");
            return;
        }

        try {
            $this->registerWithPluginBridge();
            $this->registerPricingPreferences();
            $this->subscribeToCharacterEvents();
            $this->subscribeToMiningEvents();
            $this->subscribeToESIEvents();

            Log::info("Manager-Core integration registered successfully");
        } catch (\Exception $e) {
            Log::error("Failed to register Manager-Core integration", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function registerWithPluginBridge(): void
    {
        // Register this plugin with Manager-Core's PluginBridge for UI visibility
        if (class_exists(\ManagerCore\Services\PluginBridge::class)) {
            $bridge = app(\ManagerCore\Services\PluginBridge::class);
            $bridge->registerSelf(self::PLUGIN_KEY, [
                'version' => '1.0.21',
                'description' => 'Track corporation member activity across mining, PvP, and tax contributions',
            ]);

            // Register capabilities so other plugins can query our data
            $aggregationService = app(\RCI\MemberRewards\Services\AggregationService::class);

            $bridge->registerCapability(
                self::PLUGIN_KEY,
                'member-rewards.getCharacterActivities',
                fn ($characterId, $months = 6) => $aggregationService->aggregateForCharacter($characterId, now()->subMonths($months), now())
            );

            $bridge->registerCapability(
                self::PLUGIN_KEY,
                'member-rewards.getCorporationLeagueTable',
                fn ($corporationId) => $aggregationService->getLeagueTable($corporationId)
            );
        }
    }

    private function registerPricingPreferences(): void
    {
        try {
            // Register default pricing preference for this plugin
            // Users can override via Manager-Core configuration
            if (class_exists('ManagerCore\Models\PricingPreference')) {
                \ManagerCore\Models\PricingPreference::registerDefault(
                    self::PLUGIN_KEY,
                    'jita',  // Default to Jita market
                    'sell'   // Use sell price for conservative valuation
                );

                Log::debug("Pricing preferences registered");
            }
        } catch (\Exception $e) {
            Log::debug("Could not register pricing preferences", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function subscribeToCharacterEvents(): void
    {
        if (!$this->isAvailable) {
            return;
        }

        try {
            $eventBus = app(\ManagerCore\Services\EventBus::class);

            // Subscribe to character token events
            $eventBus->subscribeHandler(
                self::PLUGIN_KEY,
                'character.token.refreshed',
                \RCI\MemberRewards\Listeners\CharacterTokenRefreshedListener::class,
                ['queued' => true]
            );

            // Subscribe to character linked events
            $eventBus->subscribeHandler(
                self::PLUGIN_KEY,
                'character.linked',
                \RCI\MemberRewards\Listeners\CharacterLinkedListener::class,
                ['queued' => true]
            );

            Log::debug("Character event subscriptions registered");
        } catch (\Exception $e) {
            Log::error("Failed to subscribe to character events", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function subscribeToMiningEvents(): void
    {
        if (!$this->isAvailable) {
            return;
        }

        try {
            $eventBus = app(\ManagerCore\Services\EventBus::class);

            // Subscribe to mining completion events
            $eventBus->subscribeHandler(
                self::PLUGIN_KEY,
                'mining.ledger.recorded',
                \RCI\MemberRewards\Listeners\MiningLedgerRecordedListener::class,
                ['queued' => true]
            );

            // Subscribe to extraction events
            $eventBus->subscribeHandler(
                self::PLUGIN_KEY,
                'mining.extraction_*',
                \RCI\MemberRewards\Listeners\MiningExtractionListener::class,
                ['queued' => true]
            );

            Log::debug("Mining event subscriptions registered");
        } catch (\Exception $e) {
            Log::error("Failed to subscribe to mining events", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function subscribeToESIEvents(): void
    {
        if (!$this->isAvailable) {
            return;
        }

        try {
            $eventBus = app(\ManagerCore\Services\EventBus::class);

            // Subscribe to ESI fast-poll events (if available)
            $eventBus->subscribeHandler(
                self::PLUGIN_KEY,
                'esi.killmail.*',
                \RCI\MemberRewards\Listeners\KillmailReceivedListener::class,
                ['queued' => true]
            );

            Log::debug("ESI event subscriptions registered");
        } catch (\Exception $e) {
            Log::error("Failed to subscribe to ESI events", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function publishActivitiesCollected(int $count, int $corporationId): void
    {
        if (!$this->isAvailable) {
            return;
        }

        try {
            \ManagerCore\Topics::publish('member-rewards.activities.collected', [
                'count' => $count,
                'corporation_id' => $corporationId,
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug("Published activities.collected event", [
                'count' => $count,
                'corporation_id' => $corporationId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish activities.collected event", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function publishActivityRecorded(int $activityId, string $type, int $characterId): void
    {
        if (!$this->isAvailable) {
            return;
        }

        try {
            \ManagerCore\Topics::publish('member-rewards.activity.recorded', [
                'activity_id' => $activityId,
                'type' => $type,
                'character_id' => $characterId,
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug("Published activity.recorded event", [
                'type' => $type,
                'character_id' => $characterId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish activity.recorded event", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function publishAlertTriggered(int $alertId, int $userId, array $context): void
    {
        if (!$this->isAvailable) {
            return;
        }

        try {
            \ManagerCore\Topics::publish('member-rewards.alert.triggered', [
                'alert_id' => $alertId,
                'user_id' => $userId,
                'context' => $context,
                'timestamp' => now()->toIso8601String(),
            ]);

            Log::debug("Published alert.triggered event", [
                'alert_id' => $alertId,
                'user_id' => $userId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish alert.triggered event", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function queryPluginCapability(string $plugin, string $capability, array $args = []): ?array
    {
        if (!$this->isAvailable) {
            return null;
        }

        try {
            $bridge = app(\ManagerCore\Service\PluginBridge::class);

            return $bridge->queryCapability($plugin, $capability, $args);
        } catch (\Exception $e) {
            Log::error("Failed to query plugin capability", [
                'plugin' => $plugin,
                'capability' => $capability,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function isManagerCoreAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getPluginKey(): string
    {
        return self::PLUGIN_KEY;
    }
}
