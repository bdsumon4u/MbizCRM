<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BusinessManagerStatus;
use App\Http\Controllers\Controller;
use App\Models\BusinessManager;
use App\Services\FacebookMarketingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookBusinessManagerOAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $appId = (string) config('services.facebook.app_id');
        $redirectUri = $this->resolveRedirectUri();

        if ($appId === '') {
            return redirect()->route('filament.admin.resources.admin.business-managers.index')
                ->with('facebook_oauth_error', 'Facebook App ID is not configured.');
        }

        if ($redirectUri === null) {
            return redirect()->route('filament.admin.resources.admin.business-managers.index')
                ->with('facebook_oauth_error', 'FACEBOOK_REDIRECT_URI is not configured. Add the exact callback URL to .env and Facebook OAuth settings.');
        }

        $state = Str::random(40);
        $request->session()->put('facebook_oauth_state', $state);

        $scopes = array_values(array_filter(array_map(
            static fn (mixed $scope): string => trim((string) $scope),
            (array) config('services.facebook.oauth_scopes', ['business_management', 'ads_management'])
        )));

        if ($scopes === []) {
            $scopes = ['business_management', 'ads_management'];
        }

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return redirect()->away("https://www.facebook.com/v21.0/dialog/oauth?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('facebook_oauth_state', '');
        $providedState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $providedState)) {
            return redirect()->route('filament.admin.resources.admin.business-managers.index')
                ->with('facebook_oauth_error', 'Invalid OAuth state. Please try connecting again.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            $oauthError = (string) $request->query('error_description', 'Facebook OAuth authorization was not completed.');

            return redirect()->route('filament.admin.resources.admin.business-managers.index')
                ->with('facebook_oauth_error', $oauthError);
        }

        try {
            $accessToken = $this->exchangeAuthorizationCodeForAccessToken($code);
            $result = FacebookMarketingService::create($accessToken)->getAllBusinessManagers();

            if (($result['success'] ?? false) !== true) {
                throw new Exception($result['message'] ?? 'Failed to retrieve business managers from Facebook.');
            }

            $importedCount = 0;

            foreach ($result['businesses'] ?? [] as $business) {
                $bmId = (string) ($business['bm_id'] ?? '');
                if ($bmId === '') {
                    continue;
                }

                BusinessManager::query()->updateOrCreate(
                    ['bm_id' => $bmId],
                    [
                        'name' => (string) ($business['name'] ?? $bmId),
                        'access_token' => $accessToken,
                        'status' => BusinessManagerStatus::ACTIVE->value,
                        'synced_at' => now(),
                    ],
                );

                $importedCount++;
            }

            return redirect()->route('filament.admin.resources.admin.business-managers.index')
                ->with('facebook_oauth_success', "Facebook connected successfully. Imported {$importedCount} business managers.");
        } catch (Exception $exception) {
            Log::error('Facebook business manager OAuth callback failed: '.$exception->getMessage());

            return redirect()->route('filament.admin.resources.admin.business-managers.index')
                ->with('facebook_oauth_error', $exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function exchangeAuthorizationCodeForAccessToken(string $code): string
    {
        $redirectUri = $this->resolveRedirectUri();

        if ($redirectUri === null) {
            throw new Exception('FACEBOOK_REDIRECT_URI is not configured.');
        }

        $response = Http::asForm()->get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'client_id' => config('services.facebook.app_id'),
            'client_secret' => config('services.facebook.app_secret'),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if ($response->failed()) {
            $errorMessage = $response->json('error.message')
                ?? $response->json('error_description')
                ?? 'Failed to exchange authorization code for access token.';

            throw new Exception($errorMessage);
        }

        $accessToken = (string) $response->json('access_token', '');
        if ($accessToken === '') {
            throw new Exception('Facebook did not return an access token.');
        }

        return $accessToken;
    }

    private function resolveRedirectUri(): ?string
    {
        $configuredRedirectUri = trim((string) config('services.facebook.redirect_uri'));

        if ($configuredRedirectUri === '') {
            return null;
        }

        return $configuredRedirectUri;
    }
}
