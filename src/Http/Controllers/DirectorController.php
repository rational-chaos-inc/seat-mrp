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
            ->orderBy('activity_timestamp', 'desc')
            ->limit(100)
            ->get();

        // Load character and corporation names
        foreach ($activities as $activity) {
            if ($activity->character_id) {
                $char = \DB::table('character_infos')->where('character_id', $activity->character_id)->first();
                $activity->character_name = $char ? $char->name : 'Unknown';
            }
            if ($activity->corporation_id) {
                $corp = \DB::table('corporation_infos')->where('corporation_id', $activity->corporation_id)->first();
                $activity->corporation_name = $corp ? $corp->name : 'Unknown';
            }
        }

        return view('member-rewards::dashboard.director', [
            'activities' => $activities,
            'timeWindow' => $timeWindow,
        ]);
    }
}
