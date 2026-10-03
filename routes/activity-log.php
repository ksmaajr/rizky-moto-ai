DO NOT REGISTER THIS FILE.

Activity Log routes belong in routes/web.php only:

use App\Http\Controllers\ActivityLogFeedController;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/activity-logs/feed', [ActivityLogFeedController::class, 'index'])
        ->name('dashboard.activity-logs.feed');

    Route::delete('/dashboard/activity-logs', [ActivityLogFeedController::class, 'destroy'])
        ->name('dashboard.activity-logs.destroy');
});
