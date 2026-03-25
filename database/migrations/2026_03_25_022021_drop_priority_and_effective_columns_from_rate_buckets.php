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
        Schema::table('global_rate_buckets', function (Blueprint $table): void {
            $table->dropIndex('grb_active_window_idx');
            $table->dropIndex('grb_priority_active_idx');
        });

        Schema::table('global_rate_buckets', function (Blueprint $table): void {
            $table->dropColumn([
                'priority',
                'effective_from',
                'effective_to',
            ]);
        });

        Schema::table('user_rate_buckets', function (Blueprint $table): void {
            $table->dropIndex('urb_effective_window_idx');
        });

        Schema::table('user_rate_buckets', function (Blueprint $table): void {
            $table->dropColumn([
                'priority',
                'effective_from',
                'effective_to',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_rate_buckets', function (Blueprint $table): void {
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->index(['is_active', 'effective_from', 'effective_to'], 'grb_active_window_idx');
            $table->index(['priority', 'is_active'], 'grb_priority_active_idx');
        });

        Schema::table('user_rate_buckets', function (Blueprint $table): void {
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->index(['effective_from', 'effective_to'], 'urb_effective_window_idx');
        });
    }
};
