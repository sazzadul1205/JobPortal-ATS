<?php

namespace App\Policies;

use App\Models\JobCategory;
use App\Models\User;

class JobCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function view(User $user, JobCategory $category): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function update(User $user, JobCategory $category): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function delete(User $user, JobCategory $category): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function restore(User $user, JobCategory $category): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, JobCategory $category): bool
    {
        return $user->role === 'admin';
    }
}
