<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_rewards_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('corporation_id');
            $table->bigInteger('user_id');
            $table->boolean('show_login_status')->default(true);
            $table->boolean('show_mining')->default(true);
            $table->boolean('show_tax_bounty')->default(true);
            $table->boolean('show_pvp')->default(true);
            $table->string('visibility_level')->default('directors'); // directors, members, both
            $table->decimal('mining_weight', 5, 2)->default(1.0);
            $table->decimal('tax_bounty_weight', 5, 2)->default(1.0);
            $table->decimal('pvp_weight', 5, 2)->default(1.0);
            $table->timestamps();

            $table->unique(['corporation_id', 'user_id']);
            $table->index('corporation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_rewards_settings');
    }
};
