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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->default('Nouvelle tache');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->unsignedInteger('estimated_hours')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['due_date']);

            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'title',
                'description',
                'status',
                'priority',
                'estimated_hours',
                'due_date',
                'completed_at',
                'position',
            ]);
        });
    }
};

