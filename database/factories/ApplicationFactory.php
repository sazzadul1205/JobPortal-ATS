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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
        $status = fake()->randomElement($statuses);

        $hasAtsScore = fake()->boolean(70);

        return [
            'job_listing_id' => JobListing::inRandomOrder()->first()?->id ?? JobListing::factory(),
            'user_id' => User::where('role', 'job_seeker')->inRandomOrder()->first()?->id ?? User::factory()->jobSeeker(),
            'applicant_profile_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'resume_path' => 'resumes/'.fake()->uuid().'.pdf',
            'expected_salary' => fake()->optional(0.5)->randomFloat(2, 30000, 500000),
            'ats_score' => $hasAtsScore ? [
                'total' => fake()->numberBetween(0, 100),
                'percentage' => fake()->numberBetween(0, 100),
                'matched_count' => fake()->numberBetween(0, 20),
                'total_keywords' => fake()->numberBetween(10, 30),
                'extracted_skills' => fake()->randomElements([
                    'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                    'Docker', 'AWS', 'Git', 'TypeScript', 'GraphQL',
                ], fake()->numberBetween(3, 8)),
                'extracted_experience_years' => fake()->numberBetween(0, 15),
                'extracted_education' => fake()->randomElement(['bachelor', 'master', 'phd', 'diploma', 'high-school']),
                'analysis_details' => [
                    'level' => fake()->randomElement(['Excellent', 'Good', 'Fair', 'Needs Improvement']),
                    'message' => fake()->sentence(),
                    'matched_count' => fake()->numberBetween(0, 20),
                    'missing_count' => fake()->numberBetween(0, 15),
                    'top_matched' => fake()->randomElements(['JavaScript', 'React', 'Node.js', 'Python', 'SQL'], 3),
                    'critical_missing' => fake()->randomElements(['Kubernetes', 'GraphQL', 'TypeScript', 'AWS'], 2),
                ],
            ] : null,
            'matched_keywords' => $hasAtsScore ? fake()->randomElements([
                'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                'Docker', 'AWS', 'Git', 'TypeScript', 'GraphQL',
                'REST API', 'MongoDB', 'Redis', 'Docker', 'Kubernetes',
            ], fake()->numberBetween(5, 15)) : null,
            'missing_keywords' => $hasAtsScore ? fake()->randomElements([
                'Kubernetes', 'GraphQL', 'TypeScript', 'AWS',
                'Microservices', 'System Design', 'CI/CD', 'Terraform',
            ], fake()->numberBetween(2, 8)) : null,
            'status' => $status,
            'employer_notes' => fake()->optional(0.3)->paragraph(),
        ];
    }

    /**
     * Indicate that the application is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the application is reviewed.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
        ]);
    }

    /**
     * Indicate that the application is shortlisted.
     */
    public function shortlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shortlisted',
        ]);
    }

    /**
     * Indicate that the application is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the application is hired.
     */
    public function hired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'hired',
        ]);
    }

    /**
     * Indicate that the application has ATS score.
     */
    public function withAtsScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'ats_score' => [
                'total' => fake()->numberBetween(40, 95),
                'percentage' => fake()->numberBetween(40, 95),
                'matched_count' => fake()->numberBetween(10, 25),
                'total_keywords' => fake()->numberBetween(15, 30),
                'extracted_skills' => fake()->randomElements([
                    'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                    'Docker', 'AWS', 'Git', 'TypeScript', 'GraphQL',
                ], fake()->numberBetween(5, 10)),
                'extracted_experience_years' => fake()->numberBetween(2, 10),
                'extracted_education' => fake()->randomElement(['bachelor', 'master']),
                'analysis_details' => [
                    'level' => fake()->randomElement(['Excellent', 'Good', 'Fair']),
                    'message' => fake()->sentence(),
                    'matched_count' => fake()->numberBetween(10, 25),
                    'missing_count' => fake()->numberBetween(2, 10),
                    'top_matched' => fake()->randomElements(['JavaScript', 'React', 'Node.js', 'Python', 'SQL'], 3),
                    'critical_missing' => fake()->randomElements(['Kubernetes', 'GraphQL', 'TypeScript', 'AWS'], 2),
                ],
            ],
            'matched_keywords' => fake()->randomElements([
                'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                'Docker', 'AWS', 'Git', 'TypeScript', 'GraphQL',
                'REST API', 'MongoDB', 'Redis', 'Docker', 'Kubernetes',
            ], fake()->numberBetween(8, 15)),
            'missing_keywords' => fake()->randomElements([
                'Kubernetes', 'GraphQL', 'TypeScript', 'AWS',
                'Microservices', 'System Design', 'CI/CD', 'Terraform',
            ], fake()->numberBetween(2, 6)),
        ]);
    }

    /**
     * Indicate that the application has high ATS score.
     */
    public function highAtsScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'ats_score' => [
                'total' => fake()->numberBetween(80, 100),
                'percentage' => fake()->numberBetween(80, 100),
                'matched_count' => fake()->numberBetween(20, 28),
                'total_keywords' => fake()->numberBetween(22, 30),
                'extracted_skills' => fake()->randomElements([
                    'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                    'Docker', 'AWS', 'Git', 'TypeScript', 'GraphQL',
                    'Kubernetes', 'Microservices', 'System Design', 'CI/CD', 'Terraform',
                ], fake()->numberBetween(10, 15)),
                'extracted_experience_years' => fake()->numberBetween(5, 15),
                'extracted_education' => fake()->randomElement(['bachelor', 'master', 'phd']),
                'analysis_details' => [
                    'level' => 'Excellent',
                    'message' => 'Outstanding match with all critical requirements met.',
                    'matched_count' => fake()->numberBetween(20, 28),
                    'missing_count' => fake()->numberBetween(0, 5),
                    'top_matched' => ['JavaScript', 'React', 'Node.js', 'TypeScript', 'AWS'],
                    'critical_missing' => [],
                ],
            ],
            'matched_keywords' => fake()->randomElements([
                'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                'Docker', 'AWS', 'Git', 'TypeScript', 'GraphQL',
                'REST API', 'MongoDB', 'Redis', 'Kubernetes',
                'Microservices', 'System Design', 'CI/CD', 'Terraform',
                'PostgreSQL',
            ], fake()->numberBetween(15, 19)),
            'missing_keywords' => fake()->randomElements([
                'GraphQL', 'Terraform',
            ], fake()->numberBetween(0, 2)),
        ]);
    }

    /**
     * Indicate that the application has low ATS score.
     */
    public function lowAtsScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'ats_score' => [
                'total' => fake()->numberBetween(0, 39),
                'percentage' => fake()->numberBetween(0, 39),
                'matched_count' => fake()->numberBetween(0, 8),
                'total_keywords' => fake()->numberBetween(15, 30),
                'extracted_skills' => fake()->randomElements([
                    'JavaScript', 'React', 'Node.js', 'Python', 'SQL',
                ], fake()->numberBetween(1, 4)),
                'extracted_experience_years' => fake()->numberBetween(0, 3),
                'extracted_education' => fake()->randomElement(['high-school', 'diploma', 'bachelor']),
                'analysis_details' => [
                    'level' => 'Needs Improvement',
                    'message' => 'Significant gaps in required skills and experience.',
                    'matched_count' => fake()->numberBetween(0, 8),
                    'missing_count' => fake()->numberBetween(15, 25),
                    'top_matched' => fake()->randomElements(['JavaScript', 'React', 'Python'], 2),
                    'critical_missing' => ['TypeScript', 'AWS', 'Docker', 'Kubernetes', 'GraphQL', 'System Design'],
                ],
            ],
            'matched_keywords' => fake()->randomElements([
                'JavaScript', 'React', 'Python',
            ], fake()->numberBetween(1, 3)),
            'missing_keywords' => fake()->randomElements([
                'TypeScript', 'AWS', 'Docker', 'Kubernetes', 'GraphQL',
                'System Design', 'CI/CD', 'Terraform', 'Microservices',
            ], fake()->numberBetween(5, 9)),
        ]);
    }
}
