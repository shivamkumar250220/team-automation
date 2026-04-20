<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SEOAutomationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::middleware(['auth', 'session.valid'])->group(function () {

    Route::get('/dashboard', [AuthController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('clients', ClientController::class);
    Route::get('/clients/data', [ClientController::class, 'data'])->name('clients.data');

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
    Route::get('/ranking-report', [SEOAutomationController::class, 'rankingReport'])->name('ranking.report');
    Route::post('/ranking-report', [SEOAutomationController::class, 'rankingReportform'])->name('ranking.report.form');
});
