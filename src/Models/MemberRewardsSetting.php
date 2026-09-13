<?php

namespace RCI\MemberRewards\Models;

use Illuminate\Database\Eloquent\Model;

class MemberRewardsSetting extends Model
{
    protected $table = 'member_rewards_settings';

    protected $fillable = [
        'corporation_id',
        'user_id',
        'show_login_status',
        'show_mining',
        'show_tax_bounty',
        'show_pvp',
        'visibility_level',
        'mining_weight',
        'tax_bounty_weight',
        'pvp_weight',
    ];

    protected $casts = [
        'show_login_status' => 'boolean',
        'show_mining' => 'boolean',
        'show_tax_bounty' => 'boolean',
        'show_pvp' => 'boolean',
        'mining_weight' => 'decimal:2',
        'tax_bounty_weight' => 'decimal:2',
        'pvp_weight' => 'decimal:2',
    ];
}
