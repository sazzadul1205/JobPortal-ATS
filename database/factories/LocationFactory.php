<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = [
            ['name' => 'All Over Bangladesh', 'address' => null],
            ['name' => 'Dhaka', 'address' => 'Dhaka Division, Bangladesh'],
            ['name' => 'Chittagong', 'address' => 'Chittagong Division, Bangladesh'],
            ['name' => 'Sylhet', 'address' => 'Sylhet Division, Bangladesh'],
            ['name' => 'Rajshahi', 'address' => 'Rajshahi Division, Bangladesh'],
            ['name' => 'Khulna', 'address' => 'Khulna Division, Bangladesh'],
            ['name' => 'Barisal', 'address' => 'Barisal Division, Bangladesh'],
            ['name' => 'Rangpur', 'address' => 'Rangpur Division, Bangladesh'],
            ['name' => 'Mymensingh', 'address' => 'Mymensingh Division, Bangladesh'],
        ];

        $location = fake()->randomElement($locations);

        return [
            'name' => $location['name'],
            'address' => $location['address'],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
