<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Carbon\Carbon;

class ApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return \in_array($user->role, ['admin', 'employer', 'job_seeker']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'job_seeker') {
            return $application->user_id === $user->getKey();
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, JobListing $jobListing): bool
    {
        if ($user->role !== 'job_seeker') {
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

    /**
     * Determine whether the user can view the create form.
     */
    public function viewCreate(User $user, JobListing $jobListing): bool
    {
        if ($user->role !== 'job_seeker') {
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

    /**
     * Determine whether the user can update the application status.
     */
    public function updateStatus(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }

    /**
     * Determine whether the user can download the resume.
     */
    public function downloadResume(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'job_seeker') {
            return $application->user_id === $user->getKey();
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'job_seeker') {
            return $application->user_id === $user->getKey();
        }

        return false;
    }

    /**
     * Determine whether the user can merge resumes.
     */
    public function mergeResumes(User $user): bool
    {
        return \in_array($user->role, ['admin', 'employer']);
    }

    /**
     * Determine whether the user can recalculate the score.
     */
    public function recalculateScore(User $user, Application $application): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'employer') {
            return $application->jobListing->user_id === $user->getKey();
        }

        return false;
    }
}
