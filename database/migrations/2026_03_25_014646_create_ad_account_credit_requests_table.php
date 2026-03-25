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
        Schema::create('ad_account_credit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_manager_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('requested_usd_micros');
            $table->unsignedBigInteger('resolved_rate_bdt_per_usd_poisha');
            $table->unsignedBigInteger('required_bdt_poisha');
            $table->string('pricing_scope');
            $table->unsignedBigInteger('pricing_bucket_id')->nullable();
            $table->string('pricing_bucket_table')->nullable();
            $table->string('status');
            $table->json('facebook_request_payload')->nullable();
            $table->json('facebook_response_payload')->nullable();
            $table->string('facebook_error_code')->nullable();
            $table->text('facebook_error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'acr_user_created_idx');
            $table->index(['ad_account_id', 'created_at'], 'acr_account_created_idx');
            $table->index(['status', 'created_at'], 'acr_status_created_idx');
            $table->index(['pricing_scope', 'pricing_bucket_id'], 'acr_pricing_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_account_credit_requests');
    }
};
