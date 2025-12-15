<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GA4Controller;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/generate', [DashboardController::class, 'generateSnapshot'])->name('dashboard.generate');

    Route::prefix('ga4')->name('ga4.')->group(function () {
        Route::get('/', [GA4Controller::class, 'index'])->name('index');
        Route::get('/connect', [GA4Controller::class, 'connect'])->name('connect');
        Route::get('/callback', [GA4Controller::class, 'callback'])->name('callback');
        Route::get('/select-property', [GA4Controller::class, 'selectProperty'])->name('select-property');
        Route::post('/select-property', [GA4Controller::class, 'storeProperty'])->name('store-property');
        Route::delete('/disconnect', [GA4Controller::class, 'disconnect'])->name('disconnect');
    });
});

require __DIR__.'/settings.php';
