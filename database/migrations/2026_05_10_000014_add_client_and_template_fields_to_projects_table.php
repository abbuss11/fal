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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('owner_id')
                ->constrained('clients')
                ->nullOnDelete();
            $table->boolean('is_template')->default(false)->after('status');
            $table->string('template_name', 180)->nullable()->after('is_template');
            $table->boolean('is_archived')->default(false)->after('due_date');
            $table->timestamp('archived_at')->nullable()->after('is_archived');

            $table->index('client_id');
            $table->index(['is_template', 'is_archived']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['is_template', 'is_archived']);
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['is_template', 'template_name', 'is_archived', 'archived_at']);
        });
    }
};

