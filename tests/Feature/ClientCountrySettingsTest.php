<?php

use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('client country options are loaded from settings and new countries can be added via settings', function () {
    $user = User::factory()->create();

    // 1) Add custom countries in settings
    Setting::create([
        'category'     => 'country',
        'key_value'    => 'KW',
        'display_name' => 'الكويت',
    ]);
    Setting::create([
        'category'     => 'country',
        'key_value'    => 'QA',
        'display_name' => 'قطر',
    ]);

    Cache::forget('system_settings');

    // 2) Access clients create page and check that new countries appear in the dropdown
    $response = $this->actingAs($user)->get(route('clients.create'));
    $response->assertStatus(200);
    $response->assertSee('الكويت');
    $response->assertSee('قطر');

    // 3) Create a client with the new country from settings
    $storeResponse = $this->actingAs($user)->post(route('clients.store'), [
        'company_name' => 'Gulf Trading Co',
        'phone'        => '0501234567',
        'country'      => 'KW',
        'client_type'  => 'wholesale',
    ]);

    $storeResponse->assertRedirect(route('clients.index'));

    $client = Client::where('company_name', 'Gulf Trading Co')->first();
    expect($client)->not->toBeNull();
    expect($client->country)->toEqual('KW');

    // 4) Access settings index and verify country section is present
    $settingsResponse = $this->actingAs($user)->get(route('settings.index'));
    $settingsResponse->assertStatus(200);
    $settingsResponse->assertSee('الكويت');
});
