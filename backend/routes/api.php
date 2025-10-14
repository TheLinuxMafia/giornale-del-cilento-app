<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\UserDraftController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\PromptConfigController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\WordPressController;
use App\Http\Controllers\Api\BroadcastController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\EditPostController;
use App\Http\Controllers\WordPress\DeletePostController;
use App\Http\Controllers\WordPress\AnalyticsController;
use App\Http\Controllers\Api\AnalyticsController as CoreAnalyticsController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\WordPressCredentialController;
use App\Http\Controllers\Api\ManualUserSyncController;
use App\Http\Controllers\Api\WordPressOAuthController;
use App\Http\Controllers\Api\WordPressAuthController;
use App\Http\Controllers\Api\WordPressUserSyncController;
use App\Http\Controllers\Api\ProxyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/wordpress-login', [WordPressAuthController::class, 'login']);
Route::get('/auth/wordpress-test', [WordPressAuthController::class, 'testConnection']);

// Test endpoint removed for security - was exposing database info without auth

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/wp-profile', [AuthController::class, 'wpProfile']);
    
    // Feed Management (Editor/Admin only)
    Route::get('/feeds/templates', [FeedController::class, 'templates']);
    Route::get('/feeds/template', [FeedController::class, 'getTemplate']);
    Route::post('/feeds/{id}/refresh', [FeedController::class, 'refresh']);
    Route::apiResource('feeds', FeedController::class);
    
    // Articles/RSS Items
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);
    Route::post('/articles/{id}/claim', [ArticleController::class, 'claim']);
    Route::delete('/articles/{id}/claim', [ArticleController::class, 'release']);
    
    // User Drafts (New System)
    // Important: place specific routes BEFORE the resource to avoid being captured by {id}
    Route::get('/user-drafts/published', [UserDraftController::class, 'published']);
    Route::apiResource('user-drafts', UserDraftController::class);
    Route::get('/user-drafts/categories', [UserDraftController::class, 'getCategories']);
    Route::get('/user-drafts/stats', [UserDraftController::class, 'getStats']);
    
    // Legacy Drafts (Keep for compatibility)
    Route::apiResource('drafts', DraftController::class);
    Route::post('/drafts/{id}/lock', [DraftController::class, 'acquireLock']);
    Route::delete('/drafts/{id}/lock', [DraftController::class, 'releaseLock']);
    
    // AI Integration - User endpoints
    Route::get('/ai/available-models', [AiController::class, 'getAvailableModels']);
    Route::post('/ai/generate', [AiController::class, 'generateContent']);
    
    // User AI Configurations
    Route::get('/ai/user-configs', [AiController::class, 'getUserConfigs']);
    Route::post('/ai/user-configs', [AiController::class, 'saveUserConfig']);
    Route::delete('/ai/user-configs/{id}', [AiController::class, 'deleteUserConfig']);
    
// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/tree', [CategoryController::class, 'tree']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/categories/search', [CategoryController::class, 'search']);
Route::get('/categories/sync/status', [CategoryController::class, 'syncStatus']);
    
    // AI Admin Management (Admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/ai/admin/providers', [AiController::class, 'getProvidersForAdmin']);
        Route::put('/ai/admin/providers/{id}', [AiController::class, 'updateProvider']);
        Route::post('/ai/admin/providers/{id}/test', [AiController::class, 'testProviderConfig']);
        Route::get('/ai/admin/user-configs', [AiController::class, 'getAllUserConfigs']);
        Route::get('/ai/providers', [AiController::class, 'getProviders']);
        Route::get('/ai/models', [AiController::class, 'getModels']);
        Route::get('/ai/test-gemini', [AiController::class, 'testGemini']);
        
        // Prompt Configuration Routes (Admin only)
        Route::get('/ai/prompt-templates', [PromptConfigController::class, 'getPromptTemplates']);
        Route::get('/ai/contextual-info', [PromptConfigController::class, 'getContextualInfo']);
        
        // Category Admin Management (Admin only)
        Route::post('/categories/sync', [CategoryController::class, 'sync']);
        Route::post('/categories/test-connection', [CategoryController::class, 'testConnection']);
    });
    
    // WordPress Integration
    Route::get('/wordpress/categories', [WordPressController::class, 'getCategories']);
    Route::get('/wordpress/tags', [WordPressController::class, 'getTags']);
    // Legacy publish endpoint redirected to EditPostController (handles both new and edit)
    Route::post('/wordpress/publish', [EditPostController::class, 'editPost']);
    Route::post('/wordpress/sync-user', [WordPressController::class, 'syncUser']);
    
    // Broadcasting
    Route::get('/broadcast/cached/{cacheKey}', [BroadcastController::class, 'getCachedData']);
    Route::get('/broadcast/stats', [BroadcastController::class, 'getStats']);
    
    // Image Processing
    Route::post('/images/convert-to-webp', [ImageController::class, 'convertToWebP']);
    Route::post('/images/info', [ImageController::class, 'getImageInfo']);
    Route::post('/images/analyze-exif', [ImageController::class, 'analyzeExif']);
    Route::delete('/images/delete', [ImageController::class, 'deleteImage']);
    Route::get('/images/debug-storage', [ImageController::class, 'debugStorage']);
    
    // Edit Post
    Route::post('/edit-post', [EditPostController::class, 'editPost']);
    
    // Delete Post
    Route::delete('/wordpress/posts/delete', [DeletePostController::class, 'deletePost']);
    
    // WordPress Analytics
    Route::prefix('wordpress/analytics')->group(function () {
        Route::get('/global', [AnalyticsController::class, 'getGlobalAnalytics']);
        Route::post('/global', [AnalyticsController::class, 'getGlobalAnalytics']);
        Route::get('/journalist', [AnalyticsController::class, 'getJournalistAnalytics']);
        Route::get('/users', [AnalyticsController::class, 'getWordPressUsers']);
        Route::get('/summary', [AnalyticsController::class, 'getSummaryStats']);
        Route::post('/summary', [AnalyticsController::class, 'getSummaryStats']);
        Route::post('/journalist-details', [AnalyticsController::class, 'getJournalistDetails']);
        Route::post('/journalist-subcategories', [AnalyticsController::class, 'getJournalistSubcategories']);
    });

    // Advertising routes
    Route::prefix('advertising')->group(function () {
        Route::get('/positions', [App\Http\Controllers\Api\AdvertisingController::class, 'getPositions']);
        Route::post('/positions', [App\Http\Controllers\Api\AdvertisingController::class, 'createPosition']);
        Route::put('/positions/{id}', [App\Http\Controllers\Api\AdvertisingController::class, 'updatePosition']);
        Route::delete('/positions/{id}', [App\Http\Controllers\Api\AdvertisingController::class, 'deletePosition']);
        
        Route::get('/contracts', [App\Http\Controllers\Api\AdvertisingController::class, 'getContracts']);
        Route::post('/contracts', [App\Http\Controllers\Api\AdvertisingController::class, 'createContract']);
    });

    // Company Settings
    Route::get('/company-settings/profile', [App\Http\Controllers\Api\CompanySettingsController::class, 'getProfile']);
    Route::put('/company-settings/profile', [App\Http\Controllers\Api\CompanySettingsController::class, 'updateProfile']);

    // Customers (fatturazione)
    Route::get('/customers', [App\Http\Controllers\Api\CustomerController::class, 'index']);
    Route::post('/customers', [App\Http\Controllers\Api\CustomerController::class, 'store']);

    // System settings
    Route::get('/settings/system', [App\Http\Controllers\Api\SystemSettingsController::class, 'get']);
    Route::put('/settings/system', [App\Http\Controllers\Api\SystemSettingsController::class, 'update']);

    // Sync actions options
    Route::get('/settings/sync-actions', [App\Http\Controllers\Api\SyncActionsController::class, 'get']);
    Route::put('/settings/sync-actions', [App\Http\Controllers\Api\SyncActionsController::class, 'update']);

    // GA4 Analytics (core)
    Route::get('/analytics/ga4/summary', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4Summary']);
    Route::get('/analytics/ga4/trend-7d', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4Trend7d']);
    Route::get('/analytics/ga4/trend', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4Trend']);
    Route::get('/analytics/ga4/landing', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4Landing']);
    Route::get('/analytics/ga4/devices', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4Devices']);
    Route::get('/analytics/ga4/geo', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4Geo']);
    Route::get('/analytics/ga4/monthly-active-users', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4MonthlyActiveUsers']);
    Route::get('/analytics/ga4/report-pdf', [\App\Http\Controllers\Api\AnalyticsController::class, 'ga4ReportPdf']);

    // Debug Logs (Super User only)
    Route::middleware(\App\Http\Middleware\SuperUserMiddleware::class)->group(function () {
        Route::get('/debug-logs', [\App\Http\Controllers\Api\DebugLogController::class, 'index']);
        Route::get('/debug-logs/stats', [\App\Http\Controllers\Api\DebugLogController::class, 'stats']);
        Route::get('/debug-logs/filters', [\App\Http\Controllers\Api\DebugLogController::class, 'filters']);
        Route::get('/debug-logs/{debugLog}', [\App\Http\Controllers\Api\DebugLogController::class, 'show']);
        Route::delete('/debug-logs/{debugLog}', [\App\Http\Controllers\Api\DebugLogController::class, 'destroy']);
        Route::post('/debug-logs/cleanup', [\App\Http\Controllers\Api\DebugLogController::class, 'cleanup']);
    });

    // (proxy spostato nelle rotte pubbliche per evitare redirect auth)
});

// Public reverse proxy endpoint (whitelisted hosts only)
Route::get('/proxy', [ProxyController::class, 'fetch']);
    
    // WordPress Integration
Route::prefix('wordpress')->group(function () {
    Route::post('/tags/create', [\App\Http\Controllers\WordPress\TagController::class, 'createOrGetTag']);
    Route::get('/tags', [\App\Http\Controllers\WordPress\TagController::class, 'getAllTags']);
    
    // Post management
    Route::post('/posts/publish', [EditPostController::class, 'editPost']);
    Route::get('/test-connection', [\App\Http\Controllers\WordPress\PostController::class, 'testConnection']);
    Route::get('/user-info', [\App\Http\Controllers\WordPress\PostController::class, 'getWordPressUserInfo']);
    
    // User WordPress configuration
    Route::post('/configure', [\App\Http\Controllers\WordPress\UserWordPressController::class, 'configureToken']);
    Route::get('/configuration', [\App\Http\Controllers\WordPress\UserWordPressController::class, 'getConfiguration']);
    Route::delete('/disconnect', [\App\Http\Controllers\WordPress\UserWordPressController::class, 'disconnect']);
    
    // WordPress Analytics routes
    Route::get('/analytics/global', [AnalyticsController::class, 'getGlobalAnalytics']);
    Route::get('/analytics/journalist', [AnalyticsController::class, 'getJournalistAnalytics']);
    Route::get('/analytics/users', [AnalyticsController::class, 'getWordPressUsers']);
    Route::get('/analytics/summary', [AnalyticsController::class, 'getSummaryStats']);
    
    // WordPress Sync routes
    Route::post('/sync/users', [SyncController::class, 'syncUsers']);
    Route::post('/sync/articles', [SyncController::class, 'syncArticles']);
    Route::post('/sync/today-articles', [SyncController::class, 'syncTodayArticles']);
    Route::get('/sync/stats', [SyncController::class, 'getSyncStats']);
    Route::get('/sync/today-articles', [SyncController::class, 'listTodayArticles']);
    Route::get('/sync/articles-trend-7d', [SyncController::class, 'articlesTrend7d']);
    Route::get('/sync/summary-7d', [SyncController::class, 'summary7d']);

    // GA4 Analytics (core)
    Route::get('/analytics/ga4/summary', [CoreAnalyticsController::class, 'ga4Summary']);
    
    // WordPress Articles
    Route::get('/wordpress-articles', [\App\Http\Controllers\Api\WordPressArticlesController::class, 'index']);
    Route::post('/wordpress-articles/{id}/convert-to-draft', [\App\Http\Controllers\Api\WordPressArticlesController::class, 'convertToDraft']);
    Route::get('/analytics/ga4/trend-7d', [CoreAnalyticsController::class, 'ga4Trend7d']);
    
    // WordPress Credentials routes
    Route::post('/credentials', [WordPressCredentialController::class, 'store']);
    Route::get('/credentials', [WordPressCredentialController::class, 'index']);
    
    // Manual User Sync routes
    Route::post('/manual-sync/users', [ManualUserSyncController::class, 'syncUsers']);
    Route::get('/manual-sync/template', [ManualUserSyncController::class, 'getTemplate']);
    
    // WordPress OAuth routes
    Route::get('/oauth/authorize', [WordPressOAuthController::class, 'getAuthorizationUrl']);
    Route::get('/oauth/callback', [WordPressOAuthController::class, 'handleCallback']);
    Route::get('/oauth/test', [WordPressOAuthController::class, 'testConnection']);
    
    // WordPress User Sync routes
    Route::post('/user-sync/sync', [WordPressUserSyncController::class, 'syncUsers']);
    Route::get('/user-sync/stats', [WordPressUserSyncController::class, 'getSyncStats']);
    
    // WordPress Posts Sync routes
    Route::post('/posts-sync/all', [WordPressUserSyncController::class, 'syncAllPosts']);
    Route::post('/posts-sync/today', [WordPressUserSyncController::class, 'syncTodayPosts']);
});

// Page Configuration routes
Route::apiResource('page-configurations', \App\Http\Controllers\Api\PageConfigurationController::class);
Route::get('/page-configurations/{pageConfiguration}/banners', [\App\Http\Controllers\Api\PageConfigurationController::class, 'getBannerConfigurations']);

// Banner Position routes
Route::get('/banner-positions', [\App\Http\Controllers\Api\BannerPositionController::class, 'index']);
