<?php

namespace RCI\MemberRewards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RCI\MemberRewards\Services\AggregationService;
use RCI\MemberRewards\DTOs\AggregationResultDTO;
use Seat\Eveapi\Models\Character\CharacterInfo;

class DirectorController
{
    public function __construct(
        private AggregationService $aggregationService,
    ) {
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $timeWindow = $request->query('window', 'month');

            // Validate permission
            if (!$user->can('view_all_activities')) {
                abort(403, 'You do not have permission to view this');
            }

            // Get user's corporations (from their characters)
            if (!method_exists($user, 'characters')) {
                return view('member-rewards::dashboard.director', [
                    'error' => 'No linked characters found',
                ]);
            }

            $corporations = $user->characters()
                ->whereNotNull('corporation_id')
                ->distinct()
                ->pluck('corporation_id');

            if ($corporations->isEmpty()) {
                return view('member-rewards::dashboard.director', [
                    'error' => 'No corporations found',
                ]);
            }

            // Get corporation info
            $corporationId = $corporations->first();
            $corporation = CharacterInfo::where('corporation_id', $corporationId)
                ->first()
                ->corporation();

            // Get corp-wide aggregations
            $corpAggregations = $this->aggregationService->aggregateForCorporation(
                $corporationId,
                $timeWindow
            );

            // Calculate summaries
            $summary = [
                'total_value' => array_sum(array_map(fn($a) => $a['total'], $corpAggregations)),
                'total_activities' => array_sum(array_map(fn($a) => $a['count'], $corpAggregations)),
                'active_members' => count($corpAggregations),
            ];

            Log::debug("Director dashboard loaded", [
                'user_id' => $user->id,
                'corporation_id' => $corporationId,
            ]);

            return view('member-rewards::dashboard.director', [
                'user' => $user,
                'corporation' => $corporation,
                'corporationId' => $corporationId,
                'timeWindow' => $timeWindow,
                'summary' => $summary,
                'memberAggregations' => collect($corpAggregations)
                    ->map(fn($a) => AggregationResultDTO::fromArray($a)),
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading director dashboard", [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return view('member-rewards::dashboard.director', [
                'error' => 'Failed to load dashboard data',
            ]);
        }
    }

    public function leagueTables(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate permission
            if (!$user->can('view_all_activities')) {
                abort(403, 'You do not have permission to view this');
            }

            $timeWindow = $request->query('window', 'month');
            $activityType = $request->query('type', 'mining');
            $limit = (int) $request->query('limit', 50);

            // Get corporation ID
            if (!method_exists($user, 'characters')) {
                abort(403, 'No linked characters');
            }

            $corporationId = $user->characters()
                ->whereNotNull('corporation_id')
                ->first()
                ?->corporation_id;

            if (!$corporationId) {
                abort(403, 'No corporation found');
            }

            $leagueTable = $this->aggregationService->getLeagueTable(
                $corporationId,
                $timeWindow,
                $activityType,
                $limit
            );

            $activityTypes = config('member-rewards.activity_types', []);

            Log::debug("League table loaded", [
                'user_id' => $user->id,
                'corporation_id' => $corporationId,
                'type' => $activityType,
            ]);

            return view('member-rewards::dashboard.league-table', [
                'leagueTable' => $leagueTable,
                'timeWindow' => $timeWindow,
                'activityType' => $activityType,
                'activityTypes' => $activityTypes,
                'limit' => $limit,
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading league tables", [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            abort(500, 'Failed to load league tables');
        }
    }

    public function memberDetail(Request $request, int $userId)
    {
        try {
            $director = Auth::user();

            // Validate permission
            if (!$director->can('view_all_activities')) {
                abort(403, 'You do not have permission to view this');
            }

            $timeWindow = $request->query('window', 'month');

            // Get director's corporation
            $directorCorporationId = $director->characters()
                ->whereNotNull('corporation_id')
                ->first()
                ?->corporation_id;

            if (!$directorCorporationId) {
                abort(403, 'No corporation found');
            }

            // Verify member is in same corporation
            $memberModel = config('auth.providers.users.model');
            $member = $memberModel::find($userId);

            if (!$member) {
                abort(404, 'Member not found');
            }

            if (!method_exists($member, 'characters')) {
                abort(403, 'Member has no linked characters');
            }

            $memberCharacters = $member->characters()
                ->where('corporation_id', $directorCorporationId)
                ->get();

            if ($memberCharacters->isEmpty()) {
                abort(403, 'Member not in your corporation');
            }

            // Get member aggregations
            $aggregation = $this->aggregationService->aggregateForUser($userId, $timeWindow);
            $byType = $this->aggregationService->aggregateByActivityType($userId, $timeWindow);
            $trends = [];

            foreach (config('member-rewards.activity_types', []) as $type) {
                $trends[$type] = $this->aggregationService->getTrendForUser($userId, $type, 30);
            }

            Log::debug("Director viewing member detail", [
                'director_id' => $director->id,
                'member_id' => $userId,
            ]);

            return view('member-rewards::dashboard.member-detail-director', [
                'member' => $member,
                'memberCharacters' => $memberCharacters,
                'timeWindow' => $timeWindow,
                'aggregation' => AggregationResultDTO::fromArray($aggregation),
                'byType' => collect($byType)->map(fn($a) => AggregationResultDTO::fromArray($a)),
                'trends' => $trends,
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading member detail for director", [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            abort(500, 'Failed to load member details');
        }
    }
}
