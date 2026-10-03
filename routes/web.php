<?php

use App\Http\Controllers\ActivityLogFeedController;
use App\Http\Controllers\GeneratedImageDownloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', 'pages::auth.login')->name('login');

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');

    Route::get('/dashboard/activity-logs/feed', [
        ActivityLogFeedController::class,
        'index',
    ])->name('dashboard.activity-logs.feed');

    Route::delete('/dashboard/activity-logs', [
        ActivityLogFeedController::class,
        'destroy',
    ])->name('dashboard.activity-logs.destroy');

    // Generated image download / compression endpoint.
    Route::get('/generated-images/{generatedImage}/download', GeneratedImageDownloadController::class)
        ->name('generated-images.download');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
