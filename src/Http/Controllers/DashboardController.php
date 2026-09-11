<?php

namespace RCI\MemberRewards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RCI\MemberRewards\Models\Activity;
use Carbon\Carbon;

class DashboardController
{
    public function member(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('member-rewards.view_own_activities')) {
            abort(403, 'Unauthorized');
        }

        $timeWindow = $request->query('window', 'month');
        $days = config('member-rewards.time_windows.' . $timeWindow, 30);

        $characters = $user->characters()->get();
        $characterIds = $characters->pluck('character_id')->toArray();

        $activities = Activity::whereIn('character_id', $characterIds)
            ->where('activity_timestamp', '>=', Carbon::now()->subDays($days))
            ->orderBy('activity_timestamp', 'desc')
            ->paginate(50);

        return view('member-rewards::dashboard.member', [
            'user' => $user,
            'characters' => $characters,
            'activities' => $activities,
            'timeWindow' => $timeWindow,
        ]);
    }
}
