<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobListing>
 */
class JobListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Senior Frontend Developer',
            'Backend Engineer (Node.js)',
            'Full Stack Developer',
            'DevOps Engineer',
            'Mobile App Developer (React Native)',
            'Data Scientist',
            'Machine Learning Engineer',
            'UI/UX Designer',
            'QA Automation Engineer',
            'Software Architect',
            'Technical Lead',
            'Product Manager',
            'Business Analyst',
            'Project Manager',
            'System Administrator',
            'Database Administrator',
            'Security Engineer',
            'Cloud Solutions Architect',
            'Frontend Developer (Vue.js)',
            'PHP/Laravel Developer',
            'Python Developer',
            'Java Spring Boot Developer',
            'Go/Golang Developer',
            'React Native Developer',
            'Flutter Developer',
        ];

        $title = fake()->randomElement($titles);

        $jobTypes = ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid'];
        $experienceLevels = ['entry', 'junior', 'mid', 'senior', 'lead', 'principal'];
        $educationRequirements = ['high-school', 'diploma', 'bachelor', 'master', 'phd', 'any'];

        $benefits = [
            'Health insurance',
            'Dental insurance',
            'Vision insurance',
            '401(k) matching',
            'Flexible working hours',
            'Remote work options',
            'Professional development budget',
            'Annual leave',
            'Sick leave',
            'Parental leave',
            'Gym membership',
            'Free meals',
            'Transportation allowance',
            'Equipment allowance',
            'Conference budget',
        ];

        $skills = [
            'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular',
            'Node.js', 'Python', 'Java', 'Go', 'PHP', 'Laravel',
            'Spring Boot', 'Django', 'Express.js', 'Next.js',
            'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Elasticsearch',
            'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP',
            'CI/CD', 'Git', 'Linux', 'GraphQL', 'REST API',
            'Microservices', 'System Design', 'Agile', 'Scrum',
        ];

        $responsibilities = [
            'Develop and maintain web applications',
            'Write clean, scalable, and testable code',
            'Participate in code reviews',
            'Collaborate with cross-functional teams',
            'Design and implement new features',
            'Optimize application performance',
            'Troubleshoot and debug issues',
            'Write technical documentation',
            'Mentor junior developers',
            'Contribute to architecture decisions',
        ];

        $keywords = array_merge($skills, $jobTypes, $experienceLevels);

        $scheduleStart = fake()->optional(0.3)->dateTimeBetween('now', '+30 days');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->numberBetween(1000, 9999).'-'.fake()->unique()->randomNumber(5),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraphs(3, true),
            'salary' => fake()->optional(0.8)->randomElement([
                'BDT 50,000 - 80,000',
                'BDT 80,000 - 120,000',
                'BDT 120,000 - 180,000',
                'BDT 180,000 - 250,000',
                'BDT 250,000 - 350,000',
                'BDT 350,000 - 500,000',
                'Negotiable',
                'Competitive',
            ]),
            'job_type' => fake()->randomElement($jobTypes),
            'experience_level' => fake()->randomElement($experienceLevels),
            'education_requirement' => fake()->optional(0.7)->randomElement($educationRequirements),
            'benefits' => fake()->randomElements($benefits, fake()->numberBetween(3, 8)),
            'skills' => fake()->randomElements($skills, fake()->numberBetween(5, 15)),
            'responsibilities' => fake()->randomElements($responsibilities, fake()->numberBetween(4, 10)),
            'keywords' => fake()->randomElements($keywords, fake()->numberBetween(10, 25)),
            'application_deadline' => fake()->dateTimeBetween('+30 days', '+90 days')->format('Y-m-d'),
            'schedule_start_date' => $scheduleStart ? $scheduleStart->format('Y-m-d') : null,
            'is_active' => true,
            'user_id' => User::factory()->employer(),
            'show_linkedin' => fake()->boolean(20),
            'show_facebook' => fake()->boolean(15),
        ];
    }

    /**
     * Indicate that the job listing is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the job listing is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'application_deadline' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the job listing is scheduled for future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_start_date' => fake()->dateTimeBetween('+5 days', '+30 days')->format('Y-m-d'),
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the job listing is remote.
     */
    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'job_type' => 'remote',
        ]);
    }
}
