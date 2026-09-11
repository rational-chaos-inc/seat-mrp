<?php

namespace RCI\MemberRewards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RCI\MemberRewards\Models\Activity;
use Carbon\Carbon;

class DirectorController
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('member-rewards.view_all_activities')) {
            abort(403, 'Unauthorized');
        }

        $timeWindow = $request->query('window', 'month');
        $days = config('member-rewards.time_windows.' . $timeWindow, 30);

        $activities = Activity::where('activity_timestamp', '>=', Carbon::now()->subDays($days))
            ->leftJoin('character_infos', 'activities.character_id', '=', 'character_infos.character_id')
            ->leftJoin('corporation_infos', 'activities.corporation_id', '=', 'corporation_infos.corporation_id')
            ->select(
                'activities.*',
                'character_infos.character_name',
                'corporation_infos.corporation_name'
            )
            ->orderBy('activity_timestamp', 'desc')
            ->limit(100)
            ->get();

        return view('member-rewards::dashboard.director', [
            'activities' => $activities,
            'timeWindow' => $timeWindow,
        ]);
    }
}
