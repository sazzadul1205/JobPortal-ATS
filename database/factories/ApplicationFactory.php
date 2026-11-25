<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        $statuses = ['pending', 'shortlisted', 'rejected', 'hired'];
        $status = fake()->randomElement($statuses);
        $hasAtsScore = fake()->boolean(70);

        return [
            'job_listing_id' => JobListing::inRandomOrder()->first()?->id ?? JobListing::factory(),
            'user_id' => User::factory(),
            'applicant_profile_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'education_level' => fake()->optional(0.7)->randomElement(['high-school', 'diploma', 'bachelor', 'master', 'phd']),
            'years_of_experience' => fake()->optional(0.7)->numberBetween(0, 15),
            'resume_path' => 'resumes/' . fake()->uuid() . '.pdf',
            'expected_salary' => fake()->optional(0.5)->randomFloat(2, 30000, 500000),
            'ats_score' => $hasAtsScore ? [
                'percentage' => fake()->numberBetween(0, 100),
                'matched_count' => fake()->numberBetween(0, 20),
                'total_keywords' => fake()->numberBetween(10, 30),
            ] : null,
            'matched_keywords' => $hasAtsScore ? fake()->randomElements([
                'JavaScript', 'React', 'Node.js', 'Python', 'SQL', 'Docker', 'AWS',
                'Git', 'TypeScript', 'GraphQL', 'REST API', 'MongoDB', 'Redis',
            ], fake()->numberBetween(5, 15)) : null,
            'missing_keywords' => $hasAtsScore ? fake()->randomElements([
                'Kubernetes', 'GraphQL', 'TypeScript', 'AWS', 'Microservices',
                'System Design', 'CI/CD', 'Terraform',
            ], fake()->numberBetween(2, 8)) : null,
            'ats_calculation_status' => 'completed',
            'ats_attempt_count' => fake()->numberBetween(1, 3),
            'ats_last_attempted_at' => now(),
            'status' => $status,
            'employer_notes' => fake()->optional(0.3)->paragraph(),
            'facebook_link' => fake()->optional(0.2)->url(),
            'linkedin_link' => fake()->optional(0.3)->url(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function shortlisted(): static
    {
        return $this->state(['status' => 'shortlisted']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function hired(): static
    {
        return $this->state(['status' => 'hired']);
    }

    public function withAtsScore(): static
    {
        return $this->state([
            'ats_score' => [
                'percentage' => fake()->numberBetween(40, 95),
                'matched_count' => fake()->numberBetween(10, 25),
                'total_keywords' => fake()->numberBetween(15, 30),
            ],
            'matched_keywords' => fake()->randomElements([
                'JavaScript', 'React', 'Node.js', 'Python', 'SQL', 'Docker', 'AWS',
                'Git', 'TypeScript', 'GraphQL', 'REST API', 'MongoDB', 'Redis',
            ], fake()->numberBetween(8, 15)),
            'missing_keywords' => fake()->randomElements([
                'Kubernetes', 'GraphQL', 'TypeScript', 'AWS', 'Microservices',
                'System Design', 'CI/CD', 'Terraform',
            ], fake()->numberBetween(2, 6)),
            'ats_calculation_status' => 'completed',
        ]);
    }

    public function highAtsScore(): static
    {
        return $this->state([
            'ats_score' => [
                'percentage' => fake()->numberBetween(80, 100),
                'matched_count' => fake()->numberBetween(20, 28),
                'total_keywords' => fake()->numberBetween(22, 30),
            ],
            'matched_keywords' => fake()->randomElements([
                'JavaScript', 'React', 'Node.js', 'Python', 'SQL', 'Docker', 'AWS',
                'Git', 'TypeScript', 'GraphQL', 'REST API', 'MongoDB', 'Redis',
                'Kubernetes', 'Microservices', 'System Design', 'CI/CD', 'Terraform',
            ], fake()->numberBetween(15, 19)),
            'missing_keywords' => fake()->randomElements(['GraphQL', 'Terraform'], fake()->numberBetween(0, 2)),
            'ats_calculation_status' => 'completed',
        ]);
    }

    public function lowAtsScore(): static
    {
        return $this->state([
            'ats_score' => [
                'percentage' => fake()->numberBetween(0, 39),
                'matched_count' => fake()->numberBetween(0, 8),
                'total_keywords' => fake()->numberBetween(15, 30),
            ],
            'matched_keywords' => fake()->randomElements(['JavaScript', 'React', 'Python'], fake()->numberBetween(1, 3)),
            'missing_keywords' => fake()->randomElements([
                'TypeScript', 'AWS', 'Docker', 'Kubernetes', 'GraphQL',
                'System Design', 'CI/CD', 'Terraform', 'Microservices',
            ], fake()->numberBetween(5, 9)),
            'ats_calculation_status' => 'completed',
        ]);
    }
}
