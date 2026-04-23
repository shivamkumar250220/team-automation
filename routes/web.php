<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DomainManagementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\SEOAutomationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::middleware(['auth', 'session.valid'])->group(function () {

    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    //Clients
    Route::resource('clients', ClientController::class);
    Route::get('/clients/data', [ClientController::class, 'data'])->name('clients.data');
    
    // 
    Route::get('/industry/data', [IndustryController::class, 'data'])->name('industry.data');
    Route::resource('industry', IndustryController::class);
    
    // Route::get('/add-client',  [DomainManagementController::class, 'create']);
    // Route::post('/add-client ygvghfc',  [DomainManagementController::class, 'store'])->name('store-client');
    Route::get('/view-client/{id}',  [DomainManagementController::class, 'show'])->name('view-client');
    // Route::get('/edit-client/{id}',  [DomainManagementController::class, 'edit'])->name('edit-client');
    // Route::post('/edit-client',  [DomainManagementController::class, 'update'])->name('update-client');

    //Clients Properties
    Route::get('/clients-properties/{id}',  [DomainManagementController::class, 'showproperties'])->name('clients-properties');
    Route::get('/add-client-properties/{id}',  [DomainManagementController::class, 'createproperties'])->name('add-client-properties');
    Route::post('/add-client-properties',  [DomainManagementController::class, 'storeproperties'])->name('store-client-properties');
    Route::get('/edit-client-properties/{id}',  [DomainManagementController::class, 'editproperties'])->name('edit-client-properties');
    Route::post('/edit-client-properties',  [DomainManagementController::class, 'updateproperties'])->name('update-client-properties');

   

    // Forms
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/basic', function () {
            return view('forms');
        })->name('basic');
    });

    // Profile
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    // Seo Automation
    // Route::prefix('arihant')->name('arihant.')->group(function () {
    //     });
    Route::get('/ranking-competitor-report/{created_by_user_id}/{cpid}', [SEOAutomationController::class, 'rankingCompetitorReport'])->name('ranking.competitor.report');
    Route::post('/ranking-competitor-report/{created_by_user_id}/{cpid}', [SEOAutomationController::class, 'rankingCompetitorReportForm'])->name('ranking.competitor.report.form');
    Route::post('/ranking-competitor-report/save',[SEOAutomationController::class, 'saveRankingCompetitorReport'])->name('ranking.competitor.report.save');
    
    Route::get('/core-web-vitals/{created_by_user_id}/{cpid}', [SEOAutomationController::class, 'coreWebVitals'])->name('core.web.vitals');
    Route::post('/core-web-vitals/{created_by_user_id}/{cpid}', [SEOAutomationController::class, 'coreWebVitalsform'])->name('core.web.vitals.form');
});
