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

        $activities = Activity::where('activity_timestamp', '>=', Carbon::now()->subDays($days))
            ->orderBy('activity_timestamp', 'desc')
            ->limit(100)
            ->get();

        return view('member-rewards::dashboard.member', [
            'activities' => $activities,
            'timeWindow' => $timeWindow,
        ]);
    }
}
