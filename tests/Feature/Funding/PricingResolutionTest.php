<?php

use App\Enums\PricingScope;
use App\Models\GlobalRateBucket;
use App\Models\User;
use App\Models\UserRateBucket;
use App\Services\RateResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('resolves global buckets using half open boundaries', function (): void {
    $user = User::factory()->create();

    GlobalRateBucket::query()->create([
        'name' => '0-20',
        'min_usd_micros' => 0,
        'max_usd_micros' => 20_000_000,
        'bdt_per_usd_poisha' => 11_000,
        'is_active' => true,
    ]);

    GlobalRateBucket::query()->create([
        'name' => '20-50',
        'min_usd_micros' => 20_000_000,
        'max_usd_micros' => 50_000_000,
        'bdt_per_usd_poisha' => 12_000,
        'is_active' => true,
    ]);

    GlobalRateBucket::query()->create([
        'name' => '50-100',
        'min_usd_micros' => 50_000_000,
        'max_usd_micros' => 100_000_000,
        'bdt_per_usd_poisha' => 13_000,
        'is_active' => true,
    ]);

    $service = app(RateResolverService::class);

    $belowTwenty = $service->resolveForUser($user, 19_999_999);
    $exactTwenty = $service->resolveForUser($user, 20_000_000);
    $exactFifty = $service->resolveForUser($user, 50_000_000);

    expect($belowTwenty['scope'])->toBe(PricingScope::GLOBAL)
        ->and($belowTwenty['bdt_per_usd_poisha'])->toBe(11_000)
        ->and($exactTwenty['bdt_per_usd_poisha'])->toBe(12_000)
        ->and($exactFifty['bdt_per_usd_poisha'])->toBe(13_000);
});

it('uses user bucket first and falls back to global bucket', function (): void {
    $user = User::factory()->create();

    GlobalRateBucket::query()->create([
        'name' => '0-50 global',
        'min_usd_micros' => 0,
        'max_usd_micros' => 50_000_000,
        'bdt_per_usd_poisha' => 12_000,
        'is_active' => true,
    ]);

    UserRateBucket::query()->create([
        'user_id' => $user->id,
        'name' => '20-50 special',
        'min_usd_micros' => 20_000_000,
        'max_usd_micros' => 50_000_000,
        'bdt_per_usd_poisha' => 9_999,
        'is_active' => true,
    ]);

    $service = app(RateResolverService::class);

    $userResolved = $service->resolveForUser($user, 30_000_000);
    $fallbackResolved = $service->resolveForUser($user, 10_000_000);

    expect($userResolved['scope'])->toBe(PricingScope::USER)
        ->and($userResolved['bdt_per_usd_poisha'])->toBe(9_999)
        ->and($fallbackResolved['scope'])->toBe(PricingScope::GLOBAL)
        ->and($fallbackResolved['bdt_per_usd_poisha'])->toBe(12_000);
});

it('rejects overlapping global bucket ranges', function (): void {
    GlobalRateBucket::query()->create([
        'name' => '0-20',
        'min_usd_micros' => 0,
        'max_usd_micros' => 20_000_000,
        'bdt_per_usd_poisha' => 11_000,
        'is_active' => true,
    ]);

    expect(function (): void {
        GlobalRateBucket::query()->create([
            'name' => '10-30 overlap',
            'min_usd_micros' => 10_000_000,
            'max_usd_micros' => 30_000_000,
            'bdt_per_usd_poisha' => 12_000,
            'is_active' => true,
        ]);
    })->toThrow(ValidationException::class);
});
