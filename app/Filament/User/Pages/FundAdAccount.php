<?php

namespace App\Filament\User\Pages;

use App\Enums\AdAccountStatus;
use App\Models\AdAccount;
use App\Services\AdAccountFundingQuoteService;
use App\Services\AdAccountFundingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class FundAdAccount extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Funding';

    protected static ?string $navigationLabel = 'Fund Ad Account';

    protected string $view = 'filament.user.pages.fund-ad-account';

    protected static ?string $title = 'Fund Ad Account';

    public ?int $adAccountId = null;

    public ?string $requestedUsd = null;

    public ?string $idempotencyKey = null;

    /** @var array<string, mixed>|null */
    public ?array $quote = null;

    public function getHeaderActions(): array
    {
        return [
            Action::make('previewQuote')
                ->label('Preview Quote')
                ->color('gray')
                ->form([
                    Select::make('ad_account_id')
                        ->label('Ad Account')
                        ->options($this->adAccountOptions())
                        ->required(),
                    TextInput::make('requested_usd')
                        ->label('Requested USD')
                        ->required()
                        ->numeric()
                        ->step('0.000001')
                        ->minValue(0.000001),
                ])
                ->action(function (array $data): void {
                    $this->adAccountId = (int) $data['ad_account_id'];
                    $this->requestedUsd = (string) $data['requested_usd'];
                    $this->idempotencyKey = (string) Str::uuid();

                    $quoteService = app(AdAccountFundingQuoteService::class);
                    $user = auth('web')->user();

                    if ($user === null) {
                        throw ValidationException::withMessages([
                            'auth' => 'You must be logged in to continue.',
                        ]);
                    }

                    $requestedUsdMicros = (int) round(((float) $this->requestedUsd) * 1_000_000);
                    $quote = $quoteService->quote($user, $requestedUsdMicros);
                    $this->quote = $this->presentQuote($quote);

                    Notification::make()
                        ->title('Quote ready')
                        ->success()
                        ->send();
                }),
            Action::make('submitFunding')
                ->label('Submit Funding')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    if ($this->adAccountId === null || $this->requestedUsd === null) {
                        throw ValidationException::withMessages([
                            'requested_usd' => 'Please preview a quote first.',
                        ]);
                    }

                    $user = auth('web')->user();

                    if ($user === null) {
                        throw ValidationException::withMessages([
                            'auth' => 'You must be logged in to continue.',
                        ]);
                    }

                    $adAccount = AdAccount::query()
                        ->where('id', $this->adAccountId)
                        ->where('user_id', $user->id)
                        ->firstOrFail();

                    $requestedUsdMicros = (int) round(((float) $this->requestedUsd) * 1_000_000);

                    $fundingService = app(AdAccountFundingService::class);
                    $result = $fundingService->commit(
                        $user,
                        $adAccount,
                        $requestedUsdMicros,
                        $this->idempotencyKey ?? (string) Str::uuid(),
                    );

                    $this->quote = $this->presentQuote($result['quote']);

                    $notification = Notification::make()
                        ->title($result['success'] ? 'Funding completed' : 'Funding failed')
                        ->body($result['message']);

                    if ($result['success']) {
                        $notification->success();
                    } else {
                        $notification->danger();
                    }

                    $notification->send();

                    if ($result['success']) {
                        $this->idempotencyKey = null;
                    }
                }),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function adAccountOptions(): array
    {
        $user = auth('web')->user();

        if ($user === null) {
            return [];
        }

        return AdAccount::query()
            ->where('user_id', $user->id)
            ->whereIn('status', array_map(
                static fn (AdAccountStatus $status): int => $status->value,
                AdAccountStatus::getActiveStatuses(),
            ))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AdAccount $adAccount): array => [
                $adAccount->id => $adAccount->name.' ('.$adAccount->act_id.')',
            ])
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $quote
     * @return array<string, mixed>
     */
    private function presentQuote(array $quote): array
    {
        return array_merge($quote, [
            'requested_usd' => number_format(((int) $quote['requested_usd_micros']) / 1_000_000, 6, '.', ''),
            'required_bdt' => number_format(((int) $quote['required_bdt_poisha']) / 100, 2, '.', ''),
            'wallet_balance_bdt' => number_format(((int) $quote['wallet_balance_poisha']) / 100, 2, '.', ''),
            'rate_bdt_per_usd' => number_format(((int) $quote['rate_bdt_per_usd_poisha']) / 100, 2, '.', ''),
            'max_affordable_usd' => number_format(((int) $quote['max_affordable_usd_micros_in_bucket']) / 1_000_000, 6, '.', ''),
        ]);
    }
}
