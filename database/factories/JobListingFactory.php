<?php

namespace Database\Factories;

use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobListing>
 */
class JobListingFactory extends Factory
{
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

        $jobTypes = ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'];
        $experienceLevels = ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive'];
        $educationRequirements = ['high-school', 'diploma', 'associate', 'bachelor', 'master', 'phd', 'any'];

        $benefits = [
            'Health insurance', 'Dental insurance', 'Vision insurance', '401(k) matching',
            'Flexible working hours', 'Remote work options', 'Professional development budget',
            'Annual leave', 'Sick leave', 'Parental leave', 'Gym membership', 'Free meals',
            'Transportation allowance', 'Equipment allowance', 'Conference budget',
        ];

        $skills = [
            'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'Node.js', 'Python',
            'Java', 'Go', 'PHP', 'Laravel', 'Spring Boot', 'Django', 'Express.js', 'Next.js',
            'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Elasticsearch', 'Docker', 'Kubernetes',
            'AWS', 'Azure', 'GCP', 'CI/CD', 'Git', 'Linux', 'GraphQL', 'REST API',
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

        $publishAt = fake()->optional(0.3)->dateTimeBetween('-30 days', 'now');

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->numberBetween(1000, 9999) . '-' . fake()->unique()->randomNumber(5),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraphs(3, true),
            'job_type' => fake()->randomElement($jobTypes),
            'salary_min' => fake()->optional(0.8)->randomFloat(2, 50000, 200000),
            'salary_max' => fake()->optional(0.8)->randomFloat(2, 200000, 500000),
            'is_salary_negotiable' => fake()->boolean(30),
            'as_per_companies_policy' => fake()->boolean(10),
            'category_id' => fn () => JobCategory::inRandomOrder()->first()?->id ?? JobCategory::factory(),
            'experience_level' => fake()->randomElement($experienceLevels),
            'education_requirement' => fake()->optional(0.7)->randomElement($educationRequirements),
            'education_details' => fake()->optional(0.5)->sentence(),
            'benefits' => fake()->randomElements($benefits, fake()->numberBetween(3, 8)),
            'skills' => fake()->randomElements($skills, fake()->numberBetween(5, 15)),
            'responsibilities' => fake()->randomElements($responsibilities, fake()->numberBetween(4, 10)),
            'keywords' => fake()->randomElements($keywords, fake()->numberBetween(10, 25)),
            'application_deadline' => fake()->dateTimeBetween('+30 days', '+90 days')->format('Y-m-d'),
            'publish_at' => $publishAt ? $publishAt->format('Y-m-d') : null,
            'views_count' => fake()->numberBetween(0, 500),
            'is_active' => true,
            'user_id' => User::factory(),
            'required_facebook_link' => fake()->boolean(20),
            'required_linkedin_link' => fake()->boolean(15),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state([
            'application_deadline' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'is_active' => false,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state([
            'publish_at' => fake()->dateTimeBetween('+5 days', '+30 days')->format('Y-m-d'),
            'is_active' => false,
        ]);
    }

    public function remote(): static
    {
        return $this->state(['job_type' => 'remote']);
    }

    public function withLocation($location): static
    {
        return $this->afterCreating(function (JobListing $listing) use ($location) {
            $listing->locations()->attach($location);
        });
    }
}
