<?php

namespace App\Filament\User\Widgets;

use App\Enums\FundingStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\AdAccountCreditRequest;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuickKpisWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth('web')->id() ?? 0;

        $topUpCount = WalletTransaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::WALLET_TOP_UP)
            ->where('status', TransactionStatus::COMPLETED)
            ->count();

        $fundingCompletedCount = WalletTransaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::AD_ACCOUNT_FUNDING)
            ->where('status', TransactionStatus::COMPLETED)
            ->count();

        $fundingFailedCount = WalletTransaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::AD_ACCOUNT_FUNDING)
            ->where('status', TransactionStatus::FAILED)
            ->count();

        $fundingTotalResolved = $fundingCompletedCount + $fundingFailedCount;
        $fundingSuccessRate = $fundingTotalResolved > 0
            ? round(($fundingCompletedCount / $fundingTotalResolved) * 100, 2)
            : 0.0;

        $pendingFundingCount = AdAccountCreditRequest::query()
            ->where('user_id', $userId)
            ->where('status', FundingStatus::PENDING)
            ->count();

        return [
            Stat::make('Top-ups Completed', (string) $topUpCount)
                ->description('All successful wallet top-ups')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('primary'),
            Stat::make('Funding Completed', (string) $fundingCompletedCount)
                ->description('Successful ad account funding requests')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Funding Success Rate', number_format($fundingSuccessRate, 2).'%')
                ->description('Completed vs failed funding outcomes')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('info'),
            Stat::make('Pending Funding', (string) $pendingFundingCount)
                ->description('Funding requests awaiting completion')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
