<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createOAuthAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'OAuth Admin',
        'email' => 'oauth-admin-'.str()->random(8).'@example.com',
        'password' => 'password',
    ]);
}

it('redirects admin to facebook oauth dialog and stores state', function (): void {
    config()->set('services.facebook.app_id', '123456');
    config()->set('services.facebook.redirect_uri', 'https://metabiz.test/admin/facebook/business-managers/oauth/callback');
    config()->set('services.facebook.oauth_scopes', ['business_management', 'ads_management']);

    $admin = createOAuthAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.facebook.business-managers.oauth.redirect'));

    $response->assertRedirect();

    $redirectUrl = $response->headers->get('Location');

    expect($redirectUrl)->toContain('facebook.com/v21.0/dialog/oauth')
        ->and($redirectUrl)->toContain('client_id=123456')
        ->and($redirectUrl)->toContain(urlencode('https://metabiz.test/admin/facebook/business-managers/oauth/callback'))
        ->and(session('facebook_oauth_state'))->not()->toBeEmpty();
});

it('rejects callback with invalid state', function (): void {
    $admin = createOAuthAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->withSession(['facebook_oauth_state' => 'expected-state'])
        ->get(route('admin.facebook.business-managers.oauth.callback', [
            'state' => 'wrong-state',
            'code' => 'abc',
        ]));

    $response->assertRedirect(route('filament.admin.resources.admin.business-managers.index'));
    $response->assertSessionHas('facebook_oauth_error', 'Invalid OAuth state. Please try connecting again.');
});

it('returns oauth error when callback has no authorization code', function (): void {
    $admin = createOAuthAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->withSession(['facebook_oauth_state' => 'expected-state'])
        ->get(route('admin.facebook.business-managers.oauth.callback', [
            'state' => 'expected-state',
            'error_description' => 'User denied the request',
        ]));

    $response->assertRedirect(route('filament.admin.resources.admin.business-managers.index'));
    $response->assertSessionHas('facebook_oauth_error', 'User denied the request');
});

it('returns configuration error when facebook app id is missing', function (): void {
    config()->set('services.facebook.app_id', '');
    config()->set('services.facebook.redirect_uri', 'https://metabiz.test/admin/facebook/business-managers/oauth/callback');

    $admin = createOAuthAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.facebook.business-managers.oauth.redirect'));

    $response->assertRedirect(route('filament.admin.resources.admin.business-managers.index'));
    $response->assertSessionHas('facebook_oauth_error', 'Facebook App ID is not configured.');
});

it('returns configuration error when facebook redirect uri is missing', function (): void {
    config()->set('services.facebook.app_id', '123456');
    config()->set('services.facebook.redirect_uri', '');

    $admin = createOAuthAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.facebook.business-managers.oauth.redirect'));

    $response->assertRedirect(route('filament.admin.resources.admin.business-managers.index'));
    $response->assertSessionHas('facebook_oauth_error', 'FACEBOOK_REDIRECT_URI is not configured. Add the exact callback URL to .env and Facebook OAuth settings.');
});
