<?php

namespace RCI\MemberRewards\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMemberStat extends Model
{
    protected $table = 'member_rewards_daily_stats';

    protected $fillable = [
        'date',
        'corporation_id',
        'character_id',
        'logged_in',
        'mining_quantity',
        'mining_value',
        'tax_bounty_amount',
        'pvp_kill_instances',
    ];

    protected $casts = [
        'date' => 'date',
        'logged_in' => 'boolean',
        'mining_quantity' => 'integer',
        'mining_value' => 'decimal:2',
        'tax_bounty_amount' => 'decimal:2',
        'pvp_kill_instances' => 'integer',
    ];
}
