<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\City;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_account_with_correct_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/delete-account', [
            'password' => 'password123'
        ]);

        // Debug output if test fails
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Аккаунт успешно удален'
                ]);

        // Verify user is deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_account_with_wrong_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/delete-account', [
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Неверный пароль'
                ]);

        // Verify user is not deleted
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_account_without_password()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/delete-account', []);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);

        // Verify user is not deleted
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_account_removes_user_products()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $product = Product::factory()->create([
            'user_id' => $user->id
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/delete-account', [
            'password' => 'password123'
        ]);

        $response->assertStatus(200);

        // Verify user and products are deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_unauthenticated_user_cannot_delete_account()
    {
        $response = $this->deleteJson('/api/delete-account', [
            'password' => 'password123'
        ]);

        $response->assertStatus(401);
    }
}
