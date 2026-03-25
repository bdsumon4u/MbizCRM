<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdAccountStatus;
use App\Enums\FundingStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\AdAccount;
use App\Models\AdAccountCreditRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AdAccountFundingService
{
    public function __construct(
        private AdAccountFundingQuoteService $quoteService,
    ) {}

    /**
     * @return array{success: bool, message: string, quote: array, wallet_transaction_id: ?int, credit_request_id: ?int}
     */
    public function commit(User $user, AdAccount $adAccount, int $requestedUsdMicros, ?string $idempotencyKey = null): array
    {
        if ($adAccount->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'ad_account_id' => 'You are not allowed to fund this ad account.',
            ]);
        }

        if (! $this->isAdAccountActive($adAccount)) {
            throw ValidationException::withMessages([
                'ad_account_id' => 'This ad account is not active and cannot be funded.',
            ]);
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existingTransaction = WalletTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', TransactionType::AD_ACCOUNT_FUNDING)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction !== null) {
                return $this->buildIdempotentResponse($existingTransaction, $adAccount, $requestedUsdMicros);
            }
        }

        $quote = $this->quoteService->quote($user, $requestedUsdMicros);

        if (! $quote['is_affordable']) {
            throw ValidationException::withMessages([
                'requested_usd' => 'Insufficient wallet balance for the requested amount.',
            ]);
        }

        $walletTransactionId = null;
        $creditRequestId = null;

        DB::transaction(function () use ($user, $adAccount, $quote, $idempotencyKey, &$walletTransactionId, &$creditRequestId): void {
            $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'currency' => 'BDT',
                ]
            );

            $requiredBdtPoisha = (int) $quote['required_bdt_poisha'];
            $beforeBalancePoisha = (int) $wallet->current_balance_poisha;

            if ($beforeBalancePoisha < $requiredBdtPoisha) {
                throw ValidationException::withMessages([
                    'requested_usd' => 'Insufficient wallet balance for the requested amount.',
                ]);
            }

            $afterBalancePoisha = $beforeBalancePoisha - $requiredBdtPoisha;

            $wallet->update([
                'current_balance_poisha' => $afterBalancePoisha,
                'lifetime_debit_poisha' => (int) $wallet->lifetime_debit_poisha + $requiredBdtPoisha,
                'last_activity_at' => now(),
            ]);

            $walletTransaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'ad_account_id' => $adAccount->id,
                'business_manager_id' => $adAccount->business_manager_id,
                'type' => TransactionType::AD_ACCOUNT_FUNDING,
                'status' => TransactionStatus::PENDING,
                'source' => TransactionSource::USER_PANEL,
                'amount_bdt_poisha' => -$requiredBdtPoisha,
                'balance_before_poisha' => $beforeBalancePoisha,
                'balance_after_poisha' => $afterBalancePoisha,
                'metadata' => [
                    'requested_usd_micros' => $quote['requested_usd_micros'],
                    'pricing_scope' => $quote['pricing_scope'],
                    'pricing_bucket_id' => $quote['pricing_bucket_id'],
                    'pricing_bucket_table' => $quote['pricing_bucket_table'],
                    'rate_bdt_per_usd_poisha' => $quote['rate_bdt_per_usd_poisha'],
                ],
                'idempotency_key' => $idempotencyKey,
                'processed_at' => now(),
            ]);

            $walletTransactionId = $walletTransaction->id;

            $creditRequest = AdAccountCreditRequest::query()->create([
                'wallet_transaction_id' => $walletTransaction->id,
                'user_id' => $user->id,
                'ad_account_id' => $adAccount->id,
                'business_manager_id' => $adAccount->business_manager_id,
                'requested_usd_micros' => (int) $quote['requested_usd_micros'],
                'resolved_rate_bdt_per_usd_poisha' => (int) $quote['rate_bdt_per_usd_poisha'],
                'required_bdt_poisha' => $requiredBdtPoisha,
                'pricing_scope' => $quote['pricing_scope'],
                'pricing_bucket_id' => (int) $quote['pricing_bucket_id'],
                'pricing_bucket_table' => $quote['pricing_bucket_table'],
                'status' => FundingStatus::PENDING,
            ]);

            $creditRequestId = $creditRequest->id;
        });

        /** @var AdAccountCreditRequest $creditRequest */
        $creditRequest = AdAccountCreditRequest::query()->findOrFail($creditRequestId);
        /** @var WalletTransaction $walletTransaction */
        $walletTransaction = WalletTransaction::query()->findOrFail($walletTransactionId);

        $facebookResult = $this->updateFacebookSpendCap($adAccount, $quote['requested_usd_micros']);

        if ($facebookResult['success']) {
            $creditRequest->update([
                'status' => FundingStatus::SUCCEEDED,
                'facebook_request_payload' => $facebookResult['request_payload'],
                'facebook_response_payload' => $facebookResult['response_payload'],
                'processed_at' => now(),
            ]);

            $walletTransaction->update([
                'status' => TransactionStatus::COMPLETED,
                'processed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Funding completed successfully.',
                'quote' => $quote,
                'wallet_transaction_id' => $walletTransaction->id,
                'credit_request_id' => $creditRequest->id,
            ];
        }

        DB::transaction(function () use ($user, $walletTransaction, $creditRequest, $facebookResult): void {
            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $requiredBdtPoisha = abs((int) $walletTransaction->amount_bdt_poisha);

            $wallet->update([
                'current_balance_poisha' => (int) $wallet->current_balance_poisha + $requiredBdtPoisha,
                'lifetime_debit_poisha' => max(0, (int) $wallet->lifetime_debit_poisha - $requiredBdtPoisha),
                'last_activity_at' => now(),
            ]);

            $walletTransaction->update([
                'status' => TransactionStatus::FAILED,
                'metadata' => array_merge($walletTransaction->metadata ?? [], [
                    'facebook_error' => $facebookResult['error_message'],
                    'facebook_result' => $facebookResult,
                ]),
                'processed_at' => now(),
            ]);

            $creditRequest->update([
                'status' => FundingStatus::FAILED,
                'facebook_request_payload' => $facebookResult['request_payload'],
                'facebook_response_payload' => $facebookResult['response_payload'],
                'facebook_error_code' => $facebookResult['error_code'],
                'facebook_error_message' => $facebookResult['error_message'],
                'processed_at' => now(),
            ]);
        });

        return [
            'success' => false,
            'message' => 'Funding failed while updating Facebook spend cap. Wallet amount has been restored.',
            'quote' => $quote,
            'wallet_transaction_id' => $walletTransaction->id,
            'credit_request_id' => $creditRequest->id,
        ];
    }

    /**
     * @return array{success: bool, message: string, quote: array, wallet_transaction_id: ?int, credit_request_id: ?int}
     */
    private function buildIdempotentResponse(WalletTransaction $walletTransaction, AdAccount $adAccount, int $requestedUsdMicros): array
    {
        if ($walletTransaction->ad_account_id !== $adAccount->id) {
            throw ValidationException::withMessages([
                'ad_account_id' => 'This idempotency key belongs to a different ad account.',
            ]);
        }

        $storedRequestedUsdMicros = (int) ($walletTransaction->metadata['requested_usd_micros'] ?? 0);

        if ($storedRequestedUsdMicros !== $requestedUsdMicros) {
            throw ValidationException::withMessages([
                'requested_usd' => 'This idempotency key belongs to a different USD amount.',
            ]);
        }

        $creditRequest = AdAccountCreditRequest::query()
            ->where('wallet_transaction_id', $walletTransaction->id)
            ->first();

        $quote = $this->buildQuoteSnapshot($walletTransaction, $creditRequest);
        $isSuccess = $walletTransaction->status === TransactionStatus::COMPLETED;

        $message = match ($walletTransaction->status) {
            TransactionStatus::COMPLETED => 'This funding request was already completed.',
            TransactionStatus::FAILED => 'This funding request was already processed and failed.',
            default => 'This funding request is already being processed.',
        };

        return [
            'success' => $isSuccess,
            'message' => $message,
            'quote' => $quote,
            'wallet_transaction_id' => $walletTransaction->id,
            'credit_request_id' => $creditRequest?->id,
        ];
    }

    /**
     * @return array{
     *   requested_usd_micros: int,
     *   pricing_scope: string,
     *   pricing_bucket_id: int,
     *   pricing_bucket_table: ?string,
     *   pricing_bucket_name: ?string,
     *   bucket_min_usd_micros: int,
     *   bucket_max_usd_micros: int,
     *   rate_bdt_per_usd_poisha: int,
     *   required_bdt_poisha: int,
     *   wallet_balance_poisha: int,
     *   is_affordable: bool,
     *   max_affordable_usd_micros_in_bucket: int
     * }
     */
    private function buildQuoteSnapshot(WalletTransaction $walletTransaction, ?AdAccountCreditRequest $creditRequest): array
    {
        $requestedUsdMicros = (int) ($walletTransaction->metadata['requested_usd_micros'] ?? 0);
        $rateBdtPerUsdPoisha = (int) ($walletTransaction->metadata['rate_bdt_per_usd_poisha'] ?? 0);
        $requiredBdtPoisha = $creditRequest !== null
            ? (int) $creditRequest->required_bdt_poisha
            : abs((int) $walletTransaction->amount_bdt_poisha);
        $balanceBeforePoisha = (int) $walletTransaction->balance_before_poisha;

        return [
            'requested_usd_micros' => $requestedUsdMicros,
            'pricing_scope' => $creditRequest?->pricing_scope->value ?? (string) ($walletTransaction->metadata['pricing_scope'] ?? 'global'),
            'pricing_bucket_id' => (int) ($creditRequest?->pricing_bucket_id ?? ($walletTransaction->metadata['pricing_bucket_id'] ?? 0)),
            'pricing_bucket_table' => $creditRequest?->pricing_bucket_table ?? ($walletTransaction->metadata['pricing_bucket_table'] ?? null),
            'pricing_bucket_name' => null,
            'bucket_min_usd_micros' => 0,
            'bucket_max_usd_micros' => 0,
            'rate_bdt_per_usd_poisha' => $rateBdtPerUsdPoisha,
            'required_bdt_poisha' => $requiredBdtPoisha,
            'wallet_balance_poisha' => $balanceBeforePoisha,
            'is_affordable' => $requiredBdtPoisha <= $balanceBeforePoisha,
            'max_affordable_usd_micros_in_bucket' => $rateBdtPerUsdPoisha > 0
                ? intdiv($balanceBeforePoisha * 1_000_000, $rateBdtPerUsdPoisha)
                : 0,
        ];
    }

    private function isAdAccountActive(AdAccount $adAccount): bool
    {
        $status = $adAccount->status;

        if ($status instanceof AdAccountStatus) {
            return $status->isActive();
        }

        try {
            return AdAccountStatus::from((int) $status)->isActive();
        } catch (\ValueError) {
            return false;
        }
    }

    /**
     * @return array{success: bool, request_payload: array, response_payload: array|null, error_code: ?string, error_message: ?string}
     */
    private function updateFacebookSpendCap(AdAccount $adAccount, int $requestedUsdMicros): array
    {
        $businessManager = $adAccount->businessManager;

        if ($businessManager === null || $businessManager->access_token === '') {
            return [
                'success' => false,
                'request_payload' => [
                    'requested_usd_micros' => $requestedUsdMicros,
                ],
                'response_payload' => null,
                'error_code' => null,
                'error_message' => 'Business manager access token is missing.',
            ];
        }

        $service = FacebookMarketingService::create($businessManager->access_token);

        $currentSpendLimitResult = $service->getSpendLimit($adAccount->act_id);
        $currentSpendLimit = 0;

        if ($currentSpendLimitResult['success'] ?? false) {
            $currentSpendLimit = (int) ($currentSpendLimitResult['spend_limit'] ?? 0);
        }

        $deltaSpendCap = (int) round($requestedUsdMicros / 1_000_000);
        $targetSpendCap = max(0, $currentSpendLimit + $deltaSpendCap);

        $requestPayload = [
            'ad_account_act_id' => $adAccount->act_id,
            'current_spend_limit' => $currentSpendLimit,
            'delta_spend_cap' => $deltaSpendCap,
            'target_spend_cap' => $targetSpendCap,
            'requested_usd_micros' => $requestedUsdMicros,
        ];

        $result = $service->setSpendLimit($adAccount->act_id, $targetSpendCap);

        return [
            'success' => (bool) ($result['success'] ?? false),
            'request_payload' => $requestPayload,
            'response_payload' => $result,
            'error_code' => null,
            'error_message' => ($result['success'] ?? false) ? null : ($result['message'] ?? 'Unknown Facebook error.'),
        ];
    }
}
