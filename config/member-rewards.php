<?php

return [
    /*
     * Enable or disable the Member Rewards Programme
     */
    'enabled' => env('MEMBER_REWARDS_ENABLED', true),

    /*
     * Activity collection polling interval in minutes
     */
    'polling_interval' => env('MEMBER_REWARDS_POLLING_INTERVAL', 5),

    /*
     * Aggregation cache TTL in seconds (0 = disabled)
     */
    'aggregation_cache_ttl' => env('MEMBER_REWARDS_CACHE_TTL', 0),

    /*
     * ESI API retry configuration
     */
    'esi' => [
        'retry_attempts' => 3,
        'retry_delay_seconds' => 2,
    ],

    /*
     * Supported time windows for aggregations
     */
    'time_windows' => [
        'day' => 1,
        'week' => 7,
        'month' => 30,
        'quarter' => 90,
        'year' => 365,
    ],

    /*
     * Activity types tracked by this plugin
     */
    'activity_types' => [
        'mining',
        'pvp_kill',
        'pvp_loss',
        'tax_wallet',
    ],

    /*
     * Enable Manager-Core integration when available
     */
    'manager_core_integration' => true,

    /*
     * Alert system configuration
     */
    'alerts' => [
        'enabled' => true,
        'check_interval' => 5, // minutes
    ],

    /*
     * Mining-Manager plugin integration
     */
    'mining_manager' => [
        'use_events' => true,
        'fallback_direct_query' => true,
    ],
];
