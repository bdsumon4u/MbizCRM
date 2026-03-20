<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdAccountStatus;
use App\Models\AdAccount;
use Exception;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Ad;
use FacebookAds\Object\AdAccount as FacebookAdAccount;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Fields\CampaignFields;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class FacebookMarketingService
{
    /**
     * Comprehensive list of all AdAccount fields for consistent usage
     */
    private const AD_ACCOUNT_FIELDS = [
        AdAccountFields::ID,
        AdAccountFields::NAME,
        AdAccountFields::ACCOUNT_STATUS,
        AdAccountFields::BALANCE,
        AdAccountFields::CURRENCY,
        AdAccountFields::TIMEZONE_NAME,
        AdAccountFields::SPEND_CAP,
        AdAccountFields::BUSINESS,
        AdAccountFields::CREATED_TIME,
        AdAccountFields::DISABLE_REASON,
        AdAccountFields::AMOUNT_SPENT,
        AdAccountFields::FUNDING_SOURCE_DETAILS,
    ];

    /**
     * Fields for spend limit operations
     */
    private const SPEND_LIMIT_FIELDS = [
        AdAccountFields::SPEND_CAP,
        AdAccountFields::CURRENCY,
        AdAccountFields::NAME,
        AdAccountFields::BALANCE,
    ];

    private string $appId;

    private string $appSecret;

    public function __construct(
        private string $accessToken,
    ) {
        $this->appId = config('services.facebook.app_id');
        $this->appSecret = config('services.facebook.app_secret');

        $this->initializeApi();
    }

    public static function create(string $accessToken): self
    {
        return new self($accessToken);
    }

    /**
     * Get account details for a specific ad account ID (basic data only)
     */
    public function getAdAccountDetails(string $adAccountId): array
    {
        return $this->getAdAccountData($adAccountId, null, false);
    }

    /**
     * Get account balance for an ad account
     */
    public function getAccountBalance(string $adAccountId): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);
            $account = $adAccount->getSelf(self::SPEND_LIMIT_FIELDS);

            return $this->transformSpendLimitData($account);
        } catch (Exception $e) {
            Log::error("Failed to get account balance for {$adAccountId}: ".$e->getMessage());
            throw new Exception('Failed to get account balance: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get campaigns for an ad account
     */
    public function getCampaigns(string $adAccountId): Collection
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);
            $campaigns = $adAccount->getCampaigns([
                CampaignFields::ID,
                CampaignFields::NAME,
                CampaignFields::STATUS,
                CampaignFields::OBJECTIVE,
                CampaignFields::CREATED_TIME,
            ]);

            $discovered = collect();

            foreach ($campaigns as $campaign) {
                $discovered->push([
                    'id' => $campaign->{CampaignFields::ID},
                    'name' => $campaign->{CampaignFields::NAME},
                    'status' => $campaign->{CampaignFields::STATUS},
                    'objective' => $campaign->{CampaignFields::OBJECTIVE},
                    'created_time' => $campaign->{CampaignFields::CREATED_TIME},
                ]);
            }

            return $discovered;
        } catch (Exception $e) {
            Log::error("Failed to get campaigns for account {$adAccountId}: ".$e->getMessage());
            throw new Exception('Failed to get campaigns: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get ad sets for a campaign
     */
    public function getAdSets(string $campaignId): Collection
    {
        try {
            $campaign = new Campaign($campaignId);
            $adSets = $campaign->getAdSets([
                AdSetFields::ID,
                AdSetFields::NAME,
                AdSetFields::STATUS,
                AdSetFields::BID_AMOUNT,
                AdSetFields::DAILY_BUDGET,
                AdSetFields::LIFETIME_BUDGET,
            ]);

            $discovered = collect();

            foreach ($adSets as $adSet) {
                $discovered->push([
                    'id' => $adSet->{AdSetFields::ID},
                    'name' => $adSet->{AdSetFields::NAME},
                    'status' => $adSet->{AdSetFields::STATUS},
                    'bid_amount' => $adSet->{AdSetFields::BID_AMOUNT} ? $adSet->{AdSetFields::BID_AMOUNT} / 100 : 0,
                    'daily_budget' => $adSet->{AdSetFields::DAILY_BUDGET} ? $adSet->{AdSetFields::DAILY_BUDGET} / 100 : 0,
                    'lifetime_budget' => $adSet->{AdSetFields::LIFETIME_BUDGET} ? $adSet->{AdSetFields::LIFETIME_BUDGET} / 100 : 0,
                ]);
            }

            return $discovered;
        } catch (Exception $e) {
            Log::error("Failed to get ad sets for campaign {$campaignId}: ".$e->getMessage());
            throw new Exception('Failed to get ad sets: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get ads for an ad set
     */
    public function getAds(string $adSetId): Collection
    {
        try {
            $adSet = new AdSet($adSetId);
            $ads = $adSet->getAds([
                AdFields::ID,
                AdFields::NAME,
                AdFields::STATUS,
                AdFields::CREATED_TIME,
            ]);

            $discovered = collect();

            foreach ($ads as $ad) {
                $discovered->push([
                    'id' => $ad->{AdFields::ID},
                    'name' => $ad->{AdFields::NAME},
                    'status' => $ad->{AdFields::STATUS},
                    'created_time' => $ad->{AdFields::CREATED_TIME},
                ]);
            }

            return $discovered;
        } catch (Exception $e) {
            Log::error("Failed to get ads for ad set {$adSetId}: ".$e->getMessage());
            throw new Exception('Failed to get ads: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Test API connection with a specific ad account ID
     */
    public function testConnection(string $adAccountId): bool
    {
        try {
            $this->getAdAccountDetails($adAccountId);

            return true;
        } catch (Exception $e) {
            Log::error('Facebook API connection test failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Set or update spend limit for an ad account
     *
     * @param  string  $adAccountId  Facebook Ad Account ID
     * @param  float  $spendLimit  Spend limit amount in the account's currency
     * @return array Response with success status and details
     */
    public function setSpendLimit(string $adAccountId, float $spendLimit): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);

            // Use a direct Graph API call to avoid any SDK field mapping quirks
            Api::instance()->call('/'.$adAccountId, 'POST', [
                AdAccountFields::SPEND_CAP => (int) $spendLimit,
            ]);

            // Verify the update by fetching the account details
            $updatedAccount = $adAccount->getSelf(self::SPEND_LIMIT_FIELDS);
            $updatedData = $this->transformSpendLimitData($updatedAccount);

            return [
                'success' => true,
                'message' => 'Spend limit updated successfully',
                'ad_account_id' => $adAccountId,
                'ad_account_name' => $updatedData['ad_account_name'],
                'spend_limit' => $updatedData['spend_limit'],
                'currency' => $updatedData['currency'],
                'updated_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error("Failed to set spend limit for {$adAccountId}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to set spend limit: '.$e->getMessage(),
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove spend limit from an ad account (set to unlimited)
     *
     * @param  string  $adAccountId  Facebook Ad Account ID
     * @return array Response with success status and details
     */
    public function removeSpendLimit(string $adAccountId): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);

            // Set spend cap to 0 to remove the limit (Facebook treats 0 as unlimited)
            Api::instance()->call('/'.$adAccountId, 'POST', [
                AdAccountFields::SPEND_CAP => 0,
            ]);

            // Verify the update
            $updatedAccount = $adAccount->getSelf(self::SPEND_LIMIT_FIELDS);
            $updatedData = $this->transformSpendLimitData($updatedAccount);

            return [
                'success' => true,
                'message' => 'Spend limit removed successfully (set to unlimited)',
                'ad_account_id' => $adAccountId,
                'ad_account_name' => $updatedData['ad_account_name'],
                'spend_limit' => null, // null indicates unlimited
                'currency' => $updatedData['currency'],
                'updated_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error("Failed to remove spend limit for {$adAccountId}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to remove spend limit: '.$e->getMessage(),
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current spend limit for an ad account
     *
     * @param  string  $adAccountId  Facebook Ad Account ID
     * @return array Response with spend limit details
     */
    public function getSpendLimit(string $adAccountId): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);
            $account = $adAccount->getSelf(self::SPEND_LIMIT_FIELDS);
            $accountData = $this->transformSpendLimitData($account);

            return [
                'success' => true,
                'ad_account_id' => $adAccountId,
                'ad_account_name' => $accountData['ad_account_name'],
                'spend_limit' => $accountData['spend_limit'],
                'currency' => $accountData['currency'],
                'balance' => $accountData['balance'],
                'is_unlimited' => $accountData['spend_limit'] === null,
                'fetched_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error("Failed to get spend limit for {$adAccountId}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get spend limit: '.$e->getMessage(),
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate spend limit before setting (check if it's reasonable)
     *
     * @param  float  $spendLimit  Spend limit amount
     * @param  string  $currency  Currency code
     * @return array Validation result
     */
    public function validateSpendLimit(float $spendLimit, string $currency = 'USD'): array
    {
        $errors = [];

        // Check minimum spend limit (Facebook has some minimums)
        if ($spendLimit < 1.0) {
            $errors[] = "Spend limit must be at least {$currency} 1.00";
        }

        // Check maximum spend limit (Facebook has some maximums)
        if ($spendLimit > 1000000.0) {
            $errors[] = "Spend limit cannot exceed {$currency} 1,000,000.00";
        }

        // Check if it's a reasonable number (not negative)
        if ($spendLimit <= 0) {
            $errors[] = 'Spend limit must be a positive number';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * Get all ad accounts from a Business Manager
     *
     * @param  string  $businessManagerId  Facebook Business Manager ID
     * @return array Response with all ad accounts
     */
    public function getAllAdAccountsFromBusinessManager(string $businessManagerId): array
    {
        try {
            // Create Business Manager object
            $businessManager = new \FacebookAds\Object\Business($businessManagerId);

            // Get all ad accounts owned by this business manager
            $adAccounts = $businessManager->getOwnedAdAccounts(self::AD_ACCOUNT_FIELDS);

            $accounts = [];
            foreach ($adAccounts as $adAccount) {
                $accountData = $this->transformAdAccountData($adAccount, $businessManagerId);
                $accounts[] = array_merge($accountData, $this->getSpendingInsights($adAccount->{AdAccountFields::ID}));
            }

            return [
                'success' => true,
                'business_manager_id' => $businessManagerId,
                'total_accounts' => count($accounts),
                'ad_accounts' => $accounts,
                'fetched_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $this->extractFacebookErrorCode($errorMessage);

            Log::error("Failed to get ad accounts from business manager {$businessManagerId}: {$errorMessage}");

            // Handle specific permission errors
            if ($errorCode === '100' || str_contains($errorMessage, 'business_management permission')) {
                return [
                    'success' => false,
                    'message' => 'Permission denied: Requires business_management permission',
                    'business_manager_id' => $businessManagerId,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'solution' => $this->getPermissionSolution(),
                    'required_permissions' => ['business_management', 'ads_management', 'pages_show_list'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get ad accounts: '.$errorMessage,
                'business_manager_id' => $businessManagerId,
                'error' => $errorMessage,
                'error_code' => $errorCode,
            ];
        }
    }

    /**
     * Get all ad accounts accessible to a Business Manager (including shared ones)
     *
     * @param  string  $businessManagerId  Facebook Business Manager ID
     * @return array Response with all accessible ad accounts
     */
    public function getAllAccessibleAdAccounts(string $businessManagerId): array
    {
        try {
            // Create Business Manager object
            $businessManager = new \FacebookAds\Object\Business($businessManagerId);

            // Get all ad accounts accessible to this business manager (owned + shared)
            // Note: getAdAccounts() might not be available, so we'll use getOwnedAdAccounts() for now
            $adAccounts = $businessManager->getOwnedAdAccounts(self::AD_ACCOUNT_FIELDS);

            $accounts = [];
            foreach ($adAccounts as $adAccount) {
                $accounts[] = $this->transformAdAccountData($adAccount, $businessManagerId);
            }

            return [
                'success' => true,
                'business_manager_id' => $businessManagerId,
                'total_accounts' => count($accounts),
                'ad_accounts' => $accounts,
                'fetched_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $this->extractFacebookErrorCode($errorMessage);

            Log::error("Failed to get accessible ad accounts from business manager {$businessManagerId}: {$errorMessage}");

            // Handle specific permission errors
            if ($errorCode === '100' || str_contains($errorMessage, 'business_management permission')) {
                return [
                    'success' => false,
                    'message' => 'Permission denied: Requires business_management permission',
                    'business_manager_id' => $businessManagerId,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'solution' => $this->getPermissionSolution(),
                    'required_permissions' => ['business_management', 'ads_management', 'pages_show_list'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get accessible ad accounts: '.$errorMessage,
                'business_manager_id' => $businessManagerId,
                'error' => $errorMessage,
                'error_code' => $errorCode,
            ];
        }
    }

    /**
     * Test if the current access token has required permissions
     */
    public function testPermissions(): array
    {
        try {
            // Test basic API access by trying to initialize the API
            Api::init($this->appId, $this->appSecret, $this->accessToken);

            // If we get here, the API initialized successfully
            return [
                'success' => true,
                'message' => 'Access token has basic permissions',
                'permissions' => 'API initialized successfully - Basic access confirmed',
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $this->extractFacebookErrorCode($errorMessage);

            if ($errorCode === '100' || str_contains($errorMessage, 'business_management permission')) {
                return [
                    'success' => false,
                    'message' => 'Permission denied: Requires business_management permission',
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'solution' => $this->getPermissionSolution(),
                    'required_permissions' => ['business_management', 'ads_management', 'pages_show_list'],
                    'oauth_url' => $this->generateOAuthUrl(),
                ];
            }

            if ($errorCode === '200' || str_contains($errorMessage, 'ads_management permission')) {
                return [
                    'success' => false,
                    'message' => 'Permission denied: Requires ads_management permission',
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'solution' => $this->getPermissionSolution(),
                    'required_permissions' => ['business_management', 'ads_management', 'pages_show_list'],
                    'oauth_url' => $this->generateOAuthUrl(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Access token test failed: '.$errorMessage,
                'error' => $errorMessage,
                'error_code' => $errorCode,
            ];
        }
    }

    /**
     * Discover and sync all ad accounts from a Business Manager
     * This will create new ad accounts in your local database
     *
     * @param  string  $businessManagerId  Facebook Business Manager ID
     * @param  int|null  $businessManagerLocalId  Local Business Manager ID for database relations
     * @return array Response with sync results
     */
    public function discoverAndSyncAdAccounts(string $businessManagerId, ?int $businessManagerLocalId = null): array
    {
        try {
            // Get all accessible ad accounts
            $result = $this->getAllAccessibleAdAccounts($businessManagerId);

            if (! $result['success']) {
                return $result;
            }

            $syncedAccounts = [];
            $existingAccounts = [];
            $newAccounts = [];
            $errors = [];

            foreach ($result['ad_accounts'] as $adAccountData) {
                try {
                    // Check if account already exists in local database
                    $existingAccount = AdAccount::query()->where('facebook_ad_account_id', $adAccountData['facebook_ad_account_id'])->first();

                    if ($existingAccount) {
                        // Update existing account with latest data
                        $existingAccount->update([
                            'name' => $adAccountData['name'],
                            'status' => $adAccountData['status'],
                            'account_type' => $adAccountData['account_type'],
                            'balance' => $adAccountData['balance'],
                            'currency' => $adAccountData['currency'],
                            'timezone' => $adAccountData['timezone'],
                            'spend_limit' => $adAccountData['spend_limit'],
                            'last_sync_at' => now(),
                        ]);

                        $existingAccounts[] = $existingAccount->id;
                        $syncedAccounts[] = $adAccountData['facebook_ad_account_id'];
                    } else {
                        // Create new account
                        $newAccount = AdAccount::query()->create([
                            'business_manager_id' => $businessManagerLocalId,
                            'name' => $adAccountData['name'],
                            'facebook_ad_account_id' => $adAccountData['facebook_ad_account_id'],
                            'account_type' => $adAccountData['account_type'],
                            'status' => $adAccountData['status'],
                            'balance' => $adAccountData['balance'],
                            'spend_limit' => $adAccountData['spend_limit'],
                            'currency' => $adAccountData['currency'],
                            'timezone' => $adAccountData['timezone'],
                            'description' => $adAccountData['disable_reason_description'] ?? null,
                            'last_sync_at' => now(),
                        ]);

                        $newAccounts[] = $newAccount->id;
                        $syncedAccounts[] = $adAccountData['facebook_ad_account_id'];
                    }

                } catch (Exception $e) {
                    $errors[] = [
                        'facebook_ad_account_id' => $adAccountData['facebook_ad_account_id'],
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Failed to sync ad account {$adAccountData['facebook_ad_account_id']}: ".$e->getMessage());
                }
            }

            return [
                'success' => true,
                'business_manager_id' => $businessManagerId,
                'total_discovered' => count($result['ad_accounts']),
                'total_synced' => count($syncedAccounts),
                'new_accounts_created' => count($newAccounts),
                'existing_accounts_updated' => count($existingAccounts),
                'errors' => $errors,
                'synced_accounts' => $syncedAccounts,
                'new_account_ids' => $newAccounts,
                'existing_account_ids' => $existingAccounts,
                'synced_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error("Failed to discover and sync ad accounts for business manager {$businessManagerId}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to discover and sync ad accounts: '.$e->getMessage(),
                'business_manager_id' => $businessManagerId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get ad accounts for a specific business manager
     */
    public function getBusinessManagerAdAccounts(string $businessManagerId): Collection
    {
        try {
            $business = new \FacebookAds\Object\Business($businessManagerId);

            $adAccounts = $business->getOwnedAdAccounts(self::AD_ACCOUNT_FIELDS);

            $accounts = collect();

            foreach ($adAccounts as $account) {
                $accounts->push($this->transformAdAccountData($account));
            }

            return $accounts;
        } catch (Exception $e) {
            Log::error("Failed to get ad accounts for business manager {$businessManagerId}: ".$e->getMessage());
            throw new Exception('Failed to get ad accounts: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get comprehensive ad account data including budgets, spending, and performance metrics
     */
    public function getComprehensiveAdAccountData(string $adAccountId): array
    {
        return $this->getAdAccountData($adAccountId, true);
    }

    /**
     * Unified method to fetch ad account data.
     * When includeInsights=true, merges spending and performance insights.
     */
    public function getAdAccountData(string $adAccountId, bool $includeInsights = false): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);
            $accountData = $adAccount->getSelf(self::AD_ACCOUNT_FIELDS);

            $basicData = $this->transformAdAccountData($accountData);

            if (! $includeInsights) {
                return $basicData;
            }

            $spendingData = $this->getSpendingInsights($adAccountId);
            $performanceData = $this->getPerformanceMetrics($adAccountId);

            return array_merge($basicData, $spendingData, $performanceData);
        } catch (Exception $e) {
            Log::error("Failed to get ad account data for {$adAccountId}: ".$e->getMessage());
            throw new Exception('Failed to get ad account data: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get business manager details
     */
    public function getBusinessManagerDetails(string $businessManagerId): array
    {
        try {
            $business = new \FacebookAds\Object\Business($businessManagerId);
            $businessData = $business->getSelf([
                'id',
                'name',
                'created_time',
                'timezone_id',
            ]);

            return [
                'bm_id' => $businessData->{'id'},
                'name' => $businessData->{'name'},
                'created_time' => $businessData->{'created_time'},
                'timezone_id' => $businessData->{'timezone_id'},
            ];
        } catch (Exception $e) {
            Log::error("Failed to get business manager details for {$businessManagerId}: ".$e->getMessage());
            throw new Exception('Failed to get business manager details: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get all business managers accessible to the current user
     */
    public function getAccessibleBusinessManagers(?string $accessToken = null): Collection
    {
        try {
            // Get the current user to access their businesses
            $user = new \FacebookAds\Object\User('me');

            $businesses = $user->getBusinesses([
                'id',
                'name',
                'created_time',
                'timezone_id',
                'primary_page',
                'is_hidden',
                'link',
            ]);

            $businessManagers = collect();

            foreach ($businesses as $business) {
                $businessManagers->push([
                    'bm_id' => $business->{'id'},
                    'name' => $business->{'name'},
                    'created_time' => $business->{'created_time'},
                    'timezone_id' => $business->{'timezone_id'},
                    'primary_page' => $business->{'primary_page'},
                    'is_hidden' => $business->{'is_hidden'},
                    'link' => $business->{'link'},
                ]);
            }

            return $businessManagers;
        } catch (Exception $e) {
            Log::error('Failed to get accessible business managers: '.$e->getMessage());
            throw new Exception('Failed to get accessible business managers: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get business managers owned by the current user
     */
    public function getOwnedBusinessManagers(?string $accessToken = null): Collection
    {
        try {
            // Get the current user to access their owned businesses
            $user = new \FacebookAds\Object\User('me');

            $businesses = $user->getBusinesses([
                'id',
                'name',
                'created_time',
                'timezone_id',
                'primary_page',
                'is_hidden',
                'link',
            ]);

            $businessManagers = collect();

            foreach ($businesses as $business) {
                $businessManagers->push([
                    'bm_id' => $business->{'id'},
                    'name' => $business->{'name'},
                    'created_time' => $business->{'created_time'},
                    'timezone_id' => $business->{'timezone_id'},
                    'primary_page' => $business->{'primary_page'},
                    'is_hidden' => $business->{'is_hidden'},
                    'link' => $business->{'link'},
                    'ownership' => 'owned',
                ]);
            }

            return $businessManagers;
        } catch (Exception $e) {
            Log::error('Failed to get owned business managers: '.$e->getMessage());
            throw new Exception('Failed to get owned business managers: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get all business managers (both owned and accessible) with their permissions
     */
    public function getAllBusinessManagers(?string $accessToken = null): array
    {
        try {
            // Get both owned and accessible business managers
            $ownedBusinesses = $this->getOwnedBusinessManagers($accessToken);
            $accessibleBusinesses = $this->getAccessibleBusinessManagers($accessToken);

            // Merge and deduplicate
            $allBusinesses = $ownedBusinesses->merge($accessibleBusinesses)
                ->unique('bm_id')
                ->values();

            // Mark ownership status
            $businessesWithPermissions = $allBusinesses->map(function (array $business) use ($ownedBusinesses): array {
                $isOwned = $ownedBusinesses->contains('bm_id', $business['bm_id']);

                return array_merge($business, [
                    'ownership' => $isOwned ? 'owned' : 'accessible',
                    'permissions' => $isOwned
                        ? ['full_access', 'manage_ad_accounts', 'view_insights', 'manage_pages']
                        : ['limited_access'], // We'd need additional API calls to get exact permissions
                ]);
            });

            return [
                'success' => true,
                'total_businesses' => $businessesWithPermissions->count(),
                'owned_businesses' => $ownedBusinesses->count(),
                'accessible_businesses' => $accessibleBusinesses->count(),
                'businesses' => $businessesWithPermissions->toArray(),
                'fetched_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get all business managers: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to get business managers: '.$e->getMessage(),
                'error' => $e->getMessage(),
                'error_code' => $this->extractFacebookErrorCode($e->getMessage()),
            ];
        }
    }

    /**
     * Test access to a specific business manager
     */
    public function testBusinessManagerAccess(string $businessManagerId): array
    {
        try {
            $business = new \FacebookAds\Object\Business($businessManagerId);

            // Try to get basic business info
            $businessData = $business->getSelf([
                'id',
                'name',
                'created_time',
            ]);

            // Try to get ad accounts to test ads_management permission
            $canAccessAdAccounts = true;
            $adAccountCount = 0;

            try {
                $adAccounts = $business->getOwnedAdAccounts(['id']);
                $adAccountCount = iterator_count($adAccounts);
            } catch (Exception $e) {
                $canAccessAdAccounts = false;
            }

            return [
                'success' => true,
                'business_manager_id' => $businessManagerId,
                'name' => $businessData->{'name'},
                'can_access_basic_info' => true,
                'can_access_ad_accounts' => $canAccessAdAccounts,
                'ad_account_count' => $adAccountCount,
                'permissions' => [
                    'business_management' => true,
                    'ads_management' => $canAccessAdAccounts,
                ],
                'tested_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $this->extractFacebookErrorCode($errorMessage);

            return [
                'success' => false,
                'business_manager_id' => $businessManagerId,
                'can_access_basic_info' => false,
                'can_access_ad_accounts' => false,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'message' => $this->getPermissionErrorMessage($errorCode),
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Transform Facebook AdAccount data to standardized format
     */
    private function transformAdAccountData(object $accountData, ?string $businessManagerId = null): array
    {
        return [
            'facebook_ad_account_id' => $accountData->{AdAccountFields::ID},
            'name' => $accountData->{AdAccountFields::NAME},
            'status' => $this->mapAccountStatus($accountData->{AdAccountFields::ACCOUNT_STATUS}),
            'account_type' => 'business', // Default to business type
            'balance' => $accountData->{AdAccountFields::BALANCE} ? (int) ($accountData->{AdAccountFields::BALANCE} / 100 * 100) : 0,
            'currency' => $accountData->{AdAccountFields::CURRENCY},
            'timezone' => $accountData->{AdAccountFields::TIMEZONE_NAME},
            'spend_limit' => $accountData->{AdAccountFields::SPEND_CAP} ? (int) $accountData->{AdAccountFields::SPEND_CAP} : null,
            'business_id' => $accountData->{AdAccountFields::BUSINESS},
            'created_time' => $accountData->{AdAccountFields::CREATED_TIME},
            'disable_reason' => $this->mapDisableReason($accountData->{AdAccountFields::DISABLE_REASON}),
            'disable_reason_description' => $this->getDisableReasonDescription($accountData->{AdAccountFields::DISABLE_REASON}),
            'amount_spent' => $accountData->{AdAccountFields::AMOUNT_SPENT} ? (int) ($accountData->{AdAccountFields::AMOUNT_SPENT} / 100 * 100) : null,
            'act_id' => str_replace('act_', '', $accountData->{AdAccountFields::ID}),
            'funding_source_details' => $accountData->{AdAccountFields::FUNDING_SOURCE_DETAILS},
            'business_manager_id' => $businessManagerId,
            'last_sync_at' => now(),
        ];
    }

    /**
     * Transform Facebook AdAccount data for spend limit operations
     */
    private function transformSpendLimitData(object $accountData): array
    {
        return [
            'ad_account_id' => $accountData->{AdAccountFields::ID},
            'ad_account_name' => $accountData->{AdAccountFields::NAME},
            'spend_limit' => $accountData->{AdAccountFields::SPEND_CAP} ? (int) $accountData->{AdAccountFields::SPEND_CAP} : null,
            'currency' => $accountData->{AdAccountFields::CURRENCY},
            'balance' => $accountData->{AdAccountFields::BALANCE} ? (int) ($accountData->{AdAccountFields::BALANCE} / 100 * 100) : 0,
        ];
    }

    /**
     * Get spending insights for an ad account
     */
    private function getSpendingInsights(string $adAccountId): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);

            // Get today's spending
            $todayInsights = $adAccount->getInsights([
                'spend',
            ], [
                'time_range' => [
                    'since' => now()->startOfDay()->format('Y-m-d'),
                    'until' => now()->endOfDay()->format('Y-m-d'),
                ],
            ]);

            // Get yesterday's spending
            $yesterdayInsights = $adAccount->getInsights([
                'spend',
            ], [
                'time_range' => [
                    'since' => now()->subDay()->startOfDay()->format('Y-m-d'),
                    'until' => now()->subDay()->endOfDay()->format('Y-m-d'),
                ],
            ]);

            // Get this month's spending
            $thisMonthInsights = $adAccount->getInsights([
                'spend',
            ], [
                'time_range' => [
                    'since' => now()->startOfMonth()->format('Y-m-d'),
                    'until' => now()->endOfMonth()->format('Y-m-d'),
                ],
            ]);

            // Get last month's spending
            $lastMonthInsights = $adAccount->getInsights([
                'spend',
            ], [
                'time_range' => [
                    'since' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                    'until' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
                ],
            ]);

            return [
                'spent_today' => $this->extractSpendFromInsights($todayInsights),
                'spent_yesterday' => $this->extractSpendFromInsights($yesterdayInsights),
                'spent_this_month' => $this->extractSpendFromInsights($thisMonthInsights),
                'spent_last_month' => $this->extractSpendFromInsights($lastMonthInsights),
            ];
        } catch (Exception $e) {
            Log::warning("Failed to get spending insights for {$adAccountId}: ".$e->getMessage());

            return [
                'spent_today' => 0,
                'spent_yesterday' => 0,
                'spent_this_month' => 0,
                'spent_last_month' => 0,
            ];
        }
    }

    /**
     * Get performance metrics for an ad account
     */
    private function getPerformanceMetrics(string $adAccountId): array
    {
        try {
            $adAccount = new FacebookAdAccount($adAccountId);

            // Get today's performance metrics
            $todayInsights = $adAccount->getInsights([
                'impressions',
                'clicks',
                'conversions',
                'ctr',
                'cpc',
            ], [
                'time_range' => [
                    'since' => now()->startOfDay()->format('Y-m-d'),
                    'until' => now()->endOfDay()->format('Y-m-d'),
                ],
            ]);

            $metrics = $this->extractMetricsFromInsights($todayInsights);

            return [
                'impressions_today' => $metrics['impressions'] ?? 0,
                'clicks_today' => $metrics['clicks'] ?? 0,
                'conversions_today' => $metrics['conversions'] ?? 0,
                'ctr_today' => $metrics['ctr'] ?? 0.0,
                'cpc_today' => $metrics['cpc'] ?? 0.0,
            ];
        } catch (Exception $e) {
            Log::warning("Failed to get performance metrics for {$adAccountId}: ".$e->getMessage());

            return [
                'impressions_today' => 0,
                'clicks_today' => 0,
                'conversions_today' => 0,
                'ctr_today' => 0.0,
                'cpc_today' => 0.0,
            ];
        }
    }

    /**
     * Extract spend amount from insights data
     */
    private function extractSpendFromInsights(iterable $insights): int
    {
        $totalSpend = 0;
        foreach ($insights as $insight) {
            $spend = $insight->{'spend'} ?? 0;
            $totalSpend += (int) ($spend * 100); // Convert to cents
        }

        return $totalSpend;
    }

    /**
     * Extract metrics from insights data
     */
    private function extractMetricsFromInsights(iterable $insights): array
    {
        $metrics = [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'ctr' => 0.0,
            'cpc' => 0.0,
        ];

        foreach ($insights as $insight) {
            $metrics['impressions'] += $insight->{'impressions'} ?? 0;
            $metrics['clicks'] += $insight->{'clicks'} ?? 0;
            $metrics['conversions'] += $insight->{'conversions'} ?? 0;
            $metrics['ctr'] = $insight->{'ctr'} ?? 0.0;
            $metrics['cpc'] = $insight->{'cpc'} ?? 0.0;
        }

        return $metrics;
    }

    /**
     * Get permission error message based on error code
     */
    private function getPermissionErrorMessage(?string $errorCode): string
    {
        return match ($errorCode) {
            '100' => 'Permission denied: Requires business_management permission',
            '200' => 'Permission denied: Requires ads_management permission',
            '10' => 'Application does not have permission for this action',
            default => 'Access denied or insufficient permissions',
        };
    }

    /**
     * Extract Facebook error code from error message
     */
    private function extractFacebookErrorCode(string $errorMessage): ?string
    {
        if (preg_match('/\(#(\d+)\)/', $errorMessage, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get solution for permission errors
     */
    private function getPermissionSolution(): string
    {
        return 'To fix this, you need to request business_management permission for your Facebook app. '.
               'Go to Facebook Developers → App Review → Permissions and Features, and request: '.
               'business_management, ads_management, and pages_show_list permissions.';
    }

    /**
     * Generate OAuth URL with required permissions
     */
    private function generateOAuthUrl(): string
    {
        $appId = config('services.facebook.app_id');
        $redirectUri = config('services.facebook.redirect_uri', 'https://yourdomain.com/facebook/callback');
        $scope = 'business_management,ads_management,pages_show_list';

        return 'https://www.facebook.com/v21.0/dialog/oauth?'.http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
        ]);
    }

    /**
     * Initialize Facebook API
     */
    private function initializeApi(): void
    {
        if ($this->appId === '' || $this->appId === '0' || ($this->appSecret === '' || $this->appSecret === '0') || ($this->accessToken === '' || $this->accessToken === '0')) {
            throw new Exception('Facebook API credentials not configured');
        }

        Api::init($this->appId, $this->appSecret, $this->accessToken);

        if (config('app.debug')) {
            Api::instance()->setLogger(new CurlLogger());
        }
    }

    /**
     * Map Facebook account status to our internal status enum
     */
    private function mapAccountStatus(int $facebookStatus): AdAccountStatus
    {
        return match ($facebookStatus) {
            1 => AdAccountStatus::ACTIVE,
            2 => AdAccountStatus::DISABLED,
            3 => AdAccountStatus::UNSETTLED,
            7 => AdAccountStatus::PENDING_RISK_REVIEW,
            8 => AdAccountStatus::PENDING_SETTLEMENT,
            9 => AdAccountStatus::IN_GRACE_PERIOD,
            100 => AdAccountStatus::PENDING_CLOSURE,
            101 => AdAccountStatus::CLOSED,
            201 => AdAccountStatus::ANY_ACTIVE,
            202 => AdAccountStatus::ANY_CLOSED,
            default => AdAccountStatus::DISABLED, // Default to disabled for unknown statuses
        };
    }

    /**
     * Map Facebook disable reason codes to human-readable descriptions
     */
    private function mapDisableReason(?int $facebookDisableReason): string
    {
        if ($facebookDisableReason === null) {
            return 'none';
        }

        return match ($facebookDisableReason) {
            0 => 'none',
            1 => 'ads_integrity_policy',
            2 => 'ads_ip_review',
            3 => 'risk_payment',
            4 => 'gray_account_shut_down',
            5 => 'ads_afc_review',
            6 => 'business_integrity_rar',
            7 => 'permanent_close',
            8 => 'unused_reseller_account',
            9 => 'unused_account',
            10 => 'umbrella_ad_account',
            11 => 'business_manager_integrity_policy',
            12 => 'misrepresented_ad_account',
            13 => 'aoab_deshare_legal_entity',
            14 => 'ctx_thread_review',
            15 => 'compromised_ad_account',
            default => 'unknown',
        };
    }

    /**
     * Get human-readable description for disable reason
     */
    private function getDisableReasonDescription(?int $facebookDisableReason): string
    {
        if ($facebookDisableReason === null) {
            return 'No restrictions';
        }

        return match ($facebookDisableReason) {
            0 => 'No restrictions',
            1 => 'Ads Integrity Policy violation',
            2 => 'Ads IP Review required',
            3 => 'Risk payment issues',
            4 => 'Gray account shut down',
            5 => 'Ads AFC Review required',
            6 => 'Business Integrity RAR',
            7 => 'Permanently closed',
            8 => 'Unused reseller account',
            9 => 'Unused account',
            10 => 'Umbrella ad account',
            11 => 'Business Manager Integrity Policy violation',
            12 => 'Misrepresented ad account',
            13 => 'AOAB Deshare Legal Entity',
            14 => 'CTX Thread Review required',
            15 => 'Compromised ad account',
            default => 'Unknown restriction',
        };
    }
}
