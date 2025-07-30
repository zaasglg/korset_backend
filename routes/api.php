<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PassportVerificationController;
use App\Http\Controllers\Api\TariffController;
use App\Http\Controllers\Api\TariffRequestController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ShopReviewController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\CityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Referral validation (public route for registration)
Route::post('/referrals/validate', [ReferralController::class, 'validateCode']);

// Public routes
Route::get('/tariffs', [TariffController::class, 'index']);
Route::get('/tariffs/{tariff}', [TariffController::class, 'show']);

// Public product routes
Route::get('/public/products', [ProductController::class, 'publicIndex']);
Route::get('/public/products/{product}', [ProductController::class, 'publicShow']);

// Public shop routes
Route::get('/public/shops', [ShopController::class, 'index']);
Route::get('/public/shops/{shop}', [ShopController::class, 'show']);
Route::get('/public/shops/{shop}/reviews', [ShopReviewController::class, 'index']);

// Public region and city routes
Route::get('/regions', [RegionController::class, 'index']);
Route::get('/regions/{region}', [RegionController::class, 'show']);
Route::get('/regions/{region}/cities', [RegionController::class, 'cities']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{city}', [CityController::class, 'show']);
Route::get('/cities/by-region/{region}', [CityController::class, 'byRegion']);

// Product and Category Management Routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/parameters', [CategoryController::class, 'parameters']);
Route::get('/categories/{category}/hierarchy', [CategoryController::class, 'hierarchy']);
Route::get('/categories/{category}/descendants', [CategoryController::class, 'descendants']);
Route::get('/categories/level/{level}', [CategoryController::class, 'byLevel'])->where('level', '[1-3]');
Route::get('/stories', [StoryController::class, 'index']);

// Protected routess
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-avatar', [AuthController::class, 'updateAvatar']);
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);
    Route::put('/update-password', [AuthController::class, 'updatePassword']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Tariff request routes
    // Route::post('/tariff-requests', [TariffRequestController::class, 'store']);
    // Route::get('/tariff-requests/user', [TariffRequestController::class, 'userRequests']);
    // Route::get('/tariff-requests', [TariffRequestController::class, 'index']);
    // Route::put('/tariff-requests/{tariffRequest}/status', [TariffRequestController::class, 'updateStatus']);

    // Passport verification routes
    Route::post('/passport-verification', [PassportVerificationController::class, 'store']);
    Route::get('/passport-verification', [PassportVerificationController::class, 'show']);
    Route::get('/passport-verifications', [PassportVerificationController::class, 'index']);
    Route::put('/passport-verifications/{verification}/status', [PassportVerificationController::class, 'updateStatus']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    
    // Video management routes (must come before parameterized routes)
    Route::post('/products/upload-video', [ProductController::class, 'uploadVideo']);
    Route::post('/products/video-info', [ProductController::class, 'getVideoInfo']);
    Route::delete('/products/delete-video', [ProductController::class, 'deleteVideo']);
    
    // Parameterized product routes (must come after specific routes)
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products/{product}/increment-views', [ProductController::class, 'incrementViews']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/{product}/parameters', [ProductController::class, 'updateParameters']);

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/check/{product}', [FavoriteController::class, 'check']);

    // Shop Management Routes
    Route::get('/my-shop', [ShopController::class, 'myShop']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::put('/shops/{shop}', [ShopController::class, 'update']);
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy']);

    // Shop Review Routes
    Route::post('/shops/{shop}/reviews', [ShopReviewController::class, 'store']);
    Route::put('/shops/{shop}/reviews/{review}', [ShopReviewController::class, 'update']);
    Route::delete('/shops/{shop}/reviews/{review}', [ShopReviewController::class, 'destroy']);

    // Story Routes
    
    Route::apiResource('stories', StoryController::class);

    // Chat routes
    Route::get('chats', [ChatController::class, 'index']);
    Route::post('chats', [ChatController::class, 'createChat']);
    Route::post('chats/{chatId}/messages', [ChatController::class, 'sendMessage']);
    Route::get('chats/{chatId}/messages', [ChatController::class, 'getChatMessages']);

    Route::get('/stories/{story}', [StoryController::class, 'show']);
    Route::put('/stories/{story}', [StoryController::class, 'update']);
    Route::delete('/stories/{story}', [StoryController::class, 'destroy']);
    Route::post('/stories/{story}/view', [StoryController::class, 'view']);
    Route::get('/my-stories', [StoryController::class, 'myStories']);
    Route::get('/users/{user}/stories', [StoryController::class, 'userStories']);

    // Referral routes
    Route::get('/referrals', [ReferralController::class, 'index']);
    Route::post('/referrals/generate', [ReferralController::class, 'generateCode']);
    Route::post('/referrals/apply', [ReferralController::class, 'applyCode']);
    Route::get('/referrals/statistics', [ReferralController::class, 'statistics']);

    // Region and City management (admin routes)
    Route::post('/regions', [RegionController::class, 'store']);
    Route::put('/regions/{region}', [RegionController::class, 'update']);
    Route::delete('/regions/{region}', [RegionController::class, 'destroy']);
    
    Route::post('/cities', [CityController::class, 'store']);
    Route::put('/cities/{city}', [CityController::class, 'update']);
    Route::delete('/cities/{city}', [CityController::class, 'destroy']);
});