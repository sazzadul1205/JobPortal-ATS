<?php

namespace App\Policies;

use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    public function view(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    public function update(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('employer');
    }

    public function restore(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
