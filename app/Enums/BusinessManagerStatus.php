<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessManagerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case PENDING_VERIFICATION = 'pending_verification';
    case RESTRICTED = 'restricted';
    case DISABLED = 'disabled';
    case ARCHIVED = 'archived';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [
            $case->value => $case->getLabel(),
        ])->toArray();
    }

    public static function optionsWithIcons(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [
            $case->value => [
                'label' => $case->getLabel(),
                'icon' => $case->getIcon(),
                'color' => $case->getColor(),
            ],
        ])->toArray();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::RESTRICTED => 'Restricted',
            self::DISABLED => 'Disabled',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-building-office-2',
            self::INACTIVE => 'heroicon-o-pause-circle',
            self::SUSPENDED => 'heroicon-o-x-circle',
            self::PENDING_VERIFICATION => 'heroicon-o-clock',
            self::RESTRICTED => 'heroicon-o-exclamation-triangle',
            self::DISABLED => 'heroicon-o-x-circle',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'danger',
            self::PENDING_VERIFICATION => 'warning',
            self::RESTRICTED => 'warning',
            self::DISABLED => 'danger',
            self::ARCHIVED => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Business Manager is active and fully operational',
            self::INACTIVE => 'Business Manager is inactive but can be reactivated',
            self::SUSPENDED => 'Business Manager is suspended due to policy violations',
            self::PENDING_VERIFICATION => 'Business Manager is pending identity verification',
            self::RESTRICTED => 'Business Manager has limited functionality due to restrictions',
            self::DISABLED => 'Business Manager is disabled and cannot be used',
            self::ARCHIVED => 'Business Manager has been archived',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canManageAdAccounts(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::RESTRICTED,
        ]);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [
            self::PENDING_VERIFICATION,
            self::SUSPENDED,
            self::RESTRICTED,
        ]);
    }

    public function isBlocked(): bool
    {
        return in_array($this, [
            self::SUSPENDED,
            self::DISABLED,
        ]);
    }
}
