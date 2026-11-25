<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer', 'job_seeker']);
    }

    public function view(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'job_seeker') {
            return $application->user_id === $user->id;
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user, JobListing $jobListing): bool
    {
        if ($user->role !== 'job_seeker') {
            return false;
        }

        if (! $jobListing->is_active) {
            return false;
        }

        if ($jobListing->application_deadline && $jobListing->application_deadline->isPast()) {
            return false;
        }

        return ! $jobListing->applications()->where('user_id', $user->id)->exists();
    }

    public function viewCreate(User $user, JobListing $jobListing): bool
    {
        if ($user->role !== 'job_seeker') {
            return false;
        }

        if (! $jobListing->is_active) {
            return false;
        }

        if ($jobListing->application_deadline && $jobListing->application_deadline->isPast()) {
            return false;
        }

        return true;
    }

    public function updateStatus(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->id;
        }

        return false;
    }

    public function downloadResume(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'job_seeker') {
            return $application->user_id === $user->id;
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'job_seeker') {
            return $application->user_id === $user->id;
        }

        return false;
    }

    public function mergeResumes(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function recalculateScore(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->id;
        }

        return false;
    }
}
