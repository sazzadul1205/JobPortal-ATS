<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Carbon\Carbon;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer') || $user->hasRole('job-seeker');
    }

    public function view(User $user, Application $application): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('job-seeker')) {
            return $application->user_id === $user->getKey();
        }

        if ($user->hasRole('employer')) {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }

    public function create(User $user, JobListing $jobListing): bool
    {
        if (! $user->hasRole('job-seeker')) {
            return false;
        }

        if (! $jobListing->is_active) {
            return false;
        }

        if ($jobListing->application_deadline && Carbon::parse($jobListing->application_deadline)->lt(now()->startOfDay())) {
            return false;
        }

        return ! $jobListing->applications()->where('user_id', $user->getKey())->exists();
    }

    public function viewCreate(User $user, JobListing $jobListing): bool
    {
        if (! $user->hasRole('job-seeker')) {
            return false;
        }

        if (! $jobListing->is_active) {
            return false;
        }

        if ($jobListing->application_deadline && Carbon::parse($jobListing->application_deadline)->lt(now()->startOfDay())) {
            return false;
        }

        return true;
    }

    public function updateStatus(User $user, Application $application): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('employer')) {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }

    public function downloadResume(User $user, Application $application): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('job-seeker')) {
            return $application->user_id === $user->getKey();
        }

        if ($user->hasRole('employer')) {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }

    public function delete(User $user, Application $application): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('job-seeker')) {
            return $application->user_id === $user->getKey();
        }

        return false;
    }

    public function mergeResumes(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    public function recalculateScore(User $user, Application $application): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('employer')) {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }
}
