<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PricingScope;
use App\Models\GlobalRateBucket;
use App\Models\User;
use App\Models\UserRateBucket;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class RateResolverService
{
    /**
     * Resolve the matching BDT per USD rate for a requested USD amount.
     *
     * @return array{scope: PricingScope, bucket_id: int, bucket_table: string, bucket_name: ?string, min_usd_micros: int, max_usd_micros: int, bdt_per_usd_poisha: int}
     */
    public function resolveForUser(User $user, int $requestedUsdMicros, ?Carbon $at = null): array
    {
        $userBucket = UserRateBucket::query()
            ->forUser($user->id)
            ->active()
            ->forUsdAmount($requestedUsdMicros)
            ->orderBy('max_usd_micros')
            ->orderByDesc('min_usd_micros')
            ->first();

        if ($userBucket !== null) {
            return [
                'scope' => PricingScope::USER,
                'bucket_id' => $userBucket->id,
                'bucket_table' => $userBucket->getTable(),
                'bucket_name' => $userBucket->name,
                'min_usd_micros' => (int) $userBucket->min_usd_micros,
                'max_usd_micros' => (int) $userBucket->max_usd_micros,
                'bdt_per_usd_poisha' => (int) $userBucket->bdt_per_usd_poisha,
            ];
        }

        $globalBucket = GlobalRateBucket::query()
            ->active()
            ->forUsdAmount($requestedUsdMicros)
            ->orderBy('max_usd_micros')
            ->orderByDesc('min_usd_micros')
            ->first();

        if ($globalBucket === null) {
            throw ValidationException::withMessages([
                'requested_usd' => 'No active pricing bucket configured for the requested USD amount.',
            ]);
        }

        return [
            'scope' => PricingScope::GLOBAL,
            'bucket_id' => $globalBucket->id,
            'bucket_table' => $globalBucket->getTable(),
            'bucket_name' => $globalBucket->name,
            'min_usd_micros' => (int) $globalBucket->min_usd_micros,
            'max_usd_micros' => (int) $globalBucket->max_usd_micros,
            'bdt_per_usd_poisha' => (int) $globalBucket->bdt_per_usd_poisha,
        ];
    }
}
