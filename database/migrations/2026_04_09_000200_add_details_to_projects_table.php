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
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->default('Nouveau projet');
            $table->text('description')->nullable();
            $table->string('status')->default('planning');
            $table->string('priority')->default('medium');
            $table->decimal('budget', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['due_date']);

            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn([
                'name',
                'description',
                'status',
                'priority',
                'budget',
                'start_date',
                'due_date',
                'completed_at',
            ]);
        });
    }
};

