<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class UserRateBucket extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $bucket): void {
            $bucket->validateRangeBoundaries();
            $bucket->validateOverlap();
        });
    }

    protected function casts(): array
    {
        return [
            'min_usd_micros' => 'integer',
            'max_usd_micros' => 'integer',
            'bdt_per_usd_poisha' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForUsdAmount(Builder $query, int $requestedUsdMicros): Builder
    {
        return $query
            ->where('min_usd_micros', '<=', $requestedUsdMicros)
            ->where('max_usd_micros', '>', $requestedUsdMicros);
    }

    private function validateRangeBoundaries(): void
    {
        if ((int) $this->min_usd_micros >= (int) $this->max_usd_micros) {
            throw ValidationException::withMessages([
                'min_usd_micros' => 'Min USD must be smaller than Max USD.',
                'max_usd_micros' => 'Max USD must be greater than Min USD.',
            ]);
        }
    }

    private function validateOverlap(): void
    {
        if ($this->user_id === null) {
            throw ValidationException::withMessages([
                'user_id' => 'User is required for a user-specific rate bucket.',
            ]);
        }

        $overlapExists = self::query()
            ->where('user_id', $this->user_id)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->where('min_usd_micros', '<', $this->max_usd_micros)
            ->where('max_usd_micros', '>', $this->min_usd_micros)
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'min_usd_micros' => 'This range overlaps an existing bucket for this user.',
                'max_usd_micros' => 'This range overlaps an existing bucket for this user.',
            ]);
        }
    }
}
