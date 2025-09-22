<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PassportVerificationController;
use App\Http\Controllers\Api\TariffController;

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
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicationPriceController;
use App\Http\Controllers\Api\ProductBookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// New registration flow with phone verification
Route::post('/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/verify-and-register', [AuthController::class, 'verifyAndRegister']);
Route::post('/verification-status', [AuthController::class, 'getVerificationStatus']);

// Phone number check
Route::post('/check-phone-number', [AuthController::class, 'checkPhoneNumber']);

// Legacy registration (keep for backward compatibility)
Route::post('/register', [AuthController::class, 'sendVerificationCode']);
Route::post('/login', [AuthController::class, 'login']);

// Referral validation (public route for registration)
Route::post('/referrals/validate', [ReferralController::class, 'validateCode']);

// Public routes
Route::get('/tariffs', [TariffController::class, 'index']);
Route::get('/tariffs/{tariff}', [TariffController::class, 'show']);

// Public product routes
Route::get('/public/products', [ProductController::class, 'publicIndex']);
Route::get('/public/products/{product}', [ProductController::class, 'publicShow']);
Route::post('/public/products/{product}/share', [ProductController::class, 'shareProduct']);
Route::get('/public/products/{product}/share-stats', [ProductController::class, 'getShareStats']);

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

// Public publication price routes
Route::get('/publication-prices', [PublicationPriceController::class, 'index']);
Route::get('/publication-prices/stories', [PublicationPriceController::class, 'stories']);
Route::get('/publication-prices/announcements', [PublicationPriceController::class, 'announcements']);
Route::get('/publication-prices/booking-commissions', [PublicationPriceController::class, 'bookingCommissions']);
Route::get('/publication-prices/stats', [PublicationPriceController::class, 'stats']);
Route::get('/publication-prices/{publicationPrice}', [PublicationPriceController::class, 'show']);

// Public booking status routes
Route::get('/products/{product}/booking-status', [ProductBookingController::class, 'checkStatus']);

// Payment callback (public route)
Route::post('/payments/freedompay/callback', [PaymentController::class, 'freedomPayCallback']);

// Product and Category Management Routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/parameters', [CategoryController::class, 'parameters']);
Route::get('/categories/{category}/hierarchy', [CategoryController::class, 'hierarchy']);
Route::get('/categories/{category}/descendants', [CategoryController::class, 'descendants']);
Route::get('/categories/level/{level}', [CategoryController::class, 'byLevel'])->where('level', '[1-3]');
Route::get('/stories-guest', [StoryController::class, 'index']);

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

    // Tariff purchase routes
    Route::post('/tariffs/{tariff}/purchase', [TariffController::class, 'purchase']);
    Route::get('/my-tariffs', [TariffController::class, 'myTariffs']);

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
    Route::get('/products/video-stats', [ProductController::class, 'getVideoStats']);

    // Parameterized product routes (must come after specific routes)
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products/{product}/increment-views', [ProductController::class, 'incrementViews'])->name('api.products.increment-views');
    Route::post('/products/{product}/share', [ProductController::class, 'shareProduct']);
    Route::get('/products/{product}/share-stats', [ProductController::class, 'getShareStats']);
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
    Route::delete('chats', [ChatController::class, 'deleteAllChats']);
    Route::post('chats/{chatId}/messages', [ChatController::class, 'sendMessage']);
    Route::get('chats/{chatId}/messages', [ChatController::class, 'getChatMessages']);
    Route::get('chats/{chatId}/join', [ChatController::class, 'joinChat']);
    Route::post('chats/{chatId}/mark-read', [ChatController::class, 'markAsRead']);
    Route::delete('chats/{chatId}', [ChatController::class, 'deleteChat']);
    Route::delete('chats/{chatId}/clear', [ChatController::class, 'clearChat']);
    Route::delete('chats/{chatId}/messages/{messageId}', [ChatController::class, 'deleteMessage']);

    // Realtime chat routes
    Route::get('chats/{chatId}/stream', [ChatController::class, 'streamMessages']);
    Route::get('chats/{chatId}/poll', [ChatController::class, 'pollMessages']);

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

    // SMS routes (admin only)
    Route::get('/sms/statistics', [SmsController::class, 'statistics']);
    Route::get('/sms/balance', [SmsController::class, 'balance']);
    Route::post('/sms/test', [SmsController::class, 'sendTest']);

    // Wallet routes
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'show']);
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::get('/transactions', action: [WalletController::class, 'transactions']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('/topup', [PaymentController::class, 'createTopUpSession']);
        Route::get('/sessions', [PaymentController::class, 'getUserSessions']);
        Route::get('/sessions/{sessionId}', [PaymentController::class, 'getSessionStatus']);
    });

    // Product booking routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [ProductBookingController::class, 'index']);
        Route::post('/', [ProductBookingController::class, 'store']);
        Route::get('/{productBooking}', [ProductBookingController::class, 'show']);
        Route::delete('/{productBooking}', [ProductBookingController::class, 'destroy']);
        Route::post('/{productBooking}/confirm', [ProductBookingController::class, 'confirm']);
        Route::post('/{productBooking}/complete', [ProductBookingController::class, 'complete']);
    });
});