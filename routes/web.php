<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdDomainController;
use App\Http\Controllers\ContactController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function() {
        Route::post('domains/test', [AdDomainController::class, 'testConnection'])->name('domains.test');
        Route::resource('domains', AdDomainController::class);
    });

    // Contact routes
    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');
    Route::get('/contacts-export', [ContactController::class, 'export'])->name('contacts.export');
});
