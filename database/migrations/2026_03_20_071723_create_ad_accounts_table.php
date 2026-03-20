<?php

use App\Enums\AdAccountStatus;
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
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bm_id')->constrained('business_managers');
            $table->string('name')->index();
            $table->string('act_id')->unique();
            $table->string('status')->default(AdAccountStatus::ACTIVE)->index();
            $table->string('currency')->default('USD');
            $table->integer('balance')->default(0);

            // Budget and spending fields
            $table->integer('daily_budget')->nullable();
            $table->integer('lifetime_budget')->nullable();
            $table->integer('spent_today')->nullable();
            $table->integer('spent_yesterday')->nullable();
            $table->integer('spent_this_month')->nullable();
            $table->integer('spent_last_month')->nullable();

            // Card and payment information
            $table->string('payment_method')->nullable();
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable();
            $table->date('card_expiry')->nullable();
            $table->string('billing_address_country')->nullable();

            // Account limits and thresholds
            $table->integer('spend_cap')->nullable();
            $table->integer('daily_spend_limit')->nullable();
            $table->integer('lifetime_spend_limit')->nullable();

            // Account performance metrics
            $table->integer('impressions_today')->nullable();
            $table->integer('clicks_today')->nullable();
            $table->integer('conversions_today')->nullable();
            $table->decimal('ctr_today', 5, 2)->nullable();
            $table->decimal('cpc_today', 8, 2)->nullable();

            // Additional metadata
            $table->string('timezone')->nullable();
            $table->string('account_type')->default('business');
            $table->text('description')->nullable();
            $table->json('disable_reason')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
