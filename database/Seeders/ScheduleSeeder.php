<?php

namespace RCI\MemberRewards\Database\Seeders;

use Seat\Services\Seeding\AbstractScheduleSeeder;

class ScheduleSeeder extends AbstractScheduleSeeder
{
    /**
     * Get the schedules for this plugin.
     */
    public function getSchedules(): array
    {
        return [
            [
                'command' => 'member-rewards:collect-activities',
                'expression' => '*/5 * * * *',  // Every 5 minutes
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            [
                'command' => 'member-rewards:check-alerts',
                'expression' => '*/5 * * * *',  // Every 5 minutes
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
        ];
    }

    /**
     * Get the deprecated schedules that should be removed.
     */
    public function getDeprecatedSchedules(): array
    {
        return [];
    }
}
