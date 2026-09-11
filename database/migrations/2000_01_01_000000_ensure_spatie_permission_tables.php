<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add Spatie columns to existing permissions table if they don't exist
        if (Schema::hasTable('permissions')) {
            if (!Schema::hasColumn('permissions', 'guard_name')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('guard_name')->default('web')->after('title');
                });
            }
            if (!Schema::hasColumn('permissions', 'created_at')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Create junction tables - guard against duplicate table creation from competing package migrations

        // Create role_has_permissions table if it doesn't exist
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Create model_has_permissions table if it doesn't exist
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->morphs('model', 32);
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        // Create model_has_roles table if it doesn't exist
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->morphs('model', 32);
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('role_has_permissions');
    }
};
