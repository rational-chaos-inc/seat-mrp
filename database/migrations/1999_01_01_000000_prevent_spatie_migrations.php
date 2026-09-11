<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mark all Spatie permission migrations as complete to prevent them from running.
        // Spatie publishes new migration files on each vendor:publish, causing conflicts
        // with SeAT's existing permissions table schema. We use SeAT's native permission
        // system instead, so we prevent Spatie migrations from ever executing.

        $spatieMigrations = [
            '2016_06_01_000000_create_roles_table',
            '2016_06_01_000001_create_permissions_table',
            '2016_06_01_000002_create_role_has_permissions_table',
            '2016_06_01_000003_create_model_has_roles_table',
            '2016_06_01_000004_create_model_has_permissions_table',
            '2016_07_01_000000_add_guard_names_to_roles_and_permissions_tables',
            '2018_07_04_023233_alter_entries_table_add_guard_name',
            '2018_12_26_000001_create_permission_tables',
        ];

        foreach ($spatieMigrations as $migration) {
            DB::table('migrations')->updateOrInsert(
                ['migration' => $migration],
                ['migration' => $migration, 'batch' => 1]
            );
        }

        // Also mark any timestamped Spatie migrations
        DB::table('migrations')
            ->where('migration', 'like', '%_create_permission_tables')
            ->orWhere('migration', 'like', '%_create_roles_table')
            ->orWhere('migration', 'like', '%_create_permissions_table')
            ->delete();
    }

    public function down(): void
    {
        // No-op: we don't want to unmark these migrations on rollback
    }
};
