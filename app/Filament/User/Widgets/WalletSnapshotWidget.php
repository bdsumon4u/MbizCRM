<?php

namespace App\Filament\User\Widgets;

use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletSnapshotWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $wallet = Wallet::query()
            ->where('user_id', auth('web')->id() ?? 0)
            ->first();

        $currentBalancePoisha = (int) ($wallet?->current_balance_poisha ?? 0);
        $reservedBalancePoisha = (int) ($wallet?->reserved_balance_poisha ?? 0);
        $availableBalancePoisha = max(0, $currentBalancePoisha - $reservedBalancePoisha);
        $lifetimeCreditPoisha = (int) ($wallet?->lifetime_credit_poisha ?? 0);
        $lifetimeDebitPoisha = (int) ($wallet?->lifetime_debit_poisha ?? 0);

        return [
            Stat::make('Wallet Balance (BDT)', $this->formatBdt($currentBalancePoisha))
                ->description('Current ledger balance')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),
            Stat::make('Available Balance (BDT)', $this->formatBdt($availableBalancePoisha))
                ->description('Current minus reserved')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
            Stat::make('Lifetime Top-ups (BDT)', $this->formatBdt($lifetimeCreditPoisha))
                ->description('Total credited to wallet')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
            Stat::make('Lifetime Spend (BDT)', $this->formatBdt($lifetimeDebitPoisha))
                ->description('Total debited from wallet')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),
        ];
    }

    private function formatBdt(int $poisha): string
    {
        return number_format($poisha / 100, 2);
    }
}
