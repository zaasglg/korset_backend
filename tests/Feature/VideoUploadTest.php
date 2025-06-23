<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\City;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VideoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');w
        
        // Create a region for cities to reference
        if (!DB::table('regions')->exists()) {
            DB::table('regions')->insert([
                'name' => 'Test Region',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_upload_video_for_product()
    {
        $user = User::factory()->create();
        $video = UploadedFile::fake()->create('test_video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/upload-video', [
                'video' => $video
            ]);

        if ($response->getStatusCode() !== 200) {
            dd($response->json()); // Debug the error
        }

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'path',
                        'url',
                        'original_name',
                        'size',
                        'mime_type'
                    ]
                ]);

        // Verify file was stored
        $responseData = $response->json('data');
        $this->assertTrue(Storage::disk('public')->exists($responseData['path']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function video_upload_validates_file_type()
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('test.txt', 1024, 'text/plain');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/upload-video', [
                'video' => $invalidFile
            ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function video_upload_validates_file_size()
    {
        $user = User::factory()->create();
        // Create a file larger than 100MB (102400 KB)
        $largeVideo = UploadedFile::fake()->create('large_video.mp4', 120000, 'video/mp4');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/upload-video', [
                'video' => $largeVideo
            ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_get_video_info()
    {
        $user = User::factory()->create();
        $video = UploadedFile::fake()->create('test_video.mp4', 1024, 'video/mp4');

        // First upload a video
        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/upload-video', [
                'video' => $video
            ]);

        $uploadResponse->assertStatus(200);
        $videoPath = $uploadResponse->json('data.path');

        // Then get video info
        $infoResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/video-info', [
                'video_path' => $videoPath
            ]);

        $infoResponse->assertStatus(200)
                    ->assertJsonStructure([
                        'status',
                        'data' => [
                            'path',
                            'url',
                            'size',
                            'exists'
                        ]
                    ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_video()
    {
        $user = User::factory()->create();
        $video = UploadedFile::fake()->create('test_video.mp4', 1024, 'video/mp4');

        // First upload a video
        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/upload-video', [
                'video' => $video
            ]);

        $uploadResponse->assertStatus(200);
        $videoPath = $uploadResponse->json('data.path');

        // Verify file exists
        $this->assertTrue(Storage::disk('public')->exists($videoPath));

        // Delete the video
        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/products/delete-video', [
                'video_path' => $videoPath
            ]);

        $deleteResponse->assertStatus(200)
                      ->assertJson([
                          'status' => 'success',
                          'deleted' => true
                      ]);

        // Verify file was deleted
        $this->assertFalse(Storage::disk('public')->exists($videoPath));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function video_upload_requires_authentication()
    {
        $video = UploadedFile::fake()->create('test_video.mp4', 1024, 'video/mp4');

        $response = $this->postJson('/api/products/upload-video', [
            'video' => $video
        ]);

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_product_with_video()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();
        $video = UploadedFile::fake()->create('product_video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'city_id' => $city->id,
                'name' => 'Test Product with Video',
                'description' => 'A test product with video',
                'main_photo' => 'photo.jpg',
                'video' => $video,
                'price' => 100,
                'address' => 'Test Address',
                'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s')
            ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'video',
                        'video_url',
                        'video_size'
                    ]
                ]);

        // Verify video file was stored
        $product = $response->json('data');
        $this->assertNotNull($product['video']);
        $this->assertTrue(Storage::disk('public')->exists($product['video']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_product_video()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();
        
        // Create product without video first
        $product = Product::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product',
            'main_photo' => 'photo.jpg',
            'price' => 100,
            'address' => 'Test Address',
            'expires_at' => now()->addDays(30),
            'status' => 'active'
        ]);

        $newVideo = UploadedFile::fake()->create('updated_video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/products/{$product->id}", [
                'video' => $newVideo
            ]);

        $response->assertStatus(200);

        // Verify video was updated
        $updatedProduct = $response->json('data');
        $this->assertNotNull($updatedProduct['video']);
        $this->assertTrue(Storage::disk('public')->exists($updatedProduct['video']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_product_with_contact_info()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'city_id' => $city->id,
                'name' => 'Test Product with Contact',
                'description' => 'A test product with contact info',
                'main_photo' => 'photo.jpg',
                'price' => 100,
                'address' => 'Test Address',
                'whatsapp_number' => '+77001234567',
                'phone_number' => '+77771234567',
                'is_video_call_available' => true,
                'ready_for_video_demo' => true,
                'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s')
            ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'whatsapp_number',
                        'phone_number',
                        'is_video_call_available',
                        'ready_for_video_demo',
                        'views_count',
                        'whatsapp_link'
                    ]
                ]);

        $product = $response->json('data');
        $this->assertEquals('+77001234567', $product['whatsapp_number']);
        $this->assertEquals('+77771234567', $product['phone_number']);
        $this->assertTrue($product['is_video_call_available']);
        $this->assertTrue($product['ready_for_video_demo']);
        $this->assertEquals(0, $product['views_count']);
        $this->assertStringContainsString('wa.me/77001234567', $product['whatsapp_link']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_increment_product_views()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();
        
        $product = Product::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product',
            'main_photo' => 'photo.jpg',
            'price' => 100,
            'address' => 'Test Address',
            'views_count' => 5,
            'expires_at' => now()->addDays(30),
            'status' => 'active'
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/increment-views");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Views count incremented',
                    'data' => [
                        'views_count' => 6
                    ]
                ]);

        // Verify in database
        $this->assertEquals(6, $product->fresh()->views_count);
    }
}
