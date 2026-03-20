<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AdAccountStatus: int implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 1;
    case DISABLED = 2;
    case UNSETTLED = 3;
    case PENDING_RISK_REVIEW = 7;
    case PENDING_SETTLEMENT = 8;
    case IN_GRACE_PERIOD = 9;
    case PENDING_CLOSURE = 100;
    case CLOSED = 101;
    case ANY_ACTIVE = 201;
    case ANY_CLOSED = 202;

    /**
     * Get all active statuses
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::ACTIVE,
            self::IN_GRACE_PERIOD,
            self::ANY_ACTIVE,
        ];
    }

    /**
     * Get all closed statuses
     */
    public static function getClosedStatuses(): array
    {
        return [
            self::DISABLED,
            self::CLOSED,
            self::ANY_CLOSED,
        ];
    }

    /**
     * Get all pending statuses
     */
    public static function getPendingStatuses(): array
    {
        return [
            self::PENDING_RISK_REVIEW,
            self::PENDING_SETTLEMENT,
            self::PENDING_CLOSURE,
        ];
    }

    /**
     * Create enum from Facebook API status code
     */
    public static function fromFacebookStatus(int $status): self
    {
        return self::from($status);
    }

    /**
     * Get all statuses as options array for forms
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->getLabel();
        }

        return $options;
    }

    /**
     * Get the human-readable label for the status
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DISABLED => 'Disabled',
            self::UNSETTLED => 'Unsettled',
            self::PENDING_RISK_REVIEW => 'Pending Risk Review',
            self::PENDING_SETTLEMENT => 'Pending Settlement',
            self::IN_GRACE_PERIOD => 'In Grace Period',
            self::PENDING_CLOSURE => 'Pending Closure',
            self::CLOSED => 'Closed',
            self::ANY_ACTIVE => 'Any Active',
            self::ANY_CLOSED => 'Any Closed',
        };
    }

    /**
     * Get the color for the status (for UI display)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::DISABLED => 'danger',
            self::UNSETTLED => 'warning',
            self::PENDING_RISK_REVIEW => 'warning',
            self::PENDING_SETTLEMENT => 'warning',
            self::IN_GRACE_PERIOD => 'info',
            self::PENDING_CLOSURE => 'warning',
            self::CLOSED => 'danger',
            self::ANY_ACTIVE => 'success',
            self::ANY_CLOSED => 'danger',
        };
    }

    /**
     * Get the icon for the status
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::DISABLED => 'heroicon-o-x-circle',
            self::UNSETTLED => 'heroicon-o-exclamation-triangle',
            self::PENDING_RISK_REVIEW => 'heroicon-o-clock',
            self::PENDING_SETTLEMENT => 'heroicon-o-clock',
            self::IN_GRACE_PERIOD => 'heroicon-o-information-circle',
            self::PENDING_CLOSURE => 'heroicon-o-exclamation-triangle',
            self::CLOSED => 'heroicon-o-x-circle',
            self::ANY_ACTIVE => 'heroicon-o-check-circle',
            self::ANY_CLOSED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get the description for the status
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Account is active and can run ads',
            self::DISABLED => 'Account is disabled and cannot run ads',
            self::UNSETTLED => 'Account has unsettled payments',
            self::PENDING_RISK_REVIEW => 'Account is under risk review',
            self::PENDING_SETTLEMENT => 'Account has pending settlement',
            self::IN_GRACE_PERIOD => 'Account is in grace period',
            self::PENDING_CLOSURE => 'Account is pending closure',
            self::CLOSED => 'Account is permanently closed',
            self::ANY_ACTIVE => 'Any active account status',
            self::ANY_CLOSED => 'Any closed account status',
        };
    }

    /**
     * Check if the status is considered active
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::ACTIVE, self::IN_GRACE_PERIOD, self::ANY_ACTIVE => true,
            default => false,
        };
    }

    /**
     * Check if the status is considered closed/inactive
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::DISABLED, self::CLOSED, self::ANY_CLOSED => true,
            default => false,
        };
    }

    /**
     * Check if the status is pending some action
     */
    public function isPending(): bool
    {
        return match ($this) {
            self::PENDING_RISK_REVIEW, self::PENDING_SETTLEMENT, self::PENDING_CLOSURE => true,
            default => false,
        };
    }
}
