<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        DB::table('tasks')
            ->where('status', 'in_progress')
            ->update(['status' => 'doing']);

        DB::table('tasks')
            ->where('status', 'blocked')
            ->update(['status' => 'doing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        DB::table('tasks')
            ->where('status', 'doing')
            ->update(['status' => 'in_progress']);
    }
};
