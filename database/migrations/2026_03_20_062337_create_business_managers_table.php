<?php

declare(strict_types=1);

use App\Enums\BusinessManagerStatus;
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
        Schema::create('business_managers', function (Blueprint $table): void {
            $table->id();
            $table->string('bm_id')->unique();
            $table->text('access_token');
            $table->string('ad_account_prefix')->nullable();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('status')->default(BusinessManagerStatus::ACTIVE)->index();
            $table->string('currency')->default('USD');
            $table->integer('balance')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_managers');
    }
};
