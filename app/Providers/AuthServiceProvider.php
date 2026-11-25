<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Policies\ApplicationPolicy;
use App\Policies\JobCategoryPolicy;
use App\Policies\JobListingPolicy;
use App\Policies\LocationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        JobListing::class => JobListingPolicy::class,
        Application::class => ApplicationPolicy::class,
        JobCategory::class => JobCategoryPolicy::class,
        Location::class => LocationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
