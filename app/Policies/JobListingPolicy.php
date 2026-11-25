<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobListing $jobListing): bool
    {
        return ($user->hasRole('admin') || $user->hasRole('employer'))
            && ($user->hasRole('admin') || $jobListing->user_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobListing $jobListing): bool
    {
        return ($user->hasRole('admin') || $user->hasRole('employer'))
            && ($user->hasRole('admin') || $jobListing->user_id === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobListing $jobListing): bool
    {
        return ($user->hasRole('admin') || $user->hasRole('employer'))
            && ($user->hasRole('admin') || $jobListing->user_id === $user->id);
    }

    /**
     * Perform the policy check.
     */
    public function viewApplications(User $user, JobListing $jobListing): bool
    {
        return ($user->hasRole('admin') || $user->hasRole('employer'))
            && ($user->hasRole('admin') || $jobListing->user_id === $user->id);
    }
}
