<?php

namespace RCI\MemberRewards\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RCI\MemberRewards\Models\Activity;
use RCI\MemberRewards\Models\DailyMemberStat;

class AggregationService
{
    public function aggregateDay(Carbon $date, ?int $corporationId = null): int
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $query = Activity::whereBetween('activity_timestamp', [$startOfDay, $endOfDay]);

        if ($corporationId) {
            $query->where('corporation_id', $corporationId);
        }

        $activities = $query->get();

        // Group by corporation and character
        $grouped = $activities->groupBy(function ($activity) {
            return $activity->corporation_id . '|' . $activity->character_id;
        });

        $count = 0;
        foreach ($grouped as $key => $groupedActivities) {
            [$corpId, $charId] = explode('|', $key);

            $stats = [
                'date' => $date->toDateString(),
                'corporation_id' => $corpId,
                'character_id' => $charId,
                'logged_in' => true, // Has activity = logged in
                'mining_quantity' => 0,
                'mining_value' => 0,
                'tax_bounty_amount' => 0,
                'pvp_kill_instances' => 0,
            ];

            // Aggregate by activity type
            foreach ($groupedActivities as $activity) {
                match ($activity->activity_type) {
                    'mining' => $stats['mining_quantity'] += $activity->metadata['quantity'] ?? 0,
                    'tax_wallet' => $stats['tax_bounty_amount'] += $activity->metadata['amount'] ?? 0,
                    'pvp_kill' => $stats['pvp_kill_instances'] += 1,
                    default => null,
                };
            }

            // Deduplicate PvP kills by hour (max 1 per hour)
            if ($stats['pvp_kill_instances'] > 0) {
                $stats['pvp_kill_instances'] = $this->deduplicateKillsByHour($groupedActivities);
            }

            DailyMemberStat::updateOrCreate(
                [
                    'date' => $date->toDateString(),
                    'corporation_id' => $corpId,
                    'character_id' => $charId,
                ],
                $stats
            );

            $count++;
        }

        return $count;
    }

    private function deduplicateKillsByHour($activities): int
    {
        $killActivities = $activities->filter(fn($a) => $a->activity_type === 'pvp_kill');

        $hourBuckets = [];
        foreach ($killActivities as $activity) {
            $hour = $activity->activity_timestamp->copy()->startOfHour()->toDateTimeString();
            if (!isset($hourBuckets[$hour])) {
                $hourBuckets[$hour] = true;
            }
        }

        return count($hourBuckets);
    }
}
