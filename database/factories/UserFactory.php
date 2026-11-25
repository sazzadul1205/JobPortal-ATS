<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['slug' => 'admin'], [
                'name' => 'Admin',
                'level' => 90,
                'description' => 'Administrator role',
                'is_default' => false,
            ]);
            $user->roles()->syncWithoutDetaching([$role->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ]]);
        });
    }

    public function employer(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['slug' => 'employer'], [
                'name' => 'Employer',
                'level' => 50,
                'description' => 'Employer role',
                'is_default' => false,
            ]);
            $user->roles()->syncWithoutDetaching([$role->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ]]);
        });
    }

    public function jobSeeker(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['slug' => 'job-seeker'], [
                'name' => 'Job Seeker',
                'level' => 20,
                'description' => 'Job Seeker role',
                'is_default' => true,
            ]);
            $user->roles()->syncWithoutDetaching([$role->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ]]);
        });
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['slug' => 'super-admin'], [
                'name' => 'Super Admin',
                'level' => 100,
                'description' => 'Super Administrator role',
                'is_default' => false,
            ]);
            $user->roles()->syncWithoutDetaching([$role->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ]]);
        });
    }
}
