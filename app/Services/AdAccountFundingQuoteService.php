<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class AdAccountFundingQuoteService
{
    public function __construct(
        private RateResolverService $rateResolverService,
    ) {}

    /**
     * @return array{
     *   requested_usd_micros: int,
     *   pricing_scope: string,
     *   pricing_bucket_id: int,
     *   pricing_bucket_table: string,
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
    public function quote(User $user, int $requestedUsdMicros): array
    {
        if ($requestedUsdMicros <= 0) {
            throw ValidationException::withMessages([
                'requested_usd' => 'Requested USD amount must be greater than zero.',
            ]);
        }

        $resolvedRate = $this->rateResolverService->resolveForUser($user, $requestedUsdMicros);
        $rateBdtPerUsdPoisha = $resolvedRate['bdt_per_usd_poisha'];

        $requiredBdtPoisha = (int) ceil(($requestedUsdMicros * $rateBdtPerUsdPoisha) / 1_000_000);
        $walletBalancePoisha = (int) ($user->wallet?->current_balance_poisha ?? 0);

        $maxAffordableUsdMicrosByBalance = $rateBdtPerUsdPoisha === 0
            ? 0
            : intdiv($walletBalancePoisha * 1_000_000, $rateBdtPerUsdPoisha);

        $maxAffordableUsdMicrosInBucket = $maxAffordableUsdMicrosByBalance < $resolvedRate['min_usd_micros']
            ? 0
            : min($resolvedRate['max_usd_micros'], $maxAffordableUsdMicrosByBalance);

        return [
            'requested_usd_micros' => $requestedUsdMicros,
            'pricing_scope' => $resolvedRate['scope']->value,
            'pricing_bucket_id' => $resolvedRate['bucket_id'],
            'pricing_bucket_table' => $resolvedRate['bucket_table'],
            'pricing_bucket_name' => $resolvedRate['bucket_name'],
            'bucket_min_usd_micros' => $resolvedRate['min_usd_micros'],
            'bucket_max_usd_micros' => $resolvedRate['max_usd_micros'],
            'rate_bdt_per_usd_poisha' => $rateBdtPerUsdPoisha,
            'required_bdt_poisha' => $requiredBdtPoisha,
            'wallet_balance_poisha' => $walletBalancePoisha,
            'is_affordable' => $requiredBdtPoisha <= $walletBalancePoisha,
            'max_affordable_usd_micros_in_bucket' => $maxAffordableUsdMicrosInBucket,
        ];
    }
}
