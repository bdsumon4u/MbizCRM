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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_manager_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->string('source');
            $table->bigInteger('amount_bdt_poisha');
            $table->unsignedBigInteger('balance_before_poisha');
            $table->unsignedBigInteger('balance_after_poisha');
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('external_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'wt_user_created_idx');
            $table->index(['ad_account_id', 'created_at'], 'wt_account_created_idx');
            $table->index(['status', 'type'], 'wt_status_type_idx');
            $table->index('external_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
