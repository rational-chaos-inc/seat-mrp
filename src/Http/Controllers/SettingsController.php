<?php

namespace RCI\MemberRewards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RCI\MemberRewards\Models\MemberRewardsSetting;

class SettingsController
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('member-rewards.view_all_activities')) {
            abort(403, 'Unauthorized');
        }

        // Get corporations the director has access to
        $corporations = $this->getUserCorporations($user);

        // Get or create settings for each corporation
        $settings = [];
        foreach ($corporations as $corp) {
            $setting = MemberRewardsSetting::firstOrCreate(
                [
                    'corporation_id' => $corp->corporation_id,
                    'user_id' => $user->id,
                ],
                [
                    'show_login_status' => true,
                    'show_mining' => true,
                    'show_tax_bounty' => true,
                    'show_pvp' => true,
                    'visibility_level' => 'directors',
                    'mining_weight' => 1.0,
                    'tax_bounty_weight' => 1.0,
                    'pvp_weight' => 1.0,
                ]
            );
            $settings[$corp->corporation_id] = $setting;
        }

        return view('member-rewards::settings.index', [
            'corporations' => $corporations,
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('member-rewards.view_all_activities')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'corporation_id' => 'required|integer',
            'show_login_status' => 'boolean',
            'show_mining' => 'boolean',
            'show_tax_bounty' => 'boolean',
            'show_pvp' => 'boolean',
            'visibility_level' => 'required|in:directors,members,both',
            'mining_weight' => 'required|numeric|min:0|max:10',
            'tax_bounty_weight' => 'required|numeric|min:0|max:10',
            'pvp_weight' => 'required|numeric|min:0|max:10',
        ]);

        MemberRewardsSetting::updateOrCreate(
            [
                'corporation_id' => $validated['corporation_id'],
                'user_id' => $user->id,
            ],
            [
                'show_login_status' => $request->boolean('show_login_status'),
                'show_mining' => $request->boolean('show_mining'),
                'show_tax_bounty' => $request->boolean('show_tax_bounty'),
                'show_pvp' => $request->boolean('show_pvp'),
                'visibility_level' => $validated['visibility_level'],
                'mining_weight' => (float) $validated['mining_weight'],
                'tax_bounty_weight' => (float) $validated['tax_bounty_weight'],
                'pvp_weight' => (float) $validated['pvp_weight'],
            ]
        );

        return redirect()->route('member-rewards.settings')
            ->with('success', 'Settings saved successfully!');
    }

    private function getUserCorporations($user)
    {
        // Get corporations where user is a director
        // For now, return all corporations with member tracking data
        return \DB::table('corporation_infos')
            ->whereIn('corporation_id',
                \DB::table('corporation_member_trackings')
                    ->select('corporation_id')
                    ->distinct()
            )
            ->get();
    }
}
