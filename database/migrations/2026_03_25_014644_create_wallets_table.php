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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('BDT');
            $table->unsignedBigInteger('current_balance_poisha')->default(0);
            $table->unsignedBigInteger('reserved_balance_poisha')->default(0);
            $table->unsignedBigInteger('lifetime_credit_poisha')->default(0);
            $table->unsignedBigInteger('lifetime_debit_poisha')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
