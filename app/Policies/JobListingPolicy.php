<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function view(User $user, JobListing $jobListing): bool
    {
        return in_array($user->role, ['admin', 'employer']) &&
            ($user->role === 'admin' || $jobListing->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function update(User $user, JobListing $jobListing): bool
    {
        return in_array($user->role, ['admin', 'employer']) &&
            ($user->role === 'admin' || $jobListing->user_id === $user->id);
    }

    public function delete(User $user, JobListing $jobListing): bool
    {
        return in_array($user->role, ['admin', 'employer']) &&
            ($user->role === 'admin' || $jobListing->user_id === $user->id);
    }

    public function viewApplications(User $user, JobListing $jobListing): bool
    {
        return in_array($user->role, ['admin', 'employer']) &&
            ($user->role === 'admin' || $jobListing->user_id === $user->id);
    }
}
