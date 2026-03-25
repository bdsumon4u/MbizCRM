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
        Schema::create('user_rate_buckets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
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

            $table->index(['user_id', 'is_active'], 'urb_user_active_idx');
            $table->index(['user_id', 'min_usd_micros', 'max_usd_micros'], 'urb_user_range_idx');
            $table->index(['effective_from', 'effective_to'], 'urb_effective_window_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_rate_buckets');
    }
};
