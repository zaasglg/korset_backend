<?php

use App\Models\Referral;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('user can get referral information', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/referrals');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'my_referral_code',
                'total_referrals',
                'total_earnings',
                'pending_earnings',
                'paid_earnings',
                'referrals'
            ]
        ]);
});

test('user can generate referral code', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/referrals/generate');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'referral_code',
                'message'
            ]
        ]);

    expect($user->referralsMade()->count())->toBe(1);
});

test('user can apply valid referral code', function () {
    $referrer = User::factory()->create();
    $referred = User::factory()->create();
    
    $referral = Referral::create([
        'referrer_id' => $referrer->id,
        'referral_code' => 'TEST1234',
    ]);

    Sanctum::actingAs($referred);

    $response = $this->postJson('/api/referrals/apply', [
        'referral_code' => 'TEST1234'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Referral code applied successfully! Your referrer will receive a reward.'
        ]);

    $this->assertDatabaseHas('referrals', [
        'referral_code' => 'TEST1234',
        'referred_id' => $referred->id,
        'reward_amount' => config('referral.reward_amount', 10.00)
    ]);
});

test('user cannot apply invalid referral code', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/referrals/apply', [
        'referral_code' => 'INVALID123'
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid or already used referral code'
        ]);
});

test('user cannot apply own referral code', function () {
    $user = User::factory()->create();
    
    $referral = Referral::create([
        'referrer_id' => $user->id,
        'referral_code' => 'MYCODE123',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/referrals/apply', [
        'referral_code' => 'MYCODE123'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'You cannot use your own referral code'
        ]);
});

test('user can validate referral code', function () {
    $referrer = User::factory()->create();
    
    $referral = Referral::create([
        'referrer_id' => $referrer->id,
        'referral_code' => 'VALID123',
    ]);

    $response = $this->postJson('/api/referrals/validate', [
        'referral_code' => 'VALID123'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Valid referral code'
        ])
        ->assertJsonStructure([
            'data' => [
                'referrer_name',
                'reward_amount'
            ]
        ]);
});

test('registration with referral code works', function () {
    $referrer = User::factory()->create();
    
    $referral = Referral::create([
        'referrer_id' => $referrer->id,
        'referral_code' => 'REF123',
    ]);

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone_number' => '+1234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'referral_code' => 'REF123'
    ]);

    $response->assertStatus(201);

    $user = User::where('email', 'test@example.com')->first();
    
    $this->assertDatabaseHas('referrals', [
        'referral_code' => 'REF123',
        'referred_id' => $user->id,
        'reward_amount' => config('referral.reward_amount', 10.00)
    ]);
});
