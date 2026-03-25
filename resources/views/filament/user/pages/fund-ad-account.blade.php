<x-filament-panels::page>
    @if ($quote)
        <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Funding Quote</h2>
            <dl class="grid grid-cols-1 gap-2 text-sm text-gray-700 md:grid-cols-2">
                <div><dt class="font-medium">Requested USD</dt><dd>{{ $quote['requested_usd'] }}</dd></div>
                <div><dt class="font-medium">Rate (BDT/USD)</dt><dd>{{ $quote['rate_bdt_per_usd'] }}</dd></div>
                <div><dt class="font-medium">Required BDT</dt><dd>{{ $quote['required_bdt'] }}</dd></div>
                <div><dt class="font-medium">Wallet Balance (BDT)</dt><dd>{{ $quote['wallet_balance_bdt'] }}</dd></div>
                <div><dt class="font-medium">Pricing Scope</dt><dd>{{ strtoupper($quote['pricing_scope']) }}</dd></div>
                <div><dt class="font-medium">Max Affordable USD (Current Bucket)</dt><dd>{{ $quote['max_affordable_usd'] }}</dd></div>
            </dl>

            <div class="rounded-md px-3 py-2 text-sm {{ $quote['is_affordable'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                {{ $quote['is_affordable'] ? 'This request is affordable and can be submitted.' : 'Insufficient balance for this amount.' }}
            </div>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-600">
            Use <strong>Preview Quote</strong> to calculate the required BDT before submitting funding.
        </div>
    @endif
</x-filament-panels::page>
