<?php

use App\Enums\AdAccountStatus;
use App\Enums\FundingStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\AdAccount;
use App\Models\AdAccountCreditRequest;
use App\Models\BusinessManager;
use App\Models\GlobalRateBucket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AdAccountFundingQuoteService;
use App\Services\AdAccountFundingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function seedGlobalFundingBucket(): void
{
    GlobalRateBucket::query()->create([
        'name' => '0-100',
        'min_usd_micros' => 0,
        'max_usd_micros' => 100_000_000,
        'bdt_per_usd_poisha' => 12_000,
        'is_active' => true,
    ]);
}

function createBusinessManager(string $accessToken = ''): BusinessManager
{
    return BusinessManager::query()->create([
        'bm_id' => 'bm_'.str()->random(12),
        'access_token' => $accessToken,
        'name' => 'BM '.str()->random(6),
    ]);
}

function createAdAccount(User $user, BusinessManager $businessManager, int $status = 1): AdAccount
{
    return AdAccount::query()->create([
        'user_id' => $user->id,
        'business_manager_id' => $businessManager->id,
        'name' => 'Ad '.str()->random(6),
        'act_id' => 'act_'.str()->random(12),
        'status' => (string) $status,
        'currency' => 'USD',
    ]);
}

it('denies funding when ad account does not belong to user', function (): void {
    seedGlobalFundingBucket();

    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $businessManager = createBusinessManager();
    $adAccount = createAdAccount($owner, $businessManager);

    Wallet::query()->create([
        'user_id' => $requester->id,
        'currency' => 'BDT',
        'current_balance_poisha' => 500_000,
    ]);

    $service = app(AdAccountFundingService::class);

    expect(fn (): array => $service->commit($requester, $adAccount, 10_000_000, 'k-ownership'))
        ->toThrow(ValidationException::class);
});

it('denies funding for inactive ad account', function (): void {
    seedGlobalFundingBucket();

    $user = User::factory()->create();
    $businessManager = createBusinessManager();
    $adAccount = createAdAccount($user, $businessManager, AdAccountStatus::DISABLED->value);

    Wallet::query()->create([
        'user_id' => $user->id,
        'currency' => 'BDT',
        'current_balance_poisha' => 500_000,
    ]);

    $service = app(AdAccountFundingService::class);

    expect(fn (): array => $service->commit($user, $adAccount, 10_000_000, 'k-inactive'))
        ->toThrow(ValidationException::class);
});

it('denies funding when wallet balance is insufficient', function (): void {
    seedGlobalFundingBucket();

    $user = User::factory()->create();
    $businessManager = createBusinessManager();
    $adAccount = createAdAccount($user, $businessManager);

    Wallet::query()->create([
        'user_id' => $user->id,
        'currency' => 'BDT',
        'current_balance_poisha' => 100,
    ]);

    $service = app(AdAccountFundingService::class);

    expect(fn (): array => $service->commit($user, $adAccount, 10_000_000, 'k-balance'))
        ->toThrow(ValidationException::class);
});

it('refunds wallet and marks records failed when facebook update fails', function (): void {
    seedGlobalFundingBucket();

    $user = User::factory()->create();
    $businessManager = createBusinessManager('');
    $adAccount = createAdAccount($user, $businessManager);

    $wallet = Wallet::query()->create([
        'user_id' => $user->id,
        'currency' => 'BDT',
        'current_balance_poisha' => 500_000,
    ]);

    $quoteService = app(AdAccountFundingQuoteService::class);
    $quote = $quoteService->quote($user, 10_000_000);

    $service = app(AdAccountFundingService::class);
    $result = $service->commit($user, $adAccount, 10_000_000, 'k-fail-refund');

    $wallet->refresh();
    $transaction = WalletTransaction::query()->findOrFail($result['wallet_transaction_id']);
    $creditRequest = AdAccountCreditRequest::query()->findOrFail($result['credit_request_id']);

    expect($result['success'])->toBeFalse()
        ->and($wallet->current_balance_poisha)->toBe(500_000)
        ->and(abs((int) $transaction->amount_bdt_poisha))->toBe((int) $quote['required_bdt_poisha'])
        ->and($transaction->type)->toBe(TransactionType::AD_ACCOUNT_FUNDING)
        ->and($transaction->status)->toBe(TransactionStatus::FAILED)
        ->and($creditRequest->status)->toBe(FundingStatus::FAILED);
});

it('returns the same transaction for idempotent retries', function (): void {
    seedGlobalFundingBucket();

    $user = User::factory()->create();
    $businessManager = createBusinessManager('');
    $adAccount = createAdAccount($user, $businessManager);

    Wallet::query()->create([
        'user_id' => $user->id,
        'currency' => 'BDT',
        'current_balance_poisha' => 500_000,
    ]);

    $service = app(AdAccountFundingService::class);

    $first = $service->commit($user, $adAccount, 10_000_000, 'k-idempotent');
    $second = $service->commit($user, $adAccount, 10_000_000, 'k-idempotent');

    expect($first['wallet_transaction_id'])->toBe($second['wallet_transaction_id'])
        ->and($first['credit_request_id'])->toBe($second['credit_request_id'])
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and(AdAccountCreditRequest::query()->count())->toBe(1);
});
