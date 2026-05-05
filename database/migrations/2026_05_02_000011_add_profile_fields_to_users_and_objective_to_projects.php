<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title', 120)->nullable()->after('name');
            $table->string('phone', 60)->nullable()->after('email');
            $table->text('bio')->nullable()->after('phone');
            $table->string('avatar_path', 255)->nullable()->after('bio');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->text('objective')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('objective');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['job_title', 'phone', 'bio', 'avatar_path']);
        });
    }
};

