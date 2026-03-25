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
        Schema::create('global_rate_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('min_usd_micros');
            $table->unsignedBigInteger('max_usd_micros');
            $table->unsignedBigInteger('bdt_per_usd_poisha');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'effective_from', 'effective_to'], 'grb_active_window_idx');
            $table->index(['min_usd_micros', 'max_usd_micros'], 'grb_range_idx');
            $table->index(['priority', 'is_active'], 'grb_priority_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_rate_buckets');
    }
};
