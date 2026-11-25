<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\JobCategoryController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\LocationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Public job browsing routes (accessible to guests)
Route::get('/jobs', [JobListingController::class, 'publicIndex'])->name('jobs.index');
Route::get('/jobs/{jobListing}', [JobListingController::class, 'publicShow'])->name('jobs.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/candidates', function () {
        return Inertia::render('candidates/index', [
            'candidates' => User::where('role', 'job_seeker')->get(['id', 'name', 'email']),
        ]);
    })->name('candidates.index');

    // Job seeker application routes
    Route::get('/applications/{jobListing}/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications/{jobListing}', [ApplicationController::class, 'store'])->name('applications.store');
});

Route::middleware(['auth'])->group(function () {
    // Backend routes with prefix
    Route::prefix('backend')->name('backend.')->group(function () {
        // Locations
        Route::resource('locations', LocationController::class)->only(['index', 'store', 'update']);
        Route::get('locations/active', [LocationController::class, 'getActiveLocations'])->name('locations.active');
        Route::patch('locations/{location}/toggle', [LocationController::class, 'toggleActive'])->name('locations.toggle');
        Route::patch('locations/{location}/restore', [LocationController::class, 'restore'])->name('locations.restore');
        Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
        Route::delete('locations/{location}/force-delete', [LocationController::class, 'forceDelete'])->name('locations.force-delete');

        // Categories
        Route::resource('categories', JobCategoryController::class)->only(['index', 'store', 'update']);
        Route::get('categories/active', [JobCategoryController::class, 'getActiveCategories'])->name('categories.active');
        Route::patch('categories/{category}/toggle', [JobCategoryController::class, 'toggleActive'])->name('categories.toggle');
        Route::patch('categories/{category}/restore', [JobCategoryController::class, 'restore'])->name('categories.restore');
        Route::delete('categories/{category}', [JobCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('categories/{category}/force-delete', [JobCategoryController::class, 'forceDelete'])->name('categories.force-delete');

        // Job Listings
        Route::resource('listing', JobListingController::class)->names([
            'index' => 'listing.index',
            'create' => 'listing.create',
            'store' => 'listing.store',
            'show' => 'listing.show',
            'edit' => 'listing.edit',
            'update' => 'listing.update',
            'destroy' => 'listing.destroy',
        ])->parameters([
            'listing' => 'jobListing',
        ]);
        Route::patch('listing/{jobListing}/toggle-active', [JobListingController::class, 'toggleActive'])->name('listing.toggle-active');
        Route::get('listing/{jobListing}/applications', [JobListingController::class, 'applications'])->name('listing.applications');

        // Applications (Employer/Admin)
        Route::get('application', [ApplicationController::class, 'index'])->name('application.index');
        Route::post('application', [ApplicationController::class, 'index'])->name('application.index');
        Route::patch('application/bulk-status', [ApplicationController::class, 'bulkUpdateStatus'])->name('application.bulk-status');
        Route::get('application/merge-resumes', [ApplicationController::class, 'mergeResumes'])->name('application.merge-resumes');
        Route::get('application/{application}', [ApplicationController::class, 'show'])->name('application.show');
        Route::patch('application/{application}/status', [ApplicationController::class, 'updateStatus'])->name('application.update-status');
        Route::get('application/{application}/resume', [ApplicationController::class, 'downloadResume'])->name('application.download-resume');
        Route::delete('application/{application}', [ApplicationController::class, 'destroy'])->name('application.destroy');
        Route::post('application/{application}/recalculate-score', [ApplicationController::class, 'recalculateScore'])->name('application.recalculate-score');

        // Analytics
        Route::get('analytics/dashboard', [ApplicationController::class, 'analyticsDashboard'])->name('analytics.dashboard');
        Route::get('analytics/job-analysis/{jobListing}', [ApplicationController::class, 'jobATSAnalysis'])->name('analytics.job-analysis');
        Route::get('analytics/ats-stats', [ApplicationController::class, 'getATSStats'])->name('analytics.ats-stats');
    });

    Route::get('/settings', fn () => Inertia::render('settings/index'))->name('settings.index');
    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('profile', fn () => Inertia::render('settings/profile'))->name('profile');
        Route::patch('profile', fn () => back()->with('success', 'Profile updated.'))->name('profile.update');
        Route::delete('profile', fn () => back())->name('profile.destroy');
        Route::get('password', fn () => Inertia::render('settings/password'))->name('password');
        Route::put('password', fn () => back()->with('success', 'Password updated.'))->name('password.update');
    });
});

require __DIR__.'/auth.php';
