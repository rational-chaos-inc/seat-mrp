<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_rewards_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->bigInteger('corporation_id');
            $table->bigInteger('character_id');
            $table->boolean('logged_in')->default(false);
            $table->bigInteger('mining_quantity')->default(0);
            $table->decimal('mining_value', 15, 2)->default(0);
            $table->decimal('tax_bounty_amount', 15, 2)->default(0);
            $table->integer('pvp_kill_instances')->default(0);
            $table->timestamps();

            $table->unique(['date', 'corporation_id', 'character_id']);
            $table->index('corporation_id');
            $table->index('character_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_rewards_daily_stats');
    }
};
