<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GMB\CitationScanController;
use App\Http\Controllers\GMB\DashboardController;
use App\Http\Controllers\GMB\GmbAuthController;
use App\Http\Controllers\GMB\GmbInsightController;
use App\Http\Controllers\GMB\GmbReviewController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::get('/gmb/gauth', [GmbAuthController::class, 'handleCallback'])->name('gmb.gauth');
Route::get('/gmb-debug', function () {
    $location   = \App\Models\GmbLocation::find(1);
    $credential = \App\Models\GmbApiCredential::where('client_id', $location->client_id)->first();

    if (!$credential) return response()->json(['error' => 'No credential']);

    $token      = $credential->getRawOriginal('access_token');
    $locationId = $location->gbp_location_id;

    $response = \Illuminate\Support\Facades\Http::withToken($token)
        ->get("https://businessprofileperformance.googleapis.com/v1/{$locationId}:fetchMultiDailyMetricsTimeSeries", [
            'dailyMetrics'               => [
                'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
                'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
                'CALL_CLICKS',
                'BUSINESS_DIRECTION_REQUESTS',
            ],
            'dailyRange.startDate.year'  => now()->year,
            'dailyRange.startDate.month' => now()->month,
            'dailyRange.startDate.day'   => 1,
            'dailyRange.endDate.year'    => now()->year,
            'dailyRange.endDate.month'   => now()->month,
            'dailyRange.endDate.day'     => now()->daysInMonth,
        ]);

    return response()->json([
        'status'     => $response->status(),
        'locationId' => $locationId,
        'token_exp'  => $credential->expires_at,
        'response'   => $response->json(),
    ]);
});

Route::middleware(['auth', 'session.valid'])->group(function () {

    Route::get('/dashboard', [AuthController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/basic', function () {
            return view('forms');
        })->name('basic');
    });

    Route::prefix('gmb')->name('gmb.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('clients/{client}', [DashboardController::class, 'show'])->name('clients.show');
        Route::post('clients/{client}/locations/store', [DashboardController::class, 'storeLocation'])->name('location.store');
        Route::post('clients/{client}/locations/update/{location}', [DashboardController::class, 'updateLocation'])->name('location.update');
        Route::post('clients/{client}/locations/delete/{location}', [DashboardController::class, 'destroyLocation'])->name('location.delete');
        Route::post('clients/{client}/credential', [DashboardController::class, 'saveCredential'])->name('credential.save');
        Route::get('connect/{client}', [GmbAuthController::class, 'redirectToGoogle'])->name('connect');
        Route::post('disconnect/{client}', [GmbAuthController::class, 'disconnect'])->name('disconnect');
        Route::get('insights', [GmbInsightController::class, 'index'])->name('insights.index');
        Route::get('insights/{client}', [GmbInsightController::class, 'show'])->name('insights.show');
        Route::post('insights/{client}/pull', [GmbInsightController::class, 'pullNow'])->name('insights.pull');

        Route::get('reviews/{client}', [GmbReviewController::class, 'index'])->name('reviews.index');
        Route::post('reviews/{client}/pull', [GmbReviewController::class, 'pullAndGenerate'])->name('reviews.pull');
        Route::post('reviews/{review}/select-draft', [GmbReviewController::class, 'selectDraft'])->name('reviews.select-draft');
        Route::post('reviews/{review}/mark-replied', [GmbReviewController::class, 'markReplied'])->name('reviews.mark-replied');

        Route::get('reviews/{client}/create', [GmbReviewController::class, 'create'])->name('reviews.create');
        Route::post('reviews/{client}/store', [GmbReviewController::class, 'store'])->name('reviews.store');

        Route::get('citation/{client}',                  [CitationScanController::class, 'index'])->name('citation.index');
        Route::post('citation/{client}/run',             [CitationScanController::class, 'run'])->name('citation.run');
        Route::post('citation/audit/{audit}/corrected',  [CitationScanController::class, 'markCorrected'])->name('citation.mark-corrected');
        Route::post('citation/audit/{audit}/manual',     [CitationScanController::class, 'manualUpdate'])->name('citation.manual-update');
    });

    Route::resource('clients', ClientController::class);
    Route::get('/clients/data', [ClientController::class, 'data'])->name('clients.data');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

});