<?php

namespace RCI\MemberRewards\Models;

use Illuminate\Database\Eloquent\Model;

class MemberRewardsSetting extends Model
{
    protected $table = 'member_rewards_settings';

    protected $fillable = [
        'corporation_id',
        'user_id',
        'login_visibility',
        'mining_visibility',
        'tax_bounty_visibility',
        'pvp_visibility',
        'fleet_participation_visibility',
        'mining_weight',
        'tax_bounty_weight',
        'pvp_weight',
        'fleet_participation_weight',
    ];

    protected $casts = [
        'mining_weight' => 'decimal:2',
        'tax_bounty_weight' => 'decimal:2',
        'pvp_weight' => 'decimal:2',
        'fleet_participation_weight' => 'decimal:2',
    ];
}
