<?php

use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\BusinessManager;
use App\Models\GlobalRateBucket;
use App\Models\User;
use App\Models\UserRateBucket;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'Admin',
        'email' => 'admin-'.str()->random(8).'@example.com',
        'password' => 'password',
    ]);
}

function createBusinessManagerForPolicy(): BusinessManager
{
    return BusinessManager::query()->create([
        'bm_id' => 'bm_'.str()->random(12),
        'access_token' => 'token',
        'name' => 'Policy BM',
    ]);
}

it('allows admins to manage core finance and account resources', function (): void {
    $admin = createAdmin();
    $user = User::factory()->create();
    $businessManager = createBusinessManagerForPolicy();

    $adAccount = AdAccount::query()->create([
        'user_id' => $user->id,
        'business_manager_id' => $businessManager->id,
        'name' => 'Account A',
        'act_id' => 'act_'.str()->random(8),
        'status' => '1',
        'currency' => 'USD',
    ]);

    $globalRate = GlobalRateBucket::query()->create([
        'name' => '0-100',
        'min_usd_micros' => 0,
        'max_usd_micros' => 100_000_000,
        'bdt_per_usd_poisha' => 12_000,
        'is_active' => true,
    ]);

    $userRate = UserRateBucket::query()->create([
        'user_id' => $user->id,
        'name' => 'special',
        'min_usd_micros' => 0,
        'max_usd_micros' => 100_000_000,
        'bdt_per_usd_poisha' => 11_000,
        'is_active' => true,
    ]);

    expect($admin->can('viewAny', AdAccount::class))->toBeTrue()
        ->and($admin->can('view', $adAccount))->toBeTrue()
        ->and($admin->can('update', $adAccount))->toBeTrue()
        ->and($admin->can('viewAny', BusinessManager::class))->toBeTrue()
        ->and($admin->can('view', $businessManager))->toBeTrue()
        ->and($admin->can('viewAny', GlobalRateBucket::class))->toBeTrue()
        ->and($admin->can('update', $globalRate))->toBeTrue()
        ->and($admin->can('viewAny', UserRateBucket::class))->toBeTrue()
        ->and($admin->can('update', $userRate))->toBeTrue()
        ->and($admin->can('viewAny', User::class))->toBeTrue();
});

it('restricts user access to owned ad account and own profile only', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $businessManager = createBusinessManagerForPolicy();

    $ownAdAccount = AdAccount::query()->create([
        'user_id' => $user->id,
        'business_manager_id' => $businessManager->id,
        'name' => 'Own Account',
        'act_id' => 'act_'.str()->random(8),
        'status' => '1',
        'currency' => 'USD',
    ]);

    $othersAdAccount = AdAccount::query()->create([
        'user_id' => $otherUser->id,
        'business_manager_id' => $businessManager->id,
        'name' => 'Other Account',
        'act_id' => 'act_'.str()->random(8),
        'status' => '1',
        'currency' => 'USD',
    ]);

    expect($user->can('view', $ownAdAccount))->toBeTrue()
        ->and($user->can('view', $othersAdAccount))->toBeFalse()
        ->and($user->can('update', $ownAdAccount))->toBeFalse()
        ->and($user->can('view', $user))->toBeTrue()
        ->and($user->can('update', $user))->toBeTrue()
        ->and($user->can('view', $otherUser))->toBeFalse()
        ->and($user->can('viewAny', BusinessManager::class))->toBeFalse()
        ->and($user->can('viewAny', GlobalRateBucket::class))->toBeFalse()
        ->and($user->can('viewAny', UserRateBucket::class))->toBeFalse();
});

it('allows users to view only their own wallet transactions', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userWallet = Wallet::query()->create([
        'user_id' => $user->id,
        'currency' => 'BDT',
    ]);

    $otherWallet = Wallet::query()->create([
        'user_id' => $otherUser->id,
        'currency' => 'BDT',
    ]);

    $ownTransaction = WalletTransaction::query()->create([
        'wallet_id' => $userWallet->id,
        'user_id' => $user->id,
        'type' => 'wallet_top_up',
        'status' => 'completed',
        'source' => 'admin_panel',
        'amount_bdt_poisha' => 1_000,
        'balance_before_poisha' => 0,
        'balance_after_poisha' => 1_000,
    ]);

    $othersTransaction = WalletTransaction::query()->create([
        'wallet_id' => $otherWallet->id,
        'user_id' => $otherUser->id,
        'type' => 'wallet_top_up',
        'status' => 'completed',
        'source' => 'admin_panel',
        'amount_bdt_poisha' => 2_000,
        'balance_before_poisha' => 0,
        'balance_after_poisha' => 2_000,
    ]);

    expect($user->can('view', $ownTransaction))->toBeTrue()
        ->and($user->can('view', $othersTransaction))->toBeFalse()
        ->and($user->can('update', $ownTransaction))->toBeFalse()
        ->and($user->can('delete', $ownTransaction))->toBeFalse();
});
