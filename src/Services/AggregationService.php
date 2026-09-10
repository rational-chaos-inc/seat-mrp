<?php

namespace RCI\MemberRewards\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RCI\MemberRewards\Models\Activity;
use RCI\MemberRewards\Models\ActivityAggregationCache;

class AggregationService
{
    private bool $useCaching = false;
    private int $cacheTtl = 0;

    public function __construct()
    {
        $this->cacheTtl = (int) config('member-rewards.aggregation_cache_ttl', 0);
        $this->useCaching = $this->cacheTtl > 0;
    }

    public function aggregateForUser(int $userId, string $timeWindow = 'month', ?string $activityType = null): array
    {
        // Check cache first if enabled
        if ($this->useCaching) {
            $cached = $this->getFromCache($userId, $timeWindow, $activityType ?? 'all');
            if ($cached) {
                return $cached;
            }
        }

        $characterIds = $this->getCharacterIdsForUser($userId);

        if ($characterIds->isEmpty()) {
            return $this->emptyAggregation();
        }

        $period = $this->getTimePeriod($timeWindow);

        $query = Activity::forCharacters($characterIds->toArray())
            ->inPeriod($period['start'], $period['end']);

        if ($activityType) {
            $query->ofType($activityType);
        }

        $activities = $query->get();

        $result = $this->calculateAggregation($activities, $characterIds, $timeWindow);

        // Store in cache if enabled
        if ($this->useCaching) {
            $this->storeInCache($userId, $timeWindow, $activityType ?? 'all', $result, $period);
        }

        return $result;
    }

    public function aggregateByActivityType(int $userId, string $timeWindow = 'month'): array
    {
        $characterIds = $this->getCharacterIdsForUser($userId);

        if ($characterIds->isEmpty()) {
            return [];
        }

        $period = $this->getTimePeriod($timeWindow);
        $activityTypes = config('member-rewards.activity_types', []);

        $results = [];

        foreach ($activityTypes as $type) {
            $activities = Activity::forCharacters($characterIds->toArray())
                ->ofType($type)
                ->inPeriod($period['start'], $period['end'])
                ->get();

            $results[$type] = $this->calculateAggregation($activities, $characterIds, $timeWindow, $type);
        }

        return $results;
    }

    public function aggregateForCorporation(int $corporationId, string $timeWindow = 'month'): array
    {
        $period = $this->getTimePeriod($timeWindow);

        $activities = Activity::inCorporation($corporationId)
            ->inPeriod($period['start'], $period['end'])
            ->get();

        $groupedByUser = $activities->groupBy('user_id');

        $results = [];
        foreach ($groupedByUser as $userId => $userActivities) {
            if (!$userId) {
                continue; // Skip activities without user_id
            }
            $characterIds = $this->getCharacterIdsForUser($userId);
            $results[$userId] = $this->calculateAggregation($userActivities, $characterIds, $timeWindow);
        }

        return $results;
    }

    public function aggregateForCharacter(int $characterId, string $timeWindow = 'month', ?string $activityType = null): array
    {
        $period = $this->getTimePeriod($timeWindow);

        $query = Activity::forCharacter($characterId)
            ->inPeriod($period['start'], $period['end']);

        if ($activityType) {
            $query->ofType($activityType);
        }

        $activities = $query->get();

        return $this->calculateAggregation($activities, collect([$characterId]), $timeWindow, $activityType);
    }

    public function getLeagueTable(int $corporationId, string $timeWindow = 'month', string $activityType = 'mining', int $limit = 50): array
    {
        $period = $this->getTimePeriod($timeWindow);

        $activities = Activity::inCorporation($corporationId)
            ->ofType($activityType)
            ->inPeriod($period['start'], $period['end'])
            ->get();

        $userStats = [];

        foreach ($activities->groupBy('user_id') as $userId => $userActivities) {
            if (!$userId) {
                continue;
            }

            $characterIds = $this->getCharacterIdsForUser($userId);
            $total = $userActivities->sum(fn($a) => $a->getValueFromMetadata() ?? 0);
            $count = $userActivities->count();

            $userStats[$userId] = [
                'user_id' => $userId,
                'total_value' => $total,
                'count' => $count,
                'average_value' => $count > 0 ? $total / $count : 0,
                'character_count' => $characterIds->count(),
                'value_per_character' => $characterIds->count() > 0 ? $total / $characterIds->count() : 0,
            ];
        }

        // Sort by total value descending
        usort($userStats, fn($a, $b) => $b['total_value'] <=> $a['total_value']);

        return array_slice($userStats, 0, $limit);
    }

    public function getTrendForUser(int $userId, string $activityType, int $daysBack = 30): array
    {
        $characterIds = $this->getCharacterIdsForUser($userId);

        if ($characterIds->isEmpty()) {
            return [];
        }

        $activities = Activity::forCharacters($characterIds->toArray())
            ->ofType($activityType)
            ->where('activity_timestamp', '>=', Carbon::now()->subDays($daysBack))
            ->orderBy('activity_timestamp', 'asc')
            ->get();

        $trend = [];

        foreach ($activities->groupBy(fn($a) => $a->activity_timestamp->format('Y-m-d')) as $date => $dayActivities) {
            $total = $dayActivities->sum(fn($a) => $a->getValueFromMetadata() ?? 0);
            $count = $dayActivities->count();

            $trend[$date] = [
                'date' => $date,
                'total' => $total,
                'count' => $count,
                'average' => $count > 0 ? $total / $count : 0,
            ];
        }

        return $trend;
    }

    private function calculateAggregation(Collection $activities, Collection $characterIds, string $timeWindow = 'custom', ?string $activityType = null): array
    {
        $total = $activities->sum(fn($a) => $a->getValueFromMetadata() ?? 0);
        $count = $activities->count();
        $average = $count > 0 ? $total / $count : 0;

        $typeBreakdown = [];
        foreach ($activities->groupBy('activity_type') as $type => $typeActivities) {
            $typeTotal = $typeActivities->sum(fn($a) => $a->getValueFromMetadata() ?? 0);
            $typeCount = $typeActivities->count();

            $typeBreakdown[$type] = [
                'count' => $typeCount,
                'total' => $typeTotal,
                'average' => $typeCount > 0 ? $typeTotal / $typeCount : 0,
                'percentage' => $total > 0 ? round(($typeTotal / $total) * 100, 2) : 0,
            ];
        }

        // Per-character breakdown
        $characterBreakdown = [];
        foreach ($activities->groupBy('character_id') as $characterId => $charActivities) {
            $charTotal = $charActivities->sum(fn($a) => $a->getValueFromMetadata() ?? 0);
            $charCount = $charActivities->count();

            $characterBreakdown[$characterId] = [
                'count' => $charCount,
                'total' => $charTotal,
                'average' => $charCount > 0 ? $charTotal / $charCount : 0,
                'percentage' => $total > 0 ? round(($charTotal / $total) * 100, 2) : 0,
            ];
        }

        return [
            'time_window' => $timeWindow,
            'activity_type' => $activityType,
            'total' => round($total, 2),
            'count' => $count,
            'average' => round($average, 2),
            'character_count' => $characterIds->count(),
            'average_per_character' => round($characterIds->count() > 0 ? $total / $characterIds->count() : 0, 2),
            'by_type' => $typeBreakdown,
            'by_character' => $characterBreakdown,
        ];
    }

    private function getCharacterIdsForUser(int $userId): Collection
    {
        try {
            $userModel = config('auth.providers.users.model');
            $user = $userModel::find($userId);

            if (!$user || !method_exists($user, 'characters')) {
                return collect();
            }

            return $user->characters()->pluck('character_id');
        } catch (\Exception $e) {
            Log::error("Failed to get character IDs for user", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    private function getTimePeriod(string $timeWindow): array
    {
        $end = Carbon::now()->endOfDay();

        return match ($timeWindow) {
            'day' => ['start' => $end->clone()->startOfDay(), 'end' => $end],
            'week' => ['start' => $end->clone()->startOfWeek(), 'end' => $end],
            'month' => ['start' => $end->clone()->startOfMonth(), 'end' => $end],
            'quarter' => ['start' => $end->clone()->startOfQuarter(), 'end' => $end],
            'year' => ['start' => $end->clone()->startOfYear(), 'end' => $end],
            default => ['start' => $end->clone()->subDays(30), 'end' => $end],
        };
    }

    private function getFromCache(int $userId, string $period, string $type): ?array
    {
        try {
            $cache = ActivityAggregationCache::where('user_id', $userId)
                ->where('aggregation_period', $period)
                ->where('activity_type', $type)
                ->where('updated_at', '>=', Carbon::now()->subSeconds($this->cacheTtl))
                ->first();

            if (!$cache) {
                return null;
            }

            return [
                'total' => (float) $cache->total_value,
                'count' => $cache->count,
                'average' => (float) $cache->average_value,
                'character_count' => 0,
                'average_per_character' => 0,
                'by_type' => [],
                'cached' => true,
            ];
        } catch (\Exception $e) {
            Log::debug("Cache retrieval failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function storeInCache(int $userId, string $period, string $type, array $result, array $timePeriod): void
    {
        try {
            ActivityAggregationCache::updateOrCreate(
                [
                    'user_id' => $userId,
                    'aggregation_period' => $period,
                    'activity_type' => $type,
                    'period_start' => $timePeriod['start']->date,
                    'period_end' => $timePeriod['end']->date,
                ],
                [
                    'total_value' => $result['total'],
                    'count' => $result['count'],
                    'average_value' => $result['average'],
                ]
            );
        } catch (\Exception $e) {
            Log::debug("Cache storage failed", ['error' => $e->getMessage()]);
        }
    }

    private function emptyAggregation(): array
    {
        return [
            'time_window' => 'custom',
            'activity_type' => null,
            'total' => 0,
            'count' => 0,
            'average' => 0,
            'character_count' => 0,
            'average_per_character' => 0,
            'by_type' => [],
            'by_character' => [],
        ];
    }

    public function clearCache(int $userId): void
    {
        try {
            ActivityAggregationCache::where('user_id', $userId)->delete();
            Log::info("Cleared aggregation cache for user {$userId}");
        } catch (\Exception $e) {
            Log::error("Failed to clear cache", ['error' => $e->getMessage()]);
        }
    }
}
