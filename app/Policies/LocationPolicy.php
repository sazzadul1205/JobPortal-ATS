<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function view(User $user, Location $location): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function update(User $user, Location $location): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function delete(User $user, Location $location): bool
    {
        return in_array($user->role, ['admin', 'employer']);
    }

    public function restore(User $user, Location $location): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return $user->role === 'admin';
    }
}
