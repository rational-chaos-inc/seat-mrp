<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_rewards_activity_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('alert_type', ['activity_threshold', 'unusual_activity', 'member_alert']);
            $table->json('conditions')->comment('Filters: activity types, value thresholds, time windows');
            $table->enum('notification_method', ['in_app', 'email', 'webhook'])->default('in_app');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_rewards_activity_alerts');
    }
};
