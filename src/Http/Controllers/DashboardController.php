<?php

namespace RCI\MemberRewards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RCI\MemberRewards\Services\AggregationService;
use RCI\MemberRewards\DTOs\AggregationResultDTO;

class DashboardController
{
    public function __construct(
        private AggregationService $aggregationService,
    ) {
    }

    public function member(Request $request)
    {
        try {
            $user = Auth::user();
            $timeWindow = $request->query('window', 'month');
            $activityType = $request->query('type');

            // Validate time window
            if (!in_array($timeWindow, ['day', 'week', 'month', 'quarter', 'year'])) {
                $timeWindow = 'month';
            }

            // Get user's linked characters
            if (!method_exists($user, 'characters')) {
                return view('member-rewards::dashboard.member', [
                    'error' => 'No linked characters found',
                    'timeWindow' => $timeWindow,
                    'characters' => collect(),
                ]);
            }

            $characters = $user->characters()->get();

            if ($characters->isEmpty()) {
                return view('member-rewards::dashboard.member', [
                    'error' => 'No linked characters found',
                    'timeWindow' => $timeWindow,
                    'characters' => collect(),
                ]);
            }

            // Get aggregations
            $overall = $this->aggregationService->aggregateForUser($user->id, $timeWindow, $activityType);
            $byType = $this->aggregationService->aggregateByActivityType($user->id, $timeWindow);
            $trends = [];

            // Get trends for each activity type
            foreach (config('member-rewards.activity_types', []) as $type) {
                $trends[$type] = $this->aggregationService->getTrendForUser($user->id, $type, 30);
            }

            Log::debug("Member dashboard loaded", [
                'user_id' => $user->id,
                'characters_count' => $characters->count(),
            ]);

            return view('member-rewards::dashboard.member', [
                'user' => $user,
                'characters' => $characters,
                'timeWindow' => $timeWindow,
                'selectedType' => $activityType,
                'overall' => AggregationResultDTO::fromArray($overall),
                'byType' => collect($byType)->map(fn($a) => AggregationResultDTO::fromArray($a)),
                'trends' => $trends,
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading member dashboard", [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return view('member-rewards::dashboard.member', [
                'error' => 'Failed to load dashboard data',
            ]);
        }
    }

    public function characterDetail(Request $request, int $characterId)
    {
        try {
            $user = Auth::user();
            $timeWindow = $request->query('window', 'month');

            // Verify user owns this character
            if (!method_exists($user, 'characters')) {
                abort(403, 'Unauthorized');
            }

            $character = $user->characters()
                ->where('character_id', $characterId)
                ->first();

            if (!$character) {
                abort(404, 'Character not found');
            }

            $aggregation = $this->aggregationService->aggregateForCharacter(
                $characterId,
                $timeWindow
            );

            $byType = [];
            foreach (config('member-rewards.activity_types', []) as $type) {
                $byType[$type] = $this->aggregationService->aggregateForCharacter(
                    $characterId,
                    $timeWindow,
                    $type
                );
            }

            Log::debug("Character detail loaded", [
                'user_id' => $user->id,
                'character_id' => $characterId,
            ]);

            return view('member-rewards::dashboard.character-detail', [
                'character' => $character,
                'timeWindow' => $timeWindow,
                'aggregation' => AggregationResultDTO::fromArray($aggregation),
                'byType' => collect($byType)->map(fn($a) => AggregationResultDTO::fromArray($a)),
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading character detail", [
                'error' => $e->getMessage(),
                'character_id' => $characterId,
            ]);

            abort(500, 'Failed to load character details');
        }
    }
}
